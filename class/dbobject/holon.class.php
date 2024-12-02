<?php
	namespace dbObject;


	class Holon extends DbObject
	{
	    public static function tableName()
		{
			return 'holon'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id'], 'integer'],					
				[['name','templatename','accesskey'], 'string'],			// Texte libre
				[['datecreation','datemodification'], 'datetime'],	// Date avec précision des heures
				[['IDuser','IDtypeholon','IDholon_parent','IDholon_template'], 'fk'],				// Clé étrangères
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
				'IDholon_org' => 'Organisation',
				'IDuser' => 'Créateur et administrateur',
				'datecreation' => 'Date de création',
				'datemodification' => 'Date de modification',
				'active' => 'Actif ?',
				'templatename' => 'Nom de template',
				'IDtypeholon' => 'Type de holon',
				'IDholon_parent' => 'Parent',
				'IDholon_template' => 'Template',
				'accesskey' => 'Clé accès',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}
		
		public function canEdit() {
				return $_SESSION["currentUser"]==$this->get("IDuser");
		}

		// Retourne tous les enfants (uniquement pour les orga
		public function getAllChildren() {
			if ($this->get("IDtypeholon")==4) {
				$children=new \dbObject\ArrayHolon();
				$params= array();	
				$params["filter"] = "IDholon_org=".$this->get("id");
				$children->load($params);
				return $children;	
			} else return null;		
		}

		// Retourne tous les enfants (uniquement pour les orga
		public function getChildren() {

			$children=new \dbObject\ArrayHolon();
			$params= array();	
			$params["filter"] = "IDholon_parent=".$this->get("id");
			$children->load($params);
			return $children;	
	
		}

		// Désactive tous les enfants
		public function disableAllChildren() {
			foreach ($this->getAllChildren() as $children) {
				$children->set("active",false);
				$children->save();
			}					
			
		}
		
	}
	
?>
