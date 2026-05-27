<?php
namespace dbObject;

class TranslationLanguage extends DbObject
{
    protected static $storageAvailable = null;

    public static function tableName()
    {
        return 'translation_languages';
    }

    public static function rules()
    {
        return [
            [['locale', 'name', 'native_name'], 'required'],
            [['locale', 'name', 'native_name'], 'string'],
            [['sort_order'], 'integer'],
            [['active', 'is_source'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'locale' => 'Code langue',
            'name' => 'Nom',
            'native_name' => 'Nom natif',
            'sort_order' => 'Ordre',
            'active' => 'Actif',
            'is_source' => 'Langue source',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'locale' => 'Code de langue BCP 47 utilise pour la traduction.',
            'name' => 'Nom de la langue dans la langue source du site.',
            'native_name' => 'Nom affiche dans la langue elle-meme.',
            'sort_order' => 'Ordre d affichage dans les selecteurs.',
            'active' => 'Autorise cette langue sur cette instance.',
            'is_source' => 'Indique la langue source du contenu.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'locale' => 10,
            'name' => 120,
            'native_name' => 120,
        ];
    }

    public static function getOrder()
    {
        return 'is_source DESC, sort_order ASC, locale ASC';
    }

    public static function isStorageAvailable($refresh = false)
    {
        if (!$refresh && self::$storageAvailable !== null) {
            return self::$storageAvailable;
        }

        $databaseName = trim((string)($GLOBALS['dbName'] ?? ''));
        if ($databaseName === '') {
            self::$storageAvailable = false;
            return false;
        }

        $tableCount = self::fetchValue(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = :schema
               AND table_name = :table",
            [
                'schema' => $databaseName,
                'table' => self::tableName(),
            ]
        );

        self::$storageAvailable = ((int)$tableCount > 0);
        return self::$storageAvailable;
    }

    public static function loadActiveCatalogRows()
    {
        if (!self::isStorageAvailable()) {
            return [];
        }

        $rows = self::fetchAll(
            'SELECT `locale`, `name`, `native_name`, `sort_order`, `active`, `is_source`
             FROM `translation_languages`
             WHERE `active` = 1
             ORDER BY `is_source` DESC, `sort_order` ASC, `locale` ASC'
        );

        return is_array($rows) ? $rows : [];
    }
}

?>
