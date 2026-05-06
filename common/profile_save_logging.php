<?php

function commonGetProfileSaveLogPath()
{
	$baseDir = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__)), '/\\');
	if ($baseDir === '') {
		$baseDir = dirname(__DIR__);
	}

	return $baseDir . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'profile-save-errors.log';
}

function commonLogProfileSaveFailure($scope, $message, array $context = array())
{
	$logPath = commonGetProfileSaveLogPath();
	$logDir = dirname($logPath);
	if (!is_dir($logDir)) {
		@mkdir($logDir, 0777, true);
	}

	$payload = array(
		'time' => date('c'),
		'scope' => (string)$scope,
		'message' => (string)$message,
		'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
		'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
		'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
		'current_user' => (int)($_SESSION['currentUser'] ?? 0),
		'current_organization' => (int)($_SESSION['currentOrganization'] ?? 0),
		'post_keys' => array_values(array_keys($_POST)),
		'context' => $context,
	);

	$line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if (!is_string($line) || $line === '') {
		$line = '{"time":"' . date('c') . '","scope":"' . addslashes((string)$scope) . '","message":"json_encode_failed"}';
	}

	error_log($line . PHP_EOL, 3, $logPath);
}

?>
