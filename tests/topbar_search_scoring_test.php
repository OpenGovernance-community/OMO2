<?php
declare(strict_types=1);

use dbObject\TopbarSearchRanker;

// Exercise the lowercase path used by shared_functions.php, without its session/DB bootstrap.
spl_autoload_register(static function (string $class): void {
    if ($class !== TopbarSearchRanker::class) { return; }
    $path = dirname(__DIR__) . '/class/' . str_replace('\\', '/', strtolower($class)) . '.class.php';
    // Windows would otherwise hide a filename case mismatch that breaks Linux deployments.
    if (!in_array(basename($path), scandir(dirname($path)), true)) {
        throw new RuntimeException('Class filename must match the lowercase autoload path: ' . $path);
    }
    require_once $path;
});
if (!class_exists(TopbarSearchRanker::class)) {
    throw new RuntimeException('Search ranker could not be autoloaded.');
}

function searchAssert(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}
function titleScore(string $title, string $query): int
{
    return TopbarSearchRanker::score(['title' => $title], $query);
}

searchAssert(commonSearchQueryTerms('quel est le processus de validation des factures') === ['validation', 'processus', 'factures'], 'Question words must not displace the business terms.');
searchAssert(commonSearchQueryTerms('PV budget organisation validation factures responsable gouvernance') === ['organisation', 'responsable', 'gouvernance', 'validation', 'factures'], 'Keep the five longest terms, not the first five.');
searchAssert(commonSearchQueryTerms('le la a de des') === [], 'Stopword-only queries must not match every object.');
searchAssert(commonSearchQueryTerms('PV RH SI') === ['pv', 'rh', 'si'], 'Business acronyms must remain searchable.');
searchAssert(count(commonSearchQueryTerms('gestion gestions gestion')) === 1, 'Repeated terms and regular plurals must not accumulate.');

$exact = titleScore('gestion', 'gestion');
$plural = titleScore('gestions', 'gestion');
$reverse = titleScore('gestion', 'gestions');
searchAssert($exact > $plural && $plural >= $exact * 0.9, 'Regular plurals must be close to exact matches.');
searchAssert($plural === $reverse, 'Plural matching must work in both directions.');
searchAssert(titleScore('digestion', 'gestion') < $exact * 0.2, 'An internal substring is weak evidence.');
searchAssert(titleScore('gestionnaire', 'gestion') > titleScore('digestion', 'gestion'), 'A word prefix is stronger than an internal substring.');
searchAssert(titleScore('Plan de gestion', 'gestion') === titleScore('Gestion du plan', 'gestion'), 'A whole word is equally relevant at the start or end of a title.');
searchAssert(titleScore('jeux', 'jeu') >= titleScore('jeu', 'jeu') * 0.9, 'Regular x plurals must work for short words too.');
searchAssert(titleScore('cour', 'cours') === 0, 'Do not strip the final s of a known invariant query word.');
searchAssert(titleScore('processu', 'processus') === 0, 'Processus must not lose its final s.');

$allInBody = TopbarSearchRanker::score(['body' => 'budget annuel 2026'], 'budget 2026');
$oneInTitle = titleScore('budget', 'budget 2026');
searchAssert($allInBody > $oneInTitle, 'Covering the complete request must beat one isolated title word.');
searchAssert(TopbarSearchRanker::score(['title'=>'budget', 'summary'=>'exercice 2026'], 'budget 2026') > $allInBody, 'Coverage can span title and summary.');
searchAssert(titleScore('budget 2026', 'budget 2026') > titleScore('2026 budget', 'budget 2026'), 'The exact phrase must beat reordered words.');
searchAssert($exact > TopbarSearchRanker::score(['tags'=>['gestion'],'body'=>array_fill(0,50,'gestion')], 'gestion'), 'An exact title must beat tags plus repetitive body text.');
searchAssert(TopbarSearchRanker::score(['body'=>['gestion']], 'gestion') === TopbarSearchRanker::score(['body'=>array_fill(0,100,'gestion')], 'gestion'), 'More properties must not inflate a score.');
searchAssert(TopbarSearchRanker::score(['summary'=>'gestion'], 'gestion') > TopbarSearchRanker::score(['context'=>'gestion'], 'gestion'), 'Object content must outweigh inherited context.');
$request = 'quel est le processus de validation des factures';
searchAssert(TopbarSearchRanker::score(['title'=>'Factures recurrentes', 'context'=>'Processus'], $request) > TopbarSearchRanker::score(['title'=>'Exemple de processus', 'context'=>'Processus'], $request), 'An object type mentioned in a question must support its business subject.');

searchAssert(titleScore('<p>Gestion</p>', 'gestion') === $exact, 'HTML formatting must not change ranking.');
searchAssert(titleScore('R&#233;union', 'reunion') === titleScore('Reunion', 'reunion'), 'HTML entities and accents must normalize consistently.');
searchAssert(titleScore('<script>gestion</script><style>.gestion{}</style>', 'gestion') === 0, 'Scripts and styles are not searchable content.');
searchAssert(titleScore('pv42', 'PV') === 0 && titleScore('PV du lundi', 'PV') > 0, 'Short acronyms require complete words.');
searchAssert(titleScore('Budget 12026', '2026') === 0, 'Numeric identifiers require complete tokens.');
searchAssert(titleScore('Budget 2026', '2026') > 0, 'Years must remain searchable.');
searchAssert(titleScore('Sans rapport', '% _') === 0, 'Punctuation must not act as SQL wildcards.');
searchAssert(TopbarSearchRanker::score([], 'gestion') === 0, 'Absent fields must never contribute points.');
searchAssert(TopbarSearchRanker::score(['body'=>str_repeat('autre ', 1000).'budget 2026'], 'budget 2026') === $allInBody, 'Ranking must use full content, not a truncated excerpt.');

preg_match('/' . commonBuildSearchMatchPattern('gestions') . '/iu', 'La gestion quotidienne', $match);
searchAssert(($match[0] ?? '') === 'gestion', 'Excerpts and highlights must recognize singular results for plural queries.');
preg_match('/' . commonBuildSearchMatchPattern('gestion') . '/iu', 'Les gestions quotidiennes', $match);
searchAssert(($match[0] ?? '') === 'gestions', 'Highlight the whole plural word.');

echo "topbar_search_scoring_test: OK\n";
