<?php
require_once __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__) . '/common/mcp/ui.php';
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    omoMcpOauthError('invalid_request', 'Use GET or POST.', 405);
}
// Expired requests cannot survive indefinitely in a browser session; permit separate tabs.
$_SESSION['mcpPending'] = array_filter($_SESSION['mcpPending'] ?? [], static fn ($request) => $request['expires'] > time());
$requestId = $_GET['request'] ?? '';
if (!is_string($requestId)) omoMcpOauthError('invalid_request', 'Invalid request identifier.');
if ($requestId === '' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $clientId = $_GET['client_id'] ?? '';
    $client = is_string($clientId) ? \dbObject\McpOauthClient::findByClientId($clientId) : null;
    if (!$client) omoMcpOauthError('invalid_client', 'Unknown OAuth client.');
    try { $request = omoMcpValidateAuthorization($_GET, $client); }
    catch (InvalidArgumentException $error) { omoMcpOauthError('invalid_request', $error->getMessage()); }
    $requestId = bin2hex(random_bytes(24));
    if (count($_SESSION['mcpPending']) >= 10) array_shift($_SESSION['mcpPending']);
    $_SESSION['mcpPending'][$requestId] = ['request' => $request, 'expires' => time() + 900,
        'csrf' => bin2hex(random_bytes(32))];
    header('Location: /mcp/authorize.php?request=' . $requestId, true, 303);
    exit;
}
$pending = $_SESSION['mcpPending'][$requestId] ?? null;
if (!$pending) {
    http_response_code(400);
    omoMcpPageStart(omoMcpUiT('title'));
    echo '<p class="generic-soft-panel generic-soft-panel--elevated">' . omoMcpEscape(omoMcpUiT('invalid')) . '</p>';
    omoMcpPageEnd(); exit;
}
omoMcpLoginIfNeeded('/mcp/authorize.php?request=' . $requestId);
$userId = commonGetCurrentUserId();
$organizations = omoMcpEligibleOrganizations($userId);
$request = $pending['request'];
$allowCreate = omoMcpCanCreateDocuments($request);
$allowMail = omoMcpCanSendMail($request);
$allowEvent = omoMcpCanCreateEvents($request);
$allowDecision = omoMcpCanCreateDecisions($request);
$client = \dbObject\McpOauthClient::findByClientId($request['client_id']);
if (!$client) omoMcpOauthError('invalid_client', 'Unknown OAuth client.');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($pending['csrf'], $csrf)) {
        omoMcpOauthError('invalid_request', 'Invalid consent form.', 403);
    }
    $decision = $_POST['decision'] ?? '';
    if ($decision === 'deny') {
        unset($_SESSION['mcpPending'][$requestId]);
        omoMcpRedirect($request, ['error' => 'access_denied']);
    }
    $organizationId = filter_var($_POST['organization_id'] ?? null, FILTER_VALIDATE_INT);
    $allowedIds = array_map(static fn ($org) => (int)$org->getId(), $organizations);
    if ($decision !== 'allow' || !in_array($organizationId, $allowedIds, true)) {
        omoMcpOauthError('access_denied', 'Organization unavailable.', 403);
    }
    $code = \dbObject\McpOauthGrant::issueCode($client, $userId, $organizationId, $request);
    unset($_SESSION['mcpPending'][$requestId]);
    omoMcpRedirect($request, ['code' => $code]);
}
omoMcpPageStart(omoMcpUiT('title'), $request['redirect_uri']);
?>
<section class="generic-soft-panel generic-soft-panel--elevated mcp-request">
    <p><?= omoMcpEscape(omoMcpUiT($allowMail || $allowEvent || $allowDecision ? 'request_write' : ($allowCreate ? 'request_create' : 'request'), ['client' => (string)$client->get('name')])) ?></p>
    <p class="mcp-muted"><?= omoMcpEscape(omoMcpUiT('identity', ['name' => commonGetCurrentUserDisplayName()])) ?></p>
