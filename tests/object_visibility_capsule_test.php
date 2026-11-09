<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/common/object_visibility_selector.php';
$read = ['type' => 'organization', 'badgeText' => 'Organisation <test>'];
$edit = ['type' => 'self', 'badgeText' => 'Propriétaire "test"'];
$html = commonRenderObjectVisibilityCapsule($read, $edit);
$documentHtml = commonRenderObjectVisibilityCapsule($read, $edit, 'omo-documents__scope-capsule');
if (substr_count($html, '<img ') !== 2 || !str_contains($html, '/organization.png') || !str_contains($html, '/me.png')
    || !str_contains($html, 'generic-visibility-capsule__separator') || str_contains($html, 'Organisation <test>')
    || !str_contains($html, 'Voir: Organisation &lt;test&gt; | Éditer: Propriétaire &quot;test&quot;')
    || str_replace(' omo-documents__scope-capsule', '', $documentHtml) !== $html) {
    throw new RuntimeException('Documents and decisions must share the two-icon capsule with escaped, accessible labels.');
}
if (($argv[1] ?? '') === '--fixtures') {
    echo json_encode(['decision' => $html, 'document' => $documentHtml], JSON_THROW_ON_ERROR);
} else {
    echo "object_visibility_capsule_test: OK\n";
}
