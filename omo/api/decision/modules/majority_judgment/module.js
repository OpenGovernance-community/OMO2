        (function () {
            window.omoDecisionMajorityJudgmentInit = function (root) {
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


                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-majority-judgment-form]'), function (form) {
                    if (form.dataset.omoDecisionMjReady === '1') {
                        return;
                    }

                        const payloadNode = form.querySelector('[data-omo-decision-mj-data]');
                        const embeddedQuestion = form.getAttribute('data-omo-decision-embedded-question') === '1';
                        const submitButton = form.querySelector('[data-omo-decision-mj-submit]')
                            || (form.id !== '' ? document.querySelector('[data-omo-decision-editor-submit][form="' + form.id + '"]') : null);
                        const feedbackNode = form.querySelector('[data-omo-decision-mj-feedback]');
                        const proposalList = form.querySelector('[data-omo-decision-mj-proposal-list]');
                        const proposalAddButton = form.querySelector('[data-omo-decision-mj-proposal-add]');
                        const settingsOpenButton = form.querySelector('[data-omo-decision-mj-settings-open]');
                        const invitationOpenButton = form.querySelector('[data-omo-decision-invitations-open]');
                        const invitationSendOpenButton = form.querySelector('[data-omo-decision-invitations-send-open]');
                        const settingsTemplate = form.querySelector('[data-omo-decision-mj-settings-template]');
                        const hiddenAnonymousInput = form.querySelector('[data-omo-decision-mj-hidden-anonymous]');
                        const hiddenAllowAnonymousVotesInput = form.querySelector('[data-omo-decision-mj-hidden-allow-anonymous-votes]');
                        const hiddenConsultationInput = form.querySelector('[data-omo-decision-mj-hidden-consultation-proposals]');
                        const hiddenProposalDiscussionsInput = form.querySelector('[data-omo-decision-mj-hidden-proposal-discussions]');
                        const hiddenLiveResultsInput = form.querySelector('[data-omo-decision-mj-hidden-live-results]');
                        const hiddenRandomOrderInput = form.querySelector('[data-omo-decision-mj-hidden-random-order]');
                        const hiddenOneProposalAtATimeInput = form.querySelector('[data-omo-decision-mj-hidden-one-proposal-at-a-time]');
                        const hiddenProposalContentTitleInput = form.querySelector('[data-omo-decision-proposal-content-hidden-title]');
                        const hiddenProposalContentDescriptionInput = form.querySelector('[data-omo-decision-proposal-content-hidden-description]');
                        const hiddenProposalContentUrlInput = form.querySelector('[data-omo-decision-proposal-content-hidden-url]');
                        const hiddenVoteWeightEnabledInput = form.querySelector('[data-omo-decision-mj-hidden-vote-weight-enabled]');
                        const hiddenVoteWeightQuestionInput = form.querySelector('[data-omo-decision-mj-hidden-vote-weight-question]');
                        const hiddenVoteWeightOptionsInput = form.querySelector('[data-omo-decision-mj-hidden-vote-weight-options]');
                        const scaleSummaryNode = form.querySelector('[data-omo-decision-mj-scale-summary]');
                        const anonymousSummary = form.querySelector('[data-omo-decision-mj-anonymous-summary]');
                        const allowAnonymousVotesSummary = form.querySelector('[data-omo-decision-mj-allow-anonymous-votes-summary]');
                        const allowAnonymousVotesStat = form.querySelector('[data-omo-decision-mj-allow-anonymous-votes-stat]');
                        const consultationSummary = form.querySelector('[data-omo-decision-mj-consultation-summary]');
                        const discussionsSummary = form.querySelector('[data-omo-decision-mj-discussions-summary]');
                        const liveResultsSummary = form.querySelector('[data-omo-decision-mj-live-results-summary]');
                        const randomOrderSummary = form.querySelector('[data-omo-decision-mj-random-order-summary]');
                        const oneProposalAtATimeSummary = form.querySelector('[data-omo-decision-mj-one-proposal-at-a-time-summary]');
                        const proposalContentSummary = form.querySelector('[data-omo-decision-mj-proposal-content-summary]');
                        const voteWeightSummary = form.querySelector('[data-omo-decision-mj-vote-weight-summary]');
                        const hiddenMentionCustomizationInput = form.querySelector('[data-omo-decision-mj-hidden-mention-customization-enabled]');
                        const hiddenMentionLabelInputs = {};
                        const hiddenMentionActiveInputs = {};

                        if (!payloadNode || !proposalList) {
                            return;
                        }

                        if (typeof window.omoDecisionInitInvitationEditors === 'function') {
                            window.omoDecisionInitInvitationEditors(form);
                        }

                        Array.prototype.forEach.call(form.querySelectorAll('[data-omo-decision-mj-hidden-mention-label]'), function (input) {
                            const score = String(input.getAttribute('data-omo-decision-mj-hidden-mention-label') || '').trim();
                            if (score !== '') {
                                hiddenMentionLabelInputs[score] = input;
                            }
                        });
                        Array.prototype.forEach.call(form.querySelectorAll('[data-omo-decision-mj-hidden-mention-active]'), function (input) {
                            const score = String(input.getAttribute('data-omo-decision-mj-hidden-mention-active') || '').trim();
                            if (score !== '') {
                                hiddenMentionActiveInputs[score] = input;
                            }
                        });

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

                        const mentionScores = Object.keys(hiddenMentionLabelInputs).sort(function (left, right) {
                            return Number(left) - Number(right);
                        });

                        const normalizeMentionLabel = function (input, rawValue) {
                            const defaultLabel = input
                                ? String(input.getAttribute('data-default-label') || '').trim()
                                : '';
                            const nextValue = String(rawValue || '').trim();
                            return nextValue !== '' ? nextValue : defaultLabel;
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

                        const buildScaleSummary = function () {
                            if (hiddenMentionCustomizationInput && !hiddenMentionCustomizationInput.value) {
                                return String(payload.texts && payload.texts.scaleSummaryDefault ? payload.texts.scaleSummaryDefault : 'Valeurs par defaut');
                            }
                            const labels = [];
                            mentionScores.forEach(function (score) {
                                const labelInput = hiddenMentionLabelInputs[score];
                                const activeInput = hiddenMentionActiveInputs[score];
                                if (!labelInput || !activeInput || !activeInput.value) {
                                    return;
                                }
                                labels.push(normalizeMentionLabel(labelInput, labelInput.value));
                            });

                            if (labels.length === 0) {
                                return String(payload.texts && payload.texts.scaleSummaryEmpty ? payload.texts.scaleSummaryEmpty : 'Aucune mention active');
                            }

                            return labels.join(' / ');
                        };

                        const syncSettingsSummary = function () {
                            const yesLabel = String(payload.texts && payload.texts.yesLabel ? payload.texts.yesLabel : 'Oui');
                            const noLabel = String(payload.texts && payload.texts.noLabel ? payload.texts.noLabel : 'Non');
                            if (scaleSummaryNode) {
                                scaleSummaryNode.textContent = buildScaleSummary();
                            }
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

                        const openSettingsModal = function () {
                            if (!settingsTemplate || typeof window.commonTopbarOpenModal !== 'function') {
                                return;
                            }

                            const modalTitle = settingsOpenButton
                                ? String(settingsOpenButton.getAttribute('data-omo-decision-mj-settings-title') || settingsOpenButton.textContent || 'Paramètres du scrutin')
                                : 'Paramètres du scrutin';
                            window.commonTopbarOpenModal(modalTitle, settingsTemplate.innerHTML, 'html');
                            const modalBody = document.getElementById('commonTopbarModalBody');
                            if (!modalBody) {
                                return;
                            }

                            const popupAnonymous = modalBody.querySelector('[data-omo-decision-mj-popup-anonymous]');
                            const popupAllowAnonymousVotes = modalBody.querySelector('[data-omo-decision-mj-popup-allow-anonymous-votes]');
                            const popupAllowAnonymousVotesOption = modalBody.querySelector('[data-omo-decision-mj-popup-allow-anonymous-votes-option]');
                            const popupConsultation = modalBody.querySelector('[data-omo-decision-mj-popup-consultation-proposals]');
                            const popupProposalDiscussions = modalBody.querySelector('[data-omo-decision-mj-popup-proposal-discussions]');
                            const popupOwnerIntermediateResults = modalBody.querySelector('[data-omo-decision-mj-popup-owner-intermediate-results]');
                            const popupParticipantIntermediateResults = modalBody.querySelector('[data-omo-decision-mj-popup-participant-intermediate-results]');
                            const popupParticipantResponsesEditable = modalBody.querySelector('[data-omo-decision-mj-popup-participant-responses-editable]');
                            const popupRandomOrder = modalBody.querySelector('[data-omo-decision-mj-popup-random-order]');
                            const popupOneProposalAtATime = modalBody.querySelector('[data-omo-decision-mj-popup-one-proposal-at-a-time]');
                            const popupProposalContentTitle = modalBody.querySelector('[data-omo-decision-proposal-content-popup-title]');
                            const popupProposalContentDescription = modalBody.querySelector('[data-omo-decision-proposal-content-popup-description]');
                            const popupProposalContentUrl = modalBody.querySelector('[data-omo-decision-proposal-content-popup-url]');
                            const popupVoteWeightRoot = modalBody.querySelector('[data-omo-decision-vote-weight-editor]');
                            const popupMentionCustomization = modalBody.querySelector('[data-omo-decision-mj-popup-mention-customization]');
                            const popupMentionSettings = modalBody.querySelector('[data-omo-decision-mj-popup-mention-settings]');
                            const popupCancel = modalBody.querySelector('[data-omo-decision-mj-popup-cancel]');
                            const popupApply = modalBody.querySelector('[data-omo-decision-mj-popup-apply]');
                            const popupVoteWeightEditor = popupVoteWeightRoot && typeof window.omoDecisionInitVoteWeightEditor === 'function'
                                ? window.omoDecisionInitVoteWeightEditor(popupVoteWeightRoot)
                                : null;
                            const popupMentionLabelInputs = {};
                            const popupMentionActiveInputs = {};

                            Array.prototype.forEach.call(modalBody.querySelectorAll('[data-omo-decision-mj-popup-mention-label]'), function (input) {
                                const score = String(input.getAttribute('data-omo-decision-mj-popup-mention-label') || '').trim();
                                if (score !== '') {
                                    popupMentionLabelInputs[score] = input;
                                }
                            });
                            Array.prototype.forEach.call(modalBody.querySelectorAll('[data-omo-decision-mj-popup-mention-active]'), function (input) {
                                const score = String(input.getAttribute('data-omo-decision-mj-popup-mention-active') || '').trim();
                                if (score !== '') {
                                    popupMentionActiveInputs[score] = input;
                                }
                            });

                            if (!popupAnonymous || !popupAllowAnonymousVotes || !popupAllowAnonymousVotesOption || !popupConsultation || !popupProposalDiscussions || !popupOwnerIntermediateResults || !popupParticipantIntermediateResults || !popupParticipantResponsesEditable || !popupRandomOrder || !popupOneProposalAtATime || !popupProposalContentTitle || !popupProposalContentDescription || !popupProposalContentUrl || !popupVoteWeightEditor || !popupApply) {
                                return;
                            }

                            const popupCanEdit = !popupApply.disabled;
                            const syncMentionCustomization = function () {
                                const isEnabled = !!(popupMentionCustomization && popupMentionCustomization.checked);
                                if (popupMentionSettings) {
                                    popupMentionSettings.hidden = !isEnabled;
                                    popupMentionSettings.setAttribute('aria-hidden', isEnabled ? 'false' : 'true');
                                }
                                mentionScores.forEach(function (score) {
                                    const popupLabelInput = popupMentionLabelInputs[score];
                                    const popupActiveInput = popupMentionActiveInputs[score];
                                    if (popupLabelInput) {
                                        popupLabelInput.disabled = !popupCanEdit || !isEnabled;
                                    }
                                    if (popupActiveInput) {
                                        popupActiveInput.disabled = !popupCanEdit || !isEnabled;
                                    }
                                });
                            };

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
                            mentionScores.forEach(function (score) {
                                const hiddenLabelInput = hiddenMentionLabelInputs[score];
                                const hiddenActiveInput = hiddenMentionActiveInputs[score];
                                const popupLabelInput = popupMentionLabelInputs[score];
                                const popupActiveInput = popupMentionActiveInputs[score];

                                if (popupLabelInput && hiddenLabelInput) {
                                    popupLabelInput.value = normalizeMentionLabel(hiddenLabelInput, hiddenLabelInput.value);
                                }
                                if (popupActiveInput && hiddenActiveInput) {
                                    popupActiveInput.checked = !!hiddenActiveInput.value;
                                }
                            });
                            if (popupMentionCustomization) {
                                popupMentionCustomization.checked = !!(hiddenMentionCustomizationInput && hiddenMentionCustomizationInput.value);
                                popupMentionCustomization.addEventListener('change', syncMentionCustomization);
                            }
                            syncMentionCustomization();

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
                                if (!embeddedQuestion) {
                                    const settingsForm = form.closest('[data-omo-decision-multi-editor]')
                                        ? form.closest('[data-omo-decision-multi-editor]').querySelector('[data-omo-decision-process-form]')
                                        : form;
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
                                        descriptionSelector: '[data-omo-decision-mj-proposal-description]',
                                        detailsSelector: '[data-omo-decision-mj-proposal-settings]',
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
                                if (hiddenMentionCustomizationInput) {
                                    hiddenMentionCustomizationInput.value = popupMentionCustomization && popupMentionCustomization.checked ? '1' : '';
                                }
                                mentionScores.forEach(function (score) {
                                    const hiddenLabelInput = hiddenMentionLabelInputs[score];
                                    const hiddenActiveInput = hiddenMentionActiveInputs[score];
                                    const popupLabelInput = popupMentionLabelInputs[score];
                                    const popupActiveInput = popupMentionActiveInputs[score];
                                    const customizeMentions = !!(popupMentionCustomization && popupMentionCustomization.checked);

                                    if (hiddenLabelInput && popupLabelInput) {
                                        hiddenLabelInput.value = customizeMentions
                                            ? normalizeMentionLabel(popupLabelInput, popupLabelInput.value)
                                            : String(hiddenLabelInput.getAttribute('data-default-label') || '');
                                        popupLabelInput.value = hiddenLabelInput.value;
                                    }
                                    if (hiddenActiveInput && popupActiveInput) {
                                        hiddenActiveInput.value = customizeMentions
                                            ? (popupActiveInput.checked ? '1' : '')
                                            : String(hiddenActiveInput.getAttribute('data-default-active') || '');
                                        popupActiveInput.checked = hiddenActiveInput.value === '1';
                                    }
                                });
                                syncSettingsSummary();
                                if (typeof window.commonTopbarCloseModal === 'function') {
                                    window.commonTopbarCloseModal();
                                }
                            });
                        };

                        const refreshProposalLabels = function () {
                            Array.prototype.forEach.call(
                                proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]'),
                                function (card, index) {
                                    const label = card.querySelector('[data-omo-decision-mj-proposal-label]');
                                    if (label) {
                                        const template = payload.texts && payload.texts.proposalItemTemplate
                                            ? payload.texts.proposalItemTemplate
                                            : 'Proposition __INDEX__';
                                        label.textContent = String(template).replace('__INDEX__', String(index + 1));
                                    }
                                }
                            );
                        };

                        const closeProposalMenus = function (exceptCard) {
                            Array.prototype.forEach.call(
                                proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]'),
                                function (menuCard) {
                                    if (exceptCard && menuCard === exceptCard) {
                                        return;
                                    }

                                    const menuPanel = menuCard.querySelector('[data-omo-decision-mj-proposal-menu-panel]');
                                    const menuToggle = menuCard.querySelector('[data-omo-decision-mj-proposal-menu-toggle]');
                                    if (menuPanel) {
                                        menuPanel.hidden = true;
                                    }
                                    if (menuToggle) {
                                        menuToggle.setAttribute('aria-expanded', 'false');
                                    }
                                }
                            );
                        };

                        let sortable = null;
                        const bindProposalCard = function (card) {
                            if (!card || card.dataset.omoDecisionMjProposalReady === '1') {
                                return;
                            }

                            const removeButton = card.querySelector('[data-omo-decision-mj-proposal-remove]');
                            const detailsButton = card.querySelector('[data-omo-decision-mj-proposal-settings]');
                            const menuToggle = card.querySelector('[data-omo-decision-mj-proposal-menu-toggle]');
                            const menuPanel = card.querySelector('[data-omo-decision-mj-proposal-menu-panel]');
                            const titleInput = card.querySelector('input[name="proposals[]"]');
                            const descriptionInput = card.querySelector('[data-omo-decision-mj-proposal-description]');
                            const descriptionEditor = card.querySelector('[data-omo-decision-mj-proposal-description-editor]');
                            const infoUrlInput = card.querySelector('[data-omo-decision-mj-proposal-info-url]');

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

                                    const proposalLabelNode = card.querySelector('[data-omo-decision-mj-proposal-label]');
                                    const modalTitle = proposalLabelNode
                                        ? String(proposalLabelNode.textContent || '').trim()
                                        : String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');
                                    const proposalContent = payload.proposalContent || {title: true, description: true};
                                    const descriptionDetailsField = proposalContent.title && proposalContent.description
                                        ? '  <label style="display:grid;gap:6px;">'
                                            + '    <span class="generic-form-label">' + String(payload.texts && payload.texts.proposalDescriptionLabel ? payload.texts.proposalDescriptionLabel : 'Description') + '</span>'
                                            + '    <div data-omo-proposal-html-field><div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-decision-mj-proposal-modal-description></div><textarea hidden aria-hidden="true" data-omo-proposal-html-value></textarea></div>'
                                            + '  </label>'
                                        : '';
                                    const modalHtml = ''
                                        + '<div class="generic-section generic-section--stack" style="display:grid;gap:12px;">'
                                        + descriptionDetailsField
                                        + '  <label style="display:grid;gap:6px;">'
                                        + '    <span class="generic-form-label">' + String(payload.texts && payload.texts.proposalInfoUrlLabel ? payload.texts.proposalInfoUrlLabel : 'URL') + '</span>'
                                        + '    <input type="url" class="generic-form-control generic-form-control--compact" data-omo-decision-mj-proposal-modal-info-url placeholder="' + String(payload.texts && payload.texts.proposalInfoUrlPlaceholder ? payload.texts.proposalInfoUrlPlaceholder : 'https://...') + '">'
                                        + '  </label>'
                                        + '  <div style="display:flex;justify-content:flex-end;gap:8px;">'
                                        + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-mj-proposal-modal-cancel>Fermer</button>'
                                        + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-mj-proposal-modal-apply>' + String(payload.texts && payload.texts.proposalApply ? payload.texts.proposalApply : 'Enregistrer') + '</button>'
                                        + '  </div>'
                                        + '</div>';

                                    window.commonTopbarOpenModal(modalTitle || 'Details', modalHtml, 'html');
                                    const modalBody = document.getElementById('commonTopbarModalBody');
                                    if (!modalBody) {
                                        return;
                                    }

                                    const modalDescription = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-description]');
                                    const modalInfoUrl = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-info-url]');
                                    const modalCancel = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-cancel]');
                                    const modalApply = modalBody.querySelector('[data-omo-decision-mj-proposal-modal-apply]');
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

                            if (removeButton && !removeButton.disabled) {
                                removeButton.addEventListener('click', function () {
                                    closeProposalMenus();
                                    card.parentNode.removeChild(card);
                                    if (proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]').length < 2) {
                                        proposalList.appendChild(createProposalCard(''));
                                    }
                                    refreshProposalLabels();
                                    setFeedback('', false);
                                });
                            }

                            card.dataset.omoDecisionMjProposalReady = '1';
                        };

                        const createProposalCard = function (value) {
                            const card = document.createElement('div');
                            card.className = 'omo-decision-majority-judgment__proposal-card omo-decision-proposal-card generic-section';
                            card.setAttribute('data-omo-decision-mj-proposal-card', '1');
                            card.setAttribute('draggable', 'true');
                            const proposalContent = payload.proposalContent || {};
                            const proposalTitleField = proposalContent.title
                                ? '<input type="text" name="proposals[]" class="generic-form-control generic-form-control--compact" placeholder="' + String(payload.texts && payload.texts.proposalPlaceholder ? payload.texts.proposalPlaceholder : 'Nom de la proposition') + '">'
                                : '<input type="hidden" name="proposals[]" value="">';
                            const proposalDescriptionField = !proposalContent.title && proposalContent.description
                                ? '<div data-omo-proposal-html-field><div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-decision-mj-proposal-description-editor' + (payload.proposalEditable === true ? '' : ' data-omo-proposal-html-disabled="1"') + '></div><textarea hidden aria-hidden="true" name="proposal_descriptions[]" data-omo-proposal-html-value data-omo-decision-mj-proposal-description></textarea></div>'
                                : '<textarea hidden aria-hidden="true" name="proposal_descriptions[]" data-omo-decision-mj-proposal-description></textarea>';
                            const proposalDetailsButton = proposalContent.url
                                ? '        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-settings role="menuitem">' + String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details') + '</button>'
                                : '';
                            card.innerHTML = ''
                                + '<button type="button" class="omo-decision-majority-judgment__proposal-drag generic-drag-handle generic-drag-handle--stretch" data-omo-decision-mj-proposal-drag title="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Reordonner') + '" aria-label="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Reordonner') + '">&#8942;&#8942;</button>'
                                + '<div class="omo-decision-majority-judgment__proposal-main">'
                                + '    <span class="omo-decision-majority-judgment__proposal-label" data-omo-decision-mj-proposal-label></span>'
                                + '    ' + proposalTitleField
                                + '    ' + proposalDescriptionField
                                + '    <input type="hidden" name="proposal_info_urls[]" value="" data-omo-decision-mj-proposal-info-url>'
                                + '    <input type="hidden" name="proposal_ids[]" value="0">'
                                + '</div>'
                                + '<div class="omo-decision-majority-judgment__proposal-menu" data-omo-decision-mj-proposal-menu>'
                                + '    <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-majority-judgment__proposal-menu-toggle" data-omo-decision-mj-proposal-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="' + String(payload.texts && payload.texts.proposalActions ? payload.texts.proposalActions : 'Actions') + '">...</button>'
                                + '    <div class="omo-decision-majority-judgment__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel" data-omo-decision-mj-proposal-menu-panel role="menu" hidden>'
                                + proposalDetailsButton
                                + '        <button type="button" class="generic-action-button generic-action-button--danger omo-decision-majority-judgment__proposal-menu-item" data-omo-decision-mj-proposal-remove role="menuitem">' + String(payload.texts && payload.texts.proposalRemove ? payload.texts.proposalRemove : 'Supprimer') + '</button>'
                                + '    </div>'
                                + '</div>';

                            const input = card.querySelector('input[name="proposals[]"]');
                            if (input) {
                                input.value = String(value || '');
                            }

                            bindProposalCard(card);
                            if (sortable && typeof sortable.bindItem === 'function') {
                                sortable.bindItem(card);
                            }
                            refreshProposalLabels();
                            return card;
                        };

                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-mj-proposal-card]'), bindProposalCard);
                        refreshProposalLabels();
                        syncSettingsSummary();

                        document.addEventListener('click', function (event) {
                            if (!proposalList.contains(event.target)) {
                                closeProposalMenus();
                            }
                        });

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
                                itemSelector: '[data-omo-decision-mj-proposal-card]',
                                handleSelector: '[data-omo-decision-mj-proposal-drag]',
                                draggingClass: 'is-dragging',
                                dropTargetClass: 'is-drop-target',
                                placeholderClass: 'omo-decision-majority-judgment__proposal-placeholder',
                                createPlaceholder: function (card) {
                                    const placeholder = document.createElement('div');
                                    placeholder.className = 'omo-decision-majority-judgment__proposal-placeholder';
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
                                const input = newCard.querySelector('input[name="proposals[]"]');
                                if (input) {
                                    input.focus();
                                }
                            });
                        }

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

                    form.dataset.omoDecisionMjReady = '1';
                });

                    Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-mj-response-form]'), function (form) {
                        if (form.dataset.omoDecisionMjResponseReady === '1') {
                            return;
                        }

                        const payloadNode = form.querySelector('[data-omo-decision-mj-response-data]');
                        const submitButton = form.querySelector('[data-omo-decision-mj-response-submit]');
                        const feedbackNode = form.querySelector('[data-omo-decision-mj-response-feedback]');
                        if (!payloadNode) {
                            return;
                        }

                        const voteWeightSelector = form.querySelector('[data-omo-decision-vote-weight-selector]');
                        if (voteWeightSelector && typeof window.omoDecisionInitVoteWeightSelector === 'function') {
                            window.omoDecisionInitVoteWeightSelector(voteWeightSelector);
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

                        const syncRatingState = function () {
                            Array.prototype.forEach.call(form.querySelectorAll('.omo-decision-majority-judgment__rating-option'), function (ratingOption) {
                                const radio = ratingOption.querySelector('input[type="radio"]');
                                const trigger = ratingOption.querySelector('[data-omo-decision-mj-rating-trigger]');
                                const isSelected = !!(radio && radio.checked);
                                ratingOption.classList.toggle('is-selected', isSelected);
                                if (trigger) {
                                    trigger.setAttribute('aria-checked', isSelected ? 'true' : 'false');
                                }
                            });
                        };

                        form.addEventListener('click', function (event) {
                            const trigger = event.target.closest('[data-omo-decision-mj-rating-trigger]');
                            if (!trigger || !form.contains(trigger)) {
                                return;
                            }

                            event.preventDefault();
                            event.stopPropagation();

                            const ratingOption = trigger.closest('.omo-decision-majority-judgment__rating-option');
                            const radio = ratingOption.querySelector('input[type="radio"]');
                            if (!radio || radio.disabled) {
                                return;
                            }

                            if (!radio.checked) {
                                radio.checked = true;
                                radio.dispatchEvent(new Event('change', { bubbles: true }));
                            } else {
                                syncRatingState();
                            }
                        });

                        form.addEventListener('change', function (event) {
                            if (!event.target.matches('.omo-decision-majority-judgment__rating-input')) {
                                return;
                            }

                            syncRatingState();
                        });

                        form.addEventListener('keydown', function (event) {
                            const trigger = event.target.closest('[data-omo-decision-mj-rating-trigger]');
                            if (!trigger || !form.contains(trigger)) {
                                return;
                            }

                            if (event.key !== ' ' && event.key !== 'Enter') {
                                return;
                            }

                            event.preventDefault();
                            trigger.click();
                        });

                        syncRatingState();

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

                    form.dataset.omoDecisionMjResponseReady = '1';
                });

                Array.prototype.forEach.call(getScopedMatches('[data-omo-decision-mj-results-panel]'), function (panel) {
                    if (panel.dataset.omoDecisionMjResultsReady === '1') {
                        return;
                    }

                    const list = panel.querySelector('[data-omo-decision-mj-results-list]');
                    const buttons = panel.querySelectorAll('[data-omo-decision-mj-results-sort]');
                    const compareToggle = panel.querySelector('[data-omo-decision-mj-results-compare-toggle]');
                    const compareBlocks = panel.querySelectorAll('[data-omo-decision-mj-results-compare-block]');
                    if (!list) {
                        panel.dataset.omoDecisionMjResultsReady = '1';
                        return;
                    }

                    const collator = typeof Intl !== 'undefined' && typeof Intl.Collator === 'function'
                        ? new Intl.Collator('fr', { sensitivity: 'base', numeric: true })
                        : null;

                    const compareText = function (left, right) {
                        const normalizedLeft = String(left || '');
                        const normalizedRight = String(right || '');

                        if (collator) {
                            return collator.compare(normalizedLeft, normalizedRight);
                        }

                        return normalizedLeft.localeCompare(normalizedRight);
                    };

                    const normalizeSortMode = function (value) {
                        const normalizedValue = String(value || '').trim().toLowerCase();
                        if (normalizedValue === 'initial' || normalizedValue === 'alpha') {
                            return normalizedValue;
                        }

                        return 'rank';
                    };

                    const sortItems = function (sortMode) {
                        const items = Array.prototype.slice.call(list.querySelectorAll('[data-omo-decision-mj-result-item]'));
                        if (items.length < 2) {
                            return;
                        }

                        items.sort(function (left, right) {
                            const leftRank = Number(left.getAttribute('data-omo-decision-mj-result-rank') || 0);
                            const rightRank = Number(right.getAttribute('data-omo-decision-mj-result-rank') || 0);
                            const leftPosition = Number(left.getAttribute('data-omo-decision-mj-result-position') || 0);
                            const rightPosition = Number(right.getAttribute('data-omo-decision-mj-result-position') || 0);
                            const leftTitle = String(left.getAttribute('data-omo-decision-mj-result-title') || '');
                            const rightTitle = String(right.getAttribute('data-omo-decision-mj-result-title') || '');

                            if (sortMode === 'initial') {
                                return leftPosition - rightPosition;
                            }

                            if (sortMode === 'alpha') {
                                const titleDiff = compareText(leftTitle, rightTitle);
                                if (titleDiff !== 0) {
                                    return titleDiff;
                                }

                                return leftPosition - rightPosition;
                            }

                            return leftRank - rightRank;
                        });

                        items.forEach(function (item) {
                            list.appendChild(item);
                        });
                    };

                    const applySortMode = function (sortMode) {
                        const normalizedSortMode = normalizeSortMode(sortMode);
                        buttons.forEach(function (button) {
                            const isActive = normalizeSortMode(button.getAttribute('data-omo-decision-mj-results-sort')) === normalizedSortMode;
                            button.classList.toggle('is-active', isActive);
                            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                        });

                        sortItems(normalizedSortMode);
                    };

                    const syncComparison = function () {
                        const showComparison = !!(compareToggle && compareToggle.checked);
                        compareBlocks.forEach(function (block) {
                            block.hidden = !showComparison;
                            block.setAttribute('aria-hidden', showComparison ? 'false' : 'true');
                            block.style.display = showComparison ? '' : 'none';
                        });
                    };

                    buttons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            applySortMode(button.getAttribute('data-omo-decision-mj-results-sort'));
                        });
                    });

                    if (compareToggle) {
                        compareToggle.addEventListener('change', syncComparison);
                    }

                    if (buttons.length) {
                        applySortMode('rank');
                    }
                    syncComparison();
                    panel.dataset.omoDecisionMjResultsReady = '1';
                });
            };

            window.omoDecisionMajorityJudgmentInit(document.currentScript ? document.currentScript.parentElement : document);
        })();
