<?php
	namespace dbObject;

	class SearchJob extends DbObject
	{
		public static function tableName()
		{
			return 'search_job';
		}

		public static function rules()
		{
			return [
				[['id'], 'required'],
				[['id', 'IDorganization', 'currentholonid', 'viewerref', 'attempts'], 'integer'],
				[['query', 'scopesjson', 'viewercontextjson', 'resultjson', 'errormessage'], 'text'],
				[['jobtype', 'status', 'viewertype', 'requesttoken'], 'string'],
				[['datecreation', 'datemodification', 'datestarted', 'datefinished'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'jobtype' => 'Type',
				'status' => 'Statut',
				'query' => 'Recherche',
				'scopesjson' => 'Scopes',
				'viewercontextjson' => 'Contexte viewer',
				'resultjson' => 'Resultat JSON',
				'errormessage' => 'Erreur',
				'requesttoken' => 'Token public',
				'IDorganization' => 'Organisation',
				'currentholonid' => 'Holon courant',
				'viewertype' => 'Type viewer',
				'viewerref' => 'Reference viewer',
				'attempts' => 'Tentatives',
				'datecreation' => 'Creation',
				'datemodification' => 'Modification',
				'datestarted' => 'Debut',
				'datefinished' => 'Fin',
			];
		}

		public static function attributeLength()
		{
			return [
				'jobtype' => 40,
				'status' => 20,
				'viewertype' => 20,
				'requesttoken' => 80,
			];
		}

		public static function getOrder()
		{
			return "datecreation DESC, id DESC";
		}

		protected static function generateRequestToken($length = 48)
		{
			$length = max(24, (int)$length);
			$raw = rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
			return substr($raw, 0, 80);
		}

		public static function buildViewerContextFromGlobals($organizationId = 0, $currentHolonId = 0)
		{
			$organizationId = (int)$organizationId;
			$currentHolonId = (int)$currentHolonId;

			if (function_exists('commonGetCurrentShareLink')) {
				$shareLink = \commonGetCurrentShareLink();
				if ($shareLink instanceof \dbObject\HolonShareLink) {
					return array(
						'type' => 'share',
						'organizationId' => (int)$shareLink->get('IDorganization'),
						'currentHolonId' => $currentHolonId,
						'shareLinkId' => (int)$shareLink->getId(),
						'shareHolonId' => (int)$shareLink->get('IDholon'),
						'allowStructure' => $shareLink->allowsStructure(),
						'allowPeople' => $shareLink->allowsPeople(),
						'allowPeopleDetail' => $shareLink->allowsPeopleDetail(),
					);
				}
			}

			$userId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);

			if ($userId > 0) {
				return array(
					'type' => 'user',
					'organizationId' => $organizationId,
					'currentHolonId' => $currentHolonId,
					'userId' => $userId,
				);
			}

			return array(
				'type' => 'public',
				'organizationId' => $organizationId,
				'currentHolonId' => $currentHolonId,
			);
		}

		public static function createTopbarJob(\dbObject\Organization $organization, $query, array $scopes, array $viewerContext, array $options = array())
		{
			self::maybePruneOldJobs();

			$job = new self();
			$job->set('jobtype', 'topbar_search');
			$job->set('status', 'queued');
			$job->set('query', trim((string)$query));
			$job->set('scopesjson', json_encode(array_values($scopes), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			$job->set('viewercontextjson', json_encode($viewerContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			$job->set('resultjson', null);
			$job->set('errormessage', null);
			$job->set('requesttoken', self::generateRequestToken());
			$job->set('IDorganization', (int)$organization->getId());
			$job->set('currentholonid', isset($options['currentHolonId']) ? (int)$options['currentHolonId'] : 0);
			$job->set('viewertype', trim((string)($viewerContext['type'] ?? 'public')));
			$job->set('viewerref', self::extractViewerReference($viewerContext));
			$job->set('attempts', 0);
			$job->set('datecreation', new \DateTime());
			$job->set('datemodification', new \DateTime());
			$saveResult = $job->save();

			return !empty($saveResult['status']) ? $job : false;
		}

		protected static function maybePruneOldJobs()
		{
			try {
				if (random_int(1, 20) !== 1) {
					return;
				}
			} catch (\Throwable $exception) {
				return;
			}

			self::execute(
				"DELETE FROM search_job
				WHERE status IN ('completed', 'failed')
				  AND datecreation < (NOW() - INTERVAL 7 DAY)"
			);
		}

		protected static function extractViewerReference(array $viewerContext)
		{
			$type = trim((string)($viewerContext['type'] ?? ''));
			if ($type === 'user') {
				return (int)($viewerContext['userId'] ?? 0);
			}

			if ($type === 'share') {
				return (int)($viewerContext['shareLinkId'] ?? 0);
			}

			return (int)($viewerContext['organizationId'] ?? 0);
		}

		public static function findByIdAndToken($jobId, $requestToken)
		{
			$jobId = (int)$jobId;
			$requestToken = trim((string)$requestToken);
			if ($jobId <= 0 || $requestToken === '') {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM search_job
				WHERE id = :id
				  AND requesttoken = :requesttoken
				LIMIT 1",
				array(
					'id' => $jobId,
					'requesttoken' => $requestToken,
				)
			);

			if ($row === false) {
				return false;
			}

			$job = new self();
			$job->loadFromArray($row);
			$job->setId((int)$row['id']);
			return $job;
		}

		public function getScopes()
		{
			$decoded = json_decode((string)$this->get('scopesjson'), true);
			if (!is_array($decoded)) {
				return array();
			}

			$scopes = array();
			foreach ($decoded as $scope) {
				$scope = trim((string)$scope);
				if ($scope === '') {
					continue;
				}

				$scopes[$scope] = $scope;
			}

			return array_values($scopes);
		}

		public function getViewerContext()
		{
			$decoded = json_decode((string)$this->get('viewercontextjson'), true);
			return is_array($decoded) ? $decoded : array();
		}

		public function getResultPayload()
		{
			$decoded = json_decode((string)$this->get('resultjson'), true);
			return is_array($decoded) ? $decoded : array();
		}

		public function matchesViewerContext(array $viewerContext)
		{
			$jobType = trim((string)$this->get('viewertype'));
			$currentType = trim((string)($viewerContext['type'] ?? ''));

			if ($jobType === '' || $jobType !== $currentType) {
				return false;
			}

			if ((int)$this->get('IDorganization') !== (int)($viewerContext['organizationId'] ?? 0)) {
				return false;
			}

			if ($jobType === 'user') {
				return (int)$this->get('viewerref') === (int)($viewerContext['userId'] ?? 0);
			}

			if ($jobType === 'share') {
				return (int)$this->get('viewerref') === (int)($viewerContext['shareLinkId'] ?? 0);
			}

			return true;
		}

		public function markRunning()
		{
			$this->set('status', 'running');
			$this->set('attempts', (int)$this->get('attempts') + 1);
			if (!$this->get('datestarted')) {
				$this->set('datestarted', new \DateTime());
			}
			$this->set('datemodification', new \DateTime());
			return $this->save();
		}

		public function markCompleted(array $resultPayload)
		{
			$this->set('status', 'completed');
			$this->set('resultjson', json_encode($resultPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
			$this->set('errormessage', null);
			$this->set('datefinished', new \DateTime());
			$this->set('datemodification', new \DateTime());
			return $this->save();
		}

		public function markFailed($message)
		{
			$this->set('status', 'failed');
			$this->set('errormessage', trim((string)$message));
			$this->set('datefinished', new \DateTime());
			$this->set('datemodification', new \DateTime());
			return $this->save();
		}

		public function dispatchAsync()
		{
			$jobId = (int)$this->getId();
			if ($jobId <= 0) {
				return false;
			}

			$scriptPath = realpath(dirname(__DIR__, 2) . '/scripts/process-search-job.php');
			if (!$scriptPath || !is_file($scriptPath)) {
				return false;
			}

			$phpBinary = trim((string)getenv('OMO_PHP_CLI_BINARY'));
			if ($phpBinary === '') {
				$candidates = array();
				if (defined('PHP_BINDIR') && trim((string)PHP_BINDIR) !== '') {
					$candidates[] = rtrim((string)PHP_BINDIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php';
					$candidates[] = rtrim((string)PHP_BINDIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php.exe';
				}
				$candidates[] = trim((string)PHP_BINARY);

				foreach ($candidates as $candidate) {
					$candidate = trim((string)$candidate);
					if ($candidate !== '' && (@is_file($candidate) || strpos($candidate, DIRECTORY_SEPARATOR) === false)) {
						$phpBinary = $candidate;
						break;
					}
				}
			}
			if ($phpBinary === '') {
				$phpBinary = 'php';
			}

			$command = escapeshellarg($phpBinary)
				. ' '
				. escapeshellarg($scriptPath)
				. ' --job='
				. $jobId;

			if (DIRECTORY_SEPARATOR === '\\') {
				if (!function_exists('popen')) {
					return false;
				}

				$launcher = 'start /B "" ' . $command . ' > NUL 2>&1';
				$process = @popen($launcher, 'r');
				if (!is_resource($process)) {
					return false;
				}

				@pclose($process);
				return true;
			}

			if (!function_exists('exec')) {
				return false;
			}

			@exec($command . ' > /dev/null 2>&1 &');
			return true;
		}

		public static function processJobById($jobId)
		{
			$jobId = (int)$jobId;
			if ($jobId <= 0) {
				return false;
			}

			$job = new self();
			if (!$job->load($jobId)) {
				return false;
			}

			$status = trim((string)$job->get('status'));
			if ($status === 'completed') {
				return true;
			}
			if ($status === 'running') {
				return true;
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load((int)$job->get('IDorganization'))) {
				$job->markFailed('Organisation introuvable pour cette recherche.');
				return false;
			}

			$job->markRunning();

			try {
				$resultPayload = $organization->searchTopbarResults(
					(string)$job->get('query'),
					$job->getScopes(),
					array(
						'limit' => 36,
						'perScopeLimit' => 14,
						'viewerContext' => $job->getViewerContext(),
					)
				);

				$job->markCompleted(is_array($resultPayload) ? $resultPayload : array());
				return true;
			} catch (\Throwable $exception) {
				$job->markFailed($exception->getMessage());
				return false;
			}
		}
	}

?>
