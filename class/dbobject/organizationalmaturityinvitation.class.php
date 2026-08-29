<?php
namespace dbObject;

class OrganizationalMaturityInvitation extends DbObject
{
    public static function tableName() { return 'organizational_maturity_invitation'; }
    public static function rules()
    {
        return [
            [['id'], 'integer'],
            [['IDorganization', 'IDuser'], 'fk'],
            [['email', 'token'], 'string'],
            [['created_at', 'updated_at', 'last_sent_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDorganization' => 'Organisation', 'IDuser' => 'Utilisateur', 'email' => 'E-mail', 'token' => 'Lien invitation', 'created_at' => 'Cree le', 'updated_at' => 'Modifie le', 'last_sent_at' => 'Envoye le'];
    }
    public static function findByToken($token)
    {
        $token = trim((string)$token);
        if (!preg_match('/^[a-f0-9]{32}$/i', $token)) return null;
        $row = self::fetchRow('SELECT * FROM organizational_maturity_invitation WHERE token = :token LIMIT 1', ['token' => $token]);
        if ($row === false) return null;
        $item = new self(); $item->loadFromArray($row); $item->setId((int)$row['id']); return $item;
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
