<?php
	namespace dbObject;


	class user extends DbObject
	{
	    public static function tableName()
		{
			return 'user'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['email','username'], 'required'],				// Champs obligatoires
				[['id'], 'integer'],							// Nombres entiers
				[['username','email','firstname','lastname','code'], 'string'],			// Chaînes de caractère (max 250)
				[['password'], 'password'],						// Chaînes de caractère (max 250)
				[['parameters'], 'parameters'],						// Textes libres
				[['datecreation','dateconnexion','codeexpiration'], 'datetime'],	// Date avec précision des heures
				[['active'], 'boolean'],						// Date avec précision des heures
				[['id','password','email','code','datecreation','dateconnexion','codeexpiration'], 'safe'],			// Champs protégés (n'apparaîssent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'username' => 'Nom d\'utilisateur',
				'firstname' => 'Prénom',
				'lastname' => 'Nom',
				'email' => 'E-mail',
				'password' => 'Mot de passe',
				'code' => 'Code',
				'parameters' => 'Paramètres',
			];
		}

		// Ajoute un champ description, qui peut apparaître sous forme de bulle d'information ou en sous-titre
		public static function attributeDescriptions() {
			return [
				'username' => 'Un identifiant utilisé pour vous identifier dans une équipe, comme des initiales.',
				'firstname' => 'Simplement votre prénom.',
				'lastname' => 'Simplement votre nom de famille.',
				'email' => 'L\'adresse e-mail utilisée pour vous connecter et pour vous envoyer les messages du système.',
			];
		}		

		// Défini les informations de taille pour le champ
		public static function attributeLength() {
			return [
				'username' => 15,										// Nombre de caractères maximum
				'firstname' => 20,										// Nombre de caractères maximum
				'lastname' => 20,										// Nombre de caractères maximum
				'email' => 30										// Nombre de caractères maximum
			];
		}			
	
		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "firstname, lastname";
		}
		
		// Retourne un boolean indiquant si oui ou non l'utilisateur connecté a le droit d'afficher ce contenu
		public function canView() {
			if (isset($_SESSION["currentUser"])) return true;
			// Par défaut, ne peut voir que son profil. A compléter lorsque les users seront attachés à des équipes et des organisations.
			return false;
		}
		
		// Retourne un boolean indiquant si oui ou non l'utilisateur connecté a le droit d'éditer ce contenu
		public function canEdit() {
			if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"]==$this->getId()) return true;
			// Par défaut, ne peut compléter que son profil. A compléter lorsque des users fantômes seront créés.
			return false;
			
		}
		
	}
	
?>
