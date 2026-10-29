<?php
namespace dbObject;

/** Shared, permission-checked value reads for REST and MCP. */
final class McpValues
{
    public static function accessible(Organization $organization, int $id): ?Valeur
    {
        $userId = (int)\commonGetCurrentUserId();
        if (!$organization->isApplicationEnabled('structure', $userId)) return null;
        $root = $organization->getEnabledStructuralRootHolon($userId);
        $value = Valeur::loadInOrganization($id, (int)$organization->getId());
        if (!$root || !$value) return null;
        try {
            // Templates can supply inherited values without appearing in the diagram.
            McpStructure::requireHolon($organization, $root, (int)$value->get('IDholon'), true);
        } catch (\DomainException $error) { return null; }
        return $value;
    }

    public static function serialize(Valeur $value, bool $detail = false): array
    {
        $id = (int)$value->getId();
        $result = ['value_id' => $id, 'title' => (string)$value->get('title'),
            'holon_id' => (int)$value->get('IDholon'),
            'url' => \omoApiSourceBaseUrl() . '/omo/o/' . (int)$value->get('IDorganization') . '/c/' . (int)$value->get('IDholon'),
            'details' => ['tool' => 'omo_get_value', 'arguments' => ['value_id' => $id]],
            'rules' => ['tool' => 'omo_list_value_rules', 'arguments' => ['value_id' => $id]]];
        if ($detail) {
            require_once dirname(__DIR__, 2) . '/omo/api/search/preview_shared.php';
            $result['principle_html'] = PropertyFormat::sanitizeHtml($value->get('principle'));
            $result['principle'] = \omoSearchPreviewText($result['principle_html']);
        }
        return $result;
    }

    public static function forRule(Rule $rule, Organization $organization, bool $detail = false): array
    {
        $id = (int)$rule->get('IDvaleur');
        $value = $id > 0 ? self::accessible($organization, $id) : null;
        return ['value_state' => $id <= 0 ? 'none' : ($value ? 'linked' : 'unavailable'),
            'value' => $value ? self::serialize($value, $detail) : null];
    }

    public static function read(array $grant, int $id): array
    {
        $organization = McpStructure::organization($grant);
        $value = self::accessible($organization, $id);
        if (!$value) throw new \DomainException('Value unavailable.');
        return ['organization_id' => (int)$organization->getId(), 'value' => self::serialize($value, true)];
    }

    public static function values(array $grant, array $args): array
    {
        $organization = McpStructure::organization($grant);
        $root = McpStructure::root($organization, (int)$grant['IDuser']);
        $effectiveIds = null;
        if (isset($args['holon_id'])) {
            $holon = McpStructure::requireHolon($organization, $root, $args['holon_id'], true);
            $effectiveIds = [];
            foreach ($holon->getPropertiesValue() as $property) {
                if ($property->get('listitemtype') !== Property::LIST_ITEM_VALUE
                    || !PropertyFormat::isListFormat((int)$property->get('IDpropertyformat'))
                    || !Property::isTypeEnabled($property->get('type'), $holon->getPropertyTypeLexicon())) continue;
                foreach (self::propertySections($property, $organization) as $section) {
                    if (isset($section['value'])) $effectiveIds[] = $section['value']['value_id'];
                }
            }
        }
        $cursor = $args['after_id'] ?? 0; $limit = $args['limit'] ?? 20;
        $items = []; $complete = false; $scanned = 0;
        while (count($items) < $limit && $scanned < 500) {
            $where = [['field' => 'IDorganization', 'value' => (int)$organization->getId()], ['field' => 'id', 'op' => '>', 'value' => $cursor]];
            if ($effectiveIds !== null) $where[] = ['field' => 'id', 'op' => 'IN', 'value' => $effectiveIds ?: [0]];
            $values = new ArrayValeur();
            $values->load(['where' => $where, 'orderBy' => [['field' => 'id', 'dir' => 'ASC']], 'limit' => 100]);
            if (!count($values)) { $complete = true; break; }
            foreach ($values as $candidate) {
                $cursor = (int)$candidate->getId(); $scanned++;
                if (isset($args['query']) && mb_stripos((string)$candidate->get('title'), $args['query'], 0, 'UTF-8') === false) continue;
                $value = self::accessible($organization, $cursor);
                if ($value) $items[] = self::serialize($value);
                if (count($items) === $limit) break;
            }
            if (count($items) < $limit && count($values) < 100) { $complete = true; break; }
        }
        return ['organization_id' => (int)$organization->getId(), 'items' => $items,
            'next_after_id' => $complete ? null : $cursor, 'complete' => $complete];
    }

    public static function rules(array $grant, array $args): array
    {
        $value = self::read($grant, $args['value_id'])['value'];
        $page = McpBrowse::records($grant, ['module' => 'rules'] + $args);
        return $page + ['value' => $value];
    }

    public static function ruleValue(array $grant, array $args): array
    {
        $organization = McpStructure::organization($grant);
        $rule = new Rule();
        if (!$rule->load($args['rule_id']) || $rule->getOrganizationId() !== (int)$organization->getId()) {
            throw new \DomainException('Rule unavailable.');
        }
        $holonId = $args['context_holon_id'] ?? ($rule->getHolon() ? (int)$rule->getHolon()->getId() : null);
        $context = McpContent::context($grant, $organization, $holonId);
        if (!McpContent::accessibleObject($organization, $context, 'rules', (int)$rule->getId())) {
            throw new \DomainException('Rule unavailable.');
        }
        return ['organization_id' => (int)$organization->getId(), 'rule_id' => (int)$rule->getId()]
            + self::forRule($rule, $organization, true);
    }

    /** Resolve live rows, never expose the potentially stale title/principle snapshots. */
    public static function propertySections(HolonProperty $property, Organization $organization): array
    {
        $sections = []; $seen = [];
        $format = (int)$property->get('IDpropertyformat');
        foreach (['value_parents', 'value'] as $field) {
            $raw = trim((string)$property->get($field));
            if ($raw === '') continue;
            $decoded = json_decode($raw, true);
            $segments = is_array($decoded) ? [$raw] : preg_split('/(?<=[\]\}])\s*\|\s*(?=[\[\{])/', $raw);
            foreach ($segments as $segment) {
                try { $parts = Property::listConversionParts($segment, $format); }
                catch (\RuntimeException $error) { continue; }
                if (!empty($parts['before'])) $sections[] = ['text' => $parts['before']];
                foreach ($parts['items'] as $item) {
                    $id = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
                    if (isset($seen[$id])) continue;
                    $value = self::accessible($organization, $id);
                    if (!$value) continue;
                    $seen[$id] = true;
                    $sections[] = ['title' => (string)$value->get('title'), 'text' => (string)$value->get('principle'),
                        'value' => self::serialize($value)];
                }
                if (!empty($parts['after'])) $sections[] = ['text' => $parts['after']];
            }
        }
        return $sections;
    }
}
