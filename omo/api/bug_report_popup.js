window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/bug_report_popup.js"] = function (pageConfig, pageScript) {
(function () {
    const root = document.getElementById('omoBugReportPopup');
    if (!root) {
        return;
    }

    const texts = pageConfig.texts;

    const patreonConnectButton = document.getElementById('omoBugReportPatreonConnect');
    const patreonConnectOrigin = pageConfig.patreonConnectOrigin;
    const bugReportPopupUrl = '/omo/api/bug_report_popup.php';
    let patreonConnectWindow = null;
    let patreonConnectCloseWatcher = null;

    function clearPatreonConnectCloseWatcher() {
        if (patreonConnectCloseWatcher) {
            window.clearInterval(patreonConnectCloseWatcher);
            patreonConnectCloseWatcher = null;
        }
    }

    function refreshBugReportPopup() {
        if (typeof window.commonTopbarRefreshModalContent === 'function') {
            window.commonTopbarRefreshModalContent(
                bugReportPopupUrl + '?refresh=' + encodeURIComponent(String(Date.now()))
            );
        }
    }

    function watchPatreonConnectWindow() {
        clearPatreonConnectCloseWatcher();

        if (!patreonConnectWindow) {
            return;
        }

        patreonConnectCloseWatcher = window.setInterval(function () {
            if (!patreonConnectWindow || patreonConnectWindow.closed !== true) {
                return;
            }

            patreonConnectWindow = null;
            clearPatreonConnectCloseWatcher();
            refreshBugReportPopup();
        }, 500);
    }

    function openPatreonConnect() {
        const width = 720;
        const height = 860;
        const left = Math.max(0, (window.screen.width - width) / 2);
        const top = Math.max(0, (window.screen.height - height) / 2);

        patreonConnectWindow = window.open(
            '/common/patreon_connect.php',
            'patreon_connect',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
        );

        watchPatreonConnectWindow();
    }

    function handlePatreonMessage(event) {
        if (patreonConnectOrigin === '' || event.origin !== patreonConnectOrigin) {
            return;
        }

        if (!event.data || event.data.type !== 'patreon-connected') {
            return;
        }

        patreonConnectWindow = null;
        clearPatreonConnectCloseWatcher();
        refreshBugReportPopup();
    }

    if (patreonConnectButton) {
        patreonConnectButton.addEventListener('click', openPatreonConnect);
    }
    window.addEventListener('message', handlePatreonMessage);

    const closeButton = document.getElementById('omoBugReportClose');
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            if (typeof window.commonTopbarCloseModal === 'function') {
                window.commonTopbarCloseModal();
            }
        });
    }

    const form = document.getElementById('omoBugReportForm');
    const feedback = document.getElementById('omoBugReportFeedback');
    const submitButton = document.getElementById('omoBugReportSubmit');
    if (!form || !feedback || !submitButton) {
        return;
    }

    const titleInput = document.getElementById('omoBugReportTitle');
    const descriptionInput = document.getElementById('omoBugReportDescription');
    const attachmentsInput = document.getElementById('omoBugReportAttachments');
    const fileList = document.getElementById('omoBugReportFileList');
    if (titleInput && !titleInput.value) {
        titleInput.focus();
    } else if (descriptionInput) {
        descriptionInput.focus();
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setFeedback(message, type, links) {
        feedback.classList.remove('is-error', 'is-success');

        if (!message) {
            feedback.textContent = '';
            return;
        }

        if (type === 'error') {
            feedback.classList.add('is-error');
            feedback.textContent = message;
            return;
        }

        if (type === 'success') {
            feedback.classList.add('is-success');
        }

        let html = escapeHtml(message);
        if (links && links.issue_url) {
            html += ' <a href="' + escapeHtml(links.issue_url) + '" target="_blank" rel="noopener">' + escapeHtml(texts.viewIssue) + '</a>';
        }
        feedback.innerHTML = html;
    }

    function renderSelectedFiles() {
        if (!attachmentsInput || !fileList) {
            return;
        }

        const files = Array.from(attachmentsInput.files || []);
        if (!files.length) {
            fileList.hidden = true;
            fileList.innerHTML = '';
            return;
        }

        fileList.hidden = false;
        fileList.innerHTML = files.map(function (file) {
            const sizeMb = file && file.size ? (file.size / (1024 * 1024)).toFixed(2) : '0.00';
            return '<div>' + escapeHtml(file.name || texts.file) + ' (' + escapeHtml(sizeMb) + ' MB)</div>';
        }).join('');
    }

    function collectContext() {
        const nav = window.navigator || {};
        const pageUrl = window.location && window.location.href ? window.location.href : '';
        const themeNode = document.documentElement;
        const screenInfo = window.screen && window.screen.width && window.screen.height
            ? String(window.screen.width) + 'x' + String(window.screen.height)
            : '';
        const viewport = typeof window.innerWidth === 'number' && typeof window.innerHeight === 'number'
            ? String(window.innerWidth) + 'x' + String(window.innerHeight)
            : '';
        const topbar = document.querySelector('.common-topbar');

        return {
            page_url: pageUrl,
            page_title: document.title || '',
            user_agent: nav.userAgent || '',
            language: nav.language || '',
            languages: Array.isArray(nav.languages) ? nav.languages.join(', ') : '',
            timezone: window.Intl && typeof window.Intl.DateTimeFormat === 'function'
                ? (Intl.DateTimeFormat().resolvedOptions().timeZone || '')
                : '',
            viewport: viewport,
            screen_size: screenInfo,
            pixel_ratio: window.devicePixelRatio ? String(window.devicePixelRatio) : '',
            referrer: document.referrer || '',
            theme: themeNode ? (themeNode.getAttribute('data-theme') || '') : '',
            app_key: topbar ? (topbar.getAttribute('data-app-key') || '') : '',
            platform: nav.platform || '',
            client_timestamp: (new Date()).toISOString()
        };
    }

    if (attachmentsInput) {
        attachmentsInput.addEventListener('change', renderSelectedFiles);
        renderSelectedFiles();
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const submitUrl = root.getAttribute('data-submit-url') || '';
        if (!submitUrl) {
            setFeedback(texts.endpointMissing, 'error');
            return;
        }

        const formData = new FormData(form);
        const context = collectContext();
        Object.keys(context).forEach(function (key) {
            if (context[key]) {
                formData.append(key, context[key]);
            }
        });

        const attachedFiles = attachmentsInput ? Array.from(attachmentsInput.files || []) : [];
        if (attachedFiles.length) {
            if (!window.confirm(texts.attachmentWarning)) {
                return;
            }
        }

        submitButton.disabled = true;
        if (closeButton) {
            closeButton.disabled = true;
        }
        setFeedback(texts.sending, 'info');

        fetch(submitUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                }).then(function (data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                });
            })
            .then(function (result) {
                submitButton.disabled = false;
                if (closeButton) {
                    closeButton.disabled = false;
                }

                if (!result.ok || !result.data || !result.data.status) {
                    setFeedback(
                        result.data && result.data.message ? result.data.message : texts.sendFailed,
                        'error'
                    );
                    return;
                }

                setFeedback(result.data.message || texts.sent, 'success', result.data);
                form.reset();
                renderSelectedFiles();
                if (titleInput) {
                    titleInput.focus();
                }
            })
            .catch(function () {
                submitButton.disabled = false;
                if (closeButton) {
                    closeButton.disabled = false;
                }
                setFeedback(texts.sendFailed, 'error');
            });
    });

    window.__omoPopupCleanup = function () {
        window.removeEventListener('message', handlePatreonMessage);
        clearPatreonConnectCloseWatcher();
        patreonConnectWindow = null;
        if (patreonConnectButton) {
            patreonConnectButton.removeEventListener('click', openPatreonConnect);
        }
    };
})();
};
