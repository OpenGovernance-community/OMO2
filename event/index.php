<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/email_layout.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
require_once dirname(__DIR__) . '/common/meeting/steps.php';

use dbObject\AuthRateLimit;
use dbObject\Event;
use dbObject\EventPublicLink;
use dbObject\EventPublicRegistration;
use dbObject\Organization;
use dbObject\User;

$sourceLang = [
    'title' => ['text' => 'Inscription à un événement', 'context' => 'Public event registration page title.'],
    'powered_by' => ['text' => 'Powered by', 'context' => 'Attribution before the OMO2 and OpenMyOrganization links in the public registration footer.'],
    'steps' => ['text' => "Étapes de l'inscription", 'context' => 'Accessible label for public registration progress.'],
    'step_details' => ['text' => 'Vos informations', 'context' => 'First registration step: name and email.'],
    'step_email' => ['text' => 'Envoi e-mail', 'context' => 'Second registration step: confirmation email sent.'],
    'step_confirm' => ['text' => 'Confirmation', 'context' => 'Third registration step: email confirmation and receipt.'],
    'unavailable' => ['text' => "Cette page d'inscription n'est pas disponible.", 'context' => 'Unavailable public event.'],
    'intro' => ['text' => 'Participez à cette rencontre', 'context' => 'Public event introduction.'],
    'date' => ['text' => 'Date et horaire', 'context' => 'Event date label.'],
    'place' => ['text' => 'Lieu', 'context' => 'Event place label.'],
    'place_unspecified' => ['text' => 'Lieu à préciser', 'context' => 'Event with no physical or online location yet.'],
    'video' => ['text' => 'Visioconférence', 'context' => 'Online event label.'],
    'video_locked' => ['text' => "Finalisez l'inscription pour accéder à l'URL", 'context' => 'Placeholder hiding the meeting URL until the registration is confirmed.'],
    'description' => ['text' => 'À propos de la rencontre', 'context' => 'Event description heading.'],
    'form_title' => ['text' => "S'inscrire", 'context' => 'Registration form heading.'],
    'name' => ['text' => 'Votre nom', 'context' => 'Registrant name field.'],
    'email' => ['text' => 'Votre adresse e-mail', 'context' => 'Registrant email field.'],
    'send' => ['text' => 'Recevoir le lien de confirmation', 'context' => 'Registration submit button.'],
    'privacy' => ['text' => "Votre nom et votre e-mail sont transmis aux organisateurs. L'inscription devient effective après validation de votre adresse.", 'context' => 'Registration privacy explanation.'],
    'sent' => ['text' => 'Consultez votre messagerie pour confirmer votre inscription.', 'context' => 'Neutral message after a registration request.'],
    'confirm_title' => ['text' => 'Confirmer mon inscription', 'context' => 'Email confirmation landing heading.'],
    'confirm_hint' => ['text' => "Validez votre adresse e-mail pour rendre votre inscription effective.", 'context' => 'Email confirmation landing explanation.'],
    'confirm_button' => ['text' => "Confirmer l'inscription", 'context' => 'Email confirmation button.'],
    'confirmed' => ['text' => 'Votre inscription est confirmée.', 'context' => 'Receipt heading.'],
    'confirmed_hint' => ['text' => 'Conservez le lien de cette page pour retrouver les informations de la rencontre.', 'context' => 'Receipt explanation.'],
    'registered_count' => ['text' => 'Personnes inscrites', 'context' => 'Number of confirmed public registrants.'],
    'present_count' => ['text' => 'Personnes présentes', 'context' => 'Number marked present at the event.'],
    'document' => ['text' => 'Document associé', 'context' => 'Linked event document heading.'],
    'open_pad' => ['text' => 'Ouvrir le pad', 'context' => 'Open an editable collaborative pad in the side drawer.'],
    'open_pv' => ['text' => 'Participer à la réunion', 'context' => 'Open the registrant’s personal public meeting participation page.'],
    'open_window' => ['text' => 'Ouvrir dans un nouvel onglet', 'context' => 'Alternative to the embedded collaborative pad.'],
    'close_document' => ['text' => 'Fermer', 'context' => 'Close the collaborative document drawer.'],
    'invalid' => ['text' => 'Ce lien est invalide ou a expiré.', 'context' => 'Invalid confirmation or receipt link.'],
    'input_error' => ['text' => 'Saisissez un nom et une adresse e-mail valides.', 'context' => 'Invalid registration input.'],
    'error' => ['text' => 'Une erreur est survenue. Réessayez plus tard.', 'context' => 'Generic registration error.'],
    'rate' => ['text' => 'Trop de demandes. Réessayez dans quelques minutes.', 'context' => 'Public registration rate limit.'],
    'mail_subject' => ['text' => 'Confirmez votre inscription : {title}', 'context' => 'Registration confirmation email subject.'],
    'mail_intro' => ['text' => 'Vous avez demandé une inscription à « {title} ». Confirmez votre adresse pour valider votre place.', 'context' => 'Registration confirmation email introduction.'],
    'mail_button' => ['text' => 'Confirmer mon inscription', 'context' => 'Registration confirmation email button.'],
    'mail_footer' => ['text' => "Si vous n'avez pas demandé cette inscription, ignorez ce message.", 'context' => 'Registration confirmation email footer.'],
    'mail_contact' => ['text' => 'Votre contact : {name} ({email}). Vous pouvez répondre directement à cet e-mail.', 'context' => 'Event creator contact details and reply instructions in the registration email.'],
];
$bundle = loadTranslationBundle('event_public_registration', translationBundleResolveRequestLocale('lang', translationBundleGetSupportedLocales(), 'fr'), $sourceLang);
function eventT(string $key, array $replace = []): string
{
    global $bundle, $sourceLang;
    return t($key, $replace, $bundle, $sourceLang);
}
function eventEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');
$_SESSION['event_registration_csrf'] ??= bin2hex(random_bytes(32));
$csrf = $_SESSION['event_registration_csrf'];
$error = '';
$state = 'form';
$registration = null;
$event = null;
$link = null;
$name = '';
$email = '';
$receiptToken = trim((string)($_GET['receipt'] ?? ''));
$publicToken = trim((string)($_GET['token'] ?? ''));
$confirmationToken = trim((string)($_GET['confirm'] ?? ''));

