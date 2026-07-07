<?php
	namespace dbObject;

	class History extends DbObject
	{
	    public static function tableName()
		{
			return 'history';
		}

		public static function rules()
		{
			return [
				[['id'], 'integer'],
				[['IDorganization', 'IDuser', 'IDholon_circle'], 'fk'],
				[['action'], 'string'],
				[['content'], 'text'],
				[['parameters'], 'parameters'],
				[['datecreation'], 'datetime'],
				[['active'], 'boolean'],
				[['id', 'datecreation'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDuser' => 'Auteur',
				'IDholon_circle' => 'Cercle rattache',
				'action' => 'Action',
				'content' => 'Contenu',
				'parameters' => 'Paramètres',
				'datecreation' => 'Date',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'content' => "Texte historisé, indexable en full text, avec références typées comme [user|1|Nom].",
			];
		}

		public static function attributeLength()
		{
			return [
				'action' => 100,
			];
		}

		public static function getOrder()
		{
			return "datecreation DESC, id DESC";
		}

		public static function sanitizeReferenceLabel($label)
		{
			$label = trim((string)$label);
			$label = preg_replace('/[\[\]\|]+/u', ' ', $label);
			$label = preg_replace('/\s+/u', ' ', $label);

			return trim((string)$label);
		}

		public static function buildReferenceToken($type, $id, $label)
		{
			$type = trim(mb_strtolower((string)$type, 'UTF-8'));
			$id = (int)$id;
			$label = self::sanitizeReferenceLabel($label);

			return '[' . $type . '|' . $id . '|' . $label . ']';
		}

		public static function createEntry($organizationId, $authorUserId, $action, $content, array $parameters = array(), $circleHolonId = 0)
		{
			$entry = new self();
			$entry->set('IDorganization', (int)$organizationId > 0 ? (int)$organizationId : null);
			$entry->set('IDuser', (int)$authorUserId > 0 ? (int)$authorUserId : null);
			$entry->set('IDholon_circle', (int)$circleHolonId > 0 ? (int)$circleHolonId : null);
			$entry->set('action', trim((string)$action));
			$entry->set('content', trim((string)$content));
			$entry->set('parameters', count($parameters) > 0 ? $parameters : null);
			$entry->set('active', true);

			return $entry->save();
		}

		public static function buildHolonSearchNeedle($holonId)
		{
			return '[holon|' . (int)$holonId . '|';
		}

		public static function renderReferenceText($content, $organizationId = 0)
		{
			$content = trim((string)$content);
			if ($content === '') {
				return '';
			}

			$segments = self::parseReferenceSegments($content);
			$parts = array();

			foreach ($segments as $segment) {
				if (($segment['kind'] ?? '') === 'text') {
					$parts[] = (string)($segment['text'] ?? '');
					continue;
				}

				$resolved = self::resolveReferenceDisplay(
					(string)($segment['type'] ?? ''),
					(int)($segment['id'] ?? 0),
					(string)($segment['label'] ?? ''),
					(int)$organizationId
				);
				$parts[] = (string)($resolved['text'] ?? '');
			}

			return implode('', $parts);
		}

		public static function renderReferenceHtml($content, $organizationId = 0, array $options = array())
		{
			$content = trim((string)$content);
			if ($content === '') {
				return '';
			}

			$options = array_merge(array(
				'linkHolons' => true,
				'linkUsers' => false,
			), $options);

			$segments = self::parseReferenceSegments($content);
			$htmlParts = array();

			foreach ($segments as $segment) {
				if (($segment['kind'] ?? '') === 'text') {
					$htmlParts[] = htmlspecialchars((string)($segment['text'] ?? ''), ENT_QUOTES, 'UTF-8');
					continue;
				}

				$resolved = self::resolveReferenceDisplay(
					(string)($segment['type'] ?? ''),
					(int)($segment['id'] ?? 0),
					(string)($segment['label'] ?? ''),
					(int)$organizationId
				);

				$referenceText = htmlspecialchars((string)($resolved['text'] ?? ''), ENT_QUOTES, 'UTF-8');
				$referenceType = (string)($resolved['type'] ?? '');
				$referenceId = (int)($resolved['id'] ?? 0);

				if (
					$referenceType === 'holon'
					&& !empty($options['linkHolons'])
					&& !empty($resolved['isLinkable'])
					&& $referenceId > 0
				) {
					$htmlParts[] = '<a href="#" class="omo-history-reference omo-history-reference--holon omo-history-reference--link"'
						. ' data-omo-history-holon-id="' . $referenceId . '">'
						. $referenceText
						. '</a>';
					continue;
				}

				if (
					$referenceType === 'user'
					&& !empty($options['linkUsers'])
					&& !empty($resolved['isLinkable'])
					&& $referenceId > 0
				) {
					$htmlParts[] = '<a href="#" class="omo-history-reference omo-history-reference--user omo-history-reference--link"'
						. ' data-omo-history-user-id="' . $referenceId . '">'
						. $referenceText
						. '</a>';
					continue;
				}

				$htmlParts[] = '<span class="omo-history-reference omo-history-reference--'
					. htmlspecialchars($referenceType !== '' ? $referenceType : 'text', ENT_QUOTES, 'UTF-8')
					. '">' . $referenceText . '</span>';
			}

			return implode('', $htmlParts);
		}

		protected static function parseReferenceSegments($content)
		{
			$content = (string)$content;
			if ($content === '') {
				return array();
			}

			$segments = array();
			$matches = array();
			$hasMatches = preg_match_all(
				'/\[([^\|\]]+)\|([0-9]+)\|([^\]]*)\]/u',
				$content,
				$matches,
				PREG_OFFSET_CAPTURE
			);

			if ($hasMatches < 1 || empty($matches[0])) {
				return array(
					array(
						'kind' => 'text',
						'text' => $content,
					),
				);
			}

			$cursor = 0;
			$totalMatches = count($matches[0]);
			for ($index = 0; $index < $totalMatches; $index++) {
				$fullMatch = (string)($matches[0][$index][0] ?? '');
				$matchOffset = (int)($matches[0][$index][1] ?? 0);
				if ($matchOffset > $cursor) {
					$segments[] = array(
						'kind' => 'text',
						'text' => substr($content, $cursor, $matchOffset - $cursor),
					);
				}

				$segments[] = array(
					'kind' => 'reference',
					'type' => trim((string)($matches[1][$index][0] ?? '')),
					'id' => (int)($matches[2][$index][0] ?? 0),
					'label' => trim((string)($matches[3][$index][0] ?? '')),
				);
				$cursor = $matchOffset + strlen($fullMatch);
			}

			if ($cursor < strlen($content)) {
				$segments[] = array(
					'kind' => 'text',
					'text' => substr($content, $cursor),
				);
			}

			return $segments;
		}

		protected static function normalizeHolonTypeLabel($typeId = 0, $typeLabel = '')
		{
			switch ((int)$typeId) {
				case 4:
					return 'organisation';
				case 3:
					return 'groupe';
				case 2:
					return 'cercle';
				case 1:
					return 'rôle';
			}

			$typeLabel = trim((string)$typeLabel);
			if ($typeLabel === '') {
				return 'holon';
			}

			$typeLabel = mb_strtolower($typeLabel, 'UTF-8');

			return $typeLabel === 'role' ? 'rôle' : $typeLabel;
		}

		protected static function isKnownHolonTypeLabel($typeLabel)
		{
			$typeLabel = self::normalizeHolonTypeLabel(0, $typeLabel);

			return in_array($typeLabel, array('organisation', 'groupe', 'cercle', 'rôle', 'holon'), true);
		}

		protected static function parseHolonReferenceLabel($label)
		{
			$label = trim((string)$label);
			$result = array(
				'name' => $label,
				'typeLabel' => '',
			);

			if ($label === '') {
				return $result;
			}

			if (preg_match('/^(.*)\(([^()]*)\)\s*$/u', $label, $matches)) {
				$candidateName = trim((string)($matches[1] ?? ''));
				$candidateType = self::normalizeHolonTypeLabel(0, (string)($matches[2] ?? ''));
				if ($candidateName !== '' && self::isKnownHolonTypeLabel($candidateType)) {
					$result['name'] = $candidateName;
					$result['typeLabel'] = $candidateType;

					return $result;
				}
			}

			if (preg_match('/^(organisation|groupe|cercle|rôle|role|holon)\s+(.+)$/iu', $label, $matches)) {
				$result['typeLabel'] = self::normalizeHolonTypeLabel(0, (string)($matches[1] ?? ''));
				$result['name'] = trim((string)($matches[2] ?? ''));
			}

			return $result;
		}

		public static function formatHolonReferenceLabel($holonName, $typeId = 0, $typeLabel = '')
		{
			$holonName = trim((string)$holonName);
			$typeLabel = self::normalizeHolonTypeLabel($typeId, $typeLabel);
			if ($holonName === '') {
				return $typeLabel;
			}

			return $holonName . ' (' . $typeLabel . ')';
		}

		protected static function formatResolvedHolonReferenceLabel($currentName, $currentTypeLabel, $storedName, $storedTypeLabel, $isDeleted = false)
		{
			$currentName = trim((string)$currentName);
			$storedName = trim((string)$storedName);
			$currentTypeLabel = self::normalizeHolonTypeLabel(0, $currentTypeLabel);
			$storedTypeLabel = self::normalizeHolonTypeLabel(0, $storedTypeLabel);

			$displayTypeLabel = $currentTypeLabel !== ''
				? $currentTypeLabel
				: ($storedTypeLabel !== '' ? $storedTypeLabel : 'holon');

			$displayName = $currentName !== '' ? $currentName : $storedName;
			if ($displayName === '') {
				$displayName = $displayTypeLabel;
			}

			if ($isDeleted) {
				$displayName .= ' (supprimé depuis)';
			} elseif (
				$currentName !== ''
				&& $storedName !== ''
				&& mb_strtolower($currentName, 'UTF-8') !== mb_strtolower($storedName, 'UTF-8')
			) {
				$displayName .= ' (anciennement ' . $storedName . ')';
			}

			return self::formatHolonReferenceLabel($displayName, 0, $displayTypeLabel);
		}

		protected static function isHistoryHolonLinkable(\dbObject\Holon $holon)
		{
			if (!$holon->canViewDetail()) {
				return false;
			}

			$rootHolonId = (int)$holon->get('IDholon_org');
			if ($rootHolonId > 0 && $holon->isTemplateNode($rootHolonId) && !(bool)$holon->get('visible')) {
				return false;
			}

			return true;
		}

		protected static function resolveReferenceDisplay($type, $id, $fallbackLabel, $organizationId = 0)
		{
			static $cache = array();

			$type = trim(mb_strtolower((string)$type, 'UTF-8'));
			$id = (int)$id;
			$organizationId = (int)$organizationId;
			$fallbackLabel = self::sanitizeReferenceLabel($fallbackLabel);
			$cacheKey = $organizationId . ':' . $type . ':' . $id . ':' . $fallbackLabel;
			if (array_key_exists($cacheKey, $cache)) {
				return $cache[$cacheKey];
			}

			$result = array(
				'type' => $type,
				'id' => $id,
				'text' => $fallbackLabel,
				'isLinkable' => false,
			);

			if ($type === 'holon' && $id > 0) {
				$storedHolonReference = self::parseHolonReferenceLabel($fallbackLabel);
				$holon = new \dbObject\Holon();
				if ($holon->load($id)) {
					$result['text'] = self::formatResolvedHolonReferenceLabel(
						$holon->canViewDetail() ? $holon->getDisplayName() : '',
						$holon->canViewDetail() ? (string)$holon->getTypeLabel() : (string)($storedHolonReference['typeLabel'] ?? ''),
						(string)($storedHolonReference['name'] ?? ''),
						(string)($storedHolonReference['typeLabel'] ?? '')
					);
					$result['isLinkable'] = $holon->canViewDetail() && self::isHistoryHolonLinkable($holon);
				} else {
					$result['text'] = self::formatResolvedHolonReferenceLabel(
						'',
						'',
						(string)($storedHolonReference['name'] ?? ''),
						(string)($storedHolonReference['typeLabel'] ?? ''),
						true
					);
				}
			} elseif ($type === 'user' && $id > 0) {
				$user = new \dbObject\User();
				if ($user->load($id) && $user->canViewDetail()) {
					$result['text'] = trim((string)$user->getScopedDisplayName($organizationId));
					$result['isLinkable'] = true;
				}
			} elseif ($type === 'organization' && $id > 0) {
				$organization = new \dbObject\Organization();
				if ($organization->load($id)) {
					$result['text'] = trim((string)$organization->get('name'));
				}
			} elseif ($type === 'property' && $id > 0) {
				$property = new \dbObject\Property();
				if ($property->load($id)) {
					$result['text'] = trim((string)$property->get('name'));
				}
			}

			if (trim((string)$result['text']) === '') {
				if ($fallbackLabel !== '') {
					$result['text'] = $fallbackLabel;
				} elseif ($type !== '' && $id > 0) {
					$result['text'] = $type . ' ' . $id;
				} else {
					$result['text'] = '';
				}
			}

			$cache[$cacheKey] = $result;

			return $cache[$cacheKey];
		}

		protected static function resolveAuthorDisplayName($userId, $organizationId = 0)
		{
			static $cache = array();

			$userId = (int)$userId;
			$organizationId = (int)$organizationId;
			if ($userId <= 0) {
				return '';
			}

			$cacheKey = $organizationId . ':' . $userId;
			if (array_key_exists($cacheKey, $cache)) {
				return $cache[$cacheKey];
			}

			$user = new \dbObject\User();
			if (!$user->load($userId) || !$user->canViewDetail()) {
				$cache[$cacheKey] = 'Utilisateur ' . $userId;
				return $cache[$cacheKey];
			}

			$label = trim((string)$user->getScopedDisplayName($organizationId));
			if ($label === '') {
				$label = 'Utilisateur ' . $userId;
			}

			$cache[$cacheKey] = $label;

			return $cache[$cacheKey];
		}

		protected static function decodeParameters($value)
		{
			if (is_array($value)) {
				return $value;
			}

			if (!is_string($value) || trim($value) === '') {
				return null;
			}

			$decoded = json_decode($value, true);

			return is_array($decoded) ? $decoded : null;
		}

		public static function formatActionLabel($action)
		{
			$action = trim((string)$action);
			if ($action === '') {
				return '';
			}

			$labels = array(
				'holon_created' => 'Creation',
				'holon_updated' => 'Modification',
				'holon_member_added' => 'Ajout de membre',
				'holon_member_removed' => 'Retrait de membre',
			);

			if (isset($labels[$action])) {
				return $labels[$action];
			}

			return ucwords(str_replace('_', ' ', $action));
		}

		protected static function mapHistoryRows(array $rows, $organizationId)
		{
			$items = array();
			foreach ($rows as $row) {
				$rawContent = trim((string)($row['content'] ?? ''));
				$parameters = self::decodeParameters($row['parameters'] ?? null);
				$action = trim((string)($row['action'] ?? ''));
				$dateCreation = trim((string)($row['datecreation'] ?? ''));

				$items[] = array(
					'id' => (int)($row['id'] ?? 0),
					'IDorganization' => (int)($row['IDorganization'] ?? 0),
					'IDuser' => (int)($row['IDuser'] ?? 0),
					'IDholon_circle' => (int)($row['IDholon_circle'] ?? 0),
					'action' => $action,
					'actionLabel' => self::formatActionLabel($action),
					'content' => $rawContent,
					'contentDisplay' => self::renderReferenceText($rawContent, $organizationId),
					'contentHtml' => self::renderReferenceHtml($rawContent, $organizationId, array(
						'linkHolons' => true,
						'linkUsers' => false,
					)),
					'parameters' => $parameters,
					'datecreation' => $dateCreation,
					'authorDisplayName' => self::resolveAuthorDisplayName((int)($row['IDuser'] ?? 0), $organizationId),
				);
			}

			return $items;
		}

		public static function fetchHolonFeedPage($organizationId, $holonId, $limit = 10, $offset = 0, $includeOrganizationScope = false)
		{
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;
			$limit = max(1, min(100, (int)$limit));
			$offset = max(0, (int)$offset);
			$includeOrganizationScope = (bool)$includeOrganizationScope;
			if ($organizationId <= 0 || (!$includeOrganizationScope && $holonId <= 0)) {
				return array(
					'items' => array(),
					'hasMore' => false,
					'nextOffset' => $offset,
				);
			}

			if ($includeOrganizationScope) {
				$query = "SELECT id, IDorganization, IDuser, IDholon_circle, action, content, parameters, datecreation, active
					FROM history
					WHERE active = 1
					  AND IDorganization = :organization_id
					ORDER BY datecreation DESC, id DESC
					LIMIT " . $offset . ", " . ($limit + 1);
				$rows = self::fetchAll($query, array(
					'organization_id' => $organizationId,
				));
			} else {
				$query = "SELECT id, IDorganization, IDuser, IDholon_circle, action, content, parameters, datecreation, active
					FROM history
					WHERE active = 1
					  AND IDorganization = :organization_id
					  AND (
						content LIKE :content_needle
						OR IDholon_circle = :circle_holon_id
					  )
					ORDER BY datecreation DESC, id DESC
					LIMIT " . $offset . ", " . ($limit + 1);
				$rows = self::fetchAll($query, array(
					'organization_id' => $organizationId,
					'content_needle' => '%' . self::buildHolonSearchNeedle($holonId) . '%',
					'circle_holon_id' => $holonId,
				));
			}
			if (!is_array($rows) || count($rows) === 0) {
				return array(
					'items' => array(),
					'hasMore' => false,
					'nextOffset' => $offset,
				);
			}

			$hasMore = count($rows) > $limit;
			if ($hasMore) {
				$rows = array_slice($rows, 0, $limit);
			}

			return array(
				'items' => self::mapHistoryRows($rows, $organizationId),
				'hasMore' => $hasMore,
				'nextOffset' => $offset + count($rows),
			);
		}

		public static function findForHolon($organizationId, $holonId, $limit = 100, $includeOrganizationScope = false)
		{
			$page = self::fetchHolonFeedPage($organizationId, $holonId, $limit, 0, $includeOrganizationScope);

			return $page['items'] ?? array();
		}

		public static function getLatestOrganizationEntryId($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return 0;
			}

			return (int)self::fetchValue(
				"SELECT MAX(id)
				FROM history
				WHERE active = 1
				  AND IDorganization = :organization_id",
				array(
					'organization_id' => $organizationId,
				)
			);
		}
	}

?>
