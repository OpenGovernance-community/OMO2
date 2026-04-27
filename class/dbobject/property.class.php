<?php
	namespace dbObject;


	class Property extends DbObject
	{
		public const LIST_ITEM_TEXT = 'text';
		public const LIST_ITEM_NUMBER = 'number';
		public const LIST_ITEM_DATE = 'date';
		public const LIST_ITEM_HOLON = 'holon';

	    public static function tableName()
		{
			return 'property'; // Nom de la table correspondante
		}	
		
		// Défini le contenu de la table
		public static function rules()
		{
			return [
				[['id'], 'required'],				// Champs obligatoires
				[['id','position'], 'integer'],					
				[['name','shortname','listitemtype','listholontypeids'], 'string'],			// Texte libre
				[['IDpropertyformat','IDholon_organization'], 'fk'],			// Texte libre
				[['datecreation'], 'datetime'],				// Clé étrangères
				[['active'], 'boolean'],				// Booléens
				[['id'], 'safe'],								// Champs protégés (n'apparaissent pas dans les formulaires)
			];
		}
		
		// Défini les labels standarts pour cet objet, affichés dans les formulaires automatiques
		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'name' => 'Nom',
				'shortname' => 'Nom court',
				'IDpropertyformat' => 'Format',
				'listitemtype' => "Type d'éléments de liste",
				'listholontypeids' => 'Types de holons autorisés',
				'IDholon_organization' => 'Organisation',
				'datecreation' => 'Date de création',
				'position' => 'Position',
				'active' => 'Actif ?',
			];
		}

		// Retourne la valeur de base pour le tri
		public static function getOrder() {
			return "name";
		}

		public static function buildShortnameFromName($name)
		{
			$name = trim((string)$name);
			if ($name === '') {
				return 'property';
			}

			$normalized = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
			if (!is_string($normalized) || trim($normalized) === '') {
				$normalized = $name;
			}

			$normalized = strtolower($normalized);
			$normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
			$normalized = trim((string)$normalized, '_');

			if ($normalized === '') {
				$normalized = 'property';
			}

			return substr($normalized, 0, 20);
		}

		public static function getListItemTypeOptions()
		{
			return array(
				array('id' => self::LIST_ITEM_TEXT, 'name' => 'Texte'),
				array('id' => self::LIST_ITEM_NUMBER, 'name' => 'Chiffre'),
				array('id' => self::LIST_ITEM_DATE, 'name' => 'Date'),
				array('id' => self::LIST_ITEM_HOLON, 'name' => 'Holon'),
			);
		}

		public static function normalizeListItemType($value)
		{
			$value = trim((string)$value);
			$allowed = array(
				self::LIST_ITEM_TEXT,
				self::LIST_ITEM_NUMBER,
				self::LIST_ITEM_DATE,
				self::LIST_ITEM_HOLON,
			);

			return in_array($value, $allowed, true) ? $value : self::LIST_ITEM_TEXT;
		}

		public static function parseHolonTypeIds($value)
		{
			if (is_array($value)) {
				$items = $value;
			} else {
				$value = trim((string)$value);
				$items = $value === '' ? array() : explode(',', $value);
			}

			$typeIds = array();
			foreach ($items as $item) {
				$typeId = (int)$item;
				if ($typeId > 0) {
					$typeIds[$typeId] = $typeId;
				}
			}

			return array_values($typeIds);
		}

		public static function serializeHolonTypeIds($value)
		{
			$typeIds = self::parseHolonTypeIds($value);
			return count($typeIds) > 0 ? implode(',', $typeIds) : null;
		}
	}
	
?>
