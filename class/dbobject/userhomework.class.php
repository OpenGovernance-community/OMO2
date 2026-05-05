<?php
	namespace dbObject;

	class UserHomework extends DbObject
	{
		public static function tableName()
		{
			return 'user_homework';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser', 'IDmission', 'IDhomework', 'IDparcours'], 'integer'],
				[['done', 'datecreation', 'dateupdate'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Utilisateur',
				'IDmission' => 'Mission',
				'IDhomework' => 'Homework',
				'IDparcours' => 'Parcours',
				'done' => 'Termine le',
				'datecreation' => 'Date de creation',
				'dateupdate' => 'Date de mise a jour',
			];
		}

		public static function getOrder()
		{
			return "done DESC, id DESC";
		}

		public static function markDone($userId, $missionId, $homeworkId, $parcoursId, $done = true)
		{
			$userHomework = new self();
			if ($userHomework->load([
				['IDuser', (int)$userId],
				['IDmission', (int)$missionId],
				['IDhomework', (int)$homeworkId],
				['IDparcours', (int)$parcoursId],
			])) {
				$userHomework->set('done', $done ? new \DateTime() : null);
			} else {
				$userHomework->set('IDuser', (int)$userId);
				$userHomework->set('IDmission', (int)$missionId);
				$userHomework->set('IDhomework', (int)$homeworkId);
				$userHomework->set('IDparcours', (int)$parcoursId);
				$userHomework->set('done', $done ? new \DateTime() : null);
			}

			return $userHomework->save();
		}

		public static function fetchDoneHomeworkIdsForUserMission($userId, $missionId, $parcoursId)
		{
			$rows = self::fetchAll(
				"SELECT IDhomework FROM user_homework WHERE IDuser = :user_id AND IDmission = :mission_id AND IDparcours = :parcours_id AND done IS NOT NULL",
				[
					'user_id' => (int)$userId,
					'mission_id' => (int)$missionId,
					'parcours_id' => (int)$parcoursId,
				]
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

		public static function countDoneForUserMission($userId, $missionId, $parcoursId)
		{
			return (int)self::fetchValue(
				"SELECT COUNT(*) FROM user_homework WHERE IDuser = :user_id AND IDmission = :mission_id AND IDparcours = :parcours_id AND done IS NOT NULL",
				[
					'user_id' => (int)$userId,
					'mission_id' => (int)$missionId,
					'parcours_id' => (int)$parcoursId,
				]
			);
		}

		public static function hasCompletedAllForUserMission($userId, $missionId, $parcoursId)
		{
			$requiredCount = Mission::countHomeworksForMission($missionId);
			if ($requiredCount <= 0) {
				return true;
			}

			return self::countDoneForUserMission($userId, $missionId, $parcoursId) >= $requiredCount;
		}
	}

?>
