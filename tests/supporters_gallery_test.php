<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once dirname(__DIR__) . '/shared_functions.php';

use dbObject\{DbObject, Organization, OrganizationPatreon, User, UserPatreon};

function supportersCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function supportersFixture(string $class, array $values): DbObject
{
    $object = new $class();
    $object->loadFromArray($values);
    $result = $object->save();
    supportersCheck(!empty($result['status']), 'Cannot save ' . $class . ': ' . json_encode($result));
    return $object;
}
function supportersHtml(): string
{
    ob_start();
    try { require dirname(__DIR__) . '/popup/supporters.php'; return (string)ob_get_contents(); }
    finally { ob_end_clean(); }
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $campaign = 'gallery-' . bin2hex(random_bytes(6));
    $GLOBALS['patreonCreatorCampaignId'] = $campaign;
    $_GET['lang'] = 'fr';
    $html = supportersHtml();
    supportersCheck(!str_contains($html, 'id="supporters-organizations"') && !str_contains($html, 'id="supporters-people"'), 'Empty supporter sections are hidden');
    $common = ['campaign_id' => $campaign, 'is_connected' => 1, 'refresh_token' => 'gallery-secret-refresh',
        'access_token' => 'gallery-secret-access', 'patron_status' => 'active_patron'];
    $organizations = $people = $users = [];
    foreach ([3999, 4000, 7999, 8000, 15999, 16000, 29999, 30000, 4000, 4000, 4000] as $i => $amount) {
        $organization = supportersFixture(Organization::class, ['name' => 'Gallery organization ' . $i,
            'description' => '<b>Public description</b>', 'url' => 'https://example.invalid/organization',
            'logo' => $i === 0 ? '/img/omo-iceberg.jpg' : ($i === 1 ? '/img/missing-supporter-logo.jpg' : '')]);
        $organizations[] = supportersFixture(OrganizationPatreon::class, $common + [
            'IDorganization' => $organization->getId(), 'currently_entitled_amount_cents' => $amount]);
    }
    $html = supportersHtml();
    supportersCheck(str_contains($html, 'id="supporters-organizations"') && !str_contains($html, 'id="supporters-people"'), 'Populated sections remain visible while empty sections are hidden');
    foreach ([1, 399, 400, 799, 800, 1599, 1600, 4000, 8000, 16000, 30000] as $i => $amount) {
        $users[] = $user = supportersFixture(User::class, ['firstname' => $i === 0 ? '<script>hostile</script>' : '',
            'email' => 'gallery-private-' . $campaign . '-' . $i . '@example.invalid', 'active' => 1,
            'image' => $i === 0 ? '/img/omo-iceberg.jpg' : ($i === 1 ? '/img/missing-supporter-portrait.jpg' : '')]);
        $people[] = supportersFixture(UserPatreon::class, array_replace($common, [
            'IDuser' => $user->getId(), 'is_connected' => $i === 10 ? 0 : 1, 'currently_entitled_amount_cents' => $amount]));
    }
    supportersCheck(count(OrganizationPatreon::getSupporterGalleryItems($campaign)) === 10, 'Organizations start at exactly 40 euros');
    supportersCheck(count(UserPatreon::getSupporterGalleryItems($campaign)) === 10, 'Individuals include free contributions and exclude disconnected accounts');
    $html = supportersHtml();
    supportersCheck(!str_contains($html, 'class="supporters-tier-amount"'), 'Exactly ten entries remain ungrouped');
    supportersCheck(strpos($html, 'id="supporters-organizations"') < strpos($html, 'id="supporters-people"'), 'Organizations precede individuals');
    supportersCheck(str_contains($html, '&lt;script&gt;hostile&lt;/script&gt;') && !str_contains($html, '<script>hostile'), 'Public names are escaped');
    supportersCheck(!str_contains($html, 'gallery-private-') && !str_contains($html, 'gallery-secret-'), 'No emails or OAuth secrets in public HTML');
    $organizations[0]->set('currently_entitled_amount_cents', 4000); $organizations[0]->save();
    $html = supportersHtml();
    [$organizationHtml, $personHtml] = explode('id="supporters-people"', $html, 2);
    supportersCheck(str_contains($organizationHtml, 'class="supporters-tier-amount"') && !str_contains($personHtml, 'class="supporters-tier-amount"'), 'Each list independently switches above ten');
    supportersCheck(!str_contains($organizationHtml, 'javascript:') && str_contains($organizationHtml, 'Public description'), 'Organization information remains safe');
    $people[10]->set('is_connected', 1); $people[10]->save();
    $html = supportersHtml();
    supportersCheck(substr_count($html, 'class="supporters-tier-amount"') === 11, 'Four organization tiers and seven individual tiers are rendered');
    foreach (['Soutien libre', 'Utilisateur', 'Contributeur', 'Nous utilisons et soutenons (social)', 'Nous utilisons et soutenons', 'Nous integrons et diffusons', 'J’accelere le projet'] as $title) {
        supportersCheck(str_contains($html, $title), 'Missing tier: ' . $title);
    }
    if (in_array('--preview', $argv, true)) file_put_contents(dirname(__DIR__) . '/tmp/supporters-gallery-preview.html', $html);
    foreach (['patron_status' => 'declined_patron', 'is_connected' => 0, 'refresh_token' => '', 'campaign_id' => 'another-campaign', 'currently_entitled_amount_cents' => 0] as $field => $value) {
        $old = $people[0]->get($field); $people[0]->set($field, $value); $people[0]->save();
        supportersCheck(count(UserPatreon::getSupporterGalleryItems($campaign)) === 10, 'Excluded individual: ' . $field);
        $people[0]->set($field, $old); $people[0]->save();
        $old = $organizations[0]->get($field); $organizations[0]->set($field, $value); $organizations[0]->save();
        supportersCheck(count(OrganizationPatreon::getSupporterGalleryItems($campaign)) === 10, 'Excluded organization: ' . $field);
        $organizations[0]->set($field, $old); $organizations[0]->save();
    }
    $users[0]->set('active', 0); $users[0]->save();
    supportersCheck(count(UserPatreon::getSupporterGalleryItems($campaign)) === 10, 'Inactive OMO users are excluded');
    echo "supporters_gallery_test: OK (eligibility, 10/11 boundary, independent tiers, public data and escaping)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
