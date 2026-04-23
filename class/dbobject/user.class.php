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
				[['email'], 'required'], // Champs obligatoires
				[['id'], 'integer'], // Nombres entiers
				[['username', 'email', 'firstname', 'lastname', 'code', 'telegramID'], 'string'], // Chaînes de caractère
				[['password'], 'password'], // Mot de passe
				[['parameters', 'param_easypv', 'param_easymemo', 'param_easycircle'], 'parameters'], // Textes libres
				[['datecreation', 'dateconnexion', 'codeexpiration'], 'datetime'], // Dates avec heures
				[['active'], 'boolean'], // Booléens
				[['id', 'password', 'email', 'code', 'datecreation', 'dateconnexion', 'codeexpiration', 'telegramID'], 'safe'], // Champs protégés
			];
		}

		// Défini les labels standards pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'username' => 'Nom d\'utilisateur',
				'firstname' => 'Prénom',
				'lastname' => 'Nom',
				'email' => 'E-mail',
				'telegramID' => 'ID Telegram',
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
				'telegramID' => 'Identifiant numérique utilisé pour associer votre compte Telegram.',
			];
		}

		// Défini les informations de taille pour le champ
		public static function attributeLength() {
			return [
				'username' => 30, // Nombre de caractères maximum
				'firstname' => 25,
				'lastname' => 25,
				'email' => 30,
				'telegramID' => 100,
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "firstname, lastname";
		}

		// Retourne un boolean indiquant si oui ou non l'utilisateur connecté a le droit d'afficher ce contenu
		public function canView() {
			if (isset($_SESSION["currentUser"])) {
				return true;
			}

			// Par défaut, ne peut voir que son profil. A compléter lorsque les users seront attachés à des équipes et des organisations.
			return false;
		}

		// Retourne un boolean indiquant si oui ou non l'utilisateur connecté a le droit d'éditer ce contenu
		public function canEdit() {
			if (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"] == $this->getId()) {
				return true;
			}

			// Par défaut, ne peut compléter que son profil. A compléter lorsque des users fantômes seront créés.
			return false;
		}

		public function getPrompt() {
			$prompts = new \dbObject\ArrayAIPrompt();
			$prompts->load([
				"whereAny" => [
					["field" => "IDuser", "value" => $this->get("id")],
					["field" => "ispublic", "value" => 1],
				],
			]);
			return $prompts;
		}

		public function getAccessibleOrganizations() {
			$organizations = new ArrayOrganization();
			$organizations->loadAccessibleForUser($this->getId());
			return $organizations;
		}

		public function hasOrganizationAccess($organizationId) {
			$organizationId = (int)$organizationId;
			if ((int)$this->getId() <= 0 || $organizationId <= 0) {
				return false;
			}

			$organizations = new ArrayOrganization();
			$organizations->loadAccessibleForUser($this->getId(), $organizationId, 1);
			return count($organizations) > 0;
		}
	}

?>
