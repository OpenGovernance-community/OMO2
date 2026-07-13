<?php

function omoDocumentsPvEditorSourceLang(): array
{
    return [
        'documents.pv_editor.page.title' => ['text' => 'Preparation du PV', 'context' => 'Title shown for the upcoming PV editor.'],
        'documents.pv_editor.page.description' => ['text' => 'Le PV suit plusieurs etapes, et chaque personne edite uniquement les points qu elle a crees quand cela est autorise.', 'context' => 'Description shown in the PV editor header.'],
        'documents.pv_editor.error.unavailable' => ['text' => 'Cet editeur PV n est pas disponible pour ce document.', 'context' => 'Error shown when the PV editor cannot be used.'],
        'documents.pv_editor.error.forbidden' => ['text' => 'Vous ne pouvez pas ouvrir cet editeur PV.', 'context' => 'Error shown when the current viewer cannot use the PV editor.'],
        'documents.pv_editor.action.add_point' => ['text' => 'Ajouter un point', 'context' => 'Button used to add a new agenda point in the PV editor.'],
        'documents.pv_editor.action.save' => ['text' => 'Enregistrer', 'context' => 'Button used to save a PV point.'],
        'documents.pv_editor.action.saving' => ['text' => 'Enregistrement...', 'context' => 'Temporary label while saving a PV point.'],
        'documents.pv_editor.action.reordering' => ['text' => 'Reorganisation...', 'context' => 'Temporary label while PV points are being reordered.'],
        'documents.pv_editor.state.saved' => ['text' => 'Enregistre', 'context' => 'State label shown after a PV point has been saved.'],
        'documents.pv_editor.state.dirty' => ['text' => 'Modifications non enregistrees', 'context' => 'State label shown when a PV point has local unsaved changes.'],
        'documents.pv_editor.state.readonly' => ['text' => 'Lecture seule', 'context' => 'Badge shown on points created by other users.'],
        'documents.pv_editor.state.mine' => ['text' => 'Mon point', 'context' => 'Badge shown on points owned by the current user.'],
        'documents.pv_editor.state.handled' => ['text' => 'Traite', 'context' => 'State label shown when a PV point has been handled.'],
        'documents.pv_editor.state.locked' => ['text' => 'Verrouille', 'context' => 'State badge shown when a PV point is locked by another session.'],
        'documents.pv_editor.field.title' => ['text' => 'Titre', 'context' => 'Label for the PV point title field.'],
        'documents.pv_editor.field.type' => ['text' => 'Type', 'context' => 'Label for the PV point type field.'],
        'documents.pv_editor.field.author' => ['text' => 'Auteur', 'context' => 'Label showing the author of a PV point.'],
        'documents.pv_editor.field.duration' => ['text' => 'Duree estimee', 'context' => 'Label showing the desired duration of a PV point.'],
        'documents.pv_editor.field.duration_short' => ['text' => '{minutes} min', 'context' => 'Short duration label used in compact PV point rows.'],
        'documents.pv_editor.field.duration_empty' => ['text' => '-- min', 'context' => 'Fallback duration label used when no desired duration is set.'],
        'documents.pv_editor.field.stage' => ['text' => 'Etape', 'context' => 'Label of the PV workflow stage selector.'],
        'documents.pv_editor.field.document_title' => ['text' => 'Titre du PV', 'context' => 'Label for the editable PV document title.'],
        'documents.pv_editor.field.document_description' => ['text' => 'Description', 'context' => 'Label for the editable PV document description.'],
        'documents.pv_editor.field.document_visibility' => ['text' => 'Visibilite', 'context' => 'Label for the PV document view visibility selector.'],
        'documents.pv_editor.state.metadata_dirty' => ['text' => 'Modifications non enregistrees', 'context' => 'State shown when PV document metadata has local changes.'],
        'documents.pv_editor.field.stage.preparation' => ['text' => 'Preparation', 'context' => 'Option label for the preparation stage of a PV.'],
        'documents.pv_editor.field.stage.meeting' => ['text' => 'Reunion', 'context' => 'Option label for the meeting stage of a PV.'],
        'documents.pv_editor.field.stage.review' => ['text' => 'Relecture', 'context' => 'Option label for the review stage of a PV.'],
        'documents.pv_editor.field.stage.validated' => ['text' => 'Valide', 'context' => 'Option label for the validated stage of a PV.'],
        'documents.pv_editor.field.attendance' => ['text' => 'Liste de presence', 'context' => 'Label shown above the attendance checklist of invited people in the PV editor header.'],
        'documents.pv_editor.field.attendance_empty' => ['text' => 'Aucune personne invitee pour le moment.', 'context' => 'Empty state shown when the event linked to the PV has no resolved invitees.'],
        'documents.pv_editor.field.attendance_present' => ['text' => 'Present', 'context' => 'Checkbox label used to mark an invited person as present in the PV editor.'],
        'documents.pv_editor.field.attendance_count' => ['text' => '{present}/{total} presents', 'context' => 'Compact summary shown next to the attendance checklist title in the PV editor.'],
        'documents.pv_editor.field.pv_editor' => ['text' => 'Editeur du PV', 'context' => 'Label of the person acting as PV secretary.'],
        'documents.pv_editor.action.claim_pv_editor' => ['text' => 'Devenir editeur', 'context' => 'Button used to claim the PV secretary role.'],
        'documents.pv_editor.action.invite' => ['text' => 'Inviter', 'context' => 'Button used by the PV secretary to edit the event invitation list during preparation.'],
        'documents.pv_editor.popup.invite_title' => ['text' => 'Invites', 'context' => 'Title of the invitation popup opened from the PV editor.'],
        'documents.pv_editor.notice.pv_editor_empty' => ['text' => 'Aucun editeur attribue.', 'context' => 'State shown when no one has claimed the PV secretary role.'],
        'documents.pv_editor.notice.pv_editor_active' => ['text' => 'Vous etes editeur du PV.', 'context' => 'State shown to the PV secretary.'],
        'documents.pv_editor.notice.pv_editor_can_edit' => ['text' => 'Vous pouvez modifier ce point car vous etes l editeur du PV.', 'context' => 'Helper text shown when the PV secretary edits a point.'],
        'documents.pv_editor.field.author' => ['text' => 'Porte par', 'context' => 'Label of the person assigned to a PV point.'],
        'documents.pv_editor.field.handled' => ['text' => 'Traite', 'context' => 'Label for the handled checkbox of a PV point.'],
        'documents.pv_editor.field.concerned_holon' => ['text' => 'Holon concerne', 'context' => 'Label showing the main holon concerned by a PV point.'],
        'documents.pv_editor.field.concerned_holon_empty' => ['text' => 'Sans role', 'context' => 'Empty option for the concerned role selector of a PV point.'],
        'documents.pv_editor.field.addressed_holons' => ['text' => 'Holons adresses', 'context' => 'Label showing the addressed holons of a PV point.'],
        'documents.pv_editor.field.tensions' => ['text' => 'Tensions', 'context' => 'Label showing the tensions attached to a PV point.'],
        'documents.pv_editor.field.content' => ['text' => 'Contenu', 'context' => 'Label shown above the HTML content editor of a PV point.'],
        'documents.pv_editor.field.pointtype.information' => ['text' => 'Information', 'context' => 'Option label for an informational PV point.'],
        'documents.pv_editor.field.pointtype.consultation' => ['text' => 'Consultation', 'context' => 'Option label for a consultation PV point.'],
        'documents.pv_editor.field.pointtype.decision' => ['text' => 'Decision', 'context' => 'Option label for a decision PV point.'],
        'documents.pv_editor.nav.empty' => ['text' => 'Aucun point pour le moment.', 'context' => 'Empty state shown in the left navigation when the PV has no points.'],
        'documents.pv_editor.point.default_title' => ['text' => 'Nouveau point', 'context' => 'Default title used when creating a fresh PV point.'],
        'documents.pv_editor.notice.schedule' => ['text' => 'Reunion prevue le {date}.', 'context' => 'Schedule sentence shown in the PV editor header.'],
        'documents.pv_editor.notice.event' => ['text' => 'Evenement associe', 'context' => 'Label used in the PV editor header for the linked event.'],
        'documents.pv_editor.notice.location' => ['text' => 'Lieu', 'context' => 'Label used in the PV editor header for the event location.'],
        'documents.pv_editor.notice.reorder' => ['text' => 'Reordonner les points', 'context' => 'Title for the PV point reorder handle.'],
        'documents.pv_editor.notice.move_up' => ['text' => 'Monter', 'context' => 'Title for the touch-friendly move up button.'],
        'documents.pv_editor.notice.move_down' => ['text' => 'Descendre', 'context' => 'Title for the touch-friendly move down button.'],
        'documents.pv_editor.notice.owner_only' => ['text' => 'Vous pouvez modifier ce point car vous en etes l auteur.', 'context' => 'Helper text shown on editable PV points.'],
        'documents.pv_editor.notice.readonly' => ['text' => 'Vous pouvez consulter ce point, mais seul son auteur peut le modifier avant la reunion.', 'context' => 'Helper text shown on read-only PV points.'],
        'documents.pv_editor.notice.locked_other' => ['text' => 'Edition en cours par {user}.', 'context' => 'Helper text shown when another session currently locks a PV point.'],
        'documents.pv_editor.notice.updated_by' => ['text' => 'Mis a jour par {user}.', 'context' => 'Short helper shown when a point was last updated by another user.'],
        'documents.pv_editor.notice.stage_readonly' => ['text' => 'Seules les personnes qui peuvent editer le document peuvent changer cette etape.', 'context' => 'Helper shown below the PV stage selector when it is read only.'],
        'documents.pv_editor.warning.unsaved_close' => ['text' => 'Des modifications non enregistrees n ont pas ete sauvegardees. Fermer quand meme ?', 'context' => 'Browser confirmation shown before closing the PV editor with unsaved changes.'],
        'documents.pv_editor.warning.validate_irreversible' => ['text' => 'Valider ce PV est irreversible. Il ne sera plus possible de le modifier. Continuer ?', 'context' => 'Confirmation shown before changing a PV stage to validated.'],
        'documents.pv_editor.summary.meeting_duration' => ['text' => 'Duree reunion', 'context' => 'Label for the total meeting duration in the PV editor timing summary.'],
        'documents.pv_editor.summary.remaining_time' => ['text' => 'Temps restant', 'context' => 'Label for the remaining meeting time when the meeting is in progress.'],
        'documents.pv_editor.summary.points_duration' => ['text' => 'Duree des points', 'context' => 'Label for the total planned duration of PV points.'],
        'documents.pv_editor.summary.points_remaining' => ['text' => 'Points restants', 'context' => 'Label for the remaining planned duration of unhandled PV points.'],
        'documents.pv_editor.summary.handled' => ['text' => 'Traites', 'context' => 'Legend label for handled agenda points in the PV editor chart.'],
        'documents.pv_editor.summary.remaining' => ['text' => 'Restants', 'context' => 'Legend label for remaining agenda points in the PV editor chart.'],
        'documents.pv_editor.summary.margin' => ['text' => 'Marge', 'context' => 'Legend label for unused meeting time in the PV editor chart.'],
        'documents.pv_editor.summary.overrun' => ['text' => 'Depassement', 'context' => 'Legend label for overrun beyond meeting duration in the PV editor chart.'],
        'documents.pv_editor.summary.not_started' => ['text' => '--', 'context' => 'Fallback value for remaining time when the meeting is not in progress.'],
    ];
}

