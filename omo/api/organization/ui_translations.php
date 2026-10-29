<?php

require_once __DIR__ . '/../../translations.php';

function omoOrganizationUiSourceLang(): array
{
    static $sourceLang = [
        'impossible_d_envoyer_cette_demande' => ['text' => 'Impossible d\'envoyer cette demande.', 'context' => 'Validation or action feedback in api/organization/access_request_popup.php'],
        'demande_envoyee' => ['text' => 'Demande envoyée.', 'context' => 'Validation or action feedback in api/organization/access_request_popup.php'],
        'vous_devez_etre_connecte_pour_envoyer_une_demande_d_acces' => ['text' => 'Vous devez être connecté pour envoyer une demande d’accès.', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'l_organisation_demandee_est_introuvable' => ['text' => 'L’organisation demandée est introuvable.', 'context' => 'Interface label in api/organization/access_request_popup.php, api/organization/member_popup.php'],
        'votre_compte_a_deja_acces_a_cette_organisation' => ['text' => 'Votre compte a déjà accès à cette organisation.', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'organisation' => ['text' => 'Organisation', 'context' => 'Interface label in api/organization/access_request_popup.php, api/organization/member_popup.php'],
        'demande_d_acces' => ['text' => 'Demande d’accès', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'envoyez_un_court_message_aux_administrateurs_de' => ['text' => 'Envoyez un court message aux administrateurs de', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'pour_expliquer_pourquoi_vous_souhaitez_rejoindre_cet_espace' => ['text' => 'pour expliquer pourquoi vous souhaitez rejoindre cet espace.', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'une_demande_est_deja_en_attente_pour_cette_organisation_vous_pouvez_toutefois_mettre_a_jou' => ['text' => 'Une demande est déjà en attente pour cette organisation. Vous pouvez toutefois mettre à jour votre message ci-dessous et renvoyer la demande.', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'votre_message' => ['text' => 'Votre message', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'le_message_est_optionnel_mais_il_aide_les_administrateurs_a_comprendre_votre_demande' => ['text' => 'Le message est optionnel, mais il aide les administrateurs à comprendre votre demande.', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'envoyer_la_demande' => ['text' => 'Envoyer la demande', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'bonjour_je_souhaite_rejoindre_cette_organisation_pour' => ['text' => 'Bonjour, je souhaite rejoindre cette organisation pour...', 'context' => 'Interface label in api/organization/access_request_popup.php'],
        'impossible_d_ajouter_ce_membre' => ['text' => 'Impossible d\'ajouter ce membre.', 'context' => 'Validation or action feedback in api/organization/member_popup.php'],
        'membre_ajoute' => ['text' => 'Membre ajoute.', 'context' => 'Validation or action feedback in api/organization/member_popup.php'],
        'vous_devez_etre_connecte_a_une_organisation_pour_inviter_un_membre' => ['text' => 'Vous devez être connecté à une organisation pour inviter un membre.', 'context' => 'Interface label in api/organization/member_popup.php'],
        'vous_n_avez_pas_le_droit_d_ajouter_un_membre_dans_cette_organisation' => ['text' => 'Vous n’avez pas le droit d’ajouter un membre dans cette organisation.', 'context' => 'Interface label in api/organization/member_popup.php'],
        'ajouter_un_membre' => ['text' => 'Ajouter un membre', 'context' => 'Interface label in api/organization/member_popup.php'],
        'invitez_une_personne_dans' => ['text' => 'Invitez une personne dans', 'context' => 'Interface label in api/organization/member_popup.php'],
        'en_saisissant_son_adresse_e_mail' => ['text' => 'en saisissant son adresse e-mail.', 'context' => 'Interface label in api/organization/member_popup.php'],
        'adresse_e_mail' => ['text' => 'Adresse e-mail', 'context' => 'Interface label in api/organization/member_popup.php'],
        'si_cette_adresse_existe_deja_le_profil_existant_sera_rattache_a_l_organisation_sinon_une_i' => ['text' => 'Si cette adresse existe déjà, le profil existant sera rattaché à l’organisation. Sinon, une invitation sera préparée pour cette adresse.', 'context' => 'Interface label in api/organization/member_popup.php'],
        'inviter' => ['text' => 'Inviter', 'context' => 'Interface label in api/organization/member_popup.php'],
    ];
    return $sourceLang;
}

function omoOrganizationUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoOrganizationUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_organization_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
