<?php
	namespace dbObject;

	class Invitation extends DbObject
	{
    protected static function uiMessageSourceLang(): array
    {
        return parent::uiMessageSourceLang() + [
            'copy.le_jeton_d_invitation_n_a_pas_pu_etre_genere' => ['text' => 'Le jeton d\'invitation n\'a pas pu être généré.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_invitation_demandee_est_invalide' => ['text' => 'L\'invitation demandée est invalide.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.le_statut_admin_demande_n_a_pas_pu_etre_memorise' => ['text' => 'Le statut admin demande n\'a pas pu être mémorisé.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_invitation_n_a_pas_pu_etre_creee' => ['text' => 'L\'invitation n\'a pas pu être créée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_organisation_invitee_est_introuvable' => ['text' => 'L\'organisation invitée est introuvable.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.les_contraintes_d_admins_ne_sont_pas_respectees' => ['text' => 'Les contraintes d’admins ne sont pas respectées.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.le_statut_admin_n_a_pas_pu_etre_applique' => ['text' => 'Le statut admin n\'a pas pu être applique.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_invitation_ne_peut_pas_etre_envoyee' => ['text' => 'L\'invitation ne peut pas être envoyée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.acces_en_attente' => ['text' => 'Accès en attente', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.vous_etes_invite_a_rejoindre_cette_organisation' => ['text' => 'Vous êtes invite a rejoindre cette organisation.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.ouvrez_ce_lien_pour_accepter_ou_refuser_l_invitation' => ['text' => 'Ouvrez ce lien pour accepter ou refuser l’invitation.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_invitation_n_a_pas_pu_etre_envoyee' => ['text' => 'L\'invitation n\'a pas pu être envoyée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.nouvelle_demande_d_acces' => ['text' => 'Nouvelle demande d’accès', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.demande_d_acces_a_organizationname' => ['text' => 'Demande d’accès a {organizationName}', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.la_demande_n_a_pas_pu_etre_envoyee_aux_administrateurs' => ['text' => 'La demande n\'a pas pu être envoyée aux administrateurs.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.la_confirmation_ne_peut_pas_etre_envoyee' => ['text' => 'La confirmation ne peut pas être envoyée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.votre_inscription_est_validee' => ['text' => 'Votre inscription est validée', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.votre_demande_pour_rejoindre_value_a_ete_acceptee' => ['text' => 'Votre demande pour rejoindre {value} a été acceptée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.vous_pouvez_maintenant_acceder_a_cet_espace_et_a_ses_outils' => ['text' => 'Vous pouvez maintenant accéder à cet espace et à ses outils.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.entrer_dans_l_organisation' => ['text' => 'Entrer dans l’organisation', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.inscription_validee_dans_organizationname' => ['text' => 'Inscription validée dans {organizationName}', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_e_mail_de_confirmation_n_a_pas_pu_etre_envoye' => ['text' => 'L\'e-mail de confirmation n\'a pas pu être envoyé.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.la_connexion_a_la_base_de_donnees_est_indisponible' => ['text' => 'La connexion à la base de données est indisponible.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.le_profil_utilisateur_n_a_pas_pu_etre_active' => ['text' => 'Le profil utilisateur n\'a pas pu être active.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_adhesion_a_l_organisation_n_a_pas_pu_etre_confirmee' => ['text' => 'L\'adhésion à l\'organisation n\'a pas pu être confirmée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.un_lien_de_contexte_n_a_pas_pu_etre_active' => ['text' => 'Un lien de contexte n\'a pas pu être active.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.l_invitation_n_a_pas_pu_etre_mise_a_jour' => ['text' => 'L\'invitation n\'a pas pu être mise à jour.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.demande_validee' => ['text' => 'Demande validée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.le_retrait_de_l_invitation_n_a_pas_pu_etre_finalise' => ['text' => 'Le retrait de l\'invitation n\'a pas pu être finalisé.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
            'copy.invitation_refusee' => ['text' => 'Invitation refusée.', 'context' => 'Interface text in class/dbobject/invitation.class.php'],
        ];
    }

		const REQUEST_ORIGIN_ADMIN = 'admin';
		const REQUEST_ORIGIN_MEMBER = 'member';

		public static function tableName()
		{
			return 'invitation';
		}

		public static function rules()
		{
			return [
				[['id'], 'integer'],
				[['IDorganization', 'IDuser', 'IDuser_sender'], 'fk'],
				[['email', 'token', 'status', 'request_origin'], 'string'],
				[['parameters'], 'parameters'],
				[['datecreation', 'dateexpiration', 'dateresponse'], 'datetime'],
				[['active'], 'boolean'],
				[['id', 'datecreation', 'dateresponse'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDuser' => 'Utilisateur invite',
				'IDuser_sender' => 'Initiateur',
				'email' => 'E-mail',
				'token' => 'Jeton',
				'request_origin' => 'Origine de la demande',
				'status' => 'Statut',
				'parameters' => 'Paramètres',
				'datecreation' => 'Création',
				'dateexpiration' => 'Expiration',
				'dateresponse' => 'Réponse',
				'active' => 'Actif',
			];
		}

		public static function attributeLength()
		{
			return [
				'email' => 250,
				'token' => 64,
				'request_origin' => 20,
				'status' => 20,
			];
		}

		public static function getOrder()
		{
			return 'datecreation DESC, id DESC';
		}

		public static function normalizeRequestOrigin($value)
		{
			$value = trim(mb_strtolower((string)$value, 'UTF-8'));
			if ($value === self::REQUEST_ORIGIN_MEMBER) {
				return self::REQUEST_ORIGIN_MEMBER;
			}

			return self::REQUEST_ORIGIN_ADMIN;
		}

		public static function findPendingForOrganizationUser($organizationId, $userId)
		{
			$query = "
				SELECT *
				FROM invitation
				WHERE IDorganization = :organization_id
				  AND IDuser = :user_id
				  AND status = 'pending'
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				ORDER BY id DESC
				LIMIT 1
			";

			$row = self::fetchRow($query, [
				'organization_id' => (int)$organizationId,
				'user_id' => (int)$userId,
			]);

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function getPendingAdminUserIdsForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			if ($organizationId <= 0) {
				return array();
			}

			$rows = self::fetchAll(
				"SELECT DISTINCT IDuser
				 FROM invitation
				 WHERE IDorganization = :organization_id
				   AND status = 'pending'
				   AND active = 1
				   AND (dateexpiration IS NULL OR dateexpiration > NOW())
				   AND (request_origin IS NULL OR request_origin != :request_origin_member)",
				array(
					'organization_id' => $organizationId,
					'request_origin_member' => self::REQUEST_ORIGIN_MEMBER,
				)
			);
			if ($rows === false) {
				return array();
			}

			$userIds = array();
			foreach ($rows as $row) {
				$userId = (int)($row['IDuser'] ?? 0);
				if ($userId > 0) {
					$userIds[$userId] = $userId;
				}
			}

			return $userIds;
		}

		public static function countPendingRequestedHolonAdmins($organizationId, $holonId, $excludeUserId = 0)
		{
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;
			$excludeUserId = (int)$excludeUserId;
			if ($organizationId <= 0 || $holonId <= 0) {
				return 0;
			}

			$rows = self::fetchAll(
				"SELECT `IDuser`, `parameters`
				 FROM `invitation`
				 WHERE `IDorganization` = :organization_id
				   AND `status` = 'pending'
				   AND `active` = 1
				   AND (`dateexpiration` IS NULL OR `dateexpiration` > NOW())",
				array('organization_id' => $organizationId)
			);
			if ($rows === false) {
				return 0;
			}

			$userIds = array();
			foreach ($rows as $row) {
				$userId = (int)($row['IDuser'] ?? 0);
				if ($userId <= 0 || $userId === $excludeUserId) {
					continue;
				}

				$parameters = json_decode((string)($row['parameters'] ?? ''), true);
				if (!is_array($parameters)) {
					continue;
				}

				foreach ((array)($parameters['holon_admin_ids'] ?? array()) as $requestedHolonId) {
					if ((int)$requestedHolonId === $holonId) {
						$userIds[$userId] = $userId;
						break;
					}
				}
			}

			return count($userIds);
		}

		public static function findPendingForUser($userId)
		{
			$userId = (int)$userId;
			if ($userId <= 0) {
				return [];
			}

			$query = "
				SELECT inv.id
				FROM invitation inv
				LEFT JOIN user_organization uo
					ON uo.IDuser = inv.IDuser
					AND uo.IDorganization = inv.IDorganization
					AND uo.active = 1
				WHERE inv.IDuser = :user_id
				  AND inv.status = 'pending'
				  AND inv.active = 1
				  AND (inv.dateexpiration IS NULL OR inv.dateexpiration > NOW())
				  AND (inv.request_origin IS NULL OR inv.request_origin != :request_origin_member)
				  AND uo.id IS NULL
				ORDER BY inv.datecreation DESC, inv.id DESC
			";

			$rows = self::fetchAll($query, [
				'user_id' => $userId,
				'request_origin_member' => self::REQUEST_ORIGIN_MEMBER,
			]);
			if ($rows === false) {
				return [];
			}

			$itemsByOrganizationId = [];
			foreach ($rows as $row) {
				$item = new self();
				if (!$item->load((int)($row['id'] ?? 0))) {
					continue;
				}

				$organizationId = (int)$item->get('IDorganization');
				if ($organizationId <= 0 || isset($itemsByOrganizationId[$organizationId])) {
					continue;
				}

				$itemsByOrganizationId[$organizationId] = $item;
			}

			return array_values($itemsByOrganizationId);
		}

		public static function findByToken($token)
		{
			$query = "
				SELECT *
				FROM invitation
				WHERE token = :token
				ORDER BY id DESC
				LIMIT 1
			";

			$row = self::fetchRow($query, ['token' => trim((string)$token)]);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findValidByToken($token)
		{
			$query = "
				SELECT *
				FROM invitation
				WHERE token = :token
				  AND status = 'pending'
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				ORDER BY id DESC
				LIMIT 1
			";

			$row = self::fetchRow($query, ['token' => trim((string)$token)]);
			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		protected static function generateToken()
		{
			for ($attempt = 0; $attempt < 5; $attempt += 1) {
				$token = bin2hex(random_bytes(32));
				if (!self::findByToken($token)) {
					return $token;
				}
			}

			throw new \RuntimeException(self::uiMessage('copy.le_jeton_d_invitation_n_a_pas_pu_etre_genere'));
		}

		public static function issue($organizationId, $userId, $senderUserId = 0, $email = '', array $options = [])
		{
			$organizationId = (int)$organizationId;
			$userId = (int)$userId;
			$senderUserId = (int)$senderUserId;
			$email = trim(mb_strtolower((string)$email, 'UTF-8'));
			$requestOrigin = self::normalizeRequestOrigin($options['requestOrigin'] ?? self::REQUEST_ORIGIN_ADMIN);
			$requestMessage = trim((string)($options['requestMessage'] ?? ''));
			$holonAdminId = (int)($options['holonAdminId'] ?? 0);

			if ($organizationId <= 0 || $userId <= 0) {
				throw new \RuntimeException(self::uiMessage('copy.l_invitation_demandee_est_invalide'));
			}

			$existing = self::findPendingForOrganizationUser($organizationId, $userId);
			if ($existing) {
				if ($holonAdminId > 0) {
					$requestedAdminResult = $existing->addRequestedHolonAdmin($holonAdminId);
					if (!is_array($requestedAdminResult) || empty($requestedAdminResult['status'])) {
						throw new \RuntimeException(self::uiMessage('copy.le_statut_admin_demande_n_a_pas_pu_etre_memorise'));
					}
				}
				if ($requestOrigin === self::REQUEST_ORIGIN_MEMBER && $existing->isMemberInitiatedRequest() && $requestMessage !== '') {
					$parameters = $existing->get('parameters');
					if (!is_array($parameters)) {
						$decodedParameters = json_decode((string)$parameters, true);
						$parameters = is_array($decodedParameters) ? $decodedParameters : [];
					}
					$parameters['request_message'] = $requestMessage;
					$existing->set('parameters', $parameters);
					$existing->save();
				}

				return [
					'invitation' => $existing,
					'created' => false,
				];
			}

			$item = new self();
			$item->set('IDorganization', $organizationId);
			$item->set('IDuser', $userId);
			$item->set('IDuser_sender', $senderUserId > 0 ? $senderUserId : null);
			$item->set('email', $email !== '' ? $email : null);
			$item->set('token', self::generateToken());
			$item->set('request_origin', $requestOrigin);
			$item->set('status', 'pending');
			$item->set('dateexpiration', new \DateTime('+14 days'));
			$item->set('active', true);

			$parameters = $item->get('parameters');
			if (!is_array($parameters)) {
				$parameters = [];
			}
			if ($requestMessage !== '') {
				$parameters['request_message'] = $requestMessage;
			}
			if ($holonAdminId > 0) {
				$parameters['holon_admin_ids'] = array($holonAdminId);
			}
			$item->set('parameters', $parameters);

			$saveResult = $item->save();
			if (!is_array($saveResult) || empty($saveResult['status']) || (int)$item->getId() <= 0) {
				throw new \RuntimeException(self::uiMessage('copy.l_invitation_n_a_pas_pu_etre_creee'));
			}

			return [
				'invitation' => $item,
				'created' => true,
			];
		}

		public function isPending()
		{
			return (string)$this->get('status') === 'pending' && (bool)$this->get('active');
		}

		public function isExpired()
		{
			$expiration = $this->get('dateexpiration');
			return $expiration instanceof \DateTimeInterface && $expiration <= new \DateTime();
		}

		public function getRequestOrigin()
		{
			return self::normalizeRequestOrigin($this->get('request_origin'));
		}

		public function isMemberInitiatedRequest()
		{
			return $this->getRequestOrigin() === self::REQUEST_ORIGIN_MEMBER;
		}

		public function isAdminInitiatedInvitation()
		{
			return !$this->isMemberInitiatedRequest();
		}

		public function getRequestMessage()
		{
			$message = $this->getParameter('request_message');
			if ($message !== null && trim((string)$message) !== '') {
				return trim((string)$message);
			}

			$parameters = $this->get('parameters');
			if (is_string($parameters) && trim($parameters) !== '') {
				$decoded = json_decode($parameters, true);
				if (is_array($decoded)) {
					return trim((string)($decoded['request_message'] ?? ''));
				}
			}

			if (is_array($parameters)) {
				return trim((string)($parameters['request_message'] ?? ''));
			}

			return '';
		}

		public function addRequestedHolonAdmin($holonId)
		{
			$holonId = (int)$holonId;
			if ($holonId <= 0) {
				return array(
					'status' => false,
					'message' => \dbObject\Organization::formatLexiconText('Le holon admin demande est invalide.'),
				);
			}

			$parameters = $this->get('parameters');
			if (!is_array($parameters)) {
				$decodedParameters = json_decode((string)$parameters, true);
				$parameters = is_array($decodedParameters) ? $decodedParameters : array();
			}

			$holonIds = array();
			foreach ((array)($parameters['holon_admin_ids'] ?? array()) as $requestedHolonId) {
				$requestedHolonId = (int)$requestedHolonId;
				if ($requestedHolonId > 0 && !in_array($requestedHolonId, $holonIds, true)) {
					$holonIds[] = $requestedHolonId;
				}
			}

			if (!in_array($holonId, $holonIds, true)) {
				$holonIds[] = $holonId;
			}

			$parameters['holon_admin_ids'] = array_values($holonIds);
			$this->set('parameters', $parameters);
			$saveResult = $this->save();
			return is_array($saveResult) ? $saveResult : array('status' => false);
		}

		protected function applyRequestedHolonAdmins($userId, $organizationId, $rootHolonId)
		{
			$parameters = $this->get('parameters');
			if (!is_array($parameters)) {
				$decodedParameters = json_decode((string)$parameters, true);
				$parameters = is_array($decodedParameters) ? $decodedParameters : array();
			}

			$organization = $this->getOrganizationObject();
			if (!$organization) {
				throw new \RuntimeException(self::uiMessage('copy.l_organisation_invitee_est_introuvable'));
			}

			foreach ((array)($parameters['holon_admin_ids'] ?? array()) as $requestedHolonId) {
				$requestedHolonId = (int)$requestedHolonId;
				if ($requestedHolonId <= 0) {
					continue;
				}

				$holon = new \dbObject\Holon();
				if (
					!$holon->load($requestedHolonId)
					|| !(bool)$holon->get('active')
					|| ((int)$holon->getId() !== (int)$rootHolonId && !$holon->isDescendantOf((int)$rootHolonId))
				) {
					continue;
				}

				if ($holon->isOrganizationHolon()) {
					$constraintResult = $holon->validateMemberAdditionAdminBounds((int)$userId, true, array(
						'organizationId' => (int)$organizationId,
					));
					if (empty($constraintResult['status'])) {
						throw new \RuntimeException((string)($constraintResult['message'] ?? self::uiMessage('copy.les_contraintes_d_admins_ne_sont_pas_respectees')));
					}

					$membership = new \dbObject\UserOrganization();
					if (!$membership->load([
						['IDuser', (int)$userId],
						['IDorganization', (int)$organizationId],
					])) {
						continue;
					}

					$saveResult = $membership->setOrganizationAdmin(true);
				} else {
					$constraintResult = $holon->validateMemberAdditionAdminBounds((int)$userId, true, array(
						'organizationId' => (int)$organizationId,
					));
					if (empty($constraintResult['status'])) {
						throw new \RuntimeException((string)($constraintResult['message'] ?? self::uiMessage('copy.les_contraintes_d_admins_ne_sont_pas_respectees')));
					}

					$link = new \dbObject\UserHolon();
					if (!$link->load([
						['IDuser', (int)$userId],
						['IDholon', $requestedHolonId],
					])) {
						$link->set('IDuser', (int)$userId);
						$link->set('IDholon', $requestedHolonId);
					}
					$link->set('active', true);
					$saveResult = $link->setHolonAdmin(true);
				}

				if (!is_array($saveResult) || empty($saveResult['status'])) {
					throw new \RuntimeException(self::uiMessage('copy.le_statut_admin_n_a_pas_pu_etre_applique'));
				}
			}
		}

		protected function getOrganizationObject()
		{
			$organization = new \dbObject\Organization();
			return $organization->load((int)$this->get('IDorganization')) ? $organization : null;
		}

		protected function getInvitedUserObject()
		{
			$user = new \dbObject\User();
			return $user->load((int)$this->get('IDuser')) ? $user : null;
		}

		protected function getOrganizationRootHolonId()
		{
			$organization = $this->getOrganizationObject();
			if (!$organization) {
				return 0;
			}

			$rootHolon = $organization->getStructuralRootHolon();
			return $rootHolon ? (int)$rootHolon->getId() : 0;
		}

		protected function getOrganizationBaseUrl($path = '/omo/')
		{
			$organization = $this->getOrganizationObject();
			if (!$organization) {
				return commonBuildUrl($path);
			}

			$targetHost = commonGetRequestHost();
			$shortname = trim((string)$organization->get('shortname'));
			if (commonUseOrganizationSubdomains() && $shortname !== '') {
				$builtHost = commonBuildOrganizationHost($shortname, commonGetRootHost($targetHost));
				if (trim((string)$builtHost) !== '') {
					$targetHost = $builtHost;
				}
			} else {
				$targetHost = commonGetRootHost($targetHost);
			}

			return commonBuildUrl($path, $targetHost);
		}

		public function getInvitationUrl()
		{
			return $this->getOrganizationBaseUrl('/common/invitation.php?token=' . rawurlencode((string)$this->get('token')));
		}

		public function getApprovalUrl()
		{
			return $this->getInvitationUrl();
		}

		public function getPendingHolons()
		{
			$holons = [];
			$organization = $this->getOrganizationObject();
			$rootHolonId = (int)$this->getOrganizationRootHolonId();
			$hasActiveOrganizationMembership = (int)self::fetchValue(
				"SELECT COUNT(*) FROM user_organization WHERE IDuser = :user_id AND IDorganization = :organization_id AND active = 1",
				[
					'user_id' => (int)$this->get('IDuser'),
					'organization_id' => (int)$this->get('IDorganization'),
				]
			) > 0;
			$hasInactiveOrganizationMembership = (int)self::fetchValue(
				"SELECT COUNT(*) FROM user_organization WHERE IDuser = :user_id AND IDorganization = :organization_id AND active = 0",
				[
					'user_id' => (int)$this->get('IDuser'),
					'organization_id' => (int)$this->get('IDorganization'),
				]
			) > 0;
			if ($organization && !$hasActiveOrganizationMembership && $hasInactiveOrganizationMembership) {
				$holons[] = [
					'id' => 0,
					'name' => trim((string)$organization->get('name')),
					'typeLabel' => 'Organisation',
				];
			}

			foreach ($this->getScopedUserHolonLinks(false) as $link) {
				$holon = new \dbObject\Holon();
				if (!$holon->load((int)$link->get('IDholon'))) {
					continue;
				}
				if ((int)$holon->getId() === $rootHolonId) {
					continue;
				}
				if (!(bool)$holon->get('active') || !(bool)$holon->get('visible')) {
					continue;
				}
				$holons[] = [
					'id' => (int)$holon->getId(),
					'name' => $holon->getDisplayName(),
					'typeLabel' => $holon->getTemplateLabel(true),
				];
			}

			return $holons;
		}

		protected function getScopedUserHolonLinks($activeFilter = null)
		{
			$userId = (int)$this->get('IDuser');
			$rootHolonId = (int)$this->getOrganizationRootHolonId();

			if ($userId <= 0 || $rootHolonId <= 0) {
				return [];
			}

			$query = "
				SELECT id
				FROM user_holon
				WHERE IDuser = :user_id
				  AND is_membership = 1
			";
			$params = [
				'user_id' => $userId,
			];

			if ($activeFilter === true) {
				$query .= " AND active = 1";
			} elseif ($activeFilter === false) {
				$query .= " AND active = 0";
			}

			$query .= " ORDER BY id ASC";

			$rows = self::fetchAll($query, $params);
			if ($rows === false) {
				return [];
			}

			$links = [];
			foreach ($rows as $row) {
				$link = new \dbObject\UserHolon();
				if (!$link->load((int)$row['id'])) {
					continue;
				}

				$holon = new \dbObject\Holon();
				if (!$holon->load((int)$link->get('IDholon'))) {
					continue;
				}

				if (!$holon->isDescendantOf($rootHolonId)) {
					continue;
				}

				$links[] = $link;
			}

			return $links;
		}

		protected function getOrganizationAdminRecipients()
		{
			$organizationId = (int)$this->get('IDorganization');
			if ($organizationId <= 0) {
				return [];
			}

			$memberships = new \dbObject\ArrayUserOrganization();
			$memberships->loadActiveForOrganization($organizationId);

			$recipients = [];
			$seenEmails = [];
			foreach ($memberships as $membership) {
				if (!($membership instanceof \dbObject\UserOrganization) || !$membership->isOrganizationAdmin()) {
					continue;
				}

				$email = trim(mb_strtolower((string)$membership->getScopedEmail(), 'UTF-8'));
				if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seenEmails[$email])) {
					continue;
				}

				$seenEmails[$email] = true;
				$recipients[] = [
					'email' => $email,
					'name' => trim((string)$membership->getUserDisplayName()),
				];
			}

			return $recipients;
		}

		protected function sendAdminInvitationEmail()
		{
			require_once dirname(__DIR__, 2) . '/common/email_layout.php';

			$organization = $this->getOrganizationObject();
			$user = $this->getInvitedUserObject();

			if (!$organization || !$user) {
				throw new \RuntimeException(self::uiMessage('copy.l_invitation_ne_peut_pas_etre_envoyee'));
			}

			$email = trim((string)$this->get('email'));
			if ($email === '') {
				$email = trim((string)$user->get('email'));
			}

			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \RuntimeException("L'adresse e-mail d'invitation est invalide.");
			}

			$organizationName = trim((string)$organization->get('name'));
			if ($organizationName === '') {
				$organizationName = 'cette organisation';
			}

			$holons = $this->getPendingHolons();
			$detailsHtml = '';
			if (count($holons) > 0) {
				$detailsHtml .= ('<div style="font-weight:700; margin:0 0 10px;">' . self::uiMessage('copy.acces_en_attente') . '</div>');
				$detailsHtml .= '<ul style="margin:0; padding-left:18px; color:#334155;">';
				foreach ($holons as $holon) {
					$detailsHtml .= '<li style="margin:0 0 8px;">'
						. '<strong>' . \commonMailEscape((string)$holon['name']) . '</strong>'
						. ' <span style="color:#64748b;">(' . \commonMailEscape((string)$holon['typeLabel']) . ')</span>'
						. '</li>';
				}
				$detailsHtml .= '</ul>';
			}

			$message = \commonRenderMailLayout([
				'brand_name' => $organizationName,
				'brand_color' => trim((string)$organization->get('color')),
				'logo_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
				'banner_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
				'heading' => 'Invitation a rejoindre ' . $organizationName,
				'intro_html' => ('<p style="margin:0 0 14px; color:#475569; line-height:1.7;">' . self::uiMessage('copy.vous_etes_invite_a_rejoindre_cette_organisation') . '</p>'),
				'body_html' => ('<p style="margin:0; color:#475569; line-height:1.7;">' . self::uiMessage('copy.ouvrez_ce_lien_pour_accepter_ou_refuser_l_invitation') . '</p>'),
				'details_html' => $detailsHtml,
				'button_label' => 'Consulter l’invitation',
				'button_url' => $this->getInvitationUrl(),
				'footer_html' => '<p style="margin:0;">Ce lien reste valable jusqu au '
					. \commonMailEscape(($this->get('dateexpiration') instanceof \DateTimeInterface ? $this->get('dateexpiration')->format('d.m.Y H:i') : 'prochaines semaines'))
					. '.</p>',
			]);

			$fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
			if ($fromAddress === '') {
				$host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
				$fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
			}

			if (!myHTMLMail([$fromAddress, (string)$organization->get('name')], $email, 'Invitation a rejoindre ' . $organizationName, $message, organizationId: (int)$organization->getId())) {
				throw new \RuntimeException(self::uiMessage('copy.l_invitation_n_a_pas_pu_etre_envoyee'));
			}

			return true;
		}

		protected function sendMembershipRequestEmail()
		{
			require_once dirname(__DIR__, 2) . '/common/email_layout.php';

			$organization = $this->getOrganizationObject();
			$user = $this->getInvitedUserObject();
			$recipients = $this->getOrganizationAdminRecipients();

			if (!$organization || !$user || count($recipients) === 0) {
				throw new \RuntimeException("Aucun administrateur ne peut recevoir cette demande pour le moment.");
			}

			$requesterLabel = trim((string)$user->getScopedDisplayName((int)$organization->getId()));
			if ($requesterLabel === '') {
				$requesterLabel = trim((string)$user->get('email'));
			}
			if ($requesterLabel === '') {
				$requesterLabel = 'Utilisateur ' . (int)$user->getId();
			}

			$requesterEmail = trim((string)$this->get('email'));
			if ($requesterEmail === '') {
				$requesterEmail = trim((string)$user->get('email'));
			}

			$organizationName = trim((string)$organization->get('name'));
			if ($organizationName === '') {
				$organizationName = 'cette organisation';
			}

			$detailsHtml = '<div style="display:grid; gap:10px;">'
				. '<div><strong>Personne</strong><br>' . \commonMailEscape($requesterLabel) . '</div>';
			if ($requesterEmail !== '') {
				$detailsHtml .= '<div><strong>E-mail</strong><br>' . \commonMailEscape($requesterEmail) . '</div>';
			}

			$requestMessage = $this->getRequestMessage();
			if ($requestMessage !== '') {
				$detailsHtml .= '<div><strong>Message</strong><br>' . \commonMailTextToHtml($requestMessage) . '</div>';
			}
			$detailsHtml .= '</div>';

			$message = \commonRenderMailLayout([
				'brand_name' => $organizationName,
				'brand_color' => trim((string)$organization->get('color')),
				'logo_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
				'banner_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
				'heading' => self::uiMessage('copy.nouvelle_demande_d_acces'),
				'intro_html' => '<p style="margin:0 0 14px; color:#475569; line-height:1.7;">'
					. \commonMailEscape($requesterLabel)
					. ' souhaite rejoindre '
					. \commonMailEscape($organizationName)
					. '.</p>',
				'body_html' => '<p style="margin:0; color:#475569; line-height:1.7;">Un clic sur le bouton ci-dessous validera directement son inscription.</p>',
				'details_html' => $detailsHtml,
				'button_label' => 'Valider l’inscription',
				'button_url' => $this->getApprovalUrl(),
				'footer_html' => '<p style="margin:0;">Ce lien reste valable jusqu au '
					. \commonMailEscape(($this->get('dateexpiration') instanceof \DateTimeInterface ? $this->get('dateexpiration')->format('d.m.Y H:i') : 'prochaines semaines'))
					. '.</p>',
			]);

			$fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
			if ($fromAddress === '') {
				$host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
				$fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
			}

			$recipientEmails = array_map(static function (array $recipient) {
				return (string)$recipient['email'];
			}, $recipients);

			if (!myHTMLMail([$fromAddress, (string)$organization->get('name')], $recipientEmails, self::uiMessage('copy.demande_d_acces_a_organizationname', ['organizationName' => $organizationName]), $message, organizationId: (int)$organization->getId())) {
				throw new \RuntimeException(self::uiMessage('copy.la_demande_n_a_pas_pu_etre_envoyee_aux_administrateurs'));
			}

			return true;
		}

		protected function sendMemberRequestAcceptedEmail()
		{
			require_once dirname(__DIR__, 2) . '/common/email_layout.php';

			$organization = $this->getOrganizationObject();
			$user = $this->getInvitedUserObject();

			if (!$organization || !$user) {
				throw new \RuntimeException(self::uiMessage('copy.la_confirmation_ne_peut_pas_etre_envoyee'));
			}

			$email = trim((string)$this->get('email'));
			if ($email === '') {
				$email = trim((string)$user->get('email'));
			}

			if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new \RuntimeException("L'adresse e-mail de confirmation est invalide.");
			}

			$organizationName = trim((string)$organization->get('name'));
			if ($organizationName === '') {
				$organizationName = 'cette organisation';
			}

			$targetUrl = commonBuildOrganizationHomeUrl(
				(int)$organization->getId(),
				(string)$organization->get('shortname'),
				commonGetRequestHost()
			);

			$message = \commonRenderMailLayout([
				'brand_name' => $organizationName,
				'brand_color' => trim((string)$organization->get('color')),
				'logo_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('logo')),
				'banner_url' => commonBuildAbsoluteAssetUrl((string)$organization->get('banner')),
				'heading' => self::uiMessage('copy.votre_inscription_est_validee'),
				'intro_html' => ('<p style="margin:0 0 14px; color:#475569; line-height:1.7;">' . self::uiMessage('copy.votre_demande_pour_rejoindre_value_a_ete_acceptee', ['value' => \commonMailEscape($organizationName)]) . '</p>'),
				'body_html' => ('<p style="margin:0; color:#475569; line-height:1.7;">' . self::uiMessage('copy.vous_pouvez_maintenant_acceder_a_cet_espace_et_a_ses_outils') . '</p>'),
				'button_label' => self::uiMessage('copy.entrer_dans_l_organisation'),
				'button_url' => $targetUrl,
			]);

			$fromAddress = trim((string)($GLOBALS['mailUser'] ?? ''));
			if ($fromAddress === '') {
				$host = preg_replace('/:\d+$/', '', commonGetRootHost() ?: 'localhost');
				$fromAddress = 'noreply@' . ($host !== '' ? $host : 'localhost');
			}

			if (!myHTMLMail([$fromAddress, (string)$organization->get('name')], $email, self::uiMessage('copy.inscription_validee_dans_organizationname', ['organizationName' => $organizationName]), $message, organizationId: (int)$organization->getId())) {
				throw new \RuntimeException(self::uiMessage('copy.l_e_mail_de_confirmation_n_a_pas_pu_etre_envoye'));
			}

			return true;
		}

		public function sendEmail()
		{
			if ($this->isMemberInitiatedRequest()) {
				return $this->sendMembershipRequestEmail();
			}

			return $this->sendAdminInvitationEmail();
		}

		protected function updateSiblingPendingInvitations($status)
		{
			self::execute(
				"UPDATE invitation
				 SET status = :status,
				     active = 0,
				     dateresponse = NOW()
				 WHERE IDorganization = :organization_id
				   AND IDuser = :user_id
				   AND id != :current_id
				   AND status = 'pending'
				   AND active = 1",
				[
					'status' => (string)$status,
					'organization_id' => (int)$this->get('IDorganization'),
					'user_id' => (int)$this->get('IDuser'),
					'current_id' => (int)$this->getId(),
				]
			);
		}

		protected function acceptInternal($sendConfirmationEmail = false, $approvedByUserId = 0)
		{
			if (!$this->isPending() || $this->isExpired()) {
				return [
					'status' => false,
					'message' => "Cette invitation n'est plus valide.",
				];
			}

			$organizationId = (int)$this->get('IDorganization');
			$userId = (int)$this->get('IDuser');
			$rootHolonId = (int)$this->getOrganizationRootHolonId();
			$pdo = \dbObject\DbObject::getPdo();

			if (!$pdo) {
				return [
					'status' => false,
					'message' => self::uiMessage('copy.la_connexion_a_la_base_de_donnees_est_indisponible'),
				];
			}

			try {
				$ownsTransaction = !$pdo->inTransaction();
				if ($ownsTransaction) {
					$pdo->beginTransaction();
				}

				$user = new \dbObject\User();
				if (!$user->load($userId)) {
					throw new \RuntimeException("Le profil invite est introuvable.");
				}

				if (!(bool)$user->get('active')) {
					$user->set('active', true);
					$saveUser = $user->save();
					if (!is_array($saveUser) || empty($saveUser['status'])) {
						throw new \RuntimeException(self::uiMessage('copy.le_profil_utilisateur_n_a_pas_pu_etre_active'));
					}
				}

				$membership = new \dbObject\UserOrganization();
				if (!$membership->load([
					['IDuser', $userId],
					['IDorganization', $organizationId],
				])) {
					$membership->set('IDuser', $userId);
					$membership->set('IDorganization', $organizationId);
				}

				if (trim((string)$membership->get('email')) === '' && trim((string)$this->get('email')) !== '') {
					$membership->set('email', trim((string)$this->get('email')));
				}

				$membership->set('active', true);
				$saveMembership = $membership->save();
				if (!is_array($saveMembership) || empty($saveMembership['status'])) {
					throw new \RuntimeException(self::uiMessage('copy.l_adhesion_a_l_organisation_n_a_pas_pu_etre_confirmee'));
				}

				self::execute(
					"UPDATE user_organization
					 SET active = 1
					 WHERE IDuser = :user_id
					   AND IDorganization = :organization_id",
					[
						'user_id' => $userId,
						'organization_id' => $organizationId,
					]
				);

				if ($rootHolonId > 0) {
					foreach ($this->getScopedUserHolonLinks(false) as $link) {
						$link->set('active', true);
						$saveLink = $link->save();
						if (!is_array($saveLink) || empty($saveLink['status'])) {
							throw new \RuntimeException(self::uiMessage('copy.un_lien_de_contexte_n_a_pas_pu_etre_active'));
						}
					}
				}

				$this->applyRequestedHolonAdmins($userId, $organizationId, $rootHolonId);

				if ($rootHolonId > 0) {
					foreach ($this->getScopedUserHolonLinks(false) as $link) {
						if (!(bool)$link->get('active') || $link->isHolonAdmin()) {
							continue;
						}

						$holon = new \dbObject\Holon();
						if (!$holon->load((int)$link->get('IDholon'))) {
							continue;
						}

						$constraintResult = $holon->validateMemberAdditionAdminBounds($userId, false, array(
							'organizationId' => $organizationId,
						));
						if (empty($constraintResult['status'])) {
							throw new \RuntimeException((string)($constraintResult['message'] ?? self::uiMessage('copy.les_contraintes_d_admins_ne_sont_pas_respectees')));
						}
					}
				}

				$parameters = $this->get('parameters');
				if (!is_array($parameters)) {
					$parameters = [];
				}
				if ($approvedByUserId > 0) {
					$parameters['approved_by_user_id'] = $approvedByUserId;
					$parameters['approved_at'] = (new \DateTime())->format('c');
				}
				$this->set('parameters', $parameters);

				if ($sendConfirmationEmail) {
					$this->sendMemberRequestAcceptedEmail();
				}

				$this->set('status', 'accepted');
				$this->set('dateresponse', new \DateTime());
				$this->set('active', false);
				$saveInvitation = $this->save();
				if (!is_array($saveInvitation) || empty($saveInvitation['status'])) {
					throw new \RuntimeException(self::uiMessage('copy.l_invitation_n_a_pas_pu_etre_mise_a_jour'));
				}

				$this->updateSiblingPendingInvitations('accepted');

				if ($ownsTransaction && $pdo->inTransaction()) {
					$pdo->commit();
				}

				return [
					'status' => true,
					'message' => $this->isMemberInitiatedRequest() ? self::uiMessage('copy.demande_validee') : 'Invitation acceptee.',
					'userId' => $userId,
					'organizationId' => $organizationId,
				];
			} catch (\Throwable $exception) {
				if (isset($ownsTransaction) && $ownsTransaction && $pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return [
					'status' => false,
					'message' => $exception->getMessage(),
				];
			}
		}

		public function accept()
		{
			return $this->acceptInternal(false, 0);
		}

		public function approveByAdmin(array $options = [])
		{
			$approvedByUserId = (int)($options['approvedByUserId'] ?? 0);
			$sendConfirmationEmail = !empty($options['sendConfirmationEmail']);

			return $this->acceptInternal($sendConfirmationEmail, $approvedByUserId);
		}

		protected function closePendingInvitation($status, $responseMessage, array $options = [])
		{
			if (!$this->isPending() || $this->isExpired()) {
				return [
					'status' => false,
					'message' => "Cette invitation n'est plus valide.",
				];
			}

			$status = trim((string)$status);
			$responseMessage = trim((string)$responseMessage);
			$closedByUserId = (int)($options['closedByUserId'] ?? 0);
			$organizationId = (int)$this->get('IDorganization');
			$userId = (int)$this->get('IDuser');
			$rootHolonId = (int)$this->getOrganizationRootHolonId();
			$pdo = \dbObject\DbObject::getPdo();

			if ($pdo === false) {
				return [
					'status' => false,
					'message' => self::uiMessage('copy.la_connexion_a_la_base_de_donnees_est_indisponible'),
				];
			}

			try {
				$pdo->beginTransaction();
				$organization = $this->getOrganizationObject();
				if (!$organization) {
					throw new \RuntimeException("L'organisation de l'invitation est introuvable.");
				}
				$departureResult = $organization->disconnectUserPreservingHistory($userId);
				if (empty($departureResult['status'])) {
					throw new \RuntimeException((string)($departureResult['message'] ?? self::uiMessage('copy.le_retrait_de_l_invitation_n_a_pas_pu_etre_finalise')));
				}

				if ($rootHolonId > 0) {
					$linkIds = [];
					foreach ($this->getScopedUserHolonLinks(false) as $link) {
						$linkIds[] = (int)$link->getId();
					}

					if (count($linkIds) > 0) {
						$placeholders = [];
						$params = [];
						foreach ($linkIds as $index => $linkId) {
							$key = 'link_' . $index;
							$placeholders[] = ':' . $key;
							$params[$key] = $linkId;
						}

						self::execute(
							"DELETE FROM user_holon WHERE id IN (" . implode(', ', $placeholders) . ")",
							$params
						);
					}
				}

				self::execute(
					"DELETE FROM user_organization
					 WHERE IDuser = :user_id
					   AND IDorganization = :organization_id
					   AND active = 0",
					[
						'user_id' => $userId,
						'organization_id' => $organizationId,
					]
				);

				$parameters = $this->get('parameters');
				if (!is_array($parameters)) {
					$parameters = [];
				}
				if ($closedByUserId > 0) {
					$parameters['closed_by_user_id'] = $closedByUserId;
					$parameters['closed_at'] = (new \DateTime())->format('c');
				}
				$this->set('parameters', $parameters);

				$this->set('status', $status);
				$this->set('dateresponse', new \DateTime());
				$this->set('active', false);
				$saveInvitation = $this->save();
				if (!is_array($saveInvitation) || empty($saveInvitation['status'])) {
					throw new \RuntimeException(self::uiMessage('copy.l_invitation_n_a_pas_pu_etre_mise_a_jour'));
				}

				$this->updateSiblingPendingInvitations($status);

				$pdo->commit();

				return [
					'status' => true,
					'message' => $responseMessage,
					'userId' => $userId,
					'organizationId' => $organizationId,
				];
			} catch (\Throwable $exception) {
				if ($pdo->inTransaction()) {
					$pdo->rollBack();
				}

				return [
					'status' => false,
					'message' => $exception->getMessage(),
				];
			}
		}

		public function decline()
		{
			return $this->closePendingInvitation('declined', self::uiMessage('copy.invitation_refusee'));
		}

		public function cancelByAdmin(array $options = [])
		{
			return $this->closePendingInvitation('canceled', 'Invitation annulee.', [
				'closedByUserId' => (int)($options['canceledByUserId'] ?? 0),
			]);
		}
	}

?>
