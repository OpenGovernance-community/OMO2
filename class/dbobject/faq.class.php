<?php

namespace dbObject;

class FAQ extends DbObject
{
	protected static $_hasViewcountColumn = null;
	protected static $_organizationIdByHolonId = array();

	public static function tableName()
	{
		return 'faq';
	}

	public static function rules()
	{
		return [
			[['question', 'answer'], 'required'],
			[['id', 'IDhowto', 'IDorganization', 'displayorder', 'viewcount'], 'integer'],
			[['IDorganization', 'IDholon'], 'fk'],
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
			'IDorganization' => 'Organisation',
			'IDholon' => 'Holon',
			'question' => 'Question',
			'detail' => 'Reponse complete',
			'answer' => 'Reponse courte',
			'displayorder' => 'Ordre',
			'isactive' => 'Active',
			'created' => 'Creee le',
			'updated' => 'Mise a jour le',
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

	public static function resolvePopupRequestContext(array $request = array())
	{
		$organizationId = isset($request['oid']) && is_numeric($request['oid'])
			? (int)$request['oid']
			: 0;
		$currentHolonId = isset($request['cid']) && is_numeric($request['cid'])
			? (int)$request['cid']
			: 0;

		return self::resolvePopupContext($organizationId, $currentHolonId);
	}

	public static function normalizePopupScope($scope = null, array $context = array())
	{
		$scope = strtolower(trim((string)$scope));
		if ($scope !== 'global' && $scope !== 'contextual') {
			$scope = 'contextual';
		}

		if ($scope === 'global' && (int)($context['organizationId'] ?? 0) <= 0) {
			return 'contextual';
		}

		return $scope;
	}

	public static function buildPopupLoadParams(array $context = array(), $scope = 'contextual')
	{
		$viewerAccess = self::resolveViewerAccess($context);
		$params = array(
			'orderBy' => self::getPopupOrderBy(),
		);

		if (empty($viewerAccess['canManageAllFaqs']) && empty($viewerAccess['canManageOrganizationFaqs'])) {
			$params['where'] = array(
				array('field' => 'isactive', 'value' => 1),
			);
		}

		return $params;
	}

	protected static function resolveCurrentUserId()
	{
		return function_exists('commonGetCurrentUserId')
			? (int)\commonGetCurrentUserId()
			: (int)($_SESSION['currentUser'] ?? 0);
	}

	public static function currentViewerHasSiteAdminAccess()
	{
		return function_exists('commonCurrentUserIsSiteAdminModeEnabled')
			&& \commonCurrentUserIsSiteAdminModeEnabled();
	}

	public static function currentViewerHasOrganizationAdminAccess($organizationId = 0)
	{
		$organizationId = (int)$organizationId;
		if ($organizationId <= 0) {
			return false;
		}

		if (self::currentViewerHasSiteAdminAccess()) {
			return true;
		}

		return function_exists('commonCurrentUserIsAdminModeEnabled')
			&& \commonCurrentUserIsAdminModeEnabled($organizationId);
	}

	public static function resolveViewerAccess(array $context = array())
	{
		$organizationId = (int)($context['organizationId'] ?? 0);

		return array(
			'userId' => self::resolveCurrentUserId(),
			'organizationId' => $organizationId,
			'canManageAllFaqs' => self::currentViewerHasSiteAdminAccess(),
			'canManageOrganizationFaqs' => self::currentViewerHasOrganizationAdminAccess($organizationId),
		);
	}

	public static function loadPopupCollection(array $context = array(), $scope = 'contextual')
	{
		$scope = self::normalizePopupScope($scope, $context);
		$allFaq = new \dbObject\ArrayFAQ();
		$allFaq->load(self::buildPopupLoadParams($context, $scope));

		$filteredFaq = new \dbObject\ArrayFAQ();
		foreach ($allFaq as $faq) {
			if ($faq instanceof self && $faq->canBeViewedInContext($context, $scope)) {
				$filteredFaq->append($faq);
			}
		}

		return $filteredFaq;
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

	protected static function resolveOrganizationIdForHolon($holonId)
	{
		$holonId = (int)$holonId;
		if ($holonId <= 0) {
			return 0;
		}

		if (array_key_exists($holonId, self::$_organizationIdByHolonId)) {
			return (int)self::$_organizationIdByHolonId[$holonId];
		}

		$holon = new \dbObject\Holon();
		if (!$holon->load($holonId)) {
			self::$_organizationIdByHolonId[$holonId] = 0;
			return 0;
		}

		self::$_organizationIdByHolonId[$holonId] = (int)$holon->get('IDorganization');
		return (int)self::$_organizationIdByHolonId[$holonId];
	}

	public function getResolvedOrganizationId()
	{
		$organizationId = (int)$this->get('IDorganization');
		if ($organizationId > 0) {
			return $organizationId;
		}

		return self::resolveOrganizationIdForHolon((int)$this->get('IDholon'));
	}

	public function getResolvedOrganization()
	{
		$organizationId = $this->getResolvedOrganizationId();
		if ($organizationId <= 0) {
			return null;
		}

		$organization = new \dbObject\Organization();
		return $organization->load($organizationId) ? $organization : null;
	}

	public function isGeneric()
	{
		return (int)$this->getResolvedOrganizationId() <= 0 && (int)$this->get('IDholon') <= 0;
	}

	public function canBeEditedInContext(array $context = array())
	{
		$viewerAccess = self::resolveViewerAccess($context);
		if (!empty($viewerAccess['canManageAllFaqs'])) {
			return true;
		}

		$organizationId = $this->getResolvedOrganizationId();
		if ($organizationId <= 0) {
			return false;
		}

		return !empty($viewerAccess['canManageOrganizationFaqs'])
			&& $organizationId === (int)($viewerAccess['organizationId'] ?? 0);
	}

	public function canBeDetachedInContext(array $context = array())
	{
		$viewerAccess = self::resolveViewerAccess($context);
		return !empty($viewerAccess['canManageAllFaqs']);
	}

	public function canBeViewedInContext(array $context = array(), $scope = 'contextual')
	{
		$scope = self::normalizePopupScope($scope, $context);
		$viewerAccess = self::resolveViewerAccess($context);
		$faqOrganizationId = $this->getResolvedOrganizationId();
		$holon = $this->getContextHolon();
		$contextOrganizationId = (int)($context['organizationId'] ?? 0);
		$currentHolon = isset($context['currentHolon']) && $context['currentHolon'] instanceof \dbObject\Holon
			? $context['currentHolon']
			: null;
		$matchesScope = false;

		if ($scope === 'global') {
			if (!empty($viewerAccess['canManageAllFaqs'])) {
				$matchesScope = true;
			} elseif ($faqOrganizationId <= 0) {
				$matchesScope = true;
			} else {
				$matchesScope = $faqOrganizationId === $contextOrganizationId;
			}
		} else {
			if ($holon instanceof \dbObject\Holon) {
				if ($currentHolon && (int)$currentHolon->getId() > 0) {
					$matchesScope = $currentHolon->isDescendantOf($holon->getId(), true);
				} else {
					$matchesScope = false;
				}
			} elseif ($faqOrganizationId <= 0) {
				$matchesScope = true;
			} else {
				$matchesScope = $faqOrganizationId === $contextOrganizationId;
			}
		}

		if (!$matchesScope) {
			return false;
		}

		if (
			!empty($viewerAccess['canManageAllFaqs'])
			|| (
				!empty($viewerAccess['canManageOrganizationFaqs'])
				&& $faqOrganizationId > 0
				&& $faqOrganizationId === (int)($viewerAccess['organizationId'] ?? 0)
			)
		) {
			return true;
		}

		if (!(int)$this->get('isactive')) {
			return false;
		}

		if ($holon && !$holon->canViewDetail()) {
			return false;
		}

		return true;
	}

	public function getShortAnswer($length = 120)
	{
		return mb_strimwidth(strip_tags((string)$this->get("answer")), 0, $length, "...");
	}
}

?>
