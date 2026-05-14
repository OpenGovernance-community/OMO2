<?php
	namespace dbObject;

	class HolonShareLink extends DbObject
	{
		protected $_scopeHolon = null;
		protected $_scopeUserIds = null;

		public static function tableName()
		{
			return 'holon_share_link';
		}

		public static function rules()
		{
			return [
				[['IDorganization', 'IDholon', 'IDuser'], 'required'],
				[['id', 'IDorganization', 'IDholon', 'IDuser'], 'integer'],
				[['label', 'token', 'password_hash'], 'string'],
				[['datecreation', 'dateexpiration'], 'datetime'],
				[['allow_structure', 'allow_people', 'allow_people_detail', 'active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDholon' => 'Holon',
				'IDuser' => 'Createur',
				'label' => 'Libelle',
				'token' => 'Token',
				'password_hash' => 'Mot de passe',
				'allow_structure' => 'Voir la structure',
				'allow_people' => 'Voir les personnes',
				'allow_people_detail' => 'Voir le detail des personnes',
				'datecreation' => 'Date creation',
				'dateexpiration' => 'Date expiration',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDorganization' => 'Organisation a partager.',
				'IDholon' => 'Holon racine du partage.',
				'IDuser' => 'Utilisateur ayant cree le lien.',
				'label' => 'Libelle interne du lien.',
				'token' => 'Token unique partageable.',
				'password_hash' => 'Hash du mot de passe optionnel.',
				'allow_structure' => 'Autorise la lecture de la structure et du detail des holons.',
				'allow_people' => 'Autorise la lecture de la liste des personnes visibles dans le scope partage.',
				'allow_people_detail' => 'Autorise l ouverture du detail d une personne visible dans le scope partage.',
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
			return "datecreation DESC, id DESC";
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
				FROM holon_share_link
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
				FROM holon_share_link
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

		public static function findByIdForContext($shareLinkId, $organizationId, $holonId, $includeInactive = false)
		{
			$shareLinkId = (int)$shareLinkId;
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;

			if ($shareLinkId <= 0 || $organizationId <= 0 || $holonId <= 0) {
				return false;
			}

			$sql = "SELECT *
				FROM holon_share_link
				WHERE id = :id
				  AND IDorganization = :organization_id
				  AND IDholon = :holon_id";

			if (!$includeInactive) {
				$sql .= "
				  AND active = 1";
			}

			$sql .= "
				LIMIT 1";

			$row = self::fetchRow($sql, array(
				'id' => $shareLinkId,
				'organization_id' => $organizationId,
				'holon_id' => $holonId,
			));

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findAllForContext($organizationId, $holonId, $includeInactive = false)
		{
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;

			if ($organizationId <= 0 || $holonId <= 0) {
				return array();
			}

			$sql = "SELECT *
				FROM holon_share_link
				WHERE IDorganization = :organization_id
				  AND IDholon = :holon_id";

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
				'holon_id' => $holonId,
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

		public static function createForHolon(Holon $holon, $userId, array $options = array())
		{
			$userId = (int)$userId;
			$organizationId = (int)$holon->get('IDorganization');
			if ($organizationId <= 0) {
				$rootHolonId = (int)$holon->get('IDholon_org');
				if ($rootHolonId > 0) {
					$rootHolon = new Holon();
					if ($rootHolon->load($rootHolonId)) {
						$organizationId = (int)$rootHolon->get('IDorganization');
					}
				}
			}

			if ($userId <= 0 || (int)$holon->getId() <= 0 || $organizationId <= 0) {
				return false;
			}

			$allowStructure = !empty($options['allow_structure']);
			$allowPeople = !empty($options['allow_people']);
			$allowPeopleDetail = $allowPeople && !empty($options['allow_people_detail']);

			if (!$allowStructure && !$allowPeople && !$allowPeopleDetail) {
				return false;
			}

			$item = new self();
			$item->set('IDorganization', $organizationId);
			$item->set('IDholon', (int)$holon->getId());
			$item->set('IDuser', $userId);
			$item->set('label', trim((string)($options['label'] ?? '')));
			$item->set('token', self::generateUniqueToken());
			$item->set('password_hash', trim((string)($options['password_hash'] ?? '')) ?: null);
			$item->set('allow_structure', $allowStructure ? 1 : 0);
			$item->set('allow_people', $allowPeople ? 1 : 0);
			$item->set('allow_people_detail', $allowPeopleDetail ? 1 : 0);
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

		public function allowsStructure()
		{
			return (bool)$this->get('allow_structure');
		}

		public function allowsPeople()
		{
			return (bool)$this->get('allow_people');
		}

		public function allowsPeopleDetail()
		{
			return $this->allowsPeople() && (bool)$this->get('allow_people_detail');
		}

		public function allowsAnyAccess()
		{
			return $this->allowsStructure() || $this->allowsPeople() || $this->allowsPeopleDetail();
		}

		public function getScopeHolon()
		{
			if ($this->_scopeHolon instanceof Holon) {
				return $this->_scopeHolon;
			}

			$holonId = (int)$this->get('IDholon');
			if ($holonId <= 0) {
				return null;
			}

			$holon = new Holon();
			if (!$holon->load($holonId)) {
				return null;
			}

			$this->_scopeHolon = $holon;
			return $this->_scopeHolon;
		}

		public function canViewOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0 || !$this->allowsAnyAccess()) {
				return false;
			}

			return (int)$this->get('IDorganization') === $organizationId;
		}

		public function canViewHolon(Holon $holon)
		{
			if (!$this->allowsStructure()) {
				return false;
			}

			return $this->containsHolon($holon);
		}

		protected function resolveHolonOrganizationId(Holon $holon)
		{
			$organizationId = (int)$holon->get('IDorganization');
			if ($organizationId > 0) {
				return $organizationId;
			}

			$rootHolonId = (int)$holon->get('IDholon_org');
			if ($rootHolonId <= 0) {
				return 0;
			}

			$rootHolon = new Holon();
			if (!$rootHolon->load($rootHolonId)) {
				return 0;
			}

			return (int)$rootHolon->get('IDorganization');
		}

		public function containsHolon(Holon $holon)
		{
			if ((int)$holon->getId() <= 0) {
				return false;
			}

			if ($this->resolveHolonOrganizationId($holon) !== (int)$this->get('IDorganization')) {
				return false;
			}

			$scopeHolon = $this->getScopeHolon();
			if (!$scopeHolon) {
				return false;
			}

			return $holon->isDescendantOf($scopeHolon->getId(), true);
		}

		protected function loadVisibleScopeUserIds()
		{
			if (is_array($this->_scopeUserIds)) {
				return $this->_scopeUserIds;
			}

			$scopeHolon = $this->getScopeHolon();
			if (!$scopeHolon) {
				$this->_scopeUserIds = array();
				return $this->_scopeUserIds;
			}

			if ((int)$scopeHolon->get('IDtypeholon') === 4) {
				$memberships = new \dbObject\ArrayUserOrganization();
				$memberships->loadVisibleForOrganization((int)$this->get('IDorganization'));
				$userIds = array();
				foreach ($memberships as $membership) {
					$userId = (int)$membership->get('IDuser');
					if ($userId > 0) {
						$userIds[$userId] = $userId;
					}
				}
				$this->_scopeUserIds = array_values($userIds);
				return $this->_scopeUserIds;
			}

			$this->_scopeUserIds = $scopeHolon->getAssociatedMemberUserIds(array(
				'organizationId' => (int)$this->get('IDorganization'),
				'includeDescendants' => true,
				'skipPermissionFilter' => true,
			));

			return $this->_scopeUserIds;
		}

		public function canViewUser(User $user, $requireDetail = false)
		{
			if ((int)$user->getId() <= 0) {
				return false;
			}

			if (!$this->canViewOrganization((int)$this->get('IDorganization'))) {
				return false;
			}

			if ($requireDetail) {
				if (!$this->allowsPeopleDetail()) {
					return false;
				}
			} elseif (!$this->allowsPeople()) {
				return false;
			}

			$membership = $user->getOrganizationMembership((int)$this->get('IDorganization'));
			if (!$membership) {
				return false;
			}

			$userId = (int)$user->getId();
			return in_array($userId, $this->loadVisibleScopeUserIds(), true);
		}

		public function buildShareUrl($cid = null)
		{
			$scopeHolonId = (int)$this->get('IDholon');
			$targetCid = $cid !== null ? (int)$cid : $scopeHolonId;
			$path = '/omo/share.php?token=' . rawurlencode((string)$this->get('token'));
			if ($targetCid > 0) {
				$path .= '&cid=' . $targetCid;
			}

			$organization = new Organization();
			if ($organization->load((int)$this->get('IDorganization'))) {
				$shortname = trim((string)$organization->get('shortname'));
				if (commonUseOrganizationSubdomains() && $shortname !== '') {
					return \commonBuildUrl($path, \commonBuildOrganizationHost($shortname, \commonGetRootHost()));
				}
			}

			return \commonBuildUrl($path, \commonGetRootHost(\commonGetRequestHost()));
		}
	}

?>
