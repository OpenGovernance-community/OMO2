<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../omo/api/lms/inc/access.php");
require_once("../common/faq_popup_helper.php");
require_once("../common/faq_ai.php");

$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);

if ($faqContext === false) {
	http_response_code(403);
	?>
	<div class="faq-popup__empty generic-drawer-content">Contexte FAQ invalide.</div>
	<?php
	return;
}

$contextHolon = $faqContext['currentHolon'] ?? null;
$rootHolon = $faqContext['rootHolon'] ?? null;
$contextHolonId = $contextHolon ? (int)$contextHolon->getId() : 0;
$contextOrganizationId = (int)($faqContext['organizationId'] ?? 0);
$faqAvailableScopes = \dbObject\FAQ::getAvailablePopupScopes($faqContext ?: array());
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());
$faqScopeLabels = array(
	'contextual' => 'Local',
	'children' => 'Enfants directs',
	'descendants' => 'Descendants',
);
$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION['currentUser'] ?? 0);
$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
$canManageAllFaqs = !empty($viewerAccess['canManageAllFaqs']);
$canManageOrganizationFaqs = !empty($viewerAccess['canManageOrganizationFaqs']);
$canManageFaqCollection = $canManageAllFaqs || $canManageOrganizationFaqs;
$faqStorageAvailable = \dbObject\FAQ::hasFaqTable();
$usePermissionSessionCache = $_SERVER['REQUEST_METHOD'] !== 'POST';
$canCreateContextualFaq = $contextHolon
	? \dbObject\FAQ::canCreateContextualForHolon($contextHolon, $currentUserId, $contextOrganizationId, $usePermissionSessionCache)
	: false;
$canCreateParcoursFaqs = $faqStorageAvailable
	? faqPopupCanCreateParcoursFaqs($faqContext ?: array(), $currentUserId, $usePermissionSessionCache)
	: false;
$canAddFaq = $faqStorageAvailable && ($canManageFaqCollection || $canCreateContextualFaq || $canCreateParcoursFaqs);

$allFAQ = \dbObject\FAQ::loadPopupCollection($faqContext ?: array(), $faqScope);
$faqReliabilityRange = faqPopupBuildReliabilityRange($allFAQ);
$defaultVisibleFaqCount = $canManageFaqCollection ? (int)count($allFAQ) : 5;
$initialRemainingFaqCount = max(0, count($allFAQ) - $defaultVisibleFaqCount);
$initialLoadMoreCount = min($defaultVisibleFaqCount, $initialRemainingFaqCount);
$contextOrganization = $faqContext['organization'] ?? null;
$contextOrganizationLabel = $contextOrganization instanceof \dbObject\Organization
	? trim((string)$contextOrganization->getLabel())
	: '';
$contextHolonLabel = $contextHolon instanceof \dbObject\Holon
	? trim((string)$contextHolon->getDisplayName())
	: '';
$heroSubtitle = $faqScope === 'children'
	? 'Aide du contexte et de ses enfants directs'
	: ($faqScope === 'descendants'
		? 'Aide du contexte et de ses descendants'
		: 'Aide du contexte courant');

$newFaq = new \dbObject\FAQ();
$newFaq->set('IDorganization', $contextOrganizationId > 0 ? $contextOrganizationId : null);
$newFaq->set('IDholon', $contextHolonId > 0 ? $contextHolonId : null);
$newFaq->set('IDparcours', null);
$newFaq->set('IDapplication', null);
$newFaq->set('isactive', true);

$popupReloadUrl = '/popup/faq.php';
$popupReloadQuery = array();
if ($contextOrganizationId > 0) {
	$popupReloadQuery[] = 'oid=' . rawurlencode((string)$contextOrganizationId);
}
if ($contextHolonId > 0) {
	$popupReloadQuery[] = 'cid=' . rawurlencode((string)$contextHolonId);
}
if ($faqScope !== 'contextual') {
	$popupReloadQuery[] = 'faq_scope=' . rawurlencode($faqScope);
}
if (count($popupReloadQuery) > 0) {
	$popupReloadUrl .= '?' . implode('&', $popupReloadQuery);
}

$editorTitle = 'Nouvelle FAQ locale';
$editorStatus = $contextHolon
	? 'Cette FAQ sera rattachee au holon courant.'
	: 'Cette FAQ sera creee dans le contexte courant.';
