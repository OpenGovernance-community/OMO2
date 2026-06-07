<?php
	namespace dbObject;

	class ArrayDocument extends ArrayDbObject
	{
		public static function objectName()
		{
			return "\dbObject\Document";
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
			$visibleDocuments = array();

			foreach ($this as $document) {
				if (!($document instanceof \dbObject\Document) || (int)$document->getId() <= 0) {
					continue;
				}

				$documentId = (int)$document->getId();
				$documentOrganizationId = (int)$document->get('IDorganization');
				$resolvedOrganizationId = $organizationId > 0 ? $organizationId : $documentOrganizationId;

				if (!\dbObject\ObjectVisibility::viewerCanAccessRule(
					$ruleMap[$documentId] ?? null,
					$viewerContext,
					array(
						'organizationId' => $resolvedOrganizationId,
						'ownerUserId' => (int)$document->get('IDuser'),
					)
				)) {
					continue;
				}

				$visibleDocuments[] = $document;
			}

			$this->exchangeArray($visibleDocuments);
			return $ruleMap;
		}

		public function loadVisibleForOrganizationContext($organizationId, $holonId = 0, $documentScope = 'contextual')
		{
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;
			$documentScope = trim(mb_strtolower((string)$documentScope, 'UTF-8'));
			if ($documentScope !== 'global') {
				$documentScope = 'contextual';
			}

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return array();
			}

			$loadParams = array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => $organizationId),
				),
				'orderBy' => array(
					array('field' => 'datecreation', 'dir' => 'DESC'),
					array('field' => 'id', 'dir' => 'DESC'),
				),
			);

			if ($documentScope !== 'global') {
				if ($holonId > 0) {
					$loadParams['where'][] = array('field' => 'IDholon', 'value' => $holonId);
				} else {
					$loadParams['where'][] = array('field' => 'IDholon', 'op' => 'is null');
				}
			}

			$this->load($loadParams);
			return $this->filterVisibleForCurrentViewer($organizationId);
		}

		public function loadRecentForOrganizationContext($organizationId, $holonId = 0, $limit = 5)
		{
			$organizationId = (int)$organizationId;
			$holonId = (int)$holonId;
			$limit = max(1, (int)$limit);

			$this->exchangeArray([]);

			if ($organizationId <= 0) {
				return;
			}

			$loadParams = array(
				'where' => array(
					array('field' => 'IDorganization', 'value' => $organizationId),
				),
				'orderBy' => array(
					array('field' => 'datecreation', 'dir' => 'DESC'),
					array('field' => 'id', 'dir' => 'DESC'),
				),
			);

			if ($holonId > 0) {
				$loadParams['where'][] = array('field' => 'IDholon', 'value' => $holonId);
			}

			$this->load($loadParams);
			$this->filterVisibleForCurrentViewer($organizationId);

			$items = array_slice($this->getArrayCopy(), 0, $limit);
			$this->exchangeArray($items);
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
