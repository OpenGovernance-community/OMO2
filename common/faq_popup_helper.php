<?php

if (!function_exists('faqPopupDescribeScope')) {
	function faqPopupDescribeScope(\dbObject\FAQ $faq)
	{
		$organization = $faq->getResolvedOrganization();
		$holon = $faq->getContextHolon();
		$scopeType = 'generic';
		$scopeLabel = 'FAQ generique';
		$organizationLabel = $organization ? trim((string)$organization->getLabel()) : '';
		$holonLabel = $holon ? trim((string)$holon->getDisplayName()) : '';

		if ($organization) {
			$scopeType = 'organization';
			$scopeLabel = 'Organisation: ' . $organizationLabel;
		}

		if ($holon) {
			$scopeType = 'holon';
			$scopeLabel = 'Holon: ' . $holonLabel;
			if (
				$organization
				&& $organizationLabel !== ''
				&& strcasecmp($holonLabel, $organizationLabel) !== 0
			) {
				$scopeLabel .= ' (' . $organizationLabel . ')';
			}
		}

		return array(
			'type' => $scopeType,
			'label' => $scopeLabel,
			'organization' => $organization,
			'holon' => $holon,
		);
	}
}

if (!function_exists('faqPopupLoadOrganizationOptions')) {
	function faqPopupLoadOrganizationOptions(array $faqContext, ?\dbObject\FAQ $faq = null)
	{
		$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext);
		$options = array();

		if (!empty($viewerAccess['canManageAllFaqs'])) {
			$organizations = new \dbObject\ArrayOrganization();
			$organizations->load(array(
				'orderBy' => array(
					array('field' => 'name', 'dir' => 'ASC'),
					array('field' => 'id', 'dir' => 'ASC'),
				),
			));

			foreach ($organizations as $organization) {
				if ($organization instanceof \dbObject\Organization) {
					$options[] = $organization;
				}
			}

			return $options;
		}

		$organization = $faqContext['organization'] ?? null;
		if ($organization instanceof \dbObject\Organization) {
			$options[] = $organization;
		} elseif ($faq instanceof \dbObject\FAQ) {
			$resolvedOrganization = $faq->getResolvedOrganization();
			if ($resolvedOrganization) {
				$options[] = $resolvedOrganization;
			}
		}

		return $options;
	}
}

if (!function_exists('faqPopupFormatHolonOptionLabel')) {
	function faqPopupFormatHolonOptionLabel($label, $depth = 0)
	{
		$label = trim((string)$label);
		$depth = max(0, (int)$depth);

		if ($depth <= 0 || $label === '') {
			return $label;
		}

		return str_repeat('-- ', $depth) . $label;
	}
}

if (!function_exists('faqPopupSortHolonChildren')) {
	function faqPopupSortHolonChildren(array $children)
	{
		$typePriority = array(
			2 => 0,
			3 => 1,
		);

		usort($children, function ($left, $right) use ($typePriority) {
			$leftType = (int)($left instanceof \dbObject\Holon ? $left->get('IDtypeholon') : 0);
			$rightType = (int)($right instanceof \dbObject\Holon ? $right->get('IDtypeholon') : 0);
			$leftPriority = $typePriority[$leftType] ?? 9;
			$rightPriority = $typePriority[$rightType] ?? 9;

			if ($leftPriority !== $rightPriority) {
				return $leftPriority <=> $rightPriority;
			}

			$leftName = $left instanceof \dbObject\Holon ? trim((string)$left->getDisplayName()) : '';
			$rightName = $right instanceof \dbObject\Holon ? trim((string)$right->getDisplayName()) : '';
			$nameComparison = strcasecmp($leftName, $rightName);
			if ($nameComparison !== 0) {
				return $nameComparison;
			}

			$leftId = $left instanceof \dbObject\Holon ? (int)$left->getId() : 0;
			$rightId = $right instanceof \dbObject\Holon ? (int)$right->getId() : 0;
			return $leftId <=> $rightId;
		});

		return $children;
	}
}

if (!function_exists('faqPopupAppendHolonOptionsFromTree')) {
	function faqPopupAppendHolonOptionsFromTree(\dbObject\Holon $holon, $organizationId, $organizationLabel, array &$options, $depth = 0, array &$visited = array())
	{
		$holonId = (int)$holon->getId();
		$organizationId = (int)$organizationId;
		if ($holonId <= 0 || $organizationId <= 0 || isset($visited[$holonId])) {
			return;
		}

		$visited[$holonId] = true;
		$options[] = array(
			'id' => $holonId,
			'organizationId' => $organizationId,
			'label' => faqPopupFormatHolonOptionLabel($holon->getDisplayName(), $depth),
			'organizationLabel' => trim((string)$organizationLabel),
		);

		$children = array();
		foreach ($holon->getChildren() as $child) {
			if ($child instanceof \dbObject\Holon) {
				$children[] = $child;
			}
		}

		foreach (faqPopupSortHolonChildren($children) as $child) {
			faqPopupAppendHolonOptionsFromTree($child, $organizationId, $organizationLabel, $options, $depth + 1, $visited);
		}
	}
}

