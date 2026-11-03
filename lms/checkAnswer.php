<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';
require_once dirname(__DIR__) . '/common/lms/check_answer.php';

commonLmsCheckAnswer((int)$org['id']);
