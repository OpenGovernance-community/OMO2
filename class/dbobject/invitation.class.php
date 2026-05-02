<?php
	namespace dbObject;

	class Invitation extends DbObject
	{
		public static function tableName()
		{
			return 'invitation';
		}

		public static function rules()
		{
			return [
				[['id'], 'integer'],
				[['IDorganization', 'IDuser', 'IDuser_sender'], 'fk'],
				[['email', 'token', 'status'], 'string'],
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
				'IDuser' => 'Utilisateur invité',
				'IDuser_sender' => 'Invité par',
				'email' => 'E-mail',
				'token' => 'Jeton',
				'status' => 'Statut',
				'parameters' => 'Paramètres',
				'datecreation' => 'Création',
				'dateexpiration' => 'Expiration',
				'dateresponse' => 'Réponse',
				'active' => 'Actif',
			];
		}

		public static function attributeLength()
		{
			return [
				'email' => 250,
				'token' => 64,
				'status' => 20,
			];
		}

		public static function getOrder()
		{
			return 'datecreation DESC, id DESC';
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

			throw new \RuntimeException("Le jeton d'invitation n'a pas pu être généré.");
		}

		public static function issue($organizationId, $userId, $senderUserId = 0, $email = '')
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			$senderUserId = (int)$senderUserId;
			$email = trim(mb_strtolower((string)$email, 'UTF-8'));

			if ($organizationId <= 0 || $userId <= 0) {
				throw new \RuntimeException("L'invitation demandée est invalide.");
			}

			$existing = self::findPendingForOrganizationUser($organizationId, $userId);
			if ($existing) {
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
			$item->set('status', 'pending');
			$item->set('dateexpiration', new \DateTime('+14 days'));
			$item->set('active', true);

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status']) || (int)$item->getId() <= 0) {
				throw new \RuntimeException("L'invitation n'a pas pu être créée.");
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
			if ($shortname !== '') {
				$builtHost = commonBuildOrganizationHost($shortname, commonGetRootHost($targetHost));
				if (trim((string)$builtHost) !== '') {
					$targetHost = $builtHost;
				}
			}

			return commonBuildUrl($path, $targetHost);
		}

		public function getInvitationUrl()
		{
			return $this->getOrganizationBaseUrl('/common/invitation.php?token=' . rawurlencode((string)$this->get('token')));
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

		public function sendEmail()
		{
			$organization = $this->getOrganizationObject();
			$user = $this->getInvitedUserObject();

			if (!$organization || !$user) {
				throw new \RuntimeException("L'invitation ne peut pas être envoyée.");
			}

			$email = trim((string)$this->get('email'));
			if ($email === '') {
				$email = trim((string)$user->get('email'));
			}

			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \RuntimeException("L'adresse e-mail d'invitation est invalide.");
			}

			$subject = "Invitation à rejoindre " . trim((string)$organization->get('name'));
			$link = $this->getInvitationUrl();
			$holons = $this->getPendingHolons();
			$holonList = '';

			if (count($holons) > 0) {
				$holonList .= '<ul style="text-align:left;display:inline-block;margin:12px 0 0;padding-left:18px;">';
				foreach ($holons as $holon) {
					$holonList .= '<li><strong>' . htmlspecialchars((string)$holon['name'], ENT_QUOTES, 'UTF-8') . '</strong> <span style="color:#64748b;">(' . htmlspecialchars((string)$holon['typeLabel'], ENT_QUOTES, 'UTF-8') . ')</span></li>';
				}
				$holonList .= '</ul>';
			}

			$organizationName = htmlspecialchars((string)$organization->get('name'), ENT_QUOTES, 'UTF-8');
			$organizationColorValue = trim((string)$organization->get('color'));
			if ($organizationColorValue === '' || stripos($organizationColorValue, 'var(') !== false) {
				$organizationColorValue = '#004663';
			}
			$organizationColor = htmlspecialchars($organizationColorValue, ENT_QUOTES, 'UTF-8');
			$logo = trim((string)$organization->get('logo'));
			$banner = trim((string)$organization->get('banner'));

			$message = "
<html>
<body style='margin:0; font-family:Arial, sans-serif; background:#f5f5f5;'>
<table width='100%' cellpadding='0' cellspacing='0'>
<tr>
<td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='background:white; border-radius:8px; overflow:hidden;'>
<tr>
<td style='background:" . $organizationColor . "; text-align:center; padding:30px 20px; position:relative;'>
    " . ($banner !== '' ? "<div style='background:url(" . htmlspecialchars($banner, ENT_QUOTES, 'UTF-8') . ") center/cover; opacity:0.25; position:absolute; inset:0;'></div>" : "") . "
    <div style='position:relative;'>
        " . ($logo !== '' ? "
        <div style='width:80px;height:80px;border-radius:50%;background:white;margin:0 auto 10px;padding:5px;'>
            <img src='" . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . "' style='width:100%;height:100%;object-fit:cover;border-radius:50%;'>
        </div>
        " : "") . "
        <h2 style='color:white; margin:0;'>" . $organizationName . "</h2>
    </div>
</td>
</tr>
<tr>
<td style='padding:30px; text-align:center;'>
    <h3 style='margin-top:0;'>Vous êtes invité·e à rejoindre cette organisation</h3>
    <p style='color:#555;'>Votre invitation vous donnera accès à l'organisation et aux holons suivants :</p>
    " . $holonList . "
    <p style='margin:22px 0 0; color:#555;'>Ouvrez ce lien pour accepter ou refuser l'invitation :</p>
    <p style='margin:14px 0 0;'>
        <a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "' style='display:inline-block;padding:12px 20px;background:" . $organizationColor . ";color:white;text-decoration:none;border-radius:999px;font-weight:bold;'>
            Consulter l'invitation
        </a>
    </p>
    <p style='margin-top:12px; font-size:12px; word-break:break-all; color:#666;'><a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "' style='color:#2563eb; text-decoration:underline;'>" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "</a></p>
    <p style='margin-top:20px; font-size:12px; color:#888;'>Ce lien reste valable jusqu'au " . htmlspecialchars(($this->get('dateexpiration') instanceof \DateTimeInterface ? $this->get('dateexpiration')->format('d.m.Y H:i') : 'prochaines semaines'), ENT_QUOTES, 'UTF-8') . ".</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
";

			$fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
			if ($fromAddress === '') {
				$host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
				$fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
			}

			if (!myHTMLMail([$fromAddress, (string)$organization->get('name')], $email, $subject, $message)) {
				throw new \RuntimeException("L'invitation n'a pas pu être envoyée.");
			}

			return true;
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

		public function accept()
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
					'message' => 'La connexion à la base de données est indisponible.',
				];
			}

			try {
				$pdo->beginTransaction();

				$user = new \dbObject\User();
				if (!$user->load($userId)) {
					throw new \RuntimeException("Le profil invité est introuvable.");
				}

				if (!(bool)$user->get('active')) {
					$user->set('active', true);
					$user->save();
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
					throw new \RuntimeException("L'adhésion à l'organisation n'a pas pu être confirmée.");
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
						$link->save();
					}
				}

				$this->set('status', 'accepted');
				$this->set('dateresponse', new \DateTime());
				$this->set('active', false);
				$saveInvitation = $this->save();
				if (!is_array($saveInvitation) || empty($saveInvitation['status'])) {
					throw new \RuntimeException("L'invitation n'a pas pu être mise à jour.");
				}

				$this->updateSiblingPendingInvitations('accepted');

				$pdo->commit();

				return [
					'status' => true,
					'message' => 'Invitation acceptée.',
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
					'message' => 'La connexion à la base de données est indisponible.',
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
					throw new \RuntimeException("L'invitation n'a pas pu être mise à jour.");
				}

				$this->updateSiblingPendingInvitations('declined');

				$pdo->commit();

				return [
					'status' => true,
					'message' => 'Invitation refusée.',
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
