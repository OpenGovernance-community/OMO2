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

    public static function getActiveUserIdsForDecision($decisionProcessId)
    {
        $rows = self::fetchAll(
            'SELECT DISTINCT `IDuser` FROM `decision_participant`
             WHERE `IDdecision_process` = :decision_process_id
               AND `IDuser` IS NOT NULL
               AND `IDuser` > 0
               AND `active` = 1
               AND `status` NOT IN (:declined_status, :revoked_status)',
            [
                'decision_process_id' => (int)$decisionProcessId,
                'declined_status' => self::STATUS_DECLINED,
                'revoked_status' => self::STATUS_REVOKED,
            ]
        );
        return array_values(array_unique(array_filter(array_map(static function ($row) {
            return is_array($row) ? (int)($row['IDuser'] ?? 0) : 0;
        }, is_array($rows) ? $rows : []), static function ($userId) {
            return $userId > 0;
        })));
    }

    public static function getInvitedUserIdsForDecision($decisionProcessId)
    {
        $rows = self::fetchAll(
            'SELECT DISTINCT `IDuser` FROM `decision_participant`
             WHERE `IDdecision_process` = :decision_process_id
               AND `IDuser` IS NOT NULL
               AND `IDuser` > 0
               AND `active` = 1
               AND `role` != :owner_role
               AND `status` NOT IN (:declined_status, :revoked_status)',
            [
                'decision_process_id' => (int)$decisionProcessId,
                'owner_role' => self::ROLE_OWNER,
                'declined_status' => self::STATUS_DECLINED,
                'revoked_status' => self::STATUS_REVOKED,
            ]
        );
        return array_values(array_unique(array_filter(array_map(static function ($row) {
            return is_array($row) ? (int)($row['IDuser'] ?? 0) : 0;
        }, is_array($rows) ? $rows : []), static function ($userId) {
            return $userId > 0;
        })));
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

    protected function getRootParametersArray()
    {
        $parameters = $this->get('parameters');
        if (is_array($parameters)) {
            return $parameters;
        }

        $parameters = trim((string)$parameters);
        if ($parameters === '') {
            return [];
        }

        $decoded = json_decode($parameters, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected static function normalizePublicAccessCode($value)
    {
        return preg_replace('/[^0-9]/', '', trim((string)$value));
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

    public function getIdentityLabel($organizationId = 0)
    {
        $displayName = trim((string)$this->get('display_name'));
        if ($displayName !== '') {
            return $displayName;
        }

        $userId = (int)$this->get('IDuser');
        if ($userId > 0) {
            $user = new user();
            if ($user->load($userId)) {
                $userDisplayName = trim((string)$user->getScopedDisplayName((int)$organizationId));
                if ($userDisplayName !== '') {
                    return $userDisplayName;
                }
            }
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
        if ((int)$this->getId() > 0) {
            $saveResult = $this->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                $this->set('access_token', '');
                return '';
            }
        }

        return $token;
    }

    public function issuePublicAccessCode($ttlSeconds = 900)
    {
        if ((int)$this->getId() <= 0) {
            return [
                'status' => false,
                'message' => 'The decision participant must be saved before issuing a public access code.',
            ];
        }

        $ttlSeconds = max(300, (int)$ttlSeconds);
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $issuedAt = new \DateTimeImmutable('now');
        $expiresAt = $issuedAt->modify('+' . $ttlSeconds . ' seconds');

        $parameters = $this->getRootParametersArray();
        $parameters['public_access_code'] = [
            'hash' => password_hash(self::normalizePublicAccessCode($code), PASSWORD_DEFAULT),
            'issued_at' => $issuedAt->format('c'),
            'expires_at' => $expiresAt->format('c'),
        ];

        $this->set('parameters', $parameters);
        $saveResult = $this->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return [
                'status' => false,
                'message' => 'The public access code could not be saved.',
            ];
        }

        return [
            'status' => true,
            'code' => $code,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ];
    }

    public function clearPublicAccessCode()
    {
        $parameters = $this->getRootParametersArray();
        if (!isset($parameters['public_access_code'])) {
            return true;
        }

        unset($parameters['public_access_code']);
        $this->set('parameters', $parameters);
        $saveResult = $this->save();
        return is_array($saveResult) && !empty($saveResult['status']);
    }

    public function getPublicAccessCodeState()
    {
        $parameters = $this->getRootParametersArray();
        $state = isset($parameters['public_access_code']) && is_array($parameters['public_access_code'])
            ? $parameters['public_access_code']
            : [];
        $hash = trim((string)($state['hash'] ?? ''));
        $expiresAtRaw = trim((string)($state['expires_at'] ?? ''));
        $expiresAt = null;

        if ($expiresAtRaw !== '') {
            try {
                $expiresAt = new \DateTimeImmutable($expiresAtRaw);
            } catch (\Throwable $exception) {
                $expiresAt = null;
            }
        }

        $isIssued = ($hash !== '' && $expiresAtRaw !== '');
        $isExpired = !$isIssued
            || !($expiresAt instanceof \DateTimeInterface)
            || $expiresAt < new \DateTimeImmutable('now');

        return [
            'hash' => $hash,
            'expires_at_raw' => $expiresAtRaw,
            'expires_at' => $expiresAt,
            'is_issued' => $isIssued,
            'is_expired' => $isExpired,
            'is_usable' => $isIssued && !$isExpired,
        ];
    }

    public function hasPublicAccessCode($onlyUsable = false)
    {
        $state = $this->getPublicAccessCodeState();
        if (!$onlyUsable) {
            return !empty($state['is_issued']);
        }

        return !empty($state['is_usable']);
    }

    public function verifyPublicAccessCode($value, $consume = true)
    {
        $state = $this->getPublicAccessCodeState();
        $hash = trim((string)($state['hash'] ?? ''));
        $expiresAtRaw = trim((string)($state['expires_at_raw'] ?? ''));
        $normalizedCode = self::normalizePublicAccessCode($value);

        if ($hash === '' || $expiresAtRaw === '') {
            return [
                'status' => false,
                'reason' => 'missing_code',
            ];
        }

        if ($normalizedCode === '') {
            return [
                'status' => false,
                'reason' => 'empty_code',
            ];
        }

        $expiresAt = $state['expires_at'] ?? null;

        if (!($expiresAt instanceof \DateTimeInterface) || $expiresAt < new \DateTimeImmutable('now')) {
            return [
                'status' => false,
                'reason' => 'expired_code',
            ];
        }

        if (!password_verify($normalizedCode, $hash)) {
            return [
                'status' => false,
                'reason' => 'invalid_code',
            ];
        }

        if ($consume && !$this->clearPublicAccessCode()) {
            return [
                'status' => false,
                'reason' => 'consume_failed',
            ];
        }

        return [
            'status' => true,
            'reason' => 'verified',
        ];
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

    public static function buildPublicAccessPathFromToken($token, $intent = '')
    {
        $token = trim((string)$token);
        if ($token === '') {
            return '/common/decision_participation.php';
        }

        $path = '/decision/access/' . rawurlencode($token);
        $intent = trim((string)$intent);
        if ($intent === 'participate') {
            $path .= '/participate';
        }

        return $path;
    }

    public function getPublicAccessUrl($intent = '')
    {
        $token = $this->ensureAccessToken();
        if ($token === '') {
            return '';
        }

        return \commonBuildUrl(self::buildPublicAccessPathFromToken($token, $intent), \commonGetRequestHost());
    }

    public function getPublicInvitationUrl($intent = '')
    {
        return $this->getPublicAccessUrl($intent);
    }
}

?>
