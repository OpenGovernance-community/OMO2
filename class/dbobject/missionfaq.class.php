<?php
	namespace dbObject;

	class MissionFaq extends DbObject
	{
		public static function tableName()
		{
			return 'mission_faq';
		}

		public static function rules()
		{
			return [
				[['id', 'IDmission', 'IDfaq', 'position'], 'integer'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDmission' => 'Mission',
				'IDfaq' => 'FAQ',
				'position' => 'Position',
			];
		}

		public static function getOrder() {
			return "position, id";
		}
	}

?>
