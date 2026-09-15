<?php
namespace dbObject;

class OrganizationalMaturityInvitation extends DbObject
{
    public static function tableName() { return 'organizational_maturity_invitation'; }
    public static function rules()
    {
        return [
            [['id', 'public_access'], 'integer'],
            [['IDorganization', 'IDuser'], 'fk'],
            [['email', 'token', 'access_code_hash'], 'string'],
            [['created_at', 'updated_at', 'last_sent_at', 'access_code_expires_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDorganization' => 'Organisation', 'IDuser' => 'Utilisateur', 'email' => 'E-mail', 'token' => 'Lien invitation', 'public_access' => 'Acces public', 'access_code_hash' => 'Code acces', 'access_code_expires_at' => 'Code expire le', 'created_at' => 'Cree le', 'updated_at' => 'Modifie le', 'last_sent_at' => 'Envoye le'];
    }
    public static function findByToken($token)
    {
        $token = trim((string)$token);
        if (!preg_match('/^[a-f0-9]{32}$/i', $token)) return null;
        $row = self::fetchRow('SELECT * FROM organizational_maturity_invitation WHERE token = :token LIMIT 1', ['token' => $token]);
        if ($row === false) return null;
        $item = new self(); $item->loadFromArray($row); $item->setId((int)$row['id']); return $item;
    }

    public static function findByOrganizationAndEmail($organizationId, $email)
    {
        $organizationId = (int)$organizationId;
        $email = self::normalizeEmail($email);
        if ($organizationId <= 0 || $email === '') {
            return null;
        }
        $row = self::fetchRow(
            'SELECT * FROM organizational_maturity_invitation WHERE IDorganization = :organization_id AND email = :email LIMIT 1',
            ['organization_id' => $organizationId, 'email' => $email]
        );
        if ($row === false) {
            return null;
        }
        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }
    public static function issueForEmails($organizationId, array $emails)
    {
        $organizationId = (int)$organizationId;
        $organization = new Organization();
        if ($organizationId <= 0 || !$organization->load($organizationId)) return ['status' => false, 'message' => 'Organisation introuvable.'];
        $issued = [];
        foreach (array_values(array_unique(array_filter(array_map([self::class, 'normalizeEmail'], $emails)))) as $email) {
            $item = new self();
            if (!$item->load([['IDorganization', $organizationId], ['email', $email]])) {
                try { $token = bin2hex(random_bytes(16)); } catch (\Throwable $error) { return ['status' => false, 'message' => 'Generation de lien impossible.']; }
                $item->set('IDorganization', $organizationId); $item->set('email', $email); $item->set('token', $token); $item->set('created_at', new \DateTimeImmutable());
            }
            $user = new User();
            if ($user->load(['email', $email])) $item->set('IDuser', (int)$user->getId());
            $item->set('updated_at', new \DateTimeImmutable());
            if (empty($item->save()['status']) || !$item->sendEmail()) return ['status' => false, 'message' => 'Envoi impossible pour ' . $email . '.'];
            $item->set('last_sent_at', new \DateTimeImmutable()); $item->save(); $issued[] = $email;
        }
        return ['status' => true, 'emails' => $issued];
    }

    public static function findOrCreateForEmail($organizationId, $email)
    {
        $organizationId = (int)$organizationId;
        $email = self::normalizeEmail($email);
        $organization = new Organization();
        if ($organizationId <= 0 || !$organization->load($organizationId) || $email === '') {
            return null;
        }

        $item = new self();
        if (!$item->load([['IDorganization', $organizationId], ['email', $email]])) {
            try {
                $token = bin2hex(random_bytes(16));
            } catch (\Throwable $error) {
                return null;
            }
            $item->set('IDorganization', $organizationId);
            $item->set('email', $email);
            $item->set('token', $token);
            $item->set('created_at', new \DateTimeImmutable());
        }
        $item->set('public_access', 1);
        $user = new User();
        if ($user->load(['email', $email])) {
            $item->set('IDuser', (int)$user->getId());
        }
        $item->set('updated_at', new \DateTimeImmutable());
        return !empty($item->save()['status']) ? $item : null;
    }

    public function issuePublicAccessCode($ttlSeconds = 900)
    {
        if ((int)$this->getId() <= 0) {
            return ['status' => false];
        }
        $ttlSeconds = max(300, (int)$ttlSeconds);
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = (new \DateTimeImmutable())->modify('+' . $ttlSeconds . ' seconds');
        $this->set('access_code_hash', password_hash($code, PASSWORD_DEFAULT));
        $this->set('access_code_expires_at', $expiresAt);
        $this->set('updated_at', new \DateTimeImmutable());
        if (empty($this->save()['status'])) {
            return ['status' => false];
        }
        return ['status' => true, 'code' => $code, 'expires_at' => $expiresAt];
    }

    public function clearPublicAccessCode()
    {
        $this->set('access_code_hash', null);
        $this->set('access_code_expires_at', null);
        $this->set('updated_at', new \DateTimeImmutable());
        return !empty($this->save()['status']);
    }

    public function verifyPublicAccessCode($value, $consume = true)
    {
        $code = preg_replace('/\s+/', '', (string)$value);
        $hash = trim((string)$this->get('access_code_hash'));
        $expiresAt = $this->get('access_code_expires_at');
        if ($code === '') {
            return ['status' => false, 'reason' => 'empty_code'];
        }
        if ($hash === '' || !$expiresAt instanceof \DateTimeInterface) {
            return ['status' => false, 'reason' => 'missing_code'];
        }
        if ($expiresAt < new \DateTimeImmutable()) {
            return ['status' => false, 'reason' => 'expired_code'];
        }
        if (!password_verify($code, $hash)) {
            return ['status' => false, 'reason' => 'invalid_code'];
        }
        if ($consume && !$this->clearPublicAccessCode()) {
            return ['status' => false, 'reason' => 'consume_failed'];
        }
        return ['status' => true];
    }

    public static function issueForSelections($organizationId, array $holonIds, array $userIds, array $emails)
    {
        $organizationId = (int)$organizationId;
        $organization = new Organization();
        if ($organizationId <= 0 || !$organization->load($organizationId)) {
            return ['status' => false, 'message' => 'Organisation introuvable.'];
        }

        $emailMap = [];
        foreach ($userIds as $userId) {
            self::addActiveOrganizationUserEmail($organizationId, (int)$userId, $emailMap);
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $rootHolonId = $rootHolon ? (int)$rootHolon->getId() : 0;
        foreach (array_values(array_unique(array_filter(array_map('intval', $holonIds)))) as $holonId) {
            $holon = new Holon();
            if (!$holon->load($holonId) || (int)$holon->get('IDholon_org') !== $rootHolonId) {
                continue;
            }
            foreach ($holon->getVisibleDescendantIds(true) as $descendantId) {
                $descendant = new Holon();
                if (!$descendant->load((int)$descendantId)) {
                    continue;
                }
                foreach ($descendant->getDirectActiveMemberUserIds($organizationId) as $userId) {
                    self::addUserEmail((int)$userId, $emailMap);
                }
            }
        }

        foreach ($emails as $email) {
            $email = self::normalizeEmail($email);
            if ($email !== '') {
                $emailMap[$email] = $email;
            }
        }

        if (count($emailMap) === 0) {
            return ['status' => false, 'message' => 'Choisissez au moins une personne ou une adresse e-mail.'];
        }
        return self::issueForEmails($organizationId, array_values($emailMap));
    }
    public function resolveOrCreateUser()
    {
        $email = self::normalizeEmail($this->get('email')); if ($email === '') return null;
        $user = new User();
        if (!$user->load(['email', $email])) { $user->set('email', $email); $user->set('active', 1); if (empty($user->save()['status'])) return null; }
        $this->set('IDuser', (int)$user->getId()); $this->set('updated_at', new \DateTimeImmutable()); $this->save(); return $user;
    }
    public function getOrganizationObject()
    {
        $organization = new Organization(); return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }
    public function sendEmail()
    {
        require_once dirname(__DIR__, 2) . '/common/email_layout.php';
        $organization = $this->getOrganizationObject(); if (!$organization) return false;
        $name = trim((string)$organization->get('name')) ?: 'votre organisation';
        $url = appBuildAbsoluteUrl('/survey/?invitation=' . rawurlencode((string)$this->get('token')));
        $message = \commonRenderMailLayout(['brand_name' => $name, 'brand_color' => (string)$organization->get('color'), 'logo_url' => \commonBuildAbsoluteAssetUrl((string)$organization->get('logo')), 'banner_url' => \commonBuildAbsoluteAssetUrl((string)$organization->get('banner')), 'heading' => 'Partagez votre perception de ' . $name, 'intro_html' => '<p style="margin:0; color:#475569; line-height:1.7;">Votre regard aidera cette organisation a mieux comprendre sa maturite et ses ambitions.</p>', 'body_html' => '<p style="margin:0; color:#475569; line-height:1.7;">Le questionnaire prend environ dix minutes. Vous pourrez revenir sur vos reponses avec ce meme lien.</p>', 'button_label' => 'Evaluer l organisation', 'button_url' => $url]);
        $from = trim((string)($GLOBALS['mailUser'] ?? '')); if ($from === '') $from = 'noreply@' . (preg_replace('/:\d+$/', '', \commonGetRootHost() ?: 'localhost'));
        return \myHTMLMail([$from, $name], (string)$this->get('email'), 'Votre perception de ' . $name, $message);
    }

    public function sendPublicAccessCodeEmail($publicAccessUrl)
    {
        require_once dirname(__DIR__, 2) . '/common/email_layout.php';
        $organization = $this->getOrganizationObject();
        if (!$organization) {
            return false;
        }
        $codeResult = $this->issuePublicAccessCode(900);
        if (empty($codeResult['status'])) {
            return false;
        }
        $name = trim((string)$organization->get('name')) ?: 'votre organisation';
        $directUrl = appBuildAbsoluteUrl('/survey/?invitation=' . rawurlencode((string)$this->get('token')));
        $expiresAt = $codeResult['expires_at'] ?? null;
        $expiresLabel = $expiresAt instanceof \DateTimeInterface ? $expiresAt->format('d.m.Y H:i') : '';
        $codeHtml = '<div style="display:inline-block;padding:16px 22px;background:#f3f4f6;border-radius:12px;border:1px solid #e5e7eb;font:700 32px/1.2 Consolas,Monaco,monospace;letter-spacing:.22em;color:#111827;">'
            . htmlspecialchars((string)$codeResult['code'], ENT_QUOTES, 'UTF-8') . '</div>';
        if ($expiresLabel !== '') {
            $codeHtml .= '<p style="margin:14px 0 0;color:#64748b;line-height:1.6;">Ce code est valable jusqu’au ' . htmlspecialchars($expiresLabel, ENT_QUOTES, 'UTF-8') . '.</p>';
        }
        $message = \commonRenderMailLayout([
            'brand_name' => $name,
            'brand_color' => (string)$organization->get('color'),
            'logo_url' => \commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
            'banner_url' => \commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
            'heading' => 'Partagez votre perception de ' . $name,
            'intro_html' => '<p style="margin:0;color:#475569;line-height:1.7;">Voici votre code personnel pour participer au questionnaire.</p>',
            'details_html' => $codeHtml,
            'body_html' => '<p style="margin:0;color:#475569;line-height:1.7;">Vous pouvez aussi reprendre vos réponses à tout moment avec votre lien personnel.</p>',
            'button_label' => 'Evaluer l organisation',
            'button_url' => $directUrl !== '' ? $directUrl : (string)$publicAccessUrl,
        ]);
        $from = trim((string)($GLOBALS['mailUser'] ?? ''));
        if ($from === '') {
            $from = 'noreply@' . (preg_replace('/:\d+$/', '', \commonGetRootHost() ?: 'localhost'));
        }
        if (!\myHTMLMail([$from, $name], (string)$this->get('email'), 'Votre accès à l évaluation de ' . $name, $message)) {
            $this->clearPublicAccessCode();
            return false;
        }
        $this->set('last_sent_at', new \DateTimeImmutable());
        $this->save();
        return true;
    }

    private static function addActiveOrganizationUserEmail($organizationId, $userId, array &$emailMap)
    {
        if (!UserOrganization::hasActiveMembership((int)$userId, (int)$organizationId)) {
            return;
        }
        self::addUserEmail((int)$userId, $emailMap);
    }

    private static function addUserEmail($userId, array &$emailMap)
    {
        $user = new User();
        if (!$user->load((int)$userId)) {
            return;
        }
        $email = self::normalizeEmail($user->get('email'));
        if ($email !== '') {
            $emailMap[$email] = $email;
        }
    }

    private static function normalizeEmail($email) { $email = trim(mb_strtolower((string)$email, 'UTF-8')); return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : ''; }
}
?>
