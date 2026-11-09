<?php
namespace dbObject;
require_once dirname(__DIR__, 2) . '/common/mcp/protocol.php';
require_once dirname(__DIR__, 2) . '/common/object_mail/validation.php';

/** One permission-checked audience shared by the OMO composer and MCP. */
final class ObjectAudience
{
        private static function uiMessage(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.un_membre_selectionne_est_indisponible_ou_inactif_dans_cette_organisat' => ['text' => 'Un membre sélectionné est indisponible ou inactif dans cette organisation.', 'context' => 'Interface text in class/dbobject/objectaudience.class.php'],
        'copy.membres_actifs_de_l_organisation_autorisee_ou_selection_explicite_par_' => ['text' => 'Membres actifs de l’organisation autorisée, ou sélection explicite par user_ids. Coordonnées propres à cette organisation. Aucun destinataire externe.', 'context' => 'Interface text in class/dbobject/objectaudience.class.php'],
        'copy.membres_effectifs_du_holon_y_compris_les_roles_descendants_des_cercles' => ['text' => 'Membres effectifs du holon, y compris les rôles descendants des cercles et les appartenances calculées. Le holon racine représente les membres actifs de l’organisation.', 'context' => 'Interface text in class/dbobject/objectaudience.class.php'],
        'copy.liste_native_des_invitations_individuelles_et_des_holons_invites_ou_me' => ['text' => 'Liste native des invitations individuelles et des holons invités, ou membres du holon de la réunion en leur absence, indépendante de la présence. Les inscriptions publiques et adresses invitées sont visibles aux gestionnaires. Un contact référence peut être liste sans être éligible à l’envoi. Une invitation refusée ne reçoit pas de message.', 'context' => 'Interface text in class/dbobject/objectaudience.class.php'],
        'copy.responsable_et_personnes_affectees_activement_au_projet_les_abonnement' => ['text' => 'Responsable et personnes affectées activement au projet. Les abonnements et membres du holon ne constituent pas une invitation au projet.', 'context' => 'Interface text in class/dbobject/objectaudience.class.php'],
        'copy.participants_actifs_deja_references_dans_la_decision_sans_votes_ni_jet' => ['text' => 'Participants actifs déjà référencés dans la décision, sans votes ni jetons personnels. Les participants refusés ou révoqués ne reçoivent pas de message.', 'context' => 'Interface text in class/dbobject/objectaudience.class.php'],
        'copy.un_destinataire_selectionne_est_indisponible_ou_invalide' => ['text' => 'Un destinataire sélectionné est indisponible ou invalide.', 'context' => 'Interface text in class/dbobject/objectaudience.class.php'],
    ];
    require_once __DIR__ . '/../../common/translation_bundles.php';
    static $bundles = [];
    $locale = \translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= \loadTranslationBundle('class_dbobject_objectaudience_class', $locale, $sourceLang)) : $sourceLang;
    return \t($key, $variables, $bundle, $sourceLang);
}

    public const TYPES = ['organization', 'holon', 'event', 'project', 'decision'];

    public static function resolve(int $organizationId, string $type, int $id, ?array $userIds = null): array
    {
        if ($userIds !== null) {
            \omoObjectMailValidateSelection(['object_type' => $type, 'user_ids' => $userIds]);
            sort($userIds, SORT_NUMERIC);
        }
        $uid = (int)\commonGetCurrentUserId();
        $user = new User(); $org = new Organization();
        if ($uid <= 0 || !$user->load($uid, true) || !$user->get('active')
            || !UserOrganization::hasActiveMembership($uid, $organizationId)
            || !$org->load($organizationId) || !$org->canViewDetail()
            || (function_exists('commonGetCurrentShareLink') && \commonGetCurrentShareLink())) {
            throw new \DomainException('Connexion avec un compte membre actif requise.');
        }
        $module = ['holon' => 'structure', 'event' => 'calendar', 'project' => 'projects', 'decision' => 'decision'][$type] ?? null;
        if ($type === 'organization' ? $id !== $organizationId
            : (!$module || $id <= 0 || !in_array($module, McpContent::enabledModules($org, $uid), true))) {
            throw new \DomainException('Objet indisponible.');
        }
        $context = McpContent::context(['IDuser' => $uid], $org, null);
        $object = $type === 'organization' ? $org : McpContent::accessibleObject($org, $context, $module, $id);
        if (!$object || (!$object instanceof Organization && !$object instanceof DecisionProcess && !$object->get('active'))) throw new \DomainException('Objet indisponible.');
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
        if ($object instanceof Organization) {
            $where = [['field' => 'IDorganization', 'value' => $organizationId], ['field' => 'active', 'value' => 1]];
            if ($userIds !== null) $where[] = ['field' => 'IDuser', 'op' => 'IN', 'value' => $userIds];
            $memberships = new ArrayUserOrganization();
            $memberships->load(['where' => $where]);
            foreach ($memberships as $membership) $addUser((int)$membership->get('IDuser'), 'organization_member');
            // A selected, unavailable member must never turn into an organization-wide send.
            if ($userIds !== null) foreach ($userIds as $memberId) {
                if (!isset($members['user:' . $memberId])) throw new \DomainException(self::uiMessage('copy.un_membre_selectionne_est_indisponible_ou_inactif_dans_cette_organisat'));
            }
            $semantics = self::uiMessage('copy.membres_actifs_de_l_organisation_autorisee_ou_selection_explicite_par_');
        } elseif ($object instanceof Holon) {
            if (!$org->containsHolon($object) || !$object->get('visible') || !$object->canViewDetail()) throw new \DomainException('Holon indisponible.');
            foreach ($object->getAssociatedMemberUserIds(['organizationId' => $organizationId, 'activeOnly' => true, 'skipPermissionFilter' => true]) as $memberId) $addUser((int)$memberId, 'member');
            $semantics = self::uiMessage('copy.membres_effectifs_du_holon_y_compris_les_roles_descendants_des_cercles');
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
            $semantics = self::uiMessage('copy.liste_native_des_invitations_individuelles_et_des_holons_invites_ou_me');
        } elseif ($object instanceof Project) {
            $addUser((int)$object->get('IDuser'), 'responsible');
            $assignments = new ArrayProjectUser();
            $assignments->load(['where' => [['field' => 'IDproject', 'value' => $id], ['field' => 'active', 'value' => 1]]]);
            foreach ($assignments as $assignment) $addUser((int)$assignment->get('IDuser'), 'assignee');
            $semantics = self::uiMessage('copy.responsable_et_personnes_affectees_activement_au_projet_les_abonnement');
        } elseif ($object instanceof DecisionProcess) {
            $canManage = $object->canUseManagementPermission('CAN_EDIT_DECISION', $uid);
            foreach ($object->getParticipants(true) as $participant) {
                $status = DecisionParticipant::normalizeStatus($participant->get('status'));
                if ($status === DecisionParticipant::STATUS_REVOKED) continue;
                $memberId = (int)$participant->get('IDuser');
                if ($memberId > 0) $addUser($memberId, (string)$participant->get('role'), $status);
                elseif ($canManage) $addExternal((string)$participant->get('email'), (string)$participant->get('display_name'), (string)$participant->get('role'), $status);
            }
            $semantics = self::uiMessage('copy.participants_actifs_deja_references_dans_la_decision_sans_votes_ni_jet');
        }
        ksort($members, SORT_STRING);
        $recipients = [];
        foreach ($members as $member) {
            $members[$member['member_id']]['mail_eligible'] = ($member['mail_eligible'] ?? true)
                && (bool)$member['email'] && !in_array($member['status'], ['declined', 'revoked'], true);
            if ($members[$member['member_id']]['mail_eligible']) $recipients[$member['email']] = $members[$member['member_id']];
        }
        ksort($recipients, SORT_STRING);
        $senderName = (string)$user->getScopedDisplayName($organizationId);
        $senderEmail = trim((string)$user->getScopedEmail($organizationId));
        // Active organization membership was checked above; sending does not require an object role.
        $canSend = (bool)filter_var($senderEmail, FILTER_VALIDATE_EMAIL);
        $title = $object instanceof Organization ? (string)$org->get('name')
            : ($object instanceof Holon ? (string)$object->getDisplayName() : (string)$object->get('title'));
        $tokenParts = [$organizationId, $type, $id, array_keys($recipients)];
        if ($userIds !== null) $tokenParts[] = $userIds;
        return ['organization_id' => $organizationId, 'object_type' => $type, 'object_id' => $id, 'title' => $title,
            'holon_id' => $object instanceof Organization ? 0 : ($object instanceof Holon ? $id : (int)$object->get('IDholon')),
            'members' => array_values($members), 'recipients' => array_values($recipients), 'can_send' => $canSend,
            'audience_token' => hash('sha256', json_encode($tokenParts, JSON_THROW_ON_ERROR)),
            'semantics' => $semantics, 'sender' => ['user_id' => $uid, 'name' => $senderName, 'email' => $senderEmail],
            'organization_name' => (string)$org->get('name')];
    }

    /** Narrow an already permission-checked audience; never accept free addresses or an empty selection. */
    public static function selectRecipients(array $audience, array $memberIds): array
    {
        if (!array_is_list($memberIds) || count($memberIds) < 1 || count($memberIds) > ObjectMail::MAX_RECIPIENTS) {
            throw new \InvalidArgumentException('Selectionnez entre 1 et 500 destinataires.');
        }
        $eligible = array_column($audience['recipients'], null, 'member_id');
        $selected = [];
        foreach ($memberIds as $memberId) {
            if (!is_string($memberId) || !isset($eligible[$memberId]) || isset($selected[$memberId])) {
                throw new \DomainException(self::uiMessage('copy.un_destinataire_selectionne_est_indisponible_ou_invalide'));
            }
            $selected[$memberId] = true;
        }
        $audience['recipients'] = array_values(array_filter($audience['recipients'], static fn ($member) => isset($selected[$member['member_id']])));
        return $audience;
    }

    public static function page(int $organizationId, array $args): array
    {
        \omoObjectMailValidateSelection($args);
        $audience = self::resolve($organizationId, $args['object_type'], $args['object_id'], $args['user_ids'] ?? null);
        $offset = $args['offset'] ?? 0; $limit = $args['limit'] ?? 50;
        $items = array_slice($audience['members'], $offset, $limit); $next = $offset + count($items);
        return ['organization_id' => $organizationId, 'object_type' => $audience['object_type'], 'object_id' => $audience['object_id'],
            'url' => $audience['object_type'] === 'organization' ? \omoMcpIssuer() . '/omo/o/' . $organizationId
                : McpContent::sourceUrl($organizationId, ['holon' => 'structure', 'event' => 'calendar', 'project' => 'projects', 'decision' => 'decision'][$audience['object_type']], $audience['object_id'], $audience['holon_id']),
            'title' => $audience['title'], 'items' => $items, 'total' => count($audience['members']),
            'recipient_count' => count($audience['recipients']), 'can_send' => $audience['can_send'], 'audience_token' => $audience['audience_token'],
            'complete' => $next >= count($audience['members']), 'next_offset' => $next < count($audience['members']) ? $next : null,
            'semantics' => $audience['semantics']];
    }
}
