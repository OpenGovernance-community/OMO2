<?php

function commonAccountUiT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'login_email_required' => ['text' => 'Veuillez utiliser votre e-mail pour la connexion.', 'context' => 'Validation shown when a shared account login form has no valid email address.'],
        'copy.requete_invalide' => ['text' => 'Requête invalide.', 'context' => 'Interface text in ajax/createaccount.php'],
        'copy.impossible_de_mettre_a_jour_le_compte' => ['text' => 'Impossible de mettre à jour le compte.', 'context' => 'Interface text in ajax/createaccount.php'],
        'copy.compte_mis_a_jour' => ['text' => 'Compte mis à jour.', 'context' => 'Interface text in ajax/createaccount.php'],
        'copy.la_connexion_avec_mot_de_passe_n_est_pas_autorisee_pour_ce_compte_util' => ['text' => 'La connexion avec mot de passe n’est pas autorisée pour ce compte. Utilisez le code reçu par e-mail.', 'context' => 'Interface text in ajax/login.php'],
        'copy.si_ce_compte_existe_la_procedure_de_reinitialisation_vous_a_ete_envoye' => ['text' => 'Si ce compte existe, la procédure de réinitialisation vous a été envoyée.\\n\\nVeuillez vérifier votre boîte e-mail et suivre les instructions envoyées pour modifier votre mot de passe.', 'context' => 'Interface text in ajax/login.php'],
        'copy.un_compte_avec_cette_adresse_e_mail_existe_deja_n_nveuillez_verifier_v' => ['text' => 'Un compte avec cette adresse e-mail existe déjà.\\n\\nVeuillez vérifier votre boîte e-mail et suivre les instructions envoyées pour le réactiver ou modifier le mot de passe.', 'context' => 'Interface text in ajax/login.php'],
        'copy.vous_n_avez_pas_le_droit_d_editer_les_informations_de_cet_utilisateur' => ['text' => 'Vous n\'avez pas le droit d\'éditer les informations de cet utilisateur', 'context' => 'Interface text in ajax/saveaccount.php'],
        'copy.donnees_invalides' => ['text' => 'Données invalides.', 'context' => 'Interface text in ajax/saveaccount.php'],
        'copy.donnees_ou_image_invalides' => ['text' => 'Données ou image invalides.', 'context' => 'Interface text in ajax/saveaccount.php'],
        'copy.impossible_d_enregistrer_ce_profil' => ['text' => 'Impossible d’enregistrer ce profil.', 'context' => 'Interface text in ajax/saveaccount.php'],
        'copy.profil_et_mot_de_passe_enregistres' => ['text' => 'Profil et mot de passe enregistrés', 'context' => 'Interface text in ajax/saveaccount.php'],
        'copy.enregistrement_reussi' => ['text' => 'Enregistrement réussi', 'context' => 'Interface text in ajax/saveaccount.php'],
        'copy.aucune_organisation_n_est_selectionnee' => ['text' => 'Aucune organisation n\'est sélectionnée.', 'context' => 'Interface text in ajax/saveaccount_organization.php'],
        'copy.adhesion_requise' => ['text' => 'Adhésion requise.', 'context' => 'Interface text in ajax/saveaccount_organization.php'],
        'copy.vous_n_avez_pas_le_droit_d_editer_ce_profil_d_organisation' => ['text' => 'Vous n\'avez pas le droit d\'éditer ce profil d\'organisation.', 'context' => 'Interface text in ajax/saveaccount_organization.php'],
        'copy.adhesion_inactive' => ['text' => 'Adhésion inactive.', 'context' => 'Interface text in ajax/saveaccount_organization.php'],
        'copy.requete_refusee' => ['text' => 'Requête refusée.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.la_double_authentification_est_deja_activee' => ['text' => 'La double authentification est déjà activée.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.la_cle_de_protection_totp_du_serveur_est_manquante' => ['text' => 'La clé de protection TOTP du serveur est manquante.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.la_configuration_a_expire_recommencez_la_procedure' => ['text' => 'La configuration a expiré. Recommencez la procédure.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.le_code_de_verification_est_invalide' => ['text' => 'Le code de vérification est invalide.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.le_secret_ne_peut_pas_etre_protege_par_le_serveur' => ['text' => 'Le secret ne peut pas être protégé par le serveur.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.impossible_d_activer_la_double_authentification' => ['text' => 'Impossible d’activer la double authentification.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.la_double_authentification_est_activee' => ['text' => 'La double authentification est activée.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.impossible_de_desactiver_la_double_authentification' => ['text' => 'Impossible de désactiver la double authentification.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.la_double_authentification_est_desactivee' => ['text' => 'La double authentification est désactivée.', 'context' => 'Interface text in ajax/totp_setup.php'],
        'copy.la_demande_a_expire_rechargez_la_page_avant_de_recommencer' => ['text' => 'La demande a expiré. Rechargez la page avant de recommencer.', 'context' => 'Interface text in ajax/user_account_delete.php'],
        'copy.votre_profil_a_ete_supprime' => ['text' => 'Votre profil a été supprimé.', 'context' => 'Interface text in ajax/user_account_delete.php'],
        'copy.competence_supprimee' => ['text' => 'Compétence supprimée.', 'context' => 'Interface text in ajax/user_competence_delete.php'],
        'copy.impossible_de_supprimer_cette_competence' => ['text' => 'Impossible de supprimer cette compétence.', 'context' => 'Interface text in ajax/user_competence_delete.php'],
        'copy.competence_enregistree' => ['text' => 'Compétence enregistrée.', 'context' => 'Interface text in ajax/user_competence_save.php'],
        'copy.impossible_d_enregistrer_cette_competence' => ['text' => 'Impossible d\'enregistrer cette compétence.', 'context' => 'Interface text in ajax/user_competence_save.php'],
        'copy.utilisateur_non_connecte' => ['text' => 'Utilisateur non connecté.', 'context' => 'Interface text in common/admin_mode.php'],
        'copy.le_mot_de_passe_doit_faire_au_moins_minlength_caracteres_et_contenir_u' => ['text' => 'Le mot de passe doit faire au moins {minLength} caractères et contenir une minuscule, une majuscule, un chiffre et un caractère spécial.', 'context' => 'Interface text in common/auth.php'],
        'copy.le_mot_de_passe_doit_respecter_les_criteres_ci_dessous' => ['text' => 'Le mot de passe doit respecter les critères ci-dessous.', 'context' => 'Interface text in common/confirm.php'],
        'copy.au_moins_12_caracteres' => ['text' => 'Au moins 12 caractères', 'context' => 'Interface text in common/confirm.php'],
        'copy.au_moins_un_caractere_special_ou_un_espace' => ['text' => 'Au moins un caractère spécial ou un espace', 'context' => 'Interface text in common/confirm.php'],
        'copy.evitez_de_reprendre_votre_e_mail_ou_votre_nom_d_utilisateur' => ['text' => 'Évitez de reprendre votre e-mail ou votre nom d\'utilisateur', 'context' => 'Interface text in common/confirm.php'],
        'copy.retapez_le_meme_mot_de_passe_pour_confirmation' => ['text' => 'Retapez le même mot de passe pour confirmation.', 'context' => 'Interface text in common/confirm.php'],
        'copy.veuillez_definir_un_nouveau_mot_de_passe_qui_remplacera_le_precedent_s' => ['text' => 'Veuillez définir un nouveau mot de passe, qui remplacera le précédent. Si vous n\'êtes pas l\'auteur de cette demande de réinitialisation, ignorez simplement cette demande et connectez-vous comme normalement.', 'context' => 'Interface text in common/confirm.php'],
        'copy.impossible_d_enregistrer_la_langue' => ['text' => 'Impossible d’enregistrer la langue.', 'context' => 'Interface text in common/language_preference.php'],
        'copy.identite_patreon_inattendue_reconnectez_le_compte' => ['text' => 'Identité Patreon inattendue. Reconnectez le compte.', 'context' => 'Interface text in common/patreon.php'],
        'copy.les_droits_de_gestion_de_cette_organisation_ont_ete_retires' => ['text' => 'Les droits de gestion de cette organisation ont été retirés.', 'context' => 'Interface text in common/patreon_callback.php'],
        'copy.acces_refuse_a0bd21' => ['text' => 'Accès refusé.', 'context' => 'Interface text in common/patreon_connect.php'],
        'copy.requete_refusee_rechargez_la_page' => ['text' => 'Requête refusée. Rechargez la page.', 'context' => 'Interface text in common/request_security.php'],
    ];
    require_once __DIR__ . '/translation_bundles.php';
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= loadTranslationBundle('common_account_translations', $locale, $sourceLang)) : $sourceLang;
    return t($key, $variables, $bundle, $sourceLang);
}
