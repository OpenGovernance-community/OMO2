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
				[['token'], 'string'],
				[['expires_at'], 'datetime'],
				[['used', 'remember'], 'boolean'],
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
				'used' => 'Utilise',
				'remember' => 'Se souvenir',
			];
		}

		public static function attributeLength() {
			return [
				'token' => 64,
			];
		}

		public static function getOrder() {
			return "id DESC";
		}

		public static function issue($userId, $token, $remember = 0) {
			$item = new self();
			$item->set('IDuser', (int)$userId);
			$item->set('token', $token);
			$item->set('expires_at', new \DateTime('+15 minutes'));
			$item->set('used', 0);
			$item->set('remember', (int)$remember);
			return $item->save();
		}

		public static function findValidByToken($token) {
			$query = "
				SELECT *
				FROM user_login_token
				WHERE token = :token
				  AND used = 0
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

		public function markUsed() {
			$this->set('used', 1);
			return $this->save();
		}
	}

?>
