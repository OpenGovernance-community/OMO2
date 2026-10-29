<?php
declare(strict_types=1);

namespace dbObject {
    // Isolated translation storage: no database, worker, or external provider.
    class TranslationBundle
    {
        public static int $lookups = 0;
        public static string $nestedFeedback = '';
        public function __construct(private array $values) {}
        public function get($key) { return $this->values[$key] ?? null; }
        public static function findByBundleAndLocale($key, $locale)
        {
            self::$lookups++;
            self::$nestedFeedback = UiFeedbackProbe::message('record.saved');
            $source = UiFeedbackProbe::source();
            return new self([
                'source_hash' => \translationBundleSourceHash($source), 'status' => 'ready',
                'translated_json' => json_encode(['probe' => ['text' => 'Saved for {name}.']]),
            ]);
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
}

namespace dbObject {
    final class UiFeedbackProbe extends DbObject
    {
        public static function rules() { return []; }
        public static function attributeLabels() { return []; }
        protected static function uiMessageSourceLang(): array
        {
            return ['probe' => ['text' => 'Enregistré pour {name}.', 'context' => 'Feedback translation test.']];
        }
        public static function source(): array { return self::uiMessageSourceLang(); }
        public static function message(string $key, array $variables = []): string { return self::uiMessage($key, $variables); }
    }
}

namespace {
    function uiTranslationCheck(bool $condition, string $message): void
    {
        if (!$condition) throw new RuntimeException($message);
    }
    $_COOKIE['lang'] = 'fr';
    uiTranslationCheck(\dbObject\UiFeedbackProbe::message('probe', ['name' => 'François']) === 'Enregistré pour François.', 'French source keeps accents and user values.');
    $_COOKIE['lang'] = 'en';
    uiTranslationCheck(\dbObject\UiFeedbackProbe::message('probe', ['name' => 'François']) === 'Saved for François.', 'Model feedback uses the requested translation.');
    uiTranslationCheck(\dbObject\TranslationBundle::$lookups === 1 && \dbObject\TranslationBundle::$nestedFeedback === 'Élément enregistré.', 'Saving a refresh job cannot recursively load translation bundles.');
    uiTranslationCheck(\dbObject\UiFeedbackProbe::message('probe', ['name' => '{count}']) === 'Saved for {count}.', 'User values are interpolated once.');
    uiTranslationCheck(\dbObject\TranslationBundle::$lookups === 1, 'Feedback loads one bundle per class and locale.');
    $_COOKIE['lang'] = 'fr';
    uiTranslationCheck(\dbObject\UiFeedbackProbe::message('probe', ['name' => 'Zoé']) === 'Enregistré pour Zoé.', 'Changing language does not reuse another locale.');

    require_once dirname(__DIR__) . '/common/admin_edit_translation.php';
    final class UiChoiceProbe
    {
        public static function tableName() { return 'ui_choice_probe'; }
        public static function attributeLabels() { return ['visibility' => 'Visibilité']; }
        public static function attributeValues() { return ['visibility' => [['private/type', 'Privé'], ['public_type', 'Public']]]; }
    }
    $object = new UiChoiceProbe();
    $source = adminEditBuildSourceLang($object);
    $option = UiChoiceProbe::attributeValues()['visibility'][0];
    $key = adminEditFieldOptionTranslationKey($object, 'visibility', $option[0]);
    uiTranslationCheck($source[$key]['text'] === 'Privé', 'Select option labels are included in the existing adminEdit bundle.');
    uiTranslationCheck(adminEditGetFieldOptionLabel($object, 'visibility', $option, [$key => ['text' => 'Private']], $source) === 'Private' && $option[0] === 'private/type', 'Only the display label is translated; the stored value stays intact.');

    foreach (omoUiJsSourceLang() as $entry) {
        foreach (['text', 'one', 'other'] as $form) {
            if (!isset($entry[$form])) continue;
            uiTranslationCheck(!preg_match('/^\s*["\']?\s*(?:data-|class\s*=|contenteditable\s*=|points\s*=)/u', $entry[$form]), 'HTML attributes and selectors must never enter a translation catalog.');
        }
    }
    $largeSource = omoUiJsSourceLang();
    $chunks = translationBundleSplitSourceForTranslation($largeSource);
    $merged = [];
    foreach ($chunks as $chunk) {
        uiTranslationCheck(count($chunk) <= 50, 'A translation request must fit the response budget.');
        $merged += $chunk;
    }
    uiTranslationCheck(count($chunks) > 1 && $merged === $largeSource, 'Large catalogs are split without losing keys, contexts, placeholders, or plural forms.');
    $oversized = ['paragraph' => ['text' => str_repeat('Été ', 4000), 'context' => 'Long entry.']];
    uiTranslationCheck(translationBundleSplitSourceForTranslation($oversized) === [$oversized], 'A long message is never cut in the middle.');
    echo "ui_translation_feedback_test: OK\n";
}
