<?php
namespace dbObject;

class PatreonOauthTransaction extends DbObject
{
	protected static $storageAvailable = null;

	public static function tableName()
	{
		return 'patreon_oauth_transaction';
	}

	public static function rules()
	{
		return [
			[['id', 'IDuser'], 'integer'],
			[['handoff_token_hash', 'oauth_state_hash', 'claim_token', 'return_origin', 'status'], 'string'],
			[['expires_at', 'created_at', 'completed_at'], 'datetime'],
			[['id'], 'safe'],
		];
	}

	public static function attributeLabels()
	{
		return [
			'id' => 'ID',
			'IDuser' => 'Utilisateur',
			'handoff_token_hash' => 'Empreinte du jeton de relais',
			'oauth_state_hash' => 'Empreinte de l’état OAuth',
			'claim_token' => 'Jeton de prise en charge',
			'return_origin' => 'Origine de retour',
			'status' => 'Statut',
			'expires_at' => 'Expire le',
			'created_at' => 'Créé le',
			'completed_at' => 'Terminé le',
		];
	}

	public static function attributeLength()
	{
		return [
			'handoff_token_hash' => 64,
			'oauth_state_hash' => 64,
			'claim_token' => 64,
			'return_origin' => 255,
			'status' => 20,
		];
	}

	public static function isStorageAvailable($refresh = false)
	{
		if (!$refresh && self::$storageAvailable !== null) {
			return self::$storageAvailable;
		}

		$databaseName = trim((string)($GLOBALS['dbName'] ?? ''));
		if ($databaseName === '') {
			self::$storageAvailable = false;
			return false;
		}

		$result = self::fetchValue(
			"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :database_name AND table_name = :table_name",
			['database_name' => $databaseName, 'table_name' => self::tableName()]
		);
		self::$storageAvailable = (int)$result > 0;
		return self::$storageAvailable;
	}

	public static function createPending(int $userId, string $handoffTokenHash, string $returnOrigin)
	{
		self::cleanupExpired();
		$item = new self();
		$item->set('IDuser', $userId);
		$item->set('handoff_token_hash', $handoffTokenHash);
		$item->set('return_origin', $returnOrigin);
		$item->set('status', 'pending');
		$item->set('expires_at', new \DateTime('+10 minutes'));
		$item->set('created_at', new \DateTime());
		$result = $item->save();
		return !empty($result['status']) ? $item : false;
	}

	public static function claimByHandoffHash(string $tokenHash)
	{
		if (!self::isStorageAvailable()) {
			return false;
		}

		$claimToken = bin2hex(random_bytes(32));
		$updated = self::execute(
			"UPDATE `patreon_oauth_transaction` SET `status` = 'starting', `claim_token` = :claim_token WHERE `handoff_token_hash` = :token_hash AND `status` = 'pending' AND `expires_at` > NOW()",
			['claim_token' => $claimToken, 'token_hash' => $tokenHash]
		);
		if (!$updated) {
			return false;
		}

		$row = self::fetchRow(
			"SELECT `id`, `claim_token` FROM `patreon_oauth_transaction` WHERE `handoff_token_hash` = :token_hash LIMIT 1",
			['token_hash' => $tokenHash]
		);
		if ($row === false || !hash_equals($claimToken, (string)($row['claim_token'] ?? ''))) {
			return false;
		}

		$item = new self();
		return $item->load((int)$row['id']) ? $item : false;
	}

	public static function findPendingByStateHash(string $stateHash)
	{
		return self::findActiveByHash('oauth_state_hash', $stateHash);
	}

	private static function findActiveByHash(string $field, string $hash)
	{
		if (!in_array($field, ['handoff_token_hash', 'oauth_state_hash'], true) || !self::isStorageAvailable()) {
			return false;
		}

		$row = self::fetchRow(
			"SELECT `id` FROM `patreon_oauth_transaction` WHERE `" . $field . "` = :token_hash AND `status` = 'pending' AND `expires_at` > NOW() LIMIT 1",
			['token_hash' => $hash]
		);
		if ($row === false) {
			return false;
		}

		$item = new self();
		return $item->load((int)$row['id']) ? $item : false;
	}

	public function prepareAuthorization(string $stateHash)
	{
		$this->set('oauth_state_hash', $stateHash);
		$this->set('handoff_token_hash', null);
		$this->set('claim_token', null);
		$this->set('status', 'pending');
		return $this->save();
	}

	public static function cleanupExpired(): void
	{
		if (self::isStorageAvailable()) {
			self::execute("DELETE FROM `patreon_oauth_transaction` WHERE `expires_at` < DATE_SUB(NOW(), INTERVAL 1 DAY)");
		}
	}

	public static function claimByStateHash(string $stateHash)
	{
		if (!self::isStorageAvailable()) {
			return false;
		}

		$claimToken = bin2hex(random_bytes(32));
		$updated = self::execute(
			"UPDATE `patreon_oauth_transaction` SET `status` = 'processing', `claim_token` = :claim_token WHERE `oauth_state_hash` = :state_hash AND `status` = 'pending' AND `expires_at` > NOW()",
			['claim_token' => $claimToken, 'state_hash' => $stateHash]
		);
		if (!$updated) {
			return false;
		}

		$row = self::fetchRow(
			"SELECT `id`, `claim_token` FROM `patreon_oauth_transaction` WHERE `oauth_state_hash` = :state_hash LIMIT 1",
			['state_hash' => $stateHash]
		);
		if ($row === false || !hash_equals($claimToken, (string)($row['claim_token'] ?? ''))) {
			return false;
		}

		$item = new self();
		return $item->load((int)$row['id']) ? $item : false;
	}

	public function markCompleted()
	{
		$this->set('status', 'completed');
		$this->set('completed_at', new \DateTime());
		$this->set('claim_token', null);
		return $this->save();
	}

	public function markFailed()
	{
		$this->set('status', 'failed');
		$this->set('claim_token', null);
		return $this->save();
	}
}
