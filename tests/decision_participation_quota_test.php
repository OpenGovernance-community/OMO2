<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/omo/api/decision/params/shared.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';

use dbObject\{Application, Organization, OrganizationApplication, DecisionProcess, DecisionParticipant,
    DecisionProposal, DecisionResponse, DecisionGovernanceAction, DeferredProposal, Rule};

$items = mcpFixtures();
$decisions = [];
$changes = [];
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $hid = (int)$items['root']->getId();
    $items['org']->set('interface_level', Organization::INTERFACE_LEVEL_AUTONOMOUS);
    mcpCheck(!empty($items['org']->save()['status']), 'Enable governance settings');
    $app = new Application();
    mcpCheck($app->load(['hash', 'decision']), 'Decision application exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $setQuota = static function (string $minimum) use ($items): void {
        $result = omoDecisionParamsStoreConfig($items['org'], ['governance' => ['enabled' => true, 'minimum_participation' => $minimum]]);
        mcpCheck(!empty($result['status']), 'Store participation quota');
        mcpCheck(omoDecisionParamsGetStoredConfig($items['org'])['governance']['minimum_participation'] === $minimum, 'Quota persists');
    };
    $setQuota('50%');
    $invalid = omoDecisionParamsStoreConfig($items['org'], ['governance' => ['minimum_participation' => '101%']]);
    mcpCheck(empty($invalid['status']) && omoDecisionParamsGetStoredConfig($items['org'])['governance']['minimum_participation'] === '50%', 'Invalid quota does not overwrite saved settings');

    foreach (['consent', 'simple_vote'] as $method) {
        foreach (['insufficient', 'enough', 'empty', 'objection'] as $scenario) {
            if ($method === 'simple_vote' && $scenario === 'objection') continue;
            $setQuota($scenario === 'empty' ? '' : '50%');
            $decision = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
                'title' => 'Quota ' . $method . ' ' . $scenario, 'evaluation_method' => $method, 'status' => 'results',
                'parameters' => ['workflow_type' => DecisionProcess::WORKFLOW_GOVERNANCE]]);
            $decisions[] = $decision;
            $group = $decision->getPrimaryGroup(true);
            $proposal = mcpFixture(DecisionProposal::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(), 'IDuser_author' => $uid, 'title' => 'Quota proposal', 'active' => 1]);
            $pid = (int)$proposal->getId();
            $participants = $responses = [];
            for ($i = 0; $i < 10; $i++) {
                $participants[] = $i === 0 ? DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $uid)
                    : mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(),
                        'email' => 'quota-' . $i . '@example.invalid', 'role' => 'participant', 'status' => 'invited', 'active' => 1]);
                if ($i >= (in_array($scenario, ['enough', 'objection'], true) ? 5 : 2)) continue;
                $choices = $method === 'consent' ? ['choices' => [$pid => ($scenario === 'objection' && $i === 0 ? 'objection' : ($i === 0 ? 'favor' : 'no_objection'))]] : ['selected_proposal_ids' => [$pid]];
                $responses[] = mcpFixture(DecisionResponse::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(),
                    'IDdecision_participant' => $participants[$i]->getId(), 'status' => 'submitted', 'parameters' => [$method => $choices]]);
            }
            if ($scenario === 'insufficient') {
                $quota = $decision->getGovernanceParticipationQuota($group);
                mcpCheck(!$quota['met'] && $quota['required'] === 5 && $quota['participating'] === 2 && $quota['invited'] === 10, 'Two answers and eight absences fail a 50% quota: ' . json_encode($quota));
                mcpCheck($decision->getGovernanceParticipationQuota($group, [$responses[0], $responses[0]])['participating'] === 1, 'One person cannot count twice');
                $responses[1]->set('status', 'draft'); $responses[1]->save();
                mcpCheck($decision->getGovernanceParticipationQuota($group)['participating'] === 1, 'Draft does not count');
                $responses[1]->set('status', 'invalidated'); $responses[1]->save();
                mcpCheck($decision->getGovernanceParticipationQuota($group)['participating'] === 1, 'Invalidated response does not count');
                $responses[1]->set('status', 'submitted'); $responses[1]->save();
                $participants[1]->set('status', 'declined'); $participants[1]->save();
                $quota = $decision->getGovernanceParticipationQuota($group);
                mcpCheck($quota['participating'] === 1 && $quota['invited'] === 10, 'Declined invitation stays in denominator without counting its old response');
                $participants[1]->set('status', 'revoked'); $participants[1]->save();
                mcpCheck($decision->getGovernanceParticipationQuota($group)['invited'] === 9, 'Revoked invitation is excluded');
                $participants[1]->set('status', 'invited'); $participants[1]->save();
                $setQuota('50.01%');
                mcpCheck($decision->getGovernanceParticipationQuota($group)['required'] === 6, 'Percentage rounds up to a whole person');
                $setQuota('3');
                mcpCheck($decision->getGovernanceParticipationQuota($group)['required'] === 3, 'Absolute quota uses a headcount');
                $setQuota('50%');
                mcpCheck(!$decision->getGovernanceParticipationQuota($group, [])['met'], 'No ballots cannot meet a configured quota');
                $decision->set('results_published_at', '2020-01-01 00:00:00');
                mcpCheck($decision->getGovernanceParticipationQuota($group)['minimum'] === '', 'A newly configured quota does not reassess an already closed decision');
                $decision->set('results_published_at', null);
                $decision->set('parameters', []);
                mcpCheck($decision->getGovernanceParticipationQuota($group)['met'], 'Ordinary decisions are unaffected');
                $decision->set('parameters', ['workflow_type' => DecisionProcess::WORKFLOW_GOVERNANCE]);
            }
            foreach (['legacy', 'deferred'] as $storage) {
                $state = ['IDholon' => $hid, 'title' => 'Quota rule ' . $storage, 'description' => '<p>Quota fixture</p>',
                    'scope' => 'local', 'review_date' => '2090-01-01', 'expiration_date' => '2091-01-01'];
                $fields = ['IDdecision_proposal' => $pid, 'target_type' => 'rule', 'status' => 'pending', 'after_state' => $state];
                $change = $storage === 'legacy'
                    ? mcpFixture(DecisionGovernanceAction::class, $fields + ['action_type' => 'rule.create'])
                    : mcpFixture(DeferredProposal::class, $fields + ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser_author' => $uid, 'operation' => 'create']);
                $changes[] = $change;
            }
            // Normal lifecycle entry point must protect both executors and retain the final counts.
            $decision->syncLifecycleStatus();
            foreach (array_slice($changes, -2) as $change) {
                $change->load((int)$change->getId(), true);
                $expected = in_array($scenario, ['insufficient', 'objection'], true) ? 'rejected' : 'applied';
                mcpCheck($change->get('status') === $expected, $method . '/' . $scenario . ': ' . $change::class . ' must be ' . $expected . ': ' . $change->get('status_message'));
                if ($scenario === 'insufficient') mcpCheck(str_contains((string)$change->get('status_message'), 'Participation minimale non atteinte'), 'Rejection explains insufficient participation');
            }
            $before = $decision->getGovernanceParticipationQuota($group);
            $setQuota('100%');
            $decision->load((int)$decision->getId(), true);
            mcpCheck($decision->getGovernanceParticipationQuota($group) === $before, 'Closed quota survives reload and later settings changes');
            $html = omoDecisionRenderGovernanceParticipationQuota($decision, $group, true, static fn ($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
            mcpCheck($scenario === 'empty' ? $html === '' : str_contains($html, $scenario === 'insufficient' ? 'Aucune modification n’est validée' : 'Participation minimale atteinte'), 'Public result explains the quota without implying acceptance');
            $decision->syncLifecycleStatus();
        }
    }
    echo "decision_participation_quota_test: OK\n";
} finally {
    foreach ($changes as $change) {
        $change->load((int)$change->getId(), true);
        if ($change->get('status') === 'applied' && (int)$change->get('target_id') > 0) {
            $rule = new Rule();
            if ($rule->load((int)$change->get('target_id'))) $rule->delete();
        }
    }
    foreach (array_reverse($decisions) as $decision) $decision->deleteWithRelations();
    mcpCleanup($items);
}
