<?php
	namespace dbObject;

	class ParcoursPrerequisite extends DbObject
	{
        protected static function uiMessageSourceLang(): array
        {
            return [
                'parcours_invalide' => ['text' => 'Parcours invalide.', 'context' => 'Tutorial data validation and action feedback.'],
                'la_table_des_prerequis_de_parcours_est_absente' => ['text' => 'La table des prérequis de parcours est absente.', 'context' => 'Tutorial data validation and action feedback.'],
                'ce_prerequis_creerait_une_boucle_entre_parcours' => ['text' => 'Ce prérequis créerait une boucle entre parcours.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_d_ajouter_ce_prerequis' => ['text' => 'Impossible d’ajouter ce prérequis.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_retirer_ce_prerequis' => ['text' => 'Impossible de retirer ce prérequis.', 'context' => 'Tutorial data validation and action feedback.'],
            ];
        }

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
					'message' => self::uiMessage('parcours_invalide'),
				];
			}

			if (!self::tableExists('parcours_prerequisite')) {
				return [
					'status' => false,
					'message' => self::uiMessage('la_table_des_prerequis_de_parcours_est_absente'),
				];
			}

			if (self::wouldCreateCycle($parcoursId, $requiredParcoursId)) {
				return [
					'status' => false,
					'message' => self::uiMessage('ce_prerequis_creerait_une_boucle_entre_parcours'),
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
						: self::uiMessage('impossible_d_ajouter_ce_prerequis'),
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
					'message' => self::uiMessage('parcours_invalide'),
				];
			}

			if (!self::tableExists('parcours_prerequisite')) {
				return [
					'status' => false,
					'message' => self::uiMessage('la_table_des_prerequis_de_parcours_est_absente'),
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
					'message' => self::uiMessage('impossible_de_retirer_ce_prerequis'),
				];
			}

			return [
				'status' => true,
			];
		}
	}

?>
