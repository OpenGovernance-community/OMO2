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
				[['title'], 'string'],
				[['detail'], 'text'],
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

		public static function getOrder()
		{
			return "position, id";
		}
	}

?>
