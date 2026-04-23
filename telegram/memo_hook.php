<?php
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/openai.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/telegram.php");

	$minTimeMessage = 10; // Durée minimum en seconde du message pour justifier une transformation

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

	function formatDocumentLink(\dbObject\Document $document): string {
		return appBuildAbsoluteUrl("/memo/".$document->getId().($document->get("codeview") ? "/".$document->get("codeview") : ""));
	}

	function buildMemoActionButtons(): array {
		return array(
			array(
				array('text' => 'Options', 'callback_data' => 'btn_options'),
				array('text' => 'Delete', 'callback_data' => 'btn_delete'),
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
				array('text' => 'Le résumé', 'callback_data' => 'btn_del_resume'),
				array('text' => 'Le fichier', 'callback_data' => 'btn_del_file'),
			),
			array(
				array('text' => 'Tout', 'callback_data' => 'btn_del_all'),
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

	function collectHolonDescendantOptions(\dbObject\Holon $holon, string $prefix = ''): array {
		$options = array();

		foreach (getVisibleHolonChildren($holon) as $child) {
			$label = $prefix !== ''
				? $prefix." > ".buildHolonChoiceLabel($child)
				: buildHolonChoiceLabel($child);

			$options[] = array(
				'holon' => $child,
				'label' => $label,
			);

			$options = array_merge($options, collectHolonDescendantOptions($child, $label));
		}

		return $options;
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
			$buttons = array();
			foreach ($organizations as $organization) {
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
						array('text' => 'Changer d’organisation', 'callback_data' => 'btn_classify_root'),
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
		$children = getVisibleHolonChildren($currentNode);
		$descendantOptions = collectHolonDescendantOptions($currentNode);
		$useIncrementalMode = count($descendantOptions) > 4;

		$buttons = array(
			array(
				array(
					'text' => 'Terminer ici',
					'callback_data' => 'btn_classify_done_'.$organization->getId().'_'.($selectedHolon ? $selectedHolon->getId() : 0),
				),
			),
		);

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
					'text' => 'Changer d’organisation',
					'callback_data' => 'btn_classify_root',
				),
			);
		} else {
			$buttons[] = array(
				array(
					'text' => 'Changer d’organisation',
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
					),
				);
			}
		} else {
			foreach ($descendantOptions as $option) {
				$buttons[] = array(
					array(
						'text' => $option['label'],
						'callback_data' => 'btn_classify_done_'.$organization->getId().'_'.$option['holon']->getId(),
					),
				);
			}
		}

		$buttons[] = array(
			array('text' => 'Annuler', 'callback_data' => 'btn_classify_cancel'),
		);

		$text = "Classer ce mémo\n\nEmplacement sélectionné : ".$currentPath;
		if (count($descendantOptions) > 0 && !$useIncrementalMode) {
			$text .= "\n\nChoisissez directement une destination ci-dessous, ou utilisez \"Terminer ici\" pour valider cet emplacement.";
		} elseif (count($children) > 0) {
			$text .= "\n\nChoisissez un sous-niveau, ou utilisez \"Terminer ici\" pour valider cet emplacement.";
		} else {
			$text .= "\n\nAucun sous-niveau supplémentaire n'est disponible ici. Vous pouvez terminer maintenant.";
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

		if ($actorId <= 0 || $chatId === null || !isset($message['voice'])) {
			return;
		}

		$data = loadLocalSession($actorId);
		if (isset($data->active) && !$data->active) {
			return;
		}

		$voice = $message['voice'];
		$fileId = $voice['file_id'] ?? '';
		$duration = isset($voice['duration']) ? (int)$voice['duration'] : 0;

		if ($fileId === '' || $duration < $minTimeMessage) {
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
		$filePath = $fileInfo['result']['file_path'] ?? null;
		if (!$filePath) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, je n'ai pas réussi à récupérer le fichier audio.", null, $threadId);
			return;
		}

		$audioUrl = "https://api.telegram.org/file/bot".TOKEN."/".$filePath;
		$audioContent = @file_get_contents($audioUrl);
		if ($audioContent === false) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, le téléchargement du fichier audio a échoué.", null, $threadId);
			return;
		}

		$tempFilePath = tempnam(sys_get_temp_dir(), 'audio');
		file_put_contents($tempFilePath, $audioContent);

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
		curl_close($ch);
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

		$prompt = "une mise en page lisible, exhaustive, optimisée pour la lecture et structurée du texte (si nécessaire avec des titres ou des listes à puce)";
		$readable = say("Peux-tu générer un JSON pour le texte suivant, comprenant 4 entrée: une entrée 'titre' avec un titre pour ce document, une entrée 'resume' avec un résumé du texte en maximum 150 caractères, une entrée 'contenu' avec ".$prompt.", et finalement une entrée 'hashtag' contenant un tableau avec 3 à 5 mots clés pertinents pour ce texte? Voici le texte : \n".$response->text);

		$dataerr = json_decode("{}");
		$dataerr->GPTreturn = $readable;
		saveLocalSession($dataerr, "error_log");

		$readableJson = extractJsonObjectFromText((string)$readable);
		if ($readableJson === null) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, problème de conversion du JSON...", null, $threadId);
			return;
		}

		$dataerr->regexp = $readableJson;
		saveLocalSession($dataerr, "error_log");

		$readableObject = json_decode($readableJson);
		if (
			!is_object($readableObject)
			|| !isset($readableObject->titre)
			|| !isset($readableObject->resume)
			|| !isset($readableObject->contenu)
		) {
			if ($waitMessageId) {
				deleteMessage($chatId, $waitMessageId, $threadId);
			}
			sendMessage($chatId, "Désolé, le JSON généré n'est pas exploitable.", null, $threadId);
			return;
		}

		$dataerr->json = $readableObject;
		saveLocalSession($dataerr, "error_log");

		$title = trim((string)$readableObject->titre);
		$resume = trim((string)$readableObject->resume);
		$content = (string)$response->text;
		$content2 = (string)$readableObject->contenu;
		$hashtags = normalizeHashtagList($readableObject->hashtag ?? array());
		$hash = "#" . implode(" #", array_filter(array_map(function ($tag) {
			$tag = str_replace(' ', '_', trim((string)$tag));
			return $tag !== '' ? $tag : null;
		}, $hashtags)));

		$dataerr->title = $title;
		$dataerr->resume = $resume;
		$dataerr->content = $content;
		$dataerr->hash = $hash;
		saveLocalSession($dataerr, "error_log");

		$doc = null;
		if ($user->getId() > 0) {
			$user->refreshDbh();

			try {
				$doc = new \dbObject\Document();
				$doc->set("title", $title !== '' ? $title : "Mémo vocal");
				$doc->set("description", $resume);
				$doc->set("content", $content);
				$doc->set("keywords", $hash);
				$doc->set("IDuser", $user->getId());

				if (($message['chat']['id'] ?? null) != ($message['from']['id'] ?? null)) {
					$doc->set("codeview", bin2hex(random_bytes(10)));
				}

				$doc->save();

				$txt = new \dbObject\AltText();
				$txt->set("IDdocument", $doc->getId());
				$txt->set("IDaiprompt", 0);
				$txt->set("text", $content2);
				$txt->save();

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

		$messageText = "\xE2\xAC\x86 ".$resume."\n".$hash;
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

		if (preg_match('/^\/connect/', $text)) {
			if (($message['chat']['id'] ?? null) == ($message['from']['id'] ?? null)) {
				if ($user->getId() > 0) {
					sendMessage($chatId, "Connexion confirmée avec ".getTelegramConnectedUserLabel($user).".", null, $threadId);
				} else {
					sendMessage($chatId, "Pour connecter EasyMEMO à votre compte Telegram, éditez les paramètres de votre compte avec la valeur suivante pour le champ TelegramID: ".$chatId, null, $threadId);
				}
			} else {
				sendMessage($chatId, "Pour connecter ce groupe à un projet, éditer les propriétés du projet avec les informations suivantes:\n\nChat ID: ".$chatId.", Group: ".$threadId, null, $threadId);
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

		if (preg_match('/^\/start/', $text)) {
			$data = loadLocalSession($actorId);
			$data->active = true;
			saveLocalSession($data, $actorId);
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
