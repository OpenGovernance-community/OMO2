<?php
namespace dbObject;

require_once dirname(__DIR__, 2) . '/common/search_text.php';

/** Comparable scores for every module; repeated fields never accumulate indefinitely. */
class TopbarSearchRanker
{
    public const FIELD_WEIGHTS = ['title' => 100, 'tags' => 75, 'summary' => 50, 'body' => 25, 'context' => 12];

    public static function score(array $fields, string $query): int
    {
        $terms = \commonSearchQueryTerms($query);
        if (!$terms) { return 0; }
        $qualityByField = [];
        $titleBonus = 0.0;
        $tagBonus = 0.0;
        foreach (self::FIELD_WEIGHTS as $field => $weight) {
            $qualityByField[$field] = array_fill(0, count($terms), 0.0);
            foreach ((array)($fields[$field] ?? []) as $value) {
                $words = array_unique(\commonSearchWords((string)$value));
                foreach ($terms as $index => $term) {
                    foreach ($words as $word) {
                        $qualityByField[$field][$index] = max($qualityByField[$field][$index], \commonSearchWordQuality($word, $term));
                        if ($qualityByField[$field][$index] === 1.0) { break; }
                    }
                }
                if ($field === 'title') {
                    $titleBonus = max($titleBonus, self::phraseQuality((string)$value, $query, true) * 50,
                        count($terms) > 1 ? self::phraseQuality((string)$value, $query, false) * 15 : 0);
                } elseif ($field === 'tags') {
                    $tagBonus = max($tagBonus, self::phraseQuality((string)$value, $query, true) * 20);
                }
            }
        }
        $weightedScore = 0.0;
        $coverage = 0.0;
        $totalImportance = 0.0;
        foreach ($terms as $index => $term) {
            $importance = 1 + min(12, mb_strlen($term, 'UTF-8')) / 24;
            $totalImportance += $importance;
            $evidence = [];
            $quality = 0.0;
            foreach (self::FIELD_WEIGHTS as $field => $weight) {
                $evidence[] = $weight * $qualityByField[$field][$index];
                $quality = max($quality, $qualityByField[$field][$index]);
            }
            rsort($evidence, SORT_NUMERIC);
            // A second independent field is a small corroboration, not another full score.
            $weightedScore += $importance * ($evidence[0] + 0.1 * $evidence[1]);
            $coverage += $importance * ($quality >= 0.94 ? 1.0 : $quality);
        }
        $coverage /= $totalImportance;
        if ($coverage === 0.0) { return 0; }
        $completeness = 0.2 + 0.8 * $coverage * $coverage;
        $score = ($weightedScore / $totalImportance + max($titleBonus, $tagBonus)) * $completeness;
        return max(1, (int)round($score));
    }

    private static function phraseQuality(string $value, string $query, bool $whole): float
    {
        $wanted = \commonSearchWords($query, true);
        $words = \commonSearchWords($value, true);
        if (!$wanted || ($whole && count($words) !== count($wanted))) { return 0.0; }
        $best = 0.0;
        for ($start = 0; $start <= count($words) - count($wanted); $start++) {
            $quality = 1.0;
            foreach ($wanted as $index => $term) {
                $match = \commonSearchWordQuality($words[$start + $index], $term);
                // A phrase bonus requires whole words (including regular plurals).
                $quality = min($quality, $match >= 0.94 ? $match : 0.0);
                if ($quality === 0.0) { break; }
            }
            $best = max($best, $quality);
        }
        return $best;
    }
}