if (!function_exists('faqPopupLoadHolonOptions')) {
	function faqPopupLoadHolonOptions(array $faqContext, array $organizations)
	{
		$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext);
		$options = array();
		$visited = array();

		if (!empty($viewerAccess['canManageAllFaqs'])) {
			foreach ($organizations as $organization) {
				if (!$organization instanceof \dbObject\Organization) {
					continue;
				}

				$organizationId = (int)$organization->getId();
				$organizationLabel = trim((string)$organization->getLabel());
				$rootHolon = $organization->getStructuralRootHolon();
				if (!$rootHolon instanceof \dbObject\Holon || (int)$rootHolon->getId() <= 0) {
					continue;
				}

				faqPopupAppendHolonOptionsFromTree($rootHolon, $organizationId, $organizationLabel, $options, 0, $visited);
			}

			return $options;
		}

		$organization = $faqContext['organization'] ?? null;
		$rootHolon = $faqContext['rootHolon'] ?? null;
		if (
			!$organization instanceof \dbObject\Organization
			|| !$rootHolon instanceof \dbObject\Holon
			|| (int)$organization->getId() <= 0
			|| (int)$rootHolon->getId() <= 0
		) {
			return $options;
		}

		faqPopupAppendHolonOptionsFromTree(
			$rootHolon,
			(int)$organization->getId(),
			trim((string)$organization->getLabel()),
			$options,
			0,
			$visited
		);

		return $options;
	}
}

