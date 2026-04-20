<?php
	namespace dbObject;

	class UserLoginToken extends DbObject
	{
		public static function tableName()
		{
			return 'user_login_token';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser'], 'integer'],
				[['token', 'code_hash', 'request_ip'], 'string'],
				[['expires_at', 'created_at', 'last_attempt_at'], 'datetime'],
				[['attempt_count'], 'integer'],
				[['used', 'remember'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Utilisateur',
				'token' => 'Jeton de demande',
				'code_hash' => 'Hash du code',
				'expires_at' => 'Expire le',
				'request_ip' => 'IP de demande',
				'attempt_count' => 'Essais',
				'created_at' => 'Cree le',
				'last_attempt_at' => 'Dernier essai',
				'used' => 'Utilise',
				'remember' => 'Se souvenir',
			];
		}

		public static function attributeLength() {
			return [
				'token' => 64,
				'code_hash' => 255,
				'request_ip' => 45,
			];
		}

		public static function getOrder() {
			return "id DESC";
		}

		public static function invalidateActiveForUser($userId) {
			return self::execute(
				"UPDATE user_login_token
				 SET used = 1
				 WHERE IDuser = :user_id
				   AND used = 0",
				['user_id' => (int)$userId]
			);
		}

		public static function issue($userId, $token, $codeHash, $requestIp, $remember = 0) {
			self::invalidateActiveForUser($userId);

			$item = new self();
			$item->set('IDuser', (int)$userId);
			$item->set('token', $token);
			$item->set('code_hash', $codeHash);
			$item->set('request_ip', $requestIp);
			$item->set('expires_at', new \DateTime('+5 minutes'));
			$item->set('attempt_count', 0);
			$item->set('created_at', new \DateTime());
			$item->set('used', 0);
			$item->set('remember', (int)$remember);
			$result = $item->save();
			return !empty($result['status']) ? $item : false;
		}

		public static function findByToken($token) {
			$query = "
				SELECT *
				FROM user_login_token
				WHERE token = :token
				LIMIT 1
			";

			$row = self::fetchRow($query, ['token' => $token]);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId($row['id']);
			return $item;
		}

		public static function findValidByToken($token) {
			$query = "
				SELECT *
				FROM user_login_token
				WHERE token = :token
				  AND used = 0
				  AND expires_at > NOW()
				  AND attempt_count < 5
				LIMIT 1
			";

			$row = self::fetchRow($query, ['token' => $token]);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId($row['id']);
			return $item;
		}

		public function markUsed() {
			$this->set('used', 1);
			return $this->save();
		}

		public function incrementAttemptCount() {
			$this->set('attempt_count', (int)$this->get('attempt_count') + 1);
			$this->set('last_attempt_at', new \DateTime());
			return $this->save();
		}
	}

?>
