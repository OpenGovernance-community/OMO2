<?php
declare(strict_types=1);

namespace dbObject {
    class Organization
    {
        public function get($field) { return $field === 'name' ? 'A organization' : null; }
    }
    class Holon
    {
        public function __construct(private int $id, public string $name, private ?self $parent = null) {}
        public function getId(): int { return $this->id; }
        public function getFullDisplayName(): string { return $this->name; }
        public function getParentHolon(): ?self { return $this->parent; }
    }
    class Authority
    {
        public function __construct(private int $id, private string $label, private ?self $parent = null, private bool $shell = false) {}
        public function getId(): int { return $this->id; }
        public function get($field) { return $field === 'is_shell' ? (int)$this->shell : $this->label; }
        public function getParent(): ?self { return $this->parent; }
    }
    class Rule
    {
        public function __construct(private int $id, public string $title = '') {}
        public function getId(): int { return $this->id; }
        public function get($field) { return $this->title; }
        public function getHolon(): ?Holon { return null; }
        public function getAuthority(): ?Authority { return null; }
    }
}

namespace {
    use dbObject\Authority;
    use dbObject\Holon;
    use dbObject\Organization;
    use dbObject\Rule;

    function omoPolicyT($key, array $replace = []): string { return $key . implode('', $replace); }
    function omoApiEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
    function numberingAssert(bool $condition, string $message): void
    {
        if (!$condition) throw new RuntimeException($message);
    }
    require_once dirname(__DIR__) . '/omo/api/policy/shared.php';

