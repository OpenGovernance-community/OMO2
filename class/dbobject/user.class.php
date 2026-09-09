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
				[['active', 'siteadmin', 'allow_password_login', 'totp_enabled'], 'boolean'],
				[['id', 'password', 'email', 'code', 'datecreation', 'dateconnexion', 'codeexpiration', 'telegramID', 'allow_password_login', 'totp_enabled', 'totp_secret'], 'safe'],
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
				'allow_password_login' => 'Autoriser le login avec mot de passe',
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

		public function allowsPasswordLogin()
		{
			return trim((string)$this->get('password')) !== ''
				&& (bool)$this->get('allow_password_login');
		}

		public function hasTotpEnabled()
		{
			return (bool)$this->get('totp_enabled')
				&& trim((string)$this->get('totp_secret')) !== '';
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

		public static function isOrganizationEmailInUse($identifier)
		{
			$normalizedIdentifier = trim(mb_strtolower((string)$identifier, 'UTF-8'));
			if ($normalizedIdentifier === '') {
				return false;
			}

			return (int)self::fetchValue(
				"SELECT COUNT(DISTINCT membership.IDuser)
				 FROM user_organization membership
				 INNER JOIN `user` owner ON owner.id = membership.IDuser
				 WHERE NULLIF(TRIM(membership.email), '') IS NOT NULL
				   AND LOWER(TRIM(membership.email)) = :identity",
				array('identity' => $normalizedIdentifier)
			) > 0;
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

		private static function accountMergeExecute(\PDO $pdo, $query, array $parameters = array())
		{
			$statement = $pdo->prepare($query);
			$statement->execute($parameters);
			$affectedRows = (int)$statement->rowCount();
			$statement->closeCursor();

			return $affectedRows;
		}

		private static function accountMergeFetchAll(\PDO $pdo, $query, array $parameters = array())
		{
			$statement = $pdo->prepare($query);
			$statement->execute($parameters);
			$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
			$statement->closeCursor();

			return is_array($rows) ? $rows : array();
		}

		private static function accountMergeQuoteIdentifier($identifier)
		{
			$identifier = (string)$identifier;
			if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
				throw new \RuntimeException('Identifiant SQL invalide pendant la fusion.');
			}

			return '`' . $identifier . '`';
		}

		private static function accountMergeParameters($keptValue, $removedValue)
		{
			$kept = json_decode((string)$keptValue, true);
			$removed = json_decode((string)$removedValue, true);
			$kept = is_array($kept) ? $kept : array();
			$removed = is_array($removed) ? $removed : array();

			if (count($kept) === 0 && count($removed) === 0) {
				return null;
			}

			return json_encode(array_replace_recursive($removed, $kept), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		private static function accountMergeFillMissing($keptValue, $removedValue)
		{
			return trim((string)$keptValue) !== '' ? $keptValue : $removedValue;
		}

		private static function accountMergeOrganizationIds(\PDO $pdo, $keptUserId, $removedUserId)
		{
			$rows = self::accountMergeFetchAll(
				$pdo,
				'SELECT DISTINCT IDorganization
				 FROM user_organization
				 WHERE IDuser IN (:kept_user_id, :removed_user_id)
				   AND IDorganization IS NOT NULL
				   AND IDorganization > 0',
				array(
					'kept_user_id' => (int)$keptUserId,
					'removed_user_id' => (int)$removedUserId,
				)
			);

			return array_values(array_unique(array_filter(array_map(
				static function ($row) {
					return (int)($row['IDorganization'] ?? 0);
				},
				$rows
			))));
		}

		private static function accountMergeUserReference(array $userRow)
		{
			$label = trim(implode(' ', array_filter(array(
				trim((string)($userRow['firstname'] ?? '')),
				trim((string)($userRow['lastname'] ?? '')),
			))));
			if ($label === '') {
				$label = trim((string)($userRow['username'] ?? ''));
			}
			if ($label === '') {
				$label = trim((string)($userRow['email'] ?? ''));
			}
			if ($label === '') {
				$label = 'Compte #' . (int)($userRow['id'] ?? 0);
			}

			return History::buildReferenceToken('user', (int)($userRow['id'] ?? 0), $label);
		}

		private static function accountMergeMembershipRows(\PDO $pdo, $keptUserId, $removedUserId, $removedEmail, array &$summary)
		{
			$removedEmail = trim((string)$removedEmail);
			$organizationPairs = self::accountMergeFetchAll(
				$pdo,
				"SELECT source.*, target.id AS target_id,
					target.username AS target_username, target.image AS target_image,
					target.email AS target_email, target.presentation AS target_presentation,
					target.latlong AS target_latlong, target.parameters AS target_parameters,
					target.datecreation AS target_datecreation, target.dateconnexion AS target_dateconnexion,
					target.active AS target_active
				 FROM user_organization source
				 INNER JOIN user_organization target
					ON target.IDuser = :kept_user_id
				   AND target.IDorganization = source.IDorganization
				   AND target.id = (
						SELECT MIN(candidate.id)
						FROM user_organization candidate
						WHERE candidate.IDuser = :kept_user_id_lookup
						  AND candidate.IDorganization = source.IDorganization
				   )
				 WHERE source.IDuser = :removed_user_id",
				array(
					'kept_user_id' => $keptUserId,
					'kept_user_id_lookup' => $keptUserId,
					'removed_user_id' => $removedUserId,
				)
			);

			foreach ($organizationPairs as $row) {
				$sourceOrganizationEmail = self::accountMergeFillMissing($row['email'] ?? '', $removedEmail);
				self::accountMergeExecute(
					$pdo,
					"UPDATE user_organization
					 SET username = :username, image = :image, email = :email,
						 presentation = :presentation, latlong = :latlong, parameters = :parameters,
						 datecreation = LEAST(datecreation, :source_datecreation),
						 dateconnexion = CASE
							 WHEN dateconnexion IS NULL THEN :source_dateconnexion
							 WHEN :source_dateconnexion_compare IS NULL THEN dateconnexion
							 ELSE GREATEST(dateconnexion, :source_dateconnexion_latest)
						 END,
						 active = GREATEST(active, :source_active)
					 WHERE id = :target_id",
					array(
						'username' => self::accountMergeFillMissing($row['target_username'] ?? '', $row['username'] ?? null),
						'image' => self::accountMergeFillMissing($row['target_image'] ?? '', $row['image'] ?? null),
						'email' => self::accountMergeFillMissing($row['target_email'] ?? '', $sourceOrganizationEmail),
						'presentation' => self::accountMergeFillMissing($row['target_presentation'] ?? '', $row['presentation'] ?? null),
						'latlong' => self::accountMergeFillMissing($row['target_latlong'] ?? '', $row['latlong'] ?? null),
						'parameters' => self::accountMergeParameters($row['target_parameters'] ?? null, $row['parameters'] ?? null),
						'source_datecreation' => $row['datecreation'],
						'source_dateconnexion' => $row['dateconnexion'],
						'source_dateconnexion_compare' => $row['dateconnexion'],
						'source_dateconnexion_latest' => $row['dateconnexion'],
						'source_active' => (int)$row['active'],
						'target_id' => (int)$row['target_id'],
					)
				);
				self::accountMergeExecute($pdo, 'DELETE FROM user_organization WHERE id = :id', array('id' => (int)$row['id']));
				$summary['deduplicated']++;
			}

			if ($removedEmail !== '') {
				self::accountMergeExecute(
					$pdo,
					"UPDATE user_organization
					 SET email = :removed_email
					 WHERE IDuser = :removed_user_id
					   AND (email IS NULL OR TRIM(email) = '')",
					array('removed_email' => $removedEmail, 'removed_user_id' => $removedUserId)
				);
			}

			$holonPairs = self::accountMergeFetchAll(
				$pdo,
				"SELECT source.*, target.id AS target_id,
					target.parameters AS target_parameters, target.focus AS target_focus,
					target.time_budget_hours AS target_time_budget_hours,
					target.time_budget_recurrence AS target_time_budget_recurrence,
					target.money_budget AS target_money_budget,
					target.money_budget_recurrence AS target_money_budget_recurrence,
					target.assignment_review_date AS target_assignment_review_date,
					target.datecreation AS target_datecreation, target.dateconnexion AS target_dateconnexion,
					target.active AS target_active, target.is_membership AS target_is_membership
				 FROM user_holon source
				 INNER JOIN user_holon target
					ON target.IDuser = :kept_user_id
				   AND target.IDholon = source.IDholon
				   AND target.id = (
						SELECT MIN(candidate.id)
						FROM user_holon candidate
						WHERE candidate.IDuser = :kept_user_id_lookup
						  AND candidate.IDholon = source.IDholon
				   )
				 WHERE source.IDuser = :removed_user_id",
				array(
					'kept_user_id' => $keptUserId,
					'kept_user_id_lookup' => $keptUserId,
					'removed_user_id' => $removedUserId,
				)
			);

			foreach ($holonPairs as $row) {
				self::accountMergeExecute(
					$pdo,
					"UPDATE user_holon
					 SET parameters = :parameters, focus = :focus,
						 time_budget_hours = :time_budget_hours,
						 time_budget_recurrence = :time_budget_recurrence,
						 money_budget = :money_budget,
						 money_budget_recurrence = :money_budget_recurrence,
						 assignment_review_date = :assignment_review_date,
						 datecreation = LEAST(datecreation, :source_datecreation),
						 dateconnexion = CASE
							 WHEN dateconnexion IS NULL THEN :source_dateconnexion
							 WHEN :source_dateconnexion_compare IS NULL THEN dateconnexion
							 ELSE GREATEST(dateconnexion, :source_dateconnexion_latest)
						 END,
						 active = GREATEST(active, :source_active),
						 is_membership = GREATEST(is_membership, :source_is_membership)
					 WHERE id = :target_id",
					array(
						'parameters' => self::accountMergeParameters($row['target_parameters'] ?? null, $row['parameters'] ?? null),
						'focus' => self::accountMergeFillMissing($row['target_focus'] ?? '', $row['focus'] ?? null),
						'time_budget_hours' => self::accountMergeFillMissing($row['target_time_budget_hours'] ?? '', $row['time_budget_hours'] ?? null),
						'time_budget_recurrence' => self::accountMergeFillMissing($row['target_time_budget_recurrence'] ?? '', $row['time_budget_recurrence'] ?? null),
						'money_budget' => self::accountMergeFillMissing($row['target_money_budget'] ?? '', $row['money_budget'] ?? null),
						'money_budget_recurrence' => self::accountMergeFillMissing($row['target_money_budget_recurrence'] ?? '', $row['money_budget_recurrence'] ?? null),
						'assignment_review_date' => self::accountMergeFillMissing($row['target_assignment_review_date'] ?? '', $row['assignment_review_date'] ?? null),
						'source_datecreation' => $row['datecreation'],
						'source_dateconnexion' => $row['dateconnexion'],
						'source_dateconnexion_compare' => $row['dateconnexion'],
						'source_dateconnexion_latest' => $row['dateconnexion'],
						'source_active' => (int)$row['active'],
						'source_is_membership' => (int)$row['is_membership'],
						'target_id' => (int)$row['target_id'],
					)
				);
				self::accountMergeExecute($pdo, 'DELETE FROM user_holon WHERE id = :id', array('id' => (int)$row['id']));
				$summary['deduplicated']++;
			}
		}

		private static function accountMergeLearningRows(\PDO $pdo, $keptUserId, $removedUserId, array &$summary)
		{
			$competencePairs = self::accountMergeFetchAll(
				$pdo,
				"SELECT source.id AS source_id, target.id AS target_id,
					source.level AS source_level, source.description AS source_description,
					target.level AS target_level, target.description AS target_description
				 FROM user_competence source
				 INNER JOIN user_competence target
					ON target.IDuser = :kept_user_id
				   AND target.IDcompetence = source.IDcompetence
				   AND target.IDorganization <=> source.IDorganization
				 WHERE source.IDuser = :removed_user_id",
				array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
			);

			foreach ($competencePairs as $row) {
				$sourceId = (int)$row['source_id'];
				$targetId = (int)$row['target_id'];
				self::accountMergeExecute(
					$pdo,
					"DELETE source_validation
					 FROM user_competence_validation source_validation
					 INNER JOIN user_competence_validation target_validation
						ON target_validation.IDuser_competence = :target_id
					   AND target_validation.IDvalidator_user = source_validation.IDvalidator_user
					   AND target_validation.IDorganization = source_validation.IDorganization
					 WHERE source_validation.IDuser_competence = :source_id",
					array('target_id' => $targetId, 'source_id' => $sourceId)
				);
				self::accountMergeExecute(
					$pdo,
					'UPDATE user_competence_validation SET IDuser_competence = :target_id WHERE IDuser_competence = :source_id',
					array('target_id' => $targetId, 'source_id' => $sourceId)
				);
				self::accountMergeExecute(
					$pdo,
					'UPDATE user_competence SET level = :level, description = :description WHERE id = :target_id',
					array(
						'level' => max((int)$row['source_level'], (int)$row['target_level']),
						'description' => self::accountMergeFillMissing($row['target_description'] ?? '', $row['source_description'] ?? null),
						'target_id' => $targetId,
					)
				);
				self::accountMergeExecute($pdo, 'DELETE FROM user_competence WHERE id = :id', array('id' => $sourceId));
				$summary['deduplicated']++;
			}

			foreach (array(
				array('table' => 'user_mission', 'keys' => array('IDmission', 'IDparcours'), 'date' => 'done'),
				array('table' => 'user_homework', 'keys' => array('IDmission', 'IDhomework', 'IDparcours'), 'date' => 'done'),
			) as $definition) {
				$table = self::accountMergeQuoteIdentifier($definition['table']);
				$conditions = array();
				foreach ($definition['keys'] as $key) {
					$quotedKey = self::accountMergeQuoteIdentifier($key);
					$conditions[] = 'target.' . $quotedKey . ' = source.' . $quotedKey;
				}
				$conditionSql = implode(' AND ', $conditions);
				$date = self::accountMergeQuoteIdentifier($definition['date']);
				self::accountMergeExecute(
					$pdo,
					"UPDATE $table target
					 INNER JOIN $table source ON target.IDuser = :kept_user_id AND $conditionSql
					 SET target.$date = CASE
						 WHEN target.$date IS NULL THEN source.$date
						 WHEN source.$date IS NULL THEN target.$date
						 ELSE LEAST(target.$date, source.$date)
					 END
					 WHERE source.IDuser = :removed_user_id",
					array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
				);
				$summary['deduplicated'] += self::accountMergeExecute(
					$pdo,
					"DELETE source FROM $table source
					 INNER JOIN $table target ON target.IDuser = :kept_user_id AND $conditionSql
					 WHERE source.IDuser = :removed_user_id",
					array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
				);
			}

			self::accountMergeExecute(
				$pdo,
				"UPDATE user_competence_validation target
				 INNER JOIN user_competence_validation source
					ON target.IDuser_competence = source.IDuser_competence
				   AND target.IDvalidator_user = :kept_user_id
				   AND target.IDorganization = source.IDorganization
				 SET target.level = GREATEST(target.level, source.level)
				 WHERE source.IDvalidator_user = :removed_user_id",
				array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
			);
			$summary['deduplicated'] += self::accountMergeExecute(
				$pdo,
				"DELETE source FROM user_competence_validation source
				 INNER JOIN user_competence_validation target
					ON target.IDuser_competence = source.IDuser_competence
				   AND target.IDvalidator_user = :kept_user_id
				   AND target.IDorganization = source.IDorganization
				 WHERE source.IDvalidator_user = :removed_user_id",
				array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
			);
		}

		private static function accountMergeActivityRows(\PDO $pdo, $keptUserId, $removedUserId, array &$summary)
		{
			self::accountMergeExecute(
				$pdo,
				"UPDATE project_user target
				 INNER JOIN project_user source
					ON target.IDproject = source.IDproject
				   AND target.IDuser = :kept_user_id
				 SET target.active = GREATEST(target.active, source.active),
					 target.datecreation = LEAST(target.datecreation, source.datecreation)
				 WHERE source.IDuser = :removed_user_id",
				array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
			);
			$summary['deduplicated'] += self::accountMergeExecute(
				$pdo,
				"DELETE source FROM project_user source
				 INNER JOIN project_user target
					ON target.IDproject = source.IDproject
				   AND target.IDuser = :kept_user_id
				 WHERE source.IDuser = :removed_user_id",
				array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
			);

			foreach (array(
				array('table' => 'event_attendance', 'keys' => array('IDevent')),
				array('table' => 'resource_attendance', 'keys' => array('resource_type', 'resource_id')),
			) as $definition) {
				$table = self::accountMergeQuoteIdentifier($definition['table']);
				$conditions = array();
				foreach ($definition['keys'] as $key) {
					$quotedKey = self::accountMergeQuoteIdentifier($key);
					$conditions[] = 'target.' . $quotedKey . ' = source.' . $quotedKey;
				}
				$pairs = self::accountMergeFetchAll(
					$pdo,
					"SELECT source.*, target.id AS target_id,
						target.email AS target_email, target.display_name AS target_display_name,
						target.is_present AS target_is_present,
						target.IDuser_checked_by AS target_checked_by,
						target.checked_at AS target_checked_at,
						target.active AS target_active,
						target.created_at AS target_created_at,
						target.updated_at AS target_updated_at
					 FROM $table source
					 INNER JOIN $table target
						ON target.IDuser = :kept_user_id
					   AND " . implode(' AND ', $conditions) . "
					 WHERE source.IDuser = :removed_user_id",
					array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
				);

				foreach ($pairs as $row) {
					$sourceCheckedAt = strtotime((string)($row['checked_at'] ?? '')) ?: 0;
					$targetCheckedAt = strtotime((string)($row['target_checked_at'] ?? '')) ?: 0;
					$sourceCheckWins = $sourceCheckedAt > $targetCheckedAt;
					self::accountMergeExecute($pdo, "DELETE FROM $table WHERE id = :id", array('id' => (int)$row['id']));
					self::accountMergeExecute(
						$pdo,
						"UPDATE $table
						 SET email = :email, display_name = :display_name,
							 is_present = GREATEST(is_present, :source_is_present),
							 IDuser_checked_by = :checked_by, checked_at = :checked_at,
							 active = GREATEST(active, :source_active),
							 created_at = LEAST(created_at, :source_created_at),
							 updated_at = GREATEST(updated_at, :source_updated_at)
						 WHERE id = :target_id",
						array(
							'email' => self::accountMergeFillMissing($row['target_email'] ?? '', $row['email'] ?? null),
							'display_name' => self::accountMergeFillMissing($row['target_display_name'] ?? '', $row['display_name'] ?? null),
							'source_is_present' => (int)$row['is_present'],
							'checked_by' => $sourceCheckWins ? $row['IDuser_checked_by'] : $row['target_checked_by'],
							'checked_at' => $sourceCheckWins ? $row['checked_at'] : $row['target_checked_at'],
							'source_active' => (int)$row['active'],
							'source_created_at' => $row['created_at'],
							'source_updated_at' => $row['updated_at'],
							'target_id' => (int)$row['target_id'],
						)
					);
					$summary['deduplicated']++;
				}
			}
		}

		private static function accountMergeDecisionRows(\PDO $pdo, $keptUserId, $removedUserId, array &$summary, array &$responseArchive)
		{
			$pairs = self::accountMergeFetchAll(
				$pdo,
				"SELECT source.*, target.id AS target_id,
					target.email AS target_email, target.display_name AS target_display_name,
					target.parameters AS target_parameters, target.active AS target_active,
					target.status AS target_status, target.role AS target_role,
					target.access_token AS target_access_token,
					target.invitation_sent_at AS target_invitation_sent_at,
					target.invitation_opened_at AS target_invitation_opened_at,
					target.created_at AS target_created_at, target.updated_at AS target_updated_at
				 FROM decision_participant source
				 INNER JOIN decision_participant target
					ON target.IDuser = :kept_user_id
				   AND target.IDdecision_process = source.IDdecision_process
				 WHERE source.IDuser = :removed_user_id",
				array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
			);

			$responseStatusRank = array('invalidated' => 0, 'draft' => 1, 'invited' => 1, 'submitted' => 2, 'accepted' => 2);
			$participantStatusRank = array('revoked' => 0, 'declined' => 1, 'invited' => 2, 'active' => 3);
			$participantRoleRank = array('observer' => 0, 'participant' => 1, 'owner' => 2);
			foreach ($pairs as $participant) {
				$sourceParticipantId = (int)$participant['id'];
				$targetParticipantId = (int)$participant['target_id'];
				$responses = self::accountMergeFetchAll(
					$pdo,
					'SELECT * FROM decision_response WHERE IDdecision_participant = :participant_id ORDER BY id ASC',
					array('participant_id' => $sourceParticipantId)
				);

				foreach ($responses as $sourceResponse) {
					$targetResponses = self::accountMergeFetchAll(
						$pdo,
						'SELECT * FROM decision_response WHERE IDdecision_group = :group_id AND IDdecision_participant = :participant_id LIMIT 1',
						array('group_id' => (int)$sourceResponse['IDdecision_group'], 'participant_id' => $targetParticipantId)
					);
					if (count($targetResponses) === 0) {
						self::accountMergeExecute(
							$pdo,
							'UPDATE decision_response SET IDdecision_participant = :target_id WHERE id = :source_id',
							array('target_id' => $targetParticipantId, 'source_id' => (int)$sourceResponse['id'])
						);
						continue;
					}

					$targetResponse = $targetResponses[0];
					$sourceRank = $responseStatusRank[(string)$sourceResponse['status']] ?? 0;
					$targetRank = $responseStatusRank[(string)$targetResponse['status']] ?? 0;
					$sourceUpdated = strtotime((string)($sourceResponse['updated_at'] ?? '')) ?: 0;
					$targetUpdated = strtotime((string)($targetResponse['updated_at'] ?? '')) ?: 0;
					$sourceWins = $sourceRank > $targetRank || ($sourceRank === $targetRank && $sourceUpdated > $targetUpdated);
					$responseArchive[] = $sourceWins ? $targetResponse : $sourceResponse;

					if ($sourceWins) {
						self::accountMergeExecute(
							$pdo,
							"UPDATE decision_response
							 SET status = :status, parameters = :parameters,
								 submitted_at = :submitted_at, updated_at = :updated_at
							 WHERE id = :target_id",
							array(
								'status' => $sourceResponse['status'],
								'parameters' => $sourceResponse['parameters'],
								'submitted_at' => $sourceResponse['submitted_at'],
								'updated_at' => $sourceResponse['updated_at'],
								'target_id' => (int)$targetResponse['id'],
							)
						);
					}
					self::accountMergeExecute($pdo, 'DELETE FROM decision_response WHERE id = :id', array('id' => (int)$sourceResponse['id']));
					$summary['response_conflicts']++;
				}

				self::accountMergeExecute(
					$pdo,
					'UPDATE chat_message SET IDdecision_participant = :target_id WHERE IDdecision_participant = :source_id',
					array('target_id' => $targetParticipantId, 'source_id' => $sourceParticipantId)
				);
				self::accountMergeExecute($pdo, 'DELETE FROM decision_participant WHERE id = :id', array('id' => $sourceParticipantId));
				$sourceStatus = (string)$participant['status'];
				$targetStatus = (string)$participant['target_status'];
				$mergedStatus = ($participantStatusRank[$sourceStatus] ?? 0) > ($participantStatusRank[$targetStatus] ?? 0)
					? $sourceStatus
					: $targetStatus;
				$sourceRole = (string)$participant['role'];
				$targetRole = (string)$participant['target_role'];
				$mergedRole = ($participantRoleRank[$sourceRole] ?? 0) > ($participantRoleRank[$targetRole] ?? 0)
					? $sourceRole
					: $targetRole;
				self::accountMergeExecute(
					$pdo,
					"UPDATE decision_participant
					 SET email = :email, display_name = :display_name, parameters = :parameters,
						 active = GREATEST(active, :source_active), status = :status, role = :role,
						 access_token = :access_token,
						 invitation_sent_at = COALESCE(invitation_sent_at, :source_invitation_sent_at),
						 invitation_opened_at = COALESCE(invitation_opened_at, :source_invitation_opened_at),
						 created_at = LEAST(created_at, :source_created_at),
						 updated_at = GREATEST(updated_at, :source_updated_at)
					 WHERE id = :target_id",
					array(
						'email' => self::accountMergeFillMissing($participant['target_email'] ?? '', $participant['email'] ?? null),
						'display_name' => self::accountMergeFillMissing($participant['target_display_name'] ?? '', $participant['display_name'] ?? null),
						'parameters' => self::accountMergeParameters($participant['target_parameters'] ?? null, $participant['parameters'] ?? null),
						'source_active' => (int)$participant['active'],
						'status' => $mergedStatus,
						'role' => $mergedRole,
						'access_token' => self::accountMergeFillMissing($participant['target_access_token'] ?? '', $participant['access_token'] ?? null),
						'source_invitation_sent_at' => $participant['invitation_sent_at'],
						'source_invitation_opened_at' => $participant['invitation_opened_at'],
						'source_created_at' => $participant['created_at'],
						'source_updated_at' => $participant['updated_at'],
						'target_id' => $targetParticipantId,
					)
				);
				$summary['deduplicated']++;
			}
		}

		private static function accountMergeOwnershipColumns(\PDO $pdo)
		{
			$rows = self::accountMergeFetchAll(
				$pdo,
				"SELECT columns_table.TABLE_NAME, columns_table.COLUMN_NAME
				 FROM information_schema.COLUMNS columns_table
				 INNER JOIN information_schema.TABLES tables_table
					ON tables_table.TABLE_SCHEMA = columns_table.TABLE_SCHEMA
				   AND tables_table.TABLE_NAME = columns_table.TABLE_NAME
				   AND tables_table.TABLE_TYPE = 'BASE TABLE'
				 LEFT JOIN information_schema.KEY_COLUMN_USAGE key_usage
					ON key_usage.TABLE_SCHEMA = columns_table.TABLE_SCHEMA
				   AND key_usage.TABLE_NAME = columns_table.TABLE_NAME
				   AND key_usage.COLUMN_NAME = columns_table.COLUMN_NAME
				 WHERE columns_table.TABLE_SCHEMA = DATABASE()
				   AND columns_table.TABLE_NAME <> 'user'
				   AND (
					 key_usage.REFERENCED_TABLE_NAME = 'user'
					 OR (
						columns_table.DATA_TYPE IN ('tinyint', 'smallint', 'mediumint', 'int', 'bigint')
						AND (
							LOWER(columns_table.COLUMN_NAME) LIKE 'iduser%'
							OR LOWER(columns_table.COLUMN_NAME) LIKE '%user_id'
							OR LOWER(columns_table.COLUMN_NAME) LIKE '%\\_user'
						)
					 )
				   )
				   AND NOT (
					 columns_table.TABLE_NAME = 'user_competence_validation'
					 AND columns_table.COLUMN_NAME = 'IDuser_competence'
				   )
				 GROUP BY columns_table.TABLE_NAME, columns_table.COLUMN_NAME
				 ORDER BY columns_table.TABLE_NAME, MIN(columns_table.ORDINAL_POSITION)"
			);

			return $rows;
		}

		private static function accountMergeDeleteUniqueCollisions(\PDO $pdo, $tableName, $columnName, $keptUserId, $removedUserId)
		{
			$indexes = self::accountMergeFetchAll(
				$pdo,
				"SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_list
				 FROM information_schema.STATISTICS
				 WHERE TABLE_SCHEMA = DATABASE()
				   AND TABLE_NAME = :table_name
				   AND NON_UNIQUE = 0
				 GROUP BY INDEX_NAME
				 HAVING FIND_IN_SET(:column_name, columns_list) > 0",
				array('table_name' => $tableName, 'column_name' => $columnName)
			);

			$table = self::accountMergeQuoteIdentifier($tableName);
			$column = self::accountMergeQuoteIdentifier($columnName);
			$deleted = 0;
			foreach ($indexes as $index) {
				$columns = array_filter(explode(',', (string)$index['columns_list']));
				$conditions = array('target.' . $column . ' = :kept_user_id');
				foreach ($columns as $indexColumn) {
					if ($indexColumn === $columnName) {
						continue;
					}
					$quotedIndexColumn = self::accountMergeQuoteIdentifier($indexColumn);
					$conditions[] = 'target.' . $quotedIndexColumn . ' = source.' . $quotedIndexColumn;
				}

				$deleted += self::accountMergeExecute(
					$pdo,
					"DELETE source FROM $table source
					 INNER JOIN $table target ON " . implode(' AND ', $conditions) . "
					 WHERE source.$column = :removed_user_id",
					array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
				);
			}

			return $deleted;
		}

		private static function accountMergeReplaceParameterUserIds($value, $keptUserId, $removedUserId, $parentKey = '')
		{
			if (!is_array($value)) {
				return array($value, false);
			}

			$changed = false;
			foreach ($value as $key => $childValue) {
				$keyName = is_string($key) ? $key : $parentKey;
				$normalizedKey = strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $keyName));
				$isUserIdentityKey = strpos($normalizedKey, 'user') !== false
					&& (str_ends_with($normalizedKey, 'id') || str_ends_with($normalizedKey, 'ids'))
					&& strpos($normalizedKey, 'patreon') === false
					&& strpos($normalizedKey, 'removeduser') === false;

				if ($isUserIdentityKey && !is_array($childValue) && (string)$childValue === (string)$removedUserId) {
					$value[$key] = is_int($childValue) ? $keptUserId : (string)$keptUserId;
					$changed = true;
					continue;
				}

				if (is_array($childValue)) {
					list($replacement, $childChanged) = self::accountMergeReplaceParameterUserIds(
						$childValue,
						$keptUserId,
						$removedUserId,
						$keyName
					);
					if ($childChanged) {
						$value[$key] = $replacement;
						$changed = true;
					}
				}
			}

			if ($parentKey !== '') {
				$normalizedParentKey = strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $parentKey));
				$isUserIdList = strpos($normalizedParentKey, 'user') !== false
					&& str_ends_with($normalizedParentKey, 'ids')
					&& strpos($normalizedParentKey, 'patreon') === false;
				if ($isUserIdList) {
					foreach ($value as $key => $childValue) {
						if (!is_array($childValue) && (string)$childValue === (string)$removedUserId) {
							$value[$key] = is_int($childValue) ? $keptUserId : (string)$keptUserId;
							$changed = true;
						}
					}
				}
			}

			return array($value, $changed);
		}

		private static function accountMergeParameterReferences(\PDO $pdo, $keptUserId, $removedUserId, array &$summary)
		{
			$tables = self::accountMergeFetchAll(
				$pdo,
				"SELECT parameters_column.TABLE_NAME
				 FROM information_schema.COLUMNS parameters_column
				 INNER JOIN information_schema.COLUMNS id_column
					ON id_column.TABLE_SCHEMA = parameters_column.TABLE_SCHEMA
				   AND id_column.TABLE_NAME = parameters_column.TABLE_NAME
				   AND id_column.COLUMN_NAME = 'id'
				 WHERE parameters_column.TABLE_SCHEMA = DATABASE()
				   AND parameters_column.COLUMN_NAME = 'parameters'
				 ORDER BY parameters_column.TABLE_NAME"
			);

			foreach ($tables as $tableRow) {
				$tableName = (string)$tableRow['TABLE_NAME'];
				$table = self::accountMergeQuoteIdentifier($tableName);
				$rows = self::accountMergeFetchAll(
					$pdo,
					"SELECT id, parameters FROM $table WHERE parameters LIKE :needle",
					array('needle' => '%' . $removedUserId . '%')
				);
				foreach ($rows as $row) {
					$parameters = json_decode((string)$row['parameters'], true);
					if (!is_array($parameters)) {
						continue;
					}

					list($replacement, $changed) = self::accountMergeReplaceParameterUserIds(
						$parameters,
						$keptUserId,
						$removedUserId
					);
					if (!$changed) {
						continue;
					}

					self::accountMergeExecute(
						$pdo,
						"UPDATE $table SET parameters = :parameters WHERE id = :id",
						array(
							'parameters' => json_encode($replacement, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
							'id' => (int)$row['id'],
						)
					);
					$summary['parameter_references']++;
				}
			}
		}

		public static function mergeAccounts($keptUserId, $removedUserId)
		{
			$keptUserId = (int)$keptUserId;
			$removedUserId = (int)$removedUserId;
			if ($keptUserId <= 0 || $removedUserId <= 0 || $keptUserId === $removedUserId) {
				return array('status' => false, 'message' => 'Les comptes a fusionner sont invalides.');
			}

			$pdo = self::getPdo();
			if (!$pdo instanceof \PDO) {
				return array('status' => false, 'message' => 'La base de donnees est indisponible.');
			}

			$summary = array(
				'updated' => 0,
				'deduplicated' => 0,
				'response_conflicts' => 0,
				'parameter_references' => 0,
				'organization_history_entries' => 0,
				'tables' => array(),
			);
			$responseArchive = array();

			try {
				$pdo->beginTransaction();
				$users = self::accountMergeFetchAll(
					$pdo,
					'SELECT id, email, username, firstname, lastname, siteadmin, parameters, param_easypv, param_easymemo, param_easycircle
					 FROM `user` WHERE id IN (:kept_user_id, :removed_user_id) FOR UPDATE',
					array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
				);
				if (count($users) !== 2) {
					throw new \RuntimeException('Un des deux comptes n existe plus.');
				}
				$usersById = array();
				foreach ($users as $userRow) {
					$usersById[(int)$userRow['id']] = $userRow;
					$userParameters = json_decode((string)($userRow['parameters'] ?? ''), true);
					if (is_array($userParameters) && !empty($userParameters['historical_placeholder'])) {
						throw new \RuntimeException('Un compte historique technique ne peut pas etre fusionne.');
					}
				}
				$superAdminForced = false;
				if (empty($usersById[$keptUserId]['siteadmin']) && !empty($usersById[$removedUserId]['siteadmin'])) {
					$requestedKeptUserId = $keptUserId;
					$keptUserId = $removedUserId;
					$removedUserId = $requestedKeptUserId;
					$superAdminForced = true;
				}
				$removedEmail = (string)($usersById[$removedUserId]['email'] ?? '');
				$organizationIds = self::accountMergeOrganizationIds($pdo, $keptUserId, $removedUserId);
				$keptUserReference = self::accountMergeUserReference($usersById[$keptUserId]);
				$removedUserReference = self::accountMergeUserReference($usersById[$removedUserId]);

				self::accountMergeMembershipRows($pdo, $keptUserId, $removedUserId, $removedEmail, $summary);
				self::accountMergeLearningRows($pdo, $keptUserId, $removedUserId, $summary);
				self::accountMergeActivityRows($pdo, $keptUserId, $removedUserId, $summary);
				self::accountMergeDecisionRows($pdo, $keptUserId, $removedUserId, $summary, $responseArchive);
				self::accountMergeExecute(
					$pdo,
					"UPDATE history
					 SET target_id = :kept_user_id
					 WHERE target_type = 'user' AND target_id = :removed_user_id",
					array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
				);
				self::accountMergeExecute(
					$pdo,
					'UPDATE history SET content = REPLACE(content, :removed_token, :kept_token) WHERE content LIKE :token_search',
					array(
						'removed_token' => '[user|' . $removedUserId . '|',
						'kept_token' => '[user|' . $keptUserId . '|',
						'token_search' => '%[user|' . $removedUserId . '|%',
					)
				);

				self::accountMergeExecute(
					$pdo,
					"UPDATE `user` kept
					 INNER JOIN `user` removed ON removed.id = :removed_user_id
					 SET kept.username = COALESCE(NULLIF(kept.username, ''), removed.username),
						 kept.firstname = COALESCE(NULLIF(kept.firstname, ''), removed.firstname),
						 kept.lastname = COALESCE(NULLIF(kept.lastname, ''), removed.lastname),
						 kept.presentation = COALESCE(NULLIF(kept.presentation, ''), removed.presentation),
						 kept.latlong = COALESCE(NULLIF(kept.latlong, ''), removed.latlong),
						 kept.birthdate = COALESCE(kept.birthdate, removed.birthdate),
						 kept.image = COALESCE(NULLIF(kept.image, ''), removed.image),
						 kept.telegramID = COALESCE(NULLIF(kept.telegramID, ''), removed.telegramID),
						 kept.parameters = :parameters,
						 kept.param_easypv = :param_easypv,
						 kept.param_easymemo = :param_easymemo,
						 kept.param_easycircle = :param_easycircle,
						 kept.datecreation = LEAST(kept.datecreation, removed.datecreation),
						 kept.dateconnexion = CASE
							 WHEN kept.dateconnexion IS NULL THEN removed.dateconnexion
							 WHEN removed.dateconnexion IS NULL THEN kept.dateconnexion
							 ELSE GREATEST(kept.dateconnexion, removed.dateconnexion)
						 END,
						 kept.active = GREATEST(kept.active, removed.active),
						 kept.siteadmin = GREATEST(kept.siteadmin, removed.siteadmin)
					 WHERE kept.id = :kept_user_id",
					array(
						'kept_user_id' => $keptUserId,
						'removed_user_id' => $removedUserId,
						'parameters' => self::accountMergeParameters($usersById[$keptUserId]['parameters'] ?? null, $usersById[$removedUserId]['parameters'] ?? null),
						'param_easypv' => self::accountMergeParameters($usersById[$keptUserId]['param_easypv'] ?? null, $usersById[$removedUserId]['param_easypv'] ?? null),
						'param_easymemo' => self::accountMergeParameters($usersById[$keptUserId]['param_easymemo'] ?? null, $usersById[$removedUserId]['param_easymemo'] ?? null),
						'param_easycircle' => self::accountMergeParameters($usersById[$keptUserId]['param_easycircle'] ?? null, $usersById[$removedUserId]['param_easycircle'] ?? null),
					)
				);

				self::accountMergeExecute($pdo, 'DELETE FROM user_login_token WHERE IDuser = :user_id', array('user_id' => $removedUserId));
				self::accountMergeExecute($pdo, 'DELETE FROM user_remember WHERE IDuser = :user_id', array('user_id' => $removedUserId));

				$ownershipColumns = self::accountMergeOwnershipColumns($pdo);
				foreach ($ownershipColumns as $ownershipColumn) {
					$tableName = (string)$ownershipColumn['TABLE_NAME'];
					$columnName = (string)$ownershipColumn['COLUMN_NAME'];
					if (in_array($tableName, array('user_login_token', 'user_remember'), true)) {
						continue;
					}

					$summary['deduplicated'] += self::accountMergeDeleteUniqueCollisions(
						$pdo,
						$tableName,
						$columnName,
						$keptUserId,
						$removedUserId
					);
					$table = self::accountMergeQuoteIdentifier($tableName);
					$column = self::accountMergeQuoteIdentifier($columnName);
					$updated = self::accountMergeExecute(
						$pdo,
						"UPDATE $table SET $column = :kept_user_id WHERE $column = :removed_user_id",
						array('kept_user_id' => $keptUserId, 'removed_user_id' => $removedUserId)
					);
					if ($updated > 0) {
						$summary['updated'] += $updated;
						$summary['tables'][$tableName] = ($summary['tables'][$tableName] ?? 0) + $updated;
					}
				}
				self::accountMergeParameterReferences($pdo, $keptUserId, $removedUserId, $summary);

				foreach ($ownershipColumns as $ownershipColumn) {
					$table = self::accountMergeQuoteIdentifier((string)$ownershipColumn['TABLE_NAME']);
					$column = self::accountMergeQuoteIdentifier((string)$ownershipColumn['COLUMN_NAME']);
					$remaining = self::accountMergeFetchAll(
						$pdo,
						"SELECT 1 AS found FROM $table WHERE $column = :removed_user_id LIMIT 1 FOR UPDATE",
						array('removed_user_id' => $removedUserId)
					);
					if (count($remaining) > 0) {
						throw new \RuntimeException('Une reference a l ancien compte subsiste dans ' . (string)$ownershipColumn['TABLE_NAME'] . '.');
					}
				}

				if (count($responseArchive) > 0) {
					$historyResult = \dbObject\History::createEntry(
						0,
						$keptUserId,
						'account_merge_response_archive',
						'Reponses conservees lors de la fusion de deux comptes.',
						array(
							'removed_user_id' => $removedUserId,
							'responses' => $responseArchive,
						),
						'user',
						$keptUserId
					);
					if (!is_array($historyResult) || empty($historyResult['status'])) {
						throw new \RuntimeException('L archivage des reponses en conflit a echoue.');
					}
				}

				foreach ($organizationIds as $organizationId) {
					$historyResult = History::createEntry(
						$organizationId,
						$keptUserId,
						'account_merge',
						'Fusion de deux profils : ' . $keptUserReference . ' et ' . $removedUserReference . '.',
						array(
							'kept_user_id' => $keptUserId,
							'removed_user_id' => $removedUserId,
							'superadmin_forced' => $superAdminForced,
						),
						'organization',
						$organizationId
					);
					if (!is_array($historyResult) || empty($historyResult['status'])) {
						throw new \RuntimeException('L ecriture de l historique de fusion a echoue.');
					}
					$summary['organization_history_entries']++;
				}

				self::accountMergeExecute($pdo, 'DELETE FROM `user` WHERE id = :removed_user_id', array('removed_user_id' => $removedUserId));
				$pdo->commit();
			} catch (\Throwable $error) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'message' => $error->getMessage(),
				);
			}

			return array(
				'status' => true,
				'kept_user_id' => $keptUserId,
				'removed_user_id' => $removedUserId,
				'superadmin_forced' => $superAdminForced,
				'summary' => $summary,
			);
		}
	}

?>
