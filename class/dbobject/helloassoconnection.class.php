<?php
namespace dbObject;

class HelloAssoConnection extends DbObject
{
    public static function tableName() { return 'helloasso_connection'; }
    public static function rules()
    {
        return [[['id'], 'integer'], [['configuration_key'], 'string'],
            [['access_token', 'refresh_token'], 'text'], [['token_expires_at'], 'datetime'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'configuration_key' => 'Configuration', 'access_token' => 'Jeton API',
            'refresh_token' => 'Jeton de renouvellement', 'token_expires_at' => 'Expiration'];
    }
    public static function attributeLength() { return ['configuration_key' => 64]; }
    public function canView() { return false; }
    public function canEdit() { return false; }
    public static function isStorageAvailable(): bool { return self::tableExists(static::tableName()); }

    /** Serialize token rotation across cron and interactive account activation. */
    public static function withLock(string $key, callable $callback, int $wait = 0)
    {
        $name = 'helloasso-' . substr(hash('sha256', ($GLOBALS['dbName'] ?? '') . ':' . $key), 0, 48);
        if ((int)self::fetchValue('SELECT GET_LOCK(:name, :wait)', ['name' => $name, 'wait' => $wait]) !== 1) {
            throw new \RuntimeException('HelloAsso synchronization already running.');
        }
        try { return $callback(); }
        finally { self::fetchValue('SELECT RELEASE_LOCK(:name)', ['name' => $name]); }
    }

    public static function forConfiguration(string $key): self
    {
        $item = new self();
        if (!$item->load(['configuration_key', $key])) $item->set('configuration_key', $key);
        return $item;
    }
}
