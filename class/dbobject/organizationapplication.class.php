<?php
	namespace dbObject;

	class OrganizationApplication extends DbObject
	{
	    public static function tableName()
		{
			return 'organization_application';
		}

		public static function rules()
		{
			return [
				[['IDorganization', 'IDapplication'], 'required'],
				[['id', 'IDorganization', 'IDapplication', 'position'], 'integer'],
				[['active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDapplication' => 'Application',
				'position' => 'Position',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDorganization' => 'Organisation concernée',
				'IDapplication' => 'Application activée pour cette organisation',
				'position' => 'Surcharge locale de l’ordre d’affichage',
				'active' => 'Activation locale de l’application',
			];
		}

		public static function getOrder()
		{
			return "position ASC, id ASC";
		}
	}

?>
