<?php
require_once dirname(__DIR__) . '/avatar.php';
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonLmsRenderMissionVideo(string $video, array $presentation, string $title): void
{
    static $lang = null;
    $sourceLang = [
        'play' => ['text' => 'Lire', 'context' => 'Custom tutorial video play button.'],
        'pause' => ['text' => 'Pause', 'context' => 'Custom tutorial video pause button.'],
        'sound' => ['text' => 'Son', 'context' => 'Custom tutorial video sound button.'],
        'muted' => ['text' => 'Muet', 'context' => 'Custom tutorial video muted button.'],
        'volume' => ['text' => 'Volume', 'context' => 'Custom tutorial video volume control.'],
        'unavailable' => ['text' => "La vidéo de cette mission n’est pas disponible pour le moment.", 'context' => 'Unavailable tutorial video message.'],
    ];
    $lang ??= loadTranslationBundle('lms_video', translationBundleResolveRequestLocale('lang'), $sourceLang);
    $labels = [];
    foreach (array_keys($sourceLang) as $key) $labels[$key] = t($key, [], $lang, $sourceLang);
    $data = \dbObject\VideoEmbedHelper::getEmbedData($video, !empty($presentation['customVideoLayout']));
    if ($data['embedUrl'] === '') {
        if ($video !== '') echo '<p>' . htmlspecialchars($labels['unavailable'], ENT_QUOTES, 'UTF-8') . '</p>';
        return;
    }
    $custom = $data['controlMode'] === 'custom';
    $native = \dbObject\VideoEmbedHelper::buildEmbedUrl($video);
    $color = trim((string)($presentation['color'] ?? ''));
    $style = $custom && preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $color)
        ? '--primary: ' . $color . '; --param-video-control-text: ' . commonAvatarResolveTextColor($color) . ';'
        : '';
    $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="video-portal" style="<?= $escape($style) ?>" data-label-play="<?= $escape($labels['play']) ?>" data-label-pause="<?= $escape($labels['pause']) ?>" data-label-sound="<?= $escape($labels['sound']) ?>" data-label-muted="<?= $escape($labels['muted']) ?>">
        <div class="video-inner">
            <iframe id="missionVideoPlayer" data-video-provider="<?= $escape($data['provider']) ?>"
                data-video-control-mode="<?= $escape($data['controlMode']) ?>" data-native-video-src="<?= $escape($native) ?>"
                src="<?= $escape($data['embedUrl']) ?>" title="<?= $escape($title) ?>"
                frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
            <?php if ($custom): ?>
            <img class="branding-overlay" src="<?= $escape($presentation['videoMask']) ?>" alt="" aria-hidden="true">
            <?php endif; ?>
        </div>
        <?php if ($custom): ?>
        <div class="custom-controls">
            <button id="playBtn" type="button"><?= $escape($labels['play']) ?></button>
            <div class="progressvideo"><div class="progressvideo-bar"></div></div>
            <span id="time">0:00</span>
            <div class="volume-controls">
                <button id="volumeBtn" type="button"><?= $escape($labels['sound']) ?></button>
                <input id="volumeSlider" type="range" min="0" max="100" step="1" value="100" aria-label="<?= $escape($labels['volume']) ?>">
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
