<?php
	namespace dbObject;

	class ParcoursPrerequisite extends DbObject
	{
		public static function tableName()
		{
			return 'parcours_prerequisite';
		}

		public static function rules()
		{
			return [
				[['id', 'IDparcours', 'IDparcours_required'], 'integer'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDparcours' => 'Parcours cible',
				'IDparcours_required' => 'Parcours prerequis',
			];
		}

		public static function getOrder()
		{
			return 'id';
		}

		public static function fetchDetailedForParcours($parcoursId)
		{
			if (!self::tableExists('parcours_prerequisite')) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT
					pp.id,
					pp.IDparcours,
					pp.IDparcours_required,
					p.title,
					p.description,
					p.image,
					" . (\dbObject\Parcours::hasApplicationColumn() ? "p.IDapplication," : "NULL AS IDapplication,") . "
					" . (\dbObject\Parcours::hasIsPackColumn() ? "COALESCE(p.ispack, 0)" : "0") . " AS ispack
				FROM parcours_prerequisite pp
				INNER JOIN parcours p
					ON p.id = pp.IDparcours_required
				WHERE pp.IDparcours = :parcours_id
				ORDER BY p.title ASC, p.id ASC",
				['parcours_id' => (int)$parcoursId]
			);

			return is_array($rows) ? $rows : [];
		}

		protected static function collectReachableParcoursIds($startParcoursId, array &$visited)
		{
			$startParcoursId = (int)$startParcoursId;
			if ($startParcoursId <= 0 || isset($visited[$startParcoursId]) || !self::tableExists('parcours_prerequisite')) {
				return;
			}

			$visited[$startParcoursId] = true;
			$rows = self::fetchAll(
				"SELECT IDparcours_required
				FROM parcours_prerequisite
				WHERE IDparcours = :parcours_id",
				['parcours_id' => $startParcoursId]
			);
			if (!is_array($rows)) {
				return;
			}

			foreach ($rows as $row) {
				$requiredParcoursId = (int)($row['IDparcours_required'] ?? 0);
				if ($requiredParcoursId > 0) {
					self::collectReachableParcoursIds($requiredParcoursId, $visited);
				}
			}
		}

		public static function wouldCreateCycle($parcoursId, $requiredParcoursId)
		{
			$parcoursId = (int)$parcoursId;
			$requiredParcoursId = (int)$requiredParcoursId;
			if ($parcoursId <= 0 || $requiredParcoursId <= 0) {
				return true;
			}

			if ($parcoursId === $requiredParcoursId) {
				return true;
			}

			$visited = [];
			self::collectReachableParcoursIds($requiredParcoursId, $visited);
			return isset($visited[$parcoursId]);
		}

		public static function attachPrerequisite($parcoursId, $requiredParcoursId)
		{
			$parcoursId = (int)$parcoursId;
			$requiredParcoursId = (int)$requiredParcoursId;
			if ($parcoursId <= 0 || $requiredParcoursId <= 0 || $parcoursId === $requiredParcoursId) {
				return [
					'status' => false,
					'message' => 'Parcours invalide.',
				];
			}

			if (!self::tableExists('parcours_prerequisite')) {
				return [
					'status' => false,
					'message' => 'La table des prerequis de parcours est absente.',
				];
			}

			if (self::wouldCreateCycle($parcoursId, $requiredParcoursId)) {
				return [
					'status' => false,
					'message' => 'Ce prerequis creerait une boucle entre parcours.',
				];
			}

			$item = new self();
			$created = !$item->load([
				['IDparcours', $parcoursId],
				['IDparcours_required', $requiredParcoursId],
			]);

			if ($created) {
				$item->set('IDparcours', $parcoursId);
				$item->set('IDparcours_required', $requiredParcoursId);
			}

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return [
					'status' => false,
					'message' => is_array($saveResult) && !empty($saveResult['text'])
						? (string)$saveResult['text']
						: 'Impossible d ajouter ce prerequis.',
				];
			}

			return [
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			];
		}

		public static function detachPrerequisite($parcoursId, $requiredParcoursId)
		{
			$parcoursId = (int)$parcoursId;
			$requiredParcoursId = (int)$requiredParcoursId;
			if ($parcoursId <= 0 || $requiredParcoursId <= 0) {
				return [
					'status' => false,
					'message' => 'Parcours invalide.',
				];
			}

			if (!self::tableExists('parcours_prerequisite')) {
				return [
					'status' => false,
					'message' => 'La table des prerequis de parcours est absente.',
				];
			}

			$result = self::execute(
				"DELETE FROM parcours_prerequisite
				WHERE IDparcours = :parcours_id
				  AND IDparcours_required = :required_parcours_id",
				[
					'parcours_id' => $parcoursId,
					'required_parcours_id' => $requiredParcoursId,
				]
			);

			if (!$result) {
				return [
					'status' => false,
					'message' => 'Impossible de retirer ce prerequis.',
				];
			}

			return [
				'status' => true,
			];
		}
	}

?>
