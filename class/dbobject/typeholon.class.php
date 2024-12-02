<?php
	namespace dbObject;


	class TypeHolon extends DbObject
	{
	    public static function tableName()
		{
			return 'typeholon'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id'], 'integer'],					
				[['name'], 'string'],			// Texte libre
				[['hastemplate','haschild'], 'boolean'],				// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'hastemplate' => 'Templates ?',
				'haschild' => 'Enfants ?',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}
		
		public function canEdit() {
				return false;
		}

		
	}
	
?>
