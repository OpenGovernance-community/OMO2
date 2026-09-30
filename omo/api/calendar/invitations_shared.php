<?php

use dbObject\ArrayUserOrganization;
use dbObject\Event;
use dbObject\EventInvitation;
use dbObject\ResourceInvitation;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\UserOrganization;

if (!function_exists('omoCalendarInvitationSourceLang')) {
    function omoCalendarInvitationSourceLang()
    {
        return [
            'calendar.invitations.title' => [
                'text' => 'Invités',
                'context' => 'Shared title used for event invitation settings.',
            ],
            'calendar.invitations.popup_title' => [
                'text' => 'Invités',
                'context' => 'Modal title used when editing event invitations from the event detail drawer.',
            ],
            'calendar.invitations.configure' => [
                'text' => 'Modifier',
                'context' => 'Button label used to edit event invitations from the summary block.',
            ],
            'calendar.invitations.tabs_aria' => [
                'text' => "Catégories d'invitations",
                'context' => 'Accessible label used by the event invitation tabs.',
            ],
            'calendar.invitations.tab.holons' => [
            'text' => 'Espaces',
                'context' => 'Tab label for holon invitations in calendar.',
            ],
            'calendar.invitations.tab.members' => [
                'text' => 'Membres',
                'context' => 'Tab label for member invitations in calendar.',
            ],
            'calendar.invitations.tab.guests' => [
                'text' => 'Invités externes',
                'context' => 'Tab label for email invitations in calendar.',
            ],
            'calendar.invitations.tab.public' => [
                'text' => 'Inscriptions tout public',
                'context' => 'Tab for enabling public event registration and listing public registrants.',
            ],
            'calendar.invitations.current' => [
                'text' => '(courant)',
                'context' => 'Suffix shown next to the current holon inside the event invitation tree.',
            ],
            'calendar.invitations.no_structure' => [
                'text' => "Cette organisation n'a pas encore de structure. Vous pouvez inviter directement des membres de l'organisation ou des adresses e-mail externes.",
                'context' => 'Hint shown when no holon structure is available for the event invitation editor.',
            ],
            'calendar.invitations.holons_title' => [
            'text' => 'Espaces invités',
                'context' => 'Section title for invited holons in the event invitation editor.',
            ],
            'calendar.invitations.holons_hint' => [
            'text' => "L’espace de contexte est coché par défaut pour éviter une exclusion involontaire. Dès qu’une sélection explicite existe, seule cette liste fait foi.",
                'context' => 'Hint shown below the invited holons list in the event invitation editor.',
            ],
            'calendar.invitations.holons_filter_placeholder' => [
            'text' => 'Filtrer les espaces...',
                'context' => 'Placeholder shown in the quick filter for the invited holons tree.',
            ],
            'calendar.invitations.members_title' => [
                'text' => 'Membres invités individuellement',
                'context' => 'Section title for individually invited members in the event invitation editor.',
            ],
            'calendar.invitations.members_filter_placeholder' => [
                'text' => 'Filtrer les membres...',
                'context' => 'Placeholder shown in the quick filter for individually invited members.',
            ],
            'calendar.invitations.members_hint_structure' => [
            'text' => 'Cochez ici les membres à ajouter en plus des espaces sélectionnés.',
                'context' => 'Hint shown below invited members when a holon structure exists.',
            ],
            'calendar.invitations.members_hint_flat' => [
                'text' => 'Cochez ici les membres à inviter individuellement.',
                'context' => 'Hint shown below invited members when no holon structure exists.',
            ],
            'calendar.invitations.filter_empty' => [
                'text' => 'Aucun résultat pour ce filtre.',
                'context' => 'Message shown when a quick invitation filter has no visible result.',
            ],
            'calendar.invitations.guests_title' => [
                'text' => 'Adresses e-mail externes',
                'context' => 'Section title for invited guest emails in calendar.',
            ],
            'calendar.invitations.guests_placeholder' => [
                'text' => 'prenom.nom@exemple.ch',
                'context' => 'Placeholder shown in the guest email textarea for event invitations.',
            ],
            'calendar.invitations.guests_hint' => [
                'text' => "Une adresse par ligne. L'envoi d'e-mails pourra être branché ensuite sur cette liste.",
                'context' => 'Hint shown below the guest email textarea in the event invitation editor.',
            ],
            'calendar.invitations.public_enable' => [
                'text' => "Permettre l'inscription tout public",
                'context' => 'Checkbox enabling public registration for an event.',
            ],
            'calendar.invitations.public_hint' => [
                'text' => "Les personnes confirmeront leur adresse e-mail avant que leur inscription soit effective.",
                'context' => 'Explanation of public event registration.',
            ],
            'calendar.invitations.public_link' => [
                'text' => 'Lien à diffuser',
                'context' => 'Label for the public event registration URL.',
            ],
            'calendar.invitations.people_count' => [
                'one' => '{count} personne invitée ou inscrite',
                'other' => '{count} personnes invitées ou inscrites',
                'context' => 'Distinct people invited through spaces, individual membership or email, including confirmed public registrations.',
            ],
            'calendar.invitations.holon_people_count' => [
                'one' => '{name} ({count} personne)',
                'other' => '{name} ({count} personnes)',
                'context' => 'Selected invitation space and number of its members, before deduplicating with other spaces.',
            ],
            'calendar.invitations.current_holon_people_count' => [
                'one' => '{name} (contexte courant, {count} personne)',
                'other' => '{name} (contexte courant, {count} personnes)',
                'context' => 'Invited current context and number of its members.',
            ],
            'calendar.invitations.no_default_members' => [
                'text' => 'Aucun membre invité par défaut.',
                'context' => 'Invitation summary when no space or individual member is selected and no default invitation scope applies.',
            ],
            'calendar.invitations.individual_members_count' => [
                'one' => '{count} membre invité individuellement',
                'other' => '{count} membres invités individuellement',
                'context' => 'Number of explicitly selected individual members; they may also belong to invited spaces.',
            ],
            'calendar.invitations.registered_count' => [
                'one' => '{count} inscription confirmée par e-mail',
                'other' => '{count} inscriptions confirmées par e-mail',
                'context' => 'Number of confirmed public event registrations.',
            ],
            'calendar.invitations.count_hint' => [
                'text' => 'Le total compte chaque personne une seule fois. Les inscriptions en attente de confirmation sont exclues.',
                'context' => 'Explanation of invitation totals and overlapping invitation sources.',
            ],
            'calendar.invitations.registrations_title' => [
                'text' => 'Inscriptions par e-mail',
                'context' => 'Heading of the registrant list in the public registration tab.',
            ],
            'calendar.invitations.registrations_empty' => [
                'text' => "Aucune demande d'inscription pour le moment.",
                'context' => 'Empty public registration list in OMO.',
            ],
            'calendar.invitations.registration_confirmed' => [
                'text' => 'Inscription confirmée',
                'context' => 'Status of a public registrant who validated their email address.',
            ],
            'calendar.invitations.registration_pending' => [
                'text' => 'En attente de confirmation',
                'context' => 'Status of a public registration which is not yet effective.',
            ],
            'calendar.invitations.default_scope' => [
                'text' => 'Par défaut, tous les membres du contexte rattaché à cet événement sont invités.',
                'context' => 'Summary shown when an event has no explicit invitations and falls back to its context.',
            ],
            'calendar.invitations.default_scope_organization' => [
                'text' => "Par défaut, tous les membres de l'organisation sont invités.",
                'context' => 'Summary shown when an event has no explicit invitations and no holon context.',
            ],
            'calendar.invitations.additional_people' => [
                'one' => '{count} personne supplémentaire',
                'other' => '{count} personnes supplémentaires',
                'context' => 'Summary fragment counting explicit additional invitees outside holons.',
            ],
            'calendar.invitations.additional_emails' => [
                'one' => '{count} e-mail externe',
                'other' => '{count} e-mails externes',
                'context' => 'Summary fragment counting explicit guest emails for the event.',
            ],
            'calendar.invitations.current_scope_included' => [
                'text' => 'Le contexte courant est inclus.',
                'context' => 'Summary fragment shown when the current holon remains explicitly invited.',
            ],
            'calendar.invitations.current_scope_excluded' => [
                'text' => "Le contexte courant n'est pas inclus.",
                'context' => 'Summary fragment shown when the current holon is not explicitly invited.',
            ],
            'calendar.invitations.updated' => [
                'text' => 'Invités mis à jour.',
                'context' => 'Success message returned after saving event invitations.',
            ],
            'calendar.invitations.save_error' => [
                'text' => "Impossible d'enregistrer ces invités pour le moment.",
                'context' => 'Generic error message returned when event invitations could not be saved.',
            ],
            'calendar.invitations.db_error' => [
                'text' => 'Connexion à la base impossible.',
                'context' => 'Error returned when the invitation popup cannot access the database.',
            ],
            'calendar.invitations.invalid_holon' => [
            'text' => 'Un espace sélectionné est invalide.',
                'context' => 'Validation error returned when a selected holon is invalid for the event invitation editor.',
            ],
            'calendar.invitations.invalid_member' => [
                'text' => 'Un membre sélectionné est invalide.',
                'context' => 'Validation error returned when a selected member is invalid for the event invitation editor.',
            ],
            'calendar.invitations.empty_denied' => [
                'text' => 'Vous ne pouvez pas gérer les invités de cet événement.',
                'context' => 'Empty-state message shown when the invitation popup is opened without permission.',
            ],
            'calendar.invitations.submit' => [
                'text' => 'Enregistrer les invités',
                'context' => 'Submit button label used in the event invitation popup.',
            ],
            'calendar.invitations.summary_intro' => [
                'text' => 'Cette liste servira ensuite pour le contact et la présence.',
                'context' => 'Secondary note shown under the inline invitation editor in the event form.',
            ],
        ];
    }
}

