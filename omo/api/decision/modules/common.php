<?php

use dbObject\DecisionInvitation;
use dbObject\DecisionProcess;
use dbObject\Holon;

if (!function_exists('omoDecisionModuleJsonResponse')) {
    function omoDecisionModuleJsonResponse($statusCode, array $payload)
    {
        http_response_code((int)$statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('omoDecisionModuleDecodeParameters')) {
    function omoDecisionModuleDecodeParameters($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('omoDecisionModuleGetMethodParameters')) {
    function omoDecisionModuleGetMethodParameters($value, $methodKey)
    {
        $parameters = omoDecisionModuleDecodeParameters($value);
        $methodKey = trim((string)$methodKey);
        if ($methodKey === '') {
            return [];
        }

        $methodParameters = $parameters[$methodKey] ?? [];
        return is_array($methodParameters) ? $methodParameters : [];
    }
}

if (!function_exists('omoDecisionModuleParseUniqueTextItems')) {
    function omoDecisionModuleParseUniqueTextItems($rawValue)
    {
        $items = is_array($rawValue) ? $rawValue : preg_split('/\r\n|\r|\n/', (string)$rawValue);
        $items = is_array($items) ? $items : [];

        $cleaned = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item === '') {
                continue;
            }

            if (!in_array($item, $cleaned, true)) {
                $cleaned[] = $item;
            }
        }

        return $cleaned;
    }
}

if (!function_exists('omoDecisionModuleRenderReadonlyMeta')) {
    function omoDecisionModuleRenderReadonlyMeta($label, $value, $escape, $extraClass = '')
    {
        if (trim((string)$value) === '') {
            return '';
        }

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        return '<div class="generic-soft-panel generic-soft-panel--stack' . $extraClass . '">'
            . '<span class="generic-card-title generic-card-title--small">' . $escape($label) . '</span>'
            . '<strong>' . $escape($value) . '</strong>'
            . '</div>';
    }
}

if (!function_exists('omoDecisionModuleEncodeJsonPayload')) {
    function omoDecisionModuleEncodeJsonPayload(array $payload, $fallback = '{}')
    {
        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return is_string($encoded) ? $encoded : (string)$fallback;
    }
}

if (!function_exists('omoDecisionInvitationGetSourceLang')) {
    function omoDecisionInvitationGetSourceLang()
    {
        return [
            'decisions.invitations.title' => [
                'text' => 'Participants invites',
                'context' => 'Shared section title for explicit decision invitations.',
            ],
            'decisions.invitations.configure' => [
                'text' => 'Inviter',
                'context' => 'Button opening the invitation popup.',
            ],
            'decisions.invitations.popup_title' => [
                'text' => 'Inviter des participants',
                'context' => 'Topbar modal title used by the invitation popup.',
            ],
            'decisions.invitations.unsaved' => [
                'text' => 'Enregistrez d abord ce scrutin pour inviter d autres personnes ou structures.',
                'context' => 'Hint shown before a decision exists and invitations cannot be configured yet.',
            ],
            'decisions.invitations.default_scope' => [
                'text' => 'Par defaut, seuls les membres du contexte courant participent.',
                'context' => 'Summary shown when no explicit invitations exist.',
            ],
            'decisions.invitations.current_scope_included' => [
                'text' => 'Contexte courant inclus',
                'context' => 'Summary fragment when the current holon is explicitly invited.',
            ],
            'decisions.invitations.current_scope_excluded' => [
                'text' => 'Contexte courant non inclus',
                'context' => 'Summary fragment when the current holon is not explicitly invited.',
            ],
            'decisions.invitations.additional_people' => [
                'one' => '+1 personne supplementaire',
                'other' => '+{count} personnes supplementaires',
                'context' => 'Summary fragment for additional invited users and emails.',
            ],
        ];
    }
}

if (!function_exists('omoDecisionBuildInvitationPopupUrl')) {
    function omoDecisionBuildInvitationPopupUrl($organizationId, $holonId = 0, $decisionId = 0, $method = '')
    {
        $query = [
            'oid' => (int)$organizationId,
            'id' => (int)$decisionId,
        ];

        if ((int)$holonId > 0) {
            $query['cid'] = (int)$holonId;
        }

        $method = trim((string)$method);
        if ($method !== '') {
            $query['method'] = $method;
        }

        return '/omo/api/decision/invitations_popup.php?' . http_build_query($query);
    }
}

if (!function_exists('omoDecisionBuildInvitationSummaryData')) {
    function omoDecisionBuildInvitationSummaryData($decision, array $context, $lang = null, array $sourceLang = [])
    {
        $currentHolon = $context['effectiveHolon'] ?? null;
        $currentHolonId = $currentHolon instanceof Holon ? (int)$currentHolon->getId() : 0;
        $method = $decision instanceof DecisionProcess
            ? DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'))
            : trim((string)($context['method'] ?? ''));

        $data = [
            'isPersisted' => $decision instanceof DecisionProcess && (int)$decision->getId() > 0,
            'popupUrl' => '',
            'summary' => '',
        ];

        if (!$data['isPersisted']) {
            $data['summary'] = t('decisions.invitations.unsaved', [], $lang, $sourceLang);
            return $data;
        }

        $data['popupUrl'] = omoDecisionBuildInvitationPopupUrl(
            (int)($context['organizationId'] ?? 0),
            (int)($context['targetHolonId'] ?? 0),
            (int)$decision->getId(),
            $method
        );

        $invitations = [];
        foreach ($decision->getInvitations(true) as $invitation) {
            if ($invitation instanceof DecisionInvitation && DecisionInvitation::normalizeStatus($invitation->get('status')) !== DecisionInvitation::STATUS_REVOKED) {
                $invitations[] = $invitation;
            }
        }

        if (count($invitations) === 0) {
            $defaultSummary = t('decisions.invitations.default_scope', [], $lang, $sourceLang);
            if ($currentHolon instanceof Holon) {
                $defaultSummary = rtrim($defaultSummary, '.');
                $defaultSummary .= ' ' . $currentHolon->getTemplateLabel(true) . ' ' . trim((string)$currentHolon->getDisplayName()) . '.';
            }

            $data['summary'] = $defaultSummary;
            return $data;
        }

        $holonLabels = [];
        $additionalPeopleCount = 0;
        $includesCurrentHolon = false;

        foreach ($invitations as $invitation) {
            $type = DecisionInvitation::normalizeType($invitation->get('invitation_type'));
            if ($type === DecisionInvitation::TYPE_HOLON) {
                $holonId = (int)$invitation->get('IDholon');
                if ($holonId === $currentHolonId && $currentHolonId > 0) {
                    $includesCurrentHolon = true;
                }

                $holonLabel = trim((string)$invitation->get('display_name'));
                if ($holonLabel === '' && $holonId > 0) {
                    $holon = new Holon();
                    if ($holon->load($holonId)) {
                        $holonLabel = trim((string)$holon->getDisplayName());
                    }
                }
                if ($holonLabel !== '') {
                    $holonLabels[] = $holonLabel;
                }
                continue;
            }

            $additionalPeopleCount++;
        }

        $summaryParts = [];
        if (count($holonLabels) > 0) {
            $summaryParts[] = implode(', ', array_slice(array_values(array_unique($holonLabels)), 0, 3));
        }
        if ($additionalPeopleCount > 0) {
            $summaryParts[] = t('decisions.invitations.additional_people', ['count' => (string)$additionalPeopleCount], $lang, $sourceLang);
        }

        $summaryParts[] = $includesCurrentHolon
            ? t('decisions.invitations.current_scope_included', [], $lang, $sourceLang)
            : t('decisions.invitations.current_scope_excluded', [], $lang, $sourceLang);

        $data['summary'] = implode(' - ', array_filter($summaryParts, static function ($value) {
            return trim((string)$value) !== '';
        }));

        return $data;
    }
}

if (!function_exists('omoDecisionRenderInvitationSection')) {
    function omoDecisionRenderInvitationSection($decision, array $context, $lang, array $sourceLang, $escape, $extraClass = '')
    {
        $summaryData = omoDecisionBuildInvitationSummaryData($decision, $context, $lang, $sourceLang);
        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        $buttonDisabled = empty($summaryData['isPersisted']) || trim((string)$summaryData['popupUrl']) === '';

        return '<div class="generic-soft-panel generic-soft-panel--stack' . $extraClass . '">'
            . '<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">'
                . '<span class="generic-card-title">' . $escape(t('decisions.invitations.title', [], $lang, $sourceLang)) . '</span>'
                . '<button'
                    . ' type="button"'
                    . ' class="generic-action-button generic-action-button--secondary"'
                    . ' data-omo-decision-invitations-open'
                    . ' data-omo-decision-invitations-url="' . $escape((string)$summaryData['popupUrl']) . '"'
                    . ' data-omo-decision-invitations-title="' . $escape(t('decisions.invitations.popup_title', [], $lang, $sourceLang)) . '"'
                    . ($buttonDisabled ? ' disabled' : '')
                . '>'
                    . $escape(t('decisions.invitations.configure', [], $lang, $sourceLang))
                . '</button>'
            . '</div>'
            . '<p style="margin:0;color:var(--color-text-light,#475569);line-height:1.6;" data-omo-decision-invitations-summary>'
                . $escape((string)$summaryData['summary'])
            . '</p>'
        . '</div>';
    }
}
