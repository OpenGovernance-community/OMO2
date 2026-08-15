<?php
require_once __DIR__ . '/web_push.php';
require_once dirname(__DIR__) . '/shared/telegram.php';

if (!function_exists('notificationCenterEventCatalog')) {
    function notificationCenterEventCatalog()
    {
        return [
            'decision_proposal_owner' => 'Nouvelle proposition dans mes scrutins',
            'decision_proposal_participant' => 'Nouvelle proposition dans un scrutin auquel je participe',
            'decision_chat_proposal_owner' => 'Nouveau commentaire sur mes propositions',
            'decision_chat_participant' => 'Nouveau commentaire dans une discussion a laquelle je participe',
            'decision_consultation_started' => 'Passage de mes scrutins invites en consultation',
            'decision_evaluation_started' => 'Passage de mes scrutins invites en vote',
            'decision_consultation_ending' => 'Fin prochaine de la consultation',
            'decision_evaluation_ending' => 'Fin prochaine du vote',
        ];
    }
}

if (!function_exists('notificationCenterEventGroupCatalog')) {
    function notificationCenterEventGroupCatalog()
    {
        return [
            'decisions' => [
                'applicationHash' => 'decision',
                'eventKeys' => [
                    'decision_proposal_owner',
                    'decision_proposal_participant',
                    'decision_chat_proposal_owner',
                    'decision_chat_participant',
                    'decision_consultation_started',
                    'decision_evaluation_started',
                    'decision_consultation_ending',
                    'decision_evaluation_ending',
                ],
            ],
        ];
    }
}

if (!function_exists('notificationCenterGetActiveEventGroups')) {
    function notificationCenterGetActiveEventGroups($organizationId, $userId)
    {
        $organization = new \dbObject\Organization();
        if (!$organization->load((int)$organizationId)) {
            return [];
        }

        $eventCatalog = notificationCenterEventCatalog();
        $groups = [];
        foreach (notificationCenterEventGroupCatalog() as $groupKey => $group) {
            $applicationHash = trim((string)($group['applicationHash'] ?? ''));
            if ($applicationHash !== '' && !$organization->isApplicationEnabled($applicationHash, (int)$userId)) {
                continue;
            }
            $eventKeys = array_values(array_filter(
                $group['eventKeys'] ?? [],
                static function ($eventKey) use ($eventCatalog) {
                    return array_key_exists((string)$eventKey, $eventCatalog);
                }
            ));
            if ($eventKeys === []) {
                continue;
            }
            $groups[(string)$groupKey] = ['eventKeys' => $eventKeys];
        }

        return $groups;
    }
}

if (!function_exists('notificationCenterBuildDecisionUrl')) {
    function notificationCenterBuildDecisionUrl($organizationId, $decisionId)
    {
        return '/omo/o/' . (int)$organizationId . '#decision-d' . (int)$decisionId;
    }
}

if (!function_exists('notificationCenterSendPush')) {
    function notificationCenterSendPush($userId, array $payload)
    {
        $subscriptions = \dbObject\NotificationPushSubscription::findActiveForUserIds([(int)$userId]);
        foreach ($subscriptions as $subscription) {
            if (!($subscription instanceof \dbObject\NotificationPushSubscription)) {
                continue;
            }
            $result = webPushSendToSubscription($subscription, $payload);
            \dbObject\NotificationPushSubscription::recordDeliveryResult(
                (int)$subscription->getId(),
                !empty($result['status']),
                (string)($result['error'] ?? ''),
                !empty($result['disable'])
            );
        }
    }
}

if (!function_exists('notificationCenterSendTelegram')) {
    function notificationCenterSendTelegram(\dbObject\User $user, $title, $body, $url)
    {
        $telegramId = trim((string)$user->get('telegramID'));
        if ($telegramId === '' || !defined('TOKEN') || trim((string)TOKEN) === '') {
            return false;
        }
        $message = trim((string)$title);
        if (trim((string)$body) !== '') {
            $message .= "\n\n" . trim((string)$body);
        }
        if (trim((string)$url) !== '' && function_exists('appBuildAbsoluteUrl')) {
            $message .= "\n\n" . appBuildAbsoluteUrl((string)$url);
        }
        return sendMessage($telegramId, $message);
    }
}

