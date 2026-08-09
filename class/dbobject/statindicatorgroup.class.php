<?php
namespace dbObject;

class StatIndicatorGroup extends DbObject
{
    const DISPLAY_OVERLAY = 'overlay';
    const DISPLAY_SUM = 'sum';

    public static function tableName()
    {
        return 'stat_indicator_group';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'name', 'display_mode', 'reference_type'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser'], 'fk'],
            [['name', 'display_mode', 'reference_type'], 'string'],
            [['chart_min_value'], 'float'],
            [['hide_same_holon_sources', 'active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Contexte',
            'IDuser' => 'Createur',
            'name' => 'Nom',
            'display_mode' => 'Affichage',
            'reference_type' => 'Type de reference',
            'chart_min_value' => 'Valeur basse du graphique',
            'hide_same_holon_sources' => 'Masquer les indicateurs du meme holon',
            'active' => 'Actif',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function normalizeDisplayMode($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return in_array($value, [self::DISPLAY_OVERLAY, self::DISPLAY_SUM], true)
            ? $value
            : self::DISPLAY_OVERLAY;
    }

    public function save()
    {
        $this->set('display_mode', self::normalizeDisplayMode($this->get('display_mode')));
        $this->set('reference_type', StatIndicator::normalizeReferenceType($this->get('reference_type')));
        $this->set('hide_same_holon_sources', (int)$this->get('hide_same_holon_sources') > 0 ? 1 : 0);
        return parent::save();
    }

    public function getItems()
    {
        $items = new ArrayStatIndicatorGroupItem();
        $items->loadForGroup((int)$this->getId());
        return $items;
    }

    public function getReferencePoints()
    {
        $points = new ArrayStatIndicatorReferencePoint();
        $points->loadForGroup((int)$this->getId());
        return $points;
    }

    public function getOrganization()
    {
        $organization = new Organization();
        return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }

    public function getHolon()
    {
        $holonId = (int)$this->get('IDholon');
        if ($holonId <= 0) {
            return null;
        }

        $holon = new Holon();
        return $holon->load($holonId) ? $holon : null;
    }

    public function canView()
    {
        $organization = new Organization();
        if (!$organization->load((int)$this->get('IDorganization')) || !$organization->canViewDetail()) {
            return false;
        }

        $holonId = (int)$this->get('IDholon');
        if ($holonId <= 0) {
            return true;
        }

        $holon = new Holon();
        return $holon->load($holonId) && $holon->canViewDetail();
    }

    public function canEdit()
    {
        $currentUserId = function_exists('commonGetCurrentUserId')
            ? (int)commonGetCurrentUserId()
            : (int)($_SESSION['currentUser'] ?? 0);
        if ($currentUserId <= 0) {
            return false;
        }

        if ((int)$this->get('IDuser') === $currentUserId) {
            return true;
        }

        $holon = $this->getHolon();
        if ($holon instanceof Holon) {
            return $holon->canEdit();
        }

        $organization = $this->getOrganization();
        return $organization instanceof Organization && $organization->canEdit();
    }
}
?>
