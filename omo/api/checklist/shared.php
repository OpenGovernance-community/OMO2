<?php

use dbObject\Checklist;
use dbObject\ChecklistItem;
use dbObject\ChecklistTrigger;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\Project;
use dbObject\RecurrenceSchedule;

if (!function_exists('omoChecklistSourceLang')) {
    function omoChecklistSourceLang()
    {
        return [
            'checklist.title' => ['text' => 'Checklists', 'context' => 'Main title of the checklist application.'],
            'checklist.description' => ['text' => 'Des processus réutilisables qui deviennent des projets au bon moment.', 'context' => 'Introduction of the checklist application.'],
            'checklist.action.new' => ['text' => 'Ajouter', 'context' => 'Button opening checklist creation.'],
            'checklist.action.edit' => ['text' => 'Modifier', 'context' => 'Button opening checklist edition.'],
            'checklist.action.close' => ['text' => 'Fermer', 'context' => 'Button closing the checklist drawer.'],
            'checklist.action.cancel' => ['text' => 'Annuler', 'context' => 'Button cancelling checklist edition.'],
            'checklist.action.save' => ['text' => 'Enregistrer', 'context' => 'Button saving a checklist.'],
            'checklist.action.add_item' => ['text' => 'Ajouter un élément', 'context' => 'Button adding an item to a checklist.'],
            'checklist.action.edit_item' => ['text' => 'Modifier l élément', 'context' => 'Button editing a checklist item.'],
            'checklist.action.save_item' => ['text' => 'Enregistrer l élément', 'context' => 'Button saving a checklist item.'],
            'checklist.action.activate' => ['text' => 'Activer', 'context' => 'Button creating a checklist instance.'],
            'checklist.action.remove_item' => ['text' => 'Retirer', 'context' => 'Button removing an item from a checklist.'],
            'checklist.action.move_up' => ['text' => 'Monter', 'context' => 'Button moving an item upward.'],
            'checklist.action.move_down' => ['text' => 'Descendre', 'context' => 'Button moving an item downward.'],
            'checklist.scope.contextual' => ['text' => 'Local', 'context' => 'Scope showing checklists attached to the current holon.'],
            'checklist.scope.children' => ['text' => 'Enfants directs', 'context' => 'Scope showing checklists attached to the current holon and direct children.'],
            'checklist.scope.descendants' => ['text' => 'Descendants', 'context' => 'Scope showing checklists attached to the current holon and descendants.'],
            'checklist.empty.contextual' => ['text' => 'Aucune checklist dans ce contexte.', 'context' => 'Empty state for local checklist scope.'],
            'checklist.empty.children' => ['text' => 'Aucune checklist dans ce contexte ou ses enfants directs.', 'context' => 'Empty state for direct children scope.'],
            'checklist.empty.descendants' => ['text' => 'Aucune checklist dans ce contexte ou ses descendants.', 'context' => 'Empty state for descendant scope.'],
            'checklist.drawer.title' => ['text' => 'Checklist', 'context' => 'Default nested drawer title.'],
            'checklist.drawer.description' => ['text' => 'Structure, activation et responsabilités.', 'context' => 'Default nested drawer description.'],
            'checklist.loading' => ['text' => 'Chargement de la checklist...', 'context' => 'Loading state inside the checklist drawer.'],
            'checklist.error.load' => ['text' => 'Impossible de charger cette checklist.', 'context' => 'Checklist drawer loading error.'],
            'checklist.error.organization' => ['text' => 'Organisation invalide ou inaccessible.', 'context' => 'Invalid organization error.'],
            'checklist.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Invalid holon context error.'],
            'checklist.error.not_found' => ['text' => 'Checklist introuvable.', 'context' => 'Checklist not found error.'],
            'checklist.error.forbidden' => ['text' => 'Vous ne pouvez pas modifier cette checklist.', 'context' => 'Checklist permission error.'],
            'checklist.error.method' => ['text' => 'Cette action doit être envoyée en POST.', 'context' => 'Invalid HTTP method error.'],
            'checklist.error.action' => ['text' => 'Action inconnue.', 'context' => 'Unsupported checklist action error.'],
            'checklist.error.title' => ['text' => 'Le titre de la checklist est obligatoire.', 'context' => 'Missing checklist title error.'],
            'checklist.error.items' => ['text' => 'Ajoutez au moins un élément à la checklist.', 'context' => 'Missing checklist item error.'],
            'checklist.error.item_title' => ['text' => 'Chaque élément doit avoir un titre.', 'context' => 'Missing checklist item title error.'],
            'checklist.error.item_not_found' => ['text' => 'Élément de checklist introuvable.', 'context' => 'Checklist item not found error.'],
            'checklist.error.item_holon' => ['text' => 'Le rôle ou holon choisi pour un élément est invalide.', 'context' => 'Invalid checklist item holon error.'],
            'checklist.error.item_relation' => ['text' => 'Une relation entre les éléments est invalide ou forme une boucle.', 'context' => 'Invalid checklist relationship error.'],
            'checklist.error.item_recurrence_structure' => ['text' => 'Un élément récurrent doit être indépendant et visible immédiatement.', 'context' => 'Recurring container checklist item cannot have a parent or dependency.'],
            'checklist.error.schedule' => ['text' => 'La récurrence choisie est incomplète ou invalide.', 'context' => 'Invalid checklist recurrence error.'],
            'checklist.error.activation_unavailable' => ['text' => 'Cette checklist ne peut pas être activée à la demande.', 'context' => 'Checklist cannot be manually activated.'],
            'checklist.error.reference_date' => ['text' => 'La date de référence est invalide.', 'context' => 'Invalid checklist run reference date.'],
            'checklist.error.instance_title' => ['text' => 'Le nom de l instance est obligatoire.', 'context' => 'Missing or too long checklist instance title.'],
            'checklist.error.open_instance' => ['text' => 'Une instance est déjà en cours pour cette checklist.', 'context' => 'Checklist overlap prevents another run.'],
            'checklist.error.save' => ['text' => 'Impossible d enregistrer la checklist.', 'context' => 'Generic checklist persistence error.'],
            'checklist.success.save' => ['text' => 'Checklist enregistrée.', 'context' => 'Checklist save success.'],
            'checklist.success.activated' => ['text' => 'La nouvelle instance est active.', 'context' => 'Checklist run creation success.'],
            'checklist.success.reused' => ['text' => 'L instance déjà ouverte a été conservée.', 'context' => 'Existing checklist run reused.'],
            'checklist.form.create_title' => ['text' => 'Nouvelle checklist', 'context' => 'Checklist creation drawer title.'],
            'checklist.form.edit_title' => ['text' => 'Modifier la checklist', 'context' => 'Checklist edition drawer title.'],
            'checklist.form.intro' => ['text' => 'Définissez le modèle, ses éléments et les conditions qui les rendent visibles.', 'context' => 'Checklist editor introduction.'],
            'checklist.form.base_intro' => ['text' => 'Commencez par les informations générales. Les étapes seront ajoutées ensuite depuis la vue de la checklist.', 'context' => 'Checklist base information editor introduction.'],
            'checklist.form.activate_title' => ['text' => 'Activer la checklist', 'context' => 'Checklist activation drawer title.'],
            'checklist.form.activate_intro' => ['text' => 'La date de référence sert de point de départ aux étapes planifiées, y compris celles prévues avant cette date.', 'context' => 'Checklist activation form introduction.'],
            'checklist.form.instance_title' => ['text' => 'Nom de l instance', 'context' => 'Checklist manual activation instance title field.'],
            'checklist.form.instance_title_help' => ['text' => 'Ce nom devient le titre du projet racine créé pour cette instance.', 'context' => 'Explanation for the checklist instance title field.'],
            'checklist.form.reference_date' => ['text' => 'Date de référence', 'context' => 'Checklist run reference date field.'],
            'checklist.form.reference_help' => ['text' => 'Par exemple, la date d arrivée. Une étape à J-5 sera planifiée cinq jours avant.', 'context' => 'Checklist run reference date help.'],
            'checklist.form.confirm_overlap' => ['text' => 'Créer une nouvelle instance malgré les instances déjà ouvertes', 'context' => 'Confirmation required by ask overlap policy.'],
            'checklist.form.create_item_title' => ['text' => 'Ajouter une étape', 'context' => 'Checklist item creation drawer title.'],
            'checklist.form.edit_item_title' => ['text' => 'Modifier l étape', 'context' => 'Checklist item edition drawer title.'],
            'checklist.form.identity' => ['text' => 'Identité du modèle', 'context' => 'Checklist identity form section.'],
            'checklist.form.title' => ['text' => 'Titre', 'context' => 'Checklist title field.'],
            'checklist.form.description' => ['text' => 'Description', 'context' => 'Checklist description field.'],
            'checklist.form.status' => ['text' => 'État', 'context' => 'Checklist publication status field.'],
            'checklist.form.revision_note' => ['text' => 'Note interne', 'context' => 'Checklist revision note field.'],
            'checklist.form.trigger' => ['text' => 'Déclenchement de la checklist', 'context' => 'Checklist trigger form section.'],
            'checklist.form.trigger_help' => ['text' => 'Elle peut être lancée à la demande, suivant une récurrence ou servir uniquement de conteneur.', 'context' => 'Checklist trigger help.'],
            'checklist.form.trigger_type' => ['text' => 'Mode', 'context' => 'Checklist trigger type field.'],
            'checklist.form.frequency' => ['text' => 'Fréquence', 'context' => 'Checklist recurrence frequency field.'],
            'checklist.form.schedule' => ['text' => 'Moment attendu', 'context' => 'Checklist recurrence schedule field.'],
            'checklist.form.overlap' => ['text' => 'Si une exécution est encore ouverte', 'context' => 'Checklist overlap policy field.'],
            'checklist.form.items' => ['text' => 'Éléments de la checklist', 'context' => 'Checklist item editor section.'],
            'checklist.form.items_help' => ['text' => 'Chaque élément est un projet-modèle. Il peut être immédiat, décalé autour de la date de référence ou attendre un autre élément.', 'context' => 'Checklist item editor help.'],
            'checklist.form.item' => ['text' => 'Élément', 'context' => 'Checklist item card label.'],
            'checklist.form.end_date' => ['text' => 'Date limite', 'context' => 'Deadline shown for a recurring checklist project.'],
            'checklist.form.item_title' => ['text' => 'Titre de l élément', 'context' => 'Checklist item title field.'],
            'checklist.form.item_description' => ['text' => 'Description', 'context' => 'Checklist item description field.'],
            'checklist.form.holon' => ['text' => 'Rôle ou holon responsable', 'context' => 'Checklist item holon field.'],
            'checklist.form.parent' => ['text' => 'Sous-projet de', 'context' => 'Checklist item project parent field.'],
            'checklist.form.parent_root' => ['text' => 'Racine de la checklist', 'context' => 'Checklist root project parent option.'],
            'checklist.form.activation' => ['text' => 'Visibilité', 'context' => 'Checklist item activation field.'],
            'checklist.form.item_recurrence' => ['text' => 'Récurrence de cet élément', 'context' => 'Recurring schedule section for a container checklist item.'],
            'checklist.form.item_recurrence_help' => ['text' => 'Chaque occurrence crée un projet simple pour le rôle choisi. Vous pouvez le faire apparaître en avance et définir son délai de réalisation.', 'context' => 'Explanation for independent recurring checklist item projects.'],
            'checklist.form.display_lead' => ['text' => 'Afficher en avance', 'context' => 'How long before the scheduled date a recurring project becomes visible.'],
            'checklist.form.display_lead_unit' => ['text' => 'Unité d anticipation', 'context' => 'Unit for recurring project display lead time.'],
            'checklist.form.execution_duration' => ['text' => 'Délai de réalisation', 'context' => 'How long a recurring project can be completed.'],
            'checklist.form.execution_duration_unit' => ['text' => 'Unité du délai', 'context' => 'Unit for recurring project execution duration.'],
            'checklist.form.delay' => ['text' => 'Délai', 'context' => 'Checklist item delay field.'],
            'checklist.form.unit' => ['text' => 'Unité', 'context' => 'Checklist item delay unit field.'],
            'checklist.form.dependency' => ['text' => 'Après l élément', 'context' => 'Checklist item dependency field.'],
            'checklist.form.select_item' => ['text' => 'Choisir un élément...', 'context' => 'Empty checklist item relation option.'],
            'checklist.form.priority' => ['text' => 'Priorité', 'context' => 'Checklist item priority field.'],
            'checklist.form.importance' => ['text' => 'Importance', 'context' => 'Checklist item importance field.'],
            'checklist.form.size' => ['text' => 'Taille', 'context' => 'Checklist item size field.'],
            'checklist.activation.immediate' => ['text' => 'Visible immédiatement', 'context' => 'Immediate checklist item activation.'],
            'checklist.activation.after_start' => ['text' => 'Selon la date de référence', 'context' => 'Checklist item activation relative to the run reference date.'],
            'checklist.activation.after_completion' => ['text' => 'Visible après un autre élément', 'context' => 'Dependent checklist item activation.'],
            'checklist.delay.day' => ['text' => 'Jour(s)', 'context' => 'Day delay unit.'],
            'checklist.delay.week' => ['text' => 'Semaine(s)', 'context' => 'Week delay unit.'],
            'checklist.delay.month' => ['text' => 'Mois', 'context' => 'Month delay unit.'],
            'checklist.trigger.manual' => ['text' => 'À la demande', 'context' => 'Manual checklist trigger.'],
            'checklist.trigger.scheduled' => ['text' => 'Récurrent', 'context' => 'Scheduled checklist trigger.'],
            'checklist.trigger.container' => ['text' => 'Conteneur', 'context' => 'Checklist that only groups independently triggered items.'],
            'checklist.status.draft' => ['text' => 'Brouillon', 'context' => 'Draft checklist status.'],
            'checklist.status.published' => ['text' => 'Disponible', 'context' => 'Published checklist status.'],
            'checklist.status.retired' => ['text' => 'Désactivée', 'context' => 'Retired checklist status.'],
            'checklist.overlap.create_new' => ['text' => 'Créer une nouvelle exécution', 'context' => 'Create a new checklist run overlap policy.'],
            'checklist.overlap.reuse_open' => ['text' => 'Réutiliser l exécution ouverte', 'context' => 'Reuse open checklist run overlap policy.'],
            'checklist.overlap.skip' => ['text' => 'Ignorer cette occurrence', 'context' => 'Skip checklist run overlap policy.'],
            'checklist.overlap.ask' => ['text' => 'Demander au moment venu', 'context' => 'Ask checklist run overlap policy.'],
            'checklist.frequency.daily' => ['text' => 'Chaque jour', 'context' => 'Daily recurrence.'],
            'checklist.frequency.weekly' => ['text' => 'Chaque semaine', 'context' => 'Weekly recurrence.'],
            'checklist.frequency.monthly' => ['text' => 'Chaque mois', 'context' => 'Monthly recurrence.'],
            'checklist.frequency.quarterly' => ['text' => 'Chaque trimestre', 'context' => 'Quarterly recurrence.'],
            'checklist.frequency.semiannual' => ['text' => 'Chaque semestre', 'context' => 'Semiannual recurrence.'],
            'checklist.frequency.yearly' => ['text' => 'Chaque année', 'context' => 'Yearly recurrence.'],
            'checklist.schedule.none' => ['text' => 'Choisir...', 'context' => 'Empty recurrence schedule option.'],
            'checklist.schedule.month_day' => ['text' => 'Le {day} du mois', 'context' => 'Monthly day recurrence option.'],
            'checklist.schedule.weekday.1' => ['text' => 'Lundi', 'context' => 'Monday recurrence option.'],
            'checklist.schedule.weekday.2' => ['text' => 'Mardi', 'context' => 'Tuesday recurrence option.'],
            'checklist.schedule.weekday.3' => ['text' => 'Mercredi', 'context' => 'Wednesday recurrence option.'],
            'checklist.schedule.weekday.4' => ['text' => 'Jeudi', 'context' => 'Thursday recurrence option.'],
            'checklist.schedule.weekday.5' => ['text' => 'Vendredi', 'context' => 'Friday recurrence option.'],
            'checklist.schedule.weekday.6' => ['text' => 'Samedi', 'context' => 'Saturday recurrence option.'],
            'checklist.schedule.weekday.7' => ['text' => 'Dimanche', 'context' => 'Sunday recurrence option.'],
            'checklist.schedule.quarter.1' => ['text' => 'Premier mois du trimestre', 'context' => 'First month of quarter recurrence.'],
            'checklist.schedule.quarter.2' => ['text' => 'Deuxième mois du trimestre', 'context' => 'Second month of quarter recurrence.'],
            'checklist.schedule.quarter.3' => ['text' => 'Troisième mois du trimestre', 'context' => 'Third month of quarter recurrence.'],
            'checklist.schedule.semester.1' => ['text' => 'Premier mois du semestre', 'context' => 'First month of semester recurrence.'],
            'checklist.schedule.semester.2' => ['text' => 'Deuxième mois du semestre', 'context' => 'Second month of semester recurrence.'],
            'checklist.schedule.semester.3' => ['text' => 'Troisième mois du semestre', 'context' => 'Third month of semester recurrence.'],
            'checklist.schedule.semester.4' => ['text' => 'Quatrième mois du semestre', 'context' => 'Fourth month of semester recurrence.'],
            'checklist.schedule.semester.5' => ['text' => 'Cinquième mois du semestre', 'context' => 'Fifth month of semester recurrence.'],
            'checklist.schedule.semester.6' => ['text' => 'Sixième mois du semestre', 'context' => 'Sixth month of semester recurrence.'],
            'checklist.schedule.month.1' => ['text' => 'Janvier', 'context' => 'January recurrence option.'],
            'checklist.schedule.month.2' => ['text' => 'Février', 'context' => 'February recurrence option.'],
            'checklist.schedule.month.3' => ['text' => 'Mars', 'context' => 'March recurrence option.'],
            'checklist.schedule.month.4' => ['text' => 'Avril', 'context' => 'April recurrence option.'],
            'checklist.schedule.month.5' => ['text' => 'Mai', 'context' => 'May recurrence option.'],
            'checklist.schedule.month.6' => ['text' => 'Juin', 'context' => 'June recurrence option.'],
            'checklist.schedule.month.7' => ['text' => 'Juillet', 'context' => 'July recurrence option.'],
            'checklist.schedule.month.8' => ['text' => 'Août', 'context' => 'August recurrence option.'],
            'checklist.schedule.month.9' => ['text' => 'Septembre', 'context' => 'September recurrence option.'],
            'checklist.schedule.month.10' => ['text' => 'Octobre', 'context' => 'October recurrence option.'],
            'checklist.schedule.month.11' => ['text' => 'Novembre', 'context' => 'November recurrence option.'],
            'checklist.schedule.month.12' => ['text' => 'Décembre', 'context' => 'December recurrence option.'],
            'checklist.detail.items' => ['text' => 'Structure', 'context' => 'Checklist detail item section.'],
            'checklist.detail.trigger' => ['text' => 'Déclenchement', 'context' => 'Checklist detail trigger section.'],
            'checklist.detail.context' => ['text' => 'Contexte', 'context' => 'Checklist detail context section.'],
            'checklist.detail.updated' => ['text' => 'Mise à jour', 'context' => 'Checklist last update label.'],
            'checklist.detail.no_description' => ['text' => 'Aucune description.', 'context' => 'Missing checklist description.'],
            'checklist.detail.root' => ['text' => 'Projet racine', 'context' => 'Checklist root project label.'],
            'checklist.detail.no_delay' => ['text' => 'Sans délai', 'context' => 'No checklist item delay.'],
            'checklist.detail.recurrence' => ['text' => 'Récurrent : {schedule}', 'context' => 'Recurring independent checklist item schedule.'],
            'checklist.detail.display_lead' => ['text' => 'Affiché {delay} avant', 'context' => 'Recurring item display lead time.'],
            'checklist.detail.execution_duration' => ['text' => 'Délai de réalisation : {delay}', 'context' => 'Recurring item execution duration.'],
            'checklist.detail.item_count' => ['one' => '{count} élément', 'other' => '{count} éléments', 'context' => 'Checklist item count.'],
            'checklist.detail.empty_items' => ['text' => 'Cette checklist ne contient encore aucune étape.', 'context' => 'Empty checklist structure message.'],
            'checklist.detail.open_runs' => ['text' => 'Instances en cours', 'context' => 'Checklist open runs section title.'],
            'checklist.detail.open_run_count' => ['one' => '{count} instance en cours', 'other' => '{count} instances en cours', 'context' => 'Checklist open run count.'],
            'checklist.detail.project_instance_count' => ['one' => '{count} projet issu de la checklist', 'other' => '{count} projets issus de la checklist', 'context' => 'Generated project count shown on checklist item bars.'],
            'checklist.detail.project_status' => ['text' => 'Statut : {status}', 'context' => 'Project status in a checklist project bar tooltip.'],
            'checklist.detail.project_status.someday' => ['text' => 'Un jour peut-être', 'context' => 'Someday project status in a checklist project bar tooltip.'],
            'checklist.detail.project_status.ready' => ['text' => 'Prêt', 'context' => 'Ready project status in a checklist project bar tooltip.'],
            'checklist.detail.project_status.in_progress' => ['text' => 'En cours', 'context' => 'In progress project status in a checklist project bar tooltip.'],
            'checklist.detail.project_status.blocked' => ['text' => 'Bloqué', 'context' => 'Blocked project status in a checklist project bar tooltip.'],
            'checklist.detail.project_status.review' => ['text' => 'À vérifier', 'context' => 'Review project status in a checklist project bar tooltip.'],
            'checklist.detail.project_status.done' => ['text' => 'Terminé', 'context' => 'Done project status in a checklist project bar tooltip.'],
            'checklist.detail.empty_runs' => ['text' => 'Aucune instance en cours.', 'context' => 'Checklist open runs empty state.'],
            'checklist.detail.recurring_instance_count' => ['one' => '{count} occurrence active', 'other' => '{count} occurrences actives', 'context' => 'Active recurring checklist project count.'],
            'checklist.detail.recurring_planned_start' => ['text' => 'Planifié le {date}', 'context' => 'Planned start date in a recurring project tooltip.'],
            'checklist.detail.recurring_deadline' => ['text' => 'Date limite {date}', 'context' => 'Deadline date in a recurring project tooltip.'],
            'checklist.detail.overdue' => ['text' => 'En retard', 'context' => 'Overdue project marker in a checklist project bar tooltip.'],
            'checklist.detail.reference_date' => ['text' => 'Référence', 'context' => 'Checklist run reference date label.'],
            'checklist.detail.activated_at' => ['text' => 'Activée le', 'context' => 'Checklist run creation date label.'],
            'checklist.detail.run_item_count' => ['one' => '{count} étape', 'other' => '{count} étapes', 'context' => 'Checklist run item count.'],
            'checklist.run.status.running' => ['text' => 'En cours', 'context' => 'Running checklist instance status.'],
        ];
    }
}

