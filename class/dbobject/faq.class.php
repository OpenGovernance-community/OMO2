<?php
	namespace dbObject;

	class Faq extends DbObject
	{
		public static function tableName()
		{
			return 'faq';
		}

		public static function rules()
		{
			return [
				[['question', 'answer'], 'required'],
				[['id', 'IDhowto', 'displayorder'], 'integer'],
				[['question'], 'string'],
				[['answer', 'detail'], 'text'],
				[['isactive'], 'boolean'],
				[['created', 'updated'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDhowto' => 'Howto',
				'question' => 'Question',
				'answer' => 'Reponse',
				'detail' => 'Detail',
				'displayorder' => 'Ordre',
				'isactive' => 'Actif',
				'created' => 'Cree le',
				'updated' => 'Mis a jour le',
			];
		}

		public static function attributeLength() {
			return [
				'question' => 255,
			];
		}

		public static function getOrder() {
			return "displayorder, id";
		}

		public function getChoices() {
			$choices = new \dbObject\ArrayFaqChoice();
			$choices->load([
				'where' => [
					['field' => 'IDfaq', 'value' => $this->getId()],
				],
				'orderBy' => [
					['field' => 'id', 'dir' => 'ASC'],
				],
			]);
			return $choices;
		}

		public static function fetchQuestionsForMission($missionId) {
			$query = "
				SELECT f.id, f.question
				FROM mission_faq mf
				JOIN faq f ON f.id = mf.IDfaq
				WHERE mf.IDmission = :mission_id
				ORDER BY mf.position ASC, mf.id ASC
			";

			$rows = self::fetchAll($query, ['mission_id' => (int)$missionId]);
			if ($rows === false) {
				return false;
			}

			foreach ($rows as &$row) {
				$choiceRows = self::fetchAll(
					"SELECT id, label, is_correct FROM faq_choice WHERE IDfaq = :faq_id ORDER BY id ASC",
					['faq_id' => (int)$row['id']]
				);
				$row['choices'] = $choiceRows === false ? [] : $choiceRows;

				$correctCount = 0;
				foreach ($row['choices'] as $choice) {
					if ((int)$choice['is_correct'] > 0) {
						$correctCount++;
					}
				}

				$row['multiple'] = $correctCount > 1;
			}

			return $rows;
		}
	}

?>
