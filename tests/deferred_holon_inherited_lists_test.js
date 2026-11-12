'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {execFileSync} = require('node:child_process');

// Feed the production PHP display states into the shared PV/decision comparison.
const fixtures = JSON.parse(execFileSync(process.env.PHP_CLI || 'php', [
    path.join(__dirname, 'deferred_holon_inherited_lists_test.php'), '--json'
], {encoding: 'utf8'}));
const window = {};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../common/choice/change-details.js'), 'utf8'), {
    window, document: {readyState: 'loading', addEventListener() {}}
});
for (const fixture of fixtures) {
    const changes = window.omoChoiceChangeDetails.governanceChanges(fixture.action);
    const expected = {
        add: ['Local property', 'added'], remove: ['Local property', 'removed'],
        add_item: ['Inherited list', 'added'], remove_item: ['Inherited list', 'removed']
    }[fixture.operation];
    assert.equal(changes.length, expected ? 1 : 0, fixture.case + ': only the actual local change should appear');
    if (expected) {
        assert.equal(changes[0].label, expected[0], fixture.case);
        assert.equal(changes[0].status, expected[1], fixture.case);
        if (fixture.operation.endsWith('_item')) {
            assert.equal(changes[0][fixture.operation === 'add_item' ? 'after' : 'before'].length, 1, fixture.case);
        }
    }
}
console.log('deferred_holon_inherited_lists_test: OK (' + fixtures.length + ' comparisons)');

let authorityComparisons = 0;
for (const formatId of [2, 7]) {
    const property = (items, displayItems) => ({name:'Authorities',formatId,listItemType:'authority',
        value:JSON.stringify(formatId === 7 ? {before:'',items,after:''} : items),displayItems});
    const compare = (before, after) => {
        authorityComparisons++;
        return window.omoChoiceChangeDetails.governanceChanges({type:'holon.update',
            before:{editor_payload:{properties:[before]}},after:{editor_payload:{properties:[after]}}});
    };
    for (const displayItems of [undefined, [117], [{id:117}], [{id:117,label:'Autorité #117'}]]) {
        const changes = compare(property([117], displayItems), property([{id:117,label:'Imported authority'}], [{id:117,label:'Imported authority'}]));
        assert.equal(changes.length, 0, 'Resolving an imported ID must not report a deletion or addition');
    }
    assert.equal(compare(property([{id:117,label:'Imported authority'}]), property([117])).length, 0, 'A missing label on the draft must not report a deletion');
    const renamed = compare(property([117], [{id:117,label:'Original name'}]), property([{id:117,label:'Completely different name'}]));
    assert.equal(renamed.length, 1, 'A genuine rename is one modification, even with dissimilar labels');
    assert.equal(renamed[0].status, 'changed');
    assert.equal(renamed[0].before, 'Original name');
    assert.equal(renamed[0].after, 'Completely different name');
    const removed = compare(property([117], [{id:117,label:'Removed authority'}]), property([{id:117,delete:true}]));
    assert.equal(removed.length, 1);
    assert.equal(removed[0].status, 'removed', 'Actual authority deletions remain visible');
    const added = compare(property([]), property([{id:118,label:'Added authority'}]));
    assert.equal(added.length, 1);
    assert.equal(added[0].status, 'added', 'Actual authority additions remain visible');
    const replaced = compare(property([{id:117,label:'Same label'}]), property([{id:118,label:'Same label'}]));
    assert.equal(replaced.length, 2, 'Different authority IDs must not be merged because their labels match');
    assert.equal(replaced[0].status, 'removed');
    assert.equal(replaced[1].status, 'added');
}
console.log('imported_authority_comparison: OK (' + authorityComparisons + ' comparisons)');
