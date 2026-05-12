<?php
	namespace dbObject;

	class user extends DbObject
	{
	    public static function tableName()
		{
			return 'user'; // Nom de la table correspondante
		}

		// DÃ©fini le contenu de la table
		public static function rules()
		{
			return [
				[['email'], 'required'], // Champs obligatoires
				[['id'], 'integer'], // Nombres entiers
				[['username', 'email', 'firstname', 'lastname', 'code', 'telegramID'], 'string'], // ChaÃ®nes de caractÃ¨re
				[['password'], 'password'], // Mot de passe
				[['image'], 'sizedimage'], // Fichier
				[['parameters', 'param_easypv', 'param_easymemo', 'param_easycircle'], 'parameters'], // Textes libres
				[['datecreation', 'dateconnexion', 'codeexpiration'], 'datetime'], // Dates avec heures
				[['active'], 'boolean'], // BoolÃ©ens
				[['id', 'password', 'email', 'code', 'datecreation', 'dateconnexion', 'codeexpiration', 'telegramID'], 'safe'], // Champs protÃ©gÃ©s
			];
		}

		// DÃ©fini les labels standards pour cet objet, affichÃ©s dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'username' => 'Nom d\'utilisateur',
				'firstname' => 'PrÃ©nom',
				'lastname' => 'Nom',
				'email' => 'E-mail',
				'image' => 'Image de profil',
				'telegramID' => 'ID Telegram',
				'password' => 'Mot de passe',
				'code' => 'Code',
				'parameters' => 'ParamÃ¨tres',
			];
		}

		// Ajoute un champ description, qui peut apparaÃ®tre sous forme de bulle d'information ou en sous-titre
		public static function attributeDescriptions() {
			return [
				'username' => 'Un identifiant utilisÃ© pour vous identifier dans une Ã©quipe, comme des initiales.',
				'firstname' => 'Simplement votre prÃ©nom.',
				'lastname' => 'Simplement votre nom de famille.',
				'email' => 'L\'adresse e-mail utilisÃ©e pour vous connecter et pour vous envoyer les messages du systÃ¨me.',
				'telegramID' => 'Identifiant numÃ©rique utilisÃ© pour associer votre compte Telegram.',
			];
		}

		// DÃ©fini les informations de taille pour le champ
		public static function attributeLength() {
			return [
				'username' => 30, // Nombre de caractÃ¨res maximum
				'firstname' => 25,
				'lastname' => 25,
				'email' => 30,
				'telegramID' => 100,
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "firstname, lastname";
		}

		// Retourne un boolean indiquant si oui ou non l'utilisateur connectÃ© a le droit d'afficher ce contenu
		public function canView() {
			return $this->resolveViewPermission(false);
		}

		public function canViewDetail() {
			return $this->resolveViewPermission(true);
		}

		// Retourne un boolean indiquant si oui ou non l'utilisateur connectÃ© a le droit d'Ã©diter ce contenu
		public function canEdit() {
			if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"] == $this->getId()) {
				return true;
			}

			// Par dÃ©faut, ne peut complÃ©ter que son profil. A complÃ©ter lorsque des users fantÃ´mes seront crÃ©Ã©s.
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
