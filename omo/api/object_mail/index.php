<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/object_mail/ui.php';
require_once dirname(__DIR__, 3) . '/common/object_mail/delivery.php';
header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
$notice = null; $error = null;
$oid = filter_var($_GET['oid'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$type = is_string($_GET['object_type'] ?? null) ? $_GET['object_type'] : '';
$id = filter_var($_GET['object_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$status = null; $audience = null; $fromAddress = '';
try {
    $audience = \dbObject\ObjectAudience::resolve($oid, $type, $id);
    $_SESSION['objectMailCsrf'] ??= bin2hex(random_bytes(32));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['objectMailCsrf'], $_POST['csrf'])) throw new DomainException('Formulaire invalide. Rechargez la page.');
        if (array_diff(array_keys($_POST), ['csrf', 'subject', 'message', 'message_format', 'request_key', 'audience_token', 'recipient_selection', 'recipient_ids'])) throw new InvalidArgumentException('Champ non autorise.');
        if (($_POST['recipient_selection'] ?? null) !== '1' || !is_array($_POST['recipient_ids'] ?? null)) throw new InvalidArgumentException(omoObjectMailT('selection_empty'));
        $args = ['object_type' => $type, 'object_id' => $id] + array_diff_key($_POST, ['csrf' => true, 'recipient_selection' => true, 'recipient_ids' => true]);
        $result = \dbObject\ObjectMail::send($oid, $args, null, $_POST['recipient_ids']);
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
    if (!$status) $fromAddress = omoObjectMailFromAddress();
} catch (DomainException | InvalidArgumentException $exception) { $error = $exception->getMessage(); http_response_code(400); }
catch (Throwable $exception) { error_log('OMO object mail composer failed.'); $error = 'Impossible de traiter le message.'; http_response_code(500); }
$baseUrl = '/omo/api/object_mail/index.php?' . http_build_query(['oid' => $oid, 'object_type' => $type, 'object_id' => $id]);
$requestKey = is_string($_POST['request_key'] ?? null) ? $_POST['request_key'] : bin2hex(random_bytes(24));
$selectedIds = $_SERVER['REQUEST_METHOD'] === 'POST' ? (is_array($_POST['recipient_ids'] ?? null) ? $_POST['recipient_ids'] : []) : null;
$selectedCount = $audience ? count(array_filter($audience['recipients'], static fn ($member) => $selectedIds === null || in_array($member['member_id'], $selectedIds, true))) : 0;
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
    <script src="<?= omoObjectMailEscape(commonAssetUrl('/omo/assets/js/simple-html-field.js')) ?>" defer></script>
    <script src="<?= omoObjectMailEscape(commonAssetUrl('/common/object_mail/ui.js')) ?>" defer></script>
</head>
<body class="object-mail-page">
<main class="object-mail-composer<?= !$status && $audience && $audience['can_send'] && $audience['recipients'] ? ' object-mail-composer--writing' : '' ?>">
    <header class="generic-drawer-content">
        <h1 class="generic-visually-hidden"><?= omoObjectMailEscape(omoObjectMailT('title')) ?></h1>
        <?php if ($audience): ?>
        <div class="object-mail-compose-context">
            <?= omoObjectMailIcon() ?>
            <p class="generic-meta"><?= omoObjectMailEscape($audience['organization_name']) ?> &middot; <?= omoObjectMailEscape($audience['title']) ?></p>
        </div>
        <?php endif; ?>
    </header>
    <?php if ($status): [$state, $tone] = omoObjectMailDeliveryState($status['delivery']); ?>
    <section class="generic-drawer-content">
        <div class="object-mail-folder-heading"><?= omoObjectMailIcon('send') ?><h2 class="generic-card-title generic-card-title--medium"><?= omoObjectMailEscape(omoObjectMailT('tracking')) ?></h2><span class="generic-badge<?= $tone ? ' generic-badge--' . $tone : '' ?>"><?= omoObjectMailEscape(omoObjectMailT('status_' . $state)) ?></span></div>
        <dl class="generic-correspondence-list object-mail-delivery"><?php foreach ($status['delivery'] as $key => $count): if (!$count) continue; ?>
            <div class="generic-correspondence-list__row object-mail-tracking-row"><dt><?= omoObjectMailEscape(omoObjectMailT($key)) ?></dt><dd class="generic-badge generic-badge--muted"><?= (int)$count ?></dd></div>
        <?php endforeach; ?></dl>
        <p class="generic-help-text"><?= omoObjectMailEscape(omoObjectMailT('history_smtp_hint')) ?></p>
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
    <div class="generic-drawer-content generic-form-grid object-mail-address-grid">
        <div class="object-mail-address-field">
            <span class="generic-form-label"><?= omoObjectMailEscape(omoObjectMailT('from')) ?></span>
            <div class="object-mail-sender">
                <span><?= omoObjectMailEscape($audience['sender']['name']) ?> &lt;<?= omoObjectMailEscape($fromAddress) ?>&gt;</span>
                <span class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT('reply_address')) ?> : <?= omoObjectMailEscape($audience['sender']['email']) ?></span>
            </div>
        </div>
        <div class="object-mail-address-field">
        <span class="generic-form-label"><?= omoObjectMailEscape(omoObjectMailT('to')) ?></span>
        <details class="generic-accordion generic-accordion--inset generic-accordion--popover" data-object-mail-picker>
            <summary class="generic-accordion__header">
                <span class="object-mail-to-line"><span class="generic-visually-hidden"><?= omoObjectMailEscape(omoObjectMailT('to')) ?></span><span data-object-mail-count data-one="<?= omoObjectMailEscape(omoObjectMailT('recipients', ['count' => 1])) ?>" data-other="<?= omoObjectMailEscape(omoObjectMailT('recipients', ['count' => '{count}'])) ?>"><?= omoObjectMailEscape(omoObjectMailT('recipients', ['count' => $selectedCount])) ?></span><span aria-hidden="true">&#9662;</span></span>
            </summary>
            <div class="generic-menu-panel generic-menu-panel--anchored object-mail-recipient-popover">
            <div class="generic-drawer-content">
                <?php if ($audience['can_send']): ?>
                <span class="generic-form-actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--compact" data-object-mail-select="all"><?= omoObjectMailEscape(omoObjectMailT('select_all')) ?></button>
                    <button type="button" class="generic-action-button generic-action-button--secondary generic-action-button--compact" data-object-mail-select="none"><?= omoObjectMailEscape(omoObjectMailT('select_none')) ?></button>
                </span>
                <?php endif; ?>
            </div>
            <ul class="object-mail-recipients generic-drawer-content" tabindex="0" aria-label="<?= omoObjectMailEscape(omoObjectMailT('audience')) ?>">
                <?php foreach ($audience['recipients'] as $member): ?><li><label class="generic-checkbox generic-checkbox--control" title="<?= omoObjectMailEscape($member['name'] . ' - ' . $member['email']) ?>"><input type="checkbox" form="object-mail-form" name="recipient_ids[]" value="<?= omoObjectMailEscape($member['member_id']) ?>" data-object-mail-recipient <?= $selectedIds === null || in_array($member['member_id'], $selectedIds, true) ? 'checked' : '' ?> <?= !$audience['can_send'] ? 'disabled' : '' ?>><span class="object-mail-recipient-identity"><span class="generic-meta-value"><?= omoObjectMailEscape($member['name']) ?></span> <span class="generic-meta">&lt;<?= omoObjectMailEscape($member['email']) ?>&gt;</span></span></label></li><?php endforeach; ?>
            </ul>
            </div>
        </details>
        </div>
    </div>
    <?php if ($audience['can_send'] && count($audience['recipients']) > 0): ?>
        <form id="object-mail-form" method="post" action="<?= omoObjectMailEscape($baseUrl) ?>" class="object-mail-form" data-object-mail-form data-object-mail-sending="<?= omoObjectMailEscape(omoObjectMailT('sending_progress')) ?>" data-object-mail-max="<?= \dbObject\ObjectMail::MAX_RECIPIENTS ?>">
            <input type="hidden" name="recipient_selection" value="1">
            <input type="hidden" name="csrf" value="<?= omoObjectMailEscape($_SESSION['objectMailCsrf']) ?>">
            <input type="hidden" name="request_key" value="<?= omoObjectMailEscape($requestKey) ?>">
            <input type="hidden" name="audience_token" value="<?= omoObjectMailEscape($audience['audience_token']) ?>">
            <input type="hidden" name="message_format" value="<?= ($_POST['message_format'] ?? '') === 'html' ? 'html' : 'plain' ?>">
            <div class="generic-drawer-content object-mail-form-content">
                <label class="object-mail-subject-field"><span class="generic-form-label"><?= omoObjectMailEscape(omoObjectMailT('compose_subject')) ?></span><input class="generic-form-control" name="subject" maxlength="250" required autofocus value="<?= omoObjectMailEscape(is_string($_POST['subject'] ?? null) ? $_POST['subject'] : '') ?>"></label>
                <div class="object-mail-body-field">
                    <label class="generic-visually-hidden" for="object-mail-message"><?= omoObjectMailEscape(omoObjectMailT('message')) ?></label>
                    <textarea id="object-mail-message" class="generic-form-control object-mail-message" name="message" maxlength="20000" rows="8" required><?= omoObjectMailEscape(is_string($_POST['message'] ?? null) ? $_POST['message'] : '') ?></textarea>
                    <div data-object-mail-editor data-placeholder="<?= omoObjectMailEscape(omoObjectMailT('compose_placeholder')) ?>" data-invalid="<?= omoObjectMailEscape(omoObjectMailT('message_invalid')) ?>"></div>
                    <p class="generic-help-text" data-object-mail-message-error role="status" hidden></p>
                </div>
                <p class="generic-help-text" data-object-mail-selection-error data-empty="<?= omoObjectMailEscape(omoObjectMailT('selection_empty')) ?>" data-too-many="<?= omoObjectMailEscape(omoObjectMailT('too_many', ['count' => \dbObject\ObjectMail::MAX_RECIPIENTS])) ?>" role="status" hidden></p>
            </div>
            <footer class="generic-drawer-footer generic-drawer-footer--sticky">
                <div class="generic-action-row generic-action-row--start">
                    <button class="generic-action-button generic-action-button--main" type="submit" <?= $selectedCount < 1 || $selectedCount > \dbObject\ObjectMail::MAX_RECIPIENTS ? 'disabled' : '' ?>><?= omoObjectMailIcon('send') ?><?= omoObjectMailEscape(omoObjectMailT('send')) ?></button>
                    <span class="generic-meta" data-object-mail-progress role="status" hidden></span>
                </div>
                <button class="generic-action-button generic-action-button--secondary" type="button" data-object-mail-close hidden><?= omoObjectMailEscape(omoObjectMailT('cancel')) ?></button>
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
