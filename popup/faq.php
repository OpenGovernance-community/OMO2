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
$rootHolon = $faqContext['rootHolon'] ?? null;
$contextHolonId = $contextHolon ? (int)$contextHolon->getId() : 0;
$contextOrganizationId = (int)($faqContext['organizationId'] ?? 0);
$faqAvailableScopes = \dbObject\FAQ::getAvailablePopupScopes($faqContext ?: array());
$faqScope = \dbObject\FAQ::normalizePopupScope($_GET['faq_scope'] ?? null, $faqContext ?: array());
$faqScopeActiveIndex = omoApiResolveContextScopeIndex($faqScope, $faqAvailableScopes);
$faqScopeLabels = array(
	'contextual' => 'Contextuel',
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
$canAddFaq = $faqStorageAvailable && ($canManageFaqCollection || $canCreateContextualFaq);

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
		--faq-bg: var(--topbar-panel-bg, var(--color-surface, #ffffff));
		--faq-surface: var(--color-surface-alt, #f8fafc);
		--faq-surface-strong: color-mix(in srgb, var(--faq-surface) 80%, var(--faq-bg));
		--faq-border: var(--topbar-panel-border, var(--color-border, #d0d7de));
		--faq-border-soft: color-mix(in srgb, var(--faq-border) 76%, transparent);
		--faq-text: var(--color-text, #1f2937);
		--faq-text-muted: var(--topbar-panel-muted, var(--color-text-light, #475569));
		--faq-heading: color-mix(in srgb, var(--faq-text) 92%, #000000);
		--faq-accent: var(--color-primary, #2563eb);
		--faq-accent-soft: color-mix(in srgb, var(--faq-accent) 16%, var(--faq-bg));
		--faq-accent-strong: color-mix(in srgb, var(--faq-accent) 84%, #1d4ed8);
		--faq-badge-bg: color-mix(in srgb, var(--faq-accent) 12%, var(--faq-bg));
		--faq-badge-text: color-mix(in srgb, var(--faq-accent) 72%, var(--faq-text));
		--faq-generic-bg: color-mix(in srgb, var(--faq-text-muted) 12%, var(--faq-bg));
		--faq-generic-text: color-mix(in srgb, var(--faq-text-muted) 90%, var(--faq-bg));
		--faq-organization-bg: color-mix(in srgb, #14b8a6 14%, var(--faq-bg));
		--faq-organization-text: color-mix(in srgb, #0f766e 78%, var(--faq-text));
		--faq-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
		--faq-shadow-strong: 0 18px 40px rgba(15, 23, 42, 0.16);
		position: relative;
		height: clamp(360px, calc(100dvh - 220px), 760px);
		overflow: hidden;
		background: var(--faq-bg);
		color: var(--faq-text);
		color-scheme: light;
	}

	html[data-theme="dark"] .faq-popup {
		--faq-bg: var(--topbar-panel-bg, var(--color-surface, #16202b));
		--faq-surface: var(--color-surface-alt, #101923);
		--faq-surface-strong: color-mix(in srgb, var(--faq-surface) 82%, var(--faq-bg));
		--faq-border: var(--topbar-panel-border, var(--color-border, #283548));
		--faq-border-soft: color-mix(in srgb, var(--faq-border) 78%, transparent);
		--faq-text: var(--color-text, #e5edf7);
		--faq-text-muted: var(--topbar-panel-muted, #9fb0c3);
		--faq-heading: var(--faq-text);
		--faq-accent: var(--color-primary, #7c9cff);
		--faq-accent-soft: color-mix(in srgb, var(--faq-accent) 18%, var(--faq-bg));
		--faq-accent-strong: color-mix(in srgb, var(--faq-accent) 92%, #9db4ff);
		--faq-badge-bg: color-mix(in srgb, var(--faq-accent) 18%, var(--faq-bg));
		--faq-badge-text: color-mix(in srgb, var(--faq-accent) 82%, var(--faq-text));
		--faq-generic-bg: color-mix(in srgb, var(--faq-text-muted) 16%, var(--faq-bg));
		--faq-generic-text: color-mix(in srgb, var(--faq-text-muted) 96%, var(--faq-bg));
		--faq-organization-bg: color-mix(in srgb, #14b8a6 18%, var(--faq-bg));
		--faq-organization-text: color-mix(in srgb, #67e8f9 74%, var(--faq-text));
		--faq-shadow: 0 12px 26px rgba(0, 0, 0, 0.24);
		--faq-shadow-strong: 0 18px 40px rgba(0, 0, 0, 0.34);
		color-scheme: dark;
	}

	@media (prefers-color-scheme: dark) {
		html[data-theme-preference="system"] .faq-popup {
			--faq-bg: var(--topbar-panel-bg, var(--color-surface, #16202b));
			--faq-surface: var(--color-surface-alt, #101923);
			--faq-surface-strong: color-mix(in srgb, var(--faq-surface) 82%, var(--faq-bg));
			--faq-border: var(--topbar-panel-border, var(--color-border, #283548));
			--faq-border-soft: color-mix(in srgb, var(--faq-border) 78%, transparent);
			--faq-text: var(--color-text, #e5edf7);
			--faq-text-muted: var(--topbar-panel-muted, #9fb0c3);
			--faq-heading: var(--faq-text);
			--faq-accent: var(--color-primary, #7c9cff);
			--faq-accent-soft: color-mix(in srgb, var(--faq-accent) 18%, var(--faq-bg));
			--faq-accent-strong: color-mix(in srgb, var(--faq-accent) 92%, #9db4ff);
			--faq-badge-bg: color-mix(in srgb, var(--faq-accent) 18%, var(--faq-bg));
			--faq-badge-text: color-mix(in srgb, var(--faq-accent) 82%, var(--faq-text));
			--faq-generic-bg: color-mix(in srgb, var(--faq-text-muted) 16%, var(--faq-bg));
			--faq-generic-text: color-mix(in srgb, var(--faq-text-muted) 96%, var(--faq-bg));
			--faq-organization-bg: color-mix(in srgb, #14b8a6 18%, var(--faq-bg));
			--faq-organization-text: color-mix(in srgb, #67e8f9 74%, var(--faq-text));
			--faq-shadow: 0 12px 26px rgba(0, 0, 0, 0.24);
			--faq-shadow-strong: 0 18px 40px rgba(0, 0, 0, 0.34);
			color-scheme: dark;
		}
	}

	.faq-popup__search {
		display: grid;
		gap: 18px;
		height: 100%;
		overflow: auto;
		align-content: start;
		padding-right: 6px;
	}

	.faq-popup--detail-open .faq-popup__search {
		visibility: hidden;
	}

	.faq-popup__hero {
		display: grid;
		gap: 18px;
		padding: 4px 4px 2px;
	}

	.faq-popup__hero-head {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 16px;
		flex-wrap: wrap;
	}

	.faq-popup__hero-main {
		display: flex;
		align-items: flex-start;
		gap: 16px;
		flex: 1 1 460px;
		min-width: 0;
	}

	.faq-popup__hero-icon {
		width: 48px;
		height: 48px;
		border-radius: 16px;
		background: var(--faq-accent-soft);
		box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--faq-accent) 18%, transparent);
		color: var(--faq-accent-strong);
		display: grid;
		place-items: center;
		font-size: 20px;
		font-weight: 700;
		flex: 0 0 auto;
	}

	.faq-popup__hero-copy {
		display: grid;
		gap: 6px;
		min-width: 0;
	}

	.faq-popup__hero-title {
		margin: 0;
		font-size: clamp(30px, 3vw, 40px);
		line-height: 1;
		letter-spacing: -0.03em;
		color: var(--faq-heading);
	}

	.faq-popup__hero-subtitle {
		font-size: 16px;
		line-height: 1.45;
		color: var(--faq-text-muted);
	}

	.faq-popup__hero-tags {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
	}

	.faq-popup__hero-tag {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 8px 12px;
		border-radius: 999px;
		background: var(--faq-surface);
		box-shadow: inset 0 0 0 1px var(--faq-border-soft);
		color: var(--faq-text-muted);
		font-size: 13px;
		line-height: 1.2;
	}

	.faq-popup__hero-tag strong {
		color: var(--faq-heading);
		font-weight: 600;
	}

	.faq-popup__search-row {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.faq-popup__search-shell {
		display: flex;
		align-items: center;
		gap: 14px;
		flex: 1 1 auto;
		padding: 0 18px;
		border: 1px solid color-mix(in srgb, var(--faq-accent) 34%, var(--faq-border));
		border-radius: 18px;
		background: var(--faq-bg);
		box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--faq-accent) 10%, transparent);
	}

	.faq-popup__search-shell:focus-within {
		border-color: var(--faq-accent);
		box-shadow:
			inset 0 0 0 1px color-mix(in srgb, var(--faq-accent) 26%, transparent),
			0 0 0 4px color-mix(in srgb, var(--faq-accent) 10%, transparent);
	}

	.faq-popup__search-icon {
		position: relative;
		width: 18px;
		height: 18px;
		flex: 0 0 auto;
		opacity: 0.86;
	}

	.faq-popup__search-icon::before {
		content: "";
		position: absolute;
		left: 1px;
		top: 1px;
		width: 11px;
		height: 11px;
		border: 2px solid var(--faq-text-muted);
		border-radius: 50%;
	}

	.faq-popup__search-icon::after {
		content: "";
		position: absolute;
		right: 0;
		bottom: 1px;
		width: 7px;
		height: 2px;
		border-radius: 999px;
		background: var(--faq-text-muted);
		transform: rotate(45deg);
		transform-origin: center;
	}

	.faq-popup__search-input {
		width: 100%;
		padding: 16px 0;
		border: 0;
		background: transparent;
		color: var(--faq-text);
		font-size: 15px;
	}

	.faq-popup__search-input:focus {
		outline: none;
	}

	.faq-popup__helper,
	.faq-popup__no-result,
	.faq-popup__empty {
		padding: 16px 18px;
		border-radius: 14px;
		background: var(--faq-surface);
		color: var(--faq-text-muted);
	}

	.faq-popup__helper strong {
		color: var(--faq-heading);
	}

	.faq-popup__toolbar {
		display: grid;
		grid-template-columns: minmax(0, 1fr) auto;
		align-items: end;
		gap: 16px 20px;
	}

	.faq-popup__toolbar-main {
		display: grid;
		gap: 12px;
		min-width: 0;
	}

	.faq-popup__toolbar-actions {
		display: flex;
		align-items: center;
		gap: 10px;
		flex: 0 0 auto;
	}

	.faq-popup__toolbar-note {
		min-width: 0;
		padding: 12px 14px;
		border-radius: 14px;
		background: var(--faq-surface);
		box-shadow: inset 0 0 0 1px var(--faq-border-soft);
		font-size: 13px;
		line-height: 1.45;
		color: var(--faq-text-muted);
	}

	.faq-popup__toolbar-note strong {
		color: var(--faq-heading);
	}

	.faq-popup__scope-toggle {
		--faq-scope-option-count: 2;
		--faq-scope-active-index: 0;
		--faq-scope-toggle-inset: 4px;
		position: relative;
		display: inline-grid;
		grid-template-columns: repeat(var(--faq-scope-option-count), minmax(0, 1fr));
		align-items: center;
		gap: 0;
		padding: 4px;
		border-radius: 999px;
		background: var(--faq-surface-strong);
		width: fit-content;
		flex: 0 0 auto;
		isolation: isolate;
		transition: opacity 180ms ease;
	}

	.faq-popup__scope-toggle::before {
		content: "";
		position: absolute;
		left: var(--faq-scope-toggle-inset);
		top: var(--faq-scope-toggle-inset);
		bottom: var(--faq-scope-toggle-inset);
		width: calc((100% - (var(--faq-scope-toggle-inset) * 2)) / var(--faq-scope-option-count));
		border-radius: 999px;
		background: var(--faq-bg);
		box-shadow: 0 4px 10px rgba(15, 23, 42, 0.10);
		transform: translateX(calc(var(--faq-scope-active-index) * 100%));
		transition: transform 220ms ease, box-shadow 220ms ease;
		z-index: 0;
	}

	.faq-popup__scope-toggle-button {
		position: relative;
		z-index: 1;
		border: 0;
		background: transparent;
		color: var(--faq-text-muted);
		padding: 9px 14px;
		border-radius: 999px;
		cursor: pointer;
		font-size: 13px;
		font-weight: 600;
		min-width: 0;
		white-space: nowrap;
		transition: color 180ms ease;
	}

	.faq-popup__scope-toggle-button::before {
		content: "";
		display: none;
		width: 18px;
		height: 18px;
		background-position: center;
		background-repeat: no-repeat;
		background-size: contain;
		opacity: 0.68;
	}

	.faq-popup__scope-toggle-button[data-omo-scope-option="contextual"]::before {
		background-image: url("/omo/assets/images/scope/contextual.png?v=20260622");
	}

	.faq-popup__scope-toggle-button[data-omo-scope-option="descendants"]::before {
		background-image: url("/omo/assets/images/scope/descendants.png?v=20260622");
	}

	.faq-popup__scope-toggle-button[data-omo-scope-option="children"]::before {
		background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 18 18'%3E%3Cg fill='none' stroke='%23000' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9 4.5v4M4.5 13.5v-2.5H13.5v2.5M9 8.5v2.5'/%3E%3C/g%3E%3Ccircle cx='9' cy='3' r='2' fill='%23000'/%3E%3Ccircle cx='4.5' cy='15' r='2' fill='%23000'/%3E%3Ccircle cx='13.5' cy='15' r='2' fill='%23000'/%3E%3C/svg%3E");
	}

	.faq-popup__scope-toggle-button.is-active {
		color: var(--faq-heading);
	}

	.faq-popup__scope-toggle-button.is-active::before {
		opacity: 1;
	}

	html[data-theme="dark"] .faq-popup__scope-toggle-button::before {
		filter: invert(1);
	}

	.faq-popup.is-loading .faq-popup__scope-toggle {
		opacity: 0.72;
	}

	.faq-popup.is-loading .faq-popup__scope-toggle-button {
		cursor: wait;
	}

	.faq-popup__list {
		display: grid;
		gap: 18px;
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
		color: var(--faq-text-muted);
	}

	.faq-popup__item {
		border: 1px solid var(--faq-border-soft);
		border-radius: 22px;
		background: color-mix(in srgb, var(--faq-bg) 90%, var(--faq-surface));
		box-shadow: var(--faq-shadow);
		overflow: hidden;
		transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
	}

	.faq-popup__item:hover {
		border-color: color-mix(in srgb, var(--faq-accent) 18%, var(--faq-border-soft));
		box-shadow: var(--faq-shadow-strong);
	}

	.faq-popup__item.is-open {
		border-color: color-mix(in srgb, var(--faq-accent) 24%, var(--faq-border-soft));
	}

	.faq-popup__item-header {
		width: 100%;
		display: grid;
		grid-template-columns: 56px minmax(0, 1fr) 44px;
		align-items: start;
		gap: 18px;
		padding: 24px;
		border: 0;
		background: transparent;
		cursor: pointer;
	}

	.faq-popup__item-icon {
		width: 56px;
		height: 56px;
		border-radius: 18px;
		background: var(--faq-accent-soft);
		color: var(--faq-accent-strong);
		display: grid;
		place-items: center;
		font-size: 20px;
		font-weight: 700;
		box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--faq-accent) 18%, transparent);
	}

	.faq-popup__item-icon--generic {
		background: var(--faq-generic-bg);
		color: var(--faq-generic-text);
		box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--faq-text-muted) 12%, transparent);
	}

	.faq-popup__item-icon--organization {
		background: var(--faq-organization-bg);
		color: var(--faq-organization-text);
		box-shadow: inset 0 0 0 1px color-mix(in srgb, #14b8a6 18%, transparent);
	}

	.faq-popup__item-heading {
		display: grid;
		gap: 10px;
		min-width: 0;
		text-align: left;
	}

	.faq-popup__question {
		display: block;
		font-size: clamp(18px, 1.35vw, 30px);
		line-height: 1.28;
		font-weight: 700;
		color: var(--faq-heading);
	}

	.faq-popup__item-toggle {
		position: relative;
		width: 44px;
		height: 44px;
		border-radius: 50%;
		background: var(--faq-surface);
		box-shadow: inset 0 0 0 1px var(--faq-border-soft);
		flex: 0 0 auto;
	}

	.faq-popup__item-toggle::before {
		content: "+";
		position: absolute;
		inset: 0;
		display: grid;
		place-items: center;
		font-size: 24px;
		line-height: 1;
		color: var(--faq-accent);
	}

	.faq-popup__item.is-open .faq-popup__item-toggle::before {
		content: "-";
	}

	.faq-popup__meta {
		display: flex;
		gap: 8px;
		flex-wrap: wrap;
	}

	.faq-popup__meta-badge {
		display: inline-flex;
		align-items: center;
		padding: 2px 7px;
		border-radius: 999px;
		background: var(--faq-badge-bg);
		color: var(--faq-badge-text);
		font-size: 10px;
		font-weight: 600;
		line-height: 1.05;
	}

	.faq-popup__meta-badge--generic {
		background: var(--faq-generic-bg);
		color: var(--faq-generic-text);
	}

	.faq-popup__meta-badge--organization {
		background: var(--faq-organization-bg);
		color: var(--faq-organization-text);
	}

	.faq-popup__answer {
		display: none;
		padding: 0 24px 24px 98px;
		color: var(--faq-text-muted);
		line-height: 1.6;
	}

	.faq-popup__item.is-open .faq-popup__answer {
		display: block;
	}

	.faq-popup__answer-body {
		display: grid;
		gap: 14px;
	}

	.faq-popup__answer-caption {
		font-size: 13px;
		font-weight: 700;
		letter-spacing: 0.04em;
		text-transform: uppercase;
		color: var(--faq-text-muted);
	}

	.faq-popup__answer-text {
		font-size: 16px;
		line-height: 1.8;
		color: var(--faq-heading);
	}

	.faq-popup__answer-footer {
		margin-top: 18px;
		padding-top: 18px;
		border-top: 1px solid var(--faq-border-soft);
		display: flex;
		align-items: center;
		gap: 16px;
		flex-wrap: wrap;
	}

	.faq-popup__actions {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
		margin-top: 0;
		flex: 0 0 auto;
	}

	.faq-popup__media {
		display: grid;
		gap: 16px;
		margin-bottom: 18px;
	}

	.faq-popup__media-figure,
	.faq-popup__media-video,
	.faq-popup__media-fallback {
		border-radius: 16px;
		background: var(--faq-surface);
		box-shadow: inset 0 0 0 1px var(--faq-border-soft);
		overflow: hidden;
	}

	.faq-popup__media-image {
		display: block;
		width: 100%;
		max-height: 440px;
		object-fit: contain;
		background: var(--faq-surface);
	}

	.faq-popup__media-video {
		position: relative;
		aspect-ratio: 16 / 9;
	}

	.faq-popup__media-video iframe {
		position: absolute;
		inset: 0;
		width: 100%;
		height: 100%;
		border: 0;
	}

	.faq-popup__media-fallback {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		padding: 16px;
		flex-wrap: wrap;
		color: var(--faq-text-muted);
	}

	.faq-popup__vote {
		margin-top: 16px;
		padding: 14px;
		border: 1px solid var(--faq-border-soft);
		border-radius: 14px;
		background: var(--faq-surface);
		display: grid;
		gap: 12px;
	}

	.faq-popup__vote--compact {
		margin-top: 0;
		padding: 0;
		border: 0;
		background: transparent;
		flex: 1 1 460px;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 14px 18px;
		min-width: 0;
	}

	.faq-popup__vote-summary {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
	}

	.faq-popup__vote-stat {
		display: inline-flex;
		align-items: baseline;
		gap: 6px;
		padding: 6px 10px;
		border-radius: 999px;
		background: var(--faq-bg);
		color: var(--faq-text-muted);
		font-size: 13px;
	}

	.faq-popup__vote-stat strong {
		color: var(--faq-heading);
		font-size: 14px;
	}

	.faq-popup__vote--compact .faq-popup__vote-summary {
		order: 2;
		flex: 0 0 auto;
	}

	.faq-popup__vote--compact .faq-popup__vote-actions {
		order: 1;
		flex: 0 0 auto;
	}

	.faq-popup__vote--compact .faq-popup__vote-message {
		order: 3;
	}

	.faq-popup__vote--compact .faq-popup__vote-stat {
		padding: 0;
		background: transparent;
	}

	.faq-popup__vote-stars {
		letter-spacing: 0.08em;
	}

	.faq-popup__vote-label {
		font-weight: 600;
	}

	.faq-popup__vote-actions {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
	}

	.faq-popup__vote-button {
		border: 0;
		background: var(--faq-bg);
		color: var(--faq-heading);
		padding: 10px 14px;
		border-radius: 999px;
		cursor: pointer;
		font-size: 14px;
		box-shadow: inset 0 0 0 1px var(--faq-border-soft);
	}

	.faq-popup__vote-button:hover {
		background: var(--faq-accent-soft);
	}

	.faq-popup__vote-button:disabled {
		cursor: not-allowed;
		opacity: 0.6;
	}

	.faq-popup__vote-message {
		display: none;
		color: var(--faq-text-muted);
		font-size: 13px;
		line-height: 1.5;
	}

	.faq-popup__vote-message.is-visible {
		display: block;
	}

	.faq-popup__vote--compact .faq-popup__vote-message.is-visible {
		margin-left: auto;
		max-width: 320px;
		text-align: right;
	}

	.faq-popup__vote-note {
		color: var(--faq-text-muted);
		font-size: 12px;
		line-height: 1.45;
	}

	.faq-popup__detail-link,
	.faq-popup__back,
	.faq-popup__add,
	.faq-popup__edit {
		border: 0;
		background: var(--faq-surface-strong);
		color: var(--faq-heading);
		padding: 10px 14px;
		border-radius: 999px;
		cursor: pointer;
		font-size: 14px;
	}

	.faq-popup__detail-link:hover,
	.faq-popup__back:hover,
	.faq-popup__add:hover,
	.faq-popup__edit:hover {
		background: color-mix(in srgb, var(--faq-surface-strong) 72%, var(--faq-accent-soft));
	}

	.faq-popup__add {
		background: var(--faq-heading);
		color: var(--faq-bg);
	}

	.faq-popup__add:hover {
		background: color-mix(in srgb, var(--faq-heading) 86%, var(--faq-accent-strong));
	}

	.faq-popup__answer-footer .faq-popup__detail-link {
		padding: 0;
		background: transparent;
		color: var(--faq-accent-strong);
		font-size: 15px;
		font-weight: 600;
	}

	.faq-popup__answer-footer .faq-popup__detail-link:hover {
		background: transparent;
		color: var(--faq-accent);
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
		background: var(--faq-surface);
		color: var(--faq-text-muted);
	}

	.faq-popup__detail {
		position: absolute;
		inset: 0;
		z-index: 5;
		padding: 2px;
		border-radius: 18px;
		background: var(--faq-bg);
		overflow: auto;
		box-shadow: var(--faq-shadow-strong);
	}

	.faq-popup__detail[hidden] {
		display: none;
	}

	.faq-popup__highlight {
		background: color-mix(in srgb, #fef08a 84%, var(--faq-bg));
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

	.faq-popup__scope-field--full {
		grid-column: 1 / -1;
	}

	.faq-popup__scope-field[hidden] {
		display: none !important;
	}

	.faq-popup__scope-label {
		font-size: 13px;
		font-weight: 600;
		color: var(--faq-heading);
	}

	.faq-popup__scope-control,
	.faq-popup__scope-fixed {
		width: 100%;
		padding: 12px 14px;
		border: 1px solid var(--faq-border);
		border-radius: 12px;
		background: var(--faq-bg);
		color: var(--faq-text);
		font-size: 14px;
	}

	.faq-popup__scope-control:disabled {
		background: var(--faq-surface);
		color: color-mix(in srgb, var(--faq-text-muted) 72%, transparent);
		cursor: not-allowed;
	}

	@media (max-width: 720px) {
		.faq-popup__hero-head {
			align-items: stretch;
		}

		.faq-popup__toolbar {
			grid-template-columns: 1fr;
		}

		.faq-popup__toolbar-main,
		.faq-popup__toolbar-actions,
		.faq-popup__toolbar-note {
			width: 100%;
		}

		.faq-popup__toolbar-actions {
			justify-content: flex-end;
		}

		.faq-popup__scope-toggle-button {
			padding: 9px 12px;
			font-size: 12px;
		}

		.faq-popup__search-shell {
			padding: 0 14px;
			border-radius: 16px;
		}

		.faq-popup__item-header {
			grid-template-columns: 48px minmax(0, 1fr) 38px;
			gap: 14px;
			padding: 18px;
		}

		.faq-popup__item-icon {
			width: 48px;
			height: 48px;
			border-radius: 16px;
			font-size: 18px;
		}

		.faq-popup__item-toggle {
			width: 38px;
			height: 38px;
		}

		.faq-popup__question {
			font-size: 18px;
		}

		.faq-popup__answer {
			padding: 0 18px 18px;
		}

		.faq-popup__answer-text {
			font-size: 15px;
			line-height: 1.7;
		}

		.faq-popup__answer-footer {
			align-items: flex-start;
		}

		.faq-popup__vote--compact,
		.faq-popup__vote--compact .faq-popup__vote-summary,
		.faq-popup__vote--compact .faq-popup__vote-actions {
			width: 100%;
		}

		.faq-popup__vote--compact .faq-popup__vote-message.is-visible {
			margin-left: 0;
			max-width: none;
			text-align: left;
		}

		.faq-popup__scope-toggle-button {
			padding: 8px 10px;
			font-size: 11px;
		}

		.faq-popup__scope-toggle-button {
			min-width: 42px;
			min-height: 36px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
		}

		.faq-popup__scope-toggle-button::before {
			display: block;
		}

		.faq-popup__scope-toggle-button > span {
			position: absolute;
			width: 1px;
			height: 1px;
			padding: 0;
			margin: -1px;
			overflow: hidden;
			clip: rect(0, 0, 0, 0);
			white-space: nowrap;
			border: 0;
		}

		.faq-popup__scope-grid {
			grid-template-columns: 1fr;
		}
	}
	</style>

	<div class="faq-popup__search" data-faq-search-view>
		<div class="faq-popup__hero">

			<?php if ($contextOrganizationId > 0 || count($allFAQ) > 0 || $canAddFaq): ?>
				<div class="faq-popup__toolbar">
					<div class="faq-popup__toolbar-main">
						<?php if ($contextOrganizationId > 0): ?>
							<div
								class="faq-popup__scope-toggle"
								role="tablist"
								aria-label="Portee de la FAQ"
								data-faq-scope-switch="<?= htmlspecialchars($faqScope, ENT_QUOTES, 'UTF-8') ?>"
								style="--faq-scope-option-count: <?= (int)count($faqAvailableScopes) ?>; --faq-scope-active-index: <?= (int)$faqScopeActiveIndex ?>;"
							>
								<?php foreach ($faqAvailableScopes as $scopeOption): ?>
									<button
										<?php if ($scopeOption === 'contextual' && $contextHolonLabel !== ''): ?>
											title="<?= htmlspecialchars($contextHolonLabel, ENT_QUOTES, 'UTF-8') ?>"
										<?php endif; ?>
										type="button"
										class="faq-popup__scope-toggle-button<?= $faqScope === $scopeOption ? ' is-active' : '' ?>"
										data-faq-scope-toggle="<?= htmlspecialchars($scopeOption, ENT_QUOTES, 'UTF-8') ?>"
										data-omo-scope-option="<?= htmlspecialchars($scopeOption, ENT_QUOTES, 'UTF-8') ?>"
										aria-pressed="<?= $faqScope === $scopeOption ? 'true' : 'false' ?>"
									><span><?= htmlspecialchars((string)($faqScopeLabels[$scopeOption] ?? $scopeOption), ENT_QUOTES, 'UTF-8') ?></span></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						
					</div>
					<div class="faq-popup__toolbar-actions">
						<?php if ($canAddFaq): ?>
							<button type="button" class="faq-popup__add" data-faq-add>Ajouter une question</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if (count($allFAQ) > 0): ?>
				<div class="faq-popup__search-row">
					<label class="faq-popup__search-shell">
						<span class="faq-popup__search-icon" aria-hidden="true"></span>
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
			<div class="faq-popup__helper"><strong>Module FAQ indisponible.</strong> La table `faq` n existe pas encore dans cette base. Lancez les migrations SQL pour activer cette fonctionnalite.</div>
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
					class="faq-popup__item<?= $faqIndex === 0 ? ' is-open' : '' ?>"
					data-faq-item
					data-faq-id="<?= (int)$faq->get("id") ?>"
					data-faq-default-order="<?= $faqIndex ?>"
					data-faq-viewcount="<?= (int)$faq->get("viewcount") ?>"
				>
					<button type="button" class="faq-popup__item-header" data-faq-toggle>
						<span class="<?= $scopeIconClass ?>" aria-hidden="true">?</span>
						<span class="faq-popup__item-heading">
							<span class="faq-popup__question"><?= htmlspecialchars((string)$faq->get("question")) ?></span>
							<span class="faq-popup__meta">
								<span class="<?= $scopeTypeClass ?>"><?= htmlspecialchars((string)($scopeInfo['label'] ?? 'FAQ'), ENT_QUOTES, 'UTF-8') ?></span>
								<?php if (!(int)$faq->get('isactive')): ?>
									<span class="faq-popup__meta-badge faq-popup__meta-badge--generic">Inactive</span>
								<?php endif; ?>
								<?php if ($faq->canBeEditedInContext($faqContext ?: array())): ?>
									<span class="faq-popup__meta-badge">Editable</span>
								<?php endif; ?>
							</span>
						</span>
						<span class="faq-popup__item-toggle" aria-hidden="true"></span>
					</button>
					<div class="faq-popup__answer" data-faq-answer>
						<div class="faq-popup__answer-body">
							<div class="faq-popup__answer-caption">Reponse resumee</div>
							<div class="faq-popup__answer-text" data-faq-answer-text><?= nl2br(htmlspecialchars($faq->getShortAnswer(220))) ?></div>
						</div>
						<div class="faq-popup__answer-footer">
							<div class="faq-popup__actions">
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
	const currentScope = normalizeFaqScope(root.getAttribute('data-faq-scope') || 'contextual');
	const reloadUrl = root.getAttribute('data-faq-reload-url') || '/popup/faq.php';
	let currentViewToken = null;
	let refreshRequestId = 0;
	let detailRequestId = 0;
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

	function normalizeFaqScope(value) {
		const normalizedScope = String(value || '').trim().toLowerCase();
		if (normalizedScope === 'global') {
			return 'descendants';
		}
		if (normalizedScope === 'children' || normalizedScope === 'descendants') {
			return normalizedScope;
		}

		return 'contextual';
	}

	function resolveFaqScopeIndex(scopeSwitch, scope) {
		if (!scopeSwitch) {
			return 0;
		}

		const normalizedScope = normalizeFaqScope(scope);
		const scopeButtons = Array.from(scopeSwitch.querySelectorAll('[data-faq-scope-toggle]'));
		for (let index = 0; index < scopeButtons.length; index += 1) {
			if (normalizeFaqScope(scopeButtons[index].getAttribute('data-faq-scope-toggle')) === normalizedScope) {
				return index;
			}
		}

		return 0;
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
		const resolvedScope = normalizeFaqScope(scope);
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

	function initFaqRichTextFields(container) {
		if (!container || typeof window.adminEditInitHtmlFields !== 'function') {
			return Promise.resolve();
		}

		try {
			return Promise.resolve(window.adminEditInitHtmlFields(container));
		} catch (error) {
			return Promise.resolve();
		}
	}

	function destroyFaqRichTextFields(container) {
		if (!container) {
			return;
		}

		if (typeof window.adminEditDestroyHtmlFields === 'function') {
			try {
				window.adminEditDestroyHtmlFields(container);
				return;
			} catch (error) {
			}
		}

		if (!window.jQuery || !window.jQuery.fn) {
			return;
		}

		window.jQuery(container).find('textarea.summernote').each(function () {
			const field = window.jQuery(this);
			if (typeof field.summernote === 'function' && (field.data('adminEditSummernoteBound') === true || field.next('.note-editor').length > 0)) {
				try {
					field.val(field.summernote('code'));
					field.summernote('destroy');
				} catch (error) {
				}
			}
			field.removeData('adminEditSummernoteBound');
		});
	}

	function clearFaqView(container) {
		if (!container) {
			return;
		}

		destroyFaqRichTextFields(container);
		container.innerHTML = '';
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
			const normalizedScope = normalizeFaqScope(nextScope);
			scopeSwitch.setAttribute(
				'data-faq-scope-switch',
				normalizedScope
			);
			scopeSwitch.style.setProperty('--faq-scope-active-index', String(resolveFaqScopeIndex(scopeSwitch, normalizedScope)));
		}

		activeRoot.querySelectorAll('[data-faq-scope-toggle]').forEach(function (button) {
			button.disabled = !!isLoading;
		});
	}

	function refreshPopupContent(url, options) {
		const config = options || {};
		const targetScope = normalizeFaqScope(config.scope || '');
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

				const scriptSource = temp.cloneNode(true);

				if (typeof window.__omoPopupCleanup === 'function') {
					window.__omoPopupCleanup();
				}

				activeRoot.parentNode.replaceChild(nextRoot, activeRoot);
				executeFetchedScripts(scriptSource);
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
		detailRequestId++;
		currentViewToken = 'faq';
		root.classList.remove('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			clearFaqView(detailView);
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

		detailRequestId++;
		currentViewToken = 'faq-create';
		root.classList.add('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			clearFaqView(detailView);
		}
		editorView.hidden = false;
		syncScopeSelectors(editorView);
		initFaqRichTextFields(editorView);
	}

	function showDetail(id, options) {
		const config = options || {};
		if (!detailView) {
			return;
		}

		const requestId = ++detailRequestId;
		currentViewToken = 'faq-' + Number(id);
		root.classList.add('faq-popup--detail-open');
		if (editorView) {
			editorView.hidden = true;
		}
		clearFaqView(detailView);
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
				if (requestId !== detailRequestId || !document.documentElement.contains(root)) {
					return;
				}

				clearFaqView(detailView);
				detailView.innerHTML = html;
				syncScopeSelectors(detailView);
				initFaqRichTextFields(detailView);
			})
			.catch(function () {
				if (requestId !== detailRequestId || !document.documentElement.contains(root)) {
					return;
				}
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

		const typeSelect = container.querySelector('[data-faq-scope-kind]');
		const organizationSelect = container.querySelector('[data-faq-scope-organization]');
		const holonSelect = container.querySelector('[data-faq-scope-holon]');
		const parcoursSelect = container.querySelector('[data-faq-scope-parcours]');
		const holonShell = container.querySelector('[data-faq-scope-holon-shell]');
		const parcoursShell = container.querySelector('[data-faq-scope-parcours-shell]');
		const organizationShell = container.querySelector('[data-faq-scope-organization-shell]');
		const organizationInput = container.querySelector('input[name="IDorganization"]');
		const holonInput = container.querySelector('input[name="IDholon"]');
		const parcoursInput = container.querySelector('input[name="IDparcours"]');
		let scopeKindInput = container.querySelector('input[name="faq_scope_kind"]');

		if (!scopeKindInput && form) {
			scopeKindInput = document.createElement('input');
			scopeKindInput.type = 'hidden';
			scopeKindInput.name = 'faq_scope_kind';
			form.appendChild(scopeKindInput);
		}

		if (!holonSelect && !parcoursSelect && !typeSelect) {
			return;
		}

		const scopeKind = typeSelect ? String(typeSelect.value || 'organization') : 'organization';
		const organizationId = organizationSelect
			? Number(organizationSelect.value || 0)
			: (currentOid > 0 ? currentOid : Number((organizationInput && organizationInput.value) || 0));

		if (scopeKindInput) {
			scopeKindInput.value = scopeKind;
		}

		if (organizationShell) {
			organizationShell.hidden = scopeKind === 'generic';
		}
		if (holonShell) {
			holonShell.hidden = scopeKind !== 'organization';
		}
		if (parcoursShell) {
			parcoursShell.hidden = scopeKind !== 'parcours';
		}

		if (organizationInput) {
			organizationInput.value = scopeKind === 'generic' ? '' : String(organizationId > 0 ? organizationId : '');
		}

		if (holonSelect) {
			let hasVisibleHolonSelection = false;
			Array.from(holonSelect.options || []).forEach(function (option, index) {
				if (index === 0) {
					option.hidden = false;
					return;
				}

				const optionOrganizationId = Number(option.getAttribute('data-organization-id') || 0);
				const shouldShow = organizationId > 0 && optionOrganizationId === organizationId;
				option.hidden = !shouldShow;
				if (!shouldShow && option.selected) {
					holonSelect.selectedIndex = 0;
				}
				if (shouldShow && option.selected) {
					hasVisibleHolonSelection = true;
				}
			});

			if (!hasVisibleHolonSelection && holonSelect.selectedIndex > 0) {
				holonSelect.selectedIndex = 0;
			}

			holonSelect.disabled = scopeKind !== 'organization';
		}
		if (parcoursSelect) {
			let hasVisibleParcoursSelection = false;
			Array.from(parcoursSelect.options || []).forEach(function (option, index) {
				if (index === 0) {
					option.hidden = false;
					return;
				}

				const optionOrganizationId = Number(option.getAttribute('data-organization-id') || 0);
				const shouldShow = organizationId > 0 && optionOrganizationId === organizationId;
				option.hidden = !shouldShow;
				if (!shouldShow && option.selected) {
					parcoursSelect.selectedIndex = 0;
				}
				if (shouldShow && option.selected) {
					hasVisibleParcoursSelection = true;
				}
			});

			if (!hasVisibleParcoursSelection && parcoursSelect.selectedIndex > 0) {
				parcoursSelect.selectedIndex = 0;
			}

			parcoursSelect.disabled = scopeKind !== 'parcours';
		}

		if (holonInput) {
			holonInput.value = scopeKind === 'organization' ? String(holonSelect && holonSelect.value ? holonSelect.value : '') : '';
		}
		if (parcoursInput) {
			parcoursInput.value = scopeKind === 'parcours' ? String(parcoursSelect && parcoursSelect.value ? parcoursSelect.value : '') : '';
		}
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

	function syncFaqRichTextFields(form) {
		if (!form || typeof window.jQuery !== 'function') {
			return;
		}

		window.jQuery(form).find('textarea.summernote').each(function () {
			const field = window.jQuery(this);
			if (typeof field.summernote === 'function') {
				try {
					field.val(field.summernote('code'));
				} catch (error) {
				}
			}
		});
	}

	function submitFaqForm(form) {
		if (!form) {
			return;
		}

		syncFaqRichTextFields(form);
		form.classList.add('disabled');

		fetch(form.getAttribute('action'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: new FormData(form)
		})
			.then(function (response) {
				return response.text();
			})
			.then(function (data) {
				handleSaveResponse(data);
			})
			.catch(function () {
				window.alert("Impossible d'enregistrer cette FAQ.");
			})
			.finally(function () {
				form.classList.remove('disabled');
			});
	}

	function formatVoteScore(value) {
		const numericValue = Number(value || 0);
		if (!Number.isFinite(numericValue)) {
			return '0';
		}

		if (Math.abs(numericValue - Math.round(numericValue)) < 0.00001) {
			return String(Math.round(numericValue));
		}

		return numericValue.toFixed(2).replace(/\.?0+$/, '');
	}

	function estimateVoteSplit(positiveScore, negativeScore, totalVotes) {
		const positive = Math.max(0, Number(positiveScore || 0));
		const negative = Math.abs(Number(negativeScore || 0));
		const total = Math.max(0, Number(totalVotes || 0));
		const activeSignal = positive + negative;

		if (!activeSignal || !total) {
			return {
				positive: 0,
				negative: 0,
				total: total
			};
		}

		const estimatedPositive = Math.max(0, Math.min(total, Math.round(total * (positive / activeSignal))));
		return {
			positive: estimatedPositive,
			negative: Math.max(0, total - estimatedPositive),
			total: total
		};
	}

	function renderStars(starCount) {
		const normalizedCount = Math.max(0, Math.min(5, Number(starCount || 0)));
		return '★'.repeat(normalizedCount) + '☆'.repeat(5 - normalizedCount);
	}

	function refreshCompactVoteStars() {
		const compactShells = Array.from(root.querySelectorAll('[data-faq-vote-shell][data-faq-vote-mode="compact"]'));
		if (compactShells.length === 0) {
			return;
		}

		const reliabilities = compactShells
			.map(function (shell) {
				return Math.max(0, Number(shell.getAttribute('data-faq-reliability') || 0));
			})
			.filter(function (value) {
				return Number.isFinite(value);
			});

		const minReliability = reliabilities.length > 0 ? Math.min.apply(null, reliabilities) : 0;
		const maxReliability = reliabilities.length > 0 ? Math.max.apply(null, reliabilities) : 0;

		compactShells.forEach(function (shell) {
			const reliability = Math.max(0, Number(shell.getAttribute('data-faq-reliability') || 0));
			let starCount = 0;

			if (maxReliability <= minReliability) {
				starCount = reliability > 0 ? 5 : 0;
			} else {
				starCount = Math.max(0, Math.min(5, Math.round(((reliability - minReliability) / (maxReliability - minReliability)) * 5)));
			}

			const starsNode = shell.querySelector('[data-faq-stars-text]');
			if (starsNode) {
				starsNode.textContent = renderStars(starCount);
			}
		});
	}

	function setVoteButtonsDisabled(faqId, disabled) {
		root.querySelectorAll('[data-faq-vote-shell][data-faq-id="' + faqId + '"] [data-faq-vote]').forEach(function (button) {
			button.disabled = !!disabled;
		});
	}

	function applyVoteState(payload) {
		const faqId = Number(payload && payload.faqId ? payload.faqId : 0);
		if (!faqId) {
			return;
		}

		root.querySelectorAll('[data-faq-vote-shell][data-faq-id="' + faqId + '"]').forEach(function (shell) {
			const voteMode = shell.getAttribute('data-faq-vote-mode') || 'detail';
			const positiveNode = shell.querySelector('[data-faq-score="positive_estimated"]');
			const negativeNode = shell.querySelector('[data-faq-score="negative_estimated"]');
			const totalNode = shell.querySelector('[data-faq-score="total"]');
			const messageNode = shell.querySelector('[data-faq-vote-message]');
			const voteSplit = estimateVoteSplit(payload.positiveScore, payload.negativeScore, payload.totalVotes);

			if (payload.reliability !== undefined) {
				shell.setAttribute('data-faq-reliability', String(Number(payload.reliability || 0)));
			}
			if (voteMode !== 'compact' && positiveNode) {
				positiveNode.textContent = String(voteSplit.positive);
			}
			if (voteMode !== 'compact' && negativeNode) {
				negativeNode.textContent = String(voteSplit.negative);
			}
			if (totalNode && payload.totalVotes !== undefined) {
				totalNode.textContent = String(Number(payload.totalVotes || 0));
			}

			if (messageNode) {
				const message = payload.message ? String(payload.message) : '';
				messageNode.textContent = message;
				messageNode.classList.toggle('is-visible', message !== '');
			}
		});

		refreshCompactVoteStars();

		if (payload.status === true || payload.alreadyVoted) {
			setVoteButtonsDisabled(faqId, true);
		}
	}

	function submitVote(faqId, vote) {
		const normalizedFaqId = Number(faqId || 0);
		const normalizedVote = String(vote || '').trim().toLowerCase();
		if (!normalizedFaqId || (normalizedVote !== 'up' && normalizedVote !== 'down')) {
			return;
		}

		setVoteButtonsDisabled(normalizedFaqId, true);

		const requestBody = new URLSearchParams();
		requestBody.set('faq_id', String(normalizedFaqId));
		requestBody.set('vote', normalizedVote);
		requestBody.set('oid', String(currentOid));
		requestBody.set('cid', String(currentCid));
		requestBody.set('faq_scope', currentScope);

		fetch('/ajax/faq_vote.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: requestBody.toString()
		})
			.then(function (response) {
				return response.text().then(function (text) {
					let payload = null;

					try {
						payload = JSON.parse(text);
					} catch (error) {
						payload = null;
					}

					return {
						ok: response.ok,
						payload: payload
					};
				});
			})
			.then(function (result) {
				if (result.payload) {
					applyVoteState(result.payload);
				}

				if (result.payload && (result.payload.status === true || result.payload.alreadyVoted)) {
					return;
				}

				setVoteButtonsDisabled(normalizedFaqId, false);
				window.alert(result.payload && result.payload.message ? result.payload.message : 'Impossible d enregistrer ce vote.');
			})
			.catch(function () {
				setVoteButtonsDisabled(normalizedFaqId, false);
				window.alert('Impossible d enregistrer ce vote pour le moment.');
			});
	}

	root.addEventListener('change', function (event) {
		if (event.target.matches('[data-faq-scope-kind], [data-faq-scope-organization], [data-faq-scope-holon], [data-faq-scope-parcours]')) {
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
			if (form) {
				submitFaqForm(form);
			}
			return;
		}

		const voteButton = event.target.closest('[data-faq-vote]');
		if (voteButton) {
			submitVote(voteButton.getAttribute('data-faq-id'), voteButton.getAttribute('data-faq-vote'));
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
		detailRequestId++;
		window.removeEventListener('hashchange', syncFromHash);
		window.removeEventListener('omo-popup-route-update', syncFromHash);
		destroyFaqRichTextFields(root);
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
	refreshCompactVoteStars();
	filterList();
})();
</script>