if (!function_exists('omoCalendarInvitationParseEmails')) {
    function omoCalendarInvitationParseEmails($value)
    {
        $rawItems = is_array($value)
            ? $value
            : preg_split('/[\r\n,;]+/', (string)$value);
        $rawItems = is_array($rawItems) ? $rawItems : [];

        $emails = [];
        foreach ($rawItems as $item) {
            $email = trim(mb_strtolower((string)$item, 'UTF-8'));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (!in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }
}

if (!function_exists('omoCalendarExtractInvitationSelections')) {
    function omoCalendarExtractInvitationSelections($event)
    {
        $selectedHolonIds = [];
        $selectedUserIds = [];
        $selectedEmails = [];

        if (is_object($event) && method_exists($event, 'getInvitations')) {
            foreach ($event->getInvitations(true) as $invitation) {
                if (
                    !($invitation instanceof ResourceInvitation)
                    || ResourceInvitation::normalizeStatus($invitation->get('status')) === ResourceInvitation::STATUS_REVOKED
                ) {
                    continue;
                }

                $type = ResourceInvitation::normalizeType($invitation->get('invitation_type'));
                if ($type === ResourceInvitation::TYPE_HOLON) {
                    $selectedHolonIds[] = (int)$invitation->get('IDholon');
                    continue;
                }

                if ($type === ResourceInvitation::TYPE_USER) {
                    $selectedUserIds[] = (int)$invitation->get('IDuser');
                    continue;
                }

                $email = trim((string)$invitation->get('email'));
                if ($email !== '') {
                    $selectedEmails[] = $email;
                }
            }
        }

        $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $selectedHolonIds), static function ($holonId) {
            return $holonId > 0;
        })));
        $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $selectedUserIds), static function ($userId) {
            return $userId > 0;
        })));
        $selectedEmails = array_values(array_unique(array_filter($selectedEmails, static function ($email) {
            return trim((string)$email) !== '';
        })));

        return [
            'holon_ids' => $selectedHolonIds,
            'user_ids' => $selectedUserIds,
            'emails' => $selectedEmails,
            'count' => count($selectedHolonIds) + count($selectedUserIds) + count($selectedEmails),
        ];
    }
}

