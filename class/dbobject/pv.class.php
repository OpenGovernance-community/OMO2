<?php
	namespace dbObject;


	class PV extends DbObject
	{
	    public static function tableName()
		{
			return 'pv'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id'], 'integer'],					
				[['data'], 'text'],			// Texte libre
				[['datecreation','datemodification'], 'datetime'],	// Date avec précision des heures
				[['IDuser'], 'fk'],				// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'data' => 'JSON data',
				'datecreation' => 'Date de création',
				'datemodification' => 'Date de modification',
				'IDuser' => 'Auteur',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "datemodification desc";
		}
		
		public function canEdit() {
				return $_SESSION["currentUser"]==$this->get("IDuser");
		}

		
	}
	
?>