if (!function_exists('notificationCenterSendEmail')) {
    function notificationCenterSendEmail(\dbObject\User $user, \dbObject\Organization $organization, $title, $body, $url)
    {
        $organizationId = (int)$organization->getId();
        $email = trim((string)$user->getScopedEmail($organizationId));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !function_exists('myHTMLMail')) {
            return false;
        }
        $fromAddress = function_exists('envValue') ? trim((string)envValue('MAIL_USER', '')) : '';
        if ($fromAddress === '' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            $fromAddress = 'noreply@' . preg_replace('/:\\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        }
        $safeTitle = htmlspecialchars(trim((string)$title), ENT_QUOTES, 'UTF-8');
        $safeBody = nl2br(htmlspecialchars(trim((string)$body), ENT_QUOTES, 'UTF-8'));
        $link = '';
        if (trim((string)$url) !== '' && function_exists('appBuildAbsoluteUrl')) {
            $absoluteUrl = appBuildAbsoluteUrl((string)$url);
            $link = '<p><a href="' . htmlspecialchars($absoluteUrl, ENT_QUOTES, 'UTF-8') . '">Ouvrir dans OMO</a></p>';
        }
        $html = '<h2>' . $safeTitle . '</h2><p>' . $safeBody . '</p>' . $link;
        return myHTMLMail([$fromAddress, trim((string)$organization->get('name')) ?: 'OMO'], $email, $safeTitle, $html);
    }
}

if (!function_exists('notificationCenterCreateForUsers')) {
    function notificationCenterCreateForUsers($organizationId, $eventKey, array $userIds, $sourceKey, $title, $body, $url, $dedupeKey = '', $excludedUserId = 0)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0 || !array_key_exists((string)$eventKey, notificationCenterEventCatalog())) {
            return;
        }
        $organization = new \dbObject\Organization();
        if (!$organization->load($organizationId)) {
            return;
        }
        $excludedUserId = (int)$excludedUserId;
        foreach (array_values(array_unique(array_filter(array_map('intval', $userIds)))) as $userId) {
            if ($excludedUserId > 0 && $userId === $excludedUserId) {
                continue;
            }
            $notification = \dbObject\Notification::createForUser($userId, $organizationId, $eventKey, $sourceKey, $title, $body, $url, $dedupeKey);
            if (!($notification instanceof \dbObject\Notification)) {
                continue;
            }
            $deliveryUrl = $notification->getOpenUrl();
            $channels = \dbObject\NotificationPreference::getChannelsFor($userId, $organizationId, $eventKey);
            $user = new \dbObject\User();
            if (!$user->load($userId)) {
                continue;
            }
            if (!empty($channels['push'])) {
                notificationCenterSendPush($userId, ['title' => $title, 'body' => $body, 'url' => $deliveryUrl, 'tag' => $sourceKey]);
            }
            if (!empty($channels['telegram'])) {
                notificationCenterSendTelegram($user, $title, $body, $deliveryUrl);
            }
            if (!empty($channels['email'])) {
                notificationCenterSendEmail($user, $organization, $title, $body, $deliveryUrl);
            }
        }
    }
}

if (!function_exists('notificationCenterDispatchDecisionProposal')) {
    function notificationCenterDispatchDecisionProposal(\dbObject\DecisionProposal $proposal)
    {
        $decision = $proposal->getDecisionProcess();
        if (!($decision instanceof \dbObject\DecisionProcess)) {
            return;
        }
        $organizationId = (int)$decision->get('IDorganization');
        $decisionId = (int)$decision->getId();
        $proposalId = (int)$proposal->getId();
        $title = 'Nouvelle proposition';
        $body = 'La proposition "' . mb_substr(trim((string)$proposal->get('title')), 0, 150, 'UTF-8') . '" vient d etre ajoutee.';
        $url = notificationCenterBuildDecisionUrl($organizationId, $decisionId);
        $dedupeKey = 'PROPOSAL_' . $organizationId . '_' . $proposalId;
        $actorUserId = (int)$proposal->getAuthorUserId();
        $ownerId = (int)$decision->get('IDuser');
        notificationCenterCreateForUsers($organizationId, 'decision_proposal_owner', $ownerId > 0 ? [$ownerId] : [], 'decision-proposal-owner-' . $proposalId, $title, $body, $url, $dedupeKey, $actorUserId);
        notificationCenterCreateForUsers(
            $organizationId,
            'decision_proposal_participant',
            \dbObject\DecisionParticipant::getActiveUserIdsForDecision($decisionId),
            'decision-proposal-participant-' . $proposalId,
            $title,
            $body,
            $url,
            $dedupeKey,
            $actorUserId
        );
    }
}

