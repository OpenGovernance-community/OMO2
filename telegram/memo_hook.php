<?php
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/common/patreon.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/openai.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/telegram.php");

	$minTimeMessage = 10; // Duree minimum en seconde du message pour justifier une transformation

	function saveLocalSession($data, $name) {
		if (!is_dir("data")) {
			mkdir("data", 0777, true);
		}

		file_put_contents("data/".$name.".txt", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
	}

	function loadLocalSession($name) {
		$path = "data/".$name.".txt";
		if (!file_exists($path)) {
			return json_decode("{}");
		}

		$data = file_get_contents($path);
		$decoded = json_decode($data);
		return is_object($decoded) ? $decoded : json_decode("{}");
	}

	function getTelegramActorId(array $update): int {
		if (isset($update['message']['from']['id'])) {
			return (int)$update['message']['from']['id'];
		}

		if (isset($update['callback_query']['from']['id'])) {
			return (int)$update['callback_query']['from']['id'];
		}

		return 0;
	}

	function getMessageThreadId(array $message): ?int {
		return isset($message['message_thread_id']) ? (int)$message['message_thread_id'] : null;
	}

	function loadTelegramUserByActorId(int $actorId): \dbObject\User {
		$user = new \dbObject\User();
		if ($actorId > 0) {
			$user->load(array('telegramID', $actorId));
		}
		return $user;
	}

	function getTelegramConnectedUserLabel(\dbObject\User $user): string {
		$firstname = trim((string)$user->get('firstname'));
		$lastname = trim((string)$user->get('lastname'));
		$fullName = trim($firstname." ".$lastname);
		if ($fullName !== '') {
			return $fullName;
		}

		$email = trim((string)$user->get('email'));
		if ($email !== '') {
			return $email;
		}

		$username = trim((string)$user->get('username'));
		if ($username !== '') {
			return $username;
		}

		return "votre compte";
	}

	function isTelegramPrivateChat(array $message): bool {
		return isset($message['chat']['id'], $message['from']['id'])
			&& (int)$message['chat']['id'] === (int)$message['from']['id'];
	}

	function disconnectTelegramUser(\dbObject\User $user): bool {
		if ((int)$user->getId() <= 0) {
			return false;
		}

		$user->set('telegramID', null);
		$saveResult = $user->save();
		return is_array($saveResult) ? !empty($saveResult['status']) : $saveResult === true;
	}

	function clearTelegramConnectState(\stdClass $sessionData): void {
		unset($sessionData->connect);
	}

	function beginTelegramConnectFlow(int $actorId): \stdClass {
		$sessionData = loadLocalSession($actorId);
		clearTelegramConnectState($sessionData);
		$sessionData->connect = (object) array(
			'step' => 'await_email',
			'startedAt' => time(),
		);
		saveLocalSession($sessionData, $actorId);
		return $sessionData;
	}

	function startTelegramConnectCodeRequest(int $actorId, \dbObject\User $targetUser): array {
		$email = trim((string)$targetUser->get('email'));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return array(
				'status' => false,
				'message' => "Ce compte n'a pas d'adresse e-mail valide.",
			);
		}

		$organizationContext = commonResolveOrganizationContext(1);
		$loginRequest = commonSendLoginCode((int)$targetUser->getId(), $email, $organizationContext, 0, '/');
		if ($loginRequest === false || empty($loginRequest['request_token'])) {
			return array(
				'status' => false,
				'message' => "Impossible d'envoyer le code pour le moment.",
			);
		}

		$sessionData = loadLocalSession($actorId);
		$sessionData->connect = (object) array(
			'step' => 'await_code',
			'userId' => (int)$targetUser->getId(),
			'email' => $email,
			'requestToken' => (string)$loginRequest['request_token'],
			'expiresAt' => time() + 300,
		);
		saveLocalSession($sessionData, $actorId);

		$message = !empty($loginRequest['delivery_failed'])
			? "Le code a peut-etre deja ete envoye a ".$email.". Si vous le recevez, repondez ici avec ce code dans les 5 minutes."
			: "Un code a ete envoye a ".$email.". Repondez ici avec ce code dans les 5 minutes.";

		return array(
			'status' => true,
			'message' => $message,
		);
	}

	function completeTelegramConnectFlow(int $actorId, string $rawCode): array {
		$sessionData = loadLocalSession($actorId);
		$connectState = isset($sessionData->connect) && is_object($sessionData->connect)
			? $sessionData->connect
			: null;

		if (!$connectState || ($connectState->step ?? '') !== 'await_code') {
			return array(
				'status' => false,
				'message' => "Aucune connexion n'est en attente. Envoyez /connect pour recommencer.",
			);
		}

		$requestToken = trim((string)($connectState->requestToken ?? ''));
		$userId = (int)($connectState->userId ?? 0);
		$code = commonNormalizeLoginCode($rawCode);

		if ($requestToken === '' || $userId <= 0) {
			clearTelegramConnectState($sessionData);
			saveLocalSession($sessionData, $actorId);
			return array(
				'status' => false,
				'message' => "La demande de connexion est invalide. Envoyez /connect pour recommencer.",
			);
		}

		if (strlen($code) !== 6) {
			return array(
				'status' => false,
				'message' => "Veuillez saisir le code complet a 6 caracteres.",
			);
		}

		$loginToken = \dbObject\UserLoginToken::findByToken($requestToken);
		if (!$loginToken) {
			clearTelegramConnectState($sessionData);
			saveLocalSession($sessionData, $actorId);
			return array(
				'status' => false,
				'message' => "Le code n'est plus valide. Envoyez /connect pour recommencer.",
			);
		}

		if ((int)$loginToken->get('IDuser') !== $userId || (int)$loginToken->get('used') > 0) {
			clearTelegramConnectState($sessionData);
			saveLocalSession($sessionData, $actorId);
			return array(
				'status' => false,
				'message' => "Le code n'est plus valide. Envoyez /connect pour recommencer.",
			);
		}

		$expiresAt = $loginToken->get('expires_at');
		if (!$expiresAt instanceof \DateTimeInterface || $expiresAt <= new \DateTime()) {
			clearTelegramConnectState($sessionData);
			saveLocalSession($sessionData, $actorId);
			return array(
				'status' => false,
				'message' => "Le code a expire. Envoyez /connect pour recommencer.",
			);
		}

		if ((int)$loginToken->get('attempt_count') >= 5) {
			clearTelegramConnectState($sessionData);
			saveLocalSession($sessionData, $actorId);
			return array(
				'status' => false,
				'message' => "Trop d'essais. Envoyez /connect pour recommencer.",
			);
		}

		if (!password_verify($code, (string)$loginToken->get('code_hash'))) {
			$loginToken->incrementAttemptCount();
			$remainingAttempts = max(0, 5 - (int)$loginToken->get('attempt_count'));
			if ($remainingAttempts <= 0) {
				clearTelegramConnectState($sessionData);
				saveLocalSession($sessionData, $actorId);
				return array(
					'status' => false,
					'message' => "Trop d'essais. Envoyez /connect pour recommencer.",
				);
			}

			return array(
				'status' => false,
				'message' => "Code incorrect. Il reste ".$remainingAttempts." essai(s).",
			);
		}

		$targetUser = new \dbObject\User();
		if (!$targetUser->load($userId)) {
			$loginToken->markUsed();
			clearTelegramConnectState($sessionData);
			saveLocalSession($sessionData, $actorId);
			return array(
				'status' => false,
				'message' => "Le compte utilisateur est introuvable.",
			);
		}

		$alreadyLinkedUser = loadTelegramUserByActorId($actorId);
		if ($alreadyLinkedUser->getId() > 0 && (int)$alreadyLinkedUser->getId() !== $userId) {
			$alreadyLinkedUser->set('telegramID', null);
			$alreadyLinkedUser->save();
		}

		$targetUser->set('telegramID', (string)$actorId);
		$saveResult = $targetUser->save();
		$loginToken->markUsed();

		if (!is_array($saveResult) || empty($saveResult['status'])) {
			return array(
				'status' => false,
				'message' => "Le compte Telegram n'a pas pu etre enregistre.",
			);
		}

		clearTelegramConnectState($sessionData);
		saveLocalSession($sessionData, $actorId);

		return array(
			'status' => true,
			'message' => "Connexion confirmee avec ".getTelegramConnectedUserLabel($targetUser).".",
		);
	}

	function handleTelegramConnectConversation(array $message, \dbObject\User $user): bool {
		$text = isset($message['text']) ? trim((string)$message['text']) : '';
		$actorId = isset($message['from']['id']) ? (int)$message['from']['id'] : 0;
		$chatId = $message['chat']['id'] ?? null;
		$threadId = getMessageThreadId($message);
		if ($text === '' || $actorId <= 0 || $chatId === null) {
			return false;
		}

		if (($message['chat']['id'] ?? null) != ($message['from']['id'] ?? null)) {
			return false;
		}

		$sessionData = loadLocalSession($actorId);
		$connectState = isset($sessionData->connect) && is_object($sessionData->connect)
			? $sessionData->connect
			: null;
		if (!$connectState || !isset($connectState->step)) {
			return false;
		}

		if (preg_match('/^\//', $text)) {
			return false;
		}

		if (($connectState->step ?? '') === 'await_email') {
			$email = strtolower(trim($text));
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				sendMessage($chatId, "Veuillez repondre avec une adresse e-mail valide, ou /cancel.", null, $threadId);
				return true;
			}

			$targetUser = new \dbObject\User();
			if (!$targetUser->load(array('email', $email))) {
				sendMessage($chatId, "Aucun compte n'a ete trouve avec cette adresse. Reessayez ou envoyez /cancel.", null, $threadId);
				return true;
			}

			$result = startTelegramConnectCodeRequest($actorId, $targetUser);
			sendMessage($chatId, $result['message'] ?? "Impossible d'envoyer le code.", null, $threadId);
			return true;
		}

		if (($connectState->step ?? '') === 'await_code') {
			$result = completeTelegramConnectFlow($actorId, $text);
			sendMessage($chatId, $result['message'] ?? "Impossible de verifier le code.", null, $threadId);
			return true;
		}

		return false;
	}

	function formatDocumentLink(\dbObject\Document $document): string {
		return appBuildAbsoluteUrl("/memo/".$document->getId().($document->get("codeview") ? "/".$document->get("codeview") : ""));
	}

	function buildMemoActionButtons(): array {
		return array(
			array(
				array('text' => 'Options', 'callback_data' => 'btn_options'),
				array('text' => 'Delete', 'callback_data' => 'btn_delete', 'style' => 'danger'),
			),
			array(
				array('text' => 'Share', 'callback_data' => 'btn_share'),
				array('text' => 'Classer', 'callback_data' => 'btn_classify'),
			),
		);
	}

	function buildDeleteButtons(): array {
		return array(
			array(
				array('text' => 'Le résumé', 'callback_data' => 'btn_del_resume', 'style' => 'danger'),
				array('text' => 'Le fichier', 'callback_data' => 'btn_del_file', 'style' => 'danger'),
			),
			array(
				array('text' => 'Tout', 'callback_data' => 'btn_del_all', 'style' => 'danger'),
				array('text' => 'Annuler', 'callback_data' => 'btn_del_cancel'),
			),
		);
	}

	function loadLastDocumentForActor(int $actorId): ?\dbObject\Document {
		$data = loadLocalSession($actorId);
		if (!isset($data->lastDoc) || (int)$data->lastDoc <= 0) {
			return null;
		}

		$document = new \dbObject\Document();
		return $document->load((int)$data->lastDoc) ? $document : null;
	}

	function clearLastDocumentSessionFields(\stdClass $sessionData): void {
		unset($sessionData->lastDoc);
	}

	function clearLastMessageSessionFields(\stdClass $sessionData): void {
		unset($sessionData->lastID);
	}

	function deleteDocumentBundle(\dbObject\Document $document): bool {
		foreach ($document->getAltText() as $altText) {
			$altText->delete();
		}

		foreach ($document->getMedias() as $media) {
			$media->delete();
		}

		return (bool)$document->delete();
	}

	function buildHolonPathLabel(\dbObject\Organization $organization, ?\dbObject\Holon $selectedHolon = null): string {
		$parts = array(trim((string)$organization->get('name')));

		if ($selectedHolon) {
			foreach ($selectedHolon->getPathHolons() as $pathHolon) {
				if ((int)$pathHolon->get('IDtypeholon') === 4) {
					continue;
				}

				$name = trim((string)$pathHolon->get('name'));
				if ($name !== '') {
					$parts[] = $name;
				}
			}
		}

		return implode(" > ", array_filter($parts, function ($value) {
			return $value !== '';
		}));
	}

	function buildHolonChoiceLabel(\dbObject\Holon $holon): string {
		$typeLabel = $holon->getTypeLabel();
		$name = trim((string)$holon->get('name'));
		return $typeLabel." : ".($name !== '' ? $name : 'Sans nom');
	}

	function telegramUserCanCreateDocumentInHolon(\dbObject\User $user, \dbObject\Organization $organization, \dbObject\Holon $holon): bool {
		$userId = (int)$user->getId();
		$organizationId = (int)$organization->getId();
		$holonId = (int)$holon->getId();

		if ($userId <= 0 || $organizationId <= 0 || $holonId <= 0) {
			return false;
		}

		return $holon->isAllowed('CAN_CREATE_DOCUMENT', false, $userId);
	}

	function getVisibleHolonChildren(\dbObject\Holon $holon): array {
		$children = array();
		foreach ($holon->getChildren() as $child) {
			if (!(bool)$child->get('active') || !(bool)$child->get('visible')) {
				continue;
			}

			$children[] = $child;
		}

		return $children;
	}

	function holonHasDocumentCreationDestination(\dbObject\User $user, \dbObject\Organization $organization, \dbObject\Holon $holon, array &$availabilityCache = array()): bool {
		$cacheKey = (int)$user->getId().'_'.(int)$organization->getId().'_'.(int)$holon->getId();
		if (array_key_exists($cacheKey, $availabilityCache)) {
			return (bool)$availabilityCache[$cacheKey];
		}

		if (telegramUserCanCreateDocumentInHolon($user, $organization, $holon)) {
			$availabilityCache[$cacheKey] = true;
			return true;
		}

		foreach (getVisibleHolonChildren($holon) as $child) {
			if (holonHasDocumentCreationDestination($user, $organization, $child, $availabilityCache)) {
				$availabilityCache[$cacheKey] = true;
				return true;
			}
		}

		$availabilityCache[$cacheKey] = false;
		return false;
	}

	function getCreatableVisibleHolonChildren(\dbObject\User $user, \dbObject\Organization $organization, \dbObject\Holon $holon, array &$availabilityCache = array()): array {
		$children = array();

		foreach (getVisibleHolonChildren($holon) as $child) {
			if (!holonHasDocumentCreationDestination($user, $organization, $child, $availabilityCache)) {
				continue;
			}

			$children[] = $child;
		}

		return $children;
	}

	function collectHolonDescendantNavigationOptions(\dbObject\User $user, \dbObject\Organization $organization, \dbObject\Holon $holon, string $prefix = '', array &$availabilityCache = array()): array {
		$options = array();

		foreach (getVisibleHolonChildren($holon) as $child) {
			if (!holonHasDocumentCreationDestination($user, $organization, $child, $availabilityCache)) {
				continue;
			}

			$label = $prefix !== ''
				? $prefix." > ".buildHolonChoiceLabel($child)
				: buildHolonChoiceLabel($child);

			$options[] = array(
				'holon' => $child,
				'label' => $label,
				'action' => telegramUserCanCreateDocumentInHolon($user, $organization, $child) ? 'done' : 'nav',
			);

			$options = array_merge($options, collectHolonDescendantNavigationOptions($user, $organization, $child, $label, $availabilityCache));
		}

		return $options;
	}

	function telegramUserCanUseOrganization(\dbObject\User $user, int $organizationId): bool {
		if ((int)$user->getId() <= 0 || $organizationId <= 0) {
			return false;
		}

		$organizations = new \dbObject\ArrayOrganization();
		$organizations->loadAccessibleForUser((int)$user->getId(), $organizationId, 1);
		return count($organizations) > 0;
	}

	function telegramRoleCanReceiveGroupMemos(\dbObject\User $user, \dbObject\Organization $organization, \dbObject\Holon $role): bool {
		return (int)$role->get('IDtypeholon') === 1
			&& (bool)$role->get('active')
			&& (bool)$role->get('visible')
			&& telegramUserCanCreateDocumentInHolon($user, $organization, $role);
	}

	function telegramProjectCanReceiveGroupMemos(\dbObject\User $user, \dbObject\Project $project, ?\dbObject\Organization $organization = null, ?\dbObject\Holon $sourceRole = null): bool {
		$organizationId = (int)$project->get('IDorganization');
		$holonId = (int)$project->get('IDholon');
		if (
			(int)$user->getId() <= 0
			|| $organizationId <= 0
			|| !(bool)$project->get('active')
			|| \dbObject\Project::normalizeKind($project->get('project_kind')) !== \dbObject\Project::KIND_STANDARD
		) {
			return false;
		}

		if ($sourceRole instanceof \dbObject\Holon) {
			$projectHolon = $project->getHolon();
			if (!($projectHolon instanceof \dbObject\Holon) || !$projectHolon->isDescendantOf($sourceRole, true)) {
				return false;
			}

			if (!($organization instanceof \dbObject\Organization) || (int)$organization->getId() !== $organizationId) {
				$organization = new \dbObject\Organization();
				if (!$organization->load($organizationId)) {
					return false;
				}
			}

			return telegramRoleCanReceiveGroupMemos($user, $organization, $sourceRole);
		}

		return \dbObject\Document::canCreateInOrganizationContext(
			$organizationId,
			$holonId > 0 ? $holonId : null,
			(int)$user->getId(),
			0,
			false
		);
	}

	function telegramHolonHasGroupRoleDestination(\dbObject\User $user, \dbObject\Organization $organization, \dbObject\Holon $holon, array &$availabilityCache = array()): bool {
		$cacheKey = (int)$user->getId().'_'.(int)$organization->getId().'_'.(int)$holon->getId();
		if (array_key_exists($cacheKey, $availabilityCache)) {
			return (bool)$availabilityCache[$cacheKey];
		}

		if (telegramRoleCanReceiveGroupMemos($user, $organization, $holon)) {
			$availabilityCache[$cacheKey] = true;
			return true;
		}

		foreach (getVisibleHolonChildren($holon) as $child) {
			if (telegramHolonHasGroupRoleDestination($user, $organization, $child, $availabilityCache)) {
				$availabilityCache[$cacheKey] = true;
				return true;
			}
		}

		$availabilityCache[$cacheKey] = false;
		return false;
	}

	function telegramLoadEligibleGroupProjects(\dbObject\User $user, \dbObject\Organization $organization, \dbObject\Holon $sourceRole): array {
		$projects = new \dbObject\ArrayProject();
		$projects->loadForOrganization((int)$organization->getId(), true, \dbObject\Project::KIND_STANDARD);
		$eligibleProjects = array();
		foreach ($projects as $project) {
			if ($project instanceof \dbObject\Project && telegramProjectCanReceiveGroupMemos($user, $project, $organization, $sourceRole)) {
				$eligibleProjects[(int)$project->getId()] = $project;
			}
		}
		return $eligibleProjects;
	}

	function telegramOrganizationHasGroupDestination(\dbObject\User $user, \dbObject\Organization $organization): bool {
		$rootHolon = $organization->getStructuralRootHolon();
		$availabilityCache = array();
		if ($rootHolon instanceof \dbObject\Holon && telegramHolonHasGroupRoleDestination($user, $organization, $rootHolon, $availabilityCache)) {
			return true;
		}

		return false;
	}

	function buildTelegramGroupRolePrompt(\dbObject\User $user, int $organizationId = 0, int $holonId = 0): array {
		if ((int)$user->getId() <= 0) {
			return array('text' => "Connectez d'abord votre compte Telegram au bot en message prive avec /connect.", 'buttons' => null);
		}

		if ($organizationId <= 0) {
			$organizations = new \dbObject\ArrayOrganization();
			$organizations->loadAccessibleForUser((int)$user->getId());
			$buttons = array();
			foreach ($organizations as $organization) {
				if ($organization instanceof \dbObject\Organization && telegramOrganizationHasGroupDestination($user, $organization)) {
					$buttons[] = array(array(
						'text' => trim((string)$organization->get('name')),
						'callback_data' => 'tg_dest_org_'.(int)$organization->getId(),
					));
				}
			}

			if (count($buttons) === 0) {
				return array('text' => "Aucune organisation avec une destination autorisee n'est disponible.", 'buttons' => null);
			}

			$buttons[] = array(array('text' => 'Annuler', 'callback_data' => 'tg_dest_cancel'));
			return array('text' => "Connecter ce groupe\n\nChoisissez une organisation.", 'buttons' => $buttons);
		}

		$organization = new \dbObject\Organization();
		if (!$organization->load($organizationId) || !telegramUserCanUseOrganization($user, $organizationId)) {
			return buildTelegramGroupRolePrompt($user, 0, 0);
		}

		$rootHolon = $organization->getStructuralRootHolon();
		if (!($rootHolon instanceof \dbObject\Holon)) {
			return array('text' => "La structure de cette organisation est introuvable.", 'buttons' => array(array(array('text' => 'Organisations', 'callback_data' => 'tg_dest_root'))));
		}

		$currentHolon = $rootHolon;
		if ($holonId > 0) {
			$candidate = new \dbObject\Holon();
			if (
				$candidate->load($holonId)
				&& in_array((int)$candidate->get('IDtypeholon'), array(1, 2, 3), true)
				&& (bool)$candidate->get('active')
				&& (bool)$candidate->get('visible')
				&& $organization->containsHolon($candidate)
			) {
				$currentHolon = $candidate;
			}
		}

		$availabilityCache = array();
		$buttons = array();
		if (
			(int)$currentHolon->get('IDtypeholon') === 1
			&& telegramRoleCanReceiveGroupMemos($user, $organization, $currentHolon)
		) {
			$buttons[] = array(array(
				'text' => 'Selectionner ce role',
				'callback_data' => 'tg_dest_role_'.$organizationId.'_'.(int)$currentHolon->getId(),
			));
		}
		foreach (getVisibleHolonChildren($currentHolon) as $child) {
			if (!telegramHolonHasGroupRoleDestination($user, $organization, $child, $availabilityCache)) {
				continue;
			}

			if ((int)$child->get('IDtypeholon') === 1) {
				$buttons[] = array(array(
					'text' => 'Role : '.trim((string)$child->getDisplayName()),
					'callback_data' => 'tg_dest_holon_'.$organizationId.'_'.(int)$child->getId(),
				));
				continue;
			}

			if (in_array((int)$child->get('IDtypeholon'), array(2, 3), true)) {
				$buttons[] = array(array(
					'text' => $child->getTypeLabel().' : '.trim((string)$child->getDisplayName()),
					'callback_data' => 'tg_dest_holon_'.$organizationId.'_'.(int)$child->getId(),
				));
			}
		}

		if (
			(int)$currentHolon->get('IDtypeholon') === 1
			&& count(telegramLoadEligibleGroupProjects($user, $organization, $currentHolon)) > 0
		) {
			$buttons[] = array(array(
				'text' => 'Explorer les projets',
				'callback_data' => 'tg_dest_projects_'.$organizationId.'_'.(int)$currentHolon->getId().'_0',
			));
		}

		$parent = $currentHolon->getParentHolon();
		if ($currentHolon !== $rootHolon && $parent instanceof \dbObject\Holon) {
			$buttons[] = array(array('text' => 'Retour', 'callback_data' => 'tg_dest_holon_'.$organizationId.'_'.(int)$parent->getId()));
		}
		$buttons[] = array(
			array('text' => 'Organisations', 'callback_data' => 'tg_dest_root'),
			array('text' => 'Annuler', 'callback_data' => 'tg_dest_cancel'),
		);

		$text = "Connecter ce groupe\n\nStructure : ".buildHolonPathLabel($organization, $currentHolon === $rootHolon ? null : $currentHolon);
		$text .= (int)$currentHolon->get('IDtypeholon') === 1
			? "\n\nVous pouvez selectionner ce role ou explorer les projets rattaches a ce role."
			: "\n\nLes groupes et les cercles servent uniquement a naviguer. Choisissez un role.";
		return array('text' => $text, 'buttons' => $buttons);
	}

	function buildTelegramGroupProjectPrompt(\dbObject\User $user, int $organizationId, int $roleHolonId, int $parentProjectId = 0): array {
		$role = new \dbObject\Holon();
		if (
			!telegramUserCanUseOrganization($user, $organizationId)
			|| !$role->load($roleHolonId)
			|| (int)$role->get('IDtypeholon') !== 1
		) {
			return buildTelegramGroupRolePrompt($user, $organizationId, 0);
		}

		$organization = new \dbObject\Organization();
		if (!$organization->load($organizationId)) {
			return buildTelegramGroupRolePrompt($user, 0, 0);
		}

		$projects = telegramLoadEligibleGroupProjects($user, $organization, $role);
		$projectsById = array();
		$parentByProjectId = array();
		foreach ($projects as $project) {
			if (!($project instanceof \dbObject\Project)) {
				continue;
			}
			$projectId = (int)$project->getId();
			$projectsById[$projectId] = $project;
			$parentByProjectId[$projectId] = (int)$project->get('IDproject_parent');
		}

		$childrenByParent = array();
		foreach ($projectsById as $projectId => $project) {
			$parentId = (int)($parentByProjectId[$projectId] ?? 0);
			if (!isset($projectsById[$parentId])) {
				$parentId = 0;
			}
			$childrenByParent[$parentId][] = $project;
			$parentByProjectId[$projectId] = $parentId;
		}

		$hasEligibleDescendant = function (int $projectId) use ($projectsById): bool {
			return isset($projectsById[$projectId]);
		};

		if ($parentProjectId > 0 && !isset($projectsById[$parentProjectId])) {
			$parentProjectId = 0;
		}

		$buttons = array();
		foreach ($childrenByParent[$parentProjectId] ?? array() as $project) {
			if (!($project instanceof \dbObject\Project) || !$hasEligibleDescendant((int)$project->getId())) {
				continue;
			}

			$projectId = (int)$project->getId();
			$title = trim((string)$project->get('title'));
			$buttons[] = array(array('text' => 'Selectionner : '.$title, 'callback_data' => 'tg_dest_project_'.$organizationId.'_'.$roleHolonId.'_'.$projectId));
			if (count($childrenByParent[$projectId] ?? array()) > 0) {
				$buttons[] = array(array('text' => 'Explorer : '.$title, 'callback_data' => 'tg_dest_projects_'.$organizationId.'_'.$roleHolonId.'_'.$projectId));
			}
		}

		if ($parentProjectId > 0) {
			$buttons[] = array(array('text' => 'Retour', 'callback_data' => 'tg_dest_projects_'.$organizationId.'_'.$roleHolonId.'_'.(int)($parentByProjectId[$parentProjectId] ?? 0)));
		}
		$buttons[] = array(
			array('text' => 'Retour au role', 'callback_data' => 'tg_dest_holon_'.$organizationId.'_'.$roleHolonId),
			array('text' => 'Annuler', 'callback_data' => 'tg_dest_cancel'),
		);

		$path = array();
		$currentId = $parentProjectId;
		while ($currentId > 0 && isset($projectsById[$currentId])) {
			array_unshift($path, trim((string)$projectsById[$currentId]->get('title')));
			$currentId = (int)($parentByProjectId[$currentId] ?? 0);
		}
		$text = "Connecter ce groupe a un projet\n\nRole : ".trim((string)$role->getDisplayName());
		$text .= count($path) > 0 ? "\nProjet : ".implode(' > ', $path) : "\nChoisissez un projet rattache a ce role.";
		return array('text' => $text, 'buttons' => $buttons);
	}

	function telegramGroupDestinationLabel(\dbObject\TelegramChatDestination $destination): string {
		$context = $destination->getDocumentContext();
		if (!is_array($context)) {
			return 'destination invalide';
		}

		if (($context['type'] ?? '') === \dbObject\TelegramChatDestination::TYPE_ROLE && ($context['role'] ?? null) instanceof \dbObject\Holon) {
			$organization = new \dbObject\Organization();
			if ($organization->load((int)$context['organizationId'])) {
				return 'role '.buildHolonPathLabel($organization, $context['role']);
			}
			return 'role '.trim((string)$context['role']->getDisplayName());
		}

		$project = $context['project'] ?? null;
		return $project instanceof \dbObject\Project ? 'projet '.trim((string)$project->get('title')) : 'destination invalide';
	}

	function buildClassificationPrompt(\dbObject\User $user, int $selectedOrganizationId = 0, int $selectedHolonId = 0): array {
		if ($user->getId() <= 0) {
			return array(
				'text' => "Votre compte Telegram n'est pas relié à un utilisateur SystemDD.",
				'buttons' => array(
					array(
						array('text' => 'Fermer', 'callback_data' => 'btn_classify_cancel'),
					),
				),
			);
		}

		$organizations = new \dbObject\ArrayOrganization();
		$organizations->loadAccessibleForUser((int)$user->getId());

		if (count($organizations) === 0) {
			return array(
				'text' => "Aucune organisation accessible n'est disponible pour classer ce document.",
				'buttons' => array(
					array(
						array('text' => 'Fermer', 'callback_data' => 'btn_classify_cancel'),
					),
				),
			);
		}

		if ($selectedOrganizationId <= 0) {
			$availableOrganizations = array();
			$buttons = array();

			foreach ($organizations as $organization) {
				$organizationRootHolon = $organization->getStructuralRootHolon();
				if (
					!($organizationRootHolon instanceof \dbObject\Holon)
					|| (int)$organizationRootHolon->getId() <= 0
					|| !holonHasDocumentCreationDestination($user, $organization, $organizationRootHolon)
				) {
					continue;
				}

				$availableOrganizations[] = $organization;
			}

			if (count($availableOrganizations) === 0) {
				return array(
					'text' => "Aucune organisation accessible avec droit de creation de document n'est disponible pour classer ce memo.",
					'buttons' => array(
						array(
							array('text' => 'Fermer', 'callback_data' => 'btn_classify_cancel'),
						),
					),
				);
			}

			foreach ($availableOrganizations as $organization) {
				$buttons[] = array(
					array(
						'text' => trim((string)$organization->get('name')),
						'callback_data' => 'btn_classify_org_'.$organization->getId(),
					),
				);
			}

			$buttons[] = array(
				array('text' => 'Annuler', 'callback_data' => 'btn_classify_cancel'),
			);

			return array(
				'text' => "Classer ce mémo\n\nChoisissez d'abord une organisation.",
				'buttons' => $buttons,
			);
		}

		$organization = new \dbObject\Organization();
		if (!$organization->load($selectedOrganizationId)) {
			return buildClassificationPrompt($user, 0, 0);
		}

		$rootHolon = $organization->getStructuralRootHolon();
		if (!$rootHolon) {
			return array(
				'text' => "Impossible de trouver la structure de cette organisation.",
				'buttons' => array(
					array(
						array('text' => "Changer d'organisation", 'callback_data' => 'btn_classify_root'),
					),
					array(
						array('text' => 'Annuler', 'callback_data' => 'btn_classify_cancel'),
					),
				),
			);
		}

		$selectedHolon = null;
		if ($selectedHolonId > 0) {
			$selectedHolon = new \dbObject\Holon();
			if (
				!$selectedHolon->load($selectedHolonId)
				|| !(bool)$selectedHolon->get('active')
				|| !(bool)$selectedHolon->get('visible')
				|| !$organization->containsHolon($selectedHolon)
			) {
				$selectedHolon = null;
				$selectedHolonId = 0;
			}
		}

		$currentPath = buildHolonPathLabel($organization, $selectedHolon);
		$currentNode = $selectedHolon ?: $rootHolon;
		$availabilityCache = array();
		$canFinishHere = telegramUserCanCreateDocumentInHolon($user, $organization, $currentNode);
		$children = getCreatableVisibleHolonChildren($user, $organization, $currentNode, $availabilityCache);
		$descendantOptions = collectHolonDescendantNavigationOptions($user, $organization, $currentNode, '', $availabilityCache);
		$useIncrementalMode = count($descendantOptions) > 4;
		$hasDescendantDestination = count($descendantOptions) > 0;

		$buttons = array();
		if ($canFinishHere) {
			$buttons[] = array(
				array(
					'text' => 'Terminer ici',
					'callback_data' => 'btn_classify_done_'.$organization->getId().'_'.($selectedHolon ? $selectedHolon->getId() : 0),
					'style' => 'success',
				),
			);
		}

		if ($selectedHolon) {
			$parentHolon = $selectedHolon->getParentHolon();
			$backTargetHolonId = 0;
			if ($parentHolon && (int)$parentHolon->get('IDtypeholon') !== 4) {
				$backTargetHolonId = (int)$parentHolon->getId();
			}

			$buttons[] = array(
				array(
					'text' => 'Retour',
					'callback_data' => 'btn_classify_nav_'.$organization->getId().'_'.$backTargetHolonId,
				),
				array(
					'text' => "Changer d'organisation",
					'callback_data' => 'btn_classify_root',
				),
			);
		} else {
			$buttons[] = array(
				array(
					'text' => "Changer d'organisation",
					'callback_data' => 'btn_classify_root',
				),
			);
		}

		if ($useIncrementalMode) {
			foreach ($children as $child) {
				$buttons[] = array(
					array(
						'text' => buildHolonChoiceLabel($child),
						'callback_data' => 'btn_classify_nav_'.$organization->getId().'_'.$child->getId(),
						'style' => 'primary',
					),
				);
			}
		} else {
			foreach ($descendantOptions as $option) {
				$callbackAction = ($option['action'] ?? '') === 'nav' ? 'btn_classify_nav_' : 'btn_classify_done_';
				$buttons[] = array(
					array(
						'text' => $option['label'],
						'callback_data' => $callbackAction.$organization->getId().'_'.$option['holon']->getId(),
						'style' => 'primary',
					),
				);
			}
		}

		$buttons[] = array(
			array('text' => 'Annuler', 'callback_data' => 'btn_classify_cancel'),
		);

		$text = "Classer ce mémo\n\nEmplacement sélectionné : ".$currentPath;
		if (!$canFinishHere && !$hasDescendantDestination) {
			$text .= "\n\nAucune destination avec droit de creation de document n'est disponible ici.";
		} elseif ($hasDescendantDestination && !$useIncrementalMode) {
			$text .= $canFinishHere
				? "\n\nChoisissez directement un emplacement ou un sous-niveau ci-dessous, ou utilisez \"Terminer ici\" pour valider cet emplacement."
				: "\n\nChoisissez directement un sous-niveau ou une destination autorisee ci-dessous.";
		} elseif (count($children) > 0) {
			$text .= $canFinishHere
				? "\n\nChoisissez un sous-niveau, ou utilisez \"Terminer ici\" pour valider cet emplacement."
				: "\n\nChoisissez un sous-niveau autorise ci-dessous.";
		} else {
			$text .= $canFinishHere
				? "\n\nAucun sous-niveau supplémentaire n'est disponible ici. Vous pouvez terminer maintenant."
				: "\n\nAucun sous-niveau supplémentaire autorise n'est disponible ici.";
		}

		return array(
			'text' => $text,
			'buttons' => $buttons,
		);
	}

	function extractJsonObjectFromText(string $text): ?string {
		$text = trim($text);
		if ($text === '') {
			return null;
		}

		$text = preg_replace('/^```json\s*/i', '', $text);
		$text = preg_replace('/^```\s*/', '', $text);
		$text = preg_replace('/\s*```$/', '', $text);

		$start = strpos($text, '{');
		$end = strrpos($text, '}');
		if ($start === false || $end === false || $end <= $start) {
			return null;
		}

		return substr($text, $start, $end - $start + 1);
	}

	function normalizeHashtagList($value): array {
		if (is_array($value)) {
			return $value;
		}

		if (is_string($value) && trim($value) !== '') {
			return preg_split('/[,;\n]+/u', $value);
		}

		return array();
	}

	function handleCallbackQuery(array $callbackQuery, \dbObject\User $user): void {
		$callbackId = $callbackQuery['id'] ?? '';
		$callbackData = $callbackQuery['data'] ?? '';
		$message = $callbackQuery['message'] ?? array();
		$chatId = $message['chat']['id'] ?? null;
		$threadId = getMessageThreadId($message);
		$actorId = isset($callbackQuery['from']['id']) ? (int)$callbackQuery['from']['id'] : 0;
		$sessionData = loadLocalSession($actorId);
		$document = loadLastDocumentForActor($actorId);

		if ($chatId === null || $callbackId === '' || $callbackData === '') {
			return;
		}

		if (strpos($callbackData, 'tg_dest_') === 0) {
			if ((int)$user->getId() <= 0) {
				answerCallbackQuery($callbackId, "Connectez d'abord votre compte Telegram en prive.");
				return;
			}

			if ((int)$chatId === $actorId) {
				answerCallbackQuery($callbackId, 'Cette configuration doit etre faite dans un groupe.');
				return;
			}

			if ($callbackData === 'tg_dest_cancel') {
				deleteMessage($chatId, (int)$message['message_id'], $threadId);
				answerCallbackQuery($callbackId, 'Configuration annulee.');
				return;
			}

			if ($callbackData === 'tg_dest_root') {
				$prompt = buildTelegramGroupRolePrompt($user, 0, 0);
				editMessageText($chatId, (int)$message['message_id'], $prompt['text'], $prompt['buttons'], $threadId);
				answerCallbackQuery($callbackId);
				return;
			}

			if (preg_match('/^tg_dest_org_(\d+)$/', $callbackData, $matches)) {
				$prompt = buildTelegramGroupRolePrompt($user, (int)$matches[1], 0);
				editMessageText($chatId, (int)$message['message_id'], $prompt['text'], $prompt['buttons'], $threadId);
				answerCallbackQuery($callbackId);
				return;
			}

			if (preg_match('/^tg_dest_holon_(\d+)_(\d+)$/', $callbackData, $matches)) {
				$prompt = buildTelegramGroupRolePrompt($user, (int)$matches[1], (int)$matches[2]);
				editMessageText($chatId, (int)$message['message_id'], $prompt['text'], $prompt['buttons'], $threadId);
				answerCallbackQuery($callbackId);
				return;
			}

			if (preg_match('/^tg_dest_projects_(\d+)_(\d+)_(\d+)$/', $callbackData, $matches)) {
				try {
					$prompt = buildTelegramGroupProjectPrompt($user, (int)$matches[1], (int)$matches[2], (int)$matches[3]);
					$updated = editMessageText($chatId, (int)$message['message_id'], $prompt['text'], $prompt['buttons'], $threadId);
					if (!$updated) {
						sendMessage($chatId, $prompt['text'], $prompt['buttons'], $threadId);
					}
					answerCallbackQuery($callbackId);
				} catch (\Throwable $exception) {
					error_log('Telegram project destination navigation failed: '.$exception->getMessage());
					sendMessage($chatId, "Impossible d'afficher les projets pour le moment. Le detail a ete enregistre dans le journal PHP.", null, $threadId);
					answerCallbackQuery($callbackId, 'Erreur lors du chargement des projets.');
				}
				return;
			}

			if (preg_match('/^tg_dest_role_(\d+)_(\d+)$/', $callbackData, $matches)) {
				if (!\dbObject\TelegramChatDestination::isStorageAvailable()) {
					editMessageText($chatId, (int)$message['message_id'], "La destination Telegram ne peut pas encore etre enregistree: la migration SQL telegram-chat-destinations doit etre executee sur ce serveur.", null, $threadId);
					answerCallbackQuery($callbackId, 'Migration SQL manquante.');
					return;
				}

				$organizationId = (int)$matches[1];
				$role = new \dbObject\Holon();
				$organization = new \dbObject\Organization();
				if (
					!$organization->load($organizationId)
					|| !telegramUserCanUseOrganization($user, $organizationId)
					|| !$role->load((int)$matches[2])
					|| !$organization->containsHolon($role)
					|| !telegramRoleCanReceiveGroupMemos($user, $organization, $role)
				) {
					answerCallbackQuery($callbackId, "Cette destination n'est plus autorisee.");
					return;
				}

				$destinationSave = \dbObject\TelegramChatDestination::saveForTelegramChat(
					$chatId,
					$threadId,
					$organizationId,
					\dbObject\TelegramChatDestination::TYPE_ROLE,
					(int)$role->getId(),
					(int)$user->getId()
				);
				if (empty($destinationSave['status'])) {
					error_log('Telegram role destination save failed: '.json_encode(array(
						'chatId' => (string)$chatId,
						'threadId' => $threadId,
						'organizationId' => $organizationId,
						'roleId' => (int)$role->getId(),
						'error' => $destinationSave['dbError'] ?? $destinationSave['message'] ?? '',
					), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
					editMessageText($chatId, (int)$message['message_id'], "Impossible d'enregistrer la destination. Le detail est dans le journal PHP et dans tmp/dbobject-sql-errors.log.", null, $threadId);
					answerCallbackQuery($callbackId, "Impossible d'enregistrer la destination.");
					return;
				}

				editMessageText($chatId, (int)$message['message_id'], "Ce groupe est connecte au role : ".buildHolonPathLabel($organization, $role).".\nLes prochains vocaux seront ajoutes a ce contexte.", null, $threadId);
				answerCallbackQuery($callbackId, 'Role connecte.');
				return;
			}

			if (preg_match('/^tg_dest_project_(\d+)_(\d+)_(\d+)$/', $callbackData, $matches)) {
				if (!\dbObject\TelegramChatDestination::isStorageAvailable()) {
					editMessageText($chatId, (int)$message['message_id'], "La destination Telegram ne peut pas encore etre enregistree: la migration SQL telegram-chat-destinations doit etre executee sur ce serveur.", null, $threadId);
					answerCallbackQuery($callbackId, 'Migration SQL manquante.');
					return;
				}

				$organizationId = (int)$matches[1];
				$organization = new \dbObject\Organization();
				$role = new \dbObject\Holon();
				$project = new \dbObject\Project();
				if (
					!$organization->load($organizationId)
					|| !telegramUserCanUseOrganization($user, $organizationId)
					|| !$role->load((int)$matches[2])
					|| (int)$role->get('IDtypeholon') !== 1
					|| !$organization->containsHolon($role)
					|| !$project->load((int)$matches[3])
					|| (int)$project->get('IDorganization') !== $organizationId
					|| !telegramProjectCanReceiveGroupMemos($user, $project, $organization, $role)
				) {
					answerCallbackQuery($callbackId, "Cette destination n'est plus autorisee.");
					return;
				}

				$destinationSave = \dbObject\TelegramChatDestination::saveForTelegramChat(
					$chatId,
					$threadId,
					$organizationId,
					\dbObject\TelegramChatDestination::TYPE_PROJECT,
					(int)$project->getId(),
					(int)$user->getId(),
					(int)$role->getId()
				);
				if (empty($destinationSave['status'])) {
					error_log('Telegram project destination save failed: '.json_encode(array(
						'chatId' => (string)$chatId,
						'threadId' => $threadId,
						'organizationId' => $organizationId,
						'projectId' => (int)$project->getId(),
						'error' => $destinationSave['dbError'] ?? $destinationSave['message'] ?? '',
					), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
					editMessageText($chatId, (int)$message['message_id'], "Impossible d'enregistrer la destination. Le detail est dans le journal PHP et dans tmp/dbobject-sql-errors.log.", null, $threadId);
					answerCallbackQuery($callbackId, "Impossible d'enregistrer la destination.");
					return;
				}

				editMessageText($chatId, (int)$message['message_id'], "Ce groupe est connecte au projet : ".trim((string)$project->get('title')).".\nLes prochains vocaux seront lies a ce projet.", null, $threadId);
				answerCallbackQuery($callbackId, 'Projet connecte.');
				return;
			}
		}

		if ($callbackData === 'btn_options') {
			answerCallbackQuery($callbackId, "Choisissez une action.");
			return;
		}

		if ($callbackData === 'btn_share') {
			if ($document && $document->getId() > 0) {
				if ($document->get("codeview") == null) {
					$document->set("codeview", bin2hex(random_bytes(10)));
					$document->save();
				}

				sendMessage($chatId, formatDocumentLink($document), null, $threadId);
			} else {
				sendMessage($chatId, "Le fichier n'a pas été trouvé.", null, $threadId);
			}

			answerCallbackQuery($callbackId);
			return;
		}

		if ($callbackData === 'btn_delete') {
			sendMessage($chatId, "Que dois-je effacer ?", buildDeleteButtons(), $threadId);
			answerCallbackQuery($callbackId);
			return;
		}

		if ($callbackData === 'btn_del_cancel') {
			editMessageText($chatId, (int)$message['message_id'], "Suppression annulée.", null, $threadId);
			answerCallbackQuery($callbackId);
			return;
		}

		if ($callbackData === 'btn_del_resume') {
			if (isset($sessionData->lastID) && (int)$sessionData->lastID > 0) {
				deleteMessage($chatId, (int)$sessionData->lastID, $threadId);
				clearLastMessageSessionFields($sessionData);
				saveLocalSession($sessionData, $actorId);
			}

			if (isset($message['message_id'])) {
				deleteMessage($chatId, (int)$message['message_id'], $threadId);
			}

			answerCallbackQuery($callbackId, "Résumé effacé.");
			return;
		}

		if ($callbackData === 'btn_del_file' || $callbackData === 'btn_del_all') {
			if ($document && $document->getId() > 0) {
				deleteDocumentBundle($document);
				clearLastDocumentSessionFields($sessionData);
			}

			if (isset($sessionData->lastID) && (int)$sessionData->lastID > 0) {
				if ($callbackData === 'btn_del_all') {
					deleteMessage($chatId, (int)$sessionData->lastID, $threadId);
					clearLastMessageSessionFields($sessionData);
				} else {
					editMessageText($chatId, (int)$sessionData->lastID, "Le document lié a été supprimé.", null, $threadId);
				}
			}

			saveLocalSession($sessionData, $actorId);

			if (isset($message['message_id'])) {
				deleteMessage($chatId, (int)$message['message_id'], $threadId);
			}

			answerCallbackQuery($callbackId, $callbackData === 'btn_del_all' ? "Tout a été supprimé." : "Fichier supprimé.");
			return;
		}

		if ($callbackData === 'btn_classify') {
			$prompt = buildClassificationPrompt($user, 0, 0);
			sendMessage($chatId, $prompt['text'], $prompt['buttons'], $threadId);
			answerCallbackQuery($callbackId);
			return;
		}

		if ($callbackData === 'btn_classify_root') {
			$prompt = buildClassificationPrompt($user, 0, 0);
			editMessageText($chatId, (int)$message['message_id'], $prompt['text'], $prompt['buttons'], $threadId);
			answerCallbackQuery($callbackId);
			return;
		}

		if ($callbackData === 'btn_classify_cancel') {
			deleteMessage($chatId, (int)$message['message_id']);
			answerCallbackQuery($callbackId);
			return;
		}

		if (preg_match('/^btn_classify_org_(\d+)$/', $callbackData, $matches)) {
			$organizationId = (int)$matches[1];
			$prompt = buildClassificationPrompt($user, $organizationId, 0);
			editMessageText($chatId, (int)$message['message_id'], $prompt['text'], $prompt['buttons'], $threadId);
			answerCallbackQuery($callbackId);
			return;
		}

		if (preg_match('/^btn_classify_nav_(\d+)_(\d+)$/', $callbackData, $matches)) {
			$organizationId = (int)$matches[1];
			$holonId = (int)$matches[2];
			$prompt = buildClassificationPrompt($user, $organizationId, $holonId);
			editMessageText($chatId, (int)$message['message_id'], $prompt['text'], $prompt['buttons'], $threadId);
			answerCallbackQuery($callbackId);
			return;
		}

		if (preg_match('/^btn_classify_done_(\d+)_(\d+)$/', $callbackData, $matches)) {
			if (!$document || $document->getId() <= 0) {
				editMessageText($chatId, (int)$message['message_id'], "Le document n'a pas été trouvé.", null, $threadId);
				answerCallbackQuery($callbackId);
				return;
			}

			$organizationId = (int)$matches[1];
			$holonId = (int)$matches[2];
			if (!\dbObject\Document::canCreateInOrganizationContext($organizationId, $holonId > 0 ? $holonId : null, (int)$user->getId(), 0, false)) {
				editMessageText(
					$chatId,
					(int)$message['message_id'],
					"Vous n'avez pas le droit de classer ce document a cet emplacement.",
					array(
						array(
							array('text' => 'Reclasser', 'callback_data' => 'btn_classify'),
						),
					),
					$threadId
				);
				answerCallbackQuery($callbackId, "Action non autorisee.");
				return;
			}

			$result = $document->assignOrganizationContext($organizationId, $holonId > 0 ? $holonId : null);

			if (!empty($result['status'])) {
				$document->load($document->getId(), true);
				editMessageText(
					$chatId,
					(int)$message['message_id'],
					"Le document a été classé dans : ".$document->getOrganizationContextLabel(),
					array(
						array(
							array('text' => 'Reclasser', 'callback_data' => 'btn_classify'),
						),
					),
					$threadId
				);
				answerCallbackQuery($callbackId, "Classement enregistré.");
			} else {
				editMessageText(
					$chatId,
					(int)$message['message_id'],
					"Impossible de classer ce document : ".($result['text'] ?? 'erreur inconnue'),
					array(
						array(
							array('text' => 'Réessayer', 'callback_data' => 'btn_classify'),
						),
					),
					$threadId
				);
				answerCallbackQuery($callbackId);
			}
		}
	}

	function handlePhotoMessage(array $message): void {
		$actorId = isset($message['from']['id']) ? (int)$message['from']['id'] : 0;
		if ($actorId <= 0 || !isset($message['photo']) || !is_array($message['photo']) || count($message['photo']) === 0) {
			return;
		}

		$data = loadLocalSession($actorId);
		if (!isset($data->lastDoc) || (int)$data->lastDoc <= 0) {
			return;
		}

		$photo = end($message['photo']);
		$fileId = $photo['file_id'] ?? '';
		if ($fileId === '') {
			return;
		}

		$media = new \dbObject\Media();
		$media->set("title", $message['caption'] ?? null);
		$media->set("filename", "telegram-photo");
		$media->set("contenttype", "image/jpeg");
		$media->set("IDdocument", (int)$data->lastDoc);
		$media->set("IDtype", 2); // Image
		$media->set("IDstorage", 1); // Telegram
		$media->set("accesskey", $fileId);
		$media->save();
	}

	function handleVoiceMessage(array $message, \dbObject\User $user, int $minTimeMessage): void {
		$actorId = isset($message['from']['id']) ? (int)$message['from']['id'] : 0;
		$chatId = $message['chat']['id'] ?? null;
		$threadId = getMessageThreadId($message);
		$isPrivateChat = isTelegramPrivateChat($message);
		$groupDestinationContext = null;

		if ($actorId <= 0 || $chatId === null || !isset($message['voice'])) {
			return;
		}

		if ((int)$user->getId() <= 0) {
			sendMessage($chatId, "Votre compte Telegram doit etre connecte avec /connect en message prive avant d'envoyer un memo vocal.", null, $threadId);
			return;
		}

		if (!$isPrivateChat) {
			$groupDestination = \dbObject\TelegramChatDestination::findByTelegramChat($chatId, $threadId);
			$groupDestinationContext = $groupDestination instanceof \dbObject\TelegramChatDestination
				? $groupDestination->getDocumentContext()
				: null;
			if (!is_array($groupDestinationContext)) {
				sendMessage($chatId, "Ce groupe n'est pas encore connecte a un role ou un projet. Une personne autorisee peut envoyer /connect.", null, $threadId);
				return;
			}

			if (!\dbObject\Document::canCreateInOrganizationContext(
				(int)$groupDestinationContext['organizationId'],
				(int)$groupDestinationContext['holonId'] > 0 ? (int)$groupDestinationContext['holonId'] : null,
				(int)$user->getId(),
				0,
				false
			)) {
				sendMessage($chatId, "Vous n'avez plus le droit d'envoyer des memos vers cette destination.", null, $threadId);
				return;
			}
		}

		if (!patreonUserCanUseAi((int)$user->getId())) {
			sendMessage(
				$chatId,
				"Les fonctions IA sont reservees aux contributeurs Patreon payants. Connectez votre compte avec /connect puis soutenez le projet sur Patreon pour utiliser la transcription audio.",
				null,
				$threadId
			);
			return;
		}

		$data = loadLocalSession($actorId);
		if (isset($data->active) && !$data->active) {
			sendMessage($chatId, "La transcription est desactivee pour votre compte. Envoyez /start pour la reactiver.", null, $threadId);
			return;
		}

		$voice = $message['voice'];
		$fileId = $voice['file_id'] ?? '';
		$duration = isset($voice['duration']) ? (int)$voice['duration'] : 0;

		if ($fileId === '' || $duration < $minTimeMessage) {
			if ($fileId !== '' && $duration < $minTimeMessage) {
				sendMessage($chatId, "Le memo vocal est trop court. Envoyez au moins ".$minTimeMessage." secondes pour lancer la transcription.", null, $threadId);
			}
			return;
		}

		$waitMessageId = sendMessage($chatId, "Un petit moment, je retranscris tout ça...", null, $threadId);

		set_time_limit(240);
		ignore_user_abort(true);
		header('Connection: close');
		flush();
		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		}

		$fileInfo = getTelegramFile($fileId);
		$filePath = is_array($fileInfo) ? ($fileInfo['result']['file_path'] ?? null) : null;
		if (!$filePath) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, je n'ai pas réussi à récupérer le fichier audio.", null, $threadId);
			return;
		}

		$download = telegramDownloadFile($filePath);

		if (!$download['ok']) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, le téléchargement du fichier audio a échoué.", null, $threadId);
			return;
		}

		$audioContent = $download['content'];
		$tempFilePath = tempnam(sys_get_temp_dir(), 'audio');
		$audioBytesWritten = $tempFilePath !== false ? file_put_contents($tempFilePath, $audioContent) : false;
		if ($tempFilePath === false || $audioBytesWritten === false) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, je n'ai pas réussi à préparer le fichier audio.", null, $threadId);
			return;
		}

		$headers = array(
			'Authorization: Bearer ' . OpenAI,
		);

		$cfile = new CURLFile($tempFilePath);
		$cfile->setMimeType("audio/ogg");
		$cfile->setPostFilename("audio.ogg");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'file' => $cfile,
			'model' => 'whisper-1',
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$responseRaw = curl_exec($ch);
		$curlError = curl_errno($ch) ? curl_error($ch) : null;
		@unlink($tempFilePath);

		if ($curlError || !$responseRaw) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, la transcription audio a échoué.", null, $threadId);
			return;
		}

		$response = json_decode($responseRaw);
		if (!is_object($response) || !isset($response->text) || trim((string)$response->text) === '') {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, la transcription reçue est vide ou invalide.", null, $threadId);
			return;
		}

		$metadataPrompt = "Return exactly three lines for the following text. TITLE: a concise document title. SUMMARY: a French summary of at most 150 characters. KEYWORDS: three to five French keywords separated only by commas. Do not use markdown or add any other text.\n".$response->text;
		$metadataSystemInstruction = "You generate document metadata. Always return the requested TITLE, SUMMARY, and KEYWORDS lines exactly, even when the source text is in French.";
		$metadata = (string)say($metadataPrompt, $metadataSystemInstruction);
		$title = "Mémo vocal";
		$resume = '';
		$keywords = '';
		if (preg_match('/^(?:TITLE|TITRE)\s*:\s*(.+)$/miu', $metadata, $titleMatch)) {
			$title = trim($titleMatch[1], " \t\n\r\0\x0B*\"");
		}
		if ($title === '') {
			$title = "Mémo vocal";
		}
		if (preg_match('/^(?:SUMMARY|RESUME|RÉSUMÉ)\s*:\s*(.+)$/miu', $metadata, $resumeMatch)) {
			$resume = trim($resumeMatch[1], " \t\n\r\0\x0B*\"");
		}
		if (preg_match('/^(?:KEYWORDS|MOTS[ _-]*CLES|MOTS[ _-]*CLÉS)\s*:\s*(.+)$/miu', $metadata, $keywordsMatch)) {
			$keywordItems = array_filter(array_map(function ($keyword) {
				return trim(ltrim((string)$keyword, '#'));
			}, explode(',', $keywordsMatch[1])));
			$keywords = implode(', ', array_unique($keywordItems));
		}

		$content = (string)$response->text;

		$doc = null;
		if ($user->getId() > 0) {
			$user->refreshDbh();

			try {
				$doc = new \dbObject\Document();
				$doc->set("title", $title);
				$doc->set("description", $resume);
				$doc->set("content", $content);
				$doc->set("keywords", $keywords);
				$doc->set("IDuser", $user->getId());

				if (is_array($groupDestinationContext)) {
					$doc->set("IDorganization", (int)$groupDestinationContext['organizationId']);
					$doc->set("IDholon", (int)$groupDestinationContext['holonId'] > 0 ? (int)$groupDestinationContext['holonId'] : null);
				}

				if (!$isPrivateChat) {
					$doc->set("codeview", bin2hex(random_bytes(10)));
				}

				$saveResult = $doc->save();
				if (!is_array($saveResult) || empty($saveResult['status']) || (int)$doc->getId() <= 0) {
					throw new \RuntimeException('telegram_document_save_failed');
				}

				if (
					is_array($groupDestinationContext)
					&& ($groupDestinationContext['type'] ?? '') === \dbObject\TelegramChatDestination::TYPE_PROJECT
					&& ($groupDestinationContext['project'] ?? null) instanceof \dbObject\Project
				) {
					$projectDocument = new \dbObject\ProjectDocument();
					$projectDocument->set('IDproject', (int)$groupDestinationContext['project']->getId());
					$projectDocument->set('IDdocument', (int)$doc->getId());
					$projectDocumentResult = $projectDocument->save();
					if (!is_array($projectDocumentResult) || empty($projectDocumentResult['status'])) {
						throw new \RuntimeException('telegram_project_document_save_failed');
					}
				}

				$data->lastDoc = $doc->getId();
				saveLocalSession($data, $actorId);

				$media = new \dbObject\Media();
				$media->set("title", $title);
				$media->set("filename", "download.oga");
				$media->set("contenttype", "audio/ogg");
				$media->set("description", $resume);
				$media->set("IDdocument", $doc->getId());
				$media->set("IDtype", 1); // Audio
				$media->set("IDstorage", 1); // Telegram
				$media->set("accesskey", $fileId);
				$media->save();
			} catch (\Exception $e) {
				$doc = null;
				sendMessage($chatId, "Désolé, problème de génération du fichier...", null, $threadId);
			}
		}

		$buttons = null;
		if ($doc && $doc->getId() > 0 && ($message['chat']['id'] ?? null) == ($message['from']['id'] ?? null)) {
			$buttons = buildMemoActionButtons();
		}

		if ($waitMessageId) {
			deleteMessage($chatId, $waitMessageId, $threadId);
		}

		$messageText = "\xE2\xAC\x86 ".($resume !== '' ? $resume : "Mémo vocal enregistré.");
		if ($doc && $doc->getId() > 0) {
			$messageText .= "\n".formatDocumentLink($doc);
		}

		$messageId = sendMessage($chatId, $messageText, $buttons, $threadId);
		if ($messageId !== null) {
			$data->lastID = $messageId;
		}
		saveLocalSession($data, $actorId);
	}

	function handleTextMessage(array $message, \dbObject\User $user): void {
		$text = isset($message['text']) ? trim((string)$message['text']) : '';
		if ($text === '') {
			return;
		}

		$actorId = isset($message['from']['id']) ? (int)$message['from']['id'] : 0;
		$chatId = $message['chat']['id'] ?? null;
		$threadId = getMessageThreadId($message);
		if ($actorId <= 0 || $chatId === null) {
			return;
		}

		$command = strtolower((string)preg_replace('/@[^\s]+$/', '', strtok($text, " \t\r\n")));
		if ($command === '/help') {
			$help = "Commandes disponibles:\n".
				"/help - Afficher cette aide\n".
				"/whois - Afficher le nom du serveur\n".
				"/connect - Connecter votre compte en prive, ou ce groupe a un role ou projet\n".
				"/cancel - Supprimer la connexion Telegram ou la destination du groupe\n".
				"/time - Afficher l'heure du serveur\n".
				"/start - Afficher le statut de connexion\n".
				"/stop - Desactiver le traitement des messages\n".
				"/delete - Supprimer le dernier message du bot";
			sendMessage($chatId, $help, null, $threadId);
			return;
		}

		if ($command === '/whois') {
			$serverName = function_exists('gethostname') ? gethostname() : false;
			if (!$serverName) {
				$serverName = $_SERVER['SERVER_NAME'] ?? 'unknown';
			}
			sendMessage($chatId, "Serveur: ".(string)$serverName, null, $threadId);
			return;
		}

		if ($command === '/cancel') {
			if (!isTelegramPrivateChat($message)) {
				$destination = \dbObject\TelegramChatDestination::findByTelegramChat($chatId, $threadId);
				$context = $destination instanceof \dbObject\TelegramChatDestination ? $destination->getDocumentContext() : null;
				$canDisconnect = is_array($context)
					&& \dbObject\Document::canCreateInOrganizationContext(
						(int)$context['organizationId'],
						(int)$context['holonId'] > 0 ? (int)$context['holonId'] : null,
						(int)$user->getId(),
						0,
						false
					);
				if (!$destination || !$canDisconnect) {
					sendMessage($chatId, "Ce groupe n'a pas de destination que vous pouvez supprimer.", null, $threadId);
					return;
				}

				if ($destination->deactivate()) {
					sendMessage($chatId, "La destination Telegram de ce groupe a ete supprimee.", null, $threadId);
				} else {
					sendMessage($chatId, "Impossible de supprimer la destination Telegram de ce groupe.", null, $threadId);
				}
				return;
			}

			$sessionData = loadLocalSession($actorId);
			clearTelegramConnectState($sessionData);
			saveLocalSession($sessionData, $actorId);

			if ($user->getId() <= 0) {
				sendMessage($chatId, "Ce compte Telegram n'est relie a aucun utilisateur. Envoyez /connect pour vous connecter.", null, $threadId);
				return;
			}

			$connectedUserLabel = getTelegramConnectedUserLabel($user);
			if (disconnectTelegramUser($user)) {
				sendMessage($chatId, "La connexion Telegram avec ".$connectedUserLabel." a ete supprimee. Envoyez /connect pour connecter un compte.", null, $threadId);
			} else {
				sendMessage($chatId, "Impossible de supprimer la connexion Telegram pour le moment. Reessayez plus tard.", null, $threadId);
			}
			return;
		}

		if (handleTelegramConnectConversation($message, $user)) {
			return;
		}

		if ($command === '/connect') {
			if (isTelegramPrivateChat($message)) {
				beginTelegramConnectFlow($actorId);
				$messageText = "Envoyez l'adresse e-mail de votre compte pour connecter Telegram.";
				if ($user->getId() > 0) {
					$messageText .= "\nCompte actuellement lie: ".getTelegramConnectedUserLabel($user).".";
					$messageText .= "\nEnvoyez /cancel pour annuler et supprimer cette connexion.";
				} else {
					$messageText .= "\nVous pouvez envoyer /cancel pour annuler.";
				}
				sendMessage($chatId, $messageText, null, $threadId);
			} else {
				if ((int)$user->getId() <= 0) {
					sendMessage($chatId, "Votre compte Telegram doit d'abord etre connecte en message prive. Ouvrez une discussion privee avec le bot et envoyez /connect.", null, $threadId);
					return;
				}

				$prompt = buildTelegramGroupRolePrompt($user, 0, 0);
				sendMessage($chatId, $prompt['text'], $prompt['buttons'], $threadId);
			}
			return;
		}


		if (preg_match('/^\/time/', $text)) {
			$current = new DateTime();
			sendMessage($chatId, "It's ".$current->format("H:i"), null, $threadId);
			return;
		}

		if (preg_match('/^\/delete/', $text)) {
			$data = loadLocalSession($actorId);
			if (isset($data->lastID)) {
				deleteMessage($chatId, (int)$data->lastID, $threadId);
				deleteMessage($chatId, (int)$message['message_id'], $threadId);
			}
			saveLocalSession($data, $actorId);
			return;
		}

		if (preg_match('/^\/stop/', $text)) {
			$data = loadLocalSession($actorId);
			$data->active = false;
			saveLocalSession($data, $actorId);
			sendMessage($chatId, "J'arrête les traductions pour ".$actorId, null, $threadId);
			return;
		}

		if ($command === '/start') {
			$data = loadLocalSession($actorId);
			$data->active = true;
			saveLocalSession($data, $actorId);

			if (!isTelegramPrivateChat($message)) {
				if ($user->getId() <= 0) {
					sendMessage($chatId, "Bienvenue dans EasyMEMO. Connectez d'abord votre compte Telegram en message prive avec /connect.", null, $threadId);
					return;
				}

				$destination = \dbObject\TelegramChatDestination::findByTelegramChat($chatId, $threadId);
				if ($destination instanceof \dbObject\TelegramChatDestination && is_array($destination->getDocumentContext())) {
					sendMessage($chatId, "Bienvenue dans EasyMEMO. Ce groupe est connecte a ".telegramGroupDestinationLabel($destination).".", null, $threadId);
					return;
				}

				sendMessage($chatId, "Bienvenue dans EasyMEMO. Votre compte Telegram est connecte a ".getTelegramConnectedUserLabel($user).". Envoyez /connect pour relier ce groupe a un role ou un projet.", null, $threadId);
				return;
			}

			if ($user->getId() > 0) {
				sendMessage(
					$chatId,
					"Bienvenue dans EasyMEMO.\n\nCe compte Telegram est connecte a : ".getTelegramConnectedUserLabel($user).".\nSi ce n'est pas vous, envoyez /cancel pour supprimer cette connexion.",
					null,
					$threadId
				);
				return;
			}

			sendMessage($chatId, "Bienvenue dans EasyMEMO.\n\nCe compte Telegram n'est pas encore connecte. Envoyez /connect pour relier votre compte.", null, $threadId);
			return;
		}

		if (preg_match('/^\//', $text)) {
			sendMessage($chatId, "Commande inconnue.", null, $threadId);
			return;
		}

		if (preg_match('/^@pottylicensebot/', $text)) {
			sendMessage($chatId, "Je ne réponds pas aux messages directs, utilisez les commandes.", null, $threadId);
		}
	}

	$content = file_get_contents('php://input');
	$update = json_decode($content, true);
	if (!is_array($update)) {
		exit;
	}

	$actorId = getTelegramActorId($update);
	$user = loadTelegramUserByActorId($actorId);

	if (isset($update['callback_query']) && is_array($update['callback_query'])) {
		handleCallbackQuery($update['callback_query'], $user);
		exit;
	}

	$message = isset($update['message']) && is_array($update['message']) ? $update['message'] : null;
	if (!$message) {
		exit;
	}

	if (isset($message['photo'])) {
		handlePhotoMessage($message);
	}

	if (isset($message['voice'])) {
		handleVoiceMessage($message, $user, $minTimeMessage);
	}

	handleTextMessage($message, $user);
?>
