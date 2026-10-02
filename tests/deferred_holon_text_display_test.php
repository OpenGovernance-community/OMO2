<?php
declare(strict_types=1);

// Isolated display decoration: no database or application bootstrap needed.
foreach (['dbobject', 'organization', 'propertyformat', 'deferredproposal'] as $class) {
    require_once dirname(__DIR__) . '/class/dbobject/' . $class . '.class.php';
}

use dbObject\DeferredProposal;
use dbObject\Organization;

function checkHolonTextDisplay(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$organization = new Organization();
foreach ([1 => ['Inherited text', 'Local text'], 5 => ['<p>Inherited HTML</p>', '<p>Local HTML</p>'],
    6 => ['{"text":"Inherited title","detail":"<p>Inherited detail</p>"}', '{"text":"Local title","detail":"<p>Local detail</p>"}']] as $formatId => [$inherited, $local]) {
    foreach ([false, true] as $locked) {
        foreach ([$local, ''] as $value) {
            $state = ['editor_payload' => ['properties' => [[
                'name' => 'Text property', 'formatId' => $formatId,
                'value' => $value, 'inheritedValue' => $inherited, 'effectiveLocked' => $locked,
            ]]]];
            $display = DeferredProposal::decorateHolonListDisplayState($state, $organization);
            $property = $display['editor_payload']['properties'][0];
            checkHolonTextDisplay($property['localValue'] === ($locked ? '' : $value), 'Keep the local text separately, respecting inherited locks.');
            checkHolonTextDisplay($property['inheritedValue'] === $inherited, 'Keep the inherited text separately.');
            checkHolonTextDisplay($property['value'] === ($locked || $value === '' ? $inherited : $local), 'Keep existing effective value semantics.');
            checkHolonTextDisplay(!array_key_exists('localValue', $state['editor_payload']['properties'][0]), 'Display metadata must not mutate the original proposal.');
        }
    }
}
echo "deferred_holon_text_display_test: OK\n";
