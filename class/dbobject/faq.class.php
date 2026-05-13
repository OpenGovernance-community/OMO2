<?php

namespace dbObject;

class FAQ extends DbObject
{
	protected static $_hasViewcountColumn = null;

	public static function tableName()
	{
		return 'faq';
	}

	public static function rules()
	{
		return [
			[['question', 'answer'], 'required'],
			[['id', 'IDhowto', 'displayorder', 'viewcount'], 'integer'],
			[['IDholon'], 'fk'],
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
			'IDholon' => 'Holon',
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

	public static function hasViewcountColumn()
	{
		if (self::$_hasViewcountColumn !== null) {
			return self::$_hasViewcountColumn;
		}

		$databaseName = (string)($GLOBALS["dbName"] ?? "");
		if ($databaseName === "") {
			self::$_hasViewcountColumn = false;
			return self::$_hasViewcountColumn;
		}

		$columnCount = self::fetchValue(
			"select count(*) from information_schema.columns where table_schema = :schema and table_name = :table and column_name = :column",
			[
				"schema" => $databaseName,
				"table" => self::tableName(),
				"column" => "viewcount",
			]
		);

		self::$_hasViewcountColumn = ((int)$columnCount > 0);
		return self::$_hasViewcountColumn;
	}

	public static function getPopupOrderBy()
	{
		$orderBy = [
			['field' => 'displayorder', 'dir' => 'ASC'],
			['field' => 'updated', 'dir' => 'DESC'],
		];

		if (self::hasViewcountColumn()) {
			array_unshift($orderBy, ['field' => 'viewcount', 'dir' => 'DESC']);
		}

		return $orderBy;
	}

	public static function resolvePopupContext($organizationId = 0, $currentHolonId = 0)
	{
		$organizationId = (int)$organizationId;
		$currentHolonId = (int)$currentHolonId;
		$context = array(
			'organizationId' => 0,
			'currentHolonId' => 0,
			'organization' => null,
			'rootHolon' => null,
			'currentHolon' => null,
		);

		if ($organizationId <= 0) {
			return $context;
		}

		$organization = new \dbObject\Organization();
		if (!$organization->load($organizationId) || !$organization->canViewDetail()) {
			return false;
		}

		$rootHolon = $organization->getStructuralRootHolon();
		if (!$rootHolon) {
			return false;
		}

		$currentHolon = $rootHolon;
		if ($currentHolonId > 0 && (int)$rootHolon->getId() !== $currentHolonId) {
			$candidate = new \dbObject\Holon();
			if (
				!$candidate->load($currentHolonId)
				|| !$candidate->isDescendantOf($rootHolon->getId())
				|| !$candidate->canViewDetail()
			) {
				return false;
			}

			$currentHolon = $candidate;
		}

		$context['organizationId'] = (int)$organization->getId();
		$context['currentHolonId'] = (int)$currentHolon->getId();
		$context['organization'] = $organization;
		$context['rootHolon'] = $rootHolon;
		$context['currentHolon'] = $currentHolon;

		return $context;
	}

	public static function buildPopupLoadParams(array $context = array())
	{
		$params = array(
			'where' => array(
				array('field' => 'isactive', 'value' => 1),
			),
			'orderBy' => self::getPopupOrderBy(),
		);

		$currentHolon = isset($context['currentHolon']) && $context['currentHolon'] instanceof \dbObject\Holon
			? $context['currentHolon']
			: null;

		if ($currentHolon && (int)$currentHolon->getId() > 0) {
			$params['whereAny'] = array(
				array('field' => 'IDholon', 'op' => 'is null'),
				array('field' => 'IDholon', 'op' => 'in', 'value' => $currentHolon->getVisibleDescendantIds(true)),
			);
		} else {
			$params['where'][] = array('field' => 'IDholon', 'op' => 'is null');
		}

		return $params;
	}

	public static function canCreateContextualForHolon($holon, $userId = 0, $organizationId = 0)
	{
		if (!$holon instanceof \dbObject\Holon || (int)$holon->getId() <= 0) {
			return false;
		}

		$userId = (int)$userId;
		$organizationId = (int)$organizationId;
		if ($userId <= 0) {
			return false;
		}

		if ($holon->canEdit()) {
			return true;
		}

		$memberUserIds = $holon->getAssociatedMemberUserIds(array(
			'organizationId' => $organizationId,
			'includeDescendants' => true,
		));

		return in_array($userId, array_map('intval', $memberUserIds), true);
	}

	public function incrementViewcount()
	{
		if (!self::hasViewcountColumn()) {
			return false;
		}

		$this->set("viewcount", (int)$this->get("viewcount") + 1);
		return $this->save();
	}

	public function getContextHolon()
	{
		$holonId = (int)$this->get('IDholon');
		if ($holonId <= 0) {
			return null;
		}

		$holon = new \dbObject\Holon();
		return $holon->load($holonId) ? $holon : null;
	}

	public function canBeViewedInContext(array $context = array())
	{
		if (!(int)$this->get('isactive')) {
			return false;
		}

		$holon = $this->getContextHolon();
		if (!$holon) {
			return true;
		}

		if (!$holon->canViewDetail()) {
			return false;
		}

		$currentHolon = isset($context['currentHolon']) && $context['currentHolon'] instanceof \dbObject\Holon
			? $context['currentHolon']
			: null;

		if ($currentHolon && (int)$currentHolon->getId() > 0) {
			return $holon->isDescendantOf($currentHolon->getId(), true);
		}

		return true;
	}

	public function getShortAnswer($length = 120)
	{
		return mb_strimwidth(strip_tags((string)$this->get("answer")), 0, $length, "...");
	}
}

?>
