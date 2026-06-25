<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';

use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\User;

function omoDecisionParticipantQrCodesResolveIdentity(DecisionParticipant $participant, $organizationId)
{
    $organizationId = (int)$organizationId;
    $userId = (int)$participant->get('IDuser');
    $email = trim((string)$participant->get('email'));
    $displayName = trim((string)$participant->get('display_name'));

    if ($userId > 0) {
        $user = new User();
        if ($user->load($userId)) {
            $resolvedDisplayName = trim((string)$user->getScopedDisplayName($organizationId));
            $resolvedEmail = trim((string)$user->getScopedEmail($organizationId));

            return [
                'label' => $resolvedDisplayName !== '' ? $resolvedDisplayName : ($resolvedEmail !== '' ? $resolvedEmail : $participant->getIdentityLabel()),
                'email' => $resolvedEmail !== '' ? $resolvedEmail : $email,
            ];
        }
    }

    return [
        'label' => $displayName !== '' ? $displayName : ($email !== '' ? $email : $participant->getIdentityLabel()),
        'email' => $email,
    ];
}

function omoDecisionParticipantQrCodesResolveAuthorLabel(DecisionProcess $decision, $organizationId)
{
    $organizationId = (int)$organizationId;
    $userId = (int)$decision->get('IDuser');
    if ($userId <= 0) {
        return '';
    }

    $user = new User();
    if (!$user->load($userId)) {
        return '';
    }

    return trim((string)$user->getScopedDisplayName($organizationId));
}

function omoDecisionParticipantQrCodesToDateTime($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value;
    }

    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($value);
    } catch (Throwable $exception) {
        return null;
    }
}

function omoDecisionParticipantQrCodesFormatDateTime($value)
{
    $dateTime = omoDecisionParticipantQrCodesToDateTime($value);
    if (!$dateTime instanceof DateTimeInterface) {
        return '';
    }

    return $dateTime->format('d.m.Y H:i');
}

function omoDecisionParticipantQrCodesFormatRange($start, $end)
{
    $startLabel = omoDecisionParticipantQrCodesFormatDateTime($start);
    $endLabel = omoDecisionParticipantQrCodesFormatDateTime($end);

    if ($startLabel !== '' && $endLabel !== '') {
        return $startLabel . ' -> ' . $endLabel;
    }

    if ($startLabel !== '') {
        return $startLabel;
    }

    return $endLabel;
}

