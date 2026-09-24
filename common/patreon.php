<?php

require_once dirname(__DIR__) . '/config.php';

if (!function_exists('patreonRegisterAutoloader')) {
	function patreonRegisterAutoloader()
	{
		static $registered = false;

		if ($registered) {
			return;
		}

		spl_autoload_register(function ($class) {
			$path = dirname(__DIR__) . '/class/' . str_replace('\\', '/', strtolower($class)) . '.class.php';
			if (file_exists($path)) {
				include_once $path;
			}
		});

		$registered = true;
	}
}

patreonRegisterAutoloader();

function patreonGetConfigurationIssues($context = 'api')
{
	$context = trim((string)$context);
	if ($context === '') {
		$context = 'api';
	}

	$issues = [];

	if (trim((string)($GLOBALS['patreonClientId'] ?? '')) === '') {
		$issues[] = 'PATREON_CLIENT_ID manquant';
	}

	if (trim((string)($GLOBALS['patreonClientSecret'] ?? '')) === '') {
		$issues[] = 'PATREON_CLIENT_SECRET manquant';
	}

	if ($context === 'oauth') {
		$redirectUri = patreonGetRedirectUri();
		if ($redirectUri === '' || preg_match('#^https?://#i', $redirectUri) !== 1) {
			$issues[] = 'PATREON_REDIRECT_URI manquant ou URL publique absolue introuvable';
		}
	}

	if (in_array($context, ['api', 'oauth', 'sync'], true) && class_exists('\\dbObject\\UserPatreon')) {
		if (!\dbObject\UserPatreon::isStorageAvailable()) {
			$issues[] = 'table SQL user_patreon absente';
		}
	}

	if ($context === 'oauth') {
		if (patreonGetConnectUrl() === '') {
			$issues[] = 'PATREON_CONNECT_URL manquant ou invalide';
		}
		if (class_exists('\\dbObject\\PatreonOauthTransaction')
			&& !\dbObject\PatreonOauthTransaction::isStorageAvailable()) {
			$issues[] = 'table SQL patreon_oauth_transaction absente';
		}
	}

	if (in_array($context, ['api', 'oauth', 'sync'], true) && !function_exists('curl_init')) {
		$issues[] = 'extension PHP cURL manquante';
	}

	return array_values(array_unique($issues));
}

function patreonIsConfigured($context = 'api')
{
	return patreonGetConfigurationIssues($context) === [];
}

function patreonSupportUiIsEnabled()
{
	return patreonIsConfigured('oauth');
}

function patreonCanManageOrganizationRouting($userId, $minimumAmountCents = 2000)
{
	$userId = (int)$userId;
	$minimumAmountCents = max(0, (int)$minimumAmountCents);

	if ($userId <= 0 || !class_exists('\\dbObject\\UserPatreon') || !\dbObject\UserPatreon::isStorageAvailable()) {
		return false;
	}

	$connection = \dbObject\UserPatreon::findByUserId($userId);
	if (!($connection instanceof \dbObject\UserPatreon) || !$connection->isConnected()) {
		return false;
	}

	if ((string)$connection->get('patron_status') !== 'active_patron') {
		return false;
	}

	return (int)$connection->get('currently_entitled_amount_cents') > $minimumAmountCents;
}

function patreonUserCanUseAi($userId)
{
	$userId = (int)$userId;
	if ($userId <= 0) {
		return false;
	}

	if (!patreonSupportUiIsEnabled()) {
		return true;
	}

	$user = new \dbObject\User();
	if ($user->load($userId) && $user->isSiteAdmin()) {
		return true;
	}

	if (!class_exists('\\dbObject\\UserPatreon') || !\dbObject\UserPatreon::isStorageAvailable()) {
		return false;
	}

	$connection = \dbObject\UserPatreon::findByUserId($userId);
	if (!($connection instanceof \dbObject\UserPatreon) || !$connection->isConnected()) {
		return false;
	}

	return (string)$connection->get('patron_status') === 'active_patron'
		&& (int)$connection->get('currently_entitled_amount_cents') > 0;
}

function patreonGetConfigurationMessage($context = 'api')
{
	$issues = patreonGetConfigurationIssues($context);
	if ($issues === []) {
		return '';
	}

	return implode(' ; ', $issues) . '.';
}

function patreonAssertConfigured($context = 'api')
{
	if (!patreonIsConfigured($context)) {
		throw new RuntimeException('Configuration Patreon incomplète : ' . patreonGetConfigurationMessage($context));
	}
}

