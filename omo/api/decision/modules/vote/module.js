window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/decision/modules/vote/module.js"] = function (pageConfig, pageScript) {
        (function () {
            if (typeof window.omoDecisionVoteInit !== 'function') {
                window.omoDecisionVoteInit = function (root) {
                    const scope = root instanceof Element ? root : document;

                    scope.querySelectorAll('[data-omo-decision-vote-form]').forEach(function (form) {
                        if (form.dataset.omoDecisionVoteReady === '1') {
                            return;
                        }

                        const payloadNode = form.querySelector('[data-omo-decision-vote-data]');
                        const embeddedQuestion = form.getAttribute('data-omo-decision-embedded-question') === '1';
                        const getSubmitButton = function () {
                            return form.querySelector('[data-omo-decision-vote-submit]')
                                || (form.id !== '' ? document.querySelector('[data-omo-decision-editor-submit][form="' + form.id + '"]') : null);
                        };
                        const feedbackNode = form.querySelector('[data-omo-decision-vote-feedback]');
                        const proposalList = form.querySelector('[data-omo-decision-vote-proposal-list]');
                        const proposalAddButton = form.querySelector('[data-omo-decision-vote-proposal-add]');
                        const settingsOpenButton = form.querySelector('[data-omo-decision-vote-settings-open]');
                        const invitationOpenButton = form.querySelector('[data-omo-decision-invitations-open]');
                        const invitationSendOpenButton = form.querySelector('[data-omo-decision-invitations-send-open]');
                        const settingsTemplate = form.querySelector('[data-omo-decision-vote-settings-template]');
                        const hiddenChoiceModeInput = form.querySelector('[data-omo-decision-vote-hidden-choice-mode]');
                        const hiddenMaxChoicesInput = form.querySelector('[data-omo-decision-vote-hidden-max-choices]');
                        const hiddenAnonymousInput = form.querySelector('[data-omo-decision-vote-hidden-anonymous]');
                        const hiddenAllowAnonymousVotesInput = form.querySelector('[data-omo-decision-vote-hidden-allow-anonymous-votes]');
                        const hiddenConsultationProposalsInput = form.querySelector('[data-omo-decision-vote-hidden-consultation-proposals]');
                        const hiddenProposalDiscussionsInput = form.querySelector('[data-omo-decision-vote-hidden-proposal-discussions]');
                        const hiddenLiveResultsInput = form.querySelector('[data-omo-decision-vote-hidden-live-results]');
                        const hiddenRandomOrderInput = form.querySelector('[data-omo-decision-vote-hidden-random-order]');
                        const hiddenOneProposalAtATimeInput = form.querySelector('[data-omo-decision-vote-hidden-one-proposal-at-a-time]');
                        const hiddenProposalContentTitleInput = form.querySelector('[data-omo-decision-proposal-content-hidden-title]');
                        const hiddenProposalContentDescriptionInput = form.querySelector('[data-omo-decision-proposal-content-hidden-description]');
                        const hiddenProposalContentUrlInput = form.querySelector('[data-omo-decision-proposal-content-hidden-url]');
                        const hiddenVoteWeightEnabledInput = form.querySelector('[data-omo-decision-vote-hidden-vote-weight-enabled]');
                        const hiddenVoteWeightQuestionInput = form.querySelector('[data-omo-decision-vote-hidden-vote-weight-question]');
                        const hiddenVoteWeightOptionsInput = form.querySelector('[data-omo-decision-vote-hidden-vote-weight-options]');
                        const choiceSummary = form.querySelector('[data-omo-decision-vote-choice-summary]');
                        const anonymousSummary = form.querySelector('[data-omo-decision-vote-anonymous-summary]');
                        const allowAnonymousVotesSummary = form.querySelector('[data-omo-decision-vote-allow-anonymous-votes-summary]');
                        const allowAnonymousVotesStat = form.querySelector('[data-omo-decision-vote-allow-anonymous-votes-stat]');
                        const consultationSummary = form.querySelector('[data-omo-decision-vote-consultation-summary]');
                        const discussionsSummary = form.querySelector('[data-omo-decision-vote-discussions-summary]');
                        const liveResultsSummary = form.querySelector('[data-omo-decision-vote-live-results-summary]');
                        const randomOrderSummary = form.querySelector('[data-omo-decision-vote-random-order-summary]');
                        const oneProposalAtATimeSummary = form.querySelector('[data-omo-decision-vote-one-proposal-at-a-time-summary]');
                        const proposalContentSummary = form.querySelector('[data-omo-decision-vote-proposal-content-summary]');
                        const voteWeightSummary = form.querySelector('[data-omo-decision-vote-vote-weight-summary]');

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

                        const clearFeedback = function () {
                            setFeedback('', false);
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

                        const syncChoiceModeFields = function () {
                            const choiceModeValue = String(hiddenChoiceModeInput && hiddenChoiceModeInput.value ? hiddenChoiceModeInput.value : 'single');
                            const isMultiple = choiceModeValue === 'multiple';
                            if (choiceSummary) {
                                if (isMultiple) {
                                    const unlimitedLabel = String(choiceSummary.getAttribute('data-unlimited-label') || 'Sans limite');
                                    const maxChoicesLabel = String(hiddenMaxChoicesInput && hiddenMaxChoicesInput.value ? hiddenMaxChoicesInput.value : '0') === '0'
                                        ? unlimitedLabel
                                        : String(hiddenMaxChoicesInput && hiddenMaxChoicesInput.value ? hiddenMaxChoicesInput.value : '1');
                                    choiceSummary.textContent = 'Plusieurs réponses (max. ' + maxChoicesLabel + ')';
                                } else {
                                    choiceSummary.textContent = 'Une seule reponse';
                                }
                            }
                        };

                        const syncSettingsSummary = function () {
                            syncChoiceModeFields();
                            if (randomOrderSummary && hiddenRandomOrderInput) {
                                const yesLabel = String(randomOrderSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(randomOrderSummary.getAttribute('data-no-label') || 'Non');
                                randomOrderSummary.textContent = hiddenRandomOrderInput.value ? yesLabel : noLabel;
                            }
                            if (oneProposalAtATimeSummary && hiddenOneProposalAtATimeInput) {
                                const yesLabel = String(oneProposalAtATimeSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(oneProposalAtATimeSummary.getAttribute('data-no-label') || 'Non');
                                oneProposalAtATimeSummary.textContent = hiddenOneProposalAtATimeInput.value ? yesLabel : noLabel;
                            }
                            if (anonymousSummary && hiddenAnonymousInput) {
                                const yesLabel = String(anonymousSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(anonymousSummary.getAttribute('data-no-label') || 'Non');
                                anonymousSummary.textContent = hiddenAnonymousInput.value
                                    ? noLabel
                                    : yesLabel;
                            }
                            if (allowAnonymousVotesStat) {
                                allowAnonymousVotesStat.hidden = !!(hiddenAnonymousInput && hiddenAnonymousInput.value);
                            }
                            if (allowAnonymousVotesSummary && hiddenAllowAnonymousVotesInput) {
                                const yesLabel = String(allowAnonymousVotesSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(allowAnonymousVotesSummary.getAttribute('data-no-label') || 'Non');
                                allowAnonymousVotesSummary.textContent = hiddenAllowAnonymousVotesInput.value ? yesLabel : noLabel;
                            }
                            if (consultationSummary && hiddenConsultationProposalsInput) {
                                const yesLabel = String(consultationSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(consultationSummary.getAttribute('data-no-label') || 'Non');
                                consultationSummary.textContent = hiddenConsultationProposalsInput.value
                                    ? yesLabel
                                    : noLabel;
                            }
                            if (discussionsSummary && hiddenProposalDiscussionsInput) {
                                const yesLabel = String(discussionsSummary.getAttribute('data-yes-label') || 'Oui');
                                const noLabel = String(discussionsSummary.getAttribute('data-no-label') || 'Non');
                                discussionsSummary.textContent = hiddenProposalDiscussionsInput.value
                                    ? yesLabel
                                    : noLabel;
                            }
                            if (liveResultsSummary) {
                                liveResultsSummary.textContent = !hiddenLiveResultsInput || !hiddenLiveResultsInput.value
                                    ? String(liveResultsSummary.getAttribute('data-no-label') || 'Non')
                                    : (hiddenAnonymousInput && hiddenAnonymousInput.value
                                        ? String(liveResultsSummary.getAttribute('data-anonymous-label') || 'Oui')
                                        : String(liveResultsSummary.getAttribute('data-named-label') || 'Oui'));
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

                        const openSettingsModal = function () {
                            if (!settingsTemplate || typeof window.commonTopbarOpenModal !== 'function') {
                                return;
                            }

                            const modalTitle = settingsOpenButton
                                ? String(settingsOpenButton.getAttribute('data-omo-decision-vote-settings-title') || settingsOpenButton.textContent || 'Paramètres du vote')
                                : 'Paramètres du vote';
                            window.commonTopbarOpenModal(modalTitle, settingsTemplate.innerHTML, 'html');
                            const modalBody = document.getElementById('commonTopbarModalBody');
                            if (!modalBody) {
                                return;
                            }
                            const isConsultationOnly = pageConfig.isConsultationOnly;

                            const popupChoiceMode = modalBody.querySelector('[data-omo-decision-vote-popup-choice-mode]');
                            const popupMaxChoicesField = modalBody.querySelector('[data-omo-decision-vote-popup-max-choices-field]');
                            const popupMaxChoices = modalBody.querySelector('[data-omo-decision-vote-popup-max-choices]');
                            const popupAnonymous = modalBody.querySelector('[data-omo-decision-vote-popup-anonymous]');
                            const popupAllowAnonymousVotes = modalBody.querySelector('[data-omo-decision-vote-popup-allow-anonymous-votes]');
                            const popupAllowAnonymousVotesOption = modalBody.querySelector('[data-omo-decision-vote-popup-allow-anonymous-votes-option]');
                            const popupConsultationProposals = modalBody.querySelector('[data-omo-decision-vote-popup-consultation-proposals]');
                            const popupProposalDiscussions = modalBody.querySelector('[data-omo-decision-vote-popup-proposal-discussions]');
                            const popupOwnerIntermediateResults = modalBody.querySelector('[data-omo-decision-vote-popup-owner-intermediate-results]');
                            const popupParticipantIntermediateResults = modalBody.querySelector('[data-omo-decision-vote-popup-participant-intermediate-results]');
                            const popupParticipantResponsesEditable = modalBody.querySelector('[data-omo-decision-vote-popup-participant-responses-editable]');
                            const popupRandomOrder = modalBody.querySelector('[data-omo-decision-vote-popup-random-order]');
                            const popupOneProposalAtATime = modalBody.querySelector('[data-omo-decision-vote-popup-one-proposal-at-a-time]');
                            const popupProposalContentTitle = modalBody.querySelector('[data-omo-decision-proposal-content-popup-title]');
                            const popupProposalContentDescription = modalBody.querySelector('[data-omo-decision-proposal-content-popup-description]');
                            const popupProposalContentUrl = modalBody.querySelector('[data-omo-decision-proposal-content-popup-url]');
                            const popupVoteWeightRoot = modalBody.querySelector('[data-omo-decision-vote-weight-editor]');
                            const popupCancel = modalBody.querySelector('[data-omo-decision-vote-popup-cancel]');
                            const popupApply = modalBody.querySelector('[data-omo-decision-vote-popup-apply]');
                            const popupVoteWeightEditor = popupVoteWeightRoot && typeof window.omoDecisionInitVoteWeightEditor === 'function'
                                ? window.omoDecisionInitVoteWeightEditor(popupVoteWeightRoot)
                                : null;

                            if (!popupConsultationProposals || !popupProposalDiscussions || !popupProposalContentTitle || !popupProposalContentDescription || !popupProposalContentUrl || !popupApply) {
                                return;
                            }
                            if (!popupAnonymous || (!isConsultationOnly && (!popupChoiceMode || !popupMaxChoices || !popupAllowAnonymousVotes || !popupAllowAnonymousVotesOption || !popupOwnerIntermediateResults || !popupParticipantIntermediateResults || !popupParticipantResponsesEditable || !popupRandomOrder || !popupOneProposalAtATime || !popupVoteWeightEditor))) {
                                return;
                            }

                            popupAnonymous.checked = !(hiddenAnonymousInput && hiddenAnonymousInput.value);
                            if (!isConsultationOnly) {
                                popupChoiceMode.value = String(hiddenChoiceModeInput && hiddenChoiceModeInput.value ? hiddenChoiceModeInput.value : 'single');
                                popupMaxChoices.value = String(hiddenMaxChoicesInput && hiddenMaxChoicesInput.value ? hiddenMaxChoicesInput.value : '1');
                                popupAllowAnonymousVotes.checked = !!(hiddenAllowAnonymousVotesInput && hiddenAllowAnonymousVotesInput.value);
                                if (typeof window.omoDecisionBindIndividualAnonymousVoteOption === 'function') {
                                    window.omoDecisionBindIndividualAnonymousVoteOption(popupAnonymous, popupAllowAnonymousVotes, popupAllowAnonymousVotesOption);
                                }
                                popupRandomOrder.checked = !!(hiddenRandomOrderInput && hiddenRandomOrderInput.value);
                                popupOneProposalAtATime.checked = !!(hiddenOneProposalAtATimeInput && hiddenOneProposalAtATimeInput.value);
                                popupVoteWeightEditor.setState({
                                    enabled: !!(hiddenVoteWeightEnabledInput && hiddenVoteWeightEnabledInput.value),
                                    question: hiddenVoteWeightQuestionInput ? String(hiddenVoteWeightQuestionInput.value || '') : '',
                                    options: parseVoteWeightOptions(hiddenVoteWeightOptionsInput ? hiddenVoteWeightOptionsInput.value : '[]', false),
                                });
                            }
                            popupConsultationProposals.checked = !!(hiddenConsultationProposalsInput && hiddenConsultationProposalsInput.value);
                            popupProposalDiscussions.checked = !!(hiddenProposalDiscussionsInput && hiddenProposalDiscussionsInput.value);
                            popupProposalContentTitle.checked = !!(hiddenProposalContentTitleInput && hiddenProposalContentTitleInput.value);
                            popupProposalContentDescription.checked = !!(hiddenProposalContentDescriptionInput && hiddenProposalContentDescriptionInput.value);
                            popupProposalContentUrl.checked = !!(hiddenProposalContentUrlInput && hiddenProposalContentUrlInput.value);

                            const syncPopup = function () {
                                if (isConsultationOnly || !popupChoiceMode) {
                                    return;
                                }
                                const isMultiple = String(popupChoiceMode.value || 'single') === 'multiple';
                                if (popupMaxChoicesField) {
                                    popupMaxChoicesField.hidden = !isMultiple;
                                }
                            };

                            syncPopup();
                            if (popupChoiceMode) {
                                popupChoiceMode.addEventListener('change', syncPopup);
                            }

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
                                if (!isConsultationOnly) {
                                    const isMultiple = String(popupChoiceMode.value || 'single') === 'multiple';
                                    const normalizedChoiceMode = isMultiple ? 'multiple' : 'single';
                                    const rawMaxChoices = Number(popupMaxChoices.value || 0);
                                    const normalizedMaxChoices = Number.isFinite(rawMaxChoices)
                                        ? Math.max(Math.floor(rawMaxChoices), 0)
                                        : 0;

                                    if (hiddenChoiceModeInput) {
                                        hiddenChoiceModeInput.value = normalizedChoiceMode;
                                    }
                                    if (hiddenMaxChoicesInput) {
                                        hiddenMaxChoicesInput.value = String(isMultiple ? normalizedMaxChoices : 1);
                                    }
                                    if (hiddenAllowAnonymousVotesInput) {
                                        hiddenAllowAnonymousVotesInput.value = popupAllowAnonymousVotes.checked ? '1' : '';
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
                                }
                                if (hiddenConsultationProposalsInput) {
                                    hiddenConsultationProposalsInput.value = popupConsultationProposals.checked ? '1' : '';
                                }
                                if (hiddenProposalDiscussionsInput) {
                                    hiddenProposalDiscussionsInput.value = popupProposalDiscussions.checked ? '1' : '';
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
                                        descriptionSelector: '[data-omo-decision-vote-proposal-description]',
                                        detailsSelector: '[data-omo-decision-vote-proposal-settings]',
                                        canEdit: payload.proposalEditable === true,
                                    });
                                }
                                if (popupVoteWeightEditor) {
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
                                }

                                syncSettingsSummary();

                                if (typeof window.commonTopbarCloseModal === 'function') {
                                    window.commonTopbarCloseModal();
                                }
                            });
                        };

                        const refreshProposalLabels = function () {
                            Array.prototype.forEach.call(
                                proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]'),
                                function (card, index) {
                                    const label = card.querySelector('[data-omo-decision-vote-proposal-label]');
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
                                proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]'),
                                function (menuCard) {
                                    if (exceptCard && menuCard === exceptCard) {
                                        return;
                                    }

                                    const menuPanel = menuCard.querySelector('[data-omo-decision-vote-proposal-menu-panel]');
                                    const menuToggle = menuCard.querySelector('[data-omo-decision-vote-proposal-menu-toggle]');
                                    if (menuPanel) {
                                        menuPanel.hidden = true;
                                    }
                                    if (menuToggle) {
                                        menuToggle.setAttribute('aria-expanded', 'false');
                                    }
                                }
                            );
                        };

                        const bindProposalCard = function (card) {
                            if (!card || card.dataset.omoDecisionVoteProposalReady === '1') {
                                return;
                            }

                            const removeButton = card.querySelector('[data-omo-decision-vote-proposal-remove]');
                            const detailsButton = card.querySelector('[data-omo-decision-vote-proposal-settings]');
                            const menuToggle = card.querySelector('[data-omo-decision-vote-proposal-menu-toggle]');
                            const menuPanel = card.querySelector('[data-omo-decision-vote-proposal-menu-panel]');
                            const titleInput = card.querySelector('input[name="proposals[]"]');
                            const descriptionInput = card.querySelector('[data-omo-decision-vote-proposal-description]');
                            const descriptionEditor = card.querySelector('[data-omo-decision-vote-proposal-description-editor]');
                            const infoUrlInput = card.querySelector('[data-omo-decision-vote-proposal-info-url]');

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

                                    const proposalLabelNode = card.querySelector('[data-omo-decision-vote-proposal-label]');
                                    const modalTitle = proposalLabelNode
                                        ? String(proposalLabelNode.textContent || '').trim()
                                        : String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details');
                                    const proposalContent = payload.proposalContent || {title: true, description: true};
                                    const descriptionDetailsField = proposalContent.title && proposalContent.description
                                        ? '  <label style="display:grid;gap:6px;">'
                                            + '    <span class="generic-form-label">' + String(payload.texts && payload.texts.proposalDescriptionLabel ? payload.texts.proposalDescriptionLabel : 'Description') + '</span>'
                                            + '    <div data-omo-proposal-html-field><div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-decision-vote-proposal-modal-description></div><textarea hidden aria-hidden="true" data-omo-proposal-html-value></textarea></div>'
                                            + '  </label>'
                                        : '';
                                    const modalHtml = ''
                                        + '<div class="generic-section generic-section--stack" style="display:grid;gap:12px;">'
                                        + descriptionDetailsField
                                        + '  <label style="display:grid;gap:6px;">'
                                        + '    <span class="generic-form-label">' + String(payload.texts && payload.texts.proposalInfoUrlLabel ? payload.texts.proposalInfoUrlLabel : 'URL') + '</span>'
                                        + '    <input type="url" class="generic-form-control generic-form-control--compact" data-omo-decision-vote-proposal-modal-info-url placeholder="' + String(payload.texts && payload.texts.proposalInfoUrlPlaceholder ? payload.texts.proposalInfoUrlPlaceholder : 'https://...') + '">'
                                        + '  </label>'
                                        + '  <div style="display:flex;justify-content:flex-end;gap:8px;">'
                                        + '    <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-decision-vote-proposal-modal-cancel>Fermer</button>'
                                        + '    <button type="button" class="generic-action-button generic-action-button--main" data-omo-decision-vote-proposal-modal-apply>' + String(payload.texts && payload.texts.proposalApply ? payload.texts.proposalApply : 'Enregistrer') + '</button>'
                                        + '  </div>'
                                        + '</div>';

                                    window.commonTopbarOpenModal(modalTitle || 'Details', modalHtml, 'html');
                                    const modalBody = document.getElementById('commonTopbarModalBody');
                                    if (!modalBody) {
                                        return;
                                    }

                                    const modalDescription = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-description]');
                                    const modalInfoUrl = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-info-url]');
                                    const modalCancel = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-cancel]');
                                    const modalApply = modalBody.querySelector('[data-omo-decision-vote-proposal-modal-apply]');
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
                                    if (proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]').length < 2) {
                                        proposalList.appendChild(createProposalCard(''));
                                    }
                                    refreshProposalLabels();
                                    clearFeedback();
                                });
                            }

                            card.dataset.omoDecisionVoteProposalReady = '1';
                        };

                        const createProposalCard = function (value) {
                            const card = document.createElement('div');
                            card.className = 'omo-decision-vote__proposal-card omo-decision-proposal-card generic-section';
                            card.setAttribute('data-omo-decision-vote-proposal-card', '1');
                            card.setAttribute('draggable', 'true');
                            const proposalContent = payload.proposalContent || {};
                            const proposalTitleField = proposalContent.title
                                ? '<input type="text" name="proposals[]" class="generic-form-control generic-form-control--compact" placeholder="' + String(payload.texts && payload.texts.proposalPlaceholder ? payload.texts.proposalPlaceholder : 'Nom de la proposition') + '">'
                                : '<input type="hidden" name="proposals[]" value="">';
                            const proposalDescriptionField = !proposalContent.title && proposalContent.description
                                ? '<div data-omo-proposal-html-field><div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-decision-vote-proposal-description-editor' + (payload.proposalEditable === true ? '' : ' data-omo-proposal-html-disabled="1"') + '></div><textarea hidden aria-hidden="true" name="proposal_descriptions[]" data-omo-proposal-html-value data-omo-decision-vote-proposal-description></textarea></div>'
                                : '<textarea hidden aria-hidden="true" name="proposal_descriptions[]" data-omo-decision-vote-proposal-description></textarea>';
                            const proposalDetailsButton = proposalContent.url
                                ? '        <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-vote__proposal-menu-item" data-omo-decision-vote-proposal-settings role="menuitem">' + String(payload.texts && payload.texts.proposalDetails ? payload.texts.proposalDetails : 'Details') + '</button>'
                                : '';
                            card.innerHTML = ''
                                + '<button type="button" class="omo-decision-vote__proposal-drag generic-drag-handle generic-drag-handle--stretch" data-omo-decision-vote-proposal-drag title="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Réordonner') + '" aria-label="' + String(payload.texts && payload.texts.proposalReorder ? payload.texts.proposalReorder : 'Réordonner') + '">&#8942;&#8942;</button>'
                                + '<div class="omo-decision-vote__proposal-main">'
                                + '    <span class="omo-decision-vote__proposal-label" data-omo-decision-vote-proposal-label></span>'
                                + '    ' + proposalTitleField
                                + '    ' + proposalDescriptionField
                                + '    <input type="hidden" name="proposal_info_urls[]" value="" data-omo-decision-vote-proposal-info-url>'
                                + '    <input type="hidden" name="proposal_ids[]" value="0">'
                                + '</div>'
                                + '<div class="omo-decision-vote__proposal-menu" data-omo-decision-vote-proposal-menu>'
                                + '    <button type="button" class="generic-action-button generic-action-button--secondary omo-decision-vote__proposal-menu-toggle" data-omo-decision-vote-proposal-menu-toggle aria-haspopup="menu" aria-expanded="false" aria-label="' + String(payload.texts && payload.texts.proposalActions ? payload.texts.proposalActions : 'Actions') + '">...</button>'
                                + '    <div class="omo-decision-vote__proposal-menu-panel omo-decision-proposal-menu-panel generic-soft-panel" data-omo-decision-vote-proposal-menu-panel role="menu" hidden>'
                                + proposalDetailsButton
                                + '        <button type="button" class="generic-action-button generic-action-button--danger omo-decision-vote__proposal-menu-item" data-omo-decision-vote-proposal-remove role="menuitem">' + String(payload.texts && payload.texts.proposalRemove ? payload.texts.proposalRemove : 'Supprimer') + '</button>'
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

                        let sortable = null;
                        Array.prototype.forEach.call(proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]'), bindProposalCard);
                        refreshProposalLabels();
                        syncSettingsSummary();

                        document.addEventListener('click', function (event) {
                            if (!proposalList.contains(event.target)) {
                                closeProposalMenus();
                            }
                        });

                        if (settingsOpenButton) {
                            settingsOpenButton.addEventListener('click', function () {
                                openSettingsModal();
                            });
                        }

                        if (invitationOpenButton) {
                            invitationOpenButton.addEventListener('click', function () {
                                openInvitationModal();
                            });
                        }

                        if (invitationSendOpenButton) {
                            invitationSendOpenButton.addEventListener('click', function () {
                                openInvitationSendModal();
                            });
                        }

                        if (payload.proposalEditable && typeof window.commonCreateVerticalSortableList === 'function') {
                            sortable = window.commonCreateVerticalSortableList({
                                list: proposalList,
                                itemSelector: '[data-omo-decision-vote-proposal-card]',
                                handleSelector: '[data-omo-decision-vote-proposal-drag]',
                                draggingClass: 'is-dragging',
                                dropTargetClass: 'is-drop-target',
                                placeholderClass: 'omo-decision-vote__proposal-placeholder',
                                createPlaceholder: function (card) {
                                    const placeholder = document.createElement('div');
                                    placeholder.className = 'omo-decision-vote__proposal-placeholder';
                                    placeholder.style.height = Math.max(Number(card.getBoundingClientRect().height) || 0, 78) + 'px';
                                    return placeholder;
                                },
                                onDragStart: clearFeedback,
                                onDragEnd: refreshProposalLabels,
                                onDrop: function () {
                                    refreshProposalLabels();
                                    clearFeedback();
                                }
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
                                clearFeedback();
                            });
                        }

                        form.addEventListener('submit', function (event) {
                            event.preventDefault();

                            const submitButton = getSubmitButton();
                            Array.prototype.forEach.call(
                                form.querySelectorAll('[data-omo-decision-vote-proposal-description-editor]'),
                                function (editor) {
                                    const descriptionInput = editor.closest('[data-omo-proposal-html-field]')
                                        ? editor.closest('[data-omo-proposal-html-field]').querySelector('[data-omo-decision-vote-proposal-description]')
                                        : null;
                                    if (descriptionInput && window.omoProposalHtml && typeof window.omoProposalHtml.getValue === 'function') {
                                        descriptionInput.value = String(window.omoProposalHtml.getValue(editor) || '').trim();
                                    }
                                }
                            );

                            const formData = new FormData(form);
                            formData.delete('proposal_descriptions[]');
                            Array.prototype.forEach.call(
                                proposalList.querySelectorAll('[data-omo-decision-vote-proposal-card]'),
                                function (card) {
                                    const editor = card.querySelector('[data-omo-decision-vote-proposal-description-editor]');
                                    const descriptionInput = card.querySelector('[data-omo-decision-vote-proposal-description]');
                                    const description = editor && window.omoProposalHtml && typeof window.omoProposalHtml.getValue === 'function'
                                        ? String(window.omoProposalHtml.getValue(editor) || '').trim()
                                        : String(descriptionInput ? descriptionInput.value || '' : '').trim();
                                    formData.append('proposal_descriptions[]', description);
                                }
                            );
                            const defaultLabel = payload.texts && payload.texts.save
                                ? payload.texts.save
                                : (submitButton ? submitButton.textContent : '');
                            const savingLabel = payload.texts && payload.texts.saving ? payload.texts.saving : defaultLabel;

                            if (submitButton) {
                                submitButton.disabled = true;
                                submitButton.textContent = savingLabel;
                            }
                            clearFeedback();

                            fetch(payload.saveUrl || form.action, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                                .then(function (response) {
                                    return response.json().catch(function () {
                                        return {
                                            status: false,
                                            message: payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'
                                        };
                                    });
                                })
                                .then(function (response) {
                                    if (submitButton) {
                                        submitButton.disabled = false;
                                        submitButton.textContent = defaultLabel;
                                    }

                                    if (!response || !response.status) {
                                        setFeedback(response && response.message ? response.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'), true);
                                        return;
                                    }

                                    setFeedback(response.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);

                                    if (response.redirectUrl) {
                                        window.setTimeout(function () {
                                            if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                                window.omoDecisionOpenNestedDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, '');
                                                return;
                                            }

                                            if (typeof window.commonTopbarOpenDrawer === 'function') {
                                                window.commonTopbarOpenDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, 'fetch');
                                                return;
                                            }

                                            window.location.href = response.redirectUrl;
                                        }, 250);
                                    }
                                })
                                .catch(function () {
                                    if (submitButton) {
                                        submitButton.disabled = false;
                                        submitButton.textContent = defaultLabel;
                                    }
                                    setFeedback(payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.', true);
                                });
                        });

                        form.dataset.omoDecisionVoteReady = '1';
                    });

                    scope.querySelectorAll('[data-omo-decision-vote-response-form]').forEach(function (form) {
                        if (form.dataset.omoDecisionVoteResponseReady === '1') {
                            return;
                        }

                        const payloadNode = form.querySelector('[data-omo-decision-vote-response-data]');
                        const submitButton = form.querySelector('[data-omo-decision-vote-response-submit]');
                        const feedbackNode = form.querySelector('[data-omo-decision-vote-response-feedback]');
                        if (!payloadNode || !submitButton || !feedbackNode) {
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
                            feedbackNode.textContent = String(message || '');
                            feedbackNode.classList.toggle('is-error', !!isError);
                            feedbackNode.classList.toggle('is-success', !isError && message !== '');
                        };

                        form.addEventListener('submit', function (event) {
                            event.preventDefault();

                            if (String(payload.choiceMode || 'single') === 'multiple') {
                                const checkedChoices = form.querySelectorAll('input[name="proposal_ids[]"]:checked');
                                const rawMaxChoices = Number(payload.maxChoices || 0);
                                const maxChoices = Number.isFinite(rawMaxChoices)
                                    ? Math.max(Math.floor(rawMaxChoices), 0)
                                    : 0;
                                if (checkedChoices.length === 0) {
                                    setFeedback(payload.texts && payload.texts.multipleHint ? payload.texts.multipleHint : 'Choisissez au moins une proposition.', true);
                                    return;
                                }
                                if (maxChoices > 0 && checkedChoices.length > maxChoices) {
                                    setFeedback(payload.texts && payload.texts.multipleHint ? payload.texts.multipleHint : 'Trop de propositions selectionnees.', true);
                                    return;
                                }
                            }

                            const formData = new FormData(form);
                            const defaultLabel = payload.texts && payload.texts.save ? payload.texts.save : submitButton.textContent;
                            const savingLabel = payload.texts && payload.texts.saving ? payload.texts.saving : defaultLabel;

                            submitButton.disabled = true;
                            submitButton.textContent = savingLabel;
                            setFeedback('', false);

                            fetch(payload.saveUrl || form.action, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                                .then(function (response) {
                                    return response.json().catch(function () {
                                        return {
                                            status: false,
                                            message: payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'
                                        };
                                    });
                                })
                                .then(function (response) {
                                    submitButton.disabled = false;
                                    submitButton.textContent = defaultLabel;

                                    if (!response || !response.status) {
                                        setFeedback(response && response.message ? response.message : (payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.'), true);
                                        return;
                                    }

                                    setFeedback(response.message || (payload.texts && payload.texts.success ? payload.texts.success : ''), false);

                                    if (response.redirectUrl) {
                                        window.setTimeout(function () {
                                            if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
                                                window.omoDecisionOpenNestedDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, '');
                                                return;
                                            }

                                            if (typeof window.commonTopbarOpenDrawer === 'function') {
                                                window.commonTopbarOpenDrawer(response.drawerTitle || payload.drawerTitle || '', response.redirectUrl, 'fetch');
                                                return;
                                            }

                                            window.location.href = response.redirectUrl;
                                        }, 250);
                                    }
                                })
                                .catch(function () {
                                    submitButton.disabled = false;
                                    submitButton.textContent = defaultLabel;
                                    setFeedback(payload.texts && payload.texts.error ? payload.texts.error : 'Erreur.', true);
                                });
                        });

                        form.dataset.omoDecisionVoteResponseReady = '1';
                    });

                    scope.querySelectorAll('[data-omo-decision-vote-results-panel]').forEach(function (panel) {
                        if (panel.dataset.omoDecisionVoteResultsReady === '1') {
                            return;
                        }

                        const list = panel.querySelector('[data-omo-decision-vote-results-list]');
                        const buttons = panel.querySelectorAll('[data-omo-decision-vote-results-sort]');
                        const compareToggle = panel.querySelector('[data-omo-decision-vote-results-compare-toggle]');
                        const compareBlocks = panel.querySelectorAll('[data-omo-decision-vote-results-compare-block]');
                        if (!list) {
                            panel.dataset.omoDecisionVoteResultsReady = '1';
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
                            const items = Array.prototype.slice.call(list.querySelectorAll('[data-omo-decision-vote-result-item]'));
                            if (items.length < 2) {
                                return;
                            }

                            items.sort(function (left, right) {
                                const leftPosition = Number(left.getAttribute('data-omo-decision-vote-result-position') || 0);
                                const rightPosition = Number(right.getAttribute('data-omo-decision-vote-result-position') || 0);
                                const leftTitle = String(left.getAttribute('data-omo-decision-vote-result-title') || '');
                                const rightTitle = String(right.getAttribute('data-omo-decision-vote-result-title') || '');
                                const leftVotes = Number(left.getAttribute('data-omo-decision-vote-result-votes') || 0);
                                const rightVotes = Number(right.getAttribute('data-omo-decision-vote-result-votes') || 0);

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

                                if (leftVotes !== rightVotes) {
                                    return rightVotes - leftVotes;
                                }

                                return leftPosition - rightPosition;
                            });

                            items.forEach(function (item) {
                                list.appendChild(item);
                            });
                        };

                        const syncComparison = function () {
                            const showComparison = !!(compareToggle && compareToggle.checked);
                            compareBlocks.forEach(function (block) {
                                block.hidden = !showComparison;
                                block.setAttribute('aria-hidden', showComparison ? 'false' : 'true');
                                block.style.display = showComparison ? '' : 'none';
                            });
                        };

                        const applySortMode = function (sortMode) {
                            const normalizedSortMode = normalizeSortMode(sortMode);
                            buttons.forEach(function (button) {
                                const isActive = normalizeSortMode(button.getAttribute('data-omo-decision-vote-results-sort')) === normalizedSortMode;
                                button.classList.toggle('is-active', isActive);
                                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                            });

                            sortItems(normalizedSortMode);
                        };

                        buttons.forEach(function (button) {
                            button.addEventListener('click', function () {
                                applySortMode(button.getAttribute('data-omo-decision-vote-results-sort'));
                            });
                        });

                        if (compareToggle) {
                            compareToggle.addEventListener('change', syncComparison);
                        }

                        if (buttons.length) {
                            applySortMode('rank');
                        }
                        syncComparison();
                        panel.dataset.omoDecisionVoteResultsReady = '1';
                    });
                };
            }

            window.omoDecisionVoteInit(document);
        })();
};
