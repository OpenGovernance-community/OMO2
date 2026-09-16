'use strict';

const assert = require('assert/strict');
const fs = require('fs');
const path = require('path');
const vm = require('vm');

// Expose private functions in the test VM only, without changing the browser API.
let source = fs.readFileSync(path.join(__dirname, '../omo/assets/js/structure-mini-map.js'), 'utf8');
const bootStart = source.indexOf("  if (document.readyState === 'loading')");
assert(bootStart > 0);
source = source.slice(0, bootStart) + 'window.testApi = { createMiniStructureMap, normalizeDisplaySettings }; })();';
assert(source.includes('      init: init'));
source = source.replace('      init: init', '      init: init, state, renderFrame');
source = source.replace('function normalizeDisplaySettings(settings) {',
  'function normalizeDisplaySettings(settings) { window.normalizationCount += 1;');

let packCount = 0;
const context = {
  window: {
    normalizationCount: 0,
    devicePixelRatio: 1,
    location: { hash: '' },
    d3: {
      layout: {
        pack() {
          return {
            padding() { return this; },
            size() { return this; },
            value() { return this; },
            sort() { return this; },
            nodes(root) {
              packCount += 1;
              const nodes = [];
              function visit(node, parent, depth) {
                Object.assign(node, { parent, depth, x: 100 + depth * 10, y: 100, r: 100 / (depth + 1) });
                nodes.push(node);
                (node.children || []).forEach(child => visit(child, node, depth + 1));
              }
              visit(root, null, 0);
              return nodes;
            }
          };
        }
      }
    }
  },
  document: { getElementById() { return null; }, documentElement: {} },
  getComputedStyle() { return { getPropertyValue() { return ''; } }; }
};
vm.runInNewContext(source, context);

let rect = { width: 300, height: 300 };
let bufferResets = 0;
const ctx = new Proxy({ measureText: text => ({ width: text.length * 5 }) }, {
  get(target, key) { return key in target ? target[key] : function () {}; }
});
const canvas = new Proxy({ width: 0, height: 0, style: {}, getContext: () => ctx }, {
  set(target, key, value) {
    if (key === 'width' || key === 'height') bufferResets += 1;
    target[key] = value;
    return true;
  }
});
const map = context.window.testApi.createMiniStructureMap({ getBoundingClientRect: () => rect });
const state = map.state;
Object.assign(state, {
  wrapper: {},
  canvas,
  tooltip: { classList: { add() {}, remove() {} } },
  currentNodeId: '1',
  zoomInfo: { centerX: 100, centerY: 100, scale: 1 },
  rootData: {
    ID: '1', name: 'Organization', type: '0', children: [{
      ID: '2', name: 'Circle', type: '0', children: [{
        ID: '3', name: 'Role', type: '1', size: 1
      }]
    }]
  },
  displaySettings: context.window.testApi.normalizeDisplaySettings({
    maxDescendantDepth: 1, labelAutoMinRadius: 3, labelHoverMinRadius: 3, labelMinFontSize: 3
  })
});
const normalizationCount = context.window.normalizationCount;
map.renderFrame();
assert.equal(packCount, 1);
assert.equal(bufferResets, 2);
assert.equal(state.packedNodes.length, 3, 'Hidden descendants must still participate in layout.');
assert.deepEqual(Array.from(state.renderedNodes, node => node.ID), ['1', '2']);
const packedNodes = state.packedNodes;
const geometry = JSON.stringify(packedNodes.map(node => [node.ID, node.x, node.y, node.r]));

for (let frame = 0; frame < 10; frame += 1) map.renderFrame();
assert.equal(packCount, 1, 'Repeated frames must reuse the packed layout.');
assert.equal(bufferResets, 2, 'Unchanged canvas dimensions must not reset the buffer.');
assert.equal(context.window.normalizationCount, normalizationCount, 'Rendering must reuse normalized settings.');
assert.equal(state.packedNodes, packedNodes, 'Drawing order must remain cached between frames.');

state.currentNodeId = '2';
state.hoveredNodeId = '3';
map.renderFrame();
assert.deepEqual(Array.from(state.renderedNodes, node => node.ID), ['1', '2', '3']);
assert.equal(packCount, 1, 'Changing focus must not repack previously hidden nodes.');
assert.equal(JSON.stringify(packedNodes.map(node => [node.ID, node.x, node.y, node.r])), geometry);

context.window.devicePixelRatio = 2;
map.renderFrame();
assert.equal(canvas.width, 600);
assert.equal(bufferResets, 4, 'Pixel density changes must resize the drawing buffer.');
assert.equal(packCount, 1, 'Pixel density does not change CSS layout geometry.');

rect = { width: 400, height: 300 };
map.renderFrame();
assert.equal(canvas.width, 800);
assert.equal(packCount, 2, 'A real viewport resize must rebuild the layout.');
assert.equal(state.packedNodes.length, 3);
process.stdout.write('structure_render_cache_test: OK\n');