if (!function_exists('faqPopupRenderScopeFields')) {
	function faqPopupRenderScopeFields(\dbObject\FAQ $faq, array $faqContext, array $options = array())
	{
		$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext);
		$canManageAllFaqs = !empty($viewerAccess['canManageAllFaqs']);
		$canManageOrganizationFaqs = !empty($viewerAccess['canManageOrganizationFaqs']);
		$isContextualOnly = empty($options['allowScopeEditing']);
		$allowGeneric = !empty($options['allowGeneric']);
		$selectedOrganizationId = (int)$faq->getResolvedOrganizationId();
		$selectedHolonId = (int)$faq->get('IDholon');

		if ($selectedOrganizationId <= 0 && !$canManageAllFaqs) {
			$selectedOrganizationId = (int)($faqContext['organizationId'] ?? 0);
		}

		$organizations = faqPopupLoadOrganizationOptions($faqContext, $faq);
		$holons = faqPopupLoadHolonOptions($faqContext, $organizations);
		$hasScopeControls = $canManageAllFaqs || $canManageOrganizationFaqs || !$isContextualOnly;

		if (!$hasScopeControls) {
			return;
		}
		?>
		<div class="faq-popup__scope-grid" data-faq-scope-fields>
			<div class="faq-popup__scope-field">
				<label class="faq-popup__scope-label" for="faqScopeOrganization">Organisation</label>
				<?php if ($canManageAllFaqs): ?>
					<select
						class="faq-popup__scope-control"
						name="IDorganization"
						id="faqScopeOrganization"
						data-faq-scope-organization
					>
						<option value="">FAQ generique</option>
						<?php foreach ($organizations as $organization): ?>
							<?php if (!$organization instanceof \dbObject\Organization) {
								continue;
							} ?>
							<option value="<?= (int)$organization->getId() ?>"<?= $selectedOrganizationId === (int)$organization->getId() ? ' selected' : '' ?>>
								<?= htmlspecialchars(trim((string)$organization->getLabel()), ENT_QUOTES, 'UTF-8') ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php else: ?>
					<input type="hidden" name="IDorganization" value="<?= $selectedOrganizationId > 0 ? (int)$selectedOrganizationId : (int)($faqContext['organizationId'] ?? 0) ?>">
					<div class="faq-popup__scope-fixed">
						<?= htmlspecialchars(trim((string)(($faqContext['organization'] ?? null) instanceof \dbObject\Organization ? $faqContext['organization']->getLabel() : 'Organisation courante')), ENT_QUOTES, 'UTF-8') ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="faq-popup__scope-field">
				<label class="faq-popup__scope-label" for="faqScopeHolon">Holon</label>
				<select
					class="faq-popup__scope-control"
					name="IDholon"
					id="faqScopeHolon"
					data-faq-scope-holon
					<?= $isContextualOnly ? ' disabled' : '' ?>
				>
					<option value=""><?= $allowGeneric || $canManageOrganizationFaqs || $canManageAllFaqs ? 'Aucun holon' : 'Holon courant' ?></option>
					<?php foreach ($holons as $holonOption): ?>
						<?php
						$holonOptionId = (int)($holonOption['id'] ?? 0);
						$holonOrganizationId = (int)($holonOption['organizationId'] ?? 0);
						$holonLabel = trim((string)($holonOption['label'] ?? ''));
						$organizationLabel = trim((string)($holonOption['organizationLabel'] ?? ''));
						if ($holonOptionId <= 0 || $holonLabel === '') {
							continue;
						}
						?>
						<option
							value="<?= $holonOptionId ?>"
							data-organization-id="<?= $holonOrganizationId ?>"
							<?= $selectedHolonId === $holonOptionId ? ' selected' : '' ?>
						>
							<?= htmlspecialchars($holonLabel . ($organizationLabel !== '' && $canManageAllFaqs ? ' (' . $organizationLabel . ')' : ''), ENT_QUOTES, 'UTF-8') ?>
						</option>
					<?php endforeach; ?>
				</select>
				<?php if ($isContextualOnly): ?>
					<input type="hidden" name="IDholon" value="<?= (int)$selectedHolonId ?>">
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

if (!function_exists('faqPopupResolveSubmittedScope')) {
	function faqPopupResolveSubmittedScope(array $faqContext, array $postData, array $options = array())
	{
		$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext);
		$canManageAllFaqs = !empty($viewerAccess['canManageAllFaqs']);
		$canManageOrganizationFaqs = !empty($viewerAccess['canManageOrganizationFaqs']);
		$allowContextualCreate = !empty($options['allowContextualCreate']);
		$organizationId = isset($postData['IDorganization']) && is_numeric($postData['IDorganization'])
			? (int)$postData['IDorganization']
			: 0;
		$holonId = isset($postData['IDholon']) && is_numeric($postData['IDholon'])
			? (int)$postData['IDholon']
			: 0;
		$holon = null;

		if ($holonId > 0) {
			$holon = new \dbObject\Holon();
			if (!$holon->load($holonId)) {
				return array(
					'status' => false,
					'message' => 'Holon invalide.',
				);
			}
		}

		if ($canManageAllFaqs) {
			if ($organizationId > 0) {
				$organization = new \dbObject\Organization();
				if (!$organization->load($organizationId)) {
					return array(
						'status' => false,
						'message' => 'Organisation invalide.',
					);
				}
			}

			if ($holon) {
				$holonOrganizationId = (int)$holon->get('IDorganization');
				if ($organizationId > 0 && $holonOrganizationId > 0 && $organizationId !== $holonOrganizationId) {
					return array(
						'status' => false,
						'message' => 'Le holon selectionne ne correspond pas a l organisation choisie.',
					);
				}

				if ($holonOrganizationId > 0) {
					$organizationId = $holonOrganizationId;
				}
			}

			return array(
				'status' => true,
				'organizationId' => $organizationId > 0 ? $organizationId : null,
				'holonId' => $holon ? (int)$holon->getId() : null,
				'holon' => $holon,
			);
		}

		$contextOrganizationId = (int)($faqContext['organizationId'] ?? 0);
		if ($canManageOrganizationFaqs) {
			if ($contextOrganizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Organisation invalide.',
				);
			}

			if ($holon && (int)$holon->get('IDorganization') !== $contextOrganizationId) {
				return array(
					'status' => false,
					'message' => 'Le holon selectionne n appartient pas a l organisation courante.',
				);
			}

			return array(
				'status' => true,
				'organizationId' => $contextOrganizationId,
				'holonId' => $holon ? (int)$holon->getId() : null,
				'holon' => $holon,
			);
		}

		if ($allowContextualCreate) {
			$currentHolon = $faqContext['currentHolon'] ?? null;
			if (!$currentHolon instanceof \dbObject\Holon || (int)$currentHolon->getId() <= 0) {
				return array(
					'status' => false,
					'message' => 'Contexte holon invalide.',
				);
			}

			return array(
				'status' => true,
				'organizationId' => $contextOrganizationId > 0 ? $contextOrganizationId : null,
				'holonId' => (int)$currentHolon->getId(),
				'holon' => $currentHolon,
			);
		}

		return array(
			'status' => false,
			'message' => 'Vous n avez pas le droit de modifier le scope de cette FAQ.',
		);
	}
}