$sourceLang = [
    'decisions.qr_sheet.title' => [
        'text' => 'Imprimer les codes QR',
        'context' => 'Page title for the printable participant QR code sheet.',
    ],
    'decisions.qr_sheet.subtitle' => [
        'text' => 'Imprimez cette page puis decoupez les cartes pour distribuer un acces direct a chaque participant.',
        'context' => 'Intro text displayed above the printable participant QR code list.',
    ],
    'decisions.qr_sheet.print' => [
        'text' => 'Imprimer',
        'context' => 'Button label used to print the participant QR code sheet.',
    ],
    'decisions.qr_sheet.close' => [
        'text' => 'Fermer',
        'context' => 'Button label used to close the participant QR code sheet window.',
    ],
    'decisions.qr_sheet.organization' => [
        'text' => 'Organisation',
        'context' => 'Summary label for the organization shown on the participant QR code sheet.',
    ],
    'decisions.qr_sheet.context' => [
        'text' => 'Contexte',
        'context' => 'Summary label for the holon context shown on the participant QR code sheet.',
    ],
    'decisions.qr_sheet.status' => [
        'text' => 'Statut',
        'context' => 'Summary label for the decision status shown on the participant QR code sheet.',
    ],
    'decisions.qr_sheet.participants' => [
        'text' => 'Participants',
        'context' => 'Summary label for the participant count shown on the participant QR code sheet.',
    ],
    'decisions.qr_sheet.organizer' => [
        'text' => 'Organisateur',
        'context' => 'Label shown for the organizer block on one printable QR code card.',
    ],
    'decisions.qr_sheet.author' => [
        'text' => 'Auteur',
        'context' => 'Label shown for the decision author on the participant QR code sheet.',
    ],
    'decisions.qr_sheet.consultation_dates' => [
        'text' => 'Consultation',
        'context' => 'Label shown for the consultation date range on the participant QR code sheet.',
    ],
    'decisions.qr_sheet.vote_dates' => [
        'text' => 'Vote',
        'context' => 'Label shown for the evaluation or vote date range on the participant QR code sheet.',
    ],
    'decisions.qr_sheet.email' => [
        'text' => 'E-mail',
        'context' => 'Label shown before the participant email on one printable card.',
    ],
    'decisions.qr_sheet.empty' => [
        'text' => 'Aucun participant avec acces individuel n est disponible pour ce questionnaire.',
        'context' => 'Empty state shown when no printable participant QR code can be generated.',
    ],
    'decisions.edit.context.organization_invalid' => [
        'text' => 'Organisation invalide.',
        'context' => 'Error when the organization id is missing or invalid.',
    ],
    'decisions.edit.context.organization_not_found' => [
        'text' => 'Organisation introuvable.',
        'context' => 'Error when the organization cannot be loaded.',
    ],
    'decisions.edit.context.organization_denied' => [
        'text' => 'Acces refuse a cette organisation.',
        'context' => 'Error when the user cannot view the organization.',
    ],
    'decisions.edit.context.organization_manage_denied' => [
        'text' => 'Vous n avez pas les droits necessaires pour creer une prise de decision dans cette organisation.',
        'context' => 'Error when the user cannot manage an organization-level decision.',
    ],
    'decisions.edit.context.holon_not_found' => [
        'text' => 'Holon introuvable pour cette organisation.',
        'context' => 'Error when the requested holon is invalid.',
    ],
    'decisions.edit.context.holon_denied' => [
        'text' => 'Acces refuse a ce holon.',
        'context' => 'Error when the user cannot view the requested holon.',
    ],
    'decisions.edit.context.holon_manage_denied' => [
        'text' => 'Vous n avez pas les droits necessaires pour creer une prise de decision dans ce holon.',
        'context' => 'Error when the user cannot manage a holon-level decision.',
    ],
    'decisions.edit.context.decision_not_found' => [
        'text' => 'Prise de decision introuvable.',
        'context' => 'Error when the requested decision cannot be loaded.',
    ],
    'decisions.edit.context.decision_mismatch' => [
        'text' => 'Cette prise de decision n appartient pas a l organisation courante.',
        'context' => 'Error when the decision does not belong to the current organization.',
    ],
    'decisions.edit.context.decision_denied' => [
        'text' => 'Vous n avez pas les droits necessaires pour modifier cette prise de decision.',
        'context' => 'Error when the user cannot manage the requested decision.',
    ],
];

$lang = omoLoadTranslationBundle('omo_decision_participant_qr_codes', $sourceLang);
$escape = 'omoApiEscape';

$input = $_GET;
if (!isset($input['intent'])) {
    $input['intent'] = 'manage';
}

$context = omoDecisionResolveEditorContext($input);
$decision = !empty($context['decision']) && $context['decision'] instanceof DecisionProcess
    ? $context['decision']
    : null;

