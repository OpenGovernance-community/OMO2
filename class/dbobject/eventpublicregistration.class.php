<?php
namespace dbObject;

class EventPublicRegistration extends DbObject
{
    public static function tableName() { return 'event_public_registration'; }
    public static function rules()
    {
        return [[['IDevent', 'name', 'email', 'token'], 'required'], [['id'], 'integer'],
            [['IDevent'], 'fk'], [['name', 'token'], 'string'], [['email'], 'mail'],
            [['created_at', 'confirmed_at'], 'datetime'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDevent' => 'Evenement', 'name' => 'Nom',
            'email' => 'E-mail', 'token' => 'Confirmation', 'created_at' => 'Creation',
            'confirmed_at' => 'Confirmation'];
    }
    public static function attributeLength() { return ['name' => 190, 'email' => 254, 'token' => 64]; }

    public static function forEmail(int $eventId, string $email): ?self
    {
        $item = new self();
        return $item->load([['IDevent', $eventId], ['email', $email]]) ? $item : null;
    }

    public static function forToken(string $token): ?self
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) { return null; }
        $item = new self();
        return $item->load(['token', $token]) ? $item : null;
    }

    public static function confirmedCount(int $eventId): int
    {
        $row = self::fetchRow('SELECT COUNT(*) AS total FROM event_public_registration WHERE IDevent = :event_id AND confirmed_at IS NOT NULL',
            ['event_id' => $eventId]);
        return (int)($row['total'] ?? 0);
    }

    public static function presentCount(int $eventId): int
    {
        $row = self::fetchRow('SELECT COUNT(*) AS total FROM resource_attendance WHERE resource_type = :type AND resource_id = :event_id AND active = 1 AND is_present = 1',
            ['type' => 'event', 'event_id' => $eventId]);
        return (int)($row['total'] ?? 0);
    }
}
