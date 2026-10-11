<?php
require_once __DIR__ . '/web_push.php';
require_once dirname(__DIR__) . '/shared/telegram.php';

if (!function_exists('notificationCenterT')) {
    function notificationCenterT($key, array $variables = [])
    {
        static $bundle = null;
        static $sourceLang = [
            'notification.member' => ['text' => 'Un membre', 'context' => 'Fallback when a notification actor cannot be identified.'],
            'notification.participant' => ['text' => 'Un participant', 'context' => 'Anonymous or unidentified decision participant.'],
            'notification.event.invitation_title' => ['text' => 'Nouvel événement — {title}', 'context' => 'Event invitation notification title.'],
            'notification.event.invitation' => ['text' => '{actor} vous invite à l’événement « {title} ».', 'context' => 'Event invitation with the person who sent it.'],
            'notification.event.recurring' => ['text' => ' Cette réunion fait partie d’une série récurrente.', 'context' => 'Recurring series information appended to meeting invitations.'],
            'notification.event.occurrence_title' => ['text' => 'Nouvelle occurrence — {title}', 'context' => 'Invitation to a new recurring meeting occurrence.'],
            'notification.event.occurrence_preference' => ['text' => 'Nouvelle occurrence d’une réunion récurrente', 'context' => 'Fallback notification preference label for newly generated recurring meeting occurrences.'],
            'notification.event.organizer' => ['text' => ' Événement organisé par {organizer}.', 'context' => 'Event creator when someone else sends the invitation.'],
            'notification.event.start' => ['text' => ' Il est prévu le {date}.', 'context' => 'Start date appended to an event invitation.'],
            'notification.event.schedule_title' => ['text' => 'Modification de l’horaire — {title}', 'context' => 'Event schedule change notification title.'],
            'notification.event.location_title' => ['text' => 'Modification du lieu — {title}', 'context' => 'Event location change notification title.'],
            'notification.event.schedule' => ['text' => '{actor} a modifié l’horaire de l’événement « {title} ».', 'context' => 'Event schedule change with the actual editor.'],
            'notification.event.location' => ['text' => '{actor} a modifié le lieu de l’événement « {title} ».', 'context' => 'Event location change with the actual editor.'],
            'notification.event.new_start' => ['text' => ' Nouveau début : {date}.', 'context' => 'Updated event start date.'],
            'notification.event.new_end' => ['text' => ' Nouvelle fin : {date}.', 'context' => 'Updated event end date.'],
            'notification.event.new_address' => ['text' => ' Nouveau lieu : {address}.', 'context' => 'Updated event physical location.'],
            'notification.event.new_video' => ['text' => ' Visioconférence : {url}.', 'context' => 'Updated event video meeting link.'],
            'notification.event.updated' => ['text' => ' Mise à jour le {date}.', 'context' => 'Date of an event change.'],
            'notification.proposal.title' => ['text' => 'Nouvelle proposition - {title}', 'context' => 'Decision proposal notification title.'],
            'notification.proposal.added' => ['text' => '{actor} a ajoute la proposition "{proposal}" au scrutin "{title}".', 'context' => 'New decision proposal with its author.'],
            'notification.proposal.added_untitled' => ['text' => '{actor} a ajoute une nouvelle proposition au scrutin "{title}".', 'context' => 'New untitled decision proposal with its author.'],
            'notification.decision.comment' => ['text' => '{actor} a commente la proposition "{proposal}" dans le scrutin "{title}".', 'context' => 'Decision comment notification with its author.'],
            'notification.project.message' => ['text' => '{actor} a ecrit dans la discussion du projet "{title}".', 'context' => 'Project discussion notification with its author.'],
            'notification.project.status_title' => ['text' => 'Statut modifie - {title}', 'context' => 'Project status change notification title.'],
            'notification.project.status' => ['text' => '{actor} a change le statut du projet "{title}" de "{previous}" a "{current}".', 'context' => 'Manual project status change with the actual editor.'],
            'notification.project.status_auto' => ['text' => 'Le statut du projet "{title}" est passe automatiquement de "{previous}" a "{current}".', 'context' => 'Automatic project status change without a human actor.'],
        ];
        if ($bundle === null) {
            $bundle = function_exists('omoLoadTranslationBundle')
                ? omoLoadTranslationBundle('common_notification_center', $sourceLang)
                : [];
        }
        if (function_exists('t')) {
            return t($key, $variables, $bundle, $sourceLang);
        }
        $text = $sourceLang[$key]['text'] ?? $key;
        foreach ($variables as $name => $value) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }
        return $text;
    }
}

