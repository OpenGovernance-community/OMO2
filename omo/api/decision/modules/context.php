<?php

use dbObject\DecisionProcess;
use dbObject\DecisionGroup;
use dbObject\DecisionParticipant;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;

require_once __DIR__ . '/public_access.php';

if (!function_exists('omoDecisionBuildEditorUrl')) {
    function omoDecisionBuildEditorUrl($organizationId, $holonId = 0, $decisionId = 0, $method = '', $intent = '', $decisionGroupId = 0, $groupAction = '')
    {
        $query = [
            'oid' => (int)$organizationId,
        ];

        if ((int)$holonId > 0) {
            $query['cid'] = (int)$holonId;
        }

        if ((int)$decisionId > 0) {
            $query['id'] = (int)$decisionId;
        }

        if ((int)$decisionGroupId > 0) {
            $query['gid'] = (int)$decisionGroupId;
        }

        $method = trim((string)$method);
        if ($method !== '') {
            $query['method'] = $method;
        }

        $intent = trim((string)$intent);
        if ($intent !== '') {
            $query['intent'] = $intent;
        }

        $groupAction = trim((string)$groupAction);
        if ($groupAction !== '') {
            $query['group_action'] = $groupAction;
        }

        return '/omo/api/decision/edit.php?' . http_build_query($query);
    }
}

if (!function_exists('omoDecisionBuildContextualEditorUrl')) {
    function omoDecisionBuildContextualEditorUrl(array $context, $intent = '')
    {
        $intent = trim((string)$intent);

        if (
            (($context['accessMode'] ?? '') === 'public')
            && trim((string)($context['publicToken'] ?? '')) !== ''
        ) {
            return omoDecisionBuildPublicParticipationUrl((string)$context['publicToken'], $intent);
        }

        return omoDecisionBuildEditorUrl(
            (int)($context['organizationId'] ?? 0),
            (int)($context['targetHolonId'] ?? 0),
            (int)($context['decisionId'] ?? 0),
            trim((string)(
                (($context['decisionGroup'] ?? null) instanceof DecisionGroup)
                    ? $context['decisionGroup']->get('evaluation_method')
                    : ((($context['decision'] ?? null) instanceof DecisionProcess) ? $context['decision']->get('evaluation_method') : ($context['method'] ?? ''))
            )),
            $intent,
            (int)($context['decisionGroupId'] ?? 0)
        );
    }
}

if (!function_exists('omoDecisionBuildParticipationPreviewUrl')) {
    function omoDecisionBuildParticipationPreviewUrl($organizationId, $holonId = 0, $decisionId = 0, $method = '', $intent = 'view', $embedded = false)
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

        $intent = trim((string)$intent);
        if ($intent !== '') {
            $query['intent'] = $intent;
        }

        if ($embedded) {
            $query['embedded'] = '1';
        }

        return '/common/decision_participation.php?' . http_build_query($query);
    }
}

