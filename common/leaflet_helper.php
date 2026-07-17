<?php

if (!function_exists('commonLeafletMapsEnabled')) {
    function commonLeafletMapsEnabled()
    {
        $apiKey = function_exists('envValue') ? trim((string)envValue('STADIA_MAPS_API_KEY', '')) : '';
        return $apiKey !== '';
    }
}

if (!function_exists('commonGetLeafletAssetConfig')) {
    function commonGetLeafletAssetConfig()
    {
        $attribution = '&copy; <a href="https://stadiamaps.com/" target="_blank" rel="noopener noreferrer">Stadia Maps</a> '
            . '&copy; <a href="https://openmaptiles.org/" target="_blank" rel="noopener noreferrer">OpenMapTiles</a> '
            . '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a>';
        $apiKey = function_exists('envValue') ? trim((string)envValue('STADIA_MAPS_API_KEY', '')) : '';
        $suffix = $apiKey !== '' ? ('?api_key=' . rawurlencode($apiKey)) : '';

        return array(
            'css' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            'js' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            'apiKeyConfigured' => commonLeafletMapsEnabled(),
            'tiles' => array(
                'light' => array(
                    'url' => 'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png' . $suffix,
                    'attribution' => $attribution,
                    'maxZoom' => 20,
                ),
                'dark' => array(
                    'url' => 'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png' . $suffix,
                    'attribution' => $attribution,
                    'maxZoom' => 20,
                ),
            ),
        );
    }
}

if (!function_exists('commonRenderLeafletAssets')) {
    function commonRenderLeafletAssets()
    {
        static $rendered = false;
        if ($rendered) {
            return;
        }

        $rendered = true;
        $config = commonGetLeafletAssetConfig();
        ?>
        <link
            rel="stylesheet"
            href="<?= htmlspecialchars((string)$config['css'], ENT_QUOTES, 'UTF-8') ?>"
            crossorigin=""
        >
        <script src="<?= htmlspecialchars((string)$config['js'], ENT_QUOTES, 'UTF-8') ?>" crossorigin=""></script>
        <script>
        window.commonLeafletTileConfig = <?= json_encode($config['tiles'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.commonCurrentLeafletTheme = window.commonCurrentLeafletTheme || null;
        window.commonWhenLeafletReady = window.commonWhenLeafletReady || function (callback) {
            if (typeof callback !== 'function') {
                return;
            }

            var maxAttempts = 120;
            var attempt = 0;

            function runWhenReady() {
                if (typeof window.L !== 'undefined') {
                    callback();
                    return;
                }

                attempt += 1;
                if (attempt >= maxAttempts) {
                    return;
                }

                window.setTimeout(runWhenReady, 50);
            }

            runWhenReady();
        };
        window.commonResolveLeafletTheme = window.commonResolveLeafletTheme || function () {
            var root = document.documentElement;
            var body = document.body;
            var rootTheme = root ? String(root.getAttribute('data-theme') || root.getAttribute('data-bs-theme') || '').toLowerCase() : '';
            var bodyTheme = body ? String(body.getAttribute('data-theme') || body.getAttribute('data-bs-theme') || '').toLowerCase() : '';

            if (window.commonCurrentLeafletTheme === 'dark' || window.commonCurrentLeafletTheme === 'light') {
                return window.commonCurrentLeafletTheme;
            }

            if (rootTheme === 'dark' || bodyTheme === 'dark') {
                return 'dark';
            }

            if (rootTheme === 'light' || bodyTheme === 'light') {
                return 'light';
            }

            try {
                var storedPreference = window.localStorage.getItem('omo-theme-preference');
                if (storedPreference === 'dark' || storedPreference === 'light') {
                    return storedPreference;
                }
            }
            catch (error) {
            }

            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                return 'dark';
            }

            return 'light';
        };
        window.commonCreateLeafletTileLayer = window.commonCreateLeafletTileLayer || function (themeName) {
            if (typeof window.L === 'undefined') {
                return null;
            }

            var tiles = window.commonLeafletTileConfig || {};
            var resolvedTheme = themeName === 'dark' ? 'dark' : 'light';
            var tileConfig = tiles[resolvedTheme] || tiles.light || null;
            if (!tileConfig || !tileConfig.url) {
                return null;
            }

            return window.L.tileLayer(tileConfig.url, {
                maxZoom: Number(tileConfig.maxZoom || 19),
                attribution: String(tileConfig.attribution || '')
            });
        };
        window.commonBindLeafletTheme = window.commonBindLeafletTheme || function (map, state) {
            if (!map || typeof state !== 'object' || state === null) {
                return;
            }

            function applyTheme(forcedTheme) {
                var theme = forcedTheme === 'dark' || forcedTheme === 'light'
                    ? forcedTheme
                    : window.commonResolveLeafletTheme();
                if (state.theme === theme && state.layer) {
                    return;
                }

                if (state.layer) {
                    map.removeLayer(state.layer);
                }

                state.layer = window.commonCreateLeafletTileLayer(theme);
                state.theme = theme;
                if (state.layer) {
                    state.layer.addTo(map);
                }

                window.commonCurrentLeafletTheme = theme;
            }

            applyTheme();

            if (window.matchMedia && !state._leafletThemeMediaBound) {
                var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                var listener = function () {
                    applyTheme();
                };

                if (typeof mediaQuery.addEventListener === 'function') {
                    mediaQuery.addEventListener('change', listener);
                } else if (typeof mediaQuery.addListener === 'function') {
                    mediaQuery.addListener(listener);
                }

                state._leafletThemeMediaBound = true;
            }

            if (!state._leafletThemeEventBound) {
                window.addEventListener('omo-theme-change', function (event) {
                    var forcedTheme = event && event.detail && (event.detail.theme === 'dark' || event.detail.theme === 'light')
                        ? event.detail.theme
                        : null;
                    applyTheme(forcedTheme);
                });
                state._leafletThemeEventBound = true;
            }

            if (!state._leafletThemeMutationBound && typeof MutationObserver !== 'undefined') {
                var targetNode = document.documentElement;
                if (targetNode) {
                    var observer = new MutationObserver(function (mutations) {
                        mutations.forEach(function (mutation) {
                            if (mutation.type === 'attributes' && (mutation.attributeName === 'data-theme' || mutation.attributeName === 'data-theme-preference')) {
                                applyTheme();
                            }
                        });
                    });

                    observer.observe(targetNode, {
                        attributes: true,
                        attributeFilter: ['data-theme', 'data-theme-preference']
                    });
                    state._leafletThemeMutationBound = true;
                }
            }
        };
        </script>
        <?php
    }
}
