<?php
use dbObject\ArrayRule;
use dbObject\Holon;
use dbObject\Rule;

$dashboardRuleItems = array();
$dashboardRuleCounts = array('modified' => 0, 'review' => 0, 'obsolete' => 0);
if (!empty($enabledAppHashes['policy']) && $scopeReferenceHolon instanceof Holon) {
    $ruleContextHolonIds = $dashboardModuleScopeHolonIds;
    $dashboardRules = new ArrayRule();
    $dashboardRules->loadForPolicyContexts($currentOrganizationId, $ruleContextHolonIds);
    $today = new DateTimeImmutable('today');
    $recentThreshold = $today->modify('-6 days');
    foreach ($dashboardRules as $dashboardRule) {
        if (!($dashboardRule instanceof Rule)) {
            continue;
        }
        $updatedAt = $dashboardRule->get('updated_at');
        $isModified = $updatedAt instanceof DateTimeInterface && $updatedAt >= $recentThreshold;
        $isObsolete = !$dashboardRule->isValidAt($today);
        $needsReview = !$isObsolete && $dashboardRule->isReviewDue($today);
        $filters = array();
        if ($isModified) {
            $dashboardRuleCounts['modified']++;
            $filters[] = 'modified';
        }
        if ($needsReview) {
            $dashboardRuleCounts['review']++;
            $filters[] = 'review';
        }
        if ($isObsolete) {
            $dashboardRuleCounts['obsolete']++;
            $filters[] = 'obsolete';
        }
        if ($filters === array()) {
            continue;
        }
        $dashboardRuleItems[] = array(
            'id' => (int)$dashboardRule->getId(),
            'title' => trim((string)$dashboardRule->get('title')),
            'filters' => $filters,
            'reviewDate' => $dashboardRule->get('review_date'),
            'expirationDate' => $dashboardRule->get('expiration_date'),
            'holonId' => $dashboardRule->getHolon() instanceof Holon ? (int)$dashboardRule->getHolon()->getId() : 0,
        );
    }
}
