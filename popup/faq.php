<?php
require_once("../config.php");
require_once("../shared_functions.php");
require_once("../common/faq_popup_helper.php");

$faqContext = \dbObject\FAQ::resolvePopupRequestContext($_GET);

if ($faqContext === false) {
	http_response_code(403);
	?>
	<div class="faq-popup__empty">Contexte FAQ invalide.</div>
	<?php
	return;
}

$contextHolon = $faqContext['currentHolon'] ?? null;
$contextHolonId = $contextHolon ? (int)$contextHolon->getId() : 0;
$contextOrganizationId = (int)($faqContext['organizationId'] ?? 0);
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());
$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION['currentUser'] ?? 0);
$viewerAccess = \dbObject\FAQ::resolveViewerAccess($faqContext ?: array());
$canManageAllFaqs = !empty($viewerAccess['canManageAllFaqs']);
$canManageOrganizationFaqs = !empty($viewerAccess['canManageOrganizationFaqs']);
$canManageFaqCollection = $canManageAllFaqs || $canManageOrganizationFaqs;
$canCreateContextualFaq = $contextHolon
	? \dbObject\FAQ::canCreateContextualForHolon($contextHolon, $currentUserId, $contextOrganizationId)
	: false;
$canAddFaq = $canManageFaqCollection || $canCreateContextualFaq;

$allFAQ = \dbObject\FAQ::loadPopupCollection($faqContext ?: array(), $faqScope);
$defaultVisibleFaqCount = $canManageFaqCollection ? (int)count($allFAQ) : 5;
$initialRemainingFaqCount = max(0, count($allFAQ) - $defaultVisibleFaqCount);
$initialLoadMoreCount = min($defaultVisibleFaqCount, $initialRemainingFaqCount);

$newFaq = new \dbObject\FAQ();
$newFaq->set('IDorganization', $contextOrganizationId > 0 ? $contextOrganizationId : null);
$newFaq->set('IDholon', $contextHolonId > 0 ? $contextHolonId : null);
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

$editorTitle = 'Nouvelle FAQ contextuelle';
$editorStatus = $contextHolon
	? 'Cette FAQ sera rattachee au holon courant.'
	: 'Cette FAQ sera creee dans le contexte courant.';
$editorAllowScopeEditing = false;
$editorAllowGeneric = false;
$editorFields = array(
	'question',
	'answer',
	'detail',
);

if ($canManageAllFaqs) {
	$editorTitle = 'Nouvelle FAQ';
	$editorStatus = 'Vous pouvez la rattacher a une organisation, a un holon, ou la laisser generique.';
	$editorAllowScopeEditing = true;
	$editorAllowGeneric = true;
	$editorFields[] = 'displayorder';
	$editorFields[] = 'isactive';
} elseif ($canManageOrganizationFaqs) {
	$editorTitle = 'Nouvelle FAQ organisation';
	$editorStatus = 'Cette FAQ sera rattachee a l organisation courante. Vous pouvez choisir un holon ou la laisser au niveau organisation.';
	$editorAllowScopeEditing = true;
	$editorFields[] = 'displayorder';
	$editorFields[] = 'isactive';
}
?>
<div
	class="faq-popup"
	id="faqPopupRoot"
	data-faq-oid="<?= (int)$contextOrganizationId ?>"
	data-faq-cid="<?= (int)$contextHolonId ?>"
	data-faq-scope="<?= htmlspecialchars($faqScope, ENT_QUOTES, 'UTF-8') ?>"
	data-faq-reload-url="<?= htmlspecialchars($popupReloadUrl, ENT_QUOTES, 'UTF-8') ?>"
	data-faq-default-visible="<?= $defaultVisibleFaqCount ?>"
