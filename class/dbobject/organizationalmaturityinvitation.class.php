<?php
namespace dbObject;

class OrganizationalMaturityInvitation extends DbObject
{
    protected static function uiMessageSourceLang(): array
    {
        return parent::uiMessageSourceLang() + [
            'copy.generation_de_lien_impossible' => ['text' => 'Génération de lien impossible.', 'context' => 'Interface text in class/dbobject/organizationalmaturityinvitation.class.php'],
            'copy.partagez_votre_perception_de_name' => ['text' => 'Partagez votre perception de {name}', 'context' => 'Interface text in class/dbobject/organizationalmaturityinvitation.class.php'],
            'copy.votre_regard_aidera_cette_organisation_a_mieux_comprendre_sa_maturite_' => ['text' => 'Votre regard aidera cette organisation à mieux comprendre sa maturité et ses ambitions.', 'context' => 'Interface text in class/dbobject/organizationalmaturityinvitation.class.php'],
            'copy.le_questionnaire_prend_environ_dix_minutes_vous_pourrez_revenir_sur_vo' => ['text' => 'Le questionnaire prend environ dix minutes. Vous pourrez revenir sur vos réponses avec ce même lien.', 'context' => 'Interface text in class/dbobject/organizationalmaturityinvitation.class.php'],
            'copy.evaluer_l_organisation' => ['text' => 'Évaluer l’organisation', 'context' => 'Interface text in class/dbobject/organizationalmaturityinvitation.class.php'],
            'copy.votre_acces_a_l_evaluation_de_name' => ['text' => 'Votre accès à l’évaluation de {name}', 'context' => 'Interface text in class/dbobject/organizationalmaturityinvitation.class.php'],
            'invalid_message' => ['text' => 'Saisissez un texte d’invitation de 20 000 caractères maximum.', 'context' => 'Validation of the editable survey invitation body.'],
            'empty_selection' => ['text' => 'Choisissez au moins une personne ou une adresse e-mail.', 'context' => 'Validation when no survey invitation recipient is selected.'],
            'invitation_subject' => ['text' => 'Votre perception de {name}', 'context' => 'Subject of the survey invitation email and its outbox entry.'],
            'history_error' => ['text' => 'Impossible d’enregistrer les invitations dans la boîte d’envoi.', 'context' => 'Error before sending survey invitations when the outbox cannot be created.'],
        ];
    }

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
        return ['id' => 'ID', 'IDorganization' => 'Organisation', 'IDuser' => 'Utilisateur', 'email' => 'E-mail', 'token' => 'Lien invitation', 'public_access' => 'Accès public', 'access_code_hash' => 'Code accès', 'access_code_expires_at' => 'Code expire le', 'created_at' => 'Créé le', 'updated_at' => 'Modifié le', 'last_sent_at' => 'Envoyé le'];
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
    public static function getDefaultMessageHtml(): string
    {
        return '<p>' . htmlspecialchars(self::uiMessage('copy.votre_regard_aidera_cette_organisation_a_mieux_comprendre_sa_maturite_'), ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars(self::uiMessage('copy.le_questionnaire_prend_environ_dix_minutes_vous_pourrez_revenir_sur_vo'), ENT_QUOTES, 'UTF-8') . '</p>';
    }

    public static function issueForEmails($organizationId, array $emails, ?string $messageHtml = null)
    {
        $organizationId = (int)$organizationId;
        $organization = new Organization();
        if ($organizationId <= 0 || !$organization->load($organizationId)) return ['status' => false, 'message' => 'Organisation introuvable.'];
        if ($messageHtml !== null) {
            if (mb_strlen($messageHtml, 'UTF-8') > 20000) {
                return ['status' => false, 'message' => self::uiMessage('invalid_message')];
            }
            $messageHtml = ObjectMail::renderMessage($messageHtml, 'html');
            if (trim(str_replace("\u{00a0}", ' ', ObjectMail::messageText($messageHtml, 'html'))) === '') {
                return ['status' => false, 'message' => self::uiMessage('invalid_message')];
            }
        }
        $emails = array_values(array_unique(array_filter(array_map([self::class, 'normalizeEmail'], $emails))));
        if ($emails === []) {
            return ['status' => true, 'emails' => []];
        }
        $messageHtml ??= self::getDefaultMessageHtml();
        $recipients = array_map(static fn(string $email): array => ['email' => $email], $emails);
        try {
            $historyMail = ObjectMail::beginSurveyInvitationDelivery($organization, self::getEmailSubject($organization), $messageHtml, $recipients);
        } catch (\Throwable $error) {
            error_log('Survey invitation history creation failed: ' . get_class($error));
            return ['status' => false, 'message' => self::uiMessage('history_error')];
        }
        $issued = [];
        $historyWarning = false;
        foreach ($emails as $email) {
            $deliveryStatus = 'failed';
            try {
                $item = new self();
                if (!$item->load([['IDorganization', $organizationId], ['email', $email]])) {
                    $item->set('IDorganization', $organizationId);
                    $item->set('email', $email);
                    $item->set('token', bin2hex(random_bytes(16)));
                    $item->set('created_at', new \DateTimeImmutable());
                }
                $user = new User();
                if ($user->load(['email', $email])) $item->set('IDuser', (int)$user->getId());
                $item->set('updated_at', new \DateTimeImmutable());
                if (!empty($item->save()['status'])) {
                    $deliveryStatus = 'unknown';
                    $deliveryStatus = $item->sendEmail($messageHtml) ? 'sent' : 'failed';
                    if ($deliveryStatus === 'sent') {
                        $issued[] = $email;
                        $item->set('last_sent_at', new \DateTimeImmutable());
                        $item->save();
                    }
                }
            } catch (\Throwable $error) {
                error_log('Survey invitation delivery interrupted for mail ' . (int)$historyMail->getId() . ': ' . get_class($error));
            }
            try {
                $historyMail->recordRecipientDelivery($email, $deliveryStatus);
            } catch (\Throwable $error) {
                error_log('Survey invitation tracking failed for mail ' . (int)$historyMail->getId() . ': ' . get_class($error));
                $historyWarning = true;
            }
        }
        return [
            'status' => count($issued) === count($emails), 'emails' => $issued,
            'mail_id' => (int)$historyMail->getId(), 'historyWarning' => $historyWarning,
        ];
    }

    private static function getEmailSubject(Organization $organization): string
    {
        $name = trim((string)$organization->get('name')) ?: 'votre organisation';
        return mb_substr(self::uiMessage('invitation_subject', ['name' => $name]), 0, 250, 'UTF-8');
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

    public static function issueForSelections($organizationId, array $holonIds, array $userIds, array $emails, string $recipientMode = 'all', ?string $messageHtml = null)
    {
        if (!in_array($recipientMode, ['all', 'not_started', 'not_completed'], true)) {
            return ['status' => false, 'error' => 'invalid_recipient_mode'];
        }
        $selection = self::previewForSelections($organizationId, $holonIds, $userIds, $emails);
        if (empty($selection['status'])) {
            return $selection;
        }
        if ($selection['recipients']['all'] === []) {
            return ['status' => false, 'message' => self::uiMessage('empty_selection')];
        }
        return self::issueForEmails($organizationId, $selection['recipients'][$recipientMode], $messageHtml);
    }

    public static function previewForSelections($organizationId, array $holonIds, array $userIds, array $emails)
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
            if ($rootHolonId <= 0 || !$holon->load($holonId)
                || ($holonId !== $rootHolonId && (int)$holon->get('IDholon_org') !== $rootHolonId)) {
                continue;
            }
            if ($holonId === $rootHolonId && $holon->isOrganizationHolon()) {
                $memberships = new ArrayUserOrganization();
                $memberships->loadActiveForOrganization($organizationId, true);
                foreach ($memberships as $membership) {
                    self::addUserEmail((int)$membership->get('IDuser'), $emailMap);
                }
                continue;
            }
            foreach ($holon->getVisibleDescendantIds(true) as $descendantId) {
                $descendant = new Holon();
                if (!$descendant->load((int)$descendantId)) {
                    continue;
                }
                foreach ($descendant->getDirectActiveMemberUserIds($organizationId) as $userId) {
                    self::addActiveOrganizationUserEmail($organizationId, (int)$userId, $emailMap);
                }
            }
        }

        foreach ($emails as $email) {
            $email = self::normalizeEmail($email);
            if ($email !== '') {
                $emailMap[$email] = $email;
            }
        }

        $recipients = ['all' => array_values($emailMap), 'not_started' => $emailMap, 'not_completed' => $emailMap];
        if ($emailMap !== []) {
            // Match both personal responses and invitation drafts, including external guests.
            $rows = self::fetchAll(
                'SELECT a.completed_at, u.email AS user_email, i.email AS invitation_email,
                        iu.email AS invitation_user_email
                 FROM organizational_maturity_assessment a
                 LEFT JOIN `user` u ON u.id = a.IDuser
                 LEFT JOIN organizational_maturity_invitation i
                    ON i.id = a.IDinvitation AND i.IDorganization = a.IDorganization
                 LEFT JOIN `user` iu ON iu.id = i.IDuser
                 WHERE a.IDorganization = :organization_id',
                ['organization_id' => (int)$organizationId]
            );
            if (!is_array($rows)) {
                return ['status' => false, 'error' => 'participation_unavailable'];
            }
            foreach ($rows as $row) {
                foreach (['user_email', 'invitation_email', 'invitation_user_email'] as $field) {
                    $email = self::normalizeEmail($row[$field] ?? '');
                    unset($recipients['not_started'][$email]);
                    if (!empty($row['completed_at'])) {
                        unset($recipients['not_completed'][$email]);
                    }
                }
            }
        }
        $recipients = array_map('array_values', $recipients);
        return ['status' => true, 'recipients' => $recipients, 'counts' => array_map('count', $recipients)];
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
    public function sendEmail(?string $messageHtml = null)
    {
        require_once dirname(__DIR__, 2) . '/common/email_layout.php';
        $organization = $this->getOrganizationObject(); if (!$organization) return false;
        $name = trim((string)$organization->get('name')) ?: 'votre organisation';
        $url = appBuildAbsoluteUrl('/survey/?invitation=' . rawurlencode((string)$this->get('token')));
        $message = \commonRenderMailLayout([
            'brand_name' => $name,
            'brand_color' => (string)$organization->get('color'),
            'logo_url' => \commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
            'banner_url' => \commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
            'heading' => self::uiMessage('copy.partagez_votre_perception_de_name', ['name' => $name]),
            'body_html' => $messageHtml ?? self::getDefaultMessageHtml(),
            'button_label' => self::uiMessage('copy.evaluer_l_organisation'),
            'button_url' => $url,
        ]);
        $from = trim((string)($GLOBALS['mailUser'] ?? '')); if ($from === '') $from = 'noreply@' . (preg_replace('/:\d+$/', '', \commonGetRootHost() ?: 'localhost'));
        return \myHTMLMail([$from, $name], (string)$this->get('email'), self::getEmailSubject($organization), $message, organizationId: (int)$organization->getId());
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
            'button_label' => self::uiMessage('copy.evaluer_l_organisation'),
            'button_url' => $directUrl !== '' ? $directUrl : (string)$publicAccessUrl,
        ]);
        $from = trim((string)($GLOBALS['mailUser'] ?? ''));
        if ($from === '') {
            $from = 'noreply@' . (preg_replace('/:\d+$/', '', \commonGetRootHost() ?: 'localhost'));
        }
        if (!\myHTMLMail([$from, $name], (string)$this->get('email'), self::uiMessage('copy.votre_acces_a_l_evaluation_de_name', ['name' => $name]), $message, organizationId: (int)$organization->getId())) {
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
