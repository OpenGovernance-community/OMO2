<style>
/* Overlay */
#overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.4);
    display: none;
    z-index: 1000;
}

/* Drawer */
#drawer {
    position: fixed;
    top: 0;
    right: -1000px;
    width: 1000px;
    max-width: 100dvw;
    height: 100dvh;
    background: var(--bg-card);
    box-shadow: -3px 0 10px rgba(0,0,0,0.2);
    transition: right 0.3s ease;
    z-index: 1001;

    display: flex;
    flex-direction: column;
}

#drawer.open {
    right: 0;
}

#drawer-content {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

#drawer-footer {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
}
#quiz-zone {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: #fafafa;
}

#drawer.drawer-simple-mode #quiz-zone,
#drawer.drawer-simple-mode #drawer-footer {
    display: none;
}
</style>
<div id="overlay" onclick="closeDrawer()"></div>

<div id="drawer">
    <div id="drawer-content"></div>
    <div id="quiz-zone"></div>
    <div id="drawer-footer">
        <button onclick="closeDrawer()">Close</button>
        <button id="doneBtn">Done</button>
    </div>
</div>
<script>
let lmsDrawerJqueryPromise = null;
const lmsDrawerExternalScriptPromises = {};

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

function lmsLoadExternalScriptOnce(src) {
    if (!src) {
        return Promise.resolve();
    }

    if (lmsDrawerExternalScriptPromises[src]) {
        return lmsDrawerExternalScriptPromises[src];
    }

    const existingScript = document.querySelector(`script[src="${src}"]`);
    if (existingScript && existingScript.getAttribute('data-lms-loaded') === '1') {
        lmsDrawerExternalScriptPromises[src] = Promise.resolve();
        return lmsDrawerExternalScriptPromises[src];
    }

    lmsDrawerExternalScriptPromises[src] = new Promise((resolve, reject) => {
        if (existingScript) {
            if (existingScript.getAttribute('data-lms-loaded') === '1') {
                resolve();
                return;
            }

            existingScript.setAttribute('data-lms-loaded', '1');
            existingScript.addEventListener('load', () => {
                existingScript.setAttribute('data-lms-loaded', '1');
                resolve();
            }, { once: true });
            existingScript.addEventListener('error', () => reject(new Error('drawer_script_load_failed')), { once: true });
            window.setTimeout(resolve, 0);
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = false;
        script.onload = () => {
            script.setAttribute('data-lms-loaded', '1');
            resolve();
        };
        script.onerror = () => reject(new Error('drawer_script_load_failed'));
        document.head.appendChild(script);
    });

    return lmsDrawerExternalScriptPromises[src];
}

async function lmsExecuteDrawerScripts(container) {
    const scripts = Array.from(container.querySelectorAll('script'));

    if (scripts.length === 0) {
        return;
    }

    await lmsEnsureJquery();

    for (const sourceScript of scripts) {
        const src = sourceScript.getAttribute('src');
        if (src) {
            await lmsLoadExternalScriptOnce(src);
            sourceScript.remove();
            continue;
        }

        const script = document.createElement('script');
        [...sourceScript.attributes].forEach(attr => {
            script.setAttribute(attr.name, attr.value);
        });
        script.textContent = sourceScript.textContent;
        sourceScript.replaceWith(script);
    }
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

    return lmsExecuteDrawerScripts(container).catch(() => {
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
</script>
