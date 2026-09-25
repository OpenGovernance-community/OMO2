(function (root, factory) {
    const search = factory();
    if (typeof module === 'object' && module.exports) module.exports = search;
    else root.commonSearchText = search;
})(typeof window === 'undefined' ? globalThis : window, function () {
    'use strict';

    // Browser counterpart of search_text.php: keep token rules and weights aligned.
    const stopWords = new Set(('a au aux avec ce ces cet cette de des du dans en et est la le les leur leurs ma mes mon ne nos notre nous on ou par pas pour qu que quel quelle quelles quels qui quoi sa sans se ses son sont sous sur ta te tes toi ton tu un une vos votre vous comment pourquoi il ils elle elles lequel laquelle lesquelles lesquels the a an and of to for with what how').split(' '));
    const invariants = new Set('cours parcours discours concours recours secours corps temps pays moins toujours'.split(' '));
    const weights = {title: 100, summary: 50, body: 25, context: 12};
    const normalize = value => String(value ?? '').toLowerCase().normalize('NFD').replace(/\p{M}/gu, '');
    const words = value => normalize(value).match(/[\p{L}\p{N}]+/gu) || [];
    const significant = value => words(value).filter(word => !stopWords.has(word) && (word.length >= 2 || /^\d+$/.test(word)));

    function singular(word) {
        if (invariants.has(word)) return word;
        if ((word.length >= 5 && /[^suipox]s$/.test(word)) || /(?:eau|eu)x$/.test(word)) return word.slice(0, -1);
        return word;
    }

    function variants(term) {
        const stem = singular(term);
        const result = new Set([term, stem]);
        if ((stem.length >= 4 || /(?:eau|eu)$/.test(stem)) && !/[sxz]$/.test(stem) && !/^\d+$/.test(stem)) {
            result.add(stem + (/(?:eau|eu)$/.test(stem) ? 'x' : 's'));
        }
        return result;
    }

    function prepareQuery(value) {
        const phrase = significant(value);
        const unique = new Map();
        [...new Set(phrase)].sort((a, b) => b.length - a.length).forEach(word => {
            if (!unique.has(singular(word))) unique.set(singular(word), word);
        });
        return {phrase, terms: [...unique.values()].slice(0, 5)};
    }

    function wordQuality(word, term) {
        if (word === term) return 1;
        if (variants(term).has(word)) return 0.94;
        const stem = singular(term);
        if (/^\d+$/.test(stem)) return 0;
        if (stem.length >= 4 && word.startsWith(stem)) return 0.5;
        if (stem.length >= 5 && word.includes(stem)) return 0.12;
        return 0;
    }

    // Fields are plain text, extracted once from the authorised popup collection.
    function prepareFields(fields) {
        const prepared = {};
        Object.keys(weights).forEach(key => { prepared[key] = [...new Set(words(fields[key]))]; });
        prepared.phrase = significant(fields.title);
        return prepared;
    }

    function phraseQuality(words, wanted, whole) {
        if (!wanted.length || (whole && words.length !== wanted.length)) return 0;
        let best = 0;
        for (let start = 0; start <= words.length - wanted.length; start++) {
            let quality = 1;
            for (let i = 0; i < wanted.length; i++) {
                const match = wordQuality(words[start + i], wanted[i]);
                quality = Math.min(quality, match >= 0.94 ? match : 0);
                if (!quality) break;
            }
            best = Math.max(best, quality);
        }
        return best;
    }

    function score(fields, query) {
        if (!query.terms.length) return 0;
        let evidence = 0;
        let coverage = 0;
        let importanceSum = 0;
        query.terms.forEach(term => {
            const importance = 1 + Math.min(12, term.length) / 24;
            const matches = Object.keys(weights).map(key => {
                let quality = 0;
                for (const word of (fields[key] || [])) {
                    quality = Math.max(quality, wordQuality(word, term));
                    if (quality === 1) break;
                }
                return {quality, evidence: quality * weights[key]};
            });
            const quality = Math.max(...matches.map(match => match.quality));
            matches.sort((a, b) => b.evidence - a.evidence);
            evidence += importance * (matches[0].evidence + 0.1 * matches[1].evidence);
            coverage += importance * (quality >= 0.94 ? 1 : quality);
            importanceSum += importance;
        });
        if (!coverage) return 0;
        const bonus = Math.max(50 * phraseQuality(fields.phrase || [], query.phrase, true),
            query.terms.length > 1 ? 15 * phraseQuality(fields.phrase || [], query.phrase, false) : 0);
        return Math.max(1, Math.round((evidence / importanceSum + bonus) * (0.2 + 0.8 * (coverage / importanceSum) ** 2)));
    }

    function matches(text, query) {
        return [...String(text).matchAll(/[\p{L}\p{N}][\p{L}\p{N}\p{M}]*/gu)].map(match => ({
            start: match.index, end: match.index + match[0].length,
            qualities: query.terms.map(term => wordQuality(normalize(match[0]), term))
        })).filter(match => match.qualities.some(quality => quality > 0));
    }

    // Prefer a window covering several distinct terms over repeated occurrences.
    function excerpt(text, query, limit = 220) {
        text = String(text || '').replace(/\s+/g, ' ').trim();
        const hits = matches(text, query);
        let start = 0;
        let best = -1;
        let first = 0;
        hits.forEach(hit => {
            let candidate = Math.max(0, hit.start - 55);
            while (candidate > 0 && candidate < hit.start && !/\s/.test(text[candidate - 1])) candidate++;
            const qualities = query.terms.map(() => 0);
            while (first < hits.length && hits[first].start < candidate) first++;
            for (let i = first; i < hits.length && hits[i].start < candidate + limit; i++) {
                const other = hits[i];
                if (other.end <= candidate + limit) {
                    other.qualities.forEach((quality, i) => { qualities[i] = Math.max(qualities[i], quality); });
                }
            }
            const value = qualities.reduce((sum, quality) => sum + quality, 0);
            if (value > best) { best = value; start = candidate; }
        });
        let end = Math.min(text.length, start + limit);
        if (end < text.length && !/\s/.test(text[end])) {
            const boundary = text.lastIndexOf(' ', end);
            if (boundary > start) end = boundary;
        }
        return (start ? '... ' : '') + text.slice(start, end).trim() + (end < text.length ? ' ...' : '');
    }

    // Highlight text nodes, never HTML source or attributes (entities, links, etc.).
    function highlight(node, query, className) {
        const document = node.ownerDocument;
        const walker = document.createTreeWalker(node, 4);
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(textNode => {
            const hits = matches(textNode.nodeValue, query);
            if (!hits.length) return;
            const fragment = document.createDocumentFragment();
            let offset = 0;
            hits.forEach(hit => {
                fragment.appendChild(document.createTextNode(textNode.nodeValue.slice(offset, hit.start)));
                const mark = document.createElement('span');
                mark.className = className;
                mark.textContent = textNode.nodeValue.slice(hit.start, hit.end);
                fragment.appendChild(mark);
                offset = hit.end;
            });
            fragment.appendChild(document.createTextNode(textNode.nodeValue.slice(offset)));
            textNode.replaceWith(fragment);
        });
    }

    return {prepareQuery, prepareFields, wordQuality, score, excerpt, highlight};
});