>
	<style>
	.faq-popup {
		position: relative;
		height: clamp(360px, calc(100dvh - 220px), 760px);
		overflow: hidden;
		color: #1f2937;
	}

	.faq-popup__search {
		display: grid;
		gap: 14px;
		height: 100%;
		overflow: auto;
		align-content: start;
		padding-right: 4px;
	}

	.faq-popup--detail-open .faq-popup__search {
		visibility: hidden;
	}

	.faq-popup__search-input {
		width: 100%;
		padding: 12px 14px;
		border: 1px solid #d0d7de;
		border-radius: 12px;
		font-size: 15px;
	}

	.faq-popup__helper,
	.faq-popup__no-result,
	.faq-popup__empty {
		padding: 16px 18px;
		border-radius: 14px;
		background: #f8fafc;
		color: #475569;
	}

	.faq-popup__helper strong {
		color: #0f172a;
	}

	.faq-popup__toolbar {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		flex-wrap: wrap;
	}

	.faq-popup__toolbar-main {
		display: flex;
		align-items: center;
		gap: 12px;
		flex: 1 1 460px;
		min-width: 0;
		flex-wrap: wrap;
	}

	.faq-popup__toolbar-actions {
		display: flex;
		align-items: center;
		gap: 10px;
		flex: 0 0 auto;
	}

	.faq-popup__toolbar-note {
		flex: 1 1 280px;
		min-width: 0;
		font-size: 13px;
		line-height: 1.45;
		color: #475569;
	}

	.faq-popup__toolbar-note strong {
		color: #0f172a;
	}

	.faq-popup__scope-toggle {
		position: relative;
		display: inline-grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		align-items: center;
		gap: 0;
		padding: 4px;
		border-radius: 999px;
		background: #e2e8f0;
		width: fit-content;
		flex: 0 0 auto;
		isolation: isolate;
		transition: opacity 180ms ease;
	}

	.faq-popup__scope-toggle::before {
		content: "";
		position: absolute;
		left: 4px;
		top: 4px;
		bottom: 4px;
		width: calc(50% - 4px);
		border-radius: 999px;
		background: #ffffff;
		box-shadow: 0 4px 10px rgba(15, 23, 42, 0.10);
		transform: translateX(0);
		transition: transform 220ms ease, box-shadow 220ms ease;
		z-index: 0;
	}

	.faq-popup__scope-toggle[data-faq-scope-switch="global"]::before {
		transform: translateX(100%);
	}

	.faq-popup__scope-toggle-button {
		position: relative;
		z-index: 1;
		border: 0;
		background: transparent;
		color: #334155;
		padding: 9px 14px;
		border-radius: 999px;
		cursor: pointer;
		font-size: 13px;
		font-weight: 600;
		transition: color 180ms ease;
	}

	.faq-popup__scope-toggle-button.is-active {
		color: #0f172a;
	}

	.faq-popup.is-loading .faq-popup__scope-toggle {
		opacity: 0.72;
	}

	.faq-popup.is-loading .faq-popup__scope-toggle-button {
		cursor: wait;
	}

	.faq-popup__list {
		display: grid;
		gap: 12px;
	}

	.faq-popup__footer {
		display: flex;
		justify-content: center;
		align-items: center;
		gap: 12px;
		flex-wrap: wrap;
	}

	.faq-popup__footer[hidden] {
		display: none;
	}

	.faq-popup__load-more-note {
		font-size: 13px;
		color: #64748b;
	}

	.faq-popup__item {
		border: 1px solid #dbe2ea;
		border-radius: 16px;
		background: #ffffff;
		box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
		overflow: hidden;
	}

	.faq-popup__question {
		position: relative;
		width: 100%;
		padding: 16px 52px 8px 18px;
		border: 0;
		background: transparent;
		text-align: left;
		font-size: 16px;
		font-weight: 700;
		color: #0f172a;
		cursor: pointer;
		display: block;
	}

	.faq-popup__question::after {
		content: "+";
		position: absolute;
		right: 18px;
		top: 22px;
		font-size: 22px;
		line-height: 1;
		color: #2563eb;
	}

	.faq-popup__item.is-open .faq-popup__question::after {
		content: "-";
	}

	.faq-popup__meta {
		padding: 0 18px 12px;
		display: flex;
		gap: 8px;
		flex-wrap: wrap;
	}

	.faq-popup__meta-badge {
		display: inline-flex;
		align-items: center;
		padding: 2px 7px;
		border-radius: 999px;
		background: #eef2ff;
		color: #3730a3;
		font-size: 10px;
		font-weight: 600;
		line-height: 1.05;
	}

	.faq-popup__meta-badge--generic {
		background: #f1f5f9;
		color: #334155;
	}

	.faq-popup__meta-badge--organization {
		background: #ecfeff;
		color: #155e75;
	}

	.faq-popup__answer {
		display: none;
		padding: 0 18px 18px;
		color: #475569;
		line-height: 1.6;
	}

	.faq-popup__item.is-open .faq-popup__answer {
		display: block;
	}

	.faq-popup__actions {
		margin-top: 12px;
		display: flex;
		justify-content: flex-end;
		gap: 10px;
		flex-wrap: wrap;
	}

	.faq-popup__detail-link,
	.faq-popup__back,
	.faq-popup__add,
	.faq-popup__edit {
		border: 0;
		background: #e2e8f0;
		color: #0f172a;
		padding: 10px 14px;
		border-radius: 999px;
		cursor: pointer;
		font-size: 14px;
	}

	.faq-popup__detail-link:hover,
	.faq-popup__back:hover,
	.faq-popup__add:hover,
	.faq-popup__edit:hover {
		background: #cbd5e1;
	}

	.faq-popup__add {
		background: #0f172a;
		color: #ffffff;
	}

	.faq-popup__add:hover {
		background: #1e293b;
	}

	.faq-popup__editor-shell {
		padding: 18px;
		display: grid;
		gap: 16px;
	}

	.faq-popup__editor-actions {
		display: flex;
		justify-content: flex-end;
		gap: 12px;
		flex-wrap: wrap;
	}

	.faq-popup__editor-status {
		padding: 12px 14px;
		border-radius: 12px;
		background: #f8fafc;
		color: #475569;
	}

	.faq-popup__detail {
		position: absolute;
		inset: 0;
		z-index: 5;
		padding: 2px;
		border-radius: 18px;
		background: #ffffff;
		overflow: auto;
		box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
	}

	.faq-popup__detail[hidden] {
		display: none;
	}

	.faq-popup__highlight {
		background: #fef08a;
		color: inherit;
		border-radius: 4px;
		padding: 0 2px;
	}

	.faq-popup__scope-grid {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 14px;
	}

	.faq-popup__scope-field {
		display: grid;
		gap: 6px;
	}

	.faq-popup__scope-label {
		font-size: 13px;
		font-weight: 600;
		color: #334155;
	}

	.faq-popup__scope-control,
	.faq-popup__scope-fixed {
		width: 100%;
		padding: 12px 14px;
		border: 1px solid #d0d7de;
		border-radius: 12px;
		background: #ffffff;
		color: #0f172a;
		font-size: 14px;
	}

	.faq-popup__scope-control:disabled {
		background: #f8fafc;
		color: #94a3b8;
		cursor: not-allowed;
	}

	@media (max-width: 720px) {
		.faq-popup__toolbar {
			align-items: flex-start;
		}

		.faq-popup__toolbar-main,
		.faq-popup__toolbar-actions,
		.faq-popup__toolbar-note {
			width: 100%;
		}

		.faq-popup__toolbar-actions {
			justify-content: flex-end;
		}

		.faq-popup__scope-grid {
			grid-template-columns: 1fr;
		}
	}
	</style>

	<div class="faq-popup__search" data-faq-search-view>
		<?php if ($contextOrganizationId > 0 || count($allFAQ) > 0 || $canAddFaq): ?>
			<div class="faq-popup__toolbar">
				<div class="faq-popup__toolbar-main">
					<?php if ($contextOrganizationId > 0): ?>
						<div
							class="faq-popup__scope-toggle"
							role="tablist"
							aria-label="Portee de la FAQ"
							data-faq-scope-switch="<?= htmlspecialchars($faqScope, ENT_QUOTES, 'UTF-8') ?>"
						>
							<button
								type="button"
								class="faq-popup__scope-toggle-button<?= $faqScope === 'contextual' ? ' is-active' : '' ?>"
								data-faq-scope-toggle="contextual"
								aria-pressed="<?= $faqScope === 'contextual' ? 'true' : 'false' ?>"
							>Contextuel</button>
							<button
								type="button"
								class="faq-popup__scope-toggle-button<?= $faqScope === 'global' ? ' is-active' : '' ?>"
								data-faq-scope-toggle="global"
								aria-pressed="<?= $faqScope === 'global' ? 'true' : 'false' ?>"
							>Global</button>
						</div>
					<?php endif; ?>
					<div class="faq-popup__toolbar-note" data-faq-helper<?= count($allFAQ) === 0 ? ' hidden' : '' ?>>
						<?php if ($canManageAllFaqs): ?>
							<strong>Mode super admin:</strong>
							<?= $faqScope === 'global'
								? 'toutes les FAQ de toutes les organisations sont listees ici.'
								: 'seules les FAQ du contexte courant sont listees ici.' ?>
						<?php elseif ($canManageOrganizationFaqs): ?>
							<strong>Mode admin organisation:</strong>
							<?= $faqScope === 'global'
								? 'toutes les FAQ de l organisation courante sont listees ici.'
								: 'seules les FAQ utiles au contexte courant sont listees ici.' ?>
						<?php else: ?>
							<?= $faqScope === 'global'
								? 'Mode global: vous cherchez dans toute la FAQ de l organisation.'
								: 'Mode contextuel: seules les questions liees au contexte courant sont chargees.' ?>
						<?php endif; ?>
					</div>
				</div>
				<div class="faq-popup__toolbar-actions">
					<?php if ($canAddFaq): ?>
						<button type="button" class="faq-popup__add" data-faq-add>Add</button>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if (count($allFAQ) > 0): ?>
			<input
				type="search"
				class="faq-popup__search-input"
				data-faq-search-input
				placeholder="Rechercher dans la FAQ..."
				aria-label="Rechercher dans la FAQ"
			>
		<?php endif; ?>
		<div class="faq-popup__no-result" data-faq-no-result hidden>
			Aucune FAQ ne correspond a cette recherche.
		</div>
		<?php if (count($allFAQ) === 0): ?>
			<div class="faq-popup__empty">Aucune FAQ n'est disponible pour le moment.</div>
		<?php endif; ?>
		<div class="faq-popup__list" data-faq-list<?= count($allFAQ) === 0 ? ' hidden' : '' ?>>
			<?php $faqIndex = 0; ?>
			<?php foreach ($allFAQ as $faq): ?>
				<?php
				$scopeInfo = faqPopupDescribeScope($faq);
				$scopeTypeClass = 'faq-popup__meta-badge';
				if (($scopeInfo['type'] ?? '') === 'generic') {
					$scopeTypeClass .= ' faq-popup__meta-badge--generic';
				} elseif (($scopeInfo['type'] ?? '') === 'organization') {
					$scopeTypeClass .= ' faq-popup__meta-badge--organization';
				}
				?>
				<div
					class="faq-popup__item"
					data-faq-item
					data-faq-id="<?= (int)$faq->get("id") ?>"
					data-faq-default-order="<?= $faqIndex ?>"
					data-faq-viewcount="<?= (int)$faq->get("viewcount") ?>"
				>
					<button type="button" class="faq-popup__question" data-faq-toggle><?= htmlspecialchars((string)$faq->get("question")) ?></button>
					<div class="faq-popup__meta">
						<span class="<?= $scopeTypeClass ?>"><?= htmlspecialchars((string)($scopeInfo['label'] ?? 'FAQ'), ENT_QUOTES, 'UTF-8') ?></span>
						<?php if (!(int)$faq->get('isactive')): ?>
							<span class="faq-popup__meta-badge faq-popup__meta-badge--generic">Inactive</span>
						<?php endif; ?>
						<?php if ($faq->canBeEditedInContext($faqContext ?: array())): ?>
							<span class="faq-popup__meta-badge">Editable</span>
						<?php endif; ?>
					</div>
					<div class="faq-popup__answer" data-faq-answer>
						<div data-faq-answer-text><?= nl2br(htmlspecialchars($faq->getShortAnswer(220))) ?></div>
						<div class="faq-popup__actions">
							<button
								type="button"
								class="faq-popup__detail-link"
								data-faq-detail
								data-faq-id="<?= (int)$faq->get("id") ?>"
							>Voir le detail</button>
						</div>
					</div>
				</div>
				<?php $faqIndex++; ?>
			<?php endforeach; ?>
		</div>
		<div class="faq-popup__footer" data-faq-load-more-shell<?= $initialRemainingFaqCount > 0 ? '' : ' hidden' ?>>
			<button type="button" class="faq-popup__detail-link" data-faq-load-more<?= $initialRemainingFaqCount > 0 ? '' : ' hidden' ?>>Voir <?= (int)$initialLoadMoreCount ?> de plus</button>
			<div class="faq-popup__load-more-note">Ou chercher par mot cle en haut de la page.</div>
		</div>
	</div>

	<div class="faq-popup__detail" data-faq-detail-view hidden></div>
	<?php if ($canAddFaq): ?>
		<div class="faq-popup__detail" data-faq-editor-view hidden>
			<div class="faq-popup__editor-shell" data-faq-form-shell>
				<div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;">
					<h4 style="margin:0; font-size:20px; color:#0f172a;"><?= htmlspecialchars($editorTitle, ENT_QUOTES, 'UTF-8') ?></h4>
					<button type="button" class="faq-popup__back" data-faq-back>Retour</button>
				</div>
				<div class="faq-popup__editor-status">
					<?= htmlspecialchars($editorStatus, ENT_QUOTES, 'UTF-8') ?>
				</div>
				<?php
				if ($canCreateContextualFaq && !$canManageFaqCollection) {
					$newFaq->set('IDholon', $contextHolonId > 0 ? $contextHolonId : null);
				}
				?>
				<?php faqPopupRenderScopeFields($newFaq, $faqContext ?: array(), array(
					'allowScopeEditing' => $editorAllowScopeEditing,
					'allowGeneric' => $editorAllowGeneric,
				)); ?>
				<?php
				$params = array(
					'buttons' => false,
					'action' => '/ajax/faq_save.php?oid=' . rawurlencode((string)$contextOrganizationId) . '&cid=' . rawurlencode((string)$contextHolonId) . '&faq_scope=' . rawurlencode($faqScope),
					'fields' => $editorFields,
				);
				$newFaq->display('adminEdit.php', $params);
				?>
				<div class="faq-popup__editor-actions">
					<button type="button" class="faq-popup__back" data-faq-back>Annuler</button>
					<button type="button" class="faq-popup__add" data-faq-save>Enregistrer</button>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>
