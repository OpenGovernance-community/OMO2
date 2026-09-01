<?php
	namespace dbObject;

	class ObjectVisibility extends DbObject
	{
		const OBJECT_TYPE_DOCUMENT = 'document';

		const TYPE_EVERYONE = 'everyone';
		const TYPE_ORGANIZATION = 'organization';
		const TYPE_CIRCLE = 'circle';
		const TYPE_ROLE = 'role';
		const TYPE_SELF = 'self';

		public static function tableName()
		{
			return 'object_visibility';
		}

		public static function rules()
		{
			return [
				[['id', 'version', 'object_id'], 'integer'],
				[['object_type', 'visibility_type'], 'string'],
				[['active'], 'boolean'],
				[['datecreation', 'datemodification'], 'datetime'],
				[['IDorganization', 'IDholon'], 'fk'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'version' => 'Version',
				'object_type' => 'Type d objet',
				'object_id' => 'ID objet',
				'IDorganization' => 'Organisation',
				'visibility_type' => 'Portee de visibilite',
				'IDholon' => 'Holon cible',
				'active' => 'Actif',
				'datecreation' => 'Date de creation',
				'datemodification' => 'Date de modification',
			];
		}

		public static function attributeLength()
		{
			return [
				'object_type' => 60,
				'visibility_type' => 30,
			];
		}

		public static function getOrder()
		{
			return 'datemodification DESC, id DESC';
		}

		public static function getDefaultVisibilityType(): string
		{
			return self::TYPE_ORGANIZATION;
		}

		public static function getVisibilityTypeOptions(): array
		{
			return [
				self::TYPE_EVERYONE => 'Tout le monde',
				self::TYPE_ORGANIZATION => 'Organisation',
				self::TYPE_CIRCLE => 'Cercle',
				self::TYPE_ROLE => 'Role',
				self::TYPE_SELF => 'Propriétaire uniquement',
			];
		}

		public static function getVisibilityTypeDescriptions(): array
		{
			return [
				self::TYPE_EVERYONE => 'Visible ou editable depuis l exterieur, sans appartenance a l organisation.',
				self::TYPE_ORGANIZATION => 'Reserve aux membres de l organisation.',
				self::TYPE_CIRCLE => 'Reserve aux membres du cercle concerne.',
				self::TYPE_ROLE => 'Reserve aux membres du role concerne.',
				self::TYPE_SELF => 'Reserve a la personne proprietaire du document.',
			];
		}

		public static function normalizeVisibilityType($visibilityType): string
		{
			$visibilityType = trim(mb_strtolower((string)$visibilityType, 'UTF-8'));

			return array_key_exists($visibilityType, self::getVisibilityTypeOptions())
				? $visibilityType
				: self::getDefaultVisibilityType();
		}

		public static function requiresHolonTarget($visibilityType): bool
		{
			$visibilityType = self::normalizeVisibilityType($visibilityType);
			return in_array($visibilityType, [self::TYPE_CIRCLE, self::TYPE_ROLE], true);
		}

		public static function getObjectKey($objectType): string
		{
			$objectType = trim(mb_strtolower((string)$objectType, 'UTF-8'));
			return preg_replace('/[^a-z0-9_]+/', '_', $objectType) ?: 'object';
		}

		public static function resolveRuleInput(int $organizationId, $visibilityType, $holonId = null): array
		{
			$organizationId = (int)$organizationId;
			$visibilityType = self::normalizeVisibilityType($visibilityType);
			$holonId = $holonId !== null ? (int)$holonId : 0;

			if ($organizationId <= 0) {
				return [
					'status' => false,
					'text' => 'Organisation invalide.',
				];
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return [
					'status' => false,
					'text' => 'Organisation introuvable.',
				];
			}

			if (!self::requiresHolonTarget($visibilityType)) {
				return [
					'status' => true,
					'type' => $visibilityType,
					'holonId' => null,
				];
			}

			if ($holonId <= 0) {
				return [
					'status' => false,
					'text' => 'Le holon de visibilite est obligatoire.',
				];
			}

			$holon = new \dbObject\Holon();
			if (
				!$holon->load($holonId)
				|| !(bool)$holon->get('active')
				|| !(bool)$holon->get('visible')
				|| !$organization->containsHolon($holon)
			) {
				return [
					'status' => false,
					'text' => 'Holon de visibilite introuvable pour cette organisation.',
				];
			}

			$requiredTypeId = $visibilityType === self::TYPE_CIRCLE ? 2 : 1;
			if ((int)$holon->get('IDtypeholon') !== $requiredTypeId) {
				return [
					'status' => false,
					'text' => $visibilityType === self::TYPE_CIRCLE
						? 'La visibilite cercle attend un cercle.'
						: 'La visibilite role attend un role.',
				];
			}

			return [
				'status' => true,
				'type' => $visibilityType,
				'holonId' => (int)$holon->getId(),
			];
		}

		public static function saveSingleRule($objectType, $objectId, $organizationId, $visibilityType, $holonId = null)
		{
			$objectType = self::getObjectKey($objectType);
			$objectId = (int)$objectId;
			$organizationId = (int)$organizationId;
			$resolvedRule = self::resolveRuleInput($organizationId, $visibilityType, $holonId);

			if (($resolvedRule['status'] ?? false) !== true) {
				return $resolvedRule;
			}

			if ($objectType === '' || $objectId <= 0) {
				return [
					'status' => false,
					'text' => 'Objet de visibilite invalide.',
				];
			}

			$now = new \DateTimeImmutable();
			$deactivateResult = self::execute(
				"
					UPDATE `" . self::tableName() . "`
					SET `active` = 0, `datemodification` = :datemodification
					WHERE `object_type` = :object_type
					  AND `object_id` = :object_id
					  AND `active` = 1
				",
				[
					'datemodification' => $now,
					'object_type' => $objectType,
					'object_id' => $objectId,
				]
			);

			if ($deactivateResult === false) {
				return [
					'status' => false,
					'text' => 'Impossible de mettre a jour les anciennes regles de visibilite.',
				];
			}

			$rule = new self();
			$rule->set('object_type', $objectType);
			$rule->set('object_id', $objectId);
			$rule->set('IDorganization', $organizationId);
			$rule->set('visibility_type', (string)$resolvedRule['type']);
			$rule->set('IDholon', isset($resolvedRule['holonId']) && (int)$resolvedRule['holonId'] > 0
				? (int)$resolvedRule['holonId']
				: null);
			$rule->set('active', true);
			$rule->set('datecreation', $now);
			$rule->set('datemodification', $now);

			$saveResult = $rule->save();
			if (!is_array($saveResult) || ($saveResult['status'] ?? false) !== true) {
				return [
					'status' => false,
					'text' => trim((string)($saveResult['text'] ?? 'Impossible de sauvegarder la visibilite.')),
				];
			}

			return [
				'status' => true,
				'text' => 'Visibilite enregistree.',
				'ruleId' => (int)$rule->getId(),
			];
		}

		public static function loadActiveRuleRow($objectType, $objectId, $organizationId = 0)
		{
			$rows = self::loadActiveRuleRows($objectType, [(int)$objectId], $organizationId);
			$objectId = (int)$objectId;
			return $rows[$objectId] ?? null;
		}

		public static function loadActiveRuleRows($objectType, array $objectIds, $organizationId = 0): array
		{
			$objectType = self::getObjectKey($objectType);
			$organizationId = (int)$organizationId;
			$objectIds = array_values(array_unique(array_filter(array_map('intval', $objectIds), static function ($objectId) {
				return $objectId > 0;
			})));

			if ($objectType === '' || count($objectIds) === 0) {
				return [];
			}

			$params = [
				'object_type' => $objectType,
			];
			$placeholders = [];
			foreach ($objectIds as $index => $objectId) {
				$placeholder = 'object_id_' . $index;
				$params[$placeholder] = $objectId;
				$placeholders[] = ':' . $placeholder;
			}

			$whereOrganization = '';
			if ($organizationId > 0) {
				$whereOrganization = ' AND `IDorganization` = :organization_id';
				$params['organization_id'] = $organizationId;
			}

			$rows = self::fetchAll(
				"
					SELECT
						`id`,
						`object_type`,
						`object_id`,
						`IDorganization`,
						`visibility_type`,
						`IDholon`,
						`active`,
						`datecreation`,
						`datemodification`
					FROM `" . self::tableName() . "`
					WHERE `object_type` = :object_type
					  AND `object_id` IN (" . implode(', ', $placeholders) . ")
					  AND `active` = 1
					  " . $whereOrganization . "
					ORDER BY `datemodification` DESC, `id` DESC
				",
				$params
			);

			if ($rows === false) {
				return [];
			}

			$ruleMap = [];
			foreach ($rows as $row) {
				$resolvedObjectId = (int)($row['object_id'] ?? 0);
				if ($resolvedObjectId <= 0 || isset($ruleMap[$resolvedObjectId])) {
					continue;
				}

				$ruleMap[$resolvedObjectId] = $row;
			}

			return $ruleMap;
		}

		public static function buildFallbackRuleData($organizationId = 0, ?string $defaultVisibilityType = null): array
		{
			$visibilityType = $defaultVisibilityType !== null
				? self::normalizeVisibilityType($defaultVisibilityType)
				: self::TYPE_ORGANIZATION;

			return [
				'id' => 0,
				'object_type' => '',
				'object_id' => 0,
				'IDorganization' => (int)$organizationId,
				'visibility_type' => $visibilityType,
				'IDholon' => null,
				'active' => 1,
			];
		}

		public static function buildCurrentViewerContext(int $organizationId, ?int $viewerUserId = null): array
		{
			$organizationId = (int)$organizationId;
			$shareLink = function_exists('commonGetCurrentShareLink')
				? \commonGetCurrentShareLink()
				: null;
			$userId = $viewerUserId !== null
				? (int)$viewerUserId
				: (function_exists('commonGetCurrentUserId')
					? (int)\commonGetCurrentUserId()
					: (int)($_SESSION['currentUser'] ?? 0));

			return [
				'organizationId' => $organizationId,
				'type' => $shareLink ? 'share' : ($userId > 0 ? 'user' : ''),
				'userId' => $userId,
				'hasAdminOverride' => !$shareLink
					&& $userId > 0
					&& function_exists('commonUserHasAdminOverride')
					? \commonUserHasAdminOverride($userId, $organizationId)
					: false,
				'shareLink' => $shareLink,
				'roleHolonIds' => null,
				'circleHolonIds' => null,
			];
		}

		protected static function viewerHasOrganizationAccess(array $viewerContext, int $organizationId): bool
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return false;
			}

			if ((string)($viewerContext['type'] ?? '') === 'share') {
				$shareLink = $viewerContext['shareLink'] ?? null;
				return $shareLink instanceof \dbObject\HolonShareLink
					? $shareLink->canViewOrganization($organizationId)
					: false;
			}

			if (function_exists('commonUserHasOrganizationAccess')) {
				return \commonUserHasOrganizationAccess((int)($viewerContext['userId'] ?? 0), $organizationId);
			}

			$userId = (int)($viewerContext['userId'] ?? 0);
			if ($userId <= 0) {
				return false;
			}

			$membershipCount = self::fetchValue(
				"
					SELECT COUNT(*)
					FROM `user_organization`
					WHERE `IDorganization` = :organization_id
					  AND `IDuser` = :user_id
					  AND `active` = 1
				",
				[
					'organization_id' => $organizationId,
					'user_id' => $userId,
				]
			);

			return (int)$membershipCount > 0;
		}

		protected static function populateViewerMembershipScope(array &$viewerContext): void
		{
			if (is_array($viewerContext['roleHolonIds'] ?? null) && is_array($viewerContext['circleHolonIds'] ?? null)) {
				return;
			}

			$viewerContext['roleHolonIds'] = [];
			$viewerContext['circleHolonIds'] = [];

			if ((string)($viewerContext['type'] ?? '') !== 'user') {
				return;
			}

			$userId = (int)($viewerContext['userId'] ?? 0);
			$organizationId = (int)($viewerContext['organizationId'] ?? 0);
			if ($userId <= 0 || $organizationId <= 0) {
				return;
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return;
			}

			$rootHolon = $organization->getEnabledStructuralRootHolon();
			$params = [
				'user_id' => $userId,
				'organization_id' => $organizationId,
			];
			$whereOrganization = 'h.`IDorganization` = :organization_id';
			if ($rootHolon) {
				$params['root_holon_id'] = (int)$rootHolon->getId();
				$whereOrganization = '(h.`IDorganization` = :organization_id OR h.`IDholon_org` = :root_holon_id)';
			}

			$rows = self::fetchAll(
				"
					SELECT DISTINCT
						h.`id`,
						h.`IDtypeholon`
					FROM `user_holon` uh
					INNER JOIN `holon` h
						ON h.`id` = uh.`IDholon`
					WHERE uh.`IDuser` = :user_id
					  AND uh.`active` = 1
					  AND uh.`is_membership` = 1
					  AND h.`active` = 1
					  AND h.`visible` = 1
					  AND " . $whereOrganization . "
					ORDER BY h.`id` ASC
				",
				$params
			);

			if ($rows === false) {
				return;
			}

			foreach ($rows as $row) {
				$holonId = (int)($row['id'] ?? 0);
				if ($holonId <= 0) {
					continue;
				}

				$holon = new \dbObject\Holon();
				if (!$holon->load($holonId)) {
					continue;
				}

				if ((int)$holon->get('IDtypeholon') === 1) {
					$viewerContext['roleHolonIds'][$holonId] = $holonId;
				}

				foreach ($holon->getPathHolons(true) as $pathHolon) {
					if ((int)$pathHolon->get('IDtypeholon') !== 2) {
						continue;
					}

					$viewerContext['circleHolonIds'][(int)$pathHolon->getId()] = (int)$pathHolon->getId();
				}
			}
		}

		protected static function shareCanViewTargetHolon(array $viewerContext, int $targetHolonId): bool
		{
			$targetHolonId = (int)$targetHolonId;
			$shareLink = $viewerContext['shareLink'] ?? null;
			if ($targetHolonId <= 0 || !($shareLink instanceof \dbObject\HolonShareLink)) {
				return false;
			}

			$targetHolon = new \dbObject\Holon();
			return $targetHolon->load($targetHolonId) && $shareLink->canViewHolon($targetHolon);
		}

		public static function viewerCanAccessRule($ruleRow, array &$viewerContext, array $objectContext = []): bool
		{
			$organizationId = (int)($objectContext['organizationId'] ?? ($viewerContext['organizationId'] ?? 0));
			if (!self::viewerHasOrganizationAccess($viewerContext, $organizationId)) {
				return false;
			}

			if (!empty($viewerContext['hasAdminOverride'])) {
				return true;
			}

			$ruleRow = is_array($ruleRow)
				? $ruleRow
				: self::buildFallbackRuleData($organizationId);
			$visibilityType = self::normalizeVisibilityType($ruleRow['visibility_type'] ?? self::TYPE_ORGANIZATION);
			$targetHolonId = (int)($ruleRow['IDholon'] ?? 0);

			switch ($visibilityType) {
				case self::TYPE_EVERYONE:
				case self::TYPE_ORGANIZATION:
					return true;

				case self::TYPE_SELF:
					return (string)($viewerContext['type'] ?? '') === 'user'
						&& (int)($viewerContext['userId'] ?? 0) > 0
						&& (int)($objectContext['ownerUserId'] ?? 0) === (int)($viewerContext['userId'] ?? 0);

				case self::TYPE_ROLE:
					if ((string)($viewerContext['type'] ?? '') === 'share') {
						return self::shareCanViewTargetHolon($viewerContext, $targetHolonId);
					}

					self::populateViewerMembershipScope($viewerContext);
					return isset($viewerContext['roleHolonIds'][$targetHolonId]);

				case self::TYPE_CIRCLE:
					if ((string)($viewerContext['type'] ?? '') === 'share') {
						return self::shareCanViewTargetHolon($viewerContext, $targetHolonId);
					}

					self::populateViewerMembershipScope($viewerContext);
					return isset($viewerContext['circleHolonIds'][$targetHolonId]);

				default:
					return true;
			}
		}

		public static function buildDisplayData($ruleRow, int $organizationId = 0, array $objectContext = []): array
		{
			$ruleRow = is_array($ruleRow)
				? $ruleRow
				: self::buildFallbackRuleData($organizationId);
			$visibilityType = self::normalizeVisibilityType($ruleRow['visibility_type'] ?? self::TYPE_ORGANIZATION);
			$targetHolonId = (int)($ruleRow['IDholon'] ?? 0);
			$typeLabels = self::getVisibilityTypeOptions();
			$typeLabel = (string)($typeLabels[$visibilityType] ?? $typeLabels[self::TYPE_ORGANIZATION]);

			if ($visibilityType === self::TYPE_SELF) {
				$ownerLabel = trim((string)($objectContext['ownerLabel'] ?? ''));
				if ($ownerLabel !== '') {
					$typeLabel = $ownerLabel . ' uniquement';
				}
			}

			$targetLabel = '';

			if ($targetHolonId > 0 && self::requiresHolonTarget($visibilityType)) {
				$holon = new \dbObject\Holon();
				if ($holon->load($targetHolonId)) {
					$targetLabel = trim((string)$holon->getDisplayName());
				}
			}

			$badgeText = $targetLabel !== '' ? ($typeLabel . ' : ' . $targetLabel) : $typeLabel;

			return [
				'type' => $visibilityType,
				'typeLabel' => $typeLabel,
				'targetHolonId' => $targetHolonId,
				'targetLabel' => $targetLabel,
				'badgeText' => $badgeText,
				'className' => 'omo-visibility-badge omo-visibility-badge--' . $visibilityType,
			];
		}
	}
