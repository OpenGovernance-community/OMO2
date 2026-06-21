<?php

namespace dbObject;

class FAQ extends DbObject
{
	protected static $_hasViewcountColumn = null;
	protected static $_hasVoteColumns = null;
	protected static $_hasScoreAnalyticsColumns = null;
	protected static $_organizationIdByHolonId = array();

	public static function tableName()
	{
		return 'faq';
	}

	public static function rules()
	{
		return [
			[['question', 'answer'], 'required'],
			[['id', 'IDhowto', 'IDorganization', 'displayorder', 'viewcount', 'total_votes'], 'integer'],
			[['IDorganization', 'IDholon'], 'fk'],
			[['positive_score', 'negative_score', 'reliability'], 'float'],
			[['question'], 'string'],
			[['video'], 'string'],
			[['answer'], 'text'],
			[['detail'], 'html'],
			[['image'], 'image'],
			[['isactive'], 'boolean'],
			[['created', 'updated', 'reliability_updated_at', 'score_decayed_at'], 'datetime'],
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
			'image' => 'Image',
			'video' => 'Video',
			'displayorder' => 'Ordre',
			'isactive' => 'Active',
			'created' => 'Creee le',
			'updated' => 'Mise a jour le',
			'viewcount' => 'Nombre de vues',
			'positive_score' => 'Votes positifs',
			'negative_score' => 'Votes negatifs',
			'total_votes' => 'Votes totaux',
			'reliability' => 'Reliability',
			'reliability_updated_at' => 'Reliability mise a jour le',
			'score_decayed_at' => 'Score oublie le',
		];
	}

	public static function attributeLength()
	{
		return [
			'question' => 255,
			'video' => 1000,
			'image' => [480, 270],
		];
	}

	public static function attributePlaceholder()
	{
		return [
			'video' => 'https://vimeo.com/...',
		];
	}

	public static function getOrder()
	{
		return "displayorder ASC, updated DESC";
	}

	public static function getScoreHalfLifeDays()
	{
		$halfLifeDays = defined('FAQ_SCORE_HALF_LIFE_DAYS')
			? (float)constant('FAQ_SCORE_HALF_LIFE_DAYS')
			: 60.0;
		return $halfLifeDays > 0 ? $halfLifeDays : 60.0;
	}

	public static function getFakeCronIntervalSeconds()
	{
		$intervalSeconds = defined('FAQ_FAKE_CRON_INTERVAL_SECONDS')
			? (int)constant('FAQ_FAKE_CRON_INTERVAL_SECONDS')
			: 86400;
		return $intervalSeconds > 0 ? $intervalSeconds : 86400;
	}

	public static function getFakeCronBatchSize()
	{
		$batchSize = defined('FAQ_FAKE_CRON_BATCH_SIZE')
			? (int)constant('FAQ_FAKE_CRON_BATCH_SIZE')
			: 50;
		return $batchSize > 0 ? $batchSize : 50;
	}

