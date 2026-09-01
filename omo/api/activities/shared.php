<?php
use dbObject\ControlActivity;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\RecurrenceSchedule;

function omoActivitySourceLang()
{
    return [
        'activity.title' => ['text' => 'Activités', 'context' => 'Activity application title.'],
        'activity.description' => ['text' => 'Activités récurrentes à valider, sans créer de projet.', 'context' => 'Activity drawer description.'],
        'activity.new' => ['text' => 'Ajouter une activité', 'context' => 'Create activity action.'],
        'activity.edit' => ['text' => 'Modifier', 'context' => 'Edit activity action.'],
        'activity.delete' => ['text' => 'Supprimer', 'context' => 'Delete activity action.'],
        'activity.check' => ['text' => 'Valider', 'context' => 'Validate activity action.'],
        'activity.done' => ['text' => 'Fait', 'context' => 'Direct completion action in the activity list.'],
        'activity.save' => ['text' => 'Enregistrer', 'context' => 'Save activity action.'],
        'activity.cancel' => ['text' => 'Annuler', 'context' => 'Cancel editing action.'],
        'activity.editor.create_title' => ['text' => 'Nouvelle activité', 'context' => 'Create activity drawer title.'],
        'activity.editor.edit_title' => ['text' => 'Modifier l’activité', 'context' => 'Edit activity drawer title.'],
        'activity.editor.identity' => ['text' => 'L’activité', 'context' => 'Activity identity form section.'],
        'activity.editor.window' => ['text' => 'Fenêtre de réalisation', 'context' => 'Activity execution window form section.'],
        'activity.close' => ['text' => 'Fermer', 'context' => 'Close drawer action.'],
        'activity.back' => ['text' => 'Retour aux activités', 'context' => 'Back action.'],
        'activity.scope.contextual' => ['text' => 'Local', 'context' => 'Current holon scope.'],
        'activity.scope.children' => ['text' => 'Enfants directs', 'context' => 'Children scope.'],
        'activity.scope.descendants' => ['text' => 'Descendants', 'context' => 'Descendant scope.'],
        'activity.empty' => ['text' => 'Aucune activité dans cette portée.', 'context' => 'Empty state.'],
        'activity.search.placeholder' => ['text' => 'Rechercher une activité...', 'context' => 'Activity search placeholder.'],
        'activity.search.aria' => ['text' => 'Rechercher dans les activités', 'context' => 'Activity search accessible label.'],
        'activity.search.empty' => ['text' => 'Aucune activité ne correspond à la recherche.', 'context' => 'Empty search result.'],
        'activity.filters.aria' => ['text' => 'Filtres des activités', 'context' => 'Activity filters accessible label.'],
        'activity.filters.scope' => ['text' => 'Portée', 'context' => 'Scope filter heading.'],
        'activity.filters.state' => ['text' => 'État', 'context' => 'State filter heading.'],
        'activity.filters.apply' => ['text' => 'Appliquer', 'context' => 'Apply filters action.'],
        'activity.filters.save_view' => ['text' => 'Enregistrer cette vue', 'context' => 'Save filters action.'],
        'activity.filter.all' => ['text' => 'Tous les états', 'context' => 'All states filter.'],
        'activity.filter.attention' => ['text' => 'À traiter', 'context' => 'Due and missed states filter.'],
        'activity.filter.missed' => ['text' => 'Non faites', 'context' => 'Missed state filter.'],
        'activity.filter.checked' => ['text' => 'Faites', 'context' => 'Checked state filter.'],
        'activity.filter.upcoming' => ['text' => 'À venir', 'context' => 'Upcoming state filter.'],
        'activity.column.activity' => ['text' => 'Activité', 'context' => 'Activity list title column.'],
        'activity.column.context' => ['text' => 'Holon', 'context' => 'Activity list context column.'],
        'activity.column.next' => ['text' => 'Échéance', 'context' => 'Activity list due date column.'],
        'activity.column.status' => ['text' => 'État', 'context' => 'Activity list state column.'],
        'activity.frequency' => ['text' => 'Récurrence', 'context' => 'Frequency field.'],
        'activity.reference' => ['text' => 'Référence', 'context' => 'Reference field.'],
        'activity.title_field' => ['text' => 'Titre', 'context' => 'Title field.'],
        'activity.description_field' => ['text' => 'Description', 'context' => 'Description field.'],
        'activity.display_lead' => ['text' => 'Afficher en avance', 'context' => 'Advance field.'],
        'activity.overdue_after' => ['text' => 'En retard après', 'context' => 'Delay field.'],
        'activity.unit' => ['text' => 'Unité', 'context' => 'Unit field.'],
        'activity.regularity' => ['text' => 'Régularité des 12 dernières occurrences', 'context' => 'Regularity heading.'],
        'activity.timeline.description' => ['text' => 'Les validations sont placées à leur date réelle. Les absences restent positionnées à la date attendue.', 'context' => 'Timeline explanation.'],
        'activity.timeline.checked' => ['text' => 'Fait dans les temps', 'context' => 'Timeline checked legend.'],
        'activity.timeline.late' => ['text' => 'Fait en retard', 'context' => 'Timeline late legend.'],
        'activity.timeline.missed' => ['text' => 'Occurrence manquée', 'context' => 'Timeline missed legend.'],
        'activity.timeline.due' => ['text' => 'À faire ou bientôt à faire', 'context' => 'Timeline activity waiting for validation, before or after its expected time.'],
        'activity.timeline.upcoming' => ['text' => 'À venir', 'context' => 'Timeline upcoming legend.'],
        'activity.timeline.by' => ['text' => 'par', 'context' => 'Person prefix in timeline marker.'],
        'activity.timeline.legend_aria' => ['text' => 'Légende de la régularité', 'context' => 'Timeline legend accessible label.'],
        'activity.state.upcoming' => ['text' => 'À venir', 'context' => 'Upcoming state.'],
        'activity.state.due' => ['text' => 'À faire', 'context' => 'Activity waiting for validation.'],
        'activity.state.due_soon' => ['text' => 'Bientôt à faire', 'context' => 'Activity displayed before its expected time.'],
        'activity.state.overdue' => ['text' => 'En retard', 'context' => 'Overdue state.'],
        'activity.state.checked' => ['text' => 'Faite dans les temps', 'context' => 'Checked on time state.'],
        'activity.state.late' => ['text' => 'Faite en retard', 'context' => 'Checked late state.'],
        'activity.state.missed' => ['text' => 'Non faite', 'context' => 'Missed occurrence state.'],
        'activity.state.invalid' => ['text' => 'Planification invalide', 'context' => 'Invalid schedule state.'],
        'activity.overdue.days.one' => ['text' => '1 jour de retard', 'context' => 'One day overdue label.'],
        'activity.overdue.days.other' => ['text' => '{count} jours de retard', 'context' => 'Multiple days overdue label.'],
        'activity.due.for' => ['text' => 'À faire pour le {date}', 'context' => 'Due occurrence date label.'],
        'activity.due_soon.for' => ['text' => 'Bientôt à faire pour le {date}', 'context' => 'Upcoming due occurrence date label.'],
        'activity.upcoming.on' => ['text' => 'Prévue le {date}', 'context' => 'Upcoming occurrence date label.'],
        'activity.checked.on' => ['text' => 'Fait le {date}', 'context' => 'Checked date label.'],
        'activity.checked.late_on' => ['text' => 'Fait en retard le {date}', 'context' => 'Late checked date label.'],
        'activity.frequency.daily' => ['text' => 'Chaque jour', 'context' => 'Daily frequency.'],
        'activity.frequency.weekly' => ['text' => 'Chaque semaine', 'context' => 'Weekly frequency.'],
        'activity.frequency.monthly' => ['text' => 'Chaque mois', 'context' => 'Monthly frequency.'],
        'activity.frequency.quarterly' => ['text' => 'Chaque trimestre', 'context' => 'Quarterly frequency.'],
        'activity.frequency.semiannual' => ['text' => 'Chaque semestre', 'context' => 'Semiannual frequency.'],
        'activity.frequency.yearly' => ['text' => 'Chaque année', 'context' => 'Yearly frequency.'],
        'activity.delay.day' => ['text' => 'jour(s)', 'context' => 'Day unit.'],
        'activity.delay.week' => ['text' => 'semaine(s)', 'context' => 'Week unit.'],
        'activity.delay.month' => ['text' => 'mois', 'context' => 'Month unit.'],
        'activity.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Context error.'],
        'activity.error.not_found' => ['text' => 'Activité introuvable.', 'context' => 'Not found error.'],
        'activity.error.forbidden' => ['text' => 'Cette action n est pas autorisée.', 'context' => 'Forbidden error.'],
        'activity.error.title' => ['text' => 'Le titre est obligatoire.', 'context' => 'Title error.'],
        'activity.error.schedule' => ['text' => 'La récurrence ou sa référence est invalide.', 'context' => 'Schedule error.'],
        'activity.error.save' => ['text' => 'Impossible d enregistrer cette activité.', 'context' => 'Save error.'],
        'activity.error.load' => ['text' => 'Impossible de charger cette activité.', 'context' => 'Activity load error.'],
        'activity.error.action' => ['text' => 'Action impossible.', 'context' => 'Generic activity action error.'],
        'activity.loading' => ['text' => 'Chargement de l’activité...', 'context' => 'Activity loading message.'],
        'activity.confirm.delete' => ['text' => 'Supprimer cette activité et son historique ?', 'context' => 'Activity deletion confirmation.'],
        'activity.success.saved' => ['text' => 'Activité enregistrée.', 'context' => 'Save success.'],
        'activity.success.checked' => ['text' => 'Activité validée.', 'context' => 'Check success.'],
    ];
}

