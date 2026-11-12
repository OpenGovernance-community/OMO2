<?php
declare(strict_types=1);

namespace dbObject {
    // Isolated template fixture: exercise production display code without a database.
    class Holon
    {
        public static array $definitions = [];
        public function load($id): bool { return (int)$id > 0; }
        public function getHolonCreationPropertyDefinitions(): array { return self::$definitions; }
        public function get($field) { return false; }
        public function getEffectiveColor(): string { return ''; }
        public function getEffectiveIcon(): string { return ''; }
        public function getEffectiveTemplateAdminBounds(): array { return ['min' => 0, 'max' => null]; }
    }
}

namespace {
    foreach (['dbobject', 'organization', 'propertyformat', 'property', 'deferredproposal', 'decisiongovernanceaction'] as $class) {
        require_once dirname(__DIR__) . '/class/dbobject/' . $class . '.class.php';
    }

    use dbObject\{DeferredProposal, DecisionGovernanceAction, Holon, Organization};

    class InheritedListOrganization extends Organization
    {
        public function getLexicon(): array { return []; }
    }

    function checkInheritedList(bool $condition, string $message): void
    {
        if (!$condition) throw new \RuntimeException($message);
    }

    function inheritedListValue(int $format, array $items): string
    {
        return json_encode($format === 7 ? ['before' => '<p>Introduction</p>', 'items' => $items, 'after' => '<p>Conclusion</p>'] : $items);
    }

    $organization = new InheritedListOrganization();
    $fixtures = [];
    foreach ([2, 7] as $format) {
        foreach (['text', 'authority'] as $itemType) {
            foreach ([false, true] as $locked) {
                $templateItems = $itemType === 'authority' ? [['id' => 17, 'label' => 'Template authority']] : ['Template item'];
                $instanceItems = $itemType === 'authority' ? [['id' => 117, 'label' => 'Instance authority']] : ['Template item', 'Ancestor item'];
                $definition = ['id' => 8, 'name' => 'Inherited list', 'formatId' => $format, 'listItemType' => $itemType,
                    'inheritedValue' => inheritedListValue($format, $templateItems), 'value' => '', 'effectiveLocked' => $locked];
                Holon::$definitions = [$definition];
                $instance = array_replace($definition, ['inheritedValue' => inheritedListValue($format, $instanceItems)]);
                $localProperty = ['id' => 9, 'name' => 'Local property', 'formatId' => 1, 'value' => 'Local content'];
                foreach (['add', 'remove', 'unchanged', 'add_item', 'remove_item'] as $operation) {
                    if ($locked && in_array($operation, ['add_item', 'remove_item'], true)) continue;
                    $draft = array_intersect_key($definition, array_flip(['id', 'name', 'formatId', 'listItemType', 'value']));
                    $beforeProperty = $instance;
                    $localItems = $itemType === 'authority' ? [['id' => 118, 'label' => 'Local authority']] : ['Local item'];
                    if ($operation === 'add_item') $draft['value'] = inheritedListValue($format, $localItems);
                    if ($operation === 'remove_item') $beforeProperty['value'] = inheritedListValue($format, $localItems);
                    $before = ['template_id' => 5, 'editor_payload' => ['templateId' => 5, 'properties' => [$beforeProperty]]];
                    $after = ['template_id' => 5, 'editor_payload' => ['templateId' => 5, 'properties' => [$draft]]];
                    if ($operation === 'add') $after['editor_payload']['properties'][] = $localProperty;
                    if ($operation === 'remove') $before['editor_payload']['properties'][] = $localProperty;
                    $beforeInput = $before;
                    $afterInput = $after;

                    // captureHolonEditorState already decorates the saved before snapshot.
                    $before = DeferredProposal::decorateHolonListDisplayState($before, $organization);
                    $before = DeferredProposal::decorateHolonTemplateDisplayState($before, $organization);
                    $after = DeferredProposal::decorateHolonTemplateDisplayState($after, $organization, $before);
                    $before = DeferredProposal::decorateHolonListDisplayState($before, $organization);
                    $after = DeferredProposal::decorateHolonListDisplayState($after, $organization);
                    $oldProperty = $before['editor_payload']['properties'][0];
                    $newProperty = $after['editor_payload']['properties'][0];
                    checkInheritedList($newProperty['inheritedValue'] === $instance['inheritedValue'], 'Keep instance inheritance, including remapped authority IDs.');
                    if (!in_array($operation, ['add_item', 'remove_item'], true)) {
                        checkInheritedList($oldProperty['value'] === $newProperty['value'], 'An unrelated local edit must not change the inherited list.');
                        checkInheritedList($oldProperty['displayItems'] === $newProperty['displayItems'], 'Inherited list labels must remain unchanged.');
                    }
                    checkInheritedList(DeferredProposal::decorateHolonListDisplayState($before, $organization) === $before, 'Display decoration must be idempotent.');
                    if ($format === 7) {
                        $parts = json_decode($newProperty['value'], true);
                        checkInheritedList($parts['before'] === '<p>Introduction</p>' && $parts['after'] === '<p>Conclusion</p>', 'Keep HTML list introduction and conclusion.');
                        checkInheritedList(count($parts['items']) === count($instanceItems) + ($operation === 'add_item' ? 1 : 0), 'Merge only list items, never HTML fragments or nested lists.');
                    }
                    $fixtures[] = ['case' => "$format/$itemType/" . (int)$locked . "/$operation", 'operation' => $operation,
                        'action' => ['type' => 'holon.update', 'before' => $before, 'after' => $after]];
                    if (($argv[1] ?? '') !== '--json' && $itemType === 'text') {
                        $description = DecisionGovernanceAction::buildRoleUpdateDescription($beforeInput, $afterInput, $organization);
                        checkInheritedList(str_contains($description, 'Inherited list') === in_array($operation, ['add_item', 'remove_item'], true), 'Decision descriptions must only report actual list changes.');
                        checkInheritedList(str_contains($description, 'Local property') === in_array($operation, ['add', 'remove'], true), 'Decision descriptions must retain added and removed local properties.');
                    }
                }
                $changedTemplate = ['template_id' => 6, 'editor_payload' => ['templateId' => 6, 'properties' => []]];
                $changedTemplate = DeferredProposal::decorateHolonTemplateDisplayState($changedTemplate, $organization, $before);
                checkInheritedList($changedTemplate['editor_payload']['properties'][0]['inheritedValue'] === $definition['inheritedValue'], 'A different template must use its own inheritance.');
            }
        }
    }
    echo ($argv[1] ?? '') === '--json' ? json_encode($fixtures, JSON_THROW_ON_ERROR) : "deferred_holon_inherited_lists_test: OK\n";
}
