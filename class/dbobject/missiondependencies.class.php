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
	}

?>
