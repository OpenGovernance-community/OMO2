<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/invitations_shared.php';
require_once __DIR__ . '/permissions_shared.php';
require_once dirname(__DIR__, 3) . '/common/etherpad.php';
require_once dirname(__DIR__, 3) . '/common/ethercalc.php';
require_once dirname(__DIR__, 3) . '/common/notification_center.php';
require_once dirname(__DIR__, 3) . '/common/external_calendar.php';

use dbObject\ArrayHolon;
use dbObject\Document;
use dbObject\Event;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\Project;

$sourceLang = array_merge([
    'calendar.availability.warning' => ['text' => 'Un point sur les disponibilites', 'context' => 'Heading of the event availability review.'],
    'calendar.availability.waiting' => ['text' => 'Verification des disponibilites...', 'context' => 'Animated progress indicator while checking and refreshing invitee calendars.'],
    'calendar.availability.waiting_hint' => ['text' => 'Les agendas sont actualises si necessaire.', 'context' => 'Explanation while refreshing calendars before saving an event.'],
    'calendar.availability.conflict_label' => ['text' => 'Conflit', 'context' => 'Compact label after a warning icon at the beginning of a conflicting appointment row.'],
    'calendar.availability.unknown' => ['text' => 'A verifier', 'context' => 'Label on an invitee whose availability could not be verified.'],
    'calendar.availability.adjust' => ['text' => 'Modifier les horaires', 'context' => 'Return to the event schedule after reviewing availability.'],
    'calendar.availability.conflict' => ['text' => '{name} - {context} : du {start} au {end}', 'context' => 'Conflicting appointment with its organization and circle or role, and full time range; no event title.'],
    'calendar.availability.external' => ['text' => 'Agenda externe', 'context' => 'Generic source label for a conflicting external appointment.'],
    'calendar.availability.omo' => ['text' => 'Agenda OMO', 'context' => 'Fallback source label when an appointment organization has no name.'],
    'calendar.availability.email' => ['text' => '{name} : agenda non accessible pour cette invitation par e-mail.', 'context' => 'Availability cannot be checked for an email-only invitee.'],
    'calendar.availability.cache' => ['text' => '{name} : l agenda n a pas pu etre actualise ou ne couvre pas cette periode.', 'context' => 'Partial external calendar availability check after a refresh attempt.'],
    'calendar.availability.detail.email' => ['text' => 'Cette invitation par e-mail ne donne pas acces a un agenda.', 'context' => 'Availability card for an email-only invitation.'],
    'calendar.availability.detail.cache' => ['text' => 'L agenda n a pas pu etre actualise ou ne couvre pas cette periode.', 'context' => 'Availability card when refreshing is unsuccessful or coverage is incomplete.'],
    'calendar.availability.detail.storage' => ['text' => 'La verification est momentanement indisponible.', 'context' => 'Availability card when storage cannot be checked.'],
    'calendar.availability.storage' => ['text' => '{name} : verification indisponible pour le moment.', 'context' => 'Availability storage failure; never imply the guest is free.'],
    'calendar.availability.note' => ['text' => 'Vous pouvez ajuster les horaires ou conserver ce rendez-vous.', 'context' => 'Options after an availability warning.'],
    'calendar.availability.confirm' => ['text' => 'Enregistrer quand meme', 'context' => 'Explicit override of an event availability warning.'],
    'calendar.create.title' => [
        'text' => 'Nouvel événement',
        'context' => 'Title shown at the top of the event creation form.',
    ],
    'calendar.edit.title' => [
        'text' => "Modifier l'événement",
        'context' => 'Title shown at the top of the event edition form.',
    ],
    'calendar.create.description' => [
        'text' => 'Planifiez une date, un horaire, un lieu et un document associé, si besoin.',
        'context' => 'Intro text shown in the event creation form.',
    ],
    'calendar.edit.description' => [
        'text' => "Mettez à jour la date, l'horaire, le lieu et le document associé.",
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
    'calendar.create.field.status' => [
        'text' => 'Statut',
        'context' => 'Label of the event visibility and planning status field.',
    ],
    'calendar.create.status.draft' => [
        'text' => 'Brouillon',
        'context' => 'Event status visible only to its creator.',
    ],
    'calendar.create.status.option' => [
        'text' => 'Option',
        'context' => 'Event status for a possible date that is not confirmed yet.',
    ],
    'calendar.create.status.confirmed' => [
        'text' => 'Confirmé',
        'context' => 'Event status for a confirmed date.',
    ],
    'calendar.create.field.start' => [
        'text' => 'Début',
        'context' => 'Label of the event start date time field.',
    ],
    'calendar.create.field.end' => [
        'text' => 'Fin',
        'context' => 'Label of the event end date time field.',
    ],
    'calendar.create.field.holon' => [
        'text' => 'Espace associé',
        'context' => 'Label of the optional space association field.',
    ],
    'calendar.create.field.all_day' => [
        'text' => 'Journée entière',
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
        'text' => 'À définir',
        'context' => 'Fallback option when the event location is not specified yet.',
    ],
    'calendar.create.field.location_address' => [
        'text' => 'Adresse',
        'context' => 'Label of the physical address field.',
    ],
    'calendar.create.field.location_address_placeholder' => [
        'text' => 'Rue, numéro, NPA, localité',
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
        'text' => 'Document associé',
        'context' => 'Label of the linked document type field.',
    ],
    'calendar.create.tab.event' => [
        'text' => 'Événement',
        'context' => 'First tab label in the event creation form.',
    ],
    'calendar.create.tab.invites' => [
        'text' => 'Invités',
        'context' => 'Second tab label in the event creation form for invitation settings.',
    ],
    'calendar.create.tabs_aria' => [
        'text' => "Configuration de l'événement",
        'context' => 'Accessible label of the tabs used in the event creation form.',
    ],
    'calendar.create.field.document_title' => [
        'text' => 'Nom du document',
        'context' => 'Optional label of the linked document title field.',
    ],
    'calendar.create.field.pv_template' => [
        'text' => 'Modèle de PV',
        'context' => 'Label of the optional PV template selector in event creation.',
    ],
    'calendar.create.field.pv_template_none' => [
        'text' => 'PV vide',
        'context' => 'Empty option of the PV template selector in event creation.',
    ],
    'calendar.create.field.pv_template_hint' => [
        'text' => 'Les groupes, points et contenus du modèle seront copiés sans leurs auteurs ni leurs invités.',
        'context' => 'Help text below the PV template selector in event creation.',
    ],
    'calendar.create.document.help_create' => [
        'text' => "Si vous choisissez un type, un document vide sera créé automatiquement avec le titre de l'événement, sa description et des tags par défaut. Vous pourrez ensuite le modifier depuis le module Documents.",
        'context' => 'Help text shown when the user chooses a linked document type from the event form.',
    ],
    'calendar.create.document.help_existing' => [
        'text' => 'Le document lié reste modifiable depuis le module Documents.',
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
        'text' => 'Le document sera créé vide avec ses métadonnées par défaut.',
        'context' => 'Notice shown below the document type selector before creating the linked document.',
    ],
    'calendar.create.document.current' => [
        'text' => 'Document actuel',
        'context' => 'Label shown above the existing linked document summary.',
    ],
    'calendar.create.document.no_permission' => [
        'text' => 'Vous ne disposez pas du droit de créer un document dans ce contexte.',
        'context' => 'Notice shown when the current user can edit an event but cannot create a linked document in its context.',
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
        'text' => "Créer l'événement",
        'context' => 'Submit button label of the event creation form.',
    ],
    'calendar.edit.submit' => [
        'text' => 'Enregistrer',
        'context' => 'Submit button label of the event edition form.',
    ],
    'calendar.edit.cancel' => [
        'text' => 'Annuler',
        'context' => 'Button returning from the event edition form to the event detail without saving.',
    ],
    'calendar.create.success' => [
        'text' => 'Événement créé.',
        'context' => 'Success message returned after an event is created.',
    ],
    'calendar.edit.success' => [
        'text' => 'Événement mis à jour.',
        'context' => 'Success message returned after an event is updated.',
    ],
    'calendar.create.error.title' => [
        'text' => 'Le titre est obligatoire.',
        'context' => 'Validation error returned when the title is missing.',
    ],
    'calendar.create.error.start' => [
        'text' => 'La date de début est invalide.',
        'context' => 'Validation error returned when the start date is invalid.',
    ],
    'calendar.create.error.end' => [
        'text' => 'La date de fin est invalide.',
        'context' => 'Validation error returned when the end date is invalid.',
    ],
    'calendar.create.error.status' => [
        'text' => 'Le statut choisi est invalide.',
        'context' => 'Validation error returned when the event status is invalid.',
    ],
    'calendar.create.error.holon' => [
        'text' => 'Le contexte choisi est invalide.',
        'context' => 'Validation error returned when the selected holon is not allowed.',
    ],
    'calendar.create.error.project' => [
        'text' => 'Le projet associé est invalide ou inaccessible.',
        'context' => 'Validation error returned when an event is created for an invalid project.',
    ],
    'calendar.create.error.duplicate' => [
        'text' => "Impossible de préparer la duplication de cet événement.",
        'context' => 'Validation error returned when an event duplication source cannot be used.',
    ],
    'calendar.create.error.document_type' => [
        'text' => 'Le type de document associé est invalide.',
        'context' => 'Validation error returned when the selected linked document type is invalid.',
    ],
    'calendar.create.error.document_permission' => [
        'text' => 'Vous ne pouvez pas créer de document dans ce contexte.',
        'context' => 'Validation error returned when the linked document cannot be created in the selected context.',
    ],
    'calendar.create.error.save' => [
        'text' => "Impossible d'enregistrer cet événement.",
        'context' => 'Generic error returned when the event could not be saved.',
    ],
    'calendar.edit.error.forbidden' => [
        'text' => "Vous ne pouvez pas modifier cet événement.",
        'context' => 'Error returned when the current user cannot edit the requested event.',
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

function omoCalendarDocumentTypeOptions(bool $nextcloudDocumentsAvailable, bool $etherpadDocumentsAvailable = false, bool $ethercalcDocumentsAvailable = false, bool $pvDocumentsEnabled = true): array
{
    $options = [
        '' => omoCalendarCreateT('calendar.create.document.none'),
        Document::TYPE_HTML => (string)Document::getDocumentTypeCatalog()[Document::TYPE_HTML],
        Document::TYPE_EXTERNAL_LINK => (string)Document::getDocumentTypeCatalog()[Document::TYPE_EXTERNAL_LINK],
        Document::TYPE_ETHERPAD => (string)Document::getDocumentTypeCatalog()[Document::TYPE_ETHERPAD],
        Document::TYPE_ETHERCALC => (string)Document::getDocumentTypeCatalog()[Document::TYPE_ETHERCALC],
    ];

    if ($pvDocumentsEnabled) {
        $options[Document::TYPE_PV] = (string)Document::getDocumentTypeCatalog()[Document::TYPE_PV];
    }

    if ($nextcloudDocumentsAvailable) {
        $options[Document::TYPE_UPLOADED_FILE] = (string)Document::getDocumentTypeCatalog()[Document::TYPE_UPLOADED_FILE];
    }

    if (!$etherpadDocumentsAvailable) {
        unset($options[Document::TYPE_ETHERPAD]);
    }

    if (!$ethercalcDocumentsAvailable) {
        unset($options[Document::TYPE_ETHERCALC]);
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
                'eventTitle' => $normalizedEventTitle !== '' ? $normalizedEventTitle : 'Événement',
                'eventDate' => $startAt->format('d.m.Y H:i'),
            ]);
        } elseif ($normalizedEventTitle !== '') {
            $normalizedTitle = $normalizedEventTitle;
        } else {
            $normalizedTitle = 'Événement du ' . $startAt->format('d.m.Y H:i');
        }
    }

    $description = trim($eventDescription);
    if ($description === '') {
        if ($normalizedEventTitle !== '') {
            $description = "Document associé à l'événement \"" . $normalizedEventTitle . '\".';
        } else {
            $description = "Document associé à l'événement du " . $startAt->format('d.m.Y H:i') . '.';
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
$duplicateEventId = isset($_REQUEST['duplicate_id']) && is_numeric($_REQUEST['duplicate_id']) ? (int)$_REQUEST['duplicate_id'] : 0;
$requestedProjectId = isset($_REQUEST['project_id']) && is_numeric($_REQUEST['project_id']) ? (int)$_REQUEST['project_id'] : 0;
$editorHost = trim((string)($_REQUEST['editor_host'] ?? '')) === 'project' ? 'project' : 'calendar';

if ($organizationId <= 0 || $currentUserId <= 0) {
    http_response_code(403);
    if (commonIsAjaxJsonRequest()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => false,
            'message' => 'Accès refusé.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Accès refusé.</div>';
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

$hasStructureApplication = $organization->isStructureApplicationEnabled($currentUserId);
$rootHolon = $hasStructureApplication ? $organization->getEnabledStructuralRootHolon($currentUserId) : null;
$nextcloudDocumentsAvailable = $organization->hasDocumentStorage();
$pvDocumentsEnabled = $organization->isPvDocumentEnabled();
$project = null;

if ($requestedProjectId > 0) {
    $candidateProject = new Project();
    $candidateProjectHolon = null;
    $projectIsValid = $candidateProject->load($requestedProjectId)
        && (int)$candidateProject->get('IDorganization') === $organizationId
        && (int)$candidateProject->get('active') === 1;

    if ($projectIsValid) {
        $candidateProjectHolon = $candidateProject->getHolon();
        if ($candidateProjectHolon instanceof Holon) {
            $projectIsValid = $rootHolon instanceof Holon
                && $candidateProjectHolon->isDescendantOf((int)$rootHolon->getId(), true)
                && $candidateProjectHolon->canViewDetail();
        }
    }

    if (!$projectIsValid) {
        http_response_code(403);
        if (commonIsAjaxJsonRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => false,
                'message' => omoCalendarCreateT('calendar.create.error.project'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo '<div class="omo-empty-state">' . omoApiEscape(omoCalendarCreateT('calendar.create.error.project')) . '</div>';
        }
        exit;
    }

    $project = $candidateProject;
    $currentHolonId = $candidateProjectHolon instanceof Holon ? (int)$candidateProjectHolon->getId() : 0;
}

$etherpadDocumentsAvailable = omoEtherpadCanUseEditingSessions($organization);
$ethercalcDocumentsAvailable = omoEthercalcHasConfig();

$event = new Event();
$isEditMode = false;
$duplicateEvent = null;

if ($eventId > 0) {
    if (
        !$event->load($eventId)
        || (int)$event->get('IDorganization') !== $organizationId
        || !$event->isDraftVisibleToViewer($currentUserId)
    ) {
        http_response_code(403);
        if (commonIsAjaxJsonRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => false,
                'message' => 'Événement invalide.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo '<div class="omo-empty-state">Événement invalide.</div>';
        }
        exit;
    }

    if (!omoCalendarCanEditEvent($event, $organizationId, $currentUserId, $rootHolon, false)) {
        http_response_code(403);
        if (commonIsAjaxJsonRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => false,
                'message' => omoCalendarCreateT('calendar.edit.error.forbidden'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo '<div class="omo-empty-state">' . omoApiEscape(omoCalendarCreateT('calendar.edit.error.forbidden')) . '</div>';
        }
        exit;
    }

    $isEditMode = true;
}

$isDuplicateMode = !$isEditMode && $duplicateEventId > 0;
if ($isDuplicateMode) {
    $candidateDuplicateEvent = new Event();
    $duplicateIsValid = $candidateDuplicateEvent->load($duplicateEventId)
        && (int)$candidateDuplicateEvent->get('IDorganization') === $organizationId
        && (int)$candidateDuplicateEvent->get('active') === 1
        && $candidateDuplicateEvent->isDraftVisibleToViewer($currentUserId);

    if ($duplicateIsValid && $project instanceof Project) {
        $duplicateIsValid = (int)$candidateDuplicateEvent->get('IDproject') === (int)$project->getId();
    }

    if (!$duplicateIsValid) {
        http_response_code(403);
        if (commonIsAjaxJsonRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'status' => false,
                'message' => omoCalendarCreateT('calendar.create.error.duplicate'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo '<div class="omo-empty-state">' . omoApiEscape(omoCalendarCreateT('calendar.create.error.duplicate')) . '</div>';
        }
        exit;
    }

    $duplicateEvent = $candidateDuplicateEvent;
}

$associatedDocument = $isEditMode ? $event->getAssociatedDocument() : null;

$holons = new ArrayHolon();
$holonOptions = [];
if ($hasStructureApplication) {
    $holons->loadVisibilityTargetsForOrganization($organizationId, [2, 1]);
    $holonOptions = $holons->buildVisibilityTargetOptions();
}
$allowedHolonIds = [];
$holonContextPaths = [];

foreach (['circle', 'role'] as $typeKey) {
    foreach (($holonOptions[$typeKey] ?? []) as $option) {
        $holonId = (int)($option['id'] ?? 0);
        $allowedHolonIds[$holonId] = $option;
        $holon = new Holon();
        if ($holonId > 0 && $holon->load($holonId)) {
            $holonContextPaths[$holonId] = implode(',', array_map(static function ($pathHolon): int {
                return (int)$pathHolon->getId();
            }, $holon->getPathHolons(true)));
        }
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
            'message' => 'Accès refusé.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        echo '<div class="omo-empty-state">Accès refusé.</div>';
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

$pvTemplatesPayload = [];
if (!$isEditMode || !($associatedDocument instanceof Document)) {
    $pvTemplates = new \dbObject\ArrayDocument();
    $pvTemplates->loadPvTemplatesForOrganization($organizationId);
    foreach ($pvTemplates as $pvTemplate) {
        if (!($pvTemplate instanceof Document) || (int)$pvTemplate->getId() <= 0) {
            continue;
        }

        $visibilityRule = $pvTemplate->getPrimaryVisibilityRuleRow();
        $visibilityType = \dbObject\ObjectVisibility::normalizeVisibilityType(
            (string)($visibilityRule['visibility_type'] ?? \dbObject\ObjectVisibility::TYPE_ORGANIZATION)
        );
        if (!in_array($visibilityType, [
            \dbObject\ObjectVisibility::TYPE_EVERYONE,
            \dbObject\ObjectVisibility::TYPE_ORGANIZATION,
            \dbObject\ObjectVisibility::TYPE_CIRCLE,
            \dbObject\ObjectVisibility::TYPE_ROLE,
        ], true)) {
            continue;
        }

        $templateLabel = trim((string)$pvTemplate->get('title'));
        $templateParent = $pvTemplate->getParentDocument();
        if ($templateParent instanceof Document && trim((string)$templateParent->get('title')) !== '') {
            $templateLabel = trim((string)$templateParent->get('title')) . ' / ' . $templateLabel;
        }
        $pvTemplatesPayload[] = [
            'id' => (int)$pvTemplate->getId(),
            'label' => $templateLabel !== '' ? $templateLabel : ('PV #' . (int)$pvTemplate->getId()),
            'visibilityType' => $visibilityType,
            'targetHolonId' => (int)($visibilityRule['IDholon'] ?? 0),
        ];
    }
}

$prefillEvent = $isEditMode ? $event : $duplicateEvent;
$defaultInvitationHolonId = $project instanceof Project ? 0 : $defaultHolonId;
$defaultInvitationUserId = !$isEditMode && $project instanceof Project ? (int)$project->get('IDuser') : 0;
$invitationEditorState = omoCalendarBuildInvitationEditorState(
    $prefillEvent,
    $organization,
    $organizationId,
    $currentContextHolon,
    $defaultHolonId > 0 ? $defaultHolonId : $currentHolonId,
    $defaultInvitationHolonId,
    true,
    $defaultInvitationUserId
);

$documentCreationHolonId = $defaultHolonId > 0
    ? $defaultHolonId
    : ($currentHolonId > 0 && isset($allowedHolonIds[$currentHolonId]) ? $currentHolonId : 0);
$canCreateLinkedDocument = Document::canCreateInOrganizationContext(
    $organizationId,
    $documentCreationHolonId > 0 ? $documentCreationHolonId : null,
    $currentUserId,
    0,
    false
);
$editableEventStatuses = [
    Event::STATUS_DRAFT => omoCalendarCreateT('calendar.create.status.draft'),
    Event::STATUS_OPTION => omoCalendarCreateT('calendar.create.status.option'),
    Event::STATUS_CONFIRMED => omoCalendarCreateT('calendar.create.status.confirmed'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');

    $previousLocation = $isEditMode ? [
        'mode' => trim((string)$event->get('locationmode')),
        'address' => trim((string)$event->get('locationaddress')),
        'video' => trim((string)$event->get('videomeetingurl')),
    ] : null;
    $previousSchedule = $isEditMode ? [
        'start' => $event->get('start_at') instanceof \DateTimeInterface ? $event->get('start_at')->format('Y-m-d H:i:s') : '',
        'end' => $event->get('end_at') instanceof \DateTimeInterface ? $event->get('end_at')->format('Y-m-d H:i:s') : '',
        'allDay' => !empty($event->get('is_all_day')) ? 1 : 0,
    ] : null;

    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $status = trim((string)($_POST['status'] ?? Event::STATUS_CONFIRMED));
    $selectedHolonId = $hasStructureApplication && isset($_POST['IDholon']) ? (int)$_POST['IDholon'] : 0;
    if ($project instanceof Project) {
        $projectHolon = $project->getHolon();
        $selectedHolonId = $projectHolon instanceof Holon ? (int)$projectHolon->getId() : 0;
    }
    $startAt = omoCalendarParseLocalDateTime($_POST['start_at'] ?? '');
    $endAt = omoCalendarParseLocalDateTime($_POST['end_at'] ?? '');
    $isAllDay = !empty($_POST['is_all_day']);
    $locationMode = Event::normalizeLocationMode($_POST['location_mode'] ?? '');
    $locationAddress = trim((string)($_POST['location_address'] ?? ''));
    $videoMeetingUrl = Event::sanitizeVideoMeetingUrl($_POST['video_meeting_url'] ?? '');

    $requestedDocumentType = trim((string)($_POST['document_type'] ?? ''));
    $documentTitle = trim((string)($_POST['document_title'] ?? ''));
    $pvTemplateId = isset($_POST['pv_template_id']) ? max(0, (int)$_POST['pv_template_id']) : 0;
    $selectedInvitationHolonIds = $hasStructureApplication ? array_values(array_unique(array_filter(array_map('intval', $_POST['invitation_holon_ids'] ?? []), static function ($holonId) {
        return $holonId > 0;
    }))) : [];
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

    if (!array_key_exists($status, $editableEventStatuses)) {
        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.status'),
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

    if ($isEditMode) {
        $targetEditPermissionHolon = $rootHolon;
        if ($selectedHolonId > 0) {
            $targetEditPermissionHolon = new Holon();
            if (!$targetEditPermissionHolon->load($selectedHolonId)) {
                $targetEditPermissionHolon = null;
            }
        }

        if (
            !($targetEditPermissionHolon instanceof Holon)
            || !omoCalendarCanUseEditEventPermission($targetEditPermissionHolon, $organizationId, $currentUserId, false)
        ) {
            http_response_code(403);
            echo json_encode([
                'status' => false,
                'message' => omoCalendarCreateT('calendar.edit.error.forbidden'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
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
                'message' => 'Accès refusé.',
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
        $documentOptions = omoCalendarDocumentTypeOptions($nextcloudDocumentsAvailable, $etherpadDocumentsAvailable, $ethercalcDocumentsAvailable, $pvDocumentsEnabled);
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
    if ($project instanceof Project) {
        $event->set('IDproject', (int)$project->getId());
    }
    $event->set('title', $title);
    $event->set('description', $description !== '' ? $description : null);
    $event->set('status', $status);
    $event->set('timezone', date_default_timezone_get());
    $event->set('locationmode', $locationMode !== '' ? $locationMode : null);
    $event->set('locationaddress', $locationAddress !== '' ? $locationAddress : null);
    $event->set('videomeetingurl', $videoMeetingUrl !== '' ? $videoMeetingUrl : null);
    $event->set('start_at', $startAt);
    $event->set('end_at', $endAt);
    $event->set('is_all_day', $isAllDay ? 1 : 0);

    // Validate participants before refreshing calendars or creating any event/document.
    $selection = omoCalendarPrepareInvitationSelections($organization, $organizationId, $selectedInvitationHolonIds, $selectedInvitationUserIds, $selectedInvitationEmails);
    if (!$selection['status']) {
        echo json_encode($selection, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    $proposedInvitations = [];
    foreach ($selection['invitations'] as $values) {
        $invitation = new \dbObject\EventInvitation();
        foreach ($values as $field => $value) { $invitation->set($field, $value); }
        $invitation->set('active', 1);
        $invitation->set('status', \dbObject\EventInvitation::STATUS_INVITED);
        $proposedInvitations[] = $invitation;
    }
    $refreshDeadline = microtime(true) + 18;
    // An explicit override rechecks conflicts but does not retry an unreachable server.
    $refreshCalendars = empty($_POST['availability_ack'])
        ? static fn(int $userId) => commonExternalCalendarRefreshForAvailability($userId, $refreshDeadline)
        : null;
    $availability = $event->checkInvitationAvailability($proposedInvitations, $refreshCalendars);
    if ($availability['conflicts'] || $availability['unverified']) {
        // Bind acknowledgement to this session, schedule, participants and current conflicts.
        $_SESSION['calendar_availability_secret'] ??= bin2hex(random_bytes(32));
        $acknowledgement = hash_hmac('sha256', json_encode([
            $organizationId, $event->getId(), $event->get('IDuser'), $selectedHolonId,
            $startAt->format('c'), $endAt->format('c'), $isAllDay, $selection['invitations'], $availability,
        ]), $_SESSION['calendar_availability_secret']);
        if (!hash_equals($acknowledgement, (string)($_POST['availability_ack'] ?? ''))) {
            $messages = [];
            $items = [];
            foreach ($availability['conflicts'] as $conflict) {
                $conflict['context'] = $conflict['source'] === 'external'
                    ? omoCalendarCreateT('calendar.availability.external')
                    : (implode(' - ', array_filter([$conflict['organization'], $conflict['holon']], static fn($label) => trim($label) !== ''))
                        ?: omoCalendarCreateT('calendar.availability.omo'));
                $items[] = $conflict + ['kind' => 'conflict', 'label' => omoCalendarCreateT('calendar.availability.conflict_label')];
                $conflict['start'] = (new \DateTimeImmutable($conflict['start']))->format('d.m.Y H:i');
                $conflict['end'] = (new \DateTimeImmutable($conflict['end']))->format('d.m.Y H:i');
                $messages[] = omoCalendarCreateT('calendar.availability.conflict', $conflict);
            }
            foreach ($availability['unverified'] as $unknown) {
                $messages[] = omoCalendarCreateT('calendar.availability.' . $unknown['reason'], ['name' => $unknown['name']]);
                $items[] = ['name' => $unknown['name'], 'kind' => 'unknown',
                    'label' => omoCalendarCreateT('calendar.availability.unknown'),
                    'detail' => omoCalendarCreateT('calendar.availability.detail.' . $unknown['reason'])];
            }
            echo json_encode(['status' => false, 'message' => omoCalendarCreateT('calendar.availability.warning'),
                'availability' => ['items' => $items, 'messages' => $messages, 'acknowledgement' => $acknowledgement]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    $pdo = \dbObject\DbObject::getPdo();
    $startedTransaction = $pdo instanceof \PDO && !$pdo->inTransaction();
    $createdEtherpadPadId = '';
    $cleanupCreatedEtherpadPad = static function () use ($organization, &$createdEtherpadPadId): void {
        if ($createdEtherpadPadId === '') {
            return;
        }

        omoEtherpadDeleteDocumentPad($organization, $createdEtherpadPadId);
        $createdEtherpadPadId = '';
    };

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
                    'pv_template_id' => $pvTemplateId,
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
            if ($linkedDocument->isEtherpadDocument()) {
                $createdEtherpadPadId = trim((string)($documentCreateResult['etherpadPadId'] ?? $linkedDocument->getEtherpadPadId()));
            }
            $syncDocumentDateResult = $event->syncAssociatedDocumentEventDate();
            if (!is_array($syncDocumentDateResult) || empty($syncDocumentDateResult['status'])) {
                if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $cleanupCreatedEtherpadPad();

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
            $cleanupCreatedEtherpadPad();

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
        \dbObject\CalDavCache::invalidateOrganization($organizationId);
        $createdEtherpadPadId = '';
    } catch (\Throwable $exception) {
        if ($startedTransaction && $pdo instanceof \PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $cleanupCreatedEtherpadPad();

        echo json_encode([
            'status' => false,
            'message' => omoCalendarCreateT('calendar.create.error.save'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    try {
        $notificationEvent = new Event();
        if (
            $notificationEvent->load((int)$event->getId())
            && Event::normalizeStatus($notificationEvent->get('status')) !== Event::STATUS_DRAFT
        ) {
            if (!$isEditMode) {
                notificationCenterDispatchEventInvitation($notificationEvent, $currentUserId);
            } else {
                $currentLocation = [
                    'mode' => trim((string)$notificationEvent->get('locationmode')),
                    'address' => trim((string)$notificationEvent->get('locationaddress')),
                    'video' => trim((string)$notificationEvent->get('videomeetingurl')),
                ];
                $currentSchedule = [
                    'start' => $notificationEvent->get('start_at') instanceof \DateTimeInterface ? $notificationEvent->get('start_at')->format('Y-m-d H:i:s') : '',
                    'end' => $notificationEvent->get('end_at') instanceof \DateTimeInterface ? $notificationEvent->get('end_at')->format('Y-m-d H:i:s') : '',
                    'allDay' => !empty($notificationEvent->get('is_all_day')) ? 1 : 0,
                ];
                if ($previousLocation !== $currentLocation) {
                    notificationCenterDispatchEventChange($notificationEvent, 'location', $currentUserId);
                }
                if ($previousSchedule !== $currentSchedule) {
                    notificationCenterDispatchEventChange($notificationEvent, 'schedule', $currentUserId);
                }
            }
        }
    } catch (\Throwable $exception) {
        error_log('OMO calendar notification dispatch failed: ' . $exception->getMessage());
    }

    echo json_encode([
        'status' => true,
        'message' => omoCalendarCreateT($isEditMode ? 'calendar.edit.success' : 'calendar.create.success'),
        'eventId' => (int)$event->getId(),
        'projectId' => $project instanceof Project ? (int)$project->getId() : (int)$event->get('IDproject'),
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
    : ($isDuplicateMode ? null : ($initialStartDefault ?: $initialDateDefault ?: new \DateTime('today 09:00')));
$endDefault = $isEditMode
    ? $event->get('end_at')
    : ($isDuplicateMode ? null : (clone $startDefault)->modify('+1 hour'));
$titleDefault = $prefillEvent instanceof Event ? trim((string)$prefillEvent->get('title')) : '';
$descriptionDefault = $prefillEvent instanceof Event ? trim((string)$prefillEvent->get('description')) : '';
$statusDefault = $prefillEvent instanceof Event ? Event::normalizeStatus($prefillEvent->get('status')) : Event::STATUS_CONFIRMED;
if (!array_key_exists($statusDefault, $editableEventStatuses)) {
    $statusDefault = Event::STATUS_CONFIRMED;
}
$isAllDayDefault = $prefillEvent instanceof Event ? (bool)$prefillEvent->get('is_all_day') : false;
$locationDisplayData = $prefillEvent instanceof Event ? $prefillEvent->getLocationDisplayData() : ['mode' => '', 'address' => '', 'videoUrl' => ''];
$locationModeDefault = $locationDisplayData['mode'] !== ''
    ? (string)$locationDisplayData['mode']
    : '';
$locationAddressDefault = trim((string)($locationDisplayData['address'] ?? ''));
$videoMeetingUrlDefault = trim((string)($locationDisplayData['videoUrl'] ?? ''));

$duplicateAssociatedDocument = $isDuplicateMode && $duplicateEvent instanceof Event
    ? $duplicateEvent->getAssociatedDocument()
    : null;
$documentTypeDefault = $associatedDocument instanceof Document
    ? $associatedDocument->getDocumentType()
    : ($duplicateAssociatedDocument instanceof Document ? $duplicateAssociatedDocument->getDocumentType() : '');
$documentTitleDefault = $isDuplicateMode && $duplicateAssociatedDocument instanceof Document
    ? trim((string)$duplicateAssociatedDocument->get('title'))
    : '';
$documentTypeOptions = omoCalendarDocumentTypeOptions($nextcloudDocumentsAvailable, $etherpadDocumentsAvailable, $ethercalcDocumentsAvailable, $pvDocumentsEnabled);
$canOpenAssociatedDocument = $associatedDocument instanceof Document
    && (
        $associatedDocument->isPvDocument() && !$associatedDocument->isPvValidated()
            ? (
                $associatedDocument->canUserAccessPvBeforeValidation($currentUserId, $organizationId)
                || ($associatedDocument->getPvStage() === Document::PV_STAGE_REVIEW
                    && $associatedDocument->canUserViewPvReadOnly($currentUserId, $organizationId, $currentHolonId > 0 ? $currentHolonId : null))
            )
            : $associatedDocument->canViewDirectlyInOrganization($organizationId)
    );
$associatedDocumentUrl = $canOpenAssociatedDocument
    ? $event->buildAssociatedDocumentDetailUrl($currentHolonId > 0 ? $currentHolonId : $defaultHolonId)
    : '';
$associatedDocumentPvPreparationUrl = $associatedDocument instanceof Document
    && $associatedDocument->canUserOpenPvEditor($currentUserId, $organizationId)
    ? $associatedDocument->buildPvEditorUrl($organizationId)
    : '';
$locationModeOptions = array_merge(
    ['' => omoCalendarCreateT('calendar.create.field.location_mode_pending')],
    array_map(static function (array $definition): string {
        return (string)($definition['label'] ?? '');
    }, Event::getLocationModeCatalog())
);
$calendarFormId = 'omoCalendarCreateForm' . ucfirst($editorHost);
$drawerTitle = omoCalendarCreateT($isEditMode ? 'calendar.edit.title' : 'calendar.create.title');
$drawerDescription = omoCalendarCreateT($isEditMode ? 'calendar.edit.description' : 'calendar.create.description');
$drawerSubmitLabel = omoCalendarCreateT($isEditMode ? 'calendar.edit.submit' : 'calendar.create.submit');
$cancelDetailUrl = '';
if ($isEditMode) {
    $cancelDetailContextHolonId = $currentHolonId > 0 ? $currentHolonId : $defaultHolonId;
    $cancelDetailUrl = '/omo/api/calendar/detail.php?oid=' . rawurlencode((string)$organizationId)
        . ($cancelDetailContextHolonId > 0 ? '&cid=' . rawurlencode((string)$cancelDetailContextHolonId) : '')
        . '&id=' . rawurlencode((string)(int)$event->getId());
}
?>
<div class="omo-calendar-create" data-omo-calendar-editor-host="<?= omoApiEscape($editorHost) ?>">
    <div
        hidden
        data-omo-calendar-drawer-header
        data-omo-calendar-drawer-title="<?= omoApiEscape($drawerTitle) ?>"
        data-omo-calendar-drawer-description="<?= omoApiEscape($drawerDescription) ?>"
        data-omo-subdrawer-header
        data-omo-subdrawer-title="<?= omoApiEscape($drawerTitle) ?>"
        data-omo-subdrawer-description="<?= omoApiEscape($drawerDescription) ?>"
    >
        <?php if ($cancelDetailUrl !== ''): ?>
            <button
                type="button"
                class="generic-action-button generic-action-button--secondary"
                data-omo-calendar-drawer-action
                data-omo-calendar-open-detail-url="<?= omoApiEscape($cancelDetailUrl) ?>"
            ><?= omoApiEscape(omoCalendarCreateT('calendar.edit.cancel')) ?></button>
        <?php endif; ?>
        <button
            type="submit"
            form="<?= omoApiEscape($calendarFormId) ?>"
            class="generic-action-button generic-action-button--main"
            data-omo-calendar-drawer-action
            data-omo-subdrawer-action
            data-omo-calendar-create-submit
        ><?= omoApiEscape($drawerSubmitLabel) ?></button>
    </div>

    <div class="omo-calendar-create__shell generic-form-stack">
        <form
            id="<?= omoApiEscape($calendarFormId) ?>"
            class="omo-calendar-create__form generic-form-stack"
            method="post"
            action="/omo/api/calendar/create.php?oid=<?= (int)$organizationId ?><?= $currentHolonId > 0 ? '&cid=' . (int)$currentHolonId : '' ?><?= $isEditMode ? '&id=' . (int)$event->getId() : '' ?><?= $project instanceof Project ? '&project_id=' . (int)$project->getId() . '&editor_host=project' : '' ?>"
            data-omo-calendar-create-form
            data-omo-calendar-editor-host="<?= omoApiEscape($editorHost) ?>"
        >
            <?php if ($isEditMode): ?>
                <input type="hidden" name="id" value="<?= (int)$event->getId() ?>">
            <?php endif; ?>
            <?php if ($project instanceof Project): ?>
                <input type="hidden" name="project_id" value="<?= (int)$project->getId() ?>">
                <input type="hidden" name="editor_host" value="project">
            <?php endif; ?>

            <div class="generic-tabs generic-tabs--embedded omo-calendar-create__tabs" data-generic-tabs>
                <div class="generic-tabs__list" aria-label="<?= omoApiEscape(omoCalendarCreateT('calendar.create.tabs_aria')) ?>">
                    <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="omoCalendarCreateTabEvent"><?= omoApiEscape(omoCalendarCreateT('calendar.create.tab.event')) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="omoCalendarCreateTabInvites"><?= omoApiEscape(omoCalendarCreateT('calendar.create.tab.invites')) ?></button>
                </div>
                <div class="generic-tabs__panels">
                    <div id="omoCalendarCreateTabEvent" class="generic-tabs__panel omo-calendar-create__tab-panel" data-generic-tab-panel>
                        <div class="omo-calendar-create__grid generic-form-grid">
                            <label class="omo-calendar-create__field generic-form-field">
                                <span class="generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.title')) ?></span>
                                <input
                                    type="text"
                                    name="title"
                                    class="generic-form-control"
                                    value="<?= omoApiEscape($titleDefault) ?>"
                                    maxlength="190"
                                    required
                                >
                            </label>

                            <label class="omo-calendar-create__field generic-form-field">
                                <span class="generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.status')) ?></span>
                                <select name="status" class="generic-form-control">
                                    <?php foreach ($editableEventStatuses as $statusValue => $statusLabel): ?>
                                        <option value="<?= omoApiEscape($statusValue) ?>"<?= $statusValue === $statusDefault ? ' selected' : '' ?>><?= omoApiEscape($statusLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <?php if ($hasStructureApplication): ?>
                            <label class="omo-calendar-create__field generic-form-field">
                                <span class="generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.holon')) ?></span>
                                <select<?= $project instanceof Project ? '' : ' name="IDholon"' ?> class="generic-form-control" data-omo-calendar-context-holon<?= $project instanceof Project ? ' disabled' : '' ?>>
                                    <option value="0"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.none')) ?></option>
                                    <?php foreach (['circle', 'role'] as $typeKey): ?>
                                        <?php foreach (($holonOptions[$typeKey] ?? []) as $option): ?>
                                            <option value="<?= (int)$option['id'] ?>" data-omo-calendar-context-path="<?= omoApiEscape((string)($holonContextPaths[(int)$option['id']] ?? '')) ?>"<?= (int)$option['id'] === $defaultHolonId ? ' selected' : '' ?>>
                                                <?= omoApiEscape((string)$option['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($project instanceof Project): ?>
                                    <input type="hidden" name="IDholon" value="<?= (int)$defaultHolonId ?>">
                                <?php endif; ?>
                            </label>
                            <?php endif; ?>

                            <label class="omo-calendar-create__field generic-form-field">
                                <span class="generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.start')) ?></span>
                                <input
                                    type="datetime-local"
                                    name="start_at"
                                    class="generic-form-control"
                                    value="<?= omoApiEscape($startDefault instanceof \DateTimeInterface ? $startDefault->format('Y-m-d\TH:i') : '') ?>"
                                    required
                                >
                            </label>

                            <label class="omo-calendar-create__field generic-form-field">
                                <span class="generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.end')) ?></span>
                                <input
                                    type="datetime-local"
                                    name="end_at"
                                    class="generic-form-control"
                                    value="<?= omoApiEscape($endDefault instanceof \DateTimeInterface ? $endDefault->format('Y-m-d\TH:i') : '') ?>"
                                    required
                                >
                            </label>
                        </div>

                        <label class="omo-calendar-create__field generic-form-field">
                            <span class="generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.description')) ?></span>
                            <textarea
                                name="description"
                                class="generic-form-control"
                            ><?= omoApiEscape($descriptionDefault) ?></textarea>
                        </label>

                        <label class="omo-calendar-create__check">
                            <input type="checkbox" name="is_all_day" value="1"<?= $isAllDayDefault ? ' checked' : '' ?>>
                            <span><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.all_day')) ?></span>
                        </label>

                        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided omo-calendar-create__block">
                            <div class="omo-calendar-create__block-head generic-form-section__heading">
                                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_mode')) ?></h3>
                            </div>

                            <div class="omo-calendar-create__grid generic-form-grid">
                                <label class="omo-calendar-create__field generic-form-field">
                                    <span class="omo-calendar-create__label generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_mode')) ?></span>
                                    <select name="location_mode" class="generic-form-control" data-omo-calendar-location-mode>
                                        <?php foreach ($locationModeOptions as $optionValue => $optionLabel): ?>
                                            <option value="<?= omoApiEscape((string)$optionValue) ?>"<?= (string)$optionValue === $locationModeDefault ? ' selected' : '' ?>>
                                                <?= omoApiEscape((string)$optionLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label class="omo-calendar-create__field generic-form-field" data-omo-calendar-location-address-field<?= in_array($locationModeDefault, [Event::LOCATION_MODE_IN_PERSON, Event::LOCATION_MODE_HYBRID], true) ? '' : ' hidden' ?>>
                                    <span class="omo-calendar-create__label generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_address')) ?></span>
                                    <input
                                        type="text"
                                        name="location_address"
                                        class="generic-form-control"
                                        value="<?= omoApiEscape($locationAddressDefault) ?>"
                                        placeholder="<?= omoApiEscape(omoCalendarCreateT('calendar.create.field.location_address_placeholder')) ?>"
                                    >
                                </label>

                                <label class="omo-calendar-create__field generic-form-field" data-omo-calendar-location-video-field<?= in_array($locationModeDefault, [Event::LOCATION_MODE_VIRTUAL, Event::LOCATION_MODE_HYBRID], true) ? '' : ' hidden' ?>>
                                    <span class="omo-calendar-create__label generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.video_url')) ?></span>
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

                        <section class="generic-section generic-section--stack generic-form-section generic-form-section--divided omo-calendar-create__block" data-omo-calendar-document-block>
                            <div class="omo-calendar-create__block-head generic-form-section__heading">
                                <h3 class="generic-card-title generic-card-title--small"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.document_type')) ?></h3>
                                <?php if ($associatedDocument instanceof Document): ?>
                                    <span class="omo-calendar-create__pill"><?= omoApiEscape($associatedDocument->getDocumentTypeLabel()) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($associatedDocument instanceof Document): ?>
                                <input type="hidden" name="document_type" value="<?= omoApiEscape($documentTypeDefault) ?>" data-omo-calendar-document-type>
                                <div data-omo-calendar-document-fields>
                                    <div class="omo-calendar-create__document-summary">
                                        <span class="omo-calendar-create__label generic-form-label generic-form-label--eyebrow"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.current')) ?></span>
                                        <strong class="omo-calendar-create__document-title"><?= omoApiEscape(trim((string)$associatedDocument->get('title')) !== '' ? trim((string)$associatedDocument->get('title')) : omoCalendarCreateT('calendar.create.document.empty_title')) ?></strong>
                                        <p class="omo-calendar-create__hint generic-description generic-description--relaxed"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.help_existing')) ?></p>
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
                                <?php if (!$canCreateLinkedDocument): ?>
                                    <p class="omo-calendar-create__hint generic-description generic-description--relaxed">
                                        <?= omoApiEscape(omoCalendarCreateT('calendar.create.document.no_permission')) ?>
                                    </p>
                                <?php else: ?>
                                    <label class="omo-calendar-create__field generic-form-field">
                                        <span class="omo-calendar-create__label generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.document_type')) ?></span>
                                        <select name="document_type" class="generic-form-control" data-omo-calendar-document-type>
                                            <?php foreach ($documentTypeOptions as $optionValue => $optionLabel): ?>
                                                <option value="<?= omoApiEscape((string)$optionValue) ?>"<?= (string)$optionValue === $documentTypeDefault ? ' selected' : '' ?>>
                                                    <?= omoApiEscape((string)$optionLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <div data-omo-calendar-document-fields<?= $documentTypeDefault !== '' ? '' : ' hidden' ?>>
                                        <label class="omo-calendar-create__field generic-form-field">
                                            <span class="omo-calendar-create__label generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.document_title')) ?></span>
                                            <input
                                                type="text"
                                                name="document_title"
                                                class="generic-form-control"
                                                value="<?= omoApiEscape($documentTitleDefault) ?>"
                                                maxlength="255"
                                            >
                                        </label>
                                        <label class="omo-calendar-create__field generic-form-field" data-omo-calendar-pv-template-field<?= $documentTypeDefault === Document::TYPE_PV ? '' : ' hidden' ?>>
                                            <span class="omo-calendar-create__label generic-form-label"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.pv_template')) ?></span>
                                            <select name="pv_template_id" class="generic-form-control">
                                                <option value="0"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.pv_template_none')) ?></option>
                                                <?php foreach ($pvTemplatesPayload as $pvTemplateOption): ?>
                                                    <option value="<?= (int)$pvTemplateOption['id'] ?>" data-omo-calendar-pv-template-scope="<?= omoApiEscape((string)$pvTemplateOption['visibilityType']) ?>" data-omo-calendar-pv-template-target="<?= (int)$pvTemplateOption['targetHolonId'] ?>"><?= omoApiEscape((string)$pvTemplateOption['label']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="omo-calendar-create__hint generic-description generic-description--relaxed"><?= omoApiEscape(omoCalendarCreateT('calendar.create.field.pv_template_hint')) ?></span>
                                        </label>
                                        <p class="omo-calendar-create__hint generic-description generic-description--relaxed"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.help_create')) ?></p>
                                        <p class="omo-calendar-create__notice"><?= omoApiEscape(omoCalendarCreateT('calendar.create.document.created_notice')) ?></p>
                                    </div>
                                <?php endif; ?>
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
                <?php require dirname(__DIR__, 3) . '/common/calendar/availability-panel.php'; ?>
                <input type="hidden" name="availability_ack" value="">
                <div class="omo-calendar-create__feedback generic-feedback" data-omo-calendar-create-feedback></div>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="/omo/api/calendar/editor.css?v=20260917-style-review-final">
