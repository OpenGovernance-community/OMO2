<?php

function commonMeetingRecurrenceSourceLang(): array
{
    $strings = [
        'enabled' => 'Réunion récurrente', 'frequency' => 'Récurrence', 'weekly' => 'Chaque semaine',
        'days' => 'Tous les X jours', 'monthly_day' => 'Chaque mois, le…', 'monthly_weekday' => 'Un jour de semaine dans le mois',
        'on_close' => 'Demander la prochaine date à la clôture', 'interval' => 'Intervalle en jours', 'month_day' => 'Jour du mois',
        'approximate_interval' => 'Délai indicatif (jours)',
        'approximate_interval_help' => 'Propose une date après ce nombre de jours : 7 pour une semaine, 14 pour deux semaines, 30 pour environ un mois. La date reste ajustable à la clôture.',
        'on_close_summary' => 'Demander la prochaine date à la clôture (environ {days} j)',
        'weekday' => 'Jour de la semaine', 'ordinal' => 'Rang dans le mois', 'first' => 'Premier', 'second' => 'Deuxième',
        'third' => 'Troisième', 'fourth' => 'Quatrième', 'last' => 'Dernier', 'monday' => 'Lundi', 'tuesday' => 'Mardi',
        'wednesday' => 'Mercredi', 'thursday' => 'Jeudi', 'friday' => 'Vendredi', 'saturday' => 'Samedi', 'sunday' => 'Dimanche',
        'weekend' => 'Si la date tombe le week-end', 'none' => 'Conserver la date', 'previous' => 'Vendredi précédent', 'next' => 'Lundi suivant',
        'horizon' => 'Créer les réunions à l’avance (mois)',
        'horizon_help' => 'Si la série commence dans le futur, cet horizon est calculé à partir de sa date de début. Il avance ensuite chaque jour.',
        'help' => 'Le cadre, les invitations aux espaces et les documents proviennent de cette réunion. Si le jour choisi n’existe pas dans un mois, la réunion a lieu le dernier jour de ce mois.',
        'document_mode' => 'Documents des prochaines occurrences',
        'document_copy' => 'Nouveaux documents à chaque occurrence',
        'document_reuse' => 'Mêmes documents (hors PV)',
        'document_help' => 'Ce choix concerne les prochaines créations. Les documents des réunions déjà planifiées sont conservés. Un document réutilisé garde son nom et reste disponible si une réunion est supprimée.',
        'shared_pv' => 'Un PV doit être propre à chaque réunion. Choisissez de nouveaux documents à chaque occurrence pour utiliser un PV.',
        'requires_pv' => 'La planification de la prochaine date à la clôture nécessite un document de type PV. Ajoutez un PV ou choisissez une autre récurrence.',
        'on_close_help' => 'Un PV est obligatoire. La prochaine date sera demandée au passage en relecture ou à la validation, si la prochaine réunion n’a pas encore été planifiée.',
        'apply_help' => 'Le bouton « Enregistrer cette réunion » ne modifie que cette réunion. Sa flèche permet d’appliquer aussi les changements aux suivantes.',
        'save_single' => 'Enregistrer cette réunion', 'save_options' => 'Options d’enregistrement',
        'save_following' => 'Enregistrer cette réunion et les suivantes', 'save_stop' => 'Enregistrer et arrêter la récurrence',
        'save_single_help' => 'Modifie uniquement cette réunion. La règle et les autres dates de la série sont conservées.',
        'save_following_help' => 'Applique le contenu et les horaires aux réunions suivantes, en conservant leurs dates et la cadence. Les réunions précédentes, les occurrences personnalisées et le contenu des documents sont conservés.',
        'save_stop_help' => 'Arrête la création de nouvelles réunions. Les rendez-vous et leurs documents déjà créés sont conservés.',
        'delete_single' => 'Supprimer cette réunion', 'delete_following' => 'Supprimer cette réunion et les suivantes',
        'delete_options' => 'Options de suppression',
        'delete_last' => 'Supprimer et mettre fin à la récurrence',
        'edit' => 'Modifier la récurrence', 'edit_cancel' => 'Annuler le changement de récurrence',
        'save_rule' => 'Enregistrer cette réunion et appliquer la récurrence', 'strategy' => 'Effet du changement',
        'save_rule_help' => 'Applique la stratégie choisie dans le formulaire de récurrence.',
        'save_without_rule' => 'Les modifications de récurrence ne seront pas appliquées. Enregistrer uniquement cette réunion ?',
        'replan' => 'Replanifier les suivantes', 'after_last' => 'Appliquer après la dernière date planifiée',
        'stop_keep' => 'Arrêter en conservant les dates planifiées', 'stop_delete' => 'Arrêter et supprimer les suivantes',
        'replan_help' => 'Recalcule les dates suivantes selon la nouvelle règle. Les réunions personnalisées et le contenu des documents sont conservés.',
        'after_last_help' => 'Conserve toutes les dates planifiées. La nouvelle règle s’applique aux prochaines créations, après la dernière date planifiée.',
        'stop_keep_help' => 'Ne crée plus de réunion. Toutes les dates planifiées et leurs documents sont conservés.',
        'stop_delete_help' => 'Arrête la création de nouvelles réunions et supprime les suivantes, y compris les occurrences personnalisées. Cette réunion et les réunions passées sont conservées.',
        'days_help' => 'Les reports ne décalent pas le rythme de base. Si plusieurs dates tombent le même jour après report, une seule réunion est créée.',
        'delete_single_help' => 'Supprimer uniquement cette réunion ? La série continue et cette date ne sera pas recréée.',
        'scope_delete_help' => 'Supprimer cette réunion et les suivantes, y compris les occurrences personnalisées, et arrêter la récurrence ? Les réunions précédentes et passées sont conservées.',
        'scope_documents' => 'Supprimer aussi les documents des réunions supprimées ? Les documents réutilisés sont conservés.',
        'next_title' => 'Prochaine réunion', 'next_date' => 'Date et heure de la prochaine réunion',
        'availability_loading' => 'Calcul des disponibilités…', 'availability_error' => 'Impossible de charger les disponibilités. Vous pouvez réessayer ou saisir la date.',
        'availability_hint' => 'Choisissez un créneau commun dans la grille. La durée de la réunion est conservée.',
        'plan' => 'Planifier la prochaine réunion', 'close_plan' => 'Clôturer et planifier', 'close_skip' => 'Clôturer sans planifier',
        'cancel' => 'Annuler', 'saved' => 'Prochaine réunion créée.', 'error' => 'Impossible de créer la prochaine réunion.',
        'invalid' => 'Choisissez une date future valide.', 'past' => 'Les réunions récurrentes passées ne peuvent plus être modifiées.',
        'csrf' => 'Votre session a expiré. Rechargez le formulaire.',
    ];
    $result = [
        'calendar.recurrence.summary.weekly' => ['text' => 'Chaque {day}', 'context' => 'Weekly meeting recurrence summary.'],
        'calendar.recurrence.summary.daily' => ['text' => 'Chaque jour', 'context' => 'Daily meeting recurrence summary.'],
        'calendar.recurrence.summary.days' => ['text' => 'Tous les {days} jours', 'context' => 'Meeting recurrence interval in days.'],
        'calendar.recurrence.summary.monthly_day' => ['text' => 'Chaque mois, le {day}', 'context' => 'Meeting recurrence on a day of the month.'],
        'calendar.recurrence.summary.monthly_weekday' => ['text' => 'Chaque mois, le {ordinal} {day}', 'context' => 'Meeting recurrence on a ranked weekday.'],
        'calendar.recurrence.summary.previous' => ['text' => 'report au vendredi précédent si la date tombe le week-end', 'context' => 'Meeting weekend adjustment to the previous Friday.'],
        'calendar.recurrence.summary.next' => ['text' => 'report au lundi suivant si la date tombe le week-end', 'context' => 'Meeting weekend adjustment to the next Monday.'],
        'calendar.recurrence.summary.weekend' => ['text' => '{rule} — {adjustment}', 'context' => 'Complete recurrence summary combining its scheduling rule and weekend adjustment.'],
        'calendar.recurrence.summary.exception' => ['text' => 'Occurrence personnalisée de la série : {rule}', 'context' => 'A meeting still linked to its series, with individual changes protected from collective edits.'],
        'calendar.recurrence.summary.stopped' => ['text' => '{rule} — création de nouvelles réunions arrêtée', 'context' => 'The recurrence no longer creates meetings.'],
    ];
    foreach ($strings as $key => $text) {
        $result['calendar.recurrence.' . $key] = ['text' => $text, 'context' => 'Shared recurring meeting settings and next meeting scheduling.'];
    }
    return $result;
}

