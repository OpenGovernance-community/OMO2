<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{OrganizationalMaturityAssessment, Organization};
$items = mcpFixtures(); $assessments = [];
try {
    $oid = (int)$items['org']->getId();
    $items['other'] = mcpFixture(McpTestOrganization::class, ['name' => 'Other survey fixture']);
    $tomorrow = [1, 2, 3, 3, 3, 4, 5, 3];
    for ($index = 0; $index < 10; $index++) {
        $answers = [];
        for ($principle = 1; $principle <= 10; $principle++) {
            $answers[] = ['affinity' => $principle === 1 ? ($index < 4 ? 1 : 5) : 4,
                'situation' => ['today' => $principle === 1 ? 3 : 2, 'tomorrow' => $principle === 1 ? ($tomorrow[$index] ?? 5) : 4]];
        }
        $created = OrganizationalMaturityAssessment::createFromAnswers($answers);
        mcpCheck(is_array($created), 'Create complete assessment fixture');
        $assessment = $created['assessment']; $assessments[] = $assessment;
        $assessment->set('IDorganization', $index === 9 ? $items['other']->getId() : $oid);
        if ($index === 8) $assessment->set('completed_at', null);
        mcpCheck(!empty($assessment->save()['status']), 'Associate assessment with organization');
    }
    $aggregate = OrganizationalMaturityAssessment::getOrganizationAggregate($oid);
    $first = $aggregate['principles'][1];
    mcpCheck($first['responseCount'] === 8, 'Only completed assessments from this organization are counted');
    mcpCheck($first['todayDistribution'] === [0, 0, 8, 0, 0] && $first['todayAgreement'] === 'aligned', 'Aligned ratings are concentrated at one level');
    mcpCheck($first['tomorrowDistribution'] === [1, 1, 4, 1, 1] && $first['tomorrowAgreement'] === 'dispersed', 'Dispersed ratings retain all five counts');
    mcpCheck($first['affinityDistribution'] === [4, 0, 0, 0, 4] && $first['affinityAgreement'] === 'polarized', 'Opposing poles are represented by the actual marginal counts');
    foreach ($aggregate['principles'] as $principle) foreach (['today', 'tomorrow', 'affinity'] as $dimension) {
        mcpCheck(array_sum($principle[$dimension . 'Distribution']) === $principle['responseCount'], 'Histogram total matches the mean and convergence sample');
    }
    $summary = OrganizationalMaturityAssessment::getOrganizationScoreSummaries([$oid])[$oid];
    mcpCheck($summary['todayAverage'] === 2.1 && $summary['tomorrowAverage'] === 3.9, 'The shared aggregate query preserves existing organization score summaries');
    $html = (static function (int $organizationId): string {
        $_GET = ['id' => $organizationId];
        ob_start(); require dirname(__DIR__) . '/survey/organization.php'; return ob_get_clean();
    })($oid);
    preg_match('/window\.SURVEY_PUBLIC_RESULT = (.+);/', $html, $match);
    $questions = json_decode($match[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(count($questions) === 10 && $questions[0]['distributions']['affinity'] === [4, 0, 0, 0, 4], 'The public report exposes aggregate histograms');
    mcpCheck(count($questions[0]['levels']) === 5 && !str_contains(json_encode($questions), 'privateToken') && !str_contains(json_encode($questions), 'IDuser'), 'Only aggregate counts and scale labels reach the chart');
    mcpCheck(substr_count($html, 'id="surveyDistributionDialog"') === 1, 'The report has one shared distribution dialog');
    file_put_contents(dirname(__DIR__) . '/tmp/survey-distribution-report.html', $html);
    echo "survey_distribution_test: OK\n";
} finally {
    foreach (array_reverse($assessments) as $assessment) $assessment->delete();
    mcpCleanup($items);
}
