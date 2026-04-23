<?php
	namespace dbObject;


	class Organization extends DbObject
	{
	    public static function tableName()
		{
			return 'organization'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['name'], 'required'],								// Champs obligatoires
				[['id'], 'integer'],								// Nombres entiers
				[['name','shortname','domain','color'], 'string'],	// Chaînes de caractère
				[['logo','banner'], 'image'],						// Images
				[['id'], 'safe'],									// Champs protégés
			];
		}
		
		// Défini les labels standards pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'shortname' => 'Nom court',
				'domain' => 'Domaine',
				'logo' => 'Logo',
				'banner' => 'Bannière',
				'color' => 'Couleur',
			];
		}

		public static function attributeDescriptions() {
			return [
				'name' => 'Nom complet de l\'organisation',
				'shortname' => 'Nom abrégé utilisé dans l\'interface',
				'domain' => 'Nom de domaine principal de l\'organisation',
				'logo' => 'Logo de l\'organisation',
				'banner' => 'Image de bannière de l\'organisation',
				'color' => 'Couleur principale au format hexadécimal ou texte court',
			];
		}

		public static function attributeLength() {
			return [
				'name' => 100,
				'shortname' => 50,
				'domain' => 100,
				'logo' => 100,
				'banner' => 100,
				'color' => 10,
			];
		}
				
		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}

		public static function resolveFromHost($host, $defaultId = 1) {
			$host = is_string($host) ? trim($host) : "";
			if ($host === "") {
				return false;
			}

			$host = preg_replace('/:\d+$/', '', $host);
			$parts = array_values(array_filter(explode(".", $host)));
			$isLocalhostSubdomain = count($parts) === 2 && ($parts[1] ?? '') === 'localhost';

			$organization = new self();
			if (count($parts) < 3 && !$isLocalhostSubdomain) {
				return $organization->load((int)$defaultId) ? $organization : false;
			}

			return $organization->load(['shortname', $parts[0]]) ? $organization : false;
		}

		public function getStructuralRootHolon()
		{
			if ((int)$this->getId() <= 0) {
				return null;
			}

			$holons = new \dbObject\ArrayHolon();
			$holons->load(array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => (int)$this->getId()),
					array('field' => 'IDtypeholon', 'value' => 4),
					array('field' => 'active', 'value' => 1),
					array('field' => 'visible', 'value' => 1),
				),
				'whereAny' => array(
					array('field' => 'IDholon_parent', 'op' => 'is null'),
					array('field' => 'IDholon_parent', 'value' => 0),
				),
				'orderBy' => array(
					array('field' => 'id', 'dir' => 'ASC'),
				),
				'limit' => 1,
			));

			foreach ($holons as $holon) {
				return $holon;
			}

			return null;
		}

		public function getApplications($userId = null)
		{
			$applications = new \dbObject\ArrayApplication();
			$applications->loadEnabledForOrganization((int)$this->getId(), $userId !== null ? (int)$userId : 0);
			return $applications;
		}
		
	}
	
?>