function omoDocumentsPvEditorBuildUiText(callable $translate = null): array
{
    $resolve = static function (string $key, string $fallback) use ($translate): string {
        if (is_callable($translate)) {
            return (string)call_user_func($translate, $key);
        }

        return $fallback;
    };

    return [
        'save' => $resolve('documents.pv_editor.action.save', 'Enregistrer'),
        'saving' => $resolve('documents.pv_editor.action.saving', 'Enregistrement...'),
        'reordering' => $resolve('documents.pv_editor.action.reordering', 'Reorganisation...'),
        'saved' => $resolve('documents.pv_editor.state.saved', 'Enregistre'),
        'dirty' => $resolve('documents.pv_editor.state.dirty', 'Modifications non enregistrees'),
        'readonly' => $resolve('documents.pv_editor.state.readonly', 'Lecture seule'),
        'mine' => $resolve('documents.pv_editor.state.mine', 'Mon point'),
        'handledState' => $resolve('documents.pv_editor.state.handled', 'Traite'),
        'lockedState' => $resolve('documents.pv_editor.state.locked', 'Verrouille'),
        'title' => $resolve('documents.pv_editor.field.title', 'Titre'),
        'type' => $resolve('documents.pv_editor.field.type', 'Type'),
        'author' => $resolve('documents.pv_editor.field.author', 'Auteur'),
        'duration' => $resolve('documents.pv_editor.field.duration', 'Duree estimee'),
        'durationShort' => $resolve('documents.pv_editor.field.duration_short', '{minutes} min'),
        'durationEmpty' => $resolve('documents.pv_editor.field.duration_empty', '-- min'),
        'stage' => $resolve('documents.pv_editor.field.stage', 'Etape'),
        'stagePreparation' => $resolve('documents.pv_editor.field.stage.preparation', 'Preparation'),
        'stageMeeting' => $resolve('documents.pv_editor.field.stage.meeting', 'Reunion'),
        'stageReview' => $resolve('documents.pv_editor.field.stage.review', 'Relecture'),
        'stageValidated' => $resolve('documents.pv_editor.field.stage.validated', 'Valide'),
        'attendance' => $resolve('documents.pv_editor.field.attendance', 'Liste de presence'),
        'attendanceEmpty' => $resolve('documents.pv_editor.field.attendance_empty', 'Aucune personne invitee pour le moment.'),
        'attendancePresent' => $resolve('documents.pv_editor.field.attendance_present', 'Present'),
        'attendanceCount' => $resolve('documents.pv_editor.field.attendance_count', '{present}/{total} presents'),
        'pvEditor' => $resolve('documents.pv_editor.field.pv_editor', 'Editeur du PV'),
        'claimPvEditor' => $resolve('documents.pv_editor.action.claim_pv_editor', 'Devenir editeur'),
        'invite' => $resolve('documents.pv_editor.action.invite', 'Inviter'),
        'inviteTitle' => $resolve('documents.pv_editor.popup.invite_title', 'Invites'),
        'pvEditorEmpty' => $resolve('documents.pv_editor.notice.pv_editor_empty', 'Aucun editeur attribue.'),
        'pvEditorActive' => $resolve('documents.pv_editor.notice.pv_editor_active', 'Vous etes editeur du PV.'),
        'pvEditorCanEdit' => $resolve('documents.pv_editor.notice.pv_editor_can_edit', 'Vous pouvez modifier ce point car vous etes l editeur du PV.'),
        'author' => $resolve('documents.pv_editor.field.author', 'Porte par'),
        'handled' => $resolve('documents.pv_editor.field.handled', 'Traite'),
        'concernedHolon' => $resolve('documents.pv_editor.field.concerned_holon', 'Holon concerne'),
        'concernedHolonEmpty' => $resolve('documents.pv_editor.field.concerned_holon_empty', 'Sans role'),
        'addressedHolons' => $resolve('documents.pv_editor.field.addressed_holons', 'Holons adresses'),
        'tensions' => $resolve('documents.pv_editor.field.tensions', 'Tensions'),
        'content' => $resolve('documents.pv_editor.field.content', 'Contenu'),
        'information' => $resolve('documents.pv_editor.field.pointtype.information', 'Information'),
        'consultation' => $resolve('documents.pv_editor.field.pointtype.consultation', 'Consultation'),
        'decision' => $resolve('documents.pv_editor.field.pointtype.decision', 'Decision'),
        'reorder' => $resolve('documents.pv_editor.notice.reorder', 'Reordonner les points'),
        'moveUp' => $resolve('documents.pv_editor.notice.move_up', 'Monter'),
        'moveDown' => $resolve('documents.pv_editor.notice.move_down', 'Descendre'),
        'ownerOnly' => $resolve('documents.pv_editor.notice.owner_only', 'Vous pouvez modifier ce point car vous en etes l auteur.'),
        'readonlyNotice' => $resolve('documents.pv_editor.notice.readonly', 'Vous pouvez consulter ce point, mais seul son auteur peut le modifier avant la reunion.'),
        'lockedOther' => $resolve('documents.pv_editor.notice.locked_other', 'Edition en cours par {user}.'),
        'updatedBy' => $resolve('documents.pv_editor.notice.updated_by', 'Mis a jour par {user}.'),
        'stageReadonly' => $resolve('documents.pv_editor.notice.stage_readonly', 'Seules les personnes qui peuvent editer le document peuvent changer cette etape.'),
        'meetingDuration' => $resolve('documents.pv_editor.summary.meeting_duration', 'Duree reunion'),
        'remainingTime' => $resolve('documents.pv_editor.summary.remaining_time', 'Temps restant'),
        'pointsDuration' => $resolve('documents.pv_editor.summary.points_duration', 'Duree des points'),
        'pointsRemaining' => $resolve('documents.pv_editor.summary.points_remaining', 'Points restants'),
        'handledLegend' => $resolve('documents.pv_editor.summary.handled', 'Traites'),
        'remainingLegend' => $resolve('documents.pv_editor.summary.remaining', 'Restants'),
        'marginLegend' => $resolve('documents.pv_editor.summary.margin', 'Marge'),
        'overrunLegend' => $resolve('documents.pv_editor.summary.overrun', 'Depassement'),
        'notStartedValue' => $resolve('documents.pv_editor.summary.not_started', '--'),
        'defaultTitle' => $resolve('documents.pv_editor.point.default_title', 'Nouveau point'),
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
        ];
    }

    $currentHolonId = (int)($pointData['concernedHolonId'] ?? 0);
    $currentHolonLabel = trim((string)($pointData['concernedHolonLabel'] ?? ''));
    if ($currentHolonId > 0 && $currentHolonLabel !== '' && !isset($optionsById[$currentHolonId])) {
        $optionsById[$currentHolonId] = [
            'id' => $currentHolonId,
            'label' => $currentHolonLabel,
        ];
    }

    $pointData['concernedHolonOptions'] = array_values($optionsById);
    return $pointData;
}

