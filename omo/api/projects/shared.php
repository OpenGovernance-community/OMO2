<?php

use dbObject\Holon;
use dbObject\Organization;
use dbObject\ArrayProjectDocument;
use dbObject\Project;

if (!function_exists('omoProjectsSourceLang')) {
    function omoProjectsSourceLang()
    {
        return [
            'projects.title' => ['text' => 'Projets', 'context' => 'Main title of the projects application.'],
            'projects.action.new' => ['text' => 'Nouveau projet', 'context' => 'Primary action opening project creation.'],
            'projects.action.edit' => ['text' => 'Modifier', 'context' => 'Button opening project edition from the detail header.'],
            'projects.action.close' => ['text' => 'Fermer', 'context' => 'Button closing the project subdrawer.'],
            'projects.action.save' => ['text' => 'Enregistrer', 'context' => 'Submit action saving a project.'],
            'projects.action.cancel' => ['text' => 'Annuler', 'context' => 'Button cancelling project creation.'],
            'projects.action.attach' => ['text' => 'Attacher un projet', 'context' => 'Button attaching an existing orphan project as a subproject.'],
            'projects.action.move' => ['text' => 'Déplacer', 'context' => 'Context menu action moving a project to another holon.'],
            'projects.action.archive' => ['text' => 'Archiver', 'context' => 'Context menu action archiving a project.'],
            'projects.action.delete' => ['text' => 'Supprimer', 'context' => 'Context menu action permanently deleting a project.'],
            'projects.action.archive_selected' => ['text' => 'Archiver la sélection', 'context' => 'Bulk action archiving selected projects.'],
            'projects.action.delete_selected' => ['text' => 'Supprimer la sélection', 'context' => 'Bulk action permanently deleting selected projects.'],
            'projects.selection.toggle' => ['text' => 'Selectionner ce projet', 'context' => 'Accessible label for a project selection checkbox.'],
            'projects.selection.count' => ['text' => '{count} sélectionnés', 'context' => 'Count of projects selected for a bulk action.'],
            'projects.scope.contextual' => ['text' => 'Local', 'context' => 'Scope showing projects attached to the current holon.'],
            'projects.scope.children' => ['text' => 'Enfants directs', 'context' => 'Scope showing projects attached to the current holon and its direct children.'],
            'projects.scope.descendants' => ['text' => 'Descendants', 'context' => 'Scope showing projects attached to the current holon and its descendants.'],
            'projects.assignment.aria' => ['text' => 'Projets affichés', 'context' => 'Accessible label for the project assignment filter.'],
            'projects.assignment.mine' => ['text' => 'Moi', 'context' => 'Project assignment filter showing projects assigned to the current user.'],
            'projects.assignment.everyone' => ['text' => 'Tout le monde', 'context' => 'Project assignment filter showing projects assigned to anyone.'],
            'projects.filters.aria' => ['text' => 'Filtres des projets', 'context' => 'Accessible label for the compact project filters control.'],
            'projects.filters.scope' => ['text' => 'Contexte', 'context' => 'Heading for the project scope choices in the filters panel.'],
            'projects.filters.assignment' => ['text' => 'Attribution', 'context' => 'Heading for the project assignment choices in the filters panel.'],
            'projects.filters.sort' => ['text' => 'Ordre', 'context' => 'Heading for the project sorting choices in the filters panel.'],
            'projects.filters.view' => ['text' => 'Représentation', 'context' => 'Heading for the project view choices in the filters panel.'],
            'projects.filters.apply' => ['text' => 'Appliquer', 'context' => 'Button applying temporary project filter choices without saving them.'],
            'projects.filters.save_view' => ['text' => 'Enregistrer la vue', 'context' => 'Button applying and saving the project filter choices for the current holon.'],
            'projects.filters.more_actions' => ['text' => 'Autres options de vue', 'context' => 'Accessible label for additional project view preference actions.'],
            'projects.filters.apply_everywhere' => ['text' => 'Appliquer partout', 'context' => 'Action setting the current project view as the default and clearing specific views.'],
            'projects.filters.set_default' => ['text' => 'Définir comme vue par défaut', 'context' => 'Action saving the current project view as the default view.'],
            'projects.filters.restore_default' => ['text' => 'Restaurer la vue par défaut', 'context' => 'Action removing the current holon specific project view.'],
            'projects.search.aria' => ['text' => 'Filtrer les projets affichés', 'context' => 'Accessible label for the project quick search input.'],
            'projects.search.placeholder' => ['text' => 'Filtrer les projets', 'context' => 'Placeholder for the project quick search input.'],
            'projects.search.empty' => ['text' => 'Aucun projet ne correspond à cette recherche.', 'context' => 'Empty state when the project quick search hides every displayed project.'],
            'projects.view.aria' => ['text' => "Mode d'affichage", 'context' => 'Accessible label for the project display mode selector.'],
            'projects.view.kanban' => ['text' => 'Kanban', 'context' => 'Project display mode button.'],
            'projects.view.list' => ['text' => 'Liste', 'context' => 'Project display mode button.'],
            'projects.view.gantt' => ['text' => 'Gantt', 'context' => 'Project display mode button.'],
            'projects.gantt.no_dates' => ['text' => 'Sans dates', 'context' => 'Project without effective planning dates in the Gantt view.'],
            'projects.gantt.inherited' => ['text' => 'hérité', 'context' => 'Label showing that a Gantt planning date comes from a parent project.'],
            'projects.gantt.overdue' => ['text' => 'En retard', 'context' => 'Label for an unfinished project whose planned end date has passed.'],
            'projects.sort.aria' => ['text' => 'Classer les projets', 'context' => 'Accessible label for the project list sort selector.'],
            'projects.sort.planned' => ['text' => 'Planification', 'context' => 'Project list sort button.'],
            'projects.sort.priority' => ['text' => 'Priorité', 'context' => 'Project list sort button.'],
            'projects.sort.importance' => ['text' => 'Importance stratégique', 'context' => 'Project list sort button.'],
            'projects.sort.holon' => ['text' => 'Holon', 'context' => 'Project list sort button.'],
            'projects.list.planned.overdue' => ['text' => 'En retard', 'context' => 'Project list planned group for past dates.'],
            'projects.list.planned.in_progress' => ['text' => 'En cours', 'context' => 'Project list planned group for projects currently within their planned dates.'],
            'projects.list.planned.tomorrow' => ['text' => 'Demain', 'context' => 'Project list planned group for tomorrow.'],
            'projects.list.planned.after_tomorrow' => ['text' => 'Après-demain', 'context' => 'Project list planned group for the day after tomorrow.'],
            'projects.list.planned.this_week' => ['text' => 'Cette semaine', 'context' => 'Project list planned group for the rest of this week.'],
            'projects.list.planned.next_week' => ['text' => 'La semaine prochaine', 'context' => 'Project list planned group for next week.'],
            'projects.list.planned.later' => ['text' => 'Plus tard', 'context' => 'Project list planned group for future dates.'],
            'projects.list.planned.none' => ['text' => 'Sans planification', 'context' => 'Project list planned group without dates.'],
            'projects.list.done' => ['text' => 'Terminés', 'context' => 'Final project list group containing completed projects.'],
            'projects.list.priority.none' => ['text' => 'Sans priorité', 'context' => 'Project list priority group without priority.'],
            'projects.empty.contextual' => ['text' => 'Aucun projet dans ce contexte.', 'context' => 'Empty state for the local project scope.'],
            'projects.empty.children' => ['text' => 'Aucun projet dans ce contexte ou ses enfants directs.', 'context' => 'Empty state for the direct child holon scope.'],
            'projects.empty.descendants' => ['text' => 'Aucun projet dans ce contexte ou ses descendants.', 'context' => 'Empty state for the descendant project scope.'],
            'projects.empty.mine' => ['text' => 'Aucun projet qui vous est attribué dans ce périmètre.', 'context' => 'Empty state when the current user has no assigned project in the selected scope.'],
            'projects.empty.column' => ['text' => 'Aucun projet dans cette colonne.', 'context' => 'Empty state for one empty Kanban column.'],
            'projects.loading' => ['text' => 'Chargement du projet…', 'context' => 'Loading message shown inside the project subdrawer.'],
            'projects.loading_error' => ['text' => 'Impossible de charger ce projet.', 'context' => 'Error shown when a project drawer cannot be loaded.'],
            'projects.status_update_error' => ['text' => 'Impossible de changer le statut.', 'context' => 'Fallback error shown when a project status cannot be changed.'],
            'projects.action_error' => ['text' => 'Impossible de mettre à jour le projet.', 'context' => 'Fallback error shown for a project context action.'],
            'projects.delete.confirm' => ['text' => 'Supprimer définitivement ce projet et ses {count} sous-projets ? Cette action est irréversible.', 'context' => 'Confirmation before permanent project deletion.'],
            'projects.archive.confirm' => ['text' => "Ce projet n'est pas terminé. L'archiver quand même ?", 'context' => 'Confirmation before archiving an unfinished project.'],
            'projects.archive.confirm_selected' => ['text' => 'Archiver les {count} projets sélectionnés et leurs sous-projets ?', 'context' => 'Confirmation before bulk project archiving.'],
            'projects.delete.confirm_selected' => ['text' => 'Supprimer définitivement les {count} projets sélectionnés et leurs sous-projets ? Cette action est irréversible.', 'context' => 'Confirmation before bulk project deletion.'],
            'projects.move.title' => ['text' => 'Déplacer le projet', 'context' => 'Title of the project holon move dialog.'],
            'projects.move.hint' => ['text' => 'Choisissez le holon de destination dans la structure.', 'context' => 'Instruction in the project holon move dialog.'],
            'projects.move.submit' => ['text' => 'Déplacer ici', 'context' => 'Submit button in the project holon move dialog.'],
            'projects.move.select_required' => ['text' => 'Choisissez un holon de destination.', 'context' => 'Validation message when no target holon is selected.'],
            'projects.column.previous' => ['text' => 'Colonne précédente', 'context' => 'Accessible label for the previous mobile Kanban column button.'],
            'projects.column.next' => ['text' => 'Colonne suivante', 'context' => 'Accessible label for the next mobile Kanban column button.'],
            'projects.error.organization' => ['text' => 'Organisation invalide ou inaccessible.', 'context' => 'Error for an invalid organization context.'],
            'projects.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Error for an invalid holon context.'],
            'projects.error.not_found' => ['text' => 'Projet introuvable.', 'context' => 'Error when a project cannot be loaded.'],
            'projects.error.forbidden' => ['text' => 'Vous ne pouvez pas modifier ce projet.', 'context' => 'Error when project mutation is forbidden.'],
            'projects.error.method' => ['text' => 'Cette action doit être envoyée en POST.', 'context' => 'Error for a mutation sent with the wrong HTTP method.'],
            'projects.error.action' => ['text' => 'Action inconnue.', 'context' => 'Error for an unsupported project action.'],
            'projects.error.title' => ['text' => 'Le titre est obligatoire.', 'context' => 'Validation error for a missing project title.'],
            'projects.error.status' => ['text' => 'Le statut du projet est invalide.', 'context' => 'Validation error for an invalid project status.'],
            'projects.error.dates' => ['text' => 'La date de fin doit être postérieure ou égale à la date de début.', 'context' => 'Validation error when the planned end date precedes the planned start date.'],
            'projects.error.parent_someday' => ['text' => 'Un sous-projet dont le parent a une date de fin ne peut pas être placé dans « Un jour peut-être ».', 'context' => 'Validation error when a dated parent project has a someday subproject.'],
            'projects.error.parent_end_date' => ['text' => 'La date de fin du sous-projet ne peut pas dépasser celle du projet parent.', 'context' => 'Validation error when a subproject end date exceeds its parent end date.'],
            'projects.error.save' => ['text' => "Impossible d'enregistrer le projet.", 'context' => 'Generic project persistence error.'],
            'projects.error.holon' => ['text' => 'Le holon de destination est invalide ou inaccessible.', 'context' => 'Error for an invalid project move target holon.'],
            'projects.success.save' => ['text' => 'Projet enregistré.', 'context' => 'Success message after project creation.'],
            'projects.success.status' => ['text' => 'Statut mis à jour.', 'context' => 'Success message after changing project status.'],
            'projects.drawer.title' => ['text' => 'Projet', 'context' => 'Default title of the project subdrawer.'],
            'projects.drawer.description' => ['text' => 'Détails et informations du projet.', 'context' => 'Default description of the project subdrawer.'],
            'projects.detail.badge' => ['text' => 'Projet', 'context' => 'Eyebrow label shown above the project detail title.'],
            'projects.detail.breadcrumb' => ['text' => 'Projets parents', 'context' => 'Accessible label for the project parent breadcrumb.'],
            'projects.detail.breadcrumb.expand' => ['text' => 'Afficher tous les projets parents', 'context' => 'Accessible title for the collapsed project breadcrumb button.'],
            'projects.detail.description' => ['text' => 'Description', 'context' => 'Project detail description section label.'],
            'projects.detail.context' => ['text' => 'Contexte', 'context' => 'Project detail holon section label.'],
            'projects.detail.schedule' => ['text' => 'Dates planifiées', 'context' => 'Project detail planned dates section label.'],
            'projects.detail.organisation' => ['text' => 'Organisation', 'context' => 'Project detail organization label.'],
            'projects.detail.responsible' => ['text' => 'Responsable', 'context' => 'Project detail responsible person label.'],
            'projects.detail.status' => ['text' => 'Statut', 'context' => 'Project detail status label.'],
            'projects.detail.priority' => ['text' => 'Priorité', 'context' => 'Project detail priority label.'],
            'projects.detail.importance' => ['text' => 'Importance stratégique', 'context' => 'Project detail importance label.'],
            'projects.detail.calculated_importance' => ['text' => 'Importance stratégique calculée', 'context' => 'Server-calculated project importance label.'],
            'projects.detail.calculated_importance_help' => ['text' => "Calculée à partir de l'importance stratégique déclarée, de la chaîne de projets et de la position holarchique.", 'context' => 'Help text for server-calculated project importance.'],
            'projects.detail.size' => ['text' => 'Taille', 'context' => 'Project detail project size label.'],
            'projects.detail.parent' => ['text' => 'Projet parent', 'context' => 'Project detail parent label.'],
            'projects.detail.subprojects' => ['text' => 'Sous-projets', 'context' => 'Project detail subprojects section label.'],
            'projects.detail.subprojects_new' => ['text' => 'Nouveau', 'context' => 'Button creating a new subproject from a project detail.'],
            'projects.detail.subprojects_empty' => ['text' => 'Aucun sous-projet pour le moment.', 'context' => 'Empty state shown in the project detail subprojects section.'],
            'projects.detail.tabs.label' => ['text' => 'Sections du projet', 'context' => 'Accessible label for the project detail tabs.'],
            'projects.detail.tabs.information' => ['text' => 'Informations', 'context' => 'Project detail tab containing the project information.'],
            'projects.detail.tabs.events' => ['text' => 'Événements associés', 'context' => 'Project detail tab reserved for events associated with the project.'],
            'projects.detail.events.empty' => ['text' => 'Aucun événement planifié', 'context' => 'Empty state for a project without associated events.'],
            'projects.detail.events.empty_hint' => ['text' => 'Planifiez une séance de travail, un atelier ou un brainstorming pour ce projet.', 'context' => 'Explanation shown in the empty project events tab.'],
            'projects.detail.events.new' => ['text' => 'Créer un événement', 'context' => 'Action opening the event editor for a project.'],
            'projects.detail.events.loading' => ['text' => 'Chargement des événements…', 'context' => 'Loading state for lazy project events.'],
            'projects.detail.events.error' => ['text' => 'Impossible de charger les événements du projet.', 'context' => 'Error state for lazy project events.'],
            'projects.detail.events.all_day' => ['text' => 'Toute la journée', 'context' => 'Time label for an all-day project event.'],
            'projects.detail.events.section.today' => ['text' => "Aujourd'hui", 'context' => 'Project events section for events happening today.'],
            'projects.detail.events.section.tomorrow' => ['text' => 'Demain', 'context' => 'Project events section for events happening tomorrow.'],
            'projects.detail.events.section.this_week' => ['text' => 'Cette semaine', 'context' => 'Project events section for events happening later this week.'],
            'projects.detail.events.section.next_week' => ['text' => 'La semaine prochaine', 'context' => 'Project events section for events happening next week.'],
            'projects.detail.events.section.this_month' => ['text' => 'Ce mois', 'context' => 'Project events section for events happening later this month.'],
            'projects.detail.events.section.next_month' => ['text' => 'Le mois prochain', 'context' => 'Project events section for events happening next month.'],
            'projects.detail.events.month.1' => ['text' => 'Janvier', 'context' => 'Project events section label for January.'],
            'projects.detail.events.month.2' => ['text' => 'Février', 'context' => 'Project events section label for February.'],
            'projects.detail.events.month.3' => ['text' => 'Mars', 'context' => 'Project events section label for March.'],
            'projects.detail.events.month.4' => ['text' => 'Avril', 'context' => 'Project events section label for April.'],
            'projects.detail.events.month.5' => ['text' => 'Mai', 'context' => 'Project events section label for May.'],
            'projects.detail.events.month.6' => ['text' => 'Juin', 'context' => 'Project events section label for June.'],
            'projects.detail.events.month.7' => ['text' => 'Juillet', 'context' => 'Project events section label for July.'],
            'projects.detail.events.month.8' => ['text' => 'Août', 'context' => 'Project events section label for August.'],
            'projects.detail.events.month.9' => ['text' => 'Septembre', 'context' => 'Project events section label for September.'],
            'projects.detail.events.month.10' => ['text' => 'Octobre', 'context' => 'Project events section label for October.'],
            'projects.detail.events.month.11' => ['text' => 'Novembre', 'context' => 'Project events section label for November.'],
            'projects.detail.events.month.12' => ['text' => 'Décembre', 'context' => 'Project events section label for December.'],
            'projects.detail.tabs.documents' => ['text' => 'Documents associés', 'context' => 'Project detail tab containing documents attached to the project.'],
            'projects.detail.documents.empty' => ['text' => 'Aucun fichier à afficher', 'context' => 'Empty state for a project without attached documents.'],
            'projects.detail.documents.empty_hint' => ['text' => 'Créez un premier fichier pour le retrouver directement dans ce projet.', 'context' => 'Explanation shown in the empty project documents tab.'],
            'projects.detail.documents.new' => ['text' => 'Nouveau fichier', 'context' => 'Action opening the project document creation drawer.'],
            'projects.detail.documents.drawer_title' => ['text' => 'Nouveau fichier', 'context' => 'Title of the document creation subdrawer opened from a project.'],
            'projects.detail.documents.drawer_description' => ['text' => 'Ajoutez un fichier associé à ce projet.', 'context' => 'Description of the document creation subdrawer opened from a project.'],
            'projects.detail.documents.add' => ['text' => 'Ajouter un fichier', 'context' => 'Action opening the documents application from an empty project document list.'],
            'projects.detail.documents.added' => ['text' => 'Ajouté le {date}', 'context' => 'Date label shown for a document attached to a project.'],
            'projects.detail.documents.loading' => ['text' => 'Chargement des documents…', 'context' => 'Loading state for lazy project documents.'],
            'projects.detail.documents.error' => ['text' => 'Impossible de charger les documents du projet.', 'context' => 'Error state for lazy project documents.'],
            'projects.detail.task.archive' => ['text' => 'Archiver', 'context' => 'Task action in the project detail status selector.'],
            'projects.detail.task.delete' => ['text' => 'Supprimer', 'context' => 'Task action in the project detail status selector.'],
            'projects.detail.task.delete_confirm' => ['text' => 'Supprimer définitivement cette tâche ? Cette action est irréversible.', 'context' => 'Confirmation before permanently deleting a task from the project detail.'],
            'projects.detail.archives.link' => ['text' => 'Voir les archives', 'context' => 'Text link opening archived subprojects.'],
            'projects.detail.archives.title' => ['text' => 'Projets archivés', 'context' => 'Title of the archived subprojects popup.'],
            'projects.detail.archives.empty' => ['text' => 'Aucun projet archivé.', 'context' => 'Empty state for archived subprojects.'],
            'projects.detail.created' => ['text' => 'Créé le', 'context' => 'Project detail creation date label.'],
            'projects.detail.empty_description' => ['text' => 'Aucune description pour ce projet.', 'context' => 'Fallback when the project has no description.'],
            'projects.detail.none' => ['text' => 'Non renseigné', 'context' => 'Fallback for missing project metadata.'],
            'projects.detail.date_start' => ['text' => 'Début', 'context' => 'Planned start date label.'],
            'projects.detail.date_end' => ['text' => 'Fin', 'context' => 'Planned end date label.'],
            'projects.subprojects.label' => ['text' => 'État des sous-projets', 'context' => 'Accessible label for the recursive subproject status bar.'],
            'projects.detail.priority_level' => ['one' => 'P{count}', 'other' => 'P{count}', 'context' => 'Project priority level.'],
            'projects.detail.importance_level' => ['one' => '{count}/5', 'other' => '{count}/5', 'context' => 'Project importance level.'],
            'projects.form.title' => ['text' => 'Nouveau projet', 'context' => 'Project creation form title.'],
            'projects.form.description' => ['text' => "Définissez le but, les dates et le niveau d'attention du projet.", 'context' => 'Project creation form introduction.'],
            'projects.form.submit' => ['text' => 'Créer le projet', 'context' => 'Submit button creating a project.'],
            'projects.form.edit_title' => ['text' => 'Modifier le projet', 'context' => 'Project edition form title.'],
            'projects.form.edit_description' => ['text' => 'Mettez à jour le but, les dates et les paramètres du projet.', 'context' => 'Project edition form introduction.'],
            'projects.form.edit_submit' => ['text' => 'Enregistrer les modifications', 'context' => 'Submit button saving project changes.'],
            'projects.form.description_field' => ['text' => 'Description HTML simple', 'context' => 'Label for the project HTML description editor.'],
            'projects.form.assignment' => ['text' => 'Responsabilité et hiérarchie', 'context' => 'Section title grouping the responsible person and parent project in the project form.'],
            'projects.form.planning' => ['text' => 'Planification', 'context' => 'Section title grouping status and planned dates in the project form.'],
            'projects.form.attention' => ['text' => "Niveau d'attention", 'context' => 'Section title grouping priority and importance controls in the project form.'],
            'projects.form.more_options' => ['text' => 'Options supplémentaires', 'context' => 'Collapsed project form section title for secondary settings.'],
            'projects.form.more_options_toggle' => ['text' => 'Afficher ou masquer les options supplémentaires', 'context' => 'Accessible label for the secondary project form options accordion.'],
            'projects.status.someday' => ['text' => 'Un jour peut-être', 'context' => 'Project status label.'],
            'projects.status.ready' => ['text' => 'Prêt', 'context' => 'Project status label.'],
            'projects.status.in_progress' => ['text' => 'En cours', 'context' => 'Project status label.'],
            'projects.status.blocked' => ['text' => 'Bloqué', 'context' => 'Project status label.'],
            'projects.status.review' => ['text' => 'À vérifier', 'context' => 'Project status label.'],
            'projects.status.done' => ['text' => 'Terminé', 'context' => 'Project status label.'],
            'projects.status_move' => ['text' => 'Changer le statut', 'context' => 'Accessible label for the project status move control.'],
            'projects.priority.none' => ['text' => 'Non définie', 'context' => 'Empty option for project priority.'],
            'projects.importance.none' => ['text' => 'Non définie', 'context' => 'Empty option for project importance.'],
            'projects.field.priority' => ['text' => 'Priorité', 'context' => 'Project creation priority field label.'],
            'projects.field.importance' => ['text' => 'Importance stratégique', 'context' => 'Project creation importance field label.'],
            'projects.field.status' => ['text' => 'Statut initial', 'context' => 'Project creation status field label.'],
            'projects.field.start_date' => ['text' => 'Début planifié', 'context' => 'Project planned start date field label.'],
            'projects.field.end_date' => ['text' => 'Fin planifiée', 'context' => 'Project planned end date field label.'],
            'projects.field.parent' => ['text' => 'Projet parent', 'context' => 'Project parent field label.'],
            'projects.field.holon' => ['text' => 'Cercle ou rôle associé', 'context' => 'Project assignment holon field label.'],
            'projects.field.responsible' => ['text' => 'Responsable', 'context' => 'Project responsible user field label.'],
            'projects.field.title' => ['text' => 'Titre du projet', 'context' => 'Project title field label.'],
            'projects.field.description' => ['text' => 'Description', 'context' => 'Project description field label.'],
            'projects.field.description_placeholder' => ['text' => 'Quel résultat voulez-vous obtenir ?', 'context' => 'Placeholder for the project description field.'],
            'projects.field.size' => ['text' => 'Taille', 'context' => 'Project size field label.'],
            'projects.field.capture_mode' => ['text' => 'Mode de capture Telegram', 'context' => 'Project Telegram capture mode field label.'],
            'projects.capture_mode.multiple_documents' => ['text' => 'Documents multiples', 'context' => 'Project Telegram capture mode option.'],
            'projects.capture_mode.single_journal' => ['text' => 'Journal unique', 'context' => 'Project Telegram capture mode option.'],
            'projects.responsible.none' => ['text' => 'Aucun responsable', 'context' => 'Empty responsible person option in the project form.'],
            'projects.responsible.help' => ['text' => 'Seules les personnes actives de cette organisation sont proposées.', 'context' => 'Help text below the responsible person selector in the project form.'],
            'projects.parent.none' => ['text' => 'Aucun projet parent', 'context' => 'Empty parent project value in the project form.'],
            'projects.parent.choose' => ['text' => 'Choisir un projet', 'context' => 'Button opening the parent project picker in the project form.'],
            'projects.parent_picker.title' => ['text' => 'Choisir le projet parent', 'context' => 'Modal title for selecting a parent project.'],
            'projects.parent_picker.search' => ['text' => 'Rechercher un projet', 'context' => 'Search placeholder in the parent project picker modal.'],
            'projects.parent_picker.empty' => ['text' => 'Aucun projet ne correspond à la recherche.', 'context' => 'Empty state in the parent project picker modal.'],
            'projects.parent_picker.none' => ['text' => 'Sans projet parent', 'context' => 'Empty option in the parent project picker modal.'],
            'projects.parent_picker.choose' => ['text' => 'Utiliser ce projet', 'context' => 'Confirmation button in the parent project picker modal.'],
            'projects.parent_picker.scope_local' => ['text' => 'Local', 'context' => 'Local scope label in the parent project picker structure navigation.'],
            'projects.parent_picker.scope_children' => ['text' => 'Enfants directs', 'context' => 'Direct child scope label in the parent project picker structure navigation.'],
            'projects.parent_picker.scope_descendants' => ['text' => 'Descendants', 'context' => 'Descendant scope label in the parent project picker structure navigation.'],
            'projects.holon.choose' => ['text' => 'Choisir un cercle ou rôle', 'context' => 'Button opening the project holon picker.'],
            'projects.holon_picker.title' => ['text' => 'Choisir le cercle ou rôle', 'context' => 'Modal title for selecting the project assignment holon.'],
            'projects.holon_picker.hint' => ['text' => 'Choisissez le cercle ou le rôle auquel confier ce projet.', 'context' => 'Instruction in the project holon picker modal.'],
            'projects.holon_picker.confirm' => ['text' => 'Utiliser ce contexte', 'context' => 'Confirmation button in the project holon picker modal.'],
            'projects.attach.title' => ['text' => 'Attacher un projet', 'context' => 'Modal title for attaching an orphan project as a subproject.'],
            'projects.attach.hint' => ['text' => 'Choisissez un projet sans parent dans la structure.', 'context' => 'Instruction in the attach existing project modal.'],
            'projects.attach.search' => ['text' => 'Rechercher un projet', 'context' => 'Search placeholder in the attach existing project modal.'],
            'projects.attach.empty' => ['text' => 'Aucun projet sans parent ne correspond à la recherche.', 'context' => 'Empty state in the attach existing project modal.'],
            'projects.attach.submit' => ['text' => 'Attacher', 'context' => 'Submit button attaching the selected project.'],
            'projects.attach.select_required' => ['text' => 'Choisissez un projet à attacher.', 'context' => 'Validation message when no project is selected for attachment.'],
            'projects.children.show_subprojects' => ['text' => 'Afficher les sous-projets de {title}', 'context' => 'Accessible label for expanding a project child list.'],
            'projects.level.none' => ['text' => 'Non définie', 'context' => 'Zero level label for priority and importance range controls.'],
        ];
    }
}

