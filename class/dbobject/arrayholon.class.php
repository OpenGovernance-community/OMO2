<?php
	namespace dbObject;

	class ArrayHolon extends ArrayDbObject
	{
		public static function objectName() {
			return "\dbObject\Holon";
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
