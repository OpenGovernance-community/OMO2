<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/team/translations.php';

use dbObject\Holon;
use dbObject\Invitation;
use dbObject\Organization;

header('Content-Type: application/json; charset=UTF-8');
$sourceLang = omoTeamSourceLang();
$lang = omoTeamLoadTranslationBundle();

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_POST['oid'] ?? 0));
$holonId = (int)($_POST['hid'] ?? 0);
$userId = (int)($_POST['user_id'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));

if ($organizationId <= 0 || $holonId <= 0 || $userId <= 0 || $action === '') {
    http_response_code(400);
    echo json_encode(array(
        'status' => false,
        'message' => omoTeamT('team.api.invalid_action', [], $lang, $sourceLang),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$organization = new Organization();
$holon = new Holon();

if (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'message' => omoTeamT('team.api.context_not_found', [], $lang, $sourceLang),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

switch ($action) {
    case 'remove':
        if (!$holon->canEdit()) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'message' => omoTeamT('team.api.no_right_modify_context', [], $lang, $sourceLang),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $result = $holon->removeMember($userId, array(
            'organizationId' => $organizationId,
        ));
        break;

    case 'grant_admin':
        if (!$holon->isAllowed('CAN_ADD_ADMIN', false)) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'message' => omoTeamT('team.api.no_right_manage_admin', [], $lang, $sourceLang),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $result = $holon->setMemberContextAdmin($userId, true, $organizationId);
        break;

    case 'revoke_admin':
        if (!$holon->isAllowed('CAN_ADD_ADMIN', false)) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'message' => omoTeamT('team.api.no_right_manage_admin', [], $lang, $sourceLang),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        $result = $holon->setMemberContextAdmin($userId, false, $organizationId);
        break;

    case 'cancel_invitation':
        if (!$holon->canEdit()) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'message' => omoTeamT('team.api.no_right_modify_context', [], $lang, $sourceLang),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $pendingInvitation = Invitation::findPendingForOrganizationUser($organizationId, $userId);
        if (!($pendingInvitation instanceof Invitation) || !$pendingInvitation->isAdminInitiatedInvitation()) {
            http_response_code(404);
            echo json_encode(array(
                'status' => false,
                'message' => omoTeamT('team.api.pending_invitation_not_found', [], $lang, $sourceLang),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $result = $pendingInvitation->cancelByAdmin(array(
            'canceledByUserId' => (int)commonGetCurrentUserId(),
        ));
        break;

    case 'resend_invitation':
        if (!$holon->isAllowed('CAN_ADD_MEMBER', false)) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'message' => omoTeamT('team.api.no_right_add_member', [], $lang, $sourceLang),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $pendingInvitation = Invitation::findPendingForOrganizationUser($organizationId, $userId);
        if (!($pendingInvitation instanceof Invitation) || !$pendingInvitation->isAdminInitiatedInvitation()) {
            http_response_code(404);
            echo json_encode(array(
                'status' => false,
                'message' => omoTeamT('team.api.pending_admin_invitation_not_found', [], $lang, $sourceLang),
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        try {
            $pendingInvitation->sendEmail();
            $result = array(
                'status' => true,
                'message' => omoTeamT('team.api.invitation_resent', [], $lang, $sourceLang),
            );
        } catch (\Throwable $exception) {
            $result = array(
                'status' => false,
                'message' => trim((string)$exception->getMessage()) !== ''
                    ? (string)$exception->getMessage()
                    : omoTeamT('team.api.invitation_resend_failed', [], $lang, $sourceLang),
            );
        }
        break;

    default:
        $result = array(
            'status' => false,
            'message' => omoTeamT('team.api.unknown_action', [], $lang, $sourceLang),
        );
        break;
}

if (!($result['status'] ?? false)) {
    http_response_code(422);
}

echo json_encode(array(
    'status' => (bool)($result['status'] ?? false),
    'message' => (string)($result['message'] ?? omoTeamT('team.api.action_completed', [], $lang, $sourceLang)),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
