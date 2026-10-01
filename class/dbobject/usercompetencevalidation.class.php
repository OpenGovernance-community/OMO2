<?php
	namespace dbObject;

	class UserCompetenceValidation extends DbObject
	{
		public static function tableName()
		{
			return 'user_competence_validation';
		}

		public static function rules()
		{
			return [
				[['id', 'IDuser_competence', 'IDvalidator_user', 'IDorganization', 'level'], 'integer'],
				[['IDuser_competence', 'IDvalidator_user', 'IDorganization', 'level'], 'required'],
				[['IDuser_competence', 'IDvalidator_user', 'IDorganization'], 'fk'],
				[['datecreation', 'datemodification'], 'datetime'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser_competence' => 'Competence utilisateur',
				'IDvalidator_user' => 'Validateur',
				'IDorganization' => 'Organisation',
				'level' => 'Niveau',
				'datecreation' => 'Creation',
				'datemodification' => 'Modification',
			];
		}

		public static function getOrder()
		{
			return 'datecreation ASC, id ASC';
		}

		public static function handleUserDeparture($organizationId, $userId, $ghostUserId)
		{
			return self::execute(
				'UPDATE user_competence_validation SET IDvalidator_user = :ghost_user_id WHERE IDorganization = :organization_id AND IDvalidator_user = :user_id',
				array('ghost_user_id' => (int)$ghostUserId, 'organization_id' => (int)$organizationId, 'user_id' => (int)$userId)
			);
		}

		public function canEdit()
		{
			$currentUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);

			return $currentUserId > 0 && $currentUserId === (int)$this->get('IDvalidator_user');
		}
	}

?>
