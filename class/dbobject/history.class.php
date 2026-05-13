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

		public static function renderReferenceText($content)
		{
			$content = trim((string)$content);
			if ($content === '') {
				return '';
			}

			return preg_replace_callback('/\[([^\|\]]+)\|([0-9]+)\|([^\]]*)\]/u', function ($matches) {
				$label = trim((string)($matches[3] ?? ''));
				if ($label !== '') {
					return $label;
				}

				$type = trim((string)($matches[1] ?? ''));
				$id = (int)($matches[2] ?? 0);
				if ($type !== '' && $id > 0) {
					return $type . ' ' . $id;
				}

				return trim((string)($matches[0] ?? ''));
			}, $content);
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
					'contentDisplay' => self::renderReferenceText($rawContent),
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
	}

?>