</section>
<div class="mcp-layout">
    <section class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack" aria-labelledby="mcp-permissions-title">
        <h2 class="generic-card-title generic-card-title--section" id="mcp-permissions-title"><?= omoMcpEscape(omoMcpUiT('permissions')) ?></h2>
        <div class="mcp-permission">
            <h3 class="generic-card-title"><?= omoMcpEscape(omoMcpUiT('read_heading')) ?></h3>
            <p><?= omoMcpEscape(omoMcpUiT('scope')) ?></p>
        </div>
        <?php if ($allowCreate): ?>
        <div class="generic-soft-panel generic-soft-panel--tinted mcp-permission">
            <h3 class="generic-card-title"><?= omoMcpEscape(omoMcpUiT('create_heading')) ?></h3>
            <p><?= omoMcpEscape(omoMcpUiT('scope_create')) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($allowMail): ?>
        <div class="generic-soft-panel generic-soft-panel--tinted mcp-permission">
            <h3 class="generic-card-title"><?= omoMcpEscape(omoMcpUiT('mail_heading')) ?></h3>
            <p><?= omoMcpEscape(omoMcpUiT('scope_mail')) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($allowDecision): ?>
        <div class="generic-soft-panel generic-soft-panel--tinted mcp-permission">
            <h3 class="generic-card-title"><?= omoMcpEscape(omoMcpUiT('decision_heading')) ?></h3>
            <p><?= omoMcpEscape(omoMcpUiT('scope_decision')) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($allowEvent): ?>
        <div class="generic-soft-panel generic-soft-panel--tinted mcp-permission">
            <h3 class="generic-card-title"><?= omoMcpEscape(omoMcpUiT('event_heading')) ?></h3>
            <p><?= omoMcpEscape(omoMcpUiT('scope_event')) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!$allowCreate && !$allowMail && !$allowEvent && !$allowDecision): ?>
        <p class="generic-soft-panel generic-soft-panel--tinted"><?= omoMcpEscape(omoMcpUiT('scope_read_only')) ?></p>
        <?php endif; ?>
        <p class="mcp-muted"><?= omoMcpEscape(omoMcpUiT('destination', ['origin' => $request['redirect_uri']])) ?></p>
    </section>
    <form method="post" action="/mcp/authorize.php?request=<?= omoMcpEscape($requestId) ?>" class="generic-soft-panel generic-soft-panel--elevated generic-soft-panel--stack" aria-labelledby="mcp-organization-title">
        <h2 class="generic-card-title generic-card-title--section" id="mcp-organization-title"><?= omoMcpEscape(omoMcpUiT('choose_heading')) ?></h2>
        <p><?= omoMcpEscape(omoMcpUiT('choose_hint')) ?></p>
        <input type="hidden" name="csrf" value="<?= omoMcpEscape($pending['csrf']) ?>">
        <?php if ($organizations): ?>
        <div class="generic-form-field">
            <label class="generic-form-label" for="organization_id"><?= omoMcpEscape(omoMcpUiT('organization')) ?></label>
            <select class="generic-form-control" name="organization_id" id="organization_id" required>
                <?php foreach ($organizations as $organization): ?>
                    <option value="<?= (int)$organization->getId() ?>"><?= omoMcpEscape((string)$organization->get('name')) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?><p class="generic-soft-panel generic-soft-panel--tinted"><?= omoMcpEscape(omoMcpUiT('empty')) ?></p><?php endif; ?>
        <div class="generic-stack">
            <?php if ($organizations): ?>
            <button type="submit" class="generic-action-button generic-action-button--main generic-action-button--wide" name="decision" value="allow"><?= omoMcpEscape(omoMcpUiT($allowMail || $allowEvent || $allowDecision ? 'allow_scopes' : ($allowCreate ? 'allow_create' : 'allow'))) ?></button>
            <?php endif; ?>
            <button type="submit" class="generic-action-button generic-action-button--secondary generic-action-button--wide" name="decision" value="deny" formnovalidate><?= omoMcpEscape(omoMcpUiT('deny')) ?></button>
        </div>
        <p class="mcp-muted"><?= omoMcpEscape(omoMcpUiT('revoke_hint')) ?></p>
    </form>
</div>
<footer class="mcp-footer"><a href="/mcp/connections.php"><?= omoMcpEscape(omoMcpUiT('connections')) ?></a></footer>
<?php omoMcpPageEnd(); ?>
