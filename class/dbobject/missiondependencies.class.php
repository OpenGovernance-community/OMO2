<?php
	namespace dbObject;

	class MissionDependencies extends DbObject
	{
		public static function tableName()
		{
			return 'mission_dependencies';
		}

		public static function rules()
		{
			return [
				[['id', 'IDmission_parent', 'IDmission_child', 'IDparcours'], 'integer'],
				[['required'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDmission_parent' => 'Mission parente',
				'IDmission_child' => 'Mission enfant',
				'IDparcours' => 'Parcours',
				'required' => 'Requis',
			];
		}

		public static function getOrder() {
			return "id";
		}

		public static function hasDependencyTable()
		{
			return self::tableExists('mission_dependencies');
		}

		public static function fetchDetailedForMission($parcoursId, $missionId)
		{
			if (!self::hasDependencyTable()) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT
					md.id,
					md.IDmission_parent,
					md.IDmission_child,
					md.IDparcours,
					md.required,
					m.title,
					m.resume
				FROM mission_dependencies md
				INNER JOIN mission m
					ON m.id = md.IDmission_parent
				WHERE md.IDparcours = :parcours_id
				  AND md.IDmission_child = :mission_id
				ORDER BY m.title ASC, m.id ASC",
				[
					'parcours_id' => (int)$parcoursId,
					'mission_id' => (int)$missionId,
				]
			);

			return is_array($rows) ? $rows : [];
		}

		protected static function missionDependsOn($parcoursId, $startMissionId, $targetMissionId, array &$visited = [])
		{
			$parcoursId = (int)$parcoursId;
			$startMissionId = (int)$startMissionId;
			$targetMissionId = (int)$targetMissionId;
			if ($parcoursId <= 0 || $startMissionId <= 0 || $targetMissionId <= 0 || isset($visited[$startMissionId]) || !self::hasDependencyTable()) {
				return false;
			}

			if ($startMissionId === $targetMissionId) {
				return true;
			}

			$visited[$startMissionId] = true;
			$rows = self::fetchAll(
				"SELECT IDmission_parent
				FROM mission_dependencies
				WHERE IDparcours = :parcours_id
				  AND IDmission_child = :mission_id",
				[
					'parcours_id' => $parcoursId,
					'mission_id' => $startMissionId,
				]
			);
			if (!is_array($rows)) {
				return false;
			}

			foreach ($rows as $row) {
				$parentMissionId = (int)($row['IDmission_parent'] ?? 0);
				if ($parentMissionId <= 0) {
					continue;
				}

				if ($parentMissionId === $targetMissionId || self::missionDependsOn($parcoursId, $parentMissionId, $targetMissionId, $visited)) {
					return true;
				}
			}

			return false;
		}

		public static function attachDependency($parcoursId, $parentMissionId, $childMissionId, $required = true)
		{
			$parcoursId = (int)$parcoursId;
			$parentMissionId = (int)$parentMissionId;
			$childMissionId = (int)$childMissionId;
			if (!self::hasDependencyTable()) {
				return [
					'status' => false,
					'message' => 'La table des dependances de mission est absente.',
				];
			}

			if ($parcoursId <= 0 || $parentMissionId <= 0 || $childMissionId <= 0 || $parentMissionId === $childMissionId) {
				return [
					'status' => false,
					'message' => 'Dependance de mission invalide.',
				];
			}

			$createsLoop = self::missionDependsOn($parcoursId, $parentMissionId, $childMissionId);
			if ($createsLoop) {
				return [
					'status' => false,
					'message' => 'Ce prerequis creerait une boucle entre missions.',
				];
			}

			$item = new self();
			$created = !$item->load([
				['IDparcours', $parcoursId],
				['IDmission_parent', $parentMissionId],
				['IDmission_child', $childMissionId],
			]);

			if ($created) {
				$item->set('IDparcours', $parcoursId);
				$item->set('IDmission_parent', $parentMissionId);
				$item->set('IDmission_child', $childMissionId);
			}
			$item->set('required', (bool)$required);

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return [
					'status' => false,
					'message' => is_array($saveResult) && !empty($saveResult['text'])
						? (string)$saveResult['text']
						: 'Impossible d ajouter ce prerequis de mission.',
				];
			}

			return [
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			];
		}

		public static function removeDependency($parcoursId, $parentMissionId, $childMissionId)
		{
			if (!self::hasDependencyTable()) {
				return [
					'status' => false,
					'message' => 'La table des dependances de mission est absente.',
				];
			}

			$result = self::execute(
				"DELETE FROM mission_dependencies
				WHERE IDparcours = :parcours_id
				  AND IDmission_parent = :parent_mission_id
				  AND IDmission_child = :child_mission_id",
				[
					'parcours_id' => (int)$parcoursId,
					'parent_mission_id' => (int)$parentMissionId,
					'child_mission_id' => (int)$childMissionId,
				]
			);
			if ($result === false) {
				return [
					'status' => false,
					'message' => 'Impossible de retirer ce prerequis de mission.',
				];
			}

			return [
				'status' => true,
			];
		}
	}

?>
