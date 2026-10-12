window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/omo/api/parameters/tips/index.js'] = function (config) {
    const root = document.querySelector('[data-tips-admin]:not([data-bound])');
    if (!root) return;
    root.dataset.bound = '1';
    const form = root.querySelector('form');
    if (window.croppedImages) delete window.croppedImages.image;
    let busy = false;
    function reload(id) {
        const path = '/omo/api/parameters/tips/index.php?oid=' + root.dataset.oid + '&id=' + id;
        const url = typeof window.omoResolveAppUrl === 'function' ? window.omoResolveAppUrl(path) : path;
        $(root).parent().load(url);
    }
    root.addEventListener('click', function (event) {
        const edit = event.target.closest('[data-tip-edit]');
        if (edit && !busy) reload(Number(edit.dataset.tipEdit) || 0);
        if (event.target.closest('[data-tip-remove-image]') && !busy) {
            form.elements.image.value = '';
            if (window.croppedImages) delete window.croppedImages.image;
            const preview = form.querySelector('#imgContainer_image');
            if (preview) preview.style.display = 'none';
        }
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (busy) return;
        busy = true;
        const save = root.querySelector('[data-tip-save]');
        const data = new FormData(form);
        if (form.elements.image.value === 'newimage' && window.croppedImages && window.croppedImages.image) {
            data.set('image', 'newimage');
            data.set('image_file', window.croppedImages.image, 'tip-image.png');
        }
        save.disabled = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: data, credentials: 'same-origin'});
            const result = await response.json();
            if (!response.ok || !result.status) throw new Error(result.message || config.error);
            window.commonNotify(result.message, 'success');
            reload(result.id);
        } catch (error) {
            window.commonNotify(error.message || config.error, 'error');
        } finally {
            busy = false;
            save.disabled = false;
        }
    });
};
