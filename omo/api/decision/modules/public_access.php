<?php

use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionGroup;
use dbObject\Organization;
use dbObject\Holon;

if (!function_exists('omoDecisionResolvePublicParticipantByToken')) {
    function omoDecisionResolvePublicParticipantByToken($token)
    {
        $token = trim((string)$token);
        if ($token === '') {
            return null;
        }

        $participant = DecisionParticipant::findByAccessToken($token);
        if (!($participant instanceof DecisionParticipant)) {
            return null;
        }

        if ((int)$participant->get('active') !== 1) {
            return null;
        }

        $status = DecisionParticipant::normalizeStatus($participant->get('status'));
        if (in_array($status, [
            DecisionParticipant::STATUS_DECLINED,
            DecisionParticipant::STATUS_REVOKED,
        ], true)) {
            return null;
        }

        return $participant;
    }
}

if (!function_exists('omoDecisionCanUsePublicTokenForPath')) {
    function omoDecisionCanUsePublicTokenForPath($path)
    {
        $path = commonNormalizeLocalPath($path, '/');

        return in_array($path, [
            '/omo/api/decision/modules/vote/respond.php',
            '/omo/api/decision/modules/majority_judgment/respond.php',
            '/omo/api/decision/modules/consent/respond.php',
            '/omo/api/decision/modules/proposals/consultation_add.php',
        ], true);
    }
}

if (!function_exists('omoDecisionBuildPublicParticipationUrl')) {
    function omoDecisionBuildPublicParticipationUrl($token, $intent = '')
    {
        $query = [
            'token' => trim((string)$token),
        ];

        $intent = trim((string)$intent);
        if ($intent !== '') {
            $query['intent'] = $intent;
        }

        return commonBuildUrl('/common/decision_participation.php?' . http_build_query($query), commonGetRequestHost());
    }
}

if (!function_exists('omoDecisionBuildGenericPublicParticipationUrl')) {
    function omoDecisionBuildGenericPublicParticipationUrl($organizationId, $holonId = 0, $decisionId = 0, $intent = 'view')
    {
        $query = [
            'public' => '1',
            'oid' => (int)$organizationId,
            'id' => (int)$decisionId,
        ];

        if ((int)$holonId > 0) {
            $query['cid'] = (int)$holonId;
        }

        $intent = trim((string)$intent);
        if ($intent !== '') {
            $query['intent'] = $intent;
        }

        return commonBuildUrl('/common/decision_participation.php?' . http_build_query($query), commonGetRequestHost());
    }
}

if (!function_exists('omoDecisionResolveGenericPublicContext')) {
    function omoDecisionResolveGenericPublicContext(array $input)
    {
        $organizationId = isset($input['oid']) ? (int)$input['oid'] : 0;
        $requestedHolonId = isset($input['cid']) ? (int)$input['cid'] : 0;
        $decisionId = isset($input['id']) ? (int)$input['id'] : 0;
        $intent = isset($input['intent']) ? trim((string)$input['intent']) : 'view';

        if ($organizationId <= 0 || $decisionId <= 0) {
            return [
                'status' => false,
                'code' => 400,
                'error_key' => 'decisions.edit.context.organization_invalid',
            ];
        }

        $organization = new Organization();
        if (!$organization->load($organizationId)) {
            return [
                'status' => false,
                'code' => 404,
                'error_key' => 'decisions.edit.context.organization_not_found',
            ];
        }

        $decision = new DecisionProcess();
        if (
            !$decision->load($decisionId)
            || (int)$decision->get('IDorganization') !== $organizationId
        ) {
            return [
                'status' => false,
                'code' => 404,
                'error_key' => 'decisions.edit.context.decision_not_found',
            ];
        }

        $decision->syncLifecycleStatus();

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

        if ($requestedHolonId > 0 && $decisionHolon instanceof Holon && (int)$decisionHolon->getId() !== $requestedHolonId) {
            return [
                'status' => false,
                'code' => 404,
                'error_key' => 'decisions.edit.context.holon_not_found',
            ];
        }

        $effectiveHolon = $decisionHolon ?: $currentHolon;
        $decisionGroup = $decision->getPrimaryGroup(false);
        if (!$decisionGroup instanceof DecisionGroup) {
            $decisionGroup = $decision->ensurePrimaryGroup();
        }

        return [
            'status' => true,
            'accessMode' => 'public_request',
            'publicToken' => '',
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
            'participant' => null,
            'currentUserEmail' => '',
            'intent' => in_array($intent, ['view', 'participate'], true) ? $intent : 'view',
            'canCreate' => false,
            'canManage' => false,
            'canView' => true,
            'canParticipate' => false,
            'hasParticipation' => false,
            'isOwner' => false,
            'requiresPublicAccessEmail' => true,
        ];
    }
}
