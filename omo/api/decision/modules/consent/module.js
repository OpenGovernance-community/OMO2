        (function () {
            window.omoDecisionConsentInit = function (root) {
                const scope = root instanceof Element ? root : document;
                const getScopedMatches = function (selector) {
                    const nodes = [];
                    if (scope && typeof scope.matches === 'function' && scope.matches(selector)) {
                        nodes.push(scope);
                    }
                    if (scope && typeof scope.querySelectorAll === 'function') {
                        Array.prototype.forEach.call(scope.querySelectorAll(selector), function (node) {
                            nodes.push(node);
                        });
                    }
                    return nodes;
                };

                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-consent-form]'), function (form) {
                    if (form.dataset.omoDecisionConsentReady === '1') {
                        return;
                    }

                    const payloadNode = form.querySelector('[data-omo-decision-consent-data]');
                    const embeddedQuestion = form.getAttribute('data-omo-decision-embedded-question') === '1';
                    const submitButton = form.querySelector('[data-omo-decision-consent-submit]')
                        || (form.id !== '' ? document.querySelector('[data-omo-decision-editor-submit][form="' + form.id + '"]') : null);
                    const feedbackNode = form.querySelector('[data-omo-decision-consent-feedback]');
                    const proposalList = form.querySelector('[data-omo-decision-consent-proposal-list]');
                    const proposalAddButton = form.querySelector('[data-omo-decision-consent-proposal-add]');
                    const settingsOpenButton = form.querySelector('[data-omo-decision-consent-settings-open]');
                    const invitationOpenButton = form.querySelector('[data-omo-decision-invitations-open]');
                    const invitationSendOpenButton = form.querySelector('[data-omo-decision-invitations-send-open]');
                    const settingsTemplate = form.querySelector('[data-omo-decision-consent-settings-template]');
                    const hiddenAnonymousInput = form.querySelector('[data-omo-decision-consent-hidden-anonymous]');
                    const hiddenAllowAnonymousVotesInput = form.querySelector('[data-omo-decision-consent-hidden-allow-anonymous-votes]');
                    const hiddenConsultationInput = form.querySelector('[data-omo-decision-consent-hidden-consultation-proposals]');
                    const hiddenProposalDiscussionsInput = form.querySelector('[data-omo-decision-consent-hidden-proposal-discussions]');
                    const hiddenLiveResultsInput = form.querySelector('[data-omo-decision-consent-hidden-live-results]');
                    const hiddenRandomOrderInput = form.querySelector('[data-omo-decision-consent-hidden-random-order]');
                    const hiddenOneProposalAtATimeInput = form.querySelector('[data-omo-decision-consent-hidden-one-proposal-at-a-time]');
                    const hiddenProposalContentTitleInput = form.querySelector('[data-omo-decision-proposal-content-hidden-title]');
                    const hiddenProposalContentDescriptionInput = form.querySelector('[data-omo-decision-proposal-content-hidden-description]');
                    const hiddenProposalContentUrlInput = form.querySelector('[data-omo-decision-proposal-content-hidden-url]');
                    const hiddenVoteWeightEnabledInput = form.querySelector('[data-omo-decision-consent-hidden-vote-weight-enabled]');
                    const hiddenVoteWeightQuestionInput = form.querySelector('[data-omo-decision-consent-hidden-vote-weight-question]');
                    const hiddenVoteWeightOptionsInput = form.querySelector('[data-omo-decision-consent-hidden-vote-weight-options]');
                    const anonymousSummary = form.querySelector('[data-omo-decision-consent-anonymous-summary]');
                    const allowAnonymousVotesSummary = form.querySelector('[data-omo-decision-consent-allow-anonymous-votes-summary]');
                    const allowAnonymousVotesStat = form.querySelector('[data-omo-decision-consent-allow-anonymous-votes-stat]');
                    const consultationSummary = form.querySelector('[data-omo-decision-consent-consultation-summary]');
                        const discussionsSummary = form.querySelector('[data-omo-decision-consent-discussions-summary]');
                        const liveResultsSummary = form.querySelector('[data-omo-decision-consent-live-results-summary]');
                        const randomOrderSummary = form.querySelector('[data-omo-decision-consent-random-order-summary]');
                        const oneProposalAtATimeSummary = form.querySelector('[data-omo-decision-consent-one-proposal-at-a-time-summary]');
                        const proposalContentSummary = form.querySelector('[data-omo-decision-consent-proposal-content-summary]');
                        const voteWeightSummary = form.querySelector('[data-omo-decision-consent-vote-weight-summary]');
                    if (!payloadNode || !proposalList) {
                        return;
                    }

                    if (typeof window.omoDecisionInitInvitationEditors === 'function') {
                        window.omoDecisionInitInvitationEditors(form);
                    }

                    let payload = {};
                    try {
                        payload = JSON.parse(payloadNode.textContent || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    const setFeedback = function (message, isError) {
                        const text = String(message || '');
                        if (isError
                            && text !== ''
                            && typeof window.omoDecisionNotifyError === 'function'
                            && window.omoDecisionNotifyError(text)) {
                            if (feedbackNode) {
                                feedbackNode.textContent = '';
                                feedbackNode.classList.remove('is-error', 'is-success');
                            }
                            return;
                        }

                        if (!feedbackNode) {
                            return;
                        }
                        feedbackNode.textContent = text;
                        feedbackNode.classList.toggle('is-error', !!isError);
                        feedbackNode.classList.toggle('is-success', !isError && message !== '');
                    };

                    const normalizeVoteWeightNumber = function (rawValue) {
                        const normalized = String(rawValue || '').trim().replace(',', '.');
                        if (normalized === '') {
                            return '';
                        }

                        const value = Number(normalized);
                        if (!Number.isFinite(value) || value <= 0) {
                            return '';
                        }

                        return String(value).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
                    };

                    const parseVoteWeightOptions = function (rawValue, fallbackToDefault) {
                        let options = [];

                        if (Array.isArray(rawValue)) {
                            options = rawValue;
                        } else {
                            const source = String(rawValue || '').trim();
                            if (source !== '') {
                                try {
                                    const decoded = JSON.parse(source);
                                    if (Array.isArray(decoded)) {
                                        options = decoded;
                                    }
                                } catch (error) {
                                    options = source.split(/\r\n|\r|\n/).map(function (line) {
                                        const parts = String(line || '').split('|');
                                        return {
                                            weight: parts.length > 0 ? parts[0] : '',
                                            label: parts.length > 1 ? parts.slice(1).join('|') : '',
                                        };
                                    });
                                }
                            }
                        }

                        const normalizedOptions = [];
                        options.forEach(function (option) {
                            if (!option || typeof option !== 'object') {
                                return;
                            }

                            const weight = normalizeVoteWeightNumber(option.weight || option.value || '');
                            const label = String(option.label || '').trim();
                            if (weight === '' || label === '') {
                                return;
                            }

                            normalizedOptions.push({
                                weight: weight,
                                label: label,
                            });
                        });

                        if (normalizedOptions.length === 0 && fallbackToDefault && hiddenVoteWeightOptionsInput) {
                            return parseVoteWeightOptions(
                                hiddenVoteWeightOptionsInput.getAttribute('data-default-options-json') || '[]',
                                false
                            );
                        }

                        return normalizedOptions;
                    };

                    const buildVoteWeightOptionsText = function (options) {
                        return options.map(function (option) {
                            return String(option.weight || '') + ' | ' + String(option.label || '');
                        }).join('\n');
                    };

                    const buildVoteWeightSummaryText = function () {
                        if (!voteWeightSummary) {
                            return '';
                        }

                        const yesLabel = String(voteWeightSummary.getAttribute('data-yes-label') || 'Oui');
                        const noLabel = String(voteWeightSummary.getAttribute('data-no-label') || 'Non');
                        if (!hiddenVoteWeightEnabledInput || !hiddenVoteWeightEnabledInput.value) {
                            return noLabel;
                        }

                        const options = parseVoteWeightOptions(
                            hiddenVoteWeightOptionsInput ? hiddenVoteWeightOptionsInput.value : '[]',
                            true
                        );
                        if (options.length === 0) {
                            return yesLabel;
                        }

                        const weights = options.map(function (option) {
                            return Number(String(option.weight || '').replace(',', '.'));
                        }).filter(function (value) {
                            return Number.isFinite(value) && value > 0;
                        });
                        if (weights.length === 0) {
                            return yesLabel;
                        }

                        return yesLabel + ' (' + String(options.length) + ' options de ' + normalizeVoteWeightNumber(String(Math.min.apply(Math, weights))) + ' a ' + normalizeVoteWeightNumber(String(Math.max.apply(Math, weights))) + ')';
                    };

                    const syncSettingsSummary = function () {
                        const yesLabel = String(payload.texts && payload.texts.yesLabel ? payload.texts.yesLabel : 'Oui');
                        const noLabel = String(payload.texts && payload.texts.noLabel ? payload.texts.noLabel : 'Non');
                        if (anonymousSummary) {
                            anonymousSummary.textContent = hiddenAnonymousInput && hiddenAnonymousInput.value ? noLabel : yesLabel;
                        }
                        if (allowAnonymousVotesStat) {
                            allowAnonymousVotesStat.hidden = !!(hiddenAnonymousInput && hiddenAnonymousInput.value);
                        }
                        if (allowAnonymousVotesSummary) {
                            allowAnonymousVotesSummary.textContent = hiddenAllowAnonymousVotesInput && hiddenAllowAnonymousVotesInput.value ? yesLabel : noLabel;
                        }
                        if (consultationSummary) {
                            consultationSummary.textContent = hiddenConsultationInput && hiddenConsultationInput.value ? yesLabel : noLabel;
                        }
                        if (discussionsSummary) {
                            discussionsSummary.textContent = hiddenProposalDiscussionsInput && hiddenProposalDiscussionsInput.value ? yesLabel : noLabel;
                        }
                        if (liveResultsSummary) {
                            liveResultsSummary.textContent = !hiddenLiveResultsInput || !hiddenLiveResultsInput.value
                                ? String(liveResultsSummary.getAttribute('data-no-label') || noLabel)
                                : (hiddenAnonymousInput && hiddenAnonymousInput.value
                                    ? String(liveResultsSummary.getAttribute('data-anonymous-label') || yesLabel)
                                    : String(liveResultsSummary.getAttribute('data-named-label') || yesLabel));
                        }
                        if (randomOrderSummary) {
                            randomOrderSummary.textContent = hiddenRandomOrderInput && hiddenRandomOrderInput.value ? yesLabel : noLabel;
                        }
                        if (oneProposalAtATimeSummary) {
                            oneProposalAtATimeSummary.textContent = hiddenOneProposalAtATimeInput && hiddenOneProposalAtATimeInput.value ? yesLabel : noLabel;
                        }
                        if (proposalContentSummary) {
                            const labels = [];
                            if (hiddenProposalContentTitleInput && hiddenProposalContentTitleInput.value) {
                                labels.push(String(proposalContentSummary.getAttribute('data-title-label') || 'Titre'));
                            }
                            if (hiddenProposalContentDescriptionInput && hiddenProposalContentDescriptionInput.value) {
                                labels.push(String(proposalContentSummary.getAttribute('data-description-label') || 'Description'));
                            }
                            if (hiddenProposalContentUrlInput && hiddenProposalContentUrlInput.value) {
                                labels.push(String(proposalContentSummary.getAttribute('data-url-label') || 'URL'));
                            }
                            proposalContentSummary.textContent = labels.join(', ');
                        }
                        if (voteWeightSummary) {
                            voteWeightSummary.textContent = buildVoteWeightSummaryText();
                        }
                    };

                    const refreshProposalLabels = function () {
                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]'), function (card, index) {
                            const label = card.querySelector('[data-omo-decision-consent-proposal-label]');
                            if (label) {
                                const template = payload.texts && payload.texts.proposalItemTemplate
                                    ? payload.texts.proposalItemTemplate
                                    : 'Proposition __INDEX__';
                                label.textContent = String(template).replace('__INDEX__', String(index + 1));
                            }
                        });
                    };

                    const closeProposalMenus = function (exceptCard) {
                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]'), function (menuCard) {
                            if (exceptCard && menuCard === exceptCard) {
                                return;
                            }

                            const menuPanel = menuCard.querySelector('[data-omo-decision-consent-proposal-menu-panel]');
                            const menuToggle = menuCard.querySelector('[data-omo-decision-consent-proposal-menu-toggle]');
                            if (menuPanel) {
                                menuPanel.hidden = true;
                            }
                            if (menuToggle) {
                                menuToggle.setAttribute('aria-expanded', 'false');
                            }
                        });
                    };

                    const bindProposalCard = function (card) {
                        if (!card || card.dataset.omoDecisionConsentProposalReady === '1') {
                            return;
                        }

                        const titleInput = card.querySelector('input[name="proposals[]"]');
                        const descriptionInput = card.querySelector('[data-omo-decision-consent-proposal-description]');
                        const descriptionEditor = card.querySelector('[data-omo-decision-consent-proposal-description-editor]');
                        const infoUrlInput = card.querySelector('[data-omo-decision-consent-proposal-info-url]');
                        const detailsButton = card.querySelector('[data-omo-decision-consent-proposal-settings]');

                        if (payload.proposalContent && !payload.proposalContent.title && titleInput) {
                            titleInput.type = 'hidden';
                        }
                        if (payload.proposalContent && !payload.proposalContent.title && payload.proposalContent.description && descriptionEditor && window.omoProposalHtml && typeof window.omoProposalHtml.mount === 'function') {
                            window.omoProposalHtml.mount(descriptionEditor, {
                                value: descriptionInput ? String(descriptionInput.value || '') : '',
                                disabled: payload.proposalEditable !== true,
                            });
                        }
                        if (payload.proposalContent && !payload.proposalContent.url && detailsButton) {
                            detailsButton.remove();
                        }
                        const removeButton = card.querySelector('[data-omo-decision-consent-proposal-remove]');
                        const menuToggle = card.querySelector('[data-omo-decision-consent-proposal-menu-toggle]');
                        const menuPanel = card.querySelector('[data-omo-decision-consent-proposal-menu-panel]');

                        if (menuToggle && menuPanel) {
                            menuToggle.addEventListener('click', function (event) {
                                event.preventDefault();
                                event.stopPropagation();

                                const shouldOpen = menuPanel.hidden;
                                closeProposalMenus(card);
                                menuPanel.hidden = !shouldOpen;
                                menuToggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                            });

                            menuPanel.addEventListener('click', function (event) {
                                event.stopPropagation();
                            });
                        }

                        if (detailsButton) {
                            detailsButton.addEventListener('click', function () {
                                closeProposalMenus();
                                if (detailsButton.disabled || typeof window.commonTopbarOpenModal !== 'function') {
                                    return;
                                }

                                const proposalLabelNode = card.querySelector('[data-omo-decision-consent-proposal-label]');
                                const modalTitle = proposalLabelNode
                                    ? String(proposalLabelNode.textContent || '').trim()
                                    : String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');
                                const proposalContent = payload.proposalContent || {title: true, description: true};
                                const descriptionDetailsField = proposalContent.title && proposalContent.description
                                    ? '  <label style="display:grid;gap:6px;">'
                                        + '    <span class="generic-form-label">' + String(payload.texts && payload.texts.proposalDescriptionLabel ? payload.texts.proposalDescriptionLabel : 'Description') + '</span>'
                                        + '    <div data-omo-proposal-html-field><div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-decision-consent-proposal-modal-description></div><textarea hidden aria-hidden="true" data-omo-proposal-html-value></textarea></div>'
                                        + '  </label>'
                                    : '';
                                const modalHtml = ''
                                    + '<div class="generic-section generic-section--stack" style="display:grid;gap:12px;">'
                                    + descriptionDetailsField
                                    + '  <label style="display:grid;gap:6px;">'
                                    + '    <span class="generic-form-label">' + String(payload.texts && payload.texts.proposalInfoUrlLabel ? payload.texts.proposalInfoUrlLabel : 'URL') + '</span>'
                                    + '    <input type="url" class="generic-form-control generic-form-control--compact" data-omo-decision-consent-proposal-modal-info-url placeholder="' + String(payload.texts && payload.texts.proposalInfoUrlPlaceholder ? payload.texts.proposalInfoUrlPlaceholder : 'https://...') + '">'
                                    + '  </label>'
                                    + '  <div style="display:flex;justify-content:flex-end;gap:8px;">'
                                    + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-consent-proposal-modal-cancel>Fermer</button>'
                                    + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-consent-proposal-modal-apply>' + String(payload.texts && payload.texts.proposalApply ? payload.texts.proposalApply : 'Enregistrer') + '</button>'
                                    + '  </div>'
                                    + '</div>';

                                window.commonTopbarOpenModal(modalTitle || 'Details', modalHtml, 'html');
                                const modalBody = document.getElementById('commonTopbarModalBody');
                                if (!modalBody) {
                                    return;
                                }

                                const modalDescription = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-description]');
                                const modalInfoUrl = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-info-url]');
                                const modalCancel = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-cancel]');
                                const modalApply = modalBody.querySelector('[data-omo-decision-consent-proposal-modal-apply]');
                                if (modalDescription && window.omoProposalHtml && typeof window.omoProposalHtml.mount === 'function') {
                                    window.omoProposalHtml.mount(modalDescription, {value: descriptionInput ? String(descriptionInput.value || '') : ''});
                                }
                                if (modalDescription) {
                                    modalDescription.setAttribute('data-omo-proposal-html-initial', descriptionInput ? String(descriptionInput.value || '') : '');
                                }
                                if (modalInfoUrl) {
                                    modalInfoUrl.value = infoUrlInput ? String(infoUrlInput.value || '') : '';
                                }
                                if (modalCancel) {
                                    modalCancel.addEventListener('click', function () {
                                        if (typeof window.commonTopbarCloseModal === 'function') {
                                            window.commonTopbarCloseModal();
                                        }
                                    });
                                }
                                if (modalApply) {
                                    modalApply.addEventListener('click', function () {
                                        if (descriptionInput && modalDescription && window.omoProposalHtml && typeof window.omoProposalHtml.getValue === 'function') {
                                            descriptionInput.value = String(window.omoProposalHtml.getValue(modalDescription) || '').trim();
                                        }
                                        if (infoUrlInput && modalInfoUrl) {
                                            infoUrlInput.value = String(modalInfoUrl.value || '').trim();
                                        }
                                        if (typeof window.commonTopbarCloseModal === 'function') {
                                            window.commonTopbarCloseModal();
                                        }
                                        if (titleInput) {
                                            titleInput.focus();
                                        }
                                    });
                                }
                            });
                        }

                        if (removeButton) {
                            removeButton.addEventListener('click', function () {
                                closeProposalMenus();
                                const cards = proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]');
                                if (cards.length <= 1) {
                                    if (titleInput) {
                                        titleInput.value = '';
                                        titleInput.focus();
                                    }
                                    if (descriptionInput) {
                                        descriptionInput.value = '';
                                    }
                                    if (infoUrlInput) {
                                        infoUrlInput.value = '';
                                    }
                                    return;
                                }

                                card.remove();
                                refreshProposalLabels();
                            });
                        }

                        card.dataset.omoDecisionConsentProposalReady = '1';
                    };

                    let sortable = null;

                    const createProposalCard = function (value) {
                        const card = document.createElement('div');
                        card.className = 'omo-decision-consent__proposal-card omo-decision-proposal-card generic-section';
                        card.setAttribute('data-omo-decision-consent-proposal-card', '');

                        const dragButton = document.createElement('button');
                        dragButton.type = 'button';
                        dragButton.className = 'omo-decision-consent__proposal-drag generic-drag-handle generic-drag-handle--stretch';
                        dragButton.setAttribute('data-omo-decision-consent-proposal-drag', '');
                        dragButton.textContent = '⋮⋮';
                        dragButton.setAttribute('aria-label', String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Reordonner'));

                        const field = document.createElement('div');
                        field.className = 'omo-decision-consent__proposal-field';

                        const label = document.createElement('span');
                        label.className = 'generic-card-title generic-card-title--small';
                        label.setAttribute('data-omo-decision-consent-proposal-label', '');

                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'generic-form-control generic-form-control--compact';
                        input.name = 'proposals[]';
                        input.value = String(value || '');
                        input.placeholder = String(payload.texts && payload.texts.proposalPlaceholder ? payload.texts.proposalPlaceholder : '');

                        const descriptionInput = document.createElement('textarea');
                        descriptionInput.hidden = true;
                        descriptionInput.setAttribute('aria-hidden', 'true');
                        descriptionInput.name = 'proposal_descriptions[]';
                        descriptionInput.value = '';
                        descriptionInput.setAttribute('data-omo-decision-consent-proposal-description', '');

                        let descriptionField = descriptionInput;
                        if (payload.proposalContent && !payload.proposalContent.title && payload.proposalContent.description) {
                            const descriptionWrapper = document.createElement('div');
                            descriptionWrapper.setAttribute('data-omo-proposal-html-field', '');
                            const descriptionEditor = document.createElement('div');
                            descriptionEditor.className = 'omo-proposal-html-editor';
                            descriptionEditor.setAttribute('data-omo-proposal-html-editor', '');
                            descriptionEditor.setAttribute('data-omo-decision-consent-proposal-description-editor', '');
                            if (payload.proposalEditable !== true) {
                                descriptionEditor.setAttribute('data-omo-proposal-html-disabled', '1');
                            }
                            descriptionWrapper.appendChild(descriptionEditor);
                            descriptionWrapper.appendChild(descriptionInput);
                            descriptionField = descriptionWrapper;
                        }

                        const infoUrlInput = document.createElement('input');
                        infoUrlInput.type = 'hidden';
                        infoUrlInput.name = 'proposal_info_urls[]';
                        infoUrlInput.value = '';
                        infoUrlInput.setAttribute('data-omo-decision-consent-proposal-info-url', '');

                        const proposalIdInput = document.createElement('input');
                        proposalIdInput.type = 'hidden';
                        proposalIdInput.name = 'proposal_ids[]';
                        proposalIdInput.value = '0';

                        const menu = document.createElement('div');
                        menu.className = 'omo-decision-consent__proposal-menu';
                        menu.setAttribute('data-omo-decision-consent-proposal-menu', '');

                        const menuToggle = document.createElement('button');
                        menuToggle.type = 'button';
                        menuToggle.className = 'generic-action-button generic-action-button--secondary omo-decision-consent__proposal-menu-toggle';
                        menuToggle.setAttribute('data-omo-decision-consent-proposal-menu-toggle', '');
                        menuToggle.setAttribute('aria-haspopup', 'menu');
                        menuToggle.setAttribute('aria-expanded', 'false');
                        menuToggle.setAttribute('aria-label', 'Actions');
                        menuToggle.textContent = '...';

                        const menuPanel = document.createElement('div');
                        menuPanel.className = 'omo-decision-consent__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel';
                        menuPanel.setAttribute('data-omo-decision-consent-proposal-menu-panel', '');
                        menuPanel.setAttribute('role', 'menu');
                        menuPanel.hidden = true;

                        const detailsButton = document.createElement('button');
                        detailsButton.type = 'button';
                        detailsButton.className = 'generic-action-button generic-action-button--secondary omo-decision-consent__proposal-menu-item';
                        detailsButton.setAttribute('data-omo-decision-consent-proposal-settings', '');
                        detailsButton.setAttribute('role', 'menuitem');
                        detailsButton.textContent = String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');

                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.className = 'generic-action-button generic-action-button--danger omo-decision-consent__proposal-menu-item';
                        removeButton.setAttribute('data-omo-decision-consent-proposal-remove', '');
                        removeButton.setAttribute('role', 'menuitem');
                        removeButton.textContent = String(payload.texts && payload.texts.proposalRemove ? payload.texts.proposalRemove : 'Supprimer');

                        menuPanel.appendChild(detailsButton);
                        menuPanel.appendChild(removeButton);
                        menu.appendChild(menuToggle);
                        menu.appendChild(menuPanel);

                        field.appendChild(label);
                        field.appendChild(input);
                        field.appendChild(descriptionField);
                        field.appendChild(infoUrlInput);
                        field.appendChild(proposalIdInput);
                        card.appendChild(dragButton);
                        card.appendChild(field);
                        card.appendChild(menu);

                        bindProposalCard(card);
                        if (sortable && typeof sortable.bindItem === 'function') {
                            sortable.bindItem(card);
                        }
                        return card;
                    };

                    const openSettingsModal = function () {
                        if (!settingsTemplate || typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        const modalTitle = settingsOpenButton
                            ? String(settingsOpenButton.getAttribute('data-omo-decision-consent-settings-title') || settingsOpenButton.textContent || 'Paramètres du scrutin')
                            : 'Paramètres du scrutin';
                        window.commonTopbarOpenModal(modalTitle, settingsTemplate.innerHTML, 'html');
                        const modalBody = document.getElementById('commonTopbarModalBody');
                        if (!modalBody) {
                            return;
                        }

                        const popupAnonymous = modalBody.querySelector('[data-omo-decision-consent-popup-anonymous]');
                        const popupAllowAnonymousVotes = modalBody.querySelector('[data-omo-decision-consent-popup-allow-anonymous-votes]');
                        const popupAllowAnonymousVotesOption = modalBody.querySelector('[data-omo-decision-consent-popup-allow-anonymous-votes-option]');
                        const popupConsultation = modalBody.querySelector('[data-omo-decision-consent-popup-consultation-proposals]');
                        const popupProposalDiscussions = modalBody.querySelector('[data-omo-decision-consent-popup-proposal-discussions]');
                        const popupOwnerIntermediateResults = modalBody.querySelector('[data-omo-decision-consent-popup-owner-intermediate-results]');
                        const popupParticipantIntermediateResults = modalBody.querySelector('[data-omo-decision-consent-popup-participant-intermediate-results]');
                        const popupParticipantResponsesEditable = modalBody.querySelector('[data-omo-decision-consent-popup-participant-responses-editable]');
                        const popupRandomOrder = modalBody.querySelector('[data-omo-decision-consent-popup-random-order]');
                        const popupOneProposalAtATime = modalBody.querySelector('[data-omo-decision-consent-popup-one-proposal-at-a-time]');
                        const popupProposalContentTitle = modalBody.querySelector('[data-omo-decision-proposal-content-popup-title]');
                        const popupProposalContentDescription = modalBody.querySelector('[data-omo-decision-proposal-content-popup-description]');
                        const popupProposalContentUrl = modalBody.querySelector('[data-omo-decision-proposal-content-popup-url]');
                        const popupVoteWeightRoot = modalBody.querySelector('[data-omo-decision-vote-weight-editor]');
                        const popupCancel = modalBody.querySelector('[data-omo-decision-consent-popup-cancel]');
                        const popupApply = modalBody.querySelector('[data-omo-decision-consent-popup-apply]');
                        const popupVoteWeightEditor = popupVoteWeightRoot && typeof window.omoDecisionInitVoteWeightEditor === 'function'
                            ? window.omoDecisionInitVoteWeightEditor(popupVoteWeightRoot)
                            : null;
                        if (!popupAnonymous || !popupAllowAnonymousVotes || !popupAllowAnonymousVotesOption || !popupConsultation || !popupProposalDiscussions || !popupOwnerIntermediateResults || !popupParticipantIntermediateResults || !popupParticipantResponsesEditable || !popupRandomOrder || !popupOneProposalAtATime || !popupProposalContentTitle || !popupProposalContentDescription || !popupProposalContentUrl || !popupVoteWeightEditor || !popupApply) {
                            return;
                        }

                        popupAnonymous.checked = !(hiddenAnonymousInput && hiddenAnonymousInput.value);
                        popupAllowAnonymousVotes.checked = !!(hiddenAllowAnonymousVotesInput && hiddenAllowAnonymousVotesInput.value);
                        if (typeof window.omoDecisionBindIndividualAnonymousVoteOption === 'function') {
                            window.omoDecisionBindIndividualAnonymousVoteOption(popupAnonymous, popupAllowAnonymousVotes, popupAllowAnonymousVotesOption);
                        }
                        popupConsultation.checked = !!(hiddenConsultationInput && hiddenConsultationInput.value);
                        popupProposalDiscussions.checked = !!(hiddenProposalDiscussionsInput && hiddenProposalDiscussionsInput.value);
                        popupRandomOrder.checked = !!(hiddenRandomOrderInput && hiddenRandomOrderInput.value);
                        popupOneProposalAtATime.checked = !!(hiddenOneProposalAtATimeInput && hiddenOneProposalAtATimeInput.value);
                        popupProposalContentTitle.checked = !!(hiddenProposalContentTitleInput && hiddenProposalContentTitleInput.value);
                        popupProposalContentDescription.checked = !!(hiddenProposalContentDescriptionInput && hiddenProposalContentDescriptionInput.value);
                        popupProposalContentUrl.checked = !!(hiddenProposalContentUrlInput && hiddenProposalContentUrlInput.value);
                        popupVoteWeightEditor.setState({
                            enabled: !!(hiddenVoteWeightEnabledInput && hiddenVoteWeightEnabledInput.value),
                            question: hiddenVoteWeightQuestionInput ? String(hiddenVoteWeightQuestionInput.value || '') : '',
                            options: parseVoteWeightOptions(hiddenVoteWeightOptionsInput ? hiddenVoteWeightOptionsInput.value : '[]', false),
                        });

                        if (popupCancel) {
                            popupCancel.addEventListener('click', function () {
                                if (typeof window.commonTopbarCloseModal === 'function') {
                                    window.commonTopbarCloseModal();
                                }
                            });
                        }

                        popupApply.addEventListener('click', function () {
                            if (hiddenAnonymousInput) {
                                hiddenAnonymousInput.value = popupAnonymous.checked ? '' : '1';
                            }
                            if (hiddenAllowAnonymousVotesInput) {
                                hiddenAllowAnonymousVotesInput.value = popupAllowAnonymousVotes.checked ? '1' : '';
                            }
                            if (hiddenConsultationInput) {
                                hiddenConsultationInput.value = popupConsultation.checked ? '1' : '';
                            }
                            if (hiddenProposalDiscussionsInput) {
                                hiddenProposalDiscussionsInput.value = popupProposalDiscussions.checked ? '1' : '';
                            }
                            const settingsForm = form.closest('[data-omo-decision-multi-editor]')
                                ? form.closest('[data-omo-decision-multi-editor]').querySelector('[data-omo-decision-process-form]')
                                : form;
                            if (!embeddedQuestion) {
                                ['owner_intermediate_results_access', 'participant_intermediate_results_access', 'participant_responses_editable'].forEach(function (name, index) {
                                    const value = [popupOwnerIntermediateResults.checked, popupParticipantIntermediateResults.checked, popupParticipantResponsesEditable.checked][index];
                                    settingsForm.querySelectorAll('[name="' + name + '"]').forEach(function (input) {
                                        if (input.type === 'checkbox') {
                                            input.checked = value;
                                        } else {
                                            input.value = value ? '1' : '0';
                                        }
                                    });
                                });
                            }
                            if (hiddenRandomOrderInput) {
                                hiddenRandomOrderInput.value = popupRandomOrder.checked ? '1' : '';
                            }
                            if (hiddenOneProposalAtATimeInput) {
                                hiddenOneProposalAtATimeInput.value = popupOneProposalAtATime.checked ? '1' : '';
                            }
                            if (hiddenProposalContentTitleInput) {
                                hiddenProposalContentTitleInput.value = popupProposalContentTitle.checked ? '1' : '';
                            }
                            if (hiddenProposalContentDescriptionInput) {
                                hiddenProposalContentDescriptionInput.value = popupProposalContentDescription.checked ? '1' : '';
                            }
                            if (hiddenProposalContentUrlInput) {
                                hiddenProposalContentUrlInput.value = popupProposalContentUrl.checked ? '1' : '';
                            }
                            payload.proposalContent = {
                                title: popupProposalContentTitle.checked,
                                description: popupProposalContentDescription.checked,
                                url: popupProposalContentUrl.checked,
                            };
                            if (window.omoProposalHtml && typeof window.omoProposalHtml.refreshDecisionProposalCards === 'function') {
                                window.omoProposalHtml.refreshDecisionProposalCards(proposalList, payload.proposalContent, {
                                    descriptionSelector: '[data-omo-decision-consent-proposal-description]',
                                    detailsSelector: '[data-omo-decision-consent-proposal-settings]',
                                    canEdit: payload.proposalEditable === true,
                                });
                            }
                            const popupVoteWeightState = popupVoteWeightEditor.getState();
                            if (hiddenVoteWeightEnabledInput) {
                                hiddenVoteWeightEnabledInput.value = popupVoteWeightState.enabled ? '1' : '';
                            }
                            if (hiddenVoteWeightQuestionInput) {
                                hiddenVoteWeightQuestionInput.value = String(popupVoteWeightState.question || '').trim();
                            }
                            if (hiddenVoteWeightOptionsInput) {
                                hiddenVoteWeightOptionsInput.value = JSON.stringify(Array.isArray(popupVoteWeightState.options) ? popupVoteWeightState.options : []);
                            }
                            syncSettingsSummary();
                            if (typeof window.commonTopbarCloseModal === 'function') {
                                window.commonTopbarCloseModal();
                            }
                        });
                    };

                    const openInvitationModal = function () {
                        if (!invitationOpenButton || typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        let invitationUrl = String(invitationOpenButton.getAttribute('data-omo-decision-invitations-url') || '');
                        if (invitationUrl === '') {
                            return;
                        }
                        if (invitationOpenButton.getAttribute('data-omo-decision-invitations-draft') === '1' && form.id !== '') {
                            window.omoDecisionInvitationDraftTargetForm = form;
                            const draftUrl = new URL(invitationUrl, window.location.origin);
                            draftUrl.searchParams.set('draft_form_id', form.id);
                            invitationUrl = draftUrl.toString();
                        }

                        const invitationTitle = String(
                            invitationOpenButton.getAttribute('data-omo-decision-invitations-title')
                            || invitationOpenButton.textContent
                            || 'Inviter des participants'
                        );

                        window.commonTopbarOpenModal(invitationTitle, invitationUrl, 'fetch');
                    };

                    const openInvitationSendModal = function () {
                        if (!invitationSendOpenButton || typeof window.commonTopbarOpenModal !== 'function') {
                            return;
                        }

                        const invitationUrl = String(invitationSendOpenButton.getAttribute('data-omo-decision-invitations-send-url') || '');
                        if (invitationUrl === '') {
                            return;
                        }

                        const invitationTitle = String(
                            invitationSendOpenButton.getAttribute('data-omo-decision-invitations-send-title')
                            || invitationSendOpenButton.textContent
                            || 'Envoyer les invitations'
                        );

                        window.commonTopbarOpenModal(invitationTitle, invitationUrl, 'fetch');
                    };

                    if (settingsOpenButton) {
                        settingsOpenButton.addEventListener('click', openSettingsModal);
                    }

                    if (invitationOpenButton) {
                        invitationOpenButton.addEventListener('click', openInvitationModal);
                    }

                    if (invitationSendOpenButton) {
                        invitationSendOpenButton.addEventListener('click', openInvitationSendModal);
                    }

                    if (payload.proposalEditable && typeof window.commonCreateVerticalSortableList === 'function') {
                        sortable = window.commonCreateVerticalSortableList({
                            list: proposalList,
                            itemSelector: '[data-omo-decision-consent-proposal-card]',
                            handleSelector: '[data-omo-decision-consent-proposal-drag]',
                            draggingClass: 'is-dragging',
                            dropTargetClass: 'is-drop-target',
                            placeholderClass: 'omo-decision-consent__proposal-placeholder',
                            createPlaceholder: function (card) {
                                const placeholder = document.createElement('div');
                                placeholder.className = 'omo-decision-consent__proposal-placeholder';
                                placeholder.style.height = Math.max(Number(card.getBoundingClientRect().height) || 0, 78) + 'px';
                                return placeholder;
                            },
                            onDragEnd: refreshProposalLabels,
                            onDrop: refreshProposalLabels
                        });
                    }

                    if (proposalAddButton && !proposalAddButton.disabled) {
                        proposalAddButton.addEventListener('click', function () {
                            const newCard = createProposalCard('');
                            proposalList.appendChild(newCard);
                            refreshProposalLabels();
                            const input = newCard.querySelector('input[name="proposals[]"]');
                            if (input) {
                                input.focus();
                            }
                        });
                    }

                    document.addEventListener('click', function (event) {
                        if (!proposalList.contains(event.target)) {
                            closeProposalMenus();
                        }
                    });

                    Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-consent-proposal-card]'), bindProposalCard);
                    refreshProposalLabels();
                    syncSettingsSummary();

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        if (!payload.saveUrl) {
                            return;
                        }

                        const formData = new FormData(form);
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.dataset.originalText = submitButton.textContent;
                            submitButton.textContent = String(payload.texts && payload.texts.saving ? payload.texts.saving : 'Enregistrement...');
                        }
                        setFeedback('', false);

                        fetch(payload.saveUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                            .then(function (response) { return response.json(); })
                            .then(function (result) {
                                if (!result || !result.status) {
                                    throw new Error(result && result.message ? result.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'));
                                }
                                setFeedback(result.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);
                                if (result.redirectUrl) {
                                    if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                        window.omoDecisionOpenNestedDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, '');
                                    } else if (typeof window.commonTopbarOpenDrawer === 'function') {
                                        window.commonTopbarOpenDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, 'fetch');
                                    } else {
                                        window.location.href = result.redirectUrl;
                                    }
                                }
                            })
                            .catch(function (error) {
                                setFeedback(error && error.message ? error.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'), true);
                            })
                            .finally(function () {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = submitButton.dataset.originalText || submitButton.textContent;
                                }
                            });
                    });

                    form.dataset.omoDecisionConsentReady = '1';
                });

                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-consent-response-form]'), function (form) {
                    if (form.dataset.omoDecisionConsentResponseReady === '1') {
                        return;
                    }

                    const payloadNode = form.querySelector('[data-omo-decision-consent-response-data]');
                    const submitButton = form.querySelector('[data-omo-decision-consent-response-submit]');
                    const feedbackNode = form.querySelector('[data-omo-decision-consent-response-feedback]');
                    if (!payloadNode) {
                        return;
                    }

                    let payload = {};
                    try {
                        payload = JSON.parse(payloadNode.textContent || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    const setFeedback = function (message, isError) {
                        if (!feedbackNode) {
                            return;
                        }
                        feedbackNode.textContent = String(message || '');
                        feedbackNode.classList.toggle('is-error', !!isError);
                        feedbackNode.classList.toggle('is-success', !isError && message !== '');
                    };

                    const syncChoiceState = function () {
                        Array.prototype.forEach.call(form.querySelectorAll('.omo-decision-consent__choice-option'), function (choiceOption) {
                            const radio = choiceOption.querySelector('input[type="radio"]');
                            const trigger = choiceOption.querySelector('[data-omo-decision-consent-choice-trigger]');
                            const isSelected = !!(radio && radio.checked);
                            choiceOption.classList.toggle('is-selected', isSelected);
                            if (trigger) {
                                trigger.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                            }
                        });
                    };

                    form.addEventListener('click', function (event) {
                        const trigger = event.target.closest('[data-omo-decision-consent-choice-trigger]');
                        if (!trigger || !form.contains(trigger)) {
                            return;
                        }

                        event.preventDefault();
                        event.stopPropagation();

                        const choiceOption = trigger.closest('.omo-decision-consent__choice-option');
                        const radio = choiceOption ? choiceOption.querySelector('input[type="radio"]') : null;
                        if (!radio || radio.disabled) {
                            return;
                        }

                        if (!radio.checked) {
                            radio.checked = true;
                            radio.dispatchEvent(new Event('change', { bubbles: true }));
                        } else {
                            syncChoiceState();
                        }
                    });

                    form.addEventListener('change', function (event) {
                        if (!event.target.matches('.omo-decision-consent__choice-input')) {
                            return;
                        }

                        syncChoiceState();
                    });

                    syncChoiceState();

                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        if (!payload.saveUrl) {
                            return;
                        }

                        const formData = new FormData(form);
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.dataset.originalText = submitButton.textContent;
                            submitButton.textContent = String(payload.texts && payload.texts.saving ? payload.texts.saving : 'Enregistrement...');
                        }
                        setFeedback('', false);

                        fetch(payload.saveUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                            .then(function (response) { return response.json(); })
                            .then(function (result) {
                                if (!result || !result.status) {
                                    throw new Error(result && result.message ? result.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'));
                                }
                                setFeedback(result.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);
                                if (result.redirectUrl) {
                                    if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                        window.omoDecisionOpenNestedDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, '');
                                    } else if (typeof window.commonTopbarOpenDrawer === 'function') {
                                        window.commonTopbarOpenDrawer(result.drawerTitle || payload.drawerTitle || 'Prises de decision', result.redirectUrl, 'fetch');
                                    } else {
                                        window.location.href = result.redirectUrl;
                                    }
                                }
                            })
                            .catch(function (error) {
                                setFeedback(error && error.message ? error.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur'), true);
                            })
                            .finally(function () {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = submitButton.dataset.originalText || submitButton.textContent;
                                }
                            });
                    });

                    form.dataset.omoDecisionConsentResponseReady = '1';
                });
            };

            window.omoDecisionConsentInit(document.currentScript ? document.currentScript.parentElement : document);
        })();
