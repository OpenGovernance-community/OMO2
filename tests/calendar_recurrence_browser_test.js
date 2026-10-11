'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 1000, height: 1100}});
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        const html = fs.readFileSync(path.join(root, 'tmp/meeting-recurrence-form.html'), 'utf8')
            .replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '').replace(/<link\b[^>]*>/g, '');
        await page.setContent(html);
        await page.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await page.addScriptTag({path: path.join(root, 'common/calendar/recurrence.js')});
        assert.equal(await page.locator('[data-meeting-recurrence-fields]').isVisible(), false);
        await page.locator('[data-meeting-recurrence-enabled]').check();
        assert.equal(await page.locator('[data-meeting-recurrence-fields]').isVisible(), true);
        assert.equal(await page.locator('[name="recurrence[document_mode]"]').inputValue(), 'copy');
        await page.locator('[name="recurrence[document_mode]"]').selectOption('reuse');
        assert.equal(await page.locator('[name="recurrence[document_mode]"]').inputValue(), 'reuse');
        await page.locator('[data-meeting-recurrence-frequency]').selectOption('monthly_day');
        assert.equal(await page.locator('[name="recurrence[month_day]"]').isVisible(), true);
        assert.equal(await page.locator('[name="recurrence[weekend_shift]"]').isVisible(), true);
        assert.equal(await page.locator('[name="recurrence[weekday]"]').isVisible(), false);
        await page.locator('[data-meeting-recurrence-frequency]').selectOption('days');
        assert.equal(await page.locator('[name="recurrence[weekend_shift]"]').isVisible(), true);
        await page.locator('[data-meeting-recurrence-frequency]').selectOption('monthly_weekday');
        assert.equal(await page.locator('[name="recurrence[ordinal]"]').isVisible(), true);
        assert.equal(await page.locator('[name="recurrence[weekday]"]').isVisible(), true);
        await page.locator('[data-meeting-recurrence-frequency]').selectOption('on_close');
        assert.equal(await page.locator('[name="recurrence[interval_days]"]').isVisible(), true);
        await page.getByLabel('Délai indicatif (jours)', {exact: true}).fill('30');
        assert.equal(await page.locator('[name="recurrence[interval_days]"]').inputValue(), '30');
        assert.equal(await page.locator('[name="recurrence[horizon_months]"]').isVisible(), false);
        assert.equal(await page.locator('[name="recurrence[weekday]"]').isVisible(), false);
        await page.locator('[data-meeting-recurrence-enabled]').uncheck();
        assert.equal(await page.locator('[data-meeting-recurrence-fields]').isVisible(), false);
        const pvHtml = fs.readFileSync(path.join(root, 'tmp/meeting-recurrence-pv.html'), 'utf8');
        const configMatch = pvHtml.match(/window\.commonPageScripts\["\/omo\/api\/documents\/pv\/editor\.js"\]\((\{.*\}), document.currentScript/);
        const config = JSON.parse(configMatch[1]);
        assert.equal(config.initialDocumentPayload.asksNextMeetingDate, true);
        assert.equal(config.meetingAvailability.suggested, '2030-04-16T09:00', 'The closure dialog receives the saved 14-day indicative interval.');
        await page.evaluate(ui => {
            window.testUi = ui;
            window.commonTopbarOpenModal = function (_, html) {
                const modal = document.createElement('section'); modal.id = 'test-modal'; modal.innerHTML = html; document.body.append(modal);
            };
            window.commonTopbarCloseModal = function () {
                document.querySelector('#test-modal')?.remove(); window.dispatchEvent(new Event('common-topbar-modal-close'));
            };
            window.dateResult = 'pending';
            window.omoMeetingRecurrence.chooseDate(ui, true).then(value => { window.dateResult = value; });
        }, config.meetingRecurrenceUi);
        await page.locator('[name="next_meeting_start"]').fill('2030-04-18T15:30');
        await page.locator('[data-meeting-next-form] button[type="submit"]').click();
        await page.waitForFunction(() => window.dateResult !== 'pending');
        assert.equal(await page.evaluate(() => window.dateResult), '2030-04-18T15:30');
        await page.evaluate(() => {
            window.dateResult = 'pending';
            window.omoMeetingRecurrence.chooseDate(window.testUi, true).then(value => { window.dateResult = value; });
        });
        await page.locator('[data-meeting-skip]').click();
        await page.waitForFunction(() => window.dateResult !== 'pending');
        assert.equal(await page.evaluate(() => window.dateResult), '');
        await page.evaluate(() => {
            window.dateResult = 'pending';
            window.omoMeetingRecurrence.chooseDate(window.testUi, true).then(value => { window.dateResult = value; });
        });
        await page.locator('[data-meeting-cancel]').click();
        await page.waitForFunction(() => window.dateResult !== 'pending');
        assert.equal(await page.evaluate(() => window.dateResult), null);
        await page.addStyleTag({path: path.join(root, 'common/calendar/availability-grid.css')});
        for (const asset of ['availability-model.js', 'availability-view.js', 'availability.js']) {
            await page.addScriptTag({path: path.join(root, 'common/calendar', asset)});
        }
        const previewHtml = fs.readFileSync(path.join(root, 'tmp/meeting-recurrence-availability.html'), 'utf8');
        await page.evaluate(({ui, availability, html}) => {
            window.originalFetch = window.fetch; window.previewRequests = [];
            window.fetch = async (url, options) => {
                window.previewRequests.push({url, month: options.body.get('month')});
                return new Response(html, {status: 200});
            };
            window.dateResult = 'pending';
            window.omoMeetingRecurrence.chooseDate(ui, true, '2030-04-09T09:00', {...availability, duration: 2700})
                .then(value => { window.dateResult = value; });
        }, {ui: config.meetingRecurrenceUi, availability: config.meetingAvailability, html: previewHtml});
        await page.waitForSelector('[data-meeting-next-form] [data-omo-calendar-preview-host][data-loaded="1"]');
        const nextForm = page.locator('[data-meeting-next-form]');
        await page.waitForFunction(() => {
            const list = document.querySelector('[data-meeting-next-form] .calendar-freebusy-slots');
            const selected = list?.querySelector('[aria-pressed="true"]');
            if (!selected) return false;
            const bounds = list.getBoundingClientRect(), slot = selected.getBoundingClientRect();
            return list.scrollTop > 0 && slot.top >= bounds.top && slot.bottom <= bounds.bottom;
        });
        await nextForm.locator('[name="next_meeting_start"]').fill('2030-04-09T10:15');
        await nextForm.locator('[name="next_meeting_start"]').dispatchEvent('change');
        await page.waitForFunction(() => {
            const list = document.querySelector('[data-meeting-next-form] .calendar-freebusy-slots');
            const slot = list.querySelector('[data-omo-calendar-preview-slot-start="2030-04-09T10:00"]');
            const bounds = list.getBoundingClientRect(), target = slot.getBoundingClientRect();
            return slot.dataset.state === 'busy' && target.top >= bounds.top && target.bottom <= bounds.bottom;
        });
        assert.equal(await nextForm.locator('[data-meeting-refresh-availability]').count(), 0);
        assert.equal(await nextForm.locator('.generic-context-help').count(), 2, 'Scheduling and participant explanations use help capsules.');
        assert.equal(await nextForm.locator('[data-omo-calendar-preview-person]').count(), 2);
        assert.equal(await nextForm.locator('[data-omo-calendar-preview-slot-start="2030-04-09T10:00"]').getAttribute('data-state'), 'busy');
        await nextForm.locator('button[data-omo-calendar-preview-slot-start="2030-04-09T09:00"]').click();
        assert.equal(await nextForm.locator('[name="next_meeting_start"]').inputValue(), '2030-04-09T09:00');
        assert.equal(await nextForm.locator('[name="end_at"]').inputValue(), '2030-04-09T09:45');
        await nextForm.locator('[data-omo-calendar-preview-target$="date=2030-04-10"]').click();
        assert.equal(await nextForm.locator('[name="next_meeting_start"]').inputValue(), '2030-04-10T09:00', 'Changing day retains the chosen time.');
        assert.equal(await nextForm.locator('[name="end_at"]').inputValue(), '2030-04-10T09:45');
        assert.equal(await nextForm.locator('[data-omo-calendar-preview-slot-start="2030-04-10T09:00"]').getAttribute('aria-pressed'), 'true', 'The previous time is selected in the new day grid.');
        await nextForm.locator('[data-omo-calendar-preview-target$="date=2030-04-09"]').click();
        await nextForm.locator('button[data-omo-calendar-preview-slot-start="2030-04-09T11:00"]').click({modifiers: ['Shift']});
        assert.equal(await nextForm.locator('[name="end_at"]').inputValue(), '2030-04-09T11:45', 'Shift-click never changes the model duration.');
        await nextForm.locator('[name="next_meeting_start"]').fill('2030-04-09T14:15');
        await nextForm.locator('[name="next_meeting_start"]').dispatchEvent('change');
        assert.equal(await nextForm.locator('[name="end_at"]').inputValue(), '2030-04-09T15:00', 'Manual start times retain the exact duration too.');
        await nextForm.locator('[data-omo-calendar-preview-target$="date=2030-04-10"]').click();
        assert.equal(await nextForm.locator('[name="next_meeting_start"]').inputValue(), '2030-04-10T14:15', 'Day changes retain minutes outside the half-hour grid.');
        assert.equal(await nextForm.locator('[name="end_at"]').inputValue(), '2030-04-10T15:00');
        await nextForm.locator('[data-omo-calendar-preview-target$="date=2030-04-09"]').click();
        await page.setViewportSize({width: 390, height: 844});
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'Availability fits the mobile modal.');
        await nextForm.screenshot({path: path.join(root, 'tmp/meeting-next-availability-mobile.png')});
        await nextForm.locator('button[type="submit"]').click();
        await page.waitForFunction(() => window.dateResult !== 'pending');
        assert.equal(await page.evaluate(() => window.dateResult), '2030-04-09T14:15');
        await page.evaluate(() => { window.fetch = window.originalFetch; });
        assert.deepEqual(errors, []);
        await page.setViewportSize({width: 390, height: 844});
        await page.locator('[data-meeting-recurrence-enabled]').check();
        await page.locator('[data-meeting-recurrence-frequency]').selectOption('monthly_weekday');
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'No mobile overflow.');
        await page.locator('[data-meeting-recurrence]').screenshot({path: path.join(root, 'tmp/meeting-recurrence-mobile.png')});

        for (const scope of ['single', 'following']) {
            page.once('dialog', dialog => dialog.accept());
            await page.evaluate(scope => {
                window.scopeResult = 'pending';
                window.omoMeetingRecurrence.confirmDeletion(window.testUi, scope).then(value => { window.scopeResult = value; });
            }, scope);
            await page.waitForFunction(() => window.scopeResult !== 'pending');
            assert.equal(await page.evaluate(() => window.scopeResult), true);
        }
        page.once('dialog', dialog => {
            assert.match(dialog.message(), /Les réunions précédentes et passées sont conservées/); dialog.dismiss();
        });
        await page.evaluate(() => {
            window.scopeResult = 'pending';
            window.omoMeetingRecurrence.confirmDeletion(window.testUi, 'following').then(value => { window.scopeResult = value; });
        });
        await page.waitForFunction(() => window.scopeResult !== 'pending');
        assert.equal(await page.evaluate(() => window.scopeResult), false, 'Closing confirmation cancels.');

        // Exercise the split save action using its server-rendered markup.
        const editHtml = fs.readFileSync(path.join(root, 'tmp/meeting-recurrence-edit-form.html'), 'utf8');
        await page.evaluate(editHtml => {
            const edit = new DOMParser().parseFromString(editHtml, 'text/html');
            const form = document.querySelector('[data-omo-calendar-create-form]');
            form.querySelector('[data-meeting-recurrence]').replaceWith(edit.querySelector('[data-meeting-recurrence]').cloneNode(true));
            const actions = document.createElement('div'); actions.className = 'generic-drawer-header__actions';
            actions.appendChild(edit.querySelector('[data-meeting-save-menu]').cloneNode(true)); document.body.appendChild(actions);
            form.noValidate = true;
            window.savedScopes = [];
            form.addEventListener('submit', event => {
                event.preventDefault(); window.savedScopes.push(new FormData(form).get('recurrence_apply_following'));
            });
        }, editHtml);
        await page.locator('[data-meeting-save-menu] > [data-meeting-save-scope="single"]').click();
        await page.waitForFunction(() => window.savedScopes.length === 1);
        assert.deepEqual(await page.evaluate(() => window.savedScopes), ['0']);
        await page.locator('[data-meeting-save-toggle]').click();
        assert.equal(await page.locator('[data-meeting-save-menu] [role="menu"]').isVisible(), true);
        const saveMenuBox = await page.locator('[data-meeting-save-menu] [role="menu"]').boundingBox();
        assert.ok(saveMenuBox.x >= 0 && saveMenuBox.x + saveMenuBox.width <= 390, 'Save menu fits the mobile drawer.');
        await page.locator('[data-meeting-save-menu] [role="menu"]').screenshot({path: path.join(root, 'tmp/meeting-save-menu-mobile.png')});
        await page.locator('[data-meeting-save-scope="following"]').click();
        await page.waitForFunction(() => window.savedScopes.length === 2);
        assert.deepEqual(await page.evaluate(() => window.savedScopes), ['0', '1']);
        assert.equal(await page.locator('[data-meeting-recurrence-fields]').isVisible(), false);
        assert.equal(await page.locator('[data-meeting-recurrence-readonly]').isVisible(), true);
        await page.locator('[data-meeting-save-toggle]').click();
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('[data-meeting-save-menu] [role="menu"]').isVisible(), false);
        assert.deepEqual(await page.evaluate(() => window.savedScopes), ['0', '1'], 'Closing menu does not save.');
        await page.locator('[data-meeting-save-toggle]').click();
        await page.locator('[data-meeting-save-scope="following"]').click();
        await page.waitForFunction(() => window.savedScopes.length === 3);
        assert.deepEqual(await page.evaluate(() => window.savedScopes), ['0', '1', '1']);
        await page.evaluate(() => document.querySelector('[data-omo-calendar-create-form]').requestSubmit());
        assert.deepEqual(await page.evaluate(() => window.savedScopes), ['0', '1', '1', '0'], 'Implicit submit defaults to this meeting, never a previous collective choice.');
        await page.addScriptTag({path: path.join(root, 'common/calendar/availability-model.js')});
        await page.addScriptTag({path: path.join(root, 'common/calendar/availability-view.js')});
        await page.addScriptTag({path: path.join(root, 'common/calendar/availability.js')});
        await page.evaluate(() => {
            const form = document.querySelector('[data-omo-calendar-create-form]');
            form.requestSubmit(document.querySelector('[data-meeting-save-scope="following"]'));
            form.querySelector('[data-calendar-availability]').dataset.acknowledgement = 'test-conflict';
            form.querySelector('[data-calendar-availability-confirm]').click();
        });
        assert.deepEqual(await page.evaluate(() => window.savedScopes.slice(-2)), ['1', '1'], 'Confirming an availability conflict preserves the collective save action.');
        assert.deepEqual(errors, []);
        await page.locator('[data-meeting-recurrence-edit]').click();
        assert.equal(await page.locator('[data-meeting-recurrence-fields]').isVisible(), true);
        await page.locator('[data-meeting-recurrence-frequency]').selectOption('days');
        assert.equal(await page.locator('[name="recurrence[weekend_shift]"]').isVisible(), true);
        await page.locator('[data-meeting-save-toggle]').click();
        assert.match(await page.locator('[data-meeting-save-scope="following"]').innerText(), /appliquer la récurrence/);
        await page.keyboard.press('Escape');
        const beforeIgnored = await page.evaluate(() => window.savedScopes.length);
        page.once('dialog', dialog => { assert.match(dialog.message(), /ne seront pas appliquées/); dialog.dismiss(); });
        await page.locator('[data-meeting-save-menu] > [data-meeting-save-scope="single"]').click();
        assert.equal(await page.evaluate(() => window.savedScopes.length), beforeIgnored, 'Cancel the warning without saving.');
        page.once('dialog', dialog => dialog.accept());
        await page.locator('[data-meeting-save-menu] > [data-meeting-save-scope="single"]').click();
        assert.equal(await page.evaluate(() => window.savedScopes.at(-1)), '0', 'Single save does not apply the edited rule.');
        await page.locator('[data-meeting-recurrence-strategy]').selectOption('stop_delete');
        assert.equal(await page.locator('[data-meeting-recurrence-fields]').isVisible(), false);
        page.once('dialog', dialog => {
            assert.match(dialog.message(), /Cette réunion et les réunions passées sont conservées/);
            page.once('dialog', documents => documents.dismiss()); dialog.accept();
        });
        await page.locator('[data-meeting-save-toggle]').click();
        await page.locator('[data-meeting-save-scope="following"]').click();
        assert.equal(await page.evaluate(() => window.savedScopes.at(-1)), '1');
        assert.equal(await page.locator('[data-meeting-recurrence-delete-documents]').inputValue(), '0');
        await page.locator('[data-meeting-recurrence-cancel]').click();
        assert.equal(await page.locator('[data-meeting-recurrence-readonly]').isVisible(), true);
        assert.equal(await page.locator('[data-meeting-recurrence-editing]').inputValue(), '0');
        assert.deepEqual(errors, []);
        const deletionPage = await browser.newPage({viewport: {width: 390, height: 844}});
        await deletionPage.route('http://calendar.test/**', route => route.fulfill({contentType: 'text/html', body: '<!doctype html><html><body></body></html>'}));
        await deletionPage.goto('http://calendar.test/');
        deletionPage.on('pageerror', error => errors.push(error.message));
        await deletionPage.evaluate(({ui, detailHtml}) => {
            document.body.innerHTML = '<div id="omo-calendar-root" data-omo-calendar-view="list" data-omo-calendar-current-url="/calendar?month=2030-04">'
                + '<script type="application/json" data-omo-calendar-data></script><div data-omo-calendar-views></div>'
                + '<div data-omo-calendar-editor-drawer><div class="generic-drawer-header__actions" data-omo-calendar-editor-body></div></div></div>';
            const config = {views: {contextual: {list: {sections: [{label: 'Meetings', items: ['1']}]}}},
                items: {'1': {id: 1, title: 'Recurring meeting', canDelete: true, deleteUrl: '/delete', isRecurring: true, hasAssociatedDocuments: true}},
                labels: {}, meetingRecurrenceUi: ui, meetingRecurrenceCsrf: 'browser-token', externalEventDrawerText: {}};
            document.querySelector('[data-omo-calendar-data]').textContent = JSON.stringify(config);
            const detail = new DOMParser().parseFromString(detailHtml, 'text/html');
            const menu = detail.querySelector('[data-meeting-delete-menu]');
            menu.querySelector('[data-omo-calendar-delete-url]').setAttribute('data-meeting-delete-csrf', 'browser-token');
            window.prepareDeletion = () => {
                document.querySelector('[data-omo-calendar-editor-body]').replaceChildren(menu.cloneNode(true));
                document.querySelector('[data-omo-calendar-editor-drawer]').hidden = false;
            };
            window.prepareDeletion(); window.deleteRequests = [];
            window.calendarRefreshRequests = [];
            window.omoReplaceFetchedPanelRoot = options => { window.calendarRefreshRequests.push(options.url); return Promise.resolve(); };
            window.fetch = async (url, options) => {
                window.deleteRequests.push(Object.fromEntries(new URLSearchParams(options.body)));
                return {json: async () => ({status: true})};
            };
            window.omoNotify = message => { throw new Error(message); };
            window.commonTopbarOpenModal = (_, html) => {
                const body = document.createElement('div'); body.id = 'commonTopbarModalBody'; body.innerHTML = html; document.body.append(body);
            };
            window.commonTopbarCloseModal = () => {
                document.querySelector('#commonTopbarModalBody')?.remove();
                window.dispatchEvent(new Event('common-topbar-modal-close'));
            };
        }, {ui: config.meetingRecurrenceUi, detailHtml: fs.readFileSync(path.join(root, 'tmp/meeting-recurrence-detail.html'), 'utf8')});
        await deletionPage.addScriptTag({path: path.join(root, 'common/assets/components.js')});
        await deletionPage.addStyleTag({path: path.join(root, 'common/assets/components.css')});
        await deletionPage.addScriptTag({path: path.join(root, 'common/calendar/recurrence.js')});
        await deletionPage.addScriptTag({path: path.join(root, 'omo/api/calendar/calendar.js')});
        await deletionPage.evaluate(() => window.omoInitCalendar(document.querySelector('#omo-calendar-root')));
        const deleteButton = deletionPage.locator('[data-omo-calendar-editor-body] [data-omo-calendar-delete-url]');
        deletionPage.once('dialog', dialog => dialog.dismiss());
        await deleteButton.click();
        assert.equal(await deletionPage.evaluate(() => window.deleteRequests.length), 0);
        await deletionPage.locator('[data-meeting-delete-toggle]').click();
        const deleteMenuBox = await deletionPage.locator('[data-meeting-delete-menu] [role="menu"]').boundingBox();
        assert.ok(deleteMenuBox.x >= 0 && deleteMenuBox.x + deleteMenuBox.width <= 390, 'Delete menu fits the mobile drawer.');
        await deletionPage.locator('[data-meeting-delete-menu] [role="menu"]').screenshot({path: path.join(root, 'tmp/meeting-delete-menu-mobile.png')});
        deletionPage.once('dialog', dialog => {
            deletionPage.once('dialog', documents => {
                assert.match(documents.message(), /Supprimer aussi les documents des réunions supprimées/); documents.dismiss();
            });
            dialog.accept();
        });
        await deletionPage.locator('[data-meeting-delete-choice="following"]').click();
        await deletionPage.waitForFunction(() => window.deleteRequests.length === 1);
        assert.deepEqual(await deletionPage.evaluate(() => window.deleteRequests[0]),
            {delete_documents: '0', recurrence_scope: 'following', recurrence_csrf: 'browser-token'}, 'Dismissing the document confirmation keeps documents.');
        await deletionPage.evaluate(() => window.prepareDeletion());
        deletionPage.once('dialog', dialog => {
            assert.match(dialog.message(), /Supprimer uniquement cette réunion/);
            deletionPage.once('dialog', documents => documents.dismiss()); dialog.accept();
        });
        await deleteButton.click();
        await deletionPage.waitForFunction(() => window.deleteRequests.length === 2);
        assert.deepEqual(await deletionPage.evaluate(() => window.deleteRequests[1]),
            {delete_documents: '0', recurrence_scope: 'single', recurrence_csrf: 'browser-token'});
        await deletionPage.evaluate(() => window.prepareDeletion());
        await deletionPage.locator('[data-meeting-delete-toggle]').click();
        deletionPage.once('dialog', dialog => {
            deletionPage.once('dialog', documents => documents.accept()); dialog.accept();
        });
        await deletionPage.locator('[data-meeting-delete-choice="following"]').click();
        await deletionPage.waitForFunction(() => window.deleteRequests.length === 3);
        assert.deepEqual(await deletionPage.evaluate(() => window.deleteRequests[2]),
            {delete_documents: '1', recurrence_scope: 'following', recurrence_csrf: 'browser-token'});
        assert.deepEqual(errors, []);
        const navigationHtml = fs.readFileSync(path.join(root, 'tmp/meeting-recurrence-navigation.html'), 'utf8');
        const navigationNextHtml = fs.readFileSync(path.join(root, 'tmp/meeting-recurrence-navigation-next.html'), 'utf8');
        await deletionPage.addStyleTag({path: path.join(root, 'omo/api/calendar/detail.css')});
        await deletionPage.evaluate(({first, next}) => {
            const clean = html => html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/g, '').replace(/<link\b[^>]*>/g, '');
            first = clean(first); next = clean(next);
            const body = document.querySelector('[data-omo-calendar-editor-body]');
            body.className = ''; body.innerHTML = first;
            document.querySelector('[data-omo-calendar-editor-drawer]').hidden = false;
            document.querySelector('[data-omo-calendar-editor-drawer]').classList.add('is-open');
            window.navigationRequests = [];
            const nextUrl = body.querySelector('[data-meeting-recurrence-direction="next"]').getAttribute('data-omo-calendar-open-detail-url');
            const nextId = new URL(nextUrl, location.href).searchParams.get('id');
            window.fetch = async url => {
                const id = new URL(url, location.href).searchParams.get('id');
                window.navigationRequests.push(id);
                return {ok: true, text: async () => id === nextId ? next : first};
            };
        }, {first: navigationHtml, next: navigationNextHtml});
        assert.equal(await deletionPage.locator('[data-meeting-recurrence-direction="previous"]').isDisabled(), true);
        const nextUrl = await deletionPage.locator('[data-meeting-recurrence-direction="next"]').getAttribute('data-omo-calendar-open-detail-url');
        const navigationBox = await deletionPage.locator('[data-meeting-recurrence-summary]').boundingBox();
        assert.ok(navigationBox.x >= 0 && navigationBox.x + navigationBox.width <= 390, 'Recurrence navigation fits the mobile drawer.');
        await deletionPage.locator('[data-meeting-recurrence-summary]').screenshot({path: path.join(root, 'tmp/meeting-recurrence-navigation-mobile.png')});
        await deletionPage.locator('[data-meeting-recurrence-direction="next"]').click();
        await deletionPage.waitForFunction(() => document.querySelector('[data-meeting-recurrence-direction="previous"]')?.tagName === 'A');
        assert.equal(await deletionPage.evaluate(() => window.navigationRequests[0]), new URL(nextUrl, 'http://calendar.test').searchParams.get('id'));
        await deletionPage.locator('[data-meeting-recurrence-direction="previous"]').click();
        await deletionPage.waitForFunction(() => document.querySelector('[data-meeting-recurrence-direction="previous"]')?.disabled === true);
        assert.equal(await deletionPage.evaluate(() => window.navigationRequests.length), 2, 'Both arrows use drawer navigation without leaving the calendar.');
        await deletionPage.evaluate(() => {
            window.calendarRefreshRequests = [];
            window.omoMeetingRecurrence.refreshAfterPlanning();
        });
        assert.deepEqual(await deletionPage.evaluate(() => window.calendarRefreshRequests), ['/calendar?month=2030-04'], 'Planning refreshes the current month even with the event drawer open.');
        await deletionPage.evaluate(() => {
            document.querySelector('#omo-calendar-root').remove();
            window.omoMeetingRecurrence.refreshAfterPlanning();
        });
        assert.equal(await deletionPage.evaluate(() => window.calendarRefreshRequests.length), 1, 'Planning does not reopen an absent calendar.');
        assert.deepEqual(errors, []);
        await deletionPage.close();
        console.log('calendar_recurrence_browser_test: OK');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
