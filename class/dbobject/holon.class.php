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
				[['id'], 'integer'],					
				[['name','templatename','accesskey'], 'string'],			// Texte libre
				[['datecreation','datemodification'], 'datetime'],	// Date avec precision des heures
				[['IDuser','IDtypeholon','IDholon_parent','IDholon_template','IDorganization','IDholon_org'], 'fk'],				// Cle etrangeres
				[['lockedname','active','visible','mandatory','unique','link'], 'boolean'],				// Cle etrangeres
				[['color'], 'color'],				// Couleur au format hexadecimal
				[['id'], 'safe'],								// Champs proteges (n'apparaissent pas dans les formulaires)
			];
		}
		
		// Defini les labels standarts pour cet objet, affiches dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
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
				'accesskey' => 'Cle acces',
				'mandatory' => 'Obligatoire ?',
				'lockedname' => 'Nom verrouille ?',
				'unique' => 'Unique ?',
				'link' => 'Lien ?',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
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

		public function canEdit() {
			$currentUserId = (int)\commonGetCurrentUserId();
			if ($currentUserId <= 0) {
				return false;
			}

			$organizationId = $this->resolveOrganizationId();
			if ($organizationId <= 0) {
				return false;
			}

			$user = new \dbObject\User();
			return $user->load($currentUserId) && $user->hasOrganizationAccess($organizationId);
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

		// Verifie template obligatoire
		public function isMandatoryTemplateInstance()
		{
			$template = $this->getTemplateHolon();
			return $template ? (bool)$template->get('mandatory') : false;
		}

		// Verifie nom verrouille
		public function isNameLockedByTemplate()
		{
			$template = $this->getTemplateHolon();
			return $template ? (bool)$template->get('lockedname') : false;
		}

		// Compte instances soeurs
		public function countSiblingTemplateInstances()
		{
			$templateId = (int)$this->get('IDholon_template');
			$parentHolon = $this->getParentHolon();
			if ($templateId <= 0 || !$parentHolon) {
				return 0;
			}

			$count = 0;
			foreach ($parentHolon->getChildren() as $child) {
				if ((int)$child->getId() === (int)$this->getId()) {
					continue;
				}

				if ((int)$child->get('IDholon_template') !== $templateId) {
					continue;
				}

				$count += 1;
			}

			return $count;
		}

		// Controle suppression noeud
		public function canDelete()
		{
			if (!$this->canEdit()) {
				return false;
			}

			if ($this->isMandatoryTemplateInstance() && $this->countSiblingTemplateInstances() === 0) {
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
			return (bool)$property->get('locked') && $this->propertyHasInheritedDefinition($property);
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

		public function getPropertyEntries(array $options = array()) {
			$keyPrefix = isset($options['propertyKeyPrefix']) ? (string)$options['propertyKeyPrefix'] : 'd';
			$entries = array();

			foreach ($this->getPropertiesValue() as $property) {
				$value = $this->shouldHideLocalPropertyValue($property) ? null : $property->get('value');
				$ancestor = $property->get('value_parents');
				$effectiveValue = null;

				if ($value !== null && trim((string)$value) !== '') {
					$effectiveValue = (string)$value;
				} elseif ($ancestor !== null && trim((string)$ancestor) !== '') {
					$effectiveValue = (string)$ancestor;
				}

				$entries[] = array(
					'id' => (int)$property->get('IDproperty'),
					'key' => $keyPrefix . $property->get('IDproperty'),
					'shortname' => (string)$property->get('shortname'),
					'name' => (string)$property->get('name'),
					'position' => (int)($property->get('effective_position') ?: $property->get('position') ?: 0),
					'formatId' => (int)$property->get('IDpropertyformat'),
					'formatName' => (string)$property->get('propertyformat_name'),
					'listItemType' => (string)$property->get('listitemtype'),
					'listHolonTypeIds' => \dbObject\Property::parseHolonTypeIds($property->get('listholontypeids')),
					'mandatory' => (bool)$property->get('mandatory'),
					'locked' => (bool)$property->get('locked'),
					'value' => $value !== null ? (string)$value : '',
					'ancestor' => $ancestor !== null ? (string)$ancestor : '',
					'effectiveValue' => $effectiveValue !== null ? $effectiveValue : '',
				);
			}

			return $entries;
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

		protected static function buildMemberSortKey($value)
		{
			$value = trim(mb_strtolower((string)$value, 'UTF-8'));
			$transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
			if (is_string($transliterated) && $transliterated !== '') {
				$value = $transliterated;
			}

			return preg_replace('/[^a-z0-9]+/', ' ', $value);
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
				$child->collectMemberScopeHolonIds(true, $bucket, $visited);
			}
		}

		protected function loadVisibleMemberLinkRows(array $holonIds, $organizationId)
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
				'inv_accepted_organization_id' => $organizationId,
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
					uh.active AS holon_active,
					CASE
						WHEN uh.active = 1 THEN 1
						WHEN COALESCE(uo.active, 0) = 1 AND inv_accepted.id IS NOT NULL THEN 1
						ELSE 0
					END AS holon_effective_active,
					COALESCE(uo.active, 0) AS organization_active,
					CASE
						WHEN inv.id IS NULL THEN 0
						ELSE 1
					END AS has_pending_invitation,
					CASE
						WHEN inv_accepted.id IS NULL THEN 0
						ELSE 1
					END AS has_accepted_invitation
				FROM user_holon uh
				INNER JOIN `user` u ON u.id = uh.IDuser
				LEFT JOIN user_organization uo
					ON uo.IDuser = uh.IDuser
					AND uo.IDorganization = :uo_organization_id
				LEFT JOIN invitation inv
					ON inv.IDorganization = :inv_pending_organization_id
					AND inv.IDuser = uh.IDuser
					AND inv.status = 'pending'
					AND inv.active = 1
					AND (inv.dateexpiration IS NULL OR inv.dateexpiration > NOW())
				LEFT JOIN invitation inv_accepted
					ON inv_accepted.IDorganization = :inv_accepted_organization_id
					AND inv_accepted.IDuser = uh.IDuser
					AND inv_accepted.status = 'accepted'
				WHERE uh.IDholon IN (" . implode(', ', $placeholders) . ")
				  AND (
					uh.active = 1
					OR inv.id IS NOT NULL
					OR (
						COALESCE(uo.active, 0) = 1
						AND inv_accepted.id IS NOT NULL
					)
				  )
				ORDER BY
					COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					u.id ASC,
					uh.IDholon ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			if ($rows !== false) {
				return $rows;
			}

			$fallbackQuery = "
				SELECT DISTINCT
					uh.IDuser AS user_id,
					uh.IDholon AS holon_id,
					uh.active AS holon_active,
					uh.active AS holon_effective_active,
					1 AS organization_active,
					0 AS has_pending_invitation,
					0 AS has_accepted_invitation
				FROM user_holon uh
				INNER JOIN `user` u ON u.id = uh.IDuser
				WHERE uh.IDholon IN (" . implode(', ', $placeholders) . ")
				  AND uh.active = 1
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
			return $rows !== false ? $rows : array();
		}

		public function getAssociatedMemberCards(array $options = array())
		{
			$options = array_merge(array(
				'organizationId' => $this->resolveOrganizationId(),
				'includeDescendants' => ((int)$this->get('IDtypeholon') !== 1),
			), $options);

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

				if (!isset($cardsByUserId[$userId])) {
					$link = new \dbObject\UserHolon();
					$link->set('IDuser', $userId);
					$cardsByUserId[$userId] = array(
						'userId' => $userId,
						'displayName' => $link->getUserDisplayName((int)$options['organizationId']),
						'photoUrl' => $link->getProfilePhotoUrl((int)$options['organizationId']),
						'initials' => $link->getUserInitials((int)$options['organizationId']),
						'holonIds' => array(),
						'isPending' => false,
					);
				}

				$linkedHolonId = (int)($row['holon_id'] ?? 0);
				if ($linkedHolonId > 0 && !in_array($linkedHolonId, $cardsByUserId[$userId]['holonIds'], true)) {
					$cardsByUserId[$userId]['holonIds'][] = $linkedHolonId;
				}

				if (
					!(bool)($row['holon_effective_active'] ?? ($row['holon_active'] ?? false))
					|| !(bool)($row['organization_active'] ?? false)
					|| (bool)($row['has_pending_invitation'] ?? false)
				) {
					$cardsByUserId[$userId]['isPending'] = true;
				}
			}

			$cards = array_values($cardsByUserId);
			usort($cards, static function (array $left, array $right) {
				return strcmp(
					self::buildMemberSortKey($left['displayName'] ?? ''),
					self::buildMemberSortKey($right['displayName'] ?? '')
				);
			});

			return $cards;
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

		protected function ensureHolonMembership(\dbObject\User $user, $isActive = true)
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

			$link->set('active', (bool)$isActive);
			$saveResult = $link->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("Impossible d'attacher cette personne à ce holon.");
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
				)
			);

			if (!is_array($saveResult) || empty($saveResult['status'])) {
				throw new \RuntimeException("L'historique de l'ajout n'a pas pu être enregistré.");
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

		public function addMember($userId = 0, $email = '')
		{
			if (!$this->canEdit()) {
				return array(
					'status' => false,
					'message' => "Vous n'avez pas le droit de modifier ce holon.",
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
				$requiresInvitation = $this->requiresInvitationForUser($user, $organizationId);

				if ($requiresInvitation) {
					$this->ensureOrganizationMembership($user, $organizationId, false);
					$this->ensureHolonMembership($user, false);

					$invitationIssue = \dbObject\Invitation::issue(
						$organizationId,
						(int)$user->getId(),
						(int)\commonGetCurrentUserId(),
						trim((string)$user->get('email'))
					);

					if (!empty($invitationIssue['created']) && isset($invitationIssue['invitation'])) {
						$invitationIssue['invitation']->sendEmail();
					}
				} else {
					$this->ensureOrganizationMembership($user, $organizationId, true);
					$this->ensureHolonMembership($user, true);
				}

				$this->recordMemberAddedHistory($user, $organizationId);

				$pdo->commit();

				return array(
					'status' => true,
					'message' => $requiresInvitation
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
			$definition['canDelete'] = !$definition['inheritedMandatory'];
			$definition['canEditValue'] = !$definition['inheritedLocked'];

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
			$isList = (int)($definition['formatId'] ?? 0) === \dbObject\PropertyFormat::FORMAT_LIST;
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
			$formatName = '';
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
					'canEditValue' => !$effectiveLocked,
				);
			}

			return $definitions;
		}

		// Prepare proprietes edition
		public function getHolonEditorPropertyDefinitions()
		{
			$definitionsByPropertyId = array();

			$templateId = (int)$this->get('IDholon_template');
			if ($templateId > 0) {
				$template = new self();
				if ($template->load($templateId)) {
					foreach ($template->getHolonCreationPropertyDefinitions() as $definition) {
						$propertyId = (int)($definition['id'] ?? 0);
						if ($propertyId > 0) {
							$definitionsByPropertyId[$propertyId] = $definition;
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
				);

				$definition['value'] = $localValue !== null ? (string)$localValue : '';
				$definition['inheritedValue'] = $inheritedValue !== null ? (string)$inheritedValue : (string)($definition['inheritedValue'] ?? '');
				$definition['effectiveMandatory'] = (bool)$property->get('mandatory');
				$definition['effectiveLocked'] = (bool)$property->get('locked');
				$definition['canEditValue'] = !((bool)$property->get('locked'));
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

				$localValue = $submittedValuesByPropertyId[$propertyId] ?? '';
				$localValue = is_scalar($localValue) ? trim((string)$localValue) : '';
				$holonProperty = isset($existingByPropertyId[$propertyId]) ? $existingByPropertyId[$propertyId] : new \dbObject\HolonProperty();

				if ($localValue === '') {
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
			foreach ($this->getTemplateChildren() as $child) {
				$children[] = $child->toTemplateEditorArray($rootHolonId);
			}

			return array(
				'id' => (int)$this->getId(),
				'name' => $this->getDisplayName(),
				'typeId' => (int)$this->get('IDtypeholon'),
				'typeLabel' => $this->getTypeLabel(),
				'color' => (string)$this->get('color'),
				'visible' => (bool)$this->get('visible'),
				'mandatory' => (bool)$this->get('mandatory'),
				'lockedName' => (bool)$this->get('lockedname'),
				'unique' => (bool)$this->get('unique'),
				'link' => (bool)$this->get('link'),
				'parentId' => (int)$this->get('IDholon_parent'),
				'inheritsFromId' => (int)$this->get('IDholon_template'),
				'rootHolonId' => (int)$rootHolonId,
				'properties' => $this->getTemplatePropertyDefinitions(),
				'children' => $children,
			);
		}

		public function toTemplateEditorNodeArray($rootHolonId = 0)
		{
			return array(
				'id' => (int)$this->getId(),
				'name' => $this->getDisplayName(),
				'typeId' => (int)$this->get('IDtypeholon'),
				'typeLabel' => $this->getTypeLabel(),
				'color' => (string)$this->get('color'),
				'visible' => (bool)$this->get('visible'),
				'mandatory' => (bool)$this->get('mandatory'),
				'lockedName' => (bool)$this->get('lockedname'),
				'unique' => (bool)$this->get('unique'),
				'link' => (bool)$this->get('link'),
				'parentId' => (int)$this->get('IDholon_parent'),
				'inheritsFromId' => (int)$this->get('IDholon_template'),
				'rootHolonId' => (int)$rootHolonId,
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

					$definitionValue = array_key_exists('value', $definition) ? (string)$definition['value'] : '';
					$inheritedValue = (string)($inheritedDefinition['value'] ?? '');
					$inheritedLocked = !empty($inheritedDefinition['effectiveLocked']);
					$localMandatory = !empty($definition['mandatory']);
					$localLocked = !empty($definition['locked']);
					$canEditValue = !$inheritedLocked;

					if (!$canEditValue) {
						$definitionValue = $inheritedValue;
					}

					$normalizedValue = trim($definitionValue) === '' ? null : $definitionValue;
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
				if ($propertyFormatId === \dbObject\PropertyFormat::FORMAT_LIST) {
					$listItemType = \dbObject\Property::normalizeListItemType($definition['listItemType'] ?? '');
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

				$definitionValue = array_key_exists('value', $definition) ? (string)$definition['value'] : '';
				$normalizedValue = trim($definitionValue) === '' ? null : $definitionValue;
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

		// Supprime holon r?cursif
		public function delete()
		{
			foreach ($this->getDeletionChildren() as $child) {
				if (!$child->delete()) {
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
