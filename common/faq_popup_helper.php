<?php

require_once __DIR__ . '/omo_context_scope.php';

if (!function_exists('faqPopupDescribeScope')) {
	function faqPopupDescribeScope(\dbObject\FAQ $faq)
	{
		$organization = $faq->getResolvedOrganization();
		$holon = $faq->getContextHolon();
		$parcours = $faq->getContextParcours();
		$scopeType = 'generic';
		$scopeLabel = 'FAQ generique';
		$organizationLabel = $organization ? trim((string)$organization->getLabel()) : '';
		$holonLabel = $holon ? trim((string)$holon->getDisplayName()) : '';
		$parcoursLabel = $parcours ? trim((string)$parcours->get('title')) : '';

		if ($organization) {
			$scopeType = 'organization';
			$scopeLabel = 'Organisation: ' . $organizationLabel;
		}

		if ($parcours) {
			$scopeType = 'parcours';
			$scopeLabel = 'Parcours: ' . $parcoursLabel;
			if ($organization && $organizationLabel !== '') {
				$scopeLabel .= ' (' . $organizationLabel . ')';
			}
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
			'parcours' => $parcours,
		);
	}
}

if (!function_exists('faqPopupRenderMediaBlock')) {
	function faqPopupRenderMediaBlock(\dbObject\FAQ $faq)
	{
		$media = $faq->getMediaDisplayData();
		if (empty($media['hasMedia'])) {
			return;
		}
		?>
		<div class="faq-popup__media">
			<?php if (!empty($media['hasImage'])): ?>
				<div class="faq-popup__media-figure">
					<img
						class="faq-popup__media-image"
						src="<?= htmlspecialchars((string)$media['imageUrl'], ENT_QUOTES, 'UTF-8') ?>"
						alt="<?= htmlspecialchars((string)$faq->get('question'), ENT_QUOTES, 'UTF-8') ?>"
						loading="lazy"
					>
				</div>
			<?php endif; ?>
			<?php if (!empty($media['hasVideo'])): ?>
				<div class="faq-popup__media-video-shell">
					<?php if (trim((string)($media['embeddedVideoUrl'] ?? '')) !== ''): ?>
						<div class="faq-popup__media-video">
							<iframe
								src="<?= htmlspecialchars((string)$media['embeddedVideoUrl'], ENT_QUOTES, 'UTF-8') ?>"
								loading="lazy"
								allow="autoplay; fullscreen; picture-in-picture"
								allowfullscreen
								referrerpolicy="strict-origin-when-cross-origin"
								title="<?= htmlspecialchars((string)$faq->get('question'), ENT_QUOTES, 'UTF-8') ?>"
							></iframe>
						</div>
					<?php else: ?>
						<div class="faq-popup__media-fallback">
							<span>Video non integrable automatiquement.</span>
							<a
								class="generic-action-button generic-action-button--secondary"
								href="<?= htmlspecialchars((string)$media['videoUrl'], ENT_QUOTES, 'UTF-8') ?>"
								target="_blank"
								rel="noopener noreferrer"
							>Ouvrir la video</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

if (!function_exists('faqPopupGetVoteSessionDate')) {
	function faqPopupGetVoteSessionDate($faqId)
	{
		$faqId = (int)$faqId;
		if ($faqId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
			return '';
		}

		$sessionValue = $_SESSION['faq_votes'][$faqId] ?? '';
		return is_string($sessionValue) ? trim($sessionValue) : '';
	}
}

if (!function_exists('faqPopupHasVotedToday')) {
	function faqPopupHasVotedToday($faqId)
	{
		return faqPopupGetVoteSessionDate($faqId) === date('Y-m-d');
	}
}

if (!function_exists('faqPopupFormatVoteScore')) {
	function faqPopupFormatVoteScore($value)
	{
		$numericValue = (float)$value;
		if (abs($numericValue - round($numericValue)) < 0.00001) {
			return (string)(int)round($numericValue);
		}

		return rtrim(rtrim(number_format($numericValue, 2, '.', ''), '0'), '.');
	}
}

if (!function_exists('faqPopupEstimateVoteSplit')) {
	function faqPopupEstimateVoteSplit($positiveScore, $negativeScore, $totalVotes)
	{
		$positiveScore = max(0.0, (float)$positiveScore);
		$negativeScore = abs((float)$negativeScore);
		$totalVotes = max(0, (int)$totalVotes);
		$activeSignal = $positiveScore + $negativeScore;

		if ($totalVotes <= 0 || $activeSignal <= 0.0) {
			return array(
				'positive' => 0,
				'negative' => 0,
				'total' => $totalVotes,
			);
		}

		$estimatedPositiveVotes = (int)round($totalVotes * ($positiveScore / $activeSignal));
		$estimatedPositiveVotes = max(0, min($totalVotes, $estimatedPositiveVotes));
		return array(
			'positive' => $estimatedPositiveVotes,
			'negative' => max(0, $totalVotes - $estimatedPositiveVotes),
			'total' => $totalVotes,
		);
	}
}

if (!function_exists('faqPopupBuildReliabilityRange')) {
	function faqPopupBuildReliabilityRange($faqCollection)
	{
		$minimumReliability = null;
		$maximumReliability = null;

		foreach ($faqCollection as $faq) {
			if (!$faq instanceof \dbObject\FAQ) {
				continue;
			}

			$reliability = max(0.0, (float)$faq->get('reliability'));
			if ($minimumReliability === null || $reliability < $minimumReliability) {
				$minimumReliability = $reliability;
			}
			if ($maximumReliability === null || $reliability > $maximumReliability) {
				$maximumReliability = $reliability;
			}
		}

		return array(
			'min' => $minimumReliability === null ? 0.0 : (float)$minimumReliability,
			'max' => $maximumReliability === null ? 0.0 : (float)$maximumReliability,
		);
	}
}

if (!function_exists('faqPopupCalculateRelativeStars')) {
	function faqPopupCalculateRelativeStars($reliability, array $range = array())
	{
		$reliability = max(0.0, (float)$reliability);
		$minimumReliability = isset($range['min']) ? (float)$range['min'] : 0.0;
		$maximumReliability = isset($range['max']) ? (float)$range['max'] : 0.0;

		if ($maximumReliability <= $minimumReliability) {
			return $reliability > 0.0 ? 5 : 0;
		}

		$normalized = ($reliability - $minimumReliability) / ($maximumReliability - $minimumReliability);
		return max(0, min(5, (int)round($normalized * 5)));
	}
}

if (!function_exists('faqPopupRenderStars')) {
	function faqPopupRenderStars($starCount)
	{
		$starCount = max(0, min(5, (int)$starCount));
		return str_repeat('★', $starCount) . str_repeat('☆', 5 - $starCount);
	}
}

if (!function_exists('faqPopupRenderVoteBlock')) {
	function faqPopupRenderVoteBlock(\dbObject\FAQ $faq, array $options = array())
	{
		if (!\dbObject\FAQ::hasVoteColumns()) {
			return;
		}

		$faqId = (int)$faq->getId();
		if ($faqId <= 0) {
			return;
		}

		$isCompact = !empty($options['compact']);
		$hasVotedToday = faqPopupHasVotedToday($faqId);
		$estimatedVoteSplit = faqPopupEstimateVoteSplit(
			$faq->get('positive_score'),
			$faq->get('negative_score'),
			$faq->get('total_votes')
		);
		$reliabilityRange = isset($options['reliabilityRange']) && is_array($options['reliabilityRange'])
			? $options['reliabilityRange']
			: array('min' => 0.0, 'max' => 0.0);
		$relativeStars = faqPopupCalculateRelativeStars($faq->get('reliability'), $reliabilityRange);
		$voteMessage = trim((string)($options['message'] ?? ''));
		if ($voteMessage === '' && $hasVotedToday) {
			$voteMessage = 'Vous avez deja vote aujourd hui. Vous pourrez revoter demain.';
		}
		?>
		<div
			class="faq-popup__vote<?= $isCompact ? ' faq-popup__vote--compact' : '' ?>"
			data-faq-vote-shell
			data-faq-id="<?= $faqId ?>"
			data-faq-vote-mode="<?= $isCompact ? 'compact' : 'detail' ?>"
			data-faq-reliability="<?= htmlspecialchars((string)(float)$faq->get('reliability'), ENT_QUOTES, 'UTF-8') ?>"
		>
			<div class="faq-popup__vote-summary">
				<?php if ($isCompact): ?>
					<span class="faq-popup__vote-stat faq-popup__vote-stat--stars">
						<span class="faq-popup__vote-label">Note</span>
						<strong class="faq-popup__vote-stars" data-faq-stars-text><?= htmlspecialchars(faqPopupRenderStars($relativeStars), ENT_QUOTES, 'UTF-8') ?></strong>
					</span>
					<span class="faq-popup__vote-stat">
						<span class="faq-popup__vote-label">Total</span>
						<strong data-faq-score="total"><?= (int)$estimatedVoteSplit['total'] ?></strong>
					</span>
				<?php else: ?>
					<span class="faq-popup__vote-stat">
						<span class="faq-popup__vote-label">Positifs</span>
						<strong data-faq-score="positive_estimated"><?= (int)$estimatedVoteSplit['positive'] ?></strong>
					</span>
					<span class="faq-popup__vote-stat">
						<span class="faq-popup__vote-label">Negatifs</span>
						<strong data-faq-score="negative_estimated"><?= (int)$estimatedVoteSplit['negative'] ?></strong>
					</span>
					<span class="faq-popup__vote-stat">
						<span class="faq-popup__vote-label">Total</span>
						<strong data-faq-score="total"><?= (int)$estimatedVoteSplit['total'] ?></strong>
					</span>
				<?php endif; ?>
			</div>
			<div class="faq-popup__vote-actions">
				<button
					type="button"
					class="faq-popup__vote-button"
					data-faq-vote="up"
					data-faq-id="<?= $faqId ?>"
					<?= $hasVotedToday ? ' disabled' : '' ?>
				>👍 Utile</button>
				<button
					type="button"
					class="faq-popup__vote-button"
					data-faq-vote="down"
					data-faq-id="<?= $faqId ?>"
					<?= $hasVotedToday ? ' disabled' : '' ?>
				>👎 Pas utile</button>
			</div>
			<?php if (!$isCompact): ?>
				<div class="faq-popup__vote-note">Repartition indicative basee sur le signal actif.</div>
			<?php endif; ?>
			<div class="faq-popup__vote-message<?= $voteMessage !== '' ? ' is-visible' : '' ?>" data-faq-vote-message>
				<?= htmlspecialchars($voteMessage, ENT_QUOTES, 'UTF-8') ?>
			</div>
		</div>
		<?php
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

if (!function_exists('faqPopupLoadParcoursOptions')) {
	function faqPopupLoadParcoursOptions(array $faqContext, array $organizations)
	{
		$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext);
		$options = array();

		if (!empty($viewerAccess['canManageAllFaqs'])) {
			foreach ($organizations as $organization) {
				if (!$organization instanceof \dbObject\Organization) {
					continue;
				}

				$organizationId = (int)$organization->getId();
				if ($organizationId <= 0) {
					continue;
				}

				foreach (\dbObject\Parcours::fetchOwnedFaqTargetsForOrganization($organizationId) as $parcoursOption) {
					$parcoursId = (int)($parcoursOption['id'] ?? 0);
					$parcoursTitle = trim((string)($parcoursOption['title'] ?? ''));
					if ($parcoursId <= 0 || $parcoursTitle === '') {
						continue;
					}

					$options[] = array(
						'id' => $parcoursId,
						'organizationId' => $organizationId,
						'title' => $parcoursTitle,
						'organizationLabel' => trim((string)$organization->getLabel()),
					);
				}
			}

			return $options;
		}

		$organizationId = (int)($faqContext['organizationId'] ?? 0);
		if ($organizationId <= 0) {
			return $options;
		}

		foreach (\dbObject\Parcours::fetchOwnedFaqTargetsForOrganization($organizationId) as $parcoursOption) {
			$parcoursId = (int)($parcoursOption['id'] ?? 0);
			$parcoursTitle = trim((string)($parcoursOption['title'] ?? ''));
			if ($parcoursId <= 0 || $parcoursTitle === '') {
				continue;
			}

			$options[] = array(
				'id' => $parcoursId,
				'organizationId' => $organizationId,
				'title' => $parcoursTitle,
				'organizationLabel' => '',
			);
		}

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
		$selectedParcoursId = \dbObject\FAQ::hasParcoursColumn() ? (int)$faq->get('IDparcours') : 0;
		$canLinkApplication = \dbObject\FAQ::hasApplicationColumn()
			&& ($canManageAllFaqs || $canManageOrganizationFaqs);
		$selectedApplicationId = $canLinkApplication ? (int)$faq->get('IDapplication') : 0;
		$contextOrganizationId = (int)($faqContext['organizationId'] ?? 0);
		$contextOrganization = ($faqContext['organization'] ?? null) instanceof \dbObject\Organization
			? $faqContext['organization']
			: null;
		$selectedAttachmentType = 'organization';

		if ($selectedParcoursId > 0) {
			$selectedAttachmentType = 'parcours';
		} elseif ($faq->isGeneric() && $allowGeneric) {
			$selectedAttachmentType = 'generic';
		}

		if ($selectedOrganizationId <= 0 && !$canManageAllFaqs) {
			$selectedOrganizationId = $contextOrganizationId;
		}

		$organizations = faqPopupLoadOrganizationOptions($faqContext, $faq);
		$holons = faqPopupLoadHolonOptions($faqContext, $organizations);
		$parcoursOptions = faqPopupLoadParcoursOptions($faqContext, $organizations);
		$applicationOptions = $canLinkApplication ? \dbObject\Application::fetchFaqAttachmentOptions() : array();
		$hasScopeControls = $canManageAllFaqs || $canManageOrganizationFaqs || !$isContextualOnly;

		if (!$hasScopeControls) {
			return;
		}
		?>
		<div class="faq-popup__scope-grid" data-faq-scope-fields>
			<input type="hidden" name="IDorganization" value="<?= $selectedOrganizationId > 0 ? $selectedOrganizationId : $contextOrganizationId ?>">
			<input type="hidden" name="IDholon" value="<?= $selectedHolonId > 0 ? $selectedHolonId : '' ?>">
			<input type="hidden" name="IDparcours" value="<?= $selectedParcoursId > 0 ? $selectedParcoursId : '' ?>">
			<?php if ($isContextualOnly): ?>
				<div class="faq-popup__scope-field">
					<label class="faq-popup__scope-label">Attachement</label>
					<div class="faq-popup__scope-fixed">
						<?= htmlspecialchars($selectedHolonId > 0 ? 'Holon courant' : 'Organisation courante', ENT_QUOTES, 'UTF-8') ?>
					</div>
				</div>
			<?php else: ?>
				<?php if ($canManageAllFaqs): ?>
					<div class="faq-popup__scope-field faq-popup__scope-field--full" data-faq-scope-organization-shell>
						<label class="faq-popup__scope-label" for="faqScopeOrganization">Organisation</label>
						<select
							class="faq-popup__scope-control"
							id="faqScopeOrganization"
							data-faq-scope-organization
						>
							<option value="">Choisir une organisation</option>
							<?php foreach ($organizations as $organization): ?>
								<?php if (!$organization instanceof \dbObject\Organization) {
									continue;
								} ?>
								<option value="<?= (int)$organization->getId() ?>"<?= $selectedOrganizationId === (int)$organization->getId() ? ' selected' : '' ?>>
									<?= htmlspecialchars(trim((string)$organization->getLabel()), ENT_QUOTES, 'UTF-8') ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<div class="faq-popup__scope-field">
					<label class="faq-popup__scope-label" for="faqScopeType">Attachement</label>
					<select
						class="faq-popup__scope-control"
						id="faqScopeType"
						data-faq-scope-kind
					>
						<option value="organization"<?= $selectedAttachmentType === 'organization' ? ' selected' : '' ?>>Organisation courante</option>
						<option value="parcours"<?= $selectedAttachmentType === 'parcours' ? ' selected' : '' ?>>Parcours</option>
						<?php if ($allowGeneric): ?>
							<option value="generic"<?= $selectedAttachmentType === 'generic' ? ' selected' : '' ?>>FAQ generique</option>
						<?php endif; ?>
					</select>
				</div>
				<?php if ($canLinkApplication): ?>
					<div class="faq-popup__scope-field">
						<label class="faq-popup__scope-label" for="faqScopeApplication">Application</label>
						<select
							class="faq-popup__scope-control"
							id="faqScopeApplication"
							name="IDapplication"
						>
							<option value="">Toutes les applications</option>
							<?php foreach ($applicationOptions as $applicationOption): ?>
								<?php
								$applicationOptionId = (int)($applicationOption['id'] ?? 0);
								$applicationOptionLabel = trim((string)($applicationOption['label'] ?? ''));
								if ($applicationOptionId <= 0 || $applicationOptionLabel === '') {
									continue;
								}
								?>
								<option value="<?= $applicationOptionId ?>"<?= $selectedApplicationId === $applicationOptionId ? ' selected' : '' ?>>
									<?= htmlspecialchars($applicationOptionLabel, ENT_QUOTES, 'UTF-8') ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
				<div class="faq-popup__scope-field" data-faq-scope-holon-shell>
					<label class="faq-popup__scope-label" for="faqScopeHolon">Holon</label>
					<select
						class="faq-popup__scope-control"
						id="faqScopeHolon"
						data-faq-scope-holon
					>
						<option value="">Toute l organisation</option>
						<?php foreach ($holons as $holonOption): ?>
							<?php
							$holonOptionId = (int)($holonOption['id'] ?? 0);
							$holonLabel = trim((string)($holonOption['label'] ?? ''));
							if ($holonOptionId <= 0 || $holonLabel === '') {
								continue;
							}
							?>
							<option
								value="<?= $holonOptionId ?>"
								data-organization-id="<?= (int)($holonOption['organizationId'] ?? 0) ?>"
								<?= $selectedHolonId === $holonOptionId ? ' selected' : '' ?>
							>
								<?= htmlspecialchars($holonLabel, ENT_QUOTES, 'UTF-8') ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="faq-popup__scope-field" data-faq-scope-parcours-shell>
					<label class="faq-popup__scope-label" for="faqScopeParcours">Parcours</label>
					<select
						class="faq-popup__scope-control"
						id="faqScopeParcours"
						data-faq-scope-parcours
					>
						<option value="">Choisir un parcours</option>
						<?php foreach ($parcoursOptions as $parcoursOption): ?>
							<?php
							$parcoursOptionId = (int)($parcoursOption['id'] ?? 0);
							$parcoursOptionTitle = trim((string)($parcoursOption['title'] ?? ''));
							$parcoursOrganizationId = (int)($parcoursOption['organizationId'] ?? 0);
							$parcoursOrganizationLabel = trim((string)($parcoursOption['organizationLabel'] ?? ''));
							if ($parcoursOptionId <= 0 || $parcoursOptionTitle === '') {
								continue;
							}
							?>
							<option
								value="<?= $parcoursOptionId ?>"
								data-organization-id="<?= $parcoursOrganizationId ?>"
								<?= $selectedParcoursId === $parcoursOptionId ? ' selected' : '' ?>
							>
								<?= htmlspecialchars($parcoursOptionTitle . ($parcoursOrganizationLabel !== '' ? ' (' . $parcoursOrganizationLabel . ')' : ''), ENT_QUOTES, 'UTF-8') ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
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
		$parcoursId = isset($postData['IDparcours']) && is_numeric($postData['IDparcours'])
			? (int)$postData['IDparcours']
			: 0;
		$attachmentType = trim((string)($postData['faq_scope_kind'] ?? ''));
		$holon = null;
		$parcours = null;

		if ($attachmentType === '') {
			if ($parcoursId > 0) {
				$attachmentType = 'parcours';
			} elseif ($holonId > 0 || $organizationId > 0) {
				$attachmentType = 'organization';
			}
		}

		if ($holonId > 0) {
			$holon = new \dbObject\Holon();
			if (!$holon->load($holonId)) {
				return array(
					'status' => false,
					'message' => 'Holon invalide.',
				);
			}
		}

		if ($parcoursId > 0) {
			$parcours = new \dbObject\Parcours();
			if (!$parcours->load($parcoursId)) {
				return array(
					'status' => false,
					'message' => 'Parcours invalide.',
				);
			}
		}

		if ($attachmentType === 'parcours' && $holonId > 0) {
			return array(
				'status' => false,
				'message' => 'Une FAQ rattachee a un parcours ne peut pas etre rattachee a un holon.',
			);
		}

		if ($attachmentType === 'organization') {
			$parcoursId = 0;
			$parcours = null;
		} elseif ($attachmentType === 'parcours') {
			$holonId = 0;
			$holon = null;
		}

		if ($canManageAllFaqs) {
			if ($attachmentType === 'generic') {
				return array(
					'status' => true,
					'organizationId' => null,
					'holonId' => null,
					'parcoursId' => null,
					'holon' => null,
					'parcours' => null,
				);
			}

			if ($organizationId <= 0) {
				return array(
					'status' => false,
					'message' => 'Organisation invalide.',
				);
			}

			$organization = new \dbObject\Organization();
			if (!$organization->load($organizationId)) {
				return array(
					'status' => false,
					'message' => 'Organisation invalide.',
				);
			}

			if ($holon && (int)$holon->get('IDorganization') !== $organizationId) {
				return array(
					'status' => false,
					'message' => 'Le holon selectionne n appartient pas a l organisation selectionnee.',
				);
			}

			if ($parcours) {
				$availableParcoursIds = \dbObject\Parcours::fetchOwnedFaqTargetIdsForOrganization($organizationId);
				if (!in_array((int)$parcours->getId(), $availableParcoursIds, true)) {
					return array(
						'status' => false,
						'message' => 'Le parcours selectionne n appartient pas a l organisation selectionnee.',
					);
				}
			}

			return array(
				'status' => true,
				'organizationId' => $organizationId > 0 ? $organizationId : null,
				'holonId' => $holon ? (int)$holon->getId() : null,
				'parcoursId' => $parcours ? (int)$parcours->getId() : null,
				'holon' => $holon,
				'parcours' => $parcours,
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

			if ($attachmentType === 'generic') {
				return array(
					'status' => false,
					'message' => 'Seul un super admin peut creer une FAQ generique.',
				);
			}

			if ($holon && (int)$holon->get('IDorganization') !== $contextOrganizationId) {
				return array(
					'status' => false,
					'message' => 'Le holon selectionne n appartient pas a l organisation courante.',
				);
			}

			if ($parcours) {
				$availableParcoursIds = \dbObject\Parcours::fetchOwnedFaqTargetIdsForOrganization($contextOrganizationId);
				if (!in_array((int)$parcours->getId(), $availableParcoursIds, true)) {
					return array(
						'status' => false,
						'message' => 'Le parcours selectionne n appartient pas a l organisation courante.',
					);
				}
			}

			return array(
				'status' => true,
				'organizationId' => $contextOrganizationId,
				'holonId' => $holon ? (int)$holon->getId() : null,
				'parcoursId' => $parcours ? (int)$parcours->getId() : null,
				'holon' => $holon,
				'parcours' => $parcours,
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
				'parcoursId' => null,
				'holon' => $currentHolon,
				'parcours' => null,
			);
		}

		return array(
			'status' => false,
			'message' => 'Vous n avez pas le droit de modifier le scope de cette FAQ.',
		);
	}
}
