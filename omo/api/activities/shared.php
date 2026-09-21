<?php
require_once dirname(__DIR__, 3) . '/common/pv_meeting_permissions.php';

use dbObject\ControlActivity;
use dbObject\DocumentPvPoint;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\RecurrenceSchedule;

function omoActivitySourceLang()
{
    return [
        'activity.title' => ['text' => 'Tâches récurrentes', 'context' => 'Recurring tasks application title.'],
        'activity.new' => ['text' => 'Ajouter une tâche récurrente', 'context' => 'Create recurring task action.'],
        'activity.edit' => ['text' => 'Modifier', 'context' => 'Edit activity action.'],
        'activity.delete' => ['text' => 'Supprimer', 'context' => 'Delete activity action.'],
        'activity.check' => ['text' => 'Valider', 'context' => 'Validate activity action.'],
        'activity.done' => ['text' => 'Fait', 'context' => 'Direct completion action in the activity list.'],
        'activity.save' => ['text' => 'Enregistrer', 'context' => 'Save activity action.'],
        'activity.cancel' => ['text' => 'Annuler', 'context' => 'Cancel editing action.'],
        'activity.editor.create_title' => ['text' => 'Nouvelle tâche récurrente', 'context' => 'Create recurring task drawer title.'],
        'activity.editor.edit_title' => ['text' => 'Modifier la tâche récurrente', 'context' => 'Edit recurring task drawer title.'],
        'activity.editor.identity' => ['text' => 'La tâche récurrente', 'context' => 'Recurring task identity form section.'],
        'activity.editor.identity_help' => ['text' => 'Donnez un titre clair, une description utile et, si besoin, une personne responsable.', 'context' => 'Help for the recurring task identity section.'],
        'activity.editor.recurrence_help' => ['text' => 'Choisissez la fréquence, puis le jour, la date ou le mois auquel la tâche doit revenir.', 'context' => 'Help for the recurring task schedule section.'],
        'activity.editor.window' => ['text' => 'Fenêtre de réalisation', 'context' => 'Activity execution window form section.'],
        'activity.editor.window_help' => ['text' => 'Définissez quand la tâche devient visible et après quel délai elle est considérée en retard.', 'context' => 'Help for the recurring task execution window.'],
        'activity.close' => ['text' => 'Fermer', 'context' => 'Close drawer action.'],
        'activity.back' => ['text' => 'Retour aux tâches récurrentes', 'context' => 'Back action.'],
        'activity.scope.contextual' => ['text' => 'Local', 'context' => 'Current holon scope.'],
        'activity.scope.children' => ['text' => 'Enfants directs', 'context' => 'Children scope.'],
        'activity.scope.descendants' => ['text' => 'Descendants', 'context' => 'Descendant scope.'],
        'activity.empty' => ['text' => 'Aucune tâche récurrente dans cette portée.', 'context' => 'Empty state.'],
        'activity.search.placeholder' => ['text' => 'Rechercher une tâche récurrente...', 'context' => 'Recurring task search placeholder.'],
        'activity.search.aria' => ['text' => 'Rechercher dans les tâches récurrentes', 'context' => 'Recurring task search accessible label.'],
        'activity.search.empty' => ['text' => 'Aucune tâche récurrente ne correspond à la recherche.', 'context' => 'Empty search result.'],
        'activity.filters.aria' => ['text' => 'Filtres des tâches récurrentes', 'context' => 'Recurring task filters accessible label.'],
        'activity.filters.scope' => ['text' => 'Portée', 'context' => 'Scope filter heading.'],
        'activity.filters.assignment' => ['text' => 'Attribution', 'context' => 'Recurring task assignment filter heading.'],
        'activity.filters.state' => ['text' => 'État', 'context' => 'State filter heading.'],
        'activity.filters.apply' => ['text' => 'Appliquer', 'context' => 'Apply filters action.'],
        'activity.filters.save_view' => ['text' => 'Enregistrer cette vue', 'context' => 'Save filters action.'],
        'activity.filter.all' => ['text' => 'Tous les états', 'context' => 'All states filter.'],
        'activity.filter.attention' => ['text' => 'À traiter', 'context' => 'Due and missed states filter.'],
        'activity.filter.missed' => ['text' => 'Non faites', 'context' => 'Missed state filter.'],
        'activity.filter.checked' => ['text' => 'Faites', 'context' => 'Checked state filter.'],
        'activity.filter.upcoming' => ['text' => 'À venir', 'context' => 'Upcoming state filter.'],
        'activity.assignment.mine' => ['text' => 'Moi', 'context' => 'Recurring tasks directly assigned to the user or unassigned in their spaces.'],
        'activity.assignment.spaces' => ['text' => 'Mes espaces', 'context' => 'Recurring tasks belonging to the user spaces.'],
        'activity.assignment.all' => ['text' => 'Tous', 'context' => 'All recurring tasks in the visible scope.'],
        'activity.column.activity' => ['text' => 'Tâche récurrente', 'context' => 'Recurring task list title column.'],
            'activity.column.context' => ['text' => 'Espace', 'context' => 'Activity list context column.'],
        'activity.column.next' => ['text' => 'Échéance', 'context' => 'Activity list due date column.'],
        'activity.column.status' => ['text' => 'État', 'context' => 'Activity list state column.'],
        'activity.frequency' => ['text' => 'Récurrence', 'context' => 'Frequency field.'],
        'activity.reference' => ['text' => 'Référence', 'context' => 'Reference field.'],
        'activity.title_field' => ['text' => 'Titre', 'context' => 'Title field.'],
        'activity.description_field' => ['text' => 'Description', 'context' => 'Description field.'],
        'activity.responsibility.label' => ['text' => 'En charge', 'context' => 'Activity responsibility label.'],
        'activity.responsibility.unassigned' => ['text' => 'Non attribué', 'context' => 'Activity without a directly assigned person.'],
        'activity.editor.responsible' => ['text' => 'Personne en charge', 'context' => 'Activity responsible person field.'],
        'activity.editor.responsible_none' => ['text' => 'Aucune personne', 'context' => 'Activity responsible person empty option.'],
        'activity.editor.responsible_help' => ['text' => 'Cette personne est responsable en complément de l espace porteur de la tâche récurrente.', 'context' => 'Recurring task responsible person field help.'],
        'activity.display_lead' => ['text' => 'Afficher en avance', 'context' => 'Advance field.'],
        'activity.display_lead_help' => ['text' => 'La tâche apparaît ce nombre d unités avant sa date prévue.', 'context' => 'Help for the activity advance display field.'],
        'activity.overdue_after' => ['text' => 'En retard après', 'context' => 'Delay field.'],
        'activity.overdue_after_help' => ['text' => 'La tâche est signalée en retard après ce délai suivant sa date prévue.', 'context' => 'Help for the activity overdue delay field.'],
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
        'activity.delay.hour' => ['text' => 'heure(s)', 'context' => 'Hour unit.'],
        'activity.delay.day' => ['text' => 'jour(s)', 'context' => 'Day unit.'],
        'activity.delay.week' => ['text' => 'semaine(s)', 'context' => 'Week unit.'],
        'activity.delay.month' => ['text' => 'mois', 'context' => 'Month unit.'],
        'activity.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Context error.'],
        'activity.error.not_found' => ['text' => 'Tâche récurrente introuvable.', 'context' => 'Not found error.'],
        'activity.error.forbidden' => ['text' => 'Cette action n est pas autorisée.', 'context' => 'Forbidden error.'],
        'activity.error.title' => ['text' => 'Le titre est obligatoire.', 'context' => 'Title error.'],
        'activity.error.schedule' => ['text' => 'La récurrence ou sa référence est invalide.', 'context' => 'Schedule error.'],
        'activity.error.save' => ['text' => 'Impossible d enregistrer cette tâche récurrente.', 'context' => 'Save error.'],
        'activity.error.load' => ['text' => 'Impossible de charger cette tâche récurrente.', 'context' => 'Recurring task load error.'],
        'activity.error.action' => ['text' => 'Action impossible.', 'context' => 'Generic activity action error.'],
        'activity.loading' => ['text' => 'Chargement de la tâche récurrente...', 'context' => 'Recurring task loading message.'],
        'activity.confirm.delete' => ['text' => 'Supprimer cette tâche récurrente et son historique ?', 'context' => 'Recurring task deletion confirmation.'],
        'activity.success.saved' => ['text' => 'Tâche récurrente enregistrée.', 'context' => 'Save success.'],
        'activity.success.checked' => ['text' => 'Tâche récurrente validée.', 'context' => 'Check success.'],
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
    if ($userId <= 0) {
        return false;
    }

    return $holon->isAllowed((string)$permissionKey, false, $userId)
        || commonPvMeetingCanUseCollectivePermission(
            commonResolvePvMeetingPermissionContext(commonResolveHolonOrganizationId($holon)),
            $holon,
            (string)$permissionKey
        );
}

