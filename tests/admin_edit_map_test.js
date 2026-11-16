'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../common/assets/admin-edit-map.js'), 'utf8');

function environment(initialLat = null, initialLng = null) {
    const mapElement = { dataset: {} };
    const latInput = new EventTarget();
    const longInput = new EventTarget();
    latInput.value = initialLat === null ? '' : String(initialLat);
    longInput.value = initialLng === null ? '' : String(initialLng);
    const elements = { map_latlong: mapElement, latlong_lat: latInput, latlong_long: longInput };
    const markers = [];
    let mapCount = 0;
    const map = {
        setView(center, zoom) { this.center = Array.from(center); this.zoom = zoom; return this; },
        getZoom() { return this.zoom; },
        on(name, callback) { this[name] = callback; },
        invalidateSize() {}
    };
    const window = {
        commonWhenLeafletReady(callback) { this.ready = callback; },
        commonBindLeafletTheme(boundMap) { assert.equal(boundMap, map); this.themeBound = true; },
        setTimeout(callback) { callback(); }
    };
    const L = {
        map(element) { assert.equal(element, mapElement); mapCount++; return map; },
        circleMarker(coordinates) {
            const marker = {
                coordinates: Array.from(coordinates),
                setLatLng(value) { this.coordinates = Array.from(value); },
                addTo(boundMap) { assert.equal(boundMap, map); return this; }
            };
            markers.push(marker);
            return marker;
        }
    };
    vm.runInNewContext(source, { window, document: { getElementById: id => elements[id] }, L, Event });
    const init = () => window.commonPageScripts['/common/assets/admin-edit-map.js']({ key: 'latlong', initialLat, initialLng });
    return { window, init, map, mapElement, latInput, longInput, markers, mapCount: () => mapCount };
}

{
    const state = environment();
    state.init();
    assert.equal(state.mapCount(), 0, 'Initialization waits for Leaflet');
    state.window.ready();
    assert.equal(state.mapCount(), 1, 'The key supplied by adminEdit resolves both coordinate inputs');
    assert.equal(state.mapElement.dataset.leafletReady, '1');
    assert.deepEqual(state.map.center, [46.8182, 8.2275]);
    assert.equal(state.map.zoom, 7);
    assert.equal(state.window.themeBound, true);
    assert.equal(state.markers.length, 0);

    const changes = [];
    for (const field of [state.latInput, state.longInput]) {
        field.addEventListener('change', event => {
            changes.push(event.bubbles);
        });
    }
    state.map.click({ latlng: { lat: 46.1234567, lng: 6.9876543 } });
    assert.equal(state.latInput.value, '46.123457');
    assert.equal(state.longInput.value, '6.987654');
    assert.deepEqual(changes, [true, true], 'Position changes reach form dirty tracking');
    assert.equal(state.markers.length, 1);
    assert.deepEqual(state.markers[0].coordinates, [46.123457, 6.987654]);
    assert.equal(state.map.zoom, 13);

    state.latInput.value = '47.2';
    state.longInput.value = '8.4';
    state.longInput.dispatchEvent(new Event('change'));
    assert.deepEqual(state.markers[0].coordinates, [47.2, 8.4], 'Manual edits move the existing marker');
    state.latInput.value = '';
    state.latInput.dispatchEvent(new Event('change'));
    assert.deepEqual(state.markers[0].coordinates, [47.2, 8.4], 'Incomplete manual coordinates are ignored');

    state.init();
    state.window.ready();
    assert.equal(state.mapCount(), 1, 'Replaying the initializer keeps a single map');
}

for (const coordinates of [[46.5, 6.6], [0, 0]]) {
    const state = environment(...coordinates);
    delete state.window.commonWhenLeafletReady;
    state.init();
    assert.deepEqual(state.map.center, coordinates, 'Saved coordinates center the map');
    assert.equal(state.map.zoom, 13);
    assert.deepEqual(state.markers[0].coordinates, coordinates, 'Saved coordinates restore the marker');
}

console.log('PASS adminEdit map initialization, coordinate selection, change events, manual edits and saved positions');
