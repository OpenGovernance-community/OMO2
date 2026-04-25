<?php
	namespace dbObject;


	class PropertyFormat extends DbObject
	{
		public const FORMAT_TEXT = 1;
		public const FORMAT_LIST = 2;
		public const FORMAT_NUMBER = 3;
		public const FORMAT_DATE = 4;

	    public static function tableName()
		{
			return 'propertyformat';
		}

		public static function rules()
		{
			return [
				[['id'], 'required'],
				[['id'], 'integer'],
				[['name'], 'string'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
			];
		}

		public static function getOrder() {
			return "id";
		}
	}
	
?>
