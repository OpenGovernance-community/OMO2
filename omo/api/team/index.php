<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;
use dbObject\UserOrganization;

function omoTeamHolonTypeLabel(Holon $holon)
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
            return 'holon';
    }
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
$currentHolonId = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

if ($organizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state">Organisation invalide.</div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state">Organisation introuvable.</div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

$currentHolon = $organization->getStructuralRootHolon();
if ($currentHolon === null) {
    http_response_code(404);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-team__empty omo-empty-state">Aucun holon racine n'a été trouvé pour cette organisation.</div>
            </div>
        </div>
    </div>
    <?php
    exit;
}

if ($currentHolonId > 0 && (int)$currentHolon->getId() !== $currentHolonId) {
    $candidate = new Holon();
    if (!$candidate->load($currentHolonId) || !$candidate->isDescendantOf($currentHolon->getId())) {
        http_response_code(404);
        ?>
        <div class="omo-team omo-panel-view">
            <div class="omo-panel-view__body">
                <div class="omo-panel-view__body_content">
                    <div class="omo-team__empty omo-empty-state">Holon introuvable pour cette organisation.</div>
                </div>
            </div>
        </div>
        <?php
        exit;
    }

    $currentHolon = $candidate;
}

$rawMemberCards = $currentHolon->getAssociatedMemberCards(array(
    'organizationId' => $organizationId,
));

$memberCards = [];
$adminCount = 0;
$connectedCount = 0;

$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_CH', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;

$formatDate = static function ($value) use ($formatter): string {
    if (!$value instanceof DateTimeInterface) {
        return '';
    }

    if ($formatter instanceof IntlDateFormatter) {
        $formatted = $formatter->format($value);
        if (is_string($formatted) && $formatted !== '') {
            return $formatted;
        }
    }

    return $value->format('d.m.Y');
};

$formatLastSeenLabel = static function ($organizationDate, $globalDate) use ($formatDate): string {
    $organizationLabel = $organizationDate instanceof DateTimeInterface ? $formatDate($organizationDate) : '';
    $globalLabel = $globalDate instanceof DateTimeInterface ? $formatDate($globalDate) : '';

    if ($organizationLabel !== '') {
        if ($globalLabel !== '') {
            return $organizationLabel . ' (générale : ' . $globalLabel . ')';
        }

        return $organizationLabel;
    }

    return $globalLabel;
};

foreach ($rawMemberCards as $rawCard) {
    $userId = (int)($rawCard['userId'] ?? 0);
    if ($userId <= 0) {
        continue;
    }

    $membership = new UserOrganization();
    $hasMembership = $membership->load(array(
        array('IDuser', $userId),
        array('IDorganization', $organizationId),
    ));

    $user = new User();
    $hasUser = $user->load($userId);

    $isPending = !empty($rawCard['isPending']) || ($hasMembership && !(bool)$membership->get('active'));
    $isAdmin = $hasMembership ? $membership->isOrganizationAdmin() : false;
    $organizationLastSeen = $hasMembership ? $membership->get('dateconnexion') : null;
    $organizationJoinedAt = $hasMembership ? $membership->get('datecreation') : null;
    $globalLastSeen = $hasMembership
        ? $membership->getGlobalLastConnectionAt()
        : ($hasUser && $user->get('dateconnexion') instanceof DateTimeInterface ? $user->get('dateconnexion') : null);
    $globalJoinedAt = $hasMembership
        ? $membership->getGlobalCreatedAt()
        : ($hasUser && $user->get('datecreation') instanceof DateTimeInterface ? $user->get('datecreation') : null);
    $effectiveLastSeen = $organizationLastSeen instanceof DateTimeInterface ? $organizationLastSeen : $globalLastSeen;

    if ($isAdmin && !$isPending) {
        $adminCount += 1;
    }

    if (!$isPending && $effectiveLastSeen instanceof DateTimeInterface) {
        $connectedCount += 1;
    }

    $effectiveJoinedAt = $organizationJoinedAt instanceof DateTimeInterface ? $organizationJoinedAt : $globalJoinedAt;
    $displayName = trim((string)($rawCard['displayName'] ?? ''));
    if ($displayName === '' && $hasMembership) {
        $displayName = $membership->getUserDisplayName();
    }
    if ($displayName === '' && $hasUser) {
        $displayName = $user->getScopedDisplayName($organizationId);
    }

    $email = $hasMembership ? $membership->getScopedEmail() : ($hasUser ? $user->getScopedEmail($organizationId) : '');
    $username = $hasMembership ? $membership->getScopedUsername() : ($hasUser ? $user->getScopedUsername($organizationId) : '');
    $secondary = $email !== ''
        ? $email
        : ($hasMembership
            ? $membership->getUserSecondaryLabel()
            : ($username !== '' ? '@' . $username : ''));

    $photoUrl = trim((string)($rawCard['photoUrl'] ?? ''));
    if ($photoUrl === '' && $hasMembership) {
        $photoUrl = $membership->getProfilePhotoUrl();
    }
    if ($photoUrl === '' && $hasUser) {
        $photoUrl = $user->getScopedProfilePhotoUrl($organizationId);
    }

    $initials = trim((string)($rawCard['initials'] ?? ''));
    if ($initials === '' && $hasMembership) {
        $initials = $membership->getUserInitials();
    }
    if ($initials === '' && $displayName !== '') {
        $words = preg_split('/\s+/u', $displayName) ?: array();
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
    }

    $memberCards[] = array(
        'displayName' => $displayName !== '' ? $displayName : ('Utilisateur ' . $userId),
        'email' => $email,
        'username' => $username,
        'secondary' => $secondary,
        'photoUrl' => $photoUrl,
        'initials' => $initials !== '' ? mb_strtoupper($initials, 'UTF-8') : 'P',
        'isAdmin' => $isAdmin,
        'isPending' => $isPending,
        'joinedAtLabel' => $effectiveJoinedAt instanceof DateTimeInterface ? $formatDate($effectiveJoinedAt) : '',
        'lastSeenLabel' => $formatLastSeenLabel($organizationLastSeen, $globalLastSeen),
    );
}

usort($memberCards, static function (array $left, array $right): int {
    if ($left['isAdmin'] !== $right['isAdmin']) {
        return $left['isAdmin'] ? -1 : 1;
    }

    return strcmp(
        omoApiSortKey($left['displayName']),
        omoApiSortKey($right['displayName'])
    );
});

$currentHolonTypeLabel = omoTeamHolonTypeLabel($currentHolon);
$currentHolonName = trim((string)$currentHolon->getDisplayName());
?>
<div class="omo-team omo-panel-view">
    <div class="omo-team__hero omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title">Team</h2>
            <p class="omo-panel-view__description">Les personnes actuellement associées au <?= omoApiEscape($currentHolonTypeLabel) ?> <?= omoApiEscape($currentHolonName) ?>.</p>
        </div>
        <div class="omo-team__stats" aria-label="Résumé de l'équipe">
            <div class="omo-team__stat">
                <strong><?= omoApiEscape(count($memberCards)) ?></strong>
                <span>Membre<?= count($memberCards) > 1 ? 's' : '' ?></span>
            </div>
            <div class="omo-team__stat">
                <strong><?= omoApiEscape($adminCount) ?></strong>
                <span>Admin<?= $adminCount > 1 ? 's' : '' ?></span>
            </div>
            <div class="omo-team__stat">
                <strong><?= omoApiEscape($connectedCount) ?></strong>
                <span>Déjà connecté<?= $connectedCount > 1 ? 's' : '' ?></span>
            </div>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content">
        <?php if (count($memberCards) === 0): ?>
            <div class="omo-team__empty omo-empty-state">
                Aucune personne n'est encore liée à ce <?= omoApiEscape($currentHolonTypeLabel) ?>.
            </div>
        <?php else: ?>
            <div class="omo-team__grid omo-card-grid omo-card-grid--fixed">
                <?php foreach ($memberCards as $card): ?>
                    <article class="omo-team-card omo-card omo-card--interactive<?= $card['isPending'] ? ' omo-team-card--pending' : '' ?>">
                        <div class="omo-team-card__banner"></div>
                        <div class="omo-team-card__media">
                            <?php if ($card['photoUrl'] !== ''): ?>
                                <img
                                    src="<?= omoApiEscape($card['photoUrl']) ?>"
                                    alt="<?= omoApiEscape($card['displayName']) ?>"
                                    class="omo-team-card__photo"
                                >
                            <?php else: ?>
                                <div class="omo-team-card__photo-placeholder">
                                    <span class="omo-team-card__initials"><?= omoApiEscape($card['initials']) ?></span>
                                    <span class="omo-team-card__photo-label">Photo à venir</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="omo-team-card__body">
                            <div class="omo-team-card__head">
                                <div class="omo-team-card__identity">
                                    <h3><?= omoApiEscape($card['displayName']) ?></h3>
                                    <?php if ($card['secondary'] !== ''): ?>
                                        <p><?= omoApiEscape($card['secondary']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($card['isPending']): ?>
                                    <span class="omo-team-card__badge omo-team-card__badge--pending">En attente</span>
                                <?php elseif ($card['isAdmin']): ?>
                                    <span class="omo-team-card__badge">Admin</span>
                                <?php endif; ?>
                            </div>

                            <div class="omo-team-card__meta">
                                <div class="omo-team-card__meta-row">
                                    <span class="omo-team-card__meta-label">E-mail</span>
                                    <span class="omo-team-card__meta-value<?= $card['email'] === '' ? ' omo-team-card__meta-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['email'] !== '' ? $card['email'] : 'Non renseigné') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="omo-team-card__dates">
                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label">Ajout</span>
                                    <span class="omo-team-card__date-value<?= $card['joinedAtLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['joinedAtLabel'] !== '' ? $card['joinedAtLabel'] : 'N/A') ?>
                                    </span>
                                </div>

                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label">Connexion</span>
                                    <span class="omo-team-card__date-value<?= $card['lastSeenLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoApiEscape($card['lastSeenLabel'] !== '' ? $card['lastSeenLabel'] : 'Jamais') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<style>
.omo-team__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(90px, 1fr));
    gap: 10px;
    min-width: min(100%, 320px);
}

