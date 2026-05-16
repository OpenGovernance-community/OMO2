<?php
namespace dbObject;

class TranslationBundleRefreshJob extends DbObject
{
    public static function tableName()
    {
        return 'translation_bundle_refresh_jobs';
    }

    public static function rules()
    {
        return [
            [['bundle_key', 'locale', 'source_hash', 'source_json', 'status'], 'required'],
            [['bundle_key', 'locale', 'source_hash', 'status'], 'string'],
            [['source_json', 'last_error'], 'text'],
            [['attempts'], 'integer'],
            [['created_at', 'updated_at', 'started_at', 'finished_at'], 'datetime'],
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
            'source_json' => 'Source JSON',
            'status' => 'Status',
            'attempts' => 'Attempts',
            'last_error' => 'Last error',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at',
            'started_at' => 'Started at',
            'finished_at' => 'Finished at',
        ];
    }

    public static function attributeLength()
    {
        return [
            'bundle_key' => 190,
            'locale' => 10,
            'source_hash' => 64,
            'status' => 16,
        ];
    }

    public static function getOrder()
    {
        return 'created_at ASC, id ASC';
    }

    public static function findById($jobId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `translation_bundle_refresh_jobs` WHERE `id` = :id LIMIT 1',
            ['id' => (int)$jobId]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $job = new self();
        $job->loadFromArray($row);
        $job->setId((int)$row['id']);
        return $job;
    }

    public static function findByBundleLocaleHash($bundleKey, $locale, $sourceHash)
    {
        $row = self::fetchRow(
            'SELECT * FROM `translation_bundle_refresh_jobs`
             WHERE `bundle_key` = :bundle_key AND `locale` = :locale AND `source_hash` = :source_hash
             LIMIT 1',
            [
                'bundle_key' => (string)$bundleKey,
                'locale' => (string)$locale,
                'source_hash' => (string)$sourceHash,
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $job = new self();
        $job->loadFromArray($row);
        $job->setId((int)$row['id']);
        return $job;
    }

    public static function enqueuePending($bundleKey, $locale, $sourceHash, $sourceJson)
    {
        $existing = self::findByBundleLocaleHash($bundleKey, $locale, $sourceHash);
        if ($existing instanceof self) {
            if ((string)$existing->get('status') === 'failed') {
                self::execute(
                    'UPDATE `translation_bundle_refresh_jobs`
                     SET `status` = :status, `last_error` = NULL, `updated_at` = NOW(), `started_at` = NULL, `finished_at` = NULL
                     WHERE `id` = :id',
                    [
                        'status' => 'pending',
                        'id' => (int)$existing->getId(),
                    ]
                );
                $existing = self::findById((int)$existing->getId());
                return [
                    'job' => $existing,
                    'created' => false,
                    'shouldTrigger' => true,
                ];
            }

            return [
                'job' => $existing,
                'created' => false,
                'shouldTrigger' => false,
            ];
        }

        $created = self::execute(
            'INSERT INTO `translation_bundle_refresh_jobs`
                (`bundle_key`, `locale`, `source_hash`, `source_json`, `status`, `attempts`, `last_error`, `created_at`, `updated_at`, `started_at`, `finished_at`)
             VALUES
                (:bundle_key, :locale, :source_hash, :source_json, :status, 0, NULL, NOW(), NOW(), NULL, NULL)',
            [
                'bundle_key' => (string)$bundleKey,
                'locale' => (string)$locale,
                'source_hash' => (string)$sourceHash,
                'source_json' => (string)$sourceJson,
                'status' => 'pending',
            ]
        );

        if (!$created) {
            $existing = self::findByBundleLocaleHash($bundleKey, $locale, $sourceHash);
            return [
                'job' => $existing instanceof self ? $existing : null,
                'created' => false,
                'shouldTrigger' => false,
            ];
        }

        $job = self::findById((int)self::getDbh()->insert_id);
        return [
            'job' => $job instanceof self ? $job : null,
            'created' => true,
            'shouldTrigger' => true,
        ];
    }

    public static function claimPendingById($jobId)
    {
        $statement = self::prepareAndExecute(
            'UPDATE `translation_bundle_refresh_jobs`
             SET `status` = :status, `attempts` = `attempts` + 1, `last_error` = NULL, `started_at` = NOW(), `updated_at` = NOW(), `finished_at` = NULL
             WHERE `id` = :id AND `status` = :pending_status',
            [
                'status' => 'running',
                'id' => (int)$jobId,
                'pending_status' => 'pending',
            ]
        );

        if ($statement === false) {
            return null;
        }

        $affectedRows = $statement->rowCount();
        $statement->closeCursor();
        if ($affectedRows < 1) {
            return null;
        }

        return self::findById($jobId);
    }

    public static function claimNextPending()
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $row = self::fetchRow(
                'SELECT `id` FROM `translation_bundle_refresh_jobs`
                 WHERE `status` = :status
                 ORDER BY `created_at` ASC, `id` ASC
                 LIMIT 1',
                ['status' => 'pending']
            );

            if (!is_array($row) || empty($row['id'])) {
                return null;
            }

            $job = self::claimPendingById((int)$row['id']);
            if ($job instanceof self) {
                return $job;
            }
        }

        return null;
    }

    public static function markCompleted($jobId)
    {
        return self::execute(
            'UPDATE `translation_bundle_refresh_jobs`
             SET `status` = :status, `last_error` = NULL, `finished_at` = NOW(), `updated_at` = NOW()
             WHERE `id` = :id',
            [
                'status' => 'completed',
                'id' => (int)$jobId,
            ]
        );
    }

    public static function markFailed($jobId, $errorMessage)
    {
        return self::execute(
            'UPDATE `translation_bundle_refresh_jobs`
             SET `status` = :status, `last_error` = :last_error, `finished_at` = NOW(), `updated_at` = NOW()
             WHERE `id` = :id',
            [
                'status' => 'failed',
                'last_error' => trim((string)$errorMessage),
                'id' => (int)$jobId,
            ]
        );
    }
}

?>
