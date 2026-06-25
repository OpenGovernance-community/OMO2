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

		protected static function fetchSingleColumnIds($query, array $params, $columnName)
		{
			$rows = self::fetchAll($query, $params);
			if (!is_array($rows)) {
				return [];
			}

			$ids = [];
			foreach ($rows as $row) {
				$value = (int)($row[$columnName] ?? 0);
				if ($value > 0) {
					$ids[$value] = $value;
				}
			}

			return array_values($ids);
		}

		protected static function deleteQuestionIfUnused($questionId)
		{
			$questionId = (int)$questionId;
			if ($questionId <= 0) {
				return false;
			}

			if (
				self::tableExists('mission_question')
				&& (int)self::fetchValue(
					"SELECT COUNT(*) FROM mission_question WHERE IDquestion = :question_id",
					['question_id' => $questionId]
				) > 0
			) {
				return false;
			}

			if (self::tableExists('user_question_response')) {
				$result = self::execute(
					"DELETE FROM user_question_response WHERE IDquestion = :question_id",
					['question_id' => $questionId]
				);
				if ($result === false) {
					throw new \RuntimeException('question_response_delete_failed');
				}
			}

			if (self::tableExists('question_choice')) {
				$result = self::execute(
					"DELETE FROM question_choice WHERE IDquestion = :question_id",
					['question_id' => $questionId]
				);
				if ($result === false) {
					throw new \RuntimeException('question_choice_delete_failed');
				}
			}

			$question = new \dbObject\Question();
			if ($question->load($questionId) && !$question->delete()) {
				throw new \RuntimeException('question_delete_failed');
			}

			return true;
		}

		protected static function deleteHomeworkIfUnused($homeworkId)
		{
			$homeworkId = (int)$homeworkId;
			if ($homeworkId <= 0) {
				return false;
			}

			if (
				self::tableExists('mission_homework')
				&& (int)self::fetchValue(
					"SELECT COUNT(*) FROM mission_homework WHERE IDhomework = :homework_id",
					['homework_id' => $homeworkId]
				) > 0
			) {
				return false;
			}

			if (self::tableExists('user_homework')) {
				$result = self::execute(
					"DELETE FROM user_homework WHERE IDhomework = :homework_id",
					['homework_id' => $homeworkId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_homework_by_homework_delete_failed');
				}
			}

			$homework = new \dbObject\Homework();
			if ($homework->load($homeworkId) && !$homework->delete()) {
				throw new \RuntimeException('homework_delete_failed');
			}

			return true;
		}

		protected static function deleteMissionIfUnused($missionId)
		{
			$missionId = (int)$missionId;
			if ($missionId <= 0) {
				return [
					'deleted' => false,
					'deletedQuestions' => 0,
					'deletedHomeworks' => 0,
				];
			}

			if (
				self::tableExists('parcours_mission')
				&& (int)self::fetchValue(
					"SELECT COUNT(*) FROM parcours_mission WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				) > 0
			) {
				return [
					'deleted' => false,
					'deletedQuestions' => 0,
					'deletedHomeworks' => 0,
				];
			}

			$questionIds = self::tableExists('mission_question')
				? self::fetchSingleColumnIds(
					"SELECT IDquestion FROM mission_question WHERE IDmission = :mission_id",
					['mission_id' => $missionId],
					'IDquestion'
				)
				: [];
			$homeworkIds = self::tableExists('mission_homework')
				? self::fetchSingleColumnIds(
					"SELECT IDhomework FROM mission_homework WHERE IDmission = :mission_id",
					['mission_id' => $missionId],
					'IDhomework'
				)
				: [];

			if (self::tableExists('user_question_response')) {
				$result = self::execute(
					"DELETE FROM user_question_response WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_question_response_delete_failed');
				}
			}

			if (self::tableExists('user_homework')) {
				$result = self::execute(
					"DELETE FROM user_homework WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_homework_by_mission_delete_failed');
				}
			}

			if (self::tableExists('user_mission')) {
				$result = self::execute(
					"DELETE FROM user_mission WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('user_mission_by_mission_delete_failed');
				}
			}

			if (self::tableExists('mission_dependencies')) {
				$result = self::execute(
					"DELETE FROM mission_dependencies
					WHERE IDmission_parent = :mission_id
					   OR IDmission_child = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('mission_dependencies_delete_failed');
				}
			}

			if (self::tableExists('mission_question')) {
				$result = self::execute(
					"DELETE FROM mission_question WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('mission_question_delete_failed');
				}
			}

			if (self::tableExists('mission_homework')) {
				$result = self::execute(
					"DELETE FROM mission_homework WHERE IDmission = :mission_id",
					['mission_id' => $missionId]
				);
				if ($result === false) {
					throw new \RuntimeException('mission_homework_delete_failed');
				}
			}

			$mission = new \dbObject\Mission();
			if ($mission->load($missionId) && !$mission->delete()) {
				throw new \RuntimeException('mission_delete_failed');
			}

			$deletedQuestions = 0;
			foreach ($questionIds as $questionId) {
				if (self::deleteQuestionIfUnused($questionId)) {
					$deletedQuestions++;
				}
			}

			$deletedHomeworks = 0;
			foreach ($homeworkIds as $homeworkId) {
				if (self::deleteHomeworkIfUnused($homeworkId)) {
					$deletedHomeworks++;
				}
			}

			return [
				'deleted' => true,
				'deletedQuestions' => $deletedQuestions,
				'deletedHomeworks' => $deletedHomeworks,
			];
		}

		public function previewDeleteForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$this->getId();
			$ownerOrganizationId = (int)$this->get('IDorganization');

			if ($organizationId <= 0 || $parcoursId <= 0) {
				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Parcours ou organisation invalide.',
				];
			}

			$link = \dbObject\OrganizationParcours::loadForOrganizationParcours($organizationId, $parcoursId);
			if (!$link instanceof \dbObject\OrganizationParcours) {
				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Ce parcours n est pas rattache a l organisation courante.',
				];
			}

			if ($ownerOrganizationId <= 0 || $ownerOrganizationId !== $organizationId) {
				return [
					'status' => true,
					'action' => 'detach',
					'message' => 'Ce parcours appartient a une autre organisation. Il sera seulement detache de votre organisation.',
					'confirmMessage' => 'Ce parcours appartient a une autre organisation.' . "\n\n" . 'Vous ne pouvez pas le supprimer definitivement.' . "\n\n" . 'Il sera seulement detache de votre organisation.' . "\n\n" . 'Voulez vous continuer ?',
					'totalOrganizationCount' => 0,
					'otherOrganizationCount' => 0,
					'isOwner' => false,
				];
			}

			$totalOrganizationCount = (int)self::fetchValue(
				"SELECT COUNT(*)
				FROM organization_parcours
				WHERE IDparcours = :parcours_id",
				['parcours_id' => $parcoursId]
			);
			$otherOrganizationCount = max(0, $totalOrganizationCount - 1);

			if ($otherOrganizationCount > 0) {
				return [
					'status' => true,
					'action' => 'detach',
					'message' => $otherOrganizationCount === 1
						? 'Ce parcours est utilise par 1 autre organisation. Il sera seulement detache de votre organisation.'
						: 'Ce parcours est utilise par ' . $otherOrganizationCount . ' autres organisations. Il sera seulement detache de votre organisation.',
					'confirmMessage' => $otherOrganizationCount === 1
						? 'Ce parcours est utilise par 1 autre organisation.' . "\n\n" . 'Il sera seulement detache de votre organisation.' . "\n\n" . 'Voulez vous continuer ?'
						: 'Ce parcours est utilise par ' . $otherOrganizationCount . ' autres organisations.' . "\n\n" . 'Il sera seulement detache de votre organisation.' . "\n\n" . 'Voulez vous continuer ?',
					'totalOrganizationCount' => $totalOrganizationCount,
					'otherOrganizationCount' => $otherOrganizationCount,
					'isOwner' => true,
				];
			}

			return [
				'status' => true,
				'action' => 'delete',
				'message' => 'Ce parcours n est utilise que dans votre organisation. Il sera supprime definitivement avec ses elements non reutilises.',
				'confirmMessage' => 'Ce parcours n est utilise que dans votre organisation.' . "\n\n" . 'Il sera supprime definitivement, avec ses missions, questions et devoirs devenus orphelins.' . "\n\n" . 'Etes vous sur de vouloir continuer ?',
				'totalOrganizationCount' => $totalOrganizationCount,
				'otherOrganizationCount' => 0,
				'isOwner' => true,
			];
		}

		public function deleteForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			$parcoursId = (int)$this->getId();
			$preview = $this->previewDeleteForOrganization($organizationId);
			if (!is_array($preview) || empty($preview['status'])) {
				return is_array($preview)
					? $preview
					: [
						'status' => false,
						'action' => 'none',
						'message' => 'Impossible de preparer la suppression de ce parcours.',
					];
			}

			$pdo = self::getPdo();
			if (!$pdo instanceof \PDO) {
				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Connexion a la base indisponible.',
				];
			}

			$startedTransaction = !$pdo->inTransaction();

			try {
				if ($startedTransaction) {
					$pdo->beginTransaction();
				}

				$missionIds = self::tableExists('parcours_mission')
					? self::fetchSingleColumnIds(
						"SELECT IDmission FROM parcours_mission WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId],
						'IDmission'
					)
					: [];

				$deleteLinkResult = self::execute(
					"DELETE FROM organization_parcours
					WHERE IDorganization = :organization_id
					  AND IDparcours = :parcours_id",
					[
						'organization_id' => $organizationId,
						'parcours_id' => $parcoursId,
					]
				);
				if ($deleteLinkResult === false) {
					throw new \RuntimeException('organization_parcours_delete_failed');
				}

				if (($preview['action'] ?? 'none') === 'detach' && empty($preview['isOwner'])) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->commit();
					}

					return [
						'status' => true,
						'action' => 'detach',
						'message' => 'Le parcours a ete detache de votre organisation.',
						'remainingOrganizationCount' => 0,
						'deletedMissionCount' => 0,
						'deletedQuestionCount' => 0,
						'deletedHomeworkCount' => 0,
					];
				}

				$remainingOrganizationCount = (int)self::fetchValue(
					"SELECT COUNT(*)
					FROM organization_parcours
					WHERE IDparcours = :parcours_id",
					['parcours_id' => $parcoursId]
				);

				if ($remainingOrganizationCount > 0) {
					if ($startedTransaction && $pdo->inTransaction()) {
						$pdo->commit();
					}

					return [
						'status' => true,
						'action' => 'detach',
						'message' => $remainingOrganizationCount === 1
							? 'Le parcours a ete detache de votre organisation. Il reste utilise dans 1 autre organisation.'
							: 'Le parcours a ete detache de votre organisation. Il reste utilise dans ' . $remainingOrganizationCount . ' autres organisations.',
						'remainingOrganizationCount' => $remainingOrganizationCount,
						'deletedMissionCount' => 0,
						'deletedQuestionCount' => 0,
						'deletedHomeworkCount' => 0,
					];
				}

				if (self::tableExists('user_homework')) {
					$result = self::execute(
						"DELETE FROM user_homework WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('user_homework_by_parcours_delete_failed');
					}
				}

				if (self::tableExists('user_mission')) {
					$result = self::execute(
						"DELETE FROM user_mission WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('user_mission_by_parcours_delete_failed');
					}
				}

				if (self::tableExists('mission_dependencies')) {
					$result = self::execute(
						"DELETE FROM mission_dependencies WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('mission_dependencies_by_parcours_delete_failed');
					}
				}

				if (self::tableExists('parcours_mission')) {
					$result = self::execute(
						"DELETE FROM parcours_mission WHERE IDparcours = :parcours_id",
						['parcours_id' => $parcoursId]
					);
					if ($result === false) {
						throw new \RuntimeException('parcours_mission_delete_failed');
					}
				}

				if (!parent::delete()) {
					throw new \RuntimeException('parcours_delete_failed');
				}

				$deletedMissionCount = 0;
				$deletedQuestionCount = 0;
				$deletedHomeworkCount = 0;
				foreach ($missionIds as $missionId) {
					$missionDeleteResult = self::deleteMissionIfUnused($missionId);
					if (!empty($missionDeleteResult['deleted'])) {
						$deletedMissionCount++;
						$deletedQuestionCount += (int)($missionDeleteResult['deletedQuestions'] ?? 0);
						$deletedHomeworkCount += (int)($missionDeleteResult['deletedHomeworks'] ?? 0);
					}
				}

				if ($startedTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}

				return [
					'status' => true,
					'action' => 'delete',
					'message' => 'Le parcours et ses elements non reutilises ont ete supprimes.',
					'remainingOrganizationCount' => 0,
					'deletedMissionCount' => $deletedMissionCount,
					'deletedQuestionCount' => $deletedQuestionCount,
					'deletedHomeworkCount' => $deletedHomeworkCount,
				];
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return [
					'status' => false,
					'action' => 'none',
					'message' => 'Impossible de supprimer ce parcours pour le moment.',
				];
			}
		}
	}

?>