	public static function getFakeCronLockTtlSeconds()
	{
		$lockTtl = defined('FAQ_FAKE_CRON_LOCK_TTL_SECONDS')
			? (int)constant('FAQ_FAKE_CRON_LOCK_TTL_SECONDS')
			: 300;
		return $lockTtl > 0 ? $lockTtl : 300;
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

	public static function hasVoteColumns()
	{
		if (self::$_hasVoteColumns !== null) {
			return self::$_hasVoteColumns;
		}

		$databaseName = (string)($GLOBALS["dbName"] ?? "");
		if ($databaseName === "") {
			self::$_hasVoteColumns = false;
			return self::$_hasVoteColumns;
		}

		$columnCount = self::fetchValue(
			"select count(*) from information_schema.columns where table_schema = :schema and table_name = :table and column_name in ('positive_score', 'negative_score', 'total_votes')",
			[
				"schema" => $databaseName,
				"table" => self::tableName(),
			]
		);

		self::$_hasVoteColumns = ((int)$columnCount >= 3);
		return self::$_hasVoteColumns;
	}

	public static function hasScoreAnalyticsColumns()
	{
		if (self::$_hasScoreAnalyticsColumns !== null) {
			return self::$_hasScoreAnalyticsColumns;
		}

		$databaseName = (string)($GLOBALS["dbName"] ?? "");
		if ($databaseName === "") {
			self::$_hasScoreAnalyticsColumns = false;
			return self::$_hasScoreAnalyticsColumns;
		}

		$columnCount = self::fetchValue(
			"select count(*) from information_schema.columns where table_schema = :schema and table_name = :table and column_name in ('reliability', 'reliability_updated_at', 'score_decayed_at')",
			[
				"schema" => $databaseName,
				"table" => self::tableName(),
			]
		);

		self::$_hasScoreAnalyticsColumns = ((int)$columnCount >= 3);
		return self::$_hasScoreAnalyticsColumns;
	}

	public static function getPopupOrderBy()
	{
		$orderBy = [
			['field' => 'displayorder', 'dir' => 'ASC'],
		];

		if (self::hasScoreAnalyticsColumns()) {
			$orderBy[] = ['field' => 'reliability', 'dir' => 'DESC'];
		}

		if (self::hasViewcountColumn()) {
			$orderBy[] = ['field' => 'viewcount', 'dir' => 'DESC'];
		}

		$orderBy[] = ['field' => 'updated', 'dir' => 'DESC'];
		return $orderBy;
	}

	protected static function resolveReferenceDateTime($referenceDateTime = null)
	{
		if ($referenceDateTime instanceof \DateTimeInterface) {
			return new \DateTime($referenceDateTime->format('Y-m-d H:i:s'));
		}

		$referenceDateTime = trim((string)$referenceDateTime);
		if ($referenceDateTime !== '') {
			try {
				return new \DateTime($referenceDateTime);
			} catch (\Exception $exception) {
			}
		}

		return new \DateTime();
	}

	protected static function parseDateTimeValue($value)
	{
		if ($value instanceof \DateTimeInterface) {
			return new \DateTime($value->format('Y-m-d H:i:s'));
		}

		$value = trim((string)$value);
		if ($value === '' || $value === '0000-00-00 00:00:00') {
			return null;
		}

		try {
			return new \DateTime($value);
		} catch (\Exception $exception) {
			return null;
		}
	}

	protected static function clampUnitFloat($value)
	{
		$value = (float)$value;
		if ($value < 0) {
			return 0.0;
		}
		if ($value > 1) {
			return 1.0;
		}
		return $value;
	}

	public static function calculateDecayFactor($elapsedDays, $halfLifeDays = null)
	{
		$elapsedDays = max(0.0, (float)$elapsedDays);
		$halfLifeDays = $halfLifeDays === null ? self::getScoreHalfLifeDays() : (float)$halfLifeDays;
		if ($halfLifeDays <= 0) {
			$halfLifeDays = self::getScoreHalfLifeDays();
		}

		return pow(0.5, $elapsedDays / $halfLifeDays);
	}

	public static function calculateReliability($positiveScore, $negativeScore, $totalVotes)
	{
		$positiveScore = max(0.0, (float)$positiveScore);
		$negativeScore = abs((float)$negativeScore);
		$totalVotes = max(0, (int)$totalVotes);
		$effectiveVotes = $positiveScore + $negativeScore;

		if ($totalVotes <= 0 && $effectiveVotes <= 0.0) {
			return 0.0;
		}

		$approvalDenominator = $positiveScore + $negativeScore + 6.0;
		if ($approvalDenominator <= 0.0) {
			return 0.0;
		}

		$approval = ($positiveScore + 3.0) / $approvalDenominator;
		$signalWeight = $effectiveVotes > 0.0
			? $effectiveVotes / ($effectiveVotes + 10.0)
			: 0.0;
		$historyWeight = $totalVotes > 0
			? sqrt($totalVotes / ($totalVotes + 10.0))
			: 0.0;

		return self::clampUnitFloat($approval * $signalWeight * $historyWeight);
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

	public static function loadReliabilityRefreshBatch($limit = null)
	{
		if (!self::hasScoreAnalyticsColumns()) {
			return array();
		}

		$limit = $limit === null ? self::getFakeCronBatchSize() : (int)$limit;
		$limit = max(1, $limit);
		$rows = self::fetchAll(
			"SELECT `id`
			FROM `faq`
			ORDER BY
				CASE WHEN `reliability_updated_at` IS NULL THEN 0 ELSE 1 END ASC,
				`reliability_updated_at` ASC,
				CASE WHEN `score_decayed_at` IS NULL THEN 0 ELSE 1 END ASC,
				`score_decayed_at` ASC,
				`id` ASC
			LIMIT " . $limit
		);

		if (!is_array($rows) || count($rows) === 0) {
			return array();
		}

		$faqItems = array();
		foreach ($rows as $row) {
			$faqId = (int)($row['id'] ?? 0);
			if ($faqId <= 0) {
				continue;
			}

			$faq = new self();
			if ($faq->load($faqId) && (int)$faq->getId() > 0) {
				$faqItems[] = $faq;
			}
		}

		return $faqItems;
	}

	public static function runReliabilityRefreshBatch($limit = null, $referenceDateTime = null)
	{
		$processedCount = 0;
		foreach (self::loadReliabilityRefreshBatch($limit) as $faq) {
			if ($faq instanceof self && $faq->refreshReliability($referenceDateTime, true, true)) {
				$processedCount++;
			}
		}

		return $processedCount;
	}

	public static function canCreateContextualForHolon($holon, $userId = 0, $organizationId = 0, $useSessionCache = true)
	{
		if (!$holon instanceof \dbObject\Holon || (int)$holon->getId() <= 0) {
			return false;
		}

		$userId = (int)$userId;
		if ($userId <= 0) {
			return false;
		}

		return $holon->isAllowed('CAN_CREATE_FAQ', (bool)$useSessionCache, $userId);
	}

	public function incrementViewcount()
	{
		if (!self::hasViewcountColumn()) {
			return false;
		}

		$this->set("viewcount", (int)$this->get("viewcount") + 1);
		return $this->save();
	}

	public function applyScoreDecay($referenceDateTime = null, $persist = true)
	{
		if (!self::hasVoteColumns() || !self::hasScoreAnalyticsColumns()) {
			return false;
		}

		$reference = self::resolveReferenceDateTime($referenceDateTime);
		$lastDecayDate = self::parseDateTimeValue($this->get('score_decayed_at'));
		$positiveScore = max(0.0, (float)$this->get('positive_score'));
		$negativeScore = max(0.0, (float)$this->get('negative_score'));

		if ($lastDecayDate instanceof \DateTimeInterface) {
			$elapsedSeconds = max(0, $reference->getTimestamp() - $lastDecayDate->getTimestamp());
			$elapsedDays = $elapsedSeconds / 86400;
			if ($elapsedDays > 0) {
				$factor = self::calculateDecayFactor($elapsedDays);
				$positiveScore *= $factor;
				$negativeScore *= $factor;
			}
		}

		$this->set('positive_score', $positiveScore);
		$this->set('negative_score', $negativeScore);
		$this->set('score_decayed_at', $reference->format('Y-m-d H:i:s'));

		if (!$persist) {
			return true;
		}

		$saveResult = $this->save();
		return is_array($saveResult) && !empty($saveResult['status']);
	}

	public function refreshReliability($referenceDateTime = null, $applyDecay = true, $persist = true)
	{
		if (!self::hasVoteColumns() || !self::hasScoreAnalyticsColumns()) {
			return false;
		}

		$reference = self::resolveReferenceDateTime($referenceDateTime);
		if ($applyDecay && !$this->applyScoreDecay($reference, false)) {
			return false;
		}

		$this->set(
			'reliability',
			self::calculateReliability(
				(float)$this->get('positive_score'),
				(float)$this->get('negative_score'),
				(int)$this->get('total_votes')
			)
		);
		$this->set('reliability_updated_at', $reference->format('Y-m-d H:i:s'));
		if (!(self::parseDateTimeValue($this->get('score_decayed_at')) instanceof \DateTimeInterface)) {
			$this->set('score_decayed_at', $reference->format('Y-m-d H:i:s'));
		}

		if (!$persist) {
			return true;
		}

		$saveResult = $this->save();
		return is_array($saveResult) && !empty($saveResult['status']);
	}

	public function registerVote($vote, $referenceDateTime = null)
	{
		if (!self::hasVoteColumns()) {
			return false;
		}

		$faqId = (int)$this->getId();
		if ($faqId <= 0) {
			return false;
		}

		$vote = strtolower(trim((string)$vote));
		if ($vote !== 'up' && $vote !== 'down') {
			return false;
		}

		if (!self::hasScoreAnalyticsColumns()) {
			$positiveIncrement = $vote === 'up' ? 1 : 0;
			$negativeIncrement = $vote === 'down' ? 1 : 0;
			$result = self::execute(
				"UPDATE `faq`
				SET `positive_score` = `positive_score` + :positive_increment,
					`negative_score` = `negative_score` + :negative_increment,
					`total_votes` = `total_votes` + 1
				WHERE `id` = :id",
				array(
					'id' => $faqId,
					'positive_increment' => $positiveIncrement,
					'negative_increment' => $negativeIncrement,
				)
			);

			if (!$result) {
				return false;
			}

			return $this->load($faqId);
		}

		$reference = self::resolveReferenceDateTime($referenceDateTime);
		if (!$this->applyScoreDecay($reference, false)) {
			return false;
		}

		if ($vote === 'up') {
			$this->set('positive_score', (float)$this->get('positive_score') + 1.0);
		} else {
			$this->set('negative_score', (float)$this->get('negative_score') + 1.0);
		}

		$this->set('total_votes', (int)$this->get('total_votes') + 1);
		$this->set(
			'reliability',
			self::calculateReliability(
				(float)$this->get('positive_score'),
				(float)$this->get('negative_score'),
				(int)$this->get('total_votes')
			)
		);
		$this->set('reliability_updated_at', $reference->format('Y-m-d H:i:s'));
		$this->set('score_decayed_at', $reference->format('Y-m-d H:i:s'));

		$saveResult = $this->save();
		return is_array($saveResult) && !empty($saveResult['status']) ? $this : false;
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

	public static function buildEmbeddedVideoUrl($url)
	{
		$url = trim((string)$url);

		if ($url === '') {
			return '';
		}

		if (preg_match('#player\.vimeo\.com/video/(\d+)(?:[?&]h=([a-zA-Z0-9]+))?#i', $url, $matches)) {
			$videoId = trim((string)($matches[1] ?? ''));
			$hash = trim((string)($matches[2] ?? ''));

			if ($videoId === '') {
				return '';
			}

			return $hash !== ''
				? 'https://player.vimeo.com/video/' . $videoId . '?h=' . $hash
				: 'https://player.vimeo.com/video/' . $videoId;
		}

		if (preg_match('#videos/(\d+)/([a-zA-Z0-9]+)#i', $url, $matches)) {
			$videoId = trim((string)($matches[1] ?? ''));
			$hash = trim((string)($matches[2] ?? ''));

			if ($videoId === '' || $hash === '') {
				return '';
			}

			return 'https://player.vimeo.com/video/' . $videoId . '?h=' . $hash;
		}

		if (preg_match('#vimeo\.com/(?:video/)?(\d+)(?:$|[?/])#i', $url, $matches)) {
			$videoId = trim((string)($matches[1] ?? ''));
			return $videoId !== ''
				? 'https://player.vimeo.com/video/' . $videoId
				: '';
		}

		return '';
	}

	public function getEmbeddedVideoUrl()
	{
		return self::buildEmbeddedVideoUrl($this->get('video'));
	}

	public function getMediaDisplayData()
	{
		$imageUrl = trim((string)$this->get('image'));
		$videoUrl = trim((string)$this->get('video'));
		$embeddedVideoUrl = $this->getEmbeddedVideoUrl();

		return array(
			'hasImage' => $imageUrl !== '',
			'imageUrl' => $imageUrl,
			'hasVideo' => $videoUrl !== '',
			'videoUrl' => $videoUrl,
			'embeddedVideoUrl' => $embeddedVideoUrl,
			'hasMedia' => $imageUrl !== '' || $videoUrl !== '',
		);
	}
}

?>
