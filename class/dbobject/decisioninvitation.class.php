<?php
namespace dbObject;

class DecisionInvitation extends ResourceInvitation
{
    public static function resourceType()
    {
        return 'decision_process';
    }

    protected static function legacyResourceField()
    {
        return 'IDdecision_process';
    }

    public static function findByDecisionAndHolon($decisionProcessId, $holonId)
    {
        return static::findByResourceAndIdentity($decisionProcessId, self::TYPE_HOLON, ['holon_id' => (int)$holonId]);
    }

    public static function findByDecisionAndUser($decisionProcessId, $userId)
    {
        return static::findByResourceAndIdentity($decisionProcessId, self::TYPE_USER, ['user_id' => (int)$userId]);
    }

    public static function findByDecisionAndEmail($decisionProcessId, $email)
    {
        return static::findByResourceAndIdentity($decisionProcessId, self::TYPE_EMAIL, ['email' => $email]);
    }
}
