<?php
	namespace dbObject;

	require_once dirname(__DIR__, 2) . '/common/avatar.php';

	class PdoResultCompat
	{
		private $_rows = array();
		private $_cursor = 0;
		public $num_rows = 0;

		public function __construct($rows = array())
		{
			$this->_rows = is_array($rows) ? array_values($rows) : array();
			$this->num_rows = count($this->_rows);
		}

		public function fetch_assoc()
		{
			if ($this->_cursor >= $this->num_rows) {
				return null;
			}
			return $this->_rows[$this->_cursor++];
		}

		public function data_seek($offset)
		{
			$offset = (int)$offset;
			if ($offset < 0 || $offset >= $this->num_rows) {
				return false;
			}

			$this->_cursor = $offset;
			return true;
		}
	}

	class PdoDbhCompat
	{
		private $_pdo;
		public $insert_id = 0;

		public function __construct($pdo)
		{
			$this->_pdo = $pdo;
		}

		public function getPdo()
		{
			return $this->_pdo;
		}

		// Ce wrapper garde l'ancienne API mysqli::query disponible le temps de la migration.
		public function query($query)
		{
			$profile = DbObject::beginSqlPerformanceProfile($query, array());
			try {
				$statement = $this->_pdo->query($query);
				if ($statement === false) {
					DbObject::finishSqlPerformanceProfile($profile, "pdo_query", false, 0);
					return false;
				}

				if ($statement->columnCount() > 0) {
					$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
					$statement->closeCursor();
					DbObject::finishSqlPerformanceProfile($profile, "pdo_query", true, count($rows));
					return new PdoResultCompat($rows);
				}

				$affectedRows = (int)$statement->rowCount();
				$statement->closeCursor();
				$this->insert_id = (int)$this->_pdo->lastInsertId();
				DbObject::finishSqlPerformanceProfile($profile, "pdo_query", true, $affectedRows);
				return true;
			} catch (\PDOException $e) {
				DbObject::finishSqlPerformanceProfile($profile, "pdo_query", false, 0, $e);
				DbObject::registerDbError($query, array(), $e);
				return false;
			}
		}

		public function prepare($query)
		{
			return $this->_pdo->prepare($query);
		}

		public function lastInsertId()
		{
			return $this->_pdo->lastInsertId();
		}
	}


	abstract class DbObject
	{
		protected $_fieldVisibilityCanViewCache = null;
		protected $_id; // Id de l'enregistrement
		protected $_loaded=false; // Id de l'enregistrement
		protected $_partiallyLoaded=false;
		protected $_loadedFieldNames=array();
		protected $_fields; // Espace de chargement de tous les champs
		protected $_parameters; // Espace de chargement des paramètres
		
		public static $_dbh;
		protected static $_lastDbError = null;
		protected static $_tableExistsCache = array();
		protected static $_sqlPerformanceConfig = null;
		protected static $_sqlPerformanceProfiles = array();
		protected static $_sqlPerformanceRequestId = null;
		protected static $_sqlPerformanceShutdownRegistered = false;
		protected static $_sqlPerformanceSummaryWritten = false;
		protected static $_sqlPerformanceWriteFailureReported = false;
		protected static $_sqlPerformanceStats = array(
			"queryCount" => 0,
			"loggedQueryCount" => 0,
			"errorCount" => 0,
			"totalDurationMs" => 0.0,
			"maxDurationMs" => 0.0,
		);
		static $myvariablearray = array();	// Liste de valeurs statiques créées à la demande
		static $preload = array();	// Liste des valeurs déjà chargées
		
		static public function getDbh() {
			if (isset(self::$_dbh) && !is_null(self::$_dbh)) {
				return self::$_dbh;
			} else {
				return self::refreshDbh();
			}
		}
		
		static public function refreshDbh() {
			// Connexion à la base de donnée
			try {
				$dsn = "mysql:host=".$GLOBALS["dbServer"].";dbname=".$GLOBALS["dbName"].";charset=utf8mb4";
				$pdo = new \PDO($dsn, $GLOBALS["dbUser"], $GLOBALS["dbPassword"], array(
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
					\PDO::ATTR_EMULATE_PREPARES => false,
				));
				self::$_dbh = new PdoDbhCompat($pdo);
			} catch (\PDOException $e) {
				self::rememberLastDbError("connect", array(
					"host" => $GLOBALS["dbServer"] ?? "",
					"database" => $GLOBALS["dbName"] ?? "",
					"user" => $GLOBALS["dbUser"] ?? "",
				), $e);
				echo $e->getMessage();
				self::$_dbh = null;
			}
			return self::$_dbh;
		}

		static public function getPdo() {
			$dbh = self::getDbh();
			return $dbh ? $dbh->getPdo() : null;
		}

		protected static function tableExists($tableName) {
			$tableName = trim((string)$tableName);
			if ($tableName === '') {
				return false;
			}

			$cacheKey = (string)($GLOBALS["dbName"] ?? "") . "|" . strtolower($tableName);
			if (array_key_exists($cacheKey, self::$_tableExistsCache)) {
				return self::$_tableExistsCache[$cacheKey];
			}

			$result = self::fetchValue(
				"SELECT 1
				FROM INFORMATION_SCHEMA.TABLES
				WHERE TABLE_SCHEMA = DATABASE()
				  AND TABLE_NAME = :table_name
				LIMIT 1",
				array(
					'table_name' => $tableName,
				)
			);

			self::$_tableExistsCache[$cacheKey] = ((int)$result === 1);
			return self::$_tableExistsCache[$cacheKey];
		}

		protected static function clearLastDbError() {
			self::$_lastDbError = null;
		}

		protected static function getDbErrorLogPath() {
			$baseDir = isset($_SERVER["DOCUMENT_ROOT"]) && is_string($_SERVER["DOCUMENT_ROOT"]) && trim($_SERVER["DOCUMENT_ROOT"]) !== ""
				? rtrim($_SERVER["DOCUMENT_ROOT"], "/\\")
				: dirname(dirname(__DIR__));

			return $baseDir . DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR . "dbobject-sql-errors.log";
		}

		protected static function writeDbErrorLog(array $payload) {
			$logPath = self::getDbErrorLogPath();
			$logDir = dirname($logPath);
			if (!is_dir($logDir)) {
				@mkdir($logDir, 0777, true);
			}

			$line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if (!is_string($line) || $line === "") {
				$line = '{"time":"' . date("c") . '","message":"dbobject_log_json_encode_failed"}';
			}

			error_log($line . PHP_EOL, 3, $logPath);
		}

		protected static function readSqlPerformanceEnvironmentValue($key, $default = null) {
			if (function_exists('envValue')) {
				return \envValue((string)$key, $default);
			}

			$value = getenv((string)$key);
			if ($value !== false) {
				return $value;
			}

			if (isset($_ENV[$key])) {
				return $_ENV[$key];
			}
			if (isset($_SERVER[$key])) {
				return $_SERVER[$key];
			}

			return $default;
		}

		protected static function parseSqlPerformanceBoolean($value) {
			if (is_bool($value)) {
				return $value;
			}

			return in_array(strtolower(trim((string)$value)), array("1", "true", "yes", "on"), true);
		}

		protected static function isAbsoluteSqlPerformancePath($path) {
			$path = (string)$path;
			return $path !== "" && (
				$path[0] === "/"
				|| $path[0] === "\\"
				|| preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
			);
		}

		protected static function getSqlPerformanceConfig() {
			if (is_array(self::$_sqlPerformanceConfig)) {
				return self::$_sqlPerformanceConfig;
			}

			$enabled = self::parseSqlPerformanceBoolean(
				self::readSqlPerformanceEnvironmentValue("DB_QUERY_LOG_ENABLED", false)
			);
			$minimumDurationMs = (float)self::readSqlPerformanceEnvironmentValue("DB_QUERY_LOG_MIN_MS", 50);
			if ($minimumDurationMs < 0) {
				$minimumDurationMs = 0.0;
			}

			$logPath = trim((string)self::readSqlPerformanceEnvironmentValue("DB_QUERY_LOG_PATH", ""));
			if ($logPath !== "" && !self::isAbsoluteSqlPerformancePath($logPath)) {
				$logPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $logPath);
			}

			self::$_sqlPerformanceConfig = array(
				"enabled" => $enabled,
				"minimumDurationMs" => $minimumDurationMs,
				"logPath" => $logPath,
			);

			return self::$_sqlPerformanceConfig;
		}

		protected static function getSqlPerformanceRequestId() {
			if (is_string(self::$_sqlPerformanceRequestId) && self::$_sqlPerformanceRequestId !== "") {
				return self::$_sqlPerformanceRequestId;
			}

			$requestId = trim((string)($_SERVER["HTTP_X_REQUEST_ID"] ?? ""));
			if ($requestId === "") {
				try {
					$requestId = bin2hex(random_bytes(8));
				} catch (\Throwable $e) {
					$requestId = uniqid("sql-", true);
				}
			}

			self::$_sqlPerformanceRequestId = substr($requestId, 0, 128);
			return self::$_sqlPerformanceRequestId;
		}

		protected static function sanitizeSqlForPerformanceLog($query) {
			$sql = preg_replace('/\/\*.*?\*\//s', ' ', (string)$query);
			$sql = preg_replace("/'(?:''|\\\\.|[^'\\\\])*'/s", '?', (string)$sql);
			$sql = preg_replace('/"(?:""|\\\\.|[^"\\\\])*"/s', '?', (string)$sql);
			$sql = preg_replace('/--[^\r\n]*/', ' ', (string)$sql);
			$sql = preg_replace('/#[^\r\n]*/', ' ', (string)$sql);
			$sql = preg_replace('/\b0x[0-9a-f]+\b/i', '?', (string)$sql);
			$sql = preg_replace('/(?<![A-Za-z0-9_:])[-+]?\d+(?:\.\d+)?(?![A-Za-z0-9_])/', '?', (string)$sql);
			$sql = preg_replace('/\s+/', ' ', trim((string)$sql));

			return substr((string)$sql, 0, 12000);
		}

		protected static function getSqlPerformanceFingerprint($sql) {
			$canonicalSql = strtolower((string)$sql);
			$canonicalSql = preg_replace('/:[A-Za-z_][A-Za-z0-9_]*/', '?', $canonicalSql);
			return hash('sha256', (string)$canonicalSql);
		}

		protected static function getSqlPerformanceQueryType($sql) {
			if (preg_match('/^\s*([A-Za-z]+)/', (string)$sql, $matches) !== 1) {
				return "unknown";
			}

			return strtolower($matches[1]);
		}

		protected static function getSqlPerformanceParameterMetadata($params) {
			$metadata = array();
			foreach ((array)$params as $key => $value) {
				$item = array(
					"name" => is_int($key) ? (string)($key + 1) : ltrim((string)$key, ":"),
					"type" => get_debug_type($value),
				);

				if (is_string($value)) {
					$item["length"] = strlen($value);
				} elseif (is_array($value)) {
					$item["count"] = count($value);
				}

				$metadata[] = $item;
			}

			return $metadata;
		}

		protected static function getSqlPerformanceCaller() {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12);
			$currentFile = realpath(__FILE__);
			foreach ($trace as $frame) {
				$file = isset($frame["file"]) ? realpath((string)$frame["file"]) : false;
				if ($file === false || $file === $currentFile) {
					continue;
				}

				$projectRoot = realpath(dirname(__DIR__, 2));
				if ($projectRoot !== false && str_starts_with($file, $projectRoot . DIRECTORY_SEPARATOR)) {
					$file = substr($file, strlen($projectRoot) + 1);
				}

				return array(
					"file" => str_replace("\\", "/", $file),
					"line" => (int)($frame["line"] ?? 0),
				);
			}

			return null;
		}

		protected static function getSqlPerformanceRequestPath() {
			$requestUri = (string)($_SERVER["REQUEST_URI"] ?? "");
			$requestPath = parse_url($requestUri, PHP_URL_PATH);
			return is_string($requestPath) ? $requestPath : "";
		}

		protected static function writeSqlPerformanceLog(array $payload) {
			$config = self::getSqlPerformanceConfig();
			$logPath = $config["logPath"];
			if ($logPath === "") {
				$logPath = dirname(__DIR__, 2)
					. DIRECTORY_SEPARATOR . "tmp"
					. DIRECTORY_SEPARATOR . "sql-performance"
					. DIRECTORY_SEPARATOR . "sql-performance-" . date("Y-m-d") . ".jsonl";
			}
			$logDir = dirname($logPath);
			if (!is_dir($logDir) && !@mkdir($logDir, 0777, true) && !is_dir($logDir)) {
				if (!self::$_sqlPerformanceWriteFailureReported) {
					self::$_sqlPerformanceWriteFailureReported = true;
					error_log("Unable to create SQL performance log directory: " . $logDir);
				}
				return;
			}

			$line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if (!is_string($line) || $line === "") {
				return;
			}

			if (@file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false && !self::$_sqlPerformanceWriteFailureReported) {
				self::$_sqlPerformanceWriteFailureReported = true;
				error_log("Unable to write SQL performance log: " . $logPath);
			}
		}

		public static function beginSqlPerformanceProfile($query, $params = array()) {
			$config = self::getSqlPerformanceConfig();
			if (!$config["enabled"]) {
				return null;
			}

			if (!self::$_sqlPerformanceShutdownRegistered) {
				self::$_sqlPerformanceShutdownRegistered = true;
				register_shutdown_function(array(self::class, "writeSqlPerformanceRequestSummary"));
			}

			$sql = self::sanitizeSqlForPerformanceLog($query);
			return array(
				"startedAtNs" => hrtime(true),
				"sql" => $sql,
				"fingerprint" => self::getSqlPerformanceFingerprint($sql),
				"queryType" => self::getSqlPerformanceQueryType($sql),
				"parameters" => self::getSqlPerformanceParameterMetadata($params),
				"caller" => self::getSqlPerformanceCaller(),
			);
		}

		public static function finishSqlPerformanceProfile($profile, $operation, $success, $rowCount = null, $exception = null) {
			if (!is_array($profile) || !isset($profile["startedAtNs"])) {
				return;
			}

			$durationMs = max(0.0, (hrtime(true) - (int)$profile["startedAtNs"]) / 1000000);
			self::$_sqlPerformanceStats["queryCount"]++;
			self::$_sqlPerformanceStats["totalDurationMs"] += $durationMs;
			self::$_sqlPerformanceStats["maxDurationMs"] = max(self::$_sqlPerformanceStats["maxDurationMs"], $durationMs);
			if (!$success) {
				self::$_sqlPerformanceStats["errorCount"]++;
			}

			$config = self::getSqlPerformanceConfig();
			if ($success && $durationMs < $config["minimumDurationMs"]) {
				return;
			}

			self::$_sqlPerformanceStats["loggedQueryCount"]++;
			$payload = array(
				"event" => "query",
				"time" => date("c"),
				"request_id" => self::getSqlPerformanceRequestId(),
				"duration_ms" => round($durationMs, 3),
				"operation" => (string)$operation,
				"query_type" => (string)$profile["queryType"],
				"fingerprint" => (string)$profile["fingerprint"],
				"sql" => (string)$profile["sql"],
				"parameter_count" => count($profile["parameters"]),
				"parameters" => $profile["parameters"],
				"success" => (bool)$success,
				"row_count" => is_null($rowCount) ? null : (int)$rowCount,
				"caller" => $profile["caller"],
				"request_path" => self::getSqlPerformanceRequestPath(),
				"method" => (string)($_SERVER["REQUEST_METHOD"] ?? ""),
				"script" => (string)($_SERVER["SCRIPT_NAME"] ?? ""),
				"database" => (string)($GLOBALS["dbName"] ?? ""),
				"current_user" => (int)($_SESSION["currentUser"] ?? 0),
				"current_organization" => (int)($_SESSION["currentOrganization"] ?? 0),
			);

			if ($exception instanceof \Throwable) {
				$payload["error_class"] = get_class($exception);
				$payload["error_code"] = (string)$exception->getCode();
			}

			self::writeSqlPerformanceLog($payload);
		}

		public static function writeSqlPerformanceRequestSummary() {
			$config = self::getSqlPerformanceConfig();
			if (!$config["enabled"] || self::$_sqlPerformanceSummaryWritten) {
				return;
			}

			self::$_sqlPerformanceSummaryWritten = true;
			$requestStartedAt = isset($_SERVER["REQUEST_TIME_FLOAT"])
				? (float)$_SERVER["REQUEST_TIME_FLOAT"]
				: microtime(true);
			self::writeSqlPerformanceLog(array(
				"event" => "request_summary",
				"time" => date("c"),
				"request_id" => self::getSqlPerformanceRequestId(),
				"request_duration_ms" => round(max(0.0, (microtime(true) - $requestStartedAt) * 1000), 3),
				"query_count" => (int)self::$_sqlPerformanceStats["queryCount"],
				"logged_query_count" => (int)self::$_sqlPerformanceStats["loggedQueryCount"],
				"error_count" => (int)self::$_sqlPerformanceStats["errorCount"],
				"database_duration_ms" => round((float)self::$_sqlPerformanceStats["totalDurationMs"], 3),
				"maximum_query_duration_ms" => round((float)self::$_sqlPerformanceStats["maxDurationMs"], 3),
				"peak_memory_bytes" => memory_get_peak_usage(true),
				"request_path" => self::getSqlPerformanceRequestPath(),
				"method" => (string)($_SERVER["REQUEST_METHOD"] ?? ""),
				"script" => (string)($_SERVER["SCRIPT_NAME"] ?? ""),
				"database" => (string)($GLOBALS["dbName"] ?? ""),
				"current_user" => (int)($_SESSION["currentUser"] ?? 0),
				"current_organization" => (int)($_SESSION["currentOrganization"] ?? 0),
			));
		}

		protected static function rememberLastDbError($query, $params = array(), $exception = null) {
			self::$_lastDbError = array(
				"query" => (string)$query,
				"params" => is_array($params) ? $params : array(),
				"message" => $exception instanceof \Throwable ? (string)$exception->getMessage() : "",
				"code" => $exception instanceof \Throwable ? (int)$exception->getCode() : 0,
				"time" => date("c"),
			);

			self::writeDbErrorLog(array(
				"time" => self::$_lastDbError["time"],
				"query" => self::$_lastDbError["query"],
				"params" => self::$_lastDbError["params"],
				"message" => self::$_lastDbError["message"],
				"code" => self::$_lastDbError["code"],
				"request_uri" => (string)($_SERVER["REQUEST_URI"] ?? ""),
				"method" => (string)($_SERVER["REQUEST_METHOD"] ?? ""),
				"remote_addr" => (string)($_SERVER["REMOTE_ADDR"] ?? ""),
				"script" => (string)($_SERVER["SCRIPT_NAME"] ?? ""),
				"database" => (string)($GLOBALS["dbName"] ?? ""),
				"current_user" => (int)($_SESSION["currentUser"] ?? 0),
				"current_organization" => (int)($_SESSION["currentOrganization"] ?? 0),
			));
		}

		static public function getLastDbError() {
			return is_array(self::$_lastDbError) ? self::$_lastDbError : null;
		}

		static public function registerDbError($query, $params = array(), $exception = null) {
			self::rememberLastDbError($query, $params, $exception);
		}

		static public function executeSQL($query) {
			return self::getDbh()->query($query);
		}

		protected static function normalizeSqlValue($value) {
			if ($value === "") {
				return null;
			}
			if ($value instanceof \DateTimeInterface) {
				return $value->format('Y-m-d H:i:s');
			}
			if (is_object($value) && get_class($value) == "stdClass") {
				if ((!isset($value->lat) || is_null($value->lat)) && (!isset($value->long) || is_null($value->long))) {
					return null;
				}
				return $value->lat.";".$value->long;
			}
			if (is_bool($value)) {
				return (int)$value;
			}
			return $value;
		}

		protected static function prepareAndExecute($query, $params = array()) {
			self::clearLastDbError();
			$pdo = self::getPdo();
			if (!$pdo) {
				if (self::getLastDbError() === null) {
					self::rememberLastDbError($query, $params);
				}
				return false;
			}
			$profile = self::beginSqlPerformanceProfile($query, $params);

			try {
				$statement = $pdo->prepare($query);
				if (!($statement instanceof \PDOStatement)) {
					self::finishSqlPerformanceProfile($profile, "prepared_statement", false, 0);
					self::rememberLastDbError($query, $params);
					return false;
				}
				foreach ($params as $key => $value) {
					$paramName = is_int($key) ? $key + 1 : (substr($key, 0, 1) === ":" ? $key : ":".$key);
					$normalizedValue = self::normalizeSqlValue($value);
					$paramType = \PDO::PARAM_STR;

					if (is_null($normalizedValue)) {
						$paramType = \PDO::PARAM_NULL;
					} elseif (is_int($normalizedValue)) {
						$paramType = \PDO::PARAM_INT;
					} elseif (is_bool($normalizedValue)) {
						$paramType = \PDO::PARAM_BOOL;
					}

					$statement->bindValue($paramName, $normalizedValue, $paramType);
				}

				if (!$statement->execute()) {
					self::finishSqlPerformanceProfile($profile, "prepared_statement", false, 0);
					self::rememberLastDbError($query, $params);
					return false;
				}
				if (is_array($profile)) {
					self::$_sqlPerformanceProfiles[spl_object_id($statement)] = $profile;
				}
				return $statement;
			} catch (\PDOException $e) {
				self::finishSqlPerformanceProfile($profile, "prepared_statement", false, 0, $e);
				self::rememberLastDbError($query, $params, $e);
				return false;
			}
		}

		protected static function finishSqlPerformanceStatement($statement, $operation, $success, $rowCount = null, $exception = null) {
			if (!self::getSqlPerformanceConfig()["enabled"]) {
				return;
			}

			$statementId = spl_object_id($statement);
			$profile = self::$_sqlPerformanceProfiles[$statementId] ?? null;
			unset(self::$_sqlPerformanceProfiles[$statementId]);
			self::finishSqlPerformanceProfile($profile, $operation, $success, $rowCount, $exception);
		}

		static public function execute($query, $params = array()) {
			$statement = self::prepareAndExecute($query, $params);
			if ($statement === false) {
				return false;
			}

			try {
				$affectedRows = (int)$statement->rowCount();
				$statement->closeCursor();
				self::getDbh()->insert_id = (int)self::getPdo()->lastInsertId();
				self::finishSqlPerformanceStatement($statement, "execute", true, $affectedRows);
				return true;
			} catch (\Throwable $e) {
				self::finishSqlPerformanceStatement($statement, "execute", false, 0, $e);
				throw $e;
			}
		}

		static public function fetchRow($query, $params = array()) {
			$statement = self::prepareAndExecute($query, $params);
			if ($statement === false) {
				return false;
			}

			try {
				$row = $statement->fetch(\PDO::FETCH_ASSOC);
				$statement->closeCursor();
				self::finishSqlPerformanceStatement($statement, "fetch_row", true, $row === false ? 0 : 1);
				return $row === false ? false : $row;
			} catch (\Throwable $e) {
				self::finishSqlPerformanceStatement($statement, "fetch_row", false, 0, $e);
				throw $e;
			}
		}

		static public function fetchAll($query, $params = array()) {
			$statement = self::prepareAndExecute($query, $params);
			if ($statement === false) {
				return false;
			}

			try {
				$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
				$statement->closeCursor();
				self::finishSqlPerformanceStatement($statement, "fetch_all", true, count($rows));
				return $rows;
			} catch (\Throwable $e) {
				self::finishSqlPerformanceStatement($statement, "fetch_all", false, 0, $e);
				throw $e;
			}
		}

		static public function fetchValue($query, $params = array()) {
			$statement = self::prepareAndExecute($query, $params);
			if ($statement === false) {
				return false;
			}

			try {
				$value = $statement->fetchColumn();
				$statement->closeCursor();
				self::finishSqlPerformanceStatement($statement, "fetch_value", true, $value === false ? 0 : 1);
				return $value;
			} catch (\Throwable $e) {
				self::finishSqlPerformanceStatement($statement, "fetch_value", false, 0, $e);
				throw $e;
			}
		}
		
		// Fonction pour créer du contenu statique à la demande
		public static function __callstatic($name, $arguments){
			if (isset(self::$myvariablearray[$name]))
				return self::$myvariablearray[$name];
			else
				return null;
		}
		
		public static function createStatic($variable, $value){
			self::$myvariablearray[$variable] = $value;
		}
    
		// Constructeur
		public function __construct()
		{
			//$this->_dbh=self::getDbh();
		}
		
		// Fonctions à redéfinir dans les instances
		// *****************************************
		
		abstract public static function rules();
		abstract public static function attributeLabels();
		public static function attributeDescriptions() {
			return [];
		}
		
		public static function attributeLength() 
		{
			return [];
		}
		
		public static function attributePlaceholder() 
		{
			return [];
		}

		public static function attributePattern() 
		{
			return [];
		}

		public static function attributeValues() 
		{
			return [];
		}

		public static function attributeHtmlEditorProfiles()
		{
			return [];
		}

		public static function publicReadableFields()
		{
			return [];
		}

	    public static function tableName() {
			return strtolower(substr(static::class,strrpos(static::class,"\\")+1));
		}

		
		public static function getOrder() {
			return "id DESC";
		}
		
		// Fonctions de base pour l'accès à la base de données
		// *****************************************
		
		protected function needsFullLoadForField($field) {
			if ($this->getId()<=0) {
				return false;
			}

			if (!$this->_loaded) {
				return true;
			}

			return $this->_partiallyLoaded && !isset($this->_loadedFieldNames[(string)$field]);
		}

		protected function needsFullLoad() {
			return $this->getId()>0 && (!$this->_loaded || $this->_partiallyLoaded);
		}

		public function isFieldLoaded($field) {
			$field = (string)$field;
			return $this->getId()>0
				&& $this->_loaded
				&& (!$this->_partiallyLoaded || isset($this->_loadedFieldNames[$field]));
		}

		public function hydrateFromDatabaseRow(array $row, $isComplete = false) {
			if (!array_key_exists('id', $row) || (int)$row['id']<=0) {
				return false;
			}

			$this->_id = null;
			$this->_fields = array();
			$this->_parameters = null;
			$this->_fieldVisibilityCanViewCache = null;
			$this->_loaded = true;
			$this->_partiallyLoaded = false;
			$this->_loadedFieldNames = array_fill_keys(array_map('strval', array_keys($row)), true);
			$this->loadFromArray($row);
			$this->_id = (int)$row['id'];
			$this->_partiallyLoaded = !$isComplete;

			if ($isComplete) {
				self::$preload[$this->tableName()."_".$this->_id] = $this;
			}

			return true;
		}

		function get($field) {
			if ($this->needsFullLoadForField($field)) $this->load($this->getId());

			if (!$this->canReadField($field)) {
				return "";
			}

			if (is_array($this->_fields) && array_key_exists($field,$this->_fields)) {
				return $this->_fields[$field];
			} else {
				// Contrôle s'il pourrait s'agir d'un ID
				if (substr($field,0,2)!="ID" && $this->get("ID".$field)!="") {
					
					// Est-ce un multi
					if (strpos($field,"_")>0) 
						$objectname="\\dbObject\\".substr($field,0,strpos($field,"_"));
					else
						$objectname="\\dbObject\\".$field;
					$object=new $objectname;
					$object->load($this->get("ID".$field));
					return $object;
	
				} else 
				  return "";
				//Die ("Ce champ n'existe pas : [".$field."]");
			}
		}
		
		public function canReadField($field)
		{
			$publicFields = static::publicReadableFields();
			if (!is_array($publicFields) || count($publicFields) === 0) {
				return true;
			}

			if ($this->_fieldVisibilityCanViewCache === 'checking') {
				return true;
			}

			if ($this->_fieldVisibilityCanViewCache === null) {
				$this->_fieldVisibilityCanViewCache = 'checking';
				$this->_fieldVisibilityCanViewCache = $this->canViewDetail() ? true : false;
			}

			if ($this->_fieldVisibilityCanViewCache) {
				return true;
			}

			$field = trim((string)$field);
			if ($field === '' || $field === 'id') {
				return true;
			}

			foreach ($publicFields as $publicField) {
				if ($field === trim((string)$publicField)) {
					return true;
				}
			}

			return false;
		}

		function clear($field) {
			if ($this->needsFullLoad()) $this->load($this->getId());
			unset($this->_fields[$field]);
		}

		protected function saveSizedImageResource($source, $sourceMime, $field, $targetDirectory, $targetWidth, $targetHeight) {
			if (!is_resource($source) && !($source instanceof \GdImage)) {
				return false;
			}

			$sourceWidth = imagesx($source);
			$sourceHeight = imagesy($source);
			$destination = $source;
			if ((int)$targetWidth > 0 && (int)$targetHeight > 0) {
				$destination = imagecreatetruecolor((int)$targetWidth, (int)$targetHeight);
				if ($destination === false) {
					imagedestroy($source);
					return false;
				}

				if (in_array((string)$sourceMime, array('image/png', 'image/webp', 'image/avif'), true)) {
					imagealphablending($destination, false);
					imagesavealpha($destination, true);
					$transparent = imagecolorallocatealpha($destination, 0, 0, 0, 127);
					imagefilledrectangle($destination, 0, 0, (int)$targetWidth, (int)$targetHeight, $transparent);
				}

				imagecopyresampled(
					$destination,
					$source,
					0,
					0,
					0,
					0,
					(int)$targetWidth,
					(int)$targetHeight,
					$sourceWidth,
					$sourceHeight
				);
			}

			$fileNameBase = time() . '_' . uniqid();
			$fullDirectory = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . $targetDirectory;
			$stored = false;
			$imagePath = '';

			if (function_exists('imagewebp')) {
				$imagePath = $targetDirectory . '/' . $fileNameBase . '.webp';
				$stored = imagewebp($destination, rtrim($fullDirectory, '/\\') . DIRECTORY_SEPARATOR . $fileNameBase . '.webp', 82);
			}

			if (!$stored && $sourceMime === 'image/jpeg') {
				$imagePath = $targetDirectory . '/' . $fileNameBase . '.jpg';
				imageinterlace($destination, true);
				$stored = imagejpeg($destination, rtrim($fullDirectory, '/\\') . DIRECTORY_SEPARATOR . $fileNameBase . '.jpg', 82);
			} elseif (!$stored) {
				$imagePath = $targetDirectory . '/' . $fileNameBase . '.png';
				$stored = imagepng($destination, rtrim($fullDirectory, '/\\') . DIRECTORY_SEPARATOR . $fileNameBase . '.png', 9, PNG_ALL_FILTERS);
			}

			if ($destination !== $source) {
				imagedestroy($destination);
			}
			imagedestroy($source);

			if (!$stored) {
				return false;
			}

			$oldImagePath = isset($this->_fields[$field]) ? trim((string)$this->_fields[$field]) : '';
			$documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
			if ($oldImagePath !== '' && str_starts_with($oldImagePath, '/img/upload/') && is_file($documentRoot . $oldImagePath)) {
				@unlink($documentRoot . $oldImagePath);
			}

			$this->_fields[$field] = $imagePath;
			return true;
		}
		
		function set($field, $value) {
			if ($field=="id") {
				$this->_id=$value; // Spécifique pour réinitialiser des noeuds
				$this->_fieldVisibilityCanViewCache = null;
			}
			if ($this->needsFullLoad()) $this->load($this->getId());

			if (is_null($value) || (is_string($value) && trim($value)==""))
				$this->_fields[$field]=null;
			else {
			
				// Lit toutes les lignes concernant cette valeur
				$param = array_filter($this->rules(), function($ar) use ($field) {
				   return (array_search($field, $ar[0])!==false);
				});

		
					
				// Entreprend des actions de formatage si nécessaire
				if (false !== array_search("integer", array_column($param, 1))) {
					$this->_fields[$field] = (int)$value;
					
				} else
				// Entreprend des actions de formatage si nécessaire
				if (false !== array_search("float", array_column($param, 1))) {
					$this->_fields[$field] = (float)str_replace(",",".",$value);
					
				} else
				// Entreprend des actions de formatage si nécessaire
				if (false !== array_search("latlong", array_column($param, 1))) {
					// Si c'est déjà un tableau, l'enregistre

					  $this->_fields[$field] = new \stdClass();

					  if (is_object($value)) {
						// Si $coordinate est un objet stdClass, on suppose qu'il contient déjà les propriétés de latitude et de longitude
						$this->_fields[$field]->lat = isset($value->lat) && !is_null($value->lat) ? (float) $value->lat : null;
						$this->_fields[$field]->long = isset($value->long) && !is_null($value->long) ? (float) $value->long : null;
					  } elseif (is_array($value)) {
						// Si $coordinate est un tableau, on suppose qu'il contient les valeurs de latitude et de longitude
						$this->_fields[$field]->lat = isset($value[0]) && trim($value[0])!=="" ? (float) $value[0] : null;
						$this->_fields[$field]->long = isset($value[1])&& trim($value[1])!=="" ? (float) $value[1] : null;
					  } elseif (is_string($value)) {
						// Si $coordinate est une chaîne de caractères, on traite la chaîne pour extraire les valeurs de latitude et de longitude
						$coordinates = preg_split('/[,;\/]/', $value); // Ou explode(';', $coordinate);

						$this->_fields[$field]->lat = isset($coordinates[0]) && trim($coordinates[0])!=="" ? (float) $coordinates[0] : null;
						$this->_fields[$field]->long = isset($coordinates[1]) && trim($coordinates[1])!=="" ? (float) $coordinates[1] : null;
					  } else {
						// Gérer les autres cas si nécessaire
					  }
				} else
				
				
				if (false !== array_search("datetime", array_column($param, 1))) {
					if (is_object($value))
						$this->_fields[$field]=$value;
					else
					if (is_numeric($value)) {
						$this->_fields[$field] = new \DateTime(date("Y-m-d H:i:s",$value));
					} else {
						if (trim($value)!="")
							$this->_fields[$field] = new \DateTime($value);
					}
				} else
				
				if (false !== array_search("date", array_column($param, 1))) {
					if (is_object($value))
						$this->_fields[$field]=$value;
					else					if (is_numeric($value)) {
						$this->_fields[$field] = new \DateTime(date("Y-m-d",$value));
					} else {
						if (trim($value)!="")
							$this->_fields[$field] = new \DateTime($value);
					}
				} else
				
				if (false !== array_search("time", array_column($param, 1))) {
					if (is_object($value))
						$this->_fields[$field]=$value;
					else
					if (is_numeric($value)) {
						$this->_fields[$field] = new \DateTime(date("H:i:s",$value));
					} else {
						if (trim($value)!="")
							$this->_fields[$field] = new \DateTime($value);
					}
				} else

				if (false !== array_search("daterange", array_column($param, 1)) || strpos($field,"_fin")>0) {
					if (is_object($value))
						$this->_fields[$field]=$value;
					else
					if (is_numeric($value)) {
						$this->_fields[$field] = new \DateTime(date("Y-m-d H:i:s",$value));
					} else {
						if (trim($value)!="")
							$this->_fields[$field] = new \DateTime($value);
					}
				} else

				if (false !== array_search("parameters", array_column($param, 1))) {
					if (is_array($value) || is_object($value)) {
						$this->_fields[$field] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
					} else {
						$this->_fields[$field] = $value;
					}
					$this->_parameters = null;
				} else
					
				if (false !== array_search("sizedimage", array_column($param, 1))) {
					$target_dir="/img/upload/".$this->tableName();
					if (!file_exists($_SERVER["DOCUMENT_ROOT"].$target_dir."/")) {
						mkdir($_SERVER["DOCUMENT_ROOT"].$target_dir."/", 0777, true);
					}
					// Si c'est une image, ne peut se contenter d'une string: regarde si un fichier est envoyé
					if ($value==="newimage" && isset($_FILES[$field]) && ($_FILES[$field]["error"] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_OK) {
						$tmpName = $_FILES[$field]["tmp_name"];
						$mime = function_exists("mime_content_type") ? mime_content_type($tmpName) : ($_FILES[$field]["type"] ?? "");
						$sizes = $this::attributeLength();
						$sizeConfig = $sizes[$field] ?? null;
						$targetWidth = null;
						$targetHeight = null;

						if (is_array($sizeConfig)) {
							if (isset($sizeConfig[0]) && is_array($sizeConfig[0])) {
								$targetWidth = $sizeConfig[0][0] ?? null;
								$targetHeight = $sizeConfig[0][1] ?? null;
							} else {
								$targetWidth = $sizeConfig[0] ?? null;
								$targetHeight = $sizeConfig[1] ?? null;
							}
						}

						switch ($mime) {
							case "image/jpeg":
								$src = imagecreatefromjpeg($tmpName);
								break;
							case "image/png":
								$src = imagecreatefrompng($tmpName);
								break;
							case "image/webp":
								$src = imagecreatefromwebp($tmpName);
								break;
							case "image/avif":
								$src = function_exists('imagecreatefromavif') ? imagecreatefromavif($tmpName) : false;
								break;
							default:
								$src = false;
								break;
						}

						if ($src !== false) {
							$this->saveSizedImageResource($src, $mime, $field, $target_dir, $targetWidth, $targetHeight);
						}
					} else
					if (isset($_POST["imageDataInput_".$field]) && $_POST["imageDataInput_".$field]!="") {
						$target_dir="/img/upload/".$this->tableName();
						if (!file_exists($_SERVER["DOCUMENT_ROOT"].$target_dir."/")) {
							mkdir($_SERVER["DOCUMENT_ROOT"].$target_dir."/", 0777);
						}
						// Convertir les données en format binaire
						$imageBinaryData = base64_decode(str_replace('data:image/png;base64,', '', $_POST["imageDataInput_".$field]), true);
						$src = is_string($imageBinaryData) ? @imagecreatefromstring($imageBinaryData) : false;
						if ($src !== false) {
							$sizes = $this::attributeLength();
							$sizeConfig = $sizes[$field] ?? null;
							$targetWidth = isset($sizeConfig[0]) && is_array($sizeConfig[0]) ? ($sizeConfig[0][0] ?? null) : ($sizeConfig[0] ?? null);
							$targetHeight = isset($sizeConfig[0]) && is_array($sizeConfig[0]) ? ($sizeConfig[0][1] ?? null) : ($sizeConfig[1] ?? null);
							$this->saveSizedImageResource($src, 'image/png', $field, $target_dir, $targetWidth, $targetHeight);
						}
						unset($_POST["imageDataInput_".$field]);
					} else {
						if (is_string($value) && $value!="[object File]" && $value!="newimage")
							$this->_fields[$field]=$value;	
					} // On ne fait rien, ça n'a pas été modifié
				} else
				
				if (false !== array_search("image", array_column($param, 1))) {
					// Si c'est une image, ne peut se contenter d'une string: regarde si un fichier est envoyé
					if (isset($_FILES[$field."_file"]) && $_FILES[$field."_file"]["tmp_name"]!="" && strpos($_FILES[$field."_file"]["name"],".php")==false) {					
						$target_dir="/img/upload/".$this->tableName();
						// Est-ce que le dossier existe? Si non, le crée
						if (!file_exists($_SERVER["DOCUMENT_ROOT"].$target_dir."/")) {
							mkdir($_SERVER["DOCUMENT_ROOT"].$target_dir."/", 0777);
						}
						
						// Rend le nom URL compatible
						$name=urlencode(str_replace(" ","",$_FILES[$field."_file"]["name"]));
						$storedFileName = time()."_".$name;
						move_uploaded_file($_FILES[$field."_file"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"].$target_dir."/".$storedFileName);
						$this->_fields[$field]=$target_dir."/".$storedFileName;
						unset($_FILES[$field."_file"]); // Ca a été traité, plus besoin de le garder
					} else {  
						$this->_fields[$field]=$value;
					} // Sinon laisse tel quel




				} else
				
				if (false !== array_search("fk", array_column($param, 1))) {
					$this->_fields[$field]=$value;
					// Est-ce un numéric?
					if (!is_numeric($value)) {
						// Cherche s'il existe une valeur correspondante
						$values=$this->getValues($field);
						$maxsim=2;
						$mostsim=0;
						foreach ($values as $val) {
							if (strtoupper($val->get("nom"))==strtoupper($value)) {
								$this->_fields[$field] = $val->getId();
								break;
							} else {
								$sim=similar_text(strtoupper($val->get("nom")),strtoupper($value), $perc);
								if ($sim>$maxsim) {
									$maxsim=$sim;
									$mostsim=$val->getId();
								}
							}
							// Défini le plus ressemblant
							if ($mostsim>0) {
								$this->_fields[$field] = $mostsim;
							}
						}
					} 
				} else
					$this->_fields[$field]=$value;
			}
		}
		
		// Retourne si le champ est requis ou pas
		function isRequired($field) {
			// Lit toutes les lignes concernant cette valeur
			$param = array_filter($this->rules(), function($ar) use ($field) {
			   return (array_search($field, $ar[0])!==false);
			});

			// Requis trouvé?
			if (false !== array_search("required", array_column($param, 1))) {
				return true;
			} else {
				// Si l'élément est trouvé dedans
				return false;
			}
		}
		
		// Retourne si le champ est unique ou pas
		function isUnique($field) {
			// Lit toutes les lignes concernant cette valeur
			$param = array_filter($this->rules(), function($ar) use ($field) {
			   return (array_search($field, $ar[0])!==false);
			});

			// Requis trouvé?
			if (false !== array_search("unique", array_column($param, 1))) {
				return true;
			} else {
				// Si l'élément est trouvé dedans
				return false;
			}
		}
		
		// Retourne si le champ est modifiable ou pas
		function isProtected($field) {
			// Lit toutes les lignes concernant cette valeur
			$param = array_filter($this->rules(), function($ar) use ($field) {
			   return (array_search($field, $ar[0])!==false);
			});


			// Requis trouvé?
			if (false !== array_search("safe", array_column($param, 1))) {
				return true;
			} else {
				// Si l'élément est trouvé dedans
				return false;
			}
		
		}
		
		// Remplace tous les champs entre crochet en la valeur correspondante
		function txtReplace($string) {
			$patterns = array();
			$replacements = array();
			
			// Change l'ID
			$patterns[] = '/\[ID'.$this->tableName().'\]/';
			$replacements[] = $this->getID();
				
			// Parcours tous les éléments
			foreach ($this->attributeLabels() as $key => $val) {
				$patterns[] = '/\['.$key.'\]/';
				
				$replacements[] = $this->getString($key);
			}
			return preg_replace($patterns, $replacements, $string);
		}

		static function getFieldType ($key) {

			// Recherche les lignes associées au type
			$params = array_filter(static::rules(), function($ar) use ($key) {
				return (array_search($key, $ar[0])!==false);
			});
			foreach ($params as $param) {
				switch($param[1]) {
					case "image" : 
					case "sizedimage" : 
					case "password" : 
					case "date" : 
					case "color" : 
					case "timezone" : 
					case "datetime" : 
					case "time" : 
					case "daterange" : 
					case "integer" : 
					case "float" : 
					case "latlong" : 
					case "fk" : 
					case "text" : 
					case "parameters" : 
					case "mail" : 
					case "html" : 
					case "boolean" : 
					case "string" : 
						return $param[1];
				}
			}
			return "";

		}

		function checkField($key, $value) {
			if ($this->needsFullLoad()) $this->load($this->getId());

			if (is_string($value) && trim($value)=="" && $this->isRequired($key)) return "The field [".$this->attributeLabels()[$key]."] can't be empty.";
			
			// Contrôle la pattern
			if (!is_null($value) && $value!="" && $this->attributePattern()[$key]) {
				if (!preg_match($this->attributePattern()[$key][0], $value)) {
					return "The field [".$this->attributeLabels()[$key]."] does not have the correct format. (".$this->attributePattern()[$key][1].").";
				}
			}
			
			switch ($this->getFieldType($key)) {
				case "latlong" :
					// Si c'est un objet
					if (is_object($value)) {
						if (isset($value->long) && $value->long!="" && filter_var($value->long, FILTER_VALIDATE_FLOAT)===false || isset($value->lat) && $value->lat!="" && filter_var($value->lat, FILTER_VALIDATE_FLOAT)===false) {
							return "Invalid format for field [".$this->attributeLabels()[$key]."]";
						}											
					} else if (is_array($value)) {
						// Si c'est un tableau
						if (isset($value[0]) && $value[0]!=="" && filter_var($value[0], FILTER_VALIDATE_FLOAT)===false || isset($value[1]) && $value[1]!=="" && filter_var($value[1], FILTER_VALIDATE_FLOAT)===false) {
							return "Invalid format for field [".$this->attributeLabels()[$key]."] ";
						}											
					} else {
						// Si c'est une chaîne de caractère, la coupe en deux
						$values = preg_split('/[,;\/-]/', $value); // Ou explode(';', $coordinate);
						if (isset($values[0]) && $values[0]!=="" && filter_var($values[0], FILTER_VALIDATE_FLOAT)===false || isset($values[1]) && $values[1]!=="" && filter_var($values[1], FILTER_VALIDATE_FLOAT)===false) {
							return "Invalid format for field [".$this->attributeLabels()[$key]."]";
						}																	
					}
				break;
				case "mail" : 
					// Contrôle le format du mail
					if ($value!="" && filter_var($value, FILTER_VALIDATE_EMAIL)===false) {
					  return "Invalid email format for field [".$this->attributeLabels()[$key]."]";
					}	
				break;				
				case "integer" : 
					// Contrôle le format du mail
					if ($value!="" && filter_var($value, FILTER_VALIDATE_INT)===false) {
					  return "Invalid numeric format for field [".$this->attributeLabels()[$key]."] (integer)";
					}					
				break;				
				case "float" : 
					// Contrôle le format du mail
					if ($value!="" && filter_var($value, FILTER_VALIDATE_FLOAT)===false) {
					  return "Invalid numeric format for field [".$this->attributeLabels()[$key]."] (float)";
					}					
			}
			

		}
		
		// Retourne la valeur d'un paramètre si celui-ci existe, null sinon
		// Le charge à partir d'un fichier JSON enregistré dans un champ texte
		function getParameter($field) {
			// Est-ce que le tableau de paramètre a déjà été chargé
			if (isset($this->_parameters) && $this->_parameters!=null) {
				// Si oui, retourne simplement la valeur, null si le paramètre n'existe pas
				return (isset($this->_parameters[$field])?$this->_parameters[$field]:null);
			} else {
				// Sinon cherche le champ de type paramètre
				$parameterlist=array_filter(static::rules(), function ($rule) {
					// Vérifie si la deuxième valeur du tableau est 'boolean'
					return isset($rule[1]) && $rule[1] === 'parameters';
				});
				//Si pas trouvé, retourne null
				if (is_null($parameterlist) || count($parameterlist)==0 || is_null($this->get(current($parameterlist)[0][0]))) return null;
				// Sinon, le converti en tableau (pour l'instant, un seul parsamètre autorisé)
				$this->_parameters=json_decode($this->get(current($parameterlist)[0][0]),true); // Sous forme de tableau
				
				// Et en retourne la valeur
				return (isset($this->_parameters[$field])?$this->_parameters[$field]:null);
			}
		}

		// Retourne le champ sous forme de chaîne de caractère
		// Particulièrement intéressant pour afficher la valeur texte d'un ID
		function getString($field, $format=NULL)  {
			if ($this->needsFullLoadForField($field)) $this->load($this->getId());
			
			$type=$this->getFieldType($field);
			switch ($type) {
				case "fk" :
					if ($this->get($field)=="") return "";
					// Retourne la valeur texte de ce champ
					if (strpos($field,"_")>0) {
						$tmpname=substr($field,2);
						$tmpname=substr($tmpname,0,strpos($tmpname,"_"));
					} else
						$tmpname=substr($field,2);
					$fieldname="_".$tmpname;
					$objectname="\\dbObject\\".$tmpname;
					$tablename="\\dbObject\\array".$tmpname;
					

					// Charge toutes les valeurs
					if ($objectname::$fieldname()) {
						// Cherche la bonne ligne
						$obj=$objectname::$fieldname()->get($this->get($field));
						if (!is_null($obj))
							return $objectname::$fieldname()->get($this->get($field))->getLabel();
						else
							return "Object not found";
					} else {
						$objectname::createStatic($fieldname, new $tablename());
						$objectname::$fieldname()->load();
						

						return $objectname::$fieldname()->get($this->get($field))->getLabel();
					}
					break;
				case "time" :
					if ($this->get($field)!="")
						return $this->get($field)->format('H:i'); //date_format(date_create($object->get($key)), 'd.m.Y H:i:s');
					break;
				case "timezone" :
					echo "<select name='".$key."' id='".$key."'>";

					$timezones = timezone_identifiers_list();
					foreach ($timezones as $timezone) {
						echo '<option value="' . $timezone . '" '.($object->get($key)==$timezone?"selected":"").'>' . $timezone . '</option>';
					}

					echo "</select>";
					break;
				case "datetime" :
					if ($this->get($field)!="")
						return $this->get($field)->format('d.m.Y H:i'); //date_format(date_create($object->get($key)), 'd.m.Y H:i:s');
					break;
				case "daterange" :
					if ($this->get($field)!="")
						if (isset($format) && ($format=="Date")) {
							$txt= "".$this->get($field)->format('d.m.Y');
							if ($this->get($field."_fin")!="")
								if ($this->get($field)->format('d.m.Y')!=$this->get($field."_fin")->format('d.m.Y'))
									$txt="from ".$txt." to ".$this->get($field."_fin")->format('d.m.Y'); //date_format(date_create($object->get($key)), 'd.m.Y H:i:s');

						} else {
						$txt= "".$this->get($field)->format('d.m.Y')." à ".$this->get($field)->format('H:i');
						if ($this->get($field."_fin")!="")
							if ($this->get($field)->format('d.m.Y')==$this->get($field."_fin")->format('d.m.Y'))
								$txt="le ".str_replace("à","de",$txt)." à ".$this->get($field."_fin")->format('H:i'); //date_format(date_create($object->get($key)), 'd.m.Y H:i:s');
							else
								$txt="du ".$txt." au ".$this->get($field."_fin")->format('d.m.Y')." à ".$this->get($field."_fin")->format('H:i'); //date_format(date_create($object->get($key)), 'd.m.Y H:i:s');
						}
						return $txt;
					break;
				case "date" :
					if ($this->get($field)!="")
						return $this->get($field)->format('d.m.Y'); //date_format(date_create($object->get($key)), 'd.m.Y H:i:s');
					break;
				case "latlong" :
					return $this->get($field)->lat." - ".$this->get($field)->long;
					break;
				case "integer" :
					return $this->get($field);
					break;
				case "float" :
					return $this->get($field);
					break;
				case "image" :
					return "<div style='width:400px; height:200px; background:url(".$this->get($field).") #DDDDDD; background-size:cover; background-position:center center'></div>";
					break;
				case "sizedimage" :
					return "<div style='width:".(isset($object::attributeLength()[$field])?$object::attributeLength()[$field][0]:"200")."px; height:".(isset($object::attributeLength()[$field])?$object::attributeLength()[$field][1]:"200")."px; background:url(".$this->get($field).") #DDDDDD; background-size:cover; background-position:center center'></div>";
					break;
				case "password" :
					if ($this->get($field)!="" && strlen($this->get($field))>5) {
						return substr($this->get($field),0,1).str_repeat("*", strlen($this->get($field))-2).substr($this->get($field),-1);
					} else return "*****";
					break;
				case "string" :
					return $this->get($field);
					break;
				case "text" :
					$str="<div style='max-height:200px; overflow:auto;'>".str_replace("\n","<br>",$this->get($field))."</div>";
					return $str;
					break;
				case "html" :
					$str="<div style='max-height:200px; overflow:auto;'>".$this->get($field)."</div>";
					return $str;
					break;
				case "undefined" :
					return "indéfini";
					break;
				default:
					return $this->get($field);
			}
				
		}	
			
		
		// Retourne la liste des valeurs possibles pour un champ, sous forme d'un tableau d'objets
		static function getValues($field) {
	
			// Si c'est une clé étrangère
			if (substr($field,0,2)=="ID") {
				
					if (strpos($field,"_")>0) {
						$tmpname=substr($field,2);
						$tmpname=substr($tmpname,0,strpos($tmpname,"_"));
					} else
						$tmpname=substr($field,2);				
				
				$fieldname="_".$tmpname;
				$objectname="\\dbObject\\".$tmpname;
				$tablename="\\dbObject\\array".$tmpname;
				

				// Charge toutes les valeurs
				if ($objectname::$fieldname()) {
					return $objectname::$fieldname();
				} else {
					$objectname::createStatic($fieldname, new $tablename());
					$objectname::$fieldname()->load();				
					return $objectname::$fieldname();
				}
			}

		}		
		
		private function value2string($value) {
			if ($value=="") {
				return "NULL";
			}
			if (is_numeric($value)) {
				return $value;
			}
			if (is_object($value)) {
				switch (get_class($value)) {
					case "DateTimeImmutable" :
					case "DateTime" :
						return "'".$value->format('Y-m-d H:i:s')."'";
					break;		
					// Pour l'instant, seul les coordonnées utilisent ce type de class			
					case "stdClass" :
						if (is_null($value->lat) && is_null($value->long))
							return "NULL";
						else
							return "'".$value->lat.";".$value->long."'";
					break;
					default :
						return "'".get_class($value).":".str_replace("'","\'",strval($value))."'";
				}
			}
			return "'".str_replace("'","\'",$value)."'";
		}

		private static function isValidSqlIdentifier($identifier) {
			return is_string($identifier) && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier);
		}

		private static function quoteIdentifier($identifier) {
			return "`".$identifier."`";
		}

		private function getAllowedDbFields() {
			$fields = array("id");

			foreach (static::rules() as $rule) {
				if (!isset($rule[0]) || !is_array($rule[0])) {
					continue;
				}

				foreach ($rule[0] as $field) {
					if (!in_array($field, $fields, true)) {
						$fields[] = $field;
					}

					if ($this->getFieldType($field)=="daterange" && !in_array($field."_fin", $fields, true)) {
						$fields[] = $field."_fin";
					}
				}
			}

			return $fields;
		}

		private function isAllowedLookupField($field) {
			return self::isValidSqlIdentifier($field) && in_array($field, $this->getAllowedDbFields(), true);
		}

		private function getQuotedTableName($suffix = "") {
			$tableName = $this->tableName().$suffix;
			if (!self::isValidSqlIdentifier($tableName)) {
				return false;
			}

			return self::quoteIdentifier($tableName);
		}

		private function getPersistableFields() {
			$fields = array();

			foreach ($this->_fields as $key => $value) {
				if (!self::isValidSqlIdentifier($key)) {
					return false;
				}
				$fields[$key] = $value;
			}

			return $fields;
		}

		private function buildInsertParts($fields, $prefix = "field_") {
			$columns = array();
			$placeholders = array();
			$params = array();
			$index = 0;

			foreach ($fields as $key => $value) {
				$columns[] = self::quoteIdentifier($key);
				$paramName = $prefix.$index++;
				$placeholders[] = ":".$paramName;
				$params[$paramName] = $value;
			}

			return array($columns, $placeholders, $params);
		}

		private function buildUpdateParts($fields, $prefix = "field_") {
			$assignments = array();
			$params = array();
			$index = 0;

			foreach ($fields as $key => $value) {
				$paramName = $prefix.$index++;
				$assignments[] = self::quoteIdentifier($key)." = :".$paramName;
				$params[$paramName] = $value;
			}

			return array($assignments, $params);
		}

		// Fonction générique pour récupérer un objet sauvegardé
		function loadBackup($id) {
			$tableName = $this->getQuotedTableName("_historique");
			if ($tableName === false) {
				return array ("status"=>false, "text"=>"Error", "query"=>"invalid table name");
			}

			$query="select * from ".$tableName." where `id` = :id";
			$rows=self::fetchAll($query, array("id" => (int)$id));
			if ($rows === false) {
				Die ("Query error : ".$query);
			}
			if (count($rows)<1) {
				return array ("status"=>false, "text"=>"No record found", "id"=>"0".$this->_id);
			}

			$row=$rows[count($rows)-1];
			$this->loadFromArray($row);
			$this->_id=$id;
			return array ("status"=>true, "text"=>"The record has been recovered", "id"=>"0".$this->_id);
		}

		function backup() {
			if ($this->needsFullLoad()) $this->load($this->getId());

			$tableName = $this->getQuotedTableName("_historique");
			$fields = $this->getPersistableFields();
			if ($tableName === false || $fields === false || count($fields)===0) {
				return array ("status"=>false, "text"=>"Error", "query"=>"invalid archive payload");
			}

			list($columns, $placeholders, $params) = $this->buildInsertParts($fields, "backup_");
			$query="insert into ".$tableName." (".implode(", ", $columns).") VALUES (".implode(", ", $placeholders).")";
			$result=self::execute($query, $params);
			
			if ($result) {
				return array ("status"=>true, "text"=>"Record archived", "id"=>"0".$this->_id);
			} else {
				return array ("status"=>false, "text"=>"Error", "query"=>$query);
			}
		}

		function delete() {
			$tableName = $this->getQuotedTableName();
			if ($tableName === false) {
				return false;
			}

			return self::execute("delete from ".$tableName." where `id` = :id", array("id" => (int)$this->getId()));
		}
			
		function save() {
			if ($this->getId()>0 && !$this->_loaded) return;
			if ($this->_partiallyLoaded) $this->load($this->getId());

			$tableName = $this->getQuotedTableName();
			$fields = $this->getPersistableFields();
			if ($tableName === false || $fields === false) {
				return array ("status"=>false, "text"=>"Error saving record.", "query"=>"invalid table or field name");
			}
			
			foreach ($fields as $key=>$value) {
				if ($this->isUnique($key)) {
					if (!$this->isAllowedLookupField($key)) {
						return array ("status"=>false, "text"=>"Error saving record.", "query"=>"invalid unique field");
					}

					$query="select `id` from ".$tableName." where ".self::quoteIdentifier($key)." = :unique_value".(!is_null($this->_id)?" and `id` != :current_id":"")." limit 1";
					$params = array("unique_value" => $value);
					if (!is_null($this->_id)) {
						$params["current_id"] = (int)$this->_id;
					}

					$result=self::fetchRow($query, $params);
					if ($result !== false) {
						return array ("status"=>false, "text"=>"The field [".$this->attributeLabels()[$key]."] should be unique. This value is already in use.", "query"=>$query);
					}
				}
			}
							
			if ($this->_id>0) {
				if (count($fields)===0) {
					return array ("status"=>true, "text"=>"Saved!", "id"=>"0".$this->_id);
				}

				list($assignments, $params) = $this->buildUpdateParts($fields, "update_");
				$params["id"] = (int)$this->_id;
				$query="update ".$tableName." set ".implode(", ", $assignments)." where `id` = :id";
				$result=self::execute($query, $params);
			} else {
				if (count($fields)===0) {
					return array ("status"=>false, "text"=>"Error saving record.", "query"=>"empty insert");
				}

				list($columns, $placeholders, $params) = $this->buildInsertParts($fields, "insert_");
				$query="insert into ".$tableName." (".implode(", ", $columns).") VALUES (".implode(", ", $placeholders).")";
				$result=self::execute($query, $params);
				$this->_id=self::getDbh()->insert_id;
				$this->set("id",self::getDbh()->insert_id);
			}
			if ($result) {
				// Keep subsequent loads in this request consistent with the value just saved.
				// This is especially important when a second synchronization reads a
				// holon property immediately after its first write.
				self::$preload[$this->tableName()."_".$this->_id] = $this;
				return array ("status"=>true, "text"=>"Saved!", "id"=>"0".$this->_id);
			} else {
				return array ("status"=>false, "text"=>"Error saving record.", "query"=>$query);
			}
		}
		
		function checkFromArray($array) {
			$message="";
			// Contrôle que le tableau contient quelque chose
			if (count($array)==0) {
				$message.=($message!=""?"\n":"")."No datas send.";
			}
			
			// Contrôle que les champs requis par l'objet soient renseignés
			
			// Contrôle que les champs fournis dans les données aient le bon format
			foreach ($array as $key => $value) {
				$error=$this->checkField($key, $value);
				if ($error !="")
					$message.=($message!=""?"\n":"").$error;			
			}
			return $message;
		}
		
		function isFilled($fields) {
			foreach ($fields as $field) {
				if ($this->get($field)=="") return false;
			}
			return true;
		}
		
		function diffFromArray($array,$fields=NULL) {
			// Compare toutes les valeurs avec l'existant
			foreach ($array as $key => $value) {
				
				// Lit toutes les lignes concernant cette valeur, pour savoir si elle existe
				$param = array_filter($this->rules(), function($ar) use ($key) {
				   return (array_search($key, $ar[0])!==false);
				});
				
				// Si trouvée, mets à jour
				if (count($param)>0 && (is_null($fields) || (is_string($fields) && $fields==$key) || (is_array($fields) && in_array($key,$fields)))) {
					// Arrête la comparaison à la première différence
					if ($this->get($key)!=$value) {
						//echo "<p>".$key." : ".$this->get($key)." (".$this->getString($key).") = ".$value."</p>";
						return true;
					}
				}
				
			}
			// Aucune différence trouvée
			return false;
		}
		
		function loadFromArray($array) {
			if ($this->needsFullLoad()) $this->load($this->getId());

			// Met à jour toutes les valeurs présentes dans le tableau
			foreach ($array as $key => $value) {
				
				// Lit toutes les lignes concernant cette valeur
				$param = array_filter($this->rules(), function($ar) use ($key) {
				   return (array_search($key, $ar[0])!==false);
				});
				
				// Si trouvée, mets à jour
				if (count($param)>0) {
					// Est-ce une date? Si oui, quel type de post ? DB ou interface?
					if ($this->getFieldType($key)=="daterange") {
						
						// Y a-t-il un ensemble de champ? Si oui, crée deux dates (post)
						if (array_key_exists($key."_h1",$array)) {
							$this->set($key,$array[$key]." ".$array[$key."_h1"]);
							$this->set($key."_fin",$array[$key."_d2"]." ".$array[$key."_h2"]);
						} else
						// Si non, y a-t-il une deuxième date (db)
						if (array_key_exists($key."_fin", $array)) {
							$this->set($key,$value);
							$this->set($key."_fin",$array[$key."_fin"]);
						} else {
							$this->set($key,$value);
						}
						
					} else  
					if ($this->getFieldType($key)=="datetime") {
						// Est-ce qu'il y a un deuxième champ? Si oui, combine les deux champs pour créer une seule date
						if (isset($array[$key."_h1"])) {
							$this->set($key,$array[$key]." ".$array[$key."_h1"]);
						} else {
							$this->set($key,$value);
						}
						
					} else { 
						$this->set($key,$value);
					}
					
				}
				
			}
		}
		
		// Charge un objet à partir de son id ou d'un tableau de paramètres
		// Exemple d'utilisation:
		// $obj=new \dbObject\monObjet();
		// $obj->load(5); // ID
		// $obj->load(['key','yopla'])  // Autre paramètre
		function load($id, $forced=false) {
			$this->_loaded=true; 
			$this->_fieldVisibilityCanViewCache = null;
			$tableName = $this->getQuotedTableName();
			if ($tableName === false) {
				return false;
			}

			$query = "";
			$params = array();
			if (is_numeric($id)) {
				if (isset(self::$preload[$this->tableName()."_".$id]) && !$forced) {
					$preloadData = self::$preload[$this->tableName()."_".$id];
					$reflection = new \ReflectionObject($this);
					$properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);

					foreach ($properties as $property) {
						$propertyName = $property->getName();
						if (property_exists($preloadData, $propertyName)) {
							@$this->$propertyName = $preloadData->$propertyName;
						}
					}

					return true;
				}
				
				$query="select * from ".$tableName." where `id` = :id";
				$params["id"] = (int)$id;
			}
			else if (is_array($id)) {
				$where = array();

				if (isset($id[0]) && is_array($id[0])) {
					foreach ($id as $index => $critere) {
						if (!isset($critere[0], $critere[1]) || !$this->isAllowedLookupField($critere[0])) {
							return false;
						}

						$paramName = "crit_".$index;
						$where[] = self::quoteIdentifier($critere[0])." = :".$paramName;
						$params[$paramName] = $critere[1];
					}
				} else {
					if (!isset($id[0], $id[1]) || !$this->isAllowedLookupField($id[0])) {
						return false;
					}

					$where[] = self::quoteIdentifier($id[0])." = :crit_0";
					$params["crit_0"] = $id[1];
				}

				if (count($where)===0) {
					return false;
				}

				$query="select * from ".$tableName." where ".implode(" and ", $where);
			}

			$rows=self::fetchAll($query, $params);
			if ($rows === false) {
				Die ("Query error : ".$query." (".$this->tableName().";".$id.")");
			}
			if (count($rows)<1) return false;
			if (count($rows)>1) return false;

			return $this->hydrateFromDatabaseRow($rows[0], true);
			
			/*
			 * Collé ici pour évolution futur: création de table automatique
			 * CREATE TABLE IF NOT EXISTS `translation` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `uid` varchar(200) NOT NULL,
			  `value` text NOT NULL,
			  `original` text DEFAULT NULL,
			  `date` datetime NOT NULL DEFAULT current_timestamp(),
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			*/
		}
		
		// Fonctions GET 
		// *****************************************
		
		// Retourne la liste des vidéos affichées sur cette page
		function getId() {
			return $this->_id;
		}
		
		// Retourne une chaîne de caractère représentative de l'objet
		function getLabel() {
			// Récupère le premier élément de la liste "string" de la fonction rules()
			return $this->get($this->rules()[array_search("string", array_column($this->rules(), 1))][0][0]);
		}

		// Fonctions SET 
		// *****************************************
		
		// Retourne la liste des actualités affichées sur cette page
		function setId($numeric) {
			$this->_id=$numeric;
			$this->_fieldVisibilityCanViewCache = null;
		}
		
		// Fonctions d'affichage
		// *****************************************
		
		function display($template, $params=[]) {
			include ($_SERVER['DOCUMENT_ROOT']."/views/".$template);
		}
		
		public function canEdit() 
		{
			// Edition limitée aux personnes connectées, auteur de l'enregistrement
			//return (isset($_SESSION["currentUser"]) && $this->get("IDuser")>0 && $this->get("IDuser")==$_SESSION["currentUser"]);
			return false;
		}

		public function canDelete()
		{
			return $this->canEdit();
		}
		
		public function canView() 
		{
			// Affichage limité aux personnes connectées, auteur de l'enregistrement
			//return (isset($_SESSION["currentUser"]) && $this->get("IDuser")>0 && $this->get("IDuser")==$_SESSION["currentUser"]);
			return true;
		}
		
		public function canViewDetail()
		{
			return $this->canView();
		}

		protected function getToken($length)
		{
			$token = "";
			$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$codeAlphabet.= "0123456789";
			$codeAlphabet.= "";
			$max = strlen($codeAlphabet); // edited

			for ($i=0; $i < $length; $i++) {
				$token .= $codeAlphabet[rand(0, $max-1)];
			}

			return $token;
		}
		
	}
	
?>
