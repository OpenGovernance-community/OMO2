<?php
	namespace dbObject;

	class Parcours extends DbObject
	{
		public static function tableName()
		{
			return 'parcours';
		}

		public static function rules()
		{
			return [
				[['title'], 'required'],
				[['id'], 'integer'],
				[['title'], 'string'],
				[['description'], 'text'],
				[['image'], 'image'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'description' => 'Description',
				'image' => 'Image',
			];
		}

		public static function attributeLength() {
			return [
				'title' => 150,
				'image' => 100,
			];
		}

		public static function getOrder() {
			return "title";
		}

		public static function fetchForOrganizationWithProgress($organizationId, $userId, $viewerHasOrganizationAccess = false) {
			$where = ["op.IDorganization = :organization_id"];
			if (!$viewerHasOrganizationAccess) {
				$where[] = (int)$userId > 0
					? "(op.everybody = 1 OR op.anonymous = 1)"
					: "op.anonymous = 1";
			}

			$query = "
				SELECT 
					p.id,
					p.title,
					p.description,
					p.image,
					op.position,
					op.everybody,
					op.anonymous,
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE 
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM organization_parcours op
				INNER JOIN parcours p
					ON p.id = op.IDparcours
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = p.id
					AND lm.IDuser = :user_id
				WHERE " . implode(" AND ", $where) . "
				GROUP BY p.id, p.title, p.description, p.image, op.position, op.everybody, op.anonymous
				ORDER BY op.position ASC, p.title ASC
			";

			return self::fetchAll($query, [
				'user_id' => (int)$userId,
				'organization_id' => (int)$organizationId,
			]);
		}

		public static function fetchEverybodyForOrganizationWithProgress($organizationId, $userId = 0)
		{
			$query = "
				SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					op.position,
					op.everybody,
					op.anonymous,
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM organization_parcours op
				INNER JOIN parcours p
					ON p.id = op.IDparcours
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = p.id
					AND lm.IDuser = :user_id
				WHERE op.IDorganization = :organization_id
				  AND op.everybody = 1
				GROUP BY p.id, p.title, p.description, p.image, op.position, op.everybody, op.anonymous
				ORDER BY op.position ASC, p.title ASC
			";

			return self::fetchAll($query, [
				'user_id' => (int)$userId,
				'organization_id' => (int)$organizationId,
			]);
		}

		public static function countRestrictedForPublicCatalog($organizationId)
		{
			$query = "
				SELECT COUNT(*)
				FROM organization_parcours
				WHERE IDorganization = :organization_id
				  AND (everybody IS NULL OR everybody = 0)
			";

			return (int)self::fetchValue($query, [
				'organization_id' => (int)$organizationId,
			]);
		}
	}

?>
