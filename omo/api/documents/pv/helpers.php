<?php

function omoDocumentsPvEditorSourceLang(): array
{
    return [
        'documents.pv_editor.page.title' => ['text' => 'Préparation du PV', 'context' => 'Title shown for the upcoming PV editor.'],
        'documents.pv_editor.page.description' => ['text' => 'Le PV suit plusieurs étapes, et chaque personne édite uniquement les points qu’elle a créés lorsque cela est autorisé.', 'context' => 'Description shown in the PV editor header.'],
        'documents.pv_editor.error.unavailable' => ['text' => 'Cet éditeur de PV n’est pas disponible pour ce document.', 'context' => 'Error shown when the PV editor cannot be used.'],
        'documents.pv_editor.error.forbidden' => ['text' => 'Vous ne pouvez pas ouvrir cet éditeur de PV.', 'context' => 'Error shown when the current viewer cannot use the PV editor.'],
        'documents.pv_editor.error.invalid_request' => ['text' => 'Demande invalide.', 'context' => 'Error shown when the PV editor action endpoint receives an unsupported request method.'],
        'documents.pv_editor.action.add_point' => ['text' => 'Ajouter un point', 'context' => 'Button used to add a new agenda point in the PV editor.'],
        'documents.pv_editor.action.add_group' => ['text' => 'Ajouter un groupe', 'context' => 'Button used to add a thematic group in the PV agenda.'],
        'documents.pv_editor.action.save' => ['text' => 'Enregistrer', 'context' => 'Button used to save a PV point.'],
        'documents.pv_editor.action.take_over_lock' => ['text' => 'Reprendre l’édition', 'context' => 'Button allowing the PV editor to take over a point editing lock.'],
        'documents.pv_editor.field.auto_save' => ['text' => 'Enregistrer automatiquement', 'context' => 'Checkbox enabling automatic saving of PV points after inactivity.'],
        'documents.pv_editor.action.auto_summary' => ['text' => 'Résumé auto', 'context' => 'Button generating an automatic summary of the complete PV.'],
        'documents.pv_editor.action.auto_summary_loading' => ['text' => 'Résumé en cours…', 'context' => 'Temporary label while generating the automatic PV summary.'],
        'documents.pv_editor.state.auto_summary_ready' => ['text' => 'Résumé généré. Enregistrez le PV pour le conserver.', 'context' => 'Status shown after the automatic PV summary has been generated locally.'],
        'documents.pv_editor.action.delete_point' => ['text' => 'Supprimer le point', 'context' => 'Accessible label for deleting an editable PV agenda point.'],
        'documents.pv_editor.action.delete_item' => ['text' => 'Supprimer l’élément', 'context' => 'Accessible label for the PV drag and drop deletion target.'],
        'documents.pv_editor.warning.delete_point' => ['text' => 'Supprimer ce point et son contenu ?', 'context' => 'Confirmation shown before deleting a PV agenda point.'],
        'documents.pv_editor.warning.delete_item' => ['text' => 'Supprimer cet élément ? Les points d’un groupe seront conservés.', 'context' => 'Confirmation shown before deleting a PV agenda item or group.'],
        'documents.pv_editor.action.saving' => ['text' => 'Enregistrement…', 'context' => 'Temporary label while saving a PV point.'],
        'documents.pv_editor.action.reordering' => ['text' => 'Réorganisation…', 'context' => 'Temporary label while PV points are being reordered.'],
        'documents.pv_editor.state.saved' => ['text' => 'Sauvegardé', 'context' => 'Button label shown when a PV point is already saved.'],
        'documents.pv_editor.state.dirty' => ['text' => 'Modifications non enregistrées', 'context' => 'State label shown when a PV point has local unsaved changes.'],
        'documents.pv_editor.state.readonly' => ['text' => 'Lecture seule', 'context' => 'Badge shown on points created by other users.'],
        'documents.pv_editor.state.mine' => ['text' => 'Mon point', 'context' => 'Badge shown on points owned by the current user.'],
        'documents.pv_editor.state.handled' => ['text' => 'Traité', 'context' => 'State label shown when a PV point has been handled.'],
        'documents.pv_editor.state.locked' => ['text' => 'Verrouillé', 'context' => 'State badge shown when a PV point is locked by another session.'],
        'documents.pv_editor.field.title' => ['text' => 'Titre', 'context' => 'Label for the PV point title field.'],
        'documents.pv_editor.field.type' => ['text' => 'Type', 'context' => 'Label for the PV point type field.'],
        'documents.pv_editor.field.author' => ['text' => 'Auteur', 'context' => 'Label showing the author of a PV point.'],
        'documents.pv_editor.field.duration' => ['text' => 'Durée estimée', 'context' => 'Label showing the desired duration of a PV point.'],
        'documents.pv_editor.field.duration_short' => ['text' => '{minutes} min', 'context' => 'Short duration label used in compact PV point rows.'],
        'documents.pv_editor.field.duration_empty' => ['text' => '-- min', 'context' => 'Fallback duration label used when no desired duration is set.'],
        'documents.pv_editor.field.confidential' => ['text' => 'Confidentiel', 'context' => 'Checkbox label used to limit a PV point to people marked present at the meeting.'],
        'documents.pv_editor.field.confidential_hint' => ['text' => 'Visible uniquement par les personnes présentes à la réunion.', 'context' => 'Help text for the confidential PV point checkbox.'],
        'documents.pv_editor.field.stage' => ['text' => 'Étape', 'context' => 'Label of the PV workflow stage selector.'],
        'documents.pv_editor.field.document_title' => ['text' => 'Titre du PV', 'context' => 'Label for the editable PV document title.'],
        'documents.pv_editor.field.document_description' => ['text' => 'Description', 'context' => 'Label for the editable PV document description.'],
        'documents.pv_editor.field.document_visibility' => ['text' => 'Visibilité', 'context' => 'Label for the PV document view visibility selector.'],
        'documents.pv_editor.state.metadata_dirty' => ['text' => 'Modifications non enregistrées', 'context' => 'State shown when PV document metadata has local changes.'],
        'documents.pv_editor.field.stage.preparation' => ['text' => 'Préparation', 'context' => 'Option label for the preparation stage of a PV.'],
        'documents.pv_editor.field.stage.meeting' => ['text' => 'Réunion', 'context' => 'Option label for the meeting stage of a PV.'],
        'documents.pv_editor.field.stage.review' => ['text' => 'Relecture', 'context' => 'Option label for the review stage of a PV.'],
        'documents.pv_editor.field.stage.validated' => ['text' => 'Validé', 'context' => 'Option label for the validated stage of a PV.'],
        'documents.pv_editor.field.attendance' => ['text' => 'Liste de présence', 'context' => 'Label shown above the attendance checklist of invited people in the PV editor header.'],
        'documents.pv_editor.field.attendance_empty' => ['text' => 'Aucune personne invitée pour le moment.', 'context' => 'Empty state shown when the event linked to the PV has no resolved invitees.'],
        'documents.pv_editor.field.attendance_present' => ['text' => 'Présent', 'context' => 'Checkbox label used to mark an invited person as present in the PV editor.'],
        'documents.pv_editor.field.attendance_count' => ['text' => '{present}/{total} présents', 'context' => 'Compact summary shown next to the attendance checklist title in the PV editor.'],
        'documents.pv_editor.field.pv_editor' => ['text' => 'Éditeur du PV', 'context' => 'Label of the person acting as PV secretary.'],
        'documents.pv_editor.field.initial_author' => ['text' => 'Auteur initial', 'context' => 'Label showing the person who initially created the PV document.'],
        'documents.pv_editor.action.claim_pv_editor' => ['text' => 'Devenir éditeur', 'context' => 'Button used to claim the PV secretary role.'],
        'documents.pv_editor.action.pass_pv_editor' => ['text' => 'Passer la main', 'context' => 'Button used by the current PV editor to allow an invited member to replace them.'],
        'documents.pv_editor.action.replace_pv_editor' => ['text' => 'Remplacer l’éditeur', 'context' => 'Button used by an invited member to take over a PV editor role that was handed over.'],
        'documents.pv_editor.action.reclaim_pv_editor' => ['text' => 'Reprendre la main', 'context' => 'Button used by a person allowed to claim the PV editor role from the current editor.'],
        'documents.pv_editor.state.pv_editor_handover_waiting' => ['text' => 'En attente d’un remplaçant', 'context' => 'State shown while the current PV editor has opened the handover for an invited member.'],
        'documents.pv_editor.warning.unsaved_handover' => ['text' => 'Enregistrez toutes les modifications avant de passer la main.', 'context' => 'Warning shown when the PV editor tries to hand over while local changes are not saved.'],
        'documents.pv_editor.action.invite' => ['text' => 'Inviter', 'context' => 'Button used by the PV secretary to edit the event invitation list during preparation.'],
        'documents.pv_editor.action.send_invitations' => ['text' => 'Envoyer les invitations', 'context' => 'Menu action used to open the PV invitation email popup.'],
        'documents.pv_editor.action.invitation_options' => ['text' => 'Options des invitations', 'context' => 'Accessible label of the menu attached to the PV invitation button.'],
        'documents.pv_editor.action.more' => ['text' => 'Plus d’actions', 'context' => 'Accessible label of the compact PV editor actions menu.'],
        'documents.pv_editor.action.mark_template' => ['text' => 'Enregistrer comme modèle', 'context' => 'Action used to make the current PV available as a reusable template.'],
        'documents.pv_editor.action.unmark_template' => ['text' => 'Retirer des modèles', 'context' => 'Action used to stop exposing the current PV as a reusable template.'],
        'documents.pv_editor.action.export_pdf' => ['text' => 'Exporter en PDF', 'context' => 'Action used to download the current PV as a PDF.'],
        'documents.pv_editor.chat.title' => ['text' => 'Signalement d’erreurs', 'context' => 'Title of the discussion used to report PV transcription errors during review.'],
        'documents.pv_editor.chat.placeholder' => ['text' => 'Signalez une erreur de transcription ou une faute d’orthographe…', 'context' => 'Placeholder of the PV review error report composer.'],
        'documents.pv_editor.chat.send' => ['text' => 'Envoyer', 'context' => 'Send button of the PV review discussion.'],
        'documents.pv_editor.chat.empty' => ['text' => 'Aucune erreur signalée pour le moment.', 'context' => 'Empty state of the PV review error report discussion.'],
        'documents.pv_editor.chat.readonly' => ['text' => 'La discussion est disponible en lecture seule.', 'context' => 'Message when posting to a closed PV review discussion.'],
        'documents.pv_editor.chat.invalid_message' => ['text' => 'Le message doit contenir entre 1 et 4 000 caractères.', 'context' => 'Validation error for a PV review discussion message.'],
        'documents.pv_editor.chat.send_error' => ['text' => 'Le message ne peut pas être envoyé pour le moment.', 'context' => 'Error when sending a PV review discussion message fails.'],
        'documents.pv_editor.chat.system_author' => ['text' => 'PV', 'context' => 'Author label used for automatic PV review discussion entries.'],
        'documents.pv_editor.chat.open' => ['text' => 'Signaler des erreurs sur ce point', 'context' => 'Accessible label for the button opening a point error report discussion.'],
        'documents.pv_editor.chat.report_errors' => ['text' => 'Signaler des erreurs', 'context' => 'Button opening the review error report discussion for one PV point.'],
        'documents.pv_editor.chat.message_count' => ['text' => 'Nombre de messages : {count}', 'context' => 'Accessible label for the count of messages in a PV point error report discussion.'],
        'documents.pv_editor.chat.change_details' => ['text' => 'Voir les modifications', 'context' => 'Button revealing the before and after values of a PV point modification.'],
        'documents.pv_editor.chat.content_excerpt' => ['text' => 'Contenu (extrait)', 'context' => 'Label used when a long PV point content change is reduced to the affected excerpt.'],
        'documents.pv_editor.chat.point_title' => ['text' => 'Erreurs signalées pour ce point', 'context' => 'Popup title for an error report discussion attached to one PV point.'],
        'documents.pv_editor.chat.loading' => ['text' => 'Chargement de la discussion…', 'context' => 'Loading state in a PV point discussion popup.'],
        'documents.pv_editor.popup.invite_title' => ['text' => 'Invités', 'context' => 'Title of the invitation popup opened from the PV editor.'],
        'documents.pv_editor.notice.pv_editor_empty' => ['text' => 'Aucun éditeur attribué.', 'context' => 'State shown when no one has claimed the PV secretary role.'],
        'documents.pv_editor.notice.pv_editor_active' => ['text' => 'Vous êtes éditeur du PV.', 'context' => 'State shown to the PV secretary.'],
        'documents.pv_editor.notice.pv_editor_can_edit' => ['text' => 'Vous pouvez modifier ce point car vous êtes l’éditeur du PV.', 'context' => 'Helper text shown when the PV secretary edits a point.'],
        'documents.pv_editor.field.author' => ['text' => 'Porté par', 'context' => 'Label of the person assigned to a PV point.'],
        'documents.pv_editor.field.handled' => ['text' => 'Traité', 'context' => 'Label for the handled checkbox of a PV point.'],
        'documents.pv_editor.field.concerned_holon' => ['text' => 'Holon concerné', 'context' => 'Label showing the main holon concerned by a PV point.'],
        'documents.pv_editor.field.concerned_holon_empty' => ['text' => 'Sans rôle', 'context' => 'Empty option for the concerned role selector of a PV point.'],
        'documents.pv_editor.field.addressed_holons' => ['text' => 'Holons adressés', 'context' => 'Label showing the addressed holons of a PV point.'],
        'documents.pv_editor.field.tensions' => ['text' => 'Tensions', 'context' => 'Label showing the tensions attached to a PV point.'],
        'documents.pv_editor.field.content' => ['text' => 'Contenu', 'context' => 'Label shown above the HTML content editor of a PV point.'],
        'documents.pv_editor.embed.button_title' => ['text' => 'Insérer un document', 'context' => 'Tooltip for the document insertion button in a PV point editor.'],
        'documents.pv_editor.embed.modal_title' => ['text' => 'Insérer un document', 'context' => 'Title of the document picker opened from a PV point editor.'],
        'documents.pv_editor.embed.search' => ['text' => 'Recherche', 'context' => 'Label for the document picker search field.'],
        'documents.pv_editor.embed.search_placeholder' => ['text' => 'Titre, résumé ou contexte', 'context' => 'Placeholder for the document picker search field.'],
        'documents.pv_editor.embed.quick_search_placeholder' => ['text' => 'Recherche rapide', 'context' => 'Placeholder for the compact resource picker search field.'],
        'documents.pv_editor.embed.scope_local' => ['text' => 'Local', 'context' => 'Scope filtering resources attached to the selected holon only.'],
        'documents.pv_editor.embed.scope_children' => ['text' => 'Enfants directs', 'context' => 'Scope filtering resources attached to the selected holon and its direct children.'],
        'documents.pv_editor.embed.scope_descendants' => ['text' => 'Descendants', 'context' => 'Scope filtering resources attached to the selected holon and its descendants.'],
        'documents.pv_editor.embed.visible_documents' => ['text' => 'Documents visibles', 'context' => 'Label for the visible documents list in the PV point picker.'],
        'documents.pv_editor.embed.none' => ['text' => 'Aucun document sélectionné.', 'context' => 'Empty preview shown in the PV point document picker.'],
        'documents.pv_editor.embed.insert' => ['text' => 'Insérer le document', 'context' => 'Button confirming insertion of a document in a PV point.'],
        'documents.pv_editor.embed.add_line' => ['text' => 'Ajouter une ligne', 'context' => 'Accessible label for the control that creates a text line between two embedded resources.'],
        'documents.pv_editor.action.cancel' => ['text' => 'Annuler', 'context' => 'Button cancelling an action in the PV editor.'],
        'documents.pv_editor.action.remove_embed' => ['text' => 'Supprimer', 'context' => 'Button removing an embedded resource from a PV point.'],
        'documents.pv_editor.embed.linked_label' => ['text' => 'Document lié', 'context' => 'Label displayed above an embedded document link in a PV point.'],
        'documents.pv_editor.embed.open_external' => ['text' => 'Ouvrir dans une nouvelle fenêtre', 'context' => 'Accessible label for opening an embedded document in a new window.'],
        'documents.pv_editor.decision.button_title' => ['text' => 'Insérer une décision', 'context' => 'Tooltip for the decision insertion button in a PV point editor.'],
        'documents.pv_editor.decision.modal_title' => ['text' => 'Insérer une décision', 'context' => 'Title of the decision picker opened from a PV point editor.'],
        'documents.pv_editor.decision.visible' => ['text' => 'Décisions visibles', 'context' => 'Label for the visible decisions list in the PV point picker.'],
        'documents.pv_editor.decision.insert' => ['text' => 'Insérer la décision', 'context' => 'Button confirming insertion of a decision in a PV point.'],
        'documents.pv_editor.decision.linked_label' => ['text' => 'Décision liée', 'context' => 'Label displayed above an embedded decision in a PV point.'],
        'documents.pv_editor.decision.type.decision' => ['text' => 'Décision', 'context' => 'Label for an embedded decision type.'],
        'documents.pv_editor.decision.type.consultation' => ['text' => 'Consultation', 'context' => 'Label for an embedded consultation type.'],
        'documents.pv_editor.project.button_title' => ['text' => 'Insérer un projet', 'context' => 'Tooltip for the project insertion button in a PV point editor.'],
        'documents.pv_editor.project.modal_title' => ['text' => 'Insérer un projet', 'context' => 'Title of the project picker opened from a PV point editor.'],
        'documents.pv_editor.project.visible' => ['text' => 'Projets visibles', 'context' => 'Label for the visible projects list in the PV point picker.'],
        'documents.pv_editor.project.insert' => ['text' => 'Insérer le projet', 'context' => 'Button confirming insertion of a project in a PV point.'],
        'documents.pv_editor.project.linked_label' => ['text' => 'Projet lié', 'context' => 'Label displayed above an embedded project link in a PV point.'],
        'documents.pv_editor.project.planned_date' => ['text' => 'Planifié : {date}', 'context' => 'Planned start date displayed in an embedded project card.'],
        'documents.pv_editor.project.end_date' => ['text' => 'Fin {date}', 'context' => 'Planned end date displayed in an embedded project card.'],
        'documents.pv_editor.project.tab_existing' => ['text' => 'Projet existant', 'context' => 'Tab used to select an existing project for a PV point.'],
        'documents.pv_editor.project.tab_new' => ['text' => 'Nouveau projet', 'context' => 'Tab used to create a new project from a PV point.'],
        'documents.pv_editor.project.tabs_aria' => ['text' => 'Choix du projet', 'context' => 'Accessible label for the project picker tabs.'],
        'documents.pv_editor.project.title' => ['text' => 'Titre du projet', 'context' => 'Label for the quick project creation title field.'],
        'documents.pv_editor.project.description' => ['text' => 'Description', 'context' => 'Label for the quick project creation description field.'],
        'documents.pv_editor.project.status' => ['text' => 'Statut', 'context' => 'Label for the quick project creation status field.'],
        'documents.pv_editor.project.priority' => ['text' => 'Priorité', 'context' => 'Label for the quick project creation priority field.'],
        'documents.pv_editor.project.size' => ['text' => 'Taille', 'context' => 'Label for the quick project creation size field.'],
        'documents.pv_editor.project.start_date' => ['text' => 'Début planifié', 'context' => 'Label for the quick project creation planned start date field.'],
        'documents.pv_editor.project.end_date_label' => ['text' => 'Fin planifiée', 'context' => 'Label for the quick project creation planned end date field.'],
        'documents.pv_editor.project.holon' => ['text' => 'Holon concerné', 'context' => 'Label for the quick project creation holon picker.'],
        'documents.pv_editor.project.responsible' => ['text' => 'Responsable', 'context' => 'Label for the quick project creation responsible member field.'],
        'documents.pv_editor.project.responsible_empty' => ['text' => 'Sans responsable', 'context' => 'Empty option for the quick project creation responsible member field.'],
        'documents.pv_editor.project.members_loading' => ['text' => 'Chargement des membres…', 'context' => 'Temporary label while loading the selected holon members.'],
        'documents.pv_editor.project.members_empty' => ['text' => 'Aucun membre disponible dans ce holon.', 'context' => 'Empty state for the quick project creation responsible member field.'],
        'documents.pv_editor.project.create_insert' => ['text' => 'Créer et insérer', 'context' => 'Button that creates and inserts a project into a PV point.'],
        'documents.pv_editor.project.create_error' => ['text' => 'Impossible de créer le projet.', 'context' => 'Error shown when quick project creation fails.'],
        'documents.pv_editor.project.children' => ['text' => 'Sous-projets', 'context' => 'Label of the dynamic direct subprojects review in a PV project embed.'],
        'documents.pv_editor.project.children_loading' => ['text' => 'Chargement des sous-projets…', 'context' => 'Temporary text while loading direct subprojects in a PV project embed.'],
        'documents.pv_editor.project.children_empty' => ['text' => 'Aucun sous-projet direct.', 'context' => 'Empty state for the direct subprojects review in a PV project embed.'],
        'documents.pv_editor.project.children_error' => ['text' => 'Impossible de charger les sous-projets.', 'context' => 'Error while loading direct subprojects in a PV project embed.'],
        'documents.pv_editor.checklist.button_title' => ['text' => 'Insérer un processus', 'context' => 'Tooltip for the process insertion button in a PV point editor.'],
        'documents.pv_editor.checklist.modal_title' => ['text' => 'Insérer un processus', 'context' => 'Title of the process picker opened from a PV point editor.'],
        'documents.pv_editor.checklist.visible' => ['text' => 'Processus visibles', 'context' => 'Label for the visible processes list in the PV point picker.'],
        'documents.pv_editor.checklist.insert' => ['text' => 'Insérer le processus', 'context' => 'Button confirming insertion of a process in a PV point.'],
        'documents.pv_editor.checklist.review_container' => ['text' => 'Activités récurrentes', 'context' => 'Review label for an independently scheduled process embedded in a PV.'],
        'documents.pv_editor.checklist.review_runs' => ['text' => 'Instances en cours', 'context' => 'Review label for a process checklist embedded in a PV.'],
        'documents.pv_editor.checklist.empty_runs' => ['text' => 'Aucune instance en cours.', 'context' => 'Empty state shown in a process checklist embedded in a PV.'],
        'documents.pv_editor.checklist.complete_archive' => ['text' => 'Valider et archiver', 'context' => 'Action available only to the PV editor for completing and archiving a checklist project.'],
        'documents.pv_editor.checklist.complete_archiving' => ['text' => 'Archivage…', 'context' => 'Temporary label while the PV editor completes and archives a checklist project.'],
        'documents.pv_editor.checklist.complete_archive_error' => ['text' => 'Impossible de valider et archiver ce projet.', 'context' => 'Error shown when the PV editor cannot complete and archive a checklist project.'],
        'documents.pv_editor.event.button_title' => ['text' => 'Insérer une date', 'context' => 'Tooltip for the calendar event insertion button in a PV point editor.'],
        'documents.pv_editor.event.modal_title' => ['text' => 'Insérer une date programmée', 'context' => 'Title of the calendar event picker opened from a PV point editor.'],
        'documents.pv_editor.event.visible' => ['text' => 'Dates programmées visibles', 'context' => 'Label for the visible calendar events list in the PV point picker.'],
        'documents.pv_editor.event.insert' => ['text' => 'Insérer la date', 'context' => 'Button confirming insertion of a calendar event in a PV point.'],
        'documents.pv_editor.event.tab_existing' => ['text' => 'Dates existantes', 'context' => 'First tab label in the PV event picker.'],
        'documents.pv_editor.event.tab_new' => ['text' => 'Nouvelle date', 'context' => 'Second tab label for creating an event in the PV event picker.'],
        'documents.pv_editor.event.tabs_aria' => ['text' => 'Sélection ou création de date', 'context' => 'Accessible label for the tabs in the PV event picker.'],
        'documents.pv_editor.event.title' => ['text' => 'Titre', 'context' => 'Label for the quick event creation title field in a PV point.'],
        'documents.pv_editor.event.description' => ['text' => 'Description', 'context' => 'Label for the quick event creation description field in a PV point.'],
        'documents.pv_editor.event.start_at' => ['text' => 'Début', 'context' => 'Label for the quick event creation start field in a PV point.'],
        'documents.pv_editor.event.end_at' => ['text' => 'Fin', 'context' => 'Label for the quick event creation end field in a PV point.'],
        'documents.pv_editor.event.create_insert' => ['text' => 'Créer et insérer', 'context' => 'Button that creates and inserts an event into a PV point.'],
        'documents.pv_editor.event.create_error' => ['text' => 'Impossible de créer la date.', 'context' => 'Error shown when quick event creation fails.'],
        'documents.pv_editor.event.end_after_start' => ['text' => 'La fin ne peut pas être avant le début.', 'context' => 'Client-side validation shown when a quick PV event end is before its start.'],
        'documents.pv_editor.event.error_title' => ['text' => 'Le titre est obligatoire.', 'context' => 'Validation error shown when a quick PV event has no title.'],
        'documents.pv_editor.event.error_start' => ['text' => 'La date de début est invalide.', 'context' => 'Validation error shown when a quick PV event start is invalid.'],
        'documents.pv_editor.event.error_end' => ['text' => 'La date de fin est invalide.', 'context' => 'Validation error shown when a quick PV event end is invalid.'],
        'documents.pv_editor.event.error_end_before_start' => ['text' => 'La date de fin doit être après la date de début.', 'context' => 'Validation error shown when a quick PV event end is before its start.'],
        'documents.pv_editor.event.error_holon' => ['text' => 'Le holon choisi est invalide.', 'context' => 'Validation error shown when a quick PV event holon is invalid.'],
        'documents.pv_editor.indicator.button_title' => ['text' => 'Insérer un indicateur', 'context' => 'Tooltip for the indicator insertion button in a PV point editor.'],
        'documents.pv_editor.indicator.modal_title' => ['text' => 'Insérer un indicateur', 'context' => 'Title of the indicator picker opened from a PV point editor.'],
        'documents.pv_editor.indicator.visible' => ['text' => 'Indicateurs visibles', 'context' => 'Label for the visible indicators list in the PV point picker.'],
        'documents.pv_editor.indicator.insert' => ['text' => 'Insérer l’indicateur', 'context' => 'Button confirming insertion of an indicator in a PV point.'],
        'documents.pv_editor.indicator.no_value' => ['text' => 'Aucune valeur', 'context' => 'Fallback shown for an embedded indicator without a measurement.'],
        'documents.pv_editor.indicator.overdue' => ['text' => 'En retard', 'context' => 'Status shown for an embedded overdue indicator.'],
        'documents.pv_editor.indicator.to_complete' => ['text' => 'À compléter', 'context' => 'Status shown for an embedded indicator within its grace period.'],
        'documents.pv_editor.indicator.overdue_days' => ['one' => 'En retard de {count} jour', 'other' => 'En retard de {count} jours', 'context' => 'Status showing how many days an embedded indicator is overdue.'],
        'documents.pv_editor.indicator.current' => ['text' => 'À jour', 'context' => 'Status shown for an embedded indicator whose latest measurement is on time.'],
        'documents.pv_editor.indicator.value_placeholder' => ['text' => 'Nouvelle valeur', 'context' => 'Placeholder for the immediate measurement input in an embedded indicator.'],
        'documents.pv_editor.indicator.add_value' => ['text' => 'Ajouter maintenant', 'context' => 'Button adding an immediate dated value to an embedded indicator.'],
        'documents.pv_editor.indicator.value_saving' => ['text' => 'Ajout…', 'context' => 'Temporary label while an immediate indicator value is saved.'],
        'documents.pv_editor.indicator.value_error' => ['text' => 'Impossible d’ajouter cette valeur.', 'context' => 'Error shown when an immediate indicator value cannot be saved.'],
        'documents.pv_editor.indicator.group_sum' => ['text' => 'Groupe cumulé', 'context' => 'Type label for an embedded summed indicator group.'],
        'documents.pv_editor.indicator.group_overlay' => ['text' => 'Groupe superposé', 'context' => 'Type label for an embedded overlay indicator group.'],
        'documents.pv_editor.indicator.group_members' => ['one' => '{count} indicateur', 'other' => '{count} indicateurs', 'context' => 'Member count shown for an embedded indicator group.'],
        'documents.pv_editor.field.pointtype.information' => ['text' => 'Information', 'context' => 'Option label for an informational PV point.'],
        'documents.pv_editor.field.pointtype.consultation' => ['text' => 'Consultation', 'context' => 'Option label for a consultation PV point.'],
        'documents.pv_editor.field.pointtype.decision' => ['text' => 'Décision', 'context' => 'Option label for a decision PV point.'],
        'documents.pv_editor.nav.empty' => ['text' => 'Aucun point pour le moment.', 'context' => 'Empty state shown in the left navigation when the PV has no points.'],
        'documents.pv_editor.point.default_title' => ['text' => 'Nouveau point', 'context' => 'Default title used when creating a fresh PV point.'],
        'documents.pv_editor.group.default_title' => ['text' => 'Nouveau groupe', 'context' => 'Default title used when creating a PV agenda group.'],
        'documents.pv_editor.group.toggle' => ['text' => 'Ouvrir ou fermer le groupe', 'context' => 'Accessible label for toggling a PV agenda group.'],
        'documents.pv_editor.group.points' => ['text' => 'points', 'context' => 'Unit label for the number of points summarized under a PV group.'],
        'documents.pv_editor.group.minutes' => ['text' => 'min', 'context' => 'Unit label for the cumulative duration summarized under a PV group.'],
        'documents.pv_editor.group.drop_inside' => ['text' => 'Dans ce groupe', 'context' => 'Label shown in the drag and drop indicator when nesting an agenda item in a group.'],
        'documents.pv_editor.notice.schedule' => ['text' => 'Réunion prévue le {date}.', 'context' => 'Schedule sentence shown in the PV editor header.'],
        'documents.pv_editor.notice.event' => ['text' => 'Événement associé', 'context' => 'Label used in the PV editor header for the linked event.'],
        'documents.pv_editor.notice.location' => ['text' => 'Lieu', 'context' => 'Label used in the PV editor header for the event location.'],
        'documents.pv_editor.notice.reorder' => ['text' => 'Reordonner les points', 'context' => 'Title for the PV point reorder handle.'],
        'documents.pv_editor.notice.move_up' => ['text' => 'Monter', 'context' => 'Title for the touch-friendly move up button.'],
        'documents.pv_editor.notice.move_down' => ['text' => 'Descendre', 'context' => 'Title for the touch-friendly move down button.'],
        'documents.pv_editor.notice.owner_only' => ['text' => 'Vous pouvez modifier ce point car vous en êtes l’auteur.', 'context' => 'Helper text shown on editable PV points.'],
        'documents.pv_editor.notice.readonly' => ['text' => 'Vous pouvez consulter ce point, mais seul son auteur peut le modifier avant la réunion.', 'context' => 'Helper text shown on read-only PV points.'],
        'documents.pv_editor.notice.locked_other' => ['text' => 'Édition en cours par {user}.', 'context' => 'Helper text shown when another session currently locks a PV point.'],
        'documents.pv_editor.notice.updated_by' => ['text' => 'Mis à jour par {user}.', 'context' => 'Short helper shown when a point was last updated by another user.'],
        'documents.pv_editor.notice.stage_readonly' => ['text' => 'Seules les personnes qui peuvent éditer le document peuvent changer cette étape.', 'context' => 'Helper shown below the PV stage selector when it is read only.'],
        'documents.pv_editor.warning.unsaved_close' => ['text' => 'Des modifications non enregistrées n’ont pas été sauvegardées. Fermer quand même ?', 'context' => 'Browser confirmation shown before closing the PV editor with unsaved changes.'],
        'documents.pv_editor.warning.validate_irreversible' => ['text' => 'Valider ce PV est irréversible. Il ne sera plus possible de le modifier. Continuer ?', 'context' => 'Confirmation shown before changing a PV stage to validated.'],
        'documents.pv_editor.summary.meeting_duration' => ['text' => 'Durée de la réunion', 'context' => 'Label for the total meeting duration in the PV editor timing summary.'],
        'documents.pv_editor.summary.remaining_time' => ['text' => 'Temps restant', 'context' => 'Label for the remaining meeting time when the meeting is in progress.'],
        'documents.pv_editor.summary.points_duration' => ['text' => 'Durée des points', 'context' => 'Label for the total planned duration of PV points.'],
        'documents.pv_editor.summary.points_remaining' => ['text' => 'Points restants', 'context' => 'Label for the remaining planned duration of unhandled PV points.'],
        'documents.pv_editor.summary.handled' => ['text' => 'Traités', 'context' => 'Legend label for handled agenda points in the PV editor chart.'],
        'documents.pv_editor.summary.remaining' => ['text' => 'Restants', 'context' => 'Legend label for remaining agenda points in the PV editor chart.'],
        'documents.pv_editor.summary.margin' => ['text' => 'Marge', 'context' => 'Legend label for unused meeting time in the PV editor chart.'],
        'documents.pv_editor.summary.overrun' => ['text' => 'Dépassement', 'context' => 'Legend label for overrun beyond meeting duration in the PV editor chart.'],
        'documents.pv_editor.summary.not_started' => ['text' => '--', 'context' => 'Fallback value for remaining time when the meeting is not in progress.'],
    ];
}

