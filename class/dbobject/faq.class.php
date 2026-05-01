<?php

namespace dbObject;

class FAQ extends DbObject
{
	public static function tableName()
	{
		return 'faq';
	}

	public static function rules()
	{
		return [
			[['question', 'answer'], 'required'],
			[['id', 'IDhowto', 'displayorder', 'viewcount'], 'integer'],
			[['question'], 'string'],
			[['answer'], 'text'],
			[['detail'], 'html'],
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
			'detail' => 'Réponse complète',
			'answer' => 'Réponse courte',
			'displayorder' => 'Ordre',
			'isactive' => 'Active',
			'created' => 'Créée le',
			'updated' => 'Mise à jour le',
			'viewcount' => 'Nombre de vues',
		];
	}

	public static function attributeLength()
	{
		return [
			'question' => 255,
		];
	}

	public static function getOrder()
	{
		return "displayorder ASC, updated DESC";
	}

	public function getShortAnswer($length = 120)
	{
		return mb_strimwidth(strip_tags((string)$this->get("answer")), 0, $length, "...");
	}
}

?>
