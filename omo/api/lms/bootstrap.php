<?php
require_once dirname(__DIR__) . '/bootstrap.php';

if (!defined('OMO_LMS_WEB_BASE')) {
    define('OMO_LMS_WEB_BASE', '/omo/api/lms');
}

if (!function_exists('omoLmsBuildPath')) {
    function omoLmsBuildPath(string $path = '/', array $params = []): string
    {
        $basePath = rtrim(OMO_LMS_WEB_BASE, '/');
        $path = trim($path);
        if ($path === '') {
            $path = '/';
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);
        $parsedQuery = parse_url($path, PHP_URL_QUERY);
        $parsedFragment = parse_url($path, PHP_URL_FRAGMENT);
        $rawPath = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : '/';

        if (
            $rawPath === '/'
            || $rawPath === '/lms'
            || $rawPath === '/lms/'
            || $rawPath === $basePath
            || $rawPath === $basePath . '/'
        ) {
            $targetPath = $basePath . '/';
        } elseif (strpos($rawPath, '/lms/') === 0) {
            $targetPath = $basePath . substr($rawPath, strlen('/lms'));
        } elseif (strpos($rawPath, $basePath . '/') === 0) {
            $targetPath = $rawPath;
        } else {
            $targetPath = $basePath . '/' . ltrim($rawPath, '/');
        }

        $targetPath = commonNormalizeLocalPath($targetPath, $basePath . '/');

        $queryParams = [];
        if (is_string($parsedQuery) && $parsedQuery !== '') {
            parse_str($parsedQuery, $queryParams);
        }

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                unset($queryParams[$key]);
                continue;
            }

            $queryParams[(string)$key] = $value;
        }

        if ($queryParams !== []) {
            $targetPath .= '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        }

        if (is_string($parsedFragment) && $parsedFragment !== '') {
            $targetPath .= '#' . $parsedFragment;
        }

        return $targetPath;
    }
}
