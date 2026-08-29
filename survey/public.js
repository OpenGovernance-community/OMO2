(function () {
    'use strict';

    var questions = Array.isArray(window.SURVEY_PUBLIC_RESULT) ? window.SURVEY_PUBLIC_RESULT : [];
    var labels = window.SURVEY_PUBLIC_LABELS || {};
    var options = window.SURVEY_PUBLIC_OPTIONS || {};
    var radar = document.getElementById('surveyPublicRadar');
    var list = document.getElementById('surveyPublicList');

    function element(tagName, className, text) {
        var node = document.createElement(tagName);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = String(text);
        }
        return node;
    }

    function formatScore(value) {
        var score = Number(value);
        if (!Number.isFinite(score)) {
            return '';
        }
        return score % 1 === 0 ? String(score) : score.toFixed(1).replace('.', ',');
    }

    function formatStatistic(value) {
        var statistic = Number(value);
        if (!Number.isFinite(statistic)) {
            return '';
        }
        return statistic.toFixed(2).replace('.', ',');
    }

    function interpolate(template, variables) {
        return String(template || '').replace(/\{([a-zA-Z]+)\}/g, function (match, key) {
            return Object.prototype.hasOwnProperty.call(variables, key) ? String(variables[key]) : match;
        });
    }

    function svgElement(tagName, attributes, text) {
        var node = document.createElementNS('http://www.w3.org/2000/svg', tagName);
        Object.keys(attributes || {}).forEach(function (name) {
            node.setAttribute(name, attributes[name]);
        });
        if (text !== undefined) {
            node.textContent = String(text);
        }
        return node;
    }

    function point(index, score, center, radius) {
        var angle = -Math.PI / 2 + index * (Math.PI * 2 / questions.length);
        var distance = radius * score / 5;
        return {
            x: center + Math.cos(angle) * distance,
            y: center + Math.sin(angle) * distance
        };
    }

    function renderRadar() {
        var size = 700;
        var center = size / 2;
        var radius = 270;
        var svg = svgElement('svg', {
            viewBox: '0 0 ' + size + ' ' + size,
            class: 'survey-radar__svg',
            'aria-hidden': 'true'
        });

        svg.appendChild(svgElement('circle', { cx: center, cy: center, r: radius, class: 'survey-radar__risk-band' }));
        svg.appendChild(svgElement('circle', { cx: center, cy: center, r: radius * 4 / 5, class: 'survey-radar__safe-center' }));
        for (var level = 1; level <= 5; level += 1) {
            var levelRadius = radius * level / 5;
            svg.appendChild(svgElement('circle', {
                cx: center,
                cy: center,
                r: levelRadius,
                class: 'survey-radar__grid-circle' + (level === 5 ? ' survey-radar__grid-circle--risk' : '')
            }));
            svg.appendChild(svgElement('text', {
                x: center + 7,
                y: center - levelRadius + 15,
                class: 'survey-radar__level'
            }, level));
        }

        questions.forEach(function (question, index) {
            var outer = point(index, 5, center, radius);
            var label = point(index, 5, center, radius + 27);
            svg.appendChild(svgElement('line', { x1: center, y1: center, x2: outer.x, y2: outer.y, class: 'survey-radar__axis' }));
            svg.appendChild(svgElement('circle', { cx: label.x, cy: label.y, r: 15, class: 'survey-radar__axis-label-bg' }));
            svg.appendChild(svgElement('text', { x: label.x, y: label.y + 4, class: 'survey-radar__axis-label' }, question.number));
        });

        ['tomorrow', 'today'].forEach(function (period) {
            var points = questions.map(function (question, index) {
                var scorePoint = point(index, Number(question[period]), center, radius);
                return scorePoint.x + ',' + scorePoint.y;
            }).join(' ');
            svg.appendChild(svgElement('polygon', { points: points, class: 'survey-radar__series survey-radar__series--' + period }));

            questions.forEach(function (question, index) {
                var scorePoint = point(index, Number(question[period]), center, radius);
                svg.appendChild(svgElement('circle', {
                    cx: scorePoint.x,
                    cy: scorePoint.y,
                    r: 4,
                    class: 'survey-radar__point survey-radar__point--' + period,
                    'data-principle-number': question.number
                }));
            });
        });

        if (options.separateDimensionList === true) {
            radar.replaceChildren(svg);
        } else {
            var key = element('ol', 'survey-radar-key survey-public-radar-key');
            questions.forEach(function (question) {
                var item = element('li');
                var link = document.createElement('a');

                link.className = 'survey-radar-key__link';
                link.href = '#surveyPublicPrinciple' + question.number;
                link.appendChild(element('strong', '', question.title));
                link.addEventListener('mouseenter', function () {
                    highlightPrinciple(question.number);
                });
                link.addEventListener('mouseleave', clearHighlightedPrinciple);
                link.addEventListener('focus', function () {
                    highlightPrinciple(question.number);
                });
                link.addEventListener('blur', clearHighlightedPrinciple);
                item.append(element('span', '', question.number), link);
                key.appendChild(item);
            });
            radar.replaceChildren(svg, key);
        }
    }

    function highlightPrinciple(principleNumber) {
        radar.classList.add('survey-public-radar--highlighting');
        radar.querySelectorAll('[data-principle-number]').forEach(function (pointNode) {
            var isHighlighted = Number(pointNode.dataset.principleNumber) === Number(principleNumber);
            pointNode.classList.toggle('is-highlighted', isHighlighted);
            pointNode.setAttribute('r', isHighlighted ? '7' : '4');
        });
    }

    function clearHighlightedPrinciple() {
        radar.classList.remove('survey-public-radar--highlighting');
        radar.querySelectorAll('[data-principle-number]').forEach(function (pointNode) {
            pointNode.classList.remove('is-highlighted');
            pointNode.setAttribute('r', '4');
        });
    }

    function renderAgreement(question) {
        if (!labels.agreementStatuses || !labels.agreementTitle) {
            return null;
        }

        var wrapper = element('div', 'survey-result-card__agreement');
        var signals = element('div', 'survey-result-card__agreement-signals');
        var hasSignal = false;
        wrapper.appendChild(element('strong', 'survey-result-card__agreement-title', labels.agreementTitle));

        [
            { key: 'today', label: labels.current },
            { key: 'tomorrow', label: labels.desired }
        ].forEach(function (period) {
            var status = String(question[period.key + 'Agreement'] || '');
            var statusLabels = labels.agreementStatuses[status];
            if (!statusLabels) {
                return;
            }

            var signal = element('span', 'survey-result-card__agreement-signal survey-result-card__agreement-signal--' + status);
            var dot = element('i', 'organization-report__agreement-dot organization-report__agreement-dot--' + status);
            var copy = element('span', 'survey-result-card__agreement-copy');
            dot.setAttribute('aria-hidden', 'true');
            copy.appendChild(element('strong', '', period.label));
            copy.appendChild(element('small', '', statusLabels.label));
            var tooltip = statusLabels.description || '';
            if (labels.agreementStats) {
                tooltip += (tooltip ? ' ' : '') + interpolate(labels.agreementStats, {
                    count: question.responseCount,
                    stddev: formatStatistic(question[period.key + 'Stddev'])
                });
            }
            signal.title = tooltip;
            signal.setAttribute('aria-label', period.label + ' : ' + statusLabels.label + '. ' + tooltip);
            signal.append(dot, copy);
            signals.appendChild(signal);
            hasSignal = true;
        });

        if (!hasSignal) {
            return null;
        }
        wrapper.appendChild(signals);
        return wrapper;
    }

    function renderSituation(label, shortText, fullText, tooltipId) {
        var row = element('div', 'survey-result-card__situations');
        if (!fullText) {
            row.textContent = label + ' : ' + shortText;
            return row;
        }

        var trigger = element('span', 'survey-situation-tooltip', label + ' : ' + shortText);
        var tooltip = element('span', 'survey-situation-tooltip__content', fullText);
        trigger.tabIndex = 0;
        trigger.setAttribute('aria-describedby', tooltipId);
        tooltip.id = tooltipId;
        tooltip.setAttribute('role', 'tooltip');
        trigger.appendChild(tooltip);
        row.appendChild(trigger);
        return row;
    }

    function renderList() {
        var fragment = document.createDocumentFragment();
        questions.forEach(function (question) {
            var card = element('article', 'survey-result-card');
            var copy = element('div', 'survey-result-card__copy');
            var visual = element('div', 'survey-result-card__visual');
            card.id = 'surveyPublicPrinciple' + question.number;
            copy.appendChild(element('span', '', String(question.number)));
            copy.appendChild(element('h2', '', question.title));
            copy.appendChild(renderSituation(labels.current, question.situation, question.situationDescription, 'surveySituationTooltipToday' + question.number));
            copy.appendChild(renderSituation(labels.desired, question.desiredSituation, question.desiredSituationDescription, 'surveySituationTooltipTomorrow' + question.number));
            var agreement = renderAgreement(question);
            if (agreement) {
                copy.appendChild(agreement);
            }
            if (Number.isFinite(Number(question.affinity)) && labels.affinity) {
                var affinity = element('div', 'survey-result-card__affinity');
                affinity.appendChild(element('span', 'survey-result-card__affinity-label', interpolate(labels.affinity, {
                    value: formatScore(question.affinity)
                })));
                var affinityStatus = String(question.affinityAgreement || '');
                var affinityStatusLabels = labels.agreementStatuses && labels.agreementStatuses[affinityStatus];
                if (affinityStatusLabels) {
                    var affinitySignal = element('span', 'survey-result-card__affinity-signal survey-result-card__affinity-signal--' + affinityStatus);
                    var affinityDot = element('i', 'organization-report__agreement-dot organization-report__agreement-dot--' + affinityStatus);
                    affinityDot.setAttribute('aria-hidden', 'true');
                    var affinityTooltip = affinityStatusLabels.description || '';
                    if (labels.agreementStats) {
                        affinityTooltip += (affinityTooltip ? ' ' : '') + interpolate(labels.agreementStats, {
                            count: question.responseCount,
                            stddev: formatStatistic(question.affinityStddev)
                        });
                    }
                    affinitySignal.title = affinityTooltip;
                    affinitySignal.setAttribute('aria-label', affinityStatusLabels.label + '. ' + affinityTooltip);
                    affinitySignal.append(affinityDot, element('span', '', affinityStatusLabels.label));
                    affinity.appendChild(affinitySignal);
                }
                copy.appendChild(affinity);
            }
            visual.appendChild(element('span', 'survey-result-card__score', formatScore(question.today)));
            visual.appendChild(element('div', 'survey-result-card__line'));
            visual.appendChild(element('span', 'survey-result-card__score survey-result-card__score--tomorrow', formatScore(question.tomorrow)));
            card.append(copy, visual);
            fragment.appendChild(card);
        });
        list.replaceChildren(fragment);
    }

    if (questions.length === 10 && radar && list) {
        renderRadar();
        renderList();
        document.querySelectorAll('[data-survey-principle-link]').forEach(function (link) {
            var principleNumber = Number(link.getAttribute('data-survey-principle-link'));
            link.addEventListener('mouseenter', function () { highlightPrinciple(principleNumber); });
            link.addEventListener('mouseleave', clearHighlightedPrinciple);
            link.addEventListener('focus', function () { highlightPrinciple(principleNumber); });
            link.addEventListener('blur', clearHighlightedPrinciple);
        });
    }
}());
