<?php

require_once dirname(__DIR__) . '/omo/translations.php';

function profilPopupGetSourceLang(): array
{
    static $sourceLang = null;
    if ($sourceLang !== null) {
        return $sourceLang;
    }

    $sourceLang = [
        'profile.popup.error.login_required' => [
            'text' => 'Connexion requise',
            'context' => 'Error message shown when the profile popup is opened without an authenticated user.',
        ],
        'profile.popup.error.unknown_user' => [
            'text' => 'Utilisateur inconnu',
            'context' => 'Error message shown when the current user record cannot be loaded for the profile popup.',
        ],
        'profile.popup.section.active.title' => [
            'text' => 'Votre profil actif',
            'context' => 'Section title shown at the top of the personal profile popup.',
        ],
        'profile.popup.section.edit.title' => [
            'text' => 'Modifier votre profil',
            'context' => 'Section title shown above the editable profile forms.',
        ],
        'profile.popup.tabs.aria' => [
            'text' => 'Sections du profil personnel',
            'context' => 'Aria label used for the profile editor tabs.',
        ],
        'profile.popup.tabs.current' => [
            'text' => 'Prévisualisation',
            'context' => 'Tab label used for the active profile preview.',
        ],
        'profile.popup.tabs.organization' => [
            'text' => 'Profil specifique',
            'context' => 'Tab label used for the organization-specific profile form.',
        ],
        'profile.popup.tabs.general' => [
            'text' => 'Profil general',
            'context' => 'Tab label used for the general profile form.',
        ],
        'profile.popup.tabs.competences' => [
            'text' => 'Competences',
            'context' => 'Tab label used for the competence editor tab.',
        ],
        'profile.popup.section.patreon.title' => [
            'text' => 'Patreon',
            'context' => 'Section title shown for the Patreon connection block in the profile popup.',
        ],
        'profile.popup.active.context.label' => [
            'text' => 'Contexte',
            'context' => 'Label shown for the active profile context summary item.',
        ],
        'profile.popup.active.context.organization' => [
            'text' => 'Organisation : {organizationName}',
            'context' => 'Summary value shown when the active profile context is scoped to an organization.',
        ],
        'profile.popup.active.context.general' => [
            'text' => 'Profil général',
            'context' => 'Summary value shown when the active profile context is the general user profile.',
        ],
        'profile.popup.active.photo.label' => [
            'text' => 'Photo affichée',
            'context' => 'Label shown for the active profile photo summary item.',
        ],
        'profile.popup.active.email.label' => [
            'text' => 'E-mail affiché',
            'context' => 'Label shown for the active profile email summary item.',
        ],
        'profile.popup.active.username.label' => [
            'text' => 'Identifiant affiché',
            'context' => 'Label shown for the active profile username summary item.',
        ],
        'profile.popup.active.fullname.label' => [
            'text' => 'Nom complet',
            'context' => 'Label shown for the active full name summary item.',
        ],
        'profile.popup.active.presentation.label' => [
            'text' => 'Présentation active',
            'context' => 'Label shown for the active profile presentation summary item.',
        ],
        'profile.popup.active.birthdate.label' => [
            'text' => 'Date de naissance',
            'context' => 'Label shown for the birth date summary item.',
        ],
        'profile.popup.active.birthday.label' => [
            'text' => 'Anniversaire',
            'context' => 'Label shown for the birthday summary item.',
        ],
        'profile.popup.value.not_provided' => [
            'text' => 'Non renseigné',
            'context' => 'Fallback text shown in the profile popup when a value is missing.',
        ],
        'profile.popup.value.no_presentation' => [
            'text' => 'Aucune présentation renseignée',
            'context' => 'Fallback text shown when the user has no active presentation text.',
        ],
        'profile.popup.scope.switch_aria' => [
            'text' => 'Choix du contexte de profil',
            'context' => 'Aria label used for the profile scope switch buttons.',
        ],
        'profile.popup.scope.general_button' => [
            'text' => 'Données générales',
            'context' => 'Button label used to switch to the general profile form.',
        ],
        'profile.popup.scope.organization_button' => [
            'text' => "Données spécifiques à l'organisation",
            'context' => 'Button label used to switch to the organization-specific profile form.',
        ],
        'profile.popup.scope.help' => [
            'text' => 'Vous pouvez compléter votre profil général, puis définir si besoin une présentation différente pour cette organisation.',
            'context' => 'Help text shown below the profile scope switch.',
        ],
        'profile.popup.scope.loading' => [
            'text' => 'Chargement du formulaire...',
            'context' => 'Status text shown while a profile scope fragment is loading.',
        ],
        'profile.popup.scope.load_error' => [
            'text' => 'Impossible de charger ce formulaire pour le moment.',
            'context' => 'Error message shown when the profile scope fragment fails to load.',
        ],
        'profile.popup.patreon.connection.label' => [
            'text' => 'Connexion',
            'context' => 'Label shown for the Patreon connection summary item.',
        ],
        'profile.popup.patreon.connection.connected' => [
            'text' => 'Compte Patreon connecté',
            'context' => 'Summary value shown when a Patreon account is linked to the current user.',
        ],
        'profile.popup.patreon.connection.disconnected' => [
            'text' => 'Aucun compte Patreon connecté',
            'context' => 'Summary value shown when no Patreon account is linked to the current user.',
        ],
        'profile.popup.patreon.name.label' => [
            'text' => 'Nom Patreon',
            'context' => 'Label shown for the Patreon full name summary item.',
        ],
        'profile.popup.patreon.status.label' => [
            'text' => "Statut d'abonnement",
            'context' => 'Label shown for the Patreon subscription status summary item.',
        ],
        'profile.popup.patreon.payment.label' => [
            'text' => 'Dernier paiement',
            'context' => 'Label shown for the last Patreon payment summary item.',
        ],
        'profile.popup.patreon.tiers.label' => [
            'text' => 'Paliers actifs',
            'context' => 'Label shown for the active Patreon tiers summary item.',
        ],
        'profile.popup.patreon.amount.label' => [
            'text' => 'Montant actif',
            'context' => 'Label shown for the current Patreon amount summary item.',
        ],
        'profile.popup.patreon.sync_at.label' => [
            'text' => 'Dernière synchronisation',
            'context' => 'Label shown for the last Patreon synchronization date summary item.',
        ],
        'profile.popup.patreon.sync_error.label' => [
            'text' => 'Dernière erreur',
            'context' => 'Label shown for the last Patreon synchronization error summary item.',
        ],
        'profile.popup.patreon.tiers.none' => [
            'text' => 'Aucun',
            'context' => 'Fallback text shown when the Patreon account has no active tier title.',
        ],
        'profile.popup.patreon.connect' => [
            'text' => 'Connecter Patreon',
            'context' => 'Primary button label shown when the Patreon account is not connected yet.',
        ],
        'profile.popup.patreon.reconnect' => [
            'text' => 'Reconnecter Patreon',
            'context' => 'Primary button label shown when the Patreon account is already connected.',
        ],
        'profile.popup.patreon.sync' => [
            'text' => 'Rafraîchir maintenant',
            'context' => 'Secondary button label used to refresh Patreon data on demand.',
        ],
        'profile.popup.patreon.disconnect' => [
            'text' => 'Déconnecter',
            'context' => 'Secondary button label used to disconnect Patreon from the current user.',
        ],
        'profile.popup.js.invalid_response' => [
            'text' => 'Réponse serveur invalide.',
            'context' => 'Fallback JavaScript error message shown when an AJAX endpoint returns invalid JSON.',
        ],
        'profile.popup.js.disconnect_confirm' => [
            'text' => 'Déconnecter le compte Patreon de ce profil ?',
            'context' => 'Confirmation dialog shown before disconnecting Patreon from the current profile.',
        ],
        'profile.popup.scope.organization_intro' => [
            'text' => "Ces données remplacent le profil général uniquement dans cette organisation. Laissez un champ vide pour continuer à utiliser la valeur générale.",
            'context' => 'Help text shown above the organization-specific profile form.',
        ],
        'profile.popup.scope.organization_submit' => [
            'text' => "Mettre à jour les données de l'organisation",
            'context' => 'Button label used to submit the organization-specific profile form.',
        ],
        'profile.popup.scope.general_submit' => [
            'text' => 'Mettre à jour les données générales',
            'context' => 'Button label used to submit the general profile form.',
        ],
        'profile.popup.password.section.title' => [
            'text' => 'Mot de passe',
            'context' => 'Section title shown above the password block inside the general profile editor.',
        ],
        'profile.popup.password.section.help' => [
            'text' => "Ajoutez un mot de passe pour utiliser la connexion classique et CardDAV. Lors d'une modification, l'ancien mot de passe est requis.",
            'context' => 'Help text shown above the password fields in the general profile editor.',
        ],
        'profile.popup.password.toggle.label' => [
            'text' => 'Modifier le mot de passe',
            'context' => 'Checkbox label used to reveal the password update form in the profile popup.',
        ],
        'profile.popup.password.toggle.help' => [
            'text' => 'Cochez cette case uniquement si vous souhaitez definir ou changer le mot de passe de ce compte.',
            'context' => 'Help text shown next to the password reveal checkbox in the profile popup.',
        ],
        'profile.popup.password.status.missing' => [
            'text' => 'Aucun mot de passe défini pour ce compte.',
            'context' => 'Status text shown when the current user account has no password configured yet.',
        ],
        'profile.popup.password.status.defined' => [
            'text' => 'Un mot de passe est déjà défini pour ce compte.',
            'context' => 'Status text shown when the current user account already has a password configured.',
        ],
        'profile.popup.password.current.label' => [
            'text' => 'Ancien mot de passe',
            'context' => 'Label shown for the current password field when changing an existing password.',
        ],
        'profile.popup.password.current.placeholder' => [
            'text' => 'Entrez votre mot de passe actuel',
            'context' => 'Placeholder shown in the current password field.',
        ],
        'profile.popup.password.new.label' => [
            'text' => 'Nouveau mot de passe',
            'context' => 'Label shown for the new password field.',
        ],
        'profile.popup.password.new.placeholder' => [
            'text' => 'Au moins 12 caractères',
            'context' => 'Placeholder shown in the new password field.',
        ],
        'profile.popup.password.confirm.label' => [
            'text' => 'Confirmation du nouveau mot de passe',
            'context' => 'Label shown for the new password confirmation field.',
        ],
        'profile.popup.password.confirm.placeholder' => [
            'text' => 'Retapez le nouveau mot de passe',
            'context' => 'Placeholder shown in the new password confirmation field.',
        ],
        'profile.popup.password.no_paste' => [
            'text' => 'Le copier-coller est désactivé sur ces champs pour vérifier la saisie.',
            'context' => 'Helper text shown below the password fields to explain why copy paste is blocked.',
        ],
        'profile.popup.password.js.paste_blocked' => [
            'text' => 'Le copier-coller est bloqué sur ces champs.',
            'context' => 'Alert message shown when the user tries to paste, copy, cut, or drop content in password fields.',
        ],
        'profile.popup.password.policy.status.empty' => [
            'text' => 'Le mot de passe doit respecter les critères ci-dessous.',
            'context' => 'Initial password policy hint shown in the profile popup.',
        ],
        'profile.popup.password.policy.status.valid' => [
            'text' => 'Mot de passe OK.',
            'context' => 'Success password policy hint shown in the profile popup.',
        ],
        'profile.popup.password.policy.status.invalid' => [
            'text' => 'Mot de passe encore incomplet.',
            'context' => 'Error password policy hint shown in the profile popup.',
        ],
        'profile.popup.password.policy.match.empty' => [
            'text' => 'Retapez le meme mot de passe pour confirmation.',
            'context' => 'Initial password confirmation hint shown in the profile popup.',
        ],
        'profile.popup.password.policy.match.valid' => [
            'text' => 'Confirmation OK.',
            'context' => 'Success password confirmation hint shown in the profile popup.',
        ],
        'profile.popup.password.policy.match.invalid' => [
            'text' => 'La confirmation ne correspond pas encore.',
            'context' => 'Error password confirmation hint shown in the profile popup.',
        ],
        'profile.popup.password.policy.rule.length' => [
            'text' => 'Au moins 12 caractères',
            'context' => 'Password policy rule shown in the profile popup.',
        ],
        'profile.popup.password.policy.rule.lower' => [
            'text' => 'Au moins une minuscule',
            'context' => 'Password policy rule shown in the profile popup.',
        ],
        'profile.popup.password.policy.rule.upper' => [
            'text' => 'Au moins une majuscule',
            'context' => 'Password policy rule shown in the profile popup.',
        ],
        'profile.popup.password.policy.rule.digit' => [
            'text' => 'Au moins un chiffre',
            'context' => 'Password policy rule shown in the profile popup.',
        ],
        'profile.popup.password.policy.rule.special' => [
            'text' => 'Au moins un caractère spécial ou un espace',
            'context' => 'Password policy rule shown in the profile popup.',
        ],
        'profile.popup.password.policy.rule.email' => [
            'text' => "Évitez de reprendre votre e-mail ou votre identifiant",
            'context' => 'Advisory password policy rule shown in the profile popup.',
        ],
        'profile.popup.scope.jquery_required' => [
            'text' => 'jQuery est requis pour ce formulaire.',
            'context' => 'Alert message shown when the adminEdit profile form is missing its expected jQuery runtime.',
        ],
        'profile.popup.competence.section.organization_title' => [
            'text' => "Compétences liées à l'organisation",
            'context' => 'Section title shown above the organization-specific competence editor list.',
        ],
        'profile.popup.competence.section.general_title' => [
            'text' => 'Compétences générales',
            'context' => 'Section title shown above the general competence editor list.',
        ],
        'profile.popup.competence.section.organization_help' => [
            'text' => "Ajoutez ici les compétences du contexte actif. Cochez la case pour les limiter à cette organisation.",
            'context' => 'Help text shown above the organization-specific competence editor list.',
        ],
        'profile.popup.competence.section.general_help' => [
            'text' => 'Ajoutez ici les compétences visibles dans toutes vos organisations, ou limitez-les au contexte actif.',
            'context' => 'Help text shown above the general competence editor list.',
        ],
        'profile.popup.competence.empty' => [
            'text' => 'Aucune compétence pour ce contexte.',
            'context' => 'Message shown when there are no competences to edit for the current scope.',
        ],
        'profile.popup.competence.field.name' => [
            'text' => 'Compétence',
            'context' => 'Label shown for the competence name field.',
        ],
        'profile.popup.competence.field.new_name' => [
            'text' => 'Nouvelle compétence',
            'context' => 'Label shown for the new competence name field.',
        ],
        'profile.popup.competence.field.description' => [
            'text' => 'Descriptif',
            'context' => 'Label shown for the competence description field.',
        ],
        'profile.popup.competence.field.description_placeholder' => [
            'text' => 'Ex: PHP / MySQL',
            'context' => 'Placeholder shown for the competence description field.',
        ],
        'profile.popup.competence.field.category' => [
            'text' => 'Type',
            'context' => 'Label shown for the competence category field.',
        ],
        'profile.popup.competence.field.level' => [
            'text' => 'Votre niveau',
            'context' => 'Label shown for the competence level field.',
        ],
        'profile.popup.competence.validation_count' => [
            'one' => '{count} validation',
            'other' => '{count} validations',
            'context' => 'Badge text shown with the number of validations received for a competence.',
        ],
        'profile.popup.competence.validators_label' => [
            'text' => 'Reconnu par',
            'context' => 'Label shown above the list of validators for a competence.',
        ],
        'profile.popup.competence.save' => [
            'text' => 'Enregistrer',
            'context' => 'Button label used to save an existing competence.',
        ],
        'profile.popup.competence.delete' => [
            'text' => 'Supprimer',
            'context' => 'Button label used to delete an existing competence.',
        ],
        'profile.popup.competence.add' => [
            'text' => 'Ajouter',
            'context' => 'Button label used to create a new competence.',
        ],
        'profile.popup.competence.edit' => [
            'text' => 'Editer',
            'context' => 'Button label used to open the shared competence editor for an existing competence.',
        ],
        'profile.popup.competence.cancel' => [
            'text' => 'Annuler',
            'context' => 'Button label used to close the shared competence editor without saving.',
        ],
        'profile.popup.competence.create_button' => [
            'text' => 'Ajouter une competence',
            'context' => 'Button label used to open the shared competence editor in creation mode.',
        ],
        'profile.popup.competence.editor.create_title' => [
            'text' => 'Nouvelle competence',
            'context' => 'Title shown above the shared competence editor in creation mode.',
        ],
        'profile.popup.competence.editor.edit_title' => [
            'text' => 'Modifier la competence',
            'context' => 'Title shown above the shared competence editor in edit mode.',
        ],
        'profile.popup.competence.js.reload_error' => [
            'text' => 'Impossible de recharger les compétences.',
            'context' => 'Error message shown when the competence list cannot be refreshed after a save or delete.',
        ],
        'profile.popup.competence.js.save_error' => [
            'text' => "Impossible d'enregistrer cette compétence.",
            'context' => 'Error message shown when saving a competence fails.',
        ],
        'profile.popup.competence.js.save_success' => [
            'text' => 'Compétence enregistrée.',
            'context' => 'Success message shown after saving a competence.',
        ],
        'profile.popup.competence.js.delete_confirm' => [
            'text' => 'Supprimer cette compétence ?',
            'context' => 'Confirmation dialog shown before deleting a competence.',
        ],
        'profile.popup.competence.js.delete_error' => [
            'text' => 'Impossible de supprimer cette compétence.',
            'context' => 'Error message shown when deleting a competence fails.',
        ],
        'profile.popup.competence.js.delete_success' => [
            'text' => 'Compétence supprimée.',
            'context' => 'Success message shown after deleting a competence.',
        ],
    ];

    return $sourceLang;
}

function profilPopupLoadBundle(): array
{
    static $bundle = null;
    if ($bundle !== null) {
        return $bundle;
    }

    $bundle = omoLoadTranslationBundle('popup_profile_editor', profilPopupGetSourceLang());
    return $bundle;
}

function profilPopupT(string $key, array $variables = [], ?array $bundle = null, ?array $sourceLang = null): string
{
    $sourceLang = is_array($sourceLang) ? $sourceLang : profilPopupGetSourceLang();
    $bundle = is_array($bundle) ? $bundle : profilPopupLoadBundle();

    return t($key, $variables, $bundle, $sourceLang);
}
