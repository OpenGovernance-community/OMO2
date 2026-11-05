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

    public static function getSupporterGalleryItems(string $campaignId = ''): array
    {
        $items = [];
        foreach (static::loadSupporterGalleryConnections(4000, $campaignId) as $connection) {
            $organization = new Organization();
            if (!$organization->load((int)$connection->get('IDorganization'))) continue;
            $name = trim((string)$organization->get('name'));
            $url = trim((string)$organization->get('url'));
            $description = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$organization->get('description'))) ?? '');
            if (mb_strlen($description, 'UTF-8') > 240) $description = rtrim(mb_substr($description, 0, 237, 'UTF-8')) . '...';
            $items[] = [
                'displayName' => $name,
                'photoUrl' => trim((string)$organization->get('logo')),
                'initials' => static::buildContributorInitials($name),
                'description' => $description,
                'url' => Organization::isValidInformationUrl($url) ? $url : '',
                'amountCents' => (int)$connection->get('currently_entitled_amount_cents'),
            ];
        }
        return $items;
    }
}
