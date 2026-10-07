<?php
require_once dirname(__DIR__, 2) . '/shared/date_groups.php';

function omoResourceArchivesT(string $key, array $replace = []): string
{
    static $source = null;
    static $bundle = null;
    $source ??= [
        'archives.open' => ['text' => 'Voir les archives', 'context' => 'Application menu action opening archived resources.'],
        'archives.date' => ['text' => "Archiv\u{00E9} le {date}", 'context' => 'Resource archive date.'],
        'archives.unknown' => ['text' => 'Date inconnue', 'context' => 'Missing archive date.'],
        'archives.today' => ['text' => "Aujourd'hui", 'context' => 'Archives date group.'],
        'archives.yesterday' => ['text' => 'Hier', 'context' => 'Archives date group.'],
        'archives.this_week' => ['text' => 'Cette semaine', 'context' => 'Archives date group.'],
        'archives.last_week' => ['text' => "La semaine pass\u{00E9}e", 'context' => 'Archives date group.'],
        'archives.this_month' => ['text' => 'Ce mois', 'context' => 'Archives date group.'],
        'archives.last_month' => ['text' => "Le mois pass\u{00E9}", 'context' => 'Archives date group.'],
        'archives.this_year' => ['text' => "Cette ann\u{00E9}e", 'context' => 'Archives date group.'],
        'archives.last_year' => ['text' => "L'ann\u{00E9}e pass\u{00E9}e", 'context' => 'Archives date group.'],
        'archives.earlier' => ['text' => 'Plus ancien', 'context' => 'Archives date group.'],
    ];
    $bundle ??= omoLoadTranslationBundle('omo_resource_archives', $source);
    return t($key, $replace, $bundle, $source);
}

/** Items contain id, title, date, url and metadata; dates are sorted newest first. */
function omoRenderResourceArchives(array $items, string $emptyText, ?DateTimeImmutable $today = null): void
{
    $today ??= new DateTimeImmutable('today');
    $labels = [];
    foreach (['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year', 'last_year', 'earlier', 'too_far'] as $key) {
        $labels[$key] = omoResourceArchivesT('archives.' . ($key === 'too_far' ? 'unknown' : $key));
    }
    $groups = sharedGetRelativeDateGroups($today, $labels);
    $grouped = array_fill(0, count($groups), []);
    foreach ($items as $item) {
        $grouped[sharedGetRelativeDateGroupIndexForDate($item['date'], $groups, $today)][] = $item;
    }
    ?>
    <div class="generic-resource-archives" data-topbar-modal-max-width="760px" data-resource-archives>
        <?php foreach ($groups as $index => $group): ?>
            <?php if ($grouped[$index] === []) continue;
            usort($grouped[$index], static function ($left, $right) {
                $dates = ($right['date'] instanceof DateTimeInterface ? $right['date']->getTimestamp() : 0)
                    <=> ($left['date'] instanceof DateTimeInterface ? $left['date']->getTimestamp() : 0);
                return $dates ?: strnatcasecmp($left['title'], $right['title']);
            }); ?>
            <section class="generic-resource-archives__group">
                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape($group['label']) ?></h3>
                <?php foreach ($grouped[$index] as $item): ?>
                    <article class="generic-soft-panel generic-resource-archives__item">
                        <div class="generic-resource-archives__main">
                            <a href="<?= omoApiEscape($item['url']) ?>" data-resource-archive-link data-resource-id="<?= (int)$item['id'] ?>"><?= omoApiEscape($item['title']) ?></a>
                            <span class="generic-description generic-description--small"><?= omoApiEscape($item['date'] instanceof DateTimeInterface ? omoResourceArchivesT('archives.date', ['date' => $item['date']->format('d.m.Y')]) : omoResourceArchivesT('archives.unknown')) ?></span>
                        </div>
                        <div class="generic-meta"><?php foreach ($item['metadata'] as $label): ?><span><?= omoApiEscape($label) ?></span><?php endforeach; ?></div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
        <?php if ($items === []): ?><p class="generic-description"><?= omoApiEscape($emptyText) ?></p><?php endif; ?>
    </div>
    <?php
}
