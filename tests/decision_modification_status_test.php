<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisionproposal.class.php';
require_once dirname(__DIR__) . '/class/dbobject/decisiongovernanceaction.class.php';
require_once dirname(__DIR__) . '/class/dbobject/deferredproposal.class.php';
require_once dirname(__DIR__) . '/omo/api/decision/modules/common.php';

function omoLoadTranslationBundle(string $bundleKey, array $sourceLang): array
{
    return $sourceLang;
}

function t($key, $parameters, $lang, $sourceLang): string
{
    return $sourceLang[$key]['text'];
}

$modification = new class extends \dbObject\DeferredProposal {
    public function buildPresentationData(): array
    {
        return ['operation' => self::OPERATION_CREATE, 'targetLabel' => 'Règle', 'title' => 'Notes de frais'];
    }
};
$proposal = new class extends \dbObject\DecisionProposal {
    public array $modifications = [];

    public function getGovernanceActions() { return []; }
    public function getDeferredProposals(bool $pendingOnly = false) { return $this->modifications; }
};
$proposal->modifications = [$modification];
$escape = static fn ($text) => htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
$expected = [
    'pending' => 'Cette modification sera appliquée si la proposition est retenue.',
    'validated' => 'Cette modification a été validée et reste en attente d’application.',
    'applied' => 'Cette modification a été appliquée.',
    'rejected' => 'Cette modification n’a pas été appliquée : la proposition n’a pas été retenue.',
    'conflict' => 'Cette modification n’a pas pu être appliquée en raison d’un conflit.',
    'failed' => 'L’application de cette modification a échoué.',
];
foreach ($expected as $status => $message) {
    $modification->set('status', $status);
    $html = omoDecisionRenderGovernanceChanges($proposal, $escape);
    if (!str_contains($html, $escape($message))
        || ($status !== 'pending' && str_contains($html, $expected['pending']))) {
        throw new RuntimeException('Incorrect modification message for status: ' . $status);
    }
}
$modification->set('status', \dbObject\DeferredProposal::STATUS_REMOVED);
if (omoDecisionRenderGovernanceChanges($proposal, $escape) !== '') {
    throw new RuntimeException('Removed modifications must remain hidden.');
}
echo "Decision modification status tests passed.\n";
