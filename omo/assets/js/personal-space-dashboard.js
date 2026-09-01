(function (window, document) {
    'use strict';

    var root = document.getElementById('omo-personal-space-root');
    if (!root || root.dataset.omoDashboardReady === '1') {
        return;
    }
    root.dataset.omoDashboardReady = '1';

    var editor = root.querySelector('[data-omo-dashboard-editor]');
    var editorGrid = root.querySelector('[data-omo-dashboard-editor-grid]');
    var picker = root.querySelector('[data-omo-dashboard-picker]');
    var pickerList = root.querySelector('[data-omo-dashboard-picker-list]');
    var saveMenu = root.querySelector('[data-omo-dashboard-save-menu]');
    var saveMenuToggle = root.querySelector('[data-omo-dashboard-save-menu-toggle]');
    var saveMenuPanel = root.querySelector('[data-omo-dashboard-save-menu-panel]');
    var layout = parseJson(root.getAttribute('data-omo-dashboard-layout'), []);
    var catalog = parseJson(root.getAttribute('data-omo-dashboard-catalog'), []);
    var texts = parseJson(root.getAttribute('data-omo-dashboard-texts'), {});
    var workingLayout = [];
    var selectedCells = [];
    var rowCount = 4;
    var pickerMode = null;

    function parseJson(value, fallback) {
        try {
            var parsed = JSON.parse(value || '');
            return parsed !== null ? parsed : fallback;
        } catch (error) {
            return fallback;
        }
    }

    function escapeHtml(value) {
        var node = document.createElement('div');
        node.textContent = String(value || '');
        return node.innerHTML;
    }

    function moduleLabel(type) {
        var item = catalog.find(function (candidate) { return candidate.type === type; });
        return item ? item.label : type;
    }

    function moduleAt(row, column) {
        return workingLayout.find(function (module) {
            return row >= module.row
                && row < module.row + module.rowSpan
                && column >= module.column
                && column < module.column + module.columnSpan;
        }) || null;
    }

    function cellKey(cell) {
        return String(cell.row) + ':' + String(cell.column);
    }

    function selectionGeometry() {
        if (selectedCells.length === 0) {
            return null;
        }
        var rows = selectedCells.map(function (cell) { return cell.row; });
        var columns = selectedCells.map(function (cell) { return cell.column; });
        var minRow = Math.min.apply(Math, rows);
        var maxRow = Math.max.apply(Math, rows);
        var minColumn = Math.min.apply(Math, columns);
        var maxColumn = Math.max.apply(Math, columns);
        return {
            row: minRow,
            column: minColumn,
            rowSpan: maxRow - minRow + 1,
            columnSpan: maxColumn - minColumn + 1
        };
    }

    function selectCell(row, column, extend) {
        var first;
        var distance;
        if (moduleAt(row, column)) {
            return;
        }
        if (!extend || selectedCells.length !== 1) {
            selectedCells = [{row: row, column: column}];
            renderEditor();
            return;
        }
        first = selectedCells[0];
        distance = Math.abs(first.row - row) + Math.abs(first.column - column);
        selectedCells = distance === 1
            ? [first, {row: row, column: column}]
            : [{row: row, column: column}];
        renderEditor();
    }

    function renderEditor() {
        var highestRow = workingLayout.reduce(function (maximum, module) {
            return Math.max(maximum, Number(module.row || 0) + Number(module.rowSpan || 1));
        }, 0);
        rowCount = Math.max(rowCount, highestRow + 1, 4);
        editorGrid.innerHTML = '';
        editorGrid.style.setProperty('--omo-dashboard-editor-rows', String(rowCount));

        for (var row = 0; row < rowCount; row++) {
            for (var column = 0; column < 2; column++) {
                var cell = document.createElement('button');
                var selected = selectedCells.some(function (candidate) {
                    return candidate.row === row && candidate.column === column;
                });
                cell.type = 'button';
                cell.className = 'omo-dashboard-editor__cell' + (selected ? ' is-selected' : '');
                cell.style.gridRow = String(row + 1);
                cell.style.gridColumn = String(column + 1);
                cell.disabled = Boolean(moduleAt(row, column));
                cell.setAttribute('data-row', String(row));
                cell.setAttribute('data-column', String(column));
                cell.setAttribute('aria-label', 'Ligne ' + String(row + 1) + ', colonne ' + String(column + 1));
                editorGrid.appendChild(cell);
            }
        }

        workingLayout.forEach(function (module) {
            var card = document.createElement('article');
            card.className = 'omo-dashboard-editor__module';
            card.style.gridRow = String(module.row + 1) + ' / span ' + String(module.rowSpan);
            card.style.gridColumn = String(module.column + 1) + ' / span ' + String(module.columnSpan);
            card.innerHTML = '<strong>' + escapeHtml(moduleLabel(module.type)) + '</strong>'
                + '<span>' + String(module.columnSpan) + ' × ' + String(module.rowSpan) + '</span>'
                + '<div class="omo-dashboard-editor__module-actions">'
                + '<button type="button" class="generic-action-button generic-action-button--secondary" data-replace-module="' + escapeHtml(module.id) + '">' + escapeHtml(texts.replace || 'Remplacer') + '</button>'
                + '<button type="button" class="generic-action-button generic-action-button--danger" data-delete-module="' + escapeHtml(module.id) + '">' + escapeHtml(texts.delete || 'Supprimer') + '</button>'
                + '</div>';
            editorGrid.appendChild(card);
        });

        var geometry = selectionGeometry();
        if (geometry) {
            var selection = document.createElement('div');
            var choose = document.createElement('button');
            selection.className = 'omo-dashboard-editor__selection';
            selection.style.gridRow = String(geometry.row + 1) + ' / span ' + String(geometry.rowSpan);
            selection.style.gridColumn = String(geometry.column + 1) + ' / span ' + String(geometry.columnSpan);
            choose.type = 'button';
            choose.className = 'generic-action-button generic-action-button--main';
            choose.setAttribute('data-choose-module', '1');
            choose.textContent = texts.choose || 'Choisir un module';
            selection.appendChild(choose);
            editorGrid.appendChild(selection);
        }
    }

    function openPicker(mode) {
        pickerMode = mode;
        pickerList.innerHTML = '';
        catalog.filter(function (item) { return item.enabled; }).forEach(function (item) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'generic-action-button generic-action-button--secondary omo-dashboard-picker__item';
            button.setAttribute('data-module-type', item.type);
            button.textContent = item.label;
            pickerList.appendChild(button);
        });
        picker.hidden = false;
    }

    function closePicker() {
        picker.hidden = true;
        pickerMode = null;
    }

    function setSaveMenuOpen(isOpen) {
        if (!saveMenu || !saveMenuToggle || !saveMenuPanel) {
            return;
        }
        saveMenu.classList.toggle('is-open', Boolean(isOpen));
        saveMenuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        saveMenuPanel.hidden = !isOpen;
    }

    function chooseModule(type) {
        var geometry;
        var module;
        if (pickerMode && pickerMode.kind === 'replace') {
            module = workingLayout.find(function (candidate) { return candidate.id === pickerMode.id; });
            if (module) {
                module.type = type;
            }
        } else {
            geometry = selectionGeometry();
            if (!geometry) {
                return;
            }
            workingLayout.push({
                id: type + '-' + String(Date.now()),
                type: type,
                row: geometry.row,
                column: geometry.column,
                rowSpan: geometry.rowSpan,
                columnSpan: geometry.columnSpan
            });
            selectedCells = [];
        }
        closePicker();
        renderEditor();
    }

    function openEditor() {
        workingLayout = JSON.parse(JSON.stringify(layout));
        selectedCells = [];
        rowCount = Math.max(4, workingLayout.reduce(function (maximum, module) {
            return Math.max(maximum, module.row + module.rowSpan);
        }, 0) + 1);
        editor.hidden = false;
        window.requestAnimationFrame(function () { editor.classList.add('is-open'); });
        renderEditor();
    }

    function closeEditor() {
        editor.classList.remove('is-open');
        closePicker();
        setSaveMenuOpen(false);
        window.setTimeout(function () { editor.hidden = true; }, 220);
    }

    function saveLayout(scope, templateKey) {
        var saveButtons = root.querySelectorAll('[data-omo-dashboard-editor-save], [data-omo-dashboard-editor-reset], [data-omo-dashboard-save-scope], [data-omo-dashboard-save-menu-toggle]');
        scope = scope === 'holon' || scope === 'organization' || scope === 'application' || scope === 'organization_template' || scope === 'application_template' || scope === 'personal_reset'
            ? scope
            : 'personal';
        saveButtons.forEach(function (button) { button.disabled = true; });
        setSaveMenuOpen(false);
        fetch(root.getAttribute('data-omo-dashboard-save-url'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({
                oid: Number(root.getAttribute('data-omo-personal-space-oid') || 0),
                holon_id: Number(root.getAttribute('data-omo-dashboard-holon-id') || 0),
                csrf_token: root.getAttribute('data-omo-dashboard-csrf') || '',
                scope: scope,
                template_key: templateKey || '',
                layout: workingLayout
            })
        }).then(function (response) {
            return response.json().then(function (payload) {
                if (!response.ok || !payload.status) {
                    throw new Error(payload.message || texts.saveError || 'Save failed');
                }
                layout = Array.isArray(payload.layout) ? payload.layout : workingLayout;
                closeEditor();
                if (typeof window.omoRefreshMainRightPanel === 'function') {
                    window.omoRefreshMainRightPanel(
                        Number(root.getAttribute('data-omo-personal-space-oid') || 0),
                        Number(root.getAttribute('data-omo-personal-space-cid') || 0),
                        {force: true}
                    );
                } else {
                    window.location.reload();
                }
            });
        }).catch(function (error) {
            window.alert(error.message || texts.saveError || 'Save failed');
        }).finally(function () {
            saveButtons.forEach(function (button) { button.disabled = false; });
        });
    }

    root.addEventListener('click', function (event) {
        var metric = event.target.closest('[data-omo-dashboard-filter]');
        var module;
        var filter;
        var cell;
        var replaceButton;
        var deleteButton;
        var moduleButton;
        var saveScopeButton;

        if (metric) {
            module = metric.closest('[data-omo-dashboard-module]');
            filter = metric.getAttribute('data-omo-dashboard-filter') || '';
            metric.parentNode.querySelectorAll('[data-omo-dashboard-filter]').forEach(function (button) {
                button.classList.toggle('is-active', button === metric && !metric.classList.contains('is-active'));
            });
            filter = metric.classList.contains('is-active') ? filter : '';
            module.querySelectorAll('[data-omo-dashboard-filter-item]').forEach(function (item) {
                var filters = (item.getAttribute('data-omo-dashboard-filter-item') || '').split(/\s+/);
                item.hidden = filter !== '' && filters.indexOf(filter) === -1;
            });
            return;
        }
        if (event.target.closest('[data-omo-dashboard-edit]')) {
            openEditor();
            return;
        }
        if (event.target.closest('[data-omo-dashboard-editor-close]')) {
            closeEditor();
            return;
        }
        if (event.target.closest('[data-omo-dashboard-editor-save]')) {
            saveLayout(
                event.target.closest('[data-omo-dashboard-editor-save]').getAttribute('data-omo-dashboard-editor-save'),
                event.target.closest('[data-omo-dashboard-editor-save]').getAttribute('data-omo-dashboard-template-key')
            );
            return;
        }
        if (event.target.closest('[data-omo-dashboard-editor-reset]')) {
            saveLayout('personal_reset');
            return;
        }
        if (event.target.closest('[data-omo-dashboard-save-menu-toggle]')) {
            setSaveMenuOpen(saveMenuPanel.hidden);
            return;
        }
        saveScopeButton = event.target.closest('[data-omo-dashboard-save-scope]');
        if (saveScopeButton) {
            saveLayout(
                saveScopeButton.getAttribute('data-omo-dashboard-save-scope'),
                saveScopeButton.getAttribute('data-omo-dashboard-template-key')
            );
            return;
        }
        if (event.target.closest('[data-omo-dashboard-add-row]')) {
            rowCount += 1;
            renderEditor();
            return;
        }
        cell = event.target.closest('.omo-dashboard-editor__cell');
        if (cell) {
            selectCell(Number(cell.getAttribute('data-row')), Number(cell.getAttribute('data-column')), event.shiftKey);
            return;
        }
        if (event.target.closest('[data-choose-module]')) {
            openPicker({kind: 'insert'});
            return;
        }
        replaceButton = event.target.closest('[data-replace-module]');
        if (replaceButton) {
            openPicker({kind: 'replace', id: replaceButton.getAttribute('data-replace-module')});
            return;
        }
        deleteButton = event.target.closest('[data-delete-module]');
        if (deleteButton) {
            workingLayout = workingLayout.filter(function (candidate) {
                return candidate.id !== deleteButton.getAttribute('data-delete-module');
            });
            selectedCells = [];
            renderEditor();
            return;
        }
        if (event.target.closest('[data-omo-dashboard-picker-close]')) {
            closePicker();
            return;
        }
        moduleButton = event.target.closest('[data-module-type]');
        if (moduleButton) {
            chooseModule(moduleButton.getAttribute('data-module-type') || '');
        }
    });

    function closeSaveMenuOnOutsideClick(event) {
        if (!document.body.contains(root)) {
            document.removeEventListener('click', closeSaveMenuOnOutsideClick);
            return;
        }
        if (saveMenu && !saveMenu.contains(event.target)) {
            setSaveMenuOpen(false);
        }
    }

    document.addEventListener('click', closeSaveMenuOnOutsideClick);
})(window, document);
