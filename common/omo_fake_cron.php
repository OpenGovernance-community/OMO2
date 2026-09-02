<?php
require_once __DIR__ . '/faq_fake_cron.php';
require_once __DIR__ . '/stats_ethercalc_sync.php';
require_once __DIR__ . '/stats_spreadsheet_sync.php';
require_once __DIR__ . '/notification_center.php';
require_once __DIR__ . '/omo_cron_log.php';

if (!function_exists('omo_run_fake_cron_maintenance')) {
    function omo_run_fake_cron_maintenance($checklistLimit = 50, $force = false, $source = 'unknown')
    {
        $logContext = omoCronStartMaintenanceLog($source);
        $failedTasks = array();
        $result = [
            'calDavCacheEntriesDeleted' => 0,
            'faqProcessed' => 0,
            'checklistProjectsCreated' => 0,
            'checklistRecurringProjectsCreated' => 0,
            'checklistRunsCompleted' => 0,
            'ethercalcIndicatorsSynced' => 0,
            'spreadsheetIndicatorsSynced' => 0,
            'decisionNotificationsProcessed' => 0,
            'eventNotificationsProcessed' => 0,
        ];

        $runTask = static function ($name, $errorPrefix, callable $callback) use (&$failedTasks) {
            try {
                return (int)$callback();
            } catch (\Throwable $exception) {
                $failedTasks[] = (string)$name;
                error_log((string)$errorPrefix . $exception->getMessage());
                return 0;
            }
        };

        $result['calDavCacheEntriesDeleted'] = $runTask(
            'caldav_cache_cleanup',
            'OMO fake cron CalDAV cache cleanup failed: ',
            static function () {
                return \dbObject\CalDavCache::cleanup();
            }
        );

        $result['faqProcessed'] = $runTask(
            'faq_reliability',
            'OMO fake cron FAQ maintenance failed: ',
            static function () {
                return faq_maybe_run_fake_cron();
            }
        );
        $result['checklistProjectsCreated'] = $runTask(
            'checklist_activation',
            'OMO fake cron timed checklist maintenance failed: ',
            static function () use ($checklistLimit) {
                return \dbObject\ChecklistRunItem::activatePendingBatch($checklistLimit);
            }
        );
        $result['checklistRecurringProjectsCreated'] = $runTask(
            'checklist_recurrence',
            'OMO fake cron recurring checklist maintenance failed: ',
            static function () use ($checklistLimit) {
                return \dbObject\ChecklistItemRecurrence::activateDueBatch($checklistLimit);
            }
        );
        $result['checklistProjectsCreated'] += $result['checklistRecurringProjectsCreated'];
        $result['checklistRunsCompleted'] = $runTask(
            'checklist_completion',
            'OMO fake cron checklist status synchronization failed: ',
            static function () {
                return \dbObject\ChecklistRun::syncRunningBatch(100);
            }
        );
        $result['ethercalcIndicatorsSynced'] = $runTask(
            'ethercalc_indicators',
            'OMO fake cron EtherCalc indicator synchronization failed: ',
            static function () {
                return omoStatsMaybeSynchronizeEthercalcIndicators(20);
            }
        );
        $result['spreadsheetIndicatorsSynced'] = $runTask(
            'spreadsheet_indicators',
            'OMO fake cron spreadsheet indicator synchronization failed: ',
            static function () {
                return omoStatsMaybeSynchronizeSpreadsheetIndicators(20);
            }
        );
        $result['decisionNotificationsProcessed'] = $runTask(
            'decision_notifications',
            'OMO fake cron decision notification maintenance failed: ',
            static function () use ($force) {
                return notificationCenterMaybeProcessDecisionLifecycle(200, $force);
            }
        );
        $result['eventNotificationsProcessed'] = $runTask(
            'event_notifications',
            'OMO fake cron event notification maintenance failed: ',
            static function () use ($force) {
                return notificationCenterMaybeProcessEventLifecycle(200, $force);
            }
        );

        omoCronFinishMaintenanceLog($logContext, $failedTasks);

        return $result;
    }
}
