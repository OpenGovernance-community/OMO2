<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\History;
use dbObject\Holon;
use dbObject\Organization;

function omoHistoryParseUtcDate($value)
{
	$value = trim((string)$value);
	if ($value === '') {
		return null;
	}

	try {
		return new DateTimeImmutable($value, new DateTimeZone('UTC'));
	} catch (Throwable $exception) {
		return null;
	}
}

function omoRenderHolonHistoryItems(array $historyItems)
{
	ob_start();
	foreach ($historyItems as $item):
		$timestamp = 0;
		$historyDate = omoHistoryParseUtcDate($item['datecreation'] ?? '');
		if ($historyDate instanceof DateTimeInterface) {
			$timestamp = $historyDate->getTimestamp();
		}
		$dateLabel = $historyDate instanceof DateTimeInterface
			? $historyDate->format('d.m.Y H:i') . ' UTC'
			: trim((string)($item['datecreation'] ?? ''));
		$authorLabel = trim((string)($item['authorDisplayName'] ?? ''));
		$actionLabel = trim((string)($item['actionLabel'] ?? ''));
		$contentHtml = trim((string)($item['contentHtml'] ?? ''));
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
					<span data-history-local-date="1" data-history-timestamp="<?= (int)$timestamp ?>"><?= omoApiEscape($dateLabel) ?></span>
				<?php endif; ?>
				<?php if ($authorLabel !== ''): ?>
					<span>par <?= omoApiEscape($authorLabel) ?></span>
				<?php endif; ?>
				<?php if ($actionLabel !== ''): ?>
					<span class="omo-holon-history-popup__action"><?= omoApiEscape($actionLabel) ?></span>
				<?php endif; ?>
			</div>
			<p class="omo-holon-history-popup__content"><?= $contentHtml !== '' ? nl2br($contentHtml) : nl2br(omoApiEscape((string)($item['contentDisplay'] ?? ''))) ?></p>
			<?php if ($hasDiffData && $payloadBase64 !== ''): ?>
				<details class="omo-holon-history-popup__details omo-change-details" data-history-diff="1" data-history-payload="<?= omoApiEscape($payloadBase64) ?>">
					<summary>Détail</summary>
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
    <div class="omo-holon-history-popup__empty">L’espace demandé est introuvable.</div>
	<?php
	exit;
}

if (!$holon->canViewDetail()) {
	http_response_code(403);
	?>
    <div class="omo-holon-history-popup__empty">Vous n'avez pas le droit de consulter l'historique de cet espace.</div>
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
<link rel="stylesheet" href="/common/choice/change-details.css?v=20260923-lifecycle-details">
<link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/holons/history.css') ?>">

<div
	class="omo-holon-history-popup generic-stack generic-stack--flush"
	data-holon-history-root="1"
	data-holon-id="<?= (int)$holonId ?>"
	data-page-limit="<?= (int)$pageLimit ?>"
	data-next-offset="<?= (int)$nextOffset ?>"
	data-has-more="<?= $hasMore ? '1' : '0' ?>"
>
	<div class="omo-holon-history-popup__header generic-drawer-header generic-drawer-header--sticky">
		<div class="generic-drawer-header__copy omo-holon-history-popup__header-copy">
			<div class="generic-card-title generic-card-title--eyebrow"><?= $isOrganizationHolon ? 'Organisation' : omoApiEscape((string)$holon->getTemplateLabel(true)) ?></div>
			<h3 class="generic-card-title generic-card-title--medium">Historique</h3>
		</div>
	</div>
	<div class="omo-holon-history-popup__shell generic-drawer-content">
	<p class="omo-holon-history-popup__intro generic-description">
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
</div>

<script src="/omo/assets/js/simple-html-field.js?v=20260904-highlight-clear"></script>
<script src="/common/choice/word-diff.js?v=20260816"></script>
<script src="/common/choice/change-details.js?v=20260924-readable-diffs"></script>
<script src="<?= commonAssetUrl('/omo/api/holons/history_popup.js') ?>"></script>
