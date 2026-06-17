<?php

use dbObject\DecisionInvitation;
use dbObject\DecisionGroup;
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;
use dbObject\Holon;
use dbObject\User;

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

if (!function_exists('omoDecisionNormalizeProposalInfoUrl')) {
    function omoDecisionNormalizeProposalInfoUrl($value)
    {
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }
}

if (!function_exists('omoDecisionBuildProposalItemsFromInput')) {
    function omoDecisionBuildProposalItemsFromInput($titles, $descriptions = [], $infoUrls = [])
    {
        $titles = is_array($titles) ? array_values($titles) : [];
        $descriptions = is_array($descriptions) ? array_values($descriptions) : [];
        $infoUrls = is_array($infoUrls) ? array_values($infoUrls) : [];

        $rowCount = max(count($titles), count($descriptions), count($infoUrls));
        $items = [];

        for ($index = 0; $index < $rowCount; $index++) {
            $title = trim((string)($titles[$index] ?? ''));
            $description = trim((string)($descriptions[$index] ?? ''));
            $infoUrl = omoDecisionNormalizeProposalInfoUrl($infoUrls[$index] ?? '');

            if ($title === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'info_url' => $infoUrl,
            ];
        }

        return $items;
    }
}

if (!function_exists('omoDecisionBuildProposalItemsFromDecision')) {
    function omoDecisionBuildProposalItemsFromDecision($decision, $minimumCount = 0)
    {
        $items = [];
        if ((is_object($decision) && method_exists($decision, 'getProposals'))) {
            foreach ($decision->getProposals(true) as $proposal) {
                if (!$proposal instanceof DecisionProposal) {
                    continue;
                }

                $items[] = [
                    'title' => trim((string)$proposal->get('title')),
                    'description' => trim((string)$proposal->get('description')) ?: null,
                    'info_url' => omoDecisionNormalizeProposalInfoUrl($proposal->get('info_url')),
                ];
            }
        }

        while (count($items) < max(0, (int)$minimumCount)) {
            $items[] = [
                'title' => '',
                'description' => null,
                'info_url' => null,
            ];
        }

        return $items;
    }
}

