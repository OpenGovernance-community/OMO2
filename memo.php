<?php
$documentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$accessCode = trim((string)($_GET['pwd'] ?? ''));

if ($documentId > 0 && $accessCode !== '') {
    header('Location: /memo/view.php?id=' . $documentId . '&access_code=' . rawurlencode($accessCode), true, 302);
    exit;
}

if ($documentId > 0) {
    header('Location: /memo/' . $documentId, true, 302);
    exit;
}

header('Location: /memo/', true, 302);
exit;
