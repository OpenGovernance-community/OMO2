<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

$_GET['return_to'] = commonNormalizeLocalPath(
	$_GET['return_to'] ?? ($_SERVER['HTTP_REFERER'] ?? '/'),
	'/'
);

require dirname(__DIR__) . '/lms/login_drawer.php';