    $root = new Holon(1, 'Z root');
    $area = new Holon(2, 'Area', $root);
    $circleA = new Holon(10, 'A circle', $area);
    $circleB = new Holon(20, 'B circle', $area);
    $circleC = new Holon(30, 'C circle', $area);
    $roleA = new Holon(11, 'Role 1', $circleA);
    $roleB = new Holon(21, 'Role 1', $circleB);
    $roleC1 = new Holon(31, 'Role 1', $circleC);
    $roleC2 = new Holon(32, 'Role 2', $circleC);
    $authorityRoot = new Authority(100, 'Domain');
    $authorityA = new Authority(110, 'A branch', $authorityRoot);
    $authorityB = new Authority(120, 'B branch', $authorityRoot);
    $authorityC = new Authority(130, 'C branch', $authorityRoot);
    $authorityC1 = new Authority(131, 'Role 1', $authorityC);
    $authorityC2 = new Authority(132, 'Role 2', $authorityC);
    $authorityShell = new Authority(140, 'Shell', $authorityC2, true);
    $entries = [
        ['rule' => new Rule(11), 'holon' => $roleA, 'authority' => $authorityA, 'visible' => true],
        ['rule' => new Rule(21), 'holon' => $roleB, 'authority' => $authorityB, 'visible' => true],
        ['rule' => new Rule(31), 'holon' => $roleC1, 'authority' => $authorityC1, 'visible' => true],
        ['rule' => new Rule(32), 'holon' => $roleC2, 'authority' => $authorityShell, 'visible' => true],
    ];
    $organization = new Organization();
    $restrict = static function (array $entries, array $ids): array {
        return array_map(static function (array $entry) use ($ids): array {
            $entry['visible'] = in_array($entry['rule']->getId(), $ids, true);
            return $entry;
        }, $entries);
    };
    // Exercise the actual page renderer as well as the grouping helper.
    $page = file_get_contents(dirname(__DIR__) . '/omo/api/policy/index.php');
    $renderStart = strpos($page, '$policyRenderGroups = null;');
    $renderEnd = strpos($page, '<div class="omo-policy__groups', $renderStart);
    $renderCode = trim(substr($page, $renderStart, $renderEnd - $renderStart));
    $renderCode = preg_replace('/\?>\s*$/', '', $renderCode);
    $render = static function (array $groups, string $policyGroup = 'holon') use ($renderCode): string {
        $policyGroupNodes = $groups['nodes'];
        $policyRenderRule = static function (array $entry): void {
            echo '<article data-rule="' . $entry['rule']->getId() . '"></article>';
        };
        ob_start();
        eval($renderCode);
        $policyRenderGroups($groups['roots']);
        return ob_get_clean();
    };
    foreach (['holon' => ['holon:32', '1.3.2'], 'authority' => ['authority:132', '3.2']] as $group => [$targetKey, $number]) {
        $global = omoPolicyBuildRuleGroups($entries, $group, $organization);
        numberingAssert($global['nodes'][$targetKey]['number'] === $number, 'Global reference must follow the complete tree.');
        foreach ([[32], [21, 32], [31, 32]] as $visibleIds) {
            $limited = omoPolicyBuildRuleGroups($restrict($entries, $visibleIds), $group, $organization);
            numberingAssert($limited['nodes'][$targetKey]['number'] === $number, 'Hidden siblings must not renumber a visible section.');
            $html = $render($limited, $group);
            numberingAssert(str_contains($html, ($group === 'holon' ? $number . '. ' : '') . 'Role 2'), 'The page must render canonical section references only for holons.');
            numberingAssert(!str_contains($html, 'data-rule="11"'), 'Hidden rules must not reach the rendered page.');
            numberingAssert(!str_contains($html, 'A circle') && !str_contains($html, 'A branch'), 'Empty branches must not render.');
            numberingAssert(!str_contains($html, '. Z root') && !str_contains($html, '. Domain'), 'A single global root remains untitled.');
        }
        $empty = omoPolicyBuildRuleGroups($restrict($entries, []), $group, $organization);
        numberingAssert($empty['roots'] === [] && trim($render($empty)) === '', 'A view with no visible rules must render no sections.');
    }
    // Multiple global roots must keep their number and title even if only one survives.
    $withOrganization = [...$entries, ['rule' => new Rule(99), 'holon' => null, 'authority' => null, 'visible' => true]];
    $global = omoPolicyBuildRuleGroups($withOrganization, 'holon', $organization);
    $limited = omoPolicyBuildRuleGroups($restrict($withOrganization, [32]), 'holon', $organization);
    numberingAssert($limited['roots'] === ['holon:1'], 'Only the visible root must survive.');
    numberingAssert($limited['nodes']['holon:1']['showTitle'], 'The global root title must survive a single-root filtered view.');
    numberingAssert($limited['nodes']['holon:32']['number'] === '2.1.3.2'
        && $limited['nodes']['holon:32']['number'] === $global['nodes']['holon:32']['number'], 'Filtering another root must preserve every prefix.');
    $html = $render($limited);
    numberingAssert(str_contains($html, '2. Z root') && str_contains($html, '2.1.3.2. Role 2'), 'Renderer must preserve the complete global prefix.');
    numberingAssert(!str_contains($html, 'A organization') && !str_contains($html, 'data-rule="99"'), 'The hidden organization branch must not render.');
    // A selected sort must not affect section numbers when labels are identical.
    $roleC2->name = 'Role 1';
    $forward = omoPolicyBuildRuleGroups($entries, 'holon', $organization);
    $reverse = omoPolicyBuildRuleGroups(array_reverse($entries), 'holon', $organization);
    numberingAssert($forward['nodes']['holon:32']['number'] === $reverse['nodes']['holon:32']['number'], 'Equal labels need a stable ordering independent of rule sorting.');
    $flat = omoPolicyBuildRuleGroups($restrict($entries, [32]), 'none', $organization);
    numberingAssert(!$flat['nodes']['flat']['showTitle'] && count($flat['nodes']['flat']['rules']) === 1, 'Ungrouped views must retain only visible rules without adding a title.');
    numberingAssert(substr_count($render($flat), '<article ') === 1, 'The flat renderer must emit only the selected rule.');
    $organizationOnly = omoPolicyBuildRuleGroups([$withOrganization[4]], 'holon', $organization);
    numberingAssert(!$organizationOnly['nodes']['organization']['showTitle'], 'Organizations without a structure retain their untitled root.');
    numberingAssert(str_contains($render($organizationOnly), 'data-rule="99"'), 'Organization rules must still render without a structure.');
    numberingAssert($organizationOnly['ruleNumbers'][99] === '1', 'A rule without a structure must have a reference.');

