<?php
namespace dbObject;

/** Outbox with immutable recipient snapshots and conservative retry semantics. */
final class ObjectMail extends DbObject
{
    public const MAX_RECIPIENTS = 500;
    public const DIRECT_RECIPIENT_LIMIT = 5;
    public static function tableName() { return 'object_mail'; }
    public static function rules() { return [
        [['IDuser', 'IDorganization', 'object_type', 'object_id', 'request_hash', 'payload_hash', 'subject', 'message', 'created_at'], 'required'],
        [['id', 'object_id', 'recipient_count', 'created_at'], 'integer'],
        [['IDuser', 'IDorganization', 'IDmcp_oauth_grant'], 'fk'],
        [['object_type', 'request_hash', 'payload_hash', 'subject'], 'string'], [['message'], 'text'], [['id'], 'safe']]; }
    public static function attributeLabels() { return ['IDuser' => 'Expediteur', 'IDorganization' => 'Organisation',
        'IDmcp_oauth_grant' => 'Autorisation assistant', 'object_type' => 'Type', 'object_id' => 'Objet',
        'subject' => 'Objet du message', 'message' => 'Message', 'recipient_count' => 'Destinataires', 'created_at' => 'Creation',
        'request_hash' => 'Identifiant de demande', 'payload_hash' => 'Empreinte du contenu']; }
    public static function attributeLength() { return ['object_type' => 20, 'request_hash' => 64, 'payload_hash' => 64, 'subject' => 250, 'message' => 20000]; }
    public function canView() { $uid = (int)\commonGetCurrentUserId(); $user = new User(); return $uid > 0
        && (int)$this->get('IDuser') === $uid && $user->load($uid, true) && $user->get('active')
        && UserOrganization::hasActiveMembership($uid, (int)$this->get('IDorganization')); }
    public function canViewDetail() { return $this->canView(); }
    public function canEdit() { return false; }

    public static function validate(array $args): void
    {
        require_once dirname(__DIR__, 2) . '/common/object_mail/validation.php';
        \omoObjectMailValidate($args);
    }

