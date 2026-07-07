<?php
	namespace dbObject;

	class Parcours extends DbObject
	{
		protected static $hasApplicationColumnCache = null;
		protected static $hasIsPackColumnCache = null;
		protected static $hasPrerequisiteTableCache = null;
		protected static $attachedPackParentCache = [];

		public static function tableName()
		{
			return 'parcours';
		}

		public static function rules()
		{
			return [
				[['title'], 'required'],
				[['IDapplication'], 'fk'],
				[['id'], 'integer'],
				[['ispublic', 'isbasic', 'ispack'], 'boolean'],
				[['title'], 'string'],
				[['description'], 'text'],
				[['datecreation', 'datemodification'], 'datetime'],
				[['image'], 'sizedimage'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'description' => 'Description',
				'image' => 'Image',
				'IDorganization' => 'Organisation proprietaire',
				'IDapplication' => 'Application liee',
				'IDusercreation' => 'Createur',
				'IDusermodification' => 'Dernier modificateur',
				'datecreation' => 'Date de creation',
				'datemodification' => 'Date de modification',
				'ispublic' => 'Public',
				'isbasic' => 'Basic',
				'ispack' => 'Pack',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDorganization' => 'Organisation proprietaire du parcours',
				'IDapplication' => 'Application dont depend ce parcours. Si elle est desactivee dans une organisation, ce parcours et ses FAQ liees sont masques.',
				'IDusercreation' => 'Utilisateur qui a cree le parcours',
				'IDusermodification' => 'Utilisateur qui a modifie le parcours en dernier',
				'datecreation' => 'Date de creation du parcours',
				'datemodification' => 'Date de derniere modification du parcours',
				'ispublic' => 'Rend le parcours visible dans un catalogue partage et ajoutable dans une organisation',
				'isbasic' => 'Instancie automatiquement ce parcours pour chaque nouvelle organisation',
				'ispack' => 'Transforme ce parcours en pack. Un pack contient d autres parcours, pas des missions.',
			];
		}

		public static function attributeLength() {
			return [
				'title' => 150,
				'image' => [[750,300],[250,100]]
			];
		}

		public static function getOrder() {
			return "title";
		}

		public static function hasApplicationColumn()
		{
			if (self::$hasApplicationColumnCache !== null) {
				return self::$hasApplicationColumnCache;
			}

			$columnCount = (int)self::fetchValue(
				"SELECT COUNT(*)
				FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = DATABASE()
				  AND TABLE_NAME = 'parcours'
				  AND COLUMN_NAME = 'IDapplication'"
			);

			self::$hasApplicationColumnCache = $columnCount > 0;
			return self::$hasApplicationColumnCache;
		}

		public static function hasIsPackColumn()
		{
			if (self::$hasIsPackColumnCache !== null) {
				return self::$hasIsPackColumnCache;
			}

			$columnCount = (int)self::fetchValue(
				"SELECT COUNT(*)
				FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = DATABASE()
				  AND TABLE_NAME = 'parcours'
				  AND COLUMN_NAME = 'ispack'"
			);

			self::$hasIsPackColumnCache = $columnCount > 0;
			return self::$hasIsPackColumnCache;
		}

		public static function hasPrerequisiteTable()
		{
			if (self::$hasPrerequisiteTableCache !== null) {
				return self::$hasPrerequisiteTableCache;
			}

			self::$hasPrerequisiteTableCache = self::tableExists('parcours_prerequisite');
			return self::$hasPrerequisiteTableCache;
		}

		public function isVisibleInOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0 || !self::hasApplicationColumn()) {
				return true;
			}

			$applicationId = (int)$this->get('IDapplication');
			if ($applicationId <= 0) {
				return true;
			}

			return \dbObject\Application::isEnabledForOrganization($applicationId, $organizationId);
		}

		public function isPack()
		{
			return self::hasIsPackColumn() && (bool)$this->get('ispack');
		}

		public static function fetchPrerequisiteIdsForParcours($parcoursId)
		{
			$parcoursId = (int)$parcoursId;
			if ($parcoursId <= 0 || !self::hasPrerequisiteTable()) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT IDparcours_required
				FROM parcours_prerequisite
				WHERE IDparcours = :parcours_id",
				['parcours_id' => $parcoursId]
			);
			if (!is_array($rows)) {
				return [];
			}

			$ids = [];
			foreach ($rows as $row) {
				$requiredParcoursId = (int)($row['IDparcours_required'] ?? 0);
				if ($requiredParcoursId > 0) {
					$ids[$requiredParcoursId] = $requiredParcoursId;
				}
			}

			return array_values($ids);
		}

		public static function fetchDetailedPrerequisitesForParcours($parcoursId)
		{
			if (!self::hasPrerequisiteTable()) {
				return [];
			}

			return \dbObject\ParcoursPrerequisite::fetchDetailedForParcours((int)$parcoursId);
		}

		public static function userHasCompletedParcours($userId, $parcoursId)
		{
			$userId = (int)$userId;
			$parcoursId = (int)$parcoursId;
			if ($userId <= 0 || $parcoursId <= 0) {
				return false;
			}

			$row = self::fetchRow(
				"SELECT
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COUNT(DISTINCT CASE WHEN um.done IS NOT NULL THEN pm.IDmission END) AS done_missions
				FROM parcours_mission pm
				LEFT JOIN user_mission um
					ON um.IDmission = pm.IDmission
					AND um.IDparcours = pm.IDparcours
					AND um.IDuser = :user_id
				WHERE pm.IDparcours = :parcours_id",
				[
					'user_id' => $userId,
					'parcours_id' => $parcoursId,
				]
			);
			if (!is_array($row)) {
				return false;
			}

			$totalMissions = (int)($row['total_missions'] ?? 0);
			$doneMissions = (int)($row['done_missions'] ?? 0);
			return $totalMissions > 0 && $doneMissions >= $totalMissions;
		}

		public static function arePrerequisitesSatisfiedForUser($parcoursId, $userId)
		{
			$parcoursId = (int)$parcoursId;
			$userId = (int)$userId;
			$prerequisiteIds = self::fetchPrerequisiteIdsForParcours($parcoursId);
			if (count($prerequisiteIds) === 0) {
				return true;
			}

			if ($userId <= 0) {
				return false;
			}

			foreach ($prerequisiteIds as $requiredParcoursId) {
				if (!self::userHasCompletedParcours($userId, (int)$requiredParcoursId)) {
					return false;
				}
			}

			return true;
		}

		protected static function rowHasVisibleApplication(array $row, $organizationId)
		{
			$organizationId = (int)$organizationId;
			$applicationId = (int)($row['linked_application_id'] ?? 0);
			if ($applicationId <= 0) {
				return true;
			}

			return self::hasApplicationColumn()
				? \dbObject\Application::isEnabledForOrganization($applicationId, $organizationId)
				: true;
		}

		protected static function normalizePositiveIds(array $ids)
		{
			$normalizedIds = [];
			foreach ($ids as $id) {
				$id = (int)$id;
				if ($id > 0) {
					$normalizedIds[$id] = $id;
				}
			}

			return array_values($normalizedIds);
		}

		protected static function rowHasVisiblePrerequisites(array $row, $userId, array $completedParcoursIds = [])
		{
			$parcoursId = (int)($row['id'] ?? 0);
			$userId = (int)$userId;
			if ($parcoursId <= 0) {
				return false;
			}

			$prerequisiteIds = self::fetchPrerequisiteIdsForParcours($parcoursId);
			if (count($prerequisiteIds) === 0) {
				return true;
			}

			if ($userId <= 0) {
				$completedParcoursIds = self::normalizePositiveIds($completedParcoursIds);
				return count(array_diff($prerequisiteIds, $completedParcoursIds)) === 0;
			}

			return self::arePrerequisitesSatisfiedForUser($parcoursId, $userId);
		}

		protected static function appendVisibilityFlagsToRows(array $rows, $organizationId, $userId, array $completedParcoursIds = [])
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			$completedParcoursIds = self::normalizePositiveIds($completedParcoursIds);
			$enrichedRows = [];

			foreach ($rows as $row) {
				if (!is_array($row)) {
					continue;
				}

				$applicationVisible = self::rowHasVisibleApplication($row, $organizationId);
				$prerequisiteVisible = self::rowHasVisiblePrerequisites($row, $userId, $completedParcoursIds);
				$row['isapplicationvisible'] = $applicationVisible ? 1 : 0;
				$row['isprerequisitevisible'] = $prerequisiteVisible ? 1 : 0;
				$row['isvisible'] = ($applicationVisible && $prerequisiteVisible) ? 1 : 0;
				$enrichedRows[] = $row;
			}

			return $enrichedRows;
		}

		protected static function filterHiddenRows(array $rows, $includeHidden)
		{
			if ($includeHidden) {
				return $rows;
			}

			return array_values(array_filter($rows, function ($row) {
				return is_array($row) && !empty($row['isvisible']);
			}));
		}

		public static function hasAttachedPackParentInOrganization($organizationId, $parcoursId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$parcoursId;
			$cacheKey = $organizationId . ':' . $parcoursId;

			if (array_key_exists($cacheKey, self::$attachedPackParentCache)) {
				return self::$attachedPackParentCache[$cacheKey];
			}

			if ($organizationId <= 0 || $parcoursId <= 0 || !self::tableExists('parcours_parcours')) {
				self::$attachedPackParentCache[$cacheKey] = false;
				return false;
			}

			$hasAttachedPackParent = (int)self::fetchValue(
				"SELECT COUNT(*)
				FROM organization_parcours op_pack
				INNER JOIN parcours_parcours pp
					ON pp.IDparcours_parent = op_pack.IDparcours
				WHERE op_pack.IDorganization = :organization_id
				  AND pp.IDparcours_child = :parcours_id",
				[
					'organization_id' => $organizationId,
					'parcours_id' => $parcoursId,
				]
			) > 0;

			self::$attachedPackParentCache[$cacheKey] = $hasAttachedPackParent;
			return $hasAttachedPackParent;
		}

		protected static function buildPrerequisiteVisibilityWhereSql($parcoursAlias, $userId, array $completedParcoursIds = [])
		{
			$parcoursAlias = trim((string)$parcoursAlias) !== '' ? trim((string)$parcoursAlias) : 'p';
			$userId = (int)$userId;
			if (!self::hasPrerequisiteTable()) {
				return '1=1';
			}

			if ($userId <= 0) {
				$completedParcoursIds = self::normalizePositiveIds($completedParcoursIds);
				if (count($completedParcoursIds) > 0) {
					return "NOT EXISTS (
						SELECT 1
						FROM parcours_prerequisite pr
						WHERE pr.IDparcours = " . $parcoursAlias . ".id
						  AND pr.IDparcours_required NOT IN (" . implode(',', $completedParcoursIds) . ")
					)";
				}

				return "NOT EXISTS (
					SELECT 1
					FROM parcours_prerequisite pr
					WHERE pr.IDparcours = " . $parcoursAlias . ".id
				)";
			}

			return "NOT EXISTS (
				SELECT 1
				FROM parcours_prerequisite pr
				WHERE pr.IDparcours = " . $parcoursAlias . ".id
				  AND (
					(SELECT COUNT(DISTINCT pm_total.IDmission)
					 FROM parcours_mission pm_total
					 WHERE pm_total.IDparcours = pr.IDparcours_required) <= 0
					OR
					(SELECT COUNT(DISTINCT CASE WHEN um_done.done IS NOT NULL THEN pm_done.IDmission END)
					 FROM parcours_mission pm_done
					 LEFT JOIN user_mission um_done
						ON um_done.IDmission = pm_done.IDmission
						AND um_done.IDparcours = pr.IDparcours_required
						AND um_done.IDuser = " . $userId . "
					 WHERE pm_done.IDparcours = pr.IDparcours_required)
					<
					(SELECT COUNT(DISTINCT pm_total_compare.IDmission)
					 FROM parcours_mission pm_total_compare
					 WHERE pm_total_compare.IDparcours = pr.IDparcours_required)
				  )
			)";
		}

		public static function resolveOwnerOrganizationIdByParcoursId($parcoursId)
		{
			$parcoursId = (int)$parcoursId;
			if ($parcoursId <= 0) {
				return 0;
			}

			$ownerOrganizationId = (int)self::fetchValue(
				"SELECT COALESCE(
					(SELECT p.IDorganization FROM parcours p WHERE p.id = :parcours_id LIMIT 1),
					(SELECT MIN(op.IDorganization) FROM organization_parcours op WHERE op.IDparcours = :parcours_id_fallback)
				)",
				[
					'parcours_id' => $parcoursId,
					'parcours_id_fallback' => $parcoursId,
				]
			);

			return max(0, $ownerOrganizationId);
		}

		public function getOwnerOrganizationId()
		{
			$ownerOrganizationId = (int)$this->get('IDorganization');
			if ($ownerOrganizationId > 0) {
				return $ownerOrganizationId;
			}

			$ownerOrganizationId = self::resolveOwnerOrganizationIdByParcoursId((int)$this->getId());
			if ($ownerOrganizationId > 0) {
				$this->set('IDorganization', $ownerOrganizationId);
			}

			return $ownerOrganizationId;
		}

		public function isOwnedByOrganization($organizationId)
		{
			return $this->getOwnerOrganizationId() === (int)$organizationId;
		}

		protected static function resolveCurrentUserId()
		{
			return function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
		}

		protected static function resolveCurrentOrganizationId()
		{
			return (int)($_SESSION['currentOrganization'] ?? 0);
		}

		public function save()
		{
			$isNew = (int)$this->getId() <= 0;
			$now = new \DateTimeImmutable();
			$currentUserId = self::resolveCurrentUserId();
			$currentOrganizationId = self::resolveCurrentOrganizationId();

			if ($isNew) {
				if ((int)$this->get('IDorganization') <= 0 && $currentOrganizationId > 0) {
					$this->set('IDorganization', $currentOrganizationId);
				}

				if ((int)$this->get('IDusercreation') <= 0 && $currentUserId > 0) {
					$this->set('IDusercreation', $currentUserId);
				}

				if (!$this->get('datecreation')) {
					$this->set('datecreation', $now);
				}
			}

			if ($currentUserId > 0) {
				$this->set('IDusermodification', $currentUserId);
			}

			if (!$this->get('datecreation')) {
				$this->set('datecreation', $now);
			}

			$this->set('datemodification', $now);

			return parent::save();
		}

		public static function loadBasicRows()
		{
			return self::fetchAll(
				"SELECT id
				FROM parcours
				WHERE isbasic = 1
				ORDER BY datecreation ASC, id ASC"
			);
		}

		protected static function buildIsPackSelectSql($alias = 'p')
		{
			$alias = trim((string)$alias) !== '' ? trim((string)$alias) : 'p';
			return self::hasIsPackColumn()
				? "COALESCE(" . $alias . ".ispack, 0) AS ispack"
				: "0 AS ispack";
		}

		public static function instantiateBasicForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Organisation invalide.',
					'createdCount' => 0,
				);
			}

			$rows = self::loadBasicRows();
			if ($rows === false) {
				return array(
					'status' => false,
					'message' => 'Impossible de charger les parcours basic.',
					'createdCount' => 0,
				);
			}

			$createdCount = 0;
			foreach ($rows as $row) {
				$parcoursId = (int)($row['id'] ?? 0);
				if ($parcoursId <= 0) {
					continue;
				}

				$attachResult = \dbObject\OrganizationParcours::attachParcoursToOrganization(
					$organizationId,
					$parcoursId,
					array(
						'everybody' => true,
						'anonymous' => false,
					)
				);
				if (!is_array($attachResult) || empty($attachResult['status'])) {
					return array(
						'status' => false,
						'message' => is_array($attachResult) && !empty($attachResult['message'])
							? (string)$attachResult['message']
							: 'Impossible d instancier un parcours basic.',
						'createdCount' => $createdCount,
					);
				}

				if (!empty($attachResult['created'])) {
					$createdCount++;
				}
			}

			return array(
				'status' => true,
				'createdCount' => $createdCount,
			);
		}

		public static function fetchPackExposureRowForOrganization($organizationId, $parcoursId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$parcoursId;
			if ($organizationId <= 0 || $parcoursId <= 0 || !self::tableExists('parcours_parcours')) {
				return null;
			}

			$row = self::fetchRow(
				"SELECT
					MAX(op_pack.everybody) AS everybody,
					" . (\dbObject\OrganizationParcours::hasAnonymousColumn() ? "MAX(op_pack.anonymous)" : "0") . " AS anonymous
				FROM organization_parcours op_pack
				INNER JOIN parcours parent
					ON parent.id = op_pack.IDparcours
				INNER JOIN parcours_parcours pp
					ON pp.IDparcours_parent = op_pack.IDparcours
				WHERE op_pack.IDorganization = :organization_id
				  AND pp.IDparcours_child = :parcours_id
				  AND " . (self::hasIsPackColumn() ? "COALESCE(parent.ispack, 0) = 1" : "1=1") . "
				GROUP BY pp.IDparcours_child
				LIMIT 1",
				[
					'organization_id' => $organizationId,
					'parcours_id' => $parcoursId,
				]
			);

			return is_array($row) ? $row : null;
		}

		protected static function mergeVisibleParcoursRows(array $directRows, array $packRows)
		{
			$merged = [];

			foreach ($directRows as $row) {
				$parcoursId = (int)($row['id'] ?? 0);
				if ($parcoursId <= 0) {
					continue;
				}

				$merged[$parcoursId] = $row;
			}

			foreach ($packRows as $row) {
				$parcoursId = (int)($row['id'] ?? 0);
				if ($parcoursId <= 0 || array_key_exists($parcoursId, $merged)) {
					continue;
				}

				$merged[$parcoursId] = $row;
			}

			return array_values($merged);
		}

		protected static function fetchDynamicPackChildrenForOrganizationWithProgress($organizationId, $userId, $visibilityMode = 'org', $viewerHasOrganizationAccess = false, $includeHidden = false)
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			if ($organizationId <= 0 || !self::tableExists('parcours_parcours')) {
				return [];
			}

			$hasAnonymousColumn = \dbObject\OrganizationParcours::hasAnonymousColumn();
			$where = [
				"op_pack.IDorganization = :organization_id",
				(self::hasIsPackColumn() ? "COALESCE(parent.ispack, 0) = 1" : "1=1"),
				(self::hasIsPackColumn() ? "COALESCE(child.ispack, 0) = 0" : "1=1"),
				"NOT EXISTS (
					SELECT 1
					FROM organization_parcours op_direct
					WHERE op_direct.IDorganization = :organization_id_direct
					  AND op_direct.IDparcours = child.id
				)",
			];

			if ($visibilityMode === 'everybody') {
				$where[] = "op_pack.everybody = 1";
			} elseif ($visibilityMode === 'public') {
				$where[] = $hasAnonymousColumn ? "(op_pack.everybody = 1 OR op_pack.anonymous = 1)" : "op_pack.everybody = 1";
			} elseif (!$viewerHasOrganizationAccess) {
				if ($hasAnonymousColumn) {
					$where[] = $userId > 0
						? "(op_pack.everybody = 1 OR op_pack.anonymous = 1)"
						: "op_pack.anonymous = 1";
				} else {
					$where[] = "op_pack.everybody = 1";
				}
			}

			if (!$includeHidden && self::hasApplicationColumn()) {
				$where[] = "(child.IDapplication IS NULL OR child.IDapplication <= 0 OR EXISTS (
					SELECT 1
					FROM organization_application oa_app
					INNER JOIN application a_app
						ON a_app.id = oa_app.IDapplication
					WHERE oa_app.IDorganization = :application_visibility_organization_id
					  AND oa_app.IDapplication = child.IDapplication
					  AND oa_app.active = 1
					  AND a_app.active = 1
				))";
			}

			if (!$includeHidden) {
				$where[] = self::buildPrerequisiteVisibilityWhereSql('child', $userId);
			}

			$rows = self::fetchAll(
				"SELECT
					child.id,
					child.title,
					child.description,
					child.image,
					COALESCE(child.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = child.id)) AS owner_organization_id,
					" . (self::hasApplicationColumn() ? "child.IDapplication AS linked_application_id" : "NULL AS linked_application_id") . ",
					" . self::buildIsPackSelectSql('child') . ",
					MIN(COALESCE(op_pack.position, 0)) AS position,
					MIN(COALESCE(pp.position, 0)) AS pack_child_position,
					MAX(op_pack.everybody) AS everybody,
					" . ($hasAnonymousColumn ? "MAX(op_pack.anonymous)" : "0 AS anonymous") . ",
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM organization_parcours op_pack
				INNER JOIN parcours parent
					ON parent.id = op_pack.IDparcours
				INNER JOIN parcours_parcours pp
					ON pp.IDparcours_parent = op_pack.IDparcours
				INNER JOIN parcours child
					ON child.id = pp.IDparcours_child
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = child.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = child.id
					AND lm.IDuser = :user_id
				WHERE " . implode(" AND ", $where) . "
				GROUP BY child.id, child.title, child.description, child.image, child.IDorganization" . (self::hasApplicationColumn() ? ", child.IDapplication" : "") . (self::hasIsPackColumn() ? ", child.ispack" : "") . "
				ORDER BY MIN(COALESCE(op_pack.position, 0)) ASC, MIN(COALESCE(pp.position, 0)) ASC, child.title ASC, child.id ASC",
				(function () use ($organizationId, $userId, $includeHidden) {
					$params = [
						'organization_id' => $organizationId,
						'organization_id_direct' => $organizationId,
						'user_id' => $userId,
					];
					if (!$includeHidden && self::hasApplicationColumn()) {
						$params['application_visibility_organization_id'] = $organizationId;
					}

					return $params;
				})()
			);

			if (!is_array($rows)) {
				return [];
			}

			$rows = self::appendVisibilityFlagsToRows($rows, $organizationId, $userId);
			return self::filterHiddenRows($rows, $includeHidden);
		}

		public static function fetchForOrganizationWithProgress($organizationId, $userId, $viewerHasOrganizationAccess = false, $includeHidden = false) {
			$hasAnonymousColumn = OrganizationParcours::hasAnonymousColumn();
			$where = ["op.IDorganization = :organization_id"];
			if (!$viewerHasOrganizationAccess) {
				if ($hasAnonymousColumn) {
					$where[] = (int)$userId > 0
						? "(op.everybody = 1 OR op.anonymous = 1)"
						: "op.anonymous = 1";
				} else {
					$where[] = "op.everybody = 1";
				}
			}
			if (!$includeHidden && self::hasApplicationColumn()) {
				$where[] = "(p.IDapplication IS NULL OR p.IDapplication <= 0 OR EXISTS (
					SELECT 1
					FROM organization_application oa_app
					INNER JOIN application a_app
						ON a_app.id = oa_app.IDapplication
					WHERE oa_app.IDorganization = :application_visibility_organization_id
					  AND oa_app.IDapplication = p.IDapplication
					  AND oa_app.active = 1
					  AND a_app.active = 1
				))";
			}
			if (!$includeHidden) {
				$where[] = self::buildPrerequisiteVisibilityWhereSql('p', $userId);
			}
			$anonymousSelect = $hasAnonymousColumn ? "op.anonymous" : "0 AS anonymous";
			$anonymousGroupBy = $hasAnonymousColumn ? ", op.anonymous" : "";
			$applicationSelect = self::hasApplicationColumn() ? "p.IDapplication AS linked_application_id" : "NULL AS linked_application_id";
			$applicationGroupBy = self::hasApplicationColumn() ? ", p.IDapplication" : "";
			$isPackSelect = self::buildIsPackSelectSql('p');
			$isPackGroupBy = self::hasIsPackColumn() ? ", p.ispack" : "";
			$ownerOrganizationSelect = "COALESCE(p.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = p.id)) AS owner_organization_id";

			$query = "
				SELECT 
					p.id,
					p.title,
					p.description,
					p.image,
					" . $ownerOrganizationSelect . ",
					" . $applicationSelect . ",
					" . $isPackSelect . ",
					op.position,
					op.everybody,
					" . $anonymousSelect . ",
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE 
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM organization_parcours op
				INNER JOIN parcours p
					ON p.id = op.IDparcours
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = p.id
					AND lm.IDuser = :user_id
				WHERE " . implode(" AND ", $where) . "
				GROUP BY p.id, p.title, p.description, p.image, p.IDorganization" . $applicationGroupBy . $isPackGroupBy . ", op.position, op.everybody" . $anonymousGroupBy . "
				ORDER BY op.position ASC, p.title ASC
			";

			$params = [
				'user_id' => (int)$userId,
				'organization_id' => (int)$organizationId,
			];
			if (!$includeHidden && self::hasApplicationColumn()) {
				$params['application_visibility_organization_id'] = (int)$organizationId;
			}

			$rows = self::fetchAll($query, $params);

			if (!is_array($rows)) {
				return [];
			}

			$rows = self::appendVisibilityFlagsToRows($rows, $organizationId, $userId);
			$rows = self::filterHiddenRows($rows, $includeHidden);
			$packRows = self::fetchDynamicPackChildrenForOrganizationWithProgress($organizationId, $userId, 'org', $viewerHasOrganizationAccess, $includeHidden);
			return self::mergeVisibleParcoursRows($rows, $packRows);
		}

		public static function fetchEverybodyForOrganizationWithProgress($organizationId, $userId = 0)
		{
			$hasAnonymousColumn = OrganizationParcours::hasAnonymousColumn();
			$anonymousSelect = $hasAnonymousColumn ? "op.anonymous" : "0 AS anonymous";
			$anonymousGroupBy = $hasAnonymousColumn ? ", op.anonymous" : "";
			$applicationSelect = self::hasApplicationColumn() ? "p.IDapplication AS linked_application_id" : "NULL AS linked_application_id";
			$applicationGroupBy = self::hasApplicationColumn() ? ", p.IDapplication" : "";
			$isPackSelect = self::buildIsPackSelectSql('p');
			$isPackGroupBy = self::hasIsPackColumn() ? ", p.ispack" : "";
			$ownerOrganizationSelect = "COALESCE(p.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = p.id)) AS owner_organization_id";

			$query = "
				SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					" . $ownerOrganizationSelect . ",
					" . $applicationSelect . ",
					" . $isPackSelect . ",
					op.position,
					op.everybody,
					" . $anonymousSelect . ",
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM organization_parcours op
				INNER JOIN parcours p
					ON p.id = op.IDparcours
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = p.id
					AND lm.IDuser = :user_id
				WHERE op.IDorganization = :organization_id
				  AND op.everybody = 1
				  AND (
					" . (self::hasApplicationColumn()
						? "p.IDapplication IS NULL OR p.IDapplication <= 0 OR EXISTS (
							SELECT 1
							FROM organization_application oa_app
							INNER JOIN application a_app
								ON a_app.id = oa_app.IDapplication
							WHERE oa_app.IDorganization = :application_visibility_organization_id
							  AND oa_app.IDapplication = p.IDapplication
							  AND oa_app.active = 1
							  AND a_app.active = 1
						)"
						: "1=1") . "
				  )
				  AND " . self::buildPrerequisiteVisibilityWhereSql('p', $userId) . "
				GROUP BY p.id, p.title, p.description, p.image, p.IDorganization" . $applicationGroupBy . $isPackGroupBy . ", op.position, op.everybody" . $anonymousGroupBy . "
				ORDER BY op.position ASC, p.title ASC
			";

			$rows = self::fetchAll($query, [
				'user_id' => (int)$userId,
				'organization_id' => (int)$organizationId,
				'application_visibility_organization_id' => (int)$organizationId,
			]);

			if (!is_array($rows)) {
				return [];
			}

			$rows = self::appendVisibilityFlagsToRows($rows, $organizationId, $userId);
			$rows = self::filterHiddenRows($rows, false);
			$packRows = self::fetchDynamicPackChildrenForOrganizationWithProgress($organizationId, $userId, 'everybody', false, false);
			return self::mergeVisibleParcoursRows($rows, $packRows);
		}

		public static function fetchPublicForOrganizationWithProgress($organizationId, $userId = 0)
		{
			$hasAnonymousColumn = OrganizationParcours::hasAnonymousColumn();
			$anonymousSelect = $hasAnonymousColumn ? "op.anonymous" : "0 AS anonymous";
			$anonymousGroupBy = $hasAnonymousColumn ? ", op.anonymous" : "";
			$applicationSelect = self::hasApplicationColumn() ? "p.IDapplication AS linked_application_id" : "NULL AS linked_application_id";
			$applicationGroupBy = self::hasApplicationColumn() ? ", p.IDapplication" : "";
			$isPackSelect = self::buildIsPackSelectSql('p');
			$isPackGroupBy = self::hasIsPackColumn() ? ", p.ispack" : "";
			$ownerOrganizationSelect = "COALESCE(p.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = p.id)) AS owner_organization_id";

			$query = "
				SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					" . $ownerOrganizationSelect . ",
					" . $applicationSelect . ",
					" . $isPackSelect . ",
					op.position,
					op.everybody,
					" . $anonymousSelect . ",
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM organization_parcours op
				INNER JOIN parcours p
					ON p.id = op.IDparcours
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = p.id
					AND lm.IDuser = :user_id
				WHERE op.IDorganization = :organization_id
				  AND " . ($hasAnonymousColumn ? "(op.everybody = 1 OR op.anonymous = 1)" : "op.everybody = 1") . "
				  AND (
					" . (self::hasApplicationColumn()
						? "p.IDapplication IS NULL OR p.IDapplication <= 0 OR EXISTS (
							SELECT 1
							FROM organization_application oa_app
							INNER JOIN application a_app
								ON a_app.id = oa_app.IDapplication
							WHERE oa_app.IDorganization = :application_visibility_organization_id
							  AND oa_app.IDapplication = p.IDapplication
							  AND oa_app.active = 1
							  AND a_app.active = 1
						)"
						: "1=1") . "
				  )
				  AND " . self::buildPrerequisiteVisibilityWhereSql('p', $userId) . "
				GROUP BY p.id, p.title, p.description, p.image, p.IDorganization" . $applicationGroupBy . $isPackGroupBy . ", op.position, op.everybody" . $anonymousGroupBy . "
				ORDER BY op.position ASC, p.title ASC
			";

			$rows = self::fetchAll($query, [
				'user_id' => (int)$userId,
				'organization_id' => (int)$organizationId,
				'application_visibility_organization_id' => (int)$organizationId,
			]);

			if (!is_array($rows)) {
				return [];
			}

			$rows = self::appendVisibilityFlagsToRows($rows, $organizationId, $userId);
			$rows = self::filterHiddenRows($rows, false);
			$packRows = self::fetchDynamicPackChildrenForOrganizationWithProgress($organizationId, $userId, 'public', false, false);
			return self::mergeVisibleParcoursRows($rows, $packRows);
		}

		public static function fetchBasicCatalogWithProgress($userId = 0, array $completedParcoursIds = [])
		{
			$userId = (int)$userId;
			$completedParcoursIds = self::normalizePositiveIds($completedParcoursIds);
			$where = [
				"(p.ispublic = 1 OR p.isbasic = 1)",
				self::buildPrerequisiteVisibilityWhereSql('p', $userId, $completedParcoursIds),
			];
			if (self::hasApplicationColumn()) {
				$where[] = "(p.IDapplication IS NULL OR p.IDapplication <= 0)";
			}

			$query = "
				SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					COALESCE(p.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = p.id)) AS owner_organization_id,
					" . (self::hasApplicationColumn() ? "p.IDapplication AS linked_application_id" : "NULL AS linked_application_id") . ",
					" . self::buildIsPackSelectSql('p') . ",
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM parcours p
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = p.id
					AND lm.IDuser = :user_id
				WHERE " . implode(" AND ", $where) . "
				GROUP BY p.id, p.title, p.description, p.image, p.IDorganization" . (self::hasApplicationColumn() ? ", p.IDapplication" : "") . (self::hasIsPackColumn() ? ", p.ispack" : "") . "
				ORDER BY p.title ASC, p.id ASC
			";

			$rows = self::fetchAll($query, [
				'user_id' => $userId,
			]);

			if (!is_array($rows)) {
				return [];
			}

			$rows = self::appendVisibilityFlagsToRows($rows, 0, $userId, $completedParcoursIds);
			return self::filterHiddenRows($rows, false);
		}

		public static function resolveBasicCatalogAccessContext($parcoursId, $userId = 0)
		{
			$parcoursId = (int)$parcoursId;
			$userId = (int)$userId;
			if ($parcoursId <= 0) {
				return [
					'exists' => false,
					'canView' => false,
					'userId' => $userId,
					'isLoggedIn' => $userId > 0,
					'hasOrganizationAccess' => false,
					'everybody' => false,
					'anonymous' => false,
					'isBasicCatalog' => true,
				];
			}

			$row = self::fetchRow(
				"SELECT
					id,
					ispublic,
					isbasic,
					" . (self::hasApplicationColumn() ? "IDapplication" : "NULL AS IDapplication") . "
				FROM parcours
				WHERE id = :parcours_id
				LIMIT 1",
				[
					'parcours_id' => $parcoursId,
				]
			);

			$exists = is_array($row) && (int)($row['id'] ?? 0) > 0;
			$isPublic = $exists && !empty($row['ispublic']);
			$isBasic = $exists && !empty($row['isbasic']);
			$hasLinkedApplication = self::hasApplicationColumn() && (int)($row['IDapplication'] ?? 0) > 0;
			$canView = ($isPublic || $isBasic) && !$hasLinkedApplication;

			return [
				'exists' => $exists,
				'canView' => $canView,
				'canTrackProgress' => $canView,
				'canTrackProgressLocally' => $userId <= 0 && $canView,
				'userId' => $userId,
				'isLoggedIn' => $userId > 0,
				'hasOrganizationAccess' => false,
				'everybody' => $isPublic || $isBasic,
				'anonymous' => $userId <= 0 && ($isPublic || $isBasic),
				'isBasicCatalog' => true,
			];
		}

		public static function fetchImportableForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					p.ispublic,
					p.isbasic,
					" . self::buildIsPackSelectSql('p') . ",
					p.IDorganization,
					owner.name AS owner_name,
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COUNT(DISTINCT pp_child.IDparcours_child) AS total_parcours
				FROM parcours p
				LEFT JOIN organization owner
					ON owner.id = p.IDorganization
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN parcours_parcours pp_child
					ON pp_child.IDparcours_parent = p.id
				WHERE (p.ispublic = 1 OR p.isbasic = 1)
				  AND EXISTS (
					SELECT 1
					FROM organization_parcours op_owner_link
					WHERE op_owner_link.IDparcours = p.id
					  AND op_owner_link.IDorganization = COALESCE(
						p.IDorganization,
						(SELECT MIN(op_owner_fallback.IDorganization)
						 FROM organization_parcours op_owner_fallback
						 WHERE op_owner_fallback.IDparcours = p.id)
					  )
				  )
				  AND NOT EXISTS (
					SELECT 1
					FROM organization_parcours op
					WHERE op.IDorganization = :organization_id
					  AND op.IDparcours = p.id
				  )
				GROUP BY
					p.id,
					p.title,
					p.description,
					p.image,
					p.ispublic,
					p.isbasic,
					" . (self::hasIsPackColumn() ? "p.ispack," : "") . "
					p.IDorganization,
					owner.name
				ORDER BY p.isbasic DESC, p.ispublic DESC, p.title ASC, p.id ASC",
				[
					'organization_id' => $organizationId,
				]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function loadImportableForOrganization($organizationId, $parcoursId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$parcoursId;
			if ($organizationId <= 0 || $parcoursId <= 0) {
				return false;
			}

			return self::fetchRow(
				"SELECT
					p.id,
					p.title,
					p.ispublic,
					p.isbasic,
					" . self::buildIsPackSelectSql('p') . "
				FROM parcours p
				WHERE p.id = :parcours_id
				  AND (p.ispublic = 1 OR p.isbasic = 1)
				  AND EXISTS (
					SELECT 1
					FROM organization_parcours op_owner_link
					WHERE op_owner_link.IDparcours = p.id
					  AND op_owner_link.IDorganization = COALESCE(
						p.IDorganization,
						(SELECT MIN(op_owner_fallback.IDorganization)
						 FROM organization_parcours op_owner_fallback
						 WHERE op_owner_fallback.IDparcours = p.id)
					  )
				  )
				  AND NOT EXISTS (
					SELECT 1
					FROM organization_parcours op
					WHERE op.IDorganization = :organization_id
					  AND op.IDparcours = p.id
				  )
				LIMIT 1",
				[
					'organization_id' => $organizationId,
					'parcours_id' => $parcoursId,
				]
			);
		}

		public static function countRestrictedForPublicCatalog($organizationId)
		{
			$query = "
				SELECT COUNT(*)
				FROM organization_parcours
				WHERE IDorganization = :organization_id
				  AND (everybody IS NULL OR everybody = 0)
			";

			return (int)self::fetchValue($query, [
				'organization_id' => (int)$organizationId,
			]);
		}

		public static function fetchAvailableFaqTargetsForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT
					p.id,
					p.title
				FROM organization_parcours op
				INNER JOIN parcours p
					ON p.id = op.IDparcours
				WHERE op.IDorganization = :organization_id
				  AND " . (self::hasIsPackColumn() ? "COALESCE(p.ispack, 0) = 0" : "1=1") . "
				ORDER BY p.title ASC, p.id ASC",
				[
					'organization_id' => $organizationId,
				]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function fetchAvailableFaqTargetIdsForOrganization($organizationId)
		{
			$rows = self::fetchAvailableFaqTargetsForOrganization($organizationId);
			$ids = [];

			foreach ($rows as $row) {
				$parcoursId = (int)($row['id'] ?? 0);
				if ($parcoursId > 0) {
					$ids[] = $parcoursId;
				}
			}

			return array_values(array_unique($ids));
		}

		public static function fetchOwnedFaqTargetsForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT
					p.id,
					p.title
				FROM parcours p
				WHERE COALESCE(p.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = p.id)) = :organization_id
				  AND " . (self::hasIsPackColumn() ? "COALESCE(p.ispack, 0) = 0" : "1=1") . "
				ORDER BY p.title ASC, p.id ASC",
				[
					'organization_id' => $organizationId,
				]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function fetchOwnedFaqTargetIdsForOrganization($organizationId)
		{
			$rows = self::fetchOwnedFaqTargetsForOrganization($organizationId);
			$ids = [];

			foreach ($rows as $row) {
				$parcoursId = (int)($row['id'] ?? 0);
				if ($parcoursId > 0) {
					$ids[] = $parcoursId;
				}
			}

			return array_values(array_unique($ids));
		}

		public static function fetchAvailablePackTargetsForOrganization($organizationId, $parentParcoursId = 0)
		{
			$organizationId = (int)$organizationId;
			$parentParcoursId = (int)$parentParcoursId;
			if ($organizationId <= 0) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					p.IDapplication
				FROM parcours p
				WHERE COALESCE(p.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = p.id), 0) = :organization_id
				  AND " . (self::hasIsPackColumn() ? "COALESCE(p.ispack, 0) = 0" : "1=1") . "
				  AND p.id <> :parent_parcours_id
				  AND NOT EXISTS (
					SELECT 1
					FROM parcours_parcours pp
					WHERE pp.IDparcours_parent = :linked_parent_parcours_id
					  AND pp.IDparcours_child = p.id
				  )
				ORDER BY p.title ASC, p.id ASC",
				[
					'organization_id' => $organizationId,
					'parent_parcours_id' => $parentParcoursId,
					'linked_parent_parcours_id' => $parentParcoursId,
				]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function fetchAvailablePrerequisiteTargetsForOrganization($organizationId, $parcoursId = 0)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$parcoursId;
			if ($organizationId <= 0) {
				return [];
			}

			$alreadyLinkedSql = self::hasPrerequisiteTable()
				? "AND NOT EXISTS (
					SELECT 1
					FROM parcours_prerequisite pp
					WHERE pp.IDparcours = :linked_parcours_id
					  AND pp.IDparcours_required = p.id
				  )"
				: "";
			$params = [
				'organization_id' => $organizationId,
				'parcours_id' => $parcoursId,
			];
			if (self::hasPrerequisiteTable()) {
				$params['linked_parcours_id'] = $parcoursId;
			}

			$rows = self::fetchAll(
				"SELECT
					p.id,
					p.title,
					p.description,
					p.image
				FROM organization_parcours op
				INNER JOIN parcours p
					ON p.id = op.IDparcours
				WHERE op.IDorganization = :organization_id
				  AND " . (self::hasIsPackColumn() ? "COALESCE(p.ispack, 0) = 0" : "1=1") . "
				  AND p.id <> :parcours_id
				  " . $alreadyLinkedSql . "
				ORDER BY p.title ASC, p.id ASC",
				$params
			);

			return is_array($rows) ? $rows : [];
		}

		public static function fetchPackChildrenForOrganizationWithProgress($organizationId, $packParcoursId, $userId, $viewerHasOrganizationAccess = false, $includeHidden = false)
		{
			$organizationId = (int)$organizationId;
			$packParcoursId = (int)$packParcoursId;
			$userId = (int)$userId;
			if ($organizationId <= 0 || $packParcoursId <= 0) {
				return [];
			}

			$hasAnonymousColumn = \dbObject\OrganizationParcours::hasAnonymousColumn();
			$where = [
				"pp.IDparcours_parent = :pack_parcours_id",
				"op_pack.IDorganization = :organization_id",
			];
			if (!$viewerHasOrganizationAccess) {
				if ($hasAnonymousColumn) {
					$where[] = $userId > 0
						? "(op_pack.everybody = 1 OR op_pack.anonymous = 1)"
						: "op_pack.anonymous = 1";
				} else {
					$where[] = "op_pack.everybody = 1";
				}
			}
			if (!$includeHidden && self::hasApplicationColumn()) {
				$where[] = "(child.IDapplication IS NULL OR child.IDapplication <= 0 OR EXISTS (
					SELECT 1
					FROM organization_application oa_app
					INNER JOIN application a_app
						ON a_app.id = oa_app.IDapplication
					WHERE oa_app.IDorganization = :application_visibility_organization_id
					  AND oa_app.IDapplication = child.IDapplication
					  AND oa_app.active = 1
					  AND a_app.active = 1
				))";
			}
			if (!$includeHidden) {
				$where[] = self::buildPrerequisiteVisibilityWhereSql('child', $userId);
			}
			$rows = self::fetchAll(
				"SELECT
					child.id,
					child.title,
					child.description,
					child.image,
					COALESCE(child.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = child.id)) AS owner_organization_id,
					" . (self::hasApplicationColumn() ? "child.IDapplication AS linked_application_id" : "NULL AS linked_application_id") . ",
					" . self::buildIsPackSelectSql('child') . ",
					pp.position,
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM parcours_parcours pp
				INNER JOIN organization_parcours op_pack
					ON op_pack.IDparcours = pp.IDparcours_parent
				INNER JOIN parcours child
					ON child.id = pp.IDparcours_child
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = child.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = child.id
					AND lm.IDuser = :user_id
				WHERE " . implode(' AND ', $where) . "
				GROUP BY child.id, child.title, child.description, child.image, child.IDorganization" . (self::hasApplicationColumn() ? ", child.IDapplication" : "") . (self::hasIsPackColumn() ? ", child.ispack" : "") . ", pp.position
				ORDER BY COALESCE(pp.position, child.title) ASC, child.title ASC, child.id ASC",
				(function () use ($packParcoursId, $userId, $organizationId, $includeHidden) {
					$params = [
						'pack_parcours_id' => $packParcoursId,
						'user_id' => $userId,
						'organization_id' => $organizationId,
					];
					if (!$includeHidden && self::hasApplicationColumn()) {
						$params['application_visibility_organization_id'] = $organizationId;
					}

					return $params;
				})()
			);

			if (!is_array($rows)) {
				return [];
			}

			$rows = self::appendVisibilityFlagsToRows($rows, $organizationId, $userId);
			return self::filterHiddenRows($rows, $includeHidden);
		}

		public static function attachOwnedPackChildrenToOrganization($organizationId, $packParcoursId)
		{
			return [
				'status' => true,
				'attachedCount' => 0,
			];
		}

		public static function cleanupLegacyDetachedChildLinksAcrossOrganizations($packParcoursId, $childParcoursId)
		{
			$packParcoursId = (int)$packParcoursId;
			$childParcoursId = (int)$childParcoursId;
			if ($packParcoursId <= 0 || $childParcoursId <= 0) {
				return [
					'status' => false,
					'message' => 'Pack ou parcours invalide.',
					'detachedCount' => 0,
				];
			}

			$ownerOrganizationId = self::resolveOwnerOrganizationIdByParcoursId($childParcoursId);
			$organizationRows = self::fetchAll(
				"SELECT DISTINCT IDorganization
				FROM organization_parcours
				WHERE IDparcours = :pack_parcours_id",
				[
					'pack_parcours_id' => $packParcoursId,
				]
			);
			if (!is_array($organizationRows)) {
				return [
					'status' => false,
					'message' => 'Impossible de charger les organisations liees au pack.',
					'detachedCount' => 0,
				];
			}

			$detachedCount = 0;
			foreach ($organizationRows as $organizationRow) {
				$organizationId = (int)($organizationRow['IDorganization'] ?? 0);
				if ($organizationId <= 0 || $organizationId === $ownerOrganizationId) {
					continue;
				}

				$hasDirectChildLink = (int)self::fetchValue(
					"SELECT COUNT(*)
					FROM organization_parcours
					WHERE IDorganization = :organization_id
					  AND IDparcours = :child_parcours_id",
					[
						'organization_id' => $organizationId,
						'child_parcours_id' => $childParcoursId,
					]
				) > 0;
				if (!$hasDirectChildLink) {
					continue;
				}

				$stillProvidedByAnotherPack = (int)self::fetchValue(
					"SELECT COUNT(*)
					FROM organization_parcours op_pack
					INNER JOIN parcours_parcours pp
						ON pp.IDparcours_parent = op_pack.IDparcours
					INNER JOIN parcours parent
						ON parent.id = op_pack.IDparcours
					WHERE op_pack.IDorganization = :organization_id
					  AND pp.IDparcours_child = :child_parcours_id
					  AND op_pack.IDparcours <> :pack_parcours_id
					  AND " . (self::hasIsPackColumn() ? "COALESCE(parent.ispack, 0) = 1" : "1=1"),
					[
						'organization_id' => $organizationId,
						'child_parcours_id' => $childParcoursId,
						'pack_parcours_id' => $packParcoursId,
					]
				) > 0;
				if ($stillProvidedByAnotherPack) {
					continue;
				}

				$deleteResult = self::execute(
					"DELETE FROM organization_parcours
					WHERE IDorganization = :organization_id
					  AND IDparcours = :child_parcours_id",
					[
						'organization_id' => $organizationId,
						'child_parcours_id' => $childParcoursId,
					]
				);
				if ($deleteResult === false) {
					return [
						'status' => false,
						'message' => 'Impossible de nettoyer un ancien lien direct de parcours.',
						'detachedCount' => $detachedCount,
					];
				}

				$detachedCount++;
			}

			return [
				'status' => true,
				'detachedCount' => $detachedCount,
			];
		}

		protected static function detachPackChildrenFromOrganization($organizationId, $packParcoursId)
		{
			$organizationId = (int)$organizationId;
			$packParcoursId = (int)$packParcoursId;
			if ($organizationId <= 0 || $packParcoursId <= 0 || !self::tableExists('parcours_parcours')) {
				return [
					'status' => true,
					'detachedCount' => 0,
				];
			}

			$fallbackRows = self::fetchAll(
				"SELECT
					child.id,
					COALESCE(child.IDorganization, (SELECT MIN(op_owner.IDorganization) FROM organization_parcours op_owner WHERE op_owner.IDparcours = child.id), 0) AS owner_organization_id
				FROM parcours_parcours pp
				INNER JOIN parcours child
					ON child.id = pp.IDparcours_child
				WHERE pp.IDparcours_parent = :pack_parcours_id
				  AND EXISTS (
					SELECT 1
					FROM organization_parcours op_child
					WHERE op_child.IDorganization = :organization_id
					  AND op_child.IDparcours = child.id
				  )
				  AND " . (self::hasIsPackColumn() ? "COALESCE(child.ispack, 0) = 0" : "1=1"),
				[
					'pack_parcours_id' => $packParcoursId,
					'organization_id' => $organizationId,
				]
			);
			if (!is_array($fallbackRows)) {
				return [
					'status' => false,
					'message' => 'Impossible de charger les parcours herites du pack.',
				];
			}

			$detachedCount = 0;
			foreach ($fallbackRows as $row) {
				$childParcoursId = (int)($row['id'] ?? 0);
				$ownerOrganizationId = (int)($row['owner_organization_id'] ?? 0);
				if ($childParcoursId <= 0 || $ownerOrganizationId === $organizationId) {
					continue;
				}

				$usedByAnotherAttachedPack = (int)self::fetchValue(
					"SELECT COUNT(*)
					FROM organization_parcours op_pack
					INNER JOIN parcours_parcours pp
						ON pp.IDparcours_parent = op_pack.IDparcours
					WHERE op_pack.IDorganization = :organization_id
					  AND op_pack.IDparcours <> :pack_parcours_id
					  AND pp.IDparcours_child = :child_parcours_id",
					[
						'organization_id' => $organizationId,
						'pack_parcours_id' => $packParcoursId,
						'child_parcours_id' => $childParcoursId,
					]
				) > 0;
				if ($usedByAnotherAttachedPack) {
					continue;
				}

				$deleteResult = self::execute(
					"DELETE FROM organization_parcours
					WHERE IDorganization = :organization_id
					  AND IDparcours = :parcours_id",
					[
						'organization_id' => $organizationId,
						'parcours_id' => $childParcoursId,
					]
				);
				if ($deleteResult === false) {
					return [
						'status' => false,
						'message' => 'Impossible de detacher un parcours du pack.',
					];
				}

				$detachedCount++;
			}

			return [
				'status' => true,
				'detachedCount' => $detachedCount,
			];
		}

		protected static function fetchSingleColumnIds($query, array $params, $columnName)
		{
			$rows = self::fetchAll($query, $params);
			if (!is_array($rows)) {
				return [];
			}

			$ids = [];
			foreach ($rows as $row) {
				$value = (int)($row[$columnName] ?? 0);
				if ($value > 0) {
					$ids[$value] = $value;
				}
			}

			return array_values($ids);
		}

		protected static function deleteQuestionIfUnused($questionId)
		{
			$questionId = (int)$questionId;
			if ($questionId <= 0) {
				return false;
			}

			if (
				self::tableExists('mission_question')
				&& (int)self::fetchValue(
					"SELECT COUNT(*) FROM mission_question WHERE IDquestion = :question_id",
					['question_id' => $questionId]
				) > 0
			) {
				return false;
			}

			if (self::tableExists('user_question_response')) {
				$result = self::execute(
					"DELETE FROM user_question_response WHERE IDquestion = :question_id",
					['question_id' => $questionId]
				);
				if ($result === false) {
					throw new \RuntimeException('question_response_delete_failed');
				}
			}

			if (self::tableExists('question_choice')) {
				$result = self::execute(
					"DELETE FROM question_choice WHERE IDquestion = :question_id",
					['question_id' => $questionId]
				);
				if ($result === false) {
					throw new \RuntimeException('question_choice_delete_failed');
				}
			}

			$question = new \dbObject\Question();
			if ($question->load($questionId) && !$question->delete()) {
				throw new \RuntimeException('question_delete_failed');
			}

			return true;
		}

		protected static function deleteHomeworkIfUnused($homeworkId)
		{
			$homeworkId = (int)$homeworkId;
			if ($homeworkId <= 0) {
				return false;
			}

			if (
				self::tableExists('mission_homework')
				&& (int)self::fetchValue(
					"SELECT COUNT(*) FROM mission_homework WHERE IDhomework = :homework_id",
					['homework_id' => $homeworkId]
				) > 0
			) {
				return false;
			}

			if (self::tableExists('user_homework')) {
				$result = self::execute(
					"DELETE FROM user_homework WHERE IDhomework = :homework_id",
					['homework_id' => $homeworkId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_homework_by_homework_delete_failed');
				}
			}

			$homework = new \dbObject\Homework();
			if ($homework->load($homeworkId) && !$homework->delete()) {
				throw new \RuntimeException('homework_delete_failed');
			}

			return true;
		}

		protected static function deleteMissionIfUnused($missionId)
		{
			$missionId = (int)$missionId;
			if ($missionId <= 0) {
				return [
					'deleted' => false,
					'deletedQuestions' => 0,
					'deletedHomeworks' => 0,
				];
			}

			if (
				self::tableExists('parcours_mission')
				&& (int)self::fetchValue(
					"SELECT COUNT(*) FROM parcours_mission WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				) > 0
			) {
				return [
					'deleted' => false,
					'deletedQuestions' => 0,
					'deletedHomeworks' => 0,
				];
			}

			$questionIds = self::tableExists('mission_question')
				? self::fetchSingleColumnIds(
					"SELECT IDquestion FROM mission_question WHERE IDmission = :mission_id",
					['mission_id' => $missionId],
					'IDquestion'
				)
				: [];
			$homeworkIds = self::tableExists('mission_homework')
				? self::fetchSingleColumnIds(
					"SELECT IDhomework FROM mission_homework WHERE IDmission = :mission_id",
					['mission_id' => $missionId],
					'IDhomework'
				)
				: [];

			if (self::tableExists('user_question_response')) {
				$result = self::execute(
					"DELETE FROM user_question_response WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_question_response_delete_failed');
				}
			}

			if (self::tableExists('user_homework')) {
				$result = self::execute(
					"DELETE FROM user_homework WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_homework_by_mission_delete_failed');
				}
			}

			if (self::tableExists('user_mission')) {
				$result = self::execute(
					"DELETE FROM user_mission WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_mission_by_mission_delete_failed');
				}
			}

			if (self::tableExists('mission_dependencies')) {
				$result = self::execute(
					"DELETE FROM mission_dependencies
					WHERE IDmission_parent = :mission_id
					   OR IDmission_child = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('mission_dependencies_delete_failed');
				}
			}

			if (self::tableExists('mission_question')) {
				$result = self::execute(
					"DELETE FROM mission_question WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('mission_question_delete_failed');
				}
			}

			if (self::tableExists('mission_homework')) {
				$result = self::execute(
					"DELETE FROM mission_homework WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('mission_homework_delete_failed');
				}
			}

			$mission = new \dbObject\Mission();
			if ($mission->load($missionId) && !$mission->delete()) {
				throw new \RuntimeException('mission_delete_failed');
			}

			$deletedQuestions = 0;
			foreach ($questionIds as $questionId) {
				if (self::deleteQuestionIfUnused($questionId)) {
					$deletedQuestions++;
				}
			}

			$deletedHomeworks = 0;
			foreach ($homeworkIds as $homeworkId) {
				if (self::deleteHomeworkIfUnused($homeworkId)) {
					$deletedHomeworks++;
				}
			}

			return [
				'deleted' => true,
				'deletedQuestions' => $deletedQuestions,
				'deletedHomeworks' => $deletedHomeworks,
			];
		}

		public function previewDeleteForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$this->getId();
			$ownerOrganizationId = $this->getOwnerOrganizationId();

			if ($organizationId <= 0 || $parcoursId <= 0) {
				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Parcours ou organisation invalide.',
				];
			}

			$link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
			if (!$link instanceof \dbObject\OrganizationParcours) {
				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Ce parcours n est pas rattache a l organisation courante.',
				];
			}

			if (self::hasAttachedPackParentInOrganization($organizationId, $parcoursId)) {
				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Ce parcours est actuellement fourni par un pack rattache a votre organisation. Detachez d abord le pack parent.',
				];
			}

			if ($ownerOrganizationId <= 0 || $ownerOrganizationId !== $organizationId) {
				return [
					'status' => true,
					'action' => 'detach',
					'message' => 'Ce parcours appartient a une autre organisation. Il sera seulement detache de votre organisation.',
					'confirmMessage' => 'Ce parcours appartient a une autre organisation.' . "\n\n" . 'Vous ne pouvez pas le supprimer definitivement.' . "\n\n" . 'Il sera seulement detache de votre organisation.' . "\n\n" . 'Voulez vous continuer ?',
					'totalOrganizationCount' => 0,
					'otherOrganizationCount' => 0,
					'isOwner' => false,
				];
			}

			$totalOrganizationCount = (int)self::fetchValue(
				"SELECT COUNT(*)
				FROM organization_parcours
				WHERE IDparcours = :parcours_id",
				['parcours_id' => $parcoursId]
			);
			$otherOrganizationCount = max(0, $totalOrganizationCount - 1);

			if ($otherOrganizationCount > 0) {
				return [
					'status' => true,
					'action' => 'detach',
					'message' => $otherOrganizationCount === 1
						? 'Ce parcours est utilise par 1 autre organisation. Il sera seulement detache de votre organisation.'
						: 'Ce parcours est utilise par ' . $otherOrganizationCount . ' autres organisations. Il sera seulement detache de votre organisation.',
					'confirmMessage' => $otherOrganizationCount === 1
						? 'Ce parcours est utilise par 1 autre organisation.' . "\n\n" . 'Il sera seulement detache de votre organisation.' . "\n\n" . 'Voulez vous continuer ?'
						: 'Ce parcours est utilise par ' . $otherOrganizationCount . ' autres organisations.' . "\n\n" . 'Il sera seulement detache de votre organisation.' . "\n\n" . 'Voulez vous continuer ?',
					'totalOrganizationCount' => $totalOrganizationCount,
					'otherOrganizationCount' => $otherOrganizationCount,
					'isOwner' => true,
				];
			}

			return [
				'status' => true,
				'action' => 'delete',
				'message' => 'Ce parcours n est utilise que dans votre organisation. Il sera supprime definitivement avec ses elements non reutilises.',
				'confirmMessage' => 'Ce parcours n est utilise que dans votre organisation.' . "\n\n" . 'Il sera supprime definitivement, avec ses missions, questions et devoirs devenus orphelins.' . "\n\n" . 'Etes vous sur de vouloir continuer ?',
				'totalOrganizationCount' => $totalOrganizationCount,
				'otherOrganizationCount' => 0,
				'isOwner' => true,
			];
		}

		public function deleteForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$this->getId();
			$preview = $this->previewDeleteForOrganization($organizationId);
			if (!is_array($preview) || empty($preview['status'])) {
				return is_array($preview)
					? $preview
					: [
						'status' => false,
						'action' => 'none',
						'message' => 'Impossible de preparer la suppression de ce parcours.',
					];
			}

			$pdo = self::getPdo();
			if (!$pdo instanceof \PDO) {
				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Connexion a la base indisponible.',
				];
			}

			$startedTransaction = !$pdo->inTransaction();

			try {
				if ($startedTransaction) {
					$pdo->beginTransaction();
				}

				if ($this->isPack()) {
					$packDetachResult = self::detachPackChildrenFromOrganization($organizationId, $parcoursId);
					if (!is_array($packDetachResult) || empty($packDetachResult['status'])) {
						throw new \RuntimeException('pack_children_detach_failed');
					}
				}

				$missionIds = self::tableExists('parcours_mission')
					? self::fetchSingleColumnIds(
						"SELECT IDmission FROM parcours_mission WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId],
						'IDmission'
					)
					: [];

				$deleteLinkResult = self::execute(
					"DELETE FROM organization_parcours
					WHERE IDorganization = :organization_id
					  AND IDparcours = :parcours_id",
					[
						'organization_id' => $organizationId,
						'parcours_id' => $parcoursId,
					]
				);
				if ($deleteLinkResult === false) {
					throw new \RuntimeException('organization_parcours_delete_failed');
				}

				if (($preview['action'] ?? 'none') === 'detach' && empty($preview['isOwner'])) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->commit();
					}

					return [
						'status' => true,
						'action' => 'detach',
						'message' => 'Le parcours a ete detache de votre organisation.',
						'remainingOrganizationCount' => 0,
						'deletedMissionCount' => 0,
						'deletedQuestionCount' => 0,
						'deletedHomeworkCount' => 0,
					];
				}

				$remainingOrganizationCount = (int)self::fetchValue(
					"SELECT COUNT(*)
					FROM organization_parcours
					WHERE IDparcours = :parcours_id",
					['parcours_id' => $parcoursId]
				);

				if ($remainingOrganizationCount > 0) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->commit();
					}

					return [
						'status' => true,
						'action' => 'detach',
						'message' => $remainingOrganizationCount === 1
							? 'Le parcours a ete detache de votre organisation. Il reste utilise dans 1 autre organisation.'
							: 'Le parcours a ete detache de votre organisation. Il reste utilise dans ' . $remainingOrganizationCount . ' autres organisations.',
						'remainingOrganizationCount' => $remainingOrganizationCount,
						'deletedMissionCount' => 0,
						'deletedQuestionCount' => 0,
						'deletedHomeworkCount' => 0,
					];
				}

				if (self::tableExists('user_homework')) {
					$result = self::execute(
						"DELETE FROM user_homework WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('user_homework_by_parcours_delete_failed');
					}
				}

				if (self::tableExists('user_mission')) {
					$result = self::execute(
						"DELETE FROM user_mission WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('user_mission_by_parcours_delete_failed');
					}
				}

				if (self::tableExists('mission_dependencies')) {
					$result = self::execute(
						"DELETE FROM mission_dependencies WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('mission_dependencies_by_parcours_delete_failed');
					}
				}

				if (self::tableExists('parcours_mission')) {
					$result = self::execute(
						"DELETE FROM parcours_mission WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('parcours_mission_delete_failed');
					}
				}

				if (self::tableExists('parcours_parcours')) {
					$result = self::execute(
						"DELETE FROM parcours_parcours
						WHERE IDparcours_parent = :parcours_id_parent
						   OR IDparcours_child = :parcours_id_child",
						[
							'parcours_id_parent' => $parcoursId,
							'parcours_id_child' => $parcoursId,
						]
					);
					if ($result === false) {
						throw new \RuntimeException('parcours_parcours_delete_failed');
					}
				}

				if (self::tableExists('organization_parcours_pack_source')) {
					$result = self::execute(
						"DELETE FROM organization_parcours_pack_source
						WHERE IDparcours_parent = :parcours_id_parent
						   OR IDparcours_child = :parcours_id_child",
						[
							'parcours_id_parent' => $parcoursId,
							'parcours_id_child' => $parcoursId,
						]
					);
					if ($result === false) {
						throw new \RuntimeException('organization_parcours_pack_source_delete_failed');
					}
				}

				if (self::hasPrerequisiteTable()) {
					$result = self::execute(
						"DELETE FROM parcours_prerequisite
						WHERE IDparcours = :parcours_id_target
						   OR IDparcours_required = :parcours_id_required",
						[
							'parcours_id_target' => $parcoursId,
							'parcours_id_required' => $parcoursId,
						]
					);
					if ($result === false) {
						throw new \RuntimeException('parcours_prerequisite_delete_failed');
					}
				}

				if (!parent::delete()) {
					throw new \RuntimeException('parcours_delete_failed');
				}

				$deletedMissionCount = 0;
				$deletedQuestionCount = 0;
				$deletedHomeworkCount = 0;
				foreach ($missionIds as $missionId) {
					$missionDeleteResult = self::deleteMissionIfUnused($missionId);
					if (!empty($missionDeleteResult['deleted'])) {
						$deletedMissionCount++;
						$deletedQuestionCount += (int)($missionDeleteResult['deletedQuestions'] ?? 0);
						$deletedHomeworkCount += (int)($missionDeleteResult['deletedHomeworks'] ?? 0);
					}
				}

				if ($startedTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}

				return [
					'status' => true,
					'action' => 'delete',
					'message' => 'Le parcours et ses elements non reutilises ont ete supprimes.',
					'remainingOrganizationCount' => 0,
					'deletedMissionCount' => $deletedMissionCount,
					'deletedQuestionCount' => $deletedQuestionCount,
					'deletedHomeworkCount' => $deletedHomeworkCount,
				];
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Impossible de supprimer ce parcours pour le moment.',
				];
			}
		}
	}

?>