    // The same rule reference must survive filtering, sorting, and regrouping.
    $canonical = omoPolicyBuildRuleGroups($entries, 'holon', $organization);
    numberingAssert($canonical['ruleNumbers'][32] === '1.3.2.1', 'A rule must have its own reference below its holon.');
    foreach (['holon', 'authority', 'none'] as $group) {
        $limitedEntries = $restrict(array_reverse($entries), [32]);
        foreach ($limitedEntries as &$entry) $entry['number'] = $canonical['ruleNumbers'][$entry['rule']->getId()];
        unset($entry);
        $limited = omoPolicyBuildRuleGroups($limitedEntries, $group, $organization);
        $visible = [];
        foreach ($limited['nodes'] as $node) foreach ($node['rules'] as $entry) $visible[] = $entry;
        numberingAssert(count($visible) === 1 && $visible[0]['number'] === '1.3.2.1', 'Regrouping and display sorting must preserve the canonical rule reference.');
    }
    // Rules at a parent and child sections must never share references.
    $mixed = [...$entries,
        ['rule' => new Rule(101, 'Alpha'), 'holon' => $area, 'authority' => null, 'visible' => true],
        ['rule' => new Rule(102, 'Beta'), 'holon' => $area, 'authority' => null, 'visible' => true],
        ['rule' => new Rule(103, 'Beta'), 'holon' => $area, 'authority' => null, 'visible' => true],
    ];
    $mixedGroups = omoPolicyBuildRuleGroups($mixed, 'holon', $organization);
    numberingAssert($mixedGroups['ruleNumbers'][101] === '1.1' && $mixedGroups['ruleNumbers'][102] === '1.2'
        && $mixedGroups['ruleNumbers'][103] === '1.3', 'Rules must sort by title, then ID for identical titles.');
    numberingAssert($mixedGroups['nodes']['holon:10']['number'] === '1.4', 'Child sections must follow the parent rules without duplicate numbers.');
    $allNumbers = array_values($mixedGroups['ruleNumbers']);
    foreach ($mixedGroups['nodes'] as $node) if ($node['showTitle']) $allNumbers[] = $node['number'];
    numberingAssert(count($allNumbers) === count(array_unique($allNumbers)), 'All visible rules and sections must have unique references.');
    $reversedGroups = omoPolicyBuildRuleGroups(array_reverse($mixed), 'holon', $organization);
    numberingAssert($mixedGroups['ruleNumbers'] === $reversedGroups['ruleNumbers'], 'Display order must not affect references, including equal titles.');
    $filteredGroups = omoPolicyBuildRuleGroups($restrict($mixed, [103]), 'holon', $organization);
    numberingAssert($filteredGroups['nodes']['holon:2']['rules'][0]['number'] === '1.3', 'Filtering must retain a gap for an existing hidden rule.');
    $deleted = array_values(array_filter($mixed, static fn (array $entry) => $entry['rule']->getId() !== 102));
    $deletedGroups = omoPolicyBuildRuleGroups($deleted, 'holon', $organization);
    numberingAssert($deletedGroups['ruleNumbers'][103] === '1.2' && $deletedGroups['nodes']['holon:10']['number'] === '1.3',
        'Deleting a rule must close the gap and renumber the following child sections.');
    $projected = omoPolicyBuildRuleEntries([new Rule(501, 'One'), new Rule(502, 'Two')], [new Rule(502)]);
    numberingAssert(count($projected) === 2 && !$projected[0]['visible'] && $projected[1]['visible'], 'Entry projection must retain the global set and mark only selected rule IDs visible.');
    echo "policy_global_numbering_test: OK\n";
}
