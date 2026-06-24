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
				[['id', 'IDorganization', 'IDusercreation', 'IDusermodification'], 'integer'],
				[['ispublic', 'isbasic'], 'boolean'],
				[['title'], 'string'],
				[['description'], 'text'],
				[['datecreation', 'datemodification'], 'datetime'],
				[['image'], 'sizedimage'],
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
				'IDorganization' => 'Organisation proprietaire',
				'IDusercreation' => 'Createur',
				'IDusermodification' => 'Dernier modificateur',
				'datecreation' => 'Date de creation',
				'datemodification' => 'Date de modification',
				'ispublic' => 'Public',
				'isbasic' => 'Basic',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDorganization' => 'Organisation proprietaire du parcours',
				'IDusercreation' => 'Utilisateur qui a cree le parcours',
				'IDusermodification' => 'Utilisateur qui a modifie le parcours en dernier',
				'datecreation' => 'Date de creation du parcours',
				'datemodification' => 'Date de derniere modification du parcours',
				'ispublic' => 'Rend le parcours visible dans un catalogue partage et ajoutable dans une organisation',
				'isbasic' => 'Instancie automatiquement ce parcours pour chaque nouvelle organisation',
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

		protected static function resolveCurrentUserId()
		{
			return function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
		}

		protected static function resolveCurrentOrganizationId()
		{
			return (int)($_SESSION['currentOrganization'] ?? 0);
		}

		public function save()
		{
			$isNew = (int)$this->getId() <= 0;
			$now = new \DateTimeImmutable();
			$currentUserId = self::resolveCurrentUserId();
			$currentOrganizationId = self::resolveCurrentOrganizationId();

			if ($isNew) {
				if ((int)$this->get('IDorganization') <= 0 && $currentOrganizationId > 0) {
					$this->set('IDorganization', $currentOrganizationId);
				}

				if ((int)$this->get('IDusercreation') <= 0 && $currentUserId > 0) {
					$this->set('IDusercreation', $currentUserId);
				}

				if (!$this->get('datecreation')) {
					$this->set('datecreation', $now);
				}
			}

			if ($currentUserId > 0) {
				$this->set('IDusermodification', $currentUserId);
			}

			if (!$this->get('datecreation')) {
				$this->set('datecreation', $now);
			}

			$this->set('datemodification', $now);

			return parent::save();
		}

		public static function loadBasicRows()
		{
			return self::fetchAll(
				"SELECT id
				FROM parcours
				WHERE isbasic = 1
				ORDER BY datecreation ASC, id ASC"
			);
		}

		public static function instantiateBasicForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Organisation invalide.',
					'createdCount' => 0,
				);
			}

			$rows = self::loadBasicRows();
			if ($rows === false) {
				return array(
					'status' => false,
					'message' => 'Impossible de charger les parcours basic.',
					'createdCount' => 0,
				);
			}

			$createdCount = 0;
			foreach ($rows as $row) {
				$parcoursId = (int)($row['id'] ?? 0);
				if ($parcoursId <= 0) {
					continue;
				}

				$attachResult = \dbObject\OrganizationParcours::attachParcoursToOrganization(
					$organizationId,
					$parcoursId,
					array(
						'everybody' => true,
						'anonymous' => false,
					)
				);
				if (!is_array($attachResult) || empty($attachResult['status'])) {
					return array(
						'status' => false,
						'message' => is_array($attachResult) && !empty($attachResult['message'])
							? (string)$attachResult['message']
							: 'Impossible d instancier un parcours basic.',
						'createdCount' => $createdCount,
					);
				}

				if (!empty($attachResult['created'])) {
					$createdCount++;
				}
			}

			return array(
				'status' => true,
				'createdCount' => $createdCount,
			);
		}

		public static function fetchForOrganizationWithProgress($organizationId, $userId, $viewerHasOrganizationAccess = false) {
			$hasAnonymousColumn = OrganizationParcours::hasAnonymousColumn();
			$where = ["op.IDorganization = :organization_id"];
			if (!$viewerHasOrganizationAccess) {
				if ($hasAnonymousColumn) {
					$where[] = (int)$userId > 0
						? "(op.everybody = 1 OR op.anonymous = 1)"
						: "op.anonymous = 1";
				} else {
					$where[] = "op.everybody = 1";
				}
			}

			$anonymousSelect = $hasAnonymousColumn ? "op.anonymous" : "0 AS anonymous";
			$anonymousGroupBy = $hasAnonymousColumn ? ", op.anonymous" : "";

			$query = "
				SELECT 
					p.id,
					p.title,
					p.description,
					p.image,
					p.IDorganization AS owner_organization_id,
					op.position,
					op.everybody,
					" . $anonymousSelect . ",
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
				GROUP BY p.id, p.title, p.description, p.image, p.IDorganization, op.position, op.everybody" . $anonymousGroupBy . "
				ORDER BY op.position ASC, p.title ASC
			";

			$rows = self::fetchAll($query, [
				'user_id' => (int)$userId,
				'organization_id' => (int)$organizationId,
			]);

			return is_array($rows) ? $rows : [];
		}

		public static function fetchEverybodyForOrganizationWithProgress($organizationId, $userId = 0)
		{
			$hasAnonymousColumn = OrganizationParcours::hasAnonymousColumn();
			$anonymousSelect = $hasAnonymousColumn ? "op.anonymous" : "0 AS anonymous";
			$anonymousGroupBy = $hasAnonymousColumn ? ", op.anonymous" : "";

			$query = "
				SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					p.IDorganization AS owner_organization_id,
					op.position,
					op.everybody,
					" . $anonymousSelect . ",
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
				GROUP BY p.id, p.title, p.description, p.image, p.IDorganization, op.position, op.everybody" . $anonymousGroupBy . "
				ORDER BY op.position ASC, p.title ASC
			";

			$rows = self::fetchAll($query, [
				'user_id' => (int)$userId,
				'organization_id' => (int)$organizationId,
			]);

			return is_array($rows) ? $rows : [];
		}

		public static function fetchBasicCatalogWithProgress($userId = 0)
		{
			$userId = (int)$userId;
			$where = ["p.isbasic = 1"];

			$query = "
				SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					p.IDorganization AS owner_organization_id,
					COUNT(DISTINCT pm.IDmission) AS total_missions,
					COALESCE(SUM(
						CASE
							WHEN lm.done IS NOT NULL THEN 1
							ELSE 0
						END
					), 0) AS done_missions
				FROM parcours p
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				LEFT JOIN user_mission lm
					ON lm.IDmission = pm.IDmission
					AND lm.IDparcours = p.id
					AND lm.IDuser = :user_id
				WHERE " . implode(" AND ", $where) . "
				GROUP BY p.id, p.title, p.description, p.image, p.IDorganization
				ORDER BY p.title ASC, p.id ASC
			";

			$rows = self::fetchAll($query, [
				'user_id' => $userId,
			]);

			return is_array($rows) ? $rows : [];
		}

		public static function resolveBasicCatalogAccessContext($parcoursId, $userId = 0)
		{
			$parcoursId = (int)$parcoursId;
			$userId = (int)$userId;
			if ($parcoursId <= 0) {
				return [
					'exists' => false,
					'canView' => false,
					'userId' => $userId,
					'isLoggedIn' => $userId > 0,
					'hasOrganizationAccess' => false,
					'everybody' => false,
					'anonymous' => false,
					'isBasicCatalog' => true,
				];
			}

			$row = self::fetchRow(
				"SELECT id, isbasic
				FROM parcours
				WHERE id = :parcours_id
				LIMIT 1",
				[
					'parcours_id' => $parcoursId,
				]
			);

			$exists = is_array($row) && (int)($row['id'] ?? 0) > 0;
			$isBasic = $exists && !empty($row['isbasic']);
			$canView = $isBasic;

			return [
				'exists' => $exists,
				'canView' => $canView,
				'canTrackProgress' => $canView,
				'canTrackProgressLocally' => $userId <= 0 && $canView,
				'userId' => $userId,
				'isLoggedIn' => $userId > 0,
				'hasOrganizationAccess' => false,
				'everybody' => $isBasic,
				'anonymous' => $userId <= 0 && $isBasic,
				'isBasicCatalog' => true,
			];
		}

		public static function fetchImportableForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return [];
			}

			$rows = self::fetchAll(
				"SELECT
					p.id,
					p.title,
					p.description,
					p.image,
					p.ispublic,
					p.isbasic,
					p.IDorganization,
					owner.name AS owner_name,
					COUNT(DISTINCT pm.IDmission) AS total_missions
				FROM parcours p
				LEFT JOIN organization owner
					ON owner.id = p.IDorganization
				LEFT JOIN parcours_mission pm
					ON pm.IDparcours = p.id
				WHERE (p.ispublic = 1 OR p.isbasic = 1)
				  AND NOT EXISTS (
					SELECT 1
					FROM organization_parcours op
					WHERE op.IDorganization = :organization_id
					  AND op.IDparcours = p.id
				  )
				GROUP BY
					p.id,
					p.title,
					p.description,
					p.image,
					p.ispublic,
					p.isbasic,
					p.IDorganization,
					owner.name
				ORDER BY p.isbasic DESC, p.ispublic DESC, p.title ASC, p.id ASC",
				[
					'organization_id' => $organizationId,
				]
			);

			return is_array($rows) ? $rows : [];
		}

		public static function loadImportableForOrganization($organizationId, $parcoursId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$parcoursId;
			if ($organizationId <= 0 || $parcoursId <= 0) {
				return false;
			}

			return self::fetchRow(
				"SELECT
					p.id,
					p.title,
					p.ispublic,
					p.isbasic
				FROM parcours p
				WHERE p.id = :parcours_id
				  AND (p.ispublic = 1 OR p.isbasic = 1)
				  AND NOT EXISTS (
					SELECT 1
					FROM organization_parcours op
					WHERE op.IDorganization = :organization_id
					  AND op.IDparcours = p.id
				  )
				LIMIT 1",
				[
					'organization_id' => $organizationId,
					'parcours_id' => $parcoursId,
				]
			);
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