function patreonGetRedirectUri()
{
	$connectUrl = patreonGetConnectUrl();
	if ($connectUrl !== '') {
		$parts = parse_url($connectUrl);
		$host = strtolower((string)($parts['host'] ?? ''));
		$port = isset($parts['port']) && (int)$parts['port'] !== 443 ? ':' . (int)$parts['port'] : '';
		return 'https://' . $host . $port . '/common/patreon_callback.php';
	}

	$configured = trim((string)($GLOBALS['patreonRedirectUri'] ?? ''));
	if ($configured !== '') {
		return $configured;
	}

	if (function_exists('appBuildAbsoluteUrl')) {
		$fallback = appBuildAbsoluteUrl('/common/patreon_callback.php');
		if (preg_match('#^https?://#i', $fallback) === 1) {
			return $fallback;
		}
	}

	$host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
	if ($host === '') {
		return '';
	}

	$https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
	$scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
	return $scheme . '://' . $host . '/common/patreon_callback.php';
}

function patreonGetConnectUrl()
{
	$url = trim((string)($GLOBALS['patreonConnectUrl'] ?? ''));
	if ($url === '') {
		return '';
	}

	$parts = parse_url($url);
	if (!is_array($parts)
		|| strtolower((string)($parts['scheme'] ?? '')) !== 'https'
		|| trim((string)($parts['host'] ?? '')) === ''
		|| isset($parts['user'])
		|| isset($parts['pass'])
		|| isset($parts['fragment'])
		|| (string)($parts['path'] ?? '') !== '/common/patreon_connect.php') {
		return '';
	}

	return rtrim($url, '?&');
}

function patreonGetConnectOrigin()
{
	$parts = parse_url(patreonGetConnectUrl());
	if (!is_array($parts)) {
		return '';
	}

	$host = strtolower((string)($parts['host'] ?? ''));
	$port = isset($parts['port']) && (int)$parts['port'] !== 443 ? ':' . (int)$parts['port'] : '';
	return $host !== '' ? 'https://' . $host . $port : '';
}

function patreonNormalizeReturnOriginPattern($origin)
{
	$parts = parse_url(trim((string)$origin));
	if (!is_array($parts)
		|| strtolower((string)($parts['scheme'] ?? '')) !== 'https'
		|| isset($parts['user'])
		|| isset($parts['pass'])
		|| isset($parts['query'])
		|| isset($parts['fragment'])
		|| !in_array((string)($parts['path'] ?? ''), ['', '/'], true)) {
		return '';
	}

	$host = strtolower((string)($parts['host'] ?? ''));
	$domain = str_starts_with($host, '*.') ? substr($host, 2) : $host;
	if ($domain === ''
		|| str_contains($domain, '*')
		|| filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
		|| !str_contains($domain, '.')) {
		return '';
	}

	$port = isset($parts['port']) && (int)$parts['port'] !== 443 ? ':' . (int)$parts['port'] : '';
	return 'https://' . $host . $port;
}

function patreonGetAllowedReturnOrigins()
{
	$configured = trim((string)($GLOBALS['patreonConnectAllowedOrigins'] ?? ''));
	$origins = $configured !== ''
		? preg_split('/\\s*,\\s*/', $configured, -1, PREG_SPLIT_NO_EMPTY)
		: [
			'https://opengov.tools',
			'https://dev.opengov.tools',
			'https://*.dev.opengov.tools',
			'https://beta.opengov.tools',
			'https://omo2.org',
			'https://*.omo2.org',
			'https://openmyorganization.org',
		];

	$allowed = [];
	foreach ($origins as $origin) {
		$pattern = patreonNormalizeReturnOriginPattern($origin);
		if ($pattern !== '') {
			$allowed[] = $pattern;
		}
	}

	return array_values(array_unique($allowed));
}

function patreonGetRequestOrigin()
{
	$host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
	if ($host === '' || preg_match('/[^a-z0-9.:-]/i', $host)) {
		return '';
	}

	$scheme = function_exists('commonGetRequestScheme')
		? commonGetRequestScheme()
		: (strtolower((string)($_SERVER['HTTPS'] ?? '')) !== '' && strtolower((string)($_SERVER['HTTPS'] ?? '')) !== 'off' ? 'https' : 'http');
	if (strtolower($scheme) !== 'https') {
		return '';
	}

	$parts = parse_url('https://' . $host);
	if (!is_array($parts) || empty($parts['host'])) {
		return '';
	}

	$normalizedHost = strtolower((string)$parts['host']);
	$port = isset($parts['port']) && (int)$parts['port'] !== 443 ? ':' . (int)$parts['port'] : '';
	return 'https://' . $normalizedHost . $port;
}

