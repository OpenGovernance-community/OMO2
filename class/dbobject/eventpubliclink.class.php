<?php
namespace dbObject;

class EventPublicLink extends DbObject
{
    public static function tableName() { return 'event_public_link'; }
    public static function rules()
    {
        return [[['IDevent', 'token'], 'required'], [['id'], 'integer'], [['IDevent'], 'fk'],
            [['token'], 'string'], [['enabled'], 'boolean'], [['created_at'], 'datetime'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDevent' => 'Evenement', 'token' => 'Lien',
            'enabled' => 'Inscription publique', 'created_at' => 'Creation'];
    }
    public static function attributeLength() { return ['token' => 64]; }

    public static function forEvent(int $eventId): ?self
    {
        $link = new self();
        return $eventId > 0 && $link->load(['IDevent', $eventId]) ? $link : null;
    }

    public static function forToken(string $token): ?self
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) { return null; }
        $link = new self();
        return $link->load(['token', $token]) && (int)$link->get('enabled') === 1 ? $link : null;
    }

    public function publicPath(): string
    {
        return '/event/register/' . rawurlencode((string)$this->get('token'));
    }

    public static function setForEvent(int $eventId, bool $enabled): bool
    {
        $link = self::forEvent($eventId);
        if (!$link && !$enabled) { return true; }
        if (!$link) {
            $link = new self();
            $link->set('IDevent', $eventId);
            $link->set('token', bin2hex(random_bytes(32)));
            $link->set('created_at', new \DateTimeImmutable());
        }
        $link->set('enabled', $enabled ? 1 : 0);
        $result = $link->save();
        return is_array($result) && !empty($result['status']);
    }
}
