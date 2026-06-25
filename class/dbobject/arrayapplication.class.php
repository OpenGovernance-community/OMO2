<?php
	namespace dbObject;

	class ArrayApplication extends ArrayDbObject
	{
		public static function objectName() {
			return "\dbObject\Application";
		}

		public function loadAvailableForOrganization($organizationId, $userId = 0)
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return;
			}

			$query = "
				SELECT a.id, a.url, a.directory
				FROM application a
				LEFT JOIN organization_application oa
					ON oa.IDapplication = a.id
					AND oa.IDorganization = :organization_id
				WHERE a.active = 1
				  AND a.navigationmode <> 'panel'
				  AND (a.requires_login = 0 OR :user_id > 0)
				ORDER BY COALESCE(oa.position, a.position, 999999) ASC, a.label ASC, a.id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, [
				'organization_id' => $organizationId,
				'user_id' => $userId,
			]);

			if ($rows === false) {
				return;
			}

			foreach ($rows as $row) {
				$probeApplication = new Application();
				$probeApplication->set('url', $row['url'] ?? null);
				$probeApplication->set('directory', $row['directory'] ?? null);
				if (!$probeApplication->hasResolvedEntryPoint()) {
					continue;
				}

				$application = new Application();
				$application->setId((int)$row['id']);
				$this[] = $application;
			}
		}

		public function loadEnabledForOrganization($organizationId, $userId = 0) {
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return;
			}

			$query = "
				SELECT a.id, a.url, a.directory
				FROM organization_application oa
				INNER JOIN application a ON a.id = oa.IDapplication
				WHERE oa.IDorganization = :organization_id
				  AND oa.active = 1
				  AND a.active = 1
				  AND a.navigationmode <> 'panel'
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
				$probeApplication = new Application();
				$probeApplication->set('url', $row['url'] ?? null);
				$probeApplication->set('directory', $row['directory'] ?? null);
				if (!$probeApplication->hasResolvedEntryPoint()) {
					continue;
				}

				$application = new Application();
				$application->setId((int)$row['id']);
				$this[] = $application;
			}
		}
	}

?>
