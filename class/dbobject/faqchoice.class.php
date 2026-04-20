<?php
	namespace dbObject;

	class FaqChoice extends DbObject
	{
		public static function tableName()
		{
			return 'faq_choice';
		}

		public static function rules()
		{
			return [
				[['id', 'IDfaq'], 'integer'],
				[['label'], 'text'],
				[['is_correct'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDfaq' => 'FAQ',
				'label' => 'Label',
				'is_correct' => 'Correct',
			];
		}

		public static function getOrder() {
			return "id";
		}

		public static function isSelectionCorrect($selected) {
			if (!is_array($selected) || count($selected)===0) {
				return false;
			}

			$faqId = self::fetchValue(
				"SELECT IDfaq FROM faq_choice WHERE id = :choice_id",
				['choice_id' => (int)$selected[0]]
			);

			if (!$faqId) {
				return false;
			}

			$all = self::fetchAll(
				"SELECT id, is_correct FROM faq_choice WHERE IDfaq = :faq_id",
				['faq_id' => (int)$faqId]
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
