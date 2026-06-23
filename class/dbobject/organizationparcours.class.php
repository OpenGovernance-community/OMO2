<?php
	namespace dbObject;

	class OrganizationParcours extends DbObject
	{
		protected static $hasAnonymousColumnCache = null;

		public static function tableName()
		{
			return 'organization_parcours';
		}

		public static function rules()
		{
			return [
				[['id', 'IDorganization', 'IDparcours', 'position'], 'integer'],
				[['everybody', 'anonymous'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDparcours' => 'Parcours',
				'position' => 'Position',
				'everybody' => 'Tout le monde',
				'anonymous' => 'Anonyme',
			];
		}

		public static function getOrder() {
			return "position, id";
		}

		public static function hasAnonymousColumn()
		{
			if (self::$hasAnonymousColumnCache !== null) {
				return self::$hasAnonymousColumnCache;
			}

			$query = "
				SELECT COUNT(*)
				FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = DATABASE()
				  AND TABLE_NAME = 'organization_parcours'
				  AND COLUMN_NAME = 'anonymous'
			";

			self::$hasAnonymousColumnCache = (int)self::fetchValue($query) > 0;
			return self::$hasAnonymousColumnCache;
		}

		public static function loadForOrganizationParcours($organizationId, $parcoursId)
		{
			$item = new self();
			if (!$item->load([
				['IDorganization', (int)$organizationId],
				['IDparcours', (int)$parcoursId],
			])) {
				return null;
			}

			return $item;
		}

		public static function attachParcoursToOrganization($organizationId, $parcoursId, array $options = array())
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$parcoursId;
			if ($organizationId <= 0 || $parcoursId <= 0) {
				return array(
					'status' => false,
					'message' => 'Organisation ou parcours invalide.',
				);
			}

			$item = new self();
			$created = !$item->load([
				['IDorganization', $organizationId],
				['IDparcours', $parcoursId],
			]);

			if ($created) {
				$item->set('IDorganization', $organizationId);
				$item->set('IDparcours', $parcoursId);
			}

			$position = array_key_exists('position', $options) ? (int)$options['position'] : 0;
			if ($created && $position <= 0) {
				$position = (int)\dbObject\DbObject::fetchValue(
					"SELECT COALESCE(MAX(position), 0) + 1
					FROM organization_parcours
					WHERE IDorganization = :organization_id",
					array(
						'organization_id' => $organizationId,
					)
				);
			}

			if ($created || array_key_exists('position', $options)) {
				$item->set('position', $position > 0 ? $position : null);
			}

			if ($created || array_key_exists('everybody', $options)) {
				$item->set('everybody', array_key_exists('everybody', $options) ? (bool)$options['everybody'] : true);
			}

			if ($created || array_key_exists('anonymous', $options)) {
				$item->set('anonymous', array_key_exists('anonymous', $options) ? (bool)$options['anonymous'] : false);
			}

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status'])) {
				return array(
					'status' => false,
					'message' => is_array($saveResult) && !empty($saveResult['text'])
						? (string)$saveResult['text']
						: 'Impossible d attacher ce parcours a l organisation.',
				);
			}

			return array(
				'status' => true,
				'created' => $created,
				'id' => (int)$item->getId(),
			);
		}

		public static function resolveAccessContext($organizationId, $parcoursId, $userId = 0)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$parcoursId;
			$userId = (int)$userId;

			$link = self::loadForOrganizationParcours($organizationId, $parcoursId);
			if ($link === null) {
				return [
					'exists' => false,
					'canView' => false,
					'userId' => $userId,
					'isLoggedIn' => $userId > 0,
					'hasOrganizationAccess' => false,
					'everybody' => false,
					'anonymous' => false,
				];
			}

			$hasOrganizationAccess = (bool)\commonUserHasOrganizationAccess($userId, $organizationId);
			$everybody = (bool)$link->get('everybody');
			$anonymous = (bool)$link->get('anonymous');

			return [
				'exists' => true,
				'canView' => $hasOrganizationAccess || $everybody || $anonymous,
				'canTrackProgress' => $hasOrganizationAccess || $userId > 0 || $anonymous,
				'canTrackProgressLocally' => $userId <= 0 && ($hasOrganizationAccess || $anonymous),
				'userId' => $userId,
				'isLoggedIn' => $userId > 0,
				'hasOrganizationAccess' => $hasOrganizationAccess,
				'everybody' => $everybody,
				'anonymous' => $anonymous,
				'link' => $link,
			];
		}
	}

?>
