<?php
	namespace dbObject;

	class OrganizationParcours extends DbObject
	{
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
