<?php
require_once __DIR__ . '/faq_fake_cron.php';
require_once __DIR__ . '/stats_ethercalc_sync.php';

if (!function_exists('omo_run_fake_cron_maintenance')) {
    function omo_run_fake_cron_maintenance($checklistLimit = 50)
    {
        $result = [
            'faqProcessed' => 0,
            'checklistProjectsCreated' => 0,
            'checklistRecurringProjectsCreated' => 0,
            'checklistRunsCompleted' => 0,
            'ethercalcIndicatorsSynced' => 0,
        ];

        try {
            $result['faqProcessed'] = (int)faq_maybe_run_fake_cron();
        } catch (\Throwable $exception) {
            error_log('OMO fake cron FAQ maintenance failed: ' . $exception->getMessage());
        }

        try {
            $result['checklistProjectsCreated'] = (int)\dbObject\ChecklistRunItem::activatePendingBatch($checklistLimit);
        } catch (\Throwable $exception) {
            error_log('OMO fake cron timed checklist maintenance failed: ' . $exception->getMessage());
        }
        try {
            $result['checklistRecurringProjectsCreated'] = (int)\dbObject\ChecklistItemRecurrence::activateDueBatch($checklistLimit);
            $result['checklistProjectsCreated'] += $result['checklistRecurringProjectsCreated'];
        } catch (\Throwable $exception) {
            error_log('OMO fake cron recurring checklist maintenance failed: ' . $exception->getMessage());
        }
        try {
            $result['checklistRunsCompleted'] = (int)\dbObject\ChecklistRun::syncRunningBatch(100);
        } catch (\Throwable $exception) {
            error_log('OMO fake cron checklist status synchronization failed: ' . $exception->getMessage());
        }
        try {
            $result['ethercalcIndicatorsSynced'] = (int)omoStatsMaybeSynchronizeEthercalcIndicators(20);
        } catch (\Throwable $exception) {
            error_log('OMO fake cron EtherCalc indicator synchronization failed: ' . $exception->getMessage());
        }

        return $result;
    }
}
