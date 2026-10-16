<?php
namespace dbObject;

/** Committed PV transitions retain their audience until notification dispatch. */
class DocumentPvNotification extends DbObject
{
    public static function tableName() { return 'document_pv_notification'; }
    public static function rules() { return [
        [['IDdocument', 'stage', 'created_at'], 'required'], [['id'], 'integer'], [['IDdocument'], 'fk'],
        [['stage'], 'string'], [['parameters'], 'parameters'], [['created_at', 'processed_at'], 'datetime'], [['id'], 'safe'],
    ]; }
    public static function attributeLabels() { return ['IDdocument' => 'PV', 'stage' => 'Etape',
        'parameters' => 'Destinataires', 'created_at' => 'Creation', 'processed_at' => 'Notifications traitees']; }
    public static function attributeLength() { return ['stage' => 30]; }

    public function getPayload(): array
    {
        return json_decode((string)$this->get('parameters'), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function enqueue(Document $document, string $stage, \DateTimeImmutable $now, bool $automatic): void
    {
        $oid = (int)$document->get('IDorganization');
        $userIds = []; $guests = []; $knownEmails = [];
        foreach ($document->getPvStageNotificationAudience($stage) as $entry) {
            $uid = (int)($entry['userId'] ?? 0);
            $email = mb_strtolower(trim((string)($entry['email'] ?? '')), 'UTF-8');
            if ($uid <= 0 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // An invitation by email can still belong to a member with channel preferences.
                $uid = (int)self::fetchValue('SELECT u.id FROM user u
                    JOIN user_organization m ON m.IDuser = u.id AND m.IDorganization = :oid AND m.active = 1
                    WHERE u.active = 1 AND (u.email = :email OR m.email = :scoped_email) ORDER BY u.id LIMIT 1',
                    ['oid' => $oid, 'email' => $email, 'scoped_email' => $email]);
            }
            if ($uid > 0) {
                $userIds[$uid] = $uid;
                $user = new User();
                if ($user->load($uid)) {
                    foreach ([$email, $user->get('email'), $user->getScopedEmail($oid)] as $knownEmail) {
                        $knownEmails[mb_strtolower(trim((string)$knownEmail), 'UTF-8')] = true;
                    }
                }
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $guests[$email] = ['email' => $email];
            }
        }
        foreach ($guests as $email => &$guest) {
            if (isset($knownEmails[$email])) { unset($guests[$email]); continue; }
            $link = DocumentShareLink::getOrCreatePvParticipantLink($document, $document->getCreatedByUserId(), $email);
            if (!$link) throw new \RuntimeException('Unable to prepare PV guest notification link.');
            $guest['url'] = $link->buildPvParticipationUrl();
        }
        unset($guest);
        $deadline = $document->getPvReviewDeadline();
        $item = new self();
        $item->set('IDdocument', $document->getId());
        $item->set('stage', $stage);
        $item->set('created_at', $now);
        $item->set('parameters', ['organizationId' => $oid, 'title' => (string)$document->get('title'),
            'userIds' => array_values($userIds), 'guests' => array_values($guests), 'automatic' => $automatic,
            'deadline' => $deadline?->format('d.m.Y H:i'), 'sentEmails' => []]);
        $result = $item->save();
        if (empty($result['status'])) throw new \RuntimeException('Unable to queue PV stage notifications.');
    }

    public static function processPending(int $limit = 200, ?int $documentId = null): int
    {
        $pdo = self::getPdo();
        if ($pdo->inTransaction() || !self::tableExists(self::tableName()) || !Notification::isStorageAvailable()) return 0;
        require_once dirname(__DIR__, 2) . '/common/notification_center.php';
        $lock = 'pv-notifications-' . substr(hash('sha256', (string)self::fetchValue('SELECT DATABASE()')), 0, 32);
        if ((int)self::fetchValue('SELECT GET_LOCK(:name, 0)', ['name' => $lock]) !== 1) return 0;
        $processed = 0;
        try {
            $params = [];
            $filter = '';
            if ($documentId !== null) { $filter = ' AND IDdocument = :document'; $params['document'] = $documentId; }
            $rows = self::fetchAll('SELECT * FROM document_pv_notification WHERE processed_at IS NULL' . $filter
                . ' ORDER BY id LIMIT ' . max(1, min(1000, $limit)), $params);
            foreach ($rows ?: [] as $row) {
                try {
                    $item = new self(); $item->hydrateFromDatabaseRow($row, true);
                    $document = new Document();
                    if ($document->load((int)$item->get('IDdocument'), true) && !$document->isArchived()) {
                        if (!\notificationCenterDispatchPvStage($item, $document)) continue;
                    }
                    $item->set('processed_at', new \DateTimeImmutable());
                    if (empty($item->save()['status'])) throw new \RuntimeException('Unable to finish PV notifications.');
                    $processed++;
                } catch (\Throwable $exception) {
                    error_log('OMO PV notification ' . (int)$row['id'] . ' failed: ' . $exception->getMessage());
                }
            }
        } finally {
            self::fetchValue('SELECT RELEASE_LOCK(:name)', ['name' => $lock]);
        }
        return $processed;
    }
}