.omo-team__stat {
    display: grid;
    gap: 4px;
    padding: 12px 14px;
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
    text-align: center;
}

.omo-team__stat strong {
    font-size: 1.25rem;
    line-height: 1;
}

.omo-team__stat span {
    color: var(--color-text-light);
    font-size: 0.9rem;
}

.omo-team__grid {
    --omo-card-min: 220px;
    --omo-card-max: 240px;
    gap: 12px;
}

.omo-team-card {
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
    padding: 0;
}

.omo-team-card--pending {
    opacity: 0.7;
}

.omo-team-card__banner {
    height: 34px;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--color-primary) 30%, transparent), transparent 52%),
        linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 22%, var(--color-surface-alt)), var(--color-surface-alt));
    border-bottom: 1px solid var(--color-border);
}

.omo-team-card__media {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-height: 0;
    padding: 0 14px;
    margin-top: -24px;
    margin-bottom: -2px;
    position: relative;
    z-index: 1;
}

.omo-team-card__photo,
.omo-team-card__photo-placeholder {
    width: 52px;
    height: 52px;
    border-radius: 999px;
    border: 2px solid var(--color-surface);
    background: var(--color-surface);
    box-shadow: var(--shadow-sm);
}

.omo-team-card__photo {
    object-fit: cover;
}