function commonMeetingRecurrenceUi(callable $translate): array
{
    $result = [];
    foreach (['next_title', 'next_date', 'plan', 'close_plan', 'close_skip', 'cancel', 'saved', 'error',
        'availability_loading', 'availability_error', 'availability_hint',
        'scope_delete_help', 'scope_documents', 'delete_single', 'delete_following', 'delete_last', 'delete_options', 'delete_single_help'] as $key) {
        $result[$key] = $translate('calendar.recurrence.' . $key);
    }
    return $result;
}

function commonMeetingNextDateConfig(\dbObject\Event $event): array
{
    $series = $event->getRecurrence();
    if (!$series) { return []; }
    $blueprint = $series->getParameter('blueprint');
    $zone = new \DateTimeZone($series->get('timezone'));
    $now = new \DateTimeImmutable('now', $zone);
    $start = new \DateTimeImmutable($event->get('start_at')->format('Y-m-d H:i:s'), $zone);
    $minimum = max($now, $start)->modify('+1 minute');
    $intervalDays = max(1, min(366, (int)($series->get('interval_days') ?: 7)));
    $suggested = max($now->modify('+1 day'), $start->modify('+' . $intervalDays . ' days'));
    return ['url' => '/omo/api/calendar/recurrence_availability.php?oid=' . (int)$event->get('IDorganization') . '&id=' . (int)$event->getId(),
        'duration' => max(0, (int)($blueprint['duration_seconds'] ?? 3600)),
        'minimum' => $minimum->format('Y-m-d\TH:i'),
        'suggested' => $suggested->format('Y-m-d') . 'T' . substr((string)$blueprint['time'], 0, 5)];
}