function omoDocumentsPvEditorBuildIndicatorEmbedPayload(\dbObject\StatIndicator $indicator, bool $isPvEditor = false, ?callable $translate = null): array
{
    $translate = $translate ?: static function (string $key): string {
        return $key;
    };
    $values = omoStatsCollectionItems($indicator->getMeasurements(), \dbObject\StatIndicatorValue::class);
    $referencePoints = omoStatsCollectionItems($indicator->getReferencePoints(), \dbObject\StatIndicatorReferencePoint::class);
    $latestValue = count($values) > 0 ? $values[count($values) - 1] : null;
    $referencePercentage = omoStatsGetIndicatorReferencePercentage($indicator, $latestValue, $referencePoints);
    $overdueInfo = omoStatsGetIndicatorOverdueInfo($indicator);
    $isOverdue = $overdueInfo['is_overdue'];
    $hasFrequency = \dbObject\StatIndicator::normalizeMeasurementFrequency($indicator->get('measurement_frequency')) !== null;
    $canAddValue = $indicator->canEdit() || $isPvEditor;
    $chartSeries = omoStatsGetIndicatorChartSeries($indicator, $values, $referencePoints);
    $chartNumbers = array_column(array_merge($chartSeries['measure'], $chartSeries['reference']), 'value');
    $chartScale = count($chartNumbers) > 0
        ? omoStatsResolveChartScale(min($chartNumbers), max($chartNumbers))
        : null;

    return [
        'id' => (int)$indicator->getId(),
        'kind' => 'indicator',
        'contextHolonId' => (int)$indicator->get('IDholon'),
        'title' => trim((string)$indicator->get('name')),
        'description' => trim((string)$indicator->get('description')),
        'contextLabel' => omoStatsContextLabel($indicator),
        'valueLabel' => $latestValue instanceof \dbObject\StatIndicatorValue
            ? omoStatsFormatNumber($latestValue->get('value')) . (is_numeric($referencePercentage) ? ' (' . omoStatsFormatNumber($referencePercentage) . '%)' : '')
            : $translate('documents.pv_editor.indicator.no_value'),
        'dateLabel' => $latestValue instanceof \dbObject\StatIndicatorValue
            ? omoStatsFormatDateTime($latestValue->get('measured_at'), false)
            : '',
        'statusLabel' => $overdueInfo['severity'] === 'warning'
            ? $translate('documents.pv_editor.indicator.to_complete')
            : ($isOverdue
                ? $translate('documents.pv_editor.indicator.overdue_days', ['count' => $overdueInfo['overdue_days']])
            : ($hasFrequency && $latestValue instanceof \dbObject\StatIndicatorValue
                ? $translate('documents.pv_editor.indicator.current')
                : '')),
        'isOverdue' => $isOverdue,
        'overdueSeverity' => $overdueInfo['severity'],
        'canAddValue' => $canAddValue,
        'chartMinLabel' => is_array($chartScale) ? omoStatsFormatNumber($chartScale['min']) : '',
        'chartMaxLabel' => is_array($chartScale) ? omoStatsFormatNumber($chartScale['max']) : '',
        'chartHtml' => omoStatsRenderChart($indicator, $values, $referencePoints, 'compact', $overdueInfo['severity']),
    ];
}

