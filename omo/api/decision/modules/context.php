<?php

use dbObject\DecisionProcess;
use dbObject\DecisionParticipant;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\User;

if (!function_exists('omoDecisionBuildEditorUrl')) {
    function omoDecisionBuildEditorUrl($organizationId, $holonId = 0, $decisionId = 0, $method = '', $intent = '')
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

        $method = trim((string)$method);
        if ($method !== '') {
            $query['method'] = $method;
        }

        $intent = trim((string)$intent);
        if ($intent !== '') {
            $query['intent'] = $intent;
        }

        return '/omo/api/decision/edit.php?' . http_build_query($query);
    }
}

if (!function_exists('omoDecisionResolveEditorContext')) {
    function omoDecisionResolveEditorContext(array $input)
    {
        $organizationId = isset($input['oid']) ? (int)$input['oid'] : (int)($_SESSION['currentOrganization'] ?? 0);
        $requestedHolonId = isset($input['cid']) ? (int)$input['cid'] : 0;
        $decisionId = isset($input['id']) ? (int)$input['id'] : 0;
        $requestedIntent = isset($input['intent']) ? trim((string)$input['intent']) : '';
        $currentUserId = function_exists('commonGetCurrentUserId')
            ? (int)commonGetCurrentUserId()
            : (int)($_SESSION['currentUser'] ?? 0);
        $currentUserEmail = '';

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
        }

        $effectiveHolon = $decisionHolon ?: $currentHolon;
        $isOwner = $decision instanceof DecisionProcess
            && $currentUserId > 0
            && (int)$decision->get('IDuser') === $currentUserId;
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
            'organizationId' => $organizationId,
            'requestedHolonId' => $requestedHolonId,
            'targetHolonId' => $effectiveHolon ? (int)$effectiveHolon->getId() : 0,
            'decisionId' => $decisionId,
            'currentUserId' => $currentUserId,
            'organization' => $organization,
            'currentHolon' => $currentHolon,
            'decisionHolon' => $decisionHolon,
            'effectiveHolon' => $effectiveHolon,
            'decision' => $decision,
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
