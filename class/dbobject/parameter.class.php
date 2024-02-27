<?php
	namespace dbObject;


	class parameter extends DbObject
	{
	    public static function tableName()
		{
			return 'parameter'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['name'], 'required'],									// Champs obligatoires
				[['name','code','type','format','typeobject'], 'string'],		// Chaînes de caractère (max 250)
				[['description','value'], 'text'],						// Textes libres
				[['active'], 'boolean'],								// Images redimentionnables
				[['id'], 'safe'],										// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom affiché',
				'code' => 'Nom d\'accès',
				'description' => 'Description',
				'type' => 'Type',
				'format' => 'Format',
				'value' => 'Valeur(s)',
				'typeobject' => 'Objet associé',
				'active' => 'Actif?',
			];
		}

		// Ajoute un champ description, qui peut apparaître sous forme de bulle d'information ou en sous-titre
		public static function attributeDescriptions() {
			return [
				'id' => 'ID',
				'name' => 'Le nom du paramètre',
				'code' => 'Nom du paramètre dans le code',
				'description' => 'Petite description de sa fonction',
				'type' => 'Numérique, chaîne de caractère, texte libre, html, etc...',
				'format' => 'Expression régulière pour validation',
				'value' => 'Valeur par défaut ou liste de valeur pour les listes à choix multiple',
				'typeobject' => 'Connecté à un objet de type dbObject',
				'active' => 'Encore actif ou non?',
			];
		}		

		// Défini les informations de taille pour le champ
		public static function attributeLength() {
			return [
				'name' => 255,										// Nombre de caractères maximum
				'type' => 30,										// Nombre de caractères maximum
				'format' => 150,										// Nombre de caractères maximum
				'typeobject' => 60,										// Nombre de caractères maximum
			];
		}	
				
	
		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}
		

		
	}
	
?>
