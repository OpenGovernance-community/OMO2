<?php
namespace dbObject;

class Permission extends DbObject
{
    public static function tableName()
    {
        return 'permission';
    }

    public static function rules()
    {
        return [
            [['permission_key', 'title', 'description'], 'required'],
            [['id'], 'integer'],
            [['iscontextual'], 'boolean'],
            [['permission_key', 'title'], 'string'],
            [['description'], 'text'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'permission_key' => 'Cle',
            'title' => 'Titre',
            'description' => 'Description',
            'iscontextual' => 'Contextuel',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'permission_key' => 'Code unique et explicite du droit dans le logiciel.',
            'title' => 'Libelle court utilise dans les listes et formulaires.',
            'description' => 'Description detaillee du droit accorde.',
            'iscontextual' => 'Definit si le droit depend du holon de contexte ou s il s applique globalement a l organisation.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'permission_key' => 190,
            'title' => 190,
        ];
    }

    public static function getOrder()
    {
        return 'title ASC, permission_key ASC';
    }

    public static function getMemberManagementCatalog()
    {
        $catalog = self::getBuiltInCatalog();

        return [
            'CAN_ADD_MEMBER' => $catalog['CAN_ADD_MEMBER'],
            'CAN_ADD_ADMIN' => $catalog['CAN_ADD_ADMIN'],
        ];
    }

    protected static function getBuiltInDefinition($permissionKey)
    {
        $permissionKey = trim((string)$permissionKey);
        $catalog = self::getBuiltInCatalog();
        return $permissionKey !== '' && isset($catalog[$permissionKey]) ? $catalog[$permissionKey] : null;
    }

    public static function getBuiltInCatalog()
    {
        return [
            'CAN_ADD_MEMBER' => [
                'title' => 'Ajouter un membre',
                'description' => 'Autorise l ajout d un membre dans le contexte cible.',
                'iscontextual' => true,
            ],
            'CAN_ADD_ADMIN' => [
                'title' => 'Definir un admin de contexte',
                'description' => 'Autorise l attribution ou le retrait du statut admin dans le contexte cible.',
                'iscontextual' => true,
            ],
            'CAN_CREATE_DOCUMENT' => [
                'title' => 'Creer des fichiers',
                'description' => 'Autorise la creation de fichiers dans le contexte cible.',
                'iscontextual' => true,
            ],
            'CAN_CREATE_DECISION' => [
                'title' => 'Creer des prises de decision',
                'description' => 'Autorise la creation de prises de decision dans le contexte cible.',
                'iscontextual' => true,
            ],
            'CAN_CREATE_EVENT' => [
                'title' => 'Creer des dates',
                'description' => 'Autorise la creation de dates dans le contexte cible.',
                'iscontextual' => true,
            ],
            'CAN_CREATE_FAQ' => [
                'title' => 'Creer des FAQ',
                'description' => 'Autorise la creation de FAQ dans le contexte cible.',
                'iscontextual' => true,
            ],
            'CAN_CREATE_PARCOURS' => [
                'title' => 'Creer des parcours',
                'description' => 'Autorise la creation, l import, la suppression et le detachement de parcours dans le contexte cible.',
                'iscontextual' => false,
            ],
            'CAN_EDIT_PARCOURS' => [
                'title' => 'Editer des parcours',
                'description' => 'Autorise la modification du contenu des parcours proprietaires et de leurs missions dans le contexte cible.',
                'iscontextual' => false,
            ],
        ];
    }

    public static function hasIsContextualColumn()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = (bool)self::fetchValue(
            "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'permission'
              AND COLUMN_NAME = 'iscontextual'
            LIMIT 1"
        );

        return $cache;
    }

    public function isContextual()
    {
        $permissionKey = trim((string)$this->get('permission_key'));

        if (self::hasIsContextualColumn()) {
            return (bool)$this->get('iscontextual');
        }

        $builtInDefinition = self::getBuiltInDefinition($permissionKey);
        if (is_array($builtInDefinition)) {
            return array_key_exists('iscontextual', $builtInDefinition)
                ? (bool)$builtInDefinition['iscontextual']
                : true;
        }

        return true;
    }

    public static function isPermissionContextual($permissionKey, $default = true)
    {
        $permissionKey = trim((string)$permissionKey);
        if ($permissionKey === '') {
            return (bool)$default;
        }

        if (self::hasIsContextualColumn()) {
            $row = self::fetchRow(
                'SELECT `iscontextual` FROM `permission` WHERE `permission_key` = :permission_key LIMIT 1',
                ['permission_key' => $permissionKey]
            );
            if (is_array($row) && array_key_exists('iscontextual', $row)) {
                return (bool)$row['iscontextual'];
            }
        }

        $builtInDefinition = self::getBuiltInDefinition($permissionKey);
        if (is_array($builtInDefinition) && array_key_exists('iscontextual', $builtInDefinition)) {
            return (bool)$builtInDefinition['iscontextual'];
        }

        return (bool)$default;
    }

    public static function getContextualMap(array $permissionKeys)
    {
        $map = [];
        $normalizedKeys = array_values(array_unique(array_filter(array_map('strval', $permissionKeys), static function ($value) {
            return trim((string)$value) !== '';
        })));

        foreach ($normalizedKeys as $permissionKey) {
            $map[$permissionKey] = self::isPermissionContextual($permissionKey, true);
        }

        return $map;
    }

    public static function findByKey($permissionKey)
    {
        $row = self::fetchRow(
            'SELECT * FROM `permission` WHERE `permission_key` = :permission_key LIMIT 1',
            ['permission_key' => trim((string)$permissionKey)]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $permission = new self();
        $permission->loadFromArray($row);
        $permission->setId((int)$row['id']);
        return $permission;
    }

    public static function existsKey($permissionKey)
    {
        static $cache = array();

        $permissionKey = trim((string)$permissionKey);
        if ($permissionKey === '') {
            return false;
        }

        if (array_key_exists($permissionKey, $cache)) {
            return $cache[$permissionKey];
        }

        $cache[$permissionKey] = (bool)self::fetchValue(
            'SELECT 1 FROM `permission` WHERE `permission_key` = :permission_key LIMIT 1',
            ['permission_key' => $permissionKey]
        );

        return $cache[$permissionKey];
    }

    public static function getEditorCatalog()
    {
        $permissions = new \dbObject\ArrayPermission();
        $permissions->load([
            'orderBy' => [
                ['field' => 'title', 'dir' => 'ASC'],
                ['field' => 'permission_key', 'dir' => 'ASC'],
            ],
        ]);

        $catalog = [];
        foreach ($permissions as $permission) {
            $permissionKey = (string)$permission->get('permission_key');
            $catalog[] = [
                'id' => (int)$permission->getId(),
                'key' => $permissionKey,
                'title' => (string)$permission->get('title'),
                'description' => (string)$permission->get('description'),
                'isContextual' => $permission->isContextual(),
                'rangeOptions' => \dbObject\HolonPermission::getEditorRangeCatalogForPermission($permissionKey, $permission->isContextual()),
            ];
        }

        return $catalog;
    }
}

?>
