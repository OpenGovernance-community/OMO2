<?php

function omoTutorialSettingsT(string $key): string
{
    static $lang = null;
    $sourceLang = [
        'title' => ['text' => 'Tutoriels', 'context' => 'Organization tutorial settings title.'],
        'hint' => ['text' => "Les vidéos utilisent leur lecteur habituel par défaut. Ces réglages s’appliquent aux parcours consultés dans cette organisation et à ses parcours publics ouverts en dehors de celle-ci.", 'context' => 'Tutorial video settings scope and default.'],
        'custom' => ['text' => 'Activer le lecteur vidéo personnalisé', 'context' => 'Enable custom video controls and mask.'],
        'supported' => ['text' => "Le lecteur personnalisé utilise Vimeo avec un masque et des commandes aux couleurs de l’organisation. Les autres hébergeurs conservent leur lecteur habituel.", 'context' => 'Supported custom video provider explanation.'],
        'mask' => ['text' => 'Image du masque', 'context' => 'Custom video mask image URL label.'],
        'mask_help' => ['text' => "Adresse HTTPS ou chemin local d’une image transparente. Laissez ce champ vide pour réutiliser le masque actuel.", 'context' => 'Custom video mask URL guidance.'],
        'save' => ['text' => 'Enregistrer', 'context' => 'Save tutorial settings.'],
        'saved' => ['text' => 'Les réglages des tutoriels ont été enregistrés.', 'context' => 'Tutorial settings success notification.'],
        'error' => ['text' => "Impossible d’enregistrer les réglages des tutoriels.", 'context' => 'Tutorial settings failure notification.'],
        'invalid' => ['text' => 'Indiquez une adresse HTTPS ou un chemin local valide pour le masque.', 'context' => 'Tutorial mask validation error.'],
        'forbidden' => ['text' => 'Activez le mode administrateur de cette organisation pour modifier les réglages des tutoriels.', 'context' => 'Tutorial settings authorization error.'],
        'organization' => ['text' => 'Organisation introuvable.', 'context' => 'Tutorial settings organization lookup error.'],
        'csrf' => ['text' => 'Rechargez cet écran, puis réessayez.', 'context' => 'Expired tutorial settings form.'],
    ];
    $lang ??= omoLoadTranslationBundle('omo_parameters_tutorials', $sourceLang);
    return t($key, [], $lang, $sourceLang);
}

function omoTutorialSettingsCanManage(int $organizationId): bool
{
    return $organizationId > 0 && commonGetCurrentUserId() > 0 && !commonGetCurrentShareLink()
        && (commonCurrentUserIsSiteAdminModeEnabled()
            || (commonCurrentUserCanUseAdminMode($organizationId) && commonCurrentUserIsAdminModeEnabled($organizationId)));
}
