<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/invitations_shared.php';

use dbObject\ArrayHolon;
use dbObject\Document;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;

$sourceLang = array_merge([
    'calendar.create.title' => [
        'text' => 'Nouvel evenement',
        'context' => 'Title shown at the top of the event creation form.',
    ],
    'calendar.edit.title' => [
        'text' => "Modifier l evenement",
        'context' => 'Title shown at the top of the event edition form.',
    ],
    'calendar.create.description' => [
        'text' => 'Planifiez une date, un horaire, un lieu et un document associe si besoin.',
        'context' => 'Intro text shown in the event creation form.',
    ],
    'calendar.edit.description' => [
        'text' => "Mettez a jour la date, l horaire, le lieu et le document associe.",
        'context' => 'Intro text shown in the event edition form.',
    ],
    'calendar.create.field.title' => [
        'text' => 'Titre',
        'context' => 'Label of the event title field.',
    ],
    'calendar.create.field.description' => [
        'text' => 'Description',
        'context' => 'Label of the event description field.',
    ],
    'calendar.create.field.start' => [
        'text' => 'Debut',
        'context' => 'Label of the event start date time field.',
    ],
    'calendar.create.field.end' => [
        'text' => 'Fin',
        'context' => 'Label of the event end date time field.',
    ],
    'calendar.create.field.holon' => [
        'text' => 'Cercle ou role',
        'context' => 'Label of the optional holon association field.',
    ],
    'calendar.create.field.all_day' => [
        'text' => 'Journee entiere',
        'context' => 'Label of the all day checkbox in the event creation form.',
    ],
    'calendar.create.field.none' => [
        'text' => 'Aucun rattachement',
        'context' => 'Empty option shown in the holon select field.',
    ],
    'calendar.create.field.location_mode' => [
        'text' => 'Format du lieu',
        'context' => 'Label of the event location mode field.',
    ],
    'calendar.create.field.location_mode_pending' => [
        'text' => 'A definir',
        'context' => 'Fallback option when the event location is not specified yet.',
    ],
    'calendar.create.field.location_address' => [
        'text' => 'Adresse',
        'context' => 'Label of the physical address field.',
    ],
    'calendar.create.field.location_address_placeholder' => [
        'text' => 'Rue, numero, NPA, localite',
        'context' => 'Placeholder shown in the physical address field.',
    ],
    'calendar.create.field.video_url' => [
        'text' => 'Lien de visio',
        'context' => 'Label of the virtual meeting URL field.',
    ],
    'calendar.create.field.video_url_placeholder' => [
        'text' => 'https://...',
        'context' => 'Placeholder shown in the virtual meeting URL field.',
    ],
    'calendar.create.field.document_type' => [
        'text' => 'Document associe',
        'context' => 'Label of the linked document type field.',
    ],
    'calendar.create.tab.event' => [
        'text' => 'Evenement',
        'context' => 'First tab label in the event creation form.',
    ],
    'calendar.create.tab.invites' => [
        'text' => 'Invites',
        'context' => 'Second tab label in the event creation form for invitation settings.',
    ],
    'calendar.create.tabs_aria' => [
        'text' => 'Configuration de l evenement',
        'context' => 'Accessible label of the tabs used in the event creation form.',
    ],
    'calendar.create.field.document_title' => [
        'text' => 'Nom du document',
        'context' => 'Optional label of the linked document title field.',
    ],
    'calendar.create.document.help_create' => [
        'text' => 'Si vous choisissez un type, un document vide sera cree automatiquement avec le titre de l evenement, sa description et des tags par defaut. Vous pourrez ensuite le modifier depuis le module Documents.',
        'context' => 'Help text shown when the user chooses a linked document type from the event form.',
    ],
    'calendar.create.document.help_existing' => [
        'text' => 'Le document lie reste modifiable depuis le module Documents.',
        'context' => 'Help text shown when an event already has a linked document.',
    ],
    'calendar.create.document.open' => [
        'text' => 'Ouvrir le document',
        'context' => 'Button label used to open the linked document from the event form.',
    ],
    'calendar.create.document.empty_title' => [
        'text' => 'Document sans titre',
        'context' => 'Fallback title shown when a linked document has no title yet.',
    ],
    'calendar.create.document.created_notice' => [
        'text' => 'Le document sera cree vide avec ses metadonnees par defaut.',
        'context' => 'Notice shown below the document type selector before creating the linked document.',
    ],
    'calendar.create.document.current' => [
        'text' => 'Document actuel',
        'context' => 'Label shown above the existing linked document summary.',
    ],
    'calendar.create.document.keyword_pv' => [
        'text' => 'PV',
        'context' => 'Localized keyword used as the default tag for PV documents created from the calendar.',
    ],
    'calendar.create.document.default_pv_title' => [
        'text' => '{pvLabel} {eventTitle} du {eventDate}',
        'context' => 'Default linked PV document title generated when no custom title is provided.',
    ],
    'calendar.create.document.none' => [
        'text' => 'Aucun document',
        'context' => 'Option shown when no linked document should be created.',
    ],
    'calendar.create.submit' => [
        'text' => 'Creer l evenement',
        'context' => 'Submit button label of the event creation form.',
    ],
    'calendar.edit.submit' => [
        'text' => 'Enregistrer les modifications',
        'context' => 'Submit button label of the event edition form.',
    ],
    'calendar.create.success' => [
        'text' => 'Evenement cree.',
        'context' => 'Success message returned after an event is created.',
    ],
    'calendar.edit.success' => [
        'text' => 'Evenement mis a jour.',
        'context' => 'Success message returned after an event is updated.',
    ],
    'calendar.create.error.title' => [
        'text' => 'Le titre est obligatoire.',
        'context' => 'Validation error returned when the title is missing.',
    ],
    'calendar.create.error.start' => [
        'text' => 'La date de debut est invalide.',
        'context' => 'Validation error returned when the start date is invalid.',
    ],
    'calendar.create.error.end' => [
        'text' => 'La date de fin est invalide.',
        'context' => 'Validation error returned when the end date is invalid.',
    ],
    'calendar.create.error.holon' => [
        'text' => 'Le contexte choisi est invalide.',
        'context' => 'Validation error returned when the selected holon is not allowed.',
    ],
    'calendar.create.error.document_type' => [
        'text' => 'Le type de document associe est invalide.',
        'context' => 'Validation error returned when the selected linked document type is invalid.',
    ],
    'calendar.create.error.document_permission' => [
        'text' => 'Vous ne pouvez pas creer de document dans ce contexte.',
        'context' => 'Validation error returned when the linked document cannot be created in the selected context.',
    ],
    'calendar.create.error.save' => [
        'text' => 'Impossible d enregistrer cet evenement.',
        'context' => 'Generic error returned when the event could not be saved.',
    ],
], omoCalendarInvitationSourceLang());

