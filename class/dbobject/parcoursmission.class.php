<?php
	namespace dbObject;

	class ParcoursMission extends DbObject
	{
		public static function tableName()
		{
			return 'parcours_mission';
		}

		public static function rules()
		{
			return [
				[['id', 'IDparcours', 'IDmission', 'position'], 'integer'],
				[['required'], 'boolean'],
				[['branch'], 'string'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDparcours' => 'Parcours',
				'IDmission' => 'Mission',
				'position' => 'Position',
				'required' => 'Requis',
				'branch' => 'Branche',
			];
		}

		public static function attributeLength() {
			return [
				'branch' => 50,
			];
		}

		public static function getOrder() {
			return "position, id";
		}

		public static function countForParcours($parcoursId) {
			return (int)self::fetchValue(
				"SELECT COUNT(*) FROM parcours_mission WHERE IDparcours = :parcours_id",
				['parcours_id' => (int)$parcoursId]
			);
		}

		public static function fetchDetailedForParcours($parcoursId)
		{
			$quizCountSql = self::tableExists('mission_question')
				? "(
						SELECT COUNT(*)
						FROM mission_question mq
						WHERE mq.IDmission = m.id
					)"
				: "0";
			$homeworkCountSql = self::tableExists('mission_homework')
				? "(
						SELECT COUNT(*)
						FROM mission_homework mh
						WHERE mh.IDmission = m.id
					)"
				: "0";

			$rows = self::fetchAll(
				"SELECT
					pm.id,
					pm.IDparcours,
					pm.IDmission,
					pm.position,
					pm.required,
					pm.branch,
					m.title,
					m.resume,
					" . $quizCountSql . " AS quiz_count,
					" . $homeworkCountSql . " AS homework_count
				FROM parcours_mission pm
				INNER JOIN mission m
					ON m.id = pm.IDmission
				WHERE pm.IDparcours = :parcours_id
				ORDER BY COALESCE(pm.position, m.position, pm.id) ASC, pm.id ASC",
				['parcours_id' => (int)$parcoursId]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function attachMissionToParcours($parcoursId, $missionId, array $options = array())
		{
			$parcoursId = (int)$parcoursId;
			$missionId = (int)$missionId;
			if ($parcoursId <= 0 || $missionId <= 0) {
				return array(
					'status' => false,
					'message' => 'Parcours ou mission invalide.',
				);
			}

			$item = new self();
			$created = !$item->load([
				['IDparcours', $parcoursId],
				['IDmission', $missionId],
			]);

			if ($created) {
				$item->set('IDparcours', $parcoursId);
				$item->set('IDmission', $missionId);
			}

			$position = array_key_exists('position', $options) ? (int)$options['position'] : 0;
			if ($created && $position <= 0) {
				$position = (int)self::fetchValue(
					"SELECT COALESCE(MAX(position), 0) + 1
					FROM parcours_mission
					WHERE IDparcours = :parcours_id",
					['parcours_id' => $parcoursId]
				);
			}

			if ($created || array_key_exists('position', $options)) {
				$item->set('position', $position > 0 ? $position : null);
			}

			if ($created || array_key_exists('required', $options)) {
				$item->set('required', array_key_exists('required', $options) ? (bool)$options['required'] : true);
			}

			if ($created || array_key_exists('branch', $options)) {
				$item->set('branch', array_key_exists('branch', $options) ? (string)$options['branch'] : null);
			}

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return array(
					'status' => false,
					'message' => is_array($saveResult) && !empty($saveResult['text'])
						? (string)$saveResult['text']
						: 'Impossible d ajouter cette mission au parcours.',
				);
			}

			return array(
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			);
		}

		public static function reorderForParcours($parcoursId, array $missionIds)
		{
			$parcoursId = (int)$parcoursId;
			if ($parcoursId <= 0) {
				return array(
					'status' => false,
					'message' => 'Parcours invalide.',
				);
			}

			$normalizedMissionIds = [];
			foreach ($missionIds as $missionId) {
				$missionId = (int)$missionId;
				if ($missionId > 0) {
					$normalizedMissionIds[$missionId] = $missionId;
				}
			}
			$normalizedMissionIds = array_values($normalizedMissionIds);

			$currentRows = self::fetchAll(
				"SELECT IDmission
				FROM parcours_mission
				WHERE IDparcours = :parcours_id",
				['parcours_id' => $parcoursId]
			);
			if (!is_array($currentRows)) {
				return array(
					'status' => false,
					'message' => 'Impossible de charger les missions du parcours.',
				);
			}

			$currentMissionIds = [];
			foreach ($currentRows as $row) {
				$missionId = (int)($row['IDmission'] ?? 0);
				if ($missionId > 0) {
					$currentMissionIds[$missionId] = $missionId;
				}
			}
			$currentMissionIds = array_values($currentMissionIds);
			sort($currentMissionIds);

			$comparisonMissionIds = $normalizedMissionIds;
			sort($comparisonMissionIds);
			if ($comparisonMissionIds !== $currentMissionIds) {
				return array(
					'status' => false,
					'message' => 'La liste des missions a reordonner est incomplete.',
				);
			}

			foreach ($normalizedMissionIds as $index => $missionId) {
				$result = self::execute(
					"UPDATE parcours_mission
					SET position = :position
					WHERE IDparcours = :parcours_id
					  AND IDmission = :mission_id",
					[
						'position' => $index + 1,
						'parcours_id' => $parcoursId,
						'mission_id' => $missionId,
					]
				);
				if (!$result) {
					return array(
						'status' => false,
						'message' => 'Impossible de reordonner les missions du parcours.',
					);
				}
			}

			return array(
				'status' => true,
			);
		}
	}

?>
