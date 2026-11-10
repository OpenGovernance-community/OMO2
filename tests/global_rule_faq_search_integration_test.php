<?php
declare(strict_types=1);

// Local MariaDB integration test; all fixtures are rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization, Application,
    OrganizationApplication, Rule, FAQ, HolonShareLink, SearchJob, ArrayRule};

function globalSearchAssert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function globalSearchFixture(string $class, array $values): DbObject
{
    $object = new $class();
    foreach ($values as $field => $value) $object->set($field, $value);
    $saved = $object->save();
    globalSearchAssert(!empty($saved['status']), 'Fixture failed: ' . $class . ' ' . json_encode($saved));
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(5));
    $needle = 'pilotage' . $nonce;
    $org = globalSearchFixture(Organization::class, ['name' => 'Global search', 'shortname' => 'gs' . $nonce]);
    $user = globalSearchFixture(User::class, ['email' => 'gs' . $nonce . '@example.invalid', 'active' => 1]);
    globalSearchFixture(UserOrganization::class, ['IDuser' => $user->getId(), 'IDorganization' => $org->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    foreach (['structure', 'policy'] as $hash) {
        $app = new Application();
        globalSearchAssert((bool)$app->load(['hash', $hash]), 'Application missing: ' . $hash);
        globalSearchFixture(OrganizationApplication::class, ['IDorganization' => $org->getId(), 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $root = globalSearchFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $org->getId(), 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $circle = globalSearchFixture(Holon::class, ['name' => 'Circle', 'IDholon_parent' => $root->getId(), 'IDholon_org' => $root->getId(), 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $role = globalSearchFixture(Holon::class, ['name' => 'Role', 'IDholon_parent' => $circle->getId(), 'IDholon_org' => $root->getId(), 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $sibling = globalSearchFixture(Holon::class, ['name' => 'Other circle', 'IDholon_parent' => $root->getId(), 'IDholon_org' => $root->getId(), 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $_SESSION['currentUser'] = $user->getId(); $_SESSION['currentOrganization'] = $org->getId();
    commonSetCurrentUserAdminMode(true, $org->getId());
    $viewer = SearchJob::buildViewerContextFromGlobals((int)$org->getId(), (int)$root->getId());
    $objects = [];
    foreach (['root' => $root, 'branch' => $role] as $kind => $holon) {
        $base = ['IDorganization' => $org->getId(), 'IDholon' => $holon->getId()];
        $objects[$kind] = [
            'rules' => globalSearchFixture(Rule::class, $base + ['title' => 'Rule ' . $kind, 'description' => '<p>lien <strong>Pilotage</strong> ' . $needle . '</p>', 'scope' => 'local', 'review_date' => new DateTime('+1 year'), 'expiration_date' => new DateTime('+2 years')]),
            'faq' => globalSearchFixture(FAQ::class, $base + ['question' => 'Question ' . $kind, 'answer' => '<p>lien Pilotage ' . $needle . '</p>', 'isactive' => 1]),
        ];
    }
    foreach ([0, $root->getId(), $role->getId(), $sibling->getId()] as $contextId) {
        $contextViewer = array_replace($viewer, ['currentHolonId' => (int)$contextId]);
        foreach (['rules', 'faq'] as $module) {
            $found = $org->searchTopbarResults($needle, [$module], ['viewerContext' => $contextViewer]);
            globalSearchAssert($found['counts'][$module] === 2, 'Global search lost another branch: ' . $module . ' ' . json_encode($found));
            foreach ($objects as $items) {
                globalSearchAssert((bool)$org->loadTopbarSearchPreviewObject($module, (int)$items[$module]->getId(), $contextViewer), 'Global preview lost another branch: ' . $module);
            }
        }
    }
    $contextual = new ArrayRule();
    $contextual->loadForPolicyContexts($org->getId(), [$root->getId()]);
    globalSearchAssert(count($contextual) === 1, 'Policy applicability must remain contextual');
    $organizationRule = globalSearchFixture(Rule::class, ['IDorganization' => $org->getId(), 'title' => 'OrganizationOnly', 'description' => 'Organization rule', 'scope' => 'global', 'review_date' => new DateTime('+1 year'), 'expiration_date' => new DateTime('+2 years')]);
    globalSearchAssert($org->searchTopbarResults('OrganizationOnly', ['rules'], ['viewerContext' => $viewer])['counts']['rules'] === 1, 'Organization rule missing from global search');
    globalSearchAssert((bool)$org->loadTopbarSearchPreviewObject('rules', (int)$organizationRule->getId(), $viewer), 'Organization rule preview missing');

    $share = globalSearchFixture(HolonShareLink::class, ['IDorganization' => $org->getId(), 'IDholon' => $circle->getId(), 'IDuser' => $user->getId(), 'token' => bin2hex(random_bytes(24)), 'allow_structure' => 1, 'active' => 1]);
    $shareViewer = ['type' => 'share', 'organizationId' => $org->getId(), 'currentHolonId' => $root->getId(), 'shareLinkId' => $share->getId(), 'shareHolonId' => $circle->getId(), 'allowStructure' => true];
    foreach (['rules', 'faq'] as $module) {
        globalSearchAssert($org->searchTopbarResults($needle, [$module], ['viewerContext' => $shareViewer])['counts'][$module] === 1, 'Share search escaped its branch: ' . $module);
        globalSearchAssert((bool)$org->loadTopbarSearchPreviewObject($module, (int)$objects['branch'][$module]->getId(), $shareViewer), 'Shared branch preview missing: ' . $module);
        globalSearchAssert(!$org->loadTopbarSearchPreviewObject($module, (int)$objects['root'][$module]->getId(), $shareViewer), 'Share preview escaped its branch: ' . $module);
    }
    $foreign = globalSearchFixture(Organization::class, ['name' => 'Other organization', 'shortname' => 'other' . $nonce]);
    $foreignRoot = globalSearchFixture(Holon::class, ['name' => 'Foreign root', 'IDorganization' => $foreign->getId(), 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $foreignRule = globalSearchFixture(Rule::class, ['IDholon' => $foreignRoot->getId(), 'title' => $needle, 'description' => 'Foreign rule', 'scope' => 'local', 'review_date' => new DateTime('+1 year'), 'expiration_date' => new DateTime('+2 years')]);
    $foreignFaq = globalSearchFixture(FAQ::class, ['IDorganization' => $foreign->getId(), 'IDholon' => $foreignRoot->getId(), 'question' => $needle, 'answer' => 'Foreign answer', 'isactive' => 1]);
    foreach (['rules' => $foreignRule, 'faq' => $foreignFaq] as $module => $object) {
        globalSearchAssert($org->searchTopbarResults($needle, [$module], ['viewerContext' => $viewer])['counts'][$module] === 2, 'Search escaped its organization: ' . $module);
        globalSearchAssert(!$org->loadTopbarSearchPreviewObject($module, (int)$object->getId(), $viewer), 'Preview escaped its organization: ' . $module);
    }

    globalSearchAssert(!empty($circle->changeArchiveState((int)$org->getId(), false, 'reassign')['status']), 'Archive circle');
    foreach (['rules', 'faq'] as $module) {
        foreach ([false, true] as $archived) {
            $archiveViewer = $viewer + ['archived' => $archived];
            $found = $org->searchTopbarResults($needle, [$module], ['viewerContext' => $archiveViewer]);
            globalSearchAssert($found['counts'][$module] === 1, 'Archive partition lost: ' . $module);
            globalSearchAssert((int)$found['results'][0]['action'][$module === 'rules' ? 'ruleId' : 'faqId'] === (int)$objects[$archived ? 'branch' : 'root'][$module]->getId(), 'Wrong archive result: ' . $module);
            foreach (['root', 'branch'] as $kind) {
                globalSearchAssert((bool)$org->loadTopbarSearchPreviewObject($module, (int)$objects[$kind][$module]->getId(), $archiveViewer) === ($archived === ($kind === 'branch')), 'Wrong archive preview: ' . $module);
            }
        }
    }
    echo "global_rule_faq_search_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Holon::clearPreloadedData();
}
