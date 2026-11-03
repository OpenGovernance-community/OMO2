<?php

function commonLmsCheckAnswer(int $organizationId): never
{
    header('Content-Type: application/json; charset=UTF-8');
    $respond = static function (int $status, bool $correct = false, string $answer = ''): never {
        http_response_code($status);
        $payload = ['correct' => $correct];
        if ($correct && trim($answer) !== '') $payload['answer'] = $answer;
        echo json_encode($payload);
        exit;
    };
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $respond(405);
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) $respond(400);
    $ids = [];
    foreach (['parcours_id', 'mission_id', 'question_id'] as $field) {
        $ids[$field] = filter_var($data[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($ids[$field] === false) $respond(400);
    }
    $selected = $data['choices'] ?? [];
    if (!is_array($selected)) $respond(400);
    foreach ($selected as $id) {
        if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) $respond(400);
    }
    $access = lmsGetParcoursAccessContext($organizationId, $ids['parcours_id']);
    if (empty($access['exists'])) $respond(404);
    if (empty($access['canView']) || !lmsCanTrackProgress($access)) $respond(empty($access['isLoggedIn']) ? 401 : 403);
    $missionLink = new \dbObject\ParcoursMission();
    $questionLink = new \dbObject\MissionQuestion();
    if (!$missionLink->load([['IDparcours', $ids['parcours_id']], ['IDmission', $ids['mission_id']]])
        || !$questionLink->load([['IDmission', $ids['mission_id']], ['IDquestion', $ids['question_id']]])) {
        $respond(404);
    }
    $correct = \dbObject\QuestionChoice::isSelectionCorrect($selected, $ids['question_id']);
    $question = new \dbObject\Question();
    $answer = $correct && $question->load($ids['question_id']) ? (string)$question->get('answer') : '';
    $respond(200, $correct, $answer);
}
