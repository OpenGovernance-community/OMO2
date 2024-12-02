<?php
	namespace dbObject;


	class qr extends DbObject
	{
	    public static function tableName()
		{
			return 'qr'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['url'], 'required'],							// Champs obligatoires
				[['id','cpt'], 'integer'],	
				[['url'], 'text'],					// Nombres entiers
				[['uniquekey','shortcut','description'], 'string'],			// Chaînes de caractère (max 250)
				[['datecreation','datelastaccess'], 'datetime'],	// Date avec précision des heures
				[['IDuser'], 'fk'],				// Clé étrangères
				[['active'], 'boolean'],				// Clé étrangères
				[['id','uniquekey','datelastaccess','datecreation','cpt','IDuser'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'uniquekey' => 'Identifiant unique',
				'url' => 'URL',
				'IDuser' => 'Créateur',
				'shortcut' => 'Raccourci',
				'description' => 'Description',
				'cpt' => 'Compteur',
				'datelastaccess' => 'Dernier accès',
				'datecreation' => 'Date de création',
				'active' => 'Actif ?',
			];
		}
	
		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "datecreation";
		}
		
		// Overload de la fonction save, pour ajouter la création d'un champ particulier. Sinon, pas besoin de cette fonction.
		function save() {
			// S'assure d'avoir un user associé
			if (!$this->get("IDuser")>0)
				if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"]>0)
					$this->set("IDuser",$_SESSION["currentUser"]);
				else
					Die("Error");
					
			// Appel de la fonction générique
			$retValue=parent::save();	
			// Peut-être faut-il traiter la situation où il y a une erreur à ce niveau...
			
			// Crée le code unique si non existant
			if (is_null($this->get("uniquekey")) || $this->get("uniquekey")=="") {
				$this->set("uniquekey",$this->getToken(7).$this->getId().$this->getToken(7));
				return parent::save();
			}
			

		}

		
	}
	
?>
