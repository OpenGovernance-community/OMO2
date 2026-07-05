<?php
	namespace dbObject;

	class document extends DbObject
	{
		public const TYPE_HTML = 'html';
		public const TYPE_EXTERNAL_LINK = 'external_link';
		public const TYPE_UPLOADED_FILE = 'uploaded_file';
		public const TYPE_FOLDER = 'folder';

	    public static function tableName()
		{
			return 'document'; // Nom de la table correspondante
		}

		// Defini le contenu de la table
		public static function rules()
		{
			return [
				[['title'], 'required'],						// Champs obligatoires
				[['id', 'version', 'estDossier', 'openinnewwindow', 'storedfilesize'], 'integer'],				// Nombres entiers
				[['title', 'codeview', 'codeedit', 'keywords', 'documenttype', 'externalurl', 'storedfilepath', 'storedfilename', 'storedfilemime'], 'string'],	// Chaines de caractere
				[['description', 'content', 'contentedition'], 'text'],			// Textes libres
				[['datecreation', 'datemodification', 'dateedition', 'datecontentedition'], 'datetime'],	// Date avec precision des heures
				[['IDuser', 'IDusercreation', 'IDusermodification', 'IDuseredition', 'IDorganization', 'IDholon', 'IDdocument_parent'], 'fk'],	// Cles etrangeres
				[['id'], 'safe'],								// Champs proteges
			];
		}

		// Defini les labels standards pour cet objet, affiches dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'description' => 'Resume',
				'content' => 'Contenu',
				'contentedition' => 'Contenu temporaire en edition',
				'keywords' => 'Mots cles',
				'IDuser' => 'Auteur',
				'IDusercreation' => 'Createur',
				'IDusermodification' => 'Utilisateur de modification',
				'IDuseredition' => 'Utilisateur en cours d edition',
				'IDorganization' => 'Organisation',
				'IDholon' => 'Holon',
				'estDossier' => 'Dossier',
				'IDdocument_parent' => 'Dossier parent',
				'datecreation' => 'Date de creation',
				'datemodification' => 'Date de modification',
				'dateedition' => 'Date d edition en cours',
				'datecontentedition' => 'Date du brouillon en edition',
				'version' => 'Version',
				'codeview' => 'Code d affichage',
				'codeedit' => 'Code d edition',
				'documenttype' => 'Type de document',
				'externalurl' => 'URL externe',
				'openinnewwindow' => 'Ouvrir dans une nouvelle fenetre',
				'storedfilepath' => 'Chemin distant du fichier',
				'storedfilename' => 'Nom original du fichier',
				'storedfilemime' => 'Type MIME du fichier',
				'storedfilesize' => 'Taille du fichier',
			];
		}

		// Ajoute un champ description, qui peut apparaitre sous forme de bulle d information ou en sous-titre
		public static function attributeDescriptions()
		{
			return [
				'title' => 'Titre affiche dans une liste de fichiers',
				'description' => 'Abstract du contenu du document',
				'content' => 'Formate en texte libre ou en HTML',
				'contentedition' => 'Brouillon HTML temporaire pendant l edition',
				'IDuser' => 'Createur du document',
				'IDusercreation' => 'Utilisateur qui a cree le document',
				'IDusermodification' => 'Utilisateur qui a realise la derniere modification',
				'IDuseredition' => 'Utilisateur qui edite actuellement le document',
				'IDorganization' => 'Organisation a laquelle le document est rattache',
				'IDholon' => 'Holon concerne si le document est specifique a un contexte local',
				'estDossier' => 'Permet de traiter cette entree comme un dossier',
				'IDdocument_parent' => 'Dossier qui contient ce document',
				'dateedition' => 'Date du dernier signal de presence pendant l edition',
				'datecontentedition' => 'Date de mise a jour du brouillon temporaire',
				'documenttype' => 'Permet de distinguer les documents HTML, les liens externes et les dossiers',
				'externalurl' => 'Adresse du site a ouvrir pour un document de type lien externe',
				'openinnewwindow' => 'Ouvre le lien externe directement dans une autre fenetre',
				'storedfilepath' => 'Chemin du fichier sur le stockage Nextcloud de l organisation',
				'storedfilename' => 'Nom du fichier televerse par l utilisateur',
				'storedfilemime' => 'Type MIME detecte pour le fichier distant',
				'storedfilesize' => 'Taille du fichier distant en octets',
			];
		}

		// Defini les informations de taille pour le champ
		public static function attributeLength()
		{
			return [
				'title' => 100,									// Nombre de caracteres maximum
				'externalurl' => 2000,
				'storedfilepath' => 1000,
				'storedfilename' => 255,
				'storedfilemime' => 255,
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder()
		{
			return "datecreation";
		}

		public static function getVisibilityObjectType(): string
		{
			return \dbObject\ObjectVisibility::OBJECT_TYPE_DOCUMENT;
		}

		// Retourne l'ensemble des medias attaches a un document
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

		// Retourne l'ensemble des alternatives textuelles attachees a un document
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
			$organizationId = (int)$this->get('IDorganization');
			if ($organizationId > 0) {
				return $this->currentViewerCanAccessVisibility($organizationId);
			}

			return (isset($_SESSION["currentUser"]) && (int)$_SESSION["currentUser"] === (int)$this->get("IDuser"));
		}

		public function canEdit()
		{
			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
			$organizationId = (int)$this->get('IDorganization');

			return $currentUserId > 0
				&& $currentUserId === (int)$this->get('IDuser')
				&& (
					$organizationId <= 0
					|| !function_exists('commonUserHasOrganizationAccess')
					|| \commonUserHasOrganizationAccess($currentUserId, $organizationId)
				);
		}

		public function canEditInOrganizationContext(int $organizationId, ?int $userId = null, bool $useSessionCache = true): bool
		{
			$documentOrganizationId = (int)$this->get('IDorganization');
			$organizationId = (int)$organizationId;
			$userId = $userId !== null
				? (int)$userId
				: (
					function_exists('commonGetCurrentUserId')
						? (int)\commonGetCurrentUserId()
						: (int)($_SESSION['currentUser'] ?? 0)
				);

			if ($userId <= 0 || $userId !== (int)$this->get('IDuser')) {
				return false;
			}

			if ($documentOrganizationId <= 0) {
				return $organizationId <= 0;
			}

			if (
				function_exists('commonUserHasOrganizationAccess')
				&& !\commonUserHasOrganizationAccess($userId, $documentOrganizationId)
			) {
				return false;
			}

			return self::canCreateInOrganizationContext(
				$documentOrganizationId,
				(int)$this->get('IDholon') > 0 ? (int)$this->get('IDholon') : null,
				$userId,
				(int)$this->get('IDdocument_parent'),
				$useSessionCache
			);
		}

		public function canViewInMemoContext(int $userId = 0, ?string $accessCode = null): bool
		{
			$userId = (int)$userId;
			$accessCode = trim((string)$accessCode);

			if ($userId > 0 && $userId === (int)$this->get('IDuser')) {
				return true;
			}

			$codeView = trim((string)$this->get('codeview'));
			if ($accessCode !== '' && $codeView !== '' && hash_equals($codeView, $accessCode)) {
				return true;
			}

			return false;
		}

		public function isFolder(): bool
		{
			return (bool)$this->get('estDossier');
		}

		public static function normalizeDocumentType($rawType, bool $isFolder = false): string
		{
			if ($isFolder) {
				return self::TYPE_FOLDER;
			}

			$documentType = trim(mb_strtolower((string)$rawType, 'UTF-8'));
			if ($documentType === self::TYPE_EXTERNAL_LINK) {
				return self::TYPE_EXTERNAL_LINK;
			}

			if ($documentType === self::TYPE_UPLOADED_FILE) {
				return self::TYPE_UPLOADED_FILE;
			}

			return self::TYPE_HTML;
		}

		public function getDocumentType(): string
		{
			return self::normalizeDocumentType($this->get('documenttype'), $this->isFolder());
		}

		public function isExternalLink(): bool
		{
			return $this->getDocumentType() === self::TYPE_EXTERNAL_LINK;
		}

		public function supportsHtmlContent(): bool
		{
			return $this->getDocumentType() === self::TYPE_HTML;
		}

		public function isUploadedFile(): bool
		{
			return $this->getDocumentType() === self::TYPE_UPLOADED_FILE;
		}

		public function canBeEmbedded(): bool
		{
			return !$this->isFolder() && $this->supportsHtmlContent();
		}

		public static function sanitizeExternalUrl($url): string
		{
			$url = trim((string)$url);
			if ($url === '') {
				return '';
			}

			if (!preg_match('/^https?:\/\//i', $url)) {
				return '';
			}

			return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
		}

		public function getExternalUrl(): string
		{
			return self::sanitizeExternalUrl($this->get('externalurl'));
		}

		public function shouldOpenExternalLinkInNewWindow(): bool
		{
			return $this->isExternalLink() && (bool)$this->get('openinnewwindow');
		}

		public function hasStoredFile(): bool
		{
			return $this->isUploadedFile() && trim((string)$this->get('storedfilepath')) !== '';
		}

		public function getStoredFileDownloadName(): string
		{
			$filename = trim((string)$this->get('storedfilename'));
			if ($filename !== '') {
				return $filename;
			}

			$title = trim((string)$this->get('title'));
			return $title !== '' ? $title : ('document-' . (int)$this->getId());
		}

		public function getStoredFileMimeType(): string
		{
			$mimeType = trim((string)$this->get('storedfilemime'));
			return $mimeType !== '' ? $mimeType : 'application/octet-stream';
		}

		public function getStoredFileSize(): int
		{
			return max(0, (int)$this->get('storedfilesize'));
		}

		protected function clearStoredFileState(): void
		{
			$this->set('storedfilepath', null);
			$this->set('storedfilename', null);
			$this->set('storedfilemime', null);
			$this->set('storedfilesize', null);
		}

		protected static function resolveUserDisplayNameById(int $userId): string
		{
			static $cache = array();

			$userId = (int)$userId;
			if ($userId <= 0) {
				return '';
			}

			if (array_key_exists($userId, $cache)) {
				return $cache[$userId];
			}

			$user = new \dbObject\User();
			if (!$user->load($userId)) {
				$cache[$userId] = '';
				return '';
			}

			$displayName = trim((string)$user->get('username'));
			if ($displayName === '') {
				$displayName = trim((string)$user->get('firstname') . ' ' . (string)$user->get('lastname'));
			}

			$cache[$userId] = $displayName;
			return $cache[$userId];
		}

		public function getCreatedByUserId(): int
		{
			$userId = (int)$this->get('IDusercreation');
			if ($userId > 0) {
				return $userId;
			}

			return (int)$this->get('IDuser');
		}

		public function getUpdatedByUserId(): int
		{
			$userId = (int)$this->get('IDusermodification');
			if ($userId > 0) {
				return $userId;
			}

			return $this->getCreatedByUserId();
		}

		public function getCreatedByDisplayName(): string
		{
			return self::resolveUserDisplayNameById($this->getCreatedByUserId());
		}

		public function getUpdatedByDisplayName(): string
		{
			return self::resolveUserDisplayNameById($this->getUpdatedByUserId());
		}

		public static function getEditLockTimeoutSeconds(): int
		{
			return 300;
		}

		public static function getDraftHeartbeatIntervalSeconds(): int
		{
			return 15;
		}

		public function getEditingUserId(): int
		{
			return (int)$this->get('IDuseredition');
		}

		public function getEditingUserDisplayName(): string
		{
			return self::resolveUserDisplayNameById($this->getEditingUserId());
		}

		public function hasRecentDraft(?\DateTimeInterface $referenceDate = null): bool
		{
			$draftDate = $this->get('datecontentedition');
			if (!($draftDate instanceof \DateTimeInterface)) {
				return false;
			}

			$referenceTimestamp = $referenceDate instanceof \DateTimeInterface
				? (int)$referenceDate->getTimestamp()
				: time();

			return ((int)$draftDate->getTimestamp() + self::getEditLockTimeoutSeconds()) >= $referenceTimestamp;
		}

		public function getEffectiveEditingContentForUser(int $userId): string
		{
			if (!$this->supportsHtmlContent()) {
				return '';
			}

			$userId = (int)$userId;
			if (
				$userId > 0
				&& $this->getEditingUserId() === $userId
				&& $this->hasRecentDraft()
			) {
				return (string)$this->get('contentedition');
			}

			return (string)$this->get('content');
		}

		protected function clearDraftContentState(): void
		{
			$this->set('contentedition', null);
			$this->set('datecontentedition', null);
		}

		public function getLiveContentSnapshot(bool $allowDraft = false): array
		{
			$editingUserId = $this->getEditingUserId();
			$editingUserName = $this->getEditingUserDisplayName();
			$draftIsActive = $allowDraft
				&& $this->supportsHtmlContent()
				&& $editingUserId > 0
				&& $this->hasRecentDraft()
				&& trim((string)$this->get('contentedition')) !== '';

			$content = !$this->supportsHtmlContent()
				? ''
				: ($draftIsActive
					? (string)$this->get('contentedition')
					: (string)$this->get('content'));
			$dateValue = $draftIsActive
				? $this->get('datecontentedition')
				: $this->get('datemodification');

			return array(
				'content' => $content,
				'date' => $dateValue instanceof \DateTimeInterface ? $dateValue : null,
				'isDraft' => $draftIsActive,
				'editingUserId' => $editingUserId,
				'editingUserName' => $editingUserName,
			);
		}

		protected static function getDocumentEmbedAttributeValue(\DOMElement $element, string $attributeName): string
		{
			return $element->hasAttribute($attributeName)
				? (string)$element->getAttribute($attributeName)
				: '';
		}

		protected static function isDocumentEmbedElement(\DOMElement $element): bool
		{
			if (trim((string)$element->getAttribute('data-omo-embed-type')) !== 'document') {
				return false;
			}

			return (int)trim((string)$element->getAttribute('data-omo-document-id')) > 0;
		}

		protected static function buildDocumentEmbedDisplayHtml(
			int $documentId,
			string $title,
			string $description = '',
			string $bodyHtml = '',
			string $variant = 'reference',
			string $message = ''
		): string {
			$variantClass = preg_replace('/[^a-z0-9_-]+/i', '-', trim((string)$variant));
			if ($variantClass === '') {
				$variantClass = 'reference';
			}

			$html = '<div class="omo-document-embed omo-document-embed--' . htmlspecialchars($variantClass, ENT_QUOTES, 'UTF-8') . '"'
				. ' data-omo-embed-type="document"'
				. ' data-omo-document-id="' . (int)$documentId . '">';

			$html .= '<div class="omo-document-embed__label">Document lie</div>';

			if (trim($title) !== '') {
				$html .= '<div class="omo-document-embed__title">' . htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8') . '</div>';
			}

			if (trim($description) !== '') {
				$html .= '<div class="omo-document-embed__description">' . htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8') . '</div>';
			}

			if (trim($message) !== '') {
				$html .= '<div class="omo-document-embed__message">' . htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8') . '</div>';
			}

			if (trim($bodyHtml) !== '') {
				$html .= '<div class="omo-document-embed__body">' . $bodyHtml . '</div>';
			}

			$html .= '</div>';

			return $html;
		}

		protected function renderExternalLinkForViewer(): string
		{
			$externalUrl = $this->getExternalUrl();
			if ($externalUrl === '') {
				return '<div class="omo-document-external omo-document-external--invalid">Lien externe invalide.</div>';
			}

			$escapedUrl = htmlspecialchars($externalUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$linkHtml = '<a class="generic-action-button generic-action-button--secondary omo-document-external__link" href="'
				. $escapedUrl
				. '" target="_blank" rel="noopener noreferrer">Ouvrir le site</a>';

			if ($this->shouldOpenExternalLinkInNewWindow()) {
				return '<div class="omo-document-external omo-document-external--window">'
					. '<p>Ce document ouvre un site externe dans une nouvelle fenetre.</p>'
					. $linkHtml
					. '</div>';
			}

			return '<div class="omo-document-external omo-document-external--iframe">'
				. '<div class="omo-document-external__toolbar">'
				. '<span class="omo-document-external__hint">Site externe affiche dans OMO.</span>'
				. $linkHtml
				. '</div>'
				. '<iframe class="omo-document-external__frame" src="' . $escapedUrl . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>'
				. '<p class="omo-document-external__fallback">Si le site refuse l affichage en iframe, utilisez le bouton pour l ouvrir directement.</p>'
				. '</div>';
		}

		protected function renderUploadedFileForViewer(): string
		{
			if (!$this->hasStoredFile()) {
				return '<div class="omo-document-file omo-document-file--empty">Aucun fichier n est actuellement televerse pour ce document.</div>';
			}

			$downloadUrl = '/omo/api/documents/download.php?id=' . (int)$this->getId();
			$fileSize = $this->getStoredFileSize();
			$fileMeta = array();
			if ($this->getStoredFileMimeType() !== '') {
				$fileMeta[] = htmlspecialchars($this->getStoredFileMimeType(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}
			if ($fileSize > 0) {
				$fileMeta[] = number_format($fileSize, 0, '.', '\'') . ' octets';
			}

			return '<div class="omo-document-file">'
				. '<div class="omo-document-file__title">' . htmlspecialchars($this->getStoredFileDownloadName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>'
				. (count($fileMeta) > 0
					? '<div class="omo-document-file__meta">' . implode(' · ', $fileMeta) . '</div>'
					: '')
				. '<a class="generic-action-button generic-action-button--main omo-document-file__download" href="'
				. htmlspecialchars($downloadUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
				. '">Telecharger le fichier</a>'
				. '</div>';
		}

		protected function renderResolvedHtmlNode(\DOMNode $node, int $organizationId, array $options = array()): string
		{
			if ($node->nodeType === XML_TEXT_NODE) {
				return htmlspecialchars((string)($node->nodeValue ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			}

			if ($node->nodeType !== XML_ELEMENT_NODE || !($node instanceof \DOMElement)) {
				return '';
			}

			if (self::isDocumentEmbedElement($node)) {
				return $this->renderEmbeddedDocumentReference($node, $organizationId, $options);
			}

			$tagName = strtolower((string)$node->tagName);
			if ($tagName === '') {
				return '';
			}

			if ($tagName === 'br') {
				return '<br>';
			}

			$attributes = '';
			if ($tagName === 'a') {
				$href = trim((string)$node->getAttribute('href'));
				if ($href !== '') {
					$attributes .= ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';
				}

				$target = trim((string)$node->getAttribute('target'));
				if ($target !== '') {
					$attributes .= ' target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
				}

				$rel = trim((string)$node->getAttribute('rel'));
				if ($rel !== '') {
					$attributes .= ' rel="' . htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') . '"';
				}
			}

			$childrenHtml = '';
			foreach ($node->childNodes as $childNode) {
				$childrenHtml .= $this->renderResolvedHtmlNode($childNode, $organizationId, $options);
			}

			return '<' . $tagName . $attributes . '>' . $childrenHtml . '</' . $tagName . '>';
		}

		protected function renderEmbeddedDocumentReference(\DOMElement $element, int $organizationId, array $options = array()): string
		{
			$targetDocumentId = (int)trim((string)$element->getAttribute('data-omo-document-id'));
			$fallbackTitle = trim((string)self::getDocumentEmbedAttributeValue($element, 'data-omo-document-title'));
			$fallbackDescription = trim((string)self::getDocumentEmbedAttributeValue($element, 'data-omo-document-description'));

			if ($targetDocumentId <= 0) {
				return self::buildDocumentEmbedDisplayHtml(0, $fallbackTitle, $fallbackDescription, '', 'unresolved', 'Reference invalide.');
			}

			$visitedIds = isset($options['visitedIds']) && is_array($options['visitedIds'])
				? $options['visitedIds']
				: array();
			$currentDocumentId = (int)$this->getId();
			if ($currentDocumentId > 0) {
				$visitedIds[$currentDocumentId] = true;
			}

			if (!empty($visitedIds[$targetDocumentId])) {
				return self::buildDocumentEmbedDisplayHtml($targetDocumentId, $fallbackTitle, $fallbackDescription, '', 'cycle', 'Reference circulaire detectee.');
			}

			$remainingDepth = isset($options['maxDepth']) ? max(0, (int)$options['maxDepth']) : 4;
			if ($remainingDepth <= 0) {
				return self::buildDocumentEmbedDisplayHtml($targetDocumentId, $fallbackTitle, $fallbackDescription, '', 'depth-limit', 'Profondeur maximale atteinte.');
			}

			$targetDocument = new \dbObject\Document();
			if (!$targetDocument->load($targetDocumentId)) {
				return self::buildDocumentEmbedDisplayHtml($targetDocumentId, $fallbackTitle, $fallbackDescription, '', 'missing', 'Document introuvable.');
			}

			if (!$targetDocument->canBeEmbedded()) {
				return self::buildDocumentEmbedDisplayHtml($targetDocumentId, $fallbackTitle, $fallbackDescription, '', 'unsupported', 'Ce type de document ne peut pas etre integre ici.');
			}

			$targetOrganizationId = (int)$targetDocument->get('IDorganization');
			$targetHolonId = (int)$targetDocument->get('IDholon');
			$canViewTarget = $targetOrganizationId > 0
				&& $targetDocument->canViewInOrganizationContext(
					$targetOrganizationId,
					$targetHolonId > 0 ? $targetHolonId : null
				);

			$targetTitle = trim((string)$targetDocument->get('title'));
			$targetDescription = trim((string)$targetDocument->get('description'));
			if (!$canViewTarget) {
				return self::buildDocumentEmbedDisplayHtml(
					$targetDocumentId,
					$fallbackTitle,
					$fallbackDescription,
					'',
					'forbidden',
					'Document non accessible dans ce contexte.'
				);
			}

			$childOptions = $options;
			$childOptions['visitedIds'] = $visitedIds;
			$childOptions['visitedIds'][$targetDocumentId] = true;
			$childOptions['maxDepth'] = $remainingDepth - 1;

			$bodyHtml = $targetDocument->renderResolvedHtmlForViewer(
				(string)$targetDocument->get('content'),
				$targetOrganizationId,
				$childOptions
			);

			return self::buildDocumentEmbedDisplayHtml(
				$targetDocumentId,
				$targetTitle !== '' ? $targetTitle : $fallbackTitle,
				$targetDescription !== '' ? $targetDescription : $fallbackDescription,
				$bodyHtml,
				'resolved'
			);
		}

		public function renderResolvedHtmlForViewer(string $contentHtml, int $organizationId, array $options = array()): string
		{
			$contentHtml = trim($contentHtml);
			$organizationId = (int)$organizationId;
			if ($contentHtml === '' || $organizationId <= 0) {
				return $contentHtml;
			}

			if (!class_exists('\DOMDocument')) {
				return $contentHtml;
			}

			$document = new \DOMDocument('1.0', 'UTF-8');
			$previousState = libxml_use_internal_errors(true);
			$document->loadHTML(
				'<?xml encoding="utf-8" ?><div>' . $contentHtml . '</div>',
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
			);
			libxml_clear_errors();
			libxml_use_internal_errors($previousState);

			$root = $document->documentElement;
			if (!$root) {
				return $contentHtml;
			}

			$html = '';
			foreach ($root->childNodes as $childNode) {
				$html .= $this->renderResolvedHtmlNode($childNode, $organizationId, $options);
			}

			return $html;
		}

		public function getRenderedContentForCurrentViewer(): string
		{
			if ($this->isExternalLink()) {
				return $this->renderExternalLinkForViewer();
			}

			if ($this->isUploadedFile()) {
				return $this->renderUploadedFileForViewer();
			}

			return $this->renderResolvedHtmlForViewer(
				(string)$this->get('content'),
				(int)$this->get('IDorganization')
			);
		}

		protected static function buildLiveShareStateHash(array $payload): string
		{
			$hashSource = array(
				'title' => trim((string)($payload['title'] ?? '')),
				'description' => trim((string)($payload['description'] ?? '')),
				'updatedAt' => trim((string)($payload['updatedAt'] ?? '')),
				'isDraft' => !empty($payload['isDraft']) ? 1 : 0,
				'editingUserName' => trim((string)($payload['editingUserName'] ?? '')),
				'contentHash' => trim((string)($payload['contentHash'] ?? '')),
			);

			$encoded = json_encode($hashSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			return sha1($encoded === false ? serialize($hashSource) : $encoded);
		}

		public function buildLiveSharePayload(bool $allowDraft = false): array
		{
			$snapshot = $this->getLiveContentSnapshot($allowDraft);
			$dateValue = $snapshot['date'] ?? null;
			$content = (string)($snapshot['content'] ?? '');
			$updatedAt = $dateValue instanceof \DateTimeInterface
				? $dateValue->format(\DateTimeInterface::ATOM)
				: null;

			$payload = array(
				'title' => trim((string)$this->get('title')),
				'description' => trim((string)$this->get('description')),
				'content' => $this->isExternalLink()
					? $this->renderExternalLinkForViewer()
					: ($this->isUploadedFile()
						? $this->renderUploadedFileForViewer()
						: $this->renderResolvedHtmlForViewer($content, (int)$this->get('IDorganization'))),
				'isDraft' => !empty($snapshot['isDraft']),
				'editingUserName' => trim((string)($snapshot['editingUserName'] ?? '')),
				'updatedAt' => $updatedAt,
			);

			$payload['contentHash'] = sha1($content);
			$payload['stateHash'] = self::buildLiveShareStateHash($payload);

			return $payload;
		}

		public function isEditLockActive(?\DateTimeInterface $referenceDate = null): bool
		{
			$editingUserId = $this->getEditingUserId();
			$editingDate = $this->get('dateedition');
			if ($editingUserId <= 0 || !($editingDate instanceof \DateTimeInterface)) {
				return false;
			}

			$referenceTimestamp = $referenceDate instanceof \DateTimeInterface
				? (int)$referenceDate->getTimestamp()
				: time();

			return ((int)$editingDate->getTimestamp() + self::getEditLockTimeoutSeconds()) >= $referenceTimestamp;
		}

		protected function buildEditLockConflictResult(int $currentUserId = 0): array
		{
			$editingUserId = $this->getEditingUserId();
			$editingDate = $this->get('dateedition');
			$editingUserName = $this->getEditingUserDisplayName();
			$isOwnedByCurrentUser = $currentUserId > 0 && $editingUserId === $currentUserId;
			$message = $isOwnedByCurrentUser
				? 'Ce document est deja en cours d edition dans votre session.'
				: 'Ce document est deja en cours d edition.';

			if (!$isOwnedByCurrentUser && $editingUserName !== '') {
				$message .= ' Utilisateur: ' . $editingUserName . '.';
			}

			$message .= ' Reessayez dans quelques minutes.';

			return array(
				'status' => false,
				'text' => $message,
				'lock' => array(
					'userId' => $editingUserId,
					'userName' => $editingUserName,
					'date' => $editingDate instanceof \DateTimeInterface ? $editingDate : null,
					'isOwnedByCurrentUser' => $isOwnedByCurrentUser,
					'timeoutSeconds' => self::getEditLockTimeoutSeconds(),
				),
			);
		}

		public function touchEditLock(int $organizationId, int $userId, $draftContent = null)
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;

			if (
				(int)$this->getId() <= 0
				|| (int)$this->get('IDorganization') !== $organizationId
				|| $userId <= 0
				|| !$this->canEditInOrganizationContext($organizationId, $userId, false)
			) {
				return array(
					'status' => false,
					'text' => 'Acces refuse.',
				);
			}

			$now = new \DateTimeImmutable();
			if ($this->isEditLockActive($now) && $this->getEditingUserId() !== $userId) {
				return $this->buildEditLockConflictResult($userId);
			}

			$this->set('IDuseredition', $userId);
			$this->set('dateedition', $now);
			if ($draftContent !== null) {
				$sanitizedDraftContent = !$this->supportsHtmlContent()
					? null
					: \dbObject\PropertyFormat::sanitizeHtml((string)$draftContent);
				$currentDraftContent = $this->supportsHtmlContent() ? (string)$this->get('contentedition') : null;

				$this->set('contentedition', $sanitizedDraftContent);
				if (!$this->supportsHtmlContent()) {
					$this->set('datecontentedition', null);
				} elseif ((string)$sanitizedDraftContent !== (string)$currentDraftContent) {
					$this->set('datecontentedition', $now);
				}
			}

			$saveResult = $this->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return array(
					'status' => false,
					'text' => 'Impossible de verrouiller ce document pour l edition.',
				);
			}

			return array(
				'status' => true,
				'text' => 'Verrou d edition actif.',
				'lock' => array(
					'userId' => $userId,
					'userName' => $this->getEditingUserDisplayName(),
					'date' => $now,
					'timeoutSeconds' => self::getEditLockTimeoutSeconds(),
				),
			);
		}

		public function releaseEditLock(int $userId, $clearDraft = false)
		{
			$userId = (int)$userId;
			if ((int)$this->getId() <= 0 || $userId <= 0) {
				return array(
					'status' => false,
					'text' => 'Verrou introuvable.',
				);
			}

			if ($this->getEditingUserId() !== $userId) {
				return array(
					'status' => true,
					'text' => 'Aucun verrou a liberer.',
				);
			}

			$this->set('IDuseredition', null);
			$this->set('dateedition', null);
			if ($clearDraft) {
				$this->clearDraftContentState();
			}

			$saveResult = $this->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return array(
					'status' => false,
					'text' => 'Impossible de liberer le verrou d edition.',
				);
			}

			return array(
				'status' => true,
				'text' => 'Verrou d edition libere.',
			);
		}

		public function getParentDocument()
		{
			$parentDocumentId = (int)$this->get('IDdocument_parent');
			if ($parentDocumentId <= 0) {
				return null;
			}

			$parentDocument = new \dbObject\Document();
			return $parentDocument->load($parentDocumentId) ? $parentDocument : null;
		}

		public function getDirectChildren()
		{
			$children = new \dbObject\ArrayDocument();
			if ((int)$this->getId() <= 0) {
				return $children;
			}

			$children->load(array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => (int)$this->get('IDorganization')),
					array('field' => 'IDdocument_parent', 'value' => (int)$this->getId()),
				),
				'orderBy' => array(
					array('field' => 'datemodification', 'dir' => 'DESC'),
					array('field' => 'id', 'dir' => 'DESC'),
				),
			));

			return $children;
		}

		public function getParentFolderChain(): array
		{
			$chain = array();
			$current = $this->getParentDocument();
			$visitedIds = array();

			while ($current instanceof \dbObject\Document && (int)$current->getId() > 0) {
				$currentId = (int)$current->getId();
				if (isset($visitedIds[$currentId])) {
					break;
				}

				$visitedIds[$currentId] = true;
				array_unshift($chain, $current);
				$current = $current->getParentDocument();
			}

			return $chain;
		}

		public function isDescendantOfDocument($documentId, $includeSelf = false): bool
		{
			$documentId = (int)$documentId;
			if ($documentId <= 0 || (int)$this->getId() <= 0) {
				return false;
			}

			if ($includeSelf && (int)$this->getId() === $documentId) {
				return true;
			}

			$current = $this->getParentDocument();
			$visitedIds = array();

			while ($current instanceof \dbObject\Document && (int)$current->getId() > 0) {
				$currentId = (int)$current->getId();
				if (isset($visitedIds[$currentId])) {
					break;
				}

				if ($currentId === $documentId) {
					return true;
				}

				$visitedIds[$currentId] = true;
				$current = $current->getParentDocument();
			}

			return false;
		}

		protected function resolveParentDocumentForContext(int $organizationId, ?int $parentDocumentId, $excludedDocumentId = 0): array
		{
			$organizationId = (int)$organizationId;
			$parentDocumentId = $parentDocumentId !== null ? (int)$parentDocumentId : 0;
			$excludedDocumentId = (int)$excludedDocumentId;

			if ($parentDocumentId <= 0) {
				return array(
					'status' => true,
					'parentDocument' => null,
					'holonId' => null,
				);
			}

			$parentDocument = new \dbObject\Document();
			if (
				!$parentDocument->load($parentDocumentId)
				|| (int)$parentDocument->get('IDorganization') !== $organizationId
				|| !$parentDocument->isFolder()
			) {
				return array(
					'status' => false,
					'text' => 'Dossier parent introuvable.',
				);
			}

			if (
				$excludedDocumentId > 0
				&& (
					(int)$parentDocument->getId() === $excludedDocumentId
					|| $parentDocument->isDescendantOfDocument($excludedDocumentId, false)
				)
			) {
				return array(
					'status' => false,
					'text' => 'Le dossier cible est invalide.',
				);
			}

			return array(
				'status' => true,
				'parentDocument' => $parentDocument,
				'holonId' => (int)$parentDocument->get('IDholon') > 0
					? (int)$parentDocument->get('IDholon')
					: null,
			);
		}

		protected function getOwnActivityDate()
		{
			$updatedAt = $this->get('datemodification');
			if ($updatedAt instanceof \DateTimeInterface) {
				return $updatedAt;
			}

			$createdAt = $this->get('datecreation');
			return $createdAt instanceof \DateTimeInterface ? $createdAt : null;
		}

		protected function getOwnActivityUserId(): int
		{
			$userId = (int)$this->get('IDusermodification');
			if ($userId > 0) {
				return $userId;
			}

			$userId = (int)$this->get('IDusercreation');
			if ($userId > 0) {
				return $userId;
			}

			return (int)$this->get('IDuser');
		}

		public function getActivityMetadata(array &$visited = array()): array
		{
			$documentId = (int)$this->getId();
			if ($documentId > 0 && isset($visited[$documentId])) {
				return array(
					'date' => $this->getOwnActivityDate(),
					'userId' => $this->getOwnActivityUserId(),
				);
			}

			if ($documentId > 0) {
				$visited[$documentId] = true;
			}

			$latestDate = $this->isFolder()
				? null
				: $this->getOwnActivityDate();
			$latestUserId = $this->getOwnActivityUserId();

			if ($this->isFolder()) {
				foreach ($this->getDirectChildren() as $childDocument) {
					if (!($childDocument instanceof \dbObject\Document)) {
						continue;
					}

					$childActivity = $childDocument->getActivityMetadata($visited);
					$childDate = $childActivity['date'] ?? null;
					if (
						$childDate instanceof \DateTimeInterface
						&& (
							!($latestDate instanceof \DateTimeInterface)
							|| $childDate->getTimestamp() > $latestDate->getTimestamp()
						)
					) {
						$latestDate = $childDate;
						$latestUserId = (int)($childActivity['userId'] ?? 0);
					}
				}

				if (!($latestDate instanceof \DateTimeInterface)) {
					$latestDate = $this->getOwnActivityDate();
					$latestUserId = $this->getOwnActivityUserId();
				}
			}

			return array(
				'date' => $latestDate,
				'userId' => $latestUserId,
			);
		}

		public function getActivityDate(array &$visited = array())
		{
			$activityMetadata = $this->getActivityMetadata($visited);
			return $activityMetadata['date'] ?? null;
		}

		public function refreshFolderActivity(array &$visited = array())
		{
			if (!$this->isFolder() || (int)$this->getId() <= 0) {
				return array(
					'status' => true,
					'date' => $this->getOwnActivityDate(),
				);
			}

			$activityMetadata = $this->getActivityMetadata($visited);
			$activityDate = $activityMetadata['date'] ?? null;
			$activityUserId = (int)($activityMetadata['userId'] ?? 0);
			if (!($activityDate instanceof \DateTimeInterface)) {
				$activityDate = new \DateTimeImmutable();
			}

			$currentDate = $this->get('datemodification');
			$currentComparable = $currentDate instanceof \DateTimeInterface
				? $currentDate->format('Y-m-d H:i:s')
				: '';
			$nextComparable = $activityDate->format('Y-m-d H:i:s');
			$currentUserId = (int)$this->get('IDusermodification');

			if ($currentComparable === $nextComparable && $currentUserId === $activityUserId) {
				return array(
					'status' => true,
					'date' => $activityDate,
				);
			}

			$this->set('datemodification', $activityDate);
			$this->set('IDusermodification', $activityUserId > 0 ? $activityUserId : null);
			$saveResult = $this->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return $saveResult;
			}

			return array(
				'status' => true,
				'date' => $activityDate,
			);
		}

		public function refreshAncestorFolderActivity($parentDocumentId = null, array &$visited = array())
		{
			$nextParentId = $parentDocumentId !== null
				? (int)$parentDocumentId
				: (int)$this->get('IDdocument_parent');
			$visitedParentIds = array();

			while ($nextParentId > 0) {
				if (isset($visitedParentIds[$nextParentId])) {
					break;
				}

				$visitedParentIds[$nextParentId] = true;
				$parentDocument = new \dbObject\Document();
				if (!$parentDocument->load($nextParentId)) {
					break;
				}

				if ($parentDocument->isFolder()) {
					$refreshResult = $parentDocument->refreshFolderActivity($visited);
					if (!is_array($refreshResult) || ($refreshResult['status'] ?? false) !== true) {
						return $refreshResult;
					}
				}

				$nextParentId = (int)$parentDocument->get('IDdocument_parent');
			}

			return array('status' => true);
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
			if (
				!$this->matchesOrganizationContext($organizationId, $holonId)
				|| !$this->currentViewerCanAccessVisibility($organizationId)
			) {
				return false;
			}

			foreach ($this->getParentFolderChain() as $parentFolder) {
				if (
					!($parentFolder instanceof \dbObject\Document)
					|| !$parentFolder->isFolder()
					|| !$parentFolder->currentViewerCanAccessVisibility($organizationId)
				) {
					return false;
				}
			}

			return true;
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

		public static function resolveCreationPermissionHolon(int $organizationId, ?int $requestedHolonId = null, int $parentDocumentId = 0)
		{
			$organizationId = (int)$organizationId;
			$requestedHolonId = $requestedHolonId !== null ? (int)$requestedHolonId : 0;
			$parentDocumentId = (int)$parentDocumentId;

			if ($organizationId <= 0) {
				return null;
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return null;
			}

			$rootHolon = $organization->getEnabledStructuralRootHolon();
			$resolvedHolonId = 0;

			if ($parentDocumentId > 0) {
				$document = new self();
				$resolvedParent = $document->resolveParentDocumentForContext($organizationId, $parentDocumentId);
				if (($resolvedParent['status'] ?? false) !== true) {
					return null;
				}

				$resolvedHolonId = (int)($resolvedParent['holonId'] ?? 0);
			} elseif ($requestedHolonId > 0) {
				$resolvedHolonId = $requestedHolonId;
			}

			if ($resolvedHolonId <= 0) {
				return $rootHolon instanceof \dbObject\Holon && (int)$rootHolon->getId() > 0
					? $rootHolon
					: null;
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($resolvedHolonId)
				|| !(bool)$holon->get('active')
				|| !(bool)$holon->get('visible')
				|| !$organization->containsHolon($holon)
			) {
				return null;
			}

			return $holon;
		}

		public static function canCreateInOrganizationContext(int $organizationId, ?int $requestedHolonId, int $userId, int $parentDocumentId = 0, bool $useSessionCache = true): bool
		{
			$organizationId = (int)$organizationId;
			$requestedHolonId = $requestedHolonId !== null ? (int)$requestedHolonId : 0;
			$userId = (int)$userId;
			$parentDocumentId = (int)$parentDocumentId;

			if ($organizationId <= 0 || $userId <= 0) {
				return false;
			}

			$permissionHolon = self::resolveCreationPermissionHolon($organizationId, $requestedHolonId, $parentDocumentId);
			if ($permissionHolon instanceof \dbObject\Holon && (int)$permissionHolon->getId() > 0) {
				return $permissionHolon->isAllowed('CAN_CREATE_DOCUMENT', $useSessionCache, $userId);
			}

			if ($requestedHolonId > 0 || $parentDocumentId > 0) {
				return false;
			}

			return function_exists('commonUserHasOrganizationAccess')
				? \commonUserHasOrganizationAccess($userId, $organizationId)
				: false;
		}

		protected static function extractValidUploadedFile($uploadedFile): ?array
		{
			if (!is_array($uploadedFile)) {
				return null;
			}

			$errorCode = (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
			$tmpName = trim((string)($uploadedFile['tmp_name'] ?? ''));
			if ($errorCode !== UPLOAD_ERR_OK || $tmpName === '' || !is_file($tmpName)) {
				return null;
			}

			return $uploadedFile;
		}

		protected function deleteStoredFileFromOrganizationStorage(\dbObject\Organization $organization): array
		{
			$storedPath = trim((string)$this->get('storedfilepath'));
			if ($storedPath === '') {
				$this->clearStoredFileState();
				return array('status' => true);
			}

			$deleteResult = $organization->deleteDocumentFileFromNextcloud($storedPath);
			if (!is_array($deleteResult) || empty($deleteResult['status'])) {
				return $deleteResult;
			}

			$this->clearStoredFileState();
			return array('status' => true);
		}

		protected function applyUploadedFileToOrganizationStorage(\dbObject\Organization $organization, array $uploadedFile): array
		{
			$uploadResult = $organization->uploadDocumentFileToNextcloud((int)$this->getId(), $uploadedFile);
			if (!is_array($uploadResult) || empty($uploadResult['status'])) {
				return $uploadResult;
			}

			$this->set('storedfilepath', (string)($uploadResult['relativePath'] ?? ''));
			$this->set('storedfilename', (string)($uploadResult['originalName'] ?? ''));
			$this->set('storedfilemime', (string)($uploadResult['mimeType'] ?? 'application/octet-stream'));
			$this->set('storedfilesize', (int)($uploadResult['size'] ?? 0));

			return array(
				'status' => true,
				'uploadedPath' => (string)($uploadResult['relativePath'] ?? ''),
			);
		}

		public function createInOrganizationContext(int $organizationId, ?int $holonId, int $userId, array $values = array())
		{
			$userId = (int)$userId;
			if ($userId <= 0) {
				return array(
					'status' => false,
					'text' => "Utilisateur invalide.",
				);
			}

			$title = trim((string)($values['title'] ?? ''));
			if ($title === '') {
				return array(
					'status' => false,
					'text' => "Le titre est obligatoire.",
				);
			}

			$description = trim((string)($values['description'] ?? ''));
			$requestedDocumentType = (string)($values['document_type'] ?? '');
			$isFolder = !empty($values['is_folder']) || trim(mb_strtolower($requestedDocumentType, 'UTF-8')) === self::TYPE_FOLDER;
			$documentType = self::normalizeDocumentType($requestedDocumentType, $isFolder);
			$uploadedFile = $documentType === self::TYPE_UPLOADED_FILE
				? self::extractValidUploadedFile($values['uploaded_file'] ?? null)
				: null;
			$parentDocumentId = isset($values['parent_document_id']) ? (int)$values['parent_document_id'] : 0;
			$content = $documentType === self::TYPE_HTML
				? \dbObject\PropertyFormat::sanitizeHtml((string)($values['content'] ?? ''))
				: '';
			$externalUrl = $documentType === self::TYPE_EXTERNAL_LINK
				? self::sanitizeExternalUrl($values['external_url'] ?? '')
				: '';
			$openInNewWindow = $documentType === self::TYPE_EXTERNAL_LINK && !empty($values['open_in_new_window']);
			if ($documentType === self::TYPE_EXTERNAL_LINK && $externalUrl === '') {
				return array(
					'status' => false,
					'text' => "L URL externe est obligatoire.",
				);
			}
			if ($documentType === self::TYPE_UPLOADED_FILE && $uploadedFile === null) {
				return array(
					'status' => false,
					'text' => 'Un fichier est obligatoire pour ce type de document.',
				);
			}
			$keywords = trim((string)($values['keywords'] ?? ''));
			$visibilityType = (string)($values['visibility_type'] ?? \dbObject\ObjectVisibility::getDefaultVisibilityType());
			$now = new \DateTimeImmutable();

			$this->set('title', $title);
			$this->set('description', $description);
			$this->set('content', $content);
			$this->set('contentedition', null);
			$this->set('datecontentedition', null);
			$this->set('keywords', $keywords);
			$this->set('estDossier', $isFolder ? 1 : 0);
			$this->set('documenttype', $documentType);
			$this->set('externalurl', $externalUrl !== '' ? $externalUrl : null);
			$this->set('openinnewwindow', $openInNewWindow ? 1 : 0);
			$this->set('IDuser', $userId);
			$this->set('IDusercreation', $userId);
			$this->set('IDusermodification', $userId);
			$this->set('datecreation', $now);
			$this->set('datemodification', $now);

			if (trim((string)$this->get('codeview')) === '') {
				$this->set('codeview', bin2hex(random_bytes(10)));
			}

			if (trim((string)$this->get('codeedit')) === '') {
				$this->set('codeedit', bin2hex(random_bytes(10)));
			}

			$pdo = self::getPdo();
			$startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();
			$organization = new \dbObject\Organization();

			try {
				if ($startedTransaction) {
					$pdo->beginTransaction();
				}

				if (!$organization->load($organizationId)) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return array(
						'status' => false,
						'text' => 'Organisation introuvable.',
					);
				}

				if ($documentType === self::TYPE_UPLOADED_FILE && !$organization->hasNextcloudDocumentStorage()) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return array(
						'status' => false,
						'text' => 'Le stockage Nextcloud n est pas configure pour cette organisation.',
					);
				}

				$resolvedParent = $this->resolveParentDocumentForContext($organizationId, $parentDocumentId);
				if (($resolvedParent['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $resolvedParent;
				}

				$resolvedParentDocument = $resolvedParent['parentDocument'] ?? null;
				$resolvedHolonId = $resolvedParentDocument instanceof \dbObject\Document
					? (($resolvedParent['holonId'] ?? null) !== null ? (int)$resolvedParent['holonId'] : null)
					: $holonId;

				$this->set('IDdocument_parent', $resolvedParentDocument instanceof \dbObject\Document
					? (int)$resolvedParentDocument->getId()
					: null);

				$contextSaveResult = $this->assignOrganizationContext($organizationId, $resolvedHolonId);
				if (!is_array($contextSaveResult) || ($contextSaveResult['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $contextSaveResult;
				}

				if ($documentType === self::TYPE_UPLOADED_FILE) {
					$fileStorageResult = $this->applyUploadedFileToOrganizationStorage($organization, $uploadedFile);
					if (!is_array($fileStorageResult) || empty($fileStorageResult['status'])) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}

						return $fileStorageResult;
					}

					$fileMetadataSaveResult = $this->save();
					if (!is_array($fileMetadataSaveResult) || ($fileMetadataSaveResult['status'] ?? false) !== true) {
						$uploadedPath = trim((string)($fileStorageResult['uploadedPath'] ?? ''));
						if ($uploadedPath !== '') {
							$organization->deleteDocumentFileFromNextcloud($uploadedPath);
						}
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}

						return $fileMetadataSaveResult;
					}
				}

				$visibilitySaveResult = $this->saveVisibilityRule($visibilityType);
				if (!is_array($visibilitySaveResult) || ($visibilitySaveResult['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $visibilitySaveResult;
				}

				if ($startedTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}

				if ($resolvedParentDocument instanceof \dbObject\Document) {
					$this->refreshAncestorFolderActivity((int)$resolvedParentDocument->getId());
				}

				return $contextSaveResult;
			} catch (\Throwable $exception) {
				if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'text' => 'Impossible de creer ce document.',
				);
			}
		}

		public function updateInOrganizationContext(int $organizationId, int $userId, array $values = array())
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			$isWithoutContext = $organizationId <= 0;

			if ((int)$this->getId() <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return array(
					'status' => false,
					'text' => 'Document introuvable.',
				);
			}

			if ($userId <= 0 || $userId !== (int)$this->get('IDuser') || !$this->canEditInOrganizationContext($organizationId, $userId, false)) {
				return array(
					'status' => false,
					'text' => 'Acces refuse.',
				);
			}

			$lockResult = $this->touchEditLock($organizationId, $userId);
			if (!is_array($lockResult) || ($lockResult['status'] ?? false) !== true) {
				return $lockResult;
			}

			$title = trim((string)($values['title'] ?? ''));
			if ($title === '') {
				return array(
					'status' => false,
					'text' => 'Le titre est obligatoire.',
				);
			}

			$description = trim((string)($values['description'] ?? ''));
			$documentType = $this->getDocumentType();
			$uploadedFile = $documentType === self::TYPE_UPLOADED_FILE
				? self::extractValidUploadedFile($values['uploaded_file'] ?? null)
				: null;
			$removeUploadedFile = $documentType === self::TYPE_UPLOADED_FILE && !empty($values['remove_uploaded_file']);
			$content = $documentType === self::TYPE_HTML
				? \dbObject\PropertyFormat::sanitizeHtml((string)($values['content'] ?? ''))
				: '';
			$externalUrl = $documentType === self::TYPE_EXTERNAL_LINK
				? self::sanitizeExternalUrl($values['external_url'] ?? '')
				: '';
			$openInNewWindow = $documentType === self::TYPE_EXTERNAL_LINK && !empty($values['open_in_new_window']);
			if ($documentType === self::TYPE_EXTERNAL_LINK && $externalUrl === '') {
				return array(
					'status' => false,
					'text' => 'L URL externe est obligatoire.',
				);
			}
			if ($documentType === self::TYPE_UPLOADED_FILE && $uploadedFile === null && $removeUploadedFile && !$this->hasStoredFile()) {
				return array(
					'status' => false,
					'text' => 'Aucun fichier n est actuellement associe a ce document.',
				);
			}
			$keywords = trim((string)($values['keywords'] ?? ''));
			$visibilityType = (string)($values['visibility_type'] ?? \dbObject\ObjectVisibility::getDefaultVisibilityType());
			$now = new \DateTimeImmutable();

			$this->set('title', $title);
			$this->set('description', $description);
			$this->set('content', $content);
			$this->set('keywords', $keywords);
			$this->set('documenttype', $documentType);
			$this->set('externalurl', $externalUrl !== '' ? $externalUrl : null);
			$this->set('openinnewwindow', $openInNewWindow ? 1 : 0);
			if ($documentType !== self::TYPE_HTML) {
				$this->clearDraftContentState();
			}
			$this->set('IDusermodification', $userId);
			$this->set('datemodification', $now);

			$pdo = self::getPdo();
			$startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();
			$organization = new \dbObject\Organization();

			try {
				if ($startedTransaction) {
					$pdo->beginTransaction();
				}

				if (!$isWithoutContext && !$organization->load($organizationId)) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return array(
						'status' => false,
						'text' => 'Organisation introuvable.',
					);
				}

				if (
					$isWithoutContext
					&& $documentType === self::TYPE_UPLOADED_FILE
					&& ($uploadedFile !== null || $removeUploadedFile)
				) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return array(
						'status' => false,
						'text' => 'Les fichiers sans contexte ne peuvent pas etre modifies depuis cet editeur.',
					);
				}

				if (
					!$isWithoutContext
					&& $documentType === self::TYPE_UPLOADED_FILE
					&& !$organization->hasNextcloudDocumentStorage()
				) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return array(
						'status' => false,
						'text' => 'Le stockage Nextcloud n est pas configure pour cette organisation.',
					);
				}

				$saveResult = $this->save();
				if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $saveResult;
				}

				if (!$isWithoutContext && $documentType === self::TYPE_UPLOADED_FILE) {
					$previousStoredPath = trim((string)$this->get('storedfilepath'));
					if ($removeUploadedFile) {
						$deleteResult = $this->deleteStoredFileFromOrganizationStorage($organization);
						if (!is_array($deleteResult) || empty($deleteResult['status'])) {
							if ($startedTransaction && $pdo->inTransaction()) {
								$pdo->rollBack();
							}

							return $deleteResult;
						}
					}

					if ($uploadedFile !== null) {
						$fileStorageResult = $this->applyUploadedFileToOrganizationStorage($organization, $uploadedFile);
						if (!is_array($fileStorageResult) || empty($fileStorageResult['status'])) {
							if ($startedTransaction && $pdo->inTransaction()) {
								$pdo->rollBack();
							}

							return $fileStorageResult;
						}

						$newStoredPath = trim((string)($fileStorageResult['uploadedPath'] ?? ''));
						if ($previousStoredPath !== '' && $previousStoredPath !== $newStoredPath) {
							$organization->deleteDocumentFileFromNextcloud($previousStoredPath);
						}
					}

					if ($removeUploadedFile || $uploadedFile !== null) {
						$fileMetadataSaveResult = $this->save();
						if (!is_array($fileMetadataSaveResult) || ($fileMetadataSaveResult['status'] ?? false) !== true) {
							if ($startedTransaction && $pdo->inTransaction()) {
								$pdo->rollBack();
							}

							return $fileMetadataSaveResult;
						}
					}
				}

				if (!$isWithoutContext) {
					$visibilitySaveResult = $this->saveVisibilityRule($visibilityType);
					if (!is_array($visibilitySaveResult) || ($visibilitySaveResult['status'] ?? false) !== true) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}

						return $visibilitySaveResult;
					}
				}

				if ($startedTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}

				if ((int)$this->get('IDdocument_parent') > 0) {
					$this->refreshAncestorFolderActivity();
				}

				$this->releaseEditLock($userId, true);

				return $saveResult;
			} catch (\Throwable $exception) {
				if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'text' => 'Impossible de mettre a jour ce document.',
				);
			}
		}

		protected function saveMoveContextAndVisibility(int $organizationId, ?int $targetHolonId, ?int $targetParentDocumentId, int $userId, \DateTimeImmutable $now)
		{
			$organizationId = (int)$organizationId;
			$targetHolonId = $targetHolonId !== null ? (int)$targetHolonId : 0;
			$targetParentDocumentId = $targetParentDocumentId !== null ? (int)$targetParentDocumentId : 0;
			$userId = (int)$userId;
			$visibilityRule = $this->getPrimaryVisibilityRuleRow();
			$visibilityType = \dbObject\ObjectVisibility::normalizeVisibilityType(
				$visibilityRule['visibility_type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION
			);
			$visibilityNeedsHolonTarget = \dbObject\ObjectVisibility::requiresHolonTarget($visibilityType);
			$finalVisibilityType = $visibilityType;

			$this->set('IDorganization', $organizationId);
			$this->set('IDholon', $targetHolonId > 0 ? $targetHolonId : null);
			$this->set('IDdocument_parent', $targetParentDocumentId > 0 ? $targetParentDocumentId : null);
			$this->set('IDusermodification', $userId > 0 ? $userId : null);
			$this->set('datemodification', $now);

			$saveResult = $this->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return $saveResult;
			}

			if ($visibilityNeedsHolonTarget) {
				$visibilitySaveResult = $this->saveVisibilityRule($visibilityType);
				if (!is_array($visibilitySaveResult) || ($visibilitySaveResult['status'] ?? false) !== true) {
					if (
						$visibilityType === \dbObject\ObjectVisibility::TYPE_ROLE
						&& $targetHolonId > 0
					) {
						$visibilitySaveResult = $this->saveVisibilityRule(\dbObject\ObjectVisibility::TYPE_CIRCLE);
						if (is_array($visibilitySaveResult) && ($visibilitySaveResult['status'] ?? false) === true) {
							$finalVisibilityType = \dbObject\ObjectVisibility::TYPE_CIRCLE;
						}
					}

					if (!is_array($visibilitySaveResult) || ($visibilitySaveResult['status'] ?? false) !== true) {
						$visibilitySaveResult = $this->saveVisibilityRule(\dbObject\ObjectVisibility::TYPE_ORGANIZATION);
						$finalVisibilityType = \dbObject\ObjectVisibility::TYPE_ORGANIZATION;
					}
				}

				if (!is_array($visibilitySaveResult) || ($visibilitySaveResult['status'] ?? false) !== true) {
					return $visibilitySaveResult;
				}
			}

			return array(
				'status' => true,
				'visibilityType' => $finalVisibilityType,
			);
		}

		protected function moveDescendantsToContext(int $organizationId, ?int $targetHolonId, int $userId, \DateTimeImmutable $now)
		{
			foreach ($this->getDirectChildren() as $childDocument) {
				if (!($childDocument instanceof \dbObject\Document) || (int)$childDocument->getId() <= 0) {
					continue;
				}

				$childMoveResult = $childDocument->saveMoveContextAndVisibility(
					$organizationId,
					$targetHolonId,
					(int)$childDocument->get('IDdocument_parent') > 0 ? (int)$childDocument->get('IDdocument_parent') : null,
					$userId,
					$now
				);
				if (!is_array($childMoveResult) || ($childMoveResult['status'] ?? false) !== true) {
					return $childMoveResult;
				}

				if ($childDocument->isFolder()) {
					$descendantMoveResult = $childDocument->moveDescendantsToContext($organizationId, $targetHolonId, $userId, $now);
					if (!is_array($descendantMoveResult) || ($descendantMoveResult['status'] ?? false) !== true) {
						return $descendantMoveResult;
					}
				}
			}

			return array('status' => true);
		}

		public function moveToOrganizationContext(int $organizationId, ?int $targetHolonId, ?int $targetParentDocumentId, int $userId)
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			$targetHolonId = $targetHolonId !== null ? (int)$targetHolonId : 0;
			$targetParentDocumentId = $targetParentDocumentId !== null ? (int)$targetParentDocumentId : 0;

			if ($targetHolonId < 0 || $targetParentDocumentId < 0) {
				return array(
					'status' => false,
					'text' => 'Destination invalide.',
				);
			}

			if ((int)$this->getId() <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return array(
					'status' => false,
					'text' => 'Document introuvable.',
				);
			}

			if ($userId <= 0 || $userId !== (int)$this->get('IDuser') || !$this->canEditInOrganizationContext($organizationId, $userId, false)) {
				return array(
					'status' => false,
					'text' => 'Acces refuse.',
				);
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return array(
					'status' => false,
					'text' => 'Organisation introuvable.',
				);
			}

			$currentHolonId = (int)$this->get('IDholon');
			$currentParentDocumentId = (int)$this->get('IDdocument_parent');
			$resolvedParent = $this->resolveParentDocumentForContext(
				$organizationId,
				$targetParentDocumentId,
				(int)$this->getId()
			);
			if (($resolvedParent['status'] ?? false) !== true) {
				return array(
					'status' => false,
					'text' => (string)($resolvedParent['text'] ?? 'Destination invalide.'),
				);
			}

			$targetParentDocument = $resolvedParent['parentDocument'] ?? null;
			if ($targetParentDocument instanceof \dbObject\Document) {
				$targetHolonId = ($resolvedParent['holonId'] ?? null) !== null
					? (int)$resolvedParent['holonId']
					: 0;
			}

			$targetHolon = null;
			if (!($targetParentDocument instanceof \dbObject\Document) && $targetHolonId > 0) {
				$targetHolon = new \dbObject\Holon();
				if (
					!$targetHolon->load($targetHolonId)
					|| !(bool)$targetHolon->get('active')
					|| !(bool)$targetHolon->get('visible')
					|| !$organization->containsHolon($targetHolon)
				) {
					return array(
						'status' => false,
						'text' => 'Destination invalide.',
					);
				}
			}

			$resolvedTargetHolonId = $targetHolonId > 0 ? $targetHolonId : 0;
			$resolvedTargetParentDocumentId = $targetParentDocument instanceof \dbObject\Document
				? (int)$targetParentDocument->getId()
				: 0;

			if (
				$currentHolonId === $resolvedTargetHolonId
				&& $currentParentDocumentId === $resolvedTargetParentDocumentId
			) {
				return array(
					'status' => false,
					'text' => 'Le document est deja dans cette destination.',
				);
			}

			$oldParentDocumentId = $currentParentDocumentId;
			$oldParentRefreshIds = array();
			foreach ($this->getParentFolderChain() as $parentFolder) {
				if ($parentFolder instanceof \dbObject\Document && $parentFolder->isFolder()) {
					$oldParentRefreshIds[(int)$parentFolder->getId()] = (int)$parentFolder->getId();
				}
			}

			$pdo = self::getPdo();
			$startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();
			$now = new \DateTimeImmutable();
			$finalVisibilityType = \dbObject\ObjectVisibility::TYPE_ORGANIZATION;

			try {
				if ($startedTransaction) {
					$pdo->beginTransaction();
				}

				$moveResult = $this->saveMoveContextAndVisibility(
					$organizationId,
					$resolvedTargetHolonId > 0 ? $resolvedTargetHolonId : null,
					$resolvedTargetParentDocumentId > 0 ? $resolvedTargetParentDocumentId : null,
					$userId,
					$now
				);
				if (!is_array($moveResult) || ($moveResult['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $moveResult;
				}

				$finalVisibilityType = (string)($moveResult['visibilityType'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION);

				if ($this->isFolder()) {
					$descendantMoveResult = $this->moveDescendantsToContext(
						$organizationId,
						$resolvedTargetHolonId > 0 ? $resolvedTargetHolonId : null,
						$userId,
						$now
					);
					if (!is_array($descendantMoveResult) || ($descendantMoveResult['status'] ?? false) !== true) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}

						return $descendantMoveResult;
					}
				}

				$refreshVisited = array();
				if ($this->isFolder()) {
					$this->refreshFolderActivity($refreshVisited);
				}

				if ($oldParentDocumentId > 0) {
					$this->refreshAncestorFolderActivity($oldParentDocumentId, $refreshVisited);
				}

				if ($resolvedTargetParentDocumentId > 0) {
					$this->refreshAncestorFolderActivity($resolvedTargetParentDocumentId, $refreshVisited);
				}

				foreach ($oldParentRefreshIds as $refreshFolderId) {
					if ($refreshFolderId === $oldParentDocumentId) {
						continue;
					}

					$this->refreshAncestorFolderActivity($refreshFolderId, $refreshVisited);
				}

				if ($startedTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}

				$message = 'Document deplace.';
				if ($finalVisibilityType !== $visibilityType) {
					$message = 'Document deplace. La visibilite a ete adaptee.';
				}

				return array(
					'status' => true,
					'text' => $message,
					'document' => array(
						'id' => (int)$this->getId(),
						'title' => (string)$this->get('title'),
						'organizationId' => $organizationId,
						'holonId' => $resolvedTargetHolonId,
						'parentDocumentId' => $resolvedTargetParentDocumentId,
					),
					'previousHolonId' => $currentHolonId,
					'previousParentDocumentId' => $currentParentDocumentId,
					'targetHolonId' => $resolvedTargetHolonId,
					'targetParentDocumentId' => $resolvedTargetParentDocumentId,
					'visibilityType' => $finalVisibilityType,
				);
			} catch (\Throwable $exception) {
				if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return array(
					'status' => false,
					'text' => 'Impossible de deplacer ce document.',
				);
			}
		}

		public function getPrimaryVisibilityRuleRow()
		{
			$organizationId = (int)$this->get('IDorganization');
			if ((int)$this->getId() <= 0 || $organizationId <= 0) {
				return \dbObject\ObjectVisibility::buildFallbackRuleData($organizationId);
			}

			$ruleRow = \dbObject\ObjectVisibility::loadActiveRuleRow(
				self::getVisibilityObjectType(),
				(int)$this->getId(),
				$organizationId
			);

			return is_array($ruleRow)
				? $ruleRow
				: \dbObject\ObjectVisibility::buildFallbackRuleData($organizationId);
		}

		protected function resolveVisibilityTargetHolonId($visibilityType): array
		{
			$visibilityType = \dbObject\ObjectVisibility::normalizeVisibilityType($visibilityType);
			if (!\dbObject\ObjectVisibility::requiresHolonTarget($visibilityType)) {
				return array(
					'status' => true,
					'holonId' => null,
				);
			}

			$documentHolonId = (int)$this->get('IDholon');
			if ($documentHolonId <= 0) {
				return array(
					'status' => false,
					'text' => $visibilityType === \dbObject\ObjectVisibility::TYPE_ROLE
						? 'La visibilite role demande un document lie a un role.'
						: 'La visibilite cercle demande un document lie a un cercle ou a un role.',
				);
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($documentHolonId)
				|| !(bool)$holon->get('active')
				|| !(bool)$holon->get('visible')
			) {
				return array(
					'status' => false,
					'text' => 'Holon du document introuvable.',
				);
			}

			if ($visibilityType === \dbObject\ObjectVisibility::TYPE_ROLE) {
				if ((int)$holon->get('IDtypeholon') !== 1) {
					return array(
						'status' => false,
						'text' => 'La visibilite role demande un document lie a un role.',
					);
				}

				return array(
					'status' => true,
					'holonId' => (int)$holon->getId(),
				);
			}

			if ((int)$holon->get('IDtypeholon') === 2) {
				return array(
					'status' => true,
					'holonId' => (int)$holon->getId(),
				);
			}

			$circleId = (int)$holon->getContainingCircleId(false);
			if ($circleId <= 0) {
				return array(
					'status' => false,
					'text' => 'La visibilite cercle demande un document place dans un cercle ou dans un role de cercle.',
				);
			}

			return array(
				'status' => true,
				'holonId' => $circleId,
			);
		}

		public function saveVisibilityRule($visibilityType, $targetHolonId = null)
		{
			$organizationId = (int)$this->get('IDorganization');
			if ((int)$this->getId() <= 0 || $organizationId <= 0) {
				return array(
					'status' => false,
					'text' => 'Contexte de visibilite invalide.',
				);
			}

			$resolvedTarget = $targetHolonId !== null
				? array('status' => true, 'holonId' => (int)$targetHolonId)
				: $this->resolveVisibilityTargetHolonId($visibilityType);
			if (($resolvedTarget['status'] ?? false) !== true) {
				return $resolvedTarget;
			}

			return \dbObject\ObjectVisibility::saveSingleRule(
				self::getVisibilityObjectType(),
				(int)$this->getId(),
				$organizationId,
				$visibilityType,
				$resolvedTarget['holonId'] ?? null
			);
		}

		public function currentViewerCanAccessVisibility($organizationId = 0, $ruleRow = null): bool
		{
			$organizationId = (int)$organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');
			if ($organizationId <= 0) {
				return false;
			}

			$viewerContext = \dbObject\ObjectVisibility::buildCurrentViewerContext($organizationId);
			return \dbObject\ObjectVisibility::viewerCanAccessRule(
				is_array($ruleRow) ? $ruleRow : $this->getPrimaryVisibilityRuleRow(),
				$viewerContext,
				array(
					'organizationId' => $organizationId,
					'ownerUserId' => (int)$this->get('IDuser'),
				)
			);
		}

		public function getVisibilityDisplayData($organizationId = 0, $ruleRow = null): array
		{
			$organizationId = (int)$organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');

			return \dbObject\ObjectVisibility::buildDisplayData(
				is_array($ruleRow) ? $ruleRow : $this->getPrimaryVisibilityRuleRow(),
				$organizationId
			);
		}

		public function getOrganizationContextBreadcrumbItems(): array
		{
			$organizationId = (int)$this->get('IDorganization');
			if ($organizationId <= 0) {
				return [];
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return [];
			}

			$items = array();
			$organizationLabel = trim((string)$organization->get('name'));
			if ($organizationLabel !== '') {
				$items[] = array(
					'label' => $organizationLabel,
					'organizationId' => $organizationId,
					'holonId' => 0,
				);
			}

			$holonId = (int)$this->get('IDholon');
			if ($holonId <= 0) {
				return $items;
			}

			$holon = new \dbObject\Holon();
			if (!$holon->load($holonId)) {
				return $items;
			}

			foreach ($holon->getPathHolons() as $pathHolon) {
				if ((int)$pathHolon->get('IDtypeholon') === 4) {
					continue;
				}

				$name = trim((string)$pathHolon->get('name'));
				if ($name === '') {
					continue;
				}

				$items[] = array(
					'label' => $name,
					'organizationId' => $organizationId,
					'holonId' => (int)$pathHolon->getId(),
				);
			}

			foreach ($this->getParentFolderChain() as $parentFolder) {
				if (!($parentFolder instanceof \dbObject\Document) || !$parentFolder->isFolder()) {
					continue;
				}

				$folderName = trim((string)$parentFolder->get('title'));
				if ($folderName === '') {
					continue;
				}

				$items[] = array(
					'label' => $folderName,
					'organizationId' => $organizationId,
					'holonId' => $holonId,
				);
			}

			return $items;
		}

		public function getOrganizationContextLabel(): string
		{
			$labels = array();
			foreach ($this->getOrganizationContextBreadcrumbItems() as $item) {
				$label = trim((string)($item['label'] ?? ''));
				if ($label !== '') {
					$labels[] = $label;
				}
			}

			return count($labels) > 0 ? implode(" > ", $labels) : '';
		}
	}

?>
