<?php

require_once __DIR__ . '/../../translations.php';

function omoSharesUiSourceLang(): array
{
    static $sourceLang = [
        'connexion_requise' => ['text' => 'Connexion requise.', 'context' => 'Validation or action feedback in api/shares/context.php'],
        'organisation_invalide' => ['text' => 'Organisation invalide.', 'context' => 'Validation or action feedback in api/shares/context.php'],
        'acces_refuse_a_cette_organisation' => ['text' => 'Accès refusé à cette organisation.', 'context' => 'Validation or action feedback in api/shares/context.php'],
        'choisissez_au_moins_un_droit_d_acces' => ['text' => 'Choisissez au moins un droit d’accès.', 'context' => 'Validation or action feedback in api/shares/create.php'],
        'date_d_expiration_invalide' => ['text' => 'Date d’expiration invalide.', 'context' => 'Validation or action feedback in api/shares/create.php'],
        'lien_de_partage_introuvable' => ['text' => 'Lien de partage introuvable.', 'context' => 'Validation or action feedback in api/shares/create.php, api/shares/delete.php'],
        'impossible_de_mettre_a_jour_le_lien_de_partage' => ['text' => 'Impossible de mettre à jour le lien de partage.', 'context' => 'Validation or action feedback in api/shares/create.php'],
        'impossible_de_creer_le_lien_de_partage' => ['text' => 'Impossible de créer le lien de partage.', 'context' => 'Validation or action feedback in api/shares/create.php'],
        'lien_de_partage_invalide' => ['text' => 'Lien de partage invalide.', 'context' => 'Validation or action feedback in api/shares/delete.php'],
        'impossible_de_supprimer_le_lien_de_partage' => ['text' => 'Impossible de supprimer le lien de partage.', 'context' => 'Validation or action feedback in api/shares/delete.php'],
        'lien_supprime' => ['text' => 'Lien supprimé.', 'context' => 'Validation or action feedback in api/shares/delete.php'],
        'partage' => ['text' => 'Partage', 'context' => 'Interface label in api/shares/popup.php'],
        'partager_ce_contexte' => ['text' => 'Partager ce contexte', 'context' => 'Interface label in api/shares/popup.php'],
        'le_lien_demarrera_sur' => ['text' => 'Le lien démarrera sur', 'context' => 'Interface label in api/shares/popup.php'],
        'et_pourra_etre_transmis_a_des_personnes_externes' => ['text' => 'et pourra être transmis à des personnes externes.', 'context' => 'Interface label in api/shares/popup.php'],
        'liens_existants' => ['text' => 'Liens existants', 'context' => 'Interface label in api/shares/popup.php'],
        'cree_le' => ['text' => 'Créé le', 'context' => 'Interface label in api/shares/popup.php'],
        'expire_le' => ['text' => 'Expire le', 'context' => 'Interface label in api/shares/popup.php'],
        'sans_expiration' => ['text' => 'Sans expiration', 'context' => 'Interface label in api/shares/popup.php'],
        'expire' => ['text' => 'Expiré', 'context' => 'Interface label in api/shares/popup.php'],
        'mot_de_passe' => ['text' => 'Mot de passe', 'context' => 'Interface label in api/shares/popup.php'],
        'structure' => ['text' => 'Structure', 'context' => 'Interface label in api/shares/popup.php'],
        'personnes' => ['text' => 'Personnes', 'context' => 'Interface label in api/shares/popup.php'],
        'detail' => ['text' => 'Détail', 'context' => 'Interface label in api/shares/popup.php'],
        'copier' => ['text' => 'Copier', 'context' => 'Interface label in api/shares/popup.php'],
        'modifier' => ['text' => 'Modifier', 'context' => 'Interface label in api/shares/popup.php'],
        'supprimer' => ['text' => 'Supprimer', 'context' => 'Interface label in api/shares/popup.php'],
        'nouveau_lien' => ['text' => 'Nouveau lien', 'context' => 'Interface label in api/shares/popup.php'],
        'libelle_interne' => ['text' => 'Libellé interne', 'context' => 'Interface label in api/shares/popup.php'],
        'ce_libelle_est_interne_et_servira_a_retrouver_le_lien' => ['text' => 'Ce libellé est interne et servira à retrouver le lien.', 'context' => 'Interface label in api/shares/popup.php'],
        'expiration' => ['text' => 'Expiration', 'context' => 'Interface label in api/shares/popup.php'],
        'laisse_vide_pour_un_lien_sans_date_de_fin' => ['text' => 'Laissez vide pour un lien sans date de fin.', 'context' => 'Interface label in api/shares/popup.php'],
        'mot_de_passe_optionnel' => ['text' => 'Mot de passe optionnel', 'context' => 'Interface label in api/shares/popup.php'],
        'si_un_mot_de_passe_est_defini_il_sera_demande_a_l_ouverture_du_lien' => ['text' => 'Si un mot de passe est défini, il sera demandé à l’ouverture du lien.', 'context' => 'Interface label in api/shares/popup.php'],
        'supprimer_le_mot_de_passe_actuel' => ['text' => 'Supprimer le mot de passe actuel', 'context' => 'Interface label in api/shares/popup.php'],
        'laisse_le_champ_mot_de_passe_vide_et_coche_ceci_pour_retirer_la_protection_existante' => ['text' => 'Laissez le mot de passe vide et cochez cette case pour retirer la protection existante.', 'context' => 'Interface label in api/shares/popup.php'],
        'voir_la_structure' => ['text' => 'Voir la structure', 'context' => 'Interface label in api/shares/popup.php'],
        'autorise_l_affichage_de_la_structure_et_du_detail_des_espaces' => ['text' => 'Autorise l’affichage de la structure et du détail des espaces.', 'context' => 'Interface label in api/shares/popup.php'],
        'voir_les_personnes' => ['text' => 'Voir les personnes', 'context' => 'Interface label in api/shares/popup.php'],
        'autorise_l_affichage_des_membres_visibles_dans_le_contexte_partage' => ['text' => 'Autorise l’affichage des membres visibles dans le contexte partagé.', 'context' => 'Interface label in api/shares/popup.php'],
        'voir_le_detail_des_personnes' => ['text' => 'Voir le détail des personnes', 'context' => 'Interface label in api/shares/popup.php'],
        'autorise_l_ouverture_de_la_popup_detail_d_une_personne_visible' => ['text' => 'Autorise l’ouverture de la popup détail d’une personne visible.', 'context' => 'Interface label in api/shares/popup.php'],
        'retour_a_la_liste' => ['text' => 'Retour à la liste', 'context' => 'Interface label in api/shares/popup.php'],
        'creer_le_lien' => ['text' => 'Créer le lien', 'context' => 'Interface label in api/shares/popup.php'],
    ];
    return $sourceLang;
}

function omoSharesUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoSharesUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_shares_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