if (!function_exists('omoDecisionRenderProposalSupplementHtml')) {
    function omoDecisionRenderProposalSupplementHtml($description, $infoUrl, $escape, $descriptionClass = '', $linkClass = '')
    {
        if (!is_callable($escape)) {
            $escape = 'omoApiEscape';
        }

        $description = trim((string)$description);
        $infoUrl = omoDecisionNormalizeProposalInfoUrl($infoUrl);
        if ($description === '' && $infoUrl === null) {
            return '';
        }

        $html = '';
        if ($description !== '') {
            $classAttribute = trim((string)$descriptionClass) !== '' ? ' class="' . $escape(trim((string)$descriptionClass)) . '"' : '';
            $html .= '<p' . $classAttribute . '>' . nl2br($escape($description)) . '</p>';
        }

        if ($infoUrl !== null) {
            $classAttribute = trim((string)$linkClass) !== '' ? ' class="' . $escape(trim((string)$linkClass)) . '"' : '';
            $html .= '<a' . $classAttribute . ' href="' . $escape($infoUrl) . '" target="_blank" rel="noopener noreferrer">Plus d infos</a>';
        }

        return $html;
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

if (!function_exists('omoDecisionEnsureMethodSharedLoaded')) {
    function omoDecisionEnsureMethodSharedLoaded($method)
    {
        if (!function_exists('omoDecisionGetModuleDefinition')) {
            $registryFile = __DIR__ . '/registry.php';
            if (is_file($registryFile)) {
                require_once $registryFile;
            }
        }

        $definition = function_exists('omoDecisionGetModuleDefinition')
            ? omoDecisionGetModuleDefinition($method)
            : null;

        if ($definition && !empty($definition['shared_file']) && is_file((string)$definition['shared_file'])) {
            require_once (string)$definition['shared_file'];
        }

        return $definition;
    }
}

if (!function_exists('omoDecisionBuildMethodConfig')) {
    function omoDecisionBuildMethodConfig($decision)
    {
        if (
            !($decision instanceof DecisionProcess)
            && !($decision instanceof DecisionGroup)
        ) {
            return [];
        }

        $method = DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
        omoDecisionEnsureMethodSharedLoaded($method);

        switch ($method) {
            case DecisionProcess::METHOD_SIMPLE_VOTE:
                return function_exists('omoDecisionVoteBuildConfig')
                    ? omoDecisionVoteBuildConfig($decision)
                    : [];

            case DecisionProcess::METHOD_MAJORITY_JUDGMENT:
                return function_exists('omoDecisionMajorityJudgmentBuildConfig')
                    ? omoDecisionMajorityJudgmentBuildConfig($decision)
                    : [];

            case DecisionProcess::METHOD_CONSENT:
                return function_exists('omoDecisionConsentBuildConfig')
                    ? omoDecisionConsentBuildConfig($decision)
                    : [];
        }

        return [];
    }
}

if (!function_exists('omoDecisionCanSubmitConsultationProposal')) {
    function omoDecisionGetConsultationProposalAvailability($decision, array $context)
    {
        if (!$decision instanceof DecisionProcess || (int)$decision->getId() <= 0) {
            return [
                'allowed' => false,
                'reason' => 'invalid_decision',
            ];
        }

        $decisionGroup = ($context['decisionGroup'] ?? null) instanceof DecisionGroup
            ? $context['decisionGroup']
            : $decision->getPrimaryGroup(false);
        $config = omoDecisionBuildMethodConfig($decisionGroup instanceof DecisionGroup ? $decisionGroup : $decision);
        if (empty($config['allow_consultation_proposals'])) {
            return [
                'allowed' => false,
                'reason' => 'option_disabled',
            ];
        }

        if (!$decision->hasConsultationStarted()) {
            return [
                'allowed' => false,
                'reason' => 'consultation_not_started',
            ];
        }

        if ($decision->hasEvaluationStarted()) {
            return [
                'allowed' => false,
                'reason' => 'evaluation_started',
            ];
        }

        $currentUserId = function_exists('commonGetCurrentUserId')
            ? (int)commonGetCurrentUserId()
            : (int)($_SESSION['currentUser'] ?? 0);
        if ($currentUserId > 0 && (int)$decision->get('IDuser') === $currentUserId) {
            return [
                'allowed' => true,
                'reason' => 'owner',
            ];
        }

        $participant = null;
        if ($currentUserId > 0) {
            $participant = DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $currentUserId);
            if ($participant instanceof DecisionParticipant) {
                $context['participant_lookup'] = 'user';
            }
        }

        if (!($participant instanceof DecisionParticipant)) {
            $currentUserEmail = trim((string)($context['currentUserEmail'] ?? ''));
            if ($currentUserEmail === '' && $currentUserId > 0) {
                $currentUser = new User();
                if ($currentUser->load($currentUserId)) {
                    $currentUserEmail = trim(mb_strtolower((string)$currentUser->getScopedEmail((int)$decision->get('IDorganization')), 'UTF-8'));
                }
            }

            if ($currentUserEmail !== '') {
                $participant = DecisionParticipant::findByDecisionAndEmail((int)$decision->getId(), $currentUserEmail);
                if ($participant instanceof DecisionParticipant) {
                    $context['participant_lookup'] = 'email';
                }
            }
        }

        if (
            !($participant instanceof DecisionParticipant)
            && (string)($context['accessMode'] ?? '') === 'public'
        ) {
            $participant = $context['participant'] ?? null;
            if ($participant instanceof DecisionParticipant) {
                $context['participant_lookup'] = 'public_token';
            }
        }

        if (!($participant instanceof DecisionParticipant)) {
            return [
                'allowed' => false,
                'reason' => 'participant_not_found',
            ];
        }

        if ((int)$participant->get('active') !== 1) {
            return [
                'allowed' => false,
                'reason' => 'participant_inactive',
            ];
        }

        $participantStatus = DecisionParticipant::normalizeStatus($participant->get('status'));
        if (in_array($participantStatus, [
            DecisionParticipant::STATUS_DECLINED,
            DecisionParticipant::STATUS_REVOKED,
        ], true)) {
            return [
                'allowed' => false,
                'reason' => 'participant_status_' . $participantStatus,
            ];
        }

        return [
            'allowed' => true,
            'reason' => (string)($context['participant_lookup'] ?? 'participant'),
        ];
    }
}

if (!function_exists('omoDecisionCanSubmitConsultationProposal')) {
    function omoDecisionCanSubmitConsultationProposal($decision, array $context)
    {
        $availability = omoDecisionGetConsultationProposalAvailability($decision, $context);
        return !empty($availability['allowed']);
    }
}

if (!function_exists('omoDecisionGetConsultationProposalDeniedMessage')) {
    function omoDecisionGetConsultationProposalDeniedMessage($reason)
    {
        switch (trim((string)$reason)) {
            case 'option_disabled':
                return 'L ajout de propositions n est pas active pour ce scrutin.';
            case 'consultation_not_started':
                return 'La consultation n a pas encore commence.';
            case 'evaluation_started':
                return 'La phase de vote a deja commence.';
            case 'participant_not_found':
                return 'Aucun participant autorise n a ete retrouve pour ce lien ou ce compte.';
            case 'participant_inactive':
                return 'Ce participant n est plus actif pour ce scrutin.';
            case 'participant_status_declined':
                return 'Votre participation a ete refusee pour ce scrutin.';
            case 'participant_status_revoked':
                return 'Votre acces a ce scrutin a ete revoque.';
            case 'invalid_decision':
                return 'Le scrutin n a pas pu etre charge.';
            default:
                return 'Ce lien ne permet pas d ajouter des propositions pour le moment.';
        }
    }
}

if (!function_exists('omoDecisionBuildConsultationProposalSubmitUrl')) {
    function omoDecisionBuildConsultationProposalSubmitUrl()
    {
        return '/omo/api/decision/modules/proposals/consultation_add.php';
    }
}

if (!function_exists('omoDecisionBuildConsultationProposalReturnUrl')) {
    function omoDecisionBuildConsultationProposalReturnUrl(array $context)
    {
        $requestUri = trim((string)($_SERVER['REQUEST_URI'] ?? ''));
        if ($requestUri !== '' && strpos($requestUri, '/') === 0) {
            return $requestUri;
        }

        return omoDecisionBuildContextualEditorUrl($context, 'view');
    }
}

if (!function_exists('omoDecisionRenderConsultationProposalPublicPanel')) {
    function omoDecisionRenderConsultationProposalPublicPanel($decision, array $context, $escape, $extraClass = '')
    {
        if (!is_callable($escape)) {
            $escape = 'omoApiEscape';
        }

        if (!omoDecisionCanSubmitConsultationProposal($decision, $context)) {
            return '';
        }

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        $feedbackStatus = trim((string)($_GET['consultation_proposal_status'] ?? ''));
        $feedbackCount = max(0, (int)($_GET['consultation_proposal_count'] ?? 0));
        $feedbackMessage = '';
        $feedbackClass = '';

        if ($feedbackStatus === 'success') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-success, #16a34a) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-success, #16a34a) 28%, var(--color-surface, #ffffff));"';
            $feedbackMessage = $feedbackCount > 1
                ? $feedbackCount . ' propositions ajoutees a la consultation.'
                : 'Proposition ajoutee a la consultation.';
        } elseif ($feedbackStatus === 'duplicate') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-warning, #f59e0b) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-warning, #f59e0b) 28%, var(--color-surface, #ffffff));"';
            $feedbackMessage = 'Toutes les propositions soumises existent deja.';
        } elseif ($feedbackStatus === 'empty') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-warning, #f59e0b) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-warning, #f59e0b) 28%, var(--color-surface, #ffffff));"';
            $feedbackMessage = 'Ajoutez au moins une proposition.';
        } elseif ($feedbackStatus === 'denied') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-warning, #f59e0b) 10%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-warning, #f59e0b) 28%, var(--color-surface, #ffffff));"';
            $feedbackMessage = 'Ce lien ne permet pas d ajouter des propositions pour le moment.';
        } elseif ($feedbackStatus === 'error') {
            $feedbackClass = ' style="background:color-mix(in srgb, var(--color-danger, #dc2626) 8%, var(--color-surface, #ffffff));border-color:color-mix(in srgb, var(--color-danger, #dc2626) 24%, var(--color-surface, #ffffff));"';
            $feedbackMessage = 'Impossible d ajouter la proposition pour le moment.';
        }

        $returnUrl = omoDecisionBuildConsultationProposalReturnUrl($context);
        $html = '<div class="generic-soft-panel generic-soft-panel--stack' . $extraClass . '">'
            . '<div style="display:grid;gap:6px;">'
                . '<h2 class="generic-card-title generic-card-title--section" style="margin:0;">Ajouter une proposition</h2>'
                . '<p style="margin:0;color:var(--color-text-light,#475569);line-height:1.6;">La consultation est ouverte. Vous pouvez proposer une nouvelle option avec son contexte et un lien d information.</p>'
                . '<p style="margin:0;color:var(--color-text-light,#64748b);font-size:13px;line-height:1.5;">La proposition sera ajoutee a la fin de la liste. Son ordre detaille reste gerable ensuite dans l interface principale.</p>'
            . '</div>';

        if ($feedbackMessage !== '') {
            $html .= '<div class="generic-soft-panel generic-soft-panel--stack"' . $feedbackClass . '>'
                . '<p style="margin:0;line-height:1.5;">' . $escape($feedbackMessage) . '</p>'
            . '</div>';
        }

        $html .= '<form method="post" action="' . $escape(omoDecisionBuildConsultationProposalSubmitUrl()) . '" style="display:grid;gap:12px;" data-omo-decision-consultation-proposal-form data-omo-decision-return-url="' . $escape($returnUrl) . '">'
                . '<input type="hidden" name="oid" value="' . $escape((int)($context['organizationId'] ?? 0)) . '">'
                . '<input type="hidden" name="cid" value="' . $escape((int)($context['targetHolonId'] ?? 0)) . '">'
                . '<input type="hidden" name="id" value="' . $escape((int)$decision->getId()) . '">'
                . '<input type="hidden" name="gid" value="' . $escape((int)((($context['decisionGroup'] ?? null) instanceof DecisionGroup) ? $context['decisionGroup']->getId() : 0)) . '">'
                . '<input type="hidden" name="method" value="' . $escape((string)(((($context['decisionGroup'] ?? null) instanceof DecisionGroup) ? $context['decisionGroup']->get('evaluation_method') : $decision->get('evaluation_method')))) . '">'
                . '<input type="hidden" name="intent" value="view">'
                . '<input type="hidden" name="ajax" value="1">'
                . '<input type="hidden" name="return_url" value="' . $escape($returnUrl) . '">'
                . omoDecisionRenderPublicTokenInput($context, $escape)
                . '<div style="display:grid;gap:10px;">'
                    . '<label style="display:grid;gap:6px;">'
                        . '<span class="generic-card-title generic-card-title--small">Titre</span>'
                        . '<input type="text" class="generic-form-control" name="consultation_proposal_title" value="" placeholder="Nom de la proposition" required>'
                    . '</label>'
                    . '<label style="display:grid;gap:6px;">'
                        . '<span class="generic-card-title generic-card-title--small">Description</span>'
                        . '<textarea class="generic-form-control" name="consultation_proposal_description" rows="4" placeholder="Contexte, details, arguments utiles..."></textarea>'
                    . '</label>'
                    . '<label style="display:grid;gap:6px;">'
                        . '<span class="generic-card-title generic-card-title--small">URL d information</span>'
                        . '<input type="url" class="generic-form-control" name="consultation_proposal_info_url" value="" placeholder="https://...">'
                    . '</label>'
                . '</div>'
                . '<div data-omo-decision-consultation-proposal-feedback hidden></div>'
                . '<div style="display:flex;justify-content:flex-end;">'
                    . '<button type="submit" class="generic-action-button generic-action-button--main">Ajouter la proposition</button>'
                . '</div>'
            . '</form>'
        . '</div>';

        return $html;
    }
}