$editorAllowScopeEditing = false;
$editorAllowGeneric = false;
$editorAllowParcoursAttachment = false;
$editorAllowContextualAttachment = false;
$editorFields = array(
	'question',
	'answer',
	'image',
	'video',
	'detail',
);

if ($canManageAllFaqs) {
	$editorTitle = 'Nouvelle FAQ';
	$editorStatus = 'Vous pouvez la rattacher a l organisation courante, a un parcours LMS, ou la laisser generique.';
	$editorAllowScopeEditing = true;
	$editorAllowGeneric = true;
	$editorFields[] = 'displayorder';
	$editorFields[] = 'isactive';
} elseif ($canManageOrganizationFaqs) {
	$editorTitle = 'Nouvelle FAQ organisation';
	$editorStatus = 'Cette FAQ peut etre rattachee a l organisation courante, a un holon, ou a un parcours LMS disponible.';
	$editorAllowScopeEditing = true;
	$editorFields[] = 'displayorder';
	$editorFields[] = 'isactive';
} elseif ($canCreateParcoursFaqs) {
	$editorTitle = 'Nouvelle FAQ de parcours';
	$editorStatus = 'Cette FAQ sera rattachee a un parcours LMS que vous pouvez gerer.';
	$editorAllowScopeEditing = true;
	$editorAllowParcoursAttachment = true;
	$editorAllowContextualAttachment = $canCreateContextualFaq;
}

?>
<link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
<div
	class="faq-popup"
	id="faqPopupRoot"
	data-faq-oid="<?= (int)$contextOrganizationId ?>"
	data-faq-cid="<?= (int)$contextHolonId ?>"
	data-faq-scope="<?= htmlspecialchars($faqScope, ENT_QUOTES, 'UTF-8') ?>"
	data-faq-reload-url="<?= htmlspecialchars($popupReloadUrl, ENT_QUOTES, 'UTF-8') ?>"
	data-faq-default-visible="<?= $defaultVisibleFaqCount ?>"
