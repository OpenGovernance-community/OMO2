<?php
	namespace dbObject;

	class ParcoursParcours extends DbObject
	{
		public static function tableName()
		{
			return 'parcours_parcours';
		}

		public static function rules()
		{
			return [
				[['id', 'IDparcours_parent', 'IDparcours_child', 'position'], 'integer'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDparcours_parent' => 'Pack parent',
				'IDparcours_child' => 'Parcours enfant',
				'position' => 'Position',
			];
		}

		public static function getOrder()
		{
			return 'position, id';
		}

		public static function countForParent($parentParcoursId)
		{
			return (int)self::fetchValue(
				"SELECT COUNT(*)
				FROM parcours_parcours
				WHERE IDparcours_parent = :parent_parcours_id",
				['parent_parcours_id' => (int)$parentParcoursId]
			);
		}

		public static function fetchDetailedForParent($parentParcoursId)
		{
			$rows = self::fetchAll(
				"SELECT
					pp.id,
					pp.IDparcours_parent,
					pp.IDparcours_child,
					pp.position,
					p.title,
					p.description,
					p.image,
					p.IDapplication,
					COALESCE(p.ispack, 0) AS ispack
				FROM parcours_parcours pp
				INNER JOIN parcours p
					ON p.id = pp.IDparcours_child
				WHERE pp.IDparcours_parent = :parent_parcours_id
				ORDER BY COALESCE(pp.position, pp.id) ASC, pp.id ASC",
				['parent_parcours_id' => (int)$parentParcoursId]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function attachChildToParent($parentParcoursId, $childParcoursId, array $options = array())
		{
			$parentParcoursId = (int)$parentParcoursId;
			$childParcoursId = (int)$childParcoursId;
			if ($parentParcoursId <= 0 || $childParcoursId <= 0 || $parentParcoursId === $childParcoursId) {
				return [
					'status' => false,
					'message' => 'Pack ou parcours invalide.',
				];
			}

			$parentParcours = new \dbObject\Parcours();
			$childParcours = new \dbObject\Parcours();
			if (
				!$parentParcours->load($parentParcoursId)
				|| !$childParcours->load($childParcoursId)
				|| !$parentParcours->isPack()
				|| $childParcours->isPack()
			) {
				return [
					'status' => false,
					'message' => 'Le lien demande n est pas autorise.',
				];
			}

			$item = new self();
			$created = !$item->load([
				['IDparcours_parent', $parentParcoursId],
				['IDparcours_child', $childParcoursId],
			]);

			if ($created) {
				$item->set('IDparcours_parent', $parentParcoursId);
				$item->set('IDparcours_child', $childParcoursId);
			}

			$position = array_key_exists('position', $options) ? (int)$options['position'] : 0;
			if ($created && $position <= 0) {
				$position = (int)self::fetchValue(
					"SELECT COALESCE(MAX(position), 0) + 1
					FROM parcours_parcours
					WHERE IDparcours_parent = :parent_parcours_id",
					['parent_parcours_id' => $parentParcoursId]
				);
			}

			if ($created || array_key_exists('position', $options)) {
				$item->set('position', $position > 0 ? $position : null);
			}

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return [
					'status' => false,
					'message' => is_array($saveResult) && !empty($saveResult['text'])
						? (string)$saveResult['text']
						: 'Impossible d ajouter ce parcours au pack.',
				];
			}

			return [
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			];
		}

		public static function reorderForParent($parentParcoursId, array $childParcoursIds)
		{
			$parentParcoursId = (int)$parentParcoursId;
			if ($parentParcoursId <= 0) {
				return [
					'status' => false,
					'message' => 'Pack invalide.',
				];
			}

			$normalizedChildIds = [];
			foreach ($childParcoursIds as $childParcoursId) {
				$childParcoursId = (int)$childParcoursId;
				if ($childParcoursId > 0) {
					$normalizedChildIds[$childParcoursId] = $childParcoursId;
				}
			}
			$normalizedChildIds = array_values($normalizedChildIds);

			$currentRows = self::fetchAll(
				"SELECT IDparcours_child
				FROM parcours_parcours
				WHERE IDparcours_parent = :parent_parcours_id",
				['parent_parcours_id' => $parentParcoursId]
			);
			if (!is_array($currentRows)) {
				return [
					'status' => false,
					'message' => 'Impossible de charger les parcours du pack.',
				];
			}

			$currentChildIds = [];
			foreach ($currentRows as $row) {
				$childParcoursId = (int)($row['IDparcours_child'] ?? 0);
				if ($childParcoursId > 0) {
					$currentChildIds[$childParcoursId] = $childParcoursId;
				}
			}
			$currentChildIds = array_values($currentChildIds);
			sort($currentChildIds);

			$comparisonChildIds = $normalizedChildIds;
			sort($comparisonChildIds);
			if ($comparisonChildIds !== $currentChildIds) {
				return [
					'status' => false,
					'message' => 'La liste des parcours a reordonner est incomplete.',
				];
			}

			foreach ($normalizedChildIds as $index => $childParcoursId) {
				$result = self::execute(
					"UPDATE parcours_parcours
					SET position = :position
					WHERE IDparcours_parent = :parent_parcours_id
					  AND IDparcours_child = :child_parcours_id",
					[
						'position' => $index + 1,
						'parent_parcours_id' => $parentParcoursId,
						'child_parcours_id' => $childParcoursId,
					]
				);
				if (!$result) {
					return [
						'status' => false,
						'message' => 'Impossible de reordonner les parcours du pack.',
					];
				}
			}

			return [
				'status' => true,
			];
		}
	}

?>
