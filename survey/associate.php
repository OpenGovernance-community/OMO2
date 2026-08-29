<?php

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once __DIR__ . '/assessment.php';

$sourceLang = [
    'survey.associate.page_title' => [
        'text' => 'Associer le résultat à OMO',
        'context' => 'Browser title of the survey organization association page.',
    ],
    'survey.associate.login_intro' => [
        'text' => 'Connectez-vous pour associer ce résultat de maturité à une organisation OMO.',
        'context' => 'Intro shown in the shared email-code login page before associating a survey result.',
    ],
    'survey.associate.eyebrow' => [
        'text' => 'Résultat de maturité',
        'context' => 'Eyebrow on the survey organization association page.',
    ],
    'survey.associate.title' => [
        'text' => 'À quelle organisation associer ce résultat ?',
        'context' => 'Heading of the survey organization association page.',
    ],
    'survey.associate.intro' => [
        'text' => 'Le résultat restera lié à votre profil et pourra ensuite être comparé aux perceptions des autres membres de cette organisation.',
        'context' => 'Explanation on the survey organization association page.',
    ],
    'survey.associate.empty' => [
        'text' => 'Aucune organisation active n’est associée à ce compte pour le moment.',
        'context' => 'Empty state on the survey organization association page.',
    ],
    'survey.associate.submit' => [
        'text' => 'Associer le résultat',
        'context' => 'Submit button on the survey organization association page.',
    ],
    'survey.associate.cancel' => [
        'text' => 'Retour au questionnaire',
        'context' => 'Link returning from the organization association page to the editable survey.',
    ],
    'survey.associate.error' => [
        'text' => 'Cette organisation ne peut pas être utilisée pour ce résultat.',
        'context' => 'Error when an invalid organization is submitted on the survey organization association page.',
    ],
    'survey.associate.not_found' => [
        'text' => 'Résultat introuvable.',
        'context' => 'Error shown when a private survey result link is invalid.',
    ],
    'survey.associate.done' => [
        'text' => 'Le résultat est maintenant associé à cette organisation.',
        'context' => 'Confirmation shown after associating a survey result to an organization.',
    ],
];
$lang = loadTranslationBundle('survey_associate', 'fr', $sourceLang);
$escape = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
$privateToken = surveyNormalizePrivateToken($_GET['token'] ?? ($_POST['token'] ?? ''));
$assessment = $privateToken !== ''
    ? \dbObject\OrganizationalMaturityAssessment::findByPrivateToken($privateToken)
    : null;

if (!$assessment) {
    http_response_code(404);
    exit($escape(t('survey.associate.not_found', [], $lang, $sourceLang)));
}

$returnTo = '/survey/associate.php?token=' . rawurlencode($privateToken);
if ((int)commonGetCurrentUserId() <= 0) {
    commonRenderMagicLoginPage([
        'title' => t('survey.associate.page_title', [], $lang, $sourceLang),
        'appName' => 'OMO',
        'intro' => t('survey.associate.login_intro', [], $lang, $sourceLang),
        'returnTo' => $returnTo,
    ]);
}

$currentUserId = (int)commonGetCurrentUserId();
$csrfKey = 'survey_associate_csrf_' . hash('sha256', $privateToken);
if (empty($_SESSION[$csrfKey])) {
    $_SESSION[$csrfKey] = bin2hex(random_bytes(24));
}
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf'] ?? '');
    $organizationId = (int)($_POST['organization_id'] ?? 0);
    if (!hash_equals((string)$_SESSION[$csrfKey], $csrf) || !$assessment->attachToUserOrganization($currentUserId, $organizationId)) {
        $error = t('survey.associate.error', [], $lang, $sourceLang);
    } else {
        header('Location: /survey/?edit=' . rawurlencode($privateToken));
        exit;
    }
}

$memberships = new \dbObject\ArrayUserOrganization();
$memberships->loadActiveForUser($currentUserId);
$organizations = [];
$selectedOrganizationId = (int)$assessment->get('IDorganization');
foreach ($memberships as $membership) {
    if (!$membership instanceof \dbObject\UserOrganization || !$membership->load((int)$membership->getId())) {
        continue;
    }
    $organization = new \dbObject\Organization();
    $organizationId = (int)$membership->get('IDorganization');
    if ($organizationId > 0 && $organization->load($organizationId)) {
        $organizations[$organizationId] = $organization;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light" data-color-style="ocean-blue">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $escape(t('survey.associate.page_title', [], $lang, $sourceLang)) ?></title>
    <link rel="stylesheet" href="/common/assets/components.css">
    <link rel="stylesheet" href="/survey/survey.css">
</head>
<body>
    <main class="survey-page survey-associate-page">
        <section class="survey-associate generic-soft-panel">
            <p class="survey-eyebrow"><?= $escape(t('survey.associate.eyebrow', [], $lang, $sourceLang)) ?></p>
            <h1><?= $escape(t('survey.associate.title', [], $lang, $sourceLang)) ?></h1>
            <p class="survey-associate__intro"><?= $escape(t('survey.associate.intro', [], $lang, $sourceLang)) ?></p>
            <?php if ($message !== ''): ?>
                <p class="survey-associate__message"><?= $escape($message) ?></p>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <p class="survey-associate__error"><?= $escape($error) ?></p>
            <?php endif; ?>
            <?php if (count($organizations) === 0): ?>
                <p class="survey-associate__message"><?= $escape(t('survey.associate.empty', [], $lang, $sourceLang)) ?></p>
            <?php else: ?>
                <form method="post" class="survey-associate__form">
                    <input type="hidden" name="token" value="<?= $escape($privateToken) ?>">
                    <input type="hidden" name="csrf" value="<?= $escape($_SESSION[$csrfKey]) ?>">
                    <div class="survey-associate__choices">
                        <?php foreach ($organizations as $organizationId => $organization): ?>
                            <label class="survey-associate__choice">
                                <input type="radio" name="organization_id" value="<?= (int)$organizationId ?>" required<?= (int)$organizationId === $selectedOrganizationId ? ' checked' : '' ?>>
                                <span>
                                    <strong><?= $escape($organization->get('name')) ?></strong>
                                    <?php if (trim((string)$organization->get('shortname')) !== ''): ?>
                                        <small><?= $escape($organization->get('shortname')) ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button class="generic-action-button generic-action-button--main" type="submit"><?= $escape(t('survey.associate.submit', [], $lang, $sourceLang)) ?></button>
                </form>
            <?php endif; ?>
            <a class="generic-action-button generic-action-button--secondary" href="/survey/?edit=<?= rawurlencode($privateToken) ?>"><?= $escape(t('survey.associate.cancel', [], $lang, $sourceLang)) ?></a>
        </section>
    </main>
</body>
</html>