if (!function_exists('omoDecisionRenderConsultationProposalPublicSection')) {
    function omoDecisionRenderConsultationProposalPublicSection($decision, array $context, $escape, $extraClass = '')
    {
        $panel = omoDecisionRenderConsultationProposalPublicPanel($decision, $context, $escape);
        if ($panel === '') {
            return '';
        }

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        return '<section class="generic-section generic-section--stack' . $extraClass . '">' . $panel . '</section>';
    }
}

if (!function_exists('omoDecisionRenderPublicTokenInput')) {
    function omoDecisionRenderPublicTokenInput(array $context, $escape)
    {
        if (
            (($context['accessMode'] ?? '') !== 'public')
            || trim((string)($context['publicToken'] ?? '')) === ''
        ) {
            return '';
        }

        return '<input type="hidden" name="token" value="' . $escape((string)$context['publicToken']) . '">';
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
            'decisions.invitations.send' => [
                'text' => 'Envoyer',
                'context' => 'Button opening the send invitations popup.',
            ],
            'decisions.invitations.send_popup_title' => [
                'text' => 'Envoyer les invitations',
                'context' => 'Topbar modal title used by the send invitations popup.',
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
            'decisions.invitations.inline_intro' => [
                'text' => 'Definissez ici les participants explicites du scrutin. Sans invitation explicite, seuls les membres du contexte courant restent autorises.',
                'context' => 'Intro text shown in the inline invitation editor inside the main decision form.',
            ],
            'decisions.invitations.inline_no_structure' => [
                'text' => 'Cette organisation n a pas encore de structure. Vous pouvez inviter directement des membres de l organisation ou des adresses e-mail externes.',
                'context' => 'Hint shown in the inline invitation editor when the organization has no holon structure.',
            ],
            'decisions.invitations.inline_save_hint' => [
                'text' => 'Ces invitations seront enregistrees avec le scrutin.',
                'context' => 'Helper text shown below the inline invitation editor before the main decision form is saved.',
            ],
            'decisions.invitations.tab.holons' => [
                'text' => 'Holons',
                'context' => 'Tab label for invited holons in the inline invitation editor.',
            ],
            'decisions.invitations.tab.members' => [
                'text' => 'Membres',
                'context' => 'Tab label for invited members in the inline invitation editor.',
            ],
            'decisions.invitations.tab.guests' => [
                'text' => 'Invites',
                'context' => 'Tab label for invited guest emails in the inline invitation editor.',
            ],
            'decisions.invitations.inline_holons_title' => [
                'text' => 'Holons invites',
                'context' => 'Section title for invited holons in the inline invitation editor.',
            ],
            'decisions.invitations.inline_holons_hint' => [
                'text' => 'Le holon courant apparait ici comme n importe quel autre. S il n est pas coche, ses membres ne seront pas inclus des qu une invitation explicite existe.',
                'context' => 'Hint for the invited holons tab in the inline invitation editor.',
            ],
            'decisions.invitations.inline_members_title' => [
                'text' => 'Membres supplementaires de l organisation',
                'context' => 'Section title for invited members in the inline invitation editor.',
            ],
            'decisions.invitations.inline_members_hint_structure' => [
                'text' => 'Cochez les membres a inviter individuellement, en plus des holons selectionnes.',
                'context' => 'Hint for invited members when a holon structure exists in the inline invitation editor.',
            ],
            'decisions.invitations.inline_members_hint_flat' => [
                'text' => 'Cochez les membres a inviter individuellement. Sans structure, ils representent le contexte organisationnel.',
                'context' => 'Hint for invited members when no holon structure exists in the inline invitation editor.',
            ],
            'decisions.invitations.inline_guests_title' => [
                'text' => 'Adresses e-mail externes',
                'context' => 'Section title for guest email invitations in the inline invitation editor.',
            ],
            'decisions.invitations.inline_guests_placeholder' => [
                'text' => 'prenom.nom@exemple.ch',
                'context' => 'Textarea placeholder for guest email invitations in the inline invitation editor.',
            ],
            'decisions.invitations.inline_guests_hint' => [
                'text' => 'Une adresse par ligne. Les invitations seront envoyees plus tard.',
                'context' => 'Hint below the guest email textarea in the inline invitation editor.',
            ],
            'decisions.invitations.inline_public_open_title' => [
                'text' => 'Participation sans invitation',
                'context' => 'Title of the public self-registration checkbox in the inline invitation editor.',
            ],
            'decisions.invitations.inline_public_open_hint' => [
                'text' => 'Toute personne disposant du lien public peut demander un code par e-mail. Si son adresse n est pas encore associee a ce scrutin, un participant est cree automatiquement.',
                'context' => 'Hint for the public self-registration checkbox in the inline invitation editor.',
            ],
            'decisions.invitations.inline_current_holon' => [
                'text' => '(courant)',
                'context' => 'Suffix shown next to the current holon in the inline invitation editor tree.',
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

if (!function_exists('omoDecisionBuildInvitationSendPopupUrl')) {
    function omoDecisionBuildInvitationSendPopupUrl($organizationId, $holonId = 0, $decisionId = 0, $method = '')
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

        return '/omo/api/decision/send_invitations_popup.php?' . http_build_query($query);
    }
}

if (!function_exists('omoDecisionParseInvitationEmails')) {
    function omoDecisionParseInvitationEmails($value)
    {
        $rawItems = is_array($value)
            ? $value
            : preg_split('/[\r\n,;]+/', (string)$value);
        $rawItems = is_array($rawItems) ? $rawItems : [];

        $emails = [];
        foreach ($rawItems as $item) {
            $email = trim(mb_strtolower((string)$item, 'UTF-8'));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (!in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }
}

if (!function_exists('omoDecisionExtractInvitationSelections')) {
    function omoDecisionExtractInvitationSelections($decision)
    {
        $selectedHolonIds = [];
        $selectedUserIds = [];
        $selectedEmails = [];

        if ($decision instanceof DecisionProcess) {
            foreach ($decision->getInvitations(true) as $invitation) {
                if (
                    !($invitation instanceof DecisionInvitation)
                    || DecisionInvitation::normalizeStatus($invitation->get('status')) === DecisionInvitation::STATUS_REVOKED
                ) {
                    continue;
                }

                $type = DecisionInvitation::normalizeType($invitation->get('invitation_type'));
                if ($type === DecisionInvitation::TYPE_HOLON) {
                    $selectedHolonIds[] = (int)$invitation->get('IDholon');
                    continue;
                }

                if ($type === DecisionInvitation::TYPE_USER) {
                    $selectedUserIds[] = (int)$invitation->get('IDuser');
                    continue;
                }

                $email = trim((string)$invitation->get('email'));
                if ($email !== '') {
                    $selectedEmails[] = $email;
                }
            }
        }

        $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $selectedHolonIds), static function ($holonId) {
            return $holonId > 0;
        })));
        $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $selectedUserIds), static function ($userId) {
            return $userId > 0;
        })));
        $selectedEmails = array_values(array_unique(array_filter($selectedEmails, static function ($email) {
            return trim((string)$email) !== '';
        })));

        return [
            'holon_ids' => $selectedHolonIds,
            'user_ids' => $selectedUserIds,
            'emails' => $selectedEmails,
            'count' => count($selectedHolonIds) + count($selectedUserIds) + count($selectedEmails),
        ];
    }
}

