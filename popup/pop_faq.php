<?php
require_once("../config.php");
require_once("../shared_functions.php");

$connected = checklogin();
if (!$connected) {
	die("Login requis");
}

$allFAQ = new \dbObject\ArrayFAQ();
$allFAQ->load([
	'where' => [
		['field' => 'isactive', 'value' => 1],
	],
	'orderBy' => [
		['field' => 'displayorder', 'dir' => 'ASC'],
		['field' => 'updated', 'dir' => 'DESC'],
	],
]);
?>
<div class="faq-popup" id="faqPopupRoot">
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
	.faq-popup__back {
		border: 0;
		background: #e2e8f0;
		color: #0f172a;
		padding: 10px 14px;
		border-radius: 999px;
		cursor: pointer;
		font-size: 14px;
	}

	.faq-popup__detail-link:hover,
	.faq-popup__back:hover {
		background: #cbd5e1;
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

	<?php if (count($allFAQ) === 0): ?>
		<div class="faq-popup__empty">Aucune FAQ n’est disponible pour le moment.</div>
	<?php else: ?>
		<div class="faq-popup__search" data-faq-search-view>
			<input
				type="search"
				class="faq-popup__search-input"
				data-faq-search-input
				placeholder="Rechercher dans la FAQ..."
				aria-label="Rechercher dans la FAQ"
			>
			<div class="faq-popup__helper" data-faq-helper>
				Tapez quelques mots-clés pour filtrer les réponses, puis ouvrez la question qui vous intéresse.
			</div>
			<div class="faq-popup__no-result" data-faq-no-result hidden>
				Aucune FAQ ne correspond à cette recherche.
			</div>
			<div class="faq-popup__list" data-faq-list>
				<?php foreach ($allFAQ as $faq): ?>
					<div class="faq-popup__item" data-faq-item>
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
									>Voir le détail</button>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="faq-popup__detail" data-faq-detail-view hidden></div>
	<?php endif; ?>
</div>
<script>
(function () {
	if (typeof window.__omoFaqPopupCleanup === 'function') {
		window.__omoFaqPopupCleanup();
	}

	const root = document.getElementById('faqPopupRoot');
	if (!root) {
		return;
	}

	const searchView = root.querySelector('[data-faq-search-view]');
	const detailView = root.querySelector('[data-faq-detail-view]');
	const searchInput = root.querySelector('[data-faq-search-input]');
	const helper = root.querySelector('[data-faq-helper]');
	const noResult = root.querySelector('[data-faq-no-result]');
	const list = root.querySelector('[data-faq-list]');
	const modalBody = document.getElementById('commonTopbarModalBody');
	let currentViewToken = null;

	if (modalBody) {
		modalBody.setAttribute('data-omo-faq-modal', '1');
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
			a: '[aàáâãäå]',
			c: '[cç]',
			e: '[eèéêë]',
			i: '[iìíîï]',
			n: '[nñ]',
			o: '[oòóôõöø]',
			u: '[uùúûü]',
			y: '[yÿý]'
		};

		return word
			.split('')
			.map(function (char) {
				return accentMap[char] || escapeRegExp(char);
			})
			.join('');
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

	function getFaqHashState() {
		if (typeof window.omoParseFaqHashState === 'function') {
			return window.omoParseFaqHashState();
		}

		const normalizedHash = (window.location.hash || '').replace(/^#/, '');
		const faqMatch = normalizedHash.match(/(?:^|\|)faq(?:-(\d+))?(?:\||$)/i);

		return {
			faqToken: faqMatch ? (faqMatch[1] ? `faq-${Number(faqMatch[1])}` : 'faq') : null,
			faqId: faqMatch && faqMatch[1] ? Number(faqMatch[1]) : null
		};
	}

	function showList(options = {}) {
		currentViewToken = 'faq';
		root.classList.remove('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			detailView.innerHTML = '';
		}

		if (options.updateHash !== false && typeof window.omoOpenFaqHashState === 'function') {
			window.omoOpenFaqHashState(null);
		}
	}

	function showDetail(id, options = {}) {
		if (!detailView || !searchView) {
			return;
		}

		currentViewToken = `faq-${Number(id)}`;
		root.classList.add('faq-popup--detail-open');
		detailView.hidden = false;
		detailView.innerHTML = '<div class="faq-popup__helper">Chargement...</div>';

		if (options.updateHash !== false && typeof window.omoOpenFaqHashState === 'function') {
			window.omoOpenFaqHashState(id);
		}

		fetch('/ajax/faq_detail.php?id=' + encodeURIComponent(id), {
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
		const faqState = getFaqHashState();
		const targetToken = faqState.faqToken;

		if (!targetToken || targetToken === currentViewToken) {
			return;
		}

		if (faqState.faqId) {
			showDetail(faqState.faqId, { updateHash: false });
			return;
		}

		showList({ updateHash: false });
	}

	function filterList() {
		if (!list || !searchInput) {
			return;
		}

		const query = searchInput.value.trim();
		const words = normalize(query).split(/\s+/).filter(Boolean);
		let visibleCount = 0;
		const rankedItems = [];

		resetHighlights(root);

		list.querySelectorAll('[data-faq-item]').forEach(function (item) {
			const question = item.querySelector('.faq-popup__question');
			const answer = item.querySelector('[data-faq-answer]');
			const answerText = item.querySelector('[data-faq-answer-text]');
			const haystack = normalize((question ? question.textContent : '') + ' ' + (answer ? answer.textContent : ''));

			if (words.length === 0) {
				item.hidden = false;
				item.classList.remove('is-open');
				visibleCount++;
				return;
			}

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

		if (event.target.closest('[data-faq-back]')) {
			showList();
		}
	});

	if (searchInput) {
		searchInput.addEventListener('input', filterList);
	}

	window.addEventListener('hashchange', syncFromHash);
	window.addEventListener('omo-faq-route-update', syncFromHash);

	window.__omoFaqPopupCleanup = function () {
		window.removeEventListener('hashchange', syncFromHash);
		window.removeEventListener('omo-faq-route-update', syncFromHash);
		if (modalBody) {
			modalBody.removeAttribute('data-omo-faq-modal');
		}
	};

	syncFromHash();
	if (!currentViewToken) {
		showList({ updateHash: false });
	}
})();
</script>
