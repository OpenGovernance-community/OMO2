<?php
require_once dirname(__DIR__) . '/config.php';

function helloAssoIsEnabled(): bool
{
    foreach (['helloAssoClientId', 'helloAssoClientSecret', 'helloAssoOrganizationSlug'] as $key) {
        if (trim((string)($GLOBALS[$key] ?? '')) !== '') return true;
    }
    return false;
}

function helloAssoIsConfigured(): bool
{
    return trim((string)($GLOBALS['helloAssoClientId'] ?? '')) !== ''
        && trim((string)($GLOBALS['helloAssoClientSecret'] ?? '')) !== ''
        && trim((string)($GLOBALS['helloAssoOrganizationSlug'] ?? '')) !== '';
}

function helloAssoConfigurationKey(bool $includeForms = true): string
{
    $values = [$GLOBALS['helloAssoClientId'] ?? '', $GLOBALS['helloAssoClientSecret'] ?? '',
        $GLOBALS['helloAssoOrganizationSlug'] ?? '', !empty($GLOBALS['helloAssoSandbox'])];
    if ($includeForms) $values[] = helloAssoAllowedForms();
    return hash('sha256', json_encode($values, JSON_THROW_ON_ERROR));
}

/** Empty means donation forms only. Explicit values are FormType/form-slug. */
function helloAssoAllowedForms(): array
{
    $forms = array_values(array_filter(array_map('trim', explode(',', (string)($GLOBALS['helloAssoForms'] ?? '')))));
    sort($forms);
    return $forms;
}

function helloAssoRequest(string $method, string $path, array $parameters = [], string $token = ''): array
{
    $base = !empty($GLOBALS['helloAssoSandbox']) ? 'https://api.helloasso-sandbox.com' : 'https://api.helloasso.com';
    $url = $base . $path;
    if ($method === 'GET' && $parameters) $url .= '?' . http_build_query($parameters);
    $curl = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;
    if ($method === 'POST') $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12, CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'OMO HelloAsso contribution sync', CURLOPT_CUSTOMREQUEST => $method]);
    if ($method === 'POST') curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($parameters));
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if ($body === false) throw new RuntimeException('HelloAsso network error (' . curl_errno($curl) . ').');
    // Never log API response bodies, credentials or payer details.
    if ($status < 200 || $status >= 300) {
        $error = json_decode($body, true);
        $invalidGrant = $status === 400 && ($error['error'] ?? '') === 'invalid_grant';
        throw new RuntimeException($invalidGrant ? 'HelloAsso invalid refresh grant.' : 'HelloAsso HTTP ' . $status . '.', $invalidGrant ? 401 : $status);
    }
    $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($payload)) throw new RuntimeException('HelloAsso invalid response.');
    return $payload;
}

function helloAssoAccessToken(callable $request): string
{
    $key = helloAssoConfigurationKey(false);
    return \dbObject\HelloAssoConnection::withLock('token:' . $key, static function () use ($key, $request): string {
        $connection = \dbObject\HelloAssoConnection::forConfiguration($key);
        $expires = $connection->get('token_expires_at');
        if (helloAssoStoredTimestamp($expires) > time() + 60 && $connection->get('access_token')) {
            return (string)$connection->get('access_token');
        }
        $refresh = (string)$connection->get('refresh_token');
        $payload = null;
        if ($refresh !== '') {
            try { $payload = $request('POST', '/oauth2/token', ['grant_type' => 'refresh_token', 'refresh_token' => $refresh], ''); }
            catch (RuntimeException $error) { if ($error->getCode() !== 401) throw $error; }
        }
        if ($payload === null) {
            $payload = $request('POST', '/oauth2/token', ['grant_type' => 'client_credentials',
                'client_id' => $GLOBALS['helloAssoClientId'], 'client_secret' => $GLOBALS['helloAssoClientSecret']], '');
        }
        if (empty($payload['access_token']) || empty($payload['refresh_token']) || (int)($payload['expires_in'] ?? 0) <= 0) {
            throw new RuntimeException('HelloAsso invalid token response.');
        }
        $connection->set('access_token', (string)$payload['access_token']);
        $connection->set('refresh_token', (string)$payload['refresh_token']);
        $connection->set('token_expires_at', new DateTime('@' . (time() + (int)$payload['expires_in'])));
        if (empty($connection->save()['status'])) throw new RuntimeException('Cannot save HelloAsso token.');
        return (string)$payload['access_token'];
    }, 2);
}

/** These integration timestamps are persisted as UTC, regardless of the PHP default timezone. */
function helloAssoStoredTimestamp($value): int
{
    return $value instanceof DateTimeInterface
        ? (new DateTimeImmutable($value->format('Y-m-d H:i:s'), new DateTimeZone('UTC')))->getTimestamp() : 0;
}

