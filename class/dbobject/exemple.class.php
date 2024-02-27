<?php
	namespace dbObject;


	class license extends DbObject
	{
	    public static function tableName()
		{
			return 'license'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['name','number'], 'required'],				// Champs obligatoires
				[['id','age'], 'integer'],						// Nombres entiers
				[['height','weight'], 'float'],					// Nombres décimaux
				[['number','privatekey'], 'string'],			// Chaînes de caractère (max 250)
				[['description','tag'], 'text'],				// Textes libres
				[['photo'], 'sizedimage'],						// Images redimentionnables
				[['datecreation','datevalidity'], 'datetime'],	// Date avec précision des heures
				[['IDgender','IDheight'], 'fk'],				// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'number' => 'license number',
				'privatekey' => 'Private Key',
			];
		}

		// Ajoute un champ description, qui peut apparaître sous forme de bulle d'information ou en sous-titre
		public static function attributeDescriptions() {
			return [
				'name' => 'The name of your avatar',
				'photo' => 'A square picture of the head of your avatar (can be anything you want)',
				'age' => 'Your age',
			];
		}		

		// Défini les informations de taille pour le champ
		public static function attributeLength() {
			return [
				'name' => 20,										// Nombre de caractères maximum
				'photo' => [200,200],								// Taille (et ratio) de la photo
				'height' => 'Your height (choose the unity too)',	// Valeurs par défaut des selects
			];
		}	
				
	
		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "datecreation";
		}
		
		// Overload de la fonction save, pour ajouter la création d'un champ particulier. Sinon, pas besoin de cette fonction.
		function save() {
			
			if (is_null($this->get("number")) || $this->get("number")=="") {
				$this->set("number",$this->getToken(15));
			}
			
			// Appel de la fonction générique
			return parent::save();	
		}
		
		// Retourne un tableau d'éléments liés à cet objet
		public function getEvaluations() {
			$evaluations=new \dbobject\ArrayEvaluation($dbh);
			$params= array();	
			$params["filter"] = "IDlivre=".$this->get("id");
			$evaluations->load($params);
			return $evaluations;
		}
		
	}
	
?>
