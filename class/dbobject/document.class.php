<?php
	namespace dbObject;

	class document extends DbObject
	{
		public const TYPE_HTML = 'html';
		public const TYPE_EXTERNAL_LINK = 'external_link';
		public const TYPE_UPLOADED_FILE = 'uploaded_file';
		public const TYPE_FOLDER = 'folder';
		public const TYPE_PV = 'pv';
		public const PV_STAGE_PREPARATION = 'preparation';
		public const PV_STAGE_MEETING = 'meeting';
		public const PV_STAGE_REVIEW = 'review';
		public const PV_STAGE_VALIDATED = 'validated';
		public const EDIT_VISIBILITY_OBJECT_TYPE = 'document_edit';
		public const DEFAULT_EDIT_VISIBILITY_TYPE = \dbObject\ObjectVisibility::TYPE_SELF;

	    public static function tableName()
		{
			return 'document'; // Nom de la table correspondante
		}

		// Defini le contenu de la table
		public static function rules()
		{
			return [
				[['title'], 'required'],						// Champs obligatoires
			[['id', 'version', 'estDossier', 'active', 'is_template', 'pv_editor_handover_open', 'openinnewwindow', 'storedfilesize'], 'integer'],				// Nombres entiers
				[['title', 'codeview', 'codeedit', 'keywords', 'documenttype', 'pvstage', 'externalurl', 'storedfilepath', 'storedfilename', 'storedfilemime'], 'string'],	// Chaines de caractere
				[['description', 'content', 'contentedition'], 'text'],			// Textes libres
				[['datecreation', 'datemodification', 'dateedition', 'datecontentedition'], 'datetime'],	// Date avec precision des heures
				[['IDuser', 'IDusercreation', 'IDusermodification', 'IDuseredition', 'IDuser_pv_editor', 'IDorganization', 'IDholon', 'IDdocument_parent', 'IDevent'], 'fk'],	// Cles etrangeres
				[['id'], 'safe'],								// Champs proteges
			];
		}

		// Defini les labels standards pour cet objet, affiches dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'active' => 'Actif',
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
				'IDevent' => 'Evenement associe',
				'IDuser_pv_editor' => 'Secretaire du PV',
				'pv_editor_handover_open' => 'Passation du secretaire ouverte',
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
				'pvstage' => 'Etape du PV',
				'is_template' => 'Modele de PV',
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
				'IDevent' => 'Evenement auquel ce document est rattache si le fichier provient de l agenda',
				'estDossier' => 'Permet de traiter cette entree comme un dossier',
				'IDdocument_parent' => 'Dossier qui contient ce document',
				'dateedition' => 'Date du dernier signal de presence pendant l edition',
				'datecontentedition' => 'Date de mise a jour du brouillon temporaire',
				'documenttype' => 'Permet de distinguer les documents HTML, les liens externes et les dossiers',
				'pvstage' => 'Etape actuelle du flux d un document PV: preparation, reunion, relecture ou valide',
				'is_template' => 'Permet d utiliser la structure et le contenu de ce PV lors d une nouvelle creation',
				'IDuser_pv_editor' => 'Personne qui tient le PV pendant la reunion et peut modifier tous les points.',
				'pv_editor_handover_open' => 'Indique que le secretaire actuel autorise un invite a reprendre son role.',
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
				'pvstage' => 30,
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

		public function isArchived(): bool
		{
			return (int)$this->get('active') !== 1;
		}

		public function isPvTemplate(): bool
		{
			return $this->isPvDocument() && (int)$this->get('is_template') === 1;
		}

		public function canUseAsPvTemplate(int $organizationId): bool
		{
			return (int)$this->getId() > 0
				&& !$this->isArchived()
				&& $this->isPvTemplate()
				&& (int)$this->get('IDorganization') === (int)$organizationId
				&& $this->canViewDirectlyInOrganization($organizationId);
		}

		public function updatePvTemplateState(int $organizationId, int $userId, bool $isTemplate): array
		{
			if (
				(int)$this->getId() <= 0
				|| (int)$this->get('IDorganization') !== (int)$organizationId
				|| !$this->isPvDocument()
				|| !$this->canUserManagePvDocument($userId)
			) {
				return array('status' => false, 'text' => 'Acces refuse.');
			}

			$this->set('is_template', $isTemplate ? 1 : 0);
			$this->set('IDusermodification', $userId);
			$this->set('datemodification', new \DateTimeImmutable());

			return $this->save();
		}

		public function canManageLifecycle(int $organizationId, int $userId): bool
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			if ($organizationId <= 0 || $userId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return false;
			}

			if ($this->isPvDocument()) {
				return $this->isPvCreatorOrEditor($userId);
			}

			return $this->canEditInOrganizationContext($organizationId, $userId, false);
		}

		public function canDeleteDocument(): bool
		{
			if ($this->isPvDocument() || (int)$this->get('IDevent') > 0) {
				return false;
			}

			$child = self::fetchRow(
				'select `id` from `document` where `IDdocument_parent` = :document_id limit 1',
				['document_id' => (int)$this->getId()]
			);

			return $child === false;
		}

		public static function getVisibilityObjectType(): string
		{
			return \dbObject\ObjectVisibility::OBJECT_TYPE_DOCUMENT;
		}

		public static function getEditVisibilityObjectType(): string
		{
			return self::EDIT_VISIBILITY_OBJECT_TYPE;
		}

		public static function getDefaultEditVisibilityType(): string
		{
			return self::DEFAULT_EDIT_VISIBILITY_TYPE;
		}

		public static function getApplicationDefaultScopeTypes(int $organizationId): array
		{
			$organizationId = (int)$organizationId;
			$defaults = array(
				'visibilityType' => \dbObject\ObjectVisibility::TYPE_ORGANIZATION,
				'editVisibilityType' => self::getDefaultEditVisibilityType(),
			);

			if ($organizationId <= 0) {
				return $defaults;
			}

			$organizationApplication = \dbObject\OrganizationApplication::loadByOrganizationAndDirectory(
				$organizationId,
				'documents',
				false
			);
			if (!($organizationApplication instanceof \dbObject\OrganizationApplication)) {
				return $defaults;
			}

			$parameters = $organizationApplication->getParametersArray();
			$storedDefaults = is_array($parameters['documentDefaults'] ?? null)
				? $parameters['documentDefaults']
				: array();

			$defaults['visibilityType'] = \dbObject\ObjectVisibility::normalizeVisibilityType(
				(string)($storedDefaults['visibilityType'] ?? $defaults['visibilityType'])
			);
			$defaults['editVisibilityType'] = \dbObject\ObjectVisibility::normalizeVisibilityType(
				(string)($storedDefaults['editVisibilityType'] ?? $defaults['editVisibilityType'])
			);

			return $defaults;
		}

		public static function getDefaultVisibilityTypeForOrganization(int $organizationId): string
		{
			$defaults = self::getApplicationDefaultScopeTypes($organizationId);
			return (string)($defaults['visibilityType'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION);
		}

		public static function getDefaultEditVisibilityTypeForOrganization(int $organizationId): string
		{
			$defaults = self::getApplicationDefaultScopeTypes($organizationId);
			return (string)($defaults['editVisibilityType'] ?? self::getDefaultEditVisibilityType());
		}

		public static function resolveCompatibleScopeTypeForHolonId(string $visibilityType, int $organizationId, ?int $holonId, string $fallbackType): string
		{
			$resolvedType = \dbObject\ObjectVisibility::normalizeVisibilityType($visibilityType);
			$fallbackType = \dbObject\ObjectVisibility::normalizeVisibilityType($fallbackType);
			$holonId = $holonId !== null ? (int)$holonId : 0;

			if (!\dbObject\ObjectVisibility::requiresHolonTarget($resolvedType)) {
				return $resolvedType;
			}

			if ($holonId <= 0) {
				return $fallbackType;
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($holonId)
				|| !(bool)$holon->get('active')
				|| !(bool)$holon->get('visible')
				|| ($organizationId > 0 && (int)$holon->get('IDorganization') > 0 && (int)$holon->get('IDorganization') !== $organizationId)
			) {
				return $fallbackType;
			}

			$holonTypeId = (int)$holon->get('IDtypeholon');
			$circleId = $holonTypeId === 2
				? (int)$holon->getId()
				: (int)$holon->getContainingCircleId(false);

			if ($resolvedType === \dbObject\ObjectVisibility::TYPE_ROLE) {
				if ($holonTypeId === 1) {
					return \dbObject\ObjectVisibility::TYPE_ROLE;
				}

				if ($circleId > 0) {
					return \dbObject\ObjectVisibility::TYPE_CIRCLE;
				}

				return $fallbackType;
			}

			if ($resolvedType === \dbObject\ObjectVisibility::TYPE_CIRCLE) {
				return $circleId > 0
					? \dbObject\ObjectVisibility::TYPE_CIRCLE
					: $fallbackType;
			}

			return $resolvedType;
		}

		protected function getOwnerDepartureFallbackScopeData(): array
		{
			$organizationId = (int)$this->get('IDorganization');
			$documentHolonId = (int)$this->get('IDholon');

			if ($documentHolonId <= 0) {
				return array(
					'type' => \dbObject\ObjectVisibility::TYPE_ORGANIZATION,
					'holonId' => null,
				);
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($documentHolonId)
				|| !(bool)$holon->get('active')
				|| !(bool)$holon->get('visible')
			) {
				return array(
					'type' => \dbObject\ObjectVisibility::TYPE_ORGANIZATION,
					'holonId' => null,
				);
			}

			if ((int)$holon->get('IDtypeholon') === 1) {
				return array(
					'type' => \dbObject\ObjectVisibility::TYPE_ROLE,
					'holonId' => (int)$holon->getId(),
				);
			}

			$circleId = (int)$holon->getContainingCircleId(true);
			if ($circleId > 0) {
				return array(
					'type' => \dbObject\ObjectVisibility::TYPE_CIRCLE,
					'holonId' => $circleId,
				);
			}

			return array(
				'type' => \dbObject\ObjectVisibility::TYPE_ORGANIZATION,
				'holonId' => null,
			);
		}

		protected function ownerStillBelongsToFallbackContext(array $fallbackScope): bool
		{
			$organizationId = (int)$this->get('IDorganization');
			$ownerUserId = (int)$this->get('IDuser');
			$fallbackType = \dbObject\ObjectVisibility::normalizeVisibilityType((string)($fallbackScope['type'] ?? ''));
			$fallbackHolonId = (int)($fallbackScope['holonId'] ?? 0);

			if ($organizationId <= 0 || $ownerUserId <= 0) {
				return false;
			}

			if ($fallbackType === \dbObject\ObjectVisibility::TYPE_ROLE && $fallbackHolonId > 0) {
				$membershipCount = self::fetchValue(
					"
						SELECT COUNT(*)
						FROM `user_holon`
						WHERE `IDuser` = :user_id
						  AND `IDholon` = :holon_id
						  AND `active` = 1
					",
					array(
						'user_id' => $ownerUserId,
						'holon_id' => $fallbackHolonId,
					)
				);

				return (int)$membershipCount > 0;
			}

			if ($fallbackType === \dbObject\ObjectVisibility::TYPE_CIRCLE && $fallbackHolonId > 0) {
				$circle = new \dbObject\Holon();
				if (
					!$circle->load($fallbackHolonId)
					|| !(bool)$circle->get('active')
					|| !(bool)$circle->get('visible')
				) {
					return false;
				}

				$memberUserIds = $circle->getAssociatedMemberUserIds(array(
					'organizationId' => $organizationId,
					'includeDescendants' => true,
					'skipPermissionFilter' => true,
				));

				return in_array($ownerUserId, array_map('intval', $memberUserIds), true);
			}

			if (function_exists('commonUserHasOrganizationAccess')) {
				return \commonUserHasOrganizationAccess($ownerUserId, $organizationId);
			}

			$membershipCount = self::fetchValue(
				"
					SELECT COUNT(*)
					FROM `user_organization`
					WHERE `IDuser` = :user_id
					  AND `IDorganization` = :organization_id
					  AND `active` = 1
				",
				array(
					'user_id' => $ownerUserId,
					'organization_id' => $organizationId,
				)
			);

			return (int)$membershipCount > 0;
		}

		public function normalizeSelfScopedRulesAfterOwnerDeparture(): array
		{
			$organizationId = (int)$this->get('IDorganization');
			if ((int)$this->getId() <= 0 || $organizationId <= 0) {
				return array(
					'status' => true,
					'changed' => false,
				);
			}

			$visibilityRule = $this->getPrimaryVisibilityRuleRow();
			$editVisibilityRule = $this->getPrimaryEditVisibilityRuleRow();
			$hasSelfVisibility = \dbObject\ObjectVisibility::normalizeVisibilityType((string)($visibilityRule['visibility_type'] ?? ''))
				=== \dbObject\ObjectVisibility::TYPE_SELF;
			$hasSelfEditVisibility = \dbObject\ObjectVisibility::normalizeVisibilityType((string)($editVisibilityRule['visibility_type'] ?? ''))
				=== \dbObject\ObjectVisibility::TYPE_SELF;

			if (!$hasSelfVisibility && !$hasSelfEditVisibility) {
				return array(
					'status' => true,
					'changed' => false,
				);
			}

			$fallbackScope = $this->getOwnerDepartureFallbackScopeData();
			if ($this->ownerStillBelongsToFallbackContext($fallbackScope)) {
				return array(
					'status' => true,
					'changed' => false,
				);
			}

			$fallbackType = \dbObject\ObjectVisibility::normalizeVisibilityType((string)($fallbackScope['type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION));
			if ($fallbackType === \dbObject\ObjectVisibility::TYPE_SELF) {
				$fallbackType = \dbObject\ObjectVisibility::TYPE_ORGANIZATION;
			}

			$changed = false;

			if ($hasSelfVisibility) {
				$saveVisibilityResult = $this->saveVisibilityRule($fallbackType);
				if (!is_array($saveVisibilityResult) || ($saveVisibilityResult['status'] ?? false) !== true) {
					return is_array($saveVisibilityResult)
						? $saveVisibilityResult
						: array(
							'status' => false,
							'text' => 'Impossible de mettre a jour la visibilite du document.',
						);
				}

				$changed = true;
			}

			if ($hasSelfEditVisibility) {
				$saveEditVisibilityResult = $this->saveEditVisibilityRule($fallbackType);
				if (!is_array($saveEditVisibilityResult) || ($saveEditVisibilityResult['status'] ?? false) !== true) {
					return is_array($saveEditVisibilityResult)
						? $saveEditVisibilityResult
						: array(
							'status' => false,
							'text' => 'Impossible de mettre a jour la portee d edition du document.',
						);
				}

				$changed = true;
			}

			return array(
				'status' => true,
				'changed' => $changed,
				'fallbackType' => $fallbackType,
			);
		}

		public static function normalizeSelfScopedDocumentsForAuthorContext(int $organizationId, int $userId): array
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;

			if ($organizationId <= 0 || $userId <= 0) {
				return array(
					'status' => true,
					'changed' => 0,
				);
			}

			$rows = self::fetchAll(
				"
					SELECT `id`
					FROM `" . self::tableName() . "`
					WHERE `IDorganization` = :organization_id
					  AND `IDuser` = :user_id
					ORDER BY `id` ASC
				",
				array(
					'organization_id' => $organizationId,
					'user_id' => $userId,
				)
			);

			if ($rows === false) {
				return array(
					'status' => false,
					'text' => 'Impossible de charger les documents a verifier.',
				);
			}

			$changedCount = 0;
			foreach ($rows as $row) {
				$documentId = (int)($row['id'] ?? 0);
				if ($documentId <= 0) {
					continue;
				}

				$document = new \dbObject\Document();
				if (!$document->load($documentId)) {
					continue;
				}

				$normalizeResult = $document->normalizeSelfScopedRulesAfterOwnerDeparture();
				if (!is_array($normalizeResult) || ($normalizeResult['status'] ?? false) !== true) {
					return is_array($normalizeResult)
						? $normalizeResult
						: array(
							'status' => false,
							'text' => 'Impossible de verifier la portee des documents.',
						);
				}

				if (!empty($normalizeResult['changed'])) {
					$changedCount += 1;
				}
			}

			return array(
				'status' => true,
				'changed' => $changedCount,
			);
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

		public function isAvailableInDocumentsList(?\DateTimeInterface $referenceDate = null): bool
		{
			if ($this->isPvDocument()) {
				$userId = function_exists('commonGetCurrentUserId')
					? (int)\commonGetCurrentUserId()
					: (int)($_SESSION['currentUser'] ?? 0);
				return $this->isVisibleInDocumentsListForUser($userId, $referenceDate);
			}

			$referenceDate = $referenceDate ?: new \DateTimeImmutable();
			$createdAt = $this->get('datecreation');
			if (!($createdAt instanceof \DateTimeInterface)) {
				return true;
			}

			return $createdAt->getTimestamp() <= $referenceDate->getTimestamp();
		}

		public function canEdit()
		{
			$organizationId = (int)$this->get('IDorganization');
			return $organizationId > 0
				? $this->canEditInOrganizationContext($organizationId, null, false)
				: (
					isset($_SESSION["currentUser"])
					&& (int)$_SESSION["currentUser"] === (int)$this->get("IDuser")
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

			if ($userId <= 0) {
				return false;
			}

			if ($documentOrganizationId <= 0) {
				return $organizationId <= 0 && $userId === (int)$this->get('IDuser');
			}

			if (
				function_exists('commonUserHasOrganizationAccess')
				&& !\commonUserHasOrganizationAccess($userId, $documentOrganizationId)
			) {
				return false;
			}

			if (!$this->currentViewerCanAccessVisibility($documentOrganizationId)) {
				return false;
			}

			return $this->currentViewerCanAccessEditVisibility($documentOrganizationId);
		}

		public function canMoveInOrganizationContext(int $organizationId, int $userId): bool
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;

			if ($organizationId <= 0 || $userId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return false;
			}

			return ($this->isPvDocument() && $this->canUserManagePvDocument($userId))
				|| $this->canEditInOrganizationContext($organizationId, $userId, false);
		}

		protected function normalizeScopeTypeForCurrentContext(string $visibilityType, string $fallbackType): string
		{
			return self::resolveCompatibleScopeTypeForHolonId(
				$visibilityType,
				(int)$this->get('IDorganization'),
				(int)$this->get('IDholon') > 0 ? (int)$this->get('IDholon') : null,
				$fallbackType
			);
		}

		protected function resolveScopeTypeInput(array $values, string $inputKey, string $fallbackType, bool $isCreate): string
		{
			$hasExplicitValue = array_key_exists($inputKey, $values)
				&& trim((string)$values[$inputKey]) !== '';
			if ($hasExplicitValue) {
				return \dbObject\ObjectVisibility::normalizeVisibilityType((string)$values[$inputKey]);
			}

			if ($isCreate) {
				return $this->normalizeScopeTypeForCurrentContext($fallbackType, $fallbackType);
			}

			$currentRule = $inputKey === 'edit_visibility_type'
				? $this->getPrimaryEditVisibilityRuleRow()
				: $this->getPrimaryVisibilityRuleRow();

			return \dbObject\ObjectVisibility::normalizeVisibilityType(
				(string)($currentRule['visibility_type'] ?? $fallbackType)
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

		public function getAssociatedEvent()
		{
			$eventId = (int)$this->get('IDevent');
			if ($eventId <= 0) {
				return null;
			}

			$event = new \dbObject\Event();
			if (!$event->load($eventId)) {
				return null;
			}

			return $event;
		}

		public function getPvEditorUserId(): int
		{
			return $this->isPvDocument() ? (int)$this->get('IDuser_pv_editor') : 0;
		}

		public function isPvEditor(int $userId): bool
		{
			return $userId > 0 && $userId === $this->getPvEditorUserId();
		}

		public function isPvEditorHandoverOpen(): bool
		{
			return $this->isPvDocument() && (int)$this->get('pv_editor_handover_open') === 1;
		}

		public function isPvCreatorOrEditor(int $userId): bool
		{
			return $this->isPvDocument()
				&& $userId > 0
				&& ($userId === $this->getCreatedByUserId() || $this->isPvEditor($userId));
		}

		public function canUserManagePvDocument(int $userId): bool
		{
			if (!$this->isPvDocument() || $this->isPvValidated() || $userId <= 0) {
				return false;
			}

			return $this->isPvEditor($userId)
				|| ($this->getPvEditorUserId() <= 0 && $userId === $this->getCreatedByUserId());
		}

		public function isVisibleInDocumentsListForUser(int $userId, ?\DateTimeInterface $referenceDate = null): bool
		{
			if (!$this->isPvDocument()) {
				return true;
			}

			if ($this->isPvCreatorOrEditor($userId)) {
				return true;
			}

			if (!($this->getAssociatedEvent() instanceof \dbObject\Event)) {
				return true;
			}

			if ($this->canUserAccessPvBeforeValidation($userId)) {
				$stage = $this->getPvStage();
				return $stage === self::PV_STAGE_REVIEW || $stage === self::PV_STAGE_VALIDATED;
			}

			return $this->isPvValidated()
				&& $this->hasAssociatedEventStarted($referenceDate);
		}

		public function hasAssociatedEventStarted(?\DateTimeInterface $referenceDate = null): bool
		{
			$event = $this->getAssociatedEvent();
			$startAt = $event instanceof \dbObject\Event ? $event->get('start_at') : null;
			if (!($startAt instanceof \DateTimeInterface)) {
				return false;
			}

			if (!($referenceDate instanceof \DateTimeInterface)) {
				$timezone = $startAt->getTimezone();
				$referenceDate = $timezone instanceof \DateTimeZone
					? new \DateTimeImmutable('now', $timezone)
					: new \DateTimeImmutable('now');
			}

			return $startAt <= $referenceDate;
		}

		public function canUserPassPvMeetingVisibilityGate(int $userId, int $organizationId = 0, ?\DateTimeInterface $referenceDate = null): bool
		{
			if (!$this->isPvDocument()) {
				return true;
			}

			$organizationId = $organizationId > 0 ? (int)$organizationId : (int)$this->get('IDorganization');
			if ($organizationId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return false;
			}

			if (!($this->getAssociatedEvent() instanceof \dbObject\Event)) {
				return true;
			}

			return $this->canUserAccessPvBeforeValidation($userId, $organizationId)
				|| ($this->isPvValidated() && $this->hasAssociatedEventStarted($referenceDate));
		}

		public function canUserAccessPvBeforeValidation(int $userId, int $organizationId = 0): bool
		{
			$organizationId = $organizationId > 0 ? (int)$organizationId : (int)$this->get('IDorganization');
			if (
				!$this->isPvDocument()
				|| $userId <= 0
				|| $organizationId <= 0
				|| (int)$this->get('IDorganization') !== $organizationId
			) {
				return false;
			}

			if ($this->isPvCreatorOrEditor($userId)) {
				return true;
			}

			$event = $this->getAssociatedEvent();
			if ($event instanceof \dbObject\Event) {
				return $event->isVisibleToInvitationViewer($userId, $organizationId);
			}
			if ($this->hasExplicitInvitations()) {
				return $this->isUserInvited($userId, $organizationId);
			}

			return $this->canViewDirectlyInOrganization($organizationId);
		}

		public function getPvPermissionHolon(int $organizationId = 0)
		{
			$organizationId = $organizationId > 0 ? (int)$organizationId : (int)$this->get('IDorganization');
			if ($organizationId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return null;
			}

			$holonId = $this->getPvContextHolonId();

			return self::resolveCreationPermissionHolon($organizationId, $holonId > 0 ? $holonId : null);
		}

		public function getPvContextHolonId(): int
		{
			if (!$this->isPvDocument()) {
				return 0;
			}

			$holonId = (int)$this->get('IDholon');
			if ($holonId > 0) {
				return $holonId;
			}

			$event = $this->getAssociatedEvent();
			return $event instanceof \dbObject\Event ? (int)$event->get('IDholon') : 0;
		}

		public function canUserClaimPvEditor(int $organizationId, int $userId): bool
		{
			if (!$this->isPvDocument() || $this->isPvValidated() || $userId <= 0) {
				return false;
			}

			if ($this->getPvContextHolonId() <= 0 && $userId === $this->getCreatedByUserId()) {
				return true;
			}

			$permissionHolon = $this->getPvPermissionHolon($organizationId);
			if (!($permissionHolon instanceof \dbObject\Holon)) {
				return false;
			}

			return (int)$permissionHolon->getId() > 0
				&& $permissionHolon->isAllowed('CAN_CLAIM_PV', false, $userId);
		}

		public function canUserReplacePvEditor(int $organizationId, int $userId): bool
		{
			if (
				!$this->isPvDocument()
				|| $this->isPvValidated()
				|| $userId <= 0
				|| $this->getPvEditorUserId() <= 0
				|| $this->isPvEditor($userId)
				|| !$this->isPvEditorHandoverOpen()
			) {
				return false;
			}

			if ($this->canUserClaimPvEditor($organizationId, $userId)) {
				return false;
			}

			$event = $this->getAssociatedEvent();
			if ($event instanceof \dbObject\Event) {
				return $event->isVisibleToInvitationViewer($userId, $organizationId);
			}

			return $this->hasExplicitInvitations() && $this->isUserInvited($userId, $organizationId);
		}

		public function claimPvEditor(int $organizationId, int $userId): array
		{
			if (!$this->canUserClaimPvEditor($organizationId, $userId)) {
				return array('status' => false, 'text' => 'Acces refuse.');
			}

			$this->set('IDuser_pv_editor', $userId);
			$this->set('pv_editor_handover_open', 0);
			$this->set('IDusermodification', $userId);
			$this->set('datemodification', new \DateTimeImmutable());
			$saveResult = $this->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return array('status' => false, 'text' => 'Impossible de devenir editeur du PV.');
			}

			$this->load((int)$this->getId());
			if (!$this->isPvEditor($userId)) {
				return array('status' => false, 'text' => 'Le PV est deja attribue a une autre personne.');
			}

			return array('status' => true);
		}

		public function openPvEditorHandover(int $userId): array
		{
			if (!$this->isPvEditor($userId) || $this->isPvValidated()) {
				return array('status' => false, 'text' => 'Acces refuse.');
			}

			$this->set('pv_editor_handover_open', 1);
			$this->set('IDusermodification', $userId);
			$this->set('datemodification', new \DateTimeImmutable());
			$saveResult = $this->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return array('status' => false, 'text' => 'Impossible de passer la main pour ce PV.');
			}

			$this->load((int)$this->getId());
			return array('status' => $this->isPvEditorHandoverOpen());
		}

		public function replacePvEditor(int $organizationId, int $userId): array
		{
			if (!$this->canUserReplacePvEditor($organizationId, $userId)) {
				return array('status' => false, 'text' => 'Acces refuse.');
			}

			$this->set('IDuser_pv_editor', $userId);
			$this->set('pv_editor_handover_open', 0);
			$this->set('IDusermodification', $userId);
			$this->set('datemodification', new \DateTimeImmutable());
			$saveResult = $this->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return array('status' => false, 'text' => 'Impossible de remplacer l editeur du PV.');
			}

			$this->load((int)$this->getId());
			return array('status' => $this->isPvEditor($userId));
		}

		public function canUserEditPvPoint(\dbObject\DocumentPvPoint $point, int $userId): bool
		{
			if (!$this->isPvDocument() || $this->isPvValidated() || $point->isHandled() || (int)$point->get('IDdocument') !== (int)$this->getId()) {
				return false;
			}

			return $this->canUserManagePvDocument($userId) || $point->isEditableByUser($userId);
		}

		public function canUserReorderPvPoints(int $userId): bool
		{
			return $this->isPvDocument() && !$this->isPvValidated() && (
				$this->canUserManagePvDocument($userId)
				|| $this->getPvStage() === self::PV_STAGE_PREPARATION
			);
		}

		public function canUserReorderPvItem(\dbObject\DocumentPvPoint $item, int $userId): bool
		{
			if (!$this->canUserReorderPvPoints($userId) || (int)$item->get('IDdocument') !== (int)$this->getId()) {
				return false;
			}

			return $this->canUserManagePvDocument($userId)
				|| (!$item->isGroup() && $this->getPvStage() === self::PV_STAGE_PREPARATION && $item->isEditableByUser($userId));
		}

		public function canUserCreatePvGroups(int $userId): bool
		{
			return !$this->isPvValidated() && $this->canUserManagePvDocument($userId);
		}

		public function getInvitations(bool $activeOnly = false)
		{
			$items = new \dbObject\ArrayDocumentInvitation();
			$params = ['where' => [
				['field' => 'resource_type', 'value' => \dbObject\DocumentInvitation::resourceType()],
				['field' => 'resource_id', 'value' => (int)$this->getId()],
			]];
			if ($activeOnly) {
				$params['where'][] = ['field' => 'active', 'value' => 1];
			}
			$items->load($params);
			return $items;
		}

		public function getInvitationEntries(int $organizationId = 0): array
		{
			$organizationId = $organizationId > 0 ? $organizationId : (int)$this->get('IDorganization');
			$entries = [];
			foreach ($this->getInvitations(true) as $invitation) {
				if (!($invitation instanceof \dbObject\DocumentInvitation) || \dbObject\DocumentInvitation::normalizeStatus($invitation->get('status')) === \dbObject\DocumentInvitation::STATUS_REVOKED) {
					continue;
				}
				$type = \dbObject\DocumentInvitation::normalizeType($invitation->get('invitation_type'));
				if ($type === \dbObject\DocumentInvitation::TYPE_HOLON) {
					$holon = new \dbObject\Holon();
					if ($holon->load((int)$invitation->get('IDholon'))) {
						foreach ($holon->getAssociatedMemberUserIds(['organizationId' => $organizationId, 'skipPermissionFilter' => true]) as $userId) {
							$entries['user:' . (int)$userId] = ['userId' => (int)$userId, 'email' => '', 'displayLabel' => '', 'identityKey' => 'user:' . (int)$userId, 'accepted' => $invitation->get('accepted')];
						}
					}
					continue;
				}
				if ($type === \dbObject\DocumentInvitation::TYPE_USER) {
					$userId = (int)$invitation->get('IDuser');
					if ($userId > 0) {
						$entries['user:' . $userId] = ['userId' => $userId, 'email' => '', 'displayLabel' => trim((string)$invitation->get('display_name')), 'identityKey' => 'user:' . $userId, 'accepted' => $invitation->get('accepted')];
					}
					continue;
				}
				$email = trim(mb_strtolower((string)$invitation->get('email'), 'UTF-8'));
				if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$entries['email:' . $email] = ['userId' => 0, 'email' => $email, 'displayLabel' => trim((string)$invitation->get('display_name')), 'identityKey' => 'email:' . $email, 'accepted' => $invitation->get('accepted')];
				}
			}
			foreach ($entries as &$entry) {
				if ((int)$entry['userId'] > 0 && trim((string)$entry['displayLabel']) === '') {
					$entry['displayLabel'] = \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization((int)$entry['userId'], $organizationId);
				}
				if (trim((string)$entry['displayLabel']) === '') {
					$entry['displayLabel'] = (string)$entry['email'];
				}
			}
			unset($entry);
			return array_values($entries);
		}

		public function hasExplicitInvitations(): bool
		{
			foreach ($this->getInvitations(true) as $invitation) {
				if (
					$invitation instanceof \dbObject\DocumentInvitation
					&& \dbObject\DocumentInvitation::normalizeStatus($invitation->get('status')) !== \dbObject\DocumentInvitation::STATUS_REVOKED
				) {
					return true;
				}
			}
			return false;
		}

		public function isUserInvited(int $userId, int $organizationId = 0): bool
		{
			if ($userId <= 0) {
				return false;
			}
			$organizationId = $organizationId > 0 ? $organizationId : (int)$this->get('IDorganization');
			$userEmails = array();
			$user = new \dbObject\User();
			if ($user->load($userId)) {
				$email = trim(mb_strtolower((string)$user->get('email'), 'UTF-8'));
				if ($email !== '') {
					$userEmails[$email] = true;
				}
			}
			$membership = new \dbObject\UserOrganization();
			if ($membership->load([['IDorganization', $organizationId], ['IDuser', $userId]])) {
				$email = trim(mb_strtolower((string)$membership->get('email'), 'UTF-8'));
				if ($email !== '') {
					$userEmails[$email] = true;
				}
			}
			foreach ($this->getInvitationEntries($organizationId) as $entry) {
				if ((int)($entry['userId'] ?? 0) === $userId) {
					return true;
				}
				$email = trim(mb_strtolower((string)($entry['email'] ?? ''), 'UTF-8'));
				if ($email !== '' && isset($userEmails[$email])) {
					return true;
				}
			}
			return false;
		}

		public function getInvitationAttendanceEntries(int $organizationId = 0): array
		{
			$entries = $this->getInvitationEntries($organizationId);
			foreach ($entries as &$entry) {
				$attendance = new \dbObject\DocumentAttendance();
				$conditions = [
					['resource_type', \dbObject\DocumentAttendance::resourceType()],
					['resource_id', (int)$this->getId()],
				];
				$conditions[] = (int)($entry['userId'] ?? 0) > 0
					? ['IDuser', (int)$entry['userId']]
					: ['email', (string)($entry['email'] ?? '')];
				$loaded = $attendance->load($conditions);
				$entry['isPresent'] = $loaded ? !empty($attendance->get('is_present')) : $entry['accepted'] === 1 || $entry['accepted'] === '1';
			}
			unset($entry);
			return $entries;
		}

		public function setInvitationAttendance(int $organizationId, int $checkedByUserId, string $identityKey, bool $isPresent): array
		{
			foreach ($this->getInvitationEntries($organizationId) as $entry) {
				if ((string)($entry['identityKey'] ?? '') !== $identityKey) {
					continue;
				}
				$userId = (int)($entry['userId'] ?? 0);
				$attendance = new \dbObject\DocumentAttendance();
				$conditions = [
					['resource_type', \dbObject\DocumentAttendance::resourceType()],
					['resource_id', (int)$this->getId()],
					$userId > 0 ? ['IDuser', $userId] : ['email', (string)($entry['email'] ?? '')],
				];
				if (!$attendance->load($conditions)) {
					$attendance->set('IDdocument', (int)$this->getId());
					$attendance->set('IDuser', $userId > 0 ? $userId : null);
					$attendance->set('email', $userId > 0 ? null : (string)($entry['email'] ?? ''));
				}
				$attendance->set('display_name', (string)($entry['displayLabel'] ?? ''));
				$attendance->set('is_present', $isPresent ? 1 : 0);
				$attendance->set('IDuser_checked_by', $checkedByUserId);
				$attendance->set('checked_at', new \DateTimeImmutable());
				$attendance->set('active', 1);
				$saveResult = $attendance->save();
				if (is_array($saveResult) && !empty($saveResult['status'])) {
					foreach ($this->getInvitations(true) as $invitation) {
						if ($invitation instanceof \dbObject\DocumentInvitation && $invitation->getIdentityKey() === $identityKey) {
							$invitation->set('accepted', $isPresent ? 1 : 0);
							$invitation->save();
							break;
						}
					}
				}
				return $saveResult;
			}
			return ['status' => false, 'text' => 'Participant introuvable.'];
		}

		public function getPvPointAuthorOptions(int $organizationId = 0): array
		{
			$organizationId = $organizationId > 0 ? (int)$organizationId : (int)$this->get('IDorganization');
			if (!$this->isPvDocument() || $organizationId <= 0) {
				return array();
			}

			$userIds = array();
			$externalAuthors = array();
			$event = $this->getAssociatedEvent();
			if ($event instanceof \dbObject\Event) {
				foreach ($event->getAttendanceEntries($organizationId) as $entry) {
					$userId = (int)($entry['userId'] ?? 0);
					if ($userId > 0) {
						$userIds[$userId] = $userId;
						continue;
					}

					$email = trim(mb_strtolower((string)($entry['email'] ?? ''), 'UTF-8'));
					if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$label = trim((string)($entry['displayLabel'] ?? ''));
						$externalAuthors[$email] = $label !== '' && $label !== $email
							? ($label . ' (' . $email . ')')
							: $email;
					}
				}
			} elseif (count($this->getInvitationEntries($organizationId)) > 0) {
				foreach ($this->getInvitationEntries($organizationId) as $entry) {
					$userId = (int)($entry['userId'] ?? 0);
					if ($userId > 0) {
						$userIds[$userId] = $userId;
						continue;
					}
					$email = trim(mb_strtolower((string)($entry['email'] ?? ''), 'UTF-8'));
					if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$externalAuthors[$email] = trim((string)($entry['displayLabel'] ?? '')) ?: $email;
					}
				}
			} else {
				$memberships = new \dbObject\ArrayUserOrganization();
				$memberships->loadActiveForOrganization($organizationId);
				foreach ($memberships as $membership) {
					if ($membership instanceof \dbObject\UserOrganization) {
						$userId = (int)$membership->get('IDuser');
						if ($userId > 0) {
							$userIds[$userId] = $userId;
						}
					}
				}
			}

			$authorRows = self::fetchAll(
				'SELECT DISTINCT IDuser_author, author_email FROM document_pv_point WHERE IDdocument = :document_id',
				array('document_id' => (int)$this->getId())
			);
			foreach ((array)$authorRows as $row) {
				$userId = (int)($row['IDuser_author'] ?? 0);
				if ($userId > 0) {
					$userIds[$userId] = $userId;
				}

				$email = trim(mb_strtolower((string)($row['author_email'] ?? ''), 'UTF-8'));
				if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($externalAuthors[$email])) {
					$externalAuthors[$email] = $email;
				}
			}

			$creatorId = (int)$this->get('IDuser');
			if ($creatorId > 0) {
				$userIds[$creatorId] = $creatorId;
			}

			$options = array();
			foreach ($userIds as $userId) {
				$label = \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($userId, $organizationId);
				if ($label !== '') {
					$options[] = array(
						'value' => 'user:' . $userId,
						'userId' => $userId,
						'email' => '',
						'label' => $label,
					);
				}
			}

			foreach ($externalAuthors as $email => $label) {
				$options[] = array(
					'value' => 'email:' . $email,
					'userId' => 0,
					'email' => $email,
					'label' => $label,
				);
			}

			usort($options, static function (array $left, array $right): int {
				return strcasecmp((string)$left['label'], (string)$right['label']);
			});
			return $options;
		}

		public function canUserPrepareUpcomingPv(int $userId = 0, int $organizationId = 0): bool
		{
			$userId = (int)$userId;
			$organizationId = $organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');

			if (
				!$this->isPvDocument()
				|| $userId <= 0
				|| $organizationId <= 0
				|| (int)$this->get('IDorganization') !== $organizationId
			) {
				return false;
			}

			$event = $this->getAssociatedEvent();
			if (!($event instanceof \dbObject\Event) || !$event->isUpcoming()) {
				return false;
			}

			if ($this->canEditInOrganizationContext($organizationId, $userId, false)) {
				return true;
			}

			return $event->canUserPrepareUpcomingPv($userId, $organizationId);
		}

		public function canUserOpenPvEditor(int $userId = 0, int $organizationId = 0): bool
		{
			$userId = (int)$userId;
			$organizationId = $organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');

			if (
				!$this->isPvDocument()
				|| $userId <= 0
				|| $organizationId <= 0
				|| (int)$this->get('IDorganization') !== $organizationId
			) {
				return false;
			}

			if ($this->isPvValidated()) {
				return false;
			}

			$event = $this->getAssociatedEvent();
			if ($event instanceof \dbObject\Event && !$this->canUserAccessPvBeforeValidation($userId, $organizationId)) {
				return false;
			}

			if ($this->getPvStage() === self::PV_STAGE_REVIEW && !$this->canUserManagePvDocument($userId)) {
				return false;
			}

			if ($this->canUserManagePvDocument($userId) || $this->canEditInOrganizationContext($organizationId, $userId, false)) {
				return true;
			}

			if ($event instanceof \dbObject\Event) {
				return $this->canUserAccessPvBeforeValidation($userId, $organizationId);
			}
			if ($this->hasExplicitInvitations()) {
				return $this->isUserInvited($userId, $organizationId);
			}

			return $this->canViewDirectlyInOrganization($organizationId);
		}

		public function hasUpcomingAssociatedEvent(): bool
		{
			if (!$this->isPvDocument()) {
				return false;
			}

			$event = $this->getAssociatedEvent();
			return $event instanceof \dbObject\Event && $event->isUpcoming();
		}

		public function buildPvEditorUrl(int $organizationId = 0): string
		{
			$organizationId = $organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');

			if (!$this->isPvDocument() || (int)$this->getId() <= 0 || $organizationId <= 0) {
				return '';
			}

			return '/omo/api/documents/pv/editor.php?id='
				. rawurlencode((string)(int)$this->getId())
				. '&oid='
				. rawurlencode((string)$organizationId);
		}

		public function buildUpcomingPvEditorUrl(int $organizationId = 0): string
		{
			return $this->buildPvEditorUrl($organizationId);
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

			if ($documentType === self::TYPE_PV) {
				return self::TYPE_PV;
			}

			return self::TYPE_HTML;
		}

		public static function getPvStageOptions(): array
		{
			return [
				self::PV_STAGE_PREPARATION => 'Preparation',
				self::PV_STAGE_MEETING => 'Reunion',
				self::PV_STAGE_REVIEW => 'Relecture',
				self::PV_STAGE_VALIDATED => 'Valide',
			];
		}

		public static function normalizePvStage($rawStage): string
		{
			$stage = trim(mb_strtolower((string)$rawStage, 'UTF-8'));
			$options = self::getPvStageOptions();
			if (isset($options[$stage])) {
				return $stage;
			}

			return self::PV_STAGE_PREPARATION;
		}

		public function getPvStage(): string
		{
			if (!$this->isPvDocument()) {
				return '';
			}

			return self::normalizePvStage($this->get('pvstage'));
		}

		public function getPvStageLabel(): string
		{
			$stage = $this->getPvStage();
			$options = self::getPvStageOptions();
			return (string)($options[$stage] ?? $options[self::PV_STAGE_PREPARATION]);
		}

		public function isPvValidated(): bool
		{
			return $this->isPvDocument() && $this->getPvStage() === self::PV_STAGE_VALIDATED;
		}

		public function canManagePvStage(int $organizationId = 0, ?int $userId = null): bool
		{
			if (!$this->isPvDocument() || $this->isPvValidated()) {
				return false;
			}

			$organizationId = $organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');

			if ($organizationId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return false;
			}

			$resolvedUserId = $userId !== null
				? (int)$userId
				: (
					function_exists('commonGetCurrentUserId')
						? (int)\commonGetCurrentUserId()
						: (int)($_SESSION['currentUser'] ?? 0)
				);

			return $this->canUserManagePvDocument($resolvedUserId);
		}

		public function updatePvStageInOrganizationContext(int $organizationId, int $userId, string $stage): array
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;

			if ((int)$this->getId() <= 0 || !$this->isPvDocument()) {
				return [
					'status' => false,
					'text' => 'Document PV introuvable.',
				];
			}

			if (!$this->canManagePvStage($organizationId, $userId)) {
				return [
					'status' => false,
					'text' => 'Acces refuse.',
				];
			}

			$this->set('pvstage', self::normalizePvStage($stage));
			$this->set('IDusermodification', $userId);
			$this->set('datemodification', new \DateTimeImmutable());

			return $this->save();
		}

		public static function getDocumentTypeCatalog(): array
		{
			return array(
				self::TYPE_HTML => 'HTML',
				self::TYPE_EXTERNAL_LINK => 'Lien externe',
				self::TYPE_UPLOADED_FILE => 'Telechargement',
				self::TYPE_FOLDER => 'Dossier',
				self::TYPE_PV => 'PV',
			);
		}

		public function getDocumentType(): string
		{
			return self::normalizeDocumentType($this->get('documenttype'), $this->isFolder());
		}

		public function getDocumentTypeLabel(): string
		{
			$catalog = self::getDocumentTypeCatalog();
			$documentType = $this->getDocumentType();
			return (string)($catalog[$documentType] ?? $catalog[self::TYPE_HTML]);
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

		public function isPvDocument(): bool
		{
			return $this->getDocumentType() === self::TYPE_PV;
		}

		public function canBeEmbedded(): bool
		{
			return !$this->isFolder()
				&& (
					$this->supportsHtmlContent()
					|| $this->isExternalLink()
					|| $this->isUploadedFile()
				);
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

		protected static function isDecisionEmbedElement(\DOMElement $element): bool
		{
			if (trim((string)$element->getAttribute('data-omo-embed-type')) !== 'decision') {
				return false;
			}

			return (int)trim((string)$element->getAttribute('data-omo-decision-id')) > 0;
		}

		protected static function isEventEmbedElement(\DOMElement $element): bool
		{
			if (trim((string)$element->getAttribute('data-omo-embed-type')) !== 'event') {
				return false;
			}

			return (int)trim((string)$element->getAttribute('data-omo-event-id')) > 0;
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

			if (trim($title) !== '') {
				$documentUrl = '#documents-d' . (int)$documentId;
				$html .= '<div class="omo-document-embed__title-wrap">'
					. '<a class="omo-document-embed__title" href="' . htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') . '">'
					. htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8') . '</a>'
					. '<a class="omo-document-embed__external" href="' . htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" title="Ouvrir dans une nouvelle fenetre" aria-label="Ouvrir dans une nouvelle fenetre">&#8599;</a>'
					. '</div>';
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

		protected static function buildDecisionEmbedDisplayHtml(int $decisionId, string $title, string $type): string
		{
			$decisionUrl = '#decision-d' . $decisionId;
			$html = '<div class="omo-decision-embed" data-omo-embed-type="decision" data-omo-decision-id="' . $decisionId . '">';
			$html .= '<a class="omo-decision-embed__title" href="' . htmlspecialchars($decisionUrl, ENT_QUOTES, 'UTF-8') . '">'
				. htmlspecialchars($title !== '' ? $title : ('Decision #' . $decisionId), ENT_QUOTES, 'UTF-8')
				. '</a>';
			if ($type !== '') {
				$html .= '<div class="omo-decision-embed__type">' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '</div>';
			}
			return $html . '</div>';
		}

		protected static function buildEventEmbedDisplayHtml(int $eventId, string $title, string $schedule, string $description): string
		{
			$eventUrl = '#calendar-e' . $eventId;
			$summary = trim(implode(' - ', array_filter(array(trim($schedule), trim($description)))));
			$html = '<div class="omo-event-embed" data-omo-embed-type="event" data-omo-event-id="' . $eventId . '">';
			$html .= '<a class="omo-event-embed__title" href="' . htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') . '">'
				. htmlspecialchars($title !== '' ? $title : ('Evenement #' . $eventId), ENT_QUOTES, 'UTF-8')
				. '</a>';
			if ($summary !== '') {
				$html .= '<div class="omo-event-embed__summary">' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</div>';
			}
			return $html . '</div>';
		}

		protected function renderExternalLinkForViewer(): string
		{
			$externalUrl = $this->getExternalUrl();
			if ($externalUrl === '') {
				return '<div class="omo-document-external omo-document-external--invalid">Aucune URL n est encore definie pour ce document.</div>';
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

			$downloadUrl = '/omo/api/documents/upload/download.php?id=' . (int)$this->getId();
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

			if (self::isDecisionEmbedElement($node)) {
				return self::buildDecisionEmbedDisplayHtml(
					(int)$node->getAttribute('data-omo-decision-id'),
					trim((string)self::getDocumentEmbedAttributeValue($node, 'data-omo-decision-title')),
					trim((string)self::getDocumentEmbedAttributeValue($node, 'data-omo-decision-type'))
				);
			}

			if (self::isEventEmbedElement($node)) {
				return self::buildEventEmbedDisplayHtml(
					(int)$node->getAttribute('data-omo-event-id'),
					trim((string)self::getDocumentEmbedAttributeValue($node, 'data-omo-event-title')),
					trim((string)self::getDocumentEmbedAttributeValue($node, 'data-omo-event-schedule')),
					trim((string)self::getDocumentEmbedAttributeValue($node, 'data-omo-event-description'))
				);
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
			if ($this->isPvDocument()) {
				return $this->renderPvForViewer();
			}

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
			$renderedContent = '';
			$contentHashSource = '';

			if ($this->isPvDocument()) {
				$renderedContent = $this->renderPvForViewer();
				$contentHashSource = $renderedContent;
			} elseif ($this->isExternalLink()) {
				$renderedContent = $this->renderExternalLinkForViewer();
				$contentHashSource = implode('|', array(
					$this->getExternalUrl(),
					$this->shouldOpenExternalLinkInNewWindow() ? '1' : '0',
				));
			} elseif ($this->isUploadedFile()) {
				$renderedContent = $this->renderUploadedFileForViewer();
				$contentHashSource = implode('|', array(
					trim((string)$this->get('storedfilepath')),
					$this->getStoredFileDownloadName(),
					$this->getStoredFileMimeType(),
					(string)$this->getStoredFileSize(),
				));
			} else {
				$renderedContent = $this->renderResolvedHtmlForViewer($content, (int)$this->get('IDorganization'));
				$contentHashSource = $content;
			}

			$payload = array(
				'title' => trim((string)$this->get('title')),
				'description' => trim((string)$this->get('description')),
				'content' => $renderedContent,
				'isDraft' => !empty($snapshot['isDraft']),
				'editingUserName' => trim((string)($snapshot['editingUserName'] ?? '')),
				'updatedAt' => $updatedAt,
			);

			$payload['contentHash'] = sha1($contentHashSource);
			$payload['stateHash'] = self::buildLiveShareStateHash($payload);

			return $payload;
		}

		public function getPvPoints(bool $activeOnly = true)
		{
			$points = new \dbObject\ArrayDocumentPvPoint();
			$points->loadForDocument((int)$this->getId(), $activeOnly);
			return $points;
		}

		public function copyPvAgendaFromTemplate(\dbObject\Document $template, int $organizationId, int $userId): array
		{
			if (
				(int)$this->getId() <= 0
				|| !$this->isPvDocument()
				|| (int)$this->get('IDorganization') !== $organizationId
				|| !$template->canUseAsPvTemplate($organizationId)
			) {
				return array('status' => false, 'text' => 'Modele de PV invalide ou inaccessible.');
			}

			$sourceItems = $template->getPvPoints(true);
			$childrenByParent = array();
			foreach ($sourceItems as $sourceItem) {
				if (!($sourceItem instanceof \dbObject\DocumentPvPoint) || (int)$sourceItem->getId() <= 0) {
					continue;
				}
				$childrenByParent[max(0, (int)$sourceItem->get('IDparent'))][] = $sourceItem;
			}

			foreach ($childrenByParent as &$siblings) {
				usort($siblings, static function ($left, $right): int {
					$positionComparison = (int)$left->get('position') <=> (int)$right->get('position');
					return $positionComparison !== 0
						? $positionComparison
						: (int)$left->getId() <=> (int)$right->getId();
				});
			}
			unset($siblings);

			$copiedIds = array();
			$visitedIds = array();
			$copyChildren = function (int $sourceParentId, int $targetParentId = 0) use (&$copyChildren, &$copiedIds, &$visitedIds, &$childrenByParent, $userId): array {
				foreach ($childrenByParent[$sourceParentId] ?? array() as $sourceItem) {
					$sourceItemId = (int)$sourceItem->getId();
					if ($sourceItemId <= 0 || isset($visitedIds[$sourceItemId])) {
						continue;
					}
					$visitedIds[$sourceItemId] = true;

					$targetItem = new \dbObject\DocumentPvPoint();
					$targetItem->set('IDdocument', (int)$this->getId());
					$targetItem->set('item_type', $sourceItem->get('item_type'));
					$targetItem->set('IDparent', $targetParentId > 0 ? $targetParentId : null);
					$targetItem->set('title', $sourceItem->get('title'));
					$targetItem->set('content', $sourceItem->isGroup() ? '' : $sourceItem->get('content'));
					$targetItem->set('position', (int)$sourceItem->get('position'));
					$targetItem->set('desired_duration_minutes', $sourceItem->isGroup() ? null : $sourceItem->get('desired_duration_minutes'));
					$targetItem->set('actual_duration_minutes', null);
					$targetItem->set('pointtype', $sourceItem->get('pointtype'));
					$targetItem->set('IDuser_author', null);
					$targetItem->set('author_email', null);
					$targetItem->set('IDholon_concerned', null);
					$targetItem->set('IDuser_modification', $userId > 0 ? $userId : null);
					$targetItem->set('IDuser_editing', null);
					$targetItem->set('edit_lock_token', null);
					$targetItem->set('dateedition', null);
					$targetItem->set('is_handled', 0);
					$targetItem->set('active', 1);

					$saveResult = $targetItem->save();
					if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
						return is_array($saveResult)
							? $saveResult
							: array('status' => false, 'text' => 'Impossible de copier un element du modele.');
					}

					$copiedIds[$sourceItemId] = (int)$targetItem->getId();
					if ($sourceItem->isGroup()) {
						$childrenResult = $copyChildren($sourceItemId, (int)$targetItem->getId());
						if (($childrenResult['status'] ?? false) !== true) {
							return $childrenResult;
						}
					}
				}

				return array('status' => true);
			};

			$copyResult = $copyChildren(0, 0);
			if (($copyResult['status'] ?? false) !== true) {
				return $copyResult;
			}

			// Invalid legacy parent links must not make an otherwise usable template fail.
			foreach ($sourceItems as $sourceItem) {
				if (!($sourceItem instanceof \dbObject\DocumentPvPoint) || isset($visitedIds[(int)$sourceItem->getId()])) {
					continue;
				}
				$childrenByParent[0] = array($sourceItem);
				$copyResult = $copyChildren(0, 0);
				if (($copyResult['status'] ?? false) !== true) {
					return $copyResult;
				}
			}

			return array('status' => true, 'copiedCount' => count($copiedIds));
		}

		protected static function escapeViewerText($value): string
		{
			return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		}

		protected static function buildPvSummaryPill(string $label): string
		{
			$label = trim($label);
			if ($label === '') {
				return '';
			}

			return '<span class="omo-pill">' . self::escapeViewerText($label) . '</span>';
		}

		protected static function buildPvFieldHtml(string $label, string $value): string
		{
			$label = trim($label);
			$value = trim($value);
			if ($label === '' || $value === '') {
				return '';
			}

			return '<span class="omo-document-pv__field">'
				. '<strong>' . self::escapeViewerText($label) . '</strong>'
				. '<span>' . self::escapeViewerText($value) . '</span>'
				. '</span>';
		}

		protected static function buildPvChipGroupHtml(string $label, array $items, string $modifier = ''): string
		{
			$cleanItems = array();
			foreach ($items as $item) {
				if (!is_array($item)) {
					continue;
				}

				$itemLabel = trim((string)($item['label'] ?? ''));
				if ($itemLabel === '') {
					continue;
				}

				$cleanItems[] = $itemLabel;
			}

			if (count($cleanItems) === 0) {
				return '';
			}

			$modifierClass = trim($modifier) !== ''
				? ' omo-document-pv__chip-group--' . preg_replace('/[^a-z0-9_-]+/i', '-', trim($modifier))
				: '';
			$html = '<div class="omo-document-pv__chip-group' . $modifierClass . '">';
			$html .= '<span class="omo-document-pv__chip-group-label">' . self::escapeViewerText($label) . '</span>';
			$html .= '<div class="omo-document-pv__chip-list">';

			foreach ($cleanItems as $itemLabel) {
				$html .= '<span class="omo-document-pv__chip">' . self::escapeViewerText($itemLabel) . '</span>';
			}

			$html .= '</div></div>';

			return $html;
		}

		protected static function formatPvDurationLabel($minutes, string $suffix): string
		{
			if ($minutes === null || $minutes === '') {
				return '';
			}

			return max(0, (int)$minutes) . ' min ' . trim($suffix);
		}

		protected function renderPvForViewer(): string
		{
			if (!\dbObject\DocumentPvPoint::hasPointTable()) {
				return '<div class="omo-document-pv omo-document-pv--empty">'
					. '<p class="omo-document-pv__empty">La structure PV n est pas encore disponible dans cette base.</p>'
					. '</div>';
			}

			$organizationId = (int)$this->get('IDorganization');
			$points = $this->getPvPoints(true);
			$positionLabels = \dbObject\DocumentPvPoint::buildHierarchyPositionLabels($points);
			$itemsByParent = array();
			foreach ($points as $item) {
				if (!($item instanceof \dbObject\DocumentPvPoint)) {
					continue;
				}
				$itemsByParent[max(0, (int)$item->get('IDparent'))][] = $item;
			}
			foreach ($itemsByParent as &$siblings) {
				usort($siblings, static function ($left, $right): int {
					$positionComparison = (int)$left->get('position') <=> (int)$right->get('position');
					return $positionComparison !== 0 ? $positionComparison : (int)$left->getId() <=> (int)$right->getId();
				});
			}
			unset($siblings);
			$orderedItems = array();
			$visitedItemIds = array();
			$appendItems = function (int $parentId, int $depth) use (&$appendItems, &$orderedItems, &$visitedItemIds, $itemsByParent): void {
				foreach ($itemsByParent[$parentId] ?? array() as $item) {
					$itemId = (int)$item->getId();
					if ($itemId <= 0 || isset($visitedItemIds[$itemId])) {
						continue;
					}
					$visitedItemIds[$itemId] = true;
					$orderedItems[] = array('item' => $item, 'depth' => $depth);
					if ($item->isGroup()) {
						$appendItems($itemId, $depth + 1);
					}
				}
			};
			$appendItems(0, 0);
			$pointCount = 0;
			$totalDesiredMinutes = 0;
			$totalActualMinutes = 0;
			$hasDesiredMinutes = false;
			$hasActualMinutes = false;

			foreach ($points as $point) {
				if (!($point instanceof \dbObject\DocumentPvPoint)) {
					continue;
				}
				if ($point->isGroup()) {
					continue;
				}
				$pointCount += 1;

				$desiredMinutes = $point->getDurationMinutesValue('desired_duration_minutes');
				if ($desiredMinutes !== null) {
					$totalDesiredMinutes += $desiredMinutes;
					$hasDesiredMinutes = true;
				}

				$actualMinutes = $point->getDurationMinutesValue('actual_duration_minutes');
				if ($actualMinutes !== null) {
					$totalActualMinutes += $actualMinutes;
					$hasActualMinutes = true;
				}
			}

			$html = '<div class="omo-document-pv">';
			$html .= '<section class="omo-document-pv__summary">';
			$html .= '<div class="omo-document-pv__summary-pills">';
			$html .= self::buildPvSummaryPill($pointCount . ' point' . ($pointCount > 1 ? 's' : ''));

			if ($hasDesiredMinutes) {
				$html .= self::buildPvSummaryPill('Souhaite ' . $totalDesiredMinutes . ' min');
			}

			if ($hasActualMinutes) {
				$html .= self::buildPvSummaryPill('Reel ' . $totalActualMinutes . ' min');
			}

			$html .= '</div>';
			$html .= '</section>';

			if ($pointCount === 0) {
				$html .= '<p class="omo-document-pv__empty">Aucun point a l ordre du jour pour ce PV.</p>';
				$html .= '</div>';
				return $html;
			}

			$html .= '<div class="omo-document-pv__points">';

			foreach ($orderedItems as $orderedItem) {
				$point = $orderedItem['item'] ?? null;
				$depth = max(0, (int)($orderedItem['depth'] ?? 0));
				if (!($point instanceof \dbObject\DocumentPvPoint)) {
					continue;
				}

				if ($point->isGroup()) {
					$html .= '<section class="omo-document-pv__group" style="margin-left:' . min(120, $depth * 18) . 'px">'
						. '<h3 class="omo-document-pv__group-title"><span class="omo-document-pv__group-order">' . self::escapeViewerText((string)($positionLabels[(int)$point->getId()] ?? '')) . '</span>'
						. self::escapeViewerText((string)$point->get('title')) . '</h3>'
						. '</section>';
					continue;
				}

				$pointData = $point->buildViewerData($organizationId);
				$pointData['positionLabel'] = (string)($positionLabels[(int)$point->getId()] ?? '');
				$pointTypeClass = preg_replace('/[^a-z0-9_-]+/i', '-', (string)($pointData['pointType'] ?? 'information'));
				$pointTypeIconMap = array(
					'information' => '/omo/assets/images/documents/pv-point-type/information.png',
					'consultation' => '/omo/assets/images/documents/pv-point-type/consultation.png',
					'decision' => '/omo/assets/images/documents/pv-point-type/decision.png',
				);
				$pointTypeIcon = isset($pointTypeIconMap[$pointTypeClass])
					? $pointTypeIconMap[$pointTypeClass]
					: $pointTypeIconMap['information'];
				$pointFields = '';
				$pointFields .= self::buildPvFieldHtml('Auteur', (string)($pointData['authorLabel'] ?? ''));
				$pointFields .= self::buildPvFieldHtml('Holon concerne', (string)($pointData['concernedHolonLabel'] ?? ''));
				$pointChips = '';
				$pointChips .= self::buildPvChipGroupHtml('Holons adresses', (array)($pointData['addressedHolons'] ?? array()), 'holons');
				$pointChips .= self::buildPvChipGroupHtml('Tensions', (array)($pointData['tensions'] ?? array()), 'tensions');
				$desiredLabel = self::formatPvDurationLabel($pointData['desiredDurationMinutes'] ?? null, 'souhaites');
				$actualLabel = self::formatPvDurationLabel($pointData['actualDurationMinutes'] ?? null, 'reelles');

				$html .= '<article class="omo-document-pv__point" style="margin-left:' . min(120, $depth * 18) . 'px">';
				$html .= '<header class="omo-document-pv__point-head">';
				$html .= '<div class="omo-document-pv__point-order">' . self::escapeViewerText((string)($pointData['positionLabel'] ?? '')) . '</div>';
				$html .= '<div class="omo-document-pv__point-main">';
				$html .= '<div class="omo-document-pv__point-topline">';
				$html .= '<span class="omo-document-pv__point-type omo-document-pv__point-type--' . self::escapeViewerText($pointTypeClass) . '">'
					. '<img src="' . self::escapeViewerText($pointTypeIcon) . '" alt="" aria-hidden="true" class="omo-document-pv__point-type-icon">'
					. self::escapeViewerText((string)($pointData['pointTypeLabel'] ?? 'Information'))
					. '</span>';

				if ($desiredLabel !== '') {
					$html .= '<span class="omo-document-pv__point-duration">' . self::escapeViewerText($desiredLabel) . '</span>';
				}

				if ($actualLabel !== '') {
					$html .= '<span class="omo-document-pv__point-duration omo-document-pv__point-duration--actual">' . self::escapeViewerText($actualLabel) . '</span>';
				}

				$html .= '</div>';
				$html .= '<h3 class="omo-document-pv__point-title">' . self::escapeViewerText((string)($pointData['title'] ?? '')) . '</h3>';

				if ($pointFields !== '') {
					$html .= '<div class="omo-document-pv__point-fields">' . $pointFields . '</div>';
				}

				$html .= '</div>';
				$html .= '</header>';

				if ($pointChips !== '') {
					$html .= '<div class="omo-document-pv__point-chips">' . $pointChips . '</div>';
				}

				if (!empty($pointData['contentHtml'])) {
					$html .= '<div class="omo-document-pv__point-content prose">' . (string)$pointData['contentHtml'] . '</div>';
				}

				$html .= '</article>';
			}

			$html .= '</div>';
			$html .= '</div>';

			return $html;
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

		public function canViewDirectlyInOrganization(int $organizationId): bool
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return false;
			}

			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
			if ($this->isPvDocument() && $this->isPvCreatorOrEditor($currentUserId)) {
				return true;
			}

			if (!$this->currentViewerCanAccessVisibility($organizationId)) {
				return false;
			}

			foreach ($this->getParentFolderChain() as $parentFolder) {
				if (
					!($parentFolder instanceof \dbObject\Document)
					|| !$parentFolder->isFolder()
					|| (int)$parentFolder->get('IDorganization') !== $organizationId
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
			$allowEmptyTypePayload = !empty($values['allow_empty_type_payload']);
			$eventId = isset($values['event_id']) ? (int)$values['event_id'] : 0;
			$pvTemplateId = $documentType === self::TYPE_PV && isset($values['pv_template_id'])
				? max(0, (int)$values['pv_template_id'])
				: 0;
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
			if ($documentType === self::TYPE_EXTERNAL_LINK && $externalUrl === '' && !$allowEmptyTypePayload) {
				return array(
					'status' => false,
					'text' => "L URL externe est obligatoire.",
				);
			}
			if ($documentType === self::TYPE_UPLOADED_FILE && $uploadedFile === null && !$allowEmptyTypePayload) {
				return array(
					'status' => false,
					'text' => 'Un fichier est obligatoire pour ce type de document.',
				);
			}
			$keywords = trim((string)($values['keywords'] ?? ''));
			$visibilityType = $this->resolveScopeTypeInput(
				$values,
				'visibility_type',
				self::getDefaultVisibilityTypeForOrganization($organizationId),
				true
			);
			$editVisibilityType = $this->resolveScopeTypeInput(
				$values,
				'edit_visibility_type',
				self::getDefaultEditVisibilityTypeForOrganization($organizationId),
				true
			);
			$now = new \DateTimeImmutable();

			$this->set('title', $title);
			$this->set('description', $description);
			$this->set('content', $content);
			$this->set('contentedition', null);
			$this->set('datecontentedition', null);
			$this->set('keywords', $keywords);
			$this->set('estDossier', $isFolder ? 1 : 0);
			$this->set('documenttype', $documentType);
			$this->set('pvstage', $documentType === self::TYPE_PV ? self::normalizePvStage($values['pv_stage'] ?? null) : null);
			$this->set('is_template', 0);
			$this->set('externalurl', $externalUrl !== '' ? $externalUrl : null);
			$this->set('openinnewwindow', $openInNewWindow ? 1 : 0);
			$this->set('IDevent', $eventId > 0 ? $eventId : null);
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

				if ($eventId > 0) {
					$event = new \dbObject\Event();
					if (
						!$event->load($eventId)
						|| (int)$event->get('IDorganization') !== $organizationId
					) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}

						return array(
							'status' => false,
							'text' => 'Evenement associe introuvable.',
						);
					}
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

				if ($documentType === self::TYPE_PV && $this->canUserClaimPvEditor($organizationId, $userId)) {
					$this->set('IDuser_pv_editor', $userId);
					$pvEditorSaveResult = $this->save();
					if (!is_array($pvEditorSaveResult) || ($pvEditorSaveResult['status'] ?? false) !== true) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}
						return $pvEditorSaveResult;
					}
				}

				$visibilityType = $this->normalizeScopeTypeForCurrentContext(
					$visibilityType,
					\dbObject\ObjectVisibility::TYPE_ORGANIZATION
				);
				$editVisibilityType = $this->normalizeScopeTypeForCurrentContext(
					$editVisibilityType,
					self::getDefaultEditVisibilityType()
				);

				if ($documentType === self::TYPE_UPLOADED_FILE && $uploadedFile !== null) {
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

				$editVisibilitySaveResult = $this->saveEditVisibilityRule($editVisibilityType);
				if (!is_array($editVisibilitySaveResult) || ($editVisibilitySaveResult['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $editVisibilitySaveResult;
				}

				if ($pvTemplateId > 0) {
					$pvTemplate = new self();
					if (!$pvTemplate->load($pvTemplateId)) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}
						return array('status' => false, 'text' => 'Modele de PV introuvable.');
					}

					$templateCopyResult = $this->copyPvAgendaFromTemplate($pvTemplate, $organizationId, $userId);
					if (!is_array($templateCopyResult) || ($templateCopyResult['status'] ?? false) !== true) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}
						return is_array($templateCopyResult)
							? $templateCopyResult
							: array('status' => false, 'text' => 'Impossible de copier le modele de PV.');
					}
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

			$canManagePvDocument = $this->isPvDocument() && $this->canUserManagePvDocument($userId);
			if ($userId <= 0 || (!$canManagePvDocument && !$this->canEditInOrganizationContext($organizationId, $userId, false))) {
				return array(
					'status' => false,
					'text' => 'Acces refuse.',
				);
			}

			if ($this->isPvDocument() && !$canManagePvDocument) {
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
			$visibilityType = $this->resolveScopeTypeInput(
				$values,
				'visibility_type',
				self::getDefaultVisibilityTypeForOrganization($organizationId),
				false
			);
			$editVisibilityType = $this->resolveScopeTypeInput(
				$values,
				'edit_visibility_type',
				self::getDefaultEditVisibilityTypeForOrganization($organizationId),
				false
			);
			$now = new \DateTimeImmutable();

			$this->set('title', $title);
			$this->set('description', $description);
			$this->set('content', $content);
			$this->set('keywords', $keywords);
			$this->set('documenttype', $documentType);
			if ($documentType === self::TYPE_PV) {
				$this->set('pvstage', self::normalizePvStage($values['pv_stage'] ?? $this->get('pvstage')));
			} else {
				$this->set('pvstage', null);
			}
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

					$editVisibilitySaveResult = $this->saveEditVisibilityRule($editVisibilityType);
					if (!is_array($editVisibilitySaveResult) || ($editVisibilitySaveResult['status'] ?? false) !== true) {
						if ($startedTransaction && $pdo->inTransaction()) {
							$pdo->rollBack();
						}

						return $editVisibilitySaveResult;
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
			$editVisibilityRule = $this->getPrimaryEditVisibilityRuleRow();
			$editVisibilityType = \dbObject\ObjectVisibility::normalizeVisibilityType(
				$editVisibilityRule['visibility_type'] ?? self::getDefaultEditVisibilityType()
			);
			$editVisibilityNeedsHolonTarget = \dbObject\ObjectVisibility::requiresHolonTarget($editVisibilityType);
			$finalEditVisibilityType = $editVisibilityType;

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

			if ($editVisibilityNeedsHolonTarget) {
				$editVisibilitySaveResult = $this->saveEditVisibilityRule($editVisibilityType);
				if (!is_array($editVisibilitySaveResult) || ($editVisibilitySaveResult['status'] ?? false) !== true) {
					if (
						$editVisibilityType === \dbObject\ObjectVisibility::TYPE_ROLE
						&& $targetHolonId > 0
					) {
						$editVisibilitySaveResult = $this->saveEditVisibilityRule(\dbObject\ObjectVisibility::TYPE_CIRCLE);
						if (is_array($editVisibilitySaveResult) && ($editVisibilitySaveResult['status'] ?? false) === true) {
							$finalEditVisibilityType = \dbObject\ObjectVisibility::TYPE_CIRCLE;
						}
					}

					if (!is_array($editVisibilitySaveResult) || ($editVisibilitySaveResult['status'] ?? false) !== true) {
						$editVisibilitySaveResult = $this->saveEditVisibilityRule(self::getDefaultEditVisibilityType());
						$finalEditVisibilityType = self::getDefaultEditVisibilityType();
					}
				}

				if (!is_array($editVisibilitySaveResult) || ($editVisibilitySaveResult['status'] ?? false) !== true) {
					return $editVisibilitySaveResult;
				}
			}

			return array(
				'status' => true,
				'visibilityType' => $finalVisibilityType,
				'editVisibilityType' => $finalEditVisibilityType,
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

			if ($userId <= 0 || !$this->canMoveInOrganizationContext($organizationId, $userId)) {
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
				!self::canCreateInOrganizationContext(
					$organizationId,
					$resolvedTargetHolonId > 0 ? $resolvedTargetHolonId : null,
					$userId,
					$resolvedTargetParentDocumentId,
					false
				)
			) {
				return array(
					'status' => false,
					'text' => 'Acces refuse pour cette destination.',
				);
			}

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

			$previousVisibilityRule = $this->getPrimaryVisibilityRuleRow();
			$previousVisibilityType = \dbObject\ObjectVisibility::normalizeVisibilityType(
				$previousVisibilityRule['visibility_type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION
			);
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
				if ($finalVisibilityType !== $previousVisibilityType) {
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

		public function getPrimaryEditVisibilityRuleRow()
		{
			$organizationId = (int)$this->get('IDorganization');
			if ((int)$this->getId() <= 0 || $organizationId <= 0) {
				return \dbObject\ObjectVisibility::buildFallbackRuleData(
					$organizationId,
					self::getDefaultEditVisibilityType()
				);
			}

			$ruleRow = \dbObject\ObjectVisibility::loadActiveRuleRow(
				self::getEditVisibilityObjectType(),
				(int)$this->getId(),
				$organizationId
			);

			return is_array($ruleRow)
				? $ruleRow
				: \dbObject\ObjectVisibility::buildFallbackRuleData(
					$organizationId,
					self::getDefaultEditVisibilityType()
				);
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

		public function saveEditVisibilityRule($visibilityType, $targetHolonId = null)
		{
			$organizationId = (int)$this->get('IDorganization');
			if ((int)$this->getId() <= 0 || $organizationId <= 0) {
				return array(
					'status' => false,
					'text' => 'Contexte d edition invalide.',
				);
			}

			$resolvedTarget = $targetHolonId !== null
				? array('status' => true, 'holonId' => (int)$targetHolonId)
				: $this->resolveVisibilityTargetHolonId($visibilityType);
			if (($resolvedTarget['status'] ?? false) !== true) {
				return $resolvedTarget;
			}

			return \dbObject\ObjectVisibility::saveSingleRule(
				self::getEditVisibilityObjectType(),
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

		public function currentViewerCanAccessEditVisibility($organizationId = 0, $ruleRow = null): bool
		{
			$organizationId = (int)$organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');
			if ($organizationId <= 0) {
				return false;
			}

			$viewerContext = \dbObject\ObjectVisibility::buildCurrentViewerContext($organizationId);
			return \dbObject\ObjectVisibility::viewerCanAccessRule(
				is_array($ruleRow) ? $ruleRow : $this->getPrimaryEditVisibilityRuleRow(),
				$viewerContext,
				array(
					'organizationId' => $organizationId,
					'ownerUserId' => (int)$this->get('IDuser'),
				)
			);
		}

		public function getEditVisibilityDisplayData($organizationId = 0, $ruleRow = null): array
		{
			$organizationId = (int)$organizationId > 0
				? (int)$organizationId
				: (int)$this->get('IDorganization');

			return \dbObject\ObjectVisibility::buildDisplayData(
				is_array($ruleRow) ? $ruleRow : $this->getPrimaryEditVisibilityRuleRow(),
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
