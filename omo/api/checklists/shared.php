<?php

use dbObject\ControlList;
use dbObject\ControlTask;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\RecurrenceSchedule;

if (!function_exists('omoControlListSourceLang')) {
    function omoControlListSourceLang()
    {
        return [
            'control.title' => ['text' => 'Listes de contrôle', 'context' => 'Main title of the control list app.'],
            'control.drawer.description' => ['text' => 'Activités récurrentes à valider, sans créer de projet.', 'context' => 'Drawer subtitle.'],
            'control.action.new' => ['text' => 'Ajouter une liste', 'context' => 'Create control list action.'],
            'control.action.add_task' => ['text' => 'Ajouter une activité', 'context' => 'Create task action.'],
            'control.action.check' => ['text' => 'Valider', 'context' => 'Confirm a task occurrence.'],
            'control.action.save' => ['text' => 'Enregistrer', 'context' => 'Save action.'],
            'control.action.edit' => ['text' => 'Modifier', 'context' => 'Edit action.'],
            'control.action.delete' => ['text' => 'Supprimer', 'context' => 'Delete action.'],
            'control.action.back' => ['text' => 'Retour aux listes', 'context' => 'Return action.'],
            'control.action.close' => ['text' => 'Fermer', 'context' => 'Close drawer action.'],
            'control.scope.contextual' => ['text' => 'Local', 'context' => 'Current holon scope.'],
            'control.scope.children' => ['text' => 'Enfants directs', 'context' => 'Children scope.'],
            'control.scope.descendants' => ['text' => 'Descendants', 'context' => 'Descendant scope.'],
            'control.field.title' => ['text' => 'Titre', 'context' => 'Title field.'],
            'control.field.description' => ['text' => 'Description', 'context' => 'Description field.'],
            'control.field.frequency' => ['text' => 'Récurrence', 'context' => 'Frequency field.'],
            'control.field.reference' => ['text' => 'Référence', 'context' => 'Schedule reference field.'],
            'control.field.display_lead' => ['text' => 'Afficher en avance', 'context' => 'Advance display duration.'],
            'control.field.overdue_after' => ['text' => 'En retard après', 'context' => 'Overdue duration.'],
            'control.field.unit' => ['text' => 'Unité', 'context' => 'Duration unit.'],
            'control.frequency.daily' => ['text' => 'Chaque jour', 'context' => 'Daily recurrence.'],
            'control.frequency.weekly' => ['text' => 'Chaque semaine', 'context' => 'Weekly recurrence.'],
            'control.frequency.monthly' => ['text' => 'Chaque mois', 'context' => 'Monthly recurrence.'],
            'control.frequency.quarterly' => ['text' => 'Chaque trimestre', 'context' => 'Quarterly recurrence.'],
            'control.frequency.semiannual' => ['text' => 'Chaque semestre', 'context' => 'Semiannual recurrence.'],
            'control.frequency.yearly' => ['text' => 'Chaque année', 'context' => 'Yearly recurrence.'],
            'control.delay.day' => ['text' => 'jour(s)', 'context' => 'Duration unit day.'],
            'control.delay.week' => ['text' => 'semaine(s)', 'context' => 'Duration unit week.'],
            'control.delay.month' => ['text' => 'mois', 'context' => 'Duration unit month.'],
            'control.state.due' => ['text' => 'À faire', 'context' => 'Task due state.'],
            'control.state.overdue' => ['text' => 'En retard', 'context' => 'Task overdue state.'],
            'control.state.checked' => ['text' => 'Validée', 'context' => 'Task checked state.'],
            'control.state.upcoming' => ['text' => 'À venir', 'context' => 'Task upcoming state.'],
            'control.empty' => ['text' => 'Aucune liste de contrôle dans cette portée.', 'context' => 'Control list empty state.'],
            'control.empty.tasks' => ['text' => 'Aucune activité dans cette liste.', 'context' => 'Task empty state.'],
            'control.regularity' => ['text' => 'Régularité des 12 dernières occurrences', 'context' => 'Regularity graph heading.'],
            'control.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Invalid context.'],
            'control.error.not_found' => ['text' => 'Élément introuvable.', 'context' => 'Not found error.'],
            'control.error.forbidden' => ['text' => 'Cette action n est pas autorisée.', 'context' => 'Forbidden action.'],
            'control.error.title' => ['text' => 'Le titre est obligatoire.', 'context' => 'Missing title error.'],
            'control.error.schedule' => ['text' => 'La récurrence ou sa référence est invalide.', 'context' => 'Invalid schedule.'],
            'control.error.save' => ['text' => 'Impossible d enregistrer cet élément.', 'context' => 'Save error.'],
            'control.error.method' => ['text' => 'Cette action doit être envoyée en POST.', 'context' => 'Invalid HTTP method.'],
            'control.success.saved' => ['text' => 'Enregistré.', 'context' => 'Save success.'],
            'control.success.checked' => ['text' => 'Activité validée.', 'context' => 'Check success.'],
        ];
    }
}

