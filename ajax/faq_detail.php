<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../omo/api/lms/inc/access.php");
require_once("../common/faq_popup_helper.php");
require_once("../common/faq_ai.php");

$faqId = (int)($_GET["id"] ?? 0);
if ($faqId <= 0) {
	die("FAQ invalide");
}

$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());

if ($faqContext === false) {
	die("Contexte FAQ invalide");
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || !(int)$faq->get("id")) {
	die("FAQ introuvable");
}

$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
$isSiteAdmin = !empty($viewerAccess['canManageAllFaqs']);
$canEditFaq = $faq->canBeEditedInContext($faqContext ?: array());
if (!$faq->canBeViewedInContext($faqContext ?: array(), $faqScope) && !$canEditFaq) {
	die("Vous n'avez pas le droit de consulter ou de modifier cette FAQ.");
}

$isPendingRequest = $faq->isPendingRequest();
$isRequestRelayed = $faq->hasRequestBeenRelayed();
$editRequest = (string)($_GET['edit'] ?? '');
$isEditMode = $editRequest === 'auto'
	? (!(int)$faq->get('isactive')
		&& $isPendingRequest
		&& !$isRequestRelayed
		&& $canEditFaq)
	: ($editRequest !== '' && $editRequest !== '0');
$canEditFaqScope = \dbObject\FAQ::currentViewerHasOrganizationAdminAccess($faq->getResolvedOrganizationId());
$canDeleteFaq = $faq->canBeDeletedInContext($faqContext ?: array());
$canManageParcoursFaqs = \dbObject\FAQ::canManageParcoursInContext($faqContext ?: array(), 0, true);
$scopeInfo = faqPopupDescribeScope($faq);

if ($isEditMode) {
	if (!$canEditFaq) {
		die("Vous n'avez pas le droit d'editer cette FAQ.");
	}

	$editorFields = array(
		'question',
		'answer',
		'image',
		'video',
		'detail',
		'displayorder',
		'isactive',
	);
	$editorTitle = faqPopupT('editor.title');
	$editorStatus = faqPopupT($isPendingRequest ? ($isRequestRelayed ? 'editor.relayed_help' : 'editor.pending_help') : 'editor.edit_help');
	$allowGeneric = $faq->canBeDetachedInContext($faqContext ?: array());
	?>
	<div>
		<div class="faq-popup__editor-shell" data-faq-form-shell>
			<div class="generic-drawer-content">
			<?php faqPopupRenderBackButton((int)$faq->getId()); ?>
			<h4 class="generic-title generic-title--section" data-faq-view-title><?= htmlspecialchars($editorTitle, ENT_QUOTES, 'UTF-8') ?></h4>
			<?php faqPopupRenderRequestInfo($faq); ?>
			<p class="faq-popup__editor-status generic-help-text generic-help-text--regular">
				<?= htmlspecialchars($editorStatus, ENT_QUOTES, 'UTF-8') ?>
			</p>
			<?php if ($faq->hasAiDraft()): ?>
				<p class="generic-help-text generic-help-text--regular"><?= htmlspecialchars(faqAiT('review'), ENT_QUOTES, 'UTF-8') ?></p>
			<?php endif; ?>
			<?php if (!$isPendingRequest): ?>
				<?php faqPopupRenderScopeFields($faq, $faqContext ?: array(), array(
					'allowScopeEditing' => $canEditFaqScope,
					'allowGeneric' => $allowGeneric,
					'allowParcoursAttachment' => $canManageParcoursFaqs,
				)); ?>
			<?php endif; ?>
			<?php
			$params = array(
				'buttons' => false,
				'action' => '/ajax/faq_update.php?id=' . rawurlencode((string)$faq->getId()) . '&oid=' . rawurlencode((string)($faqContext['organizationId'] ?? 0)) . '&cid=' . rawurlencode((string)($faqContext['currentHolonId'] ?? 0)) . '&faq_scope=' . rawurlencode($faqScope),
				'fields' => $editorFields,
				'sections' => faqPopupEditorSections(),
				'includeComponentAssets' => false,
			);
			$faq->display('adminEdit.php', $params);
			?>
			</div>
			<footer class="faq-popup__editor-actions generic-drawer-footer generic-drawer-footer--sticky">
				<button type="button" class="faq-popup__back generic-action-button generic-action-button--secondary" data-faq-cancel-edit data-faq-id="<?= (int)$faq->getId() ?>">Annuler</button>
				<div class="generic-action-row">
				<?php if ($isPendingRequest && $isSiteAdmin): ?>
					<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-save data-faq-request-resolution="generic">Sauver comme FAQ générique</button>
					<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-save data-faq-request-resolution="organization">Sauver comme FAQ d’orga</button>
				<?php elseif ($isPendingRequest): ?>
					<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-save data-faq-request-resolution="organization">Sauver comme FAQ d’orga</button>
				<?php else: ?>
					<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-save>Enregistrer</button>
				<?php endif; ?>
				</div>
			</footer>
		</div>
	</div>
	<?php
	return;
}

