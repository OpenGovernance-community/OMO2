<?php
	namespace dbObject;


	class Translation extends DbObject
	{
	    public static function tableName()
		{
			return 'translation';
		}	
		
		public static function rules()
		{
			return [
				[['uid','value'], 'required'],
				[['id','cpt'], 'integer'],
				[['uid'], 'string'],
				[['value','original'], 'text'],
				[['date'], 'datetime'],
				[['id'], 'safe'],

			];
		}
		
		// Défini les labels standarts pour cet objet
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'uid' => 'MD5 uid',
				'value' => 'Traduction',
				'original' => 'Text original',
				'date' => 'Dernier accès',
				'cpt' => 'Nb accès',
			];
		}
		
		public static function getOrder() {
			return "id";
		}
		
	}
	
?>
