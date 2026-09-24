<?php

function commonPermissionEditorSourceLang(): array
{
    return [
        'search' => ['text' => 'Rechercher un droit ou une application', 'context' => 'Permission editor search field.'],
        'assigned' => ['text' => 'Droits attribués uniquement', 'context' => 'Filter for local or inherited permission assignments.'],
        'expand' => ['text' => 'Tout déplier', 'context' => 'Expand permission application groups.'],
        'collapse' => ['text' => 'Tout replier', 'context' => 'Collapse permission application groups.'],
        'empty' => ['text' => 'Aucun droit ne correspond à cette recherche.', 'context' => 'Empty permission filter results.'],
        'inherited' => ['text' => 'Hérité', 'context' => 'Read-only permission inherited from a template.'],
        'details' => ['text' => 'Description et code du droit', 'context' => 'Expandable technical information for a permission.'],
        'profile_legend' => ['text' => 'M : Membres · A : Admin · C : Collectif', 'context' => 'Sticky legend for permission profile abbreviations.'],
        'help' => ['text' => 'Choisissez le profil puis les portées de chaque droit. Les droits hérités s’ajoutent aux droits locaux. Un droit non configuré dans l’organisation reste ouvert aux membres. Le mode admin doit être activé pour donner tous les droits.', 'context' => 'Permission editor explanation of scope, inheritance, default access and admin override.'],
    ];
}
