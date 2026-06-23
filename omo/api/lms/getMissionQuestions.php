<?php
require_once __DIR__ . '/bootstrap.php';

$mission_id = (int)($_GET['mission_id'] ?? 0);
$questions = \dbObject\Question::fetchQuestionsForMission($mission_id);

echo json_encode($questions ?: []);
