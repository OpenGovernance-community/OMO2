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
	}

?>
