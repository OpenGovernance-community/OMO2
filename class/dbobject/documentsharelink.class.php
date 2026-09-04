<?php
	namespace dbObject;

	class DocumentShareLink extends DbObject
	{
		public static function tableName()
		{
			return 'document_share_link';
		}

		public static function rules()
		{
			return [
				[['IDorganization', 'IDdocument', 'IDuser'], 'required'],
				[['id', 'IDorganization', 'IDdocument', 'IDuser'], 'integer'],
				[['label', 'token', 'password_hash'], 'string'],
				[['datecreation', 'dateexpiration'], 'datetime'],
				[['allow_live_follow', 'active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDdocument' => 'Document',
				'IDuser' => 'Createur',
				'label' => 'Libelle',
				'token' => 'Token',
				'password_hash' => 'Mot de passe',
				'allow_live_follow' => 'Suivi temps reel',
				'datecreation' => 'Date creation',
				'dateexpiration' => 'Date expiration',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDorganization' => 'Organisation du document partage.',
				'IDdocument' => 'Document partage.',
				'IDuser' => 'Utilisateur ayant cree le lien.',
				'label' => 'Libelle interne du lien.',
				'token' => 'Token unique partageable.',
				'password_hash' => 'Hash du mot de passe optionnel.',
				'allow_live_follow' => 'Autorise l affichage du brouillon temporaire pendant l edition.',
				'dateexpiration' => 'Date de fin de validite du lien.',
			];
		}

		public static function attributeLength()
		{
			return [
				'label' => 150,
				'token' => 80,
				'password_hash' => 255,
			];
		}

		public static function getOrder()
		{
			return 'datecreation DESC, id DESC';
		}

		protected static function generateToken($length = 48)
		{
			$length = max(24, (int)$length);
			$raw = rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
			return substr($raw, 0, 80);
		}

		public static function generateUniqueToken()
		{
			for ($attempt = 0; $attempt < 10; $attempt++) {
				$token = self::generateToken();
				if (!self::findByToken($token)) {
					return $token;
				}
			}

			return self::generateToken(64);
		}

		public static function findByToken($token)
		{
			$token = trim((string)$token);
			if ($token === '') {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM document_share_link
				WHERE token = :token
				LIMIT 1",
				array(
					'token' => $token,
				)
			);

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
			$token = trim((string)$token);
			if ($token === '') {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM document_share_link
				WHERE token = :token
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				LIMIT 1",
				array(
					'token' => $token,
				)
			);

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findByIdForContext($shareLinkId, $organizationId, $documentId, $includeInactive = false)
		{
			$shareLinkId = (int)$shareLinkId;
			$organizationId = (int)$organizationId;
			$documentId = (int)$documentId;

			if ($shareLinkId <= 0 || $organizationId <= 0 || $documentId <= 0) {
				return false;
			}

			$sql = "SELECT *
				FROM document_share_link
				WHERE id = :id
				  AND IDorganization = :organization_id
				  AND IDdocument = :document_id";

			if (!$includeInactive) {
				$sql .= "
				  AND active = 1";
			}

			$sql .= "
				LIMIT 1";

			$row = self::fetchRow($sql, array(
				'id' => $shareLinkId,
				'organization_id' => $organizationId,
				'document_id' => $documentId,
			));

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findAllForContext($organizationId, $documentId, $includeInactive = false)
		{
			$organizationId = (int)$organizationId;
			$documentId = (int)$documentId;

			if ($organizationId <= 0 || $documentId <= 0) {
				return array();
			}

			$sql = "SELECT *
				FROM document_share_link
				WHERE IDorganization = :organization_id
				  AND IDdocument = :document_id";

			if (!$includeInactive) {
				$sql .= "
				  AND active = 1";
			}

			$sql .= "
				ORDER BY
				  CASE
				    WHEN dateexpiration IS NULL THEN 0
				    WHEN dateexpiration > NOW() THEN 0
				    ELSE 1
				  END ASC,
				  datecreation DESC,
				  id DESC";

			$rows = self::fetchAll($sql, array(
				'organization_id' => $organizationId,
				'document_id' => $documentId,
			));

			if (!is_array($rows)) {
				return array();
			}

			$items = array();
			foreach ($rows as $row) {
				$item = new self();
				$item->loadFromArray($row);
				$item->setId((int)$row['id']);
				$items[] = $item;
			}

			return $items;
		}

		public static function findActiveLiveFollowForDocumentByLabel($documentId, $label)
		{
			$documentId = (int)$documentId;
			$label = trim((string)$label);
			if ($documentId <= 0 || $label === '') {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM document_share_link
				WHERE IDdocument = :document_id
				  AND label = :label
				  AND allow_live_follow = 1
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				ORDER BY datecreation DESC, id DESC
				LIMIT 1",
				array(
					'document_id' => $documentId,
					'label' => $label,
				)
			);

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function getOrCreateLiveFollowForDocument(Document $document, $userId, $label)
		{
			$documentId = (int)$document->getId();
			$label = trim((string)$label);
			if ($documentId <= 0 || $label === '') {
				return false;
			}

			$existing = self::findActiveLiveFollowForDocumentByLabel($documentId, $label);
			if ($existing instanceof self) {
				return $existing;
			}

			return self::createForDocument($document, (int)$userId, array(
				'label' => $label,
				'allow_live_follow' => true,
			));
		}

		public static function createForDocument(Document $document, $userId, array $options = array())
		{
			$userId = (int)$userId;
			$organizationId = (int)$document->get('IDorganization');

			if (
				$userId <= 0
				|| (int)$document->getId() <= 0
				|| $organizationId <= 0
				|| $document->isFolder()
			) {
				return false;
			}

			$item = new self();
			$item->set('IDorganization', $organizationId);
			$item->set('IDdocument', (int)$document->getId());
			$item->set('IDuser', $userId);
			$item->set('label', trim((string)($options['label'] ?? '')));
			$item->set('token', self::generateUniqueToken());
			$item->set('password_hash', trim((string)($options['password_hash'] ?? '')) ?: null);
			$item->set('allow_live_follow', !empty($options['allow_live_follow']) ? 1 : 0);
			$item->set('datecreation', new \DateTime());
			$item->set('dateexpiration', $options['dateexpiration'] ?? null);
			$item->set('active', 1);

			$result = $item->save();
			return !empty($result['status']) ? $item : false;
		}

		public function requiresPassword()
		{
			return trim((string)$this->get('password_hash')) !== '';
		}

		public function isExpired()
		{
			$dateExpiration = $this->get('dateexpiration');
			if (!$dateExpiration) {
				return false;
			}

			try {
				$expiration = $dateExpiration instanceof \DateTimeInterface
					? $dateExpiration
					: new \DateTime((string)$dateExpiration);
			} catch (\Exception $exception) {
				return false;
			}

			$now = new \DateTime();
			return $expiration <= $now;
		}

		public function verifyPassword($password)
		{
			if (!$this->requiresPassword()) {
				return true;
			}

			$password = (string)$password;
			if ($password === '') {
				return false;
			}

			return password_verify($password, (string)$this->get('password_hash'));
		}

		public function allowsLiveFollow()
		{
			return (bool)$this->get('allow_live_follow');
		}

		public function getDocument()
		{
			$documentId = (int)$this->get('IDdocument');
			if ($documentId <= 0) {
				return null;
			}

			$document = new \dbObject\Document();
			return $document->load($documentId) ? $document : null;
		}

		public function buildShareUrl()
		{
			return '/omo/document_share.php?token=' . rawurlencode((string)$this->get('token'));
		}
	}

?>
