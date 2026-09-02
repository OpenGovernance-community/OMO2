<?php
	namespace dbObject;


	class Holon extends DbObject
	{
	    public static function tableName()
		{
			return 'holon'; // Nom de la table correspondante
		}	
		
		// Defini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id', 'admin_min', 'admin_max'], 'integer'],
				[['name','nomcomplet','templatename','accesskey'], 'string'],			// Texte libre
				[['icon','banner'], 'sizedimage'],			// Images illustratives
				[['datecreation','datemodification'], 'datetime'],	// Date avec precision des heures
				[['IDuser','IDtypeholon','IDholon_parent','IDholon_template','IDorganization','IDholon_org'], 'fk'],				// Cle etrangeres
				[['lockedname','lockedicon','lockedbanner','lockedadminmin','lockedadminmax','adminminoverride','adminmaxoverride','active','visible','mandatory','unique','link','adminparent'], 'boolean'],				// Cle etrangeres
				[['color'], 'color'],				// Couleur au format hexadecimal
				[['parameters'], 'parameters'],
				[['id'], 'safe'],								// Champs proteges (n'apparaissent pas dans les formulaires)
			];
		}
		
		// Defini les labels standarts pour cet objet, affiches dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'nomcomplet' => 'Nom complet',
				'IDholon_org' => 'Organisation',
				'IDuser' => 'Createur et administrateur',
				'datecreation' => 'Date de creation',
				'datemodification' => 'Date de modification',
				'active' => 'Actif ?',
				'visible' => 'Visible ?',
				'color' => 'Couleur',
				'templatename' => 'Nom de template',
				'IDorganization' => 'Organisation',
				'IDtypeholon' => 'Type de holon',
				'IDholon_parent' => 'Parent',
				'IDholon_template' => 'Template',
				'icon' => 'Icône',
				'banner' => 'Bannière',
				'accesskey' => 'Cle acces',
				'parameters' => 'Parametres',
				'mandatory' => 'Obligatoire ?',
				'lockedname' => 'Nom verrouille ?',
				'lockedicon' => 'Icône verrouillée ?',
				'lockedbanner' => 'Bannière verrouillée ?',
				'unique' => 'Unique ?',
				'link' => 'Lien ?',
				'adminparent' => 'Admin parent ?',
				'admin_min' => 'Nombre minimum d admins',
				'admin_max' => 'Nombre maximum d admins',
				'lockedadminmin' => 'Minimum d admins verrouille ?',
				'lockedadminmax' => 'Maximum d admins verrouille ?',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'name' => 'Nom court utilise dans la representation graphique, les chemins et les choix de contexte.',
				'nomcomplet' => 'Nom complet facultatif utilise dans les vues textuelles.',
				'parameters' => 'Parametres techniques du holon.',
			];
		}

		public static function attributeLength()
		{
			return [
				'nomcomplet' => 255,
				'icon' => [[320, 320], [160, 160]],
				'banner' => [[960, 540], [480, 270]],
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
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

		public function getDashboardDefaultLayout(): ?array
		{
			$parameters = $this->getParametersArray();
			if (!array_key_exists(UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER, $parameters)) {
				return null;
			}

			return UserHolon::normalizeDashboardLayout($parameters[UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER]);
		}

		public function setDashboardDefaultLayout(array $layout): void
		{
			$parameters = $this->getParametersArray();
			$parameters[UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER] = UserHolon::normalizeDashboardLayout($layout);
			$this->setParametersArray($parameters);
		}

		public function clearDashboardDefaultLayout(): void
		{
			$parameters = $this->getParametersArray();
			unset($parameters[UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER]);
			$this->setParametersArray($parameters);
		}

		public function getDashboardTemplateLayoutKeys(): array
		{
			$keys = array();
			foreach ($this->getTemplateLineageHolons() as $template) {
				if (!$template instanceof self) {
					continue;
				}
				$key = UserHolon::makeDashboardTemplateKey(
					(int)$template->get('IDtypeholon'),
					(string)$template->get('templatename')
				);
				$keys[$key] = $key;
			}

			$typeKey = UserHolon::makeDashboardTemplateKey((int)$this->get('IDtypeholon'));
			$keys[$typeKey] = $typeKey;
			return array_values($keys);
		}

		public function getDashboardDirectTemplateLayoutKey(): string
		{
			$template = $this->getTemplateHolon();
			if (!$template instanceof self) {
				return '';
			}

			return UserHolon::makeDashboardTemplateKey(
				(int)$template->get('IDtypeholon'),
				(string)$template->get('templatename')
			);
		}

		public function getDashboardBaseTypeLayoutKey(): string
		{
			return UserHolon::makeDashboardBaseTypeKey((int)$this->get('IDtypeholon'));
		}

		public function getDashboardTemplateLayoutLabel(): string
		{
			$template = $this->getTemplateHolon();
			if ($template instanceof self) {
				$templateName = trim((string)$template->get('templatename'));
				if ($templateName !== '') {
					return $templateName;
				}
			}

			return $this->getTypeLabel();
		}
		
		// Resout organisation liee
		protected function resolveOrganizationId()
		{
			$organizationId = (int)$this->get('IDorganization');
			if ($organizationId > 0) {
				return $organizationId;
			}

			$rootHolonId = (int)$this->get('IDholon_org');
			if ($rootHolonId <= 0) {
				return 0;
			}

			$rootHolon = new self();
			if (!$rootHolon->load($rootHolonId)) {
				return 0;
			}

			return (int)$rootHolon->get('IDorganization');
		}

		public function canView()
		{
			return $this->canViewDetail();
		}

		public function canViewDetail()
		{
			$organizationId = $this->resolveOrganizationId();
			if ($organizationId <= 0) {
				return false;
			}

			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);

			if (function_exists('commonUserHasOrganizationMembership') && \commonUserHasOrganizationMembership($currentUserId, $organizationId)) {
				return true;
			}

			if (function_exists('commonCurrentShareCanViewHolon')) {
				return \commonCurrentShareCanViewHolon($this);
			}

			return false;
		}

		public function canEdit() {
			$currentUserId = (int)\commonGetCurrentUserId();
			if ($currentUserId <= 0) {
				return false;
			}

			$organizationId = $this->resolveOrganizationId();
			if ($organizationId <= 0) {
				return false;
			}

			if (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($currentUserId, $organizationId)) {
				return true;
			}

			if (function_exists('commonUserHasOrganizationMembership')) {
				return \commonUserHasOrganizationMembership($currentUserId, $organizationId);
			}

			$user = new \dbObject\User();
			return $user->load($currentUserId) && $user->hasOrganizationAccess($organizationId);
		}

		protected function userIsAllowed($userId, $permissionKey, $useSessionCache = false)
		{
			$userId = (int)$userId;
			$permissionKey = trim((string)$permissionKey);
			$organizationId = $this->resolveOrganizationId();

			if ($userId <= 0 || $organizationId <= 0 || $permissionKey === '') {
				return false;
			}

			if (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($userId, $organizationId)) {
				return true;
			}

			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
			if (
				$useSessionCache
				&& $currentUserId > 0
				&& $currentUserId === $userId
				&& function_exists('commonCurrentUserHasPermission')
			) {
				return \commonCurrentUserHasPermission($permissionKey, $this, $organizationId);
			}

			return \dbObject\HolonPermission::userHasPermissionForHolonContext(
				$userId,
				$organizationId,
				$permissionKey,
				(int)$this->getId()
			);
		}

		public function isAllowed($permissionKey, $useSessionCache = true, $userId = 0)
		{
			$userId = (int)$userId;
			if ($userId <= 0) {
				$userId = function_exists('commonGetCurrentUserId')
					? (int)\commonGetCurrentUserId()
					: (int)($_SESSION['currentUser'] ?? 0);
			}

			return $this->userIsAllowed($userId, $permissionKey, $useSessionCache);
		}

		// Charge template lie
		public function getTemplateHolon()
		{
			$templateId = (int)$this->get('IDholon_template');
			if ($templateId <= 0) {
				return null;
			}

			$template = new self();
			return $template->load($templateId) ? $template : null;
		}

		protected function getTemplateLineageHolons()
		{
			$lineage = array();
			$current = $this->getTemplateHolon();
			$guard = 0;

			while ($current && (int)$current->getId() > 0 && $guard < 100) {
				$currentId = (int)$current->getId();
				if (isset($lineage[$currentId])) {
					break;
				}

				$lineage[$currentId] = $current;
				$current = $current->getTemplateHolon();
				$guard += 1;
			}

			return array_values($lineage);
		}

		public function getTemplateLineageIds()
		{
			return array_values(array_map(function ($template) {
				return (int)$template->getId();
			}, $this->getTemplateLineageHolons()));
		}

		public function getMandatoryTemplateAncestorIds()
		{
			$mandatoryTemplateIds = array();

			foreach ($this->getTemplateLineageHolons() as $template) {
				$templateId = (int)$template->getId();
				if ($templateId <= 0 || !(bool)$template->get('mandatory')) {
					continue;
				}

				$mandatoryTemplateIds[$templateId] = $templateId;
			}

			return array_values($mandatoryTemplateIds);
		}

		// Verifie template obligatoire
		public function isMandatoryTemplateInstance()
		{
			return count($this->getMandatoryTemplateAncestorIds()) > 0;
		}

		public function isMandatoryVisibleTemplateOriginal()
		{
			return (bool)$this->get('visible')
				&& (bool)$this->get('mandatory')
				&& trim((string)$this->get('templatename')) !== '';
		}

		// Verifie nom verrouille
		public function isNameLockedByTemplate()
		{
			$template = $this->getTemplateHolon();
			return $template ? (bool)$template->get('lockedname') : false;
		}

		protected function getInheritedTemplateStringField($field, $guard = 0)
		{
			if ($guard >= 20) {
				return '';
			}

			$template = $this->getTemplateHolon();
			if (!$template) {
				return '';
			}

			$value = trim((string)$template->get($field));
			if ($value !== '') {
				return $value;
			}

			return $template->getInheritedTemplateStringField($field, $guard + 1);
		}

		public function getEffectiveTemplateStringField($field, $guard = 0)
		{
			$value = trim((string)$this->get($field));
			if ($value !== '') {
				return $value;
			}

			return $this->getInheritedTemplateStringField($field, $guard);
		}

		protected function getInheritedTemplateBooleanField($field, $guard = 0)
		{
			if ($guard >= 20) {
				return false;
			}

			$template = $this->getTemplateHolon();
			if (!$template) {
				return false;
			}

			if ((bool)$template->get($field)) {
				return true;
			}

			return $template->getInheritedTemplateBooleanField($field, $guard + 1);
		}

		public function getEffectiveTemplateBooleanField($field, $guard = 0)
		{
			if ((bool)$this->get($field)) {
				return true;
			}

			return $this->getInheritedTemplateBooleanField($field, $guard);
		}

		public function getInheritedIcon()
		{
			return $this->getInheritedTemplateStringField('icon');
		}

		public function getEffectiveIcon()
		{
			return $this->getEffectiveTemplateStringField('icon');
		}

		public function getInheritedBanner()
		{
			return $this->getInheritedTemplateStringField('banner');
		}

		public function getEffectiveBanner()
		{
			return $this->getEffectiveTemplateStringField('banner');
		}

		public function isIconLockedByTemplate()
		{
			return $this->getInheritedTemplateBooleanField('lockedicon');
		}

		public function isBannerLockedByTemplate()
		{
			return $this->getInheritedTemplateBooleanField('lockedbanner');
		}

		// Compte instances soeurs
		public function countSiblingTemplateInstances()
		{
			$templateId = (int)$this->get('IDholon_template');
			$parentHolon = $this->getParentHolon();
			if ($templateId <= 0 || !$parentHolon) {
				return 0;
			}

			$mandatoryTemplateIds = $this->getMandatoryTemplateAncestorIds();
			$mandatoryTemplateIdMap = array_fill_keys(array_map('intval', $mandatoryTemplateIds), true);
			$count = 0;
			foreach ($parentHolon->getChildren() as $child) {
				if ((int)$child->getId() === (int)$this->getId()) {
					continue;
				}

				$childTemplateId = (int)$child->get('IDholon_template');
				$isVisibleTemplateOriginal = (bool)$child->get('visible')
					&& trim((string)$child->get('templatename')) !== '';
				if ($childTemplateId <= 0 && !$isVisibleTemplateOriginal) {
					continue;
				}

				if (count($mandatoryTemplateIdMap) > 0) {
					$childLineageIds = $child->getTemplateLineageIds();
					if ($isVisibleTemplateOriginal) {
						$childLineageIds[] = (int)$child->getId();
					}
					$matchesMandatoryConstraints = true;

					foreach ($mandatoryTemplateIdMap as $mandatoryTemplateId => $unused) {
						if (!in_array((int)$mandatoryTemplateId, $childLineageIds, true)) {
							$matchesMandatoryConstraints = false;
							break;
						}
					}

					if (!$matchesMandatoryConstraints) {
						continue;
					}
				} elseif ($childTemplateId !== $templateId && (int)$child->getId() !== $templateId) {
					continue;
				}

				$count += 1;
			}

			return $count;
		}

		public function isLastMandatoryTemplateInstance()
		{
			return $this->isMandatoryTemplateInstance() && $this->countSiblingTemplateInstances() === 0;
		}

		// Controle suppression noeud
		public function canDelete()
		{
			if (!$this->canEdit()) {
				return false;
			}

			if ($this->isLastMandatoryTemplateInstance()) {
				return false;
			}

			if ($this->isMandatoryVisibleTemplateOriginal()) {
				return false;
			}

			return true;
		}

		// Retourne tous les enfants (uniquement pour les orga
		public function getAllChildren() {
			if ($this->get("IDtypeholon")==4) {
				$children=new \dbObject\ArrayHolon();
				$children->load([
					"where" => [
						["field" => "active", "value" => 1],
						["field" => "visible", "value" => 1],
						["field" => "IDholon_org", "value" => $this->get("id")],
					],
				]);
				return $children;	
			} else return null;		
		}
		
		public function setPropertyValue($key,$value) {
			// Charge la propriete avec cette cle et cette reference au noeud
			$property=new \dbObject\HolonProperty();
			$property->load([['IDholon',$this->getId()],['IDproperty',$key]]);
			$rawValue = isset($value["value"]) ? $value["value"] : null;
			if (is_array($rawValue)) {
				$rawValue = json_encode(array_values($rawValue), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
			// Si trouve, la reactive et la met a jour
			if ($property->getId()>0) {
				$property->set("value",$rawValue);
				$property->set("active",true);
			} else {
				
				// Si pas trouve, la cree et l'initialise
				$property->set("IDholon",$this->getId());
				$property->set("IDproperty",$key);
				$property->set("value",$rawValue);
			}
			// Sauve la valeur
			$property->save();
				
		}
		
		// Retourne les proprietes de l'objet, incluant celles associees a l'objet et celles associees a ses templates
		// Pour l'instant, uniquement celles de l'objet
		public function getProperties() {
			// Recupere la liste des proprietes specifiques au noeud
			$properties=new \dbObject\ArrayProperty();
			$properties->load([
				"joins" => ["holonproperty"],
				"where" => [
					["field" => "holonproperty.IDholon", "value" => $this->getId()],
				],
				"orderBy" => [
					["field" => "holonproperty.position", "dir" => "ASC"],
				],
			]);
			return $properties;	
			
		}
		
		public function getHolonProperties() {
			// Recupere la liste des proprietes specifiques au noeud
			$properties=new \dbObject\ArrayHolonProperty();
			$properties->load([
				"where" => [
					["field" => "IDholon", "value" => $this->getId()],
				],
				"orderBy" => [
					["field" => "position", "dir" => "ASC"],
				],
			]);
			return $properties;	
			
		}
		
		public function getPropertiesValue() {
			// Recupere l'ensemble des proprietes et leurs valeurs
			$values=new \dbObject\ArrayHolonProperty();
			$values->loadAllValues($this);
			return $values;
			
		}

		protected function propertyHasInheritedDefinition(\dbObject\HolonProperty $property)
		{
			return trim((string)$property->get('list_parent')) !== ''
				|| trim((string)$property->get('value_parents')) !== '';
		}

		protected function shouldHideLocalPropertyValue(\dbObject\HolonProperty $property)
		{
			return false;
		}

		public function getRepresentationData(array $options = array()) {
			$keyPrefix = isset($options['propertyKeyPrefix']) ? (string)$options['propertyKeyPrefix'] : 'd';
			$includeAncestors = !isset($options['includePropertyAncestors']) || $options['includePropertyAncestors'];
			$data = array();

			foreach ($this->getPropertiesValue() as $property) {
				$value = $this->shouldHideLocalPropertyValue($property) ? null : $property->get('value');
				$ancestor = $property->get('value_parents');

				if ($value === null && $ancestor === null) {
					continue;
				}

 				$item = array(
					'name' => (string)$property->get('name'),
					'shortname' => (string)$property->get('shortname'),
					'position' => (int)($property->get('effective_position') ?: $property->get('position') ?: 0),
  					'value' => $value !== null ? (string)$value : '',
  					'formatId' => (int)$property->get('IDpropertyformat'),
  					'formatName' => (string)$property->get('propertyformat_name'),
					'listItemType' => (string)$property->get('listitemtype'),
					'listHolonTypeIds' => \dbObject\Property::parseHolonTypeIds($property->get('listholontypeids')),
					'mandatory' => (bool)$property->get('mandatory'),
					'locked' => (bool)$property->get('locked'),
  				);

				if ($includeAncestors) {
					$item['ancestor'] = $ancestor !== null ? (string)$ancestor : '';
				}

				if ($value !== null && trim((string)$value) !== '') {
					$item['effectiveValue'] = (string)$value;
				} elseif ($ancestor !== null && trim((string)$ancestor) !== '') {
					$item['effectiveValue'] = (string)$ancestor;
				} else {
					$item['effectiveValue'] = '';
				}

				$data[$keyPrefix . $property->get('IDproperty')] = $item;
			}

			return $data;
		}

		public function toRepresentationArray(array $options = array()) {
			$options = array_merge(array(
				'representation' => 'default',
				'includeRepresentation' => false,
				'includeDbId' => true,
				'includeProperties' => true,
				'includeChildren' => null,
				'includeSize' => true,
				'childrenField' => 'children',
				'childrenMethod' => 'getChildren',
				'idField' => 'ID',
				'dbIdField' => 'IDdb',
				'nameField' => 'name',
				'typeField' => 'type',
				'leafSize' => 10,
				'containerSize' => 20,
				'maxDepth' => null,
				'currentDepth' => 0,
			), $options);

			$type = (int)$this->get('IDtypeholon');
			$nameField = $options['nameField'];
			$idField = $options['idField'];
			$typeField = $options['typeField'];
			$dbIdField = $options['dbIdField'];
			$childrenField = $options['childrenField'];
			$currentDepth = (int)$options['currentDepth'];
			$maxDepth = isset($options['maxDepth']) ? $options['maxDepth'] : null;

			$node = array(
				$nameField => (string)$this->get('name'),
				$idField => (string)$this->getId(),
				$typeField => (string)$type,
			);

			$fullName = trim((string)$this->get('nomcomplet'));
			if ($fullName !== '') {
				$node['fullName'] = $fullName;
			}

			$color = $this->getEffectiveColor();
			if ($color !== '') {
				$node['mycolor'] = $color;
			}

			$visibleTemplateAncestorId = $this->getVisibleTemplateAncestorId();
			if ($visibleTemplateAncestorId > 0) {
				$node['visibleTemplateAncestorId'] = (string)$visibleTemplateAncestorId;
				$node['isVisibleTemplateInstance'] = true;
			}

			if (!empty($options['includeDbId'])) {
				$node[$dbIdField] = (string)$this->getId();
			}

			if (!empty($options['includeRepresentation'])) {
				$node['representation'] = (string)$options['representation'];
			}

			if (!empty($options['includeMemberUserIds'])) {
				$memberUserIds = $this->getDirectMemberUserIds(isset($options['organizationId']) ? (int)$options['organizationId'] : 0);
				if (count($memberUserIds) > 0) {
					$node['userIds'] = array_values(array_map('intval', $memberUserIds));
				}
			}

			if (!empty($options['includeProperties'])) {
				$data = $this->getRepresentationData($options);
				if (count($data) > 0) {
					$node['data'] = $data;
				}
			}

			$shouldIncludeChildren = is_null($options['includeChildren'])
				? ($type > 1)
				: (bool)$options['includeChildren'];
			$canTraverseChildren = is_null($maxDepth) || $currentDepth < (int)$maxDepth;

			if ($shouldIncludeChildren && $canTraverseChildren && method_exists($this, $options['childrenMethod'])) {
				$children = array();
				$childrenOptions = $options;
				$childrenOptions['currentDepth'] = $currentDepth + 1;
				$childCollection = $this->{$options['childrenMethod']}();

				if ($childCollection) {
					foreach ($childCollection as $child) {
						$children[] = $child->toRepresentationArray($childrenOptions);
					}
				}

				$node[$childrenField] = $children;
			}

			if (!empty($options['includeSize'])) {
				$node['size'] = $shouldIncludeChildren ? (int)$options['containerSize'] : (int)$options['leafSize'];
			}

			return $node;
		}

		public function toBulkStructureRepresentationArray(array $options = array())
		{
			$options = array_merge(array(
				'representation' => 'circle',
				'includeMemberUserIds' => false,
				'organizationId' => 0,
				'organizationRootHolonId' => 0,
			), $options);

			$navigationRootId = (int)$this->getId();
			$organizationRootHolonId = (int)$options['organizationRootHolonId'];
			if ($navigationRootId <= 0 || $organizationRootHolonId <= 0) {
				return array();
			}

			$holonRows = \dbObject\ArrayHolon::fetchStructureRows($organizationRootHolonId);
			$structureHolonIds = self::collectBulkStructureHolonIds($holonRows, $navigationRootId);
			if (count($structureHolonIds) === 0) {
				return array();
			}

			$propertyRowsByHolonId = \dbObject\ArrayHolonProperty::fetchAllValuesByHolonIds($structureHolonIds);
			$memberRows = array();
			$organizationMemberUserIds = array();
			if (!empty($options['includeMemberUserIds'])) {
				$memberRows = \dbObject\UserHolon::fetchStructureRowsForHolonIds(
					(int)$options['organizationId'],
					$structureHolonIds
				);
				$organizationMemberUserIds = \dbObject\UserOrganization::fetchStructureUserIds((int)$options['organizationId']);
			}

			return self::buildBulkStructureRepresentationFromRows(
				$holonRows,
				$navigationRootId,
				$propertyRowsByHolonId,
				$memberRows,
				$organizationMemberUserIds,
				$options
			);
		}

		protected static function collectBulkStructureHolonIds(array $holonRows, $navigationRootId)
		{
			$navigationRootId = (int)$navigationRootId;
			$rowsById = array();
			$childrenByParentId = array();

			foreach ($holonRows as $row) {
				$holonId = (int)($row['id'] ?? 0);
				if ($holonId <= 0) {
					continue;
				}

				$rowsById[$holonId] = $row;
				if (!(bool)($row['active'] ?? false) || !(bool)($row['visible'] ?? false)) {
					continue;
				}

				$parentId = (int)($row['IDholon_parent'] ?? 0);
				if (!isset($childrenByParentId[$parentId])) {
					$childrenByParentId[$parentId] = array();
				}
				$childrenByParentId[$parentId][] = $holonId;
			}

			if (!isset($rowsById[$navigationRootId])) {
				return array();
			}

			$ids = array();
			$append = function ($holonId) use (&$append, &$ids, $rowsById, $childrenByParentId) {
				$holonId = (int)$holonId;
				if ($holonId <= 0 || isset($ids[$holonId]) || !isset($rowsById[$holonId])) {
					return;
				}

				$ids[$holonId] = true;
				if ((int)($rowsById[$holonId]['IDtypeholon'] ?? 0) <= 1) {
					return;
				}

				foreach ($childrenByParentId[$holonId] ?? array() as $childId) {
					$append($childId);
				}
			};
			$append($navigationRootId);

			return array_values(array_map('intval', array_keys($ids)));
		}

		public static function buildBulkStructureRepresentationFromRows(
			array $holonRows,
			$navigationRootId,
			array $propertyRowsByHolonId = array(),
			array $memberRows = array(),
			array $organizationMemberUserIds = array(),
			array $options = array()
		) {
			$options = array_merge(array(
				'representation' => 'circle',
				'includeMemberUserIds' => false,
				'leafSize' => 10,
				'containerSize' => 20,
			), $options);

			$navigationRootId = (int)$navigationRootId;
			$rowsById = array();
			$childrenByParentId = array();
			foreach ($holonRows as $row) {
				$holonId = (int)($row['id'] ?? 0);
				if ($holonId <= 0) {
					continue;
				}

				$rowsById[$holonId] = $row;
				if (!(bool)($row['active'] ?? false) || !(bool)($row['visible'] ?? false)) {
					continue;
				}

				$parentId = (int)($row['IDholon_parent'] ?? 0);
				if (!isset($childrenByParentId[$parentId])) {
					$childrenByParentId[$parentId] = array();
				}
				$childrenByParentId[$parentId][] = $holonId;
			}

			if (!isset($rowsById[$navigationRootId])) {
				return array();
			}

			$effectiveStringCache = array();
			$resolveEffectiveString = function ($holonId, $field) use (&$resolveEffectiveString, &$effectiveStringCache, $rowsById) {
				$cacheKey = (int)$holonId . ':' . (string)$field;
				if (array_key_exists($cacheKey, $effectiveStringCache)) {
					return $effectiveStringCache[$cacheKey];
				}

				$currentId = (int)$holonId;
				$visited = array();
				for ($depth = 0; $depth < 20 && $currentId > 0 && isset($rowsById[$currentId]); $depth += 1) {
					if (isset($visited[$currentId])) {
						break;
					}
					$visited[$currentId] = true;
					$value = trim((string)($rowsById[$currentId][$field] ?? ''));
					if ($value !== '') {
						$effectiveStringCache[$cacheKey] = $value;
						return $value;
					}
					$currentId = (int)($rowsById[$currentId]['IDholon_template'] ?? 0);
				}

				$effectiveStringCache[$cacheKey] = '';
				return '';
			};

			$effectiveBooleanCache = array();
			$resolveEffectiveBoolean = function ($holonId, $field) use (&$resolveEffectiveBoolean, &$effectiveBooleanCache, $rowsById) {
				$cacheKey = (int)$holonId . ':' . (string)$field;
				if (array_key_exists($cacheKey, $effectiveBooleanCache)) {
					return $effectiveBooleanCache[$cacheKey];
				}

				$currentId = (int)$holonId;
				$visited = array();
				for ($depth = 0; $depth < 20 && $currentId > 0 && isset($rowsById[$currentId]); $depth += 1) {
					if (isset($visited[$currentId])) {
						break;
					}
					$visited[$currentId] = true;
					if ((bool)($rowsById[$currentId][$field] ?? false)) {
						$effectiveBooleanCache[$cacheKey] = true;
						return true;
					}
					$currentId = (int)($rowsById[$currentId]['IDholon_template'] ?? 0);
				}

				$effectiveBooleanCache[$cacheKey] = false;
				return false;
			};

			$visibleTemplateAncestorCache = array();
			$resolveVisibleTemplateAncestorId = function ($holonId) use (&$resolveVisibleTemplateAncestorId, &$visibleTemplateAncestorCache, $rowsById) {
				$holonId = (int)$holonId;
				if (array_key_exists($holonId, $visibleTemplateAncestorCache)) {
					return $visibleTemplateAncestorCache[$holonId];
				}

				$currentId = isset($rowsById[$holonId]) ? (int)($rowsById[$holonId]['IDholon_template'] ?? 0) : 0;
				$visited = array();
				for ($depth = 0; $depth < 20 && $currentId > 0 && isset($rowsById[$currentId]); $depth += 1) {
					if (isset($visited[$currentId])) {
						break;
					}
					$visited[$currentId] = true;
					$templateRow = $rowsById[$currentId];
					if ((bool)($templateRow['visible'] ?? false) && trim((string)($templateRow['templatename'] ?? '')) !== '') {
						$visibleTemplateAncestorCache[$holonId] = $currentId;
						return $currentId;
					}
					$currentId = (int)($templateRow['IDholon_template'] ?? 0);
				}

				$visibleTemplateAncestorCache[$holonId] = 0;
				return 0;
			};

			$memberUserIdsByHolonId = array();
			if (!empty($options['includeMemberUserIds'])) {
				foreach ($memberRows as $memberRow) {
					$holonId = (int)($memberRow['IDholon'] ?? 0);
					$userId = (int)($memberRow['IDuser'] ?? 0);
					if ($holonId <= 0 || $userId <= 0) {
						continue;
					}

					if (!isset($memberUserIdsByHolonId[$holonId])) {
						$memberUserIdsByHolonId[$holonId] = array();
					}
					$memberUserIdsByHolonId[$holonId][$userId] = $userId;
				}

				$findContainingCircleId = static function ($holonId) use ($rowsById) {
					$currentId = isset($rowsById[(int)$holonId])
						? (int)($rowsById[(int)$holonId]['IDholon_parent'] ?? 0)
						: 0;
					$visited = array();
					for ($depth = 0; $depth < 100 && $currentId > 0 && isset($rowsById[$currentId]); $depth += 1) {
						if (isset($visited[$currentId])) {
							break;
						}
						$visited[$currentId] = true;
						if ((int)($rowsById[$currentId]['IDtypeholon'] ?? 0) === 2) {
							return $currentId;
						}
						$currentId = (int)($rowsById[$currentId]['IDholon_parent'] ?? 0);
					}
					return 0;
				};

				foreach ($memberRows as $memberRow) {
					$roleHolonId = (int)($memberRow['IDholon'] ?? 0);
					$userId = (int)($memberRow['IDuser'] ?? 0);
					if (
						$roleHolonId <= 0
						|| $userId <= 0
						|| !(bool)($memberRow['active'] ?? false)
						|| (int)($rowsById[$roleHolonId]['IDtypeholon'] ?? 0) !== 1
						|| !$resolveEffectiveBoolean($roleHolonId, 'link')
					) {
						continue;
					}

					$parameters = json_decode((string)($memberRow['parameters'] ?? ''), true);
					if (!is_array($parameters) || empty($parameters['isAdmin'])) {
						continue;
					}

					$containingCircleId = $findContainingCircleId($roleHolonId);
					$englobingCircleId = $findContainingCircleId($containingCircleId);
					if ($englobingCircleId <= 0) {
						continue;
					}

					if (!isset($memberUserIdsByHolonId[$englobingCircleId])) {
						$memberUserIdsByHolonId[$englobingCircleId] = array();
					}
					$memberUserIdsByHolonId[$englobingCircleId][$userId] = $userId;
				}

				foreach ($rowsById as $holonId => $row) {
					if ((int)($row['IDtypeholon'] ?? 0) === 4) {
						$memberUserIdsByHolonId[$holonId] = array();
						foreach ($organizationMemberUserIds as $userId) {
							$userId = (int)$userId;
							if ($userId > 0) {
								$memberUserIdsByHolonId[$holonId][$userId] = $userId;
							}
						}
					}
				}
			}

			$buildNode = function ($holonId) use (
				&$buildNode,
				$rowsById,
				$childrenByParentId,
				$propertyRowsByHolonId,
				$memberUserIdsByHolonId,
				$resolveEffectiveString,
				$resolveVisibleTemplateAncestorId,
				$options
			) {
				$holonId = (int)$holonId;
				if (!isset($rowsById[$holonId])) {
					return null;
				}

				$row = $rowsById[$holonId];
				$typeId = (int)($row['IDtypeholon'] ?? 0);
				$node = array(
					'name' => (string)($row['name'] ?? ''),
					'ID' => (string)$holonId,
					'type' => (string)$typeId,
					'IDdb' => (string)$holonId,
				);

				$fullName = trim((string)($row['nomcomplet'] ?? ''));
				if ($fullName !== '') {
					$node['fullName'] = $fullName;
				}

				$color = $resolveEffectiveString($holonId, 'color');
				if ($color !== '') {
					$node['mycolor'] = $color;
				}

				$visibleTemplateAncestorId = $resolveVisibleTemplateAncestorId($holonId);
				if ($visibleTemplateAncestorId > 0) {
					$node['visibleTemplateAncestorId'] = (string)$visibleTemplateAncestorId;
					$node['isVisibleTemplateInstance'] = true;
				}

				if (!empty($options['includeMemberUserIds']) && !empty($memberUserIdsByHolonId[$holonId])) {
					$node['userIds'] = array_values(array_map('intval', $memberUserIdsByHolonId[$holonId]));
				}

				$data = array();
				foreach ($propertyRowsByHolonId[$holonId] ?? array() as $propertyRow) {
					$value = $propertyRow['value'] ?? null;
					$ancestor = $propertyRow['value_parents'] ?? null;
					if ($value === null && $ancestor === null) {
						continue;
					}

					$propertyId = (int)($propertyRow['IDproperty'] ?? 0);
					if ($propertyId <= 0) {
						continue;
					}

					$data['d' . $propertyId] = array(
						'name' => (string)($propertyRow['name'] ?? ''),
						'shortname' => (string)($propertyRow['shortname'] ?? ''),
						'position' => (int)($propertyRow['effective_position'] ?? 0),
						'value' => $value !== null ? (string)$value : '',
						'formatId' => (int)($propertyRow['IDpropertyformat'] ?? 0),
						'formatName' => (string)($propertyRow['propertyformat_name'] ?? ''),
						'listItemType' => (string)($propertyRow['listitemtype'] ?? ''),
						'listHolonTypeIds' => \dbObject\Property::parseHolonTypeIds($propertyRow['listholontypeids'] ?? ''),
						'mandatory' => (bool)($propertyRow['mandatory'] ?? false),
						'locked' => (bool)($propertyRow['locked'] ?? false),
						'ancestor' => $ancestor !== null ? (string)$ancestor : '',
						'effectiveValue' => $value !== null && trim((string)$value) !== ''
							? (string)$value
							: ($ancestor !== null && trim((string)$ancestor) !== '' ? (string)$ancestor : ''),
					);
				}
				if (count($data) > 0) {
					$node['data'] = $data;
				}

				$shouldIncludeChildren = $typeId > 1;
				if ($shouldIncludeChildren) {
					$node['children'] = array();
					foreach ($childrenByParentId[$holonId] ?? array() as $childId) {
						$childNode = $buildNode($childId);
						if (is_array($childNode)) {
							$node['children'][] = $childNode;
						}
					}
				}

				$node['size'] = $shouldIncludeChildren
					? (int)$options['containerSize']
					: (int)$options['leafSize'];

				return $node;
			};

			return $buildNode($navigationRootId) ?: array();
		}

		public function toRepresentationJson(array $options = array()) {
			$jsonFlags = isset($options['jsonFlags'])
				? (int)$options['jsonFlags']
				: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

			unset($options['jsonFlags']);

			return json_encode($this->toRepresentationArray($options), $jsonFlags);
		}

		public function getParentHolon() {
			$parentId = (int)$this->get('IDholon_parent');
			if ($parentId <= 0) {
				return null;
			}

			$parent = new self();
			return $parent->load($parentId) ? $parent : null;
		}

		public function getContainingCircle($includeSelf = false)
		{
			$current = $includeSelf ? $this : $this->getParentHolon();
			$guard = 0;

			while ($current !== null && $guard < 100) {
				if ((int)$current->get('IDtypeholon') === 2) {
					return $current;
				}

				$current = $current->getParentHolon();
				$guard += 1;
			}

			return null;
		}

		public function getContainingCircleId($includeSelf = false)
		{
			$circle = $this->getContainingCircle($includeSelf);

			return $circle ? (int)$circle->getId() : 0;
		}

		public function getAuthorityParentHolon($includeSelf = false)
		{
			$current = $includeSelf ? $this : $this->getParentHolon();
			$guard = 0;

			while ($current !== null && $guard < 100) {
				if (in_array((int)$current->get('IDtypeholon'), array(2, 4), true)) {
					return $current;
				}

				$current = $current->getParentHolon();
				$guard += 1;
			}

			return null;
		}

		public function getPathHolons($includeSelf = true) {
			$path = array();
			$current = $includeSelf ? $this : $this->getParentHolon();
			$guard = 0;

			while ($current !== null && $guard < 100) {
				$path[] = $current;
				$current = $current->getParentHolon();
				$guard += 1;
			}

			return array_reverse($path);
		}

		public function getTypeLabel() {
			switch ((int)$this->get('IDtypeholon')) {
				case 4:
					return 'Organisation';
				case 3:
					return 'Groupe';
				case 2:
					return 'Cercle';
				case 1:
					return 'Role';
				default:
					return 'Holon';
			}
		}

		public function getTemplateLabel($fallbackToType = true)
		{
			$templateId = (int)$this->get('IDholon_template');
			if ($templateId > 0) {
				$template = new self();
				if ($template->load($templateId)) {
					return $template->getDisplayName();
				}
			}

			return $fallbackToType ? $this->getTypeLabel() : '';
		}

		public function getEffectiveColor($guard = 0)
		{
			$color = trim((string)$this->get('color'));
			if ($color !== '') {
				return $color;
			}

			if ($guard >= 20) {
				return '';
			}

			$templateId = (int)$this->get('IDholon_template');
			if ($templateId <= 0) {
				return '';
			}

			$template = new self();
			if (!$template->load($templateId)) {
				return '';
			}

			return $template->getEffectiveColor($guard + 1);
		}

		public function getVisibleTemplateAncestorId($guard = 0)
		{
			if ($guard >= 20) {
				return 0;
			}

			$templateId = (int)$this->get('IDholon_template');
			if ($templateId <= 0) {
				return 0;
			}

			$template = new self();
			if (!$template->load($templateId)) {
				return 0;
			}

			if ((bool)$template->get('visible') && trim((string)$template->get('templatename')) !== '') {
				return (int)$template->getId();
			}

			return $template->getVisibleTemplateAncestorId($guard + 1);
		}

		public function isDescendantOf($ancestor, $includeSelf = true) {
			$ancestorId = is_object($ancestor) ? (int)$ancestor->getId() : (int)$ancestor;
			if ($ancestorId <= 0) {
				return false;
			}

			$current = $includeSelf ? $this : $this->getParentHolon();
			$guard = 0;

			while ($current !== null && $guard < 100) {
				if ((int)$current->getId() === $ancestorId) {
					return true;
				}

				$current = $current->getParentHolon();
				$guard += 1;
			}

			return false;
		}

		protected function getTemplateAuthorityInstanceIdMap()
		{
			$map = array();
			$authorities = new \dbObject\ArrayAuthority();
			$authorities->loadForHolon((int)$this->getId());
			foreach ($authorities as $authority) {
				$sourceAuthorityId = (int)$authority->get('IDauthority_template');
				if ($sourceAuthorityId > 0) {
					$map[$sourceAuthorityId] = (int)$authority->getId();
				}
			}
			return $map;
		}

		protected function remapTemplateAuthorityListValue($value, $formatId, array $authorityIdMap)
		{
			return \dbObject\PropertyFormat::remapListReferenceIds($value, $formatId, $authorityIdMap);
		}

		public function getPropertyEntries(array $options = array()) {
			$keyPrefix = isset($options['propertyKeyPrefix']) ? (string)$options['propertyKeyPrefix'] : 'd';
			$entries = array();
			$templatePositionsByPropertyId = array();
			$templateLastPosition = 0;
			$template = $this->getTemplateHolon();
			if ($template instanceof self) {
				foreach ($template->getTemplatePropertyDefinitions() as $templateDefinition) {
					$propertyId = (int)($templateDefinition['id'] ?? 0);
					$position = (int)($templateDefinition['position'] ?? 0);
					if ($propertyId <= 0 || $position <= 0) {
						continue;
					}
					$templatePositionsByPropertyId[$propertyId] = $position;
					$templateLastPosition = max($templateLastPosition, $position);
				}
			}

			$templateAuthorityIdMap = $this->getTemplateAuthorityInstanceIdMap();
			foreach ($this->getPropertiesValue() as $property) {
				$propertyId = (int)$property->get('IDproperty');
				$propertyPosition = (int)($property->get('effective_position') ?: $property->get('position') ?: 0);
				if (isset($templatePositionsByPropertyId[$propertyId])) {
					$propertyPosition = (int)$templatePositionsByPropertyId[$propertyId];
				} elseif ($templateLastPosition > 0) {
					$propertyPosition = $templateLastPosition + max(1, $propertyPosition);
				}

				$value = $this->shouldHideLocalPropertyValue($property) ? null : $property->get('value');
				$ancestor = $property->get('value_parents');
				$effectiveValue = null;

				if ($value !== null && trim((string)$value) !== '') {
					$effectiveValue = (string)$value;
				} elseif ($ancestor !== null && trim((string)$ancestor) !== '') {
					$effectiveValue = (string)$ancestor;
				}
				if ((string)$property->get('listitemtype') === \dbObject\Property::LIST_ITEM_AUTHORITY && \dbObject\PropertyFormat::isListFormat((int)$property->get('IDpropertyformat'))) {
					$formatId = (int)$property->get('IDpropertyformat');
					$value = $this->remapTemplateAuthorityListValue($value, $formatId, $templateAuthorityIdMap);
					$ancestor = $this->remapTemplateAuthorityListValue($ancestor, $formatId, $templateAuthorityIdMap);
					$effectiveValue = $this->remapTemplateAuthorityListValue($effectiveValue, $formatId, $templateAuthorityIdMap);
				}

				$entries[] = array(
					'id' => $propertyId,
					'key' => $keyPrefix . $property->get('IDproperty'),
					'shortname' => (string)$property->get('shortname'),
					'name' => (string)$property->get('name'),
					'position' => $propertyPosition,
					'formatId' => (int)$property->get('IDpropertyformat'),
					'formatName' => (string)$property->get('propertyformat_name'),
					'listItemType' => (string)$property->get('listitemtype'),
					'listHolonTypeIds' => \dbObject\Property::parseHolonTypeIds($property->get('listholontypeids')),
					'mandatory' => (bool)$property->get('mandatory'),
					'locked' => (bool)$property->get('locked'),
					'value' => $value !== null ? (string)$value : '',
					'ancestor' => $ancestor !== null ? (string)$ancestor : '',
					'effectiveValue' => $effectiveValue !== null ? $effectiveValue : '',
					'updatedAt' => $property->get('datemodification'),
					'updatedByUserId' => (int)$property->get('IDusermodification'),
				);
			}

			usort($entries, static function ($left, $right) {
				$positionComparison = (int)($left['position'] ?? 0) <=> (int)($right['position'] ?? 0);
				return $positionComparison !== 0
					? $positionComparison
					: ((int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0));
			});

			return $entries;
		}

		public function getCompactExportPropertyRows()
		{
			$rows = array();

			foreach ($this->getHolonProperties() as $holonProperty) {
				$row = array(
					'propertyId' => (int)$holonProperty->get('IDproperty'),
				);

				if ($holonProperty->get('value') !== null && trim((string)$holonProperty->get('value')) !== '') {
					$row['value'] = (string)$holonProperty->get('value');
				}

				if ((int)$holonProperty->get('position') > 0) {
					$row['position'] = (int)$holonProperty->get('position');
				}

				if ((bool)$holonProperty->get('mandatory')) {
					$row['mandatory'] = true;
				}

				if ((bool)$holonProperty->get('locked')) {
					$row['locked'] = true;
				}

				if (!(bool)$holonProperty->get('active')) {
					$row['active'] = false;
				}

				$rows[] = $row;
			}

			return $rows;
		}

		public function getCompactExportPermissionRows()
		{
			$assignmentsByMemberType = \dbObject\HolonPermission::getAssignmentKeyMapForHolon((int)$this->getId());
			if (!is_array($assignmentsByMemberType) || count($assignmentsByMemberType) === 0) {
				return array();
			}

			$rows = array();
			ksort($assignmentsByMemberType);

			foreach ($assignmentsByMemberType as $memberType => $assignmentsByPermissionKey) {
				$memberType = \dbObject\HolonPermission::normalizeMemberType($memberType);
				ksort($assignmentsByPermissionKey);
				foreach ($assignmentsByPermissionKey as $permissionKey => $ranges) {
					$permissionKey = trim((string)$permissionKey);
					if ($permissionKey === '') {
						continue;
					}

					$ranges = is_array($ranges) ? array_values($ranges) : array();
					sort($ranges);

					foreach ($ranges as $range) {
						$range = trim((string)$range);
						if ($range === '') {
							continue;
						}

						$rows[] = array(
							'permissionKey' => $permissionKey,
							'range' => $range,
							'memberType' => $memberType,
						);
					}
				}
			}

			return $rows;
		}

		public function toCompactExportRecord($rootHolonId = 0, array $options = array())
		{
			$options = array_merge(array(
				'role' => 'structure',
				'isScopeRoot' => false,
			), $options);

			$record = array(
				'id' => (int)$this->getId(),
				'typeId' => (int)$this->get('IDtypeholon'),
				'name' => (string)$this->get('name'),
			);

			if (trim((string)$this->get('nomcomplet')) !== '') {
				$record['fullName'] = (string)$this->get('nomcomplet');
			}

			if (!empty($options['role']) && (string)$options['role'] !== 'structure') {
				$record['role'] = (string)$options['role'];
			}

			if (!empty($options['isScopeRoot'])) {
				$record['scopeRoot'] = true;
			}

			if ($this->isTemplateNode((int)$rootHolonId)) {
				$record['templateNode'] = true;
			}

			if (trim((string)$this->get('templatename')) !== '') {
				$record['templateName'] = (string)$this->get('templatename');
			}

			if ((int)$this->get('IDholon_parent') > 0) {
				$record['parentId'] = (int)$this->get('IDholon_parent');
			}

			if ((int)$this->get('IDholon_template') > 0) {
				$record['templateId'] = (int)$this->get('IDholon_template');
			}

			if (!(bool)$this->get('visible')) {
				$record['visible'] = false;
			}

			if ((bool)$this->get('mandatory')) {
				$record['mandatory'] = true;
			}

			if ((bool)$this->get('lockedname')) {
				$record['lockedName'] = true;
			}

			if ((bool)$this->get('lockedicon')) {
				$record['lockedIcon'] = true;
			}

			if ((bool)$this->get('lockedbanner')) {
				$record['lockedBanner'] = true;
			}

			if ((bool)$this->get('unique')) {
				$record['unique'] = true;
			}

			if ((bool)$this->get('link')) {
				$record['link'] = true;
			}

			if ((bool)$this->get('adminparent')) {
				$record['adminParent'] = true;
			}

			if ((int)$this->get('admin_min') > 0) {
				$record['adminMin'] = (int)$this->get('admin_min');
			}

			if ($this->get('admin_max') !== null) {
				$record['adminMax'] = (int)$this->get('admin_max');
			}

			if ((bool)$this->get('lockedadminmin')) {
				$record['lockedAdminMin'] = true;
			}

			if ((bool)$this->get('lockedadminmax')) {
				$record['lockedAdminMax'] = true;
			}

			if ((bool)$this->get('adminminoverride')) {
				$record['adminMinOverride'] = true;
			}

			if ((bool)$this->get('adminmaxoverride')) {
				$record['adminMaxOverride'] = true;
			}

			if (trim((string)$this->get('color')) !== '') {
				$record['color'] = (string)$this->get('color');
			}

			if (trim((string)$this->get('icon')) !== '') {
				$record['icon'] = (string)$this->get('icon');
			}

			if (trim((string)$this->get('banner')) !== '') {
				$record['banner'] = (string)$this->get('banner');
			}

			if (trim((string)$this->get('accesskey')) !== '') {
				$record['accessKey'] = (string)$this->get('accesskey');
			}

			$propertyRows = $this->getCompactExportPropertyRows();
			if (count($propertyRows) > 0) {
				$record['properties'] = $propertyRows;
			}

			$permissionRows = $this->getCompactExportPermissionRows();
			if (count($permissionRows) > 0) {
				$record['permissions'] = $permissionRows;
			}

			return $record;
		}

		public function getDisplayName()
		{
			$name = trim((string)$this->get('name'));
			if ($name !== '') {
				return $name;
			}

			$templateName = trim((string)$this->get('templatename'));
			if ($templateName !== '') {
				return $templateName;
			}

			return 'Holon ' . (int)$this->getId();
		}

		public function getFullDisplayName()
		{
			$fullName = trim((string)$this->get('nomcomplet'));
			if ($fullName !== '') {
				return $fullName;
			}

			return $this->getDisplayName();
		}

		protected static function buildMemberSortKey($value)
		{
			$value = trim(mb_strtolower((string)$value, 'UTF-8'));
			$transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
			if (is_string($transliterated) && $transliterated !== '') {
				$value = $transliterated;
			}

			return preg_replace('/[^a-z0-9]+/', ' ', $value);
		}

		public function isOrganizationHolon()
		{
			return (int)$this->get('IDtypeholon') === 4;
		}

		protected static function resolveMemberPermission($userId, $skipPermissionFilter = false, $organizationId = 0)
		{
			static $cache = array();

			$userId = (int)$userId;
			$organizationId = (int)$organizationId;
			if ($userId <= 0) {
				return array(
					'canView' => false,
					'canViewDetail' => false,
				);
			}

			if ($skipPermissionFilter) {
				return array(
					'canView' => true,
					'canViewDetail' => true,
				);
			}

			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
			$shareToken = function_exists('commonGetCurrentShareToken')
				? (string)\commonGetCurrentShareToken()
				: '';
			$cacheKey = $userId . ':' . $organizationId . ':' . $currentUserId . ':' . $shareToken;

			if (isset($cache[$cacheKey])) {
				return $cache[$cacheKey];
			}

			if (
				$organizationId > 0
				&& $currentUserId > 0
				&& function_exists('commonUserHasOrganizationMembership')
				&& \commonUserHasOrganizationMembership($currentUserId, $organizationId)
			) {
				$cache[$cacheKey] = array(
					'canView' => true,
					'canViewDetail' => true,
				);
				return $cache[$cacheKey];
			}

			if (
				$organizationId > 0
				&& $shareToken !== ''
				&& function_exists('commonCurrentShareCanViewOrganization')
				&& \commonCurrentShareCanViewOrganization($organizationId)
			) {
				$cache[$cacheKey] = array(
					'canView' => function_exists('commonCurrentShareAllowsPeople') ? \commonCurrentShareAllowsPeople() : false,
					'canViewDetail' => function_exists('commonCurrentShareAllowsPeopleDetail') ? \commonCurrentShareAllowsPeopleDetail() : false,
				);
				return $cache[$cacheKey];
			}

			$user = new \dbObject\User();
			if (!$user->load($userId)) {
				$cache[$cacheKey] = array(
					'canView' => false,
					'canViewDetail' => false,
				);
				return $cache[$cacheKey];
			}

			$cache[$cacheKey] = array(
				'canView' => $user->canView(),
				'canViewDetail' => $user->canViewDetail(),
			);

			return $cache[$cacheKey];
		}

		protected function getOrganizationMemberCards($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return array();
			}

			$memberships = new \dbObject\ArrayUserOrganization();
			$memberships->loadVisibleForOrganization($organizationId, true);
			$pendingInvitationUserIds = \dbObject\Invitation::getPendingAdminUserIdsForOrganization($organizationId);

			$cardsByUserId = array();
			foreach ($memberships as $membership) {
				$userId = (int)$membership->get('IDuser');
				if ($userId <= 0) {
					continue;
				}

				$isPending = !(bool)$membership->get('active');
				$hasPendingInvitation = isset($pendingInvitationUserIds[$userId]);

				$permission = self::resolveMemberPermission($userId, false, $organizationId);
				if (!$permission['canView']) {
					continue;
				}

				if (!isset($cardsByUserId[$userId])) {
					$cardsByUserId[$userId] = array(
						'userId' => $userId,
						'displayName' => $membership->getUserDisplayName(),
						'photoUrl' => $membership->getProfilePhotoUrl(),
						'initials' => $membership->getUserInitials(),
						'avatarSeed' => $membership->getAvatarSeedLabel(),
						'holonIds' => array((int)$this->getId()),
						'isPending' => $isPending,
						'hasPendingInvitation' => $hasPendingInvitation,
						'isAdmin' => $membership->isOrganizationAdmin(),
						'canViewDetail' => $permission['canViewDetail'],
					);
					continue;
				}

				if (!$isPending) {
					$cardsByUserId[$userId]['displayName'] = $membership->getUserDisplayName();
					$cardsByUserId[$userId]['photoUrl'] = $membership->getProfilePhotoUrl();
					$cardsByUserId[$userId]['initials'] = $membership->getUserInitials();
					$cardsByUserId[$userId]['avatarSeed'] = $membership->getAvatarSeedLabel();
					$cardsByUserId[$userId]['isPending'] = false;
					$cardsByUserId[$userId]['hasPendingInvitation'] = false;
					$cardsByUserId[$userId]['isAdmin'] = $membership->isOrganizationAdmin();
					$cardsByUserId[$userId]['canViewDetail'] = $permission['canViewDetail'];
				}
			}

			$cards = array_values($cardsByUserId);

			usort($cards, static function (array $left, array $right) {
				if ((bool)($left['isAdmin'] ?? false) !== (bool)($right['isAdmin'] ?? false)) {
					return !empty($left['isAdmin']) ? -1 : 1;
				}

				return strcmp(
					self::buildMemberSortKey($left['displayName'] ?? ''),
					self::buildMemberSortKey($right['displayName'] ?? '')
				);
			});

			return $cards;
		}

		protected function getOrganizationMemberUserIds($organizationId)
		{
			$userIds = array();
			foreach ($this->getOrganizationMemberCards($organizationId) as $card) {
				$userId = (int)($card['userId'] ?? 0);
				if ($userId <= 0 || isset($userIds[$userId])) {
					continue;
				}

				$userIds[$userId] = $userId;
			}

			return array_values($userIds);
		}

		protected function collectMemberScopeHolonIds($includeDescendants = true, &$bucket = array(), &$visited = array())
		{
			$holonId = (int)$this->getId();
			if ($holonId <= 0 || isset($visited[$holonId])) {
				return;
			}

			$visited[$holonId] = true;
			$bucket[] = $holonId;

			if (!$includeDescendants) {
				return;
			}

			foreach ($this->getChildren() as $child) {
				$childTypeId = (int)$child->get('IDtypeholon');
				if ($childTypeId === 1 || $childTypeId === 3) {
					$child->collectMemberScopeHolonIds(true, $bucket, $visited);
					continue;
				}

				if ((int)$this->get('IDtypeholon') === 4) {
					$child->collectMemberScopeHolonIds(true, $bucket, $visited);
				}
			}
		}

		protected function collectRoleScopeHolonIds($includeDescendants = true, &$bucket = array(), &$visited = array())
		{
			$holonId = (int)$this->getId();
			if ($holonId <= 0 || isset($visited[$holonId])) {
				return;
			}

			$visited[$holonId] = true;
			if ((int)$this->get('IDtypeholon') === 1) {
				$bucket[$holonId] = $holonId;
			}

			if (!$includeDescendants) {
				return;
			}

			foreach ($this->getChildren() as $child) {
				$child->collectRoleScopeHolonIds(true, $bucket, $visited);
			}
		}

		protected function collectLinkRoleIdsForEnglobingCircleMembership($targetCircleId, &$bucket = array(), &$visited = array())
		{
			$targetCircleId = (int)$targetCircleId;
			$holonId = (int)$this->getId();
			if ($targetCircleId <= 0 || $holonId <= 0 || isset($visited[$holonId])) {
				return;
			}

			$visited[$holonId] = true;

			foreach ($this->getChildren() as $child) {
				$childId = (int)$child->getId();
				if ($childId <= 0) {
					continue;
				}

				$childTypeId = (int)$child->get('IDtypeholon');
				if ($childTypeId === 1) {
					if (!(bool)$child->getEffectiveTemplateBooleanField('link')) {
						continue;
					}

					$containingCircle = $child->getContainingCircle();
					$englobingCircle = $containingCircle ? $containingCircle->getContainingCircle() : null;
					if ($englobingCircle && (int)$englobingCircle->getId() === $targetCircleId) {
						$bucket[$childId] = $childId;
					}

					continue;
				}

				if (in_array($childTypeId, array(2, 3), true)) {
					$child->collectLinkRoleIdsForEnglobingCircleMembership($targetCircleId, $bucket, $visited);
				}
			}
		}

		protected function loadCalculatedCircleMemberLinkRows($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0 || (int)$this->get('IDtypeholon') !== 2) {
				return array();
			}

			$linkRoleIds = array();
			$visitedHolonIds = array();
			$this->collectLinkRoleIdsForEnglobingCircleMembership((int)$this->getId(), $linkRoleIds, $visitedHolonIds);
			$linkRoleIds = array_values(array_unique(array_filter(array_map('intval', $linkRoleIds), function ($holonId) {
				return $holonId > 0;
			})));

			if (count($linkRoleIds) === 0) {
				return array();
			}

			$adminLinks = new \dbObject\ArrayUserHolon();
			$adminLinks->loadActiveForHolonIds($linkRoleIds);
			$adminMapByHolonId = array();

			foreach ($adminLinks as $link) {
				$roleHolonId = (int)$link->get('IDholon');
				$userId = (int)$link->get('IDuser');
				if ($roleHolonId <= 0 || $userId <= 0 || !$link->isHolonAdmin()) {
					continue;
				}

				if (!isset($adminMapByHolonId[$roleHolonId])) {
					$adminMapByHolonId[$roleHolonId] = array();
				}

				$adminMapByHolonId[$roleHolonId][$userId] = true;
			}

			if (count($adminMapByHolonId) === 0) {
				return array();
			}

			$candidateRows = $this->loadVisibleMemberLinkRows($linkRoleIds, $organizationId, false);
			$rows = array();

			foreach ($candidateRows as $row) {
				$roleHolonId = (int)($row['holon_id'] ?? 0);
				$userId = (int)($row['user_id'] ?? 0);
				if ($roleHolonId <= 0 || $userId <= 0 || empty($adminMapByHolonId[$roleHolonId][$userId])) {
					continue;
				}

				$rows[] = $row;
			}

			return $rows;
		}

		protected function loadVisibleMemberLinkRows(array $holonIds, $organizationId, $includeCalculatedMembers = true)
		{
			$organizationId = (int)$organizationId;
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));

			if ($organizationId <= 0 || count($holonIds) === 0) {
				return array();
			}

			$placeholders = array();
			$params = array(
				'uo_organization_id' => $organizationId,
				'inv_pending_organization_id' => $organizationId,
				'inv_request_origin_member' => \dbObject\Invitation::REQUEST_ORIGIN_MEMBER,
			);

			foreach ($holonIds as $index => $holonId) {
				$placeholder = 'holon_' . $index;
				$placeholders[] = ':' . $placeholder;
				$params[$placeholder] = $holonId;
			}

			$query = "
				SELECT DISTINCT
					uh.IDuser AS user_id,
					uh.IDholon AS holon_id,
					h.IDtypeholon AS holon_type_id,
					uh.active AS holon_active,
					uh.active AS holon_effective_active,
					uh.parameters AS holon_parameters,
					uh.datecreation AS holon_assigned_at,
					uh.focus AS holon_focus,
					uh.time_budget_hours AS holon_time_budget_hours,
					uh.time_budget_recurrence AS holon_time_budget_recurrence,
					uh.money_budget AS holon_money_budget,
					uh.money_budget_recurrence AS holon_money_budget_recurrence,
					uh.assignment_review_date AS holon_assignment_review_date,
					COALESCE(uo.active, 0) AS organization_active,
					CASE
						WHEN inv.id IS NULL THEN 0
						ELSE 1
					END AS has_pending_invitation,
					CASE
						WHEN inv.id IS NULL OR inv.request_origin = :inv_request_origin_member THEN 0
						ELSE 1
					END AS has_pending_admin_invitation,
					0 AS has_accepted_invitation
				FROM user_holon uh
				INNER JOIN `user` u ON u.id = uh.IDuser
				INNER JOIN holon h ON h.id = uh.IDholon
				LEFT JOIN user_organization uo
					ON uo.IDuser = uh.IDuser
					AND uo.IDorganization = :uo_organization_id
				LEFT JOIN invitation inv
					ON inv.IDorganization = :inv_pending_organization_id
					AND inv.IDuser = uh.IDuser
					AND inv.status = 'pending'
					AND inv.active = 1
					AND (inv.dateexpiration IS NULL OR inv.dateexpiration > NOW())
				WHERE uh.IDholon IN (" . implode(', ', $placeholders) . ")
				  AND uh.is_membership = 1
				  AND (
					uh.active = 1
					OR inv.id IS NOT NULL
					OR (uo.id IS NOT NULL AND uo.active = 0)
				  )
				ORDER BY
					COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					u.id ASC,
					uh.IDholon ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			if ($rows === false) {
				$rows = null;
			}

			if ($rows === null) {
				$fallbackQuery = "
					SELECT DISTINCT
						uh.IDuser AS user_id,
						uh.IDholon AS holon_id,
						h.IDtypeholon AS holon_type_id,
						uh.active AS holon_active,
						uh.active AS holon_effective_active,
					uh.parameters AS holon_parameters,
					uh.datecreation AS holon_assigned_at,
					uh.focus AS holon_focus,
					uh.time_budget_hours AS holon_time_budget_hours,
					uh.time_budget_recurrence AS holon_time_budget_recurrence,
					uh.money_budget AS holon_money_budget,
					uh.money_budget_recurrence AS holon_money_budget_recurrence,
					uh.assignment_review_date AS holon_assignment_review_date,
					1 AS organization_active,
						0 AS has_pending_invitation,
						0 AS has_pending_admin_invitation,
						0 AS has_accepted_invitation
					FROM user_holon uh
					INNER JOIN `user` u ON u.id = uh.IDuser
					INNER JOIN holon h ON h.id = uh.IDholon
					WHERE uh.IDholon IN (" . implode(', ', $placeholders) . ")
					  AND uh.active = 1
					  AND uh.is_membership = 1
					ORDER BY
						COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
						COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
						u.id ASC,
						uh.IDholon ASC
				";

				$fallbackParams = array();
				foreach ($holonIds as $index => $holonId) {
					$fallbackParams['holon_' . $index] = $holonId;
				}

				$rows = \dbObject\DbObject::fetchAll($fallbackQuery, $fallbackParams);
				$rows = $rows !== false ? $rows : array();
			}

			if (!$includeCalculatedMembers) {
				return $rows;
			}

			$calculatedRows = $this->loadCalculatedCircleMemberLinkRows($organizationId);
			if (count($calculatedRows) === 0) {
				return $rows;
			}

			$mergedRows = array();
			$rowKeys = array();
			foreach (array_merge($rows, $calculatedRows) as $row) {
				$rowKey = (int)($row['user_id'] ?? 0) . ':' . (int)($row['holon_id'] ?? 0);
				if ($rowKey === '0:0' || isset($rowKeys[$rowKey])) {
					continue;
				}

				$rowKeys[$rowKey] = true;
				$mergedRows[] = $row;
			}

			return $mergedRows;
		}

		public function getAssociatedMemberCards(array $options = array())
		{
			$options = array_merge(array(
				'organizationId' => $this->resolveOrganizationId(),
				'includeDescendants' => ((int)$this->get('IDtypeholon') !== 1),
				'skipPermissionFilter' => false,
			), $options);

			if ($this->isOrganizationHolon()) {
				return $this->getOrganizationMemberCards((int)$options['organizationId']);
			}

			$scopeHolonIds = array();
			$visitedHolonIds = array();
			$this->collectMemberScopeHolonIds((bool)$options['includeDescendants'], $scopeHolonIds, $visitedHolonIds);

			$linkRows = $this->loadVisibleMemberLinkRows($scopeHolonIds, (int)$options['organizationId']);

			$cardsByUserId = array();
			foreach ($linkRows as $row) {
				$userId = (int)($row['user_id'] ?? 0);
				if ($userId <= 0) {
					continue;
				}

				$permission = self::resolveMemberPermission($userId, !empty($options['skipPermissionFilter']), (int)$options['organizationId']);
				if (!$permission['canView']) {
					continue;
				}

				if (!isset($cardsByUserId[$userId])) {
					$link = new \dbObject\UserHolon();
					$link->set('IDuser', $userId);
					$cardsByUserId[$userId] = array(
						'userId' => $userId,
						'displayName' => $link->getUserDisplayName((int)$options['organizationId']),
						'photoUrl' => $link->getProfilePhotoUrl((int)$options['organizationId']),
						'initials' => $link->getUserInitials((int)$options['organizationId']),
						'avatarSeed' => $link->getAvatarSeedLabel((int)$options['organizationId']),
						'holonIds' => array(),
						'assignmentLinks' => array(),
						'isPending' => false,
						'hasPendingInvitation' => false,
						'isAdmin' => false,
						'canViewDetail' => $permission['canViewDetail'],
					);
				}

				$linkedHolonId = (int)($row['holon_id'] ?? 0);
				if ($linkedHolonId > 0 && !in_array($linkedHolonId, $cardsByUserId[$userId]['holonIds'], true)) {
					$cardsByUserId[$userId]['holonIds'][] = $linkedHolonId;
				}
				if ($linkedHolonId > 0) {
					$cardsByUserId[$userId]['assignmentLinks'][$linkedHolonId] = array(
						'holonId' => $linkedHolonId,
						'holonTypeId' => (int)($row['holon_type_id'] ?? 0),
						'assignedAt' => $row['holon_assigned_at'] ?? null,
						'focus' => trim((string)($row['holon_focus'] ?? '')),
						'timeBudgetHours' => $row['holon_time_budget_hours'] ?? null,
						'timeBudgetRecurrence' => trim((string)($row['holon_time_budget_recurrence'] ?? '')),
						'moneyBudget' => $row['holon_money_budget'] ?? null,
						'moneyBudgetRecurrence' => trim((string)($row['holon_money_budget_recurrence'] ?? '')),
						'assignmentReviewDate' => $row['holon_assignment_review_date'] ?? null,
					);
				}

				if (
					!(bool)($row['holon_effective_active'] ?? ($row['holon_active'] ?? false))
					|| !(bool)($row['organization_active'] ?? false)
					|| (bool)($row['has_pending_invitation'] ?? false)
				) {
					$cardsByUserId[$userId]['isPending'] = true;
				}
				if ((bool)($row['has_pending_admin_invitation'] ?? false)) {
					$cardsByUserId[$userId]['hasPendingInvitation'] = true;
				}

				$holonParameters = json_decode((string)($row['holon_parameters'] ?? ''), true);
				if (is_array($holonParameters) && !empty($holonParameters['isAdmin'])) {
					$cardsByUserId[$userId]['isAdmin'] = true;
				}
			}

			$cards = array_values($cardsByUserId);
			foreach ($cards as &$card) {
				$card['assignmentLinks'] = array_values($card['assignmentLinks']);
			}
			unset($card);
			usort($cards, static function (array $left, array $right) {
				if ((bool)($left['isAdmin'] ?? false) !== (bool)($right['isAdmin'] ?? false)) {
					return !empty($left['isAdmin']) ? -1 : 1;
				}

				return strcmp(
					self::buildMemberSortKey($left['displayName'] ?? ''),
					self::buildMemberSortKey($right['displayName'] ?? '')
				);
			});

			return $cards;
		}

		public function getAssociatedMemberUserIds(array $options = array())
		{
			$options = array_merge(array(
				'organizationId' => $this->resolveOrganizationId(),
				'includeDescendants' => ((int)$this->get('IDtypeholon') !== 1),
				'skipPermissionFilter' => false,
			), $options);

			if ($this->isOrganizationHolon()) {
				if (!empty($options['skipPermissionFilter'])) {
					$memberships = new \dbObject\ArrayUserOrganization();
					$memberships->loadVisibleForOrganization((int)$options['organizationId']);
					$userIds = array();
					foreach ($memberships as $membership) {
						$userId = (int)$membership->get('IDuser');
						if ($userId > 0) {
							$userIds[$userId] = $userId;
						}
					}
					return array_values($userIds);
				}

				return $this->getOrganizationMemberUserIds((int)$options['organizationId']);
			}

			$scopeHolonIds = array();
			$visitedHolonIds = array();
			$this->collectMemberScopeHolonIds((bool)$options['includeDescendants'], $scopeHolonIds, $visitedHolonIds);

			$linkRows = $this->loadVisibleMemberLinkRows($scopeHolonIds, (int)$options['organizationId']);
			$userIds = array();
			foreach ($linkRows as $row) {
				$userId = (int)($row['user_id'] ?? 0);
				if ($userId <= 0 || isset($userIds[$userId])) {
					continue;
				}

				$permission = self::resolveMemberPermission($userId, !empty($options['skipPermissionFilter']), (int)$options['organizationId']);
				if (!$permission['canView']) {
					continue;
				}

				$userIds[$userId] = $userId;
			}

			return array_values($userIds);
		}

		public function getDirectMemberCards($organizationId = 0)
		{
			return $this->getAssociatedMemberCards(array(
				'organizationId' => (int)$organizationId > 0 ? (int)$organizationId : $this->resolveOrganizationId(),
				'includeDescendants' => false,
			));
		}

		public function getDirectMemberUserIds($organizationId = 0)
		{
			if ($this->isOrganizationHolon()) {
				return $this->getOrganizationMemberUserIds((int)$organizationId > 0 ? (int)$organizationId : $this->resolveOrganizationId());
			}

			$linkRows = $this->loadVisibleMemberLinkRows(
				array((int)$this->getId()),
				(int)$organizationId > 0 ? (int)$organizationId : $this->resolveOrganizationId()
			);

			$userIds = array();
			foreach ($linkRows as $row) {
				$userId = (int)($row['user_id'] ?? 0);
				if ($userId <= 0 || isset($userIds[$userId])) {
					continue;
				}

				$userIds[$userId] = $userId;
			}

			return array_values($userIds);
		}

		public function getMemberRemovalSummary($userId, array $options = array())
		{
			$userId = (int)$userId;
			$options = array_merge(array(
				'organizationId' => $this->resolveOrganizationId(),
				'includeDescendants' => ((int)$this->get('IDtypeholon') !== 1),
			), $options);
			$organizationId = (int)$options['organizationId'];

			if ($userId <= 0 || $organizationId <= 0) {
				return array(
					'holonCount' => 0,
					'roleCount' => 0,
				);
			}

			$scopeHolonIds = array();
			$visitedHolonIds = array();
			$this->collectMemberScopeHolonIds((bool)$options['includeDescendants'], $scopeHolonIds, $visitedHolonIds);
			$scopeHolonIds = array_values(array_unique(array_filter(array_map('intval', $scopeHolonIds), function ($holonId) {
				return $holonId > 0;
			})));

			if (count($scopeHolonIds) === 0) {
				return array(
					'holonCount' => 0,
					'roleCount' => 0,
				);
			}

			$placeholders = array();
			$params = array('user_id' => $userId);
			foreach ($scopeHolonIds as $index => $holonId) {
				$placeholder = 'holon_' . $index;
				$placeholders[] = ':' . $placeholder;
				$params[$placeholder] = $holonId;
			}

			$rows = self::fetchAll(
				"SELECT DISTINCT uh.IDholon AS holon_id, h.IDtypeholon AS holon_type
				 FROM user_holon uh
     INNER JOIN holon h ON h.id = uh.IDholon
				 WHERE uh.IDuser = :user_id
				   AND uh.IDholon IN (" . implode(', ', $placeholders) . ")
				   AND uh.is_membership = 1",
				$params
			);

			if (!is_array($rows)) {
				$rows = array();
			}

			$holonIds = array();
			$roleIds = array();
			foreach ($rows as $row) {
				$holonId = (int)($row['holon_id'] ?? 0);
				if ($holonId <= 0) {
					continue;
				}

				$holonIds[$holonId] = $holonId;
				if ((int)($row['holon_type'] ?? 0) === 1) {
					$roleIds[$holonId] = $holonId;
				}
			}

			return array(
				'holonCount' => count($holonIds),
				'roleCount' => count($roleIds),
			);
		}

		public function getVisibleRoleAssignmentsForUser($userId, array $options = array())
		{
			$userId = (int)$userId;
			if ($userId <= 0 || (int)$this->getId() <= 0) {
				return array();
			}

			$options = array_merge(array(
				'organizationId' => $this->resolveOrganizationId(),
				'includeDescendants' => ((int)$this->get('IDtypeholon') !== 1),
			), $options);

			$scopeHolonIds = array();
			$visitedHolonIds = array();
			$this->collectRoleScopeHolonIds((bool)$options['includeDescendants'], $scopeHolonIds, $visitedHolonIds);
			$scopeHolonIds = array_values($scopeHolonIds);

			$linkRows = $this->loadVisibleMemberLinkRows($scopeHolonIds, (int)$options['organizationId']);
			$assignmentsByHolonId = array();

			foreach ($linkRows as $row) {
				if ((int)($row['user_id'] ?? 0) !== $userId) {
					continue;
				}

				$roleHolonId = (int)($row['holon_id'] ?? 0);
				if ($roleHolonId <= 0 || isset($assignmentsByHolonId[$roleHolonId])) {
					continue;
				}

				$roleHolon = new self();
				if (
					!$roleHolon->load($roleHolonId)
					|| !(bool)$roleHolon->get('active')
					|| !(bool)$roleHolon->get('visible')
					|| (int)$roleHolon->get('IDtypeholon') !== 1
				) {
					continue;
				}

				$pathLabels = array();
				foreach ($roleHolon->getPathHolons() as $pathHolon) {
					$pathLabel = trim((string)$pathHolon->getDisplayName());
					if ($pathLabel === '') {
						continue;
					}

					$pathLabels[] = $pathLabel;
				}

				$parentHolon = $roleHolon->getParentHolon();
				$assignmentsByHolonId[$roleHolonId] = array(
					'holonId' => $roleHolonId,
					'name' => trim((string)$roleHolon->getDisplayName()),
					'parentLabel' => $parentHolon ? trim((string)$parentHolon->getDisplayName()) : '',
					'pathLabel' => implode(' > ', $pathLabels),
					'isPending' => (
						!(bool)($row['holon_effective_active'] ?? ($row['holon_active'] ?? false))
						|| !(bool)($row['organization_active'] ?? false)
						|| (bool)($row['has_pending_invitation'] ?? false)
					),
				);
			}

			$assignments = array_values($assignmentsByHolonId);
			usort($assignments, static function (array $left, array $right) {
				if ($left['isPending'] !== $right['isPending']) {
					return $left['isPending'] ? 1 : -1;
				}

				return strcmp(
					self::buildMemberSortKey($left['pathLabel'] ?: ($left['name'] ?? '')),
					self::buildMemberSortKey($right['pathLabel'] ?: ($right['name'] ?? ''))
				);
			});

			return $assignments;
		}

		public function getDirectContextAdminUserIds($organizationId = 0)
		{
			$organizationId = (int)$organizationId > 0 ? (int)$organizationId : $this->resolveOrganizationId();
			$userIds = array();

			if ($this->isOrganizationHolon()) {
				$memberships = new \dbObject\ArrayUserOrganization();
				$memberships->loadVisibleForOrganization($organizationId);

				foreach ($memberships as $membership) {
					$userId = (int)$membership->get('IDuser');
					if ($userId <= 0 || !(bool)$membership->get('active') || !$membership->isOrganizationAdmin()) {
						continue;
					}

					$userIds[$userId] = $userId;
				}

				return array_values($userIds);
			}

			$links = new \dbObject\ArrayUserHolon();
			$links->loadActiveForHolonIds(array((int)$this->getId()));

			foreach ($links as $link) {
				$userId = (int)$link->get('IDuser');
				if ($userId <= 0 || !$link->isHolonAdmin()) {
					continue;
				}

				$userIds[$userId] = $userId;
			}

			if ((int)$this->get('IDtypeholon') === 2) {
				foreach ($this->getChildren() as $child) {
					if (!$child->isParentAdminRole()) {
						continue;
					}

					$roleLinks = new \dbObject\ArrayUserHolon();
					$roleLinks->loadActiveForHolonIds(array((int)$child->getId()));
					foreach ($roleLinks as $roleLink) {
						$userId = (int)$roleLink->get('IDuser');
						if ($userId > 0 && $roleLink->isHolonAdmin()) {
							$userIds[$userId] = $userId;
						}
					}
				}
			}

			return array_values($userIds);
		}

		public function isParentAdminRole()
		{
			if ((int)$this->get('IDtypeholon') !== 1) {
				return false;
			}

			if (trim((string)$this->get('templatename')) !== '') {
				return $this->getEffectiveTemplateBooleanField('adminparent');
			}

			$template = $this->getTemplateHolon();
			if ($template) {
				return $template->getEffectiveTemplateBooleanField('adminparent');
			}

			return false;
		}

		public function getEffectiveTemplateAdminBounds()
		{
			$parentTemplate = $this->getTemplateHolon();
			$parentBounds = $parentTemplate
				? $parentTemplate->getEffectiveTemplateAdminBounds()
				: array(
					'min' => 0,
					'max' => null,
					'minLocked' => false,
					'maxLocked' => false,
				);
			$hasParentTemplate = $parentTemplate instanceof self;
			$rawMinimum = $this->get('admin_min');
			$rawMaximum = $this->get('admin_max');
			$hasLocalMinimum = $rawMinimum !== null && trim((string)$rawMinimum) !== '';
			$hasLocalMaximum = $rawMaximum !== null && trim((string)$rawMaximum) !== '';
			$minimum = $hasParentTemplate && (!$hasLocalMinimum || !empty($parentBounds['minLocked']))
				? (int)$parentBounds['min']
				: ($hasLocalMinimum ? max(0, (int)$rawMinimum) : 0);
			$maximum = $hasParentTemplate && (!$hasLocalMaximum || !empty($parentBounds['maxLocked']))
				? $parentBounds['max']
				: ($hasLocalMaximum ? max(0, (int)$rawMaximum) : null);
			if ($maximum !== null && $maximum < $minimum) {
				$maximum = $minimum;
			}

			return array(
				'min' => $minimum,
				'max' => $maximum,
				'minLocked' => !empty($parentBounds['minLocked']) || (bool)$this->get('lockedadminmin'),
				'maxLocked' => !empty($parentBounds['maxLocked']) || (bool)$this->get('lockedadminmax'),
			);
		}

		public function getAdminMemberBounds()
		{
			$template = $this->getTemplateHolon();
			if (!$template && $this->isTemplateNode()) {
				$template = $this;
			}

			if (!$template) {
				return array(
					'min' => 0,
					'max' => null,
					'templateId' => 0,
					'minLocked' => false,
					'maxLocked' => false,
					'minOverridden' => false,
					'maxOverridden' => false,
				);
			}

			$templateBounds = $template->getEffectiveTemplateAdminBounds();
			$isTemplate = (int)$template->getId() === (int)$this->getId();
			$minimumLocked = !empty($templateBounds['minLocked']);
			$maximumLocked = !empty($templateBounds['maxLocked']);
			$minimumOverridden = !$isTemplate && !$minimumLocked && (bool)$this->get('adminminoverride');
			$maximumOverridden = !$isTemplate && !$maximumLocked && (bool)$this->get('adminmaxoverride');
			$minimum = $minimumOverridden
				? max(0, (int)$this->get('admin_min'))
				: (int)$templateBounds['min'];
			$rawMaximum = $maximumOverridden ? $this->get('admin_max') : $templateBounds['max'];
			$maximum = $rawMaximum === null || trim((string)$rawMaximum) === ''
				? null
				: max(0, (int)$rawMaximum);
			if ($maximum !== null && $maximum < $minimum) {
				$maximum = $minimum;
			}

			return array(
				'min' => $minimum,
				'max' => $maximum,
				'templateId' => (int)$template->getId(),
				'minLocked' => $minimumLocked,
				'maxLocked' => $maximumLocked,
				'minOverridden' => $minimumOverridden,
				'maxOverridden' => $maximumOverridden,
			);
		}

		public function getDirectActiveMemberUserIds($organizationId = 0)
		{
			$organizationId = (int)$organizationId > 0 ? (int)$organizationId : $this->resolveOrganizationId();
			if ($organizationId <= 0) {
				return array();
			}

			if ($this->isOrganizationHolon()) {
				return $this->getOrganizationMemberUserIds($organizationId);
			}

			$rows = self::fetchAll(
				'SELECT DISTINCT `IDuser` FROM `user_holon` WHERE `IDholon` = :holon_id AND `active` = 1 AND `is_membership` = 1',
				array('holon_id' => (int)$this->getId())
			);
			if ($rows === false) {
				return array();
			}

			$userIds = array();
			foreach ($rows as $row) {
				$userId = (int)($row['IDuser'] ?? 0);
				if ($userId > 0) {
					$userIds[$userId] = $userId;
				}
			}

			return array_values($userIds);
		}

		public function getAdminMemberConstraintState($organizationId = 0)
		{
			$organizationId = (int)$organizationId > 0 ? (int)$organizationId : $this->resolveOrganizationId();
			$bounds = $this->getAdminMemberBounds();
			$adminUserIds = $this->getDirectContextAdminUserIds($organizationId);
			$memberUserIds = $this->getDirectActiveMemberUserIds($organizationId);

			return array_merge($bounds, array(
				'adminCount' => count($adminUserIds),
				'memberCount' => count($memberUserIds),
				'adminUserIds' => array_values(array_map('intval', $adminUserIds)),
				'memberUserIds' => array_values(array_map('intval', $memberUserIds)),
			));
		}

		public function validateMemberAdditionAdminBounds($userId, $isAdmin, array $options = array())
		{
			$userId = (int)$userId;
			$isAdmin = (bool)$isAdmin;
			$options = array_merge(array(
				'organizationId' => $this->resolveOrganizationId(),
				'canAssignAdmin' => true,
				'includePendingAdmins' => true,
			), $options);
			$state = $this->getAdminMemberConstraintState((int)$options['organizationId']);
			$adminUserIds = array_fill_keys(array_map('intval', $state['adminUserIds']), true);

			if ($isAdmin) {
				if (!isset($adminUserIds[$userId]) && $state['max'] !== null) {
					$pendingAdminCount = 0;
					if (!empty($options['includePendingAdmins'])) {
						$pendingAdminCount = \dbObject\Invitation::countPendingRequestedHolonAdmins(
							(int)$options['organizationId'],
							(int)$this->getId(),
							$userId
						);
					}

					if ((int)$state['adminCount'] + $pendingAdminCount >= (int)$state['max']) {
						return array(
							'status' => false,
							'message' => 'Ce role ne peut pas avoir plus de ' . (int)$state['max'] . ' admin(s).',
						);
					}
				}

				return array('status' => true);
			}

			if ((int)$state['min'] > 0 && (int)$state['adminCount'] < (int)$state['min']) {
				return array(
					'status' => false,
					'message' => !empty($options['canAssignAdmin'])
						? 'Ce role exige au moins ' . (int)$state['min'] . ' admin(s) avant de pouvoir ajouter un membre normal.'
						: 'Ce role exige au moins ' . (int)$state['min'] . ' admin(s) avant de pouvoir ajouter un membre normal, et vous ne pouvez pas definir cet admin.',
				);
			}

			return array('status' => true);
		}

		public function validateMemberAdminStatusChange($userId, $isAdmin, $organizationId = 0)
		{
			$userId = (int)$userId;
			$isAdmin = (bool)$isAdmin;
			$state = $this->getAdminMemberConstraintState($organizationId);
			$adminUserIds = array_fill_keys(array_map('intval', $state['adminUserIds']), true);

			if ($isAdmin) {
				return $this->validateMemberAdditionAdminBounds($userId, true, array(
					'organizationId' => $organizationId,
				));
			}

			if (!isset($adminUserIds[$userId])) {
				return array('status' => true);
			}

			if ((int)$state['adminCount'] - 1 < (int)$state['min']) {
				return array(
					'status' => false,
					'message' => 'Cet admin ne peut pas devenir membre normal tant que le role ne compte pas au moins ' . (int)$state['min'] . ' admin(s).',
				);
			}

			return array('status' => true);
		}

		public function validateDirectMemberRemovalAdminBounds($userId, $organizationId = 0)
		{
			$userId = (int)$userId;
			$state = $this->getAdminMemberConstraintState($organizationId);
			$adminUserIds = array_fill_keys(array_map('intval', $state['adminUserIds']), true);
			$memberUserIds = array_fill_keys(array_map('intval', $state['memberUserIds']), true);
			if (!isset($adminUserIds[$userId]) || !isset($memberUserIds[$userId])) {
				return array('status' => true);
			}

			$remainingMemberCount = max(0, (int)$state['memberCount'] - 1);
			$remainingAdminCount = max(0, (int)$state['adminCount'] - 1);
			if ($remainingMemberCount > 0 && $remainingAdminCount < (int)$state['min']) {
				return array(
					'status' => false,
					'message' => 'Ce retrait laisserait moins de ' . (int)$state['min'] . ' admin(s) alors que ce role compte encore des membres. Nommez ou conservez un admin avant ce retrait.',
				);
			}

			return array('status' => true);
		}

		public function setMemberContextAdmin($userId, $isAdmin, $organizationId = 0)
		{
			$userId = (int)$userId;
			$isAdmin = (bool)$isAdmin;
			$organizationId = (int)$organizationId > 0 ? (int)$organizationId : $this->resolveOrganizationId();
			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);

			if (!$this->userIsAllowed($currentUserId, 'CAN_ADD_ADMIN', false)) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas le droit de gerer le statut admin dans ce contexte.",
				);
			}

			if ($userId <= 0 || $organizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Le membre ou le contexte est invalide.',
				);
			}

			$adminConstraintResult = $this->validateMemberAdminStatusChange($userId, $isAdmin, $organizationId);
			if (empty($adminConstraintResult['status'])) {
				return $adminConstraintResult;
			}

			if ($this->isOrganizationHolon()) {
				$organization = new \dbObject\Organization();
				$membership = new \dbObject\UserOrganization();
				if (
					$organization->load($organizationId)
					&& $membership->load(array(
						array('IDuser', $userId),
						array('IDorganization', $organizationId),
					))
					&& (bool)$membership->get('active')
					&& $membership->isOrganizationAdmin()
					&& $organization->countActiveAdminMemberships($userId) === 0
				) {
					return array(
						'status' => false,
						'message' => "Le dernier admin de l'organisation ne peut pas etre retire.",
					);
				}
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'La connexion à la base de données est indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				if ($this->isOrganizationHolon()) {
					$organization = new \dbObject\Organization();
					if (!$organization->load($organizationId)) {
						throw new \RuntimeException("L'organisation est introuvable.");
					}

					$membership = new \dbObject\UserOrganization();
					if (!$membership->load(array(
						array('IDuser', $userId),
						array('IDorganization', $organizationId),
					))) {
						throw new \RuntimeException("Cette personne n'est pas membre de l'organisation.");
					}

					if (!(bool)$membership->get('active')) {
						throw new \RuntimeException("Le statut admin ne peut être modifié que pour un membre actif de l'organisation.");
					}

					if (!$isAdmin && $membership->isOrganizationAdmin() && $organization->countActiveAdminMemberships($userId) === 0) {
						throw new \RuntimeException("Le dernier admin de l'organisation ne peut pas perdre ses droits.");
					}

					$saveResult = $membership->setOrganizationAdmin($isAdmin);
					if (!is_array($saveResult) || empty($saveResult['status'])) {
						throw new \RuntimeException("Le statut admin n'a pas pu être enregistré.");
					}
				} else {
					$link = new \dbObject\UserHolon();
					if (!$link->load(array(
						array('IDuser', $userId),
						array('IDholon', (int)$this->getId()),
					))) {
						if (!$isAdmin) {
							$pdo->commit();
							return array(
								'status' => true,
								'message' => 'Aucune mise à jour nécessaire.',
							);
						}

						$link->set('IDuser', $userId);
						$link->set('IDholon', (int)$this->getId());
						$link->set('active', true);
					} elseif (!(bool)$link->get('active')) {
						$link->set('active', true);
					}

					$saveResult = $link->setHolonAdmin($isAdmin);
					if (!is_array($saveResult) || empty($saveResult['status'])) {
						throw new \RuntimeException("Le statut admin n'a pas pu être enregistré.");
					}
				}
				$pdo->commit();
				return array(
					'status' => true,
					'message' => $isAdmin
						? 'Le statut admin a été accordé pour ce contexte.'
						: 'Le statut admin a été retiré pour ce contexte.',
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

		public function removeMember($userId, array $options = array())
		{
			$userId = (int)$userId;
			$options = array_merge(array(
				'organizationId' => $this->resolveOrganizationId(),
				'includeDescendants' => ((int)$this->get('IDtypeholon') !== 1),
			), $options);
			$organizationId = (int)$options['organizationId'];

			if (!$this->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas le droit de modifier ce contexte.",
				);
			}

			if ($userId <= 0 || $organizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Le membre ou le contexte est invalide.',
				);
			}

			$scopeHolonIdsForConstraintCheck = array();
			$visitedHolonIdsForConstraintCheck = array();
			$this->collectMemberScopeHolonIds((bool)$options['includeDescendants'], $scopeHolonIdsForConstraintCheck, $visitedHolonIdsForConstraintCheck);
			foreach (array_values(array_unique(array_map('intval', $scopeHolonIdsForConstraintCheck))) as $scopeHolonId) {
				if ($scopeHolonId <= 0) {
					continue;
				}

				$scopeHolon = (int)$this->getId() === $scopeHolonId ? $this : new self();
				if ($scopeHolon !== $this && !$scopeHolon->load($scopeHolonId)) {
					continue;
				}

				$adminConstraintResult = $scopeHolon->validateDirectMemberRemovalAdminBounds($userId, $organizationId);
				if (empty($adminConstraintResult['status'])) {
					return $adminConstraintResult;
				}
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'La connexion à la base de données est indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				$memberUser = new \dbObject\User();
				if (!$memberUser->load($userId)) {
					$memberUser->setId($userId);
				}

				$organizationMembership = new \dbObject\UserOrganization();
				$hasPendingOrganizationMembership = $organizationMembership->load(array(
					array('IDuser', $userId),
					array('IDorganization', $organizationId),
				)) && !(bool)$organizationMembership->get('active');

				$updatedLinkCount = 0;
				$removedHolonIds = array();
				$scopeHolonIds = array();
				$visitedHolonIds = array();
				$this->collectMemberScopeHolonIds((bool)$options['includeDescendants'], $scopeHolonIds, $visitedHolonIds);
				$scopeHolonIds = array_values(array_unique(array_filter(array_map('intval', $scopeHolonIds), function ($holonId) {
					return $holonId > 0;
				})));

				if (count($scopeHolonIds) > 0) {
					$placeholders = array();
					$params = array('user_id' => $userId);
					foreach ($scopeHolonIds as $index => $holonId) {
						$placeholder = 'holon_' . $index;
						$placeholders[] = ':' . $placeholder;
						$params[$placeholder] = $holonId;
					}

					$linkRows = \dbObject\DbObject::fetchAll(
						"SELECT id FROM user_holon WHERE IDuser = :user_id AND is_membership = 1 AND IDholon IN (" . implode(', ', $placeholders) . ")",
						$params
					);

					if ($linkRows !== false) {
						foreach ($linkRows as $row) {
							$linkId = (int)($row['id'] ?? 0);
							if ($linkId <= 0) {
								continue;
							}

							$link = new \dbObject\UserHolon();
							if (!$link->load($linkId)) {
								continue;
							}

							if ($hasPendingOrganizationMembership) {
								$removedHolonId = (int)$link->get('IDholon');
								if (!$link->delete()) {
									throw new \RuntimeException('Le membre ne peut pas etre retire de ce contexte.');
								}

								$updatedLinkCount += 1;
								if ($removedHolonId > 0) {
									$removedHolonIds[$removedHolonId] = $removedHolonId;
								}
								continue;
							}

							$link->set('active', false);
							$saveResult = $link->setHolonAdmin(false);
							if (!is_array($saveResult) || empty($saveResult['status'])) {
								throw new \RuntimeException("Le membre n'a pas pu être retiré de ce contexte.");
							}

							$updatedLinkCount += 1;
							$removedHolonId = (int)$link->get('IDholon');
							if ($removedHolonId > 0) {
								$removedHolonIds[$removedHolonId] = $removedHolonId;
							}
						}
					}
				}

				$membershipUpdated = false;
				if ($hasPendingOrganizationMembership) {
					$remainingLinkCount = self::fetchValue(
						"SELECT COUNT(*)
						 FROM user_holon uh
         INNER JOIN holon h ON h.id = uh.IDholon
						 WHERE uh.IDuser = :user_id
						   AND uh.is_membership = 1
						   AND h.IDorganization = :organization_id",
						array(
							'user_id' => $userId,
							'organization_id' => $organizationId,
						)
					);
					if ($remainingLinkCount === false) {
						throw new \RuntimeException('Les liens du membre ne peuvent pas etre verifies.');
					}
					$remainingLinkCount = (int)$remainingLinkCount;

					if ($remainingLinkCount === 0) {
						if (!$organizationMembership->delete()) {
							throw new \RuntimeException('Le membre ne peut pas etre retire de l organisation.');
						}

						$pendingInvitation = \dbObject\Invitation::findPendingForOrganizationUser($organizationId, $userId);
						if ($pendingInvitation instanceof \dbObject\Invitation && $pendingInvitation->isAdminInitiatedInvitation()) {
							$pendingInvitation->set('status', 'canceled');
							$pendingInvitation->set('dateresponse', new \DateTime());
							$pendingInvitation->set('active', false);
							$invitationSaveResult = $pendingInvitation->save();
							if (!is_array($invitationSaveResult) || empty($invitationSaveResult['status'])) {
								throw new \RuntimeException('L invitation ne peut pas etre annulee.');
							}
						}

						$membershipUpdated = true;
					}
				}

				if ($this->isOrganizationHolon() && $hasPendingOrganizationMembership && !$membershipUpdated) {
					if (!$organizationMembership->delete()) {
						throw new \RuntimeException('Le membre ne peut pas etre retire de l organisation.');
					}

					$membershipUpdated = true;
				} elseif ($this->isOrganizationHolon()) {
					$membership = new \dbObject\UserOrganization();
					if ($membership->load(array(
						array('IDuser', $userId),
						array('IDorganization', $organizationId),
					))) {
						$membership->set('active', false);
						$saveResult = $membership->setOrganizationAdmin(false);
						if (!is_array($saveResult) || empty($saveResult['status'])) {
							throw new \RuntimeException("Le membre n'a pas pu être retiré de l'organisation.");
						}

						$membershipUpdated = true;
					}
				}

				if ($updatedLinkCount === 0 && !$membershipUpdated) {
					throw new \RuntimeException("Aucun lien membre actif n'a été trouvé dans ce contexte.");
				}

				$scopeUpdateResult = \dbObject\Document::normalizeSelfScopedDocumentsForAuthorContext($organizationId, $userId);
				if (!is_array($scopeUpdateResult) || empty($scopeUpdateResult['status'])) {
					throw new \RuntimeException("Les portees des documents lies a ce membre n'ont pas pu etre mises a jour.");
				}

				$this->recordMemberRemovedHistory($memberUser, $organizationId, array_values($removedHolonIds), $membershipUpdated);

				$pdo->commit();
				return array(
					'status' => true,
					'message' => $this->isOrganizationHolon()
						? "Le membre a été retiré de l'organisation."
						: 'Le membre a été retiré de ce contexte.',
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

		protected function ensureOrganizationMembership(\dbObject\User $user, $organizationId, $isActive = true)
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

			$membership->set('active', (bool)$isActive);
			$saveResult = $membership->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("Impossible d'attacher cette personne à l'organisation.");
			}

			return $membership;
		}

		protected function ensureHolonMembership(\dbObject\User $user, $isActive = true, $focus = '')
		{
			if ((int)$user->getId() <= 0 || (int)$this->getId() <= 0) {
				throw new \RuntimeException('Le lien vers ce holon est invalide.');
			}

			$link = new \dbObject\UserHolon();
			if (!$link->load(array(
				array('IDuser', (int)$user->getId()),
				array('IDholon', (int)$this->getId()),
			))) {
				$link->set('IDuser', (int)$user->getId());
				$link->set('IDholon', (int)$this->getId());
			}

			$link->set('focus', trim((string)$focus));
			$link->set('active', (bool)$isActive);
			$saveResult = $link->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("Impossible d'attacher cette personne à ce holon.");
			}

			if (!$isActive) {
				$scopeUpdateResult = \dbObject\Document::normalizeSelfScopedDocumentsForAuthorContext($this->resolveOrganizationId(), (int)$user->getId());
				if (!is_array($scopeUpdateResult) || empty($scopeUpdateResult['status'])) {
					throw new \RuntimeException("Impossible de mettre a jour les documents de cette personne.");
				}
			}

			return $link;
		}

		protected function requiresInvitationForUser(\dbObject\User $user, $organizationId)
		{
			$membership = new \dbObject\UserOrganization();
			if ($membership->load(array(
				array('IDuser', (int)$user->getId()),
				array('IDorganization', (int)$organizationId),
			))) {
				return !(bool)$membership->get('active');
			}

			return true;
		}

		protected function hasActiveOrganizationMembership(\dbObject\User $user, $organizationId)
		{
			$membership = new \dbObject\UserOrganization();
			return $membership->load(array(
				array('IDuser', (int)$user->getId()),
				array('IDorganization', (int)$organizationId),
			)) && (bool)$membership->get('active');
		}

		protected function getHistoryTypeLabel()
		{
			switch ((int)$this->get('IDtypeholon')) {
				case 4:
					return 'organisation';
				case 3:
					return 'groupe';
				case 2:
					return 'cercle';
				case 1:
					return 'rôle';
				default:
					return 'holon';
			}
		}

		protected function getHistoryReferenceLabel()
		{
			$name = trim((string)$this->getDisplayName());
			$typeLabel = $this->getHistoryTypeLabel();

			if ($name === '') {
				return $typeLabel;
			}

			return $typeLabel . ' ' . $name;
		}

		protected function buildMemberHistoryAuthorData($organizationId)
		{
			$organizationId = (int)$organizationId;
			$authorUserId = (int)\commonGetCurrentUserId();
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

			return array(
				'userId' => $authorUserId,
				'label' => $authorLabel,
			);
		}

		protected function buildMemberHistoryUserLabel(\dbObject\User $memberUser, $organizationId)
		{
			$organizationId = (int)$organizationId;
			$memberLabel = trim((string)$memberUser->getScopedDisplayName($organizationId));
			if ($memberLabel === '') {
				$memberLabel = trim((string)$memberUser->get('email'));
			}
			if ($memberLabel === '') {
				$memberLabel = 'Utilisateur ' . (int)$memberUser->getId();
			}

			return $memberLabel;
		}

		protected function buildHistoryHolonReferenceToken($holonId)
		{
			$holonId = (int)$holonId;
			if ($holonId <= 0) {
				return '';
			}

			if ($holonId === (int)$this->getId()) {
				return \dbObject\History::buildReferenceToken('holon', $holonId, $this->getHistoryReferenceLabel());
			}

			$holon = new \dbObject\Holon();
			$holonLabel = 'Holon ' . $holonId;
			if ($holon->load($holonId)) {
				$holonLabel = $holon->getHistoryReferenceLabel();
			}

			return \dbObject\History::buildReferenceToken('holon', $holonId, $holonLabel);
		}

		protected function buildHistoryHolonReferenceList(array $holonIds)
		{
			$tokens = array();
			$seenHolonIds = array();

			foreach ($holonIds as $holonId) {
				$holonId = (int)$holonId;
				if ($holonId <= 0 || isset($seenHolonIds[$holonId])) {
					continue;
				}

				$seenHolonIds[$holonId] = true;
				$token = $this->buildHistoryHolonReferenceToken($holonId);
				if ($token !== '') {
					$tokens[] = $token;
				}
			}

			return $tokens;
		}

		protected function recordMemberAddedHistory(\dbObject\User $memberUser, $organizationId)
		{
			$organizationId = (int)$organizationId;
			$authorUserId = (int)\commonGetCurrentUserId();
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

			$content = \dbObject\History::buildReferenceToken('user', (int)$memberUser->getId(), $memberLabel)
				. ' a été ajouté au '
				. \dbObject\History::buildReferenceToken('holon', (int)$this->getId(), $this->getHistoryReferenceLabel())
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
					'IDholon' => (int)$this->getId(),
					'authorUserId' => $authorUserId,
				),
				(int)$this->getContainingCircleId(false)
			);

			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("L'historique de l'ajout n'a pas pu être enregistré.");
			}
		}

		protected function recordMemberRemovedHistory(\dbObject\User $memberUser, $organizationId, array $removedHolonIds = array(), $membershipUpdated = false)
		{
			$organizationId = (int)$organizationId;
			$authorData = $this->buildMemberHistoryAuthorData($organizationId);
			$authorUserId = (int)($authorData['userId'] ?? 0);
			$authorLabel = (string)($authorData['label'] ?? 'Utilisateur');
			$memberLabel = $this->buildMemberHistoryUserLabel($memberUser, $organizationId);
			$holonTokens = $this->buildHistoryHolonReferenceList($removedHolonIds);

			if (count($holonTokens) === 0) {
				$holonTokens[] = \dbObject\History::buildReferenceToken('holon', (int)$this->getId(), $this->getHistoryReferenceLabel());
			}

			if (count($holonTokens) === 1) {
				$content = \dbObject\History::buildReferenceToken('user', (int)$memberUser->getId(), $memberLabel)
					. ' a ete retire du '
					. $holonTokens[0]
					. ' par '
					. \dbObject\History::buildReferenceToken('user', $authorUserId, $authorLabel)
					. '.';
			} else {
				$content = \dbObject\History::buildReferenceToken('user', (int)$memberUser->getId(), $memberLabel)
					. ' a ete retire des holons suivants par '
					. \dbObject\History::buildReferenceToken('user', $authorUserId, $authorLabel)
					. ' : '
					. implode(', ', $holonTokens)
					. '.';
			}

			if (!empty($membershipUpdated) && $this->isOrganizationHolon()) {
				$content .= ' Son adhesion a aussi ete retiree de l organisation.';
			}

			$saveResult = \dbObject\History::createEntry(
				$organizationId,
				$authorUserId,
				'holon_member_removed',
				$content,
				array(
					'IDtargetuser' => (int)$memberUser->getId(),
					'IDholon' => (int)$this->getId(),
					'authorUserId' => $authorUserId,
					'removedHolonIds' => array_values(array_map('intval', $removedHolonIds)),
					'membershipUpdated' => !empty($membershipUpdated),
				),
				(int)$this->getContainingCircleId(false)
			);

			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("L'historique du retrait n'a pas pu etre enregistre.");
			}
		}

		protected function resolveMemberUser($userId = 0, $email = '')
		{
			$userId = (int)$userId;
			$email = trim(mb_strtolower((string)$email, 'UTF-8'));

			if ($userId > 0) {
				$user = new \dbObject\User();
				if (!$user->load($userId)) {
					throw new \RuntimeException('La personne sélectionnée est introuvable.');
				}

				return $user;
			}

			if ($email === '') {
				throw new \RuntimeException('Sélectionnez une personne ou saisissez une adresse e-mail.');
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
				throw new \RuntimeException("Le profil n'a pas pu être créé.");
			}

			return $user;
		}

		public function addMember($userId = 0, $email = '', array $options = array())
		{
			$isAdmin = !empty($options['isAdmin']);
			$focus = trim((string)($options['focus'] ?? ''));
			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
			if (!$this->userIsAllowed($currentUserId, 'CAN_ADD_MEMBER', false)) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas le droit d'ajouter un membre dans ce contexte.",
				);
			}
			if ($isAdmin && !$this->userIsAllowed($currentUserId, 'CAN_ADD_ADMIN', false)) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas le droit de gerer le statut admin dans ce contexte.",
				);
			}

			$organizationId = $this->resolveOrganizationId();
			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'message' => "L'organisation liée à ce holon est introuvable.",
				);
			}

			$pdo = \dbObject\DbObject::getPdo();
			if (!$pdo) {
				return array(
					'status' => false,
					'message' => 'La connexion à la base de données est indisponible.',
				);
			}

			try {
				$pdo->beginTransaction();

				$user = $this->resolveMemberUser($userId, $email);
				$adminConstraintResult = $this->validateMemberAdditionAdminBounds(
					(int)$user->getId(),
					$isAdmin,
					array(
						'organizationId' => $organizationId,
						'canAssignAdmin' => $this->userIsAllowed($currentUserId, 'CAN_ADD_ADMIN', false),
					)
				);
				if (empty($adminConstraintResult['status'])) {
					throw new \RuntimeException((string)($adminConstraintResult['message'] ?? 'Les contraintes d admins ne sont pas respectees.'));
				}

				$invitationIssue = array();
				$pendingInvitation = \dbObject\Invitation::findPendingForOrganizationUser($organizationId, (int)$user->getId());
				if ($isAdmin && $pendingInvitation instanceof \dbObject\Invitation) {
					$requestedAdminResult = $pendingInvitation->addRequestedHolonAdmin((int)$this->getId());
					if (!is_array($requestedAdminResult) || empty($requestedAdminResult['status'])) {
						throw new \RuntimeException("Le statut admin demande n'a pas pu etre memorise.");
					}
				}
				$hasActiveOrganizationMembership = $this->hasActiveOrganizationMembership($user, $organizationId);
				$requiresInvitation = !$hasActiveOrganizationMembership && !($pendingInvitation instanceof \dbObject\Invitation);
				$canApprovePendingRequest = $pendingInvitation instanceof \dbObject\Invitation && $pendingInvitation->isMemberInitiatedRequest();
				$keepsPendingInvitation = $pendingInvitation instanceof \dbObject\Invitation
					&& !$hasActiveOrganizationMembership
					&& !$canApprovePendingRequest;
				$isPendingAdd = $requiresInvitation || $keepsPendingInvitation;
				$organizationMembership = null;
				$holonMembership = null;

				if ($canApprovePendingRequest) {
					$approvalResult = $pendingInvitation->approveByAdmin([
						'approvedByUserId' => $currentUserId,
						'sendConfirmationEmail' => false,
					]);
					if (!($approvalResult['status'] ?? false)) {
						throw new \RuntimeException((string)($approvalResult['message'] ?? "L'ajout en attente n'a pas pu etre finalise."));
					}

					$this->ensureOrganizationMembership($user, $organizationId, true);
					$this->ensureHolonMembership($user, true, $focus);
				} elseif ($keepsPendingInvitation) {
					$this->ensureOrganizationMembership($user, $organizationId, false);
					$this->ensureHolonMembership($user, false, $focus);
				} elseif ($requiresInvitation) {
					$organizationMembership = $this->ensureOrganizationMembership($user, $organizationId, false);
					$holonMembership = $this->ensureHolonMembership($user, false, $focus);

					$invitationIssue = \dbObject\Invitation::issue(
						$organizationId,
						(int)$user->getId(),
						(int)\commonGetCurrentUserId(),
						trim((string)$user->get('email')),
						array(
							'holonAdminId' => $isAdmin ? (int)$this->getId() : 0,
						)
					);

					if (!empty($invitationIssue['created']) && isset($invitationIssue['invitation'])) {
						$invitationIssue['invitation']->sendEmail();
					}
				} else {
					$organizationMembership = $this->ensureOrganizationMembership($user, $organizationId, true);
					$holonMembership = $this->ensureHolonMembership($user, true, $focus);

					if ($isAdmin) {
						$saveResult = $this->isOrganizationHolon()
							? $organizationMembership->setOrganizationAdmin(true)
							: $holonMembership->setHolonAdmin(true);
						if (!is_array($saveResult) || empty($saveResult['status'])) {
							throw new \RuntimeException("Le statut admin n'a pas pu etre enregistre.");
						}
					}
				}

				$this->recordMemberAddedHistory($user, $organizationId);

				$pdo->commit();

				return array(
					'status' => true,
					'message' => $isPendingAdd
						? (
							!empty($invitationIssue['created'])
								? 'Invitation envoyée : ' . trim((string)$user->get('email'))
								: 'Ajout en attente de confirmation : ' . trim((string)$user->get('email'))
						)
						: (
							trim((string)$user->get('email')) !== ''
								? 'Membre ajouté : ' . trim((string)$user->get('email'))
								: 'Membre ajouté.'
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

		public function isTemplateNode($rootHolonId = 0)
		{
			$rootHolonId = (int)$rootHolonId;
			if ($rootHolonId > 0 && (int)$this->get('IDholon_org') !== $rootHolonId) {
				return false;
			}

			if (!(bool)$this->get('active')) {
				return false;
			}

			if (!(bool)$this->get('visible')) {
				return true;
			}

			return trim((string)$this->get('templatename')) !== '';
		}

		public function getTemplateChildren()
		{
			$children = new \dbObject\ArrayHolon();
			$children->load([
				"filter" => 'active = 1'
					. ' and IDholon_parent = ' . (int)$this->get("id")
					. ' and (visible = 0 or (templatename is not null and templatename != ""))',
				"orderBy" => [
					["field" => "IDtypeholon", "dir" => "ASC"],
					["field" => "templatename", "dir" => "ASC"],
					["field" => "name", "dir" => "ASC"],
					["field" => "id", "dir" => "ASC"],
				],
			]);

			return $children;
		}

		protected function finalizeTemplatePropertyDefinition(array $definition)
		{
			$definition['mandatory'] = !empty($definition['mandatory']);
			$definition['locked'] = !empty($definition['locked']);
			$definition['inheritedMandatory'] = !empty($definition['inheritedMandatory']);
			$definition['inheritedLocked'] = !empty($definition['inheritedLocked']);
			$definition['effectiveMandatory'] = $definition['inheritedMandatory'] || $definition['mandatory'];
			$definition['effectiveLocked'] = $definition['inheritedLocked'] || $definition['locked'];
			$definition['canDelete'] = !$definition['inheritedMandatory']
				&& $this->isAllowed('CAN_DELETE_TEMPLATE_PROPERTIES');
			$definition['canEditValue'] = !$definition['inheritedLocked']
				&& $this->isAllowed('CAN_EDIT_TEMPLATE_PROPERTIES');

			return $definition;
		}

		protected function parseTemplateListValue($rawValue)
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

		protected function mergeTemplateListValues($ancestorValue, $currentValue)
		{
			$merged = array();
			$seen = array();

			foreach (array_merge($this->parseTemplateListValue($ancestorValue), $this->parseTemplateListValue($currentValue)) as $item) {
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

		// Resout valeur visible
		protected function getTemplateDefinitionVisibleValue(array $definition)
		{
			$localValue = isset($definition['value']) ? (string)$definition['value'] : '';
			$inheritedValue = isset($definition['inheritedValue']) ? (string)$definition['inheritedValue'] : '';
			$isList = \dbObject\PropertyFormat::isListFormat((int)($definition['formatId'] ?? 0));
			$isLockedByAncestor = !empty($definition['inheritedLocked']);

			if ($isLockedByAncestor) {
				return $inheritedValue;
			}

			if ($isList) {
				return $this->mergeTemplateListValues($inheritedValue, $localValue);
			}

			$localValue = trim($localValue);
			if ($localValue !== '') {
				return $localValue;
			}

			return $inheritedValue;
		}

		// Construit definition propriete
		protected function buildTemplatePropertyDefinition(\dbObject\Property $property, ?\dbObject\HolonProperty $holonProperty = null, array $overrides = array())
		{
			$format = new \dbObject\PropertyFormat();
			$formatName = \dbObject\PropertyFormat::getBuiltinFormatName((int)$property->get('IDpropertyformat'));
			if ((int)$property->get('IDpropertyformat') > 0 && $format->load((int)$property->get('IDpropertyformat'))) {
				$formatName = (string)$format->get('name');
			}

			$localMandatory = $holonProperty ? (bool)$holonProperty->get('mandatory') : false;
			$localLocked = $holonProperty ? (bool)$holonProperty->get('locked') : false;

			$definition = array(
				'holonPropertyId' => $holonProperty ? (int)$holonProperty->getId() : 0,
				'id' => (int)$property->getId(),
				'name' => (string)$property->get('name'),
				'shortname' => (string)$property->get('shortname'),
				'formatId' => (int)$property->get('IDpropertyformat'),
				'formatName' => $formatName,
				'listItemType' => (string)$property->get('listitemtype'),
				'listHolonTypeIds' => \dbObject\Property::parseHolonTypeIds($property->get('listholontypeids')),
				'mandatory' => $localMandatory,
				'locked' => $localLocked,
				'inheritedMandatory' => false,
				'inheritedLocked' => false,
				'effectiveMandatory' => $localMandatory,
				'effectiveLocked' => $localLocked,
				'position' => (int)($holonProperty ? ($holonProperty->get('position') ?: $property->get('position') ?: 0) : ($property->get('position') ?: 0)),
				'value' => $holonProperty && $holonProperty->get('value') !== null ? (string)$holonProperty->get('value') : '',
				'inheritedValue' => '',
				'isInherited' => false,
				'isLocal' => true,
			);

			return $this->finalizeTemplatePropertyDefinition(array_merge($definition, $overrides));
		}

		// Charge proprietes locales
		protected function getLocalTemplateHolonProperties()
		{
			$holonProperties = new \dbObject\ArrayHolonProperty();
			$holonProperties->load([
				"where" => [
					["field" => "IDholon", "value" => $this->getId()],
				],
				"orderBy" => [
					["field" => "position", "dir" => "ASC"],
					["field" => "id", "dir" => "ASC"],
				],
			]);

			return $holonProperties;
		}

		// Agrege proprietes template
		public function getTemplatePropertyDefinitions()
		{
			$definitionsByPropertyId = array();
			$inheritedOrder = 0;

			$parentTemplateId = (int)$this->get('IDholon_template');
			if ($parentTemplateId > 0) {
				$parentTemplate = new self();
				if ($parentTemplate->load($parentTemplateId)) {
					foreach ($parentTemplate->getTemplatePropertyDefinitions() as $parentDefinition) {
						$propertyId = (int)($parentDefinition['id'] ?? 0);
						if ($propertyId <= 0) {
							continue;
						}

						$parentVisibleValue = $this->getTemplateDefinitionVisibleValue($parentDefinition);
						$parentDefinition['holonPropertyId'] = 0;
						$parentDefinition['inheritedValue'] = $parentVisibleValue;
						$parentDefinition['value'] = '';
						$parentDefinition['mandatory'] = false;
						$parentDefinition['locked'] = false;
						$parentDefinition['inheritedMandatory'] = !empty($parentDefinition['effectiveMandatory']);
						$parentDefinition['inheritedLocked'] = !empty($parentDefinition['effectiveLocked']);
						$parentDefinition['isInherited'] = true;
						$parentDefinition['isLocal'] = false;
						$parentDefinition['position'] = (int)($parentDefinition['position'] ?? (++$inheritedOrder));
						$definitionsByPropertyId[$propertyId] = $this->finalizeTemplatePropertyDefinition($parentDefinition);
						$inheritedOrder += 1;
					}
				}
			}

			foreach ($this->getLocalTemplateHolonProperties() as $holonProperty) {
				$property = new \dbObject\Property();
				if (!$property->load((int)$holonProperty->get('IDproperty'))) {
					continue;
				}

				$propertyId = (int)$property->getId();
				$isActive = (bool)$holonProperty->get('active');

				if (!$isActive) {
					unset($definitionsByPropertyId[$propertyId]);
					continue;
				}

				$isInherited = isset($definitionsByPropertyId[$propertyId]);
				$definition = $this->buildTemplatePropertyDefinition(
					$property,
					$holonProperty,
					array(
						'isInherited' => $isInherited,
						'isLocal' => true,
					)
				);

				if ($isInherited && isset($definitionsByPropertyId[$propertyId])) {
					$definition['inheritedValue'] = (string)($definitionsByPropertyId[$propertyId]['inheritedValue'] ?? $definitionsByPropertyId[$propertyId]['value'] ?? '');
					$definition['inheritedMandatory'] = !empty($definitionsByPropertyId[$propertyId]['effectiveMandatory']);
					$definition['inheritedLocked'] = !empty($definitionsByPropertyId[$propertyId]['effectiveLocked']);
					$definition['position'] = (int)($holonProperty->get('position') ?: $definitionsByPropertyId[$propertyId]['position'] ?: $property->get('position') ?: 0);
					$definition = $this->finalizeTemplatePropertyDefinition($definition);
				}

				$definitionsByPropertyId[$propertyId] = $definition;
			}

			$definitions = array_values($definitionsByPropertyId);
			usort($definitions, function ($left, $right) {
				$leftPosition = (int)($left['position'] ?? 0);
				$rightPosition = (int)($right['position'] ?? 0);
				if ($leftPosition === $rightPosition) {
					return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
				}

				return $leftPosition <=> $rightPosition;
			});

			return $definitions;
		}

		// Prepare proprietes creation
		public function getHolonCreationPropertyDefinitions()
		{
			$definitions = array();

			foreach ($this->getTemplatePropertyDefinitions() as $definition) {
				$effectiveMandatory = !empty($definition['effectiveMandatory']);
				$effectiveLocked = !empty($definition['effectiveLocked']);

				$definitions[] = array(
					'id' => (int)($definition['id'] ?? 0),
					'name' => (string)($definition['name'] ?? ''),
					'shortname' => (string)($definition['shortname'] ?? ''),
					'formatId' => (int)($definition['formatId'] ?? 0),
					'formatName' => (string)($definition['formatName'] ?? ''),
					'position' => (int)($definition['position'] ?? 0),
					'listItemType' => (string)($definition['listItemType'] ?? ''),
					'listHolonTypeIds' => \dbObject\Property::parseHolonTypeIds($definition['listHolonTypeIds'] ?? array()),
					'value' => '',
					'inheritedValue' => (string)$this->getTemplateDefinitionVisibleValue($definition),
					'inheritedMandatory' => $effectiveMandatory,
					'inheritedLocked' => $effectiveLocked,
					'effectiveMandatory' => $effectiveMandatory,
					'effectiveLocked' => $effectiveLocked,
					'canEditValue' => !empty($definition['canEditValue']),
				);
			}

			return $definitions;
		}

		// Prepare proprietes edition
		public function getHolonEditorPropertyDefinitions()
		{
			$definitionsByPropertyId = array();
			$templatePropertyIds = array();
			$templateAuthorityIdMap = $this->getTemplateAuthorityInstanceIdMap();
			// Les droits de propriete peuvent venir d un role et de son modele.
			// Cette edition doit donc utiliser le calcul courant, sans conserver une
			// ancienne portee en cache dans la session.
			$canEditHolonProperties = $this->isAllowed('CAN_EDIT_HOLON_PROPERTIES', false);

			$templateId = (int)$this->get('IDholon_template');
			if ($templateId > 0) {
				$template = new self();
				if ($template->load($templateId)) {
					foreach ($template->getHolonCreationPropertyDefinitions() as $definition) {
						$propertyId = (int)($definition['id'] ?? 0);
						if ($propertyId > 0) {
							// Une propriete uniquement heritee n a pas encore de ligne
							// holonproperty locale. Son droit doit neanmoins etre evalue
							// sur cette instance, et non sur le modele qui la definit.
							$definition['canEditValue'] = empty($definition['effectiveLocked'])
								&& $canEditHolonProperties;
							$definition['isTemplateProperty'] = true;
							$definition['isDirectProperty'] = false;
							$definition['canEditDefinition'] = false;
							$definition['canDelete'] = false;
							$definitionsByPropertyId[$propertyId] = $definition;
							$templatePropertyIds[$propertyId] = true;
						}
					}
				}
			}

			foreach ($this->getPropertiesValue() as $property) {
				$propertyId = (int)$property->get('IDproperty');
				if ($propertyId <= 0) {
					continue;
				}

				$localValue = $property->get('value');
				$inheritedValue = $property->get('value_parents');

				$definition = isset($definitionsByPropertyId[$propertyId]) ? $definitionsByPropertyId[$propertyId] : array(
					'id' => $propertyId,
					'name' => (string)$property->get('name'),
					'shortname' => (string)$property->get('shortname'),
					'formatId' => (int)$property->get('IDpropertyformat'),
					'formatName' => (string)$property->get('propertyformat_name'),
					'position' => (int)($property->get('effective_position') ?: 0),
					'listItemType' => (string)$property->get('listitemtype'),
					'listHolonTypeIds' => \dbObject\Property::parseHolonTypeIds($property->get('listholontypeids')),
					'value' => '',
					'inheritedValue' => '',
					'inheritedMandatory' => false,
					'inheritedLocked' => false,
					'effectiveMandatory' => false,
					'effectiveLocked' => false,
					'canEditValue' => true,
					'isTemplateProperty' => false,
					'isDirectProperty' => true,
					'canEditDefinition' => $this->isAllowed('CAN_EDIT_HOLON_PROPERTIES'),
					'canDelete' => $this->isAllowed('CAN_DELETE_HOLON_PROPERTIES'),
				);

				$definition['value'] = $localValue !== null ? (string)$localValue : '';
				$definition['inheritedValue'] = $inheritedValue !== null ? (string)$inheritedValue : (string)($definition['inheritedValue'] ?? '');
				if ((string)($definition['listItemType'] ?? '') === \dbObject\Property::LIST_ITEM_AUTHORITY && \dbObject\PropertyFormat::isListFormat((int)($definition['formatId'] ?? 0))) {
					$definition['inheritedValue'] = $this->remapTemplateAuthorityListValue(
						$definition['inheritedValue'],
						(int)$definition['formatId'],
						$templateAuthorityIdMap
					);
				}
				$definition['effectiveMandatory'] = (bool)$property->get('mandatory');
				$definition['effectiveLocked'] = (bool)$property->get('locked');
				// Les valeurs ajoutees ici restent locales au holon. Le droit HOLON
				// autorise donc leur edition, y compris pour une propriete definie
				// par le modele, sans rendre sa definition heritee modifiable.
				$definition['canEditValue'] = !((bool)$property->get('locked'))
					&& $canEditHolonProperties;
				$definition['isTemplateProperty'] = isset($templatePropertyIds[$propertyId]);
				$definition['isDirectProperty'] = !isset($templatePropertyIds[$propertyId]);
				$definition['canEditDefinition'] = !isset($templatePropertyIds[$propertyId])
					&& $this->isAllowed('CAN_EDIT_HOLON_PROPERTIES');
				$definition['canDelete'] = !isset($templatePropertyIds[$propertyId])
					&& $this->isAllowed('CAN_DELETE_HOLON_PROPERTIES');
				$definition['position'] = (int)($property->get('effective_position') ?: ($definition['position'] ?? 0));

				$definitionsByPropertyId[$propertyId] = $definition;
			}

			$definitions = array_values($definitionsByPropertyId);
			usort($definitions, function ($left, $right) {
				$leftPosition = (int)($left['position'] ?? 0);
				$rightPosition = (int)($right['position'] ?? 0);
				if ($leftPosition === $rightPosition) {
					return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
				}

				return $leftPosition <=> $rightPosition;
			});

			return $definitions;
		}

		public function syncDirectEditorPropertyDefinitions(array $definitions, $organizationRootId)
		{
			$organizationRootId = (int)$organizationRootId;
			$templatePropertyIds = array();
			$template = $this->getTemplateHolon();
			if ($template instanceof self) {
				foreach ($template->getHolonCreationPropertyDefinitions() as $templateDefinition) {
					$templatePropertyId = (int)($templateDefinition['id'] ?? 0);
					if ($templatePropertyId > 0) {
						$templatePropertyIds[$templatePropertyId] = true;
					}
				}
			}

			$resolvedDefinitions = array();
			foreach ($definitions as $definition) {
				if (!is_array($definition)) {
					continue;
				}

				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0 && isset($templatePropertyIds[$propertyId])) {
					continue;
				}

				$propertyName = trim((string)($definition['name'] ?? ''));
				$formatId = (int)($definition['formatId'] ?? 0);
				if ($propertyName === '' || $formatId <= 0) {
					continue;
				}

				$property = new \dbObject\Property();
				if ($propertyId > 0) {
					$existingHolonProperty = new \dbObject\HolonProperty();
					if (!$existingHolonProperty->load(array(
						array('IDholon', $this->getId()),
						array('IDproperty', $propertyId),
					))) {
						continue;
					}
					$property->load($propertyId);
				}
				if ($property->getId() > 0 && (int)$property->get('IDholon_organization') !== $organizationRootId) {
					continue;
				}
				if ($property->getId() <= 0) {
					$property->set('IDholon_organization', $organizationRootId);
				}

				$property->set('name', $propertyName);
				$property->set('shortname', trim((string)($definition['shortname'] ?? '')) !== '' ? $definition['shortname'] : \dbObject\Property::buildShortnameFromName($propertyName));
				$property->set('IDpropertyformat', $formatId);
				$listItemType = null;
				$listHolonTypeIds = null;
				if (\dbObject\PropertyFormat::isListFormat($formatId)) {
					$listItemType = \dbObject\Property::normalizeTemplateListItemType($definition['listItemType'] ?? '');
					if ($listItemType === \dbObject\Property::LIST_ITEM_HOLON) {
						$listHolonTypeIds = \dbObject\Property::serializeHolonTypeIds($definition['listHolonTypeIds'] ?? array());
					}
				}
				$property->set('listitemtype', $listItemType);
				$property->set('listholontypeids', $listHolonTypeIds);
				$property->set('position', (int)($definition['position'] ?? count($resolvedDefinitions) + 1));
				$property->set('active', true);
				$property->save();

				if ((int)$property->getId() <= 0) {
					continue;
				}

				$holonProperty = new \dbObject\HolonProperty();
				$holonProperty->load(array(
					array('IDholon', $this->getId()),
					array('IDproperty', $property->getId()),
				));
				$holonProperty->set('IDholon', $this->getId());
				$holonProperty->set('IDproperty', $property->getId());
				$holonProperty->set('position', (int)($definition['position'] ?? count($resolvedDefinitions) + 1));
				$holonProperty->set('mandatory', false);
				$holonProperty->set('locked', false);
				$holonProperty->set('active', true);
				$holonProperty->save();

				$definition['id'] = (int)$property->getId();
				$definition['isTemplateProperty'] = false;
				$definition['isDirectProperty'] = true;
				$resolvedDefinitions[] = $definition;
			}

			return $resolvedDefinitions;
		}

		// Synchronise valeurs locales
		public function syncEditorPropertyValues(array $submittedValuesByPropertyId, array $propertyDefinitions)
		{
			$definitionsByPropertyId = array();
			foreach ($propertyDefinitions as $definition) {
				$propertyId = (int)($definition['id'] ?? 0);
				if ($propertyId > 0) {
					$definitionsByPropertyId[$propertyId] = $definition;
				}
			}

			$existingByPropertyId = array();
			foreach ($this->getHolonProperties() as $holonProperty) {
				$existingByPropertyId[(int)$holonProperty->get('IDproperty')] = $holonProperty;
			}

			foreach ($definitionsByPropertyId as $propertyId => $definition) {
				if (!empty($definition['effectiveLocked'])) {
					continue;
				}

				$formatId = (int)($definition['formatId'] ?? 0);
				$localValue = $submittedValuesByPropertyId[$propertyId] ?? '';
				$localValue = \dbObject\PropertyFormat::normalizeValueForStorage($formatId, $localValue);
				if (!\dbObject\PropertyFormat::isHtmlFormat($formatId)) {
					$localValue = trim((string)$localValue);
				}
				$holonProperty = isset($existingByPropertyId[$propertyId]) ? $existingByPropertyId[$propertyId] : new \dbObject\HolonProperty();

				if (\dbObject\PropertyFormat::isEmptyValue($formatId, $localValue)) {
					if (!empty($definition['isDirectProperty'])) {
						$holonProperty->set('IDholon', $this->getId());
						$holonProperty->set('IDproperty', $propertyId);
						$holonProperty->set('position', (int)($definition['position'] ?? 0));
						$holonProperty->set('mandatory', false);
						$holonProperty->set('locked', false);
						$holonProperty->set('active', true);
						$holonProperty->set('value', null);
						$holonProperty->save();
						continue;
					}
					if ($holonProperty->getId() > 0) {
						$holonProperty->set('active', false);
						$holonProperty->set('value', null);
						$holonProperty->save();
					}
					continue;
				}

				$holonProperty->set('IDholon', $this->getId());
				$holonProperty->set('IDproperty', $propertyId);
				$holonProperty->set('value', $localValue);
				$holonProperty->set('position', (int)($definition['position'] ?? 0));
				$holonProperty->set('mandatory', false);
				$holonProperty->set('locked', false);
				$holonProperty->set('active', true);
				$holonProperty->save();
			}

			foreach ($existingByPropertyId as $propertyId => $holonProperty) {
				if (isset($definitionsByPropertyId[$propertyId])) {
					continue;
				}

				$holonProperty->set('active', false);
				$holonProperty->set('value', null);
				$holonProperty->save();
			}
		}

		public function toTemplateEditorArray($rootHolonId = 0)
		{
			$children = array();
			$inheritedAdminTemplate = $this->getTemplateHolon();
			$inheritedAdminBounds = $inheritedAdminTemplate
				? $inheritedAdminTemplate->getEffectiveTemplateAdminBounds()
				: array('min' => 0, 'max' => null, 'minLocked' => false, 'maxLocked' => false);
			$effectiveAdminBounds = $this->getEffectiveTemplateAdminBounds();
			foreach ($this->getTemplateChildren() as $child) {
				$children[] = $child->toTemplateEditorArray($rootHolonId);
			}

			return array(
				'id' => (int)$this->getId(),
				'name' => $this->getDisplayName(),
				'typeId' => (int)$this->get('IDtypeholon'),
				'typeLabel' => $this->getTypeLabel(),
				'color' => (string)$this->get('color'),
				'icon' => (string)$this->get('icon'),
				'inheritedIcon' => $this->getInheritedIcon(),
				'effectiveIcon' => $this->getEffectiveIcon(),
				'banner' => (string)$this->get('banner'),
				'inheritedBanner' => $this->getInheritedBanner(),
				'effectiveBanner' => $this->getEffectiveBanner(),
				'visible' => (bool)$this->get('visible'),
				'mandatory' => (bool)$this->get('mandatory'),
				'lockedName' => (bool)$this->get('lockedname'),
				'lockedIcon' => (bool)$this->get('lockedicon'),
				'inheritedLockedIcon' => $this->isIconLockedByTemplate(),
				'effectiveLockedIcon' => (bool)$this->get('lockedicon') || $this->isIconLockedByTemplate(),
				'lockedBanner' => (bool)$this->get('lockedbanner'),
				'inheritedLockedBanner' => $this->isBannerLockedByTemplate(),
				'effectiveLockedBanner' => (bool)$this->get('lockedbanner') || $this->isBannerLockedByTemplate(),
				'unique' => (bool)$this->get('unique'),
				'link' => (bool)$this->get('link'),
				'adminParent' => (bool)$this->get('adminparent'),
				'adminMin' => $this->get('admin_min') === null ? null : max(0, (int)$this->get('admin_min')),
				'adminMax' => $this->get('admin_max') === null ? null : (int)$this->get('admin_max'),
				'lockedAdminMin' => (bool)$this->get('lockedadminmin'),
				'lockedAdminMax' => (bool)$this->get('lockedadminmax'),
				'inheritedAdminMin' => $inheritedAdminBounds['min'],
				'inheritedAdminMax' => $inheritedAdminBounds['max'],
				'inheritedLockedAdminMin' => !empty($inheritedAdminBounds['minLocked']),
				'inheritedLockedAdminMax' => !empty($inheritedAdminBounds['maxLocked']),
				'effectiveAdminMin' => $effectiveAdminBounds['min'],
				'effectiveAdminMax' => $effectiveAdminBounds['max'],
				'effectiveLockedAdminMin' => !empty($effectiveAdminBounds['minLocked']),
				'effectiveLockedAdminMax' => !empty($effectiveAdminBounds['maxLocked']),
				'adminMinOverride' => (bool)$this->get('adminminoverride'),
				'adminMaxOverride' => (bool)$this->get('adminmaxoverride'),
				'parentId' => (int)$this->get('IDholon_parent'),
				'inheritsFromId' => (int)$this->get('IDholon_template'),
				'rootHolonId' => (int)$rootHolonId,
				'permissionAssignments' => \dbObject\HolonPermission::getAssignmentKeyMapForHolon((int)$this->getId()),
				'properties' => $this->getTemplatePropertyDefinitions(),
				'children' => $children,
			);
		}

		public function toTemplateEditorNodeArray($rootHolonId = 0)
		{
			$inheritedAdminTemplate = $this->getTemplateHolon();
			$inheritedAdminBounds = $inheritedAdminTemplate
				? $inheritedAdminTemplate->getEffectiveTemplateAdminBounds()
				: array('min' => 0, 'max' => null, 'minLocked' => false, 'maxLocked' => false);
			$effectiveAdminBounds = $this->getEffectiveTemplateAdminBounds();

			return array(
				'id' => (int)$this->getId(),
				'name' => $this->getDisplayName(),
				'typeId' => (int)$this->get('IDtypeholon'),
				'typeLabel' => $this->getTypeLabel(),
				'color' => (string)$this->get('color'),
				'icon' => (string)$this->get('icon'),
				'inheritedIcon' => $this->getInheritedIcon(),
				'effectiveIcon' => $this->getEffectiveIcon(),
				'banner' => (string)$this->get('banner'),
				'inheritedBanner' => $this->getInheritedBanner(),
				'effectiveBanner' => $this->getEffectiveBanner(),
				'visible' => (bool)$this->get('visible'),
				'mandatory' => (bool)$this->get('mandatory'),
				'lockedName' => (bool)$this->get('lockedname'),
				'lockedIcon' => (bool)$this->get('lockedicon'),
				'inheritedLockedIcon' => $this->isIconLockedByTemplate(),
				'effectiveLockedIcon' => (bool)$this->get('lockedicon') || $this->isIconLockedByTemplate(),
				'lockedBanner' => (bool)$this->get('lockedbanner'),
				'inheritedLockedBanner' => $this->isBannerLockedByTemplate(),
				'effectiveLockedBanner' => (bool)$this->get('lockedbanner') || $this->isBannerLockedByTemplate(),
				'unique' => (bool)$this->get('unique'),
				'link' => (bool)$this->get('link'),
				'adminParent' => (bool)$this->get('adminparent'),
				'adminMin' => $this->get('admin_min') === null ? null : max(0, (int)$this->get('admin_min')),
				'adminMax' => $this->get('admin_max') === null ? null : (int)$this->get('admin_max'),
				'lockedAdminMin' => (bool)$this->get('lockedadminmin'),
				'lockedAdminMax' => (bool)$this->get('lockedadminmax'),
				'inheritedAdminMin' => $inheritedAdminBounds['min'],
				'inheritedAdminMax' => $inheritedAdminBounds['max'],
				'inheritedLockedAdminMin' => !empty($inheritedAdminBounds['minLocked']),
				'inheritedLockedAdminMax' => !empty($inheritedAdminBounds['maxLocked']),
				'effectiveAdminMin' => $effectiveAdminBounds['min'],
				'effectiveAdminMax' => $effectiveAdminBounds['max'],
				'effectiveLockedAdminMin' => !empty($effectiveAdminBounds['minLocked']),
				'effectiveLockedAdminMax' => !empty($effectiveAdminBounds['maxLocked']),
				'adminMinOverride' => (bool)$this->get('adminminoverride'),
				'adminMaxOverride' => (bool)$this->get('adminmaxoverride'),
				'parentId' => (int)$this->get('IDholon_parent'),
				'inheritsFromId' => (int)$this->get('IDholon_template'),
				'rootHolonId' => (int)$rootHolonId,
				'permissionAssignments' => \dbObject\HolonPermission::getAssignmentKeyMapForHolon((int)$this->getId()),
				'properties' => $this->getTemplatePropertyDefinitions(),
				'children' => array(),
			);
		}

		public function syncTemplateProperties(array $definitions, $organizationRootId)
		{
			$organizationRootId = (int)$organizationRootId;
			$retainedHolonPropertyIds = array();
			$submittedPropertyIds = array();
			$inheritedDefinitionsById = array();

			$parentTemplateId = (int)$this->get('IDholon_template');
			if ($parentTemplateId > 0) {
				$parentTemplate = new self();
				if ($parentTemplate->load($parentTemplateId)) {
					foreach ($parentTemplate->getTemplatePropertyDefinitions() as $parentDefinition) {
						$parentPropertyId = (int)($parentDefinition['id'] ?? 0);
						if ($parentPropertyId > 0) {
							$inheritedDefinitionsById[$parentPropertyId] = $parentDefinition;
						}
					}
				}
			}

			foreach (array_values($definitions) as $index => $definition) {
				$propertyName = trim((string)($definition['name'] ?? ''));
				$propertyFormatId = (int)($definition['formatId'] ?? 0);
				if ($propertyName === '' || $propertyFormatId <= 0) {
					continue;
				}

				$propertyId = (int)($definition['id'] ?? 0);
				$isInheritedDefinition = $propertyId > 0 && isset($inheritedDefinitionsById[$propertyId]);
				$submittedPropertyIds[$propertyId] = true;

				if ($isInheritedDefinition) {
					$inheritedDefinition = $inheritedDefinitionsById[$propertyId];
					$holonProperty = new \dbObject\HolonProperty();
					$holonPropertyId = (int)($definition['holonPropertyId'] ?? 0);
					if ($holonPropertyId > 0) {
						$holonProperty->load($holonPropertyId);
					}

					if ($holonProperty->getId() <= 0) {
						$holonProperty->load([
							['IDholon', $this->getId()],
							['IDproperty', $propertyId],
						]);
					}

					$definitionValue = \dbObject\PropertyFormat::normalizeValueForStorage(
						$propertyFormatId,
						array_key_exists('value', $definition) ? $definition['value'] : ''
					);
					$inheritedValue = \dbObject\PropertyFormat::normalizeValueForStorage(
						$propertyFormatId,
						(string)($inheritedDefinition['value'] ?? '')
					);
					$inheritedLocked = !empty($inheritedDefinition['effectiveLocked']);
					$localMandatory = !empty($definition['mandatory']);
					$localLocked = !empty($definition['locked']);
					$canEditValue = !$inheritedLocked;

					if (!$canEditValue) {
						$definitionValue = $inheritedValue;
					}

					if (!\dbObject\PropertyFormat::isHtmlFormat($propertyFormatId)) {
						$definitionValue = trim((string)$definitionValue);
						$inheritedValue = trim((string)$inheritedValue);
					}

					$normalizedValue = \dbObject\PropertyFormat::isEmptyValue($propertyFormatId, $definitionValue)
						? null
						: $definitionValue;
					$hasLocalOverride = $canEditValue && $normalizedValue !== null;

					$holonProperty->set('IDholon', $this->getId());
					$holonProperty->set('IDproperty', $propertyId);
					$holonProperty->set('value', $hasLocalOverride ? $normalizedValue : null);
					$holonProperty->set('position', $index + 1);
					$holonProperty->set('mandatory', $localMandatory);
					$holonProperty->set('locked', $localLocked);
					$holonProperty->set('active', true);
					$holonProperty->save();

					if ($holonProperty->getId() > 0) {
						$retainedHolonPropertyIds[$holonProperty->getId()] = true;
					}

					continue;
				}

				$property = new \dbObject\Property();
				if ($propertyId > 0) {
					$property->load($propertyId);
				}

				if ($property->getId() > 0 && (int)$property->get('IDholon_organization') !== $organizationRootId) {
					$property = new \dbObject\Property();
				}

				if ($property->getId() <= 0) {
					$property->set('IDholon_organization', $organizationRootId);
				}

				$property->set('name', $propertyName);
				$property->set('shortname', trim((string)($definition['shortname'] ?? '')) !== '' ? $definition['shortname'] : \dbObject\Property::buildShortnameFromName($propertyName));
				$property->set('IDpropertyformat', $propertyFormatId);
				$listItemType = null;
				$listHolonTypeIds = null;
				if (\dbObject\PropertyFormat::isListFormat($propertyFormatId)) {
					$listItemType = \dbObject\Property::normalizeTemplateListItemType($definition['listItemType'] ?? '');
					if ($listItemType === \dbObject\Property::LIST_ITEM_HOLON) {
						$listHolonTypeIds = \dbObject\Property::serializeHolonTypeIds($definition['listHolonTypeIds'] ?? array());
					}
				}
				$property->set('listitemtype', $listItemType);
				$property->set('listholontypeids', $listHolonTypeIds);
				$property->set('position', $index + 1);
				$property->set('active', true);
				$property->save();

				$holonProperty = new \dbObject\HolonProperty();
				$holonPropertyId = (int)($definition['holonPropertyId'] ?? 0);
				if ($holonPropertyId > 0) {
					$holonProperty->load($holonPropertyId);
				}

				if ($holonProperty->getId() <= 0 && $property->getId() > 0) {
					$holonProperty->load([
						['IDholon', $this->getId()],
						['IDproperty', $property->getId()],
					]);
				}

				$definitionValue = \dbObject\PropertyFormat::normalizeValueForStorage(
					$propertyFormatId,
					array_key_exists('value', $definition) ? $definition['value'] : ''
				);
				if (!\dbObject\PropertyFormat::isHtmlFormat($propertyFormatId)) {
					$definitionValue = trim((string)$definitionValue);
				}

				$normalizedValue = \dbObject\PropertyFormat::isEmptyValue($propertyFormatId, $definitionValue)
					? null
					: $definitionValue;
				$localMandatory = !empty($definition['mandatory']);
				$localLocked = !empty($definition['locked']);
				$holonProperty->set('IDholon', $this->getId());
				$holonProperty->set('IDproperty', $property->getId());
				$holonProperty->set('value', $normalizedValue);
				$holonProperty->set('position', $index + 1);
				$holonProperty->set('mandatory', $localMandatory);
				$holonProperty->set('locked', $localLocked);
				$holonProperty->set('active', true);
				$holonProperty->save();

				if ($holonProperty->getId() > 0) {
					$retainedHolonPropertyIds[$holonProperty->getId()] = true;
				}
			}

			foreach ($inheritedDefinitionsById as $propertyId => $inheritedDefinition) {
				if (isset($submittedPropertyIds[$propertyId])) {
					continue;
				}

				if (!empty($inheritedDefinition['effectiveMandatory'])) {
					continue;
				}

				$suppression = new \dbObject\HolonProperty();
				$suppression->load([
					['IDholon', $this->getId()],
					['IDproperty', $propertyId],
				]);
				$suppression->set('IDholon', $this->getId());
				$suppression->set('IDproperty', $propertyId);
				$suppression->set('value', null);
				$suppression->set('position', (int)($inheritedDefinition['position'] ?? 0));
				$suppression->set('mandatory', false);
				$suppression->set('locked', false);
				$suppression->set('active', false);
				$suppression->save();
			}

			$existingHolonProperties = new \dbObject\ArrayHolonProperty();
			$existingHolonProperties->load([
				"where" => [
					["field" => "IDholon", "value" => $this->getId()],
				],
			]);

			foreach ($existingHolonProperties as $existingHolonProperty) {
				if (isset($retainedHolonPropertyIds[$existingHolonProperty->getId()])) {
					continue;
				}

				$propertyId = (int)$existingHolonProperty->get('IDproperty');
				if (isset($inheritedDefinitionsById[$propertyId]) && !isset($submittedPropertyIds[$propertyId])) {
					continue;
				}

				$existingHolonProperty->set('active', false);
				$existingHolonProperty->save();
			}
		}

		// Retourne tous les enfants (uniquement pour les orga
		public function getChildren() {

			$children=new \dbObject\ArrayHolon();
			$children->load([
				"where" => [
					["field" => "active", "value" => 1],
					["field" => "visible", "value" => 1],
					["field" => "IDholon_parent", "value" => $this->get("id")],
				],
			]);

			return $children;	
	
		}

		// Charge enfants suppression
		protected function getDeletionChildren()
		{
			$children = new \dbObject\ArrayHolon();
			$children->load([
				"where" => [
					["field" => "IDholon_parent", "value" => $this->get("id")],
				],
				"orderBy" => [
					["field" => "id", "dir" => "ASC"],
				],
			]);

			return $children;
		}

		// Compte enfants visibles
		public function countVisibleDescendants()
		{
			$count = 0;

			foreach ($this->getChildren() as $child) {
				$count += 1;
				$count += $child->countVisibleDescendants();
			}

			return $count;
		}

		public function getVisibleDescendantIds($includeSelf = true)
		{
			$ids = array();

			if ($includeSelf) {
				$ids[] = (int)$this->getId();
			}

			foreach ($this->getChildren() as $child) {
				$ids = array_merge($ids, $child->getVisibleDescendantIds(true));
			}

			return array_values(array_unique(array_map('intval', $ids)));
		}

		// Supprime holon r?cursif
		public function delete()
		{
			foreach ($this->getDeletionChildren() as $child) {
				if (!$child->delete()) {
					return false;
				}
			}

			$parentHolon = $this->getParentHolon();
			if ($parentHolon instanceof self) {
				$authorityTransfer = Authority::reassignForHolonDeletion($this, $parentHolon);
				if (empty($authorityTransfer['status'])) {
					return false;
				}
			}

			foreach ($this->getHolonProperties() as $property) {
				if (!$property->delete()) {
					return false;
				}
			}

			return parent::delete();
		}

		public function disableAllProperty() {
			$query="update holonproperty set active=0 where IDholon=0".$this->get("id");
			$this->executeSQL($query);
		}

		// Desactive tous les enfants
		public function disableAllChildren() {
			foreach ($this->getAllChildren() as $children) {
				$children->set("active",false);
				$children->save();
			}					
			
		}
		
	}
	
?>
