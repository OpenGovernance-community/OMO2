<?php
	namespace dbObject;


	class AltText extends DbObject
	{
	    public static function tableName()
		{
			return 'alttext';
		}	
		
		public static function rules()
		{
			return [
				[['id'], 'required'],
				[['IDdocument','IDaiprompt'], 'fk'],
				[['text'], 'text'],
				[['datecreation'], 'datetime'],
				[['id'], 'safe'],

			];
		}
		
		// Défini les labels standarts pour cet objet
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDdocument' => 'Document original',
				'IDaiprompt' => 'Prompt',
				'text' => 'Texte',
				'datecreation' => 'Date de création',
			];
		}
		
		public static function getOrder() {
			return "id";
		}
		
	}
	
?>
