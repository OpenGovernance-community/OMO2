<?php

/** A stable URL shared by every consumer; changing the file renews its cache key. */
function commonAssetUrl(string $path): string
{
    static $urls = [];
    if (isset($urls[$path])) return $urls[$path];
    if (!preg_match('~^/[a-zA-Z0-9_./-]+$~', $path) || str_contains($path, '..')) {
        throw new InvalidArgumentException('Expected a local asset path.');
    }
    $file = dirname(__DIR__) . $path;
    $hash = is_file($file) ? hash_file('sha256', $file) : false;
    return $urls[$path] = $path . ($hash !== false ? '?v=' . substr($hash, 0, 12) : '');
}

/** Load shared foundations explicitly, without a second CSS @import request. */
function commonStylesheetTags(string $path): string
{
    $paths = in_array($path, ['/shared_css.css', '/omo/assets/css/styles.css'], true)
        ? ['/common/assets/components.css', $path]
        : [$path];
    return implode("\n", array_map(
        static fn(string $asset): string => '<link rel="stylesheet" href="' . commonAssetUrl($asset) . '">',
        $paths
    ));
}

/** Static page code plus the small, request-specific configuration it needs. */
function commonPageScriptTags(string $path, array $config, ?string $globalConfigName = null): string
{
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
        | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;
    // Legacy pages expose global functions to inline handlers and other scripts.
    if ($globalConfigName !== null) {
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $globalConfigName)) {
            throw new InvalidArgumentException('Expected a JavaScript configuration identifier.');
        }
        return '<script>window[' . json_encode($globalConfigName, $flags) . '] = '
            . json_encode($config, $flags) . ';</script>' . "\n"
            . '<script src="' . commonAssetUrl($path) . '"></script>';
    }
    return '<script src="' . commonAssetUrl($path) . '"></script>' . "\n"
        . '<script>window.commonPageScripts[' . json_encode($path, $flags) . ']('
        . json_encode($config, $flags) . ', document.currentScript.previousElementSibling);</script>';
}
