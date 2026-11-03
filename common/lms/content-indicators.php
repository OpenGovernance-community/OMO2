<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonLmsRenderContentIndicators(array $indicators, bool $inverse = false): void
{
    static $lang = null;
    $sourceLang = [
        'video.present' => ['text' => 'Video presente', 'context' => 'Tooltip for a course containing video content.'],
        'video.absent' => ['text' => 'Aucune video', 'context' => 'Tooltip for a course without video content.'],
        'homework.present' => ['text' => 'Devoirs presents', 'context' => 'Tooltip for a course containing homework.'],
        'homework.absent' => ['text' => 'Aucun devoir', 'context' => 'Tooltip for a course without homework.'],
        'quiz.present' => ['text' => 'Quiz present', 'context' => 'Tooltip for a course containing a quiz.'],
        'quiz.absent' => ['text' => 'Aucun quiz', 'context' => 'Tooltip for a course without quizzes.'],
    ];
    $lang ??= loadTranslationBundle('lms_content_indicators', translationBundleResolveRequestLocale('lang'), $sourceLang);
    ?>
    <span class="lms-content-indicators<?= $inverse ? ' lms-content-indicators--inverse' : '' ?>">
        <?php foreach (['video', 'homework', 'quiz'] as $feature):
            $present = !empty($indicators[$feature]);
            $label = t($feature . ($present ? '.present' : '.absent'), [], $lang, $sourceLang);
            ?>
            <span class="lms-content-indicators__item<?= $present ? ' is-present' : '' ?>" role="img" aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" data-lms-content="<?= $feature ?>" data-present="<?= $present ? '1' : '0' ?>">
                <img src="<?= commonAssetUrl('/common/lms/icons/' . $feature . '.png') ?>" width="20" height="20" alt="" aria-hidden="true">
            </span>
        <?php endforeach; ?>
    </span>
    <?php
}

function commonLmsRenderParcoursCover(array $parcours, array $indicators): void
{
    $image = trim((string)($parcours['image'] ?? ''));
    ?>
    <div class="card-image lms-parcours-cover<?= $image === '' ? ' lms-parcours-cover--empty' : '' ?>">
        <?php if ($image !== ''): ?>
            <img src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>" alt="">
        <?php endif; ?>
        <?php commonLmsRenderContentIndicators($indicators, true); ?>
    </div>
    <?php
}
