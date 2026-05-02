<?php
require_once("../config.php");
require_once("../shared_functions.php");

$faqId = (int)($_GET["id"] ?? 0);
if ($faqId <= 0) {
	die("FAQ invalide");
}

$faq = new \dbObject\FAQ();
if (!$faq->load($faqId) || !(int)$faq->get("id")) {
	die("FAQ introuvable");
}

if (!(int)$faq->get("isactive")) {
	die("Cette FAQ n’est pas disponible");
}

$faq->set("viewcount", (int)$faq->get("viewcount") + 1);
$faq->save();
?>
<div class="faq-popup__item is-open">
	<div style="padding: 18px;">
		<div style="margin-bottom: 16px;">
			<button type="button" class="faq-popup__back" data-faq-back>Retour à la FAQ</button>
		</div>
		<h4 style="margin: 0 0 12px; font-size: 20px; color: #0f172a;"><?= htmlspecialchars((string)$faq->get("question")) ?></h4>
		<div style="color: #334155; line-height: 1.7; margin-bottom: 18px;">
			<?= nl2br(htmlspecialchars((string)$faq->get("answer"))) ?>
		</div>
		<?php if ((string)$faq->get("detail") !== ''): ?>
			<div style="padding: 16px; border-radius: 14px; background: #f8fafc; color: #1e293b; line-height: 1.7;">
				<?= (string)$faq->get("detail") ?>
			</div>
		<?php endif; ?>
		<div style="margin-top: 16px; color: #64748b; font-size: 14px;">
			Consultations: <?= (int)$faq->get("viewcount") ?>
		</div>
	</div>
</div>
