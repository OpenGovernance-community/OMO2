'use strict';

const assert = require('assert/strict');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const sourcePath = path.join(__dirname, '..', 'omo', 'api', 'getStructure.php');
const source = fs.readFileSync(sourcePath, 'utf8');
const start = source.indexOf('    function getTerminalMemberRows(');
const end = source.indexOf('    function drawTerminalMemberGroup(', start);
assert(start >= 0 && end > start, 'Unable to locate the terminal member layout implementation.');

const context = {
  window: { devicePixelRatio: 1 }
};
vm.runInNewContext(
  source.slice(start, end)
    + '\nthis.testApi = { getTerminalMemberLayout, getTerminalMemberPatternMetrics };',
  context
);

function verifyLayout(cardCount, nodeRadius, maximumAvatarRadius = Number.POSITIVE_INFINITY) {
  const cards = Array.from({ length: cardCount }, (_, index) => ({ userId: index + 1 }));
  const layout = context.testApi.getTerminalMemberLayout(cards, nodeRadius, maximumAvatarRadius);
  assert(layout, `Expected a layout for ${cardCount} cards in a radius of ${nodeRadius}.`);
  assert.equal(
    layout.rows.reduce((total, row) => total + row.cards, 0),
    Math.min(cardCount, 12),
    'Every visible card must have a slot.'
  );
  assert(layout.radius <= maximumAvatarRadius + 0.01, 'The group cap must be respected.');

  const metrics = context.testApi.getTerminalMemberPatternMetrics(
    layout.rows,
    nodeRadius,
    layout.radius,
    1
  );
  assert(metrics, 'The selected layout must fit inside its half-circle.');
  assert.equal(layout.axisGap, metrics.axisGap);

  layout.rows.forEach((rowDefinition, row) => {
    const cardsInRow = rowDefinition.cards;
    const y = layout.axisGap + layout.radius + (row * ((layout.radius * 2) + layout.rowGap));
    const rowWidth = (rowDefinition.slots * layout.radius * 2) + ((rowDefinition.slots - 1) * layout.horizontalGap);
    const slotIndexes = cardsInRow === rowDefinition.slots
      ? Array.from({ length: cardsInRow }, (_, index) => index)
      : cardsInRow === 1
        ? [Math.floor(rowDefinition.slots / 2)]
        : cardsInRow === 2 && rowDefinition.slots === 3
          ? [0, 2]
          : Array.from({ length: cardsInRow }, (_, index) => (rowDefinition.slots - cardsInRow) / 2 + index);
    for (let column = 0; column < cardsInRow; column += 1) {
      const x = -(rowWidth / 2) + layout.radius + (slotIndexes[column] * ((layout.radius * 2) + layout.horizontalGap));
      assert(
        Math.hypot(x, y) + layout.radius <= nodeRadius + 0.001,
        'Each avatar must stay completely inside the holon circle.'
      );
      assert(y - layout.radius >= layout.axisGap - 0.001, 'Each avatar must stay in its own half-circle.');
    }
  });

  return layout;
}

verifyLayout(1, 160);
verifyLayout(4, 160);
const fiveCardsLayout = verifyLayout(5, 220);
const sixCardsLayout = verifyLayout(6, 220);
const sevenCardsLayout = verifyLayout(7, 220);
const eightCardsLayout = verifyLayout(8, 220);
const nineCardsLayout = verifyLayout(9, 220);
verifyLayout(12, 220);
verifyLayout(20, 220);
assert.deepEqual(Array.from(fiveCardsLayout.rows, row => row.cards), [3, 2], 'Five members should form 3-2 rows.');
assert.deepEqual(Array.from(sixCardsLayout.rows, row => row.cards), [4, 2], 'Six members should use the 4-3 layout.');
assert.deepEqual(Array.from(sevenCardsLayout.rows, row => row.cards), [4, 3], 'Seven members should form 4-3 rows.');
assert.deepEqual(Array.from(eightCardsLayout.rows, row => row.cards), [5, 3], 'Eight members should start the 5-4-3 layout.');
assert.deepEqual(Array.from(nineCardsLayout.rows, row => row.cards), [5, 4], 'Nine members should use the first two rows of the 5-4-3 layout.');

const adminLayout = verifyLayout(3, 180);
const memberLayout = verifyLayout(3, 180, adminLayout.radius * 0.8);
assert(memberLayout.radius <= adminLayout.radius * 0.8 + 0.01, 'Regular members cannot exceed 80% of admin size.');

process.stdout.write('structure_terminal_member_layout_test: OK\n');