$lang = omoLoadTranslationBundle('omo_calendar_create', $sourceLang);

function omoCalendarCreateT($key, array $replace = [])
{
    global $lang, $sourceLang;
    return t($key, $replace, $lang, $sourceLang);
}

function omoCalendarParseLocalDateTime($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return null;
    }

    $formats = ['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'];
    foreach ($formats as $format) {
        $value = \DateTime::createFromFormat($format, $rawValue);
        if ($value instanceof \DateTime) {
            return $value;
        }
    }

    try {
        return new \DateTime($rawValue);
    } catch (\Throwable $exception) {
        return null;
    }
}

function omoCalendarDocumentTypeOptions(bool $nextcloudDocumentsAvailable): array
{
    $options = [
        '' => omoCalendarCreateT('calendar.create.document.none'),
        Document::TYPE_HTML => (string)Document::getDocumentTypeCatalog()[Document::TYPE_HTML],
        Document::TYPE_EXTERNAL_LINK => (string)Document::getDocumentTypeCatalog()[Document::TYPE_EXTERNAL_LINK],
        Document::TYPE_PV => (string)Document::getDocumentTypeCatalog()[Document::TYPE_PV],
    ];

    if ($nextcloudDocumentsAvailable) {
        $options[Document::TYPE_UPLOADED_FILE] = (string)Document::getDocumentTypeCatalog()[Document::TYPE_UPLOADED_FILE];
    }

    return $options;
}

function omoCalendarBuildDefaultLinkedDocumentValues(string $eventTitle, string $eventDescription, \DateTimeInterface $startAt, string $documentType, string $documentTitle = ''): array
{
    $normalizedEventTitle = trim($eventTitle);
    $normalizedTitle = trim($documentTitle);

    if ($normalizedTitle === '') {
        if ($documentType === Document::TYPE_PV) {
            $normalizedTitle = omoCalendarCreateT('calendar.create.document.default_pv_title', [
                'pvLabel' => omoCalendarCreateT('calendar.create.document.keyword_pv'),
                'eventTitle' => $normalizedEventTitle !== '' ? $normalizedEventTitle : 'Evenement',
                'eventDate' => $startAt->format('d.m.Y H:i'),
            ]);
        } elseif ($normalizedEventTitle !== '') {
            $normalizedTitle = $normalizedEventTitle;
        } else {
            $normalizedTitle = 'Evenement du ' . $startAt->format('d.m.Y H:i');
        }
    }

    $description = trim($eventDescription);
    if ($description === '') {
        if ($normalizedEventTitle !== '') {
            $description = 'Document associe a l evenement "' . $normalizedEventTitle . '".';
        } else {
            $description = 'Document associe a l evenement du ' . $startAt->format('d.m.Y H:i') . '.';
        }
    }

    $keywords = $documentType === Document::TYPE_PV
        ? trim(omoCalendarCreateT('calendar.create.document.keyword_pv'))
        : '';

    return [
        'title' => $normalizedTitle,
        'description' => $description,
        'keywords' => $keywords,
    ];
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_REQUEST['oid'] ?? 0));
$currentHolonId = isset($_REQUEST['cid']) && is_numeric($_REQUEST['cid']) ? (int)$_REQUEST['cid'] : 0;
$currentUserId = (int)commonGetCurrentUserId();
$eventId = isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    if (commonIsAjaxJsonRequest()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Acces refuse.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Acces refuse.</div>';
    }
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
    http_response_code(403);
    if (commonIsAjaxJsonRequest()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Organisation invalide.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Organisation invalide.</div>';
    }
    exit;
}