function patreonIsAllowedReturnOrigin($origin)
{
	$origin = (string)$origin;
	if ($origin === '' || $origin !== patreonNormalizeReturnOriginPattern($origin)) {
		return false;
	}

	$host = strtolower((string)parse_url($origin, PHP_URL_HOST));
	if (str_contains($host, '*')) {
		return false;
	}
	$port = parse_url($origin, PHP_URL_PORT);
	foreach (patreonGetAllowedReturnOrigins() as $pattern) {
		if ($origin === $pattern) {
			return true;
		}

		$patternHost = strtolower((string)parse_url($pattern, PHP_URL_HOST));
		if (!str_starts_with($patternHost, '*.') || parse_url($pattern, PHP_URL_PORT) !== $port) {
			continue;
		}

		$suffix = substr($patternHost, 1);
		if (strlen($host) > strlen($suffix) && str_ends_with($host, $suffix)) {
			return true;
		}
	}

	return false;
}

function patreonIsConnectHubRequest()
{
	$connectOrigin = patreonGetConnectOrigin();
	$requestOrigin = patreonGetRequestOrigin();
	return $connectOrigin !== '' && $requestOrigin !== '' && hash_equals($connectOrigin, $requestOrigin);
}

function patreonGetCreatorCampaignId()
{
	return trim((string)($GLOBALS['patreonCreatorCampaignId'] ?? ''));
}

function patreonGetUserAgent()
{
	$userAgent = trim((string)($GLOBALS['patreonUserAgent'] ?? ''));
	return $userAgent !== '' ? $userAgent : 'EasyPV Patreon Sync';
}

function patreonGetRequestedScopes()
{
	return 'identity';
}

function patreonBuildAuthorizeUrl($state)
{
	patreonAssertConfigured('oauth');

	$params = [
		'response_type' => 'code',
		'client_id' => (string)($GLOBALS['patreonClientId'] ?? ''),
		'redirect_uri' => patreonGetRedirectUri(),
		'scope' => patreonGetRequestedScopes(),
		'state' => (string)$state,
	];

	return 'https://www.patreon.com/oauth2/authorize?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function patreonRequest($method, $url, array $options = [])
{
	if (!function_exists('curl_init')) {
		throw new RuntimeException('La configuration Patreon est incomplète : extension PHP cURL manquante.');
	}

	$headers = [
		'Accept: application/json',
		'User-Agent: ' . patreonGetUserAgent(),
	];

	if (!empty($options['headers']) && is_array($options['headers'])) {
		$headers = array_merge($headers, $options['headers']);
	}

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper((string)$method));
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	if (!empty($options['body'])) {
		curl_setopt($curl, CURLOPT_POSTFIELDS, $options['body']);
	}

	$body = curl_exec($curl);
	if ($body === false) {
		$error = curl_error($curl);
		throw new RuntimeException('Erreur réseau Patreon : ' . $error);
	}

	$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

	return [
		'status' => $status,
		'body' => $body,
	];
}

function patreonDecodeResponse(array $response)
{
	$decoded = json_decode((string)($response['body'] ?? ''), true);
	if (!is_array($decoded)) {
		throw new RuntimeException('Réponse Patreon invalide.');
	}

	return $decoded;
}

function patreonBuildErrorMessage(array $response, array $payload)
{
	if (isset($payload['errors'][0]['detail']) && trim((string)$payload['errors'][0]['detail']) !== '') {
		return trim((string)$payload['errors'][0]['detail']);
	}

	if (isset($payload['error_description']) && trim((string)$payload['error_description']) !== '') {
		return trim((string)$payload['error_description']);
	}

	if (isset($payload['error']) && trim((string)$payload['error']) !== '') {
		return trim((string)$payload['error']);
	}

	return 'Erreur Patreon HTTP ' . (int)($response['status'] ?? 0);
}

