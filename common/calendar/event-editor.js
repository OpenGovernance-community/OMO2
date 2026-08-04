(function (window, document) {
    'use strict';

    function parseLocalDateTime(value) {
        var normalized = String(value || '').trim();
        if (!normalized) {
            return null;
        }
        var parsed = new Date(normalized);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    function formatLocalDateTimeValue(value) {
        function pad(number) {
            return String(number).padStart(2, '0');
        }
        return value.getFullYear() + '-' + pad(value.getMonth() + 1) + '-' + pad(value.getDate())
            + 'T' + pad(value.getHours()) + ':' + pad(value.getMinutes());
    }

    function rememberScheduleState(form) {
        var startField = form.querySelector('input[name="start_at"]');
        var endField = form.querySelector('input[name="end_at"]');
        if (!startField || !endField) {
            return;
        }
        form.dataset.omoCalendarLastStart = startField.value || '';
        form.dataset.omoCalendarLastEnd = endField.value || '';
    }

    function syncEndDateWithStart(form) {
        var startField = form.querySelector('input[name="start_at"]');
        var endField = form.querySelector('input[name="end_at"]');
        if (!startField || !endField) {
            return;
        }
        var startDate = parseLocalDateTime(startField.value);
        if (!startDate) {
            return;
        }
        var previousStart = parseLocalDateTime(form.dataset.omoCalendarLastStart || '');
        var previousEnd = parseLocalDateTime(form.dataset.omoCalendarLastEnd || '');
        var duration = previousStart && previousEnd ? Math.max(0, previousEnd.getTime() - previousStart.getTime()) : 0;
        endField.value = duration > 0
            ? formatLocalDateTimeValue(new Date(startDate.getTime() + duration))
            : startField.value;
        rememberScheduleState(form);
    }

    function syncLocationFields(form) {
        var modeField = form.querySelector('[data-omo-calendar-location-mode]');
        var addressField = form.querySelector('[data-omo-calendar-location-address-field]');
        var videoField = form.querySelector('[data-omo-calendar-location-video-field]');
        var mode = modeField ? String(modeField.value || '') : '';
        var showAddress = mode === 'in_person' || mode === 'hybrid';
        var showVideo = mode === 'virtual' || mode === 'hybrid';

        if (addressField) {
            addressField.hidden = !showAddress;
            var addressInput = addressField.querySelector('input[name="location_address"]');
            if (addressInput) {
                addressInput.required = showAddress;
            }
        }
        if (videoField) {
            videoField.hidden = !showVideo;
            var videoInput = videoField.querySelector('input[name="video_meeting_url"]');
            if (videoInput) {
                videoInput.required = showVideo;
            }
        }
    }

    function syncDocumentFields(form) {
        var typeField = form.querySelector('[data-omo-calendar-document-type]');
        var fields = form.querySelector('[data-omo-calendar-document-fields]');
        var pvTemplateField = form.querySelector('[data-omo-calendar-pv-template-field]');
        var documentType = typeField ? String(typeField.value || '') : '';
        if (fields) {
            fields.hidden = documentType === '';
        }
        if (pvTemplateField) {
            pvTemplateField.hidden = documentType !== 'pv';
        }
    }

    function normalizeFilterText(value) {
        var normalized = String(value || '').toLowerCase();
        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return normalized.replace(/\s+/g, ' ').trim();
    }

    function directHolonChildren(node) {
        if (!node || !node.children) {
            return null;
        }
        for (var index = 0; index < node.children.length; index += 1) {
            if (node.children[index].hasAttribute('data-omo-calendar-holon-children')) {
                return node.children[index];
            }
        }
        return null;
    }

    function filterHolonNode(node, query) {
        var row = node.querySelector('.omo-calendar-invitations-editor__tree-row');
        var checkbox = row ? row.querySelector('input[type="checkbox"]') : null;
        var toggle = row ? row.querySelector('[data-omo-calendar-holon-toggle]') : null;
        var children = directHolonChildren(node);
        var hasVisibleChild = false;

        if (children) {
            Array.prototype.forEach.call(children.children, function (child) {
                if (child.hasAttribute('data-omo-calendar-holon-node') && filterHolonNode(child, query)) {
                    hasVisibleChild = true;
                }
            });
        }

        var searchText = normalizeFilterText(node.getAttribute('data-omo-calendar-search-text') || node.textContent || '');
        var visible = query === '' || searchText.indexOf(query) !== -1 || Boolean(checkbox && checkbox.checked) || hasVisibleChild;
        node.hidden = !visible;
        if (children) {
            children.hidden = query === ''
                ? Boolean(toggle && toggle.getAttribute('aria-expanded') !== 'true')
                : !hasVisibleChild;
        }
        return visible;
    }

    function syncInvitationFilters(editor) {
        var holonFilter = editor.querySelector('[data-omo-calendar-holon-filter]');
        var holonEmpty = editor.querySelector('[data-omo-calendar-holon-empty]');
        var holonQuery = normalizeFilterText(holonFilter ? holonFilter.value : '');
        var hasHolon = false;
        var holonList = editor.querySelector('[data-omo-calendar-holon-list]');
        if (holonList) {
            Array.prototype.forEach.call(holonList.children, function (node) {
                if (node.hasAttribute('data-omo-calendar-holon-node') && filterHolonNode(node, holonQuery)) {
                    hasHolon = true;
                }
            });
        }
        if (holonEmpty) {
            holonEmpty.hidden = holonQuery === '' || hasHolon;
        }

        var memberFilter = editor.querySelector('[data-omo-calendar-member-filter]');
        var memberEmpty = editor.querySelector('[data-omo-calendar-member-empty]');
        var memberQuery = normalizeFilterText(memberFilter ? memberFilter.value : '');
        var hasMember = false;
        Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-member-item]'), function (item) {
            var checkbox = item.querySelector('input[type="checkbox"]');
            var searchText = normalizeFilterText(item.getAttribute('data-omo-calendar-search-text') || item.textContent || '');
            var visible = memberQuery === '' || searchText.indexOf(memberQuery) !== -1 || Boolean(checkbox && checkbox.checked);
            item.hidden = !visible;
            hasMember = hasMember || visible;
        });
        if (memberEmpty) {
            memberEmpty.hidden = memberQuery === '' || hasMember;
        }
    }

    function initInvitationEditor(editor) {
        if (!editor || editor.dataset.omoCalendarInvitationsReady === '1') {
            return;
        }
        editor.dataset.omoCalendarInvitationsReady = '1';
        Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-holon-toggle]'), function (toggle) {
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                var node = toggle.closest('[data-omo-calendar-holon-node]');
                var children = directHolonChildren(node);
                if (!children) {
                    return;
                }
                var expanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                children.hidden = expanded;
            });
        });
        Array.prototype.forEach.call(editor.querySelectorAll('[data-omo-calendar-holon-filter], [data-omo-calendar-member-filter]'), function (field) {
            field.addEventListener('input', function () {
                syncInvitationFilters(editor);
            });
        });
        editor.addEventListener('change', function () {
            editor.setAttribute('data-omo-calendar-uses-default-selection', '0');
            syncInvitationFilters(editor);
        });
        syncInvitationFilters(editor);
    }

    function findSubmitButton(form) {
        var localButton = form.querySelector('[data-omo-calendar-create-submit]');
        if (localButton) {
            return localButton;
        }
        return form.id ? document.querySelector('[data-omo-calendar-create-submit][form="' + form.id + '"]') : null;
    }

    function setFeedback(form, message, isError) {
        var feedback = form.querySelector('[data-omo-calendar-create-feedback]');
        if (!feedback) {
            return;
        }
        feedback.textContent = String(message || '');
        feedback.className = 'omo-calendar-create__feedback generic-feedback' + (isError ? ' is-error' : '');
    }

    function initForm(form, options) {
        if (!form || form.dataset.omoCalendarStandaloneReady === '1') {
            return;
        }
        form.dataset.omoCalendarStandaloneReady = '1';
        syncLocationFields(form);
        syncDocumentFields(form);
        rememberScheduleState(form);
        Array.prototype.forEach.call(form.querySelectorAll('[data-omo-calendar-invitations-editor]'), initInvitationEditor);

        form.addEventListener('change', function (event) {
            if (event.target.matches('[data-omo-calendar-location-mode]')) {
                syncLocationFields(form);
            } else if (event.target.matches('[data-omo-calendar-document-type]')) {
                syncDocumentFields(form);
            } else if (event.target.matches('input[name="start_at"]')) {
                syncEndDateWithStart(form);
            } else if (event.target.matches('input[name="end_at"]')) {
                rememberScheduleState(form);
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var submitButton = findSubmitButton(form);
            if (submitButton) {
                submitButton.disabled = true;
            }
            setFeedback(form, 'Enregistrement…', false);

            fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            }).then(function (response) {
                return response.json().catch(function () {
                    return {status: false, message: 'Réponse invalide du serveur.'};
                }).then(function (payload) {
                    if (!response.ok || !payload.status) {
                        throw new Error(payload.message || "Impossible d'enregistrer cet événement.");
                    }
                    return payload;
                });
            }).then(function (payload) {
                setFeedback(form, payload.message || 'Événement enregistré.', false);
                if (typeof window.omoNotify === 'function') {
                    window.omoNotify(payload.message || 'Événement enregistré.', 'success');
                }
                if (options && typeof options.onSave === 'function') {
                    options.onSave(payload, form);
                }
            }).catch(function (error) {
                setFeedback(form, error.message || "Impossible d'enregistrer cet événement.", true);
            }).finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
        });
    }

    window.omoInitCalendarEventEditor = function (scope, options) {
        var root = scope && scope.querySelectorAll ? scope : document;
        if (typeof window.initGenericComponents === 'function') {
            window.initGenericComponents(root);
        }
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-calendar-create-form][data-omo-calendar-editor-host="project"]'), function (form) {
            initForm(form, options || {});
        });
    };
})(window, document);
