<?php
require_once __DIR__ . '/bootstrap.php';

$data = json_decode(file_get_contents("php://input"), true);
$selected = $data['choices'] ?? [];

if (empty($selected) && isset($_POST['choice_id'])) {
    $selected = [(int)$_POST['choice_id']];
}

if (empty($selected)) {
    echo json_encode(['correct' => false]);
    exit;
}

$isCorrect = \dbObject\QuestionChoice::isSelectionCorrect($selected);

echo json_encode(['correct' => $isCorrect]);
