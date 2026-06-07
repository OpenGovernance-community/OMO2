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
            $subject = 'Acces a la prise de decision';
            if ($decisionTitle !== '') {
                $subject .= ' : ' . $decisionTitle;
            }
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
            'button_url' => $participant->getPublicAccessUrl(),
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
            'access_url' => $participant->getPublicAccessUrl(),
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