/** Calendar anniversary, clamped for Jan 31 and Feb 29 rather than PHP's date overflow. */
function helloAssoPeriodEnd(DateTimeImmutable $start, bool $monthly): DateTimeImmutable
{
    $target = $start->modify('first day of this month')->modify($monthly ? '+1 month' : '+1 year');
    return $target->setDate((int)$target->format('Y'), (int)$target->format('m'), min((int)$start->format('d'), (int)$target->format('t')));
}

function helloAssoNormalizePayment(array $payment, string $email): array
{
    if (strtolower(trim((string)($payment['payer']['email'] ?? ''))) !== $email) return [];
    $order = $payment['order'] ?? [];
    if (($order['organizationSlug'] ?? '') !== trim((string)$GLOBALS['helloAssoOrganizationSlug'])) return [];
    $forms = helloAssoAllowedForms();
    if ($forms ? !in_array(($order['formType'] ?? '') . '/' . ($order['formSlug'] ?? ''), $forms, true) : ($order['formType'] ?? '') !== 'Donation') return [];
    if (empty($payment['id']) || empty($payment['date']) || !isset($payment['state'], $payment['amount']) || !is_array($payment['items'] ?? null)) {
        throw new RuntimeException('HelloAsso incomplete payment response.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T/', (string)$payment['date'])) throw new RuntimeException('HelloAsso invalid payment date.');
    $paid = (new DateTimeImmutable($payment['date']))->setTimezone(new DateTimeZone('Europe/Paris'));
    $amount = max(0, (int)$payment['amount']);
    $refunded = 0;
    foreach ($payment['refundOperations'] ?? [] as $refund) {
        if (in_array($refund['status'] ?? '', ['Processed', 'Processing'], true)) $refunded += max(0, (int)($refund['amount'] ?? 0));
    }
    $groups = ['monthly' => 0, 'annual' => 0];
    foreach ($payment['items'] as $item) {
        $type = (string)($item['type'] ?? '');
        $monthly = in_array($type, ['MonthlyDonation', 'MonthlyPayment'], true);
        $annual = in_array($type, ['Donation', 'Payment', 'Membership', 'Registration', 'Product'], true);
        // Tips, offline payments and unknown item types never create rights.
        if (!$monthly && !$annual) continue;
        if (!isset($item['shareAmount'])) throw new RuntimeException('HelloAsso missing payment item share.');
        $groups[$monthly ? 'monthly' : 'annual'] += max(0, (int)$item['shareAmount']);
    }
    if (array_sum($groups) > $amount) throw new RuntimeException('HelloAsso inconsistent payment shares.');
    $rows = [];
    foreach ($groups as $kind => $share) {
        if ($share <= 0) continue;
        $net = $amount > 0 ? intdiv($share * max(0, $amount - $refunded), $amount) : 0;
        $rows[] = ['payment_id' => (string)$payment['id'], 'kind' => $kind, 'state' => (string)$payment['state'],
            'amount_cents' => $net, 'monthly_amount_cents' => $kind === 'monthly' ? $net : intdiv($net, 10),
            'paid_at' => $paid->format(DATE_ATOM), 'valid_until' => helloAssoPeriodEnd($paid, $kind === 'monthly')->format(DATE_ATOM)];
    }
    return $rows;
}

/** A complete paginated snapshot is required before replacing the last known payments. */
function helloAssoFetchPayments(string $email, callable $request, string $token, float $deadline): array
{
    $parameters = ['from' => gmdate('Y-m-d\TH:i:s\Z', time() - 367 * 86400),
        'to' => gmdate('Y-m-d\TH:i:s\Z', time() + 1), 'userSearchKey' => $email,
        'pageIndex' => 1, 'pageSize' => 100, 'sortOrder' => 'Asc', 'sortField' => 'Date'];
    $rows = []; $seenTokens = [];
    for ($page = 1; $page <= 100; $page++) {
        if (microtime(true) >= $deadline) throw new RuntimeException('HelloAsso synchronization time limit reached.');
        $payload = $request('GET', '/v5/organizations/' . rawurlencode(trim($GLOBALS['helloAssoOrganizationSlug'])) . '/payments', $parameters, $token);
        if (!is_array($payload['data'] ?? null) || !is_array($payload['meta']['pagination'] ?? null)) throw new RuntimeException('HelloAsso incomplete pagination.');
        foreach ($payload['data'] as $payment) {
            if (!is_array($payment)) throw new RuntimeException('HelloAsso invalid payment.');
            foreach (helloAssoNormalizePayment($payment, $email) as $row) $rows[$row['payment_id'] . ':' . $row['kind']] = $row;
        }
        $pagination = $payload['meta']['pagination'];
        $next = (string)($pagination['continuationToken'] ?? '');
        if ($next !== '') {
            if (isset($seenTokens[$next])) throw new RuntimeException('HelloAsso repeated pagination token.');
            $seenTokens[$next] = true;
            $parameters['continuationToken'] = $next;
        } elseif (isset($pagination['totalPages']) && $page < (int)$pagination['totalPages']) {
            if (!$payload['data']) throw new RuntimeException('HelloAsso truncated pagination.');
            unset($parameters['continuationToken']);
            $parameters['pageIndex'] = $page + 1;
        } else {
            if (!array_key_exists('continuationToken', $pagination) && !isset($pagination['totalPages'])) throw new RuntimeException('HelloAsso missing pagination end.');
            return array_values($rows);
        }
    }
    throw new RuntimeException('HelloAsso pagination limit reached.');
}

