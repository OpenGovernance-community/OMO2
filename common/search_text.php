<?php

/** Shared by candidate selection, ranking, excerpts and highlighting. */
function commonSearchNormalizeText(string $value): string
{
    $value = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', ' ', $value);
    $value = html_entity_decode(strip_tags(preg_replace('~<[^>]+>~u', ' ', $value)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (class_exists(\Normalizer::class)) {
        $value = \Normalizer::normalize($value, \Normalizer::FORM_D) ?: $value;
        $value = preg_replace('/\p{Mn}/u', '', $value);
    }
    return mb_strtolower($value, 'UTF-8');
}

function commonSearchWords(string $value, bool $significantOnly = false): array
{
    preg_match_all('/[\p{L}\p{N}]+/u', commonSearchNormalizeText($value), $matches);
    if (!$significantOnly) {
        return $matches[0];
    }
    // Keep business acronyms (PV, RH, SI...) and numbers. No aggressive stemming.
    static $stopWords = null;
    $stopWords ??= array_fill_keys(explode(' ', 'a au aux avec ce ces cet cette de des du dans en et est la le les leur leurs ma mes mon ne nos notre nous on ou par pas pour qu que quel quelle quelles quels qui quoi sa sans se ses son sont sous sur ta te tes toi ton tu un une vos votre vous comment pourquoi il ils elle elles lequel laquelle lesquelles lesquels the a an and of to for with what how'), true);
    return array_values(array_filter($matches[0], static fn($word) =>
        !isset($stopWords[$word]) && (mb_strlen($word, 'UTF-8') >= 2 || ctype_digit($word))
    ));
}

function commonSearchSingular(string $word): string
{
    static $invariants = ['cours', 'parcours', 'discours', 'concours', 'recours', 'secours', 'corps', 'temps', 'pays', 'moins', 'toujours'];
    if (in_array($word, $invariants, true)) { return $word; }
    if (mb_strlen($word, 'UTF-8') >= 5 && preg_match('/(?<![suipox])s$/u', $word)) {
        return mb_substr($word, 0, -1, 'UTF-8');
    }
    if (preg_match('/(?:eau|eu)x$/u', $word)) {
        return mb_substr($word, 0, -1, 'UTF-8');
    }
    return $word;
}

function commonSearchTermVariants(string $term): array
{
    $singular = commonSearchSingular($term);
    $variants = [$term, $singular];
    if ((mb_strlen($singular, 'UTF-8') >= 4 || preg_match('/(?:eau|eu)$/u', $singular)) && !preg_match('/[sxz]$/u', $singular) && !ctype_digit($singular)) {
        $variants[] = $singular . (preg_match('/(?:eau|eu)$/u', $singular) ? 'x' : 's');
    }
    return array_values(array_unique($variants));
}

function commonSearchQueryTerms(string $query): array
{
    $words = array_values(array_unique(commonSearchWords($query, true)));
    // Stable sorting preserves query order when lengths are equal.
    usort($words, static fn($left, $right) => mb_strlen($right, 'UTF-8') <=> mb_strlen($left, 'UTF-8'));
    $terms = [];
    foreach ($words as $word) {
        $terms[commonSearchSingular($word)] ??= $word;
    }
    return array_slice(array_values($terms), 0, 5);
}

function commonSearchWordQuality(string $word, string $term): float
{
    if ($word === $term) { return 1.0; }
    if (in_array($word, commonSearchTermVariants($term), true)) { return 0.94; }
    // Numbers and short acronyms only match as complete tokens.
    $stem = commonSearchSingular($term);
    if (ctype_digit($stem)) { return 0.0; }
    $length = mb_strlen($stem, 'UTF-8');
    if ($length >= 4 && str_starts_with($word, $stem)) { return 0.5; }
    if ($length >= 5 && str_contains($word, $stem)) { return 0.12; }
    return 0.0;
}

function commonSearchTextQuality(string $value, string $term): float
{
    $quality = 0.0;
    foreach (array_unique(commonSearchWords($value)) as $word) {
        $quality = max($quality, commonSearchWordQuality($word, $term));
        if ($quality === 1.0) { break; }
    }
    return $quality;
}

function commonBuildSearchMatchPattern(string $term): string
{
    $variants = array_map('commonBuildSearchTermPattern', commonSearchTermVariants($term));
    $pattern = '(?<![\p{L}\p{N}])(?:' . implode('|', $variants) . ')(?![\p{L}\p{N}])';
    $stem = commonSearchSingular($term);
    if (!ctype_digit($stem) && mb_strlen($stem, 'UTF-8') >= 4) {
        $prefix = mb_strlen($stem, 'UTF-8') >= 5 ? '' : '(?<![\p{L}\p{N}])';
        $pattern .= '|' . $prefix . commonBuildSearchTermPattern($stem);
    }
    return '(?:' . $pattern . ')';
}

if (!function_exists('commonBuildSearchTermPattern')) {
    function commonBuildSearchTermPattern($term)
    {
        $term = (string)$term;
        $cacheKey = $term;
        static $patterns = array();
        if (isset($patterns[$cacheKey])) {
            return $patterns[$cacheKey];
        }
        if (class_exists(\Normalizer::class)) {
            $decomposed = \Normalizer::normalize($term, \Normalizer::FORM_D);
            if (is_string($decomposed)) {
                $term = (string)preg_replace('/\p{Mn}/u', '', $decomposed);
            }
        }
        $term = function_exists('mb_strtolower') ? mb_strtolower($term, 'UTF-8') : strtolower($term);
        $characters = preg_split('//u', $term, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false) {
            return $patterns[$cacheKey] = preg_quote($term, '/');
        }

        $accentGroups = array(
            'a' => '[a\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}]',
            'c' => '[c\x{00E7}]',
            'e' => '[e\x{00E8}\x{00E9}\x{00EA}\x{00EB}]',
            'i' => '[i\x{00EC}\x{00ED}\x{00EE}\x{00EF}]',
            'n' => '[n\x{00F1}]',
            'o' => '[o\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}]',
            'u' => '[u\x{00F9}\x{00FA}\x{00FB}\x{00FC}]',
            'y' => '[y\x{00FD}\x{00FF}]',
        );
        $pattern = '';
        foreach ($characters as $character) {
            $pattern .= $accentGroups[$character] ?? preg_quote($character, '/');
        }
        return $patterns[$cacheKey] = $pattern;
    }
}