if (!function_exists('omoDecisionBuildInvitationEditorHolonTreeData')) {
    function omoDecisionBuildInvitationEditorHolonTreeData(Holon $holon, \dbObject\Organization $organization, array $selectedHolonIds, $currentHolonId)
    {
        if (!$organization->containsHolon($holon) || !$holon->canViewDetail()) {
            return null;
        }

        $holonId = (int)$holon->getId();
        $children = [];
        $hasSelectedDescendant = in_array($holonId, $selectedHolonIds, true);
        $hasCurrentDescendant = $holonId === (int)$currentHolonId;

        foreach ($holon->getChildren() as $child) {
            if (!$child instanceof Holon) {
                continue;
            }

            $childNode = omoDecisionBuildInvitationEditorHolonTreeData($child, $organization, $selectedHolonIds, $currentHolonId);
            if (!is_array($childNode)) {
                continue;
            }

            $children[] = $childNode;
            if (!empty($childNode['hasSelectedDescendant'])) {
                $hasSelectedDescendant = true;
            }
            if (!empty($childNode['hasCurrentDescendant'])) {
                $hasCurrentDescendant = true;
            }
        }

        return [
            'id' => $holonId,
            'label' => trim((string)$holon->getDisplayName()),
            'typeLabel' => trim((string)$holon->getTemplateLabel(true)),
            'isCurrent' => $holonId === (int)$currentHolonId,
            'isSelected' => in_array($holonId, $selectedHolonIds, true),
            'children' => $children,
            'hasChildren' => count($children) > 0,
            'hasSelectedDescendant' => $hasSelectedDescendant,
            'hasCurrentDescendant' => $hasCurrentDescendant,
            'isExpanded' => $holonId === (int)$currentHolonId || $hasSelectedDescendant || $hasCurrentDescendant,
        ];
    }
}

