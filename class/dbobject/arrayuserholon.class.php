<?php
	namespace dbObject;

	class ArrayUserHolon extends ArrayDbObject
	{
		public static function objectName()
		{
			return "\dbObject\UserHolon";
		}

		public function loadActiveForHolonIds(array $holonIds)
		{
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));

			$this->exchangeArray([]);

			if (count($holonIds) === 0) {
				return;
			}

			$placeholders = [];
			$params = [];
			foreach ($holonIds as $index => $holonId) {
				$placeholder = 'holon_' . $index;
				$placeholders[] = ':' . $placeholder;
				$params[$placeholder] = $holonId;
			}

			$query = "
				SELECT uh.id
				FROM user_holon uh
				INNER JOIN `user` u ON u.id = uh.IDuser
				WHERE uh.active = 1
				  AND uh.IDholon IN (" . implode(', ', $placeholders) . ")
				ORDER BY
				  COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
				  u.id ASC,
				  uh.id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			if ($rows === false) {
				return;
			}

			foreach ($rows as $row) {
				$item = new UserHolon();
				$item->setId((int)$row['id']);
				$this[] = $item;
			}
		}
	}

?>
