<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/common/object_mail/ui.php';

$oid = (int)($_SESSION['currentOrganization'] ?? 0);
$mailId = isset($_GET['mail_id']) ? filter_var($_GET['mail_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
commonReleaseReadOnlySession();
try {
    if ($mailId === false) throw new DomainException('Message indisponible.');
    $detail = $mailId !== null ? \dbObject\ObjectMail::getHistoryDetail($oid, $mailId) : null;
    $history = $detail === null ? \dbObject\ObjectMail::getHistory($oid) : [];
} catch (DomainException $error) {
    http_response_code(403);
    echo '<p class="generic-drawer-content generic-description">' . omoObjectMailEscape(omoObjectMailT('history_unavailable')) . '</p>';
    exit;
} catch (Throwable $error) {
    error_log('OMO mail history failed: ' . $error->getMessage());
    http_response_code(500);
    echo '<p class="generic-drawer-content generic-description">' . omoObjectMailEscape(omoObjectMailT('history_unavailable')) . '</p>';
    exit;
}
?>
<link rel="stylesheet" href="<?= omoObjectMailEscape(commonAssetUrl('/common/object_mail/ui.css')) ?>">
<div class="object-mail-mailbox" data-topbar-modal-max-width="860px">
<?php if ($detail !== null):
    $mail = $detail['mail'];
    $expired = (string)$mail->get('message') === '';
    $delivery = [];
    foreach ($detail['recipients'] as $recipient) {
        $key = (string)$recipient->get('status');
        $delivery[$key] = ($delivery[$key] ?? 0) + 1;
    }
    [$state, $tone] = omoObjectMailDeliveryState($delivery);
?>
    <nav class="generic-drawer-footer" aria-label="<?= omoObjectMailEscape(omoObjectMailT('folder_sent')) ?>">
        <a class="generic-action-button generic-action-button--secondary generic-action-button--compact" href="/omo/api/object_mail/history.php" data-topbar-mail-url="/omo/api/object_mail/history.php"><?= omoObjectMailIcon('back') ?><?= omoObjectMailEscape(omoObjectMailT('history_back')) ?></a>
        <span class="generic-badge<?= $tone ? ' generic-badge--' . $tone : '' ?>"><?= omoObjectMailEscape(omoObjectMailT('status_' . $state)) ?></span>
    </nav>
    <article class="generic-drawer-content object-mail-reading">
        <h2 class="object-mail-subject"><?= omoObjectMailEscape($expired ? omoObjectMailT('history_expired') : $mail->get('subject')) ?></h2>
        <div class="object-mail-letterhead">
            <span class="object-mail-avatar" aria-hidden="true"><?= omoObjectMailIcon() ?></span>
            <div class="object-mail-letterhead__copy">
                <div class="object-mail-letterhead__line">
                    <strong><?= omoObjectMailEscape(omoObjectMailT('you')) ?></strong>
                    <time class="generic-meta" datetime="<?= gmdate(DATE_ATOM, (int)$mail->get('created_at')) ?>"><?= omoObjectMailEscape(date('d.m.Y H:i', (int)$mail->get('created_at'))) ?></time>
                </div>
                <p class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT($mail->get('IDmcp_oauth_grant') !== null ? 'history_mcp' : 'history_native')) ?></p>
                <details class="object-mail-addresses">
                    <summary class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT('to')) ?> : <?= omoObjectMailEscape(omoObjectMailT('recipients', ['count' => (int)$mail->get('recipient_count')])) ?></summary>
                    <ul class="object-mail-address-list">
                    <?php foreach ($detail['recipients'] as $recipient): ?>
                        <li><?= omoObjectMailEscape($recipient->get('member_id') === '' ? omoObjectMailT('history_recipient_expired') : $recipient->get('email')) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </details>
            </div>
        </div>
        <div class="object-mail-history-message"><?= $expired ? omoObjectMailEscape(omoObjectMailT('history_retention')) : \dbObject\ObjectMail::renderMessage($mail->get('message'), $mail->get('message_format')) ?></div>
        <details class="generic-accordion generic-accordion--inset" <?= in_array($state, ['issue', 'cancelled'], true) ? 'open' : '' ?>>
            <summary class="generic-accordion__header"><span><?= omoObjectMailEscape(omoObjectMailT('tracking')) ?></span><span class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT('recipients', ['count' => (int)$mail->get('recipient_count')])) ?></span></summary>
            <div class="generic-drawer-content">
                <p class="generic-help-text"><?= omoObjectMailEscape(omoObjectMailT('history_smtp_hint')) ?></p>
                <ul class="generic-correspondence-list">
                <?php foreach ($detail['recipients'] as $recipient): ?>
                    <li class="generic-correspondence-list__row object-mail-tracking-row"><span><?= omoObjectMailEscape($recipient->get('member_id') === '' ? omoObjectMailT('history_recipient_expired') : $recipient->get('email')) ?></span><span class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT((string)$recipient->get('status'))) ?></span></li>
                <?php endforeach; ?>
                </ul>
            </div>
        </details>
    </article>
