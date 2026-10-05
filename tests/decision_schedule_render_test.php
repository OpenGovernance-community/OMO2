<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';

use dbObject\DecisionProcess;

function scheduleExpect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function scheduleDom(string $html): DOMXPath
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<meta charset="utf-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return new DOMXPath($document);
}

$escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
foreach (['vote' => 'Vote', 'majority_judgment' => 'MajorityJudgment', 'consent' => 'Consent'] as $method => $function) {
    require_once dirname(__DIR__) . '/omo/api/decision/modules/' . $method . '/module.php';
    $source = 'omoDecision' . $function . 'ModuleGetSourceLang';
    $render = 'omoDecision' . $function . 'ModuleRender';
    ob_start();
    $render([
        'context' => ['intent' => 'manage', 'canManage' => true, 'organizationId' => 0, 'targetHolonId' => 0],
        'decision' => null, 'decisionGroup' => null, 'includeAssets' => false,
        'lang' => [], 'sourceLang' => $source(), 'escape' => $escape,
    ]);
    $xpath = scheduleDom(ob_get_clean());
    scheduleExpect($xpath->query('//*[@data-omo-decision-schedule-phase]')->length === 2, $method . ': both scheduling phases are rendered.');
    scheduleExpect($xpath->query('//*[@data-omo-decision-schedule-phase]//input[@data-generic-accordion-toggle and @checked]')->length === 0, $method . ': new schedules start unchecked.');
    scheduleExpect($xpath->query('//select[@name="status" or @name="visibility_type"][not(ancestor::*[@data-generic-accordion])]')->length === 2, $method . ': status and visibility stay outside scheduling.');
    scheduleExpect($xpath->query('//*[@data-omo-decision-schedule-phase]//details/summary[@aria-label]')->length === 2, $method . ': both phase checkboxes have explanatory help.');
    scheduleExpect($xpath->query('//*[@data-omo-decision-schedule-phase]//input[@type="datetime-local"]')->length === 4, $method . ': existing date save fields are rendered.');
}

$decision = new DecisionProcess();
$decision->set('consultation_end_at', new DateTimeImmutable('2026-10-09 18:00:00'));
$decision->set('evaluation_start_at', new DateTimeImmutable('2026-10-10 10:00:00'));
$xpath = scheduleDom(omoDecisionRenderProcessSchedule($decision, false, true, [], omoDecisionScheduleGetSourceLang(), $escape));
scheduleExpect($xpath->query('//input[@data-generic-accordion-toggle and @checked]')->length === 2, 'A date at either boundary opens the existing phase.');
scheduleExpect($xpath->query('//input[@name="evaluation_start_at" and @value="2026-10-10T10:00" and @disabled]')->length === 1, 'Existing values and start-date locks are preserved.');
scheduleExpect($xpath->query('//input[@name="evaluation_end_at" and not(@disabled)]')->length === 1, 'The end date stays editable when the start date is locked.');
$xpath = scheduleDom(omoDecisionRenderProcessSchedule($decision, false, true, [], omoDecisionScheduleGetSourceLang(), $escape, true, true));
scheduleExpect($xpath->query('//*[@data-omo-decision-schedule-phase="evaluation"]')->length === 0, 'Elaboration-only processes do not expose an evaluation phase.');
scheduleExpect($xpath->query('//input[@name="consultation_start_at" and @readonly and not(@disabled)]')->length === 1, 'Read-only start dates retain their existing submission behavior.');
echo "Decision schedule rendering: passed.\n";
