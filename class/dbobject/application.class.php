<?php
	namespace dbObject;

	class Application extends DbObject
	{
		private static array $organizationCatalogs = [];

		public static function clearOrganizationCatalogCache(): void
		{
			self::$organizationCatalogs = [];
			unset($_SESSION['applicationCatalogs']);
		}

		/** One version check per request; shared configuration is kept in the session. */
		public static function loadOrganizationCatalog(int $organizationId): array
		{
			if ($organizationId <= 0) { return []; }
			$key = hash('sha256', serialize([$GLOBALS['dbServer'] ?? '', $GLOBALS['dbName'] ?? '', $organizationId]));
			if (isset(self::$organizationCatalogs[$key])) { return self::$organizationCatalogs[$key]; }
			// Row digests avoid aggregate truncation and detect edits/deletes from other sessions too.
			$versions = self::fetchAll("SELECT CONCAT('a', id) AS item, SHA2(JSON_ARRAY(id, label, hash, directory, icon, drawer, url, navigationmode, position, requires_login, active), 256) AS revision FROM application
				UNION ALL SELECT CONCAT('o', id), SHA2(JSON_ARRAY(id, IDapplication, position, active), 256)
				FROM organization_application WHERE IDorganization = :organization ORDER BY item", ['organization' => $organizationId]);
			if ($versions === false) { return []; }
			$version = hash('sha256', json_encode($versions, JSON_THROW_ON_ERROR));
			$cached = $_SESSION['applicationCatalogs'][$key] ?? null;
			if (is_array($cached) && ($cached['version'] ?? '') === $version && is_array($cached['rows'] ?? null)) {
				return self::$organizationCatalogs[$key] = $cached['rows'];
			}
			$rows = self::fetchAll('SELECT a.*, oa.active AS organization_active, oa.position AS organization_position
				FROM application a LEFT JOIN organization_application oa ON oa.IDapplication = a.id AND oa.IDorganization = :organization
				ORDER BY COALESCE(oa.position, a.position, 999999), a.label, a.id', ['organization' => $organizationId]);
			if ($rows === false) { return []; }
			$_SESSION['applicationCatalogs'][$key] = ['version' => $version, 'rows' => $rows];
			return self::$organizationCatalogs[$key] = $rows;
		}

	    public static function tableName()
		{
			return 'application';
		}

		public static function rules()
		{
			return [
				[['label'], 'required'],
				[['id', 'position'], 'integer'],
				[['label', 'hash', 'directory', 'icon', 'drawer', 'url', 'navigationmode'], 'string'],
				[['requires_login', 'active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'label' => 'LibellÃ©',
				'hash' => 'Hash',
				'directory' => 'RÃ©pertoire',
				'icon' => 'IcÃ´ne',
				'drawer' => 'Drawer',
				'url' => 'URL',
				'navigationmode' => 'Mode de navigation',
				'position' => 'Position',
				'requires_login' => 'Connexion requise',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'label' => 'Texte visible dans la barre latÃ©rale',
				'hash' => 'Hash utilisÃ© pour le routage OMO',
				'directory' => 'RÃ©pertoire du module dans /omo/api',
				'icon' => 'Chemin de lâ€™icÃ´ne Ã  afficher',
				'drawer' => 'Identifiant du drawer Ã  ouvrir',
				'url' => 'URL du contenu Ã  charger pour ce module',
				'navigationmode' => 'panel pour revenir Ã  la structure, drawer pour ouvrir un module',
				'position' => 'Ordre dâ€™affichage',
				'requires_login' => 'Masque le module aux visiteurs non connectÃ©s',
				'active' => 'Permet de dÃ©sactiver globalement le module',
			];
		}

		public static function attributeLength()
		{
			return [
				'label' => 100,
				'hash' => 100,
				'directory' => 100,
				'icon' => 255,
				'drawer' => 100,
				'url' => 255,
				'navigationmode' => 20,
			];
		}

		public static function getOrder()
		{
			return "position ASC, label ASC";
		}

		public function getRouteHash()
		{
			return trim((string)$this->get('hash'));
		}

		public function getNavigationMode()
		{
			$mode = strtolower(trim((string)$this->get('navigationmode')));
			return in_array($mode, ['panel', 'drawer'], true) ? $mode : 'drawer';
		}

		public function getResolvedUrl()
		{
			$url = trim((string)$this->get('url'));
			if ($url !== '') {
				return $url;
			}

			$directory = trim((string)$this->get('directory'), "/ \t\n\r\0\x0B");
			if ($directory !== '') {
				return 'api/' . $directory . '/index.php';
			}

			return '';
		}

		public function getResolvedFilesystemTarget()
		{
			$url = trim((string)$this->getResolvedUrl());
			if ($url === '') {
				return '';
			}

			if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $url) === 1) {
				return '';
			}

			$path = trim((string)parse_url($url, PHP_URL_PATH));
			if ($path === '') {
				return '';
			}

			$relativePath = ltrim($path, "/\\");
			if ($relativePath === '') {
				return '';
			}

			if ($path[0] !== '/' && strpos($relativePath, 'omo/') !== 0) {
				$relativePath = 'omo/' . $relativePath;
			}

			$basePath = dirname(__DIR__, 2);
			return $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
		}

		public function getParametersFilesystemTarget()
		{
			$directory = trim((string)$this->get('directory'), "/ \t\n\r\0\x0B");
			if ($directory === '') {
				return '';
			}

			$basePath = dirname(__DIR__, 2);
			return $basePath
				. DIRECTORY_SEPARATOR . 'omo'
				. DIRECTORY_SEPARATOR . 'api'
				. DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory)
				. DIRECTORY_SEPARATOR . 'params'
				. DIRECTORY_SEPARATOR . 'index.php';
		}

		public function hasOrganizationParametersEntryPoint()
		{
			$target = $this->getParametersFilesystemTarget();
			return $target !== '' && is_file($target);
		}

		public function getOrganizationParametersUrl()
		{
			$directory = trim((string)$this->get('directory'), "/ \t\n\r\0\x0B");
			if ($directory === '') {
				return '';
			}

			return '/omo/api/' . $directory . '/params/index.php';
		}

		public function hasResolvedEntryPoint()
		{
			static $cache = array();

			$url = trim((string)$this->getResolvedUrl());
			$directory = trim((string)$this->get('directory'));
			$cacheKey = $url . '|' . $directory;

			if (array_key_exists($cacheKey, $cache)) {
				return $cache[$cacheKey];
			}

			if ($url === '') {
				$cache[$cacheKey] = false;
				return false;
			}

			if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $url) === 1) {
				$cache[$cacheKey] = true;
				return true;
			}

			$target = $this->getResolvedFilesystemTarget();
			$cache[$cacheKey] = ($target !== '' && is_file($target));
			return $cache[$cacheKey];
		}

		public function getResolvedDrawer()
		{
			$drawer = trim((string)$this->get('drawer'));
			if ($drawer !== '') {
				return $drawer;
			}

			$hash = $this->getRouteHash();
			if ($hash !== '') {
				return 'drawer_' . $hash;
			}

			return '';
		}

		public function requiresLogin()
		{
			return (bool)$this->get('requires_login');
		}

		public static function isEnabledForOrganization($applicationId, $organizationId)
		{
			if ((int)$applicationId <= 0 || (int)$organizationId <= 0) { return false; }
			foreach (self::loadOrganizationCatalog((int)$organizationId) as $row) {
				if ((int)$row['id'] !== (int)$applicationId) { continue; }
				$probe = new self();
				$probe->set('url', $row['url']); $probe->set('directory', $row['directory']);
				return (int)$row['active'] === 1 && (int)$row['organization_active'] === 1
					&& trim((string)$row['navigationmode']) !== 'panel' && $probe->hasResolvedEntryPoint();
			}
			return false;
		}

		public static function fetchFaqAttachmentOptions()
		{
			return self::fetchAll(
				"SELECT id, label
				FROM application
				WHERE active = 1
				ORDER BY position ASC, label ASC, id ASC"
			);
		}
	}

?>
