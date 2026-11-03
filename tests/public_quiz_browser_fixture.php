<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Parcours, Mission, ParcoursMission, Question, MissionQuestion, QuestionChoice, UserMission};
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local fixture only');
$items = [];
try {
    $items['org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Public quiz fixture']);
    foreach (['public' => 1, 'private' => 0] as $key => $public) {
        $items[$key . '_parcours'] = mcpFixture(Parcours::class, ['title' => $key . ' quiz', 'IDorganization' => $items['org']->getId(), 'ispublic' => $public]);
        $items[$key . '_mission'] = mcpFixture(Mission::class, ['title' => $key . ' mission', 'resume' => 'Quiz test summary', 'html' => '<p>Quiz test content</p>']);
        $items[$key . '_link'] = mcpFixture(ParcoursMission::class, ['IDparcours' => $items[$key . '_parcours']->getId(), 'IDmission' => $items[$key . '_mission']->getId()]);
    }
    $items['public_attachment'] = mcpFixture(\dbObject\OrganizationParcours::class, ['IDorganization' => $items['org']->getId(), 'IDparcours' => $items['public_parcours']->getId(), 'everybody' => 1, 'anonymous' => 1]);
    $questions = [];
    foreach (['single' => ['Right' => 1, 'Wrong' => 0], 'multiple' => ['First' => 1, 'Second' => 1, 'Wrong' => 0], 'private' => ['Private right' => 1]] as $key => $choices) {
        $explanation = $key === 'single' ? "First educational explanation.\nLiteral <strong>text</strong>." : "Final educational explanation.\n" . str_repeat("More details to understand the answer.\n", 30);
        $items[$key . '_question'] = mcpFixture(Question::class, ['question' => 'Quiz ' . $key, 'answer' => $explanation]);
        $items[$key . '_question_link'] = mcpFixture(MissionQuestion::class, ['IDmission' => $items[$key === 'private' ? 'private_mission' : 'public_mission']->getId(), 'IDquestion' => $items[$key . '_question']->getId(), 'position' => count($questions) + 1]);
        $questions[$key] = ['id' => (int)$items[$key . '_question']->getId(), 'answer' => $explanation, 'correct' => [], 'wrong' => []];
        foreach ($choices as $label => $correct) {
            $itemKey = $key . '_choice_' . count($items);
            $items[$itemKey] = mcpFixture(QuestionChoice::class, ['IDquestion' => $items[$key . '_question']->getId(), 'label' => $label, 'is_correct' => $correct]);
            $questions[$key][$correct ? 'correct' : 'wrong'][] = (int)$items[$itemKey]->getId();
        }
    }
    echo json_encode(['origin' => omoMcpIssuer(), 'oid' => (int)$items['org']->getId(), 'pid' => (int)$items['public_parcours']->getId(), 'mid' => (int)$items['public_mission']->getId(),
        'privatePid' => (int)$items['private_parcours']->getId(), 'privateMid' => (int)$items['private_mission']->getId(), 'questions' => $questions]) . "\n";
    fflush(STDOUT);
    session_write_close();
    fgets(STDIN);
    mcpCheck(UserMission::countDoneForUserAndParcours(0, (int)$items['public_parcours']->getId()) === 0, 'Anonymous quiz completion never creates a server-side learner record.');
} finally {
    mcpCleanup($items);
}
