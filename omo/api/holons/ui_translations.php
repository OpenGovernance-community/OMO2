<?php

require_once __DIR__ . '/../../translations.php';

function omoHolonsUiSourceLang(): array
{
    static $sourceLang = [
        'droits_associes_a_l_element' => ['text' => 'Droits associés à l’élément', 'context' => 'Validation or action feedback in api/holons/create.php'],
        'aucune_organisation_n_est_actuellement_selectionnee' => ['text' => 'Aucune organisation n\'est actuellement sélectionnée.', 'context' => 'Validation or action feedback in api/holons/create.php, api/holons/move.php'],
        'l_organisation_demandee_est_introuvable' => ['text' => 'L\'organisation demandée est introuvable.', 'context' => 'Validation or action feedback in api/holons/create.php, api/holons/delete.php, api/holons/move.php, api/holons/move_action.php, api/holons/save.php'],
        'droits_associes_au_modele' => ['text' => 'Droits associés au modèle', 'context' => 'Validation or action feedback in api/holons/create.php'],
        'l_element_demande_est_introuvable' => ['text' => 'L’élément demandé est introuvable.', 'context' => 'Validation or action feedback in api/holons/create.php'],
        'cet_element_ne_peut_pas_etre_edite_avec_ce_formulaire' => ['text' => 'Cet élément ne peut pas être édité avec ce formulaire.', 'context' => 'Validation or action feedback in api/holons/create.php'],
        'cet_element_n_autorise_pas_l_ajout_d_enfant' => ['text' => 'Cet élément n\'autorise pas l\'ajout d\'enfant.', 'context' => 'Validation or action feedback in api/holons/create.php'],
        'nom' => ['text' => 'Nom', 'context' => 'Interface label in api/holons/create.php'],
        'modele' => ['text' => 'Modèle', 'context' => 'Interface label in api/holons/create.php'],
        'nom_complet' => ['text' => 'Nom complet', 'context' => 'Interface label in api/holons/create.php'],
        'facultatif_utilise_dans_la_vue_liste_et_dans_la_fiche_contexte' => ['text' => 'Facultatif. Utilisé dans la vue liste et dans la fiche contexte.', 'context' => 'Interface label in api/holons/create.php'],
        'proprietes' => ['text' => 'Propriétés', 'context' => 'Interface label in api/holons/create.php'],
        'les_proprietes_heritees_du_modele_sont_affichees_ci_dessous' => ['text' => 'Les propriétés héritées du modèle sont affichées ci-dessous.', 'context' => 'Interface label in api/holons/create.php'],
        'ajouter_une_propriete' => ['text' => 'Ajouter une propriété', 'context' => 'Interface label in api/holons/create.php'],
        'droits' => ['text' => 'Droits', 'context' => 'Interface label in api/holons/create.php'],
        'droits_herites' => ['text' => 'Droits hérités', 'context' => 'Interface label in api/holons/create.php'],
        'aucun' => ['text' => 'aucun', 'context' => 'Interface label in api/holons/create.php'],
        'editer' => ['text' => 'Éditer', 'context' => 'Interface label in api/holons/create.php'],
        'equipe' => ['text' => 'Équipe', 'context' => 'Interface label in api/holons/create.php'],
        'minimum_de' => ['text' => 'Minimum de', 'context' => 'Interface label in api/holons/create.php'],
        'redefinir' => ['text' => 'Redéfinir', 'context' => 'Interface label in api/holons/create.php'],
        'maximum_de' => ['text' => 'Maximum de', 'context' => 'Interface label in api/holons/create.php'],
        'apparence' => ['text' => 'Apparence', 'context' => 'Interface label in api/holons/create.php'],
        'couleur' => ['text' => 'Couleur', 'context' => 'Interface label in api/holons/create.php'],
        'illustrations' => ['text' => 'Illustrations', 'context' => 'Interface label in api/holons/create.php'],
        'icone' => ['text' => 'Icône', 'context' => 'Interface label in api/holons/create.php'],
        'fermer' => ['text' => 'Fermer', 'context' => 'Interface label in api/holons/create.php'],
        'sans_limite' => ['text' => 'Sans limite', 'context' => 'Interface label in api/holons/create.php'],
        'redefinir_la_couleur' => ['text' => 'Redéfinir la couleur', 'context' => 'Interface label in api/holons/create.php'],
        'couleur_redefinie' => ['text' => 'Couleur redéfinie', 'context' => 'Interface label in api/holons/create.php'],
        'aucune_organisation_n_est_selectionnee' => ['text' => 'Aucune organisation n\'est sélectionnée.', 'context' => 'Validation or action feedback in api/holons/delete.php, api/holons/move_action.php, api/holons/save.php'],
        'l_espace_a_supprimer_est_invalide' => ['text' => 'L’espace à supprimer est invalide.', 'context' => 'Validation or action feedback in api/holons/delete.php, api/holons/delete_popup.php'],
        'l_espace_n_a_pas_pu_etre_supprime' => ['text' => 'L’espace n’a pas pu être supprimé.', 'context' => 'Validation or action feedback in api/holons/delete.php, api/holons/delete_popup.php'],
        'espace_supprime' => ['text' => 'Espace supprimé.', 'context' => 'Validation or action feedback in api/holons/delete.php, api/holons/delete_popup.php'],
        'l_espace_demande_est_introuvable' => ['text' => 'L’espace demandé est introuvable.', 'context' => 'Validation or action feedback in api/holons/delete_popup.php, api/holons/history_popup.php, api/holons/member_popup.php, api/holons/move.php'],
        'vous_n_avez_pas_les_droits_pour_supprimer_cet_espace' => ['text' => 'Vous n\'avez pas les droits pour supprimer cet espace.', 'context' => 'Validation or action feedback in api/holons/delete_popup.php'],
        'suppression' => ['text' => 'Suppression', 'context' => 'Interface label in api/holons/delete_popup.php'],
        'supprimer_un_espace' => ['text' => 'Supprimer un espace', 'context' => 'Interface label in api/holons/delete_popup.php'],
        'supprimer' => ['text' => 'Supprimer', 'context' => 'Interface label in api/holons/delete_popup.php'],
        'annuler' => ['text' => 'Annuler', 'context' => 'Interface label in api/holons/delete_popup.php, api/holons/move.php'],
        'par' => ['text' => 'par', 'context' => 'Interface label in api/holons/history_popup.php'],
        'detail' => ['text' => 'Détail', 'context' => 'Interface label in api/holons/history_popup.php'],
        'vous_devez_etre_connecte_a_une_organisation_pour_consulter_cet_historique' => ['text' => 'Vous devez être connecté à une organisation pour consulter cet historique.', 'context' => 'Interface label in api/holons/history_popup.php'],
        'vous_n_avez_pas_le_droit_de_consulter_l_historique_de_cet_espace' => ['text' => 'Vous n\'avez pas le droit de consulter l\'historique de cet espace.', 'context' => 'Interface label in api/holons/history_popup.php'],
        'historique' => ['text' => 'Historique', 'context' => 'Interface label in api/holons/history_popup.php'],
        'le_focus_ne_peut_pas_depasser' => ['text' => 'Le focus ne peut pas dépasser ', 'context' => 'Validation or action feedback in api/holons/member_popup.php'],
        'vous_n_avez_pas_le_droit_de_gerer_le_statut' => ['text' => 'Vous n\'avez pas le droit de gérer le statut ', 'context' => 'Validation or action feedback in api/holons/member_popup.php'],
        'impossible_d_ajouter_ce_membre' => ['text' => 'Impossible d\'ajouter ce membre.', 'context' => 'Validation or action feedback in api/holons/member_popup.php'],
        'membre_ajoute' => ['text' => 'Membre ajouté.', 'context' => 'Validation or action feedback in api/holons/member_popup.php'],
        'vous_devez_etre_connecte_a_une_organisation_pour_ajouter_un_membre' => ['text' => 'Vous devez être connecté à une organisation pour ajouter un membre.', 'context' => 'Interface label in api/holons/member_popup.php'],
        'vous_n_avez_pas_le_droit_d_ajouter_un_membre_dans_ce_contexte' => ['text' => 'Vous n\'avez pas le droit d\'ajouter un membre dans ce contexte.', 'context' => 'Interface label in api/holons/member_popup.php'],
        'ajouter_un_membre' => ['text' => 'Ajouter un membre', 'context' => 'Interface label in api/holons/member_popup.php'],
        'ajoutez_une_personne_au_holon' => ['text' => 'Ajoutez une personne au holon', 'context' => 'Interface label in api/holons/member_popup.php'],
        'soit_en_choisissant_un_membre_deja_present_dans_l_organisation_soit_en_saisissant_une_nouv' => ['text' => ',
        soit en choisissant un membre déjà présent dans l\'organisation, soit en saisissant une nouvelle adresse e-mail.', 'context' => 'Interface label in api/holons/member_popup.php'],
        'membre_existant' => ['text' => 'Membre existant', 'context' => 'Interface label in api/holons/member_popup.php'],
        'choisir_dans_l_organisation' => ['text' => 'Choisir dans l\'organisation', 'context' => 'Interface label in api/holons/member_popup.php'],
        'les_personnes_deja_liees_directement_a_ce_holon_sont_desactivees_dans_la_liste' => ['text' => 'Les personnes déjà liées directement à ce holon sont désactivées dans la liste.', 'context' => 'Interface label in api/holons/member_popup.php'],
        'nouvelle_adresse_e_mail' => ['text' => 'Nouvelle adresse e-mail', 'context' => 'Interface label in api/holons/member_popup.php'],
        'si_l_adresse_existe_deja_le_profil_existant_sera_reutilise_sinon_un_nouveau_profil_minimal' => ['text' => 'Si l\'adresse existe déjà, le profil existant sera réutilisé. Sinon, un nouveau profil minimal sera créé puis rattaché.', 'context' => 'Interface label in api/holons/member_popup.php'],
        'focus' => ['text' => 'Focus', 'context' => 'Interface label in api/holons/member_popup.php'],
        'ajouter_au_holon' => ['text' => 'Ajouter au holon', 'context' => 'Interface label in api/holons/member_popup.php'],
        'l_espace_a_deplacer_est_invalide' => ['text' => 'L’espace à déplacer est invalide.', 'context' => 'Validation or action feedback in api/holons/move.php'],
        'vous_n_avez_pas_les_droits_pour_deplacer_cet_espace' => ['text' => 'Vous n’avez pas les droits pour déplacer cet espace.', 'context' => 'Validation or action feedback in api/holons/move.php'],
        'aucune_destination_compatible_n_a_ete_trouvee_pour_cet_espace' => ['text' => 'Aucune destination compatible n’a été trouvée pour cet espace.', 'context' => 'Validation or action feedback in api/holons/move.php'],
        'espace' => ['text' => 'Espace', 'context' => 'Interface label in api/holons/move.php'],
        'deplacer_un_espace' => ['text' => 'Déplacer un espace', 'context' => 'Interface label in api/holons/move.php'],
        'destination' => ['text' => 'Destination', 'context' => 'Interface label in api/holons/move.php'],
        'deplacer' => ['text' => 'Déplacer', 'context' => 'Interface label in api/holons/move.php'],
        'rechercher_une_destination' => ['text' => 'Rechercher une destination', 'context' => 'Interface label in api/holons/move.php'],
        'le_deplacement_demande_est_invalide' => ['text' => 'Le déplacement demandé est invalide.', 'context' => 'Validation or action feedback in api/holons/move_action.php'],
        'l_espace_n_a_pas_pu_etre_deplace' => ['text' => 'L’espace n’a pas pu être déplacé.', 'context' => 'Validation or action feedback in api/holons/move_action.php'],
        'espace_deplace' => ['text' => 'Espace déplacé.', 'context' => 'Validation or action feedback in api/holons/move_action.php'],
        'la_requete_envoyee_est_invalide' => ['text' => 'La requête envoyée est invalide.', 'context' => 'Validation or action feedback in api/holons/save.php'],
        'l_espace_n_a_pas_pu_etre_cree' => ['text' => 'L’espace n’a pas pu être créé.', 'context' => 'Validation or action feedback in api/holons/save.php'],
        'espace_cree' => ['text' => 'Espace créé.', 'context' => 'Validation or action feedback in api/holons/save.php'],
        'focus_maximum' => ['text' => 'Le focus ne peut pas dépasser {count} caractères.', 'context' => 'Translated interface feedback with named interpolation variables.'],
        'manage_admin_denied' => ['text' => 'Vous n’avez pas le droit de gérer le statut {admin} dans ce contexte.', 'context' => 'Translated interface feedback with named interpolation variables.'],
    ];
    return $sourceLang;
}

function omoHolonsUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoHolonsUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_holons_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
