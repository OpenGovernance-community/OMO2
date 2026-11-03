<?php
	namespace dbObject;

	class ParcoursParcours extends DbObject
	{
        protected static function uiMessageSourceLang(): array
        {
            return [
                'pack_ou_parcours_invalide' => ['text' => 'Pack ou parcours invalide.', 'context' => 'Tutorial data validation and action feedback.'],
                'le_lien_demande_n_est_pas_autorise' => ['text' => 'Le lien demandé n’est pas autorisé.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_d_ajouter_ce_parcours_au_pack' => ['text' => 'Impossible d’ajouter ce parcours au pack.', 'context' => 'Tutorial data validation and action feedback.'],
                'pack_invalide' => ['text' => 'Pack invalide.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_charger_les_parcours_du_pack' => ['text' => 'Impossible de charger les parcours du pack.', 'context' => 'Tutorial data validation and action feedback.'],
                'la_liste_des_parcours_a_reordonner_est_incomplete' => ['text' => 'La liste des parcours à réordonner est incomplète.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_reordonner_les_parcours_du_pack' => ['text' => 'Impossible de réordonner les parcours du pack.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_retirer_ce_parcours_du_pack' => ['text' => 'Impossible de retirer ce parcours du pack.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_nettoyer_les_anciens_liens_de_ce_parcours' => ['text' => 'Impossible de nettoyer les anciens liens de ce parcours.', 'context' => 'Tutorial data validation and action feedback.'],
            ];
        }

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
					'message' => self::uiMessage('pack_ou_parcours_invalide'),
				];
			}

			$parentParcours = new \dbObject\Parcours();
			$childParcours = new \dbObject\Parcours();
			if (
				!$parentParcours->load($parentParcoursId)
				|| !$childParcours->load($childParcoursId)
				|| !$parentParcours->isPack()
				|| $childParcours->isPack()
				|| $parentParcours->get('isarchived')
				|| $childParcours->get('isarchived')
			) {
				return [
					'status' => false,
					'message' => self::uiMessage('le_lien_demande_n_est_pas_autorise'),
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
						: self::uiMessage('impossible_d_ajouter_ce_parcours_au_pack'),
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
					'message' => self::uiMessage('pack_invalide'),
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
					'message' => self::uiMessage('impossible_de_charger_les_parcours_du_pack'),
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
					'message' => self::uiMessage('la_liste_des_parcours_a_reordonner_est_incomplete'),
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
						'message' => self::uiMessage('impossible_de_reordonner_les_parcours_du_pack'),
					];
				}
			}

			return [
				'status' => true,
			];
		}

		public static function detachChildFromParent($parentParcoursId, $childParcoursId)
		{
			$parentParcoursId = (int)$parentParcoursId;
			$childParcoursId = (int)$childParcoursId;
			if ($parentParcoursId <= 0 || $childParcoursId <= 0) {
				return [
					'status' => false,
					'message' => self::uiMessage('pack_ou_parcours_invalide'),
				];
			}

			$result = self::execute(
				"DELETE FROM parcours_parcours
				WHERE IDparcours_parent = :parent_parcours_id
				  AND IDparcours_child = :child_parcours_id",
				[
					'parent_parcours_id' => $parentParcoursId,
					'child_parcours_id' => $childParcoursId,
				]
			);
			if ($result === false) {
				return [
					'status' => false,
					'message' => self::uiMessage('impossible_de_retirer_ce_parcours_du_pack'),
				];
			}

			$cleanupResult = \dbObject\Parcours::cleanupLegacyDetachedChildLinksAcrossOrganizations($parentParcoursId, $childParcoursId);
			if (!is_array($cleanupResult) || empty($cleanupResult['status'])) {
				return [
					'status' => false,
					'message' => is_array($cleanupResult) && !empty($cleanupResult['message'])
						? (string)$cleanupResult['message']
						: self::uiMessage('impossible_de_nettoyer_les_anciens_liens_de_ce_parcours'),
				];
			}

			return [
				'status' => true,
				'cleanedLegacyLinks' => (int)($cleanupResult['detachedCount'] ?? 0),
			];
		}
	}

?>