if (empty($context['status']) || !($decision instanceof DecisionProcess) || empty($context['canManage'])) {
    $errorKey = (string)($context['error_key'] ?? 'decisions.edit.context.decision_denied');
    http_response_code((int)($context['code'] ?? 403));
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape(t('decisions.qr_sheet.title', [], $lang, $sourceLang)) ?></title>
    <style>
        body{margin:0;font-family:Arial,sans-serif;background:#f6f7fb;color:#122033}
        .omo-decision-qr-sheet__error{max-width:720px;margin:48px auto;padding:32px;border-radius:20px;background:#fff;box-shadow:0 18px 40px rgba(15,23,42,.08)}
    </style>
</head>
<body>
    <div class="omo-decision-qr-sheet__error">
        <?= $escape(t($errorKey, [], $lang, $sourceLang)) ?>
    </div>
</body>
</html>
    <?php
    return;
}

$organization = $context['organization'] ?? null;
$effectiveHolon = $context['effectiveHolon'] ?? null;
$status = DecisionProcess::normalizeStatus($decision->get('status'));
$organizationId = (int)($context['organizationId'] ?? 0);
$organizationLabel = $organization ? trim((string)$organization->get('name')) : '';
$authorLabel = omoDecisionParticipantQrCodesResolveAuthorLabel($decision, $organizationId);
$rawContextLabel = ((int)$decision->get('IDholon') > 0 && $effectiveHolon)
    ? trim((string)$effectiveHolon->get('name'))
    : '';
$rootHolon = $organization ? $organization->getEnabledStructuralRootHolon() : null;
$isRootOrganizationHolon = $rootHolon && $effectiveHolon
    && (int)$rootHolon->getId() > 0
    && (int)$effectiveHolon->getId() === (int)$rootHolon->getId();
$hasSameLabelAsOrganization = $rawContextLabel !== ''
    && $organizationLabel !== ''
    && mb_strtolower($rawContextLabel, 'UTF-8') === mb_strtolower($organizationLabel, 'UTF-8');
$contextLabel = (!$isRootOrganizationHolon && !$hasSameLabelAsOrganization)
    ? $rawContextLabel
    : '';
$consultationRange = omoDecisionParticipantQrCodesFormatRange(
    $decision->get('consultation_start_at'),
    $decision->get('consultation_end_at')
);
$voteRange = omoDecisionParticipantQrCodesFormatRange(
    $decision->get('evaluation_start_at'),
    $decision->get('evaluation_end_at')
);
$decisionTitle = trim((string)$decision->get('title'));

if (
    in_array($status, [
        DecisionProcess::STATUS_DRAFT,
        DecisionProcess::STATUS_RESULTS,
        DecisionProcess::STATUS_ARCHIVED,
    ], true)
) {
    http_response_code(403);
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape(t('decisions.qr_sheet.title', [], $lang, $sourceLang)) ?></title>
    <style>
        body{margin:0;font-family:Arial,sans-serif;background:#f6f7fb;color:#122033}
        .omo-decision-qr-sheet__error{max-width:720px;margin:48px auto;padding:32px;border-radius:20px;background:#fff;box-shadow:0 18px 40px rgba(15,23,42,.08)}
    </style>
</head>
<body>
    <div class="omo-decision-qr-sheet__error">
        <?= $escape(t('decisions.edit.context.decision_denied', [], $lang, $sourceLang)) ?>
    </div>
</body>
</html>
    <?php
    return;
}

$participantCards = [];

foreach ($decision->getParticipants(true) as $participant) {
    if (!($participant instanceof DecisionParticipant)) {
        continue;
    }

    $participantRole = DecisionParticipant::normalizeRole($participant->get('role'));
    if (!in_array($participantRole, [DecisionParticipant::ROLE_OWNER, DecisionParticipant::ROLE_PARTICIPANT], true)) {
        continue;
    }

    $participantStatus = DecisionParticipant::normalizeStatus($participant->get('status'));
    if (in_array($participantStatus, [DecisionParticipant::STATUS_DECLINED, DecisionParticipant::STATUS_REVOKED], true)) {
        continue;
    }

    $identity = omoDecisionParticipantQrCodesResolveIdentity($participant, (int)($context['organizationId'] ?? 0));
    $accessUrl = trim((string)$participant->getPublicInvitationUrl());
    if ($accessUrl === '') {
        continue;
    }

    $participantCards[] = [
        'label' => trim((string)($identity['label'] ?? '')),
        'email' => trim((string)($identity['email'] ?? '')),
        'url' => $accessUrl,
    ];
}

usort($participantCards, static function (array $left, array $right): int {
    return strcasecmp(
        trim((string)($left['label'] ?? '')),
        trim((string)($right['label'] ?? ''))
    );
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape(t('decisions.qr_sheet.title', [], $lang, $sourceLang)) ?> - <?= $escape($decisionTitle) ?></title>
    <style>
        :root{
            --omo-qr-bg:#f3f5f9;
            --omo-qr-panel:#ffffff;
            --omo-qr-panel-soft:#eef3fb;
            --omo-qr-line:#d7dfec;
            --omo-qr-text:#142033;
            --omo-qr-muted:#526173;
            --omo-qr-accent:#245d8b;
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:Arial,sans-serif;background:var(--omo-qr-bg);color:var(--omo-qr-text)}
        .omo-decision-qr-sheet{max-width:1180px;margin:0 auto;padding:28px}
        .omo-decision-qr-sheet__topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px}
        .omo-decision-qr-sheet__title{margin:0 0 8px;font-size:32px;line-height:1.1}
        .omo-decision-qr-sheet__subtitle{margin:0;color:var(--omo-qr-muted);max-width:760px;line-height:1.55}
        .omo-decision-qr-sheet__actions{display:flex;gap:12px;flex-wrap:wrap}
        .omo-decision-qr-sheet__button{appearance:none;border:0;border-radius:999px;padding:12px 18px;font-size:14px;font-weight:700;cursor:pointer}
        .omo-decision-qr-sheet__button--main{background:var(--omo-qr-text);color:#fff}
        .omo-decision-qr-sheet__button--secondary{background:var(--omo-qr-panel);color:var(--omo-qr-text);box-shadow:0 8px 24px rgba(15,23,42,.08)}
        .omo-decision-qr-sheet__summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:24px}
        .omo-decision-qr-sheet__summary-card{padding:16px 18px;border-radius:18px;background:var(--omo-qr-panel);box-shadow:0 12px 30px rgba(15,23,42,.07)}
        .omo-decision-qr-sheet__summary-label{display:block;margin-bottom:6px;color:var(--omo-qr-muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
        .omo-decision-qr-sheet__summary-value{display:block;font-size:16px;font-weight:700;line-height:1.35}
        .omo-decision-qr-sheet__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
        .omo-decision-qr-sheet__card{display:flex;flex-direction:column;gap:14px;align-items:stretch;padding:16px;border:1px dashed var(--omo-qr-line);border-radius:24px;background:var(--omo-qr-panel)}
        .omo-decision-qr-sheet__participant{display:flex;flex-direction:column;gap:10px;min-width:0}
        .omo-decision-qr-sheet__participant-name{margin:0;font-size:22px;line-height:1.15;word-break:break-word}
        .omo-decision-qr-sheet__decision-title{margin:0;font-size:16px;line-height:1.25;font-weight:700;word-break:break-word}
        .omo-decision-qr-sheet__meta{display:grid;gap:8px}
        .omo-decision-qr-sheet__meta-line{padding:10px 12px;border-radius:14px;background:var(--omo-qr-panel-soft)}
        .omo-decision-qr-sheet__meta-label{display:block;margin-bottom:4px;color:var(--omo-qr-muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
        .omo-decision-qr-sheet__meta-value{display:block;font-size:13px;line-height:1.4;word-break:break-word}
        .omo-decision-qr-sheet__organizer-line{display:block}
        .omo-decision-qr-sheet__qr{display:flex;align-items:center;justify-content:center;padding:12px;border-radius:20px;background:#fff;border:1px solid var(--omo-qr-line);align-self:center}
        .omo-decision-qr-sheet__qr img{display:block;width:100%;max-width:164px;height:auto}
        .omo-decision-qr-sheet__empty{padding:32px;border-radius:22px;background:var(--omo-qr-panel);box-shadow:0 12px 30px rgba(15,23,42,.07);color:var(--omo-qr-muted)}
        @media (max-width:980px){
            .omo-decision-qr-sheet__summary{grid-template-columns:repeat(2,minmax(0,1fr))}
            .omo-decision-qr-sheet__grid{grid-template-columns:1fr}
        }
        @media (max-width:720px){
            .omo-decision-qr-sheet{padding:18px}
            .omo-decision-qr-sheet__topbar{flex-direction:column}
            .omo-decision-qr-sheet__summary{grid-template-columns:1fr}
            .omo-decision-qr-sheet__card{grid-template-columns:1fr}
            .omo-decision-qr-sheet__qr{max-width:240px;margin:0 auto}
        }
        @media print{
            @page{size:A4 portrait;margin:8mm}
            body{background:#fff}
            .omo-decision-qr-sheet{max-width:none;padding:0}
            .omo-decision-qr-sheet__actions{display:none}
            .omo-decision-qr-sheet__topbar{margin-bottom:10px}
            .omo-decision-qr-sheet__title{font-size:18px;margin-bottom:0}
            .omo-decision-qr-sheet__subtitle{display:none}
            .omo-decision-qr-sheet__summary{display:none}
            .omo-decision-qr-sheet__grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:8mm}
            .omo-decision-qr-sheet__summary-card,
            .omo-decision-qr-sheet__card{box-shadow:none}
            .omo-decision-qr-sheet__card{break-inside:avoid;page-break-inside:avoid;gap:3mm;padding:4mm;border-radius:10px;min-height:62mm}
            .omo-decision-qr-sheet__participant{gap:2mm}
            .omo-decision-qr-sheet__participant-name{font-size:14pt}
            .omo-decision-qr-sheet__decision-title{font-size:10pt}
            .omo-decision-qr-sheet__meta{gap:2mm}
            .omo-decision-qr-sheet__meta-line{padding:2.2mm 2.5mm;border-radius:6px}
            .omo-decision-qr-sheet__meta-label{font-size:7pt;margin-bottom:1mm}
            .omo-decision-qr-sheet__meta-value{font-size:8pt;line-height:1.25}
            .omo-decision-qr-sheet__qr{padding:2mm;border-radius:8px}
            .omo-decision-qr-sheet__qr img{max-width:30mm}
        }
    </style>
</head>
<body>
    <div class="omo-decision-qr-sheet">
        <div class="omo-decision-qr-sheet__topbar">
            <div>
                <h1 class="omo-decision-qr-sheet__title"><?= $escape($decisionTitle) ?></h1>
                <p class="omo-decision-qr-sheet__subtitle"><?= $escape(t('decisions.qr_sheet.subtitle', [], $lang, $sourceLang)) ?></p>
            </div>
            <div class="omo-decision-qr-sheet__actions">
                <button type="button" class="omo-decision-qr-sheet__button omo-decision-qr-sheet__button--main" onclick="window.print()"><?= $escape(t('decisions.qr_sheet.print', [], $lang, $sourceLang)) ?></button>
                <button type="button" class="omo-decision-qr-sheet__button omo-decision-qr-sheet__button--secondary" onclick="window.close()"><?= $escape(t('decisions.qr_sheet.close', [], $lang, $sourceLang)) ?></button>
            </div>
        </div>

        <div class="omo-decision-qr-sheet__summary">
            <div class="omo-decision-qr-sheet__summary-card">
                <span class="omo-decision-qr-sheet__summary-label"><?= $escape(t('decisions.qr_sheet.organization', [], $lang, $sourceLang)) ?></span>
                <span class="omo-decision-qr-sheet__summary-value"><?= $escape($organizationLabel) ?></span>
            </div>
            <div class="omo-decision-qr-sheet__summary-card">
                <span class="omo-decision-qr-sheet__summary-label"><?= $escape(t('decisions.qr_sheet.author', [], $lang, $sourceLang)) ?></span>
                <span class="omo-decision-qr-sheet__summary-value"><?= $escape($authorLabel) ?></span>
            </div>
            <div class="omo-decision-qr-sheet__summary-card">
                <span class="omo-decision-qr-sheet__summary-label"><?= $escape(t('decisions.qr_sheet.consultation_dates', [], $lang, $sourceLang)) ?></span>
                <span class="omo-decision-qr-sheet__summary-value"><?= $escape($consultationRange) ?></span>
            </div>
            <div class="omo-decision-qr-sheet__summary-card">
                <span class="omo-decision-qr-sheet__summary-label"><?= $escape(t('decisions.qr_sheet.vote_dates', [], $lang, $sourceLang)) ?></span>
                <span class="omo-decision-qr-sheet__summary-value"><?= $escape($voteRange) ?></span>
            </div>
        </div>

        <?php if (count($participantCards) === 0) { ?>
            <div class="omo-decision-qr-sheet__empty"><?= $escape(t('decisions.qr_sheet.empty', [], $lang, $sourceLang)) ?></div>
        <?php } else { ?>
            <div class="omo-decision-qr-sheet__grid">
                <?php foreach ($participantCards as $card) { ?>
                    <section class="omo-decision-qr-sheet__card">
                        <div class="omo-decision-qr-sheet__qr">
                            <img src="/qr/image/?url=<?= rawurlencode((string)$card['url']) ?>" alt="<?= $escape((string)$card['label']) ?>">
                        </div>
                        <div class="omo-decision-qr-sheet__participant">
                            <h2 class="omo-decision-qr-sheet__participant-name"><?= $escape((string)$card['label']) ?></h2>
                            <p class="omo-decision-qr-sheet__decision-title"><?= $escape($decisionTitle) ?></p>
                            <div class="omo-decision-qr-sheet__meta">
                                <?php if (trim((string)$card['email']) !== '') { ?>
                                    <div class="omo-decision-qr-sheet__meta-line">
                                        <span class="omo-decision-qr-sheet__meta-label"><?= $escape(t('decisions.qr_sheet.email', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-qr-sheet__meta-value"><?= $escape((string)$card['email']) ?></span>
                                    </div>
                                <?php } ?>
                                <div class="omo-decision-qr-sheet__meta-line">
                                    <span class="omo-decision-qr-sheet__meta-label"><?= $escape(t('decisions.qr_sheet.organizer', [], $lang, $sourceLang)) ?></span>
                                    <span class="omo-decision-qr-sheet__meta-value">
                                        <?php if ($organizationLabel !== '') { ?>
                                            <span class="omo-decision-qr-sheet__organizer-line"><?= $escape(t('decisions.qr_sheet.organization', [], $lang, $sourceLang)) ?>: <?= $escape($organizationLabel) ?></span>
                                        <?php } ?>
                                        <?php if ($contextLabel !== '') { ?>
                                            <span class="omo-decision-qr-sheet__organizer-line"><?= $escape(t('decisions.qr_sheet.context', [], $lang, $sourceLang)) ?>: <?= $escape($contextLabel) ?></span>
                                        <?php } ?>
                                        <?php if ($authorLabel !== '') { ?>
                                            <span class="omo-decision-qr-sheet__organizer-line"><?= $escape(t('decisions.qr_sheet.author', [], $lang, $sourceLang)) ?>: <?= $escape($authorLabel) ?></span>
                                        <?php } ?>
                                    </span>
                                </div>
                                <?php if ($consultationRange !== '') { ?>
                                    <div class="omo-decision-qr-sheet__meta-line">
                                        <span class="omo-decision-qr-sheet__meta-label"><?= $escape(t('decisions.qr_sheet.consultation_dates', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-qr-sheet__meta-value"><?= $escape($consultationRange) ?></span>
                                    </div>
                                <?php } ?>
                                <?php if ($voteRange !== '') { ?>
                                    <div class="omo-decision-qr-sheet__meta-line">
                                        <span class="omo-decision-qr-sheet__meta-label"><?= $escape(t('decisions.qr_sheet.vote_dates', [], $lang, $sourceLang)) ?></span>
                                        <span class="omo-decision-qr-sheet__meta-value"><?= $escape($voteRange) ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </section>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>
