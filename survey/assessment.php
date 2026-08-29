<?php

function surveyNormalizePrivateToken($token)
{
    $token = trim((string)$token);
    return preg_match('/^[a-f0-9]{64}$/i', $token) ? strtolower($token) : '';
}

function surveyNormalizePublicToken($token)
{
    $token = trim((string)$token);
    return preg_match('/^(?:[a-f0-9]{16}|[a-f0-9]{48})$/i', $token) ? strtolower($token) : '';
}

function surveyNormalizeInvitationToken($token)
{
    $token = trim((string)$token);
    return preg_match('/^[a-f0-9]{32}$/i', $token) ? strtolower($token) : '';
}

function surveyBuildAssessmentUrls($publicToken, $privateToken)
{
    return [
        'publicUrl' => appBuildAbsoluteUrl('/survey/public.php?token=' . rawurlencode((string)$publicToken)),
        'privateUrl' => appBuildAbsoluteUrl('/survey/?edit=' . rawurlencode((string)$privateToken)),
        'associateUrl' => appBuildAbsoluteUrl('/survey/associate.php?token=' . rawurlencode((string)$privateToken)),
    ];
}

function surveyJsonResponse(array $payload, $statusCode = 200)
{
    http_response_code((int)$statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