if (!function_exists('omoProjectsLoadTranslationBundle')) {
    function omoProjectsLoadTranslationBundle()
    {
        static $bundle = null;
        if ($bundle === null) {
            $sourceLang = omoProjectsSourceLang();
            $bundle = omoLoadTranslationBundle('omo_projects', $sourceLang);
        }
        return $bundle;
    }
}

if (!function_exists('omoProjectsT')) {
    function omoProjectsT($key, array $replace = [])
    {
        $sourceLang = omoProjectsSourceLang();
        return t($key, $replace, omoProjectsLoadTranslationBundle(), $sourceLang);
    }
}

if (!function_exists('omoProjectsResolveContext')) {
    function omoProjectsResolveContext($organizationId, $currentHolonId = 0)
    {
        $organizationId = (int)$organizationId;
        $currentHolonId = (int)$currentHolonId;
        $organization = new Organization();

        if ($organizationId <= 0 || !$organization->load($organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoProjectsT('projects.error.organization')];
        }

        \dbObject\ProjectImportanceCalculator::ensureOrganizationInitialized($organizationId);

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $currentHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ($currentHolonId > 0) {
            $candidate = new Holon();
            if (
                !$candidate->load($currentHolonId)
                || !($rootHolon instanceof Holon)
                || !$candidate->isDescendantOf((int)$rootHolon->getId(), true)
                || !$candidate->canViewDetail()
            ) {
                return ['status' => false, 'message' => omoProjectsT('projects.error.context')];
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

if (!function_exists('omoProjectsCanManageContext')) {
    function omoProjectsCanManageContext(array $context)
    {
        $currentHolon = $context['currentHolon'] ?? null;
        if ($currentHolon instanceof Holon) {
            return $currentHolon->canEdit();
        }

        $organization = $context['organization'] ?? null;
        return $organization instanceof Organization && $organization->canEdit();
    }
}

if (!function_exists('omoProjectsCanCreateContext')) {
    function omoProjectsCanCreateContext(array $context)
    {
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        if ($currentUserId <= 0) {
            return false;
        }

        $useSessionCache = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST';
        $currentHolon = $context['currentHolon'] ?? null;
        if ($currentHolon instanceof Holon) {
            return $currentHolon->isAllowed('CAN_CREATE_PROJECT', $useSessionCache, $currentUserId);
        }

        $organization = $context['organization'] ?? null;
        $rootHolon = $context['rootHolon'] ?? null;
        if ($rootHolon instanceof Holon) {
            return $rootHolon->isAllowed('CAN_CREATE_PROJECT', $useSessionCache, $currentUserId);
        }

        return $organization instanceof Organization && $organization->canEdit();
    }
}

if (!function_exists('omoProjectsCanManageProject')) {
    function omoProjectsCanManageProject(Project $project, array $context)
    {
        $projectHolon = $project->getHolon();
        if ($projectHolon instanceof Holon) {
            return $projectHolon->canEdit();
        }

        // A task without its own holon inherits the management right of its
        // project chain. This keeps project-owned tasks editable even when
        // they are not directly attached to a holon.
        $parent = $project->getParent();
        if (
            $parent instanceof Project
            && (int)$parent->getId() !== (int)$project->getId()
            && (int)$parent->get('IDorganization') === (int)$project->get('IDorganization')
            && (int)$parent->get('active') === 1
            && omoProjectsCanManageProject($parent, $context)
        ) {
            return true;
        }

        $organization = $context['organization'] ?? null;
        return $organization instanceof Organization && $organization->canEdit();
    }
}

if (!function_exists('omoProjectsIsKanbanVisible')) {
    function omoProjectsIsKanbanVisible(Project $project, array $projectsById, array $projectsByParent, array $visibleProjectIds = [])
    {
        $children = $projectsByParent[(int)$project->getId()] ?? [];
        if (count($children) > 0) {
            return true;
        }

        $parentId = (int)$project->get('IDproject_parent');
        if ($parentId <= 0) {
            return true;
        }

        $parent = $projectsById[$parentId] ?? null;
        if (!($parent instanceof Project)) {
            return true;
        }

        return !isset($visibleProjectIds[$parentId]);
    }
}

if (!function_exists('omoProjectsCountDescendants')) {
    function omoProjectsCountDescendants($projectId, array $childrenByParent, array &$visited = [])
    {
        $projectId = (int)$projectId;
        if ($projectId <= 0 || isset($visited[$projectId])) {
            return 0;
        }

        $visited[$projectId] = true;
        $count = 0;
        foreach ($childrenByParent[$projectId] ?? [] as $child) {
            if (!($child instanceof Project)) {
                continue;
            }
            $childId = (int)$child->getId();
            if ($childId <= 0 || isset($visited[$childId])) {
                continue;
            }
            $count++;
            $count += omoProjectsCountDescendants($childId, $childrenByParent, $visited);
        }
        return $count;
    }
}

if (!function_exists('omoProjectsFormatDate')) {
    function omoProjectsFormatDate($value)
    {
        return $value instanceof \DateTimeInterface ? $value->format('d.m.Y') : '';
    }
}

if (!function_exists('omoProjectsFormatGanttDateRange')) {
    function omoProjectsFormatGanttDateRange(\DateTimeInterface $start, \DateTimeInterface $end)
    {
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $start->format('d.m.Y');
        }
        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $start->format('d') . '-' . $end->format('d.m.Y');
        }
        if ($start->format('Y') === $end->format('Y')) {
            return $start->format('d.m') . '-' . $end->format('d.m.Y');
        }
        return $start->format('d.m.Y') . '-' . $end->format('d.m.Y');
    }
}

if (!function_exists('omoProjectsResolveGanttDates')) {
    function omoProjectsResolveGanttDates(Project $project, array $projectsById, array &$memo = [], array $path = [])
    {
        $projectId = (int)$project->getId();
        if ($projectId > 0 && isset($memo[$projectId])) {
            return $memo[$projectId];
        }

        $start = $project->get('planned_start_date');
        $end = $project->get('planned_end_date');
        $start = $start instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($start) : null;
        $end = $end instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($end) : null;
        $inheritedStart = false;
        $inheritedEnd = false;
        $parentId = (int)$project->get('IDproject_parent');

        if ($parentId > 0 && !isset($path[$projectId])) {
            $parent = $projectsById[$parentId] ?? null;
            if ($parent instanceof Project && (int)$parent->getId() !== $projectId) {
                $path[$projectId] = true;
                $parentDates = omoProjectsResolveGanttDates($parent, $projectsById, $memo, $path);
                if (!($start instanceof \DateTimeImmutable) && $parentDates['start'] instanceof \DateTimeImmutable) {
                    $start = $parentDates['start'];
                    $inheritedStart = true;
                }
                if (!($end instanceof \DateTimeImmutable) && $parentDates['end'] instanceof \DateTimeImmutable) {
                    $end = $parentDates['end'];
                    $inheritedEnd = true;
                }
            }
        }

        $dates = [
            'start' => $start,
            'end' => $end,
            'inheritedStart' => $inheritedStart,
            'inheritedEnd' => $inheritedEnd,
        ];
        if ($projectId > 0) {
            $memo[$projectId] = $dates;
        }
        return $dates;
    }
}

if (!function_exists('omoProjectsGetVisibleDocuments')) {
    function omoProjectsGetVisibleDocuments(Project $project, $organizationId, $projectHolon = null)
    {
        $projectDocuments = new ArrayProjectDocument();
        $projectDocuments->loadForProject((int)$project->getId());
        $visibleDocuments = [];

        foreach ($projectDocuments as $projectDocument) {
            $document = $projectDocument->getDocument();
            if (
                !is_object($document)
                || (int)$document->get('IDorganization') !== (int)$organizationId
                || $document->isArchived()
                || !(
                    $document->canViewInOrganizationContext(
                        (int)$organizationId,
                        $projectHolon instanceof Holon ? (int)$projectHolon->getId() : null
                    )
                    || $document->canViewDirectlyInOrganization((int)$organizationId)
                )
            ) {
                continue;
            }

            $createdAt = $projectDocument->get('datecreation');
            $visibleDocuments[] = [
                'id' => (int)$document->getId(),
                'title' => trim((string)$document->get('title')),
                'type' => $document->getDocumentTypeLabel(),
                'addedAt' => $createdAt instanceof \DateTimeInterface ? $createdAt->format('d.m.Y') : '',
            ];
        }

        return $visibleDocuments;
    }
}

if (!function_exists('omoProjectsStatusLabel')) {
    function omoProjectsStatusLabel($status)
    {
        $status = Project::normalizeStatus($status);
        return omoProjectsT('projects.status.' . $status);
    }
}

if (!function_exists('omoProjectsCaptureModeLabel')) {
    function omoProjectsCaptureModeLabel($captureMode)
    {
        $captureMode = Project::normalizeCaptureMode($captureMode);
        return omoProjectsT('projects.capture_mode.' . $captureMode);
    }
}

if (!function_exists('omoProjectsScopeContainsProject')) {
    function omoProjectsScopeContainsProject(Project $project, $scope, $currentHolonId, array $descendantHolonIds = [])
    {
        $projectHolonId = (int)$project->get('IDholon');
        $scope = trim(mb_strtolower((string)$scope, 'UTF-8'));
        if ($scope === 'descendants') {
            return in_array($projectHolonId, array_merge([(int)$currentHolonId], array_map('intval', $descendantHolonIds)), true);
        }
        if ($scope === 'children') {
            return in_array($projectHolonId, array_map('intval', $descendantHolonIds), true);
        }
        return $projectHolonId === (int)$currentHolonId || ($projectHolonId === 0 && (int)$currentHolonId === 0);
    }
}

if (!function_exists('omoProjectsGetUserLabel')) {
    function omoProjectsGetUserLabel($user)
    {
        if (!is_object($user)) {
            return omoProjectsT('projects.detail.none');
        }

        $name = trim(trim((string)$user->get('firstname')) . ' ' . trim((string)$user->get('lastname')));
        if ($name !== '') {
            return $name;
        }

        $username = trim((string)$user->get('username'));
        return $username !== '' ? $username : trim((string)$user->get('email'));
    }
}

if (!function_exists('omoProjectsBuildStatusBar')) {
    function omoProjectsBuildStatusBar(Project $project, array $childrenByParent, array &$memo = [], $includeSelfWhenLeaf = false)
    {
        return Project::buildChildrenStatusSummary($project, $childrenByParent, $memo, $includeSelfWhenLeaf);
    }
}

if (!function_exists('omoProjectsStatusDisplayOrder')) {
    function omoProjectsStatusDisplayOrder()
    {
        return [
            Project::STATUS_READY,
            Project::STATUS_IN_PROGRESS,
            Project::STATUS_BLOCKED,
            Project::STATUS_REVIEW,
            Project::STATUS_DONE,
            Project::STATUS_SOMEDAY,
        ];
    }
}

if (!function_exists('omoProjectsStatusSummaryLabel')) {
    function omoProjectsStatusSummaryLabel(array $summary)
    {
        $parts = [];
        foreach (omoProjectsStatusDisplayOrder() as $status) {
            $count = (int)($summary['counts'][$status] ?? 0);
            if ($count > 0) {
                $weight = (float)($summary['weights'][$status] ?? 0);
                $percentage = rtrim(rtrim(number_format($weight * 100, 1, '.', ''), '0'), '.');
                $parts[] = omoProjectsStatusLabel($status) . ': ' . $count . ' (' . $percentage . '%)';
            }
        }
        return omoProjectsT('projects.subprojects.label') . ': ' . implode(', ', $parts);
    }
}

if (!function_exists('omoProjectsRenderStatusBar')) {
    function omoProjectsRenderStatusBar(array $summary, $extraClass = '', $elementTag = 'div')
    {
        if ((int)($summary['total'] ?? 0) <= 0 || empty($summary['leaves'])) {
            return '';
        }

        $elementTag = strtolower(trim((string)$elementTag)) === 'span' ? 'span' : 'div';
        $className = trim('omo-project-status-bar ' . (string)$extraClass);
        $label = omoProjectsStatusSummaryLabel($summary);
        $html = '<' . $elementTag . ' class="' . omoApiEscape($className) . '" role="img" aria-label="' . omoApiEscape($label) . '" title="' . omoApiEscape($label) . '">';
        $weightsByStatus = array_fill_keys(omoProjectsStatusDisplayOrder(), 0.0);
        foreach ($summary['leaves'] as $leaf) {
            $status = Project::normalizeStatus($leaf['status'] ?? '');
            if (!array_key_exists($status, $weightsByStatus)) {
                continue;
            }
            $weightsByStatus[$status] += max(0, (float)($leaf['weight'] ?? 0));
        }
        foreach (omoProjectsStatusDisplayOrder() as $status) {
            $segmentWidth = max(0, min(100, $weightsByStatus[$status] * 100));
            if ($segmentWidth <= 0) {
                continue;
            }
            $html .= '<span class="omo-project-status-bar__segment omo-project-status-bar__segment--' . omoApiEscape($status) . '" style="flex: 0 0 ' . omoApiEscape(number_format($segmentWidth, 6, '.', '')) . '%;" aria-hidden="true"></span>';
        }
        return $html . '</' . $elementTag . '>';
    }
}
