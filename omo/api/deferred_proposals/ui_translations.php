<?php

require_once __DIR__ . '/../../translations.php';

function omoDeferredProposalsUiSourceLang(): array
{
    static $sourceLang = [
        'acces_refuse' => ['text' => 'Accès refusé.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php, api/deferred_proposals/pv_object_save.php, api/deferred_proposals/pv_project_save.php, api/deferred_proposals/pv_proposal_context.php, api/deferred_proposals/pv_rule_context.php, api/deferred_proposals/pv_rule_save.php'],
        'action_invalide' => ['text' => 'Action invalide.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php, api/deferred_proposals/pv_project_save.php, api/deferred_proposals/pv_rule_save.php'],
        'le_collectif_du_pv_ne_dispose_pas_du_droit_necessaire_dans_cet_espace' => ['text' => 'Le collectif du PV ne dispose pas du droit nécessaire dans cet espace.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php, api/deferred_proposals/pv_project_save.php, api/deferred_proposals/pv_proposal_context.php'],
        'cette_modification_ne_peut_plus_etre_modifiee' => ['text' => 'Cette modification ne peut plus être modifiée.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php, api/deferred_proposals/pv_object_save.php, api/deferred_proposals/pv_project_save.php, api/deferred_proposals/pv_rule_save.php'],
        'la_cible_et_le_type_du_deplacement_existant_ne_peuvent_pas_etre_remplaces' => ['text' => 'La cible et le type du déplacement existant ne peuvent pas être remplacés.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php'],
        'le_contenu_de_l_editeur_est_invalide' => ['text' => 'Le contenu de l’éditeur est invalide.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php'],
        'organisation_introuvable' => ['text' => 'Organisation introuvable.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php'],
        'le_contexte_de_cet_espace_est_invalide' => ['text' => 'Le contexte de cet espace est invalide.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php'],
        'le_modele_choisi_n_est_pas_disponible_dans_cet_espace' => ['text' => 'Le modèle choisi n’est pas disponible dans cet espace.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php'],
        'modification_invalide' => ['text' => 'Modification invalide.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php, api/deferred_proposals/pv_rule_save.php'],
        'impossible_d_enregistrer_la_modification' => ['text' => 'Impossible d’enregistrer la modification.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_holon_save.php, api/deferred_proposals/pv_project_save.php, api/deferred_proposals/pv_rule_save.php'],
        'la_suppression_sera_appliquee_uniquement_apres_validation_de_la_proposition_et_si_l_elemen' => ['text' => 'La suppression sera appliquée uniquement après validation de la proposition et si l’élément n’a pas changé.', 'context' => 'Interface label in api/deferred_proposals/pv_object_editor.php'],
        'ajouter_la_suppression' => ['text' => 'Ajouter la suppression', 'context' => 'Interface label in api/deferred_proposals/pv_object_editor.php'],
        'annuler' => ['text' => 'Annuler', 'context' => 'Interface label in api/deferred_proposals/pv_object_editor.php'],
        'le_contexte_de_cette_modification_a_change' => ['text' => 'Le contexte de cette modification a changé.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_object_save.php, api/deferred_proposals/pv_project_save.php'],
        'le_collectif_du_pv_ne_dispose_pas_du_droit_necessaire' => ['text' => 'Le collectif du PV ne dispose pas du droit nécessaire.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_object_save.php'],
        'l_element_demande_est_introuvable' => ['text' => 'L’élément demandé est introuvable.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_object_save.php'],
        'selection_invalide' => ['text' => 'Sélection invalide.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_object_save.php, api/deferred_proposals/pv_proposal_context.php'],
        'le_titre_est_obligatoire' => ['text' => 'Le titre est obligatoire.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_object_save.php'],
        'le_nom_est_obligatoire' => ['text' => 'Le nom est obligatoire.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_object_save.php'],
        'la_modification_ne_peut_pas_etre_enregistree' => ['text' => 'La modification ne peut pas être enregistrée.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_object_save.php'],
        'le_projet_selectionne_est_introuvable_dans_cet_espace' => ['text' => 'Le projet sélectionné est introuvable dans cet espace.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_project_save.php'],
        'la_personne_proposant_le_projet_doit_etre_membre_de_l_organisation' => ['text' => 'La personne proposant le projet doit être membre de l’organisation.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_project_save.php'],
        'le_titre_du_projet_est_obligatoire' => ['text' => 'Le titre du projet est obligatoire.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_project_save.php'],
        'la_fin_planifiee_doit_suivre_le_debut_planifie' => ['text' => 'La fin planifiée doit suivre le début planifié.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_project_save.php'],
        'renseignez_les_champs_de_blocage_obligatoires_selon_les_parametres_de_votre_organisation' => ['text' => 'Renseignez les champs de blocage obligatoires selon les paramètres de votre organisation.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_project_save.php'],
        'le_contexte_structurel_est_invalide' => ['text' => 'Le contexte structurel est invalide.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_proposal_context.php'],
        'le_collectif_du_pv_ne_peut_pas_creer_d_espace_ici' => ['text' => 'Le collectif du PV ne peut pas créer d’espace ici.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_proposal_context.php'],
        'vous_ne_pouvez_pas_effectuer_cette_action_dans_cet_espace' => ['text' => 'Vous ne pouvez pas effectuer cette action dans cet espace.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_rule_context.php, api/deferred_proposals/pv_rule_save.php'],
        'regle_introuvable_dans_cet_espace' => ['text' => 'Règle introuvable dans cet espace.', 'context' => 'Validation or action feedback in api/deferred_proposals/pv_rule_save.php'],
    ];
    return $sourceLang;
}

function omoDeferredProposalsUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoDeferredProposalsUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_deferred_proposals_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