function commonMeetingAvailabilityAssets(): void
{
    ?>
    <link rel="stylesheet" href="<?= commonAssetUrl('/common/calendar/availability-grid.css') ?>">
    <script src="<?= commonAssetUrl('/common/calendar/availability-model.js') ?>"></script>
    <script src="<?= commonAssetUrl('/common/calendar/availability-view.js') ?>"></script>
    <script src="<?= commonAssetUrl('/common/calendar/availability.js') ?>"></script>
    <?php
}

function commonRenderMeetingRecurrenceFields(array $settings, bool $enabled, bool $existing, callable $translate, string $summary = ''): void
{
    $t = static fn(string $key): string => htmlspecialchars($translate('calendar.recurrence.' . $key), ENT_QUOTES, 'UTF-8');
    $frequency = $settings['frequency'] ?? 'weekly';
    $intervalId = uniqid('meeting-recurrence-interval-');
    ?>
    <section class="generic-section generic-form-stack generic-form-stack--compact" data-meeting-recurrence>
        <?php if ($existing): ?>
        <div data-meeting-recurrence-readonly>
            <p class="generic-description"><?= htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') ?></p>
            <p class="generic-description"><?= $t(($settings['document_mode'] ?? 'copy') === 'reuse' ? 'document_reuse' : 'document_copy') ?></p>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-meeting-recurrence-edit><?= $t('edit') ?></button>
        </div>
        <input type="hidden" name="recurrence_edit" value="0" data-meeting-recurrence-editing>
        <input type="hidden" name="recurrence_delete_documents" value="0" data-meeting-recurrence-delete-documents>
        <div class="generic-form-stack generic-form-stack--compact" data-meeting-recurrence-editor hidden>
            <label class="generic-form-field"><span class="generic-form-label"><?= $t('strategy') ?></span>
                <select class="generic-form-control" name="recurrence_strategy" data-meeting-recurrence-strategy>
                    <?php foreach (['replan', 'after_last', 'stop_keep', 'stop_delete'] as $strategy): ?>
                    <option value="<?= $strategy ?>"><?= $t($strategy) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php foreach (['replan', 'after_last', 'stop_keep', 'stop_delete'] as $strategy): ?>
            <p class="generic-description" data-meeting-strategy-help="<?= $strategy ?>"<?= $strategy === 'replan' ? '' : ' hidden' ?>><?= $t($strategy . '_help') ?></p>
            <?php endforeach; ?>
            <input type="hidden" name="recurrence_enabled" value="1" data-meeting-recurrence-enabled>
        <?php else: ?>
        <label class="generic-checkbox"><input type="checkbox" name="recurrence_enabled" value="1" data-meeting-recurrence-enabled<?= $enabled ? ' checked' : '' ?>> <?= $t('enabled') ?></label>
        <?php endif; ?>
        <div class="generic-form-stack generic-form-stack--compact" data-meeting-recurrence-fields<?= $enabled ? '' : ' hidden' ?>>
            <div class="generic-form-grid">
                <label class="generic-form-field"><span class="generic-form-label"><?= $t('frequency') ?></span>
                    <select class="generic-form-control" name="recurrence[frequency]" data-meeting-recurrence-frequency>
                        <?php foreach (['weekly', 'days', 'monthly_day', 'monthly_weekday', 'on_close'] as $value): ?>
                        <option value="<?= $value ?>"<?= $value === $frequency ? ' selected' : '' ?>><?= $t($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="generic-form-field" data-meeting-recurrence-for="days on_close">
                    <div class="generic-heading-with-help">
                        <label class="generic-form-label" for="<?= $intervalId ?>" data-meeting-interval-label data-days="<?= $t('interval') ?>" data-on-close="<?= $t('approximate_interval') ?>"><?= $t($frequency === 'on_close' ? 'approximate_interval' : 'interval') ?></label>
                        <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover data-meeting-recurrence-for="on_close">
                            <summary aria-label="<?= $t('approximate_interval_help') ?>">?</summary>
                            <div class="generic-context-help__content"><?= $t('approximate_interval_help') ?></div>
                        </details>
                    </div>
                    <input id="<?= $intervalId ?>" type="number" class="generic-form-control" min="1" max="366" name="recurrence[interval_days]" value="<?= (int)($settings['interval_days'] ?? 7) ?>">
                </div>
                <label class="generic-form-field" data-meeting-recurrence-for="monthly_day"><span class="generic-form-label"><?= $t('month_day') ?></span>
                    <input type="number" class="generic-form-control" min="1" max="31" name="recurrence[month_day]" value="<?= (int)($settings['month_day'] ?? 1) ?>">
                </label>
                <label class="generic-form-field" data-meeting-recurrence-for="weekly monthly_weekday"><span class="generic-form-label"><?= $t('weekday') ?></span>
                    <select class="generic-form-control" name="recurrence[weekday]">
                        <?php foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $index => $day): ?>
                        <option value="<?= $index + 1 ?>"<?= $index + 1 === (int)($settings['weekday'] ?? 1) ? ' selected' : '' ?>><?= $t($day) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="generic-form-field" data-meeting-recurrence-for="monthly_weekday"><span class="generic-form-label"><?= $t('ordinal') ?></span>
                    <select class="generic-form-control" name="recurrence[ordinal]">
                        <?php foreach (['first', 'second', 'third', 'fourth', 'last'] as $index => $ordinal): ?>
                        <option value="<?= $index + 1 ?>"<?= $index + 1 === (int)($settings['ordinal'] ?? 1) ? ' selected' : '' ?>><?= $t($ordinal) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="generic-form-field" data-meeting-recurrence-for="monthly_day days"><span class="generic-form-label"><?= $t('weekend') ?></span>
                    <select class="generic-form-control" name="recurrence[weekend_shift]">
                        <?php foreach (['none', 'previous', 'next'] as $shift): ?>
                        <option value="<?= $shift ?>"<?= $shift === ($settings['weekend_shift'] ?? 'none') ? ' selected' : '' ?>><?= $t($shift) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="generic-form-field" data-meeting-recurrence-for="weekly days monthly_day monthly_weekday"><span class="generic-form-label"><?= $t('horizon') ?></span>
                    <select class="generic-form-control" name="recurrence[horizon_months]">
                        <?php foreach (range(1, 12) as $months): ?>
                        <option value="<?= $months ?>"<?= $months === (int)($settings['horizon_months'] ?? 3) ? ' selected' : '' ?>><?= $months ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="generic-description"><?= $t('horizon_help') ?></span>
                </label>
            </div>
            <label class="generic-form-field"><span class="generic-form-label"><?= $t('document_mode') ?></span>
                <select class="generic-form-control" name="recurrence[document_mode]">
                    <option value="copy"<?= ($settings['document_mode'] ?? 'copy') === 'copy' ? ' selected' : '' ?>><?= $t('document_copy') ?></option>
                    <option value="reuse"<?= ($settings['document_mode'] ?? 'copy') === 'reuse' ? ' selected' : '' ?>><?= $t('document_reuse') ?></option>
                </select>
            </label>
            <p class="generic-description"><?= $t('document_help') ?></p>
            <p class="generic-description"><?= $t('help') ?></p>
            <p class="generic-description" data-meeting-recurrence-for="days"><?= $t('days_help') ?></p>
            <p class="generic-description" data-meeting-recurrence-for="on_close"><?= $t('on_close_help') ?></p>
        </div>
        <?php if ($existing): ?>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-meeting-recurrence-cancel><?= $t('edit_cancel') ?></button>
        </div>
        <span hidden data-meeting-stop-confirm><?= $t('stop_delete_help') ?></span>
        <span hidden data-meeting-documents-confirm><?= $t('scope_documents') ?></span>
        <span hidden data-meeting-ignore-rule-confirm><?= $t('save_without_rule') ?></span>
        <input type="hidden" name="recurrence_apply_following" value="0" data-meeting-edit-scope>
        <p class="generic-description"><?= $t('apply_help') ?></p>
        <?php endif; ?>
    </section>
    <?php
}

