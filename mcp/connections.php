<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/common/mcp/ui.php';
omoMcpLoginIfNeeded('/mcp/connections.php');
$userId = commonGetCurrentUserId();
$_SESSION['mcpConnectionsCsrf'] ??= bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    $id = filter_var($_POST['grant_id'] ?? null, FILTER_VALIDATE_INT);
    if (is_string($csrf) && hash_equals($_SESSION['mcpConnectionsCsrf'], $csrf) && $id > 0) {
        $_SESSION['mcpNotice'] = \dbObject\McpOauthGrant::revokeOwned($id, $userId) ? 'revoked' : 'revoke_error';
    } else { $_SESSION['mcpNotice'] = 'revoke_error'; }
    header('Location: /mcp/connections.php', true, 303); exit;
}
$notice = $_SESSION['mcpNotice'] ?? null;
unset($_SESSION['mcpNotice']);
$items = \dbObject\McpOauthGrant::listOwned($userId);
omoMcpPageStart(omoMcpUiT('connections'));
?>
<p><?= omoMcpEscape(omoMcpUiT('connections_intro')) ?></p>
<?php if (!$items): ?><p><?= omoMcpEscape(omoMcpUiT('no_connections')) ?></p><?php endif; ?>
<?php foreach ($items as $item): ?>
    <section class="generic-soft-panel generic-stack">
        <h2 class="generic-card-title"><?= omoMcpEscape($item['client_name']) ?></h2>
        <p><?= omoMcpEscape($item['organization_name']) ?></p>
        <p><?= omoMcpEscape(omoMcpUiT(omoMcpCanCreateDocuments($item) ? 'allow_create' : 'scope_read_only')) ?></p>
        <p><?= omoMcpEscape(omoMcpUiT('expires', ['date' => date('Y-m-d H:i', (int)$item['refresh_expires_at'])])) ?></p>
        <form method="post" action="/mcp/connections.php">
            <input type="hidden" name="csrf" value="<?= omoMcpEscape($_SESSION['mcpConnectionsCsrf']) ?>">
            <input type="hidden" name="grant_id" value="<?= (int)$item['id'] ?>">
            <button class="generic-action-button generic-action-button--secondary"><?= omoMcpEscape(omoMcpUiT('revoke')) ?></button>
        </form>
    </section>
<?php endforeach; ?>
<?php if (in_array($notice, ['revoked', 'revoke_error'], true)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.commonNotify(<?= json_encode(omoMcpUiT($notice), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        <?= json_encode($notice === 'revoked' ? 'success' : 'error') ?>);
});
</script>
<?php endif; ?>
<?php omoMcpPageEnd(); ?>
