<?php
require_once __DIR__ . '/patreon.php';
require_once __DIR__ . '/helloasso.php';

/** Nominal monthly contribution in cents, same convention as the existing Patreon thresholds. */
function supportGetUserContribution(int $userId): array
{
    $result = ['amount_cents' => 0, 'source' => null, 'valid_until' => null];
    if ($userId <= 0) return $result;
    $patreon = \dbObject\UserPatreon::findByUserId($userId);
    if ($patreon && $patreon->isConnected() && $patreon->get('patron_status') === 'active_patron'
        && (patreonGetCreatorCampaignId() === '' || $patreon->get('campaign_id') === patreonGetCreatorCampaignId())) {
        $result['amount_cents'] = max(0, (int)$patreon->get('currently_entitled_amount_cents'));
        if ($result['amount_cents'] > 0) $result['source'] = 'patreon';
    }
    if (helloAssoIsConfigured()) {
        $helloAsso = \dbObject\UserHelloAsso::contribution($userId, helloAssoConfigurationKey());
        if ($helloAsso['amount_cents'] > $result['amount_cents']) $result = $helloAsso + ['source' => 'helloasso'];
    }
    return $result;
}

function supportGetUserContributionAmountCents(int $userId): int
{
    return supportGetUserContribution($userId)['amount_cents'];
}

/** Organization contributions remain explicitly attached through their existing Patreon connection. */
function supportGetOrganizationContributionAmountCents(int $organizationId): int
{
    $connection = \dbObject\OrganizationPatreon::findByOrganizationId($organizationId);
    return $connection && $connection->isConnected() && $connection->get('patron_status') === 'active_patron'
        && (patreonGetCreatorCampaignId() === '' || $connection->get('campaign_id') === patreonGetCreatorCampaignId())
        ? max(0, (int)$connection->get('currently_entitled_amount_cents')) : 0;
}

function supportIndividualCanUseAi(int $userId): bool
{
    // Retains self-hosting, creator and super-admin exceptions without inventing a monetary amount.
    if (patreonIndividualHasAiAccess($userId)) return true;
    if ($userId <= 0 || !helloAssoIsConfigured()) return false;
    return \dbObject\UserHelloAsso::contribution($userId, helloAssoConfigurationKey())['amount_cents'] > 0;
}

function supportUserCanUseAi(int $userId, int $organizationId = 0): bool
{
    if (supportIndividualCanUseAi($userId)) return true;
    return $organizationId > 0 && $userId > 0
        && \dbObject\UserOrganization::hasActiveMembership($userId, $organizationId)
        && patreonOrganizationCanUseAi($organizationId);
}

function supportCanManageOrganizationRouting(int $userId, int $minimumAmountCents = 2000): bool
{
    if (function_exists('commonUserHasSiteAdminOverride') && commonUserHasSiteAdminOverride($userId)) return true;
    return $userId > 0 && supportGetUserContributionAmountCents($userId) > max(0, $minimumAmountCents);
}
