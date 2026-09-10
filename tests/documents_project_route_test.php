<?php

declare(strict_types=1);

function assertDocumentsProjectRoute(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$source = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/index.php');

assertDocumentsProjectRoute(
    preg_match(
        '/requestedDocument\s*&& Number\(requestedDocument\.id \|\| 0\) === targetDocumentId/',
        $source
    ) === 1,
    'A direct document route must use the requested document payload when the document is absent from the current list.'
);
assertDocumentsProjectRoute(
    str_contains($source, 'window.omoOpenDocumentDetailByPayload(documentItem, panel) === true'),
    'A requested project document must continue to open through the document detail drawer.'
);
assertDocumentsProjectRoute(
    str_contains($source, 'nextPanel.__omoDocumentsApplyRouteChange({'),
    'A refreshed Documents drawer must replay the requested document route after its panel is ready.'
);

echo "documents_project_route_test: OK\n";
