<?php
require_once dirname(__DIR__) . '/projects/shared.php';

function omoScrumSourceLang(): array {
    $texts = [
        'title' => 'Scrum', 'new' => 'Nouveau sprint', 'archives' => 'Archives', 'back' => 'Retour aux sprints',
        'empty' => 'Aucun sprint dans cet espace.', 'save_action' => 'Enregistrer', 'cancel' => 'Annuler',
        'edit' => 'Modifier', 'add' => 'Ajouter des projets', 'import' => 'Importer toute la selection',
        'search' => 'Rechercher un projet', 'selection' => 'Projets et sous-projets inclus',
        'import_hint' => 'Tous les descendants sont inclus. Les projets Un jour peut-etre passeront dans Pret dans les deux applications. Les estimations modifiees sont aussi partagees.',
        'small_parent' => 'Estimation inferieure aux sous-projets : vous pouvez l adapter ; leur charge totale sera retenue.',
        'load' => 'Charge', 'remaining' => 'Restant', 'points' => 'points', 'point' => 'point', 'ideal' => 'Trajectoire ideale',
        'stop' => 'Stop / revoir la preparation', 'resume' => 'Relancer', 'archive' => 'Archiver', 'restore' => 'Restaurer', 'delete' => 'Supprimer le sprint',
        'remove' => 'Retirer cet ensemble', 'team' => 'Equipe', 'no_team' => 'Aucun responsable attribue',
        'scheduled' => 'Planifie', 'stopped' => 'Preparation arretee', 'running' => 'En cours', 'finished' => 'Termine',
        'stop_confirm' => 'Arreter le sprint pour revoir sa preparation ? La relance remplacera les anciennes mesures par une nouvelle base.',
        'delete_confirm' => 'Supprimer ce sprint et son historique ? Les projets seront conserves.',
        'restart_hint' => 'Preparation ouverte. Relancez pour figer la charge et demarrer (ou programmer le demarrage a la date prevue).',
        'frozen' => 'Bilan fige a la fin du sprint. Les projets continuent d evoluer dans l application Projets.',
        'estimate_hint' => 'S = 1, M = 4, L = 16, XL = 64, XXL = 256. La charge propre du parent est la difference positive avec ses descendants.',
        'saved' => 'Sprint mis a jour.', 'loading' => 'Chargement...', 'access' => 'Acces non autorise.',
        'missing' => 'Sprint ou projet introuvable.', 'save' => 'Impossible d enregistrer les modifications.',
        'dates' => 'Indiquez un titre et des dates valides, avec une fin egale ou posterieure au debut et non depassee.',
        'locked' => 'Le sprint a demarre. Utilisez Stop pour revoir sa preparation.',
        'conflict' => 'Un projet de cette selection appartient deja a un sprint non termine.',
        'estimate' => 'Chaque projet et sous-projet doit avoir une estimation.', 'busy' => 'Une modification est en cours. Reessayez.',
        'partial' => 'Retirez le projet racine avec tous ses descendants.', 'tree' => 'La hierarchie des projets contient une boucle.',
        'children_not_done' => 'Terminez tous les sous-projets avant de terminer leur parent.',
        'sprint_tree_locked' => 'La hierarchie est figee pendant le sprint. Arretez le sprint avant de la modifier.',
        'sprint_someday' => 'Un projet importe dans un sprint ne peut pas retourner dans Un jour peut-etre avant la fin.',
        'select' => 'Selectionner', 'refresh' => 'Actualiser', 'own' => 'Charge propre',
        'history' => 'Mesures du burndown', 'measured_at' => 'Date et heure (Europe/Zurich)',
        'snapshot_help' => 'Les tailles et la hierarchie sont figees au demarrage. Les statuts restent partages avec Projets.',
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
function omoScrumChart(\dbObject\ScrumSprint $sprint, ?array $samples = null): string {
    $samples ??= \dbObject\ScrumSample::forSprint((int)$sprint->getId());
    $start = $sprint->get('started_at') ?: $sprint->get('start_date');
    // SQL DATETIME has no zone; dbObject may hydrate it in the PHP server zone.
    $start = new DateTimeImmutable($start->format('Y-m-d H:i:s'), new DateTimeZone('Europe/Zurich'));
    $from = $start->getTimestamp(); $to = $sprint->endAt()->getTimestamp();
    $total = max(1, (int)$sprint->get('baseline'));
    $idealStart = $samples ? (int)$samples[0]['remaining'] : (int)$sprint->get('baseline');
    $x = static fn (int $timestamp): float => 40 + 430 * max(0, min(1, ($timestamp - $from) / max(1, $to - $from)));
    $y = static fn (int $points): float => 160 - 135 * $points / $total;
    $path = ''; $lastY = null; $markers = '';
    foreach ($samples as $sample) {
        $px = $x((new DateTimeImmutable($sample['sampled_at'], new DateTimeZone('Europe/Zurich')))->getTimestamp());
        $py = $y((int)$sample['remaining']);
        $path .= $lastY === null ? "M $px $py " : "H $px V $py "; $lastY = $py;
        $markers .= '<circle cx="' . $px . '" cy="' . $py . '" r="3" fill="#159da4"><title>'
            . omoApiEscape($sample['sampled_at'] . ' : ' . $sample['remaining'] . ' ' . omoScrumT('points')) . '</title></circle>';
    }
    if ($lastY !== null) { $path .= 'H ' . $x(min(\dbObject\ScrumSprint::now()->getTimestamp(), $to)); }
    $label = omoApiEscape(omoScrumT('remaining'));
    return '<svg class="scrum-chart" viewBox="0 0 500 205" role="img" aria-label="' . $label . '">'
        . '<path d="M40 20 V160 H475" fill="none" stroke="currentColor" opacity=".3"/>'
        . '<path d="M40 ' . $y($idealStart) . ' L470 160" fill="none" stroke="currentColor" opacity=".4" stroke-dasharray="5 5"/>'
        . '<path d="' . $path . '" fill="none" stroke="#159da4" stroke-width="3"/>' . $markers
        . '<g fill="currentColor" font-size="12"><text x="5" y="30">' . (int)$sprint->get('baseline') . '</text><text x="20" y="164">0</text>'
        . '<text x="40" y="180">' . $start->format('d.m') . '</text><text x="440" y="180">' . $sprint->get('end_date')->format('d.m') . '</text>'
        . '<text x="40" y="200">' . $label . ' / ' . omoApiEscape(omoScrumT('ideal')) . ' - ' . omoApiEscape(omoScrumT('points')) . '</text></g></svg>';
}