function omoActivityT($key, array $replace = [])
{
    static $bundle = null;
    static $source = null;
    $source = $source ?? omoActivitySourceLang();
    $bundle = $bundle ?? omoLoadTranslationBundle('omo_activities', $source);
    return t($key, $replace, $bundle, $source);
}

function omoActivityStateLabel(array $state, DateTimeImmutable $now)
{
    $stateKey = (string)($state['state'] ?? 'upcoming');
    if ($stateKey === 'due') {
        $occurrenceAt = $state['occurrenceAt'] ?? null;
        if ($occurrenceAt instanceof DateTimeInterface && DateTimeImmutable::createFromInterface($occurrenceAt) > $now) {
            return omoActivityT('activity.state.due_soon');
        }
    }
    return omoActivityT('activity.state.' . $stateKey);
}

function omoActivityResolveContext($organizationId, $currentHolonId = 0)
{
    $organization = new Organization();
    if ((int)$organizationId <= 0 || !$organization->load((int)$organizationId) || !$organization->canViewDetail()) { return ['status' => false, 'message' => omoActivityT('activity.error.context')]; }
    $root = $organization->getEnabledStructuralRootHolon();
    $holon = $root instanceof Holon ? $root : null;
    if ((int)$currentHolonId > 0) {
        $candidate = new Holon();
        if (!($root instanceof Holon) || !$candidate->load((int)$currentHolonId) || !$candidate->isDescendantOf((int)$root->getId(), true) || !$candidate->canViewDetail()) { return ['status' => false, 'message' => omoActivityT('activity.error.context')]; }
        $holon = $candidate;
    }
    return ['status' => true, 'organization' => $organization, 'rootHolon' => $root, 'currentHolon' => $holon];
}

