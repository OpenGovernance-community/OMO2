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
				[['name','shortname','domain'], 'string'],	// Chaines de caractere
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
			} elseif (count($parts) >= 3 && in_array((string)($parts[count($parts) - 3] ?? ''), array('dev', 'beta'), true)) {
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
					'listItemTypes' => \dbObject\Property::getTemplateListItemTypeOptions(),
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
				'listItemTypes' => \dbObject\Property::getTemplateListItemTypeOptions(),
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
					'definedInId' => (int)$template->get('IDholon_parent'),
					'definedInName' => $definitionHolonName,
					'definedInLabel' => $definitionHolonLabel,
					'properties' => $template->getTemplatePropertyDefinitions(),
				), $this->getHolonIllustrationData($template));
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

			return array(
				'status' => true,
				'message' => 'Modele enregistre.',
				'template' => $template->toTemplateEditorArray((int)$rootHolon->getId()),
				'data' => $this->getHolonTemplateEditorData((int)$contextHolon->getId()),
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
		
	}
	
?>
