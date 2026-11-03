<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/carddav.php';

use dbObject\{User, UserOrganization, PropertyFormat};

function presentationCheck(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}

class PresentationMembershipProbe extends UserOrganization
{
    public User $fixtureUser;
    public function get($field)
    {
        return $field === 'user' ? $this->fixtureUser : parent::get($field);
    }
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
$plain = "First & <second@example.org>\r\nSecond\n\nFourth";
$html = '<p>Hello <strong>Name</strong></p><ul><li>One</li><li><em>Two</em></li></ul>';
$unsafe = '<p onclick="alert(1)">Safe <b>bold</b><a href="javascript:alert(2)">link</a></p><script>alert(3)</script><img src=x onerror=alert(4)>';
foreach ([User::class, UserOrganization::class] as $class) {
    presentationCheck($class::getFieldType('presentation') === 'html' && $class::attributeHtmlEditorProfiles()['presentation'] === 'simple', 'Both presentation scopes declare the shared simple HTML editor');
    $object = new $class();
    $object->set('presentation', $plain);
    $formatted = (string)$object->get('presentation');
    presentationCheck(substr_count($formatted, '<br>') === 3 && str_contains($formatted, '&lt;second@example.org&gt;'), 'Keep legacy line breaks and escape text comparisons/address brackets');
    presentationCheck(PropertyFormat::htmlToPlainText($formatted) === "First & <second@example.org>\nSecond\n\nFourth", 'Text and HTML conversions preserve visible legacy content');
    $object->set('presentation', $html);
    presentationCheck($object->get('presentation') === $html, 'Keep basic formatting without escaping tags');
    $object->loadProfileInput(['presentation' => $unsafe]);
    $clean = (string)$object->get('presentation');
    presentationCheck(str_contains($clean, '<b>bold</b>') && !preg_match('/script|onclick|onerror|javascript:|<img|alert\(/i', $clean), 'Profile updates sanitize HTML through dbObject');
    $loaded = new $class();
    $loaded->hydrateFromDatabaseRow(['id' => 123, 'presentation' => "Stored first\nStored second"], true);
    presentationCheck(str_contains((string)$loaded->get('presentation'), 'Stored first<br>'), 'Existing database text is normalized at read time');
    $loaded->hydrateFromDatabaseRow(['id' => 123, 'presentation' => $unsafe], true);
    presentationCheck($loaded->get('presentation') === $clean, 'Existing database HTML is sanitized at read time');
    presentationCheck(PropertyFormat::textOrHtmlToHtml($clean) === $clean, 'Repeated reads must not duplicate tags or escape content again');

    // Exercise the real adminEdit renderer rather than a separate presentation form.
    ob_start();
    if (function_exists('displayField')) { echo displayField($object, 'presentation'); }
    else { $object->display('adminEdit.php', ['fields' => ['presentation'], 'buttons' => false]); }
    $form = (string)ob_get_clean();
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $form);
    $fields = (new DOMXPath($dom))->query('//textarea[@name="presentation" and @data-editor-profile="simple" and contains(@class,"summernote")]');
    presentationCheck($fields->length === 1 && $fields->item(0)->textContent === $clean, 'adminEdit renders one safely escaped Summernote source field');
}

$user = new User();
$user->set('firstname', 'Profile'); $user->set('email', 'profile@example.org');
$user->set('presentation', "General first\nGeneral second");
$member = new PresentationMembershipProbe(); $member->fixtureUser = $user;
$member->set('presentation', $html);
presentationCheck($member->getScopedPresentation() === $html, 'Organization presentation takes precedence');
$card = commonCardDavBuildContactResource(1, $user, [$member]);
presentationCheck($card['payload']['note'] === "Hello Name\nOne\nTwo" && str_contains($card['vcard'], 'NOTE:Hello Name\\nOne\\nTwo'), 'CardDAV exports readable text and explicit line breaks instead of HTML tags');
$member->set('presentation', '<p><br></p>');
presentationCheck($member->getScopedPresentation() === $user->getScopedPresentation(), 'Empty rich text falls back to the general presentation');
$card = commonCardDavBuildContactResource(1, $user, []);
presentationCheck($card['payload']['note'] === "General first\nGeneral second", 'General presentation fallback remains plain text in CardDAV');
echo "user_presentation_html_test: OK\n";
