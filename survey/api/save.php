<?php

require_once dirname(__DIR__, 2) . '/shared_functions.php';
require_once dirname(__DIR__) . '/assessment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    surveyJsonResponse(['status' => false, 'error' => 'method_not_allowed'], 405);
}

$body = file_get_contents('php://input');
$payload = json_decode(is_string($body) ? $body : '', true);
if (!is_array($payload)) {
    surveyJsonResponse(['status' => false, 'error' => 'invalid_payload'], 400);
}

$answers = $payload['answers'] ?? null;
$privateToken = surveyNormalizePrivateToken($payload['privateToken'] ?? '');
$invitationToken = surveyNormalizeInvitationToken($payload['invitationToken'] ?? '');

if ($invitationToken !== '') {
    $invitation = \dbObject\OrganizationalMaturityInvitation::findByToken($invitationToken);
    if (!$invitation) {
        surveyJsonResponse(['status' => false, 'error' => 'invalid_invitation_link'], 404);
    }

    $assessment = \dbObject\OrganizationalMaturityAssessment::findByInvitation((int)$invitation->getId());
    if ($assessment) {
        if (!$assessment->updateAnswers(is_array($answers) ? $answers : [])) {
            surveyJsonResponse(['status' => false, 'error' => 'save_failed'], 422);
        }
    } else {
        $created = \dbObject\OrganizationalMaturityAssessment::createFromAnswers(is_array($answers) ? $answers : []);
        if ($created === false || !$created['assessment']->attachToInvitation($invitation)) {
            surveyJsonResponse(['status' => false, 'error' => 'save_failed'], 422);
        }
        $assessment = $created['assessment'];
    }

    surveyJsonResponse([
        'status' => true,
        'invitation' => true,
        'assessmentId' => (int)$assessment->getId(),
    ]);
} elseif ($privateToken !== '') {
    $assessment = \dbObject\OrganizationalMaturityAssessment::findByPrivateToken($privateToken);
    if (!$assessment) {
        surveyJsonResponse(['status' => false, 'error' => 'invalid_private_link'], 404);
    }

    if (!$assessment->updateAnswers(is_array($answers) ? $answers : [])) {
        surveyJsonResponse(['status' => false, 'error' => 'save_failed'], 422);
    }
} else {
    $created = \dbObject\OrganizationalMaturityAssessment::createFromAnswers(is_array($answers) ? $answers : []);
    if ($created === false) {
        surveyJsonResponse(['status' => false, 'error' => 'save_failed'], 422);
    }

    $assessment = $created['assessment'];
    $privateToken = $created['privateToken'];
}

surveyJsonResponse(array_merge([
    'status' => true,
    'assessmentId' => (int)$assessment->getId(),
    'privateToken' => $privateToken,
], surveyBuildAssessmentUrls(
    (string)$assessment->get('public_token'),
    $privateToken
)));
