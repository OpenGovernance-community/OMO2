<?php
declare(strict_types=1);

// Real session/admin authorization with in-memory users and Patreon storage.
namespace dbObject {
    final class User {
        private int $id = 0;
        public function load($id): bool { $this->id = (int)$id; return $this->id > 0; }
        public function isSiteAdmin(): bool { return $this->id === 2; }
    }
    final class UserPatreon {
        public static bool $storageAvailable = false;
        public static array $connections = [];
        public static int $reads = 0;
        public function __construct(private array $fields) {}
        public static function isStorageAvailable(): bool { self::$reads++; return self::$storageAvailable; }
        public static function findByUserId($id): self|false { self::$reads++; return self::$connections[(int)$id] ?? false; }
        public function isConnected(): bool { return !empty($this->fields['connected']); }
        public function get($key): mixed { return $this->fields[$key] ?? null; }
    }
}

namespace {
    // Configuration errors must not load translation bundles from the database.
    function githubBugReportT(string $key, array $variables = []): string { return $key; }
    require_once dirname(__DIR__) . '/common/auth.php';
    require_once dirname(__DIR__) . '/common/ai_access.php';
    require_once dirname(__DIR__) . '/common/github_bug_report.php';

    function patreonAdminExpect(bool $condition, string $message): void {
        if (!$condition) throw new \RuntimeException($message);
    }
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $_SESSION = ['currentUser' => 2, 'isSiteAdminModeEnabled' => true];
    foreach (['patreonClientId', 'patreonClientSecret', 'patreonCreatorCampaignId', 'patreonCreatorUserId', 'patreonConnectUrl', 'patreonRedirectUri', 'patreonConnectAllowedOrigins'] as $key) {
        $GLOBALS[$key] = '';
    }
    $GLOBALS['patreonClientId'] = 'partial-patreon-config';
    $GLOBALS['githubBugReportToken'] = 'test-token-never-sent';
    $_ENV['AI_PROVIDER'] = 'openai';
    $_ENV['TRANSCRIPTION_PROVIDER'] = 'openai';
    $_ENV['TRANSCRIPTION_API_KEY'] = 'test-audio-key-never-sent';
    $_ENV['TRANSCRIPTION_MODEL'] = 'test-audio-model';

    patreonAdminExpect(patreonUserCanUseAi(2), 'Active super admin bypasses incomplete Patreon configuration.');
    patreonAdminExpect(patreonCanManageOrganizationRouting(2), 'Active super admin bypasses the routing contribution threshold.');
    patreonAdminExpect(githubBugReportUserCanSubmit(2), 'Active super admin can report bugs without a Patreon account.');
    patreonAdminExpect(\dbObject\UserPatreon::$reads === 0, 'The bypass does not require Patreon storage or a connection.');
    foreach ([0, 1, 3] as $id) {
        patreonAdminExpect(!patreonUserCanUseAi($id), 'The session override never grants AI to another or anonymous user.');
        patreonAdminExpect(!patreonCanManageOrganizationRouting($id), 'The session override never grants routing to another user.');
        patreonAdminExpect(!githubBugReportUserCanSubmit($id), 'The session override never grants bug reporting to another user.');
    }
    if (function_exists('curl_init')) {
        patreonAdminExpect(commonAiUserCanUse(2, 'test-model', 'test-key-never-sent'), 'Configured text AI is available to the active super admin.');
        patreonAdminExpect(commonAiUserCanTranscribe(2), 'Configured dictation is available to the active super admin.');
        patreonAdminExpect(githubBugReportUiIsEnabled(), 'The bug report form needs no Patreon configuration for the active super admin.');
    }
    patreonAdminExpect(!commonAiUserCanUse(2, 'test-model', ''), 'Super admin still needs an AI key.');
    $_ENV['AI_PROVIDER'] = 'disabled';
    $_ENV['TRANSCRIPTION_PROVIDER'] = 'disabled';
    patreonAdminExpect(!commonAiUserCanUse(2, 'test-model', 'test-key-never-sent') && !commonAiUserCanTranscribe(2), 'Super admin respects disabled AI services.');
    $GLOBALS['githubBugReportToken'] = '';
    patreonAdminExpect(!githubBugReportUiIsEnabled(), 'Super admin still needs GitHub configuration.');

    unset($_SESSION['isSiteAdminModeEnabled']);
    patreonAdminExpect(!patreonUserCanUseAi(2) && !patreonCanManageOrganizationRouting(2) && !githubBugReportUserCanSubmit(2), 'Disabling super admin restores all Patreon restrictions immediately.');
    $_SESSION = ['currentUser' => 1, 'isSiteAdminModeEnabled' => true, 'isAdminByOrganization' => [42 => true]];
    patreonAdminExpect(!patreonUserCanUseAi(1) && !patreonCanManageOrganizationRouting(1) && !githubBugReportUserCanSubmit(1), 'An organization admin or forged mode flag cannot bypass Patreon.');
    \dbObject\UserPatreon::$storageAvailable = true;
    \dbObject\UserPatreon::$connections[1] = new \dbObject\UserPatreon([
        'connected' => true, 'patron_status' => 'active_patron', 'currently_entitled_amount_cents' => 2500
    ]);
    $GLOBALS['patreonClientSecret'] = 'test-secret';
    patreonAdminExpect(patreonUserCanUseAi(1) && patreonCanManageOrganizationRouting(1) && githubBugReportUserCanSubmit(1), 'Ordinary connected contributors keep their existing access.');
    \dbObject\UserPatreon::$connections[1] = new \dbObject\UserPatreon([
        'connected' => true, 'patron_status' => 'former_patron', 'currently_entitled_amount_cents' => 0
    ]);
    patreonAdminExpect(!patreonUserCanUseAi(1) && !patreonCanManageOrganizationRouting(1) && githubBugReportUserCanSubmit(1), 'An unpaid connected user retains only bug reporting.');
    echo "PASS super admin Patreon bypass, inactive/other-user guards, service configuration and ordinary entitlements\n";
}
