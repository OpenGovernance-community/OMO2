<?php
require_once dirname(__DIR__) . '/ai_access.php';
require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/session.php';

function commonAssistantRender(int $organizationId, string $organizationName): void
{
    $userId = commonGetCurrentUserId();
    if ($organizationId <= 0 || $userId <= 0 || commonGetCurrentShareToken() !== ''
        || !\dbObject\UserOrganization::hasActiveMembership($userId, $organizationId)) return;
    if (!commonAiUserCanUse($userId)) return;
    if (($_SESSION['common_assistant_contexts'][$organizationId]['user'] ?? 0) !== $userId) {
        $_SESSION['common_assistant_contexts'][$organizationId] = ['user' => $userId, 'csrf' => bin2hex(random_bytes(32))];
    }
    $text = [];
    foreach (['title', 'you', 'working', 'error', 'context', 'sources', 'copy_csv', 'copied_csv', 'copy_error', 'restoring', 'restore_error', 'interrupted'] as $key) $text[$key] = commonAssistantT($key);
    $escape = static fn ($value) => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $config = ['organizationId' => $organizationId, 'csrf' => $_SESSION['common_assistant_contexts'][$organizationId]['csrf'],
        'storageScope' => hash('sha256', $_SESSION['common_assistant_contexts'][$organizationId]['csrf']
            . commonAssistantSessionIdentity(['IDuser' => $userId, 'IDorganization' => $organizationId])),
        'endpoint' => '/common/assistant/endpoint.php', 'text' => $text];
    ?>
    <?= commonStylesheetTags('/common/assistant/chat.css') ?>
    <div class="organization-assistant" data-organization-assistant data-config="<?= $escape(json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
        <button type="button" class="generic-action-button generic-action-button--floating" data-assistant-toggle aria-controls="organization-assistant-panel" aria-expanded="false" aria-label="<?= $escape(commonAssistantT('open')) ?>" title="<?= $escape(commonAssistantT('open')) ?>">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-3 2V11.5a8.5 8.5 0 0 1 8.5-8.5H11"/><path d="m18 1 1.5 4.5L24 7l-4.5 1.5L18 13l-1.5-4.5L12 7l4.5-1.5Z"/><path d="M6 12h6M6 16h8"/></svg>
        </button>
        <section id="organization-assistant-panel" class="generic-soft-panel generic-soft-panel--flush generic-floating-panel organization-assistant__panel" role="dialog" aria-modal="false" aria-labelledby="organization-assistant-title" hidden>
            <div class="generic-resize-handle generic-resize-handle--left" data-assistant-resize role="separator" tabindex="0" aria-orientation="vertical" aria-controls="organization-assistant-panel" aria-label="<?= $escape(commonAssistantT('resize')) ?>" title="<?= $escape(commonAssistantT('resize')) ?>"></div>
            <header class="generic-drawer-header">
                <div class="generic-drawer-header__copy">
                    <div class="generic-heading-with-help">
                        <img class="organization-assistant__mascot" src="<?= $escape(commonAssetUrl('/common/assistant/assets/penguin.png')) ?>" alt="" width="48" height="48">
                        <div class="generic-drawer-header__copy">
                            <h2 id="organization-assistant-title" class="generic-card-title"><?= $escape(commonAssistantT('title')) ?></h2>
                            <div class="generic-heading-with-help">
                                <span class="generic-meta-text"><?= $escape($organizationName) ?></span>
                                <details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>
                                    <summary aria-label="<?= $escape(commonAssistantT('help')) ?>">?</summary>
                                    <div class="generic-context-help__content">
                                        <p><?= $escape(commonAssistantT('welcome')) ?></p>
                                        <p><?= $escape(commonAssistantT('privacy')) ?></p>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="generic-drawer-header__actions">
                    <button type="button" class="generic-action-button generic-action-button--icon-only" data-assistant-new title="<?= $escape(commonAssistantT('new')) ?>" aria-label="<?= $escape(commonAssistantT('new')) ?>"><img class="black-icon" src="<?= $escape(commonAssetUrl('/common/assistant/assets/new-chat.png')) ?>" alt="" width="20" height="20"></button>
                    <button type="button" class="generic-action-button generic-action-button--icon-only generic-action-button--close" data-assistant-close aria-label="<?= $escape(commonAssistantT('close')) ?>"></button>
                </div>
            </header>
            <div class="organization-assistant__conversation">
                <div data-assistant-welcome class="generic-form-stack">
                    <button type="button" class="generic-action-button" data-assistant-example><?= $escape(commonAssistantT('role')) ?></button>
                    <button type="button" class="generic-action-button" data-assistant-example><?= $escape(commonAssistantT('meeting')) ?></button>
                </div>
                <div data-assistant-messages role="log" aria-live="polite" aria-relevant="additions" class="generic-form-stack"></div>
                <p data-assistant-progress role="status" class="generic-meta-text" hidden></p>
            </div>
            <form data-assistant-form class="generic-control-action generic-control-action--nowrap organization-assistant__composer">
                <textarea id="organization-assistant-question" class="generic-form-control" rows="2" maxlength="4000" required placeholder="<?= $escape(commonAssistantT('question')) ?>" aria-label="<?= $escape(commonAssistantT('question')) ?>"></textarea>
                <button type="submit" class="generic-action-button generic-action-button--main generic-action-button--icon-only" data-assistant-send title="<?= $escape(commonAssistantT('send')) ?>" aria-label="<?= $escape(commonAssistantT('send')) ?>"><span class="organization-assistant__send-icon" aria-hidden="true"></span></button>
            </form>
        </section>
    </div>
    <script src="<?= $escape(commonAssetUrl('/common/assistant/chat.js')) ?>" defer></script>
    <?php
}