if (!function_exists('omoChecklistLoadTranslationBundle')) {
    function omoChecklistLoadTranslationBundle()
    {
        static $bundle = null;
        if ($bundle === null) {
            $bundle = omoLoadTranslationBundle('omo_checklist', omoChecklistSourceLang());
        }
        return $bundle;
    }
}

if (!function_exists('omoChecklistT')) {
    function omoChecklistT($key, array $replace = [])
    {
        return t($key, $replace, omoChecklistLoadTranslationBundle(), omoChecklistSourceLang());
    }
}

if (!function_exists('omoChecklistResolveContext')) {
    function omoChecklistResolveContext($organizationId, $currentHolonId = 0)
    {
        $organization = new Organization();
        if ((int)$organizationId <= 0 || !$organization->load((int)$organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoChecklistT('checklist.error.organization')];
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $currentHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ((int)$currentHolonId > 0) {
            $candidate = new Holon();
            if (
                !$candidate->load((int)$currentHolonId)
                || !($rootHolon instanceof Holon)
                || !$candidate->isDescendantOf((int)$rootHolon->getId(), true)
                || !$candidate->canViewDetail()
            ) {
                return ['status' => false, 'message' => omoChecklistT('checklist.error.context')];
            }
            $currentHolon = $candidate;
        }

        return [
            'status' => true,
            'organization' => $organization,
            'rootHolon' => $rootHolon instanceof Holon ? $rootHolon : null,
            'currentHolon' => $currentHolon,
        ];
    }
}

if (!function_exists('omoChecklistCanCreateContext')) {
    function omoChecklistCanCreateContext(array $context)
    {
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        $currentHolon = $context['currentHolon'] ?? null;
        if ($currentUserId <= 0 || !($currentHolon instanceof Holon)) {
            return false;
        }
        $useSessionCache = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST';
        return $currentHolon->isAllowed('CAN_CREATE_PROJECT', $useSessionCache, $currentUserId);
    }
}

if (!function_exists('omoChecklistCanView')) {
    function omoChecklistCanView(Checklist $checklist)
    {
        $templateRoot = $checklist->getTemplateRoot();
        $holon = $templateRoot instanceof Project ? $templateRoot->getHolon() : null;
        return $holon instanceof Holon && $holon->canViewDetail();
    }
}

if (!function_exists('omoChecklistCanManage')) {
    function omoChecklistCanManage(Checklist $checklist)
    {
        $templateRoot = $checklist->getTemplateRoot();
        $holon = $templateRoot instanceof Project ? $templateRoot->getHolon() : null;
        return $holon instanceof Holon && $holon->canEdit();
    }
}

if (!function_exists('omoChecklistCanActivate')) {
    function omoChecklistCanActivate(Checklist $checklist, $trigger = null)
    {
        if (Checklist::normalizeStatus($checklist->get('status')) !== Checklist::STATUS_PUBLISHED) {
            return false;
        }
        $trigger = $trigger instanceof ChecklistTrigger ? $trigger : omoChecklistGetPrimaryTrigger($checklist);
        if (
            !($trigger instanceof ChecklistTrigger)
            || ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type')) !== ChecklistTrigger::TYPE_MANUAL
            || (int)$trigger->get('enabled') !== 1
        ) {
            return false;
        }
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        $holon = $checklist->getHolon();
        if ($currentUserId <= 0 || !($holon instanceof Holon) || !$holon->canViewDetail()) {
            return false;
        }
        $useSessionCache = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST';
        return $holon->isAllowed('CAN_CREATE_PROJECT', $useSessionCache, $currentUserId);
    }
}

if (!function_exists('omoChecklistLoad')) {
    function omoChecklistLoad($checklistId, $organizationId)
    {
        $checklist = new Checklist();
        return (int)$checklistId > 0
            && $checklist->load((int)$checklistId)
            && (int)$checklist->get('IDorganization') === (int)$organizationId
            && (int)$checklist->get('active') === 1
                ? $checklist
                : null;
    }
}

if (!function_exists('omoChecklistBuildHolonOptions')) {
    function omoChecklistBuildHolonOptions(array $context)
    {
        $rootHolon = $context['rootHolon'] ?? null;
        if (!($rootHolon instanceof Holon)) {
            return [];
        }

        $options = [];
        $visited = [];
        $append = static function (Holon $holon, array $path = []) use (&$append, &$options, &$visited, $rootHolon) {
            $holonId = (int)$holon->getId();
            if ($holonId <= 0 || isset($visited[$holonId])) {
                return;
            }
            $visited[$holonId] = true;
            $label = trim((string)$holon->getDisplayName());
            $nextPath = $path;
            if ($label !== '') {
                $nextPath[] = $label;
            }
            if ($holon->canViewDetail() && omoApiIsStructuralScopeHolon($holon, $rootHolon)) {
                $options[] = [
                    'id' => $holonId,
                    'label' => implode(' › ', $nextPath),
                ];
            }
            foreach ($holon->getChildren() as $child) {
                if ($child instanceof Holon && omoApiIsStructuralScopeHolon($child, $rootHolon)) {
                    $append($child, $nextPath);
                }
            }
        };
        $append($rootHolon);
        return $options;
    }
}

if (!function_exists('omoChecklistGetPrimaryTrigger')) {
    function omoChecklistGetPrimaryTrigger(Checklist $checklist)
    {
        foreach ($checklist->getTriggers(false) as $trigger) {
            if ($trigger instanceof ChecklistTrigger) {
                return $trigger;
            }
        }
        return null;
    }
}

if (!function_exists('omoChecklistStatusLabel')) {
    function omoChecklistStatusLabel($status)
    {
        return omoChecklistT('checklist.status.' . Checklist::normalizeStatus($status));
    }
}

if (!function_exists('omoChecklistActivationLabel')) {
    function omoChecklistActivationLabel($activationType)
    {
        return omoChecklistT('checklist.activation.' . ChecklistItem::normalizeActivationType($activationType));
    }
}

if (!function_exists('omoChecklistFrequencyLabel')) {
    function omoChecklistFrequencyLabel($frequency)
    {
        $frequency = RecurrenceSchedule::normalizeFrequency($frequency);
        return $frequency === null ? '' : omoChecklistT('checklist.frequency.' . $frequency);
    }
}

if (!function_exists('omoChecklistScheduleOptions')) {
    function omoChecklistScheduleOptions($frequency)
    {
        $frequency = RecurrenceSchedule::normalizeFrequency($frequency);
        if ($frequency === null) {
            return [];
        }
        $options = [['value' => '', 'label' => omoChecklistT('checklist.schedule.none')]];
        if ($frequency === RecurrenceSchedule::FREQUENCY_DAILY) {
            for ($hour = 0; $hour < 24; $hour++) {
                $time = str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00';
                $options[] = ['value' => $time, 'label' => $time];
            }
            return $options;
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_WEEKLY) {
            for ($day = 1; $day <= 7; $day++) {
                $options[] = ['value' => (string)$day, 'label' => omoChecklistT('checklist.schedule.weekday.' . $day)];
            }
            return $options;
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_MONTHLY) {
            for ($day = 1; $day <= 31; $day++) {
                $options[] = ['value' => (string)$day, 'label' => omoChecklistT('checklist.schedule.month_day', ['day' => $day])];
            }
            return $options;
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_QUARTERLY) {
            for ($month = 1; $month <= 3; $month++) {
                $options[] = ['value' => (string)$month, 'label' => omoChecklistT('checklist.schedule.quarter.' . $month)];
            }
            return $options;
        }
        if ($frequency === RecurrenceSchedule::FREQUENCY_SEMIANNUAL) {
            for ($month = 1; $month <= 6; $month++) {
                $options[] = ['value' => (string)$month, 'label' => omoChecklistT('checklist.schedule.semester.' . $month)];
            }
            return $options;
        }
        for ($month = 1; $month <= 12; $month++) {
            $options[] = ['value' => (string)$month, 'label' => omoChecklistT('checklist.schedule.month.' . $month)];
        }
        return $options;
    }
}

if (!function_exists('omoChecklistScheduleLabel')) {
    function omoChecklistScheduleLabel($frequency, $schedule)
    {
        $normalized = RecurrenceSchedule::normalizeSchedule($frequency, $schedule);
        foreach (omoChecklistScheduleOptions($frequency) as $option) {
            if ((string)$option['value'] === (string)$normalized) {
                return (string)$option['label'];
            }
        }
        return '';
    }
}

if (!function_exists('omoChecklistTriggerLabel')) {
    function omoChecklistTriggerLabel($trigger)
    {
        if (!($trigger instanceof ChecklistTrigger)) {
            return omoChecklistT('checklist.trigger.manual');
        }
        $triggerType = ChecklistTrigger::normalizeTriggerType($trigger->get('trigger_type'));
        if ($triggerType === ChecklistTrigger::TYPE_CONTAINER) {
            return omoChecklistT('checklist.trigger.container');
        }
        if ($triggerType === ChecklistTrigger::TYPE_MANUAL) {
            return omoChecklistT('checklist.trigger.manual');
        }
        $frequencyLabel = omoChecklistFrequencyLabel($trigger->get('frequency'));
        $scheduleLabel = omoChecklistScheduleLabel($trigger->get('frequency'), $trigger->get('schedule'));
        return trim($frequencyLabel . ($scheduleLabel !== '' ? ' · ' . $scheduleLabel : ''));
    }
}
