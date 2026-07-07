<?php
	namespace dbObject;

	class QuestionChoice extends DbObject
	{
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
					'message' => 'Question invalide.',
				);
			}

			$deleteResult = self::execute(
				"DELETE FROM question_choice WHERE IDquestion = :question_id",
				['question_id' => $questionId]
			);
			if (!$deleteResult) {
				return array(
					'status' => false,
					'message' => 'Impossible de reinitialiser les choix de cette question.',
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
							: 'Impossible d enregistrer un choix de reponse.',
					);
				}
			}

			return array(
				'status' => true,
			);
		}

		public static function isSelectionCorrect($selected) {
			if (!is_array($selected) || count($selected)===0) {
				return false;
			}

			$questionId = self::fetchValue(
				"SELECT IDquestion FROM question_choice WHERE id = :choice_id",
				['choice_id' => (int)$selected[0]]
			);

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
