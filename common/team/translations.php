<?php

if (!function_exists('omoTeamTranslationBundleKey')) {
    function omoTeamTranslationBundleKey(): string
    {
        return 'omo_team_module';
    }
}

if (!function_exists('omoTeamSourceLang')) {
    function omoTeamSourceLang(): array
    {
        static $sourceLang = null;

        if (is_array($sourceLang)) {
            return $sourceLang;
        }

        $sourceLang = [
            'team.title' => ['text' => 'Team', 'context' => 'Title of the team app drawer in OMO.'],
            'team.scope.members_aria' => ['text' => 'Portée des membres', 'context' => 'Accessible label of the team scope switcher.'],
            'team.scope.contextual' => ['text' => 'Contextuel', 'context' => 'Label of the contextual scope in the team module.'],
            'team.scope.children' => ['text' => 'Enfants directs', 'context' => 'Label of the current holon and direct child scope in the team module.'],
            'team.scope.descendants' => ['text' => 'Descendants', 'context' => 'Label of the descendants scope in the team module.'],
            'team.view.choice_aria' => ['text' => 'Choix de la vue', 'context' => 'Accessible label of the team view switcher.'],
            'team.view.cards' => ['text' => 'Fiches', 'context' => 'Member profile cards view label in the team module.'],
            'team.view.compact' => ['text' => 'Compact', 'context' => 'Compact view label in the team module.'],
            'team.view.map' => ['text' => 'Carte géo', 'context' => 'Map view label in the team module.'],
            'team.filters.aria' => ['text' => 'Filtres de l’équipe', 'context' => 'Accessible label for the compact Team filters control.'],
            'team.filters.scope' => ['text' => 'Contexte', 'context' => 'Heading for the Team scope choices in the filters panel.'],
            'team.filters.view' => ['text' => 'Représentation', 'context' => 'Heading for the Team representation choices in the filters panel.'],
            'team.filters.apply' => ['text' => 'Appliquer', 'context' => 'Button applying temporary Team filter choices without saving them.'],
            'team.filters.save_view' => ['text' => 'Enregistrer la vue', 'context' => 'Button applying and saving Team filter choices for the current context.'],
            'team.filters.more_actions' => ['text' => 'Autres options de vue', 'context' => 'Accessible label for additional Team view preference actions.'],
            'team.filters.apply_everywhere' => ['text' => 'Appliquer partout', 'context' => 'Action setting the current Team view as the default and clearing specific views.'],
            'team.filters.set_default' => ['text' => 'Définir comme vue par défaut', 'context' => 'Action saving the current Team view as the default view.'],
            'team.filters.restore_default' => ['text' => 'Restaurer la vue par défaut', 'context' => 'Action removing the current holon specific Team view.'],
            'team.search.aria' => ['text' => 'Filtrer les membres affichés', 'context' => 'Accessible label for the Team quick search input.'],
            'team.search.placeholder' => ['text' => 'Filtrer les membres', 'context' => 'Placeholder for the Team quick search input.'],
            'team.search.empty' => ['text' => 'Aucun membre ne correspond à cette recherche.', 'context' => 'Empty state when Team quick search hides all members in the active representation.'],
            'team.action.add_member' => ['text' => 'Ajouter un membre', 'context' => 'Primary action button used to add a member in the team module.'],
            'team.action.cancel_invitation' => ['text' => "Annuler l'invitation", 'context' => 'Menu action used to cancel a pending invitation in the team module.'],
            'team.action.remove_from_context' => ['text' => 'Retirer du contexte {context}', 'context' => 'Menu action used to remove a member from a context in the team module.'],
            'team.action.grant_context_admin' => ['text' => 'Définir comme {adminLabel} du contexte {context}', 'context' => 'Menu action used to grant context admin rights in the team module.'],
            'team.action.revoke_context_admin' => ['text' => 'Retirer le statut {adminLabel} du contexte {context}', 'context' => 'Menu action used to revoke context admin rights in the team module.'],
            'team.confirm.cancel_invitation' => ['text' => "Annuler l'invitation envoyée à {name} ?", 'context' => 'Confirmation message shown before canceling a pending invitation in the team module.'],
            'team.confirm.remove' => ['text' => 'Retirer {name} du contexte {context} ?', 'context' => 'Confirmation message shown before removing a member from a context in the team module.'],
            'team.confirm.grant_context_admin' => ['text' => 'Définir {name} comme {adminLabel} du contexte {context} ?', 'context' => 'Confirmation message shown before granting context admin rights in the team module.'],
            'team.confirm.revoke_context_admin' => ['text' => 'Retirer le statut {adminLabel} de {name} pour le contexte {context} ?', 'context' => 'Confirmation message shown before revoking context admin rights in the team module.'],
            'team.message.update_failed' => ['text' => 'Impossible de mettre à jour ce membre.', 'context' => 'Fallback error message shown when a team member update fails.'],
            'team.message.update_failed_later' => ['text' => 'Impossible de mettre à jour ce membre pour le moment.', 'context' => 'Fallback error message shown when a team member update fails due to a request error.'],
            'team.message.update_success' => ['text' => 'Mise à jour effectuée.', 'context' => 'Fallback success message shown after a team member update succeeds.'],
            'team.empty.contextual' => ['text' => "Aucune personne n'est encore liée à ce {context_type}.", 'context' => 'Empty state shown in the team module for the contextual scope.'],
            'team.empty.children' => ['text' => "Aucune personne n'est encore liée à ce contexte ou à ses enfants directs.", 'context' => 'Empty state shown in the team module for the direct child scope.'],
            'team.empty.descendants' => ['text' => "Aucune personne n'est encore liée à ce contexte et à ses descendants.", 'context' => 'Empty state shown in the team module for the descendants scope.'],
            'team.map.empty.contextual' => ['text' => "Aucun membre n'a encore de position géographique dans ce contexte.", 'context' => 'Map empty state shown in the team module for the contextual scope.'],
            'team.map.empty.children' => ['text' => "Aucun membre n'a encore de position géographique dans ce contexte ou ses enfants directs.", 'context' => 'Map empty state shown in the team module for the direct child scope.'],
            'team.map.empty.descendants' => ['text' => "Aucun membre n'a encore de position géographique dans ce contexte et ses descendants.", 'context' => 'Map empty state shown in the team module for the descendants scope.'],
            'team.map.summary' => ['one' => '{count} membre géolocalisé.', 'other' => '{count} membres géolocalisés.', 'context' => 'Summary shown above the team map with the number of geolocated members.'],
            'team.map.summary_one' => ['text' => '1 membre géolocalisé.', 'context' => 'Singular summary shown above the filtered team map.'],
            'team.map.summary_other' => ['text' => '{count} membres géolocalisés.', 'context' => 'Plural summary shown above the filtered team map.'],
            'team.map.open_profile' => ['text' => 'Ouvrir la fiche', 'context' => 'Button label used in the team map popup to open the member profile.'],
            'team.error.invalid_organization' => ['text' => 'Organisation invalide.', 'context' => 'Error shown in the team module when no valid organization identifier is available.'],
            'team.error.people_forbidden' => ['text' => 'Accès refusé à la liste des personnes.', 'context' => 'Error shown in the team module when the current share link cannot access the people list.'],
            'team.member.user_fallback' => ['text' => 'Utilisateur {userId}', 'context' => 'Fallback display name used in the team module when a user has no visible name.'],
            'team.member.this_member' => ['text' => 'ce membre', 'context' => 'Fallback label used in confirmations when a team member display name is missing.'],
            'team.member.pending' => ['text' => 'En attente', 'context' => 'Badge shown for a pending member in the team module.'],
            'team.member.invitation_pending' => ['text' => 'Invitation envoyée', 'context' => 'Badge shown when an invitation email is pending for a member.'],
            'team.member.to_invite' => ['text' => 'À inviter', 'context' => 'Badge shown for an imported member who has not received an invitation yet.'],
            'team.member.admin_short' => ['text' => '{adminLabel}', 'context' => 'Short admin badge shown on member cards in the team module.'],
            'team.member.admin_context' => ['text' => '{adminLabel} du contexte', 'context' => 'Badge shown for a context admin in the team module.'],
            'team.member.admin_organization' => ['text' => "{adminLabel} de l'organisation", 'context' => 'Badge shown for an organization admin in the team module.'],
            'team.member.email' => ['text' => 'E-mail', 'context' => 'Email field label in the team module.'],
            'team.member.not_provided' => ['text' => 'Non renseigné', 'context' => 'Fallback value used when a member field is missing in the team module.'],
            'team.member.added' => ['text' => 'Ajout', 'context' => 'Label of the member organization link creation date in the team module.'],
            'team.member.organization_connection' => ['text' => "Connexion à l'org", 'context' => 'Label of the member last connection date in the current organization.'],
            'team.member.site_connection' => ['text' => 'Connexion au site', 'context' => 'Label of the member last connection date on the site.'],
            'team.member.last_connection' => ['text' => 'Connexion', 'context' => 'Label of the member combined last connection date in the team map.'],
            'team.member.created' => ['text' => 'Création', 'context' => 'Label of the user account creation date in the team module.'],
            'team.member.never' => ['text' => 'Jamais', 'context' => 'Fallback value used when a member never connected in the team module.'],
            'team.member.open_contextual_profile' => ['text' => 'Ouvrir le profil contextuel de {name}', 'context' => 'Accessible label used to open the member contextual profile from the team module.'],
            'team.member.actions_for' => ['text' => 'Actions pour {name}', 'context' => 'Accessible label used for the team member action menu button.'],
            'team.member.last_seen_global' => ['text' => '{organization} (générale : {global})', 'context' => 'Composite label used when both organization and global last-seen dates are available in the team module.'],
            'team.column.name' => ['text' => 'Nom', 'context' => 'Surname column label in the compact team view.'],
            'team.column.first_name' => ['text' => 'Prénom', 'context' => 'First name column label in the compact team view.'],
            'team.column.phone' => ['text' => 'Téléphone', 'context' => 'Phone column label in the compact team view.'],
            'team.column.identity' => ['text' => 'Identité', 'context' => 'Identity field label in the compact team view.'],
            'team.popup.invalid_member_context' => ['text' => 'Contexte membre invalide.', 'context' => 'Error shown when the team member popup is opened without a valid organization or user context.'],
            'team.popup.organization_not_found' => ['text' => 'Organisation introuvable.', 'context' => 'Error shown in the team member popup when the organization cannot be loaded.'],
            'team.popup.organization_forbidden' => ['text' => 'Accès refusé à cette organisation.', 'context' => 'Error shown in the team member popup when the organization cannot be viewed.'],
            'team.popup.organization_context_missing' => ['text' => "Aucun contexte organisationnel n'est disponible.", 'context' => 'Error shown in the team member popup when the organization has no structural root context.'],
            'team.popup.context_not_found' => ['text' => 'Contexte introuvable pour cette organisation.', 'context' => 'Error shown in the team member popup when the context cannot be loaded.'],
            'team.popup.context_forbidden' => ['text' => 'Accès refusé à ce contexte.', 'context' => 'Error shown in the team member popup when the context cannot be viewed.'],
            'team.popup.user_not_found' => ['text' => 'Utilisateur introuvable.', 'context' => 'Error shown in the team member popup when the user cannot be loaded.'],
            'team.popup.user_forbidden' => ['text' => 'Accès refusé à cet utilisateur.', 'context' => 'Error shown in the team member popup when the user cannot be viewed.'],
            'team.popup.contextual_actions' => ['text' => 'Actions contextuelles', 'context' => 'Eyebrow title shown in the team member popup.'],
            'team.popup.context_prefix' => ['text' => 'Contexte', 'context' => 'Prefix shown before the current context in the team member popup.'],
            'team.popup.member_management' => ['text' => 'Gestion du membre', 'context' => 'Section title shown in the team member popup.'],
            'team.popup.no_manage_rights' => ['text' => "Vous n’avez pas les droits pour modifier ce {context}.", 'context' => 'Message shown in the team member popup when the current user cannot manage members in the current context.'],
            'team.popup.choose_action' => ['text' => 'Choisissez l’action à appliquer dans ce {context}.', 'context' => 'Helper text shown in the team member popup above the member action buttons.'],
            'team.api.invalid_action' => ['text' => 'Action membre invalide.', 'context' => 'JSON error message returned by the team member action endpoint when the payload is invalid.'],
            'team.api.context_not_found' => ['text' => 'Contexte introuvable.', 'context' => 'JSON error message returned by the team member action endpoint when the context cannot be loaded.'],
            'team.api.no_right_modify_context' => ['text' => "Vous n'avez pas le droit de modifier ce contexte.", 'context' => 'JSON error message returned by the team member action endpoint when the current user cannot modify the current context.'],
            'team.api.no_right_manage_admin' => ['text' => "Vous n'avez pas le droit de gérer le statut {adminLabel} dans ce contexte.", 'context' => 'JSON error message returned by the team member action endpoint when the current user cannot manage admin rights in the current context.'],
            'team.api.no_right_add_member' => ['text' => "Vous n'avez pas le droit d'ajouter un membre dans ce contexte.", 'context' => 'JSON error message returned by the team member action endpoint when the current user cannot add a member in the current context.'],
            'team.api.pending_invitation_not_found' => ['text' => "Aucune invitation en attente n'a été trouvée pour cette personne.", 'context' => 'JSON error message returned by the team member action endpoint when no pending invitation is found for the target user.'],
            'team.api.pending_admin_invitation_not_found' => ['text' => "Aucune invitation {adminLabel} en attente n'a été trouvée pour cette personne.", 'context' => 'JSON error message returned by the team member action endpoint when no pending admin invitation is found for the target user.'],
            'team.api.invitation_resent' => ['text' => 'Invitation renvoyée.', 'context' => 'JSON success message returned by the team member action endpoint when a pending invitation is resent.'],
            'team.api.invitation_resend_failed' => ['text' => "L'invitation n'a pas pu être renvoyée.", 'context' => 'JSON error message returned by the team member action endpoint when an invitation resend fails without a more specific error.'],
            'team.api.invitation_sent' => ['text' => 'Invitation envoyée.', 'context' => 'JSON success message returned by the team member action endpoint when an invitation is sent for the first time.'],
            'team.api.member_not_ready_for_invitation' => ['text' => 'Cette personne n attend pas d invitation.', 'context' => 'JSON error message returned when an invitation cannot be created for the selected member.'],
            'team.api.invitation_send_failed' => ['text' => "L'invitation n'a pas pu être envoyée.", 'context' => 'JSON error message returned when an invitation email cannot be sent.'],
            'team.api.unknown_action' => ['text' => 'Action inconnue.', 'context' => 'JSON error message returned by the team member action endpoint when the requested action is unknown.'],
            'team.api.action_completed' => ['text' => 'Action terminée.', 'context' => 'Fallback JSON message returned by the team member action endpoint when a member action completed without a specific message.'],
            'team.holon_type.organization' => ['text' => 'organisation', 'context' => 'Fallback holon type label used in the team module for an organization.'],
            'team.holon_type.group' => ['text' => 'groupe', 'context' => 'Fallback holon type label used in the team module for a group.'],
            'team.holon_type.circle' => ['text' => 'cercle', 'context' => 'Fallback holon type label used in the team module for a circle.'],
            'team.holon_type.role' => ['text' => 'rôle', 'context' => 'Fallback holon type label used in the team module for a role.'],
            'team.holon_type.holon' => ['text' => 'holon', 'context' => 'Fallback holon type label used in the team module when no specific type matches.'],
            'team.holon_type.context' => ['text' => 'contexte', 'context' => 'Fallback holon type label used in the team module for a generic context.'],
        ];

        return $sourceLang;
    }
}

