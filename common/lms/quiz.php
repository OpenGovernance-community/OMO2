<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonLmsQuizFeedbackTexts(): array
{
    static $texts = null;
    if ($texts !== null) return $texts;
    $sourceLang = [
        'wellDone' => ['text' => 'Bien joué', 'context' => 'Heading above the educational explanation after a correct quiz answer.'],
        'next' => ['text' => 'Suivant', 'context' => 'Continue to the next quiz question after reading the explanation.'],
        'close' => ['text' => 'Fermer', 'context' => 'Complete and close the mission after reading the last quiz explanation.'],
    ];
    $lang = loadTranslationBundle('lms_quiz_feedback', translationBundleResolveRequestLocale('lang'), $sourceLang);
    $texts = [];
    foreach (array_keys($sourceLang) as $key) $texts[$key] = t($key, [], $lang, $sourceLang);
    return $texts;
}
