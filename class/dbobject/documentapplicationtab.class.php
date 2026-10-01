<?php
namespace dbObject;

class DocumentApplicationTab extends DbObject
{
    public static function tableName()
    {
        return 'document_application_tab';
    }

    public static function rules()
    {
        return [
            [['IDdocument', 'IDapplication'], 'required'],
            [['IDdocument', 'IDapplication'], 'fk'],
            [['id', 'position'], 'integer'],
            [['view_parameters'], 'parameters'],
            [['datecreation', 'datemodification'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdocument' => 'Document PV',
            'IDapplication' => 'Application',
            'position' => 'Position',
            'view_parameters' => 'Configuration de la vue',
            'datecreation' => 'Creation',
            'datemodification' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'IDdocument' => 'PV dans lequel l onglet est affiche.',
            'IDapplication' => 'Application ouverte dans l espace de reunion.',
            'position' => 'Ordre de l onglet dans l editeur du PV.',
            'view_parameters' => 'Portee, tri, mode d affichage et filtres propres a ce PV.',
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public static function hasTable(): bool
    {
        return self::tableExists(self::tableName());
    }

    public function getViewParametersArray(): array
    {
        $parameters = json_decode((string)$this->get('view_parameters'), true);
        return UserHolon::normalizeApplicationView(is_array($parameters) ? $parameters : []);
    }

    public function setViewParametersArray(array $parameters): void
    {
        $this->set('view_parameters', UserHolon::normalizeApplicationView($parameters));
    }

    public function getApplication(): ?Application
    {
        $application = $this->get('application');
        return $application instanceof Application && (int)$application->getId() > 0
            ? $application
            : null;
    }

    public function getApplicationKey(): string
    {
        $application = $this->getApplication();
        if (!($application instanceof Application)) {
            return '';
        }

        $directory = trim(mb_strtolower((string)$application->get('directory'), 'UTF-8'));
        $hash = trim(mb_strtolower((string)$application->get('hash'), 'UTF-8'));
        return UserHolon::normalizeApplicationViewKey($directory) !== ''
            ? UserHolon::normalizeApplicationViewKey($directory)
            : UserHolon::normalizeApplicationViewKey($hash);
    }

    public function matchesApplicationKey(string $applicationKey): bool
    {
        return $this->getApplicationKey() !== ''
            && $this->getApplicationKey() === UserHolon::normalizeApplicationViewKey($applicationKey);
    }

    public function belongsToDocument(Document $document): bool
    {
        return (int)$this->get('IDdocument') > 0
            && (int)$this->get('IDdocument') === (int)$document->getId();
    }

    public static function findForDocumentAndApplication(int $documentId, int $applicationId): ?self
    {
        if ($documentId <= 0 || $applicationId <= 0 || !self::hasTable()) {
            return null;
        }

        $item = new self();
        return $item->load([
            ['IDdocument', $documentId],
            ['IDapplication', $applicationId],
        ]) ? $item : null;
    }

    public static function createForDocument(Document $document, int $applicationId): array
    {
        $documentId = (int)$document->getId();
        $organizationId = (int)$document->get('IDorganization');
        if (
            $documentId <= 0
            || !$document->isPvDocument()
            || $document->isPvValidated()
            || $applicationId <= 0
            || !self::hasTable()
            || !Application::isEnabledForOrganization($applicationId, $organizationId)
        ) {
            return ['status' => false, 'text' => 'Application indisponible pour ce PV.'];
        }

        $existing = self::findForDocumentAndApplication($documentId, $applicationId);
        if ($existing instanceof self) {
            return ['status' => true, 'item' => $existing, 'created' => false];
        }

        $item = new self();
        $item->set('IDdocument', $documentId);
        $item->set('IDapplication', $applicationId);
        $item->set('position', self::nextPositionForDocument($documentId));
        $item->setViewParametersArray([]);
        $item->set('datecreation', new \DateTimeImmutable());

        $result = $item->save();
        if (!is_array($result) || empty($result['status'])) {
            return is_array($result)
                ? $result
                : ['status' => false, 'text' => 'Impossible d ajouter l application au PV.'];
        }

        return ['status' => true, 'item' => $item, 'created' => true];
    }

    public function saveView(array $view): array
    {
        $this->setViewParametersArray($view);
        $this->set('datemodification', new \DateTimeImmutable());
        $result = $this->save();
        return is_array($result)
            ? $result
            : ['status' => false, 'text' => 'Impossible d enregistrer la vue du PV.'];
    }

    protected static function nextPositionForDocument(int $documentId): int
    {
        $position = self::fetchValue(
            'SELECT COALESCE(MAX(`position`), 0) + 10 FROM `document_application_tab` WHERE `IDdocument` = :document_id',
            ['document_id' => $documentId]
        );
        return max(10, (int)$position);
    }
}