if (!function_exists('omoDecisionRenderInvitationEditorHolonTreeNode')) {
    function omoDecisionRenderInvitationEditorHolonTreeNode(array $node, $escape, $currentLabel, $fieldName = 'invitation_holon_ids[]')
    {
        $hasChildren = !empty($node['hasChildren']);
        $isExpanded = !empty($node['isExpanded']);
        ?>
        <div class="omo-decision-invitations-editor__tree-node<?= $hasChildren ? ' has-children' : '' ?>" data-omo-decision-holon-node>
            <div class="omo-decision-invitations-editor__tree-row">
                <?php if ($hasChildren): ?>
                <button
                    type="button"
                    class="omo-decision-invitations-editor__tree-toggle"
                    data-omo-decision-holon-toggle
                    aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                >
                    <span aria-hidden="true">&#9662;</span>
                </button>
                <?php else: ?>
                <span class="omo-decision-invitations-editor__tree-spacer" aria-hidden="true"></span>
                <?php endif; ?>

                <label class="omo-decision-invitations-editor__check">
                    <input type="checkbox" name="<?= $escape($fieldName) ?>" value="<?= (int)$node['id'] ?>"<?= !empty($node['isSelected']) ? ' checked' : '' ?>>
                    <span class="omo-decision-invitations-editor__check-meta">
                        <strong><?= $escape((string)$node['label']) ?><?= !empty($node['isCurrent']) ? ' ' . $escape((string)$currentLabel) : '' ?></strong>
                        <span class="omo-decision-invitations-editor__check-type"><?= $escape((string)$node['typeLabel']) ?></span>
                    </span>
                </label>
            </div>

            <?php if ($hasChildren): ?>
            <div class="omo-decision-invitations-editor__tree-children" data-omo-decision-holon-children<?= $isExpanded ? '' : ' hidden' ?>>
                <?php foreach ((array)$node['children'] as $childNode): ?>
                    <?php omoDecisionRenderInvitationEditorHolonTreeNode($childNode, $escape, $currentLabel, $fieldName); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('omoDecisionBuildInvitationEditorState')) {
    function omoDecisionBuildInvitationEditorState($decision, array $context)
    {
        $organization = $context['organization'] ?? null;
        $effectiveHolon = $context['effectiveHolon'] ?? null;
        $targetHolonId = (int)($context['targetHolonId'] ?? 0);
        $organizationId = (int)($context['organizationId'] ?? 0);
        $selectionState = omoDecisionExtractInvitationSelections($decision);
        $allowPublicSelfRegistration = $decision instanceof DecisionProcess
            && method_exists($decision, 'isPublicSelfRegistrationEnabled')
            ? $decision->isPublicSelfRegistrationEnabled()
            : false;

        $rootHolon = $organization instanceof \dbObject\Organization ? $organization->getEnabledStructuralRootHolon() : null;
        $holonTree = $rootHolon instanceof Holon
            ? omoDecisionBuildInvitationEditorHolonTreeData($rootHolon, $organization, $selectionState['holon_ids'], $targetHolonId)
            : null;

        $memberships = new \dbObject\ArrayUserOrganization();
        if ($organizationId > 0) {
            $memberships->loadActiveForOrganization($organizationId);
        }

        return [
            'organization' => $organization,
            'effectiveHolon' => $effectiveHolon,
            'organizationId' => $organizationId,
            'targetHolonId' => $targetHolonId,
            'selectedHolonIds' => $selectionState['holon_ids'],
            'selectedUserIds' => $selectionState['user_ids'],
            'selectedEmails' => $selectionState['emails'],
            'hasExplicitInvitations' => $selectionState['count'] > 0,
            'allowPublicSelfRegistration' => $allowPublicSelfRegistration,
            'holonTree' => $holonTree,
            'hasHolonStructure' => is_array($holonTree),
            'currentContextLabel' => $effectiveHolon instanceof Holon ? 'du contexte courant' : 'de l organisation',
            'memberships' => $memberships,
        ];
    }
}

if (!function_exists('omoDecisionApplyInvitationSelections')) {
    function omoDecisionApplyInvitationSelections(DecisionProcess $decision, \dbObject\Organization $organization, $organizationId, array $selectedHolonIds, array $selectedUserIds, $selectedEmails, $allowPublicSelfRegistration = false)
    {
        $organizationId = (int)$organizationId;
        if ((int)$decision->getId() <= 0 || $organizationId <= 0) {
            return [
                'status' => false,
                'message' => 'Contexte d invitations invalide.',
            ];
        }

        $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $selectedHolonIds), static function ($holonId) {
            return $holonId > 0;
        })));
        $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $selectedUserIds), static function ($userId) {
            return $userId > 0;
        })));
        $selectedEmails = omoDecisionParseInvitationEmails($selectedEmails);

        $validHolonLabels = [];
        foreach ($selectedHolonIds as $holonId) {
            $holon = new Holon();
            if (!$holon->load($holonId) || !$organization->containsHolon($holon) || !$holon->canViewDetail()) {
                return [
                    'status' => false,
                    'message' => 'Un holon selectionne est invalide.',
                ];
            }

            $validHolonLabels[$holonId] = trim((string)$holon->getDisplayName());
        }

        $validUserLabels = [];
        foreach ($selectedUserIds as $userId) {
            $membership = new \dbObject\UserOrganization();
            if (
                !$membership->load([
                    ['IDorganization', $organizationId],
                    ['IDuser', $userId],
                ])
                || !(bool)$membership->get('active')
            ) {
                return [
                    'status' => false,
                    'message' => 'Un membre selectionne est invalide.',
                ];
            }

            $validUserLabels[$userId] = trim((string)$membership->getUserDisplayName());
        }

        $existingInvitations = [];
        foreach ($decision->getInvitations(false) as $invitation) {
            if ($invitation instanceof DecisionInvitation) {
                $existingInvitations[$invitation->getIdentityKey()] = $invitation;
            }
        }

        $desiredInvitations = [];
        foreach ($selectedHolonIds as $holonId) {
            $desiredInvitations['holon:' . $holonId] = [
                'invitation_type' => DecisionInvitation::TYPE_HOLON,
                'IDholon' => $holonId,
                'display_name' => $validHolonLabels[$holonId] ?? '',
            ];
        }
        foreach ($selectedUserIds as $userId) {
            $desiredInvitations['user:' . $userId] = [
                'invitation_type' => DecisionInvitation::TYPE_USER,
                'IDuser' => $userId,
                'display_name' => $validUserLabels[$userId] ?? '',
            ];
        }
        foreach ($selectedEmails as $email) {
            $desiredInvitations['email:' . $email] = [
                'invitation_type' => DecisionInvitation::TYPE_EMAIL,
                'email' => $email,
                'display_name' => $email,
            ];
        }

        foreach ($desiredInvitations as $identityKey => $invitationData) {
            $invitation = $existingInvitations[$identityKey] ?? new DecisionInvitation();
            $invitation->set('IDdecision_process', (int)$decision->getId());
            $invitation->set('invitation_type', $invitationData['invitation_type']);
            $invitation->set('IDholon', $invitationData['IDholon'] ?? null);
            $invitation->set('IDuser', $invitationData['IDuser'] ?? null);
            $invitation->set('email', $invitationData['email'] ?? null);
            $invitation->set('display_name', $invitationData['display_name'] ?? null);
            $invitation->set('status', DecisionInvitation::STATUS_INVITED);
            $invitation->set('active', 1);
            $invitation->set('parameters', [
                'updated_from_inline' => 1,
            ]);

            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Impossible d enregistrer les invitations pour le moment.',
                ];
            }
        }

        foreach ($existingInvitations as $identityKey => $invitation) {
            if (isset($desiredInvitations[$identityKey])) {
                continue;
            }

            $invitation->set('active', 0);
            $invitation->set('status', DecisionInvitation::STATUS_REVOKED);
            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => 'Impossible de retirer une invitation pour le moment.',
                ];
            }
        }

        if (
            method_exists($decision, 'setPublicSelfRegistrationEnabled')
            && !$decision->setPublicSelfRegistrationEnabled($allowPublicSelfRegistration)
        ) {
            return [
                'status' => false,
                'message' => 'Impossible d enregistrer le mode de participation publique.',
            ];
        }

        return [
            'status' => true,
            'count' => count($desiredInvitations),
        ];
    }
}

if (!function_exists('omoDecisionPersistInlineInvitationDraft')) {
    function omoDecisionPersistInlineInvitationDraft(DecisionProcess $decision, array $context, array $input)
    {
        if (empty($input['invitation_inline_enabled'])) {
            return [
                'status' => true,
                'applied' => false,
            ];
        }

        $organization = $context['organization'] ?? null;
        if (!$organization instanceof \dbObject\Organization) {
            return [
                'status' => false,
                'message' => 'Organisation introuvable pour les invitations.',
            ];
        }

        return omoDecisionApplyInvitationSelections(
            $decision,
            $organization,
            (int)($context['organizationId'] ?? 0),
            (array)($input['invitation_holon_ids'] ?? []),
            (array)($input['invitation_user_ids'] ?? []),
            $input['invitation_emails'] ?? [],
            !empty($input['allow_public_self_registration'])
        );
    }
}

