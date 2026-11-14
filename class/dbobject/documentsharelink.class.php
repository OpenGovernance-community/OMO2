<?php
	namespace dbObject;

	class DocumentShareLink extends ShareLink
	{
		public static function tableName()
		{
			return 'document_share_link';
		}

		public static function rules()
		{
			return [
				[['IDorganization', 'IDdocument', 'IDuser'], 'required'],
				[['id', 'IDorganization', 'IDdocument', 'IDuser'], 'integer'],
				[['recipient_user_id'], 'fk'],
				[['label', 'token', 'password_hash', 'recipient_email'], 'string'],
				[['datecreation', 'dateexpiration'], 'datetime'],
				[['allow_live_follow', 'allow_pv_contribution', 'allow_edit', 'active'], 'boolean'],
				[['id'], 'safe'],
			];
		}

		public static function attributeLabels()
		{
			return [
				'id' => 'ID',
				'IDorganization' => 'Organisation',
				'IDdocument' => 'Document',
				'IDuser' => 'Créateur',
				'label' => 'Libellé',
				'token' => 'Token',
				'password_hash' => 'Mot de passe',
				'allow_live_follow' => 'Suivi temps reel',
				'allow_edit' => 'Autoriser l’édition',
				'allow_pv_contribution' => 'Contribution au PV',
				'recipient_email' => 'E-mail du participant',
				'recipient_user_id' => 'Utilisateur participant',
				'datecreation' => 'Date creation',
				'dateexpiration' => 'Date expiration',
				'active' => 'Actif',
			];
		}

		public static function attributeDescriptions()
		{
			return [
				'IDorganization' => 'Organisation du document partage.',
				'IDdocument' => 'Document partage.',
				'IDuser' => 'Utilisateur ayant créé le lien.',
				'label' => 'Libellé interne du lien.',
				'token' => 'Token unique partageable.',
				'password_hash' => 'Hash du mot de passe optionnel.',
				'allow_live_follow' => 'Autorise l’affichage du brouillon temporaire pendant l’édition.',
				'allow_edit' => 'Autorise l’édition du contenu dans les outils collaboratifs compatibles, hors PV.',
				'allow_pv_contribution' => 'Autorise le participant invité à ajouter et modifier ses propres points avant la validation.',
				'recipient_email' => 'Adresse e-mail à laquelle est attribué le lien de participation.',
				'recipient_user_id' => 'Utilisateur déjà connu correspondant au participant invité.',
				'dateexpiration' => 'Date de fin de validité du lien.',
			];
		}

		public static function attributeLength()
		{
			return [
				'label' => 150,
				'token' => 80,
				'password_hash' => 255,
				'recipient_email' => 250,
			];
		}

		public static function getOrder()
		{
			return 'datecreation DESC, id DESC';
		}



		public static function findByToken($token)
		{
			$token = trim((string)$token);
			if ($token === '') {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM document_share_link
				WHERE token = :token
				LIMIT 1",
				array(
					'token' => $token,
				)
			);

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
			$token = trim((string)$token);
			if ($token === '') {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM document_share_link
				WHERE token = :token
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				LIMIT 1",
				array(
					'token' => $token,
				)
			);

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findByIdForContext($shareLinkId, $organizationId, $documentId, $includeInactive = false)
		{
			$shareLinkId = (int)$shareLinkId;
			$organizationId = (int)$organizationId;
			$documentId = (int)$documentId;

			if ($shareLinkId <= 0 || $organizationId <= 0 || $documentId <= 0) {
				return false;
			}

			$sql = "SELECT *
				FROM document_share_link
				WHERE id = :id
				  AND IDorganization = :organization_id
				  AND IDdocument = :document_id";

			if (!$includeInactive) {
				$sql .= "
				  AND active = 1";
			}

			$sql .= "
				LIMIT 1";

			$row = self::fetchRow($sql, array(
				'id' => $shareLinkId,
				'organization_id' => $organizationId,
				'document_id' => $documentId,
			));

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function findAllForContext($organizationId, $documentId, $includeInactive = false)
		{
			$organizationId = (int)$organizationId;
			$documentId = (int)$documentId;

			if ($organizationId <= 0 || $documentId <= 0) {
				return array();
			}

			$sql = "SELECT *
				FROM document_share_link
				WHERE IDorganization = :organization_id
				  AND IDdocument = :document_id";

			if (!$includeInactive) {
				$sql .= "
				  AND active = 1";
			}

			$sql .= "
				ORDER BY
				  CASE
				    WHEN dateexpiration IS NULL THEN 0
				    WHEN dateexpiration > NOW() THEN 0
				    ELSE 1
				  END ASC,
				  datecreation DESC,
				  id DESC";

			$rows = self::fetchAll($sql, array(
				'organization_id' => $organizationId,
				'document_id' => $documentId,
			));

			if (!is_array($rows)) {
				return array();
			}

			$items = array();
			foreach ($rows as $row) {
				$item = new self();
				$item->loadFromArray($row);
				$item->setId((int)$row['id']);
				$items[] = $item;
			}

			return $items;
		}

		public static function findActiveLiveFollowForDocumentByLabel($documentId, $label)
		{
			$documentId = (int)$documentId;
			$label = trim((string)$label);
			if ($documentId <= 0 || $label === '') {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM document_share_link
				WHERE IDdocument = :document_id
				  AND label = :label
				  AND allow_live_follow = 1
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				ORDER BY datecreation DESC, id DESC
				LIMIT 1",
				array(
					'document_id' => $documentId,
					'label' => $label,
				)
			);

			if ($row === false) {
				return false;
			}

			$item = new self();
			$item->loadFromArray($row);
			$item->setId((int)$row['id']);
			return $item;
		}

		public static function getOrCreateLiveFollowForDocument(Document $document, $userId, $label)
		{
			$documentId = (int)$document->getId();
			$label = trim((string)$label);
			if ($documentId <= 0 || $label === '') {
				return false;
			}

			$existing = self::findActiveLiveFollowForDocumentByLabel($documentId, $label);
			if ($existing instanceof self) {
				return $existing;
			}

			return self::createForDocument($document, (int)$userId, array(
				'label' => $label,
				'allow_live_follow' => true,
			));
		}

		public static function getOrCreatePvParticipantLink(Document $document, $userId, $recipientEmail, $recipientUserId = 0)
		{
			$documentId = (int)$document->getId();
			$recipientEmail = trim(mb_strtolower((string)$recipientEmail, 'UTF-8'));
			if ($documentId <= 0 || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
				return false;
			}

			$row = self::fetchRow(
				"SELECT *
				FROM document_share_link
				WHERE IDdocument = :document_id
				  AND recipient_email = :recipient_email
				  AND allow_pv_contribution = 1
				  AND active = 1
				  AND (dateexpiration IS NULL OR dateexpiration > NOW())
				ORDER BY datecreation DESC, id DESC
				LIMIT 1",
				[
					'document_id' => $documentId,
					'recipient_email' => $recipientEmail,
				]
			);
			if (is_array($row)) {
				$item = new self();
				$item->loadFromArray($row);
				$item->setId((int)$row['id']);
				if ((int)$recipientUserId > 0 && (int)$item->get('recipient_user_id') !== (int)$recipientUserId) {
					$item->set('recipient_user_id', (int)$recipientUserId);
					$item->save();
				}
				return $item;
			}

			return self::createForDocument($document, (int)$userId, [
				'label' => 'PV participant access',
				'recipient_email' => $recipientEmail,
				'recipient_user_id' => max(0, (int)$recipientUserId),
				'allow_live_follow' => true,
				'allow_pv_contribution' => true,
			]);
		}

		public static function createForDocument(Document $document, $userId, array $options = array())
		{
			$userId = (int)$userId;
			$organizationId = (int)$document->get('IDorganization');

			if (
				$userId <= 0
				|| (int)$document->getId() <= 0
				|| $organizationId <= 0
				|| $document->isFolder()
			) {
				return false;
			}

			$item = new self();
			$item->set('IDorganization', $organizationId);
			$item->set('IDdocument', (int)$document->getId());
			$item->set('IDuser', $userId);
			$item->set('label', trim((string)($options['label'] ?? '')));
			$item->set('token', self::generateUniqueToken());
			$item->set('password_hash', trim((string)($options['password_hash'] ?? '')) ?: null);
			$item->set('recipient_email', trim(mb_strtolower((string)($options['recipient_email'] ?? ''), 'UTF-8')) ?: null);
			$item->set('recipient_user_id', (int)($options['recipient_user_id'] ?? 0) ?: null);
			$item->set('allow_live_follow', !empty($options['allow_live_follow']) ? 1 : 0);
			$item->set('allow_edit', $document->supportsSharedEditing() && !empty($options['allow_edit']) ? 1 : 0);
			$item->set('allow_pv_contribution', !empty($options['allow_pv_contribution']) ? 1 : 0);
			$item->set('datecreation', new \DateTime());
			$item->set('dateexpiration', $options['dateexpiration'] ?? null);
			$item->set('active', 1);

			$result = $item->save();
			return !empty($result['status']) ? $item : false;
		}


		/** Bind password approvals and service credentials to the current link settings. */
		public function getAccessFingerprint(): string
		{
			$expiration = $this->get('dateexpiration');
			return hash('sha256', implode('|', [(string)$this->get('token'), (string)$this->get('password_hash'),
				(string)$this->get('active'), (string)$this->get('allow_edit'), (string)$this->get('allow_live_follow'),
				$expiration instanceof \DateTimeInterface ? $expiration->format('c') : (string)$expiration]));
		}

		public function allowsDocumentAccess(Document $document): bool
		{
			if (!(bool)$this->get('active') || $this->isExpired() || $document->isArchived()
				|| $document->isFolder() || $document->isNextcloudFolder()
				|| (int)$document->getId() !== (int)$this->get('IDdocument')
				|| (int)$document->get('IDorganization') !== (int)$this->get('IDorganization')) return false;
			foreach ($document->getParentFolderChain() as $parent) {
				if ($parent->isArchived()) return false;
			}
			return true;
		}

		public function allowsDocumentEditing(Document $document): bool
		{
			return $this->allowsDocumentAccess($document) && !$this->allowsPvContribution()
				&& (bool)$this->get('allow_edit') && $document->supportsSharedEditing();
		}

		/** Service callbacks have no browser session; the signed fingerprint proves approval. */
		public static function resolveServiceGrant(int $id, string $fingerprint, Document $document): ?self
		{
			$link = new self();
			return $id > 0 && $link->load($id) && $link->allowsDocumentAccess($document)
				&& hash_equals($link->getAccessFingerprint(), $fingerprint) ? $link : null;
		}



		public function allowsLiveFollow()
		{
			return (bool)$this->get('allow_live_follow');
		}

		public function allowsPvContribution(): bool
		{
			return (bool)$this->get('allow_pv_contribution')
				&& filter_var(trim((string)$this->get('recipient_email')), FILTER_VALIDATE_EMAIL) !== false;
		}

		public function getRecipientEmail(): string
		{
			return trim(mb_strtolower((string)$this->get('recipient_email'), 'UTF-8'));
		}

		public function getRecipientUserId(): int
		{
			return (int)$this->get('recipient_user_id');
		}

		public function getPvAgendaManagerUserId(Document $document): int
		{
			$userId = $this->getRecipientUserId();
			$organizationId = (int)$this->get('IDorganization');
			return $this->allowsPvContribution() && (bool)$this->get('active') && !$this->isExpired()
				&& (int)$this->get('IDdocument') === (int)$document->getId()
				&& (int)$document->get('IDorganization') === $organizationId
				&& in_array($document->getPvStage(), [Document::PV_STAGE_PREPARATION, Document::PV_STAGE_MEETING], true)
				&& UserOrganization::hasActiveMembership($userId, $organizationId)
				&& $document->canUserManagePvStructure($organizationId, $userId) ? $userId : 0;
		}

		/** Read access granted only by a saved reference in a visible meeting point. */
		public function getReadablePvEmbeddedDocument(int $documentId): ?Document
		{
			$link = self::findValidByToken((string)$this->get('token'));
			if (!$link || $link->isExpired() || !$link->allowsPvContribution() || $documentId <= 0) return null;
			$pv = $link->getDocument();
			if (!$pv || !$pv->isPvDocument() || $pv->isPvValidated() || !(bool)$pv->get('active')) return null;
			$organizationId = (int)$pv->get('IDorganization');
			if ($organizationId !== (int)$link->get('IDorganization')) return null;
			$userId = UserOrganization::hasActiveMembership($link->getRecipientUserId(), $organizationId)
				? $link->getRecipientUserId() : 0;
			$referenced = false;
			foreach ($pv->getVisiblePvPointsForUser($userId) as $point) {
				if ($point->isGroup() || $point->isMoved()) continue;
				if (in_array($documentId, Document::getEmbeddedDocumentIds((string)$point->get('content')), true)) {
					$referenced = true;
					break;
				}
			}
			if (!$referenced) return null;
			$document = new Document();
			return $document->load($documentId) && (bool)$document->get('active')
				&& (int)$document->get('IDorganization') === $organizationId && $document->canBeEmbedded()
				? $document : null;
		}

		public function canUsePvDocumentReferences(string $content, string $previousContent = ''): bool
		{
			$organizationId = (int)$this->get('IDorganization');
			$userId = $this->getRecipientUserId();
			$isMember = UserOrganization::hasActiveMembership($userId, $organizationId);
			$previousIds = Document::getEmbeddedDocumentIds($previousContent);
			foreach (Document::getEmbeddedDocumentIds($content) as $id) {
				// Keep unavailable references editable, without granting access to them.
				if (in_array($id, $previousIds, true)) continue;
				if ($this->getReadablePvEmbeddedDocument($id)) continue;
				$target = new Document();
				if (!$isMember || !$target->load($id) || !(bool)$target->get('active')
					|| (int)$target->get('IDorganization') !== $organizationId || !$target->canBeEmbedded()
					|| !$target->canViewInOrganizationContext($organizationId, (int)$target->get('IDholon') ?: null, $userId)) return false;
			}
			return true;
		}

		public function getDocument()
		{
			$documentId = (int)$this->get('IDdocument');
			if ($documentId <= 0) {
				return null;
			}

			$document = new \dbObject\Document();
			return $document->load($documentId) ? $document : null;
		}

		public function buildShareUrl()
		{
			return '/omo/document_share.php?token=' . rawurlencode((string)$this->get('token'));
		}

		public function buildPvParticipationUrl()
		{
			return '/omo/pv_participation.php?token=' . rawurlencode((string)$this->get('token'));
		}
	}

?>
