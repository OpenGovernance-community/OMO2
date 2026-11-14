'use strict';
// Keep the upstream worker intact except for the shared credential gate.
const fs = require('node:fs');
const file = process.argv[2];
if (!file) throw new Error('Worker bundle path required');
const configFile = process.argv[3];
if (configFile) {
    let config = fs.readFileSync(configFile, 'utf8');
    if (!config.includes('name = "internet"')) {
        const anchor = '(name = "main", worker = .mainWorker),';
        if (!config.includes(anchor)) throw new Error('Unsupported EtherCalc workerd network configuration');
        config = config.replace(anchor, anchor + '\n    # OMO callback URLs are signed with the server key before any fetch.\n    (name = "internet", network = (allow = ["public", "private"], tlsOptions = (trustBrowserCas = true))),');
        fs.writeFileSync(configFile, config);
    }
}
let source = fs.readFileSync(file, 'utf8');
if (!source.includes('async function omoVerifyShareAccess(')) {
    const original = 'async function verifyAuth(key, room, supplied) {\n  if (supplied === "0") return false;';
    if (!source.includes(original)) throw new Error('Unsupported EtherCalc verifyAuth implementation');
    const verifier = `async function omoVerifyShareAccess(key, room, token) {
  try {
    const parts = token.split('.');
    if (!key || parts.length !== 3 || parts[0] !== 'omo') return false;
    const expected = await computeAuth(key, 'omo.' + parts[1]);
    if (!timingSafeEqualString(expected, parts[2])) return false;
    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
    if (payload.room !== room || !Number.isInteger(payload.expiresAt) || payload.expiresAt <= Date.now() / 1000) return false;
    const url = new URL(payload.callback);
    if (!['https:', 'http:'].includes(url.protocol)) return false;
    url.searchParams.set('room', room);
    url.searchParams.set('auth', token);
    const response = await fetch(url, {redirect: 'manual', signal: AbortSignal.timeout(5000)});
    return response.ok && (await response.json()).canEdit === true;
  } catch (error) { console.warn('OMO share access check failed: ' + String(error?.message || error)); return false; }
}
async function verifyAuth(key, room, supplied, allowShared = false) {
  if (supplied.startsWith('omo.')) return allowShared && await omoVerifyShareAccess(key, room, supplied);
  if (supplied === "0") return false;`;
    source = source.replace(original, verifier);
    const wsGate = 'verifyAuth(env2.ETHERCALC_KEY, messageRoom, messageAuth)';
    if (!source.includes(wsGate)) throw new Error('Unsupported EtherCalc WebSocket gate');
    source = source.replace(wsGate, 'verifyAuth(env2.ETHERCALC_KEY, messageRoom, messageAuth, true)');
    const dispatch = 'const ctx = this.#buildWsContext(ws, attachment, parsed.room, perMessageAuth);';
    if (!source.includes(dispatch)) throw new Error('Unsupported EtherCalc WebSocket dispatch');
    source = source.replace(dispatch, `if (attachment.room !== parsed.room || (this.#env.ETHERCALC_KEY && !await verifyAuth(this.#env.ETHERCALC_KEY, parsed.room, perMessageAuth || attachment.auth || '0', true))) {
      ws.close(1008, 'Access denied'); return;
    }
    if ((perMessageAuth || attachment.auth || '').startsWith('omo.') && parsed.type === 'execute' && /^submitform\\b/i.test(parsed.cmdstr || '')) return;
    ${dispatch}`);
    const apply = 'await this.#applyCommandAndMirror(nameToMirror, cmdstr);';
    if (!source.includes(apply)) throw new Error('Unsupported EtherCalc command application');
    source = source.replace(apply, `if (messageAuth.startsWith('omo.')) {
          const current = await this.#getSpreadsheet();
          const allowed = new Set(current.findCrossSheetRefs());
          const probe = createSpreadsheet({snapshot: current.createSpreadsheetSave()});
          probe.executeCommand(cmdstr);
          if (probe.findCrossSheetRefs().some(name => !allowed.has(name))) throw new Error('Shared editors cannot introduce cross-sheet access');
        }
        ${apply}`);
    // Never forward an OMO editor's permanent credential to shared collaborators.
    source = source.replaceAll('const frame = encodeMessage(msg);', 'const {auth: omittedCredential, ...safeMessage} = msg;\n    const frame = encodeMessage(safeMessage);');
    source = source.replaceAll('peer.send(frame);', `const peerAccess = peer.deserializeAttachment() || {};
        if (this.#env.ETHERCALC_KEY && !await verifyAuth(this.#env.ETHERCALC_KEY, peerAccess.room || '', peerAccess.auth || '0', true)) {
          peer.close(1008, 'Access denied'); continue;
        }
        peer.send(frame);`);
    source = source.replace('#broadcast(skip, msg) {', 'async #broadcast(skip, msg) {')
        .replace('#broadcastAll(msg) {', 'async #broadcastAll(msg) {')
        .replace('if (includeSelf) this.#broadcastAll(msg);', 'if (includeSelf) await this.#broadcastAll(msg);')
        .replace('else this.#broadcast(ws, msg);', 'else await this.#broadcast(ws, msg);');
    // Upstream /edit and /app otherwise reveal the permanent room credential to any reader.
    const redirectGate = 'const key = c.env.ETHERCALC_KEY;\n    const redirect = await buildRoomRedirect(';
    if (!source.includes(redirectGate)) throw new Error('Unsupported EtherCalc redirect gate');
    source = source.replace(redirectGate, `const key = c.env.ETHERCALC_KEY;
    if (key && mode !== 'view' && !await verifyAuth(key, room, c.req.query('auth') ?? '0')) return new Response('Forbidden', {status: 403});
    const redirect = await buildRoomRedirect(`);
    const appGate = 'const app = new Hono2();';
    if (!source.includes(appGate)) throw new Error('Unsupported EtherCalc HTTP application');
    source = source.replace(appGate, `const app = new Hono2();
  app.use('*', async (c, next) => {
    const key = c.env.ETHERCALC_KEY;
    if (!key) return next();
    const path = c.req.path;
    if ((path.startsWith('/socket.io/') && path !== '/socket.io/socket.io.js') || ['/_', '/_new', '/=_new'].includes(path)) return c.text('Forbidden', 403);
    const match = /^\\/_\\/([^/]+)/.exec(path);
    if (match) {
      const room = decodeURIComponent(match[1]).replace(/\\.(html|csv|json|xlsx|ods|fods|md)$/, '');
      if (!await verifyAuth(key, room, c.req.query('auth') ?? '0')) return c.text('Forbidden', 403);
    }
    return next();
  });`);
    fs.writeFileSync(file, source);
}
