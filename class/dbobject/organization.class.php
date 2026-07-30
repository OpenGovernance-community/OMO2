<?php
	namespace dbObject;

	require_once dirname(__DIR__, 2) . '/common/environment_subdomains.php';


	class Organization extends DbObject
	{
		public const SYSTEM_ORGANIZATION_ID = 1;
		protected $lastDeleteError = '';

	    public static function tableName()
		{
			return 'organization'; // Nom de la table correspondante
		}	
		
		// Defini le contenu de la table
		public static function rules()
		{
			return [
				[['name'], 'required'],								// Champs obligatoires
				[['id'], 'integer'],								// Nombres entiers
				[['datecreation'], 'datetime'],
				[['name','shortname','domain'], 'string'],	// Chaines de caractere
				[['latlong'], 'latlong'],
				[['parameters'], 'parameters'],
				[['shortname'], 'unique'],
				[['logo','banner'], 'sizedimage'],	
				[['color'],'color'],				// Images
				[['id'], 'safe'],									// Champs proteges
			];
		}
		
		// Defini les labels standards pour cet objet, affiches dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'shortname' => 'Nom court',
				'domain' => 'Domaine',
				'latlong' => 'Position geographique',
				'logo' => 'Logo',
				'banner' => 'Banniere',
				'color' => 'Couleur',
				'datecreation' => 'Date de creation',
			];
		}

		public static function attributeDescriptions() {
			return [
				'name' => 'Nom complet de l\'organisation',
				'shortname' => 'Nom abrege utilise dans l\'interface et dans l\'URL de l\'organisation',
				'domain' => 'Nom de domaine principal de l\'organisation',
				'latlong' => 'Position geographique facultative de l organisation pour l affichage sur une carte.',
				'logo' => 'Logo de l\'organisation',
				'banner' => 'Image de banniere de l\'organisation',
				'color' => 'Couleur principale au format hexadecimal ou texte court',
			];
		}

		public static function attributeLength() {
			return [
				'name' => 100,
				'shortname' => 50,
				'domain' => 100,
				'latlong' => 100,
				'logo' => [[500, 500],[180,180]],
				'banner' => [[960, 540],[480, 270]],
				'color' => 10,
			];
		}

		public static function publicReadableFields()
		{
			return array(
				'id',
				'name',
				'shortname',
				'domain',
				'logo',
				'banner',
				'color',
				'latlong',
			);
		}

		public static function fetchPublicMapRows($limit = null): array
		{
			$publicFields = array_values(array_intersect(
				static::publicReadableFields(),
				array('id', 'name', 'shortname', 'domain', 'logo', 'banner', 'color', 'latlong')
			));
			if (count($publicFields) === 0) {
				return array();
			}

			$query = "
				SELECT " . implode(', ', $publicFields) . "
				FROM organization
				WHERE latlong IS NOT NULL
				  AND latlong <> ''
				ORDER BY name ASC";
			if ($limit !== null && (int)$limit > 0) {
				$query .= "
				LIMIT " . (int)$limit;
			}

			$rows = self::fetchAll($query);
			if ($rows === false) {
				return array();
			}

			$result = array();
			foreach ($rows as $row) {
				$organizationId = (int)($row['id'] ?? 0);
				if ($organizationId <= 0) {
					continue;
				}

				$organization = new self();
				$organization->loadFromArray($row);

				$latlong = $organization->get('latlong');
				$latitude = is_object($latlong) ? ($latlong->lat ?? null) : null;
				$longitude = is_object($latlong) ? ($latlong->long ?? null) : null;
				if (!is_numeric($latitude) || !is_numeric($longitude)) {
					continue;
				}

				$result[] = array(
					'id' => $organizationId,
					'name' => trim((string)$organization->get('name')),
					'logo' => trim((string)$organization->get('logo')),
					'color' => trim((string)$organization->get('color')),
					'latlong' => array(
						'lat' => (float)$latitude,
						'long' => (float)$longitude,
					),
				);
			}

			return $result;
		}

		public function getParametersArray(): array
		{
			$parameters = json_decode((string)$this->get('parameters'), true);
			return is_array($parameters) ? $parameters : array();
		}

		public function setParametersArray(array $parameters): void
		{
			$this->set('parameters', $parameters);
		}

		public static function getDefaultLexicon(): array
		{
			return array(
				'tension' => array(
					'label' => 'Tension',
					'article' => 'une',
				),
				'admin' => array(
					'label' => 'Admin',
				),
			);
		}

		public static function normalizeLexicon(array $lexicon): array
		{
			$defaults = self::getDefaultLexicon();
			$normalized = $defaults;

			foreach ($defaults as $key => $defaultTerm) {
				$value = $lexicon[$key] ?? array();
				if (is_string($value)) {
					$value = array('label' => $value);
				}
				if (!is_array($value)) {
					$value = array();
				}

				$label = trim((string)($value['label'] ?? ''));
				if ($label !== '') {
					$normalized[$key]['label'] = function_exists('mb_substr')
						? mb_substr($label, 0, 80, 'UTF-8')
						: substr($label, 0, 80);
				}

				if (array_key_exists('article', $defaultTerm)) {
					$article = trim((string)($value['article'] ?? ''));
					if ($article !== '') {
						$normalized[$key]['article'] = function_exists('mb_substr')
							? mb_substr($article, 0, 20, 'UTF-8')
							: substr($article, 0, 20);
					}
				}
			}

			return $normalized;
		}

		public function getLexicon(): array
		{
			$parameters = $this->getParametersArray();
			$lexicon = $parameters['lexicon'] ?? array();

			return self::normalizeLexicon(is_array($lexicon) ? $lexicon : array());
		}

		public function setLexicon(array $lexicon): void
		{
			$parameters = $this->getParametersArray();
			$parameters['lexicon'] = self::normalizeLexicon($lexicon);
			$this->setParametersArray($parameters);
		}

		public function getApplicationLinkByDirectory(string $directory, bool $activeOnly = false): ?\dbObject\OrganizationApplication
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return null;
			}

			return \dbObject\OrganizationApplication::loadByOrganizationAndDirectory($organizationId, $directory, $activeOnly);
		}

		public function ensureApplicationLinkByDirectory(string $directory): ?\dbObject\OrganizationApplication
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return null;
			}

			return \dbObject\OrganizationApplication::ensureByOrganizationAndDirectory($organizationId, $directory);
		}

		public function getApplicationParametersByDirectory(string $directory, bool $activeOnly = false): array
		{
			$link = $this->getApplicationLinkByDirectory($directory, $activeOnly);
			return $link ? $link->getParametersArray() : array();
		}

		public function getNextcloudDocumentsConfig(): array
		{
			require_once dirname(__DIR__, 2) . '/omo/api/documents/params/shared.php';

			if (\function_exists('omoDocumentsParamsGetNextcloudConfig')) {
				return \omoDocumentsParamsGetNextcloudConfig($this);
			}

			return array(
				'baseUrl' => '',
				'username' => '',
				'appPassword' => '',
				'folder' => '',
			);
		}

		public function hasNextcloudDocumentStorage(): bool
		{
			require_once dirname(__DIR__, 2) . '/omo/api/documents/params/shared.php';

			if (\function_exists('omoDocumentsParamsHasNextcloudConfig')) {
				return \omoDocumentsParamsHasNextcloudConfig($this->getNextcloudDocumentsConfig());
			}

			$config = $this->getNextcloudDocumentsConfig();
			return $config['baseUrl'] !== ''
				&& $config['username'] !== ''
				&& $config['appPassword'] !== '';
		}

		protected function buildNextcloudDocumentsDavUrl(string $relativePath = ''): string
		{
			$config = $this->getNextcloudDocumentsConfig();
			if (!$this->hasNextcloudDocumentStorage()) {
				return '';
			}

			$segments = array_filter(
				array_map('trim', explode('/', trim($relativePath, '/'))),
				static function ($segment) {
					return $segment !== '';
				}
			);

			$encodedPath = '';
			if (count($segments) > 0) {
				$encodedPath = '/' . implode('/', array_map('rawurlencode', $segments));
			}

			return $config['baseUrl']
				. '/remote.php/dav/files/'
				. rawurlencode($config['username'])
				. $encodedPath;
		}

		protected function executeNextcloudDocumentsRequest(string $method, string $relativePath = '', array $options = array()): array
		{
			if (!$this->hasNextcloudDocumentStorage()) {
				return array(
					'status' => false,
					'text' => 'Le stockage Nextcloud n est pas configure pour cette organisation.',
				);
			}

			if (!function_exists('curl_init')) {
				return array(
					'status' => false,
					'text' => 'cURL est requis pour communiquer avec Nextcloud.',
				);
			}

			$config = $this->getNextcloudDocumentsConfig();
			$url = $this->buildNextcloudDocumentsDavUrl($relativePath);
			if ($url === '') {
				return array(
					'status' => false,
					'text' => 'URL Nextcloud invalide.',
				);
			}

			$headers = array();
			if (!empty($options['headers']) && is_array($options['headers'])) {
				$headers = array_values($options['headers']);
			}

			$curl = curl_init($url);
			$body = array_key_exists('body', $options) ? $options['body'] : null;
			$timeout = isset($options['timeout']) ? max(5, (int)$options['timeout']) : 120;

			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, $config['username'] . ':' . $config['appPassword']);
			curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			if ($body !== null) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
			}

			$response = curl_exec($curl);
			$curlError = curl_error($curl);
			$httpCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
			$headerSize = (int)curl_getinfo($curl, CURLINFO_HEADER_SIZE);

			if ($response === false) {
				return array(
					'status' => false,
					'text' => $curlError !== '' ? $curlError : 'La requete Nextcloud a echoue.',
				);
			}

			$rawHeaders = substr((string)$response, 0, max(0, $headerSize));
			$responseBody = substr((string)$response, max(0, $headerSize));
			$headerMap = array();
			foreach (preg_split("/\r\n|\n|\r/", $rawHeaders) as $headerLine) {
				$separatorPosition = strpos($headerLine, ':');
				if ($separatorPosition === false) {
					continue;
				}

				$headerName = strtolower(trim(substr($headerLine, 0, $separatorPosition)));
				$headerValue = trim(substr($headerLine, $separatorPosition + 1));
				if ($headerName !== '') {
					$headerMap[$headerName] = $headerValue;
				}
			}

			return array(
				'status' => $httpCode >= 200 && $httpCode < 300,
				'httpCode' => $httpCode,
				'headers' => $headerMap,
				'body' => $responseBody,
				'url' => $url,
				'text' => ($httpCode >= 200 && $httpCode < 300)
					? 'OK'
					: (
						'Requete Nextcloud refusee (' . $httpCode . ')'
						. (
							trim((string)parse_url($url, PHP_URL_PATH)) !== ''
								? ' pour ' . trim((string)parse_url($url, PHP_URL_PATH))
								: ''
						)
						. '.'
					),
			);
		}

		protected function ensureNextcloudDocumentsFolder(string $relativeFolder): array
		{
			$normalizedFolder = trim(str_replace('\\', '/', $relativeFolder), '/');
			if ($normalizedFolder === '') {
				return array('status' => true);
			}

			$segments = array_filter(explode('/', $normalizedFolder), static function ($segment) {
				return trim((string)$segment) !== '';
			});
			$currentPath = '';

			foreach ($segments as $segment) {
				$currentPath = $currentPath === '' ? $segment : ($currentPath . '/' . $segment);
				$result = $this->executeNextcloudDocumentsRequest('MKCOL', $currentPath, array(
					'timeout' => 30,
				));

				if (!is_array($result)) {
					return array(
						'status' => false,
						'text' => 'Impossible de creer le dossier distant.',
					);
				}

				$httpCode = (int)($result['httpCode'] ?? 0);
				if (!in_array($httpCode, array(201, 405), true) && empty($result['status'])) {
					return array(
						'status' => false,
						'text' => trim((string)($result['text'] ?? 'Impossible de preparer le dossier distant.')),
					);
				}
			}

			return array('status' => true);
		}

		protected function buildNextcloudDocumentStorageRoot(): string
		{
			$config = $this->getNextcloudDocumentsConfig();
			$parts = array();
			if ($config['folder'] !== '') {
				$parts[] = trim($config['folder'], '/');
			}

			$parts[] = 'omo-documents';

			return implode('/', $parts);
		}

		protected static function sanitizeNextcloudRemoteFilename(string $filename): string
		{
			$filename = basename(trim($filename));
			$filename = preg_replace('/[^\pL\pN._-]+/u', '-', $filename);
			$filename = trim((string)$filename, '.-');
			return $filename !== '' ? $filename : ('document-' . date('Ymd-His'));
		}

		public function uploadDocumentFileToNextcloud(int $documentId, array $uploadedFile): array
		{
			$documentId = (int)$documentId;
			if ($documentId <= 0) {
				return array(
					'status' => false,
					'text' => 'Document invalide pour le stockage distant.',
				);
			}

			$tmpName = trim((string)($uploadedFile['tmp_name'] ?? ''));
			$errorCode = (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
			if ($errorCode !== UPLOAD_ERR_OK || $tmpName === '' || !is_file($tmpName)) {
				return array(
					'status' => false,
					'text' => 'Aucun fichier valide n a ete televerse.',
				);
			}

			$originalName = trim((string)($uploadedFile['name'] ?? ''));
			$mimeType = trim((string)($uploadedFile['type'] ?? ''));
			if ($mimeType === '' && function_exists('mime_content_type')) {
				$mimeType = trim((string)mime_content_type($tmpName));
			}
			if ($mimeType === '') {
				$mimeType = 'application/octet-stream';
			}

			$fileSize = isset($uploadedFile['size']) ? (int)$uploadedFile['size'] : (int)filesize($tmpName);
			$remoteFilename = self::sanitizeNextcloudRemoteFilename($originalName !== '' ? $originalName : ('document-' . $documentId));
			$storageDirectory = $this->buildNextcloudDocumentStorageRoot() . '/' . $documentId;

			$directoryResult = $this->ensureNextcloudDocumentsFolder($storageDirectory);
			if (!is_array($directoryResult) || empty($directoryResult['status'])) {
				return $directoryResult;
			}

			$fileContent = file_get_contents($tmpName);
			if ($fileContent === false) {
				return array(
					'status' => false,
					'text' => 'Impossible de lire le fichier a envoyer.',
				);
			}

			$relativePath = $storageDirectory . '/' . $remoteFilename;
			$uploadResult = $this->executeNextcloudDocumentsRequest('PUT', $relativePath, array(
				'body' => $fileContent,
				'timeout' => 300,
				'headers' => array(
					'Content-Type: ' . $mimeType,
					'Content-Length: ' . strlen($fileContent),
				),
			));

			if (!is_array($uploadResult) || empty($uploadResult['status'])) {
				return array(
					'status' => false,
					'text' => trim((string)($uploadResult['text'] ?? 'Impossible d envoyer le fichier vers Nextcloud.')),
				);
			}

			return array(
				'status' => true,
				'relativePath' => $relativePath,
				'originalName' => $originalName !== '' ? $originalName : $remoteFilename,
				'mimeType' => $mimeType,
				'size' => max(0, $fileSize),
			);
		}

		public function deleteDocumentFileFromNextcloud(string $relativePath): array
		{
			$normalizedPath = trim(str_replace('\\', '/', $relativePath), '/');
			if ($normalizedPath === '') {
				return array('status' => true);
			}

			$result = $this->executeNextcloudDocumentsRequest('DELETE', $normalizedPath, array(
				'timeout' => 120,
			));
			if (!is_array($result)) {
				return array(
					'status' => false,
					'text' => 'Impossible de supprimer le fichier distant.',
				);
			}

			$httpCode = (int)($result['httpCode'] ?? 0);
			if (in_array($httpCode, array(204, 404), true) || !empty($result['status'])) {
				return array('status' => true);
			}

			return array(
				'status' => false,
				'text' => trim((string)($result['text'] ?? 'Impossible de supprimer le fichier distant.')),
			);
		}

		public function downloadDocumentFileFromNextcloud(string $relativePath): array
		{
			$normalizedPath = trim(str_replace('\\', '/', $relativePath), '/');
			if ($normalizedPath === '') {
				return array(
					'status' => false,
					'text' => 'Chemin de fichier distant invalide.',
				);
			}

			$result = $this->executeNextcloudDocumentsRequest('GET', $normalizedPath, array(
				'timeout' => 300,
				'headers' => array(
					'Accept: */*',
				),
			));
			if (!is_array($result) || empty($result['status'])) {
				return array(
					'status' => false,
					'text' => trim((string)($result['text'] ?? 'Impossible de recuperer le fichier distant.')),
				);
			}

			return array(
				'status' => true,
				'body' => (string)($result['body'] ?? ''),
				'contentType' => trim((string)(($result['headers']['content-type'] ?? 'application/octet-stream'))),
				'contentLength' => isset($result['headers']['content-length']) ? (int)$result['headers']['content-length'] : strlen((string)($result['body'] ?? '')),
			);
		}

		public static function attributePattern()
		{
			return [
				'shortname' => [
					'/^[A-Za-z0-9_-]+$/',
					'use only letters, digits, "-" and "_"'
				],
			];
		}

		protected function normalizeShortname($value)
		{
			$value = is_scalar($value) ? trim((string)$value) : '';
			if ($value === '') {
				return null;
			}

			return strtolower($value);
		}

		protected function validateShortnameValue($value)
		{
			$value = is_scalar($value) ? trim((string)$value) : '';
			if ($value === '') {
				return array(
					'status' => true,
				);
			}

			if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
				return array(
					'status' => false,
					'text' => 'Le nom court ne peut contenir que des lettres, chiffres, tirets et underscores.',
				);
			}

			$params = array(
				'shortname' => $value,
			);
			$query = "SELECT id
				FROM organization
				WHERE LOWER(shortname) = :shortname";
			if ((int)$this->getId() > 0) {
				$query .= " AND id != :current_id";
				$params['current_id'] = (int)$this->getId();
			}
			$query .= " LIMIT 1";

			$existing = self::fetchRow($query, $params);
			if ($existing !== false) {
				return array(
					'status' => false,
					'text' => 'Ce nom court est deja utilise par une autre organisation. Choisissez-en un autre.',
				);
			}

			return array(
				'status' => true,
			);
		}

		public function set($field, $value)
		{
			if ($field === 'shortname') {
				$value = $this->normalizeShortname($value);
			}

			parent::set($field, $value);
		}
				
		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}

		public function save()
		{
			$shortnameValidation = $this->validateShortnameValue($this->get('shortname'));
			if (!is_array($shortnameValidation) || empty($shortnameValidation['status'])) {
				return is_array($shortnameValidation)
					? $shortnameValidation
					: array(
						'status' => false,
						'text' => "Le nom court de l'organisation est invalide.",
					);
			}

			return parent::save();
		}

		public function instantiateBasicParcours()
		{
			return \dbObject\Parcours::instantiateBasicForOrganization((int)$this->getId());
		}

		public function canView()
		{
			return $this->canViewDetail();
		}

		public function canViewDetail()
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return false;
			}

			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);

			if (function_exists('commonUserHasOrganizationMembership') && \commonUserHasOrganizationMembership($currentUserId, $organizationId)) {
				return true;
			}

			if (function_exists('commonCurrentShareCanViewOrganization')) {
				return \commonCurrentShareCanViewOrganization($organizationId);
			}

			return false;
		}

		protected function resolveCurrentUserId()
		{
			return function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
		}

		public function getMembership($userId, $activeOnly = false)
		{
			$userId = (int)$userId;
			if ((int)$this->getId() <= 0 || $userId <= 0) {
				return null;
			}

			$membership = new \dbObject\UserOrganization();
			if (!$membership->load(array(
				array('IDuser', $userId),
				array('IDorganization', (int)$this->getId()),
			))) {
				return null;
			}

			if ($activeOnly && !(bool)$membership->get('active')) {
				return null;
			}

			return $membership;
		}

		public function isUserOrganizationAdmin($userId)
		{
			if (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($userId, (int)$this->getId())) {
				return true;
			}

			if ($userId === $this->resolveCurrentUserId()) {
				return false;
			}

			$membership = $this->getMembership($userId, true);
			return $membership ? $membership->isOrganizationAdmin() : false;
		}

		public function canEdit()
		{
			return $this->isUserOrganizationAdmin($this->resolveCurrentUserId());
		}

		public function canDelete()
		{
			return !$this->isSystemOrganization() && $this->canEdit();
		}

		public function isSystemOrganization()
		{
			return (int)$this->getId() === self::SYSTEM_ORGANIZATION_ID;
		}

		public function countActiveAdminMemberships($excludeUserId = 0)
		{
			$organizationId = (int)$this->getId();
			$excludeUserId = (int)$excludeUserId;
			if ($organizationId <= 0) {
				return 0;
			}

			$query = "
				SELECT parameters
				FROM user_organization
				WHERE IDorganization = :organization_id
				  AND active = 1
			";
			$params = array(
				'organization_id' => $organizationId,
			);

			if ($excludeUserId > 0) {
				$query .= "
				  AND IDuser != :exclude_user_id";
				$params['exclude_user_id'] = $excludeUserId;
			}

			$rows = self::fetchAll($query, $params);
			if ($rows === false) {
				return 0;
			}

			$count = 0;
			foreach ($rows as $row) {
				$parameters = json_decode((string)($row['parameters'] ?? ''), true);
				if (is_array($parameters) && !empty($parameters['isAdmin'])) {
					$count += 1;
				}
			}

			return $count;
		}

		public function ensureDefaultApplicationLinks(): array
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Organisation invalide.',
				);
			}

			$result = self::execute(
				"INSERT IGNORE INTO organization_application (IDorganization, IDapplication, position, active)
				SELECT :organization_id, a.id, a.position, 1
				FROM application a",
				array(
					'organization_id' => $organizationId,
				)
			);

			if (!$result) {
				return array(
					'status' => false,
					'message' => 'Impossible d initialiser les applications de l organisation.',
				);
			}

			return array(
				'status' => true,
			);
		}

		public function synchronizeOmo1ImportedApplicationLinks(array $selectedModules, array $sourceModules): array
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Organisation invalide.',
				);
			}

			$activeApplicationKeys = array(
				'structure' => true,
			);
			$moduleApplicationMap = array(
				'rules' => array('policy'),
				'members' => array('team'),
				'documents' => array('documents'),
				'projects' => array('projects'),
				'tasks' => array('projects'),
				'checklists' => array('checklist'),
				'indicators' => array('stats'),
				'calendar' => array('calendar'),
				'pv' => array('calendar'),
			);
			foreach ($moduleApplicationMap as $module => $applicationKeys) {
				if (empty($selectedModules[$module])) {
					continue;
				}
				foreach ($applicationKeys as $applicationKey) {
					$activeApplicationKeys[$applicationKey] = true;
				}
			}

			$importedAllAvailableModules = true;
			foreach (array_keys($moduleApplicationMap) as $module) {
				if (empty($sourceModules[$module]['selected']) || empty($selectedModules[$module])) {
					$importedAllAvailableModules = false;
					break;
				}
			}

			$applications = new \dbObject\ArrayApplication();
			$applications->load(array(
				'orderBy' => array(
					array('field' => 'position', 'dir' => 'ASC'),
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));
			$links = new \dbObject\ArrayOrganizationApplication();
			$links->load(array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => $organizationId),
				),
			));
			$linksByApplicationId = array();
			foreach ($links as $link) {
				$linksByApplicationId[(int)$link->get('IDapplication')] = $link;
			}

			$activeDirectories = array();
			foreach ($applications as $application) {
				$applicationId = (int)$application->getId();
				if ($applicationId <= 0) {
					continue;
				}

				$directory = strtolower(trim((string)$application->get('directory')));
				$hash = strtolower(trim((string)$application->get('hash')));
				$applicationKey = $directory !== '' ? $directory : $hash;
				$shouldBeActive = $importedAllAvailableModules
					|| ($applicationKey !== '' && isset($activeApplicationKeys[$applicationKey]));
				$link = $linksByApplicationId[$applicationId] ?? null;
				if (!($link instanceof \dbObject\OrganizationApplication)) {
					$link = new \dbObject\OrganizationApplication();
					$link->set('IDorganization', $organizationId);
					$link->set('IDapplication', $applicationId);
					$link->set('position', (int)$application->get('position'));
				}
				$link->set('active', $shouldBeActive ? 1 : 0);
				$saveResult = $link->save();
				if (!is_array($saveResult) || empty($saveResult['status'])) {
					return array(
						'status' => false,
						'message' => 'Impossible de configurer les applications de l organisation importee.',
					);
				}

				if ($shouldBeActive && $applicationKey !== '') {
					$activeDirectories[] = $applicationKey;
				}
			}

			return array(
				'status' => true,
				'activeApplications' => array_values(array_unique($activeDirectories)),
				'importedAllAvailableModules' => $importedAllAvailableModules,
			);
		}

		protected static function buildIntPlaceholders(array $ids, $prefix, array &$params)
		{
			$placeholders = array();
			foreach (array_values($ids) as $index => $id) {
				$key = $prefix . '_' . $index;
				$placeholders[] = ':' . $key;
				$params[$key] = (int)$id;
			}

			return $placeholders;
		}

		protected function getOrganizationRootHolonIds()
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return array();
			}

			$rows = self::fetchAll(
				"SELECT id
				FROM holon
				WHERE IDorganization = :organization_id
				ORDER BY id ASC",
				array(
					'organization_id' => $organizationId,
				)
			);

			if ($rows === false) {
				return array();
			}

			$rootIds = array();
			foreach ($rows as $row) {
				$rootId = (int)($row['id'] ?? 0);
				if ($rootId > 0) {
					$rootIds[$rootId] = $rootId;
				}
			}

			return array_values($rootIds);
		}

		protected function getOrganizationHolonIds()
		{
			$rootIds = $this->getOrganizationRootHolonIds();
			if (count($rootIds) === 0) {
				return array();
			}

			$params = array();
			$idPlaceholders = self::buildIntPlaceholders($rootIds, 'root_holon_id', $params);
			$organizationRootPlaceholders = self::buildIntPlaceholders($rootIds, 'root_holon_org', $params);
			$rows = self::fetchAll(
				"SELECT id
				FROM holon
				WHERE id IN (" . implode(', ', $idPlaceholders) . ")
				   OR IDholon_org IN (" . implode(', ', $organizationRootPlaceholders) . ")
				ORDER BY id ASC",
				$params
			);

			if ($rows === false) {
				return $rootIds;
			}

			$holonIds = array();
			foreach ($rows as $row) {
				$holonId = (int)($row['id'] ?? 0);
				if ($holonId > 0) {
					$holonIds[$holonId] = $holonId;
				}
			}

			return array_values($holonIds);
		}

		protected function deactivateUserHolonLinks($userId, array $holonIds)
		{
			$userId = (int)$userId;
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));

			if ($userId <= 0 || count($holonIds) === 0) {
				return true;
			}

			$params = array(
				'user_id' => $userId,
			);
			$placeholders = self::buildIntPlaceholders($holonIds, 'holon', $params);
			$rows = self::fetchAll(
				"SELECT id
				FROM user_holon
				WHERE IDuser = :user_id
				  AND IDholon IN (" . implode(', ', $placeholders) . ")",
				$params
			);

			if ($rows === false) {
				return false;
			}

			foreach ($rows as $row) {
				$linkId = (int)($row['id'] ?? 0);
				if ($linkId <= 0) {
					continue;
				}

				$link = new \dbObject\UserHolon();
				if (!$link->load($linkId)) {
					continue;
				}

				$link->set('active', false);
				$saveResult = $link->setHolonAdmin(false);
				if (!is_array($saveResult) || empty($saveResult['status'])) {
					return false;
				}
			}

			return true;
		}

		protected function removeUserHolonLinks($userId, array $holonIds)
		{
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds))));
			if (count($holonIds) === 0) {
				return true;
			}
			$params = array('user_id' => (int)$userId);
			$placeholders = self::buildIntPlaceholders($holonIds, 'holon', $params);
			$rows = self::fetchAll("SELECT id FROM user_holon WHERE IDuser = :user_id AND IDholon IN (" . implode(', ', $placeholders) . ')', $params);
			if ($rows === false) {
				return false;
			}
			foreach ($rows as $row) {
				$link = new \dbObject\UserHolon();
				if ($link->load((int)($row['id'] ?? 0)) && !$link->delete()) {
					return false;
				}
			}

			return true;
		}

		public function disconnectUserPreservingHistory($userId)
		{
			$organizationId = (int)$this->getId();
			$userId = (int)$userId;
			if ($organizationId <= 0 || $userId <= 0) {
				return array('status' => false, 'message' => 'Membre ou organisation invalide.');
			}
			$user = new \dbObject\User();
			if (!$user->load($userId) || $user->isHistoricalPlaceholder()) {
				return array('status' => false, 'message' => 'Le compte a retirer est introuvable.');
			}
			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array('status' => false, 'message' => 'Connexion base de donnees indisponible.');
			}

			try {
				$ownsTransaction = !$pdo->inTransaction();
				if ($ownsTransaction) {
					$pdo->beginTransaction();
				}
				$ghostUser = \dbObject\User::getOrCreateHistoricalPlaceholder($organizationId, $user);
				$scopeUpdateResult = \dbObject\Document::normalizeSelfScopedDocumentsForAuthorContext($organizationId, $userId);
				if (!is_array($scopeUpdateResult) || empty($scopeUpdateResult['status'])) {
					throw new \RuntimeException('Les portees des documents lies a ce membre n ont pas pu etre mises a jour.');
				}
				$handlers = array('Project', 'ProjectUser', 'StatIndicator', 'StatIndicatorValue', 'Document', 'DocumentPvPoint', 'Event', 'History', 'Tension');
				foreach ($handlers as $handler) {
					$className = '\\dbObject\\' . $handler;
					if (!$className::handleUserDeparture($organizationId, $userId, (int)$ghostUser->getId())) {
						throw new \RuntimeException('Les references de ' . $handler . ' n ont pas pu etre mises a jour.');
					}
				}
				if (!$this->removeUserHolonLinks($userId, $this->getOrganizationHolonIds())) {
					throw new \RuntimeException('Les liens aux holons n ont pas pu etre retires.');
				}
				$membership = $this->getMembership($userId);
				if ($membership && !$membership->delete()) {
					throw new \RuntimeException('L adhesion a l organisation n a pas pu etre retiree.');
				}
				if ($ownsTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}
				return array('status' => true, 'ghostUserId' => (int)$ghostUser->getId());
			} catch (\Throwable $exception) {
				if (isset($ownsTransaction) && $ownsTransaction && $pdo->inTransaction()) {
					$pdo->rollBack();
				}
				return array('status' => false, 'message' => $exception->getMessage());
			}
		}

		protected function deleteOrganizationDocuments(array $holonIds)
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return true;
			}

			$params = array(
				'organization_id' => $organizationId,
			);
			$query = "
				SELECT id
				FROM document
				WHERE IDorganization = :organization_id
			";

			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));
			if (count($holonIds) > 0) {
				$placeholders = self::buildIntPlaceholders($holonIds, 'document_holon', $params);
				$query .= "
				   OR IDholon IN (" . implode(', ', $placeholders) . ")";
			}

			$rows = self::fetchAll($query, $params);
			if ($rows === false) {
				return false;
			}

			$documentIds = array();
			foreach ($rows as $row) {
				$documentId = (int)($row['id'] ?? 0);
				if ($documentId > 0) {
					$documentIds[$documentId] = $documentId;
				}
			}

			if (count($documentIds) === 0) {
				return true;
			}

			$documentParams = array();
			$documentPlaceholders = self::buildIntPlaceholders(array_values($documentIds), 'document', $documentParams);

			if (!self::execute(
				"DELETE FROM alttext
				WHERE IDdocument IN (" . implode(', ', $documentPlaceholders) . ")",
				$documentParams
			)) {
				return false;
			}

			if (!self::execute(
				"DELETE FROM media
				WHERE IDdocument IN (" . implode(', ', $documentPlaceholders) . ")",
				$documentParams
			)) {
				return false;
			}

			return self::execute(
				"DELETE FROM document
				WHERE id IN (" . implode(', ', $documentPlaceholders) . ")",
				$documentParams
			);
		}

		public function removeMember($userId, array $options = array())
		{
			$organizationId = (int)$this->getId();
			$userId = (int)$userId;
			$actorUserId = isset($options['actorUserId']) ? (int)$options['actorUserId'] : $this->resolveCurrentUserId();

			if ($organizationId <= 0 || $userId <= 0) {
				return array(
					'status' => false,
					'message' => 'Membre ou organisation invalide.',
				);
			}

			$membership = $this->getMembership($userId);
			if (!$membership || !(bool)$membership->get('active')) {
				return array(
					'status' => false,
					'message' => "Ce membre n'est pas actif dans cette organisation.",
				);
			}

			$isSelfRemoval = $actorUserId > 0 && $actorUserId === $userId;
			if ($isSelfRemoval && $this->isSystemOrganization() && $membership->isOrganizationAdmin()) {
				return array(
					'status' => false,
					'message' => 'Un admin ne peut pas quitter l organisation de base.',
				);
			}

			$actorIsAdmin = $this->isUserOrganizationAdmin($actorUserId);
			if (!$isSelfRemoval && !$actorIsAdmin) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas le droit de modifier cette organisation.",
				);
			}

			if ($membership->isOrganizationAdmin() && $this->countActiveAdminMemberships($userId) === 0) {
				return array(
					'status' => false,
					'message' => "Le dernier admin ne peut pas quitter l'organisation. Nommez un autre admin ou supprimez l'organisation.",
				);
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'Connexion base de donnees indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				$departureResult = $this->disconnectUserPreservingHistory($userId);
				if (empty($departureResult['status'])) {
					throw new \RuntimeException((string)($departureResult['message'] ?? 'Le retrait du membre n a pas pu etre finalise.'));
				}

				$pdo->commit();

				return array(
					'status' => true,
					'message' => $isSelfRemoval
						? "Vous avez quitte l'organisation."
						: "Le membre a ete retire de l'organisation.",
				);
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'message' => $exception->getMessage(),
				);
			}
		}

		public function addMember($userId = 0, $email = '')
		{
			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'message' => "L'organisation cible est invalide.",
				);
			}

			if (!$this->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas le droit de modifier cette organisation.",
				);
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'Connexion base de donnees indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				$user = $this->resolveMemberUser($userId, $email);
				$invitationIssue = array();
				$pendingInvitation = \dbObject\Invitation::findPendingForOrganizationUser($organizationId, (int)$user->getId());
				$hasActiveMembership = $this->hasActiveMembershipForUser($user, $organizationId);
				$requiresInvitation = !$hasActiveMembership && !($pendingInvitation instanceof \dbObject\Invitation);
				$canApprovePendingRequest = $pendingInvitation instanceof \dbObject\Invitation && $pendingInvitation->isMemberInitiatedRequest();
				$keepsPendingInvitation = $pendingInvitation instanceof \dbObject\Invitation
					&& !$hasActiveMembership
					&& !$canApprovePendingRequest;
				$isPendingAdd = $requiresInvitation || $keepsPendingInvitation;

				if ($canApprovePendingRequest) {
					$approvalResult = $pendingInvitation->approveByAdmin([
						'approvedByUserId' => (int)$this->resolveCurrentUserId(),
						'sendConfirmationEmail' => false,
					]);
					if (!($approvalResult['status'] ?? false)) {
						throw new \RuntimeException((string)($approvalResult['message'] ?? "L'ajout en attente n'a pas pu etre finalise."));
					}
				} elseif ($keepsPendingInvitation) {
					$this->ensureOrganizationMembershipState($user, $organizationId, false);
				} elseif ($requiresInvitation) {
					$this->ensureOrganizationMembershipState($user, $organizationId, false);
					$invitationIssue = \dbObject\Invitation::issue(
						$organizationId,
						(int)$user->getId(),
						(int)$this->resolveCurrentUserId(),
						trim((string)$user->get('email'))
					);

					if (!empty($invitationIssue['created']) && isset($invitationIssue['invitation'])) {
						$invitationIssue['invitation']->sendEmail();
					}
				} else {
					$this->ensureOrganizationMembershipState($user, $organizationId, true);
				}

				$this->recordMemberAddedHistory($user, $organizationId);

				$pdo->commit();

				return array(
					'status' => true,
					'message' => $isPendingAdd
						? (
							!empty($invitationIssue['created'])
								? 'Invitation envoyee : ' . trim((string)$user->get('email'))
								: 'Ajout en attente de confirmation : ' . trim((string)$user->get('email'))
						)
						: (
							trim((string)$user->get('email')) !== ''
								? 'Membre ajoute : ' . trim((string)$user->get('email'))
								: 'Membre ajoute.'
						),
					'userId' => (int)$user->getId(),
					'pending' => $isPendingAdd,
				);
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'message' => $exception->getMessage(),
				);
			}
		}

		public function requestAccess($userId, $message = '')
		{
			$organizationId = (int)$this->getId();
			$userId = (int)$userId;
			$message = trim((string)$message);

			if ($organizationId <= 0 || $userId <= 0) {
				return array(
					'status' => false,
					'message' => "L'organisation demandee est invalide.",
				);
			}

			if ($this->resolveCurrentUserId() !== $userId) {
				return array(
					'status' => false,
					'message' => 'Vous ne pouvez demander cet acces que pour votre propre compte.',
				);
			}

			$user = new \dbObject\User();
			if (!$user->load($userId)) {
				return array(
					'status' => false,
					'message' => 'Votre profil utilisateur est introuvable.',
				);
			}

			if ($this->hasActiveMembershipForUser($user, $organizationId)) {
				return array(
					'status' => false,
					'message' => 'Votre compte a deja acces a cette organisation.',
				);
			}

			$email = trim(mb_strtolower((string)$user->getScopedEmail($organizationId), 'UTF-8'));
			if ($email === '') {
				$email = trim(mb_strtolower((string)$user->get('email'), 'UTF-8'));
			}

			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				return array(
					'status' => false,
					'message' => "Votre compte doit disposer d'une adresse e-mail valide pour envoyer cette demande.",
				);
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'Connexion base de donnees indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				$this->ensureOrganizationMembershipState($user, $organizationId, false);
				$invitationIssue = \dbObject\Invitation::issue(
					$organizationId,
					$userId,
					$userId,
					$email,
					[
						'requestOrigin' => \dbObject\Invitation::REQUEST_ORIGIN_MEMBER,
						'requestMessage' => $message,
					]
				);

				if (!empty($invitationIssue['created']) && isset($invitationIssue['invitation'])) {
					$invitationIssue['invitation']->sendEmail();
				}

				$pdo->commit();

				$existingInvitation = $invitationIssue['invitation'] ?? null;
				if ($existingInvitation instanceof \dbObject\Invitation && !$existingInvitation->isMemberInitiatedRequest()) {
					return array(
						'status' => true,
						'created' => false,
						'message' => 'Une invitation est deja en attente pour cette organisation.',
					);
				}

				return array(
					'status' => true,
					'created' => !empty($invitationIssue['created']),
					'message' => !empty($invitationIssue['created'])
						? 'Votre demande a ete envoyee aux administrateurs.'
						: 'Une demande est deja en attente pour cette organisation.',
				);
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'message' => $exception->getMessage(),
				);
			}
		}

		public function delete()
		{
			$this->lastDeleteError = '';

			if (!$this->canDelete()) {
				$this->lastDeleteError = "Vous n'avez pas le droit de supprimer cette organisation.";
				return false;
			}

			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				$this->lastDeleteError = "L'organisation est invalide.";
				return false;
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				$this->lastDeleteError = "La connexion a la base de donnees n'est pas disponible.";
				return false;
			}

			try {
				$pdo->beginTransaction();

				$rootHolonIds = $this->getOrganizationRootHolonIds();
				$holonIds = $this->getOrganizationHolonIds();

				if (!$this->deleteOrganizationDocuments($holonIds)) {
					throw new \RuntimeException("Les documents de l'organisation n'ont pas pu etre supprimes.");
				}

				if (!self::execute(
					"DELETE FROM holon_share_link
					WHERE IDorganization = :organization_id",
					array('organization_id' => $organizationId)
				)) {
					throw new \RuntimeException("Les liens de partage n'ont pas pu etre supprimes.");
				}

				if (!self::execute(
					"DELETE FROM invitation
					WHERE IDorganization = :organization_id",
					array('organization_id' => $organizationId)
				)) {
					throw new \RuntimeException("Les invitations n'ont pas pu etre supprimees.");
				}

				if (!self::execute(
					"DELETE FROM history
					WHERE IDorganization = :organization_id",
					array('organization_id' => $organizationId)
				)) {
					throw new \RuntimeException("L'historique n'a pas pu etre supprime.");
				}

				if (!self::execute(
					"DELETE FROM organization_application
					WHERE IDorganization = :organization_id",
					array('organization_id' => $organizationId)
				)) {
					throw new \RuntimeException("Les applications d'organisation n'ont pas pu etre supprimees.");
				}

				if (!self::execute(
					"DELETE FROM organization_parcours
					WHERE IDorganization = :organization_id",
					array('organization_id' => $organizationId)
				)) {
					throw new \RuntimeException("Les parcours d'organisation n'ont pas pu etre supprimes.");
				}

				if (count($holonIds) > 0) {
					$holonParams = array();
					$holonPlaceholders = self::buildIntPlaceholders($holonIds, 'delete_holon', $holonParams);

					if (!self::execute(
						"DELETE FROM user_holon
						WHERE IDholon IN (" . implode(', ', $holonPlaceholders) . ")",
						$holonParams
					)) {
						throw new \RuntimeException("Les liens membres des holons n'ont pas pu etre supprimes.");
					}

					// Les regles rattachees a une autorite bloquent sa suppression (FK restrictive).
					// Nettoyer ces regles et les autorites avant la suppression recursive des holons.
					if (!self::execute(
						"DELETE FROM rule
						WHERE IDauthority IN (
							SELECT id FROM authority
							WHERE IDholon IN (" . implode(', ', $holonPlaceholders) . ")
						)",
						$holonParams
					)) {
						throw new \RuntimeException("Les regles d'autorite n'ont pas pu etre supprimees.");
					}

					if (!self::execute(
						"UPDATE authority
						SET IDauthority_parent = NULL
						WHERE IDholon IN (" . implode(', ', $holonPlaceholders) . ")",
						$holonParams
					)) {
						throw new \RuntimeException("Les liens entre domaines d'autorite n'ont pas pu etre supprimes.");
					}

					if (!self::execute(
						"DELETE FROM authority
						WHERE IDholon IN (" . implode(', ', $holonPlaceholders) . ")",
						$holonParams
					)) {
						throw new \RuntimeException("Les domaines d'autorite n'ont pas pu etre supprimes.");
					}
				}

				if (count($rootHolonIds) > 0) {
					$rootParams = array();
					$rootPlaceholders = self::buildIntPlaceholders($rootHolonIds, 'root_delete', $rootParams);

					if (!self::execute(
						"DELETE FROM property
						WHERE IDholon_organization IN (" . implode(', ', $rootPlaceholders) . ")",
						$rootParams
					)) {
						throw new \RuntimeException("Les definitions de proprietes n'ont pas pu etre supprimees.");
					}

					foreach ($rootHolonIds as $rootHolonId) {
						$rootHolon = new \dbObject\Holon();
						if ($rootHolon->load((int)$rootHolonId) && !$rootHolon->delete()) {
							throw new \RuntimeException("La structure de l'organisation n'a pas pu etre supprimee.");
						}
					}
				}

				if (!self::execute(
					"DELETE FROM user_organization
					WHERE IDorganization = :organization_id",
					array('organization_id' => $organizationId)
				)) {
					throw new \RuntimeException("Les membres de l'organisation n'ont pas pu etre supprimes.");
				}

				if (!parent::delete()) {
					throw new \RuntimeException("L'organisation n'a pas pu etre supprimee.");
				}

				$pdo->commit();
				return true;
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				$this->lastDeleteError = trim((string)$exception->getMessage());
				if ($this->lastDeleteError === '') {
					$this->lastDeleteError = "L'organisation n'a pas pu etre supprimee.";
				}
				\dbObject\DbObject::registerDbError(
					'organization_delete',
					array('organization_id' => $organizationId),
					$exception
				);

				return false;
			}
		}

		public function getLastDeleteError()
		{
			return (string)$this->lastDeleteError;
		}

		public static function resolveFromHost($host, $defaultId = 1) {
			$host = is_string($host) ? trim($host) : "";
			if ($host === "") {
				return false;
			}

			$host = preg_replace('/:\d+$/', '', $host);
			$parts = array_values(array_filter(explode(".", $host)));
			$rootPartCount = 0;
			if (function_exists('commonGetHostRootPartCount')) {
				$rootPartCount = (int)commonGetHostRootPartCount($parts);
			} elseif (count($parts) === 2 && ($parts[1] ?? '') === 'localhost') {
				$rootPartCount = 1;
			} elseif (count($parts) >= 3 && in_array((string)($parts[count($parts) - 3] ?? ''), commonGetConfiguredEnvironmentSubdomains(), true)) {
				$rootPartCount = 3;
			} else {
				$rootPartCount = min(2, count($parts));
			}

			$organization = new self();
			if (count($parts) <= $rootPartCount) {
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

		protected function getStructuralInitializationTemplateName(\dbObject\Holon $holon)
		{
			$templateName = trim((string)$holon->get('templatename'));
			if ($templateName !== '') {
				return $templateName;
			}

			return $holon->getDisplayName();
		}

		protected function getHolonMediaFieldData(\dbObject\Holon $holon, $field, $lockField)
		{
			$field = (string)$field;
			$lockField = (string)$lockField;
			$template = $holon->getTemplateHolon();
			$localValue = trim((string)$holon->get($field));
			$inheritedValue = '';
			$effectiveValue = '';
			$localLocked = (bool)$holon->get($lockField);
			$inheritedLocked = false;

			if ($template) {
				$inheritedLocked = $template->getEffectiveTemplateBooleanField($lockField);
				if ($field === 'icon') {
					$inheritedValue = $template->getEffectiveIcon();
				} elseif ($field === 'banner') {
					$inheritedValue = $template->getEffectiveBanner();
				}
			}

			if ($field === 'icon') {
				$effectiveValue = $holon->getEffectiveIcon();
			} elseif ($field === 'banner') {
				$effectiveValue = $holon->getEffectiveBanner();
			}

			return array(
				'value' => $localValue,
				'inheritedValue' => $inheritedValue,
				'effectiveValue' => $effectiveValue,
				'locked' => $localLocked,
				'inheritedLocked' => $inheritedLocked,
				'effectiveLocked' => $localLocked || $inheritedLocked,
			);
		}

		protected function getHolonIllustrationData(\dbObject\Holon $holon)
		{
			$icon = $this->getHolonMediaFieldData($holon, 'icon', 'lockedicon');
			$banner = $this->getHolonMediaFieldData($holon, 'banner', 'lockedbanner');

			return array(
				'icon' => $icon['value'],
				'inheritedIcon' => $icon['inheritedValue'],
				'effectiveIcon' => $icon['effectiveValue'],
				'lockedIcon' => $icon['locked'],
				'inheritedLockedIcon' => $icon['inheritedLocked'],
				'effectiveLockedIcon' => $icon['effectiveLocked'],
				'banner' => $banner['value'],
				'inheritedBanner' => $banner['inheritedValue'],
				'effectiveBanner' => $banner['effectiveValue'],
				'lockedBanner' => $banner['locked'],
				'inheritedLockedBanner' => $banner['inheritedLocked'],
				'effectiveLockedBanner' => $banner['effectiveLocked'],
			);
		}

		public function getStructuralInitializationTemplates()
		{
			$templates = array();
			$holons = new \dbObject\ArrayHolon();
			$holons->load(array(
				'filter' => 'active = 1'
					. ' and IDtypeholon = 4'
					. ' and templatename is not null'
					. ' and templatename != ""'
					. ' and (IDholon_parent is null or IDholon_parent = 0)',
				'orderBy' => array(
					array('field' => 'templatename', 'dir' => 'ASC'),
					array('field' => 'name', 'dir' => 'ASC'),
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			foreach ($holons as $holon) {
				$sourceOrganizationName = '';
				$sourceOrganizationId = (int)$holon->get('IDorganization');
				if ($sourceOrganizationId > 0) {
					$sourceOrganization = new self();
					if ($sourceOrganization->load($sourceOrganizationId)) {
						$sourceOrganizationName = trim((string)$sourceOrganization->get('name'));
					}
				}

				$templates[] = array(
					'id' => (int)$holon->getId(),
					'name' => $this->getStructuralInitializationTemplateName($holon),
					'sourceOrganizationId' => $sourceOrganizationId,
					'sourceOrganizationName' => $sourceOrganizationName,
					'color' => trim((string)$holon->getEffectiveColor()),
					'icon' => $holon->getEffectiveIcon(),
					'banner' => $holon->getEffectiveBanner(),
				);
			}

			return $templates;
		}

		public function getStructuralInitializationData()
		{
			return array(
				'organizationId' => (int)$this->getId(),
				'organizationName' => trim((string)$this->get('name')),
				'hasStructure' => $this->getStructuralRootHolon() !== null,
				'templates' => $this->getStructuralInitializationTemplates(),
			);
		}

		public function getSharedTemplateRootHolon()
		{
			static $cache = array();

			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return null;
			}

			if (array_key_exists($organizationId, $cache)) {
				return $cache[$organizationId] ?: null;
			}

			$row = self::fetchRow(
				"SELECT id
				FROM holon
				WHERE IDorganization = :organization_id
				  AND IDtypeholon = 4
				  AND active = 1
				  AND templatename IS NOT NULL
				  AND templatename != ''
				  AND (IDholon_parent IS NULL OR IDholon_parent = 0)
				ORDER BY id ASC
				LIMIT 1",
				array(
					'organization_id' => $organizationId,
				)
			);

			$holonId = $row !== false ? (int)($row['id'] ?? 0) : 0;
			if ($holonId <= 0) {
				$cache[$organizationId] = false;
				return null;
			}

			$holon = new \dbObject\Holon();
			$cache[$organizationId] = $holon->load($holonId) ? $holon : false;

			return $cache[$organizationId] ?: null;
		}

		public function isSharedAsTemplate()
		{
			return $this->getSharedTemplateRootHolon() !== null;
		}

		public function getSharedTemplateName()
		{
			$templateHolon = $this->getSharedTemplateRootHolon();

			return $templateHolon ? $this->getStructuralInitializationTemplateName($templateHolon) : '';
		}

		protected function createStructuralRootHolon($userId = 0, ?\dbObject\Holon $sourceTemplate = null)
		{
			$organizationName = trim((string)$this->get('name'));
			if ($organizationName === '') {
				$organizationName = 'Organisation ' . (int)$this->getId();
			}

			$rootHolon = new \dbObject\Holon();
			$rootHolon->set('name', $organizationName);
			$rootHolon->set('templatename', null);
			$rootHolon->set('IDtypeholon', 4);
			$rootHolon->set('IDholon_parent', null);
			$rootHolon->set('IDholon_template', null);
			$rootHolon->set('IDholon_org', null);
			$rootHolon->set('IDorganization', (int)$this->getId());
			$rootHolon->set('IDuser', (int)$userId > 0
				? (int)$userId
				: ($sourceTemplate ? (int)$sourceTemplate->get('IDuser') : 0));
			$rootHolon->set('active', true);
			$rootHolon->set('visible', true);
			$rootHolon->set('mandatory', false);
			$rootHolon->set('lockedname', false);
			$rootHolon->set('lockedicon', false);
			$rootHolon->set('lockedbanner', false);
			$rootHolon->set('unique', false);
			$rootHolon->set('link', false);
			$rootHolon->set('color', $sourceTemplate ? ($sourceTemplate->getEffectiveColor() ?: null) : null);
			$rootHolon->set('icon', $sourceTemplate ? ($sourceTemplate->getEffectiveIcon() ?: null) : null);
			$rootHolon->set('banner', $sourceTemplate ? ($sourceTemplate->getEffectiveBanner() ?: null) : null);
			$rootHolon->set('accesskey', null);
			$rootHolon->save();

			if ((int)$rootHolon->getId() <= 0) {
				return null;
			}

			$rootHolon->set('IDholon_org', (int)$rootHolon->getId());
			$rootHolon->save();

			if ((int)$userId > 0 && !$this->ensureActiveMembershipForUser((int)$userId)) {
				return null;
			}

			return $rootHolon;
		}

		protected function ensureActiveMembershipForUser($userId)
		{
			$userId = (int)$userId;
			$organizationId = (int)$this->getId();
			if ($userId <= 0 || $organizationId <= 0) {
				return false;
			}

			$membership = new \dbObject\UserOrganization();
			if (!$membership->load(array(
				array('IDuser', $userId),
				array('IDorganization', $organizationId),
			))) {
				$membership->set('IDuser', $userId);
				$membership->set('IDorganization', $organizationId);
			}

			$user = new \dbObject\User();
			if ($user->load($userId)) {
				if (trim((string)$membership->get('email')) === '' && trim((string)$user->get('email')) !== '') {
					$membership->set('email', trim((string)$user->get('email')));
				}

				if (trim((string)$membership->get('username')) === '' && trim((string)$user->get('username')) !== '') {
					$membership->set('username', trim((string)$user->get('username')));
				}
			}

			$membership->set('active', true);
			$saveResult = $membership->save();

			return is_array($saveResult) && !empty($saveResult['status']);
		}

		protected function ensureOrganizationMembershipState(\dbObject\User $user, $organizationId, $isActive = true)
		{
			$organizationId = (int)$organizationId;
			if ((int)$user->getId() <= 0 || $organizationId <= 0) {
				throw new \RuntimeException("L'organisation cible est invalide.");
			}

			$membership = new \dbObject\UserOrganization();
			if (!$membership->load(array(
				array('IDuser', (int)$user->getId()),
				array('IDorganization', $organizationId),
			))) {
				$membership->set('IDuser', (int)$user->getId());
				$membership->set('IDorganization', $organizationId);
			}

			if (trim((string)$membership->get('email')) === '' && trim((string)$user->get('email')) !== '') {
				$membership->set('email', trim((string)$user->get('email')));
			}

			if (trim((string)$membership->get('username')) === '' && trim((string)$user->get('username')) !== '') {
				$membership->set('username', trim((string)$user->get('username')));
			}

			$membership->set('active', (bool)$isActive);
			$saveResult = $membership->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("Impossible d'attacher cette personne a l'organisation.");
			}

			if (!$isActive) {
				$scopeUpdateResult = \dbObject\Document::normalizeSelfScopedDocumentsForAuthorContext($organizationId, (int)$user->getId());
				if (!is_array($scopeUpdateResult) || empty($scopeUpdateResult['status'])) {
					throw new \RuntimeException("Impossible de mettre a jour les documents de cette personne.");
				}
			}

			return $membership;
		}

		protected function hasActiveMembershipForUser(\dbObject\User $user, $organizationId)
		{
			$membership = new \dbObject\UserOrganization();
			return $membership->load(array(
				array('IDuser', (int)$user->getId()),
				array('IDorganization', (int)$organizationId),
			)) && (bool)$membership->get('active');
		}

		protected function recordMemberAddedHistory(\dbObject\User $memberUser, $organizationId)
		{
			$organizationId = (int)$organizationId;
			$authorUserId = (int)$this->resolveCurrentUserId();
			$authorLabel = 'Utilisateur';

			if ($authorUserId > 0) {
				$author = new \dbObject\User();
				if ($author->load($authorUserId)) {
					$authorLabel = trim((string)$author->getScopedDisplayName($organizationId));
				}
			}

			if ($authorLabel === '') {
				$authorLabel = 'Utilisateur ' . $authorUserId;
			}

			$memberLabel = trim((string)$memberUser->getScopedDisplayName($organizationId));
			if ($memberLabel === '') {
				$memberLabel = trim((string)$memberUser->get('email'));
			}
			if ($memberLabel === '') {
				$memberLabel = 'Utilisateur ' . (int)$memberUser->getId();
			}

			$organizationLabel = trim((string)$this->get('name'));
			if ($organizationLabel === '') {
				$organizationLabel = 'organisation';
			}

			$content = \dbObject\History::buildReferenceToken('user', (int)$memberUser->getId(), $memberLabel)
				. ' a ete ajoute a '
				. \dbObject\History::buildReferenceToken('organization', $organizationId, $organizationLabel)
				. ' par '
				. \dbObject\History::buildReferenceToken('user', $authorUserId, $authorLabel)
				. '.';

			$saveResult = \dbObject\History::createEntry(
				$organizationId,
				$authorUserId,
				'holon_member_added',
				$content,
				array(
					'IDtargetuser' => (int)$memberUser->getId(),
					'IDorganization' => $organizationId,
					'authorUserId' => $authorUserId,
				),
				0
			);

			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("L'historique de l'ajout n'a pas pu etre enregistre.");
			}
		}

		protected function resolveMemberUser($userId = 0, $email = '')
		{
			$userId = (int)$userId;
			$email = trim(mb_strtolower((string)$email, 'UTF-8'));

			if ($userId > 0) {
				$user = new \dbObject\User();
				if (!$user->load($userId)) {
					throw new \RuntimeException('La personne selectionnee est introuvable.');
				}

				return $user;
			}

			if ($email === '') {
				throw new \RuntimeException('Selectionnez une personne ou saisissez une adresse e-mail.');
			}

			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \RuntimeException("L'adresse e-mail saisie n'est pas valide.");
			}

			$user = new \dbObject\User();
			if ($user->load(array('email', $email))) {
				return $user;
			}

			$user->set('email', $email);
			$user->set('active', false);
			$saveResult = $user->save();
			if (!is_array($saveResult) || empty($saveResult['status']) || (int)$user->getId() <= 0) {
				throw new \RuntimeException("Le profil n'a pas pu etre cree.");
			}

			return $user;
		}

		protected function getStructuralInitializationChildren(\dbObject\Holon $holon)
		{
			$children = new \dbObject\ArrayHolon();
			$children->load(array(
				'where' => array(
					array('field' => 'IDholon_parent', 'value' => (int)$holon->getId()),
					array('field' => 'active', 'value' => 1),
				),
				'orderBy' => array(
					array('field' => 'IDtypeholon', 'dir' => 'ASC'),
					array('field' => 'name', 'dir' => 'ASC'),
					array('field' => 'templatename', 'dir' => 'ASC'),
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			return $children;
		}

		protected function remapHolonReferenceValueByListType($listItemType, $rawValue, array $holonIdMap)
		{
			if ((string)$listItemType !== \dbObject\Property::LIST_ITEM_HOLON) {
				return $rawValue;
			}

			$rawValue = is_scalar($rawValue) || $rawValue === null ? (string)$rawValue : '';
			$trimmedValue = trim($rawValue);
			if ($trimmedValue === '') {
				return $rawValue;
			}

			$decoded = json_decode($trimmedValue, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				$converted = array();
				foreach ($decoded as $item) {
					if (is_array($item)) {
						$itemId = (int)($item['id'] ?? 0);
						if ($itemId > 0 && isset($holonIdMap[$itemId])) {
							$item['id'] = (int)$holonIdMap[$itemId];
						}
						$converted[] = $item;
						continue;
					}

					$itemId = (int)$item;
					$converted[] = isset($holonIdMap[$itemId]) ? (int)$holonIdMap[$itemId] : $item;
				}

				return json_encode($converted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}

			$singleHolonId = (int)$trimmedValue;
			if ($singleHolonId > 0 && isset($holonIdMap[$singleHolonId])) {
				return (string)$holonIdMap[$singleHolonId];
			}

			if (strpos($trimmedValue, '|') !== false || preg_match('/\r\n|\r|\n/', $trimmedValue)) {
				$separator = strpos($trimmedValue, '|') !== false ? '|' : "\n";
				$items = strpos($trimmedValue, '|') !== false
					? explode('|', $trimmedValue)
					: preg_split('/\r\n|\r|\n/', $trimmedValue);
				$converted = array();

				foreach ($items as $item) {
					$item = trim((string)$item);
					if ($item === '') {
						continue;
					}

					$itemId = (int)$item;
					$converted[] = isset($holonIdMap[$itemId]) ? (string)$holonIdMap[$itemId] : $item;
				}

				return implode($separator, $converted);
			}

			return $rawValue;
		}

		protected function remapProjectReferenceItems($items, array $projectIdMap)
		{
			if (!is_array($items)) {
				return $items;
			}

			$remapped = array();
			foreach ($items as $item) {
				if (is_array($item) && array_key_exists('id', $item)) {
					$sourceProjectId = (int)$item['id'];
					if ($sourceProjectId > 0 && !isset($projectIdMap[$sourceProjectId])) {
						continue;
					}
					if ($sourceProjectId > 0) {
						$item['id'] = (int)$projectIdMap[$sourceProjectId];
					}
					$remapped[] = $item;
					continue;
				}

				if (is_scalar($item)) {
					$sourceProjectId = (int)$item;
					if ($sourceProjectId > 0) {
						if (isset($projectIdMap[$sourceProjectId])) {
							$remapped[] = (int)$projectIdMap[$sourceProjectId];
						}
						continue;
					}
				}

				$remapped[] = $item;
			}

			return array_values($remapped);
		}

		protected function remapProjectReferenceValue($rawValue, array $projectIdMap)
		{
			$rawValue = is_scalar($rawValue) || $rawValue === null ? (string)$rawValue : '';
			$trimmedValue = trim($rawValue);
			if ($trimmedValue === '') {
				return $rawValue;
			}

			$decoded = json_decode($trimmedValue, true);
			if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
				$sourceProjectId = (int)$trimmedValue;
				return $sourceProjectId > 0 && isset($projectIdMap[$sourceProjectId])
					? (string)$projectIdMap[$sourceProjectId]
					: $rawValue;
			}

			if (array_key_exists('items', $decoded) && is_array($decoded['items'])) {
				$decoded['items'] = $this->remapProjectReferenceItems($decoded['items'], $projectIdMap);
			} elseif (array_is_list($decoded)) {
				$decoded = $this->remapProjectReferenceItems($decoded, $projectIdMap);
			} elseif (array_key_exists('id', $decoded)) {
				$remapped = $this->remapProjectReferenceItems(array($decoded), $projectIdMap);
				$decoded = $remapped[0] ?? array();
			}

			return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		protected function remapImportedProjectPropertyValues(array $projectIdMap, array $taskIdMap)
		{
			$projectIdMap = $projectIdMap + $taskIdMap;

			$rootHolon = $this->getStructuralRootHolon();
			if (!($rootHolon instanceof \dbObject\Holon)) {
				return;
			}

			$holons = new \dbObject\ArrayHolon();
			$holons->load(array(
				'whereAny' => array(
					array('field' => 'IDorganization', 'value' => (int)$this->getId()),
					array('field' => 'IDholon_org', 'value' => (int)$rootHolon->getId()),
				),
				'orderBy' => array(
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			foreach ($holons as $holon) {
				if (!($holon instanceof \dbObject\Holon)) {
					continue;
				}

				foreach ($holon->getHolonProperties() as $holonProperty) {
					$property = new \dbObject\Property();
					if (!$property->load((int)$holonProperty->get('IDproperty'))
						|| (string)$property->get('listitemtype') !== \dbObject\Property::LIST_ITEM_PROJECT) {
						continue;
					}

					$currentValue = $holonProperty->get('value');
					$remappedValue = $this->remapProjectReferenceValue($currentValue, $projectIdMap);
					if ((string)$remappedValue === (string)$currentValue) {
						continue;
					}

					$holonProperty->set('value', $remappedValue);
					self::omo1ImportSave($holonProperty, 'Une liste de projets n a pas pu etre restauree');
				}
			}
		}

		protected function remapAuthorityReferenceValueByListType($listItemType, $rawValue, array $authorityIdMap)
		{
			if ((string)$listItemType !== \dbObject\Property::LIST_ITEM_AUTHORITY) {
				return $rawValue;
			}

			$rawValue = is_scalar($rawValue) || $rawValue === null ? (string)$rawValue : '';
			$decoded = json_decode(trim($rawValue), true);
			if (!is_array($decoded) || count($authorityIdMap) === 0) {
				return $rawValue;
			}

			$items = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : $decoded;
			if (!is_array($items)) {
				return $rawValue;
			}

			foreach ($items as $index => $item) {
				$sourceAuthorityId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
				if ($sourceAuthorityId <= 0 || !isset($authorityIdMap[$sourceAuthorityId])) {
					continue;
				}
				if (is_array($item)) {
					$items[$index]['id'] = (int)$authorityIdMap[$sourceAuthorityId];
				} else {
					$items[$index] = (int)$authorityIdMap[$sourceAuthorityId];
				}
			}

			if (isset($decoded['items']) && is_array($decoded['items'])) {
				$decoded['items'] = array_values($items);
			} else {
				$decoded = array_values($items);
			}

			return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		protected function cloneStructuralPropertyValue(\dbObject\Property $property, $rawValue, array $holonIdMap, array $authorityIdMap = array())
		{
			$value = $this->remapHolonReferenceValueByListType($property->get('listitemtype'), $rawValue, $holonIdMap);
			$value = $this->remapAuthorityReferenceValueByListType($property->get('listitemtype'), $value, $authorityIdMap);
			if (\dbObject\PropertyFormat::isHtmlFormat((int)$property->get('IDpropertyformat'))) {
				return \dbObject\PropertyFormat::normalizeValueForStorage((int)$property->get('IDpropertyformat'), $value);
			}

			return $value;
		}

		protected function cloneStructuralProperty(\dbObject\Property $sourceProperty, $sourceRootHolonId, $targetRootHolonId, array &$propertyIdMap)
		{
			$sourcePropertyId = (int)$sourceProperty->getId();
			if ($sourcePropertyId <= 0) {
				return 0;
			}

			if (isset($propertyIdMap[$sourcePropertyId])) {
				return (int)$propertyIdMap[$sourcePropertyId];
			}

			if ((int)$sourceProperty->get('IDholon_organization') !== (int)$sourceRootHolonId) {
				$propertyIdMap[$sourcePropertyId] = $sourcePropertyId;
				return $sourcePropertyId;
			}

			$targetProperty = new \dbObject\Property();
			$targetProperty->set('name', $sourceProperty->get('name'));
			$targetProperty->set('shortname', $sourceProperty->get('shortname'));
			$targetProperty->set('IDpropertyformat', (int)$sourceProperty->get('IDpropertyformat'));
			$targetProperty->set('listitemtype', $sourceProperty->get('listitemtype'));
			$targetProperty->set('listholontypeids', $sourceProperty->get('listholontypeids'));
			$targetProperty->set('IDholon_organization', (int)$targetRootHolonId);
			$targetProperty->set('position', (int)$sourceProperty->get('position'));
			$targetProperty->set('active', (bool)$sourceProperty->get('active'));
			$targetProperty->save();

			$propertyIdMap[$sourcePropertyId] = (int)$targetProperty->getId();
			return (int)$targetProperty->getId();
		}

		protected function cloneStructuralHolonProperties(\dbObject\Holon $sourceHolon, \dbObject\Holon $targetHolon, $sourceRootHolonId, $targetRootHolonId, array $holonIdMap, array &$propertyIdMap, array $authorityIdMap = array())
		{
			foreach ($sourceHolon->getHolonProperties() as $sourceHolonProperty) {
				$sourceProperty = new \dbObject\Property();
				if (!$sourceProperty->load((int)$sourceHolonProperty->get('IDproperty'))) {
					continue;
				}

				$targetPropertyId = $this->cloneStructuralProperty(
					$sourceProperty,
					(int)$sourceRootHolonId,
					(int)$targetRootHolonId,
					$propertyIdMap
				);
				if ($targetPropertyId <= 0) {
					continue;
				}

				$targetHolonProperty = new \dbObject\HolonProperty();
				$targetHolonProperty->set('IDholon', (int)$targetHolon->getId());
				$targetHolonProperty->set('IDproperty', $targetPropertyId);
				$targetHolonProperty->set('value', $this->cloneStructuralPropertyValue($sourceProperty, $sourceHolonProperty->get('value'), $holonIdMap, $authorityIdMap));
				$targetHolonProperty->set('position', (int)$sourceHolonProperty->get('position'));
				$targetHolonProperty->set('mandatory', (bool)$sourceHolonProperty->get('mandatory'));
				$targetHolonProperty->set('locked', (bool)$sourceHolonProperty->get('locked'));
				$targetHolonProperty->set('active', (bool)$sourceHolonProperty->get('active'));
				$targetHolonProperty->save();
			}
		}

		protected function cloneStructuralAuthorities(array $sourceHolonsById, array $targetHolonsBySourceId)
		{
			$authorityIdMap = array();
			$copiedAuthorities = array();

			foreach ($sourceHolonsById as $sourceHolonId => $sourceHolon) {
				$targetHolon = $targetHolonsBySourceId[(int)$sourceHolonId] ?? null;
				if (!($sourceHolon instanceof \dbObject\Holon) || !($targetHolon instanceof \dbObject\Holon)) {
					continue;
				}

				$sourceAuthorities = new \dbObject\ArrayAuthority();
				$sourceAuthorities->loadForHolon((int)$sourceHolon->getId());
				foreach ($sourceAuthorities as $sourceAuthority) {
					$sourceAuthorityId = (int)$sourceAuthority->getId();
					if ($sourceAuthorityId <= 0) {
						continue;
					}

					$targetAuthority = new \dbObject\Authority();
					$targetAuthority->set('IDholon', (int)$targetHolon->getId());
					$targetAuthority->set('IDauthority_parent', null);
					$targetAuthority->set('IDauthority_template', null);
					$targetAuthority->set('label', $sourceAuthority->get('label'));
					$targetAuthority->set('description', $sourceAuthority->get('description'));
					$targetAuthority->set('is_shell', (bool)$sourceAuthority->get('is_shell'));
					$targetAuthority->set(
						'is_local',
						(bool)$sourceAuthority->get('is_local')
							|| ($sourceHolon->isTemplateNode() && (int)$sourceAuthority->get('IDauthority_parent') <= 0)
					);
					$targetAuthority->set('template_origin_lost', false);
					$saveResult = $targetAuthority->save();
					if (!is_array($saveResult) || empty($saveResult['status']) || (int)$targetAuthority->getId() <= 0) {
						throw new \RuntimeException('Une autorite du modele n a pas pu etre copiee.');
					}

					$authorityIdMap[$sourceAuthorityId] = (int)$targetAuthority->getId();
					$copiedAuthorities[$sourceAuthorityId] = array(
						'source' => $sourceAuthority,
						'target' => $targetAuthority,
					);
				}
			}

			foreach ($copiedAuthorities as $sourceAuthorityId => $copiedAuthority) {
				$targetAuthority = $copiedAuthority['target'];
				$sourceParentId = (int)$copiedAuthority['source']->get('IDauthority_parent');
				$sourceTemplateAuthorityId = (int)$copiedAuthority['source']->get('IDauthority_template');
				$targetParentId = isset($authorityIdMap[$sourceParentId]) ? (int)$authorityIdMap[$sourceParentId] : 0;
				$targetTemplateAuthorityId = isset($authorityIdMap[$sourceTemplateAuthorityId])
					? (int)$authorityIdMap[$sourceTemplateAuthorityId]
					: 0;
				if ($targetParentId <= 0 && $targetTemplateAuthorityId <= 0) {
					continue;
				}
				$targetAuthority->set('IDauthority_parent', $targetParentId > 0 ? $targetParentId : null);
				$targetAuthority->set('IDauthority_template', $targetTemplateAuthorityId > 0 ? $targetTemplateAuthorityId : null);
				$saveResult = $targetAuthority->save();
				if (!is_array($saveResult) || empty($saveResult['status'])) {
					throw new \RuntimeException('La hierarchie des autorites du modele n a pas pu etre copiee.');
				}
			}

			return $authorityIdMap;
		}

		protected function cloneStructuralHolonPermissions(\dbObject\Holon $sourceHolon, \dbObject\Holon $targetHolon)
		{
			$sourceHolonId = (int)$sourceHolon->getId();
			$targetHolonId = (int)$targetHolon->getId();
			if ($sourceHolonId <= 0 || $targetHolonId <= 0) {
				return false;
			}

			$assignments = \dbObject\HolonPermission::getAssignmentKeyMapForHolon($sourceHolonId);
			return \dbObject\HolonPermission::syncAssignmentsForHolon($targetHolonId, $assignments);
		}

		protected function getImportedHolonRecords(array $payload)
		{
			$holons = $payload['holons'] ?? array();
			if (!is_array($holons)) {
				return array();
			}

			$flattened = array();
			$flattenNodes = function (array $nodes, $parentId = 0) use (&$flattenNodes, &$flattened) {
				foreach ($nodes as $node) {
					if (!is_array($node)) {
						continue;
					}

					$record = $node;
					$children = $record['children'] ?? array();
					unset($record['children']);

					if ($parentId > 0 && !array_key_exists('parentId', $record)) {
						$record['parentId'] = (int)$parentId;
					}

					$flattened[] = $record;

					$currentId = (int)($record['id'] ?? 0);
					if ($currentId > 0 && is_array($children) && count($children) > 0) {
						$flattenNodes($children, $currentId);
					}
				}
			};

			$flattenNodes(array_values(array_filter($holons, 'is_array')));

			return $flattened;
		}

		protected function getImportedCompactPropertyDefinitions(array $payload)
		{
			$definitions = $payload['propertyDefinitions'] ?? array();
			return is_array($definitions) ? array_values(array_filter($definitions, 'is_array')) : array();
		}

		protected function createImportedPropertiesFromCompactDefinitions(array $definitions, $targetRootHolonId, array &$propertyIdMap)
		{
			foreach ($definitions as $definition) {
				$sourcePropertyId = (int)($definition['id'] ?? 0);
				if ($sourcePropertyId <= 0) {
					continue;
				}

				$formatId = (int)($definition['formatId'] ?? 0);
				if (strtolower(trim((string)($definition['shortname'] ?? ''))) === 'strategie' && $formatId <= 0) {
					$formatId = ($definition['listItemType'] ?? '') === \dbObject\Property::LIST_ITEM_PROJECT
						? \dbObject\PropertyFormat::FORMAT_HTML_LIST
						: \dbObject\PropertyFormat::FORMAT_HTML;
				}
				$property = new \dbObject\Property();
				$property->set('name', trim((string)($definition['name'] ?? '')) !== '' ? $definition['name'] : 'Propriete');
				$property->set('shortname', trim((string)($definition['shortname'] ?? '')) !== '' ? $definition['shortname'] : \dbObject\Property::buildShortnameFromName((string)($definition['name'] ?? 'Propriete')));
				$property->set('IDpropertyformat', $formatId);
				$property->set('listitemtype', \dbObject\Property::normalizeListItemType($definition['listItemType'] ?? null));
				$property->set('listholontypeids', \dbObject\Property::serializeHolonTypeIds($definition['listHolonTypeIds'] ?? array()));
				$property->set('IDholon_organization', (int)$targetRootHolonId);
				$property->set('position', (int)($definition['position'] ?? 0));
				$property->set('active', !array_key_exists('active', $definition) || (bool)$definition['active']);
				$property->save();

				$propertyIdMap[$sourcePropertyId] = (int)$property->getId();
			}
		}

		protected function applyImportedCompactRecordToHolon(\dbObject\Holon $targetHolon, array $record, $userId = 0, $preserveName = false, $isOrganizationRoot = false)
		{
			$name = trim((string)($record['name'] ?? ''));
			$fullName = trim((string)($record['fullName'] ?? ''));
			if ($name === '') {
				$name = 'Holon';
			}

			if (!$preserveName) {
				$targetHolon->set('name', $name);
			}

			$targetHolon->set('nomcomplet', $fullName !== '' ? $fullName : null);
			$templateName = trim((string)($record['templateName'] ?? ''));
			$targetHolon->set('templatename', $templateName !== '' ? $templateName : null);
			$targetHolon->set('IDtypeholon', $isOrganizationRoot ? 4 : max(1, (int)($record['typeId'] ?? 1)));
			$targetHolon->set('IDuser', (int)$userId > 0 ? (int)$userId : (int)$targetHolon->get('IDuser'));
			$targetHolon->set('active', true);
			$targetHolon->set('visible', !array_key_exists('visible', $record) || (bool)$record['visible']);
			$targetHolon->set('mandatory', !empty($record['mandatory']));
			$targetHolon->set('lockedname', !empty($record['lockedName']));
			$targetHolon->set('lockedicon', !empty($record['lockedIcon']));
			$targetHolon->set('lockedbanner', !empty($record['lockedBanner']));
			$targetHolon->set('unique', !empty($record['unique']));
			$targetHolon->set('link', !empty($record['link']));
			$targetHolon->set('adminparent', !empty($record['adminParent']) && (int)$targetHolon->get('IDtypeholon') === 1);
			$targetHolon->set('admin_min', max(0, (int)($record['adminMin'] ?? 0)));
			$targetHolon->set('admin_max', array_key_exists('adminMax', $record) && trim((string)$record['adminMax']) !== ''
				? max(0, (int)$record['adminMax'])
				: null);
			$targetHolon->set('lockedadminmin', !empty($record['lockedAdminMin']));
			$targetHolon->set('lockedadminmax', !empty($record['lockedAdminMax']));
			$targetHolon->set('adminminoverride', !empty($record['adminMinOverride']));
			$targetHolon->set('adminmaxoverride', !empty($record['adminMaxOverride']));
			$targetHolon->set('color', trim((string)($record['color'] ?? '')) !== '' ? $record['color'] : null);
			$targetHolon->set('icon', trim((string)($record['icon'] ?? '')) !== '' ? $record['icon'] : null);
			$targetHolon->set('banner', trim((string)($record['banner'] ?? '')) !== '' ? $record['banner'] : null);
			$targetHolon->set('accesskey', trim((string)($record['accessKey'] ?? '')) !== '' ? $record['accessKey'] : null);

			if ($isOrganizationRoot) {
				$targetHolon->set('IDholon_parent', null);
				$targetHolon->set('IDholon_template', null);
				$targetHolon->set('IDorganization', (int)$this->getId());
			}

			$targetHolon->save();
		}

		protected static function omo1ImportExistingImagePath($value): ?string
		{
			$path = trim((string)$value);
			if (
				$path === ''
				|| strpos($path, "\0") !== false
				|| $path[0] !== '/'
				|| preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $path)
			) {
				return null;
			}

			$documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
			if (!is_string($documentRoot) || $documentRoot === '') {
				return null;
			}

			$absolutePath = realpath($documentRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('\\', '/', $path), '/'));
			if (!is_string($absolutePath) || $absolutePath === '') {
				return null;
			}

			$normalizedRoot = rtrim(str_replace('\\', '/', $documentRoot), '/') . '/';
			$normalizedPath = str_replace('\\', '/', $absolutePath);
			if (stripos($normalizedPath, $normalizedRoot) !== 0 || !is_file($absolutePath) || !is_readable($absolutePath)) {
				return null;
			}

			return @getimagesize($absolutePath) === false
				? null
				: '/' . ltrim(substr($normalizedPath, strlen($normalizedRoot)), '/');
		}

		protected static function sanitizeOmo1ImportedMediaReferences(array $payload, array &$warnings): array
		{
			$discardedCount = 0;
			$sanitizeRecord = function (array &$record, array $fields) use (&$discardedCount) {
				foreach ($fields as $field) {
					$value = trim((string)($record[$field] ?? ''));
					if ($value === '') {
						continue;
					}

					$imagePath = self::omo1ImportExistingImagePath($value);
					if ($imagePath === null) {
						$record[$field] = null;
						$discardedCount += 1;
						continue;
					}

					$record[$field] = $imagePath;
				}
			};

			if (isset($payload['organization']) && is_array($payload['organization'])) {
				$sanitizeRecord($payload['organization'], array('logo', 'banner'));
			}

			$sanitizeHolons = null;
			$sanitizeHolons = function (array &$nodes) use (&$sanitizeHolons, $sanitizeRecord) {
				foreach ($nodes as &$node) {
					if (!is_array($node)) {
						continue;
					}
					$sanitizeRecord($node, array('icon', 'banner'));
					if (isset($node['children']) && is_array($node['children'])) {
						$sanitizeHolons($node['children']);
					}
				}
				unset($node);
			};
			if (isset($payload['holons']) && is_array($payload['holons'])) {
				$sanitizeHolons($payload['holons']);
			}

			if ($discardedCount > 0) {
				$warnings[] = $discardedCount . ' image(s) indisponible(s) sur ce serveur ont ete ignorees.';
			}

			return $payload;
		}

		protected function createImportedHolonFromCompactRecord(array $record, $targetParentId, $targetRootHolonId, $userId = 0)
		{
			$targetHolon = new \dbObject\Holon();
			$targetHolon->set('IDholon_parent', (int)$targetParentId > 0 ? (int)$targetParentId : null);
			$targetHolon->set('IDholon_template', null);
			$targetHolon->set('IDholon_org', (int)$targetRootHolonId);
			$targetHolon->set('IDorganization', null);
			$this->applyImportedCompactRecordToHolon($targetHolon, $record, $userId, false, false);

			return $targetHolon;
		}

		protected function importCompactHolonPropertyRows(array $record, \dbObject\Holon $targetHolon, array $propertyIdMap, array $holonIdMap, array $authorityIdMap = array())
		{
			$rows = $record['properties'] ?? array();
			if (!is_array($rows)) {
				return;
			}

			foreach ($rows as $row) {
				if (!is_array($row)) {
					continue;
				}

				$sourcePropertyId = (int)($row['propertyId'] ?? 0);
				if ($sourcePropertyId <= 0 || !isset($propertyIdMap[$sourcePropertyId])) {
					continue;
				}

				$targetProperty = new \dbObject\Property();
				if (!$targetProperty->load((int)$propertyIdMap[$sourcePropertyId])) {
					continue;
				}

				$value = array_key_exists('value', $row)
					? $this->cloneStructuralPropertyValue($targetProperty, $row['value'], $holonIdMap, $authorityIdMap)
					: null;

				$holonProperty = new \dbObject\HolonProperty();
				$holonProperty->set('IDholon', (int)$targetHolon->getId());
				$holonProperty->set('IDproperty', (int)$targetProperty->getId());
				$holonProperty->set('value', $value);
				$holonProperty->set('position', (int)($row['position'] ?? 0));
				$holonProperty->set('mandatory', !empty($row['mandatory']));
				$holonProperty->set('locked', !empty($row['locked']));
				$holonProperty->set('active', !array_key_exists('active', $row) || (bool)$row['active']);
				$holonProperty->save();
			}
		}

		protected function importCompactAuthorityRecords(array $payload, array $targetHolonsBySourceId)
		{
			$records = isset($payload['authorities']) && is_array($payload['authorities'])
				? $payload['authorities']
				: array();
			$authorityIdMap = array();
			$createdAuthorities = array();
			$warnings = array();

			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}

				$sourceAuthorityId = (int)($record['id'] ?? $record['sourceId'] ?? 0);
				$sourceHolonId = (int)($record['holonId'] ?? $record['sourceHolonId'] ?? 0);
				$targetHolon = $targetHolonsBySourceId[$sourceHolonId] ?? null;
				if ($sourceAuthorityId <= 0 || !($targetHolon instanceof \dbObject\Holon)) {
					if ($sourceAuthorityId > 0) {
						$warnings[] = 'Une autorite exportee n a pas pu etre rattachee a son holon importe.';
					}
					continue;
				}

				$label = trim((string)($record['label'] ?? ''));
				if ($label === '') {
					$warnings[] = 'Une autorite exportee sans libelle a ete ignoree.';
					continue;
				}

				$authority = new \dbObject\Authority();
				$authority->set('IDholon', (int)$targetHolon->getId());
				$authority->set('IDauthority_parent', null);
				$authority->set('IDauthority_template', null);
				$authority->set('label', $label);
				$authority->set('description', trim((string)($record['description'] ?? '')) ?: null);
				$authority->set('is_shell', !empty($record['isShell']));
				$authority->set('is_local', !empty($record['isLocal']));
				$authority->set('template_origin_lost', !empty($record['templateOriginLost']));
				$saveResult = $authority->save();
				if (!is_array($saveResult) || empty($saveResult['status']) || (int)$authority->getId() <= 0) {
					throw new \RuntimeException('Une autorite exportee n a pas pu etre importee.');
				}

				$authorityIdMap[$sourceAuthorityId] = (int)$authority->getId();
				$createdAuthorities[$sourceAuthorityId] = array(
					'authority' => $authority,
					'record' => $record,
				);
			}

			foreach ($createdAuthorities as $sourceAuthorityId => $entry) {
				$authority = $entry['authority'];
				$record = $entry['record'];
				$sourceParentId = (int)($record['parentAuthorityId'] ?? $record['parentId'] ?? 0);
				$sourceTemplateAuthorityId = (int)($record['templateAuthorityId'] ?? 0);
				$targetParentId = $authorityIdMap[$sourceParentId] ?? 0;
				$targetTemplateAuthorityId = $authorityIdMap[$sourceTemplateAuthorityId] ?? 0;
				$templateOriginLost = !empty($record['templateOriginLost']);

				if ($sourceParentId > 0 && $targetParentId <= 0) {
					$warnings[] = 'Une autorite importee a perdu son rattachement parent hors du perimetre exporte.';
				}
				if ($sourceTemplateAuthorityId > 0 && $targetTemplateAuthorityId <= 0) {
					$templateOriginLost = true;
					$warnings[] = 'Une autorite importee a perdu son lien vers son autorite source de modele.';
				}

				$authority->set('IDauthority_parent', $targetParentId > 0 ? $targetParentId : null);
				$authority->set('IDauthority_template', $targetTemplateAuthorityId > 0 ? $targetTemplateAuthorityId : null);
				$authority->set('template_origin_lost', $templateOriginLost);
				$saveResult = $authority->save();
				if (!is_array($saveResult) || empty($saveResult['status'])) {
					throw new \RuntimeException('Les liens entre les autorites importees n ont pas pu etre restaures.');
				}
			}

			return array(
				'authorityIdMap' => $authorityIdMap,
				'warnings' => array_values(array_unique($warnings)),
			);
		}

		protected function importCompactRuleRecords(array $payload, array $authorityIdMap, array $targetHolonsBySourceId, $userId = 0)
		{
			$records = isset($payload['rules']) && is_array($payload['rules'])
				? $payload['rules']
				: array();
			$warnings = array();

			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}

				$sourceAuthorityId = (int)($record['authorityId'] ?? $record['sourceAuthorityId'] ?? 0);
				$sourceHolonId = (int)($record['holonId'] ?? $record['sourceHolonId'] ?? 0);
				$targetAuthorityId = $authorityIdMap[$sourceAuthorityId] ?? 0;
				$targetHolon = $targetHolonsBySourceId[$sourceHolonId] ?? null;
				if ($sourceAuthorityId > 0 && $targetAuthorityId <= 0) {
					$warnings[] = 'Une regle exportee a ete ignoree car son autorite est absente de l import.';
					continue;
				}
				if ($sourceAuthorityId <= 0 && !($targetHolon instanceof \dbObject\Holon)) {
					$warnings[] = 'Une regle locale exportee a ete ignoree car son holon est absent de l import.';
					continue;
				}

				$title = trim((string)($record['title'] ?? ''));
				$description = trim((string)($record['description'] ?? ''));
				$reviewDate = trim((string)($record['reviewDate'] ?? ''));
				$expirationDate = trim((string)($record['expirationDate'] ?? ''));
				if ($title === '' || $description === '' || $reviewDate === '' || $expirationDate === '') {
					$warnings[] = 'Une regle exportee incomplete a ete ignoree.';
					continue;
				}

				$rule = new \dbObject\Rule();
				$rule->set('IDauthority', $targetAuthorityId > 0 ? $targetAuthorityId : null);
				$rule->set('IDholon', $targetAuthorityId > 0 ? null : (int)$targetHolon->getId());
				$rule->set('title', $title);
				$rule->set('intention', trim((string)($record['intention'] ?? '')) ?: null);
				$rule->set('description', $description);
				$rule->set('scope', $targetAuthorityId > 0 ? \dbObject\Rule::normalizeScope($record['scope'] ?? null) : \dbObject\Rule::SCOPE_LOCAL);
				$rule->set('review_date', $reviewDate);
				$rule->set('expiration_date', $expirationDate);
				$saveResult = $rule->save();
				if (!is_array($saveResult) || empty($saveResult['status'])) {
					throw new \RuntimeException('Une regle exportee n a pas pu etre importee.');
				}
			}

			return array_values(array_unique($warnings));
		}

		protected function alignImportedHolonPropertyPositionsWithTemplate(\dbObject\Holon $targetHolon, \dbObject\Holon $templateHolon)
		{
			$positionsByPropertyId = array();
			$lastPosition = 0;
			foreach ($templateHolon->getTemplatePropertyDefinitions() as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId <= 0 || isset($positionsByPropertyId[$propertyId])) {
					continue;
				}

				$position = (int)($definition['position'] ?? 0);
				if ($position <= 0) {
					$position = $lastPosition + 1;
				}
				$positionsByPropertyId[$propertyId] = $position;
				$lastPosition = max($lastPosition, $position);
			}

			$extraProperties = array();
			foreach ($targetHolon->getHolonProperties() as $holonProperty) {
				$propertyId = (int)$holonProperty->get('IDproperty');
				if (isset($positionsByPropertyId[$propertyId])) {
					$holonProperty->set('position', (int)$positionsByPropertyId[$propertyId]);
					$holonProperty->save();
					continue;
				}
				$extraProperties[] = $holonProperty;
			}

			usort($extraProperties, static function ($left, $right) {
				$positionComparison = (int)$left->get('position') <=> (int)$right->get('position');
				return $positionComparison !== 0
					? $positionComparison
					: ((int)$left->getId() <=> (int)$right->getId());
			});
			foreach ($extraProperties as $holonProperty) {
				$lastPosition += 1;
				$holonProperty->set('position', $lastPosition);
				$holonProperty->save();
			}
		}

		protected function importCompactHolonPermissionRows(array $record, \dbObject\Holon $targetHolon)
		{
			$targetHolonId = (int)$targetHolon->getId();
			if ($targetHolonId <= 0) {
				return false;
			}

			$rows = $record['permissions'] ?? array();
			$assignmentsByPermissionKey = array(
				\dbObject\HolonPermission::MEMBER_TYPE_MEMBER => array(),
				\dbObject\HolonPermission::MEMBER_TYPE_ADMIN => array(),
			);

			if (is_array($rows)) {
				foreach ($rows as $row) {
					if (!is_array($row)) {
						continue;
					}

					$permissionKey = trim((string)($row['permissionKey'] ?? ''));
					$range = trim((string)($row['range'] ?? ''));
					$memberType = \dbObject\HolonPermission::normalizeMemberType($row['memberType'] ?? \dbObject\HolonPermission::MEMBER_TYPE_MEMBER);
					if ($permissionKey === '' || $range === '') {
						continue;
					}

					if (!isset($assignmentsByPermissionKey[$memberType][$permissionKey])) {
						$assignmentsByPermissionKey[$memberType][$permissionKey] = array();
					}

					$assignmentsByPermissionKey[$memberType][$permissionKey][] = $range;
				}
			}

			return \dbObject\HolonPermission::syncAssignmentsForHolon($targetHolonId, $assignmentsByPermissionKey);
		}

		protected static function normalizeImportTemplateKey($value)
		{
			$value = trim((string)$value);
			if ($value === '') {
				return '';
			}

			$value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
			$asciiValue = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
			if (is_string($asciiValue) && $asciiValue !== '') {
				$value = $asciiValue;
			}

			return trim((string)preg_replace('/[^a-z0-9]+/', '', $value));
		}

		protected static function getImportPropertyMatchKeys($shortname, $name)
		{
			$keys = array();
			foreach (array($shortname, $name) as $value) {
				$key = self::normalizeImportTemplateKey($value);
				if ($key !== '') {
					$keys[$key] = true;
				}
			}

			$rdeKeys = array('rde', 'raisonetre', 'raisondetre', 'purpose');
			foreach ($rdeKeys as $rdeKey) {
				if (isset($keys[$rdeKey])) {
					foreach ($rdeKeys as $aliasKey) {
						$keys[$aliasKey] = true;
					}
					break;
				}
			}

			return array_keys($keys);
		}

		protected static function isImportedTemplateRecord(array $record)
		{
			return trim((string)($record['templateName'] ?? '')) !== ''
				|| (array_key_exists('visible', $record) && !(bool)$record['visible']);
		}

		protected function getImportTemplateNodes(\dbObject\Holon $templateRootHolon)
		{
			$nodes = array();
			$visitedHolonIds = array();
			$templateRootHolonId = (int)$templateRootHolon->getId();
			$collectNodes = function (\dbObject\Holon $parentHolon) use (&$collectNodes, &$nodes, &$visitedHolonIds, $templateRootHolonId) {
				$parentHolonId = (int)$parentHolon->getId();
				if ($parentHolonId <= 0 || isset($visitedHolonIds[$parentHolonId])) {
					return;
				}
				$visitedHolonIds[$parentHolonId] = true;

				$children = new \dbObject\ArrayHolon();
				$children->load(array(
					'where' => array(
						array('field' => 'IDholon_parent', 'value' => $parentHolonId),
						array('field' => 'active', 'value' => 1),
					),
					'orderBy' => array(
						array('field' => 'IDtypeholon', 'dir' => 'ASC'),
						array('field' => 'name', 'dir' => 'ASC'),
						array('field' => 'templatename', 'dir' => 'ASC'),
						array('field' => 'id', 'dir' => 'ASC'),
					),
				));
				foreach ($children as $childHolon) {
					if ($childHolon->isTemplateNode($templateRootHolonId)) {
						$nodes[(int)$childHolon->getId()] = $childHolon;
					}
					$collectNodes($childHolon);
				}
			};

			$collectNodes($templateRootHolon);
			return $nodes;
		}

		protected function getImportTemplateNodePath(\dbObject\Holon $nodeHolon, $templateRootHolonId)
		{
			$templateRootHolonId = (int)$templateRootHolonId;
			$labels = array();
			$currentHolon = $nodeHolon;
			$visitedHolonIds = array();

			while ($currentHolon instanceof \dbObject\Holon) {
				$currentHolonId = (int)$currentHolon->getId();
				if ($currentHolonId <= 0 || isset($visitedHolonIds[$currentHolonId])) {
					break;
				}
				$visitedHolonIds[$currentHolonId] = true;
				if ($currentHolonId === $templateRootHolonId) {
					break;
				}

				$label = $currentHolon->isTemplateNode($templateRootHolonId)
					? $this->getStructuralInitializationTemplateName($currentHolon)
					: $currentHolon->getDisplayName();
				if (trim((string)$label) !== '') {
					$labels[] = trim((string)$label);
				}

				$parentHolonId = (int)$currentHolon->get('IDholon_parent');
				if ($parentHolonId <= 0) {
					break;
				}
				$parentHolon = new \dbObject\Holon();
				$currentHolon = $parentHolon->load($parentHolonId) ? $parentHolon : null;
			}

			return implode(' > ', array_reverse($labels));
		}

		public function getStructuralImportTemplateCatalog()
		{
			$catalog = array();
			foreach ($this->getStructuralInitializationTemplates() as $template) {
				$templateRootHolonId = (int)($template['id'] ?? 0);
				if ($templateRootHolonId <= 0) {
					continue;
				}

				$templateRootHolon = new \dbObject\Holon();
				if (!$templateRootHolon->load($templateRootHolonId)) {
					continue;
				}

				$nodes = array();
				foreach ($this->getImportTemplateNodes($templateRootHolon) as $nodeHolon) {
					$nodes[] = array(
						'id' => (int)$nodeHolon->getId(),
						'name' => $this->getStructuralInitializationTemplateName($nodeHolon),
						'path' => $this->getImportTemplateNodePath($nodeHolon, $templateRootHolonId),
						'typeId' => (int)$nodeHolon->get('IDtypeholon'),
						'parentId' => (int)$nodeHolon->get('IDholon_parent'),
					);
				}

				$template['nodes'] = $nodes;
				$catalog[] = $template;
			}

			return $catalog;
		}

		protected function cloneImportTemplateNodes(\dbObject\Holon $sourceTemplateRoot, \dbObject\Holon $targetRootHolon, $userId = 0)
		{
			$sourceNodesById = $this->getImportTemplateNodes($sourceTemplateRoot);
			$targetNodesBySourceId = array();
			$targetNodeIdMap = array();
			$propertyIdMap = array();
			$sourceRootHolonId = (int)$sourceTemplateRoot->getId();
			$targetRootHolonId = (int)$targetRootHolon->getId();

			foreach ($sourceNodesById as $sourceNodeId => $sourceNode) {
				$sourceParentId = (int)$sourceNode->get('IDholon_parent');
				$targetParentId = isset($targetNodeIdMap[$sourceParentId])
					? (int)$targetNodeIdMap[$sourceParentId]
					: $targetRootHolonId;

				$targetNode = new \dbObject\Holon();
				$targetNode->set('name', $sourceNode->get('name'));
				$targetNode->set('nomcomplet', trim((string)$sourceNode->get('nomcomplet')) !== '' ? $sourceNode->get('nomcomplet') : null);
				$targetNode->set('templatename', $sourceNode->get('templatename'));
				$targetNode->set('IDtypeholon', (int)$sourceNode->get('IDtypeholon'));
				$targetNode->set('IDholon_parent', $targetParentId);
				$targetNode->set('IDholon_template', null);
				$targetNode->set('IDholon_org', $targetRootHolonId);
				$targetNode->set('IDorganization', null);
				$targetNode->set('IDuser', (int)$userId > 0 ? (int)$userId : (int)$sourceNode->get('IDuser'));
				$targetNode->set('active', (bool)$sourceNode->get('active'));
				$targetNode->set('visible', (bool)$sourceNode->get('visible'));
				$targetNode->set('mandatory', (bool)$sourceNode->get('mandatory'));
				$targetNode->set('lockedname', (bool)$sourceNode->get('lockedname'));
				$targetNode->set('lockedicon', (bool)$sourceNode->get('lockedicon'));
				$targetNode->set('lockedbanner', (bool)$sourceNode->get('lockedbanner'));
				$targetNode->set('unique', (bool)$sourceNode->get('unique'));
				$targetNode->set('link', (bool)$sourceNode->get('link'));
				$targetNode->set('adminparent', (bool)$sourceNode->get('adminparent'));
				$targetNode->set('admin_min', max(0, (int)$sourceNode->get('admin_min')));
				$targetNode->set('admin_max', $sourceNode->get('admin_max') === null ? null : (int)$sourceNode->get('admin_max'));
				$targetNode->set('lockedadminmin', (bool)$sourceNode->get('lockedadminmin'));
				$targetNode->set('lockedadminmax', (bool)$sourceNode->get('lockedadminmax'));
				$targetNode->set('adminminoverride', (bool)$sourceNode->get('adminminoverride'));
				$targetNode->set('adminmaxoverride', (bool)$sourceNode->get('adminmaxoverride'));
				$targetNode->set('color', $sourceNode->get('color') ?: null);
				$targetNode->set('icon', $sourceNode->get('icon') ?: null);
				$targetNode->set('banner', $sourceNode->get('banner') ?: null);
				$targetNode->set('accesskey', $sourceNode->get('accesskey') ?: null);
				$targetNode->save();
				if ((int)$targetNode->getId() <= 0) {
					throw new \RuntimeException("Un template du modele selectionne n'a pas pu etre copie.");
				}

				$targetNodesBySourceId[(int)$sourceNodeId] = $targetNode;
				$targetNodeIdMap[(int)$sourceNodeId] = (int)$targetNode->getId();
			}

			foreach ($sourceNodesById as $sourceNodeId => $sourceNode) {
				$targetNode = $targetNodesBySourceId[(int)$sourceNodeId];
				$sourceTemplateId = (int)$sourceNode->get('IDholon_template');
				$targetNode->set(
					'IDholon_template',
					isset($targetNodeIdMap[$sourceTemplateId]) ? (int)$targetNodeIdMap[$sourceTemplateId] : null
				);
				$targetNode->save();
			}

			$authorityIdMap = $this->cloneStructuralAuthorities($sourceNodesById, $targetNodesBySourceId);

			foreach ($sourceNodesById as $sourceNodeId => $sourceNode) {
				$targetNode = $targetNodesBySourceId[(int)$sourceNodeId];
				$this->cloneStructuralHolonProperties(
					$sourceNode,
					$targetNode,
					$sourceRootHolonId,
					$targetRootHolonId,
					$targetNodeIdMap,
					$propertyIdMap,
					$authorityIdMap
				);
				if (!$this->cloneStructuralHolonPermissions($sourceNode, $targetNode)) {
					throw new \RuntimeException("Les droits d'un template du modele n'ont pas pu etre copies.");
				}
			}

			return array(
				'sourceNodesById' => $sourceNodesById,
				'targetNodesBySourceId' => $targetNodesBySourceId,
				'targetNodeIdMap' => $targetNodeIdMap,
			);
		}

		protected function applyImportTemplateCalibration(array $recordsBySourceId, array $propertyDefinitions, \dbObject\Holon $targetRootHolon, array &$holonIdMap, array &$targetHolonsBySourceId, array &$propertyIdMap, array $calibration, $userId = 0)
		{
			$templateRootHolonId = (int)($calibration['templateRootHolonId'] ?? 0);
			$mappings = isset($calibration['mappings']) && is_array($calibration['mappings']) ? $calibration['mappings'] : array();
			if ($templateRootHolonId <= 0 || count($mappings) === 0) {
				return array('mappedSourceTemplateIds' => array(), 'warnings' => array(), 'authorityTemplateApplied' => false);
			}

			$templateRootHolon = new \dbObject\Holon();
			if (
				!$templateRootHolon->load($templateRootHolonId)
				|| (int)$templateRootHolon->get('IDtypeholon') !== 4
				|| !(bool)$templateRootHolon->get('active')
				|| trim((string)$templateRootHolon->get('templatename')) === ''
			) {
				throw new \RuntimeException("Le modele d'organisation selectionne est introuvable.");
			}
			$authorityTemplateApplied = $this->templateTreeUsesAuthorityProperties($templateRootHolon);

			$clonedTemplates = $this->cloneImportTemplateNodes($templateRootHolon, $targetRootHolon, $userId);
			$targetTemplateNodes = $clonedTemplates['targetNodesBySourceId'];
			$mappedSourceTemplateIds = array();
			$mappedTargetTemplateIds = array();
			$warnings = array();
			$propertyDefinitionsById = array();
			foreach ($propertyDefinitions as $definition) {
				$propertyDefinitionsById[(int)($definition['id'] ?? 0)] = $definition;
			}

			foreach ($mappings as $sourceTemplateId => $targetTemplateSourceId) {
				$sourceTemplateId = (int)$sourceTemplateId;
				$targetTemplateSourceId = (int)$targetTemplateSourceId;
				$sourceRecord = $recordsBySourceId[$sourceTemplateId] ?? null;
				$targetTemplate = $targetTemplateNodes[$targetTemplateSourceId] ?? null;
				if (!is_array($sourceRecord) || !self::isImportedTemplateRecord($sourceRecord) || !($targetTemplate instanceof \dbObject\Holon)) {
					throw new \RuntimeException('Une correspondance de template est invalide.');
				}
				if ((int)($sourceRecord['typeId'] ?? 0) !== (int)$targetTemplate->get('IDtypeholon')) {
					throw new \RuntimeException('Les templates associes doivent etre du meme type de holon.');
				}
				if (isset($mappedTargetTemplateIds[$targetTemplateSourceId])) {
					throw new \RuntimeException('Un template du modele ne peut etre associe qu a un seul template importe.');
				}

				$holonIdMap[$sourceTemplateId] = (int)$targetTemplate->getId();
				$targetHolonsBySourceId[$sourceTemplateId] = $targetTemplate;
				$mappedSourceTemplateIds[$sourceTemplateId] = true;
				$mappedTargetTemplateIds[$targetTemplateSourceId] = true;

				$targetPropertiesByKey = array();
				foreach ($targetTemplate->getTemplatePropertyDefinitions() as $targetDefinition) {
					$targetPropertyId = (int)($targetDefinition['id'] ?? 0);
					if ($targetPropertyId <= 0) {
						continue;
					}
					foreach (self::getImportPropertyMatchKeys($targetDefinition['shortname'] ?? '', $targetDefinition['name'] ?? '') as $targetPropertyKey) {
						$targetPropertiesByKey[$targetPropertyKey] = $targetPropertyId;
					}
				}

				$sourcePropertyIds = array();
				$currentSourceTemplateRecord = $sourceRecord;
				$visitedSourceTemplateIds = array();
				while (is_array($currentSourceTemplateRecord)) {
					$currentSourceTemplateId = (int)($currentSourceTemplateRecord['id'] ?? 0);
					if ($currentSourceTemplateId <= 0 || isset($visitedSourceTemplateIds[$currentSourceTemplateId])) {
						break;
					}
					$visitedSourceTemplateIds[$currentSourceTemplateId] = true;
					foreach (($currentSourceTemplateRecord['properties'] ?? array()) as $sourcePropertyRow) {
						$sourcePropertyId = (int)($sourcePropertyRow['propertyId'] ?? 0);
						if ($sourcePropertyId > 0) {
							$sourcePropertyIds[$sourcePropertyId] = true;
						}
					}
					$currentSourceTemplateRecord = $recordsBySourceId[(int)($currentSourceTemplateRecord['templateId'] ?? 0)] ?? null;
				}

				foreach (array_keys($sourcePropertyIds) as $sourcePropertyId) {
					$sourcePropertyId = (int)$sourcePropertyId;
					$sourceDefinition = $propertyDefinitionsById[$sourcePropertyId] ?? null;
					if (!is_array($sourceDefinition)) {
						continue;
					}

					$targetPropertyId = 0;
					foreach (self::getImportPropertyMatchKeys($sourceDefinition['shortname'] ?? '', $sourceDefinition['name'] ?? '') as $sourcePropertyKey) {
						if (isset($targetPropertiesByKey[$sourcePropertyKey])) {
							$targetPropertyId = (int)$targetPropertiesByKey[$sourcePropertyKey];
							break;
						}
					}
					if ($targetPropertyId <= 0) {
						continue;
					}

					$targetProperty = new \dbObject\Property();
					if (!$targetProperty->load($targetPropertyId)) {
						continue;
					}

					$sourceFormatId = (int)($sourceDefinition['formatId'] ?? 0);
					$sourceListItemType = \dbObject\PropertyFormat::isListFormat($sourceFormatId)
						? \dbObject\Property::normalizeListItemType($sourceDefinition['listItemType'] ?? null)
						: null;
					$sourceListHolonTypeIds = \dbObject\PropertyFormat::isListFormat($sourceFormatId)
						? \dbObject\Property::serializeHolonTypeIds($sourceDefinition['listHolonTypeIds'] ?? array())
						: null;
					if (
						$sourceFormatId > 0
						&& (
							(int)$targetProperty->get('IDpropertyformat') !== $sourceFormatId
							|| (string)$targetProperty->get('listitemtype') !== (string)$sourceListItemType
							|| (string)$targetProperty->get('listholontypeids') !== (string)$sourceListHolonTypeIds
						)
					) {
						$warnings[] = 'La propriete ' . trim((string)$targetProperty->get('name')) . ' conserve le format du modele applique.';
					}

					$propertyIdMap[$sourcePropertyId] = $targetPropertyId;
				}
			}

			return array(
				'mappedSourceTemplateIds' => $mappedSourceTemplateIds,
				'warnings' => array_values(array_unique($warnings)),
				'authorityTemplateApplied' => $authorityTemplateApplied,
				'targetTemplateNodes' => $targetTemplateNodes,
			);
		}

		protected function templateTreeUsesAuthorityProperties(\dbObject\Holon $templateRootHolon)
		{
			$templatesToVisit = array($templateRootHolon);
			$visited = array();
			while (count($templatesToVisit) > 0) {
				$template = array_pop($templatesToVisit);
				$templateId = (int)$template->getId();
				if ($templateId <= 0 || isset($visited[$templateId])) {
					continue;
				}
				$visited[$templateId] = true;

				foreach ($template->getTemplatePropertyDefinitions() as $definition) {
					if (
						\dbObject\PropertyFormat::isListFormat((int)($definition['formatId'] ?? 0))
						&& \dbObject\Property::normalizeListItemType($definition['listItemType'] ?? null) === \dbObject\Property::LIST_ITEM_AUTHORITY
					) {
						return true;
					}
				}

				foreach ($template->getTemplateChildren() as $child) {
					if ($child instanceof \dbObject\Holon) {
						$templatesToVisit[] = $child;
					}
				}
			}

			return false;
		}

		protected function importStructureFromCompactGraph(array $payload, $userId = 0, array $calibration = array())
		{
			$records = $this->getImportedHolonRecords($payload);
			$propertyDefinitions = $this->getImportedCompactPropertyDefinitions($payload);
			if (count($records) === 0) {
				return array(
					'status' => false,
					'message' => "Le fichier d'import ne contient pas de holons valides.",
				);
			}

			$recordsBySourceId = array();
			foreach ($records as $record) {
				$sourceId = (int)($record['id'] ?? 0);
				if ($sourceId > 0) {
					$recordsBySourceId[$sourceId] = $record;
				}
			}

			$scope = isset($payload['scope']) && is_array($payload['scope']) ? $payload['scope'] : array();
			$sourceRootId = (int)($scope['organizationRootHolonId'] ?? 0);
			if ($sourceRootId <= 0 || !isset($recordsBySourceId[$sourceRootId])) {
				foreach ($recordsBySourceId as $candidateId => $record) {
					if ((string)($record['role'] ?? '') === 'organization_root') {
						$sourceRootId = (int)$candidateId;
						break;
					}
				}
			}

			if ($sourceRootId <= 0 || !isset($recordsBySourceId[$sourceRootId])) {
				return array(
					'status' => false,
					'message' => "Impossible d'identifier la racine de l'organisation dans le fichier compact.",
				);
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'La connexion a la base de donnees est indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				$targetRootHolon = $this->createStructuralRootHolon($userId);
				if (!$targetRootHolon) {
					throw new \RuntimeException("Le holon racine n'a pas pu etre cree.");
				}

				$targetRootHolonId = (int)$targetRootHolon->getId();
				$this->applyImportedCompactRecordToHolon($targetRootHolon, $recordsBySourceId[$sourceRootId], $userId, true, true);

				$propertyIdMap = array();
				$this->createImportedPropertiesFromCompactDefinitions($propertyDefinitions, $targetRootHolonId, $propertyIdMap);

				$holonIdMap = array(
					$sourceRootId => $targetRootHolonId,
				);
				$targetHolonsBySourceId = array(
					$sourceRootId => $targetRootHolon,
				);
				$calibrationResult = $this->applyImportTemplateCalibration(
					$recordsBySourceId,
					$propertyDefinitions,
					$targetRootHolon,
					$holonIdMap,
					$targetHolonsBySourceId,
					$propertyIdMap,
					$calibration,
					$userId
				);
				$mappedSourceTemplateIds = $calibrationResult['mappedSourceTemplateIds'] ?? array();
				$calibrationWarnings = $calibrationResult['warnings'] ?? array();

				$pending = $recordsBySourceId;
				unset($pending[$sourceRootId]);
				foreach ($mappedSourceTemplateIds as $mappedSourceTemplateId => $isMapped) {
					if ($isMapped) {
						unset($pending[(int)$mappedSourceTemplateId]);
					}
				}

				$guard = 0;
				while (count($pending) > 0 && $guard < 1000) {
					$progress = false;

					foreach ($pending as $sourceId => $record) {
						$parentSourceId = (int)($record['parentId'] ?? 0);
						if ($parentSourceId > 0 && !isset($holonIdMap[$parentSourceId])) {
							continue;
						}

						$targetParentId = $parentSourceId > 0 ? (int)$holonIdMap[$parentSourceId] : $targetRootHolonId;
						$targetHolon = $this->createImportedHolonFromCompactRecord($record, $targetParentId, $targetRootHolonId, $userId);
						if ((int)$targetHolon->getId() <= 0) {
							throw new \RuntimeException("Un holon du fichier compact n'a pas pu etre cree.");
						}

						$holonIdMap[$sourceId] = (int)$targetHolon->getId();
						$targetHolonsBySourceId[$sourceId] = $targetHolon;
						unset($pending[$sourceId]);
						$progress = true;
					}

					if (!$progress) {
						throw new \RuntimeException("Le graphe compact contient des dependances de parent invalides.");
					}

					$guard += 1;
				}

				foreach ($recordsBySourceId as $sourceId => $record) {
					if (!isset($targetHolonsBySourceId[$sourceId])) {
						continue;
					}
					if (!empty($mappedSourceTemplateIds[(int)$sourceId])) {
						continue;
					}

					$templateSourceId = (int)($record['templateId'] ?? 0);
					$targetHolon = $targetHolonsBySourceId[$sourceId];
					$targetHolon->set(
						'IDholon_template',
						($templateSourceId > 0 && isset($holonIdMap[$templateSourceId]))
							? (int)$holonIdMap[$templateSourceId]
							: null
					);
					if ($templateSourceId > 0 && !empty($mappedSourceTemplateIds[$templateSourceId])) {
						$mappedTemplate = $targetHolonsBySourceId[$templateSourceId] ?? null;
						if ($mappedTemplate instanceof \dbObject\Holon) {
							$mappedTemplateName = trim((string)$mappedTemplate->getDisplayName());
							if ($mappedTemplateName !== '' && (bool)$mappedTemplate->getEffectiveTemplateBooleanField('lockedname')) {
								$targetHolon->set('name', $mappedTemplateName);
							}
							$targetHolon->set('color', null);
							$targetHolon->set('icon', null);
							$targetHolon->set('banner', null);
							$targetHolon->set('mandatory', false);
							$targetHolon->set('lockedname', false);
							$targetHolon->set('lockedicon', false);
							$targetHolon->set('lockedbanner', false);
							$targetHolon->set('unique', false);
							$targetHolon->set('link', false);
							$targetHolon->set('adminparent', false);
							$targetHolon->set('admin_min', 0);
							$targetHolon->set('admin_max', null);
							$targetHolon->set('lockedadminmin', false);
							$targetHolon->set('lockedadminmax', false);
							$targetHolon->set('adminminoverride', false);
							$targetHolon->set('adminmaxoverride', false);
						}
					}
					$targetHolon->save();
				}

				$authorityImportResult = $this->importCompactAuthorityRecords($payload, $targetHolonsBySourceId);
				$authorityIdMap = $authorityImportResult['authorityIdMap'] ?? array();
				$calibrationWarnings = array_merge(
					$calibrationWarnings,
					is_array($authorityImportResult['warnings'] ?? null) ? $authorityImportResult['warnings'] : array()
				);

				foreach ($recordsBySourceId as $sourceId => $record) {
					if (!isset($targetHolonsBySourceId[$sourceId])) {
						continue;
					}
					if (!empty($mappedSourceTemplateIds[(int)$sourceId])) {
						continue;
					}

					$this->importCompactHolonPropertyRows(
						$record,
						$targetHolonsBySourceId[$sourceId],
						$propertyIdMap,
						$holonIdMap,
						$authorityIdMap
					);

					$templateSourceId = (int)($record['templateId'] ?? 0);
					if (!empty($mappedSourceTemplateIds[$templateSourceId])) {
						$mappedTemplate = $targetHolonsBySourceId[$templateSourceId] ?? null;
						if ($mappedTemplate instanceof \dbObject\Holon) {
							$this->alignImportedHolonPropertyPositionsWithTemplate(
								$targetHolonsBySourceId[$sourceId],
								$mappedTemplate
							);
						}
					}
					if (
						empty($mappedSourceTemplateIds[$templateSourceId])
						&& !$this->importCompactHolonPermissionRows($record, $targetHolonsBySourceId[$sourceId])
					) {
						throw new \RuntimeException("Les droits d'un holon importe n'ont pas pu etre recrees.");
					}
				}

				$calibrationWarnings = array_merge(
					$calibrationWarnings,
					$this->importCompactRuleRecords($payload, $authorityIdMap, $targetHolonsBySourceId, $userId)
				);

				foreach (($calibrationResult['targetTemplateNodes'] ?? array()) as $template) {
					if ($template instanceof \dbObject\Holon) {
						$this->normalizeTemplateLocalAuthorities($template);
						$this->syncTemplateAuthorityInstances($template);
					}
				}

				$pdo->commit();

				return array(
					'status' => true,
					'message' => "L'organisation a ete importee depuis le format compact.",
					'rootHolon' => $targetRootHolon,
					'holonIdMap' => $holonIdMap,
					'propertyIdMap' => $propertyIdMap,
					'mappedSourceTemplateIds' => $mappedSourceTemplateIds,
					'authorityTemplateApplied' => !empty($calibrationResult['authorityTemplateApplied']),
					'warnings' => $calibrationWarnings,
				);
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'message' => $exception->getMessage(),
				);
			}
		}

		protected static function omo1ImportModuleRecords(array $payload, $module)
		{
			$modules = isset($payload['modules']) && is_array($payload['modules']) ? $payload['modules'] : array();
			$moduleData = isset($modules[$module]) && is_array($modules[$module]) ? $modules[$module] : array();
			return isset($moduleData['records']) && is_array($moduleData['records']) ? array_values($moduleData['records']) : array();
		}

		protected static function omo1ImportEarliestImportedDate(array $payload, array $selectedModules)
		{
			$dateFields = array_flip(array('createdAt', 'measuredAt', 'scheduledAt', 'openedAt', 'closedAt', 'checkedAt', 'scratchpadAt', 'completedAt', 'deletedAt'));
			$earliestDate = null;
			$scanValue = null;
			$scanValue = function ($value) use (&$scanValue, &$earliestDate, $dateFields) {
				if (!is_array($value)) {
					return;
				}

				foreach ($value as $field => $fieldValue) {
					if (isset($dateFields[$field])) {
						$date = self::omo1ImportDate($fieldValue);
						if ($date && (!$earliestDate || $date < $earliestDate)) {
							$earliestDate = $date;
						}
					}
					if (is_array($fieldValue)) {
						$scanValue($fieldValue);
					}
				}
			};

			foreach ($selectedModules as $module => $isSelected) {
				if (!$isSelected) {
					continue;
				}
				$scanValue(self::omo1ImportModuleRecords($payload, $module));
			}

			return $earliestDate;
		}

		protected static function omo1ImportDate($value)
		{
			if ($value instanceof \DateTimeInterface) {
				return \DateTimeImmutable::createFromInterface($value);
			}

			$value = trim((string)$value);
			if ($value === '' || strpos($value, '0000-00-00') === 0) {
				return null;
			}

			try {
				return new \DateTimeImmutable($value, new \DateTimeZone('Europe/Zurich'));
			} catch (\Throwable $exception) {
				return null;
			}
		}

		protected static function omo1ImportScheduledDate($dateValue, $timeValue)
		{
			$date = self::omo1ImportDate($dateValue);
			if (!$date) {
				return null;
			}

			$timeValue = trim((string)$timeValue);
			if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/', $timeValue)) {
				return $date;
			}

			try {
				return new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $timeValue, new \DateTimeZone('Europe/Zurich'));
			} catch (\Throwable $exception) {
				return $date;
			}
		}

		protected static function omo1ImportSave($object, $label)
		{
			$result = $object->save();
			if (!is_array($result) || empty($result['status']) || (int)$object->getId() <= 0) {
				$message = is_array($result) ? trim((string)($result['text'] ?? '')) : '';
				throw new \RuntimeException($label . ($message !== '' ? ': ' . $message : '.'));
			}
		}

		protected static function omo1ImportLimitText($value, $length)
		{
			$value = trim((string)$value);
			if ($value === '') {
				return '';
			}

			return function_exists('mb_substr') ? mb_substr($value, 0, (int)$length, 'UTF-8') : substr($value, 0, (int)$length);
		}

		protected static function omo1ImportProjectStatus($legacyStatusId, $completedAt = null, $deletedAt = null)
		{
			if (self::omo1ImportDate($completedAt) || self::omo1ImportDate($deletedAt)) {
				return \dbObject\Project::STATUS_DONE;
			}

			$legacyStatusId = (int)$legacyStatusId;
			$statusMap = array(
				1 => \dbObject\Project::STATUS_IN_PROGRESS,
				2 => \dbObject\Project::STATUS_BLOCKED,
				4 => \dbObject\Project::STATUS_DONE,
				8 => \dbObject\Project::STATUS_BLOCKED,
				16 => \dbObject\Project::STATUS_READY,
				32 => \dbObject\Project::STATUS_SOMEDAY,
				64 => \dbObject\Project::STATUS_DONE,
			);

			return isset($statusMap[$legacyStatusId]) ? $statusMap[$legacyStatusId] : \dbObject\Project::STATUS_READY;
		}

		protected static function omo1ImportProjectIsArchived($legacyStatusId)
		{
			return (int)$legacyStatusId === 64;
		}

		protected static function omo1ImportUserMembership(\dbObject\Organization $organization, $userId, $isAdmin, array $record = array(), $isActive = true)
		{
			$userId = (int)$userId;
			if ($userId <= 0) {
				return;
			}

			$membership = new \dbObject\UserOrganization();
			if (!$membership->load(array(array('IDuser', $userId), array('IDorganization', (int)$organization->getId())))) {
				$membership->set('IDuser', $userId);
				$membership->set('IDorganization', (int)$organization->getId());
			}

			$membership->set('active', (bool)$isActive);
			if (!empty($record['username'])) {
				$membership->set('username', self::omo1ImportLimitText($record['username'], 250));
			}
			if (!empty($record['email'])) {
				$membership->set('email', self::omo1ImportLimitText($record['email'], 250));
			}
			if (!empty($record['presentation'])) {
				$membership->set('presentation', $record['presentation']);
			}
			$createdAt = self::omo1ImportDate($record['createdAt'] ?? null);
			if ($createdAt) {
				$membership->set('datecreation', $createdAt);
			}
			$lastConnectionAt = self::omo1ImportDate($record['lastConnectionAt'] ?? null);
			if ($lastConnectionAt) {
				$membership->set('dateconnexion', $lastConnectionAt);
			}

			$parameters = json_decode((string)$membership->get('parameters'), true);
			if (!is_array($parameters)) {
				$parameters = array();
			}
			$parameters['isAdmin'] = (bool)$isAdmin;
			$membership->set('parameters', $parameters);
			self::omo1ImportSave($membership, 'Le lien membre n a pas pu etre cree');
		}

		protected static function omo1ImportMembers(\dbObject\Organization $organization, array $records, $actorUserId, array &$userIdMap, array &$pendingUserIds, array &$pendingInvitations, array &$stats, array &$warnings)
		{
			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}

				$sourceId = (int)($record['sourceId'] ?? 0);
				$email = trim((string)($record['email'] ?? ''));
				if ($sourceId <= 0 || $email === '') {
					$warnings[] = 'Un membre sans adresse e-mail n a pas pu etre importe.';
					continue;
				}

				$user = \dbObject\User::findByLoginIdentifier($email);
				if (!($user instanceof \dbObject\User)) {
					$emailMatchSummary = \dbObject\User::debugLoginIdentifierMatchSummary($email);
					if ((int)($emailMatchSummary['globalEmailMatches'] ?? 0) > 0) {
						$warnings[] = 'Le membre ' . $email . ' n a pas ete importe car plusieurs comptes existants utilisent deja cette adresse e-mail.';
						continue;
					}

					$user = new \dbObject\User();
					$user->set('email', self::omo1ImportLimitText($email, 250));
					$user->set('firstname', self::omo1ImportLimitText($record['firstname'] ?? '', 25));
					$user->set('lastname', self::omo1ImportLimitText($record['lastname'] ?? '', 25));
					$user->set('username', self::omo1ImportLimitText($record['username'] ?? '', 30));
					$user->set('presentation', $record['presentation'] ?? null);
					$user->set('active', !array_key_exists('active', $record) || !empty($record['active']));
					$createdAt = self::omo1ImportDate($record['createdAt'] ?? null);
					if ($createdAt) {
						$user->set('datecreation', $createdAt);
					}
					$lastConnectionAt = self::omo1ImportDate($record['lastConnectionAt'] ?? null);
					if ($lastConnectionAt) {
						$user->set('dateconnexion', $lastConnectionAt);
					}
					self::omo1ImportSave($user, 'Le compte membre n a pas pu etre cree');
				}

				$targetUserId = (int)$user->getId();
				$userIdMap[$sourceId] = $targetUserId;
				$isAdmin = !empty($record['organizationMembership']['isAdmin']);
				$isActor = $targetUserId === (int)$actorUserId;
				self::omo1ImportUserMembership($organization, $targetUserId, $isAdmin, $record, $isActor);
				if (!$isActor) {
					$pendingUserIds[$targetUserId] = true;
					$invitationIssue = \dbObject\Invitation::issue(
						(int)$organization->getId(),
						$targetUserId,
						(int)$actorUserId,
						trim((string)$user->get('email'))
					);
					if (!empty($invitationIssue['created']) && isset($invitationIssue['invitation'])) {
						$pendingInvitations[(int)$invitationIssue['invitation']->getId()] = $invitationIssue['invitation'];
					}
				}
				$stats['members'] += 1;
			}

			self::omo1ImportUserMembership($organization, (int)$actorUserId, true);
		}

		protected function omo1ImportConvertAuthorityPropertyValues(array $payload, array $domainRecords, array $authorityIdMap, array $authorityIdsByHolonId, array $holonIdMap, array $propertyIdMap, array $mappedSourceTemplateIds, array &$warnings, $allowSchemaConversion = true)
		{
			if (count($authorityIdMap) === 0 || count($propertyIdMap) === 0) {
				return;
			}

			$sourceDomainPropertyId = 0;
			$propertyDefinitions = $this->getImportedCompactPropertyDefinitions($payload);
			foreach ($propertyDefinitions as $definition) {
				$propertyKey = self::normalizeImportTemplateKey($definition['shortname'] ?? ($definition['name'] ?? ''));
				if (in_array($propertyKey, array('domainesautorite', 'domainautorite', 'authoritydomains'), true)) {
					$sourceDomainPropertyId = (int)($definition['id'] ?? 0);
					break;
				}
			}
			$targetDomainPropertyId = $sourceDomainPropertyId > 0 && isset($propertyIdMap[$sourceDomainPropertyId])
				? (int)$propertyIdMap[$sourceDomainPropertyId]
				: 0;
			if ($targetDomainPropertyId <= 0) {
				return;
			}
			$targetDomainProperty = new \dbObject\Property();
			if (!$targetDomainProperty->load($targetDomainPropertyId)) {
				return;
			}
			$isAuthorityList = \dbObject\PropertyFormat::isListFormat((int)$targetDomainProperty->get('IDpropertyformat'))
				&& \dbObject\Property::normalizeListItemType($targetDomainProperty->get('listitemtype')) === \dbObject\Property::LIST_ITEM_AUTHORITY;
			if (!$isAuthorityList && !$allowSchemaConversion) {
				return;
			}
			if (!$isAuthorityList) {
				$targetDomainProperty->set('IDpropertyformat', \dbObject\PropertyFormat::FORMAT_LIST);
				$targetDomainProperty->set('listitemtype', \dbObject\Property::LIST_ITEM_AUTHORITY);
				$targetDomainProperty->set('listholontypeids', null);
				self::omo1ImportSave($targetDomainProperty, 'La propriete des domaines n a pas pu etre convertie en liste d autorites');
			}

			$normalizeValueKey = static function ($value) {
				$value = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
				$value = preg_replace('/\s+/u', ' ', trim($value));
				return function_exists('mb_strtolower') ? mb_strtolower((string)$value, 'UTF-8') : strtolower((string)$value);
			};
			$authoritySourceIdByValue = array();
			$authoritySourceIdsBySourceHolonId = array();
			$sourceIdsByTargetAuthorityId = array();
			foreach ($domainRecords as $domain) {
				if (!is_array($domain)) {
					continue;
				}
				$sourceDomainId = (int)($domain['sourceId'] ?? 0);
				if ($sourceDomainId <= 0 || !isset($authorityIdMap[$sourceDomainId])) {
					continue;
				}
				$targetAuthorityId = (int)$authorityIdMap[$sourceDomainId];
				if (!isset($sourceIdsByTargetAuthorityId[$targetAuthorityId])) {
					$sourceIdsByTargetAuthorityId[$targetAuthorityId] = array();
				}
				$sourceIdsByTargetAuthorityId[$targetAuthorityId][$sourceDomainId] = $sourceDomainId;
				$sourceHolonIds = array(
					(int)($domain['sourceHolonId'] ?? 0),
					(int)($domain['sourceRoleId'] ?? 0),
				);
				foreach (array_values(array_unique($sourceHolonIds)) as $sourceHolonId) {
					if ($sourceHolonId <= 0) {
						continue;
					}
					if (!isset($authoritySourceIdsBySourceHolonId[$sourceHolonId])) {
						$authoritySourceIdsBySourceHolonId[$sourceHolonId] = array();
					}
					$authoritySourceIdsBySourceHolonId[$sourceHolonId][$sourceDomainId] = $sourceDomainId;
				}
				$label = trim((string)($domain['label'] ?? ($domain['sourceScopeLabel'] ?? '')));
				$description = trim((string)($domain['description'] ?? ($domain['sourceScopeDescription'] ?? '')));
				$candidates = array($label, $description);
				if ($label !== '' && $description !== '') {
					$candidates[] = $label . "\nPolitiques: " . $description;
				}
				foreach ($candidates as $candidate) {
					$key = $normalizeValueKey($candidate);
					if ($key !== '') {
						$authoritySourceIdByValue[$key] = $sourceDomainId;
					}
				}
			}

			$unmatchedCount = 0;
			foreach ($holonIdMap as $sourceHolonId => $targetHolonId) {
				if (!empty($mappedSourceTemplateIds[(int)$sourceHolonId])) {
					continue;
				}
				$targetHolon = new \dbObject\Holon();
				if (!$targetHolon->load((int)$targetHolonId)) {
					continue;
				}

				$resolveAuthorityId = static function ($sourceDomainId) use ($targetHolonId, $authorityIdMap, $authorityIdsByHolonId) {
					$sourceDomainId = (int)$sourceDomainId;
					if (
						$sourceDomainId > 0
						&& isset($authorityIdsByHolonId[(int)$targetHolonId][$sourceDomainId])
					) {
						return (int)$authorityIdsByHolonId[(int)$targetHolonId][$sourceDomainId];
					}
					return $sourceDomainId > 0 && isset($authorityIdMap[$sourceDomainId])
						? (int)$authorityIdMap[$sourceDomainId]
						: 0;
				};
				$sourceAuthorityIds = array();
				foreach ($authoritySourceIdsBySourceHolonId[(int)$sourceHolonId] ?? array() as $sourceDomainId) {
					$resolvedAuthorityId = $resolveAuthorityId($sourceDomainId);
					if ($resolvedAuthorityId > 0) {
						$sourceAuthorityIds[$resolvedAuthorityId] = $resolvedAuthorityId;
					}
				}
				$sourceAuthorityIds = array_values($sourceAuthorityIds);
				$targetDomainHolonProperty = null;
				foreach ($targetHolon->getHolonProperties() as $holonProperty) {
					if ((int)$holonProperty->get('IDproperty') !== $targetDomainPropertyId) {
						continue;
					}
					$targetDomainHolonProperty = $holonProperty;

					$formatId = (int)$targetDomainProperty->get('IDpropertyformat');
					$rawValue = $holonProperty->get('value');
					$items = $formatId === \dbObject\PropertyFormat::FORMAT_HTML_LIST
						? \dbObject\PropertyFormat::getHtmlListParts($rawValue)['items']
						: json_decode((string)$rawValue, true);
					if (!is_array($items)) {
						$items = array();
					}

					$convertedItems = array();
					foreach ($items as $item) {
						$itemId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
						$targetAuthorityId = $resolveAuthorityId($itemId);
						if ($targetAuthorityId <= 0 && isset($sourceIdsByTargetAuthorityId[$itemId])) {
							foreach ($sourceIdsByTargetAuthorityId[$itemId] as $sourceDomainId) {
								$targetAuthorityId = $resolveAuthorityId($sourceDomainId);
								if ($targetAuthorityId > 0) {
									break;
								}
							}
						}
						if ($targetAuthorityId <= 0) {
							$itemValue = is_array($item)
								? ($item['label'] ?? ($item['title'] ?? ($item['value'] ?? ($item['text'] ?? ''))))
								: $item;
							$key = $normalizeValueKey($itemValue);
							$targetAuthorityId = $key !== '' && isset($authoritySourceIdByValue[$key])
								? $resolveAuthorityId($authoritySourceIdByValue[$key])
								: 0;
						}
						if ($targetAuthorityId <= 0) {
							$unmatchedCount += 1;
							continue;
						}
						$convertedItems[] = is_array($item) ? array_merge($item, array('id' => $targetAuthorityId)) : $targetAuthorityId;
					}
					if (count($sourceAuthorityIds) > 0) {
						$convertedAuthorityIds = array();
						foreach ($convertedItems as $convertedItem) {
							$convertedItemId = is_array($convertedItem)
								? (int)($convertedItem['id'] ?? 0)
								: (int)$convertedItem;
							if ($convertedItemId > 0) {
								$convertedAuthorityIds[$convertedItemId] = true;
							}
						}
						foreach ($sourceAuthorityIds as $sourceAuthorityId) {
							if (!isset($convertedAuthorityIds[(int)$sourceAuthorityId])) {
								$convertedItems[] = (int)$sourceAuthorityId;
								$convertedAuthorityIds[(int)$sourceAuthorityId] = true;
							}
						}
					}

					if ($formatId === \dbObject\PropertyFormat::FORMAT_HTML_LIST) {
						$htmlList = \dbObject\PropertyFormat::getHtmlListParts($rawValue);
						$htmlList['items'] = $convertedItems;
						$holonProperty->set('value', \dbObject\PropertyFormat::normalizeValueForStorage($formatId, $htmlList));
					} else {
						$holonProperty->set('value', json_encode($convertedItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
					}
					$holonProperty->set('active', true);
					self::omo1ImportSave($holonProperty, 'Les domaines d un holon n ont pas pu etre rattaches aux autorites');
				}

				if ($targetDomainHolonProperty === null && count($sourceAuthorityIds) > 0) {
					$targetDomainHolonProperty = new \dbObject\HolonProperty();
					$targetDomainHolonProperty->set('IDholon', (int)$targetHolon->getId());
					$targetDomainHolonProperty->set('IDproperty', $targetDomainPropertyId);
					$targetDomainHolonProperty->set('value', json_encode($sourceAuthorityIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
					$targetDomainHolonProperty->set('position', (int)$targetDomainProperty->get('position'));
					$targetDomainHolonProperty->set('mandatory', false);
					$targetDomainHolonProperty->set('locked', false);
					$targetDomainHolonProperty->set('active', true);
					self::omo1ImportSave($targetDomainHolonProperty, 'La liste des domaines d un holon n a pas pu etre creee');
				}
			}

			if ($unmatchedCount > 0) {
				$warnings[] = $unmatchedCount . ' domaine(s) OMO 1 n ont pas pu etre reconnus dans les proprietes d autorite.';
			}
		}

		protected static function omo1ImportRuleDomains(array $payload, array $ruleRecords)
		{
			$modules = isset($payload['modules']) && is_array($payload['modules']) ? $payload['modules'] : array();
			$rulesModule = isset($modules['rules']) && is_array($modules['rules']) ? $modules['rules'] : array();
			$domains = isset($rulesModule['domains']) && is_array($rulesModule['domains']) ? $rulesModule['domains'] : array();
			$domainsById = array();

			foreach ($domains as $domain) {
				if (!is_array($domain) || (int)($domain['sourceId'] ?? 0) <= 0) {
					continue;
				}
				$domainsById[(int)$domain['sourceId']] = $domain;
			}

			// Compatibilite avec les anciens exports qui ne contenaient que les regles.
			foreach ($ruleRecords as $record) {
				$sourceId = (int)($record['sourceScopeId'] ?? 0);
				if ($sourceId <= 0 || isset($domainsById[$sourceId])) {
					continue;
				}

				$domainsById[$sourceId] = array(
					'sourceId' => $sourceId,
					'label' => $record['sourceScopeLabel'] ?? '',
					'description' => $record['sourceScopeDescription'] ?? '',
					'sourceRoleId' => $record['sourceScopeRoleId'] ?? 0,
					'sourceHolonId' => $record['sourceScopeHolonId'] ?? 0,
					'sourceParentScopeId' => $record['sourceParentScopeId'] ?? 0,
				);
			}

			return array_values($domainsById);
		}

		protected static function omo1ImportAuthorityMatchKey($value)
		{
			$value = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$value = preg_replace('/\s+/u', ' ', trim($value));
			return function_exists('mb_strtolower') ? mb_strtolower((string)$value, 'UTF-8') : strtolower((string)$value);
		}

		protected static function omo1ImportHolonUsesAuthorityDomains(\dbObject\Holon $holon)
		{
			foreach ($holon->getHolonEditorPropertyDefinitions() as $definition) {
				if (
					\dbObject\PropertyFormat::isListFormat((int)($definition['formatId'] ?? 0))
					&& \dbObject\Property::normalizeListItemType($definition['listItemType'] ?? null) === \dbObject\Property::LIST_ITEM_AUTHORITY
				) {
					return true;
				}
			}

			return false;
		}

		protected static function omo1ImportFindMatchingAuthority(\dbObject\Holon $holon, $label, $description)
		{
			$labelKey = self::omo1ImportAuthorityMatchKey($label);
			$descriptionKey = self::omo1ImportAuthorityMatchKey($description);
			$authorities = new \dbObject\ArrayAuthority();
			$authorities->loadForHolon((int)$holon->getId());

			foreach ($authorities as $authority) {
				if (!($authority instanceof \dbObject\Authority)) {
					continue;
				}
				if ($labelKey !== '' && self::omo1ImportAuthorityMatchKey($authority->get('label')) === $labelKey) {
					return $authority;
				}
				if ($descriptionKey !== '' && self::omo1ImportAuthorityMatchKey($authority->get('description')) === $descriptionKey) {
					return $authority;
				}
			}

			return null;
		}

		protected static function omo1ImportCreateAuthority(\dbObject\Holon $holon, array $record, $isLocal, array &$stats)
		{
			$sourceId = (int)($record['sourceId'] ?? 0);
			$label = self::omo1ImportLimitText($record['label'] ?? ($record['sourceScopeLabel'] ?? ''), 255);
			if ($label === '') {
				$label = 'Domaine OMO 1 #' . $sourceId;
			}
			$description = trim((string)($record['description'] ?? ($record['sourceScopeDescription'] ?? '')));
			$authority = new \dbObject\Authority();
			$authority->set('IDholon', (int)$holon->getId());
			$authority->set('IDauthority_parent', null);
			$authority->set('label', $label);
			$authority->set('description', $description !== '' ? $description : null);
			$authority->set('is_local', $isLocal);
			$authority->set('is_shell', false);
			self::omo1ImportSave($authority, 'Le domaine OMO 1 n a pas pu etre converti en autorite');
			$stats['authorities'] += 1;

			return $authority;
		}

		protected static function omo1ImportBuildAuthorityIdsByHolon(\dbObject\Organization $organization, array $authorityIdMap)
		{
			$sourceIdsByAuthorityId = array();
			foreach ($authorityIdMap as $sourceId => $authorityId) {
				$sourceId = (int)$sourceId;
				$authorityId = (int)$authorityId;
				if ($sourceId <= 0 || $authorityId <= 0) {
					continue;
				}
				if (!isset($sourceIdsByAuthorityId[$authorityId])) {
					$sourceIdsByAuthorityId[$authorityId] = array();
				}
				$sourceIdsByAuthorityId[$authorityId][$sourceId] = $sourceId;
			}

			$authorityIdsByHolonId = array();
			foreach ($organization->getOrganizationHolonIds() as $holonId) {
				$authorities = new \dbObject\ArrayAuthority();
				$authorities->loadForHolon((int)$holonId);
				foreach ($authorities as $authority) {
					if (!($authority instanceof \dbObject\Authority)) {
						continue;
					}
					$authorityId = (int)$authority->getId();
					$templateAuthorityId = (int)$authority->get('IDauthority_template');
					$sourceIds = array();
					if (isset($sourceIdsByAuthorityId[$authorityId])) {
						$sourceIds += $sourceIdsByAuthorityId[$authorityId];
					}
					if ($templateAuthorityId > 0 && isset($sourceIdsByAuthorityId[$templateAuthorityId])) {
						$sourceIds += $sourceIdsByAuthorityId[$templateAuthorityId];
					}
					foreach ($sourceIds as $sourceId) {
						$authorityIdsByHolonId[(int)$holonId][(int)$sourceId] = $authorityId;
					}
				}
			}

			return $authorityIdsByHolonId;
		}

		protected static function omo1ImportAuthorities(\dbObject\Organization $organization, array $records, array $holonIdMap, array &$stats, array &$warnings, array $options = array())
		{
			$hasAppliedOrganizationModel = !empty($options['hasAppliedOrganizationModel']);
			$authorityIdMap = array();
			$createdAuthoritiesBySourceId = array();
			$entriesBySourceId = array();
			$rootHolon = $organization->getStructuralRootHolon();
			$rootHolonId = $rootHolon instanceof \dbObject\Holon ? (int)$rootHolon->getId() : 0;

			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}
				$sourceId = (int)($record['sourceId'] ?? 0);
				if ($sourceId <= 0 || isset($entriesBySourceId[$sourceId])) {
					continue;
				}
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$sourceRoleId = (int)($record['sourceRoleId'] ?? 0);
				$targetHolonId = $sourceHolonId > 0 && isset($holonIdMap[$sourceHolonId])
					? (int)$holonIdMap[$sourceHolonId]
					: (isset($holonIdMap[$sourceRoleId]) ? (int)$holonIdMap[$sourceRoleId] : 0);
				$targetHolon = new \dbObject\Holon();
				if ($targetHolonId <= 0 || !$targetHolon->load($targetHolonId)) {
					$warnings[] = 'Le domaine OMO 1 ' . $sourceId . ' n a pas pu etre transforme car son holon est absent.';
					continue;
				}
				$entriesBySourceId[$sourceId] = array(
					'record' => $record,
					'holon' => $targetHolon,
					'isTemplate' => $rootHolonId > 0 && $targetHolon->isTemplateNode($rootHolonId),
				);
			}

			if ($hasAppliedOrganizationModel) {
				$textDomainCount = 0;
				$unmatchedDomainCount = 0;
				$templatesToSync = array();
				foreach ($entriesBySourceId as $sourceId => $entry) {
					$holon = $entry['holon'];
					if (!self::omo1ImportHolonUsesAuthorityDomains($holon)) {
						$textDomainCount += 1;
						continue;
					}
					$record = $entry['record'];
					$authority = self::omo1ImportFindMatchingAuthority(
						$holon,
						$record['label'] ?? ($record['sourceScopeLabel'] ?? ''),
						$record['description'] ?? ($record['sourceScopeDescription'] ?? '')
					);
					if ($authority instanceof \dbObject\Authority) {
						$authorityIdMap[$sourceId] = (int)$authority->getId();
						if (!empty($entry['isTemplate'])) {
							$templatesToSync[(int)$holon->getId()] = $holon;
						}
					} else {
						$unmatchedDomainCount += 1;
					}
				}
				foreach ($templatesToSync as $template) {
					$organization->normalizeTemplateLocalAuthorities($template);
					$organization->syncTemplateAuthorityInstances($template);
				}
				if ($textDomainCount > 0) {
					$warnings[] = $textDomainCount . ' domaine(s) OMO 1 correspondent a un format texte du modele : leurs regles restent rattachees aux holons.';
				}
				if ($unmatchedDomainCount > 0) {
					$warnings[] = $unmatchedDomainCount . ' domaine(s) OMO 1 n ont pas pu etre associes sans ambiguite a une autorite du modele : leurs regles restent rattachees aux holons.';
				}
				return array(
					'authorityIdMap' => $authorityIdMap,
					'authorityIdsByHolonId' => self::omo1ImportBuildAuthorityIdsByHolon($organization, $authorityIdMap),
				);
			}

			$templatesToSync = array();
			foreach ($entriesBySourceId as $sourceId => $entry) {
				if (empty($entry['isTemplate'])) {
					continue;
				}
				$record = $entry['record'];
				$authority = self::omo1ImportFindMatchingAuthority(
					$entry['holon'],
					$record['label'] ?? ($record['sourceScopeLabel'] ?? ''),
					$record['description'] ?? ($record['sourceScopeDescription'] ?? '')
				);
				if (!($authority instanceof \dbObject\Authority)) {
					$authority = self::omo1ImportCreateAuthority($entry['holon'], $record, true, $stats);
					$createdAuthoritiesBySourceId[$sourceId] = array('authority' => $authority, 'entry' => $entry);
				}
				$authorityIdMap[$sourceId] = (int)$authority->getId();
				$templatesToSync[(int)$entry['holon']->getId()] = $entry['holon'];
			}
			foreach ($templatesToSync as $template) {
				$organization->normalizeTemplateLocalAuthorities($template);
				$organization->syncTemplateAuthorityInstances($template);
			}

			foreach ($entriesBySourceId as $sourceId => $entry) {
				if (isset($authorityIdMap[$sourceId])) {
					continue;
				}
				$record = $entry['record'];
				$authority = self::omo1ImportFindMatchingAuthority(
					$entry['holon'],
					$record['label'] ?? ($record['sourceScopeLabel'] ?? ''),
					$record['description'] ?? ($record['sourceScopeDescription'] ?? '')
				);
				if (!($authority instanceof \dbObject\Authority)) {
					$authority = self::omo1ImportCreateAuthority($entry['holon'], $record, false, $stats);
					$createdAuthoritiesBySourceId[$sourceId] = array('authority' => $authority, 'entry' => $entry);
				}
				$authorityIdMap[$sourceId] = (int)$authority->getId();
			}

			$manualParentCount = 0;
			foreach ($createdAuthoritiesBySourceId as $sourceId => $createdEntry) {
				$authority = $createdEntry['authority'];
				$entry = $createdEntry['entry'];
				$record = $entry['record'];
				if (!empty($entry['isTemplate']) || (int)$authority->get('IDholon') === $rootHolonId) {
					continue;
				}
				$parentAuthorityId = 0;
				$sourceParentScopeId = (int)($record['sourceParentScopeId'] ?? 0);
				if ($sourceParentScopeId > 0 && isset($authorityIdMap[$sourceParentScopeId])) {
					$parentAuthorityId = (int)$authorityIdMap[$sourceParentScopeId];
				}
				if ($parentAuthorityId <= 0) {
					$parentHolon = $entry['holon']->getAuthorityParentHolon();
					if ($parentHolon instanceof \dbObject\Holon) {
						$parentAuthorities = new \dbObject\ArrayAuthority();
						$parentAuthorities->loadForHolon((int)$parentHolon->getId());
						if (count($parentAuthorities) === 1) {
							$parentAuthority = $parentAuthorities[0] ?? null;
							if ($parentAuthority instanceof \dbObject\Authority) {
								$parentAuthorityId = (int)$parentAuthority->getId();
							}
						}
					}
				}
				if ($parentAuthorityId > 0) {
					$authority->set('IDauthority_parent', $parentAuthorityId);
					self::omo1ImportSave($authority, 'Le rattachement parent de l autorite OMO 1 n a pas pu etre cree');
					continue;
				}
				$description = trim((string)$authority->get('description'));
				$authority->set('description', \dbObject\Authority::IMPORT_NEEDS_PARENT_MARKER . ($description !== '' ? "\n\n" . $description : ''));
				self::omo1ImportSave($authority, 'Le marquage de l autorite OMO 1 a rattacher n a pas pu etre enregistre');
				$manualParentCount += 1;
			}

			if ($manualParentCount > 0) {
				$warnings[] = $manualParentCount . ' autorite(s) OMO 1 restent sans parent et sont signalees en rouge pour rattachement manuel.';
			}

			return array(
				'authorityIdMap' => $authorityIdMap,
				'authorityIdsByHolonId' => self::omo1ImportBuildAuthorityIdsByHolon($organization, $authorityIdMap),
			);
		}

		protected static function omo1ImportRules(\dbObject\Organization $organization, array $records, $actorUserId, array $userIdMap, array $holonIdMap, array $authorityIdMap, array $authorityIdsByHolonId, array &$stats, array &$warnings)
		{
			$validityWarningAdded = false;
			foreach ($records as $record) {
				if (!is_array($record) || (int)($record['sourceId'] ?? 0) <= 0) {
					continue;
				}

				$sourceScopeRoleId = (int)($record['sourceScopeRoleId'] ?? 0);
				$sourceRoleId = $sourceScopeRoleId > 0
					? $sourceScopeRoleId
					: (int)($record['sourceRoleId'] ?? 0);
				$sourceHolonId = $sourceScopeRoleId > 0
					? (int)($record['sourceScopeHolonId'] ?? 0)
					: (int)($record['sourceHolonId'] ?? 0);
				$targetHolonId = $sourceHolonId > 0 && isset($holonIdMap[$sourceHolonId])
					? (int)$holonIdMap[$sourceHolonId]
					: (isset($holonIdMap[$sourceRoleId]) ? (int)$holonIdMap[$sourceRoleId] : 0);
				if ($targetHolonId <= 0) {
					$warnings[] = 'La regle ' . (int)$record['sourceId'] . ' n a pas pu etre rattachee a un holon importe.';
					continue;
				}

				$title = self::omo1ImportLimitText($record['title'] ?? '', 255);
				if ($title === '') {
					$title = 'Regle OMO 1 #' . (int)$record['sourceId'];
				}
				$description = \dbObject\Rule::sanitizeContentHtml((string)($record['description'] ?? ''));
				if (trim(strip_tags($description)) === '') {
					$description = '<p>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</p>';
				}
				$intention = \dbObject\Rule::sanitizeContentHtml((string)($record['intention'] ?? ''));

				$sourceScopeId = (int)($record['sourceScopeId'] ?? 0);
				$targetAuthorityId = $sourceScopeId > 0 && isset($authorityIdsByHolonId[$targetHolonId][$sourceScopeId])
					? (int)$authorityIdsByHolonId[$targetHolonId][$sourceScopeId]
					: ($sourceScopeId > 0 && isset($authorityIdMap[$sourceScopeId]) ? (int)$authorityIdMap[$sourceScopeId] : 0);
				if ($sourceScopeId > 0 && $targetAuthorityId <= 0) {
					$warnings[] = 'La regle ' . (int)$record['sourceId'] . ' conserve un rattachement local car son domaine OMO 1 n a pas pu etre converti en autorite.';
				}

				$rule = new \dbObject\Rule();
				$rule->set('IDholon', $targetAuthorityId > 0 ? null : $targetHolonId);
				$rule->set('IDauthority', $targetAuthorityId > 0 ? $targetAuthorityId : null);
				$rule->set('title', $title);
				$rule->set('intention', $intention !== '' ? $intention : null);
				$rule->set('description', $description);
				$rule->set('scope', \dbObject\Rule::SCOPE_LOCAL);
				$rule->set('review_date', (new \DateTimeImmutable('today'))->format('Y-m-d'));
				$rule->set('expiration_date', '9999-12-31');

				$createdAt = self::omo1ImportDate($record['createdAt'] ?? null);
				$updatedAt = self::omo1ImportDate($record['updatedAt'] ?? null);
				$createdAt = $createdAt ?: $updatedAt ?: new \DateTimeImmutable();
				if ($createdAt) {
					$rule->set('created_at', $createdAt);
				}
				$sourceCreatorId = (int)($record['sourceCreatorId'] ?? 0);
				if ($sourceCreatorId > 0 && isset($userIdMap[$sourceCreatorId])) {
					$rule->set('IDuser_creation', (int)$userIdMap[$sourceCreatorId]);
				}
				$updatedAt = $updatedAt ?: $createdAt;
				if ($updatedAt) {
					$rule->set('updated_at', $updatedAt);
				}
				$sourceModifierId = (int)($record['sourceModifierId'] ?? 0);
				if ($sourceModifierId > 0 && isset($userIdMap[$sourceModifierId])) {
					$rule->set('IDuser_modification', (int)$userIdMap[$sourceModifierId]);
				}
				$rule->preserveImportedAuditMetadata();
				self::omo1ImportSave($rule, 'Une regle n a pas pu etre importee');
				$stats['rules'] += 1;

				if (!$validityWarningAdded) {
					$warnings[] = 'Les regles OMO 1 n ayant pas de dates de validite, elles ont ete importees avec une revision a la date du jour et une echeance lointaine.';
					$validityWarningAdded = true;
				}
			}
		}

		protected static function omo1ImportRoleAssignments(array $records, array $userIdMap, array $holonIdMap, array $pendingUserIds, array &$stats)
		{
			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}
				$sourceUserId = (int)($record['sourceId'] ?? 0);
				$targetUserId = isset($userIdMap[$sourceUserId]) ? (int)$userIdMap[$sourceUserId] : 0;
				if ($targetUserId <= 0 || empty($record['roleAssignments']) || !is_array($record['roleAssignments'])) {
					continue;
				}

				foreach ($record['roleAssignments'] as $assignment) {
					$sourceHolonId = (int)($assignment['sourceHolonId'] ?? 0);
					$targetHolonId = isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : 0;
					if ($targetHolonId <= 0) {
						continue;
					}

					$link = new \dbObject\UserHolon();
					if (!$link->load(array(array('IDuser', $targetUserId), array('IDholon', $targetHolonId)))) {
						$link->set('IDuser', $targetUserId);
						$link->set('IDholon', $targetHolonId);
					}
					$link->set('active', !isset($pendingUserIds[$targetUserId]));
					self::omo1ImportSave($link, 'Une attribution de role n a pas pu etre creee');
					$stats['roleAssignments'] += 1;
				}
			}
		}

		protected static function omo1ImportDocuments(\dbObject\Organization $organization, array $records, $actorUserId, array $userIdMap, array $holonIdMap, array &$documentIdMap, array &$documentProjectSourceMap, array &$stats, array &$warnings)
		{
			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}
				$sourceId = (int)($record['sourceId'] ?? 0);
				if ($sourceId <= 0) {
					continue;
				}
				$title = self::omo1ImportLimitText($record['title'] ?? '', 100);
				if ($title === '') {
					$title = 'Document OMO 1 #' . $sourceId;
				}
				$sourceUserId = (int)($record['sourceUserId'] ?? 0);
				$targetUserId = isset($userIdMap[$sourceUserId]) ? (int)$userIdMap[$sourceUserId] : (int)$actorUserId;
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$sourceProjectId = (int)($record['sourceProjectId'] ?? 0);
				$targetHolonId = $sourceProjectId > 0
					? null
					: (isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : null);
				$content = (string)($record['content'] ?? '');
				$legacyFilePath = trim((string)($record['legacyFilePath'] ?? ''));
				$isLegacyUploadedFile = !empty($record['fileTransferRequired']) || $legacyFilePath !== '';
				$description = trim((string)($record['description'] ?? ''));
				$legacyFilename = self::omo1ImportLimitText($record['filename'] ?? '', 255);
				if ($legacyFilename === '' && $legacyFilePath !== '') {
					$legacyFilename = self::omo1ImportLimitText(basename($legacyFilePath), 255);
				}
				if ($isLegacyUploadedFile) {
					$description .= ($description !== '' ? "\n\n" : '')
						. 'Fichier OMO 1 a transferer manuellement'
						. ($legacyFilePath !== '' ? ' : ' . $legacyFilePath : '.');
					$warnings[] = 'Les fichiers joints OMO 1 ne sont pas copies automatiquement.';
				}

				$document = new \dbObject\Document();
				$document->set('title', $title);
				$document->set('description', $description !== '' ? $description : null);
				$document->set('content', $content !== '' ? $content : null);
				$document->set('documenttype', $isLegacyUploadedFile
					? \dbObject\Document::TYPE_UPLOADED_FILE
					: (trim((string)($record['externalUrl'] ?? '')) !== '' ? \dbObject\Document::TYPE_EXTERNAL_LINK : \dbObject\Document::TYPE_HTML));
				$document->set('externalurl', $record['externalUrl'] ?? null);
				if ($isLegacyUploadedFile && $legacyFilename !== '') {
					$document->set('storedfilename', $legacyFilename);
				}
				$document->set('IDorganization', (int)$organization->getId());
				$document->set('IDholon', $targetHolonId);
				$document->set('IDuser', $targetUserId);
				$document->set('IDusercreation', $targetUserId);
				$createdAt = self::omo1ImportDate($record['createdAt'] ?? null);
				if ($createdAt) {
					$document->set('datecreation', $createdAt);
				}
				$updatedAt = self::omo1ImportDate($record['updatedAt'] ?? null);
				if ($updatedAt) {
					$document->set('datemodification', $updatedAt);
				}
				$document->set('active', true);
				self::omo1ImportSave($document, 'Un document n a pas pu etre cree');
				self::omo1ImportSaveDocumentEditVisibility($document, $organization, $targetHolonId);
				$documentIdMap[$sourceId] = (int)$document->getId();
				$documentProjectSourceMap[$sourceId] = $sourceProjectId;
				$stats['documents'] += 1;
			}
		}

		protected static function omo1ImportProjects(\dbObject\Organization $organization, array $records, $actorUserId, array $userIdMap, array $holonIdMap, array $documentIdMap, array $documentProjectSourceMap, array &$projectIdMap, array &$stats)
		{
			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}
				$sourceId = (int)($record['sourceId'] ?? 0);
				if ($sourceId <= 0) {
					continue;
				}
				$sourceUserId = (int)($record['sourceUserId'] ?? 0);
				$targetUserId = isset($userIdMap[$sourceUserId]) ? (int)$userIdMap[$sourceUserId] : (int)$actorUserId;
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$targetHolonId = isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : null;
				$title = self::omo1ImportLimitText($record['title'] ?? '', 255);
				$project = new \dbObject\Project();
				$project->set('IDorganization', (int)$organization->getId());
				$project->set('IDholon', $targetHolonId);
				$project->set('IDuser', $targetUserId);
				$project->set('title', $title !== '' ? $title : 'Projet OMO 1 #' . $sourceId);
				$project->set('description', $record['description'] ?? null);
				$project->set('status', array_key_exists('status', $record)
					? \dbObject\Project::normalizeStatus($record['status'])
					: self::omo1ImportProjectStatus($record['legacyStatusId'] ?? 0));
				$project->set('planned_start_date', self::omo1ImportDate($record['plannedStartAt'] ?? null));
				$project->set('planned_end_date', self::omo1ImportDate($record['plannedEndAt'] ?? null));
				$project->set('priority', \dbObject\Project::normalizeLevel($record['priority'] ?? null));
				$project->set('importance', \dbObject\Project::normalizeLevel($record['importance'] ?? null));
				$project->set('calculated_importance', (float)($record['calculatedImportance'] ?? 0));
				$project->set('capture_mode', \dbObject\Project::normalizeCaptureMode($record['captureMode'] ?? null));
				$project->set('project_size', \dbObject\Project::normalizeSize($record['projectSize'] ?? \dbObject\Project::SIZE_M));
				$project->set('project_kind', \dbObject\Project::KIND_STANDARD);
				$createdAt = self::omo1ImportDate($record['createdAt'] ?? null);
				if ($createdAt) {
					$project->set('created_at', $createdAt);
				}
				$project->set('active', array_key_exists('active', $record)
					? (bool)$record['active']
					: !self::omo1ImportProjectIsArchived($record['legacyStatusId'] ?? 0));
				self::omo1ImportSave($project, 'Un projet n a pas pu etre cree');
				$projectIdMap[$sourceId] = (int)$project->getId();
				$stats['projects'] += 1;
			}

			foreach ($records as $record) {
				$sourceId = (int)($record['sourceId'] ?? 0);
				$targetProjectId = isset($projectIdMap[$sourceId]) ? (int)$projectIdMap[$sourceId] : 0;
				if ($targetProjectId <= 0) {
					continue;
				}
				$project = new \dbObject\Project();
				if (!$project->load($targetProjectId)) {
					continue;
				}
				$sourceParentId = (int)($record['sourceParentProjectId'] ?? 0);
				$project->set('IDproject_parent', isset($projectIdMap[$sourceParentId]) ? (int)$projectIdMap[$sourceParentId] : null);
				self::omo1ImportSave($project, 'La hierarchie des projets n a pas pu etre recreee');
			}

		}

		protected static function omo1ImportTasks(\dbObject\Organization $organization, array $records, $actorUserId, array $userIdMap, array $holonIdMap, array $projectIdMap, array &$taskIdMap, array &$stats)
		{
			foreach ($records as $record) {
				if (!is_array($record) || (int)($record['sourceId'] ?? 0) <= 0) {
					continue;
				}
				$sourceUserId = (int)($record['sourceProposerUserId'] ?? 0);
				$targetUserId = isset($userIdMap[$sourceUserId]) ? (int)$userIdMap[$sourceUserId] : (int)$actorUserId;
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$targetHolonId = isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : null;
				$sourceProjectId = (int)($record['sourceParentProjectId'] ?? ($record['sourceProjectId'] ?? 0));
				$title = self::omo1ImportLimitText($record['title'] ?? '', 255);
				$completedAt = $record['completedAt'] ?? null;
				if (!self::omo1ImportDate($completedAt) && !empty($record['checks']) && is_array($record['checks'])) {
					foreach ($record['checks'] as $check) {
						if (is_array($check) && self::omo1ImportDate($check['checkedAt'] ?? null)) {
							$completedAt = $check['checkedAt'];
							break;
						}
					}
				}
				$task = new \dbObject\Project();
				$task->set('IDorganization', (int)$organization->getId());
				$task->set('IDholon', $targetHolonId);
				$task->set('IDuser', $targetUserId);
				$task->set('IDproject_parent', isset($projectIdMap[$sourceProjectId]) ? (int)$projectIdMap[$sourceProjectId] : null);
				$task->set('title', $title !== '' ? $title : 'Tache OMO 1 #' . (int)$record['sourceId']);
				$task->set('description', $record['description'] ?? null);
				$task->set('status', array_key_exists('status', $record)
					? \dbObject\Project::normalizeStatus($record['status'])
					: self::omo1ImportProjectStatus(0, $completedAt, $record['deletedAt'] ?? null));
				$task->set('planned_start_date', self::omo1ImportDate($record['plannedStartAt'] ?? null));
				$task->set('planned_end_date', self::omo1ImportDate($record['plannedEndAt'] ?? null));
				$task->set('priority', \dbObject\Project::normalizeLevel($record['priority'] ?? null));
				$task->set('importance', \dbObject\Project::normalizeLevel($record['importance'] ?? null));
				$task->set('calculated_importance', (float)($record['calculatedImportance'] ?? 0));
				$task->set('capture_mode', \dbObject\Project::normalizeCaptureMode($record['captureMode'] ?? null));
				$task->set('project_size', \dbObject\Project::normalizeSize($record['projectSize'] ?? \dbObject\Project::SIZE_S));
				$task->set('project_kind', \dbObject\Project::KIND_STANDARD);
				$createdAt = self::omo1ImportDate($record['createdAt'] ?? null);
				if ($createdAt) {
					$task->set('created_at', $createdAt);
				}
				$task->set('active', array_key_exists('active', $record) ? (bool)$record['active'] : true);
				self::omo1ImportSave($task, 'Une tache n a pas pu etre importee');
				$taskIdMap[(int)$record['sourceId']] = (int)$task->getId();
				$stats['tasks'] += 1;
			}

			foreach ($records as $record) {
				$sourceId = (int)($record['sourceId'] ?? 0);
				$targetTaskId = isset($taskIdMap[$sourceId]) ? (int)$taskIdMap[$sourceId] : 0;
				$sourceParentId = (int)($record['sourceParentProjectId'] ?? ($record['sourceProjectId'] ?? 0));
				if ($targetTaskId <= 0 || $sourceParentId <= 0 || !isset($taskIdMap[$sourceParentId])) {
					continue;
				}
				$task = new \dbObject\Project();
				if (!$task->load($targetTaskId)) {
					continue;
				}
				$task->set('IDproject_parent', (int)$taskIdMap[$sourceParentId]);
				self::omo1ImportSave($task, 'La hierarchie des sous-projets n a pas pu etre recreee');
			}
		}

		protected static function omo1ImportLinkDocumentsToProjects(array $documentIdMap, array $documentProjectSourceMap, array $projectIdMap, array $taskIdMap)
		{
			foreach ($documentIdMap as $sourceDocumentId => $targetDocumentId) {
				$sourceProjectId = (int)($documentProjectSourceMap[$sourceDocumentId] ?? 0);
				$targetProjectId = (int)($projectIdMap[$sourceProjectId] ?? ($taskIdMap[$sourceProjectId] ?? 0));
				if ($targetProjectId <= 0 || (int)$targetDocumentId <= 0) {
					continue;
				}
				$link = new \dbObject\ProjectDocument();
				if ($link->load([['IDproject', $targetProjectId], ['IDdocument', (int)$targetDocumentId]])) {
					continue;
				}
				$link->set('IDproject', $targetProjectId);
				$link->set('IDdocument', (int)$targetDocumentId);
				self::omo1ImportSave($link, 'Un lien projet-document n a pas pu etre cree');
			}
		}

		protected static function omo1ImportChecklistRecurrence(array $record)
		{
			$frequency = \dbObject\RecurrenceSchedule::normalizeFrequency($record['frequency'] ?? null);
			$schedule = \dbObject\RecurrenceSchedule::normalizeSchedule($frequency, $record['schedule'] ?? null);
			return array($frequency, $schedule);
		}

		protected static function omo1ImportChecklistTemplateProject(\dbObject\Organization $organization, $holonId, $userId, $title, $description, $isActive)
		{
			$template = new \dbObject\Project();
			$template->set('IDorganization', (int)$organization->getId());
			$template->set('IDholon', (int)$holonId > 0 ? (int)$holonId : null);
			$template->set('IDuser', (int)$userId > 0 ? (int)$userId : null);
			$template->set('title', self::omo1ImportLimitText($title, 255));
			$template->set('description', $description ?: null);
			$template->set('status', \dbObject\Project::STATUS_READY);
			$template->set('project_size', \dbObject\Project::SIZE_S);
			$template->set('project_kind', \dbObject\Project::KIND_CHECKLIST_TEMPLATE);
			$template->set('active', (bool)$isActive);
			self::omo1ImportSave($template, 'Un projet modele de checklist n a pas pu etre cree');
			return $template;
		}

		protected static function omo1ImportChecklists(\dbObject\Organization $organization, array $records, $actorUserId, array $userIdMap, array $holonIdMap, array &$stats)
		{
			foreach ($records as $recordIndex => $record) {
				if (!is_array($record)) {
					continue;
				}
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$targetHolonId = isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : 0;
				if ($targetHolonId <= 0) {
					continue;
				}
				$triggerData = isset($record['trigger']) && is_array($record['trigger']) ? $record['trigger'] : array();
				$triggerType = \dbObject\ChecklistTrigger::normalizeTriggerType($triggerData['type'] ?? \dbObject\ChecklistTrigger::TYPE_CONTAINER);
				$isStandalone = ($record['kind'] ?? '') === 'standalone' || $triggerType === \dbObject\ChecklistTrigger::TYPE_MANUAL;
				$sourceUserId = (int)($record['sourceUserId'] ?? 0);
				$targetUserId = isset($userIdMap[$sourceUserId]) ? (int)$userIdMap[$sourceUserId] : (int)$actorUserId;
				$title = self::omo1ImportLimitText($record['title'] ?? '', 255);
				if ($title === '') {
					$title = $isStandalone ? 'Checklist OMO 1 #' . (int)($record['sourceId'] ?? ($recordIndex + 1)) : 'Checklists OMO 1';
				}
				$rootTemplate = self::omo1ImportChecklistTemplateProject(
					$organization,
					$targetHolonId,
					$targetUserId,
					$title,
					$record['description'] ?? null,
					!array_key_exists('active', $record) || (bool)$record['active']
				);
				$checklist = new \dbObject\Checklist();
				$checklist->set('IDorganization', (int)$organization->getId());
				$checklist->set('IDproject_template_root', (int)$rootTemplate->getId());
				$checklist->set('IDchecklist_previous', null);
				$checklist->set('IDdocument', null);
				$checklist->set('status', \dbObject\Checklist::normalizeStatus($record['status'] ?? \dbObject\Checklist::STATUS_PUBLISHED));
				$checklist->set('revision_note', $record['revisionNote'] ?? null);
				$checklist->set('active', !array_key_exists('active', $record) || (bool)$record['active']);
				self::omo1ImportSave($checklist, 'Une checklist n a pas pu etre creee');

				$trigger = new \dbObject\ChecklistTrigger();
				$trigger->set('IDchecklist', (int)$checklist->getId());
				$trigger->set('stable_key', self::omo1ImportLimitText($triggerData['stableKey'] ?? 'primary', 64));
				$trigger->set('trigger_type', $isStandalone ? \dbObject\ChecklistTrigger::TYPE_MANUAL : \dbObject\ChecklistTrigger::TYPE_CONTAINER);
				$trigger->set('overlap_policy', \dbObject\ChecklistTrigger::normalizeOverlapPolicy($triggerData['overlapPolicy'] ?? \dbObject\ChecklistTrigger::OVERLAP_REUSE_OPEN));
				$trigger->set('enabled', $isStandalone && (!array_key_exists('enabled', $triggerData) || (bool)$triggerData['enabled']));
				self::omo1ImportSave($trigger, 'Le declencheur de checklist n a pas pu etre cree');
				$stats['checklists'] += 1;

				if ($isStandalone) {
					continue;
				}
				$items = isset($record['items']) && is_array($record['items']) ? $record['items'] : array();
				foreach ($items as $itemIndex => $itemRecord) {
					if (!is_array($itemRecord)) {
						continue;
					}
					$itemSourceUserId = (int)($itemRecord['sourceUserId'] ?? 0);
					$itemUserId = isset($userIdMap[$itemSourceUserId]) ? (int)$userIdMap[$itemSourceUserId] : (int)$actorUserId;
					$itemTitle = self::omo1ImportLimitText($itemRecord['title'] ?? '', 255);
					if ($itemTitle === '') {
						$itemTitle = 'Checklist OMO 1 #' . (int)($itemRecord['sourceId'] ?? ($itemIndex + 1));
					}
					$itemActive = !array_key_exists('active', $itemRecord) || (bool)$itemRecord['active'];
					$itemTemplate = self::omo1ImportChecklistTemplateProject(
						$organization,
						$targetHolonId,
						$itemUserId,
						$itemTitle,
						$itemRecord['description'] ?? null,
						$itemActive
					);
					$item = new \dbObject\ChecklistItem();
					$item->set('IDchecklist', (int)$checklist->getId());
					$item->set('IDproject_template', (int)$itemTemplate->getId());
					$item->set('stable_key', self::omo1ImportLimitText($itemRecord['stableKey'] ?? ('item_' . ($itemIndex + 1)), 64));
					$activationData = isset($itemRecord['activation']) && is_array($itemRecord['activation']) ? $itemRecord['activation'] : array();
					$activationType = \dbObject\ChecklistItem::normalizeActivationType($activationData['type'] ?? \dbObject\ChecklistItem::ACTIVATION_IMMEDIATE);
					$item->set('activation_type', $activationType);
					$item->set('delay_value', $activationType === \dbObject\ChecklistItem::ACTIVATION_AFTER_START ? (int)($activationData['delayValue'] ?? 0) : 0);
					$item->set('delay_unit', $activationType === \dbObject\ChecklistItem::ACTIVATION_AFTER_START ? \dbObject\ChecklistItem::normalizeDelayUnit($activationData['delayUnit'] ?? null) : null);
					$item->set('display_lead_value', max(0, (int)($activationData['displayLeadValue'] ?? 0)));
					$item->set('display_lead_unit', \dbObject\ChecklistItem::normalizeDelayUnit($activationData['displayLeadUnit'] ?? null));
					$item->set('execution_duration_value', max(0, (int)($activationData['executionDurationValue'] ?? 0)));
					$item->set('execution_duration_unit', \dbObject\ChecklistItem::normalizeDelayUnit($activationData['executionDurationUnit'] ?? null));
					$item->set('position', max(1, (int)($itemRecord['position'] ?? ($itemIndex + 1))));
					$item->set('active', $itemActive);
					self::omo1ImportSave($item, 'Un element de checklist n a pas pu etre cree');
					list($frequency, $schedule) = self::omo1ImportChecklistRecurrence(isset($itemRecord['recurrence']) && is_array($itemRecord['recurrence']) ? $itemRecord['recurrence'] : array());
					if ($frequency !== null && $schedule !== null) {
						$recurrence = new \dbObject\ChecklistItemRecurrence();
						$recurrence->set('IDchecklistitem', (int)$item->getId());
						$recurrence->set('frequency', $frequency);
						$recurrence->set('schedule', $schedule);
						$recurrence->set('display_lead_value', max(0, (int)($itemRecord['recurrence']['displayLeadValue'] ?? 0)));
						$recurrence->set('display_lead_unit', \dbObject\ChecklistItem::normalizeDelayUnit($itemRecord['recurrence']['displayLeadUnit'] ?? null));
						$recurrence->set('execution_duration_value', max(0, (int)($itemRecord['recurrence']['executionDurationValue'] ?? 0)));
						$recurrence->set('execution_duration_unit', \dbObject\ChecklistItem::normalizeDelayUnit($itemRecord['recurrence']['executionDurationUnit'] ?? null));
						$recurrence->set('enabled', $itemActive);
						$nextTriggerAt = \dbObject\RecurrenceSchedule::getNextOccurrence($frequency, $schedule, new \DateTimeImmutable());
						$recurrence->set('next_trigger_at', $nextTriggerAt);
						self::omo1ImportSave($recurrence, 'La recurrence de checklist n a pas pu etre creee');
					}
					$stats['checklistItems'] += 1;
				}
			}
		}

		protected static function omo1ImportIndicatorRecurrence(array $record)
		{
			$legacyRecurrence = isset($record['legacyRecurrence']) && is_array($record['legacyRecurrence'])
				? $record['legacyRecurrence']
				: array();
			$legacyRecurrenceId = (int)($legacyRecurrence['id'] ?? ($record['legacyRecurrenceId'] ?? 0));
			$frequencyMap = array(
				1 => \dbObject\StatIndicator::FREQUENCY_WEEKLY,
				2 => \dbObject\StatIndicator::FREQUENCY_MONTHLY,
				3 => \dbObject\StatIndicator::FREQUENCY_QUARTERLY,
				4 => \dbObject\StatIndicator::FREQUENCY_SEMIANNUAL,
				5 => \dbObject\StatIndicator::FREQUENCY_DAILY,
				6 => \dbObject\StatIndicator::FREQUENCY_YEARLY,
			);
			$frequency = $frequencyMap[$legacyRecurrenceId] ?? null;
			if ($frequency === null) {
				$legacyLabel = mb_strtolower(trim((string)($legacyRecurrence['label'] ?? '')), 'UTF-8');
				$labelMap = array(
					'quotidiennement' => \dbObject\StatIndicator::FREQUENCY_DAILY,
					'hebdomadaire' => \dbObject\StatIndicator::FREQUENCY_WEEKLY,
					'mensuel' => \dbObject\StatIndicator::FREQUENCY_MONTHLY,
					'trimestriel' => \dbObject\StatIndicator::FREQUENCY_QUARTERLY,
					'semestriel' => \dbObject\StatIndicator::FREQUENCY_SEMIANNUAL,
					'annuel' => \dbObject\StatIndicator::FREQUENCY_YEARLY,
				);
				$frequency = $labelMap[$legacyLabel] ?? null;
			}

			$legacyTrigger = $record['legacyTrigger'] ?? null;
			if ($legacyTrigger === null && array_key_exists('trigger', $legacyRecurrence)) {
				$legacyTrigger = $legacyRecurrence['trigger'];
			}
			$schedule = \dbObject\StatIndicator::normalizeMeasurementSchedule($frequency, $legacyTrigger);

			return array($frequency, $schedule);
		}

		protected static function omo1ImportIndicators(\dbObject\Organization $organization, array $records, $actorUserId, array $userIdMap, array $holonIdMap, array &$stats)
		{
			foreach ($records as $record) {
				if (!is_array($record) || (int)($record['sourceId'] ?? 0) <= 0) {
					continue;
				}
				$sourceUserId = (int)($record['sourceUserId'] ?? 0);
				$targetUserId = isset($userIdMap[$sourceUserId]) ? (int)$userIdMap[$sourceUserId] : (int)$actorUserId;
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$targetHolonId = isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : null;
				$name = self::omo1ImportLimitText($record['name'] ?? '', 190);
				if ($name === '') {
					$name = self::omo1ImportLimitText(strip_tags((string)($record['description'] ?? '')), 190);
				}
				$indicator = new \dbObject\StatIndicator();
				$indicator->set('IDorganization', (int)$organization->getId());
				$indicator->set('IDholon', $targetHolonId);
				$indicator->set('IDuser', $targetUserId);
				$indicator->set('name', $name !== '' ? $name : 'Indicateur OMO 1 #' . (int)$record['sourceId']);
				$indicator->set('description', $record['description'] ?? null);
				$indicator->set('reference_type', \dbObject\StatIndicator::REFERENCE_NONE);
				list($measurementFrequency, $measurementSchedule) = self::omo1ImportIndicatorRecurrence($record);
				$indicator->set('measurement_frequency', $measurementFrequency);
				$indicator->set('measurement_schedule', $measurementSchedule);
				$createdAt = self::omo1ImportDate($record['createdAt'] ?? null);
				if ($createdAt) {
					$indicator->set('created_at', $createdAt);
				}
				$indicator->set('active', true);
				self::omo1ImportSave($indicator, 'Un indicateur n a pas pu etre cree');
				$stats['indicators'] += 1;

				$values = isset($record['values']) && is_array($record['values']) ? $record['values'] : array();
				foreach ($values as $valueRecord) {
					if (!is_array($valueRecord) || !is_numeric($valueRecord['value'] ?? null)) {
						continue;
					}
					$measuredAt = self::omo1ImportDate($valueRecord['measuredAt'] ?? null);
					if (!$measuredAt) {
						continue;
					}
					$value = new \dbObject\StatIndicatorValue();
					$value->set('IDstatindicator', (int)$indicator->getId());
					$value->set('IDuser', $targetUserId);
					$value->set('value', (float)$valueRecord['value']);
					$value->set('measured_at', $measuredAt);
					self::omo1ImportSave($value, 'Une valeur d indicateur n a pas pu etre creee');
					$stats['indicatorValues'] += 1;
				}
			}
		}

		protected static function omo1ImportPvStage(array $record): string
		{
			if (self::omo1ImportDate($record['closedAt'] ?? null)) {
				return \dbObject\Document::PV_STAGE_VALIDATED;
			}
			if (self::omo1ImportDate($record['openedAt'] ?? null)) {
				return \dbObject\Document::PV_STAGE_MEETING;
			}

			return \dbObject\Document::PV_STAGE_PREPARATION;
		}

		protected static function omo1ImportPlainText($value, $length = 0): string
		{
			$value = preg_replace('#<\s*/?\s*(br|p|div|li|h[1-6])\b[^>]*>#i', ' ', (string)$value);
			$value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$value = preg_replace('/\s+/u', ' ', trim($value));
			if ($value === '') {
				return '';
			}

			return $length > 0 ? self::omo1ImportLimitText($value, $length) : $value;
		}

		protected static function omo1ImportPvDocumentTitle($meetingTitle, $scheduledAt, $sourceMeetingId): string
		{
			$meetingTitle = self::omo1ImportPlainText($meetingTitle, 120);
			if ($meetingTitle === '') {
				$meetingTitle = 'Reunion OMO 1';
			}

			$scheduledAt = self::omo1ImportDate($scheduledAt);
			if (!$scheduledAt) {
				return 'PV ' . $meetingTitle . ' #' . (int)$sourceMeetingId;
			}

			$months = array('', 'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre');
			return 'PV ' . $meetingTitle . ' du ' . $scheduledAt->format('j') . ' ' . $months[(int)$scheduledAt->format('n')] . ' ' . $scheduledAt->format('Y');
		}

		protected static function omo1ImportPvPointContent(array $record, $includeTitle = true): string
		{
			$content = '';
			$title = \dbObject\PropertyFormat::sanitizeHtml($record['title'] ?? '');
			if ($includeTitle && $title !== '') {
				$content .= '<p>' . $title . '</p>';
			}
			$description = trim((string)($record['description'] ?? ''));
			if ($description !== '') {
				$content .= '<p>' . nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) . '</p>';
			}
			$externalUrl = trim((string)($record['externalUrl'] ?? ''));
			if ($externalUrl !== '' && filter_var($externalUrl, FILTER_VALIDATE_URL)) {
				$content .= '<p><a href="' . htmlspecialchars($externalUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">Lien OMO 1</a></p>';
			}

			return $content;
		}

		protected static function omo1ImportSaveDocumentEditVisibility(\dbObject\Document $document, \dbObject\Organization $organization, ?int $targetHolonId): void
		{
			$editVisibilityType = \dbObject\Document::resolveCompatibleScopeTypeForHolonId(
				\dbObject\Document::getDefaultEditVisibilityTypeForOrganization((int)$organization->getId()),
				(int)$organization->getId(),
				$targetHolonId,
				\dbObject\ObjectVisibility::TYPE_SELF
			);
			$editVisibilitySaveResult = $document->saveEditVisibilityRule($editVisibilityType);
			if (!is_array($editVisibilitySaveResult) || empty($editVisibilitySaveResult['status'])) {
				$message = is_array($editVisibilitySaveResult)
					? trim((string)($editVisibilitySaveResult['text'] ?? ''))
					: '';
				throw new \RuntimeException('Le droit d edition du document n a pas pu etre cree'
					. ($message !== '' ? ': ' . $message : '.'));
			}
		}

		protected static function omo1ImportPvs(\dbObject\Organization $organization, array $records, $actorUserId, array $userIdMap, array $holonIdMap, array $eventIdMap, array &$stats)
		{
			foreach ($records as $record) {
				if (!is_array($record)) {
					continue;
				}
				$sourceMeetingId = (int)($record['sourceMeetingId'] ?? ($record['sourceId'] ?? 0));
				$eventId = isset($eventIdMap[$sourceMeetingId]) ? (int)$eventIdMap[$sourceMeetingId] : 0;
				if ($sourceMeetingId <= 0 || $eventId <= 0) {
					continue;
				}
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$targetHolonId = isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : null;
				$sourceUserId = (int)($record['sourceSecretaryUserId'] ?? 0);
				$targetUserId = isset($userIdMap[$sourceUserId]) ? (int)$userIdMap[$sourceUserId] : (int)$actorUserId;
				$event = new \dbObject\Event();
				$eventTitle = $event->load($eventId) ? $event->get('title') : ($record['meetingTitle'] ?? '');
				$document = new \dbObject\Document();
				$document->set('title', self::omo1ImportPvDocumentTitle($eventTitle, $record['scheduledAt'] ?? null, $sourceMeetingId));
				$document->set('description', trim((string)($record['meetingScratchpad'] ?? '')) ?: null);
				$document->set('documenttype', \dbObject\Document::TYPE_PV);
				$document->set('pvstage', self::omo1ImportPvStage($record));
				$document->set('IDorganization', (int)$organization->getId());
				$document->set('IDholon', $targetHolonId);
				$document->set('IDevent', $eventId);
				$document->set('IDuser', $targetUserId);
				$document->set('IDusercreation', $targetUserId);
				$document->set('IDusermodification', $targetUserId);
				$createdAt = self::omo1ImportDate($record['scheduledAt'] ?? null);
				if ($createdAt) {
					$document->set('datecreation', $createdAt);
				}
				$updatedAt = self::omo1ImportDate($record['closedAt'] ?? null) ?: $createdAt;
				if ($updatedAt) {
					$document->set('datemodification', $updatedAt);
				}
				$document->set('active', true);
				self::omo1ImportSave($document, 'Un proces-verbal n a pas pu etre cree');
				self::omo1ImportSaveDocumentEditVisibility($document, $organization, $targetHolonId);
				$stats['pv'] += 1;

				$historyRecords = isset($record['history']) && is_array($record['history']) ? $record['history'] : array();
				$historyById = array();
				$historyChildrenByParent = array();
				foreach ($historyRecords as $historyRecord) {
					if (!is_array($historyRecord)) {
						continue;
					}
					$sourceHistoryId = (int)($historyRecord['sourceId'] ?? 0);
					if ($sourceHistoryId <= 0) {
						continue;
					}
					$historyById[$sourceHistoryId] = $historyRecord;
					$sourceParentId = (int)($historyRecord['sourceParentHistoryId'] ?? 0);
					if ($sourceParentId > 0) {
						$historyChildrenByParent[$sourceParentId][] = $sourceHistoryId;
					}
				}

				$collectHistory = null;
				$collectHistory = function ($sourceHistoryId, array &$collectedIds) use (&$collectHistory, $historyChildrenByParent) {
					$sourceHistoryId = (int)$sourceHistoryId;
					if ($sourceHistoryId <= 0 || isset($collectedIds[$sourceHistoryId])) {
						return;
					}
					$collectedIds[$sourceHistoryId] = $sourceHistoryId;
					foreach ($historyChildrenByParent[$sourceHistoryId] ?? array() as $childHistoryId) {
						$collectHistory($childHistoryId, $collectedIds);
					}
				};

				$processedHistoryIds = array();
				$pointGroups = array();
				foreach ($historyById as $sourceHistoryId => $historyRecord) {
					if ((int)($historyRecord['sourceTensionId'] ?? 0) <= 0 || isset($processedHistoryIds[$sourceHistoryId])) {
						continue;
					}
					$groupHistoryIds = array();
					$collectHistory($sourceHistoryId, $groupHistoryIds);
					foreach ($groupHistoryIds as $groupHistoryId) {
						$processedHistoryIds[$groupHistoryId] = true;
					}
					$pointGroups[] = array('rootId' => $sourceHistoryId, 'historyIds' => array_values($groupHistoryIds), 'isTension' => true);
				}
				foreach ($historyById as $sourceHistoryId => $historyRecord) {
					if (isset($processedHistoryIds[$sourceHistoryId])) {
						continue;
					}
					$groupHistoryIds = array();
					$collectHistory($sourceHistoryId, $groupHistoryIds);
					foreach ($groupHistoryIds as $groupHistoryId) {
						$processedHistoryIds[$groupHistoryId] = true;
					}
					$pointGroups[] = array('rootId' => $sourceHistoryId, 'historyIds' => array_values($groupHistoryIds), 'isTension' => false);
				}

				foreach ($pointGroups as $pointGroup) {
					$sourceHistoryId = (int)$pointGroup['rootId'];
					$historyRecord = $historyById[$sourceHistoryId];
					$pointHolonSourceId = (int)($historyRecord['sourceHolonId'] ?? 0);
					$pointHolonId = isset($holonIdMap[$pointHolonSourceId]) ? (int)$holonIdMap[$pointHolonSourceId] : null;
					$pointSourceUserId = !empty($pointGroup['isTension'])
						? (int)($historyRecord['sourceTensionUserId'] ?? 0)
						: (int)($historyRecord['sourceUserId'] ?? 0);
					if ($pointSourceUserId <= 0 && !empty($pointGroup['isTension'])) {
						$pointSourceUserId = (int)($historyRecord['sourceUserId'] ?? 0);
					}
					$pointUserId = isset($userIdMap[$pointSourceUserId]) ? (int)$userIdMap[$pointSourceUserId] : null;
					$title = !empty($pointGroup['isTension'])
						? self::omo1ImportPlainText($historyRecord['tensionTitle'] ?? '', 120)
						: self::omo1ImportPlainText($historyRecord['title'] ?? '', 120);
					if ($title === '') {
						$title = 'Point OMO 1 #' . $sourceHistoryId;
					}
					$content = '';
					foreach ($pointGroup['historyIds'] as $groupHistoryId) {
						$groupHistoryRecord = $historyById[(int)$groupHistoryId];
						$content .= self::omo1ImportPvPointContent($groupHistoryRecord, (int)$groupHistoryId !== $sourceHistoryId);
					}
					$point = new \dbObject\DocumentPvPoint();
					$point->set('IDdocument', (int)$document->getId());
					$point->set('item_type', \dbObject\DocumentPvPoint::ITEM_TYPE_POINT);
					$point->set('title', $title);
					$point->set('content', $content);
					$point->set('pointtype', ($historyRecord['legacyPointType'] ?? '') === 'information'
						? \dbObject\DocumentPvPoint::TYPE_INFORMATION
						: \dbObject\DocumentPvPoint::TYPE_CONSULTATION);
					$point->set('position', max(1, (int)($historyRecord['position'] ?? 1)));
					$point->set('IDuser_author', $pointUserId);
					$point->set('IDuser_modification', $targetUserId);
					$point->set('IDholon_concerned', $pointHolonId);
					$point->set('is_handled', true);
					$point->set('active', true);
					$pointCreatedAt = self::omo1ImportDate($historyRecord['createdAt'] ?? null);
					if ($pointCreatedAt) {
						$point->set('datecreation', $pointCreatedAt);
						$point->set('datemodification', $pointCreatedAt);
					}
					self::omo1ImportSave($point, 'Un point de proces-verbal n a pas pu etre cree');
					$stats['pvPoints'] += 1;
				}
			}
		}

		protected static function omo1ImportCalendar(\dbObject\Organization $organization, array $records, $actorUserId, array $holonIdMap, array &$eventIdMap, array &$stats)
		{
			foreach ($records as $record) {
				if (!is_array($record) || (int)($record['sourceId'] ?? 0) <= 0) {
					continue;
				}
				$sourceHolonId = (int)($record['sourceHolonId'] ?? 0);
				$targetHolonId = isset($holonIdMap[$sourceHolonId]) ? (int)$holonIdMap[$sourceHolonId] : null;
				$startAt = self::omo1ImportDate($record['openedAt'] ?? null) ?: self::omo1ImportScheduledDate($record['scheduledAt'] ?? null, $record['startTime'] ?? null);
				$endAt = self::omo1ImportDate($record['closedAt'] ?? null) ?: self::omo1ImportScheduledDate($record['scheduledAt'] ?? null, $record['endTime'] ?? null);
				if (!$startAt) {
					continue;
				}
				if (!$endAt || $endAt < $startAt) {
					$endAt = $startAt->modify('+1 hour');
				}
				$title = self::omo1ImportLimitText($record['title'] ?? '', 190);
				if ($title === '') {
					$meetingTypeTitles = array(
						1 => 'Reunion de gouvernance',
						2 => 'Reunion operationnelle',
						3 => 'Reunion mixte',
						4 => 'Reunion de strategie',
						5 => 'Reunion de travail',
						6 => 'Reunion de regulation',
						7 => 'Reunion informelle',
					);
					$legacyMeetingTypeId = (int)($record['legacyMeetingTypeId'] ?? 0);
					$legacyMeetingTypeLabel = self::omo1ImportLimitText($record['legacyMeetingTypeLabel'] ?? '', 160);
					$title = $meetingTypeTitles[$legacyMeetingTypeId]
						?? ($legacyMeetingTypeLabel !== '' ? 'Reunion ' . $legacyMeetingTypeLabel : 'Reunion OMO 1');
				}
				$event = new \dbObject\Event();
				$event->set('IDorganization', (int)$organization->getId());
				$event->set('IDholon', $targetHolonId);
				$event->set('IDuser', (int)$actorUserId);
				$event->set('title', $title);
				$event->set('description', $record['scratchpad'] ?? null);
				$event->set('status', \dbObject\Event::STATUS_CONFIRMED);
				$event->set('timezone', 'Europe/Zurich');
				$event->set('locationaddress', $record['location'] ?? null);
				$event->set('start_at', $startAt);
				$event->set('end_at', $endAt);
				$event->set('is_all_day', false);
				$event->set('active', true);
				self::omo1ImportSave($event, 'Une reunion du calendrier n a pas pu etre creee');
				$eventIdMap[(int)$record['sourceId']] = (int)$event->getId();
				$stats['calendar'] += 1;
			}
		}

		public static function importOmo1ExportAsNewOrganization(array $payload, array $requestedModules, $actorUserId, $organizationName = '', array $templateCalibration = array())
		{
			$actorUserId = (int)$actorUserId;
			if ($actorUserId <= 0) {
				return array('status' => false, 'message' => 'Connexion requise.');
			}
			if ((string)($payload['format'] ?? '') !== 'openmyorganization-structure-export' || (int)($payload['version'] ?? 0) !== 4) {
				return array('status' => false, 'message' => 'Le fichier doit etre un export OMO compact version 4.');
			}
			$mediaWarnings = array();
			$payload = self::sanitizeOmo1ImportedMediaReferences($payload, $mediaWarnings);

			$availableModules = array('structure', 'rules', 'members', 'documents', 'projects', 'tasks', 'checklists', 'indicators', 'calendar', 'pv');
			$sourceModules = isset($payload['modules']) && is_array($payload['modules']) ? $payload['modules'] : array();
			$selectedModules = array();
			foreach ($availableModules as $module) {
				$selectedModules[$module] = !empty($requestedModules[$module]) && !empty($sourceModules[$module]['selected']);
			}
			if ($selectedModules['tasks']) {
				$selectedModules['projects'] = !empty($sourceModules['projects']['selected']);
			}
			if ($selectedModules['pv']) {
				if (empty($sourceModules['calendar']['selected'])) {
					return array('status' => false, 'message' => 'Les proces-verbaux OMO 1 necessitent aussi la section calendrier.');
				}
				$selectedModules['calendar'] = true;
			}
			if (!$selectedModules['structure']) {
				return array('status' => false, 'message' => 'La structure doit etre selectionnee pour creer une organisation importee.');
			}

			$sourceOrganization = isset($payload['organization']) && is_array($payload['organization']) ? $payload['organization'] : array();
			$name = trim((string)$organizationName);
			if ($name === '') {
				$name = trim((string)($sourceOrganization['name'] ?? ''));
			}
			if ($name === '') {
				return array('status' => false, 'message' => 'Le nom de la nouvelle organisation est obligatoire.');
			}

			$organization = new self();
			$organization->set('name', self::omo1ImportLimitText($name, 100));
			$organization->set('color', trim((string)($sourceOrganization['color'] ?? '')) ?: null);
			$organization->set('logo', $sourceOrganization['logo'] ?? null);
			$organization->set('banner', $sourceOrganization['banner'] ?? null);
			$earliestImportDate = self::omo1ImportEarliestImportedDate($payload, $selectedModules);
			if ($earliestImportDate) {
				$organization->set('datecreation', $earliestImportDate);
			}
			$organizationSave = $organization->save();
			if (!is_array($organizationSave) || empty($organizationSave['status']) || (int)$organization->getId() <= 0) {
				return array('status' => false, 'message' => 'La nouvelle organisation n a pas pu etre creee.');
			}

			try {
				self::omo1ImportUserMembership($organization, $actorUserId, true);
				$structureResult = $organization->importStructure($payload, $actorUserId, $templateCalibration);
				if (empty($structureResult['status']) || !($structureResult['rootHolon'] ?? null) instanceof \dbObject\Holon) {
					throw new \RuntimeException((string)($structureResult['message'] ?? 'La structure n a pas pu etre importee.'));
				}

				$organization->ensureDefaultApplicationLinks();
				$pdo = \dbObject\DbObject::getPdo();
				if (!$pdo) {
					throw new \RuntimeException('La connexion a la base de donnees est indisponible.');
				}
				$pdo->beginTransaction();
				$applicationSync = $organization->synchronizeOmo1ImportedApplicationLinks($selectedModules, $sourceModules);
				if (empty($applicationSync['status'])) {
					throw new \RuntimeException((string)($applicationSync['message'] ?? 'Les applications de l organisation n ont pas pu etre configurees.'));
				}
				$holonIdMap = isset($structureResult['holonIdMap']) && is_array($structureResult['holonIdMap']) ? $structureResult['holonIdMap'] : array();
				$rulesRecords = $selectedModules['rules'] ? self::omo1ImportModuleRecords($payload, 'rules') : array();
				$ruleDomainRecords = $selectedModules['rules'] ? self::omo1ImportRuleDomains($payload, $rulesRecords) : array();
				$hasAppliedOrganizationModel = (int)($templateCalibration['templateRootHolonId'] ?? 0) > 0
					&& isset($templateCalibration['mappings'])
					&& is_array($templateCalibration['mappings'])
					&& count($templateCalibration['mappings']) > 0;
				$userIdMap = array();
				$documentIdMap = array();
				$documentProjectSourceMap = array();
				$projectIdMap = array();
				$taskIdMap = array();
				$eventIdMap = array();
				$pendingUserIds = array();
				$pendingInvitations = array();
				$stats = array('members' => 0, 'invitations' => 0, 'roleAssignments' => 0, 'authorities' => 0, 'rules' => 0, 'documents' => 0, 'projects' => 0, 'tasks' => 0, 'checklists' => 0, 'checklistItems' => 0, 'indicators' => 0, 'indicatorValues' => 0, 'calendar' => 0, 'pv' => 0, 'pvPoints' => 0);
				$warnings = array_merge(
					$mediaWarnings,
					isset($structureResult['warnings']) && is_array($structureResult['warnings'])
						? $structureResult['warnings']
						: array()
				);

				if ($selectedModules['members']) {
					$memberRecords = self::omo1ImportModuleRecords($payload, 'members');
					self::omo1ImportMembers($organization, $memberRecords, $actorUserId, $userIdMap, $pendingUserIds, $pendingInvitations, $stats, $warnings);
					self::omo1ImportRoleAssignments($memberRecords, $userIdMap, $holonIdMap, $pendingUserIds, $stats);
				}
				if ($selectedModules['rules']) {
					$authorityImportResult = self::omo1ImportAuthorities(
						$organization,
						$ruleDomainRecords,
						$holonIdMap,
						$stats,
						$warnings,
						array('hasAppliedOrganizationModel' => $hasAppliedOrganizationModel)
					);
					$authorityIdMap = isset($authorityImportResult['authorityIdMap']) && is_array($authorityImportResult['authorityIdMap'])
						? $authorityImportResult['authorityIdMap']
						: array();
					$authorityIdsByHolonId = isset($authorityImportResult['authorityIdsByHolonId']) && is_array($authorityImportResult['authorityIdsByHolonId'])
						? $authorityImportResult['authorityIdsByHolonId']
						: array();
					$organization->omo1ImportConvertAuthorityPropertyValues(
						$payload,
						$ruleDomainRecords,
						$authorityIdMap,
						$authorityIdsByHolonId,
						$holonIdMap,
						isset($structureResult['propertyIdMap']) && is_array($structureResult['propertyIdMap']) ? $structureResult['propertyIdMap'] : array(),
						isset($structureResult['mappedSourceTemplateIds']) && is_array($structureResult['mappedSourceTemplateIds']) ? $structureResult['mappedSourceTemplateIds'] : array(),
						$warnings,
						!$hasAppliedOrganizationModel
					);
					self::omo1ImportRules($organization, $rulesRecords, $actorUserId, $userIdMap, $holonIdMap, $authorityIdMap, $authorityIdsByHolonId, $stats, $warnings);
				}
				if ($selectedModules['documents']) {
					self::omo1ImportDocuments($organization, self::omo1ImportModuleRecords($payload, 'documents'), $actorUserId, $userIdMap, $holonIdMap, $documentIdMap, $documentProjectSourceMap, $stats, $warnings);
				}
				if ($selectedModules['projects']) {
					self::omo1ImportProjects($organization, self::omo1ImportModuleRecords($payload, 'projects'), $actorUserId, $userIdMap, $holonIdMap, $documentIdMap, $documentProjectSourceMap, $projectIdMap, $stats);
				}
				if ($selectedModules['tasks']) {
					self::omo1ImportTasks($organization, self::omo1ImportModuleRecords($payload, 'tasks'), $actorUserId, $userIdMap, $holonIdMap, $projectIdMap, $taskIdMap, $stats);
				}
				if ($selectedModules['documents'] && ($selectedModules['projects'] || $selectedModules['tasks'])) {
					self::omo1ImportLinkDocumentsToProjects($documentIdMap, $documentProjectSourceMap, $projectIdMap, $taskIdMap);
				}
				$organization->remapImportedProjectPropertyValues($projectIdMap, $taskIdMap);
				if ($selectedModules['checklists']) {
					self::omo1ImportChecklists($organization, self::omo1ImportModuleRecords($payload, 'checklists'), $actorUserId, $userIdMap, $holonIdMap, $stats);
				}
				if ($selectedModules['indicators']) {
					self::omo1ImportIndicators($organization, self::omo1ImportModuleRecords($payload, 'indicators'), $actorUserId, $userIdMap, $holonIdMap, $stats);
				}
				if ($selectedModules['calendar']) {
					self::omo1ImportCalendar($organization, self::omo1ImportModuleRecords($payload, 'calendar'), $actorUserId, $holonIdMap, $eventIdMap, $stats);
				}
				if ($selectedModules['pv']) {
					self::omo1ImportPvs($organization, self::omo1ImportModuleRecords($payload, 'pv'), $actorUserId, $userIdMap, $holonIdMap, $eventIdMap, $stats);
				}
				$pdo->commit();
				foreach ($pendingInvitations as $pendingInvitation) {
					try {
						$pendingInvitation->sendEmail();
						$stats['invitations'] += 1;
					} catch (\Throwable $exception) {
						$warnings[] = 'L invitation pour ' . trim((string)$pendingInvitation->get('email')) . ' n a pas pu etre envoyee.';
					}
				}

				return array(
					'status' => true,
					'message' => 'La nouvelle organisation a ete importee.',
					'organization' => $organization,
					'rootHolon' => $structureResult['rootHolon'],
					'stats' => $stats,
					'warnings' => array_values(array_unique($warnings)),
					'applications' => $applicationSync['activeApplications'] ?? array(),
				);
			} catch (\Throwable $exception) {
				$pdo = \dbObject\DbObject::getPdo();
				if ($pdo && $pdo->inTransaction()) {
					$pdo->rollBack();
				}
				return array(
					'status' => false,
					'message' => $exception->getMessage(),
					'organization' => $organization,
				);
			}
		}

		public function importStructure(array $payload, $userId = 0, array $templateCalibration = array())
		{
			if ((int)$this->getId() <= 0) {
				return array(
					'status' => false,
					'message' => "L'organisation demandee est introuvable.",
				);
			}

			if ($this->getStructuralRootHolon()) {
				return array(
					'status' => false,
					'message' => 'Cette organisation a deja une structure.',
				);
			}

			$format = (string)($payload['format'] ?? '');
			$version = (int)($payload['version'] ?? 0);
			if ($format !== 'openmyorganization-structure-export' || $version !== 4) {
				return array(
					'status' => false,
					'message' => "Le fichier d'import doit etre un export OMO au format compact version 4.",
				);
			}

			$importedHolonRecords = $this->getImportedHolonRecords($payload);
			if (count($importedHolonRecords) === 0) {
				return array(
					'status' => false,
					'message' => "Le fichier d'import ne contient pas de holons dans le format compact attendu.",
				);
			}

			return $this->importStructureFromCompactGraph($payload, $userId, $templateCalibration);
		}

		protected function cloneStructuralChildrenRecursively(\dbObject\Holon $sourceParent, $targetParentId, $targetRootHolonId, $userId, array &$sourceHolonsById, array &$targetHolonsBySourceId, array &$holonIdMap)
		{
			foreach ($this->getStructuralInitializationChildren($sourceParent) as $sourceChild) {
				$targetChild = new \dbObject\Holon();
				$targetChild->set('name', $sourceChild->get('name'));
				$targetChild->set('nomcomplet', trim((string)$sourceChild->get('nomcomplet')) !== '' ? $sourceChild->get('nomcomplet') : null);
				$targetChild->set('templatename', $sourceChild->get('templatename'));
				$targetChild->set('IDtypeholon', (int)$sourceChild->get('IDtypeholon'));
				$targetChild->set('IDholon_parent', (int)$targetParentId);
				$targetChild->set('IDholon_template', null);
				$targetChild->set('IDholon_org', (int)$targetRootHolonId);
				$targetChild->set('IDorganization', null);
				$targetChild->set('IDuser', (int)$userId > 0 ? (int)$userId : (int)$sourceChild->get('IDuser'));
				$targetChild->set('active', (bool)$sourceChild->get('active'));
				$targetChild->set('visible', (bool)$sourceChild->get('visible'));
				$targetChild->set('mandatory', (bool)$sourceChild->get('mandatory'));
				$targetChild->set('lockedname', (bool)$sourceChild->get('lockedname'));
				$targetChild->set('lockedicon', (bool)$sourceChild->get('lockedicon'));
				$targetChild->set('lockedbanner', (bool)$sourceChild->get('lockedbanner'));
				$targetChild->set('unique', (bool)$sourceChild->get('unique'));
				$targetChild->set('link', (bool)$sourceChild->get('link'));
				$targetChild->set('admin_min', max(0, (int)$sourceChild->get('admin_min')));
				$targetChild->set('admin_max', $sourceChild->get('admin_max') === null ? null : (int)$sourceChild->get('admin_max'));
				$targetChild->set('lockedadminmin', (bool)$sourceChild->get('lockedadminmin'));
				$targetChild->set('lockedadminmax', (bool)$sourceChild->get('lockedadminmax'));
				$targetChild->set('adminminoverride', (bool)$sourceChild->get('adminminoverride'));
				$targetChild->set('adminmaxoverride', (bool)$sourceChild->get('adminmaxoverride'));
				$targetChild->set('color', $sourceChild->get('color') ?: null);
				$targetChild->set('icon', $sourceChild->get('icon') ?: null);
				$targetChild->set('banner', $sourceChild->get('banner') ?: null);
				$targetChild->set('accesskey', $sourceChild->get('accesskey') ?: null);
				$targetChild->save();

				$sourceChildId = (int)$sourceChild->getId();
				$sourceHolonsById[$sourceChildId] = $sourceChild;
				$targetHolonsBySourceId[$sourceChildId] = $targetChild;
				$holonIdMap[$sourceChildId] = (int)$targetChild->getId();

				$this->cloneStructuralChildrenRecursively(
					$sourceChild,
					(int)$targetChild->getId(),
					(int)$targetRootHolonId,
					(int)$userId,
					$sourceHolonsById,
					$targetHolonsBySourceId,
					$holonIdMap
				);
			}
		}

		protected function initializeStructureFromTemplate(\dbObject\Holon $sourceRootHolon, $userId = 0)
		{
			$targetRootHolon = $this->createStructuralRootHolon($userId, $sourceRootHolon);
			if (!$targetRootHolon) {
				return array(
					'status' => false,
					'message' => "Le holon racine n'a pas pu etre cree.",
				);
			}

			$sourceRootHolonId = (int)$sourceRootHolon->getId();
			$targetRootHolonId = (int)$targetRootHolon->getId();
			$sourceHolonsById = array(
				$sourceRootHolonId => $sourceRootHolon,
			);
			$targetHolonsBySourceId = array(
				$sourceRootHolonId => $targetRootHolon,
			);
			$holonIdMap = array(
				$sourceRootHolonId => $targetRootHolonId,
			);
			$propertyIdMap = array();

			$this->cloneStructuralChildrenRecursively(
				$sourceRootHolon,
				$targetRootHolonId,
				$targetRootHolonId,
				(int)$userId,
				$sourceHolonsById,
				$targetHolonsBySourceId,
				$holonIdMap
			);

			foreach ($sourceHolonsById as $sourceHolonId => $sourceHolon) {
				$targetHolon = $targetHolonsBySourceId[$sourceHolonId] ?? null;
				if (!$targetHolon) {
					continue;
				}

				$sourceTemplateId = (int)$sourceHolon->get('IDholon_template');
				$targetTemplateId = 0;
				if ($sourceTemplateId > 0) {
					$targetTemplateId = isset($holonIdMap[$sourceTemplateId])
						? (int)$holonIdMap[$sourceTemplateId]
						: $sourceTemplateId;
				}

				$targetHolon->set('IDholon_template', $targetTemplateId > 0 ? $targetTemplateId : null);
				$targetHolon->save();
			}

			$authorityIdMap = $this->cloneStructuralAuthorities($sourceHolonsById, $targetHolonsBySourceId);

			foreach ($sourceHolonsById as $sourceHolonId => $sourceHolon) {
				$targetHolon = $targetHolonsBySourceId[$sourceHolonId] ?? null;
				if (!$targetHolon) {
					continue;
				}

				$this->cloneStructuralHolonProperties(
					$sourceHolon,
					$targetHolon,
					$sourceRootHolonId,
					$targetRootHolonId,
					$holonIdMap,
					$propertyIdMap,
					$authorityIdMap
				);

				if (!$this->cloneStructuralHolonPermissions($sourceHolon, $targetHolon)) {
					return array(
						'status' => false,
						'message' => "Les droits du modele n'ont pas pu etre dupliques.",
					);
				}
			}

			foreach ($targetHolonsBySourceId as $targetHolon) {
				if ($targetHolon instanceof \dbObject\Holon && $targetHolon->isTemplateNode($targetRootHolonId)) {
					$this->normalizeTemplateLocalAuthorities($targetHolon);
					$this->syncTemplateAuthorityInstances($targetHolon);
				}
			}

			return array(
				'status' => true,
				'message' => 'Organisation initialisee depuis le modele selectionne.',
				'rootHolon' => $targetRootHolon,
			);
		}

		public function initializeStructure($userId = 0, $templateRootHolonId = 0)
		{
			if ((int)$this->getId() <= 0) {
				return array(
					'status' => false,
					'message' => "L'organisation demandee est introuvable.",
				);
			}

			if ($this->getStructuralRootHolon()) {
				return array(
					'status' => false,
					'message' => 'Cette organisation a deja une structure.',
				);
			}

			$templateRootHolonId = (int)$templateRootHolonId;
			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'La connexion a la base de donnees est indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				if ($templateRootHolonId <= 0) {
					$rootHolon = $this->createStructuralRootHolon($userId);
					if (!$rootHolon) {
						throw new \RuntimeException("Le holon racine n'a pas pu etre cree.");
					}

					$result = array(
						'status' => true,
						'message' => 'Organisation creee.',
						'rootHolon' => $rootHolon,
					);
				} else {
					$templateRootHolon = new \dbObject\Holon();
					if (
						!$templateRootHolon->load($templateRootHolonId)
						|| (int)$templateRootHolon->get('IDtypeholon') !== 4
						|| !(bool)$templateRootHolon->get('active')
						|| trim((string)$templateRootHolon->get('templatename')) === ''
					) {
						throw new \RuntimeException("Le modele d'organisation demande est introuvable.");
					}

					$result = $this->initializeStructureFromTemplate($templateRootHolon, $userId);
					if (!($result['status'] ?? false)) {
						throw new \RuntimeException((string)($result['message'] ?? "Le modele n'a pas pu etre duplique."));
					}
				}

				$pdo->commit();
				return $result;
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'message' => $exception->getMessage(),
				);
			}
		}

		public function containsHolon($holon): bool
		{
			$holonObject = $holon instanceof \dbObject\Holon ? $holon : new \dbObject\Holon();
			if (!($holon instanceof \dbObject\Holon) && !$holonObject->load((int)$holon)) {
				return false;
			}

			if (!(bool)$holonObject->get('active') || !(bool)$holonObject->get('visible')) {
				return false;
			}

			$rootHolon = $this->getEnabledStructuralRootHolon();
			if (!$rootHolon) {
				return false;
			}

			if ((int)$holonObject->get('IDorganization') === (int)$this->getId()) {
				return true;
			}

			return $holonObject->isDescendantOf($rootHolon, true);
		}

		public function getTemplateContextHolon($contextHolonId = 0)
		{
			$rootHolon = $this->getEnabledStructuralRootHolon();
			if (!$rootHolon) {
				return null;
			}

			$contextHolonId = (int)$contextHolonId;
			if ($contextHolonId <= 0 || $contextHolonId === (int)$rootHolon->getId()) {
				return $rootHolon;
			}

			$contextHolon = new \dbObject\Holon();
			if (!$contextHolon->load($contextHolonId) || !$this->containsHolon($contextHolon)) {
				return $rootHolon;
			}

			return $contextHolon;
		}

		public function getTemplateContextPathHolons($contextHolonId = 0)
		{
			$contextHolon = $this->getTemplateContextHolon($contextHolonId);
			if (!$contextHolon) {
				return array();
			}

			return $contextHolon->getPathHolons(true);
		}

		public function getAllTemplateDefinitionHolons()
		{
			$rootHolon = $this->getStructuralRootHolon();
			$templates = array();

			if (!$rootHolon) {
				return $templates;
			}

			$templateCollection = new \dbObject\ArrayHolon();
			$templateCollection->load(array(
				'filter' => 'active = 1'
					. ' and id != ' . (int)$rootHolon->getId()
					. ' and IDholon_org = ' . (int)$rootHolon->getId(),
				'orderBy' => array(
					array('field' => 'IDholon_parent', 'dir' => 'ASC'),
					array('field' => 'IDtypeholon', 'dir' => 'ASC'),
					array('field' => 'templatename', 'dir' => 'ASC'),
					array('field' => 'name', 'dir' => 'ASC'),
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			foreach ($templateCollection as $template) {
				if (!$template->isTemplateNode((int)$rootHolon->getId())) {
					continue;
				}

				$templates[] = $template;
			}

			return $templates;
		}

		public function getTemplateDefinitionHolons($contextHolonId = 0)
		{
			$contextHolon = $this->getTemplateContextHolon($contextHolonId);
			if (!$contextHolon) {
				return array();
			}

			$contextId = (int)$contextHolon->getId();
			return array_values(array_filter($this->getAllTemplateDefinitionHolons(), function ($template) use ($contextId) {
				return (int)$template->get('IDholon_parent') === $contextId;
			}));
		}

		public function getAvailableTemplateDefinitionHolons($contextHolonId = 0)
		{
			$pathIds = array_map(function ($holon) {
				return (int)$holon->getId();
			}, $this->getTemplateContextPathHolons($contextHolonId));

			if (count($pathIds) === 0) {
				return array();
			}

			return array_values(array_filter($this->getAllTemplateDefinitionHolons(), function ($template) use ($pathIds) {
				return in_array((int)$template->get('IDholon_parent'), $pathIds, true);
			}));
		}

		protected function collectExportScopeHolonIds(\dbObject\Holon $holon, array &$ids)
		{
			$holonId = (int)$holon->getId();
			if ($holonId <= 0 || isset($ids[$holonId])) {
				return;
			}

			$ids[$holonId] = $holonId;
			foreach ($holon->getChildren() as $child) {
				$this->collectExportScopeHolonIds($child, $ids);
			}
		}

		protected function loadExportHolonById($holonId, array &$cache)
		{
			$holonId = (int)$holonId;
			if ($holonId <= 0) {
				return null;
			}

			if (isset($cache[$holonId])) {
				return $cache[$holonId];
			}

			$holon = new \dbObject\Holon();
			if (!$holon->load($holonId)) {
				$cache[$holonId] = null;
				return null;
			}

			$cache[$holonId] = $holon;
			return $holon;
		}

		protected function addExportHolonIdWithAncestors($holonId, array &$selectedHolonIds, array &$holonCache)
		{
			$current = $this->loadExportHolonById((int)$holonId, $holonCache);
			$guard = 0;

			while ($current instanceof \dbObject\Holon && $guard < 100) {
				$currentId = (int)$current->getId();
				if ($currentId <= 0) {
					break;
				}

				$selectedHolonIds[$currentId] = $currentId;

				$parentId = (int)$current->get('IDholon_parent');
				if ($parentId <= 0) {
					break;
				}

				$current = $this->loadExportHolonById($parentId, $holonCache);
				$guard += 1;
			}
		}

		protected function getStructureCompactExportHolons(\dbObject\Holon $exportRoot)
		{
			$rootHolon = $this->getStructuralRootHolon();
			if (!$rootHolon || (int)$exportRoot->getId() <= 0) {
				return array();
			}

			$visibleScopeIds = array();
			$this->collectExportScopeHolonIds($exportRoot, $visibleScopeIds);

			$pathIds = array_map(function ($holon) {
				return (int)$holon->getId();
			}, $exportRoot->getPathHolons(true));

			$eligibleTemplateContextIds = array();
			foreach (array_merge(array_values($visibleScopeIds), $pathIds) as $holonId) {
				$holonId = (int)$holonId;
				if ($holonId > 0) {
					$eligibleTemplateContextIds[$holonId] = $holonId;
				}
			}

			$selectedHolonIds = array();
			$holonCache = array();

			foreach (array_values($eligibleTemplateContextIds) as $holonId) {
				$this->addExportHolonIdWithAncestors($holonId, $selectedHolonIds, $holonCache);
			}

			$templateById = array();
			foreach ($this->getAllTemplateDefinitionHolons() as $template) {
				$templateById[(int)$template->getId()] = $template;
				$holonCache[(int)$template->getId()] = $template;
			}

			$selectedTemplateIds = array();
			$appendTemplateChain = function ($templateId) use (&$appendTemplateChain, &$selectedTemplateIds, &$selectedHolonIds, &$holonCache, $templateById) {
				$templateId = (int)$templateId;
				if ($templateId <= 0 || isset($selectedTemplateIds[$templateId]) || !isset($templateById[$templateId])) {
					return;
				}

				$template = $templateById[$templateId];
				$selectedTemplateIds[$templateId] = $templateId;
				$selectedHolonIds[$templateId] = $templateId;
				$holonCache[$templateId] = $template;

				$contextHolonId = (int)$template->get('IDholon_parent');
				if ($contextHolonId > 0) {
					$this->addExportHolonIdWithAncestors($contextHolonId, $selectedHolonIds, $holonCache);
				}

				$inheritsFromId = (int)$template->get('IDholon_template');
				if ($inheritsFromId > 0) {
					$appendTemplateChain($inheritsFromId);
				}
			};

			foreach (array_values($eligibleTemplateContextIds) as $holonId) {
				$holon = $this->loadExportHolonById($holonId, $holonCache);
				if (!$holon instanceof \dbObject\Holon) {
					continue;
				}

				$templateId = (int)$holon->get('IDholon_template');
				if ($templateId > 0) {
					$appendTemplateChain($templateId);
				}
			}

			foreach ($templateById as $templateId => $template) {
				if (isset($eligibleTemplateContextIds[(int)$template->get('IDholon_parent')])) {
					$appendTemplateChain($templateId);
				}
			}

			$items = array();
			$organizationRootId = (int)$rootHolon->getId();
			$exportRootId = (int)$exportRoot->getId();

			foreach (array_values($selectedHolonIds) as $holonId) {
				$holon = $this->loadExportHolonById($holonId, $holonCache);
				if (!$holon instanceof \dbObject\Holon) {
					continue;
				}

				$role = 'context_support';
				if ($holonId === $organizationRootId) {
					$role = 'organization_root';
				} elseif (isset($visibleScopeIds[$holonId])) {
					$role = $holonId === $exportRootId ? 'export_root' : 'structure';
				} elseif ($holon->isTemplateNode($organizationRootId)) {
					$role = 'template';
				}

				$items[] = array(
					'holon' => $holon,
					'role' => $role,
					'isScopeRoot' => $holonId === $exportRootId,
				);
			}

			usort($items, function ($left, $right) {
				$leftParentId = (int)$left['holon']->get('IDholon_parent');
				$rightParentId = (int)$right['holon']->get('IDholon_parent');
				if ($leftParentId === $rightParentId) {
					return (int)$left['holon']->getId() <=> (int)$right['holon']->getId();
				}

				return $leftParentId <=> $rightParentId;
			});

			return $items;
		}

		public function getStructureCompactExportData(\dbObject\Holon $exportRoot)
		{
			$items = $this->getStructureCompactExportHolons($exportRoot);
			$rootHolon = $this->getStructuralRootHolon();
			$rootHolonId = $rootHolon ? (int)$rootHolon->getId() : 0;
			$holonRows = array();
			$propertyDefinitionIds = array();
			$authorityRows = array();
			$ruleRows = array();
			$exportedRuleIds = array();

			foreach ($items as $item) {
				$holon = $item['holon'] ?? null;
				if (!$holon instanceof \dbObject\Holon || (int)$holon->getId() <= 0) {
					continue;
				}

				$holonRows[] = $holon->toCompactExportRecord($rootHolonId, array(
					'role' => (string)($item['role'] ?? 'structure'),
					'isScopeRoot' => !empty($item['isScopeRoot']),
				));

				foreach ($holon->getHolonProperties() as $holonProperty) {
					$propertyId = (int)$holonProperty->get('IDproperty');
					if ($propertyId > 0) {
						$propertyDefinitionIds[$propertyId] = $propertyId;
					}
				}

				$authorities = new \dbObject\ArrayAuthority();
				$authorities->loadForHolon((int)$holon->getId());
				foreach ($authorities as $authority) {
					$authorityId = (int)$authority->getId();
					if ($authorityId <= 0) {
						continue;
					}
					$authorityRows[] = array(
						'id' => $authorityId,
						'holonId' => (int)$holon->getId(),
						'parentAuthorityId' => (int)$authority->get('IDauthority_parent'),
						'templateAuthorityId' => (int)$authority->get('IDauthority_template'),
						'label' => (string)$authority->get('label'),
						'description' => (string)$authority->get('description'),
						'isShell' => (bool)$authority->get('is_shell'),
						'isLocal' => (bool)$authority->get('is_local'),
						'templateOriginLost' => (bool)$authority->get('template_origin_lost'),
					);

					$authorityRules = new \dbObject\ArrayRule();
					$authorityRules->loadForAuthority($authorityId);
					foreach ($authorityRules as $rule) {
						$ruleId = (int)$rule->getId();
						if ($ruleId <= 0 || isset($exportedRuleIds[$ruleId])) {
							continue;
						}
						$exportedRuleIds[$ruleId] = true;
						$ruleRows[] = array(
							'id' => $ruleId,
							'authorityId' => $authorityId,
							'title' => (string)$rule->get('title'),
							'intention' => (string)$rule->get('intention'),
							'description' => (string)$rule->get('description'),
							'scope' => (string)$rule->get('scope'),
							'reviewDate' => $rule->get('review_date') instanceof \DateTimeInterface ? $rule->get('review_date')->format('Y-m-d') : (string)$rule->get('review_date'),
							'expirationDate' => $rule->get('expiration_date') instanceof \DateTimeInterface ? $rule->get('expiration_date')->format('Y-m-d') : (string)$rule->get('expiration_date'),
						);
					}
				}

				$localRules = new \dbObject\ArrayRule();
				$localRules->loadForHolon((int)$holon->getId());
				foreach ($localRules as $rule) {
					$ruleId = (int)$rule->getId();
					if ($ruleId <= 0 || isset($exportedRuleIds[$ruleId])) {
						continue;
					}
					$exportedRuleIds[$ruleId] = true;
					$ruleRows[] = array(
						'id' => $ruleId,
						'holonId' => (int)$holon->getId(),
						'title' => (string)$rule->get('title'),
						'intention' => (string)$rule->get('intention'),
						'description' => (string)$rule->get('description'),
						'scope' => \dbObject\Rule::SCOPE_LOCAL,
						'reviewDate' => $rule->get('review_date') instanceof \DateTimeInterface ? $rule->get('review_date')->format('Y-m-d') : (string)$rule->get('review_date'),
						'expirationDate' => $rule->get('expiration_date') instanceof \DateTimeInterface ? $rule->get('expiration_date')->format('Y-m-d') : (string)$rule->get('expiration_date'),
					);
				}
			}

			$propertyDefinitions = array();
			foreach (array_values($propertyDefinitionIds) as $propertyId) {
				$property = new \dbObject\Property();
				if (!$property->load($propertyId)) {
					continue;
				}

				$definition = array(
					'id' => (int)$property->getId(),
					'name' => (string)$property->get('name'),
					'shortname' => (string)$property->get('shortname'),
					'formatId' => (int)$property->get('IDpropertyformat'),
				);

				if (trim((string)$property->get('listitemtype')) !== '') {
					$definition['listItemType'] = (string)$property->get('listitemtype');
				}

				$listHolonTypeIds = \dbObject\Property::parseHolonTypeIds($property->get('listholontypeids'));
				if (count($listHolonTypeIds) > 0) {
					$definition['listHolonTypeIds'] = $listHolonTypeIds;
				}

				if ((int)$property->get('position') > 0) {
					$definition['position'] = (int)$property->get('position');
				}

				if (!(bool)$property->get('active')) {
					$definition['active'] = false;
				}

				$propertyDefinitions[] = $definition;
			}

			usort($holonRows, function ($left, $right) {
				$leftParentId = (int)($left['parentId'] ?? 0);
				$rightParentId = (int)($right['parentId'] ?? 0);
				if ($leftParentId === $rightParentId) {
					return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
				}

				return $leftParentId <=> $rightParentId;
			});

			usort($propertyDefinitions, function ($left, $right) {
				return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
			});

			$holonRowsById = array();
			foreach ($holonRows as $row) {
				$holonRowsById[(int)$row['id']] = $row;
			}

			$childrenByParentId = array();
			foreach ($holonRows as $row) {
				$parentId = (int)($row['parentId'] ?? 0);
				if ($parentId <= 0 || !isset($holonRowsById[$parentId])) {
					continue;
				}

				if (!isset($childrenByParentId[$parentId])) {
					$childrenByParentId[$parentId] = array();
				}

				$childrenByParentId[$parentId][] = (int)$row['id'];
			}

			$buildNode = function ($holonId) use (&$buildNode, $holonRowsById, $childrenByParentId) {
				if (!isset($holonRowsById[$holonId])) {
					return null;
				}

				$node = $holonRowsById[$holonId];
				unset($node['parentId']);

				if (isset($childrenByParentId[$holonId])) {
					$children = array();
					foreach ($childrenByParentId[$holonId] as $childId) {
						$childNode = $buildNode((int)$childId);
						if (is_array($childNode)) {
							$children[] = $childNode;
						}
					}

					if (count($children) > 0) {
						$node['children'] = $children;
					}
				}

				return $node;
			};

			$holonTree = array();
			foreach ($holonRows as $row) {
				$holonId = (int)($row['id'] ?? 0);
				$parentId = (int)($row['parentId'] ?? 0);
				if ($holonId <= 0) {
					continue;
				}

				if ($parentId > 0 && isset($holonRowsById[$parentId])) {
					continue;
				}

				$node = $buildNode($holonId);
				if (is_array($node)) {
					$holonTree[] = $node;
				}
			}

			return array(
				'holons' => $holonTree,
				'propertyDefinitions' => $propertyDefinitions,
				'authorities' => $authorityRows,
				'rules' => $ruleRows,
			);
		}

		public function isTemplateAvailableInContext(\dbObject\Holon $template, $contextHolonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			if (!$rootHolon || !$template->isTemplateNode((int)$rootHolon->getId())) {
				return false;
			}

			$pathIds = array_map(function ($holon) {
				return (int)$holon->getId();
			}, $this->getTemplateContextPathHolons($contextHolonId));

			return in_array((int)$template->get('IDholon_parent'), $pathIds, true);
		}

		protected function buildEditorPropertyFormats($formats)
		{
			$formatMap = array();

			if ($formats) {
				foreach ($formats as $format) {
					$formatId = (int)$format->getId();
					if ($formatId <= 0) {
						continue;
					}

					$formatMap[$formatId] = array(
						'id' => $formatId,
						'name' => (string)$format->get('name'),
					);
				}
			}

			foreach (\dbObject\PropertyFormat::getBuiltinFormats() as $builtinFormat) {
				$formatId = (int)$builtinFormat['id'];
				if ($formatId <= 0) {
					continue;
				}

				if (!isset($formatMap[$formatId]) || trim((string)$formatMap[$formatId]['name']) === '') {
					$formatMap[$formatId] = array(
						'id' => $formatId,
						'name' => (string)$builtinFormat['name'],
					);
				}
			}

			ksort($formatMap);
			return array_values($formatMap);
		}

		protected function normalizeTemplateEditorScope($scope = 'contextual')
		{
			$scope = strtolower(trim((string)$scope));
			if ($scope === 'global') {
				$scope = 'descendants';
			}
			return in_array($scope, array('contextual', 'children', 'descendants'), true) ? $scope : 'contextual';
		}

		public function getHolonTemplateEditorData($contextHolonId = 0, $scope = 'contextual')
		{
			$scope = $this->normalizeTemplateEditorScope($scope);
			$rootHolon = $this->getStructuralRootHolon();
			$contextHolon = $this->getTemplateContextHolon($contextHolonId);
			if (!$rootHolon) {
				return array(
					'organizationId' => (int)$this->getId(),
					'organizationName' => (string)$this->get('name'),
					'scope' => $scope,
					'rootHolonId' => 0,
					'contextHolonId' => 0,
					'contextHolonName' => '',
					'contextHolonLabel' => '',
					'types' => array(),
					'formats' => array(),
					'listItemTypes' => \dbObject\Property::getTemplateListItemTypeOptions(),
					'permissionCatalog' => \dbObject\Permission::getEditorCatalog(),
					'permissionRanges' => \dbObject\HolonPermission::getEditorRangeCatalog(),
					'templateCatalog' => array(),
					'projectCatalog' => array(),
					'authorityCatalog' => array(),
					'authorityParentCatalog' => array(),
					'authorityCanCreateRoot' => true,
					'templates' => array(),
				);
			}

			$types = new \dbObject\ArrayTypeHolon();
			$types->load(array(
				'orderBy' => array(
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			$formats = new \dbObject\ArrayPropertyFormat();
			$formats->load(array(
				'orderBy' => array(
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			$data = array(
				'organizationId' => (int)$this->getId(),
				'organizationName' => (string)$this->get('name'),
				'scope' => $scope,
				'rootHolonId' => (int)$rootHolon->getId(),
				'contextHolonId' => $contextHolon ? (int)$contextHolon->getId() : 0,
				'contextHolonName' => $contextHolon ? $contextHolon->getDisplayName() : '',
				'contextHolonLabel' => $contextHolon ? $contextHolon->getTemplateLabel() : '',
				'types' => array(),
				'formats' => array(),
				'listItemTypes' => \dbObject\Property::getTemplateListItemTypeOptions(),
				'permissionCatalog' => \dbObject\Permission::getEditorCatalog(),
				'permissionRanges' => \dbObject\HolonPermission::getEditorRangeCatalog(),
				'templateCatalog' => array(),
				'projectCatalog' => $this->getProjectListEditorCatalog(),
				'authorityCatalog' => $this->getAuthorityListEditorCatalog(),
				'authorityParentCatalog' => array(),
				'authorityCanCreateRoot' => true,
				'templates' => array(),
				'canAddTemplateProperties' => $contextHolon ? $contextHolon->isAllowed('CAN_ADD_TEMPLATE_PROPERTIES') : false,
			);

			foreach ($types as $type) {
				$data['types'][] = array(
					'id' => (int)$type->getId(),
					'name' => (string)$type->get('name'),
					'hasTemplate' => (bool)$type->get('hastemplate'),
					'hasChild' => (bool)$type->get('haschild'),
				);
			}

			$data['formats'] = $this->buildEditorPropertyFormats($formats);

			$templateCatalogSource = $scope === 'descendants'
				? $this->getAllTemplateDefinitionHolons()
				: $this->getAvailableTemplateDefinitionHolons($contextHolon ? (int)$contextHolon->getId() : 0);
			$scopeContextHolonIds = $contextHolon ? array((int)$contextHolon->getId()) : array();
			if ($scope === 'children' && $contextHolon) {
				$scopeContextHolonIds = array((int)$contextHolon->getId());
				foreach ($contextHolon->getChildren() as $childHolon) {
					if (!$childHolon->isTemplateNode((int)$rootHolon->getId())) {
						$scopeContextHolonIds[] = (int)$childHolon->getId();
					}
				}
			} elseif ($scope === 'descendants' && $contextHolon) {
				$scopeContextHolonIds = array();
				$collectStructuralHolonIds = function ($holon) use (&$collectStructuralHolonIds, &$scopeContextHolonIds, $rootHolon) {
					$holonId = (int)$holon->getId();
					if ($holonId <= 0 || in_array($holonId, $scopeContextHolonIds, true)) {
						return;
					}
					$scopeContextHolonIds[] = $holonId;
					foreach ($holon->getChildren() as $childHolon) {
						if (!$childHolon->isTemplateNode((int)$rootHolon->getId())) {
							$collectStructuralHolonIds($childHolon);
						}
					}
				};
				$collectStructuralHolonIds($contextHolon);
			}
			$scopeContextHolonIdMap = count($scopeContextHolonIds) > 0
				? array_fill_keys(array_map('intval', $scopeContextHolonIds), true)
				: array();
			$templateTreeSource = array_values(array_filter($this->getAllTemplateDefinitionHolons(), function ($template) use ($scopeContextHolonIdMap) {
				return isset($scopeContextHolonIdMap[(int)$template->get('IDholon_parent')]);
			}));
			$definitionHolonMetaCache = array();

			$resolveDefinitionHolonMeta = function ($definitionHolonId) use (&$definitionHolonMetaCache) {
				$definitionHolonId = (int)$definitionHolonId;
				if ($definitionHolonId <= 0) {
					return array(
						'id' => 0,
						'name' => '',
						'label' => '',
					);
				}

				if (isset($definitionHolonMetaCache[$definitionHolonId])) {
					return $definitionHolonMetaCache[$definitionHolonId];
				}

				$definitionHolon = new \dbObject\Holon();
				if (!$definitionHolon->load($definitionHolonId)) {
					$definitionHolonMetaCache[$definitionHolonId] = array(
						'id' => $definitionHolonId,
						'name' => '',
						'label' => '',
					);
					return $definitionHolonMetaCache[$definitionHolonId];
				}

				$definitionHolonMetaCache[$definitionHolonId] = array(
					'id' => $definitionHolonId,
					'name' => $definitionHolon->getDisplayName(),
					'label' => $definitionHolon->getTemplateLabel(),
				);
				return $definitionHolonMetaCache[$definitionHolonId];
			};

			$templateNodes = array();
			$childrenByParent = array();
			foreach ($templateCatalogSource as $template) {
				$definitionHolonMeta = $resolveDefinitionHolonMeta((int)$template->get('IDholon_parent'));
				$templateAdminBounds = $template->getEffectiveTemplateAdminBounds();

				$data['templateCatalog'][] = array_merge(array(
					'id' => (int)$template->getId(),
					'name' => $template->getDisplayName(),
					'typeId' => (int)$template->get('IDtypeholon'),
					'typeLabel' => $template->getTypeLabel(),
					'color' => (string)$template->get('color'),
					'visible' => (bool)$template->get('visible'),
					'mandatory' => (bool)$template->get('mandatory'),
					'lockedName' => (bool)$template->get('lockedname'),
					'unique' => (bool)$template->get('unique'),
					'link' => (bool)$template->get('link'),
					'adminParent' => (bool)$template->get('adminparent'),
					'adminMin' => $templateAdminBounds['min'],
					'adminMax' => $templateAdminBounds['max'],
					'lockedAdminMin' => !empty($templateAdminBounds['minLocked']),
					'lockedAdminMax' => !empty($templateAdminBounds['maxLocked']),
					'inheritsFromId' => (int)$template->get('IDholon_template'),
					'definedInId' => (int)$definitionHolonMeta['id'],
					'definedInName' => (string)$definitionHolonMeta['name'],
					'definedInLabel' => (string)$definitionHolonMeta['label'],
					'properties' => $template->getTemplatePropertyDefinitions(),
				), $this->getHolonIllustrationData($template));
			}

			foreach ($templateTreeSource as $template) {
				$templateNode = $template->toTemplateEditorNodeArray((int)$rootHolon->getId());
				$templateNode['canAddProperties'] = $template->isAllowed('CAN_ADD_TEMPLATE_PROPERTIES');
				$definitionHolonMeta = $resolveDefinitionHolonMeta((int)$template->get('IDholon_parent'));
				$templateNode['definedInId'] = (int)$definitionHolonMeta['id'];
				$templateNode['definedInName'] = (string)$definitionHolonMeta['name'];
				$templateNode['definedInLabel'] = (string)$definitionHolonMeta['label'];
				$templateNodes[(int)$template->getId()] = $templateNode;
				$parentId = (int)$templateNode['inheritsFromId'];

				if (!isset($childrenByParent[$parentId])) {
					$childrenByParent[$parentId] = array();
				}

				$childrenByParent[$parentId][] = (int)$template->getId();
			}

			$buildTemplateBranch = function ($parentId) use (&$buildTemplateBranch, $childrenByParent, $templateNodes) {
				$branch = array();
				if (!isset($childrenByParent[$parentId])) {
					return $branch;
				}

				foreach ($childrenByParent[$parentId] as $childId) {
					if (!isset($templateNodes[$childId])) {
						continue;
					}

					$node = $templateNodes[$childId];
					$node['children'] = $buildTemplateBranch($childId);
					$branch[] = $node;
				}

				return $branch;
			};

			$data['templates'] = $buildTemplateBranch(0);

			foreach ($templateNodes as $templateId => $templateNode) {
				$parentId = (int)$templateNode['inheritsFromId'];
				if ($parentId === 0 || isset($templateNodes[$parentId])) {
					continue;
				}

				$templateNode['children'] = $buildTemplateBranch((int)$templateId);
				$data['templates'][] = $templateNode;
			}

			return $data;
		}

		protected function buildHolonDefinitionEditorNode(\dbObject\Holon $holon, $rootHolonId)
		{
			$node = $holon->toTemplateEditorNodeArray((int)$rootHolonId);
			$node['properties'] = array_map(function ($property) use ($holon) {
				$property['canEditValue'] = empty($property['effectiveLocked'])
					&& $holon->isAllowed('CAN_EDIT_HOLON_PROPERTIES');
				$property['canDelete'] = empty($property['inheritedMandatory'])
					&& $holon->isAllowed('CAN_DELETE_HOLON_PROPERTIES');
				return $property;
			}, $holon->getTemplatePropertyDefinitions());
			$node['children'] = array();
			$node['shareAsTemplate'] = trim((string)$holon->get('templatename')) !== '';
			$node['publicTemplateName'] = trim((string)$holon->get('templatename'));
			$node['canAddProperties'] = $holon->isAllowed('CAN_ADD_HOLON_PROPERTIES');

			return $node;
		}

		protected function normalizePropertyDefinitionForPermission(array $definition, $position)
		{
			$formatId = (int)($definition['formatId'] ?? 0);
			$value = \dbObject\PropertyFormat::normalizeValueForStorage($formatId, $definition['value'] ?? '');
			if (!\dbObject\PropertyFormat::isHtmlFormat($formatId)) {
				$value = trim((string)$value);
			}

			$listHolonTypeIds = \dbObject\Property::parseHolonTypeIds($definition['listHolonTypeIds'] ?? array());
			sort($listHolonTypeIds);

			return array(
				'name' => trim((string)($definition['name'] ?? '')),
				'formatId' => $formatId,
				'listItemType' => \dbObject\Property::normalizeTemplateListItemType($definition['listItemType'] ?? ''),
				'listHolonTypeIds' => $listHolonTypeIds,
				'mandatory' => !empty($definition['mandatory']),
				'locked' => !empty($definition['locked']),
				'isLocal' => !empty($definition['isLocal']),
				'value' => $value,
				'position' => (int)$position,
			);
		}

		protected function getPropertyDefinitionPermissionOperations(array $existingDefinitions, array $submittedDefinitions)
		{
			$existingById = array();
			foreach (array_values($existingDefinitions) as $position => $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0) {
					$existingById[$propertyId] = array(
						'definition' => $definition,
						'position' => $position,
					);
				}
			}

			$operations = array();
			$submittedIds = array();
			foreach (array_values($submittedDefinitions) as $position => $definition) {
				if (!is_array($definition) || trim((string)($definition['name'] ?? '')) === '') {
					continue;
				}

				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId <= 0 || !isset($existingById[$propertyId])) {
					$operations['add'] = true;
					continue;
				}

				$submittedIds[$propertyId] = true;
				if (
					$this->normalizePropertyDefinitionForPermission($existingById[$propertyId]['definition'], $existingById[$propertyId]['position'])
					!== $this->normalizePropertyDefinitionForPermission($definition, $position)
				) {
					$operations['edit'] = true;
				}
			}

			foreach ($existingById as $propertyId => $existing) {
				if (!isset($submittedIds[$propertyId])) {
					$operations['delete'] = true;
				}
			}

			return array_keys($operations);
		}

		protected function getRemovedPropertyDefinitionIds(array $payload)
		{
			$removedPropertyIds = array();
			foreach (is_array($payload['removedPropertyIds'] ?? null) ? $payload['removedPropertyIds'] : array() as $propertyId) {
				$propertyId = (int)$propertyId;
				if ($propertyId > 0) {
					$removedPropertyIds[$propertyId] = true;
				}
			}

			return $removedPropertyIds;
		}

		protected function excludeRemovedPropertyDefinitions(array $definitions, array $removedPropertyIds)
		{
			if (count($removedPropertyIds) === 0) {
				return array_values($definitions);
			}

			return array_values(array_filter($definitions, function ($definition) use ($removedPropertyIds) {
				return !is_array($definition) || !isset($removedPropertyIds[(int)($definition['id'] ?? 0)]);
			}));
		}

		protected function canApplyPropertyDefinitionChanges(\dbObject\Holon $permissionHolon, array $operations, $propertyScope)
		{
			$propertyScope = strtoupper(trim((string)$propertyScope));
			$operationLabels = array(
				'ADD' => 'ajouter',
				'EDIT' => 'modifier',
				'DELETE' => 'supprimer',
			);
			foreach ($operations as $operation) {
				$operation = strtoupper(trim((string)$operation));
				if (!in_array($operation, array('ADD', 'EDIT', 'DELETE'), true)) {
					continue;
				}

				$permissionKey = 'CAN_' . $operation . '_' . $propertyScope . '_PROPERTIES';
				if (!$permissionHolon->isAllowed($permissionKey)) {
					return array(
						'status' => false,
						'message' => "Vous n'avez pas les droits pour " . $operationLabels[$operation] . ' les proprietes de ' . strtolower($propertyScope) . '.',
					);
				}
			}

			return array('status' => true);
		}

		protected function canEditSubmittedTemplatePropertyValues(\dbObject\Holon $holon, \dbObject\Holon $template, array $submittedValuesByPropertyId, array $propertyDefinitions)
		{
			$existingValuesByPropertyId = array();
			if ((int)$holon->getId() > 0) {
				foreach ($holon->getHolonProperties() as $holonProperty) {
					$existingValuesByPropertyId[(int)$holonProperty->get('IDproperty')] = $holonProperty->get('value');
				}
			}

			foreach ($propertyDefinitions as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				$formatId = (int)($definition['formatId'] ?? 0);
				if ($propertyId <= 0 || !array_key_exists($propertyId, $submittedValuesByPropertyId)) {
					continue;
				}

				$submittedValue = \dbObject\PropertyFormat::normalizeValueForStorage($formatId, $submittedValuesByPropertyId[$propertyId]);
				$existingValue = \dbObject\PropertyFormat::normalizeValueForStorage($formatId, $existingValuesByPropertyId[$propertyId] ?? '');
				if (!\dbObject\PropertyFormat::isHtmlFormat($formatId)) {
					$submittedValue = trim((string)$submittedValue);
					$existingValue = trim((string)$existingValue);
				}

				if ($submittedValue === $existingValue || $template->isAllowed('CAN_EDIT_TEMPLATE_PROPERTIES')) {
					continue;
				}

				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour modifier les proprietes de template.",
				);
			}

			return array('status' => true);
		}

		public function getHolonDefinitionEditorData($holonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;

			if (!$rootHolon || $holonId <= 0) {
				return null;
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($holonId)
				|| !$this->containsHolon($holon)
				|| (int)$holon->get('IDtypeholon') !== 4
			) {
				return null;
			}

			$types = new \dbObject\ArrayTypeHolon();
			$types->load(array(
				'orderBy' => array(
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			$formats = new \dbObject\ArrayPropertyFormat();
			$formats->load(array(
				'orderBy' => array(
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			$data = array(
				'organizationId' => (int)$this->getId(),
				'organizationName' => (string)$this->get('name'),
				'rootHolonId' => (int)$rootHolon->getId(),
				'contextHolonId' => (int)$holon->getId(),
				'contextHolonName' => $holon->getDisplayName(),
				'contextHolonLabel' => $holon->getTypeLabel(),
				'editorMode' => 'holon-definition',
				'targetHolonId' => (int)$holon->getId(),
				'types' => array(),
				'formats' => array(),
				'listItemTypes' => \dbObject\Property::getTemplateListItemTypeOptions(),
				'permissionCatalog' => \dbObject\Permission::getEditorCatalog(),
				'permissionRanges' => \dbObject\HolonPermission::getEditorRangeCatalog(),
				'templateCatalog' => array(),
				'projectCatalog' => $this->getProjectListEditorCatalog(),
				'authorityCatalog' => $this->getAuthorityListEditorCatalog(),
				'authorityParentCatalog' => array(),
				'authorityCanCreateRoot' => true,
				'templates' => array(
					$this->buildHolonDefinitionEditorNode($holon, (int)$rootHolon->getId()),
				),
			);

			foreach ($types as $type) {
				$data['types'][] = array(
					'id' => (int)$type->getId(),
					'name' => (string)$type->get('name'),
					'hasTemplate' => (bool)$type->get('hastemplate'),
					'hasChild' => (bool)$type->get('haschild'),
				);
			}

			$data['formats'] = $this->buildEditorPropertyFormats($formats);

			return $data;
		}

		// Construit liste holons
		protected function buildSelectableHolonCatalog(\dbObject\Holon $holon, array &$catalog, $rootHolonId, array $path = array())
		{
			$rootHolonId = (int)$rootHolonId;
			if ((int)$holon->getId() !== $rootHolonId && $holon->isTemplateNode($rootHolonId)) {
				return;
			}

			$currentPath = $path;
			$currentPath[] = $holon->getDisplayName();

			$catalog[] = array(
				'id' => (int)$holon->getId(),
				'name' => $holon->getDisplayName(),
				'typeId' => (int)$holon->get('IDtypeholon'),
				'typeLabel' => $holon->getTypeLabel(),
				'pathLabel' => implode(' > ', $currentPath),
			);

			foreach ($holon->getChildren() as $child) {
				$this->buildSelectableHolonCatalog($child, $catalog, $rootHolonId, $currentPath);
			}
		}

		protected function getProjectListEditorCatalog()
		{
			$projects = new \dbObject\ArrayProject();
			$projects->loadForOrganization((int)$this->getId());
			$catalog = array();

			foreach ($projects as $project) {
				$projectId = (int)$project->getId();
				if ($projectId <= 0) {
					continue;
				}

				$holon = $project->getHolon();
				$catalog[] = array(
					'id' => $projectId,
					'title' => trim((string)$project->get('title')),
					'holonLabel' => $holon ? $holon->getFullDisplayName() : '',
					'importance' => \dbObject\Project::normalizeLevel($project->get('importance')),
					'priority' => \dbObject\Project::normalizeLevel($project->get('priority')),
				);
			}

			usort($catalog, function ($left, $right) {
				$leftImportance = $left['importance'] === null ? -1 : (int)$left['importance'];
				$rightImportance = $right['importance'] === null ? -1 : (int)$right['importance'];
				if ($leftImportance !== $rightImportance) {
					return $rightImportance <=> $leftImportance;
				}

				$leftPriority = $left['priority'] === null ? PHP_INT_MAX : (int)$left['priority'];
				$rightPriority = $right['priority'] === null ? PHP_INT_MAX : (int)$right['priority'];
				if ($leftPriority !== $rightPriority) {
					return $leftPriority <=> $rightPriority;
				}

				return strcasecmp((string)$left['title'], (string)$right['title']);
			});

			return $catalog;
		}

		protected function getAuthorityListEditorCatalog()
		{
			return \dbObject\Authority::getEditorCatalogForOrganization((int)$this->getId());
		}

		protected function getAuthorityParentEditorCatalog(?\dbObject\Holon $parentHolon = null)
		{
			$authorityParentHolon = $parentHolon instanceof \dbObject\Holon
				? $parentHolon->getAuthorityParentHolon(true)
				: null;
			$parentHolonId = $authorityParentHolon instanceof \dbObject\Holon
				? (int)$authorityParentHolon->getId()
				: 0;
			if ($parentHolonId <= 0) {
				return array();
			}

			return array_values(array_filter($this->getAuthorityListEditorCatalog(), function ($authority) use ($parentHolonId) {
				return (int)($authority['holonId'] ?? 0) === $parentHolonId;
			}));
		}

		protected function canMoveHolonToParent(\dbObject\Holon $holon, \dbObject\Holon $targetParent, ?\dbObject\Holon $rootHolon = null)
		{
			$targetTypeId = (int)$targetParent->get('IDtypeholon');
			if (!in_array($targetTypeId, array(2, 3, 4), true)) {
				return false;
			}

			if (!$this->containsHolon($targetParent)) {
				return false;
			}

			if (!$targetParent->canEdit()) {
				return false;
			}

			if ($targetParent->isDescendantOf((int)$holon->getId(), true)) {
				return false;
			}

			$rootHolon = $rootHolon ?: $this->getStructuralRootHolon();
			if (
				$rootHolon
				&& (int)$targetParent->getId() !== (int)$rootHolon->getId()
				&& $targetParent->isTemplateNode((int)$rootHolon->getId())
			) {
				return false;
			}

			$templateId = (int)$holon->get('IDholon_template');
			if ($templateId <= 0) {
				return true;
			}

			$template = new \dbObject\Holon();
			if (!$template->load($templateId)) {
				return false;
			}

			if (
				$rootHolon
				&& !$this->isTemplateAvailableInContext($template, (int)$targetParent->getId())
			) {
				return false;
			}

			return $this->isTemplateAvailableForHolonCreation($template, $targetParent, (int)$holon->getId());
		}

		protected function buildMovableHolonDestinationCatalog(\dbObject\Holon $candidate, array &$catalog, $rootHolonId, \dbObject\Holon $movingHolon, array $path = array())
		{
			$rootHolonId = (int)$rootHolonId;
			if ((int)$candidate->getId() !== $rootHolonId && $candidate->isTemplateNode($rootHolonId)) {
				return;
			}

			if ($candidate->isDescendantOf((int)$movingHolon->getId(), true)) {
				return;
			}

			$currentPath = $path;
			$currentPath[] = $candidate->getDisplayName();

			if ($this->canMoveHolonToParent($movingHolon, $candidate)) {
				$catalog[] = array(
					'id' => (int)$candidate->getId(),
					'name' => $candidate->getDisplayName(),
					'typeId' => (int)$candidate->get('IDtypeholon'),
					'typeLabel' => $candidate->getTypeLabel(),
					'pathLabel' => implode(' > ', $currentPath),
					'isCurrentParent' => (int)$candidate->getId() === (int)$movingHolon->get('IDholon_parent'),
				);
			}

			foreach ($candidate->getChildren() as $child) {
				$this->buildMovableHolonDestinationCatalog($child, $catalog, $rootHolonId, $movingHolon, $currentPath);
			}
		}

		protected function buildDocumentDestinationCatalog(\dbObject\Holon $candidate, array &$catalog, array $path = array(), int $currentUserId = 0)
		{
			if (!(bool)$candidate->get('active') || !(bool)$candidate->get('visible')) {
				return;
			}

			$typeId = (int)$candidate->get('IDtypeholon');
			$currentPath = $path;
			$displayName = trim((string)$candidate->getDisplayName());

			if ($typeId !== 4 && $displayName !== '') {
				$currentPath[] = $displayName;

				if (
					$currentUserId > 0
					&& in_array($typeId, array(1, 2, 3), true)
					&& \dbObject\Document::canCreateInOrganizationContext(
						(int)$this->getId(),
						(int)$candidate->getId(),
						$currentUserId,
						0,
						true
					)
				) {
					$catalog[] = array(
						'key' => 'holon-' . (int)$candidate->getId(),
						'holonId' => (int)$candidate->getId(),
						'name' => $displayName,
						'typeId' => $typeId,
						'typeLabel' => $candidate->getTypeLabel(),
						'pathLabel' => implode(' > ', $currentPath),
					);
				}
			}

			foreach ($candidate->getChildren() as $child) {
				$this->buildDocumentDestinationCatalog($child, $catalog, $currentPath, $currentUserId);
			}
		}

		public function getHolonMoveEditorData($holonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;
			$holon = null;
			$currentParent = null;

			$data = array(
				'organizationId' => (int)$this->getId(),
				'organizationName' => (string)$this->get('name'),
				'rootHolonId' => $rootHolon ? (int)$rootHolon->getId() : 0,
				'holonId' => 0,
				'canMove' => false,
				'holon' => null,
				'currentParent' => null,
				'destinations' => array(),
			);

			if (!$rootHolon || $holonId <= 0) {
				return $data;
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($holonId)
				|| !$this->containsHolon($holon)
				|| $holon->isTemplateNode((int)$rootHolon->getId())
				|| !in_array((int)$holon->get('IDtypeholon'), array(1, 2, 3), true)
			) {
				return $data;
			}

			$currentParent = $holon->getParentHolon();
			$data['holonId'] = (int)$holon->getId();
			$data['holon'] = array(
				'id' => (int)$holon->getId(),
				'name' => $holon->getDisplayName(),
				'typeId' => (int)$holon->get('IDtypeholon'),
				'typeLabel' => $holon->getTemplateLabel(),
				'parentId' => (int)$holon->get('IDholon_parent'),
				'templateId' => (int)$holon->get('IDholon_template'),
			);

			if ($currentParent) {
				$data['currentParent'] = array(
					'id' => (int)$currentParent->getId(),
					'name' => $currentParent->getDisplayName(),
					'typeId' => (int)$currentParent->get('IDtypeholon'),
					'typeLabel' => $currentParent->getTemplateLabel(),
					'pathLabel' => implode(' > ', array_map(function ($pathHolon) {
						return $pathHolon->getDisplayName();
					}, $currentParent->getPathHolons())),
				);
			}

			$data['canMove'] = $currentParent && $holon->canEdit() && $currentParent->canEdit();
			if (!$data['canMove']) {
				return $data;
			}

			$this->buildMovableHolonDestinationCatalog($rootHolon, $data['destinations'], (int)$rootHolon->getId(), $holon);

			return $data;
		}

		protected function sortDocumentMoveDestinations(array &$destinations)
		{
			usort($destinations, function ($left, $right) {
				$leftKey = (string)($left['key'] ?? '');
				$rightKey = (string)($right['key'] ?? '');
				if ($leftKey === 'organization' || $rightKey === 'organization') {
					if ($leftKey === $rightKey) {
						return 0;
					}

					return $leftKey === 'organization' ? -1 : 1;
				}

				$leftPath = trim((string)($left['pathLabel'] ?? $left['name'] ?? ''));
				$rightPath = trim((string)($right['pathLabel'] ?? $right['name'] ?? ''));
				$comparison = strnatcasecmp($leftPath, $rightPath);
				if ($comparison !== 0) {
					return $comparison;
				}

				$leftTypeId = (int)($left['typeId'] ?? 0);
				$rightTypeId = (int)($right['typeId'] ?? 0);
				if ($leftTypeId !== $rightTypeId) {
					return $leftTypeId <=> $rightTypeId;
				}

				return strnatcasecmp(
					trim((string)($left['name'] ?? '')),
					trim((string)($right['name'] ?? ''))
				);
			});
		}

		public function getDocumentMoveEditorData($documentId = 0)
		{
			$rootHolon = $this->getEnabledStructuralRootHolon();
			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
			$documentId = (int)$documentId;
			$document = new \dbObject\Document();
			$organizationLabel = trim((string)$this->get('name'));

			$data = array(
				'organizationId' => (int)$this->getId(),
				'organizationName' => $organizationLabel,
				'documentId' => 0,
				'canMove' => false,
				'document' => null,
				'currentDestination' => null,
				'destinations' => array(),
			);

			if ($documentId <= 0) {
				return $data;
			}

			if (
				!$document->load($documentId)
				|| (int)$document->get('IDorganization') !== (int)$this->getId()
			) {
				return $data;
			}

			$currentHolonId = (int)$document->get('IDholon');
			$currentParentDocumentId = (int)$document->get('IDdocument_parent');
			$currentDestinationKey = $currentParentDocumentId > 0
				? 'folder-' . $currentParentDocumentId
				: ($currentHolonId > 0 ? 'holon-' . $currentHolonId : 'organization');
			$currentPathLabel = $document->getOrganizationContextLabel();
			if ($currentPathLabel === '') {
				$currentPathLabel = $organizationLabel;
			}

			$data['documentId'] = (int)$document->getId();
			$data['document'] = array(
				'id' => (int)$document->getId(),
				'title' => (string)$document->get('title'),
				'holonId' => $currentHolonId,
				'isFolder' => $document->isFolder(),
				'parentDocumentId' => $currentParentDocumentId,
			);
			$data['currentDestination'] = array(
				'key' => $currentDestinationKey,
				'holonId' => $currentHolonId,
				'parentDocumentId' => $currentParentDocumentId,
				'pathLabel' => $currentPathLabel,
			);
			$data['canMove'] = $document->canMoveInOrganizationContext((int)$this->getId(), $currentUserId);

			if (!$data['canMove']) {
				return $data;
			}

			if (
				$currentUserId > 0
				&& \dbObject\Document::canCreateInOrganizationContext(
					(int)$this->getId(),
					null,
					$currentUserId,
					0,
					true
				)
			) {
				$data['destinations'][] = array(
					'key' => 'organization',
					'holonId' => 0,
					'name' => $organizationLabel !== '' ? $organizationLabel : 'Organisation',
					'typeId' => 0,
					'typeLabel' => 'Organisation',
					'pathLabel' => $organizationLabel !== '' ? $organizationLabel : 'Organisation',
					'isCurrentDestination' => $currentHolonId <= 0,
				);
			}

			if ($rootHolon) {
				$this->buildDocumentDestinationCatalog(
					$rootHolon,
					$data['destinations'],
					$organizationLabel !== '' ? array($organizationLabel) : array('Organisation'),
					$currentUserId
				);
			}

			$folderDocuments = new \dbObject\ArrayDocument();
			$folderDocuments->loadVisibleForOrganization((int)$this->getId());
			foreach ($folderDocuments as $folderDocument) {
				if (
					!($folderDocument instanceof \dbObject\Document)
					|| !$folderDocument->isFolder()
				) {
					continue;
				}

				$folderId = (int)$folderDocument->getId();
				if ($folderId <= 0 || $folderId === (int)$document->getId()) {
					continue;
				}

				if (
					$currentUserId <= 0
					|| !\dbObject\Document::canCreateInOrganizationContext(
						(int)$this->getId(),
						(int)$folderDocument->get('IDholon') > 0 ? (int)$folderDocument->get('IDholon') : null,
						$currentUserId,
						$folderId,
						true
					)
				) {
					continue;
				}

				if ($document->isFolder() && $folderDocument->isDescendantOfDocument((int)$document->getId(), false)) {
					continue;
				}

				$folderPathLabel = $folderDocument->getOrganizationContextLabel();
				$folderName = trim((string)$folderDocument->get('title'));
				if ($folderName !== '') {
					$folderPathLabel = $folderPathLabel !== ''
						? ($folderPathLabel . ' > ' . $folderName)
						: $folderName;
				}

				$data['destinations'][] = array(
					'key' => 'folder-' . $folderId,
					'holonId' => (int)$folderDocument->get('IDholon'),
					'parentDocumentId' => $folderId,
					'name' => $folderName !== '' ? $folderName : ('Dossier #' . $folderId),
					'typeId' => -1,
					'typeLabel' => 'Dossier',
					'pathLabel' => $folderPathLabel,
					'isCurrentDestination' => $folderId === $currentParentDocumentId,
				);
			}

			foreach ($data['destinations'] as $index => $destination) {
				$data['destinations'][$index]['isCurrentDestination'] = (
					(string)($destination['key'] ?? '') === $currentDestinationKey
				);
			}

			$this->sortDocumentMoveDestinations($data['destinations']);

			return $data;
		}

		// Resout scope unique
		protected function resolveUniqueTemplateScopeHolon(\dbObject\Holon $contextHolon)
		{
			$current = $contextHolon;
			$guard = 0;

			while ($current && $guard < 100) {
				$typeId = (int)$current->get('IDtypeholon');
				if (in_array($typeId, array(2, 4), true)) {
					return $current;
				}

				$current = $current->getParentHolon();
				$guard += 1;
			}

			return $contextHolon;
		}

		// Liste heritage template
		protected function getTemplateLineageIds(\dbObject\Holon $template)
		{
			$lineageIds = array();
			$current = $template;
			$guard = 0;

			while ($current && (int)$current->getId() > 0 && $guard < 100) {
				$currentId = (int)$current->getId();
				if (in_array($currentId, $lineageIds, true)) {
					break;
				}

				$lineageIds[] = $currentId;

				$parentTemplateId = (int)$current->get('IDholon_template');
				if ($parentTemplateId <= 0) {
					break;
				}

				$parentTemplate = new \dbObject\Holon();
				if (!$parentTemplate->load($parentTemplateId)) {
					break;
				}

				$current = $parentTemplate;
				$guard += 1;
			}

			return $lineageIds;
		}

		// Compare famille template
		protected function templateMatchesUniqueFamily(\dbObject\Holon $selectedTemplate, \dbObject\Holon $instanceTemplate)
		{
			$resolveUniqueFamilyId = function (\dbObject\Holon $template) {
				$lineageIds = $this->getTemplateLineageIds($template);
				$uniqueFamilyId = 0;

				foreach ($lineageIds as $templateId) {
					$currentTemplate = new \dbObject\Holon();
					if (!$currentTemplate->load((int)$templateId)) {
						continue;
					}

					if ((bool)$currentTemplate->get('unique')) {
						$uniqueFamilyId = (int)$currentTemplate->getId();
					}
				}

				return $uniqueFamilyId;
			};

			$selectedFamilyId = (int)$resolveUniqueFamilyId($selectedTemplate);
			$instanceFamilyId = (int)$resolveUniqueFamilyId($instanceTemplate);

			if ($selectedFamilyId <= 0 || $instanceFamilyId <= 0) {
				return false;
			}

			return $selectedFamilyId === $instanceFamilyId;
		}

		// Parcourt scope unique
		protected function scopeHasTemplateInstance(\dbObject\Holon $scopeHolon, $templateId, $excludedHolonId = 0)
		{
			$templateId = (int)$templateId;
			$excludedHolonId = (int)$excludedHolonId;
			if ($templateId <= 0) {
				return false;
			}

			$selectedTemplate = new \dbObject\Holon();
			if (!$selectedTemplate->load($templateId)) {
				return false;
			}

			foreach ($scopeHolon->getChildren() as $child) {
				$childTemplateId = (int)$child->get('IDholon_template');
				$isVisibleTemplateOriginal = (bool)$child->get('visible')
					&& trim((string)$child->get('templatename')) !== '';
				if (
					(int)$child->getId() !== $excludedHolonId
					&& ($childTemplateId > 0 || $isVisibleTemplateOriginal)
				) {
					if ($childTemplateId === $templateId || ($isVisibleTemplateOriginal && (int)$child->getId() === $templateId)) {
						return true;
					}

					$instanceTemplate = new \dbObject\Holon();
					if (
						$instanceTemplate->load($isVisibleTemplateOriginal ? (int)$child->getId() : $childTemplateId)
						&& $this->templateMatchesUniqueFamily($selectedTemplate, $instanceTemplate)
					) {
						return true;
					}
				}

				if (
					(int)$child->get('IDtypeholon') === 3
					&& $this->scopeHasTemplateInstance($child, $templateId, $excludedHolonId)
				) {
					return true;
				}
			}

			return false;
		}

		// Filtre template unique
		protected function isTemplateAvailableForHolonCreation(\dbObject\Holon $template, \dbObject\Holon $contextHolon, $excludedHolonId = 0)
		{
			if (!(bool)$template->get('unique')) {
				return true;
			}

			$scopeHolon = $this->resolveUniqueTemplateScopeHolon($contextHolon);
			return !$this->scopeHasTemplateInstance($scopeHolon, (int)$template->getId(), $excludedHolonId);
		}

		// Prepare donnees editeur
		// Cherche enfant template
		protected function circleHasTemplateChild(\dbObject\Holon $parentHolon, $templateId)
		{
			$templateId = (int)$templateId;
			if ($templateId <= 0) {
				return false;
			}

			foreach ($parentHolon->getChildren() as $child) {
				if ((int)$child->get('IDholon_template') === $templateId) {
					return true;
				}
			}

			return false;
		}

		// Cree enfant obligatoire
		protected function createMandatoryTemplateChild(\dbObject\Holon $parentHolon, \dbObject\Holon $template, $rootHolonId, $userId = 0)
		{
			$child = new \dbObject\Holon();
			$child->set('name', $template->getDisplayName());
			$child->set('templatename', null);
			$child->set('IDtypeholon', (int)$template->get('IDtypeholon'));
			$child->set('IDholon_parent', (int)$parentHolon->getId());
			$child->set('IDholon_template', (int)$template->getId());
			$child->set('IDholon_org', (int)$rootHolonId);
			$child->set('IDorganization', null);
			$child->set('IDuser', (int)$userId > 0 ? (int)$userId : (int)$template->get('IDuser'));
			$child->set('active', true);
			$child->set('visible', true);
			$child->set('mandatory', false);
			$child->set('lockedname', false);
			$child->set('lockedicon', false);
			$child->set('lockedbanner', false);
			$child->set('unique', false);
			$child->set('link', false);
			$child->set('color', null);
			$child->set('icon', null);
			$child->set('banner', null);
			$child->save();

			if ((int)$child->getId() <= 0) {
				return null;
			}

			$child->syncEditorPropertyValues(array(), $template->getHolonCreationPropertyDefinitions());

			return $child;
		}

		// Ajoute enfants obligatoires
		protected function createMandatoryChildrenForCircle(\dbObject\Holon $circleHolon, $rootHolonId, $userId = 0, array $excludedTemplateIds = array())
		{
			$excludedTemplateIds = array_map('intval', $excludedTemplateIds);

			foreach ($this->getAvailableTemplateDefinitionHolons((int)$circleHolon->getId()) as $template) {
				$templateId = (int)$template->getId();
				if ($templateId <= 0 || !$template->get('mandatory')) {
					continue;
				}

				if (in_array($templateId, $excludedTemplateIds, true)) {
					continue;
				}

				$typeId = (int)$template->get('IDtypeholon');
				if (!in_array($typeId, array(1, 2, 3), true)) {
					continue;
				}

				if ($this->circleHasTemplateChild($circleHolon, $templateId)) {
					continue;
				}

				$this->createMandatoryTemplateChild($circleHolon, $template, $rootHolonId, $userId);
			}
		}

		public function getHolonCreationEditorData($contextHolonId = 0, $holonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;
			$editingHolon = null;
			$isTemplateEditing = false;
			if ($holonId > 0) {
				$editingHolon = new \dbObject\Holon();
				if (
					!$editingHolon->load($holonId)
					|| !$this->containsHolon($editingHolon)
				) {
					$editingHolon = null;
				} else {
					$isTemplateEditing = $editingHolon->isTemplateNode($rootHolon ? (int)$rootHolon->getId() : 0);
				}
			}

			$contextHolon = $editingHolon ? $editingHolon->getParentHolon() : $this->getTemplateContextHolon($contextHolonId);

			$data = array(
				'organizationId' => (int)$this->getId(),
				'organizationName' => (string)$this->get('name'),
				'rootHolonId' => $rootHolon ? (int)$rootHolon->getId() : 0,
				'mode' => $editingHolon ? 'edit' : 'create',
				'editorType' => $isTemplateEditing ? 'template' : 'holon',
				'holonId' => $editingHolon ? (int)$editingHolon->getId() : 0,
				'contextHolonId' => $contextHolon ? (int)$contextHolon->getId() : 0,
				'contextHolonName' => $contextHolon ? $contextHolon->getDisplayName() : '',
				'contextHolonLabel' => $contextHolon ? $contextHolon->getTemplateLabel() : '',
				'contextHolonTypeId' => $contextHolon ? (int)$contextHolon->get('IDtypeholon') : 0,
				'contextHolonTypeLabel' => $contextHolon ? $contextHolon->getTypeLabel() : '',
				'canCreate' => false,
				'canEdit' => false,
				'types' => array(),
				'formats' => array(),
				'listItemTypes' => \dbObject\Property::getTemplateListItemTypeOptions(),
				'canAddHolonProperties' => $editingHolon
					? $editingHolon->isAllowed('CAN_ADD_HOLON_PROPERTIES')
					: ($contextHolon ? $contextHolon->isAllowed('CAN_ADD_HOLON_PROPERTIES') : false),
				'permissionCatalog' => \dbObject\Permission::getEditorCatalog(),
				'permissionRanges' => \dbObject\HolonPermission::getEditorRangeCatalog(),
				'templateCatalog' => array(),
				'holonCatalog' => array(),
				'projectCatalog' => array(),
				'authorityCatalog' => array(),
				'authorityParentCatalog' => array(),
				'authorityCanCreateRoot' => $editingHolon && (int)$editingHolon->get('IDtypeholon') === 4,
				'holon' => null,
			);

			if (!$rootHolon || !$contextHolon) {
				return $data;
			}

			$data['canCreate'] = !$isTemplateEditing && $contextHolon->canEdit() && in_array((int)$contextHolon->get('IDtypeholon'), array(2, 3, 4), true);
			$data['canEdit'] = $editingHolon && $editingHolon->canEdit() && in_array((int)$editingHolon->get('IDtypeholon'), array(1, 2, 3), true);

			$templateContextPathRank = array_flip(array_map(static function ($pathHolon) {
				return (int)$pathHolon->getId();
			}, $contextHolon->getPathHolons(true)));
			$typeLabelsById = array();
			foreach ($this->getAvailableTemplateDefinitionHolons((int)$contextHolon->getId()) as $template) {
				$typeId = (int)$template->get('IDtypeholon');
				if ($typeId <= 0 || $typeId === 4) {
					continue;
				}

				if ($isTemplateEditing) {
					if ((int)$template->getId() === (int)$editingHolon->getId()) {
						continue;
					}

					if ((int)$editingHolon->get('IDtypeholon') !== $typeId) {
						continue;
					}
				}

				if (
					!$isTemplateEditing
					&& !$this->isTemplateAvailableForHolonCreation($template, $contextHolon, $editingHolon ? (int)$editingHolon->getId() : 0)
				) {
					continue;
				}

				$definitionHolon = new \dbObject\Holon();
				$definitionHolonName = '';
				$definitionHolonLabel = '';
				if ($definitionHolon->load((int)$template->get('IDholon_parent'))) {
					$definitionHolonName = $definitionHolon->getDisplayName();
					$definitionHolonLabel = $definitionHolon->getTemplateLabel();
				}
				$templateAdminBounds = $template->getEffectiveTemplateAdminBounds();

				$data['templateCatalog'][] = array_merge(array(
					'id' => (int)$template->getId(),
					'name' => $template->getDisplayName(),
					'typeId' => $typeId,
					'typeLabel' => $template->getTypeLabel(),
					'color' => (string)$template->get('color'),
					'visible' => (bool)$template->get('visible'),
					'mandatory' => (bool)$template->get('mandatory'),
					'lockedName' => (bool)$template->get('lockedname'),
					'unique' => (bool)$template->get('unique'),
					'link' => (bool)$template->get('link'),
					'adminParent' => (bool)$template->get('adminparent'),
					'adminMin' => $templateAdminBounds['min'],
					'adminMax' => $templateAdminBounds['max'],
					'lockedAdminMin' => !empty($templateAdminBounds['minLocked']),
					'lockedAdminMax' => !empty($templateAdminBounds['maxLocked']),
					'definedInId' => (int)$template->get('IDholon_parent'),
					'definedInName' => $definitionHolonName,
					'definedInLabel' => $definitionHolonLabel,
					'properties' => $isTemplateEditing
						? $template->getTemplatePropertyDefinitions()
						: $template->getHolonCreationPropertyDefinitions(),
				), $this->getHolonIllustrationData($template));

				$typeLabelsById[$typeId] = $template->getTypeLabel();
			}

			usort($data['templateCatalog'], static function (array $left, array $right) use ($templateContextPathRank) {
				$leftRank = $templateContextPathRank[(int)($left['definedInId'] ?? 0)] ?? PHP_INT_MAX;
				$rightRank = $templateContextPathRank[(int)($right['definedInId'] ?? 0)] ?? PHP_INT_MAX;
				if ($leftRank !== $rightRank) {
					return $leftRank <=> $rightRank;
				}

				$leftTypeId = (int)($left['typeId'] ?? 0);
				$rightTypeId = (int)($right['typeId'] ?? 0);
				if ($leftTypeId !== $rightTypeId) {
					return $leftTypeId <=> $rightTypeId;
				}

				$byName = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
				if ($byName !== 0) {
					return $byName;
				}

				return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
			});

			ksort($typeLabelsById);
			foreach ($typeLabelsById as $typeId => $typeLabel) {
				$data['types'][] = array(
					'id' => (int)$typeId,
					'name' => (string)$typeLabel,
				);
			}

			$formats = new \dbObject\ArrayPropertyFormat();
			$formats->load(array(
				'orderBy' => array(
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));
			$data['formats'] = $this->buildEditorPropertyFormats($formats);

			$this->buildSelectableHolonCatalog($rootHolon, $data['holonCatalog'], (int)$rootHolon->getId());
			$data['projectCatalog'] = $this->getProjectListEditorCatalog();
			$data['authorityCatalog'] = $this->getAuthorityListEditorCatalog();
			$data['authorityParentCatalog'] = $this->getAuthorityParentEditorCatalog($contextHolon);

			if ($editingHolon && $data['canEdit']) {
				$editingAdminBounds = $editingHolon->getAdminMemberBounds();
				$data['holon'] = array_merge(array(
					'id' => (int)$editingHolon->getId(),
					'name' => $editingHolon->getDisplayName(),
					'fullName' => trim((string)$editingHolon->get('nomcomplet')),
					'color' => (string)$editingHolon->get('color'),
					'templateId' => (int)$editingHolon->get('IDholon_template'),
					'typeId' => (int)$editingHolon->get('IDtypeholon'),
					'typeLabel' => $editingHolon->getTemplateLabel(),
					'isTemplate' => $isTemplateEditing,
					'visible' => (bool)$editingHolon->get('visible'),
					'mandatory' => (bool)$editingHolon->get('mandatory'),
					'nameLocked' => $isTemplateEditing ? (bool)$editingHolon->get('lockedname') : $editingHolon->isNameLockedByTemplate(),
					'unique' => (bool)$editingHolon->get('unique'),
					'link' => (bool)$editingHolon->get('link'),
					'adminParent' => (bool)$editingHolon->get('adminparent'),
					'adminMin' => $editingAdminBounds['min'],
					'adminMax' => $editingAdminBounds['max'],
					'lockedAdminMin' => $editingAdminBounds['minLocked'],
					'lockedAdminMax' => $editingAdminBounds['maxLocked'],
					'adminMinOverride' => $editingAdminBounds['minOverridden'],
					'adminMaxOverride' => $editingAdminBounds['maxOverridden'],
					'inheritedPermissions' => $this->buildHolonInheritedPermissionSnapshot($editingHolon),
					'permissionAssignments' => \dbObject\HolonPermission::getAssignmentKeyMapForHolon((int)$editingHolon->getId()),
					'properties' => $isTemplateEditing
						? $editingHolon->getTemplatePropertyDefinitions()
						: $editingHolon->getHolonEditorPropertyDefinitions(),
				), $this->getHolonIllustrationData($editingHolon));
			}

			return $data;
		}

		protected function getSubmittedDirectHolonPropertyDefinitions(array $submittedDefinitions, array $templateDefinitions)
		{
			$templatePropertyIds = array();
			foreach ($templateDefinitions as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0) {
					$templatePropertyIds[$propertyId] = true;
				}
			}

			$directDefinitions = array();
			foreach ($submittedDefinitions as $position => $definition) {
				if (!is_array($definition)) {
					continue;
				}
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0 && isset($templatePropertyIds[$propertyId])) {
					continue;
				}
				if (trim((string)($definition['name'] ?? '')) === '') {
					continue;
				}
				$definition['position'] = (int)$position + 1;
				$definition['isDirectProperty'] = true;
				$definition['isTemplateProperty'] = false;
				$directDefinitions[] = $definition;
			}

			return $directDefinitions;
		}

		// Enregistre holon edite
		protected function parseHolonHistoryListValue($rawValue)
		{
			$rawValue = trim((string)$rawValue);
			if ($rawValue === '') {
				return array();
			}

			$decoded = json_decode($rawValue, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				return array_values($decoded);
			}

			$items = preg_split('/\r\n|\r|\n|\|/', $rawValue);
			return array_values(array_filter(array_map('trim', $items), function ($item) {
				return $item !== '';
			}));
		}

		protected function parseHolonHistoryListItems($rawValue, $formatId)
		{
			if ((int)$formatId === \dbObject\PropertyFormat::FORMAT_HTML_LIST) {
				$parts = \dbObject\PropertyFormat::getHtmlListParts($rawValue);
				return array_values($parts['items']);
			}

			return $this->parseHolonHistoryListValue($rawValue);
		}

		protected function buildHolonHistoryListDisplayItem($item, $listItemType)
		{
			$listItemType = trim((string)$listItemType);
			$itemId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
			$label = '';

			if ($itemId > 0 && $listItemType === \dbObject\Property::LIST_ITEM_HOLON) {
				$linkedHolon = new \dbObject\Holon();
				if ($linkedHolon->load($itemId) && $this->containsHolon($linkedHolon)) {
					$label = trim((string)$linkedHolon->getDisplayName());
				}
			}

			if ($itemId > 0 && $listItemType === \dbObject\Property::LIST_ITEM_PROJECT) {
				$project = new \dbObject\Project();
				if ($project->load($itemId) && (int)$project->get('IDorganization') === (int)$this->getId()) {
					$label = trim((string)$project->get('title'));
				}
			}

			if ($itemId > 0 && $listItemType === \dbObject\Property::LIST_ITEM_AUTHORITY) {
				$authority = new \dbObject\Authority();
				if ($authority->load($itemId) && (int)$authority->getOrganizationId() === (int)$this->getId()) {
					$label = trim((string)$authority->get('label'));
				}
			}

			if ($itemId > 0 && $label !== '') {
				return array(
					'id' => $itemId,
					'label' => $label,
				);
			}

			return $item;
		}

		protected function buildHolonHistoryListDisplayItems(array $items, $listItemType)
		{
			return array_map(function ($item) use ($listItemType) {
				return $this->buildHolonHistoryListDisplayItem($item, $listItemType);
			}, array_values($items));
		}

		protected function mergeHolonHistoryListValues($ancestorValue, $currentValue)
		{
			$merged = array();
			$seen = array();

			foreach (array_merge($this->parseHolonHistoryListValue($ancestorValue), $this->parseHolonHistoryListValue($currentValue)) as $item) {
				$key = is_array($item)
					? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
					: trim((string)$item);
				if ($key === '' || isset($seen[$key])) {
					continue;
				}

				$seen[$key] = true;
				$merged[] = $item;
			}

			return count($merged) > 0
				? json_encode(array_values($merged), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
				: '';
		}

		protected function buildHolonHistoryVisibleValue(array $definition)
		{
			$formatId = (int)($definition['formatId'] ?? 0);
			$localValue = \dbObject\PropertyFormat::normalizeValueForStorage($formatId, $definition['value'] ?? '');
			$inheritedValue = \dbObject\PropertyFormat::normalizeValueForStorage($formatId, $definition['inheritedValue'] ?? '');

			if ($formatId === \dbObject\PropertyFormat::FORMAT_LIST) {
				if (!empty($definition['effectiveLocked'])) {
					return $inheritedValue;
				}

				return $this->mergeHolonHistoryListValues($inheritedValue, $localValue);
			}

			if (!\dbObject\PropertyFormat::isHtmlFormat($formatId)) {
				$localValue = trim((string)$localValue);
				$inheritedValue = trim((string)$inheritedValue);
			}

			if (!empty($definition['effectiveLocked'])) {
				return $inheritedValue;
			}

			return !\dbObject\PropertyFormat::isEmptyValue($formatId, $localValue)
				? $localValue
				: $inheritedValue;
		}

		protected function buildPermissionSnapshotFromAssignmentMap(array $assignments)
		{
			$rangeLabels = \dbObject\HolonPermission::getRangeLabels();
			$permissions = array();

			foreach ($assignments as $permissionKey => $ranges) {
				$permissionKey = trim((string)$permissionKey);
				if ($permissionKey === '') {
					continue;
				}

				$permission = \dbObject\Permission::findByKey($permissionKey);
				$title = $permission ? trim((string)$permission->get('title')) : $permissionKey;
				$description = $permission ? trim((string)$permission->get('description')) : '';
				$permissionId = $permission ? (int)$permission->getId() : 0;
				$visibleItems = array();

				foreach ((array)$ranges as $range) {
					$range = trim((string)$range);
					if ($range === '') {
						continue;
					}

					$visibleItems[] = array(
						'id' => $range,
						'label' => (string)($rangeLabels[$range] ?? $range),
					);
				}

				usort($visibleItems, function ($left, $right) {
					return strcmp(
						mb_strtolower(trim((string)($left['label'] ?? '')), 'UTF-8'),
						mb_strtolower(trim((string)($right['label'] ?? '')), 'UTF-8')
					);
				});

				$permissions[$permissionKey] = array(
					'id' => $permissionId,
					'key' => $permissionKey,
					'name' => $title !== '' ? $title : $permissionKey,
					'shortname' => $permissionKey,
					'description' => $description,
					'visibleItems' => array_values($visibleItems),
					'visibleValue' => implode('; ', array_map(function ($item) {
						return trim((string)($item['label'] ?? ''));
					}, $visibleItems)),
				);
			}

			ksort($permissions);
			return $permissions;
		}

		protected function buildHolonInheritedPermissionSnapshot(\dbObject\Holon $holon)
		{
			$collectedAssignments = array(
				\dbObject\HolonPermission::MEMBER_TYPE_MEMBER => array(),
				\dbObject\HolonPermission::MEMBER_TYPE_ADMIN => array(),
			);
			$visitedTemplateIds = array();
			$currentTemplateId = (int)$holon->get('IDholon_template');
			$guard = 0;

			while ($currentTemplateId > 0 && $guard < 30) {
				if (isset($visitedTemplateIds[$currentTemplateId])) {
					break;
				}

				$visitedTemplateIds[$currentTemplateId] = true;
				$template = new \dbObject\Holon();
				if (!$template->load($currentTemplateId)) {
					break;
				}

				foreach (\dbObject\HolonPermission::getAssignmentKeyMapForHolon($currentTemplateId) as $memberType => $profileAssignments) {
					$memberType = \dbObject\HolonPermission::normalizeMemberType($memberType);
					foreach ((array)$profileAssignments as $permissionKey => $ranges) {
						$permissionKey = trim((string)$permissionKey);
						if ($permissionKey === '') {
							continue;
						}

						if (!isset($collectedAssignments[$memberType][$permissionKey])) {
							$collectedAssignments[$memberType][$permissionKey] = array();
						}

						foreach ((array)$ranges as $range) {
							$range = trim((string)$range);
							if ($range === '') {
								continue;
							}

							$collectedAssignments[$memberType][$permissionKey][$range] = $range;
						}
					}
				}

				$currentTemplateId = (int)$template->get('IDholon_template');
				$guard++;
			}

			$snapshot = array();
			foreach ($collectedAssignments as $memberType => $profileAssignments) {
				foreach ($profileAssignments as $permissionKey => $ranges) {
					$collectedAssignments[$memberType][$permissionKey] = array_values($ranges);
				}
				$snapshot[$memberType] = $this->buildPermissionSnapshotFromAssignmentMap($collectedAssignments[$memberType]);
			}

			return $snapshot;
		}

		protected function buildHolonHistoryPermissionSnapshot(\dbObject\Holon $holon)
		{
			$holonId = (int)$holon->getId();
			if ($holonId <= 0) {
				return array();
			}

			$assignments = \dbObject\HolonPermission::getAssignmentKeyMapForHolon($holonId);
			$snapshot = array();
			foreach ($assignments as $memberType => $profileAssignments) {
				$profileSnapshot = $this->buildPermissionSnapshotFromAssignmentMap((array)$profileAssignments);
				foreach ($profileSnapshot as $permissionKey => $permissionSnapshot) {
					$historyKey = $memberType . ':' . $permissionKey;
					$permissionSnapshot['memberType'] = $memberType;
					$profileLabel = $memberType === \dbObject\HolonPermission::MEMBER_TYPE_ADMIN ? 'Admin' : 'Membre';
					$permissionSnapshot['name'] = $profileLabel . ' - ' . (string)($permissionSnapshot['name'] ?? $permissionKey);
					$snapshot[$historyKey] = $permissionSnapshot;
				}
			}

			return $snapshot;
		}

		protected function buildHolonHistorySnapshot(\dbObject\Holon $holon, array $options = array())
		{
			$options = array_merge(array(
				'propertyMode' => 'editor',
				'includePermissions' => false,
			), $options);

			$propertyDefinitions = $options['propertyMode'] === 'template'
				? $holon->getTemplatePropertyDefinitions()
				: $holon->getHolonEditorPropertyDefinitions();
			$properties = array();

			foreach ($propertyDefinitions as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId <= 0) {
					continue;
				}

				$formatId = (int)($definition['formatId'] ?? 0);
				$visibleValue = $this->buildHolonHistoryVisibleValue($definition);
				$properties[$propertyId] = array(
					'id' => $propertyId,
					'name' => trim((string)($definition['name'] ?? ('Propriete ' . $propertyId))),
					'shortname' => trim((string)($definition['shortname'] ?? '')),
					'formatId' => $formatId,
					'formatName' => (string)($definition['formatName'] ?? ''),
					'listItemType' => (string)($definition['listItemType'] ?? ''),
					'localValue' => (string)($definition['value'] ?? ''),
					'inheritedValue' => (string)($definition['inheritedValue'] ?? ''),
					'visibleValue' => (string)$visibleValue,
					'visibleItems' => \dbObject\PropertyFormat::isListFormat($formatId)
						? $this->buildHolonHistoryListDisplayItems(
							$this->parseHolonHistoryListItems($visibleValue, $formatId),
							(string)($definition['listItemType'] ?? '')
						)
						: array(),
				);
			}

			$parentTemplateName = '';
			$parentTemplateId = (int)$holon->get('IDholon_template');
			if ($parentTemplateId > 0) {
				$parentTemplate = new \dbObject\Holon();
				if ($parentTemplate->load($parentTemplateId)) {
					$parentTemplateName = trim((string)$parentTemplate->getDisplayName());
				}
			}

			return array(
				'holon' => array(
					'id' => (int)$holon->getId(),
					'name' => trim((string)$holon->getDisplayName()),
					'fullName' => trim((string)$holon->get('nomcomplet')),
					'typeId' => (int)$holon->get('IDtypeholon'),
					'typeLabel' => trim((string)$holon->getTypeLabel()),
					'parentId' => (int)$holon->get('IDholon_parent'),
					'templateId' => (int)$holon->get('IDholon_template'),
					'inheritsFromName' => $parentTemplateName,
					'color' => trim((string)$holon->get('color')),
					'icon' => trim((string)$holon->get('icon')),
					'banner' => trim((string)$holon->get('banner')),
					'visible' => (bool)$holon->get('visible'),
					'mandatory' => (bool)$holon->get('mandatory'),
					'lockedName' => (bool)$holon->get('lockedname'),
					'lockedIcon' => (bool)$holon->get('lockedicon'),
					'lockedBanner' => (bool)$holon->get('lockedbanner'),
					'unique' => (bool)$holon->get('unique'),
					'link' => (bool)$holon->get('link'),
					'adminParent' => (bool)$holon->get('adminparent'),
					'adminMin' => max(0, (int)$holon->get('admin_min')),
					'adminMax' => $holon->get('admin_max') === null ? null : (int)$holon->get('admin_max'),
					'lockedAdminMin' => (bool)$holon->get('lockedadminmin'),
					'lockedAdminMax' => (bool)$holon->get('lockedadminmax'),
					'adminMinOverride' => (bool)$holon->get('adminminoverride'),
					'adminMaxOverride' => (bool)$holon->get('adminmaxoverride'),
				),
				'properties' => $properties,
				'permissions' => !empty($options['includePermissions'])
					? $this->buildHolonHistoryPermissionSnapshot($holon)
					: array(),
			);
		}

		protected function buildHolonHistoryPermissionPreview(array $permissionSnapshot)
		{
			$labels = array();
			foreach (($permissionSnapshot['visibleItems'] ?? array()) as $item) {
				$label = trim((string)($item['label'] ?? ''));
				if ($label !== '') {
					$labels[] = $label;
				}
			}

			if (count($labels) === 0) {
				return '';
			}

			return implode('; ', array_slice($labels, 0, 3))
				. (count($labels) > 3 ? '; +' . (count($labels) - 3) . ' autre(s)' : '');
		}

		protected function buildHolonHistoryListItemKey($item)
		{
			return is_array($item)
				? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
				: trim((string)$item);
		}

		protected function buildHolonHistoryListItemComparableText($item)
		{
			if (is_array($item)) {
				return trim(implode(' ', array_filter(array(
					(string)($item['title'] ?? ''),
					(string)($item['label'] ?? ''),
					(string)($item['value'] ?? ''),
					(string)($item['description'] ?? ''),
					(string)($item['text'] ?? ''),
					(string)($item['id'] ?? ''),
				), function ($value) {
					return trim((string)$value) !== '';
				})));
			}

			return trim((string)$item);
		}

		protected function buildHolonHistoryListItemIdentity($item)
		{
			if (!is_array($item)) {
				return '';
			}

			$id = trim((string)($item['id'] ?? ''));
			if ($id !== '') {
				return 'id:' . mb_strtolower($id, 'UTF-8');
			}

			$title = trim((string)($item['title'] ?? ''));
			if ($title !== '') {
				return 'title:' . mb_strtolower($title, 'UTF-8');
			}

			$label = trim((string)($item['label'] ?? ''));
			if ($label !== '') {
				return 'label:' . mb_strtolower($label, 'UTF-8');
			}

			$value = trim((string)($item['value'] ?? ''));
			$description = trim((string)($item['description'] ?? $item['text'] ?? ''));
			if ($value !== '' && $description === '') {
				return 'value:' . mb_strtolower($value, 'UTF-8');
			}

			return '';
		}

		protected function tokenizeHolonHistoryComparableText($text)
		{
			$text = mb_strtolower(trim((string)$text), 'UTF-8');
			if ($text === '') {
				return array();
			}

			preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);
			$tokens = array_values(array_unique($matches[0] ?? array()));

			return $tokens;
		}

		protected function computeHolonHistoryListItemSimilarity($beforeItem, $afterItem, $beforeIndex = 0, $afterIndex = 0)
		{
			$beforeIdentity = $this->buildHolonHistoryListItemIdentity($beforeItem);
			$afterIdentity = $this->buildHolonHistoryListItemIdentity($afterItem);
			if ($beforeIdentity !== '' && $beforeIdentity === $afterIdentity) {
				return 2.0;
			}

			$beforeTokens = $this->tokenizeHolonHistoryComparableText($this->buildHolonHistoryListItemComparableText($beforeItem));
			$afterTokens = $this->tokenizeHolonHistoryComparableText($this->buildHolonHistoryListItemComparableText($afterItem));
			$tokenSet = array();
			$sharedCount = 0;

			foreach ($beforeTokens as $token) {
				$tokenSet[$token] = true;
			}
			foreach ($afterTokens as $token) {
				if (isset($tokenSet[$token])) {
					$sharedCount++;
				}
				$tokenSet[$token] = true;
			}

			$unionCount = count($tokenSet);
			$score = $unionCount > 0 ? ($sharedCount / $unionCount) : 0.0;

			$beforeLabel = '';
			$afterLabel = '';
			if (is_array($beforeItem)) {
				$beforeLabel = trim((string)($beforeItem['title'] ?? $beforeItem['label'] ?? ''));
			}
			if (is_array($afterItem)) {
				$afterLabel = trim((string)($afterItem['title'] ?? $afterItem['label'] ?? ''));
			}

			if ($beforeLabel !== '' && $beforeLabel === $afterLabel) {
				$score += 0.9;
			}

			$distance = abs((int)$beforeIndex - (int)$afterIndex);
			if ($distance === 0) {
				$score += 0.15;
			} elseif ($distance === 1) {
				$score += 0.05;
			}

			return $score;
		}

		protected function pairHolonHistoryModifiedListItems(array $removedItems, array $addedItems)
		{
			$removedEntries = array();
			$addedEntries = array();
			$candidates = array();

			foreach (array_values($removedItems) as $index => $item) {
				$removedEntries[] = array(
					'index' => $index,
					'item' => $item,
				);
			}
			foreach (array_values($addedItems) as $index => $item) {
				$addedEntries[] = array(
					'index' => $index,
					'item' => $item,
				);
			}

			foreach ($removedEntries as $removedEntry) {
				foreach ($addedEntries as $addedEntry) {
					$score = $this->computeHolonHistoryListItemSimilarity(
						$removedEntry['item'],
						$addedEntry['item'],
						$removedEntry['index'],
						$addedEntry['index']
					);
					if ($score >= 0.6) {
						$candidates[] = array(
							'before' => $removedEntry,
							'after' => $addedEntry,
							'score' => $score,
							'distance' => abs((int)$removedEntry['index'] - (int)$addedEntry['index']),
						);
					}
				}
			}

			usort($candidates, function ($left, $right) {
				if ((float)$left['score'] === (float)$right['score']) {
					return (int)$left['distance'] <=> (int)$right['distance'];
				}

				return (float)$right['score'] <=> (float)$left['score'];
			});

			$matchedRemovedIndexes = array();
			$matchedAddedIndexes = array();
			$matches = array();

			foreach ($candidates as $candidate) {
				$removedIndex = (int)($candidate['before']['index'] ?? -1);
				$addedIndex = (int)($candidate['after']['index'] ?? -1);
				if (isset($matchedRemovedIndexes[$removedIndex]) || isset($matchedAddedIndexes[$addedIndex])) {
					continue;
				}

				$matchedRemovedIndexes[$removedIndex] = true;
				$matchedAddedIndexes[$addedIndex] = true;
				$matches[] = array(
					'beforeIndex' => $removedIndex,
					'afterIndex' => $addedIndex,
					'before' => $candidate['before']['item'],
					'after' => $candidate['after']['item'],
				);
			}

			return array(
				'matches' => $matches,
				'removed' => array_values(array_filter($removedEntries, function ($entry) use ($matchedRemovedIndexes) {
					return !isset($matchedRemovedIndexes[(int)($entry['index'] ?? -1)]);
				})),
				'added' => array_values(array_filter($addedEntries, function ($entry) use ($matchedAddedIndexes) {
					return !isset($matchedAddedIndexes[(int)($entry['index'] ?? -1)]);
				})),
			);
		}

		protected function buildHolonHistoryListOrderSignature(array $items, array $modifiedItems, $side = 'before')
		{
			$side = $side === 'after' ? 'after' : 'before';
			$pairTokens = array();

			foreach (array_values($modifiedItems) as $pairIndex => $pair) {
				$item = $pair[$side] ?? null;
				$key = $this->buildHolonHistoryListItemKey($item);
				if ($key === '') {
					continue;
				}

				$pairTokens[$key] = '__pair_' . $pairIndex . '__';
			}

			$signature = array();
			foreach ($items as $item) {
				$key = $this->buildHolonHistoryListItemKey($item);
				if ($key === '') {
					continue;
				}

				$signature[] = $pairTokens[$key] ?? '__item_' . $key . '__';
			}

			return $signature;
		}

		protected function limitHolonHistoryText($text, $maxLength = 180)
		{
			$text = trim((string)$text);
			if ($text === '') {
				return '';
			}

			$text = preg_replace('/\s+/u', ' ', $text);
			if (function_exists('mb_strlen') && function_exists('mb_substr')) {
				if (mb_strlen($text, 'UTF-8') <= $maxLength) {
					return $text;
				}

				return rtrim(mb_substr($text, 0, max(1, $maxLength - 5), 'UTF-8')) . '[...]';
			}

			if (strlen($text) <= $maxLength) {
				return $text;
			}

			return rtrim(substr($text, 0, max(1, $maxLength - 5))) . '[...]';
		}

		protected function tokenizeHolonHistoryTextWords($text)
		{
			$text = trim((string)$text);
			if ($text === '') {
				return array();
			}

			$tokens = preg_split('/\s+/u', $text);
			if (!is_array($tokens)) {
				return array();
			}

			return array_values(array_filter(array_map('trim', $tokens), function ($token) {
				return $token !== '';
			}));
		}

		protected function buildHolonHistoryTextDiffOperations($beforeText, $afterText, $maxCells = 40000)
		{
			$beforeTokens = $this->tokenizeHolonHistoryTextWords($beforeText);
			$afterTokens = $this->tokenizeHolonHistoryTextWords($afterText);
			if (count($beforeTokens) === 0 || count($afterTokens) === 0) {
				return null;
			}

			if ((count($beforeTokens) + 1) * (count($afterTokens) + 1) > $maxCells) {
				return null;
			}

			$matrix = array();
			for ($i = 0; $i <= count($beforeTokens); $i++) {
				$matrix[$i] = array_fill(0, count($afterTokens) + 1, 0);
			}

			for ($i = 1; $i <= count($beforeTokens); $i++) {
				for ($j = 1; $j <= count($afterTokens); $j++) {
					if ($beforeTokens[$i - 1] === $afterTokens[$j - 1]) {
						$matrix[$i][$j] = $matrix[$i - 1][$j - 1] + 1;
					} else {
						$matrix[$i][$j] = max($matrix[$i - 1][$j], $matrix[$i][$j - 1]);
					}
				}
			}

			$operations = array();
			$i = count($beforeTokens);
			$j = count($afterTokens);

			while ($i > 0 || $j > 0) {
				if ($i > 0 && $j > 0 && $beforeTokens[$i - 1] === $afterTokens[$j - 1]) {
					$operations[] = array(
						'type' => 'equal',
						'value' => $beforeTokens[$i - 1],
					);
					$i--;
					$j--;
				} elseif ($j > 0 && ($i === 0 || $matrix[$i][$j - 1] > $matrix[$i - 1][$j])) {
					$operations[] = array(
						'type' => 'added',
						'value' => $afterTokens[$j - 1],
					);
					$j--;
				} else {
					$operations[] = array(
						'type' => 'removed',
						'value' => $beforeTokens[$i - 1],
					);
					$i--;
				}
			}

			$operations = array_reverse($operations);
			$merged = array();
			foreach ($operations as $operation) {
				$lastIndex = count($merged) - 1;
				if ($lastIndex >= 0 && $merged[$lastIndex]['type'] === $operation['type']) {
					$merged[$lastIndex]['values'][] = $operation['value'];
					continue;
				}

				$merged[] = array(
					'type' => $operation['type'],
					'values' => array($operation['value']),
				);
			}

			return array(
				'beforeTokens' => $beforeTokens,
				'afterTokens' => $afterTokens,
				'operations' => $merged,
			);
		}

		protected function buildHolonHistoryChangedTextSnippet($beforeText, $afterText, $maxLength = 180, $contextWords = 4)
		{
			$beforeText = preg_replace('/\s+/u', ' ', trim((string)$beforeText));
			$afterText = preg_replace('/\s+/u', ' ', trim((string)$afterText));
			if ($afterText === '') {
				return '';
			}

			$diff = $this->buildHolonHistoryTextDiffOperations($beforeText, $afterText);
			if (!is_array($diff)) {
				return $this->limitHolonHistoryText($afterText, $maxLength);
			}

			$afterTokens = $diff['afterTokens'] ?? array();
			$operations = $diff['operations'] ?? array();
			if (count($afterTokens) === 0 || count($operations) === 0) {
				return $this->limitHolonHistoryText($afterText, $maxLength);
			}

			$segments = array();
			$afterCursor = 0;
			$currentSegmentStart = null;
			$currentSegmentHasAfterTokens = false;

			foreach ($operations as $operation) {
				$type = (string)($operation['type'] ?? '');
				$values = is_array($operation['values'] ?? null) ? $operation['values'] : array();
				$valueCount = count($values);

				if ($type === 'equal') {
					if (!is_null($currentSegmentStart)) {
						$segments[] = array(
							'start' => $currentSegmentStart,
							'end' => $currentSegmentHasAfterTokens ? max($currentSegmentStart, $afterCursor - 1) : max(0, $currentSegmentStart - 1),
						);
						$currentSegmentStart = null;
						$currentSegmentHasAfterTokens = false;
					}

					$afterCursor += $valueCount;
					continue;
				}

				if (is_null($currentSegmentStart)) {
					$currentSegmentStart = $afterCursor;
				}

				if ($type === 'added') {
					$currentSegmentHasAfterTokens = true;
					$afterCursor += $valueCount;
				}
			}

			if (!is_null($currentSegmentStart)) {
				$segments[] = array(
					'start' => $currentSegmentStart,
					'end' => $currentSegmentHasAfterTokens ? max($currentSegmentStart, $afterCursor - 1) : max(0, $currentSegmentStart - 1),
				);
			}

			if (count($segments) === 0) {
				return $this->limitHolonHistoryText($afterText, $maxLength);
			}

			$startIndex = max(0, (int)$segments[0]['start'] - (int)$contextWords);
			$endIndex = min(count($afterTokens) - 1, (int)$segments[count($segments) - 1]['end'] + (int)$contextWords);
			if ($endIndex < $startIndex) {
				$endIndex = min(count($afterTokens) - 1, $startIndex + (int)$contextWords);
			}

			$ellipsisLength = 10;
			while ($startIndex > 0 || $endIndex < count($afterTokens) - 1) {
				$currentTokens = array_slice($afterTokens, $startIndex, $endIndex - $startIndex + 1);
				$currentText = implode(' ', $currentTokens);
				$currentLength = function_exists('mb_strlen')
					? mb_strlen($currentText, 'UTF-8')
					: strlen($currentText);
				$currentLength += $startIndex > 0 ? $ellipsisLength : 0;
				$currentLength += $endIndex < count($afterTokens) - 1 ? $ellipsisLength : 0;

				if ($currentLength >= $maxLength) {
					break;
				}

				$expanded = false;
				if ($startIndex > 0) {
					$candidateTokens = array_slice($afterTokens, $startIndex - 1, $endIndex - $startIndex + 2);
					$candidateText = implode(' ', $candidateTokens);
					$candidateLength = function_exists('mb_strlen')
						? mb_strlen($candidateText, 'UTF-8')
						: strlen($candidateText);
					$candidateLength += ($startIndex - 1) > 0 ? $ellipsisLength : 0;
					$candidateLength += $endIndex < count($afterTokens) - 1 ? $ellipsisLength : 0;
					if ($candidateLength <= $maxLength) {
						$startIndex--;
						$expanded = true;
					}
				}

				if ($endIndex < count($afterTokens) - 1) {
					$candidateTokens = array_slice($afterTokens, $startIndex, $endIndex - $startIndex + 2);
					$candidateText = implode(' ', $candidateTokens);
					$candidateLength = function_exists('mb_strlen')
						? mb_strlen($candidateText, 'UTF-8')
						: strlen($candidateText);
					$candidateLength += $startIndex > 0 ? $ellipsisLength : 0;
					$candidateLength += ($endIndex + 1) < count($afterTokens) - 1 ? $ellipsisLength : 0;
					if ($candidateLength <= $maxLength) {
						$endIndex++;
						$expanded = true;
					}
				}

				if (!$expanded) {
					break;
				}
			}

			$snippetTokens = array_slice($afterTokens, $startIndex, $endIndex - $startIndex + 1);
			$snippet = implode(' ', $snippetTokens);
			if ($startIndex > 0) {
				$snippet = '[...] ' . ltrim($snippet);
			}
			if ($endIndex < count($afterTokens) - 1) {
				$snippet = rtrim($snippet) . ' [...]';
			}

			return trim($snippet);
		}

		protected function formatHolonHistoryDateValue($value)
		{
			$value = trim((string)$value);
			if ($value === '') {
				return '';
			}

			try {
				return (new \DateTime($value))->format('d.m.Y');
			} catch (\Exception $exception) {
				return $value;
			}
		}

		protected function buildHolonHistoryListItemPreview($item, $listItemType = '')
		{
			$listItemType = trim((string)$listItemType);

			if ($listItemType === \dbObject\Property::LIST_ITEM_DETAIL) {
				$title = trim((string)($item['title'] ?? $item['label'] ?? $item['value'] ?? ''));
				$description = trim((string)($item['description'] ?? $item['text'] ?? ''));
				$summary = $title;
				if ($description !== '') {
					$summary .= ($summary !== '' ? ' - ' : '') . $description;
				}

				return $this->limitHolonHistoryText($summary);
			}

			if ($listItemType === \dbObject\Property::LIST_ITEM_DATE) {
				return $this->formatHolonHistoryDateValue($item);
			}

			if ($listItemType === \dbObject\Property::LIST_ITEM_HOLON) {
				$holonId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
				if ($holonId > 0) {
					$holon = new \dbObject\Holon();
					if ($holon->load($holonId)) {
						return $this->limitHolonHistoryText($holon->getDisplayName());
					}
				}
			}

			if ($listItemType === \dbObject\Property::LIST_ITEM_PROJECT) {
				$projectId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
				if ($projectId > 0) {
					$project = new \dbObject\Project();
					if (
						$project->load($projectId)
						&& (int)$project->get('IDorganization') === (int)$this->getId()
					) {
						return $this->limitHolonHistoryText((string)$project->get('title'));
					}
				}
			}

			if ($listItemType === \dbObject\Property::LIST_ITEM_AUTHORITY) {
				$authorityId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
				if ($authorityId > 0) {
					$authority = new \dbObject\Authority();
					if (
						$authority->load($authorityId)
						&& (int)$authority->getOrganizationId() === (int)$this->getId()
					) {
						return $this->limitHolonHistoryText((string)$authority->get('label'));
					}
				}
			}

			if (is_array($item)) {
				return $this->limitHolonHistoryText((string)($item['label'] ?? $item['value'] ?? ''));
			}

			return $this->limitHolonHistoryText((string)$item);
		}

		protected function buildHolonHistoryHtmlTextPreview($html)
		{
			$html = html_entity_decode(
				strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), ' ', (string)$html)),
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			);

			return $this->limitHolonHistoryText($html);
		}

		protected function buildHolonHistoryValuePreview(array $propertySnapshot, array $beforePropertySnapshot = array())
		{
			$formatId = (int)($propertySnapshot['formatId'] ?? 0);
			$value = (string)($propertySnapshot['visibleValue'] ?? '');
			$beforeValue = (string)($beforePropertySnapshot['visibleValue'] ?? '');

			if (\dbObject\PropertyFormat::isListFormat($formatId)) {
				$previews = array();
				foreach (array_slice($propertySnapshot['visibleItems'] ?? array(), 0, 3) as $item) {
					$preview = $this->buildHolonHistoryListItemPreview($item, $propertySnapshot['listItemType'] ?? '');
					if ($preview !== '') {
						$previews[] = $preview;
					}
				}

				$remainingCount = max(0, count($propertySnapshot['visibleItems'] ?? array()) - count($previews));
				if ($remainingCount > 0) {
					$previews[] = '+' . $remainingCount . ' autre(s)';
				}

				return implode('; ', $previews);
			}

			if ($formatId === \dbObject\PropertyFormat::FORMAT_DATE) {
				return $this->formatHolonHistoryDateValue($value);
			}

			if ($formatId === \dbObject\PropertyFormat::FORMAT_HTML) {
				$value = $this->buildHolonHistoryHtmlTextPreview($value);
				$beforeValue = $this->buildHolonHistoryHtmlTextPreview($beforeValue);
			}

			if ($formatId === \dbObject\PropertyFormat::FORMAT_TEXT_HTML) {
				$parts = \dbObject\PropertyFormat::getTextHtmlParts($value);
				$previewParts = array_filter(array(
					trim((string)$parts['text']),
					$this->buildHolonHistoryHtmlTextPreview($parts['detail']),
				), function ($part) {
					return trim((string)$part) !== '';
				});
				return implode(' - ', $previewParts);
			}

			if (
				in_array($formatId, array(\dbObject\PropertyFormat::FORMAT_TEXT, \dbObject\PropertyFormat::FORMAT_HTML), true)
				&& trim($beforeValue) !== ''
				&& trim($value) !== ''
				&& trim($beforeValue) !== trim($value)
			) {
				return $this->buildHolonHistoryChangedTextSnippet($beforeValue, $value);
			}

			return $this->limitHolonHistoryText($value);
		}

		protected function buildHolonHistoryPropertyToken(array $propertySnapshot)
		{
			$propertyId = (int)($propertySnapshot['id'] ?? 0);
			$propertyLabel = trim((string)($propertySnapshot['name'] ?? ('Propriete ' . $propertyId)));

			return \dbObject\History::buildReferenceToken('property', $propertyId, $propertyLabel);
		}

		protected function buildHolonHistoryHolonToken(array $holonSnapshot)
		{
			$holonId = (int)($holonSnapshot['id'] ?? 0);
			$holonName = trim((string)($holonSnapshot['name'] ?? ''));
			$holonTypeId = (int)($holonSnapshot['typeId'] ?? 0);
			$holonTypeLabel = trim((string)($holonSnapshot['typeLabel'] ?? ''));
			$holonLabel = \dbObject\History::formatHolonReferenceLabel($holonName, $holonTypeId, $holonTypeLabel);
			if ($holonLabel === '') {
				$holonLabel = 'Holon ' . $holonId;
			}

			return \dbObject\History::buildReferenceToken('holon', $holonId, $holonLabel);
		}

		protected function buildHolonHistoryPermissionLabel(array $permissionSnapshot, $permissionKey = '')
		{
			$permissionKey = trim((string)$permissionKey);
			$label = trim((string)($permissionSnapshot['name'] ?? $permissionSnapshot['shortname'] ?? ''));
			if ($label !== '') {
				return $label;
			}

			return $permissionKey !== '' ? $permissionKey : 'Droit';
		}

		protected function buildHolonHistoryDiff(array $beforeSnapshot, array $afterSnapshot)
		{
			$messages = array();
			$changes = array();
			$beforeHolon = is_array($beforeSnapshot['holon'] ?? null) ? $beforeSnapshot['holon'] : array();
			$afterHolon = is_array($afterSnapshot['holon'] ?? null) ? $afterSnapshot['holon'] : array();

			if ((string)($beforeHolon['name'] ?? '') !== (string)($afterHolon['name'] ?? '')) {
				$messages[] = 'le nom a ete modifie en "' . $this->limitHolonHistoryText((string)($afterHolon['name'] ?? '')) . '"';
				$changes[] = array(
					'type' => 'field_changed',
					'field' => 'name',
					'before' => (string)($beforeHolon['name'] ?? ''),
					'after' => (string)($afterHolon['name'] ?? ''),
				);
			}

			if ((string)($beforeHolon['fullName'] ?? '') !== (string)($afterHolon['fullName'] ?? '')) {
				$messages[] = (string)($afterHolon['fullName'] ?? '') !== ''
					? 'le nom complet a ete modifie en "' . $this->limitHolonHistoryText((string)($afterHolon['fullName'] ?? '')) . '"'
					: 'le nom complet a ete vide';
				$changes[] = array(
					'type' => 'field_changed',
					'field' => 'fullName',
					'before' => (string)($beforeHolon['fullName'] ?? ''),
					'after' => (string)($afterHolon['fullName'] ?? ''),
				);
			}

			$mediaFields = array(
				'color' => 'la couleur a ete modifiee',
				'icon' => "l'icone a ete modifiee",
				'banner' => 'la banniere a ete modifiee',
			);
			foreach ($mediaFields as $field => $message) {
				if ((string)($beforeHolon[$field] ?? '') === (string)($afterHolon[$field] ?? '')) {
					continue;
				}

				$messages[] = $message;
				$changes[] = array(
					'type' => 'field_changed',
					'field' => $field,
					'before' => (string)($beforeHolon[$field] ?? ''),
					'after' => (string)($afterHolon[$field] ?? ''),
				);
			}

			$templateBooleanFields = array(
				'visible' => 'visible',
				'mandatory' => 'obligatoire',
				'lockedName' => 'nom verrouille',
				'lockedIcon' => 'icone verrouillee',
				'lockedBanner' => 'banniere verrouillee',
				'lockedAdminMin' => 'minimum d admins verrouille',
				'lockedAdminMax' => 'maximum d admins verrouille',
				'adminMinOverride' => 'minimum d admins redefini',
				'adminMaxOverride' => 'maximum d admins redefini',
				'unique' => 'unique',
				'link' => 'lien',
				'adminParent' => 'admin parent',
			);
			foreach ($templateBooleanFields as $field => $label) {
				if ((bool)($beforeHolon[$field] ?? false) === (bool)($afterHolon[$field] ?? false)) {
					continue;
				}

				$messages[] = 'le parametre "' . $label . '" a ete '
					. ((bool)($afterHolon[$field] ?? false) ? 'active' : 'desactive');
				$changes[] = array(
					'type' => 'field_changed',
					'field' => $field,
					'before' => (bool)($beforeHolon[$field] ?? false),
					'after' => (bool)($afterHolon[$field] ?? false),
				);
			}

			$templateIntegerFields = array(
				'adminMin' => 'nombre minimum d admins',
				'adminMax' => 'nombre maximum d admins',
			);
			foreach ($templateIntegerFields as $field => $label) {
				if (($beforeHolon[$field] ?? null) === ($afterHolon[$field] ?? null)) {
					continue;
				}

				$messages[] = 'le parametre "' . $label . '" a ete modifie';
				$changes[] = array(
					'type' => 'field_changed',
					'field' => $field,
					'before' => $beforeHolon[$field] ?? null,
					'after' => $afterHolon[$field] ?? null,
				);
			}

			if ((string)($beforeHolon['inheritsFromName'] ?? '') !== (string)($afterHolon['inheritsFromName'] ?? '')) {
				$afterTemplateName = trim((string)($afterHolon['inheritsFromName'] ?? ''));
				if ((string)($beforeHolon['inheritsFromName'] ?? '') === '' && $afterTemplateName !== '') {
					$messages[] = 'le modele parent a ete defini sur "' . $this->limitHolonHistoryText($afterTemplateName) . '"';
				} elseif ((string)($beforeHolon['inheritsFromName'] ?? '') !== '' && $afterTemplateName === '') {
					$messages[] = 'le modele parent a ete retire';
				} else {
					$messages[] = 'le modele parent a ete modifie en "' . $this->limitHolonHistoryText($afterTemplateName) . '"';
				}

				$changes[] = array(
					'type' => 'field_changed',
					'field' => 'inheritsFromName',
					'before' => (string)($beforeHolon['inheritsFromName'] ?? ''),
					'after' => (string)($afterHolon['inheritsFromName'] ?? ''),
				);
			}

			$beforeProperties = is_array($beforeSnapshot['properties'] ?? null) ? $beforeSnapshot['properties'] : array();
			$afterProperties = is_array($afterSnapshot['properties'] ?? null) ? $afterSnapshot['properties'] : array();
			$propertyIds = array_unique(array_merge(array_keys($beforeProperties), array_keys($afterProperties)));
			sort($propertyIds);

			foreach ($propertyIds as $propertyId) {
				$beforeProperty = $beforeProperties[$propertyId] ?? null;
				$afterProperty = $afterProperties[$propertyId] ?? null;

				if (!is_array($beforeProperty) && !is_array($afterProperty)) {
					continue;
				}

				$propertySnapshot = is_array($afterProperty) ? $afterProperty : $beforeProperty;
				$propertyToken = $this->buildHolonHistoryPropertyToken($propertySnapshot);
				$formatId = (int)($propertySnapshot['formatId'] ?? 0);

				if (!is_array($beforeProperty)) {
					$messages[] = 'la propriete ' . $propertyToken . ' a ete ajoutee';
					$changes[] = array(
						'type' => 'property_added',
						'propertyId' => (int)$propertyId,
						'after' => $afterProperty,
					);
					continue;
				}

				if (!is_array($afterProperty)) {
					$messages[] = 'la propriete ' . $propertyToken . ' a ete retiree';
					$changes[] = array(
						'type' => 'property_removed',
						'propertyId' => (int)$propertyId,
						'before' => $beforeProperty,
					);
					continue;
				}

				if (\dbObject\PropertyFormat::isListFormat($formatId)) {
					if ($formatId === \dbObject\PropertyFormat::FORMAT_HTML_LIST) {
						$beforeParts = \dbObject\PropertyFormat::getHtmlListParts((string)($beforeProperty['visibleValue'] ?? ''));
						$afterParts = \dbObject\PropertyFormat::getHtmlListParts((string)($afterProperty['visibleValue'] ?? ''));
						$beforeHtml = $this->buildHolonHistoryHtmlTextPreview($beforeParts['before'])
							. ' ' . $this->buildHolonHistoryHtmlTextPreview($beforeParts['after']);
						$afterHtml = $this->buildHolonHistoryHtmlTextPreview($afterParts['before'])
							. ' ' . $this->buildHolonHistoryHtmlTextPreview($afterParts['after']);
						if (trim($beforeHtml) !== trim($afterHtml)) {
							$messages[] = 'le contenu HTML de ' . $propertyToken . ' a ete modifie'
								. (($preview = $this->buildHolonHistoryChangedTextSnippet($beforeHtml, $afterHtml)) !== '' ? ' : ' . $preview : '');
							$changes[] = array(
								'type' => 'property_html_changed',
								'propertyId' => (int)$propertyId,
								'before' => $beforeParts,
								'after' => $afterParts,
							);
						}
					}
					$beforeItemsByKey = array();
					foreach ($beforeProperty['visibleItems'] ?? array() as $item) {
						$key = $this->buildHolonHistoryListItemKey($item);
						if ($key !== '') {
							$beforeItemsByKey[$key] = $item;
						}
					}

					$afterItemsByKey = array();
					foreach ($afterProperty['visibleItems'] ?? array() as $item) {
						$key = $this->buildHolonHistoryListItemKey($item);
						if ($key !== '') {
							$afterItemsByKey[$key] = $item;
						}
					}

					$addedItems = array_values(array_diff_key($afterItemsByKey, $beforeItemsByKey));
					$removedItems = array_values(array_diff_key($beforeItemsByKey, $afterItemsByKey));
					$pairing = $this->pairHolonHistoryModifiedListItems($removedItems, $addedItems);
					$modifiedItems = array_values($pairing['matches'] ?? array());
					$removedItems = array_map(function ($entry) {
						return $entry['item'] ?? null;
					}, $pairing['removed'] ?? array());
					$removedItems = array_values(array_filter($removedItems, function ($item) {
						return !is_null($item);
					}));
					$addedItems = array_map(function ($entry) {
						return $entry['item'] ?? null;
					}, $pairing['added'] ?? array());
					$addedItems = array_values(array_filter($addedItems, function ($item) {
						return !is_null($item);
					}));

					if (count($modifiedItems) > 0) {
						$itemPreviews = array();
						foreach (array_slice($modifiedItems, 0, 3) as $itemPair) {
							$beforePreview = $this->buildHolonHistoryListItemPreview($itemPair['before'] ?? null, $beforeProperty['listItemType'] ?? '');
							$afterPreview = $this->buildHolonHistoryListItemPreview($itemPair['after'] ?? null, $afterProperty['listItemType'] ?? '');
							if ($beforePreview !== '' && $afterPreview !== '' && $beforePreview !== $afterPreview) {
								$itemPreviews[] = $beforePreview . ' -> ' . $afterPreview;
							} elseif ($afterPreview !== '') {
								$itemPreviews[] = $afterPreview;
							} elseif ($beforePreview !== '') {
								$itemPreviews[] = $beforePreview;
							}
						}

						$modifiedCount = count($modifiedItems);
						$messages[] = $modifiedCount . ' ' . ($modifiedCount > 1 ? 'elements ont ete modifies dans ' : 'element a ete modifie dans ') . $propertyToken
							. (count($itemPreviews) > 0 ? ' : ' . implode('; ', $itemPreviews) : '');
						$changes[] = array(
							'type' => 'property_list_changed',
							'propertyId' => (int)$propertyId,
							'items' => $modifiedItems,
						);
					}

					if (count($addedItems) > 0) {
						$itemPreviews = array();
						foreach (array_slice($addedItems, 0, 3) as $item) {
							$preview = $this->buildHolonHistoryListItemPreview($item, $afterProperty['listItemType'] ?? '');
							if ($preview !== '') {
								$itemPreviews[] = $preview;
							}
						}

						$addedCount = count($addedItems);
						$messages[] = $addedCount . ' ' . ($addedCount > 1 ? 'elements ont ete ajoutes a ' : 'element a ete ajoute a ') . $propertyToken
							. (count($itemPreviews) > 0 ? ' : ' . implode('; ', $itemPreviews) : '');
						$changes[] = array(
							'type' => 'property_list_added',
							'propertyId' => (int)$propertyId,
							'items' => $addedItems,
						);
					}

					if (count($removedItems) > 0) {
						$itemPreviews = array();
						foreach (array_slice($removedItems, 0, 3) as $item) {
							$preview = $this->buildHolonHistoryListItemPreview($item, $beforeProperty['listItemType'] ?? '');
							if ($preview !== '') {
								$itemPreviews[] = $preview;
							}
						}

						$removedCount = count($removedItems);
						$messages[] = $removedCount . ' ' . ($removedCount > 1 ? 'elements ont ete retires de ' : 'element a ete retire de ') . $propertyToken
							. (count($itemPreviews) > 0 ? ' : ' . implode('; ', $itemPreviews) : '');
						$changes[] = array(
							'type' => 'property_list_removed',
							'propertyId' => (int)$propertyId,
							'items' => $removedItems,
						);
					}

					$beforeOrderSignature = $this->buildHolonHistoryListOrderSignature(
						array_values($beforeProperty['visibleItems'] ?? array()),
						$modifiedItems,
						'before'
					);
					$afterOrderSignature = $this->buildHolonHistoryListOrderSignature(
						array_values($afterProperty['visibleItems'] ?? array()),
						$modifiedItems,
						'after'
					);

					if (
						count($addedItems) === 0
						&& count($removedItems) === 0
						&& $beforeOrderSignature !== $afterOrderSignature
					) {
						$messages[] = 'les elements de ' . $propertyToken . ' ont ete reordonnes';
						$changes[] = array(
							'type' => 'property_list_reordered',
							'propertyId' => (int)$propertyId,
							'before' => array_values($beforeProperty['visibleItems'] ?? array()),
							'after' => array_values($afterProperty['visibleItems'] ?? array()),
						);
					}

					continue;
				}

				$beforeValue = (string)($beforeProperty['visibleValue'] ?? '');
				$afterValue = (string)($afterProperty['visibleValue'] ?? '');
				if ($beforeValue === $afterValue) {
					continue;
				}

				$afterPreview = $this->buildHolonHistoryValuePreview($afterProperty, $beforeProperty);
				if ($beforeValue === '' && $afterValue !== '') {
					$messages[] = 'la propriete ' . $propertyToken . ' a ete renseignee'
						. ($afterPreview !== '' ? ' : ' . $afterPreview : '');
				} elseif ($beforeValue !== '' && $afterValue === '') {
					$messages[] = 'la propriete ' . $propertyToken . ' a ete videe';
				} else {
					$messages[] = 'la propriete ' . $propertyToken . ' a ete modifiee'
						. ($afterPreview !== '' ? ' : ' . $afterPreview : '');
				}

				$changes[] = array(
					'type' => 'property_value_changed',
					'propertyId' => (int)$propertyId,
					'before' => $beforeProperty,
					'after' => $afterProperty,
				);
			}

			$beforePermissions = is_array($beforeSnapshot['permissions'] ?? null) ? $beforeSnapshot['permissions'] : array();
			$afterPermissions = is_array($afterSnapshot['permissions'] ?? null) ? $afterSnapshot['permissions'] : array();
			$permissionKeys = array_unique(array_merge(array_keys($beforePermissions), array_keys($afterPermissions)));
			sort($permissionKeys);

			foreach ($permissionKeys as $permissionKey) {
				$beforePermission = $beforePermissions[$permissionKey] ?? null;
				$afterPermission = $afterPermissions[$permissionKey] ?? null;
				$permissionSnapshot = is_array($afterPermission) ? $afterPermission : $beforePermission;
				$permissionLabel = $this->buildHolonHistoryPermissionLabel(is_array($permissionSnapshot) ? $permissionSnapshot : array(), $permissionKey);

				if (!is_array($beforePermission) && is_array($afterPermission)) {
					$messages[] = 'le droit "' . $permissionLabel . '" a ete ajoute'
						. (($preview = $this->buildHolonHistoryPermissionPreview($afterPermission)) !== '' ? ' : ' . $preview : '');
					$changes[] = array(
						'type' => 'permission_added',
						'permissionKey' => $permissionKey,
						'after' => $afterPermission,
					);
					continue;
				}

				if (is_array($beforePermission) && !is_array($afterPermission)) {
					$messages[] = 'le droit "' . $permissionLabel . '" a ete retire';
					$changes[] = array(
						'type' => 'permission_removed',
						'permissionKey' => $permissionKey,
						'before' => $beforePermission,
					);
					continue;
				}

				if (!is_array($beforePermission) || !is_array($afterPermission)) {
					continue;
				}

				$beforeItems = array_values($beforePermission['visibleItems'] ?? array());
				$afterItems = array_values($afterPermission['visibleItems'] ?? array());
				if (json_encode($beforeItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($afterItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
					continue;
				}

				$addedItems = array_values(array_udiff($afterItems, $beforeItems, function ($left, $right) {
					return strcmp((string)($left['id'] ?? ''), (string)($right['id'] ?? ''));
				}));
				$removedItems = array_values(array_udiff($beforeItems, $afterItems, function ($left, $right) {
					return strcmp((string)($left['id'] ?? ''), (string)($right['id'] ?? ''));
				}));

				$message = 'les portees du droit "' . $permissionLabel . '" ont ete modifiees';
				$messageParts = array();
				if (count($addedItems) > 0) {
					$messageParts[] = '+ ' . implode(', ', array_map(function ($item) {
						return trim((string)($item['label'] ?? ''));
					}, $addedItems));
				}
				if (count($removedItems) > 0) {
					$messageParts[] = '- ' . implode(', ', array_map(function ($item) {
						return trim((string)($item['label'] ?? ''));
					}, $removedItems));
				}
				if (count($messageParts) > 0) {
					$message .= ' : ' . implode(' ; ', $messageParts);
				}

				$messages[] = $message;
				$changes[] = array(
					'type' => 'permission_changed',
					'permissionKey' => $permissionKey,
					'before' => $beforePermission,
					'after' => $afterPermission,
					'added' => $addedItems,
					'removed' => $removedItems,
				);
			}

			return array(
				'messages' => $messages,
				'changes' => $changes,
			);
		}

		protected function recordHolonUpdateHistory(\dbObject\Holon $holon, $authorUserId, array $beforeSnapshot, array $afterSnapshot)
		{
			$diff = $this->buildHolonHistoryDiff($beforeSnapshot, $afterSnapshot);
			if (count($diff['changes']) === 0) {
				return;
			}

			$messageLines = array();
			foreach ($diff['messages'] as $message) {
				$message = trim((string)$message);
				if ($message === '') {
					continue;
				}

				$messageLines[] = '- ' . rtrim($message, ". \t\n\r\0\x0B") . '.';
			}

			$content = 'Modification de ' . $this->buildHolonHistoryHolonToken($afterSnapshot['holon'] ?? array()) . ' :';
			if (count($messageLines) > 0) {
				$content .= "\n" . implode("\n", $messageLines);
			}

			\dbObject\History::createEntry(
				(int)$this->getId(),
				(int)$authorUserId,
				'holon_updated',
				$content,
				array(
					'IDholon' => (int)$holon->getId(),
					'before' => $beforeSnapshot,
					'after' => $afterSnapshot,
					'changes' => $diff['changes'],
				),
				(int)$holon->getContainingCircleId(false)
			);
		}

		protected function recordHolonCreatedHistory(\dbObject\Holon $holon, $authorUserId, array $afterSnapshot)
		{
			$content = 'Creation de ' . $this->buildHolonHistoryHolonToken($afterSnapshot['holon'] ?? array()) . '.';

			\dbObject\History::createEntry(
				(int)$this->getId(),
				(int)$authorUserId,
				'holon_created',
				$content,
				array(
					'IDholon' => (int)$holon->getId(),
					'after' => $afterSnapshot,
				),
				(int)$holon->getContainingCircleId(false)
			);
		}

		protected function recordAuthorityHistory(\dbObject\Holon $holon, $authorUserId, $action, $content, array $parameters = array())
		{
			$holonId = (int)$holon->getId();
			$holonToken = \dbObject\History::buildReferenceToken(
				'holon',
				$holonId,
				$holon->getDisplayName()
			);
			$content = trim((string)$content);
			if ($holonId > 0 && strpos($content, \dbObject\History::buildHolonSearchNeedle($holonId)) === false) {
				$content .= ' Autorite confiee a ' . $holonToken . '.';
			}
			$parameters['IDholon'] = $holonId;

			\dbObject\History::createEntry(
				(int)$this->getId(),
				(int)$authorUserId,
				(string)$action,
				(string)$content,
				$parameters,
				(int)$holon->getContainingCircleId(false)
			);
		}

		protected function getAuthorityHistoryLabel($authorityId)
		{
			$authorityId = (int)$authorityId;
			if ($authorityId <= 0) {
				return 'aucune autorite parente';
			}

			$authority = new \dbObject\Authority();
			if (!$authority->load($authorityId)) {
				return 'autorite #' . $authorityId;
			}

			$label = trim((string)$authority->get('label'));
			return $label !== '' ? $label : 'autorite #' . $authorityId;
		}

		protected function markTemplateAuthorityInstancesOriginLost(\dbObject\Authority $sourceAuthority)
		{
			$sourceIds = array();
			$pendingAuthorities = array($sourceAuthority);
			while (count($pendingAuthorities) > 0) {
				$authority = array_shift($pendingAuthorities);
				if (!($authority instanceof \dbObject\Authority)) {
					continue;
				}
				$authorityId = (int)$authority->getId();
				if ($authorityId <= 0 || isset($sourceIds[$authorityId])) {
					continue;
				}
				$sourceIds[$authorityId] = true;
				foreach ($authority->getChildren() as $childAuthority) {
					$pendingAuthorities[] = $childAuthority;
				}
			}

			if (count($sourceIds) === 0) {
				return;
			}

			foreach ($this->getAuthorityListEditorCatalog() as $entry) {
				$templateAuthorityId = (int)($entry['templateAuthorityId'] ?? 0);
				if ($templateAuthorityId <= 0 || !isset($sourceIds[$templateAuthorityId]) || !empty($entry['templateOriginLost'])) {
					continue;
				}
				$instance = new \dbObject\Authority();
				if (!$instance->load((int)($entry['id'] ?? 0))) {
					continue;
				}
				$instance->set('template_origin_lost', true);
				$instance->save();
			}
		}

		protected function syncTemplateAuthorityInstances(\dbObject\Holon $template)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$templateId = (int)$template->getId();
			if (!$rootHolon || $templateId <= 0) {
				return array();
			}

			$sourceAuthorities = new \dbObject\ArrayAuthority();
			$sourceAuthorities->loadForHolon($templateId);
			$sourceById = array();
			foreach ($sourceAuthorities as $sourceAuthority) {
				$sourceId = (int)$sourceAuthority->getId();
				if ($sourceId > 0 && (int)$sourceAuthority->get('IDauthority_template') <= 0 && !empty($sourceAuthority->get('is_local'))) {
					$sourceById[$sourceId] = $sourceAuthority;
				}
			}
			if (count($sourceById) === 0) {
				return array();
			}

			$instancesByHolonId = array();
			foreach ($this->getOrganizationHolonIds() as $holonId) {
				$holon = new \dbObject\Holon();
				if (!$holon->load((int)$holonId) || $holon->isTemplateNode((int)$rootHolon->getId())) {
					continue;
				}
				if (!in_array($templateId, $holon->getTemplateLineageIds(), true)) {
					continue;
				}

				$instancesBySourceId = array();
				$existingAuthorities = new \dbObject\ArrayAuthority();
				$existingAuthorities->loadForHolon((int)$holon->getId());
				foreach ($existingAuthorities as $existingAuthority) {
					$sourceId = (int)$existingAuthority->get('IDauthority_template');
					if ($sourceId > 0 && isset($sourceById[$sourceId])) {
						$instancesBySourceId[$sourceId] = $existingAuthority;
					}
				}

				foreach ($sourceById as $sourceId => $sourceAuthority) {
					$instance = $instancesBySourceId[$sourceId] ?? new \dbObject\Authority();
					$instance->set('IDholon', (int)$holon->getId());
					$instance->set('IDauthority_template', $sourceId);
					$instance->set('IDauthority_parent', null);
					$instance->set('label', (string)$sourceAuthority->get('label'));
					$instance->set('description', $sourceAuthority->get('description'));
					$instance->set('is_local', true);
					$instance->set('template_origin_lost', false);
					$instance->set('is_shell', false);
					$saveResult = $instance->save();
					if (!is_array($saveResult) || empty($saveResult['status'])) {
						continue;
					}
					$instancesByHolonId[(int)$holon->getId()][$sourceId] = (int)$instance->getId();
				}
			}

			return $instancesByHolonId;
		}

		public function ensureTemplateAuthorityInstancesForHolon(\dbObject\Holon $holon)
		{
			$rootHolon = $this->getStructuralRootHolon();
			if (!($rootHolon instanceof \dbObject\Holon) || !$this->containsHolon($holon)) {
				return;
			}

			foreach ($holon->getTemplateLineageIds() as $templateId) {
				$template = new \dbObject\Holon();
				if (!$template->load((int)$templateId) || !$template->isTemplateNode((int)$rootHolon->getId())) {
					continue;
				}
				$this->normalizeTemplateLocalAuthorities($template);
				$this->syncTemplateAuthorityInstances($template);
			}
		}

		protected function normalizeTemplateLocalAuthorities(\dbObject\Holon $template)
		{
			$authorities = new \dbObject\ArrayAuthority();
			$authorities->loadForHolon((int)$template->getId());
			foreach ($authorities as $authority) {
				if ((int)$authority->get('IDauthority_template') > 0 || (int)$authority->get('IDauthority_parent') > 0 || !empty($authority->get('is_local'))) {
					continue;
				}
				$authority->set('is_local', true);
				$authority->save();
			}
		}

		protected function remapTemplateAuthorityListValue($rawValue, $formatId, array $authorityIdMap)
		{
			return \dbObject\PropertyFormat::remapListReferenceIds($rawValue, $formatId, $authorityIdMap);
		}

		protected function remapTemplateAuthorityPropertyValues(\dbObject\Holon $holon, array &$submittedValuesByPropertyId, array &$propertyDefinitions)
		{
			$authorityIdMap = array();
			$authorities = new \dbObject\ArrayAuthority();
			$authorities->loadForHolon((int)$holon->getId());
			foreach ($authorities as $authority) {
				$sourceId = (int)$authority->get('IDauthority_template');
				if ($sourceId > 0) {
					$authorityIdMap[$sourceId] = (int)$authority->getId();
				}
			}
			if (count($authorityIdMap) === 0) {
				return;
			}

			foreach ($propertyDefinitions as &$definition) {
				if (
					(string)($definition['listItemType'] ?? '') !== \dbObject\Property::LIST_ITEM_AUTHORITY
					|| !\dbObject\PropertyFormat::isListFormat((int)($definition['formatId'] ?? 0))
				) {
					continue;
				}
				$propertyId = (int)($definition['id'] ?? 0);
				$formatId = (int)($definition['formatId'] ?? 0);
				$definition['inheritedValue'] = $this->remapTemplateAuthorityListValue($definition['inheritedValue'] ?? '', $formatId, $authorityIdMap);
				if ($propertyId > 0 && array_key_exists($propertyId, $submittedValuesByPropertyId)) {
					$submittedValuesByPropertyId[$propertyId] = $this->remapTemplateAuthorityListValue($submittedValuesByPropertyId[$propertyId], $formatId, $authorityIdMap);
				}
			}
			unset($definition);
		}

		protected function syncSubmittedAuthorityPropertyValues(\dbObject\Holon $holon, array &$submittedValuesByPropertyId, array $propertyDefinitions, $authorUserId = 0, array $options = array())
		{
			$allowLocalRoot = !empty($options['allowLocalRoot']);
			$isTemplateSource = !empty($options['isTemplateSource']);
			$parseItems = static function ($rawValue, $formatId) {
				$rawValue = trim((string)$rawValue);
				if ($rawValue === '') {
					return array();
				}

				$decoded = json_decode($rawValue, true);
				if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
					return array();
				}

				if ((int)$formatId === \dbObject\PropertyFormat::FORMAT_HTML_LIST) {
					return is_array($decoded['items'] ?? null) ? array_values($decoded['items']) : array();
				}

				return array_values($decoded);
			};

			$serializeItems = static function ($rawValue, $formatId, array $items) {
				if ((int)$formatId === \dbObject\PropertyFormat::FORMAT_HTML_LIST) {
					$parts = json_decode(trim((string)$rawValue), true);
					if (!is_array($parts)) {
						$parts = array();
					}
					$parts['items'] = array_values($items);
					return json_encode($parts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				}

				return count($items) > 0
					? json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
					: '';
			};

			$existingValuesByPropertyId = array();
			foreach ($holon->getHolonProperties() as $holonProperty) {
				$existingValuesByPropertyId[(int)$holonProperty->get('IDproperty')] = (string)$holonProperty->get('value');
			}

			$parentAuthorityIds = array();
			$parentHolon = $holon->getAuthorityParentHolon();
			if ($parentHolon instanceof \dbObject\Holon) {
				$parentAuthorities = new \dbObject\ArrayAuthority();
				$parentAuthorities->loadForHolon((int)$parentHolon->getId());
				foreach ($parentAuthorities as $parentAuthority) {
					$parentAuthorityId = (int)$parentAuthority->getId();
					if ($parentAuthorityId > 0) {
						$parentAuthorityIds[$parentAuthorityId] = true;
					}
				}
			}

			foreach ($propertyDefinitions as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				$formatId = (int)($definition['formatId'] ?? 0);
				if (
					$propertyId <= 0
					|| (string)($definition['listItemType'] ?? '') !== \dbObject\Property::LIST_ITEM_AUTHORITY
					|| !\dbObject\PropertyFormat::isListFormat($formatId)
					|| (!$isTemplateSource && !empty($definition['effectiveLocked']))
				) {
					continue;
				}

				$currentItems = $parseItems($existingValuesByPropertyId[$propertyId] ?? '', $formatId);
				$allowedExistingIds = array();
				foreach ($currentItems as $currentItem) {
					$currentId = is_array($currentItem) ? (int)($currentItem['id'] ?? 0) : (int)$currentItem;
					if ($currentId > 0) {
						$allowedExistingIds[$currentId] = true;
					}
				}
				if (!empty($options['allowTemplateInstances'])) {
					$templateAuthorities = new \dbObject\ArrayAuthority();
					$templateAuthorities->loadForHolon((int)$holon->getId());
					foreach ($templateAuthorities as $templateAuthority) {
						if ($templateAuthority->isTemplateInstance()) {
							$allowedExistingIds[(int)$templateAuthority->getId()] = true;
						}
					}
				}

				$rawValue = (string)($submittedValuesByPropertyId[$propertyId] ?? '');
				$submittedItems = $parseItems($rawValue, $formatId);
				$resolvedIds = array();
				$seenIds = array();

				foreach ($submittedItems as $submittedItem) {
					$existingId = is_array($submittedItem) ? (int)($submittedItem['id'] ?? 0) : (int)$submittedItem;
					if ($existingId > 0) {
						if (!isset($allowedExistingIds[$existingId])) {
							return array(
								'status' => false,
								'message' => 'Une autorite existante ne peut pas etre ajoutee a cette propriete.',
							);
						}

						$authority = new \dbObject\Authority();
						if (
							!$authority->load($existingId)
							|| (int)$authority->get('IDholon') !== (int)$holon->getId()
						) {
							return array(
								'status' => false,
								'message' => 'Cette autorite ne peut pas etre modifiee depuis ce holon.',
							);
						}

						$isDeletion = is_array($submittedItem) && !empty($submittedItem['delete']);
						$before = array(
							'label' => trim((string)$authority->get('label')),
							'description' => trim((string)$authority->get('description')),
							'parentId' => (int)$authority->get('IDauthority_parent'),
							'parentLabel' => $this->getAuthorityHistoryLabel((int)$authority->get('IDauthority_parent')),
						);
						if ($isDeletion) {
							if ($authority->isTemplateInstance()) {
								return array(
									'status' => false,
									'message' => 'Cette autorite est geree par son modele et ne peut pas etre modifiee ici.',
								);
							}
							if ($isTemplateSource) {
								$this->markTemplateAuthorityInstancesOriginLost($authority);
							}
							$deletionPlan = is_array($submittedItem['deletionPlan'] ?? null)
								? $submittedItem['deletionPlan']
								: array();
							$deletionResult = $authority->applyDeletionPlan($deletionPlan);
							if (empty($deletionResult['status'])) {
								return array(
									'status' => false,
									'message' => (string)($deletionResult['text'] ?? 'Cette autorite ne peut pas etre traitee.'),
								);
							}

							$authorityRetained = !empty($deletionResult['authorityRetained']);
							$reactivatedShellId = (int)($deletionResult['reactivatedShellId'] ?? 0);
							$historyAction = $reactivatedShellId > 0
								? 'authority_complete_delegation_reversed'
								: ($authorityRetained ? 'authority_reassigned' : 'authority_deleted');
							$historyContent = $reactivatedShellId > 0
								? 'Annulation de la delegation complete de l autorite "' . $before['label'] . '" : l autorite source a ete reactivee.'
								: ($authorityRetained
									? 'Remontee de l autorite "' . $before['label'] . '" au holon parent.'
									: 'Suppression de l autorite "' . $before['label'] . '".');
							$propertyReferenceCount = (int)($deletionResult['movedPropertyReferenceCount'] ?? 0);
							if ($propertyReferenceCount > 0) {
								$historyContent .= ' ' . $propertyReferenceCount . ' rattachement' . ($propertyReferenceCount > 1 ? 's' : '') . ' de propriete remonte' . ($propertyReferenceCount > 1 ? 's' : '') . ' au holon parent.';
							}
							$this->recordAuthorityHistory(
								$holon,
								$authorUserId,
								$historyAction,
								$historyContent,
								array(
									'IDauthority' => $existingId,
									'before' => $before,
									'deletionPlan' => $deletionResult['plan'] ?? array(),
									'deletedAuthorityIds' => $deletionResult['deletedAuthorityIds'] ?? array(),
									'movedAuthorityIds' => $deletionResult['movedAuthorityIds'] ?? array(),
									'deletedRuleIds' => $deletionResult['deletedRuleIds'] ?? array(),
									'movedRuleIds' => $deletionResult['movedRuleIds'] ?? array(),
									'movedPropertyReferenceCount' => $propertyReferenceCount,
									'reactivatedShellId' => $reactivatedShellId,
								)
							);
							continue;
						}

						if (is_array($submittedItem) && (
							array_key_exists('label', $submittedItem)
							|| array_key_exists('parentId', $submittedItem)
							|| array_key_exists('description', $submittedItem)
						)) {
							if ($authority->isTemplateInstance()) {
								return array(
									'status' => false,
									'message' => 'Cette autorite est geree par son modele et ne peut pas etre modifiee ici.',
								);
							}
							$label = trim((string)($submittedItem['label'] ?? $before['label']));
							$parentId = array_key_exists('parentId', $submittedItem)
								? (int)$submittedItem['parentId']
								: $before['parentId'];
							$description = trim((string)($submittedItem['description'] ?? $before['description']));
							$isLocal = array_key_exists('isLocal', $submittedItem)
								? !empty($submittedItem['isLocal'])
								: !empty($authority->get('is_local'));
							$canCreateRootAuthority = (int)$holon->get('IDtypeholon') === 4 || ($allowLocalRoot && $isLocal);
							if ($label === '' || ($parentId <= 0 && !$canCreateRootAuthority)) {
								return array(
									'status' => false,
									'message' => $canCreateRootAuthority
										? 'Chaque autorite doit avoir un nom.'
										: 'Chaque autorite doit avoir un nom et une autorite parente.',
								);
							}
							if ($parentId > 0 && !isset($parentAuthorityIds[$parentId])) {
								return array(
									'status' => false,
									'message' => 'L autorite parente doit etre confiee au premier cercle parent.',
								);
							}

							$authority->set('label', $label);
							$authority->set('IDauthority_parent', $parentId > 0 ? $parentId : null);
							$authority->set('description', $description !== '' ? $description : null);
							$authority->set('is_local', $isLocal);
							$saveResult = $authority->save();
							if (empty($saveResult['status'])) {
								return array(
									'status' => false,
									'message' => 'Cette autorite n a pas pu etre modifiee.',
								);
							}

							$changes = array();
							if ($before['label'] !== $label) {
								$changes[] = 'le nom est passe de "' . $before['label'] . '" a "' . $label . '"';
							}
							if ($before['parentId'] !== $parentId) {
								$changes[] = 'l autorite parente est maintenant "' . $this->getAuthorityHistoryLabel($parentId) . '"';
							}
							if ($before['description'] !== $description) {
								$changes[] = 'la description a ete modifiee';
							}
							if (count($changes) > 0) {
								$this->recordAuthorityHistory(
									$holon,
									$authorUserId,
									'authority_updated',
									'Modification de l autorite "' . $label . '" : ' . implode('; ', $changes) . '.',
									array('IDauthority' => $existingId, 'before' => $before, 'after' => array(
										'label' => $label,
										'description' => $description,
										'parentId' => $parentId,
										'parentLabel' => $this->getAuthorityHistoryLabel($parentId),
									))
								);
							}
						}

						if (!isset($seenIds[$existingId])) {
							$seenIds[$existingId] = true;
							$resolvedIds[] = $existingId;
						}
						continue;
					}

					if (!is_array($submittedItem)) {
						continue;
					}

					$label = trim((string)($submittedItem['label'] ?? ''));
					$parentId = (int)($submittedItem['parentId'] ?? 0);
					$description = trim((string)($submittedItem['description'] ?? ''));
					$isLocal = !empty($submittedItem['isLocal']);
					$delegationMode = (string)($submittedItem['delegationMode'] ?? 'partial');
					$delegationMode = $delegationMode === 'complete' ? 'complete' : 'partial';
					if ($delegationMode === 'partial' && $label === '' && $parentId <= 0 && $description === '') {
						continue;
					}
					$canCreateRootAuthority = (int)$holon->get('IDtypeholon') === 4 || ($allowLocalRoot && $isLocal);
					if (($delegationMode === 'partial' && $label === '') || ($delegationMode === 'complete' && $parentId <= 0) || ($parentId <= 0 && !$canCreateRootAuthority)) {
						return array(
							'status' => false,
							'message' => $canCreateRootAuthority
								? 'Chaque nouvelle autorite doit avoir un nom.'
								: 'Chaque nouvelle autorite doit avoir un nom et une autorite parente.',
						);
					}
					if ($parentId > 0 && !isset($parentAuthorityIds[$parentId])) {
						return array(
							'status' => false,
							'message' => 'L autorite parente doit etre confiee au premier cercle parent.',
						);
					}
					$parentAuthority = null;
					if ($parentId > 0) {
						$parentAuthority = new \dbObject\Authority();
						if (!$parentAuthority->load($parentId)) {
							return array('status' => false, 'message' => 'L autorite parente est introuvable.');
						}
					}

					if ($delegationMode === 'complete') {
						$delegationResult = $parentAuthority->delegateCompletelyToHolon($holon);
						if (empty($delegationResult['status'])) {
							return array('status' => false, 'message' => (string)($delegationResult['text'] ?? 'La delegation complete a echoue.'));
						}
						$authorityId = (int)($delegationResult['authorityId'] ?? 0);
						$delegatedAuthorityIds = is_array($delegationResult['authorityIds'] ?? null)
							? $delegationResult['authorityIds']
							: array($authorityId);
						foreach ($delegatedAuthorityIds as $delegatedAuthorityId) {
							$delegatedAuthorityId = (int)$delegatedAuthorityId;
							if ($delegatedAuthorityId > 0 && !isset($seenIds[$delegatedAuthorityId])) {
								$seenIds[$delegatedAuthorityId] = true;
								$resolvedIds[] = $delegatedAuthorityId;
							}
						}
						$this->recordAuthorityHistory(
							$holon,
							$authorUserId,
							'authority_complete_delegated',
							'Delegation complete de l autorite "' . trim((string)$parentAuthority->get('label')) . '".' . (!empty($delegationResult['createdShell']) ? ' Une coquille a ete conservee dans le holon parent.' : ''),
							array(
								'IDauthority' => $authorityId,
								'sourceAuthorityId' => $parentId,
								'createdShell' => !empty($delegationResult['createdShell']),
								'movedRuleIds' => $delegationResult['movedRuleIds'] ?? array(),
							)
						);
						continue;
					}

					if ($parentAuthority instanceof \dbObject\Authority && $parentAuthority->isShell()) {
						return array('status' => false, 'message' => 'Une coquille de delegation complete ne peut pas etre la source d une delegation partielle.');
					}

					$authority = new \dbObject\Authority();
					$authority->set('IDholon', (int)$holon->getId());
					$authority->set('IDauthority_parent', $parentId > 0 ? $parentId : null);
					$authority->set('label', $label);
					$authority->set('description', $description !== '' ? $description : null);
					$authority->set('is_local', $isLocal);
					$saveResult = $authority->save();
					if (empty($saveResult['status']) || (int)$authority->getId() <= 0) {
						return array(
							'status' => false,
							'message' => 'La nouvelle autorite n a pas pu etre creee.',
						);
					}

					$authorityId = (int)$authority->getId();
					if (!isset($seenIds[$authorityId])) {
						$seenIds[$authorityId] = true;
						$resolvedIds[] = $authorityId;
					}
				}

				$submittedValuesByPropertyId[$propertyId] = $serializeItems($rawValue, $formatId, $resolvedIds);
			}

			return array('status' => true);
		}

		public function saveHolonEditorDefinition(array $payload, $userId = 0, $contextHolonId = 0, $holonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;
			$isEditing = $holonId > 0;
			$isTemplateEditing = false;
			$holon = null;
			$contextHolon = null;
			$historyBeforeSnapshot = null;

			if ($isEditing) {
				$holon = new \dbObject\Holon();
				if (
					!$holon->load($holonId)
					|| !$this->containsHolon($holon)
					|| !in_array((int)$holon->get('IDtypeholon'), array(1, 2, 3), true)
				) {
					return array(
						'status' => false,
						'message' => 'Le holon a modifier est introuvable.',
					);
				}

				$isTemplateEditing = $holon->isTemplateNode($rootHolon ? (int)$rootHolon->getId() : 0);

				if (!$holon->canEdit()) {
					return array(
						'status' => false,
						'message' => "Vous n'avez pas les droits pour modifier ce holon.",
					);
				}

				$contextHolon = $holon->getParentHolon();
				if (!$isTemplateEditing) {
					$historyBeforeSnapshot = $this->buildHolonHistorySnapshot($holon, array(
						'includePermissions' => true,
					));
				}
			} else {
				$contextHolon = $this->getTemplateContextHolon($contextHolonId);
			}

			if (!$rootHolon) {
				return array(
					'status' => false,
					'message' => "Aucun holon racine n'a ete trouve pour cette organisation.",
				);
			}

			if (!$contextHolon || (!$isEditing && !in_array((int)$contextHolon->get('IDtypeholon'), array(2, 3, 4), true))) {
				return array(
					'status' => false,
					'message' => $isEditing
						? "Le contexte d'edition de ce holon est invalide."
						: "Le holon courant n'autorise pas l'ajout d'enfant.",
				);
			}

			if (!$contextHolon || !$contextHolon->canEdit()) {
				return array(
					'status' => false,
					'message' => $isEditing
						? "Vous n'avez pas les droits pour modifier ce holon."
						: "Vous n'avez pas les droits pour creer un holon ici.",
				);
			}

			$name = trim((string)($payload['name'] ?? ''));
			$fullName = trim((string)($payload['fullName'] ?? ''));
			$iconValue = is_scalar($payload['icon'] ?? null) ? trim((string)$payload['icon']) : '';
			$bannerValue = is_scalar($payload['banner'] ?? null) ? trim((string)$payload['banner']) : '';

			$submittedValuesByPropertyId = array();
			if (is_array($payload['properties'] ?? null)) {
				foreach ($payload['properties'] as $propertyPayload) {
					$propertyId = (int)($propertyPayload['id'] ?? 0);
					if ($propertyId <= 0) {
						continue;
					}

					$submittedValuesByPropertyId[$propertyId] = array_key_exists('value', $propertyPayload)
						? $propertyPayload['value']
						: '';
				}
			}

			$parseListValue = function ($rawValue) {
				$rawValue = trim((string)$rawValue);
				if ($rawValue === '') {
					return array();
				}

				$decoded = json_decode($rawValue, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
					return array_values($decoded);
				}

				$items = preg_split('/\r\n|\r|\n|\|/', $rawValue);
				return array_values(array_filter(array_map('trim', $items), function ($item) {
					return $item !== '';
				}));
			};

			$template = null;
			$templateDefinitions = array();
			$typeId = 0;
			$templateId = (int)($payload['templateId'] ?? 0);

			if ($isTemplateEditing) {
				$typeId = (int)$holon->get('IDtypeholon');

				if ($templateId > 0) {
					$template = new \dbObject\Holon();
					if (
						!$template->load($templateId)
						|| (int)$template->getId() === (int)$holon->getId()
						|| !$this->isTemplateAvailableInContext($template, (int)$contextHolon->getId())
					) {
						return array(
							'status' => false,
							'message' => "Le modele parent selectionne n'est pas disponible ici.",
						);
					}

					if ((int)$template->get('IDtypeholon') !== $typeId) {
						return array(
							'status' => false,
							'message' => "Le modele parent doit etre du meme type que ce holon template.",
						);
					}

					$currentInheritance = $template;
					$guard = 0;
					while ($currentInheritance && $guard < 100) {
						if ((int)$currentInheritance->getId() === (int)$holon->getId()) {
							return array(
								'status' => false,
								'message' => "Le modele parent choisi creerait une boucle.",
							);
						}

						$nextInheritanceId = (int)$currentInheritance->get('IDholon_template');
						if ($nextInheritanceId <= 0) {
							break;
						}

						$nextInheritance = new \dbObject\Holon();
						if (!$nextInheritance->load($nextInheritanceId)) {
							break;
						}

						$currentInheritance = $nextInheritance;
						$guard += 1;
					}
				}

				$templateDefinitions = is_array($payload['properties'] ?? null)
					? array_values($payload['properties'])
					: array();
			} else {
				if ($templateId <= 0) {
					return array(
						'status' => false,
						'message' => 'Le modele de reference est obligatoire.',
					);
				}

				$template = new \dbObject\Holon();
				if (
					!$template->load($templateId)
					|| !$this->isTemplateAvailableInContext($template, (int)$contextHolon->getId())
				) {
					return array(
						'status' => false,
						'message' => "Le modele selectionne n'est pas disponible ici.",
					);
				}

				$typeId = (int)$template->get('IDtypeholon');
				if ($typeId <= 0 || $typeId === 4) {
					return array(
						'status' => false,
						'message' => "Le modele choisi ne peut pas etre instancie ici.",
					);
				}

				if (
					!$this->isTemplateAvailableForHolonCreation($template, $contextHolon, $isEditing ? (int)$holon->getId() : 0)
				) {
					return array(
						'status' => false,
						'message' => "Ce modele unique est deja implemente dans ce cercle.",
					);
				}

				if ((bool)$template->get('lockedname')) {
					$name = trim((string)$template->getDisplayName());
				} elseif ((bool)$template->get('unique') && $name === '') {
					$name = trim((string)$template->getDisplayName());
				}

				$templateDefinitions = $template->getHolonCreationPropertyDefinitions();
				foreach ($templateDefinitions as $definition) {
					$propertyId = (int)($definition['id'] ?? 0);
					if ($propertyId <= 0) {
						continue;
					}

					$formatId = (int)($definition['formatId'] ?? 0);
					$localValue = \dbObject\PropertyFormat::normalizeValueForStorage(
						$formatId,
						$submittedValuesByPropertyId[$propertyId] ?? ''
					);
					$inheritedValue = \dbObject\PropertyFormat::normalizeValueForStorage(
						$formatId,
						(string)($definition['inheritedValue'] ?? '')
					);
					$effectiveValue = '';

					if (\dbObject\PropertyFormat::isListFormat($formatId)) {
						$effectiveItems = !empty($definition['effectiveLocked'])
							? $parseListValue($inheritedValue)
							: array_values(array_unique(array_merge($parseListValue($inheritedValue), $parseListValue($localValue)), SORT_REGULAR));
						$effectiveValue = count($effectiveItems) > 0 ? json_encode($effectiveItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
					} elseif (!empty($definition['effectiveLocked'])) {
						$effectiveValue = \dbObject\PropertyFormat::isHtmlFormat($formatId)
							? $inheritedValue
							: trim((string)$inheritedValue);
					} else {
						if (!\dbObject\PropertyFormat::isHtmlFormat($formatId)) {
							$localValue = trim((string)$localValue);
							$inheritedValue = trim((string)$inheritedValue);
						}

						$effectiveValue = !\dbObject\PropertyFormat::isEmptyValue($formatId, $localValue)
							? $localValue
							: $inheritedValue;
					}

					if (!empty($definition['effectiveMandatory']) && \dbObject\PropertyFormat::isEmptyValue($formatId, $effectiveValue)) {
						return array(
							'status' => false,
							'message' => 'La propriete "' . (string)($definition['name'] ?? ('#' . $propertyId)) . '" est obligatoire.',
						);
					}
				}
			}

			$submittedAdminMin = max(0, (int)($payload['adminMin'] ?? 0));
			$submittedAdminMaxValue = $payload['adminMax'] ?? null;
			$submittedAdminMax = $submittedAdminMaxValue === null || trim((string)$submittedAdminMaxValue) === ''
				? null
				: max(0, (int)$submittedAdminMaxValue);
			$lockedAdminMin = $isTemplateEditing ? !empty($payload['lockedAdminMin']) : false;
			$lockedAdminMax = $isTemplateEditing ? !empty($payload['lockedAdminMax']) : false;
			$adminMinOverride = false;
			$adminMaxOverride = false;
			$effectiveAdminMin = $submittedAdminMin;
			$effectiveAdminMax = $submittedAdminMax;
			if (!$isTemplateEditing && $template instanceof \dbObject\Holon) {
				$templateAdminBounds = $template->getEffectiveTemplateAdminBounds();
				$adminMinOverride = !empty($payload['adminMinOverride']) && empty($templateAdminBounds['minLocked']);
				$adminMaxOverride = !empty($payload['adminMaxOverride']) && empty($templateAdminBounds['maxLocked']);
				$effectiveAdminMin = $adminMinOverride ? $submittedAdminMin : (int)$templateAdminBounds['min'];
				$effectiveAdminMax = $adminMaxOverride ? $submittedAdminMax : $templateAdminBounds['max'];
			}

			if ($effectiveAdminMax !== null && $effectiveAdminMax < $effectiveAdminMin) {
				return array(
					'status' => false,
					'message' => 'Le nombre maximum d admins doit etre superieur ou egal au minimum.',
				);
			}

			$submittedDirectDefinitions = array();
			if (!$isTemplateEditing) {
				$submittedDirectDefinitions = $this->getSubmittedDirectHolonPropertyDefinitions(
					is_array($payload['properties'] ?? null) ? array_values($payload['properties']) : array(),
					$templateDefinitions
				);
				$existingDirectDefinitions = $isEditing
					? array_values(array_filter($holon->getHolonEditorPropertyDefinitions(), function ($definition) {
						return !empty($definition['isDirectProperty']);
					}))
					: array();
				$propertyPermissionHolon = $holon ?: $contextHolon;
				$propertyPermissionResult = $this->canApplyPropertyDefinitionChanges(
					$propertyPermissionHolon,
					$this->getPropertyDefinitionPermissionOperations($existingDirectDefinitions, $submittedDirectDefinitions),
					'HOLON'
				);
				if (empty($propertyPermissionResult['status'])) {
					return $propertyPermissionResult;
				}
			}

			if ($name === '') {
				return array(
					'status' => false,
					'message' => 'Le nom du holon est obligatoire.',
				);
			}

			if (!$isTemplateEditing && $template instanceof \dbObject\Holon) {
				$templatePropertyPermissionResult = $this->canEditSubmittedTemplatePropertyValues(
					$holon ?: new \dbObject\Holon(),
					$template,
					$submittedValuesByPropertyId,
					$templateDefinitions
				);
				if (empty($templatePropertyPermissionResult['status'])) {
					return $templatePropertyPermissionResult;
				}
			}

			if (!$holon) {
				$holon = new \dbObject\Holon();
			}

			$holon->set('name', $name);
			$holon->set('nomcomplet', $fullName !== '' ? $fullName : null);
			$holon->set('templatename', $isTemplateEditing ? $name : null);
			$holon->set('IDtypeholon', $typeId);
			$holon->set('IDholon_parent', (int)$contextHolon->getId());
			$holon->set('IDholon_template', $templateId > 0 ? $templateId : null);
			$holon->set('IDholon_org', (int)$rootHolon->getId());
			$holon->set('IDorganization', null);
			$holon->set('IDuser', (int)$userId > 0 ? (int)$userId : (int)($holon->get('IDuser') ?: ($template ? $template->get('IDuser') : 0)));
			$holon->set('active', true);
			$holon->set('visible', $isTemplateEditing ? !empty($payload['visible']) : true);
			$holon->set('mandatory', $isTemplateEditing ? !empty($payload['mandatory']) : false);
			$holon->set('lockedname', $isTemplateEditing ? !empty($payload['lockedName']) : false);
			$holon->set('lockedicon', $isTemplateEditing ? !empty($payload['lockedIcon']) : false);
			$holon->set('lockedbanner', $isTemplateEditing ? !empty($payload['lockedBanner']) : false);
			$holon->set('unique', $isTemplateEditing ? !empty($payload['unique']) : false);
			$holon->set('link', $isTemplateEditing ? !empty($payload['link']) : false);
			$holon->set(
				'adminparent',
				$isTemplateEditing
				&& $typeId === 1
				&& (array_key_exists('adminParent', $payload) ? !empty($payload['adminParent']) : (bool)$holon->get('adminparent'))
			);
			$holon->set('admin_min', $isTemplateEditing || $adminMinOverride ? $submittedAdminMin : $effectiveAdminMin);
			$holon->set('admin_max', $isTemplateEditing || $adminMaxOverride ? $submittedAdminMax : $effectiveAdminMax);
			$holon->set('lockedadminmin', $lockedAdminMin);
			$holon->set('lockedadminmax', $lockedAdminMax);
			$holon->set('adminminoverride', $adminMinOverride);
			$holon->set('adminmaxoverride', $adminMaxOverride);
			$color = trim((string)($payload['color'] ?? ''));
			$holon->set('color', $color !== '' ? $color : null);
			$holon->set(
				'icon',
				(!$isTemplateEditing && $template && $template->getEffectiveTemplateBooleanField('lockedicon'))
					? null
					: ($iconValue !== '' ? $iconValue : null)
			);
			$holon->set(
				'banner',
				(!$isTemplateEditing && $template && $template->getEffectiveTemplateBooleanField('lockedbanner'))
					? null
					: ($bannerValue !== '' ? $bannerValue : null)
			);
			$holon->save();

			if ((int)$holon->getId() <= 0) {
				return array(
					'status' => false,
					'message' => "Le holon n'a pas pu etre enregistre.",
				);
			}

			if (!$isTemplateEditing && $template instanceof \dbObject\Holon) {
				$this->syncTemplateAuthorityInstances($template);
				$this->remapTemplateAuthorityPropertyValues($holon, $submittedValuesByPropertyId, $templateDefinitions);
			}

			if ($isTemplateEditing) {
				$holon->syncTemplateProperties($templateDefinitions, (int)$rootHolon->getId());
			} else {
				$resolvedDirectDefinitions = $holon->syncDirectEditorPropertyDefinitions(
					$submittedDirectDefinitions,
					(int)$rootHolon->getId()
				);
				$templateDefinitions = array_merge($templateDefinitions, $resolvedDirectDefinitions);
				foreach ($resolvedDirectDefinitions as $definition) {
					$propertyId = (int)($definition['id'] ?? 0);
					if ($propertyId > 0) {
						$submittedValuesByPropertyId[$propertyId] = $definition['value'] ?? '';
					}
				}

				$authoritySyncResult = $this->syncSubmittedAuthorityPropertyValues(
					$holon,
					$submittedValuesByPropertyId,
					$templateDefinitions,
					$userId,
					array('allowTemplateInstances' => true)
				);
				if (empty($authoritySyncResult['status'])) {
					return array(
						'status' => false,
						'message' => (string)($authoritySyncResult['message'] ?? 'Les autorites n ont pas pu etre enregistrees.'),
					);
				}

				$holon->syncEditorPropertyValues($submittedValuesByPropertyId, $templateDefinitions);
				if (!\dbObject\HolonPermission::syncAssignmentsForHolon(
					(int)$holon->getId(),
					is_array($payload['permissions'] ?? null) ? $payload['permissions'] : array()
				)) {
					return array(
						'status' => false,
						'message' => "Les droits du holon n'ont pas pu etre enregistres.",
					);
				}

				if (!$isEditing && (int)$holon->get('IDtypeholon') === 2) {
					$excludedTemplateIds = array();
					if ($templateId > 0) {
						$excludedTemplateIds[] = $templateId;
					}

						$this->createMandatoryChildrenForCircle($holon, (int)$rootHolon->getId(), $userId, $excludedTemplateIds);
				}

				$holon->load((int)$holon->getId(), true);
				$historyAfterSnapshot = $this->buildHolonHistorySnapshot($holon, array(
					'includePermissions' => true,
				));
				if ($isEditing && is_array($historyBeforeSnapshot)) {
					$this->recordHolonUpdateHistory($holon, $userId, $historyBeforeSnapshot, $historyAfterSnapshot);
				} elseif (!$isEditing) {
					$this->recordHolonCreatedHistory($holon, $userId, $historyAfterSnapshot);
				}
			}

			return array(
				'status' => true,
				'message' => $isEditing ? 'Holon enregistre.' : 'Holon cree.',
				'holon' => array(
					'id' => (int)$holon->getId(),
					'name' => $holon->getDisplayName(),
					'typeId' => (int)$holon->get('IDtypeholon'),
					'typeLabel' => $holon->getTemplateLabel(),
					'parentId' => (int)$contextHolon->getId(),
				),
				'data' => $this->getHolonCreationEditorData((int)$contextHolon->getId(), (int)$holon->getId()),
			);
		}

		// Supprime holon cible
		public function deleteHolonDefinition($holonId = 0, $userId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;

			if (!$rootHolon || $holonId <= 0) {
				return array(
					'status' => false,
					'message' => 'Le holon a supprimer est invalide.',
				);
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($holonId)
				|| !$this->containsHolon($holon)
				|| $holon->isTemplateNode((int)$rootHolon->getId())
				|| !in_array((int)$holon->get('IDtypeholon'), array(1, 2, 3), true)
			) {
				return array(
					'status' => false,
					'message' => 'Le holon a supprimer est introuvable.',
				);
			}

			if (!$holon->canEdit() || !$holon->canDelete()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour supprimer ce holon.",
				);
			}

			$parentHolon = $holon->getParentHolon();
			if (!$parentHolon) {
				return array(
					'status' => false,
					'message' => 'Le parent de ce holon est introuvable.',
				);
			}

			$descendantCount = $holon->countVisibleDescendants();
			$holonName = $holon->getDisplayName();
			$holonTypeId = (int)$holon->get('IDtypeholon');

			if (!$holon->delete()) {
				return array(
					'status' => false,
					'message' => "Le holon n'a pas pu etre supprime.",
				);
			}

			return array(
				'status' => true,
				'message' => 'Holon supprime.',
				'holon' => array(
					'id' => $holonId,
					'name' => $holonName,
					'typeId' => $holonTypeId,
					'descendantCount' => $descendantCount,
				),
				'parent' => array(
					'id' => (int)$parentHolon->getId(),
					'isRoot' => (int)$parentHolon->get('IDtypeholon') === 4,
				),
			);
		}

		public function moveHolonDefinition($holonId = 0, $targetParentId = 0, $userId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;
			$targetParentId = (int)$targetParentId;

			if (!$rootHolon || $holonId <= 0 || $targetParentId <= 0) {
				return array(
					'status' => false,
					'message' => 'Le deplacement demande est invalide.',
				);
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($holonId)
				|| !$this->containsHolon($holon)
				|| $holon->isTemplateNode((int)$rootHolon->getId())
				|| !in_array((int)$holon->get('IDtypeholon'), array(1, 2, 3), true)
			) {
				return array(
					'status' => false,
					'message' => 'Le holon a deplacer est introuvable.',
				);
			}

			$currentParent = $holon->getParentHolon();
			if (!$currentParent) {
				return array(
					'status' => false,
					'message' => 'Le parent actuel de ce holon est introuvable.',
				);
			}

			$targetParent = new \dbObject\Holon();
			if (
				!$targetParent->load($targetParentId)
				|| !$this->containsHolon($targetParent)
			) {
				return array(
					'status' => false,
					'message' => 'Le parent cible est introuvable.',
				);
			}

			if ((int)$currentParent->getId() === $targetParentId) {
				return array(
					'status' => false,
					'message' => 'Ce holon est deja rattache a cet emplacement.',
				);
			}

			if (!$holon->canEdit() || !$currentParent->canEdit() || !$targetParent->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour deplacer ce holon.",
				);
			}

			if (!$this->canMoveHolonToParent($holon, $targetParent, $rootHolon)) {
				return array(
					'status' => false,
					'message' => "Le parent cible n'est pas compatible avec ce deplacement.",
				);
			}

			$previousParentId = (int)$currentParent->getId();
			$holon->set('IDholon_parent', $targetParentId);
			$holon->save();

			if ((int)$holon->get('IDholon_parent') !== $targetParentId) {
				return array(
					'status' => false,
					'message' => "Le holon n'a pas pu etre deplace.",
				);
			}

			$this->createMandatoryChildrenForCircle($currentParent, (int)$rootHolon->getId(), $userId);

			if (in_array((int)$holon->get('IDtypeholon'), array(2, 3), true)) {
				$this->createMandatoryChildrenForCircle($holon, (int)$rootHolon->getId(), $userId);
			}

			\dbObject\ProjectImportanceCalculator::recalculateForHolonHierarchyChange((int)$this->getId());

			return array(
				'status' => true,
				'message' => 'Holon deplace.',
				'holon' => array(
					'id' => (int)$holon->getId(),
					'name' => $holon->getDisplayName(),
					'typeId' => (int)$holon->get('IDtypeholon'),
					'typeLabel' => $holon->getTemplateLabel(),
					'parentId' => $targetParentId,
				),
				'previousParent' => array(
					'id' => $previousParentId,
					'isRoot' => (int)$currentParent->get('IDtypeholon') === 4,
				),
				'parent' => array(
					'id' => (int)$targetParent->getId(),
					'name' => $targetParent->getDisplayName(),
					'typeId' => (int)$targetParent->get('IDtypeholon'),
					'typeLabel' => $targetParent->getTemplateLabel(),
					'isRoot' => (int)$targetParent->get('IDtypeholon') === 4,
				),
			);
		}

		public function saveHolonTemplateDefinition(array $payload, $userId = 0, $contextHolonId = 0, $scope = 'contextual')
		{
			$rootHolon = $this->getStructuralRootHolon();
			$contextHolon = $this->getTemplateContextHolon($contextHolonId);
			$scope = $this->normalizeTemplateEditorScope($scope);
			$historyBeforeSnapshot = null;
			if (!$rootHolon) {
				return array(
					'status' => false,
					'message' => "Aucun holon racine n'a ete trouve pour cette organisation.",
				);
			}

			if (!$contextHolon) {
				return array(
					'status' => false,
					'message' => 'Le contexte de definition du modele est invalide.',
				);
			}

			$templateName = trim((string)($payload['name'] ?? ''));
			$iconValue = is_scalar($payload['icon'] ?? null) ? trim((string)$payload['icon']) : '';
			$bannerValue = is_scalar($payload['banner'] ?? null) ? trim((string)$payload['banner']) : '';
			$typeId = (int)($payload['typeId'] ?? 0);
			if ($templateName === '') {
				return array(
					'status' => false,
					'message' => 'Le nom du modele est obligatoire.',
				);
			}

			if ($typeId <= 0) {
				return array(
					'status' => false,
					'message' => 'Le type de base est obligatoire.',
				);
			}

			$type = new \dbObject\TypeHolon();
			if (!$type->load($typeId)) {
				return array(
					'status' => false,
					'message' => 'Le type de holon demande est introuvable.',
				);
			}

			$template = new \dbObject\Holon();
			$templateId = (int)($payload['id'] ?? 0);
			if ($templateId > 0 && !$template->load($templateId)) {
				return array(
					'status' => false,
					'message' => 'Le modele a modifier est introuvable.',
				);
			}

			if ($templateId > 0 && !$template->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour modifier ce modele.",
				);
			}

			if ($template->getId() > 0 && !$template->isTemplateNode((int)$rootHolon->getId())) {
				return array(
					'status' => false,
					'message' => "Ce modele n'appartient pas a cette organisation.",
				);
			}

			if ($template->getId() > 0) {
				$templateContextHolon = $template->getParentHolon();
				if (!$templateContextHolon || !$this->containsHolon($templateContextHolon)) {
					return array(
						'status' => false,
						'message' => "Le contexte de definition du modele est invalide.",
					);
				}
				$contextHolon = $templateContextHolon;
			} else {
				$definitionHolonId = (int)($payload['definitionHolonId'] ?? 0);
				if ($definitionHolonId > 0 && $definitionHolonId !== (int)$rootHolon->getId()) {
					$definitionHolon = new \dbObject\Holon();
					if (!$definitionHolon->load($definitionHolonId) || !$this->containsHolon($definitionHolon)) {
						return array(
							'status' => false,
							'message' => 'Le contexte de definition du modele est invalide.',
						);
					}
					$contextHolon = $definitionHolon;
				}
			}

			if (!$contextHolon->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour modifier les modeles de ce holon.",
				);
			}

			$submittedProperties = is_array($payload['properties'] ?? null)
				? array_values($payload['properties'])
				: array();
			$submittedProperties = $this->excludeRemovedPropertyDefinitions(
				$submittedProperties,
				$this->getRemovedPropertyDefinitionIds($payload)
			);
			$propertyPermissionHolon = $template->getId() > 0 ? $template : $contextHolon;
			$propertyPermissionResult = $this->canApplyPropertyDefinitionChanges(
				$propertyPermissionHolon,
				$this->getPropertyDefinitionPermissionOperations(
					$template->getId() > 0 ? $template->getTemplatePropertyDefinitions() : array(),
					$submittedProperties
				),
				'TEMPLATE'
			);
			if (empty($propertyPermissionResult['status'])) {
				return $propertyPermissionResult;
			}

			if ($template->getId() > 0) {
				$historyBeforeSnapshot = $this->buildHolonHistorySnapshot($template, array(
					'propertyMode' => 'template',
					'includePermissions' => true,
				));
			}

			$inheritsFromId = (int)($payload['inheritsFromId'] ?? 0);
			$inheritsTemplate = null;
			if ($inheritsFromId > 0) {
				$inheritsTemplate = new \dbObject\Holon();
				if (
					!$inheritsTemplate->load($inheritsFromId)
					|| !$this->isTemplateAvailableInContext($inheritsTemplate, (int)$contextHolon->getId())
				) {
					return array(
						'status' => false,
						'message' => "Le modele d'heritage choisi est invalide.",
					);
				}

				if ($template->getId() > 0) {
					$currentInheritance = $inheritsTemplate;
					$guard = 0;
					while ($currentInheritance && $guard < 100) {
						if ((int)$currentInheritance->getId() === (int)$template->getId()) {
							return array(
								'status' => false,
								'message' => "Le modele d'heritage choisi creerait une boucle.",
							);
						}

						$nextInheritanceId = (int)$currentInheritance->get('IDholon_template');
						if ($nextInheritanceId <= 0) {
							break;
						}

						$nextInheritance = new \dbObject\Holon();
						if (!$nextInheritance->load($nextInheritanceId)) {
							break;
						}

						$currentInheritance = $nextInheritance;
						$guard += 1;
					}
				}
			}

			if ($inheritsTemplate && (int)$inheritsTemplate->get('IDtypeholon') > 0) {
				$typeId = (int)$inheritsTemplate->get('IDtypeholon');
				$type = new \dbObject\TypeHolon();
				$type->load($typeId);
			}

			if ($template->getId() > 0) {
				if ($inheritsFromId > 0 && $inheritsFromId === (int)$template->getId()) {
					return array(
						'status' => false,
						'message' => "Un modele ne peut pas heriter de lui-meme.",
					);
				}
			}

			$adminMinValue = $payload['adminMin'] ?? null;
			$adminMin = $adminMinValue === null || trim((string)$adminMinValue) === ''
				? null
				: max(0, (int)$adminMinValue);
			$adminMaxValue = $payload['adminMax'] ?? null;
			$adminMax = $adminMaxValue === null || trim((string)$adminMaxValue) === ''
				? null
				: max(0, (int)$adminMaxValue);
			$inheritedAdminBounds = $inheritsTemplate
				? $inheritsTemplate->getEffectiveTemplateAdminBounds()
				: array('min' => 0, 'max' => null, 'minLocked' => false, 'maxLocked' => false);
			$inheritedAdminMinimumLocked = !empty($inheritedAdminBounds['minLocked']);
			$inheritedAdminMaximumLocked = !empty($inheritedAdminBounds['maxLocked']);
			$lockedAdminMin = !$inheritedAdminMinimumLocked && !empty($payload['lockedAdminMin']);
			$lockedAdminMax = !$inheritedAdminMaximumLocked && !empty($payload['lockedAdminMax']);
			if ($inheritedAdminMinimumLocked) {
				$adminMin = null;
			}
			if ($inheritedAdminMaximumLocked) {
				$adminMax = null;
			}
			$effectiveAdminMin = $adminMin === null
				? (int)$inheritedAdminBounds['min']
				: $adminMin;
			$effectiveAdminMax = $adminMax === null
				? $inheritedAdminBounds['max']
				: $adminMax;
			if ($effectiveAdminMax !== null && $effectiveAdminMax < $effectiveAdminMin) {
				return array(
					'status' => false,
					'message' => 'Le nombre maximum d admins doit etre superieur ou egal au minimum.',
				);
			}

			$template->set('name', $templateName);
			$template->set('templatename', $templateName);
			$template->set('IDtypeholon', $typeId);
			$template->set('IDholon_parent', (int)$contextHolon->getId());
			$template->set('IDholon_template', $inheritsFromId > 0 ? $inheritsFromId : null);
			$template->set('IDholon_org', (int)$rootHolon->getId());
			$template->set('IDorganization', null);
			$template->set('IDuser', (int)$userId > 0 ? (int)$userId : (int)$template->get('IDuser'));
			$template->set('active', true);
			$template->set('color', trim((string)($payload['color'] ?? '')) !== '' ? trim((string)$payload['color']) : null);
			$template->set('visible', !empty($payload['visible']));
			$template->set('mandatory', !empty($payload['mandatory']));
			$template->set('lockedname', !empty($payload['lockedName']));
			$template->set('lockedicon', !empty($payload['lockedIcon']));
			$template->set('lockedbanner', !empty($payload['lockedBanner']));
			$template->set('unique', !empty($payload['unique']));
			$template->set('link', !empty($payload['link']));
			$template->set('adminparent', $typeId === 1 && !empty($payload['adminParent']));
			$template->set('admin_min', $adminMin);
			$template->set('admin_max', $adminMax);
			$template->set('lockedadminmin', $lockedAdminMin);
			$template->set('lockedadminmax', $lockedAdminMax);
			$template->set('adminminoverride', false);
			$template->set('adminmaxoverride', false);
			$template->set('icon', $iconValue !== '' ? $iconValue : null);
			$template->set('banner', $bannerValue !== '' ? $bannerValue : null);
			$template->save();

			if ((int)$template->getId() <= 0) {
				return array(
					'status' => false,
					'message' => "Le modele n'a pas pu etre enregistre.",
				);
			}

			$template->syncTemplateProperties(
				$submittedProperties,
				(int)$rootHolon->getId()
			);

			$persistedTemplateDefinitions = $template->getTemplatePropertyDefinitions();
			$templateAuthorityValues = array();
			$templateAuthorityDefinitions = array();
			foreach ($persistedTemplateDefinitions as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if (
					$propertyId > 0
					&& !empty($definition['isLocal'])
					&& (string)($definition['listItemType'] ?? '') === \dbObject\Property::LIST_ITEM_AUTHORITY
					&& \dbObject\PropertyFormat::isListFormat((int)($definition['formatId'] ?? 0))
				) {
					$templateAuthorityValues[$propertyId] = $definition['value'] ?? '';
					$templateAuthorityDefinitions[] = $definition;
				}
			}
			if (count($templateAuthorityDefinitions) > 0) {
				$templateAuthoritySyncResult = $this->syncSubmittedAuthorityPropertyValues(
					$template,
					$templateAuthorityValues,
					$templateAuthorityDefinitions,
					$userId,
					array(
						'allowLocalRoot' => true,
						'isTemplateSource' => true,
					)
				);
				if (empty($templateAuthoritySyncResult['status'])) {
					return array(
						'status' => false,
						'message' => (string)($templateAuthoritySyncResult['message'] ?? 'Les autorites du modele n ont pas pu etre enregistrees.'),
					);
				}
			}
			foreach ($persistedTemplateDefinitions as &$definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0 && array_key_exists($propertyId, $templateAuthorityValues)) {
					$definition['value'] = $templateAuthorityValues[$propertyId];
				}
			}
			unset($definition);
			$template->syncTemplateProperties($persistedTemplateDefinitions, (int)$rootHolon->getId());
			$this->normalizeTemplateLocalAuthorities($template);
			$this->syncTemplateAuthorityInstances($template);

			if (!\dbObject\HolonPermission::syncAssignmentsForHolon(
				(int)$template->getId(),
				is_array($payload['permissions'] ?? null) ? $payload['permissions'] : array()
			)) {
				return array(
					'status' => false,
					'message' => "Les droits du modele n'ont pas pu etre enregistres.",
				);
			}

			$template->load((int)$template->getId(), true);
			$historyAfterSnapshot = $this->buildHolonHistorySnapshot($template, array(
				'propertyMode' => 'template',
				'includePermissions' => true,
			));
			if (is_array($historyBeforeSnapshot)) {
				$this->recordHolonUpdateHistory($template, $userId, $historyBeforeSnapshot, $historyAfterSnapshot);
			} else {
				$this->recordHolonCreatedHistory($template, $userId, $historyAfterSnapshot);
			}

			return array(
				'status' => true,
				'message' => 'Modele enregistre.',
				'template' => $template->toTemplateEditorArray((int)$rootHolon->getId()),
				'data' => $this->getHolonTemplateEditorData(
					$scope === 'descendants' ? (int)$rootHolon->getId() : (int)$contextHolon->getId(),
					$scope
				),
			);
		}

		public function deleteHolonTemplateDefinition($templateId = 0, $userId = 0, $contextHolonId = 0, $scope = 'contextual')
		{
			$rootHolon = $this->getStructuralRootHolon();
			$contextHolon = $this->getTemplateContextHolon($contextHolonId);
			$templateId = (int)$templateId;
			$scope = $this->normalizeTemplateEditorScope($scope);

			if (!$rootHolon || !$contextHolon || $templateId <= 0) {
				return array(
					'status' => false,
					'message' => 'Le modele a supprimer est invalide.',
				);
			}

			$template = new \dbObject\Holon();
			if (
				!$template->load($templateId)
				|| !$template->isTemplateNode((int)$rootHolon->getId())
			) {
				return array(
					'status' => false,
					'message' => 'Le modele a supprimer est introuvable.',
				);
			}

			$templateContextHolon = $template->getParentHolon();
			if (!$templateContextHolon || !$this->containsHolon($templateContextHolon)) {
				return array(
					'status' => false,
					'message' => 'Le modele a supprimer est introuvable.',
				);
			}
			$contextHolon = $templateContextHolon;

			if (!$contextHolon->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour modifier les modeles de ce holon.",
				);
			}

			if (!$template->canDelete()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour supprimer ce modele.",
				);
			}

			$templateName = $template->getDisplayName();
			if (!$template->delete()) {
				return array(
					'status' => false,
					'message' => "Le modele n'a pas pu etre supprime.",
				);
			}

			return array(
				'status' => true,
				'message' => 'Modele supprime.',
				'template' => array(
					'id' => $templateId,
					'name' => $templateName,
				),
				'data' => $this->getHolonTemplateEditorData(
					$scope === 'descendants' ? (int)$rootHolon->getId() : (int)$contextHolon->getId(),
					$scope
				),
			);
		}

		public function saveHolonDefinitionEditor(array $payload, $userId = 0, $holonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;

			if (!$rootHolon || $holonId <= 0) {
				return array(
					'status' => false,
					'message' => "Le holon d'organisation a modifier est invalide.",
				);
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($holonId)
				|| !$this->containsHolon($holon)
				|| (int)$holon->get('IDtypeholon') !== 4
			) {
				return array(
					'status' => false,
					'message' => "Le holon d'organisation a modifier est introuvable.",
				);
			}

			if (!$holon->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour modifier cette organisation.",
				);
			}

			$historyBeforeSnapshot = $this->buildHolonHistorySnapshot($holon, array(
				'propertyMode' => 'template',
				'includePermissions' => true,
			));

			$name = trim((string)($payload['name'] ?? ''));
			if ($name === '') {
				$name = $holon->getDisplayName();
			}

			if ($name === '') {
				return array(
					'status' => false,
					'message' => "Le nom de l'organisation est obligatoire.",
				);
			}

			$iconValue = is_scalar($payload['icon'] ?? null) ? trim((string)$payload['icon']) : '';
			$bannerValue = is_scalar($payload['banner'] ?? null) ? trim((string)$payload['banner']) : '';
			$color = trim((string)($payload['color'] ?? ''));
			$shareAsTemplate = !empty($payload['shareAsTemplate']);
			$publicTemplateName = trim((string)($payload['publicTemplateName'] ?? ''));
			$definitions = is_array($payload['properties'] ?? null)
				? array_map(function ($definition) {
					if (!is_array($definition)) {
						return array();
					}

					$definition['mandatory'] = false;
					$definition['locked'] = false;
					$definition['inheritedMandatory'] = false;
					$definition['inheritedLocked'] = false;
					$definition['effectiveMandatory'] = false;
					$definition['effectiveLocked'] = false;

					return $definition;
				}, array_values($payload['properties']))
				: array();
			$definitions = $this->excludeRemovedPropertyDefinitions(
				$definitions,
				$this->getRemovedPropertyDefinitionIds($payload)
			);

			$propertyPermissionResult = $this->canApplyPropertyDefinitionChanges(
				$holon,
				$this->getPropertyDefinitionPermissionOperations($holon->getTemplatePropertyDefinitions(), $definitions),
				'HOLON'
			);
			if (empty($propertyPermissionResult['status'])) {
				return $propertyPermissionResult;
			}

			if ($shareAsTemplate && $publicTemplateName === '') {
				return array(
					'status' => false,
					'message' => "Le nom public du modele d'organisation est obligatoire.",
				);
			}

			$holon->set('name', $name);
			$holon->set('templatename', $shareAsTemplate ? $publicTemplateName : null);
			$holon->set('color', $color !== '' ? $color : null);
			$holon->set('icon', $shareAsTemplate && $iconValue !== '' ? $iconValue : null);
			$holon->set('banner', $shareAsTemplate && $bannerValue !== '' ? $bannerValue : null);
			$holon->save();

			if ((int)$holon->getId() <= 0) {
				return array(
					'status' => false,
					'message' => "L'organisation n'a pas pu etre enregistree.",
				);
			}

			$organizationId = (int)$holon->get('IDorganization');
			if ($organizationId > 0) {
				$linkedOrganization = new self();
				if ($linkedOrganization->load($organizationId)) {
					$linkedOrganization->set('name', $name);
					$linkedOrganization->save();
				}
			}

			$submittedValuesByPropertyId = array();
			foreach ($definitions as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0) {
					$submittedValuesByPropertyId[$propertyId] = $definition['value'] ?? '';
				}
			}

			$authoritySyncResult = $this->syncSubmittedAuthorityPropertyValues($holon, $submittedValuesByPropertyId, $definitions, $userId);
			if (empty($authoritySyncResult['status'])) {
				return array(
					'status' => false,
					'message' => (string)($authoritySyncResult['message'] ?? 'Les autorites n ont pas pu etre enregistrees.'),
				);
			}
			foreach ($definitions as &$definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0 && array_key_exists($propertyId, $submittedValuesByPropertyId)) {
					$definition['value'] = $submittedValuesByPropertyId[$propertyId];
				}
			}
			unset($definition);

			$holon->syncTemplateProperties($definitions, (int)$rootHolon->getId());

			$requiresAuthorityPostSync = false;
			foreach ($definitions as $definition) {
				if (
					(int)($definition['id'] ?? 0) <= 0
					&& (string)($definition['listItemType'] ?? '') === \dbObject\Property::LIST_ITEM_AUTHORITY
					&& \dbObject\PropertyFormat::isListFormat((int)($definition['formatId'] ?? 0))
				) {
					$requiresAuthorityPostSync = true;
					break;
				}
			}

			if ($requiresAuthorityPostSync) {
				$persistedDefinitions = $holon->getTemplatePropertyDefinitions();
				$postSyncSubmittedValues = array();
				foreach ($definitions as $index => $definition) {
					$propertyId = (int)($definition['id'] ?? 0);
					if ($propertyId <= 0) {
						$propertyId = (int)($persistedDefinitions[$index]['id'] ?? 0);
					}
					if ($propertyId > 0) {
						$postSyncSubmittedValues[$propertyId] = $definition['value'] ?? '';
					}
				}

				$authorityPostSyncResult = $this->syncSubmittedAuthorityPropertyValues($holon, $postSyncSubmittedValues, $persistedDefinitions, $userId);
				if (empty($authorityPostSyncResult['status'])) {
					return array(
						'status' => false,
						'message' => (string)($authorityPostSyncResult['message'] ?? 'Les autorites n ont pas pu etre enregistrees.'),
					);
				}

				$holon->syncEditorPropertyValues($postSyncSubmittedValues, $persistedDefinitions);
			}

			if (!\dbObject\HolonPermission::syncAssignmentsForHolon(
				(int)$holon->getId(),
				is_array($payload['permissions'] ?? null) ? $payload['permissions'] : array()
			)) {
				return array(
					'status' => false,
					'message' => "Les droits de l'organisation n'ont pas pu etre enregistres.",
				);
			}

			$holon->load((int)$holon->getId(), true);
			$historyAfterSnapshot = $this->buildHolonHistorySnapshot($holon, array(
				'propertyMode' => 'template',
				'includePermissions' => true,
			));
			$this->recordHolonUpdateHistory($holon, $userId, $historyBeforeSnapshot, $historyAfterSnapshot);

			return array(
				'status' => true,
				'message' => 'Organisation enregistree.',
				'template' => $this->buildHolonDefinitionEditorNode($holon, (int)$rootHolon->getId()),
				'data' => $this->getHolonDefinitionEditorData((int)$holon->getId()),
			);
		}

		public function getApplications($userId = null)
		{
			$applications = new \dbObject\ArrayApplication();
			$applications->loadEnabledForOrganization((int)$this->getId(), $userId !== null ? (int)$userId : 0);
			return $applications;
		}

		public function isApplicationEnabled($hash, $userId = null)
		{
			static $cache = array();

			$organizationId = (int)$this->getId();
			$hash = trim(mb_strtolower((string)$hash, 'UTF-8'));
			$userId = $userId !== null
				? (int)$userId
				: (function_exists('commonGetCurrentUserId')
					? (int)\commonGetCurrentUserId()
					: (int)($_SESSION['currentUser'] ?? 0));

			$cacheKey = $organizationId . ':' . $userId . ':' . $hash;
			if (array_key_exists($cacheKey, $cache)) {
				return $cache[$cacheKey];
			}

			if ($organizationId <= 0 || $hash === '') {
				$cache[$cacheKey] = false;
				return false;
			}

			$rows = self::fetchAll(
				"SELECT
					a.id,
					a.directory,
					a.url,
					a.active AS app_active,
					a.navigationmode,
					a.requires_login,
					oa.active AS organization_active
				FROM application a
				LEFT JOIN organization_application oa
					ON oa.IDapplication = a.id
					AND oa.IDorganization = :organization_id
				WHERE LOWER(a.hash) = :hash
				ORDER BY a.id ASC
				LIMIT 1",
				array(
					'organization_id' => $organizationId,
					'hash' => $hash,
				)
			);

			if ($rows === false || count($rows) === 0) {
				$cache[$cacheKey] = true;
				return true;
			}

			$row = $rows[0];
			$probeApplication = new \dbObject\Application();
			$probeApplication->set('url', $row['url'] ?? null);
			$probeApplication->set('directory', $row['directory'] ?? null);
			$cache[$cacheKey] = (
				(int)($row['app_active'] ?? 0) === 1
				&& trim((string)($row['navigationmode'] ?? '')) !== 'panel'
				&& ((int)($row['requires_login'] ?? 0) === 0 || $userId > 0)
				&& array_key_exists('organization_active', $row)
				&& (int)$row['organization_active'] === 1
				&& $probeApplication->hasResolvedEntryPoint()
			);
			return $cache[$cacheKey];
		}

		public function isStructureApplicationEnabled($userId = null)
		{
			return $this->isApplicationEnabled('structure', $userId);
		}

		public function getEnabledStructuralRootHolon($userId = null)
		{
			if (!$this->isStructureApplicationEnabled($userId)) {
				return null;
			}

			return $this->getStructuralRootHolon();
		}

		public function getEnabledApplicationHashes($userId = null)
		{
			$hashes = array();

			foreach ($this->getApplications($userId) as $application) {
				if (!($application instanceof \dbObject\Application)) {
					continue;
				}

				$hash = trim(mb_strtolower((string)$application->getRouteHash(), 'UTF-8'));
				if ($hash === '') {
					continue;
				}

				$hashes[$hash] = $hash;
			}

			return array_values($hashes);
		}

		protected static function normalizeTopbarSearchText($value)
		{
			$value = trim((string)$value);
			if ($value === '') {
				return '';
			}

			if (function_exists('mb_strtolower')) {
				$value = mb_strtolower($value, 'UTF-8');
			} else {
				$value = strtolower($value);
			}

			$value = preg_replace('/\s+/u', ' ', $value);
			return trim((string)$value);
		}

		protected static function buildTopbarSearchTerms($query)
		{
			$normalizedQuery = self::normalizeTopbarSearchText($query);
			if ($normalizedQuery === '') {
				return array();
			}

			$terms = array($normalizedQuery);
			$tokens = preg_split('/\s+/u', $normalizedQuery) ?: array();

			foreach ($tokens as $token) {
				$token = trim((string)$token);
				if ($token === '') {
					continue;
				}

				$length = function_exists('mb_strlen')
					? (int)mb_strlen($token, 'UTF-8')
					: (int)strlen($token);
				if ($length < 2) {
					continue;
				}

				$terms[] = $token;
			}

			$terms = array_values(array_unique($terms));
			return array_slice($terms, 0, 6);
		}

		protected static function normalizeTopbarSearchDateRange(array $dateRange = array())
		{
			$result = array('startDate' => '', 'endDate' => '');
			foreach (array('startDate', 'endDate') as $key) {
				$value = trim((string)($dateRange[$key] ?? ''));
				$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
				$errors = \DateTimeImmutable::getLastErrors();
				if ($date instanceof \DateTimeImmutable && ($errors === false || ((int)$errors['warning_count'] === 0 && (int)$errors['error_count'] === 0)) && $date->format('Y-m-d') === $value) {
					$result[$key] = $value;
				}
			}
			if ($result['startDate'] !== '' && $result['endDate'] !== '' && $result['startDate'] > $result['endDate']) {
				$result['endDate'] = $result['startDate'];
			}
			return $result;
		}

		protected static function filterTopbarSearchResultsByDateRange(array $results, array $dateRange, $limit)
		{
			$startDate = (string)($dateRange['startDate'] ?? '');
			$endDate = (string)($dateRange['endDate'] ?? '');
			$filtered = array();
			foreach ($results as $result) {
				$resultDate = substr(trim((string)($result['_searchDate'] ?? '')), 0, 10);
				if (($startDate !== '' || $endDate !== '') && ($resultDate === '' || ($startDate !== '' && $resultDate < $startDate) || ($endDate !== '' && $resultDate > $endDate))) {
					continue;
				}
				unset($result['_searchDate']);
				$filtered[] = $result;
			}
			return array_slice($filtered, 0, max(1, (int)$limit));
		}

		protected static function buildTopbarSearchScoreSql($expression, array $terms, array &$params, $prefix, array $weights = array())
		{
			if (count($terms) === 0) {
				return '0';
			}

			$resolvedWeights = array_merge(array(
				'exact' => 60,
				'prefix' => 35,
				'like' => 18,
			), $weights);

			$chunks = array();

			foreach (array_values($terms) as $index => $term) {
				$paramBase = $prefix . '_' . $index;
				$params[$paramBase . '_exact'] = $term;
				$params[$paramBase . '_prefix'] = $term . '%';
				$params[$paramBase . '_like'] = '%' . $term . '%';

				$chunks[] = '(CASE'
					. ' WHEN ' . $expression . ' = :' . $paramBase . '_exact THEN ' . (int)$resolvedWeights['exact']
					. ' WHEN ' . $expression . ' LIKE :' . $paramBase . '_prefix THEN ' . (int)$resolvedWeights['prefix']
					. ' WHEN ' . $expression . ' LIKE :' . $paramBase . '_like THEN ' . (int)$resolvedWeights['like']
					. ' ELSE 0 END)';
			}

			return implode(' + ', $chunks);
		}

		protected static function buildTopbarSearchTagScoreSql($expression, array $terms, array &$params, $prefix, array $weights = array())
		{
			if (count($terms) === 0) {
				return '0';
			}

			$resolvedWeights = array_merge(array(
				'exact' => 100,
				'prefix' => 72,
				'like' => 24,
			), $weights);
			$normalizedExpression = "CONCAT(',', TRIM(BOTH ',' FROM REPLACE(REPLACE(" . $expression . ", ', ', ','), ' ,', ',')), ',')";
			$chunks = array();

			foreach (array_values($terms) as $index => $term) {
				$normalizedTerm = self::normalizeTopbarSearchText($term);
				if ($normalizedTerm === '') {
					continue;
				}

				$paramBase = $prefix . '_' . $index;
				$params[$paramBase . '_exact'] = '%,' . $normalizedTerm . ',%';
				$params[$paramBase . '_prefix'] = '%,' . $normalizedTerm . '%';
				$params[$paramBase . '_like'] = '%' . $normalizedTerm . '%';

				$chunks[] = '(CASE'
					. ' WHEN ' . $normalizedExpression . ' LIKE :' . $paramBase . '_exact THEN ' . (int)$resolvedWeights['exact']
					. ' WHEN ' . $normalizedExpression . ' LIKE :' . $paramBase . '_prefix THEN ' . (int)$resolvedWeights['prefix']
					. ' WHEN ' . $normalizedExpression . ' LIKE :' . $paramBase . '_like THEN ' . (int)$resolvedWeights['like']
					. ' ELSE 0 END)';
			}

			return count($chunks) > 0 ? implode(' + ', $chunks) : '0';
		}

		protected static function buildTopbarSearchAnyMatchSql(array $expressions, array $terms, array &$params, $prefix)
		{
			if (count($expressions) === 0 || count($terms) === 0) {
				return '1 = 0';
			}

			$chunks = array();

			foreach (array_values($expressions) as $expressionIndex => $expression) {
				foreach (array_values($terms) as $termIndex => $term) {
					$paramName = $prefix . '_' . $expressionIndex . '_' . $termIndex;
					$params[$paramName] = '%' . $term . '%';
					$chunks[] = $expression . ' LIKE :' . $paramName;
				}
			}

			return count($chunks) > 0 ? '(' . implode(' OR ', $chunks) . ')' : '1 = 0';
		}

		protected static function getTopbarSearchTextScore($value, array $terms, array $weights = array())
		{
			if (count($terms) === 0) {
				return 0;
			}

			$text = self::normalizeTopbarSearchText(self::cleanTopbarSearchTextValue($value));
			if ($text === '') {
				return 0;
			}

			$resolvedWeights = array_merge(array(
				'exact' => 60,
				'prefix' => 35,
				'like' => 18,
			), $weights);
			$score = 0;

			foreach ($terms as $term) {
				$term = self::normalizeTopbarSearchText($term);
				if ($term === '') {
					continue;
				}

				if ($text === $term) {
					$score += (int)$resolvedWeights['exact'];
					continue;
				}

				if (strpos($text, $term) === 0) {
					$score += (int)$resolvedWeights['prefix'];
					continue;
				}

				if (strpos($text, $term) !== false) {
					$score += (int)$resolvedWeights['like'];
				}
			}

			return $score;
		}

		protected static function buildTopbarStructurePropertySearchValue(\dbObject\HolonProperty $property)
		{
			$parts = array();
			$value = self::cleanTopbarSearchTextValue((string)$property->get('value'));
			$ancestorValue = self::cleanTopbarSearchTextValue(str_replace('|', ' | ', (string)$property->get('value_parents')));

			if ($value !== '') {
				$parts[] = $value;
			}
			if ($ancestorValue !== '') {
				$parts[] = $ancestorValue;
			}

			return implode(' | ', $parts);
		}

		protected static function normalizeTopbarSearchViewerContext(array $options = array())
		{
			$viewerContext = isset($options['viewerContext']) && is_array($options['viewerContext'])
				? $options['viewerContext']
				: array();
			$type = trim((string)($viewerContext['type'] ?? ''));

			if ($type === '') {
				if (function_exists('commonGetCurrentShareLink')) {
					$shareLink = \commonGetCurrentShareLink();
					if ($shareLink instanceof \dbObject\HolonShareLink) {
						return array(
							'type' => 'share',
							'organizationId' => (int)$shareLink->get('IDorganization'),
							'shareLinkId' => (int)$shareLink->getId(),
							'shareHolonId' => (int)$shareLink->get('IDholon'),
							'allowStructure' => $shareLink->allowsStructure(),
							'allowPeople' => $shareLink->allowsPeople(),
							'allowPeopleDetail' => $shareLink->allowsPeopleDetail(),
							'shareLink' => $shareLink,
						);
					}
				}

				$currentUserId = function_exists('commonGetCurrentUserId')
					? (int)\commonGetCurrentUserId()
					: (int)($_SESSION['currentUser'] ?? 0);

				if ($currentUserId > 0) {
					return array(
						'type' => 'user',
						'organizationId' => (int)($viewerContext['organizationId'] ?? ($options['organizationId'] ?? ($_SESSION['currentOrganization'] ?? 0))),
						'currentHolonId' => (int)($viewerContext['currentHolonId'] ?? 0),
						'userId' => $currentUserId,
					);
				}

				return array(
					'type' => 'public',
					'organizationId' => (int)($viewerContext['organizationId'] ?? ($options['organizationId'] ?? ($_SESSION['currentOrganization'] ?? 0))),
					'currentHolonId' => (int)($viewerContext['currentHolonId'] ?? 0),
				);
			}

			$normalized = array(
				'type' => $type,
				'organizationId' => (int)($viewerContext['organizationId'] ?? ($options['organizationId'] ?? 0)),
				'currentHolonId' => (int)($viewerContext['currentHolonId'] ?? 0),
			);

			if ($type === 'user') {
				$normalized['userId'] = (int)($viewerContext['userId'] ?? 0);
				return $normalized;
			}

			if ($type === 'share') {
				$normalized['shareLinkId'] = (int)($viewerContext['shareLinkId'] ?? 0);
				$normalized['shareHolonId'] = (int)($viewerContext['shareHolonId'] ?? 0);
				$normalized['allowStructure'] = !empty($viewerContext['allowStructure']);
				$normalized['allowPeople'] = !empty($viewerContext['allowPeople']);
				$normalized['allowPeopleDetail'] = !empty($viewerContext['allowPeopleDetail']);
				if (!empty($viewerContext['shareLink']) && $viewerContext['shareLink'] instanceof \dbObject\HolonShareLink) {
					$normalized['shareLink'] = $viewerContext['shareLink'];
				}
			}

			return $normalized;
		}

		protected static function getTopbarSearchViewerShareLink(array &$viewerContext)
		{
			if (($viewerContext['type'] ?? '') !== 'share') {
				return null;
			}

			if (!empty($viewerContext['shareLink']) && $viewerContext['shareLink'] instanceof \dbObject\HolonShareLink) {
				return $viewerContext['shareLink'];
			}

			$shareLinkId = (int)($viewerContext['shareLinkId'] ?? 0);
			$organizationId = (int)($viewerContext['organizationId'] ?? 0);
			$shareHolonId = (int)($viewerContext['shareHolonId'] ?? 0);
			if ($shareLinkId <= 0 || $organizationId <= 0 || $shareHolonId <= 0) {
				return null;
			}

			$shareLink = \dbObject\HolonShareLink::findByIdForContext($shareLinkId, $organizationId, $shareHolonId, true);
			if (
				!$shareLink
				|| !(bool)$shareLink->get('active')
				|| $shareLink->isExpired()
			) {
				return null;
			}

			$viewerContext['shareLink'] = $shareLink;
			$viewerContext['allowStructure'] = $shareLink->allowsStructure();
			$viewerContext['allowPeople'] = $shareLink->allowsPeople();
			$viewerContext['allowPeopleDetail'] = $shareLink->allowsPeopleDetail();

			return $shareLink;
		}

		protected static function topbarSearchViewerHasOrganizationAccess(array &$viewerContext, $organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return false;
			}

			$type = (string)($viewerContext['type'] ?? '');
			if ($type === 'share') {
				$shareLink = self::getTopbarSearchViewerShareLink($viewerContext);
				return $shareLink ? $shareLink->canViewOrganization($organizationId) : false;
			}

			if ($type === 'user') {
				$userId = (int)($viewerContext['userId'] ?? 0);
				return $userId > 0 && function_exists('commonUserHasOrganizationMembership')
					? \commonUserHasOrganizationMembership($userId, $organizationId)
					: false;
			}

			return $type === 'public';
		}

		protected static function topbarSearchViewerCanSearchPeople(array &$viewerContext, $organizationId)
		{
			if (!self::topbarSearchViewerHasOrganizationAccess($viewerContext, $organizationId)) {
				return false;
			}

			$type = (string)($viewerContext['type'] ?? '');
			if ($type === 'share') {
				return !empty($viewerContext['allowPeople']);
			}

			return $type === 'user';
		}

		protected static function topbarSearchViewerCanViewHolon(\dbObject\Holon $holon, array &$viewerContext)
		{
			$type = (string)($viewerContext['type'] ?? '');
			if ($type === 'share') {
				$shareLink = self::getTopbarSearchViewerShareLink($viewerContext);
				return $shareLink ? $shareLink->canViewHolon($holon) : false;
			}

			return self::topbarSearchViewerHasOrganizationAccess($viewerContext, (int)$holon->get('IDorganization') ?: (int)($viewerContext['organizationId'] ?? 0));
		}

		protected static function topbarSearchViewerCanViewUser(\dbObject\User $user, array &$viewerContext, $requireDetail = false)
		{
			$type = (string)($viewerContext['type'] ?? '');
			if ($type === 'share') {
				$shareLink = self::getTopbarSearchViewerShareLink($viewerContext);
				return $shareLink ? $shareLink->canViewUser($user, $requireDetail) : false;
			}

			if ($type === 'user') {
				return self::topbarSearchViewerHasOrganizationAccess($viewerContext, (int)($viewerContext['organizationId'] ?? 0));
			}

			return false;
		}

		protected static function topbarSearchViewerCanViewDocument(\dbObject\Document $document, array &$viewerContext, $organizationId)
		{
			$organizationId = (int)$organizationId;
			if (
				$organizationId <= 0
				|| (int)$document->getId() <= 0
				|| (int)$document->get('IDorganization') !== $organizationId
				|| !self::topbarSearchViewerHasOrganizationAccess($viewerContext, $organizationId)
			) {
				return false;
			}

			if (!array_key_exists('roleHolonIds', $viewerContext)) {
				$viewerContext['roleHolonIds'] = null;
			}

			if (!array_key_exists('circleHolonIds', $viewerContext)) {
				$viewerContext['circleHolonIds'] = null;
			}

			$viewerContext['organizationId'] = $organizationId;

			$ruleRow = \dbObject\ObjectVisibility::loadActiveRuleRow(
				\dbObject\Document::getVisibilityObjectType(),
				(int)$document->getId(),
				$organizationId
			);

			return \dbObject\ObjectVisibility::viewerCanAccessRule(
				$ruleRow,
				$viewerContext,
				array(
					'organizationId' => $organizationId,
					'ownerUserId' => (int)$document->get('IDuser'),
				)
			);
		}

		protected static function getTopbarSearchViewerScopedEmail(array &$viewerContext, $organizationId)
		{
			if ((string)($viewerContext['type'] ?? '') !== 'user') {
				return '';
			}

			if (array_key_exists('scopedEmail', $viewerContext)) {
				return trim((string)$viewerContext['scopedEmail']);
			}

			$userId = (int)($viewerContext['userId'] ?? 0);
			if ($userId <= 0) {
				$viewerContext['scopedEmail'] = '';
				return '';
			}

			$user = new \dbObject\User();
			if (!$user->load($userId)) {
				$viewerContext['scopedEmail'] = '';
				return '';
			}

			$viewerContext['scopedEmail'] = trim(mb_strtolower((string)$user->getScopedEmail((int)$organizationId), 'UTF-8'));
			return (string)$viewerContext['scopedEmail'];
		}

		protected static function topbarSearchResolveDecisionAccess(\dbObject\DecisionProcess $decision, array &$viewerContext, $organizationId)
		{
			$organizationId = (int)$organizationId;
			if (
				$organizationId <= 0
				|| (int)$decision->getId() <= 0
				|| (int)$decision->get('IDorganization') !== $organizationId
				|| !self::topbarSearchViewerHasOrganizationAccess($viewerContext, $organizationId)
			) {
				return false;
			}

			$decisionHolonId = (int)$decision->get('IDholon');
			if ($decisionHolonId > 0) {
				$decisionHolon = new \dbObject\Holon();
				if (
					!$decisionHolon->load($decisionHolonId)
					|| !self::topbarSearchViewerCanViewHolon($decisionHolon, $viewerContext)
				) {
					return false;
				}
			}

			$participant = null;
			$hasParticipation = false;
			$isOwner = false;
			$status = \dbObject\DecisionProcess::normalizeStatus($decision->get('status'));
			$visibilityAccess = $decision->currentViewerCanAccessVisibility($organizationId);

			if ((string)($viewerContext['type'] ?? '') === 'user') {
				$userId = (int)($viewerContext['userId'] ?? 0);
				if ($userId > 0) {
					$participant = \dbObject\DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $userId);
				}

				if (!$participant || (int)$participant->get('active') !== 1) {
					$scopedEmail = self::getTopbarSearchViewerScopedEmail($viewerContext, $organizationId);
					if ($scopedEmail !== '') {
						$participant = \dbObject\DecisionParticipant::findByDecisionAndEmail((int)$decision->getId(), $scopedEmail);
					}
				}

				if ($participant instanceof \dbObject\DecisionParticipant) {
					$participantStatus = \dbObject\DecisionParticipant::normalizeStatus($participant->get('status'));
					$hasParticipation = (int)$participant->get('active') === 1
						&& !in_array($participantStatus, array(
							\dbObject\DecisionParticipant::STATUS_DECLINED,
							\dbObject\DecisionParticipant::STATUS_REVOKED,
						), true);
					$isOwner = $userId > 0 && (
						(int)$decision->get('IDuser') === $userId
						|| \dbObject\DecisionParticipant::normalizeRole($participant->get('role')) === \dbObject\DecisionParticipant::ROLE_OWNER
					);
				} elseif ($userId > 0) {
					$isOwner = (int)$decision->get('IDuser') === $userId;
				}
			}

			$canManage = $isOwner;
			$canParticipate = ($isOwner || $hasParticipation) && $decision->isParticipationOpen();
			$canView = $canManage
				|| $hasParticipation
				|| ($status !== \dbObject\DecisionProcess::STATUS_DRAFT && $visibilityAccess);

			if (!$canView && !$canParticipate) {
				return false;
			}

			$intent = 'view';
			if ($canManage) {
				$intent = 'manage';
			} elseif ($canParticipate) {
				$intent = 'participate';
			}

			return array(
				'intent' => $intent,
				'canManage' => $canManage,
				'canParticipate' => $canParticipate,
				'canView' => $canView,
			);
		}

		protected static function topbarSearchViewerCanViewEvent(\dbObject\Event $event, array &$viewerContext, $organizationId)
		{
			$organizationId = (int)$organizationId;
			if (
				$organizationId <= 0
				|| (int)$event->getId() <= 0
				|| (int)$event->get('IDorganization') !== $organizationId
				|| !self::topbarSearchViewerHasOrganizationAccess($viewerContext, $organizationId)
			) {
				return false;
			}

			$eventHolonId = (int)$event->get('IDholon');
			if ($eventHolonId > 0) {
				$eventHolon = new \dbObject\Holon();
				if (
					!$eventHolon->load($eventHolonId)
					|| !self::topbarSearchViewerCanViewHolon($eventHolon, $viewerContext)
				) {
					return false;
				}
			}

			return (int)$event->get('active') === 1
				&& \dbObject\Event::normalizeStatus($event->get('status')) !== \dbObject\Event::STATUS_CANCELLED;
		}

		protected static function topbarSearchResolveAccessibleTutorialRows(array &$viewerContext, $organizationId)
		{
			if (array_key_exists('topbarTutorialRows', $viewerContext)) {
				return is_array($viewerContext['topbarTutorialRows'])
					? $viewerContext['topbarTutorialRows']
					: array();
			}

			$organizationId = (int)$organizationId;
			if (
				$organizationId <= 0
				|| !self::topbarSearchViewerHasOrganizationAccess($viewerContext, $organizationId)
			) {
				$viewerContext['topbarTutorialRows'] = array();
				return array();
			}

			$type = (string)($viewerContext['type'] ?? '');
			$userId = $type === 'user'
				? (int)($viewerContext['userId'] ?? 0)
				: 0;

			if ($type === 'user') {
				$rows = \dbObject\Parcours::fetchForOrganizationWithProgress($organizationId, $userId, true, false);
			} else {
				$rows = \dbObject\Parcours::fetchPublicForOrganizationWithProgress($organizationId, $userId);
			}

			$visibleRows = array();
			foreach ((array)$rows as $row) {
				$parcoursId = (int)($row['id'] ?? 0);
				if ($parcoursId <= 0) {
					continue;
				}

				$visibleRows[$parcoursId] = $row;
			}

			$viewerContext['topbarTutorialRows'] = $visibleRows;
			return $visibleRows;
		}

		protected static function topbarSearchViewerCanViewParcours($parcoursId, array &$viewerContext, $organizationId)
		{
			$parcoursId = (int)$parcoursId;
			if ($parcoursId <= 0) {
				return false;
			}

			$visibleRows = self::topbarSearchResolveAccessibleTutorialRows($viewerContext, $organizationId);
			return !empty($visibleRows[$parcoursId]);
		}

		protected static function topbarSearchResolveCurrentHolon(\dbObject\Organization $organization, array &$viewerContext)
		{
			if (array_key_exists('topbarCurrentHolon', $viewerContext)) {
				return $viewerContext['topbarCurrentHolon'] instanceof \dbObject\Holon
					? $viewerContext['topbarCurrentHolon']
					: null;
			}

			$currentHolon = null;
			$rootHolon = $organization->getStructuralRootHolon();
			if ($rootHolon instanceof \dbObject\Holon && (int)$rootHolon->getId() > 0) {
				$currentHolon = $rootHolon;
				$currentHolonId = (int)($viewerContext['currentHolonId'] ?? 0);

				if ($currentHolonId > 0 && $currentHolonId !== (int)$rootHolon->getId()) {
					$candidateHolon = new \dbObject\Holon();
					if (
						$candidateHolon->load($currentHolonId)
						&& $candidateHolon->isDescendantOf((int)$rootHolon->getId(), true)
					) {
						$currentHolon = $candidateHolon;
					}
				}

				if (!self::topbarSearchViewerCanViewHolon($currentHolon, $viewerContext)) {
					$currentHolon = null;
				}
			}

			$viewerContext['topbarCurrentHolon'] = $currentHolon;
			return $currentHolon;
		}

		protected static function topbarSearchViewerCanViewFaq(\dbObject\FAQ $faq, \dbObject\Organization $organization, array &$viewerContext, $scope = 'contextual')
		{
			$organizationId = (int)$organization->getId();
			if (
				$organizationId <= 0
				|| (int)$faq->getId() <= 0
				|| !self::topbarSearchViewerHasOrganizationAccess($viewerContext, $organizationId)
				|| (int)$faq->get('isactive') !== 1
			) {
				return false;
			}

			if (!$faq->isLinkedApplicationVisibleInOrganization($organizationId)) {
				return false;
			}

			if ($faq->isGeneric()) {
				return true;
			}

			$parcoursId = \dbObject\FAQ::hasParcoursColumn()
				? (int)$faq->get('IDparcours')
				: 0;
			if ($parcoursId > 0) {
				return self::topbarSearchViewerCanViewParcours($parcoursId, $viewerContext, $organizationId);
			}

			$currentHolon = self::topbarSearchResolveCurrentHolon($organization, $viewerContext);
			$currentHolonId = $currentHolon instanceof \dbObject\Holon
				? (int)$currentHolon->getId()
				: 0;
			$faqHolon = $faq->getContextHolon();
			if ($faqHolon instanceof \dbObject\Holon) {
				if (
					$currentHolonId <= 0
					|| !self::topbarSearchViewerCanViewHolon($faqHolon, $viewerContext)
				) {
					return false;
				}

				if ($scope === 'descendants') {
					return $faqHolon->isDescendantOf($currentHolonId, true);
				}

				return (int)$faqHolon->getId() === $currentHolonId;
			}

			return $faq->getResolvedOrganizationId() === $organizationId;
		}

		protected static function cleanTopbarSearchTextValue($value, $limit = 0)
		{
			$value = html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$value = preg_replace('/\s+/u', ' ', $value);
			$value = trim((string)$value);

			if ($limit > 0) {
				if (function_exists('mb_substr')) {
					if (mb_strlen($value, 'UTF-8') > $limit) {
						$value = rtrim((string)mb_substr($value, 0, $limit, 'UTF-8')) . '...';
					}
				} elseif (strlen($value) > $limit) {
					$value = rtrim(substr($value, 0, $limit)) . '...';
				}
			}

			return $value;
		}

		protected static function buildTopbarSearchSnippet($value, $query, $radius = 90, $fallbackLimit = 220)
		{
			$text = self::cleanTopbarSearchTextValue($value);
			$query = trim((string)$query);

			if ($text === '') {
				return '';
			}

			if ($query === '') {
				return self::cleanTopbarSearchTextValue($text, $fallbackLimit);
			}

			$lowerText = function_exists('mb_strtolower')
				? mb_strtolower($text, 'UTF-8')
				: strtolower($text);
			$lowerQuery = self::normalizeTopbarSearchText($query);

			if ($lowerQuery === '') {
				return self::cleanTopbarSearchTextValue($text, $fallbackLimit);
			}

			$position = function_exists('mb_stripos')
				? mb_stripos($lowerText, $lowerQuery, 0, 'UTF-8')
				: stripos($lowerText, $lowerQuery);

			if ($position === false) {
				return self::cleanTopbarSearchTextValue($text, $fallbackLimit);
			}

			$queryLength = function_exists('mb_strlen')
				? (int)mb_strlen($lowerQuery, 'UTF-8')
				: (int)strlen($lowerQuery);
			$textLength = function_exists('mb_strlen')
				? (int)mb_strlen($text, 'UTF-8')
				: (int)strlen($text);

			$start = max(0, (int)$position - (int)$radius);
			$length = min($textLength - $start, ((int)$radius * 2) + $queryLength);
			$snippet = function_exists('mb_substr')
				? (string)mb_substr($text, $start, $length, 'UTF-8')
				: (string)substr($text, $start, $length);

			if ($start > 0) {
				$snippet = '... ' . ltrim($snippet);
			}

			if ($start + $length < $textLength) {
				$snippet = rtrim($snippet) . ' ...';
			}

			return trim($snippet);
		}

		protected static function getTopbarSearchHolonTypeLabel($typeId)
		{
			switch ((int)$typeId) {
				case 1:
					return 'Role';
				case 2:
					return 'Cercle';
				case 3:
					return 'Groupe';
				case 4:
					return 'Organisation';
				default:
					return 'Holon';
			}
		}

		protected function searchTopbarFaqResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			if (
				(int)$this->getId() <= 0
				|| count($terms) === 0
				|| !\dbObject\FAQ::hasFaqTable()
			) {
				return array();
			}

			$faqCollection = new \dbObject\ArrayFAQ();
			$faqCollection->load(array(
				'orderBy' => \dbObject\FAQ::getPopupOrderBy(),
			));

			$results = array();

			foreach ($faqCollection as $faq) {
				if (
					!($faq instanceof \dbObject\FAQ)
					|| !self::topbarSearchViewerCanViewFaq($faq, $this, $viewerContext, 'contextual')
				) {
					continue;
				}

				$question = trim((string)$faq->get('question'));
				$answer = self::cleanTopbarSearchTextValue((string)$faq->get('answer'));
				$detail = self::cleanTopbarSearchTextValue((string)$faq->get('detail'));
				$questionScore = self::getTopbarSearchTextScore($question, $terms, array(
					'exact' => 110,
					'prefix' => 70,
					'like' => 36,
				));
				$answerScore = self::getTopbarSearchTextScore($answer, $terms, array(
					'exact' => 34,
					'prefix' => 20,
					'like' => 12,
				));
				$detailScore = self::getTopbarSearchTextScore($detail, $terms, array(
					'exact' => 24,
					'prefix' => 14,
					'like' => 8,
				));

				$subtitleParts = array();
				$contextCandidates = array();
				$parcours = $faq->getContextParcours();
				$holon = $faq->getContextHolon();

				if ($parcours instanceof \dbObject\Parcours) {
					$parcoursTitle = trim((string)$parcours->get('title'));
					if ($parcoursTitle !== '') {
						$subtitleParts[] = 'Tutoriel';
						$subtitleParts[] = $parcoursTitle;
						$contextCandidates[] = $parcoursTitle;
					}
				} elseif ($holon instanceof \dbObject\Holon) {
					$holonLabel = trim((string)$holon->getDisplayName());
					if ($holonLabel !== '') {
						$subtitleParts[] = self::getTopbarSearchHolonTypeLabel((int)$holon->get('IDtypeholon'));
						$subtitleParts[] = $holonLabel;
						$contextCandidates[] = $holonLabel;
					}
				} elseif ($faq->getResolvedOrganizationId() === (int)$this->getId()) {
					$organizationLabel = trim((string)$this->get('name'));
					if ($organizationLabel !== '') {
						$subtitleParts[] = $organizationLabel;
						$contextCandidates[] = $organizationLabel;
					}
				} else {
					$subtitleParts[] = 'Generique';
				}

				$contextScore = 0;
				foreach ($contextCandidates as $contextCandidate) {
					$contextScore += self::getTopbarSearchTextScore($contextCandidate, $terms, array(
						'exact' => 14,
						'prefix' => 8,
						'like' => 4,
					));
				}

				$totalScore = $questionScore + $answerScore + $detailScore + $contextScore;
				if ($totalScore <= 0) {
					continue;
				}

				$snippetCandidates = array(
					$answer,
					$detail,
					$question,
				);
				$snippetSource = '';
				$snippetScore = -1;
				foreach ($snippetCandidates as $candidate) {
					$candidate = trim((string)$candidate);
					if ($candidate === '') {
						continue;
					}

					$candidateScore = self::getTopbarSearchTextScore($candidate, $terms);
					if ($candidateScore <= $snippetScore) {
						continue;
					}

					$snippetScore = $candidateScore;
					$snippetSource = $candidate;
				}

				$results[] = array(
					'module' => 'faq',
					'moduleLabel' => 'FAQ',
					'title' => $question !== '' ? $question : ('FAQ #' . (int)$faq->getId()),
					'subtitle' => implode(' | ', array_values(array_filter($subtitleParts, function ($part) {
						return trim((string)$part) !== '';
					}))),
					'excerpt' => self::buildTopbarSearchSnippet($snippetSource, $query, 100, 220),
					'relevance' => $totalScore,
					'_searchDate' => (string)$faq->get('created'),
					'action' => array(
						'type' => 'faq',
						'faqId' => (int)$faq->getId(),
					),
				);
			}

			usort($results, function ($left, $right) {
				$leftScore = (int)($left['relevance'] ?? 0);
				$rightScore = (int)($right['relevance'] ?? 0);
				if ($leftScore !== $rightScore) {
					return $rightScore <=> $leftScore;
				}

				$leftId = (int)($left['action']['faqId'] ?? 0);
				$rightId = (int)($right['action']['faqId'] ?? 0);
				return $rightId <=> $leftId;
			});

			return array_slice($results, 0, max(1, (int)$limit));
		}

		protected function searchTopbarTutorialResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			$organizationId = (int)$this->getId();
			if (
				$organizationId <= 0
				|| count($terms) === 0
				|| !self::topbarSearchViewerHasOrganizationAccess($viewerContext, $organizationId)
			) {
				return array();
			}

			$visibleParcoursRows = self::topbarSearchResolveAccessibleTutorialRows($viewerContext, $organizationId);
			if (count($visibleParcoursRows) === 0) {
				return array();
			}

			$results = array();

			foreach ($visibleParcoursRows as $parcoursId => $parcoursRow) {
				$title = trim((string)($parcoursRow['title'] ?? ''));
				$description = self::cleanTopbarSearchTextValue((string)($parcoursRow['description'] ?? ''));
				$titleScore = self::getTopbarSearchTextScore($title, $terms, array(
					'exact' => 104,
					'prefix' => 64,
					'like' => 34,
				));
				$descriptionScore = self::getTopbarSearchTextScore($description, $terms, array(
					'exact' => 34,
					'prefix' => 20,
					'like' => 12,
				));
				$totalScore = $titleScore + $descriptionScore;
				if ($totalScore <= 0) {
					continue;
				}

				$isPack = !empty($parcoursRow['ispack']);
				$results[] = array(
					'module' => 'tutorials',
					'moduleLabel' => 'Tutoriels',
					'title' => $title !== '' ? $title : ('Parcours #' . (int)$parcoursId),
					'subtitle' => $isPack ? 'Pack' : 'Parcours',
					'excerpt' => self::buildTopbarSearchSnippet($description !== '' ? $description : $title, $query, 100, 220),
					'relevance' => $totalScore,
					'_searchDate' => (string)($parcoursRow['datecreation'] ?? ''),
					'_sortKind' => 1,
					'action' => array(
						'type' => 'tutorial',
						'parcoursId' => (int)$parcoursId,
						'missionId' => 0,
					),
				);
			}

			$missionParcoursIds = array();
			foreach ($visibleParcoursRows as $parcoursId => $parcoursRow) {
				if (!empty($parcoursRow['ispack'])) {
					continue;
				}

				$missionParcoursIds[] = (int)$parcoursId;
			}

			if (count($missionParcoursIds) > 0) {
				$missionParams = array();
				$missionPlaceholders = self::buildIntPlaceholders($missionParcoursIds, 'tutorial_parcours', $missionParams);
				$missionRows = self::fetchAll(
					"SELECT
						m.id,
						m.title,
						m.resume,
						m.html,
						m.datecreation,
						pm.IDparcours,
						pm.branch
					FROM mission m
					INNER JOIN parcours_mission pm
						ON pm.IDmission = m.id
					WHERE pm.IDparcours IN (" . implode(', ', $missionPlaceholders) . ")
					ORDER BY pm.IDparcours ASC, COALESCE(pm.position, m.position, m.id) ASC, pm.id ASC",
					$missionParams
				);

				foreach ((array)$missionRows as $missionRow) {
					$parcoursId = (int)($missionRow['IDparcours'] ?? 0);
					$parcoursRow = $visibleParcoursRows[$parcoursId] ?? null;
					if (!is_array($parcoursRow)) {
						continue;
					}

					$missionTitle = trim((string)($missionRow['title'] ?? ''));
					$missionResume = self::cleanTopbarSearchTextValue((string)($missionRow['resume'] ?? ''));
					$missionHtml = self::cleanTopbarSearchTextValue((string)($missionRow['html'] ?? ''));
					$parcoursTitle = trim((string)($parcoursRow['title'] ?? ''));
					$branchLabel = trim((string)($missionRow['branch'] ?? ''));
					$missionScore =
						self::getTopbarSearchTextScore($missionTitle, $terms, array(
							'exact' => 98,
							'prefix' => 62,
							'like' => 32,
						))
						+ self::getTopbarSearchTextScore($missionResume, $terms, array(
							'exact' => 34,
							'prefix' => 20,
							'like' => 12,
						))
						+ self::getTopbarSearchTextScore($missionHtml, $terms, array(
							'exact' => 22,
							'prefix' => 14,
							'like' => 8,
						))
						+ self::getTopbarSearchTextScore($parcoursTitle, $terms, array(
							'exact' => 16,
							'prefix' => 10,
							'like' => 6,
						))
						+ self::getTopbarSearchTextScore($branchLabel, $terms, array(
							'exact' => 10,
							'prefix' => 6,
							'like' => 4,
						));

					if ($missionScore <= 0) {
						continue;
					}

					$subtitleParts = array('Mission');
					if ($parcoursTitle !== '') {
						$subtitleParts[] = $parcoursTitle;
					}
					if ($branchLabel !== '') {
						$subtitleParts[] = $branchLabel;
					}

					$snippetCandidates = array(
						$missionResume,
						$missionHtml,
						$parcoursTitle,
					);
					$snippetSource = '';
					$snippetScore = -1;
					foreach ($snippetCandidates as $candidate) {
						$candidate = trim((string)$candidate);
						if ($candidate === '') {
							continue;
						}

						$candidateScore = self::getTopbarSearchTextScore($candidate, $terms);
						if ($candidateScore <= $snippetScore) {
							continue;
						}

						$snippetScore = $candidateScore;
						$snippetSource = $candidate;
					}

					$results[] = array(
						'module' => 'tutorials',
						'moduleLabel' => 'Tutoriels',
						'title' => $missionTitle !== '' ? $missionTitle : ('Mission #' . (int)($missionRow['id'] ?? 0)),
						'subtitle' => implode(' | ', $subtitleParts),
						'excerpt' => self::buildTopbarSearchSnippet($snippetSource, $query, 100, 220),
						'relevance' => $missionScore,
						'_searchDate' => (string)($missionRow['datecreation'] ?? ''),
						'_sortKind' => 2,
						'action' => array(
							'type' => 'tutorial',
							'parcoursId' => $parcoursId,
							'missionId' => (int)($missionRow['id'] ?? 0),
						),
					);
				}
			}

			usort($results, function ($left, $right) {
				$leftScore = (int)($left['relevance'] ?? 0);
				$rightScore = (int)($right['relevance'] ?? 0);
				if ($leftScore !== $rightScore) {
					return $rightScore <=> $leftScore;
				}

				$leftKind = (int)($left['_sortKind'] ?? 99);
				$rightKind = (int)($right['_sortKind'] ?? 99);
				if ($leftKind !== $rightKind) {
					return $leftKind <=> $rightKind;
				}

				return strcmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
			});

			$results = array_slice($results, 0, max(1, (int)$limit));
			foreach ($results as &$result) {
				unset($result['_sortKind']);
			}
			unset($result);

			return $results;
		}

		protected function searchTopbarStructureResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			$rootHolon = $this->getEnabledStructuralRootHolon(isset($viewerContext['userId']) ? (int)$viewerContext['userId'] : null);
			if (!$rootHolon || (int)$rootHolon->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$rows = self::fetchAll(
				"SELECT
					h.id,
					h.name,
					h.templatename,
					h.IDtypeholon,
					h.datecreation,
					h.datemodification
				FROM holon h
				WHERE h.IDholon_org = :root_holon_id
				  AND h.active = 1
				  AND h.visible = 1
				  AND h.IDtypeholon IN (1, 2, 3)
				ORDER BY h.datemodification DESC, h.id DESC",
				array(
					'root_holon_id' => (int)$rootHolon->getId(),
				)
			);

			if ($rows === false) {
				return array();
			}

			$results = array();

			foreach ($rows as $row) {
				$holon = new \dbObject\Holon();
				if (
					!$holon->load((int)($row['id'] ?? 0))
					|| !self::topbarSearchViewerCanViewHolon($holon, $viewerContext)
				) {
					continue;
				}

				$nameScore = self::getTopbarSearchTextScore(
					trim((string)$holon->getDisplayName() . ' ' . (string)$holon->get('templatename')),
					$terms,
					array(
						'exact' => 90,
						'prefix' => 55,
						'like' => 28,
					)
				);

				$propertyScore = 0;
				$matchedExcerpt = '';
				$matchedExcerptScore = 0;

				foreach ($holon->getPropertiesValue() as $property) {
					$propertyLabel = trim((string)$property->get('name') . ' ' . (string)$property->get('shortname'));
					$propertyValue = self::buildTopbarStructurePropertySearchValue($property);
					$propertyRowScore =
						self::getTopbarSearchTextScore($propertyLabel, $terms, array(
							'exact' => 26,
							'prefix' => 16,
							'like' => 10,
						))
						+ self::getTopbarSearchTextScore($propertyValue, $terms, array(
							'exact' => 18,
							'prefix' => 10,
							'like' => 6,
						));

					if ($propertyRowScore <= 0) {
						continue;
					}

					$propertyScore += $propertyRowScore;

					if ($propertyRowScore > $matchedExcerptScore) {
						$matchedExcerptScore = $propertyRowScore;
						$matchedExcerpt = trim($propertyLabel);
						if ($propertyValue !== '') {
							$matchedExcerpt .= ($matchedExcerpt !== '' ? ': ' : '') . $propertyValue;
						}
					}
				}

				$totalScore = $nameScore + $propertyScore;
				if ($totalScore <= 0) {
					continue;
				}

				$pathLabels = array();
				foreach ($holon->getPathHolons() as $pathHolon) {
					if ((int)$pathHolon->get('IDtypeholon') === 4 || (int)$pathHolon->getId() === (int)$holon->getId()) {
						continue;
					}

					$pathLabels[] = trim((string)$pathHolon->getDisplayName());
				}

				if ($matchedExcerpt === '' && $nameScore > 0) {
					$matchedExcerpt = $holon->getDisplayName();
				}

				$matchedExcerpt = self::buildTopbarSearchSnippet($matchedExcerpt, $query, 80, 180);
				$subtitle = self::getTopbarSearchHolonTypeLabel((int)($row['IDtypeholon'] ?? 0));
				if (count($pathLabels) > 0) {
					$subtitle .= ' - ' . implode(' > ', $pathLabels);
				}

				$results[] = array(
					'module' => 'structure',
					'moduleLabel' => 'Structure',
					'title' => $holon->getDisplayName(),
					'subtitle' => $subtitle,
					'excerpt' => $matchedExcerpt,
					'relevance' => $totalScore,
					'_searchDate' => (string)($row['datecreation'] ?? ''),
					'datemodification' => (string)($row['datemodification'] ?? ''),
					'action' => array(
						'type' => 'structure',
						'holonId' => (int)$holon->getId(),
					),
				);
			}

			usort($results, function ($left, $right) {
				$leftScore = (int)($left['relevance'] ?? 0);
				$rightScore = (int)($right['relevance'] ?? 0);
				if ($leftScore !== $rightScore) {
					return $rightScore <=> $leftScore;
				}

				$leftDate = (string)($left['datemodification'] ?? '');
				$rightDate = (string)($right['datemodification'] ?? '');
				if ($leftDate !== $rightDate) {
					return strcmp($rightDate, $leftDate);
				}

				$leftId = (int)($left['action']['holonId'] ?? 0);
				$rightId = (int)($right['action']['holonId'] ?? 0);
				return $rightId <=> $leftId;
			});

			$results = array_slice($results, 0, max(1, (int)$limit));
			foreach ($results as &$result) {
				unset($result['datemodification']);
			}
			unset($result);

			return $results;
		}

		protected function searchTopbarTeamResults($query, array $terms, $limit = 10, array $viewerContext = array())
		{
			if ((int)$this->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$params = array(
				'organization_id' => (int)$this->getId(),
				'team_competence_scope_org' => (int)$this->getId(),
			);

			$identityExpr = "LOWER(CONCAT_WS(' ', COALESCE(u.firstname, ''), COALESCE(u.lastname, ''), COALESCE(NULLIF(uo.username, ''), u.username, ''), COALESCE(NULLIF(uo.email, ''), u.email, '')))";
			$parameterExpr = "LOWER(CONCAT_WS(' ', COALESCE(u.parameters, ''), COALESCE(uo.parameters, '')))";
			$competenceNameExpr = "LOWER(COALESCE(c_skill.name, ''))";
			$competenceDescriptionExpr = "LOWER(COALESCE(uc_skill.description, ''))";

			$identityScoreSql = self::buildTopbarSearchScoreSql($identityExpr, $terms, $params, 'team_identity', array(
				'exact' => 80,
				'prefix' => 48,
				'like' => 24,
			));
			$parameterScoreSql = self::buildTopbarSearchScoreSql($parameterExpr, $terms, $params, 'team_parameters', array(
				'exact' => 16,
				'prefix' => 10,
				'like' => 5,
			));
			$competenceNameScoreSql = self::buildTopbarSearchScoreSql($competenceNameExpr, $terms, $params, 'team_competence_name', array(
				'exact' => 54,
				'prefix' => 32,
				'like' => 16,
			));
			$competenceDescriptionScoreSql = self::buildTopbarSearchScoreSql($competenceDescriptionExpr, $terms, $params, 'team_competence_description', array(
				'exact' => 28,
				'prefix' => 16,
				'like' => 8,
			));
			$competenceNameRelevanceSql = self::buildTopbarSearchScoreSql($competenceNameExpr, $terms, $params, 'team_competence_name_relevance', array(
				'exact' => 54,
				'prefix' => 32,
				'like' => 16,
			));
			$competenceDescriptionRelevanceSql = self::buildTopbarSearchScoreSql($competenceDescriptionExpr, $terms, $params, 'team_competence_description_relevance', array(
				'exact' => 28,
				'prefix' => 16,
				'like' => 8,
			));
			$preFilterSql = self::buildTopbarSearchAnyMatchSql(
				array($identityExpr, $parameterExpr, $competenceNameExpr, $competenceDescriptionExpr),
				$terms,
				$params,
				'team_prefilter'
			);
			$limitSql = max(1, (int)$limit);

			$rows = self::fetchAll(
				"SELECT
					u.id,
					u.firstname,
					u.lastname,
					COALESCE(NULLIF(uo.username, ''), u.username, '') AS scoped_username,
					COALESCE(NULLIF(uo.email, ''), u.email, '') AS scoped_email,
					MAX(uo.active) AS membership_active,
					MAX(uo.dateconnexion) AS membership_last_connection,
					MAX(uo.datecreation) AS membership_created_at,
					GROUP_CONCAT(
						DISTINCT NULLIF(
							CONCAT(
								COALESCE(c_skill.name, ''),
								CASE
									WHEN TRIM(COALESCE(uc_skill.description, '')) <> '' THEN ': '
									ELSE ''
								END,
								COALESCE(uc_skill.description, '')
							),
							''
						)
						SEPARATOR ' || '
					) AS competence_excerpt_source,
					COALESCE(SUM(" . $competenceNameScoreSql . " + " . $competenceDescriptionScoreSql . "), 0) AS competence_relevance,
					(
						MAX(" . $identityScoreSql . ")
						+ MAX(" . $parameterScoreSql . ")
						+ COALESCE(SUM(" . $competenceNameRelevanceSql . " + " . $competenceDescriptionRelevanceSql . "), 0)
					) AS relevance
				FROM user_organization uo
				INNER JOIN user u
					ON u.id = uo.IDuser
				LEFT JOIN user_competence uc_skill
					ON uc_skill.IDuser = u.id
					AND (
						uc_skill.IDorganization IS NULL
						OR uc_skill.IDorganization = :team_competence_scope_org
					)
				LEFT JOIN competence c_skill
					ON c_skill.id = uc_skill.IDcompetence
				WHERE uo.IDorganization = :organization_id
				  AND " . $preFilterSql . "
				GROUP BY
					u.id,
					u.firstname,
					u.lastname,
					uo.username,
					u.username,
					uo.email,
					u.email
				HAVING relevance > 0
				ORDER BY relevance DESC, membership_last_connection DESC, membership_created_at DESC, u.id DESC
				LIMIT " . $limitSql,
				$params
			);

			if ($rows === false) {
				return array();
			}

			$results = array();

			foreach ($rows as $row) {
				$user = new \dbObject\User();
				if (
					!$user->load((int)($row['id'] ?? 0))
					|| !self::topbarSearchViewerCanViewUser($user, $viewerContext, false)
				) {
					continue;
				}

				$fullName = trim((string)($row['firstname'] ?? '') . ' ' . (string)($row['lastname'] ?? ''));
				$scopedUsername = trim((string)($row['scoped_username'] ?? ''));
				$scopedEmail = trim((string)($row['scoped_email'] ?? ''));
				$title = $fullName !== '' ? $fullName : ($scopedUsername !== '' ? $scopedUsername : $scopedEmail);
				if ($title === '') {
					$title = 'Membre #' . (int)($row['id'] ?? 0);
				}

				$subtitleParts = array();
				if ($scopedUsername !== '') {
					$subtitleParts[] = '@' . $scopedUsername;
				}
				if ($scopedEmail !== '') {
					$subtitleParts[] = $scopedEmail;
				}

				$matchedCompetenceExcerpt = trim((string)($row['competence_excerpt_source'] ?? ''));
				$competenceRelevance = (int)($row['competence_relevance'] ?? 0);
				$excerpt = '';
				if ($competenceRelevance > 0 && $matchedCompetenceExcerpt !== '') {
					$excerpt = self::buildTopbarSearchSnippet($matchedCompetenceExcerpt, $query, 90, 220);
				}
				if ($excerpt === '' && (int)($row['membership_active'] ?? 0) !== 1) {
					$excerpt = 'Membre en attente ou inactif.';
				}

				$results[] = array(
					'module' => 'team',
					'moduleLabel' => 'Team',
					'title' => $title,
					'subtitle' => implode(' - ', $subtitleParts),
					'excerpt' => $excerpt,
					'relevance' => (int)($row['relevance'] ?? 0),
					'_searchDate' => (string)($row['membership_created_at'] ?? ''),
					'action' => array(
						'type' => 'user',
						'userId' => (int)($row['id'] ?? 0),
					),
				);
			}

			return $results;
		}

		protected function searchTopbarDocumentResults($query, array $terms, $limit = 12, array $viewerContext = array(), $documentType = null)
		{
			if ((int)$this->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$params = array(
				'organization_id' => (int)$this->getId(),
			);
			$isPvSearch = $documentType === \dbObject\Document::TYPE_PV;
			$params['document_type_pv'] = \dbObject\Document::TYPE_PV;

			$titleExpr = "LOWER(COALESCE(d.title, ''))";
			$descriptionExpr = "LOWER(COALESCE(d.description, ''))";
			$keywordsExpr = "LOWER(COALESCE(d.keywords, ''))";
			$contentExpr = "LOWER(COALESCE(d.content, ''))";
			$pvPointTitleExpr = $isPvSearch ? "LOWER(COALESCE(pv_search.point_titles, ''))" : '0';
			$pvPointContentExpr = $isPvSearch ? "LOWER(COALESCE(pv_search.point_contents, ''))" : '0';

			$titleScoreSql = self::buildTopbarSearchScoreSql($titleExpr, $terms, $params, 'document_title', array(
				'exact' => 100,
				'prefix' => 65,
				'like' => 34,
			));
			$descriptionScoreSql = self::buildTopbarSearchScoreSql($descriptionExpr, $terms, $params, 'document_description', array(
				'exact' => 30,
				'prefix' => 18,
				'like' => 10,
			));
			$keywordsScoreSql = self::buildTopbarSearchTagScoreSql($keywordsExpr, $terms, $params, 'document_keywords', array(
				'exact' => 100,
				'prefix' => 72,
				'like' => 24,
			));
			$contentScoreSql = self::buildTopbarSearchScoreSql($contentExpr, $terms, $params, 'document_content', array(
				'exact' => 18,
				'prefix' => 12,
				'like' => 6,
			));
			$pvPointTitleScoreSql = self::buildTopbarSearchScoreSql($pvPointTitleExpr, $terms, $params, 'document_pv_point_title', array(
				'exact' => 44,
				'prefix' => 28,
				'like' => 14,
			));
			$pvPointContentScoreSql = self::buildTopbarSearchScoreSql($pvPointContentExpr, $terms, $params, 'document_pv_point_content', array(
				'exact' => 30,
				'prefix' => 18,
				'like' => 9,
			));
			$preFilterSql = self::buildTopbarSearchAnyMatchSql(
				array($titleExpr, $descriptionExpr, $keywordsExpr, $contentExpr, $pvPointTitleExpr, $pvPointContentExpr),
				$terms,
				$params,
				'document_prefilter'
			);
			$limitSql = max(1, (int)$limit);

			$documentTypeSql = $isPvSearch
				? 'AND d.documenttype = :document_type_pv'
				: 'AND COALESCE(d.documenttype, \'\') <> :document_type_pv';
			$pvJoinSql = $isPvSearch
				? "LEFT JOIN (\n\t\t\t\t\tSELECT IDdocument, GROUP_CONCAT(COALESCE(title, '') SEPARATOR ' ') AS point_titles, GROUP_CONCAT(COALESCE(content, '') SEPARATOR ' ') AS point_contents\n\t\t\t\t\tFROM document_pv_point\n\t\t\t\t\tWHERE item_type = 'point'\n\t\t\t\t\tGROUP BY IDdocument\n\t\t\t\t) pv_search ON pv_search.IDdocument = d.id"
				: '';
			$pvSelectSql = $isPvSearch
				? 'pv_search.point_titles, pv_search.point_contents'
				: "'' AS point_titles, '' AS point_contents";

			$rows = self::fetchAll(
				"SELECT
					d.id,
					d.title,
					d.description,
					d.keywords,
					d.content,
					d.IDholon,
					d.datecreation,
					d.datemodification,
					(" . $titleScoreSql . " + " . $descriptionScoreSql . " + " . $keywordsScoreSql . " + " . $contentScoreSql . " + " . $pvPointTitleScoreSql . " + " . $pvPointContentScoreSql . ") AS relevance,
					" . $pvSelectSql . "
				FROM document d
				" . $pvJoinSql . "
				WHERE d.IDorganization = :organization_id
				  " . $documentTypeSql . "
				  AND " . $preFilterSql . "
				HAVING relevance > 0
				ORDER BY relevance DESC, d.datemodification DESC, d.datecreation DESC, d.id DESC
				LIMIT " . $limitSql,
				$params
			);

			if ($rows === false) {
				return array();
			}

			$results = array();

			foreach ($rows as $row) {
				$document = new \dbObject\Document();
				if (!$document->load((int)($row['id'] ?? 0))) {
					continue;
				}

				if (!self::topbarSearchViewerCanViewDocument($document, $viewerContext, (int)$this->getId())) {
					continue;
				}

				$subtitle = $document->getOrganizationContextLabel();
				$pvSnippetSource = trim((string)($row['point_titles'] ?? '') . ' ' . (string)($row['point_contents'] ?? ''));
				$snippetSource = $isPvSearch && $pvSnippetSource !== ''
					? $pvSnippetSource
					: (trim((string)($row['description'] ?? '')) !== ''
					? (string)($row['description'] ?? '')
					: ((trim((string)($row['keywords'] ?? '')) !== '' ? (string)($row['keywords'] ?? '') : (string)($row['content'] ?? ''))));
				$detailUrl = '/omo/api/documents/detail.php?id=' . (int)$document->getId() . '&oid=' . (int)$this->getId();
				if ((int)$document->get('IDholon') > 0) {
					$detailUrl .= '&cid=' . (int)$document->get('IDholon');
				}

				$results[] = array(
					'module' => $isPvSearch ? 'pv' : 'documents',
					'moduleLabel' => $isPvSearch ? 'PV' : 'Documents',
					'title' => trim((string)$document->get('title')) !== '' ? (string)$document->get('title') : ('Document #' . (int)$document->getId()),
					'subtitle' => $subtitle,
					'excerpt' => self::buildTopbarSearchSnippet($snippetSource, $query, 100, 220),
					'relevance' => (int)($row['relevance'] ?? 0),
					'_searchDate' => (string)($row['datecreation'] ?? ''),
					'action' => array(
						'type' => 'document',
						'documentId' => (int)$document->getId(),
						'documentUrl' => $detailUrl,
					),
				);
			}

			return $results;
		}

		protected function searchTopbarDecisionResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			if ((int)$this->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$params = array(
				'organization_id' => (int)$this->getId(),
			);

			$processTitleExpr = "LOWER(COALESCE(search_rows.process_title, ''))";
			$processDescriptionExpr = "LOWER(COALESCE(search_rows.process_description, ''))";
			$groupTitleExpr = "LOWER(COALESCE(search_rows.group_titles, ''))";
			$groupDescriptionExpr = "LOWER(COALESCE(search_rows.group_descriptions, ''))";
			$proposalTitleExpr = "LOWER(COALESCE(search_rows.proposal_titles, ''))";
			$proposalDescriptionExpr = "LOWER(COALESCE(search_rows.proposal_descriptions, ''))";

			$processTitleScoreSql = self::buildTopbarSearchScoreSql($processTitleExpr, $terms, $params, 'decision_process_title', array(
				'exact' => 110,
				'prefix' => 72,
				'like' => 38,
			));
			$processDescriptionScoreSql = self::buildTopbarSearchScoreSql($processDescriptionExpr, $terms, $params, 'decision_process_description', array(
				'exact' => 34,
				'prefix' => 22,
				'like' => 12,
			));
			$groupTitleScoreSql = self::buildTopbarSearchScoreSql($groupTitleExpr, $terms, $params, 'decision_group_title', array(
				'exact' => 92,
				'prefix' => 58,
				'like' => 30,
			));
			$groupDescriptionScoreSql = self::buildTopbarSearchScoreSql($groupDescriptionExpr, $terms, $params, 'decision_group_description', array(
				'exact' => 42,
				'prefix' => 28,
				'like' => 16,
			));
			$proposalTitleScoreSql = self::buildTopbarSearchScoreSql($proposalTitleExpr, $terms, $params, 'decision_proposal_title', array(
				'exact' => 76,
				'prefix' => 48,
				'like' => 26,
			));
			$proposalDescriptionScoreSql = self::buildTopbarSearchScoreSql($proposalDescriptionExpr, $terms, $params, 'decision_proposal_description', array(
				'exact' => 28,
				'prefix' => 18,
				'like' => 10,
			));
			$preFilterSql = self::buildTopbarSearchAnyMatchSql(
				array(
					$processTitleExpr,
					$processDescriptionExpr,
					$groupTitleExpr,
					$groupDescriptionExpr,
					$proposalTitleExpr,
					$proposalDescriptionExpr,
				),
				$terms,
				$params,
				'decision_prefilter'
			);
			$limitSql = max(1, (int)$limit);

			$rows = self::fetchAll(
				"SELECT
					search_rows.*,
					(" . $processTitleScoreSql . " + " . $processDescriptionScoreSql . " + " . $groupTitleScoreSql . " + " . $groupDescriptionScoreSql . " + " . $proposalTitleScoreSql . " + " . $proposalDescriptionScoreSql . ") AS relevance
				FROM (
					SELECT
						dp.`id`,
						dp.`title` AS `process_title`,
						dp.`description` AS `process_description`,
						dp.`IDholon`,
						dp.`IDuser`,
						dp.`status`,
						dp.`evaluation_method`,
						dp.`created_at`,
						dp.`updated_at`,
						h.`name` AS `holon_name`,
						COALESCE(GROUP_CONCAT(DISTINCT NULLIF(dg.`title`, '') ORDER BY dg.`position` ASC, dg.`id` ASC SEPARATOR ' | '), '') AS `group_titles`,
						COALESCE(GROUP_CONCAT(DISTINCT NULLIF(dg.`description`, '') ORDER BY dg.`position` ASC, dg.`id` ASC SEPARATOR ' | '), '') AS `group_descriptions`,
						COALESCE(GROUP_CONCAT(DISTINCT NULLIF(proposal.`title`, '') ORDER BY proposal.`position` ASC, proposal.`id` ASC SEPARATOR ' | '), '') AS `proposal_titles`,
						COALESCE(GROUP_CONCAT(DISTINCT NULLIF(proposal.`description`, '') ORDER BY proposal.`position` ASC, proposal.`id` ASC SEPARATOR ' | '), '') AS `proposal_descriptions`
					FROM `decision_process` dp
					LEFT JOIN `holon` h
						ON h.`id` = dp.`IDholon`
					LEFT JOIN `decision_group` dg
						ON dg.`IDdecision_process` = dp.`id`
					   AND dg.`active` = 1
					LEFT JOIN `decision_proposal` proposal
						ON proposal.`IDdecision_process` = dp.`id`
					   AND proposal.`active` = 1
					WHERE dp.`IDorganization` = :organization_id
					GROUP BY
						dp.`id`,
						dp.`title`,
						dp.`description`,
						dp.`IDholon`,
						dp.`IDuser`,
						dp.`status`,
						dp.`evaluation_method`,
						dp.`created_at`,
						dp.`updated_at`,
						h.`name`
				) search_rows
				WHERE " . $preFilterSql . "
				HAVING relevance > 0
				ORDER BY relevance DESC, search_rows.`updated_at` DESC, search_rows.`created_at` DESC, search_rows.`id` DESC
				LIMIT " . $limitSql,
				$params
			);

			if ($rows === false) {
				return array();
			}

			$results = array();

			foreach ($rows as $row) {
				$decision = new \dbObject\DecisionProcess();
				if (!$decision->load((int)($row['id'] ?? 0))) {
					continue;
				}

				$decision->syncLifecycleStatus();
				$decisionAccess = self::topbarSearchResolveDecisionAccess($decision, $viewerContext, (int)$this->getId());
				if ($decisionAccess === false) {
					continue;
				}

				$bestGroupTitle = '';
				$bestGroupDescription = '';
				$bestProposalTitle = '';
				$bestProposalDescription = '';
				$bestGroupScore = 0;

				foreach ($decision->getDecisionGroups(false) as $group) {
					if (!($group instanceof \dbObject\DecisionGroup) || (int)$group->get('active') !== 1) {
						continue;
					}

					$groupTitle = trim((string)$group->get('title'));
					$groupDescription = trim((string)$group->get('description'));
					$groupScore = self::getTopbarSearchTextScore($groupTitle, $terms, array(
						'exact' => 92,
						'prefix' => 58,
						'like' => 30,
					)) + self::getTopbarSearchTextScore($groupDescription, $terms, array(
						'exact' => 42,
						'prefix' => 28,
						'like' => 16,
					));

					$matchedProposalTitle = '';
					$matchedProposalDescription = '';
					foreach ($group->getProposals(true) as $proposal) {
						if (!($proposal instanceof \dbObject\DecisionProposal)) {
							continue;
						}

						$proposalTitle = trim((string)$proposal->get('title'));
						$proposalDescription = trim((string)$proposal->get('description'));
						$proposalScore = self::getTopbarSearchTextScore($proposalTitle, $terms, array(
							'exact' => 76,
							'prefix' => 48,
							'like' => 26,
						)) + self::getTopbarSearchTextScore($proposalDescription, $terms, array(
							'exact' => 28,
							'prefix' => 18,
							'like' => 10,
						));

						if ($proposalScore <= 0) {
							continue;
						}

						$groupScore += $proposalScore;
						if ($matchedProposalTitle === '') {
							$matchedProposalTitle = $proposalTitle;
							$matchedProposalDescription = $proposalDescription;
						}
					}

					if ($groupScore <= $bestGroupScore) {
						continue;
					}

					$bestGroupScore = $groupScore;
					$bestGroupTitle = $groupTitle;
					$bestGroupDescription = $groupDescription;
					$bestProposalTitle = $matchedProposalTitle;
					$bestProposalDescription = $matchedProposalDescription;
				}

				$processTitle = trim((string)($row['process_title'] ?? ''));
				$processDescription = trim((string)($row['process_description'] ?? ''));
				$holonName = trim((string)($row['holon_name'] ?? ''));
				$resultTitle = $processTitle !== '' ? $processTitle : ($bestGroupTitle !== '' ? $bestGroupTitle : ('Decision #' . (int)$decision->getId()));

				$subtitleParts = array();
				if ($holonName !== '') {
					$subtitleParts[] = $holonName;
				}
				if ($bestGroupTitle !== '' && $bestGroupTitle !== $resultTitle) {
					$subtitleParts[] = $bestGroupTitle;
				}
				$subtitle = implode(' | ', $subtitleParts);

				$snippetCandidates = array(
					$processDescription,
					$bestGroupDescription,
					$bestProposalDescription,
					$bestProposalTitle,
					$bestGroupTitle,
					$processTitle,
				);
				$snippetSource = '';
				$snippetScore = -1;
				foreach ($snippetCandidates as $candidate) {
					$candidate = trim((string)$candidate);
					if ($candidate === '') {
						continue;
					}

					$candidateScore = self::getTopbarSearchTextScore($candidate, $terms);
					if ($candidateScore <= $snippetScore) {
						continue;
					}

					$snippetScore = $candidateScore;
					$snippetSource = $candidate;
				}

				if ($snippetSource === '') {
					$snippetSource = trim((string)($row['group_descriptions'] ?? ''));
				}
				if ($snippetSource === '') {
					$snippetSource = trim((string)($row['proposal_descriptions'] ?? ''));
				}
				if ($snippetSource === '') {
					$snippetSource = trim((string)($row['group_titles'] ?? ''));
				}
				if ($snippetSource === '') {
					$snippetSource = trim((string)($row['proposal_titles'] ?? ''));
				}

				$results[] = array(
					'module' => 'decision',
					'moduleLabel' => 'Decisions',
					'title' => $resultTitle,
					'subtitle' => $subtitle,
					'excerpt' => self::buildTopbarSearchSnippet($snippetSource, $query, 100, 220),
					'relevance' => (int)($row['relevance'] ?? 0),
					'_searchDate' => (string)($row['created_at'] ?? ''),
					'action' => array(
						'type' => 'decision',
						'decisionId' => (int)$decision->getId(),
						'holonId' => (int)$decision->get('IDholon'),
					),
				);
			}

			return $results;
		}

		protected function searchTopbarProjectResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			if ((int)$this->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$params = array(
				'organization_id' => (int)$this->getId(),
				'project_kind' => \dbObject\Project::KIND_STANDARD,
			);
			$titleExpr = "LOWER(COALESCE(p.title, ''))";
			$descriptionExpr = "LOWER(COALESCE(p.description, ''))";
			$titleScoreSql = self::buildTopbarSearchScoreSql($titleExpr, $terms, $params, 'project_title', array(
				'exact' => 108,
				'prefix' => 68,
				'like' => 36,
			));
			$descriptionScoreSql = self::buildTopbarSearchScoreSql($descriptionExpr, $terms, $params, 'project_description', array(
				'exact' => 34,
				'prefix' => 22,
				'like' => 12,
			));
			$preFilterSql = self::buildTopbarSearchAnyMatchSql(array($titleExpr, $descriptionExpr), $terms, $params, 'project_prefilter');
			$rows = self::fetchAll(
				"SELECT
					p.id,
					p.title,
					p.description,
					p.status,
					p.priority,
					p.IDholon,
					p.created_at,
					(" . $titleScoreSql . " + " . $descriptionScoreSql . ") AS relevance
				FROM project p
				WHERE p.IDorganization = :organization_id
				  AND p.active = 1
				  AND p.project_kind = :project_kind
				  AND " . $preFilterSql . "
				HAVING relevance > 0
				ORDER BY relevance DESC, p.created_at DESC, p.id DESC
				LIMIT " . max(1, (int)$limit),
				$params
			);
			if ($rows === false) {
				return array();
			}

			$results = array();
			$statusCatalog = \dbObject\Project::getStatusCatalog();
			foreach ($rows as $row) {
				$holonId = (int)($row['IDholon'] ?? 0);
				$subtitleParts = array();
				if ($holonId > 0) {
					$holon = new \dbObject\Holon();
					if (!$holon->load($holonId) || !self::topbarSearchViewerCanViewHolon($holon, $viewerContext)) {
						continue;
					}
					$subtitleParts[] = trim((string)$holon->getDisplayName());
				}
				$status = \dbObject\Project::normalizeStatus($row['status'] ?? '');
				if (!empty($statusCatalog[$status]['label'])) {
					$subtitleParts[] = (string)$statusCatalog[$status]['label'];
				}
				if ((int)($row['priority'] ?? 0) > 0) {
					$subtitleParts[] = 'P' . (int)$row['priority'];
				}

				$title = trim((string)($row['title'] ?? ''));
				$description = self::cleanTopbarSearchTextValue((string)($row['description'] ?? ''));
				$excerptSource = self::getTopbarSearchTextScore($description, $terms) > 0
					? $description
					: $title;
				$results[] = array(
					'module' => 'projects',
					'moduleLabel' => 'Projets',
					'title' => $title !== '' ? $title : ('Projet #' . (int)($row['id'] ?? 0)),
					'subtitle' => implode(' | ', array_filter($subtitleParts)),
					'excerpt' => self::buildTopbarSearchSnippet($excerptSource, $query, 100, 220),
					'relevance' => (int)($row['relevance'] ?? 0),
					'_searchDate' => (string)($row['created_at'] ?? ''),
					'action' => array(
						'type' => 'project',
						'projectId' => (int)($row['id'] ?? 0),
						'holonId' => $holonId,
					),
				);
			}

			return $results;
		}

		protected function searchTopbarStatIndicatorResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			if ((int)$this->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$params = array('organization_id' => (int)$this->getId());
			$nameExpr = "LOWER(COALESCE(si.name, ''))";
			$descriptionExpr = "LOWER(COALESCE(si.description, ''))";
			$sourceExpr = "LOWER(COALESCE(si.source_url, ''))";
			$nameScoreSql = self::buildTopbarSearchScoreSql($nameExpr, $terms, $params, 'stats_name', array('exact' => 108, 'prefix' => 68, 'like' => 36));
			$descriptionScoreSql = self::buildTopbarSearchScoreSql($descriptionExpr, $terms, $params, 'stats_description', array('exact' => 34, 'prefix' => 22, 'like' => 12));
			$sourceScoreSql = self::buildTopbarSearchScoreSql($sourceExpr, $terms, $params, 'stats_source', array('exact' => 20, 'prefix' => 12, 'like' => 6));
			$preFilterSql = self::buildTopbarSearchAnyMatchSql(array($nameExpr, $descriptionExpr, $sourceExpr), $terms, $params, 'stats_prefilter');
			$rows = self::fetchAll(
				"SELECT
					si.id,
					si.name,
					si.description,
					si.source_url,
					si.measurement_frequency,
					si.IDholon,
					si.created_at,
					(" . $nameScoreSql . " + " . $descriptionScoreSql . " + " . $sourceScoreSql . ") AS relevance
				FROM stat_indicator si
				WHERE si.IDorganization = :organization_id
				  AND si.active = 1
				  AND " . $preFilterSql . "
				HAVING relevance > 0
				ORDER BY relevance DESC, si.created_at DESC, si.id DESC
				LIMIT " . max(1, (int)$limit),
				$params
			);
			if ($rows === false) {
				return array();
			}

			$results = array();
			foreach ($rows as $row) {
				$holonId = (int)($row['IDholon'] ?? 0);
				$subtitleParts = array();
				if ($holonId > 0) {
					$holon = new \dbObject\Holon();
					if (!$holon->load($holonId) || !self::topbarSearchViewerCanViewHolon($holon, $viewerContext)) {
						continue;
					}
					$subtitleParts[] = trim((string)$holon->getDisplayName());
				}
				$frequency = trim((string)($row['measurement_frequency'] ?? ''));

				$name = trim((string)($row['name'] ?? ''));
				$description = self::cleanTopbarSearchTextValue((string)($row['description'] ?? ''));
				$sourceUrl = trim((string)($row['source_url'] ?? ''));
				$excerptSource = self::getTopbarSearchTextScore($description, $terms) > 0
					? $description
					: (self::getTopbarSearchTextScore($sourceUrl, $terms) > 0 ? $sourceUrl : $name);
				$results[] = array(
					'module' => 'stats',
					'moduleLabel' => 'Indicateurs',
					'title' => $name !== '' ? $name : ('Indicateur #' . (int)($row['id'] ?? 0)),
					'subtitle' => implode(' | ', array_filter($subtitleParts)),
					'excerpt' => self::buildTopbarSearchSnippet($excerptSource, $query, 100, 220),
					'relevance' => (int)($row['relevance'] ?? 0),
					'_searchDate' => (string)($row['created_at'] ?? ''),
					'action' => array(
						'type' => 'stat_indicator',
						'indicatorId' => (int)($row['id'] ?? 0),
						'holonId' => $holonId,
						'measurementFrequency' => $frequency,
					),
				);
			}

			return $results;
		}

		protected function searchTopbarCalendarResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			if ((int)$this->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$params = array(
				'organization_id' => (int)$this->getId(),
				'cancelled_status' => \dbObject\Event::STATUS_CANCELLED,
			);

			$titleExpr = "LOWER(COALESCE(search_rows.title, ''))";
			$descriptionExpr = "LOWER(COALESCE(search_rows.description, ''))";
			$holonExpr = "LOWER(COALESCE(search_rows.holon_name, ''))";
			$statusExpr = "LOWER(COALESCE(search_rows.status, ''))";

			$titleScoreSql = self::buildTopbarSearchScoreSql($titleExpr, $terms, $params, 'calendar_title', array(
				'exact' => 108,
				'prefix' => 68,
				'like' => 36,
			));
			$descriptionScoreSql = self::buildTopbarSearchScoreSql($descriptionExpr, $terms, $params, 'calendar_description', array(
				'exact' => 34,
				'prefix' => 22,
				'like' => 12,
			));
			$holonScoreSql = self::buildTopbarSearchScoreSql($holonExpr, $terms, $params, 'calendar_holon', array(
				'exact' => 46,
				'prefix' => 30,
				'like' => 16,
			));
			$statusScoreSql = self::buildTopbarSearchScoreSql($statusExpr, $terms, $params, 'calendar_status', array(
				'exact' => 16,
				'prefix' => 10,
				'like' => 6,
			));
			$preFilterSql = self::buildTopbarSearchAnyMatchSql(
				array($titleExpr, $descriptionExpr, $holonExpr, $statusExpr),
				$terms,
				$params,
				'calendar_prefilter'
			);
			$limitSql = max(1, (int)$limit);

			$rows = self::fetchAll(
				"SELECT
					search_rows.*,
					(" . $titleScoreSql . " + " . $descriptionScoreSql . " + " . $holonScoreSql . " + " . $statusScoreSql . ") AS relevance
				FROM (
					SELECT
						e.`id`,
						e.`title`,
						e.`description`,
						e.`IDholon`,
						e.`IDuser`,
						e.`status`,
						e.`start_at`,
						e.`end_at`,
						e.`updated_at`,
						h.`name` AS `holon_name`
					FROM `event` e
					LEFT JOIN `holon` h
						ON h.`id` = e.`IDholon`
					WHERE e.`IDorganization` = :organization_id
					  AND e.`active` = 1
					  AND e.`status` <> :cancelled_status
				) search_rows
				WHERE " . $preFilterSql . "
				HAVING relevance > 0
				ORDER BY relevance DESC, search_rows.`start_at` ASC, search_rows.`updated_at` DESC, search_rows.`id` DESC
				LIMIT " . $limitSql,
				$params
			);

			if ($rows === false) {
				return array();
			}

			$results = array();

			foreach ($rows as $row) {
				$event = new \dbObject\Event();
				if (!$event->load((int)($row['id'] ?? 0))) {
					continue;
				}

				if (!self::topbarSearchViewerCanViewEvent($event, $viewerContext, (int)$this->getId())) {
					continue;
				}

				$eventTitle = trim((string)$event->get('title'));
				$holonName = trim((string)($row['holon_name'] ?? ''));
				$subtitleParts = array();
				if ($holonName !== '') {
					$subtitleParts[] = $holonName;
				} else {
					$subtitleParts[] = trim((string)$this->get('name'));
				}

				$startAt = $event->get('start_at');
				$endAt = $event->get('end_at');
				if ($startAt instanceof \DateTimeInterface && $endAt instanceof \DateTimeInterface) {
					if ((int)$event->get('is_all_day') === 1) {
						$subtitleParts[] = $startAt->format('d.m.Y');
					} elseif ($startAt->format('Y-m-d') === $endAt->format('Y-m-d')) {
						$subtitleParts[] = $startAt->format('d.m.Y H:i') . ' - ' . $endAt->format('H:i');
					} else {
						$subtitleParts[] = $startAt->format('d.m.Y H:i') . ' -> ' . $endAt->format('d.m.Y H:i');
					}
				}

				$snippetSource = trim((string)$event->get('description'));
				if ($snippetSource === '') {
					$snippetSource = $holonName;
				}
				if ($snippetSource === '') {
					$snippetSource = trim((string)$event->get('status'));
				}

				$results[] = array(
					'module' => 'calendar',
					'moduleLabel' => 'Calendrier',
					'title' => $eventTitle !== '' ? $eventTitle : ('Evenement #' . (int)$event->getId()),
					'subtitle' => implode(' | ', array_values(array_filter($subtitleParts, function ($part) {
						return trim((string)$part) !== '';
					}))),
					'excerpt' => self::buildTopbarSearchSnippet($snippetSource, $query, 100, 220),
					'relevance' => (int)($row['relevance'] ?? 0),
					'_searchDate' => (string)($row['start_at'] ?? ''),
					'action' => array(
						'type' => 'calendar_event',
						'eventId' => (int)$event->getId(),
						'holonId' => (int)$event->get('IDholon'),
					),
				);
			}

			return $results;
		}

		public function searchTopbarResults($query, array $scopes = array(), array $options = array())
		{
			$query = trim((string)$query);
			$requestedUserId = isset($options['viewerContext']['userId']) && is_numeric($options['viewerContext']['userId'])
				? (int)$options['viewerContext']['userId']
				: null;
			$scopeAppHashes = array(
				'structure' => 'structure',
				'team' => 'team',
				'calendar' => 'calendar',
				'documents' => 'documents',
				'pv' => 'documents',
				'decision' => 'decision',
				'projects' => 'projects',
				'stats' => 'stats',
			);
			$enabledScopes = array();
			foreach ($scopeAppHashes as $scopeId => $hash) {
				if ($this->isApplicationEnabled($hash, $requestedUserId)) {
					$enabledScopes[$scopeId] = $scopeId;
				}
			}
			$enabledScopes['faq'] = 'faq';
			$enabledScopes['tutorials'] = 'tutorials';
			$normalizedScopes = array();

			foreach ($scopes as $scope) {
				$scope = trim((string)$scope);
				if ($scope === '__structure__') {
					$scope = 'structure';
				}

				if (isset($enabledScopes[$scope])) {
					$normalizedScopes[$scope] = $scope;
				}
			}

			if (count($normalizedScopes) === 0) {
				$normalizedScopes = $enabledScopes;
			}

			$viewerContext = self::normalizeTopbarSearchViewerContext(array(
				'viewerContext' => isset($options['viewerContext']) && is_array($options['viewerContext'])
					? $options['viewerContext']
					: array(),
				'organizationId' => (int)$this->getId(),
			));

			$terms = self::buildTopbarSearchTerms($query);
			$limit = isset($options['limit']) ? max(1, (int)$options['limit']) : 30;
			$perScopeLimit = isset($options['perScopeLimit']) ? max(1, (int)$options['perScopeLimit']) : 12;
			$expandedPerScopeLimit = $perScopeLimit * 8;
			$dateRange = self::normalizeTopbarSearchDateRange(is_array($options['dateRange'] ?? null) ? $options['dateRange'] : array());
			$canSearchPeople = array_key_exists('canSearchPeople', $options)
				? (bool)$options['canSearchPeople']
				: self::topbarSearchViewerCanSearchPeople($viewerContext, (int)$this->getId());

			$counts = array(
				'structure' => 0,
				'team' => 0,
				'calendar' => 0,
				'documents' => 0,
				'pv' => 0,
				'decision' => 0,
				'faq' => 0,
				'tutorials' => 0,
				'projects' => 0,
				'stats' => 0,
			);
			$results = array();

			if (
				$query !== ''
				&& count($terms) > 0
				&& self::topbarSearchViewerHasOrganizationAccess($viewerContext, (int)$this->getId())
			) {
				if (isset($normalizedScopes['structure'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarStructureResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['structure'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if ($canSearchPeople && isset($normalizedScopes['team'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarTeamResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['team'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['calendar'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarCalendarResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['calendar'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['documents'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarDocumentResults($query, $terms, $expandedPerScopeLimit, $viewerContext, 'non_pv'), $dateRange, $perScopeLimit);
					$counts['documents'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['pv'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarDocumentResults($query, $terms, $expandedPerScopeLimit, $viewerContext, \dbObject\Document::TYPE_PV), $dateRange, $perScopeLimit);
					$counts['pv'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['decision'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarDecisionResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['decision'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['faq'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarFaqResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['faq'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['tutorials'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarTutorialResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['tutorials'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['projects'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarProjectResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['projects'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['stats'])) {
					$scopeResults = self::filterTopbarSearchResultsByDateRange($this->searchTopbarStatIndicatorResults($query, $terms, $expandedPerScopeLimit, $viewerContext), $dateRange, $perScopeLimit);
					$counts['stats'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}
			}

			$moduleOrder = array(
				'structure' => 1,
				'team' => 2,
				'calendar' => 3,
				'decision' => 4,
				'documents' => 5,
				'pv' => 6,
				'faq' => 7,
				'tutorials' => 8,
				'projects' => 9,
				'stats' => 10,
			);

			usort($results, function ($left, $right) use ($moduleOrder) {
				$leftScore = (int)($left['relevance'] ?? 0);
				$rightScore = (int)($right['relevance'] ?? 0);
				if ($leftScore !== $rightScore) {
					return $rightScore <=> $leftScore;
				}

				$leftModuleOrder = $moduleOrder[(string)($left['module'] ?? '')] ?? 99;
				$rightModuleOrder = $moduleOrder[(string)($right['module'] ?? '')] ?? 99;
				if ($leftModuleOrder !== $rightModuleOrder) {
					return $leftModuleOrder <=> $rightModuleOrder;
				}

				return strcmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
			});

			if (count($results) > $limit) {
				$results = array_slice($results, 0, $limit);
			}

			return array(
				'query' => $query,
				'scopes' => array_values($normalizedScopes),
				'counts' => $counts,
				'total' => count($results),
				'results' => $results,
			);
		}
		
	}
	
?>
