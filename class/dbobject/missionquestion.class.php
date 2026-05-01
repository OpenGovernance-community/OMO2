<?php
	namespace dbObject;

	class MissionQuestion extends DbObject
	{
		public static function tableName()
		{
			return 'mission_question';
		}

		public static function rules()
		{
			return [
				[['id', 'IDmission', 'IDquestion', 'position'], 'integer'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDmission' => 'Mission',
				'IDquestion' => 'Question',
				'position' => 'Position',
			];
		}

		public static function getOrder() {
			return "position, id";
		}
	}

?>
