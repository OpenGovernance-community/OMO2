<?php
namespace dbObject;

class StatIndicator extends DbObject
{
    const REFERENCE_NONE = 'none';
    const REFERENCE_CEILING = 'ceiling';
    const REFERENCE_OBJECTIVE = 'objective';
    const FREQUENCY_DAILY = RecurrenceSchedule::FREQUENCY_DAILY;
    const FREQUENCY_WEEKLY = RecurrenceSchedule::FREQUENCY_WEEKLY;
    const FREQUENCY_MONTHLY = RecurrenceSchedule::FREQUENCY_MONTHLY;
    const FREQUENCY_QUARTERLY = RecurrenceSchedule::FREQUENCY_QUARTERLY;
    const FREQUENCY_SEMIANNUAL = RecurrenceSchedule::FREQUENCY_SEMIANNUAL;
    const FREQUENCY_YEARLY = RecurrenceSchedule::FREQUENCY_YEARLY;
    const SOURCE_MANUAL = 'manual';
    const SOURCE_ETHERCALC_CELL = 'ethercalc_cell';
    const SOURCE_ETHERCALC_TABLE = 'ethercalc_table';
    const SOURCE_SPREADSHEET_CELL = 'spreadsheet_cell';
    const SOURCE_SPREADSHEET_TABLE = 'spreadsheet_table';
    const ETHERCALC_FREQUENCY_HOURLY = 'hourly';
    const ETHERCALC_FREQUENCY_DAILY = 'daily';
    const ETHERCALC_FREQUENCY_WEEKLY = 'weekly';
    const SPREADSHEET_FREQUENCY_HOURLY = 'hourly';
    const SPREADSHEET_FREQUENCY_DAILY = 'daily';
    const SPREADSHEET_FREQUENCY_WEEKLY = 'weekly';

    public static function tableName()
    {
        return 'stat_indicator';
    }

    public static function rules()
    {
        return [
            [['IDorganization', 'name', 'reference_type'], 'required'],
            [['id'], 'integer'],
            [['IDorganization', 'IDholon', 'IDuser', 'IDdocument'], 'fk'],
            [['name', 'source_url', 'reference_type', 'measurement_frequency', 'measurement_schedule', 'source_type', 'ethercalc_cell', 'ethercalc_frequency', 'ethercalc_range', 'ethercalc_date_column', 'ethercalc_value_column', 'spreadsheet_sheet', 'spreadsheet_cell', 'spreadsheet_frequency', 'spreadsheet_range', 'spreadsheet_date_column', 'spreadsheet_value_column'], 'string'],
            [['description'], 'text'],
            [['chart_min_value'], 'float'],
            [['show_cumulative', 'active'], 'boolean'],
            [['created_at', 'updated_at', 'ethercalc_last_sync_at', 'spreadsheet_last_sync_at'], 'datetime'],
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
            'source_type' => 'Type de source',
            'IDdocument' => 'Document tableur',
            'ethercalc_cell' => 'Cellule EtherCalc',
            'ethercalc_frequency' => 'Frequence de synchronisation EtherCalc',
            'ethercalc_range' => 'Plage EtherCalc',
            'ethercalc_date_column' => 'Colonne de date EtherCalc',
            'ethercalc_value_column' => 'Colonne de valeur EtherCalc',
            'ethercalc_last_sync_at' => 'Derniere synchronisation EtherCalc',
            'spreadsheet_sheet' => 'Feuille du tableur',
            'spreadsheet_cell' => 'Cellule du tableur',
            'spreadsheet_frequency' => 'Frequence de synchronisation du tableur',
            'spreadsheet_range' => 'Plage du tableur',
            'spreadsheet_date_column' => 'Colonne de date du tableur',
            'spreadsheet_value_column' => 'Colonne de valeur du tableur',
            'spreadsheet_last_sync_at' => 'Derniere synchronisation du tableur',
            'chart_min_value' => 'Valeur basse du graphique',
            'show_cumulative' => 'Afficher le cumul',
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
            'chart_min_value' => 'Borne facultative incluse dans l échelle verticale du graphique.',
            'show_cumulative' => 'Affiche les mesures en barres et leur cumul sur une seconde échelle.',
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
            'source_type' => 30,
            'ethercalc_cell' => 20,
            'ethercalc_frequency' => 20,
            'ethercalc_range' => 40,
            'ethercalc_date_column' => 10,
            'ethercalc_value_column' => 10,
            'spreadsheet_sheet' => 190,
            'spreadsheet_cell' => 20,
            'spreadsheet_frequency' => 20,
            'spreadsheet_range' => 40,
            'spreadsheet_date_column' => 10,
            'spreadsheet_value_column' => 10,
        ];
    }

