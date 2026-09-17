<?php

function omoStructureDisplaySourceLang(): array
{
    static $sourceLang = null;

    if (is_array($sourceLang)) {
        return $sourceLang;
    }

    $sourceLang = array(
        'parameters.structure_display.title' => array('text' => 'Affichage de la structure', 'context' => 'Title of the organization structure display settings editor.'),
        'parameters.structure_display.description' => array('text' => 'Ces reglages s appliquent a la grande vue Structure et a sa mini-carte.', 'context' => 'Description of the organization structure display settings editor.'),
        'parameters.structure_display.section.visibility' => array('text' => 'Visibilite', 'context' => 'Section heading for structure display visibility settings.'),
        'parameters.structure_display.field.fade_step.label' => array('text' => 'Facteur d estompage par niveau', 'context' => 'Label for the structure depth fade step input.'),
        'parameters.structure_display.field.fade_step.help' => array('text' => '0 desactive l estompage. La valeur actuelle par defaut est 0.18.', 'context' => 'Help text for the structure depth fade step input.'),
        'parameters.structure_display.field.max_depth.label' => array('text' => 'Profondeur maximale sous le niveau courant', 'context' => 'Label for the maximum visible descendant depth input.'),
        'parameters.structure_display.field.max_depth.help' => array('text' => '0 affiche tous les descendants, comme aujourd hui.', 'context' => 'Help text for the maximum visible descendant depth input.'),
        'parameters.structure_display.section.labels' => array('text' => 'Libelles', 'context' => 'Section heading for structure display label settings.'),
        'parameters.structure_display.section.members' => array('text' => 'Personnes', 'context' => 'Section heading for terminal structure member display settings.'),
        'parameters.structure_display.field.label_auto_radius.label' => array('text' => 'Rayon minimal pour afficher un texte automatiquement', 'context' => 'Label for the minimum rendered holon radius for automatic labels.'),
        'parameters.structure_display.field.label_auto_radius.help' => array('text' => 'S applique aux textes affiches dans les roles et cercles. Valeur par defaut : 18 px.', 'context' => 'Help text for the automatic label threshold input.'),
        'parameters.structure_display.field.label_hover_radius.label' => array('text' => 'Rayon minimal pour afficher un texte au survol', 'context' => 'Label for the minimum rendered holon radius for hover labels.'),
        'parameters.structure_display.field.label_hover_radius.help' => array('text' => 'S applique au texte revele lorsque le pointeur passe sur un holon. Valeur par defaut : 18 px.', 'context' => 'Help text for the hover label threshold input.'),
        'parameters.structure_display.field.label_font_size.label' => array('text' => 'Taille minimale de police', 'context' => 'Label for the minimum font size input.'),
        'parameters.structure_display.field.label_font_size.help' => array('text' => '0 conserve les garde-fous actuels. Une valeur positive impose cette taille minimale en pixels.', 'context' => 'Help text for the minimum font size input.'),
        'parameters.structure_display.field.outline.label' => array('text' => 'Afficher un contour autour des textes', 'context' => 'Checkbox label controlling text outlines in structure views.'),
        'parameters.structure_display.field.outline.help' => array('text' => 'Le contour contraste avec le texte pour le garder lisible.', 'context' => 'Help text for the text outline checkbox.'),
        'parameters.structure_display.field.terminal_members.label' => array('text' => 'Afficher les personnes sur les elements terminaux selectionnes', 'context' => 'Checkbox label enabling member avatars on selected terminal structure elements.'),
        'parameters.structure_display.field.terminal_members.help' => array('text' => 'Dans la grande vue, les admins apparaissent au-dessus et les autres membres au-dessous, avec leur photo ou leurs initiales.', 'context' => 'Help text explaining the terminal member avatar display.'),
        'parameters.structure_display.action.save' => array('text' => 'Enregistrer', 'context' => 'Save button label for structure display settings.'),
        'parameters.structure_display.action.reset' => array('text' => 'Restaurer les valeurs actuelles', 'context' => 'Reset button label for structure display settings.'),
        'parameters.structure_display.status.saved' => array('text' => 'Affichage de la structure enregistre.', 'context' => 'Success message after saving structure display settings.'),
        'parameters.structure_display.status.error' => array('text' => 'Impossible d enregistrer cet affichage.', 'context' => 'Fallback error message after a failed structure display settings save.'),
        'parameters.structure_display.error.organization' => array('text' => 'Organisation introuvable.', 'context' => 'Error shown when the current organization cannot be loaded.'),
        'parameters.structure_display.error.structure' => array('text' => 'Aucune structure n est disponible pour cette organisation.', 'context' => 'Error shown when structure display settings cannot be used without a structure.'),
        'parameters.structure_display.error.admin_required' => array('text' => 'Cet affichage est reserve aux admins de cette organisation.', 'context' => 'Error shown when a non-admin opens structure display settings.'),
        'parameters.structure_display.error.admin_mode_required' => array('text' => 'Activez le mode Admin de l organisation pour modifier cet affichage.', 'context' => 'Error shown when an organization admin has not enabled admin mode.'),
    );

    return $sourceLang;
}

function omoStructureDisplayLang(): array
{
    static $lang = null;

    if ($lang === null) {
        $lang = omoLoadTranslationBundle('omo_parameters_structure_display', omoStructureDisplaySourceLang());
    }

    return $lang;
}

function omoStructureDisplayT(string $key, array $replace = array()): string
{
    return t($key, $replace, omoStructureDisplayLang(), omoStructureDisplaySourceLang());
}

function omoStructureDisplayEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function omoStructureDisplayAdminModeAccess($organizationId): array
{
    $organizationId = (int)$organizationId;
    if (commonCurrentUserIsSiteAdminModeEnabled()) {
        return array('status' => true);
    }

    if (!commonCurrentUserCanUseAdminMode($organizationId)) {
        return array('status' => false, 'message' => omoStructureDisplayT('parameters.structure_display.error.admin_required'));
    }

    if (!commonCurrentUserIsAdminModeEnabled($organizationId)) {
        return array('status' => false, 'message' => omoStructureDisplayT('parameters.structure_display.error.admin_mode_required'));
    }

    return array('status' => true);
}
