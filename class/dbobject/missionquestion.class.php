<?php
	namespace dbObject;

	class MissionQuestion extends DbObject
	{
        protected static function uiMessageSourceLang(): array
        {
            return [
                'mission_ou_question_invalide' => ['text' => 'Mission ou question invalide.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_d_ajouter_cette_question_a_la_mission' => ['text' => 'Impossible d’ajouter cette question à la mission.', 'context' => 'Tutorial data validation and action feedback.'],
                'mission_invalide' => ['text' => 'Mission invalide.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_charger_les_questions_de_la_mission' => ['text' => 'Impossible de charger les questions de la mission.', 'context' => 'Tutorial data validation and action feedback.'],
                'la_liste_des_questions_a_reordonner_est_incomplete' => ['text' => 'La liste des questions à réordonner est incomplète.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_reordonner_les_questions_de_la_mission' => ['text' => 'Impossible de réordonner les questions de la mission.', 'context' => 'Tutorial data validation and action feedback.'],
            ];
        }

		public static function tableName()
		{
			return 'mission_question';
		}

		public static function rules()
		{
			return [
				[['id', 'IDmission', 'IDquestion', 'position'], 'integer'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDmission' => 'Mission',
				'IDquestion' => 'Question',
				'position' => 'Position',
			];
		}

		public static function getOrder() {
			return "position, id";
		}

		public static function fetchDetailedForMission($missionId)
		{
			if (!self::tableExists('mission_question')) {
				return [];
			}

			$choiceCountSql = self::tableExists('question_choice')
				? "(
						SELECT COUNT(*)
						FROM question_choice qc
						WHERE qc.IDquestion = q.id
					)"
				: "0";
			$correctChoiceCountSql = self::tableExists('question_choice')
				? "(
						SELECT COUNT(*)
						FROM question_choice qc
						WHERE qc.IDquestion = q.id
						  AND qc.is_correct = 1
					)"
				: "0";

			$rows = self::fetchAll(
				"SELECT
					mq.id,
					mq.IDmission,
					mq.IDquestion,
					mq.position,
					q.question,
					q.answer,
					q.detail,
					" . $choiceCountSql . " AS choice_count,
					" . $correctChoiceCountSql . " AS correct_choice_count
				FROM mission_question mq
				INNER JOIN question q
					ON q.id = mq.IDquestion
				WHERE mq.IDmission = :mission_id
				ORDER BY COALESCE(mq.position, q.displayorder, mq.id) ASC, mq.id ASC",
				['mission_id' => (int)$missionId]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function attachQuestionToMission($missionId, $questionId, array $options = array())
		{
			$missionId = (int)$missionId;
			$questionId = (int)$questionId;
			if ($missionId <= 0 || $questionId <= 0) {
				return array(
					'status' => false,
					'message' => self::uiMessage('mission_ou_question_invalide'),
				);
			}

			$item = new self();
			$created = !$item->load([
				['IDmission', $missionId],
				['IDquestion', $questionId],
			]);

			if ($created) {
				$item->set('IDmission', $missionId);
				$item->set('IDquestion', $questionId);
			}

			$position = array_key_exists('position', $options) ? (int)$options['position'] : 0;
			if ($created && $position <= 0) {
				$position = (int)self::fetchValue(
					"SELECT COALESCE(MAX(position), 0) + 1
					FROM mission_question
					WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
			}

			if ($created || array_key_exists('position', $options)) {
				$item->set('position', $position > 0 ? $position : null);
			}

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return array(
					'status' => false,
					'message' => is_array($saveResult) && !empty($saveResult['text'])
						? (string)$saveResult['text']
						: self::uiMessage('impossible_d_ajouter_cette_question_a_la_mission'),
				);
			}

			return array(
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			);
		}

		public static function reorderForMission($missionId, array $questionIds)
		{
			$missionId = (int)$missionId;
			if ($missionId <= 0) {
				return array(
					'status' => false,
					'message' => self::uiMessage('mission_invalide'),
				);
			}

			$normalizedQuestionIds = [];
			foreach ($questionIds as $questionId) {
				$questionId = (int)$questionId;
				if ($questionId > 0) {
					$normalizedQuestionIds[$questionId] = $questionId;
				}
			}
			$normalizedQuestionIds = array_values($normalizedQuestionIds);

			$currentRows = self::fetchAll(
				"SELECT IDquestion
				FROM mission_question
				WHERE IDmission = :mission_id",
				['mission_id' => $missionId]
			);
			if (!is_array($currentRows)) {
				return array(
					'status' => false,
					'message' => self::uiMessage('impossible_de_charger_les_questions_de_la_mission'),
				);
			}

			$currentQuestionIds = [];
			foreach ($currentRows as $row) {
				$questionId = (int)($row['IDquestion'] ?? 0);
				if ($questionId > 0) {
					$currentQuestionIds[$questionId] = $questionId;
				}
			}
			$currentQuestionIds = array_values($currentQuestionIds);
			sort($currentQuestionIds);

			$comparisonQuestionIds = $normalizedQuestionIds;
			sort($comparisonQuestionIds);
			if ($comparisonQuestionIds !== $currentQuestionIds) {
				return array(
					'status' => false,
					'message' => self::uiMessage('la_liste_des_questions_a_reordonner_est_incomplete'),
				);
			}

			foreach ($normalizedQuestionIds as $index => $questionId) {
				$result = self::execute(
					"UPDATE mission_question
					SET position = :position
					WHERE IDmission = :mission_id
					  AND IDquestion = :question_id",
					[
						'position' => $index + 1,
						'mission_id' => $missionId,
						'question_id' => $questionId,
					]
				);
				if (!$result) {
					return array(
						'status' => false,
						'message' => self::uiMessage('impossible_de_reordonner_les_questions_de_la_mission'),
					);
				}
			}

			return array(
				'status' => true,
			);
		}
	}

?>
