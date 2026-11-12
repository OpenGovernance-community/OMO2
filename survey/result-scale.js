(function () {
    'use strict';

    function element(tag, className, text) {
        var node = document.createElement(tag);
        node.className = className;
        if (text !== undefined) node.textContent = String(text);
        return node;
    }

    // Both report renderers use the same fixed scale, including fractional averages.
    window.SurveyResultScale = {
        create: function (today, tomorrow, labels, gapText) {
            var visual = element('div', 'survey-result-card__visual');
            var legend = element('div', 'survey-result-scale__legend');
            var scale = element('div', 'survey-result-scale');
            var track = element('div', 'survey-result-scale__track');
            var line = element('div', 'survey-result-card__line');
            var descriptions = [];
            var positions = [today, tomorrow].map(function (value) {
                return (Math.max(1, Math.min(5, Number(value))) - 1) * 25;
            });

            for (var level = 1; level <= 5; level += 1) {
                var tick = element('span', 'survey-result-scale__tick', level);
                tick.style.left = ((level - 1) * 25) + '%';
                track.appendChild(tick);
            }
            line.style.left = Math.min(positions[0], positions[1]) + '%';
            line.style.width = Math.abs(positions[1] - positions[0]) + '%';
            track.appendChild(line);

            [today, tomorrow].forEach(function (value, index) {
                var period = index === 0 ? 'today' : 'tomorrow';
                var label = index === 0 ? labels.current : labels.desired;
                var score = Number(value);
                var text = score % 1 === 0 ? String(score) : score.toFixed(1).replace('.', ',');
                var marker = element('span', 'survey-result-card__score survey-result-card__score--' + period, text);
                marker.style.left = positions[index] + '%';
                marker.title = label + ' : ' + text + '/5';
                descriptions.push(marker.title);
                legend.appendChild(element('span', 'survey-result-scale__key survey-result-scale__key--' + period, label));
                track.appendChild(marker);
            });

            // Separate vertical lanes keep equal and nearby scores readable at every width.
            visual.setAttribute('role', 'img');
            visual.setAttribute('aria-label', descriptions.join('. ') + '. ' + (gapText || ''));
            legend.setAttribute('aria-hidden', 'true');
            scale.setAttribute('aria-hidden', 'true');
            scale.appendChild(track);
            visual.append(legend, scale);
            if (gapText) visual.appendChild(element('span', 'survey-result-scale__gap', gapText));
            return visual;
        }
    };
}());
