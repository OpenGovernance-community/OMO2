(function () {
    'use strict';

    var toolbarSelector = '#tableeditor > div:nth-child(1) > div:nth-child(1) > div:nth-child(2)';

    function syncToolbarOverflow() {
        var toolbar = document.querySelector(toolbarSelector);

        if (!toolbar) {
            return;
        }

        toolbar.classList.toggle(
            'omo-ethercalc-toolbar--overflowing',
            toolbar.scrollWidth > toolbar.clientWidth + 1
        );
    }

    function scheduleToolbarOverflowSync() {
        [0, 100, 300, 800].forEach(function (delay) {
            window.setTimeout(syncToolbarOverflow, delay);
        });
    }

    function resizeSpreadsheet(viewport) {
        var spreadsheet = window.spreadsheet;
        var parentNode = spreadsheet && spreadsheet.parentNode;
        var editor = spreadsheet && spreadsheet.editor;
        var targetWidth = Number(viewport && viewport.width);
        var targetHeight = Number(viewport && viewport.height);

        if (
            parentNode
            && editor
            && typeof editor.ResizeTableEditor === 'function'
            && targetWidth > 0
            && targetHeight > 0
        ) {
            var position = parentNode.getBoundingClientRect();
            var width = Math.max(1, Math.floor(targetWidth - position.left - 10));
            var height = Math.max(1, Math.floor(targetHeight - position.top - 10));
            var viewName;

            spreadsheet.width = width;
            spreadsheet.height = height;
            spreadsheet.spreadsheetDiv.style.width = width + 'px';
            spreadsheet.spreadsheetDiv.style.height = height + 'px';

            for (viewName in spreadsheet.views) {
                spreadsheet.views[viewName].element.style.width = width + 'px';
                spreadsheet.views[viewName].element.style.height = Math.max(1, height - spreadsheet.nonviewheight) + 'px';
            }

            editor.ResizeTableEditor(width, Math.max(1, height - spreadsheet.nonviewheight));
            return;
        }

        if (typeof window.doresize === 'function') {
            window.doresize();
            return;
        }

        if (
            window.spreadsheet
            && typeof window.spreadsheet.DoOnResize === 'function'
        ) {
            window.spreadsheet.DoOnResize();
        }
    }

    function scheduleResize(viewport) {
        [0, 50, 180, 500].forEach(function (delay) {
            window.setTimeout(function () {
                resizeSpreadsheet(viewport);
            }, delay);
        });
    }

    window.addEventListener('message', function (event) {
        if (!event.data || event.data.type !== 'omo-ethercalc-resize') {
            return;
        }

        scheduleResize(event.data.viewport);
    });

    window.addEventListener('resize', scheduleResize);
    window.addEventListener('resize', scheduleToolbarOverflowSync);

    if (typeof window.ResizeObserver === 'function') {
        var toolbarResizeObserver = new window.ResizeObserver(scheduleToolbarOverflowSync);
        toolbarResizeObserver.observe(document.documentElement);
    }

    scheduleToolbarOverflowSync();
}());
