<?php

require_once __DIR__ . '/../../translations.php';

function omoOrganizationsUiSourceLang(): array
{
    static $sourceLang = [
        'connexion_requise' => ['text' => 'Connexion requise.', 'context' => 'Validation or action feedback in api/organizations/card_action.php, api/organizations/create_import.php, api/organizations/import_preview.php, api/organizations/model_create.php'],
        'action_organisation_invalide' => ['text' => 'Action organisation invalide.', 'context' => 'Validation or action feedback in api/organizations/card_action.php'],
        'organisation_introuvable' => ['text' => 'Organisation introuvable.', 'context' => 'Validation or action feedback in api/organizations/card_action.php'],
        'vous_n_avez_pas_acces_a_cette_organisation' => ['text' => 'Vous n\'avez pas accès à cette organisation.', 'context' => 'Validation or action feedback in api/organizations/card_action.php'],
        'action_inconnue' => ['text' => 'Action inconnue.', 'context' => 'Validation or action feedback in api/organizations/card_action.php'],
        'le_partage_comme_modele_requiert_une_organisation_structuree_et_le_mode_administrateur_act' => ['text' => 'Le partage comme modèle requiert une organisation structurée et le mode administrateur actif.', 'context' => 'Validation or action feedback in api/organizations/card_action.php'],
        'l_organisation_de_base_est_utilisee_par_le_systeme_et_ne_peut_pas_etre_supprimee' => ['text' => 'L’organisation de base est utilisée par le système et ne peut pas être supprimée.', 'context' => 'Validation or action feedback in api/organizations/card_action.php'],
        'vous_n_avez_pas_le_droit_de_supprimer_cette_organisation' => ['text' => 'Vous n\'avez pas le droit de supprimer cette organisation.', 'context' => 'Validation or action feedback in api/organizations/card_action.php'],
        'choisissez_explicitement_si_les_e_mails_d_invitation_aux_membres_doivent_etre_envoyes' => ['text' => 'Choisissez explicitement si les e-mails d’invitation aux membres doivent être envoyés.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'aucun_fichier_zip_ou_json_n_a_ete_transmis' => ['text' => 'Aucun fichier ZIP ou JSON n’a été transmis.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'le_fichier_n_a_pas_pu_etre_televerse' => ['text' => 'Le fichier n’a pas pu être téléversé.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'import_invalide' => ['text' => 'Import invalide : ', 'context' => 'Validation or action feedback in api/organizations/create_import.php, api/organizations/import_preview.php'],
        'les_correspondances_de_templates_ne_sont_pas_valides' => ['text' => 'Les correspondances de modèles ne sont pas valides.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'les_correspondances_de_proprietes_ne_sont_pas_valides' => ['text' => 'Les correspondances de propriétés ne sont pas valides.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'selectionnez_le_modele_d_organisation_utilise_pour_les_correspondances' => ['text' => 'Sélectionnez le modèle d’organisation utilisé pour les correspondances.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'l_import_de_la_nouvelle_organisation_a_echoue' => ['text' => 'L’import de la nouvelle organisation a échoué.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'la_nouvelle_organisation_a_ete_importee' => ['text' => 'La nouvelle organisation a été importée.', 'context' => 'Validation or action feedback in api/organizations/create_import.php'],
        'zip_omo_2' => ['text' => 'ZIP OMO 2', 'context' => 'Interface label in api/organizations/export_popup.php'],
        'obligatoire' => ['text' => 'Obligatoire', 'context' => 'Interface label in api/organizations/export_popup.php'],
        'aucune_organisation_n_est_selectionnee' => ['text' => 'Aucune organisation n\'est sélectionnée.', 'context' => 'Validation or action feedback in api/organizations/import.php, api/organizations/initialize.php'],
        'vous_n_avez_pas_les_droits_pour_importer_cette_organisation' => ['text' => 'Vous n\'avez pas les droits pour importer cette organisation.', 'context' => 'Validation or action feedback in api/organizations/import.php'],
        'l_organisation_demandee_est_introuvable' => ['text' => 'L\'organisation demandée est introuvable.', 'context' => 'Validation or action feedback in api/organizations/import.php, api/organizations/initialize.php'],
        'aucun_fichier_n_a_ete_transmis' => ['text' => 'Aucun fichier n\'a été transmis.', 'context' => 'Validation or action feedback in api/organizations/import.php'],
        'le_fichier_n_a_pas_pu_etre_televerse_2' => ['text' => 'Le fichier n\'a pas pu être téléversé.', 'context' => 'Validation or action feedback in api/organizations/import.php'],
        'l_import_de_l_organisation_a_echoue' => ['text' => 'L\'import de l\'organisation a échoué.', 'context' => 'Validation or action feedback in api/organizations/import.php'],
        'l_organisation_a_ete_importee' => ['text' => 'L\'organisation a été importée.', 'context' => 'Validation or action feedback in api/organizations/import.php'],
        'acces_refuse' => ['text' => 'Accès refusé.', 'context' => 'Interface label in api/organizations/import_popup.php'],
        'import_zip_json' => ['text' => 'Import ZIP / JSON', 'context' => 'Interface label in api/organizations/import_popup.php'],
        'importer_une_organisation' => ['text' => 'Importer une organisation', 'context' => 'Interface label in api/organizations/import_popup.php'],
        'fichier_zip_ou_json' => ['text' => 'Fichier ZIP ou JSON', 'context' => 'Interface label in api/organizations/import_popup.php'],
        'conseil_creez_d_abord_une_organisation_vide_puis_importez_un_fichier_genere_par_export_dep' => ['text' => 'Conseil: créez d\'abord une organisation vide, puis importez un fichier généré par `Export` depuis la vue structure.', 'context' => 'Interface label in api/organizations/import_popup.php'],
        'annuler' => ['text' => 'Annuler', 'context' => 'Interface label in api/organizations/import_popup.php'],
        'importer' => ['text' => 'Importer', 'context' => 'Interface label in api/organizations/import_popup.php'],
        'la_requete_envoyee_est_invalide' => ['text' => 'La requête envoyée est invalide.', 'context' => 'Validation or action feedback in api/organizations/initialize.php'],
        'l_initialisation_de_l_organisation_a_echoue' => ['text' => 'L\'initialisation de l\'organisation a échoué.', 'context' => 'Validation or action feedback in api/organizations/initialize.php'],
        'organisation_initialisee' => ['text' => 'Organisation initialisée.', 'context' => 'Validation or action feedback in api/organizations/initialize.php'],
        'choisissez_un_modele_public' => ['text' => 'Choisissez un modèle public.', 'context' => 'Validation or action feedback in api/organizations/model_create.php'],
        'la_creation_depuis_le_modele_a_echoue' => ['text' => 'La création depuis le modèle a échoué.', 'context' => 'Validation or action feedback in api/organizations/model_create.php'],
        'organisation_creee_depuis_le_modele_public' => ['text' => 'Organisation créée depuis le modèle public.', 'context' => 'Validation or action feedback in api/organizations/model_create.php'],
    ];
    return $sourceLang;
}

function omoOrganizationsUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoOrganizationsUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_organizations_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
