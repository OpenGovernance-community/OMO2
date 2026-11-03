<?php

function omoTutorialSettingsT(string $key): string
{
    static $lang = null;
    $sourceLang = [
        'title' => ['text' => 'Tutoriels', 'context' => 'Organization tutorial settings title.'],
        'hint' => ['text' => "Les videos utilisent leur lecteur habituel par defaut. Ces reglages s'appliquent aux parcours consultes dans cette organisation et a ses parcours publics ouverts hors organisation.", 'context' => 'Tutorial video settings scope and default.'],
        'custom' => ['text' => 'Activer le layout video personnalise', 'context' => 'Enable custom video controls and mask.'],
        'supported' => ['text' => "Le layout personnalise utilise le lecteur Vimeo sans commandes natives, un masque et des commandes aux couleurs de l'organisation. Les autres hebergeurs conservent leur lecteur habituel.", 'context' => 'Supported custom video provider explanation.'],
        'mask' => ['text' => 'Image du masque', 'context' => 'Custom video mask image URL label.'],
        'mask_help' => ['text' => "Adresse HTTPS ou chemin local d'une image transparente. Laissez vide pour reutiliser le masque actuel.", 'context' => 'Custom video mask URL guidance.'],
        'save' => ['text' => 'Enregistrer', 'context' => 'Save tutorial settings.'],
        'saved' => ['text' => 'Les reglages des tutoriels ont ete enregistres.', 'context' => 'Tutorial settings success notification.'],
        'error' => ['text' => "Impossible d'enregistrer les reglages des tutoriels.", 'context' => 'Tutorial settings failure notification.'],
        'invalid' => ['text' => 'Indiquez une adresse HTTPS ou un chemin local valide pour le masque.', 'context' => 'Tutorial mask validation error.'],
        'forbidden' => ['text' => 'Activez le mode Admin de cette organisation pour modifier les tutoriels.', 'context' => 'Tutorial settings authorization error.'],
        'organization' => ['text' => 'Organisation introuvable.', 'context' => 'Tutorial settings organization lookup error.'],
        'csrf' => ['text' => 'Rechargez cet ecran, puis reessayez.', 'context' => 'Expired tutorial settings form.'],
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