    public static function enqueue(int $oid, array $args, ?array $grant = null): array
    {
        self::validate($args);
        $uid = (int)\commonGetCurrentUserId();
        if ($grant !== null && ((int)$grant['IDuser'] !== $uid || (int)$grant['IDorganization'] !== $oid
            || !McpOauthGrant::hasActiveScopeAuthorization($grant, \OMO_MCP_MAIL_SCOPE))) throw new \DomainException('Autorisation mail:send absente, expiree ou revoquee.');
        $audience = ObjectAudience::resolve($oid, $args['object_type'], $args['object_id'], $args['user_ids'] ?? null);
        if (!$audience['can_send']) throw new \DomainException('Vous devez participer a cet objet ou avoir le droit de le gerer pour envoyer un message.');
        $bindings = ['uid' => $uid, 'oid' => $oid, 'request' => hash('sha256', $args['request_key'])];
        $payload = [$args['object_type'], $args['object_id'], $args['subject'], $args['message'], $args['audience_token']];
        if (isset($args['user_ids'])) { $selected = $args['user_ids']; sort($selected, SORT_NUMERIC); $payload[] = $selected; }
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $pdo = self::getPdo(); $pdo->beginTransaction();
        try {
            // Serialize quotas and idempotency across native and MCP requests, including separate clients.
            self::fetchRow('SELECT id FROM `user` WHERE id = :id FOR UPDATE', ['id' => $uid]);
            self::fetchRow('SELECT id FROM organization WHERE id = :id FOR UPDATE', ['id' => $oid]);
            $existing = self::fetchRow('SELECT id, payload_hash FROM object_mail WHERE IDuser = :uid AND IDorganization = :oid AND request_hash = :request', $bindings);
            if ($existing) {
                if (!hash_equals($existing['payload_hash'], $hash)) throw new \DomainException('Cette request_key a deja servi pour un autre message.');
                $pdo->commit(); return self::status($oid, (int)$existing['id']) + ['replayed' => true];
            }
            if (!hash_equals($audience['audience_token'], $args['audience_token'])) throw new \DomainException('Les destinataires ont change. Relisez la liste avant de confirmer l envoi.');
            $count = count($audience['recipients']);
            if ($count < 1 || $count > self::MAX_RECIPIENTS) throw new \DomainException('L audience doit contenir entre 1 et 500 destinataires valides.');
            $userQuota = self::fetchRow('SELECT COALESCE(SUM(recipient_count), 0) AS recipients,
                COALESCE(SUM(created_at > :hour), 0) AS requests FROM object_mail WHERE IDuser = :uid AND created_at > :day',
                ['hour' => time() - 3600, 'uid' => $uid, 'day' => time() - 86400]);
            $orgQuota = (int)self::fetchValue('SELECT COALESCE(SUM(recipient_count), 0) FROM object_mail WHERE IDorganization = :oid AND created_at > :day', ['oid' => $oid, 'day' => time() - 86400]);
            if ((int)$userQuota['requests'] >= 20 || (int)$userQuota['recipients'] + $count > 1000 || $orgQuota + $count > 5000) {
                throw new \DomainException('Limite d envoi atteinte : 20 messages par heure et 1000 destinataires par jour par compte, 5000 par organisation.');
            }
            $mail = new self();
            foreach (['IDuser' => $uid, 'IDorganization' => $oid, 'IDmcp_oauth_grant' => $grant['id'] ?? null,
                'object_type' => $args['object_type'], 'object_id' => $args['object_id'], 'request_hash' => $bindings['request'],
                'payload_hash' => $hash, 'subject' => $args['subject'], 'message' => $args['message'], 'recipient_count' => $count, 'created_at' => time()] as $field => $value) $mail->set($field, $value);
            if (empty($mail->save()['status'])) throw new \RuntimeException('Impossible de mettre le message en attente.');
            foreach ($audience['recipients'] as $recipient) {
                $delivery = new ObjectMailRecipient();
                foreach (['IDobject_mail' => $mail->getId(), 'member_id' => $recipient['member_id'], 'email' => $recipient['email'], 'status' => 'queued', 'updated_at' => time()] as $field => $value) $delivery->set($field, $value);
                if (empty($delivery->save()['status'])) throw new \RuntimeException('Impossible de conserver les destinataires.');
            }
            $pdo->commit();
            return self::status($oid, (int)$mail->getId()) + ['replayed' => false];
        } catch (\Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
    }

    public static function send(int $oid, array $args, ?array $grant = null): array
    {
        $result = self::enqueue($oid, $args, $grant);
        if ($result['replayed']) return $result;
        $direct = $result['recipient_count'] <= self::DIRECT_RECIPIENT_LIMIT;
        if ($direct) self::processBatch(self::DIRECT_RECIPIENT_LIMIT, $result['mail_id']);
        $result = self::status($oid, $result['mail_id']) + ['replayed' => false, 'delivery_mode' => $direct ? 'direct' : 'queued'];
        if ($result['delivery']['queued'] > 0 && PHP_SAPI !== 'cli') {
            require_once dirname(__DIR__, 2) . '/common/object_mail/dispatch.php';
            \omoObjectMailDispatch($result['mail_id']);
        }
        return $result;
    }

    public static function status(int $oid, int $id): array
    {
        $uid = (int)\commonGetCurrentUserId(); $user = new User();
        if ($uid <= 0 || !$user->load($uid, true) || !$user->get('active') || !UserOrganization::hasActiveMembership($uid, $oid)) throw new \DomainException('Connexion requise.');
        $mail = self::fetchRow('SELECT id, object_type, object_id, recipient_count FROM object_mail WHERE id = :id AND IDuser = :uid AND IDorganization = :oid', ['id' => $id, 'uid' => $uid, 'oid' => $oid]);
        if (!$mail) throw new \DomainException('Message indisponible.');
        $counts = ['queued' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'unknown' => 0];
        foreach (self::fetchAll('SELECT status, COUNT(*) AS total FROM object_mail_recipient WHERE IDobject_mail = :id GROUP BY status', ['id' => $id]) as $row) $counts[$row['status']] = (int)$row['total'];
        return ['mail_id' => $id, 'object_type' => $mail['object_type'], 'object_id' => (int)$mail['object_id'],
            'recipient_count' => (int)$mail['recipient_count'], 'delivery' => $counts,
            'complete' => $counts['queued'] + $counts['sending'] === 0,
            'instructions' => 'queued attend le traitement OMO ; sent indique l acceptation SMTP, pas la reception finale. failed et unknown ne sont jamais relances automatiquement pour eviter les doublons. skipped indique une invitation ou une autorisation devenue invalide. Reutilisez la meme request_key pour toute nouvelle tentative du meme envoi.'];
    }

    /** Existing OMO maintenance runs this queue. Never retry an ambiguous SMTP delivery. */
    public static function processBatch(int $limit = 20, ?int $mailId = null): int
    {
        $pdo = self::getPdo();
        // Long-running processes must not reuse cached permissions between jobs.
        $beforeSession = $_SESSION ?? [];
        $processed = 0; $deadline = microtime(true) + 20;
        self::execute("UPDATE object_mail_recipient SET status = 'unknown', updated_at = :now WHERE status = 'sending' AND updated_at < :cutoff"
            . ($mailId !== null ? ' AND IDobject_mail = :mail_id' : ''), ['now' => time(), 'cutoff' => time() - 600] + ($mailId !== null ? ['mail_id' => $mailId] : []));
        try {
            while ($processed < $limit && microtime(true) < $deadline) {
                $pdo->beginTransaction();
                try {
                    $row = self::fetchRow("SELECT r.*, m.IDuser, m.IDorganization, m.IDmcp_oauth_grant, m.object_type, m.object_id,
                        m.subject, m.message FROM object_mail_recipient r INNER JOIN object_mail m ON m.id = r.IDobject_mail
                        WHERE r.status = 'queued'" . ($mailId !== null ? ' AND m.id = :mail_id' : '')
                        . ' ORDER BY r.id ASC LIMIT 1 FOR UPDATE SKIP LOCKED', $mailId !== null ? ['mail_id' => $mailId] : []);
                    if (!$row) { $pdo->commit(); break; }
                    if (!self::execute("UPDATE object_mail_recipient SET status = 'sending', updated_at = :now WHERE id = :id", ['now' => time(), 'id' => $row['id']])) throw new \RuntimeException('Cannot claim mail recipient.');
                    $pdo->commit();
                } catch (\Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
                $_SESSION = ['currentUser' => (int)$row['IDuser'], 'currentOrganization' => (int)$row['IDorganization']];
                DbObject::$preload = [];
                $status = 'skipped';
                try {
                    if ($row['IDmcp_oauth_grant']) {
                        require_once dirname(__DIR__, 2) . '/common/mcp/protocol.php';
                        $grant = McpOauthGrant::fetchRow('SELECT * FROM mcp_oauth_grant WHERE id = :id', ['id' => $row['IDmcp_oauth_grant']]);
                        if (!$grant || (int)$grant['IDuser'] !== (int)$row['IDuser'] || (int)$grant['IDorganization'] !== (int)$row['IDorganization']
                            || !McpOauthGrant::hasActiveScopeAuthorization($grant, \OMO_MCP_MAIL_SCOPE)) throw new \DomainException('Authorization withdrawn.');
                    }
                    $selected = $row['object_type'] === 'organization' ? [(int)substr($row['member_id'], 5)] : null;
                    $audience = ObjectAudience::resolve((int)$row['IDorganization'], $row['object_type'], (int)$row['object_id'], $selected);
                    $eligible = array_filter($audience['recipients'], static fn ($member) => $member['email'] === $row['email']);
                    if ($audience['can_send'] && $eligible) {
                        $status = 'unknown'; // SMTP exceptions may occur after accepting DATA.
                        require_once dirname(__DIR__, 2) . '/common/object_mail/delivery.php';
                        $status = \omoObjectMailDeliver($audience, $row) ? 'sent' : 'failed';
                    }
                } catch (\DomainException $error) { /* A revoked invitation/permission is skipped, without exposing private diagnostics. */ }
                catch (\Throwable $error) { error_log('OMO object mail delivery failed for recipient ' . (int)$row['id']); }
                if (!self::execute('UPDATE object_mail_recipient SET status = :status, updated_at = :now WHERE id = :id', ['status' => $status, 'now' => time(), 'id' => $row['id']])) throw new \RuntimeException('Cannot record mail delivery.');
                $processed++;
            }
            if ($mailId === null) {
                // Global retention belongs to maintenance; targeted workers/tests touch only their job.
                self::execute("UPDATE object_mail SET subject = '', message = '' WHERE created_at < :cutoff AND message <> '' AND NOT EXISTS
                    (SELECT 1 FROM object_mail_recipient r WHERE r.IDobject_mail = object_mail.id AND r.status IN ('queued', 'sending'))", ['cutoff' => time() - 2592000]);
                self::execute("UPDATE object_mail_recipient r INNER JOIN object_mail m ON m.id = r.IDobject_mail SET r.email = CONCAT('expired-', r.id), r.member_id = '' WHERE m.created_at < :cutoff AND r.status NOT IN ('queued', 'sending') AND r.member_id <> ''", ['cutoff' => time() - 2592000]);
            }
        } finally { $_SESSION = $beforeSession; DbObject::$preload = []; }
        return $processed;
    }
}
