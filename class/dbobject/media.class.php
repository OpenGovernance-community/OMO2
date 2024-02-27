<?php
	namespace dbObject;


	class media extends DbObject
	{
	    public static function tableName()
		{
			return 'media'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id','IDtype','IDstorage','accesskey'], 'required'],				// Champs obligatoires
				[['id','IDtype','IDstorage'], 'integer'],					
				[['title','filename','contenttype','accesskey'], 'string'],			// Chaînes de caractère (max 250)
				[['description'], 'text'],			// Texte libre
				[['datecreation'], 'datetime'],	// Date avec précision des heures
				[['IDdocument'], 'fk'],				// Clé étrangères
				[['id'], 'safe'],								// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'filename' => 'Nom de fichier',
				'contenttype' => 'Type de contenu',
				'description' => 'Résumé du contenu du média',
				'IDtype' => 'type de média',
				'IDstorage' => 'Espace de stockage',
				'accesskey' => 'Lien d\'accès',
				'IDdocument' => 'Document',
				'datecreation' => 'Date de création',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "ID desc";
		}
		
		public function canView() {
			// Si pas attaché à un document, fichier public
			if (!$this->get("IDdocument")>0) return true;
			// Visible si la variable de session a été posée, ou si auteur du document
			return ((isset($_SESSION["doc_".$this->get("IDdocument")]) && $_SESSION["doc_".$this->get("IDdocument")]) || (isset ($_SESSION["currentUser"]) && ($this->get("document")->get("IDuser")==$_SESSION["currentUser"])));

		}
		

		
	}
	
?>
