<?php

function omoLexiconSourceLang(): array
{
    static $sourceLang = null;

    if (is_array($sourceLang)) {
        return $sourceLang;
    }

    $sourceLang = array(
        'parameters.lexicon.term.type1.label' => ['text' => 'Type 1', 'context' => 'Property type name in the organization lexicon'],
        'parameters.lexicon.term.type2.label' => ['text' => 'Type 2', 'context' => 'Property type name in the organization lexicon'],
        'parameters.lexicon.term.type3.label' => ['text' => 'Type 3', 'context' => 'Property type name in the organization lexicon'],
        'parameters.lexicon.term.type4.label' => ['text' => 'Type 4', 'context' => 'Property type name in the organization lexicon'],
        'parameters.lexicon.term.type5.label' => ['text' => 'Type 5', 'context' => 'Property type name in the organization lexicon'],
        'parameters.lexicon.type.enabled' => ['text' => 'Activer ce type', 'context' => 'Checkbox enabling a property type in this organization'],
        'parameters.lexicon.type.name' => ['text' => "Nom dans l'interface", 'context' => 'Label of the custom name input for each property type'],
        'parameters.lexicon.section.properties.help' => ['text' => "Cochez les types que vous souhaitez utiliser et personnalisez leur nom. Un type d\u{00e9}sactiv\u{00e9} est masqu\u{00e9} dans les choix, les \u{00e9}crans et les droits ; ses donn\u{00e9}es sont conserv\u{00e9}es.", 'context' => 'Explanation of property type activation in the organization lexicon'],
        'parameters.lexicon.section.properties' => ['text' => 'Types de proprietes', 'context' => 'Property types section in the organization lexicon'],
        'parameters.lexicon.title' => array(
            'text' => 'Lexique de l’organisation',
            'context' => 'Title of the organization lexicon editor.',
        ),
        'parameters.lexicon.description' => array(
            'text' => 'Adaptez quelques termes affichés dans l’interface pour cette organisation.',
            'context' => 'Description of the organization lexicon editor.',
        ),
        'parameters.lexicon.section.structure.title' => array(
            'text' => 'Éléments de structure',
            'context' => 'Title of the structural term section in the organization lexicon editor.',
        ),
        'parameters.lexicon.section.structure.help' => array(
            'text' => 'Personnalisez les noms affichés pour les éléments de la structure.',
            'context' => 'Help text for the structural term section in the organization lexicon editor.',
        ),
        'parameters.lexicon.term.space.label' => array(
            'text' => 'Espace',
            'context' => 'Label of the generic structure element term field.',
        ),
        'parameters.lexicon.term.circle.label' => array(
            'text' => 'Cercle',
            'context' => 'Label of the circle term field.',
        ),
        'parameters.lexicon.term.role.label' => array(
            'text' => 'Rôle',
            'context' => 'Label of the role term field.',
        ),
        'parameters.lexicon.term.group.label' => array(
            'text' => 'Groupe',
            'context' => 'Label of the group term field.',
        ),
        'parameters.lexicon.term.tension.label' => array(
            'text' => 'Tension',
            'context' => 'Label of the organization lexicon tension term field.',
        ),
        'parameters.lexicon.term.tension.help' => array(
            'text' => 'Ce terme est utilisé dans le bouton et le titre de déclaration.',
            'context' => 'Help text for the organization lexicon tension term field.',
        ),
        'parameters.lexicon.term.tension.article' => array(
            'text' => 'Article',
            'context' => 'Label of the article field for the organization lexicon tension term.',
        ),
        'parameters.lexicon.term.tension.article_help' => array(
            'text' => 'Exemple : « une » pour « une tension », « un » pour « un point de vigilance ».',
            'context' => 'Help text for the article field of the organization lexicon tension term.',
        ),
        'parameters.lexicon.term.admin.label' => array(
            'text' => 'Admin',
            'context' => 'Label of the organization lexicon admin term field.',
        ),
        'parameters.lexicon.term.admin.help' => array(
			'text' => 'Ce terme est utilisé dans les libellés de gestion de l’organisation et des éléments.',
			'context' => 'Help text for the organization lexicon admin term field.',
        ),
        'parameters.lexicon.action.save' => array(
            'text' => 'Enregistrer',
            'context' => 'Save button label in the organization lexicon editor.',
        ),
        'parameters.lexicon.action.reset' => array(
            'text' => 'Restaurer les valeurs par défaut',
            'context' => 'Reset button label in the organization lexicon editor.',
        ),
        'parameters.lexicon.status.saved' => array(
            'text' => 'Lexique enregistré.',
            'context' => 'Success message shown after saving the organization lexicon.',
        ),
        'parameters.lexicon.status.error' => array(
            'text' => 'Impossible d’enregistrer le lexique.',
            'context' => 'Error message shown when saving the organization lexicon fails.',
        ),
        'parameters.lexicon.error.organization' => array(
            'text' => 'Organisation introuvable.',
            'context' => 'Error shown when the current organization cannot be loaded.',
        ),
        'parameters.lexicon.error.access' => array(
            'text' => 'Vous devez pouvoir modifier l’organisation pour gérer son lexique.',
            'context' => 'Error shown when the current user cannot edit the organization lexicon.',
        ),
    );

    return $sourceLang;
}

function omoLexiconLang(): array
{
    static $lang = null;

    if ($lang === null) {
        $lang = omoLoadTranslationBundle('omo_parameters_lexicon', omoLexiconSourceLang());
    }

    return $lang;
}

function omoLexiconT(string $key, array $replace = array()): string
{
    return t($key, $replace, omoLexiconLang(), omoLexiconSourceLang());
}

function omoLexiconEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