if (!function_exists('omoCalendarBuildInvitationHolonTreeData')) {
    function omoCalendarBuildInvitationHolonTreeData(Holon $holon, Organization $organization, array $selectedHolonIds, $currentHolonId)
    {
        if (!$organization->containsHolon($holon) || !$holon->canViewDetail()) {
            return null;
        }

        $holonId = (int)$holon->getId();
        $children = [];
        $hasSelectedDescendant = in_array($holonId, $selectedHolonIds, true);
        $hasCurrentDescendant = $holonId === (int)$currentHolonId;

        foreach ($holon->getChildren() as $child) {
            if (!$child instanceof Holon) {
                continue;
            }

            $childNode = omoCalendarBuildInvitationHolonTreeData($child, $organization, $selectedHolonIds, $currentHolonId);
            if (!is_array($childNode)) {
                continue;
            }

            $children[] = $childNode;
            if (!empty($childNode['hasSelectedDescendant'])) {
                $hasSelectedDescendant = true;
            }
            if (!empty($childNode['hasCurrentDescendant'])) {
                $hasCurrentDescendant = true;
            }
        }

        return [
            'id' => $holonId,
            'label' => trim((string)$holon->getDisplayName()),
            'typeLabel' => trim((string)$holon->getTemplateLabel(true)),
            'isCurrent' => $holonId === (int)$currentHolonId,
            'isSelected' => in_array($holonId, $selectedHolonIds, true),
            'children' => $children,
            'hasChildren' => count($children) > 0,
            'hasSelectedDescendant' => $hasSelectedDescendant,
            'hasCurrentDescendant' => $hasCurrentDescendant,
            'isExpanded' => $holonId === (int)$currentHolonId || $hasSelectedDescendant || $hasCurrentDescendant,
        ];
    }
}

