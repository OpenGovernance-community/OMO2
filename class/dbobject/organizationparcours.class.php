<?php
	namespace dbObject;

	class OrganizationParcours extends DbObject
	{
		public static function tableName()
		{
			return 'organization_parcours';
		}

		public static function rules()
		{
			return [
				[['id', 'IDorganization', 'IDparcours', 'position'], 'integer'],
				[['everybody'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDparcours' => 'Parcours',
				'position' => 'Position',
				'everybody' => 'Tout le monde',
			];
		}

		public static function getOrder() {
			return "position, id";
		}
	}

?>
