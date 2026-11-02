<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, PdoDbhCompat, Organization, OrganizationRoleplay, OrganizationExport, User, UserOrganization, UserHolon, ArrayUserOrganization, Invitation, ObjectAudience, ObjectMail};

function roleplayCheck(bool $ok, string $message): void
{
    if (!$ok) { throw new RuntimeException($message); }
}
function roleplaySave(DbObject $object): void
{
    roleplayCheck(!empty($object->save()['status']), 'Save fixture: ' . get_class($object));
}
function roleplayDenied(callable $action): void
{
    try { $action(); } catch (DomainException $error) { return; }
    throw new RuntimeException('Unsafe or ambiguous roleplay routing must fail');
}
// Keep the real importer's commits inside a single rollback-only transaction.
class RoleplayRollbackConnection
{
    public function __construct(private PDO $pdo) {}
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function __call(string $method, array $arguments) { return $this->pdo->$method(...$arguments); }
}

$beforeSession = $_SESSION ?? [];
$originalDbh = DbObject::$_dbh;
$pdo = DbObject::getPdo();
$capture = tempnam(sys_get_temp_dir(), 'omo-roleplay-mail-');
$smtp = null; $pipes = [];
$pdo->beginTransaction();
DbObject::$_dbh = new PdoDbhCompat(new RoleplayRollbackConnection($pdo));
try {
    // All SMTP, including accidental import invitations, goes to this loopback-only sink.
    $smtp = proc_open([PHP_BINARY, __DIR__ . '/object_mail_smtp_fixture.php', $capture], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    roleplayCheck(is_resource($smtp), 'Start local SMTP sink');
    $address = trim(fgets($pipes[1]));
    roleplayCheck(str_starts_with($address, '127.0.0.1:'), 'SMTP must never leave loopback');
    $GLOBALS['mailHost'] = '127.0.0.1'; $GLOBALS['mailPort'] = (int)substr(strrchr($address, ':'), 1);
    $GLOBALS['mailSecure'] = ''; $GLOBALS['mailAuth'] = false; $GLOBALS['mailTimeout'] = 3;
    $GLOBALS['mailUser'] = 'sender@example.org';
    $token = bin2hex(random_bytes(5));
    $actor = new User();
    $actor->set('email', 'actor-' . $token . '@example.org');
    $actor->set('firstname', 'Importer'); $actor->set('active', true); roleplaySave($actor);
    $sourceUser = new User();
    $sourceUser->set('email', 'source-' . $token . '@example.org');
    $sourceUser->set('firstname', 'Original'); $sourceUser->set('active', true); roleplaySave($sourceUser);
    $extraAdmin = new User();
    $extraAdmin->set('email', 'admin-' . $token . '@example.org');
    $extraAdmin->set('active', true); roleplaySave($extraAdmin);
    $_SESSION['currentUser'] = (int)$actor->getId();
    $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
    $record = ['sourceId' => 11, 'email' => (string)$actor->get('email'), 'firstname' => 'Character', 'lastname' => 'One',
        'username' => 'one', 'image' => '/img/orange.webp', 'active' => false, 'organizationMembership' => ['isAdmin' => true],
        'roleAssignments' => [['sourceHolonId' => 2, 'isContextAdmin' => true, 'kind' => 'role_owner']]];
    $sourcePayload = [
        'format' => OrganizationExport::FORMAT, 'version' => 4, 'exportedAt' => (new DateTimeImmutable('-7 days'))->format('c'), 'source' => ['system' => 'omo2'],
        'organization' => ['name' => 'Roleplay fixture'], 'scope' => ['organizationRootHolonId' => 1],
        'holons' => [['id' => 1, 'typeId' => 4, 'name' => 'Root', 'role' => 'organization_root', 'children' => [['id' => 2, 'typeId' => 1, 'name' => 'Role']]]],
        'modules' => ['structure' => ['selected' => true], 'members' => ['selected' => true, 'records' => [
            $record, array_replace($record, ['sourceId' => 12, 'email' => (string)$sourceUser->get('email'), 'lastname' => 'Two']),
            array_replace($record, ['sourceId' => 13, 'email' => '', 'lastname' => 'Without email']),
        ]]],
    ];
    $selected = ['structure' => true, 'members' => true];
    $imports = [];
    foreach ([true, false] as $actorInFile) {
        $payload = $sourcePayload;
        if (!$actorInFile) { array_shift($payload['modules']['members']['records']); }
        $before = $payload;
        $options = ['roleplay' => true];
        if (!$actorInFile) { $options['sendMemberInvitationEmails'] = true; }
        $result = Organization::importOmo1ExportAsNewOrganization($payload, $selected, (int)$actor->getId(), '', [], $options);
        roleplayCheck(!empty($result['status']), 'Import: ' . ($result['message'] ?? 'failed'));
        roleplayCheck($payload === $before, 'Never rewrite the original archive');
        $org = $result['organization']; $oid = (int)$org->getId();
        roleplayCheck((bool)$org->getParameter('roleplay'), 'Persist the roleplay organization marker');
        $members = new ArrayUserOrganization();
        $members->load(['where' => [['field' => 'IDorganization', 'value' => $oid]]]);
        roleplayCheck(count($members) === ($actorInFile ? 4 : 3), 'Keep every character and add the real importer separately');
        $characters = [];
        foreach ($members as $member) {
            $user = $member->get('user');
            roleplayCheck((bool)$member->get('active'), 'No pending/grey organization members');
            if ((int)$user->getId() === (int)$actor->getId()) {
                roleplayCheck($member->isOrganizationAdmin() && !$user->isRoleplayCharacter(), 'The real importer remains administrator');
                continue;
            }
            roleplayCheck($user->isRoleplayCharacter() && $user->get('active') && !$user->isSiteAdmin(), 'Active isolated character');
            roleplayCheck(!(Invitation::findPendingForOrganizationUser($oid, $user->getId()) instanceof Invitation), 'Characters have no pending invitation');
            roleplayCheck(!$member->isOrganizationAdmin() && (int)$user->getId() !== (int)$sourceUser->getId(), 'Never reuse source accounts or imported administrators');
            roleplayCheck(OrganizationRoleplay::isInvalidEmail($member->getScopedEmail()), 'Display only .invalid addresses');
            roleplayCheck($member->get('image') === '/img/orange.webp', 'Preserve character photos');
            roleplayCheck(!$user->activateForVerifiedLogin() && !$user->allowsPasswordLogin() && $user->issuePasswordResetCode() === '', 'Characters cannot authenticate or recover passwords');
            roleplayCheck(commonSendLoginCode($user->getId(), $user->get('email'), [], 0, '/') === false, 'No login code is delivered to administrators for a character');
            $assignment = new UserHolon();
            roleplayCheck((bool)$assignment->load([['IDuser', $user->getId()], ['IDholon', $result['holonIdMap'][2]]]), 'Keep role assignments');
            roleplayCheck($assignment->get('active') && !$assignment->isHolonAdmin(), 'Active role without imported context administration');
            $characters[$member->getScopedEmail()] = $user;
        }
        roleplayCheck($result['stats']['invitations'] === 0 && filesize($capture) === 0, 'No invitations, even when send was requested');
        $sourceEmail = OrganizationRoleplay::invalidEmail((string)$sourceUser->get('email'));
        roleplayCheck(isset($characters[$sourceEmail]), 'Append .invalid to the original visible email');
        $exported = OrganizationExport::build($org, $selected);
        $exportedCharacters = array_filter($exported['modules']['members']['records'], static fn ($item) => !empty($item['roleplayCharacter']));
        roleplayCheck(count($exportedCharacters) === count($characters), 'Export preserves the character marker');
        $imports[] = ['org' => $org, 'characters' => $characters];
    }
    $sourceUser->load($sourceUser->getId(), true);
    roleplayCheck(!$sourceUser->isRoleplayCharacter() && $sourceUser->get('firstname') === 'Original', 'Original accounts remain unchanged');
    $oid = (int)$imports[0]['org']->getId();
    $sourceEmail = OrganizationRoleplay::invalidEmail((string)$sourceUser->get('email'));
    $character = $imports[0]['characters'][$sourceEmail];
    roleplayCheck($character->get('email') !== $imports[1]['characters'][$sourceEmail]->get('email'), 'Separate identities for successive imports');
    $extraMembership = new UserOrganization();
    foreach (['IDorganization' => $oid, 'IDuser' => $extraAdmin->getId(), 'active' => true, 'parameters' => ['isAdmin' => true]] as $field => $value) { $extraMembership->set($field, $value); }
    roleplaySave($extraMembership);
    $admins = [(string)$actor->get('email'), (string)$extraAdmin->get('email')]; sort($admins);
    $_SESSION['currentOrganization'] = (int)$imports[1]['org']->getId();
    $routed = OrganizationRoleplay::routeMail(['to' => [$sourceEmail], 'cc' => [$sourceEmail], 'bcc' => [$sourceEmail]], $oid);
    $actual = $routed['to']; sort($actual);
    roleplayCheck($actual === $admins && !$routed['cc'] && !$routed['bcc'], 'Deduplicate administrators across To, Cc and Bcc');
    $routed = OrganizationRoleplay::routeMail(['to' => [(string)$character->get('email')]]);
    $actual = $routed['to']; sort($actual);
    roleplayCheck($actual === $admins, 'Background delivery uses character identity rather than the current session');
    roleplayDenied(fn () => OrganizationRoleplay::routeMail(['to' => [$sourceEmail]]));
    $normal = OrganizationRoleplay::routeMail(['to' => ['normal@example.org']], $oid);
    roleplayCheck($normal['to'] === ['normal@example.org'], 'Real recipients keep their address');
    $masked = OrganizationRoleplay::preparePayload(['authorEmail' => 'author@example.org', 'targets' => ['emails' => ['guest@example.org'], 'registeredEmails' => ['guest@example.org' => 'Guest']], 'description' => 'Text stays unchanged']);
    roleplayCheck($masked['authorEmail'] === 'author@example.org.invalid' && $masked['targets']['emails'] === ['guest@example.org.invalid'] && isset($masked['targets']['registeredEmails']['guest@example.org.invalid']), 'Mask structured external invitations and author addresses');
    roleplayCheck($masked['description'] === 'Text stays unchanged' && OrganizationRoleplay::invalidEmail($sourceEmail) === $sourceEmail, 'Do not rewrite free text or append .invalid twice');
    roleplayCheck(myHTMLMail('sender@example.org', [$sourceEmail, (string)$character->get('email')], 'Roleplay SMTP check', 'Character delivery', cc: [$sourceEmail], bcc: ['guest@example.org.invalid'], organizationId: $oid), 'Actual SMTP delivery');
    $messages = array_map(static fn ($line) => json_decode($line, true), file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    roleplayCheck(count($messages) === 1 && count($messages[0]['recipients']) === 2, 'One message to the two real administrators');
    foreach ($messages[0]['recipients'] as $recipient) {
        roleplayCheck(!str_contains($recipient, '.invalid') && !str_contains($recipient, (string)$sourceUser->get('email')), 'No SMTP envelope leaks to characters or original members');
    }
    $_SESSION['currentOrganization'] = $oid;
    $audience = ObjectAudience::resolve($oid, 'organization', $oid, [(int)$character->getId()]);
    $queued = ObjectMail::enqueue($oid, ['object_type' => 'organization', 'object_id' => $oid,
        'user_ids' => [(int)$character->getId()], 'subject' => 'Queued roleplay message', 'message' => 'Worker delivery',
        'request_key' => 'roleplay-worker-' . $token, 'audience_token' => $audience['audience_token']]);
    $_SESSION['currentOrganization'] = (int)$imports[1]['org']->getId();
    roleplayCheck(ObjectMail::processBatch(1, $queued['mail_id']) === 1, 'Process the actual mail queue');
    roleplayCheck(ObjectMail::status($oid, $queued['mail_id'])['delivery']['sent'] === 1, 'Queued character mail is delivered');
    $messages = array_map(static fn ($line) => json_decode($line, true), file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    roleplayCheck(count($messages) === 2 && $messages[1]['recipients'] === $messages[0]['recipients'], 'Worker delivery targets the same administrators, without session leakage');
    $actorMembership = new UserOrganization(); $actorMembership->load([['IDorganization', $oid], ['IDuser', $actor->getId()]]);
    $actorMembership->set('active', false); roleplaySave($actorMembership);
    $extraMembership->set('active', false); roleplaySave($extraMembership);
    roleplayCheck(!myHTMLMail('sender@example.org', $sourceEmail, 'No admin', 'Must not leave', organizationId: $oid), 'No fallback to source addresses when no administrator is available');
    roleplayCheck(count(file($capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) === 2, 'Failed routing never reaches SMTP');
    $regular = Organization::importOmo1ExportAsNewOrganization($sourcePayload, $selected, (int)$actor->getId(), 'Regular import', [], ['sendMemberInvitationEmails' => false]);
    roleplayCheck(!empty($regular['status']) && !$regular['organization']->getParameter('roleplay'), 'Regular imports retain their existing behavior');
    $regularMember = new UserOrganization(); $regularMember->load([['IDorganization', $regular['organization']->getId()], ['IDuser', $sourceUser->getId()]]);
    roleplayCheck(!$regularMember->get('active') && $regularMember->isOrganizationAdmin(), 'Regular import preserves pending membership and imported admin status');
    echo "organization_roleplay_test: OK\n";
} finally {
    if (is_resource($smtp)) { proc_terminate($smtp); foreach ($pipes as $pipe) { if (is_resource($pipe)) { fclose($pipe); } } proc_close($smtp); }
    @unlink($capture);
    DbObject::$_dbh = $originalDbh;
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    $_SESSION = $beforeSession;
}
