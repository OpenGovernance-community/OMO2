<?php

use dbObject\Holon;
use dbObject\Organization;
use dbObject\StatIndicator;
use dbObject\StatIndicatorGroup;
use dbObject\StatIndicatorGroupItem;
use dbObject\StatIndicatorImport;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

if (!function_exists('omoStatsSourceLang')) {
    function omoStatsSourceLang()
    {
        return [
            'stats.title' => ['text' => 'Indicateurs', 'context' => 'Main title of the contextual steering indicators application.'],
            'stats.scope.contextual' => ['text' => 'Contextuel', 'context' => 'Scope label for indicators defined in the current holon.'],
            'stats.scope.children' => ['text' => 'Enfants directs', 'context' => 'Scope label for indicators defined in the current holon and its direct children.'],
            'stats.scope.descendants' => ['text' => 'Descendants', 'context' => 'Scope label for indicators in the current holon and descendants.'],
            'stats.view.cards' => ['text' => 'Cartes', 'context' => 'Button switching the indicator list to cards.'],
            'stats.view.compact' => ['text' => 'Compact', 'context' => 'Button switching the indicator list to compact rows.'],
            'stats.controls.sort.aria' => ['text' => 'Classement des indicateurs', 'context' => 'Accessible label for the indicator sorting selector.'],
            'stats.controls.sort.alpha' => ['text' => 'Alphabétique', 'context' => 'Alphabetical indicator sorting option.'],
            'stats.controls.sort.temporal' => ['text' => 'Temporalité', 'context' => 'Measurement frequency indicator sorting option.'],
            'stats.group.combined' => ['text' => 'Cumuls', 'context' => 'Section label for composite indicator groups in the temporal sorting mode.'],
            'stats.action.new' => ['text' => 'Nouvel indicateur', 'context' => 'Primary button opening indicator creation.'],
            'stats.action.more' => ['text' => "Plus d'actions", 'context' => 'Menu button opening additional indicator actions.'],
            'stats.action.import' => ['text' => 'Importer un indicateur', 'context' => 'Menu action creating a contextual indicator import.'],
            'stats.action.group' => ['text' => 'Grouper des indicateurs', 'context' => 'Menu action creating a multi-indicator chart group.'],
            'stats.action.add' => ['text' => 'Ajouter', 'context' => 'Generic add action.'],
            'stats.action.update' => ['text' => 'Enregistrer', 'context' => 'Button saving an edited contextual import or indicator group.'],
            'stats.action.create_group' => ['text' => 'Créer le groupe', 'context' => 'Button creating an indicator group.'],
            'stats.action.edit' => ['text' => 'Modifier', 'context' => 'Button opening indicator edition.'],
            'stats.action.detail' => ['text' => 'Détail', 'context' => 'Button opening an indicator detail view.'],
            'stats.action.close' => ['text' => 'Fermer', 'context' => 'Button closing the nested indicator drawer.'],
            'stats.action.cancel' => ['text' => 'Annuler', 'context' => 'Button cancelling indicator edition.'],
            'stats.action.save' => ['text' => 'Enregistrer', 'context' => 'Button saving indicator edition.'],
            'stats.action.delete' => ['text' => 'Supprimer', 'context' => 'Button deleting one dated indicator value.'],
            'stats.action.delete_indicator' => ['text' => "Supprimer l'indicateur", 'context' => 'Menu action archiving an indicator from the current catalogue.'],
            'stats.action.edit_import' => ['text' => 'Changer la source', 'context' => 'Menu action changing the source of a contextual indicator import.'],
            'stats.action.delete_import' => ['text' => 'Retirer de ce contexte', 'context' => 'Menu action removing an indicator import from the current context.'],
            'stats.action.edit_group' => ['text' => 'Modifier le groupe', 'context' => 'Menu action editing a contextual indicator group.'],
            'stats.action.delete_group' => ['text' => 'Retirer le groupe', 'context' => 'Menu action removing a contextual indicator group.'],
            'stats.detail.confirm_delete_indicator' => ['text' => 'Supprimer cet indicateur de la liste ? Ses valeurs seront conservées.', 'context' => 'Confirmation before hiding an indicator.'],
            'stats.detail.confirm_delete_import' => ['text' => 'Retirer cet indicateur du contexte ?', 'context' => 'Confirmation before removing a contextual import.'],
            'stats.detail.confirm_delete_group' => ['text' => 'Retirer ce groupe du contexte ?', 'context' => 'Confirmation before removing a contextual indicator group.'],
            'stats.empty.contextual' => ['text' => "Aucun indicateur n'est encore défini dans ce contexte.", 'context' => 'Empty state for the contextual scope.'],
            'stats.empty.children' => ['text' => "Aucun indicateur n'est encore défini dans ce contexte ou ses enfants directs.", 'context' => 'Empty state for the direct child scope.'],
            'stats.empty.descendants' => ['text' => "Aucun indicateur n'est encore défini dans ce contexte ou ses descendants.", 'context' => 'Empty state for the descendants scope.'],
            'stats.card.latest' => ['text' => 'Dernière valeur', 'context' => 'Label introducing the latest indicator value on a card.'],
            'stats.card.no_value' => ['text' => 'Aucune valeur', 'context' => 'Card fallback when an indicator has no dated values.'],
            'stats.card.value_count' => ['one' => '{count} valeur', 'other' => '{count} valeurs', 'context' => 'Count of dated values attached to an indicator.'],
            'stats.card.context' => ['text' => 'Contexte', 'context' => 'Label for the holon owning an indicator.'],
            'stats.card.imported' => ['text' => 'Importé', 'context' => 'Label on an indicator imported into the current context.'],
            'stats.card.group' => ['text' => 'Groupe', 'context' => 'Label on a composite indicator group card.'],
            'stats.card.member_count' => ['one' => '{count} indicateur', 'other' => '{count} indicateurs', 'context' => 'Number of indicators in a group.'],
            'stats.card.overdue' => ['text' => 'Valeur dépassée', 'context' => 'Label shown when an indicator has passed its expected measurement deadline.'],
            'stats.card.to_complete' => ['text' => 'À compléter', 'context' => 'Label shown when an indicator is due but still within its grace period.'],
            'stats.card.overdue_days' => ['one' => 'En retard de {count} jour', 'other' => 'En retard de {count} jours', 'context' => 'Delay shown below the latest value label on an overdue indicator card.'],
            'stats.card.open' => ['text' => "Ouvrir l'indicateur {name}", 'context' => 'Accessible label on an interactive indicator card or row.'],
            'stats.column.indicator' => ['text' => 'Indicateur', 'context' => 'Compact list column for the indicator identity.'],
            'stats.column.context' => ['text' => 'Contexte', 'context' => 'Compact list column for the owning context.'],
            'stats.column.latest' => ['text' => 'Dernière valeur', 'context' => 'Compact list column for the latest value.'],
            'stats.column.history' => ['text' => 'Historique', 'context' => 'Compact list column for the mini chart.'],
            'stats.drawer.title' => ['text' => 'Indicateur', 'context' => 'Nested drawer title.'],
            'stats.drawer.description' => ['text' => 'Graphique, valeurs et saisie manuelle.', 'context' => 'Nested drawer description.'],
            'stats.loading' => ['text' => "Chargement de l'indicateur…", 'context' => 'Loading message in the nested drawer.'],
            'stats.error.load' => ['text' => 'Impossible de charger cet indicateur.', 'context' => 'Generic nested drawer loading error.'],
            'stats.error.organization' => ['text' => 'Organisation invalide ou inaccessible.', 'context' => 'Error shown when the organization context is invalid.'],
            'stats.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Error shown when the holon context is invalid.'],
            'stats.error.not_found' => ['text' => 'Indicateur introuvable.', 'context' => 'Error shown when an indicator is unavailable.'],
            'stats.error.forbidden' => ['text' => 'Vous ne pouvez pas modifier cet indicateur.', 'context' => 'Error shown when indicator edition is forbidden.'],
            'stats.error.method' => ['text' => 'Cette action doit être envoyée en POST.', 'context' => 'Error returned for a mutation using the wrong HTTP method.'],
            'stats.error.action' => ['text' => 'Action inconnue.', 'context' => 'Error returned for an unsupported stats action.'],
            'stats.error.name' => ['text' => "Le nom de l'indicateur est obligatoire.", 'context' => 'Validation error for a missing indicator name.'],
            'stats.error.url' => ['text' => "L'URL doit commencer par http:// ou https://.", 'context' => 'Validation error for an unsafe source URL.'],
            'stats.error.value' => ['text' => 'La valeur saisie est invalide.', 'context' => 'Validation error for a non numeric indicator value.'],
            'stats.error.date' => ['text' => 'La date saisie est invalide.', 'context' => 'Validation error for an invalid measurement date.'],
            'stats.error.reference_points' => ['text' => 'La courbe de référence doit contenir des points uniques entre 0 et 100 %.', 'context' => 'Validation error for malformed reference positions.'],
            'stats.error.reference_endpoints' => ['text' => 'Les points à 0 % et 100 % sont obligatoires et doivent avoir une date.', 'context' => 'Validation error for missing dated reference endpoints.'],
            'stats.error.reference_dates' => ['text' => 'La date de fin de la référence doit être postérieure à sa date de début.', 'context' => 'Validation error for inverted endpoint dates.'],
            'stats.error.ceiling' => ['text' => "Tous les points d'un plafond doivent utiliser la même valeur.", 'context' => 'Validation error when a ceiling is not horizontal.'],
            'stats.error.ceiling_value' => ['text' => 'La valeur du plafond est obligatoire.', 'context' => 'Validation error for a missing or invalid ceiling value.'],
            'stats.error.chart_min_value' => ['text' => 'La valeur basse du graphique est invalide.', 'context' => 'Validation error for a malformed chart lower value.'],
            'stats.error.save' => ['text' => "Impossible d'enregistrer l'indicateur.", 'context' => 'Generic indicator persistence error.'],
            'stats.error.value_save' => ['text' => "Impossible d'enregistrer cette valeur.", 'context' => 'Generic indicator value persistence error.'],
            'stats.error.selection' => ['text' => 'Sélectionnez au moins un indicateur visible.', 'context' => 'Validation error for an empty or invalid indicator selection.'],
            'stats.error.group_name' => ['text' => 'Le nom du groupe est obligatoire.', 'context' => 'Validation error for a missing indicator group name.'],
            'stats.error.schedule' => ['text' => 'Le rythme de mesure est invalide.', 'context' => 'Validation error for an invalid expected measurement frequency or moment.'],
            'stats.detail.tab.chart' => ['text' => 'Graphique', 'context' => 'Tab showing the large chart.'],
            'stats.detail.tab.values' => ['text' => 'Valeurs', 'context' => 'Tab showing dated values.'],
            'stats.detail.source' => ['text' => 'Consulter la source', 'context' => 'External link to the indicator source.'],
            'stats.detail.reference' => ['text' => 'Référence', 'context' => 'Label for the reference type.'],
            'stats.detail.reference_none' => ['text' => 'Sans courbe de référence', 'context' => 'Indicator reference type label for no reference.'],
            'stats.detail.reference_ceiling' => ['text' => 'Plafond horizontal', 'context' => 'Indicator reference type label for ceiling.'],
            'stats.detail.reference_objective' => ['text' => 'Objectif ou trajectoire', 'context' => 'Indicator reference type label for objective.'],
            'stats.detail.latest' => ['text' => 'Valeur actuelle', 'context' => 'Label for the latest value in indicator detail.'],
            'stats.detail.frequency' => ['text' => 'Fréquence attendue', 'context' => 'Label for the expected measurement frequency in indicator detail.'],
            'stats.detail.schedule' => ['text' => 'Moment attendu', 'context' => 'Label for the optional expected measurement moment in indicator detail.'],
            'stats.detail.chart_min_value' => ['text' => 'Valeur basse', 'context' => 'Label for the optional lower chart value in indicator detail.'],
            'stats.detail.no_values' => ['text' => "Aucune valeur n'a encore été enregistrée.", 'context' => 'Empty state in the indicator value list.'],
            'stats.detail.value_date' => ['text' => 'Date', 'context' => 'Heading for the dated value date.'],
            'stats.detail.value' => ['text' => 'Valeur', 'context' => 'Heading for the dated value number.'],
            'stats.detail.add_title' => ['text' => 'Ajouter la valeur du moment', 'context' => 'Heading above the quick value form.'],
            'stats.detail.add_help' => ['text' => "La date et l'heure actuelles sont proposées automatiquement.", 'context' => 'Help below the quick value form heading.'],
            'stats.detail.add' => ['text' => 'Ajouter la valeur', 'context' => 'Submit button for a new dated value.'],
            'stats.detail.range.label' => ['text' => 'Période affichée', 'context' => 'Label above the interactive chart time range selector.'],
            'stats.detail.range.start' => ['text' => 'Début de la période affichée', 'context' => 'Accessible label for the start handle of the chart time range selector.'],
            'stats.detail.range.end' => ['text' => 'Fin de la période affichée', 'context' => 'Accessible label for the end handle of the chart time range selector.'],
            'stats.detail.confirm_delete' => ['text' => 'Supprimer définitivement cette valeur ?', 'context' => 'Confirmation before deleting one value.'],
            'stats.form.create_title' => ['text' => 'Nouvel indicateur', 'context' => 'Heading of the create indicator form.'],
            'stats.form.edit_title' => ['text' => "Modifier l'indicateur", 'context' => 'Heading of the edit indicator form.'],
            'stats.form.intro' => ['text' => 'Définissez la série et, si nécessaire, sa courbe de référence.', 'context' => 'Introductory copy in the indicator form.'],
            'stats.form.schedule_title' => ['text' => 'Rythme de mesure', 'context' => 'Heading of the expected measurement schedule editor.'],
            'stats.form.schedule_help' => ['text' => "Définissez le rythme attendu. Le moment est facultatif : sans lui, le système pourra s'appuyer sur l'intervalle observé entre les mesures.", 'context' => 'Help text for optional measurement timing.'],
            'stats.form.frequency' => ['text' => 'Fréquence', 'context' => 'Label for expected measurement frequency select.'],
            'stats.form.schedule' => ['text' => 'Quand', 'context' => 'Label for expected measurement moment select.'],
            'stats.frequency.none' => ['text' => 'Aucune fréquence définie', 'context' => 'Empty option for an indicator without expected measurement frequency.'],
            'stats.frequency.daily' => ['text' => 'Chaque jour', 'context' => 'Expected measurement frequency option.'],
            'stats.frequency.weekly' => ['text' => 'Chaque semaine', 'context' => 'Expected measurement frequency option.'],
            'stats.frequency.monthly' => ['text' => 'Chaque mois', 'context' => 'Expected measurement frequency option.'],
            'stats.frequency.quarterly' => ['text' => 'Chaque trimestre', 'context' => 'Expected measurement frequency option.'],
            'stats.frequency.semiannual' => ['text' => 'Chaque semestre', 'context' => 'Expected measurement frequency option.'],
            'stats.frequency.yearly' => ['text' => 'Chaque année', 'context' => 'Expected measurement frequency option.'],
            'stats.schedule.none' => ['text' => 'Sans précision', 'context' => 'Empty option for the optional expected measurement moment.'],
            'stats.schedule.month_day' => ['text' => 'Le {day}', 'context' => 'Day of month option for an expected monthly measurement.'],
            'stats.schedule.weekday.1' => ['text' => 'Lundi', 'context' => 'Weekday option for an expected weekly measurement.'],
            'stats.schedule.weekday.2' => ['text' => 'Mardi', 'context' => 'Weekday option for an expected weekly measurement.'],
            'stats.schedule.weekday.3' => ['text' => 'Mercredi', 'context' => 'Weekday option for an expected weekly measurement.'],
            'stats.schedule.weekday.4' => ['text' => 'Jeudi', 'context' => 'Weekday option for an expected weekly measurement.'],
            'stats.schedule.weekday.5' => ['text' => 'Vendredi', 'context' => 'Weekday option for an expected weekly measurement.'],
            'stats.schedule.weekday.6' => ['text' => 'Samedi', 'context' => 'Weekday option for an expected weekly measurement.'],
            'stats.schedule.weekday.7' => ['text' => 'Dimanche', 'context' => 'Weekday option for an expected weekly measurement.'],
            'stats.schedule.quarter.1' => ['text' => 'Janvier, avril, juillet, octobre', 'context' => 'Quarter cycle option for an expected quarterly measurement.'],
            'stats.schedule.quarter.2' => ['text' => 'Février, mai, août, novembre', 'context' => 'Quarter cycle option for an expected quarterly measurement.'],
            'stats.schedule.quarter.3' => ['text' => 'Mars, juin, septembre, décembre', 'context' => 'Quarter cycle option for an expected quarterly measurement.'],
            'stats.schedule.semester.1' => ['text' => 'Janvier, juillet', 'context' => 'Semester cycle option for an expected semiannual measurement.'],
            'stats.schedule.semester.2' => ['text' => 'Février, août', 'context' => 'Semester cycle option for an expected semiannual measurement.'],
            'stats.schedule.semester.3' => ['text' => 'Mars, septembre', 'context' => 'Semester cycle option for an expected semiannual measurement.'],
            'stats.schedule.semester.4' => ['text' => 'Avril, octobre', 'context' => 'Semester cycle option for an expected semiannual measurement.'],
            'stats.schedule.semester.5' => ['text' => 'Mai, novembre', 'context' => 'Semester cycle option for an expected semiannual measurement.'],
            'stats.schedule.semester.6' => ['text' => 'Juin, décembre', 'context' => 'Semester cycle option for an expected semiannual measurement.'],
            'stats.schedule.month.1' => ['text' => 'Janvier', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.2' => ['text' => 'Février', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.3' => ['text' => 'Mars', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.4' => ['text' => 'Avril', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.5' => ['text' => 'Mai', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.6' => ['text' => 'Juin', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.7' => ['text' => 'Juillet', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.8' => ['text' => 'Août', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.9' => ['text' => 'Septembre', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.10' => ['text' => 'Octobre', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.11' => ['text' => 'Novembre', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.schedule.month.12' => ['text' => 'Décembre', 'context' => 'Month option for an expected yearly measurement.'],
            'stats.form.reference_title' => ['text' => 'Courbe de référence', 'context' => 'Heading of the reference point editor.'],
            'stats.form.reference_help' => ['text' => 'Les extrémités datées utilisent 0 % et 100 %. Les dates intermédiaires sont calculées selon la position du point.', 'context' => 'Help text for the reference point editor.'],
            'stats.form.reference_none' => ['text' => 'Aucune référence', 'context' => 'Reference type select option for none.'],
            'stats.form.reference_ceiling' => ['text' => 'Plafond horizontal', 'context' => 'Reference type select option for ceiling.'],
            'stats.form.reference_objective' => ['text' => 'Objectif ou trajectoire', 'context' => 'Reference type select option for objective.'],
            'stats.form.ceiling_title' => ['text' => 'Plafond', 'context' => 'Heading of the simple ceiling reference editor.'],
            'stats.form.ceiling_help' => ['text' => 'Saisissez une valeur unique. Le repère sera affiché sur toute la période visible du graphique.', 'context' => 'Help text for the simple ceiling reference editor.'],
            'stats.form.ceiling_value' => ['text' => 'Valeur du plafond', 'context' => 'Label for the simple ceiling value input.'],
            'stats.form.position' => ['text' => 'Position (%)', 'context' => 'Reference point editor position label.'],
            'stats.form.point_date' => ['text' => 'Date', 'context' => 'Reference point editor endpoint date label.'],
            'stats.form.point_date_auto' => ['text' => 'Date calculée', 'context' => 'Reference point editor calculated intermediate date label.'],
            'stats.form.point_value' => ['text' => 'Valeur', 'context' => 'Reference point editor value label.'],
            'stats.form.add_point' => ['text' => 'Ajouter un point', 'context' => 'Button adding an intermediate reference point.'],
            'stats.form.remove_point' => ['text' => 'Retirer', 'context' => 'Button removing an intermediate reference point.'],
            'stats.form.endpoint' => ['text' => 'Extrémité datée', 'context' => 'Badge shown on reference curve endpoint rows.'],
            'stats.form.intermediate' => ['text' => 'Point intermédiaire', 'context' => 'Badge shown on intermediate reference curve rows.'],
            'stats.chart.empty' => ['text' => 'Pas encore de données à représenter.', 'context' => 'Empty chart message.'],
            'stats.chart.tooltip.value' => ['text' => 'Valeur', 'context' => 'Tooltip label for a chart point value.'],
            'stats.chart.tooltip.date' => ['text' => 'Date', 'context' => 'Tooltip label for a chart point date.'],
            'stats.import.title' => ['text' => 'Importer un indicateur', 'context' => 'Title of the indicator import picker modal.'],
            'stats.import.edit_title' => ['text' => 'Modifier la source importée', 'context' => 'Title of the indicator import edit picker modal.'],
            'stats.import.search' => ['text' => 'Rechercher', 'context' => 'Label for the indicator picker search field.'],
            'stats.import.search_placeholder' => ['text' => 'Nom ou contexte', 'context' => 'Placeholder for the indicator picker search field.'],
            'stats.import.visible' => ['text' => 'Indicateurs visibles', 'context' => 'Label for the indicator picker result list.'],
            'stats.group.title' => ['text' => 'Grouper des indicateurs', 'context' => 'Title of the indicator group picker modal.'],
            'stats.group.edit_title' => ['text' => 'Modifier le groupe', 'context' => 'Title of the indicator group edit picker modal.'],
            'stats.group.name' => ['text' => 'Nom du groupe', 'context' => 'Label for the indicator group name.'],
            'stats.group.mode' => ['text' => 'Affichage', 'context' => 'Label for the group chart display mode.'],
            'stats.group.mode.overlay' => ['text' => 'Courbes superposées', 'context' => 'Group chart mode drawing one curve per indicator.'],
            'stats.group.mode.sum' => ['text' => 'Somme des valeurs', 'context' => 'Group chart mode aggregating indicator values.'],
            'stats.group.detail.sources' => ['text' => 'Indicateurs sources', 'context' => 'Heading above the source indicator legend in a group detail.'],
            'stats.group.detail.sum' => ['text' => 'Somme calculée', 'context' => 'Legend label for the main aggregated group curve.'],
        ];
    }
}

if (!function_exists('omoStatsLoadTranslationBundle')) {
    function omoStatsLoadTranslationBundle()
    {
        static $bundle = null;
        if (is_array($bundle)) {
            return $bundle;
        }

        $bundle = omoLoadTranslationBundle('omo_stats', omoStatsSourceLang());
        return $bundle;
    }
}

if (!function_exists('omoStatsT')) {
    function omoStatsT($key, array $replace = [])
    {
        return t($key, $replace, omoStatsLoadTranslationBundle(), omoStatsSourceLang());
    }
}

if (!function_exists('omoStatsMeasurementFrequencyLabel')) {
    function omoStatsMeasurementFrequencyLabel($frequency)
    {
        $frequency = StatIndicator::normalizeMeasurementFrequency($frequency);
        return $frequency === null ? omoStatsT('stats.frequency.none') : omoStatsT('stats.frequency.' . $frequency);
    }
}

if (!function_exists('omoStatsMeasurementFrequencyRank')) {
    function omoStatsMeasurementFrequencyRank($frequency)
    {
        $frequency = StatIndicator::normalizeMeasurementFrequency($frequency);
        $ranks = [
            StatIndicator::FREQUENCY_DAILY => 10,
            StatIndicator::FREQUENCY_WEEKLY => 20,
            StatIndicator::FREQUENCY_MONTHLY => 30,
            StatIndicator::FREQUENCY_QUARTERLY => 40,
            StatIndicator::FREQUENCY_SEMIANNUAL => 50,
            StatIndicator::FREQUENCY_YEARLY => 60,
        ];
        return $frequency !== null && isset($ranks[$frequency]) ? $ranks[$frequency] : 70;
    }
}

if (!function_exists('omoStatsMeasurementScheduleOptions')) {
    function omoStatsMeasurementScheduleOptions($frequency)
    {
        $frequency = StatIndicator::normalizeMeasurementFrequency($frequency);
        if ($frequency === null) {
            return [];
        }

        $options = [['value' => '', 'label' => omoStatsT('stats.schedule.none')]];
        if ($frequency === StatIndicator::FREQUENCY_DAILY) {
            for ($hour = 0; $hour < 24; $hour++) {
                $time = str_pad((string)$hour, 2, '0', STR_PAD_LEFT) . ':00';
                $options[] = ['value' => $time, 'label' => $time];
            }
            return $options;
        }
        if ($frequency === StatIndicator::FREQUENCY_WEEKLY) {
            for ($day = 1; $day <= 7; $day++) {
                $options[] = ['value' => (string)$day, 'label' => omoStatsT('stats.schedule.weekday.' . $day)];
            }
            return $options;
        }
        if ($frequency === StatIndicator::FREQUENCY_MONTHLY) {
            for ($day = 1; $day <= 31; $day++) {
                $options[] = ['value' => (string)$day, 'label' => omoStatsT('stats.schedule.month_day', ['day' => $day])];
            }
            return $options;
        }
        if ($frequency === StatIndicator::FREQUENCY_QUARTERLY) {
            for ($month = 1; $month <= 3; $month++) {
                $options[] = ['value' => (string)$month, 'label' => omoStatsT('stats.schedule.quarter.' . $month)];
            }
            return $options;
        }
        if ($frequency === StatIndicator::FREQUENCY_SEMIANNUAL) {
            for ($month = 1; $month <= 6; $month++) {
                $options[] = ['value' => (string)$month, 'label' => omoStatsT('stats.schedule.semester.' . $month)];
            }
            return $options;
        }
        for ($month = 1; $month <= 12; $month++) {
            $options[] = ['value' => (string)$month, 'label' => omoStatsT('stats.schedule.month.' . $month)];
        }
        return $options;
    }
}

if (!function_exists('omoStatsMeasurementScheduleLabel')) {
    function omoStatsMeasurementScheduleLabel($frequency, $schedule)
    {
        $schedule = StatIndicator::normalizeMeasurementSchedule($frequency, $schedule);
        if ($schedule === null) {
            return '';
        }
        foreach (omoStatsMeasurementScheduleOptions($frequency) as $option) {
            if ((string)$option['value'] === $schedule) {
                return (string)$option['label'];
            }
        }
        return '';
    }
}

if (!function_exists('omoStatsGetIndicatorMeasurementTimestamps')) {
    function omoStatsGetIndicatorMeasurementTimestamps(StatIndicator $indicator)
    {
        $timestamps = [];
        foreach ($indicator->getMeasurements() as $measurement) {
            if (!($measurement instanceof StatIndicatorValue)) {
                continue;
            }
            $measuredAt = $measurement->get('measured_at');
            if ($measuredAt instanceof DateTimeInterface && is_numeric($measurement->get('value'))) {
                $timestamps[] = $measuredAt->getTimestamp();
            }
        }
        sort($timestamps, SORT_NUMERIC);
        return $timestamps;
    }
}

if (!function_exists('omoStatsBuildMeasurementDueDate')) {
    function omoStatsBuildMeasurementDueDate($frequency, $schedule, DateTimeImmutable $now)
    {
        return \dbObject\RecurrenceSchedule::getPreviousOccurrence($frequency, $schedule, $now);
    }
}

if (!function_exists('omoStatsGetIndicatorOverdueInfo')) {
    function omoStatsGetIndicatorOverdueInfo(StatIndicator $indicator, ?DateTimeInterface $referenceDate = null): array
    {
        $emptyResult = [
            'is_overdue' => false,
            'severity' => 'none',
            'overdue_days' => 0,
            'overdue_seconds' => 0,
            'period_seconds' => 0,
        ];
        $frequency = StatIndicator::normalizeMeasurementFrequency($indicator->get('measurement_frequency'));
        if ($frequency === null) {
            return $emptyResult;
        }

        $now = $referenceDate instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($referenceDate)
            : new DateTimeImmutable('now');
        $timestamps = omoStatsGetIndicatorMeasurementTimestamps($indicator);
        $schedule = StatIndicator::normalizeMeasurementSchedule($frequency, $indicator->get('measurement_schedule'));
        $periodModifiers = [
            StatIndicator::FREQUENCY_DAILY => '+1 day',
            StatIndicator::FREQUENCY_WEEKLY => '+1 week',
            StatIndicator::FREQUENCY_MONTHLY => '+1 month',
            StatIndicator::FREQUENCY_QUARTERLY => '+3 months',
            StatIndicator::FREQUENCY_SEMIANNUAL => '+6 months',
            StatIndicator::FREQUENCY_YEARLY => '+1 year',
        ];
        $graceModifiers = [
            StatIndicator::FREQUENCY_DAILY => '+1 hour',
            StatIndicator::FREQUENCY_WEEKLY => '+1 day',
            StatIndicator::FREQUENCY_MONTHLY => '+1 week',
            StatIndicator::FREQUENCY_QUARTERLY => '+1 month',
            StatIndicator::FREQUENCY_SEMIANNUAL => '+1 month',
            StatIndicator::FREQUENCY_YEARLY => '+1 month',
        ];
        if (!isset($periodModifiers[$frequency], $graceModifiers[$frequency])) {
            return $emptyResult;
        }

        $dueDate = null;
        $isOverdue = false;
        if ($schedule !== null) {
            $dueDate = omoStatsBuildMeasurementDueDate($frequency, $schedule, $now);
            $isOverdue = $dueDate instanceof DateTimeInterface
                && (count($timestamps) === 0 || end($timestamps) < $dueDate->getTimestamp());
        } elseif (count($timestamps) > 0) {
            $latestTimestamp = $timestamps[count($timestamps) - 1];
            $latestDate = DateTimeImmutable::createFromFormat('U', (string)$latestTimestamp, $now->getTimezone());
            if ($latestDate instanceof DateTimeImmutable) {
                $dueDate = $latestDate->modify($periodModifiers[$frequency]);
                $isOverdue = $now->getTimestamp() > $dueDate->getTimestamp();
            }
        }

        if (!$isOverdue || !($dueDate instanceof DateTimeInterface)) {
            return $emptyResult;
        }

        $overdueSeconds = max(0, $now->getTimestamp() - $dueDate->getTimestamp());
        $periodEnd = $dueDate->modify($periodModifiers[$frequency]);
        $periodSeconds = max(1, $periodEnd->getTimestamp() - $dueDate->getTimestamp());
        $graceEnd = $dueDate->modify($graceModifiers[$frequency]);
        $severity = $now->getTimestamp() < $graceEnd->getTimestamp() ? 'warning' : 'error';

        return [
            'is_overdue' => true,
            'severity' => $severity,
            'overdue_days' => $severity === 'error' ? max(1, (int)ceil($overdueSeconds / 86400)) : 0,
            'overdue_seconds' => $overdueSeconds,
            'period_seconds' => $periodSeconds,
        ];
    }
}

if (!function_exists('omoStatsIsIndicatorOverdue')) {
    function omoStatsIsIndicatorOverdue(StatIndicator $indicator, ?DateTimeInterface $referenceDate = null)
    {
        return omoStatsGetIndicatorOverdueInfo($indicator, $referenceDate)['is_overdue'];
    }
}

if (!function_exists('omoStatsGetGroupOverdueInfo')) {
    function omoStatsGetGroupOverdueInfo(StatIndicatorGroup $group, ?DateTimeInterface $referenceDate = null): array
    {
        $severity = 'none';
        foreach ($group->getItems() as $item) {
            if (!($item instanceof StatIndicatorGroupItem)) {
                continue;
            }
            $indicator = $item->getIndicator();
            if (!($indicator instanceof StatIndicator) || !$indicator->canView()) {
                continue;
            }
            $indicatorSeverity = omoStatsGetIndicatorOverdueInfo($indicator, $referenceDate)['severity'];
            if ($indicatorSeverity === 'error') {
                $severity = 'error';
                break;
            }
            if ($indicatorSeverity === 'warning') {
                $severity = 'warning';
            }
        }
        return [
            'is_overdue' => $severity !== 'none',
            'severity' => $severity,
        ];
    }
}

if (!function_exists('omoStatsIsGroupOverdue')) {
    function omoStatsIsGroupOverdue(StatIndicatorGroup $group, ?DateTimeInterface $referenceDate = null)
    {
        return omoStatsGetGroupOverdueInfo($group, $referenceDate)['is_overdue'];
    }
}

if (!function_exists('omoStatsResolveContext')) {
    function omoStatsResolveContext($organizationId, $currentHolonId = 0)
    {
        $organizationId = (int)$organizationId;
        $currentHolonId = (int)$currentHolonId;
        $organization = new Organization();

        if ($organizationId <= 0 || !$organization->load($organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoStatsT('stats.error.organization')];
        }

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
                return ['status' => false, 'message' => omoStatsT('stats.error.context')];
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

if (!function_exists('omoStatsCanManageContext')) {
    function omoStatsCanManageContext(array $context)
    {
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        if ($currentUserId <= 0) {
            return false;
        }

        $currentHolon = $context['currentHolon'] ?? null;
        if ($currentHolon instanceof Holon) {
            return $currentHolon->canEdit();
        }

        $organization = $context['organization'] ?? null;
        return $organization instanceof Organization && $organization->canEdit();
    }
}

if (!function_exists('omoStatsCanCreateContext')) {
    function omoStatsCanCreateContext(array $context)
    {
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        if ($currentUserId <= 0) {
            return false;
        }

        $useSessionCache = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST';
        $currentHolon = $context['currentHolon'] ?? null;
        if ($currentHolon instanceof Holon) {
            return $currentHolon->isAllowed('CAN_CREATE_INDICATOR', $useSessionCache, $currentUserId);
        }

        $rootHolon = $context['rootHolon'] ?? null;
        if ($rootHolon instanceof Holon) {
            return $rootHolon->isAllowed('CAN_CREATE_INDICATOR', $useSessionCache, $currentUserId);
        }

        $organization = $context['organization'] ?? null;
        return $organization instanceof Organization && $organization->canEdit();
    }
}

if (!function_exists('omoStatsLoadIndicator')) {
    function omoStatsLoadIndicator($indicatorId, $organizationId)
    {
        $indicator = new StatIndicator();
        if (
            (int)$indicatorId <= 0
            || !$indicator->load((int)$indicatorId)
            || (int)$indicator->get('IDorganization') !== (int)$organizationId
            || !(bool)$indicator->get('active')
            || !$indicator->canView()
        ) {
            return null;
        }

        return $indicator;
    }
}

if (!function_exists('omoStatsLoadGroup')) {
    function omoStatsLoadGroup($groupId, $organizationId)
    {
        $group = new StatIndicatorGroup();
        if (
            (int)$groupId <= 0
            || !$group->load((int)$groupId)
            || (int)$group->get('IDorganization') !== (int)$organizationId
            || !(bool)$group->get('active')
            || !$group->canView()
        ) {
            return null;
        }
        return $group;
    }
}

if (!function_exists('omoStatsLoadImport')) {
    function omoStatsLoadImport($importId, $organizationId)
    {
        $import = new StatIndicatorImport();
        if (
            (int)$importId <= 0
            || !$import->load((int)$importId)
            || (int)$import->get('IDorganization') !== (int)$organizationId
            || !(bool)$import->get('active')
        ) {
            return null;
        }
        $indicator = $import->getIndicator();
        return $indicator instanceof StatIndicator && $indicator->canView() ? $import : null;
    }
}

if (!function_exists('omoStatsCanEditContextResource')) {
    function omoStatsCanEditContextResource($resource, array $context)
    {
        if (!omoStatsCanManageContext($context)) {
            return false;
        }
        $currentHolon = $context['currentHolon'] ?? null;
        $currentHolonId = $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0;
        return $resource instanceof StatIndicatorImport || $resource instanceof StatIndicatorGroup
            ? (int)$resource->get('IDholon') === $currentHolonId
            : false;
    }
}

if (!function_exists('omoStatsReferenceTypeLabel')) {
    function omoStatsReferenceTypeLabel($referenceType)
    {
        $referenceType = StatIndicator::normalizeReferenceType($referenceType);
        if ($referenceType === StatIndicator::REFERENCE_CEILING) {
            return omoStatsT('stats.detail.reference_ceiling');
        }
        if ($referenceType === StatIndicator::REFERENCE_OBJECTIVE) {
            return omoStatsT('stats.detail.reference_objective');
        }
        return omoStatsT('stats.detail.reference_none');
    }
}

if (!function_exists('omoStatsFormatNumber')) {
    function omoStatsFormatNumber($value)
    {
        if (!is_numeric($value)) {
            return '';
        }

        $rounded = round((float)$value, 6);
        $decimals = abs($rounded - round($rounded)) < 0.000001 ? 0 : 6;
        $formatted = number_format($rounded, $decimals, ',', ' ');
        return $decimals > 0 ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }
}

if (!function_exists('omoStatsResolveChartScale')) {
    function omoStatsResolveChartScale($minValue, $maxValue, $targetIntervals = 4)
    {
        $minValue = (float)$minValue;
        $maxValue = (float)$maxValue;
        if ($maxValue < $minValue) {
            $swap = $minValue;
            $minValue = $maxValue;
            $maxValue = $swap;
        }

        $targetIntervals = max(1, (int)$targetIntervals);
        $valueRange = $maxValue - $minValue;
        if ($valueRange < 0.000000001) {
            $valueRange = max(1.0, abs($maxValue) * 0.2);
            $minValue -= $valueRange / 2;
            $maxValue += $valueRange / 2;
        }

        $rawStep = $valueRange / $targetIntervals;
        $power = pow(10, floor(log10($rawStep)));
        $normalizedStep = $rawStep / $power;
        if ($normalizedStep < 1.5) {
            $niceStep = 1;
        } elseif ($normalizedStep < 3) {
            $niceStep = 2;
        } elseif ($normalizedStep < 7) {
            $niceStep = 5;
        } else {
            $niceStep = 10;
        }
        $step = $niceStep * $power;

        $scaleMin = floor($minValue / $step) * $step;
        $scaleMax = ceil($maxValue / $step) * $step;
        $scaleMin = abs($scaleMin) < ($step * 0.000000001) ? 0.0 : $scaleMin;
        $scaleMax = abs($scaleMax) < ($step * 0.000000001) ? 0.0 : $scaleMax;
        $intervals = max(1, (int)round(($scaleMax - $scaleMin) / $step));

        return [
            'min' => $scaleMin,
            'max' => $scaleMax,
            'step' => $step,
            'intervals' => $intervals,
        ];
    }
}

if (!function_exists('omoStatsFormatDateTime')) {
    function omoStatsFormatDateTime($value, $withTime = true)
    {
        if (!($value instanceof DateTimeInterface)) {
            return '';
        }

        return $value->format($withTime ? 'd.m.Y H:i' : 'd.m.Y');
    }
}

if (!function_exists('omoStatsFormatChartPointTooltip')) {
    function omoStatsFormatChartPointTooltip(array $point)
    {
        $timestamp = isset($point['timestamp']) ? (int)$point['timestamp'] : 0;
        return omoStatsT('stats.chart.tooltip.value') . ' : ' . omoStatsFormatNumber($point['value'] ?? '')
            . "\n" . omoStatsT('stats.chart.tooltip.date') . ' : ' . date('d.m.Y H:i', $timestamp);
    }
}

if (!function_exists('omoStatsContextLabel')) {
    function omoStatsContextLabel(StatIndicator $indicator)
    {
        $holon = $indicator->getHolon();
        if ($holon instanceof Holon) {
            $label = trim((string)$holon->getDisplayName());
            if ($label !== '') {
                return $label;
            }
        }

        $organization = $indicator->getOrganization();
        return $organization instanceof Organization
            ? trim((string)$organization->get('name'))
            : '';
    }
}

if (!function_exists('omoStatsCollectionItems')) {
    function omoStatsCollectionItems($collection, $className)
    {
        $items = [];
        if (!is_iterable($collection)) {
            return $items;
        }

        foreach ($collection as $item) {
            if ($item instanceof $className) {
                $items[] = $item;
            }
        }
        return $items;
    }
}

if (!function_exists('omoStatsResolveReferenceSeries')) {
    function omoStatsResolveReferenceSeries(array $referencePoints, array $values = [])
    {
        if (count($referencePoints) === 0) {
            return [];
        }

        usort($referencePoints, static function ($left, $right) {
            return (float)$left->get('position_percent') <=> (float)$right->get('position_percent');
        });

        $valueTimes = [];
        foreach ($values as $value) {
            if (!($value instanceof StatIndicatorValue)) {
                continue;
            }
            $measuredAt = $value->get('measured_at');
            if ($measuredAt instanceof DateTimeInterface) {
                $valueTimes[] = $measuredAt->getTimestamp();
            }
        }

        $startTimestamp = count($valueTimes) > 0 ? min($valueTimes) : time();
        $endTimestamp = count($valueTimes) > 0 ? max($valueTimes) : ($startTimestamp + 86400);

        foreach ($referencePoints as $point) {
            $position = (float)$point->get('position_percent');
            $pointAt = $point->get('point_at');
            if ($pointAt instanceof DateTimeInterface && abs($position) < 0.0001) {
                $startTimestamp = $pointAt->getTimestamp();
            }
            if ($pointAt instanceof DateTimeInterface && abs($position - 100.0) < 0.0001) {
                $endTimestamp = $pointAt->getTimestamp();
            }
        }

        if ($endTimestamp <= $startTimestamp) {
            $endTimestamp = $startTimestamp + 86400;
        }

        $resolved = [];
        foreach ($referencePoints as $point) {
            $position = max(0.0, min(100.0, (float)$point->get('position_percent')));
            $pointAt = $point->get('point_at');
            $timestamp = $pointAt instanceof DateTimeInterface
                ? $pointAt->getTimestamp()
                : (int)round($startTimestamp + (($endTimestamp - $startTimestamp) * $position / 100));
            $resolved[] = [
                'timestamp' => $timestamp,
                'value' => (float)$point->get('value'),
                'position' => $position,
            ];
        }

        return $resolved;
    }
}

if (!function_exists('omoStatsGetCeilingValue')) {
    function omoStatsGetCeilingValue(array $referencePoints)
    {
        foreach ($referencePoints as $point) {
            if ($point instanceof StatIndicatorReferencePoint && is_numeric($point->get('value'))) {
                return (float)$point->get('value');
            }
        }

        return null;
    }
}

if (!function_exists('omoStatsGetSeriesValueRange')) {
    function omoStatsGetSeriesValueRange(array $series)
    {
        $values = [];
        foreach ($series as $point) {
            if (is_numeric($point['value'] ?? null)) {
                $values[] = (float)$point['value'];
            }
        }

        if (count($values) === 0) {
            return null;
        }

        return [
            'min' => min($values),
            'max' => max($values),
        ];
    }
}

if (!function_exists('omoStatsGetIndicatorChartSeries')) {
    function omoStatsGetIndicatorChartSeries(StatIndicator $indicator, array $values, array $referencePoints)
    {
        $measureSeries = [];
        foreach ($values as $value) {
            if (!($value instanceof StatIndicatorValue)) {
                continue;
            }
            $measuredAt = $value->get('measured_at');
            if (!($measuredAt instanceof DateTimeInterface) || !is_numeric($value->get('value'))) {
                continue;
            }
            $measureSeries[] = [
                'timestamp' => $measuredAt->getTimestamp(),
                'value' => (float)$value->get('value'),
            ];
        }

        usort($measureSeries, static function (array $left, array $right) {
            return $left['timestamp'] <=> $right['timestamp'];
        });

        $referenceType = StatIndicator::normalizeReferenceType($indicator->get('reference_type'));
        $referenceSeries = $referenceType === StatIndicator::REFERENCE_OBJECTIVE
            ? omoStatsResolveReferenceSeries($referencePoints, $values)
            : [];
        $ceilingValue = $referenceType === StatIndicator::REFERENCE_CEILING
            ? omoStatsGetCeilingValue($referencePoints)
            : null;

        return [
            'measure' => $measureSeries,
            'reference' => $referenceSeries,
            'ceiling' => $ceilingValue,
        ];
    }
}

if (!function_exists('omoStatsGetGroupReferencePoints')) {
    function omoStatsGetGroupReferencePoints(StatIndicatorGroup $group)
    {
        return omoStatsCollectionItems($group->getReferencePoints(), StatIndicatorReferencePoint::class);
    }
}

if (!function_exists('omoStatsGetGroupReferenceSeries')) {
    function omoStatsGetGroupReferenceSeries(StatIndicatorGroup $group)
    {
        if (StatIndicator::normalizeReferenceType($group->get('reference_type')) !== StatIndicator::REFERENCE_OBJECTIVE) {
            return [];
        }

        return omoStatsResolveReferenceSeries(omoStatsGetGroupReferencePoints($group));
    }
}

if (!function_exists('omoStatsGetGroupCeilingValue')) {
    function omoStatsGetGroupCeilingValue(StatIndicatorGroup $group)
    {
        return StatIndicator::normalizeReferenceType($group->get('reference_type')) === StatIndicator::REFERENCE_CEILING
            ? omoStatsGetCeilingValue(omoStatsGetGroupReferencePoints($group))
            : null;
    }
}

if (!function_exists('omoStatsGetGroupLatestSumValue')) {
    function omoStatsGetGroupLatestSumValue(StatIndicatorGroup $group, array $series)
    {
        if (StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode')) !== StatIndicatorGroup::DISPLAY_SUM) {
            return null;
        }

        $latestPoint = null;
        foreach ($series as $seriesItem) {
            if (empty($seriesItem['is_sum'])) {
                continue;
            }
            foreach (($seriesItem['points'] ?? []) as $point) {
                if (!is_numeric($point['timestamp'] ?? null) || !is_numeric($point['value'] ?? null)) {
                    continue;
                }
                if ($latestPoint === null || (int)$point['timestamp'] > (int)$latestPoint['timestamp']) {
                    $latestPoint = [
                        'timestamp' => (int)$point['timestamp'],
                        'value' => (float)$point['value'],
                    ];
                }
            }
        }
        if ($latestPoint === null) {
            return null;
        }

        $referenceValue = StatIndicator::normalizeReferenceType($group->get('reference_type')) === StatIndicator::REFERENCE_CEILING
            ? omoStatsGetGroupCeilingValue($group)
            : omoStatsGetReferenceValueAtTimestamp(
                omoStatsGetGroupReferenceSeries($group),
                $latestPoint['timestamp']
            );
        $latestPoint['referencePercentage'] = is_numeric($referenceValue) && abs((float)$referenceValue) >= 0.000000001
            ? round(($latestPoint['value'] / (float)$referenceValue) * 100, 2)
            : null;
        return $latestPoint;
    }
}

if (!function_exists('omoStatsGetReferenceValueAtTimestamp')) {
    function omoStatsGetReferenceValueAtTimestamp(array $referenceSeries, int $timestamp)
    {
        if (count($referenceSeries) === 0) {
            return null;
        }

        usort($referenceSeries, static function (array $left, array $right) {
            return (int)$left['timestamp'] <=> (int)$right['timestamp'];
        });

        $first = $referenceSeries[0];
        $last = $referenceSeries[count($referenceSeries) - 1];
        if ($timestamp < (int)$first['timestamp'] || $timestamp > (int)$last['timestamp']) {
            return null;
        }

        for ($index = 1; $index < count($referenceSeries); $index++) {
            $left = $referenceSeries[$index - 1];
            $right = $referenceSeries[$index];
            $leftTimestamp = (int)$left['timestamp'];
            $rightTimestamp = (int)$right['timestamp'];
            if ($timestamp > $rightTimestamp) {
                continue;
            }
            if ($rightTimestamp <= $leftTimestamp) {
                return (float)$right['value'];
            }

            $ratio = ($timestamp - $leftTimestamp) / ($rightTimestamp - $leftTimestamp);
            return (float)$left['value'] + (((float)$right['value'] - (float)$left['value']) * $ratio);
        }

        return (float)$last['value'];
    }
}

if (!function_exists('omoStatsGetIndicatorReferencePercentage')) {
    function omoStatsGetIndicatorReferencePercentage(StatIndicator $indicator, $value, array $referencePoints)
    {
        if (!($value instanceof StatIndicatorValue)
            || StatIndicator::normalizeReferenceType($indicator->get('reference_type')) === StatIndicator::REFERENCE_NONE
            || !is_numeric($value->get('value'))
        ) {
            return null;
        }

        if (StatIndicator::normalizeReferenceType($indicator->get('reference_type')) === StatIndicator::REFERENCE_CEILING) {
            $ceilingValue = omoStatsGetCeilingValue($referencePoints);
            return is_numeric($ceilingValue) && abs((float)$ceilingValue) >= 0.000000001
                ? round(((float)$value->get('value') / (float)$ceilingValue) * 100, 2)
                : null;
        }

        $measuredAt = $value->get('measured_at');
        if (!($measuredAt instanceof DateTimeInterface)) {
            return null;
        }

        $referenceSeries = omoStatsResolveReferenceSeries($referencePoints, [$value]);
        $referenceValue = omoStatsGetReferenceValueAtTimestamp($referenceSeries, $measuredAt->getTimestamp());
        if (!is_numeric($referenceValue) || abs((float)$referenceValue) < 0.000000001) {
            return null;
        }

        return round(((float)$value->get('value') / (float)$referenceValue) * 100, 2);
    }
}

if (!function_exists('omoStatsReferenceTimestampRange')) {
    function omoStatsReferenceTimestampRange(array $referenceSeries)
    {
        $timestamps = [];
        foreach ($referenceSeries as $point) {
            if (is_numeric($point['timestamp'] ?? null)) {
                $timestamps[] = (int)$point['timestamp'];
            }
        }
        if (count($timestamps) < 2) {
            return null;
        }

        return [
            'start' => min($timestamps),
            'end' => max($timestamps),
        ];
    }
}

if (!function_exists('omoStatsClipChartSeries')) {
    function omoStatsClipChartSeries(array $series, int $startTimestamp, int $endTimestamp)
    {
        $sorted = array_values(array_filter($series, static function (array $point) {
            return is_numeric($point['timestamp'] ?? null) && is_numeric($point['value'] ?? null);
        }));
        usort($sorted, static function (array $left, array $right) {
            return (int)$left['timestamp'] <=> (int)$right['timestamp'];
        });

        $clipped = array_values(array_filter($sorted, static function (array $point) use ($startTimestamp, $endTimestamp) {
            return (int)$point['timestamp'] >= $startTimestamp && (int)$point['timestamp'] <= $endTimestamp;
        }));

        $interpolate = static function (int $timestamp) use ($sorted) {
            for ($index = 1; $index < count($sorted); $index++) {
                $left = $sorted[$index - 1];
                $right = $sorted[$index];
                $leftTimestamp = (int)$left['timestamp'];
                $rightTimestamp = (int)$right['timestamp'];
                if ($timestamp === $leftTimestamp) {
                    return ['timestamp' => $timestamp, 'value' => (float)$left['value']];
                }
                if ($timestamp === $rightTimestamp) {
                    return ['timestamp' => $timestamp, 'value' => (float)$right['value']];
                }
                if ($leftTimestamp < $timestamp && $timestamp < $rightTimestamp) {
                    $ratio = ($timestamp - $leftTimestamp) / ($rightTimestamp - $leftTimestamp);
                    return [
                        'timestamp' => $timestamp,
                        'value' => (float)$left['value'] + (((float)$right['value'] - (float)$left['value']) * $ratio),
                    ];
                }
            }
            return null;
        };

        foreach ([$startTimestamp, $endTimestamp] as $boundaryTimestamp) {
            $boundaryPoint = $interpolate($boundaryTimestamp);
            if ($boundaryPoint === null) {
                continue;
            }
            $alreadyIncluded = false;
            foreach ($clipped as $point) {
                if ((int)$point['timestamp'] === $boundaryTimestamp) {
                    $alreadyIncluded = true;
                    break;
                }
            }
            if (!$alreadyIncluded) {
                $clipped[] = $boundaryPoint;
            }
        }

        usort($clipped, static function (array $left, array $right) {
            return (int)$left['timestamp'] <=> (int)$right['timestamp'];
        });
        return $clipped;
    }
}

if (!function_exists('omoStatsSmallIndicatorTimestampRange')) {
    function omoStatsSmallIndicatorTimestampRange(StatIndicator $indicator, array $measureSeries)
    {
        if (count($measureSeries) === 0) {
            return null;
        }

        $timestamps = array_map(static function (array $point) {
            return (int)$point['timestamp'];
        }, $measureSeries);
        $latestTimestamp = max($timestamps);
        $latestDate = (new DateTimeImmutable())->setTimestamp($latestTimestamp);
        $frequency = StatIndicator::normalizeMeasurementFrequency($indicator->get('measurement_frequency'));
        $months = [
            StatIndicator::FREQUENCY_MONTHLY => 12,
            StatIndicator::FREQUENCY_QUARTERLY => 36,
            StatIndicator::FREQUENCY_SEMIANNUAL => 72,
            StatIndicator::FREQUENCY_YEARLY => 144,
        ];

        if (isset($months[$frequency])) {
            $startDate = $latestDate->modify('-' . $months[$frequency] . ' months');
        } elseif ($frequency === StatIndicator::FREQUENCY_WEEKLY) {
            $startDate = $latestDate->modify('-12 weeks');
        } elseif ($frequency === StatIndicator::FREQUENCY_DAILY) {
            $startDate = $latestDate->modify('-12 days');
        } else {
            return null;
        }

        return [
            'start' => $startDate->getTimestamp(),
            'end' => $latestTimestamp,
        ];
    }
}

if (!function_exists('omoStatsSmallGroupTimestampRange')) {
    function omoStatsSmallGroupTimestampRange(array $series)
    {
        $smallRange = null;
        foreach ($series as $seriesItem) {
            $indicator = $seriesItem['indicator'] ?? null;
            if (!($indicator instanceof StatIndicator)) {
                continue;
            }
            $sourceRange = omoStatsSmallIndicatorTimestampRange($indicator, $seriesItem['points'] ?? []);
            if ($sourceRange === null) {
                continue;
            }
            if ($smallRange === null) {
                $smallRange = $sourceRange;
                continue;
            }
            $smallRange['start'] = min($smallRange['start'], $sourceRange['start']);
            $smallRange['end'] = max($smallRange['end'], $sourceRange['end']);
        }
        return $smallRange;
    }
}

if (!function_exists('omoStatsChartSeriesTimestampRange')) {
    function omoStatsChartSeriesTimestampRange(array $series, ?array $limits = null)
    {
        $timestamps = [];
        foreach ($series as $point) {
            if (!is_numeric($point['timestamp'] ?? null)) {
                continue;
            }
            $timestamp = (int)$point['timestamp'];
            if (
                $limits !== null
                && ($timestamp < (int)$limits['start'] || $timestamp > (int)$limits['end'])
            ) {
                continue;
            }
            $timestamps[] = $timestamp;
        }

        return count($timestamps) > 0
            ? ['start' => min($timestamps), 'end' => max($timestamps)]
            : null;
    }
}

if (!function_exists('omoStatsGroupedChartSeriesTimestampRange')) {
    function omoStatsGroupedChartSeriesTimestampRange(array $series, ?array $limits = null)
    {
        $points = [];
        foreach ($series as $seriesItem) {
            foreach (($seriesItem['points'] ?? []) as $point) {
                $points[] = $point;
            }
        }

        return omoStatsChartSeriesTimestampRange($points, $limits);
    }
}

if (!function_exists('omoStatsBuildIndicatorChartData')) {
    function omoStatsBuildIndicatorChartData(StatIndicator $indicator, array $values, array $referencePoints, $isOverdue = null)
    {
        if ($isOverdue === null) {
            $isOverdue = omoStatsGetIndicatorOverdueInfo($indicator)['severity'];
        }
        $overdueSeverity = is_string($isOverdue) ? $isOverdue : ($isOverdue ? 'error' : 'none');
        $series = omoStatsGetIndicatorChartSeries($indicator, $values, $referencePoints);
        return [
            'type' => 'indicator',
            'label' => (string)$indicator->get('name'),
            'measure' => $series['measure'],
            'reference' => $series['reference'],
            'ceiling' => $series['ceiling'],
            'minimumValue' => is_numeric($indicator->get('chart_min_value')) ? (float)$indicator->get('chart_min_value') : null,
            'defaultRange' => omoStatsReferenceTimestampRange($series['reference'])
                ?: omoStatsChartSeriesTimestampRange(
                    $series['measure'],
                    omoStatsSmallIndicatorTimestampRange($indicator, $series['measure'])
                ),
            'overdue' => $overdueSeverity !== 'none',
            'overdueSeverity' => $overdueSeverity,
            'tooltip' => [
                'value' => omoStatsT('stats.chart.tooltip.value'),
                'date' => omoStatsT('stats.chart.tooltip.date'),
            ],
        ];
    }
}

if (!function_exists('omoStatsBuildGroupChartData')) {
    function omoStatsBuildGroupChartData(StatIndicatorGroup $group, array $series, $isOverdue = null)
    {
        if ($isOverdue === null) {
            $isOverdue = omoStatsGetGroupOverdueInfo($group)['severity'];
        }
        $overdueSeverity = is_string($isOverdue) ? $isOverdue : ($isOverdue ? 'error' : 'none');
        $dataSeries = [];
        foreach ($series as $seriesIndex => $seriesItem) {
            $points = [];
            foreach (($seriesItem['points'] ?? []) as $point) {
                if (!is_numeric($point['timestamp'] ?? null) || !is_numeric($point['value'] ?? null)) {
                    continue;
                }
                $points[] = [
                    'timestamp' => (int)$point['timestamp'],
                    'value' => (float)$point['value'],
                ];
            }
            if (count($points) === 0) {
                continue;
            }
            $dataSeries[] = [
                'points' => $points,
                'background' => !empty($seriesItem['is_background']),
                'sum' => !empty($seriesItem['is_sum']),
                'sourceIndex' => isset($seriesItem['source_index']) ? (int)$seriesItem['source_index'] : (int)$seriesIndex,
            ];
        }
        $referenceSeries = omoStatsGetGroupReferenceSeries($group);
        $ceilingValue = omoStatsGetGroupCeilingValue($group);
        return [
            'type' => 'group',
            'label' => (string)$group->get('name'),
            'series' => $dataSeries,
            'reference' => $referenceSeries,
            'ceiling' => $ceilingValue,
            'minimumValue' => is_numeric($group->get('chart_min_value')) ? (float)$group->get('chart_min_value') : null,
            'defaultRange' => omoStatsReferenceTimestampRange($referenceSeries)
                ?: omoStatsGroupedChartSeriesTimestampRange(
                    $dataSeries,
                    omoStatsSmallGroupTimestampRange($series)
                ),
            'overdue' => $overdueSeverity !== 'none',
            'overdueSeverity' => $overdueSeverity,
            'tooltip' => [
                'value' => omoStatsT('stats.chart.tooltip.value'),
                'date' => omoStatsT('stats.chart.tooltip.date'),
            ],
        ];
    }
}

if (!function_exists('omoStatsChartRangeDays')) {
    function omoStatsChartRangeDays(array $chartData)
    {
        $timestamps = [];
        if (($chartData['type'] ?? '') === 'group') {
            foreach (($chartData['series'] ?? []) as $seriesItem) {
                foreach (($seriesItem['points'] ?? []) as $point) {
                    if (is_numeric($point['timestamp'] ?? null)) {
                        $timestamps[] = (int)$point['timestamp'];
                    }
                }
            }
            foreach (($chartData['reference'] ?? []) as $point) {
                if (is_numeric($point['timestamp'] ?? null)) {
                    $timestamps[] = (int)$point['timestamp'];
                }
            }
        } else {
            foreach (['measure', 'reference'] as $seriesKey) {
                foreach (($chartData[$seriesKey] ?? []) as $point) {
                    if (is_numeric($point['timestamp'] ?? null)) {
                        $timestamps[] = (int)$point['timestamp'];
                    }
                }
            }
        }
        if (count($timestamps) === 0) {
            return null;
        }
        return [
            'start' => (int)floor(min($timestamps) / 86400),
            'end' => (int)floor(max($timestamps) / 86400),
        ];
    }
}

if (!function_exists('omoStatsChartReferenceRangeDays')) {
    function omoStatsChartReferenceRangeDays(array $chartData)
    {
        if (!in_array($chartData['type'] ?? '', ['indicator', 'group'], true)) {
            return null;
        }

        $referenceRange = omoStatsReferenceTimestampRange($chartData['reference'] ?? []);
        if ($referenceRange === null) {
            return null;
        }

        return [
            'start' => (int)floor($referenceRange['start'] / 86400),
            'end' => (int)floor($referenceRange['end'] / 86400),
        ];
    }
}

if (!function_exists('omoStatsRenderInteractiveChartRange')) {
    function omoStatsRenderInteractiveChartRange(array $chartData)
    {
        $range = omoStatsChartRangeDays($chartData);
        if ($range === null || $range['start'] >= $range['end']) {
            return '';
        }
        $json = json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return '';
        }
        $start = (int)$range['start'];
        $end = (int)$range['end'];
        $defaultRange = null;
        if (is_array($chartData['defaultRange'] ?? null)) {
            $defaultRange = [
                'start' => (int)floor((int)($chartData['defaultRange']['start'] ?? 0) / 86400),
                'end' => (int)floor((int)($chartData['defaultRange']['end'] ?? 0) / 86400),
            ];
        }
        $defaultRange = $defaultRange ?: (omoStatsChartReferenceRangeDays($chartData) ?: $range);
        $defaultStart = max($start, min($end, (int)$defaultRange['start']));
        $defaultEnd = max($defaultStart, min($end, (int)$defaultRange['end']));
        return '<div class="omo-stats-chart-range" data-omo-stats-chart-range'
            . ' data-omo-stats-chart-data="' . omoApiEscape($json) . '"'
            . ' data-start-day="' . $start . '" data-end-day="' . $end . '"'
            . ' data-initial-start-day="' . $defaultStart . '" data-initial-end-day="' . $defaultEnd . '">'
            . '<div class="omo-stats-chart-range__header">'
            . '<strong>' . omoApiEscape(omoStatsT('stats.detail.range.label')) . '</strong>'
            . '<output data-omo-stats-chart-range-output></output>'
            . '</div>'
            . '<div class="omo-stats-chart-range__track">'
            . '<span class="omo-stats-chart-range__selection" data-omo-stats-chart-range-selection></span>'
            . '<input type="range" min="' . $start . '" max="' . $end . '" step="1" value="' . $start . '" aria-label="' . omoApiEscape(omoStatsT('stats.detail.range.start')) . '" data-omo-stats-chart-range-start>'
            . '<input type="range" min="' . $start . '" max="' . $end . '" step="1" value="' . $end . '" aria-label="' . omoApiEscape(omoStatsT('stats.detail.range.end')) . '" data-omo-stats-chart-range-end>'
            . '</div>'
            . '<div class="omo-stats-chart-range__limits" aria-hidden="true">'
            . '<span data-omo-stats-chart-range-min></span><span data-omo-stats-chart-range-max></span>'
            . '</div>'
            . '</div>';
    }
}

if (!function_exists('omoStatsAppendSimpleChartScale')) {
    function omoStatsAppendSimpleChartScale(string &$svg, string $variant, int $width, int $height, int $paddingLeft, int $paddingRight, int $paddingTop, int $paddingBottom, $minValue, $maxValue): void
    {
        if (!in_array($variant, ['compact', 'card'], true)) {
            return;
        }

        $lineStart = $paddingLeft + ($variant === 'compact' ? 18 : 30);
        $lineEnd = $width - $paddingRight;
        $labelX = $variant === 'card' ? 5 : 0;
        $topLabelY = $paddingTop + 4;
        $bottomLabelY = $height - $paddingBottom;
        $svg .= '<line class="omo-stats-chart__scale-line" x1="' . $lineStart . '" y1="' . $paddingTop . '" x2="' . $lineEnd . '" y2="' . $paddingTop . '"/>';
        $svg .= '<line class="omo-stats-chart__scale-line" x1="' . $lineStart . '" y1="' . ($height - $paddingBottom) . '" x2="' . $lineEnd . '" y2="' . ($height - $paddingBottom) . '"/>';
        $svg .= '<text class="omo-stats-chart__scale-label" x="' . $labelX . '" y="' . $topLabelY . '">' . omoApiEscape(omoStatsFormatNumber($maxValue)) . '</text>';
        $svg .= '<text class="omo-stats-chart__scale-label" x="' . $labelX . '" y="' . $bottomLabelY . '">' . omoApiEscape(omoStatsFormatNumber($minValue)) . '</text>';
    }
}

if (!function_exists('omoStatsRenderChart')) {
    function omoStatsRenderChart(StatIndicator $indicator, array $values, array $referencePoints, $variant = 'card', $isOverdue = null, $withTooltips = false)
    {
        $variant = in_array($variant, ['compact', 'card', 'large'], true) ? $variant : 'card';
        if ($isOverdue === null) {
            $isOverdue = omoStatsGetIndicatorOverdueInfo($indicator)['severity'];
        }
        $overdueSeverity = is_string($isOverdue) ? $isOverdue : ($isOverdue ? 'error' : 'none');
        $chartSeries = omoStatsGetIndicatorChartSeries($indicator, $values, $referencePoints);
        $measureSeries = $chartSeries['measure'];
        $referenceSeries = $chartSeries['reference'];
        $ceilingValue = is_numeric($chartSeries['ceiling'] ?? null) ? (float)$chartSeries['ceiling'] : null;
        $minimumValue = is_numeric($indicator->get('chart_min_value')) ? (float)$indicator->get('chart_min_value') : null;

        if (count($measureSeries) === 0 && count($referenceSeries) === 0) {
            return '<div class="omo-stats-chart-empty">' . omoApiEscape(omoStatsT('stats.chart.empty')) . '</div>';
        }

        $width = $variant === 'compact' ? 180 : ($variant === 'large' ? 900 : 520);
        $height = $variant === 'compact' ? 54 : ($variant === 'large' ? 340 : 190);
        $paddingLeft = $variant === 'large' ? 64 : ($variant === 'card' ? 18 : 2);
        $paddingRight = $variant === 'large' ? 24 : ($variant === 'card' ? 18 : 2);
        $paddingTop = $variant === 'large' ? 24 : ($variant === 'card' ? 16 : 2);
        $paddingBottom = $variant === 'large' ? 42 : ($variant === 'card' ? 18 : 2);
        $plotWidth = max(1, $width - $paddingLeft - $paddingRight);
        $plotHeight = max(1, $height - $paddingTop - $paddingBottom);
        $referenceRange = omoStatsReferenceTimestampRange($referenceSeries);
        $displayTimeRange = $referenceRange;
        if ($referenceRange !== null) {
            $measureSeries = omoStatsClipChartSeries($measureSeries, $referenceRange['start'], $referenceRange['end']);
        } elseif ($variant !== 'large') {
            $smallRange = omoStatsSmallIndicatorTimestampRange($indicator, $measureSeries);
            if ($smallRange !== null) {
                $measureSeries = omoStatsClipChartSeries($measureSeries, $smallRange['start'], $smallRange['end']);
            }
        }
        $allSeries = array_merge($measureSeries, $referenceSeries);
        $timestamps = array_column($allSeries, 'timestamp');
        $numbers = array_column($allSeries, 'value');
        if ($ceilingValue !== null) {
            $numbers[] = $ceilingValue;
        }
        if ($minimumValue !== null) {
            $numbers[] = $minimumValue;
        }
        $minTimestamp = $displayTimeRange !== null ? $displayTimeRange['start'] : min($timestamps);
        $maxTimestamp = $displayTimeRange !== null ? $displayTimeRange['end'] : max($timestamps);
        if ($maxTimestamp <= $minTimestamp) {
            $minTimestamp -= 43200;
            $maxTimestamp += 43200;
        }

        $chartScale = omoStatsResolveChartScale(min($numbers), max($numbers));
        $minValue = $chartScale['min'];
        $maxValue = $chartScale['max'];

        $mapPoint = static function (array $point) use ($minTimestamp, $maxTimestamp, $minValue, $maxValue, $paddingLeft, $paddingTop, $plotWidth, $plotHeight) {
            $x = $paddingLeft + (($point['timestamp'] - $minTimestamp) / ($maxTimestamp - $minTimestamp)) * $plotWidth;
            $y = $paddingTop + (1 - (($point['value'] - $minValue) / ($maxValue - $minValue))) * $plotHeight;
            return [round($x, 2), round($y, 2)];
        };

        $measureCoordinates = array_map($mapPoint, $measureSeries);
        $referenceCoordinates = array_map($mapPoint, $referenceSeries);
        $measureValueRange = omoStatsGetSeriesValueRange($measureSeries);
        $minimumLineValue = $minimumValue !== null
            && $measureValueRange !== null
            && $minimumValue >= $measureValueRange['min']
            && $minimumValue <= $measureValueRange['max']
            ? $minimumValue
            : null;
        $coordinateString = static function (array $coordinates) {
            return implode(' ', array_map(static function (array $point) {
                return $point[0] . ',' . $point[1];
            }, $coordinates));
        };

        $chartId = 'omo-stats-chart-' . (int)$indicator->getId() . '-' . $variant . '-' . substr(md5((string)count($measureSeries) . ':' . (string)count($referenceSeries)), 0, 8);
        $svg = '<svg class="omo-stats-chart omo-stats-chart--' . omoApiEscape($variant) . ($overdueSeverity === 'error' ? ' omo-stats-chart--overdue' : ($overdueSeverity === 'warning' ? ' omo-stats-chart--warning' : '')) . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . omoApiEscape((string)$indicator->get('name')) . '">';
        $svg .= '<defs><linearGradient id="' . $chartId . '-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity="0.24"/><stop offset="1" stop-color="currentColor" stop-opacity="0.02"/></linearGradient></defs>';

        if ($variant === 'large') {
            for ($gridIndex = 0; $gridIndex <= $chartScale['intervals']; $gridIndex++) {
                $ratio = $gridIndex / $chartScale['intervals'];
                $gridY = round($paddingTop + ($plotHeight * $ratio), 2);
                $gridValue = $maxValue - ($chartScale['step'] * $gridIndex);
                $svg .= '<line class="omo-stats-chart__grid" x1="' . $paddingLeft . '" y1="' . $gridY . '" x2="' . ($width - $paddingRight) . '" y2="' . $gridY . '"/>';
                $svg .= '<text class="omo-stats-chart__axis-label" x="' . ($paddingLeft - 10) . '" y="' . ($gridY + 4) . '" text-anchor="end">' . omoApiEscape(omoStatsFormatNumber($gridValue)) . '</text>';
            }
            $svg .= '<text class="omo-stats-chart__axis-label" x="' . $paddingLeft . '" y="' . ($height - 12) . '">' . omoApiEscape(date('d.m.Y', $minTimestamp)) . '</text>';
            $svg .= '<text class="omo-stats-chart__axis-label" x="' . ($width - $paddingRight) . '" y="' . ($height - 12) . '" text-anchor="end">' . omoApiEscape(date('d.m.Y', $maxTimestamp)) . '</text>';
        }

        if (count($measureCoordinates) > 0) {
            $areaPoints = $coordinateString($measureCoordinates)
                . ' ' . $measureCoordinates[count($measureCoordinates) - 1][0] . ',' . ($paddingTop + $plotHeight)
                . ' ' . $measureCoordinates[0][0] . ',' . ($paddingTop + $plotHeight);
            $svg .= '<polygon class="omo-stats-chart__area" points="' . $areaPoints . '" fill="url(#' . $chartId . '-area)"/>';
            if (count($measureCoordinates) > 1) {
                $svg .= '<polyline class="omo-stats-chart__line" points="' . $coordinateString($measureCoordinates) . '"/>';
            }
            if ($variant !== 'compact') {
                foreach ($measureCoordinates as $pointIndex => $point) {
                    $pointTooltip = $withTooltips ? omoStatsFormatChartPointTooltip($measureSeries[$pointIndex]) : '';
                    $pointTooltipAttributes = $withTooltips
                        ? ' data-omo-stats-chart-tooltip="' . omoApiEscape($pointTooltip) . '" tabindex="0" aria-label="' . omoApiEscape($pointTooltip) . '"'
                        : '';
                    $svg .= '<circle class="omo-stats-chart__point" cx="' . $point[0] . '" cy="' . $point[1] . '" r="' . ($variant === 'large' ? 4 : 3) . '"' . $pointTooltipAttributes . '/>';
                }
            } else {
                $lastPoint = $measureCoordinates[count($measureCoordinates) - 1];
                $pointTooltip = $withTooltips ? omoStatsFormatChartPointTooltip($measureSeries[count($measureSeries) - 1]) : '';
                $pointTooltipAttributes = $withTooltips
                    ? ' data-omo-stats-chart-tooltip="' . omoApiEscape($pointTooltip) . '" tabindex="0" aria-label="' . omoApiEscape($pointTooltip) . '"'
                    : '';
                $svg .= '<circle class="omo-stats-chart__point" cx="' . $lastPoint[0] . '" cy="' . $lastPoint[1] . '" r="2.5"' . $pointTooltipAttributes . '/>';
            }
        }

        if (count($referenceCoordinates) > 1) {
            $svg .= '<polyline class="omo-stats-chart__reference" points="' . $coordinateString($referenceCoordinates) . '"/>';
        }
        if ($ceilingValue !== null) {
            $ceilingY = $mapPoint(['timestamp' => $minTimestamp, 'value' => $ceilingValue])[1];
            $svg .= '<line class="omo-stats-chart__reference omo-stats-chart__reference--ceiling" x1="' . $paddingLeft . '" y1="' . $ceilingY . '" x2="' . ($width - $paddingRight) . '" y2="' . $ceilingY . '"/>';
        }
        if ($minimumLineValue !== null) {
            $minimumY = $mapPoint(['timestamp' => $minTimestamp, 'value' => $minimumLineValue])[1];
            $svg .= '<line class="omo-stats-chart__baseline" x1="' . $paddingLeft . '" y1="' . $minimumY . '" x2="' . ($width - $paddingRight) . '" y2="' . $minimumY . '"/>';
        }

        omoStatsAppendSimpleChartScale($svg, $variant, $width, $height, $paddingLeft, $paddingRight, $paddingTop, $paddingBottom, $minValue, $maxValue);

        $svg .= '</svg>';
        return $svg;
    }
}

if (!function_exists('omoStatsGroupTimeBucketSeconds')) {
    function omoStatsGroupTimeBucketSeconds(array $series)
    {
        $allTimestamps = [];
        $gaps = [];
        foreach ($series as $seriesItem) {
            $points = $seriesItem['points'] ?? [];
            foreach ($points as $point) {
                $allTimestamps[] = (int)$point['timestamp'];
            }
            for ($index = 1; $index < count($points); $index++) {
                $gap = (int)$points[$index]['timestamp'] - (int)$points[$index - 1]['timestamp'];
                if ($gap > 0) {
                    $gaps[] = $gap;
                }
            }
        }
        if (count($allTimestamps) < 2) {
            return 3600;
        }

        $range = max($allTimestamps) - min($allTimestamps);
        $rangeBucket = $range <= (10 * 86400) ? 3600 : ($range <= (730 * 86400) ? 86400 : 604800);
        if (count($gaps) === 0) {
            return $rangeBucket;
        }

        sort($gaps, SORT_NUMERIC);
        $middle = (int)floor(count($gaps) / 2);
        $medianGap = count($gaps) % 2 === 0
            ? ($gaps[$middle - 1] + $gaps[$middle]) / 2
            : $gaps[$middle];
        $maximumBucket = min($rangeBucket, $medianGap);
        $buckets = [1, 60, 300, 900, 3600, 21600, 86400, 604800];
        $bucket = 1;
        foreach ($buckets as $candidate) {
            if ($candidate <= $maximumBucket) {
                $bucket = $candidate;
            }
        }
        return $bucket;
    }
}

if (!function_exists('omoStatsNormalizeGroupSeriesTimestamps')) {
    function omoStatsNormalizeGroupSeriesTimestamps(array $series, $bucketSeconds)
    {
        $bucketSeconds = max(1, (int)$bucketSeconds);
        foreach ($series as &$seriesItem) {
            $normalizedPoints = [];
            foreach ($seriesItem['points'] as $point) {
                $timestamp = (int)$point['timestamp'];
                $normalizedTimestamp = (int)(floor($timestamp / $bucketSeconds) * $bucketSeconds);
                // Keep the latest value when multiple measurements share one display period.
                $normalizedPoints[$normalizedTimestamp] = [
                    'timestamp' => $normalizedTimestamp,
                    'value' => (float)$point['value'],
                ];
            }
            ksort($normalizedPoints, SORT_NUMERIC);
            $seriesItem['points'] = array_values($normalizedPoints);
        }
        unset($seriesItem);
        return $series;
    }
}

if (!function_exists('omoStatsGetGroupSeries')) {
    function omoStatsGetGroupSeries(StatIndicatorGroup $group)
    {
        $series = [];
        foreach ($group->getItems() as $item) {
            if (!($item instanceof StatIndicatorGroupItem)) {
                continue;
            }
            $indicator = $item->getIndicator();
            if (!($indicator instanceof StatIndicator) || !$indicator->canView()) {
                continue;
            }

            $points = [];
            foreach ($indicator->getMeasurements() as $measurement) {
                if (!($measurement instanceof StatIndicatorValue)) {
                    continue;
                }
                $measuredAt = $measurement->get('measured_at');
                if (!($measuredAt instanceof DateTimeInterface) || !is_numeric($measurement->get('value'))) {
                    continue;
                }
                $points[] = [
                    'timestamp' => $measuredAt->getTimestamp(),
                    'value' => (float)$measurement->get('value'),
                ];
            }
            usort($points, static function (array $left, array $right) {
                return $left['timestamp'] <=> $right['timestamp'];
            });
            if (count($points) > 0) {
                $series[] = ['indicator' => $indicator, 'points' => $points];
            }
        }

        if (StatIndicatorGroup::normalizeDisplayMode($group->get('display_mode')) !== StatIndicatorGroup::DISPLAY_SUM) {
            return $series;
        }

        $series = omoStatsNormalizeGroupSeriesTimestamps($series, omoStatsGroupTimeBucketSeconds($series));

        $timestamps = [];
        foreach ($series as $seriesItem) {
            $points = $seriesItem['points'];
            foreach ($points as $point) {
                $timestamps[(int)$point['timestamp']] = true;
            }
        }

        $interpolate = static function (array $points, $timestamp) {
            $timestamp = (int)$timestamp;
            $firstTimestamp = (int)$points[0]['timestamp'];
            $lastTimestamp = (int)$points[count($points) - 1]['timestamp'];
            if ($timestamp < $firstTimestamp || $timestamp > $lastTimestamp) {
                return 0.0;
            }
            foreach ($points as $index => $point) {
                $pointTimestamp = (int)$point['timestamp'];
                if ($pointTimestamp === $timestamp) {
                    return (float)$point['value'];
                }
                if ($pointTimestamp > $timestamp && $index > 0) {
                    $previous = $points[$index - 1];
                    $previousTimestamp = (int)$previous['timestamp'];
                    $ratio = ($timestamp - $previousTimestamp) / ($pointTimestamp - $previousTimestamp);
                    return (float)$previous['value'] + (((float)$point['value'] - (float)$previous['value']) * $ratio);
                }
            }
            return 0.0;
        };

        $sumPoints = [];
        foreach (array_keys($timestamps) as $timestamp) {
            $timestamp = (int)$timestamp;
            $sum = 0.0;
            foreach ($series as $seriesItem) {
                $value = $interpolate($seriesItem['points'], $timestamp);
                $sum += $value;
            }
            $sumPoints[] = ['timestamp' => $timestamp, 'value' => $sum];
        }
        usort($sumPoints, static function (array $left, array $right) {
            return $left['timestamp'] <=> $right['timestamp'];
        });
        if (count($sumPoints) === 0) {
            return [];
        }

        $backgroundSeries = [];
        foreach ($series as $sourceIndex => $seriesItem) {
            $seriesItem['is_background'] = true;
            $seriesItem['source_index'] = $sourceIndex;
            $backgroundSeries[] = $seriesItem;
        }
        $backgroundSeries[] = [
            'indicator' => null,
            'points' => $sumPoints,
            'is_sum' => true,
        ];
        return $backgroundSeries;
    }
}

if (!function_exists('omoStatsRenderGroupChart')) {
    function omoStatsRenderGroupChart(StatIndicatorGroup $group, array $series, $variant = 'card', $isOverdue = null, $withTooltips = false)
    {
        $variant = in_array($variant, ['compact', 'card', 'large'], true) ? $variant : 'card';
        if ($isOverdue === null) {
            $isOverdue = omoStatsGetGroupOverdueInfo($group)['severity'];
        }
        $overdueSeverity = is_string($isOverdue) ? $isOverdue : ($isOverdue ? 'error' : 'none');
        $referenceSeries = omoStatsGetGroupReferenceSeries($group);
        $groupCeilingValue = omoStatsGetGroupCeilingValue($group);
        $ceilingValue = is_numeric($groupCeilingValue) ? (float)$groupCeilingValue : null;
        $minimumValue = is_numeric($group->get('chart_min_value')) ? (float)$group->get('chart_min_value') : null;
        $referenceRange = omoStatsReferenceTimestampRange($referenceSeries);
        $displayTimeRange = $referenceRange;
        $renderSeries = $series;
        if ($referenceRange !== null) {
            $renderSeries = array_values(array_filter($series, static function (array $seriesItem) {
                return empty($seriesItem['is_background']);
            }));
            foreach ($renderSeries as &$seriesItem) {
                $seriesItem['points'] = omoStatsClipChartSeries($seriesItem['points'] ?? [], $referenceRange['start'], $referenceRange['end']);
            }
            unset($seriesItem);
        } elseif ($variant !== 'large') {
            $renderSeries = array_values(array_filter($series, static function (array $seriesItem) {
                return empty($seriesItem['is_background']);
            }));
            $smallRange = omoStatsSmallGroupTimestampRange($series);
            if ($smallRange !== null) {
                foreach ($renderSeries as &$seriesItem) {
                    $seriesItem['points'] = omoStatsClipChartSeries($seriesItem['points'] ?? [], $smallRange['start'], $smallRange['end']);
                }
                unset($seriesItem);
                $renderSeries = array_values(array_filter($renderSeries, static function (array $seriesItem) {
                    return count($seriesItem['points'] ?? []) > 0;
                }));
            }
        }
        if (count($referenceSeries) > 0) {
            $renderSeries[] = [
                'points' => $referenceSeries,
                'is_reference' => true,
                'source_index' => 0,
            ];
        }
        if (count($renderSeries) === 0) {
            return '<div class="omo-stats-chart-empty">' . omoApiEscape(omoStatsT('stats.chart.empty')) . '</div>';
        }

        $width = $variant === 'compact' ? 180 : ($variant === 'large' ? 900 : 520);
        $height = $variant === 'compact' ? 54 : ($variant === 'large' ? 340 : 190);
        $paddingLeft = $variant === 'large' ? 64 : ($variant === 'compact' ? 3 : 18);
        $paddingRight = $variant === 'large' ? 24 : ($variant === 'compact' ? 3 : 18);
        $paddingTop = $variant === 'large' ? 24 : ($variant === 'compact' ? 3 : 18);
        $paddingBottom = $variant === 'large' ? 42 : ($variant === 'compact' ? 3 : 18);
        $allPoints = [];
        $measurePoints = [];
        foreach ($renderSeries as $seriesItem) {
            $allPoints = array_merge($allPoints, $seriesItem['points']);
            if (empty($seriesItem['is_reference'])) {
                $measurePoints = array_merge($measurePoints, $seriesItem['points']);
            }
        }
        $timestamps = array_column($allPoints, 'timestamp');
        $values = array_column($allPoints, 'value');
        if ($ceilingValue !== null) {
            $values[] = $ceilingValue;
        }
        if ($minimumValue !== null) {
            $values[] = $minimumValue;
        }
        $minTimestamp = $displayTimeRange !== null ? $displayTimeRange['start'] : min($timestamps);
        $maxTimestamp = $displayTimeRange !== null ? $displayTimeRange['end'] : max($timestamps);
        if ($maxTimestamp <= $minTimestamp) {
            $minTimestamp -= 43200;
            $maxTimestamp += 43200;
        }
        $chartScale = omoStatsResolveChartScale(min($values), max($values));
        $minValue = $chartScale['min'];
        $maxValue = $chartScale['max'];
        $measureValueRange = omoStatsGetSeriesValueRange($measurePoints);
        $minimumLineValue = $minimumValue !== null
            && $measureValueRange !== null
            && $minimumValue >= $measureValueRange['min']
            && $minimumValue <= $measureValueRange['max']
            ? $minimumValue
            : null;

        $plotWidth = $width - $paddingLeft - $paddingRight;
        $plotHeight = $height - $paddingTop - $paddingBottom;
        $mapPoint = static function (array $point) use ($minTimestamp, $maxTimestamp, $minValue, $maxValue, $paddingLeft, $paddingTop, $plotWidth, $plotHeight) {
            return [
                round($paddingLeft + (($point['timestamp'] - $minTimestamp) / ($maxTimestamp - $minTimestamp)) * $plotWidth, 2),
                round($paddingTop + (1 - (($point['value'] - $minValue) / ($maxValue - $minValue))) * $plotHeight, 2),
            ];
        };
        $colors = ['#2563eb', '#db2777', '#059669', '#d97706', '#7c3aed', '#0891b2'];
        $svg = '<svg class="omo-stats-chart omo-stats-chart--' . omoApiEscape($variant) . ' omo-stats-chart--group' . ($overdueSeverity === 'error' ? ' omo-stats-chart--overdue' : ($overdueSeverity === 'warning' ? ' omo-stats-chart--warning' : '')) . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . omoApiEscape((string)$group->get('name')) . '">';
        if ($variant === 'large') {
            for ($gridIndex = 0; $gridIndex <= $chartScale['intervals']; $gridIndex++) {
                $ratio = $gridIndex / $chartScale['intervals'];
                $gridY = round($paddingTop + ($plotHeight * $ratio), 2);
                $gridValue = $maxValue - ($chartScale['step'] * $gridIndex);
                $svg .= '<line class="omo-stats-chart__grid" x1="' . $paddingLeft . '" y1="' . $gridY . '" x2="' . ($width - $paddingRight) . '" y2="' . $gridY . '"/>';
                $svg .= '<text class="omo-stats-chart__axis-label" x="' . ($paddingLeft - 10) . '" y="' . ($gridY + 4) . '" text-anchor="end">' . omoApiEscape(omoStatsFormatNumber($gridValue)) . '</text>';
            }
            $svg .= '<text class="omo-stats-chart__axis-label" x="' . $paddingLeft . '" y="' . ($height - 12) . '">' . omoApiEscape(date('d.m.Y', $minTimestamp)) . '</text>';
            $svg .= '<text class="omo-stats-chart__axis-label" x="' . ($width - $paddingRight) . '" y="' . ($height - 12) . '" text-anchor="end">' . omoApiEscape(date('d.m.Y', $maxTimestamp)) . '</text>';
        }
        foreach ($renderSeries as $seriesIndex => $seriesItem) {
            $coordinates = array_map($mapPoint, $seriesItem['points']);
            $coordinateString = implode(' ', array_map(static function (array $point) {
                return $point[0] . ',' . $point[1];
            }, $coordinates));
            $isBackground = !empty($seriesItem['is_background']);
            $isSum = !empty($seriesItem['is_sum']);
            $isReference = !empty($seriesItem['is_reference']);
            $sourceIndex = isset($seriesItem['source_index']) ? (int)$seriesItem['source_index'] : $seriesIndex;
            $color = $isReference ? '#7b9aa8' : ($isSum ? $colors[0] : $colors[$sourceIndex % count($colors)]);
            $lineClass = $isReference ? 'omo-stats-chart__reference' : 'omo-stats-chart__line'
                . ($isBackground ? ' omo-stats-chart__line--background' : '')
                . ($isSum ? ' omo-stats-chart__line--sum' : '');
            if ($isReference) {
                $lineClass = 'omo-stats-chart__reference';
            }
            if (count($coordinates) > 1) {
                $svg .= '<polyline class="' . $lineClass . '" style="stroke:' . $color . '" points="' . $coordinateString . '"/>';
            }
            if ($variant !== 'compact' && !$isBackground && !$isReference) {
                foreach ($coordinates as $pointIndex => $point) {
                    $pointTooltip = $withTooltips ? omoStatsFormatChartPointTooltip($seriesItem['points'][$pointIndex]) : '';
                    $pointTooltipAttributes = $withTooltips
                        ? ' data-omo-stats-chart-tooltip="' . omoApiEscape($pointTooltip) . '" tabindex="0" aria-label="' . omoApiEscape($pointTooltip) . '"'
                        : '';
                    $svg .= '<circle class="omo-stats-chart__point" style="stroke:' . $color . '" cx="' . $point[0] . '" cy="' . $point[1] . '" r="' . ($variant === 'large' ? 4 : 3) . '"' . $pointTooltipAttributes . '/>';
                }
            }
        }
        if ($ceilingValue !== null) {
            $ceilingY = $mapPoint(['timestamp' => $minTimestamp, 'value' => $ceilingValue])[1];
            $svg .= '<line class="omo-stats-chart__reference omo-stats-chart__reference--ceiling" x1="' . $paddingLeft . '" y1="' . $ceilingY . '" x2="' . ($width - $paddingRight) . '" y2="' . $ceilingY . '"/>';
        }
        if ($minimumLineValue !== null) {
            $minimumY = $mapPoint(['timestamp' => $minTimestamp, 'value' => $minimumLineValue])[1];
            $svg .= '<line class="omo-stats-chart__baseline" x1="' . $paddingLeft . '" y1="' . $minimumY . '" x2="' . ($width - $paddingRight) . '" y2="' . $minimumY . '"/>';
        }
        omoStatsAppendSimpleChartScale($svg, $variant, $width, $height, $paddingLeft, $paddingRight, $paddingTop, $paddingBottom, $minValue, $maxValue);
        return $svg . '</svg>';
    }
}

?>