if (!function_exists('omoDecisionResolveEditorContext')) {
    function omoDecisionResolveEditorContext(array $input)
    {
        $publicToken = trim((string)($input['token'] ?? ''));
        if ($publicToken !== '') {
            $participant = omoDecisionResolvePublicParticipantByToken($publicToken);
            if (!($participant instanceof DecisionParticipant)) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.decision_denied',
                ];
            }

            $decision = $participant->getDecisionProcess();
            if (!($decision instanceof DecisionProcess) || !$decision->load((int)$decision->getId())) {
                return [
                    'status' => false,
                    'code' => 404,
                    'error_key' => 'decisions.edit.context.decision_not_found',
                ];
            }

            $decision->syncLifecycleStatus();

            $organizationId = (int)$decision->get('IDorganization');
            $organization = new Organization();
            if ($organizationId <= 0 || !$organization->load($organizationId)) {
                return [
                    'status' => false,
                    'code' => 404,
                    'error_key' => 'decisions.edit.context.organization_not_found',
                ];
            }

            $currentHolon = $organization->getStructuralRootHolon();
            $decisionHolon = null;
            $decisionHolonId = (int)$decision->get('IDholon');
            if ($decisionHolonId > 0) {
                $decisionHolon = new Holon();
                if (!$decisionHolon->load($decisionHolonId) || !$organization->containsHolon($decisionHolon)) {
                    return [
                        'status' => false,
                        'code' => 404,
                        'error_key' => 'decisions.edit.context.holon_not_found',
                    ];
                }
            }

            $effectiveHolon = $decisionHolon ?: $currentHolon;
            $decisionGroup = $decision->getPrimaryGroup(true);
            $requestedIntent = isset($input['intent']) ? trim((string)$input['intent']) : '';
            $participantStatus = DecisionParticipant::normalizeStatus($participant->get('status'));
            $hasParticipation = (int)$participant->get('active') === 1
                && !in_array($participantStatus, [
                    DecisionParticipant::STATUS_DECLINED,
                    DecisionParticipant::STATUS_REVOKED,
                ], true);
            $canManage = false;
            $canCreate = false;
            $canView = $hasParticipation;
            $canParticipate = $hasParticipation && $decision->isParticipationOpen();

            $intent = $canParticipate ? 'participate' : 'view';
            if (in_array($requestedIntent, ['view', 'participate'], true)) {
                $intent = $requestedIntent;
            }

            if ($intent === 'participate' && !$canParticipate) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.decision_denied',
                ];
            }

            if ($intent === 'view' && !$canView) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.decision_denied',
                ];
            }

            return [
                'status' => true,
                'accessMode' => 'public',
                'publicToken' => $publicToken,
                'organizationId' => $organizationId,
                'requestedHolonId' => $effectiveHolon ? (int)$effectiveHolon->getId() : 0,
                'targetHolonId' => $effectiveHolon ? (int)$effectiveHolon->getId() : 0,
                'decisionId' => (int)$decision->getId(),
                'decisionGroupId' => $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0,
                'currentUserId' => 0,
                'organization' => $organization,
                'currentHolon' => $currentHolon,
                'decisionHolon' => $decisionHolon,
                'effectiveHolon' => $effectiveHolon,
                'decision' => $decision,
                'decisionGroup' => $decisionGroup,
                'participant' => $participant,
                'currentUserEmail' => trim((string)$participant->get('email')),
                'intent' => $intent,
                'canCreate' => $canCreate,
                'canManage' => $canManage,
                'canView' => $canView,
                'canParticipate' => $canParticipate,
                'hasParticipation' => $hasParticipation,
                'isOwner' => false,
            ];
        }

        $organizationId = isset($input['oid']) ? (int)$input['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
        $requestedHolonId = isset($input['cid']) ? (int)$input['cid'] : 0;
        $decisionId = isset($input['id']) ? (int)$input['id'] : 0;
        $decisionGroupId = isset($input['gid']) ? (int)$input['gid'] : 0;
        $requestedIntent = isset($input['intent']) ? trim((string)$input['intent']) : '';
        $currentUserId = function_exists('commonGetCurrentUserId')
            ? (int)commonGetCurrentUserId()
            : (int)($_SESSION['currentUser'] ?? 0);
        $currentUserEmail = '';

        if (!empty($input['public'])) {
            return omoDecisionResolveGenericPublicContext($input);
        }

        $organization = new Organization();
        if ($organizationId <= 0) {
            return [
                'status' => false,
                'code' => 400,
                'error_key' => 'decisions.edit.context.organization_invalid',
            ];
        }

        if (!$organization->load($organizationId)) {
            return [
                'status' => false,
                'code' => 404,
                'error_key' => 'decisions.edit.context.organization_not_found',
            ];
        }

        if (!$organization->canViewDetail()) {
            return [
                'status' => false,
                'code' => 403,
                'error_key' => 'decisions.edit.context.organization_denied',
            ];
        }

        if ($currentUserId > 0) {
            $currentUser = new User();
            if ($currentUser->load($currentUserId)) {
                $currentUserEmail = trim(mb_strtolower((string)$currentUser->getScopedEmail($organizationId), 'UTF-8'));
            }
        }

        $currentHolon = $organization->getStructuralRootHolon();
        if (!$currentHolon) {
            return [
                'status' => false,
                'code' => 404,
                'error_key' => 'decisions.edit.context.holon_not_found',
            ];
        }

        if ($requestedHolonId > 0 && (int)$currentHolon->getId() !== $requestedHolonId) {
            $candidateHolon = new Holon();
            if (!$candidateHolon->load($requestedHolonId) || !$organization->containsHolon($candidateHolon)) {
                return [
                    'status' => false,
                    'code' => 404,
                    'error_key' => 'decisions.edit.context.holon_not_found',
                ];
            }

            if (!$candidateHolon->canViewDetail()) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.holon_denied',
                ];
            }

            $currentHolon = $candidateHolon;
        }

        $decision = null;
        $decisionGroup = null;
        $decisionHolon = null;
        $participant = null;
        $hasParticipation = false;
        if ($decisionId > 0) {
            $decision = new DecisionProcess();
            if (!$decision->load($decisionId)) {
                return [
                    'status' => false,
                    'code' => 404,
                    'error_key' => 'decisions.edit.context.decision_not_found',
                ];
            }

            if ((int)$decision->get('IDorganization') !== $organizationId) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.decision_mismatch',
                ];
            }

            $decision->syncLifecycleStatus();

            $decisionHolonId = (int)$decision->get('IDholon');
            if ($decisionHolonId > 0) {
                $decisionHolon = new Holon();
                if (!$decisionHolon->load($decisionHolonId) || !$organization->containsHolon($decisionHolon)) {
                    return [
                        'status' => false,
                        'code' => 404,
                        'error_key' => 'decisions.edit.context.holon_not_found',
                    ];
                }

                if (!$decisionHolon->canViewDetail()) {
                    return [
                        'status' => false,
                        'code' => 403,
                        'error_key' => 'decisions.edit.context.holon_denied',
                    ];
                }
            }

            if ($currentUserId > 0) {
                $participant = DecisionParticipant::findByDecisionAndUser($decisionId, $currentUserId);
            }
            if (
                (!$participant || (int)$participant->get('active') !== 1)
                && $currentUserEmail !== ''
            ) {
                $participant = DecisionParticipant::findByDecisionAndEmail($decisionId, $currentUserEmail);
            }

            if ($participant instanceof DecisionParticipant) {
                $participantStatus = DecisionParticipant::normalizeStatus($participant->get('status'));
                $hasParticipation = (int)$participant->get('active') === 1
                    && !in_array($participantStatus, [
                        DecisionParticipant::STATUS_DECLINED,
                        DecisionParticipant::STATUS_REVOKED,
                    ], true);
            }

            $decisionGroup = $decision->getPrimaryGroup(true);
            if ($decisionGroupId > 0) {
                $candidateGroup = new DecisionGroup();
                if (
                    !$candidateGroup->load($decisionGroupId)
                    || (int)$candidateGroup->get('IDdecision_process') !== (int)$decision->getId()
                ) {
                    return [
                        'status' => false,
                        'code' => 404,
                        'error_key' => 'decisions.edit.context.decision_not_found',
                    ];
                }
                $decisionGroup = $candidateGroup;
            }
            $decisionGroupId = $decisionGroup instanceof DecisionGroup ? (int)$decisionGroup->getId() : 0;
        }

        $effectiveHolon = $decisionHolon ?: $currentHolon;
        $isOwnerParticipant = $participant instanceof DecisionParticipant
            && (int)$participant->get('active') === 1
            && DecisionParticipant::normalizeRole($participant->get('role')) === DecisionParticipant::ROLE_OWNER;
        $isOwner = $decision instanceof DecisionProcess
            && $currentUserId > 0
            && (
                (int)$decision->get('IDuser') === $currentUserId
                || $isOwnerParticipant
            );
        $canCreate = $effectiveHolon ? $effectiveHolon->canEdit() : $organization->canEdit();
        $canManage = $decision instanceof DecisionProcess ? $isOwner : $canCreate;
        $canView = $decision instanceof DecisionProcess
            ? ($canManage || $hasParticipation || DecisionProcess::normalizeStatus($decision->get('status')) !== DecisionProcess::STATUS_DRAFT)
            : $canCreate;
        $canParticipate = $decision instanceof DecisionProcess
            ? (($isOwner || $hasParticipation) && $decision->isParticipationOpen())
            : false;

        $intent = 'manage';
        if ($decision instanceof DecisionProcess) {
            $allowedIntents = ['manage', 'view', 'participate'];
            $intent = in_array($requestedIntent, $allowedIntents, true)
                ? $requestedIntent
                : ($canManage ? 'manage' : 'view');
        }

        if ($decision instanceof DecisionProcess) {
            if ($intent === 'manage' && !$canManage) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.decision_denied',
                ];
            }

            if ($intent === 'participate' && !$canParticipate) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.decision_denied',
                ];
            }

            if (($intent === 'view' || $requestedIntent === '') && !$canView && !$canParticipate) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.decision_denied',
                ];
            }
        } else {
            if ($effectiveHolon && !$effectiveHolon->canEdit()) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.holon_manage_denied',
                ];
            }

            if (!$effectiveHolon && !$organization->canEdit()) {
                return [
                    'status' => false,
                    'code' => 403,
                    'error_key' => 'decisions.edit.context.organization_manage_denied',
                ];
            }
        }

        return [
            'status' => true,
            'accessMode' => 'app',
            'publicToken' => '',
            'organizationId' => $organizationId,
            'requestedHolonId' => $requestedHolonId,
            'targetHolonId' => $effectiveHolon ? (int)$effectiveHolon->getId() : 0,
            'decisionId' => $decisionId,
            'decisionGroupId' => $decisionGroupId,
            'currentUserId' => $currentUserId,
            'organization' => $organization,
            'currentHolon' => $currentHolon,
            'decisionHolon' => $decisionHolon,
            'effectiveHolon' => $effectiveHolon,
            'decision' => $decision,
            'decisionGroup' => $decisionGroup,
            'participant' => $participant,
            'currentUserEmail' => $currentUserEmail,
            'intent' => $intent,
            'canCreate' => $canCreate,
            'canManage' => $canManage,
            'canView' => $canView,
            'canParticipate' => $canParticipate,
            'hasParticipation' => $hasParticipation,
            'isOwner' => $isOwner,
        ];
    }
}
