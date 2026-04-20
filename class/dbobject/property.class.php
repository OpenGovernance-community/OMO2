<?php
	namespace dbObject;


	class Property extends DbObject
	{
	    public static function tableName()
		{
			return 'property'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id'], 'integer'],					
				[['name','shortname'], 'string'],			// Texte libre
				[['IDpropertyformat','IDholon_organization'], 'fk'],			// Texte libre
				[['datecreation'], 'datetime'],				// Clé étrangères
				[['active'], 'boolean'],				// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'shortname' => 'Nom court',
				'IDpropertyformat' => 'Format',
				'IDholon_organization' => 'Organisation',
				'datecreation' => 'Date de création',
				'active' => 'Actif ?',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}
		

		
	}
	
?>