.omo-team-card--pending .omo-team-card__photo {
    filter: grayscale(1);
}

.omo-team-card__photo-placeholder {
    display: grid;
    place-items: center;
    gap: 2px;
    padding: 6px;
    text-align: center;
}

.omo-team-card__initials {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 999px;
    background: var(--color-primary);
    color: var(--color-text-inverse);
    font-size: 0.72rem;
    font-weight: 700;
}

.omo-team-card__photo-label {
    font-size: 0.52rem;
    line-height: 1.1;
    color: var(--color-text-light);
}

.omo-team-card__body {
    display: grid;
    gap: 10px;
    padding: 8px 14px 12px;
}

.omo-team-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}

.omo-team-card__identity {
    min-width: 0;
}

.omo-team-card__identity h3 {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.2;
}

.omo-team-card__identity p {
    margin: 2px 0 0;
    color: var(--color-text-light);
    font-size: 0.76rem;
    line-height: 1.25;
    word-break: break-word;
}

.omo-team-card__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 22px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary) 12%, var(--color-surface));
    color: var(--color-primary);
    font-size: 0.7rem;
    font-weight: 700;
    white-space: nowrap;
}

.omo-team-card__badge--pending {
    background: rgba(100, 116, 139, 0.12);
    color: #475569;
}

.omo-team-card__meta {
    display: block;
}

.omo-team-card__meta-row {
    display: grid;
    gap: 1px;
}

.omo-team-card__meta-label {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.omo-team-card__meta-value {
    word-break: break-word;
    font-size: 0.8rem;
}

.omo-team-card__meta-value--muted {
    color: var(--color-text-light);
}

.omo-team-card__dates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    padding-top: 8px;
    border-top: 1px solid var(--color-border);
}

.omo-team-card__date {
    display: grid;
    gap: 1px;
    min-width: 0;
}

.omo-team-card__date-label {
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--color-text-light);
}

.omo-team-card__date-value {
    font-size: 0.68rem;
    line-height: 1.2;
    word-break: break-word;
}

.omo-team-card__date-value--muted {
    color: var(--color-text-light);
}

@media (max-width: 820px) {
    .omo-team__stats {
        width: 100%;
        min-width: 0;
    }
}

@media (max-width: 560px) {
    .omo-team__stats {
        grid-template-columns: 1fr;
    }

    .omo-team__grid {
        grid-template-columns: 1fr;
    }
}
</style>
