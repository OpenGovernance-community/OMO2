<?php
require_once dirname(__DIR__) . '/request_security.php';

function commonChatReactionLabels(): array
{
    static $labels;
    if ($labels !== null) return $labels;
    $sourceLang = [
        'up' => ['text' => 'Pouce leve', 'context' => 'Chat reaction expressing support'],
        'down' => ['text' => 'Pouce baisse', 'context' => 'Chat reaction expressing disagreement'],
        'clap' => ['text' => 'Applaudissements', 'context' => 'Chat reaction expressing applause'],
        'choose' => ['text' => 'Réagir au message', 'context' => 'Open chat reaction picker'],
        'remove' => ['text' => 'Retirer ma réaction', 'context' => 'Selected reaction can be removed'],
        'ownMessage' => ['text' => 'Vous ne pouvez pas réagir à votre propre message.', 'context' => 'Own chat messages show received reactions only'],
        'error' => ['text' => 'La réaction ne peut pas être enregistrée. Rechargez la discussion et réessayez.', 'context' => 'Chat reaction save error'],
    ];
    $bundle = omoLoadTranslationBundle('common_chat_reactions', $sourceLang);
    $labels = [];
    foreach ($sourceLang as $key => $definition) $labels[$key] = t($key, [], $bundle, $sourceLang);
    return $labels;
}

/** Called only after the owning endpoint has checked its normal discussion permissions. */
function commonChatHandleReaction(?\dbObject\ChatThread $thread, bool $canReact, string $actorKey): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || ($_POST['action'] ?? '') !== 'reaction') return;
    $labels = commonChatReactionLabels();
    try {
        if (!$canReact || !$thread || !commonBrowserMutationIsAllowed($_SERVER, $_POST, $_SESSION)) {
            http_response_code(403);
            throw new \InvalidArgumentException('Reaction forbidden.');
        }
        $state = \dbObject\ChatMessageReaction::setForMessage($thread, (int)($_POST['message_id'] ?? 0),
            $actorKey, is_string($_POST['reaction'] ?? null) ? $_POST['reaction'] : 'invalid');
        echo json_encode(['status' => true, 'reactions' => [$state]], JSON_UNESCAPED_UNICODE);
    } catch (\DomainException $error) {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => $labels['ownMessage']]);
    } catch (\InvalidArgumentException $error) {
        if (http_response_code() !== 403) http_response_code(422);
        echo json_encode(['status' => false, 'message' => $labels['error']]);
    } catch (\Throwable $error) {
        error_log('chat_reaction_failed: ' . $error->getMessage());
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => $labels['error']]);
    }
    exit;
}

function commonChatReactionPayload(?\dbObject\ChatThread $thread, array $messages, array $input, string $actorKey, bool $canReact): array
{
    $ids = array_column($messages, 'id');
    if (is_string($input['reaction_ids'] ?? null)) {
        $ids = array_merge(explode(',', substr($input['reaction_ids'], 0, 6000)), $ids);
    }
    return [
        'canReact' => $canReact && $actorKey !== '',
        'reactionCsrf' => commonCsrfToken(),
        // Also delivered here for participants using a public invitation, without a login session.
        'reactionLabels' => commonChatReactionLabels(),
        'reactions' => $thread ? \dbObject\ChatMessageReaction::forMessages($thread, $ids, $actorKey) : [],
    ];
}
