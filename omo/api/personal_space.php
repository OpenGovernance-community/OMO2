<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/common/user_profile_ui.php';

use dbObject\ArrayDecisionProcess;
use dbObject\ArrayDocument;
use dbObject\ArrayUserOrganization;
use dbObject\History;
use dbObject\Organization;

$sourceLang = [
    'personal_space.heading' => [
        'text' => 'Espace personnel',
        'context' => 'Main title of the personal space panel shown on the right side of the OMO workspace.',
    ],
    'personal_space.intro' => [
        'text' => 'Un resume rapide des sujets qui vous concernent dans cet espace.',
        'context' => 'Intro text displayed below the personal space title.',
    ],
    'personal_space.empty' => [
        'text' => 'Aucun resume personnel disponible avec les applications actives pour le moment.',
        'context' => 'Fallback empty state when no supported applications are enabled in the sidebar.',
    ],
    'personal_space.login_required' => [
        'text' => 'Connectez-vous pour afficher votre resume personnel.',
        'context' => 'Message shown when the personal space is requested without a logged in user.',
    ],
    'personal_space.open_app' => [
        'text' => 'Ouvrir',
        'context' => 'Button label used to open the full application from the personal space card.',
    ],
    'personal_space.section.decisions' => [
        'text' => 'Decisions',
        'context' => 'Title of the decision summary card in the personal space panel.',
    ],
    'personal_space.section.documents' => [
        'text' => 'Documents',
        'context' => 'Title of the documents summary card in the personal space panel.',
    ],
    'personal_space.section.team' => [
        'text' => 'Team',
        'context' => 'Title of the team summary card in the personal space panel.',
    ],
    'personal_space.section.structure' => [
        'text' => 'Structure',
        'context' => 'Title of the structure summary card in the personal space panel.',
    ],
    'personal_space.documents.empty' => [
        'text' => 'Aucun document recent dans ce contexte.',
        'context' => 'Empty state shown when no recent documents are available in the current context.',
    ],
    'personal_space.team.empty' => [
        'text' => 'Aucun anniversaire proche a afficher.',
        'context' => 'Empty state shown when no upcoming personal or professional anniversaries are found.',
    ],
    'personal_space.structure.empty' => [
        'text' => 'Aucune modification recente a afficher.',
        'context' => 'Empty state shown when no recent structure history items are available.',
    ],
    'personal_space.decisions.empty' => [
        'text' => 'Aucune decision a suivre pour le moment.',
        'context' => 'Empty state shown when the user has no tracked decisions in the current organization.',
    ],
    'personal_space.decisions.finalize' => [
        'one' => '{count} decision en preparation a finaliser',
        'other' => '{count} decisions en preparation a finaliser',
        'context' => 'Decision summary line for draft or scheduled decisions managed by the user.',
    ],
    'personal_space.decisions.consultation' => [
        'one' => '{count} consultation en cours',
        'other' => '{count} consultations en cours',
        'context' => 'Decision summary line for consultation processes currently active.',
    ],
    'personal_space.decisions.action' => [
        'one' => '{count} decision a prendre',
        'other' => '{count} decisions a prendre',
        'context' => 'Decision summary line for active decisions the user can answer.',
    ],
    'personal_space.decisions.responded' => [
        'one' => 'dont {count} deja repondue',
        'other' => 'dont {count} deja repondues',
        'context' => 'Extra detail appended to the action summary line for already submitted responses.',
    ],
    'personal_space.decisions.results' => [
        'one' => '{count} decision terminee avec resultat a consulter',
        'other' => '{count} decisions terminees avec resultats a consulter',
        'context' => 'Decision summary line for finished decisions with available results.',
    ],
    'personal_space.team.tag.personal' => [
        'text' => 'Perso',
        'context' => 'Short badge shown for a personal birthday in the team card.',
    ],
    'personal_space.team.tag.pro' => [
        'text' => 'Pro',
        'context' => 'Short badge shown for a professional join-date anniversary in the team card.',
    ],
    'personal_space.team.pro.today' => [
        'text' => "Anniversaire pro aujourd'hui",
        'context' => 'Headline shown when a professional anniversary happens today.',
    ],
    'personal_space.team.pro.soon_prefix' => [
        'text' => 'Anniversaire pro dans',
        'context' => 'Prefix used before the remaining day count for a nearby professional anniversary.',
    ],
    'personal_space.team.pro.month_prefix' => [
        'text' => 'Anniversaire pro en',
        'context' => 'Prefix used when the professional anniversary is farther away and summarized by month.',
    ],
    'personal_space.date.unknown' => [
        'text' => 'Date inconnue',
        'context' => 'Fallback label shown when no usable date is available for a listed item.',
    ],
];

$lang = omoLoadTranslationBundle('omo_personal_space_panel', $sourceLang);

