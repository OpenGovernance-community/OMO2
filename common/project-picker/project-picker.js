(function (window) {
    'use strict';

    function normalizeId(value) {
        var id = Number(value || 0);
        return Number.isInteger(id) && id > 0 ? id : 0;
    }

    function normalizeIds(values) {
        var normalized = [];
        (Array.isArray(values) ? values : []).forEach(function (value) {
            var id = normalizeId(value);
            if (id > 0 && normalized.indexOf(id) === -1) {
                normalized.push(id);
            }
        });
        return normalized;
    }

    function resolveElement(value, root) {
        if (value instanceof Element) {
            return value;
        }
        return root instanceof Element && typeof value === 'string' ? root.querySelector(value) : null;
    }

    window.commonMountProjectPicker = function (options) {
        var settings = options && typeof options === 'object' ? options : {};
        var root = settings.root instanceof Element ? settings.root : document;
        var scopeHost = resolveElement(settings.scopeHost, root);
        var searchInput = resolveElement(settings.searchInput, root);
        var selectElement = resolveElement(settings.selectElement, root);
        var emptyElement = resolveElement(settings.emptyElement, root);
        var projects = Array.isArray(settings.projects) ? settings.projects.slice() : [];
        var multiple = settings.multiple === true;
        var selectedIds = new Set(normalizeIds(settings.selectedIds));
        var assignment = String(settings.initialAssignment || 'all');
        var assignmentAttribute = String(settings.assignmentAttribute || 'data-project-picker-assignment');
        var assignmentButtons = settings.assignmentButtons
            ? Array.from(settings.assignmentButtons)
            : (root instanceof Element ? Array.from(root.querySelectorAll('[' + assignmentAttribute + ']')) : []);
        var scopePicker = null;
        var visibleProjects = [];
        var destroyed = false;
        var listeners = [];

        function projectId(project) {
            return normalizeId(typeof settings.getProjectId === 'function' ? settings.getProjectId(project) : project && project.id);
        }

        function projectHolonId(project) {
            return normalizeId(typeof settings.getHolonId === 'function'
                ? settings.getHolonId(project)
                : (project && (project.contextHolonId || project.holonId)));
        }

        function projectSearchText(project) {
            return String(typeof settings.getSearchText === 'function'
                ? settings.getSearchText(project)
                : [project && project.title, project && project.contextLabel, project && project.holonLabel, project && project.summary].join(' '));
        }

        function projectOptionLabel(project) {
            return String(typeof settings.getOptionLabel === 'function'
                ? settings.getOptionLabel(project)
                : (project && project.title || ''));
        }

        function selectedProjects() {
            return Array.from(selectedIds).map(function (id) {
                return projects.find(function (project) { return projectId(project) === id; }) || null;
            }).filter(Boolean);
        }

        function notify() {
            if (typeof settings.onChange === 'function') {
                settings.onChange(selectedProjects(), {
                    selectedIds: Array.from(selectedIds),
                    assignment: assignment,
                    holonId: scopePicker && typeof scopePicker.getSelectedHolonId === 'function'
                        ? normalizeId(scopePicker.getSelectedHolonId())
                        : 0
                });
            }
        }

        function matchesAssignment(project) {
            return typeof settings.matchesAssignment === 'function'
                ? settings.matchesAssignment(project, assignment)
                : true;
        }

        function matchesScope(project) {
            if (!scopePicker) {
                return true;
            }
            var holonId = projectHolonId(project);
            var selectedHolonId = typeof scopePicker.getSelectedHolonId === 'function'
                ? normalizeId(scopePicker.getSelectedHolonId())
                : 0;
            return scopePicker.matches(holonId) || (selectedHolonId > 0 && holonId === selectedHolonId);
        }

        function render() {
            if (destroyed || !(selectElement instanceof HTMLSelectElement)) {
                return;
            }
            var query = String(searchInput && searchInput.value || '').trim().toLocaleLowerCase();
            visibleProjects = projects.filter(function (project) {
                return matchesScope(project)
                    && matchesAssignment(project)
                    && (query === '' || projectSearchText(project).toLocaleLowerCase().includes(query));
            });

            selectElement.innerHTML = '';
            visibleProjects.forEach(function (project) {
                var id = projectId(project);
                if (id <= 0) {
                    return;
                }
                var option = document.createElement('option');
                option.value = String(id);
                option.textContent = projectOptionLabel(project);
                option.selected = selectedIds.has(id);
                selectElement.appendChild(option);
            });
            selectElement.multiple = multiple;
            selectElement.disabled = visibleProjects.length === 0;
            if (emptyElement) {
                emptyElement.hidden = visibleProjects.length > 0;
            }

            if (!multiple) {
                var selectedVisibleProject = visibleProjects.find(function (project) {
                    return selectedIds.has(projectId(project));
                }) || visibleProjects[0] || null;
                selectedIds.clear();
                if (selectedVisibleProject) {
                    var selectedId = projectId(selectedVisibleProject);
                    selectedIds.add(selectedId);
                    selectElement.value = String(selectedId);
                }
            }
            notify();
        }

        function syncSelection() {
            if (!(selectElement instanceof HTMLSelectElement)) {
                return;
            }
            if (multiple) {
                visibleProjects.forEach(function (project) {
                    selectedIds.delete(projectId(project));
                });
                Array.from(selectElement.selectedOptions).forEach(function (option) {
                    var id = normalizeId(option.value);
                    if (id > 0) {
                        selectedIds.add(id);
                    }
                });
            } else {
                selectedIds.clear();
                var selectedId = normalizeId(selectElement.value);
                if (selectedId > 0) {
                    selectedIds.add(selectedId);
                }
            }
            notify();
        }

        function listen(element, eventName, handler) {
            if (!element || typeof element.addEventListener !== 'function') {
                return;
            }
            element.addEventListener(eventName, handler);
            listeners.push([element, eventName, handler]);
        }

        listen(searchInput, 'input', render);
        listen(selectElement, 'change', syncSelection);
        if (multiple) {
            listen(selectElement, 'mousedown', function (event) {
                if (!(event.target instanceof HTMLOptionElement)) {
                    return;
                }
                event.preventDefault();
                selectElement.focus();
                event.target.selected = !event.target.selected;
                syncSelection();
            });
        }
        assignmentButtons.forEach(function (button) {
            listen(button, 'click', function () {
                assignment = String(button.getAttribute(assignmentAttribute) || 'all');
                assignmentButtons.forEach(function (choice) {
                    var active = String(choice.getAttribute(assignmentAttribute) || 'all') === assignment;
                    choice.classList.toggle('is-active', active);
                    choice.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                render();
            });
        });

        if (scopeHost instanceof Element && typeof window.omoMountHolonScopePicker === 'function') {
            scopePicker = window.omoMountHolonScopePicker({
                host: scopeHost,
                organizationId: normalizeId(settings.organizationId),
                initialHolonId: normalizeId(settings.initialHolonId),
                initialScope: String(settings.initialScope || 'local'),
                labels: settings.scopeLabels || {},
                showModes: settings.showModes !== false,
                labelMode: settings.labelMode === 'context' ? 'context' : 'children',
                suppressInitialChange: true,
                onChange: function (holonId) {
                    render();
                    if (typeof settings.onHolonChange === 'function') {
                        settings.onHolonChange(normalizeId(holonId));
                    }
                },
                onReady: function (holonId) {
                    render();
                    if (typeof settings.onHolonChange === 'function') {
                        settings.onHolonChange(normalizeId(holonId));
                    }
                }
            });
        }

        render();

        return {
            destroy: function () {
                destroyed = true;
                listeners.forEach(function (listener) {
                    listener[0].removeEventListener(listener[1], listener[2]);
                });
                listeners = [];
                if (scopePicker && typeof scopePicker.destroy === 'function') {
                    scopePicker.destroy();
                }
                scopePicker = null;
            },
            refresh: render,
            getSelectedIds: function () { return Array.from(selectedIds); },
            getSelectedProjects: selectedProjects,
            getSelectedHolonId: function () {
                return scopePicker && typeof scopePicker.getSelectedHolonId === 'function'
                    ? normalizeId(scopePicker.getSelectedHolonId())
                    : 0;
            },
            setSelectedIds: function (values) {
                selectedIds = new Set(normalizeIds(values));
                render();
            }
        };
    };
})(window);
