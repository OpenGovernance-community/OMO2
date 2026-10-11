'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require(process.argv[2] || 'playwright');
const root = path.resolve(__dirname, '..');
const states = ['free', 'partial', 'full', 'closed'];
const samples = states.map((state, index) => `<button class="calendar-freebusy-day" data-state="${state}" data-audit>${index + 1}</button>`).join('')
    + [0, 48].map(hue => `<button class="calendar-freebusy-day" data-state="partial" data-busy-slots="1" style="--param-freebusy-busy-hue:${hue}" data-audit>5</button>`).join('')
    + '<button class="calendar-freebusy-day" data-state="free" aria-current="date" data-audit>7</button>';
const shared = `<section class="calendar-freebusy generic-section"><h2>Disponibilités communes</h2><div class="calendar-freebusy-layout"><div class="calendar-freebusy-month generic-soft-panel"><h3>Novembre 2026</h3><div class="calendar-freebusy-calendar">${samples}</div></div>
    <div class="calendar-freebusy-day-panel generic-soft-panel"><h3>Jeudi 26 novembre</h3><div class="calendar-freebusy-slots">
    <button class="calendar-freebusy-slot" data-state="free" data-audit><time>09:00 – 09:30</time><span>Libre</span></button>
    <div class="calendar-freebusy-slot" data-state="busy" data-audit><time>09:30 – 10:00</time><span>Occupé</span></div>
    <div class="calendar-freebusy-slot" data-state="busy" data-busy-count="1" style="--param-freebusy-busy-hue:48" data-audit><time>10:00 – 10:30</time><span>Occupé</span></div>
    <button class="calendar-freebusy-slot" data-state="free" aria-pressed="true" data-audit><time>10:30 – 11:00</time><span>Sélectionné</span></button></div></div></div>
    <p class="omo-calendar-create__preview-warning" data-audit>Certains agendas externes ne sont pas à jour.</p>
    <p class="calendar-availability__conflict" data-audit>Conflit</p></section>`;
const publicFlow = `<main class="generic-page-shell meeting-shell"><section class="generic-elevated-panel"><h2>Prendre rendez-vous</h2><div class="meeting-calendar">${states.map((state, index) => `<a href="#" class="meeting-calendar__day" data-state="${state}" data-audit>${index + 1}</a>`).join('')}
    ${[0, 48].map(hue => `<a href="#" class="meeting-calendar__day" data-state="partial" data-busy-slots="1" style="--param-freebusy-busy-hue:${hue}" data-audit>5</a>`).join('')}
    <a href="#" class="meeting-calendar__day" data-state="free" aria-current="date" data-audit>7</a></div></section>
    <section class="generic-elevated-panel meeting-times"><h3>Créneaux disponibles</h3><div class="meeting-slots"><button class="meeting-slot generic-action-button generic-action-button--secondary" data-audit>09:00</button><button class="meeting-slot generic-action-button generic-action-button--secondary" aria-pressed="true" data-audit>10:00</button></div></section></main>`;
(async () => {
    const browser = await chromium.launch({headless: true, channel: 'msedge'});
    try {
        const page = await browser.newPage({viewport: {width: 1100, height: 700}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/*', route => route.fulfill({contentType: 'text/html', body: '<!doctype html><html><head></head><body></body></html>'}));
        await page.goto('http://availability.test/');
        for (const file of ['common/assets/theme-style-mono.css', 'common/assets/theme-style-turquoise.css', 'common/assets/theme-style-ocean-blue.css',
            'common/assets/components.css', 'common/calendar/availability-grid.css', 'common/calendar/availability.css', 'common/meeting/public.css']) {
            await page.addStyleTag({content: fs.readFileSync(path.join(root, file), 'utf8').replace(/^@import[^;]+;/gm, '')});
        }
        await page.addScriptTag({path: path.join(root, 'shared_functions.js')});
        for (const style of ['mono', 'turquoise', 'ocean-blue']) {
            for (const theme of ['light', 'dark']) {
                for (const mode of ['shared', 'public']) {
                    await page.evaluate(({style, theme, mode, markup}) => {
                        localStorage.setItem('omo-theme-preference', theme);
                        localStorage.setItem('omo-color-style-preference', style);
                        window.sharedApplyDocumentTheme();
                        document.body.className = mode === 'public' ? 'meeting-page' : '';
                        document.body.style.backgroundColor = 'var(--color-bg)';
                        document.body.style.color = 'var(--color-text)';
                        document.body.innerHTML = markup;
                    }, {style, theme, mode, markup: mode === 'public' ? publicFlow : shared});
                    const metrics = await page.evaluate(() => {
                        const ctx = document.createElement('canvas').getContext('2d', {willReadFrequently: true});
                        function luminance(color) {
                            ctx.clearRect(0, 0, 1, 1); ctx.fillStyle = color; ctx.fillRect(0, 0, 1, 1);
                            const rgb = Array.from(ctx.getImageData(0, 0, 1, 1).data).slice(0, 3).map(value => {
                                value /= 255; return value <= .04045 ? value / 12.92 : ((value + .055) / 1.055) ** 2.4;
                            });
                            return rgb[0] * .2126 + rgb[1] * .7152 + rgb[2] * .0722;
                        }
                        return Array.from(document.querySelectorAll('[data-audit]')).map(node => {
                            const css = getComputedStyle(node);
                            let bg = css.backgroundColor, ancestor = node;
                            while (bg === 'rgba(0, 0, 0, 0)' && ancestor.parentElement) {
                                ancestor = ancestor.parentElement; bg = getComputedStyle(ancestor).backgroundColor;
                            }
                            const foreground = luminance(css.color), background = luminance(bg);
                            return {text: node.textContent, state: node.dataset.state, background,
                                selected: node.hasAttribute('aria-current') || node.getAttribute('aria-pressed') === 'true',
                                contrast: (Math.max(foreground, background) + .05) / (Math.min(foreground, background) + .05)};
                        });
                    });
                    for (const metric of metrics) {
                        const label = `${theme}/${style}/${mode}: ${metric.text} (${metric.state || 'message'})`;
                        assert(metric.contrast >= 4.5, `${label} contrast ${metric.contrast.toFixed(2)}`);
                        if (!metric.selected) assert(theme === 'dark' ? metric.background < .18 : metric.background > .6, `${label} uses the theme surface`);
                    }
                    if (theme === 'dark' && style === 'mono') await page.screenshot({path: path.join(root, `tmp/availability-dark-${mode}.png`)});
                }
            }
        }
        assert.deepEqual(errors, []);
        console.log('availability_theme_browser_test: OK (12 combinations, contrast >= 4.5)');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