if (!function_exists('omoCalendarRenderInvitationHolonTreeNode')) {
    function omoCalendarRenderInvitationHolonTreeNode(array $node, $escape, $currentLabel, $fieldName = 'invitation_holon_ids[]')
    {
        $hasChildren = !empty($node['hasChildren']);
        $isExpanded = !empty($node['isExpanded']);
        ?>
        <div
            class="omo-calendar-invitations-editor__tree-node<?= $hasChildren ? ' has-children' : '' ?>"
            data-omo-calendar-holon-node
            data-omo-calendar-search-text="<?= $escape(trim((string)$node['label'] . ' ' . (string)$node['typeLabel'])) ?>"
        >
            <div class="omo-calendar-invitations-editor__tree-row">
                <?php if ($hasChildren): ?>
                <button
                    type="button"
                    class="omo-calendar-invitations-editor__tree-toggle generic-tree-toggle"
                    data-omo-calendar-holon-toggle
                    aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                >
                    <span aria-hidden="true">&#9662;</span>
                </button>
                <?php else: ?>
                <span class="omo-calendar-invitations-editor__tree-spacer generic-tree-spacer" aria-hidden="true"></span>
                <?php endif; ?>

                <label class="omo-calendar-invitations-editor__check">
                    <input type="checkbox" name="<?= $escape($fieldName) ?>" value="<?= (int)$node['id'] ?>"<?= !empty($node['isSelected']) ? ' checked' : '' ?>>
                    <span class="omo-calendar-invitations-editor__check-meta">
                        <strong><?= $escape((string)$node['label']) ?><?= !empty($node['isCurrent']) ? ' ' . $escape((string)$currentLabel) : '' ?></strong>
                        <span class="omo-calendar-invitations-editor__check-type"><?= $escape((string)$node['typeLabel']) ?></span>
                    </span>
                </label>
            </div>

            <?php if ($hasChildren): ?>
            <div class="omo-calendar-invitations-editor__tree-children" data-omo-calendar-holon-children<?= $isExpanded ? '' : ' hidden' ?>>
                <?php foreach ((array)$node['children'] as $childNode): ?>
                    <?php omoCalendarRenderInvitationHolonTreeNode($childNode, $escape, $currentLabel, $fieldName); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('omoCalendarBuildInvitationEditorState')) {
    function omoCalendarBuildInvitationEditorState(
        $event,
        Organization $organization,
        int $organizationId,
        ?Holon $effectiveHolon,
        int $targetHolonId,
        int $defaultHolonId = 0,
        bool $preferDefaultHolonSelection = true,
        int $defaultUserId = 0
    ) {
        $selectionState = is_object($event) && method_exists($event, 'getInvitations')
            ? omoCalendarExtractInvitationSelections($event)
            : ['holon_ids' => [], 'user_ids' => [], 'emails' => [], 'count' => 0];

        $hasStructureApplication = $organization->isStructureApplicationEnabled();

        $memberships = new ArrayUserOrganization();
        if ($organizationId > 0) {
            $memberships->loadActiveForOrganization($organizationId);
        }

        $activeMemberUserIds = [];
        foreach ($memberships as $membership) {
            $memberUserId = (int)$membership->get('IDuser');
            if ($memberUserId > 0) {
                $activeMemberUserIds[$memberUserId] = true;
            }
        }

        $selectedHolonIds = $hasStructureApplication ? $selectionState['holon_ids'] : [];
        if (
            $hasStructureApplication
            && $preferDefaultHolonSelection
            && $selectionState['count'] === 0
            && $defaultHolonId > 0
        ) {
            $selectedHolonIds = [$defaultHolonId];
        }

        $selectedUserIds = $selectionState['user_ids'];
        $usesDefaultUserSelection = $selectionState['count'] === 0
            && $defaultUserId > 0
            && isset($activeMemberUserIds[$defaultUserId]);
        if ($usesDefaultUserSelection) {
            $selectedHolonIds = [];
            $selectedUserIds = [$defaultUserId];
        }
        if (!$hasStructureApplication && $selectionState['count'] === 0 && !$usesDefaultUserSelection) {
            $selectedUserIds = [];
            foreach ($memberships as $membership) {
                $userId = (int)$membership->get('IDuser');
                if ($userId > 0) {
                    $selectedUserIds[] = $userId;
                }
            }
        }

        $rootHolon = $hasStructureApplication ? $organization->getEnabledStructuralRootHolon() : null;
        $holonTree = $rootHolon instanceof Holon
            ? omoCalendarBuildInvitationHolonTreeData($rootHolon, $organization, $selectedHolonIds, $targetHolonId)
            : null;

        return [
            'organization' => $organization,
            'effectiveHolon' => $effectiveHolon,
            'organizationId' => $organizationId,
            'targetHolonId' => $targetHolonId,
            'selectedHolonIds' => $selectedHolonIds,
            'selectedUserIds' => $selectedUserIds,
            'selectedEmails' => $selectionState['emails'],
            'hasExplicitInvitations' => $selectionState['count'] > 0,
            'usesDefaultHolonSelection' => $hasStructureApplication && $selectionState['count'] === 0 && $defaultHolonId > 0 && !$usesDefaultUserSelection,
            'usesDefaultUserSelection' => $usesDefaultUserSelection,
            'preferredTab' => $usesDefaultUserSelection ? 'members' : 'holons',
            'holonTree' => $holonTree,
            'hasHolonStructure' => $hasStructureApplication && is_array($holonTree),
            'memberships' => $memberships,
            'defaultHolonId' => $defaultHolonId,
            'defaultUserId' => $usesDefaultUserSelection ? $defaultUserId : 0,
        ];
    }
}

if (!function_exists('omoCalendarPrepareInvitationSelections')) {
    function omoCalendarPrepareInvitationSelections(Organization $organization, int $organizationId, array $selectedHolonIds, array $selectedUserIds, $selectedEmails)
    {
        if ($organizationId <= 0 || (int)$organization->getId() !== $organizationId) {
            return [
                'status' => false,
                'message' => "Contexte d'invitations invalide.",
            ];
        }

        $selectedHolonIds = array_values(array_unique(array_filter(array_map('intval', $selectedHolonIds), static function ($holonId) {
            return $holonId > 0;
        })));
        $selectedUserIds = array_values(array_unique(array_filter(array_map('intval', $selectedUserIds), static function ($userId) {
            return $userId > 0;
        })));
        $selectedEmails = omoCalendarInvitationParseEmails($selectedEmails);

        if (!$organization->isStructureApplicationEnabled()) {
            $selectedHolonIds = [];
        }

        $validHolonLabels = [];
        foreach ($selectedHolonIds as $holonId) {
            $holon = new Holon();
            if (!$holon->load($holonId) || !$organization->containsHolon($holon) || !$holon->canViewDetail()) {
                return [
                    'status' => false,
        'message' => 'Un espace sélectionné est invalide.',
                ];
            }

            $validHolonLabels[$holonId] = trim((string)$holon->getDisplayName());
        }

        $validUserLabels = [];
        foreach ($selectedUserIds as $userId) {
            $membership = new UserOrganization();
            if (
                !$membership->load([
                    ['IDorganization', $organizationId],
                    ['IDuser', $userId],
                ])
                || !(bool)$membership->get('active')
            ) {
                return [
                    'status' => false,
                    'message' => 'Un membre sélectionné est invalide.',
                ];
            }

            $validUserLabels[$userId] = trim((string)$membership->getUserDisplayName());
        }

        $desiredInvitations = [];
        foreach ($selectedHolonIds as $holonId) {
            $desiredInvitations['holon:' . $holonId] = [
                'invitation_type' => ResourceInvitation::TYPE_HOLON,
                'IDholon' => $holonId,
                'display_name' => $validHolonLabels[$holonId] ?? '',
            ];
        }
        foreach ($selectedUserIds as $userId) {
            $desiredInvitations['user:' . $userId] = [
                'invitation_type' => ResourceInvitation::TYPE_USER,
                'IDuser' => $userId,
                'display_name' => $validUserLabels[$userId] ?? '',
            ];
        }
        foreach ($selectedEmails as $email) {
            $desiredInvitations['email:' . $email] = [
                'invitation_type' => ResourceInvitation::TYPE_EMAIL,
                'email' => $email,
                'display_name' => '',
            ];
        }

        return ['status' => true, 'invitations' => $desiredInvitations];
    }
}

if (!function_exists('omoCalendarApplyInvitationSelections')) {
    function omoCalendarApplyInvitationSelections($event, Organization $organization, int $organizationId, array $selectedHolonIds, array $selectedUserIds, $selectedEmails, string $invitationClass = EventInvitation::class, string $resourceField = 'IDevent')
    {
        if ((int)$event->getId() <= 0) {
            return ['status' => false, 'message' => 'Contexte d invitations invalide.'];
        }
        $selection = omoCalendarPrepareInvitationSelections($organization, $organizationId, $selectedHolonIds, $selectedUserIds, $selectedEmails);
        if (!$selection['status']) { return $selection; }
        $desiredInvitations = $selection['invitations'];
        $existingInvitations = [];
        foreach ($event->getInvitations(false) as $invitation) {
            if ($invitation instanceof ResourceInvitation) {
                $existingInvitations[$invitation->getIdentityKey()] = $invitation;
            }
        }
        foreach ($desiredInvitations as $identityKey => $invitationData) {
            $invitation = $existingInvitations[$identityKey] ?? new $invitationClass();
            $existingParameters = $invitation->get('parameters');
            if (!is_array($existingParameters)) {
                $existingParameters = json_decode(trim((string)$existingParameters), true);
            }
            $existingParameters = is_array($existingParameters) ? $existingParameters : [];

            $invitation->set($resourceField, (int)$event->getId());
            $invitation->set('IDholon', isset($invitationData['IDholon']) ? (int)$invitationData['IDholon'] : null);
            $invitation->set('IDuser', isset($invitationData['IDuser']) ? (int)$invitationData['IDuser'] : null);
            $invitation->set('email', trim((string)($invitationData['email'] ?? '')) !== '' ? trim((string)$invitationData['email']) : null);
            $invitation->set('display_name', trim((string)($invitationData['display_name'] ?? '')) !== '' ? trim((string)$invitationData['display_name']) : null);
            $invitation->set('invitation_type', (string)$invitationData['invitation_type']);
            $invitation->set('status', ResourceInvitation::STATUS_INVITED);
            $invitation->set('active', 1);
            $invitation->set('parameters', $existingParameters);

            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => "Impossible d'enregistrer une invitation d'événement.",
                ];
            }
        }

        foreach ($existingInvitations as $identityKey => $invitation) {
            if (isset($desiredInvitations[$identityKey])) {
                continue;
            }

            $invitation->set('active', 0);
            $invitation->set('status', ResourceInvitation::STATUS_REVOKED);
            $saveResult = $invitation->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return [
                    'status' => false,
                    'message' => "Impossible de retirer une ancienne invitation d'événement.",
                ];
            }
        }

        return [
            'status' => true,
            'count' => count($desiredInvitations),
        ];
    }
}

