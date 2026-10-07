<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/site_update_admin.php';

siteUpdateAdminAssertUploadStorageExcluded([]);
siteUpdateAdminAssertUploadStorageExcluded(['.htaccess' => [], 'img/logo.png' => [], 'img/uploads/example.png' => []]);
foreach (['img/upload', 'img/upload/.htaccess', 'img/upload/user/photo.jpg'] as $path) {
    try {
        siteUpdateAdminAssertUploadStorageExcluded([$path => []]);
        throw new LogicException('Deployment must reject a tracked runtime upload path: ' . $path);
    } catch (RuntimeException $error) {
        if (!str_contains($error->getMessage(), $path)) throw $error;
    }
}
echo "site_update_upload_storage_test: OK\n";
