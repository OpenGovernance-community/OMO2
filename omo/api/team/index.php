<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\ArrayUserOrganization;
use dbObject\Organization;

function omoTeamEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function omoTeamSortKey($value)
{
    $value = trim(mb_strtolower((string)$value, 'UTF-8'));
    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if (is_string($transliterated) && $transliterated !== '') {
        $value = $transliterated;
    }

    return preg_replace('/[^a-z0-9]+/', ' ', $value);
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));

if ($organizationId <= 0) {
    http_response_code(400);
    ?>
    <div class="omo-team omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-team__empty omo-empty-state">Organisation invalide.</div>
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
            <div class="omo-team__empty omo-empty-state">Organisation introuvable.</div>
        </div>
    </div>
    <?php
    exit;
}

$memberships = new ArrayUserOrganization();
$memberships->loadActiveForOrganization($organizationId);

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

foreach ($memberships as $membership) {
    $isAdmin = $membership->isOrganizationAdmin();
    $organizationLastSeen = $membership->get('dateconnexion');
    $organizationJoinedAt = $membership->get('datecreation');
    $globalLastSeen = $membership->getGlobalLastConnectionAt();
    $globalJoinedAt = $membership->getGlobalCreatedAt();
    $effectiveLastSeen = $organizationLastSeen instanceof DateTimeInterface ? $organizationLastSeen : $globalLastSeen;

    if ($isAdmin) {
        $adminCount += 1;
    }

    if ($effectiveLastSeen instanceof DateTimeInterface) {
        $connectedCount += 1;
    }

    $effectiveJoinedAt = $organizationJoinedAt instanceof DateTimeInterface ? $organizationJoinedAt : $globalJoinedAt;
    $displayName = $membership->getUserDisplayName();

    $memberCards[] = [
        'displayName' => $displayName,
        'email' => $membership->getScopedEmail(),
        'username' => $membership->getScopedUsername(),
        'secondary' => $membership->getScopedEmail() !== '' ? $membership->getScopedEmail() : $membership->getUserSecondaryLabel(),
        'photoUrl' => $membership->getProfilePhotoUrl(),
        'initials' => $membership->getUserInitials(),
        'isAdmin' => $isAdmin,
        'joinedAtLabel' => $effectiveJoinedAt instanceof DateTimeInterface ? $formatDate($effectiveJoinedAt) : '',
        'lastSeenLabel' => $formatLastSeenLabel($organizationLastSeen, $globalLastSeen),
    ];
}

usort($memberCards, static function (array $left, array $right): int {
    if ($left['isAdmin'] !== $right['isAdmin']) {
        return $left['isAdmin'] ? -1 : 1;
    }

    return strcmp(
        omoTeamSortKey($left['displayName']),
        omoTeamSortKey($right['displayName'])
    );
});
?>
<div class="omo-team omo-panel-view">
    <div class="omo-team__hero omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title">Team</h2>
            <p class="omo-panel-view__description">Les personnes actuellement associées à <?= omoTeamEscape($organization->get('name')) ?>.</p>
        </div>
        <div class="omo-team__stats" aria-label="Résumé de l’équipe">
            <div class="omo-team__stat">
                <strong><?= omoTeamEscape(count($memberCards)) ?></strong>
                <span>Membre<?= count($memberCards) > 1 ? 's' : '' ?></span>
            </div>
            <div class="omo-team__stat">
                <strong><?= omoTeamEscape($adminCount) ?></strong>
                <span>Admin<?= $adminCount > 1 ? 's' : '' ?></span>
            </div>
            <div class="omo-team__stat">
                <strong><?= omoTeamEscape($connectedCount) ?></strong>
                <span>Déjà connecté<?= $connectedCount > 1 ? 's' : '' ?></span>
            </div>
        </div>
    </div>
    <div class="omo-panel-view__body">
        <?php if (count($memberCards) === 0): ?>
            <div class="omo-team__empty omo-empty-state">
                Aucune personne n’est encore liée à cette organisation.
            </div>
        <?php else: ?>
            <div class="omo-team__grid omo-card-grid omo-card-grid--fixed">
                <?php foreach ($memberCards as $card): ?>
                    <article class="omo-team-card omo-card omo-card--interactive">
                        <div class="omo-team-card__banner"></div>
                        <div class="omo-team-card__media">
                            <?php if ($card['photoUrl'] !== ''): ?>
                                <img
                                    src="<?= omoTeamEscape($card['photoUrl']) ?>"
                                    alt="<?= omoTeamEscape($card['displayName']) ?>"
                                    class="omo-team-card__photo"
                                >
                            <?php else: ?>
                                <div class="omo-team-card__photo-placeholder">
                                    <span class="omo-team-card__initials"><?= omoTeamEscape($card['initials']) ?></span>
                                    <span class="omo-team-card__photo-label">Photo à venir</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="omo-team-card__body">
                            <div class="omo-team-card__head">
                                <div class="omo-team-card__identity">
                                    <h3><?= omoTeamEscape($card['displayName']) ?></h3>
                                    <?php if ($card['secondary'] !== ''): ?>
                                        <p><?= omoTeamEscape($card['secondary']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($card['isAdmin']): ?>
                                    <span class="omo-team-card__badge">Admin</span>
                                <?php endif; ?>
                            </div>

                            <div class="omo-team-card__meta">
                                <div class="omo-team-card__meta-row">
                                    <span class="omo-team-card__meta-label">E-mail</span>
                                    <span class="omo-team-card__meta-value<?= $card['email'] === '' ? ' omo-team-card__meta-value--muted' : '' ?>">
                                        <?= omoTeamEscape($card['email'] !== '' ? $card['email'] : 'Non renseigné') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="omo-team-card__dates">
                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label">Ajout</span>
                                    <span class="omo-team-card__date-value<?= $card['joinedAtLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoTeamEscape($card['joinedAtLabel'] !== '' ? $card['joinedAtLabel'] : 'N/A') ?>
                                    </span>
                                </div>

                                <div class="omo-team-card__date">
                                    <span class="omo-team-card__date-label">Connexion</span>
                                    <span class="omo-team-card__date-value<?= $card['lastSeenLabel'] === '' ? ' omo-team-card__date-value--muted' : '' ?>">
                                        <?= omoTeamEscape($card['lastSeenLabel'] !== '' ? $card['lastSeenLabel'] : 'Jamais') ?>
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
