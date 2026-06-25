<?php
	namespace dbObject;

	class Mission extends DbObject
	{
		public static function tableName()
		{
			return 'mission';
		}

		public static function rules()
		{
			return [
				[['title', 'resume'], 'required'],
				[['id', 'position'], 'integer'],
				[['title', 'video'], 'string'],
				[['resume'], 'text'],
				[['html'], 'html'],
				[['datecreation', 'dateupdate'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'resume' => 'Resume',
				'video' => 'Video',
				'html' => 'Contenu HTML',
				'position' => 'Position',
				'datecreation' => 'Date de creation',
				'dateupdate' => 'Date de mise a jour',
			];
		}

		public static function attributeLength() {
			return [
				'title' => 150,
				'video' => 150,
			];
		}

		public static function getOrder() {
			return "position";
		}

		public static function getNextPosition()
		{
			return (int)self::fetchValue(
				"SELECT COALESCE(MAX(position), 0) + 1 FROM mission"
			);
		}

		protected static function hasMissionQuestionTable()
		{
			return self::tableExists('mission_question');
		}

		protected static function hasMissionHomeworkTable()
		{
			return self::tableExists('mission_homework');
		}

		public function getQuizCount() {
			if (!self::hasMissionQuestionTable()) {
				return 0;
			}

			$query = "SELECT COUNT(*) FROM mission_question WHERE IDmission = :mission_id";
			return (int)self::fetchValue($query, ['mission_id' => (int)$this->getId()]);
		}

		public function getHomeworkCount() {
			return self::countHomeworksForMission($this->getId());
		}

		public static function countHomeworksForMission($missionId)
		{
			if (!self::hasMissionHomeworkTable()) {
				return 0;
			}

			return (int)self::fetchValue(
				"SELECT COUNT(*) FROM mission_homework WHERE IDmission = :mission_id",
				['mission_id' => (int)$missionId]
			);
		}

		public static function fetchHomeworkIdsForMission($missionId)
		{
			if (!self::hasMissionHomeworkTable()) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT IDhomework FROM mission_homework WHERE IDmission = :mission_id",
				['mission_id' => (int)$missionId]
			);

			if (!is_array($rows)) {
				return [];
			}

			$ids = [];
			foreach ($rows as $row) {
				$homeworkId = (int)($row['IDhomework'] ?? 0);
				if ($homeworkId > 0) {
					$ids[$homeworkId] = $homeworkId;
				}
			}

			return array_values($ids);
		}

		public static function areHomeworkIdsComplete($missionId, array $doneHomeworkIds)
		{
			$requiredIds = self::fetchHomeworkIdsForMission($missionId);
			if (count($requiredIds) === 0) {
				return true;
			}

			$doneLookup = [];
			foreach ($doneHomeworkIds as $homeworkId) {
				$homeworkId = (int)$homeworkId;
				if ($homeworkId > 0) {
					$doneLookup[$homeworkId] = true;
				}
			}

			foreach ($requiredIds as $requiredId) {
				if (empty($doneLookup[(int)$requiredId])) {
					return false;
				}
			}

			return true;
		}

		public static function fetchHomeworksForMission($missionId, $userId = 0, $parcoursId = 0)
		{
			$missionId = (int)$missionId;
			$userId = (int)$userId;
			$parcoursId = (int)$parcoursId;

			if (!self::hasMissionHomeworkTable()) {
				return [];
			}

			if ($userId > 0 && $parcoursId > 0) {
				$query = "
					SELECT
						h.id,
						h.title,
						h.detail,
						COALESCE(mh.position, h.position, h.id) AS position,
						uh.done
					FROM mission_homework mh
					INNER JOIN homework h
						ON h.id = mh.IDhomework
					LEFT JOIN user_homework uh
						ON uh.IDmission = mh.IDmission
						AND uh.IDhomework = mh.IDhomework
						AND uh.IDuser = :user_id
						AND uh.IDparcours = :parcours_id
					WHERE mh.IDmission = :mission_id
					ORDER BY COALESCE(mh.position, h.position, h.id) ASC, h.id ASC
				";

				$rows = self::fetchAll($query, [
					'user_id' => $userId,
					'parcours_id' => $parcoursId,
					'mission_id' => $missionId,
				]);
			} else {
				$query = "
					SELECT
						h.id,
						h.title,
						h.detail,
						COALESCE(mh.position, h.position, h.id) AS position,
						NULL AS done
					FROM mission_homework mh
					INNER JOIN homework h
						ON h.id = mh.IDhomework
					WHERE mh.IDmission = :mission_id
					ORDER BY COALESCE(mh.position, h.position, h.id) ASC, h.id ASC
				";

				$rows = self::fetchAll($query, [
					'mission_id' => $missionId,
				]);
			}

			if (!is_array($rows)) {
				return [];
			}

			foreach ($rows as &$row) {
				$row['id'] = (int)($row['id'] ?? 0);
				$row['title'] = (string)($row['title'] ?? '');
				$row['detail'] = (string)($row['detail'] ?? '');
				$row['position'] = (int)($row['position'] ?? 0);
				$row['is_done'] = !empty($row['done']);
			}

			return $rows;
		}

		protected static function normalizeMissionIds(array $missionIds)
		{
			$normalized = [];
			foreach ($missionIds as $missionId) {
				$missionId = (int)$missionId;
				if ($missionId > 0) {
					$normalized[$missionId] = $missionId;
				}
			}

			return array_values($normalized);
		}

		protected static function buildMissionIdPlaceholders(array $missionIds, $prefix, array &$params)
		{
			$placeholders = [];
			foreach (self::normalizeMissionIds($missionIds) as $index => $missionId) {
				$key = $prefix . $index;
				$params[$key] = $missionId;
				$placeholders[] = ':' . $key;
			}

			return $placeholders;
		}

		protected static function bindSqlValue(array &$params, $prefix, $value)
		{
			$key = $prefix . count($params);
			$params[$key] = $value;
			return ':' . $key;
		}

		protected static function buildUserDependencyAvailabilitySql(array &$params, $userId, $parcoursId)
		{
			$depParcoursIdA = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
			$depParcoursIdB = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
			$depParcoursIdC = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
			$depParcoursIdD = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
			$depParcoursIdE = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
			$reqUserIdA = self::bindSqlValue($params, 'req_user_', (int)$userId);
			$reqParcoursIdA = self::bindSqlValue($params, 'req_parcours_', (int)$parcoursId);
			$reqUserIdB = self::bindSqlValue($params, 'req_user_', (int)$userId);
			$reqParcoursIdB = self::bindSqlValue($params, 'req_parcours_', (int)$parcoursId);

			return "
				(
					NOT EXISTS (
						SELECT 1
						FROM mission_dependencies md
						WHERE md.IDmission_child = m.id
						  AND md.IDparcours = $depParcoursIdA
					)
					OR
					(
						EXISTS (
							SELECT 1
							FROM mission_dependencies md
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = $depParcoursIdB
							  AND md.required = 1
						)
						AND NOT EXISTS (
							SELECT 1
							FROM mission_dependencies md
							LEFT JOIN user_mission lm
								ON lm.IDmission = md.IDmission_parent
								AND lm.IDuser = $reqUserIdA
								AND lm.IDparcours = $reqParcoursIdA
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = $depParcoursIdC
							  AND md.required = 1
							  AND lm.done IS NULL
						)
					)
					OR
					(
						NOT EXISTS (
							SELECT 1
							FROM mission_dependencies md
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = $depParcoursIdD
							  AND md.required = 1
						)
						AND EXISTS (
							SELECT 1
							FROM mission_dependencies md
							INNER JOIN user_mission lm
								ON lm.IDmission = md.IDmission_parent
								AND lm.IDuser = $reqUserIdB
								AND lm.IDparcours = $reqParcoursIdB
								AND lm.done IS NOT NULL
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = $depParcoursIdE
						)
					)
				)
			";
		}

		protected static function buildMissionIdsDependencyAvailabilitySql(array &$params, array $doneMissionIds, $parcoursId)
		{
			$doneMissionIds = self::normalizeMissionIds($doneMissionIds);
			$depParcoursIdNoDependency = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
			$depParcoursIdHasRequired = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
			$depParcoursIdNoRequired = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);

			if (count($doneMissionIds) > 0) {
				$donePlaceholdersCompleted = self::buildMissionIdPlaceholders($doneMissionIds, 'done_dep_completed_', $params);
				$donePlaceholdersUnmetRequired = self::buildMissionIdPlaceholders($doneMissionIds, 'done_dep_unmet_', $params);
				$depParcoursIdCompleted = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
				$depParcoursIdUnmetRequired = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
				$completedDependencySql = "SELECT 1
					FROM mission_dependencies md
					WHERE md.IDmission_child = m.id
					  AND md.IDparcours = $depParcoursIdCompleted
					  AND md.IDmission_parent IN (" . implode(', ', $donePlaceholdersCompleted) . ")";
				$unmetRequiredDependencySql = "SELECT 1
					FROM mission_dependencies md
					WHERE md.IDmission_child = m.id
					  AND md.IDparcours = $depParcoursIdUnmetRequired
					  AND md.required = 1
					  AND md.IDmission_parent NOT IN (" . implode(', ', $donePlaceholdersUnmetRequired) . ")";
			} else {
				$depParcoursIdUnmetRequired = self::bindSqlValue($params, 'dep_parcours_', (int)$parcoursId);
				$completedDependencySql = "SELECT 1
					FROM mission_dependencies md
					WHERE 1 = 0";
				$unmetRequiredDependencySql = "SELECT 1
					FROM mission_dependencies md
					WHERE md.IDmission_child = m.id
					  AND md.IDparcours = $depParcoursIdUnmetRequired
					  AND md.required = 1";
			}

			return "
				(
					NOT EXISTS (
						SELECT 1
						FROM mission_dependencies md
						WHERE md.IDmission_child = m.id
						  AND md.IDparcours = $depParcoursIdNoDependency
					)
					OR
					(
						EXISTS (
							SELECT 1
							FROM mission_dependencies md
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = $depParcoursIdHasRequired
							  AND md.required = 1
						)
						AND NOT EXISTS (
							$unmetRequiredDependencySql
						)
					)
					OR
					(
						NOT EXISTS (
							SELECT 1
							FROM mission_dependencies md
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = $depParcoursIdNoRequired
							  AND md.required = 1
						)
						AND EXISTS (
							$completedDependencySql
						)
					)
				)
			";
		}

		public static function fetchAvailableForUserParcours($userId, $parcoursId) {
			$params = [
				'pm_parcours_id' => (int)$parcoursId,
				'done_user_id' => (int)$userId,
				'done_parcours_id' => (int)$parcoursId,
			];
			$dependencyAvailabilitySql = self::buildUserDependencyAvailabilitySql($params, $userId, $parcoursId);
			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE
					NOT EXISTS (
						SELECT 1
						FROM user_mission lm_done
						WHERE lm_done.IDmission = m.id
						  AND lm_done.IDuser = :done_user_id
						  AND lm_done.IDparcours = :done_parcours_id
						  AND lm_done.done IS NOT NULL
					)
					AND $dependencyAvailabilitySql
				ORDER BY COALESCE(pm.position, m.position, m.id) ASC, pm.id ASC
			";

			return self::fetchAll($query, $params);
		}

		public static function fetchAvailableForMissionIds($parcoursId, array $doneMissionIds)
		{
			$params = [
				'pm_parcours_id' => (int)$parcoursId,
			];
			$donePlaceholdersExclude = self::buildMissionIdPlaceholders($doneMissionIds, 'done_exclude_', $params);
			$dependencyAvailabilitySql = self::buildMissionIdsDependencyAvailabilitySql($params, $doneMissionIds, $parcoursId);
			$doneSql = count($donePlaceholdersExclude) > 0
				? "AND m.id NOT IN (" . implode(', ', $donePlaceholdersExclude) . ")"
				: '';

			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE 1=1
					$doneSql
					AND $dependencyAvailabilitySql
				ORDER BY COALESCE(pm.position, m.position, m.id) ASC, pm.id ASC
			";

			return self::fetchAll($query, $params);
		}

		public static function fetchLockedForUserParcours($userId, $parcoursId) {
			$params = [
				'pm_parcours_id' => (int)$parcoursId,
				'done_user_id' => (int)$userId,
				'done_parcours_id' => (int)$parcoursId,
			];
			$dependencyAvailabilitySql = self::buildUserDependencyAvailabilitySql($params, $userId, $parcoursId);
			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE
					NOT EXISTS (
						SELECT 1
						FROM user_mission lm
						WHERE lm.IDmission = m.id
						  AND lm.IDuser = :done_user_id
						  AND lm.IDparcours = :done_parcours_id
						  AND lm.done IS NOT NULL
					)
					AND NOT $dependencyAvailabilitySql
				ORDER BY COALESCE(pm.position, m.position, m.id) ASC, pm.id ASC
			";

			return self::fetchAll($query, $params);
		}

		public static function fetchLockedForMissionIds($parcoursId, array $doneMissionIds)
		{
			$params = [
				'pm_parcours_id' => (int)$parcoursId,
			];
			$donePlaceholdersExclude = self::buildMissionIdPlaceholders($doneMissionIds, 'done_exclude_', $params);
			$dependencyAvailabilitySql = self::buildMissionIdsDependencyAvailabilitySql($params, $doneMissionIds, $parcoursId);
			$doneSql = count($donePlaceholdersExclude) > 0
				? "AND m.id NOT IN (" . implode(', ', $donePlaceholdersExclude) . ")"
				: '';

			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE 1=1
					$doneSql
					AND NOT $dependencyAvailabilitySql
				ORDER BY COALESCE(pm.position, m.position, m.id) ASC, pm.id ASC
			";

			return self::fetchAll($query, $params);
		}

		public static function fetchDoneForUserParcours($userId, $parcoursId) {
			$query = "
				SELECT m.*, pm.branch, lm.done
				FROM user_mission lm
				INNER JOIN mission m ON m.id = lm.IDmission
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :parcours_id
				WHERE lm.IDuser = :user_id
				  AND lm.IDparcours = :done_parcours_id
				  AND lm.done IS NOT NULL
				ORDER BY lm.done DESC
			";

			return self::fetchAll($query, [
				'user_id' => (int)$userId,
				'parcours_id' => (int)$parcoursId,
				'done_parcours_id' => (int)$parcoursId,
			]);
		}

		public static function fetchDoneForMissionIds($parcoursId, array $doneMissionIds)
		{
			$params = [
				'parcours_id' => (int)$parcoursId,
			];
			$donePlaceholders = self::buildMissionIdPlaceholders($doneMissionIds, 'done_mission_', $params);
			if (count($donePlaceholders) === 0) {
				return [];
			}

			$query = "
				SELECT m.*, pm.branch, NULL AS done
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :parcours_id
				WHERE m.id IN (" . implode(', ', $donePlaceholders) . ")
				ORDER BY COALESCE(pm.position, m.position, m.id) ASC, pm.id ASC
			";

			return self::fetchAll($query, $params);
		}

		public static function fetchAvailableForParcoursEditor($parcoursId)
		{
			$rows = self::fetchAll(
				"SELECT
					m.id,
					m.title,
					m.resume,
					m.position
				FROM mission m
				WHERE NOT EXISTS (
					SELECT 1
					FROM parcours_mission pm
					WHERE pm.IDparcours = :parcours_id
					  AND pm.IDmission = m.id
				)
				ORDER BY COALESCE(m.position, m.id) ASC, m.id ASC",
				['parcours_id' => (int)$parcoursId]
			);

			return is_array($rows) ? $rows : [];
		}
	}

?>
