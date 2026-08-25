<?php
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionprocess.class.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';
require_once dirname(__DIR__) . '/omo/api/decision/params/shared.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/vote/shared.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/majority_judgment/shared.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/consent/shared.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/consultation_only/shared.php';

use dbObject\DecisionProcess;

function assertDecisionParams($condition, $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$defaults = omoDecisionParamsGetDefaultConfig();
assertDecisionParams(empty($defaults['governance']['enabled']), 'Governance workflow must require explicit activation.');
assertDecisionParams(!empty($defaults['methods'][DecisionProcess::METHOD_CONSENT]), 'Consent must remain enabled by default.');
assertDecisionParams(!empty($defaults['methods'][DecisionProcess::METHOD_CONSULTATION_ONLY]), 'Consultation only must be enabled by default.');

$normalized = omoDecisionParamsNormalizeConfig([
    'methods' => [DecisionProcess::METHOD_SIMPLE_VOTE => false],
    'governance' => [
        'enabled' => true,
        'evaluation_method' => DecisionProcess::METHOD_SIMPLE_VOTE,
        'question' => ' Question test ',
        'consultation_days' => -2,
        'vote_days' => 500,
        'show_live_votes' => true,
        'live_votes_anonymous' => true,
    ],
]);
assertDecisionParams(empty($normalized['methods'][DecisionProcess::METHOD_SIMPLE_VOTE]), 'A disabled method must remain disabled.');
assertDecisionParams(empty($normalized['methods'][DecisionProcess::METHOD_CONSENT]), 'Unchecked methods must remain disabled after form submission.');
assertDecisionParams(!empty($normalized['methods'][DecisionProcess::METHOD_CONSULTATION_ONLY]), 'Consultation only must be added to existing settings by default.');
assertDecisionParams($normalized['governance']['evaluation_method'] === DecisionProcess::METHOD_SIMPLE_VOTE, 'Governance workflows must retain their configured evaluation method.');
assertDecisionParams($normalized['governance']['question'] === 'Question test', 'The governance question must be trimmed.');
assertDecisionParams($normalized['governance']['consultation_days'] === 0, 'Consultation duration must accept zero but not negative days.');
assertDecisionParams($normalized['governance']['vote_days'] === 365, 'Vote duration must be limited to one year.');
assertDecisionParams(!empty($normalized['governance']['live_votes_anonymous']), 'Anonymous live votes require live result display.');

$withoutLiveVotes = omoDecisionParamsNormalizeConfig([
    'governance' => ['live_votes_anonymous' => true],
]);
assertDecisionParams(empty($withoutLiveVotes['governance']['live_votes_anonymous']), 'Anonymous live votes must be disabled when live results are off.');
assertDecisionParams(!empty($withoutLiveVotes['methods'][DecisionProcess::METHOD_SIMPLE_VOTE]), 'Methods must retain their defaults before the settings form is saved.');

assertDecisionParams(!empty(omoDecisionVoteBuildConfig([])['is_anonymous']), 'Simple votes must be anonymous by default.');
assertDecisionParams(!empty(omoDecisionMajorityJudgmentBuildConfig([])['is_anonymous']), 'Majority judgment votes must be anonymous by default.');
assertDecisionParams(!empty(omoDecisionConsentBuildConfig([])['is_anonymous']), 'Consent votes must be anonymous by default.');
assertDecisionParams(!empty(omoDecisionConsultationOnlyBuildConfig([])['proposal_content']['description']), 'Consultation only must keep a description field for proposals by default.');
assertDecisionParams(!empty(omoDecisionVoteBuildConfig(['randomize_proposal_order' => true])['randomize_proposal_order']), 'Simple votes must retain the random proposal order setting.');
assertDecisionParams(!empty(omoDecisionMajorityJudgmentBuildConfig(['randomize_proposal_order' => true])['randomize_proposal_order']), 'Majority judgment votes must retain the random proposal order setting.');
assertDecisionParams(!empty(omoDecisionConsentBuildConfig(['randomize_proposal_order' => true])['randomize_proposal_order']), 'Consent votes must retain the random proposal order setting.');
assertDecisionParams(!empty(omoDecisionVoteBuildConfig(['one_proposal_at_a_time' => true])['one_proposal_at_a_time']), 'Simple votes must retain the one-proposal-at-a-time setting.');
assertDecisionParams(!empty(omoDecisionMajorityJudgmentBuildConfig(['one_proposal_at_a_time' => true])['one_proposal_at_a_time']), 'Majority judgment votes must retain the one-proposal-at-a-time setting.');
assertDecisionParams(!empty(omoDecisionConsentBuildConfig(['one_proposal_at_a_time' => true])['one_proposal_at_a_time']), 'Consent votes must retain the one-proposal-at-a-time setting.');
assertDecisionParams(empty(omoDecisionConsultationOnlyBuildConfig(['is_anonymous' => false])['is_anonymous']), 'Consultation only must retain its named participation setting.');

$shuffleProposals = [
    new class(1) {
        public function __construct(private int $id) {}
        public function getId(): int { return $this->id; }
    },
    new class(2) {
        public function __construct(private int $id) {}
        public function getId(): int { return $this->id; }
    },
    new class(3) {
        public function __construct(private int $id) {}
        public function getId(): int { return $this->id; }
    },
];
$firstShuffle = omoDecisionShuffleProposalsForParticipant($shuffleProposals, ['currentUserId' => 12], 'test');
$secondShuffle = omoDecisionShuffleProposalsForParticipant($shuffleProposals, ['currentUserId' => 12], 'test');
assertDecisionParams(
    array_map(static fn ($proposal) => $proposal->getId(), $firstShuffle) === array_map(static fn ($proposal) => $proposal->getId(), $secondShuffle),
    'Random proposal order must remain stable for the same participant.'
);

$untitledProposalItems = omoDecisionBuildProposalItemsFromInput(
    [''],
    ['<p>Proposition sans titre</p>'],
    [''],
    [0],
    ['title' => false, 'description' => true, 'url' => false]
);
assertDecisionParams(count($untitledProposalItems) === 1, 'A description-only proposal must be retained.');
assertDecisionParams($untitledProposalItems[0]['title'] === '', 'A description-only proposal must not receive a copied title.');

$emptyProposalItems = omoDecisionBuildProposalItemsFromInput(
    [''],
    [''],
    [''],
    [0],
    ['title' => false, 'description' => true, 'url' => false]
);
assertDecisionParams(count($emptyProposalItems) === 0, 'An empty proposal must not be retained.');

$newDecision = new DecisionProcess();
assertDecisionParams($newDecision->canEnableNamedVote(), 'A new decision must allow a named vote configuration.');

echo "Decision parameter tests passed.\n";
