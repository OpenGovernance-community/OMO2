<?php
	namespace dbObject;

	class document extends DbObject
	{
	    public static function tableName()
		{
			return 'document'; // Nom de la table correspondante
		}

		// Defini le contenu de la table
		public static function rules()
		{
			return [
				[['title'], 'required'],						// Champs obligatoires
				[['id', 'version'], 'integer'],				// Nombres entiers
				[['title', 'codeview', 'codeedit', 'keywords'], 'string'],	// Chaines de caractere
				[['description', 'content'], 'text'],			// Textes libres
				[['datecreation', 'datemodification'], 'datetime'],	// Date avec precision des heures
				[['IDuser', 'IDorganization', 'IDholon'], 'fk'],	// Cles etrangeres
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
				'keywords' => 'Mots cles',
				'IDuser' => 'Auteur',
				'IDorganization' => 'Organisation',
				'IDholon' => 'Holon',
				'datecreation' => 'Date de creation',
				'datemodification' => 'Date de modification',
				'version' => 'Version',
				'codeview' => 'Code d affichage',
				'codeedit' => 'Code d edition',
			];
		}

		// Ajoute un champ description, qui peut apparaitre sous forme de bulle d information ou en sous-titre
		public static function attributeDescriptions()
		{
			return [
				'title' => 'Titre affiche dans une liste de fichiers',
				'description' => 'Abstract du contenu du document',
				'content' => 'Formate en texte libre ou en HTML',
				'IDuser' => 'Createur du document',
				'IDorganization' => 'Organisation a laquelle le document est rattache',
				'IDholon' => 'Holon concerne si le document est specifique a un contexte local',
			];
		}

		// Defini les informations de taille pour le champ
		public static function attributeLength()
		{
			return [
				'title' => 100,									// Nombre de caracteres maximum
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
				&& $organizationId > 0
				&& $currentUserId === (int)$this->get('IDuser')
				&& (
					function_exists('commonUserHasOrganizationAccess')
						? \commonUserHasOrganizationAccess($currentUserId, $organizationId)
						: true
				);
		}

		public function canEditInOrganizationContext(int $organizationId): bool
		{
			return (int)$this->get('IDorganization') === (int)$organizationId
				&& $this->canEdit();
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
				&& $this->currentViewerCanAccessVisibility($organizationId);
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
			$content = \dbObject\PropertyFormat::sanitizeHtml((string)($values['content'] ?? ''));
			$keywords = trim((string)($values['keywords'] ?? ''));
			$visibilityType = (string)($values['visibility_type'] ?? \dbObject\ObjectVisibility::getDefaultVisibilityType());
			$now = new \DateTimeImmutable();

			$this->set('title', $title);
			$this->set('description', $description);
			$this->set('content', $content);
			$this->set('keywords', $keywords);
			$this->set('IDuser', $userId);
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

			try {
				if ($startedTransaction) {
					$pdo->beginTransaction();
				}

				$contextSaveResult = $this->assignOrganizationContext($organizationId, $holonId);
				if (!is_array($contextSaveResult) || ($contextSaveResult['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $contextSaveResult;
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

			if ((int)$this->getId() <= 0 || (int)$this->get('IDorganization') !== $organizationId) {
				return array(
					'status' => false,
					'text' => 'Document introuvable.',
				);
			}

			if ($userId <= 0 || $userId !== (int)$this->get('IDuser') || !$this->canEditInOrganizationContext($organizationId)) {
				return array(
					'status' => false,
					'text' => 'Acces refuse.',
				);
			}

			$title = trim((string)($values['title'] ?? ''));
			if ($title === '') {
				return array(
					'status' => false,
					'text' => 'Le titre est obligatoire.',
				);
			}

			$description = trim((string)($values['description'] ?? ''));
			$content = \dbObject\PropertyFormat::sanitizeHtml((string)($values['content'] ?? ''));
			$keywords = trim((string)($values['keywords'] ?? ''));
			$visibilityType = (string)($values['visibility_type'] ?? \dbObject\ObjectVisibility::getDefaultVisibilityType());
			$now = new \DateTimeImmutable();

			$this->set('title', $title);
			$this->set('description', $description);
			$this->set('content', $content);
			$this->set('keywords', $keywords);
			$this->set('datemodification', $now);

			$pdo = self::getPdo();
			$startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();

			try {
				if ($startedTransaction) {
					$pdo->beginTransaction();
				}

				$saveResult = $this->save();
				if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->rollBack();
					}

					return $saveResult;
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
