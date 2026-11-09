window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/common/choice/evaluation-preview.js'] = function (pageConfig) {
    'use strict';
    window.omoDecisionCompleteEvaluationPreview = function (form) {
        if (!document.body.hasAttribute('data-omo-decision-evaluation-preview') || !(form instanceof HTMLFormElement)) return;
        if (typeof window.commonNotify === 'function') window.commonNotify(pageConfig.completeMessage, 'success');
    };
};
