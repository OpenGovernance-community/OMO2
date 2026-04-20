<?php
	namespace dbObject;

	class UserRemember extends DbObject
	{
		public static function tableName()
		{
			return 'user_remember';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser'], 'integer'],
				[['token', 'ip', 'browser', 'os'], 'string'],
				[['user_agent'], 'text'],
				[['expires_at', 'created_at'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Utilisateur',
				'token' => 'Token',
				'expires_at' => 'Expire le',
				'ip' => 'IP',
				'user_agent' => 'User agent',
				'browser' => 'Navigateur',
				'os' => 'Systeme',
				'created_at' => 'Cree le',
			];
		}

		public static function attributeLength() {
			return [
				'token' => 64,
				'ip' => 45,
				'browser' => 100,
				'os' => 100,
			];
		}

		public static function getOrder() {
			return "id DESC";
		}

		public static function issue($userId, $token, $ip, $userAgent, $browser, $os) {
			$item = new self();
			$item->set('IDuser', (int)$userId);
			$item->set('token', $token);
			$item->set('expires_at', new \DateTime('+30 days'));
			$item->set('ip', $ip);
			$item->set('user_agent', $userAgent);
			$item->set('browser', $browser);
			$item->set('os', $os);
			return $item->save();
		}

		public static function findValidByToken($token) {
			$query = "
				SELECT *
				FROM user_remember
				WHERE token = :token
				  AND expires_at > NOW()
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
	}

?>
