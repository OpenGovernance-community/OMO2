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
	}

?>
