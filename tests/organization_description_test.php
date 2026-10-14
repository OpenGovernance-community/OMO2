<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization};

function organizationDescriptionCheck(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}
function organizationDescriptionFixture(string $class, array $values): DbObject
{
    $object = new $class();
    $object->loadFromArray($values);
    organizationDescriptionCheck(!empty($object->save()['status']), 'Fixture save failed: ' . $class);
    return $object;
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $description = "Text <script>alert(1)</script> & quotes\nSecond line";
    $url = 'https://example.org/model?a=1&b=2';
    $fixtureOrganization = organizationDescriptionFixture(Organization::class, [
        'name' => 'Model description test', 'description' => $description, 'url' => '  ' . $url . '  ', 'isModel' => true,
    ]);
    organizationDescriptionFixture(Holon::class, ['name' => 'Model root', 'IDorganization' => $fixtureOrganization->getId(), 'IDtypeholon' => 4, 'visible' => 1, 'active' => 1]);
    $fixtureOrganization->load($fixtureOrganization->getId(), true);
    organizationDescriptionCheck($fixtureOrganization->get('description') === $description && $fixtureOrganization->get('url') === $url, 'Plain text and trimmed URL must persist');
    foreach (['javascript:alert(1)', 'data:text/html,test', '//example.org', 'ftp://example.org', 'https://', 'https://example.org/' . str_repeat('a', 2048)] as $invalidUrl) {
        $fixtureOrganization->set('url', $invalidUrl);
        organizationDescriptionCheck(empty($fixtureOrganization->save()['status']), 'Unsafe or invalid URL must be rejected');
    }
    $fixtureOrganization->load($fixtureOrganization->getId(), true);
    organizationDescriptionCheck($fixtureOrganization->get('url') === $url, 'Failed saves must preserve the stored URL');

    $user = organizationDescriptionFixture(User::class, ['firstname' => 'Description test', 'email' => 'description-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
    organizationDescriptionFixture(UserOrganization::class, ['IDuser' => $user->getId(), 'IDorganization' => $fixtureOrganization->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    $_SESSION['currentUser'] = $user->getId();
    $_SESSION['currentOrganization'] = $fixtureOrganization->getId();
    organizationDescriptionCheck(commonSetCurrentUserAdminMode(true, $fixtureOrganization->getId()), 'Admin fixture must be active');
    $_SERVER['REQUEST_URI'] = '/omo/api/organizations/model_popup.php';
    ob_start();
    require dirname(__DIR__) . '/omo/api/organizations/model_popup.php';
    $html = (string)ob_get_clean();
    organizationDescriptionCheck(!str_contains($html, 'Partage par'), 'Sharing attribution must be removed');
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    $card = $xpath->query('//input[@name="model_id" and @value="' . $fixtureOrganization->getId() . '"]/parent::label')->item(0);
    organizationDescriptionCheck($card !== null && str_contains($card->textContent, $description), 'Description must render as plain text');
    organizationDescriptionCheck($card->getElementsByTagName('script')->length === 0 && $card->getElementsByTagName('br')->length === 1, 'HTML must be escaped and line breaks preserved');
    $link = $card->getElementsByTagName('a')->item(0);
    organizationDescriptionCheck($link && $link->getAttribute('href') === $url && $link->getAttribute('target') === '_blank' && str_contains($link->getAttribute('rel'), 'noopener'), 'Learn-more link must open the saved URL safely');

    $_GET = ['oid' => $fixtureOrganization->getId()];
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    ob_start();
    require dirname(__DIR__) . '/popup/organization_create.php';
    $formHtml = (string)ob_get_clean();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $formHtml);
    $xpath = new DOMXPath($dom);
    organizationDescriptionCheck($xpath->query('//textarea[@name="description"]')->item(0)?->textContent === $description, 'Edit form must load the plain text description');
    organizationDescriptionCheck($xpath->query('//input[@name="url" and @type="url"]')->item(0)?->getAttribute('value') === $url, 'Edit form must load the URL');

    $fixtureOrganization->loadFromArray(['name' => 'Renamed model']);
    organizationDescriptionCheck(!empty($fixtureOrganization->save()['status']), 'Other edits must succeed');
    $fixtureOrganization->load($fixtureOrganization->getId(), true);
    organizationDescriptionCheck($fixtureOrganization->get('description') === $description && $fixtureOrganization->get('url') === $url, 'Omitted fields must be retained');
    $fixtureOrganization->loadFromArray(['description' => '', 'url' => '']);
    organizationDescriptionCheck(!empty($fixtureOrganization->save()['status']), 'Optional fields must allow clearing');
    $models = array_column(Organization::getPublicModelCatalog(), null, 'id');
    organizationDescriptionCheck($models[$fixtureOrganization->getId()]['description'] === '' && $models[$fixtureOrganization->getId()]['url'] === '', 'Cleared fields must disappear from the catalog');
    echo "organization_description_test: OK (persistence, validation, public card, edit form, omitted and cleared fields)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
