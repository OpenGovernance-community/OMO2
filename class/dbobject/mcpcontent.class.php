<?php
namespace dbObject;

/** Permission-checked read model, reusing the data projected by OMO search previews. */
final class McpContent
{
    private const ID_FIELDS = ['structure' => 'holonId', 'team' => 'userId', 'calendar' => 'eventId',
        'rules' => 'ruleId', 'documents' => 'documentId', 'pv' => 'documentId', 'decision' => 'decisionId',
        'projects' => 'projectId', 'stats' => 'indicatorId', 'processus' => 'checklistId',
        'activities' => 'activityId', 'faq' => 'faqId', 'tutorials' => 'parcoursId'];

    public static function enabledModules(Organization $organization, int $userId): array
    {
        return array_values(array_filter(\OMO_MCP_MODULES, static function ($module) use ($organization, $userId) {
            $app = ['pv' => 'documents', 'rules' => 'policy'][$module] ?? $module;
            return in_array($module, ['faq', 'tutorials'], true) || $organization->isApplicationEnabled($app, $userId);
        }));
    }
    private static function context(array $grant, Organization $organization, ?int $holonId): array
    {
        $root = $organization->getStructuralRootHolon();
        if ($holonId !== null) {
            if (!$root) throw new \DomainException('Context unavailable.');
            McpStructure::requireHolon($organization, $root, $holonId);
        }
        return ['type' => 'user', 'userId' => (int)$grant['IDuser'],
            'organizationId' => (int)$organization->getId(), 'currentHolonId' => $holonId ?? ($root ? (int)$root->getId() : 0)];
    }
    private static function previewHelpers(): void
    {
        require_once dirname(__DIR__, 2) . '/omo/translations.php';
        require_once dirname(__DIR__, 2) . '/omo/api/search/preview_shared.php';
    }
    private static function sourceUrl(int $organizationId, string $module, int $id, int $holonId, int $missionId = 0): string
    {
        // Existing authenticated preview endpoint: no share keys or external URLs are disclosed.
        return \omoMcpIssuer() . '/omo/api/search/preview.php?' . http_build_query([
            'oid' => $organizationId, 'module' => $module, 'id' => $id, 'cid' => $holonId,
            'mission_id' => $missionId], '', '&', PHP_QUERY_RFC3986);
    }
    private static function accessibleObject(Organization $organization, array $context, string $module, int $id): ?DbObject
    {
        $object = $organization->loadTopbarSearchPreviewObject($module, $id, $context);
        if (!$object || ($module === 'team' && !$object->canViewDetail())) return null;
        if ($module === 'projects') {
            require_once dirname(__DIR__, 2) . '/omo/api/projects/shared.php';
            $projectContext = \omoProjectsResolveContext((int)$organization->getId(), $context['currentHolonId'], false);
            if (!\omoProjectsCanViewProject($object, $projectContext)) return null;
        }
        return $object;
    }
    public static function search(array $grant, string $query, array $modules, ?int $holonId, int $offset, int $limit): array
    {
        $organization = McpStructure::organization($grant);
        $context = self::context($grant, $organization, $holonId);
        $enabled = self::enabledModules($organization, (int)$grant['IDuser']);
        $scopes = $modules ? array_values(array_intersect($modules, $enabled)) : $enabled;
        // The existing search falls back to all modules when no requested scope is enabled.
        // Avoid that fallback when the caller explicitly selected a disabled module.
        $selection = $scopes ? $organization->searchTopbarResults($query, $scopes,
            ['viewerContext' => $context, 'perScopeLimit' => 50, 'retainAllScopes' => true])['results'] : [];
        self::previewHelpers();
        // Apply the detail access gates before paging or returning titles/excerpts.
        $selection = array_values(array_filter($selection, static fn ($result) =>
            self::accessibleObject($organization, $context, $result['module'],
                (int)$result['action'][self::ID_FIELDS[$result['module']]]) !== null));
        $items = [];
        foreach (array_slice($selection, $offset, $limit) as $result) {
            $module = $result['module'];
            $action = $result['action'];
            $id = (int)$action[self::ID_FIELDS[$module]];
            $missionId = $module === 'tutorials' ? (int)($action['missionId'] ?? 0) : 0;
            $items[] = ['module' => $module, 'record_id' => $id,
                'context_holon_id' => $context['currentHolonId'] ?: null, 'mission_id' => $missionId ?: null,
                'title' => \omoSearchPreviewText($result['title'] ?? ''),
                'subtitle' => \omoSearchPreviewText($result['subtitle'] ?? ''),
                'excerpt' => \omoSearchPreviewText($result['excerpt'] ?? ''),
                'url' => self::sourceUrl((int)$organization->getId(), $module, $id, $context['currentHolonId'], $missionId)];
        }
        return ['organization_id' => (int)$organization->getId(), 'query' => $query, 'modules' => $scopes,
            'items' => $items, 'next_offset' => $offset + count($items) < count($selection) ? $offset + count($items) : null,
            'selection_count' => count($selection), 'selection_limit_per_module' => 50, 'exhaustive' => false];
    }
    public static function read(array $grant, string $module, int $id, ?int $holonId, int $missionId, int $offset, int $limit): array
    {
        $organization = McpStructure::organization($grant);
        $context = self::context($grant, $organization, $holonId);
        if (!in_array($module, self::enabledModules($organization, (int)$grant['IDuser']), true)) {
            throw new \DomainException('Record unavailable.');
        }
        self::previewHelpers();
        $object = self::accessibleObject($organization, $context, $module, $id);
        if (!$object) throw new \DomainException('Record unavailable.');
        try {
            $preview = \omoSearchPreviewLoad($module, $object, $organization, $context['currentHolonId'], '', $missionId, false);
        } catch (\RuntimeException $error) {
            // Domain failures from module previews are expected; database failures must propagate.
            if ($error instanceof \PDOException || $error->getMessage() !== 'Preview unavailable') throw $error;
            throw new \DomainException('Record unavailable.');
        }
        $parts = [\omoSearchPreviewText($preview['title'] ?? '')];
        foreach ($preview['fields'] ?? [] as $key => $value) {
            $value = \omoSearchPreviewText($value);
            if ($value !== '') $parts[] = $key . ': ' . $value;
        }
        foreach (array_merge($preview['sections'] ?? [], $preview['collections'] ?? []) as $block) {
            $parts[] = \omoSearchPreviewText($block['title'] ?? '') . "\n" . \omoSearchPreviewText($block['text'] ?? '');
            foreach ($block['items'] ?? [] as $item) {
                $parts[] = implode("\n", array_map('omoSearchPreviewText', [$item['title'] ?? '', $item['meta'] ?? '', $item['text'] ?? '']));
            }
        }
        if ($module === 'stats') {
            foreach ($object->getMeasurements() as $value) {
                $parts[] = 'measurement: ' . \omoSearchPreviewText($value->get('measured_at')) . ' = ' . \omoSearchPreviewText($value->get('value'));
            }
        }
        $text = implode("\n\n", array_filter($parts, static fn ($part) => trim($part) !== ''));
        $chunk = mb_substr($text, $offset, $limit, 'UTF-8');
        return ['organization_id' => (int)$organization->getId(), 'module' => $module, 'record_id' => $id,
            'context_holon_id' => $context['currentHolonId'] ?: null, 'mission_id' => $missionId ?: null,
            'title' => \omoSearchPreviewText($preview['title'] ?? ''),
            'url' => self::sourceUrl((int)$organization->getId(), $module, $id, $context['currentHolonId'], $missionId),
            'text' => $chunk, 'offset' => $offset, 'next_offset' => $offset + mb_strlen($chunk, 'UTF-8') < mb_strlen($text, 'UTF-8')
                ? $offset + mb_strlen($chunk, 'UTF-8') : null,
            'coverage' => 'OMO record text and visible collections from the module preview. External attachment contents and full module history are not included.'];
    }
}
