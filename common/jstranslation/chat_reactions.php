<?php
require_once dirname(__DIR__, 2) . '/omo/api/bootstrap.php';
require_once dirname(__DIR__) . '/chat/reactions.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store');
echo json_encode(commonChatReactionLabels(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