try {
    if ($receiptToken !== '') {
        $registration = EventPublicRegistration::forToken($receiptToken);
        if (!$registration || !$registration->get('confirmed_at')) { throw new RuntimeException('invalid'); }
        $link = EventPublicLink::forEvent((int)$registration->get('IDevent'));
        if (!$link || !(int)$link->get('enabled')) { throw new RuntimeException('invalid'); }
        $state = 'receipt';
    } else {
        $link = EventPublicLink::forToken($publicToken);
        if (!$link) { throw new RuntimeException('invalid'); }
    }
    $event = new Event();
    if (!$event->load((int)$link->get('IDevent')) || !(int)$event->get('active')
        || in_array((string)$event->get('status'), [Event::STATUS_DRAFT, Event::STATUS_CANCELLED], true)) {
        throw new RuntimeException('invalid');
    }
    if ($confirmationToken !== '' && $state !== 'receipt') {
        $registration = EventPublicRegistration::forToken($confirmationToken);
        if (!$registration || (int)$registration->get('IDevent') !== (int)$event->getId()) { throw new RuntimeException('invalid'); }
        if ($registration->get('confirmed_at')) {
            header('Location: /event/receipt/' . rawurlencode((string)$registration->get('token')), true, 303);
            exit;
        }
        $state = 'confirm';
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { throw new RuntimeException('invalid'); }
        $limit = AuthRateLimit::consume('event-public-registration', commonAuthHashIdentifier('event-ip', commonGetRequestIp()), 12, 600);
        if (empty($limit['allowed'])) { http_response_code(429); throw new RuntimeException('rate'); }
        if ($state === 'confirm' && ($_POST['action'] ?? '') === 'confirm') {
            if (!$registration->get('confirmed_at')) {
                $registration->set('confirmed_at', new DateTimeImmutable());
                $saved = $registration->save();
                if (empty($saved['status'])) { throw new RuntimeException('error'); }
            }
            header('Location: /event/receipt/' . rawurlencode((string)$registration->get('token')), true, 303);
            exit;
        }
        if ($state !== 'form') { throw new RuntimeException('invalid'); }
        if (trim((string)($_POST['website'] ?? '')) !== '') { throw new RuntimeException('input_error'); }
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim(mb_strtolower((string)($_POST['email'] ?? ''), 'UTF-8'));
        if ($name === '' || mb_strlen($name) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new RuntimeException('input_error');
        }
        $registration = EventPublicRegistration::forEmail((int)$event->getId(), $email);
        if (!$registration) {
            $registration = new EventPublicRegistration();
            $registration->set('IDevent', (int)$event->getId());
            $registration->set('email', $email);
            $registration->set('token', bin2hex(random_bytes(32)));
            $registration->set('created_at', new DateTimeImmutable());
        }
        if (!$registration->get('confirmed_at')) {
            $registration->set('name', $name);
            $saved = $registration->save();
            if (empty($saved['status'])) { throw new RuntimeException('error'); }
        }
        $base = rtrim((string)appGetCurrentSiteBaseUrl(), '/');
        $url = $base . $link->publicPath() . '?confirm=' . rawurlencode((string)$registration->get('token'));
        $organizationId = (int)$event->get('IDorganization');
        $organization = new Organization();
        $organizationName = $organization->load($organizationId) ? trim((string)$organization->get('name')) : '';
        $creator = new User();
        $creatorEmail = $creator->load((int)$event->get('IDuser')) ? trim((string)$creator->getScopedEmail($organizationId)) : '';
        if (!filter_var($creatorEmail, FILTER_VALIDATE_EMAIL)) { $creatorEmail = ''; }
        $creatorName = $event->getCreatedByDisplayName();
        $contactName = $creatorName !== '' ? $creatorName : $creatorEmail;
        $senderName = implode(' · ', array_unique(array_filter([$contactName, $organizationName]))) ?: 'OMO';
        $replyTo = $creatorEmail !== '' ? [$creatorEmail, $contactName] : null;
        $body = commonRenderMailLayout([
            'brand_name' => $organizationName !== '' ? $organizationName : $senderName,
            'heading' => eventT('confirm_title'),
            'intro_html' => commonMailTextToHtml(eventT('mail_intro', ['title' => (string)$event->get('title')])),
            'details_html' => $replyTo !== null ? commonMailTextToHtml(eventT('mail_contact', ['name' => $contactName, 'email' => $creatorEmail])) : '',
            'button_label' => eventT('mail_button'), 'button_url' => $url,
            'footer_html' => commonMailTextToHtml(eventT('mail_footer')),
        ]);
        $from = trim((string)($GLOBALS['mailUser'] ?? ''));
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $host = function_exists('commonGetRootHost') ? commonGetRootHost() : (string)($_SERVER['HTTP_HOST'] ?? '');
            $from = 'noreply@' . preg_replace('/:\d+$/', '', (string)$host);
            if (!filter_var($from, FILTER_VALIDATE_EMAIL)) { $from = 'noreply@localhost.invalid'; }
        }
        if (!myHTMLMail([$from, $senderName], $email, eventT('mail_subject', ['title' => (string)$event->get('title')]), $body, replyTo: $replyTo)) {
            error_log('Public event registration email failed for event ' . (int)$event->getId());
            throw new RuntimeException('error');
        }
        $state = 'sent';
    }
} catch (Throwable $exception) {
    $key = in_array($exception->getMessage(), ['invalid', 'input_error', 'rate'], true) ? $exception->getMessage() : 'error';
    if ($key === 'invalid') { http_response_code(404); $event = null; }
    if ($key === 'error') { error_log('Public event registration failed: ' . get_class($exception) . ': ' . $exception->getMessage()); }
    $error = eventT($key);
}

