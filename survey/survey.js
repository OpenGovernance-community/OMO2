(function () {
    'use strict';

    var config = window.SURVEY_PROTOTYPE || {};
    var questions = Array.isArray(config.questions) ? config.questions : [];
    var labels = config.labels || {};
    var storageKey = 'survey-organizational-maturity-prototype-v2';
    var periods = ['today', 'tomorrow'];
    var advanceTimer = null;

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

    function saveState() {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(state));
        } catch (error) {
            // Local persistence is a convenience; the prototype still works without it.
        }
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

    function handleChoiceChange(period, value, question) {
        state.answers[state.questionIndex].situation[period] = normalizeValue(value);
        if (period === 'today' && state.answers[state.questionIndex].situation.tomorrow === null) {
            state.activePeriod = 'tomorrow';
        }
        saveState();
        renderChoiceResponse(question);
        updateNavigation();
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

    updateWelcome();
    showWelcome();
}());
