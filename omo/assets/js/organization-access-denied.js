window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/omo/assets/js/organization-access-denied.js'] = function (config) {
    function initialize() {
        var button = document.getElementById('omoAccessRequestButton');
        if (button && !button.disabled) {
            button.addEventListener('click', function () {
                var url = button.getAttribute('data-modal-url') || '/omo/';
                if (typeof window.commonTopbarOpenModal !== 'function') {
                    window.location.href = url;
                    return;
                }
                window.commonTopbarOpenModal(button.getAttribute('data-modal-title') || '', url, 'fetch');
            });
        }
        window.addEventListener('omo-access-request-submitted', function () { window.location.reload(); });

        // The server still authorizes the particular request. This page never loads the organization workspace.
        if (!config.canAnswerFaq) return;
        if (typeof window.omoNotify !== 'function') window.omoNotify = window.commonNotify;
        var activeUrl = '';
        function openRequest() {
            var match = window.location.hash.match(/(?:^#|\|)faq-([1-9][0-9]*)$/);
            if (!match || typeof window.commonTopbarOpenModal !== 'function') return;
            var url = '/popup/faq.php?id=' + encodeURIComponent(match[1]) + '&oid=' + encodeURIComponent(config.organizationId);
            var modal = document.getElementById('commonTopbarModal');
            if (activeUrl === url && modal && !modal.hidden) return;
            activeUrl = url;
            window.commonTopbarOpenModal(config.faqTitle, url, 'fetch');
        }
        window.addEventListener('hashchange', openRequest);
        window.addEventListener('common-topbar-modal-close', function (event) {
            if (!activeUrl || !event.detail || event.detail.url !== activeUrl) return;
            activeUrl = '';
            var hash = window.location.hash.replace(/\|?faq-[1-9][0-9]*$/, '').replace(/^#$/, '');
            history.replaceState(null, '', window.location.pathname + window.location.search + hash);
        });
        openRequest();
    }
    // The shared topbar scripts are deferred, including on the access-denied page.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once: true});
    else initialize();
};
