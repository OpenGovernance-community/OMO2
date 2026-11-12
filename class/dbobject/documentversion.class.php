<?php
namespace dbObject;

// Versions are independent snapshots, never ordinary documents or Collabora targets.
class DocumentVersion extends DbObject
{
    public static function tableName()
    {
        return 'document_version';
    }

    public static function rules()
    {
        return [
            [['IDdocument', 'reason', 'documenttype', 'storedfilepath', 'storedfilename', 'storedfilemime'], 'required'],
            [['id', 'storedfilesize'], 'integer'],
            [['IDdocument', 'IDuser'], 'fk'],
            [['datecreation'], 'datetime'],
            [['reason', 'documenttype', 'storedfilepath', 'storedfilename', 'storedfilemime'], 'string'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID', 'IDdocument' => 'Document', 'IDuser' => 'Auteur de l’archive',
            'datecreation' => 'Date d’archivage', 'reason' => 'Origine', 'documenttype' => 'Type de document',
            'storedfilepath' => 'Chemin distant du fichier', 'storedfilename' => 'Nom original du fichier',
            'storedfilemime' => 'Type MIME du fichier', 'storedfilesize' => 'Taille du fichier',
        ];
    }

    public static function attributeLength()
    {
        return ['reason' => 30, 'documenttype' => 50, 'storedfilepath' => 1000, 'storedfilename' => 255, 'storedfilemime' => 255];
    }

    public function belongsToDocument(Document $document): bool
    {
        return (int)$document->getId() > 0 && (int)$this->get('IDdocument') === (int)$document->getId();
    }

    public function buildDownloadUrl(): string
    {
        return '/omo/api/documents/upload/download.php?id=' . (int)$this->get('IDdocument')
            . '&version_id=' . (int)$this->getId();
    }

    // The caller holds the document row lock until its transaction finishes.
    public static function createFileSnapshot(Document $document, Organization $organization, int $userId, string $reason): array
    {
        if (!$document->hasStoredFile() || (int)$organization->getId() !== (int)$document->get('IDorganization')) {
            return ['status' => false];
        }
        $download = $organization->downloadDocumentFileFromStorage((string)$document->get('storedfilepath'));
        if (empty($download['status'])) {
            return ['status' => false];
        }
        $body = (string)($download['body'] ?? '');
        $temporaryFile = tempnam(sys_get_temp_dir(), 'omo-version-');
        if ($temporaryFile === false) {
            return ['status' => false];
        }
        $archivePath = '';
        try {
            if (file_put_contents($temporaryFile, $body) !== strlen($body)) {
                return ['status' => false];
            }
            $extension = pathinfo($document->getStoredFileDownloadName(), PATHINFO_EXTENSION);
            $upload = $organization->uploadDocumentFileToStorage((int)$document->getId(), [
                'error' => UPLOAD_ERR_OK, 'tmp_name' => $temporaryFile,
                'name' => 'archive-' . bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : ''),
                'type' => $document->getStoredFileMimeType(), 'size' => strlen($body),
            ]);
            if (empty($upload['status'])) {
                return ['status' => false];
            }
            $archivePath = (string)$upload['relativePath'];
            $version = new self();
            $version->set('IDdocument', $document->getId());
            $version->set('IDuser', $userId);
            $version->set('datecreation', new \DateTimeImmutable());
            $version->set('reason', $reason);
            $version->set('documenttype', $document->getDocumentType());
            $version->set('storedfilepath', $archivePath);
            $version->set('storedfilename', $document->getStoredFileDownloadName());
            $version->set('storedfilemime', $document->getStoredFileMimeType());
            $version->set('storedfilesize', strlen($body));
            $save = $version->save();
            if (empty($save['status'])) {
                $organization->deleteDocumentFileFromStorage($archivePath);
                return ['status' => false];
            }
            return ['status' => true, 'version' => $version];
        } catch (\Throwable $exception) {
            if ($archivePath !== '') {
                $organization->deleteDocumentFileFromStorage($archivePath);
            }
            error_log('Document snapshot failed: ' . $exception->getMessage());
            return ['status' => false];
        } finally {
            unlink($temporaryFile);
        }
    }
}
