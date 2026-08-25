(function (window, document) {
    'use strict';

    function initialize(root) {
        if (!root || root.dataset.omoDecisionOneAtATimeReady === '1') {
            return;
        }

        var items = Array.prototype.slice.call(root.querySelectorAll('[data-omo-decision-one-at-a-time-item]'));
        if (items.length < 2) {
            return;
        }

        var previous = root.querySelector('[data-omo-decision-one-at-a-time-previous]');
        var next = root.querySelector('[data-omo-decision-one-at-a-time-next]');
        var dots = root.querySelector('[data-omo-decision-one-at-a-time-dots]');
        var form = root.closest ? root.closest('form') : null;
        var draftUrl = String(root.getAttribute('data-omo-decision-one-at-a-time-draft-url') || '');
        var draftTimer = null;
        if (!previous || !next || !dots) {
            return;
        }

        var singleChoice = root.getAttribute('data-omo-decision-one-at-a-time-single-choice') === '1';
        var getComplete = function (item) {
            if (singleChoice) {
                return !!root.querySelector('input[type="radio"]:checked');
            }
            return !!item.querySelector('input[type="radio"]:checked, input[type="checkbox"]:checked');
        };
        var currentIndex = items.findIndex(function (item) { return !getComplete(item); });
        if (currentIndex < 0) {
            currentIndex = 0;
        }

        var render = function (animate) {
            items.forEach(function (item, index) {
                var isCurrent = index === currentIndex;
                item.hidden = !isCurrent;
                item.classList.toggle('is-entering', !!animate && isCurrent);
            });

            Array.prototype.forEach.call(dots.children, function (dot, index) {
                dot.classList.toggle('is-complete', getComplete(items[index]));
                dot.classList.toggle('is-current', index === currentIndex);
                dot.setAttribute('aria-current', index === currentIndex ? 'step' : 'false');
            });
            previous.disabled = currentIndex === 0;
            next.disabled = currentIndex >= items.length - 1;
        };

        var saveDraft = function () {
            if (draftUrl === '' || !form || typeof window.fetch !== 'function') {
                return;
            }
            if (draftTimer) {
                window.clearTimeout(draftTimer);
            }
            draftTimer = window.setTimeout(function () {
                var formData = new FormData(form);
                formData.set('draft', '1');
                window.fetch(draftUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }).catch(function () {
                    return null;
                });
            }, 240);
        };

        items.forEach(function (item, index) {
            var dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'omo-decision-one-at-a-time__dot';
            dot.setAttribute('aria-label', 'Proposition ' + String(index + 1));
            dot.addEventListener('click', function () {
                currentIndex = index;
                render(true);
            });
            dots.appendChild(dot);
        });

        previous.addEventListener('click', function () {
            if (currentIndex > 0) {
                currentIndex--;
                render(true);
            }
        });
        next.addEventListener('click', function () {
            if (currentIndex < items.length - 1) {
                currentIndex++;
                render(true);
            }
        });
        root.addEventListener('change', function (event) {
            if (!event.target.matches('input[type="radio"], input[type="checkbox"]')) {
                return;
            }
            render(false);
            saveDraft();
            window.setTimeout(function () {
                var nextIncompleteIndex = items.findIndex(function (item) { return !getComplete(item); });
                if (getComplete(items[currentIndex]) && nextIncompleteIndex >= 0 && nextIncompleteIndex !== currentIndex) {
                    currentIndex = nextIncompleteIndex;
                    render(true);
                }
            }, 180);
        });

        root.dataset.omoDecisionOneAtATimeReady = '1';
        render(false);
    }

    window.omoDecisionInitOneProposalAtATime = function (scope) {
        var root = scope instanceof Element ? scope : document;
        Array.prototype.forEach.call(root.querySelectorAll('[data-omo-decision-one-at-a-time]'), initialize);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.omoDecisionInitOneProposalAtATime(document);
        }, {once: true});
    } else {
        window.omoDecisionInitOneProposalAtATime(document);
    }

    if (window.MutationObserver && document.documentElement) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (node.nodeType === 1) {
                        window.omoDecisionInitOneProposalAtATime(node);
                        if (node.matches && node.matches('[data-omo-decision-one-at-a-time]')) {
                            initialize(node);
                        }
                    }
                });
            });
        }).observe(document.documentElement, {childList: true, subtree: true});
    }
})(window, document);