<script>
(function () {
	if (typeof window.__omoPopupCleanup === 'function') {
		window.__omoPopupCleanup();
	} else if (typeof window.__omoFaqPopupCleanup === 'function') {
		window.__omoFaqPopupCleanup();
	}

	const root = document.getElementById('faqPopupRoot');
	if (!root) {
		return;
	}

	const detailView = root.querySelector('[data-faq-detail-view]');
	const editorView = root.querySelector('[data-faq-editor-view]');
	const searchInput = root.querySelector('[data-faq-search-input]');
	const helper = root.querySelector('[data-faq-helper]');
	const noResult = root.querySelector('[data-faq-no-result]');
	const list = root.querySelector('[data-faq-list]');
	const loadMoreShell = root.querySelector('[data-faq-load-more-shell]');
	const loadMoreButton = root.querySelector('[data-faq-load-more]');
	const modalBody = document.getElementById('commonTopbarModalBody');
	const parsedDefaultVisibleLimit = Number(root.getAttribute('data-faq-default-visible') || 5);
	const defaultVisibleLimit = parsedDefaultVisibleLimit > 0 ? parsedDefaultVisibleLimit : Number.MAX_SAFE_INTEGER;
	const currentOid = Number(root.getAttribute('data-faq-oid') || 0);
	const currentCid = Number(root.getAttribute('data-faq-cid') || 0);
	const currentScope = String(root.getAttribute('data-faq-scope') || 'contextual').trim().toLowerCase() === 'global'
		? 'global'
		: 'contextual';
	const reloadUrl = root.getAttribute('data-faq-reload-url') || '/popup/faq.php';
	let currentViewToken = null;
	let refreshRequestId = 0;
	let currentVisibleLimit = defaultVisibleLimit;

	if (modalBody) {
		modalBody.setAttribute('data-omo-faq-modal', '1');
		modalBody.setAttribute('data-omo-popup-key', 'faq');
		modalBody.setAttribute('data-omo-popup-url', reloadUrl);
		modalBody.setAttribute('data-omo-popup-live-sync', '1');
	}

	function normalize(value) {
		return String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '');
	}

	function escapeRegExp(value) {
		return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function buildAccentInsensitivePattern(word) {
		const accentMap = {
			a: '[a\\u00E0\\u00E1\\u00E2\\u00E3\\u00E4\\u00E5]',
			c: '[c\\u00E7]',
			e: '[e\\u00E8\\u00E9\\u00EA\\u00EB]',
			i: '[i\\u00EC\\u00ED\\u00EE\\u00EF]',
			n: '[n\\u00F1]',
			o: '[o\\u00F2\\u00F3\\u00F4\\u00F5\\u00F6\\u00F8]',
			u: '[u\\u00F9\\u00FA\\u00FB\\u00FC]',
			y: '[y\\u00FF\\u00FD]'
		};

		return word
			.split('')
			.map(function (char) {
				return accentMap[char] || escapeRegExp(char);
			})
			.join('');
	}

	function buildFaqQuery(id, extraParams) {
		const query = ['id=' + encodeURIComponent(id)];
		if (currentOid > 0) {
			query.push('oid=' + encodeURIComponent(currentOid));
		}
		if (currentCid > 0) {
			query.push('cid=' + encodeURIComponent(currentCid));
		}
		if (currentScope !== 'contextual') {
			query.push('faq_scope=' + encodeURIComponent(currentScope));
		}
		if (extraParams && typeof extraParams === 'object') {
			Object.keys(extraParams).forEach(function (key) {
				const value = extraParams[key];
				if (value === null || value === undefined || value === '') {
					return;
				}
				query.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
			});
		}
		return query.join('&');
	}

	function buildPopupUrlForScope(scope) {
		const resolvedScope = String(scope || '').trim().toLowerCase() === 'global' ? 'global' : 'contextual';
		const query = [];

		if (currentOid > 0) {
			query.push('oid=' + encodeURIComponent(currentOid));
		}
		if (currentCid > 0) {
			query.push('cid=' + encodeURIComponent(currentCid));
		}
		if (resolvedScope !== 'contextual') {
			query.push('faq_scope=' + encodeURIComponent(resolvedScope));
		}

		return '/popup/faq.php' + (query.length > 0 ? '?' + query.join('&') : '');
	}

	function executeFetchedScripts(container) {
		Array.from(container.querySelectorAll('script')).forEach(function (script) {
			const executableScript = document.createElement('script');
			Array.from(script.attributes).forEach(function (attribute) {
				executableScript.setAttribute(attribute.name, attribute.value);
			});
			executableScript.text = script.textContent || '';
			document.body.appendChild(executableScript);
			document.body.removeChild(executableScript);
		});
	}

	function setLoadingState(isLoading, nextScope) {
		const activeRoot = document.getElementById('faqPopupRoot');
		if (!activeRoot) {
			return;
		}

		activeRoot.classList.toggle('is-loading', !!isLoading);
		activeRoot.setAttribute('aria-busy', isLoading ? 'true' : 'false');

		const scopeSwitch = activeRoot.querySelector('[data-faq-scope-switch]');
		if (scopeSwitch && nextScope) {
			scopeSwitch.setAttribute(
				'data-faq-scope-switch',
				String(nextScope).trim().toLowerCase() === 'global' ? 'global' : 'contextual'
			);
		}

		activeRoot.querySelectorAll('[data-faq-scope-toggle]').forEach(function (button) {
			button.disabled = !!isLoading;
		});
	}

	function refreshPopupContent(url, options) {
		const config = options || {};
		const targetScope = String(config.scope || '').trim().toLowerCase() === 'global' ? 'global' : 'contextual';
		const requestId = ++refreshRequestId;

		setLoadingState(true, config.scope ? targetScope : null);

		return fetch(url, {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('faq_popup_reload_failed');
				}
				return response.text();
			})
			.then(function (html) {
				if (requestId !== refreshRequestId) {
					return;
				}

				const temp = document.createElement('div');
				temp.innerHTML = html;
				const nextRoot = temp.querySelector('#faqPopupRoot');
				const activeRoot = document.getElementById('faqPopupRoot');

				if (!nextRoot || !activeRoot || !activeRoot.parentNode) {
					throw new Error('faq_popup_reload_invalid');
				}

				if (typeof window.__omoPopupCleanup === 'function') {
					window.__omoPopupCleanup();
				}

				activeRoot.parentNode.replaceChild(nextRoot, activeRoot);
				executeFetchedScripts(temp);
			})
			.catch(function () {
				setLoadingState(false);
				window.alert('Impossible de recharger la FAQ pour le moment.');
			});
	}

	function resetHighlights(container) {
		container.querySelectorAll('[data-faq-answer-text], .faq-popup__question').forEach(function (node) {
			const original = node.getAttribute('data-original-text');
			if (original !== null) {
				node.innerHTML = original;
			}
		});
	}

	function ensureOriginalText(node) {
		if (!node.hasAttribute('data-original-text')) {
			node.setAttribute('data-original-text', node.innerHTML);
		}
	}

	function highlight(node, words) {
		ensureOriginalText(node);
		const html = node.getAttribute('data-original-text') || '';
		const filteredWords = words.filter(function (word) {
			return word.length >= 2;
		});

		if (filteredWords.length === 0) {
			node.innerHTML = html;
			return;
		}

		const pattern = filteredWords
			.map(buildAccentInsensitivePattern)
			.sort(function (a, b) {
				return b.length - a.length;
			})
			.join('|');
		const regex = new RegExp('(' + pattern + ')', 'gi');

		node.innerHTML = html.replace(regex, '<span class="faq-popup__highlight">$1</span>');
	}

	function getPopupHashState() {
		if (typeof window.omoParsePopupHashState === 'function') {
			const popupState = window.omoParsePopupHashState();

			return {
				popupToken: popupState.popupKey === 'faq' ? popupState.popupToken : null,
				popupId: popupState.popupKey === 'faq' ? popupState.popupId : null
			};
		}

		const normalizedHash = (window.location.hash || '').replace(/^#/, '').trim();
		const hashParts = normalizedHash === '' ? [] : normalizedHash.split('|');
		const popupToken = String(hashParts.length > 1 ? hashParts[1] : '').trim();
		const popupMatch = popupToken.match(/^faq(?:-(\d+))?$/i);

		return {
			popupToken: popupMatch ? (popupMatch[1] ? 'faq-' + Number(popupMatch[1]) : 'faq') : null,
			popupId: popupMatch && popupMatch[1] ? Number(popupMatch[1]) : null
		};
	}

	function showList(options) {
		const config = options || {};
		currentViewToken = 'faq';
		root.classList.remove('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			detailView.innerHTML = '';
		}
		if (editorView) {
			editorView.hidden = true;
		}

		if (config.updateHash !== false && typeof window.omoOpenPopupHashState === 'function') {
			window.omoOpenPopupHashState('faq', null);
		}
	}

	function showEditor() {
		if (!editorView) {
			return;
		}

		currentViewToken = 'faq-create';
		root.classList.add('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			detailView.innerHTML = '';
		}
		editorView.hidden = false;
		syncScopeSelectors(editorView);
	}

	function showDetail(id, options) {
		const config = options || {};
		if (!detailView) {
			return;
		}

		currentViewToken = 'faq-' + Number(id);
		root.classList.add('faq-popup--detail-open');
		if (editorView) {
			editorView.hidden = true;
		}
		detailView.hidden = false;
		detailView.innerHTML = '<div class="faq-popup__helper">Chargement...</div>';

		if (config.updateHash !== false && typeof window.omoOpenPopupHashState === 'function') {
			window.omoOpenPopupHashState('faq', id);
		}

		const extraParams = {};
		if (config.edit === true) {
			extraParams.edit = '1';
		}

		fetch('/ajax/faq_detail.php?' + buildFaqQuery(id, extraParams), {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('faq_detail_load_failed');
				}
				return response.text();
			})
			.then(function (html) {
				detailView.innerHTML = html;
				syncScopeSelectors(detailView);
			})
			.catch(function () {
				detailView.innerHTML = '<div class="faq-popup__no-result">Impossible de charger cette FAQ pour le moment.</div>';
			});
	}

	function syncFromHash() {
		const popupState = getPopupHashState();
		const targetToken = popupState.popupToken;

		if (!targetToken || targetToken === currentViewToken) {
			return;
		}

		if (popupState.popupId) {
			showDetail(popupState.popupId, { updateHash: false });
			return;
		}

		showList({ updateHash: false });
	}

	function sortItemsByDefaultOrder(items) {
		return items.sort(function (a, b) {
			const orderA = Number(a.getAttribute('data-faq-default-order') || 0);
			const orderB = Number(b.getAttribute('data-faq-default-order') || 0);

			return orderA - orderB;
		});
	}

	function updateLoadMoreControls(totalCount, filteredCount, queryActive) {
		if (!loadMoreShell || !loadMoreButton) {
			return;
		}

		const totalVisibleCandidates = queryActive ? filteredCount : totalCount;
		const remainingCount = Math.max(0, totalVisibleCandidates - currentVisibleLimit);
		const showControls = !queryActive && remainingCount > 0;

		loadMoreShell.hidden = !showControls;
		loadMoreButton.hidden = !showControls;
		if (!showControls) {
			loadMoreButton.textContent = '';
			return;
		}

		const increment = Math.min(defaultVisibleLimit, remainingCount);
		loadMoreButton.textContent = 'Voir ' + increment + ' de plus';
	}

	function filterList() {
		if (!list || !searchInput) {
			return;
		}

		const query = searchInput.value.trim();
		const words = normalize(query).split(/\s+/).filter(Boolean);
		const items = Array.from(list.querySelectorAll('[data-faq-item]'));
		let visibleCount = 0;
		const rankedItems = [];

		resetHighlights(root);

		if (words.length === 0) {
			sortItemsByDefaultOrder(items).forEach(function (item, index) {
				item.hidden = index >= currentVisibleLimit;
				item.classList.remove('is-open');

				if (!item.hidden) {
					visibleCount++;
				}

				list.appendChild(item);
			});

			if (helper) {
				helper.hidden = false;
			}
			if (noResult) {
				noResult.hidden = true;
			}
			updateLoadMoreControls(items.length, visibleCount, false);

			return;
		}

		items.forEach(function (item) {
			const question = item.querySelector('.faq-popup__question');
			const answer = item.querySelector('[data-faq-answer]');
			const answerText = item.querySelector('[data-faq-answer-text]');
			const meta = item.querySelector('.faq-popup__meta');
			const haystack = normalize(
				(question ? question.textContent : '')
				+ ' '
				+ (answer ? answer.textContent : '')
				+ ' '
				+ (meta ? meta.textContent : '')
			);

			let score = 0;
			words.forEach(function (word) {
				if (word.length > 0 && haystack.indexOf(word) !== -1) {
					score++;
				}
			});

			const visible = score >= Math.ceil(words.length / 2);
			item.hidden = !visible;

			if (visible) {
				visibleCount++;
				item.classList.add('is-open');
				rankedItems.push({
					item: item,
					score: score
				});
				if (question) {
					highlight(question, words);
				}
				if (answerText) {
					highlight(answerText, words);
				}
			} else {
				item.classList.remove('is-open');
			}
		});

		if (helper) {
			helper.hidden = words.length > 0;
		}
		if (noResult) {
			noResult.hidden = visibleCount > 0 || words.length === 0;
		}
		updateLoadMoreControls(items.length, visibleCount, true);

		if (words.length > 0 && rankedItems.length > 1) {
			rankedItems
				.sort(function (a, b) {
					return b.score - a.score;
				})
				.forEach(function (entry) {
					list.appendChild(entry.item);
				});
		}
	}

	function syncScopeSelectors(container) {
		if (!container) {
			return;
		}

		const form = container.querySelector('#formulaire-edit');
		const scopeFields = container.querySelector('[data-faq-scope-fields]');
		if (form && scopeFields && scopeFields.parentNode !== form) {
			form.insertBefore(scopeFields, form.firstChild || null);
		}

		const organizationSelect = container.querySelector('[data-faq-scope-organization]');
		const holonSelect = container.querySelector('[data-faq-scope-holon]');
		if (!holonSelect) {
			return;
		}

		const selectedOrganizationId = organizationSelect
			? Number(organizationSelect.value || 0)
			: currentOid;
		const options = Array.from(holonSelect.options || []);
		let hasVisibleSelection = false;

		options.forEach(function (option, index) {
			if (index === 0) {
				option.hidden = false;
				return;
			}

			const optionOrganizationId = Number(option.getAttribute('data-organization-id') || 0);
			const shouldShow = selectedOrganizationId <= 0 || optionOrganizationId === selectedOrganizationId;
			option.hidden = !shouldShow;
			if (!shouldShow && option.selected) {
				holonSelect.selectedIndex = 0;
			}
			if (shouldShow && option.selected) {
				hasVisibleSelection = true;
			}
		});

		if (!hasVisibleSelection && holonSelect.selectedIndex > 0) {
			holonSelect.selectedIndex = 0;
		}

		holonSelect.disabled = organizationSelect && selectedOrganizationId <= 0 && !holonSelect.hasAttribute('data-faq-force-enabled');
	}

	function handleSaveResponse(data) {
		let payload = data;

		if (typeof payload === 'string') {
			try {
				payload = JSON.parse(payload);
			} catch (error) {
				payload = null;
			}
		}

		if (!payload || payload.status === false) {
			window.alert(payload && payload.message ? payload.message : "Impossible d'enregistrer cette FAQ.");
			return;
		}

		if (typeof window.omoOpenPopupHashState === 'function') {
			if (payload.focusId) {
				window.omoOpenPopupHashState('faq', Number(payload.focusId));
			} else {
				window.omoOpenPopupHashState('faq', null);
			}
		}

		if (payload.reloadUrl) {
			refreshPopupContent(payload.reloadUrl);
		} else if (payload.script) {
			eval(payload.script);
		}

		if (payload.message) {
			window.alert(payload.message);
		}
	}

	root.addEventListener('change', function (event) {
		if (event.target.matches('[data-faq-scope-organization]')) {
			syncScopeSelectors(event.target.closest('[data-faq-form-shell]') || event.target.closest('.faq-popup__detail') || root);
		}
	});

	root.addEventListener('click', function (event) {
		const toggle = event.target.closest('[data-faq-toggle]');
		if (toggle) {
			const item = toggle.closest('[data-faq-item]');
			if (!item) {
				return;
			}

			root.querySelectorAll('[data-faq-item]').forEach(function (other) {
				if (other !== item) {
					other.classList.remove('is-open');
				}
			});

			item.classList.toggle('is-open');
			return;
		}

		const detailButton = event.target.closest('[data-faq-detail]');
		if (detailButton) {
			showDetail(detailButton.getAttribute('data-faq-id'));
			return;
		}

		const editButton = event.target.closest('[data-faq-edit]');
		if (editButton) {
			showDetail(editButton.getAttribute('data-faq-id'), { edit: true });
			return;
		}

		const cancelEditButton = event.target.closest('[data-faq-cancel-edit]');
		if (cancelEditButton) {
			showDetail(cancelEditButton.getAttribute('data-faq-id'));
			return;
		}

		const scopeToggleButton = event.target.closest('[data-faq-scope-toggle]');
		if (scopeToggleButton) {
			const targetScope = scopeToggleButton.getAttribute('data-faq-scope-toggle') || 'contextual';
			refreshPopupContent(buildPopupUrlForScope(targetScope), { scope: targetScope });
			return;
		}

		if (event.target.closest('[data-faq-load-more]')) {
			currentVisibleLimit += defaultVisibleLimit;
			filterList();
			return;
		}

		if (event.target.closest('[data-faq-add]')) {
			showEditor();
			return;
		}

		const saveButton = event.target.closest('[data-faq-save]');
		if (saveButton) {
			const scope = saveButton.closest('[data-faq-form-shell]') || editorView || detailView;
			const form = scope ? scope.querySelector('#formulaire-edit') : null;
			if (form && typeof window.jQuery === 'function' && typeof window.sendForm === 'function') {
				window.sendForm(window.jQuery(form), handleSaveResponse);
			}
			return;
		}

		if (event.target.closest('[data-faq-back]')) {
			showList();
		}
	});

	if (searchInput) {
		searchInput.addEventListener('input', filterList);
	}

	window.addEventListener('hashchange', syncFromHash);
	window.addEventListener('omo-popup-route-update', syncFromHash);

	window.__omoPopupCleanup = function () {
		window.removeEventListener('hashchange', syncFromHash);
		window.removeEventListener('omo-popup-route-update', syncFromHash);
		if (modalBody) {
			modalBody.removeAttribute('data-omo-faq-modal');
			modalBody.removeAttribute('data-omo-popup-key');
			modalBody.removeAttribute('data-omo-popup-url');
			modalBody.removeAttribute('data-omo-popup-live-sync');
		}
	};

	syncFromHash();
	if (!currentViewToken) {
		showList({ updateHash: false });
	}
	syncScopeSelectors(editorView);
	filterList();
})();
</script>
