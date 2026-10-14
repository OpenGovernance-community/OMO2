<?php
require_once dirname(__DIR__) . '/projects/shared.php';

function omoScrumSourceLang(): array {
    $texts = [
        'title' => 'Scrum', 'more' => 'Autres options', 'close' => 'Fermer', 'next' => 'Suivant : ajuster les tailles', 'previous' => 'Retour a la selection', 'deadline' => 'Fin prevue', 'actual' => 'Charge restante', 'no_scope' => 'Ajoutez des projets pour afficher la progression.', 'restart_confirm' => 'Redemarrer maintenant avec une nouvelle base de progression ? Les anciennes mesures seront remplacees.', 'new' => 'Nouveau sprint', 'archives' => 'Archives', 'back' => 'Retour aux sprints',
        'empty' => 'Aucun sprint dans cet espace.', 'save_action' => 'Enregistrer', 'cancel' => 'Annuler',
        'edit' => 'Modifier', 'add' => 'Ajouter des projets', 'import' => 'Importer toute la selection',
        'search' => 'Rechercher un projet', 'selection' => 'Projets et sous-projets inclus',
        'import_hint' => '1. Selectionnez les projets dans les cercles et roles. Leurs sous-projets seront inclus automatiquement.',
        'small_parent' => 'Estimation inferieure aux sous-projets : vous pouvez l adapter ; leur charge totale sera retenue.',
        'load' => 'Charge', 'remaining' => 'Restant', 'points' => 'points', 'point' => 'point', 'ideal' => 'Trajectoire ideale',
        'stop' => 'Revoir la preparation', 'resume' => 'Demarrer maintenant', 'archive' => 'Archiver', 'restore' => 'Restaurer', 'delete' => 'Supprimer le sprint',
        'remove' => 'Retirer cet ensemble', 'team' => 'Equipe', 'no_team' => 'Aucun responsable attribue',
        'scheduled' => 'En preparation', 'stopped' => 'En preparation', 'running' => 'En cours', 'finished' => 'Termine',
        'stop_confirm' => 'Arreter le sprint pour revoir sa preparation ? La relance remplacera les anciennes mesures par une nouvelle base.',
        'delete_confirm' => 'Supprimer ce sprint et son historique ? Les projets seront conserves.',
        'restart_hint' => 'Ajoutez les projets, ajustez leurs tailles, puis cliquez sur Demarrer maintenant quand le sprint est pret.',
        'frozen' => 'Bilan fige a la fin du sprint. Les projets continuent d evoluer dans l application Projets.',
        'estimate_hint' => '2. Definissez ou ajustez la taille de chaque projet et sous-projet. Les tailles sont partagees avec l application Projets ; la charge des enfants n est pas comptee deux fois.',
        'adjust_hint' => 'Ajustez les tailles ou retirez un projet racine avec ses sous-projets du sprint. Les projets restent dans l application Projets. Les changements sont appliques en cliquant sur Enregistrer.',
        'saved' => 'Sprint mis a jour.', 'loading' => 'Chargement...', 'access' => 'Acces non autorise.',
        'missing' => 'Sprint ou projet introuvable.', 'save' => 'Impossible d enregistrer les modifications.',
        'dates' => 'Indiquez un titre et une date de fin valide, aujourd hui ou plus tard.',
        'locked' => 'Le sprint a demarre. Utilisez Revoir la preparation dans le menu pour modifier ses projets.',
        'conflict' => 'Un projet de cette selection appartient deja a un sprint non termine.',
        'estimate' => 'Chaque projet et sous-projet doit avoir une estimation.', 'busy' => 'Une modification est en cours. Reessayez.',
        'partial' => 'Retirez le projet racine avec tous ses descendants.', 'tree' => 'La hierarchie des projets contient une boucle.',
        'children_not_done' => 'Terminez tous les sous-projets avant de terminer leur parent.',
        'sprint_tree_locked' => 'La hierarchie est figee pendant le sprint. Arretez le sprint avant de la modifier.',
        'sprint_someday' => 'Un projet importe dans un sprint ne peut pas retourner dans Un jour peut-etre avant la fin.',
        'select' => 'Selectionner', 'refresh' => 'Actualiser', 'own' => 'Charge propre',
        'history' => 'Mesures du burndown', 'measured_at' => 'Date et heure (Europe/Zurich)',
        'snapshot_help' => 'La charge au demarrage reste la reference de 100 %. Les tailles peuvent etre reevaluees dans Projets et faire depasser 100 % ; la hierarchie reste figee.',
    ];
    $sourceLang = [];
    foreach ($texts as $key => $text) { $sourceLang[$key] = ['text' => $text, 'context' => 'Scrum sprint management: ' . $key]; }
    return $sourceLang;
}
function omoScrumT(string $key): string {
    static $bundle;
    $sourceLang = omoScrumSourceLang();
    $bundle ??= omoLoadTranslationBundle('omo_scrum', $sourceLang);
    return t($key, [], $bundle, $sourceLang);
}
function omoScrumContext(): array {
    $oid = (int)($_SESSION['currentOrganization'] ?? 0);
    $context = omoProjectsResolveContext($oid, (int)($_REQUEST['cid'] ?? 0), false);
    if (empty($context['status']) || !$context['currentHolon'] || !$context['organization']->isApplicationEnabled('scrum') || !\dbObject\ScrumSprint::available()) {
        throw new RuntimeException('access');
    }
    return $context;
}
function omoScrumLoad(array $context, int $id): \dbObject\ScrumSprint {
    $sprint = new \dbObject\ScrumSprint();
    if (!$sprint->load($id, true) || (int)$sprint->get('IDorganization') !== (int)$context['organization']->getId()
        || (int)$sprint->get('IDholon') !== (int)$context['currentHolon']->getId()) { throw new RuntimeException('missing'); }
    return $sprint;
}
function omoScrumUrl(array $context, array $params = [], string $page = 'index.php'): string {
    return '/omo/api/scrum/' . $page . '?' . http_build_query(array_merge([
        'oid' => (int)$context['organization']->getId(), 'cid' => (int)$context['currentHolon']->getId(),
    ], $params));
}
function omoScrumPercent(int $remaining, int $baseline): string {
    return $baseline > 0 ? (string)round(100 * $remaining / $baseline) . ' %' : '-';
}
function omoScrumDates(\dbObject\ScrumSprint $sprint): string {
    $start = $sprint->get('started_at');
    return ($start ? $start->format('d.m.Y') . ' - ' : omoScrumT('deadline') . ' : ') . $sprint->get('end_date')->format('d.m.Y');
}
function omoScrumChart(\dbObject\ScrumSprint $sprint, ?array $samples = null): string {
    $baseline = (int)$sprint->get('baseline');
    if ($baseline <= 0) { return '<p class="generic-content-state generic-description">' . omoApiEscape(omoScrumT('no_scope')) . '</p>'; }
    $samples = $sprint->editable() ? [] : ($samples ?? \dbObject\ScrumSample::forSprint((int)$sprint->getId()));
    $start = $sprint->editable() ? \dbObject\ScrumSprint::now() : ($sprint->get('started_at') ?: \dbObject\ScrumSprint::now());
    $start = new DateTimeImmutable($start->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Zurich'));
    $from = $start->getTimestamp(); $to = max($from + 1, $sprint->endAt()->getTimestamp());
    $x = static fn (int $timestamp): float => round(52 + 480 * max(0, min(1, ($timestamp - $from) / ($to - $from))), 2);
    $peak = max(array_merge([$baseline], array_column($samples, 'remaining')));
    $axisMax = (int)(100 * ceil($peak / $baseline));
    $y = static fn (int $points): float => round(190 - 160 * max(0, 100 * $points / $baseline) / $axisMax, 2);
    $path = ''; $lastY = null; $markers = ''; $grid = ''; $dates = '';
    foreach ([0, $axisMax / 4, $axisMax / 2, 3 * $axisMax / 4, $axisMax] as $percent) {
        $py = 190 - 160 * $percent / $axisMax;
        $grid .= '<path d="M52 ' . $py . ' H532" class="scrum-chart__grid"/><text x="40" y="' . ($py + 4) . '" text-anchor="end">' . $percent . ' %</text>';
    }
    for ($i = 0; $i <= 4; $i++) {
        $timestamp = (int)round($from + ($to - $from) * $i / 4);
        $date = $start->setTimestamp($timestamp);
        $dates .= '<text x="' . $x($timestamp) . '" y="218" text-anchor="' . ($i === 0 ? 'start' : ($i === 4 ? 'end' : 'middle')) . '">' . $date->format($to - $from < 86400 ? 'H:i' : 'd.m') . '</text>';
    }
    foreach ($samples as $sample) {
        $px = $x((new DateTimeImmutable($sample['sampled_at'], new DateTimeZone('Europe/Zurich')))->getTimestamp());
        $py = $y((int)$sample['remaining']);
        $path .= $lastY === null ? "M $px $py " : "H $px V $py "; $lastY = $py;
        $markers .= '<circle cx="' . $px . '" cy="' . $py . '" r="3" class="scrum-chart__sample"><title>'
            . omoApiEscape($sample['sampled_at'] . ' : ' . omoScrumPercent((int)$sample['remaining'], $baseline)) . '</title></circle>';
    }
    $endX = $x(min(\dbObject\ScrumSprint::now()->getTimestamp(), $to));
    if ($lastY !== null) { $path .= 'H ' . $endX; }
    $area = $lastY !== null ? '<path d="' . $path . ' V190 H' . $x((new DateTimeImmutable($samples[0]['sampled_at'], new DateTimeZone('Europe/Zurich')))->getTimestamp()) . ' Z" class="scrum-chart__area"/>' : '';
    $idealStart = $samples ? (int)$samples[0]['remaining'] : $baseline;
    return '<figure class="scrum-chart"><figcaption class="scrum-chart__legend generic-meta"><span class="scrum-chart__actual">' . omoApiEscape(omoScrumT('actual')) . '</span><span class="scrum-chart__ideal">' . omoApiEscape(omoScrumT('ideal')) . '</span></figcaption>'
        . '<svg viewBox="0 0 560 234" role="img" aria-label="' . omoApiEscape(omoScrumT('remaining')) . '"><g class="scrum-chart__axes">' . $grid . $dates . '</g>'
        . $area . '<path d="M52 ' . $y($idealStart) . ' L532 190" class="scrum-chart__target"/>'
        . '<path d="' . $path . '" class="scrum-chart__line"/>' . $markers
        . ($lastY !== null ? '<circle cx="' . $endX . '" cy="' . $lastY . '" r="7" class="scrum-chart__endpoint' . ($sprint->get('state') === 'running' ? ' scrum-chart__endpoint--running' : '') . '"/>' : '') . '</svg></figure>';
}
