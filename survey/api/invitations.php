<?php

require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/assessment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    surveyJsonResponse(['status' => false, 'error' => 'method_not_allowed'], 405);
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
    surveyJsonResponse(['status' => false, 'error' => 'invalid_payload'], 400);
}

$organizationId = (int)($payload['organizationId'] ?? 0);
$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    surveyJsonResponse(['status' => false, 'error' => 'not_authenticated'], 401);
}
if (!commonCurrentUserCanUseAdminMode($organizationId)) {
    surveyJsonResponse(['status' => false, 'error' => 'organization_access_denied'], 403);
}

$result = \dbObject\OrganizationalMaturityInvitation::issueForSelections(
    $organizationId,
    is_array($payload['holonIds'] ?? null) ? $payload['holonIds'] : [],
    is_array($payload['userIds'] ?? null) ? $payload['userIds'] : [],
    is_array($payload['emails'] ?? null) ? $payload['emails'] : []
);

surveyJsonResponse($result, !empty($result['status']) ? 200 : 422);
