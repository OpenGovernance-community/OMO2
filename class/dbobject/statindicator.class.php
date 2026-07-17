<?php
namespace dbObject;

class StatIndicator extends DbObject
{
    const REFERENCE_NONE = 'none';
    const REFERENCE_CEILING = 'ceiling';
    const REFERENCE_OBJECTIVE = 'objective';
    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_SEMIANNUAL = 'semiannual';
    const FREQUENCY_YEARLY = 'yearly';

    public static function tableName()
    {
        return 'stat_indicator';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'name', 'reference_type'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser'], 'fk'],
            [['name', 'source_url', 'reference_type', 'measurement_frequency', 'measurement_schedule'], 'string'],
            [['description'], 'text'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'IDholon' => 'Cercle ou rôle',
            'IDuser' => 'Créateur',
            'name' => 'Nom',
            'description' => 'Description',
            'source_url' => 'URL de la source',
            'reference_type' => 'Type de référence',
            'measurement_frequency' => 'Fréquence de mesure',
            'measurement_schedule' => 'Moment attendu',
            'active' => 'Actif',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDholon' => 'Contexte dans lequel cet indicateur est défini.',
            'source_url' => 'Lien vers la donnée, le rapport ou l outil à l origine de la mesure.',
            'reference_type' => 'Un plafond reste horizontal. Un objectif peut suivre une trajectoire composée de plusieurs points.',
            'measurement_frequency' => 'Cadence attendue pour la saisie des valeurs.',
            'measurement_schedule' => 'Heure, jour ou mois attendu selon la cadence. Cette information est facultative.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'name' => 190,
            'source_url' => 2000,
            'reference_type' => 20,
            'measurement_frequency' => 20,
            'measurement_schedule' => 20,
        ];
    }

    public static function getOrder()
    {
        return 'name ASC, id ASC';
    }

    public static function getReferenceTypeCatalog()
    {
        return [
            self::REFERENCE_NONE => 'Aucune référence',
            self::REFERENCE_CEILING => 'Plafond horizontal',
            self::REFERENCE_OBJECTIVE => 'Objectif ou trajectoire',
        ];
    }

    public static function normalizeReferenceType($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return array_key_exists($value, self::getReferenceTypeCatalog())
            ? $value
            : self::REFERENCE_NONE;
    }

    public static function getMeasurementFrequencyCatalog()
    {
        return [
            self::FREQUENCY_DAILY => self::FREQUENCY_DAILY,
            self::FREQUENCY_WEEKLY => self::FREQUENCY_WEEKLY,
            self::FREQUENCY_MONTHLY => self::FREQUENCY_MONTHLY,
            self::FREQUENCY_QUARTERLY => self::FREQUENCY_QUARTERLY,
            self::FREQUENCY_SEMIANNUAL => self::FREQUENCY_SEMIANNUAL,
            self::FREQUENCY_YEARLY => self::FREQUENCY_YEARLY,
        ];
    }

    public static function normalizeMeasurementFrequency($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return array_key_exists($value, self::getMeasurementFrequencyCatalog()) ? $value : null;
    }

    public static function normalizeMeasurementSchedule($frequency, $value)
    {
        $frequency = self::normalizeMeasurementFrequency($frequency);
        $value = trim((string)$value);
        if ($frequency === null || $value === '') {
            return null;
        }

        if ($frequency === self::FREQUENCY_DAILY) {
            return preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $value) ? $value : null;
        }

        $limits = [
            self::FREQUENCY_WEEKLY => [1, 7],
            self::FREQUENCY_MONTHLY => [1, 31],
            self::FREQUENCY_QUARTERLY => [1, 3],
            self::FREQUENCY_SEMIANNUAL => [1, 6],
            self::FREQUENCY_YEARLY => [1, 12],
        ];
        if (!isset($limits[$frequency]) || !ctype_digit($value)) {
            return null;
        }

        $numericValue = (int)$value;
        return $numericValue >= $limits[$frequency][0] && $numericValue <= $limits[$frequency][1]
            ? (string)$numericValue
            : null;
    }

    public static function sanitizeSourceUrl($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = @parse_url($value);
        $scheme = mb_strtolower((string)($parts['scheme'] ?? ''), 'UTF-8');
        return in_array($scheme, ['http', 'https'], true) ? $value : '';
    }

    public function save()
    {
        $this->set('reference_type', self::normalizeReferenceType($this->get('reference_type')));
        $measurementFrequency = self::normalizeMeasurementFrequency($this->get('measurement_frequency'));
        $this->set('measurement_frequency', $measurementFrequency);
        $this->set('measurement_schedule', self::normalizeMeasurementSchedule($measurementFrequency, $this->get('measurement_schedule')));

        $sourceUrl = trim((string)$this->get('source_url'));
        if ($sourceUrl !== '') {
            $this->set('source_url', self::sanitizeSourceUrl($sourceUrl));
        }

        $now = new \DateTime();
        if ((int)$this->getId() <= 0 && !($this->get('created_at') instanceof \DateTimeInterface)) {
            $this->set('created_at', $now);
        }
        $this->set('updated_at', $now);

        return parent::save();
    }

    public function getOrganization()
    {
        $organization = new \dbObject\Organization();
        return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }

    public function getHolon()
    {
        $holonId = (int)$this->get('IDholon');
        if ($holonId <= 0) {
            return null;
        }

        $holon = new \dbObject\Holon();
        return $holon->load($holonId) ? $holon : null;
    }

    public function getMeasurements()
    {
        $values = new \dbObject\ArrayStatIndicatorValue();
        $values->loadForIndicator((int)$this->getId());
        return $values;
    }

    public function getReferencePoints()
    {
        $points = new \dbObject\ArrayStatIndicatorReferencePoint();
        $points->loadForIndicator((int)$this->getId());
        return $points;
    }

    public function canView()
    {
        return $this->canViewDetail();
    }

    public function canViewDetail()
    {
        $organization = $this->getOrganization();
        if (!($organization instanceof \dbObject\Organization) || !$organization->canViewDetail()) {
            return false;
        }

        $holon = $this->getHolon();
        if (!$holon) {
            return true;
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        return $rootHolon instanceof \dbObject\Holon
            && $holon->isDescendantOf((int)$rootHolon->getId(), true)
            && $holon->canViewDetail();
    }

    public function canEdit()
    {
        $currentUserId = function_exists('commonGetCurrentUserId')
            ? (int)\commonGetCurrentUserId()
            : (int)($_SESSION['currentUser'] ?? 0);
        if ($currentUserId <= 0) {
            return false;
        }

        if ((int)$this->get('IDuser') === $currentUserId) {
            return true;
        }

        $holon = $this->getHolon();
        if ($holon instanceof \dbObject\Holon) {
            return $holon->canEdit();
        }

        $organization = $this->getOrganization();
        return $organization instanceof \dbObject\Organization && $organization->canEdit();
    }
}

?>