function patreonNormalizeTokenPayload(array $payload)
{
	if (empty($payload['access_token']) || empty($payload['refresh_token'])) {
		throw new RuntimeException('Patreon n’a pas retourné les jetons attendus.');
	}

	$expiresIn = isset($payload['expires_in']) ? max(0, (int)$payload['expires_in']) : 0;
	$expiresAt = new DateTime();
	if ($expiresIn > 0) {
		$expiresAt->modify('+' . $expiresIn . ' seconds');
	}

	return [
		'access_token' => (string)$payload['access_token'],
		'refresh_token' => (string)$payload['refresh_token'],
		'scope' => (string)($payload['scope'] ?? ''),
		'token_type' => (string)($payload['token_type'] ?? 'Bearer'),
		'token_expires_at' => $expiresAt,
	];
}

function patreonExchangeCodeForTokens($code)
{
	patreonAssertConfigured('oauth');

	$response = patreonRequest('POST', 'https://www.patreon.com/api/oauth2/token', [
		'headers' => [
			'Content-Type: application/x-www-form-urlencoded',
		],
		'body' => http_build_query([
			'code' => (string)$code,
			'grant_type' => 'authorization_code',
			'client_id' => (string)($GLOBALS['patreonClientId'] ?? ''),
			'client_secret' => (string)($GLOBALS['patreonClientSecret'] ?? ''),
			'redirect_uri' => patreonGetRedirectUri(),
		], '', '&', PHP_QUERY_RFC3986),
	]);
	$payload = patreonDecodeResponse($response);

	if ((int)$response['status'] >= 400) {
		throw new RuntimeException(patreonBuildErrorMessage($response, $payload));
	}

	return patreonNormalizeTokenPayload($payload);
}

function patreonRefreshTokens($refreshToken)
{
	patreonAssertConfigured('api');

	$response = patreonRequest('POST', 'https://www.patreon.com/api/oauth2/token', [
		'headers' => [
			'Content-Type: application/x-www-form-urlencoded',
		],
		'body' => http_build_query([
			'grant_type' => 'refresh_token',
			'refresh_token' => (string)$refreshToken,
			'client_id' => (string)($GLOBALS['patreonClientId'] ?? ''),
			'client_secret' => (string)($GLOBALS['patreonClientSecret'] ?? ''),
		], '', '&', PHP_QUERY_RFC3986),
	]);
	$payload = patreonDecodeResponse($response);

	if ((int)$response['status'] >= 400) {
		throw new RuntimeException(patreonBuildErrorMessage($response, $payload));
	}

	return patreonNormalizeTokenPayload($payload);
}

