<?php
	namespace dbObject;

	class UserHolon extends DbObject
	{
		protected $_scopedMembershipCache = array();
		protected $_linkedUserCache = null;

	    public static function tableName()
		{
			return 'user_holon';
		}

		public static function rules()
		{
			return [
				[['IDuser', 'IDholon'], 'required'],
				[['id'], 'integer'],
				[['IDuser', 'IDholon'], 'fk'],
				[['parameters'], 'parameters'],
				[['datecreation', 'dateconnexion'], 'datetime'],
				[['active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Personne',
				'IDholon' => 'Holon',
				'parameters' => 'Paramètres',
				'datecreation' => 'Création',
				'dateconnexion' => 'Dernière connexion',
				'active' => 'Actif',
			];
		}

		protected function loadScopedMembership($organizationId = 0)
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$this->get('IDuser');
			if ($organizationId <= 0 || $userId <= 0) {
				return null;
			}

			if (array_key_exists($organizationId, $this->_scopedMembershipCache)) {
				return $this->_scopedMembershipCache[$organizationId];
			}

			$membership = new \dbObject\UserOrganization();
			$this->_scopedMembershipCache[$organizationId] = $membership->load([
				['IDuser', $userId],
				['IDorganization', $organizationId],
			]) ? $membership : null;

			return $this->_scopedMembershipCache[$organizationId];
		}

		protected function loadLinkedUser()
		{
			if ($this->_linkedUserCache instanceof \dbObject\User) {
				return $this->_linkedUserCache;
			}

			$user = $this->get('user');
			if ($user && (int)$user->getId() > 0) {
				$this->_linkedUserCache = $user;
				return $this->_linkedUserCache;
			}

			$userId = (int)$this->get('IDuser');
			if ($userId <= 0) {
				return null;
			}

			$user = new \dbObject\User();
			$this->_linkedUserCache = $user->load($userId) ? $user : null;
			return $this->_linkedUserCache;
		}

		public function getUserDisplayName($organizationId = 0)
		{
			$membership = $this->loadScopedMembership($organizationId);
			if ($membership) {
				return $membership->getUserDisplayName();
			}

			$user = $this->loadLinkedUser();
			if (!$user) {
				return 'Profil';
			}

			$firstname = trim((string)$user->get('firstname'));
			$lastname = trim((string)$user->get('lastname'));
			$fullName = trim($firstname . ' ' . $lastname);
			if ($fullName !== '') {
				return $fullName;
			}

			$username = trim((string)$user->get('username'));
			if ($username !== '') {
				return $username;
			}

			$email = trim((string)$user->get('email'));
			if ($email !== '') {
				return $email;
			}

			return 'Profil';
		}

		public function getUserInitials($organizationId = 0)
		{
			$membership = $this->loadScopedMembership($organizationId);
			if ($membership) {
				return $membership->getUserInitials();
			}

			$label = $this->getUserDisplayName($organizationId);
			$words = preg_split('/\s+/u', $label) ?: [];
			$initials = '';

			foreach ($words as $word) {
				$word = trim((string)$word);
				if ($word === '') {
					continue;
				}

				$initials .= mb_substr($word, 0, 1, 'UTF-8');
				if (mb_strlen($initials, 'UTF-8') >= 2) {
					break;
				}
			}

			if ($initials === '') {
				$initials = mb_substr($label, 0, 1, 'UTF-8');
			}

			return mb_strtoupper($initials !== '' ? $initials : 'P', 'UTF-8');
		}

		public function getProfilePhotoUrl($organizationId = 0)
		{
			$membership = $this->loadScopedMembership($organizationId);
			if ($membership) {
				return $membership->getProfilePhotoUrl();
			}

			$user = $this->loadLinkedUser();
			if ($user && method_exists($user, 'getProfilePhotoUrl')) {
				return (string)$user->getProfilePhotoUrl();
			}

			return '';
		}

		public function isHolonAdmin()
		{
			return (bool)$this->getParameter('isAdmin');
		}

		public function setHolonAdmin($isAdmin)
		{
			$parameters = json_decode((string)$this->get('parameters'), true);
			if (!is_array($parameters)) {
				$parameters = array();
			}

			if ($isAdmin) {
				$parameters['isAdmin'] = true;
			} else {
				unset($parameters['isAdmin']);
			}

			$this->set('parameters', $parameters);
			return $this->save();
		}

		public static function fetchEffectiveRowsForUserAndHolonIds($userId, array $holonIds)
		{
			$userId = (int)$userId;
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));

			if ($userId <= 0 || count($holonIds) === 0) {
				return array();
			}

			$params = array(
				'user_id' => $userId,
			);
			$placeholders = array();
			foreach ($holonIds as $index => $holonId) {
				$placeholder = 'holon_' . $index;
				$placeholders[] = ':' . $placeholder;
				$params[$placeholder] = $holonId;
			}

			$query = "
				SELECT DISTINCT
					uh.IDholon,
					uh.active AS holon_active,
					uh.active AS holon_effective_active
				FROM user_holon uh
				WHERE uh.IDuser = :user_id
				  AND uh.IDholon IN (" . implode(', ', $placeholders) . ")
				  AND uh.active = 1
				ORDER BY uh.IDholon ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			return $rows !== false ? $rows : array();
		}

		public static function fetchRawRowsForUserAndHolonIds($userId, array $holonIds)
		{
			$userId = (int)$userId;
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));

			if ($userId <= 0 || count($holonIds) === 0) {
				return array();
			}

			$params = array(
				'user_id' => $userId,
			);
			$placeholders = array();
			foreach ($holonIds as $index => $holonId) {
				$placeholder = 'holon_' . $index;
				$placeholders[] = ':' . $placeholder;
				$params[$placeholder] = $holonId;
			}

			$query = "
				SELECT
					id,
					IDholon,
					active,
					parameters,
					datecreation,
					dateconnexion
				FROM user_holon
				WHERE IDuser = :user_id
				  AND IDholon IN (" . implode(', ', $placeholders) . ")
				ORDER BY IDholon ASC, id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			return $rows !== false ? $rows : array();
		}
	}

?>
