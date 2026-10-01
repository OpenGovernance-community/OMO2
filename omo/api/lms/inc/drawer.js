let lmsDrawerJqueryPromise = null;

function lmsEnsureJquery() {
    if (window.jQuery) {
        return Promise.resolve(window.jQuery);
    }

    if (lmsDrawerJqueryPromise) {
        return lmsDrawerJqueryPromise;
    }

    lmsDrawerJqueryPromise = new Promise((resolve, reject) => {
        const existingScript = document.querySelector('script[data-lms-jquery-loader="1"]');
        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(window.jQuery), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('jquery_load_failed')), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
        script.setAttribute('data-lms-jquery-loader', '1');
        script.onload = () => resolve(window.jQuery);
        script.onerror = () => reject(new Error('jquery_load_failed'));
        document.head.appendChild(script);
    });

    return lmsDrawerJqueryPromise;
}

async function lmsExecuteDrawerScripts(container) {
    await lmsEnsureJquery();
    return window.commonExecuteFragmentScripts(container);
    }

function closeDrawer() {
    const drawer = document.getElementById('drawer');
    const overlay = document.getElementById('overlay');
    const content = document.getElementById('drawer-content');
    const quizZone = document.getElementById('quiz-zone');
    const footer = document.getElementById('drawer-footer');

    if (typeof window.lmsDestroyCurrentVideoPlayer === 'function') {
        window.lmsDestroyCurrentVideoPlayer({ unload: true }).catch(() => {});
    }

    drawer.classList.remove('open');
    overlay.style.display = 'none';
    document.documentElement.classList.remove('lms-drawer-open');
    document.body.classList.remove('lms-drawer-open');

    window.setTimeout(() => {
        if (drawer.classList.contains('open')) {
            return;
        }

        content.innerHTML = '';
        quizZone.innerHTML = '';
        drawer.classList.remove('drawer-simple-mode');
        if (footer) {
            footer.style.display = '';
        }
    }, 320);
}
function openDrawer(content, options) {
    const container = document.getElementById('drawer-content');
    const drawer = document.getElementById('drawer');
    const quizZone = document.getElementById('quiz-zone');
    const footer = document.getElementById('drawer-footer');
    const resolvedOptions = options && typeof options === 'object' ? options : {};
    const simpleMode = !!resolvedOptions.simpleMode;
    const targetScrollTop = typeof resolvedOptions.scrollTop === 'number' && Number.isFinite(resolvedOptions.scrollTop)
        ? Math.max(0, resolvedOptions.scrollTop)
        : 0;

    if (typeof window.lmsDestroyCurrentVideoPlayer === 'function') {
        window.lmsDestroyCurrentVideoPlayer({ unload: true }).catch(() => {});
    }

    drawer.classList.toggle('drawer-simple-mode', simpleMode);
    container.innerHTML = content;
    quizZone.innerHTML = '';
    if (footer) {
        footer.style.display = simpleMode ? 'none' : '';
    }

    document.getElementById('overlay').style.display = 'block';
    drawer.classList.add('open');
    document.documentElement.classList.add('lms-drawer-open');
    document.body.classList.add('lms-drawer-open');

    const applyScrollPosition = () => {
        container.scrollTop = targetScrollTop;
        window.requestAnimationFrame(() => {
            container.scrollTop = targetScrollTop;
        });
    };

    applyScrollPosition();

    return lmsExecuteDrawerScripts(container).then(() => {
        applyScrollPosition();
    }).catch(() => {
        window.alert('Impossible de charger les scripts du drawer.');
    });
}

function openDrawerFromUrl(url, options) {
    return fetch(url, { credentials: 'same-origin' })
        .then(response => {
            if (!response.ok) {
                throw new Error('drawer_load_failed');
            }

            return response.text();
        })
        .then(html => {
            return openDrawer(html, options).then(() => html);
        });
}
