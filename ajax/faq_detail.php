<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/faq_popup_helper.php");

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

if (!$faq->canBeViewedInContext($faqContext ?: array(), $faqScope)) {
	die("Cette FAQ n'est pas disponible");
}

$isEditMode = !empty($_GET['edit']) && $_GET['edit'] !== '0';
$canEditFaq = $faq->canBeEditedInContext($faqContext ?: array());
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
	$editorTitle = 'Editer la FAQ';
	$editorStatus = 'Mettez a jour le contenu puis validez pour revenir au detail.';
	$allowGeneric = $faq->canBeDetachedInContext($faqContext ?: array());
	?>
	<div class="faq-popup__item is-open">
		<div class="faq-popup__editor-shell" data-faq-form-shell>
			<div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;">
				<h4 style="margin:0; font-size:20px; color:#0f172a;"><?= htmlspecialchars($editorTitle, ENT_QUOTES, 'UTF-8') ?></h4>
				<button type="button" class="faq-popup__back" data-faq-cancel-edit data-faq-id="<?= (int)$faq->getId() ?>">Retour</button>
			</div>
			<div class="faq-popup__editor-status">
				<?= htmlspecialchars($editorStatus, ENT_QUOTES, 'UTF-8') ?>
			</div>
			<?php faqPopupRenderScopeFields($faq, $faqContext ?: array(), array(
				'allowScopeEditing' => true,
				'allowGeneric' => $allowGeneric,
			)); ?>
			<?php
			$params = array(
				'buttons' => false,
				'action' => '/ajax/faq_update.php?id=' . rawurlencode((string)$faq->getId()) . '&oid=' . rawurlencode((string)($faqContext['organizationId'] ?? 0)) . '&cid=' . rawurlencode((string)($faqContext['currentHolonId'] ?? 0)) . '&faq_scope=' . rawurlencode($faqScope),
				'fields' => $editorFields,
			);
			$faq->display('adminEdit.php', $params);
			?>
			<div class="faq-popup__editor-actions">
				<button type="button" class="faq-popup__back" data-faq-cancel-edit data-faq-id="<?= (int)$faq->getId() ?>">Annuler</button>
				<button type="button" class="faq-popup__add" data-faq-save>Enregistrer</button>
			</div>
		</div>
	</div>
	<?php
	return;
}

if (\dbObject\FAQ::hasViewcountColumn()) {
	$faq->incrementViewcount();
}
?>
<div class="faq-popup__item is-open">
	<div class="faq-popup__detail-content generic-drawer-content">
		<div class="generic-form-section__heading">
			<div class="generic-form-section__copy">
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
				<h4 class="generic-title generic-title--section"><?= htmlspecialchars((string)$faq->get("question")) ?></h4>
			</div>
			<div class="generic-action-row">
				<?php if ($canEditFaq): ?>
					<button
						type="button"
						class="faq-popup__edit generic-action-button generic-action-button--secondary"
						data-faq-edit
						data-faq-id="<?= (int)$faq->getId() ?>"
					>Editer</button>
				<?php endif; ?>
				<button type="button" class="faq-popup__back generic-action-button generic-action-button--secondary" data-faq-back>Retour a la FAQ</button>
			</div>
		</div>
		<div style="color: #334155; line-height: 1.7; margin-bottom: 18px;">
			<?= nl2br(htmlspecialchars((string)$faq->get("answer"))) ?>
		</div>
		<?php faqPopupRenderMediaBlock($faq); ?>
		<?php if ((string)$faq->get("detail") !== ''): ?>
			<div style="padding: 16px; border-radius: 14px; background: #f8fafc; color: #1e293b; line-height: 1.7;">
				<?= (string)$faq->get("detail") ?>
			</div>
		<?php endif; ?>
		<?php faqPopupRenderVoteBlock($faq); ?>
		<?php if (\dbObject\FAQ::hasViewcountColumn()): ?>
			<div style="margin-top: 16px; color: #64748b; font-size: 14px;">
				Consultations: <?= (int)$faq->get("viewcount") ?>
			</div>
		<?php endif; ?>
	</div>
</div>