if (\dbObject\FAQ::hasViewcountColumn()) {
	$faq->incrementViewcount();
}
?>
<div class="faq-popup__detail-content">
	<div class="generic-drawer-content">
		<div class="generic-title-row generic-title-row--center">
			<?php faqPopupRenderBackButton(); ?>
			<div class="generic-action-row">
				<?php if ($canEditFaq): ?>
					<button type="button" class="faq-popup__edit generic-action-button generic-action-button--secondary" data-faq-edit data-faq-id="<?= (int)$faq->getId() ?>">Editer</button>
				<?php endif; ?>
				<?php if ($canDeleteFaq): ?>
					<button type="button" class="generic-action-button generic-action-button--danger generic-action-button--icon-only" data-faq-delete data-faq-id="<?= (int)$faq->getId() ?>" data-faq-delete-confirm="Supprimer cette FAQ ? Cette action est definitive." title="Supprimer" aria-label="Supprimer">
						<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M5 7h14M10 11v6M14 11v6M9 7V5h6v2m-9 0 1 13h10l1-13"></path></svg>
					</button>
				<?php endif; ?>
			</div>
		</div>
				<div class="faq-popup__meta">
					<span class="faq-popup__meta-badge<?= ($scopeInfo['type'] ?? '') === 'generic' ? ' faq-popup__meta-badge--generic' : (($scopeInfo['type'] ?? '') === 'organization' ? ' faq-popup__meta-badge--organization' : '') ?>">
						<?= htmlspecialchars((string)($scopeInfo['label'] ?? 'FAQ'), ENT_QUOTES, 'UTF-8') ?>
					</span>
					<?php if (!(int)$faq->get('isactive')): ?>
						<span class="faq-popup__meta-badge faq-popup__meta-badge--generic">Inactive</span>
					<?php endif; ?>
					<?php if ($faq->canBeDetachedInContext($faqContext ?: array())): ?>
						<span class="faq-popup__meta-badge">Detach possible</span>
					<?php endif; ?>
				</div>
		<h4 class="generic-title generic-title--section" data-faq-view-title><?= htmlspecialchars((string)$faq->get("question")) ?></h4>
		<?php if ($canEditFaq) faqPopupRenderRequestInfo($faq); ?>
		<div class="generic-description generic-stack generic-stack--compact">
			<?php if ($faq->hasAiDraft()): ?>
				<p class="generic-help-text"><?= htmlspecialchars(faqAiT('review'), ENT_QUOTES, 'UTF-8') ?></p>
			<?php endif; ?>
			<?= trim((string)$faq->get("answer")) !== '' ? nl2br(htmlspecialchars((string)$faq->get("answer"), ENT_QUOTES, 'UTF-8')) : ($canEditFaq && (int)$faq->get('request_user_id') > 0 ? 'Cette question attend votre réponse.' : '') ?>
		</div>
		<?php faqPopupRenderMediaBlock($faq); ?>
		<?php if ((string)$faq->get("detail") !== ''): ?>
			<div class="generic-soft-panel generic-description">
				<?= (string)$faq->get("detail") ?>
			</div>
		<?php endif; ?>
		<?php faqPopupRenderVoteBlock($faq); ?>
		<?php if (\dbObject\FAQ::hasViewcountColumn()): ?>
			<div class="generic-help-text">
				Consultations: <?= (int)$faq->get("viewcount") ?>
			</div>
		<?php endif; ?>
	</div>
</div>