function omoDocumentsPvEditorBuildUiText(?callable $translate = null): array
{
    $resolve = static function (string $key, string $fallback) use ($translate): string {
        if (is_callable($translate)) {
            return (string)call_user_func($translate, $key);
        }

        return $fallback;
    };

    return [
        'save' => $resolve('documents.pv_editor.action.save', 'Enregistrer'),
        'takeOverLock' => $resolve('documents.pv_editor.action.take_over_lock', 'Reprendre l’édition'),
        'deletePoint' => $resolve('documents.pv_editor.action.delete_point', 'Supprimer le point'),
        'deleteItem' => $resolve('documents.pv_editor.action.delete_item', 'Supprimer l’élément'),
        'deleteItemMessage' => $resolve('documents.pv_editor.warning.delete_item', 'Supprimer cet élément ? Les points d’un groupe seront conservés.'),
        'saving' => $resolve('documents.pv_editor.action.saving', 'Enregistrement…'),
        'reordering' => $resolve('documents.pv_editor.action.reordering', 'Réorganisation…'),
        'saved' => $resolve('documents.pv_editor.state.saved', 'Enregistré'),
        'dirty' => $resolve('documents.pv_editor.state.dirty', 'Modifications non enregistrées'),
        'readonly' => $resolve('documents.pv_editor.state.readonly', 'Lecture seule'),
        'mine' => $resolve('documents.pv_editor.state.mine', 'Mon point'),
        'handledState' => $resolve('documents.pv_editor.state.handled', 'Traité'),
        'lockedState' => $resolve('documents.pv_editor.state.locked', 'Verrouillé'),
        'title' => $resolve('documents.pv_editor.field.title', 'Titre'),
        'type' => $resolve('documents.pv_editor.field.type', 'Type'),
        'author' => $resolve('documents.pv_editor.field.author', 'Auteur'),
        'duration' => $resolve('documents.pv_editor.field.duration', 'Durée estimée'),
        'durationShort' => $resolve('documents.pv_editor.field.duration_short', '{minutes} min'),
        'durationEmpty' => $resolve('documents.pv_editor.field.duration_empty', '-- min'),
        'confidential' => $resolve('documents.pv_editor.field.confidential', 'Confidentiel'),
        'confidentialHint' => $resolve('documents.pv_editor.field.confidential_hint', 'Visible uniquement par les personnes présentes à la réunion.'),
        'stage' => $resolve('documents.pv_editor.field.stage', 'Étape'),
        'stagePreparation' => $resolve('documents.pv_editor.field.stage.preparation', 'Préparation'),
        'stageMeeting' => $resolve('documents.pv_editor.field.stage.meeting', 'Réunion'),
        'stageReview' => $resolve('documents.pv_editor.field.stage.review', 'Relecture'),
        'stageValidated' => $resolve('documents.pv_editor.field.stage.validated', 'Validé'),
        'attendance' => $resolve('documents.pv_editor.field.attendance', 'Liste de présence'),
        'attendanceEmpty' => $resolve('documents.pv_editor.field.attendance_empty', 'Aucune personne invitée pour le moment.'),
        'attendancePresent' => $resolve('documents.pv_editor.field.attendance_present', 'Présent'),
        'attendanceCount' => $resolve('documents.pv_editor.field.attendance_count', '{present}/{total} présents'),
        'pvEditor' => $resolve('documents.pv_editor.field.pv_editor', 'Éditeur du PV'),
        'initialAuthor' => $resolve('documents.pv_editor.field.initial_author', 'Auteur initial'),
        'claimPvEditor' => $resolve('documents.pv_editor.action.claim_pv_editor', 'Devenir éditeur'),
        'passPvEditor' => $resolve('documents.pv_editor.action.pass_pv_editor', 'Passer la main'),
        'replacePvEditor' => $resolve('documents.pv_editor.action.replace_pv_editor', 'Remplacer l’éditeur'),
        'reclaimPvEditor' => $resolve('documents.pv_editor.action.reclaim_pv_editor', 'Reprendre la main'),
        'pvEditorHandoverWaiting' => $resolve('documents.pv_editor.state.pv_editor_handover_waiting', 'En attente d’un remplaçant'),
        'unsavedHandover' => $resolve('documents.pv_editor.warning.unsaved_handover', 'Enregistrez toutes les modifications avant de passer la main.'),
        'invite' => $resolve('documents.pv_editor.action.invite', 'Inviter'),
        'sendInvitations' => $resolve('documents.pv_editor.action.send_invitations', 'Envoyer les invitations'),
        'invitationOptions' => $resolve('documents.pv_editor.action.invitation_options', 'Options des invitations'),
        'moreActions' => $resolve('documents.pv_editor.action.more', 'Plus d’actions'),
        'markTemplate' => $resolve('documents.pv_editor.action.mark_template', 'Enregistrer comme modèle'),
        'unmarkTemplate' => $resolve('documents.pv_editor.action.unmark_template', 'Retirer des modèles'),
        'exportPdf' => $resolve('documents.pv_editor.action.export_pdf', 'Exporter en PDF'),
        'chatOpen' => $resolve('documents.pv_editor.chat.open', 'Ouvrir la discussion du point'),
        'chatReportErrors' => $resolve('documents.pv_editor.chat.report_errors', 'Signaler des erreurs'),
        'chatMessageCount' => $resolve('documents.pv_editor.chat.message_count', 'Nombre de messages : {count}'),
        'chatChangeDetails' => $resolve('documents.pv_editor.chat.change_details', 'Voir les modifications'),
        'chatContentExcerpt' => $resolve('documents.pv_editor.chat.content_excerpt', 'Contenu (extrait)'),
        'chatPointTitle' => $resolve('documents.pv_editor.chat.point_title', 'Discussion du point'),
        'chatLoading' => $resolve('documents.pv_editor.chat.loading', 'Chargement de la discussion…'),
        'chatEmpty' => $resolve('documents.pv_editor.chat.empty', 'Aucun message pour le moment.'),
        'chatPlaceholder' => $resolve('documents.pv_editor.chat.placeholder', 'Écrivez un message…'),
        'chatSend' => $resolve('documents.pv_editor.chat.send', 'Envoyer'),
        'autoSummary' => $resolve('documents.pv_editor.action.auto_summary', 'Résumé auto'),
        'autoSummaryLoading' => $resolve('documents.pv_editor.action.auto_summary_loading', 'Résumé en cours…'),
        'autoSummaryReady' => $resolve('documents.pv_editor.state.auto_summary_ready', 'Résumé généré. Enregistrez le PV pour le conserver.'),
        'inviteTitle' => $resolve('documents.pv_editor.popup.invite_title', 'Invités'),
        'pvEditorEmpty' => $resolve('documents.pv_editor.notice.pv_editor_empty', 'Aucun éditeur attribué.'),
        'pvEditorActive' => $resolve('documents.pv_editor.notice.pv_editor_active', 'Vous êtes éditeur du PV.'),
        'pvEditorCanEdit' => $resolve('documents.pv_editor.notice.pv_editor_can_edit', 'Vous pouvez modifier ce point car vous êtes l’éditeur du PV.'),
        'author' => $resolve('documents.pv_editor.field.author', 'Porté par'),
        'handled' => $resolve('documents.pv_editor.field.handled', 'Traité'),
        'concernedHolon' => $resolve('documents.pv_editor.field.concerned_holon', 'Holon concerné'),
        'concernedHolonEmpty' => $resolve('documents.pv_editor.field.concerned_holon_empty', 'Sans rôle'),
        'addressedHolons' => $resolve('documents.pv_editor.field.addressed_holons', 'Holons adressés'),
        'tensions' => $resolve('documents.pv_editor.field.tensions', 'Tensions'),
        'content' => $resolve('documents.pv_editor.field.content', 'Contenu'),
        'embedAddLine' => $resolve('documents.pv_editor.embed.add_line', 'Ajouter une ligne'),
        'information' => $resolve('documents.pv_editor.field.pointtype.information', 'Information'),
        'consultation' => $resolve('documents.pv_editor.field.pointtype.consultation', 'Consultation'),
        'decision' => $resolve('documents.pv_editor.field.pointtype.decision', 'Décision'),
        'reorder' => $resolve('documents.pv_editor.notice.reorder', 'Reordonner les points'),
        'moveUp' => $resolve('documents.pv_editor.notice.move_up', 'Monter'),
        'moveDown' => $resolve('documents.pv_editor.notice.move_down', 'Descendre'),
        'ownerOnly' => $resolve('documents.pv_editor.notice.owner_only', 'Vous pouvez modifier ce point car vous en êtes l’auteur.'),
        'readonlyNotice' => $resolve('documents.pv_editor.notice.readonly', 'Vous pouvez consulter ce point, mais seul son auteur peut le modifier avant la réunion.'),
        'lockedOther' => $resolve('documents.pv_editor.notice.locked_other', 'Édition en cours par {user}.'),
        'updatedBy' => $resolve('documents.pv_editor.notice.updated_by', 'Mis à jour par {user}.'),
        'stageReadonly' => $resolve('documents.pv_editor.notice.stage_readonly', 'Seules les personnes qui peuvent éditer le document peuvent changer cette étape.'),
        'meetingDuration' => $resolve('documents.pv_editor.summary.meeting_duration', 'Durée de la réunion'),
        'remainingTime' => $resolve('documents.pv_editor.summary.remaining_time', 'Temps restant'),
        'pointsDuration' => $resolve('documents.pv_editor.summary.points_duration', 'Durée des points'),
        'pointsRemaining' => $resolve('documents.pv_editor.summary.points_remaining', 'Points restants'),
        'handledLegend' => $resolve('documents.pv_editor.summary.handled', 'Traités'),
        'remainingLegend' => $resolve('documents.pv_editor.summary.remaining', 'Restants'),
        'marginLegend' => $resolve('documents.pv_editor.summary.margin', 'Marge'),
        'overrunLegend' => $resolve('documents.pv_editor.summary.overrun', 'Dépassement'),
        'notStartedValue' => $resolve('documents.pv_editor.summary.not_started', '--'),
        'defaultTitle' => $resolve('documents.pv_editor.point.default_title', 'Nouveau point'),
        'defaultGroupTitle' => $resolve('documents.pv_editor.group.default_title', 'Nouveau groupe'),
        'toggleGroup' => $resolve('documents.pv_editor.group.toggle', 'Ouvrir ou fermer le groupe'),
        'groupPoints' => $resolve('documents.pv_editor.group.points', 'points'),
        'groupMinutes' => $resolve('documents.pv_editor.group.minutes', 'min'),
    ];
}

function omoDocumentsPvEditorEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function omoDocumentsPvEditorDurationLabel(?int $minutes, array $uiText): string
{
    if ($minutes === null || $minutes <= 0) {
        return (string)($uiText['durationEmpty'] ?? '-- min');
    }

    return str_replace('{minutes}', (string)$minutes, (string)($uiText['durationShort'] ?? '{minutes} min'));
}

function omoDocumentsPvEditorAttachConcernedHolonOptions(array $pointData, array $baseOptions): array
{
    $optionsById = [];
    foreach ($baseOptions as $option) {
        $optionId = (int)($option['id'] ?? 0);
        $optionLabel = trim((string)($option['label'] ?? ''));
        if ($optionId <= 0 || $optionLabel === '') {
            continue;
        }

        $optionsById[$optionId] = [
            'id' => $optionId,
            'label' => $optionLabel,
            'isLocal' => !empty($option['isLocal']),
        ];
    }

    $currentHolonId = (int)($pointData['concernedHolonId'] ?? 0);
    $currentHolonLabel = trim((string)($pointData['concernedHolonLabel'] ?? ''));
    if ($currentHolonId > 0 && $currentHolonLabel !== '' && !isset($optionsById[$currentHolonId])) {
        $optionsById[$currentHolonId] = [
            'id' => $currentHolonId,
            'label' => $currentHolonLabel,
            'isLocal' => false,
        ];
    }

    $pointData['concernedHolonOptions'] = array_values($optionsById);
    return $pointData;
}

