(function () {
    'use strict';
    var dialog = document.getElementById('surveyDistributionDialog');
    if (!dialog) return;
    var questions = Array.isArray(window.SURVEY_PUBLIC_RESULT) ? window.SURVEY_PUBLIC_RESULT : [];
    var labels = window.SURVEY_PUBLIC_LABELS || {};
    var panels = dialog.querySelector('[data-survey-distribution-panels]');
    var formatter = new Intl.NumberFormat(document.documentElement.lang || 'fr', {maximumFractionDigits: 1});
    var lastTrigger = null;

    function node(tag, className, text) {
        var element = document.createElement(tag);
        element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    }

    function text(template, variables) {
        return String(template || '').replace(/\{([a-zA-Z]+)\}/g, function (match, key) {
            return Object.prototype.hasOwnProperty.call(variables, key) ? variables[key] : match;
        });
    }

    function openDistribution(question, trigger) {
        if (!question.distributions) return;
        var fragment = document.createDocumentFragment();
        ['today', 'tomorrow', 'affinity'].forEach(function (dimension) {
            var counts = question.distributions[dimension];
            if (!Array.isArray(counts) || counts.length !== 5) return;
            var section = node('section', 'generic-soft-panel generic-form-stack generic-form-stack--compact survey-distribution');
            section.setAttribute('data-distribution-dimension', dimension);
            var heading = node('h3', 'generic-card-title generic-card-title--small', dimension === 'today' ? labels.current : dimension === 'tomorrow' ? labels.desired : labels.distributionAffinity);
            heading.id = 'surveyDistribution-' + dimension;
            section.setAttribute('aria-labelledby', heading.id);
            section.appendChild(heading);
            section.appendChild(node('p', 'generic-meta', text(labels.distributionAverage, {value: formatter.format(question[dimension])})));
            var status = question[dimension + 'Agreement'];
            var statusLabels = labels.agreementStatuses && labels.agreementStatuses[status];
            if (statusLabels) {
                var signal = node('div', 'survey-distribution__status');
                var dot = node('i', 'organization-report__agreement-dot organization-report__agreement-dot--' + status);
                dot.setAttribute('aria-hidden', 'true');
                signal.append(dot, node('strong', '', statusLabels.label));
                section.appendChild(signal);
                section.appendChild(node('p', 'survey-distribution__description', statusLabels.description));
            }
            var total = counts.reduce(function (sum, count) { return sum + Number(count); }, 0);
            section.style.setProperty('--param-distribution-count-width', (String(total).length + 8) + 'ch');
            var rows = node('ol', 'survey-distribution__levels');
            counts.forEach(function (count, index) {
                var percent = total > 0 ? 100 * Number(count) / total : 0;
                var row = node('li', 'survey-distribution__level');
                var track = node('span', 'survey-distribution__track');
                track.setAttribute('aria-hidden', 'true');
                var bar = node('span', 'survey-distribution__bar');
                bar.style.setProperty('--param-distribution-share', percent + '%');
                track.appendChild(bar);
                var value = node('span', 'survey-distribution__count', String(count) + ' · ' + formatter.format(percent) + ' %');
                value.setAttribute('aria-label', text(labels.distributionCounts[count === 1 ? 'one' : 'other'], {count: count, percent: formatter.format(percent)}));
                var meanings = dimension === 'affinity' ? labels.affinityLevels : question.levels;
                row.append(node('strong', '', String(index + 1)), track, value);
                row.appendChild(node('small', 'survey-distribution__meaning', meanings && meanings[index] || ''));
                rows.appendChild(row);
            });
            section.appendChild(rows);
            fragment.appendChild(section);
        });
        panels.replaceChildren(fragment);
        dialog.querySelector('#surveyDistributionTitle').textContent = question.number + '. ' + question.title;
        dialog.querySelector('[data-survey-distribution-sample]').textContent = text(labels.distributionSamples[question.responseCount === 1 ? 'one' : 'other'], {count: question.responseCount});
        lastTrigger = trigger;
        if (!dialog.open) dialog.showModal();
        dialog.querySelector('.survey-omo-dialog__body').scrollTop = 0;
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-survey-distribution]');
        if (!trigger) return;
        var question = questions.find(function (item) { return Number(item.number) === Number(trigger.getAttribute('data-survey-distribution')); });
        if (question) openDistribution(question, trigger);
    });
    dialog.querySelector('[data-survey-distribution-close]').addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('click', function (event) {
        var bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
    });
    dialog.addEventListener('close', function () { if (lastTrigger && document.contains(lastTrigger)) lastTrigger.focus(); });
})();
