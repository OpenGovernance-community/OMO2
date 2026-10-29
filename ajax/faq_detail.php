<?php
require_once("../config.php");
require_once("../shared_functions.php");
commonEnableReadOnlyCaches();
require_once("../common/auth.php");
require_once("../common/faq_popup_helper.php");
require_once("../common/faq_ai.php");
$responderRequest = \dbObject\FAQ::loadRequestForResponder((int)($_GET['id'] ?? 0));
if (!$responderRequest) require_once("../omo/api/lms/inc/access.php");

$faqId = (int)($_GET["id"] ?? 0);
if ($faqId <= 0) {
	die(faqPopupT('faq_invalide'));
}

$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET, $responderRequest);
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());

if ($faqContext === false) {
	die(faqPopupT('contexte_faq_invalide_2'));
}

$faq = $responderRequest ?? new \dbObject\FAQ();
if ((!$responderRequest && !$faq->load($faqId)) || !(int)$faq->get("id")) {
	die(faqPopupT('faq_introuvable_2'));
}

$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
$canEditFaq = $faq->canAnswerRequest() || $faq->canBeEditedInContext($faqContext ?: array());
if (!$faq->canBeViewedInContext($faqContext ?: array(), $faqScope) && !$canEditFaq) {
	die(faqPopupT('vous_n_avez_pas_le_droit_de_consulter_ou_de_modifier_cette_faq'));
}

$isPendingRequest = $faq->isPendingRequest();
$isRequestRelayed = $faq->hasRequestBeenRelayed();
$editRequest = (string)($_GET['edit'] ?? '');
$isEditMode = $editRequest === 'auto'
	? (!(int)$faq->get('isactive')
		&& $isPendingRequest
		&& $canEditFaq)
	: ($editRequest !== '' && $editRequest !== '0');
$canEditFaqScope = \dbObject\FAQ::currentViewerHasOrganizationAdminAccess($faq->getResolvedOrganizationId());
$canDeleteFaq = $faq->canBeDeletedInContext($faqContext ?: array());
$canManageParcoursFaqs = \dbObject\FAQ::canManageParcoursInContext($faqContext ?: array(), 0, true);
$scopeInfo = faqPopupDescribeScope($faq);

if ($isEditMode) {
	if (!$canEditFaq) {
		die(faqPopupT('vous_n_avez_pas_le_droit_d_editer_cette_faq'));
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
	$responseAccess = $isPendingRequest ? \dbObject\FAQ::resolveRequestViewerAccess($faqContext) : $viewerAccess;
	$canPublishRequest = !empty($responseAccess['canManageAllFaqs']) || !empty($responseAccess['canManageOrganizationFaqs']);
	if ($isPendingRequest) $editorFields = ['question', 'answer', 'detail', 'image', 'video'];
	$editorTitle = faqPopupT($isPendingRequest ? 'editor.reply' : 'editor.title');
	$editorStatus = faqPopupT($isPendingRequest ? ($isRequestRelayed ? 'editor.relayed_help' : 'editor.pending_help') : 'editor.edit_help');
	$allowGeneric = $isPendingRequest ? !empty($responseAccess['canManageAllFaqs']) : $faq->canBeDetachedInContext($faqContext ?: array());
	$requestControls = '';
	if ($isPendingRequest) {
		ob_start();
		?>
		<input type="hidden" name="faq_answer_request" value="1">
		<?php if ($canPublishRequest): ?>
			<label class="generic-form-field"><span><input type="checkbox" name="faq_publish_answer" value="1" data-faq-publish-answer> <?= htmlspecialchars(faqPopupT('editor.publish_answer'), ENT_QUOTES, 'UTF-8') ?></span></label>
			<div data-faq-request-publication hidden>
				<?php faqPopupRenderScopeFields($faq, $faqContext, ['requestResponse' => true, 'allowScopeEditing' => true, 'allowGeneric' => $allowGeneric]); ?>
			</div>
		<?php endif; ?>
		<?php
		$requestControls = ob_get_clean();
	}
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
				'sections' => $isPendingRequest ? array_slice(faqPopupEditorSections(), 0, 2) : faqPopupEditorSections(),
				'afterTableHtml' => $requestControls,
				'bindFormScripts' => false,
				'includeComponentAssets' => false,
			);
			$faq->display('adminEdit.php', $params);
			?>
			</div>
			<footer class="faq-popup__editor-actions generic-drawer-footer generic-drawer-footer--sticky">
				<button type="button" class="faq-popup__back generic-action-button generic-action-button--secondary" data-faq-cancel-edit data-faq-id="<?= (int)$faq->getId() ?>"><?= htmlspecialchars(faqPopupT('annuler'), ENT_QUOTES, 'UTF-8') ?></button>
				<div class="generic-action-row">
				<?php if ($isPendingRequest): ?>
					<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-save data-faq-answer-save
						data-reply-label="<?= htmlspecialchars(faqPopupT('editor.reply'), ENT_QUOTES, 'UTF-8') ?>"
						data-publish-label="<?= htmlspecialchars(faqPopupT('editor.reply_publish'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(faqPopupT('editor.reply'), ENT_QUOTES, 'UTF-8') ?></button>
				<?php else: ?>
					<button type="button" class="faq-popup__add generic-action-button generic-action-button--main" data-faq-save><?= htmlspecialchars(faqPopupT('enregistrer'), ENT_QUOTES, 'UTF-8') ?></button>
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
					<button type="button" class="faq-popup__edit generic-action-button generic-action-button--secondary" data-faq-edit data-faq-id="<?= (int)$faq->getId() ?>"><?= htmlspecialchars(faqPopupT('editer'), ENT_QUOTES, 'UTF-8') ?></button>
				<?php endif; ?>
				<?php if ($canDeleteFaq): ?>
					<button type="button" class="generic-action-button generic-action-button--danger generic-action-button--icon-only" data-faq-delete data-faq-id="<?= (int)$faq->getId() ?>" data-faq-delete-confirm="Supprimer cette FAQ ? Cette action est definitive." title="<?= htmlspecialchars(faqPopupT('supprimer'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(faqPopupT('supprimer'), ENT_QUOTES, 'UTF-8') ?>">
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
						<span class="faq-popup__meta-badge faq-popup__meta-badge--generic"><?= htmlspecialchars(faqPopupT('inactive'), ENT_QUOTES, 'UTF-8') ?></span>
					<?php endif; ?>
					<?php if ($faq->canBeDetachedInContext($faqContext ?: array())): ?>
						<span class="faq-popup__meta-badge"><?= htmlspecialchars(faqPopupT('detach_possible'), ENT_QUOTES, 'UTF-8') ?></span>
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
				<?= htmlspecialchars(faqPopupT('consultations'), ENT_QUOTES, 'UTF-8') ?> <?= (int)$faq->get("viewcount") ?>
			</div>
		<?php endif; ?>
	</div>
</div>
