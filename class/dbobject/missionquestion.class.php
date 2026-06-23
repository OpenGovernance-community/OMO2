<?php
	namespace dbObject;

	class MissionQuestion extends DbObject
	{
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
					'message' => 'Mission ou question invalide.',
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
						: 'Impossible d ajouter cette question a la mission.',
				);
			}

			return array(
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			);
		}
	}

?>
