<?php
namespace dbObject;

final class CalDavCache
{
    private const DEFAULT_OBSOLETE_GRACE_SECONDS = 3600;
    private const MAX_RESPONSE_BYTES = 20971520;
    private static $scheduledOrganizationIds = array();
    private static $shutdownInvalidationRegistered = false;
    private static $runningScheduledInvalidations = false;

    public static function getRootPath()
    {
        $configuredPath = function_exists('envValue')
            ? trim((string)envValue('CALDAV_CACHE_PATH', ''))
            : trim((string)getenv('CALDAV_CACHE_PATH'));

        if ($configuredPath !== '') {
            return rtrim(str_replace('\\', '/', $configuredPath), '/');
        }

        $installationKey = substr(hash('sha256', dirname(__DIR__, 2)), 0, 12);
        return rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/')
            . '/omo-caldav-cache-' . $installationKey;
    }

    public static function getOrganizationRevision($organizationId)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return 0;
        }

        $organizationPath = self::getOrganizationPath($organizationId);
        if (!self::ensureDirectory($organizationPath)) {
            return 0;
        }

        $revisionPath = $organizationPath . '/current-revision.txt';
        $handle = @fopen($revisionPath, 'c+');
        if (!is_resource($handle)) {
            return 0;
        }

        $revision = 1;
        if (@flock($handle, LOCK_SH)) {
            rewind($handle);
            $storedRevision = (int)trim((string)stream_get_contents($handle));
            if ($storedRevision > 0) {
                @flock($handle, LOCK_UN);
                fclose($handle);
                return $storedRevision;
            }
            @flock($handle, LOCK_UN);
        }

        if (@flock($handle, LOCK_EX)) {
            rewind($handle);
            $storedRevision = (int)trim((string)stream_get_contents($handle));
            if ($storedRevision > 0) {
                $revision = $storedRevision;
            } else {
                rewind($handle);
                @ftruncate($handle, 0);
                @fwrite($handle, "1\n");
                @fflush($handle);
            }
            @flock($handle, LOCK_UN);
        }
        fclose($handle);

        return $revision;
    }

    public static function invalidateOrganization($organizationId)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return 0;
        }

        $organizationPath = self::getOrganizationPath($organizationId);
        if (!self::ensureDirectory($organizationPath)) {
            return 0;
        }

        $revisionPath = $organizationPath . '/current-revision.txt';
        $handle = @fopen($revisionPath, 'c+');
        if (!is_resource($handle)) {
            return 0;
        }

        $revision = 0;
        if (@flock($handle, LOCK_EX)) {
            rewind($handle);
            $currentRevision = (int)trim((string)stream_get_contents($handle));
            $revision = max(1, $currentRevision) + 1;
            rewind($handle);
            @ftruncate($handle, 0);
            @fwrite($handle, (string)$revision . "\n");
            @fflush($handle);
            $obsoleteRevisionPath = $organizationPath . '/revision-' . max(1, $currentRevision);
            if (is_dir($obsoleteRevisionPath)) {
                @touch($obsoleteRevisionPath);
            }
            @flock($handle, LOCK_UN);
        }
        fclose($handle);

        if ($revision > 0) {
            self::schedulePostTransactionInvalidation($organizationId);
        }

        return $revision;
    }

    public static function buildResponseKey(array $context)
    {
        ksort($context);
        return hash('sha256', json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public static function readResponse($organizationId, $revision, $key)
    {
        $path = self::getResponsePath($organizationId, $revision, $key);
        if ($path === '' || !is_file($path)) {
            return null;
        }

        $payload = @file_get_contents($path);
        if (!is_string($payload) || $payload === '' || strlen($payload) > self::MAX_RESPONSE_BYTES) {
            return null;
        }

        $response = @unserialize($payload, array('allowed_classes' => false));
        if (
            !is_array($response)
            || !isset($response['status'], $response['headers'], $response['body'])
            || !is_int($response['status'])
            || !is_array($response['headers'])
            || !is_string($response['body'])
        ) {
            return null;
        }

        return $response;
    }

    public static function writeResponse($organizationId, $revision, $key, array $response)
    {
        $status = (int)($response['status'] ?? 0);
        $headers = $response['headers'] ?? null;
        $body = $response['body'] ?? null;
        if ($status < 200 || $status >= 300 || !is_array($headers) || !is_string($body)) {
            return false;
        }

        $payload = serialize(array(
            'status' => $status,
            'headers' => self::filterHeaders($headers),
            'body' => $body,
        ));
        if (strlen($payload) > self::MAX_RESPONSE_BYTES) {
            return false;
        }

        $path = self::getResponsePath($organizationId, $revision, $key);
        if ($path === '') {
            return false;
        }

        $directory = dirname($path);
        if (!self::ensureDirectory($directory)) {
            return false;
        }

        $temporaryPath = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($temporaryPath, $payload, LOCK_EX) === false) {
            return false;
        }
        @chmod($temporaryPath, 0600);

        if (is_file($path)) {
            @unlink($temporaryPath);
            return true;
        }

        if (!@rename($temporaryPath, $path)) {
            @unlink($temporaryPath);
            return false;
        }

        return true;
    }

    public static function acquireBuildLock($organizationId, $revision, $key, $waitMilliseconds = 8000)
    {
        $responsePath = self::getResponsePath($organizationId, $revision, $key);
        if ($responsePath === '' || !self::ensureDirectory(dirname($responsePath))) {
            return null;
        }

        $handle = @fopen($responsePath . '.lock', 'c');
        if (!is_resource($handle)) {
            return null;
        }

        $deadline = microtime(true) + (max(0, (int)$waitMilliseconds) / 1000);
        do {
            if (@flock($handle, LOCK_EX | LOCK_NB)) {
                @touch($responsePath . '.lock');
                return $handle;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);

        fclose($handle);
        return null;
    }

    public static function releaseBuildLock($handle)
    {
        if (!is_resource($handle)) {
            return;
        }

        @flock($handle, LOCK_UN);
        fclose($handle);
    }

    public static function sendResponse(array $response, $cacheStatus = 'HIT')
    {
        http_response_code((int)($response['status'] ?? 200));
        foreach (self::filterHeaders((array)($response['headers'] ?? array())) as $header) {
            header($header, true);
        }
        header('X-OMO-CalDAV-Cache: ' . trim((string)$cacheStatus), true);
        echo (string)($response['body'] ?? '');
    }

    public static function cleanup($obsoleteGraceSeconds = self::DEFAULT_OBSOLETE_GRACE_SECONDS)
    {
        $rootPath = self::getRootPath();
        if (!is_dir($rootPath)) {
            return 0;
        }

        $obsoleteThreshold = time() - max(60, (int)$obsoleteGraceSeconds);
        $deleted = 0;
        $organizationPaths = glob($rootPath . '/org-*', GLOB_ONLYDIR);
        if (!is_array($organizationPaths)) {
            return 0;
        }

        foreach ($organizationPaths as $organizationPath) {
            if (!preg_match('/^org-\d+$/', basename($organizationPath))) {
                continue;
            }

            $organizationId = (int)substr(basename($organizationPath), 4);
            $currentRevision = self::getOrganizationRevision($organizationId);
            $revisionPaths = glob($organizationPath . '/revision-*', GLOB_ONLYDIR);
            if (!is_array($revisionPaths)) {
                continue;
            }

            foreach ($revisionPaths as $revisionPath) {
                if (!preg_match('/^revision-(\d+)$/', basename($revisionPath), $matches)) {
                    continue;
                }

                $revision = (int)$matches[1];
                $modifiedAt = @filemtime($revisionPath);
                if ($revision !== $currentRevision && is_int($modifiedAt) && $modifiedAt < $obsoleteThreshold) {
                    $deleted += self::removeDirectory($revisionPath);
                }
            }
        }

        return $deleted;
    }

    private static function filterHeaders(array $headers)
    {
        $filtered = array();
        $excluded = array(
            'connection',
            'content-length',
            'date',
            'server',
            'set-cookie',
            'transfer-encoding',
            'x-omo-caldav-cache',
        );

        foreach ($headers as $header) {
            $header = trim((string)$header);
            $separatorPosition = strpos($header, ':');
            if ($header === '' || $separatorPosition === false) {
                continue;
            }

            $name = strtolower(trim(substr($header, 0, $separatorPosition)));
            if ($name === '' || in_array($name, $excluded, true)) {
                continue;
            }
            $filtered[] = $header;
        }

        return $filtered;
    }

    private static function getOrganizationPath($organizationId)
    {
        return self::getRootPath() . '/org-' . (int)$organizationId;
    }

    private static function getResponsePath($organizationId, $revision, $key)
    {
        $organizationId = (int)$organizationId;
        $revision = (int)$revision;
        $key = strtolower(trim((string)$key));
        if ($organizationId <= 0 || $revision <= 0 || !preg_match('/^[a-f0-9]{64}$/', $key)) {
            return '';
        }

        return self::getOrganizationPath($organizationId)
            . '/revision-' . $revision
            . '/' . $key . '.cache';
    }

    private static function ensureDirectory($path)
    {
        return is_dir($path) || @mkdir($path, 0700, true) || is_dir($path);
    }

    private static function schedulePostTransactionInvalidation($organizationId)
    {
        if (self::$runningScheduledInvalidations || !class_exists(DbObject::class, false)) {
            return;
        }

        try {
            $pdo = DbObject::getPdo();
        } catch (\Throwable $exception) {
            return;
        }

        if (!($pdo instanceof \PDO) || !$pdo->inTransaction()) {
            return;
        }

        self::$scheduledOrganizationIds[(int)$organizationId] = true;
        if (self::$shutdownInvalidationRegistered) {
            return;
        }

        self::$shutdownInvalidationRegistered = true;
        register_shutdown_function(static function () {
            self::$runningScheduledInvalidations = true;
            $organizationIds = array_keys(self::$scheduledOrganizationIds);
            self::$scheduledOrganizationIds = array();
            foreach ($organizationIds as $organizationId) {
                self::invalidateOrganization((int)$organizationId);
            }
            self::$runningScheduledInvalidations = false;
        });
    }

    private static function removeDirectory($path)
    {
        if (!is_dir($path) || !preg_match('/^revision-\d+$/', basename($path))) {
            return 0;
        }

        $deleted = 0;
        $items = scandir($path);
        if (!is_array($items)) {
            return 0;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;
            if (is_file($itemPath) && @unlink($itemPath)) {
                $deleted++;
            }
        }

        @rmdir($path);
        return $deleted;
    }
}
