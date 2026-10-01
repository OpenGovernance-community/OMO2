'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM, VirtualConsole} = require('jsdom');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const settle = () => new Promise(resolve => setTimeout(resolve, 0));
const plain = value => JSON.parse(JSON.stringify(value));

// Load the actual editor functions without initializing unrelated property drawers.
function loadFunctions(window, file, names) {
    const source = read(file);
    names.forEach(name => {
        const start = source.indexOf('function ' + name + '(');
        assert(start >= 0, name);
        const end = source.indexOf('\nfunction ', start + 1);
        window.eval(source.slice(start, end));
    });
}

(async function () {
    const virtualConsole = new VirtualConsole();
    const dom = new JSDOM('<!doctype html><body></body>', {runScripts: 'outside-only', url: 'https://omo.localtest.me/', virtualConsole});
    const {window} = dom, {document} = window;
    window.fetch = async () => ({ok: true, json: async () => ({extended: 'Autorité étendue', extended_help: 'Activation requise'})});
    window.eval(read('common/permissions/editor.js'));
    window.adminLexiconLabel = 'Admins';
    window.syncPermissionSummary = () => {};
    window.escapeHtml = value => String(value);
    window.omoHolonTemplateEscapeHtml = window.escapeHtml;
    window.omoHolonTemplateTexts = {};
    loadFunctions(window, 'omo/api/holons/editor.js', ['normalizePermissionRanges', 'getPermissionProfiles', 'getPermissionRangeLabel', 'getPermissionAssignmentsForKey', 'readPermissionRowAssignments', 'readPermissions', 'setPermissionRowRanges', 'bindPermissionRow']);
    loadFunctions(window, 'omo/api/parameters/holon-templates/templates.js', ['omoHolonTemplateNormalizePermissionRanges', 'omoHolonTemplateGetPermissionRangeLabel', 'omoHolonTemplateGetPermissionAssignmentsForKey', 'omoHolonTemplateReadPermissionRowAssignments', 'omoHolonTemplateReadPermissions', 'omoHolonTemplateSetPermissionRowRanges', 'omoHolonTemplateBindPermissionRow']);
    window.elements = window.omoHolonTemplateElements = {permissions: document.body};
    window.omoHolonTemplateGetPermissionProfiles = window.getPermissionProfiles;
    const profiles = window.getPermissionProfiles();
    const ranges = [{key: 'self', label: 'Soi'}, {key: 'descendants', label: 'Descendants'}];
    const assignments = {member: ['self', {range: 'descendants', is_extended: true}], admin: [], collective: ['descendants']};
    for (const prefix of ['', 'omoHolonTemplate']) {
        const fn = name => window[prefix ? prefix + name[0].toUpperCase() + name.slice(1) : name];
        const row = document.createElement('div');
        row.dataset.permissionKey = 'CAN_MOVE_HOLON';
        row.innerHTML = '<div data-permission-tokens></div><select data-permission-select><option value=""></option><option value="descendants">Descendants</option></select>';
        document.body.append(row);
        const map = {member: {CAN_MOVE_HOLON: assignments.member}, admin: {}, collective: {CAN_MOVE_HOLON: assignments.collective}};
        fn('setPermissionRowRanges')(row, fn('getPermissionAssignmentsForKey')(map, 'CAN_MOVE_HOLON'), ranges, profiles);
        fn('bindPermissionRow')(row, ranges);
        assert.deepEqual(plain(fn('readPermissionRowAssignments')(row)), assignments, prefix + ': reopening must preserve dormant and ordinary grants');
        assert.deepEqual(plain(fn('readPermissions')()), map, 'Saving must not turn implicit admin rights into explicit grants');
        const scope = row.querySelector('[data-permission-token="descendants"]');
        const admin = scope.querySelector('[data-permission-profile="admin"]');
        assert(admin.checked && admin.disabled, 'Member rights must show as included for admins on opening');
        admin.click();
        assert(admin.checked, 'Implicit admin rights cannot be unchecked independently');
        const extended = scope.querySelector('[data-permission-extended]');
        assert(extended.checked && !extended.disabled);
        extended.click();
        assert.deepEqual(plain(fn('readPermissionRowAssignments')(row).member), ['self', 'descendants']);
        extended.click();
        const member = scope.querySelector('[data-permission-profile="member"]');
        member.click();
        assert(!admin.checked && !admin.disabled, 'Removing member rights restores the unassigned admin state');
        assert(extended.disabled, 'Collective-only grants cannot depend on a personal activation');
        assert.deepEqual(plain(fn('readPermissionRowAssignments')(row).collective), ['descendants']);
        member.click();
        assert(admin.checked && admin.disabled, 'Checking members must immediately check and disable admin');
        scope.querySelector('[data-permission-remove]').click();
        assert.deepEqual(plain(fn('readPermissionRowAssignments')(row)), {member: ['self'], admin: [], collective: []}, 'Removal must handle object assignments');
        const select = row.querySelector('select');
        select.value = 'descendants';
        select.dispatchEvent(new window.Event('change', {bubbles: true}));
        assert.deepEqual(plain(fn('readPermissionRowAssignments')(row).member), ['self', 'descendants'], 'New scopes are ordinary by default');
        // Explicit admin rights survive toggling members, rerendering and saving.
        let currentScope = row.querySelector('[data-permission-token="descendants"]');
        currentScope.querySelector('[data-permission-profile="member"]').click();
        currentScope.querySelector('[data-permission-profile="admin"]').click();
        currentScope.querySelector('[data-permission-profile="member"]').click();
        assert.deepEqual(plain(fn('readPermissions')().admin), {CAN_MOVE_HOLON: ['descendants']});
        const saved = fn('readPermissionRowAssignments')(row);
        fn('setPermissionRowRanges')(row, saved, ranges, profiles);
        currentScope = row.querySelector('[data-permission-token="descendants"]');
        const explicitAdmin = currentScope.querySelector('[data-permission-profile="admin"]');
        assert(explicitAdmin.checked && explicitAdmin.disabled);
        currentScope.querySelector('[data-permission-profile="member"]').click();
        assert(explicitAdmin.checked && !explicitAdmin.disabled, 'An explicit admin grant must survive removing member rights');
        explicitAdmin.click();
        assert.deepEqual(plain(fn('readPermissions')().admin), {}, 'An explicit admin grant can still be removed');
        row.remove();
    }

    window.eval(read('common/assets/topbar.js'));
    const button = document.createElement('button');
    button.setAttribute('data-topbar-admin-mode-toggle', '');
    button.dataset.adminModeUrl = '/common/extended_authorities.php';
    button.dataset.adminModeOrganizationId = '42';
    button.dataset.adminModeEnabled = '1';
    button.dataset.adminModeConfirm = 'Usage temporaire au service de l organisation';
    button.dataset.adminModeCsrf = 'test-token';
    document.body.append(button);
    const posts = [];
    let confirmations = 0, accepted = false, reloads = 0;
    virtualConsole.on('jsdomError', error => {
        if (error.message.includes('navigation')) reloads++;
        else throw error;
    });
    window.confirm = notice => { assert.equal(notice, button.dataset.adminModeConfirm); confirmations++; return accepted; };
    window.fetch = async (url, options) => { posts.push({url, fields: Object.fromEntries(options.body.entries())}); return {ok: true, json: async () => ({status: true})}; };
    button.click();
    await settle();
    assert.equal(posts.length, 0, 'Cancelling the notice must not activate anything');
    accepted = true;
    button.click();
    await settle();
    assert.equal(posts[0].url, '/common/extended_authorities.php');
    assert.equal(posts[0].fields.enabled, '1');
    assert.equal(posts[0].fields.csrf_token, 'test-token');
    assert.equal(posts[0].fields.organization_id, '42');
    button.disabled = false;
    button.dataset.adminModeEnabled = '0';
    button.click();
    await settle();
    assert.equal(posts[1].fields.enabled, '0');
    assert.equal(confirmations, 2, 'Deactivation does not require another confirmation');
    assert.equal(reloads, 2, 'Both transitions refresh the visible permission matrix');
    dom.window.close();
    console.log('extended_authorities_editor_test: OK');
})().catch(error => { console.error(error); process.exitCode = 1; });
