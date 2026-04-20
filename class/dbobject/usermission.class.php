<?php
	namespace dbObject;

	class UserMission extends DbObject
	{
		public static function tableName()
		{
			return 'user_mission';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser', 'IDmission', 'IDparcours'], 'integer'],
				[['done'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Utilisateur',
				'IDmission' => 'Mission',
				'IDparcours' => 'Parcours',
				'done' => 'Termine le',
			];
		}

		public static function getOrder() {
			return "done DESC";
		}

		public static function markDone($userId, $missionId, $parcoursId) {
			$userMission = new self();
			if ($userMission->load([
				['IDuser', (int)$userId],
				['IDmission', (int)$missionId],
				['IDparcours', (int)$parcoursId],
			])) {
				$userMission->set('done', new \DateTime());
			} else {
				$userMission->set('IDuser', (int)$userId);
				$userMission->set('IDmission', (int)$missionId);
				$userMission->set('IDparcours', (int)$parcoursId);
				$userMission->set('done', new \DateTime());
			}

			return $userMission->save();
		}

		public static function countDoneForUserAndParcours($userId, $parcoursId) {
			$query = "
				SELECT COUNT(*)
				FROM user_mission
				WHERE IDuser = :user_id
				  AND IDparcours = :parcours_id
				  AND done IS NOT NULL
			";

			return (int)self::fetchValue($query, [
				'user_id' => (int)$userId,
				'parcours_id' => (int)$parcoursId,
			]);
		}
	}

?>
