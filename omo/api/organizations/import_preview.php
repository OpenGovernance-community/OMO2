<?php
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
if ((int)commonGetCurrentUserId() <= 0) {
    http_response_code(401);
    echo json_encode(['status' => false, 'message' => 'Connexion requise.']);
    exit;
}
try {
    $file = $_FILES['omo1_export_file'] ?? [];
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { throw new RuntimeException('Le fichier n a pas pu etre televerse.'); }
    $archive = \dbObject\OrganizationArchive::read((string)$file['tmp_name']);
    $payload = $archive['payload'];
    // Preview needs structure and module availability, never writes files or data.
    foreach ($payload['modules'] ?? [] as $key => $module) { $payload['modules'][$key]['records'] = []; }
    echo json_encode(['status' => true, 'payload' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['status' => false, 'message' => 'Import invalide : '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
