<?php

/** Shared by the page and endpoint so restored chats keep their original access context. */
function commonAssistantSessionIdentity(array $grant): string
{
    return hash('sha256', json_encode([$grant['IDuser'], $grant['IDorganization'], $_SESSION['isAdmin'] ?? null,
        $_SESSION['isAdminByOrganization'] ?? null, $_SESSION['isSiteAdminModeEnabled'] ?? null,
        $_SESSION['isAdminModeEnabled'] ?? null, $_SESSION['extendedAuthoritiesByOrganization'] ?? null]));
}

function commonAssistantSessionChat(string $chatId, string $identity): array
{
    $chat = $_SESSION['common_assistant_chats'][$chatId] ?? ['history' => [], 'messages' => [], 'identity' => $identity];
    if (($chat['identity'] ?? '') !== $identity) throw new DomainException('context');
    return $chat;
}

/** Only display data leaves the session; prompts, grants and tool payloads remain server-side. */
function commonAssistantChatSnapshot(array $chat): array
{
    $pending = (string)($chat['pending_question'] ?? '');
    return ['messages' => $chat['messages'] ?? [], 'busy' => ($chat['busy_until'] ?? 0) > time(),
        'pendingQuestion' => $pending, 'failed' => $pending !== '' && ($chat['busy_until'] ?? 0) <= time()];
}
