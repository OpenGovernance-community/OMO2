<?php

function omoCalendarSettingsT(string $key, array $replace = []): string
{
    static $lang = null;
    $sourceLang = [
        'title' => ['text' => 'Horaires par defaut de l organisation', 'context' => 'Organization calendar settings heading.'],
        'hint' => ['text' => 'Ces horaires sont utilises chaque jour pour calculer les disponibilites et leur niveau lorsque la personne n a pas d horaires personnels actifs dans la prise de rendez-vous. Les rendez-vous OMO et les agendas externes restent pris en compte.', 'context' => 'Fallback working hours explanation.'],
        'step' => ['text' => 'Horaires par tranches de 30 minutes. Les horaires personnels actifs, avec leurs jours et pauses, restent prioritaires.', 'context' => 'Calendar settings precision and precedence.'],
        'start' => ['text' => 'Debut de journee', 'context' => 'Default day start label.'],
        'end' => ['text' => 'Fin de journee', 'context' => 'Default day end label.'],
        'save' => ['text' => 'Enregistrer', 'context' => 'Save organization calendar settings.'],
        'saved' => ['text' => 'Les horaires du calendrier ont ete enregistres.', 'context' => 'Calendar settings saved notification.'],
        'error' => ['text' => 'Impossible d enregistrer les horaires du calendrier.', 'context' => 'Calendar settings save failure.'],
        'invalid' => ['text' => 'La fin de journee doit etre apres le debut. Choisissez des horaires par tranches de 30 minutes.', 'context' => 'Invalid default calendar hours.'],
        'forbidden' => ['text' => 'Activez le mode Admin de cette organisation pour modifier les parametres du calendrier.', 'context' => 'Calendar settings authorization error.'],
        'organization' => ['text' => 'Organisation introuvable.', 'context' => 'Calendar settings organization lookup error.'],
        'csrf' => ['text' => 'Rechargez cet ecran, puis reessayez.', 'context' => 'Expired or cross-organization calendar settings form.'],
    ];
    $lang ??= omoLoadTranslationBundle('omo_parameters_calendar', $sourceLang);
    return t($key, $replace, $lang, $sourceLang);
}

function omoCalendarSettingsCanManage(int $organizationId): bool
{
    return $organizationId > 0 && commonGetCurrentUserId() > 0 && (commonCurrentUserIsSiteAdminModeEnabled()
        || (commonCurrentUserCanUseAdminMode($organizationId) && commonCurrentUserIsAdminModeEnabled($organizationId)));
}
