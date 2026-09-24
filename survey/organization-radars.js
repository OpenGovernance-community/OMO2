        (function () {
            var groupRadars = Array.isArray(window.SURVEY_ORGANIZATION_GROUP_RADARS) ? window.SURVEY_ORGANIZATION_GROUP_RADARS : [];
            var labels = window.SURVEY_ORGANIZATION_GROUP_LABELS || {};
            var cards = Array.prototype.slice.call(document.querySelectorAll('[data-group-radar-index]'));
            var scope = document.querySelector('[data-organization-radar-scope]');
            var resetButton = document.querySelector('[data-reset-group-radar]');
            var radarTitle = document.getElementById('surveyOrganizationRadarTitle');
            var activeIndex = null;

            function formatGroupLabel(index) {
                return 'Groupe ' + String.fromCharCode(65 + index);
            }

            function updateSelection(index) {
                var isGlobal = index === null;
                var radarData = isGlobal ? window.SURVEY_PUBLIC_RESULT : groupRadars[index];
                if (!window.SurveyPublicRadar || !window.SurveyPublicRadar.setQuestions(radarData)) {
                    return;
                }
                activeIndex = isGlobal ? null : index;
                cards.forEach(function (card) {
                    var cardIndex = Number(card.getAttribute('data-group-radar-index'));
                    var isActive = cardIndex === activeIndex;
                    card.classList.toggle('is-selected', isActive);
                    card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });
                if (scope) {
                    scope.textContent = isGlobal
                        ? String(labels.all || '')
                        : String(labels.group || 'Résultats du {group}').replace('{group}', formatGroupLabel(index));
                }
                if (resetButton) {
                    resetButton.hidden = isGlobal;
                }
                if (!isGlobal && radarTitle) {
                    radarTitle.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            cards.forEach(function (card) {
                function toggleGroup() {
                    var index = Number(card.getAttribute('data-group-radar-index'));
                    if (!Number.isInteger(index) || !groupRadars[index]) {
                        return;
                    }
                    updateSelection(index === activeIndex ? null : index);
                }
                card.addEventListener('click', toggleGroup);
                card.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    event.preventDefault();
                    toggleGroup();
                });
            });

            if (resetButton) {
                resetButton.addEventListener('click', function () {
                    updateSelection(null);
                });
            }
        }());

        document.querySelector('[data-print-report]')?.addEventListener('click', function () {
            window.print();
        });