$zoneName = $event ? (string)$event->get('timezone') : 'Europe/Zurich';
try { $zone = new DateTimeZone($zoneName ?: 'Europe/Zurich'); } catch (Throwable) { $zone = new DateTimeZone('Europe/Zurich'); }
$start = $event && $event->get('start_at') instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($event->get('start_at'))->setTimezone($zone) : null;
$end = $event && $event->get('end_at') instanceof DateTimeInterface ? DateTimeImmutable::createFromInterface($event->get('end_at'))->setTimezone($zone) : null;
$schedule = '';
if ($start) {
    if ($event->get('is_all_day')) {
        $schedule = $start->format('d.m.Y');
        if ($end && $end->format('Y-m-d') !== $start->format('Y-m-d')) { $schedule .= ' – ' . $end->format('d.m.Y'); }
    } else {
        $schedule = $start->format('d.m.Y H:i');
        if ($end) { $schedule .= ' – ' . ($end->format('Y-m-d') === $start->format('Y-m-d') ? $end->format('H:i') : $end->format('d.m.Y H:i')); }
        $schedule .= ' (' . $zone->getName() . ')';
    }
}
$location = $event ? $event->getLocationDisplayData() : [];
$documents = $state === 'receipt' && $event && $registration ? $registration->getAccessibleDocuments() : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?= eventEscape($event ? $event->get('title') : eventT('title')) ?> - OMO</title>
    <link rel="stylesheet" href="<?= eventEscape(commonAssetUrl('/common/assets/components.css')) ?>">
    <link rel="stylesheet" href="<?= eventEscape(commonAssetUrl('/common/meeting/public.css')) ?>">
    <link rel="stylesheet" href="<?= eventEscape(commonAssetUrl('/event/event.css')) ?>">
    <?php if ($documents): ?><link rel="stylesheet" href="<?= eventEscape(commonAssetUrl('/common/meeting/document-drawer.css')) ?>"><?php endif; ?>
