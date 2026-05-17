<?php
	namespace dbObject;

	class ArrayOrganization extends ArrayDbObject
	{
		
		public static function objectName() {
			return "\dbObject\Organization";
		}

		public function loadAccessibleForUser($userId, $organizationId = null, $limit = null) {
			$userId = (int)$userId;
			$organizationId = $organizationId !== null ? (int)$organizationId : null;
			$limit = $limit !== null ? max(0, (int)$limit) : null;

			$this->exchangeArray([]);

			if ($userId <= 0) {
				return;
			}

			if (function_exists('commonUserIsSiteAdmin') && \commonUserIsSiteAdmin($userId)) {
				$query = "
					SELECT o.id
					FROM organization o
					WHERE 1 = 1
				";
				$params = [];

				if ($organizationId !== null && $organizationId > 0) {
					$query .= " AND o.id = :organization_id";
					$params['organization_id'] = $organizationId;
				}

				$query .= " ORDER BY o.name ASC";

				if ($limit !== null && $limit > 0) {
					$query .= " LIMIT ".$limit;
				}

				$rows = \dbObject\DbObject::fetchAll($query, $params);
				if ($rows === false) {
					return;
				}

				foreach ($rows as $row) {
					$organization = new Organization();
					$organization->setId((int)$row['id']);
					$this[] = $organization;
				}

				return;
			}

			$query = "
				SELECT o.id
				FROM user_organization uo
				INNER JOIN organization o ON o.id = uo.IDorganization
				WHERE uo.IDuser = :user_id
				  AND uo.active = 1
			";
			$params = [
				'user_id' => $userId,
			];

			if ($organizationId !== null && $organizationId > 0) {
				$query .= " AND o.id = :organization_id";
				$params['organization_id'] = $organizationId;
			}

			$query .= " ORDER BY o.name ASC";

			if ($limit !== null && $limit > 0) {
				$query .= " LIMIT ".$limit;
			}

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			if ($rows === false) {
				return;
			}

			foreach ($rows as $row) {
				$organization = new Organization();
				$organization->setId((int)$row['id']);
				$this[] = $organization;
			}
		}
	}
	
?>