function omoDocumentsPvEditorBuildAuthorHolonOptions(
    \dbObject\Document $document,
    array $authorOptions,
    bool $hasStructureApplication
): array {
    if (!$hasStructureApplication) {
        return [];
    }

    $optionsByAuthor = [];
    foreach ($authorOptions as $authorOption) {
        $authorValue = trim((string)($authorOption['value'] ?? ''));
        $authorUserId = (int)($authorOption['userId'] ?? 0);
        if ($authorValue === '') {
            continue;
        }

        $optionsByAuthor[$authorValue] = $authorUserId > 0
            ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, $authorUserId)
            : [];
    }

    return $optionsByAuthor;
}

function omoDocumentsPvEditorBuildPointDiscussionSummaryMap($organizationId, $points, $currentUserId): array
{
    $pointIds = [];
    foreach ($points as $point) {
        if ($point instanceof \dbObject\DocumentPvPoint && !$point->isGroup() && (int)$point->getId() > 0) {
            $pointIds[] = (int)$point->getId();
        }
    }

    return \dbObject\ChatThread::getSubjectDiscussionSummaries(
        (int)$organizationId,
        \dbObject\ChatThread::SUBJECT_DOCUMENT_PV_POINT,
        $pointIds,
        (int)$currentUserId
    );
}

function omoDocumentsPvEditorBuildContextualPointPayload(
    \dbObject\DocumentPvPoint $point,
    \dbObject\Document $document,
    int $organizationId,
    int $currentUserId,
    string $lockToken,
    array $uiText,
    bool $hasStructureApplication,
    array $authorOptions,
    array $authorHolonOptions,
    string $positionLabel,
    array $groupSummary = [],
    array $discussionSummary = []
): array {
    $pointData = $point->buildEditorData($organizationId, $currentUserId, $lockToken);
    $pointData['positionLabel'] = $positionLabel !== '' ? $positionLabel : '--';
    $pointData['documentId'] = (int)$document->getId();
    $pointData['organizationId'] = $organizationId;
    $pointData['isEditable'] = $document->canUserEditPvPoint($point, $currentUserId);
    $pointData['canEditNow'] = !empty($pointData['isEditable']) && empty($pointData['lock']['isLockedByOther']);
    $pointData['canReorder'] = $document->canUserReorderPvItem($point, $currentUserId);
    $pointData['canEditGroup'] = $point->isGroup() && $document->canUserCreatePvGroups($currentUserId);
    $pointData['isReview'] = $document->getPvStage() === \dbObject\Document::PV_STAGE_REVIEW;
    $pointData['discussionMessageCount'] = max(0, (int)($discussionSummary['total_messages'] ?? 0));
    $pointData['canDelete'] = !$pointData['isReview']
        && !$pointData['isHandled']
        && ($point->isGroup()
            ? $pointData['canEditGroup']
            : !empty($pointData['canEditNow']));
    $pointData['isPvEditor'] = $document->isPvEditor($currentUserId);
    $pointData['canTakeOverLock'] = $pointData['isPvEditor']
        && $document->canUserManagePvDocument($currentUserId)
        && !empty($pointData['lock']['isLockedByOther']);
    $pointData['canToggleHandled'] = !$pointData['isReview'] && $document->canUserManagePvDocument($currentUserId);
    $pointData['canAssignAuthor'] = $document->canUserManagePvDocument($currentUserId);
    $pointData['hasStructureApplication'] = $hasStructureApplication;
    $pointData['authorOptions'] = $authorOptions;
    $pointData['authorHolonOptions'] = $authorHolonOptions;
    if ($point->isGroup()) {
        $pointData['groupPointCount'] = (int)($groupSummary['pointCount'] ?? 0);
        $pointData['groupDurationMinutes'] = (int)($groupSummary['durationMinutes'] ?? 0);
    }

    $pointData = omoDocumentsPvEditorAttachConcernedHolonOptions(
        $pointData,
        $hasStructureApplication
            ? \dbObject\DocumentPvPoint::buildConcernedHolonOptionsForDocument($document, (int)$point->get('IDuser_author'))
            : []
    );

    return omoDocumentsPvEditorBuildPointPayload($pointData, $uiText);
}

function omoDocumentsPvEditorBuildAttendancePayloadFromDocument(\dbObject\Document $document, int $organizationId): ?array
{
    $event = method_exists($document, 'getAssociatedEvent')
        ? $document->getAssociatedEvent()
        : null;
    $entries = $event instanceof \dbObject\Event
        ? $event->getAttendanceEntries($organizationId)
        : $document->getInvitationAttendanceEntries($organizationId);
    $presentCount = 0;
    foreach ($entries as $entry) {
        if (!empty($entry['isPresent'])) {
            $presentCount++;
        }
    }

    return [
        'eventId' => $event instanceof \dbObject\Event ? (int)$event->getId() : 0,
        'presentCount' => $presentCount,
        'totalCount' => count($entries),
        'entries' => array_values(array_map(static function (array $entry): array {
            $displayLabel = trim((string)($entry['displayLabel'] ?? ''));
            $secondaryLabel = trim((string)($entry['secondaryLabel'] ?? ''));
            $email = trim((string)($entry['email'] ?? ''));
            if ($displayLabel === '') {
                $displayLabel = $secondaryLabel !== '' ? $secondaryLabel : $email;
                $secondaryLabel = '';
            }
            if ($secondaryLabel === '' && $email !== '' && $email !== $displayLabel) {
                $secondaryLabel = $email;
            }

            return [
                'identityKey' => (string)($entry['identityKey'] ?? ''),
                'displayLabel' => $displayLabel,
                'secondaryLabel' => $secondaryLabel,
                'isPresent' => !empty($entry['isPresent']),
            ];
        }, $entries)),
    ];
}

function omoDocumentsPvEditorRenderChipGroup(string $label, array $items, string $modifier = ''): string
{
    if (trim($label) === '' || count($items) === 0) {
        return '';
    }

    $modifierClass = trim($modifier) !== ''
        ? ' omo-document-pv__chip-group--' . preg_replace('/[^a-z0-9_-]+/i', '-', trim($modifier))
        : '';
    $html = '<div class="omo-document-pv__chip-group' . $modifierClass . '">';
    $html .= '<span class="omo-document-pv__chip-group-label">' . omoDocumentsPvEditorEscape($label) . '</span>';
    $html .= '<div class="omo-document-pv__chip-list">';

    foreach ($items as $item) {
        $itemLabel = trim((string)($item['label'] ?? ''));
        if ($itemLabel === '') {
            continue;
        }

        $html .= '<span class="omo-document-pv__chip">' . omoDocumentsPvEditorEscape($itemLabel) . '</span>';
    }

    $html .= '</div></div>';
    return $html;
}

