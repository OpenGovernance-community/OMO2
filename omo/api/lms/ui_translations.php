<?php

require_once __DIR__ . '/../../translations.php';

function omoLmsUiSourceLang(): array
{
    static $sourceLang = [
        'parcours_inconnu' => ['text' => 'Parcours inconnu', 'context' => 'Validation or action feedback in api/lms/action.php, api/lms/homework_action.php'],
        'acces_non_autorise' => ['text' => 'Accès non autorisé', 'context' => 'Validation or action feedback in api/lms/action.php, api/lms/homework_action.php'],
        'mission_ou_parcours_invalide' => ['text' => 'Mission ou parcours invalide', 'context' => 'Validation or action feedback in api/lms/action.php'],
        'progression_indisponible' => ['text' => 'Progression indisponible', 'context' => 'Validation or action feedback in api/lms/action.php, api/lms/homework_action.php'],
        'mission_introuvable_dans_ce_parcours' => ['text' => 'Mission introuvable dans ce parcours', 'context' => 'Validation or action feedback in api/lms/action.php, api/lms/homework_action.php'],
        'devoir_requis' => ['text' => 'Devoir requis', 'context' => 'Validation or action feedback in api/lms/action.php'],
        'ok' => ['text' => 'OK', 'context' => 'Validation or action feedback in api/lms/action.php, api/lms/homework_action.php'],
        'vous_n_avez_pas_le_droit_de_supprimer_ou_detacher_ce_parcours' => ['text' => 'Vous n’avez pas le droit de supprimer ou détacher ce parcours.', 'context' => 'Validation or action feedback in api/lms/delete_parcours.php, api/lms/delete_parcours_preview.php'],
        'parcours_invalide' => ['text' => 'Parcours invalide.', 'context' => 'Validation or action feedback in api/lms/delete_parcours.php, api/lms/delete_parcours_preview.php, api/lms/import_parcours.php, api/lms/parcours_mission_create.php'],
        'parcours_introuvable' => ['text' => 'Parcours introuvable.', 'context' => 'Validation or action feedback in api/lms/delete_parcours.php, api/lms/delete_parcours_preview.php, api/lms/parcours_mission_add.php, api/lms/parcours_mission_create.php, api/lms/parcours_mission_remove.php, api/lms/parcours_mission_reorder.php, api/lms/parcours_prerequisite_add.php, api/lms/parcours_prerequisite_remove.php, api/lms/save_parcours.php'],
        'parcours_supprime' => ['text' => 'Parcours supprimé.', 'context' => 'Validation or action feedback in api/lms/delete_parcours.php'],
        'volume' => ['text' => 'Volume', 'context' => 'Interface label in api/lms/getMissionDetail.php'],
        'demande_de_devoir_invalide' => ['text' => 'Demande de devoir invalide', 'context' => 'Validation or action feedback in api/lms/homework_action.php'],
        'devoir_introuvable_dans_cette_mission' => ['text' => 'Devoir introuvable dans cette mission', 'context' => 'Validation or action feedback in api/lms/homework_action.php'],
        'vous_n_avez_pas_le_droit_d_importer_un_parcours_dans_ce_contexte' => ['text' => 'Vous n’avez pas le droit d’importer un parcours dans ce contexte.', 'context' => 'Validation or action feedback in api/lms/import_parcours.php'],
        'parcours_deja_present_dans_cette_organisation' => ['text' => 'Parcours déjà présent dans cette organisation.', 'context' => 'Validation or action feedback in api/lms/import_parcours.php'],
        'ce_parcours_n_est_pas_disponible_a_l_import' => ['text' => 'Ce parcours n’est pas disponible à l’import.', 'context' => 'Validation or action feedback in api/lms/import_parcours.php'],
        'parcours_importe' => ['text' => 'Parcours importé.', 'context' => 'Validation or action feedback in api/lms/import_parcours.php'],
        'fermer' => ['text' => 'Fermer', 'context' => 'Interface label in api/lms/inc/drawer.php'],
        'termine' => ['text' => 'Terminé', 'context' => 'Interface label in api/lms/inc/drawer.php'],
        'rechercher_un_parcours_ou_une_mission' => ['text' => 'Rechercher un parcours ou une mission', 'context' => 'Validation or action feedback in api/lms/inc/menu.php'],
        'aide' => ['text' => 'Aide', 'context' => 'Validation or action feedback in api/lms/inc/menu.php'],
        'acces_aux_questions_les_plus_courantes_avec_moteur_de_recherche_pour_trouver_facilement_la' => ['text' => 'Accès aux questions les plus courantes, avec moteur de recherche pour trouver facilement la réponse à ses questions.', 'context' => 'Validation or action feedback in api/lms/inc/menu.php'],
        'faq_lms' => ['text' => 'FAQ LMS', 'context' => 'Validation or action feedback in api/lms/inc/menu.php'],
        'visite_guidee' => ['text' => 'Visite guidée', 'context' => 'Validation or action feedback in api/lms/inc/menu.php'],
        'tour_des_fonctions_visibles_a_l_ecran_avec_explication_pour_chaque_bouton_et_chaque_possib' => ['text' => 'Tour des fonctions visibles à l’écran avec explication pour chaque bouton et chaque possibilité.', 'context' => 'Validation or action feedback in api/lms/inc/menu.php'],
        'des_formations_ciblees_pour_monter_en_competences_dans_l_utilisation_du_logiciel' => ['text' => 'Des formations ciblées pour monter en compétences dans l’utilisation du logiciel.', 'context' => 'Validation or action feedback in api/lms/inc/menu.php'],
        'quiz' => ['text' => 'quiz', 'context' => 'Interface label in api/lms/inc/parcours_editor.php'],
        'devoirs' => ['text' => 'devoirs', 'context' => 'Interface label in api/lms/inc/parcours_editor.php'],
        'logo' => ['text' => 'logo', 'context' => 'Interface label in api/lms/index.php'],
        'actions' => ['text' => 'Actions', 'context' => 'Interface label in api/lms/index.php'],
        'acces_refuse' => ['text' => 'Accès refusé.', 'context' => 'Validation or action feedback in api/lms/mission_dependency_add.php, api/lms/mission_dependency_remove.php, api/lms/mission_homework_create.php, api/lms/mission_homework_reorder.php, api/lms/mission_question_create.php, api/lms/mission_question_reorder.php, api/lms/parcours_mission_add.php, api/lms/parcours_mission_create.php, api/lms/parcours_mission_remove.php, api/lms/parcours_mission_reorder.php, api/lms/parcours_pack_add.php, api/lms/parcours_pack_remove.php, api/lms/parcours_pack_reorder.php, api/lms/parcours_prerequisite_add.php, api/lms/parcours_prerequisite_remove.php, api/lms/save_mission.php, api/lms/save_parcours.php'],
        'vous_n_avez_pas_le_droit_de_modifier_ce_parcours' => ['text' => 'Vous n’avez pas le droit de modifier ce parcours.', 'context' => 'Validation or action feedback in api/lms/mission_dependency_add.php, api/lms/mission_dependency_remove.php, api/lms/mission_homework_create.php, api/lms/mission_homework_reorder.php, api/lms/mission_question_create.php, api/lms/mission_question_reorder.php, api/lms/parcours_mission_add.php, api/lms/parcours_mission_create.php, api/lms/parcours_mission_remove.php, api/lms/parcours_mission_reorder.php, api/lms/parcours_pack_add.php, api/lms/parcours_pack_remove.php, api/lms/parcours_pack_reorder.php, api/lms/parcours_prerequisite_add.php, api/lms/parcours_prerequisite_remove.php, api/lms/save_mission.php, api/lms/save_parcours.php'],
        'mission_prerequis_introuvable_ou_non_autorisee' => ['text' => 'Mission prérequis introuvable ou non autorisée.', 'context' => 'Validation or action feedback in api/lms/mission_dependency_add.php'],
        'prerequis_ajoute' => ['text' => 'Prérequis ajouté.', 'context' => 'Validation or action feedback in api/lms/mission_dependency_add.php'],
        'prerequis_retire' => ['text' => 'Prérequis retiré.', 'context' => 'Validation or action feedback in api/lms/mission_dependency_remove.php, api/lms/parcours_prerequisite_remove.php'],
        'mission_introuvable' => ['text' => 'Mission introuvable.', 'context' => 'Validation or action feedback in api/lms/mission_homework_create.php, api/lms/mission_homework_reorder.php, api/lms/mission_question_create.php, api/lms/mission_question_reorder.php, api/lms/parcours_mission_add.php, api/lms/save_mission.php'],
        'devoir_introuvable' => ['text' => 'Devoir introuvable.', 'context' => 'Validation or action feedback in api/lms/mission_homework_create.php'],
        'le_titre_est_obligatoire' => ['text' => 'Le titre est obligatoire.', 'context' => 'Validation or action feedback in api/lms/mission_homework_create.php, api/lms/parcours_mission_create.php, api/lms/save_mission.php, api/lms/save_parcours.php'],
        'ordre_des_devoirs_mis_a_jour' => ['text' => 'Ordre des devoirs mis à jour.', 'context' => 'Validation or action feedback in api/lms/mission_homework_reorder.php'],
        'ajoutez_au_moins_deux_choix_de_reponse' => ['text' => 'Ajoutez au moins deux choix de réponse.', 'context' => 'Validation or action feedback in api/lms/mission_question_create.php'],
        'indiquez_au_moins_une_bonne_reponse' => ['text' => 'Indiquez au moins une bonne réponse.', 'context' => 'Validation or action feedback in api/lms/mission_question_create.php'],
        'question_introuvable' => ['text' => 'Question introuvable.', 'context' => 'Validation or action feedback in api/lms/mission_question_create.php'],
        'la_question_est_obligatoire' => ['text' => 'La question est obligatoire.', 'context' => 'Validation or action feedback in api/lms/mission_question_create.php'],
        'la_reponse_est_obligatoire' => ['text' => 'La réponse est obligatoire.', 'context' => 'Validation or action feedback in api/lms/mission_question_create.php'],
        'ordre_des_questions_mis_a_jour' => ['text' => 'Ordre des questions mis à jour.', 'context' => 'Validation or action feedback in api/lms/mission_question_reorder.php'],
        'parcours_introuvable_2' => ['text' => 'Parcours introuvable', 'context' => 'Validation or action feedback in api/lms/parcours.php'],
        'votre_avancement_est_memorise_localement_sur_cet_appareil_tant_que_vous_restez_deconnecte' => ['text' => 'Votre avancement est mémorisé localement sur cet appareil tant que vous restez déconnecté.', 'context' => 'Interface label in api/lms/parcours.php'],
        'la_validation_de_progression_n_est_pas_disponible_dans_ce_contexte' => ['text' => 'La validation de progression n’est pas disponible dans ce contexte.', 'context' => 'Interface label in api/lms/parcours.php'],
        'le_resume_est_obligatoire' => ['text' => 'Le résumé est obligatoire.', 'context' => 'Validation or action feedback in api/lms/parcours_mission_create.php, api/lms/save_mission.php'],
        'mission_creee_et_ajoutee' => ['text' => 'Mission créée et ajoutée.', 'context' => 'Validation or action feedback in api/lms/parcours_mission_create.php'],
        'mission_retiree_du_parcours' => ['text' => 'Mission retirée du parcours.', 'context' => 'Validation or action feedback in api/lms/parcours_mission_remove.php'],
        'ordre_mis_a_jour' => ['text' => 'Ordre mis à jour.', 'context' => 'Validation or action feedback in api/lms/parcours_mission_reorder.php, api/lms/parcours_pack_reorder.php'],
        'pack_introuvable' => ['text' => 'Pack introuvable.', 'context' => 'Validation or action feedback in api/lms/parcours_pack_add.php, api/lms/parcours_pack_remove.php, api/lms/parcours_pack_reorder.php'],
        'parcours_enfant_introuvable_ou_non_autorise' => ['text' => 'Parcours enfant introuvable ou non autorisé.', 'context' => 'Validation or action feedback in api/lms/parcours_pack_add.php'],
        'parcours_retire_du_pack' => ['text' => 'Parcours retiré du pack.', 'context' => 'Validation or action feedback in api/lms/parcours_pack_remove.php'],
        'parcours_prerequis_introuvable_ou_non_autorise' => ['text' => 'Parcours prérequis introuvable ou non autorisé.', 'context' => 'Validation or action feedback in api/lms/parcours_prerequisite_add.php'],
        'mission_mise_a_jour' => ['text' => 'Mission mise à jour.', 'context' => 'Validation or action feedback in api/lms/save_mission.php'],
        'vous_n_avez_pas_le_droit_de_creer_un_parcours_dans_ce_contexte' => ['text' => 'Vous n’avez pas le droit de créer un parcours dans ce contexte.', 'context' => 'Validation or action feedback in api/lms/save_parcours.php'],
        'verrouillee' => ['text' => 'Verrouillée', 'context' => 'Interface text in api/lms/getmissions_next.php'],
        'verrouille' => ['text' => 'Verrouillé', 'context' => 'Interface text in api/lms/getmissions_next.php'],
        'faq' => ['text' => 'FAQ', 'context' => 'Interface text in api/lms/inc/menu.php'],
        'cette_zone_pourra_accueillir_les_questions_frequentes_du_lms_pour_l_instant_elle_sert_de_p' => ['text' => 'Cette zone pourra accueillir les questions fréquentes du LMS. Pour l’instant, elle sert de point d’entrée commun.', 'context' => 'Interface text in api/lms/inc/menu.php'],
        'la_visite_guidee_du_lms_pourra_etre_branchee_ici_application_par_application' => ['text' => 'La visite guidée du LMS pourra être branchée ici application par application.', 'context' => 'Interface text in api/lms/inc/menu.php'],
        'tutoriels' => ['text' => 'Tutoriels', 'context' => 'Interface text in api/lms/inc/menu.php'],
        'cette_section_pourra_ensuite_lister_des_tutoriels_video_des_guides_pas_a_pas_ou_des_suppor' => ['text' => 'Cette section pourra ensuite lister des tutoriels vidéo, des guides pas à pas ou des supports de formation.', 'context' => 'Interface text in api/lms/inc/menu.php'],
    ];
    return $sourceLang;
}

function omoLmsUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoLmsUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_lms_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
