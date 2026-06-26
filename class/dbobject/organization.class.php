<?php
	namespace dbObject;

	require_once dirname(__DIR__, 2) . '/common/environment_subdomains.php';


	class Organization extends DbObject
	{
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

		public function getNextcloudDocumentsConfig(): array
		{
			$parameters = $this->getParametersArray();
			$config = isset($parameters['nextcloudDocuments']) && is_array($parameters['nextcloudDocuments'])
				? $parameters['nextcloudDocuments']
				: array();

			$baseUrl = trim((string)($config['baseUrl'] ?? ''));
			$username = trim((string)($config['username'] ?? ''));
			$appPassword = trim((string)($config['appPassword'] ?? ''));
			$folder = trim((string)($config['folder'] ?? ''));
			$folder = trim(str_replace('\\', '/', $folder), '/');

			return array(
				'baseUrl' => rtrim($baseUrl, '/'),
				'username' => $username,
				'appPassword' => $appPassword,
				'folder' => $folder,
			);
		}

		public function hasNextcloudDocumentStorage(): bool
		{
			$config = $this->getNextcloudDocumentsConfig();
			return $config['baseUrl'] !== ''
				&& $config['username'] !== ''
				&& $config['appPassword'] !== '';
		}

		public function updateNextcloudDocumentsConfig(array $values, bool $preserveExistingPassword = true): void
		{
			$parameters = $this->getParametersArray();
			$currentConfig = $this->getNextcloudDocumentsConfig();
			$clearConfig = !empty($values['nextcloud_clear_config']);

			if ($clearConfig) {
				unset($parameters['nextcloudDocuments']);
				$this->setParametersArray($parameters);
				return;
			}

			$baseUrl = trim((string)($values['nextcloud_base_url'] ?? ''));
			$username = trim((string)($values['nextcloud_username'] ?? ''));
			$appPassword = trim((string)($values['nextcloud_app_password'] ?? ''));
			$folder = trim((string)($values['nextcloud_folder'] ?? ''));
			$folder = trim(str_replace('\\', '/', $folder), '/');

			if ($preserveExistingPassword && $appPassword === '' && $currentConfig['appPassword'] !== '') {
				$appPassword = $currentConfig['appPassword'];
			}

			if ($baseUrl === '' && $username === '' && $appPassword === '' && $folder === '') {
				unset($parameters['nextcloudDocuments']);
				$this->setParametersArray($parameters);
				return;
			}

			$parameters['nextcloudDocuments'] = array(
				'baseUrl' => rtrim($baseUrl, '/'),
				'username' => $username,
				'appPassword' => $appPassword,
				'folder' => $folder,
			);
			$this->setParametersArray($parameters);
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
			curl_close($curl);

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
				'text' => ($httpCode >= 200 && $httpCode < 300) ? 'OK' : ('Requete Nextcloud refusee (' . $httpCode . ').'),
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
			return $this->canEdit();
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
			$placeholders = self::buildIntPlaceholders($rootIds, 'root_holon', $params);
			$rows = self::fetchAll(
				"SELECT id
				FROM holon
				WHERE id IN (" . implode(', ', $placeholders) . ")
				   OR IDholon_org IN (" . implode(', ', $placeholders) . ")
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

				if (!$this->deactivateUserHolonLinks($userId, $this->getOrganizationHolonIds())) {
					throw new \RuntimeException("Le retrait des roles et cercles n'a pas pu etre finalise.");
				}

				$membership->set('active', false);
				$saveResult = $membership->setOrganizationAdmin(false);
				if (!is_array($saveResult) || empty($saveResult['status'])) {
					throw new \RuntimeException("Le retrait de l'organisation n'a pas pu etre enregistre.");
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
				$pendingInvitation = \dbObject\Invitation::findPendingForOrganizationUser($organizationId, (int)$user->getId());
				$hasActiveMembership = $this->hasActiveMembershipForUser($user, $organizationId);
				$requiresInvitation = !$hasActiveMembership && !($pendingInvitation instanceof \dbObject\Invitation);

				if ($pendingInvitation instanceof \dbObject\Invitation) {
					$approvalResult = $pendingInvitation->approveByAdmin([
						'approvedByUserId' => (int)$this->resolveCurrentUserId(),
						'sendConfirmationEmail' => false,
					]);
					if (!($approvalResult['status'] ?? false)) {
						throw new \RuntimeException((string)($approvalResult['message'] ?? "L'ajout en attente n'a pas pu etre finalise."));
					}
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
					'message' => $requiresInvitation
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
					'pending' => $requiresInvitation,
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
			if (!$this->canDelete()) {
				return false;
			}

			$organizationId = (int)$this->getId();
			if ($organizationId <= 0) {
				return false;
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
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

				return false;
			}
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

		protected function cloneStructuralPropertyValue(\dbObject\Property $property, $rawValue, array $holonIdMap)
		{
			return $this->remapHolonReferenceValueByListType($property->get('listitemtype'), $rawValue, $holonIdMap);
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

		protected function cloneStructuralHolonProperties(\dbObject\Holon $sourceHolon, \dbObject\Holon $targetHolon, $sourceRootHolonId, $targetRootHolonId, array $holonIdMap, array &$propertyIdMap)
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
				$targetHolonProperty->set('value', $this->cloneStructuralPropertyValue($sourceProperty, $sourceHolonProperty->get('value'), $holonIdMap));
				$targetHolonProperty->set('position', (int)$sourceHolonProperty->get('position'));
				$targetHolonProperty->set('mandatory', (bool)$sourceHolonProperty->get('mandatory'));
				$targetHolonProperty->set('locked', (bool)$sourceHolonProperty->get('locked'));
				$targetHolonProperty->set('active', (bool)$sourceHolonProperty->get('active'));
				$targetHolonProperty->save();
			}
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

				$property = new \dbObject\Property();
				$property->set('name', trim((string)($definition['name'] ?? '')) !== '' ? $definition['name'] : 'Propriete');
				$property->set('shortname', trim((string)($definition['shortname'] ?? '')) !== '' ? $definition['shortname'] : \dbObject\Property::buildShortnameFromName((string)($definition['name'] ?? 'Propriete')));
				$property->set('IDpropertyformat', (int)($definition['formatId'] ?? 0));
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
			if ($name === '') {
				$name = 'Holon';
			}

			if (!$preserveName) {
				$targetHolon->set('name', $name);
			}

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

		protected function importCompactHolonPropertyRows(array $record, \dbObject\Holon $targetHolon, array $propertyIdMap, array $holonIdMap)
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
					? $this->cloneStructuralPropertyValue($targetProperty, $row['value'], $holonIdMap)
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

		protected function importCompactHolonPermissionRows(array $record, \dbObject\Holon $targetHolon)
		{
			$targetHolonId = (int)$targetHolon->getId();
			if ($targetHolonId <= 0) {
				return false;
			}

			$rows = $record['permissions'] ?? array();
			$assignmentsByPermissionKey = array();

			if (is_array($rows)) {
				foreach ($rows as $row) {
					if (!is_array($row)) {
						continue;
					}

					$permissionKey = trim((string)($row['permissionKey'] ?? ''));
					$range = trim((string)($row['range'] ?? ''));
					if ($permissionKey === '' || $range === '') {
						continue;
					}

					if (!isset($assignmentsByPermissionKey[$permissionKey])) {
						$assignmentsByPermissionKey[$permissionKey] = array();
					}

					$assignmentsByPermissionKey[$permissionKey][] = $range;
				}
			}

			return \dbObject\HolonPermission::syncAssignmentsForHolon($targetHolonId, $assignmentsByPermissionKey);
		}

		protected function importStructureFromCompactGraph(array $payload, $userId = 0)
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

				$pending = $recordsBySourceId;
				unset($pending[$sourceRootId]);

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

					$templateSourceId = (int)($record['templateId'] ?? 0);
					$targetHolon = $targetHolonsBySourceId[$sourceId];
					$targetHolon->set(
						'IDholon_template',
						($templateSourceId > 0 && isset($holonIdMap[$templateSourceId]))
							? (int)$holonIdMap[$templateSourceId]
							: null
					);
					$targetHolon->save();
				}

				foreach ($recordsBySourceId as $sourceId => $record) {
					if (!isset($targetHolonsBySourceId[$sourceId])) {
						continue;
					}

					$this->importCompactHolonPropertyRows(
						$record,
						$targetHolonsBySourceId[$sourceId],
						$propertyIdMap,
						$holonIdMap
					);

					if (!$this->importCompactHolonPermissionRows($record, $targetHolonsBySourceId[$sourceId])) {
						throw new \RuntimeException("Les droits d'un holon importe n'ont pas pu etre recrees.");
					}
				}

				$pdo->commit();

				return array(
					'status' => true,
					'message' => "L'organisation a ete importee depuis le format compact.",
					'rootHolon' => $targetRootHolon,
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

		public function importStructure(array $payload, $userId = 0)
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

			return $this->importStructureFromCompactGraph($payload, $userId);
		}

		protected function cloneStructuralChildrenRecursively(\dbObject\Holon $sourceParent, $targetParentId, $targetRootHolonId, $userId, array &$sourceHolonsById, array &$targetHolonsBySourceId, array &$holonIdMap)
		{
			foreach ($this->getStructuralInitializationChildren($sourceParent) as $sourceChild) {
				$targetChild = new \dbObject\Holon();
				$targetChild->set('name', $sourceChild->get('name'));
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
					$propertyIdMap
				);

				if (!$this->cloneStructuralHolonPermissions($sourceHolon, $targetHolon)) {
					return array(
						'status' => false,
						'message' => "Les droits du modele n'ont pas pu etre dupliques.",
					);
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
			return $scope === 'global' ? 'global' : 'contextual';
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
				'templates' => array(),
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

			$templateCatalogSource = $this->getAvailableTemplateDefinitionHolons($contextHolon ? (int)$contextHolon->getId() : 0);
			$templateTreeSource = $scope === 'global'
				? $this->getAllTemplateDefinitionHolons()
				: $this->getTemplateDefinitionHolons($contextHolon ? (int)$contextHolon->getId() : 0);
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
					'inheritsFromId' => (int)$template->get('IDholon_template'),
					'definedInId' => (int)$definitionHolonMeta['id'],
					'definedInName' => (string)$definitionHolonMeta['name'],
					'definedInLabel' => (string)$definitionHolonMeta['label'],
					'properties' => $template->getTemplatePropertyDefinitions(),
				), $this->getHolonIllustrationData($template));
			}

			foreach ($templateTreeSource as $template) {
				$templateNode = $template->toTemplateEditorNodeArray((int)$rootHolon->getId());
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
			$node['properties'] = $holon->getTemplatePropertyDefinitions();
			$node['children'] = array();
			$node['shareAsTemplate'] = trim((string)$holon->get('templatename')) !== '';
			$node['publicTemplateName'] = trim((string)$holon->get('templatename'));

			return $node;
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

		protected function buildDocumentDestinationCatalog(\dbObject\Holon $candidate, array &$catalog, array $path = array())
		{
			if (!(bool)$candidate->get('active') || !(bool)$candidate->get('visible')) {
				return;
			}

			$typeId = (int)$candidate->get('IDtypeholon');
			$currentPath = $path;
			$displayName = trim((string)$candidate->getDisplayName());

			if ($typeId !== 4 && $displayName !== '') {
				$currentPath[] = $displayName;

				if (in_array($typeId, array(1, 2, 3), true)) {
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
				$this->buildDocumentDestinationCatalog($child, $catalog, $currentPath);
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
			$data['canMove'] = $document->canEditInOrganizationContext((int)$this->getId());

			if (!$data['canMove']) {
				return $data;
			}

			$data['destinations'][] = array(
				'key' => 'organization',
				'holonId' => 0,
				'name' => $organizationLabel !== '' ? $organizationLabel : 'Organisation',
				'typeId' => 0,
				'typeLabel' => 'Organisation',
				'pathLabel' => $organizationLabel !== '' ? $organizationLabel : 'Organisation',
				'isCurrentDestination' => $currentHolonId <= 0,
			);

			if ($rootHolon) {
				$this->buildDocumentDestinationCatalog(
					$rootHolon,
					$data['destinations'],
					$organizationLabel !== '' ? array($organizationLabel) : array('Organisation')
				);
			}

			$folderDocuments = new \dbObject\ArrayDocument();
			$folderDocuments->loadVisibleForOrganizationContext((int)$this->getId(), 0, 'global');
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
				if (
					(int)$child->getId() !== $excludedHolonId
					&& $childTemplateId > 0
				) {
					if ($childTemplateId === $templateId) {
						return true;
					}

					$instanceTemplate = new \dbObject\Holon();
					if (
						$instanceTemplate->load($childTemplateId)
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
				'templateCatalog' => array(),
				'holonCatalog' => array(),
				'holon' => null,
			);

			if (!$rootHolon || !$contextHolon) {
				return $data;
			}

			$data['canCreate'] = !$isTemplateEditing && $contextHolon->canEdit() && in_array((int)$contextHolon->get('IDtypeholon'), array(2, 3, 4), true);
			$data['canEdit'] = $editingHolon && $editingHolon->canEdit() && in_array((int)$editingHolon->get('IDtypeholon'), array(1, 2, 3), true);

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
					'definedInId' => (int)$template->get('IDholon_parent'),
					'definedInName' => $definitionHolonName,
					'definedInLabel' => $definitionHolonLabel,
					'properties' => $isTemplateEditing
						? $template->getTemplatePropertyDefinitions()
						: $template->getHolonCreationPropertyDefinitions(),
				), $this->getHolonIllustrationData($template));

				$typeLabelsById[$typeId] = $template->getTypeLabel();
			}

			ksort($typeLabelsById);
			foreach ($typeLabelsById as $typeId => $typeLabel) {
				$data['types'][] = array(
					'id' => (int)$typeId,
					'name' => (string)$typeLabel,
				);
			}

			$this->buildSelectableHolonCatalog($rootHolon, $data['holonCatalog'], (int)$rootHolon->getId());

			if ($editingHolon && $data['canEdit']) {
				$data['holon'] = array_merge(array(
					'id' => (int)$editingHolon->getId(),
					'name' => $editingHolon->getDisplayName(),
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
					'properties' => $isTemplateEditing
						? $editingHolon->getTemplatePropertyDefinitions()
						: $editingHolon->getHolonEditorPropertyDefinitions(),
				), $this->getHolonIllustrationData($editingHolon));
			}

			return $data;
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

		protected function buildHolonHistoryPermissionSnapshot(\dbObject\Holon $holon)
		{
			$holonId = (int)$holon->getId();
			if ($holonId <= 0) {
				return array();
			}

			$rangeLabels = \dbObject\HolonPermission::getRangeLabels();
			$assignments = \dbObject\HolonPermission::getAssignmentKeyMapForHolon($holonId);
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
					'visibleItems' => $formatId === \dbObject\PropertyFormat::FORMAT_LIST
					? $this->parseHolonHistoryListValue($visibleValue)
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

			if (is_array($item)) {
				return $this->limitHolonHistoryText((string)($item['label'] ?? $item['value'] ?? ''));
			}

			return $this->limitHolonHistoryText((string)$item);
		}

		protected function buildHolonHistoryValuePreview(array $propertySnapshot, array $beforePropertySnapshot = array())
		{
			$formatId = (int)($propertySnapshot['formatId'] ?? 0);
			$value = (string)($propertySnapshot['visibleValue'] ?? '');
			$beforeValue = (string)($beforePropertySnapshot['visibleValue'] ?? '');

			if ($formatId === \dbObject\PropertyFormat::FORMAT_LIST) {
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
				$value = html_entity_decode(
					strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), ' ', $value)),
					ENT_QUOTES | ENT_HTML5,
					'UTF-8'
				);
				$beforeValue = html_entity_decode(
					strip_tags(str_ireplace(array('<br>', '<br/>', '<br />'), ' ', $beforeValue)),
					ENT_QUOTES | ENT_HTML5,
					'UTF-8'
				);
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
				'unique' => 'unique',
				'link' => 'lien',
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

				if ($formatId === \dbObject\PropertyFormat::FORMAT_LIST) {
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
					$historyBeforeSnapshot = $this->buildHolonHistorySnapshot($holon);
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

					if ($formatId === \dbObject\PropertyFormat::FORMAT_LIST) {
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

			if ($name === '') {
				return array(
					'status' => false,
					'message' => 'Le nom du holon est obligatoire.',
				);
			}

			if (!$holon) {
				$holon = new \dbObject\Holon();
			}

			$holon->set('name', $name);
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

			if ($isTemplateEditing) {
				$holon->syncTemplateProperties($templateDefinitions, (int)$rootHolon->getId());
			} else {
				$holon->syncEditorPropertyValues($submittedValuesByPropertyId, $templateDefinitions);

				if (!$isEditing && (int)$holon->get('IDtypeholon') === 2) {
					$excludedTemplateIds = array();
					if ($templateId > 0) {
						$excludedTemplateIds[] = $templateId;
					}

						$this->createMandatoryChildrenForCircle($holon, (int)$rootHolon->getId(), $userId, $excludedTemplateIds);
				}

				$holon->load((int)$holon->getId(), true);
				$historyAfterSnapshot = $this->buildHolonHistorySnapshot($holon);
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

			if (!$contextHolon->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas les droits pour modifier les modeles de ce holon.",
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

			if ($template->getId() > 0 && (int)$template->get('IDholon_parent') !== (int)$contextHolon->getId()) {
				return array(
					'status' => false,
					'message' => "Ce modele n'est pas defini dans le holon courant.",
				);
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
				is_array($payload['properties'] ?? null) ? $payload['properties'] : array(),
				(int)$rootHolon->getId()
			);

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
				'data' => $this->getHolonTemplateEditorData((int)$contextHolon->getId(), $scope),
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

			$holon->syncTemplateProperties($definitions, (int)$rootHolon->getId());

			if (!\dbObject\HolonPermission::syncAssignmentsForHolon(
				(int)$holon->getId(),
				is_array($payload['permissions'] ?? null) ? $payload['permissions'] : array()
			)) {
				return array(
					'status' => false,
					'message' => "Les droits de l'organisation n'ont pas pu etre enregistres.",
				);
			}

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
						'userId' => $currentUserId,
					);
				}

				return array(
					'type' => 'public',
					'organizationId' => (int)($viewerContext['organizationId'] ?? ($options['organizationId'] ?? ($_SESSION['currentOrganization'] ?? 0))),
				);
			}

			$normalized = array(
				'type' => $type,
				'organizationId' => (int)($viewerContext['organizationId'] ?? ($options['organizationId'] ?? 0)),
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
					'action' => array(
						'type' => 'user',
						'userId' => (int)($row['id'] ?? 0),
					),
				);
			}

			return $results;
		}

		protected function searchTopbarDocumentResults($query, array $terms, $limit = 12, array $viewerContext = array())
		{
			if ((int)$this->getId() <= 0 || count($terms) === 0) {
				return array();
			}

			$params = array(
				'organization_id' => (int)$this->getId(),
			);

			$titleExpr = "LOWER(COALESCE(d.title, ''))";
			$descriptionExpr = "LOWER(COALESCE(d.description, ''))";
			$keywordsExpr = "LOWER(COALESCE(d.keywords, ''))";
			$contentExpr = "LOWER(COALESCE(d.content, ''))";

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
			$keywordsScoreSql = self::buildTopbarSearchScoreSql($keywordsExpr, $terms, $params, 'document_keywords', array(
				'exact' => 40,
				'prefix' => 26,
				'like' => 15,
			));
			$contentScoreSql = self::buildTopbarSearchScoreSql($contentExpr, $terms, $params, 'document_content', array(
				'exact' => 18,
				'prefix' => 12,
				'like' => 6,
			));
			$preFilterSql = self::buildTopbarSearchAnyMatchSql(
				array($titleExpr, $descriptionExpr, $keywordsExpr, $contentExpr),
				$terms,
				$params,
				'document_prefilter'
			);
			$limitSql = max(1, (int)$limit);

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
					(" . $titleScoreSql . " + " . $descriptionScoreSql . " + " . $keywordsScoreSql . " + " . $contentScoreSql . ") AS relevance
				FROM document d
				WHERE d.IDorganization = :organization_id
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
				$snippetSource = trim((string)($row['description'] ?? '')) !== ''
					? (string)($row['description'] ?? '')
					: ((trim((string)($row['keywords'] ?? '')) !== '' ? (string)($row['keywords'] ?? '') : (string)($row['content'] ?? '')));
				$detailUrl = '/omo/api/documents/detail.php?id=' . (int)$document->getId() . '&oid=' . (int)$this->getId();
				if ((int)$document->get('IDholon') > 0) {
					$detailUrl .= '&cid=' . (int)$document->get('IDholon');
				}

				$results[] = array(
					'module' => 'documents',
					'moduleLabel' => 'Documents',
					'title' => trim((string)$document->get('title')) !== '' ? (string)$document->get('title') : ('Document #' . (int)$document->getId()),
					'subtitle' => $subtitle,
					'excerpt' => self::buildTopbarSearchSnippet($snippetSource, $query, 100, 220),
					'relevance' => (int)($row['relevance'] ?? 0),
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
					'action' => array(
						'type' => 'decision',
						'decisionId' => (int)$decision->getId(),
						'holonId' => (int)$decision->get('IDholon'),
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
				'decision' => 'decision',
			);
			$enabledScopes = array();
			foreach ($scopeAppHashes as $scopeId => $hash) {
				if ($this->isApplicationEnabled($hash, $requestedUserId)) {
					$enabledScopes[$scopeId] = $scopeId;
				}
			}
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
			$canSearchPeople = array_key_exists('canSearchPeople', $options)
				? (bool)$options['canSearchPeople']
				: self::topbarSearchViewerCanSearchPeople($viewerContext, (int)$this->getId());

			$counts = array(
				'structure' => 0,
				'team' => 0,
				'calendar' => 0,
				'documents' => 0,
				'decision' => 0,
			);
			$results = array();

			if (
				$query !== ''
				&& count($terms) > 0
				&& self::topbarSearchViewerHasOrganizationAccess($viewerContext, (int)$this->getId())
			) {
				if (isset($normalizedScopes['structure'])) {
					$scopeResults = $this->searchTopbarStructureResults($query, $terms, $perScopeLimit, $viewerContext);
					$counts['structure'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if ($canSearchPeople && isset($normalizedScopes['team'])) {
					$scopeResults = $this->searchTopbarTeamResults($query, $terms, $perScopeLimit, $viewerContext);
					$counts['team'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['calendar'])) {
					$scopeResults = $this->searchTopbarCalendarResults($query, $terms, $perScopeLimit, $viewerContext);
					$counts['calendar'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['documents'])) {
					$scopeResults = $this->searchTopbarDocumentResults($query, $terms, $perScopeLimit, $viewerContext);
					$counts['documents'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}

				if (isset($normalizedScopes['decision'])) {
					$scopeResults = $this->searchTopbarDecisionResults($query, $terms, $perScopeLimit, $viewerContext);
					$counts['decision'] = count($scopeResults);
					$results = array_merge($results, $scopeResults);
				}
			}

			$moduleOrder = array(
				'structure' => 1,
				'team' => 2,
				'calendar' => 3,
				'decision' => 4,
				'documents' => 5,
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
