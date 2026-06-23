<?php
	namespace dbObject;

	class MissionHomework extends DbObject
	{
		public static function tableName()
		{
			return 'mission_homework';
		}

		public static function rules()
		{
			return [
				[['id', 'IDmission', 'IDhomework', 'position'], 'integer'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDmission' => 'Mission',
				'IDhomework' => 'Homework',
				'position' => 'Position',
			];
		}

		public static function getOrder()
		{
			return "position, id";
		}

		public static function existsForMission($missionId, $homeworkId)
		{
			return (bool)self::fetchValue(
				"SELECT 1 FROM mission_homework WHERE IDmission = :mission_id AND IDhomework = :homework_id LIMIT 1",
				[
					'mission_id' => (int)$missionId,
					'homework_id' => (int)$homeworkId,
				]
			);
		}

		public static function fetchDetailedForMission($missionId)
		{
			$rows = self::fetchAll(
				"SELECT
					mh.id,
					mh.IDmission,
					mh.IDhomework,
					mh.position,
					h.title,
					h.detail
				FROM mission_homework mh
				INNER JOIN homework h
					ON h.id = mh.IDhomework
				WHERE mh.IDmission = :mission_id
				ORDER BY COALESCE(mh.position, h.position, mh.id) ASC, mh.id ASC",
				['mission_id' => (int)$missionId]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function attachHomeworkToMission($missionId, $homeworkId, array $options = array())
		{
			$missionId = (int)$missionId;
			$homeworkId = (int)$homeworkId;
			if ($missionId <= 0 || $homeworkId <= 0) {
				return array(
					'status' => false,
					'message' => 'Mission ou devoir invalide.',
				);
			}

			$item = new self();
			$created = !$item->load([
				['IDmission', $missionId],
				['IDhomework', $homeworkId],
			]);

			if ($created) {
				$item->set('IDmission', $missionId);
				$item->set('IDhomework', $homeworkId);
			}

			$position = array_key_exists('position', $options) ? (int)$options['position'] : 0;
			if ($created && $position <= 0) {
				$position = (int)self::fetchValue(
					"SELECT COALESCE(MAX(position), 0) + 1
					FROM mission_homework
					WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
			}

			if ($created || array_key_exists('position', $options)) {
				$item->set('position', $position > 0 ? $position : null);
			}

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return array(
					'status' => false,
					'message' => is_array($saveResult) && !empty($saveResult['text'])
						? (string)$saveResult['text']
						: 'Impossible d ajouter ce devoir a la mission.',
				);
			}

			return array(
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			);
		}

		public static function reorderForMission($missionId, array $homeworkIds)
		{
			$missionId = (int)$missionId;
			if ($missionId <= 0) {
				return array(
					'status' => false,
					'message' => 'Mission invalide.',
				);
			}

			$normalizedHomeworkIds = [];
			foreach ($homeworkIds as $homeworkId) {
				$homeworkId = (int)$homeworkId;
				if ($homeworkId > 0) {
					$normalizedHomeworkIds[$homeworkId] = $homeworkId;
				}
			}
			$normalizedHomeworkIds = array_values($normalizedHomeworkIds);

			$currentRows = self::fetchAll(
				"SELECT IDhomework
				FROM mission_homework
				WHERE IDmission = :mission_id",
				['mission_id' => $missionId]
			);
			if (!is_array($currentRows)) {
				return array(
					'status' => false,
					'message' => 'Impossible de charger les devoirs de la mission.',
				);
			}

			$currentHomeworkIds = [];
			foreach ($currentRows as $row) {
				$homeworkId = (int)($row['IDhomework'] ?? 0);
				if ($homeworkId > 0) {
					$currentHomeworkIds[$homeworkId] = $homeworkId;
				}
			}
			$currentHomeworkIds = array_values($currentHomeworkIds);
			sort($currentHomeworkIds);

			$comparisonHomeworkIds = $normalizedHomeworkIds;
			sort($comparisonHomeworkIds);
			if ($comparisonHomeworkIds !== $currentHomeworkIds) {
				return array(
					'status' => false,
					'message' => 'La liste des devoirs a reordonner est incomplete.',
				);
			}

			foreach ($normalizedHomeworkIds as $index => $homeworkId) {
				$result = self::execute(
					"UPDATE mission_homework
					SET position = :position
					WHERE IDmission = :mission_id
					  AND IDhomework = :homework_id",
					[
						'position' => $index + 1,
						'mission_id' => $missionId,
						'homework_id' => $homeworkId,
					]
				);
				if (!$result) {
					return array(
						'status' => false,
						'message' => 'Impossible de reordonner les devoirs de la mission.',
					);
				}
			}

			return array(
				'status' => true,
			);
		}
	}

?>
