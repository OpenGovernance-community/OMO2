window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/admin-edit-map.js"] = function (pageConfig, pageScript) {
    function runMapInit() {
        var mapElement = document.getElementById('map_' + pageConfig.key);
        var latInput = document.getElementById(pageConfig.key + '_lat');
        var longInput = document.getElementById(pageConfig.key + '_long');
        if (!mapElement || !latInput || !longInput) {
            return;
        }
        if (mapElement.dataset.leafletReady === '1') {
            return;
        }
        mapElement.dataset.leafletReady = '1';

        var initialLat = pageConfig.initialLat;
        var initialLng = pageConfig.initialLng;
        var hasCoordinates = typeof initialLat === 'number' && typeof initialLng === 'number';
        var center = hasCoordinates ? [initialLat, initialLng] : [46.8182, 8.2275];
        var zoom = hasCoordinates ? 13 : 7;
        var map = L.map(mapElement).setView(center, zoom);
        var tileState = { layer: null, theme: null };
        if (typeof window.commonBindLeafletTheme === 'function') {
            window.commonBindLeafletTheme(map, tileState);
        }

        var marker = null;
        function updateMarker(lat, lng, shouldCenter) {
            if (typeof lat !== 'number' || typeof lng !== 'number' || Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.circleMarker([lat, lng], {
                    radius: 9,
                    color: '#0f766e',
                    weight: 2,
                    fillColor: '#14b8a6',
                    fillOpacity: 0.85
                }).addTo(map);
            }
            if (shouldCenter) {
                map.setView([lat, lng], Math.max(map.getZoom(), 13));
            }
        }

        map.on('click', function (event) {
            var lat = event.latlng.lat;
            var lng = event.latlng.lng;
            latInput.value = Number(lat).toFixed(6);
            longInput.value = Number(lng).toFixed(6);
            updateMarker(lat, lng, true);
            latInput.dispatchEvent(new Event('change', { bubbles: true }));
            longInput.dispatchEvent(new Event('change', { bubbles: true }));
        });

        function syncInputs() {
            var lat = parseFloat(latInput.value);
            var lng = parseFloat(longInput.value);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }
            updateMarker(lat, lng, true);
        }
        latInput.addEventListener('change', syncInputs);
        longInput.addEventListener('change', syncInputs);
        if (hasCoordinates) {
            updateMarker(initialLat, initialLng, false);
        }
        window.setTimeout(function () { map.invalidateSize(); }, 0);
        window.setTimeout(function () { map.invalidateSize(); }, 250);
    }

    if (typeof window.commonWhenLeafletReady === 'function') {
        window.commonWhenLeafletReady(runMapInit);
    } else {
        runMapInit();
    }
};
