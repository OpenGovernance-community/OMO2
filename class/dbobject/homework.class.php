<?php
	namespace dbObject;

	class Homework extends DbObject
	{
		public static function tableName()
		{
			return 'homework';
		}

		public static function rules()
		{
			return [
				[['title'], 'required'],
				[['id', 'position'], 'integer'],
				[['onlyAdmin'], 'boolean'],
				[['title'], 'string'],
				[['detail'], 'html'],
				[['datecreation', 'dateupdate'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'detail' => 'Detail',
				'onlyAdmin' => 'Reserve aux admins de l organisation',
				'position' => 'Position',
				'datecreation' => 'Date de creation',
				'dateupdate' => 'Date de mise a jour',
			];
		}

		public static function attributeLength()
		{
			return [
				'title' => 150,
			];
		}

		public static function attributeHtmlEditorProfiles()
		{
			return [
				'detail' => 'simple',
			];
		}

		public static function getOrder()
		{
			return "position, id";
		}

		public static function getNextPosition()
		{
			return (int)self::fetchValue(
				"SELECT COALESCE(MAX(position), 0) + 1 FROM homework"
			);
		}
	}

?>
