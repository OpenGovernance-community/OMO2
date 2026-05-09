<?php
	namespace dbObject;

	class ArrayUserOrganization extends ArrayDbObject
	{
		public static function objectName()
		{
			return "\dbObject\UserOrganization";
		}

		public function loadActiveForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return;
			}

			$query = "
				SELECT uo.id
				FROM user_organization uo
				INNER JOIN `user` u ON u.id = uo.IDuser
				WHERE uo.IDorganization = :organization_id
				  AND uo.active = 1
				ORDER BY
				  COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  u.id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, [
				'organization_id' => $organizationId,
			]);

			if ($rows === false) {
				return;
			}

			foreach ($rows as $row) {
				$item = new UserOrganization();
				$item->setId((int)$row['id']);
				$this[] = $item;
			}
		}

		public function loadVisibleForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return;
			}

			$query = "
				SELECT DISTINCT uo.id
				FROM user_organization uo
				INNER JOIN `user` u ON u.id = uo.IDuser
				LEFT JOIN invitation inv
					ON inv.IDorganization = :invitation_organization_id
					AND inv.IDuser = uo.IDuser
					AND inv.status = 'pending'
					AND inv.active = 1
					AND (inv.dateexpiration IS NULL OR inv.dateexpiration > NOW())
				WHERE uo.IDorganization = :organization_id
				  AND (
					uo.active = 1
					OR inv.id IS NOT NULL
				  )
				ORDER BY
				  COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  u.id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, [
				'organization_id' => $organizationId,
				'invitation_organization_id' => $organizationId,
			]);

			if ($rows === false) {
				$fallbackQuery = "
					SELECT uo.id
					FROM user_organization uo
					INNER JOIN `user` u ON u.id = uo.IDuser
					WHERE uo.IDorganization = :organization_id
					  AND uo.active = 1
					ORDER BY
					  COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					  COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					  u.id ASC
				";

				$rows = \dbObject\DbObject::fetchAll($fallbackQuery, [
					'organization_id' => $organizationId,
				]);
			}

			if ($rows === false) {
				return;
			}

			foreach ($rows as $row) {
				$item = new UserOrganization();
				$item->setId((int)$row['id']);
				$this[] = $item;
			}
		}
	}

?>
