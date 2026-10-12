<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{ArrayFAQ, DbObject, FAQ};

function faqReadSnapshot(array $query, bool $optimized): array
{
    DbObject::$preload = [];
    DbObject::enableReadOnlyMemoization($optimized);
    commonClearCurrentUserPermissionCache();
    $before = DbObject::getPerformanceCounters();
    $context = FAQ::resolvePopupRequestContext($query);
    mcpCheck(is_array($context), 'Fixture context must be accessible.');
    if ($optimized) {
        $faqs = FAQ::loadPopupCollection($context);
    } else {
        // Reference path before hydration and request-scoped permission reuse.
        $faqs = new ArrayFAQ();
        $faqs->load(FAQ::buildPopupLoadParams($context));
    }
    $snapshot = [];
    foreach ($faqs as $faq) {
        if (!$faq->canBeViewedInContext($context)) continue;
        $scope = faqPopupDescribeScope($faq);
        ob_start();
        faqPopupRenderVoteBlock($faq);
        $votes = ob_get_clean();
        $snapshot[(int)$faq->getId()] = [(string)$faq->get('question'), (string)$faq->get('answer'),
            $scope['type'], $scope['label'], $faq->canBeEditedInContext($context),
            $faq->canBeDeletedInContext($context), $votes];
    }
    $after = DbObject::getPerformanceCounters();
    // Rows with equal display order, reliability and timestamps have no defined tie order.
    ksort($snapshot);
    return ['rows' => $snapshot, 'queries' => $after['queries'] - $before['queries']];
}

$previousSession = $_SESSION;
$items = mcpFixtures();
try {
    $_SESSION = ['currentUser' => (int)$items['user']->getId(), 'currentOrganization' => (int)$items['org']->getId(),
        'auth_security_version' => (int)$items['user']->get('security_version')];
    require_once dirname(__DIR__) . '/omo/api/lms/inc/access.php';
    require_once dirname(__DIR__) . '/common/faq_popup_helper.php';
    $query = ['oid' => (int)$items['org']->getId(), 'cid' => (int)$items['root']->getId()];
    $fields = ['question' => 'FAQ read test', 'answer' => 'Read answer', 'isactive' => 1,
        'IDorganization' => $items['org']->getId(), 'IDholon' => $items['root']->getId()];
    $items['faq'] = mcpFixture(FAQ::class, $fields);
    $items['inactive_faq'] = mcpFixture(FAQ::class, array_replace($fields, ['isactive' => 0]));
    $items['other_faq'] = mcpFixture(FAQ::class, array_replace($fields,
        ['IDorganization' => $items['other_org']->getId(), 'IDholon' => $items['other_root']->getId()]));

    // Warm schema checks, then compare fresh snapshots with the same permission cache state.
    faqReadSnapshot($query, false);
    $legacy = faqReadSnapshot($query, false);
    $one = faqReadSnapshot($query, true);
    mcpCheck($legacy['rows'] === $one['rows'], 'Bulk loading and caching must preserve content, scores, scopes and actions.');
    mcpCheck(isset($one['rows'][$items['faq']->getId()]), 'The active local FAQ remains visible.');
    mcpCheck(!isset($one['rows'][$items['inactive_faq']->getId()]) && !isset($one['rows'][$items['other_faq']->getId()]),
        'Neither inaccessible fixture may appear.');
    mcpCheck($one['queries'] < $legacy['queries'], 'The optimized collection must execute fewer SQL queries.');

    for ($i = 0; $i < 20; $i++) {
        $items['faq_' . $i] = mcpFixture(FAQ::class, array_replace($fields, ['question' => 'FAQ read test ' . $i]));
    }
    $many = faqReadSnapshot($query, true);
    mcpCheck(count($many['rows']) === count($one['rows']) + 20, 'All additional visible questions are loaded.');
    mcpCheck($many['queries'] <= $one['queries'] + 2, 'Questions in the same context must not add per-question reads.');

    FAQ::hasVoteColumns();
    $voteQueryCounts = [];
    foreach ([0, 10000] as $count) {
        $faq = new FAQ();
        $faq->load($items['faq']->getId());
        $faq->set('positive_score', $count * .8);
        $faq->set('negative_score', $count * .2);
        $faq->set('total_votes', $count);
        $before = DbObject::getPerformanceCounters();
        ob_start(); faqPopupRenderVoteBlock($faq); $html = ob_get_clean();
        $after = DbObject::getPerformanceCounters();
        $voteQueryCounts[] = $after['queries'] - $before['queries'];
        mcpCheck(str_contains($html, 'data-faq-score="total">' . $count . '</strong>'), 'The block displays the stored vote count.');
    }
    mcpCheck($voteQueryCounts === [0, 0], 'Rendering votes must not query individual opinions.');
    $items['admin_user'] = mcpFixture(\dbObject\User::class, ['firstname' => 'FAQ admin',
        'email' => 'faq-admin-' . bin2hex(random_bytes(8)) . '@example.invalid', 'active' => 1]);
    $items['admin_membership'] = mcpFixture(\dbObject\UserOrganization::class, ['IDuser' => $items['admin_user']->getId(),
        'IDorganization' => $items['org']->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    $_SESSION = ['currentUser' => (int)$items['admin_user']->getId(), 'currentOrganization' => (int)$items['org']->getId(),
        'auth_security_version' => (int)$items['admin_user']->get('security_version')];
    $adminLegacy = faqReadSnapshot($query, false);
    $adminOptimized = faqReadSnapshot($query, true);
    mcpCheck($adminLegacy['rows'] === $adminOptimized['rows'], 'Administrator visibility and actions must also stay identical.');
    mcpCheck(isset($adminOptimized['rows'][$items['inactive_faq']->getId()]), 'An organization administrator still sees its inactive FAQ.');
    mcpCheck(!isset($adminOptimized['rows'][$items['other_faq']->getId()]), 'Organization administration must not expose other organizations.');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    commonEnableReadOnlyCaches();
    mcpCheck(!DbObject::isReadOnlyMemoizationEnabled(), 'Write requests must keep fresh permission checks.');
    echo 'faq_read_performance_test: OK (reference=' . $legacy['queries'] . ', optimized=' . $one['queries']
        . ', 20 extra FAQs=' . $many['queries'] . "; votes=0 SQL)\n";
} finally {
    DbObject::enableReadOnlyMemoization(false);
    mcpCleanup($items);
    $_SESSION = $previousSession;
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
