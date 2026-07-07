<?php
require_once __DIR__ . '/bootstrap.php';

commonRestoreRememberedUser();
include __DIR__ . '/inc/org.php';
require_once __DIR__ . '/inc/access.php';

header('Content-Type: application/json; charset=utf-8');

$currentUserId = (int)commonGetCurrentUserId();
$organizationId = (int)($org['id'] ?? 0);
$parcoursId = (int)($_POST['pid'] ?? 0);
$missionId = (int)($_POST['mid'] ?? 0);
$questionId = (int)($_POST['id'] ?? 0);
$managementContext = lmsResolveParcoursManagementContext($organizationId, $parcoursId, $currentUserId, false);
$hasOrganizationAccess = !empty($managementContext['hasOrganizationAccess']);

if ($currentUserId <= 0 || !$hasOrganizationAccess || $organizationId <= 0) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Acces refuse.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$parcoursMission = new \dbObject\ParcoursMission();
if (($managementContext['link'] ?? null) === null || !$parcoursMission->load([
    ['IDparcours', $parcoursId],
    ['IDmission', $missionId],
])) {
    http_response_code(404);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Mission introuvable.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($managementContext['canEditContent'])) {
    http_response_code(403);
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Vous n avez pas le droit de modifier ce parcours.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rawChoices = isset($_POST['choices']) && is_array($_POST['choices']) ? $_POST['choices'] : array();
$choices = array();
$correctChoiceCount = 0;

foreach ($rawChoices as $choiceData) {
    $label = trim((string)($choiceData['label'] ?? ''));
    if ($label === '') {
        continue;
    }

    $isCorrect = !empty($choiceData['is_correct']);
    if ($isCorrect) {
        $correctChoiceCount++;
    }

    $choices[] = array(
        'label' => $label,
        'is_correct' => $isCorrect,
    );
}

if (count($choices) < 2) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Ajoutez au moins deux choix de reponse.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($correctChoiceCount <= 0) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'Indiquez au moins une bonne reponse.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$question = new \dbObject\Question();
$isEditMode = false;

if ($questionId > 0) {
    $missionQuestion = new \dbObject\MissionQuestion();
    if (!$missionQuestion->load([
        ['IDmission', $missionId],
        ['IDquestion', $questionId],
    ]) || !$question->load($questionId)) {
        http_response_code(404);
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => 'Question introuvable.',
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $isEditMode = true;
}

$question->loadFromArray($_POST);
if (!$isEditMode) {
    $question->set('displayorder', \dbObject\Question::getNextDisplayOrder());
}
$question->set('isactive', true);

if (trim((string)$question->get('question')) === '') {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'La question est obligatoire.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (trim((string)$question->get('answer')) === '') {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => 'La reponse est obligatoire.',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$saveResult = $question->save();
if (!is_array($saveResult) || empty($saveResult['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
            'message' => is_array($saveResult) && !empty($saveResult['text'])
                ? (string)$saveResult['text']
            : ($isEditMode ? 'Impossible de mettre a jour cette question.' : 'Impossible de creer cette question.'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$choicesResult = \dbObject\QuestionChoice::replaceForQuestion((int)$question->getId(), $choices);
if (!is_array($choicesResult) || empty($choicesResult['status'])) {
    echo json_encode(array(
        'status' => false,
        'success' => false,
        'message' => is_array($choicesResult) && !empty($choicesResult['message'])
            ? (string)$choicesResult['message']
            : 'Impossible d enregistrer les choix de cette question.',
        'id' => (int)$question->getId(),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$isEditMode) {
    $attachResult = \dbObject\MissionQuestion::attachQuestionToMission($missionId, (int)$question->getId());
    if (!is_array($attachResult) || empty($attachResult['status'])) {
        echo json_encode(array(
            'status' => false,
            'success' => false,
            'message' => is_array($attachResult) && !empty($attachResult['message'])
                ? (string)$attachResult['message']
                : 'La question a ete creee mais pas liee a la mission.',
            'id' => (int)$question->getId(),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

echo json_encode(array(
    'status' => true,
    'success' => true,
    'message' => $isEditMode ? 'Question mise a jour.' : 'Question creee.',
    'id' => (int)$question->getId(),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
