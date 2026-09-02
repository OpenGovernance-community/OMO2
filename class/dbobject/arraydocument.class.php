<?php
	namespace dbObject;

	class ArrayDocument extends ArrayDbObject
	{
		protected $lastVisibilityStats = array(
			'loaded' => 0,
			'visible' => 0,
			'hidden' => 0,
		);

		public static function objectName()
		{
			return "\dbObject\Document";
		}

		public function getLastVisibilityStats(): array
		{
			return $this->lastVisibilityStats;
		}

		public static function loadListMetadataForOrganization(int $organizationId): array
		{
			$metadata = array(
				'activityByDocumentId' => array(),
				'documentsWithChildren' => array(),
			);
			if ($organizationId <= 0) {
				return $metadata;
			}

			$documents = new self();
			$documents->load(array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => $organizationId),
				),
				'orderBy' => array(
					array('field' => 'datemodification', 'dir' => 'DESC'),
					array('field' => 'id', 'dir' => 'DESC'),
				),
				'hydrate' => array(
					'IDdocument_parent',
					'IDuser',
					'IDusercreation',
					'IDusermodification',
					'estDossier',
					'datecreation',
					'datemodification',
				),
			));

			$documentsById = array();
			$childrenByParentId = array();
			foreach ($documents as $document) {
				if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
					continue;
				}

				$documentId = (int)$document->getId();
				$documentsById[$documentId] = $document;
				$parentDocumentId = (int)$document->get('IDdocument_parent');
				if ($parentDocumentId > 0) {
					$childrenByParentId[$parentDocumentId][] = $documentId;
					$metadata['documentsWithChildren'][$parentDocumentId] = true;
				}
			}

			$resolved = array();
			$resolving = array();
			$resolveActivity = static function (int $documentId) use (&$resolveActivity, &$resolved, &$resolving, $documentsById, $childrenByParentId): array {
				if (isset($resolved[$documentId])) {
					return $resolved[$documentId];
				}

				$document = $documentsById[$documentId] ?? null;
				if (!($document instanceof \dbObject\Document)) {
					return array('date' => null, 'userId' => 0);
				}

				$ownDate = $document->get('datemodification');
				if (!($ownDate instanceof \DateTimeInterface)) {
					$ownDate = $document->get('datecreation');
				}
				$ownUserId = (int)$document->get('IDusermodification');
				if ($ownUserId <= 0) {
					$ownUserId = (int)$document->get('IDusercreation');
				}
				if ($ownUserId <= 0) {
					$ownUserId = (int)$document->get('IDuser');
				}

				if (isset($resolving[$documentId])) {
					return array('date' => $ownDate, 'userId' => $ownUserId);
				}

				$resolving[$documentId] = true;
				$latestDate = (int)$document->get('estDossier') === 1 ? null : $ownDate;
				$latestUserId = $ownUserId;
				if ((int)$document->get('estDossier') === 1) {
					foreach ($childrenByParentId[$documentId] ?? array() as $childDocumentId) {
						$childActivity = $resolveActivity((int)$childDocumentId);
						$childDate = $childActivity['date'] ?? null;
						if (
							$childDate instanceof \DateTimeInterface
							&& (!($latestDate instanceof \DateTimeInterface) || $childDate->getTimestamp() > $latestDate->getTimestamp())
						) {
							$latestDate = $childDate;
							$latestUserId = (int)($childActivity['userId'] ?? 0);
						}
					}
					if (!($latestDate instanceof \DateTimeInterface)) {
						$latestDate = $ownDate;
						$latestUserId = $ownUserId;
					}
				}

				unset($resolving[$documentId]);
				$resolved[$documentId] = array('date' => $latestDate, 'userId' => $latestUserId);
				return $resolved[$documentId];
			};

			foreach (array_keys($documentsById) as $documentId) {
				$metadata['activityByDocumentId'][(int)$documentId] = $resolveActivity((int)$documentId);
			}

			return $metadata;
		}

		protected function getVisibilityRuleMap($organizationId = 0): array
		{
			$organizationId = (int)$organizationId;
			$documentIds = array();

			foreach ($this as $document) {
				if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
					continue;
				}

				$documentIds[] = (int)$document->getId();
			}

			return \dbObject\ObjectVisibility::loadActiveRuleRows(
				\dbObject\Document::getVisibilityObjectType(),
				$documentIds,
				$organizationId
			);
		}

		public function filterVisibleForCurrentViewer($organizationId = 0, ?array $ruleMap = null)
		{
			$organizationId = (int)$organizationId;
			$ruleMap = is_array($ruleMap) ? $ruleMap : $this->getVisibilityRuleMap($organizationId);
			$viewerContext = \dbObject\ObjectVisibility::buildCurrentViewerContext($organizationId);
			$referenceDate = new \DateTimeImmutable();
			$viewerUserId = function_exists('commonGetCurrentUserId')
				? (int)\commonGetCurrentUserId()
				: (int)($_SESSION['currentUser'] ?? 0);
			$candidateVisibleDocuments = array();
			$documentsById = array();
			$loadedCount = 0;

			foreach ($this as $document) {
				if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
					continue;
				}
				if ($document->isArchived()) {
					$loadedCount += 1;
					continue;
				}

				$loadedCount += 1;
				$documentsById[(int)$document->getId()] = $document;

				$documentId = (int)$document->getId();
				$documentOrganizationId = (int)$document->get('IDorganization');
				$resolvedOrganizationId = $organizationId > 0 ? $organizationId : $documentOrganizationId;

				$canAccessVisibility = \dbObject\ObjectVisibility::viewerCanAccessRule(
					$ruleMap[$documentId] ?? null,
					$viewerContext,
					array(
						'organizationId' => $resolvedOrganizationId,
						'ownerUserId' => (int)$document->get('IDuser'),
					)
				);

				$hasPvPreValidationAccess = $document->isPvDocument()
					&& !$document->isPvValidated()
					&& $document->canUserAccessPvBeforeValidation($viewerUserId, $resolvedOrganizationId);
				if (!$canAccessVisibility && !$hasPvPreValidationAccess) {
					continue;
				}

				if (!$document->canUserPassPvMeetingVisibilityGate($viewerUserId, $resolvedOrganizationId, $referenceDate)) {
					continue;
				}

				if (!$document->isAvailableInDocumentsList($referenceDate)) {
					continue;
				}

				$candidateVisibleDocuments[] = $document;
			}

			$candidateVisibleIds = array();
			foreach ($candidateVisibleDocuments as $document) {
				$candidateVisibleIds[(int)$document->getId()] = true;
			}

			$visibleDocuments = array();
			foreach ($candidateVisibleDocuments as $document) {
				$parentDocumentId = (int)$document->get('IDdocument_parent');
				$visitedParentIds = array();
				$hasVisibleChain = true;

				while ($parentDocumentId > 0) {
					if (isset($visitedParentIds[$parentDocumentId])) {
						$hasVisibleChain = false;
						break;
					}

					$visitedParentIds[$parentDocumentId] = true;
					if (
						!isset($candidateVisibleIds[$parentDocumentId])
						|| !isset($documentsById[$parentDocumentId])
					) {
						$hasVisibleChain = false;
						break;
					}

					$parentDocumentId = (int)$documentsById[$parentDocumentId]->get('IDdocument_parent');
				}

				if ($hasVisibleChain) {
					$visibleDocuments[] = $document;
				}
			}

			$this->exchangeArray($visibleDocuments);
			$this->lastVisibilityStats = array(
				'loaded' => $loadedCount,
				'visible' => count($visibleDocuments),
				'hidden' => max(0, $loadedCount - count($visibleDocuments)),
			);
			return $ruleMap;
		}

		public function loadVisibleForOrganizationContext($organizationId, $holonId = 0, $documentScope = 'contextual', array $descendantHolonIds = array())
		{
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;
			$documentScope = trim(mb_strtolower((string)$documentScope, 'UTF-8'));
			if (!in_array($documentScope, array('contextual', 'children', 'descendants'), true)) {
				$documentScope = 'contextual';
			}
			$descendantHolonIds = array_values(array_unique(array_filter(array_map('intval', $descendantHolonIds), static function ($candidateHolonId) {
				return $candidateHolonId > 0;
			})));

			$this->exchangeArray([]);
			$this->lastVisibilityStats = array(
				'loaded' => 0,
				'visible' => 0,
				'hidden' => 0,
			);

			if ($organizationId <= 0) {
				return array();
			}

			$loadParams = array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => $organizationId),
					array('field' => 'active', 'value' => 1),
				),
				'hydrate' => \dbObject\Document::getCollectionHydrationFields(),
				'orderBy' => array(
					array('field' => 'datecreation', 'dir' => 'DESC'),
					array('field' => 'id', 'dir' => 'DESC'),
				),
			);

			if ($documentScope === 'children' || $documentScope === 'descendants') {
				if (count($descendantHolonIds) === 0) {
					return array();
				}

				$loadParams['where'][] = array('field' => 'IDholon', 'op' => 'in', 'value' => $descendantHolonIds);
			} else {
				if ($holonId > 0) {
					$loadParams['where'][] = array('field' => 'IDholon', 'value' => $holonId);
				} else {
					$loadParams['where'][] = array('field' => 'IDholon', 'op' => 'is null');
				}
			}

			$this->load($loadParams);
			$this->filterProjectDocumentsNotVisibleInHolon();
			return $this->filterVisibleForCurrentViewer($organizationId);
		}

		protected function filterProjectDocumentsNotVisibleInHolon(): void
		{
			$documentIds = array();
			foreach ($this as $document) {
				if ($document instanceof \dbObject\Document && (int)$document->getId() > 0) {
					$documentIds[] = (int)$document->getId();
				}
			}

			$documentIds = array_values(array_unique($documentIds));
			if (count($documentIds) === 0) {
				return;
			}

			$placeholders = array();
			$params = array();
			foreach ($documentIds as $index => $documentId) {
				$parameterName = 'project_document_id_' . $index;
				$placeholders[] = ':' . $parameterName;
				$params[$parameterName] = $documentId;
			}

			$rows = \dbObject\DbObject::fetchAll(
				'SELECT DISTINCT `IDdocument` FROM `project_document` WHERE `IDdocument` IN (' . implode(', ', $placeholders) . ')',
				$params
			);
			if (!is_array($rows) || count($rows) === 0) {
				return;
			}

			$projectDocumentIds = array();
			foreach ($rows as $row) {
				$projectDocumentId = (int)($row['IDdocument'] ?? 0);
				if ($projectDocumentId > 0) {
					$projectDocumentIds[$projectDocumentId] = true;
				}
			}

			$this->exchangeArray(array_values(array_filter(
				$this->getArrayCopy(),
				static function ($document) use ($projectDocumentIds): bool {
					if (!($document instanceof \dbObject\Document)) {
						return false;
					}

					$documentId = (int)$document->getId();
					return !isset($projectDocumentIds[$documentId])
						|| $document->isVisibleInHolonWhenProjectDocument();
				}
			)));
		}

		public function loadVisibleForOrganization($organizationId)
		{
			$organizationId = (int)$organizationId;
			$this->exchangeArray([]);
			$this->lastVisibilityStats = array(
				'loaded' => 0,
				'visible' => 0,
				'hidden' => 0,
			);

			if ($organizationId <= 0) {
				return array();
			}

			$this->load(array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => $organizationId),
					array('field' => 'active', 'value' => 1),
				),
				'hydrate' => \dbObject\Document::getCollectionHydrationFields(),
				'orderBy' => array(
					array('field' => 'datecreation', 'dir' => 'DESC'),
					array('field' => 'id', 'dir' => 'DESC'),
				),
			));

			return $this->filterVisibleForCurrentViewer($organizationId);
		}

		public function loadVisiblePvTemplatesForOrganization(int $organizationId): void
		{
			$this->exchangeArray([]);
			if ($organizationId <= 0) {
				return;
			}

			$this->load(array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => $organizationId),
					array('field' => 'active', 'value' => 1),
					array('field' => 'documenttype', 'value' => \dbObject\Document::TYPE_PV),
					array('field' => 'is_template', 'value' => 1),
				),
				'hydrate' => \dbObject\Document::getCollectionHydrationFields(),
				'orderBy' => array(
					array('field' => 'title', 'dir' => 'ASC'),
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			$visibleTemplates = array_values(array_filter($this->getArrayCopy(), static function ($document) use ($organizationId): bool {
				return $document instanceof \dbObject\Document
					&& $document->canUseAsPvTemplate($organizationId);
			}));
			$this->exchangeArray($visibleTemplates);
		}

		public function loadRecentForOrganizationContext($organizationId, $holonId = 0, $limit = 5, $documentScope = 'contextual', array $descendantHolonIds = array())
		{
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;
			$limit = max(1, (int)$limit);

			$this->exchangeArray([]);
			$this->lastVisibilityStats = array(
				'loaded' => 0,
				'visible' => 0,
				'hidden' => 0,
			);

			if ($organizationId <= 0) {
				return;
			}

			$this->loadVisibleForOrganizationContext($organizationId, $holonId, $documentScope, $descendantHolonIds);

			$items = array_values(array_filter($this->getArrayCopy(), function ($document) {
				return $document instanceof \dbObject\Document
					&& !$document->isFolder()
					&& (int)$document->getId() > 0;
			}));

			usort($items, function ($left, $right) {
				$leftDate = $left instanceof \dbObject\Document ? $left->get('datemodification') : null;
				$rightDate = $right instanceof \dbObject\Document ? $right->get('datemodification') : null;

				if (!($leftDate instanceof \DateTimeInterface) && $left instanceof \dbObject\Document) {
					$leftDate = $left->get('datecreation');
				}

				if (!($rightDate instanceof \DateTimeInterface) && $right instanceof \dbObject\Document) {
					$rightDate = $right->get('datecreation');
				}

				$leftTimestamp = $leftDate instanceof \DateTimeInterface ? (int)$leftDate->getTimestamp() : 0;
				$rightTimestamp = $rightDate instanceof \DateTimeInterface ? (int)$rightDate->getTimestamp() : 0;
				if ($leftTimestamp !== $rightTimestamp) {
					return $rightTimestamp <=> $leftTimestamp;
				}

				return (int)$right->getId() <=> (int)$left->getId();
			});

			$items = array_slice($items, 0, $limit);
			$this->exchangeArray($items);
		}

		public function loadOwnedByUser($userId)
		{
			$userId = (int)$userId;

			$this->exchangeArray([]);
			$this->lastVisibilityStats = array(
				'loaded' => 0,
				'visible' => 0,
				'hidden' => 0,
			);

			if ($userId <= 0) {
				return;
			}

			$this->load(array(
				'where' => array(
					array('field' => 'IDuser', 'value' => $userId),
				),
				'orderBy' => array(
					array('field' => 'datecreation', 'dir' => 'DESC'),
					array('field' => 'id', 'dir' => 'DESC'),
				),
			));

			$count = count($this);
			$this->lastVisibilityStats = array(
				'loaded' => $count,
				'visible' => $count,
				'hidden' => 0,
			);
		}

		public function buildPersonalSpaceItems($organizationId = 0)
		{
			$organizationId = (int)$organizationId;
			$items = array();
			$ruleMap = $this->getVisibilityRuleMap($organizationId);

			foreach ($this as $document) {
				if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
					continue;
				}

				$documentHolonId = (int)$document->get('IDholon');
				$resolvedOrganizationId = $organizationId > 0
					? $organizationId
					: (int)$document->get('IDorganization');
				$visibility = $document->getVisibilityDisplayData(
					$resolvedOrganizationId,
					$ruleMap[(int)$document->getId()] ?? null
				);

				$items[] = array(
					'id' => (int)$document->getId(),
					'title' => trim((string)$document->get('title')) !== ''
						? trim((string)$document->get('title'))
						: 'Document #' . (int)$document->getId(),
					'description' => trim((string)$document->get('description')),
					'datecreation' => $document->get('datecreation'),
					'datemodification' => $document->get('datemodification'),
					'createdByUserId' => $document->getCreatedByUserId(),
					'updatedByUserId' => $document->getUpdatedByUserId(),
					'createdByName' => $document->getCreatedByDisplayName(),
					'updatedByName' => $document->getUpdatedByDisplayName(),
					'visibility' => $visibility,
					'contextUrl' => '/omo/api/documents/detail.php?id=' . (int)$document->getId()
						. '&oid=' . $resolvedOrganizationId
						. ($documentHolonId > 0 ? '&cid=' . $documentHolonId : ''),
				);
			}

			return $items;
		}
	}

?>
