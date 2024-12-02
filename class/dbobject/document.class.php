<?php
	namespace dbObject;


	class document extends DbObject
	{
	    public static function tableName()
		{
			return 'document'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['title'], 'required'],						// Champs obligatoires
				[['id','version'], 'integer'],							// Nombres entiers
				[['title','codeview','codeedit','keywords'], 'string'],							// Chaînes de caractère (max 250)
				[['description','content'], 'text'],			// Textes libres
				[['datecreation','datemodification'], 'datetime'],	// Date avec précision des heures
				[['IDuser'], 'fk'],									// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'description' => 'Résumé',
				'content' => 'Contenu',
				'keywords' => 'Mots clés',
				'IDuser' => 'Auteur',
				'datecreation' => 'Date de création',
				'datemodification' => 'Date de modification',
				'version' => 'Version',
				'codeview' => 'Code d\'affichage',
				'codeedit' => 'code d\'édition',
			];
		}

		// Ajoute un champ description, qui peut apparaître sous forme de bulle d'information ou en sous-titre
		public static function attributeDescriptions() {
			return [
				'title' => 'Titre affiché dans une liste de fichiers',
				'description' => 'Abstract du contenu du document',
				'content' => 'Formaté en texte libre ou en HTML',
				'IDuser' => 'Créateur du document',
			];
		}		

		// Défini les informations de taille pour le champ
		public static function attributeLength() {
			return [
				'title' => 100,										// Nombre de caractères maximum
			];
		}	
				
	
		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "datecreation";
		}
		
		// Retourne l'ensemble des médias attachés à un document
		public function getMedias() {
			$medias=new \dbobject\ArrayMedia();
			$params= array();	
			$params["filter"] = "IDdocument=".$this->get("id");
			$medias->load($params);
			return $medias;			
		}
		// Retourne l'ensemble des médias attachés à un document
		public function getAltText() {
			$medias=new \dbobject\ArrayAltText();
			$params= array();	
			$params["filter"] = "IDdocument=".$this->get("id");
			$medias->load($params);
			return $medias;			
		}
		
		public function canView() {
			// Uniquement les utilisateurs connectés auteur du document
			// exception faite de mot de passe codés dans les différentes pages
			return (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"]==$this->get("IDuser"));
		}
		
		
	}
	
?>
