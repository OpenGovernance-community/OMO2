<?php
namespace dbObject;

class OrganizationalMaturityPublicLink extends DbObject
{
    public static function tableName() { return 'organizational_maturity_public_link'; }

    public static function rules()
    {
        return [
            [['id'], 'integer'],
            [['IDorganization'], 'fk'],
            [['token'], 'string'],
            [['created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDorganization' => 'Organisation',
            'token' => 'Lien public',
            'created_at' => 'Cree le',
            'updated_at' => 'Modifie le',
        ];
    }

    public static function attributeLength()
    {
        return ['token' => 32];
    }

    public static function findByToken($token)
    {
        $token = self::normalizeToken($token);
        if ($token === '') {
            return null;
        }
        $row = self::fetchRow(
            'SELECT * FROM organizational_maturity_public_link WHERE token = :token LIMIT 1',
            ['token' => $token]
        );
        if ($row === false) {
            return null;
        }
        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public static function findByOrganization($organizationId)
    {
        $row = self::fetchRow(
            'SELECT * FROM organizational_maturity_public_link WHERE IDorganization = :organization_id LIMIT 1',
            ['organization_id' => (int)$organizationId]
        );
        if ($row === false) {
            return null;
        }
        $item = new self();
        $item->loadFromArray($row);
        $item->setId((int)$row['id']);
        return $item;
    }

    public static function issueForOrganization($organizationId)
    {
        $organizationId = (int)$organizationId;
        $organization = new Organization();
        if ($organizationId <= 0 || !$organization->load($organizationId)) {
            return ['status' => false, 'message' => 'Organisation introuvable.'];
        }

        $item = self::findByOrganization($organizationId);
        if (!$item) {
            try {
                $token = bin2hex(random_bytes(16));
            } catch (\Throwable $error) {
                return ['status' => false, 'message' => 'Generation de lien impossible.'];
            }
            $item = new self();
            $item->set('IDorganization', $organizationId);
            $item->set('token', $token);
            $item->set('created_at', new \DateTimeImmutable());
        }
        $item->set('updated_at', new \DateTimeImmutable());
        $saved = $item->save();
        if (empty($saved['status'])) {
            return ['status' => false, 'message' => 'Enregistrement du lien impossible.'];
        }

        $token = (string)$item->get('token');
        return [
            'status' => true,
            'token' => $token,
            'publicUrl' => appBuildAbsoluteUrl('/survey/public-access.php?token=' . rawurlencode($token)),
            'organization' => (string)$organization->get('name'),
        ];
    }

    public function getOrganizationObject()
    {
        $organization = new Organization();
        return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
    }

    private static function normalizeToken($token)
    {
        $token = trim((string)$token);
        return preg_match('/^[a-f0-9]{32}$/i', $token) ? strtolower($token) : '';
    }
}
