'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const cases = [
    ['checklist/checklist.js', 'initializeViewFilter', 'function buildChecklistRouteToken'],
    ['activities/activities.js', 'initializeViewFilter', "root.addEventListener('click'"],
    ['stats/stats.js', 'initializeViewFilter', 'function executeFetchedScripts'],
    ['calendar/calendar.js', 'initializeCalendarViewFilter', 'function getCalendarNow'],
    ['team/index.js', 'omoTeamInitializeFilters', 'function omoTeamEnsureMapReady'],
];
for (const [file, initializer, next] of cases) {
    const source = fs.readFileSync(path.join(__dirname, '../omo/api/', file), 'utf8');
    const start = source.indexOf('function ' + initializer + '(');
    const code = source.slice(start, source.indexOf(next, start));
    for (const fromDashboard of [false, true]) {
        for (const scope of ['contextual', 'children', 'descendants']) {
            const savedScope = scope === 'contextual' ? 'descendants' : 'contextual';
            const saved = {scope: savedScope, assignment: 'mine', view: 'cards', state: 'done', sort: 'alpha'};
            const calls = [];
            const noop = () => {};
            const refresh = value => { calls.push(value); return Promise.resolve(); };
            const root = {querySelector: () => ({textContent: 'Scope'}), removeAttribute: noop,
                getAttribute: name => name === 'data-team-scope' ? scope : ''};
            const window = {sessionStorage: {}, localStorage: {},
                omoApplicationViewPreferencesIsDashboardNavigation: () => fromDashboard,
                omoApplicationViewPreferencesCanUseLegacyPersonal: () => true,
                omoApplicationViewPreferencesGetDefault: () => saved,
                omoApplicationViewPreferencesGetPersonal: () => saved};
            const ctx = {window, root, currentScope: scope, currentAssignment: 'all', currentSort: 'temporal',
                initialOpenChecklistId: 0, initialOpenEventId: 0, initialIndicatorId: 0, initialGroupId: 0,
                currentUrl: '/calendar', omoTeamInitialScope: scope,
                normalizeScope: value => value, normalizeAssignment: value => value, normalizeState: value => value,
                normalizeFilters: value => value, normalizeCalendarFilters: value => value,
                readStoredValue: () => saved, readStoredSearch: () => 'old query',
                getStoredFilters: () => saved, getDefaultStoredFilters: () => saved,
                readCalendarStoredValue: () => saved, getCalendarStoredPreferences: () => saved,
                getCalendarDefaultPreferences: () => saved, readCalendarSearch: () => 'old query',
                getActiveFilters: () => ({scope, assignment: 'all', sort: 'temporal', view: 'cards'}),
                getActiveCalendarFilters: () => ({scope, view: 'month'}),
                omoTeamGetRoot: () => root, omoTeamReadViewPreferences: () => saved,
                omoTeamGetStoredViewPreferences: () => saved, omoTeamGetDefaultViewPreferences: () => saved,
                omoTeamNormalizeFilters: value => ({...value}), omoTeamNormalizeScope: value => value,
                refreshRoot: refresh, refreshCalendar: refresh, omoTeamChangeScope: refresh,
                buildScopeUrl: value => value, buildFilterUrl: value => value,
                resolveViewMeta: (view, selectedScope) => ({url: selectedScope}),
                setActiveView: (view, url) => calls.push(url),
                syncFilterChip: noop, syncFilterChips: noop, applyQuickSearch: noop, applyListFilters: noop,
                applyView: noop, omoTeamApplyView: noop, omoTeamRevealRoot: noop};
            for (const key of ['checklistSessionViewsStorageKey', 'checklistSavedViewsStorageKey', 'checklistSearchStorageKey',
                'sessionViewsStorageKey', 'savedViewsStorageKey', 'searchStorageKey', 'calendarSessionViewsStorageKey', 'omoTeamSessionViewsStorageKey']) ctx[key] = key;
            vm.createContext(ctx);
            vm.runInContext(code + '\n' + initializer + '();', ctx);
            if (file.startsWith('calendar')) {
                assert.deepEqual(calls, [fromDashboard ? scope : savedScope]);
            } else {
                assert.equal(calls.length, fromDashboard ? 0 : 1, file + ': explicit dashboard filters must survive stored preferences');
            }
            if (fromDashboard && !file.startsWith('team')) assert.equal(ctx.currentSearch, '');
        }
    }
}
console.log('dashboard_navigation_preferences_test: OK (dashboard precedence, all ranges, old searches and normal preference restoration)');
