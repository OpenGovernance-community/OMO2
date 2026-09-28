<?php
namespace dbObject;

class Permission extends DbObject
{
    public static function requiresExplicitAssignment(string $permissionKey): bool
    {
        return $permissionKey === 'CAN_DELETE_PARCOURS'
            || (bool)preg_match('/^CAN_(CREATE|EDIT|DELETE)_TYPE[1-9][0-9]*_PROPERTIES$/', $permissionKey);
    }

    public static function userCanInOrganization(string $permissionKey, int $organizationId, int $userId): bool
    {
        if ($userId <= 0 || $organizationId <= 0) return false;
        if (function_exists('commonUserHasAdminOverride') && \commonUserHasAdminOverride($userId, $organizationId)) return true;
        return HolonPermission::userHasPermissionForHolonContext($userId, $organizationId, $permissionKey, 0);
    }

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
            'CAN_EDIT_PROJECT' => [
                'title' => 'Modifier des projets',
                'description' => 'Autorise la modification des projets et de leurs taches.',
                'iscontextual' => true,
                'group' => 'projects',
            ],
            'CAN_CREATE_RULE' => [
                'title' => 'Creer des regles',
                'description' => 'Autorise la creation de regles dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'policy',
            ],
            'CAN_EDIT_RULE' => [
                'title' => 'Modifier des regles',
                'description' => 'Autorise la modification des regles dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'policy',
            ],
            'CAN_DELETE_RULE' => [
                'title' => 'Supprimer des regles',
                'description' => 'Autorise la suppression des regles dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'policy',
            ],
            'CAN_EDIT_INDICATOR' => [
                'title' => 'Modifier des indicateurs',
                'description' => 'Autorise la modification des indicateurs, de leurs valeurs, groupes et imports.',
                'iscontextual' => true,
                'group' => 'stats',
            ],
            'CAN_DELETE_INDICATOR' => [
                'title' => 'Supprimer des indicateurs',
                'description' => 'Autorise le retrait des indicateurs, groupes et imports du contexte cible.',
                'iscontextual' => true,
                'group' => 'stats',
            ],
            'CAN_EDIT_DOCUMENT' => [
                'title' => 'Modifier des documents',
                'description' => 'Autorise la modification des documents dans le respect de leur portee d edition.',
                'iscontextual' => true,
                'group' => 'documents',
            ],
            'CAN_DELETE_DOCUMENT' => [
                'title' => 'Supprimer des documents',
                'description' => 'Autorise la suppression des documents dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'documents',
            ],
            'CAN_EDIT_MEMBER_ASSIGNMENT' => [
                'title' => 'Modifier les affectations',
                'description' => 'Autorise la modification du focus et de la date de revue des affectations.',
                'iscontextual' => true,
                'group' => 'members',
            ],
            'CAN_DELETE_MEMBER' => [
                'title' => 'Retirer des membres',
                'description' => 'Autorise le retrait des membres et l annulation de leurs invitations, sans supprimer leur compte.',
                'iscontextual' => true,
                'group' => 'members',
            ],
            'CAN_EDIT_DECISION' => [
                'title' => 'Modifier des decisions',
                'description' => 'Autorise la gestion des prises de decision dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'decisions',
            ],
            'CAN_DELETE_DECISION' => [
                'title' => 'Supprimer des decisions',
                'description' => 'Autorise la suppression des prises de decision dans le respect de leur cycle de vie.',
                'iscontextual' => true,
                'group' => 'decisions',
            ],
            'CAN_EDIT_FAQ' => [
                'title' => 'Modifier des FAQ',
                'description' => 'Autorise la modification des FAQ dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'help',
            ],
            'CAN_DELETE_FAQ' => [
                'title' => 'Supprimer des FAQ',
                'description' => 'Autorise la suppression des FAQ dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'help',
            ],
            'CAN_PROPOSE_PROJECT' => [
                'title' => 'Proposer des projets',
                'description' => 'Autorise la proposition de projets au role ou cercle cible.',
                'iscontextual' => true,
                'group' => 'projects',
            ],
            'CAN_ADD_HOLON' => [
                'title' => 'Ajouter un holon',
                'description' => 'Autorise l ajout d un holon dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'holons',
            ],
            'CAN_EDIT_HOLON' => [
                'title' => 'Modifier des holons',
                'description' => 'Autorise la modification de holons dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'holons',
            ],
            'CAN_DELETE_HOLON' => [
                'title' => 'Supprimer des holons',
                'description' => 'Autorise la suppression de holons dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'holons',
            ],
            'CAN_MOVE_HOLON' => [
                'title' => 'Deplacer des holons',
                'description' => 'Autorise le deplacement des holons couverts par ce droit vers les destinations couvertes par ce meme droit.',
                'iscontextual' => true,
                'group' => 'holons',
            ],
            'CAN_EDIT_HOLON_BUDGET' => [
                'title' => 'Modifier les budgets de holons',
                'description' => 'Autorise la modification des budgets temps et argent des holons dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'budget',
            ],
            'CAN_EDIT_AFFECTATION_BUDGET' => [
                'title' => 'Modifier les budgets des affectations',
                'description' => 'Autorise la modification des budgets temps et argent des affectations dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'budget',
            ],
            'CAN_ADD_MEMBER' => [
                'title' => 'Ajouter un membre',
                'description' => 'Autorise l ajout d un membre dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'members',
            ],
            'CAN_ADD_ADMIN' => [
                'title' => 'Definir un admin de contexte',
                'description' => 'Autorise l attribution ou le retrait du statut admin dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'members',
            ],
            'CAN_CREATE_DOCUMENT' => [
                'title' => 'Creer des fichiers',
                'description' => 'Autorise la creation de fichiers dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'documents',
            ],
            'CAN_CREATE_DECISION' => [
                'title' => 'Creer des prises de decision',
                'description' => 'Autorise la creation de prises de decision dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'decisions',
            ],
            'CAN_CREATE_EVENT' => [
                'title' => 'Creer des dates',
                'description' => 'Autorise la creation de dates dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'calendar',
            ],
            'CAN_EDIT_EVENT' => [
                'title' => 'Modifier des dates',
                'description' => 'Autorise la modification de dates dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'calendar',
            ],
            'CAN_DELETE_EVENT' => [
                'title' => 'Supprimer des dates',
                'description' => 'Autorise la suppression de dates dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'calendar',
            ],
            'CAN_CLAIM_PV' => [
                'title' => 'Devenir secretaire de PV',
                'description' => 'Autorise a prendre le role de secretaire pendant une reunion associee a un PV.',
                'iscontextual' => true,
                'group' => 'documents',
            ],
            'CAN_CREATE_FAQ' => [
                'title' => 'Creer des FAQ',
                'description' => 'Autorise la creation de FAQ dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'help',
            ],
            'CAN_CREATE_PROCESS' => [
                'title' => 'Creer des processus',
                'description' => 'Autorise la creation de processus dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'processes',
            ],
            'CAN_EDIT_PROCESS' => [
                'title' => 'Modifier des processus',
                'description' => 'Autorise l ajout, la modification et la suppression des etapes et activites de processus dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'processes',
            ],
            'CAN_DELETE_PROCESS' => [
                'title' => 'Supprimer des processus',
                'description' => 'Autorise la suppression de processus dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'processes',
            ],
            'CAN_CREATE_RECURRING_TASK' => [
                'title' => 'Creer des taches recurrentes',
                'description' => 'Autorise la creation de taches recurrentes dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'recurring_tasks',
            ],
            'CAN_EDIT_RECURRING_TASK' => [
                'title' => 'Modifier des taches recurrentes',
                'description' => 'Autorise la modification des taches recurrentes dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'recurring_tasks',
            ],
            'CAN_DELETE_RECURRING_TASK' => [
                'title' => 'Supprimer des taches recurrentes',
                'description' => 'Autorise la suppression des taches recurrentes dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'recurring_tasks',
            ],
            'CAN_CREATE_PROJECT' => [
                'title' => 'Creer des projets',
                'description' => 'Autorise la creation de projets dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'projects',
            ],
            'CAN_DELETE_PROJECT' => [
                'title' => 'Supprimer des projets',
                'description' => 'Autorise la suppression de projets dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'projects',
            ],
            'CAN_CREATE_INDICATOR' => [
                'title' => 'Creer des indicateurs',
                'description' => 'Autorise la creation d indicateurs dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'stats',
            ],
            'CAN_CREATE_TYPE1_PROPERTIES' => ['title' => 'Creer les proprietes type1', 'description' => 'Autorise a creer les proprietes type1 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_EDIT_TYPE1_PROPERTIES' => ['title' => 'Modifier les proprietes type1', 'description' => 'Autorise uniquement a modifier les valeurs des proprietes type1 (textes, listes et elements de liste), sans changer leur structure.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_DELETE_TYPE1_PROPERTIES' => ['title' => 'Supprimer les proprietes type1', 'description' => 'Autorise a supprimer les proprietes type1 dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_CREATE_TYPE2_PROPERTIES' => ['title' => 'Creer les proprietes type2', 'description' => 'Autorise a creer les proprietes type2 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_EDIT_TYPE2_PROPERTIES' => ['title' => 'Modifier les proprietes type2', 'description' => 'Autorise uniquement a modifier les valeurs des proprietes type2 (textes, listes et elements de liste), sans changer leur structure.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_DELETE_TYPE2_PROPERTIES' => ['title' => 'Supprimer les proprietes type2', 'description' => 'Autorise a supprimer les proprietes type2 dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_CREATE_TYPE3_PROPERTIES' => ['title' => 'Creer les proprietes type3', 'description' => 'Autorise a creer les proprietes type3 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_EDIT_TYPE3_PROPERTIES' => ['title' => 'Modifier les proprietes type3', 'description' => 'Autorise uniquement a modifier les valeurs des proprietes type3 (textes, listes et elements de liste), sans changer leur structure.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_DELETE_TYPE3_PROPERTIES' => ['title' => 'Supprimer les proprietes type3', 'description' => 'Autorise a supprimer les proprietes type3 dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_CREATE_TYPE4_PROPERTIES' => ['title' => 'Creer les proprietes type4', 'description' => 'Autorise a creer les proprietes type4 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_EDIT_TYPE4_PROPERTIES' => ['title' => 'Modifier les proprietes type4', 'description' => 'Autorise uniquement a modifier les valeurs des proprietes type4 (textes, listes et elements de liste), sans changer leur structure.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_DELETE_TYPE4_PROPERTIES' => ['title' => 'Supprimer les proprietes type4', 'description' => 'Autorise a supprimer les proprietes type4 dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_CREATE_TYPE5_PROPERTIES' => ['title' => 'Creer les proprietes type5', 'description' => 'Autorise a creer les proprietes type5 et a modifier leur structure (nom, format, type et configuration) dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_EDIT_TYPE5_PROPERTIES' => ['title' => 'Modifier les proprietes type5', 'description' => 'Autorise uniquement a modifier les valeurs des proprietes type5 (textes, listes et elements de liste), sans changer leur structure.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_DELETE_TYPE5_PROPERTIES' => ['title' => 'Supprimer les proprietes type5', 'description' => 'Autorise a supprimer les proprietes type5 dans le contexte cible.', 'iscontextual' => true, 'group' => 'properties'],
            'CAN_EDIT_TEMPLATE_PROPERTIES' => [
                'title' => 'Modifier les proprietes de templates',
                'description' => 'Autorise la modification des proprietes definies par les templates dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'properties',
            ],
            'CAN_ADD_TEMPLATE_PROPERTIES' => [
                'title' => 'Ajouter des proprietes de templates',
                'description' => 'Autorise l ajout de proprietes definies par les templates dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'properties',
            ],
            'CAN_DELETE_TEMPLATE_PROPERTIES' => [
                'title' => 'Supprimer les proprietes de templates',
                'description' => 'Autorise le retrait des proprietes definies par les templates dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'properties',
            ],
            'CAN_EDIT_HOLON_PROPERTIES' => [
                'title' => 'Modifier les proprietes de holons',
                'description' => 'Autorise la modification des proprietes ajoutees directement a un holon dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'properties',
            ],
            'CAN_ADD_HOLON_PROPERTIES' => [
                'title' => 'Ajouter des proprietes de holons',
                'description' => 'Autorise l ajout de proprietes directement sur un holon dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'properties',
            ],
            'CAN_DELETE_HOLON_PROPERTIES' => [
                'title' => 'Supprimer les proprietes de holons',
                'description' => 'Autorise le retrait des proprietes ajoutees directement a un holon dans le contexte cible.',
                'iscontextual' => true,
                'group' => 'properties',
            ],
            'CAN_ADD_APP' => [
                'title' => 'Gerer les applications',
                'description' => 'Autorise la gestion des applications actives et de leur ordre dans l organisation.',
                'iscontextual' => false,
                'group' => 'organization',
            ],
            'CAN_CREATE_PARCOURS' => [
                'title' => 'Creer des parcours',
                'description' => 'Autorise la creation, l import et le detachement de parcours dans le contexte cible.',
                'iscontextual' => false,
                'group' => 'help',
            ],
            'CAN_EDIT_PARCOURS' => [
                'title' => 'Editer des parcours',
                'description' => 'Autorise la modification du contenu des parcours proprietaires et de leurs missions dans le contexte cible.',
                'iscontextual' => false,
                'group' => 'help',
            ],
            'CAN_DELETE_PARCOURS' => [
                'title' => 'Supprimer un parcours',
                'description' => 'Autorise la suppression des parcours de l organisation. Les parcours encore utilises sont retires du partage et conserves pour leurs utilisateurs existants.',
                'iscontextual' => false,
                'group' => 'help',
            ],
        ];
    }

    public static function getEditorGroupCatalog(array $lexicon = [])
    {
        return [
            'organization' => ['title' => 'Organisation', 'order' => 10],
            'holons' => ['title' => Organization::formatLexiconText('Holons', $lexicon), 'order' => 20, 'application' => 'structure'],
            'properties' => ['title' => 'Proprietes', 'order' => 30, 'application' => 'structure'],
            'members' => ['title' => 'Membres et roles', 'order' => 40, 'application' => 'team'],
            'calendar' => ['title' => 'Calendrier', 'order' => 50, 'application' => 'calendar'],
            'policy' => ['title' => 'Reglement', 'order' => 60, 'application' => 'policy'],
            'documents' => ['title' => 'Documents et PV', 'order' => 70, 'application' => 'documents'],
            'projects' => ['title' => 'Projets', 'order' => 80, 'application' => 'projects'],
            'stats' => ['title' => 'Indicateurs', 'order' => 90, 'application' => 'stats'],
            'recurring_tasks' => ['title' => 'Taches recurrentes', 'order' => 100, 'application' => 'activities'],
            'processes' => ['title' => 'Processus', 'order' => 110, 'application' => 'processus'],
            'decisions' => ['title' => 'Decisions', 'order' => 120, 'application' => 'decision'],
            'budget' => ['title' => 'Budget', 'order' => 130, 'application' => 'budget'],
            'help' => ['title' => 'Aide', 'order' => 140],
            'content' => ['title' => 'Contenus et reunions', 'order' => 990],
            'other' => ['title' => 'Autres droits', 'order' => 999],
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
        return self::memoizeRead([__FUNCTION__, (string)$permissionKey, (bool)$default],
            static fn () => self::loadPermissionContextual($permissionKey, $default));
    }

    protected static function loadPermissionContextual($permissionKey, $default = true)
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

    public static function getEditorCatalog(array $lexicon = [], ?array $enabledApplicationHashes = null)
    {
        $permissions = new \dbObject\ArrayPermission();
        $permissions->load([
            'orderBy' => [
                ['field' => 'title', 'dir' => 'ASC'],
                ['field' => 'permission_key', 'dir' => 'ASC'],
            ],
        ]);

        $groups = self::getEditorGroupCatalog($lexicon);
        $catalog = [];
        foreach ($permissions as $permission) {
            $permissionKey = (string)$permission->get('permission_key');
            // Keep historical assignments in storage, but expose the type-based rights only.
            if (preg_match('/^CAN_(ADD|EDIT|DELETE)_(HOLON|TEMPLATE)_PROPERTIES$/', $permissionKey)) {
                continue;
            }
            $definition = self::getBuiltInDefinition($permissionKey);
            $groupKey = trim((string)($definition['group'] ?? 'other'));
            if (!isset($groups[$groupKey])) {
                $groupKey = 'other';
            }
            $application = $groups[$groupKey]['application'] ?? null;
            if ($enabledApplicationHashes !== null && $application !== null
                && !in_array($application, $enabledApplicationHashes, true)) {
                continue;
            }
            $title = Organization::formatLexiconText((string)$permission->get('title'), $lexicon);
            $description = Organization::formatLexiconText((string)$permission->get('description'), $lexicon);
            if ($lexicon && preg_match('/^CAN_(CREATE|EDIT|DELETE)_(TYPE[1-9][0-9]*)_PROPERTIES$/', $permissionKey, $matches)) {
                $type = strtolower($matches[2]);
                if (!Property::isTypeEnabled($type, $lexicon)) continue;
                $label = Organization::getLexiconLabel($lexicon, $type);
                $title = str_replace($type, $label, $title);
                $description = str_replace($type, $label, $description);
            }
            $catalog[] = [
                'id' => (int)$permission->getId(),
                'key' => $permissionKey,
                'title' => $title,
                'description' => $description,
                'group' => $groupKey,
                'groupTitle' => (string)$groups[$groupKey]['title'],
                'groupOrder' => (int)$groups[$groupKey]['order'],
                'isContextual' => $permission->isContextual(),
                'rangeOptions' => \dbObject\HolonPermission::getEditorRangeCatalogForPermission($permissionKey, $permission->isContextual()),
            ];
        }

        usort($catalog, static function ($left, $right) {
            $groupOrderComparison = ((int)$left['groupOrder']) <=> ((int)$right['groupOrder']);
            if ($groupOrderComparison !== 0) {
                return $groupOrderComparison;
            }

            // Keep each object's actions together, independently of translated labels.
            $leftObject = preg_replace('/^CAN_[A-Z]+_/', '', (string)$left['key']);
            $rightObject = preg_replace('/^CAN_[A-Z]+_/', '', (string)$right['key']);
            $objectComparison = strnatcasecmp($leftObject, $rightObject);
            if ($objectComparison !== 0) {
                return $objectComparison;
            }

            $actionOrder = static function ($key) {
                if (str_starts_with($key, 'CAN_CREATE_') || str_starts_with($key, 'CAN_ADD_')) return 10;
                if (str_starts_with($key, 'CAN_EDIT_')) return 20;
                if (str_starts_with($key, 'CAN_DELETE_')) return 30;
                return 40;
            };
            $actionComparison = $actionOrder($left['key']) <=> $actionOrder($right['key']);
            if ($actionComparison !== 0) return $actionComparison;

            $titleComparison = strcasecmp((string)$left['title'], (string)$right['title']);
            if ($titleComparison !== 0) {
                return $titleComparison;
            }

            return strcasecmp((string)$left['key'], (string)$right['key']);
        });

        return $catalog;
    }
}

?>
