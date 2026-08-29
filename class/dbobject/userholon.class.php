<?php
	namespace dbObject;

	class UserHolon extends DbObject
	{
		public const BUDGET_RECURRENCE_DAY = 'day';
		public const BUDGET_RECURRENCE_WEEK = 'week';
		public const BUDGET_RECURRENCE_MONTH = 'month';
		public const BUDGET_RECURRENCE_YEAR = 'year';

		protected $_scopedMembershipCache = array();
		protected $_linkedUserCache = null;

	    public static function tableName()
		{
			return 'user_holon';
		}

		public static function rules()
		{
			return [
				[['IDuser', 'IDholon'], 'required'],
				[['id'], 'integer'],
				[['IDuser', 'IDholon'], 'fk'],
				[['focus'], 'string'],
				[['time_budget_hours', 'money_budget'], 'float'],
				[['time_budget_recurrence', 'money_budget_recurrence'], 'string'],
				[['assignment_review_date'], 'date'],
				[['parameters'], 'parameters'],
				[['datecreation', 'dateconnexion'], 'datetime'],
				[['active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDuser' => 'Personne',
				'IDholon' => 'Holon',
				'focus' => 'Focus',
				'time_budget_hours' => 'Budget temps',
				'time_budget_recurrence' => 'Recurrence du budget temps',
				'money_budget' => 'Budget argent',
				'money_budget_recurrence' => 'Recurrence du budget argent',
				'assignment_review_date' => 'Date limite d affectation',
				'parameters' => 'Paramètres',
				'datecreation' => 'Création',
				'dateconnexion' => 'Dernière connexion',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'focus' => 'Specificite de la participation de cette personne dans ce holon.',
				'time_budget_hours' => 'Temps prevu pour cette affectation, exprime en heures.',
				'money_budget' => 'Montant prevu pour cette affectation.',
				'assignment_review_date' => 'Date a laquelle l affectation doit etre reconfirmee ou arretee.',
			];
		}

		public static function attributeLength()
		{
			return [
				'focus' => 250,
				'time_budget_recurrence' => 10,
				'money_budget_recurrence' => 10,
			];
		}

		public static function getBudgetRecurrences()
		{
			return array(
				self::BUDGET_RECURRENCE_DAY,
				self::BUDGET_RECURRENCE_WEEK,
				self::BUDGET_RECURRENCE_MONTH,
				self::BUDGET_RECURRENCE_YEAR,
			);
		}

		public static function normalizeBudgetRecurrence($value)
		{
			$value = trim((string)$value);
			return in_array($value, self::getBudgetRecurrences(), true) ? $value : '';
		}

		public static function parseBudgetAmount($value, $maximum = 9999999999.99)
		{
			if (!is_scalar($value)) {
				return array('valid' => false, 'value' => null);
			}

			$value = trim((string)$value);
			if ($value === '') {
				return array('valid' => true, 'value' => null);
			}

			$value = str_replace(array(' ', ','), array('', '.'), $value);
			if (!is_numeric($value)) {
				return array('valid' => false, 'value' => null);
			}

			$amount = (float)$value;
			if ($amount < 0 || $amount > (float)$maximum) {
				return array('valid' => false, 'value' => null);
			}

			return array('valid' => true, 'value' => number_format($amount, 2, '.', ''));
		}

		public static function parseAssignmentReviewDate($value)
		{
			if (!is_scalar($value)) {
				return array('valid' => false, 'value' => null);
			}

			$value = trim((string)$value);
			if ($value === '') {
				return array('valid' => true, 'value' => null);
			}

			$date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
			$errors = \DateTimeImmutable::getLastErrors();
			if (!$date instanceof \DateTimeImmutable || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
				return array('valid' => false, 'value' => null);
			}

			return array('valid' => true, 'value' => $date->format('Y-m-d'));
		}

		public function updateAssignmentDetails(array $details)
		{
			$focus = trim((string)($details['focus'] ?? ''));
			$focusMaximumLength = (int)(self::attributeLength()['focus'] ?? 250);
			if (mb_strlen($focus, 'UTF-8') > $focusMaximumLength) {
				return array('status' => false, 'reason' => 'focus_too_long');
			}

			$timeBudget = self::parseBudgetAmount($details['time_budget_hours'] ?? null);
			if (!$timeBudget['valid']) {
				return array('status' => false, 'reason' => 'invalid_time_budget');
			}

			$moneyBudget = self::parseBudgetAmount($details['money_budget'] ?? null);
			if (!$moneyBudget['valid']) {
				return array('status' => false, 'reason' => 'invalid_money_budget');
			}

			$assignmentReviewDate = self::parseAssignmentReviewDate($details['assignment_review_date'] ?? null);
			if (!$assignmentReviewDate['valid']) {
				return array('status' => false, 'reason' => 'invalid_assignment_review_date');
			}

			$timeRecurrence = self::normalizeBudgetRecurrence($details['time_budget_recurrence'] ?? '');
			if ($timeBudget['value'] !== null && $timeRecurrence === '') {
				return array('status' => false, 'reason' => 'invalid_time_recurrence');
			}

			$moneyRecurrence = self::normalizeBudgetRecurrence($details['money_budget_recurrence'] ?? '');
			if ($moneyBudget['value'] !== null && $moneyRecurrence === '') {
				return array('status' => false, 'reason' => 'invalid_money_recurrence');
			}

			$this->set('focus', $focus !== '' ? $focus : null);
			$this->set('time_budget_hours', $timeBudget['value']);
			$this->set('time_budget_recurrence', $timeBudget['value'] !== null ? $timeRecurrence : null);
			$this->set('money_budget', $moneyBudget['value']);
			$this->set('money_budget_recurrence', $moneyBudget['value'] !== null ? $moneyRecurrence : null);
			$this->set('assignment_review_date', $assignmentReviewDate['value']);

			return array('status' => $this->save(), 'reason' => 'save_failed');
		}

		protected function loadScopedMembership($organizationId = 0)
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$this->get('IDuser');
			if ($organizationId <= 0 || $userId <= 0) {
				return null;
			}

			if (array_key_exists($organizationId, $this->_scopedMembershipCache)) {
				return $this->_scopedMembershipCache[$organizationId];
			}

			$membership = new \dbObject\UserOrganization();
			$this->_scopedMembershipCache[$organizationId] = $membership->load([
				['IDuser', $userId],
				['IDorganization', $organizationId],
			]) ? $membership : null;

			return $this->_scopedMembershipCache[$organizationId];
		}

		protected function loadLinkedUser()
		{
			if ($this->_linkedUserCache instanceof \dbObject\User) {
				return $this->_linkedUserCache;
			}

			$user = $this->get('user');
			if ($user && (int)$user->getId() > 0) {
				$this->_linkedUserCache = $user;
				return $this->_linkedUserCache;
			}

			$userId = (int)$this->get('IDuser');
			if ($userId <= 0) {
				return null;
			}

			$user = new \dbObject\User();
			$this->_linkedUserCache = $user->load($userId) ? $user : null;
			return $this->_linkedUserCache;
		}

		public function getUserDisplayName($organizationId = 0)
		{
			$membership = $this->loadScopedMembership($organizationId);
			if ($membership) {
				return $membership->getUserDisplayName();
			}

			$user = $this->loadLinkedUser();
			if (!$user) {
				return 'Profil';
			}

			$firstname = trim((string)$user->get('firstname'));
			$lastname = trim((string)$user->get('lastname'));
			$fullName = trim($firstname . ' ' . $lastname);
			if ($fullName !== '') {
				return $fullName;
			}

			$username = trim((string)$user->get('username'));
			if ($username !== '') {
				return $username;
			}

			$email = trim((string)$user->get('email'));
			if ($email !== '') {
				return $email;
			}

			return 'Profil';
		}

		public function getAvatarSeedLabel($organizationId = 0)
		{
			$membership = $this->loadScopedMembership($organizationId);
			if ($membership) {
				return \commonBuildAvatarSeedLabel(
					$membership->getUserDisplayName(),
					$membership->getScopedEmail()
				);
			}

			$user = $this->loadLinkedUser();
			if (!$user) {
				return \commonBuildAvatarSeedLabel($this->getUserDisplayName($organizationId), '');
			}

			return \commonBuildAvatarSeedLabel(
				$this->getUserDisplayName($organizationId),
				trim((string)$user->get('email'))
			);
		}

		public function getUserInitials($organizationId = 0)
		{
			$membership = $this->loadScopedMembership($organizationId);
			if ($membership) {
				return $membership->getUserInitials();
			}

			$label = $this->getUserDisplayName($organizationId);
			$words = preg_split('/\s+/u', $label) ?: [];
			$initials = '';

			foreach ($words as $word) {
				$word = trim((string)$word);
				if ($word === '') {
					continue;
				}

				$initials .= mb_substr($word, 0, 1, 'UTF-8');
				if (mb_strlen($initials, 'UTF-8') >= 2) {
					break;
				}
			}

			if ($initials === '') {
				$initials = mb_substr($label, 0, 1, 'UTF-8');
			}

			return mb_strtoupper($initials !== '' ? $initials : 'P', 'UTF-8');
		}

		public function getProfilePhotoUrl($organizationId = 0)
		{
			$membership = $this->loadScopedMembership($organizationId);
			if ($membership) {
				return $membership->getProfilePhotoUrl();
			}

			$user = $this->loadLinkedUser();
			if ($user && method_exists($user, 'getProfilePhotoUrl')) {
				return (string)$user->getProfilePhotoUrl();
			}

			return '';
		}

		public function isHolonAdmin()
		{
			return (bool)$this->getParameter('isAdmin');
		}

		public function setHolonAdmin($isAdmin)
		{
			$parameters = json_decode((string)$this->get('parameters'), true);
			if (!is_array($parameters)) {
				$parameters = array();
			}

			if ($isAdmin) {
				$parameters['isAdmin'] = true;
			} else {
				unset($parameters['isAdmin']);
			}

			$this->set('parameters', $parameters);
			return $this->save();
		}

		public static function fetchEffectiveRowsForUserAndHolonIds($userId, array $holonIds)
		{
			$userId = (int)$userId;
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));

			if ($userId <= 0 || count($holonIds) === 0) {
				return array();
			}

			$params = array(
				'user_id' => $userId,
			);
			$placeholders = array();
			foreach ($holonIds as $index => $holonId) {
				$placeholder = 'holon_' . $index;
				$placeholders[] = ':' . $placeholder;
				$params[$placeholder] = $holonId;
			}

			$query = "
				SELECT DISTINCT
					uh.IDholon,
					uh.active AS holon_active,
					uh.active AS holon_effective_active,
					uh.parameters
				FROM user_holon uh
				WHERE uh.IDuser = :user_id
				  AND uh.IDholon IN (" . implode(', ', $placeholders) . ")
				  AND uh.active = 1
				ORDER BY uh.IDholon ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			if ($rows === false || !is_array($rows)) {
				return array();
			}

			foreach ($rows as &$row) {
				$parameters = json_decode((string)($row['parameters'] ?? ''), true);
				$row['is_admin'] = is_array($parameters) && !empty($parameters['isAdmin']);
				unset($row['parameters']);
			}
			unset($row);

			return $rows;
		}

		public static function fetchStructureRowsForHolonIds($organizationId, array $holonIds)
		{
			$organizationId = (int)$organizationId;
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), static function ($holonId) {
				return $holonId > 0;
			})));

			if ($organizationId <= 0 || count($holonIds) === 0) {
				return array();
			}

			$params = array(
				'organization_id' => $organizationId,
				'invitation_organization_id' => $organizationId,
			);
			$placeholders = array();
			foreach ($holonIds as $index => $holonId) {
				$key = 'structure_holon_' . $index;
				$params[$key] = $holonId;
				$placeholders[] = ':' . $key;
			}

			$query = "SELECT DISTINCT
					uh.IDholon,
					uh.IDuser,
					uh.active,
					uh.parameters
				FROM user_holon uh
				INNER JOIN `user` u ON u.id = uh.IDuser
				LEFT JOIN user_organization uo
					ON uo.IDuser = uh.IDuser
					AND uo.IDorganization = :organization_id
				LEFT JOIN invitation inv
					ON inv.IDorganization = :invitation_organization_id
					AND inv.IDuser = uh.IDuser
					AND inv.status = 'pending'
					AND inv.active = 1
					AND (inv.dateexpiration IS NULL OR inv.dateexpiration > NOW())
				WHERE uh.IDholon IN (" . implode(', ', $placeholders) . ")
				  AND (
					uh.active = 1
					OR inv.id IS NOT NULL
					OR (uo.id IS NOT NULL AND uo.active = 0)
				  )
				ORDER BY
					COALESCE(NULLIF(u.lastname, ''), NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					COALESCE(NULLIF(u.firstname, ''), NULLIF(u.username, ''), u.email) ASC,
					u.id ASC,
					uh.IDholon ASC";

			$rows = self::fetchAll($query, $params);
			return is_array($rows) ? $rows : array();
		}

		public static function fetchRawRowsForUserAndHolonIds($userId, array $holonIds)
		{
			$userId = (int)$userId;
			$holonIds = array_values(array_unique(array_filter(array_map('intval', $holonIds), function ($holonId) {
				return $holonId > 0;
			})));

			if ($userId <= 0 || count($holonIds) === 0) {
				return array();
			}

			$params = array(
				'user_id' => $userId,
			);
			$placeholders = array();
			foreach ($holonIds as $index => $holonId) {
				$placeholder = 'holon_' . $index;
				$placeholders[] = ':' . $placeholder;
				$params[$placeholder] = $holonId;
			}

			$query = "
				SELECT
					id,
					IDholon,
					active,
					parameters,
					datecreation,
					dateconnexion
				FROM user_holon
				WHERE IDuser = :user_id
				  AND IDholon IN (" . implode(', ', $placeholders) . ")
				ORDER BY IDholon ASC, id ASC
			";

			$rows = \dbObject\DbObject::fetchAll($query, $params);
			return $rows !== false ? $rows : array();
		}
	}

?>
