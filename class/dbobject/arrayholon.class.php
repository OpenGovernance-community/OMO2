<?php
	namespace dbObject;

	class ArrayHolon extends ArrayDbObject
	{
		public static function objectName() {
			return "\dbObject\Holon";
		}

		/**
		 * Fully loaded snapshot of a subtree. One collection read per level,
		 * instead of one child query and one object load per node.
		 * Hidden/inactive branches are excluded just like getChildren().
		 */
		public function loadSubtreeHydrated(int $rootId, bool $includeHidden = false): void
		{
			$this->exchangeArray([]);
			$root = new Holon();
			if ($rootId <= 0 || !$root->load($rootId)) return;
			$this[] = $root;
			$visited = [$rootId => true];
			$frontier = [$rootId];
			while ($frontier !== []) {
				$next = [];
				foreach (array_chunk($frontier, 500) as $parentIds) {
					$children = new self();
					$where = [
						['field' => 'IDholon_parent', 'op' => 'in', 'value' => $parentIds],
						['field' => 'active', 'value' => 1],
					];
					if (!$includeHidden) $where[] = ['field' => 'visible', 'value' => 1];
					$children->loadHydrated(['where' => $where]);
					foreach ($children as $child) {
						$id = (int)$child->getId();
						if (isset($visited[$id])) continue;
						$visited[$id] = true;
						$this[] = $child;
						$next[] = $id;
					}
				}
				$frontier = $next;
			}
		}

		/** Build an adjacency map without further database reads. */
		public function getChildrenByParentId(): array
		{
			$children = [];
			foreach ($this as $holon) {
				$children[(int)$holon->get('IDholon_parent')][] = $holon;
			}
			return $children;
		}

		public static function fetchStructureRows($organizationRootHolonId)
		{
			$organizationRootHolonId = (int)$organizationRootHolonId;
			if ($organizationRootHolonId <= 0) {
				return array();
			}

			$rows = \dbObject\DbObject::fetchAll(
				"SELECT h.*
				FROM holon h
				WHERE h.id = :root_holon_id
				   OR h.IDholon_org = :organization_root_holon_id
				ORDER BY h.name ASC, h.id ASC",
				array(
					'root_holon_id' => $organizationRootHolonId,
					'organization_root_holon_id' => $organizationRootHolonId,
				)
			);

			return is_array($rows) ? $rows : array();
		}

		public function loadVisibilityTargetsForOrganization($organizationId, array $typeIds = array(2, 1))
		{
			$organizationId = (int)$organizationId;
			$typeIds = array_values(array_unique(array_filter(array_map('intval', $typeIds), static function ($typeId) {
				return in_array($typeId, array(1, 2), true);
			})));

			$this->exchangeArray([]);

			if ($organizationId <= 0 || count($typeIds) === 0) {
				return;
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return;
			}

			$rootHolon = $organization->getEnabledStructuralRootHolon();
			if (!$rootHolon) {
				return;
			}

			$this->load([
				'where' => [
					['field' => 'IDholon_org', 'value' => (int)$rootHolon->getId()],
					['field' => 'active', 'value' => 1],
					['field' => 'visible', 'value' => 1],
					['field' => 'IDtypeholon', 'op' => 'in', 'value' => $typeIds],
				],
				'orderBy' => [
					['field' => 'IDtypeholon', 'dir' => 'DESC'],
					['field' => 'name', 'dir' => 'ASC'],
				],
			]);
		}

		public function buildVisibilityTargetOptions(): array
		{
			$options = array(
				'circle' => array(),
				'role' => array(),
			);

			foreach ($this as $holon) {
				if (!($holon instanceof \dbObject\Holon) || (int)$holon->getId() <= 0) {
					continue;
				}

				$typeId = (int)$holon->get('IDtypeholon');
				$typeKey = $typeId === 2 ? 'circle' : ($typeId === 1 ? 'role' : '');
				if ($typeKey === '') {
					continue;
				}

				$pathLabels = array();
				foreach ($holon->getPathHolons(true) as $pathHolon) {
					if ((int)$pathHolon->get('IDtypeholon') === 4) {
						continue;
					}

					$label = trim((string)$pathHolon->getDisplayName());
					if ($label !== '') {
						$pathLabels[] = $label;
					}
				}

				$options[$typeKey][] = array(
					'id' => (int)$holon->getId(),
					'label' => count($pathLabels) > 0 ? implode(' > ', $pathLabels) : trim((string)$holon->getDisplayName()),
					'name' => trim((string)$holon->getDisplayName()),
				);
			}

			return $options;
		}
	}
	
?>