    public static function getOrder()
    {
        return 'name ASC, id ASC';
    }

    public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
    {
        return self::execute("UPDATE stat_indicator SET IDuser = CASE WHEN active = 1 THEN NULL ELSE :ghost_user_id END WHERE IDorganization = :organization_id AND IDuser = :user_id", array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId));
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
        return RecurrenceSchedule::getFrequencyCatalog();
    }

    public static function normalizeMeasurementFrequency($value)
    {
        return RecurrenceSchedule::normalizeFrequency($value);
    }

    public static function normalizeMeasurementSchedule($frequency, $value)
    {
        return RecurrenceSchedule::normalizeSchedule($frequency, $value);
    }

    public static function getSourceTypeCatalog()
    {
        return [
            self::SOURCE_MANUAL => 'Manuelle',
            self::SOURCE_ETHERCALC_CELL => 'Cellule EtherCalc',
            self::SOURCE_ETHERCALC_TABLE => 'Tableau EtherCalc',
            self::SOURCE_SPREADSHEET_CELL => 'Cellule tableur',
            self::SOURCE_SPREADSHEET_TABLE => 'Tableau tableur',
        ];
    }

    public static function normalizeSourceType($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return array_key_exists($value, self::getSourceTypeCatalog()) ? $value : self::SOURCE_MANUAL;
    }

    public static function getEthercalcFrequencyCatalog()
    {
        return [
            self::ETHERCALC_FREQUENCY_HOURLY => 'Toutes les heures',
            self::ETHERCALC_FREQUENCY_DAILY => 'Chaque jour',
            self::ETHERCALC_FREQUENCY_WEEKLY => 'Chaque semaine',
        ];
    }

    public static function normalizeEthercalcFrequency($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return array_key_exists($value, self::getEthercalcFrequencyCatalog())
            ? $value
            : self::ETHERCALC_FREQUENCY_DAILY;
    }

    public static function getSpreadsheetFrequencyCatalog()
    {
        return self::getEthercalcFrequencyCatalog();
    }

    public static function normalizeSpreadsheetFrequency($value)
    {
        $value = trim(mb_strtolower((string)$value, 'UTF-8'));
        return array_key_exists($value, self::getSpreadsheetFrequencyCatalog())
            ? $value
            : self::SPREADSHEET_FREQUENCY_DAILY;
    }

    public static function normalizeSpreadsheetSheet($value)
    {
        $value = trim((string)$value);
        return preg_replace('/[\x00-\x1F\x7F]/', '', mb_substr($value, 0, 190, 'UTF-8'));
    }

    public static function normalizeEthercalcCell($value)
    {
        $value = strtoupper(trim((string)$value));
        return preg_match('/^[A-Z]{1,3}[1-9][0-9]*$/', $value) === 1 ? $value : '';
    }

    public static function normalizeEthercalcColumn($value)
    {
        $value = strtoupper(trim((string)$value));
        return preg_match('/^[A-Z]{1,3}$/', $value) === 1 ? $value : '';
    }

    public static function ethercalcColumnToIndex($column)
    {
        $column = self::normalizeEthercalcColumn($column);
        if ($column === '') {
            return -1;
        }

        $index = 0;
        $length = strlen($column);
        for ($position = 0; $position < $length; $position += 1) {
            $index = ($index * 26) + (ord($column[$position]) - 64);
        }
        return $index - 1;
    }

    public static function normalizeEthercalcRange($value)
    {
        $value = strtoupper(trim((string)$value));
        if (preg_match('/^([A-Z]{1,3}[1-9][0-9]*):([A-Z]{1,3}[1-9][0-9]*)$/', $value, $matches) !== 1) {
            return '';
        }

        $startColumn = self::ethercalcColumnToIndex(preg_replace('/[0-9]+$/', '', $matches[1]));
        $endColumn = self::ethercalcColumnToIndex(preg_replace('/[0-9]+$/', '', $matches[2]));
        $startRow = (int)preg_replace('/^[A-Z]+/', '', $matches[1]);
        $endRow = (int)preg_replace('/^[A-Z]+/', '', $matches[2]);
        return $startColumn <= $endColumn && $startRow <= $endRow ? $value : '';
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
        $this->set('show_cumulative', (int)$this->get('show_cumulative') > 0 ? 1 : 0);
        $measurementFrequency = self::normalizeMeasurementFrequency($this->get('measurement_frequency'));
        $this->set('measurement_frequency', $measurementFrequency);
        $this->set('measurement_schedule', self::normalizeMeasurementSchedule($measurementFrequency, $this->get('measurement_schedule')));

        $sourceType = self::normalizeSourceType($this->get('source_type'));
        $this->set('source_type', $sourceType);
        if ($sourceType === self::SOURCE_ETHERCALC_CELL) {
            $this->set('ethercalc_cell', self::normalizeEthercalcCell($this->get('ethercalc_cell')));
            $this->set('ethercalc_frequency', self::normalizeEthercalcFrequency($this->get('ethercalc_frequency')));
            $this->set('ethercalc_range', null);
            $this->set('ethercalc_date_column', null);
            $this->set('ethercalc_value_column', null);
            $this->clearSpreadsheetSourceFields();
        } elseif ($sourceType === self::SOURCE_ETHERCALC_TABLE) {
            $this->set('ethercalc_cell', null);
            $this->set('ethercalc_frequency', self::normalizeEthercalcFrequency($this->get('ethercalc_frequency')));
            $this->set('ethercalc_range', self::normalizeEthercalcRange($this->get('ethercalc_range')));
            $this->set('ethercalc_date_column', self::normalizeEthercalcColumn($this->get('ethercalc_date_column')));
            $this->set('ethercalc_value_column', self::normalizeEthercalcColumn($this->get('ethercalc_value_column')));
            $this->clearSpreadsheetSourceFields();
        } elseif ($sourceType === self::SOURCE_SPREADSHEET_CELL) {
            $this->clearEthercalcSourceFields();
            $this->set('spreadsheet_sheet', self::normalizeSpreadsheetSheet($this->get('spreadsheet_sheet')));
            $this->set('spreadsheet_cell', self::normalizeEthercalcCell($this->get('spreadsheet_cell')));
            $this->set('spreadsheet_frequency', self::normalizeSpreadsheetFrequency($this->get('spreadsheet_frequency')));
            $this->set('spreadsheet_range', null);
            $this->set('spreadsheet_date_column', null);
            $this->set('spreadsheet_value_column', null);
        } elseif ($sourceType === self::SOURCE_SPREADSHEET_TABLE) {
            $this->clearEthercalcSourceFields();
            $this->set('spreadsheet_sheet', self::normalizeSpreadsheetSheet($this->get('spreadsheet_sheet')));
            $this->set('spreadsheet_cell', null);
            $this->set('spreadsheet_frequency', self::normalizeSpreadsheetFrequency($this->get('spreadsheet_frequency')));
            $this->set('spreadsheet_range', self::normalizeEthercalcRange($this->get('spreadsheet_range')));
            $this->set('spreadsheet_date_column', self::normalizeEthercalcColumn($this->get('spreadsheet_date_column')));
            $this->set('spreadsheet_value_column', self::normalizeEthercalcColumn($this->get('spreadsheet_value_column')));
        } else {
            $this->set('IDdocument', null);
            $this->clearEthercalcSourceFields();
            $this->clearSpreadsheetSourceFields();
        }

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

    public function getEthercalcDocument()
    {
        $documentId = (int)$this->get('IDdocument');
        if ($documentId <= 0) {
            return null;
        }

        $document = new \dbObject\Document();
        return $document->load($documentId) ? $document : null;
    }

    protected function clearEthercalcSourceFields()
    {
        $this->set('ethercalc_cell', null);
        $this->set('ethercalc_frequency', null);
        $this->set('ethercalc_range', null);
        $this->set('ethercalc_date_column', null);
        $this->set('ethercalc_value_column', null);
        $this->set('ethercalc_last_sync_at', null);
    }

    protected function clearSpreadsheetSourceFields()
    {
        $this->set('spreadsheet_sheet', null);
        $this->set('spreadsheet_cell', null);
        $this->set('spreadsheet_frequency', null);
        $this->set('spreadsheet_range', null);
        $this->set('spreadsheet_date_column', null);
        $this->set('spreadsheet_value_column', null);
        $this->set('spreadsheet_last_sync_at', null);
    }

    public function getSpreadsheetDocument()
    {
        $documentId = (int)$this->get('IDdocument');
        if ($documentId <= 0) {
            return null;
        }

        $document = new \dbObject\Document();
        return $document->load($documentId) ? $document : null;
    }

    public function isEthercalcSource()
    {
        return in_array(self::normalizeSourceType($this->get('source_type')), [self::SOURCE_ETHERCALC_CELL, self::SOURCE_ETHERCALC_TABLE], true);
    }

    public function isSpreadsheetSource()
    {
        return in_array(self::normalizeSourceType($this->get('source_type')), [self::SOURCE_SPREADSHEET_CELL, self::SOURCE_SPREADSHEET_TABLE], true);
    }

    public function isHiddenFromCatalog()
    {
        $indicatorId = (int)$this->getId();
        if ($indicatorId <= 0) {
            return false;
        }

        $memberships = new \dbObject\ArrayStatIndicatorGroupItem();
        $memberships->load([
            'where' => [['field' => 'IDstatindicator', 'value' => $indicatorId]],
        ]);
        foreach ($memberships as $membership) {
            $group = new \dbObject\StatIndicatorGroup();
            if (
                !$group->load((int)$membership->get('IDstatindicatorgroup'))
                || (int)$group->get('active') !== 1
                || (int)$group->get('hide_same_holon_sources') !== 1
            ) {
                continue;
            }
            if ((int)$group->get('IDorganization') === (int)$this->get('IDorganization') && (int)$group->get('IDholon') === (int)$this->get('IDholon')) {
                return true;
            }
        }
        return false;
    }

    public function isEthercalcCellSource()
    {
        return self::normalizeSourceType($this->get('source_type')) === self::SOURCE_ETHERCALC_CELL;
    }

    public function isEthercalcTableSource()
    {
        return self::normalizeSourceType($this->get('source_type')) === self::SOURCE_ETHERCALC_TABLE;
    }

    public function isSpreadsheetCellSource()
    {
        return self::normalizeSourceType($this->get('source_type')) === self::SOURCE_SPREADSHEET_CELL;
    }

    public function isSpreadsheetTableSource()
    {
        return self::normalizeSourceType($this->get('source_type')) === self::SOURCE_SPREADSHEET_TABLE;
    }

    public function isEthercalcSyncDue(?\DateTimeInterface $referenceDate = null)
    {
        if (!$this->isEthercalcSource() || (int)$this->get('active') !== 1) {
            return false;
        }

        if ($this->isEthercalcTableSource()) {
            return true;
        }

        $referenceDate = $referenceDate instanceof \DateTimeInterface ? $referenceDate : new \DateTimeImmutable();
        $lastSyncAt = $this->get('ethercalc_last_sync_at');
        if (!($lastSyncAt instanceof \DateTimeInterface)) {
            return true;
        }

        $intervals = [
            self::ETHERCALC_FREQUENCY_HOURLY => 3600,
            self::ETHERCALC_FREQUENCY_DAILY => 86400,
            self::ETHERCALC_FREQUENCY_WEEKLY => 604800,
        ];
        $frequency = self::normalizeEthercalcFrequency($this->get('ethercalc_frequency'));
        return $referenceDate->getTimestamp() >= $lastSyncAt->getTimestamp() + ($intervals[$frequency] ?? 86400);
    }

    public function markEthercalcSynced(\DateTimeInterface $syncedAt)
    {
        $this->set('ethercalc_last_sync_at', \DateTime::createFromInterface($syncedAt));
        return $this->save();
    }

    public function isSpreadsheetSyncDue(?\DateTimeInterface $referenceDate = null)
    {
        if (!$this->isSpreadsheetSource() || (int)$this->get('active') !== 1) {
            return false;
        }

        $referenceDate = $referenceDate instanceof \DateTimeInterface ? $referenceDate : new \DateTimeImmutable();
        $lastSyncAt = $this->get('spreadsheet_last_sync_at');
        if (!($lastSyncAt instanceof \DateTimeInterface)) {
            return true;
        }

        $intervals = [
            self::SPREADSHEET_FREQUENCY_HOURLY => 3600,
            self::SPREADSHEET_FREQUENCY_DAILY => 86400,
            self::SPREADSHEET_FREQUENCY_WEEKLY => 604800,
        ];
        $frequency = self::normalizeSpreadsheetFrequency($this->get('spreadsheet_frequency'));
        return $referenceDate->getTimestamp() >= $lastSyncAt->getTimestamp() + ($intervals[$frequency] ?? 86400);
    }

    public function markSpreadsheetSynced(\DateTimeInterface $syncedAt)
    {
        $this->set('spreadsheet_last_sync_at', \DateTime::createFromInterface($syncedAt));
        return $this->save();
    }

    public function replaceMeasurementsFromEthercalc(array $measurements)
    {
        $indicatorId = (int)$this->getId();
        if ($indicatorId <= 0) {
            return false;
        }

        $pdo = self::getPdo();
        $startedTransaction = false;
        try {
            if ($pdo && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }
            if (!StatIndicatorValue::deleteForIndicator($indicatorId)) {
                throw new \RuntimeException('Unable to delete previous EtherCalc values.');
            }
            foreach ($measurements as $measurement) {
                $measuredAt = is_array($measurement) ? ($measurement['measured_at'] ?? null) : null;
                if (!is_array($measurement) || !is_numeric($measurement['value'] ?? null) || !($measuredAt instanceof \DateTimeInterface)) {
                    continue;
                }
                $value = new StatIndicatorValue();
                $value->set('IDstatindicator', $indicatorId);
                $value->set('IDuser', null);
                $value->set('value', (float)$measurement['value']);
                $value->set('measured_at', $measuredAt);
                $result = $value->save();
                if (!is_array($result) || empty($result['status'])) {
                    throw new \RuntimeException('Unable to save an EtherCalc value.');
                }
            }
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('EtherCalc indicator synchronization failed: ' . $exception->getMessage());
            return false;
        }
    }

    public function replaceMeasurementsFromSpreadsheet(array $measurements)
    {
        $indicatorId = (int)$this->getId();
        if ($indicatorId <= 0) {
            return false;
        }

        $pdo = self::getPdo();
        $startedTransaction = false;
        try {
            if ($pdo && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            }
            if (!StatIndicatorValue::deleteForIndicator($indicatorId)) {
                throw new \RuntimeException('Unable to delete previous spreadsheet values.');
            }
            foreach ($measurements as $measurement) {
                $measuredAt = is_array($measurement) ? ($measurement['measured_at'] ?? null) : null;
                if (!is_array($measurement) || !is_numeric($measurement['value'] ?? null) || !($measuredAt instanceof \DateTimeInterface)) {
                    continue;
                }
                $value = new StatIndicatorValue();
                $value->set('IDstatindicator', $indicatorId);
                $value->set('IDuser', null);
                $value->set('value', (float)$measurement['value']);
                $value->set('measured_at', $measuredAt);
                $result = $value->save();
                if (!is_array($result) || empty($result['status'])) {
                    throw new \RuntimeException('Unable to save a spreadsheet value.');
                }
            }
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->commit();
            }
            return true;
        } catch (\Throwable $exception) {
            if ($startedTransaction && $pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Spreadsheet indicator synchronization failed: ' . $exception->getMessage());
            return false;
        }
    }

    public static function loadDueEthercalcSources($limit = 20, ?\DateTimeInterface $referenceDate = null)
    {
        $sources = new \dbObject\ArrayStatIndicator();
        $sources->load([
            'where' => [
                ['field' => 'active', 'value' => 1],
                ['field' => 'source_type', 'op' => 'in', 'value' => [self::SOURCE_ETHERCALC_CELL, self::SOURCE_ETHERCALC_TABLE]],
            ],
            'orderBy' => [
                ['field' => 'ethercalc_last_sync_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);

        $dueSources = [];
        foreach ($sources as $source) {
            if ($source instanceof self && $source->isEthercalcSyncDue($referenceDate)) {
                $dueSources[] = $source;
                if (count($dueSources) >= max(1, (int)$limit)) {
                    break;
                }
            }
        }
        return $dueSources;
    }

    public static function loadDueSpreadsheetSources($limit = 20, ?\DateTimeInterface $referenceDate = null)
    {
        $sources = new \dbObject\ArrayStatIndicator();
        $sources->load([
            'where' => [
                ['field' => 'active', 'value' => 1],
                ['field' => 'source_type', 'op' => 'in', 'value' => [self::SOURCE_SPREADSHEET_CELL, self::SOURCE_SPREADSHEET_TABLE]],
            ],
            'orderBy' => [
                ['field' => 'spreadsheet_last_sync_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);

        $dueSources = [];
        foreach ($sources as $source) {
            if ($source instanceof self && $source->isSpreadsheetSyncDue($referenceDate)) {
                $dueSources[] = $source;
                if (count($dueSources) >= max(1, (int)$limit)) {
                    break;
                }
            }
        }
        return $dueSources;
    }

    public static function findActiveEthercalcTableSource($organizationId, $holonId, $documentId, $range, $dateColumn, $valueColumn)
    {
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $documentId = (int)$documentId;
        if ($organizationId <= 0 || $documentId <= 0) {
            return null;
        }

        $sources = new \dbObject\ArrayStatIndicator();
        $sources->load([
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
                $holonId > 0
                    ? ['field' => 'IDholon', 'value' => $holonId]
                    : ['field' => 'IDholon', 'op' => 'is null'],
                ['field' => 'IDdocument', 'value' => $documentId],
                ['field' => 'active', 'value' => 1],
                ['field' => 'source_type', 'value' => self::SOURCE_ETHERCALC_TABLE],
                ['field' => 'ethercalc_range', 'value' => self::normalizeEthercalcRange($range)],
                ['field' => 'ethercalc_date_column', 'value' => self::normalizeEthercalcColumn($dateColumn)],
                ['field' => 'ethercalc_value_column', 'value' => self::normalizeEthercalcColumn($valueColumn)],
            ],
            'orderBy' => [
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);

        foreach ($sources as $source) {
            if ($source instanceof self) {
                return $source;
            }
        }
        return null;
    }

    public static function findActiveSpreadsheetTableSource($organizationId, $holonId, $documentId, $sheet, $range, $dateColumn, $valueColumn)
    {
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $documentId = (int)$documentId;
        if ($organizationId <= 0 || $documentId <= 0) {
            return null;
        }

        $sources = new \dbObject\ArrayStatIndicator();
        $sources->load([
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
                $holonId > 0
                    ? ['field' => 'IDholon', 'value' => $holonId]
                    : ['field' => 'IDholon', 'op' => 'is null'],
                ['field' => 'IDdocument', 'value' => $documentId],
                ['field' => 'active', 'value' => 1],
                ['field' => 'source_type', 'value' => self::SOURCE_SPREADSHEET_TABLE],
                ['field' => 'spreadsheet_sheet', 'value' => self::normalizeSpreadsheetSheet($sheet)],
                ['field' => 'spreadsheet_range', 'value' => self::normalizeEthercalcRange($range)],
                ['field' => 'spreadsheet_date_column', 'value' => self::normalizeEthercalcColumn($dateColumn)],
                ['field' => 'spreadsheet_value_column', 'value' => self::normalizeEthercalcColumn($valueColumn)],
            ],
            'orderBy' => [['field' => 'id', 'dir' => 'ASC']],
        ]);

        foreach ($sources as $source) {
            if ($source instanceof self) {
                return $source;
            }
        }
        return null;
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
