<?php
namespace dbObject;

class Valeur extends DbObject
{
    public static function tableName() { return 'valeur'; }
    public static function rules()
    {
        return [
            [['title'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon'], 'fk'],
            [['title'], 'string'],
            [['principle'], 'html'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDorganization' => 'Organisation', 'IDholon' => 'Espace', 'title' => 'Valeur', 'principle' => 'Principe'];
    }
    public static function attributeLength() { return ['title' => 255]; }
    public static function getOrder() { return 'title'; }

    public function save()
    {
        $title = trim((string)$this->get('title'));
        $holon = new Holon();
        if ($title === '' || mb_strlen($title) > 255 || !$holon->load((int)$this->get('IDholon'))) {
            return ['status' => false, 'text' => 'La valeur doit avoir un titre et un espace valide.'];
        }
        $root = new Holon();
        $organizationId = (int)$holon->get('IDorganization');
        if (!$organizationId && $root->load((int)$holon->get('IDholon_org'))) $organizationId = (int)$root->get('IDorganization');
        if ($organizationId <= 0) return ['status' => false, 'text' => 'Organisation de la valeur introuvable.'];
        $this->set('IDorganization', $organizationId);
        $this->set('title', $title);
        $this->set('principle', PropertyFormat::sanitizeHtml($this->get('principle')));
        return parent::save();
    }

    public static function findForOrganization(int $organizationId): array
    {
        $items = [];
        foreach (self::fetchAll('SELECT * FROM `valeur` WHERE `IDorganization` = :org ORDER BY `title`, `id`', ['org' => $organizationId]) ?: [] as $row) {
            $item = new self();
            $item->hydrateFromDatabaseRow($row, true);
            $items[] = $item;
        }
        return $items;
    }

    public static function loadInOrganization($id, int $organizationId): ?self
    {
        $item = new self();
        return (int)$id > 0 && $item->load((int)$id) && (int)$item->get('IDorganization') === $organizationId ? $item : null;
    }

    // Snapshots keep proposals and history readable; the row remains the reference for rules and display.
    public static function syncProperty(HolonProperty $property, Property $definition): void
    {
        $raw = $property->get('value');
        if ($raw === null || trim((string)$raw) === '') return;
        $format = (int)$definition->get('IDpropertyformat');
        $parts = Property::listConversionParts($raw, $format);
        $holon = new Holon();
        if (!$holon->load((int)$property->get('IDholon'))) throw new \RuntimeException('Espace de la valeur introuvable.');
        $root = new Holon();
        $organizationId = (int)$holon->get('IDorganization');
        if (!$organizationId && $root->load((int)$holon->get('IDholon_org'))) $organizationId = (int)$root->get('IDorganization');
        $old = new HolonProperty();
        $ownedIds = [];
        if ($old->load([['IDholon', $holon->getId()], ['IDproperty', $definition->getId()]])) {
            $previous = json_decode((string)$old->get('value'), true);
            foreach (is_array($previous) ? ($previous['items'] ?? $previous) : [] as $item) {
                $ownedIds[is_array($item) ? (int)($item['id'] ?? 0) : (int)$item] = true;
            }
        }
        $items = [];
        foreach ($parts['items'] as $draft) {
            $id = is_array($draft) ? (int)($draft['id'] ?? 0) : (int)$draft;
            $value = $id > 0 ? self::loadInOrganization($id, $organizationId) : null;
            if ($id > 0 && !$value) throw new \RuntimeException('Valeur introuvable dans cette organisation.');
            $title = is_array($draft) ? trim((string)($draft['title'] ?? '')) : ($value ? (string)$value->get('title') : trim((string)$draft));
            $principle = is_array($draft) ? PropertyFormat::sanitizeHtml($draft['principle'] ?? '') : ($value ? (string)$value->get('principle') : '');
            if ($title === '' && PropertyFormat::isEmptyValue(PropertyFormat::FORMAT_HTML, $principle) && !$id) continue;
            $changed = !$value || $title !== (string)$value->get('title') || $principle !== (string)$value->get('principle');
            if ($changed) {
                // Editing an inherited reference creates a local value, preserving its source.
                if (!$value || (int)$value->get('IDholon') !== (int)$holon->getId() || !isset($ownedIds[$id])) $value = new self();
                $value->set('IDholon', $holon->getId());
                $value->set('title', $title);
                $value->set('principle', $principle);
                $result = $value->save();
                if (empty($result['status'])) throw new \RuntimeException($result['text'] ?? 'Enregistrement de la valeur impossible.');
            }
            $items[] = ['id' => (int)$value->getId(), 'title' => (string)$value->get('title'), 'principle' => (string)$value->get('principle')];
        }
        $parts['items'] = $items;
        $property->set('value', json_encode($format === PropertyFormat::FORMAT_HTML_LIST ? $parts : $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
