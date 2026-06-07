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

		public function loadCardDavVisibleForViewer($viewerUserId)
		{
			$viewerUserId = (int)$viewerUserId;

			$this->exchangeArray([]);

			if ($viewerUserId <= 0) {
				return;
			}

			$query = "
				SELECT DISTINCT uo.id
				FROM user_organization viewer_uo
				INNER JOIN user_organization uo
					ON uo.IDorganization = viewer_uo.IDorganization
				INNER JOIN `user` u
					ON u.id = uo.IDuser
				INNER JOIN organization o
					ON o.id = uo.IDorganization
				WHERE viewer_uo.IDuser = :viewer_user_id
				  AND viewer_uo.active = 1
				  AND uo.active = 1
				ORDER BY
				  COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  o.name ASC,
				  uo.id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, [
				'viewer_user_id' => $viewerUserId,
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

		public function loadCardDavSharedForViewerAndUser($viewerUserId, $targetUserId)
		{
			$viewerUserId = (int)$viewerUserId;
			$targetUserId = (int)$targetUserId;

			$this->exchangeArray([]);

			if ($viewerUserId <= 0 || $targetUserId <= 0) {
				return;
			}

			$query = "
				SELECT DISTINCT uo.id
				FROM user_organization viewer_uo
				INNER JOIN user_organization uo
					ON uo.IDorganization = viewer_uo.IDorganization
				INNER JOIN organization o
					ON o.id = uo.IDorganization
				WHERE viewer_uo.IDuser = :viewer_user_id
				  AND viewer_uo.active = 1
				  AND uo.IDuser = :target_user_id
				  AND uo.active = 1
				ORDER BY o.name ASC, uo.id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, [
				'viewer_user_id' => $viewerUserId,
				'target_user_id' => $targetUserId,
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
	}

?>
