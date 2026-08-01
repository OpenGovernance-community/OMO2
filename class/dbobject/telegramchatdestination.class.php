<?php
namespace dbObject;

class TelegramChatDestination extends DbObject
{
    public const TYPE_ROLE = 'role';
    public const TYPE_PROJECT = 'project';

    public static function tableName()
    {
        return 'telegram_chat_destination';
    }

    public static function rules()
    {
        return [
            [['telegram_chat_id', 'telegram_thread_id', 'destination_type'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDproject', 'IDuser_configured'], 'fk'],
            [['telegram_chat_id', 'telegram_thread_id', 'destination_type'], 'string'],
            [['created_at', 'updated_at'], 'datetime'],
            [['active'], 'boolean'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'telegram_chat_id' => 'Discussion Telegram',
            'telegram_thread_id' => 'Sujet Telegram',
            'IDorganization' => 'Organisation',
            'destination_type' => 'Type de destination',
            'IDholon' => 'Role',
            'IDproject' => 'Projet',
            'IDuser_configured' => 'Configure par',
            'active' => 'Actif',
            'created_at' => 'Date de creation',
            'updated_at' => 'Date de modification',
        ];
    }

    public static function getOrder()
    {
        return 'updated_at DESC, id DESC';
    }

    public static function isStorageAvailable()
    {
        return self::tableExists(self::tableName());
    }

    public static function normalizeDestinationType($value)
    {
        $value = trim(strtolower((string)$value));
        return in_array($value, [self::TYPE_ROLE, self::TYPE_PROJECT], true) ? $value : '';
    }

    public static function findByTelegramChat($chatId, $threadId = null)
    {
        $chatId = trim((string)$chatId);
        $threadId = $threadId === null ? '' : trim((string)$threadId);
        if ($chatId === '') {
            return null;
        }

        if (!self::isStorageAvailable()) {
            return null;
        }

        $destination = new self();
        return $destination->load([
            ['telegram_chat_id', $chatId],
            ['telegram_thread_id', $threadId],
            ['active', 1],
        ]) ? $destination : null;
    }

    public static function saveForTelegramChat($chatId, $threadId, $organizationId, $destinationType, $destinationId, $configuredUserId)
    {
        $chatId = trim((string)$chatId);
        $threadId = $threadId === null ? '' : trim((string)$threadId);
        $organizationId = (int)$organizationId;
        $destinationType = self::normalizeDestinationType($destinationType);
        $destinationId = (int)$destinationId;
        $configuredUserId = (int)$configuredUserId;

        if ($chatId === '' || $organizationId <= 0 || $destinationType === '' || $destinationId <= 0 || $configuredUserId <= 0) {
            return null;
        }

        if (!self::isStorageAvailable()) {
            return null;
        }

        $destination = new self();
        $destination->load([
            ['telegram_chat_id', $chatId],
            ['telegram_thread_id', $threadId],
        ]);
        $destination->set('telegram_chat_id', $chatId);
        $destination->set('telegram_thread_id', $threadId);
        $destination->set('IDorganization', $organizationId);
        $destination->set('destination_type', $destinationType);
        $destination->set('IDholon', $destinationType === self::TYPE_ROLE ? $destinationId : null);
        $destination->set('IDproject', $destinationType === self::TYPE_PROJECT ? $destinationId : null);
        $destination->set('IDuser_configured', $configuredUserId);
        $destination->set('active', true);
        $now = new \DateTime();
        if ((int)$destination->getId() <= 0) {
            $destination->set('created_at', $now);
        }
        $destination->set('updated_at', $now);

        $result = $destination->save();
        return is_array($result) && !empty($result['status']) ? $destination : null;
    }

    public function deactivate()
    {
        if ((int)$this->getId() <= 0) {
            return false;
        }

        $this->set('active', false);
        $this->set('updated_at', new \DateTime());
        $result = $this->save();
        return is_array($result) && !empty($result['status']);
    }

    public function getRole()
    {
        if (self::normalizeDestinationType($this->get('destination_type')) !== self::TYPE_ROLE) {
            return null;
        }

        $role = new Holon();
        if (!$role->load((int)$this->get('IDholon')) || (int)$role->get('IDtypeholon') !== 1) {
            return null;
        }

        return $role;
    }

    public function getProject()
    {
        if (self::normalizeDestinationType($this->get('destination_type')) !== self::TYPE_PROJECT) {
            return null;
        }

        $project = new Project();
        return $project->load((int)$this->get('IDproject')) ? $project : null;
    }

    public function getDocumentContext()
    {
        $organizationId = (int)$this->get('IDorganization');
        if ($organizationId <= 0 || !(bool)$this->get('active')) {
            return null;
        }

        $type = self::normalizeDestinationType($this->get('destination_type'));
        if ($type === self::TYPE_ROLE) {
            $role = $this->getRole();
            if (!$role || !(bool)$role->get('active') || !(bool)$role->get('visible')) {
                return null;
            }

            $organization = new Organization();
            if (!$organization->load($organizationId) || !$organization->containsHolon($role)) {
                return null;
            }

            return [
                'type' => self::TYPE_ROLE,
                'organizationId' => $organizationId,
                'holonId' => (int)$role->getId(),
                'role' => $role,
                'project' => null,
            ];
        }

        if ($type === self::TYPE_PROJECT) {
            $project = $this->getProject();
            if (!$project || (int)$project->get('IDorganization') !== $organizationId || !(bool)$project->get('active')) {
                return null;
            }

            return [
                'type' => self::TYPE_PROJECT,
                'organizationId' => $organizationId,
                'holonId' => (int)$project->get('IDholon'),
                'role' => null,
                'project' => $project,
            ];
        }

        return null;
    }
}
?>
