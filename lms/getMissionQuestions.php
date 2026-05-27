<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/shared_functions.php';

$mission_id = (int)($_GET['mission_id'] ?? 0);
$questions = \dbObject\Question::fetchQuestionsForMission($mission_id);

echo json_encode($questions ?: []);
