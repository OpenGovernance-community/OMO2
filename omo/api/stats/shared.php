<?php

use dbObject\Holon;
use dbObject\Organization;
use dbObject\StatIndicator;
use dbObject\StatIndicatorReferencePoint;
use dbObject\StatIndicatorValue;

if (!function_exists('omoStatsSourceLang')) {
    function omoStatsSourceLang()
    {
        return [
            'stats.title' => ['text' => 'Indicateurs', 'context' => 'Main title of the contextual steering indicators application.'],
            'stats.description' => ['text' => 'Suivez les données utiles au pilotage de ce contexte.', 'context' => 'Short description below the indicators application title.'],
            'stats.scope.contextual' => ['text' => 'Contextuel', 'context' => 'Scope label for indicators defined in the current holon.'],
            'stats.scope.descendants' => ['text' => 'Descendants', 'context' => 'Scope label for indicators in the current holon and descendants.'],
            'stats.scope.global' => ['text' => 'Global', 'context' => 'Scope label for all organization indicators.'],
            'stats.view.cards' => ['text' => 'Cartes', 'context' => 'Button switching the indicator list to cards.'],
            'stats.view.compact' => ['text' => 'Compact', 'context' => 'Button switching the indicator list to compact rows.'],
            'stats.action.new' => ['text' => 'Nouvel indicateur', 'context' => 'Primary button opening indicator creation.'],
            'stats.action.edit' => ['text' => 'Modifier', 'context' => 'Button opening indicator edition.'],
            'stats.action.close' => ['text' => 'Fermer', 'context' => 'Button closing the nested indicator drawer.'],
            'stats.action.cancel' => ['text' => 'Annuler', 'context' => 'Button cancelling indicator edition.'],
            'stats.action.save' => ['text' => 'Enregistrer', 'context' => 'Button saving indicator edition.'],
            'stats.action.delete' => ['text' => 'Supprimer', 'context' => 'Button deleting one dated indicator value.'],
            'stats.empty.contextual' => ['text' => 'Aucun indicateur n est encore défini dans ce contexte.', 'context' => 'Empty state for the contextual scope.'],
            'stats.empty.descendants' => ['text' => 'Aucun indicateur n est encore défini dans ce contexte ou ses descendants.', 'context' => 'Empty state for the descendants scope.'],
            'stats.empty.global' => ['text' => 'Aucun indicateur n est encore défini dans cette organisation.', 'context' => 'Empty state for the global scope.'],
            'stats.card.latest' => ['text' => 'Dernière valeur', 'context' => 'Label introducing the latest indicator value on a card.'],
            'stats.card.no_value' => ['text' => 'Aucune valeur', 'context' => 'Card fallback when an indicator has no dated values.'],
            'stats.card.value_count' => ['one' => '{count} valeur', 'other' => '{count} valeurs', 'context' => 'Count of dated values attached to an indicator.'],
            'stats.card.context' => ['text' => 'Contexte', 'context' => 'Label for the holon owning an indicator.'],
            'stats.card.open' => ['text' => 'Ouvrir l indicateur {name}', 'context' => 'Accessible label on an interactive indicator card or row.'],
            'stats.column.indicator' => ['text' => 'Indicateur', 'context' => 'Compact list column for the indicator identity.'],
            'stats.column.context' => ['text' => 'Contexte', 'context' => 'Compact list column for the owning context.'],
            'stats.column.latest' => ['text' => 'Dernière valeur', 'context' => 'Compact list column for the latest value.'],
            'stats.column.history' => ['text' => 'Historique', 'context' => 'Compact list column for the mini chart.'],
            'stats.drawer.title' => ['text' => 'Indicateur', 'context' => 'Nested drawer title.'],
            'stats.drawer.description' => ['text' => 'Graphique, valeurs et saisie manuelle.', 'context' => 'Nested drawer description.'],
            'stats.loading' => ['text' => 'Chargement de l indicateur...', 'context' => 'Loading message in the nested drawer.'],
            'stats.error.load' => ['text' => 'Impossible de charger cet indicateur.', 'context' => 'Generic nested drawer loading error.'],
            'stats.error.organization' => ['text' => 'Organisation invalide ou inaccessible.', 'context' => 'Error shown when the organization context is invalid.'],
            'stats.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Error shown when the holon context is invalid.'],
            'stats.error.not_found' => ['text' => 'Indicateur introuvable.', 'context' => 'Error shown when an indicator is unavailable.'],
            'stats.error.forbidden' => ['text' => 'Vous ne pouvez pas modifier cet indicateur.', 'context' => 'Error shown when indicator edition is forbidden.'],
            'stats.error.method' => ['text' => 'Cette action doit être envoyée en POST.', 'context' => 'Error returned for a mutation using the wrong HTTP method.'],
            'stats.error.action' => ['text' => 'Action inconnue.', 'context' => 'Error returned for an unsupported stats action.'],
            'stats.error.name' => ['text' => 'Le nom de l indicateur est obligatoire.', 'context' => 'Validation error for a missing indicator name.'],
            'stats.error.url' => ['text' => 'L URL doit commencer par http:// ou https://.', 'context' => 'Validation error for an unsafe source URL.'],
            'stats.error.value' => ['text' => 'La valeur saisie est invalide.', 'context' => 'Validation error for a non numeric indicator value.'],
            'stats.error.date' => ['text' => 'La date saisie est invalide.', 'context' => 'Validation error for an invalid measurement date.'],
            'stats.error.reference_points' => ['text' => 'La courbe de référence doit contenir des points uniques entre 0 et 100 %.', 'context' => 'Validation error for malformed reference positions.'],
            'stats.error.reference_endpoints' => ['text' => 'Les points à 0 % et 100 % sont obligatoires et doivent avoir une date.', 'context' => 'Validation error for missing dated reference endpoints.'],
            'stats.error.reference_dates' => ['text' => 'La date de fin de la référence doit être postérieure à sa date de début.', 'context' => 'Validation error for inverted endpoint dates.'],
            'stats.error.ceiling' => ['text' => 'Tous les points d un plafond doivent utiliser la même valeur.', 'context' => 'Validation error when a ceiling is not horizontal.'],
            'stats.error.save' => ['text' => 'Impossible d enregistrer l indicateur.', 'context' => 'Generic indicator persistence error.'],
            'stats.error.value_save' => ['text' => 'Impossible d enregistrer cette valeur.', 'context' => 'Generic indicator value persistence error.'],
            'stats.detail.tab.chart' => ['text' => 'Graphique', 'context' => 'Tab showing the large chart.'],
            'stats.detail.tab.values' => ['text' => 'Valeurs', 'context' => 'Tab showing dated values.'],
            'stats.detail.description_empty' => ['text' => 'Aucune description.', 'context' => 'Fallback for an indicator without description.'],
            'stats.detail.source' => ['text' => 'Consulter la source', 'context' => 'External link to the indicator source.'],
            'stats.detail.reference' => ['text' => 'Référence', 'context' => 'Label for the reference type.'],
            'stats.detail.reference_none' => ['text' => 'Sans courbe de référence', 'context' => 'Indicator reference type label for no reference.'],
            'stats.detail.reference_ceiling' => ['text' => 'Plafond horizontal', 'context' => 'Indicator reference type label for ceiling.'],
            'stats.detail.reference_objective' => ['text' => 'Objectif ou trajectoire', 'context' => 'Indicator reference type label for objective.'],
            'stats.detail.latest' => ['text' => 'Valeur actuelle', 'context' => 'Label for the latest value in indicator detail.'],
            'stats.detail.no_values' => ['text' => 'Aucune valeur n a encore été enregistrée.', 'context' => 'Empty state in the indicator value list.'],
            'stats.detail.value_date' => ['text' => 'Date', 'context' => 'Heading for the dated value date.'],
            'stats.detail.value' => ['text' => 'Valeur', 'context' => 'Heading for the dated value number.'],
            'stats.detail.add_title' => ['text' => 'Ajouter la valeur du moment', 'context' => 'Heading above the quick value form.'],
            'stats.detail.add_help' => ['text' => 'La date et l heure actuelles sont proposées automatiquement.', 'context' => 'Help below the quick value form heading.'],
            'stats.detail.add' => ['text' => 'Ajouter la valeur', 'context' => 'Submit button for a new dated value.'],
            'stats.detail.confirm_delete' => ['text' => 'Supprimer définitivement cette valeur ?', 'context' => 'Confirmation before deleting one value.'],
            'stats.form.create_title' => ['text' => 'Nouvel indicateur', 'context' => 'Heading of the create indicator form.'],
            'stats.form.edit_title' => ['text' => 'Modifier l indicateur', 'context' => 'Heading of the edit indicator form.'],
            'stats.form.intro' => ['text' => 'Définissez la série et, si nécessaire, sa courbe de référence.', 'context' => 'Introductory copy in the indicator form.'],
            'stats.form.reference_title' => ['text' => 'Courbe de référence', 'context' => 'Heading of the reference point editor.'],
            'stats.form.reference_help' => ['text' => 'Les extrémités datées utilisent 0 % et 100 %. Ajoutez des points intermédiaires pour infléchir la trajectoire.', 'context' => 'Help text for the reference point editor.'],
            'stats.form.reference_none' => ['text' => 'Aucune référence', 'context' => 'Reference type select option for none.'],
            'stats.form.reference_ceiling' => ['text' => 'Plafond horizontal', 'context' => 'Reference type select option for ceiling.'],
            'stats.form.reference_objective' => ['text' => 'Objectif ou trajectoire', 'context' => 'Reference type select option for objective.'],
            'stats.form.position' => ['text' => 'Position (%)', 'context' => 'Reference point editor position label.'],
            'stats.form.point_date' => ['text' => 'Date', 'context' => 'Reference point editor endpoint date label.'],
            'stats.form.point_value' => ['text' => 'Valeur', 'context' => 'Reference point editor value label.'],
            'stats.form.add_point' => ['text' => 'Ajouter un point', 'context' => 'Button adding an intermediate reference point.'],
            'stats.form.remove_point' => ['text' => 'Retirer', 'context' => 'Button removing an intermediate reference point.'],
            'stats.form.endpoint' => ['text' => 'Extrémité datée', 'context' => 'Badge shown on reference curve endpoint rows.'],
            'stats.form.intermediate' => ['text' => 'Point intermédiaire', 'context' => 'Badge shown on intermediate reference curve rows.'],
            'stats.chart.empty' => ['text' => 'Pas encore de données à représenter.', 'context' => 'Empty chart message.'],
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

if (!function_exists('omoStatsFormatDateTime')) {
    function omoStatsFormatDateTime($value, $withTime = true)
    {
        if (!($value instanceof DateTimeInterface)) {
            return '';
        }

        return $value->format($withTime ? 'd.m.Y H:i' : 'd.m.Y');
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

        usort($referencePoints, static function (StatIndicatorReferencePoint $left, StatIndicatorReferencePoint $right) {
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

if (!function_exists('omoStatsRenderChart')) {
    function omoStatsRenderChart(StatIndicator $indicator, array $values, array $referencePoints, $variant = 'card')
    {
        $variant = in_array($variant, ['compact', 'card', 'large'], true) ? $variant : 'card';
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
        $referenceSeries = StatIndicator::normalizeReferenceType($indicator->get('reference_type')) === StatIndicator::REFERENCE_NONE
            ? []
            : omoStatsResolveReferenceSeries($referencePoints, $values);

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
        $allSeries = array_merge($measureSeries, $referenceSeries);
        $timestamps = array_column($allSeries, 'timestamp');
        $numbers = array_column($allSeries, 'value');
        $minTimestamp = min($timestamps);
        $maxTimestamp = max($timestamps);
        if ($maxTimestamp <= $minTimestamp) {
            $minTimestamp -= 43200;
            $maxTimestamp += 43200;
        }

        $minValue = min($numbers);
        $maxValue = max($numbers);
        if (abs($maxValue - $minValue) < 0.000001) {
            $valuePadding = max(1.0, abs($maxValue) * 0.1);
            $minValue -= $valuePadding;
            $maxValue += $valuePadding;
        } else {
            $valuePadding = ($maxValue - $minValue) * 0.12;
            $minValue -= $valuePadding;
            $maxValue += $valuePadding;
        }

        $mapPoint = static function (array $point) use ($minTimestamp, $maxTimestamp, $minValue, $maxValue, $paddingLeft, $paddingTop, $plotWidth, $plotHeight) {
            $x = $paddingLeft + (($point['timestamp'] - $minTimestamp) / ($maxTimestamp - $minTimestamp)) * $plotWidth;
            $y = $paddingTop + (1 - (($point['value'] - $minValue) / ($maxValue - $minValue))) * $plotHeight;
            return [round($x, 2), round($y, 2)];
        };

        $measureCoordinates = array_map($mapPoint, $measureSeries);
        $referenceCoordinates = array_map($mapPoint, $referenceSeries);
        $coordinateString = static function (array $coordinates) {
            return implode(' ', array_map(static function (array $point) {
                return $point[0] . ',' . $point[1];
            }, $coordinates));
        };

        $chartId = 'omo-stats-chart-' . (int)$indicator->getId() . '-' . $variant . '-' . substr(md5((string)count($measureSeries) . ':' . (string)count($referenceSeries)), 0, 8);
        $svg = '<svg class="omo-stats-chart omo-stats-chart--' . omoApiEscape($variant) . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . omoApiEscape((string)$indicator->get('name')) . '">';
        $svg .= '<defs><linearGradient id="' . $chartId . '-area" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity="0.24"/><stop offset="1" stop-color="currentColor" stop-opacity="0.02"/></linearGradient></defs>';

        if ($variant === 'large') {
            for ($gridIndex = 0; $gridIndex <= 4; $gridIndex++) {
                $ratio = $gridIndex / 4;
                $gridY = round($paddingTop + ($plotHeight * $ratio), 2);
                $gridValue = $maxValue - (($maxValue - $minValue) * $ratio);
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
                foreach ($measureCoordinates as $point) {
                    $svg .= '<circle class="omo-stats-chart__point" cx="' . $point[0] . '" cy="' . $point[1] . '" r="' . ($variant === 'large' ? 4 : 3) . '"/>';
                }
            } else {
                $lastPoint = $measureCoordinates[count($measureCoordinates) - 1];
                $svg .= '<circle class="omo-stats-chart__point" cx="' . $lastPoint[0] . '" cy="' . $lastPoint[1] . '" r="2.5"/>';
            }
        }

        if (count($referenceCoordinates) > 1) {
            $svg .= '<polyline class="omo-stats-chart__reference" points="' . $coordinateString($referenceCoordinates) . '"/>';
        }

        $svg .= '</svg>';
        return $svg;
    }
}

?>