function omoDocumentsPvEditorBuildAttendancePayloadFromDocument(\dbObject\Document $document, int $organizationId): ?array
{
    $event = method_exists($document, 'getAssociatedEvent')
        ? $document->getAssociatedEvent()
        : null;
    if (!($event instanceof \dbObject\Event)) {
        return null;
    }

    $entries = $event->getAttendanceEntries($organizationId);
    $presentCount = 0;
    foreach ($entries as $entry) {
        if (!empty($entry['isPresent'])) {
            $presentCount++;
        }
    }

    return [
        'eventId' => (int)$event->getId(),
        'presentCount' => $presentCount,
        'totalCount' => count($entries),
        'entries' => array_values(array_map(static function (array $entry): array {
            return [
                'identityKey' => (string)($entry['identityKey'] ?? ''),
                'displayLabel' => (string)($entry['displayLabel'] ?? ''),
                'secondaryLabel' => (string)($entry['secondaryLabel'] ?? ''),
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

function omoDocumentsPvEditorRenderNavItem(array $pointData, array $uiText): string
{
    $pointId = (int)($pointData['id'] ?? 0);
    $title = trim((string)($pointData['title'] ?? ''));
    if ($title === '') {
        $title = (string)($uiText['defaultTitle'] ?? 'Nouveau point');
    }

    $author = trim((string)($pointData['authorLabel'] ?? ''));
    $metaParts = [];
    $metaParts[] = $author !== '' ? $author : (string)($uiText['readonly'] ?? 'Lecture seule');
    $metaParts[] = omoDocumentsPvEditorDurationLabel(
        isset($pointData['desiredDurationMinutes']) ? (int)$pointData['desiredDurationMinutes'] : null,
        $uiText
    );
    if (!empty($pointData['lock']['isLockedByOther']) && trim((string)($pointData['lock']['userLabel'] ?? '')) !== '') {
        $metaParts[] = str_replace('{user}', trim((string)$pointData['lock']['userLabel']), (string)($uiText['lockedOther'] ?? 'Edition en cours par {user}.'));
    }

    $reorderHandle = !empty($pointData['canReorder'])
        ? '  <span class="omo-pv-editor__nav-handle generic-drag-handle generic-drag-handle--static" draggable="true" data-omo-pv-point-drag-handle="' . $pointId . '" title="' . omoDocumentsPvEditorEscape((string)($uiText['reorder'] ?? 'Reordonner les points')) . '" aria-label="' . omoDocumentsPvEditorEscape((string)($uiText['reorder'] ?? 'Reordonner les points')) . '">::</span>'
        : '  <span class="omo-pv-editor__nav-handle omo-pv-editor__nav-handle--disabled" aria-hidden="true"></span>';
    $handledDisabled = empty($pointData['canToggleHandled']) ? ' disabled' : '';

    return '<div class="omo-pv-editor__nav-row' . (!empty($pointData['isHandled']) ? ' is-handled' : '') . '" data-omo-pv-point-nav-row="' . $pointId . '">'
        . $reorderHandle
        . '  <button type="button" class="omo-pv-editor__nav-item" data-omo-pv-point-nav-target="' . $pointId . '">'
        . '      <span class="omo-pv-editor__nav-titleline">'
        . '          <span class="omo-pv-editor__nav-order">' . omoDocumentsPvEditorEscape((string)($pointData['positionLabel'] ?? '--')) . '</span>'
        . '          <strong class="omo-pv-editor__nav-title">' . omoDocumentsPvEditorEscape($title) . '</strong>'
        . '      </span>'
        . '      <span class="omo-pv-editor__nav-meta">' . omoDocumentsPvEditorEscape(implode(' | ', $metaParts)) . '</span>'
        . '  </button>'
        . '  <div class="omo-pv-editor__nav-actions">'
        . '  <label class="omo-pv-editor__nav-check" title="' . omoDocumentsPvEditorEscape((string)($uiText['handled'] ?? 'Traite')) . '">'
        . '      <input type="checkbox" data-omo-pv-point-handled="' . $pointId . '"' . (!empty($pointData['isHandled']) ? ' checked' : '') . $handledDisabled . '>'
        . '      <span class="omo-pv-editor__nav-check-label">' . omoDocumentsPvEditorEscape((string)($uiText['handled'] ?? 'Traite')) . '</span>'
        . '  </label>'
        . '  </div>'
        . '</div>';
}

function omoDocumentsPvEditorRenderPointCard(array $pointData, array $uiText): string
{
    $pointId = (int)($pointData['id'] ?? 0);
    $pointType = trim((string)($pointData['pointType'] ?? 'information'));
    $pointTypeLabel = trim((string)($pointData['pointTypeLabel'] ?? ($uiText[$pointType] ?? $pointType)));
    $pointTypeIcons = [
        'information' => '/omo/assets/images/documents/pv-point-type/information.png',
        'consultation' => '/omo/assets/images/documents/pv-point-type/consultation.png',
        'decision' => '/omo/assets/images/documents/pv-point-type/decision.png',
    ];
    $pointTypeIcon = (string)($pointTypeIcons[$pointType] ?? $pointTypeIcons['information']);
    $title = trim((string)($pointData['title'] ?? ''));
    if ($title === '') {
        $title = (string)($uiText['defaultTitle'] ?? 'Nouveau point');
    }

    $isEditable = !empty($pointData['isEditable']);
    $canEditNow = !empty($pointData['canEditNow']);
    $canAssignAuthor = !empty($pointData['canAssignAuthor']);
    $canReorder = !empty($pointData['canReorder']);
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
        $updateInfo = str_replace('{user}', trim((string)$pointData['lastModifiedByLabel']), (string)($uiText['updatedBy'] ?? 'Mis a jour par {user}.'));
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
        $html .= '      <label class="omo-pv-editor__point-duration-shell" title="' . omoDocumentsPvEditorEscape((string)$uiText['duration']) . '">';
        $html .= '          <input type="number" min="0" step="1" class="omo-pv-editor__point-duration-input" value="' . omoDocumentsPvEditorEscape($durationValue > 0 ? (string)$durationValue : '') . '" data-omo-pv-point-duration="' . $pointId . '" aria-label="' . omoDocumentsPvEditorEscape((string)$uiText['duration']) . '">';
        $html .= '          <span>min</span>';
        $html .= '      </label>';
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
        $html .= '              <option value="0">' . omoDocumentsPvEditorEscape((string)($uiText['concernedHolonEmpty'] ?? 'Sans role')) . '</option>';
        foreach ($concernedHolonOptions as $option) {
            $optionId = (int)($option['id'] ?? 0);
            $optionLabel = trim((string)($option['label'] ?? ''));
            if ($optionId <= 0 || $optionLabel === '') {
                continue;
            }
            $html .= '<option value="' . $optionId . '"' . ($optionId === $concernedHolonId ? ' selected' : '') . '>' . omoDocumentsPvEditorEscape($optionLabel) . '</option>';
        }
        $html .= '          </select>';
        $html .= '      </label>';
    } elseif (!empty($pointData['hasStructureApplication']) && trim((string)($pointData['concernedHolonLabel'] ?? '')) !== '') {
        $html .= '      <span class="omo-pv-editor__point-concerned-readonly">' . omoDocumentsPvEditorEscape(trim((string)$pointData['concernedHolonLabel'])) . '</span>';
    }
    $html .= '    </div>';
    if ($chips !== '') {
        $html .= '<div class="omo-document-pv__point-chips">' . $chips . '</div>';
    }
    $html .= '  </div>';
    $html .= '</header>';

    if ($canEditNow) {
        $html .= '<div class="omo-pv-editor__editor-block">';
        $html .= '  <div class="omo-pv-editor__field-label">' . omoDocumentsPvEditorEscape((string)$uiText['content']) . '</div>';
        $html .= '  <div class="omo-pv-editor__editor-host" data-omo-pv-point-editor-host="' . $pointId . '"></div>';
        $html .= '  <textarea hidden data-omo-pv-point-content-source="' . $pointId . '">' . omoDocumentsPvEditorEscape((string)($pointData['contentRaw'] ?? '')) . '</textarea>';
        $html .= '</div>';
        $html .= '<div class="omo-pv-editor__point-footer">';
        $footerNoteParts = [!empty($pointData['isPvEditor'])
            ? (string)$uiText['pvEditorCanEdit']
            : (string)$uiText['ownerOnly']];
        if ($updateInfo !== '') {
            $footerNoteParts[] = $updateInfo;
        }
        $html .= '  <span class="omo-pv-editor__point-note">' . omoDocumentsPvEditorEscape(implode(' | ', $footerNoteParts)) . '</span>';
        $html .= '  <div class="omo-pv-editor__point-actions">';
        $html .= '    <span class="omo-pv-editor__point-status" data-omo-pv-point-status="' . $pointId . '"></span>';
        $html .= '    <button type="button" class="generic-action-button omo-pv-editor__save-button" data-omo-pv-point-save="' . $pointId . '" disabled aria-disabled="true">' . omoDocumentsPvEditorEscape((string)$uiText['save']) . '</button>';
        $html .= '  </div>';
        $html .= '</div>';
    } else {
        $html .= '<div class="omo-pv-editor__point-footer omo-pv-editor__point-footer--readonly">';
        if (!empty($pointData['lock']['isLockedByOther'])) {
            $readonlyNote = str_replace(
                '{user}',
                trim((string)($pointData['lock']['userLabel'] ?? '')) !== '' ? trim((string)$pointData['lock']['userLabel']) : (string)($uiText['readonly'] ?? 'Lecture seule'),
                (string)($uiText['lockedOther'] ?? 'Edition en cours par {user}.')
            );
        } else {
            $readonlyNote = (string)$uiText['readonlyNotice'];
        }
        if ($updateInfo !== '') {
            $readonlyNote .= ' | ' . $updateInfo;
        }
        $html .= '  <span class="omo-pv-editor__point-note">' . omoDocumentsPvEditorEscape($readonlyNote) . '</span>';
        $html .= '</div>';
        $html .= '<div class="omo-document-pv__point-content prose">' . (string)($pointData['contentHtml'] ?? '') . '</div>';
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
        'title' => (string)($pointData['title'] ?? ''),
        'authorValue' => (string)($pointData['authorValue'] ?? ''),
        'position' => (int)($pointData['position'] ?? 0),
        'concernedHolonId' => (int)($pointData['concernedHolonId'] ?? 0),
        'desiredDurationMinutes' => isset($pointData['desiredDurationMinutes']) ? (int)$pointData['desiredDurationMinutes'] : 0,
        'isHandled' => !empty($pointData['isHandled']),
        'syncVersion' => hash('sha256', (string)($pointData['syncVersion'] ?? '') . '|' . implode('|', $authorOptionValues)),
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
