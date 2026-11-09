<?php

function commonExternalCalendarT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'copy.l_extension_curl_est_indisponible_sur_le_serveur' => ['text' => 'L’extension cURL est indisponible sur le serveur.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.lecture_du_flux_ics_impossible_reseau_certificat_ou_reponse_trop_volum' => ['text' => 'Lecture du flux ICS impossible (réseau, certificat ou réponse trop volumineuse).', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.redirection_du_flux_ics_refusee' => ['text' => 'Redirection du flux ICS refusée.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.le_flux_ics_a_repondu_avec_le_code_http_statuscode' => ['text' => 'Le flux ICS a répondu avec le code HTTP {statusCode}.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.le_serveur_caldav_a_repondu_avec_le_code_http_statuscode' => ['text' => 'Le serveur CalDAV a répondu avec le code HTTP {statusCode}.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.reponse_caldav_xml_invalide_ou_extension_dom_indisponible' => ['text' => 'Réponse CalDAV XML invalide ou extension DOM indisponible.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.le_serveur_ne_renvoie_pas_une_reponse_caldav_valide' => ['text' => 'Le serveur ne renvoie pas une réponse CalDAV valide.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.trop_d_agendas_utilisez_une_adresse_caldav_plus_precise' => ['text' => 'Trop d’agendas. Utilisez une adresse CalDAV plus précise.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.decouverte_caldav_trop_longue_utilisez_une_adresse_plus_precise' => ['text' => 'Découverte CalDAV trop longue. Utilisez une adresse plus précise.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.evenement_externe' => ['text' => 'Événement externe', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.l_extension_dom_est_indisponible_sur_le_serveur' => ['text' => 'L’extension DOM est indisponible sur le serveur.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.la_reponse_caldav_est_invalide' => ['text' => 'La réponse CalDAV est invalide.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.reponse_calendrier_incomplete' => ['text' => 'Réponse calendrier incomplète.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.les_disponibilites_ne_peuvent_pas_etre_verifiees' => ['text' => 'Les disponibilités ne peuvent pas être vérifiées.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.la_configuration_du_calendrier_est_incomplete_ou_ne_peut_pas_etre_lue' => ['text' => 'La configuration du calendrier est incomplète ou ne peut pas être lue.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.la_reponse_caldav_ne_peut_pas_etre_lue' => ['text' => 'La réponse CalDAV ne peut pas être lue.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.la_synchronisation_du_calendrier_a_echoue' => ['text' => 'La synchronisation du calendrier a échoué.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.synchronisation_indisponible_donnees_existantes_conservees' => ['text' => 'Synchronisation indisponible. Données existantes conservées.', 'context' => 'Interface text in common/external_calendar.php'],
        'copy.le_flux_ics_contient_trop_d_evenements' => ['text' => 'Le flux ICS contient trop d’événements.', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.un_evenement_ics_n_a_pas_de_debut' => ['text' => 'Un événement ICS n’a pas de début.', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.une_duree_ics_est_invalide' => ['text' => 'Une durée ICS est invalide.', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.regle_de_recurrence_ics_invalide' => ['text' => 'Règle de récurrence ICS invalide.', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.date_de_fin_de_recurrence_ics_incompatible' => ['text' => 'Date de fin de récurrence ICS incompatible.', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.un_evenement_ics_n_a_pas_d_identifiant' => ['text' => 'Un événement ICS n’a pas d’identifiant.', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.rdate_en_periode_non_pris_en_charge' => ['text' => 'RDATE en période non pris en charge.', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.cause_non_precisee' => ['text' => 'Cause non précisée', 'context' => 'Interface text in common/external_calendar_ics.php'],
        'copy.le_flux_ics_ne_peut_pas_etre_lu_completement_detail_technique_value_de' => ['text' => 'Le flux ICS ne peut pas être lu complètement. Détail technique ({value}) : {detail}', 'context' => 'Interface text in common/external_calendar_ics.php'],
    ];
    require_once __DIR__ . '/../translation_bundles.php';
    static $bundles = [];
    $locale = translationBundleResolveRequestLocale();
    $bundle = class_exists(\dbObject\TranslationBundle::class)
        ? ($bundles[$locale] ??= loadTranslationBundle('common_calendar_external_translations', $locale, $sourceLang)) : $sourceLang;
    return t($key, $variables, $bundle, $sourceLang);
}
