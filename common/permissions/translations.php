<?php

function commonPermissionEditorSourceLang(): array
{
    return [
        'search' => ['text' => 'Rechercher un droit ou une application', 'context' => 'Permission editor search field.'],
        'assigned' => ['text' => 'Droits attribues uniquement', 'context' => 'Filter for local or inherited permission assignments.'],
        'expand' => ['text' => 'Tout deplier', 'context' => 'Expand permission application groups.'],
        'collapse' => ['text' => 'Tout replier', 'context' => 'Collapse permission application groups.'],
        'empty' => ['text' => 'Aucun droit ne correspond a cette recherche.', 'context' => 'Empty permission filter results.'],
        'inherited' => ['text' => 'Herite', 'context' => 'Read-only permission inherited from a template.'],
        'details' => ['text' => 'Description et code du droit', 'context' => 'Expandable technical information for a permission.'],
        'help' => ['text' => 'Choisissez le profil puis les portees de chaque droit. Les droits herites s ajoutent aux droits locaux. Un droit non configure dans l organisation reste ouvert aux membres. Le mode admin doit etre active pour donner tous les droits.', 'context' => 'Permission editor explanation of scope, inheritance, default access and admin override.'],
    ];
}
