'use strict';

const assert = require('node:assert/strict');
const search = require('../common/search_text.js');
const score = (fields, query) => search.score(search.prepareFields(fields), search.prepareQuery(query));

assert.deepEqual(search.prepareQuery('le la a comment').terms, []);
assert.deepEqual(search.prepareQuery('le suivi des responsabilites organisationnelles validation factures recurrentes budget').terms,
    ['organisationnelles', 'responsabilites', 'recurrentes', 'validation', 'factures']);
assert.equal(search.prepareQuery('gestion gestions gestion').terms.length, 1);
assert.deepEqual(search.prepareQuery('RH PV 2026').terms, ['2026', 'rh', 'pv']);
const exact = score({title: 'Gestion'}, 'gestion');
const plural = score({title: 'Gestions'}, 'gestion');
assert(plural >= exact * 0.9 && plural < exact);
assert.equal(plural, score({title: 'Gestion'}, 'gestions'));
assert(plural > score({title: 'Gestionnaire'}, 'gestion'));
assert(score({title: 'Gestionnaire'}, 'gestion') > score({title: 'Digestion'}, 'gestion'));
assert.equal(score({title: 'cours processus'}, 'cours processus'), 150);
assert(score({title: 'Jeux tableaux'}, 'jeu tableau') > 100);
assert.equal(score({title: 'phrase 20260'}, 'RH 2026'), 0);
assert.equal(score({title: 'R\u00e8gles'}, 'regle'), score({title: 'regles'}, 'regle'));
assert.equal(score({title: 'Re\u0300gles'}, 'regle'), score({title: 'regles'}, 'regle'));
assert(score({title: 'Validation factures'}, 'validation factures') > score({title: 'Factures validation'}, 'validation factures'));
assert(score({body: 'Validation des factures recurrentes'}, 'validation factures recurrentes') > score({title: 'Validation'}, 'validation factures recurrentes'));
assert(score({title: 'Budget'}, 'budget') > score({summary: 'budget '.repeat(100)}, 'budget'));
assert.equal(score({summary: 'Budget'}, 'budget'), score({summary: 'budget '.repeat(100)}, 'budget'));
assert(score({summary: 'Budget'}, 'budget') > score({body: 'Budget'}, 'budget'));
assert(score({body: 'Budget'}, 'budget') > score({context: 'Budget'}, 'budget'));
assert.equal(score({}, 'ouvrir'), 0);
assert.equal(score({title: 'Budget'}, '% _'), 0);
const longAnswer = 'Introduction generale. '.repeat(40) + 'La validation des factures recurrentes se fait ici. ' + 'Conclusion. '.repeat(30);
const excerpt = search.excerpt(longAnswer, search.prepareQuery('validation factures recurrentes'));
assert(excerpt.includes('validation des factures recurrentes'));
assert(excerpt.length <= 228);
assert(score({body: longAnswer}, 'factures') > 0);
assert(search.excerpt('budget '.repeat(50) + 'validation factures', search.prepareQuery('budget validation factures')).includes('validation factures'));
console.log('faq_search_scoring_test: OK');