$rootHolon = $organization->getEnabledStructuralRootHolon();
$nextcloudDocumentsAvailable = $organization->hasNextcloudDocumentStorage();

$event = new Event();
$isEditMode = false;

if ($eventId > 0) {
    if (
        !$event->load($eventId)
        || (int)$event->get('IDorganization') !== $organizationId
        || (int)$event->get('IDuser') !== $currentUserId
    ) {
        http_response_code(403);
        if (commonIsAjaxJsonRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => false,
                'message' => 'Evenement invalide.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo '<div class="omo-empty-state">Evenement invalide.</div>';
        }
        exit;
    }

    $isEditMode = true;
}

$associatedDocument = $isEditMode ? $event->getAssociatedDocument() : null;

$holons = new ArrayHolon();
$holons->loadVisibilityTargetsForOrganization($organizationId, [2, 1]);
$holonOptions = $holons->buildVisibilityTargetOptions();
$allowedHolonIds = [];

foreach (['circle', 'role'] as $typeKey) {
    foreach (($holonOptions[$typeKey] ?? []) as $option) {
        $allowedHolonIds[(int)($option['id'] ?? 0)] = $option;
    }
}

$currentContextHolon = null;
if ($currentHolonId > 0 && isset($allowedHolonIds[$currentHolonId])) {
    $candidateHolon = new Holon();
    if ($candidateHolon->load($currentHolonId)) {
        $currentContextHolon = $candidateHolon;
    }
}

$usePermissionSessionCache = $_SERVER['REQUEST_METHOD'] !== 'POST';
$createPermissionHolon = $currentContextHolon instanceof Holon ? $currentContextHolon : $rootHolon;
$canCreateEvent = $currentUserId > 0
    && (
        $createPermissionHolon instanceof Holon
            ? $createPermissionHolon->isAllowed('CAN_CREATE_EVENT', $usePermissionSessionCache, $currentUserId)
            : commonCurrentUserHasOrganizationAccess($organizationId)
    );

if (!$isEditMode && !$canCreateEvent) {
    http_response_code(403);
    if (commonIsAjaxJsonRequest()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Acces refuse.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Acces refuse.</div>';
    }
    exit;
}

$defaultHolonId = 0;
if ($currentHolonId > 0 && isset($allowedHolonIds[$currentHolonId])) {
    $defaultHolonId = $currentHolonId;
}

if ($isEditMode) {
    $loadedHolonId = (int)$event->get('IDholon');
    if ($loadedHolonId > 0 && isset($allowedHolonIds[$loadedHolonId])) {
        $defaultHolonId = $loadedHolonId;
    } else {
        $defaultHolonId = 0;
    }
}

