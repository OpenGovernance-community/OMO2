<?php

declare(strict_types=1);

function assertDocumentPdfPreview(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$documentSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/document.class.php');
$detailSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/detail.php');

assertDocumentPdfPreview(
    str_contains($documentSource, 'public function isStoredPdfFile(): bool'),
    'Uploaded documents must expose PDF detection through their dbObject.'
);
assertDocumentPdfPreview(
    str_contains($documentSource, 'public function buildStoredFileDownloadUrl(')
        && str_contains($documentSource, "\$url .= '/' . rawurlencode(\$previewFilename)"),
    'The PDF preview URL must end with the original PDF filename for browser PDF viewers.'
);
assertDocumentPdfPreview(
    str_contains($documentSource, 'class="omo-document-file__pdf-frame"'),
    'Uploaded PDFs must be rendered in an iframe.'
);
assertDocumentPdfPreview(
    str_contains($documentSource, 'if ($isStoredPdf) {')
        && !str_contains($documentSource, "} elseif (\$isStoredPdf) {"),
    'PDF previews must not be wrapped in the generic file card with its title and metadata.'
);
assertDocumentPdfPreview(
    str_contains($detailSource, '$document->isStoredPdfFile()')
        && str_contains($detailSource, "documents.detail.action.download")
        && str_contains($detailSource, 'download="<?= $escape($uploadedPdfDownloadName) ?>"')
        && str_contains($detailSource, 'buildStoredFileDownloadUrl(false, $organizationId, $holonId)')
        && str_contains($detailSource, 'omo-document-detail__preview-actions'),
    'The detail drawer must show a dedicated download action for uploaded PDFs.'
);
assertDocumentPdfPreview(
    str_contains($detailSource, 'omo-document-file__pdf-frame:fullscreen')
        && str_contains($detailSource, 'data-omo-document-fullscreen'),
    'PDF previews must support the same fullscreen action as collaborative document frames.'
);

echo "document_pdf_preview_test: OK\n";
