(function (window) {
    'use strict';

    window.omoDecisionBindIndividualAnonymousVoteOption = function (namedInput, individualAnonymousInput, individualAnonymousOption) {
        if (!namedInput || !individualAnonymousInput || !individualAnonymousOption) {
            return function () {};
        }

        const sync = function () {
            const isNamedVote = !!namedInput.checked;
            individualAnonymousOption.hidden = !isNamedVote;
            individualAnonymousInput.disabled = !isNamedVote || namedInput.disabled;
            if (!isNamedVote) {
                individualAnonymousInput.checked = false;
            }
        };

        namedInput.addEventListener('change', sync);
        sync();
        return sync;
    };
})(window);