function helloAssoSyncUser(int $userId, bool $force = false, ?callable $request = null): bool
{
    if (!helloAssoIsEnabled()) return false;
    if (!helloAssoIsConfigured()) throw new RuntimeException('HelloAsso configuration incomplete.');
    if (!\dbObject\UserHelloAsso::isStorageAvailable() || !\dbObject\HelloAssoConnection::isStorageAvailable()) throw new RuntimeException('HelloAsso migration missing.');
    $request ??= 'helloAssoRequest';
    return \dbObject\HelloAssoConnection::withLock('user:' . $userId, static function () use ($userId, $force, $request): bool {
        $snapshot = \dbObject\UserHelloAsso::findByUserId($userId) ?? new \dbObject\UserHelloAsso();
        $email = \dbObject\UserHelloAsso::eligibleEmail($userId);
        $key = helloAssoConfigurationKey();
        $lastAttempt = $snapshot->get('last_attempt_at');
        $lastSync = $snapshot->get('last_sync_at');
        $sameIdentity = $snapshot->get('email') === $email && $snapshot->get('configuration_key') === $key;
        if (!$force && helloAssoStoredTimestamp($lastAttempt) > time() - 900) return false;
        if (!$force && $sameIdentity && helloAssoStoredTimestamp($lastSync) > time() - max(900, (int)($GLOBALS['helloAssoSyncInterval'] ?? 21600))) return false;
        $snapshot->set('IDuser', $userId);
        $snapshot->set('last_attempt_at', new DateTime('now', new DateTimeZone('UTC')));
        if (!$sameIdentity) {
            $snapshot->set('email', $email); $snapshot->set('configuration_key', $key);
            $snapshot->set('payments', '[]'); $snapshot->set('last_sync_at', null);
        }
        if (empty($snapshot->save()['status'])) throw new RuntimeException('Cannot save HelloAsso synchronization attempt.');
        try {
            $deadline = microtime(true) + 20;
            $rows = $email === '' ? [] : helloAssoFetchPayments($email, $request, helloAssoAccessToken($request), $deadline);
            // If the email changed while the API was queried, never attach the old payer to it.
            if (\dbObject\UserHelloAsso::eligibleEmail($userId) !== $email) throw new RuntimeException('HelloAsso account changed during synchronization.');
            $snapshot->set('payments', json_encode($rows, JSON_THROW_ON_ERROR));
            $snapshot->set('last_sync_at', new DateTime('now', new DateTimeZone('UTC')));
            $snapshot->set('last_sync_error', null);
            if (empty($snapshot->save()['status'])) throw new RuntimeException('Cannot save HelloAsso payments.');
            return true;
        } catch (Throwable $error) {
            $snapshot->set('last_sync_error', 'Synchronization failed (' . (int)$error->getCode() . '). See server cron log.');
            $snapshot->save();
            throw $error;
        }
    });
}

function helloAssoSyncBatch(): int
{
    if (!helloAssoIsEnabled()) return 0;
    if (!helloAssoIsConfigured()) throw new RuntimeException('HelloAsso configuration incomplete.');
    if (!\dbObject\UserHelloAsso::isStorageAvailable()) throw new RuntimeException('HelloAsso migration missing.');
    $ids = \dbObject\UserHelloAsso::dueUserIds(helloAssoConfigurationKey(), max(900, (int)($GLOBALS['helloAssoSyncInterval'] ?? 21600)),
        max(1, min(100, (int)($GLOBALS['helloAssoBatchSize'] ?? 20))));
    $count = 0; $failures = 0; $deadline = microtime(true) + 45;
    foreach ($ids as $id) {
        if (microtime(true) >= $deadline) break;
        try { if (helloAssoSyncUser($id)) $count++; }
        catch (Throwable $error) {
            $failures++;
            error_log('HelloAsso user #' . $id . ': ' . $error->getMessage());
            // Do not multiply API failures on a rate limit or service outage.
            if (in_array($error->getCode(), [401, 403, 429], true) || $error->getCode() >= 500) break;
        }
    }
    if ($failures) throw new RuntimeException('HelloAsso batch: ' . $count . ' synced, ' . $failures . ' failed.');
    return $count;
}

/** External service failure must never prevent local account activation. */
function helloAssoCheckActivatedAccount(int $userId): void
{
    if (!helloAssoIsEnabled()) return;
    try { helloAssoSyncUser($userId); }
    catch (Throwable $error) { error_log('HelloAsso activation user #' . $userId . ': ' . $error->getMessage()); }
}