if (!function_exists('omoDecisionRenderInlineInvitationEditorScript')) {
    function omoDecisionRenderInlineInvitationEditorScript()
    {
        static $alreadyRendered = false;
        if ($alreadyRendered) {
            return '';
        }

        $alreadyRendered = true;

        return '<script>(function(){'
            . 'if(typeof window.omoDecisionInitInvitationEditors!=="function"){'
                . 'window.omoDecisionInitInvitationEditors=function(root){'
                    . 'var scope=(root&&root.querySelectorAll)?root:document;'
                    . 'if(typeof window.initGenericComponents==="function"){window.initGenericComponents(scope);}'
                    . 'Array.prototype.forEach.call(scope.querySelectorAll("[data-omo-decision-invitations-editor]"),function(editor){'
                        . 'if(editor.dataset.omoDecisionInvitationsReady==="1"){return;}'
                        . 'editor.dataset.omoDecisionInvitationsReady="1";'
                        . 'Array.prototype.forEach.call(editor.querySelectorAll("[data-omo-decision-holon-toggle]"),function(toggle){'
                            . 'if(toggle.dataset.omoDecisionBound==="1"){return;}'
                            . 'toggle.dataset.omoDecisionBound="1";'
                            . 'toggle.addEventListener("click",function(event){'
                                . 'var node,children,isExpanded;'
                                . 'event.preventDefault();'
                                . 'event.stopPropagation();'
                                . 'node=toggle.closest("[data-omo-decision-holon-node]");'
                                . 'children=node?node.querySelector("[data-omo-decision-holon-children]"):null;'
                                . 'if(!children){return;}'
                                . 'isExpanded=toggle.getAttribute("aria-expanded")==="true";'
                                . 'toggle.setAttribute("aria-expanded",isExpanded?"false":"true");'
                                . 'children.hidden=isExpanded;'
                            . '});'
                        . '});'
                    . '});'
                . '};'
            . '}'
            . 'if(typeof window.omoDecisionInitInvitationEditors==="function"){window.omoDecisionInitInvitationEditors(document);}'
        . '})();</script>';
    }
}

