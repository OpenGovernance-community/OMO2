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

		public function getQuizCount() {
			$query = "SELECT COUNT(*) FROM mission_question WHERE IDmission = :mission_id";
			return (int)self::fetchValue($query, ['mission_id' => (int)$this->getId()]);
		}

		public function getHomeworkCount() {
			return self::countHomeworksForMission($this->getId());
		}

		public static function countHomeworksForMission($missionId)
		{
			return (int)self::fetchValue(
				"SELECT COUNT(*) FROM mission_homework WHERE IDmission = :mission_id",
				['mission_id' => (int)$missionId]
			);
		}

		public static function fetchHomeworkIdsForMission($missionId)
		{
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

		public static function fetchAvailableForUserParcours($userId, $parcoursId) {
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
					AND
					(
						NOT EXISTS (
							SELECT 1
							FROM mission_dependencies md
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = :dep_parcours_id
						)
						OR
						NOT EXISTS (
							SELECT 1
							FROM mission_dependencies md
							LEFT JOIN user_mission lm
								ON lm.IDmission = md.IDmission_parent
								AND lm.IDuser = :req_user_id
								AND lm.IDparcours = :req_parcours_id
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = :req_dep_parcours_id
							  AND md.required = 1
							  AND lm.done IS NULL
						)
					)
				ORDER BY pm.branch ASC, m.position ASC
			";

			return self::fetchAll($query, [
				'pm_parcours_id' => (int)$parcoursId,
				'done_user_id' => (int)$userId,
				'done_parcours_id' => (int)$parcoursId,
				'dep_parcours_id' => (int)$parcoursId,
				'req_user_id' => (int)$userId,
				'req_parcours_id' => (int)$parcoursId,
				'req_dep_parcours_id' => (int)$parcoursId,
			]);
		}

		public static function fetchAvailableForMissionIds($parcoursId, array $doneMissionIds)
		{
			$params = [
				'pm_parcours_id' => (int)$parcoursId,
				'req_dep_parcours_id' => (int)$parcoursId,
			];
			$donePlaceholdersExclude = self::buildMissionIdPlaceholders($doneMissionIds, 'done_exclude_', $params);
			$donePlaceholdersDeps = self::buildMissionIdPlaceholders($doneMissionIds, 'done_dep_', $params);
			$doneSql = count($donePlaceholdersExclude) > 0
				? "AND m.id NOT IN (" . implode(', ', $donePlaceholdersExclude) . ")"
				: '';
			$unmetDependencySql = count($donePlaceholdersDeps) > 0
				? "SELECT 1
					FROM mission_dependencies md
					WHERE md.IDmission_child = m.id
					  AND md.IDparcours = :req_dep_parcours_id
					  AND md.required = 1
					  AND md.IDmission_parent NOT IN (" . implode(', ', $donePlaceholdersDeps) . ")"
				: "SELECT 1
					FROM mission_dependencies md
					WHERE md.IDmission_child = m.id
					  AND md.IDparcours = :req_dep_parcours_id
					  AND md.required = 1";

			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE 1=1
					$doneSql
					AND NOT EXISTS (
						$unmetDependencySql
					)
				ORDER BY pm.branch ASC, m.position ASC
			";

			return self::fetchAll($query, $params);
		}

		public static function fetchLockedForUserParcours($userId, $parcoursId) {
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
					AND
					EXISTS (
						SELECT 1
						FROM mission_dependencies md
						LEFT JOIN user_mission lm
							ON lm.IDmission = md.IDmission_parent
							AND lm.IDuser = :req_user_id
							AND lm.IDparcours = :req_parcours_id
						WHERE md.IDmission_child = m.id
						  AND md.IDparcours = :req_dep_parcours_id
						  AND md.required = 1
						  AND lm.done IS NULL
					)
				ORDER BY pm.branch ASC, m.position ASC
			";

			return self::fetchAll($query, [
				'pm_parcours_id' => (int)$parcoursId,
				'done_user_id' => (int)$userId,
				'done_parcours_id' => (int)$parcoursId,
				'req_user_id' => (int)$userId,
				'req_parcours_id' => (int)$parcoursId,
				'req_dep_parcours_id' => (int)$parcoursId,
			]);
		}

		public static function fetchLockedForMissionIds($parcoursId, array $doneMissionIds)
		{
			$params = [
				'pm_parcours_id' => (int)$parcoursId,
				'req_dep_parcours_id' => (int)$parcoursId,
			];
			$donePlaceholdersExclude = self::buildMissionIdPlaceholders($doneMissionIds, 'done_exclude_', $params);
			$donePlaceholdersDeps = self::buildMissionIdPlaceholders($doneMissionIds, 'done_dep_', $params);
			$doneSql = count($donePlaceholdersExclude) > 0
				? "AND m.id NOT IN (" . implode(', ', $donePlaceholdersExclude) . ")"
				: '';
			$missingDependencySql = count($donePlaceholdersDeps) > 0
				? "SELECT 1
					FROM mission_dependencies md
					WHERE md.IDmission_child = m.id
					  AND md.IDparcours = :req_dep_parcours_id
					  AND md.required = 1
					  AND md.IDmission_parent NOT IN (" . implode(', ', $donePlaceholdersDeps) . ")"
				: "SELECT 1
					FROM mission_dependencies md
					WHERE md.IDmission_child = m.id
					  AND md.IDparcours = :req_dep_parcours_id
					  AND md.required = 1";

			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE 1=1
					$doneSql
					AND EXISTS (
						$missingDependencySql
					)
				ORDER BY pm.branch ASC, m.position ASC
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
				ORDER BY pm.branch ASC, m.position ASC
			";

			return self::fetchAll($query, $params);
		}
	}

?>