if (!function_exists('omoTeamLoadTranslationBundle')) {
    function omoTeamLoadTranslationBundle(): array
    {
        static $bundle = null;

        if (is_array($bundle)) {
            return $bundle;
        }

        $bundle = omoLoadTranslationBundle(omoTeamTranslationBundleKey(), omoTeamSourceLang());
        return $bundle;
    }
}

if (!function_exists('omoTeamT')) {
    function omoTeamT(string $key, array $variables = [], ?array $bundle = null, ?array $sourceLang = null): string
    {
        $sourceLang = $sourceLang ?? omoTeamSourceLang();
        $bundle = $bundle ?? omoTeamLoadTranslationBundle();

        return t($key, $variables, $bundle, $sourceLang);
    }
}

if (!function_exists('omoTeamHolonTypeLabelByTypeId')) {
    function omoTeamHolonTypeLabelByTypeId(int $typeId, ?array $bundle = null, ?array $sourceLang = null): string
    {
        switch ($typeId) {
            case 4:
                return omoTeamT('team.holon_type.organization', [], $bundle, $sourceLang);
            case 3:
                return omoTeamT('team.holon_type.group', [], $bundle, $sourceLang);
            case 2:
                return omoTeamT('team.holon_type.circle', [], $bundle, $sourceLang);
            case 1:
                return omoTeamT('team.holon_type.role', [], $bundle, $sourceLang);
            default:
                return omoTeamT('team.holon_type.holon', [], $bundle, $sourceLang);
        }
    }
}

?>
