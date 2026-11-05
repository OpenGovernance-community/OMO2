<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, PdoDbhCompat, Organization, OrganizationExport, Holon, User, UserOrganization};

function modelImagesCheck(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

function modelImagesFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    $object->loadFromArray($fields);
    modelImagesCheck(!empty($object->save()['status']), 'Fixture save: ' . $class);
    return $object;
}

// Keep the real importer's transactions inside one rollback-only fixture.
class ModelImagesRollbackConnection
{
    public function __construct(private PDO $pdo) {}
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function __call(string $method, array $arguments) { return $this->pdo->$method(...$arguments); }
}

$storage = sys_get_temp_dir() . '/omo-model-images-' . bin2hex(random_bytes(6));
mkdir($storage . '/img/upload/organization', 0755, true);
mkdir($storage . '/img/upload/holon', 0755, true);
$modelImage = '/img/upload/organization/model.png';
$modelIcon = '/img/upload/holon/model.png';
$image = imagecreatetruecolor(8, 8);
ob_start();
imagepng($image);
$png = (string)ob_get_clean();
file_put_contents($storage . $modelImage, $png);
file_put_contents($storage . $modelIcon, $png);
$_SERVER['DOCUMENT_ROOT'] = $storage;
$pdo = DbObject::getPdo();
$originalDbh = DbObject::$_dbh;
$pdo->beginTransaction();
DbObject::$_dbh = new PdoDbhCompat(new ModelImagesRollbackConnection($pdo));
try {
    $actor = modelImagesFixture(User::class, ['email' => 'model-images-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => true]);
    $_SESSION['currentUser'] = (int)$actor->getId();
    $sourceModel = modelImagesFixture(Organization::class, [
        'name' => 'Image model', 'isModel' => true, 'logo' => $modelImage, 'banner' => $modelImage,
        'description' => 'Public model description', 'url' => 'https://example.org/model',
    ]);
    modelImagesFixture(UserOrganization::class, [
        'IDuser' => $actor->getId(), 'IDorganization' => $sourceModel->getId(),
        'active' => true, 'parameters' => ['isAdmin' => true],
    ]);
    $root = modelImagesFixture(Holon::class, [
        'name' => 'Root', 'IDorganization' => $sourceModel->getId(), 'IDtypeholon' => 4,
        'active' => true, 'visible' => true, 'icon' => $modelIcon,
    ]);
    $template = modelImagesFixture(Holon::class, [
        'name' => 'Role template', 'templatename' => 'Role template', 'IDtypeholon' => 1,
        'IDholon_parent' => $root->getId(), 'IDholon_org' => $root->getId(),
        'active' => true, 'visible' => false, 'icon' => $modelIcon,
    ]);
    $circle = modelImagesFixture(Holon::class, [
        'name' => 'Circle', 'IDtypeholon' => 2, 'IDholon_parent' => $root->getId(),
        'IDholon_org' => $root->getId(), 'active' => true, 'visible' => true, 'icon' => $modelIcon,
    ]);
    $instance = modelImagesFixture(Holon::class, [
        'name' => 'Nested role', 'IDtypeholon' => 1, 'IDholon_parent' => $circle->getId(),
        'IDholon_org' => $root->getId(), 'IDholon_template' => $template->getId(),
        'active' => true, 'visible' => true, 'icon' => $modelIcon,
    ]);

    foreach ([[], ['logo' => $modelImage, 'banner' => $modelImage]] as $overrides) {
        $result = Organization::createFromPublicModel($sourceModel->getId(), $actor->getId(), 'Empty visual identity', $overrides);
        modelImagesCheck(!empty($result['status']), 'Create from model: ' . ($result['message'] ?? 'failed'));
        $target = $result['organization'];
        $target->load($target->getId(), true);
        modelImagesCheck(!$target->get('logo') && !$target->get('banner'), 'New organization has no inherited logo or banner');
        modelImagesCheck(!$target->get('description') && !$target->get('url'), 'New organization has no inherited description or URL');
        foreach ([$root, $template, $circle, $instance] as $sourceHolon) {
            $imported = new Holon();
            modelImagesCheck($imported->load($result['holonIdMap'][$sourceHolon->getId()], true), 'Copied holon exists');
            modelImagesCheck(!$imported->get('icon') && !$imported->getEffectiveIcon(), 'Root, templates and nested holons have no inherited icon');
        }
    }

    $initialized = modelImagesFixture(Organization::class, ['name' => 'Initialized from model']);
    $result = $initialized->initializeStructure($actor->getId(), $root->getId());
    modelImagesCheck(!empty($result['status']), 'Initialize from model: ' . ($result['message'] ?? 'failed'));
    $tree = $initialized->getStructureCompactExportData($result['rootHolon']);
    $iconCount = 0;
    array_walk_recursive($tree['holons'], static function ($value, $key) use (&$iconCount) {
        if ($key === 'icon' && $value) $iconCount++;
    });
    modelImagesCheck($iconCount === 0, 'Structural initialization also omits model icons');

    // Ordinary archive imports must still retain their own media references.
    $selected = ['structure' => true];
    $payload = OrganizationExport::build($sourceModel, $selected);
    $result = Organization::importOmo1ExportAsNewOrganization($payload, $selected, $actor->getId(), 'Archive images', [], ['sendMemberInvitationEmails' => false]);
    modelImagesCheck(!empty($result['status']), 'Archive import: ' . ($result['message'] ?? 'failed'));
    modelImagesCheck($result['organization']->get('logo') === $modelImage && $result['organization']->get('banner') === $modelImage, 'Archive organization images are preserved');
    modelImagesCheck($result['organization']->get('description') === $sourceModel->get('description') && $result['organization']->get('url') === $sourceModel->get('url'), 'Archive description and URL are preserved');
    $imported = new Holon();
    $imported->load($result['holonIdMap'][$instance->getId()], true);
    modelImagesCheck($imported->get('icon') === $modelIcon, 'Archive holon icons are preserved');

    // Uploading a new image during model creation must not touch the model file.
    $_POST['imageDataInput_logo'] = 'data:image/png;base64,' . base64_encode($png);
    $result = Organization::createFromPublicModel($sourceModel->getId(), $actor->getId(), 'Custom visual identity', [
        'logo' => 'newimage', 'description' => 'New organization description', 'url' => 'https://example.org/new',
    ]);
    modelImagesCheck(!empty($result['status']), 'Create with custom logo: ' . ($result['message'] ?? 'failed'));
    $customLogo = $result['organization']->get('logo');
    modelImagesCheck($customLogo && $customLogo !== $modelImage && is_file($storage . $customLogo), 'Explicit custom logo is saved separately');
    $result['organization']->load($result['organization']->getId(), true);
    modelImagesCheck($result['organization']->get('description') === 'New organization description' && $result['organization']->get('url') === 'https://example.org/new', 'Explicit description and URL are saved');
    modelImagesCheck(file_get_contents($storage . $modelImage) === $png && file_get_contents($storage . $modelIcon) === $png, 'Model files are intact');

    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $_GET = ['model_id' => $sourceModel->getId()];
    ob_start();
    require dirname(__DIR__) . '/popup/organization_create.php';
    $html = (string)ob_get_clean();
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);
    foreach (['logo', 'banner', 'url'] as $field) {
        modelImagesCheck($xpath->query('//input[@name="' . $field . '"]')->item(0)?->getAttribute('value') === '', 'Creation form starts with an empty ' . $field);
    }
    modelImagesCheck($xpath->query('//textarea[@name="description"]')->item(0)?->textContent === '', 'Creation form starts with an empty description');
    $sourceModel->load($sourceModel->getId(), true);
    modelImagesCheck($sourceModel->get('logo') === $modelImage && $sourceModel->get('banner') === $modelImage, 'Model references are unchanged');
    modelImagesCheck($sourceModel->get('description') === 'Public model description' && $sourceModel->get('url') === 'https://example.org/model', 'Model public information is unchanged');
    echo "organization_model_images_test: OK\n";
} finally {
    DbObject::$_dbh = $originalDbh;
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach (['organization', 'holon'] as $table) {
        foreach (glob($storage . '/img/upload/' . $table . '/*') ?: [] as $file) unlink($file);
        rmdir($storage . '/img/upload/' . $table);
    }
    foreach (['/img/upload', '/img', ''] as $directory) rmdir($storage . $directory);
}
