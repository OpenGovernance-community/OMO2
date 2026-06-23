<?php
	namespace dbObject;

	class UserQuestionResponse extends DbObject
	{
		public static function tableName()
		{
			return 'user_question_response';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser', 'IDquestion', 'IDchoice', 'IDmission'], 'integer'],
				[['created_at'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Utilisateur',
				'IDquestion' => 'Question',
				'IDchoice' => 'Choix',
				'IDmission' => 'Mission',
				'created_at' => 'Cree le',
			];
		}

		public static function getOrder()
		{
			return 'created_at DESC, id DESC';
		}
	}

?>
