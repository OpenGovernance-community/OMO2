<?php
	namespace dbObject;

	class QuestionChoice extends DbObject
	{
        protected static function uiMessageSourceLang(): array
        {
            return [
                'question_invalide' => ['text' => 'Question invalide.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_de_reinitialiser_les_choix_de_cette_question' => ['text' => 'Impossible de réinitialiser les choix de cette question.', 'context' => 'Tutorial data validation and action feedback.'],
                'impossible_d_enregistrer_un_choix_de_reponse' => ['text' => 'Impossible d’enregistrer un choix de réponse.', 'context' => 'Tutorial data validation and action feedback.'],
            ];
        }

		public static function tableName()
		{
			return 'question_choice';
		}

		public static function rules()
		{
			return [
				[['id', 'IDquestion'], 'integer'],
				[['label'], 'text'],
				[['is_correct'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDquestion' => 'Question',
				'label' => 'Label',
				'is_correct' => 'Correct',
			];
		}

		public static function getOrder() {
			return "id";
		}

		public static function fetchForQuestion($questionId)
		{
			$rows = self::fetchAll(
				"SELECT id, IDquestion, label, is_correct
				FROM question_choice
				WHERE IDquestion = :question_id
				ORDER BY id ASC",
				['question_id' => (int)$questionId]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function replaceForQuestion($questionId, array $choices)
		{
			$questionId = (int)$questionId;
			if ($questionId <= 0) {
				return array(
					'status' => false,
					'message' => self::uiMessage('question_invalide'),
				);
			}

			$deleteResult = self::execute(
				"DELETE FROM question_choice WHERE IDquestion = :question_id",
				['question_id' => $questionId]
			);
			if (!$deleteResult) {
				return array(
					'status' => false,
					'message' => self::uiMessage('impossible_de_reinitialiser_les_choix_de_cette_question'),
				);
			}

			foreach ($choices as $choiceData) {
				$label = trim((string)($choiceData['label'] ?? ''));
				if ($label === '') {
					continue;
				}

				$choice = new self();
				$choice->set('IDquestion', $questionId);
				$choice->set('label', $label);
				$choice->set('is_correct', !empty($choiceData['is_correct']));

				$saveResult = $choice->save();
				if (!is_array($saveResult) || empty($saveResult['status'])) {
					return array(
						'status' => false,
						'message' => is_array($saveResult) && !empty($saveResult['text'])
							? (string)$saveResult['text']
							: self::uiMessage('impossible_d_enregistrer_un_choix_de_reponse'),
					);
				}
			}

			return array(
				'status' => true,
			);
		}

		public static function isSelectionCorrect($selected, $questionId = null) {
			if (!is_array($selected) || count($selected)===0) {
				return false;
			}

			if ($questionId === null) {
				$questionId = self::fetchValue(
					"SELECT IDquestion FROM question_choice WHERE id = :choice_id",
					['choice_id' => (int)$selected[0]]
				);
			}

			if (!$questionId) {
				return false;
			}

			$all = self::fetchAll(
				"SELECT id, is_correct FROM question_choice WHERE IDquestion = :question_id",
				['question_id' => (int)$questionId]
			);

			if ($all === false) {
				return false;
			}

			$correctIds = array();
			foreach ($all as $choice) {
				if ((int)$choice['is_correct'] > 0) {
					$correctIds[] = (int)$choice['id'];
				}
			}

			$selected = array_map('intval', $selected);
			sort($selected);
			sort($correctIds);

			return $selected == $correctIds;
		}
	}

?>
