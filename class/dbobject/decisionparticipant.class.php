<?php
namespace dbObject;

class DecisionParticipant extends DbObject
{
    const ROLE_OWNER = 'owner';
    const ROLE_PARTICIPANT = 'participant';
    const ROLE_OBSERVER = 'observer';

    const STATUS_INVITED = 'invited';
    const STATUS_ACTIVE = 'active';
    const STATUS_DECLINED = 'declined';
    const STATUS_REVOKED = 'revoked';

    public static function tableName()
    {
        return 'decision_participant';
    }

    public static function rules()
    {
        return [
            [['IDdecision_process', 'role', 'status'], 'required'],
            [['id'], 'integer'],
            [['IDdecision_process', 'IDuser'], 'fk'],
            [['email'], 'mail'],
            [['display_name', 'role', 'status', 'access_token'], 'string'],
            [['parameters'], 'parameters'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at', 'invitation_sent_at', 'invitation_opened_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdecision_process' => 'Prise de decision',
            'IDuser' => 'Utilisateur',
            'email' => 'E-mail',
            'display_name' => 'Nom affiche',
            'role' => 'Role',
            'status' => 'Statut',
            'access_token' => 'Jeton public',
            'invitation_sent_at' => 'Invitation envoyee',
            'invitation_opened_at' => 'Invitation ouverte',
            'parameters' => 'Parametres',
            'active' => 'Active',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'email' => 'Permet de rattacher une personne invitee qui n a pas encore de compte.',
            'access_token' => 'Permet un acces public personnalise a la page de participation de cette personne.',
            'parameters' => 'Metadonnees additionnelles comme la source d invitation ou des droits fins.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'email' => 250,
            'display_name' => 190,
            'role' => 30,
            'status' => 30,
            'access_token' => 64,
        ];
    }

    public static function getOrder()
    {
        return 'created_at ASC, id ASC';
    }

    public static function getRoleCatalog()
    {
        return [
            self::ROLE_OWNER => 'Owner',
            self::ROLE_PARTICIPANT => 'Participant',
            self::ROLE_OBSERVER => 'Observer',
        ];
    }

    public static function getStatusCatalog()
    {
        return [
            self::STATUS_INVITED => 'Invite',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_DECLINED => 'Refuse',
            self::STATUS_REVOKED => 'Revoque',
        ];
    }

    public static function isValidRole($role)
    {
        return array_key_exists((string)$role, self::getRoleCatalog());
    }

    public static function normalizeRole($role)
    {
        $role = trim((string)$role);
        return self::isValidRole($role) ? $role : self::ROLE_PARTICIPANT;
    }

    public static function isValidStatus($status)
    {
        return array_key_exists((string)$status, self::getStatusCatalog());
    }

    public static function normalizeStatus($status)
    {
        $status = trim((string)$status);
        return self::isValidStatus($status) ? $status : self::STATUS_INVITED;
    }

    public static function findByDecisionAndUser($decisionProcessId, $userId)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_participant`
             WHERE `IDdecision_process` = :decision_process_id AND `IDuser` = :user_id
             ORDER BY `id` DESC
             LIMIT 1',
            [
                'decision_process_id' => (int)$decisionProcessId,
                'user_id' => (int)$userId,
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public static function findByDecisionAndEmail($decisionProcessId, $email)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_participant`
             WHERE `IDdecision_process` = :decision_process_id AND `email` = :email
             ORDER BY `id` DESC
             LIMIT 1',
            [
                'decision_process_id' => (int)$decisionProcessId,
                'email' => trim(mb_strtolower((string)$email, 'UTF-8')),
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public static function findByAccessToken($token)
    {
        $row = self::fetchRow(
            'SELECT * FROM `decision_participant`
             WHERE `access_token` = :access_token
             ORDER BY `id` DESC
             LIMIT 1',
            [
                'access_token' => trim((string)$token),
            ]
        );

        if (!is_array($row) || !isset($row['id'])) {
            return null;
        }

        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    protected static function generateAccessToken()
    {
        for ($attempt = 0; $attempt < 5; $attempt += 1) {
            $token = bin2hex(random_bytes(32));
            if (!self::findByAccessToken($token)) {
                return $token;
            }
        }

        throw new \RuntimeException('The decision participant access token could not be generated.');
    }

    protected function hasIdentity()
    {
        return (int)$this->get('IDuser') > 0 || trim((string)$this->get('email')) !== '';
    }

    public function save()
    {
        if (!$this->hasIdentity()) {
            return [
                'status' => false,
                'text' => 'A decision participant needs a linked user or an email.',
            ];
        }

        $email = trim((string)$this->get('email'));
        if ($email !== '') {
            $this->set('email', mb_strtolower($email, 'UTF-8'));
        }

        $this->set('role', self::normalizeRole($this->get('role')));
        $this->set('status', self::normalizeStatus($this->get('status')));

        $accessToken = trim((string)$this->get('access_token'));
        if ($accessToken !== '') {
            $this->set('access_token', $accessToken);
        }

        return parent::save();
    }

    public function isExternalInvite()
    {
        return (int)$this->get('IDuser') <= 0 && trim((string)$this->get('email')) !== '';
    }

    public function getIdentityLabel()
    {
        $displayName = trim((string)$this->get('display_name'));
        if ($displayName !== '') {
            return $displayName;
        }

        $email = trim((string)$this->get('email'));
        if ($email !== '') {
            return $email;
        }

        return 'Participant #' . (int)$this->getId();
    }

    public function getDecisionProcess()
    {
        $decision = new \dbObject\DecisionProcess();
        return $decision->load((int)$this->get('IDdecision_process')) ? $decision : null;
    }

    public function ensureAccessToken()
    {
        $existingToken = trim((string)$this->get('access_token'));
        if ($existingToken !== '') {
            return $existingToken;
        }

        $token = self::generateAccessToken();
        $this->set('access_token', $token);
        return $token;
    }

    public function markInvitationSent($dateTime = null)
    {
        if (!$dateTime instanceof \DateTimeInterface) {
            $dateTime = new \DateTimeImmutable('now');
        }

        $this->set('invitation_sent_at', $dateTime);
        return $this->save();
    }

    public function markInvitationOpened($dateTime = null)
    {
        if (!$dateTime instanceof \DateTimeInterface) {
            $dateTime = new \DateTimeImmutable('now');
        }

        $this->set('invitation_opened_at', $dateTime);
        return $this->save();
    }

    public function getPublicAccessUrl()
    {
        $token = $this->ensureAccessToken();
        return \commonBuildUrl('/common/decision_participation.php?token=' . rawurlencode($token), \commonGetRequestHost());
    }

    public function getPublicInvitationUrl()
    {
        return $this->getPublicAccessUrl();
    }
}

?>
