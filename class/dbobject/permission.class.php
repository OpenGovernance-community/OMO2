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
        return [
            'CAN_ADD_MEMBER' => [
                'title' => 'Ajouter un membre',
                'description' => 'Autorise l ajout d un membre dans le contexte cible.',
            ],
            'CAN_ADD_ADMIN' => [
                'title' => 'Definir un admin de contexte',
                'description' => 'Autorise l attribution ou le retrait du statut admin dans le contexte cible.',
            ],
        ];
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
            $catalog[] = [
                'id' => (int)$permission->getId(),
                'key' => (string)$permission->get('permission_key'),
                'title' => (string)$permission->get('title'),
                'description' => (string)$permission->get('description'),
            ];
        }

        return $catalog;
    }
}

?>