function omoDocumentsPvEditorBuildGroupSummaryMap(iterable $points): array
{
    $itemsById = [];
    $childrenByParent = [];
    foreach ($points as $point) {
        if (!($point instanceof \dbObject\DocumentPvPoint) || (int)$point->getId() <= 0) {
            continue;
        }

        $pointId = (int)$point->getId();
        $parentId = (int)$point->get('IDparent');
        $itemsById[$pointId] = [
            'isGroup' => $point->isGroup(),
            'duration' => max(0, (int)$point->get('desired_duration_minutes')),
        ];
        $childrenByParent[$parentId > 0 ? $parentId : 0][] = $pointId;
    }

    $summaries = [];
    $buildSummary = static function (int $groupId, array $trail = []) use (&$buildSummary, &$summaries, $itemsById, $childrenByParent): array {
        if (isset($summaries[$groupId])) {
            return $summaries[$groupId];
        }
        if (isset($trail[$groupId])) {
            return ['pointCount' => 0, 'durationMinutes' => 0];
        }

        $trail[$groupId] = true;
        $summary = ['pointCount' => 0, 'durationMinutes' => 0];
        foreach ($childrenByParent[$groupId] ?? [] as $childId) {
            $child = $itemsById[$childId] ?? null;
            if (!is_array($child)) {
                continue;
            }
            if (!empty($child['isGroup'])) {
                $childSummary = $buildSummary($childId, $trail);
                $summary['pointCount'] += (int)$childSummary['pointCount'];
                $summary['durationMinutes'] += (int)$childSummary['durationMinutes'];
            } else {
                $summary['pointCount']++;
                $summary['durationMinutes'] += (int)$child['duration'];
            }
        }

        return $summaries[$groupId] = $summary;
    };

    foreach ($itemsById as $pointId => $item) {
        if (!empty($item['isGroup'])) {
            $buildSummary((int)$pointId);
        }
    }

    return $summaries;
}

function omoDocumentsPvEditorPointTypeIcons(): array
{
    return [
        'information' => '/omo/assets/images/documents/pv-point-type/information.png',
        'consultation' => '/omo/assets/images/documents/pv-point-type/consultation.png',
        'decision' => '/omo/assets/images/documents/pv-point-type/decision.png',
    ];
}

function omoDocumentsPvEditorRenderNavItem(array $pointData, array $uiText): string
{
    $pointId = (int)($pointData['id'] ?? 0);
    $title = trim((string)($pointData['title'] ?? ''));
    if ($title === '') {
        $title = !empty($pointData['isGroup'])
            ? (string)($uiText['defaultGroupTitle'] ?? 'Nouveau groupe')
            : (string)($uiText['defaultTitle'] ?? 'Nouveau point');
    }

    if (!empty($pointData['isGroup'])) {
        $reorderHandle = !empty($pointData['canReorder'])
            ? '<span class="omo-pv-editor__nav-handle generic-drag-handle generic-drag-handle--static" draggable="true" data-omo-pv-point-drag-handle="' . $pointId . '" title="' . omoDocumentsPvEditorEscape((string)($uiText['reorder'] ?? 'Reordonner les points')) . '">::</span>'
            : '<span class="omo-pv-editor__nav-handle omo-pv-editor__nav-handle--disabled" aria-hidden="true"></span>';
        $titleHtml = !empty($pointData['canEditGroup'])
            ? '<input type="text" class="omo-pv-editor__group-title-input" maxlength="80" value="' . omoDocumentsPvEditorEscape($title) . '" data-omo-pv-group-title="' . $pointId . '" aria-label="' . omoDocumentsPvEditorEscape((string)($uiText['title'] ?? 'Titre')) . '">'
            : '<strong class="omo-pv-editor__group-title">' . omoDocumentsPvEditorEscape($title) . '</strong>';
        $groupSummary = ((int)($pointData['groupPointCount'] ?? 0)) . ' ' . (string)($uiText['groupPoints'] ?? 'points')
            . ' | ' . ((int)($pointData['groupDurationMinutes'] ?? 0)) . ' ' . (string)($uiText['groupMinutes'] ?? 'min');

        $canDelete = !empty($pointData['canDelete']);
        return '<section class="omo-pv-editor__nav-group generic-stack generic-stack--compact" data-omo-pv-nav-node="' . $pointId . '" data-omo-pv-group="' . $pointId . '" data-omo-pv-parent-id="' . (int)($pointData['parentId'] ?? 0) . '" data-omo-pv-can-delete="' . ($canDelete ? '1' : '0') . '">'
            . '<div class="omo-pv-editor__group-head">'
            . $reorderHandle
            . '<button type="button" class="omo-pv-editor__group-toggle" data-omo-pv-group-toggle="' . $pointId . '" aria-expanded="true" title="' . omoDocumentsPvEditorEscape((string)($uiText['toggleGroup'] ?? 'Ouvrir ou fermer le groupe')) . '"><span aria-hidden="true">&#9662;</span></button>'
            . '<span class="omo-pv-editor__nav-order omo-pv-editor__group-order">' . omoDocumentsPvEditorEscape((string)($pointData['positionLabel'] ?? '--')) . '</span>'
            . '<span class="omo-pv-editor__group-copy">' . $titleHtml . '<span class="omo-pv-editor__group-summary">' . omoDocumentsPvEditorEscape($groupSummary) . '</span></span>'
            . '</div>'
            . '<div class="omo-pv-editor__group-children generic-stack generic-stack--compact" data-omo-pv-nav-children="' . $pointId . '"></div>'
            . '</section>';
    }

    $author = trim((string)($pointData['authorLabel'] ?? ''));
    $pointType = trim((string)($pointData['pointType'] ?? 'information'));
    $pointTypeIcons = omoDocumentsPvEditorPointTypeIcons();
    $pointTypeIcon = (string)($pointTypeIcons[$pointType] ?? $pointTypeIcons['information']);
    $pointTypeLabel = trim((string)($pointData['pointTypeLabel'] ?? ($uiText[$pointType] ?? $pointType)));
    $metaParts = [];
    $metaParts[] = $author !== '' ? $author : (string)($uiText['readonly'] ?? 'Lecture seule');
    $metaParts[] = omoDocumentsPvEditorDurationLabel(
        isset($pointData['desiredDurationMinutes']) ? (int)$pointData['desiredDurationMinutes'] : null,
        $uiText
    );
    if (!empty($pointData['lock']['isLockedByOther']) && trim((string)($pointData['lock']['userLabel'] ?? '')) !== '') {
        $metaParts[] = str_replace('{user}', trim((string)$pointData['lock']['userLabel']), (string)($uiText['lockedOther'] ?? 'Édition en cours par {user}.'));
    }

    $reorderHandle = !empty($pointData['canReorder'])
        ? '  <span class="omo-pv-editor__nav-handle generic-drag-handle generic-drag-handle--static" draggable="true" data-omo-pv-point-drag-handle="' . $pointId . '" title="' . omoDocumentsPvEditorEscape((string)($uiText['reorder'] ?? 'Reordonner les points')) . '" aria-label="' . omoDocumentsPvEditorEscape((string)($uiText['reorder'] ?? 'Reordonner les points')) . '">::</span>'
        : '  <span class="omo-pv-editor__nav-handle omo-pv-editor__nav-handle--disabled" aria-hidden="true"></span>';
    $handledDisabled = empty($pointData['canToggleHandled']) ? ' disabled' : '';

    $canDelete = !empty($pointData['canDelete']);
    return '<div class="omo-pv-editor__nav-row' . (!empty($pointData['isHandled']) ? ' is-handled' : '') . '" data-omo-pv-nav-node="' . $pointId . '" data-omo-pv-parent-id="' . (int)($pointData['parentId'] ?? 0) . '" data-omo-pv-point-nav-row="' . $pointId . '" data-omo-pv-can-delete="' . ($canDelete ? '1' : '0') . '">'
        . $reorderHandle
        . '  <button type="button" class="omo-pv-editor__nav-item" data-omo-pv-point-nav-target="' . $pointId . '">'
        . '      <span class="omo-pv-editor__nav-titleline">'
        . '          <span class="omo-pv-editor__nav-order">' . omoDocumentsPvEditorEscape((string)($pointData['positionLabel'] ?? '--')) . '</span>'
        . '          <img src="' . omoDocumentsPvEditorEscape($pointTypeIcon) . '" alt="' . omoDocumentsPvEditorEscape($pointTypeLabel) . '" class="omo-pv-editor__nav-point-type-icon" data-omo-pv-point-nav-type-icon="' . $pointId . '">'
        . '          <strong class="omo-pv-editor__nav-title">' . omoDocumentsPvEditorEscape($title) . '</strong>'
        . '      </span>'
        . '      <span class="omo-pv-editor__nav-meta">' . omoDocumentsPvEditorEscape(implode(' | ', $metaParts)) . '</span>'
        . '  </button>'
        . '  <div class="omo-pv-editor__nav-actions">'
        . '  <label class="omo-pv-editor__nav-check" title="' . omoDocumentsPvEditorEscape((string)($uiText['handled'] ?? 'Traité')) . '">'
        . '      <input type="checkbox" data-omo-pv-point-handled="' . $pointId . '"' . (!empty($pointData['isHandled']) ? ' checked' : '') . $handledDisabled . '>'
        . '      <span class="omo-pv-editor__nav-check-label">' . omoDocumentsPvEditorEscape((string)($uiText['handled'] ?? 'Traité')) . '</span>'
        . '  </label>'
        . '  </div>'
        . '</div>';
}