>
	<link rel="stylesheet" href="<?= commonAssetUrl('/omo/assets/css/faq.css') ?>">

	<div class="faq-popup__search generic-stack generic-stack--roomy" data-faq-search-view>
		<div class="faq-popup__hero generic-stack generic-stack--roomy">

			<?php if ($contextOrganizationId > 0 || count($allFAQ) > 0 || $canAddFaq): ?>
				<div class="faq-popup__toolbar">
					<div class="faq-popup__toolbar-main generic-stack generic-stack--compact">
						<?php if ($contextOrganizationId > 0): ?>
							<div
								class="faq-popup__scope-toggle omo-segmented"
								role="tablist"
								aria-label="Portee de la FAQ"
								data-faq-scope-switch="<?= htmlspecialchars($faqScope, ENT_QUOTES, 'UTF-8') ?>"
							>
								<?php foreach ($faqAvailableScopes as $scopeOption): ?>
									<button
										<?php if ($scopeOption === 'contextual' && $contextHolonLabel !== ''): ?>
											title="<?= htmlspecialchars($contextHolonLabel, ENT_QUOTES, 'UTF-8') ?>"
										<?php endif; ?>
										type="button"
										class="faq-popup__scope-toggle-button omo-segmented__button<?= $faqScope === $scopeOption ? ' is-active' : '' ?>"
										data-faq-scope-toggle="<?= htmlspecialchars($scopeOption, ENT_QUOTES, 'UTF-8') ?>"
										data-omo-scope-option="<?= htmlspecialchars($scopeOption, ENT_QUOTES, 'UTF-8') ?>"
										aria-pressed="<?= $faqScope === $scopeOption ? 'true' : 'false' ?>"
									><span class="omo-segmented__text"><?= htmlspecialchars((string)($faqScopeLabels[$scopeOption] ?? $scopeOption), ENT_QUOTES, 'UTF-8') ?></span></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						
					</div>
					<div class="faq-popup__toolbar-actions generic-action-row">
						<?php if ($canAddFaq): ?>
							<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-add>Ajouter une question</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if (count($allFAQ) > 0): ?>
				<div class="faq-popup__search-row">
					<label class="faq-popup__search-shell generic-form-control generic-form-control--compact">
						<img src="/common/assets/icon-topbar-search.png" alt="" class="faq-popup__search-icon black-icon" aria-hidden="true">
						<input
							type="search"
							class="faq-popup__search-input"
							data-faq-search-input
							placeholder="Rechercher une question, une reponse, un mot-cle..."
							aria-label="Rechercher dans la FAQ"
						>
					</label>
				</div>
			<?php endif; ?>
		</div>
		<?php if (!$faqStorageAvailable): ?>
			<div class="faq-popup__helper generic-soft-panel generic-help-text generic-help-text--regular"><strong>Module FAQ indisponible.</strong> La table `faq` n existe pas encore dans cette base. Lancez les migrations SQL pour activer cette fonctionnalite.</div>
		<?php endif; ?>
		<div class="faq-popup__no-result generic-soft-panel generic-help-text generic-help-text--regular" data-faq-no-result hidden>
			Aucune FAQ ne correspond a cette recherche.
		</div>
		<?php if (count($allFAQ) === 0): ?>
			<div class="faq-popup__empty generic-soft-panel generic-help-text generic-help-text--regular">Aucune FAQ n'est disponible pour le moment.</div>
		<?php endif; ?>
		<div class="faq-popup__list generic-stack generic-stack--compact" data-faq-list<?= count($allFAQ) === 0 ? ' hidden' : '' ?>>
			<?php $faqIndex = 0; ?>
			<?php foreach ($allFAQ as $faq): ?>
				<?php
				$scopeInfo = faqPopupDescribeScope($faq);
				$scopeTypeClass = 'faq-popup__meta-badge';
				$scopeIconClass = 'faq-popup__item-icon';
				if (($scopeInfo['type'] ?? '') === 'generic') {
					$scopeTypeClass .= ' faq-popup__meta-badge--generic';
					$scopeIconClass .= ' faq-popup__item-icon--generic';
				} elseif (($scopeInfo['type'] ?? '') === 'organization') {
					$scopeTypeClass .= ' faq-popup__meta-badge--organization';
					$scopeIconClass .= ' faq-popup__item-icon--organization';
				}
				?>
				<div
					class="faq-popup__item generic-soft-panel generic-soft-panel--flush<?= $faqIndex === 0 ? ' is-open' : '' ?>"
					data-faq-item
					data-faq-id="<?= (int)$faq->get("id") ?>"
					data-faq-default-order="<?= $faqIndex ?>"
					data-faq-viewcount="<?= (int)$faq->get("viewcount") ?>"
				>
					<button type="button" class="faq-popup__item-header" data-faq-toggle>
						<span class="<?= $scopeIconClass ?>" aria-hidden="true">?</span>
						<span class="faq-popup__item-heading generic-stack generic-stack--compact">
							<span class="faq-popup__question generic-title generic-title--card"><?= htmlspecialchars((string)$faq->get("question")) ?></span>
							<span class="faq-popup__meta">
								<span class="<?= $scopeTypeClass ?>"><?= htmlspecialchars((string)($scopeInfo['label'] ?? 'FAQ'), ENT_QUOTES, 'UTF-8') ?></span>
								<?php if (!(int)$faq->get('isactive')): ?>
									<span class="faq-popup__meta-badge faq-popup__meta-badge--generic"><?= $faq->isPendingRequest() ? 'À traiter' : 'Inactive' ?></span>
								<?php endif; ?>
								<?php if ($faq->canBeEditedInContext($faqContext ?: array())): ?>
									<span class="faq-popup__meta-badge">Editable</span>
								<?php endif; ?>
							</span>
						</span>
						<span class="faq-popup__item-toggle" aria-hidden="true"></span>
					</button>
					<div class="faq-popup__answer" data-faq-answer>
						<div class="faq-popup__answer-body generic-stack generic-stack--compact">
							<div class="faq-popup__answer-caption generic-meta-label">Reponse resumee</div>
							<div class="faq-popup__answer-text generic-description generic-description--card generic-description--primary" data-faq-answer-text><?= nl2br(htmlspecialchars($faq->getShortAnswer(220))) ?></div>
						</div>
						<div class="faq-popup__answer-footer">
							<div class="faq-popup__actions generic-action-row">
								<button
									type="button"
									class="faq-popup__detail-link"
									data-faq-detail
									data-faq-id="<?= (int)$faq->get("id") ?>"
								>Voir le detail</button>
							</div>
							<?php faqPopupRenderVoteBlock($faq, array('compact' => true, 'reliabilityRange' => $faqReliabilityRange)); ?>
						</div>
					</div>
				</div>
				<?php $faqIndex++; ?>
			<?php endforeach; ?>
		</div>
		<div class="faq-popup__ask generic-soft-panel generic-stack generic-stack--compact" data-faq-ask-shell hidden>
			<div>Pas trouvé de réponse à votre problème dans cette liste ? Posez la question ci-dessous, et un administrateur vous répondra rapidement.</div>
			<button type="button" class="generic-action-button generic-action-button--main" data-faq-ask>Poser la question</button>
		</div>
		<div class="faq-popup__footer" data-faq-load-more-shell<?= $initialRemainingFaqCount > 0 ? '' : ' hidden' ?>>
			<button type="button" class="faq-popup__detail-link" data-faq-load-more<?= $initialRemainingFaqCount > 0 ? '' : ' hidden' ?>>Voir <?= (int)$initialLoadMoreCount ?> de plus</button>
			<div class="faq-popup__load-more-note">Ou chercher par mot cle en haut de la page.</div>
		</div>
	</div>

	<div class="faq-popup__detail" data-faq-detail-view hidden></div>
	<div class="faq-popup__detail" data-faq-request-view hidden>
		<div class="faq-popup__editor-shell">
			<form data-faq-request-form class="generic-drawer-content">
				<?php faqPopupRenderBackButton(); ?>
				<h4 class="generic-title generic-title--section" data-faq-view-title>Poser une question</h4>
				<label class="generic-form-field"><span class="generic-form-label">Votre question</span><input class="generic-form-control" type="text" name="question" maxlength="255" required></label>
				<label class="generic-form-field"><span class="generic-form-label">Description du problème</span><textarea class="generic-form-control" name="request_description" rows="5" maxlength="10000" required></textarea></label>
				<div class="generic-action-row"><button type="submit" class="generic-action-button generic-action-button--main">Envoyer</button></div>
				<div data-faq-request-message role="status" aria-live="polite"></div>
				<p data-faq-ai-notice class="generic-help-text" role="status" aria-live="polite" hidden><?= htmlspecialchars(faqAiT('notice'), ENT_QUOTES, 'UTF-8') ?></p>
				<div data-faq-ai-draft class="generic-soft-panel generic-stack" aria-live="polite" hidden>
					<div data-faq-ai-draft-text class="generic-stack generic-stack--compact"></div>
				</div>
			</form>
		</div>
	</div>
	<?php if ($canAddFaq): ?>
		<div class="faq-popup__detail" data-faq-editor-view hidden>
			<div class="faq-popup__editor-shell" data-faq-form-shell>
				<div class="generic-drawer-content">
					<?php faqPopupRenderBackButton(); ?>
					<h4 class="generic-title generic-title--section" data-faq-view-title><?= htmlspecialchars($editorTitle, ENT_QUOTES, 'UTF-8') ?></h4>
				<p class="faq-popup__editor-status generic-help-text generic-help-text--regular">
					<?= htmlspecialchars($editorStatus, ENT_QUOTES, 'UTF-8') ?>
				</p>
				<?php
				if ($canCreateContextualFaq && !$canManageFaqCollection) {
					$newFaq->set('IDholon', $contextHolonId > 0 ? $contextHolonId : null);
				}
				?>
				<?php faqPopupRenderScopeFields($newFaq, $faqContext ?: array(), array(
					'allowScopeEditing' => $editorAllowScopeEditing,
					'allowGeneric' => $editorAllowGeneric,
					'allowParcoursAttachment' => $editorAllowParcoursAttachment,
					'allowContextualAttachment' => $editorAllowContextualAttachment,
					)); ?>
				<?php
				$params = array(
					'buttons' => false,
					'action' => '/ajax/faq_save.php?oid=' . rawurlencode((string)$contextOrganizationId) . '&cid=' . rawurlencode((string)$contextHolonId) . '&faq_scope=' . rawurlencode($faqScope),
					'fields' => $editorFields,
					'sections' => faqPopupEditorSections(),
					'includeComponentAssets' => false,
				);
				$newFaq->display('adminEdit.php', $params);
				?>
				</div>
				<footer class="faq-popup__editor-actions generic-drawer-footer generic-drawer-footer--sticky">
					<button type="button" class="faq-popup__back generic-action-button generic-action-button--secondary" data-faq-back>Annuler</button>
					<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-save>Enregistrer</button>
				</footer>
			</div>
		</div>
	<?php endif; ?>
</div>
<script src="<?= commonAssetUrl('/omo/assets/js/faq.js') ?>"></script>
