<?php
	namespace dbObject;

	class ArrayApplication extends ArrayDbObject
	{
		public static function objectName() {
			return "\dbObject\Application";
		}

		public function loadAvailableForOrganization($organizationId, $userId = 0)
		{
			$this->loadOrganizationCatalog((int)$organizationId, (int)$userId, false, false);
		}

		public function loadEnabledForOrganization($organizationId, $userId = 0, bool $ignoreLoginRequirement = false)
		{
			$this->loadOrganizationCatalog((int)$organizationId, (int)$userId, true, $ignoreLoginRequirement);
		}

		private function loadOrganizationCatalog(int $organizationId, int $userId, bool $enabledOnly, bool $ignoreLoginRequirement): void
		{
			$this->exchangeArray([]);
			if ($organizationId <= 0) { return; }
			foreach (Application::loadOrganizationCatalog($organizationId) as $row) {
				if ((int)$row['active'] !== 1 || ($enabledOnly && (int)$row['organization_active'] !== 1)
					|| $row['navigationmode'] === null || strtolower(rtrim((string)$row['navigationmode'])) === 'panel'
					|| (($row['requires_login'] === null || (int)$row['requires_login'] !== 0) && $userId <= 0 && !$ignoreLoginRequirement)) { continue; }
				$application = new Application();
				unset($row['organization_active'], $row['organization_position']);
				$application->hydrateFromDatabaseRow($row, true);
				if ($application->hasResolvedEntryPoint()) { $this[] = $application; }
			}
		}

	}

?>
