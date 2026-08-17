<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/modules/context.php';
require_once __DIR__ . '/modules/registry.php';

$omoDecisionInput = $_GET;
$context = omoDecisionResolveEditorContext($omoDecisionInput);
if (
    !empty($context['status'])
    && ($context['intent'] ?? '') === 'participate'
    && !empty($context['decision'])
    && $context['decision'] instanceof \dbObject\DecisionProcess
) {
    $decision = $context['decision'];
    $method = \dbObject\DecisionProcess::normalizeEvaluationMethod($decision->get('evaluation_method'));
    $redirectUrl = omoDecisionBuildParticipationPreviewUrl(
        (int)($context['organizationId'] ?? 0),
        (int)($context['targetHolonId'] ?? 0),
        (int)$decision->getId(),
        $method,
        'participate',
        true
    );

    if (($context['accessMode'] ?? '') === 'public' && trim((string)($context['publicToken'] ?? '')) !== '') {
        $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&')
            . 'token=' . rawurlencode((string)$context['publicToken']);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

require __DIR__ . '/edit_shared.php';
