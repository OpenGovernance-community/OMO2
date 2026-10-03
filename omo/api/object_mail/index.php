<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/object_mail/ui.php';
header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
$notice = null; $error = null;
$oid = filter_var($_GET['oid'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$type = is_string($_GET['object_type'] ?? null) ? $_GET['object_type'] : '';
$id = filter_var($_GET['object_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$status = null; $audience = null;
try {
    $audience = \dbObject\ObjectAudience::resolve($oid, $type, $id);
    $_SESSION['objectMailCsrf'] ??= bin2hex(random_bytes(32));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['objectMailCsrf'], $_POST['csrf'])) throw new DomainException('Formulaire invalide. Rechargez la page.');
        if (array_diff(array_keys($_POST), ['csrf', 'subject', 'message', 'request_key', 'audience_token'])) throw new InvalidArgumentException('Champ non autorise.');
        $args = ['object_type' => $type, 'object_id' => $id] + array_diff_key($_POST, ['csrf' => true]);
        $result = \dbObject\ObjectMail::send($oid, $args);
        header('Location: /omo/api/object_mail/index.php?' . http_build_query(['oid' => $oid, 'object_type' => $type, 'object_id' => $id, 'mail_id' => $result['mail_id'], 'queued' => 1]), true, 303);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') { header('Allow: GET, POST'); http_response_code(405); exit; }
    if (isset($_GET['mail_id'])) {
        $mailId = filter_var($_GET['mail_id'], FILTER_VALIDATE_INT) ?: 0;
        $status = \dbObject\ObjectMail::status($oid, $mailId);
        if ($status['object_type'] !== $type || $status['object_id'] !== $id) throw new DomainException('Message indisponible pour cet objet.');
        if (isset($_GET['queued'])) {
            $hasDeliveryIssue = $status['delivery']['failed'] + $status['delivery']['skipped'] + $status['delivery']['unknown'] > 0;
            $notice = omoObjectMailT($hasDeliveryIssue ? 'delivery_issue' : ($status['complete'] ? 'delivery_done' : 'delivery_pending'));
        }
    }
} catch (DomainException | InvalidArgumentException $exception) { $error = $exception->getMessage(); http_response_code(400); }
catch (Throwable $exception) { error_log('OMO object mail composer failed.'); $error = 'Impossible de traiter le message.'; http_response_code(500); }
$baseUrl = '/omo/api/object_mail/index.php?' . http_build_query(['oid' => $oid, 'object_type' => $type, 'object_id' => $id]);
$requestKey = is_string($_POST['request_key'] ?? null) ? $_POST['request_key'] : bin2hex(random_bytes(24));
?>
<!doctype html>
<html lang="<?= omoObjectMailEscape(commonAuthGetTranslationLocale()) ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= omoObjectMailEscape(omoObjectMailT('title')) ?></title>
    <link rel="stylesheet" href="<?= omoObjectMailEscape(commonAssetUrl('/common/assets/components.css')) ?>">
    <link rel="stylesheet" href="<?= omoObjectMailEscape(commonAssetUrl('/common/object_mail/ui.css')) ?>">
    <link rel="stylesheet" href="/common/notifications/notifications.css">
    <script src="/common/notifications/notifications.js" defer></script>
    <script src="<?= omoObjectMailEscape(commonAssetUrl('/common/object_mail/ui.js')) ?>" defer></script>
</head>
<body class="object-mail-page">
<main class="object-mail-composer">
    <header class="generic-drawer-content">
        <h1 class="generic-visually-hidden"><?= omoObjectMailEscape(omoObjectMailT('title')) ?></h1>
        <?php if ($audience): ?>
        <div class="generic-form-section__copy">
            <p class="generic-meta"><?= omoObjectMailEscape($audience['organization_name']) ?></p>
            <h2 class="generic-card-title generic-card-title--medium"><?= omoObjectMailEscape($audience['title']) ?></h2>
        </div>
        <?php endif; ?>
    </header>
    <?php if ($status): ?>
    <section class="generic-drawer-content">
        <h2 class="generic-card-title generic-card-title--small"><?= omoObjectMailEscape(omoObjectMailT('tracking')) ?></h2>
        <dl class="generic-form-grid object-mail-delivery"><?php foreach ($status['delivery'] as $key => $count): if (!$count) continue; ?>
            <div class="generic-soft-panel generic-soft-panel--stack"><dt class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT($key)) ?></dt><dd class="generic-meta-value"><?= (int)$count ?></dd></div>
        <?php endforeach; ?></dl>
        <?php if ($status['delivery']['failed'] + $status['delivery']['unknown'] > 0): ?><p class="generic-help-text"><?= omoObjectMailEscape(omoObjectMailT('tracking_hint')) ?></p><?php endif; ?>
    </section>
    <footer class="generic-drawer-footer generic-drawer-footer--sticky">
        <button class="generic-action-button generic-action-button--secondary" type="button" data-object-mail-close hidden><?= omoObjectMailEscape(omoObjectMailT('close')) ?></button>
        <div class="generic-form-actions">
            <?php if ($status['delivery']['queued'] + $status['delivery']['sending'] > 0): ?><a class="generic-action-button generic-action-button--main" href="<?= omoObjectMailEscape($baseUrl . '&mail_id=' . $status['mail_id']) ?>"><?= omoObjectMailEscape(omoObjectMailT('refresh')) ?></a><?php endif; ?>
            <a class="generic-action-button generic-action-button--secondary" href="<?= omoObjectMailEscape($baseUrl) ?>"><?= omoObjectMailEscape(omoObjectMailT('new')) ?></a>
        </div>
    </footer>
    <?php elseif ($audience): ?>
    <div class="generic-drawer-content">
        <details class="generic-accordion generic-accordion--inset">
            <summary><?= omoObjectMailEscape(omoObjectMailT('recipients', ['count' => count($audience['recipients'])])) ?></summary>
            <ul class="object-mail-recipients generic-drawer-content" tabindex="0" aria-label="<?= omoObjectMailEscape(omoObjectMailT('audience')) ?>">
                <?php foreach ($audience['recipients'] as $member): ?><li class="generic-form-section__copy"><span class="generic-meta-value"><?= omoObjectMailEscape($member['name']) ?></span><span class="generic-meta"><?= omoObjectMailEscape($member['email']) ?></span></li><?php endforeach; ?>
            </ul>
        </details>
    </div>
    <?php if ($audience['can_send'] && count($audience['recipients']) > 0 && count($audience['recipients']) <= \dbObject\ObjectMail::MAX_RECIPIENTS): ?>
        <form method="post" action="<?= omoObjectMailEscape($baseUrl) ?>" class="object-mail-form" data-object-mail-form data-object-mail-sending="<?= omoObjectMailEscape(omoObjectMailT('sending_progress')) ?>">
            <input type="hidden" name="csrf" value="<?= omoObjectMailEscape($_SESSION['objectMailCsrf']) ?>">
            <input type="hidden" name="request_key" value="<?= omoObjectMailEscape($requestKey) ?>">
            <input type="hidden" name="audience_token" value="<?= omoObjectMailEscape($audience['audience_token']) ?>">
            <div class="generic-drawer-content generic-form-stack generic-form-stack--compact">
                <label class="generic-form-field"><span class="generic-form-label"><?= omoObjectMailEscape(omoObjectMailT('subject')) ?></span><input class="generic-form-control generic-form-control--compact" name="subject" maxlength="250" required autofocus value="<?= omoObjectMailEscape(is_string($_POST['subject'] ?? null) ? $_POST['subject'] : '') ?>"></label>
                <label class="generic-form-field"><span class="generic-form-label"><?= omoObjectMailEscape(omoObjectMailT('message')) ?></span><textarea class="generic-form-control generic-form-control--compact object-mail-message" name="message" maxlength="20000" rows="8" required><?= omoObjectMailEscape(is_string($_POST['message'] ?? null) ? $_POST['message'] : '') ?></textarea></label>
                <p class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT('reply_to', ['email' => $audience['sender']['email']])) ?></p>
            </div>
            <footer class="generic-drawer-footer generic-drawer-footer--sticky">
                <button class="generic-action-button generic-action-button--secondary" type="button" data-object-mail-close hidden><?= omoObjectMailEscape(omoObjectMailT('cancel')) ?></button>
                <div class="generic-form-actions">
                    <span class="generic-meta" data-object-mail-progress role="status" hidden></span>
                    <button class="generic-action-button generic-action-button--main" type="submit"><?= omoObjectMailEscape(omoObjectMailT('send')) ?></button>
                </div>
            </footer>
        </form>
    <?php else: ?><p class="generic-drawer-content generic-description"><?= omoObjectMailEscape(omoObjectMailT(!$audience['recipients'] ? 'empty' : (count($audience['recipients']) > \dbObject\ObjectMail::MAX_RECIPIENTS ? 'too_many' : 'denied'), ['count' => \dbObject\ObjectMail::MAX_RECIPIENTS])) ?></p><?php endif; ?>
    <?php else: ?><p class="generic-drawer-content generic-description"><?= omoObjectMailEscape(omoObjectMailT('unavailable')) ?></p>
    <?php endif; ?>
</main>
<?php if ($error || $notice): ?>
<script>document.addEventListener('DOMContentLoaded', function () {
    var notifier = window.parent !== window && typeof window.parent.commonNotify === 'function' ? window.parent : window;
    notifier.commonNotify(<?= json_encode($error ?? $notice, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($error || !empty($hasDeliveryIssue) ? 'error' : 'success') ?>);
});</script>
<?php endif; ?>
</body></html>