if (!function_exists('omoControlListLoadTranslationBundle')) {
    function omoControlListLoadTranslationBundle()
    {
        static $bundle = null;
        if ($bundle === null) {
            $bundle = omoLoadTranslationBundle('omo_control_lists', omoControlListSourceLang());
        }
        return $bundle;
    }
}

if (!function_exists('omoControlListT')) {
    function omoControlListT($key, array $replace = [])
    {
        return t($key, $replace, omoControlListLoadTranslationBundle(), omoControlListSourceLang());
    }
}

if (!function_exists('omoControlListResolveContext')) {
    function omoControlListResolveContext($organizationId, $currentHolonId = 0)
    {
        $organization = new Organization();
        if ((int)$organizationId <= 0 || !$organization->load((int)$organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoControlListT('control.error.context')];
        }
        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $currentHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ((int)$currentHolonId > 0) {
            $candidate = new Holon();
            if (!($rootHolon instanceof Holon) || !$candidate->load((int)$currentHolonId) || !$candidate->isDescendantOf((int)$rootHolon->getId(), true) || !$candidate->canViewDetail()) {
                return ['status' => false, 'message' => omoControlListT('control.error.context')];
            }
            $currentHolon = $candidate;
        }
        return ['status' => true, 'organization' => $organization, 'rootHolon' => $rootHolon, 'currentHolon' => $currentHolon];
    }
}

if (!function_exists('omoControlListCanUsePermission')) {
    function omoControlListCanUsePermission(Holon $holon, $permissionKey)
    {
        $userId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        return $userId > 0 && $holon->isAllowed((string)$permissionKey, strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST', $userId);
    }
}

if (!function_exists('omoControlListCanView')) {
    function omoControlListCanView(ControlList $list)
    {
        $holon = $list->getHolon();
        return $holon instanceof Holon && $holon->canViewDetail();
    }
}

if (!function_exists('omoControlListCanManage')) {
    function omoControlListCanManage(ControlList $list)
    {
        $holon = $list->getHolon();
        return $holon instanceof Holon && omoControlListCanUsePermission($holon, 'CAN_EDIT_CONTROL_LIST');
    }
}

if (!function_exists('omoControlListCanDelete')) {
    function omoControlListCanDelete(ControlList $list)
    {
        $holon = $list->getHolon();
        return $holon instanceof Holon && omoControlListCanUsePermission($holon, 'CAN_DELETE_CONTROL_LIST');
    }
}

if (!function_exists('omoControlListLoad')) {
    function omoControlListLoad($listId, $organizationId)
    {
        $list = new ControlList();
        return (int)$listId > 0 && $list->load((int)$listId) && (int)$list->get('IDorganization') === (int)$organizationId && (int)$list->get('active') === 1 ? $list : null;
    }
}

if (!function_exists('omoControlListFrequencyLabel')) {
    function omoControlListFrequencyLabel($frequency)
    {
        $frequency = RecurrenceSchedule::normalizeFrequency($frequency);
        return $frequency === null ? '' : omoControlListT('control.frequency.' . $frequency);
    }
}

if (!function_exists('omoControlListScheduleOptions')) {
    function omoControlListScheduleOptions()
    {
        $weekdays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $options = [RecurrenceSchedule::FREQUENCY_DAILY => []];
        for ($hour = 0; $hour < 24; $hour++) {
            $options[RecurrenceSchedule::FREQUENCY_DAILY][] = ['value' => sprintf('%02d:00', $hour), 'label' => sprintf('%02dh00', $hour)];
        }
        foreach ($weekdays as $index => $label) { $options[RecurrenceSchedule::FREQUENCY_WEEKLY][] = ['value' => (string)($index + 1), 'label' => $label]; }
        for ($day = 1; $day <= 31; $day++) { $options[RecurrenceSchedule::FREQUENCY_MONTHLY][] = ['value' => (string)$day, 'label' => $day . 'e jour']; }
        for ($month = 1; $month <= 3; $month++) { $options[RecurrenceSchedule::FREQUENCY_QUARTERLY][] = ['value' => (string)$month, 'label' => '1er mois: ' . $months[$month - 1]]; }
        for ($month = 1; $month <= 6; $month++) { $options[RecurrenceSchedule::FREQUENCY_SEMIANNUAL][] = ['value' => (string)$month, 'label' => 'Mois de référence: ' . $months[$month - 1]]; }
        foreach ($months as $index => $label) { $options[RecurrenceSchedule::FREQUENCY_YEARLY][] = ['value' => (string)($index + 1), 'label' => $label]; }
        return $options;
    }
}

if (!function_exists('omoControlListScheduleLabel')) {
    function omoControlListScheduleLabel($frequency, $schedule)
    {
        $frequency = RecurrenceSchedule::normalizeFrequency($frequency);
        $schedule = RecurrenceSchedule::normalizeSchedule($frequency, $schedule);
        foreach (omoControlListScheduleOptions()[$frequency] ?? [] as $option) {
            if ((string)$option['value'] === (string)$schedule) { return (string)$option['label']; }
        }
        return '';
    }
}
?>