if (!function_exists('omoDecisionRenderInlineInvitationSection')) {
    function omoDecisionRenderInlineInvitationSection($decision, array $context, $lang, array $sourceLang, $escape, $extraClass = '')
    {
        $editorState = omoDecisionBuildInvitationEditorState($decision, $context);
        $hasHolonStructure = !empty($editorState['hasHolonStructure']);
        $memberships = $editorState['memberships'];
        $holonTree = $editorState['holonTree'];
        $selectedUserIds = $editorState['selectedUserIds'];
        $selectedEmails = $editorState['selectedEmails'];
        $allowPublicSelfRegistration = !empty($editorState['allowPublicSelfRegistration']);

        static $instanceCounter = 0;
        $instanceCounter += 1;
        $instanceId = 'omoDecisionInvitationsInline' . $instanceCounter;
        $membersTabId = $instanceId . 'Members';
        $guestsTabId = $instanceId . 'Guests';
        $holonsTabId = $instanceId . 'Holons';

        ob_start();
        ?>
        <div class="generic-soft-panel generic-soft-panel--stack omo-decision-invitations-editor<?= $extraClass !== '' ? ' ' . $escape(trim((string)$extraClass)) : '' ?>" data-omo-decision-invitations-editor>
            <span class="generic-card-title"><?= $escape(t('decisions.invitations.title', [], $lang, $sourceLang)) ?></span>
            <p class="omo-decision-invitations-editor__intro"><?= $escape(t('decisions.invitations.inline_intro', [], $lang, $sourceLang)) ?></p>

            <?php if (!$hasHolonStructure): ?>
            <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_no_structure', [], $lang, $sourceLang)) ?></p>
            <?php endif; ?>

            <input type="hidden" name="invitation_inline_enabled" value="1">

            <div class="generic-tabs omo-decision-invitations-editor__tabs" data-generic-tabs>
                <div class="generic-tabs__list" aria-label="Categories d invitations">
                    <?php if ($hasHolonStructure): ?>
                    <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="<?= $escape($holonsTabId) ?>"><?= $escape(t('decisions.invitations.tab.holons', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($membersTabId) ?>"><?= $escape(t('decisions.invitations.tab.members', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($guestsTabId) ?>"><?= $escape(t('decisions.invitations.tab.guests', [], $lang, $sourceLang)) ?></button>
                    <?php else: ?>
                    <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="<?= $escape($membersTabId) ?>"><?= $escape(t('decisions.invitations.tab.members', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($guestsTabId) ?>"><?= $escape(t('decisions.invitations.tab.guests', [], $lang, $sourceLang)) ?></button>
                    <?php endif; ?>
                </div>
                <div class="generic-tabs__panels">
                    <?php if ($hasHolonStructure): ?>
                    <div id="<?= $escape($holonsTabId) ?>" class="generic-tabs__panel omo-decision-invitations-editor__tab-panel" data-generic-tab-panel>
                        <strong><?= $escape(t('decisions.invitations.inline_holons_title', [], $lang, $sourceLang)) ?></strong>
                        <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_holons_hint', [], $lang, $sourceLang)) ?></p>
                        <div class="omo-decision-invitations-editor__checklist">
                            <?php if (is_array($holonTree)): ?>
                                <?php omoDecisionRenderInvitationEditorHolonTreeNode($holonTree, $escape, t('decisions.invitations.inline_current_holon', [], $lang, $sourceLang)); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div id="<?= $escape($membersTabId) ?>" class="generic-tabs__panel omo-decision-invitations-editor__tab-panel" data-generic-tab-panel<?= $hasHolonStructure ? ' hidden' : '' ?>>
                        <strong><?= $escape(t('decisions.invitations.inline_members_title', [], $lang, $sourceLang)) ?></strong>
                        <div class="omo-decision-invitations-editor__member-list">
                            <?php foreach ($memberships as $membership): ?>
                                <?php
                                $userId = (int)$membership->get('IDuser');
                                if ($userId <= 0) {
                                    continue;
                                }
                                $displayName = $membership->getUserDisplayName();
                                $secondary = $membership->getScopedEmail() !== '' ? $membership->getScopedEmail() : $membership->getUserSecondaryLabel();
                                ?>
                                <label class="omo-decision-invitations-editor__check">
                                    <input type="checkbox" name="invitation_user_ids[]" value="<?= $userId ?>"<?= in_array($userId, $selectedUserIds, true) ? ' checked' : '' ?>>
                                    <span class="omo-decision-invitations-editor__check-meta">
                                        <strong><?= $escape($displayName) ?></strong>
                                        <?php if ($secondary !== ''): ?>
                                        <span class="omo-decision-invitations-editor__member-email"><?= $escape($secondary) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="omo-decision-invitations-editor__hint">
                            <?= $escape($hasHolonStructure
                                ? t('decisions.invitations.inline_members_hint_structure', [], $lang, $sourceLang)
                                : t('decisions.invitations.inline_members_hint_flat', [], $lang, $sourceLang)) ?>
                        </p>
                    </div>

                    <div id="<?= $escape($guestsTabId) ?>" class="generic-tabs__panel omo-decision-invitations-editor__tab-panel" data-generic-tab-panel hidden>
                        <label for="<?= $escape($instanceId) ?>Emails"><strong><?= $escape(t('decisions.invitations.inline_guests_title', [], $lang, $sourceLang)) ?></strong></label>
                        <textarea
                            id="<?= $escape($instanceId) ?>Emails"
                            name="invitation_emails"
                            class="omo-decision-invitations-editor__textarea generic-form-control"
                            placeholder="<?= $escape(t('decisions.invitations.inline_guests_placeholder', [], $lang, $sourceLang)) ?>"
                        ><?= $escape(implode("\n", $selectedEmails)) ?></textarea>
                        <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_guests_hint', [], $lang, $sourceLang)) ?></p>
                        <div class="generic-soft-panel generic-soft-panel--stack">
                            <label class="omo-decision-invitations-editor__check">
                                <input type="checkbox" name="allow_public_self_registration" value="1"<?= $allowPublicSelfRegistration ? ' checked' : '' ?>>
                                <span class="omo-decision-invitations-editor__check-meta">
                                    <strong><?= $escape(t('decisions.invitations.inline_public_open_title', [], $lang, $sourceLang)) ?></strong>
                                    <span class="omo-decision-invitations-editor__member-email"><?= $escape(t('decisions.invitations.inline_public_open_hint', [], $lang, $sourceLang)) ?></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <p class="omo-decision-invitations-editor__hint"><?= $escape(t('decisions.invitations.inline_save_hint', [], $lang, $sourceLang)) ?></p>
        </div>
        <?= omoDecisionRenderInlineInvitationEditorScript() ?>
        <?php

        return (string)ob_get_clean();
    }
}

if (!function_exists('omoDecisionSendParticipantAccessEmail')) {
    function omoDecisionSendParticipantAccessEmail(DecisionProcess $decision, DecisionParticipant $participant, $message = '', $subject = '')
    {
        $recipient = $decision->getParticipantInvitationRecipientData($participant);
        $email = trim((string)($recipient['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'message' => 'Aucune adresse e-mail valide n a ete trouvee pour ce participant.',
            ];
        }

        $accessUrl = trim((string)$participant->getPublicAccessUrl());
        if ($accessUrl === '') {
            return [
                'status' => false,
                'message' => 'Impossible de generer un lien public valide pour ce participant.',
            ];
        }

        require_once dirname(__DIR__, 4) . '/common/email_layout.php';

        $organization = $decision->getOrganizationObject();
        $organizationName = $organization ? trim((string)$organization->get('name')) : 'Organisation';
        $decisionTitle = trim((string)$decision->get('title'));
        $message = trim((string)$message);
        if ($message === '') {
            $message = $decision->buildPublicAccessRequestEmailMessage();
        }

        $subject = trim((string)$subject);
        if ($subject === '') {
            $subject = $decision->buildDefaultInvitationEmailSubject();
        }

        $fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
        if ($fromAddress === '') {
            $host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
            $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
        }

        $holon = $decision->getHolonObject();
        $detailsItems = [];
        if ($holon instanceof Holon) {
            $detailsItems[] = '<li><strong>Contexte</strong>: '
                . commonMailEscape(trim((string)$holon->getTemplateLabel(true)) . ' ' . trim((string)$holon->getDisplayName()))
                . '</li>';
        }

        $consultationStart = DecisionProcess::normalizeDateTimeValue($decision->get('consultation_start_at'));
        if ($consultationStart instanceof DateTimeInterface) {
            $detailsItems[] = '<li><strong>Debut</strong>: ' . commonMailEscape($consultationStart->format('d.m.Y H:i')) . '</li>';
        }

        $consultationEnd = DecisionProcess::normalizeDateTimeValue($decision->get('consultation_end_at'));
        if ($consultationEnd instanceof DateTimeInterface) {
            $detailsItems[] = '<li><strong>Fin</strong>: ' . commonMailEscape($consultationEnd->format('d.m.Y H:i')) . '</li>';
        }

        $detailsHtml = count($detailsItems) > 0
            ? '<ul style="margin:0; padding-left:18px; color:#475569; line-height:1.7;">' . implode('', $detailsItems) . '</ul>'
            : '';

        $html = commonRenderMailLayout([
            'brand_name' => $organizationName,
            'brand_color' => $organization ? trim((string)$organization->get('color')) : '',
            'logo_url' => $organization ? trim((string)$organization->get('logo')) : '',
            'banner_url' => $organization ? trim((string)$organization->get('banner')) : '',
            'heading' => $decisionTitle !== '' ? $decisionTitle : 'Prise de decision',
            'intro_html' => commonMailTextToHtml($message),
            'details_html' => $detailsHtml,
            'button_label' => 'Ouvrir la prise de decision',
            'button_url' => $accessUrl,
            'footer_html' => '<p style="margin:0;">Ce message a ete envoye depuis ' . commonMailEscape($organizationName) . '.</p>',
        ]);

        $mailSent = myHTMLMail([$fromAddress, $organizationName !== '' ? $organizationName : 'Organisation'], $email, $subject, $html);
        if (!$mailSent) {
            return [
                'status' => false,
                'message' => 'Impossible d envoyer ce lien pour le moment.',
            ];
        }

        $participant->markInvitationSent();

        return [
            'status' => true,
            'email' => $email,
            'display_name' => trim((string)($recipient['display_name'] ?? '')),
            'access_url' => $accessUrl,
        ];
    }
}

if (!function_exists('omoDecisionSendParticipantAccessCodeEmail')) {
    function omoDecisionSendParticipantAccessCodeEmail(DecisionProcess $decision, DecisionParticipant $participant, $publicRequestUrl = '', $subject = '')
    {
        $recipient = $decision->getParticipantInvitationRecipientData($participant);
        $email = trim((string)($recipient['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'message' => 'Aucune adresse e-mail valide n a ete trouvee pour ce participant.',
            ];
        }

        $codeResult = $participant->issuePublicAccessCode(900);
        if (empty($codeResult['status'])) {
            return [
                'status' => false,
                'message' => trim((string)($codeResult['message'] ?? 'Impossible de generer un code d acces pour le moment.')),
            ];
        }

        require_once dirname(__DIR__, 4) . '/common/email_layout.php';

        $organization = $decision->getOrganizationObject();
        $organizationName = $organization ? trim((string)$organization->get('name')) : 'Organisation';
        $decisionTitle = trim((string)$decision->get('title'));
        $publicRequestUrl = trim((string)$publicRequestUrl);
        if ($publicRequestUrl === '') {
            $publicRequestUrl = $decision->getGenericPublicAccessUrl('participate');
        }
        $directIntent = $decision->isParticipationOpen() ? 'participate' : 'view';
        $directAccessUrl = trim((string)$participant->getPublicAccessUrl($directIntent));
        if ($directAccessUrl === '') {
            $directAccessUrl = $publicRequestUrl;
        }

        $subject = trim((string)$subject);
        if ($subject === '') {
            $subject = 'Code d acces a la prise de decision';
            if ($decisionTitle !== '') {
                $subject .= ' : ' . $decisionTitle;
            }
        }

        $fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
        if ($fromAddress === '') {
            $host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
            $fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
        }

        $expiresAt = $codeResult['expires_at'] ?? null;
        $expiresLabel = $expiresAt instanceof DateTimeInterface
            ? $expiresAt->format('d.m.Y H:i')
            : '';

        $messageLines = [
            'Bonjour,',
            '',
            'Vous avez demande un acces a la prise de decision "' . ($decisionTitle !== '' ? $decisionTitle : 'sans titre') . '".',
            'Vous pouvez soit cliquer sur le lien personnel recu dans cet e-mail, soit copier le code ci-dessous sur la page publique pour continuer.',
        ];
        if ($expiresLabel !== '') {
            $messageLines[] = 'Ce code est valable jusqu au ' . $expiresLabel . '.';
        }
        $messageLines[] = '';
        $messageLines[] = 'A bientot,';
        $messageLines[] = $organizationName;

        $codeHtml = '<div style="display:inline-block;padding:16px 22px;background:#f3f4f6;border-radius:12px;border:1px solid #e5e7eb;font:700 32px/1.2 Consolas, Monaco, monospace;letter-spacing:0.22em;color:#111827;">'
            . commonMailEscape((string)($codeResult['code'] ?? ''))
            . '</div>';
        if ($expiresLabel !== '') {
            $codeHtml .= '<p style="margin:14px 0 0;color:#64748b;line-height:1.6;">Valable jusqu au ' . commonMailEscape($expiresLabel) . '.</p>';
        }
        if ($directAccessUrl !== '') {
            $codeHtml .= '<div style="margin-top:18px;">'
                . '<p style="margin:0 0 8px;color:#111827;line-height:1.6;"><strong>Lien direct personnel</strong></p>'
                . '<div style="padding:12px 14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;word-break:break-all;line-height:1.6;">'
                . '<a href="' . commonMailEscape($directAccessUrl) . '" style="color:#2563eb;text-decoration:none;">' . commonMailEscape($directAccessUrl) . '</a>'
                . '</div>'
                . '</div>';
        }

        $html = commonRenderMailLayout([
            'brand_name' => $organizationName,
            'brand_color' => $organization ? trim((string)$organization->get('color')) : '',
            'logo_url' => $organization ? trim((string)$organization->get('logo')) : '',
            'banner_url' => $organization ? trim((string)$organization->get('banner')) : '',
            'heading' => $decisionTitle !== '' ? $decisionTitle : 'Prise de decision',
            'intro_html' => commonMailTextToHtml(implode("\n", $messageLines)),
            'details_html' => $codeHtml,
            'button_label' => 'Ouvrir directement le scrutin',
            'button_url' => $directAccessUrl !== '' ? $directAccessUrl : $publicRequestUrl,
            'footer_html' => '<p style="margin:0;">Ce message a ete envoye depuis ' . commonMailEscape($organizationName) . '.</p>',
        ]);

        $mailSent = myHTMLMail([$fromAddress, $organizationName !== '' ? $organizationName : 'Organisation'], $email, $subject, $html);
        if (!$mailSent) {
            $participant->clearPublicAccessCode();
            return [
                'status' => false,
                'message' => 'Impossible d envoyer ce code pour le moment.',
            ];
        }

        $participant->markInvitationSent();

        return [
            'status' => true,
            'email' => $email,
            'display_name' => trim((string)($recipient['display_name'] ?? '')),
            'expires_at' => $expiresAt instanceof DateTimeInterface ? $expiresAt->format('c') : '',
            'public_url' => $publicRequestUrl,
            'direct_url' => $directAccessUrl,
        ];
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
            'sendPopupUrl' => '',
            'publicUrl' => '',
            'sendEnabled' => false,
            'invitationCount' => 0,
            'hasExplicitInvitations' => false,
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
        $data['sendPopupUrl'] = omoDecisionBuildInvitationSendPopupUrl(
            (int)($context['organizationId'] ?? 0),
            (int)($context['targetHolonId'] ?? 0),
            (int)$decision->getId(),
            $method
        );
        $data['publicUrl'] = $decision->getGenericPublicAccessUrl('view');
        $data['sendEnabled'] = count($decision->getInvitationEmailRecipients()) > 0
            && DecisionProcess::normalizeStatus($decision->get('status')) !== DecisionProcess::STATUS_DRAFT;
        $hasPublicSelfRegistration = method_exists($decision, 'isPublicSelfRegistrationEnabled')
            && $decision->isPublicSelfRegistrationEnabled();

        $invitations = [];
        foreach ($decision->getInvitations(true) as $invitation) {
            if ($invitation instanceof DecisionInvitation && DecisionInvitation::normalizeStatus($invitation->get('status')) !== DecisionInvitation::STATUS_REVOKED) {
                $invitations[] = $invitation;
            }
        }
        $data['invitationCount'] = count($invitations);
        $data['hasExplicitInvitations'] = $data['invitationCount'] > 0 || $hasPublicSelfRegistration;

        if (count($invitations) === 0) {
            $defaultSummary = t('decisions.invitations.default_scope', [], $lang, $sourceLang);
            if ($currentHolon instanceof Holon) {
                $defaultSummary = rtrim($defaultSummary, '.');
                $defaultSummary .= ' ' . $currentHolon->getTemplateLabel(true) . ' ' . trim((string)$currentHolon->getDisplayName()) . '.';
            }

            if ($hasPublicSelfRegistration) {
                $defaultSummary .= ' Participation publique ouverte.';
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
        if ($hasPublicSelfRegistration) {
            $summaryParts[] = 'Participation publique ouverte';
        }

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
        if (empty($summaryData['hasExplicitInvitations'])) {
            return omoDecisionRenderInlineInvitationSection($decision, $context, $lang, $sourceLang, $escape, $extraClass);
        }

        $extraClass = trim((string)$extraClass);
        if ($extraClass !== '') {
            $extraClass = ' ' . $extraClass;
        }

        $buttonDisabled = empty($summaryData['isPersisted']) || trim((string)$summaryData['popupUrl']) === '';
        $sendDisabled = empty($summaryData['isPersisted'])
            || trim((string)$summaryData['sendPopupUrl']) === ''
            || empty($summaryData['sendEnabled']);

        return '<div class="generic-soft-panel generic-soft-panel--stack' . $extraClass . '">'
            . '<div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">'
                . '<span class="generic-card-title">' . $escape(t('decisions.invitations.title', [], $lang, $sourceLang)) . '</span>'
                . '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
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
                    . '<button'
                        . ' type="button"'
                        . ' class="generic-action-button generic-action-button--main"'
                        . ' data-omo-decision-invitations-send-open'
                        . ' data-omo-decision-invitations-send-url="' . $escape((string)$summaryData['sendPopupUrl']) . '"'
                        . ' data-omo-decision-invitations-send-title="' . $escape(t('decisions.invitations.send_popup_title', [], $lang, $sourceLang)) . '"'
                        . ($sendDisabled ? ' disabled' : '')
                    . '>'
                        . $escape(t('decisions.invitations.send', [], $lang, $sourceLang))
                    . '</button>'
                    . '<a'
                        . ' class="generic-action-button generic-action-button--secondary"'
                        . ' href="' . $escape((string)$summaryData['publicUrl']) . '"'
                        . ' target="_blank"'
                        . ' rel="noopener noreferrer"'
                        . (trim((string)$summaryData['publicUrl']) === '' ? ' aria-disabled="true"' : '')
                    . '>'
                        . $escape('Lien public')
                    . '</a>'
                . '</div>'
            . '</div>'
            . '<p style="margin:0;color:var(--color-text-light,#475569);line-height:1.6;" data-omo-decision-invitations-summary>'
                . $escape((string)$summaryData['summary'])
            . '</p>'
        . '</div>';
    }
}