if (!function_exists('omoCalendarBuildInvitationPopupUrl')) {
    function omoCalendarBuildInvitationPopupUrl(int $organizationId, int $targetHolonId, int $eventId): string
    {
        $query = [
            'oid' => $organizationId,
            'id' => $eventId,
        ];
        if ($targetHolonId > 0) {
            $query['cid'] = $targetHolonId;
        }

        return '/omo/api/calendar/invitations_popup.php?' . http_build_query($query);
    }
}

if (!function_exists('omoCalendarBuildInvitationSummaryData')) {
    function omoCalendarBuildInvitationSummaryData(Event $event, array $context, $lang, array $sourceLang)
    {
        $currentHolon = $context['effectiveHolon'] ?? null;
        $eventHolon = null;
        $eventHolonId = (int)$event->get('IDholon');
        if ($eventHolonId > 0) {
            $eventHolon = new Holon();
            if (!$eventHolon->load($eventHolonId)) {
                $eventHolon = null;
            }
        }

        $summaryHolon = $currentHolon instanceof Holon
            ? $currentHolon
            : ($eventHolon instanceof Holon ? $eventHolon : null);
        $summaryHolonId = $summaryHolon instanceof Holon ? (int)$summaryHolon->getId() : 0;

        $data = [
            'popupUrl' => omoCalendarBuildInvitationPopupUrl(
                (int)($context['organizationId'] ?? 0),
                (int)($context['targetHolonId'] ?? 0),
                (int)$event->getId()
            ),
            'invitationCount' => 0,
            'hasExplicitInvitations' => false,
            'summaryItems' => [],
        ];

        $invitations = [];
        foreach ($event->getInvitations(true) as $invitation) {
            if ($invitation instanceof EventInvitation && EventInvitation::normalizeStatus($invitation->get('status')) !== EventInvitation::STATUS_REVOKED) {
                $invitations[] = $invitation;
            }
        }
        $data['invitationCount'] = count($invitations);
        $data['hasExplicitInvitations'] = $data['invitationCount'] > 0;
        $data['counts'] = $event->getInvitationCounts($invitations);
        $peopleSummaryParts = [];
        foreach ([
            'individualMembers' => 'calendar.invitations.individual_members_count',
            'invitedEmails' => 'calendar.invitations.additional_emails',
            'confirmedRegistrations' => 'calendar.invitations.registered_count',
        ] as $countKey => $translationKey) {
            if ($data['counts'][$countKey] > 0) {
                $peopleSummaryParts[] = t($translationKey, ['count' => $data['counts'][$countKey]], $lang, $sourceLang);
            }
        }

        if (count($invitations) === 0) {
            $defaultSummary = '';
            if ($data['counts']['members'] > 0) {
                $defaultSummary = $eventHolon instanceof Holon
                    ? t(
                        $eventHolonId === $summaryHolonId
                            ? 'calendar.invitations.current_holon_people_count'
                            : 'calendar.invitations.holon_people_count',
                        ['name' => trim((string)$eventHolon->getDisplayName()), 'count' => $data['counts']['members']],
                        $lang,
                        $sourceLang
                    )
                    : t('calendar.invitations.default_scope_organization', [], $lang, $sourceLang);
            }

            $data['summaryItems'] = array_values(array_filter(array_merge([$defaultSummary], $peopleSummaryParts)));
            return $data;
        }

        $holonLabels = [];

        foreach ($invitations as $invitation) {
            $type = EventInvitation::normalizeType($invitation->get('invitation_type'));
            if ($type === EventInvitation::TYPE_HOLON) {
                $holonId = (int)$invitation->get('IDholon');

                $holonLabel = trim((string)$invitation->get('display_name'));
                if ($holonLabel === '' && $holonId > 0) {
                    $holon = new Holon();
                    if ($holon->load($holonId)) {
                        $holonLabel = trim((string)$holon->getDisplayName());
                    }
                }
                $memberCount = $data['counts']['holonMemberCounts'][$holonId] ?? 0;
                if ($holonLabel !== '' && $memberCount > 0) {
                    $holonLabels[$holonId] = t(
                        $holonId === $summaryHolonId
                            ? 'calendar.invitations.current_holon_people_count'
                            : 'calendar.invitations.holon_people_count', [
                        'name' => $holonLabel, 'count' => $memberCount,
                    ], $lang, $sourceLang);
                }
                continue;
            }

        }

        $data['summaryItems'] = array_merge(array_values($holonLabels), $peopleSummaryParts);

        return $data;
    }
}

