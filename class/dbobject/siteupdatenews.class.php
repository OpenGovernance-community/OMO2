<?php
namespace dbObject;

class SiteUpdateNews extends DbObject
{
    protected static function uiMessageSourceLang(): array
    {
        return parent::uiMessageSourceLang() + [
            'copy.description_ou_versions_de_mise_a_jour_invalides' => ['text' => 'Description ou versions de mise à jour invalides.', 'context' => 'Interface text in class/dbobject/siteupdatenews.class.php'],
            'copy.publication_des_nouveautes_indisponible' => ['text' => 'Publication des nouveautés indisponible.', 'context' => 'Interface text in class/dbobject/siteupdatenews.class.php'],
            'copy.impossible_de_publier_la_news_de_mise_a_jour' => ['text' => 'Impossible de publier la news de mise à jour.', 'context' => 'Interface text in class/dbobject/siteupdatenews.class.php'],
            'copy.impossible_de_creer_les_notifications_de_mise_a_jour' => ['text' => 'Impossible de créer les notifications de mise à jour.', 'context' => 'Interface text in class/dbobject/siteupdatenews.class.php'],
        ];
    }

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
        return ['revision' => 'Version', 'previous_revision' => 'Version précédente',
            'title' => 'Titre', 'description' => 'Présentation', 'journal' => 'Journal des nouveautés', 'created_at' => 'Publication'];
    }

    public static function attributeLength() { return ['revision' => 64, 'previous_revision' => 64, 'title' => 250]; }
    public static function getOrder() { return 'created_at DESC, id DESC'; }

    /** Publish with optional notifications atomically; finalization can safely be retried. */
    public static function publish(string $previousRevision, string $revision, string $title, string $description, string $journal = '', bool $notifyUsers = true): self
    {
        if (!preg_match('/^[a-f0-9]{40,64}$/', $revision) || !preg_match('/^[a-f0-9]{40,64}$/', $previousRevision)
            || $revision === $previousRevision || trim($description) === '') {
            throw new \InvalidArgumentException(self::uiMessage('copy.description_ou_versions_de_mise_a_jour_invalides'));
        }
        $pdo = self::getPdo();
        if (!$pdo || $pdo->inTransaction() || ($notifyUsers && !Notification::isStorageAvailable())) {
            throw new \RuntimeException(self::uiMessage('copy.publication_des_nouveautes_indisponible'));
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
                throw new \RuntimeException(self::uiMessage('copy.impossible_de_publier_la_news_de_mise_a_jour'));
            }
            foreach ($notifyUsers ? User::getSiteUpdateRecipientIds() : [] as $userId) {
                $notification = Notification::createForUser($userId, 0, 'site_update_installed',
                    'site-update:' . $revision, $title, '', '/omo/?site-update-news=' . $news->getId());
                if (!$notification) {
                    throw new \RuntimeException(self::uiMessage('copy.impossible_de_creer_les_notifications_de_mise_a_jour'));
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
