window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/documents/merge.js"] = function (pageConfig, pageScript) {
(() => {
const data = pageConfig.data;
const ui = pageConfig.ui;
const form = document.querySelector('[data-omo-document-merge-form]');
if (!form) return;
const titleInput = form.querySelector('[data-omo-document-merge-title]');
const tagsHost = form.querySelector('[data-omo-document-merge-tags]');
const documentsHost = form.querySelector('[data-omo-document-merge-documents]');
const feedback = form.querySelector('[data-omo-document-merge-feedback]');
const submit = form.querySelector('[data-omo-document-merge-submit]');
const keepSourcesInput = form.querySelector('[data-omo-document-merge-keep-sources]');
let tags = Array.isArray(data.keywords) ? data.keywords.slice() : [];
let documents = Array.isArray(data.documents) ? data.documents.slice() : [];
let draggedId = 0;

function renderTags() {
    tagsHost.replaceChildren();
    tags.forEach(function (tag, index) {
        const chip = document.createElement('span');
        chip.className = 'omo-document-merge__tag';
        chip.textContent = String(tag);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'omo-document-merge__tag-remove';
        remove.textContent = '×';
        remove.setAttribute('aria-label', ui.removeTag + ' ' + String(tag));
        remove.addEventListener('click', function () { tags.splice(index, 1); renderTags(); });
        chip.appendChild(remove);
        tagsHost.appendChild(chip);
    });
}

function moveDocument(index, direction) {
    const targetIndex = index + direction;
    if (targetIndex < 0 || targetIndex >= documents.length) return;
    const item = documents.splice(index, 1)[0];
    documents.splice(targetIndex, 0, item);
    renderDocuments();
}

function renderDocuments() {
    documentsHost.replaceChildren();
    documents.forEach(function (documentItem, index) {
        const row = document.createElement('div');
        row.className = 'omo-document-merge__document';
        row.draggable = true;
        row.dataset.documentId = String(documentItem.id || '');
        const handle = document.createElement('span');
        handle.className = 'generic-drag-handle generic-drag-handle--minimal';
        handle.innerHTML = '<svg viewBox="0 0 16 24" aria-hidden="true"><circle cx="5" cy="5" r="1.4"></circle><circle cx="11" cy="5" r="1.4"></circle><circle cx="5" cy="12" r="1.4"></circle><circle cx="11" cy="12" r="1.4"></circle><circle cx="5" cy="19" r="1.4"></circle><circle cx="11" cy="19" r="1.4"></circle></svg>';
        const title = document.createElement('strong');
        title.textContent = String(documentItem.title || ui.documentFallback);
        const actions = document.createElement('div');
        actions.className = 'omo-document-merge__document-actions';
        [['up', -1, ui.moveUp], ['down', 1, ui.moveDown]].forEach(function (definition) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'generic-action-button generic-action-button--quiet-icon';
            button.innerHTML = definition[0] === 'up'
                ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 14 5-5 5 5"></path></svg>'
                : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>';
            button.title = definition[2];
            button.setAttribute('aria-label', definition[2]);
            button.disabled = definition[1] < 0 ? index === 0 : index === documents.length - 1;
            button.addEventListener('click', function () { moveDocument(index, definition[1]); });
            actions.appendChild(button);
        });
        row.append(handle, title, actions);
        row.addEventListener('dragstart', function () { draggedId = Number(documentItem.id || 0); row.classList.add('is-dragging'); });
        row.addEventListener('dragend', function () { draggedId = 0; row.classList.remove('is-dragging'); });
        row.addEventListener('dragover', function (event) { event.preventDefault(); });
        row.addEventListener('drop', function (event) {
            event.preventDefault();
            const from = documents.findIndex(function (item) { return Number(item.id) === draggedId; });
            const to = documents.findIndex(function (item) { return Number(item.id) === Number(documentItem.id); });
            if (from < 0 || to < 0 || from === to) return;
            const item = documents.splice(from, 1)[0];
            documents.splice(to, 0, item);
            renderDocuments();
        });
        documentsHost.appendChild(row);
    });
}

form.addEventListener('submit', function (event) {
    event.preventDefault();
    const title = String(titleInput.value || '').trim();
    if (title === '') { titleInput.focus(); return; }
    submit.disabled = true;
    fetch('/omo/api/documents/merge_action.php', {
        method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({
            title: title,
            keywords: tags,
            contextDocumentId: Number(data.contextDocumentId || 0),
            visibilityType: String((form.querySelector('input[name="visibility_type"]:checked') || {}).value || ''),
            editVisibilityType: String((form.querySelector('input[name="edit_visibility_type"]:checked') || {}).value || ''),
            keepSources: !keepSourcesInput || keepSourcesInput.checked,
            ids: documents.map(function (item) { return Number(item.id || 0); })
        })
    }).then(function (response) { return response.json().then(function (payload) { if (!response.ok || !payload.status) throw new Error(payload.message || ui.submitError); return payload; }); })
      .then(function () {
          window.dispatchEvent(new CustomEvent('omo-documents-merge-complete', {detail: {ids: documents.map(function (item) { return Number(item.id || 0); })}}));
          if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal();
          if (typeof window.omoRefreshDocumentsPanel === 'function') window.omoRefreshDocumentsPanel();
      }).catch(function (error) {
          feedback.hidden = false; feedback.className = 'generic-feedback is-error'; feedback.textContent = error.message; submit.disabled = false;
      });
});
form.querySelector('[data-omo-document-merge-cancel]').addEventListener('click', function () { if (typeof window.commonTopbarCloseModal === 'function') window.commonTopbarCloseModal(); });
renderTags(); renderDocuments();
})();
};
