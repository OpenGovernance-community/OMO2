<?php

require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/assessment.php';

$lang = loadTranslationBundle('survey_prototype', 'fr', $sourceLang);
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$token = surveyNormalizeInvitationToken($_GET['token'] ?? '');
$publicLink = $token !== ''
    ? \dbObject\OrganizationalMaturityPublicLink::findByToken($token)
    : null;
$organization = $publicLink ? $publicLink->getOrganizationObject() : null;

if (!$publicLink || !$organization) {
    http_response_code(404);
    ?><!DOCTYPE html>
    <html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Survey</title></head>
    <body><p><?= $escape(t('survey.public_access.invalid', [], $lang, $sourceLang)) ?></p></body></html><?php
    exit;
}

$organizationId = (int)$organization->getId();
$organizationName = trim((string)$organization->get('name'));
$publicAccessUrl = appBuildAbsoluteUrl('/survey/public-access.php?token=' . rawurlencode($token));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim((string)($_POST['public_access_action'] ?? 'request_code'));
    $email = trim(mb_strtolower((string)($_POST['email'] ?? ''), 'UTF-8'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        surveyJsonResponse([
            'status' => false,
            'message' => t('survey.public_access.invalid_email', [], $lang, $sourceLang),
        ], 422);
    }

    if ($action === 'verify_code') {
        $invitation = \dbObject\OrganizationalMaturityInvitation::findByOrganizationAndEmail($organizationId, $email);
        if (!$invitation) {
            surveyJsonResponse([
                'status' => false,
                'message' => t('survey.public_access.code_missing', [], $lang, $sourceLang),
            ], 422);
        }
        $verification = $invitation->verifyPublicAccessCode($_POST['code'] ?? '', true);
        if (empty($verification['status'])) {
            $reason = (string)($verification['reason'] ?? '');
            $key = [
                'empty_code' => 'survey.public_access.code_empty',
                'missing_code' => 'survey.public_access.code_missing',
                'expired_code' => 'survey.public_access.code_expired',
                'invalid_code' => 'survey.public_access.code_invalid',
            ][$reason] ?? 'survey.public_access.process_failed';
            surveyJsonResponse(['status' => false, 'message' => t($key, [], $lang, $sourceLang)], 422);
        }
        surveyJsonResponse([
            'status' => true,
            'message' => t('survey.public_access.code_valid', [], $lang, $sourceLang),
            'redirectUrl' => '/survey/?invitation=' . rawurlencode((string)$invitation->get('token')),
        ]);
    }

    $invitation = \dbObject\OrganizationalMaturityInvitation::findOrCreateForEmail($organizationId, $email);
    if (!$invitation || !$invitation->sendPublicAccessCodeEmail($publicAccessUrl)) {
        surveyJsonResponse([
            'status' => false,
            'message' => t('survey.public_access.process_failed', [], $lang, $sourceLang),
        ], 500);
    }
    surveyJsonResponse([
        'status' => true,
        'message' => t('survey.public_access.code_sent', ['email' => $email], $lang, $sourceLang),
        'nextAction' => 'verify_code',
    ]);
}

$pageTitle = t('survey.public_access.title', ['organization' => $organizationName], $lang, $sourceLang);
$clientLabels = [
    'processFailed' => t('survey.public_access.process_failed', [], $lang, $sourceLang),
];
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light" data-color-style="ocean-blue">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#073a59">
    <title><?= $escape($pageTitle) ?></title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/survey/survey.css?v=20260915-drafts">
</head>
<body>
    <main class="survey-public-access">
        <section class="generic-section survey-public-access__card">
            <p class="survey-eyebrow"><?= $escape(t('survey.public_access.eyebrow', [], $lang, $sourceLang)) ?></p>
            <h1><?= $escape($pageTitle) ?></h1>
            <p class="survey-public-access__intro"><?= $escape(t('survey.public_access.intro', ['organization' => $organizationName], $lang, $sourceLang)) ?></p>
            <form class="survey-public-access__form" id="surveyPublicAccessForm" method="post" action="<?= $escape((string)($_SERVER['REQUEST_URI'] ?? '')) ?>">
                <input type="hidden" id="surveyPublicAccessAction" name="public_access_action" value="request_code">
                <label>
                    <span><?= $escape(t('survey.public_access.email', [], $lang, $sourceLang)) ?></span>
                    <input class="generic-form-control" id="surveyPublicAccessEmail" name="email" type="email" autocomplete="email" placeholder="<?= $escape(t('survey.public_access.email_placeholder', [], $lang, $sourceLang)) ?>" required>
                </label>
                <label id="surveyPublicAccessCodeRow" hidden>
                    <span><?= $escape(t('survey.public_access.code', [], $lang, $sourceLang)) ?></span>
                    <input class="generic-form-control" id="surveyPublicAccessCode" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="<?= $escape(t('survey.public_access.code_placeholder', [], $lang, $sourceLang)) ?>">
                </label>
                <p class="survey-public-access__feedback" id="surveyPublicAccessFeedback" aria-live="polite"></p>
                <div class="survey-public-access__actions" id="surveyPublicAccessSendActions">
                    <button class="generic-action-button generic-action-button--main" id="surveyPublicAccessSend" type="submit"><?= $escape(t('survey.public_access.send', [], $lang, $sourceLang)) ?></button>
                </div>
                <div class="survey-public-access__actions" id="surveyPublicAccessVerifyActions" hidden>
                    <button class="generic-action-button generic-action-button--secondary" id="surveyPublicAccessResend" type="button"><?= $escape(t('survey.public_access.resend', [], $lang, $sourceLang)) ?></button>
                    <button class="generic-action-button generic-action-button--main" id="surveyPublicAccessVerify" type="submit"><?= $escape(t('survey.public_access.enter', [], $lang, $sourceLang)) ?></button>
                </div>
            </form>
        </section>
    </main>
    <?= commonPageScriptTags('/survey/public-access.js', [
    'labels' => $clientLabels,
]) ?>
</body>
</html>
