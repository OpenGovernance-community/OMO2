<?php
require_once("../config.php");
require_once("../shared_functions.php");

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
$currentUserId = function_exists('commonGetCurrentUserId')
	? (int)commonGetCurrentUserId()
	: (int)($_SESSION['currentUser'] ?? 0);
$canCreateContextualFaq = $contextHolon
	? \dbObject\FAQ::canCreateContextualForHolon($contextHolon, $currentUserId, $contextOrganizationId)
	: false;

$allFAQ = new \dbObject\ArrayFAQ();
$allFAQ->load(\dbObject\FAQ::buildPopupLoadParams($faqContext ?: array()));

$newFaq = new \dbObject\FAQ();
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
if (count($popupReloadQuery) > 0) {
	$popupReloadUrl .= '?' . implode('&', $popupReloadQuery);
}
?>
<div
	class="faq-popup"
	id="faqPopupRoot"
	data-faq-oid="<?= (int)$contextOrganizationId ?>"
	data-faq-cid="<?= (int)$contextHolonId ?>"
	data-faq-reload-url="<?= htmlspecialchars($popupReloadUrl, ENT_QUOTES, 'UTF-8') ?>"
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

	.faq-popup__list {
		display: grid;
		gap: 12px;
	}

	.faq-popup__footer {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 12px;
		flex-wrap: wrap;
	}

	.faq-popup__context {
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
		padding: 16px 52px 16px 18px;
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
		top: 50%;
		transform: translateY(-50%);
		font-size: 22px;
		line-height: 1;
		color: #2563eb;
	}

	.faq-popup__item.is-open .faq-popup__question::after {
		content: "-";
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
	}

	.faq-popup__detail-link,
	.faq-popup__back,
	.faq-popup__add {
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
	.faq-popup__add:hover {
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
	</style>

	<div class="faq-popup__search" data-faq-search-view>
		<?php if (count($allFAQ) > 0): ?>
			<input
				type="search"
				class="faq-popup__search-input"
				data-faq-search-input
				placeholder="Rechercher dans la FAQ..."
				aria-label="Rechercher dans la FAQ"
			>
		<?php endif; ?>
		<div class="faq-popup__helper" data-faq-helper<?= count($allFAQ) === 0 ? ' hidden' : '' ?>>
			Tapez quelques mots cles pour filtrer les reponses, puis ouvrez la question utile.
		</div>
		<div class="faq-popup__no-result" data-faq-no-result hidden>
			Aucune FAQ ne correspond a cette recherche.
		</div>
		<?php if (count($allFAQ) === 0): ?>
			<div class="faq-popup__empty">Aucune FAQ n'est disponible pour le moment.</div>
		<?php endif; ?>
		<div class="faq-popup__list" data-faq-list<?= count($allFAQ) === 0 ? ' hidden' : '' ?>>
			<?php $faqIndex = 0; ?>
			<?php foreach ($allFAQ as $faq): ?>
				<div
					class="faq-popup__item"
					data-faq-item
					data-faq-default-order="<?= $faqIndex ?>"
					data-faq-viewcount="<?= (int)$faq->get("viewcount") ?>"
				>
					<button type="button" class="faq-popup__question" data-faq-toggle><?= htmlspecialchars((string)$faq->get("question")) ?></button>
					<div class="faq-popup__answer" data-faq-answer>
						<div data-faq-answer-text><?= nl2br(htmlspecialchars($faq->getShortAnswer(220))) ?></div>
						<?php if ((string)$faq->get("detail") !== ''): ?>
							<div class="faq-popup__actions">
								<button
									type="button"
									class="faq-popup__detail-link"
									data-faq-detail
									data-faq-id="<?= (int)$faq->get("id") ?>"
								>Voir le detail</button>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<?php $faqIndex++; ?>
			<?php endforeach; ?>
		</div>
		<?php if ($canCreateContextualFaq): ?>
			<div class="faq-popup__footer">
				<div class="faq-popup__context">
					Contexte: <?= htmlspecialchars((string)$contextHolon->getDisplayName(), ENT_QUOTES, 'UTF-8') ?>
				</div>
				<button type="button" class="faq-popup__add" data-faq-add>Add</button>
			</div>
		<?php endif; ?>
	</div>

	<div class="faq-popup__detail" data-faq-detail-view hidden></div>
	<?php if ($canCreateContextualFaq): ?>
		<div class="faq-popup__detail" data-faq-editor-view hidden>
			<div class="faq-popup__editor-shell">
				<div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;">
					<h4 style="margin:0; font-size:20px; color:#0f172a;">Nouvelle FAQ contextuelle</h4>
					<button type="button" class="faq-popup__back" data-faq-back>Retour</button>
				</div>
				<div class="faq-popup__editor-status">
					Cette FAQ sera rattachee a <?= htmlspecialchars((string)$contextHolon->getDisplayName(), ENT_QUOTES, 'UTF-8') ?>.
				</div>
				<?php
				$params = array(
					'buttons' => false,
					'action' => '/ajax/faq_save.php?oid=' . rawurlencode((string)$contextOrganizationId) . '&cid=' . rawurlencode((string)$contextHolonId),
					'fields' => array(
						'question',
						'answer',
						'detail',
					),
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

	const searchView = root.querySelector('[data-faq-search-view]');
	const detailView = root.querySelector('[data-faq-detail-view]');
	const editorView = root.querySelector('[data-faq-editor-view]');
	const editorForm = editorView ? editorView.querySelector('#formulaire-edit') : null;
	const searchInput = root.querySelector('[data-faq-search-input]');
	const helper = root.querySelector('[data-faq-helper]');
	const noResult = root.querySelector('[data-faq-no-result]');
	const list = root.querySelector('[data-faq-list]');
	const modalBody = document.getElementById('commonTopbarModalBody');
	const defaultVisibleLimit = 5;
	const currentOid = Number(root.getAttribute('data-faq-oid') || 0);
	const currentCid = Number(root.getAttribute('data-faq-cid') || 0);
	const reloadUrl = root.getAttribute('data-faq-reload-url') || '/popup/faq.php';
	let currentViewToken = null;

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

	function buildFaqQuery(id) {
		const query = ['id=' + encodeURIComponent(id)];
		if (currentOid > 0) {
			query.push('oid=' + encodeURIComponent(currentOid));
		}
		if (currentCid > 0) {
			query.push('cid=' + encodeURIComponent(currentCid));
		}
		return query.join('&');
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
			popupToken: popupMatch ? (popupMatch[1] ? `faq-${Number(popupMatch[1])}` : 'faq') : null,
			popupId: popupMatch && popupMatch[1] ? Number(popupMatch[1]) : null
		};
	}

	function showList(options = {}) {
		currentViewToken = 'faq';
		root.classList.remove('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			detailView.innerHTML = '';
		}
		if (editorView) {
			editorView.hidden = true;
		}

		if (options.updateHash !== false && typeof window.omoOpenPopupHashState === 'function') {
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
	}

	function showDetail(id, options = {}) {
		if (!detailView || !searchView) {
			return;
		}

		currentViewToken = `faq-${Number(id)}`;
		root.classList.add('faq-popup--detail-open');
		if (editorView) {
			editorView.hidden = true;
		}
		detailView.hidden = false;
		detailView.innerHTML = '<div class="faq-popup__helper">Chargement...</div>';

		if (options.updateHash !== false && typeof window.omoOpenPopupHashState === 'function') {
			window.omoOpenPopupHashState('faq', id);
		}

		fetch('/ajax/faq_detail.php?' + buildFaqQuery(id), {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Erreur de chargement');
				}
				return response.text();
			})
			.then(function (html) {
				detailView.innerHTML = html;
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
				item.hidden = index >= defaultVisibleLimit;
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

			return;
		}

		items.forEach(function (item) {
			const question = item.querySelector('.faq-popup__question');
			const answer = item.querySelector('[data-faq-answer]');
			const answerText = item.querySelector('[data-faq-answer-text]');
			const haystack = normalize((question ? question.textContent : '') + ' ' + (answer ? answer.textContent : ''));

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

		if (payload.script) {
			eval(payload.script);
		} else if (typeof window.commonTopbarRefreshModalContent === 'function') {
			window.commonTopbarRefreshModalContent(reloadUrl);
		}

		if (payload.message) {
			window.alert(payload.message);
		}
	}

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

		if (event.target.closest('[data-faq-add]')) {
			showEditor();
			return;
		}

		if (event.target.closest('[data-faq-save]')) {
			if (editorForm && typeof window.jQuery === 'function' && typeof window.sendForm === 'function') {
				window.sendForm(window.jQuery(editorForm), handleSaveResponse);
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
	filterList();
})();
</script>
