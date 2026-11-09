(function () {
    if (window.commonDecisionInitQuestionSettingsCopy) return;

    const fieldNames = new Set([
        'choice_mode', 'max_choices', 'is_anonymous', 'allow_anonymous_votes',
        'allow_consultation_proposals', 'allow_proposal_discussions',
        'randomize_proposal_order', 'one_proposal_at_a_time',
        'proposal_content_title', 'proposal_content_description', 'proposal_content_url', 'proposal_content_date',
        'vote_weight_enabled', 'vote_weight_question', 'vote_weight_options_json', 'mention_customization_enabled'
    ]);
    let openMenu = null;

    function closeMenu(focusToggle) {
        if (!openMenu) return;
        openMenu.panel.hidden = true;
        openMenu.toggle.setAttribute('aria-expanded', 'false');
        if (focusToggle) openMenu.toggle.focus();
        openMenu = null;
    }

    function getForm(panel) {
        return panel.querySelector('form[data-omo-decision-consent-form], form[data-omo-decision-vote-form], form[data-omo-decision-majority-judgment-form]');
    }

    function getSources(root, target) {
        return Array.from(root.querySelectorAll('[data-omo-decision-question-panel]')).filter(function (panel) {
            const form = getForm(panel);
            return panel !== target && panel.dataset.questionMethod === target.dataset.questionMethod
                && form && form.commonDecisionQuestionSettings;
        });
    }

    function getLabel(root, panel) {
        const link = Array.from(root.querySelectorAll('[data-omo-decision-question-link]')).find(function (item) {
            return item.dataset.questionKey === panel.dataset.questionKey;
        });
        return link ? link.firstElementChild.textContent.trim() : '';
    }

    function copySettings(sourceForm, targetForm) {
        const sourceFields = new Map();
        sourceForm.querySelectorAll('input[type="hidden"][name]').forEach(function (field) {
            if (fieldNames.has(field.name) || /^mention_(labels|active)\[\d+\]$/.test(field.name)) {
                sourceFields.set(field.name, field.value);
            }
        });
        const changedFields = [];
        targetForm.querySelectorAll('input[type="hidden"][name]').forEach(function (field) {
            if (sourceFields.has(field.name)) {
                field.value = sourceFields.get(field.name);
                changedFields.push(field);
            }
        });
        const settings = targetForm.commonDecisionQuestionSettings;
        const content = {};
        ['title', 'description', 'url', 'date'].forEach(function (key) {
            const field = targetForm.elements.namedItem('proposal_content_' + key);
            content[key] = !!(field && field.value);
        });
        settings.payload.proposalContent = content;
        if (window.omoProposalHtml && typeof window.omoProposalHtml.refreshDecisionProposalCards === 'function') {
            window.omoProposalHtml.refreshDecisionProposalCards(settings.proposals, content, {
                descriptionSelector: '[data-omo-decision-' + settings.prefix + '-proposal-description]',
                detailsSelector: '[data-omo-decision-' + settings.prefix + '-proposal-settings]',
                canEdit: settings.editable
            });
        }
        settings.refreshSummary();
        changedFields.forEach(function (field) {
            field.dispatchEvent(new Event('input', {bubbles: true}));
            field.dispatchEvent(new Event('change', {bubbles: true}));
        });
    }

    window.commonDecisionInitQuestionSettingsCopy = function (root, texts) {
        root.querySelectorAll('[data-omo-decision-question-panel]').forEach(function (target) {
            const form = getForm(target);
            const settings = form && form.commonDecisionQuestionSettings;
            if (!settings || !settings.button) return;
            if (!settings.copyToggle) {
                const group = document.createElement('div');
                group.className = 'generic-menu generic-menu--split';
                settings.button.before(group);
                group.appendChild(settings.button);
                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'generic-menu-toggle';
                toggle.textContent = '\u25be';
                toggle.setAttribute('data-decision-settings-copy-toggle', '');
                toggle.setAttribute('aria-label', texts.label);
                toggle.setAttribute('aria-haspopup', 'menu');
                toggle.setAttribute('aria-expanded', 'false');
                const panel = document.createElement('div');
                panel.className = 'generic-menu-panel generic-menu-panel--wide';
                panel.setAttribute('role', 'menu');
                panel.setAttribute('aria-label', texts.label);
                panel.hidden = true;
                group.append(toggle, panel);
                settings.copyToggle = toggle;
                settings.button.addEventListener('click', function () { closeMenu(false); });
                toggle.addEventListener('click', function () {
                    const wasOpen = !panel.hidden;
                    closeMenu(false);
                    if (wasOpen || !settings.editable || root.getAttribute('aria-busy') === 'true') return;
                    panel.replaceChildren();
                    getSources(root, target).forEach(function (source) {
                        const label = getLabel(root, source);
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'generic-menu-item';
                        item.setAttribute('role', 'menuitem');
                        item.textContent = texts.from.replace('__NAME__', label);
                        item.addEventListener('click', function () {
                            if (!settings.editable || root.getAttribute('aria-busy') === 'true' || !root.contains(source)) return;
                            copySettings(getForm(source), form);
                            closeMenu(true);
                            if (typeof window.commonNotify === 'function') {
                                window.commonNotify(texts.copied.replace('__NAME__', label), 'success');
                            }
                        });
                        panel.appendChild(item);
                    });
                    if (!panel.childElementCount) return;
                    panel.hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                    openMenu = {panel: panel, toggle: toggle, group: group};
                    panel.firstElementChild.focus();
                });
                group.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && openMenu) {
                        event.preventDefault();
                        event.stopPropagation();
                        closeMenu(true);
                    } else if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                        event.preventDefault();
                        if (panel.hidden) { toggle.click(); return; }
                        const items = Array.from(panel.children);
                        const index = items.indexOf(document.activeElement);
                        const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                            : (index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                        items[next].focus();
                    }
                });
            }
            const hasSources = getSources(root, target).length > 0;
            settings.copyToggle.disabled = !settings.editable || !hasSources;
            settings.copyToggle.title = hasSources ? texts.label : texts.empty;
        });
    };

    document.addEventListener('click', function (event) {
        if (openMenu && !openMenu.group.contains(event.target)) closeMenu(false);
    });
    document.addEventListener('focusin', function (event) {
        if (openMenu && !openMenu.group.contains(event.target)) closeMenu(false);
    });
})();
