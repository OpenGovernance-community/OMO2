<?php
namespace dbObject;

/** Organization connection reuses the same OAuth/profile format as personal connections. */
class OrganizationPatreon extends UserPatreon
{
    protected static $storageAvailable = null;
    public static function tableName() { return 'organization_patreon'; }
    public static function rules()
    {
        $rules = parent::rules();
        $rules[0][0] = array_values(array_diff($rules[0][0], ['IDuser']));
        $rules[] = [['IDorganization'], 'fk'];
        return $rules;
    }
    public static function attributeLabels() { return parent::attributeLabels() + ['IDorganization' => 'Organisation']; }
    public static function findByOrganizationId(int $organizationId): ?self
    {
        if ($organizationId <= 0 || !static::isStorageAvailable()) return null;
        $item = new self();
        return $item->load(['IDorganization', $organizationId]) ? $item : null;
    }
    public static function loadOrCreateByOrganizationId(int $organizationId): self
    {
        $item = self::findByOrganizationId($organizationId) ?? new self();
        $item->set('IDorganization', $organizationId);
        return $item;
    }
    public static function loadActiveConnections($organizationId = 0)
    {
        if (!static::isStorageAvailable()) return [];
        $rows = self::fetchAll('SELECT id FROM organization_patreon WHERE is_connected = 1'
            . ((int)$organizationId > 0 ? ' AND IDorganization = :id' : ''), (int)$organizationId > 0 ? ['id' => (int)$organizationId] : []);
        $items = [];
        foreach ($rows ?: [] as $row) { $item = new self(); if ($item->load((int)$row['id'])) $items[] = $item; }
        return $items;
    }
}
