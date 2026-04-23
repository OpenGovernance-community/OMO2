<?php
	namespace dbObject;

	class ArrayApplication extends ArrayDbObject
	{
		public static function objectName() {
			return "\dbObject\Application";
		}

		public function loadEnabledForOrganization($organizationId, $userId = 0) {
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return;
			}

			$query = "
				SELECT a.id
				FROM organization_application oa
				INNER JOIN application a ON a.id = oa.IDapplication
				WHERE oa.IDorganization = :organization_id
				  AND oa.active = 1
				  AND a.active = 1
				  AND (a.requires_login = 0 OR :user_id > 0)
				ORDER BY COALESCE(oa.position, a.position, 999999) ASC, a.label ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, [
				'organization_id' => $organizationId,
				'user_id' => $userId,
			]);

			if ($rows === false) {
				return;
			}

			foreach ($rows as $row) {
				$application = new Application();
				$application->setId((int)$row['id']);
				$this[] = $application;
			}
		}
	}

?>