if (!function_exists('omoCalendarRenderInvitationSummarySection')) {
    function omoCalendarRenderInvitationSummarySection(Event $event, array $context, $lang, array $sourceLang, $escape)
    {
        $summaryData = omoCalendarBuildInvitationSummaryData($event, $context, $lang, $sourceLang);
        $canEditInvitations = !array_key_exists('canEditInvitations', $context) || !empty($context['canEditInvitations']);
        $summaryList = '';
        foreach ($summaryData['summaryItems'] as $item) {
            $summaryList .= '<li>' . $escape((string)$item) . '</li>';
        }

        return '<section class="generic-soft-panel generic-soft-panel--stack omo-calendar-detail__content">'
            . '<div class="omo-calendar-detail__summary-head">'
                . '<h3 class="generic-card-title generic-card-title--small">' . $escape(t('calendar.invitations.title', [], $lang, $sourceLang)) . '</h3>'
                . (
                    $canEditInvitations
                        ? '<button'
                            . ' type="button"'
                            . ' class="generic-action-button generic-action-button--secondary"'
                            . ' data-omo-calendar-open-invitations-url="' . $escape((string)$summaryData['popupUrl']) . '"'
                            . ' data-omo-calendar-open-invitations-title="' . $escape(t('calendar.invitations.popup_title', [], $lang, $sourceLang)) . '"'
                        . '>'
                            . $escape(t('calendar.invitations.configure', [], $lang, $sourceLang))
                        . '</button>'
                        : ''
                )
            . '</div>'
            . '<div class="generic-heading-with-help">'
                . '<strong class="generic-meta-value">' . $escape(t('calendar.invitations.people_count', ['count' => $summaryData['counts']['total']], $lang, $sourceLang)) . '</strong>'
                . '<details class="generic-context-help generic-context-help--compact" data-generic-context-help-hover>'
                    . '<summary aria-label="' . $escape(t('calendar.invitations.count_hint', [], $lang, $sourceLang)) . '">?</summary>'
                    . '<div class="generic-context-help__content">' . $escape(t('calendar.invitations.count_hint', [], $lang, $sourceLang)) . '</div>'
                . '</details>'
            . '</div>'
            . ($summaryList !== ''
                ? '<ul class="omo-calendar-detail__summary-copy">' . $summaryList . '</ul>'
                : '')
        . '</section>';
    }
}

