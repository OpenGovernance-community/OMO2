<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/lms/video.php';
use dbObject\{DbObject, Organization, Parcours, VideoEmbedHelper};
class TutorialTestOrganization extends Organization { public function snapshotParameters(): array { return $this->getLexiconParameters(); } }

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $_SESSION['currentUser'] = 0;
    $org = mcpFixture(McpTestOrganization::class, ['name' => 'Tutorial settings fixture', 'color' => '#ffd966']);
    mcpCheck($org->getTutorialSettings() === Organization::DEFAULT_TUTORIAL_SETTINGS, 'New organizations use native video controls.');
    $org->setParametersArray(['unrelated' => ['preserved' => true]]);
    $org->setTutorialSettings(['customVideoLayout' => '1', 'videoMask' => 'https://example.invalid/mask.png?a=1&b=2']);
    mcpCheck(!empty($org->save()['status']), 'Save settings.');
    $loaded = new TutorialTestOrganization();
    $loaded->load($org->getId());
    mcpCheck($loaded->getTutorialSettings()['customVideoLayout'], 'Anonymous rendering reads saved video settings.');
    mcpCheck(!$loaded->canReadField('parameters'), 'Public video settings do not expose private organization parameters.');
    $parcours = mcpFixture(Parcours::class, ['title' => 'Public video fixture', 'IDorganization' => $org->getId(), 'ispublic' => 1]);
    mcpCheck($parcours->getVideoPresentationForOrganization()['customVideoLayout'], 'Public links use owner settings.');
    $other = mcpFixture(McpTestOrganization::class, ['name' => 'Other tutorial organization']);
    mcpCheck(!$parcours->getVideoPresentationForOrganization((int)$other->getId())['customVideoLayout'], 'Organization views use that organization settings without leaking owner preferences.');
    mcpCheck($org->getTutorialSettings()['videoMask'] === $loaded->getTutorialSettings()['videoMask'], 'Mask survives database reload.');
    mcpCheck(isset($loaded->getTransferConfigurationParameters()[Organization::TUTORIAL_SETTINGS_PARAMETER]), 'Tutorial settings are included in transferred configuration.');
    foreach (['javascript:alert(1)', '//example.invalid/image.png', '/img/../private.png', "/img/evil\\mask.png", "https://example.invalid/a\nb.png", [], 123] as $mask) {
        $rejected = false;
        try { $loaded->setTutorialSettings(['videoMask' => $mask]); } catch (InvalidArgumentException $error) { $rejected = true; }
        mcpCheck($rejected, 'Invalid mask rejected: ' . json_encode($mask));
        mcpCheck($loaded->getTutorialSettings()['customVideoLayout'], 'Rejected input preserves prior settings.');
    }
    $vimeo = 'https://player.vimeo.com/video/123456789?h=abcdef';
    $native = VideoEmbedHelper::getEmbedData($vimeo);
    mcpCheck($native['controlMode'] === 'native' && $native['embedUrl'] === $vimeo, 'Vimeo is native by default and preserves its privacy hash.');
    $custom = VideoEmbedHelper::getEmbedData($vimeo, true);
    parse_str(parse_url($custom['embedUrl'], PHP_URL_QUERY), $query);
    mcpCheck($custom['controlMode'] === 'custom' && $query['controls'] === '0' && $query['h'] === 'abcdef', 'Custom Vimeo keeps the hash and removes provider controls.');
    mcpCheck(VideoEmbedHelper::getEmbedData('https://youtu.be/abc123', true)['controlMode'] === 'native', 'Other providers retain native controls.');
    foreach ([false, true] as $enabled) {
        ob_start();
        commonLmsRenderMissionVideo($vimeo, ['customVideoLayout' => $enabled, 'videoMask' => '/img/mask.png', 'color' => '#ffd966'], 'Video <test>');
        $html = ob_get_clean();
        mcpCheck(str_contains($html, 'branding-overlay') === $enabled, 'Only customized video contains a mask.');
        mcpCheck(str_contains($html, 'custom-controls') === $enabled, 'Only customized video contains custom controls.');
        mcpCheck(str_contains($html, 'Video &lt;test&gt;'), 'Escape video titles.');
    }
    $loaded->setTutorialSettings(['customVideoLayout' => false, 'videoMask' => '']);
    mcpCheck(!empty($loaded->save()['status']), 'Disable custom layout.');
    mcpCheck($loaded->getTutorialSettings() === Organization::DEFAULT_TUTORIAL_SETTINGS, 'Empty mask uses existing default image.');
    $raw = $loaded->snapshotParameters();
    mcpCheck(!empty($raw['unrelated']['preserved']), 'Saving tutorial preferences preserves unrelated organization parameters.');
    echo "tutorial_video_settings_test: OK\n";
} finally {
    $pdo->rollBack();
}
