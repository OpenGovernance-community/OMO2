<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonLmsRenderContentIndicators(array $indicators): void
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
    <span class="lms-content-indicators">
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
