<?php
	namespace dbObject;


	class PV extends DbObject
	{
	    public static function tableName()
		{
			return 'pv'; // Nom de la table correspondante
		}	
		
		// Defini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id'], 'integer'],					
				[['data'], 'text'],			// Texte libre
				[['codeaffichage','codeedition'], 'string'],
				[['datecreation','datemodification'], 'datetime'],	// Date avec precision des heures
				[['IDuser'], 'fk'],				// Cles etrangeres
				[['id'], 'safe'],								// Champs proteges (n'apparaissent pas dans les formulaires)
			];
		}
		
		// Defini les labels standarts pour cet objet, affiches dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'data' => 'JSON data',
				'datecreation' => 'Date de creation',
				'datemodification' => 'Date de modification',
				'codeaffichage' => 'Code affichage',
				'codeedition' => 'Code edition',
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

		function save() {
			if (!$this->get("IDuser")>0) {
				if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"]>0)
					$this->set("IDuser", $_SESSION["currentUser"]);
				else
					Die("Error");
			}

			if (is_null($this->get("codeaffichage"))) {
				$this->set("codeaffichage", "");
			}

			if (is_null($this->get("codeedition"))) {
				$this->set("codeedition", "");
			}

			return parent::save();
		}
	}
	
?>
