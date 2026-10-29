<?php
namespace dbObject;

class Tip extends DbObject
{
    public static function tableName() { return 'tip'; }
    public static function rules()
    {
        return [
            [['text', 'link'], 'required'],
            [['text'], 'text'],
            [['link'], 'string'],
            [['image'], 'sizedimage'],
            [['active'], 'boolean'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'text' => 'Texte court', 'link' => 'Lien', 'image' => 'Image facultative', 'active' => 'Publier cette astuce'];
    }
    public static function attributeLength() { return ['text' => 500, 'link' => 1000, 'image' => [[480, 270], [240, 135]]]; }
    public static function attributeDescriptions()
    {
        return ['link' => 'Lien interne commencant par / ou #, ou adresse externe https://...',
            'active' => 'Les astuces publiées sont proposées a tous les membres du logiciel.'];
    }
    public static function getOrder() { return 'id DESC'; }
    public static function isAvailable(): bool { return self::tableExists('tip'); }

    public static function isSafeLink(string $link): bool
    {
        if ($link === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $link)) return false;
        if (str_starts_with($link, '#')) return true;
        if (str_starts_with($link, '/') && !str_starts_with($link, '//')) return true;
        return preg_match('~^https?://~i', $link) === 1 && filter_var($link, FILTER_VALIDATE_URL) !== false;
    }

    public function save()
    {
        $text = trim((string)$this->get('text'));
        $link = trim((string)$this->get('link'));
        if ($text === '' || mb_strlen($text, 'UTF-8') > 500 || strlen($link) > 1000 || !self::isSafeLink($link)) {
            return ['status' => false, 'text' => 'Invalid tip content.'];
        }
        $this->set('text', $text);
        $this->set('link', $link);
        return parent::save();
    }

    /** Restore the day's active tip, or draw another one without immediate repetition. */
    public static function pick(int $currentId = 0, bool $next = false): ?self
    {
        if (!self::isAvailable()) return null;
        if (!$next && $currentId > 0) {
            $tip = new self();
            if ($tip->load($currentId) && $tip->get('active') && self::isSafeLink((string)$tip->get('link'))) return $tip;
        }
        $rows = self::fetchAll('SELECT id, link FROM tip WHERE active = 1 AND CHAR_LENGTH(TRIM(text)) > 0');
        $ids = [];
        foreach ($rows ?: [] as $row) {
            if (self::isSafeLink((string)$row['link'])) $ids[] = (int)$row['id'];
        }
        if (count($ids) > 1) $ids = array_values(array_diff($ids, [$currentId]));
        if (!$ids) return null;
        $tip = new self();
        return $tip->load($ids[random_int(0, count($ids) - 1)]) ? $tip : null;
    }

    public function toPayload(): array
    {
        $image = (string)$this->get('image');
        if (!str_starts_with($image, '/img/') || !self::isSafeLink($image)) $image = '';
        return ['id' => (int)$this->getId(), 'text' => (string)$this->get('text'),
            'link' => (string)$this->get('link'), 'image' => $image];
    }
}
