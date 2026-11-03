<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/lms/ui-translations.php';
require_once dirname(__DIR__) . '/common/lms/mission-translations.php';
require_once dirname(__DIR__) . '/omo/api/lms/ui_translations.php';

$_GET['lang'] = 'fr';
$uiSource = commonLmsUiSourceLang();
$missionSource = commonLmsMissionSourceLang();
foreach (['lms', 'omo/api/lms'] as $folder) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/' . $folder));
    foreach ($files as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') continue;
        preg_match_all('/(?:commonLmsUiT|omoLmsUiT)\(\s*\x27([^\x27]+)\x27/', file_get_contents($file->getPathname()), $matches);
        foreach ($matches[1] as $key) mcpCheck(isset($uiSource[$key]), $file . ': missing tutorial translation ' . $key);
    }
}
$texts = commonLmsMissionTexts();
foreach (['lms/mission-detail.js', 'omo/api/lms/getMissionDetail.js'] as $file) {
    preg_match_all('/lmsMissionText\.(\w+)/', file_get_contents(dirname(__DIR__) . '/' . $file), $matches);
    foreach ($matches[1] as $key) mcpCheck(isset($texts[$key]), $file . ': missing mission translation ' . $key);
}
mcpCheck($texts['homeworksTitle'] === 'Devoirs', 'Public and OMO homework titles use French.');
mcpCheck($texts['scrollToEnd'] === 'Faites défiler le contenu jusqu’en bas pour marquer cette mission comme lue.', 'Read instruction keeps accents and apostrophes.');
mcpCheck($texts['alertCheckAnswer'] === 'Impossible de vérifier cette réponse. Réessayez.', 'Quiz errors keep French accents.');
mcpCheck(str_contains($texts['remainingOne'], 'devoir pour') && str_contains($texts['remaining'], 'devoirs pour'), 'Remaining tasks have separate translated plural forms.');
mcpCheck(omoLmsUiT('saved_locally') === 'Enregistré localement', 'Legacy OMO wrapper shares the corrected bundle.');

$pdo = \dbObject\DbObject::getPdo();
$pdo->beginTransaction();
try {
    // Test actual translated bundles without retaining any changes to the user's translations.
    \dbObject\TranslationBundle::saveTranslatedBundle('omo_lms_ui', 'en', translationBundleSourceHash($uiSource),
        json_encode(['login' => ['text' => 'Sign in'], 'done_on' => ['text' => 'Completed on {date}']], JSON_THROW_ON_ERROR), 'approved');
    \dbObject\TranslationBundle::saveTranslatedBundle('omo_lms_mission_detail', 'en', translationBundleSourceHash($missionSource),
        json_encode([
            'lms.mission_detail.homeworks.title' => ['text' => 'Tasks'],
            'lms.mission_detail.validation.remaining_count' => ['one' => 'Finish {total} task.', 'other' => 'Finish {total} tasks.'],
        ], JSON_THROW_ON_ERROR), 'approved');
    $_GET['lang'] = 'en';
    mcpCheck(commonLmsUiT('login') === 'Sign in' && omoLmsUiT('login') === 'Sign in', 'Both public and OMO helpers use the requested language.');
    mcpCheck(commonLmsUiT('done_on', ['date' => '2026-11-03']) === 'Completed on 2026-11-03', 'Translated messages preserve interpolation.');
    $translated = commonLmsMissionTexts();
    mcpCheck($translated['homeworksTitle'] === 'Tasks' && $translated['remainingOne'] === 'Finish {count} task.'
        && $translated['remaining'] === 'Finish {count} tasks.', 'Mission JavaScript receives translated singular and plural templates.');
} finally {
    $pdo->rollBack();
    $_GET['lang'] = 'fr';
}
echo "PASS tutorial translation keys, French typography, public/OMO bundles, interpolation and plural templates\n";
