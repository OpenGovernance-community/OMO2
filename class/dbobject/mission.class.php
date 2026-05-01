<?php
	namespace dbObject;

	class Mission extends DbObject
	{
		public static function tableName()
		{
			return 'mission';
		}

		public static function rules()
		{
			return [
				[['title', 'resume'], 'required'],
				[['id', 'position'], 'integer'],
				[['title', 'video'], 'string'],
				[['resume'], 'text'],
				[['html'], 'html'],
				[['datecreation', 'dateupdate'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'title' => 'Titre',
				'resume' => 'Resume',
				'video' => 'Video',
				'html' => 'Contenu HTML',
				'position' => 'Position',
				'datecreation' => 'Date de creation',
				'dateupdate' => 'Date de mise a jour',
			];
		}

		public static function attributeLength() {
			return [
				'title' => 150,
				'video' => 150,
			];
		}

		public static function getOrder() {
			return "position";
		}

		public function getQuizCount() {
			$query = "SELECT COUNT(*) FROM mission_question WHERE IDmission = :mission_id";
			return (int)self::fetchValue($query, ['mission_id' => (int)$this->getId()]);
		}

		public static function fetchAvailableForUserParcours($userId, $parcoursId) {
			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE
					NOT EXISTS (
						SELECT 1
						FROM user_mission lm_done
						WHERE lm_done.IDmission = m.id
						  AND lm_done.IDuser = :done_user_id
						  AND lm_done.IDparcours = :done_parcours_id
						  AND lm_done.done IS NOT NULL
					)
					AND
					(
						NOT EXISTS (
							SELECT 1
							FROM mission_dependencies md
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = :dep_parcours_id
						)
						OR
						NOT EXISTS (
							SELECT 1
							FROM mission_dependencies md
							LEFT JOIN user_mission lm
								ON lm.IDmission = md.IDmission_parent
								AND lm.IDuser = :req_user_id
								AND lm.IDparcours = :req_parcours_id
							WHERE md.IDmission_child = m.id
							  AND md.IDparcours = :req_dep_parcours_id
							  AND md.required = 1
							  AND lm.done IS NULL
						)
					)
				ORDER BY pm.branch ASC, m.position ASC
			";

			return self::fetchAll($query, [
				'pm_parcours_id' => (int)$parcoursId,
				'done_user_id' => (int)$userId,
				'done_parcours_id' => (int)$parcoursId,
				'dep_parcours_id' => (int)$parcoursId,
				'req_user_id' => (int)$userId,
				'req_parcours_id' => (int)$parcoursId,
				'req_dep_parcours_id' => (int)$parcoursId,
			]);
		}

		public static function fetchLockedForUserParcours($userId, $parcoursId) {
			$query = "
				SELECT m.*, pm.branch
				FROM mission m
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :pm_parcours_id
				WHERE
					NOT EXISTS (
						SELECT 1
						FROM user_mission lm
						WHERE lm.IDmission = m.id
						  AND lm.IDuser = :done_user_id
						  AND lm.IDparcours = :done_parcours_id
						  AND lm.done IS NOT NULL
					)
					AND
					EXISTS (
						SELECT 1
						FROM mission_dependencies md
						LEFT JOIN user_mission lm
							ON lm.IDmission = md.IDmission_parent
							AND lm.IDuser = :req_user_id
							AND lm.IDparcours = :req_parcours_id
						WHERE md.IDmission_child = m.id
						  AND md.IDparcours = :req_dep_parcours_id
						  AND md.required = 1
						  AND lm.done IS NULL
					)
				ORDER BY pm.branch ASC, m.position ASC
			";

			return self::fetchAll($query, [
				'pm_parcours_id' => (int)$parcoursId,
				'done_user_id' => (int)$userId,
				'done_parcours_id' => (int)$parcoursId,
				'req_user_id' => (int)$userId,
				'req_parcours_id' => (int)$parcoursId,
				'req_dep_parcours_id' => (int)$parcoursId,
			]);
		}

		public static function fetchDoneForUserParcours($userId, $parcoursId) {
			$query = "
				SELECT m.*, pm.branch, lm.done
				FROM user_mission lm
				INNER JOIN mission m ON m.id = lm.IDmission
				INNER JOIN parcours_mission pm
					ON pm.IDmission = m.id
					AND pm.IDparcours = :parcours_id
				WHERE lm.IDuser = :user_id
				  AND lm.IDparcours = :done_parcours_id
				  AND lm.done IS NOT NULL
				ORDER BY lm.done DESC
			";

			return self::fetchAll($query, [
				'user_id' => (int)$userId,
				'parcours_id' => (int)$parcoursId,
				'done_parcours_id' => (int)$parcoursId,
			]);
		}
	}

?>
