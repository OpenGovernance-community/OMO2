<?php
namespace dbObject;

class AiUsage extends DbObject
{
    public static function tableName() { return 'ai_usage'; }
    public static function rules()
    {
        return [
            [['id', 'input_tokens', 'output_tokens', 'total_tokens', 'input_chars', 'output_chars', 'audio_bytes', 'http_status'], 'integer'],
            [['IDuser', 'IDorganization'], 'fk'],
            [['origin', 'kind', 'feature', 'provider', 'model'], 'string'],
            [['success'], 'boolean'], [['created_at'], 'datetime'], [['id'], 'safe'],
        ];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'IDuser' => 'Utilisateur', 'IDorganization' => 'Organisation', 'origin' => 'Origine',
            'kind' => 'Type', 'feature' => 'Fonction', 'provider' => 'Fournisseur', 'model' => 'Modele',
            'input_tokens' => 'Jetons entrants', 'output_tokens' => 'Jetons sortants', 'total_tokens' => 'Jetons totaux',
            'input_chars' => 'Caracteres entrants', 'output_chars' => 'Caracteres sortants', 'audio_bytes' => 'Octets audio',
            'success' => 'Succes', 'http_status' => 'Statut HTTP', 'created_at' => 'Date'];
    }
    public static function attributeLength() { return ['origin' => 20, 'kind' => 20, 'feature' => 190, 'provider' => 40, 'model' => 120]; }

    public static function record(array $values): void
    {
        $item = new self();
        foreach ($values as $field => $value) $item->set($field, $value);
        $item->set('created_at', new \DateTime('now', new \DateTimeZone('UTC')));
        if (empty($item->save()['status'])) throw new \RuntimeException('Usage storage unavailable.');
    }

    public static function dashboard(string $start, string $end, int $organizationId = 0, string $origin = ''): array
    {
        $where = 'a.created_at >= :start AND a.created_at < :end';
        $params = ['start' => $start, 'end' => $end];
        if ($organizationId > 0) { $where .= ' AND a.IDorganization = :organization'; $params['organization'] = $organizationId; }
        if (in_array($origin, ['site', 'api', 'mcp', 'telegram', 'background'], true)) { $where .= ' AND a.origin = :origin'; $params['origin'] = $origin; }
        $totals = self::fetchRow("SELECT COUNT(*) AS requests, SUM(a.kind = 'model') AS model_requests,
            SUM(a.kind = 'exchange') AS exchange_requests, SUM(a.total_tokens) AS tokens,
            COUNT(a.total_tokens) AS measured_requests, SUM(a.success = 0) AS errors,
            SUM(a.input_chars) AS input_chars, SUM(a.output_chars) AS output_chars FROM ai_usage a WHERE " . $where, $params);
        if ($totals === false) return ['available' => false, 'totals' => [], 'rows' => []];
        $rows = self::fetchAll("SELECT a.IDuser, a.IDorganization, a.origin, a.kind, a.feature, a.provider, a.model,
            CONCAT_WS(' ', u.firstname, u.lastname) AS name, u.email, o.name AS organization_name,
            COUNT(*) AS requests, SUM(a.success = 0) AS errors, COUNT(a.total_tokens) AS measured_requests,
            SUM(a.input_tokens) AS input_tokens, SUM(a.output_tokens) AS output_tokens, SUM(a.total_tokens) AS total_tokens,
            SUM(a.input_chars) AS input_chars, SUM(a.output_chars) AS output_chars, SUM(a.audio_bytes) AS audio_bytes
            FROM ai_usage a LEFT JOIN user u ON u.id = a.IDuser LEFT JOIN organization o ON o.id = a.IDorganization
            WHERE " . $where . ' GROUP BY a.IDuser, a.IDorganization, a.origin, a.kind, a.feature, a.provider, a.model,
            u.firstname, u.lastname, u.email, o.name ORDER BY total_tokens DESC, requests DESC LIMIT 200', $params) ?: [];
        return ['available' => true, 'totals' => $totals, 'rows' => $rows];
    }
}
