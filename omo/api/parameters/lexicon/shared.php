<?php

function omoLexiconSourceLang(): array
{
    static $sourceLang = null;

    if (is_array($sourceLang)) {
        return $sourceLang;
    }

    $sourceLang = array(
        'parameters.lexicon.title' => array(
            'text' => 'Lexique de l organisation',
            'context' => 'Title of the organization lexicon editor.',
        ),
        'parameters.lexicon.description' => array(
            'text' => 'Adaptez quelques termes affiches dans l interface pour cette organisation.',
            'context' => 'Description of the organization lexicon editor.',
        ),
        'parameters.lexicon.term.tension.label' => array(
            'text' => 'Tension',
            'context' => 'Label of the organization lexicon tension term field.',
        ),
        'parameters.lexicon.term.tension.help' => array(
            'text' => 'Ce terme est utilise dans le bouton et le titre de declaration.',
            'context' => 'Help text for the organization lexicon tension term field.',
        ),
        'parameters.lexicon.term.tension.article' => array(
            'text' => 'Article',
            'context' => 'Label of the article field for the organization lexicon tension term.',
        ),
        'parameters.lexicon.term.tension.article_help' => array(
            'text' => 'Exemple : une pour "une tension", un pour "un point de vigilance".',
            'context' => 'Help text for the article field of the organization lexicon tension term.',
        ),
        'parameters.lexicon.term.admin.label' => array(
            'text' => 'Admin',
            'context' => 'Label of the organization lexicon admin term field.',
        ),
        'parameters.lexicon.term.admin.help' => array(
			'text' => 'Ce terme est utilise dans les libelles de gestion de l organisation et des holons.',
			'context' => 'Help text for the organization lexicon admin term field.',
        ),
        'parameters.lexicon.action.save' => array(
            'text' => 'Enregistrer',
            'context' => 'Save button label in the organization lexicon editor.',
        ),
        'parameters.lexicon.action.reset' => array(
            'text' => 'Restaurer les valeurs par defaut',
            'context' => 'Reset button label in the organization lexicon editor.',
        ),
        'parameters.lexicon.status.saved' => array(
            'text' => 'Lexique enregistre.',
            'context' => 'Success message shown after saving the organization lexicon.',
        ),
        'parameters.lexicon.status.error' => array(
            'text' => 'Impossible d enregistrer le lexique.',
            'context' => 'Error message shown when saving the organization lexicon fails.',
        ),
        'parameters.lexicon.error.organization' => array(
            'text' => 'Organisation introuvable.',
            'context' => 'Error shown when the current organization cannot be loaded.',
        ),
        'parameters.lexicon.error.access' => array(
            'text' => 'Vous devez pouvoir modifier l organisation pour gerer son lexique.',
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
