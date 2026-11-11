<?php
declare(strict_types=1);

// Isolated clock and persistence adapter: execute the real Document methods
// without requiring a running database or writing meeting data.
namespace dbObject {
    function microtime(bool $asFloat = false): float { return DbObject::$now / 1000; }

    class DbObject
    {
        public static int $now = 100000;
        public static array $row = ['pv_timer' => null, 'IDuser_pv_editor' => 7, 'active' => 1];
        public static bool $handoverOnWrite = false;
        public array $fields = [];
        public function get($key) { return $this->fields[$key] ?? null; }
        public function getId() { return 1; }
        public function load($id, $forced = false) { $this->fields = self::$row; return true; }
        public static function execute($query, $params = []) {
            if (self::$handoverOnWrite) self::$row['IDuser_pv_editor'] = 8;
            if (self::$row['IDuser_pv_editor'] === $params['editor']
                && (string)self::$row['pv_timer'] === $params['previous'] && self::$row['active'] === 1) {
                self::$row['pv_timer'] = $params['timer'];
            }
            return true;
        }
    }
    require dirname(__DIR__) . '/class/dbobject/objectvisibility.class.php';
    require dirname(__DIR__) . '/class/dbobject/document.class.php';

    class TimerDocument extends Document
    {
        public bool $validated = false;
        public function isPvDocument(): bool { return true; }
        public function isPvValidated(): bool { return $this->validated; }
        public function isPvEditor(int $userId): bool { return $userId > 0 && (int)$this->get('IDuser_pv_editor') === $userId; }
    }
}

namespace {
    use dbObject\{DbObject, TimerDocument};
    function check(bool $condition, string $message): void {
        if (!$condition) throw new RuntimeException($message);
    }
    $doc = new TimerDocument();
    $doc->load(1);
    $command = function (string $command, ?int $seconds = null, string $mode = 'countdown') use ($doc): bool {
        return $doc->updatePvTimer(7, $command, $doc->getPvTimerState()['revision'], $seconds, $mode);
    };
    check($doc->getPvTimerState()['milliseconds'] === 0, 'Initial time is zero.');
    check(!$doc->updatePvTimer(8, 'play', ''), 'Other participants cannot control the timer.');
    check($command('play', 0, 'stopwatch'), 'Start stopwatch.');
    DbObject::$now += 12345;
    check($command('pause'), 'Pause stopwatch.');
    check($doc->getPvTimerState()['milliseconds'] === 12345, 'Pause freezes elapsed time.');
    $run = $doc->getPvTimerState()['runId'];
    DbObject::$now += 60000;
    check($command('play'), 'Resume stopwatch.');
    check($doc->getPvTimerState()['runId'] === $run, 'Resume keeps the alarm identity.');
    DbObject::$now += 655;
    check($command('pause'), 'Pause after resuming.');
    check($doc->getPvTimerState()['milliseconds'] === 13000, 'Paused time is excluded.');
    check($command('edit', 65), 'Manual edit prepares countdown while paused.');
    check($doc->getPvTimerState()['status'] === 'paused', 'Editing preserves pause.');
    check($doc->getPvTimerState()['mode'] === 'countdown', 'Manual edit selects countdown.');
    check($command('play'), 'Start countdown.');
    DbObject::$now += 61000;
    check($command('pause'), 'Pause countdown.');
    check($doc->getPvTimerState()['milliseconds'] === 4000, 'Countdown subtracts elapsed time.');
    check($command('play'), 'Resume countdown.');
    DbObject::$now += 10000;
    check($command('pause'), 'Pause after expiry.');
    check($doc->getPvTimerState()['milliseconds'] === 0, 'Countdown never becomes negative.');
    check($command('stop'), 'Stop resets timer.');
    check($doc->getPvTimerState()['status'] === 'stopped' && $doc->getPvTimerState()['milliseconds'] === 0, 'Stopped at zero.');
    check(!$command('pause'), 'Cannot pause a stopped timer.');
    check(!$command('edit', -1) && !$command('edit', 360000), 'Reject invalid durations.');
    check(!$doc->updatePvTimer(7, 'play', 'outdated'), 'Reject stale browser revision.');
    check($command('play', 180, 'countdown'), 'Start selected point duration.');
    DbObject::$now += 1200;
    check($command('edit', 90), 'Editing a running timer changes its remaining time.');
    check($doc->getPvTimerState()['milliseconds'] === 90000 && $doc->getPvTimerState()['status'] === 'playing', 'Editing preserves playback.');
    $doc->validated = true;
    check(!$command('stop'), 'Validated document is read-only.');
    $doc->validated = false;
    DbObject::$handoverOnWrite = true;
    check(!$command('stop'), 'Atomic editor guard rejects a handover during a command.');
    check($doc->getPvTimerState()['status'] === 'playing', 'Rejected handover command preserves the timer.');
    echo "pv_timer_test: OK\n";
}
