<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{FAQ, UserHolon};

$items = mcpFixtures();
$previousSession = $_SESSION;
try {
    $uid = (int)$items['user']->getId();
    $oid = (int)$items['org']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $_GET = ['oid' => $oid, 'cid' => (int)$items['role']->getId()];
    $_REQUEST = $_GET;
    require_once dirname(__DIR__) . '/omo/api/lms/inc/access.php';
    $items['assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $uid, 'IDholon' => $items['role']->getId(), 'active' => 1, 'is_membership' => 1]);
    $fields = ['IDorganization' => $oid, 'IDholon' => $items['role']->getId(), 'isactive' => 1,
        'question' => 'Published question', 'answer' => '<p>First &amp; second</p><p>Next line</p>',
        'detail' => '<script>secret script</script><style>secret style</style><div>Useful detail</div>'];
    $items['published'] = mcpFixture(FAQ::class, $fields);
    $items['unpublished'] = mcpFixture(FAQ::class, array_replace($fields, ['isactive' => 0]));
    $items['draft'] = mcpFixture(FAQ::class, array_replace($fields, ['request_ai_draft' => 1]));
    $items['empty'] = mcpFixture(FAQ::class, array_replace($fields, ['answer' => '', 'detail' => '']));
    $items['foreign'] = mcpFixture(FAQ::class, array_replace($fields,
        ['IDorganization' => $items['other_org']->getId(), 'IDholon' => $items['other_root']->getId()]));
    // Visibility in the diagram alone is not a read restriction; an archived space is.
    $items['hidden']->set('archived_at', new DateTime()); $items['hidden']->save();
    $items['inaccessible_faq'] = mcpFixture(FAQ::class, array_replace($fields, ['IDholon' => $items['hidden']->getId()]));
    $context = FAQ::resolvePopupContext($oid, $items['role']->getId());
    mcpCheck(is_array($context), 'Fixture context must be accessible.');
    $sources = array_column(FAQ::loadAiSources($context), null, 'id');
    $published = $sources[(int)$items['published']->getId()] ?? null;
    mcpCheck(is_array($published), 'Published FAQ must be searchable.');
    mcpCheck($published['answer'] === "First & second\nNext line" && $published['detail'] === 'Useful detail', 'Send full readable text, preserving paragraphs without scripts or styles.');
    foreach (['unpublished', 'draft', 'empty', 'foreign', 'inaccessible_faq'] as $name) {
        mcpCheck(!isset($sources[(int)$items[$name]->getId()]), 'Exclude inaccessible or unreviewed content: ' . $name);
    }
    $rootContext = FAQ::resolvePopupContext($oid, $items['root']->getId());
    $descendants = array_column(FAQ::loadAiSources($rootContext, 'descendants'), null, 'id');
    mcpCheck(isset($descendants[(int)$items['published']->getId()])
        && !isset($descendants[(int)$items['inaccessible_faq']->getId()]), 'Descendant search includes readable children and rejects archived spaces.');
    // Organization administrators can see unpublished questions, but they are never AI evidence.
    $items['membership']->setOrganizationAdmin(true); $items['membership']->save();
    $_SESSION['isAdminByOrganization'][$oid] = true;
    $sources = array_column(FAQ::loadAiSources($context), null, 'id');
    foreach (['unpublished', 'draft', 'empty', 'foreign'] as $name) {
        mcpCheck(!isset($sources[(int)$items[$name]->getId()]), 'Admin search must also exclude unpublished or foreign evidence: ' . $name);
    }
    echo "PASS FAQ AI source visibility, publication, admin isolation and HTML extraction\n";
} finally {
    $_SESSION = $previousSession;
    mcpCleanup($items);
}
