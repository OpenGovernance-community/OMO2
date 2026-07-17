<?php
namespace dbObject;

class VideoEmbedHelper
{
    public static function getEmbedData($value)
    {
        $url = self::extractCandidateUrl($value);
        if ($url === '') {
            return [
                'provider' => '',
                'embedUrl' => '',
                'controlMode' => 'none',
            ];
        }

        $vimeoEmbedUrl = self::buildVimeoEmbedUrl($url);
        if ($vimeoEmbedUrl !== '') {
            return [
                'provider' => 'vimeo',
                'embedUrl' => $vimeoEmbedUrl,
                'controlMode' => 'custom',
            ];
        }

        $youtubeEmbedUrl = self::buildYouTubeEmbedUrl($url);
        if ($youtubeEmbedUrl !== '') {
            return [
                'provider' => 'youtube',
                'embedUrl' => $youtubeEmbedUrl,
                'controlMode' => 'native',
            ];
        }

        $infomaniakEmbedUrl = self::buildInfomaniakEmbedUrl($url);
        if ($infomaniakEmbedUrl !== '') {
            return [
                'provider' => 'infomaniak',
                'embedUrl' => $infomaniakEmbedUrl,
                'controlMode' => 'native',
            ];
        }

        return [
            'provider' => '',
            'embedUrl' => '',
            'controlMode' => 'none',
        ];
    }

    public static function buildEmbedUrl($value)
    {
        $data = self::getEmbedData($value);
        return (string)($data['embedUrl'] ?? '');
    }

    protected static function extractCandidateUrl($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*([\'"])(.*?)\1/i', $value, $matches)) {
            return trim(html_entity_decode((string)($matches[2] ?? ''), ENT_QUOTES, 'UTF-8'));
        }

        return $value;
    }

    protected static function buildVimeoEmbedUrl($url)
    {
        if (preg_match('#player\.vimeo\.com/video/(\d+)(?:[?&]h=([a-zA-Z0-9]+))?#i', $url, $matches)) {
            $videoId = trim((string)($matches[1] ?? ''));
            $hash = trim((string)($matches[2] ?? ''));

            if ($videoId === '') {
                return '';
            }

            return $hash !== ''
                ? 'https://player.vimeo.com/video/' . $videoId . '?h=' . $hash
                : 'https://player.vimeo.com/video/' . $videoId;
        }

        if (preg_match('#videos/(\d+)/([a-zA-Z0-9]+)#i', $url, $matches)) {
            $videoId = trim((string)($matches[1] ?? ''));
            $hash = trim((string)($matches[2] ?? ''));

            if ($videoId === '' || $hash === '') {
                return '';
            }

            return 'https://player.vimeo.com/video/' . $videoId . '?h=' . $hash;
        }

        if (preg_match('#vimeo\.com/(?:video/)?(\d+)(?:$|[?/])#i', $url, $matches)) {
            $videoId = trim((string)($matches[1] ?? ''));
            return $videoId !== ''
                ? 'https://player.vimeo.com/video/' . $videoId
                : '';
        }

        return '';
    }

    protected static function buildYouTubeEmbedUrl($url)
    {
        $parts = @parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $host = strtolower((string)($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host);
        $path = trim((string)($parts['path'] ?? ''));
        $queryValues = [];

        if (!empty($parts['query'])) {
            parse_str((string)$parts['query'], $queryValues);
        }

        $videoId = '';
        if ($host === 'youtu.be') {
            $segments = preg_split('#/+#', trim((string)$path, '/'));
            $videoId = trim((string)($segments[0] ?? ''));
        } elseif (
            $host === 'youtube.com'
            || $host === 'm.youtube.com'
            || $host === 'youtube-nocookie.com'
        ) {
            if (preg_match('#^/(?:embed|shorts|live)/([^/?&#]+)#i', $path, $matches)) {
                $videoId = trim((string)($matches[1] ?? ''));
            } elseif ($path === '/watch') {
                $videoId = trim((string)($queryValues['v'] ?? ''));
            }
        }

        if ($videoId === '') {
            return '';
        }

        $params = [];
        $start = self::parseYouTubeStartValue($queryValues['start'] ?? ($queryValues['t'] ?? ''));
        if ($start > 0) {
            $params['start'] = $start;
        }

        $end = (int)($queryValues['end'] ?? 0);
        if ($end > 0) {
            $params['end'] = $end;
        }

        $list = trim((string)($queryValues['list'] ?? ''));
        if ($list !== '') {
            $params['list'] = $list;
        }

        $si = trim((string)($queryValues['si'] ?? ''));
        if ($si !== '') {
            $params['si'] = $si;
        }

        $embedUrl = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId);
        if ($params !== []) {
            $embedUrl .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $embedUrl;
    }

    protected static function buildInfomaniakEmbedUrl($url)
    {
        $parts = @parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));
        $path = (string)($parts['path'] ?? '');

        if (($scheme !== 'https' && $scheme !== 'http') || $host === '') {
            return '';
        }

        if (!preg_match('/(^|\.)infomaniak\.com$/', $host)) {
            return '';
        }

        if (
            stripos($path, '/player/') === false
            && stripos($path, '/embed/') === false
            && stripos($path, '/iframe/') === false
            && stripos($path, '/share/') === false
        ) {
            return '';
        }

        if (preg_match('#/share/([^/?\#]+)#i', $path, $matches)) {
            $mediaId = trim((string)($matches[1] ?? ''));
            if ($mediaId === '') {
                return '';
            }

            $embedUrl = 'https://' . $host . '/embed/' . rawurlencode($mediaId);
            if (!empty($parts['query'])) {
                $embedUrl .= '?' . (string)$parts['query'];
            }
            if (!empty($parts['fragment'])) {
                $embedUrl .= '#' . (string)$parts['fragment'];
            }

            return $embedUrl;
        }

        return $url;
    }

    protected static function parseYouTubeStartValue($value)
    {
        if (is_numeric($value)) {
            return max(0, (int)$value);
        }

        $value = trim((string)$value);
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/i', $value, $matches)) {
            $hours = (int)($matches[1] ?? 0);
            $minutes = (int)($matches[2] ?? 0);
            $seconds = (int)($matches[3] ?? 0);
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        return 0;
    }
}
