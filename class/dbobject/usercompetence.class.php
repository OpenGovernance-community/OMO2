<?php
	namespace dbObject;

	class UserCompetence extends DbObject
	{
		public static function tableName()
		{
			return 'user_competence';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser', 'IDcompetence', 'IDorganization', 'level'], 'integer'],
				[['IDuser', 'IDcompetence', 'level'], 'required'],
				[['IDuser', 'IDcompetence', 'IDorganization'], 'fk'],
				[['description'], 'string'],
				[['datecreation', 'datemodification'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Utilisateur',
				'IDcompetence' => 'Competence',
				'IDorganization' => 'Organisation',
				'level' => 'Niveau',
				'description' => 'Description',
				'datecreation' => 'Creation',
				'datemodification' => 'Modification',
			];
		}

		public static function attributeLength()
		{
			return [
				'description' => 500,
			];
		}

		public static function getOrder()
		{
			return 'id DESC';
		}

		public static function getTypeOptions()
		{
			return [
				'technical' => 'Technique',
				'soft' => 'Soft skill',
			];
		}

		public static function getLevelOptions()
		{
			return [
				1 => 'Debutant',
				2 => 'Notions',
				3 => 'Autonome',
				4 => 'Avance',
				5 => 'Expert',
			];
		}

		public static function normalizeCategory($value)
		{
			return Competence::normalizeCategory($value);
		}

		public static function normalizeLevel($value)
		{
			$level = (int)$value;
			return ($level >= 1 && $level <= 5) ? $level : 0;
		}

		public static function normalizeDescription($value)
		{
			$value = trim((string)$value);
			$value = preg_replace('/\s+/u', ' ', $value);
			$value = is_string($value) ? trim($value) : '';
			if ($value === '') {
				return '';
			}

			return function_exists('mb_substr')
				? mb_substr($value, 0, 500, 'UTF-8')
				: substr($value, 0, 500);
		}

		public static function getLevelLabel($level)
		{
			$options = self::getLevelOptions();
			$level = (int)$level;
			return $options[$level] ?? '';
		}

		public static function getCategoryLabel($category)
		{
			$options = self::getTypeOptions();
			$category = self::normalizeCategory($category);
			return $options[$category] ?? $options['technical'];
		}

		protected static function computeUserInitials($displayName)
		{
			$displayName = trim((string)$displayName);
			if ($displayName === '') {
				return 'P';
			}

			$words = preg_split('/\s+/u', $displayName) ?: [];
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
				$initials = mb_substr($displayName, 0, 1, 'UTF-8');
			}

			return mb_strtoupper($initials, 'UTF-8');
		}

		protected static function buildValidatorDisplayName(array $row)
		{
			$fullName = trim((string)($row['validator_firstname'] ?? '') . ' ' . (string)($row['validator_lastname'] ?? ''));
			if ($fullName !== '') {
				return $fullName;
			}

			$scopedUsername = trim((string)($row['validator_scoped_username'] ?? ''));
			if ($scopedUsername !== '') {
				return $scopedUsername;
			}

			$username = trim((string)($row['validator_username'] ?? ''));
			if ($username !== '') {
				return $username;
			}

			$scopedEmail = trim((string)($row['validator_scoped_email'] ?? ''));
			if ($scopedEmail !== '') {
				return $scopedEmail;
			}

			return trim((string)($row['validator_email'] ?? ''));
		}

		protected static function loadValidatorCard($validatorUserId, $organizationId = 0)
		{
			static $cache = [];

			$validatorUserId = (int)$validatorUserId;
			$organizationId = (int)$organizationId;
			$cacheKey = $validatorUserId . ':' . $organizationId;

			if (isset($cache[$cacheKey])) {
				return $cache[$cacheKey];
			}

			$user = new User();
			if (!$user->load($validatorUserId)) {
				$cache[$cacheKey] = null;
				return null;
			}

			$displayName = trim((string)$user->getScopedDisplayName($organizationId));
			$photoUrl = trim((string)$user->getScopedProfilePhotoUrl($organizationId));

			$cache[$cacheKey] = [
				'userId' => $validatorUserId,
				'displayName' => $displayName,
				'photoUrl' => $photoUrl,
				'initials' => self::computeUserInitials($displayName),
			];

			return $cache[$cacheKey];
		}

		protected static function loadRowById($id)
		{
			$row = self::fetchRow(
				"SELECT *
				FROM user_competence
				WHERE id = :id
				LIMIT 1",
				[
					'id' => (int)$id,
				]
			);

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		protected static function findByUserCompetenceScope($userId, $competenceId, $organizationId = 0)
		{
			$userId = (int)$userId;
			$competenceId = (int)$competenceId;
			$organizationId = (int)$organizationId;

			if ($userId <= 0 || $competenceId <= 0) {
				return false;
			}

			$sql = "
				SELECT *
				FROM user_competence
				WHERE IDuser = :user_id
				  AND IDcompetence = :competence_id
			";
			$params = [
				'user_id' => $userId,
				'competence_id' => $competenceId,
			];

			if ($organizationId > 0) {
				$sql .= " AND IDorganization = :organization_id";
				$params['organization_id'] = $organizationId;
			} else {
				$sql .= " AND IDorganization IS NULL";
			}

			$sql .= " ORDER BY id ASC LIMIT 1";

			$row = self::fetchRow($sql, $params);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		protected static function loadValidationByScope($userCompetenceId, $validatorUserId, $organizationId)
		{
			$row = self::fetchRow(
				"SELECT *
				FROM user_competence_validation
				WHERE IDuser_competence = :user_competence_id
				  AND IDvalidator_user = :validator_user_id
				  AND IDorganization = :organization_id
				LIMIT 1",
				[
					'user_competence_id' => (int)$userCompetenceId,
					'validator_user_id' => (int)$validatorUserId,
					'organization_id' => (int)$organizationId,
				]
			);

			if ($row === false) {
				return false;
			}

			$item = new UserCompetenceValidation();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function buildVisibleCompetenceRows($targetUserId, $organizationId = 0, $viewerUserId = 0)
		{
			$targetUserId = (int)$targetUserId;
			$organizationId = (int)$organizationId;
			$viewerUserId = (int)$viewerUserId;

			if ($targetUserId <= 0) {
				return [];
			}

			$sql = "
				SELECT
					uc.id AS user_competence_id,
					uc.IDorganization AS declaration_organization_id,
					uc.level AS declaration_level,
					uc.description AS declaration_description,
					uc.datecreation AS declaration_created_at,
					uc.datemodification AS declaration_updated_at,
					c.id AS competence_id,
					c.name AS competence_name,
					c.category AS competence_category,
					ucv.id AS validation_id,
					ucv.level AS validation_level,
					ucv.IDvalidator_user AS validator_user_id
				FROM user_competence uc
				INNER JOIN competence c ON c.id = uc.IDcompetence
			";

			if ($organizationId > 0) {
				$sql .= "
				LEFT JOIN user_competence_validation ucv
					ON ucv.IDuser_competence = uc.id
					AND ucv.IDorganization = :validation_organization_id
				";
			} else {
				$sql .= "
				LEFT JOIN user_competence_validation ucv
					ON 1 = 0
				";
			}

			$sql .= "
				WHERE uc.IDuser = :target_user_id
			";
			$params = [
				'target_user_id' => $targetUserId,
			];

			if ($organizationId > 0) {
				$params['validation_organization_id'] = $organizationId;
				$params['scope_organization_id'] = $organizationId;
				$sql .= "
				  AND (
					uc.IDorganization IS NULL
					OR uc.IDorganization = :scope_organization_id
				  )
				";
			} else {
				$sql .= "
				  AND uc.IDorganization IS NULL
				";
			}

			$sql .= "
				ORDER BY
					CASE WHEN uc.IDorganization IS NULL THEN 1 ELSE 0 END ASC,
					c.name ASC,
					uc.id ASC,
					ucv.id ASC
			";

			$rows = self::fetchAll($sql, $params);
			if (!is_array($rows)) {
				return [];
			}

			$items = [];
			foreach ($rows as $row) {
				$itemId = (int)($row['user_competence_id'] ?? 0);
				if ($itemId <= 0) {
					continue;
				}

				if (!isset($items[$itemId])) {
					$category = self::normalizeCategory($row['competence_category'] ?? '');
					$level = (int)($row['declaration_level'] ?? 0);
					$scope = (int)($row['declaration_organization_id'] ?? 0) > 0 ? 'organization' : 'general';

					$items[$itemId] = [
						'id' => $itemId,
						'competenceId' => (int)($row['competence_id'] ?? 0),
						'name' => (string)($row['competence_name'] ?? ''),
						'description' => (string)($row['declaration_description'] ?? ''),
						'category' => $category,
						'categoryLabel' => self::getCategoryLabel($category),
						'level' => $level,
						'levelLabel' => self::getLevelLabel($level),
						'scope' => $scope,
						'scopeLabel' => $scope === 'organization' ? 'Cette organisation' : 'Toutes les organisations',
						'validationCount' => 0,
						'validators' => [],
						'currentViewerValidationLevel' => 0,
					];
				}

				$validationId = (int)($row['validation_id'] ?? 0);
				if ($validationId <= 0) {
					continue;
				}

				$validatorUserId = (int)($row['validator_user_id'] ?? 0);
				if ($validatorUserId <= 0) {
					continue;
				}

				if (isset($items[$itemId]['validators'][$validatorUserId])) {
					continue;
				}

				$validatorCard = self::loadValidatorCard($validatorUserId, $organizationId);
				if (!is_array($validatorCard)) {
					continue;
				}
				$validatorLevel = (int)($row['validation_level'] ?? 0);

				$items[$itemId]['validators'][$validatorUserId] = [
					'userId' => $validatorUserId,
					'displayName' => (string)$validatorCard['displayName'],
					'photoUrl' => (string)$validatorCard['photoUrl'],
					'initials' => (string)$validatorCard['initials'],
					'level' => $validatorLevel,
					'levelLabel' => self::getLevelLabel($validatorLevel),
				];
				$items[$itemId]['validationCount'] += 1;

				if ($viewerUserId > 0 && $viewerUserId === $validatorUserId) {
					$items[$itemId]['currentViewerValidationLevel'] = $validatorLevel;
				}
			}

			foreach ($items as $itemId => $item) {
				$items[$itemId]['validators'] = array_values($item['validators']);
			}

			return array_values($items);
		}

		public static function saveDeclarationForUser($userId, array $payload, $currentOrganizationId = 0)
		{
			$userId = (int)$userId;
			$currentOrganizationId = (int)$currentOrganizationId;
			$scope = (($payload['scope'] ?? 'general') === 'organization') ? 'organization' : 'general';
			$limitToOrganization = !empty($payload['limit_to_organization']) && $currentOrganizationId > 0;
			$scopeOrganizationId = $limitToOrganization ? $currentOrganizationId : 0;
			$name = Competence::normalizeName($payload['name'] ?? '');
			$description = self::normalizeDescription($payload['description'] ?? '');
			$category = self::normalizeCategory($payload['category'] ?? 'technical');
			$level = self::normalizeLevel($payload['level'] ?? 0);
			$itemId = (int)($payload['id'] ?? 0);

			if ($userId <= 0) {
				return [
					'status' => false,
					'message' => 'Utilisateur invalide.',
				];
			}

			if ($scope === 'organization' && $scopeOrganizationId <= 0) {
				return [
					'status' => false,
					'message' => "Aucune organisation n'est disponible pour cette competence.",
				];
			}

			if ($scope === 'organization' && function_exists('commonUserHasOrganizationMembership') && !\commonUserHasOrganizationMembership($userId, $scopeOrganizationId)) {
				return [
					'status' => false,
					'message' => "Vous ne pouvez pas enregistrer de competence pour cette organisation.",
				];
			}

			if ($name === '') {
				return [
					'status' => false,
					'message' => 'Le nom de la competence est obligatoire.',
				];
			}

			if ($level <= 0) {
				return [
					'status' => false,
					'message' => 'Le niveau doit etre choisi.',
				];
			}

			$item = new self();
			if ($itemId > 0) {
				$item = self::loadRowById($itemId);
				if (!$item instanceof self) {
					return [
						'status' => false,
						'message' => 'Competence introuvable.',
					];
				}

				if ((int)$item->get('IDuser') !== $userId) {
					return [
						'status' => false,
						'message' => "Vous ne pouvez pas modifier cette competence.",
					];
				}
			}

			$competence = Competence::findOrCreate($name, $category, $scopeOrganizationId);
			if (!$competence instanceof Competence) {
				return [
					'status' => false,
					'message' => "Impossible d'enregistrer cette competence.",
				];
			}

			$existing = self::findByUserCompetenceScope($userId, (int)$competence->getId(), $scopeOrganizationId);
			if ($existing instanceof self && (int)$existing->getId() !== (int)$item->getId()) {
				return [
					'status' => false,
					'message' => 'Cette competence existe deja dans ce profil.',
				];
			}

			$isNew = (int)$item->getId() <= 0;
			$item->set('IDuser', $userId);
			$item->set('IDcompetence', (int)$competence->getId());
			$item->set('IDorganization', $scopeOrganizationId > 0 ? $scopeOrganizationId : null);
			$item->set('level', $level);
			$item->set('description', $description !== '' ? $description : null);
			if ($isNew) {
				$item->set('datecreation', new \DateTime());
			}
			$item->set('datemodification', new \DateTime());

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return [
					'status' => false,
					'message' => "Impossible d'enregistrer cette competence.",
				];
			}

			return [
				'status' => true,
				'message' => $isNew ? 'Competence ajoutee.' : 'Competence mise a jour.',
				'item' => $item,
			];
		}

		public static function deleteDeclarationForUser($userCompetenceId, $userId)
		{
			$userCompetenceId = (int)$userCompetenceId;
			$userId = (int)$userId;

			if ($userCompetenceId <= 0 || $userId <= 0) {
				return [
					'status' => false,
					'message' => 'Competence invalide.',
				];
			}

			$item = self::loadRowById($userCompetenceId);
			if (!$item instanceof self) {
				return [
					'status' => false,
					'message' => 'Competence introuvable.',
				];
			}

			if ((int)$item->get('IDuser') !== $userId) {
				return [
					'status' => false,
					'message' => "Vous ne pouvez pas supprimer cette competence.",
				];
			}

			self::execute(
				"DELETE FROM user_competence_validation
				WHERE IDuser_competence = :user_competence_id",
				[
					'user_competence_id' => $userCompetenceId,
				]
			);

			$deleted = $item->delete();
			if (!$deleted) {
				return [
					'status' => false,
					'message' => "Impossible de supprimer cette competence.",
				];
			}

			return [
				'status' => true,
				'message' => 'Competence supprimee.',
			];
		}

		public static function saveValidationForViewer($userCompetenceId, $validatorUserId, $organizationId, $level)
		{
			$userCompetenceId = (int)$userCompetenceId;
			$validatorUserId = (int)$validatorUserId;
			$organizationId = (int)$organizationId;
			$level = self::normalizeLevel($level);

			if ($userCompetenceId <= 0 || $validatorUserId <= 0 || $organizationId <= 0) {
				return [
					'status' => false,
					'message' => 'Validation invalide.',
				];
			}

			if ($level <= 0) {
				return [
					'status' => false,
					'message' => 'Le niveau de validation est obligatoire.',
				];
			}

			if (function_exists('commonUserHasOrganizationMembership') && !\commonUserHasOrganizationMembership($validatorUserId, $organizationId)) {
				return [
					'status' => false,
					'message' => "Vous ne faites pas partie de cette organisation.",
				];
			}

			$item = self::loadRowById($userCompetenceId);
			if (!$item instanceof self) {
				return [
					'status' => false,
					'message' => 'Competence introuvable.',
				];
			}

			$ownerUserId = (int)$item->get('IDuser');
			if ($ownerUserId <= 0) {
				return [
					'status' => false,
					'message' => 'Utilisateur introuvable pour cette competence.',
				];
			}

			if ($ownerUserId === $validatorUserId) {
				return [
					'status' => false,
					'message' => 'Vous ne pouvez pas valider votre propre competence.',
				];
			}

			$scopeOrganizationId = (int)$item->get('IDorganization');
			if ($scopeOrganizationId > 0 && $scopeOrganizationId !== $organizationId) {
				return [
					'status' => false,
					'message' => "Cette competence n'est pas visible dans cette organisation.",
				];
			}

			$owner = new User();
			if (!$owner->load($ownerUserId) || !$owner->hasOrganizationAccess($organizationId)) {
				return [
					'status' => false,
					'message' => "Cette competence n'est pas visible dans cette organisation.",
				];
			}

			$validation = self::loadValidationByScope($userCompetenceId, $validatorUserId, $organizationId);
			$isNew = !($validation instanceof UserCompetenceValidation);
			if ($isNew) {
				$validation = new UserCompetenceValidation();
				$validation->set('IDuser_competence', $userCompetenceId);
				$validation->set('IDvalidator_user', $validatorUserId);
				$validation->set('IDorganization', $organizationId);
				$validation->set('datecreation', new \DateTime());
			}

			$validation->set('level', $level);
			$validation->set('datemodification', new \DateTime());
			$saveResult = $validation->save();

			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return [
					'status' => false,
					'message' => "Impossible d'enregistrer cette validation.",
				];
			}

			return [
				'status' => true,
				'message' => $isNew ? 'Validation enregistree.' : 'Validation mise a jour.',
			];
		}

		public function canEdit()
		{
			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);

			return $currentUserId > 0 && $currentUserId === (int)$this->get('IDuser');
		}
	}

?>
