<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$currentUserId = (int)($_SESSION['currentUser'] ?? commonGetCurrentUserId());

echo $currentUserId;

$documents = new \dbObject\ArrayDocument();
$documents->load([
    'where' => [
        ['field' => 'IDuser', 'value' => $currentUserId],
    ],
    'orderBy' => [
        ['field' => 'datecreation', 'dir' => 'DESC'],
    ],
]);

$today = new DateTimeImmutable('today');
$lastMonth = $today->modify('-1 month');
$dayOfWeek = (int)$today->format('N');
$dayOfMonth = (int)$today->format('d');

$groups = [
    ['duration' => 0, 'label' => "Aujourd'hui"],
    ['duration' => 1, 'label' => 'Hier'],
    ['duration' => min($dayOfWeek, 2), 'label' => 'Cette semaine'],
    ['duration' => max(2, $dayOfWeek), 'label' => 'La semaine passee'],
    ['duration' => max($dayOfWeek + 1, $dayOfWeek + 7), 'label' => 'Ce mois'],
    ['duration' => max($dayOfMonth, $dayOfWeek + 8), 'label' => 'Le mois passe'],
    [
        'duration' => $dayOfMonth + cal_days_in_month(
            CAL_GREGORIAN,
            (int)$lastMonth->format('m'),
            (int)$lastMonth->format('Y')
        ),
        'label' => 'Cette annee',
    ],
    ['duration' => 730, 'label' => "L'annee passee"],
    ['duration' => 1100, 'label' => 'Precedemment'],
    ['duration' => 9999, 'label' => 'Trop loin'],
];

$escape = static function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$getGroupIndex = static function (int $interval) use ($groups): int {
    $selectedIndex = 0;

    foreach ($groups as $index => $group) {
        if ($interval >= $group['duration']) {
            $selectedIndex = $index;
            continue;
        }

        break;
    }

    return $selectedIndex;
};

$formatter = class_exists('IntlDateFormatter')
    ? new IntlDateFormatter('fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE)
    : null;

if ($formatter instanceof IntlDateFormatter) {
    $formatter->setPattern('d MMM');
}

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
?>
<div class="omo-documents">
    <div class="omo-documents__header">
        <div>
            <h2>Documents</h2>
            <p>Vos documents EasyMEMO dans OMO.</p>
        </div>
        <div class="omo-documents__count"><?= $escape(count($documents)) ?> document(s)</div>
    </div>

    <?php if (count($documents) === 0): ?>
        <div class="omo-documents__empty">
            Aucun document disponible pour ce compte.
        </div>
    <?php else: ?>
        <?php
        $currentGroupIndex = null;

        foreach ($documents as $document):
            $createdAt = $document->get('datecreation');
            $interval = 9999;

            if ($createdAt instanceof DateTimeInterface) {
                $documentDate = DateTimeImmutable::createFromInterface($createdAt)->setTime(0, 0);
                $interval = (int)$documentDate->diff($today)->format('%a');
            }

            $groupIndex = $getGroupIndex($interval);

            if ($groupIndex !== $currentGroupIndex):
                if ($currentGroupIndex !== null):
        ?>
                </div>
            </section>
        <?php
                endif;
                $currentGroupIndex = $groupIndex;
        ?>
            <section class="omo-documents__group">
                <h3><?= $escape($groups[$groupIndex]['label']) ?></h3>
                <div class="omo-documents__list">
        <?php endif; ?>
                    <a
                        class="omo-documents__item"
                        href="/memo/<?= $escape($document->getId()) ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        <div class="omo-documents__item-head">
                            <span class="omo-documents__date"><?= $escape($formatDate($createdAt)) ?></span>
                            <strong><?= $escape($document->get('title')) ?></strong>
                        </div>

                        <?php if ((string)$document->get('description') !== ''): ?>
                            <p><?= $escape($document->get('description')) ?></p>
                        <?php endif; ?>

                        <?php if ((string)$document->get('keywords') !== ''): ?>
                            <div class="omo-documents__keywords"><?= $escape($document->get('keywords')) ?></div>
                        <?php endif; ?>
                    </a>
        <?php endforeach; ?>
                </div>
            </section>
    <?php endif; ?>
</div>

<style>
.omo-documents {
    display: flex;
    flex-direction: column;
    gap: 18px;
    min-height: 100%;
    padding: 20px;
    background: var(--color-bg);
    color: var(--color-text);
}

.omo-documents__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    box-shadow: var(--shadow-sm);
}

.omo-documents__header h2 {
    margin: 0 0 6px;
    font-size: 1.5rem;
}

.omo-documents__header p {
    margin: 0;
    color: var(--color-text-light);
}

.omo-documents__count {
    padding: 8px 12px;
    border-radius: 999px;
    background: var(--color-surface-alt);
    font-size: 0.95rem;
    white-space: nowrap;
}

.omo-documents__group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.omo-documents__group h3 {
    margin: 0;
    font-size: 1rem;
    color: var(--color-text-light);
}

.omo-documents__list {
    display: grid;
    gap: 12px;
}

.omo-documents__item {
    display: block;
    padding: 16px 18px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    color: inherit;
    text-decoration: none;
    box-shadow: var(--shadow-sm);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.omo-documents__item:hover {
    transform: translateY(-2px);
    border-color: var(--color-primary);
    box-shadow: var(--shadow-md);
}

.omo-documents__item-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.omo-documents__date {
    color: var(--color-text-light);
    font-size: 0.92rem;
}

.omo-documents__item p {
    margin: 0 0 10px;
    color: var(--color-text-light);
}

.omo-documents__keywords {
    font-size: 0.92rem;
    color: var(--color-primary);
}

.omo-documents__empty {
    padding: 28px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    color: var(--color-text-light);
    text-align: center;
}

@media (max-width: 720px) {
    .omo-documents {
        padding: 14px;
    }

    .omo-documents__header {
        flex-direction: column;
        align-items: stretch;
    }

    .omo-documents__count {
        align-self: flex-start;
    }
}
</style>
