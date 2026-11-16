// Requires local Docker and a test-only Playwright installation passed as the first argument.
const assert = require('node:assert/strict');
const {spawn} = require('node:child_process');
const {createInterface} = require('node:readline');
const {createServer} = require('node:http');
const {chromium} = require(process.argv[2] || 'playwright');

(async () => {
    const callbackMethods = [];
    const callbackServer = createServer((request, response) => {
        callbackMethods.push(request.method);
        response.writeHead(200, {'Content-Type': 'text/html'});
        response.end('OAuth callback received');
    });
    await new Promise(resolve => callbackServer.listen(0, '127.0.0.1', resolve));
    const callbackUrl = 'http://127.0.0.1:' + callbackServer.address().port + '/callback';
    const fixture = spawn('docker', ['compose', 'exec', '-T', 'app', 'php', 'tests/mcp_consent_browser_fixture.php', callbackUrl],
        {stdio: ['pipe', 'pipe', 'inherit']});
    const closed = new Promise(resolve => fixture.on('close', resolve));
    const lines = createInterface({input: fixture.stdout});
    let browser;
    try {
        const first = await lines[Symbol.asyncIterator]().next();
        assert(!first.done, 'Local fixture must start');
        const config = JSON.parse(first.value);
        assert.equal(new URL(config.origin).hostname, 'localtest.me');
        browser = await chromium.launch({headless: true, channel: process.env.MCP_BROWSER_CHANNEL || 'chrome'});
        const context = await browser.newContext({ignoreHTTPSErrors: true}); // Local Docker certificate only.
        const login = await context.request.post(config.origin + '/common/login_password.php', {
            form: {email: config.email, password: config.password, return_to: '/mcp/connections.php'},
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        assert.equal((await login.json()).status, 'ok');
        const page = await context.newPage();
        const violations = [];
        let consentStatus;
        let consentError;
        page.on('response', async response => {
            if (response.request().method() === 'POST' && new URL(response.url()).pathname === '/mcp/authorize.php') {
                consentStatus = response.status();
                if (response.headers()['content-type']?.includes('application/json')) consentError = (await response.json()).error_description;
            }
        });
        page.on('console', message => { if (message.text().includes('form-action')) violations.push(message.text()); });
        for (const decision of ['allow', 'deny']) {
            await page.goto(config.origin + config.authorization);
            for (const heading of ['Créer des documents', 'Envoyer des e-mails', 'Créer des événements', 'Créer des scrutins', 'Créer et modifier des projets']) {
                assert.equal(await page.getByRole('heading', {name: heading, exact: true}).count(), 1, 'Fresh consent displays each action scope');
            }
            const callback = page.waitForURL(callbackUrl + '?**', {timeout: 5000});
            // Attach a rejection handler immediately while Chromium processes the form submission.
            callback.catch(() => {});
            await page.locator('button[name="decision"][value="' + decision + '"]').click({noWaitAfter: true});
            if (process.argv.includes('--expect-blocked')) {
                await assert.rejects(callback);
                await page.waitForTimeout(300);
                assert(violations.length > 0, 'Original policy must reproduce the browser CSP failure');
                console.log('mcp_consent_browser_test: original CSP failure reproduced');
                return;
            }
            await callback.catch(error => {
                throw new Error('OAuth callback failed, consent HTTP ' + consentStatus + ', error ' + consentError + ', CSP violations ' + violations.length, {cause: error});
            });
            const result = new URL(page.url()).searchParams;
            assert.equal(result.get('state'), 'test-state');
            assert.equal(result.get('iss'), config.origin);
            if (decision === 'allow') {
                assert(result.get('code'));
                const authorization = new URL(config.authorization, config.origin).searchParams;
                const tokenResponse = await context.request.post(config.origin + '/mcp/token.php', {form: {
                    grant_type: 'authorization_code', client_id: authorization.get('client_id'),
                    code: result.get('code'), redirect_uri: callbackUrl,
                    resource: authorization.get('resource').replace(/\/$/, ''), code_verifier: config.code_verifier
                }});
                assert.equal(tokenResponse.status(), 200, 'Browser consent exchanges a code for the endpoint without a trailing slash');
                const tokens = await tokenResponse.json();
                assert.deepEqual(tokens.scope.split(' '), ['organization:read', 'documents:create', 'mail:send', 'events:create', 'decisions:create', 'projects:write']);
                for (const path of ['/mcp', '/mcp/']) {
                    const headers = {Authorization: 'Bearer ' + tokens.access_token, 'MCP-Protocol-Version': '2025-11-25'};
                    const initialized = await context.request.post(config.origin + path, {headers, maxRedirects: 0, data: {
                        jsonrpc: '2.0', id: 1, method: 'initialize', params: {
                            protocolVersion: '2025-11-25', capabilities: {}, clientInfo: {name: 'browser-reconnection-test', version: '1'}
                        }
                    }});
                    assert.equal(initialized.status(), 200, 'MCP-only organization initializes without redirect');
                    assert((await initialized.json()).result.capabilities.tools);
                    const listed = await context.request.post(config.origin + path, {headers, maxRedirects: 0,
                        data: {jsonrpc: '2.0', id: 2, method: 'tools/list'}});
                    assert.equal(listed.status(), 200, 'Fresh browser consent discovers tools without redirect');
                    const names = (await listed.json()).result.tools.map(tool => tool.name);
                    for (const name of ['omo_send_object_email', 'omo_create_event', 'omo_create_document', 'omo_create_decision', 'omo_create_project']) {
                        assert(names.includes(name), 'Discovery includes each authorized action');
                    }
                }
            } else assert.equal(result.get('error'), 'access_denied');
        }
        assert.equal(violations.length, 0, 'Registered OAuth callback must not trigger CSP');
        assert(callbackMethods.length >= 2 && callbackMethods.every(method => method === 'GET'), 'Callback redirects use GET');
        await page.goto(config.origin + config.authorization);
        let escaped = false;
        await context.route('https://unregistered.example.invalid/**', route => { escaped = true; return route.abort(); });
        await page.locator('form').evaluate(form => { form.action = 'https://unregistered.example.invalid/capture'; });
        await page.locator('button[name="decision"][value="allow"]').click({noWaitAfter: true});
        await page.waitForTimeout(300);
        assert(violations.length > 0, 'Unregistered origin must still be blocked by CSP');
        assert.equal(escaped, false, 'No form data may leave for the unregistered origin');
        console.log('mcp_consent_browser_test: OK (accept, deny, unrelated origin blocked)');
    } finally {
        if (browser) await browser.close();
        fixture.stdin.end('done\n');
        lines.close();
        await closed;
        await new Promise(resolve => callbackServer.close(resolve));
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
