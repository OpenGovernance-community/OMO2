<?php
require_once __DIR__ . '/bootstrap.php';
commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once dirname(__DIR__, 3) . '/common/lms/check_answer.php';

commonLmsCheckAnswer((int)$org['id']);
