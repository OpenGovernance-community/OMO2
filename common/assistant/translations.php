<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonAssistantT(string $key, array $variables = []): string
{
    static $bundle;
    if ($bundle === null) {
        $sourceLang = [
            'title' => ['text' => 'Assistant', 'context' => 'Organization assistant chat title.'],
            'open' => ['text' => 'Poser une question sur mon organisation', 'context' => 'Floating assistant button accessible label.'],
            'close' => ['text' => 'Fermer', 'context' => 'Close the assistant panel.'],
            'new' => ['text' => 'Nouvelle discussion', 'context' => 'Clear the assistant conversation.'],
            'question' => ['text' => 'Votre question', 'context' => 'Chat input accessible label and placeholder.'],
            'help' => ['text' => 'Aide de l assistant', 'context' => 'Accessible label of the assistant help capsule.'],
            'resize' => ['text' => 'Redimensionner le chat (glisser ou utiliser les fleches gauche et droite)', 'context' => 'Accessible label and tooltip for the chat left resize handle.'],
            'send' => ['text' => 'Envoyer', 'context' => 'Submit an assistant question.'],
            'copy_csv' => ['text' => 'Copier le tableau en CSV', 'context' => 'Tooltip and accessible label for copying a chat table.'],
            'copied_csv' => ['text' => 'Tableau copie en CSV.', 'context' => 'Notification after a table is copied to the clipboard.'],
            'copy_error' => ['text' => 'Copie impossible. Verifiez l autorisation du presse-papier dans votre navigateur.', 'context' => 'Notification when copying a table to the clipboard fails.'],
            'welcome' => ['text' => 'Que souhaitez-vous savoir sur votre organisation ?', 'context' => 'Introduction inside the assistant help capsule.'],
            'role' => ['text' => 'Qui est en charge de ce role ?', 'context' => 'Suggested organization assistant question.'],
            'meeting' => ['text' => 'Quand a lieu la prochaine reunion Suisse ?', 'context' => 'Suggested organization assistant question.'],
            'privacy' => ['text' => 'Lecture seule, avec vos droits actuels. Les questions et les extraits utiles sont transmis a l IA configuree.', 'context' => 'Help capsule explanation of access and data handling.'],
            'working' => ['text' => 'Je consulte les informations accessibles...', 'context' => 'Assistant request in progress.'],
            'restoring' => ['text' => 'Restauration de la discussion...', 'context' => 'Chat session history is loading.'],
            'restore_error' => ['text' => 'Impossible de restaurer la discussion. Rechargez la page pour reessayer ou ouvrez une nouvelle discussion.', 'context' => 'Chat history could not be retrieved; sending stays disabled to avoid losing context.'],
            'interrupted' => ['text' => 'La reponse n a pas abouti. Votre question est conservee dans le champ de saisie.', 'context' => 'An interrupted question is restored as a draft without resending it.'],
            'you' => ['text' => 'Vous', 'context' => 'User message author.'],
            'sources' => ['text' => 'Pages consultees', 'context' => 'Links to source pages below assistant answer.'],
            'access' => ['text' => 'Connectez-vous avec un compte membre pour utiliser cet assistant.', 'context' => 'Assistant authentication error.'],
            'context' => ['text' => 'Le contexte a change. Rechargez la page pour discuter dans cette organisation.', 'context' => 'Assistant session or organization changed.'],
            'unavailable' => ['text' => 'L assistant est indisponible pour ce compte ou la configuration IA actuelle.', 'context' => 'AI configuration or entitlement unavailable.'],
            'invalid' => ['text' => 'Saisissez une question de 1 a 4000 caracteres.', 'context' => 'Assistant question validation error.'],
            'busy' => ['text' => 'Une reponse est deja en cours. Patientez avant de reessayer.', 'context' => 'Concurrent assistant turn rejected.'],
            'rate' => ['text' => 'Trop de questions rapprochees. Reessayez dans quelques minutes.', 'context' => 'Assistant rate limit.'],
            'provider' => ['text' => 'L IA n a pas pu repondre. Verifiez que le modele configure prend en charge les outils, puis reessayez.', 'context' => 'Assistant provider error without leaking raw API errors.'],
            'limit' => ['text' => 'Cette recherche est trop large. Precisez le role, le groupe ou la periode, puis reessayez.', 'context' => 'Assistant bounded tool loop exhausted.'],
            'error' => ['text' => 'Impossible de terminer la recherche. Vous pouvez reessayer.', 'context' => 'Unexpected assistant or network error.'],
        ];
        $bundle = [loadTranslationBundle('common_assistant', commonAuthGetTranslationLocale(), $sourceLang), $sourceLang];
    }
    return t($key, $variables, $bundle[0], $bundle[1]);
}
