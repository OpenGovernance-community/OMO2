(function () {
    if (window.genericMultilineListPaste && typeof window.genericMultilineListPaste.attach === 'function') {
        return;
    }

    function normalizeListItemText(item) {
        var normalized = String(item || '').replace(/\u00a0/g, ' ').trim();

        normalized = normalized.replace(/^(?:[-*+•◦▪‣⁃◾◼◻◽■□▸▹►▻➜➤➢◆◇○●]|[–—])\s+/, '');
        normalized = normalized.replace(/^(?:[-*+•◦▪‣⁃]\s*)?\[(?:\s|x|X)\]\s+/, '');
        normalized = normalized.replace(/^(?:[-*+•◦▪‣⁃]\s*)?[☐☑☒✅✓✔✗✘]\s+/, '');
        normalized = normalized.replace(/^\(\d{1,3}\)\s+/, '');
        normalized = normalized.replace(/^\([a-zA-Z]\)\s+/, '');
        normalized = normalized.replace(/^\d{1,3}[.):-]\s+/, '');
        normalized = normalized.replace(/^[a-zA-Z][.):-]\s+/, '');

        return normalized.trim();
    }

    function parsePlainTextItems(text) {
        return String(text || '')
            .split(/\r\n|\r|\n/)
            .map(function (item) {
                return normalizeListItemText(item);
            })
            .filter(function (item) {
                return item !== '';
            });
    }

    function parseHtmlItems(html) {
        var source = String(html || '');
        var scratch;

        if (!source || !/<\s*(p|div|li|br)\b/i.test(source)) {
            return [];
        }

        scratch = document.createElement('div');
        scratch.innerHTML = source
            .replace(/<\s*br\s*\/?\s*>/gi, '\n')
            .replace(/<\s*\/?\s*(p|div|li)\b[^>]*>/gi, '\n');

        return parsePlainTextItems(scratch.textContent || scratch.innerText || '');
    }

    function extractItems(event) {
        var clipboard = event && (event.clipboardData || window.clipboardData);
        var htmlItems;
        var textItems;

        if (!clipboard || typeof clipboard.getData !== 'function') {
            return [];
        }

        htmlItems = parseHtmlItems(clipboard.getData('text/html') || '');
        if (htmlItems.length > 1) {
            return htmlItems;
        }

        textItems = parsePlainTextItems(clipboard.getData('text/plain') || '');
        if (textItems.length > 1) {
            return textItems;
        }

        return [];
    }

    function replaceFieldValue(field, nextValue) {
        var currentValue = String(field && field.value ? field.value : '');
        var selectionStart = typeof field.selectionStart === 'number' ? field.selectionStart : currentValue.length;
        var selectionEnd = typeof field.selectionEnd === 'number' ? field.selectionEnd : currentValue.length;
        var valueBeforeSelection = currentValue.slice(0, selectionStart);
        var valueAfterSelection = currentValue.slice(selectionEnd);
        var safeValue = String(nextValue || '');
        var caretPosition = valueBeforeSelection.length + safeValue.length;

        field.value = valueBeforeSelection + safeValue + valueAfterSelection;

        if (typeof field.setSelectionRange === 'function') {
            field.setSelectionRange(caretPosition, caretPosition);
        }
    }

    function attach(root, options) {
        var config = options && typeof options === 'object' ? options : {};
        var bindingKey;

        if (!root || root.nodeType !== 1) {
            return;
        }

        if (
            !config.inputSelector
            || !config.rowSelector
            || !config.listSelector
            || !config.itemsSelector
            || typeof config.renderRow !== 'function'
        ) {
            return;
        }

        bindingKey = [
            config.inputSelector,
            config.rowSelector,
            config.listSelector,
            config.itemsSelector
        ].join('::');

        if (!Array.isArray(root.__genericMultilineListPasteBindings)) {
            root.__genericMultilineListPasteBindings = [];
        }

        if (root.__genericMultilineListPasteBindings.indexOf(bindingKey) !== -1) {
            return;
        }

        root.__genericMultilineListPasteBindings.push(bindingKey);

        root.addEventListener('paste', function (event) {
            var target = event.target;
            var list;
            var row;
            var itemsContainer;
            var listItemType;
            var items;

            if (!(target instanceof Element) || !target.matches(config.inputSelector)) {
                return;
            }

            list = target.closest(config.listSelector);
            row = target.closest(config.rowSelector);
            if (!list || !row) {
                return;
            }

            listItemType = String(list.getAttribute(config.listItemTypeAttribute || 'data-list-item-type') || 'text')
                .trim()
                .toLowerCase();
            if (listItemType !== String(config.textListType || 'text').trim().toLowerCase()) {
                return;
            }

            items = extractItems(event);
            if (items.length < 2) {
                return;
            }

            itemsContainer = list.querySelector(config.itemsSelector);
            if (!itemsContainer) {
                return;
            }

            event.preventDefault();
            replaceFieldValue(target, items[0]);

            if (items.length > 1) {
                row.insertAdjacentHTML('afterend', items.slice(1).map(function (item) {
                    return config.renderRow(listItemType, item);
                }).join(''));
            }

            if (typeof config.onAfterPaste === 'function') {
                config.onAfterPaste({
                    field: target,
                    row: row,
                    list: list,
                    itemsContainer: itemsContainer,
                    items: items
                });
            }
        });
    }

    window.genericMultilineListPaste = {
        attach: attach
    };
})();
