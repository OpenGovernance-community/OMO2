<?php
	namespace dbObject;

	class UserPatreon extends DbObject
	{
		protected static $storageAvailable = null;

		public static function tableName()
		{
			return 'user_patreon';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser', 'currently_entitled_amount_cents', 'campaign_lifetime_support_cents'], 'integer'],
				[['scope', 'token_type', 'patreon_user_id', 'patreon_member_id', 'campaign_id', 'full_name', 'email', 'image_url', 'profile_url', 'vanity', 'patron_status', 'last_charge_status', 'last_sync_status'], 'string'],
				[['access_token', 'refresh_token', 'tier_titles', 'last_sync_error'], 'text'],
				[['token_expires_at', 'last_charge_date', 'next_charge_date', 'connected_at', 'last_sync_at', 'created_at', 'updated_at'], 'datetime'],
				[['is_connected'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Utilisateur',
				'access_token' => 'Jeton d’accès Patreon',
				'refresh_token' => 'Jeton de rafraîchissement Patreon',
				'token_expires_at' => 'Expiration du jeton',
				'scope' => 'Scopes Patreon',
				'token_type' => 'Type de jeton',
				'patreon_user_id' => 'ID utilisateur Patreon',
				'patreon_member_id' => 'ID membre Patreon',
				'campaign_id' => 'ID campagne Patreon',
				'full_name' => 'Nom Patreon',
				'email' => 'E-mail Patreon',
				'image_url' => 'Image Patreon',
				'profile_url' => 'Profil Patreon',
				'vanity' => 'Nom public Patreon',
				'patron_status' => 'Statut d’abonnement',
				'last_charge_status' => 'Statut du dernier paiement',
				'last_charge_date' => 'Date du dernier paiement',
				'next_charge_date' => 'Prochain paiement',
				'currently_entitled_amount_cents' => 'Montant actif',
				'campaign_lifetime_support_cents' => 'Montant total soutenu',
				'tier_titles' => 'Paliers actifs',
				'is_connected' => 'Compte connecté',
				'connected_at' => 'Date de connexion',
				'last_sync_at' => 'Dernière synchronisation',
				'last_sync_status' => 'Statut de synchronisation',
				'last_sync_error' => 'Erreur de synchronisation',
				'created_at' => 'Créé le',
				'updated_at' => 'Mis à jour le',
			];
		}

		public static function attributeLength()
		{
			return [
				'scope' => 255,
				'token_type' => 50,
				'patreon_user_id' => 50,
				'patreon_member_id' => 100,
				'campaign_id' => 50,
				'full_name' => 255,
				'email' => 255,
				'image_url' => 500,
				'profile_url' => 500,
				'vanity' => 255,
				'patron_status' => 50,
				'last_charge_status' => 50,
				'last_sync_status' => 50,
			];
		}

		public static function getOrder()
		{
			return 'id DESC';
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
				"SELECT COUNT(*)
				 FROM information_schema.tables
				 WHERE table_schema = :database_name
				   AND table_name = :table_name",
				[
					'database_name' => $databaseName,
					'table_name' => self::tableName(),
				]
			);

			self::$storageAvailable = ((int)$result > 0);
			return self::$storageAvailable;
		}

		public static function findByUserId($userId)
		{
			if (!self::isStorageAvailable()) {
				return false;
			}

			$item = new self();
			if (!$item->load(['IDuser', (int)$userId])) {
				return false;
			}

			return $item;
		}

		public static function loadOrCreateByUserId($userId)
		{
			$userId = (int)$userId;

			if (!self::isStorageAvailable()) {
				return false;
			}

			$item = self::findByUserId($userId);
			if ($item !== false) {
				return $item;
			}

			$item = new self();
			$item->set('IDuser', $userId);
			$item->set('is_connected', 0);
			$item->set('created_at', new \DateTime());
			$item->set('updated_at', new \DateTime());
			return $item;
		}

		public static function loadActiveConnections($userId = 0)
		{
			if (!self::isStorageAvailable()) {
				return [];
			}

			$query = "
				SELECT `id`
				FROM `user_patreon`
				WHERE `is_connected` = 1
			";
			$params = [];

			if ((int)$userId > 0) {
				$query .= " AND `IDuser` = :user_id";
				$params['user_id'] = (int)$userId;
			}

			$query .= " ORDER BY `id` ASC";

			$rows = self::fetchAll($query, $params);
			if ($rows === false) {
				return [];
			}

			$items = [];
			foreach ($rows as $row) {
				$item = new self();
				if ($item->load((int)$row['id'])) {
					$items[] = $item;
				}
			}

			return $items;
		}

		public function isConnected()
		{
			return (int)$this->get('is_connected') > 0
				&& trim((string)$this->get('refresh_token')) !== '';
		}

		public function applyOauthTokens(array $tokens)
		{
			$now = new \DateTime();
			$this->set('access_token', (string)($tokens['access_token'] ?? ''));
			$this->set('refresh_token', (string)($tokens['refresh_token'] ?? ''));
			$this->set('scope', (string)($tokens['scope'] ?? ''));
			$this->set('token_type', (string)($tokens['token_type'] ?? 'Bearer'));
			$this->set('token_expires_at', $tokens['token_expires_at'] ?? null);
			$this->set('is_connected', 1);
			if (!$this->get('connected_at')) {
				$this->set('connected_at', $now);
			}
			if (!$this->get('created_at')) {
				$this->set('created_at', $now);
			}
			$this->set('updated_at', $now);
			$this->set('last_sync_error', null);
		}

		public function applyPatreonProfile(array $profile)
		{
			$fields = [
				'patreon_user_id',
				'patreon_member_id',
				'campaign_id',
				'full_name',
				'email',
				'image_url',
				'profile_url',
				'vanity',
				'patron_status',
				'last_charge_status',
				'last_charge_date',
				'next_charge_date',
				'currently_entitled_amount_cents',
				'campaign_lifetime_support_cents',
				'tier_titles',
			];

			foreach ($fields as $field) {
				$this->set($field, $profile[$field] ?? null);
			}

			if (!$this->get('created_at')) {
				$this->set('created_at', new \DateTime());
			}
			$this->set('updated_at', new \DateTime());
		}

		public function markSyncSuccess()
		{
			$this->set('last_sync_at', new \DateTime());
			$this->set('last_sync_status', 'ok');
			$this->set('last_sync_error', null);
			$this->set('updated_at', new \DateTime());
		}

		public function markSyncFailure($message)
		{
			$this->set('last_sync_at', new \DateTime());
			$this->set('last_sync_status', 'error');
			$this->set('last_sync_error', substr((string)$message, 0, 65535));
			$this->set('updated_at', new \DateTime());
		}

		public function disconnect()
		{
			$this->set('access_token', null);
			$this->set('refresh_token', null);
			$this->set('token_expires_at', null);
			$this->set('scope', null);
			$this->set('token_type', null);
			$this->set('patreon_member_id', null);
			$this->set('campaign_id', null);
			$this->set('patron_status', null);
			$this->set('last_charge_status', null);
			$this->set('last_charge_date', null);
			$this->set('next_charge_date', null);
			$this->set('currently_entitled_amount_cents', 0);
			$this->set('campaign_lifetime_support_cents', 0);
			$this->set('tier_titles', null);
			$this->set('is_connected', 0);
			$this->set('last_sync_status', 'disconnected');
			$this->set('last_sync_error', null);
			$this->set('updated_at', new \DateTime());
			return $this->save();
		}

		public function getTierTitlesList()
		{
			$value = trim((string)$this->get('tier_titles'));
			if ($value === '') {
				return [];
			}

			return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));
		}
	}

?>