function patreonBuildIdentityUrl()
{
	$params = [
		'include' => 'memberships.currently_entitled_tiers',
		'fields[user]' => 'full_name',
		'fields[member]' => 'campaign_lifetime_support_cents,currently_entitled_amount_cents,last_charge_date,last_charge_status,next_charge_date,patron_status',
		'fields[tier]' => 'title',
	];

	return 'https://www.patreon.com/api/oauth2/v2/identity?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function patreonFetchIdentity($accessToken)
{
	patreonAssertConfigured('api');

	$response = patreonRequest('GET', patreonBuildIdentityUrl(), [
		'headers' => [
			'Authorization: Bearer ' . (string)$accessToken,
		],
	]);
	$payload = patreonDecodeResponse($response);

	if ((int)$response['status'] >= 400) {
		$message = patreonBuildErrorMessage($response, $payload);
		throw new RuntimeException($message, (int)$response['status']);
	}

	return $payload;
}

function patreonSelectMembership(array $identity)
{
	$members = [];
	foreach (($identity['included'] ?? []) as $resource) {
		if (($resource['type'] ?? '') === 'member') {
			$members[] = $resource;
		}
	}

	if ($members === []) {
		return null;
	}

	$configuredCampaignId = patreonGetCreatorCampaignId();
	if ($configuredCampaignId !== '') {
		foreach ($members as $member) {
			$campaignId = (string)($member['relationships']['campaign']['data']['id'] ?? '');
			if ($campaignId === $configuredCampaignId) {
				return $member;
			}
		}
	}

	if (count($members) === 1) {
		return $members[0];
	}

	foreach ($members as $member) {
		if ((string)($member['attributes']['patron_status'] ?? '') === 'active_patron') {
			return $member;
		}
	}

	return $members[0];
}

function patreonMapIncludedResourcesByType(array $identity, $type)
{
	$mapped = [];
	foreach (($identity['included'] ?? []) as $resource) {
		if (($resource['type'] ?? '') === $type && isset($resource['id'])) {
			$mapped[(string)$resource['id']] = $resource;
		}
	}

	return $mapped;
}

function patreonExtractProfile(array $identity)
{
	$userAttributes = $identity['data']['attributes'] ?? [];
	$membership = patreonSelectMembership($identity);
	$memberAttributes = $membership['attributes'] ?? [];
	$tierMap = patreonMapIncludedResourcesByType($identity, 'tier');
	$tierTitles = [];
	$membershipTiers = [];

	if (is_array($membership) && isset($membership['relationships']['currently_entitled_tiers']['data']) && is_array($membership['relationships']['currently_entitled_tiers']['data'])) {
		$membershipTiers = $membership['relationships']['currently_entitled_tiers']['data'];
	}

	foreach ($membershipTiers as $tierReference) {
		$tierId = (string)($tierReference['id'] ?? '');
		$title = trim((string)($tierMap[$tierId]['attributes']['title'] ?? ''));
		if ($title !== '') {
			$tierTitles[] = $title;
		}
	}

	return [
		'patreon_user_id' => (string)($identity['data']['id'] ?? ''),
		'patreon_member_id' => is_array($membership) ? (string)($membership['id'] ?? '') : '',
		'campaign_id' => is_array($membership) ? (string)($membership['relationships']['campaign']['data']['id'] ?? '') : '',
		'full_name' => (string)($userAttributes['full_name'] ?? ''),
		'email' => '',
		'image_url' => '',
		'profile_url' => '',
		'vanity' => '',
		'patron_status' => (string)($memberAttributes['patron_status'] ?? ''),
		'last_charge_status' => (string)($memberAttributes['last_charge_status'] ?? ''),
		'last_charge_date' => !empty($memberAttributes['last_charge_date']) ? new DateTime((string)$memberAttributes['last_charge_date']) : null,
		'next_charge_date' => !empty($memberAttributes['next_charge_date']) ? new DateTime((string)$memberAttributes['next_charge_date']) : null,
		'currently_entitled_amount_cents' => (int)($memberAttributes['currently_entitled_amount_cents'] ?? 0),
		'campaign_lifetime_support_cents' => (int)($memberAttributes['campaign_lifetime_support_cents'] ?? 0),
		'tier_titles' => $tierTitles !== [] ? implode("\n", $tierTitles) : null,
	];
}

function patreonEnsureFreshToken(\dbObject\UserPatreon $connection, $thresholdSeconds = 3600)
{
	$expiresAt = $connection->get('token_expires_at');
	$needsRefresh = !($expiresAt instanceof DateTimeInterface)
		|| $expiresAt->getTimestamp() <= (time() + (int)$thresholdSeconds);

	if (!$needsRefresh) {
		return false;
	}

	$tokens = patreonRefreshTokens((string)$connection->get('refresh_token'));
	$connection->applyOauthTokens($tokens);
	$saveResult = $connection->save();
	if (empty($saveResult['status'])) {
		throw new RuntimeException('Impossible d’enregistrer les nouveaux jetons Patreon.');
	}

	return true;
}

function patreonSyncConnection(\dbObject\UserPatreon $connection)
{
	patreonAssertConfigured('sync');

	if (!$connection->isConnected()) {
		throw new RuntimeException('Aucun compte Patreon connecté pour cet utilisateur.');
	}

	try {
		patreonEnsureFreshToken($connection);

		try {
			$identity = patreonFetchIdentity((string)$connection->get('access_token'));
		} catch (RuntimeException $exception) {
			if ((int)$exception->getCode() !== 401) {
				throw $exception;
			}

			$tokens = patreonRefreshTokens((string)$connection->get('refresh_token'));
			$connection->applyOauthTokens($tokens);
			$saveResult = $connection->save();
			if (empty($saveResult['status'])) {
				throw new RuntimeException('Impossible d’enregistrer les jetons Patreon rafraîchis.');
			}

			$identity = patreonFetchIdentity((string)$connection->get('access_token'));
		}

		$profile = patreonExtractProfile($identity);
		$connection->applyPatreonProfile($profile);
		$connection->markSyncSuccess();
		$saveResult = $connection->save();
		if (empty($saveResult['status'])) {
			throw new RuntimeException('Impossible d’enregistrer les données Patreon synchronisées.');
		}

		return $profile;
	} catch (Throwable $exception) {
		$connection->markSyncFailure($exception->getMessage());
		$connection->save();
		throw $exception;
	}
}

?>