if (!function_exists('omoCalendarRenderInvitationEditor')) {
    function omoCalendarRenderInvitationEditor(array $editorState, $lang, array $sourceLang, $escape, array $options = [])
    {
        $options = array_merge([
            'instanceId' => 'omoCalendarInvitationsEditor',
            'holonFieldName' => 'invitation_holon_ids[]',
            'userFieldName' => 'invitation_user_ids[]',
            'emailFieldName' => 'invitation_emails',
            'showFooterHint' => true,
            'publicRegistration' => null,
        ], $options);

        $instanceId = preg_replace('/[^A-Za-z0-9_-]+/', '', (string)$options['instanceId']);
        if ($instanceId === '') {
            $instanceId = 'omoCalendarInvitationsEditor';
        }

        $hasHolonStructure = !empty($editorState['hasHolonStructure']);
        $memberships = $editorState['memberships'];
        $holonTree = $editorState['holonTree'];
        $selectedUserIds = (array)$editorState['selectedUserIds'];
        $selectedEmails = (array)$editorState['selectedEmails'];
        $defaultHolonId = (int)($editorState['defaultHolonId'] ?? 0);
        $defaultUserId = (int)($editorState['defaultUserId'] ?? 0);
        $preferredTab = (string)($editorState['preferredTab'] ?? 'holons');
        $membersTabIsActive = $preferredTab === 'members' || !$hasHolonStructure;
        $holonsTabId = $instanceId . \dbObject\Organization::formatLexiconText('Holons');
        $membersTabId = $instanceId . 'Members';
        $guestsTabId = $instanceId . 'Guests';
        $publicTabId = $instanceId . 'PublicRegistrations';

        ob_start();
        ?>
        <div
            class="omo-calendar-invitations-editor"
            data-omo-calendar-invitations-editor
            data-omo-calendar-default-holon-id="<?= (int)$defaultHolonId ?>"
            data-omo-calendar-default-user-id="<?= (int)$defaultUserId ?>"
            data-omo-calendar-uses-default-selection="<?= !empty($editorState['usesDefaultHolonSelection']) ? '1' : '0' ?>"
        >
            <?php if (!$hasHolonStructure): ?>
            <p class="omo-calendar-invitations-editor__hint"><?= $escape(t('calendar.invitations.no_structure', [], $lang, $sourceLang)) ?></p>
            <?php endif; ?>

            <div class="generic-tabs omo-calendar-invitations-editor__tabs" data-generic-tabs>
                <div class="generic-tabs__list" aria-label="<?= $escape(t('calendar.invitations.tabs_aria', [], $lang, $sourceLang)) ?>">
                    <?php if ($hasHolonStructure): ?>
                    <button type="button" class="generic-tabs__tab<?= $membersTabIsActive ? '' : ' is-active' ?>" data-generic-tab data-generic-tab-target="<?= $escape($holonsTabId) ?>"><?= $escape(t('calendar.invitations.tab.holons', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab<?= $membersTabIsActive ? ' is-active' : '' ?>" data-generic-tab data-generic-tab-target="<?= $escape($membersTabId) ?>"><?= $escape(t('calendar.invitations.tab.members', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($guestsTabId) ?>"><?= $escape(t('calendar.invitations.tab.guests', [], $lang, $sourceLang)) ?></button>
                    <?php else: ?>
                    <button type="button" class="generic-tabs__tab is-active" data-generic-tab data-generic-tab-target="<?= $escape($membersTabId) ?>"><?= $escape(t('calendar.invitations.tab.members', [], $lang, $sourceLang)) ?></button>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($guestsTabId) ?>"><?= $escape(t('calendar.invitations.tab.guests', [], $lang, $sourceLang)) ?></button>
                    <?php endif; ?>
                    <?php if (is_array($options['publicRegistration'])): ?>
                    <button type="button" class="generic-tabs__tab" data-generic-tab data-generic-tab-target="<?= $escape($publicTabId) ?>"><?= $escape(t('calendar.invitations.tab.public', [], $lang, $sourceLang)) ?></button>
                    <?php endif; ?>
                </div>
                <div class="generic-tabs__panels">
                    <?php if ($hasHolonStructure): ?>
                    <div id="<?= $escape($holonsTabId) ?>" class="generic-tabs__panel omo-calendar-invitations-editor__tab-panel" data-generic-tab-panel<?= $membersTabIsActive ? ' hidden' : '' ?>>
                        <strong><?= $escape(t('calendar.invitations.holons_title', [], $lang, $sourceLang)) ?></strong>
                        <p class="omo-calendar-invitations-editor__hint"><?= $escape(t('calendar.invitations.holons_hint', [], $lang, $sourceLang)) ?></p>
                        <input
                            type="search"
                            class="omo-calendar-invitations-editor__filter generic-form-control"
                            data-omo-calendar-holon-filter
                            placeholder="<?= $escape(t('calendar.invitations.holons_filter_placeholder', [], $lang, $sourceLang)) ?>"
                        >
                        <div class="omo-calendar-invitations-editor__checklist" data-omo-calendar-holon-list>
                            <?php if (is_array($holonTree)): ?>
                                <?php omoCalendarRenderInvitationHolonTreeNode($holonTree, $escape, t('calendar.invitations.current', [], $lang, $sourceLang), (string)$options['holonFieldName']); ?>
                            <?php endif; ?>
                        </div>
                        <p class="omo-calendar-invitations-editor__empty generic-description generic-description--small" data-omo-calendar-holon-empty hidden><?= $escape(t('calendar.invitations.filter_empty', [], $lang, $sourceLang)) ?></p>
                    </div>
                    <?php endif; ?>

                    <div id="<?= $escape($membersTabId) ?>" class="generic-tabs__panel omo-calendar-invitations-editor__tab-panel" data-generic-tab-panel<?= $membersTabIsActive ? '' : ' hidden' ?>>
                        <strong><?= $escape(t('calendar.invitations.members_title', [], $lang, $sourceLang)) ?></strong>
                        <input
                            type="search"
                            class="omo-calendar-invitations-editor__filter generic-form-control"
                            data-omo-calendar-member-filter
                            placeholder="<?= $escape(t('calendar.invitations.members_filter_placeholder', [], $lang, $sourceLang)) ?>"
                        >
                        <div class="omo-calendar-invitations-editor__member-list" data-omo-calendar-member-list>
                            <?php foreach ($memberships as $membership): ?>
                                <?php
                                $userId = (int)$membership->get('IDuser');
                                if ($userId <= 0) {
                                    continue;
                                }
                                $displayName = $membership->getUserDisplayName();
                                $secondary = $membership->getScopedEmail() !== '' ? $membership->getScopedEmail() : $membership->getUserSecondaryLabel();
                                ?>
                                <label
                                    class="omo-calendar-invitations-editor__check"
                                    data-omo-calendar-member-item
                                    data-omo-calendar-search-text="<?= $escape(trim((string)$displayName . ' ' . (string)$secondary)) ?>"
                                >
                                    <input
                                        type="checkbox"
                                        name="<?= $escape((string)$options['userFieldName']) ?>"
                                        value="<?= $userId ?>"
                                        <?= in_array($userId, $selectedUserIds, true) ? ' checked' : '' ?>
                                    >
                                    <span class="omo-calendar-invitations-editor__check-meta">
                                        <strong><?= $escape($displayName) ?></strong>
                                        <?php if ($secondary !== ''): ?>
                                        <span class="omo-calendar-invitations-editor__member-email"><?= $escape($secondary) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="omo-calendar-invitations-editor__empty generic-description generic-description--small" data-omo-calendar-member-empty hidden><?= $escape(t('calendar.invitations.filter_empty', [], $lang, $sourceLang)) ?></p>
                        <p class="omo-calendar-invitations-editor__hint">
                            <?= $escape($hasHolonStructure
                                ? t('calendar.invitations.members_hint_structure', [], $lang, $sourceLang)
                                : t('calendar.invitations.members_hint_flat', [], $lang, $sourceLang)) ?>
                        </p>
                    </div>

                    <div id="<?= $escape($guestsTabId) ?>" class="generic-tabs__panel omo-calendar-invitations-editor__tab-panel" data-generic-tab-panel hidden>
                        <label for="<?= $escape($instanceId) ?>Emails"><strong><?= $escape(t('calendar.invitations.guests_title', [], $lang, $sourceLang)) ?></strong></label>
                        <textarea
                            id="<?= $escape($instanceId) ?>Emails"
                            name="<?= $escape((string)$options['emailFieldName']) ?>"
                            class="omo-calendar-invitations-editor__textarea generic-form-control"
                            placeholder="<?= $escape(t('calendar.invitations.guests_placeholder', [], $lang, $sourceLang)) ?>"
                        ><?= $escape(implode("\n", $selectedEmails)) ?></textarea>
                        <p class="omo-calendar-invitations-editor__hint"><?= $escape(t('calendar.invitations.guests_hint', [], $lang, $sourceLang)) ?></p>
                    </div>

                    <?php if (is_array($options['publicRegistration'])): ?>
                    <div id="<?= $escape($publicTabId) ?>" class="generic-tabs__panel omo-calendar-invitations-editor__tab-panel" data-generic-tab-panel hidden>
                        <label class="omo-calendar-invitations-editor__check">
                            <input type="checkbox" name="public_registration" value="1" data-omo-public-registration-toggle<?= !empty($options['publicRegistration']['enabled']) ? ' checked' : '' ?>>
                            <span class="omo-calendar-invitations-editor__check-meta">
                                <strong><?= $escape(t('calendar.invitations.public_enable', [], $lang, $sourceLang)) ?></strong>
                                <span class="omo-calendar-invitations-editor__member-email"><?= $escape(t('calendar.invitations.public_hint', [], $lang, $sourceLang)) ?></span>
                            </span>
                        </label>
                        <label class="generic-form-field" data-omo-public-registration-link<?= empty($options['publicRegistration']['url']) ? ' hidden' : '' ?>>
                            <span><?= $escape(t('calendar.invitations.public_link', [], $lang, $sourceLang)) ?></span>
                            <input class="generic-form-control" type="url" readonly value="<?= $escape((string)($options['publicRegistration']['url'] ?? '')) ?>" data-omo-public-registration-url>
                        </label>
                        <section class="generic-section generic-section--stack" aria-labelledby="<?= $escape($instanceId) ?>Registrations">
                            <h4 class="generic-card-title generic-card-title--small" id="<?= $escape($instanceId) ?>Registrations"><?= $escape(t('calendar.invitations.registrations_title', [], $lang, $sourceLang)) ?></h4>
                            <?php if (empty($options['publicRegistration']['people'])): ?>
                                <p class="generic-help-text"><?= $escape(t('calendar.invitations.registrations_empty', [], $lang, $sourceLang)) ?></p>
                            <?php else: ?>
                                <div class="generic-stack" role="list">
                                    <?php foreach ($options['publicRegistration']['people'] as $person): ?>
                                    <div class="generic-soft-panel generic-soft-panel--stack" role="listitem">
                                        <strong><?= $escape($person->get('name')) ?></strong>
                                        <span class="generic-description generic-description--small"><?= $escape($person->get('email')) ?></span>
                                        <span class="generic-description generic-description--small"><?= $escape(t($person->get('confirmed_at') ? 'calendar.invitations.registration_confirmed' : 'calendar.invitations.registration_pending', [], $lang, $sourceLang)) ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($options['showFooterHint'])): ?>
            <p class="omo-calendar-invitations-editor__hint"><?= $escape(t('calendar.invitations.summary_intro', [], $lang, $sourceLang)) ?></p>
            <?php endif; ?>
        </div>
        <?php

        return (string)ob_get_clean();
    }
}
