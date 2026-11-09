<?php
declare(strict_types=1);

namespace dbObject {
    /** Isolated translation cache: this test never writes SQL or calls a provider. */
    final class TranslationBundle
    {
        public static array $lookups = [];
        public function __construct(private array $data) {}
        public function get($key) { return $this->data[$key] ?? null; }
        public static function findByBundleAndLocale($key, $locale)
        {
            self::$lookups[] = [$key, $locale];
            $source = match ($key) {
                'dbobject_eventrecurrence_messages' => (new \ReflectionMethod(EventRecurrence::class, 'uiMessageSourceLang'))->invoke(null),
                'dbobject_usercompetence_messages' => (new \ReflectionMethod(UserCompetence::class, 'uiMessageSourceLang'))->invoke(null),
                'common_account_translations' => (new \ReflectionFunction('commonAccountUiT'))->getStaticVariables()['sourceLang'],
                default => throw new \RuntimeException('Unexpected test bundle: ' . $key),
            };
            return new self([
                'source_hash' => \translationBundleSourceHash($source), 'status' => 'ready',
                'translated_json' => json_encode([
                    'copy.recurrence_invalide' => ['text' => 'Ungültige Wiederholung.'],
                    'copy.debutant' => ['text' => 'Anfänger'],
                    'copy.avance' => ['text' => 'Fortgeschritten'],
                    'copy.le_mot_de_passe_doit_faire_au_moins_minlength_caracteres_et_contenir_u' => ['text' => 'Mindestens {minLength} Zeichen.'],
                ]),
            ]);
        }
    }
}

namespace {
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/eventrecurrence.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/usercompetence.class.php';
    require_once dirname(__DIR__) . '/common/account-translations.php';
    require_once dirname(__DIR__) . '/common/admin_edit_translation.php';

    final class CorrectedMetadataProbe extends \dbObject\DbObject
    {
        public static function tableName() { return 'translation_probe'; }
        public static function rules() { return []; }
        public static function attributeLabels() { return ['level' => 'Niveau']; }
        public static function attributeValues() { return ['level' => [[1, \dbObject\UserCompetence::getLevelOptions()[1]]]]; }
    }

    function correctedTranslationCheck(bool $condition, string $message): void
    {
        if (!$condition) throw new RuntimeException($message);
    }
    $_COOKIE['lang'] = 'de';
    try {
        \dbObject\EventRecurrence::normalizeSettings(['frequency' => 'invalid']);
        throw new RuntimeException('Expected recurrence validation.');
    } catch (InvalidArgumentException $error) {
        correctedTranslationCheck($error->getMessage() === 'Ungültige Wiederholung.', 'Model errors must use their translated bundle.');
    }
    $settings = \dbObject\EventRecurrence::normalizeSettings(['frequency' => 'monthly_day', 'weekend_shift' => 'next', 'document_mode' => 'reuse']);
    correctedTranslationCheck($settings['frequency'] === 'monthly_day' && $settings['weekend_shift'] === 'next'
        && $settings['document_mode'] === 'reuse', 'Stored text keys must stay identical in another locale.');
    $levels = \dbObject\UserCompetence::getLevelOptions();
    correctedTranslationCheck(array_keys($levels) === [1, 2, 3, 4, 5] && $levels[1] === 'Anfänger'
        && $levels[4] === 'Fortgeschritten', 'Choice identifiers stay stable while display labels change.');
    $metadata = adminEditBuildSourceLang(new CorrectedMetadataProbe());
    $optionKey = adminEditFieldOptionTranslationKey(new CorrectedMetadataProbe(), 'level', 1);
    correctedTranslationCheck($metadata[$optionKey]['text'] === 'Débutant', 'Translated requests must not pollute French adminEdit source catalogs.');
    correctedTranslationCheck(\dbObject\UserCompetence::getLevelOptions()[1] === 'Anfänger', 'Collecting source metadata restores the active display language.');
    correctedTranslationCheck(commonAccountUiT('copy.le_mot_de_passe_doit_faire_au_moins_minlength_caracteres_et_contenir_u', ['minLength' => 16])
        === 'Mindestens 16 Zeichen.', 'Shared validation messages interpolate named variables after translation.');
    $lookups = count(\dbObject\TranslationBundle::$lookups);
    commonAccountUiT('copy.le_mot_de_passe_doit_faire_au_moins_minlength_caracteres_et_contenir_u', ['minLength' => 20]);
    correctedTranslationCheck(count(\dbObject\TranslationBundle::$lookups) === $lookups, 'A shared bundle loads once per locale.');
    $_COOKIE['lang'] = 'fr';
    correctedTranslationCheck(\dbObject\UserCompetence::getLevelOptions()[1] === 'Débutant', 'French source accents remain intact.');
    echo "omo_corrected_text_translations_test: OK\n";
}
