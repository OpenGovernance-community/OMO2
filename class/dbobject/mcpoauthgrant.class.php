<?php
namespace dbObject;

class McpOauthGrant extends DbObject
{
    public const ACCESS_LIFETIME = 3600;
    public const REFRESH_LIFETIME = 2592000;
    public static function tableName() { return 'mcp_oauth_grant'; }
    public static function rules()
    {
        return [
            [['id', 'code_expires_at', 'code_used_at', 'access_expires_at', 'refresh_expires_at', 'revoked_at', 'created_at'], 'integer'],
            [['IDclient', 'IDuser', 'IDorganization'], 'fk'],
            [['resource', 'scope', 'redirect_uri', 'code_hash', 'code_challenge', 'access_hash', 'refresh_hash'], 'string'],
            [['used_refresh_hashes'], 'text'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDclient' => 'Client', 'IDuser' => 'Utilisateur', 'IDorganization' => 'Organisation',
            'resource' => 'Serveur', 'scope' => 'Droits', 'redirect_uri' => 'Adresse de retour',
            'code_hash' => 'Empreinte du code', 'code_challenge' => 'PKCE', 'code_expires_at' => 'Expiration du code',
            'code_used_at' => 'Code utilise', 'access_hash' => 'Empreinte acces', 'access_expires_at' => 'Expiration acces',
            'refresh_hash' => 'Empreinte renouvellement', 'refresh_expires_at' => 'Expiration renouvellement',
            'revoked_at' => 'Revocation', 'created_at' => 'Creation', 'used_refresh_hashes' => 'Empreintes deja utilisees'];
    }
    public static function attributeLength()
    {
        return ['resource' => 512, 'scope' => 100, 'redirect_uri' => 2048, 'code_hash' => 64,
            'code_challenge' => 43, 'access_hash' => 64, 'refresh_hash' => 64];
    }
    public static function token(): string { return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='); }
    public static function hash(string $token): string { return hash('sha256', $token); }

    public static function issueCode(McpOauthClient $client, int $userId, int $organizationId, array $request): string
    {
        $code = self::token();
        $grant = new self();
        foreach (['IDclient' => (int)$client->getId(), 'IDuser' => $userId, 'IDorganization' => $organizationId,
            'resource' => $request['resource'], 'scope' => $request['scope'], 'redirect_uri' => $request['redirect_uri'],
            'code_hash' => self::hash($code), 'code_challenge' => $request['code_challenge'],
            'code_expires_at' => time() + 300, 'created_at' => time()] as $field => $value) {
            $grant->set($field, $value);
        }
        if (empty($grant->save()['status'])) throw new \RuntimeException('OAuth grant storage unavailable.');
        return $code;
    }

    /** Lock before consuming a code or rotating a refresh token: concurrent replays cannot succeed. */
    public static function exchange(McpOauthClient $client, array $request): ?array
    {
        $pdo = self::getPdo();
        $pdo->beginTransaction();
        try {
            $refresh = $request['grant_type'] === 'refresh_token';
            $field = $refresh ? 'refresh_hash' : 'code_hash';
            $secret = (string)($request[$refresh ? 'refresh_token' : 'code'] ?? '');
            if ($secret === '' || strlen($secret) > 256) { $pdo->rollBack(); return null; }
            $hash = self::hash($secret);
            $lookup = $field . ' = :hash';
            $bindings = ['hash' => $hash];
            if ($refresh) {
                $lookup .= " OR JSON_CONTAINS(COALESCE(used_refresh_hashes, '[]'), :used_hash)";
                $bindings['used_hash'] = json_encode($hash, JSON_THROW_ON_ERROR);
            }
            $row = self::fetchRow('SELECT * FROM mcp_oauth_grant WHERE (' . $lookup . ') FOR UPDATE', $bindings);
            if (!$row || (int)$row['IDclient'] !== (int)$client->getId()
                || $row['revoked_at'] !== null || \omoMcpNormalizeScope($row['scope']) === null || !hash_equals($row['resource'], $request['resource'])
                || !UserOrganization::hasActiveMembership((int)$row['IDuser'], (int)$row['IDorganization'])) {
                $pdo->rollBack(); return null;
            }
            $user = new User();
            if (!$user->load((int)$row['IDuser']) || !$user->get('active')) { $pdo->rollBack(); return null; }
            if ((!$refresh && $row['code_used_at'] !== null) || ($refresh && !hash_equals((string)$row['refresh_hash'], $hash))) {
                self::execute('UPDATE mcp_oauth_grant SET revoked_at = :now WHERE id = :id', ['now' => time(), 'id' => (int)$row['id']]);
                $pdo->commit(); return null;
            }
            if ($refresh) {
                if ((int)$row['refresh_expires_at'] <= time()
                    || (isset($request['scope']) && $request['scope'] !== $row['scope'])) {
                    $pdo->rollBack(); return null;
                }
            } else {
                $verifier = (string)($request['code_verifier'] ?? '');
                $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
                if ((int)$row['code_expires_at'] <= time()
                    || !hash_equals($row['redirect_uri'], (string)($request['redirect_uri'] ?? ''))
                    || !preg_match('/^[A-Za-z0-9._~-]{43,128}$/D', $verifier)
                    || !hash_equals($row['code_challenge'], $challenge)) {
                    $pdo->rollBack(); return null;
                }
            }
            $access = self::token();
            $renew = self::token();
            $usedHashes = json_decode($row['used_refresh_hashes'] ?? '[]', true, 8, JSON_THROW_ON_ERROR);
            if ($refresh) $usedHashes[] = $hash;
            $ok = self::execute('UPDATE mcp_oauth_grant SET code_used_at = COALESCE(code_used_at, :now),
                access_hash = :access_hash, access_expires_at = :access_expires,
                refresh_hash = :refresh_hash, refresh_expires_at = :refresh_expires,
                used_refresh_hashes = :used_hashes WHERE id = :id',
                ['now' => time(), 'access_hash' => self::hash($access), 'access_expires' => time() + self::ACCESS_LIFETIME,
                    'refresh_hash' => self::hash($renew), 'refresh_expires' => $refresh ? (int)$row['refresh_expires_at'] : time() + self::REFRESH_LIFETIME,
                    'used_hashes' => json_encode($usedHashes, JSON_THROW_ON_ERROR),
                    'id' => (int)$row['id']]);
            if (!$ok) throw new \RuntimeException('OAuth token storage unavailable.');
            $pdo->commit();
            return ['access_token' => $access, 'token_type' => 'Bearer', 'expires_in' => self::ACCESS_LIFETIME,
                'refresh_token' => $renew, 'scope' => $row['scope']];
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function authenticate(string $token, string $resource): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/D', $token)) return null;
        $row = self::fetchRow('SELECT g.id, g.IDuser, g.IDorganization, g.scope, g.resource, g.access_expires_at
            FROM mcp_oauth_grant g JOIN user u ON u.id = g.IDuser AND u.active = 1
            WHERE g.access_hash = :hash AND g.revoked_at IS NULL AND g.access_expires_at > :now',
            ['hash' => self::hash($token), 'now' => time()]);
        if (!$row || !hash_equals($resource, $row['resource']) || \omoMcpNormalizeScope($row['scope']) === null
            || !UserOrganization::hasActiveMembership((int)$row['IDuser'], (int)$row['IDorganization'])) return null;
        return $row;
    }

    public static function revokeToken(string $token, int $clientId): void
    {
        self::execute('UPDATE mcp_oauth_grant SET revoked_at = :now WHERE IDclient = :client
            AND (access_hash = :access OR refresh_hash = :refresh)',
            ['now' => time(), 'client' => $clientId, 'access' => self::hash($token), 'refresh' => self::hash($token)]);
    }
    public static function hasActiveCreationAuthorization(array $grant): bool
    {
        return (bool)self::fetchRow('SELECT id FROM mcp_oauth_grant WHERE id = :id AND IDuser = :user
            AND IDorganization = :organization AND resource = :resource AND scope = :scope
            AND revoked_at IS NULL AND access_expires_at > :now',
            ['id' => (int)($grant['id'] ?? 0), 'user' => (int)$grant['IDuser'], 'organization' => (int)$grant['IDorganization'],
                'resource' => \omoMcpPublicUrl(), 'scope' => \OMO_MCP_SCOPE . ' ' . \OMO_MCP_CREATE_SCOPE, 'now' => time()]);
    }
    public static function revokeOwned(int $id, int $userId): bool
    {
        if (!self::fetchRow('SELECT id FROM mcp_oauth_grant WHERE id = :id AND IDuser = :user', ['id' => $id, 'user' => $userId])) return false;
        return self::execute('UPDATE mcp_oauth_grant SET revoked_at = :now WHERE id = :id AND IDuser = :user',
            ['now' => time(), 'id' => $id, 'user' => $userId]);
    }
    public static function listOwned(int $userId): array
    {
        return self::fetchAll('SELECT g.id, g.scope, c.name AS client_name, o.name AS organization_name, g.created_at,
            g.access_expires_at, g.refresh_expires_at FROM mcp_oauth_grant g
            JOIN mcp_oauth_client c ON c.id = g.IDclient JOIN organization o ON o.id = g.IDorganization
            WHERE g.IDuser = :user AND g.revoked_at IS NULL AND g.refresh_expires_at > :now
            ORDER BY g.id DESC LIMIT 100', ['user' => $userId, 'now' => time()]) ?: [];
    }
    public function canView() { return false; }
    public function canViewDetail() { return false; }
    public function canEdit() { return false; }
    public function canDelete() { return false; }
}
