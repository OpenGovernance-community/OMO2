<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once dirname(__DIR__, 4) . '/common/tips/translations.php';
header('Content-Type: application/json; charset=UTF-8');
$respond = static function (int $code, string $key, array $extra = []): never {
    http_response_code($code);
    echo json_encode(array_merge(['status' => $code === 200, 'message' => commonTipsT($key)], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $respond(405, 'invalid');
if (!commonCurrentUserIsSiteAdminModeEnabled() || commonGetCurrentShareLink()) $respond(403, 'forbidden');
$token = $_POST['_csrf'] ?? null;
if (!is_string($token) || !hash_equals(commonCsrfToken(), $token)) $respond(403, 'forbidden');
if (!\dbObject\Tip::isAvailable()) $respond(503, 'unavailable');
$tip = new \dbObject\Tip();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0 && !$tip->load($id)) $respond(404, 'invalid');
$data = array_intersect_key($_POST, array_flip(['text', 'link', 'image', 'active']));
foreach ($data as $value) if (!is_string($value)) $respond(422, 'invalid');
$text = trim($data['text'] ?? '');
$link = trim($data['link'] ?? '');
if ($text === '' || mb_strlen($text, 'UTF-8') > 500 || strlen($link) > 1000 || !\dbObject\Tip::isSafeLink($link)
    || !in_array($data['active'] ?? '', ['0', '1'], true)) $respond(422, 'invalid');
try {
    $tip->loadFromArray($data);
    if (empty($tip->save()['status'])) $respond(500, 'error');
} catch (InvalidArgumentException $error) {
    $respond(422, 'invalid');
} catch (Throwable $error) {
    error_log('Software tip save failed: ' . $error->getMessage());
    $respond(500, 'error');
}
$respond(200, 'saved', ['id' => (int)$tip->getId()]);
