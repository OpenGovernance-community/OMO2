<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/common/openai_audio.php';
function audioCheck(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function audioReject(callable $operation, string $message): void
{
    try { $operation(); }
    catch (RuntimeException $error) { audioCheck(str_contains($error->getMessage(), $message), 'Unexpected audio error'); return; }
    throw new RuntimeException('Expected audio rejection');
}
$step = 0;
$result = commonInfomaniakWaitForTranscription('batch-1', 'fixture-key', '456', microtime(true) + 5,
    static function ($path, $key) use (&$step): array {
        audioCheck($path === '/1/ai/456/results/batch-1' && $key === 'fixture-key', 'Poll only the configured product with its own credentials');
        return match (++$step) { 1 => ['status' => 'pending'], 2 => ['status' => 'processing'], default => ['status' => 'success', 'data' => ['text' => 'Bonjour OMO']] };
    });
audioCheck($step === 3 && $result['text'] === 'Bonjour OMO', 'Wait for a complete asynchronous transcript');
$paths = [];
$downloaded = commonInfomaniakWaitForTranscription('batch-2', 'fixture-key', '456', microtime(true) + 5,
    static function ($path) use (&$paths): array|string {
        $paths[] = $path;
        return str_ends_with($path, '/download') ? '{"text":"Downloaded transcript"}'
            : ['status' => 'success', 'data' => null, 'url' => 'https://untrusted.invalid/never-follow'];
    });
audioCheck($downloaded['text'] === 'Downloaded transcript' && $paths === ['/1/ai/456/results/batch-2', '/1/ai/456/results/batch-2/download'], 'Download through the trusted product route, ignoring arbitrary returned URLs');
foreach (['failed', 'cancelled', 'unknown'] as $status) {
    audioReject(fn () => commonInfomaniakWaitForTranscription('batch-3', 'fixture-key', '456', microtime(true) + 5, static fn () => ['status' => $status]), $status === 'unknown' ? 'invalide' : 'échoué');
}
audioReject(fn () => commonInfomaniakWaitForTranscription('batch-3', 'fixture-key', '456', microtime(true) - 1, static fn () => []), 'délai imparti');
audioReject(fn () => commonInfomaniakWaitForTranscription('../bad', 'fixture-key', '456', microtime(true) + 5, static fn () => []), 'Identifiant');
audioReject(fn () => commonInfomaniakWaitForTranscription('batch-3', 'fixture-key', '456', microtime(true) + 5, static fn () => ['status' => 'success', 'data' => ['text' => '']]), 'vide');
echo "PASS Infomaniak asynchronous transcription, trusted download, failure and deadline handling\n";
