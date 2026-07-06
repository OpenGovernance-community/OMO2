<?php
	namespace dbObject;

	class OrganizationApplication extends DbObject
	{
	    public static function tableName()
		{
			return 'organization_application';
		}

		public static function rules()
		{
			return [
				[['IDorganization', 'IDapplication'], 'required'],
				[['IDorganization', 'IDapplication'], 'fk'],
				[['id', 'position'], 'integer'],
				[['parameters'], 'parameters'],
				[['active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDapplication' => 'Application',
				'position' => 'Position',
				'parameters' => 'Parametres',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDorganization' => 'Organisation concernee',
				'IDapplication' => 'Application activee pour cette organisation',
				'position' => 'Surcharge locale de l ordre d affichage',
				'parameters' => 'Parametres specifiques a cette application dans cette organisation.',
				'active' => 'Activation locale de l application',
			];
		}

		public static function getOrder()
		{
			return "position ASC, id ASC";
		}

		public function getParametersArray(): array
		{
			$parameters = json_decode((string)$this->get('parameters'), true);
			return is_array($parameters) ? $parameters : array();
		}

		public function setParametersArray(array $parameters): void
		{
			$this->set('parameters', $parameters);
		}

		public function getApplicationDirectory(): string
		{
			$application = $this->get('application');
			if (!$application || (int)$application->getId() <= 0) {
				return '';
			}

			return trim((string)$application->get('directory'));
		}

		public static function loadByOrganizationAndDirectory(int $organizationId, string $directory, bool $activeOnly = false): ?self
		{
			$organizationId = (int)$organizationId;
			$directory = trim((string)$directory);
			if ($organizationId <= 0 || $directory === '') {
				return null;
			}

			$query = "
				SELECT oa.id
				FROM organization_application oa
				INNER JOIN application a
					ON a.id = oa.IDapplication
				WHERE oa.IDorganization = :organization_id
				  AND LOWER(a.directory) = :directory";
			$params = array(
				'organization_id' => $organizationId,
				'directory' => mb_strtolower($directory, 'UTF-8'),
			);

			if ($activeOnly) {
				$query .= "
				  AND oa.active = 1
				  AND a.active = 1";
			}

			$query .= "
				ORDER BY oa.id ASC
				LIMIT 1";

			$id = (int)self::fetchValue($query, $params);
			if ($id <= 0) {
				return null;
			}

			$item = new self();
			return $item->load($id) ? $item : null;
		}

		public static function ensureByOrganizationAndDirectory(int $organizationId, string $directory): ?self
		{
			$organizationId = (int)$organizationId;
			$directory = trim((string)$directory);
			if ($organizationId <= 0 || $directory === '') {
				return null;
			}

			$existing = self::loadByOrganizationAndDirectory($organizationId, $directory, false);
			if ($existing instanceof self) {
				return $existing;
			}

			$row = self::fetchRow(
				"SELECT id, position
				FROM application
				WHERE LOWER(directory) = :directory
				LIMIT 1",
				array(
					'directory' => mb_strtolower($directory, 'UTF-8'),
				)
			);
			if (!is_array($row) || (int)($row['id'] ?? 0) <= 0) {
				return null;
			}

			$item = new self();
			$item->set('IDorganization', $organizationId);
			$item->set('IDapplication', (int)$row['id']);
			$item->set('position', isset($row['position']) ? (int)$row['position'] : null);
			$item->set('active', 1);

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status']) || (int)$item->getId() <= 0) {
				return null;
			}

			return $item;
		}
	}

?>