function commonRenderMeetingSaveActions(string $formId, bool $enabled, callable $translate): void
{
    $t = static fn(string $key): string => htmlspecialchars($translate('calendar.recurrence.' . $key), ENT_QUOTES, 'UTF-8');
    $form = htmlspecialchars($formId, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="generic-menu generic-menu--split" data-meeting-save-menu data-meeting-action-menu data-omo-calendar-drawer-action data-omo-subdrawer-action>
        <button type="submit" form="<?= $form ?>" class="generic-action-button generic-action-button--main"
            data-omo-calendar-create-submit data-meeting-save-scope="single"><?= $t('save_single') ?></button>
        <button type="button" class="generic-menu-toggle" data-meeting-save-toggle data-meeting-action-toggle aria-haspopup="menu" aria-expanded="false"
            aria-label="<?= $t('save_options') ?>" title="<?= $t('save_options') ?>">&#9662;</button>
        <div class="generic-menu-panel generic-menu-panel--descriptive" role="menu" hidden>
            <button type="submit" form="<?= $form ?>" class="generic-menu-item generic-menu-item--descriptive" role="menuitem" data-meeting-save-scope="following">
                <strong data-meeting-save-label data-rule="<?= $t('save_rule') ?>" data-enabled="<?= $t('save_following') ?>" data-disabled="<?= $t('save_stop') ?>"><?= $t('save_following') ?></strong>
                <span class="generic-menu-item__description" data-meeting-save-label data-rule="<?= $t('save_rule_help') ?>" data-enabled="<?= $t('save_following_help') ?>" data-disabled="<?= $t('save_stop_help') ?>"><?= $t('save_following_help') ?></span>
            </button>
        </div>
    </div>
    <?php
}

function commonMeetingRecurrenceSummary(\dbObject\Event $event, callable $translate): string
{
    $series = $event->getRecurrence();
    $recurrenceSummary = '';
    if ($series) {
        $weekdays = [1 => 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $ordinals = [1 => 'first', 'second', 'third', 'fourth', 'last'];
        $weekday = mb_strtolower($translate('calendar.recurrence.' . ($weekdays[(int)$series->get('weekday')] ?? 'monday')));
        $ordinal = mb_strtolower($translate('calendar.recurrence.' . ($ordinals[(int)$series->get('ordinal')] ?? 'first')));
        $recurrenceSummary = match ($series->get('frequency')) {
            'weekly' => $translate('calendar.recurrence.summary.weekly', ['day' => $weekday]),
            'days' => (int)$series->get('interval_days') === 1 ? $translate('calendar.recurrence.summary.daily')
                : $translate('calendar.recurrence.summary.days', ['days' => (int)$series->get('interval_days')]),
            'monthly_day' => $translate('calendar.recurrence.summary.monthly_day', ['day' => (int)$series->get('month_day')]),
            'monthly_weekday' => $translate('calendar.recurrence.summary.monthly_weekday', ['ordinal' => $ordinal, 'day' => $weekday]),
            'on_close' => $translate('calendar.recurrence.on_close_summary', ['days' => (int)($series->get('interval_days') ?: 7)]),
            default => $translate('calendar.recurrence.enabled'),
        };
        if (in_array($series->get('frequency'), ['monthly_day', 'days'], true) && in_array($series->get('weekend_shift'), ['previous', 'next'], true)) {
            $recurrenceSummary = $translate('calendar.recurrence.summary.weekend', ['rule' => $recurrenceSummary,
                'adjustment' => $translate('calendar.recurrence.summary.' . $series->get('weekend_shift'))]);
        }
        if ($event->get('recurrence_exception')) {
            $recurrenceSummary = $translate('calendar.recurrence.summary.exception', ['rule' => $recurrenceSummary]);
        }
        if (!$series->get('active')) {
            $recurrenceSummary = $translate('calendar.recurrence.summary.stopped', ['rule' => $recurrenceSummary]);
        }
    }
    return $recurrenceSummary;
}