</head>
<body class="meeting-page event-registration-page">
<main class="generic-page-shell meeting-shell event-registration-shell">
    <div class="meeting-topbar">
        <a class="meeting-brand" href="<?= eventEscape($link ? $link->publicPath() : '/') ?>"><span><img class="meeting-brand__logo" src="<?= eventEscape(commonAssetUrl('/img/omo2/logo-omo-dark.png')) ?>" alt="OMO" width="1076" height="332"><span class="meeting-brand__label"><?= eventEscape(eventT('title')) ?></span></span></a>
        <?php if ($event): ?>
            <?= commonMeetingRenderSteps(
                [eventT('step_details'), eventT('step_email'), eventT('step_confirm')],
                match ($state) { 'form' => 1, 'sent' => 2, default => 3 },
                $state === 'receipt',
                eventT('steps')
            ) ?>
        <?php endif; ?>
    </div>
    <?php if (!$event): ?>
    <section class="generic-soft-panel generic-soft-panel--elevated"><h1 class="generic-card-title generic-card-title--large"><?= eventEscape(eventT('unavailable')) ?></h1></section>
    <?php else: ?>
    <header class="generic-soft-panel generic-soft-panel--elevated meeting-host">
        <div class="meeting-icon-disc" aria-hidden="true">&#128197;</div>
        <div class="meeting-host__copy"><p class="meeting-eyebrow"><?= eventEscape(eventT('intro')) ?></p><h1 class="generic-card-title generic-card-title--display"><?= eventEscape($event->get('title')) ?></h1></div>
    </header>
    <div class="event-registration-layout">
        <section class="generic-soft-panel generic-soft-panel--elevated generic-stack">
            <div class="meeting-detail-row"><span class="meeting-icon-disc" aria-hidden="true">&#128339;</span><div><span class="meeting-muted"><?= eventEscape(eventT('date')) ?></span><strong><?= eventEscape($schedule) ?></strong></div></div>
            <?php if (!empty($location['address'])): ?><div class="meeting-detail-row"><span class="meeting-icon-disc" aria-hidden="true">&#128205;</span><div><span class="meeting-muted"><?= eventEscape(eventT('place')) ?></span><strong><?= eventEscape($location['address']) ?></strong></div></div><?php endif; ?>
            <?php if (!empty($location['videoUrl'])): ?><div class="meeting-detail-row"><span class="meeting-icon-disc" aria-hidden="true">&#128249;</span><div><span class="meeting-muted"><?= eventEscape(eventT('video')) ?></span><strong><?= eventEscape($state === 'receipt' ? $location['videoUrl'] : eventT('video_locked')) ?></strong></div></div><?php endif; ?>
            <?php if (empty($location['address']) && empty($location['videoUrl'])): ?><div class="meeting-detail-row"><span class="meeting-icon-disc" aria-hidden="true">&#128205;</span><div><span class="meeting-muted"><?= eventEscape(eventT('place')) ?></span><strong><?= eventEscape(eventT('place_unspecified')) ?></strong></div></div><?php endif; ?>
            <?php if (trim((string)$event->get('description')) !== ''): ?><section class="generic-section generic-section--stack"><h2 class="generic-card-title generic-card-title--medium"><?= eventEscape(eventT('description')) ?></h2><p class="event-registration-description"><?= nl2br(eventEscape($event->get('description'))) ?></p></section><?php endif; ?>
        </section>
        <section class="generic-soft-panel generic-soft-panel--elevated generic-stack" aria-live="polite">
            <?php if ($state === 'receipt' && $registration): ?>
                <span class="meeting-success-mark" aria-hidden="true">&#10003;</span>
                <h2 class="generic-card-title generic-card-title--large"><?= eventEscape(eventT('confirmed')) ?></h2>
                <p class="meeting-muted"><?= eventEscape(eventT('confirmed_hint')) ?></p>
                <div class="event-registration-counts"><div><strong><?= EventPublicRegistration::confirmedCount((int)$event->getId()) ?></strong><span><?= eventEscape(eventT('registered_count')) ?></span></div><div><strong><?= EventPublicRegistration::presentCount((int)$event->getId()) ?></strong><span><?= eventEscape(eventT('present_count')) ?></span></div></div>
            <?php elseif ($state === 'sent'): ?>
                <h2 class="generic-card-title generic-card-title--large"><?= eventEscape(eventT('sent')) ?></h2>
            <?php elseif ($state === 'confirm' && $registration): ?>
                <h2 class="generic-card-title generic-card-title--large"><?= eventEscape(eventT('confirm_title')) ?></h2>
                <p class="meeting-muted"><?= eventEscape(eventT('confirm_hint')) ?></p>
                <form method="post" class="generic-form-stack"><input type="hidden" name="csrf" value="<?= eventEscape($csrf) ?>"><input type="hidden" name="action" value="confirm"><button class="generic-action-button generic-action-button--main"><?= eventEscape(eventT('confirm_button')) ?></button></form>
            <?php else: ?>
                <h2 class="generic-card-title generic-card-title--large"><?= eventEscape(eventT('form_title')) ?></h2>
                <?php if ($error !== ''): ?><p class="generic-feedback" role="alert"><?= eventEscape($error) ?></p><?php endif; ?>
                <form method="post" class="generic-form-stack generic-form-stack--compact">
                    <input type="hidden" name="csrf" value="<?= eventEscape($csrf) ?>">
                    <label class="meeting-honeypot" aria-hidden="true">Website<input name="website" tabindex="-1" autocomplete="off"></label>
                    <label class="generic-form-field"><span><?= eventEscape(eventT('name')) ?></span><input class="generic-form-control" type="text" name="name" autocomplete="name" maxlength="190" required value="<?= eventEscape($name) ?>"></label>
                    <label class="generic-form-field"><span><?= eventEscape(eventT('email')) ?></span><input class="generic-form-control" type="email" name="email" autocomplete="email" maxlength="254" required value="<?= eventEscape($email) ?>"></label>
                    <p class="generic-help-text"><?= eventEscape(eventT('privacy')) ?></p>
                    <button class="generic-action-button generic-action-button--main generic-action-button--wide"><?= eventEscape(eventT('send')) ?></button>
                </form>
            <?php endif; ?>
        </section>
    </div>
    <?php if ($state === 'receipt' && $documents): ?>
        <?php foreach ($documents as $document): ?>
            <?php
                $isPad = $document->isEtherpadDocument() || $document->isFramapadExternalLink();
                $isParticipation = $document->isPvDocument() && !$document->isPvValidated();
                $documentTitle = trim((string)$document->get('title'));
                $documentUrl = '/event/document.php?' . http_build_query(['receipt' => $receiptToken, 'id' => (int)$document->getId()]);
            ?>
            <section class="generic-soft-panel generic-soft-panel--elevated generic-stack">
                <h2 class="generic-card-title generic-card-title--large"><?= eventEscape(eventT('document')) ?> : <?= eventEscape($documentTitle) ?></h2>
                <?php if ($isPad || $isParticipation): ?>
                    <div class="generic-action-row">
                        <a class="generic-action-button generic-action-button--main" href="<?= eventEscape($documentUrl) ?>" target="_blank" rel="noopener noreferrer"<?= $isPad ? ' data-meeting-document-open data-meeting-document-title="' . eventEscape($documentTitle) . '"' : '' ?>><?= eventEscape(eventT($isPad ? 'open_pad' : 'open_pv')) ?></a>
                    </div>
                <?php else: ?>
                    <?php $payload = $document->buildLiveSharePayload(false); ?>
                    <div class="event-registration-document prose"><?= (string)($payload['content'] ?? '') ?></div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php endif; ?>
    <footer class="meeting-footer"><?= eventEscape(eventT('powered_by')) ?> <a href="/">OMO2</a> &middot; <a href="/">OpenMyOrganization</a></footer>
</main>
<?php if ($documents): ?>
<dialog class="meeting-document-drawer" data-meeting-document-drawer aria-labelledby="eventDocumentTitle">
    <div class="generic-drawer-header">
        <div class="generic-drawer-header__copy"><h2 class="generic-card-title generic-card-title--medium" id="eventDocumentTitle" data-meeting-document-title><?= eventEscape(eventT('document')) ?></h2></div>
        <div class="generic-drawer-header__actions">
            <a class="generic-action-button generic-action-button--secondary" data-meeting-document-external target="_blank" rel="noopener noreferrer"><?= eventEscape(eventT('open_window')) ?></a>
            <button type="button" class="generic-action-button generic-action-button--secondary" data-meeting-document-close autofocus><?= eventEscape(eventT('close_document')) ?></button>
        </div>
    </div>
    <iframe class="meeting-document-drawer__frame" title="<?= eventEscape(eventT('document')) ?>" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-downloads"></iframe>
</dialog>
<script src="<?= eventEscape(commonAssetUrl('/common/meeting/document-drawer.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