$currentOrganizationId = isset($_GET['oid']) ? (int)$_GET['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
$currentHolonId = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$currentUserId = (int)commonGetCurrentUserId();

$organization = new Organization();
if ($currentOrganizationId <= 0 || !$organization->load($currentOrganizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-personal-space omo-personal-space--empty">
        <div class="omo-personal-space__scroll">
            <section class="generic-section generic-section--stack omo-personal-space__hero">
                <span class="generic-card-title generic-card-title--section"><?= omoApiEscape(t('personal_space.heading', [], $lang, $sourceLang)) ?></span>
                <p class="omo-personal-space__hero-text"><?= omoApiEscape(t('personal_space.empty', [], $lang, $sourceLang)) ?></p>
            </section>
        </div>
    </div>
    <?php
    exit;
}

$enabledAppHashes = array_fill_keys($organization->getEnabledApplicationHashes($currentUserId), true);
$supportedAppHashes = array('decision', 'documents', 'team', 'structure');
$hasSupportedApp = false;
foreach ($supportedAppHashes as $supportedAppHash) {
    if (!empty($enabledAppHashes[$supportedAppHash])) {
        $hasSupportedApp = true;
        break;
    }
}

$formatDateTime = static function ($value, $includeTime = false) use ($lang, $sourceLang): string {
    if (!$value instanceof DateTimeInterface) {
        return t('personal_space.date.unknown', [], $lang, $sourceLang);
    }

    return $value->format($includeTime ? 'd.m.Y H:i' : 'd.m.Y');
};

$decisionProcesses = new ArrayDecisionProcess();
$decisionSummary = !empty($enabledAppHashes['decision']) && $currentUserId > 0
    ? $decisionProcesses->buildPersonalSpaceSummary($currentOrganizationId, $currentUserId, $currentHolonId, 3)
    : null;
$documents = new ArrayDocument();
$recentDocuments = array();
if (!empty($enabledAppHashes['documents'])) {
    $documents->loadRecentForOrganizationContext($currentOrganizationId, $currentHolonId, 5);
    $recentDocuments = $documents->buildPersonalSpaceItems($currentOrganizationId);
}
$memberships = new ArrayUserOrganization();
$teamEvents = !empty($enabledAppHashes['team']) && $currentUserId > 0
    ? $memberships->buildUpcomingCelebrations($currentOrganizationId, 6, null, array(
        'proToday' => t('personal_space.team.pro.today', [], $lang, $sourceLang),
        'proSoonPrefix' => t('personal_space.team.pro.soon_prefix', [], $lang, $sourceLang),
        'proMonthPrefix' => t('personal_space.team.pro.month_prefix', [], $lang, $sourceLang),
    ))
    : array();

$structureHistory = !empty($enabledAppHashes['structure'])
    ? History::fetchHolonFeedPage($currentOrganizationId, $currentHolonId, 5, 0, $currentHolonId <= 0)
    : array('items' => array());
$historyItems = is_array($structureHistory['items'] ?? null) ? $structureHistory['items'] : array();
?>
<div class="omo-personal-space<?= $currentUserId <= 0 ? ' omo-personal-space--guest' : '' ?>">
    <div class="omo-personal-space__scroll">
        <section class="generic-section generic-section--stack omo-personal-space__hero">
            <span class="generic-card-title generic-card-title--section"><?= omoApiEscape(t('personal_space.heading', [], $lang, $sourceLang)) ?></span>
            <p class="omo-personal-space__hero-text">
                <?= omoApiEscape($currentUserId > 0 ? t('personal_space.intro', [], $lang, $sourceLang) : t('personal_space.login_required', [], $lang, $sourceLang)) ?>
            </p>
        </section>

        <?php if ($currentUserId <= 0): ?>
            <section class="generic-section generic-section--stack omo-personal-space__card">
                <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.login_required', [], $lang, $sourceLang)) ?></p>
            </section>
        <?php elseif (!$hasSupportedApp): ?>
            <section class="generic-section generic-section--stack omo-personal-space__card">
                <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.empty', [], $lang, $sourceLang)) ?></p>
            </section>
        <?php else: ?>
            <?php if (!empty($enabledAppHashes['decision']) && is_array($decisionSummary)): ?>
                <?php
                $decisionCounts = $decisionSummary['counts'] ?? array();
                $actionLine = t('personal_space.decisions.action', [
                    'count' => (string)(int)($decisionCounts['action'] ?? 0),
                ], $lang, $sourceLang);
                $respondedCount = (int)($decisionCounts['actionResponded'] ?? 0);
                if ($respondedCount > 0) {
                    $actionLine .= ' (' . t('personal_space.decisions.responded', [
                        'count' => (string)$respondedCount,
                    ], $lang, $sourceLang) . ')';
                }
                $decisionLines = array(
                    t('personal_space.decisions.finalize', ['count' => (string)(int)($decisionCounts['finalize'] ?? 0)], $lang, $sourceLang),
                    t('personal_space.decisions.consultation', ['count' => (string)(int)($decisionCounts['consultation'] ?? 0)], $lang, $sourceLang),
                    $actionLine,
                    t('personal_space.decisions.results', ['count' => (string)(int)($decisionCounts['results'] ?? 0)], $lang, $sourceLang),
                );
                $hasDecisionActivity = array_sum(array_map('intval', $decisionCounts)) > 0;
                ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.decisions', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="decision"><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($hasDecisionActivity): ?>
                        <ul class="omo-personal-space__summary-list">
                            <?php foreach ($decisionLines as $decisionLine): ?>
                                <li><?= omoApiEscape($decisionLine) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.decisions.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['documents'])): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.documents', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="documents"><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($recentDocuments !== array()): ?>
                        <div class="omo-personal-space__item-list">
                            <?php foreach ($recentDocuments as $documentItem): ?>
                                <button
                                    type="button"
                                    class="omo-personal-space__item-button"
                                    data-omo-personal-space-document-url="<?= omoApiEscape($documentItem['contextUrl'] ?? '') ?>"
                                    data-omo-personal-space-document-title="<?= omoApiEscape($documentItem['title'] ?? '') ?>"
                                >
                                    <span class="omo-personal-space__item-title"><?= omoApiEscape($documentItem['title'] ?? '') ?></span>
                                    <span class="omo-personal-space__item-meta"><?= omoApiEscape($formatDateTime($documentItem['datemodification'] ?? $documentItem['datecreation'] ?? null, true)) ?></span>
                                    <?php if (trim((string)($documentItem['visibility']['badgeText'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-meta"><?= omoApiEscape((string)$documentItem['visibility']['badgeText']) ?></span>
                                    <?php endif; ?>
                                    <?php if (trim((string)($documentItem['description'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-copy"><?= omoApiEscape($documentItem['description']) ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.documents.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['team'])): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.team', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="team"><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($teamEvents !== array()): ?>
                        <div class="omo-personal-space__item-list">
                            <?php foreach ($teamEvents as $event): ?>
                                <?php
                                $tagType = trim((string)($event['tagType'] ?? ''));
                                $tagLabel = $tagType === 'pro'
                                    ? t('personal_space.team.tag.pro', [], $lang, $sourceLang)
                                    : t('personal_space.team.tag.personal', [], $lang, $sourceLang);
                                ?>
                                <button
                                    type="button"
                                    class="omo-personal-space__item-button"
                                    data-omo-personal-space-user-id="<?= (int)($event['userId'] ?? 0) ?>"
                                >
                                    <span class="omo-personal-space__item-topline">
                                        <span class="omo-personal-space__item-title"><?= omoApiEscape($event['displayName'] ?? '') ?></span>
                                        <span class="omo-personal-space__tag"><?= omoApiEscape($tagLabel) ?></span>
                                    </span>
                                    <span class="omo-personal-space__item-meta"><?= omoApiEscape($event['headline'] ?? '') ?></span>
                                    <?php if (trim((string)($event['detail'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-copy"><?= omoApiEscape($event['detail']) ?></span>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.team.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($enabledAppHashes['structure'])): ?>
                <section class="generic-section generic-section--stack omo-personal-space__card">
                    <div class="omo-personal-space__section-head">
                        <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(t('personal_space.section.structure', [], $lang, $sourceLang)) ?></span>
                        <button type="button" class="omo-personal-space__section-action" data-omo-personal-space-route-token="structure"><?= omoApiEscape(t('personal_space.open_app', [], $lang, $sourceLang)) ?></button>
                    </div>

                    <?php if ($historyItems !== array()): ?>
                        <div class="omo-personal-space__item-list">
                            <?php foreach ($historyItems as $historyItem): ?>
                                <?php
                                $historyDate = null;
                                $historyDateValue = trim((string)($historyItem['datecreation'] ?? ''));
                                if ($historyDateValue !== '') {
                                    try {
                                        $historyDate = new DateTimeImmutable($historyDateValue);
                                    } catch (Throwable $exception) {
                                        $historyDate = null;
                                    }
                                }
                                ?>
                                <div class="omo-personal-space__item-card">
                                    <span class="omo-personal-space__item-topline">
                                        <span class="omo-personal-space__item-title"><?= omoApiEscape($historyItem['actionLabel'] ?? '') ?></span>
                                        <span class="omo-personal-space__item-meta"><?= omoApiEscape($formatDateTime($historyDate, true)) ?></span>
                                    </span>
                                    <span class="omo-personal-space__item-copy"><?= omoApiEscape($historyItem['contentDisplay'] ?? '') ?></span>
                                    <?php if (trim((string)($historyItem['authorDisplayName'] ?? '')) !== ''): ?>
                                        <span class="omo-personal-space__item-meta"><?= omoApiEscape($historyItem['authorDisplayName']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="omo-personal-space__empty"><?= omoApiEscape(t('personal_space.structure.empty', [], $lang, $sourceLang)) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
