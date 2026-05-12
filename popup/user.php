<?php
require_once dirname(__DIR__) . '/omo/api/bootstrap.php';
require_once dirname(__DIR__) . '/common/user_competence_ui.php';

use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;

function omoUserContextFormatDate($value)
{
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if (class_exists('IntlDateFormatter')) {
        $formatter = new IntlDateFormatter('fr_CH', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE);
        $formatted = $formatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y');
}

$organizationId = (int)($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0));
$userId = (int)($_GET['id'] ?? 0);
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($organizationId <= 0 || $userId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-user-context omo-user-context--error">Contexte utilisateur invalide.</div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-user-context omo-user-context--error">Organisation introuvable.</div>
    <?php
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-user-context omo-user-context--error">Acces refuse a cette organisation.</div>
    <?php
    exit;
}

$rootHolon = $organization->getStructuralRootHolon();
if ($rootHolon === null) {
    http_response_code(404);
    ?>
    <div class="omo-user-context omo-user-context--error">Aucun contexte organisationnel n'est disponible.</div>
    <?php
    exit;
}

$currentHolon = $rootHolon;
if ($currentHolonId > 0 && (int)$rootHolon->getId() !== $currentHolonId) {
    $candidate = new Holon();
    if (!$candidate->load($currentHolonId) || !$candidate->isDescendantOf($rootHolon->getId())) {
        http_response_code(404);
        ?>
        <div class="omo-user-context omo-user-context--error">Contexte introuvable pour cette organisation.</div>
        <?php
        exit;
    }

    $canViewCandidate = $candidate->canViewDetail()
        || (function_exists('commonCurrentShareContainsHolon') && commonCurrentShareContainsHolon($candidate));
    if (!$canViewCandidate) {
        http_response_code(403);
        ?>
        <div class="omo-user-context omo-user-context--error">Acces refuse a ce contexte.</div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$user = new User();
if (!$user->load($userId)) {
    http_response_code(404);
    ?>
    <div class="omo-user-context omo-user-context--error">Utilisateur introuvable.</div>
    <?php
    exit;
}

if (!$user->canViewDetail()) {
    http_response_code(403);
    ?>
    <div class="omo-user-context omo-user-context--error">Acces refuse a cet utilisateur.</div>
    <?php
    exit;
}

$membership = $user->getOrganizationMembership($organizationId);
$currentViewerUserId = (int)commonGetCurrentUserId();
$displayName = trim((string)$user->getScopedDisplayName($organizationId));
$email = trim((string)$user->getScopedEmail($organizationId));
$username = trim((string)$user->getScopedUsername($organizationId));
$photoUrl = trim((string)$user->getScopedProfilePhotoUrl($organizationId));
$joinedAt = $membership ? ($membership->get('datecreation') instanceof DateTimeInterface ? $membership->get('datecreation') : $membership->getGlobalCreatedAt()) : $user->get('datecreation');
$lastSeenAt = $membership ? ($membership->get('dateconnexion') instanceof DateTimeInterface ? $membership->get('dateconnexion') : $membership->getGlobalLastConnectionAt()) : $user->get('dateconnexion');
$joinedAtLabel = omoUserContextFormatDate($joinedAt);
$lastSeenLabel = omoUserContextFormatDate($lastSeenAt);
$isPending = $membership ? !(bool)$membership->get('active') : false;
$isAdmin = $membership ? $membership->isOrganizationAdmin() : false;
$currentAssignments = $currentHolon->getVisibleRoleAssignmentsForUser($userId, [
    'organizationId' => $organizationId,
]);
$organizationAssignments = $rootHolon->getVisibleRoleAssignmentsForUser($userId, [
    'organizationId' => $organizationId,
]);
$competenceRows = $user->getVisibleCompetenceRows($organizationId, $currentViewerUserId);
$canValidateCompetences = $currentViewerUserId > 0
    && $currentViewerUserId !== $userId
    && commonCurrentUserHasOrganizationAccess($organizationId)
    && (!function_exists('commonGetCurrentShareToken') || commonGetCurrentShareToken() === '');
$popupReloadUrl = '/popup/user.php?id=' . (int)$userId . '&oid=' . (int)$organizationId . ($currentHolonId > 0 ? '&cid=' . (int)$currentHolonId : '');
$showCurrentScope = (int)$currentHolon->getId() !== (int)$rootHolon->getId();
$currentScopeName = trim((string)$currentHolon->getDisplayName());
$secondaryLabel = $email !== '' ? $email : ($username !== '' ? '@' . $username : '');
$initials = 'P';

if ($displayName !== '') {
    $words = preg_split('/\s+/u', $displayName) ?: [];
    $initials = '';
    foreach ($words as $word) {
        $word = trim((string)$word);
        if ($word === '') {
            continue;
        }

        $initials .= mb_substr($word, 0, 1, 'UTF-8');
        if (mb_strlen($initials, 'UTF-8') >= 2) {
            break;
        }
    }

    if ($initials === '') {
        $initials = mb_substr($displayName, 0, 1, 'UTF-8');
    }
}

$initials = mb_strtoupper($initials !== '' ? $initials : 'P', 'UTF-8');
?>
<div class="omo-user-context" data-user-competence-popup-url="<?= omoApiEscape($popupReloadUrl) ?>">
    <style>
    .omo-user-context {
        display: grid;
        gap: 16px;
        color: var(--color-text, #1f2937);
    }

    .omo-user-context--error {
        padding: 18px;
        border-radius: 16px;
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text-light, #6b7280);
        border: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-user-context__hero {
        grid-template-columns: auto 1fr;
        align-items: center;
        --generic-hero-gap: 16px;
        --generic-hero-padding: 18px;
        --generic-hero-radius: 20px;
        --generic-hero-shadow: var(--shadow-md, 0 12px 24px rgba(0,0,0,0.12));
    }

    .omo-user-context__photo,
    .omo-user-context__photo-placeholder {
        width: 72px;
        height: 72px;
        border-radius: 999px;
        overflow: hidden;
        background: var(--color-surface-alt, #f0f2f5);
        border: 2px solid var(--color-surface, #ffffff);
        box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
    }

    .omo-user-context__photo {
        object-fit: cover;
        display: block;
    }

    .omo-user-context__photo-placeholder {
        display: grid;
        place-items: center;
        font-size: 24px;
        font-weight: 700;
        color: var(--color-primary, #2563eb);
    }

    .omo-user-context__identity {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .omo-user-context__identity h2 {
        margin: 0;
        font-size: 22px;
        line-height: 1.1;
    }

    .omo-user-context__secondary {
        color: var(--color-text-light, #6b7280);
        word-break: break-word;
    }

    .omo-user-context__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .omo-user-context__badge {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0 10px;
        border-radius: 999px;
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text, #1f2937);
        font-size: 12px;
        font-weight: 700;
        border: 1px solid var(--color-border, #e5e7eb);
    }

    .omo-user-context__badge--admin {
        background: color-mix(in srgb, var(--color-primary, #2563eb) 14%, var(--color-surface, #ffffff));
        color: var(--color-primary, #2563eb);
        border-color: color-mix(in srgb, var(--color-primary, #2563eb) 30%, var(--color-border, #e5e7eb));
    }

    .omo-user-context__badge--pending {
        background: rgba(100, 116, 139, 0.14);
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__meta {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .omo-user-context__meta-card,
    .omo-user-context__section {
        --generic-section-gap: 12px;
    }

    .omo-user-context__meta-label,
    .omo-user-context__section-kicker {
        margin-bottom: 6px;
    }

    .omo-user-context__meta-value {
        color: var(--color-text, #1f2937);
        line-height: 1.4;
        word-break: break-word;
    }

    .omo-user-context__meta-value--muted,
    .omo-user-context__empty {
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__section {
        --generic-section-gap: 12px;
    }

    .omo-user-context__roles {
        display: grid;
        gap: 10px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .omo-user-context__role {
        --generic-soft-panel-border: var(--color-border, #e5e7eb);
        --generic-soft-panel-background: var(--color-surface-alt, #f0f2f5);
        --generic-soft-panel-radius: 14px;
    }

    .omo-user-context__role-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .omo-user-context__role-name {
        font-weight: 700;
        line-height: 1.3;
    }

    .omo-user-context__role-path {
        margin-top: 4px;
        font-size: 13px;
        line-height: 1.4;
        color: var(--color-text-light, #6b7280);
    }

    .omo-user-context__role-status {
        flex: 0 0 auto;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(100, 116, 139, 0.14);
        color: var(--color-text-light, #6b7280);
        font-size: 11px;
        font-weight: 700;
    }

    .omo-user-context__competences {
        display: grid;
        gap: 8px;
    }

    .omo-user-context__competence {
        --generic-soft-panel-radius: 12px;
        --generic-soft-panel-padding: 10px 12px;
    }

    .omo-user-context__competence-line {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        scrollbar-width: thin;
    }

    .omo-user-context__competence-main {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 1 1 auto;
    }

    .omo-user-context__competence-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 700;
        line-height: 1.2;
    }

    .omo-user-context__competence-description {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--color-text-light, #6b7280);
        font-size: 12px;
    }

    .omo-user-context__competence-badges,
    .omo-user-context__competence-form {
        display: flex;
        flex-wrap: nowrap;
        gap: 6px;
        align-items: center;
    }

    .omo-user-context__competence-badge {
        display: inline-flex;
        align-items: center;
        min-height: 22px;
        padding: 0 8px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--color-primary, #2563eb) 12%, var(--color-surface, #ffffff));
        color: var(--color-primary, #2563eb);
        border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 28%, var(--color-border, #e5e7eb));
        font-size: 11px;
        font-weight: 700;
    }

    .omo-user-context__competence-badge--muted {
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text-light, #6b7280);
        border-color: var(--color-border, #e5e7eb);
    }

    .omo-user-context__competence-validators {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 auto;
    }

    .omo-user-context__competence-validators-label {
        color: var(--color-text-light, #6b7280);
        font-size: 11px;
        flex: 0 0 auto;
    }

    .omo-user-context__competence-avatar-stack {
        display: flex;
        flex-wrap: nowrap;
        gap: 4px;
        align-items: center;
        flex: 0 0 auto;
    }

    .omo-user-context__competence-avatar,
    .omo-user-context__competence-avatar--placeholder {
        width: 22px;
        height: 22px;
        border-radius: 999px;
        border: 1px solid var(--color-border, #e5e7eb);
        background: var(--color-surface-alt, #f0f2f5);
    }

    .omo-user-context__competence-avatar {
        object-fit: cover;
        display: block;
    }

    .omo-user-context__competence-avatar--placeholder {
        display: inline-grid;
        place-items: center;
        font-size: 9px;
        font-weight: 700;
        color: var(--color-primary, #2563eb);
    }

    .omo-user-context__competence-form {
        gap: 6px;
        flex: 0 0 auto;
    }

    .omo-user-context__competence-form select {
        min-width: 118px;
        min-height: 32px;
        padding: 4px 8px;
        border-radius: 9px;
        font-size: 12px;
    }

    .omo-user-context__competence-form .generic-action-button {
        min-height: 32px;
        padding: 6px 10px;
        border-radius: 9px;
        font-size: 12px;
        line-height: 1.1;
    }

    .omo-user-context__competence-feedback {
        color: var(--color-text-light, #6b7280);
        font-size: 13px;
        line-height: 1.45;
    }

    .omo-user-context__competence-feedback.is-success {
        color: #15803d;
    }

    .omo-user-context__competence-feedback.is-error {
        color: #b91c1c;
    }

    @media (max-width: 640px) {
        .omo-user-context__competence {
            --generic-soft-panel-padding: 8px 10px;
        }

        .omo-user-context__competence-line {
            gap: 8px;
        }

        .omo-user-context__competence-name {
            max-width: 180px;
        }
    }
    </style>

    <div class="omo-user-context__hero generic-hero-panel">
        <?php if ($photoUrl !== ''): ?>
            <img
                src="<?= omoApiEscape($photoUrl) ?>"
                alt="<?= omoApiEscape($displayName !== '' ? $displayName : ('Utilisateur ' . $userId)) ?>"
                class="omo-user-context__photo"
            >
        <?php else: ?>
            <div class="omo-user-context__photo-placeholder" aria-hidden="true"><?= omoApiEscape($initials) ?></div>
        <?php endif; ?>

        <div class="omo-user-context__identity">
            <h2 class="generic-card-title generic-card-title--large"><?= omoApiEscape($displayName !== '' ? $displayName : ('Utilisateur ' . $userId)) ?></h2>
            <?php if ($secondaryLabel !== ''): ?>
                <div class="omo-user-context__secondary"><?= omoApiEscape($secondaryLabel) ?></div>
            <?php endif; ?>
            <div class="omo-user-context__badges">
                <?php if ($isAdmin): ?>
                    <span class="omo-user-context__badge omo-user-context__badge--admin">Admin de l'organisation</span>
                <?php endif; ?>
                <?php if ($isPending): ?>
                    <span class="omo-user-context__badge omo-user-context__badge--pending">Invitation en attente</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="omo-user-context__meta">
        <div class="omo-user-context__meta-card generic-section">
            <div class="omo-user-context__meta-label generic-card-title generic-card-title--eyebrow">E-mail</div>
            <div class="omo-user-context__meta-value<?= $email === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($email !== '' ? $email : 'Non renseigné') ?>
            </div>
        </div>
        <div class="omo-user-context__meta-card generic-section">
            <div class="omo-user-context__meta-label generic-card-title generic-card-title--eyebrow">Identifiant</div>
            <div class="omo-user-context__meta-value<?= $username === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($username !== '' ? $username : 'Non renseigné') ?>
            </div>
        </div>
        <div class="omo-user-context__meta-card generic-section">
            <div class="omo-user-context__meta-label generic-card-title generic-card-title--eyebrow">Ajout à l'organisation</div>
            <div class="omo-user-context__meta-value<?= $joinedAtLabel === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($joinedAtLabel !== '' ? $joinedAtLabel : 'Inconnu') ?>
            </div>
        </div>
        <div class="omo-user-context__meta-card generic-section">
            <div class="omo-user-context__meta-label generic-card-title generic-card-title--eyebrow">Dernière connexion</div>
            <div class="omo-user-context__meta-value<?= $lastSeenLabel === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($lastSeenLabel !== '' ? $lastSeenLabel : 'Jamais') ?>
            </div>
        </div>
    </div>

 

 



<div class="generic-tabs" data-generic-tabs>
    <div class="generic-tabs__list">
        <button class="generic-tabs__tab is-active"
            data-generic-tab
            data-generic-tab-target="panel-a">Rôles (context)</button>
        <button class="generic-tabs__tab"
            data-generic-tab
            data-generic-tab-target="panel-b">Rôles (orga)</button>
        <button class="generic-tabs__tab"
            data-generic-tab
            data-generic-tab-target="panel-c">Compétences</button>
    </div>
    <div class="generic-tabs__panels">
        <div id="panel-b" class="generic-tabs__panel" data-generic-tab-panel>
            
    <section class="omo-user-context__section generic-section generic-section--stack">
        <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Organisation</div>
 
        <?php if (count($organizationAssignments) === 0): ?>
            <div class="omo-user-context__empty">Aucun rôle visible dans l'organisation.</div>
        <?php else: ?>
            <ul class="omo-user-context__roles">
                <?php foreach ($organizationAssignments as $assignment): ?>
                    <li class="omo-user-context__role generic-soft-panel">
                        <div class="omo-user-context__role-head">
                            <div>
                                <div class="omo-user-context__role-name"><?= omoApiEscape($assignment['name'] ?: ('Rôle ' . (int)$assignment['holonId'])) ?></div>
                                <?php if ((string)$assignment['pathLabel'] !== ''): ?>
                                    <div class="omo-user-context__role-path"><?= omoApiEscape($assignment['pathLabel']) ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($assignment['isPending'])): ?>
                                <span class="omo-user-context__role-status">En attente</span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

        </div>
        <div id="panel-a" class="generic-tabs__panel" data-generic-tab-panel hidden>
            
   <?php if ($showCurrentScope): ?>
        <section class="omo-user-context__section generic-section generic-section--stack">
            <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Contexte courant</div>
 
            <?php if (count($currentAssignments) === 0): ?>
                <div class="omo-user-context__empty">Aucun rôle visible dans ce contexte.</div>
            <?php else: ?>
                <ul class="omo-user-context__roles">
                    <?php foreach ($currentAssignments as $assignment): ?>
                        <li class="omo-user-context__role generic-soft-panel">
                            <div class="omo-user-context__role-head">
                                <div>
                                    <div class="omo-user-context__role-name"><?= omoApiEscape($assignment['name'] ?: ('Rôle ' . (int)$assignment['holonId'])) ?></div>
                                    <?php if ((string)$assignment['pathLabel'] !== ''): ?>
                                        <div class="omo-user-context__role-path"><?= omoApiEscape($assignment['pathLabel']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($assignment['isPending'])): ?>
                                    <span class="omo-user-context__role-status">En attente</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

        </div>
        <div id="panel-c" class="generic-tabs__panel" data-generic-tab-panel hidden>

   <section class="omo-user-context__section generic-section generic-section--stack">
        <div class="omo-user-context__section-kicker generic-card-title generic-card-title--eyebrow">Competences</div>
 
        <?php if (count($competenceRows) === 0): ?>
            <div class="omo-user-context__empty">Aucune competence visible pour le moment.</div>
        <?php else: ?>
            <div class="omo-user-context__competences">
                <?php foreach ($competenceRows as $competenceRow): ?>
                    <article class="omo-user-context__competence generic-soft-panel">
                        <div class="omo-user-context__competence-line">
                            <div class="omo-user-context__competence-main">
                                <div class="omo-user-context__competence-name" title="<?= omoApiEscape((string)$competenceRow['name']) ?>"><?= omoApiEscape((string)$competenceRow['name']) ?></div>
                                <?php if (trim((string)($competenceRow['description'] ?? '')) !== ''): ?>
                                    <div class="omo-user-context__competence-description" title="<?= omoApiEscape((string)$competenceRow['description']) ?>"><?= omoApiEscape((string)$competenceRow['description']) ?></div>
                                <?php endif; ?>
                                <div class="omo-user-context__competence-badges">
                                    <span class="omo-user-context__competence-badge"><?= omoApiEscape((string)$competenceRow['levelLabel']) ?></span>
                                    <span class="omo-user-context__competence-badge omo-user-context__competence-badge--muted"><?= omoApiEscape((string)$competenceRow['categoryLabel']) ?></span>
                                    <?php if ((string)$competenceRow['scope'] === 'organization'): ?>
                                        <span class="omo-user-context__competence-badge omo-user-context__competence-badge--muted"><?= omoApiEscape((string)$competenceRow['scopeLabel']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="omo-user-context__competence-validators">
                                <span class="omo-user-context__competence-validators-label"><?= (int)$competenceRow['validationCount'] ?> avis</span>
                                <?php if (!empty($competenceRow['validators'])): ?>
                                    <div class="omo-user-context__competence-avatar-stack">
                                        <?php foreach ($competenceRow['validators'] as $validator): ?>
                                            <?php omoRenderCompetenceAvatar([
                                                'photoUrl' => (string)($validator['photoUrl'] ?? ''),
                                                'displayName' => (string)($validator['displayName'] ?? ''),
                                                'initials' => (string)($validator['initials'] ?? 'P'),
                                                'levelLabel' => (string)($validator['levelLabel'] ?? ''),
                                            ], 'omo-user-context__competence-avatar'); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($canValidateCompetences): ?>
                                <form class="omo-user-context__competence-form" data-user-competence-validate-form="1">
                                    <input type="hidden" name="id" value="<?= (int)$competenceRow['id'] ?>">
                                    <select name="level" class="generic-form-control" title="Votre reconnaissance pour cette competence">
                                        <?php omoRenderCompetenceLevelOptions((int)$competenceRow['currentViewerValidationLevel'], true); ?>
                                    </select>
                                    <button type="submit" class="generic-action-button generic-action-button--main">
                                        <?= (int)$competenceRow['currentViewerValidationLevel'] > 0 ? 'Maj' : 'Valider' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($canValidateCompetences): ?>
                <div class="omo-user-context__competence-feedback" data-user-competence-feedback="1"></div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

        </div>
    </div>
</div>

</div>
<?php if ($canValidateCompetences && count($competenceRows) > 0): ?>
<script>
(function () {
    var root = document.querySelector('.omo-user-context[data-user-competence-popup-url]');
    if (!root) {
        return;
    }

    var feedback = root.querySelector('[data-user-competence-feedback="1"]');

    function setFeedback(message, type) {
        if (!feedback) {
            return;
        }

        feedback.textContent = message || '';
        feedback.className = 'omo-user-context__competence-feedback';
        if (type === 'success') {
            feedback.classList.add('is-success');
        } else if (type === 'error') {
            feedback.classList.add('is-error');
        }
    }

    function parseResponse(response) {
        return response.text().then(function (text) {
            try {
                return JSON.parse(text);
            } catch (error) {
                return {
                    status: false,
                    message: 'Reponse serveur invalide.'
                };
            }
        });
    }

    function reloadPopup() {
        var popupUrl = root.getAttribute('data-user-competence-popup-url') || '';
        if (popupUrl === '') {
            return;
        }

        if (window.jQuery && document.getElementById('popup_content')) {
            window.jQuery('#popup_content').load(popupUrl);
            return;
        }

        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(popupUrl);
        }
    }

    root.querySelectorAll('[data-user-competence-validate-form="1"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            setFeedback('', '');

            fetch('/omo/api/user_competence_validate.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(parseResponse)
                .then(function (result) {
                    if (!result || !result.status) {
                        setFeedback(result && result.message ? result.message : "Impossible d'enregistrer cette validation.", 'error');
                        return;
                    }

                    setFeedback(result.message || 'Validation enregistree.', 'success');
                    reloadPopup();
                })
                .catch(function () {
                    setFeedback("Impossible d'enregistrer cette validation.", 'error');
                });
        });
    });
})();
</script>
<?php endif; ?>
