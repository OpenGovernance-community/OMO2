<?php

function commonUserProfileT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.element_exact_value' => ['text' => 'Élément exact: {value}', 'context' => 'Interface text in common/user_permission_ui.php'],
        'copy.element_exact' => ['text' => 'Élément exact', 'context' => 'Interface text in common/user_permission_ui.php'],
        'copy.portee_inconnue' => ['text' => 'Portée inconnue', 'context' => 'Interface text in common/user_permission_ui.php'],
        'copy.evenement_aujourd_hui' => ['text' => 'Événement aujourd\'hui', 'context' => 'Interface text in common/user_profile_ui.php'],
        'copy.evenement_dans' => ['text' => 'Événement dans', 'context' => 'Interface text in common/user_profile_ui.php'],
        'copy.evenement_en' => ['text' => 'Événement en', 'context' => 'Interface text in common/user_profile_ui.php'],
        'copy.role_roleid' => ['text' => 'Rôle {roleId}', 'context' => 'Interface text in popup/user.php'],
        'copy.ouvrir_le_role_rolelabel' => ['text' => 'Ouvrir le rôle {roleLabel}', 'context' => 'Interface text in popup/user.php'],
        'copy.actions_pour_le_role_rolelabel' => ['text' => 'Actions pour le rôle {roleLabel}', 'context' => 'Interface text in popup/user.php'],
        'copy.vous_pouvez_voir_que_cette_invitation_est_en_attente_mais_seuls_les_re' => ['text' => 'Vous pouvez voir que cette invitation est en attente, mais seuls les responsables du contexte peuvent la gérer.', 'context' => 'Interface text in popup/user.php'],
        'copy.vous_pouvez_renvoyer_le_message_d_invitation_a_cette_adresse_e_mail' => ['text' => 'Vous pouvez renvoyer le message d’invitation à cette adresse e-mail.', 'context' => 'Interface text in popup/user.php'],
        'copy.invitation_non_envoyee' => ['text' => 'Invitation non envoyée', 'context' => 'Interface text in popup/user.php'],
        'copy.le_compte_est_prepare_dans_cette_organisation_sans_que_la_personne_ait' => ['text' => 'Le compte est préparé dans cette organisation, sans que la personne ait encore été informée de son existence.', 'context' => 'Interface text in popup/user.php'],
        'copy.modifier_votre_recommandation' => ['text' => 'Modifier votre recommandation.', 'context' => 'Interface text in popup/user.php'],
        'copy.ajouter_une_recommandation_discrete' => ['text' => 'Ajouter une recommandation discrète.', 'context' => 'Interface text in popup/user.php'],
    ];
    require_once __DIR__ . '/../translation_bundles.php';
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= loadTranslationBundle('common_team_profile_translations', $locale, $sourceLang)) : $sourceLang;
    return t($key, $variables, $bundle, $sourceLang);
}