$invitationEditorState = omoCalendarBuildInvitationEditorState(
    $isEditMode ? $event : null,
    $organization,
    $organizationId,
    $currentContextHolon,
    $defaultHolonId > 0 ? $defaultHolonId : $currentHolonId,
    $defaultHolonId,
    true
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $selectedHolonId = isset($_POST['IDholon']) ? (int)$_POST['IDholon'] : 0;
    $startAt = omoCalendarParseLocalDateTime($_POST['start_at'] ?? '');
    $endAt = omoCalendarParseLocalDateTime($_POST['end_at'] ?? '');
    $isAllDay = !empty($_POST['is_all_day']);
    $locationMode = Event::normalizeLocationMode($_POST['location_mode'] ?? '');
    $locationAddress = trim((string)($_POST['location_address'] ?? ''));
    $videoMeetingUrl = Event::sanitizeVideoMeetingUrl($_POST['video_meeting_url'] ?? '');

    $requestedDocumentType = trim((string)($_POST['document_type'] ?? ''));
    $documentTitle = trim((string)($_POST['document_title'] ?? ''));
    $selectedInvitationHolonIds = array_values(array_unique(array_filter(array_map('intval', $_POST['invitation_holon_ids'] ?? []), static function ($holonId) {
        return $holonId > 0;
    })));
    $selectedInvitationUserIds = array_values(array_unique(array_filter(array_map('intval', $_POST['invitation_user_ids'] ?? []), static function ($userId) {
        return $userId > 0;
    })));
    $selectedInvitationEmails = omoCalendarInvitationParseEmails($_POST['invitation_emails'] ?? '');

    if ($title === '') {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.title'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!($startAt instanceof \DateTimeInterface)) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.start'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!($endAt instanceof \DateTimeInterface)) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.end'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($endAt < $startAt) {
        $endAt = (clone $startAt);
    }

    if ($selectedHolonId > 0 && !isset($allowedHolonIds[$selectedHolonId])) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.holon'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!$isEditMode) {
        $targetPermissionHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ($selectedHolonId > 0) {
            $targetPermissionHolon = new Holon();
            if (!$targetPermissionHolon->load($selectedHolonId)) {
                echo json_encode([
                    'status' => false,
                    'message' => omoCalendarCreateT('calendar.create.error.holon'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        $canCreateSelectedEvent = $targetPermissionHolon instanceof Holon
            ? $targetPermissionHolon->isAllowed('CAN_CREATE_EVENT', false, $currentUserId)
            : commonCurrentUserHasOrganizationAccess($organizationId);
        if (!$canCreateSelectedEvent) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => 'Acces refuse.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    if ($isAllDay) {
        $startAt = (clone $startAt)->setTime(0, 0, 0);
        $endAt = (clone $endAt)->setTime(23, 59, 59);
    }

    $linkedDocument = $associatedDocument instanceof Document ? $associatedDocument : null;
    $resolvedDocumentType = '';
    $willCreateDocument = false;

    if ($linkedDocument instanceof Document) {
        $resolvedDocumentType = $linkedDocument->getDocumentType();
    } elseif ($requestedDocumentType !== '') {
        $documentOptions = omoCalendarDocumentTypeOptions($nextcloudDocumentsAvailable);
        if (!array_key_exists($requestedDocumentType, $documentOptions)) {
            echo json_encode([
                'status' => false,
                'message' => omoCalendarCreateT('calendar.create.error.document_type'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $resolvedDocumentType = Document::normalizeDocumentType($requestedDocumentType, false);
        $willCreateDocument = $resolvedDocumentType !== '';
        if (
            $willCreateDocument
            && !Document::canCreateInOrganizationContext(
                $organizationId,
                $selectedHolonId > 0 ? $selectedHolonId : null,
                $currentUserId,
                0,
                false
            )
        ) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => omoCalendarCreateT('calendar.create.error.document_permission'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    if (!$isEditMode) {
        $event = new Event();
        $event->set('IDuser', $currentUserId);
        $event->set('active', 1);
    }

    $event->set('IDorganization', $organizationId);
    $event->set('IDholon', $selectedHolonId > 0 ? $selectedHolonId : null);
    $event->set('title', $title);
    $event->set('description', $description !== '' ? $description : null);
    $event->set('status', Event::STATUS_CONFIRMED);
    $event->set('timezone', date_default_timezone_get());
    $event->set('locationmode', $locationMode !== '' ? $locationMode : null);
    $event->set('locationaddress', $locationAddress !== '' ? $locationAddress : null);
    $event->set('videomeetingurl', $videoMeetingUrl !== '' ? $videoMeetingUrl : null);
    $event->set('start_at', $startAt);
    $event->set('end_at', $endAt);
    $event->set('is_all_day', $isAllDay ? 1 : 0);

    $pdo = \dbObject\DbObject::getPdo();
    $startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();

    try {
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        $saveResult = $event->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            echo json_encode([
                'status' => false,
                'message' => trim((string)($saveResult['text'] ?? '')) !== ''
                    ? trim((string)$saveResult['text'])
                    : omoCalendarCreateT('calendar.create.error.save'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($willCreateDocument) {
            $linkedDocument = new Document();
            $defaultDocumentValues = omoCalendarBuildDefaultLinkedDocumentValues(
                $title,
                $description,
                $startAt,
                $resolvedDocumentType,
                $documentTitle
            );
            $documentCreateResult = $linkedDocument->createInOrganizationContext(
                $organizationId,
                $selectedHolonId > 0 ? $selectedHolonId : null,
                $currentUserId,
                [
                    'title' => $defaultDocumentValues['title'],
                    'description' => $defaultDocumentValues['description'],
                    'keywords' => $defaultDocumentValues['keywords'],
                    'document_type' => $resolvedDocumentType,
                    'event_id' => (int)$event->getId(),
                    'allow_empty_type_payload' => 1,
                ]
            );
            if (!is_array($documentCreateResult) || empty($documentCreateResult['status'])) {
                if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                echo json_encode([
                    'status' => false,
                    'message' => trim((string)($documentCreateResult['text'] ?? '')) !== ''
                        ? trim((string)$documentCreateResult['text'])
                        : omoCalendarCreateT('calendar.create.error.save'),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            $syncDocumentDateResult = $event->syncAssociatedDocumentEventDate();
            if (!is_array($syncDocumentDateResult) || empty($syncDocumentDateResult['status'])) {
                if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                echo json_encode([
                    'status' => false,
                    'message' => trim((string)($syncDocumentDateResult['text'] ?? '')) !== ''
                        ? trim((string)$syncDocumentDateResult['text'])
                        : omoCalendarCreateT('calendar.create.error.save'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        } elseif ($linkedDocument instanceof Document) {
            $syncDocumentDateResult = $event->syncAssociatedDocumentEventDate();
            if (!is_array($syncDocumentDateResult) || empty($syncDocumentDateResult['status'])) {
                if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                echo json_encode([
                    'status' => false,
                    'message' => trim((string)($syncDocumentDateResult['text'] ?? '')) !== ''
                        ? trim((string)$syncDocumentDateResult['text'])
                        : omoCalendarCreateT('calendar.create.error.save'),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }

        $applyInvitationResult = omoCalendarApplyInvitationSelections(
            $event,
            $organization,
            $organizationId,
            $selectedInvitationHolonIds,
            $selectedInvitationUserIds,
            $selectedInvitationEmails
        );
        if (!is_array($applyInvitationResult) || empty($applyInvitationResult['status'])) {
            if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            echo json_encode([
                'status' => false,
                'message' => trim((string)($applyInvitationResult['message'] ?? '')) !== ''
                    ? trim((string)$applyInvitationResult['message'])
                    : omoCalendarCreateT('calendar.invitations.save_error'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.save'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    echo json_encode([
        'status' => true,
        'message' => omoCalendarCreateT($isEditMode ? 'calendar.edit.success' : 'calendar.create.success'),
        'eventId' => (int)$event->getId(),
        'documentId' => $linkedDocument instanceof Document ? (int)$linkedDocument->getId() : 0,
        'detailUrl' => '/omo/api/calendar/detail.php?oid=' . rawurlencode((string)$organizationId)
            . ($selectedHolonId > 0 ? '&cid=' . rawurlencode((string)$selectedHolonId) : '')
            . '&id=' . rawurlencode((string)(int)$event->getId()),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$initialDate = trim((string)($_GET['date'] ?? ''));
$initialDateTime = trim((string)($_GET['datetime'] ?? ''));
$initialStartDefault = null;

if (!$isEditMode && $initialDateTime !== '') {
    $initialStartDefault = omoCalendarParseLocalDateTime($initialDateTime);
}

$initialDateDefault = $initialDate !== ''
    ? omoCalendarParseLocalDateTime($initialDate . 'T09:00')
    : null;

$startDefault = $isEditMode
    ? $event->get('start_at')
    : ($initialStartDefault ?: $initialDateDefault ?: new \DateTime('today 09:00'));
$endDefault = $isEditMode
    ? $event->get('end_at')
    : (clone $startDefault)->modify('+1 hour');
$titleDefault = $isEditMode ? trim((string)$event->get('title')) : '';
$descriptionDefault = $isEditMode ? trim((string)$event->get('description')) : '';
$isAllDayDefault = $isEditMode ? (bool)$event->get('is_all_day') : false;
$locationDisplayData = $isEditMode ? $event->getLocationDisplayData() : ['mode' => '', 'address' => '', 'videoUrl' => ''];
$locationModeDefault = $locationDisplayData['mode'] !== ''
    ? (string)$locationDisplayData['mode']
    : ($isEditMode ? '' : Event::LOCATION_MODE_IN_PERSON);
$locationAddressDefault = trim((string)($locationDisplayData['address'] ?? ''));
$videoMeetingUrlDefault = trim((string)($locationDisplayData['videoUrl'] ?? ''));

$documentTypeDefault = $associatedDocument instanceof Document ? $associatedDocument->getDocumentType() : '';
$documentTitleDefault = '';
$documentTypeOptions = omoCalendarDocumentTypeOptions($nextcloudDocumentsAvailable);
$associatedDocumentUrl = $associatedDocument instanceof Document
    ? $event->buildAssociatedDocumentDetailUrl($currentHolonId > 0 ? $currentHolonId : $defaultHolonId)
    : '';
$associatedDocumentPvPreparationUrl = $associatedDocument instanceof Document
    && $associatedDocument->canUserPrepareUpcomingPv($currentUserId, $organizationId)
    ? $associatedDocument->buildUpcomingPvEditorUrl($organizationId)
    : '';
$locationModeOptions = array_merge(
    ['' => omoCalendarCreateT('calendar.create.field.location_mode_pending')],
    array_map(static function (array $definition): string {
        return (string)($definition['label'] ?? '');
    }, Event::getLocationModeCatalog())
);
?>
<div class="omo-calendar-create">
    <div class="generic-section generic-section--stack omo-calendar-create__shell">
        <div class="omo-calendar-create__head">
            <h2 class="generic-card-title generic-card-title--medium"><?= omoApiEscape(omoCalendarCreateT($isEditMode ? 'calendar.edit.title' : 'calendar.create.title')) ?></h2>
            <p class="omo-calendar-create__text"><?= omoApiEscape(omoCalendarCreateT($isEditMode ? 'calendar.edit.description' : 'calendar.create.description')) ?></p>
        </div>

        <form
            class="omo-calendar-create__form"
            method="post"
            action="/omo/api/calendar/create.php?oid=<?= (int)$organizationId ?><?= $currentHolonId > 0 ? '&cid=' . (int)$currentHolonId : '' ?><?= $isEditMode ? '&id=' . (int)$event->getId() : '' ?>"
            data-omo-calendar-create-form
        >
            <?php if ($isEditMode): ?>
                <input type="hidden" name="id" value="<?= (int)$event->getId() ?>">
            <?php endif; ?>

            <div class="generic-tabs omo-calendar-create__tabs" data-generic-tabs>
                <div class="generic-tabs__list" aria-label="<?= omoApiEscape(omoCalendarCreateT('calendar.create.tabs_aria')) ?>">
                    <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoCalendarCreateTabEvent"><?= omoApiEscape(omoCalendarCreateT('calendar.create.tab.event')) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoCalendarCreateTabInvites"><?= omoApiEscape(omoCalendarCreateT('calendar.create.tab.invites')) ?></button>
                </div>
                <div class="generic-tabs__panels">
                    <div id="omoCalendarCreateTabEvent" class="generic-tabs__panel omo-calendar-create__tab-panel" data-generic-tab-panel>
                        <div class="omo-calendar-create__grid">
                            <label class="omo-calendar-create__field">
                                <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.title')) ?></span>
                                <input
                                    type="text"
                                    name="title"
                                    class="generic-form-control"
                                    value="<?= omoApiEscape($titleDefault) ?>"
                                    maxlength="190"
                                    required
                                >
                            </label>

                            <label class="omo-calendar-create__field">
                                <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.holon')) ?></span>
                                <select name="IDholon" class="generic-form-control" data-omo-calendar-context-holon>
                                    <option value="0"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.none')) ?></option>
                                    <?php foreach (['circle', 'role'] as $typeKey): ?>
                                        <?php foreach (($holonOptions[$typeKey] ?? []) as $option): ?>
                                            <option value="<?= (int)$option['id'] ?>"<?= (int)$option['id'] === $defaultHolonId ? ' selected' : '' ?>>
                                                <?= omoApiEscape((string)$option['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label class="omo-calendar-create__field">
                                <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.start')) ?></span>
                                <input
                                    type="datetime-local"
                                    name="start_at"
                                    class="generic-form-control"
                                    value="<?= omoApiEscape($startDefault->format('Y-m-d\TH:i')) ?>"
                                    required
                                >
                            </label>

                            <label class="omo-calendar-create__field">
                                <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.end')) ?></span>
                                <input
                                    type="datetime-local"
                                    name="end_at"
                                    class="generic-form-control"
                                    value="<?= omoApiEscape($endDefault->format('Y-m-d\TH:i')) ?>"
                                    required
                                >
                            </label>
                        </div>

                        <label class="omo-calendar-create__field">
                            <span class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.description')) ?></span>
                            <textarea
                                name="description"
                                class="generic-form-control"
                            ><?= omoApiEscape($descriptionDefault) ?></textarea>
                        </label>

                        <label class="omo-calendar-create__check">
                            <input type="checkbox" name="is_all_day" value="1"<?= $isAllDayDefault ? ' checked' : '' ?>>
                            <span><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.all_day')) ?></span>
                        </label>

                        <section class="generic-soft-panel generic-soft-panel--stack omo-calendar-create__block">
                            <div class="omo-calendar-create__block-head">
                                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_mode')) ?></h3>
                            </div>

                            <div class="omo-calendar-create__grid">
                                <label class="omo-calendar-create__field">
                                    <span class="omo-calendar-create__label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_mode')) ?></span>
                                    <select name="location_mode" class="generic-form-control" data-omo-calendar-location-mode>
                                        <?php foreach ($locationModeOptions as $optionValue => $optionLabel): ?>
                                            <option value="<?= omoApiEscape((string)$optionValue) ?>"<?= (string)$optionValue === $locationModeDefault ? ' selected' : '' ?>>
                                                <?= omoApiEscape((string)$optionLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label class="omo-calendar-create__field" data-omo-calendar-location-address-field<?= in_array($locationModeDefault, [Event::LOCATION_MODE_IN_PERSON, Event::LOCATION_MODE_HYBRID], true) ? '' : ' hidden' ?>>
                                    <span class="omo-calendar-create__label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_address')) ?></span>
                                    <input
                                        type="text"
                                        name="location_address"
                                        class="generic-form-control"
                                        value="<?= omoApiEscape($locationAddressDefault) ?>"
                                        placeholder="<?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_address_placeholder')) ?>"
                                    >
                                </label>

                                <label class="omo-calendar-create__field" data-omo-calendar-location-video-field<?= in_array($locationModeDefault, [Event::LOCATION_MODE_VIRTUAL, Event::LOCATION_MODE_HYBRID], true) ? '' : ' hidden' ?>>
                                    <span class="omo-calendar-create__label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.video_url')) ?></span>
                                    <input
                                        type="url"
                                        name="video_meeting_url"
                                        class="generic-form-control"
                                        value="<?= omoApiEscape($videoMeetingUrlDefault) ?>"
                                        placeholder="<?= omoApiEscape(omoCalendarCreateT('calendar.create.field.video_url_placeholder')) ?>"
                                    >
                                </label>
                            </div>
                        </section>

                        <section class="generic-soft-panel generic-soft-panel--stack omo-calendar-create__block" data-omo-calendar-document-block>
                            <div class="omo-calendar-create__block-head">
                                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.document_type')) ?></h3>
                                <?php if ($associatedDocument instanceof Document): ?>
                                    <span class="omo-calendar-create__pill"><?= omoApiEscape($associatedDocument->getDocumentTypeLabel()) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($associatedDocument instanceof Document): ?>
                                <input type="hidden" name="document_type" value="<?= omoApiEscape($documentTypeDefault) ?>" data-omo-calendar-document-type>
                                <div data-omo-calendar-document-fields>
                                    <div class="omo-calendar-create__document-summary">
                                        <span class="omo-calendar-create__label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.current')) ?></span>
                                        <strong class="omo-calendar-create__document-title"><?= omoApiEscape(trim((string)$associatedDocument->get('title')) !== '' ? trim((string)$associatedDocument->get('title')) : omoCalendarCreateT('calendar.create.document.empty_title')) ?></strong>
                                        <p class="omo-calendar-create__hint"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.help_existing')) ?></p>
                                        <?php if ($associatedDocumentUrl !== ''): ?>
                                            <button
                                                type="button"
                                                class="generic-action-button generic-action-button--secondary"
                                                data-omo-calendar-open-url="<?= omoApiEscape($associatedDocumentUrl) ?>"
                                                data-omo-calendar-open-url-title="<?= omoApiEscape(trim((string)$associatedDocument->get('title')) !== '' ? trim((string)$associatedDocument->get('title')) : omoCalendarCreateT('calendar.create.document.empty_title')) ?>"
                                                data-omo-calendar-open-pv-editor-url="<?= omoApiEscape($associatedDocumentPvPreparationUrl) ?>"
                                            ><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.open')) ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <label class="omo-calendar-create__field">
                                    <span class="omo-calendar-create__label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.document_type')) ?></span>
                                    <select name="document_type" class="generic-form-control" data-omo-calendar-document-type>
                                        <?php foreach ($documentTypeOptions as $optionValue => $optionLabel): ?>
                                            <option value="<?= omoApiEscape((string)$optionValue) ?>"<?= (string)$optionValue === $documentTypeDefault ? ' selected' : '' ?>>
                                                <?= omoApiEscape((string)$optionLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <div data-omo-calendar-document-fields<?= $documentTypeDefault !== '' ? '' : ' hidden' ?>>
                                    <label class="omo-calendar-create__field">
                                        <span class="omo-calendar-create__label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.document_title')) ?></span>
                                        <input
                                            type="text"
                                            name="document_title"
                                            class="generic-form-control"
                                            value="<?= omoApiEscape($documentTitleDefault) ?>"
                                            maxlength="255"
                                        >
                                    </label>
                                    <p class="omo-calendar-create__hint"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.help_create')) ?></p>
                                    <p class="omo-calendar-create__notice"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.created_notice')) ?></p>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <div id="omoCalendarCreateTabInvites" class="generic-tabs__panel omo-calendar-create__tab-panel" data-generic-tab-panel hidden>
                        <?= omoCalendarRenderInvitationEditor($invitationEditorState, $lang, $sourceLang, 'omoApiEscape', [
                            'instanceId' => 'omoCalendarCreateInvitations',
                            'holonFieldName' => 'invitation_holon_ids[]',
                            'userFieldName' => 'invitation_user_ids[]',
                            'emailFieldName' => 'invitation_emails',
                            'showFooterHint' => true,
                        ]) ?>
                    </div>
                </div>
            </div>

            <div class="omo-calendar-create__footer">
                <div class="omo-calendar-create__feedback" data-omo-calendar-create-feedback></div>
                <button type="submit" class="generic-action-button generic-action-button--main" data-omo-calendar-create-submit>
                    <?= omoApiEscape(omoCalendarCreateT($isEditMode ? 'calendar.edit.submit' : 'calendar.create.submit')) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.omo-calendar-create {
    padding: 18px;
}

.omo-calendar-create__shell,
.omo-calendar-create__form,
.omo-calendar-create__field,
.omo-calendar-create__head,
.omo-calendar-create__block {
    display: grid;
    gap: 10px;
}

.omo-calendar-create [hidden] {
    display: none !important;
}

.omo-calendar-create__text {
    margin: 0;
    color: var(--color-text-light, #64748b);
    line-height: 1.6;
}

.omo-calendar-create__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.omo-calendar-create__tabs {
    --generic-tabs-panel-padding-block: 14px;
    --generic-tabs-panel-padding-inline: 14px;
}

.omo-calendar-create__tab-panel {
    display: grid;
    gap: 14px;
}

.omo-calendar-create__block-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.omo-calendar-create__label {
    color: var(--color-text-light, #64748b);
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.omo-calendar-create__hint {
    margin: 0;
    color: var(--color-text-light, #64748b);
    line-height: 1.6;
}

.omo-calendar-create__pill {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary, #2563eb) 10%, var(--color-surface, #ffffff));
    color: var(--color-text, #1f2937);
    font-size: 0.85rem;
    font-weight: 700;
}

.omo-calendar-create__document-summary {
    display: grid;
    gap: 8px;
}

.omo-calendar-create__document-title {
    color: var(--color-text, #1f2937);
    font-size: 1rem;
}

.omo-calendar-create__check {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--color-text, #1f2937);
    font-weight: 600;
}

.omo-calendar-create__notice {
    margin: 0;
    color: var(--color-text-light, #64748b);
    font-size: 0.92rem;
}

.omo-calendar-create__footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
}

.omo-calendar-create__feedback {
    min-height: 20px;
    color: var(--color-text-light, #64748b);
}

.omo-calendar-create__feedback.is-error {
    color: var(--color-danger, #b42318);
}

.omo-calendar-invitations-editor {
    display: grid;
    gap: 12px;
}

.omo-calendar-invitations-editor [hidden] {
    display: none !important;
}

.omo-calendar-invitations-editor__tabs {
    --generic-tabs-panel-padding-block: 14px;
    --generic-tabs-panel-padding-inline: 14px;
}

.omo-calendar-invitations-editor__tab-panel,
.omo-calendar-invitations-editor__checklist,
.omo-calendar-invitations-editor__member-list,
.omo-calendar-invitations-editor__check-meta,
.omo-calendar-invitations-editor__tree-node,
.omo-calendar-invitations-editor__tree-children {
    display: grid;
    gap: 8px;
}

.omo-calendar-invitations-editor__hint {
    margin: 0;
    color: var(--color-text-light, #64748b);
    line-height: 1.6;
}

.omo-calendar-invitations-editor__filter {
    width: 100%;
}

.omo-calendar-invitations-editor__empty {
    margin: 0;
    color: var(--color-text-light, #64748b);
    font-style: italic;
}

.omo-calendar-invitations-editor__tree-row,
.omo-calendar-invitations-editor__check {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.omo-calendar-invitations-editor__tree-toggle,
.omo-calendar-invitations-editor__tree-spacer {
    width: 28px;
    min-width: 28px;
    height: 28px;
    margin-top: 2px;
}

.omo-calendar-invitations-editor__tree-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.12);
    color: inherit;
    cursor: pointer;
}

.omo-calendar-invitations-editor__tree-toggle span {
    display: inline-block;
    transition: transform 0.18s ease;
}

.omo-calendar-invitations-editor__tree-toggle[aria-expanded="false"] span {
    transform: rotate(-90deg);
}

.omo-calendar-invitations-editor__tree-children {
    margin-left: 18px;
    padding-left: 14px;
    border-left: 1px solid var(--topbar-panel-border, #dbe3ef);
}

.omo-calendar-invitations-editor__check-type,
.omo-calendar-invitations-editor__member-email {
    color: var(--color-text-light, #64748b);
    font-size: 0.9rem;
}

.omo-calendar-invitations-editor__textarea {
    min-height: 120px;
}

@media (max-width: 720px) {
    .omo-calendar-create {
        padding: 14px;
    }

    .omo-calendar-create__grid {
        grid-template-columns: 1fr;
    }

    .omo-calendar-create__footer {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
