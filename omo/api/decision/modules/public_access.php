<?php

use dbObject\DecisionParticipant;

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
