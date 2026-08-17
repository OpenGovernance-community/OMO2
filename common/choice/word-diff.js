(function (window, document) {
    'use strict';

    if (window.omoChoiceWordDiff) return;

    function escapeHtml(value) {
        var node = document.createElement('div');
        node.textContent = String(value == null ? '' : value);
        return node.innerHTML;
    }

    function tokenize(text) {
        var tokens = String(text || '').match(/(\s+|[^\s]+)/g);
        return Array.isArray(tokens) ? tokens : [];
    }

    function buildOperations(beforeText, afterText, tokensAlready) {
        var before = tokensAlready && Array.isArray(beforeText) ? beforeText : tokenize(beforeText), after = tokensAlready && Array.isArray(afterText) ? afterText : tokenize(afterText), matrix = [], operations = [], i, j;
        if ((before.length + 1) * (after.length + 1) > 250000) return null;
        for (i = 0; i <= before.length; i += 1) {
            matrix[i] = [];
            for (j = 0; j <= after.length; j += 1) matrix[i][j] = 0;
        }
        for (i = 1; i <= before.length; i += 1) {
            for (j = 1; j <= after.length; j += 1) {
                matrix[i][j] = before[i - 1] === after[j - 1]
                    ? matrix[i - 1][j - 1] + 1
                    : Math.max(matrix[i - 1][j], matrix[i][j - 1]);
            }
        }
        i = before.length;
        j = after.length;
        while (i > 0 || j > 0) {
            if (i > 0 && j > 0 && before[i - 1] === after[j - 1]) {
                operations.push({type:'equal', value:before[i - 1], beforeIndex:i - 1, afterIndex:j - 1}); i--; j--;
            } else if (j > 0 && (i === 0 || matrix[i][j - 1] > matrix[i - 1][j])) {
                operations.push({type:'added', value:after[j - 1], afterIndex:j - 1}); j--;
            } else {
                operations.push({type:'removed', value:before[i - 1], beforeIndex:i - 1}); i--;
            }
        }
        return operations.reverse();
    }

    function renderSide(operations, side, fallback) {
        if (!Array.isArray(operations)) return escapeHtml(fallback);
        return operations.map(function (operation) {
            if (operation.type === 'equal') return escapeHtml(operation.value);
            if (side === 'before' && operation.type === 'removed') return '<span class="omo-proposal-chat__diff-removed">' + escapeHtml(operation.value) + '</span>';
            if (side === 'after' && operation.type === 'added') return '<span class="omo-proposal-chat__diff-added">' + escapeHtml(operation.value) + '</span>';
            return '';
        }).join('');
    }

    window.omoChoiceWordDiff = {
        buildOperations: buildOperations,
        renderPair: function (before, after) {
            var operations = buildOperations(before, after);
            return {
                before: renderSide(operations, 'before', before),
                after: renderSide(operations, 'after', after)
            };
        }
    };
})(window, document);
