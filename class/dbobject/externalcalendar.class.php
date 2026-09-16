<?php
namespace dbObject;

class ExternalCalendar extends DbObject
{
    public static function tableName()
    {
        return 'external_calendar';
    }

    public static function rules()
    {
        return [
            [['IDuser', 'provider', 'title', 'calendar_url', 'username', 'password_encrypted'], 'required'],
            [['id'], 'integer'],
            [['IDuser'], 'fk'],
            [['provider', 'title', 'calendar_url', 'username', 'password_encrypted', 'color', 'timezone', 'source_ctag'], 'string'],
            [['last_sync_error'], 'text'],
            [['active'], 'boolean'],
            [['last_sync_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'IDuser' => 'Utilisateur',
            'provider' => 'Fournisseur',
            'title' => 'Calendrier',
            'calendar_url' => 'Adresse CalDAV',
            'username' => 'Identifiant',
            'password_encrypted' => 'Mot de passe chiffre',
            'color' => 'Couleur',
            'timezone' => 'Fuseau horaire',
            'source_ctag' => 'Version source',
            'active' => 'Actif',
            'last_sync_at' => 'Derniere synchronisation',
            'last_sync_error' => 'Erreur de synchronisation',
        ];
    }

    public static function attributeLength()
    {
        return [
            'provider' => 32,
            'title' => 190,
            'calendar_url' => 2000,
            'username' => 250,
            'color' => 7,
            'timezone' => 64,
            'source_ctag' => 255,
        ];
    }

    public static function getOrder()
    {
        return 'title ASC, id ASC';
    }

    public static function isStorageAvailable()
    {
        return self::tableExists(self::tableName());
    }

    public static function normalizeColor($value)
    {
        $value = trim((string)$value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value) !== 1) {
            return '#0f766e';
        }

        return strtolower($value);
    }

    public function markSyncResult($success, $message = '', $sourceCtag = null)
    {
        $this->set('last_sync_at', new \DateTimeImmutable('now'));
        $this->set('last_sync_error', $success ? null : trim((string)$message));
        if ($sourceCtag !== null) {
            $this->set('source_ctag', trim((string)$sourceCtag) ?: null);
        }
        $this->set('updated_at', new \DateTimeImmutable('now'));
        $result = $this->save();
        return is_array($result) && !empty($result['status']);
    }
}
