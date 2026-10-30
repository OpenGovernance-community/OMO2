<?php
declare(strict_types=1);
// Run as the web server user: both processes reuse the same fixture session.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['HTTP_HOST'] = 'localtest.me'; $_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{ArrayDbObject, Document, DocumentPvPoint, PerformanceRequest};

class PvPollTestSamples extends ArrayDbObject
{
    public static function objectName() { return PerformanceRequest::class; }
}

function pvPollRequest(array $post): array
{
    $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id(); session_write_close();
    $curl = curl_init('https://localhost/omo/api/documents/pv/action.php');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post), CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    $body = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'HTTP request failed.');
    return ['status' => $status, 'payload' => json_decode($body, true, 512, JSON_THROW_ON_ERROR)];
}
$before = $_SESSION; $items = mcpFixtures();
try {
    mcpEnableDocumentCreation($items);
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    for ($i = 0; $i < 16; $i++) {
        $items['role_' . $i] = mcpFixture(McpTestHolon::class, ['IDorganization' => $oid,
            'IDholon_org' => $items['root']->getId(), 'IDholon_parent' => $items['root']->getId(),
            'IDtypeholon' => 1, 'name' => 'Polling role ' . $i, 'active' => 1, 'visible' => 1]);
    }
    $items['pv'] = mcpFixture(Document::class, ['title' => 'Polling fixture', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_PREPARATION, 'IDorganization' => $oid, 'IDholon' => $items['role']->getId(),
        'IDuser' => $uid, 'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid, 'IDuser_pv_official_editor' => $uid, 'active' => 1]);
    $id = (int)$items['pv']->getId();
    foreach ([false, true] as $confidential) {
        $key = $confidential ? 'private_point' : 'point';
        $items[$key] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $id, 'IDuser_author' => $uid,
            'title' => $key, 'content' => '<p>Initial</p>', 'pointtype' => 'information',
            'is_confidential' => (int)$confidential, 'active' => 1]);
    }
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version'),
        'omo_pv_editor_tokens' => [$oid . ':' . $id . ':user:' . $uid => 'polling-token']];
    $post = ['action' => 'poll_updates', 'oid' => $oid, 'document_id' => $id, 'editor_token' => 'polling-token'];
    $full = pvPollRequest($post);
    mcpCheck($full['status'] === 200 && !empty($full['payload']['status']) && $full['payload']['unchanged'] === false,
        'The first poll returns the complete authorized state.');
    $ids = array_column($full['payload']['points'], 'id');
    mcpCheck(in_array((int)$items['point']->getId(), $ids, true)
        && in_array((int)$items['private_point']->getId(), $ids, true), 'The editor still sees confidential points.');
    if (commonPerformanceEnabled() && PerformanceRequest::available()) {
        $samples = new PvPollTestSamples();
        $samples->loadHydrated(['where' => [['field' => 'IDorganization', 'value' => $oid],
            ['field' => 'route', 'value' => '/omo/api/documents/pv/action.php'], ['field' => 'kind', 'value' => 'web']],
            'orderBy' => [['field' => 'id', 'dir' => 'DESC']], 'limit' => 1]);
        mcpCheck(count($samples) === 1 && (int)$samples[0]->get('query_count') < 250,
            'Full polling must reuse hierarchy and permission reads instead of repeating them for every control.');
    }
    $revision = $full['payload']['pollRevision'];
    $same = pvPollRequest($post + ['poll_revision' => $revision]);
    mcpCheck($same['status'] === 200 && $same['payload']['unchanged'] === true
        && !isset($same['payload']['points']) && !isset($same['payload']['document']), 'Stable polls only return the revision.');

    $lock = pvPollRequest(array_replace($post, ['action' => 'lock_point', 'point_id' => $items['point']->getId()]));
    mcpCheck($lock['status'] === 200 && !empty($lock['payload']['status']), 'Point acquisition still succeeds.');
    $save = pvPollRequest(array_replace($post, ['action' => 'save_point', 'point_id' => $items['point']->getId(),
        'title' => 'Saved title', 'content' => '<p>Saved after lock</p>', 'pointtype' => 'information', 'author' => 'user:' . $uid]));
    mcpCheck($save['status'] === 200 && !empty($save['payload']['status'])
        && $save['payload']['point']['title'] === 'Saved title', 'Response caches see the just-saved point.');
    $items['point']->load((int)$items['point']->getId(), true);
    mcpCheck($items['point']->get('title') === 'Saved title', 'The point was persisted.');
    $changed = pvPollRequest($post + ['poll_revision' => $revision]);
    mcpCheck($changed['status'] === 200 && $changed['payload']['unchanged'] === false
        && $changed['payload']['pollRevision'] !== $revision, 'A save invalidates polling state on the next request.');
    $stable = pvPollRequest($post + ['poll_revision' => $changed['payload']['pollRevision']]);
    mcpCheck($stable['payload']['unchanged'] === true, 'The new revision becomes stable.');

    $wrongOrganization = pvPollRequest(array_replace($post, ['oid' => $items['other_org']->getId(), 'poll_revision' => $revision]));
    mcpCheck($wrongOrganization['status'] === 403, 'An unchanged revision cannot bypass organization isolation.');
    $items['pv']->set('pvstage', Document::PV_STAGE_REVIEW);
    mcpCheck(!empty($items['pv']->save()['status']), 'Prepare review state.');
    $review = pvPollRequest($post);
    mcpCheck($review['status'] === 200 && $review['payload']['document']['pvStage'] === Document::PV_STAGE_REVIEW,
        'Review polling still returns the document.');
    $disallowed = pvPollRequest(array_replace($post, ['action' => 'add_point']));
    mcpCheck($disallowed['status'] === 403, 'Review restrictions still apply to mutations.');
    $items['pv']->set('pvstage', Document::PV_STAGE_VALIDATED);
    mcpCheck(!empty($items['pv']->save()['status']), 'Prepare validated state.');
    mcpCheck(pvPollRequest($post + ['poll_revision' => $review['payload']['pollRevision']])['status'] === 403,
        'A validated PV cannot be reopened through a stale revision.');
    echo "pv_action_polling_test: OK (HTTP full/unchanged, save visibility, locks, organization isolation and review/validation gates)\n";
} finally {
    $_SESSION = $before;
    mcpCleanup($items);
}
