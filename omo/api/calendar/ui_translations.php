<?php

require_once __DIR__ . '/../../translations.php';

function omoCalendarUiSourceLang(): array
{
    static $sourceLang = [
        'acces_refuse' => ['text' => 'Accès refusé.', 'context' => 'Validation or action feedback in api/calendar/connect.php, api/calendar/create.php, api/calendar/delete.php'],
        'calendrier_introuvable' => ['text' => 'Calendrier introuvable.', 'context' => 'Validation or action feedback in api/calendar/connect.php'],
        'organisation_invalide' => ['text' => 'Organisation invalide.', 'context' => 'Validation or action feedback in api/calendar/create.php, api/calendar/delete.php, api/calendar/index.php'],
        'evenement_invalide' => ['text' => 'Événement invalide.', 'context' => 'Validation or action feedback in api/calendar/create.php, api/calendar/delete.php'],
        'modele_de_document_invalide_ou_inaccessible' => ['text' => 'Modèle de document invalide ou inaccessible.', 'context' => 'Validation or action feedback in api/calendar/create.php'],
        'methode_non_autorisee' => ['text' => 'Méthode non autorisée.', 'context' => 'Validation or action feedback in api/calendar/delete.php'],
        'vous_n_avez_pas_le_droit_de_supprimer_cet_evenement' => ['text' => 'Vous n\'avez pas le droit de supprimer cet événement.', 'context' => 'Validation or action feedback in api/calendar/delete.php'],
        'impossible_de_supprimer_cet_evenement' => ['text' => 'Impossible de supprimer cet événement.', 'context' => 'Validation or action feedback in api/calendar/delete.php'],
        'evenement_supprime' => ['text' => 'Événement supprimé.', 'context' => 'Validation or action feedback in api/calendar/delete.php'],
        'synchronisation_impossible' => ['text' => 'Synchronisation impossible.', 'context' => 'Validation or action feedback in api/calendar/external_calendars.php'],
        'organisation_introuvable' => ['text' => 'Organisation introuvable.', 'context' => 'Interface label in api/calendar/index.php'],
        'acces_refuse_a_cette_organisation' => ['text' => 'Accès refusé à cette organisation.', 'context' => 'Interface label in api/calendar/index.php'],
        'calendrier' => ['text' => 'Calendrier', 'context' => 'Interface label in api/calendar/invitations_popup.php'],
        'contexte_d_invitations_invalide' => ['text' => 'Contexte d\'invitations invalide.', 'context' => 'Validation or action feedback in api/calendar/invitations_shared.php'],
        'un_espace_selectionne_est_invalide' => ['text' => 'Un espace sélectionné est invalide.', 'context' => 'Validation or action feedback in api/calendar/invitations_shared.php'],
        'un_membre_selectionne_est_invalide' => ['text' => 'Un membre sélectionné est invalide.', 'context' => 'Validation or action feedback in api/calendar/invitations_shared.php'],
        'contexte_d_invitations_invalide_2' => ['text' => 'Contexte d’invitations invalide.', 'context' => 'Validation or action feedback in api/calendar/invitations_shared.php'],
        'impossible_d_enregistrer_une_invitation_d_evenement' => ['text' => 'Impossible d\'enregistrer une invitation d\'événement.', 'context' => 'Validation or action feedback in api/calendar/invitations_shared.php'],
        'impossible_de_retirer_une_ancienne_invitation_d_evenement' => ['text' => 'Impossible de retirer une ancienne invitation d\'événement.', 'context' => 'Validation or action feedback in api/calendar/invitations_shared.php'],
    ];
    return $sourceLang;
}

function omoCalendarUiT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = omoCalendarUiSourceLang();
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_calendar_ui', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}
