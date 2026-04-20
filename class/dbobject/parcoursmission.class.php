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
				[['id', 'IDparcours', 'IDmission'], 'integer'],
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
			return "id";
		}

		public static function countForParcours($parcoursId) {
			return (int)self::fetchValue(
				"SELECT COUNT(*) FROM parcours_mission WHERE IDparcours = :parcours_id",
				['parcours_id' => (int)$parcoursId]
			);
		}
	}

?>
