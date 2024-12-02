<?php
	namespace dbObject;


	class AIPrompt extends DbObject
	{
	    public static function tableName()
		{
			return 'aiprompt';
		}	
		
		public static function rules()
		{
			return [
				[['id'], 'required'],
				[['IDuser'], 'fk'],
				[['title'], 'string'],
				[['prompt'], 'text'],
				[['ispublic'], 'boolean'],
				[['datecreation'], 'datetime'],
				[['id'], 'safe'],

			];
		}
		
		// Défini les labels standarts pour cet objet
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'prompt' => 'Prompt',
				'ispublic' => 'Public ?',
				'datecreation' => 'Date de création',
				'IDuser' => 'Créateur',
			];
		}
		
		public static function getOrder() {
			return "id";
		}
		
	}
	
?>
