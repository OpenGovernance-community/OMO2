(function () {
    'use strict';

    var config = window.SURVEY_PROTOTYPE || {};
    var questions = Array.isArray(config.questions) ? config.questions : [];
    var omoPaths = config.omoPaths || {};
    var labels = config.labels || {};
    var persistence = config.persistence || {};
    var invite = config.invite || {};
    var privateToken = String(persistence.privateToken || '');
    var invitationToken = String(persistence.invitationToken || '');
    var isInvitation = persistence.isInvitation === true && invitationToken !== '';
    var storageKey = 'survey-organizational-maturity-prototype-v2' + (privateToken ? '-' + privateToken : (invitationToken ? '-invite-' + invitationToken : ''));
    var periods = ['today', 'tomorrow'];
    var advanceTimer = null;
    var automaticInvitationSaveAttempted = false;

    var elements = {
        welcome: document.getElementById('surveyWelcome'),
        workspace: document.getElementById('surveyWorkspace'),
        results: document.getElementById('surveyResults'),
        start: document.getElementById('surveyStart'),
        startLabel: document.getElementById('surveyStartLabel'),
        restart: document.getElementById('surveyRestart'),
        progressPrinciple: document.getElementById('surveyProgressPrinciple'),
        progressPercent: document.getElementById('surveyProgressPercent'),
        progressBar: document.getElementById('surveyProgressBar'),
        questionCard: document.getElementById('surveyQuestionCard'),
        questionNumber: document.getElementById('surveyQuestionNumber'),
        phaseLabel: document.getElementById('surveyPhaseLabel'),
        questionTitle: document.getElementById('surveyQuestionTitle'),
        principleLabel: document.getElementById('surveyPrincipleLabel'),
        principleText: document.getElementById('surveyPrincipleText'),
        responseTitle: document.getElementById('surveyResponseTitle'),
        responseHelp: document.getElementById('surveyResponseHelp'),
        response: document.getElementById('surveyResponse'),
        validation: document.getElementById('surveyValidation'),
        back: document.getElementById('surveyBack'),
        next: document.getElementById('surveyNext'),
        saveNote: document.getElementById('surveySaveNote'),
        resultsEyebrow: document.getElementById('surveyResultsEyebrow'),
        resultsTitle: document.getElementById('surveyResultsTitle'),
        resultsIntro: document.getElementById('surveyResultsIntro'),
        radar: document.getElementById('surveyRadar'),
        resultsStats: document.getElementById('surveyResultsStats'),
        resultsList: document.getElementById('surveyResultsList'),
        omoAction: document.getElementById('surveyOmoAction'),
        omoDialog: document.getElementById('surveyOmoDialog'),
        omoClose: document.getElementById('surveyOmoClose'),
        omoContent: document.getElementById('surveyOmoContent'),
        saveResult: document.getElementById('surveySaveResult'),
        saveLinks: document.getElementById('surveySaveLinks'),
        saveOmo: document.getElementById('surveySaveOmo'),
        saveDialog: document.getElementById('surveySaveDialog'),
        saveDialogEyebrow: document.getElementById('surveySaveDialogEyebrow'),
        saveDialogTitle: document.getElementById('surveySaveDialogTitle'),
        saveContent: document.getElementById('surveySaveContent'),
        saveClose: document.getElementById('surveySaveClose'),
        inviteAction: document.getElementById('surveyInviteAction'),
        inviteDialog: document.getElementById('surveyInviteDialog'),
        inviteDialogEyebrow: document.getElementById('surveyInviteDialogEyebrow'),
        inviteDialogTitle: document.getElementById('surveyInviteDialogTitle'),
        inviteContent: document.getElementById('surveyInviteContent'),
        inviteClose: document.getElementById('surveyInviteClose'),
        review: document.getElementById('surveyReview'),
        resultsRestart: document.getElementById('surveyResultsRestart')
    };

    function emptyAnswers() {
        return questions.map(function () {
            return {
                affinity: null,
                situation: { today: null, tomorrow: null }
            };
        });
    }

    function initialState() {
        return {
            version: 2,
            questionIndex: 0,
            phase: 'scale',
            activePeriod: 'today',
            manualNavigation: false,
            completed: false,
            answers: emptyAnswers()
        };
    }

    function normalizeValue(value) {
        var number = Number(value);
        return Number.isInteger(number) && number >= 1 && number <= 5 ? number : null;
    }

    function loadState() {
        var fresh = initialState();

        try {
            var saved = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
            if (!saved || saved.version !== 2 || !Array.isArray(saved.answers)) {
                return fresh;
            }

            fresh.questionIndex = Math.max(0, Math.min(questions.length - 1, Number(saved.questionIndex) || 0));
            fresh.phase = saved.phase === 'choice' ? 'choice' : 'scale';
            fresh.activePeriod = saved.activePeriod === 'tomorrow' ? 'tomorrow' : 'today';
            fresh.manualNavigation = saved.manualNavigation === true;
            fresh.completed = saved.completed === true;

            questions.forEach(function (_, questionIndex) {
                var savedAnswer = saved.answers[questionIndex] || {};
                fresh.answers[questionIndex].affinity = normalizeValue(savedAnswer.affinity);
                periods.forEach(function (period) {
                    fresh.answers[questionIndex].situation[period] = normalizeValue(savedAnswer.situation && savedAnswer.situation[period]);
                });
            });

            return fresh;
        } catch (error) {
            return fresh;
        }
    }

    var state = loadState();

    function hydratePersistedAnswers() {
        var answers = Array.isArray(persistence.answers) ? persistence.answers : [];
        if (answers.length !== questions.length || (privateToken === '' && invitationToken === '')) {
            return false;
        }

        state = initialState();
        answers.forEach(function (answer, index) {
            state.answers[index].affinity = normalizeValue(answer && answer.affinity);
            state.answers[index].situation.today = normalizeValue(answer && answer.situation && answer.situation.today);
            state.answers[index].situation.tomorrow = normalizeValue(answer && answer.situation && answer.situation.tomorrow);
        });
        return state.answers.every(function (answer) {
            return answer.affinity !== null
                && answer.situation.today !== null
                && answer.situation.tomorrow !== null;
        });
    }

    var hasPersistedAnswers = hydratePersistedAnswers();
    var savedAnswers = hasPersistedAnswers ? cloneAnswers(state.answers) : null;
    var savedLinks = persistence.links && typeof persistence.links === 'object' ? persistence.links : null;

    function saveState() {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (error) {
            // Local persistence is a convenience; the prototype still works without it.
        }
    }

    function cloneAnswers(answers) {
        return answers.map(function (answer) {
            return {
                affinity: normalizeValue(answer.affinity),
                situation: {
                    today: normalizeValue(answer.situation.today),
                    tomorrow: normalizeValue(answer.situation.tomorrow)
                }
            };
        });
    }

    function isSurveyDirty() {
        if (!savedAnswers || savedAnswers.length !== state.answers.length) {
            return true;
        }

        return state.answers.some(function (answer, index) {
            var savedAnswer = savedAnswers[index];
            return answer.affinity !== savedAnswer.affinity
                || answer.situation.today !== savedAnswer.situation.today
                || answer.situation.tomorrow !== savedAnswer.situation.tomorrow;
        });
    }

    function clearState() {
        state = initialState();
        try {
            window.localStorage.removeItem(storageKey);
        } catch (error) {
            // Ignore storage restrictions and reset the in-memory state.
        }
    }

    function interpolate(template, variables) {
        return String(template || '').replace(/\{([a-zA-Z]+)\}/g, function (match, key) {
            return Object.prototype.hasOwnProperty.call(variables, key) ? String(variables[key]) : match;
        });
    }

    function el(tagName, className, text) {
        var node = document.createElement(tagName);
        if (className) {
            node.className = className;
        }
        if (text !== undefined && text !== null) {
            node.textContent = String(text);
        }
        return node;
    }

    function hasAnyAnswer() {
        return state.answers.some(function (answer) {
            return answer.affinity !== null || periods.some(function (period) {
                return answer.situation[period] !== null;
            });
        });
    }

    function answerComplete(answerType) {
        var currentAnswer = state.answers[state.questionIndex];
        if (answerType === 'affinity') {
            return currentAnswer.affinity !== null;
        }
        var answer = currentAnswer.situation;
        return answer.today !== null && answer.tomorrow !== null;
    }

    function updateWelcome() {
        var saved = hasAnyAnswer() || state.completed;
        elements.startLabel.textContent = saved ? labels.resume : labels.start;
        elements.restart.hidden = !saved;
    }

    function showOnly(section) {
        elements.welcome.hidden = section !== 'welcome';
        elements.workspace.hidden = section !== 'workspace';
        elements.results.hidden = section !== 'results';
    }

    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function showWelcome() {
        updateWelcome();
        showOnly('welcome');
        scrollToTop();
    }

    function showQuestion() {
        if (!questions.length) {
            return;
        }

        window.clearTimeout(advanceTimer);
        advanceTimer = null;
        var currentAnswerType = state.phase === 'scale' ? 'affinity' : 'situation';
        if (state.manualNavigation && !answerComplete(currentAnswerType)) {
            state.manualNavigation = false;
        }
        state.completed = false;
        saveState();
        showOnly('workspace');

        var question = questions[state.questionIndex];
        var stepIndex = state.questionIndex * 2 + (state.phase === 'choice' ? 2 : 1);
        var totalSteps = questions.length * 2;
        var percent = Math.round(stepIndex / totalSteps * 100);

        elements.progressPrinciple.textContent = interpolate(labels.progressPrinciple, {
            current: question.number,
            total: questions.length
        });
        elements.progressPercent.textContent = interpolate(labels.progressComplete, { percent: percent });
        elements.progressBar.style.width = percent + '%';
        elements.questionNumber.textContent = question.number;
        elements.phaseLabel.textContent = state.phase === 'scale' ? labels.scalePhaseLabel : labels.choicePhaseLabel;
        elements.questionTitle.textContent = question.title;
        elements.principleLabel.textContent = labels.principleLabel;
        elements.principleText.textContent = question.principle;
        elements.responseTitle.textContent = state.phase === 'scale' ? labels.scaleTitle : labels.choiceTitle;
        elements.responseHelp.textContent = state.phase === 'scale' ? labels.scaleHelp : labels.choiceHelp;
        elements.validation.textContent = labels.incomplete;
        elements.validation.hidden = true;
        elements.back.textContent = labels.back;
        elements.saveNote.textContent = labels.saveStatus;

        if (state.phase === 'scale') {
            renderScaleResponse(question);
        } else {
            renderChoiceResponse(question);
        }

        updateNavigation();
        scrollToTop();
    }

    function renderScaleResponse() {
        var fieldset = el('fieldset', 'survey-period-panel survey-affinity-panel');
        var legend = el('legend');
        var heading = el('span', 'survey-period-panel__heading');
        var selected = state.answers[state.questionIndex].affinity;

        heading.appendChild(el('strong', '', labels.radarAffinity));
        if (selected !== null) {
            heading.appendChild(el('span', '', labels.periodDone));
        }
        legend.appendChild(heading);
        fieldset.appendChild(legend);

        var scale = el('div', 'survey-scale');
        for (var value = 1; value <= 5; value += 1) {
            var wrapper = el('div', 'survey-scale__option');
            var input = document.createElement('input');
            var label = document.createElement('label');
            var inputId = 'affinity-' + state.questionIndex + '-' + value;

            input.type = 'radio';
            input.name = 'affinity-' + state.questionIndex;
            input.id = inputId;
            input.value = value;
            input.checked = selected === value;
            input.addEventListener('change', handleScaleChange);

            label.htmlFor = inputId;
            label.appendChild(el('span', 'survey-scale__number', value));
            label.appendChild(el('span', '', labels.scale[value]));
            wrapper.append(input, label);
            scale.appendChild(wrapper);
        }

        fieldset.appendChild(scale);
        elements.response.replaceChildren(fieldset);
    }

    function handleScaleChange(event) {
        state.answers[state.questionIndex].affinity = normalizeValue(event.target.value);
        saveState();
        renderScaleResponse();
        updateNavigation();
        scheduleAdvance();
    }

    function renderChoiceResponse(question) {
        var host = el('div', 'survey-choice-response');
        var tabs = el('div', 'survey-period-tabs');
        var activePeriod = state.activePeriod;

        periods.forEach(function (period) {
            var tab = el('button', 'survey-period-tab' + (period === activePeriod ? ' is-active' : ''));
            var tabCopy = el('span', 'survey-period-tab__copy');
            var value = state.answers[state.questionIndex].situation[period];

            tab.type = 'button';
            tab.dataset.period = period;
            tab.setAttribute('aria-pressed', period === activePeriod ? 'true' : 'false');
            tabCopy.appendChild(el('strong', '', period === 'today' ? labels.today : labels.tomorrow));
            tabCopy.appendChild(el('small', '', period === 'today' ? labels.todayHelp : labels.tomorrowHelp));
            tab.appendChild(tabCopy);
            if (value !== null) {
                tab.appendChild(el('span', 'survey-period-tab__status', labels.periodDone));
            }
            tab.addEventListener('click', function () {
                state.activePeriod = period;
                saveState();
                renderChoiceResponse(question);
            });
            tabs.appendChild(tab);
        });

        var list = el('div', 'survey-choice-list');
        question.options.forEach(function (option) {
            var wrapper = el('div', 'survey-choice');
            var input = document.createElement('input');
            var label = document.createElement('label');
            var copy = el('span', 'survey-choice__copy');
            var inputId = 'choice-' + state.questionIndex + '-' + activePeriod + '-' + option.value;

            input.type = 'radio';
            input.name = 'choice-' + state.questionIndex + '-' + activePeriod;
            input.id = inputId;
            input.value = option.value;
            input.checked = state.answers[state.questionIndex].situation[activePeriod] === option.value;
            input.addEventListener('change', function () {
                handleChoiceChange(activePeriod, option.value, question);
            });

            label.htmlFor = inputId;
            label.appendChild(el('span', 'survey-choice__number', option.value));
            copy.appendChild(el('strong', '', option.title));
            copy.appendChild(el('p', '', option.description));
            label.appendChild(copy);
            wrapper.append(input, label);
            list.appendChild(wrapper);
        });

        host.append(tabs, list);
        elements.response.replaceChildren(host);
    }

    function revealActivePeriod() {
        window.requestAnimationFrame(function () {
            var activeTab = elements.response.querySelector('.survey-period-tab.is-active');
            var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (activeTab) {
                activeTab.scrollIntoView({
                    behavior: reducedMotion ? 'auto' : 'smooth',
                    block: 'start'
                });
            }
        });
    }

    function handleChoiceChange(period, value, question) {
        var movesToTomorrow = period === 'today' && state.answers[state.questionIndex].situation.tomorrow === null;

        state.answers[state.questionIndex].situation[period] = normalizeValue(value);
        if (movesToTomorrow) {
            state.activePeriod = 'tomorrow';
        }
        saveState();
        renderChoiceResponse(question);
        updateNavigation();
        if (movesToTomorrow) {
            revealActivePeriod();
        }
        if (answerComplete('situation')) {
            scheduleAdvance();
        }
    }

    function updateNavigation() {
        var answerType = state.phase === 'scale' ? 'affinity' : 'situation';
        var isLast = state.questionIndex === questions.length - 1 && state.phase === 'choice';
        elements.next.textContent = isLast ? labels.results : labels.next;
        elements.next.disabled = !answerComplete(answerType);
        elements.next.hidden = !state.manualNavigation;
    }

    function scheduleAdvance() {
        if (state.manualNavigation) {
            return;
        }
        window.clearTimeout(advanceTimer);
        advanceTimer = window.setTimeout(function () {
            advanceTimer = null;
            goNext();
        }, 520);
    }

    function goBack() {
        window.clearTimeout(advanceTimer);
        advanceTimer = null;
        state.manualNavigation = true;
        if (state.phase === 'choice') {
            state.phase = 'scale';
        } else if (state.questionIndex > 0) {
            state.questionIndex -= 1;
            state.phase = 'choice';
            state.activePeriod = 'today';
        } else {
            saveState();
            showWelcome();
            return;
        }
        saveState();
        showQuestion();
    }

    function goNext() {
        var answerType = state.phase === 'scale' ? 'affinity' : 'situation';
        if (!answerComplete(answerType)) {
            elements.validation.hidden = false;
            return;
        }

        if (state.phase === 'scale') {
            state.phase = 'choice';
            state.activePeriod = state.answers[state.questionIndex].situation.today === null ? 'today' : 'tomorrow';
        } else if (state.questionIndex < questions.length - 1) {
            state.questionIndex += 1;
            state.phase = 'scale';
            state.activePeriod = 'today';
        } else {
            state.completed = true;
            saveState();
            showResults();
            return;
        }

        saveState();
        showQuestion();
    }

    function average(values) {
        var total = values.reduce(function (sum, value) { return sum + value; }, 0);
        return values.length ? (total / values.length).toFixed(1) : '0.0';
    }

    function signedGap(value) {
        return value > 0 ? '+' + value : String(value);
    }

    function gapLabel(gap) {
        if (gap === 0) {
            return labels.sameValue;
        }
        var template = Math.abs(gap) === 1 ? labels.gapValue : labels.gapValuePlural;
        return interpolate(template, { gap: signedGap(gap) });
    }

    function omoRecommendations() {
        return state.answers.map(function (answer, index) {
            var today = normalizeValue(answer.situation.today) || 1;
            var tomorrow = normalizeValue(answer.situation.tomorrow) || 1;
            var affinity = normalizeValue(answer.affinity) || 1;
            var target = Math.max(tomorrow, affinity);
            var potential = (5 - today) * 6 + target * 5 + Math.max(0, target - today) * 2;

            return {
                index: index,
                question: questions[index],
                today: today,
                tomorrow: tomorrow,
                affinity: affinity,
                target: target,
                potential: potential
            };
        }).sort(function (first, second) {
            return second.potential - first.potential || first.index - second.index;
        }).slice(0, 3);
    }

    function createOmoStage(stage) {
        var item = el('article', 'survey-omo-stage' + (stage.level === 5 ? ' survey-omo-stage--risk' : ''));
        var copy = el('div', 'survey-omo-stage__copy');
        var heading = el('h4');

        item.appendChild(el('span', 'survey-omo-stage__number', stage.level));
        heading.appendChild(el('span', 'survey-omo-stage__label', interpolate(labels.omoStage, { level: stage.level })));
        heading.appendChild(document.createTextNode(' ' + stage.title));
        copy.append(heading, el('p', '', stage.description));
        item.appendChild(copy);
        return item;
    }

    function renderOmoDialog() {
        var fragment = document.createDocumentFragment();
        var recommendations = omoRecommendations();

        fragment.appendChild(el('p', 'survey-omo-intro', labels.omoIntro));

        recommendations.forEach(function (recommendation) {
            var path = omoPaths[recommendation.question.number];
            if (!path || !Array.isArray(path.stages)) {
                return;
            }

            var axis = el('article', 'survey-omo-axis');
            var header = el('header', 'survey-omo-axis__header');
            var copy = el('div', 'survey-omo-axis__copy');
            var stages = el('div', 'survey-omo-stages');
            var firstLevel = Math.min(recommendation.today, recommendation.target);
            var lastLevel = recommendation.target;

            copy.appendChild(el('p', 'survey-eyebrow', interpolate(labels.omoAxisLabel, {
                number: recommendation.question.number
            })));
            copy.appendChild(el('h3', '', recommendation.question.title));
            header.append(copy, el('p', 'survey-omo-axis__scores', interpolate(labels.omoScores, {
                today: recommendation.today,
                tomorrow: recommendation.tomorrow,
                affinity: recommendation.affinity
            })));
            axis.appendChild(header);
            axis.appendChild(el('p', 'survey-omo-path', interpolate(labels.omoPath, {
                start: recommendation.today,
                target: recommendation.target
            })));

            path.stages.forEach(function (stage) {
                if (stage.level >= firstLevel && stage.level <= lastLevel) {
                    stages.appendChild(createOmoStage(stage));
                }
            });
            axis.appendChild(stages);

            if (lastLevel === 5) {
                axis.appendChild(el('p', 'survey-omo-risk', labels.omoRisk));
            }
            fragment.appendChild(axis);
        });

        elements.omoContent.replaceChildren(fragment);
    }

    function openOmoDialog() {
        renderOmoDialog();
        if (typeof elements.omoDialog.showModal === 'function') {
            if (!elements.omoDialog.open) {
                elements.omoDialog.showModal();
            }
            return;
        }
        elements.omoDialog.setAttribute('open', 'open');
    }

    function closeOmoDialog() {
        if (typeof elements.omoDialog.close === 'function' && elements.omoDialog.open) {
            elements.omoDialog.close();
            return;
        }
        elements.omoDialog.removeAttribute('open');
    }

    function openSaveDialog() {
        if (typeof elements.saveDialog.showModal === 'function') {
            if (!elements.saveDialog.open) {
                elements.saveDialog.showModal();
            }
            return;
        }
        elements.saveDialog.setAttribute('open', 'open');
    }

    function closeSaveDialog() {
        if (typeof elements.saveDialog.close === 'function' && elements.saveDialog.open) {
            elements.saveDialog.close();
            return;
        }
        elements.saveDialog.removeAttribute('open');
    }

    function copySaveLink(input, button) {
        var copied = function () {
            button.textContent = labels.saveCopied;
            window.setTimeout(function () {
                button.textContent = labels.saveCopy;
            }, 1500);
        };

        input.select();
        input.setSelectionRange(0, input.value.length);
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(input.value).then(copied).catch(function () {
                document.execCommand('copy');
                copied();
            });
            return;
        }
        document.execCommand('copy');
        copied();
    }

    function createSavedLink(label, url, isPrivate) {
        var block = el('section', 'survey-save-link');
        var field = document.createElement('input');
        var copy = el('button', 'generic-action-button generic-action-button--secondary', labels.saveCopy);

        field.type = 'text';
        field.className = 'generic-form-control';
        field.value = url;
        field.readOnly = true;
        field.setAttribute('aria-label', label);
        copy.type = 'button';
        copy.addEventListener('click', function () {
            copySaveLink(field, copy);
        });

        block.appendChild(el('strong', '', label));
        block.append(field, copy);
        if (isPrivate) {
            block.appendChild(el('p', 'survey-save-link__help', labels.savePrivateHelp));
        }
        return block;
    }

    function renderSavedLinks(result) {
        var fragment = document.createDocumentFragment();
        var associate = el('button', 'generic-action-button generic-action-button--main', labels.saveAssociate);

        elements.saveDialogEyebrow.textContent = labels.saveEyebrow;
        elements.saveDialogTitle.textContent = labels.saveTitle;
        fragment.appendChild(createSavedLink(labels.savePublicLabel, result.publicUrl, false));
        fragment.appendChild(createSavedLink(labels.savePrivateLabel, result.privateUrl, true));
        associate.type = 'button';
        associate.addEventListener('click', function () {
            window.location.assign(result.associateUrl);
        });
        fragment.appendChild(associate);
        elements.saveContent.replaceChildren(fragment);
    }

    function renderSaveError() {
        elements.saveDialogEyebrow.textContent = labels.saveEyebrow;
        elements.saveDialogTitle.textContent = labels.saveTitle;
        elements.saveContent.replaceChildren(el('p', 'survey-save-error', labels.saveError));
        openSaveDialog();
    }

    function setSaveButtonsBusy(isBusy) {
        [elements.saveResult, elements.saveLinks, elements.saveOmo].forEach(function (button) {
            button.disabled = isBusy;
        });
        elements.saveResult.textContent = isBusy ? labels.saveSaving : elements.saveResult.textContent;
        elements.saveLinks.textContent = labels.saveLinks;
        elements.saveOmo.textContent = labels.saveOmo;
    }

    function updateSaveResultAction() {
        var hasSavedResult = savedAnswers !== null && (isInvitation || savedLinks !== null);
        var dirty = isSurveyDirty();

        elements.saveResult.disabled = hasSavedResult && !dirty;
        elements.saveResult.textContent = !hasSavedResult
            ? labels.saveResult
            : (dirty ? labels.saveChanges : labels.saveSaved);
        elements.saveLinks.textContent = labels.saveLinks;
        elements.saveOmo.textContent = labels.saveOmo;
        elements.saveLinks.hidden = isInvitation;
        elements.saveOmo.hidden = isInvitation;
    }

    function saveCurrentResult() {
        if (savedAnswers !== null && (isInvitation || savedLinks !== null) && !isSurveyDirty()) {
            return Promise.resolve(isInvitation ? { status: true, invitation: true } : savedLinks);
        }

        setSaveButtonsBusy(true);
        return window.fetch(String(persistence.saveEndpoint || '/survey/api/save.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                answers: state.answers,
                privateToken: privateToken,
                invitationToken: invitationToken
            })
        }).then(function (response) {
            return response.json().catch(function () {
                return { status: false };
            });
        }).then(function (result) {
            if (!result || result.status !== true) {
                throw new Error('save_failed');
            }
            if (isInvitation) {
                if (result.invitation !== true) {
                    throw new Error('save_failed');
                }
                savedAnswers = cloneAnswers(state.answers);
                return result;
            }
            if (!result.privateToken || !result.publicUrl || !result.privateUrl || !result.associateUrl) {
                throw new Error('save_failed');
            }
            privateToken = String(result.privateToken);
            persistence.privateToken = privateToken;
            savedAnswers = cloneAnswers(state.answers);
            savedLinks = {
                publicUrl: result.publicUrl,
                privateUrl: result.privateUrl,
                associateUrl: result.associateUrl
            };
            persistence.links = savedLinks;
            if (window.history && typeof window.history.replaceState === 'function') {
                window.history.replaceState({}, '', '/survey/?edit=' + encodeURIComponent(privateToken));
            }
            return result;
        }).finally(function () {
            setSaveButtonsBusy(false);
            updateSaveResultAction();
        });
    }

    function openInviteDialog() {
        var organizations = Array.isArray(invite.organizations) ? invite.organizations : [];
        if (organizations.length === 0) {
            if (invite.authenticated === true) {
                window.alert(labels.inviteEmpty);
                return;
            }
            window.location.assign(String(invite.loginUrl || '/survey/invite.php'));
            return;
        }
        renderInviteDialog(organizations[0].id);
        if (typeof elements.inviteDialog.showModal === 'function') {
            if (!elements.inviteDialog.open) {
                elements.inviteDialog.showModal();
            }
            return;
        }
        elements.inviteDialog.setAttribute('open', 'open');
    }

    function closeInviteDialog() {
        if (typeof elements.inviteDialog.close === 'function' && elements.inviteDialog.open) {
            elements.inviteDialog.close();
            return;
        }
        elements.inviteDialog.removeAttribute('open');
    }

    function getInviteOrganization(organizationId) {
        return (Array.isArray(invite.organizations) ? invite.organizations : []).filter(function (organization) {
            return Number(organization.id) === Number(organizationId);
        })[0] || null;
    }

    function inviteCheckbox(kind, option) {
        var label = el('label', 'survey-invite-dialog__check');
        var input = document.createElement('input');
        var copy = el('span', 'survey-invite-dialog__check-copy');
        input.type = 'checkbox';
        input.value = String(option.id);
        input.setAttribute('data-invite-' + kind, '1');
        copy.appendChild(el('strong', '', option.label || option.name || ''));
        if (option.email) {
            copy.appendChild(el('small', '', option.email));
        }
        label.append(input, copy);
        return label;
    }

    function renderInviteDialog(organizationId) {
        var organization = getInviteOrganization(organizationId);
        if (!organization) {
            return;
        }
        var fragment = document.createDocumentFragment();
        var form = el('form', 'survey-invite-dialog__form');
        var organizationLabel = el('label', 'survey-invite-dialog__organization');
        var select = document.createElement('select');
        var tabs = el('div', 'survey-invite-dialog__tabs');
        var panels = el('div', 'survey-invite-dialog__panels');
        var feedback = el('p', 'survey-invite-dialog__feedback');
        var submit = el('button', 'generic-action-button generic-action-button--main', labels.inviteSend);

        elements.inviteDialogEyebrow.textContent = labels.inviteEyebrow;
        elements.inviteDialogTitle.textContent = labels.inviteTitle;
        form.appendChild(el('p', 'survey-omo-intro', labels.inviteIntro));
        organizationLabel.appendChild(el('span', '', labels.inviteOrganization));
        select.className = 'generic-form-control';
        (Array.isArray(invite.organizations) ? invite.organizations : []).forEach(function (item) {
            var option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = String(item.name);
            option.selected = Number(item.id) === Number(organization.id);
            select.appendChild(option);
        });
        select.addEventListener('change', function () { renderInviteDialog(select.value); });
        organizationLabel.appendChild(select);
        form.appendChild(organizationLabel);

        [
            { key: 'holons', label: labels.inviteHolons, options: organization.holons || [] },
            { key: 'members', label: labels.inviteMembers, options: organization.members || [] },
            { key: 'emails', label: labels.inviteEmails, options: [] }
        ].forEach(function (definition, index) {
            var tab = el('button', 'survey-invite-dialog__tab', definition.label);
            var panel = el('section', 'survey-invite-dialog__panel');
            tab.type = 'button';
            tab.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
            tab.addEventListener('click', function () {
                tabs.querySelectorAll('button').forEach(function (button) { button.setAttribute('aria-selected', 'false'); });
                panels.querySelectorAll('section').forEach(function (item) { item.hidden = true; });
                tab.setAttribute('aria-selected', 'true');
                panel.hidden = false;
            });
            if (definition.key === 'emails') {
                var textarea = document.createElement('textarea');
                textarea.className = 'generic-form-control';
                textarea.rows = 5;
                textarea.placeholder = labels.inviteEmailPlaceholder;
                textarea.setAttribute('data-invite-emails', '1');
                panel.append(textarea, el('p', 'survey-invite-dialog__help', labels.inviteEmailHelp));
            } else {
                var list = el('div', 'survey-invite-dialog__list');
                definition.options.forEach(function (option) { list.appendChild(inviteCheckbox(definition.key, option)); });
                panel.appendChild(list);
            }
            if (index !== 0) { panel.hidden = true; }
            tabs.appendChild(tab);
            panels.appendChild(panel);
        });
        form.append(tabs, panels);
        feedback.hidden = true;
        submit.type = 'submit';
        form.append(feedback, submit);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            submit.disabled = true;
            submit.textContent = labels.inviteSending;
            feedback.hidden = true;
            var emailField = form.querySelector('[data-invite-emails]');
            var emails = String(emailField ? emailField.value : '').split(/[\s,;]+/).filter(Boolean);
            var values = function (selector) {
                return Array.prototype.slice.call(form.querySelectorAll(selector + ':checked')).map(function (input) { return Number(input.value); });
            };
            window.fetch(String(invite.endpoint || '/survey/api/invitations.php'), {
                method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ organizationId: Number(organization.id), holonIds: values('[data-invite-holons]'), userIds: values('[data-invite-members]'), emails: emails })
            }).then(function (response) { return response.json().catch(function () { return { status: false }; }); }).then(function (result) {
                if (!result || result.status !== true) { throw new Error('invite_failed'); }
                feedback.textContent = interpolate(labels.inviteSent, { count: Array.isArray(result.emails) ? result.emails.length : 0 });
                feedback.classList.remove('survey-invite-dialog__feedback--error');
                feedback.hidden = false;
            }).catch(function (error) {
                feedback.textContent = (error && error.message === 'invite_failed') ? labels.inviteError : labels.inviteError;
                feedback.classList.add('survey-invite-dialog__feedback--error');
                feedback.hidden = false;
            }).finally(function () {
                submit.disabled = false;
                submit.textContent = labels.inviteSend;
            });
        });
        fragment.appendChild(form);
        elements.inviteContent.replaceChildren(fragment);
    }

    function svgEl(tagName, attributes, text) {
        var node = document.createElementNS('http://www.w3.org/2000/svg', tagName);
        Object.keys(attributes || {}).forEach(function (name) {
            node.setAttribute(name, attributes[name]);
        });
        if (text !== undefined) {
            node.textContent = String(text);
        }
        return node;
    }

    function radarPoint(index, value, center, radius) {
        var angle = -Math.PI / 2 + index * (Math.PI * 2 / questions.length);
        var distance = radius * normalizeValue(value) / 5;
        return {
            x: center + Math.cos(angle) * distance,
            y: center + Math.sin(angle) * distance
        };
    }

    function renderRadar() {
        var size = 700;
        var center = size / 2;
        var radius = 220;
        var svg = svgEl('svg', {
            viewBox: '0 0 ' + size + ' ' + size,
            class: 'survey-radar__svg',
            'aria-hidden': 'true'
        });
        svg.appendChild(svgEl('title', {}, labels.radarTitle));

        svg.appendChild(svgEl('circle', {
            cx: center,
            cy: center,
            r: radius,
            class: 'survey-radar__risk-band'
        }));
        svg.appendChild(svgEl('circle', {
            cx: center,
            cy: center,
            r: radius * 4 / 5,
            class: 'survey-radar__safe-center'
        }));

        for (var level = 1; level <= 5; level += 1) {
            var levelRadius = radius * level / 5;
            svg.appendChild(svgEl('circle', {
                cx: center,
                cy: center,
                r: levelRadius,
                class: 'survey-radar__grid-circle' + (level === 5 ? ' survey-radar__grid-circle--risk' : '')
            }));
            svg.appendChild(svgEl('text', {
                x: center + 7,
                y: center - levelRadius + 15,
                class: 'survey-radar__level' + (level === 5 ? ' survey-radar__level--risk' : '')
            }, level));
        }

        questions.forEach(function (question, index) {
            var outerPoint = radarPoint(index, 5, center, radius);
            var labelPoint = radarPoint(index, 5, center, radius + 30);
            svg.appendChild(svgEl('line', {
                x1: center,
                y1: center,
                x2: outerPoint.x,
                y2: outerPoint.y,
                class: 'survey-radar__axis'
            }));
            svg.appendChild(svgEl('circle', {
                cx: labelPoint.x,
                cy: labelPoint.y,
                r: 15,
                class: 'survey-radar__axis-label-bg'
            }));
            svg.appendChild(svgEl('text', {
                x: labelPoint.x,
                y: labelPoint.y + 4,
                class: 'survey-radar__axis-label'
            }, question.number));
        });

        var series = [
            {
                key: 'affinity',
                values: state.answers.map(function (answer) { return answer.affinity; })
            },
            {
                key: 'tomorrow',
                values: state.answers.map(function (answer) { return answer.situation.tomorrow; })
            },
            {
                key: 'today',
                values: state.answers.map(function (answer) { return answer.situation.today; })
            }
        ];

        series.forEach(function (item) {
            var toggle = document.querySelector('[data-radar-series="' + item.key + '"]');
            if (toggle && !toggle.checked) {
                return;
            }
            var points = item.values.map(function (value, index) {
                var point = radarPoint(index, value, center, radius);
                return point.x + ',' + point.y;
            }).join(' ');
            svg.appendChild(svgEl('polygon', {
                points: points,
                class: 'survey-radar__series survey-radar__series--' + item.key
            }));
            item.values.forEach(function (value, index) {
                var point = radarPoint(index, value, center, radius);
                svg.appendChild(svgEl('circle', {
                    cx: point.x,
                    cy: point.y,
                    r: 4,
                    class: 'survey-radar__point survey-radar__point--' + item.key
                }));
            });
        });

        var key = el('ol', 'survey-radar-key');
        questions.forEach(function (question) {
            var item = el('li');
            item.appendChild(el('span', '', question.number));
            item.appendChild(el('strong', '', question.title));
            key.appendChild(item);
        });
        elements.radar.replaceChildren(svg, key);
    }

    function showResults() {
        showOnly('results');
        elements.resultsEyebrow.textContent = labels.resultsEyebrow;
        elements.resultsTitle.textContent = labels.resultsTitle;
        elements.resultsIntro.textContent = labels.resultsIntro;
        updateSaveResultAction();
        elements.omoAction.textContent = labels.omoAction;
        elements.review.textContent = labels.review;
        elements.resultsRestart.textContent = labels.restart;

        var todayValues = state.answers.map(function (answer) { return answer.situation.today; });
        var tomorrowValues = state.answers.map(function (answer) { return answer.situation.tomorrow; });
        var affinityValues = state.answers.map(function (answer) { return answer.affinity; });
        var gaps = state.answers.map(function (answer, index) {
            return { index: index, gap: answer.situation.tomorrow - answer.situation.today };
        });
        var largestGap = gaps.reduce(function (largest, item) {
            return item.gap > largest.gap ? item : largest;
        }, gaps[0]);

        var todayStat = createResultStat(labels.todayAverage, average(todayValues));
        var tomorrowStat = createResultStat(labels.tomorrowAverage, average(tomorrowValues));
        var affinityStat = createResultStat(labels.affinityAverage, average(affinityValues));
        var gapStat = createResultStat(labels.largestGap, questions[largestGap.index].title, true);
        elements.resultsStats.replaceChildren(todayStat, tomorrowStat, affinityStat, gapStat);
        renderRadar();

        var fragment = document.createDocumentFragment();
        questions.forEach(function (question, index) {
            var answer = state.answers[index];
            var gap = answer.situation.tomorrow - answer.situation.today;
            var card = el('article', 'survey-result-card');
            var copy = el('div', 'survey-result-card__copy');
            var situations = el('div', 'survey-result-card__situations');
            var visual = el('div', 'survey-result-card__visual');
            var line = el('div', 'survey-result-card__line');

            copy.appendChild(el('span', '', interpolate(labels.progressPrinciple, { current: question.number, total: questions.length })));
            copy.appendChild(el('h2', '', question.title));
            situations.appendChild(el('span', '', interpolate(labels.currentSituation, {
                title: question.options[answer.situation.today - 1].title
            })));
            situations.appendChild(el('span', '', interpolate(labels.desiredSituation, {
                title: question.options[answer.situation.tomorrow - 1].title
            })));
            situations.appendChild(el('span', '', interpolate(labels.affinityValue, { value: answer.affinity })));
            copy.appendChild(situations);

            line.appendChild(el('span', '', gapLabel(gap)));
            visual.appendChild(el('span', 'survey-result-card__score', answer.situation.today));
            visual.appendChild(line);
            visual.appendChild(el('span', 'survey-result-card__score survey-result-card__score--tomorrow', answer.situation.tomorrow));
            card.append(copy, visual);
            fragment.appendChild(card);
        });
        elements.resultsList.replaceChildren(fragment);
        scrollToTop();

        if (isInvitation && savedAnswers === null && !automaticInvitationSaveAttempted) {
            automaticInvitationSaveAttempted = true;
            window.setTimeout(function () {
                saveCurrentResult().catch(function () {
                    // The local answers remain available and the manual save button is re-enabled.
                });
            }, 0);
        }
    }

    function createResultStat(label, value, wide) {
        var stat = el('div', 'survey-result-stat' + (wide ? ' survey-result-stat--wide' : ''));
        stat.appendChild(el('span', '', label));
        stat.appendChild(el('strong', '', value));
        return stat;
    }

    function restartSurvey() {
        if ((hasAnyAnswer() || state.completed) && !window.confirm(labels.restartDialog)) {
            return;
        }
        clearState();
        updateWelcome();
        showQuestion();
    }

    elements.start.addEventListener('click', function () {
        if (state.completed) {
            showResults();
        } else {
            showQuestion();
        }
    });
    elements.restart.addEventListener('click', restartSurvey);
    elements.resultsRestart.addEventListener('click', restartSurvey);
    elements.back.addEventListener('click', goBack);
    elements.next.addEventListener('click', goNext);
    elements.omoAction.addEventListener('click', openOmoDialog);
    elements.omoClose.addEventListener('click', closeOmoDialog);
    elements.omoDialog.addEventListener('click', function (event) {
        if (event.target === elements.omoDialog) {
            closeOmoDialog();
        }
    });
    elements.saveResult.addEventListener('click', function () {
        saveCurrentResult().then(function (result) {
            if (!isInvitation) {
                savedLinks = result;
            }
        }).catch(renderSaveError);
    });
    elements.saveLinks.addEventListener('click', function () {
        if (savedLinks !== null) {
            renderSavedLinks(savedLinks);
            openSaveDialog();
            return;
        }
        saveCurrentResult().then(function (result) {
            renderSavedLinks(result);
            openSaveDialog();
        }).catch(renderSaveError);
    });
    elements.saveOmo.addEventListener('click', function () {
        if (savedLinks !== null && !isSurveyDirty()) {
            window.location.assign(savedLinks.associateUrl);
            return;
        }
        saveCurrentResult().then(function (result) {
            window.location.assign(result.associateUrl);
        }).catch(renderSaveError);
    });
    elements.saveClose.addEventListener('click', closeSaveDialog);
    elements.saveDialog.addEventListener('click', function (event) {
        if (event.target === elements.saveDialog) {
            closeSaveDialog();
        }
    });
    if (elements.inviteAction) {
        elements.inviteAction.addEventListener('click', openInviteDialog);
    }
    elements.inviteClose.addEventListener('click', closeInviteDialog);
    elements.inviteDialog.addEventListener('click', function (event) {
        if (event.target === elements.inviteDialog) {
            closeInviteDialog();
        }
    });
    elements.review.addEventListener('click', function () {
        state.completed = false;
        state.manualNavigation = true;
        state.questionIndex = questions.length - 1;
        state.phase = 'choice';
        state.activePeriod = 'today';
        saveState();
        showQuestion();
    });
    document.querySelectorAll('[data-radar-series]').forEach(function (toggle) {
        toggle.addEventListener('change', renderRadar);
    });

    if (hasPersistedAnswers) {
        state.completed = true;
        showResults();
    } else {
        updateWelcome();
        showWelcome();
    }
    if (invite.openDialog === true) {
        window.setTimeout(openInviteDialog, 0);
    }
}());
