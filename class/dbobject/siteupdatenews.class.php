<?php
namespace dbObject;

class SiteUpdateNews extends DbObject
{
    public static function tableName() { return 'site_update_news'; }

    public static function rules()
    {
        return [
            [['revision', 'previous_revision', 'title', 'description'], 'required'],
            [['id'], 'integer'],
            [['revision', 'previous_revision', 'title'], 'string'],
            [['description', 'journal'], 'text'],
            [['created_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return ['revision' => 'Version', 'previous_revision' => 'Version precedente',
            'title' => 'Titre', 'description' => 'Presentation', 'journal' => 'Journal des nouveautes', 'created_at' => 'Publication'];
    }

    public static function attributeLength() { return ['revision' => 64, 'previous_revision' => 64, 'title' => 250]; }
    public static function getOrder() { return 'created_at DESC, id DESC'; }

    /** Publish with optional notifications atomically; finalization can safely be retried. */
    public static function publish(string $previousRevision, string $revision, string $title, string $description, string $journal = '', bool $notifyUsers = true): self
    {
        if (!preg_match('/^[a-f0-9]{40,64}$/', $revision) || !preg_match('/^[a-f0-9]{40,64}$/', $previousRevision)
            || $revision === $previousRevision || trim($description) === '') {
            throw new \InvalidArgumentException('Description ou versions de mise a jour invalides.');
        }
        $pdo = self::getPdo();
        if (!$pdo || $pdo->inTransaction() || ($notifyUsers && !Notification::isStorageAvailable())) {
            throw new \RuntimeException('Publication des nouveautes indisponible.');
        }
        $pdo->beginTransaction();
        try {
            $news = new self();
            if ($news->load([['revision', $revision]])) {
                $pdo->commit();
                return $news;
            }
            $news->set('revision', $revision);
            $news->set('previous_revision', $previousRevision);
            $news->set('title', $title);
            $news->set('description', $description);
            $news->set('journal', $journal !== '' ? $journal : null);
            $news->set('created_at', new \DateTimeImmutable());
            $saved = $news->save();
            if (empty($saved['status'])) {
                throw new \RuntimeException('Impossible de publier la news de mise a jour.');
            }
            foreach ($notifyUsers ? User::getSiteUpdateRecipientIds() : [] as $userId) {
                $notification = Notification::createForUser($userId, 0, 'site_update_installed',
                    'site-update:' . $revision, $title, '', '/omo/?site-update-news=' . $news->getId());
                if (!$notification) {
                    throw new \RuntimeException('Impossible de creer les notifications de mise a jour.');
                }
            }
            $pdo->commit();
            return $news;
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $error;
        }
    }
}
