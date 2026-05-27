<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\History;
use dbObject\Holon;
use dbObject\Organization;

function omoRenderHolonHistoryItems(array $historyItems)
{
	ob_start();
	foreach ($historyItems as $item):
		$timestamp = 0;
		if (!empty($item['datecreation'])) {
			try {
				$timestamp = (new DateTime((string)$item['datecreation']))->getTimestamp();
			} catch (Throwable $exception) {
				$timestamp = 0;
			}
		}
		$dateLabel = trim((string)($item['datecreation'] ?? ''));
		if ($dateLabel !== '') {
			try {
				$dateLabel = (new DateTime($dateLabel))->format('d.m.Y H:i');
			} catch (Throwable $exception) {
			}
		}
		$authorLabel = trim((string)($item['authorDisplayName'] ?? ''));
		$actionLabel = trim((string)($item['actionLabel'] ?? ''));
		$parameters = is_array($item['parameters'] ?? null) ? $item['parameters'] : null;
		$hasDiffData = is_array($parameters)
			&& (
				isset($parameters['before'])
				|| isset($parameters['after'])
				|| isset($parameters['changes'])
			);
		$payloadJson = $hasDiffData
			? json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
			: false;
		$payloadBase64 = is_string($payloadJson) ? base64_encode($payloadJson) : '';
		?>
		<article class="omo-holon-history-popup__item" data-history-timestamp="<?= (int)$timestamp ?>">
			<div class="omo-holon-history-popup__meta">
				<?php if ($dateLabel !== ''): ?>
					<span><?= omoApiEscape($dateLabel) ?></span>
				<?php endif; ?>
				<?php if ($authorLabel !== ''): ?>
					<span>par <?= omoApiEscape($authorLabel) ?></span>
				<?php endif; ?>
				<?php if ($actionLabel !== ''): ?>
					<span class="omo-holon-history-popup__action"><?= omoApiEscape($actionLabel) ?></span>
				<?php endif; ?>
			</div>
			<p class="omo-holon-history-popup__content"><?= nl2br(omoApiEscape((string)($item['contentDisplay'] ?? ''))) ?></p>
			<?php if ($hasDiffData && $payloadBase64 !== ''): ?>
				<details class="omo-holon-history-popup__details" data-history-diff="1" data-history-payload="<?= omoApiEscape($payloadBase64) ?>">
					<summary>Voir les changements</summary>
					<div class="omo-holon-history-popup__diff" data-history-diff-container="1"></div>
				</details>
			<?php endif; ?>
		</article>
	<?php
	endforeach;

	return (string)ob_get_clean();
}

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$holonId = (int)($_GET['hid'] ?? $_POST['hid'] ?? 0);
$pageLimit = max(1, min(50, (int)($_GET['limit'] ?? $_POST['limit'] ?? 10)));
$pageOffset = max(0, (int)($_GET['offset'] ?? $_POST['offset'] ?? 0));
$requestFragment = trim((string)($_GET['fragment'] ?? $_POST['fragment'] ?? ''));

if ($organizationId <= 0 || $holonId <= 0) {
	http_response_code(403);
	?>
	<div class="omo-holon-history-popup__empty">Vous devez etre connecte a une organisation pour consulter cet historique.</div>
	<?php
	exit;
}

$organization = new Organization();
$holon = new Holon();

if (!$organization->load($organizationId) || !$holon->load($holonId) || !$organization->containsHolon($holon)) {
	http_response_code(404);
	?>
	<div class="omo-holon-history-popup__empty">Le holon demande est introuvable.</div>
	<?php
	exit;
}

if (!$holon->canViewDetail()) {
	http_response_code(403);
	?>
	<div class="omo-holon-history-popup__empty">Vous n'avez pas le droit de consulter l'historique de ce holon.</div>
	<?php
	exit;
}

$isOrganizationHolon = (int)$holon->get('IDtypeholon') === 4;
$pageData = History::fetchHolonFeedPage($organizationId, $holonId, $pageLimit, $pageOffset, $isOrganizationHolon);
$historyItems = is_array($pageData['items'] ?? null) ? $pageData['items'] : array();
$hasMore = !empty($pageData['hasMore']);
$nextOffset = (int)($pageData['nextOffset'] ?? ($pageOffset + count($historyItems)));

