'use strict';

const assert = require('assert/strict');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const sourcePath = path.join(__dirname, '..', 'omo', 'api', 'getStructure.php');
const source = fs.readFileSync(sourcePath, 'utf8');
const start = source.indexOf('    function getTerminalMemberRowPatterns(');
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
    layout.rowCounts.reduce((total, rowCount) => total + rowCount, 0),
    Math.min(cardCount, 12),
    'Every visible card must have a slot.'
  );
  assert(layout.radius <= maximumAvatarRadius + 0.01, 'The group cap must be respected.');

  const metrics = context.testApi.getTerminalMemberPatternMetrics(
    layout.rowCounts,
    nodeRadius,
    layout.radius,
    1
  );
  assert(metrics, 'The selected layout must fit inside its half-circle.');
  assert.equal(layout.axisGap, metrics.axisGap);

  layout.rowCounts.forEach((cardsInRow, row) => {
    const y = layout.axisGap + layout.radius + (row * ((layout.radius * 2) + layout.rowGap));
    const rowWidth = (cardsInRow * layout.radius * 2) + ((cardsInRow - 1) * layout.horizontalGap);
    for (let column = 0; column < cardsInRow; column += 1) {
      const x = -(rowWidth / 2) + layout.radius + (column * ((layout.radius * 2) + layout.horizontalGap));
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
const nineCardsLayout = verifyLayout(9, 220);
verifyLayout(12, 220);
verifyLayout(20, 220);
assert(nineCardsLayout.rowCounts.length > 1, 'Several members should use staggered rows.');
assert.deepEqual(Array.from(nineCardsLayout.rowCounts), [4, 3, 2], 'Nine members should form staggered 4-3-2 rows.');
assert(
  nineCardsLayout.rowCounts.every((rowCount, index, rows) => index === 0 || rowCount <= rows[index - 1]),
  'Rows must narrow as they move away from the center of the half-circle.'
);

const adminLayout = verifyLayout(3, 180);
const memberLayout = verifyLayout(3, 180, adminLayout.radius * 0.8);
assert(memberLayout.radius <= adminLayout.radius * 0.8 + 0.01, 'Regular members cannot exceed 80% of admin size.');

process.stdout.write('structure_terminal_member_layout_test: OK\n');
