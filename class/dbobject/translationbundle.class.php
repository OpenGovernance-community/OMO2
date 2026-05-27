<?php
namespace dbObject;

class TranslationBundle extends DbObject
{
    public static function tableName()
    {
        return 'translation_bundles';
    }

    public static function rules()
    {
        return [
            [['bundle_key', 'locale', 'source_hash', 'translated_json', 'status'], 'required'],
            [['bundle_key', 'locale', 'source_hash', 'status'], 'string'],
            [['translated_json'], 'text'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bundle_key' => 'Bundle key',
            'locale' => 'Locale',
            'source_hash' => 'Source hash',
            'translated_json' => 'Translated JSON',
            'status' => 'Status',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'bundle_key' => 'Unique module or page key for the translation bundle.',
            'locale' => 'Requested locale for this translated bundle.',
            'source_hash' => 'Hash of the current source bundle content.',
            'translated_json' => 'Serialized JSON payload containing translated entries for the bundle.',
            'status' => 'Translation workflow status.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'bundle_key' => 190,
            'locale' => 10,
            'source_hash' => 64,
            'status' => 32,
        ];
    }

    public static function getOrder()
    {
        return 'bundle_key ASC, locale ASC';
    }

    public static function findByBundleAndLocale($bundleKey, $locale)
    {
        $row = self::fetchRow(
            'SELECT * FROM `translation_bundles` WHERE `bundle_key` = :bundle_key AND `locale` = :locale LIMIT 1',
            [
                'bundle_key' => (string)$bundleKey,
                'locale' => (string)$locale,
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $bundle = new self();
        $bundle->loadFromArray($row);
        $bundle->setId((int)$row['id']);
        return $bundle;
    }

    public static function upsertRefreshCandidate($bundleKey, $locale, $sourceHash, $translatedJson, $status = 'outdated')
    {
        $allowedStatuses = ['machine_translated', 'approved', 'outdated'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'outdated';
        }

        return self::execute(
            'INSERT INTO `translation_bundles` (`bundle_key`, `locale`, `source_hash`, `translated_json`, `status`, `created_at`, `updated_at`)
             VALUES (:bundle_key, :locale, :source_hash, :translated_json, :status, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 `source_hash` = VALUES(`source_hash`),
                 `translated_json` = VALUES(`translated_json`),
                 `status` = VALUES(`status`),
                 `updated_at` = NOW()',
            [
                'bundle_key' => (string)$bundleKey,
                'locale' => (string)$locale,
                'source_hash' => (string)$sourceHash,
                'translated_json' => (string)$translatedJson,
                'status' => (string)$status,
            ]
        );
    }

    public static function markOutdatedPreservingTranslations($bundleKey, $locale, $sourceHash)
    {
        return self::execute(
            'INSERT INTO `translation_bundles` (`bundle_key`, `locale`, `source_hash`, `translated_json`, `status`, `created_at`, `updated_at`)
             VALUES (:bundle_key, :locale, :source_hash, :translated_json, :status, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 `source_hash` = VALUES(`source_hash`),
                 `status` = VALUES(`status`),
                 `updated_at` = NOW()',
            [
                'bundle_key' => (string)$bundleKey,
                'locale' => (string)$locale,
                'source_hash' => (string)$sourceHash,
                'translated_json' => '{}',
                'status' => 'outdated',
            ]
        );
    }

    public static function saveTranslatedBundle($bundleKey, $locale, $sourceHash, $translatedJson, $status = 'machine_translated')
    {
        $allowedStatuses = ['machine_translated', 'approved', 'outdated'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'machine_translated';
        }

        return self::execute(
            'INSERT INTO `translation_bundles` (`bundle_key`, `locale`, `source_hash`, `translated_json`, `status`, `created_at`, `updated_at`)
             VALUES (:bundle_key, :locale, :source_hash, :translated_json, :status, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 `source_hash` = VALUES(`source_hash`),
                 `translated_json` = VALUES(`translated_json`),
                 `status` = VALUES(`status`),
                 `updated_at` = NOW()',
            [
                'bundle_key' => (string)$bundleKey,
                'locale' => (string)$locale,
                'source_hash' => (string)$sourceHash,
                'translated_json' => (string)$translatedJson,
                'status' => (string)$status,
            ]
        );
    }
}

?>