<?php else: ?>
    <header class="generic-drawer-content">
        <div class="object-mail-folder-heading"><?= omoObjectMailIcon('send') ?><h2 class="generic-card-title generic-card-title--medium"><?= omoObjectMailEscape(omoObjectMailT('folder_sent')) ?></h2><span class="generic-badge generic-badge--muted"><?= count($history) ?></span></div>
        <p class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT('folder_scope')) ?></p>
    </header>
    <?php if (!$history): ?>
    <div class="generic-empty-hero"><p class="generic-empty-hero__text"><?= omoObjectMailEscape(omoObjectMailT('history_empty')) ?></p></div>
    <?php else: ?>
    <ul class="generic-correspondence-list">
    <?php foreach ($history as $item):
        $url = '/omo/api/object_mail/history.php?mail_id=' . (int)$item['id'];
        [$state, $tone] = omoObjectMailDeliveryState($item['delivery']);
    ?>
        <li><a class="generic-correspondence-list__row object-mail-row" href="<?= omoObjectMailEscape($url) ?>" data-topbar-mail-url="<?= omoObjectMailEscape($url) ?>">
            <span class="object-mail-row__icon" aria-hidden="true"><?= omoObjectMailIcon() ?></span>
            <span class="object-mail-row__main">
                <strong class="object-mail-row__subject"><?= omoObjectMailEscape($item['content_expired'] ? omoObjectMailT('history_expired') : $item['subject']) ?></strong>
                <span class="object-mail-row__preview"><?= omoObjectMailEscape($item['content_expired'] ? omoObjectMailT('history_expired') : preg_replace('/\s+/u', ' ', $item['preview'])) ?></span>
                <span class="generic-meta"><?= omoObjectMailEscape(omoObjectMailT('to')) ?> : <?= omoObjectMailEscape(omoObjectMailT('recipients', ['count' => (int)$item['recipient_count']])) ?> &middot; <?= omoObjectMailEscape(omoObjectMailT($item['via_mcp'] ? 'history_mcp' : 'history_native')) ?></span>
            </span>
            <span class="object-mail-row__aside">
                <time class="generic-meta" datetime="<?= gmdate(DATE_ATOM, (int)$item['created_at']) ?>"><?= omoObjectMailEscape(date('d.m.Y H:i', (int)$item['created_at'])) ?></time>
                <span class="generic-badge<?= $tone ? ' generic-badge--' . $tone : '' ?>"><?= omoObjectMailEscape(omoObjectMailT('status_' . $state)) ?></span>
            </span>
        </a></li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
<?php endif; ?>
    <footer class="generic-drawer-content">
        <details class="object-mail-retention"><summary class="generic-help-text"><?= omoObjectMailEscape(omoObjectMailT('retention_title')) ?></summary><p class="generic-help-text"><?= omoObjectMailEscape(omoObjectMailT('history_retention')) ?></p></details>
    </footer>
</div>
