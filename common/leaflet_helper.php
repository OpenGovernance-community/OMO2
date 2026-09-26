<?php

require_once __DIR__ . '/assets.php';

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
        <?= commonPageScriptTags('/common/assets/leaflet.js', [
    'tiles' => $config['tiles'],
], 'commonLeafletPageConfig') ?>
        <?php
    }
}
