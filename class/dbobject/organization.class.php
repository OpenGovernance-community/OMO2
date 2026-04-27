<?php
	namespace dbObject;


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
				[['name','shortname','domain','color'], 'string'],	// Chaines de caractere
				[['logo','banner'], 'sizedimage'],						// Images
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
				'logo' => 'Logo',
				'banner' => 'Banniere',
				'color' => 'Couleur',
			];
		}

		public static function attributeDescriptions() {
			return [
				'name' => 'Nom complet de l\'organisation',
				'shortname' => 'Nom abrege utilise dans l\'interface',
				'domain' => 'Nom de domaine principal de l\'organisation',
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
				'logo' => [[500, 500],[180,180]],
				'banner' => [[960, 540],[480, 270]],
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
					'color' => trim((string)$holon->get('color')),
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
			$rootHolon->set('unique', false);
			$rootHolon->set('link', false);
			$rootHolon->set('color', $sourceTemplate ? ($sourceTemplate->get('color') ?: null) : null);
			$rootHolon->set('accesskey', null);
			$rootHolon->save();

			if ((int)$rootHolon->getId() <= 0) {
				return null;
			}

			$rootHolon->set('IDholon_org', (int)$rootHolon->getId());
			$rootHolon->save();

			return $rootHolon;
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

		protected function cloneStructuralPropertyValue(\dbObject\Property $property, $rawValue, array $holonIdMap)
		{
			if ((string)$property->get('listitemtype') !== \dbObject\Property::LIST_ITEM_HOLON) {
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
				$targetChild->set('unique', (bool)$sourceChild->get('unique'));
				$targetChild->set('link', (bool)$sourceChild->get('link'));
				$targetChild->set('color', $sourceChild->get('color') ?: null);
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

			if ((int)$holonObject->get('IDorganization') === (int)$this->getId()) {
				return true;
			}

			$rootHolon = $this->getStructuralRootHolon();
			if (!$rootHolon) {
				return false;
			}

			return $holonObject->isDescendantOf($rootHolon, true);
		}

		public function getTemplateContextHolon($contextHolonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
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

		public function getHolonTemplateEditorData($contextHolonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$contextHolon = $this->getTemplateContextHolon($contextHolonId);
			if (!$rootHolon) {
				return array(
					'organizationId' => (int)$this->getId(),
					'organizationName' => (string)$this->get('name'),
					'rootHolonId' => 0,
					'contextHolonId' => 0,
					'contextHolonName' => '',
					'contextHolonLabel' => '',
					'types' => array(),
					'formats' => array(),
					'listItemTypes' => \dbObject\Property::getListItemTypeOptions(),
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
				'rootHolonId' => (int)$rootHolon->getId(),
				'contextHolonId' => $contextHolon ? (int)$contextHolon->getId() : 0,
				'contextHolonName' => $contextHolon ? $contextHolon->getDisplayName() : '',
				'contextHolonLabel' => $contextHolon ? $contextHolon->getTemplateLabel() : '',
				'types' => array(),
				'formats' => array(),
				'listItemTypes' => \dbObject\Property::getListItemTypeOptions(),
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

			foreach ($formats as $format) {
				$data['formats'][] = array(
					'id' => (int)$format->getId(),
					'name' => (string)$format->get('name'),
				);
			}

			$templateNodes = array();
			$childrenByParent = array();
			foreach ($this->getAvailableTemplateDefinitionHolons($contextHolon ? (int)$contextHolon->getId() : 0) as $template) {
				$definitionHolon = new \dbObject\Holon();
				$definitionHolonName = '';
				$definitionHolonLabel = '';
				if ($definitionHolon->load((int)$template->get('IDholon_parent'))) {
					$definitionHolonName = $definitionHolon->getDisplayName();
					$definitionHolonLabel = $definitionHolon->getTemplateLabel();
				}

				$data['templateCatalog'][] = array(
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
					'definedInId' => (int)$template->get('IDholon_parent'),
					'definedInName' => $definitionHolonName,
					'definedInLabel' => $definitionHolonLabel,
					'properties' => $template->getTemplatePropertyDefinitions(),
				);
			}

			foreach ($this->getTemplateDefinitionHolons($contextHolon ? (int)$contextHolon->getId() : 0) as $template) {
				$templateNode = $template->toTemplateEditorNodeArray((int)$rootHolon->getId());
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
			$child->set('unique', false);
			$child->set('link', false);
			$child->set('color', null);
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

				$data['templateCatalog'][] = array(
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
				);

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
				$data['holon'] = array(
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
				);
			}

			return $data;
		}

		// Enregistre holon edite
		public function saveHolonEditorDefinition(array $payload, $userId = 0, $contextHolonId = 0, $holonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$holonId = (int)$holonId;
			$isEditing = $holonId > 0;
			$isTemplateEditing = false;
			$holon = null;
			$contextHolon = null;

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

					$localValue = $submittedValuesByPropertyId[$propertyId] ?? '';
					$localValue = is_scalar($localValue) ? (string)$localValue : '';
					$inheritedValue = (string)($definition['inheritedValue'] ?? '');
					$effectiveValue = '';

					if ((int)($definition['formatId'] ?? 0) === \dbObject\PropertyFormat::FORMAT_LIST) {
						$effectiveItems = !empty($definition['effectiveLocked'])
							? $parseListValue($inheritedValue)
							: array_values(array_unique(array_merge($parseListValue($inheritedValue), $parseListValue($localValue)), SORT_REGULAR));
						$effectiveValue = count($effectiveItems) > 0 ? json_encode($effectiveItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
					} elseif (!empty($definition['effectiveLocked'])) {
						$effectiveValue = trim($inheritedValue);
					} else {
						$effectiveValue = trim($localValue) !== '' ? trim($localValue) : trim($inheritedValue);
					}

					if (!empty($definition['effectiveMandatory']) && $effectiveValue === '') {
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
			$holon->set('unique', $isTemplateEditing ? !empty($payload['unique']) : false);
			$holon->set('link', $isTemplateEditing ? !empty($payload['link']) : false);
			$color = trim((string)($payload['color'] ?? ''));
			$holon->set('color', $color !== '' ? $color : null);
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

		public function saveHolonTemplateDefinition(array $payload, $userId = 0, $contextHolonId = 0)
		{
			$rootHolon = $this->getStructuralRootHolon();
			$contextHolon = $this->getTemplateContextHolon($contextHolonId);
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
			$template->set('unique', !empty($payload['unique']));
			$template->set('link', !empty($payload['link']));
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

			return array(
				'status' => true,
				'message' => 'Modele enregistre.',
				'template' => $template->toTemplateEditorArray((int)$rootHolon->getId()),
				'data' => $this->getHolonTemplateEditorData((int)$contextHolon->getId()),
			);
		}

		public function getApplications($userId = null)
		{
			$applications = new \dbObject\ArrayApplication();
			$applications->loadEnabledForOrganization((int)$this->getId(), $userId !== null ? (int)$userId : 0);
			return $applications;
		}
		
	}
	
?>
