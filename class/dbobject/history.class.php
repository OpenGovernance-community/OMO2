<?php
	namespace dbObject;

	class History extends DbObject
	{
	    public static function tableName()
		{
			return 'history';
		}

		public static function rules()
		{
			return [
				[['id'], 'integer'],
				[['IDorganization', 'IDuser'], 'fk'],
				[['action'], 'string'],
				[['content'], 'text'],
				[['parameters'], 'parameters'],
				[['datecreation'], 'datetime'],
				[['active'], 'boolean'],
				[['id', 'datecreation'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDuser' => 'Auteur',
				'action' => 'Action',
				'content' => 'Contenu',
				'parameters' => 'Paramètres',
				'datecreation' => 'Date',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'content' => "Texte historisé, indexable en full text, avec références typées comme [user|1|Nom].",
			];
		}

		public static function attributeLength()
		{
			return [
				'action' => 100,
			];
		}

		public static function getOrder()
		{
			return "datecreation DESC, id DESC";
		}

		public static function sanitizeReferenceLabel($label)
		{
			$label = trim((string)$label);
			$label = preg_replace('/[\[\]\|]+/u', ' ', $label);
			$label = preg_replace('/\s+/u', ' ', $label);

			return trim((string)$label);
		}

		public static function buildReferenceToken($type, $id, $label)
		{
			$type = trim(mb_strtolower((string)$type, 'UTF-8'));
			$id = (int)$id;
			$label = self::sanitizeReferenceLabel($label);

			return '[' . $type . '|' . $id . '|' . $label . ']';
		}

		public static function createEntry($organizationId, $authorUserId, $action, $content, array $parameters = array())
		{
			$entry = new self();
			$entry->set('IDorganization', (int)$organizationId > 0 ? (int)$organizationId : null);
			$entry->set('IDuser', (int)$authorUserId > 0 ? (int)$authorUserId : null);
			$entry->set('action', trim((string)$action));
			$entry->set('content', trim((string)$content));
			$entry->set('parameters', count($parameters) > 0 ? $parameters : null);
			$entry->set('active', true);

			return $entry->save();
		}
	}

?>
