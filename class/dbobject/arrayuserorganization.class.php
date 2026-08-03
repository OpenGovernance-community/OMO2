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

		public function loadActiveForUser($userId)
		{
			$userId = (int)$userId;

			$this->exchangeArray([]);

			if ($userId <= 0) {
				return;
			}

			$query = "
				SELECT uo.id
				FROM user_organization uo
				WHERE uo.IDuser = :user_id
				  AND uo.active = 1
				ORDER BY uo.IDorganization ASC, uo.id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, [
				'user_id' => $userId,
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

		public function loadVisibleForOrganization($organizationId, $includeInactive = false)
		{
			$organizationId = (int)$organizationId;
			$includeInactive = (bool)$includeInactive;

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return;
			}

			$visibilityCondition = $includeInactive
				? '1 = 1'
				: "
					uo.active = 1
					OR inv.id IS NOT NULL
				";

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
				  AND (" . $visibilityCondition . ")
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
				$fallbackVisibilityCondition = $includeInactive ? '1 = 1' : 'uo.active = 1';
				$fallbackQuery = "
					SELECT uo.id
					FROM user_organization uo
					INNER JOIN `user` u ON u.id = uo.IDuser
					WHERE uo.IDorganization = :organization_id
					  AND " . $fallbackVisibilityCondition . "
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
		public function buildUpcomingCelebrations($organizationId, $limit = 6, $referenceDate = null, array $labels = array(), ?array $allowedUserIds = null)
		{
			require_once dirname(__DIR__, 2) . '/common/user_profile_ui.php';

			$organizationId = (int)$organizationId;
			$limit = max(1, (int)$limit);
			$items = array();
			$allowedUserIdMap = null;

			if (is_array($allowedUserIds)) {
				$allowedUserIdMap = array_fill_keys(array_values(array_unique(array_filter(array_map('intval', $allowedUserIds), static function ($userId) {
					return $userId > 0;
				}))), true);

				if (count($allowedUserIdMap) === 0) {
					return array();
				}
			}

			$this->loadActiveForOrganization($organizationId);

			foreach ($this as $membership) {
				if (!($membership instanceof \dbObject\UserOrganization)) {
					continue;
				}

				$userId = (int)$membership->get('IDuser');
				if ($userId <= 0) {
					continue;
				}

				if (is_array($allowedUserIdMap) && !isset($allowedUserIdMap[$userId])) {
					continue;
				}

				$user = new \dbObject\User();
				if (!$user->load($userId) || !$user->canView()) {
					continue;
				}

				$displayName = trim((string)$user->getScopedDisplayName($organizationId));
				if ($displayName === '') {
					$displayName = 'Utilisateur ' . $userId;
				}

				$birthSummary = \commonUserProfileBuildRecurringDateSummary($user->get('birthdate'), array(
					'today' => "Anniversaire aujourd'hui",
					'soonPrefix' => 'Anniversaire dans',
					'detailPrefix' => 'Le',
				), $referenceDate, null, 7);
				if (is_array($birthSummary)) {
					$items[] = array(
						'userId' => $userId,
						'displayName' => $displayName,
						'tagType' => 'personal',
						'headline' => (string)($birthSummary['headline'] ?? ''),
						'detail' => (string)($birthSummary['detail'] ?? ''),
						'sortGroup' => (int)(($birthSummary['daysUntil'] ?? 9999) === 0 ? 0 : 1),
						'sortDistance' => (int)($birthSummary['daysUntil'] ?? 9999),
						'sortTimestamp' => ($birthSummary['nextBirthday'] ?? null) instanceof \DateTimeInterface
							? (int)$birthSummary['nextBirthday']->getTimestamp()
							: 0,
					);
				}

				$recentJoinSummary = \commonUserProfileBuildRecentDateSummary($membership->get('datecreation'), array(
					'label' => (string)($labels['proNew'] ?? 'Nouveau'),
					'detailPrefix' => (string)($labels['proNewDetailPrefix'] ?? 'Arrive le'),
				), $referenceDate, null, 7);
				if (is_array($recentJoinSummary)) {
					$items[] = array(
						'userId' => $userId,
						'displayName' => $displayName,
						'tagType' => 'pro',
						'headline' => (string)($recentJoinSummary['headline'] ?? ''),
						'detail' => (string)($recentJoinSummary['detail'] ?? ''),
						'sortGroup' => (int)(($recentJoinSummary['daysSince'] ?? 9999) === 0 ? 0 : 2),
						'sortDistance' => (int)($recentJoinSummary['daysSince'] ?? 9999),
						'sortTimestamp' => ($recentJoinSummary['eventDate'] ?? null) instanceof \DateTimeInterface
							? (int)$recentJoinSummary['eventDate']->getTimestamp()
							: 0,
					);
					continue;
				}

				$joinSummary = \commonUserProfileBuildRecurringDateSummary($membership->get('datecreation'), array(
					'today' => (string)($labels['proToday'] ?? "Anniversaire pro aujourd'hui"),
					'soonPrefix' => (string)($labels['proSoonPrefix'] ?? 'Anniversaire pro dans'),
					'detailPrefix' => 'Le',
				), $referenceDate, null, 7);
				if (is_array($joinSummary)) {
					$items[] = array(
						'userId' => $userId,
						'displayName' => $displayName,
						'tagType' => 'pro',
						'headline' => (string)($joinSummary['headline'] ?? ''),
						'detail' => (string)($joinSummary['detail'] ?? ''),
						'sortGroup' => (int)(($joinSummary['daysUntil'] ?? 9999) === 0 ? 0 : 1),
						'sortDistance' => (int)($joinSummary['daysUntil'] ?? 9999),
						'sortTimestamp' => ($joinSummary['nextBirthday'] ?? null) instanceof \DateTimeInterface
							? (int)$joinSummary['nextBirthday']->getTimestamp()
							: 0,
					);
				}
			}

			usort($items, static function (array $left, array $right) {
				$leftGroup = (int)($left['sortGroup'] ?? 9);
				$rightGroup = (int)($right['sortGroup'] ?? 9);
				if ($leftGroup !== $rightGroup) {
					return $leftGroup <=> $rightGroup;
				}

				$leftDistance = (int)($left['sortDistance'] ?? 9999);
				$rightDistance = (int)($right['sortDistance'] ?? 9999);
				if ($leftDistance !== $rightDistance) {
					return $leftDistance <=> $rightDistance;
				}

				$leftTimestamp = (int)($left['sortTimestamp'] ?? 0);
				$rightTimestamp = (int)($right['sortTimestamp'] ?? 0);
				if ($leftTimestamp !== $rightTimestamp) {
					if ($leftGroup === 2) {
						return $rightTimestamp <=> $leftTimestamp;
					}

					return $leftTimestamp <=> $rightTimestamp;
				}

				return strcmp((string)($left['displayName'] ?? ''), (string)($right['displayName'] ?? ''));
			});

			return array_slice($items, 0, $limit);
		}
	}

?>
