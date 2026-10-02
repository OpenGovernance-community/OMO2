<?php
namespace dbObject;

class McpOauthClient extends DbObject
{
    public static function tableName() { return 'mcp_oauth_client'; }
    public static function rules()
    {
        return [
            [['id', 'created_at'], 'integer'],
            [['client_id', 'name'], 'string'],
            [['redirect_uris'], 'text'],
            [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'client_id' => 'Client OAuth', 'name' => 'Nom',
            'redirect_uris' => 'Adresses de retour', 'created_at' => 'Creation'];
    }
    public static function attributeLength() { return ['client_id' => 64, 'name' => 150]; }

    public static function register(string $name, array $redirectUris): self
    {
        $client = new self();
        $client->set('client_id', bin2hex(random_bytes(24)));
        $client->set('name', $name);
        $client->set('redirect_uris', json_encode($redirectUris, JSON_THROW_ON_ERROR));
        $client->set('created_at', time());
        if (empty($client->save()['status'])) {
            throw new \RuntimeException('OAuth client storage unavailable.');
        }
        return $client;
    }

    public static function findByClientId(string $clientId): ?self
    {
        if (!preg_match('/^[a-f0-9]{48}$/D', $clientId)) return null;
        $row = self::fetchRow('SELECT id FROM mcp_oauth_client WHERE client_id = :client_id', ['client_id' => $clientId]);
        $client = new self();
        return $row && $client->load((int)$row['id']) ? $client : null;
    }

    public function allowsRedirect(string $uri): bool
    {
        return in_array($uri, json_decode((string)$this->get('redirect_uris'), true, 8, JSON_THROW_ON_ERROR), true);
    }
    // OAuth clients are managed by the protocol, never through generic object editors.
    public function canView() { return false; }
    public function canViewDetail() { return false; }
    public function canEdit() { return false; }
    public function canDelete() { return false; }
}