if (!function_exists('notificationCenterActorName')) {
    function notificationCenterActorName($userId, $organizationId, $fallback = '')
    {
        $user = new \dbObject\User();
        if ((int)$userId > 0 && $user->load((int)$userId)) {
            $name = mb_substr(trim((string)$user->getScopedDisplayName((int)$organizationId)), 0, 80, 'UTF-8');
            if ($name !== '') {
                return $name;
            }
        }
        return $fallback !== '' ? $fallback : notificationCenterT('notification.member');
    }
}

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
            'decision_consultation_finished' => 'Fin de la consultation de mes scrutins',
            'decision_evaluation_finished' => 'Fin du vote de mes scrutins',
            'calendar_event_invited' => 'Invitation a un nouvel evenement',
            'calendar_recurring_occurrence_created' => notificationCenterT('notification.event.occurrence_preference'),
            'calendar_event_location_changed' => 'Modification du lieu d un evenement',
            'calendar_event_schedule_changed' => 'Modification de l horaire d un evenement',
            'calendar_event_starting' => 'Debut prochain d un evenement',
            'project_proposal_refused' => 'Refus de mes propositions de projet',
            'project_status_changed' => 'Changement de statut des projets suivis',
            'project_chat_owner' => 'Nouveau commentaire sur mes projets ou propositions',
            'project_chat_participant' => 'Nouveau commentaire dans une discussion de projet a laquelle je participe',
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
                    'decision_consultation_finished',
                    'decision_evaluation_finished',
                ],
            ],
            'calendar' => [
                'applicationHash' => 'calendar',
                'eventKeys' => [
                    'calendar_event_invited',
                    'calendar_recurring_occurrence_created',
                    'calendar_event_location_changed',
                    'calendar_event_schedule_changed',
                    'calendar_event_starting',
                ],
            ],
            'projects' => [
                'applicationHash' => 'projects',
                'eventKeys' => [
                    'project_proposal_refused',
                    'project_status_changed',
                    'project_chat_owner',
                    'project_chat_participant',
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

if (!function_exists('notificationCenterBuildEventUrl')) {
    function notificationCenterBuildEventUrl($organizationId, $eventId)
    {
        return '/omo/o/' . (int)$organizationId . '#calendar-e' . (int)$eventId;
    }
}

if (!function_exists('notificationCenterBuildProjectUrl')) {
    function notificationCenterBuildProjectUrl($organizationId, $projectId)
    {
        return '/omo/o/' . (int)$organizationId . '#projects-d' . (int)$projectId;
    }
}

if (!function_exists('notificationCenterBuildDecisionProposalLabel')) {
    function notificationCenterBuildDecisionProposalLabel(\dbObject\DecisionProposal $proposal, $descriptionLength = 30)
    {
        $title = trim((string)$proposal->get('title'));
        if ($title !== '') {
            return mb_substr($title, 0, 150, 'UTF-8');
        }

        $description = html_entity_decode(
            strip_tags((string)$proposal->get('description')),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $description = preg_replace('/\s+/u', ' ', trim($description));
        if (!is_string($description) || $description === '') {
            return '';
        }

        $descriptionLength = max(1, (int)$descriptionLength);
        $label = mb_substr($description, 0, $descriptionLength, 'UTF-8');
        if (mb_strlen($description, 'UTF-8') > $descriptionLength) {
            $label = rtrim($label) . '...';
        }

        return $label;
    }
}

if (!function_exists('notificationCenterFormatEventDateTime')) {
    function notificationCenterFormatEventDateTime($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d.m.Y H:i');
        }
        return '';
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
            $channels = \dbObject\NotificationPreference::getChannelsFor($userId, $organizationId, $eventKey);
            $hasExternalChannel = !empty($channels['push']) || !empty($channels['telegram']) || !empty($channels['email']);
            if (empty($channels['in_app']) && !$hasExternalChannel) {
                continue;
            }
            $notification = \dbObject\Notification::createForUser($userId, $organizationId, $eventKey, $sourceKey, $title, $body, $url, $dedupeKey);
            if (!($notification instanceof \dbObject\Notification)) {
                continue;
            }
            $deliveryUrl = $notification->getOpenUrl();
            if (empty($channels['in_app'])) {
                \dbObject\Notification::markReadForUser($userId, $organizationId, (int)$notification->getId());
            }
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

if (!function_exists('notificationCenterDispatchEventInvitation')) {
    function notificationCenterDispatchEventInvitation(\dbObject\Event $event, $actorUserId = 0)
    {
        $organizationId = (int)$event->get('IDorganization');
        $eventId = (int)$event->getId();
        if ($organizationId <= 0 || $eventId <= 0) {
            return 0;
        }
        $eventTitle = mb_substr(trim((string)$event->get('title')), 0, 140, 'UTF-8');
        $startAt = notificationCenterFormatEventDateTime($event->get('start_at'));
        $inviterId = (int)$actorUserId > 0 ? (int)$actorUserId : (int)$event->get('IDuser');
        $body = notificationCenterT('notification.event.invitation', [
            'actor' => notificationCenterActorName($inviterId, $organizationId),
            'title' => $eventTitle,
        ]);
        if ($inviterId !== (int)$event->get('IDuser')) {
            $organizer = trim((string)$event->getCreatedByDisplayName());
            if ($organizer !== '') {
                $body .= notificationCenterT('notification.event.organizer', ['organizer' => $organizer]);
            }
        }
        if ($startAt !== '') {
            $body .= notificationCenterT('notification.event.start', ['date' => $startAt]);
        }
        $recurring = (int)$event->get('IDeventrecurrence') > 0;
        $occurrence = $recurring && (int)$event->get('recurrence_position') > 0;
        if ($recurring) { $body .= notificationCenterT('notification.event.recurring'); }
        notificationCenterCreateForUsers(
            $organizationId,
            $occurrence ? 'calendar_recurring_occurrence_created' : 'calendar_event_invited',
            $event->getNotificationRecipientUserIds(),
            'calendar-event-invited-' . $eventId,
            notificationCenterT($occurrence ? 'notification.event.occurrence_title' : 'notification.event.invitation_title', ['title' => $eventTitle]),
            $body,
            notificationCenterBuildEventUrl($organizationId, $eventId),
            '',
            $occurrence ? 0 : (int)$actorUserId
        );
        return 1;
    }
}

if (!function_exists('notificationCenterProcessRecurringInvitations')) {
    function notificationCenterProcessRecurringInvitations(int $limit = 200, ?int $seriesId = null): int
    {
        // No delivery for uncommitted meetings. Pending work survives a failed request/worker.
        if (\dbObject\DbObject::getPdo()->inTransaction() || !\dbObject\Notification::isStorageAvailable()) { return 0; }
        $processed = 0;
        foreach (\dbObject\EventRecurrence::getPendingInvitationEvents($limit, $seriesId) as $event) {
            try {
                if ($event->get('start_at') > new \DateTimeImmutable()) {
                    notificationCenterDispatchEventInvitation($event);
                }
                \dbObject\EventRecurrence::finishPendingInvitation($event);
                $processed++;
            } catch (\Throwable $exception) {
                error_log('OMO recurring invitation ' . (int)$event->getId() . ' failed: ' . $exception->getMessage());
            }
        }
        return $processed;
    }
}

if (!function_exists('notificationCenterDispatchEventChange')) {
    function notificationCenterDispatchEventChange(\dbObject\Event $event, $changeType, $actorUserId = 0)
    {
        $changeType = $changeType === 'schedule' ? 'schedule' : 'location';
        $organizationId = (int)$event->get('IDorganization');
        $eventId = (int)$event->getId();
        if ($organizationId <= 0 || $eventId <= 0) {
            return 0;
        }
        $eventKey = $changeType === 'schedule' ? 'calendar_event_schedule_changed' : 'calendar_event_location_changed';
        $eventTitle = mb_substr(trim((string)$event->get('title')), 0, 140, 'UTF-8');
        $updatedAt = notificationCenterFormatEventDateTime($event->get('updated_at'));
        $sourceSuffix = $event->get('updated_at') instanceof \DateTimeInterface
            ? $event->get('updated_at')->format('YmdHis')
            : sha1($eventTitle . '|' . $changeType . '|' . microtime(true));
        $body = notificationCenterT('notification.event.' . $changeType, [
            'actor' => notificationCenterActorName($actorUserId, $organizationId),
            'title' => $eventTitle,
        ]);
        if ($changeType === 'schedule') {
            $startAt = notificationCenterFormatEventDateTime($event->get('start_at'));
            if ($startAt !== '') {
                $body .= notificationCenterT('notification.event.new_start', ['date' => $startAt]);
            }
            $endAt = notificationCenterFormatEventDateTime($event->get('end_at'));
            if ($endAt !== '') {
                $body .= notificationCenterT('notification.event.new_end', ['date' => $endAt]);
            }
        } else {
            $address = trim((string)$event->getResolvedLocationAddress());
            $videoUrl = trim((string)$event->getResolvedVideoMeetingUrl());
            if ($address !== '') {
                $body .= notificationCenterT('notification.event.new_address', ['address' => $address]);
            }
            if ($videoUrl !== '') {
                $body .= notificationCenterT('notification.event.new_video', ['url' => $videoUrl]);
            }
        }
        if ($updatedAt !== '') {
            $body .= notificationCenterT('notification.event.updated', ['date' => $updatedAt]);
        }
        notificationCenterCreateForUsers(
            $organizationId,
            $eventKey,
            $event->getNotificationRecipientUserIds(),
            'calendar-event-' . $changeType . '-' . $eventId . '-' . $sourceSuffix,
            notificationCenterT('notification.event.' . $changeType . '_title', ['title' => $eventTitle]),
            $body,
            notificationCenterBuildEventUrl($organizationId, $eventId),
            '',
            (int)$actorUserId
        );
        return 1;
    }
}

if (!function_exists('notificationCenterLeadTimeSeconds')) {
    function notificationCenterLeadTimeSeconds($leadTime)
    {
        $leadTime = trim((string)$leadTime);
        if (!preg_match('/^([1-9][0-9]*)([hd])$/', $leadTime, $matches)) {
            return 0;
        }
        $quantity = (int)$matches[1];
        if ($matches[2] === 'h') {
            return $quantity * 60 * 60;
        }
        return $quantity * 24 * 60 * 60;
    }
}

if (!function_exists('notificationCenterLeadTimeLabel')) {
    function notificationCenterLeadTimeLabel($leadTime)
    {
        $seconds = notificationCenterLeadTimeSeconds($leadTime);
        if ($seconds <= 0) {
            return '';
        }
        $quantity = $seconds % (24 * 60 * 60) === 0 ? (int)($seconds / (24 * 60 * 60)) : (int)($seconds / (60 * 60));
        $unit = $seconds % (24 * 60 * 60) === 0 ? 'jour' : 'heure';
        return $quantity . ' ' . $unit . ($quantity > 1 ? 's' : '');
    }
}

if (!function_exists('notificationCenterProcessEventLifecycle')) {
    function notificationCenterProcessEventLifecycle($limit = 200, $referenceDateTime = null)
    {
        $referenceDateTime = $referenceDateTime instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($referenceDateTime)
            : new \DateTimeImmutable('now');
        $processed = 0;
        foreach (\dbObject\Event::getNotificationLifecycleCandidates($limit, $referenceDateTime) as $event) {
            $startAt = $event->get('start_at');
            if (!($startAt instanceof \DateTimeInterface)) {
                continue;
            }
            $remainingSeconds = $startAt->getTimestamp() - $referenceDateTime->getTimestamp();
            if ($remainingSeconds <= 0) {
                continue;
            }
            foreach ($event->getNotificationRecipientUserIds() as $userId) {
                $settings = \dbObject\NotificationPreference::getChannelsFor(
                    $userId,
                    (int)$event->get('IDorganization'),
                    'calendar_event_starting'
                );
                $leadTime = trim((string)($settings['lead_time'] ?? ''));
                $leadSeconds = notificationCenterLeadTimeSeconds($leadTime);
                if ($leadSeconds <= 0 || $remainingSeconds > $leadSeconds) {
                    continue;
                }
                $eventTitle = mb_substr(trim((string)$event->get('title')), 0, 140, 'UTF-8');
                $leadLabel = notificationCenterLeadTimeLabel($leadTime);
                notificationCenterCreateForUsers(
                    (int)$event->get('IDorganization'),
                    'calendar_event_starting',
                    [$userId],
                    'calendar-event-starting-' . (int)$event->getId() . '-' . $startAt->format('YmdHi') . '-' . $leadTime,
                    'Debut dans ' . $leadLabel . ' - ' . $eventTitle,
                    'L evenement "' . $eventTitle . '" commence le ' . notificationCenterFormatEventDateTime($startAt) . '.',
                    notificationCenterBuildEventUrl((int)$event->get('IDorganization'), (int)$event->getId())
                );
            }
            $processed++;
        }
        return $processed;
    }
}

if (!function_exists('notificationCenterMaybeProcessEventLifecycle')) {
    function notificationCenterMaybeProcessEventLifecycle($limit = 200, $force = false)
    {
        $directory = dirname(__DIR__) . '/tmp';
        $path = $directory . '/notification-event-lifecycle-last-run.txt';
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
            $processed = notificationCenterProcessEventLifecycle($limit);
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
        $decisionTitle = mb_substr(trim((string)$decision->get('title')), 0, 140, 'UTF-8');
        if ($decisionTitle === '') {
            $decisionTitle = 'ce scrutin';
        }
        $proposalLabel = notificationCenterBuildDecisionProposalLabel($proposal);
        $actorUserId = (int)$proposal->getAuthorUserId();
        $authorName = notificationCenterT('notification.participant');
        if (!$proposal->isAnonymous()) {
            $participant = $proposal->getAuthorParticipant();
            $authorName = $participant instanceof \dbObject\DecisionParticipant
                ? mb_substr(trim((string)$participant->getIdentityLabel($organizationId)), 0, 80, 'UTF-8')
                : notificationCenterActorName($actorUserId, $organizationId, $authorName);
        }
        $title = notificationCenterT('notification.proposal.title', ['title' => $decisionTitle]);
        $body = notificationCenterT($proposalLabel !== '' ? 'notification.proposal.added' : 'notification.proposal.added_untitled', [
            'actor' => $authorName !== '' ? $authorName : notificationCenterT('notification.participant'),
            'proposal' => $proposalLabel,
            'title' => $decisionTitle,
        ]);
        $url = notificationCenterBuildDecisionUrl($organizationId, $decisionId);
        $dedupeKey = 'PROPOSAL_' . $organizationId . '_' . $proposalId;
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
            ? notificationCenterT('notification.participant')
            : mb_substr(trim((string)$message->get('author_name')), 0, 80, 'UTF-8');
        if ($authorName === '' && !$message->isAnonymous()) {
            $authorName = notificationCenterActorName($message->get('IDuser'), $organizationId, notificationCenterT('notification.participant'));
        }
        $title = 'Nouveau commentaire - ' . $decisionTitle;
        $body = notificationCenterT('notification.decision.comment', ['actor' => $authorName, 'proposal' => $proposalTitle, 'title' => $decisionTitle]);
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

if (!function_exists('notificationCenterDispatchProjectChatMessage')) {
    function notificationCenterDispatchProjectChatMessage(\dbObject\ChatMessage $message)
    {
        $thread = new \dbObject\ChatThread();
        if (
            !$thread->load((int)$message->get('IDchat_thread'))
            || (string)$thread->get('subject_type') !== \dbObject\ChatThread::SUBJECT_PROJECT
        ) {
            return;
        }

        $project = new \dbObject\Project();
        if (
            !$project->load((int)$thread->get('subject_id'))
            || (int)$project->get('IDorganization') !== (int)$thread->get('IDorganization')
        ) {
            return;
        }

        $organizationId = (int)$project->get('IDorganization');
        $projectId = (int)$project->getId();
        if ($organizationId <= 0 || $projectId <= 0) {
            return;
        }

        $projectTitle = mb_substr(trim((string)$project->get('title')), 0, 120, 'UTF-8');
        if ($projectTitle === '') {
            $projectTitle = 'ce projet';
        }
        $authorName = $message->isAnonymous()
            ? notificationCenterT('notification.participant')
            : mb_substr(trim((string)$message->get('author_name')), 0, 80, 'UTF-8');
        if ($authorName === '' && !$message->isAnonymous()) {
            $authorName = notificationCenterActorName($message->get('IDuser'), $organizationId);
        }
        $title = 'Nouveau message - ' . $projectTitle;
        $body = notificationCenterT('notification.project.message', ['actor' => $authorName, 'title' => $projectTitle]);
        $url = notificationCenterBuildProjectUrl($organizationId, $projectId);
        $messageId = (int)$message->getId();
        $actorUserId = (int)$message->get('IDuser');
        $ownerUserIds = [
            (int)$project->get('IDuser'),
            (int)$project->get('IDuser_proposed'),
        ];

        notificationCenterCreateForUsers(
            $organizationId,
            'project_chat_owner',
            $ownerUserIds,
            'project-chat-owner-' . $messageId,
            $title,
            $body,
            $url,
            'PROJECT_CHAT_' . $organizationId . '_' . $messageId,
            $actorUserId
        );
        notificationCenterCreateForUsers(
            $organizationId,
            'project_chat_participant',
            \dbObject\ChatMessage::getParticipantUserIdsForThread((int)$thread->getId()),
            'project-chat-participant-' . $messageId,
            $title,
            $body,
            $url,
            'PROJECT_CHAT_' . $organizationId . '_' . $messageId,
            $actorUserId
        );
    }
}

if (!function_exists('notificationCenterDispatchProjectStatusChange')) {
    function notificationCenterDispatchProjectStatusChange(\dbObject\Project $project, $previousStatus, $actorUserId = 0)
    {
        $organizationId = (int)$project->get('IDorganization');
        $projectId = (int)$project->getId();
        $previousStatus = \dbObject\Project::normalizeStatus($previousStatus);
        $currentStatus = \dbObject\Project::normalizeStatus($project->get('status'));
        if ($organizationId <= 0 || $projectId <= 0 || $previousStatus === $currentStatus) {
            return 0;
        }

        $followerUserIds = \dbObject\ProjectFollower::getActiveUserIdsForProject($projectId);
        if ($followerUserIds === []) {
            return 0;
        }

        $projectTitle = mb_substr(trim((string)$project->get('title')), 0, 120, 'UTF-8');
        if ($projectTitle === '') {
            $projectTitle = 'ce projet';
        }
        $previousLabel = \dbObject\Project::getOrganizationStatusLabel($organizationId, $previousStatus);
        $currentLabel = \dbObject\Project::getOrganizationStatusLabel($organizationId, $currentStatus);
        $sourceKey = 'project-status-changed-' . $projectId . '-' . str_replace('.', '-', uniqid('', true));

        notificationCenterCreateForUsers(
            $organizationId,
            'project_status_changed',
            $followerUserIds,
            $sourceKey,
            notificationCenterT('notification.project.status_title', ['title' => $projectTitle]),
            notificationCenterT((int)$actorUserId > 0 ? 'notification.project.status' : 'notification.project.status_auto', [
                'actor' => (int)$actorUserId > 0 ? notificationCenterActorName($actorUserId, $organizationId) : '',
                'title' => $projectTitle,
                'previous' => $previousLabel,
                'current' => $currentLabel,
            ]),
            notificationCenterBuildProjectUrl($organizationId, $projectId),
            '',
            (int)$actorUserId
        );

        return count($followerUserIds);
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

if (!function_exists('notificationCenterDispatchDecisionCompletedPhase')) {
    function notificationCenterDispatchDecisionCompletedPhase(\dbObject\DecisionProcess $decision, $phase, $referenceDateTime = null)
    {
        $phase = $phase === 'consultation' ? 'consultation' : 'vote';
        $organizationId = (int)$decision->get('IDorganization');
        $decisionId = (int)$decision->getId();
        $ownerId = (int)$decision->get('IDuser');
        if ($organizationId <= 0 || $decisionId <= 0 || $ownerId <= 0) {
            return 0;
        }
        $deadlineField = $phase === 'consultation' ? 'consultation_end_at' : 'evaluation_end_at';
        $deadline = \dbObject\DecisionProcess::normalizeDateTimeValue($decision->get($deadlineField));
        $sourceDate = $deadline instanceof \DateTimeInterface
            ? $deadline->format('YmdHi')
            : ($referenceDateTime instanceof \DateTimeInterface ? $referenceDateTime->format('YmdHi') : 'completed');
        $eventKey = $phase === 'consultation' ? 'decision_consultation_finished' : 'decision_evaluation_finished';
        $phaseLabel = $phase === 'consultation' ? 'consultation' : 'vote';
        $titlePrefix = $phase === 'consultation' ? 'Fin de la consultation' : 'Fin du vote';
        $decisionTitle = mb_substr(trim((string)$decision->get('title')), 0, 140, 'UTF-8');
        notificationCenterCreateForUsers(
            $organizationId,
            $eventKey,
            [$ownerId],
            'decision-' . $phase . '-finished-' . $decisionId . '-' . $sourceDate,
            $titlePrefix . ' - ' . $decisionTitle,
            'La periode de ' . $phaseLabel . ' du scrutin "' . $decisionTitle . '" est terminee. Vous pouvez maintenant effectuer les actions de suivi necessaires.',
            notificationCenterBuildDecisionUrl($organizationId, $decisionId)
        );
        return 1;
    }
}

if (!function_exists('notificationCenterDispatchDecisionPhaseFinished')) {
    function notificationCenterDispatchDecisionPhaseFinished(\dbObject\DecisionProcess $decision, $previousStatus, $nextStatus, $referenceDateTime = null)
    {
        $previousStatus = \dbObject\DecisionProcess::normalizeStatus($previousStatus);
        $nextStatus = \dbObject\DecisionProcess::normalizeStatus($nextStatus);
        $phase = '';
        if (
            $previousStatus === \dbObject\DecisionProcess::STATUS_CONSULTATION
            && \dbObject\DecisionProcess::getStatusRank($nextStatus) >= \dbObject\DecisionProcess::getStatusRank(\dbObject\DecisionProcess::STATUS_EVALUATION)
        ) {
            $phase = 'consultation';
        } elseif (
            $previousStatus === \dbObject\DecisionProcess::STATUS_EVALUATION
            && \dbObject\DecisionProcess::getStatusRank($nextStatus) >= \dbObject\DecisionProcess::getStatusRank(\dbObject\DecisionProcess::STATUS_RESULTS)
        ) {
            $phase = 'vote';
        }
        if ($phase === '') {
            return 0;
        }

        return notificationCenterDispatchDecisionCompletedPhase($decision, $phase, $referenceDateTime);
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

            foreach ([
                ['phase' => 'consultation', 'start' => 'consultation_start_at', 'end' => 'consultation_end_at'],
                ['phase' => 'vote', 'start' => 'evaluation_start_at', 'end' => 'evaluation_end_at'],
            ] as $phaseSchedule) {
                $phaseStart = \dbObject\DecisionProcess::normalizeDateTimeValue($decision->get($phaseSchedule['start']));
                $phaseEnd = \dbObject\DecisionProcess::normalizeDateTimeValue($decision->get($phaseSchedule['end']));
                if (
                    $phaseEnd instanceof \DateTimeInterface
                    && $phaseEnd <= $referenceDateTime
                    && (!($phaseStart instanceof \DateTimeInterface) || $phaseStart <= $referenceDateTime)
                ) {
                    notificationCenterDispatchDecisionCompletedPhase($decision, $phaseSchedule['phase'], $referenceDateTime);
                }
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
