<?php
namespace dbObject;
require_once dirname(__DIR__, 2) . '/common/mcp/protocol.php';

/** One permission-checked audience shared by the OMO composer and MCP. */
final class ObjectAudience
{
    public const TYPES = ['holon', 'event', 'project', 'decision'];

    public static function resolve(int $organizationId, string $type, int $id): array
    {
        $uid = (int)\commonGetCurrentUserId();
        $user = new User(); $org = new Organization();
        if ($uid <= 0 || !$user->load($uid, true) || !$user->get('active')
            || !UserOrganization::hasActiveMembership($uid, $organizationId)
            || !$org->load($organizationId) || !$org->canViewDetail()
            || (function_exists('commonGetCurrentShareLink') && \commonGetCurrentShareLink())) {
            throw new \DomainException('Connexion avec un compte membre actif requise.');
        }
        $module = ['holon' => 'structure', 'event' => 'calendar', 'project' => 'projects', 'decision' => 'decision'][$type] ?? null;
        if (!$module || $id <= 0 || !in_array($module, McpContent::enabledModules($org, $uid), true)) {
            throw new \DomainException('Objet indisponible.');
        }
        $context = McpContent::context(['IDuser' => $uid], $org, null);
        $object = McpContent::accessibleObject($org, $context, $module, $id);
        if (!$object || (!$object instanceof DecisionProcess && !$object->get('active'))) throw new \DomainException('Objet indisponible.');
        $members = []; $canManage = false; $semantics = '';
        $addUser = static function (int $userId, string $relation, string $status = 'active', bool $requireActive = true) use ($organizationId, &$members): void {
            $member = new User();
            if (!$member->load($userId, true) || !$member->canViewDetail()) return;
            $mailEligible = (bool)$member->get('active') && UserOrganization::hasActiveMembership($userId, $organizationId);
            if (($requireActive && !$mailEligible) || (!$requireActive && !$member->getOrganizationMembership($organizationId))) return;
            $key = 'user:' . $userId;
            $email = strtolower(trim((string)$member->getScopedEmail($organizationId)));
            if (isset($members[$key])) {
                $members[$key]['relations'] = array_values(array_unique([...$members[$key]['relations'], $relation]));
                return;
            }
            $name = (string)$member->getScopedDisplayName($organizationId);
            $members[$key] = ['member_id' => $key, 'user_id' => $userId, 'name' => $name,
                'firstname' => (string)$member->get('firstname'), 'lastname' => (string)$member->get('lastname'),
                'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                'phone' => (string)$member->getScopedPhone($organizationId), 'relations' => [$relation], 'status' => $status,
                'mail_eligible' => $mailEligible];
        };
        $addExternal = static function (string $email, string $name, string $relation, string $status = 'invited') use (&$members): void {
            $email = strtolower(trim($email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return;
            $key = 'guest:' . hash('sha256', $email);
            $members[$key] = ['member_id' => $key, 'user_id' => null, 'name' => trim($name) ?: $email,
                'email' => $email, 'phone' => null, 'relations' => [$relation], 'status' => $status];
        };
        if ($object instanceof Holon) {
            if (!$org->containsHolon($object) || !$object->get('visible') || !$object->canViewDetail()) throw new \DomainException('Holon indisponible.');
            foreach ($object->getAssociatedMemberUserIds(['organizationId' => $organizationId, 'activeOnly' => true, 'skipPermissionFilter' => true]) as $memberId) $addUser((int)$memberId, 'member');
            $canManage = $object->isAllowed('CAN_ADD_MEMBER', false, $uid);
            $semantics = 'Membres effectifs du holon, y compris les roles descendants des cercles et les appartenances calculees. Le holon racine represente les membres actifs de l organisation.';
        } elseif ($object instanceof Event) {
            require_once dirname(__DIR__, 2) . '/omo/api/calendar/permissions_shared.php';
            $canManage = \omoCalendarCanEditEvent($object, $organizationId, $uid, $org->getStructuralRootHolon(), false);
            // Enumerate the native invitation scope, independently of delivery eligibility or attendance.
            $targets = $object->getEffectiveInvitationTargets($organizationId, null, true);
            $deliveryTargets = $object->getEffectiveInvitationTargets($organizationId, null, true, true);
            $deliveryUserIds = array_fill_keys($deliveryTargets['userIds'], true);
            foreach ($targets['userIds'] as $memberId) {
                $addUser((int)$memberId, 'invited', 'invited', false);
                $key = 'user:' . (int)$memberId;
                if (isset($members[$key]) && !isset($deliveryUserIds[(int)$memberId])) $members[$key]['mail_eligible'] = false;
            }
            // External invitation details follow the native invitation editor's access rule.
            if ($canManage) foreach ($targets['emails'] as $email) $addExternal($email, (string)($targets['registeredEmails'][$email] ?? ''), 'invited');
            $groupStatuses = []; $individualStatuses = [];
            foreach ($object->getInvitations(true) as $invitation) {
                $status = ResourceInvitation::normalizeStatus($invitation->get('status'));
                if ($status === ResourceInvitation::STATUS_REVOKED) continue;
                if ($invitation->get('invitation_type') === ResourceInvitation::TYPE_HOLON) {
                    $group = $object->getEffectiveInvitationTargets($organizationId, [$invitation], true);
                    foreach ($group['userIds'] as $memberId) {
                        $key = 'user:' . $memberId;
                        if (!isset($groupStatuses[$key]) || $groupStatuses[$key] === 'declined') $groupStatuses[$key] = $status;
                    }
                } else {
                    $key = $invitation->get('invitation_type') === ResourceInvitation::TYPE_USER ? 'user:' . (int)$invitation->get('IDuser')
                        : 'guest:' . hash('sha256', strtolower(trim((string)$invitation->get('email'))));
                    $individualStatuses[$key] = $status;
                    if (isset($members[$key]) && $invitation->get('invitation_type') === ResourceInvitation::TYPE_EMAIL
                        && trim((string)$invitation->get('display_name')) !== '') $members[$key]['name'] = (string)$invitation->get('display_name');
                }
            }
            foreach (array_replace($groupStatuses, $individualStatuses) as $key => $status) if (isset($members[$key])) $members[$key]['status'] = $status;
            $semantics = 'Liste native des invitations individuelles et des holons invites, ou membres du holon de la reunion en leur absence, independante de la presence. Les inscriptions publiques et adresses invitees sont visibles aux gestionnaires. Un contact reference peut etre liste sans etre eligible a l envoi. Une invitation refusee ne recoit pas de message.';
        } elseif ($object instanceof Project) {
            $projectContext = \omoProjectsResolveContext($organizationId, $context['currentHolonId'], false);
            $canManage = \omoProjectsCanManageProject($object, $projectContext);
            $addUser((int)$object->get('IDuser'), 'responsible');
            $assignments = new ArrayProjectUser();
            $assignments->load(['where' => [['field' => 'IDproject', 'value' => $id], ['field' => 'active', 'value' => 1]]]);
            foreach ($assignments as $assignment) $addUser((int)$assignment->get('IDuser'), 'assignee');
            $semantics = 'Responsable et personnes affectees activement au projet. Les abonnements et membres du holon ne constituent pas une invitation au projet.';
        } elseif ($object instanceof DecisionProcess) {
            $canManage = $object->canUseManagementPermission('CAN_EDIT_DECISION', $uid);
            // Do not disclose private participant identities or voting information to a general viewer.
            if (!$canManage) throw new \DomainException('Gestion de la decision requise pour consulter ses destinataires.');
            foreach ($object->getParticipants(true) as $participant) {
                $status = DecisionParticipant::normalizeStatus($participant->get('status'));
                if ($status === DecisionParticipant::STATUS_REVOKED) continue;
                $memberId = (int)$participant->get('IDuser');
                if ($memberId > 0) $addUser($memberId, (string)$participant->get('role'), $status);
                else $addExternal((string)$participant->get('email'), (string)$participant->get('display_name'), (string)$participant->get('role'), $status);
            }
            $semantics = 'Participants actifs deja references dans la decision, sans votes ni jetons personnels. Les participants refuses ou revoques ne recoivent pas de message.';
        }
        ksort($members, SORT_STRING);
        $recipients = [];
        foreach ($members as $member) {
            $members[$member['member_id']]['mail_eligible'] = ($member['mail_eligible'] ?? true)
                && (bool)$member['email'] && !in_array($member['status'], ['declined', 'revoked'], true);
            if ($members[$member['member_id']]['mail_eligible']) $recipients[$member['email']] = $members[$member['member_id']];
        }
        ksort($recipients, SORT_STRING);
        $canSend = $canManage || (isset($members['user:' . $uid]) && !in_array($members['user:' . $uid]['status'], ['declined', 'revoked'], true));
        $senderName = (string)$user->getScopedDisplayName($organizationId);
        $senderEmail = trim((string)$user->getScopedEmail($organizationId));
        $canSend = $canSend && (bool)filter_var($senderEmail, FILTER_VALIDATE_EMAIL);
        $title = $object instanceof Holon ? (string)$object->getDisplayName() : (string)$object->get('title');
        return ['organization_id' => $organizationId, 'object_type' => $type, 'object_id' => $id, 'title' => $title,
            'holon_id' => $object instanceof Holon ? $id : (int)$object->get('IDholon'),
            'members' => array_values($members), 'recipients' => array_values($recipients), 'can_send' => $canSend,
            'audience_token' => hash('sha256', json_encode([$organizationId, $type, $id, array_keys($recipients)], JSON_THROW_ON_ERROR)),
            'semantics' => $semantics, 'sender' => ['user_id' => $uid, 'name' => $senderName, 'email' => $senderEmail],
            'organization_name' => (string)$org->get('name')];
    }

    public static function page(int $organizationId, array $args): array
    {
        $audience = self::resolve($organizationId, $args['object_type'], $args['object_id']);
        $offset = $args['offset'] ?? 0; $limit = $args['limit'] ?? 50;
        $items = array_slice($audience['members'], $offset, $limit); $next = $offset + count($items);
        return ['organization_id' => $organizationId, 'object_type' => $audience['object_type'], 'object_id' => $audience['object_id'],
            'url' => McpContent::sourceUrl($organizationId, ['holon' => 'structure', 'event' => 'calendar', 'project' => 'projects', 'decision' => 'decision'][$audience['object_type']], $audience['object_id'], $audience['holon_id']),
            'title' => $audience['title'], 'items' => $items, 'total' => count($audience['members']),
            'recipient_count' => count($audience['recipients']), 'can_send' => $audience['can_send'], 'audience_token' => $audience['audience_token'],
            'complete' => $next >= count($audience['members']), 'next_offset' => $next < count($audience['members']) ? $next : null,
            'semantics' => $audience['semantics']];
    }
}
