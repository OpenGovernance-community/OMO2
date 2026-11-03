<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';

$items = mcpFixtures(false);
try {
    $ids = [];
    foreach (['empty', 'video', 'homework', 'quiz', 'pack'] as $kind) {
        $items[$kind] = mcpFixture(\dbObject\Parcours::class, [
            'title' => 'Indicators ' . $kind, 'IDorganization' => $items['org']->getId(),
            'ispublic' => 1, 'ispack' => $kind === 'pack' ? 1 : 0,
        ]);
        $ids[$kind] = (int)$items[$kind]->getId();
        $items[$kind . '_attachment'] = mcpFixture(\dbObject\OrganizationParcours::class, [
            'IDorganization' => $items['org']->getId(), 'IDparcours' => $ids[$kind], 'everybody' => 1, 'anonymous' => 1,
        ]);
    }
    foreach (['empty', 'video', 'homework', 'quiz'] as $kind) {
        $items[$kind . '_mission'] = mcpFixture(\dbObject\Mission::class, [
            'title' => 'Indicators ' . $kind, 'resume' => 'Content indicators test',
            'video' => $kind === 'video' ? 'https://youtu.be/example' : '   ',
        ]);
        $items[$kind . '_mission_link'] = mcpFixture(\dbObject\ParcoursMission::class, [
            'IDparcours' => $ids[$kind], 'IDmission' => $items[$kind . '_mission']->getId(),
        ]);
        if ($kind !== 'empty') {
            $items[$kind . '_pack_link'] = mcpFixture(\dbObject\ParcoursParcours::class, [
                'IDparcours_parent' => $ids['pack'], 'IDparcours_child' => $ids[$kind],
            ]);
        }
    }
    $items['homework_object'] = mcpFixture(\dbObject\Homework::class, ['title' => 'Indicators homework']);
    $items['homework_link'] = mcpFixture(\dbObject\MissionHomework::class, [
        'IDmission' => $items['homework_mission']->getId(), 'IDhomework' => $items['homework_object']->getId(),
    ]);
    $items['question'] = mcpFixture(\dbObject\Question::class, ['question' => 'Indicators question?', 'answer' => 'Explanation']);
    $items['question_link'] = mcpFixture(\dbObject\MissionQuestion::class, [
        'IDmission' => $items['quiz_mission']->getId(), 'IDquestion' => $items['question']->getId(),
    ]);

    $indicators = \dbObject\Parcours::fetchContentIndicators([...array_values($ids), $ids['video'], 0, -1]);
    mcpCheck(count($indicators) === count($ids), 'Duplicate and invalid IDs must be excluded');
    foreach ($ids as $kind => $id) {
        foreach (['video', 'homework', 'quiz'] as $feature) {
            mcpCheck($indicators[$id][$feature] === ($kind === $feature || $kind === 'pack'), $kind . ': incorrect ' . $feature . ' indicator');
        }
    }
    mcpCheck(\dbObject\Parcours::fetchContentIndicators([]) === [], 'Empty input');
    mcpCheck(\dbObject\Parcours::fetchContentIndicators([$ids['pack']])[$ids['pack']] === $indicators[$ids['pack']], 'Pack must include children absent from the requested IDs');
    // Removing the mission link must clear the indicator even if the question still exists.
    $items['question_link']->delete();
    mcpCheck(!\dbObject\Parcours::fetchContentIndicators([$ids['quiz']])[$ids['quiz']]['quiz'], 'Removed quiz link must clear its indicator');
    $items['question_link'] = mcpFixture(\dbObject\MissionQuestion::class, [
        'IDmission' => $items['quiz_mission']->getId(), 'IDquestion' => $items['question']->getId(),
    ]);
    if (in_array('--browser', $argv, true)) {
        mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Local browser fixture only');
        echo json_encode(['origin' => omoMcpIssuer(), 'oid' => (int)$items['org']->getId(), 'ids' => $ids,
            'email' => $items['user']->get('email'), 'password' => $items['password']]) . "\n";
        fflush(STDOUT);
        session_write_close();
        fgets(STDIN);
    } else {
        echo "Parcours content indicators OK\n";
    }
} finally {
    mcpCleanup($items);
}