function omoActivityPvMeetingQuery(int $organizationId): string
{
    $meetingContext = commonResolvePvMeetingPermissionContext($organizationId);
    $documentId = is_array($meetingContext) ? (int)($meetingContext['documentId'] ?? 0) : 0;
    $request = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $editorToken = trim((string)($request['pv_meeting_editor_token'] ?? $request['editor_token'] ?? ''));
    return $documentId > 0 && $editorToken !== ''
        ? '&pv_meeting_document_id=' . $documentId . '&pv_meeting_editor_token=' . rawurlencode($editorToken)
        : '';
}

function omoActivityCanView(ControlActivity $activity)
{
    $holon = $activity->getHolon();
    return $holon instanceof Holon && $holon->canViewDetail();
}

if (!function_exists('omoActivityNormalizeAssignment')) {
    function omoActivityNormalizeAssignment($assignment): string
    {
        $assignment = strtolower(trim((string)$assignment));
        return in_array($assignment, ['mine', 'spaces'], true) ? $assignment : 'all';
    }
}

if (!function_exists('omoActivityUserIsAssociatedWithHolon')) {
    function omoActivityUserIsAssociatedWithHolon($userId, $organizationId, Holon $holon): bool
    {
        static $associationCache = [];

        $userId = (int)$userId;
        $organizationId = (int)$organizationId;
        $holonId = (int)$holon->getId();
        if ($userId <= 0 || $organizationId <= 0 || $holonId <= 0) {
            return false;
        }

        $cacheKey = $userId . ':' . $organizationId . ':' . $holonId;
        if (!array_key_exists($cacheKey, $associationCache)) {
            $associationCache[$cacheKey] = in_array(
                $userId,
                $holon->getAssociatedMemberUserIds([
                    'organizationId' => $organizationId,
                    'skipPermissionFilter' => true,
                ]),
                true
            );
        }

        return $associationCache[$cacheKey];
    }
}