function omoDocumentsPvEditorRenderPointDiscussionTrigger(array $pointData, array $uiText, string $pointTitle): string
{
    if (empty($pointData['isReview'])) {
        return '';
    }

    $pointId = (int)($pointData['id'] ?? 0);
    $documentId = (int)($pointData['documentId'] ?? 0);
    $organizationId = (int)($pointData['organizationId'] ?? 0);
    if ($pointId <= 0 || $documentId <= 0 || $organizationId <= 0) {
        return '';
    }

    $context = json_encode([
        'oid' => $organizationId,
        'document_id' => $documentId,
        'point_id' => $pointId,
    ], JSON_UNESCAPED_SLASHES);
    $labels = json_encode([
        'loading' => (string)($uiText['chatLoading'] ?? ''),
        'empty' => (string)($uiText['chatEmpty'] ?? ''),
        'placeholder' => (string)($uiText['chatPlaceholder'] ?? ''),
        'send' => (string)($uiText['chatSend'] ?? ''),
        'messageCount' => (string)($uiText['chatMessageCount'] ?? ''),
        'changeDetails' => (string)($uiText['chatChangeDetails'] ?? ''),
        'contentExcerpt' => (string)($uiText['chatContentExcerpt'] ?? ''),
    ], JSON_UNESCAPED_SLASHES);
    $messageCount = max(0, (int)($pointData['discussionMessageCount'] ?? 0));
    $messageCountLabel = str_replace(
        '{count}',
        (string)$messageCount,
        (string)($uiText['chatMessageCount'] ?? 'Nombre de messages : {count}')
    );
    $triggerAttributes = ' data-omo-chat-open'
        . ' data-omo-chat-endpoint="/omo/api/documents/pv/discussion.php"'
        . ' data-omo-chat-context="' . omoDocumentsPvEditorEscape((string)$context) . '"'
        . ' data-omo-chat-title="' . omoDocumentsPvEditorEscape((string)($uiText['chatPointTitle'] ?? 'Erreurs signalées pour ce point')) . '"'
        . ' data-omo-chat-point-title="' . omoDocumentsPvEditorEscape($pointTitle) . '"'
        . ' data-omo-chat-labels="' . omoDocumentsPvEditorEscape((string)$labels) . '"'
        . ' data-omo-chat-message-count="' . $messageCount . '"';

    return '<div class="omo-chat-popup-actions">'
        . '<span class="omo-chat-popup-count" data-omo-chat-message-count-display title="' . omoDocumentsPvEditorEscape($messageCountLabel) . '" aria-label="' . omoDocumentsPvEditorEscape($messageCountLabel) . '">'
        . '<span class="omo-chat-popup-count-value">' . $messageCount . '</span></span>'
        . '<button type="button" class="generic-action-button generic-action-button--main omo-pv-editor__save-button omo-chat-popup-trigger"'
        . $triggerAttributes
        . ' title="' . omoDocumentsPvEditorEscape((string)($uiText['chatOpen'] ?? 'Signaler des erreurs sur ce point')) . '"'
        . ' aria-label="' . omoDocumentsPvEditorEscape((string)($uiText['chatOpen'] ?? 'Signaler des erreurs sur ce point')) . '">'
        . omoDocumentsPvEditorEscape((string)($uiText['chatReportErrors'] ?? 'Signaler des erreurs'))
        . '</button></div>';
}

