<?php
declare(strict_types=1);

// Exercise the actual endpoint in isolated CLI processes, with no mail, network or database.
namespace dbObject {
    final class FAQ {
        public static function resolvePopupRequestContext($request): array { return ['organizationId' => 7]; }
        public static function hasFaqTable(): bool { return true; }
        public static function hasRequestColumns(): bool { return true; }
        public static function normalizePopupScope($scope, $context): string { return $scope ?? 'contextual'; }
        public static function loadAiSources($context, $scope): array {
            $GLOBALS['trace']['scope'] = $scope;
            return [['id' => 42, 'question' => 'Published FAQ']];
        }
        public static function hasParcoursColumn(): bool { return true; }
        public static function hasApplicationColumn(): bool { return true; }
        public static function getSystemAdminEmails(): array { return ['admin@example.invalid']; }
        public function set($key, $value): void { $GLOBALS['trace']['fields'][$key] = $value; }
        public function getId(): int { return 55; }
        public function save(): array { $GLOBALS['trace']['saves']++; return ['status' => true]; }
    }
    final class User {
        public function load($id): bool { return true; }
        public function getScopedEmail($oid): string { return 'author@example.invalid'; }
        public function getScopedDisplayName($oid): string { return 'Author'; }
    }
}

namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    function checkLogin(): bool { return true; }
    function commonGetCurrentUserId(): int { return 1; }
    function commonAiSetUsageContext($uid, $oid, $feature): void { $GLOBALS['trace']['feature'] = $feature; }
    function commonOpenAiGetRewriteModel(): string { return 'fixture'; }
    function commonAiUserCanUse($uid, $model): bool { return $GLOBALS['scenario'] !== 'no_access'; }
    function faqAiFindAnswer($question, $description, $sources, $request, $uid): array {
        $GLOBALS['trace']['ai']++;
        if ($GLOBALS['scenario'] === 'failure') throw new RuntimeException('Simulated failure');
        return in_array($GLOBALS['scenario'], ['unsupported', 'failure'], true)
            ? ['answer' => '', 'sources' => []]
            : ['answer' => 'Found answer', 'sources' => [['type' => 'faq', 'id' => 42, 'title' => 'Published FAQ']]];
    }
    function faqAiT($key): string { return $key === 'sent' ? 'Question envoyee a un administrateur.' : $key; }
    function appBuildAbsoluteUrl($path): string { return 'https://example.invalid' . $path; }
    function faqMailRenderQuestionRequest($faq, $url, $organization): string { return 'Fixture mail'; }
    function myHTMLMail($from, $to, $subject, $body): bool { $GLOBALS['trace']['mails']++; return true; }

    if (($argv[1] ?? '') === 'worker') {
        $GLOBALS['scenario'] = $argv[2];
        $GLOBALS['trace'] = ['ai' => 0, 'saves' => 0, 'mails' => 0];
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'HTTP_HOST' => 'example.invalid', 'HTTP_ORIGIN' => 'https://example.invalid'];
        $_GET = ['oid' => 7, 'faq_scope' => 'descendants'];
        $_POST = ['question' => 'Original question', 'request_description' => 'Original description',
            'action' => $GLOBALS['scenario'] === 'escalate' ? 'send_admin' : 'search'];
        if ($GLOBALS['scenario'] === 'foreign_origin') $_SERVER['HTTP_ORIGIN'] = 'https://evil.invalid';
        register_shutdown_function(static function () {
            fwrite(STDERR, 'TRACE:' . json_encode($GLOBALS['trace'] + ['http_status' => http_response_code() ?: 200]));
        });
        // Replace only the bootstrap imports; the endpoint control flow runs unchanged.
        $source = file_get_contents(dirname(__DIR__) . '/ajax/faq_question_request.php');
        $source = preg_replace('/^require_once\([^\r\n]+\);\R/m', '', $source);
        eval('?>' . $source);
        exit;
    }

    foreach (['answered', 'unsupported', 'no_access', 'escalate', 'failure', 'foreign_origin'] as $scenario) {
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', $scenario], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exit = proc_close($process);
        $response = json_decode($output, true);
        $trace = json_decode(explode('TRACE:', $errors)[1] ?? '', true);
        if ($exit !== 0 || !is_array($response) || !is_array($trace)) throw new RuntimeException($scenario . ': ' . $output . $errors);
        $assert = static function (bool $condition, string $message) use ($scenario): void {
            if (!$condition) throw new RuntimeException($scenario . ': ' . $message);
        };
        if ($scenario === 'foreign_origin') {
            $assert($trace['http_status'] === 403 && $trace['ai'] === 0 && $trace['saves'] === 0 && $trace['mails'] === 0, 'Cross-origin requests must have no side effects.');
        } elseif ($scenario === 'answered') {
            $assert($response['status'] === 'answered' && $response['answer'] === 'Found answer', 'Return the answer with its references.');
            $assert($trace['ai'] === 1 && $trace['scope'] === 'descendants' && $trace['saves'] === 0 && $trace['mails'] === 0, 'Answered searches never create pending requests or mail administrators.');
        } else {
            $assert($response['status'] === 'submitted' && $trace['saves'] === 1 && $trace['mails'] === 1, 'Unresolved questions are saved and sent exactly once.');
            $assert($trace['fields']['question'] === 'Original question' && $trace['fields']['request_description'] === 'Original description'
                && $trace['fields']['answer'] === '' && $trace['fields']['isactive'] === false, 'Keep the original question pending without publishing an AI answer.');
            if (in_array($scenario, ['no_access', 'escalate'], true)) $assert($trace['ai'] === 0, 'No AI call when access is denied or the user explicitly escalates.');
        }
    }
    echo "PASS FAQ endpoint answer without admin side effects, fallback, escalation, access and CSRF\n";
}
