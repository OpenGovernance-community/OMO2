(function (window, document) {
    'use strict';

    const OMO_SIMPLE_HTML_FIELD_VERSION = '20260724-pv-embed-status';

    if (
        window.omoSimpleHtmlField
        && String(window.omoSimpleHtmlField.version || '') === OMO_SIMPLE_HTML_FIELD_VERSION
    ) {
        return;
    }

    const SUMMERNOTE_VERSION = '0.8.18';
    const SUMMERNOTE_CSS_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/summernote-lite.min.css';
    const SUMMERNOTE_JS_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/summernote-lite.min.js';
    const SUMMERNOTE_LANG_URL = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + SUMMERNOTE_VERSION + '/lang/summernote-fr-FR.min.js';

    let stylesInjected = false;
    let dependencyPromise = null;
    let cursorMarkerCounter = 0;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function ensureLocalStyles() {
        if (stylesInjected) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = ''
            + '.omo-simple-html-field{display:grid;gap:10px;}'
            + '.omo-simple-html-field .note-editor.note-frame{border:1px solid var(--color-border,#d1d5db);border-radius:var(--radius-md);background:var(--color-surface,#fff);}'
            + '.omo-simple-html-field .note-toolbar{position:sticky;top:0;z-index:6;border-bottom:1px solid var(--color-border,#d1d5db);background:color-mix(in srgb,var(--color-surface-alt,#f8fafc) 88%,white);border-top-left-radius:var(--radius-md);border-top-right-radius:var(--radius-md);padding:8px;box-shadow:0 8px 18px -18px rgba(15,23,42,.45);}'
            + '.omo-simple-html-field .note-btn{border-radius:var(--radius-md);border-color:var(--color-border,#d1d5db);}'
            + '.omo-simple-html-field .note-editing-area{overflow:visible;}'
            + '.omo-simple-html-field .note-editing-area .note-editable{min-height:140px;height:auto!important;overflow-y:hidden!important;padding:14px;line-height:1.55;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-placeholder{color:var(--color-text-light,#6b7280);}'
            + '.omo-simple-html-field .note-statusbar{display:none;}'
            + '.omo-simple-html-field .note-editable h1,.omo-simple-html-render h1{margin:0 0 .6em;font-size:1.8rem;line-height:1.15;font-weight:850;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-editable h2,.omo-simple-html-render h2{margin:0 0 .55em;font-size:1.45rem;line-height:1.2;font-weight:800;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-editable h3,.omo-simple-html-render h3{margin:0 0 .5em;font-size:1.16rem;line-height:1.25;font-weight:750;color:var(--color-text,#1f2937);}'
            + '.omo-simple-html-field .note-editable blockquote,.omo-simple-html-render blockquote{margin:0 0 1em;padding:.2em 0 .2em 1em;border-left:3px solid color-mix(in srgb,var(--color-primary,#2563eb) 38%,var(--color-border,#d1d5db));color:var(--color-text-light,#6b7280);font-style:italic;}'
            + '.omo-simple-html-field .note-editable table,.omo-simple-html-render table{width:100%;max-width:100%;margin:0 0 1em;border-collapse:collapse;border-spacing:0;font-size:.97em;}'
            + '.omo-simple-html-field .note-editable th,.omo-simple-html-field .note-editable td,.omo-simple-html-render th,.omo-simple-html-render td{padding:9px 12px;border:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 88%,var(--color-text,#1f2937) 12%);text-align:left;vertical-align:top;}'
            + '.omo-simple-html-field .note-editable th,.omo-simple-html-render th{background:color-mix(in srgb,var(--color-surface-alt,#f8fafc) 82%,var(--color-text,#1f2937) 18%);font-weight:700;}'
            + '.omo-simple-html-field .note-editable tbody tr:nth-child(even),.omo-simple-html-render tbody tr:nth-child(even){background:color-mix(in srgb,var(--color-surface,#fff) 92%,var(--color-surface-alt,#f8fafc) 8%);}'
            + '.omo-simple-html-field .note-editable .omo-document-embed,.omo-simple-html-render .omo-document-embed{display:block;margin:0 0 1em;padding:12px 14px;border:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 88%,#2563eb 12%);border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-surface,#fff) 90%,#eff6ff 10%);box-shadow:0 10px 24px -20px rgba(37,99,235,.45);cursor:pointer;white-space:normal;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-decision-embed,.omo-simple-html-field .note-editable .omo-event-embed,.omo-simple-html-field .note-editable .omo-project-embed,.omo-simple-html-field .note-editable .omo-checklist-embed,.omo-simple-html-render .omo-decision-embed,.omo-simple-html-render .omo-event-embed,.omo-simple-html-render .omo-project-embed,.omo-simple-html-render .omo-checklist-embed{display:block;margin:0 0 1em;padding:12px 14px;border:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 88%,#2563eb 12%);border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-surface,#fff) 90%,#eff6ff 10%);box-shadow:0 10px 24px -20px rgba(37,99,235,.45);cursor:pointer;white-space:normal;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed:last-child,.omo-simple-html-render .omo-document-embed:last-child{margin-bottom:0;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed__label,.omo-simple-html-render .omo-document-embed__label{display:block;margin:0 0 6px;color:var(--color-text-light,#6b7280);font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed__title,.omo-simple-html-render .omo-document-embed__title{display:block;margin:0;color:var(--color-text,#1f2937);font-weight:700;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed__description,.omo-simple-html-render .omo-document-embed__description{display:block;margin:6px 0 0;color:var(--color-text-light,#6b7280);font-size:13px;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-decision-embed__title,.omo-simple-html-field .note-editable .omo-event-embed__title,.omo-simple-html-render .omo-decision-embed__title,.omo-simple-html-render .omo-event-embed__title{display:block;margin:0;color:var(--color-text,#1f2937);font-weight:700;}'
            + '.omo-simple-html-field .note-editable .omo-project-embed>strong,.omo-simple-html-render .omo-project-embed>strong{display:flex;align-items:center;gap:6px;margin:0;color:var(--color-text,#1f2937);font-weight:700;}.omo-simple-html-field .note-editable .omo-project-embed>strong>a:first-child,.omo-simple-html-render .omo-project-embed>strong>a:first-child{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}.omo-simple-html-field .note-editable .omo-project-embed>strong>a:nth-child(2),.omo-simple-html-render .omo-project-embed>strong>a:nth-child(2){flex:0 0 auto;font-size:13px;text-decoration:none;}.omo-simple-html-field .note-editable .omo-project-embed>strong>em,.omo-simple-html-render .omo-project-embed>strong>em{flex:0 0 auto;margin:0;padding:1px 5px;border-radius:999px;background:color-mix(in srgb,#2563eb 10%,var(--color-surface,#fff));color:#1d4ed8;font-size:11px;font-style:normal;font-weight:800;line-height:1.35;}.omo-simple-html-field .note-editable .omo-project-embed>em,.omo-simple-html-render .omo-project-embed>em{display:block;margin:5px 0 0;color:var(--color-text-light,#6b7280);font-size:12px;font-style:normal;line-height:1.35;}'
            + '.omo-simple-html-field .note-editable .omo-decision-embed__summary,.omo-simple-html-field .note-editable .omo-event-embed__summary,.omo-simple-html-render .omo-decision-embed__summary,.omo-simple-html-render .omo-event-embed__summary{display:block;margin:6px 0 0;color:var(--color-text-light,#6b7280);font-size:13px;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > p:first-child,.omo-simple-html-render .omo-document-embed > p:first-child{margin:0 0 6px;color:var(--color-text-light,#6b7280);font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > p:nth-child(2),.omo-simple-html-render .omo-document-embed > p:nth-child(2){margin:0;color:var(--color-text,#1f2937);font-weight:700;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > p:nth-child(3),.omo-simple-html-render .omo-document-embed > p:nth-child(3){margin:6px 0 0;color:var(--color-text-light,#6b7280);font-size:13px;line-height:1.5;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > strong:first-child,.omo-simple-html-render .omo-document-embed > strong:first-child{display:block;margin:0 0 6px;color:var(--color-text-light,#6b7280);font-size:12px;font-weight:600;letter-spacing:.02em;text-transform:uppercase;}'
            + '.omo-simple-html-field .note-editable .omo-document-embed > strong:nth-of-type(2),.omo-simple-html-render .omo-document-embed > strong:nth-of-type(2){display:block;margin:0;color:var(--color-text,#1f2937);font-weight:700;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed,.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed,.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed,.omo-pv-editor .omo-simple-html-field .note-editable .omo-checklist-embed,.omo-pv-editor .omo-simple-html-render .omo-document-embed,.omo-pv-editor .omo-simple-html-render .omo-decision-embed,.omo-pv-editor .omo-simple-html-render .omo-event-embed,.omo-pv-editor .omo-simple-html-render .omo-project-embed,.omo-pv-editor .omo-simple-html-render .omo-checklist-embed{position:relative;min-height:54px;margin-bottom:10px;padding:9px 12px 9px 58px;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed:before,.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed:before,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed:before,.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed:before,.omo-pv-editor .omo-simple-html-field .note-editable .omo-checklist-embed:before,.omo-pv-editor .omo-simple-html-render .omo-document-embed:before,.omo-pv-editor .omo-simple-html-render .omo-decision-embed:before,.omo-pv-editor .omo-simple-html-render .omo-event-embed:before,.omo-pv-editor .omo-simple-html-render .omo-project-embed:before,.omo-pv-editor .omo-simple-html-render .omo-checklist-embed:before{content:"";position:absolute;top:50%;left:14px;width:30px;height:30px;transform:translateY(-50%);background:var(--color-primary,#2563eb);-webkit-mask:url("/omo/images/tools/documents-folder.png") center/contain no-repeat;mask:url("/omo/images/tools/documents-folder.png") center/contain no-repeat;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed:before,.omo-pv-editor .omo-simple-html-render .omo-decision-embed:before{background:#7c3aed;-webkit-mask-image:url("/omo/images/tools/decision.png");mask-image:url("/omo/images/tools/decision.png");}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed:before,.omo-pv-editor .omo-simple-html-render .omo-event-embed:before{background:#0f766e;-webkit-mask-image:url("/omo/images/tools/calendar.png");mask-image:url("/omo/images/tools/calendar.png");}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed:before,.omo-pv-editor .omo-simple-html-render .omo-project-embed:before{background:#2563eb;-webkit-mask-image:url("/omo/images/tools/product.png");mask-image:url("/omo/images/tools/product.png");}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-checklist-embed:before,.omo-pv-editor .omo-simple-html-render .omo-checklist-embed:before{background:#0f766e;-webkit-mask-image:url("/omo/images/tools/checklist.png");mask-image:url("/omo/images/tools/checklist.png");}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-checklist-embed>strong,.omo-pv-editor .omo-simple-html-render .omo-checklist-embed .omo-project-embed__head{display:block;margin:0;font-weight:750;}.omo-pv-editor .omo-simple-html-field .note-editable .omo-checklist-embed a,.omo-pv-editor .omo-simple-html-render .omo-checklist-embed a{color:var(--color-text,#1f2937);text-decoration:none;}.omo-checklist-embed__review{display:grid;gap:4px;margin-top:7px;}.omo-checklist-embed__review-label{font-size:11px;font-weight:750;color:var(--color-text-light,#64748b);}.omo-checklist-embed__review.is-overdue .omo-checklist-embed__review-label{color:#b45309;}.omo-pv-editor .note-editable .omo-checklist-embed__container-toggle{display:block;width:100%;padding:3px 0;border:0;background:transparent;cursor:pointer;}.omo-pv-editor .note-editable .omo-checklist-embed__container-toggle:hover,.omo-pv-editor .note-editable .omo-checklist-embed__container-toggle.is-expanded{filter:brightness(.96);}.omo-pv-editor .note-editable .omo-checklist-embed__instances{display:grid;gap:5px;}.omo-pv-editor .note-editable .omo-checklist-embed__instance{display:grid;gap:3px;}.omo-pv-editor .note-editable .omo-checklist-embed__instance-toggle{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;padding:4px 5px;border:0;border-radius:var(--radius-sm);background:transparent;color:var(--color-text,#1f2937);cursor:pointer;font:inherit;text-align:left;}.omo-pv-editor .note-editable .omo-checklist-embed__instance-toggle:hover,.omo-pv-editor .note-editable .omo-checklist-embed__instance-toggle.is-expanded{background:color-mix(in srgb,var(--color-primary,#2563eb) 8%,transparent);}.omo-pv-editor .note-editable .omo-checklist-embed__instance-title{min-width:0;overflow:hidden;font-size:.78rem;font-weight:700;text-overflow:ellipsis;white-space:nowrap;}.omo-pv-editor .note-editable .omo-checklist-embed__instance-bar{display:block;min-height:7px;}.omo-pv-editor .note-editable .omo-checklist-embed__items-bar{height:7px;}.omo-pv-editor .note-editable .omo-checklist-embed__item-segment{display:flex;min-width:0;}.omo-pv-editor .note-editable .omo-checklist-embed__item-segment .omo-project-status-bar__segment{width:100%;}.omo-pv-editor .note-editable .omo-checklist-embed__item-segment:last-child .omo-project-status-bar__segment{border-right:0;}.omo-pv-editor .note-editable .omo-checklist-embed__items-list{display:grid;gap:4px;margin-top:2px;}.omo-pv-editor .note-editable .omo-checklist-embed__item{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:3px 5px;border-radius:var(--radius-sm);background:color-mix(in srgb,var(--color-surface,#fff) 70%,var(--color-surface-alt,#f8fafc));}.omo-pv-editor .note-editable .omo-checklist-embed__item-copy{display:flex;flex-wrap:wrap;gap:3px 6px;align-items:baseline;min-width:0;}.omo-pv-editor .note-editable .omo-checklist-embed__item .omo-project-embed__child-title{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}.omo-pv-editor .note-editable .omo-checklist-embed__item-summary{display:flex;flex:0 1 42%;justify-content:flex-end;min-width:64px;}.omo-pv-editor .note-editable .omo-checklist-embed__item-summary.is-project-summary{min-width:92px;}.omo-pv-editor .note-editable .omo-checklist-embed__item-summary.is-project-summary .omo-project-status-bar{width:100%;}'
            + '.omo-pv-editor .note-editable .omo-checklist-embed__complete-archive{flex:0 0 auto;min-height:22px;padding:3px 6px;font-size:.68rem;line-height:1.1;white-space:nowrap;}.omo-pv-editor .note-editable .omo-checklist-embed__complete-archive:disabled{cursor:wait;opacity:.6;}'
            + '.omo-pv-editor .note-editable .omo-checklist-embed__empty-runs{display:block;margin:4px 0 0;color:var(--color-text-light,#64748b);font-size:.78rem;font-style:italic;line-height:1.35;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed>strong:first-of-type:not(:only-of-type),.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed>strong:first-of-type:not(:only-of-type),.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>strong:first-of-type:not(:only-of-type),.omo-pv-editor .omo-simple-html-render .omo-document-embed__label,.omo-pv-editor .omo-simple-html-render .omo-decision-embed__label{display:none;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed>strong:last-of-type,.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed>strong:last-of-type,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed>strong:last-of-type,.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>strong:last-of-type,.omo-pv-editor .omo-simple-html-render .omo-document-embed__title,.omo-pv-editor .omo-simple-html-render .omo-decision-embed__title,.omo-pv-editor .omo-simple-html-render .omo-event-embed__title{display:block;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:1;line-clamp:1;text-overflow:ellipsis;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-document-embed>em,.omo-pv-editor .omo-simple-html-field .note-editable .omo-decision-embed>em,.omo-pv-editor .omo-simple-html-field .note-editable .omo-event-embed>em,.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>em,.omo-pv-editor .omo-simple-html-render .omo-document-embed__description,.omo-pv-editor .omo-simple-html-render .omo-decision-embed__summary,.omo-pv-editor .omo-simple-html-render .omo-event-embed__summary{display:-webkit-box;overflow:hidden;margin-top:3px;color:var(--color-text-light,#6b7280);font-size:12px;font-style:normal;line-height:1.3;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-document-embed),.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-decision-embed),.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-event-embed),.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-project-embed){margin:0 0 10px;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed,.omo-pv-editor .omo-simple-html-render .omo-project-embed{min-height:68px;}.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>strong,.omo-pv-editor .omo-simple-html-render .omo-project-embed>strong{display:flex!important;align-items:center;gap:6px;min-width:0;overflow:hidden;white-space:normal;}.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>strong>a:first-child,.omo-pv-editor .omo-simple-html-render .omo-project-embed>strong>a:first-child{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>strong>a:nth-child(2),.omo-pv-editor .omo-simple-html-render .omo-project-embed>strong>a:nth-child(2){flex:0 0 auto;font-size:13px;text-decoration:none;}.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>strong>em,.omo-pv-editor .omo-simple-html-render .omo-project-embed>strong>em{display:block!important;flex:0 0 auto;margin:0;padding:1px 5px;border-radius:999px;background:color-mix(in srgb,#2563eb 10%,var(--color-surface,#fff));color:#1d4ed8;font-size:11px;font-style:normal;font-weight:800;line-height:1.35;}.omo-pv-editor .omo-simple-html-field .note-editable .omo-project-embed>em,.omo-pv-editor .omo-simple-html-render .omo-project-embed>em{display:block!important;overflow:hidden;margin-top:5px;white-space:nowrap;text-overflow:ellipsis;-webkit-line-clamp:unset;line-clamp:unset;}'
            + '.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved{display:grid;gap:7px;min-height:0;margin-bottom:10px;padding:11px 13px 11px 58px;cursor:default;line-height:1.4;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__head{display:flex;align-items:center;gap:7px;min-width:0;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__title{min-width:0;overflow:hidden;color:var(--color-text,#1f2937);font-size:.92rem;font-weight:750;text-decoration:none;text-overflow:ellipsis;white-space:nowrap;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__external{flex:0 0 auto;color:var(--color-text-light,#64748b);font-size:.86rem;text-decoration:none;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__meta{color:var(--color-text-light,#64748b);font-size:.76rem;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__status{--omo-project-status-color:#99a3b1;--omo-project-status-text:#4d5968;flex:0 0 auto;display:inline-flex;align-items:center;min-height:20px;padding:2px 7px;border-radius:999px;background:color-mix(in srgb,var(--omo-project-status-color) 19%,var(--color-surface,#fff));color:var(--omo-project-status-text);font-size:.72rem;font-style:normal;font-weight:750;line-height:1.2;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__status--ready{--omo-project-status-color:#5e88d5;--omo-project-status-text:#294c8b;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__status--in_progress{--omo-project-status-color:#d0a857;--omo-project-status-text:#735518;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__status--blocked{--omo-project-status-color:#d67272;--omo-project-status-text:#842f35;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__status--review{--omo-project-status-color:#9884c7;--omo-project-status-text:#5f4b91;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__status--done{--omo-project-status-color:#6fa98d;--omo-project-status-text:#2f6d4a;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__status--someday{--omo-project-status-color:#99a3b1;--omo-project-status-text:#4d5968;}.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__toggle,.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__children{display:none!important;}'
            + '.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__priority,.omo-pv-editor .omo-simple-html-render .omo-project-embed--resolved .omo-project-embed__size{flex:0 0 auto;display:inline-flex;align-items:center;min-height:20px;padding:2px 7px;border-radius:999px;background:color-mix(in srgb,#2563eb 11%,var(--color-surface,#fff));color:#1d4ed8;font-size:.72rem;font-weight:750;line-height:1.2;}'
            + '.omo-simple-html-field .note-editable .omo-indicator-embed,.omo-simple-html-render .omo-indicator-embed{display:block;margin:0 0 1em;padding:12px 14px;border:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 88%,#2563eb 12%);border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-surface,#fff) 90%,#eff6ff 10%);box-shadow:0 10px 24px -20px rgba(37,99,235,.45);cursor:pointer;white-space:normal;line-height:1.3;}'
            + '.omo-indicator-embed--overdue{border-color:color-mix(in srgb,#dc2626 36%,var(--color-border,#d1d5db));background:color-mix(in srgb,var(--color-surface,#fff) 92%,#fef2f2 8%);}'
            + '.omo-indicator-embed--overdue .omo-indicator-embed__chart{border-color:color-mix(in srgb,#dc2626 36%,var(--color-border,#d1d5db));background:linear-gradient(135deg,color-mix(in srgb,#dc2626 10%,var(--color-surface,#fff)),color-mix(in srgb,#dc2626 4%,var(--color-surface-alt,#f8fafc)));}'
            + '.omo-indicator-embed--warning{border-color:color-mix(in srgb,#eab308 42%,var(--color-border,#d1d5db));background:color-mix(in srgb,var(--color-surface,#fff) 94%,#fef9c3 6%);}'
            + '.omo-indicator-embed--warning .omo-indicator-embed__chart{border-color:color-mix(in srgb,#eab308 42%,var(--color-border,#d1d5db));background:linear-gradient(135deg,color-mix(in srgb,#eab308 10%,var(--color-surface,#fff)),color-mix(in srgb,#eab308 4%,var(--color-surface-alt,#f8fafc)));}'
            + '.omo-indicator-embed--current .omo-indicator-embed__values em{color:#15803d;}'
            + '.omo-indicator-embed--warning .omo-indicator-embed__values em{color:#a16207;}'
            + '.omo-indicator-embed__main{display:grid;grid-template-columns:minmax(0,210px) minmax(0,1fr) 128px;gap:12px;align-items:stretch;}'
            + '.omo-indicator-embed__chart{grid-column:1;grid-row:1;position:relative;display:block;width:100%;max-width:210px;min-width:0;aspect-ratio:16 / 9;padding:6px 7px;border:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 68%,#2563eb 32%);border-radius:var(--radius-md);background:linear-gradient(135deg,color-mix(in srgb,var(--color-primary,#2563eb) 9%,var(--color-surface,#fff)),color-mix(in srgb,var(--color-primary,#2563eb) 3%,var(--color-surface-alt,#f8fafc)));box-shadow:0 12px 26px -21px color-mix(in srgb,var(--color-primary,#2563eb) 68%,transparent);box-sizing:border-box;color:#2563eb;}'
            + '.omo-indicator-embed__chart-plot{position:absolute;left:7px;right:7px;top:50%;aspect-ratio:180 / 54;transform:translateY(-50%);}'
            + '.omo-indicator-embed__chart-svg{position:absolute;inset:0;display:block;width:100%;height:100%;}'
            + '.omo-indicator-embed__chart-svg svg{display:block;width:100%;height:100%;overflow:visible;}'
            + '.omo-indicator-embed__copy{grid-column:2;grid-row:1;display:flex;flex-direction:column;justify-content:center;min-width:0;gap:5px;}'
            + '.omo-indicator-embed__title{display:flex;align-items:center;gap:6px;min-width:0;color:var(--color-text,#1f2937);font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}'
            + '.omo-indicator-embed__title>span:last-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}'
            + '.omo-indicator-embed__description{display:-webkit-box;overflow:hidden;margin-top:1px;color:var(--color-text-light,#64748b);font-size:inherit;font-weight:400;line-height:1.3;-webkit-box-orient:vertical;-webkit-line-clamp:3;line-clamp:3;}'
            + '.omo-indicator-embed__status-dot{width:8px;height:8px;flex:0 0 auto;border-radius:999px;background:#94a3b8;box-shadow:0 0 0 3px color-mix(in srgb,#94a3b8 14%,transparent);}'
            + '.omo-indicator-embed__status-dot--current{background:#16a34a;box-shadow:0 0 0 3px color-mix(in srgb,#16a34a 14%,transparent);}'
            + '.omo-indicator-embed__status-dot--warning{background:#eab308;box-shadow:0 0 0 3px color-mix(in srgb,#eab308 18%,transparent);}'
            + '.omo-indicator-embed__status-dot--overdue{background:#dc2626;box-shadow:0 0 0 3px color-mix(in srgb,#dc2626 14%,transparent);}'
            + '.omo-indicator-embed--warning .omo-indicator-embed__chart{color:#ca8a04;}'
            + '.omo-indicator-embed--overdue .omo-indicator-embed__chart{color:#dc2626;}'
            + '.omo-indicator-embed--warning .omo-indicator-embed__chart .omo-stats-chart--overdue{color:#ca8a04;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__line{fill:none;stroke:currentColor;stroke-width:2.4;stroke-linecap:round;stroke-linejoin:round;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__line--background{stroke-width:2;opacity:.18;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__line--sum{stroke-width:4;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__reference{fill:none;stroke:color-mix(in srgb,currentColor 42%,transparent);stroke-width:1.7;stroke-dasharray:4 3;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__reference--ceiling{stroke-dasharray:none;stroke-width:3;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__baseline{fill:none;stroke:color-mix(in srgb,var(--color-text-light,#64748b) 72%,transparent);stroke-width:1.5;stroke-dasharray:3 5;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__point{fill:currentColor;stroke:var(--color-surface,#fff);stroke-width:1.5;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__scale-line{fill:none;stroke:color-mix(in srgb,currentColor 48%,transparent);stroke-width:1.2;stroke-dasharray:4 3;}'
            + '.omo-indicator-embed__chart-svg .omo-stats-chart__scale-label{fill:var(--color-text-light,#64748b);font-size:9px;}'
            + '.omo-indicator-embed__values{grid-column:3;grid-row:1;display:grid;justify-items:end;align-content:center;gap:1px;width:128px;min-width:128px;color:var(--color-text-light,#6b7280);font-size:12px;text-align:right;}'
            + '.omo-indicator-embed__values b{color:var(--color-text,#1f2937);font-size:1.05rem;}'
            + '.omo-indicator-embed__values time{font-size:11px;}'
            + '.omo-indicator-embed__values em{font-style:normal;color:#b91c1c;font-weight:700;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-indicator-embed,.omo-pv-editor .omo-simple-html-render .omo-indicator-embed{margin-bottom:10px;padding:9px 12px;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-indicator-embed__value-entry{display:flex;align-items:center;justify-content:flex-end;gap:5px;margin-top:3px;padding-top:3px;border-top:1px solid color-mix(in srgb,var(--color-border,#d1d5db) 72%,transparent);}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-indicator-embed__value-input{min-width:0;width:74px;padding:3px 6px;border:1px solid var(--color-border,#d1d5db);border-radius:var(--radius-md);background:var(--color-surface,#fff);color:var(--color-text,#1f2937);font-size:11px;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-indicator-embed__value-button{width:22px;height:22px;padding:0;border:0;border-radius:999px;background:var(--color-primary,#2563eb);color:#fff;font-size:16px;font-weight:800;line-height:1;cursor:pointer;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable .omo-indicator-embed__value-button:disabled{opacity:.55;cursor:wait;}'
            + '.omo-pv-editor .omo-simple-html-field .note-editable p:has(>.omo-indicator-embed){margin:0 0 10px;}'
            + '@media (max-width:760px){.omo-indicator-embed__main{grid-template-columns:minmax(0,1fr) 120px;}.omo-indicator-embed__chart{grid-column:1 / -1;grid-row:1;}.omo-indicator-embed__copy{grid-column:1;grid-row:2;}.omo-indicator-embed__values{grid-column:2;grid-row:2;width:120px;min-width:120px;}}'
            + '.omo-pv-editor__indicator-embed-button{min-width:34px!important;font-size:0!important;background-image:url("/omo/images/tools/stats.png")!important;background-position:center!important;background-repeat:no-repeat!important;background-size:20px 20px!important;}'
            + '.omo-simple-html-field__meta{font-size:12px;line-height:1.45;color:var(--color-text-light,#6b7280);}'
            + '.omo-simple-html-render{line-height:1.55;word-break:break-word;white-space:normal;}'
            + '.omo-simple-html-render > :first-child{margin-top:0;}'
            + '.omo-simple-html-render > :last-child{margin-bottom:0;}'
            + '.omo-simple-html-render p{margin:0 0 .85em;}'
            + '.omo-simple-html-render ul,.omo-simple-html-render ol{margin:.2em 0;padding-left:1.35em;}'
            + '.omo-simple-html-render li + li{margin-top:.28em;}'
            + '.omo-simple-html-render a{color:var(--color-primary,#2563eb);text-decoration:underline;}';
        document.head.appendChild(style);
        stylesInjected = true;
    }

    function ensureStylesheet(url) {
        return new Promise(function (resolve, reject) {
            const existing = document.querySelector('link[data-omo-summernote-href="' + url + '"]');
            if (existing) {
                resolve();
                return;
            }

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.setAttribute('data-omo-summernote-href', url);
            link.onload = function () { resolve(); };
            link.onerror = function () { reject(new Error('Impossible de charger la feuille de style Summernote.')); };
            document.head.appendChild(link);
        });
    }

    function ensureScript(url) {
        return new Promise(function (resolve, reject) {
            const existing = document.querySelector('script[data-omo-summernote-src="' + url + '"]');
            if (existing) {
                if (existing.getAttribute('data-loaded') === '1') {
                    resolve();
                    return;
                }

                existing.addEventListener('load', function () { resolve(); }, { once: true });
                existing.addEventListener('error', function () { reject(new Error('Impossible de charger Summernote.')); }, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = url;
            script.async = false;
            script.setAttribute('data-omo-summernote-src', url);
            script.onload = function () {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = function () { reject(new Error('Impossible de charger Summernote.')); };
            document.head.appendChild(script);
        });
    }

    function ensureDependencies() {
        if (dependencyPromise) {
            return dependencyPromise;
        }

        dependencyPromise = Promise.resolve()
            .then(function () {
                if (!window.jQuery) {
                    throw new Error('jQuery est requis pour Summernote.');
                }

                ensureLocalStyles();
                return ensureStylesheet(SUMMERNOTE_CSS_URL);
            })
            .then(function () {
                return ensureScript(SUMMERNOTE_JS_URL);
            })
            .then(function () {
                return ensureScript(SUMMERNOTE_LANG_URL);
            })
            .then(function () {
                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.summernote !== 'function') {
                    throw new Error('Summernote n est pas disponible apres chargement.');
                }
            });

        return dependencyPromise;
    }

    function sanitizeUrl(url) {
        const value = String(url || '').trim();
        if (!value) {
            return '';
        }

        if (/^(#|\/)/.test(value)) {
            return value;
        }

        if (!/^[a-z][a-z0-9+.-]*:/i.test(value)) {
            return value;
        }

        return /^(https?:|mailto:|tel:)/i.test(value) ? value : '';
    }

    function appendSanitizedChild(parentNode, childNode) {
        if (childNode && childNode.nodeType === 11 && !childNode.hasChildNodes()) {
            return;
        }

        parentNode.appendChild(childNode);
    }

    function getElementAttributeValue(element, attributeName) {
        if (!element || element.nodeType !== 1 || !element.hasAttribute(attributeName)) {
            return '';
        }

        return String(element.getAttribute(attributeName) || '');
    }

    function getDocumentEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-document-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getDecisionEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-decision-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getProjectEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-project-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getChecklistEmbedElementId(element) {
        const parsed = Number.parseInt(getElementAttributeValue(element, 'data-omo-checklist-id').trim(), 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getEventEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-event-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function getIndicatorEmbedElementId(element) {
        const rawValue = getElementAttributeValue(element, 'data-omo-indicator-id').trim();
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
    }

    function isTemporaryCursorMarkerElement(element) {
        return !!(element && element.nodeType === 1 && getElementAttributeValue(element, 'data-omo-cursor-marker').trim() !== '');
    }

    function isAllowedDocumentEmbedElement(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }

        return getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'document'
            && getDocumentEmbedElementId(element) > 0;
    }

    function isAllowedDecisionEmbedElement(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }

        return getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'decision'
            && getDecisionEmbedElementId(element) > 0;
    }

    function isAllowedEventEmbedElement(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }

        return getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'event'
            && getEventEmbedElementId(element) > 0;
    }

    function isAllowedProjectEmbedElement(element) {
        if (!element || element.nodeType !== 1) {
            return false;
        }

        return getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'project'
            && getProjectEmbedElementId(element) > 0;
    }

    function isAllowedChecklistEmbedElement(element) {
        return !!element && element.nodeType === 1
            && getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'checklist'
            && getChecklistEmbedElementId(element) > 0;
    }

    function isAllowedIndicatorEmbedElement(element) {
        return !!element
            && element.nodeType === 1
            && getElementAttributeValue(element, 'data-omo-embed-type').trim() === 'indicator'
            && getIndicatorEmbedElementId(element) > 0;
    }

    function isSafeSvgNumber(value) {
        return /^-?\d+(?:\.\d+)?$/.test(String(value || '').trim());
    }

    function isSafeSvgPoints(value) {
        const normalizedValue = String(value || '').trim();
        return normalizedValue.length <= 4000 && /^-?[\d.]+,-?[\d.]+(?:\s+-?[\d.]+,-?[\d.]+)*$/.test(normalizedValue);
    }

    function getIndicatorEmbedStatusLabel(element) {
        const attributeValue = getElementAttributeValue(element, 'data-omo-indicator-status').trim();
        if (attributeValue) {
            return attributeValue;
        }

        const statusNode = element && element.querySelector
            ? element.querySelector('.omo-indicator-embed__values em')
            : null;
        return statusNode ? String(statusNode.textContent || '').trim() : '';
    }

    function appendSanitizedIndicatorChart(embedNode, sourceNode, ownerDocument) {
        const sourceChart = sourceNode.querySelector && sourceNode.querySelector('svg.omo-stats-chart');
        if (!sourceChart) {
            return;
        }

        const chart = ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'svg');
        const indicatorKind = getElementAttributeValue(sourceNode, 'data-omo-indicator-kind').trim() === 'group' ? 'group' : 'indicator';
        chart.setAttribute('class', 'omo-stats-chart omo-stats-chart--compact' + (indicatorKind === 'group' ? ' omo-stats-chart--group' : ''));
        chart.setAttribute('viewBox', '0 0 180 54');
        chart.setAttribute('aria-hidden', 'true');
        ['polyline', 'circle'].forEach(function (tagName) {
            Array.from(sourceChart.querySelectorAll(tagName)).forEach(function (sourceShape) {
                const className = String(sourceShape.getAttribute('class') || '');
                if (tagName === 'polyline' && !/^omo-stats-chart__line(?: omo-stats-chart__line--(?:background|sum))?$/.test(className) && className !== 'omo-stats-chart__reference') {
                    return;
                }
                if (tagName === 'circle' && className !== 'omo-stats-chart__point') {
                    return;
                }

                const shape = ownerDocument.createElementNS('http://www.w3.org/2000/svg', tagName);
                shape.setAttribute('class', className);
                if (tagName === 'polyline') {
                    const points = String(sourceShape.getAttribute('points') || '').trim();
                    if (!isSafeSvgPoints(points)) {
                        return;
                    }
                    shape.setAttribute('points', points);
                    const strokeStyle = String(sourceShape.getAttribute('style') || '').trim();
                    if (/^stroke:\s*#[0-9a-f]{6};?$/i.test(strokeStyle)) {
                        shape.setAttribute('style', strokeStyle);
                    }
                } else {
                    const cx = String(sourceShape.getAttribute('cx') || '').trim();
                    const cy = String(sourceShape.getAttribute('cy') || '').trim();
                    const radius = String(sourceShape.getAttribute('r') || '').trim();
                    if (!isSafeSvgNumber(cx) || !isSafeSvgNumber(cy) || !isSafeSvgNumber(radius)) {
                        return;
                    }
                    shape.setAttribute('cx', cx);
                    shape.setAttribute('cy', cy);
                    shape.setAttribute('r', radius);
                }
                chart.appendChild(shape);
            });
        });

        ['line', 'text'].forEach(function (tagName) {
            Array.from(sourceChart.querySelectorAll(tagName)).forEach(function (sourceShape) {
                const className = String(sourceShape.getAttribute('class') || '');
                if (tagName === 'line' && [
                    'omo-stats-chart__scale-line',
                    'omo-stats-chart__reference omo-stats-chart__reference--ceiling',
                    'omo-stats-chart__baseline'
                ].indexOf(className) < 0) {
                    return;
                }
                if (tagName === 'text' && className !== 'omo-stats-chart__scale-label') {
                    return;
                }

                const shape = ownerDocument.createElementNS('http://www.w3.org/2000/svg', tagName);
                shape.setAttribute('class', className);
                if (tagName === 'line') {
                    const x1 = String(sourceShape.getAttribute('x1') || '').trim();
                    const y1 = String(sourceShape.getAttribute('y1') || '').trim();
                    const x2 = String(sourceShape.getAttribute('x2') || '').trim();
                    const y2 = String(sourceShape.getAttribute('y2') || '').trim();
                    if (![x1, y1, x2, y2].every(isSafeSvgNumber)) {
                        return;
                    }
                    shape.setAttribute('x1', x1);
                    shape.setAttribute('y1', y1);
                    shape.setAttribute('x2', x2);
                    shape.setAttribute('y2', y2);
                } else {
                    const x = String(sourceShape.getAttribute('x') || '').trim();
                    const y = String(sourceShape.getAttribute('y') || '').trim();
                    const label = String(sourceShape.textContent || '').trim();
                    if (!isSafeSvgNumber(x) || !isSafeSvgNumber(y) || !/^-?[\d.,\s]+$/.test(label)) {
                        return;
                    }
                    shape.setAttribute('x', x);
                    shape.setAttribute('y', y);
                    shape.textContent = label;
                }
                chart.appendChild(shape);
            });
        });

        const chartMinLabel = getElementAttributeValue(sourceNode, 'data-omo-indicator-chart-min').trim();
        const chartMaxLabel = getElementAttributeValue(sourceNode, 'data-omo-indicator-chart-max').trim();
        if (chartMinLabel && chartMaxLabel && !chart.querySelector('.omo-stats-chart__scale-line')) {
            const scaleLineTop = ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'line');
            scaleLineTop.setAttribute('class', 'omo-stats-chart__scale-line');
            scaleLineTop.setAttribute('x1', '20');
            scaleLineTop.setAttribute('y1', '2');
            scaleLineTop.setAttribute('x2', '178');
            scaleLineTop.setAttribute('y2', '2');
            chart.appendChild(scaleLineTop);
            const scaleLineBottom = ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'line');
            scaleLineBottom.setAttribute('class', 'omo-stats-chart__scale-line');
            scaleLineBottom.setAttribute('x1', '20');
            scaleLineBottom.setAttribute('y1', '52');
            scaleLineBottom.setAttribute('x2', '178');
            scaleLineBottom.setAttribute('y2', '52');
            chart.appendChild(scaleLineBottom);
        }
        if (chartMinLabel && chartMaxLabel && !chart.querySelector('.omo-stats-chart__scale-label')) {
            const scaleLabelTop = ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'text');
            scaleLabelTop.setAttribute('class', 'omo-stats-chart__scale-label');
            scaleLabelTop.setAttribute('x', '0');
            scaleLabelTop.setAttribute('y', '6');
            scaleLabelTop.textContent = chartMaxLabel;
            chart.appendChild(scaleLabelTop);
            const scaleLabelBottom = ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'text');
            scaleLabelBottom.setAttribute('class', 'omo-stats-chart__scale-label');
            scaleLabelBottom.setAttribute('x', '0');
            scaleLabelBottom.setAttribute('y', '52');
            scaleLabelBottom.textContent = chartMinLabel;
            chart.appendChild(scaleLabelBottom);
        }

        if (chart.childNodes.length === 0) {
            return;
        }

        const chartWrapper = ownerDocument.createElement('span');
        chartWrapper.setAttribute('class', 'omo-indicator-embed__chart-svg');
        chartWrapper.appendChild(chart);
        embedNode.appendChild(chartWrapper);
    }

    function refreshIndicatorValueControls(editable, ui) {
        if (!editable) {
            return;
        }

        const config = ui && typeof ui === 'object' ? ui : null;
        const allowedIds = config && Array.isArray(config.allowedIndicatorIds)
            ? config.allowedIndicatorIds.map(function (value) { return String(value); })
            : [];
        editable.querySelectorAll('.omo-indicator-embed[data-omo-embed-type="indicator"]').forEach(function (embedNode) {
            const existingEntry = embedNode.querySelector('.omo-indicator-embed__value-entry');
            if (existingEntry) {
                existingEntry.remove();
            }

            const indicatorKind = getElementAttributeValue(embedNode, 'data-omo-indicator-kind').trim();
            const indicatorId = String(getIndicatorEmbedElementId(embedNode));
            if (!config || !config.enabled || indicatorKind === 'group' || allowedIds.indexOf(indicatorId) < 0) {
                return;
            }

            const entryNode = editable.ownerDocument.createElement('span');
            entryNode.setAttribute('class', 'omo-indicator-embed__value-entry');
            entryNode.setAttribute('contenteditable', 'false');
            const inputNode = editable.ownerDocument.createElement('input');
            inputNode.setAttribute('class', 'omo-indicator-embed__value-input');
            inputNode.setAttribute('type', 'text');
            inputNode.setAttribute('inputmode', 'decimal');
            inputNode.setAttribute('data-omo-indicator-value-input', '1');
            inputNode.setAttribute('placeholder', String(config.placeholder || 'Nouvelle valeur'));
            inputNode.setAttribute('aria-label', String(config.inputLabel || config.placeholder || 'Nouvelle valeur'));
            inputNode.addEventListener('input', function (event) {
                event.stopPropagation();
            });
            const buttonNode = editable.ownerDocument.createElement('button');
            buttonNode.setAttribute('class', 'omo-indicator-embed__value-button');
            buttonNode.setAttribute('type', 'button');
            buttonNode.setAttribute('data-omo-indicator-add-value', '1');
            buttonNode.setAttribute('aria-label', String(config.addLabel || 'Ajouter maintenant'));
            buttonNode.setAttribute('title', String(config.addLabel || 'Ajouter maintenant'));
            buttonNode.textContent = '+';
            inputNode.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    buttonNode.click();
                }
            });
            entryNode.appendChild(inputNode);
            entryNode.appendChild(buttonNode);
            const valuesNode = embedNode.querySelector('.omo-indicator-embed__values')
                || embedNode.querySelector('.omo-indicator-embed__copy')
                || embedNode;
            valuesNode.appendChild(entryNode);
        });
    }

    function emitIndicatorValueAdd(targetNode, event) {
        if (!targetNode || !targetNode.closest) {
            return;
        }

        const buttonNode = targetNode.closest('[data-omo-indicator-add-value="1"]');
        if (!buttonNode) {
            return;
        }

        const embedNode = buttonNode.closest('.omo-indicator-embed[data-omo-embed-type="indicator"]');
        const inputNode = embedNode ? embedNode.querySelector('[data-omo-indicator-value-input="1"]') : null;
        if (!embedNode || !inputNode) {
            return;
        }

        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
            event.stopPropagation();
        }
        return {
            indicatorId: getIndicatorEmbedElementId(embedNode),
            node: embedNode,
            input: inputNode,
            button: buttonNode,
            api: null,
        };
    }

    function buildSanitizedNode(sourceNode, ownerDocument) {
        if (!sourceNode) {
            return ownerDocument.createDocumentFragment();
        }

        if (sourceNode.nodeType === 3) {
            return ownerDocument.createTextNode(sourceNode.nodeValue || '');
        }

        if (sourceNode.nodeType !== 1) {
            return ownerDocument.createDocumentFragment();
        }

        const sourceTagName = String(sourceNode.tagName || '').toUpperCase();
        if (!sourceTagName) {
            return ownerDocument.createDocumentFragment();
        }

        if (['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'META', 'LINK'].indexOf(sourceTagName) >= 0) {
            return ownerDocument.createDocumentFragment();
        }

        if (isTemporaryCursorMarkerElement(sourceNode)) {
            return ownerDocument.createDocumentFragment();
        }

        if (sourceNode.hasAttribute('data-omo-project-embed-runtime') || sourceNode.hasAttribute('data-omo-checklist-embed-runtime')) {
            return ownerDocument.createDocumentFragment();
        }

        if (isAllowedDocumentEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-document-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'document');
            embedNode.setAttribute('data-omo-document-id', String(getDocumentEmbedElementId(sourceNode)));

            const title = getElementAttributeValue(sourceNode, 'data-omo-document-title').trim();
            if (title) {
                embedNode.setAttribute('data-omo-document-title', title);
            }

            const description = getElementAttributeValue(sourceNode, 'data-omo-document-description').trim();
            if (description) {
                embedNode.setAttribute('data-omo-document-description', description);
            }

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return embedNode;
        }

        if (isAllowedDecisionEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-decision-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'decision');
            embedNode.setAttribute('data-omo-decision-id', String(getDecisionEmbedElementId(sourceNode)));

            const title = getElementAttributeValue(sourceNode, 'data-omo-decision-title').trim();
            if (title) {
                embedNode.setAttribute('data-omo-decision-title', title);
            }

            const type = getElementAttributeValue(sourceNode, 'data-omo-decision-type').trim();
            if (type) {
                embedNode.setAttribute('data-omo-decision-type', type);
            }

            const summary = getElementAttributeValue(sourceNode, 'data-omo-decision-summary').trim();
            if (summary) {
                embedNode.setAttribute('data-omo-decision-summary', summary);
            }

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return embedNode;
        }

        if (isAllowedEventEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-event-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'event');
            embedNode.setAttribute('data-omo-event-id', String(getEventEmbedElementId(sourceNode)));

            ['title', 'schedule', 'location', 'description'].forEach(function (attributeName) {
                const value = getElementAttributeValue(sourceNode, 'data-omo-event-' + attributeName).trim();
                if (value) {
                    embedNode.setAttribute('data-omo-event-' + attributeName, value);
                }
            });

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return embedNode;
        }

        if (isAllowedProjectEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-project-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'project');
            embedNode.setAttribute('data-omo-project-id', String(getProjectEmbedElementId(sourceNode)));

            const title = getElementAttributeValue(sourceNode, 'data-omo-project-title').trim();
            if (title) {
                embedNode.setAttribute('data-omo-project-title', title);
            }

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return embedNode;
        }

        if (isAllowedChecklistEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            embedNode.setAttribute('class', 'omo-checklist-embed');
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'checklist');
            embedNode.setAttribute('data-omo-checklist-id', String(getChecklistEmbedElementId(sourceNode)));
            const title = getElementAttributeValue(sourceNode, 'data-omo-checklist-title').trim();
            if (title) { embedNode.setAttribute('data-omo-checklist-title', title); }
            ['STRONG', 'EM'].forEach(function (tagName) {
                const childNode = Array.from(sourceNode.children || []).find(function (candidate) {
                    return String(candidate.tagName || '').toUpperCase() === tagName;
                });
                if (childNode) appendSanitizedChild(embedNode, buildSanitizedNode(childNode, ownerDocument));
            });
            return embedNode;
        }

        if (isAllowedIndicatorEmbedElement(sourceNode)) {
            const embedNode = ownerDocument.createElement('span');
            const sourceClassName = ' ' + String(sourceNode.getAttribute('class') || '').trim() + ' ';
            const isOverdue = getElementAttributeValue(sourceNode, 'data-omo-indicator-overdue').trim() === '1'
                || sourceClassName.indexOf(' omo-indicator-embed--overdue ') >= 0
                || sourceClassName.indexOf(' omo-indicator-embed--warning ') >= 0;
            const overdueSeverity = getElementAttributeValue(sourceNode, 'data-omo-indicator-overdue-severity').trim() === 'warning'
                || sourceClassName.indexOf(' omo-indicator-embed--warning ') >= 0
                ? 'warning'
                : 'error';
            const statusLabel = getIndicatorEmbedStatusLabel(sourceNode);
            const hasStatus = statusLabel !== '' || sourceClassName.indexOf(' omo-indicator-embed--current ') >= 0;
            embedNode.setAttribute('class', 'omo-indicator-embed' + (isOverdue ? (overdueSeverity === 'warning' ? ' omo-indicator-embed--warning' : ' omo-indicator-embed--overdue') : (hasStatus ? ' omo-indicator-embed--current' : '')));
            embedNode.setAttribute('contenteditable', 'false');
            embedNode.setAttribute('data-omo-embed-type', 'indicator');
            embedNode.setAttribute('data-omo-indicator-id', String(getIndicatorEmbedElementId(sourceNode)));
            const indicatorKind = getElementAttributeValue(sourceNode, 'data-omo-indicator-kind').trim() === 'group' ? 'group' : 'indicator';
            embedNode.setAttribute('data-omo-indicator-kind', indicatorKind);
            ['title', 'description', 'value', 'date', 'context', 'chart-min', 'chart-max', 'overdue-severity'].forEach(function (attributeName) {
                const value = getElementAttributeValue(sourceNode, 'data-omo-indicator-' + attributeName).trim();
                if (value) {
                    embedNode.setAttribute('data-omo-indicator-' + attributeName, value);
                }
            });
            if (statusLabel) {
                embedNode.setAttribute('data-omo-indicator-status', statusLabel);
            }
            if (isOverdue) {
                embedNode.setAttribute('data-omo-indicator-overdue', '1');
            }

            const title = getElementAttributeValue(sourceNode, 'data-omo-indicator-title').trim() || ('Indicateur #' + String(getIndicatorEmbedElementId(sourceNode)));
            const description = getElementAttributeValue(sourceNode, 'data-omo-indicator-description').trim();
            const chartMinLabel = getElementAttributeValue(sourceNode, 'data-omo-indicator-chart-min').trim();
            const chartMaxLabel = getElementAttributeValue(sourceNode, 'data-omo-indicator-chart-max').trim();
            const chartNode = ownerDocument.createElement('span');
            chartNode.setAttribute('class', 'omo-indicator-embed__chart');
            const chartPlotNode = ownerDocument.createElement('span');
            chartPlotNode.setAttribute('class', 'omo-indicator-embed__chart-plot');
            appendSanitizedIndicatorChart(chartPlotNode, sourceNode, ownerDocument);
            chartNode.appendChild(chartPlotNode);
            const titleNode = ownerDocument.createElement('strong');
            const linkNode = ownerDocument.createElement('a');
            linkNode.setAttribute('class', 'omo-indicator-embed__title');
            linkNode.setAttribute('href', indicatorKind === 'group' ? '#stats' : ('#stats-i' + String(getIndicatorEmbedElementId(sourceNode))));
            const statusDotNode = ownerDocument.createElement('span');
            statusDotNode.setAttribute('class', 'omo-indicator-embed__status-dot'
                + (isOverdue ? (overdueSeverity === 'warning' ? ' omo-indicator-embed__status-dot--warning' : ' omo-indicator-embed__status-dot--overdue') : (hasStatus ? ' omo-indicator-embed__status-dot--current' : ' omo-indicator-embed__status-dot--unknown')));
            statusDotNode.setAttribute('aria-hidden', 'true');
            const titleTextNode = ownerDocument.createElement('span');
            titleTextNode.textContent = title;
            linkNode.appendChild(statusDotNode);
            linkNode.appendChild(titleTextNode);
            titleNode.appendChild(linkNode);
            const mainNode = ownerDocument.createElement('span');
            mainNode.setAttribute('class', 'omo-indicator-embed__main');
            mainNode.appendChild(chartNode);
            const copyNode = ownerDocument.createElement('span');
            copyNode.setAttribute('class', 'omo-indicator-embed__copy');
            copyNode.appendChild(titleNode);
            if (description) {
                const descriptionNode = ownerDocument.createElement('span');
                descriptionNode.setAttribute('class', 'omo-indicator-embed__description');
                descriptionNode.textContent = description;
                copyNode.appendChild(descriptionNode);
            }
            const valuesNode = ownerDocument.createElement('span');
            valuesNode.setAttribute('class', 'omo-indicator-embed__values');
            const valueLabel = getElementAttributeValue(sourceNode, 'data-omo-indicator-value').trim();
            const dateLabel = getElementAttributeValue(sourceNode, 'data-omo-indicator-date').trim();
            if (valueLabel) { const valueNode = ownerDocument.createElement('b'); valueNode.textContent = valueLabel; valuesNode.appendChild(valueNode); }
            if (dateLabel) { const dateNode = ownerDocument.createElement('time'); dateNode.textContent = dateLabel; valuesNode.appendChild(dateNode); }
            if (statusLabel) { const statusNode = ownerDocument.createElement('em'); statusNode.textContent = statusLabel; valuesNode.appendChild(statusNode); }
            mainNode.appendChild(copyNode);
            mainNode.appendChild(valuesNode);
            embedNode.appendChild(mainNode);
            return embedNode;
        }

        const normalizedTagName = sourceTagName === 'DIV' ? 'P' : sourceTagName;
        const allowedTags = {
            P: true,
            H1: true,
            H2: true,
            H3: true,
            BLOCKQUOTE: true,
            TABLE: true,
            THEAD: true,
            TBODY: true,
            TR: true,
            TH: true,
            TD: true,
            BR: true,
            STRONG: true,
            B: true,
            EM: true,
            I: true,
            U: true,
            UL: true,
            OL: true,
            LI: true,
            A: true
        };

        if (!allowedTags[normalizedTagName]) {
            const fragment = ownerDocument.createDocumentFragment();
            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(fragment, buildSanitizedNode(childNode, ownerDocument));
            });
            return fragment;
        }

        if (normalizedTagName === 'A') {
            const href = sanitizeUrl(sourceNode.getAttribute('href') || '');
            if (!href) {
                const anchorFragment = ownerDocument.createDocumentFragment();
                Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                    appendSanitizedChild(anchorFragment, buildSanitizedNode(childNode, ownerDocument));
                });
                return anchorFragment;
            }

            const anchorNode = ownerDocument.createElement('a');
            anchorNode.setAttribute('href', href);

            const target = String(sourceNode.getAttribute('target') || '').trim().toLowerCase();
            if (target === '_blank') {
                anchorNode.setAttribute('target', '_blank');
                anchorNode.setAttribute('rel', 'noopener noreferrer');
            }

            Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
                appendSanitizedChild(anchorNode, buildSanitizedNode(childNode, ownerDocument));
            });

            return anchorNode;
        }

        const elementNode = ownerDocument.createElement(normalizedTagName.toLowerCase());
        if (normalizedTagName === 'TH' || normalizedTagName === 'TD') {
            const colspan = Number.parseInt(sourceNode.getAttribute('colspan') || '', 10);
            const rowspan = Number.parseInt(sourceNode.getAttribute('rowspan') || '', 10);

            if (Number.isInteger(colspan) && colspan > 1) {
                elementNode.setAttribute('colspan', String(colspan));
            }

            if (Number.isInteger(rowspan) && rowspan > 1) {
                elementNode.setAttribute('rowspan', String(rowspan));
            }
        }

        Array.from(sourceNode.childNodes || []).forEach(function (childNode) {
            appendSanitizedChild(elementNode, buildSanitizedNode(childNode, ownerDocument));
        });

        return elementNode;
    }

    function normalizeHtmlValue(html) {
        const rawHtml = String(html || '').trim();
        if (!rawHtml) {
            return '';
        }

        const textValue = rawHtml
            .replace(/<br\s*\/?>/gi, ' ')
            .replace(/<\/(p|li)>/gi, ' ')
            .replace(/<[^>]+>/g, ' ')
            .replace(/&nbsp;|&#160;/gi, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        return textValue ? rawHtml : '';
    }

    function sanitizeHtml(html) {
        const parser = new window.DOMParser();
        const parsed = parser.parseFromString('<div>' + String(html || '') + '</div>', 'text/html');
        const sourceRoot = parsed.body && parsed.body.firstElementChild ? parsed.body.firstElementChild : parsed.body;
        const cleanDocument = document.implementation.createHTMLDocument('');
        const wrapper = cleanDocument.createElement('div');

        Array.from(sourceRoot.childNodes || []).forEach(function (childNode) {
            appendSanitizedChild(wrapper, buildSanitizedNode(childNode, cleanDocument));
        });

        const sanitized = wrapper.innerHTML
            .replace(/<p>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/gi, '')
            .trim();

        return normalizeHtmlValue(sanitized);
    }

    function buildTextInsertionHtml(text) {
        const normalizedText = String(text || '')
            .replace(/\r\n?/g, '\n')
            .trim();

        if (!normalizedText) {
            return '';
        }

        return escapeHtml(normalizedText).replace(/\n/g, '<br>');
    }

    function normalizePlainText(text) {
        return String(text || '')
            .replace(/\r\n?/g, '\n')
            .replace(/\u00a0/g, ' ')
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .replace(/[ \t]{2,}/g, ' ')
            .trim();
    }

    function htmlToPlainText(html) {
        const temp = document.createElement('div');
        temp.innerHTML = sanitizeHtml(html);
        return normalizePlainText(temp.innerText || temp.textContent || '');
    }

    function mount(container, options) {
        if (!container) {
            return null;
        }

        if (typeof container.__omoSimpleHtmlFieldDestroy === 'function') {
            container.__omoSimpleHtmlFieldDestroy();
        }

        const state = Object.assign({
            value: '',
            placeholder: 'Saisissez du contenu HTML simple.',
            disabled: false,
            height: 180,
            minHeight: null,
            customButtons: [],
            indicatorValueUi: null,
            onChange: null,
            onIndicatorValueAdd: null,
            onReady: null,
            onDoubleClick: null
        }, options || {});

        const safeInitialValue = sanitizeHtml(state.value);
        const editorId = 'omo-html-field-' + Math.random().toString(36).slice(2);
        const textareaId = editorId + '-textarea';
        let destroyed = false;
        let initialized = false;
        let $editor = null;
        let nativeSavedRange = null;
        const toolbarButtons = {};
        const toolbarButtonState = {};
        const customToolbarButtons = Array.isArray(state.customButtons)
            ? state.customButtons.filter(function (buttonConfig) {
                return buttonConfig && String(buttonConfig.name || '').trim() !== '';
            }).map(function (buttonConfig) {
                const normalizedConfig = Object.assign({}, buttonConfig);
                normalizedConfig.name = String(buttonConfig.name || '').trim();
                normalizedConfig.group = String(buttonConfig.group || 'custom').trim() || 'custom';
                normalizedConfig.label = String(buttonConfig.label || buttonConfig.name || '').trim() || normalizedConfig.name;
                normalizedConfig.title = String(buttonConfig.title || buttonConfig.label || buttonConfig.name || '').trim() || normalizedConfig.label;
                normalizedConfig.className = String(buttonConfig.className || '').trim();
                return normalizedConfig;
            })
            : [];

        container.setAttribute('data-omo-html-field', '1');
        container.innerHTML = ''
            + '<div class="omo-simple-html-field">'
            + '  <textarea id="' + escapeHtml(textareaId) + '"></textarea>'
            + '</div>';

        const textarea = container.querySelector('textarea');
        if (textarea) {
            textarea.value = safeInitialValue;
        }

        function setRawValue(nextValue) {
            state.value = sanitizeHtml(nextValue);
            if (textarea) {
                textarea.value = state.value;
            }
        }

        function getValue() {
            if (initialized && $editor) {
                return sanitizeHtml($editor.summernote('code'));
            }

            return sanitizeHtml(state.value);
        }

        function getEditableElement() {
            return container.querySelector('.note-editable');
        }

        function resizeEditableToContent() {
            const editable = getEditableElement();
            if (!editable) {
                return;
            }

            const minimumHeight = Math.max(80, Number(state.minHeight || state.height || 180));
            editable.style.minHeight = minimumHeight + 'px';
            editable.style.height = 'auto';
            editable.style.overflowY = 'hidden';
            editable.style.height = Math.max(minimumHeight, editable.scrollHeight) + 'px';
        }

        function scheduleResizeEditableToContent() {
            const schedule = typeof window.requestAnimationFrame === 'function'
                ? window.requestAnimationFrame.bind(window)
                : function (callback) { window.setTimeout(callback, 16); };

            schedule(function () {
                if (!destroyed) {
                    resizeEditableToContent();
                }
            });
        }

        function cloneRange(range) {
            if (!range || typeof range.cloneRange !== 'function') {
                return null;
            }

            try {
                return range.cloneRange();
            } catch (error) {
                return null;
            }
        }

        function captureCurrentSelectionRange() {
            const selection = window.getSelection ? window.getSelection() : null;
            const editable = getEditableElement();

            if (!selection || selection.rangeCount === 0 || !editable) {
                return null;
            }

            const range = selection.getRangeAt(0);
            const commonNode = range.commonAncestorContainer;
            if (commonNode !== editable && !editable.contains(commonNode)) {
                return null;
            }

            return cloneRange(range);
        }

        function restoreNativeSavedRange() {
            const editable = getEditableElement();
            const selection = window.getSelection ? window.getSelection() : null;
            if (!editable || !selection || !nativeSavedRange) {
                return false;
            }

            try {
                const commonNode = nativeSavedRange.commonAncestorContainer;
                if (commonNode !== editable && !editable.contains(commonNode)) {
                    return false;
                }

                selection.removeAllRanges();
                selection.addRange(nativeSavedRange);
                return true;
            } catch (error) {
                return false;
            }
        }

        function restoreRange() {
            if (initialized && $editor) {
                try {
                    $editor.summernote('restoreRange');
                } catch (error) {
                    // ignore selection restore issues
                }
            }

            restoreNativeSavedRange();
        }

        function getSelectionRange() {
            restoreRange();
            const selection = window.getSelection ? window.getSelection() : null;
            const editable = getEditableElement();

            if (!selection || selection.rangeCount === 0) {
                return null;
            }

            const range = selection.getRangeAt(0);
            if (!editable) {
                return range;
            }

            const commonNode = range.commonAncestorContainer;
            if (commonNode === editable || editable.contains(commonNode)) {
                return range;
            }

            return null;
        }

        function setValue(nextValue) {
            setRawValue(nextValue);

            if (initialized && $editor) {
                $editor.summernote('code', state.value);
                refreshIndicatorValueControls(getEditableElement(), state.indicatorValueUi);
                saveRange();
                scheduleResizeEditableToContent();
            }

            if (typeof state.onChange === 'function') {
                try {
                    state.onChange(getValue(), container.__omoSimpleHtmlField || null);
                } catch (error) {
                }
            }
        }

        function saveRange() {
            if (initialized && $editor) {
                try {
                    $editor.summernote('saveRange');
                } catch (error) {
                    // ignore selection save issues
                }
            }

            nativeSavedRange = captureCurrentSelectionRange();
        }

        function emitChange() {
            if (typeof state.onChange === 'function') {
                try {
                    state.onChange(getValue(), container.__omoSimpleHtmlField || null);
                } catch (error) {
                }
            }
        }

        function emitDoubleClick(targetNode, event) {
            if (typeof state.onDoubleClick === 'function') {
                try {
                    state.onDoubleClick({
                        target: targetNode || null,
                        event: event || null,
                        api: container.__omoSimpleHtmlField || null
                    });
                } catch (error) {
                }
            }
        }

        function applyToolbarButtonState(name, nextState) {
            const buttonName = String(name || '').trim();
            if (!buttonName) {
                return;
            }

            toolbarButtonState[buttonName] = Object.assign({}, toolbarButtonState[buttonName] || {}, nextState || {});
            const $button = toolbarButtons[buttonName];
            if (!$button || !$button.length) {
                return;
            }

            const resolvedState = toolbarButtonState[buttonName];
            const label = resolvedState && resolvedState.label !== undefined
                ? String(resolvedState.label || '')
                : null;
            const title = resolvedState && resolvedState.title !== undefined
                ? String(resolvedState.title || '')
                : null;
            const isDisabled = !!(resolvedState && resolvedState.disabled);
            const isHidden = !!(resolvedState && resolvedState.hidden);
            const isActive = !!(resolvedState && resolvedState.active);

            if (label !== null) {
                $button.html(escapeHtml(label));
            }

            if (title !== null) {
                $button.attr('title', title);
                $button.attr('aria-label', title);
            }

            $button.prop('disabled', isDisabled);
            $button.toggleClass('disabled', isDisabled);
            $button.attr('aria-disabled', isDisabled ? 'true' : 'false');
            $button.toggleClass('is-active', isActive);
            $button.attr('aria-hidden', isHidden ? 'true' : 'false');
            $button.css('display', isHidden ? 'none' : '');

            const $group = $button.closest('.note-btn-group');
            if ($group && $group.length) {
                const hasVisibleButtons = $group.find('button').toArray().some(function (groupButton) {
                    return window.getComputedStyle(groupButton).display !== 'none';
                });
                $group.css('display', hasVisibleButtons ? '' : 'none');
            }
        }

        function insertHtmlAtCursor(nextHtml) {
            const safeHtml = sanitizeHtml(nextHtml);
            if (!safeHtml) {
                return '';
            }

            if (initialized && $editor) {
                try {
                    $editor.summernote('focus');
                    restoreRange();

                    const selectionRange = getSelectionRange();
                    if (selectionRange) {
                        const temp = document.createElement('div');
                        const selection = window.getSelection ? window.getSelection() : null;
                        temp.innerHTML = safeHtml;

                        const nodes = Array.from(temp.childNodes || []);
                        let lastInsertedNode = null;
                        const fragment = document.createDocumentFragment();

                        nodes.forEach(function (node) {
                            lastInsertedNode = node;
                            fragment.appendChild(node);
                        });

                        selectionRange.deleteContents();
                        selectionRange.insertNode(fragment);

                        if (selection && lastInsertedNode) {
                            const collapsedRange = document.createRange();
                            collapsedRange.setStartAfter(lastInsertedNode);
                            collapsedRange.collapse(true);
                            selection.removeAllRanges();
                            selection.addRange(collapsedRange);
                        }
                    } else {
                        $editor.summernote('pasteHTML', safeHtml);
                    }

                    saveRange();
                    setRawValue($editor.summernote('code'));
                    refreshIndicatorValueControls(getEditableElement(), state.indicatorValueUi);
                } catch (error) {
                    setRawValue((state.value || '') + safeHtml);
                    $editor.summernote('code', state.value);
                    refreshIndicatorValueControls(getEditableElement(), state.indicatorValueUi);
                    saveRange();
                }

                return safeHtml;
            }

            setRawValue((state.value || '') + safeHtml);
            return safeHtml;
        }

        function insertTextAtCursor(text) {
            return insertHtmlAtCursor(buildTextInsertionHtml(text));
        }

        function replaceNodeWithHtml(targetNode, nextHtml, shouldEmitChange) {
            const safeHtml = sanitizeHtml(nextHtml);
            const editable = getEditableElement();
            const emitChangeAfterReplace = shouldEmitChange !== false;

            if (!safeHtml || !editable || !targetNode || !editable.contains(targetNode)) {
                return insertHtmlAtCursor(safeHtml);
            }

            const temp = document.createElement('div');
            temp.innerHTML = safeHtml;

            const nodes = Array.from(temp.childNodes || []);
            if (!nodes.length) {
                return '';
            }

            const fragment = document.createDocumentFragment();
            let lastInsertedNode = null;
            nodes.forEach(function (node) {
                lastInsertedNode = node;
                fragment.appendChild(node);
            });

            targetNode.replaceWith(fragment);

            if (window.getSelection && lastInsertedNode) {
                const selection = window.getSelection();
                const range = document.createRange();
                range.setStartAfter(lastInsertedNode);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            saveRange();

            if (initialized && $editor) {
                setRawValue($editor.summernote('code'));
            } else {
                setRawValue(editable.innerHTML);
            }
            refreshIndicatorValueControls(editable, state.indicatorValueUi);

            if (emitChangeAfterReplace) {
                emitChange();
            }
            return safeHtml;
        }

        function buildCursorMarkerNode() {
            const markerNode = document.createElement('span');
            cursorMarkerCounter += 1;
            markerNode.setAttribute('data-omo-cursor-marker', 'omo-cursor-marker-' + String(cursorMarkerCounter));
            markerNode.setAttribute('contenteditable', 'false');
            markerNode.className = 'omo-html-cursor-marker';
            return markerNode;
        }

        function createTemporaryCursorMarker() {
            const editable = getEditableElement();
            if (!editable) {
                return null;
            }

            restoreRange();
            let range = getSelectionRange();
            if (!range) {
                range = document.createRange();
                range.selectNodeContents(editable);
                range.collapse(false);
            } else {
                range = cloneRange(range) || range;
            }

            const markerNode = buildCursorMarkerNode();

            try {
                range.deleteContents();
                range.insertNode(markerNode);
            } catch (error) {
                editable.appendChild(markerNode);
            }

            if (window.getSelection) {
                const selection = window.getSelection();
                const caretRange = document.createRange();
                caretRange.setStartAfter(markerNode);
                caretRange.collapse(true);
                selection.removeAllRanges();
                selection.addRange(caretRange);
            }

            saveRange();
            return markerNode;
        }

        function replaceMarkerWithHtml(markerNode, nextHtml) {
            const editable = getEditableElement();
            const safeHtml = sanitizeHtml(nextHtml);

            if (!editable || !markerNode || !editable.contains(markerNode)) {
                return insertHtmlAtCursor(safeHtml);
            }

            const temp = document.createElement('div');
            temp.innerHTML = safeHtml;
            const nodes = Array.from(temp.childNodes || []);
            const fragment = document.createDocumentFragment();
            let lastInsertedNode = null;

            nodes.forEach(function (node) {
                lastInsertedNode = node;
                fragment.appendChild(node);
            });

            markerNode.replaceWith(fragment);

            if (window.getSelection) {
                const selection = window.getSelection();
                const range = document.createRange();
                if (lastInsertedNode) {
                    range.setStartAfter(lastInsertedNode);
                } else {
                    range.selectNodeContents(editable);
                    range.collapse(false);
                }
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            saveRange();

            if (initialized && $editor) {
                setRawValue($editor.summernote('code'));
            } else {
                setRawValue(editable.innerHTML);
            }

            emitChange();
            return safeHtml;
        }

        function removeTemporaryMarker(markerNode) {
            const editable = getEditableElement();
            if (!editable || !markerNode || !editable.contains(markerNode)) {
                return false;
            }

            if (window.getSelection) {
                const selection = window.getSelection();
                const range = document.createRange();
                range.setStartBefore(markerNode);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            markerNode.remove();
            saveRange();
            return true;
        }

        function removeNode(targetNode) {
            const editable = getEditableElement();
            if (!editable || !targetNode || !editable.contains(targetNode)) {
                return false;
            }

            const nextSibling = targetNode.nextSibling;
            const previousSibling = targetNode.previousSibling;
            targetNode.remove();

            if (window.getSelection) {
                const selection = window.getSelection();
                const range = document.createRange();

                if (nextSibling && editable.contains(nextSibling)) {
                    range.setStartBefore(nextSibling);
                } else if (previousSibling && editable.contains(previousSibling)) {
                    range.setStartAfter(previousSibling);
                } else {
                    range.selectNodeContents(editable);
                    range.collapse(false);
                }

                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            saveRange();

            if (initialized && $editor) {
                setRawValue($editor.summernote('code'));
            } else {
                setRawValue(editable.innerHTML);
            }

            emitChange();
            return true;
        }

        function getSelectedText() {
            const range = getSelectionRange();
            if (!range) {
                return '';
            }

            const fragment = range.cloneContents();
            const temp = document.createElement('div');
            temp.appendChild(fragment);

            return normalizePlainText(temp.innerText || temp.textContent || '');
        }

        function hasSelection() {
            const range = getSelectionRange();
            return !!(range && !range.collapsed && normalizePlainText(range.toString()) !== '');
        }

        function getPlainText() {
            return htmlToPlainText(getValue());
        }

        function replaceSelectionWithText(text) {
            return insertTextAtCursor(text);
        }

        function destroy() {
            destroyed = true;

            if (initialized && $editor) {
                try {
                    setRawValue($editor.summernote('code'));
                    $editor.summernote('destroy');
                } catch (error) {
                    // ignore cleanup issues
                }
            }

            initialized = false;
            $editor = null;
            delete container.__omoSimpleHtmlField;
            delete container.__omoSimpleHtmlFieldDestroy;
        }

        ensureDependencies()
            .then(function () {
                if (destroyed || !textarea) {
                    return;
                }

                const toolbar = [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']]
                ];
                const toolbarGroups = {};
                const buttonsConfig = {};

                customToolbarButtons.forEach(function (buttonConfig) {
                    if (!toolbarGroups[buttonConfig.group]) {
                        toolbarGroups[buttonConfig.group] = [];
                    }

                    toolbarGroups[buttonConfig.group].push(buttonConfig.name);
                    buttonsConfig[buttonConfig.name] = function () {
                        const ui = window.jQuery && window.jQuery.summernote
                            ? window.jQuery.summernote.ui
                            : null;

                        if (!ui) {
                            return window.jQuery('<button type="button"></button>').text(buttonConfig.label);
                        }

                        const $button = ui.button({
                            contents: escapeHtml(buttonConfig.label),
                            tooltip: buttonConfig.title,
                            className: buttonConfig.className,
                            click: function (event) {
                                if (typeof buttonConfig.onClick === 'function') {
                                    buttonConfig.onClick({
                                        event: event,
                                        name: buttonConfig.name,
                                        api: container.__omoSimpleHtmlField || null
                                    });
                                }
                            }
                        }).render();

                        toolbarButtons[buttonConfig.name] = $button;
                        if ($button && $button.length) {
                            const buttonNode = $button.get(0);
                            if (buttonNode) {
                                buttonNode.addEventListener('mousedown', saveRange);
                                buttonNode.addEventListener('pointerdown', saveRange);
                            }
                        }
                        $button.attr('data-omo-toolbar-button-name', buttonConfig.name);
                        applyToolbarButtonState(buttonConfig.name, {
                            label: buttonConfig.label,
                            title: buttonConfig.title,
                            hidden: !!buttonConfig.hidden,
                            disabled: !!buttonConfig.disabled,
                            active: !!buttonConfig.active
                        });

                        return $button;
                    };
                });

                Object.keys(toolbarGroups).forEach(function (groupName) {
                    toolbar.push([groupName, toolbarGroups[groupName]]);
                });

                $editor = window.jQuery(textarea);
                $editor.summernote({
                    lang: 'fr-FR',
                    placeholder: state.placeholder,
                    minHeight: Math.max(80, Number(state.minHeight || state.height || 180)),
                    maxHeight: null,
                    dialogsInBody: true,
                    disableDragAndDrop: true,
                    styleTags: [
                        'p',
                        { title: 'Titre 1', tag: 'h1', className: '', value: 'h1' },
                        { title: 'Titre 2', tag: 'h2', className: '', value: 'h2' },
                        { title: 'Titre 3', tag: 'h3', className: '', value: 'h3' },
                        { title: 'Citation', tag: 'blockquote', className: '', value: 'blockquote' }
                    ],
                    toolbar: toolbar,
                    buttons: buttonsConfig,
                    callbacks: {
                        onChange: function (contents) {
                            setRawValue(contents);
                            saveRange();
                            scheduleResizeEditableToContent();
                            emitChange();
                        },
                        onFocus: function () {
                            saveRange();
                        },
                        onBlur: function () {
                            saveRange();
                        },
                        onKeyup: function () {
                            saveRange();
                            scheduleResizeEditableToContent();
                        },
                        onMouseup: function () {
                            saveRange();
                        }
                    }
                });

                $editor.summernote('code', state.value);
                refreshIndicatorValueControls(getEditableElement(), state.indicatorValueUi);
                if (state.disabled) {
                    $editor.summernote('disable');
                }

                initialized = true;
                saveRange();
                scheduleResizeEditableToContent();

                const editable = getEditableElement();
                if (editable) {
                    editable.addEventListener('input', scheduleResizeEditableToContent);
                    editable.addEventListener('click', function (event) {
                        const context = emitIndicatorValueAdd(event.target || null, event);
                        if (context && typeof state.onIndicatorValueAdd === 'function') {
                            context.api = container.__omoSimpleHtmlField || null;
                            try {
                                state.onIndicatorValueAdd(context);
                            } catch (error) {
                            }
                        }
                    });
                    editable.addEventListener('dblclick', function (event) {
                        if (event.target && event.target.closest && event.target.closest('.omo-indicator-embed__value-entry')) {
                            event.preventDefault();
                            event.stopPropagation();
                            return;
                        }
                        saveRange();
                        emitDoubleClick(event.target || null, event);
                    });
                }
                if (typeof state.onReady === 'function') {
                    try {
                        state.onReady(container.__omoSimpleHtmlField || null);
                    } catch (error) {
                    }
                }
            })
            .catch(function (error) {
                if (destroyed) {
                    return;
                }

                container.innerHTML = '<div class="omo-simple-html-field__meta">Impossible de charger l editeur HTML.</div>';
                if (window.console && typeof window.console.error === 'function') {
                    window.console.error(error);
                }
            });

        container.__omoSimpleHtmlField = {
            version: OMO_SIMPLE_HTML_FIELD_VERSION,
            getValue: getValue,
            setValue: setValue,
            focus: function () {
                if (initialized && $editor) {
                    $editor.summernote('focus');
                }
            },
            saveRange: saveRange,
            restoreRange: restoreRange,
            insertHtmlAtCursor: insertHtmlAtCursor,
            createTemporaryCursorMarker: createTemporaryCursorMarker,
            replaceMarkerWithHtml: replaceMarkerWithHtml,
            removeTemporaryMarker: removeTemporaryMarker,
            insertTextAtCursor: insertTextAtCursor,
            replaceNodeWithHtml: replaceNodeWithHtml,
            removeNode: removeNode,
            replaceSelectionWithText: replaceSelectionWithText,
            getSelectedText: getSelectedText,
            hasSelection: hasSelection,
            getPlainText: getPlainText,
            getEditableElement: getEditableElement,
            setToolbarButtonState: applyToolbarButtonState,
            destroy: destroy
        };
        container.__omoSimpleHtmlFieldDestroy = destroy;

        return container.__omoSimpleHtmlField;
    }

    function renderPreviewHtml(value, className) {
        ensureLocalStyles();

        const safeValue = sanitizeHtml(value);
        if (!safeValue) {
            return '';
        }

        const classes = ['omo-simple-html-render'];
        if (String(className || '').trim() !== '') {
            classes.push(String(className).trim());
        }

        return '<div class="' + escapeHtml(classes.join(' ')) + '">' + safeValue + '</div>';
    }

    ensureLocalStyles();

    window.omoSimpleHtmlField = {
        version: OMO_SIMPLE_HTML_FIELD_VERSION,
        mount: mount,
        sanitizeHtml: sanitizeHtml,
        renderPreviewHtml: renderPreviewHtml
    };
})(window, document);