function omoDocumentsPvEditorRenderPointCard(array $pointData, array $uiText): string
{
    if (!empty($pointData['isGroup'])) {
        return '';
    }
    $pointId = (int)($pointData['id'] ?? 0);
    $pointType = trim((string)($pointData['pointType'] ?? 'information'));
    $pointTypeLabel = trim((string)($pointData['pointTypeLabel'] ?? ($uiText[$pointType] ?? $pointType)));
    $pointTypeIcons = omoDocumentsPvEditorPointTypeIcons();
    $pointTypeIcon = (string)($pointTypeIcons[$pointType] ?? $pointTypeIcons['information']);
    $title = trim((string)($pointData['title'] ?? ''));
    if ($title === '') {
        $title = (string)($uiText['defaultTitle'] ?? 'Nouveau point');
    }

    $isEditable = !empty($pointData['isEditable']);
    $canEditNow = !empty($pointData['canEditNow']);
    $canAssignAuthor = !empty($pointData['canAssignAuthor']);
    $canReorder = !empty($pointData['canReorder']);
    $isReview = !empty($pointData['isReview']);
    $canEditDuration = $canEditNow && !$isReview;
    $canEditConfidential = $canEditNow && !$isReview;
    $chips = '';
    $addressedHolons = is_array($pointData['addressedHolons'] ?? null) ? $pointData['addressedHolons'] : [];
    $tensions = is_array($pointData['tensions'] ?? null) ? $pointData['tensions'] : [];
    if (count($addressedHolons) > 0) {
        $chips .= omoDocumentsPvEditorRenderChipGroup((string)$uiText['addressedHolons'], $addressedHolons, 'holons');
    }
    if (count($tensions) > 0) {
        $chips .= omoDocumentsPvEditorRenderChipGroup((string)$uiText['tensions'], $tensions, 'tensions');
    }

    $authorLabel = trim((string)($pointData['authorLabel'] ?? ''));
    $authorValue = trim((string)($pointData['authorValue'] ?? ''));
    $authorOptions = is_array($pointData['authorOptions'] ?? null) ? $pointData['authorOptions'] : [];
    $authorHolonOptions = is_array($pointData['authorHolonOptions'] ?? null) ? $pointData['authorHolonOptions'] : [];
    $concernedHolonId = (int)($pointData['concernedHolonId'] ?? 0);
    $concernedHolonOptions = is_array($pointData['concernedHolonOptions'] ?? null) ? $pointData['concernedHolonOptions'] : [];
    $durationValue = isset($pointData['desiredDurationMinutes']) ? (int)$pointData['desiredDurationMinutes'] : 0;
    $durationLabel = omoDocumentsPvEditorDurationLabel($durationValue, $uiText);
    $updateInfo = '';
    if (
        trim((string)($pointData['lastModifiedByLabel'] ?? '')) !== ''
        && (int)($pointData['lastModifiedByUserId'] ?? 0) > 0
    ) {
        $updateInfo = str_replace('{user}', trim((string)$pointData['lastModifiedByLabel']), (string)($uiText['updatedBy'] ?? 'Mis à jour par {user}.'));
    }

    $html = '<article class="omo-pv-editor__point-card omo-document-pv__point' . ($canEditNow ? ' is-editable' : ' is-readonly') . '"'
        . ' id="omo-pv-editor-point-' . $pointId . '"'
        . ' data-omo-pv-point-card="' . $pointId . '"'
        . ' data-omo-pv-point-editable="' . ($canEditNow ? '1' : '0') . '"'
        . ' data-omo-pv-point-id="' . $pointId . '">';
    $html .= '<header class="omo-document-pv__point-head">';
    $html .= '  <div class="omo-document-pv__point-main">';
    $html .= '    <div class="omo-document-pv__point-topline">';
    $html .= '      <span class="omo-document-pv__point-order">' . omoDocumentsPvEditorEscape((string)($pointData['positionLabel'] ?? '--')) . '</span>';
    if ($canEditNow) {
        $html .= '      <input type="text" class="omo-pv-editor__point-title-input" maxlength="80" value="' . omoDocumentsPvEditorEscape($title) . '" data-omo-pv-point-title="' . $pointId . '" aria-label="' . omoDocumentsPvEditorEscape((string)$uiText['title']) . '">';
        if ($canEditDuration) {
            $html .= '      <label class="omo-pv-editor__point-duration-shell" title="' . omoDocumentsPvEditorEscape((string)$uiText['duration']) . '">';
            $html .= '          <input type="number" min="0" step="1" class="omo-pv-editor__point-duration-input" value="' . omoDocumentsPvEditorEscape($durationValue > 0 ? (string)$durationValue : '') . '" data-omo-pv-point-duration="' . $pointId . '" aria-label="' . omoDocumentsPvEditorEscape((string)$uiText['duration']) . '">';
            $html .= '          <span>min</span>';
            $html .= '      </label>';
        } else {
            $html .= '      <span class="omo-pv-editor__point-duration-readonly">' . omoDocumentsPvEditorEscape($durationLabel) . '</span>';
        }
        $html .= '      <input type="hidden" value="' . omoDocumentsPvEditorEscape($pointType) . '" data-omo-pv-point-type="' . $pointId . '">';
        $html .= '      <div class="omo-segmented omo-pv-editor__type-switch" role="radiogroup" aria-label="' . omoDocumentsPvEditorEscape((string)$uiText['type']) . '">';
        foreach ([
            'information' => (string)$uiText['information'],
            'consultation' => (string)$uiText['consultation'],
            'decision' => (string)$uiText['decision'],
        ] as $optionValue => $optionLabel) {
            $optionIcon = (string)($pointTypeIcons[$optionValue] ?? $pointTypeIcons['information']);
            $isSelected = $optionValue === $pointType;
            $html .= '<button type="button" class="omo-segmented__button omo-pv-editor__type-switch-button' . ($isSelected ? ' is-active' : '') . '"'
                . ' data-omo-pv-point-type-option="' . $pointId . '"'
                . ' data-omo-pv-point-type-value="' . omoDocumentsPvEditorEscape($optionValue) . '"'
                . ' role="radio"'
                . ' aria-checked="' . ($isSelected ? 'true' : 'false') . '"'
                . ' tabindex="' . ($isSelected ? '0' : '-1') . '"'
                . ' title="' . omoDocumentsPvEditorEscape($optionLabel) . '"'
                . ' aria-label="' . omoDocumentsPvEditorEscape($optionLabel) . '">'
                . '<img src="' . omoDocumentsPvEditorEscape($optionIcon) . '" alt="" aria-hidden="true" class="omo-pv-editor__point-type-icon">'
                . '<span class="omo-pv-editor__sr-only">' . omoDocumentsPvEditorEscape($optionLabel) . '</span>'
                . '</button>';
        }
        $html .= '      </div>';
    } else {
        $html .= '      <h3 class="omo-document-pv__point-title">' . omoDocumentsPvEditorEscape($title) . '</h3>';
        $html .= '      <span class="omo-pv-editor__point-duration-readonly">' . omoDocumentsPvEditorEscape($durationLabel) . '</span>';
        $html .= '      <span class="omo-document-pv__point-type omo-document-pv__point-type--' . omoDocumentsPvEditorEscape($pointType) . '">';
        $html .= '          <img src="' . omoDocumentsPvEditorEscape($pointTypeIcon) . '" alt="" aria-hidden="true" class="omo-document-pv__point-type-icon omo-pv-editor__point-type-icon">';
        $html .= '          <span>' . omoDocumentsPvEditorEscape($pointTypeLabel) . '</span>';
        $html .= '      </span>';
    }
    if (!empty($pointData['lock']['isLockedByOther'])) {
        $html .= '      <span class="omo-pv-editor__point-ownership">' . omoDocumentsPvEditorEscape((string)$uiText['lockedState']) . '</span>';
    } elseif (!empty($pointData['isHandled'])) {
        $html .= '      <span class="omo-pv-editor__point-ownership">' . omoDocumentsPvEditorEscape((string)$uiText['handledState']) . '</span>';
    }
    if ($canReorder) {
        $html .= '      <span class="omo-pv-editor__point-reorder-actions">';
        $html .= '          <button type="button" class="omo-pv-editor__point-move-button" data-omo-pv-point-move="' . $pointId . '" data-omo-pv-point-move-direction="up" title="' . omoDocumentsPvEditorEscape((string)($uiText['moveUp'] ?? 'Monter')) . '" aria-label="' . omoDocumentsPvEditorEscape((string)($uiText['moveUp'] ?? 'Monter')) . '">&uarr;</button>';
        $html .= '          <button type="button" class="omo-pv-editor__point-move-button" data-omo-pv-point-move="' . $pointId . '" data-omo-pv-point-move-direction="down" title="' . omoDocumentsPvEditorEscape((string)($uiText['moveDown'] ?? 'Descendre')) . '" aria-label="' . omoDocumentsPvEditorEscape((string)($uiText['moveDown'] ?? 'Descendre')) . '">&darr;</button>';
        $html .= '      </span>';
    }
    $html .= '    </div>';
    $html .= '    <div class="omo-pv-editor__point-meta-line">';
    if ($canAssignAuthor) {
        $html .= '      <label class="omo-pv-editor__point-author-select-shell">';
        $html .= '          <span class="omo-pv-editor__point-concerned-label">' . omoDocumentsPvEditorEscape((string)$uiText['author']) . '</span>';
        $html .= '          <select class="omo-pv-editor__point-concerned-select" data-omo-pv-point-author="' . $pointId . '" data-omo-pv-point-author-holons="' . omoDocumentsPvEditorEscape((string)json_encode($authorHolonOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '">';
        foreach ($authorOptions as $option) {
            $optionValue = trim((string)($option['value'] ?? ''));
            $optionLabel = trim((string)($option['label'] ?? ''));
            if ($optionValue === '' || $optionLabel === '') {
                continue;
            }
            $html .= '<option value="' . omoDocumentsPvEditorEscape($optionValue) . '"' . ($optionValue === $authorValue ? ' selected' : '') . '>' . omoDocumentsPvEditorEscape($optionLabel) . '</option>';
        }
        $html .= '          </select>';
        $html .= '      </label>';
    } else {
        $html .= '      <span class="omo-pv-editor__point-author">' . omoDocumentsPvEditorEscape($authorLabel !== '' ? $authorLabel : (string)($uiText['readonly'] ?? 'Lecture seule')) . '</span>';
    }
    if ($canEditNow && !empty($pointData['hasStructureApplication'])) {
        $html .= '      <label class="omo-pv-editor__point-concerned">';
        $html .= '          <span class="omo-pv-editor__point-concerned-label">' . omoDocumentsPvEditorEscape((string)$uiText['concernedHolon']) . '</span>';
        $html .= '          <select class="omo-pv-editor__point-concerned-select" data-omo-pv-point-concerned-holon="' . $pointId . '" aria-label="' . omoDocumentsPvEditorEscape((string)$uiText['concernedHolon']) . '">';
        $html .= '              <option value="0">' . omoDocumentsPvEditorEscape((string)($uiText['concernedHolonEmpty'] ?? 'Sans rôle')) . '</option>';
        $localConcernedHolonCount = count(array_filter($concernedHolonOptions, static function (array $option): bool {
            return !empty($option['isLocal']);
        }));
        $hasLocalConcernedHolon = $localConcernedHolonCount > 0;
        $hasExternalConcernedHolon = count($concernedHolonOptions) > $localConcernedHolonCount;
        $externalSeparatorRendered = false;
        foreach ($concernedHolonOptions as $option) {
            $optionId = (int)($option['id'] ?? 0);
            $optionLabel = trim((string)($option['label'] ?? ''));
            if ($optionId <= 0 || $optionLabel === '') {
                continue;
            }
            if ($hasLocalConcernedHolon && $hasExternalConcernedHolon && empty($option['isLocal']) && !$externalSeparatorRendered) {
                $html .= '<option disabled aria-hidden="true">----------</option>';
                $externalSeparatorRendered = true;
            }
            $html .= '<option value="' . $optionId . '"' . ($optionId === $concernedHolonId ? ' selected' : '') . '>' . omoDocumentsPvEditorEscape($optionLabel) . '</option>';
        }
        $html .= '          </select>';
        $html .= '      </label>';
    } elseif (!empty($pointData['hasStructureApplication']) && trim((string)($pointData['concernedHolonLabel'] ?? '')) !== '') {
        $html .= '      <span class="omo-pv-editor__point-concerned-readonly">' . omoDocumentsPvEditorEscape(trim((string)$pointData['concernedHolonLabel'])) . '</span>';
    }
    if ($canEditConfidential) {
        $html .= '      <label class="omo-pv-editor__point-confidential" title="' . omoDocumentsPvEditorEscape((string)$uiText['confidentialHint']) . '">';
        $html .= '          <input type="checkbox" data-omo-pv-point-confidential="' . $pointId . '"' . (!empty($pointData['isConfidential']) ? ' checked' : '') . '>';
        $html .= '          <span>' . omoDocumentsPvEditorEscape((string)$uiText['confidential']) . '</span>';
        $html .= '      </label>';
    } elseif (!empty($pointData['isConfidential'])) {
        $html .= '      <span class="omo-pv-editor__point-confidential">' . omoDocumentsPvEditorEscape((string)$uiText['confidential']) . '</span>';
    }
    $html .= '    </div>';
    if ($chips !== '') {
        $html .= '<div class="omo-document-pv__point-chips">' . $chips . '</div>';
    }
    $html .= '  </div>';
    $html .= '</header>';

    if ($canEditNow) {
        $html .= '<div class="omo-pv-editor__editor-block generic-stack generic-stack--compact">';
        $html .= '  <div class="omo-pv-editor__editor-host" data-omo-pv-point-editor-host="' . $pointId . '"></div>';
        $html .= '  <textarea hidden data-omo-pv-point-content-source="' . $pointId . '">' . omoDocumentsPvEditorEscape((string)($pointData['contentRaw'] ?? '')) . '</textarea>';
        $html .= '</div>';
        $html .= '<div class="omo-pv-editor__point-footer">';
        $footerNoteParts = [(!empty($pointData['isPvEditor']) || !empty($pointData['canAssignAuthor']))
            ? (string)$uiText['pvEditorCanEdit']
            : (string)$uiText['ownerOnly']];
        if ($updateInfo !== '') {
            $footerNoteParts[] = $updateInfo;
        }
        $html .= '  <span class="omo-pv-editor__point-note">' . omoDocumentsPvEditorEscape(implode(' | ', $footerNoteParts)) . '</span>';
        $html .= '  <div class="omo-pv-editor__point-actions">';
        $html .= '    <button type="button" class="generic-action-button omo-pv-editor__save-button" data-omo-pv-point-save="' . $pointId . '" disabled aria-disabled="true">' . omoDocumentsPvEditorEscape((string)$uiText['saved']) . '</button>';
        if (!empty($pointData['canDelete'])) {
            $html .= '    <button type="button" class="omo-pv-editor__delete-button" data-omo-pv-point-delete="' . $pointId . '" title="' . omoDocumentsPvEditorEscape((string)($uiText['deletePoint'] ?? 'Supprimer le point')) . '" aria-label="' . omoDocumentsPvEditorEscape((string)($uiText['deletePoint'] ?? 'Supprimer le point')) . '"><img src="/omo/assets/images/documents/poubelle.png" alt="" aria-hidden="true"></button>';
        }
        $html .= omoDocumentsPvEditorRenderPointDiscussionTrigger($pointData, $uiText, $title);
        $html .= '  </div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="omo-pv-editor__point-footer omo-pv-editor__point-footer--readonly">';
        if (!empty($pointData['isHandled'])) {
            $readonlyNote = (string)$uiText['handledState'];
        } elseif (!empty($pointData['lock']['isLockedByOther'])) {
            $readonlyNote = str_replace(
                '{user}',
                trim((string)($pointData['lock']['userLabel'] ?? '')) !== '' ? trim((string)$pointData['lock']['userLabel']) : (string)($uiText['readonly'] ?? 'Lecture seule'),
                (string)($uiText['lockedOther'] ?? 'Édition en cours par {user}.')
            );
        } else {
            $readonlyNote = (string)$uiText['readonlyNotice'];
        }
        if ($updateInfo !== '') {
            $readonlyNote .= ' | ' . $updateInfo;
        }
        $html .= '  <span class="omo-pv-editor__point-note">' . omoDocumentsPvEditorEscape($readonlyNote) . '</span>';
        if (!empty($pointData['canTakeOverLock'])) {
            $html .= '  <button type="button" class="generic-action-button generic-action-button--main omo-pv-editor__take-over-lock" data-omo-pv-point-take-over-lock="' . $pointId . '">' . omoDocumentsPvEditorEscape((string)($uiText['takeOverLock'] ?? 'Reprendre l’édition')) . '</button>';
        }
        $html .= omoDocumentsPvEditorRenderPointDiscussionTrigger($pointData, $uiText, $title);
        $html .= '</div>';
        $html .= '<div class="omo-document-pv__point-content prose omo-simple-html-render">' . (string)($pointData['contentHtml'] ?? '') . '</div>';
    }

    $html .= '</article>';

    return $html;
}

function omoDocumentsPvEditorBuildPointPayload(array $pointData, array $uiText): array
{
    $lockData = is_array($pointData['lock'] ?? null) ? $pointData['lock'] : [];
    $authorOptionValues = [];
    foreach ((array)($pointData['authorOptions'] ?? []) as $authorOption) {
        $authorOptionValues[] = trim((string)($authorOption['value'] ?? ''));
    }

    return [
        'id' => (int)($pointData['id'] ?? 0),
        'itemType' => (string)($pointData['itemType'] ?? 'point'),
        'isGroup' => !empty($pointData['isGroup']),
        'parentId' => (int)($pointData['parentId'] ?? 0),
        'canReorder' => !empty($pointData['canReorder']),
        'canEditGroup' => !empty($pointData['canEditGroup']),
        'isReview' => !empty($pointData['isReview']),
        'canTakeOverLock' => !empty($pointData['canTakeOverLock']),
        'title' => (string)($pointData['title'] ?? ''),
        'authorValue' => (string)($pointData['authorValue'] ?? ''),
        'position' => (int)($pointData['position'] ?? 0),
        'concernedHolonId' => (int)($pointData['concernedHolonId'] ?? 0),
        'desiredDurationMinutes' => isset($pointData['desiredDurationMinutes']) ? (int)$pointData['desiredDurationMinutes'] : 0,
        'isHandled' => !empty($pointData['isHandled']),
        'isConfidential' => !empty($pointData['isConfidential']),
        'syncVersion' => hash('sha256', implode('|', [
            (string)($pointData['syncVersion'] ?? ''),
            implode('|', $authorOptionValues),
            !empty($pointData['isEditable']) ? '1' : '0',
            !empty($pointData['canEditNow']) ? '1' : '0',
            !empty($pointData['canReorder']) ? '1' : '0',
            !empty($pointData['canEditGroup']) ? '1' : '0',
            !empty($pointData['canTakeOverLock']) ? '1' : '0',
            !empty($pointData['canToggleHandled']) ? '1' : '0',
            !empty($pointData['canAssignAuthor']) ? '1' : '0',
            !empty($pointData['isPvEditor']) ? '1' : '0',
        ])),
        'lastModifiedAtIso' => (string)($pointData['lastModifiedAtIso'] ?? ''),
        'lastModifiedAtTimestamp' => (int)($pointData['lastModifiedAtTimestamp'] ?? 0),
        'lastModifiedByUserId' => (int)($pointData['lastModifiedByUserId'] ?? 0),
        'lock' => [
            'isActive' => !empty($lockData['isActive']),
            'isLockedByOther' => !empty($lockData['isLockedByOther']),
            'isOwnedByCurrentUser' => !empty($lockData['isOwnedByCurrentUser']),
            'isOwnedByCurrentSession' => !empty($lockData['isOwnedByCurrentSession']),
            'userId' => (int)($lockData['userId'] ?? 0),
            'userLabel' => (string)($lockData['userLabel'] ?? ''),
            'dateIso' => (string)($lockData['dateIso'] ?? ''),
            'timestamp' => (int)($lockData['timestamp'] ?? 0),
        ],
        'cardHtml' => omoDocumentsPvEditorRenderPointCard($pointData, $uiText),
        'navHtml' => omoDocumentsPvEditorRenderNavItem($pointData, $uiText),
    ];
}
