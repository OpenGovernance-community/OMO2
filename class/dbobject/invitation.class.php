<?php
	namespace dbObject;

	class Invitation extends DbObject
	{
		const REQUEST_ORIGIN_ADMIN = 'admin';
		const REQUEST_ORIGIN_MEMBER = 'member';

		public static function tableName()
		{
			return 'invitation';
		}

		public static function rules()
		{
			return [
				[['id'], 'integer'],
				[['IDorganization', 'IDuser', 'IDuser_sender'], 'fk'],
				[['email', 'token', 'status', 'request_origin'], 'string'],
				[['parameters'], 'parameters'],
				[['datecreation', 'dateexpiration', 'dateresponse'], 'datetime'],
				[['active'], 'boolean'],
				[['id', 'datecreation', 'dateresponse'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDuser' => 'Utilisateur invite',
				'IDuser_sender' => 'Initiateur',
				'email' => 'E-mail',
				'token' => 'Jeton',
				'request_origin' => 'Origine de la demande',
				'status' => 'Statut',
				'parameters' => 'Parametres',
				'datecreation' => 'Creation',
				'dateexpiration' => 'Expiration',
				'dateresponse' => 'Reponse',
				'active' => 'Actif',
			];
		}

		public static function attributeLength()
		{
			return [
				'email' => 250,
				'token' => 64,
				'request_origin' => 20,
				'status' => 20,
			];
		}

		public static function getOrder()
		{
			return 'datecreation DESC, id DESC';
		}

		public static function normalizeRequestOrigin($value)
		{
			$value = trim(mb_strtolower((string)$value, 'UTF-8'));
			if ($value === self::REQUEST_ORIGIN_MEMBER) {
				return self::REQUEST_ORIGIN_MEMBER;
			}

			return self::REQUEST_ORIGIN_ADMIN;
		}

		public static function findPendingForOrganizationUser($organizationId, $userId)
		{
			$query = "
				SELECT *
				FROM invitation
				WHERE IDorganization = :organization_id
				  AND IDuser = :user_id
				  AND status = 'pending'
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				ORDER BY id DESC
				LIMIT 1
			";

			$row = self::fetchRow($query, [
				'organization_id' => (int)$organizationId,
				'user_id' => (int)$userId,
			]);

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findPendingForUser($userId)
		{
			$userId = (int)$userId;
			if ($userId <= 0) {
				return [];
			}

			$query = "
				SELECT inv.id
				FROM invitation inv
				LEFT JOIN user_organization uo
					ON uo.IDuser = inv.IDuser
					AND uo.IDorganization = inv.IDorganization
					AND uo.active = 1
				WHERE inv.IDuser = :user_id
				  AND inv.status = 'pending'
				  AND inv.active = 1
				  AND (inv.dateexpiration IS NULL OR inv.dateexpiration > NOW())
				  AND (inv.request_origin IS NULL OR inv.request_origin != :request_origin_member)
				  AND uo.id IS NULL
				ORDER BY inv.datecreation DESC, inv.id DESC
			";

			$rows = self::fetchAll($query, [
				'user_id' => $userId,
				'request_origin_member' => self::REQUEST_ORIGIN_MEMBER,
			]);
			if ($rows === false) {
				return [];
			}

			$itemsByOrganizationId = [];
			foreach ($rows as $row) {
				$item = new self();
				if (!$item->load((int)($row['id'] ?? 0))) {
					continue;
				}

				$organizationId = (int)$item->get('IDorganization');
				if ($organizationId <= 0 || isset($itemsByOrganizationId[$organizationId])) {
					continue;
				}

				$itemsByOrganizationId[$organizationId] = $item;
			}

			return array_values($itemsByOrganizationId);
		}

		public static function findByToken($token)
		{
			$query = "
				SELECT *
				FROM invitation
				WHERE token = :token
				ORDER BY id DESC
				LIMIT 1
			";

			$row = self::fetchRow($query, ['token' => trim((string)$token)]);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findValidByToken($token)
		{
			$query = "
				SELECT *
				FROM invitation
				WHERE token = :token
				  AND status = 'pending'
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				ORDER BY id DESC
				LIMIT 1
			";

			$row = self::fetchRow($query, ['token' => trim((string)$token)]);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		protected static function generateToken()
		{
			for ($attempt = 0; $attempt < 5; $attempt += 1) {
				$token = bin2hex(random_bytes(32));
				if (!self::findByToken($token)) {
					return $token;
				}
			}

			throw new \RuntimeException("Le jeton d'invitation n'a pas pu etre genere.");
		}

		public static function issue($organizationId, $userId, $senderUserId = 0, $email = '', array $options = [])
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			$senderUserId = (int)$senderUserId;
			$email = trim(mb_strtolower((string)$email, 'UTF-8'));
			$requestOrigin = self::normalizeRequestOrigin($options['requestOrigin'] ?? self::REQUEST_ORIGIN_ADMIN);
			$requestMessage = trim((string)($options['requestMessage'] ?? ''));

			if ($organizationId <= 0 || $userId <= 0) {
				throw new \RuntimeException("L'invitation demandee est invalide.");
			}

			$existing = self::findPendingForOrganizationUser($organizationId, $userId);
			if ($existing) {
				if ($requestOrigin === self::REQUEST_ORIGIN_MEMBER && $existing->isMemberInitiatedRequest() && $requestMessage !== '') {
					$parameters = $existing->get('parameters');
					if (!is_array($parameters)) {
						$parameters = [];
					}
					$parameters['request_message'] = $requestMessage;
					$existing->set('parameters', $parameters);
					$existing->save();
				}

				return [
					'invitation' => $existing,
					'created' => false,
				];
			}

			$item = new self();
			$item->set('IDorganization', $organizationId);
			$item->set('IDuser', $userId);
			$item->set('IDuser_sender', $senderUserId > 0 ? $senderUserId : null);
			$item->set('email', $email !== '' ? $email : null);
			$item->set('token', self::generateToken());
			$item->set('request_origin', $requestOrigin);
			$item->set('status', 'pending');
			$item->set('dateexpiration', new \DateTime('+14 days'));
			$item->set('active', true);

			$parameters = $item->get('parameters');
			if (!is_array($parameters)) {
				$parameters = [];
			}
			if ($requestMessage !== '') {
				$parameters['request_message'] = $requestMessage;
			}
			$item->set('parameters', $parameters);

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status']) || (int)$item->getId() <= 0) {
				throw new \RuntimeException("L'invitation n'a pas pu etre creee.");
			}

			return [
				'invitation' => $item,
				'created' => true,
			];
		}

		public function isPending()
		{
			return (string)$this->get('status') === 'pending' && (bool)$this->get('active');
		}

		public function isExpired()
		{
			$expiration = $this->get('dateexpiration');
			return $expiration instanceof \DateTimeInterface && $expiration <= new \DateTime();
		}

		public function getRequestOrigin()
		{
			return self::normalizeRequestOrigin($this->get('request_origin'));
		}

		public function isMemberInitiatedRequest()
		{
			return $this->getRequestOrigin() === self::REQUEST_ORIGIN_MEMBER;
		}

		public function isAdminInitiatedInvitation()
		{
			return !$this->isMemberInitiatedRequest();
		}

		public function getRequestMessage()
		{
			$message = $this->getParameter('request_message');
			if ($message !== null && trim((string)$message) !== '') {
				return trim((string)$message);
			}

			$parameters = $this->get('parameters');
			if (is_string($parameters) && trim($parameters) !== '') {
				$decoded = json_decode($parameters, true);
				if (is_array($decoded)) {
					return trim((string)($decoded['request_message'] ?? ''));
				}
			}

			if (is_array($parameters)) {
				return trim((string)($parameters['request_message'] ?? ''));
			}

			return '';
		}

		protected function getOrganizationObject()
		{
			$organization = new \dbObject\Organization();
			return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
		}

		protected function getInvitedUserObject()
		{
			$user = new \dbObject\User();
			return $user->load((int)$this->get('IDuser')) ? $user : null;
		}

		protected function getOrganizationRootHolonId()
		{
			$organization = $this->getOrganizationObject();
			if (!$organization) {
				return 0;
			}

			$rootHolon = $organization->getStructuralRootHolon();
			return $rootHolon ? (int)$rootHolon->getId() : 0;
		}

		protected function getOrganizationBaseUrl($path = '/omo/')
		{
			$organization = $this->getOrganizationObject();
			if (!$organization) {
				return commonBuildUrl($path);
			}

			$targetHost = commonGetRequestHost();
			$shortname = trim((string)$organization->get('shortname'));
			if (commonUseOrganizationSubdomains() && $shortname !== '') {
				$builtHost = commonBuildOrganizationHost($shortname, commonGetRootHost($targetHost));
				if (trim((string)$builtHost) !== '') {
					$targetHost = $builtHost;
				}
			} else {
				$targetHost = commonGetRootHost($targetHost);
			}

			return commonBuildUrl($path, $targetHost);
		}

		public function getInvitationUrl()
		{
			return $this->getOrganizationBaseUrl('/common/invitation.php?token=' . rawurlencode((string)$this->get('token')));
		}

		public function getApprovalUrl()
		{
			return $this->getInvitationUrl();
		}

		public function getPendingHolons()
		{
			$holons = [];
			$organization = $this->getOrganizationObject();
			$hasActiveOrganizationMembership = (int)self::fetchValue(
				"SELECT COUNT(*) FROM user_organization WHERE IDuser = :user_id AND IDorganization = :organization_id AND active = 1",
				[
					'user_id' => (int)$this->get('IDuser'),
					'organization_id' => (int)$this->get('IDorganization'),
				]
			) > 0;
			$hasInactiveOrganizationMembership = (int)self::fetchValue(
				"SELECT COUNT(*) FROM user_organization WHERE IDuser = :user_id AND IDorganization = :organization_id AND active = 0",
				[
					'user_id' => (int)$this->get('IDuser'),
					'organization_id' => (int)$this->get('IDorganization'),
				]
			) > 0;
			if ($organization && !$hasActiveOrganizationMembership && $hasInactiveOrganizationMembership) {
				$holons[] = [
					'id' => 0,
					'name' => trim((string)$organization->get('name')),
					'typeLabel' => 'Organisation',
				];
			}

			foreach ($this->getScopedUserHolonLinks(false) as $link) {
				$holon = new \dbObject\Holon();
				if (!$holon->load((int)$link->get('IDholon'))) {
					continue;
				}
				if (!(bool)$holon->get('active') || !(bool)$holon->get('visible')) {
					continue;
				}
				$holons[] = [
					'id' => (int)$holon->getId(),
					'name' => $holon->getDisplayName(),
					'typeLabel' => $holon->getTemplateLabel(true),
				];
			}

			return $holons;
		}

		protected function getScopedUserHolonLinks($activeFilter = null)
		{
			$userId = (int)$this->get('IDuser');
			$rootHolonId = (int)$this->getOrganizationRootHolonId();

			if ($userId <= 0 || $rootHolonId <= 0) {
				return [];
			}

			$query = "
				SELECT id
				FROM user_holon
				WHERE IDuser = :user_id
			";
			$params = [
				'user_id' => $userId,
			];

			if ($activeFilter === true) {
				$query .= " AND active = 1";
			} elseif ($activeFilter === false) {
				$query .= " AND active = 0";
			}

			$query .= " ORDER BY id ASC";

			$rows = self::fetchAll($query, $params);
			if ($rows === false) {
				return [];
			}

			$links = [];
			foreach ($rows as $row) {
				$link = new \dbObject\UserHolon();
				if (!$link->load((int)$row['id'])) {
					continue;
				}

				$holon = new \dbObject\Holon();
				if (!$holon->load((int)$link->get('IDholon'))) {
					continue;
				}

				if (!$holon->isDescendantOf($rootHolonId)) {
					continue;
				}

				$links[] = $link;
			}

			return $links;
		}

		protected function getOrganizationAdminRecipients()
		{
			$organizationId = (int)$this->get('IDorganization');
			if ($organizationId <= 0) {
				return [];
			}

			$memberships = new \dbObject\ArrayUserOrganization();
			$memberships->loadActiveForOrganization($organizationId);

			$recipients = [];
			$seenEmails = [];
			foreach ($memberships as $membership) {
				if (!($membership instanceof \dbObject\UserOrganization) || !$membership->isOrganizationAdmin()) {
					continue;
				}

				$email = trim(mb_strtolower((string)$membership->getScopedEmail(), 'UTF-8'));
				if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$email])) {
					continue;
				}

				$seenEmails[$email] = true;
				$recipients[] = [
					'email' => $email,
					'name' => trim((string)$membership->getUserDisplayName()),
				];
			}

			return $recipients;
		}

		protected function sendAdminInvitationEmail()
		{
			require_once dirname(__DIR__, 2) . '/common/email_layout.php';

			$organization = $this->getOrganizationObject();
			$user = $this->getInvitedUserObject();

			if (!$organization || !$user) {
				throw new \RuntimeException("L'invitation ne peut pas etre envoyee.");
			}

			$email = trim((string)$this->get('email'));
			if ($email === '') {
				$email = trim((string)$user->get('email'));
			}

			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \RuntimeException("L'adresse e-mail d'invitation est invalide.");
			}

			$organizationName = trim((string)$organization->get('name'));
			if ($organizationName === '') {
				$organizationName = 'cette organisation';
			}

			$holons = $this->getPendingHolons();
			$detailsHtml = '';
			if (count($holons) > 0) {
				$detailsHtml .= '<div style="font-weight:700; margin:0 0 10px;">Acces en attente</div>';
				$detailsHtml .= '<ul style="margin:0; padding-left:18px; color:#334155;">';
				foreach ($holons as $holon) {
					$detailsHtml .= '<li style="margin:0 0 8px;">'
						. '<strong>' . \commonMailEscape((string)$holon['name']) . '</strong>'
						. ' <span style="color:#64748b;">(' . \commonMailEscape((string)$holon['typeLabel']) . ')</span>'
						. '</li>';
				}
				$detailsHtml .= '</ul>';
			}

			$message = \commonRenderMailLayout([
				'brand_name' => $organizationName,
				'brand_color' => trim((string)$organization->get('color')),
				'logo_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
				'banner_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
				'heading' => 'Invitation a rejoindre ' . $organizationName,
				'intro_html' => '<p style="margin:0 0 14px; color:#475569; line-height:1.7;">Vous etes invite a rejoindre cette organisation.</p>',
				'body_html' => '<p style="margin:0; color:#475569; line-height:1.7;">Ouvrez ce lien pour accepter ou refuser l invitation.</p>',
				'details_html' => $detailsHtml,
				'button_label' => 'Consulter l invitation',
				'button_url' => $this->getInvitationUrl(),
				'footer_html' => '<p style="margin:0;">Ce lien reste valable jusqu au '
					. \commonMailEscape(($this->get('dateexpiration') instanceof \DateTimeInterface ? $this->get('dateexpiration')->format('d.m.Y H:i') : 'prochaines semaines'))
					. '.</p>',
			]);

			$fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
			if ($fromAddress === '') {
				$host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
				$fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
			}

			if (!myHTMLMail([$fromAddress, (string)$organization->get('name')], $email, 'Invitation a rejoindre ' . $organizationName, $message)) {
				throw new \RuntimeException("L'invitation n'a pas pu etre envoyee.");
			}

			return true;
		}

		protected function sendMembershipRequestEmail()
		{
			require_once dirname(__DIR__, 2) . '/common/email_layout.php';

			$organization = $this->getOrganizationObject();
			$user = $this->getInvitedUserObject();
			$recipients = $this->getOrganizationAdminRecipients();

			if (!$organization || !$user || count($recipients) === 0) {
				throw new \RuntimeException("Aucun administrateur ne peut recevoir cette demande pour le moment.");
			}

			$requesterLabel = trim((string)$user->getScopedDisplayName((int)$organization->getId()));
			if ($requesterLabel === '') {
				$requesterLabel = trim((string)$user->get('email'));
			}
			if ($requesterLabel === '') {
				$requesterLabel = 'Utilisateur ' . (int)$user->getId();
			}

			$requesterEmail = trim((string)$this->get('email'));
			if ($requesterEmail === '') {
				$requesterEmail = trim((string)$user->get('email'));
			}

			$organizationName = trim((string)$organization->get('name'));
			if ($organizationName === '') {
				$organizationName = 'cette organisation';
			}

			$detailsHtml = '<div style="display:grid; gap:10px;">'
				. '<div><strong>Personne</strong><br>' . \commonMailEscape($requesterLabel) . '</div>';
			if ($requesterEmail !== '') {
				$detailsHtml .= '<div><strong>E-mail</strong><br>' . \commonMailEscape($requesterEmail) . '</div>';
			}

			$requestMessage = $this->getRequestMessage();
			if ($requestMessage !== '') {
				$detailsHtml .= '<div><strong>Message</strong><br>' . \commonMailTextToHtml($requestMessage) . '</div>';
			}
			$detailsHtml .= '</div>';

			$message = \commonRenderMailLayout([
				'brand_name' => $organizationName,
				'brand_color' => trim((string)$organization->get('color')),
				'logo_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
				'banner_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
				'heading' => 'Nouvelle demande d acces',
				'intro_html' => '<p style="margin:0 0 14px; color:#475569; line-height:1.7;">'
					. \commonMailEscape($requesterLabel)
					. ' souhaite rejoindre '
					. \commonMailEscape($organizationName)
					. '.</p>',
				'body_html' => '<p style="margin:0; color:#475569; line-height:1.7;">Un clic sur le bouton ci-dessous validera directement son inscription.</p>',
				'details_html' => $detailsHtml,
				'button_label' => 'Valider l inscription',
				'button_url' => $this->getApprovalUrl(),
				'footer_html' => '<p style="margin:0;">Ce lien reste valable jusqu au '
					. \commonMailEscape(($this->get('dateexpiration') instanceof \DateTimeInterface ? $this->get('dateexpiration')->format('d.m.Y H:i') : 'prochaines semaines'))
					. '.</p>',
			]);

			$fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
			if ($fromAddress === '') {
				$host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
				$fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
			}

			$recipientEmails = array_map(static function (array $recipient) {
				return (string)$recipient['email'];
			}, $recipients);

			if (!myHTMLMail([$fromAddress, (string)$organization->get('name')], $recipientEmails, 'Demande d acces a ' . $organizationName, $message)) {
				throw new \RuntimeException("La demande n'a pas pu etre envoyee aux administrateurs.");
			}

			return true;
		}

		protected function sendMemberRequestAcceptedEmail()
		{
			require_once dirname(__DIR__, 2) . '/common/email_layout.php';

			$organization = $this->getOrganizationObject();
			$user = $this->getInvitedUserObject();

			if (!$organization || !$user) {
				throw new \RuntimeException("La confirmation ne peut pas etre envoyee.");
			}

			$email = trim((string)$this->get('email'));
			if ($email === '') {
				$email = trim((string)$user->get('email'));
			}

			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \RuntimeException("L'adresse e-mail de confirmation est invalide.");
			}

			$organizationName = trim((string)$organization->get('name'));
			if ($organizationName === '') {
				$organizationName = 'cette organisation';
			}

			$targetUrl = commonBuildOrganizationHomeUrl(
				(int)$organization->getId(),
				(string)$organization->get('shortname'),
				commonGetRequestHost()
			);

			$message = \commonRenderMailLayout([
				'brand_name' => $organizationName,
				'brand_color' => trim((string)$organization->get('color')),
				'logo_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
				'banner_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
				'heading' => 'Votre inscription est validee',
				'intro_html' => '<p style="margin:0 0 14px; color:#475569; line-height:1.7;">Votre demande pour rejoindre '
					. \commonMailEscape($organizationName)
					. ' a ete acceptee.</p>',
				'body_html' => '<p style="margin:0; color:#475569; line-height:1.7;">Vous pouvez maintenant acceder a cet espace et a ses outils.</p>',
				'button_label' => 'Entrer dans l organisation',
				'button_url' => $targetUrl,
			]);

			$fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
			if ($fromAddress === '') {
				$host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
				$fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
			}

			if (!myHTMLMail([$fromAddress, (string)$organization->get('name')], $email, 'Inscription validee dans ' . $organizationName, $message)) {
				throw new \RuntimeException("L'e-mail de confirmation n'a pas pu etre envoye.");
			}

			return true;
		}

		public function sendEmail()
		{
			if ($this->isMemberInitiatedRequest()) {
				return $this->sendMembershipRequestEmail();
			}

			return $this->sendAdminInvitationEmail();
		}

		protected function updateSiblingPendingInvitations($status)
		{
			self::execute(
				"UPDATE invitation
				 SET status = :status,
				     active = 0,
				     dateresponse = NOW()
				 WHERE IDorganization = :organization_id
				   AND IDuser = :user_id
				   AND id != :current_id
				   AND status = 'pending'
				   AND active = 1",
				[
					'status' => (string)$status,
					'organization_id' => (int)$this->get('IDorganization'),
					'user_id' => (int)$this->get('IDuser'),
					'current_id' => (int)$this->getId(),
				]
			);
		}

		protected function acceptInternal($sendConfirmationEmail = false, $approvedByUserId = 0)
		{
			if (!$this->isPending() || $this->isExpired()) {
				return [
					'status' => false,
					'message' => "Cette invitation n'est plus valide.",
				];
			}

			$organizationId = (int)$this->get('IDorganization');
			$userId = (int)$this->get('IDuser');
			$rootHolonId = (int)$this->getOrganizationRootHolonId();
			$pdo = \dbObject\DbObject::getPdo();

			if (!$pdo) {
				return [
					'status' => false,
					'message' => 'La connexion a la base de donnees est indisponible.',
				];
			}

			try {
				$ownsTransaction = !$pdo->inTransaction();
				if ($ownsTransaction) {
					$pdo->beginTransaction();
				}

				$user = new \dbObject\User();
				if (!$user->load($userId)) {
					throw new \RuntimeException("Le profil invite est introuvable.");
				}

				if (!(bool)$user->get('active')) {
					$user->set('active', true);
					$saveUser = $user->save();
					if (!is_array($saveUser) || empty($saveUser['status'])) {
						throw new \RuntimeException("Le profil utilisateur n'a pas pu etre active.");
					}
				}

				$membership = new \dbObject\UserOrganization();
				if (!$membership->load([
					['IDuser', $userId],
					['IDorganization', $organizationId],
				])) {
					$membership->set('IDuser', $userId);
					$membership->set('IDorganization', $organizationId);
				}

				if (trim((string)$membership->get('email')) === '' && trim((string)$this->get('email')) !== '') {
					$membership->set('email', trim((string)$this->get('email')));
				}

				$membership->set('active', true);
				$saveMembership = $membership->save();
				if (!is_array($saveMembership) || empty($saveMembership['status'])) {
					throw new \RuntimeException("L'adhesion a l'organisation n'a pas pu etre confirmee.");
				}

				self::execute(
					"UPDATE user_organization
					 SET active = 1
					 WHERE IDuser = :user_id
					   AND IDorganization = :organization_id",
					[
						'user_id' => $userId,
						'organization_id' => $organizationId,
					]
				);

				if ($rootHolonId > 0) {
					foreach ($this->getScopedUserHolonLinks(false) as $link) {
						$link->set('active', true);
						$saveLink = $link->save();
						if (!is_array($saveLink) || empty($saveLink['status'])) {
							throw new \RuntimeException("Un lien de contexte n'a pas pu etre active.");
						}
					}
				}

				$parameters = $this->get('parameters');
				if (!is_array($parameters)) {
					$parameters = [];
				}
				if ($approvedByUserId > 0) {
					$parameters['approved_by_user_id'] = $approvedByUserId;
					$parameters['approved_at'] = (new \DateTime())->format('c');
				}
				$this->set('parameters', $parameters);

				if ($sendConfirmationEmail) {
					$this->sendMemberRequestAcceptedEmail();
				}

				$this->set('status', 'accepted');
				$this->set('dateresponse', new \DateTime());
				$this->set('active', false);
				$saveInvitation = $this->save();
				if (!is_array($saveInvitation) || empty($saveInvitation['status'])) {
					throw new \RuntimeException("L'invitation n'a pas pu etre mise a jour.");
				}

				$this->updateSiblingPendingInvitations('accepted');

				if ($ownsTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}

				return [
					'status' => true,
					'message' => $this->isMemberInitiatedRequest() ? 'Demande validee.' : 'Invitation acceptee.',
					'userId' => $userId,
					'organizationId' => $organizationId,
				];
			} catch (\Throwable $exception) {
				if (isset($ownsTransaction) && $ownsTransaction && $pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return [
					'status' => false,
					'message' => $exception->getMessage(),
				];
			}
		}

		public function accept()
		{
			return $this->acceptInternal(false, 0);
		}

		public function approveByAdmin(array $options = [])
		{
			$approvedByUserId = (int)($options['approvedByUserId'] ?? 0);
			$sendConfirmationEmail = !empty($options['sendConfirmationEmail']);

			return $this->acceptInternal($sendConfirmationEmail, $approvedByUserId);
		}

		public function decline()
		{
			if (!$this->isPending() || $this->isExpired()) {
				return [
					'status' => false,
					'message' => "Cette invitation n'est plus valide.",
				];
			}

			$organizationId = (int)$this->get('IDorganization');
			$userId = (int)$this->get('IDuser');
			$rootHolonId = (int)$this->getOrganizationRootHolonId();
			$pdo = \dbObject\DbObject::getPdo();

			if (!$pdo) {
				return [
					'status' => false,
					'message' => 'La connexion a la base de donnees est indisponible.',
				];
			}

			try {
				$pdo->beginTransaction();

				if ($rootHolonId > 0) {
					$linkIds = [];
					foreach ($this->getScopedUserHolonLinks(false) as $link) {
						$linkIds[] = (int)$link->getId();
					}

					if (count($linkIds) > 0) {
						$placeholders = [];
						$params = [];
						foreach ($linkIds as $index => $linkId) {
							$key = 'link_' . $index;
							$placeholders[] = ':' . $key;
							$params[$key] = $linkId;
						}

						self::execute(
							"DELETE FROM user_holon WHERE id IN (" . implode(', ', $placeholders) . ")",
							$params
						);
					}
				}

				self::execute(
					"DELETE FROM user_organization
					 WHERE IDuser = :user_id
					   AND IDorganization = :organization_id
					   AND active = 0",
					[
						'user_id' => $userId,
						'organization_id' => $organizationId,
					]
				);

				$this->set('status', 'declined');
				$this->set('dateresponse', new \DateTime());
				$this->set('active', false);
				$saveInvitation = $this->save();
				if (!is_array($saveInvitation) || empty($saveInvitation['status'])) {
					throw new \RuntimeException("L'invitation n'a pas pu etre mise a jour.");
				}

				$this->updateSiblingPendingInvitations('declined');

				$pdo->commit();

				return [
					'status' => true,
					'message' => 'Invitation refusee.',
					'userId' => $userId,
					'organizationId' => $organizationId,
				];
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return [
					'status' => false,
					'message' => $exception->getMessage(),
				];
			}
		}
	}

?>
