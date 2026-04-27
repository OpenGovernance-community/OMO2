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

function patreonIsConfigured()
{
	return trim((string)($GLOBALS['patreonClientId'] ?? '')) !== ''
		&& trim((string)($GLOBALS['patreonClientSecret'] ?? '')) !== '';
}

function patreonGetRedirectUri()
{
	$configured = trim((string)($GLOBALS['patreonRedirectUri'] ?? ''));
	if ($configured !== '') {
		return $configured;
	}

	if (function_exists('appBuildAbsoluteUrl')) {
		return appBuildAbsoluteUrl('/common/patreon_callback.php');
	}

	$host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
	if ($host === '') {
		return '/common/patreon_callback.php';
	}

	$https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
	$scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
	return $scheme . '://' . $host . '/common/patreon_callback.php';
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
	return 'identity identity[email] identity.memberships campaigns';
}

function patreonBuildAuthorizeUrl($state)
{
	$params = [
		'response_type' => 'code',
		'client_id' => (string)$GLOBALS['patreonClientId'],
		'redirect_uri' => patreonGetRedirectUri(),
		'scope' => patreonGetRequestedScopes(),
		'state' => (string)$state,
	];

	return 'https://www.patreon.com/oauth2/authorize?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function patreonRequest($method, $url, array $options = [])
{
	if (!function_exists('curl_init')) {
		throw new RuntimeException('cURL est requis pour intégrer Patreon.');
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
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);

	if (!empty($options['body'])) {
		curl_setopt($curl, CURLOPT_POSTFIELDS, $options['body']);
	}

	$body = curl_exec($curl);
	if ($body === false) {
		$error = curl_error($curl);
		curl_close($curl);
		throw new RuntimeException('Erreur réseau Patreon : ' . $error);
	}

	$status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
	curl_close($curl);

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
	$response = patreonRequest('POST', 'https://www.patreon.com/api/oauth2/token', [
		'headers' => [
			'Content-Type: application/x-www-form-urlencoded',
		],
		'body' => http_build_query([
			'code' => (string)$code,
			'grant_type' => 'authorization_code',
			'client_id' => (string)$GLOBALS['patreonClientId'],
			'client_secret' => (string)$GLOBALS['patreonClientSecret'],
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
	$response = patreonRequest('POST', 'https://www.patreon.com/api/oauth2/token', [
		'headers' => [
			'Content-Type: application/x-www-form-urlencoded',
		],
		'body' => http_build_query([
			'grant_type' => 'refresh_token',
			'refresh_token' => (string)$refreshToken,
			'client_id' => (string)$GLOBALS['patreonClientId'],
			'client_secret' => (string)$GLOBALS['patreonClientSecret'],
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
		'fields[user]' => 'full_name,email,image_url,url,vanity',
		'fields[member]' => 'campaign_lifetime_support_cents,currently_entitled_amount_cents,last_charge_date,last_charge_status,next_charge_date,patron_status',
		'fields[tier]' => 'title,amount_cents',
	];

	return 'https://www.patreon.com/api/oauth2/v2/identity?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function patreonFetchIdentity($accessToken)
{
	$response = patreonRequest('GET', patreonBuildIdentityUrl(), [
		'headers' => [
			'Authorization: Bearer ' . (string)$accessToken,
		],
	]);
	$payload = patreonDecodeResponse($response);

	if ((int)$response['status'] >= 400) {
		$message = patreonBuildErrorMessage($response, $payload);
		$exception = new RuntimeException($message, (int)$response['status']);
		throw $exception;
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

	foreach (($membership['relationships']['currently_entitled_tiers']['data'] ?? []) as $tierReference) {
		$tierId = (string)($tierReference['id'] ?? '');
		$title = trim((string)($tierMap[$tierId]['attributes']['title'] ?? ''));
		if ($title !== '') {
			$tierTitles[] = $title;
		}
	}

	return [
		'patreon_user_id' => (string)($identity['data']['id'] ?? ''),
		'patreon_member_id' => (string)($membership['id'] ?? ''),
		'campaign_id' => (string)($membership['relationships']['campaign']['data']['id'] ?? ''),
		'full_name' => (string)($userAttributes['full_name'] ?? ''),
		'email' => (string)($userAttributes['email'] ?? ''),
		'image_url' => (string)($userAttributes['image_url'] ?? ''),
		'profile_url' => (string)($userAttributes['url'] ?? ''),
		'vanity' => (string)($userAttributes['vanity'] ?? ''),
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
