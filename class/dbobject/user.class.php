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
				[['latlong'], 'latlong'],
				[['password'], 'password'],
				[['image'], 'sizedimage'],
				[['parameters', 'param_easypv', 'param_easymemo', 'param_easycircle'], 'parameters'],
				[['datecreation', 'dateconnexion', 'codeexpiration'], 'datetime'],
				[['birthdate'], 'date'],
				[['active', 'siteadmin'], 'boolean'],
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
				'latlong' => 'Position geographique',
				'birthdate' => 'Date de naissance',
				'email' => 'E-mail',
				'image' => 'Image de profil',
				'telegramID' => 'ID Telegram',
				'password' => 'Mot de passe',
				'siteadmin' => 'Admin du site',
				'code' => 'Code',
				'parameters' => 'Parametres',
			];
		}

		public static function attributeDescriptions() {
			return [
				'username' => 'Un nom d\'utilisateur utilise pour vous identifier dans une equipe, comme des initiales.',
				'firstname' => 'Simplement votre prenom.',
				'lastname' => 'Simplement votre nom de famille.',
				'presentation' => 'Petit texte de presentation partage entre les organisations, sauf si une organisation le remplace localement.',
				'latlong' => 'Position geographique generale, partagee dans toutes les organisations.',
				'birthdate' => 'Date de naissance facultative, utilisee pour afficher le prochain anniversaire.',
				'email' => 'L\'adresse e-mail utilisee pour vous connecter et pour vous envoyer les messages du systeme.',
				'telegramID' => 'Identifiant numerique utilise pour associer votre compte Telegram.',
				'siteadmin' => 'Donne un acces global a l administration du serveur.',
			];
		}

		public static function attributeLength() {
			return [
				'username' => 30,
				'firstname' => 25,
				'lastname' => 25,
				'presentation' => 2000,
				'latlong' => 100,
				'email' => 30,
				'telegramID' => 100,
				'image' => [[320, 320], [160, 160]],
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

		public static function buildHistoricalPlaceholderEmail($organizationId, $sourceUserId)
		{
			return 'h-' . substr(sha1((int)$organizationId . ':' . (int)$sourceUserId), 0, 16) . '@invalid';
		}

		public static function getOrCreateHistoricalPlaceholder($organizationId, \dbObject\User $sourceUser)
		{
			$organizationId = (int)$organizationId;
			$sourceUserId = (int)$sourceUser->getId();
			if ($organizationId <= 0 || $sourceUserId <= 0) {
				throw new \RuntimeException('Le compte historique demande est invalide.');
			}

			$email = self::buildHistoricalPlaceholderEmail($organizationId, $sourceUserId);
			$placeholder = new self();
			if ($placeholder->load(array(array('email', $email)))) {
				return $placeholder;
			}

			$placeholder->set('email', $email);
			$placeholder->set('firstname', (string)$sourceUser->get('firstname'));
			$placeholder->set('lastname', (string)$sourceUser->get('lastname'));
			$placeholder->set('username', (string)$sourceUser->get('username'));
			$placeholder->set('active', false);
			$placeholder->set('siteadmin', false);
			$placeholder->set('parameters', array('historical_placeholder' => true));
			$saveResult = $placeholder->save();
			if (!is_array($saveResult) || empty($saveResult['status']) || (int)$placeholder->getId() <= 0) {
				throw new \RuntimeException('Le compte historique n a pas pu etre cree.');
			}

			return $placeholder;
		}

		public function isHistoricalPlaceholder()
		{
			return !empty($this->getParameter('historical_placeholder'));
		}

		public function canEdit() {
			if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"] == $this->getId()) {
				return true;
			}

			return false;
		}

		public function isSiteAdmin()
		{
			$siteAdmin = $this->get('siteadmin');
			if ($siteAdmin !== null && $siteAdmin !== '') {
				return (bool)$siteAdmin;
			}

			return (bool)$this->getParameter('isSiteAdmin');
		}

		public function setSiteAdmin($isSiteAdmin)
		{
			$this->set('siteadmin', $isSiteAdmin ? 1 : 0);

			$parameters = json_decode((string)$this->get('parameters'), true);
			if (!is_array($parameters)) {
				$parameters = array();
			}

			unset($parameters['isSiteAdmin']);

			$this->set('parameters', $parameters);
			return $this->save();
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

		public function getPendingOrganizationInvitations()
		{
			return \dbObject\Invitation::findPendingForUser((int)$this->getId());
		}

		public static function findByLoginIdentifier($identifier)
		{
			$normalizedIdentifier = trim(mb_strtolower((string)$identifier, 'UTF-8'));
			if ($normalizedIdentifier === '') {
				return null;
			}

			$query = "
				SELECT
					u.id
				FROM `user` u
				WHERE LOWER(u.email) = :identity
				ORDER BY u.id ASC
			";

			$rows = self::fetchAll($query, array(
				'identity' => $normalizedIdentifier,
			));

			if (!is_array($rows) || count($rows) === 0) {
				return null;
			}

			$matchedUserIds = array();

			foreach ($rows as $row) {
				$userId = (int)($row['id'] ?? 0);
				if ($userId <= 0) {
					continue;
				}

				$matchedUserIds[$userId] = $userId;
			}

			if (count($matchedUserIds) !== 1) {
				return null;
			}

			$userId = (int)reset($matchedUserIds);
			if ($userId <= 0) {
				return null;
			}

			$user = new self();
			return $user->load($userId) ? $user : null;
		}

		public static function debugLoginIdentifierMatchSummary($identifier)
		{
			$normalizedIdentifier = trim(mb_strtolower((string)$identifier, 'UTF-8'));
			$summary = array(
				'normalizedIdentifier' => $normalizedIdentifier,
				'globalEmailMatches' => 0,
				'organizationEmailMatches' => 0,
				'resolvedUserIds' => array(),
			);

			if ($normalizedIdentifier === '') {
				return $summary;
			}

			$rows = self::fetchAll(
				"SELECT
					u.id,
					1 AS global_email_match
				FROM `user` u
				WHERE LOWER(u.email) = :identity
				ORDER BY u.id ASC",
				array(
					'identity' => $normalizedIdentifier,
				)
			);

			if (!is_array($rows)) {
				return $summary;
			}

			$userIds = array();
			foreach ($rows as $row) {
				$userId = (int)($row['id'] ?? 0);
				if ($userId > 0) {
					$userIds[$userId] = $userId;
				}

				$summary['globalEmailMatches'] += (int)($row['global_email_match'] ?? 0);
			}

			$summary['resolvedUserIds'] = array_values($userIds);
			return $summary;
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
			if ($this->isHistoricalPlaceholder()) {
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

			$currentOrganizationId = function_exists('commonGetCurrentUserOrganizationId')
				? (int)\commonGetCurrentUserOrganizationId()
				: (int)($_SESSION['currentOrganization'] ?? 0);
			if (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($currentUserId, $currentOrganizationId)) {
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

		public function getSharedOrganizationMembershipsForViewer($viewerUserId)
		{
			$memberships = new \dbObject\ArrayUserOrganization();
			$memberships->loadCardDavSharedForViewerAndUser((int)$viewerUserId, (int)$this->getId());
			return $memberships;
		}

		public static function buildInitials($label, $fallback = 'P')
		{
			$label = trim((string)$label);
			$fallback = trim((string)$fallback);
			if ($fallback === '') {
				$fallback = 'P';
			}

			if ($label === '') {
				$label = $fallback;
			}

			$initials = '';
			$tokens = preg_split('/[\s\.\-_@]+/u', $label) ?: array();

			foreach ($tokens as $token) {
				$token = trim((string)$token);
				if ($token === '') {
					continue;
				}

				if (function_exists('mb_substr')) {
					$initials .= mb_substr($token, 0, 1, 'UTF-8');
					if (mb_strlen($initials, 'UTF-8') >= 2) {
						break;
					}
				} else {
					$initials .= substr($token, 0, 1);
					if (strlen($initials) >= 2) {
						break;
					}
				}
			}

			$collapsed = preg_replace('/[\s\.\-_@]+/u', '', $label);
			if (!is_string($collapsed)) {
				$collapsed = '';
			}

			if (function_exists('mb_strlen') && function_exists('mb_substr')) {
				$collapsedLength = mb_strlen($collapsed, 'UTF-8');
				$offset = mb_strlen($initials, 'UTF-8');
				while ($collapsed !== '' && mb_strlen($initials, 'UTF-8') < 2 && $offset < $collapsedLength) {
					$initials .= mb_substr($collapsed, $offset, 1, 'UTF-8');
					$offset++;
				}
			} else {
				$collapsedLength = strlen($collapsed);
				$offset = strlen($initials);
				while ($collapsed !== '' && strlen($initials) < 2 && $offset < $collapsedLength) {
					$initials .= substr($collapsed, $offset, 1);
					$offset++;
				}
			}

			if ($initials === '') {
				$initials = $fallback;
			}

			return function_exists('mb_strtoupper')
				? mb_strtoupper($initials, 'UTF-8')
				: strtoupper($initials);
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

		public function getScopedInitials($organizationId = 0)
		{
			$membership = $this->getOrganizationMembership($organizationId);
			if ($membership && method_exists($membership, 'getUserInitials')) {
				return $membership->getUserInitials();
			}

			return self::buildInitials($this->getScopedDisplayName($organizationId));
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