if (!function_exists('notificationCenterDispatchDecisionChatMessage')) {
    function notificationCenterDispatchDecisionChatMessage(\dbObject\ChatMessage $message)
    {
        $thread = new \dbObject\ChatThread();
        if (!$thread->load((int)$message->get('IDchat_thread')) || (string)$thread->get('subject_type') !== \dbObject\ChatThread::SUBJECT_DECISION_PROPOSAL) {
            return;
        }
        $proposal = new \dbObject\DecisionProposal();
        if (!$proposal->load((int)$thread->get('subject_id'))) {
            return;
        }
        $decision = $proposal->getDecisionProcess();
        if (!($decision instanceof \dbObject\DecisionProcess)) {
            return;
        }
        $organizationId = (int)$decision->get('IDorganization');
        $decisionId = (int)$decision->getId();
        $messageId = (int)$message->getId();
        $decisionTitle = mb_substr(trim((string)$decision->get('title')), 0, 120, 'UTF-8');
        $proposalTitle = mb_substr(trim((string)$proposal->get('title')), 0, 120, 'UTF-8');
        $authorName = $message->isAnonymous()
            ? 'Un participant'
            : mb_substr(trim((string)$message->get('author_name')), 0, 80, 'UTF-8');
        if ($authorName === '') {
            $authorName = 'Un participant';
        }
        $title = 'Nouveau commentaire - ' . $decisionTitle;
        $body = $authorName . ' a commente la proposition "' . $proposalTitle . '" dans le scrutin "' . $decisionTitle . '".';
        $url = notificationCenterBuildDecisionUrl($organizationId, $decisionId);
        $dedupeKey = 'CHAT_' . $organizationId . '_' . (int)$proposal->getId();
        $actorUserId = (int)$message->get('IDuser');
        $proposalAuthorUserId = (int)$proposal->getAuthorUserId();
        notificationCenterCreateForUsers($organizationId, 'decision_chat_proposal_owner', $proposalAuthorUserId > 0 ? [$proposalAuthorUserId] : [], 'decision-chat-owner-' . $messageId, $title, $body, $url, $dedupeKey, $actorUserId);
        notificationCenterCreateForUsers(
            $organizationId,
            'decision_chat_participant',
            \dbObject\ChatMessage::getParticipantUserIdsForThread((int)$thread->getId()),
            'decision-chat-participant-' . $messageId,
            $title,
            $body,
            $url,
            $dedupeKey,
            $actorUserId
        );
    }
}

if (!function_exists('notificationCenterDispatchDecisionPhase')) {
    function notificationCenterDispatchDecisionPhase(\dbObject\DecisionProcess $decision, $status)
    {
        $status = \dbObject\DecisionProcess::normalizeStatus($status);
        $eventKey = $status === \dbObject\DecisionProcess::STATUS_CONSULTATION
            ? 'decision_consultation_started'
            : ($status === \dbObject\DecisionProcess::STATUS_EVALUATION ? 'decision_evaluation_started' : '');
        if ($eventKey === '') {
            return 0;
        }

        $organizationId = (int)$decision->get('IDorganization');
        $decisionId = (int)$decision->getId();
        $decisionTitle = mb_substr(trim((string)$decision->get('title')), 0, 140, 'UTF-8');
        $phaseLabel = $status === \dbObject\DecisionProcess::STATUS_CONSULTATION ? 'consultation' : 'vote';
        $titlePrefix = $phaseLabel === 'consultation' ? 'Consultation ouverte' : 'Vote ouvert';
        notificationCenterCreateForUsers(
            $organizationId,
            $eventKey,
            \dbObject\DecisionParticipant::getInvitedUserIdsForDecision($decisionId),
            'decision-phase-' . $phaseLabel . '-' . $decisionId,
            $titlePrefix . ' - ' . $decisionTitle,
            'Le scrutin "' . $decisionTitle . '" est maintenant en phase de ' . $phaseLabel . '.',
            notificationCenterBuildDecisionUrl($organizationId, $decisionId)
        );
        return 1;
    }
}

