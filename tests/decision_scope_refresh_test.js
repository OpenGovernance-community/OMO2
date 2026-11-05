'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../omo/api/decision/index.js'), 'utf8');
function extract(start, end) {
    const first = source.indexOf(start);
    const last = source.indexOf(end, first);
    assert(first >= 0 && last > first, 'Cannot locate ' + start);
    return source.slice(first, last);
}

const functions = extract('function omoDecisionsNormalizeScopePreference(', 'function omoDecisionsGetPreferencesContextKey(')
    + extract('function omoDecisionGetCurrentScope()', 'function omoDecisionsFindOptionLabel(')
    + extract('function omoDecisionsBuildScopeUrl(', 'function omoDecisionsApplyViewFilters(')
    + extract('function omoDecisionsInitializeViewFilter()', 'window.omoToggleDecisionsScope');

function simulate({preferred, serverDefault, allowed, dashboard = false, openDecisionId = 0}) {
    let currentScope;
    let synchronized = false;
    const requests = [];
    const pending = [];
    const removedAttributes = [];
    const context = {
        window: {
            omoApplicationViewPreferencesIsDashboardNavigation: () => dashboard,
            omoReplaceFetchedPanelRoot: options => {
                requests.push(options.url);
                pending.push(options.url);
                return Promise.resolve();
            }
        },
        root: {
            getAttribute: key => ({
                'data-omo-decision-oid': '7',
                'data-omo-decision-cid': '48',
                'data-omo-decision-scope': currentScope
            })[key],
            querySelector: selector => allowed.some(scope => selector === '[data-omo-decision-scope-toggle="' + scope + '"]') ? {} : null,
            classList: {add() {}, remove() {}},
            removeAttribute: name => removedAttributes.push(name)
        },
        payload: {openDecisionId},
        savedPreferences: {scope: preferred},
        omoDecisionsSyncFilterChips: () => { synchronized = true; }
    };
    vm.createContext(context);
    vm.runInContext(functions, context);

    // Emulate PHP: an omitted scope uses the server default; unavailable scopes
    // fall back to contextual. Each fetched fragment runs initialization again.
    function render(url) {
        const requested = new URL(url, 'https://omo.test').searchParams.get('decision_scope') ?? serverDefault;
        currentScope = allowed.includes(requested) ? requested : 'contextual';
        context.omoDecisionsInitializeViewFilter();
    }
    render('/omo/api/decision/index.php?oid=7&cid=48');
    for (let count = 0; pending.length && count < 3; count++) {
        render(pending.shift());
    }
    assert.equal(pending.length, 0, 'Restoring the scope must stop reloading: ' + JSON.stringify({preferred, serverDefault, allowed}));
    assert(synchronized, 'The settled panel must synchronize its filter chips.');
    assert(removedAttributes.includes('aria-busy'), 'The settled panel must clear its loading state.');
    assert.equal(context.savedPreferences.scope, preferred, 'Unavailable scopes must not erase saved preferences.');
    return {requests, currentScope};
}

const scopes = ['contextual', 'children', 'descendants'];
for (const allowed of [['contextual'], scopes]) {
    for (const preferred of scopes) {
        for (const serverDefault of scopes) {
            const result = simulate({preferred, serverDefault, allowed});
            const initialScope = allowed.includes(serverDefault) ? serverDefault : 'contextual';
            const expectedScope = allowed.includes(preferred) ? preferred : initialScope;
            assert.equal(result.currentScope, expectedScope);
            assert.equal(result.requests.length, expectedScope === initialScope ? 0 : 1);
            for (const url of result.requests) {
                const params = new URL(url, 'https://omo.test').searchParams;
                assert.equal(params.get('decision_scope'), preferred, 'Every requested scope must be explicit, including contextual.');
                assert.equal(params.get('oid'), '7');
                assert.equal(params.get('cid'), '48');
            }
        }
    }
}

for (const navigation of [{dashboard: true}, {openDecisionId: 42}]) {
    const result = simulate({preferred: 'contextual', serverDefault: 'descendants', allowed: scopes, ...navigation});
    assert.equal(result.requests.length, 0, 'Dashboard and direct decision navigation must preserve their initial scope.');
    assert.equal(result.currentScope, 'descendants');
}

console.log('decision_scope_refresh_test: OK');