if ($requestFragment === 'items') {
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode(array(
		'status' => true,
		'html' => omoRenderHolonHistoryItems($historyItems),
		'hasMore' => $hasMore,
		'nextOffset' => $nextOffset,
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}
?>
<style>
	.omo-holon-history-popup {
		display: grid;
		gap: 14px;
		padding: 8px 4px 4px;
		color: var(--color-text, #1f2937);
	}

	.omo-holon-history-popup__intro {
		margin: 0;
		line-height: 1.5;
		color: var(--topbar-panel-muted, #64748b);
	}

	.omo-holon-history-popup__list {
		display: grid;
		gap: 10px;
	}

	.omo-holon-history-popup__group {
		display: grid;
		gap: 10px;
	}

	.omo-holon-history-popup__group-title {
		margin: 0;
		position: sticky;
		top: 0;
		z-index: 2;
		padding: 6px 0 8px;
		font-size: 0.9rem;
		font-weight: 700;
		color: var(--color-text, #1f2937);
		background: linear-gradient(180deg, var(--topbar-panel-bg, #ffffff) 0%, var(--topbar-panel-bg, #ffffff) 82%, rgba(255, 255, 255, 0) 100%);
		backdrop-filter: blur(6px);
	}

	.omo-holon-history-popup__group-list {
		display: grid;
		gap: 10px;
	}

	.omo-holon-history-popup__item {
		display: grid;
		gap: 8px;
		padding: 12px 14px;
		border: 1px solid var(--topbar-panel-border, #dbe3ef);
		border-radius: 14px;
		background: var(--topbar-panel-bg, #ffffff);
	}

	.omo-holon-history-popup__meta {
		display: flex;
		flex-wrap: wrap;
		gap: 8px 12px;
		font-size: 0.83rem;
		color: var(--topbar-panel-muted, #64748b);
	}

	.omo-holon-history-popup__action {
		font-weight: 700;
		text-transform: capitalize;
		color: var(--color-text, #1f2937);
	}

	.omo-holon-history-popup__content {
		margin: 0;
		line-height: 1.55;
		white-space: pre-wrap;
		word-break: break-word;
	}

	.omo-holon-history-popup__details {
		font-size: 0.84rem;
		color: var(--topbar-panel-muted, #64748b);
	}

	.omo-holon-history-popup__details summary {
		cursor: pointer;
		user-select: none;
	}

	.omo-holon-history-popup__feed-status {
		padding: 8px 6px 2px;
		text-align: center;
		font-size: 0.82rem;
		line-height: 1.4;
		color: var(--topbar-panel-muted, #64748b);
	}

	.omo-holon-history-popup__feed-status.is-loading {
		font-weight: 700;
	}

	.omo-holon-history-popup__feed-status[hidden] {
		display: none;
	}

	.omo-holon-history-popup__diff {
		display: grid;
		gap: 10px;
		margin-top: 10px;
	}

	.omo-holon-history-popup__diff-section {
		display: grid;
		gap: 8px;
	}

	.omo-holon-history-popup__diff-title {
		font-size: 0.82rem;
		font-weight: 700;
		letter-spacing: 0.02em;
		color: var(--color-text, #1f2937);
	}

	.omo-holon-history-popup__diff-card {
		display: grid;
		gap: 8px;
		padding: 10px 12px;
		border-radius: 12px;
		border: 1px solid var(--topbar-panel-border, #dbe3ef);
		background: rgba(148, 163, 184, 0.06);
	}

	.omo-holon-history-popup__diff-card--added {
		border-color: rgba(22, 163, 74, 0.26);
		background: rgba(22, 163, 74, 0.08);
	}

	.omo-holon-history-popup__diff-card--removed {
		border-color: rgba(220, 38, 38, 0.24);
		background: rgba(220, 38, 38, 0.08);
	}

	.omo-holon-history-popup__diff-card--changed {
		border-color: rgba(37, 99, 235, 0.22);
		background: rgba(37, 99, 235, 0.06);
	}

	.omo-holon-history-popup__diff-card-title {
		font-weight: 700;
		color: var(--color-text, #1f2937);
	}

	.omo-holon-history-popup__diff-value {
		padding: 8px 10px;
		border-radius: 10px;
		font-size: 0.84rem;
		line-height: 1.5;
		white-space: pre-wrap;
		word-break: break-word;
		background: rgba(255, 255, 255, 0.72);
	}

	.omo-holon-history-popup__diff-value--added {
		color: #166534;
		background: rgba(22, 163, 74, 0.1);
	}

	.omo-holon-history-popup__diff-value--removed {
		color: #991b1b;
		background: rgba(220, 38, 38, 0.1);
	}

	.omo-holon-history-popup__diff-inline {
		display: block;
	}

	.omo-holon-history-popup__diff-inline-added {
		color: #166534;
		background: rgba(22, 163, 74, 0.12);
		border-radius: 6px;
		padding: 0 2px;
	}

	.omo-holon-history-popup__diff-inline-removed {
		color: #991b1b;
		background: rgba(220, 38, 38, 0.12);
		border-radius: 6px;
		padding: 0 2px;
		text-decoration: line-through;
	}

	.omo-holon-history-popup__diff-list {
		display: grid;
		gap: 6px;
	}

	.omo-holon-history-popup__diff-list-item {
		padding: 7px 9px;
		border-radius: 9px;
		font-size: 0.83rem;
		line-height: 1.45;
		background: rgba(255, 255, 255, 0.72);
	}

	.omo-holon-history-popup__diff-list-item--added {
		color: #166534;
		background: rgba(22, 163, 74, 0.1);
	}

	.omo-holon-history-popup__diff-list-item--removed {
		color: #991b1b;
		background: rgba(220, 38, 38, 0.1);
	}

	.omo-holon-history-popup__diff-list-item--changed {
		background: rgba(37, 99, 235, 0.08);
		border: 1px solid rgba(37, 99, 235, 0.14);
		color: var(--color-text, #1f2937);
	}

	.omo-holon-history-popup__diff-list-item--muted {
		color: var(--topbar-panel-muted, #64748b);
	}

	.omo-holon-history-popup__diff-list-item-title {
		display: block;
		font-weight: 700;
		margin-bottom: 4px;
	}

	.omo-holon-history-popup__diff-list-item-description {
		display: block;
		white-space: pre-wrap;
		word-break: break-word;
	}

	.omo-holon-history-popup__diff-empty {
		padding: 10px 12px;
		border-radius: 10px;
		background: rgba(15, 23, 42, 0.05);
		font-size: 0.82rem;
		line-height: 1.45;
		color: var(--topbar-panel-muted, #64748b);
	}

	.omo-holon-history-popup__empty {
		padding: 18px 6px;
		line-height: 1.5;
		color: var(--topbar-panel-muted, #64748b);
	}
</style>

<div
	class="omo-holon-history-popup"
	data-holon-history-root="1"
	data-holon-id="<?= (int)$holonId ?>"
	data-page-limit="<?= (int)$pageLimit ?>"
	data-next-offset="<?= (int)$nextOffset ?>"
	data-has-more="<?= $hasMore ? '1' : '0' ?>"
>
	<p class="omo-holon-history-popup__intro">
		Historique lie au holon <strong><?= omoApiEscape($holon->getDisplayName()) ?></strong>.
		<?= $isOrganizationHolon
			? 'Le flux couvre l ensemble de l historique de cette organisation.'
			: 'Le flux inclut les modifications directes de ce holon et, pour un cercle, les elements qui lui sont rattaches.' ?>
	</p>

	<?php if (count($historyItems) === 0): ?>
		<div class="omo-holon-history-popup__empty">
			Aucun element d'historique n'a ete trouve pour ce holon.
		</div>
	<?php endif; ?>

	<div class="omo-holon-history-popup__list" data-history-list="1"><?= omoRenderHolonHistoryItems($historyItems) ?></div>
	<div class="omo-holon-history-popup__feed-status<?= $hasMore ? '' : '' ?>" data-history-feed-status="1"<?= (!$hasMore && count($historyItems) === 0) ? ' hidden' : '' ?>>
		<?= $hasMore ? 'Faites defiler pour charger la suite.' : 'Fin de l\'historique.' ?>
	</div>
</div>

<script>
	(function () {
		function safeObject(value) {
			return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
		}

		function safeArray(value) {
			return Array.isArray(value) ? value : [];
		}

		function normalizeText(value) {
			if (value === null || typeof value === 'undefined') {
				return '';
			}

			return String(value).trim();
		}

		function decodeHtmlToText(value) {
			var text = normalizeText(value);
			if (text === '') {
				return '';
			}

			if (text.indexOf('<') === -1 || text.indexOf('>') === -1) {
				return text;
			}

			var wrapper = document.createElement('div');
			wrapper.innerHTML = text
				.replace(/<br\s*\/?>/gi, '\n')
				.replace(/<\/p>/gi, '\n')
				.replace(/<\/div>/gi, '\n');

			return normalizeText(wrapper.textContent || wrapper.innerText || '');
		}

		function createElement(tag, className, text) {
			var element = document.createElement(tag);
			if (className) {
				element.className = className;
			}
			if (typeof text !== 'undefined' && text !== null) {
				element.textContent = text;
			}

			return element;
		}

		function createValueBlock(text, modifierClass) {
			var block = createElement('div', 'omo-holon-history-popup__diff-value' + (modifierClass ? ' ' + modifierClass : ''));
			block.textContent = normalizeText(text);
			return block;
		}

		function tokenizeForWordDiff(text) {
			var tokens = normalizeText(text).match(/(\s+|[^\s]+)/g);
			return Array.isArray(tokens) ? tokens : [];
		}

		function buildWordDiffOperations(beforeText, afterText) {
			var beforeTokens = tokenizeForWordDiff(beforeText);
			var afterTokens = tokenizeForWordDiff(afterText);
			var maxCellCount = 40000;
			var matrix = [];
			var i = 0;
			var j = 0;
			var operations = [];

			if ((beforeTokens.length + 1) * (afterTokens.length + 1) > maxCellCount) {
				return null;
			}

			for (i = 0; i <= beforeTokens.length; i += 1) {
				matrix[i] = [];
				for (j = 0; j <= afterTokens.length; j += 1) {
					matrix[i][j] = 0;
				}
			}

			for (i = 1; i <= beforeTokens.length; i += 1) {
				for (j = 1; j <= afterTokens.length; j += 1) {
					if (beforeTokens[i - 1] === afterTokens[j - 1]) {
						matrix[i][j] = matrix[i - 1][j - 1] + 1;
					} else {
						matrix[i][j] = Math.max(matrix[i - 1][j], matrix[i][j - 1]);
					}
				}
			}

			i = beforeTokens.length;
			j = afterTokens.length;

			while (i > 0 || j > 0) {
				if (i > 0 && j > 0 && beforeTokens[i - 1] === afterTokens[j - 1]) {
					operations.push({ type: 'equal', value: beforeTokens[i - 1] });
					i -= 1;
					j -= 1;
				} else if (j > 0 && (i === 0 || matrix[i][j - 1] > matrix[i - 1][j])) {
					operations.push({ type: 'added', value: afterTokens[j - 1] });
					j -= 1;
				} else if (i > 0) {
					operations.push({ type: 'removed', value: beforeTokens[i - 1] });
					i -= 1;
				}
			}

			operations.reverse();

			return operations.reduce(function (merged, operation) {
				var previous = merged.length ? merged[merged.length - 1] : null;
				if (previous && previous.type === operation.type) {
					previous.value += operation.value;
					return merged;
				}

				merged.push({
					type: operation.type,
					value: operation.value
				});
				return merged;
			}, []);
		}

		function createInlineWordDiffBlock(beforeValue, afterValue) {
			var block = createElement('div', 'omo-holon-history-popup__diff-value omo-holon-history-popup__diff-inline');
			var operations = buildWordDiffOperations(beforeValue, afterValue);
			var previousType = '';
			var previousValue = '';

			if (!Array.isArray(operations) || !operations.length) {
				block.textContent = normalizeText(afterValue);
				return block;
			}

			operations.forEach(function (operation) {
				var span;

				if (operation.type === 'equal') {
					block.appendChild(document.createTextNode(operation.value));
					previousType = operation.type;
					previousValue = operation.value;
					return;
				}

				if (
					previousType !== ''
					&& previousType !== 'equal'
					&& !/\s$/.test(previousValue)
					&& !/^\s/.test(operation.value)
				) {
					block.appendChild(document.createTextNode(' '));
				}

				span = createElement(
					'span',
					operation.type === 'added'
						? 'omo-holon-history-popup__diff-inline-added'
						: 'omo-holon-history-popup__diff-inline-removed'
				);
				span.textContent = operation.value;
				block.appendChild(span);
				previousType = operation.type;
				previousValue = operation.value;
			});

			return block;
		}

		function createSection(title) {
			var section = createElement('section', 'omo-holon-history-popup__diff-section');
			section.appendChild(createElement('div', 'omo-holon-history-popup__diff-title', title));
			return section;
		}

		function appendEmptyState(container, text) {
			container.appendChild(createElement('div', 'omo-holon-history-popup__diff-empty', text));
		}

		function decodeBase64Json(base64Value) {
			var binary = window.atob(base64Value);
			var bytes = [];
			var index = 0;

			for (index = 0; index < binary.length; index += 1) {
				bytes.push('%' + ('00' + binary.charCodeAt(index).toString(16)).slice(-2));
			}

			return JSON.parse(decodeURIComponent(bytes.join('')));
		}

		function buildItemKey(item) {
			if (item && typeof item === 'object') {
				try {
					return JSON.stringify(item);
				} catch (error) {
					return normalizeText(item.id || item.value || item.label || item.title || '');
				}
			}

			return normalizeText(item);
		}

		function buildItemLabel(item) {
			if (item && typeof item === 'object') {
				var mainLabel = normalizeText(item.title || item.label || item.value || item.id || '');
				var secondaryLabel = normalizeText(item.description || item.text || '');
				if (mainLabel !== '' && secondaryLabel !== '') {
					return mainLabel + ' - ' + secondaryLabel;
				}
				if (mainLabel !== '') {
					return mainLabel;
				}

				try {
					return JSON.stringify(item);
				} catch (error) {
					return '[objet]';
				}
			}

			return normalizeText(item);
		}

		function buildItemComparableText(item) {
			if (item && typeof item === 'object') {
				return normalizeText([
					item.title || '',
					item.label || '',
					item.value || '',
					item.description || '',
					item.text || '',
					item.id || ''
				].join(' '));
			}

			return normalizeText(item);
		}

		function buildItemIdentity(item) {
			if (!item || typeof item !== 'object') {
				return '';
			}

			if (normalizeText(item.id) !== '') {
				return 'id:' + normalizeText(item.id).toLowerCase();
			}
			if (normalizeText(item.title) !== '') {
				return 'title:' + normalizeText(item.title).toLowerCase();
			}
			if (normalizeText(item.label) !== '') {
				return 'label:' + normalizeText(item.label).toLowerCase();
			}
			if (normalizeText(item.value) !== '' && normalizeText(item.description || item.text || '') === '') {
				return 'value:' + normalizeText(item.value).toLowerCase();
			}

			return '';
		}

		function tokenizeComparableWords(text) {
			var matches = normalizeText(text).toLowerCase().match(/[a-z0-9\u00c0-\u017f]+/gi);
			return Array.isArray(matches) ? matches : [];
		}

		function computeItemSimilarity(beforeItem, afterItem, beforeIndex, afterIndex) {
			var beforeIdentity = buildItemIdentity(beforeItem);
			var afterIdentity = buildItemIdentity(afterItem);
			var beforeWords;
			var afterWords;
			var beforeSet = {};
			var sharedCount = 0;
			var union = 0;
			var score = 0;

			if (beforeIdentity !== '' && beforeIdentity === afterIdentity) {
				return 2;
			}

			beforeWords = tokenizeComparableWords(buildItemComparableText(beforeItem));
			afterWords = tokenizeComparableWords(buildItemComparableText(afterItem));

			beforeWords.forEach(function (word) {
				beforeSet[word] = true;
			});

			afterWords.forEach(function (word) {
				if (beforeSet[word]) {
					sharedCount += 1;
				}
				beforeSet[word] = true;
			});

			union = Object.keys(beforeSet).length;
			score = union > 0 ? sharedCount / union : 0;

			if (
				beforeItem && typeof beforeItem === 'object'
				&& afterItem && typeof afterItem === 'object'
				&& normalizeText(beforeItem.title || beforeItem.label) !== ''
				&& normalizeText(beforeItem.title || beforeItem.label) === normalizeText(afterItem.title || afterItem.label)
			) {
				score += 0.9;
			}

			if (Math.abs(beforeIndex - afterIndex) === 0) {
				score += 0.15;
			} else if (Math.abs(beforeIndex - afterIndex) === 1) {
				score += 0.05;
			}

			return score;
		}

		function pairModifiedListItems(removedEntries, addedEntries) {
			var candidates = [];
			var usedBefore = {};
			var usedAfter = {};
			var matches = [];

			removedEntries.forEach(function (beforeEntry) {
				addedEntries.forEach(function (afterEntry) {
					var score = computeItemSimilarity(beforeEntry.item, afterEntry.item, beforeEntry.index, afterEntry.index);
					if (score >= 0.6) {
						candidates.push({
							beforeEntry: beforeEntry,
							afterEntry: afterEntry,
							score: score,
							distance: Math.abs(beforeEntry.index - afterEntry.index)
						});
					}
				});
			});

			candidates.sort(function (left, right) {
				if (right.score !== left.score) {
					return right.score - left.score;
				}
				return left.distance - right.distance;
			});

			candidates.forEach(function (candidate) {
				var beforeKey = String(candidate.beforeEntry.index);
				var afterKey = String(candidate.afterEntry.index);
				if (usedBefore[beforeKey] || usedAfter[afterKey]) {
					return;
				}

				usedBefore[beforeKey] = true;
				usedAfter[afterKey] = true;
				matches.push(candidate);
			});

			return {
				matches: matches,
				unmatchedRemoved: removedEntries.filter(function (entry) {
					return !usedBefore[String(entry.index)];
				}),
				unmatchedAdded: addedEntries.filter(function (entry) {
					return !usedAfter[String(entry.index)];
				})
			};
		}

		function createListItemChangedBlock(beforeItem, afterItem) {
			var wrapper = createElement(
				'div',
				'omo-holon-history-popup__diff-list-item omo-holon-history-popup__diff-list-item--changed'
			);
			var beforeIsObject = beforeItem && typeof beforeItem === 'object';
			var afterIsObject = afterItem && typeof afterItem === 'object';
			var beforeTitle = normalizeText(beforeIsObject ? (beforeItem.title || beforeItem.label || beforeItem.value || beforeItem.id || '') : beforeItem);
			var afterTitle = normalizeText(afterIsObject ? (afterItem.title || afterItem.label || afterItem.value || afterItem.id || '') : afterItem);
			var beforeDescription = normalizeText(beforeIsObject ? (beforeItem.description || beforeItem.text || '') : '');
			var afterDescription = normalizeText(afterIsObject ? (afterItem.description || afterItem.text || '') : '');

			if (beforeIsObject || afterIsObject) {
				if (beforeTitle !== '' || afterTitle !== '') {
					if (beforeTitle !== '' && afterTitle !== '' && beforeTitle !== afterTitle) {
						wrapper.appendChild(createInlineWordDiffBlock(beforeTitle, afterTitle));
						wrapper.lastChild.classList.add('omo-holon-history-popup__diff-list-item-title');
					} else {
						wrapper.appendChild(createElement('div', 'omo-holon-history-popup__diff-list-item-title', afterTitle || beforeTitle));
					}
				}

				if (beforeDescription !== '' || afterDescription !== '') {
					if (beforeDescription !== '' && afterDescription !== '' && beforeDescription !== afterDescription) {
						wrapper.appendChild(createInlineWordDiffBlock(beforeDescription, afterDescription));
						wrapper.lastChild.classList.add('omo-holon-history-popup__diff-list-item-description');
					} else {
						wrapper.appendChild(createElement('div', 'omo-holon-history-popup__diff-list-item-description', afterDescription || beforeDescription));
					}
				}

				return wrapper;
			}

			wrapper.appendChild(createInlineWordDiffBlock(beforeTitle, afterTitle));
			return wrapper;
		}

		function buildPropertyLabel(propertySnapshot, propertyId) {
			var snapshot = safeObject(propertySnapshot);
			var label = normalizeText(snapshot.name || snapshot.shortname || '');
			if (label !== '') {
				return label;
			}

			return 'Propriete ' + propertyId;
		}

		function createPropertyCard(title, status) {
			return createElement(
				'div',
				'omo-holon-history-popup__diff-card omo-holon-history-popup__diff-card--' + status
			);
		}

		function renderScalarProperty(section, title, beforeValue, afterValue, status, options) {
			var card = createPropertyCard(title, status);
			var config = safeObject(options);
			var useInlineWordDiff = !!config.useInlineWordDiff;
			card.appendChild(createElement('div', 'omo-holon-history-popup__diff-card-title', title));

			if (status === 'added') {
				card.appendChild(createValueBlock(afterValue, 'omo-holon-history-popup__diff-value--added'));
			} else if (status === 'removed') {
				card.appendChild(createValueBlock(beforeValue, 'omo-holon-history-popup__diff-value--removed'));
			} else if (useInlineWordDiff && normalizeText(beforeValue) !== '' && normalizeText(afterValue) !== '') {
				card.appendChild(createInlineWordDiffBlock(beforeValue, afterValue));
			} else {
				card.appendChild(createValueBlock(beforeValue, 'omo-holon-history-popup__diff-value--removed'));
				card.appendChild(createValueBlock(afterValue, 'omo-holon-history-popup__diff-value--added'));
			}

			section.appendChild(card);
		}

		function renderListProperty(section, title, beforeItems, afterItems, status) {
			var card = createPropertyCard(title, status);
			var list = createElement('div', 'omo-holon-history-popup__diff-list');
			var beforeMap = {};
			var afterMap = {};
			var addedKeys = [];
			var removedKeys = [];
			var unchangedCount = 0;
			var index = 0;
			var removedEntries = [];
			var addedEntries = [];
			var pairing = null;

			card.appendChild(createElement('div', 'omo-holon-history-popup__diff-card-title', title));

			for (index = 0; index < beforeItems.length; index += 1) {
				beforeMap[buildItemKey(beforeItems[index])] = beforeItems[index];
			}
			for (index = 0; index < afterItems.length; index += 1) {
				afterMap[buildItemKey(afterItems[index])] = afterItems[index];
			}

			Object.keys(afterMap).forEach(function (key) {
				if (!Object.prototype.hasOwnProperty.call(beforeMap, key)) {
					addedKeys.push(key);
				} else {
					unchangedCount += 1;
				}
			});
			Object.keys(beforeMap).forEach(function (key) {
				if (!Object.prototype.hasOwnProperty.call(afterMap, key)) {
					removedKeys.push(key);
				}
			});

			if (status === 'added') {
				afterItems.forEach(function (item) {
					list.appendChild(createElement(
						'div',
						'omo-holon-history-popup__diff-list-item omo-holon-history-popup__diff-list-item--added',
						buildItemLabel(item)
					));
				});
			} else if (status === 'removed') {
				beforeItems.forEach(function (item) {
					list.appendChild(createElement(
						'div',
						'omo-holon-history-popup__diff-list-item omo-holon-history-popup__diff-list-item--removed',
						buildItemLabel(item)
					));
				});
			} else {
				removedKeys.forEach(function (key) {
					removedEntries.push({
						key: key,
						item: beforeMap[key],
						index: beforeItems.findIndex(function (item) {
							return buildItemKey(item) === key;
						})
					});
				});

				addedKeys.forEach(function (key) {
					addedEntries.push({
						key: key,
						item: afterMap[key],
						index: afterItems.findIndex(function (item) {
							return buildItemKey(item) === key;
						})
					});
				});

				pairing = pairModifiedListItems(removedEntries, addedEntries);

				pairing.matches.forEach(function (match) {
					list.appendChild(createListItemChangedBlock(match.beforeEntry.item, match.afterEntry.item));
				});

				pairing.unmatchedRemoved.forEach(function (entry) {
					list.appendChild(createElement(
						'div',
						'omo-holon-history-popup__diff-list-item omo-holon-history-popup__diff-list-item--removed',
						buildItemLabel(entry.item)
					));
				});

				pairing.unmatchedAdded.forEach(function (entry) {
					list.appendChild(createElement(
						'div',
						'omo-holon-history-popup__diff-list-item omo-holon-history-popup__diff-list-item--added',
						buildItemLabel(entry.item)
					));
				});

				if (addedKeys.length === 0 && removedKeys.length === 0 && JSON.stringify(beforeItems) !== JSON.stringify(afterItems)) {
					list.appendChild(createElement(
						'div',
						'omo-holon-history-popup__diff-list-item omo-holon-history-popup__diff-list-item--muted',
						'Ordre modifie'
					));
				} else if (unchangedCount > 0) {
					list.appendChild(createElement(
						'div',
						'omo-holon-history-popup__diff-list-item omo-holon-history-popup__diff-list-item--muted',
						unchangedCount + ' element(s) inchanges'
					));
				}
			}

			card.appendChild(list);
			section.appendChild(card);
		}

		function renderHolonFieldDiffs(payload, root) {
			var beforeHolon = safeObject(safeObject(payload.before).holon);
			var afterHolon = safeObject(safeObject(payload.after).holon);
			var fieldLabels = {
				name: 'Nom',
				color: 'Couleur',
				icon: 'Icone',
				banner: 'Banniere'
			};
			var section = createSection('Holon');
			var hasChanges = false;

			Object.keys(fieldLabels).forEach(function (field) {
				var beforeValue = decodeHtmlToText(beforeHolon[field] || '');
				var afterValue = decodeHtmlToText(afterHolon[field] || '');
				var useInlineWordDiff = field === 'name';
				if (beforeValue === afterValue) {
					return;
				}

				hasChanges = true;
				renderScalarProperty(section, fieldLabels[field], beforeValue, afterValue, 'changed', {
					useInlineWordDiff: useInlineWordDiff
				});
			});

			if (hasChanges) {
				root.appendChild(section);
			}
		}

		function renderPropertyDiffs(payload, root) {
			var beforeProperties = safeObject(safeObject(payload.before).properties);
			var afterProperties = safeObject(safeObject(payload.after).properties);
			var propertyIds = Object.keys(beforeProperties).concat(Object.keys(afterProperties));
			var uniqueIds = [];
			var section = createSection('Proprietes');
			var hasChanges = false;

			propertyIds.forEach(function (propertyId) {
				if (uniqueIds.indexOf(propertyId) === -1) {
					uniqueIds.push(propertyId);
				}
			});

			uniqueIds.sort(function (left, right) {
				return Number(left) - Number(right);
			});

			uniqueIds.forEach(function (propertyId) {
				var beforeProperty = safeObject(beforeProperties[propertyId]);
				var afterProperty = safeObject(afterProperties[propertyId]);
				var hasBefore = Object.keys(beforeProperty).length > 0;
				var hasAfter = Object.keys(afterProperty).length > 0;
				var title = buildPropertyLabel(hasAfter ? afterProperty : beforeProperty, propertyId);
				var beforeItems = safeArray(beforeProperty.visibleItems);
				var afterItems = safeArray(afterProperty.visibleItems);
				var beforeValue = decodeHtmlToText(beforeProperty.visibleValue || '');
				var afterValue = decodeHtmlToText(afterProperty.visibleValue || '');
				var formatId = Number(beforeProperty.formatId || afterProperty.formatId || 0);
				var isList = beforeItems.length > 0 || afterItems.length > 0;
				var useInlineWordDiff = formatId === 1 || formatId === 5;

				if (!hasBefore && hasAfter) {
					hasChanges = true;
					if (isList) {
						renderListProperty(section, title, [], afterItems, 'added');
					} else {
						renderScalarProperty(section, title, '', afterValue, 'added', {
							useInlineWordDiff: useInlineWordDiff
						});
					}
					return;
				}

				if (hasBefore && !hasAfter) {
					hasChanges = true;
					if (isList) {
						renderListProperty(section, title, beforeItems, [], 'removed');
					} else {
						renderScalarProperty(section, title, beforeValue, '', 'removed', {
							useInlineWordDiff: useInlineWordDiff
						});
					}
					return;
				}

				if (!hasBefore || !hasAfter) {
					return;
				}

				if (isList) {
					if (JSON.stringify(beforeItems) === JSON.stringify(afterItems)) {
						return;
					}

					hasChanges = true;
					renderListProperty(section, title, beforeItems, afterItems, 'changed');
					return;
				}

				if (beforeValue === afterValue) {
					return;
				}

				hasChanges = true;
				renderScalarProperty(section, title, beforeValue, afterValue, 'changed', {
					useInlineWordDiff: useInlineWordDiff
				});
			});

			if (hasChanges) {
				root.appendChild(section);
			}
		}

		function renderCreatedState(payload, root) {
			var afterProperties = safeObject(safeObject(payload.after).properties);
			var propertyIds = Object.keys(afterProperties).sort(function (left, right) {
				return Number(left) - Number(right);
			});
			var section = createSection('Etat cree');
			var hasContent = false;

			propertyIds.forEach(function (propertyId) {
				var property = safeObject(afterProperties[propertyId]);
				var title = buildPropertyLabel(property, propertyId);
				var items = safeArray(property.visibleItems);
				var value = decodeHtmlToText(property.visibleValue || '');
				var isList = items.length > 0;

				if (isList) {
					if (items.length === 0) {
						return;
					}
					hasContent = true;
					renderListProperty(section, title, [], items, 'added');
					return;
				}

				if (value === '') {
					return;
				}

				hasContent = true;
				renderScalarProperty(section, title, '', value, 'added');
			});

			if (hasContent) {
				root.appendChild(section);
			}
		}

		function renderDiff(details) {
			var container = details.querySelector('[data-history-diff-container="1"]');
			var payloadBase64 = normalizeText(details.getAttribute('data-history-payload'));
			var payload = null;

			if (!container || payloadBase64 === '') {
				return;
			}

			container.innerHTML = '';

			try {
				payload = decodeBase64Json(payloadBase64);
			} catch (error) {
				appendEmptyState(container, 'Impossible de lire le detail de cette entree.');
				return;
			}

			if (!payload || typeof payload !== 'object') {
				appendEmptyState(container, 'Aucun detail supplementaire disponible.');
				return;
			}

			if (payload.before && payload.after) {
				renderHolonFieldDiffs(payload, container);
				renderPropertyDiffs(payload, container);
			} else if (payload.after) {
				renderCreatedState(payload, container);
			}

			if (!container.children.length) {
				appendEmptyState(container, 'Aucun diff visuel supplementaire disponible pour cette entree.');
			}

			details.setAttribute('data-history-rendered', '1');
		}

		function bindHistoryDiffDetails(scope) {
			scope.querySelectorAll('[data-history-diff="1"]').forEach(function (details) {
				if (details.getAttribute('data-history-bound') === '1') {
					return;
				}

				details.setAttribute('data-history-bound', '1');
				details.addEventListener('toggle', function () {
					if (!details.open || details.getAttribute('data-history-rendered') === '1') {
						return;
					}

					renderDiff(details);
				});
			});
		}

		function startOfDay(date) {
			return new Date(date.getFullYear(), date.getMonth(), date.getDate());
		}

		function addDays(date, days) {
			return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
		}

		function startOfWeek(date) {
			var current = startOfDay(date);
			var day = current.getDay();
			var distance = day === 0 ? 6 : day - 1;

			return addDays(current, -distance);
		}

		function startOfMonth(date) {
			return new Date(date.getFullYear(), date.getMonth(), 1);
		}

		function getRelativeHistoryGroups() {
			var now = new Date();
			var today = startOfDay(now);
			var tomorrow = addDays(today, 1);
			var yesterday = addDays(today, -1);
			var thisWeekStart = startOfWeek(now);
			var lastWeekStart = addDays(thisWeekStart, -7);
			var thisMonthStart = startOfMonth(now);
			var lastMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1);

			return [
				{ key: 'today', label: "Aujourd'hui", start: today, end: tomorrow },
				{ key: 'yesterday', label: 'Hier', start: yesterday, end: today },
				{ key: 'this_week', label: 'Cette semaine', start: thisWeekStart, end: yesterday },
				{ key: 'last_week', label: 'La semaine passee', start: lastWeekStart, end: thisWeekStart },
				{ key: 'this_month', label: 'Ce mois-ci', start: thisMonthStart, end: lastWeekStart },
				{ key: 'last_month', label: 'Le mois passe', start: lastMonthStart, end: thisMonthStart },
				{ key: 'earlier', label: 'Plus ancien', start: null, end: lastMonthStart }
			];
		}

		function getRelativeHistoryGroupForTimestamp(timestamp, groups) {
			var date = timestamp > 0 ? new Date(timestamp * 1000) : null;

			if (!date || Number.isNaN(date.getTime())) {
				return groups[groups.length - 1];
			}

			return groups.find(function (group) {
				var afterStart = !group.start || date >= group.start;
				var beforeEnd = !group.end || date < group.end;
				return afterStart && beforeEnd;
			}) || groups[groups.length - 1];
		}

		function regroupHistoryFeed() {
			var items;
			var fragment;
			var currentGroupKey = null;
			var currentSection = null;
			var currentSectionList = null;
			var groups = getRelativeHistoryGroups();

			if (!list) {
				return;
			}

			items = Array.prototype.slice.call(list.querySelectorAll('.omo-holon-history-popup__item'));
			if (!items.length) {
				return;
			}

			fragment = document.createDocumentFragment();

			items.forEach(function (item) {
				var timestamp = Number(item.getAttribute('data-history-timestamp') || 0);
				var group = getRelativeHistoryGroupForTimestamp(timestamp, groups);

				if (!group || group.key !== currentGroupKey) {
					currentGroupKey = group ? group.key : 'earlier';
					currentSection = createElement('section', 'omo-holon-history-popup__group');
					currentSectionList = createElement('div', 'omo-holon-history-popup__group-list');
					currentSection.appendChild(createElement('h3', 'omo-holon-history-popup__group-title', group ? group.label : 'Plus ancien'));
					currentSection.appendChild(currentSectionList);
					fragment.appendChild(currentSection);
				}

				currentSectionList.appendChild(item);
			});

			list.replaceChildren(fragment);
		}

		function getScrollContainer(element) {
			var current = element ? element.parentElement : null;
			while (current) {
				var style = window.getComputedStyle(current);
				if (
					/(auto|scroll)/.test(style.overflowY || '')
					&& current.scrollHeight > current.clientHeight + 5
				) {
					return current;
				}
				current = current.parentElement;
			}

			return window;
		}

		function buildHistoryFeedUrl(state) {
			return 'api/holons/history_popup.php?hid=' + encodeURIComponent(state.holonId)
				+ '&fragment=items'
				+ '&offset=' + encodeURIComponent(state.nextOffset)
				+ '&limit=' + encodeURIComponent(state.pageLimit);
		}

		var root = document.querySelector('[data-holon-history-root="1"]');
		var list = root ? root.querySelector('[data-history-list="1"]') : null;
		var feedStatus = root ? root.querySelector('[data-history-feed-status="1"]') : null;
		var state = root ? {
			holonId: Number(root.getAttribute('data-holon-id') || 0),
			pageLimit: Number(root.getAttribute('data-page-limit') || 10),
			nextOffset: Number(root.getAttribute('data-next-offset') || 0),
			hasMore: String(root.getAttribute('data-has-more') || '0') === '1',
			loading: false
		} : null;
		var scrollContainer = root ? getScrollContainer(root) : window;
		var scrollTarget = scrollContainer === window ? window : scrollContainer;

		function updateFeedStatus(text, isHidden, isLoading) {
			if (!feedStatus) {
				return;
			}

			feedStatus.textContent = normalizeText(text);
			feedStatus.hidden = !!isHidden;
			feedStatus.classList.toggle('is-loading', !!isLoading);
		}

		function appendHistoryHtml(html) {
			var wrapper = document.createElement('div');
			wrapper.innerHTML = html;
			bindHistoryDiffDetails(wrapper);

			while (wrapper.firstChild) {
				list.appendChild(wrapper.firstChild);
			}

			regroupHistoryFeed();
		}

		function isNearFeedBottom() {
			var rect;
			var containerRect;

			if (!feedStatus || feedStatus.hidden) {
				return false;
			}

			rect = feedStatus.getBoundingClientRect();
			if (scrollContainer === window) {
				return rect.top <= window.innerHeight + 160;
			}

			containerRect = scrollContainer.getBoundingClientRect();
			return rect.top <= containerRect.bottom + 120;
		}

		function maybeLoadMoreHistory() {
			if (!state || state.loading || !state.hasMore) {
				return;
			}

			if (isNearFeedBottom()) {
				loadMoreHistory();
			}
		}

		function loadMoreHistory() {
			if (!state || state.loading || !state.hasMore) {
				return;
			}

			state.loading = true;
			updateFeedStatus('Chargement...', false, true);

			fetch(buildHistoryFeedUrl(state), {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
				.then(function (response) {
					return response.json().then(function (data) {
						return {
							ok: response.ok,
							data: data
						};
					});
				})
				.then(function (result) {
					state.loading = false;

					if (!result.ok || !result.data || !result.data.status) {
						state.hasMore = false;
						updateFeedStatus('Impossible de charger la suite.', false, false);
						return;
					}

					if (normalizeText(result.data.html) !== '') {
						appendHistoryHtml(String(result.data.html));
					}

					state.hasMore = !!result.data.hasMore;
					state.nextOffset = Number(result.data.nextOffset || state.nextOffset);

					if (state.hasMore) {
						updateFeedStatus('Faites defiler pour charger la suite.', false, false);
						window.setTimeout(maybeLoadMoreHistory, 0);
					} else {
						updateFeedStatus('Fin de l\'historique.', false, false);
					}
				})
				.catch(function () {
					state.loading = false;
					state.hasMore = false;
					updateFeedStatus('Impossible de charger la suite.', false, false);
				});
		}

		if (!root || !list || !state) {
			return;
		}

		bindHistoryDiffDetails(root);
		regroupHistoryFeed();

		scrollTarget.addEventListener('scroll', maybeLoadMoreHistory, { passive: true });
		window.addEventListener('resize', maybeLoadMoreHistory, { passive: true });
		window.setTimeout(maybeLoadMoreHistory, 0);
	})();
</script>