if (!function_exists('notificationCenterDispatchDecisionDeadlineReminder')) {
    function notificationCenterDispatchDecisionDeadlineReminder(\dbObject\DecisionProcess $decision, $phase, \DateTimeInterface $deadline, $days)
    {
        $phase = $phase === 'consultation' ? 'consultation' : 'vote';
        $eventKey = $phase === 'consultation' ? 'decision_consultation_ending' : 'decision_evaluation_ending';
        $organizationId = (int)$decision->get('IDorganization');
        $decisionId = (int)$decision->getId();
        $decisionTitle = mb_substr(trim((string)$decision->get('title')), 0, 140, 'UTF-8');
        $sourceKey = 'decision-' . $phase . '-ending-' . $decisionId . '-' . $deadline->format('Ymd') . '-' . (int)$days . 'd';
        $phaseEndingLabel = $phase === 'consultation' ? 'de la consultation' : 'du vote';
        $phaseBodyLabel = $phase === 'consultation' ? 'La consultation' : 'Le vote';
        $title = 'Fin ' . $phaseEndingLabel . ' dans ' . (int)$days . ' jour' . ((int)$days > 1 ? 's' : '') . ' - ' . $decisionTitle;
        $body = $phaseBodyLabel . ' du scrutin "' . $decisionTitle . '" se termine le ' . $deadline->format('d.m.Y H:i') . '.';
        $recipientIds = \dbObject\DecisionParticipant::getInvitedUserIdsForDecision($decisionId);
        foreach ($recipientIds as $userId) {
            $settings = \dbObject\NotificationPreference::getChannelsFor($userId, $organizationId, $eventKey);
            if (!in_array((int)$days, $settings['days'] ?? [], true)) {
                continue;
            }
            notificationCenterCreateForUsers(
                $organizationId,
                $eventKey,
                [(int)$userId],
                $sourceKey,
                $title,
                $body,
                notificationCenterBuildDecisionUrl($organizationId, $decisionId)
            );
        }
        return 1;
    }
}

if (!function_exists('notificationCenterProcessDecisionLifecycle')) {
    function notificationCenterProcessDecisionLifecycle($limit = 200, $referenceDateTime = null)
    {
        $referenceDateTime = \dbObject\DecisionProcess::normalizeDateTimeValue($referenceDateTime);
        if (!($referenceDateTime instanceof \DateTimeInterface)) {
            $referenceDateTime = new \DateTimeImmutable('now');
        }
        $processed = 0;
        foreach (\dbObject\DecisionProcess::getLifecycleNotificationCandidates($limit) as $decision) {
            if (!($decision instanceof \dbObject\DecisionProcess)) {
                continue;
            }
            $statusChanged = $decision->syncLifecycleStatus($referenceDateTime);
            $status = \dbObject\DecisionProcess::normalizeStatus($decision->get('status'));
            if ($statusChanged && ($status === \dbObject\DecisionProcess::STATUS_CONSULTATION || $status === \dbObject\DecisionProcess::STATUS_EVALUATION)) {
                notificationCenterDispatchDecisionPhase($decision, $status);
            }

            $deadlineField = $status === \dbObject\DecisionProcess::STATUS_CONSULTATION
                ? 'consultation_end_at'
                : ($status === \dbObject\DecisionProcess::STATUS_EVALUATION ? 'evaluation_end_at' : '');
            if ($deadlineField === '') {
                $processed++;
                continue;
            }
            $deadline = \dbObject\DecisionProcess::normalizeDateTimeValue($decision->get($deadlineField));
            if (!($deadline instanceof \DateTimeInterface) || $deadline < $referenceDateTime) {
                $processed++;
                continue;
            }
            $referenceDay = new \DateTimeImmutable($referenceDateTime->format('Y-m-d'));
            $deadlineDay = new \DateTimeImmutable($deadline->format('Y-m-d'));
            $days = (int)$referenceDay->diff($deadlineDay)->format('%r%a');
            if (in_array($days, [1, 2, 3, 5], true)) {
                notificationCenterDispatchDecisionDeadlineReminder(
                    $decision,
                    $status === \dbObject\DecisionProcess::STATUS_CONSULTATION ? 'consultation' : 'vote',
                    $deadline,
                    $days
                );
            }
            $processed++;
        }
        return $processed;
    }
}

if (!function_exists('notificationCenterMaybeProcessDecisionLifecycle')) {
    function notificationCenterMaybeProcessDecisionLifecycle($limit = 200, $force = false)
    {
        $directory = dirname(__DIR__) . '/tmp';
        $path = $directory . '/notification-decision-lifecycle-last-run.txt';
        if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
            return 0;
        }
        $handle = @fopen($path, 'c+');
        if ($handle === false || !@flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            return 0;
        }
        try {
            $lastRun = (int)trim((string)stream_get_contents($handle));
            if (!$force && $lastRun > 0 && (time() - $lastRun) < 15 * 60) {
                return 0;
            }
            $processed = notificationCenterProcessDecisionLifecycle($limit);
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string)time());
            return $processed;
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
