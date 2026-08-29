<?php

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/common/spacedeck.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

echo json_encode(omoSpacedeckBuildExternalAccessResponse($_SERVER), JSON_UNESCAPED_SLASHES);
