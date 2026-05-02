<?php
require_once dirname(__DIR__) . '/omo/api/bootstrap.php';

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

function omoUserContextHolonTypeLabel(Holon $holon)
{
    switch ((int)$holon->get('IDtypeholon')) {
        case 4:
            return 'organisation';
        case 3:
            return 'groupe';
        case 2:
            return 'cercle';
        case 1:
            return 'rôle';
        default:
            return 'contexte';
    }
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

$membership = $user->getOrganizationMembership($organizationId);
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
$showCurrentScope = (int)$currentHolon->getId() !== (int)$rootHolon->getId();
$currentScopeTypeLabel = omoUserContextHolonTypeLabel($currentHolon);
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
<div class="omo-user-context">
    <style>
    .omo-user-context {
        display: grid;
        gap: 16px;
        color: #0f172a;
    }

    .omo-user-context--error {
        padding: 18px;
        border-radius: 16px;
        background: #f8fafc;
        color: #475569;
    }

    .omo-user-context__hero {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 16px;
        align-items: center;
        padding: 18px;
        border: 1px solid #dbe2ea;
        border-radius: 20px;
        background: linear-gradient(135deg, #ffffff, #f8fbff);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
    }

    .omo-user-context__photo,
    .omo-user-context__photo-placeholder {
        width: 72px;
        height: 72px;
        border-radius: 999px;
        overflow: hidden;
        background: #e2e8f0;
        border: 2px solid #ffffff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
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
        color: #2563eb;
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
        color: #475569;
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
        background: #e2e8f0;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
    }

    .omo-user-context__badge--admin {
        background: rgba(37, 99, 235, 0.12);
        color: #1d4ed8;
    }

    .omo-user-context__badge--pending {
        background: rgba(100, 116, 139, 0.14);
        color: #475569;
    }

    .omo-user-context__meta {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .omo-user-context__meta-card,
    .omo-user-context__section {
        padding: 16px 18px;
        border: 1px solid #dbe2ea;
        border-radius: 18px;
        background: #ffffff;
    }

    .omo-user-context__meta-label,
    .omo-user-context__section-kicker {
        margin-bottom: 6px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        font-weight: 700;
    }

    .omo-user-context__meta-value {
        color: #0f172a;
        line-height: 1.4;
        word-break: break-word;
    }

    .omo-user-context__meta-value--muted,
    .omo-user-context__empty {
        color: #64748b;
    }

    .omo-user-context__section {
        display: grid;
        gap: 12px;
    }

    .omo-user-context__section h3 {
        margin: 0;
        font-size: 18px;
        line-height: 1.2;
    }

    .omo-user-context__roles {
        display: grid;
        gap: 10px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .omo-user-context__role {
        padding: 12px 14px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
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
        color: #475569;
    }

    .omo-user-context__role-status {
        flex: 0 0 auto;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(100, 116, 139, 0.14);
        color: #475569;
        font-size: 11px;
        font-weight: 700;
    }
    </style>

    <div class="omo-user-context__hero">
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
            <h2><?= omoApiEscape($displayName !== '' ? $displayName : ('Utilisateur ' . $userId)) ?></h2>
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
        <div class="omo-user-context__meta-card">
            <div class="omo-user-context__meta-label">E-mail</div>
            <div class="omo-user-context__meta-value<?= $email === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($email !== '' ? $email : 'Non renseigné') ?>
            </div>
        </div>
        <div class="omo-user-context__meta-card">
            <div class="omo-user-context__meta-label">Identifiant</div>
            <div class="omo-user-context__meta-value<?= $username === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($username !== '' ? $username : 'Non renseigné') ?>
            </div>
        </div>
        <div class="omo-user-context__meta-card">
            <div class="omo-user-context__meta-label">Ajout à l'organisation</div>
            <div class="omo-user-context__meta-value<?= $joinedAtLabel === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($joinedAtLabel !== '' ? $joinedAtLabel : 'Inconnu') ?>
            </div>
        </div>
        <div class="omo-user-context__meta-card">
            <div class="omo-user-context__meta-label">Dernière connexion</div>
            <div class="omo-user-context__meta-value<?= $lastSeenLabel === '' ? ' omo-user-context__meta-value--muted' : '' ?>">
                <?= omoApiEscape($lastSeenLabel !== '' ? $lastSeenLabel : 'Jamais') ?>
            </div>
        </div>
    </div>

    <?php if ($showCurrentScope): ?>
        <section class="omo-user-context__section">
            <div class="omo-user-context__section-kicker">Contexte courant</div>
            <h3>Rôles visibles depuis le <?= omoApiEscape($currentScopeTypeLabel) ?> <?= omoApiEscape($currentScopeName) ?></h3>

            <?php if (count($currentAssignments) === 0): ?>
                <div class="omo-user-context__empty">Aucun rôle visible dans ce contexte.</div>
            <?php else: ?>
                <ul class="omo-user-context__roles">
                    <?php foreach ($currentAssignments as $assignment): ?>
                        <li class="omo-user-context__role">
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

    <section class="omo-user-context__section">
        <div class="omo-user-context__section-kicker">Organisation</div>
        <h3>Rôles dans toute l'organisation</h3>

        <?php if (count($organizationAssignments) === 0): ?>
            <div class="omo-user-context__empty">Aucun rôle visible dans l'organisation.</div>
        <?php else: ?>
            <ul class="omo-user-context__roles">
                <?php foreach ($organizationAssignments as $assignment): ?>
                    <li class="omo-user-context__role">
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