function omoActivityCanUsePermission(Holon $holon, $permissionKey)
{
    $userId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
    return $userId > 0 && $holon->isAllowed((string)$permissionKey, strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST', $userId);
}

function omoActivityCanView(ControlActivity $activity)
{
    $holon = $activity->getHolon();
    return $holon instanceof Holon && $holon->canViewDetail();
}

function omoActivityCanEdit(ControlActivity $activity)
{
    $holon = $activity->getHolon();
    return $holon instanceof Holon && omoActivityCanUsePermission($holon, 'CAN_EDIT_CONTROL_ACTIVITY');
}

function omoActivityCanDelete(ControlActivity $activity)
{
    $holon = $activity->getHolon();
    return $holon instanceof Holon && omoActivityCanUsePermission($holon, 'CAN_DELETE_CONTROL_ACTIVITY');
}

function omoActivityFrequencyLabel($frequency)
{
    $frequency = RecurrenceSchedule::normalizeFrequency($frequency);
    return $frequency ? omoActivityT('activity.frequency.' . $frequency) : '';
}

function omoActivityScheduleOptions()
{
    $weekdays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $options = [RecurrenceSchedule::FREQUENCY_DAILY => []];
    for ($hour = 0; $hour < 24; $hour++) { $options['daily'][] = ['value' => sprintf('%02d:00', $hour), 'label' => sprintf('%02dh00', $hour)]; }
    foreach ($weekdays as $i => $label) { $options['weekly'][] = ['value' => (string)($i + 1), 'label' => $label]; }
    for ($i = 1; $i <= 31; $i++) { $options['monthly'][] = ['value' => (string)$i, 'label' => $i . 'e jour']; }
    for ($i = 1; $i <= 3; $i++) { $options['quarterly'][] = ['value' => (string)$i, 'label' => 'Mois ' . $i . ' du trimestre']; }
    for ($i = 1; $i <= 6; $i++) { $options['semiannual'][] = ['value' => (string)$i, 'label' => 'Mois ' . $i . ' du semestre']; }
    foreach ($months as $i => $label) { $options['yearly'][] = ['value' => (string)($i + 1), 'label' => $label]; }
    return $options;
}

function omoActivityScheduleLabel($frequency, $schedule)
{
    $frequency = RecurrenceSchedule::normalizeFrequency($frequency);
    $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $schedule);
    foreach (omoActivityScheduleOptions()[$frequency] ?? [] as $option) { if ((string)$option['value'] === (string)$schedule) { return $option['label']; } }
    return '';
}

function omoActivityOverdueDays(array $state, DateTimeImmutable $now)
{
    $missedOccurrenceAt = $state['missedOccurrenceAt'] ?? null;
    if (($state['state'] ?? '') !== 'missed' || !($missedOccurrenceAt instanceof DateTimeInterface)) {
        return 0;
    }
    return max(1, (int)$missedOccurrenceAt->diff($now)->format('%a'));
}

function omoActivityOverdueLabel(array $state, DateTimeImmutable $now)
{
    $days = omoActivityOverdueDays($state, $now);
    if ($days <= 0) {
        return '';
    }
    return omoActivityT($days === 1 ? 'activity.overdue.days.one' : 'activity.overdue.days.other', ['count' => $days]);
}
?>
