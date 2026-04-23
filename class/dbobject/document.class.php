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
				[['id', 'version'], 'integer'],				// Nombres entiers
				[['title', 'codeview', 'codeedit', 'keywords'], 'string'],	// Chaînes de caractère
				[['description', 'content'], 'text'],			// Textes libres
				[['datecreation', 'datemodification'], 'datetime'],	// Date avec précision des heures
				[['IDuser', 'IDorganization', 'IDholon'], 'fk'],	// Clés étrangères
				[['id'], 'safe'],								// Champs protégés
			];
		}

		// Défini les labels standards pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'description' => 'Résumé',
				'content' => 'Contenu',
				'keywords' => 'Mots clés',
				'IDuser' => 'Auteur',
				'IDorganization' => 'Organisation',
				'IDholon' => 'Holon',
				'datecreation' => 'Date de création',
				'datemodification' => 'Date de modification',
				'version' => 'Version',
				'codeview' => 'Code d\'affichage',
				'codeedit' => 'Code d\'édition',
			];
		}

		// Ajoute un champ description, qui peut apparaître sous forme de bulle d'information ou en sous-titre
		public static function attributeDescriptions()
		{
			return [
				'title' => 'Titre affiché dans une liste de fichiers',
				'description' => 'Abstract du contenu du document',
				'content' => 'Formaté en texte libre ou en HTML',
				'IDuser' => 'Créateur du document',
				'IDorganization' => 'Organisation à laquelle le document est rattaché',
				'IDholon' => 'Holon concerné si le document est spécifique à un contexte local',
			];
		}

		// Défini les informations de taille pour le champ
		public static function attributeLength()
		{
			return [
				'title' => 100,									// Nombre de caractères maximum
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder()
		{
			return "datecreation";
		}

		// Retourne l'ensemble des médias attachés à un document
		public function getMedias()
		{
			$medias = new \dbobject\ArrayMedia();
			$medias->load([
				"where" => [
					["field" => "IDdocument", "value" => $this->get("id")],
				],
			]);
			return $medias;
		}

		// Retourne l'ensemble des alternatives textuelles attachées à un document
		public function getAltText()
		{
			$medias = new \dbobject\ArrayAltText();
			$medias->load([
				"where" => [
					["field" => "IDdocument", "value" => $this->get("id")],
				],
			]);
			return $medias;
		}

		public function canView()
		{
			// Uniquement les utilisateurs connectés auteur du document
			// exception faite de mots de passe codés dans les différentes pages
			return (isset($_SESSION["currentUser"]) && $_SESSION["currentUser"] == $this->get("IDuser"));
		}

		public function matchesOrganizationContext(int $organizationId, ?int $holonId = null): bool
		{
			$organizationId = (int)$organizationId;
			$holonId = $holonId !== null ? (int)$holonId : 0;

			if ($organizationId <= 0) {
				return false;
			}

			$documentOrganizationId = (int)$this->get('IDorganization');
			$documentHolonId = (int)$this->get('IDholon');

			if ($documentOrganizationId !== $organizationId) {
				return false;
			}

			if ($holonId > 0) {
				return $documentHolonId === $holonId;
			}

			return $documentHolonId === 0;
		}

		public function canViewInOrganizationContext(int $organizationId, ?int $holonId = null): bool
		{
			return $this->matchesOrganizationContext($organizationId, $holonId)
				&& \commonCurrentUserHasOrganizationAccess($organizationId);
		}

		public function assignOrganizationContext(int $organizationId, ?int $holonId = null)
		{
			$organizationId = (int)$organizationId;
			$holonId = $holonId !== null ? (int)$holonId : 0;

			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'text' => "Organisation invalide.",
				);
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return array(
					'status' => false,
					'text' => "Organisation introuvable.",
				);
			}

			$resolvedHolonId = null;
			if ($holonId > 0) {
				$holon = new \dbObject\Holon();
				if (
					!$holon->load($holonId)
					|| !(bool)$holon->get('active')
					|| !(bool)$holon->get('visible')
					|| !$organization->containsHolon($holon)
				) {
					return array(
						'status' => false,
						'text' => "Holon introuvable pour cette organisation.",
					);
				}

				$resolvedHolonId = $holon->getId();
			}

			$this->set('IDorganization', $organizationId);
			$this->set('IDholon', $resolvedHolonId);

			return $this->save();
		}

		public function getOrganizationContextLabel(): string
		{
			$organizationId = (int)$this->get('IDorganization');
			if ($organizationId <= 0) {
				return '';
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return '';
			}

			$label = (string)$organization->get('name');
			$holonId = (int)$this->get('IDholon');
			if ($holonId <= 0) {
				return $label;
			}

			$holon = new \dbObject\Holon();
			if (!$holon->load($holonId)) {
				return $label;
			}

			$pathLabels = array();
			foreach ($holon->getPathHolons() as $pathHolon) {
				if ((int)$pathHolon->get('IDtypeholon') === 4) {
					continue;
				}

				$name = trim((string)$pathHolon->get('name'));
				if ($name !== '') {
					$pathLabels[] = $name;
				}
			}

			if (count($pathLabels) === 0) {
				return $label;
			}

			return $label." > ".implode(" > ", $pathLabels);
		}
	}

?>
