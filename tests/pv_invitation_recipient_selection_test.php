<?php
declare(strict_types=1);

// Exercise the real popup with isolated permissions, recipients and mail delivery.
// No database writes, share links or SMTP calls leave this fixture.
namespace dbObject {
    class Event
    {
        public function getInvitationEmailRecipients(int $organizationId = 0): array { return $GLOBALS['fixtureRecipients']; }
    }
    class Document
    {
        public const PV_STAGE_PREPARATION = 'preparation';
        public function load($id): bool { return true; }
        public function get($key) { return $key === 'IDorganization' ? 1 : 'Fixture meeting'; }
        public function getId(): int { return 2; }
        public function isPvDocument(): bool { return true; }
        public function getPvStage(): string { return self::PV_STAGE_PREPARATION; }
        public function canUserManagePvStructure($oid, $uid): bool { return $GLOBALS['fixtureCanSend']; }
        public function getAssociatedEvent() { return $GLOBALS['fixtureWithEvent'] ? new Event() : null; }
        public function getInvitationEmailRecipients(int $oid): array { return $GLOBALS['fixtureRecipients']; }
    }
    class Organization
    {
        public function load($id): bool { return true; }
        public function get($key): string { return $key === 'name' ? 'Fixture organization' : ''; }
        public static function formatLexiconText($text): string { return $text; }
    }
    class DocumentShareLink
    {
        public string $email;
        public static function getOrCreatePvParticipantLink($document, $uid, $email, $recipientUserId): self
        {
            $GLOBALS['fixtureLinks'][] = ['email' => $email, 'user_id' => $recipientUserId];
            $link = new self();
            $link->email = $email;
            return $link;
        }
        public function buildPvParticipationUrl(): string { return '/fixture/' . rawurlencode($this->email); }
    }
    class ObjectMail
    {
        public static function beginPvInvitationDelivery($document, $subject, $message, $recipients): self
        {
            if ($GLOBALS['fixtureHistoryError']) { throw new \RuntimeException('Fixture history failure'); }
            $GLOBALS['fixtureHistory'] = ['subject' => $subject, 'message' => $message, 'recipients' => $recipients, 'delivery' => []];
            return new self();
        }
        public function recordRecipientDelivery($email, $status): void { $GLOBALS['fixtureHistory']['delivery'][$email] = $status; }
        public function getId(): int { return 101; }
    }
}
namespace {
    function omoLoadTranslationBundle($key, $source): array { return []; }
    function omoApiEscape($text): string { return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function commonGetCurrentUserId(): int { return 7; }
    function commonGetRequestHost(): string { return 'example.invalid'; }
    function commonBuildUrl($path, $host): string { return 'https://' . $host . $path; }
    function myHTMLMail($from, $to, $subject, $html): bool
    {
        $GLOBALS['fixtureMails'][] = ['email' => $to, 'html' => $html];
        if (in_array($to, $GLOBALS['fixtureUnknownEmails'], true)) { throw new \RuntimeException('Fixture uncertain delivery'); }
        return !in_array($to, $GLOBALS['fixtureFailedEmails'], true);
    }
    function pvRecipientExpect(bool $condition, string $message): void
    {
        if (!$condition) { throw new \RuntimeException($message); }
    }

    $root = dirname(__DIR__);
    if (in_array($argv[1] ?? '', ['--request', '--render'], true)) {
        require $root . '/common/translation_bundles.php';
        $scenario = ($argv[1] === '--request') ? json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR) : [];
        $GLOBALS['fixtureRecipients'] = [
            ['email' => 'alice@example.invalid', 'display_name' => 'Alice', 'user_id' => 11],
            ['email' => 'bob@example.invalid', 'display_name' => 'Bob <guest>', 'user_id' => 12],
            ['email' => 'guest@example.invalid', 'display_name' => '', 'user_id' => 0],
        ];
        $GLOBALS['fixtureCanSend'] = $scenario['canSend'] ?? true;
        $GLOBALS['fixtureWithEvent'] = $scenario['withEvent'] ?? true;
        $GLOBALS['fixtureHistoryError'] = $scenario['historyError'] ?? false;
        $GLOBALS['fixtureFailedEmails'] = $scenario['failedEmails'] ?? [];
        $GLOBALS['fixtureUnknownEmails'] = $scenario['unknownEmails'] ?? [];
        $GLOBALS['fixtureHistory'] = null;
        $GLOBALS['fixtureLinks'] = $GLOBALS['fixtureMails'] = [];
        $GLOBALS['mailUser'] = 'fixture@example.invalid';
        $_REQUEST = ['id' => 2, 'oid' => 1];
        $_SERVER['REQUEST_METHOD'] = $argv[1] === '--render' ? 'GET' : 'POST';
        $_POST = $scenario['post'] ?? [];
        if ($argv[1] === '--request') {
            ob_start();
            register_shutdown_function(static function (): void {
                $response = json_decode((string)ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
                echo json_encode(['code' => http_response_code(), 'response' => $response,
                    'links' => $GLOBALS['fixtureLinks'], 'mails' => $GLOBALS['fixtureMails'], 'history' => $GLOBALS['fixtureHistory']], JSON_THROW_ON_ERROR);
            });
        }
        $source = file_get_contents($root . '/omo/api/documents/pv/send_invitations_popup.php');
        $source = str_replace("require_once dirname(__DIR__, 2) . '/bootstrap.php';", '', $source);
        $source = str_replace("dirname(__DIR__, 4) . '/common/email_layout.php'", var_export($root . '/common/email_layout.php', true), $source);
        eval('?>' . $source);
        exit;
    }

    $request = static function (array $post, array $options = []): array {
        $scenario = $options + ['post' => $post];
        $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($scenario, JSON_THROW_ON_ERROR))],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        pvRecipientExpect(is_resource($process), 'Could not start fixture request.');
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
        pvRecipientExpect(proc_close($process) === 0, 'Fixture request failed: ' . $errors . $output);
        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    };
    $emails = ['alice@example.invalid', 'bob@example.invalid', 'guest@example.invalid'];
    foreach ([true, false] as $withEvent) {
        foreach ([$emails, [$emails[1]], [$emails[0], $emails[2]], [$emails[1], $emails[1]]] as $selected) {
            $result = $request(['message' => 'Selected invitation', 'recipient_emails' => $selected], ['withEvent' => $withEvent]);
            $expected = array_values(array_unique($selected));
            pvRecipientExpect($result['code'] === 200 && $result['response']['status'], 'Selected invitations should succeed.');
            pvRecipientExpect(array_column($result['mails'], 'email') === $expected, 'Only selected people receive one email each.');
            pvRecipientExpect(array_column($result['links'], 'email') === $expected, 'Only selected people receive individual access links.');
            pvRecipientExpect(array_column($result['history']['recipients'], 'email') === $expected, 'History contains only selected people.');
            pvRecipientExpect(array_keys($result['history']['delivery']) === $expected && count(array_unique($result['history']['delivery'])) === 1
                && reset($result['history']['delivery']) === 'sent', 'History records successful individual deliveries.');
            pvRecipientExpect($result['history']['message'] === 'Selected invitation' && $result['response']['mail_id'] === 101, 'Original message is kept and the send returns its history ID.');
            foreach ($result['mails'] as $mail) {
                pvRecipientExpect(str_contains($mail['html'], '/fixture/' . rawurlencode($mail['email'])), 'Each message uses its own participation link.');
            }
        }
    }
    foreach ([[], ['recipient_emails' => []], ['recipient_emails' => 'alice@example.invalid'],
        ['recipient_emails' => [['alice@example.invalid']]], ['recipient_emails' => [$emails[0], 'outsider@example.invalid']]] as $selection) {
        $result = $request(['message' => 'Fixture message'] + $selection);
        pvRecipientExpect($result['code'] === 422 && !$result['response']['status'], 'Empty or unauthorized selections must be rejected.');
        pvRecipientExpect(!$result['mails'] && !$result['links'], 'Invalid selections must cause no delivery or link creation.');
        pvRecipientExpect($result['history'] === null, 'Invalid selection does not create a history entry.');
    }
    $denied = $request(['message' => 'Fixture message', 'recipient_emails' => $emails], ['canSend' => false]);
    pvRecipientExpect($denied['code'] === 403 && !$denied['mails'] && !$denied['links'], 'Selecting recipients cannot bypass PV management permissions.');
    $historyFailure = $request(['message' => 'Fixture message', 'recipient_emails' => $emails], ['historyError' => true]);
    pvRecipientExpect($historyFailure['code'] === 500 && !$historyFailure['mails'] && !$historyFailure['links'], 'History persistence must succeed before any invitation is sent.');
    $partial = $request(['message' => 'Fixture message', 'recipient_emails' => $emails],
        ['failedEmails' => [$emails[1]], 'unknownEmails' => [$emails[2]]]);
    pvRecipientExpect($partial['history']['delivery'] === [$emails[0] => 'sent', $emails[1] => 'failed', $emails[2] => 'unknown'],
        'History distinguishes accepted, failed and uncertain sends.');
    echo "pv_invitation_recipient_selection_test: OK\n";
}