if (!function_exists('omoActivityMatchesAssignment')) {
    function omoActivityMatchesAssignment(ControlActivity $activity, $assignment, $currentUserId, $organizationId): bool
    {
        $assignment = omoActivityNormalizeAssignment($assignment);
        if ($assignment === 'all') {
            return true;
        }

        $currentUserId = (int)$currentUserId;
        if ($currentUserId <= 0) {
            return false;
        }

        $responsibleUserId = (int)$activity->get('IDuser_responsible');
        if ($assignment === 'mine' && $responsibleUserId === $currentUserId) {
            return true;
        }
        if ($assignment === 'mine' && $responsibleUserId > 0) {
            return false;
        }

        $holon = $activity->getHolon();
        return $holon instanceof Holon
            && omoActivityUserIsAssociatedWithHolon($currentUserId, $organizationId, $holon);
    }
}

function omoActivityCanEdit(ControlActivity $activity)
{
    $holon = $activity->getHolon();
    return $holon instanceof Holon && omoActivityCanUsePermission($holon, 'CAN_EDIT_RECURRING_TASK');
}

function omoActivityCanDelete(ControlActivity $activity)
{
    $holon = $activity->getHolon();
    return $holon instanceof Holon && omoActivityCanUsePermission($holon, 'CAN_DELETE_RECURRING_TASK');
}

function omoActivityResponsibleAssignmentLabel(ControlActivity $activity)
{
    $holon = $activity->getHolon();
    $roleLabel = $holon instanceof Holon ? trim((string)$holon->getDisplayName()) : '';
    $responsibleUserId = (int)$activity->get('IDuser_responsible');
    $responsibleLabel = $responsibleUserId > 0
        ? DocumentPvPoint::getUserDisplayNameForOrganization($responsibleUserId, (int)$activity->get('IDorganization'))
        : omoActivityT('activity.responsibility.unassigned');
    return trim($roleLabel) . ' (' . trim((string)$responsibleLabel) . ')';
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

function omoActivityDescriptionHtml($description)
{
    return \dbObject\PropertyFormat::sanitizeHtml((string)$description);
}

function omoActivityDescriptionText($description, $maxWidth = 0)
{
    $text = html_entity_decode(
        strip_tags(omoActivityDescriptionHtml($description)),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $text = trim((string)preg_replace('/\s+/', ' ', $text));

    return (int)$maxWidth > 0
        ? mb_strimwidth($text, 0, (int)$maxWidth, '...', 'UTF-8')
        : $text;
}
?>
