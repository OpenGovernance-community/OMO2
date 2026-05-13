<?php
	namespace dbObject;

	class user extends DbObject
	{
	    public static function tableName()
		{
			return 'user';
		}

		public static function rules()
		{
			return [
				[['email'], 'required'],
				[['id'], 'integer'],
				[['username', 'email', 'firstname', 'lastname', 'code', 'telegramID'], 'string'],
				[['presentation'], 'text'],
				[['password'], 'password'],
				[['image'], 'sizedimage'],
				[['parameters', 'param_easypv', 'param_easymemo', 'param_easycircle'], 'parameters'],
				[['datecreation', 'dateconnexion', 'codeexpiration'], 'datetime'],
				[['birthdate'], 'date'],
				[['active'], 'boolean'],
				[['id', 'password', 'email', 'code', 'datecreation', 'dateconnexion', 'codeexpiration', 'telegramID'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'username' => 'Nom d\'utilisateur',
				'firstname' => 'Prenom',
				'lastname' => 'Nom',
				'presentation' => 'Presentation',
				'birthdate' => 'Date de naissance',
				'email' => 'E-mail',
				'image' => 'Image de profil',
				'telegramID' => 'ID Telegram',
				'password' => 'Mot de passe',
				'code' => 'Code',
				'parameters' => 'Parametres',
			];
		}

		public static function attributeDescriptions() {
			return [
				'username' => 'Un identifiant utilise pour vous identifier dans une equipe, comme des initiales.',
				'firstname' => 'Simplement votre prenom.',
				'lastname' => 'Simplement votre nom de famille.',
				'presentation' => 'Petit texte de presentation partage entre les organisations, sauf si une organisation le remplace localement.',
				'birthdate' => 'Date de naissance facultative, utilisee pour afficher le prochain anniversaire.',
				'email' => 'L\'adresse e-mail utilisee pour vous connecter et pour vous envoyer les messages du systeme.',
				'telegramID' => 'Identifiant numerique utilise pour associer votre compte Telegram.',
			];
		}

		public static function attributeLength() {
			return [
				'username' => 30,
				'firstname' => 25,
				'lastname' => 25,
				'presentation' => 2000,
				'email' => 30,
				'telegramID' => 100,
			];
		}

		public static function getOrder() {
			return "firstname, lastname";
		}

		public function canView() {
			return $this->resolveViewPermission(false);
		}

		public function canViewDetail() {
			return $this->resolveViewPermission(true);
		}

		public function canEdit() {
			if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"] == $this->getId()) {
				return true;
			}

			return false;
		}

		public function getPrompt() {
			$prompts = new \dbObject\ArrayAIPrompt();
			$prompts->load([
				"whereAny" => [
					["field" => "IDuser", "value" => $this->get("id")],
					["field" => "ispublic", "value" => 1],
				],
			]);
			return $prompts;
		}

		public function getAccessibleOrganizations() {
			$organizations = new ArrayOrganization();
			$organizations->loadAccessibleForUser($this->getId());
			return $organizations;
		}

		protected static function loadActiveOrganizationIdsForUser($userId)
		{
			static $cache = array();

			$userId = (int)$userId;
			if ($userId <= 0) {
				return array();
			}

			if (array_key_exists($userId, $cache)) {
				return $cache[$userId];
			}

			$rows = self::fetchAll(
				"SELECT IDorganization
				FROM user_organization
				WHERE IDuser = :user_id
				  AND active = 1
				ORDER BY IDorganization ASC",
				array(
					'user_id' => $userId,
				)
			);

			if ($rows === false) {
				$cache[$userId] = array();
				return $cache[$userId];
			}

			$organizationIds = array();
			foreach ($rows as $row) {
				$organizationId = (int)($row['IDorganization'] ?? 0);
				if ($organizationId > 0) {
					$organizationIds[$organizationId] = $organizationId;
				}
			}

			$cache[$userId] = array_values($organizationIds);

			return $cache[$userId];
		}

		protected function getActiveOrganizationIds()
		{
			return self::loadActiveOrganizationIdsForUser((int)$this->getId());
		}

		protected function resolveViewPermission($requireDetail = false)
		{
			static $cache = array();

			$targetUserId = (int)$this->getId();
			if ($targetUserId <= 0) {
				return false;
			}

			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION["currentUser"] ?? 0);
			$shareToken = function_exists('commonGetCurrentShareToken')
				? (string)\commonGetCurrentShareToken()
				: '';
			$cacheKey = $targetUserId . ':' . $currentUserId . ':' . ($requireDetail ? '1' : '0') . ':' . $shareToken;

			if (array_key_exists($cacheKey, $cache)) {
				return $cache[$cacheKey];
			}

			if ($currentUserId > 0 && $currentUserId === $targetUserId) {
				$cache[$cacheKey] = true;
				return true;
			}

			$targetOrganizationIds = $this->getActiveOrganizationIds();
			if (count($targetOrganizationIds) > 0) {
				$currentOrganizationIds = self::loadActiveOrganizationIdsForUser($currentUserId);
				if (count(array_intersect($targetOrganizationIds, $currentOrganizationIds)) > 0) {
					$cache[$cacheKey] = true;
					return true;
				}
			}

			if (function_exists('commonCurrentShareCanViewUser')) {
				$cache[$cacheKey] = \commonCurrentShareCanViewUser($this, $requireDetail);
				return $cache[$cacheKey];
			}

			$cache[$cacheKey] = false;
			return false;
		}

		public function getOrganizationMembership($organizationId = 0)
		{
			static $cache = array();

			$organizationId = (int)$organizationId;
			$userId = (int)$this->getId();
			if ($userId <= 0 || $organizationId <= 0) {
				return null;
			}

			$cacheKey = $userId . ':' . $organizationId;
			if (array_key_exists($cacheKey, $cache)) {
				return $cache[$cacheKey] ?: null;
			}

			$membership = new \dbObject\UserOrganization();
			$cache[$cacheKey] = $membership->load([
				['IDuser', $userId],
				['IDorganization', $organizationId],
			]) ? $membership : false;

			return $cache[$cacheKey] ?: null;
		}

		public function getProfilePhotoUrl()
		{
			$image = trim((string)$this->get('image'));
			if ($image !== '') {
				return $image;
			}

			return '';
		}

		public function getScopedProfilePhotoUrl($organizationId = 0)
		{
			$membership = $this->getOrganizationMembership($organizationId);
			if ($membership) {
				return $membership->getProfilePhotoUrl();
			}

			return $this->getProfilePhotoUrl();
		}

		public function getScopedUsername($organizationId = 0)
		{
			$membership = $this->getOrganizationMembership($organizationId);
			if ($membership) {
				return $membership->getScopedUsername();
			}

			return trim((string)$this->get('username'));
		}

		public function getScopedEmail($organizationId = 0)
		{
			$membership = $this->getOrganizationMembership($organizationId);
			if ($membership) {
				return $membership->getScopedEmail();
			}

			return trim((string)$this->get('email'));
		}

		public function getScopedDisplayName($organizationId = 0)
		{
			$fullName = trim((string)$this->get('firstname') . ' ' . (string)$this->get('lastname'));
			if ($fullName !== '') {
				return $fullName;
			}

			$username = $this->getScopedUsername($organizationId);
			if ($username !== '') {
				return $username;
			}

			return $this->getScopedEmail($organizationId);
		}

		public function getScopedPresentation($organizationId = 0)
		{
			$membership = $this->getOrganizationMembership($organizationId);
			if ($membership && method_exists($membership, 'getScopedPresentation')) {
				return $membership->getScopedPresentation();
			}

			return trim((string)$this->get('presentation'));
		}

		public function hasOrganizationAccess($organizationId) {
			$organizationId = (int)$organizationId;
			if ((int)$this->getId() <= 0 || $organizationId <= 0) {
				return false;
			}

			if (function_exists('commonUserHasOrganizationMembership')) {
				return \commonUserHasOrganizationMembership((int)$this->getId(), $organizationId);
			}

			$organizations = new ArrayOrganization();
			$organizations->loadAccessibleForUser($this->getId(), $organizationId, 1);
			return count($organizations) > 0;
		}

		public function getVisibleCompetenceRows($organizationId = 0, $viewerUserId = 0)
		{
			return \dbObject\UserCompetence::buildVisibleCompetenceRows((int)$this->getId(), (int)$organizationId, (int)$viewerUserId);
		}

		public function getCompetenceRowsForScope($scope = 'general', $organizationId = 0, $viewerUserId = 0)
		{
			$scope = $scope === 'organization' ? 'organization' : 'general';
			$rows = $this->getVisibleCompetenceRows($organizationId, $viewerUserId);

			return array_values(array_filter($rows, static function ($row) use ($scope) {
				return (string)($row['scope'] ?? 'general') === $scope;
			}));
		}

		public function saveCompetenceDeclaration(array $payload, $currentOrganizationId = 0)
		{
			if (!$this->canEdit()) {
				return [
					'status' => false,
					'message' => "Vous ne pouvez pas modifier ces competences.",
				];
			}

			return \dbObject\UserCompetence::saveDeclarationForUser((int)$this->getId(), $payload, (int)$currentOrganizationId);
		}

		public function deleteCompetenceDeclaration($userCompetenceId)
		{
			if (!$this->canEdit()) {
				return [
					'status' => false,
					'message' => "Vous ne pouvez pas supprimer ces competences.",
				];
			}

			return \dbObject\UserCompetence::deleteDeclarationForUser((int)$userCompetenceId, (int)$this->getId());
		}
	}

?>
