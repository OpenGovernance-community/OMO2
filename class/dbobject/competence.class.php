<?php
	namespace dbObject;

	class Competence extends DbObject
	{
		public static function tableName()
		{
			return 'competence';
		}

		public static function rules()
		{
			return [
				[['id', 'IDorganization'], 'integer'],
				[['name', 'normalized_name', 'category'], 'string'],
				[['datecreation'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'name' => 'Competence',
				'normalized_name' => 'Nom normalise',
				'category' => 'Type',
				'datecreation' => 'Creation',
			];
		}

		public static function attributeLength()
		{
			return [
				'name' => 190,
				'normalized_name' => 190,
				'category' => 30,
			];
		}

		public static function getOrder()
		{
			return 'name ASC, id ASC';
		}

		public static function normalizeName($value)
		{
			$value = trim((string)$value);
			$value = preg_replace('/\s+/u', ' ', $value);
			$value = is_string($value) ? trim($value) : '';
			if ($value === '') {
				return '';
			}

			return function_exists('mb_substr')
				? mb_substr($value, 0, 190, 'UTF-8')
				: substr($value, 0, 190);
		}

		public static function normalizeSearchName($value)
		{
			$value = mb_strtolower(self::normalizeName($value), 'UTF-8');
			$ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
			if (is_string($ascii) && $ascii !== '') {
				$value = $ascii;
			}

			$value = preg_replace('/[^a-z0-9]+/', ' ', (string)$value);
			$value = is_string($value) ? trim($value) : '';

			return substr($value, 0, 190);
		}

		public static function normalizeCategory($value)
		{
			$value = trim((string)$value);
			return $value === 'soft' ? 'soft' : 'technical';
		}

		public static function findByNormalizedName($normalizedName, $category, $organizationId = 0)
		{
			$normalizedName = trim((string)$normalizedName);
			$category = self::normalizeCategory($category);
			$organizationId = (int)$organizationId;

			if ($normalizedName === '') {
				return false;
			}

			$sql = "
				SELECT *
				FROM competence
				WHERE normalized_name = :normalized_name
				  AND category = :category
			";
			$params = [
				'normalized_name' => $normalizedName,
				'category' => $category,
			];

			if ($organizationId > 0) {
				$sql .= " AND IDorganization = :organization_id";
				$params['organization_id'] = $organizationId;
			} else {
				$sql .= " AND IDorganization IS NULL";
			}

			$sql .= " ORDER BY id ASC LIMIT 1";

			$row = self::fetchRow($sql, $params);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findOrCreate($name, $category, $organizationId = 0)
		{
			$name = self::normalizeName($name);
			$normalizedName = self::normalizeSearchName($name);
			$category = self::normalizeCategory($category);
			$organizationId = (int)$organizationId;

			if ($name === '' || $normalizedName === '') {
				return false;
			}

			$item = self::findByNormalizedName($normalizedName, $category, $organizationId);
			if ($item instanceof self) {
				return $item;
			}

			$item = new self();
			$item->set('IDorganization', $organizationId > 0 ? $organizationId : null);
			$item->set('name', $name);
			$item->set('normalized_name', $normalizedName);
			$item->set('category', $category);
			$item->set('datecreation', new \DateTime());

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return false;
			}

			return $item;
		}

		public function getCategoryLabel()
		{
			return $this->get('category') === 'soft' ? 'Soft skill' : 'Technique';
		}
	}

?>
