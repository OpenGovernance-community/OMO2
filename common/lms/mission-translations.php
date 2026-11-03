<?php
require_once dirname(__DIR__) . '/translation_bundles.php';

function commonLmsMissionSourceLang(): array
{
    return [
        'lms.mission_detail.error.access_denied' => ['text' => 'Accès refusé', 'context' => 'Error shown when the viewer cannot access the mission detail.'],
        'lms.mission_detail.error.not_found' => ['text' => 'Mission introuvable', 'context' => 'Error shown when the mission cannot be found.'],
        'lms.mission_detail.homeworks.title' => ['text' => 'Devoirs', 'context' => 'Section title listing mission homeworks.'],
        'lms.mission_detail.homeworks.status_done' => ['text' => 'Validé', 'context' => 'Status label shown when a homework is completed.'],
        'lms.mission_detail.homeworks.status_todo' => ['text' => 'À faire', 'context' => 'Status label shown when a homework remains to do.'],
        'lms.mission_detail.homeworks.expand' => ['text' => 'Détail de la tâche', 'context' => 'Button label used to open a homework detail.'],
        'lms.mission_detail.homeworks.collapse' => ['text' => 'Masquer le détail', 'context' => 'Button label used to collapse a homework detail.'],
        'lms.mission_detail.homeworks.empty_detail' => ['text' => 'Aucun détail supplémentaire.', 'context' => 'Fallback text shown when a homework has no extra detail.'],
        'lms.mission_detail.homeworks.help' => ['text' => 'Terminez tous les devoirs avant de poursuivre cette mission.', 'context' => 'Help text shown below the homework list.'],
        'lms.mission_detail.homeworks.mark_done' => ['text' => 'Valider la tâche', 'context' => 'Accessible label used to mark a homework as done.'],
        'lms.mission_detail.homeworks.mark_undone' => ['text' => 'Retirer la validation', 'context' => 'Accessible label used to unmark a completed homework.'],
        'lms.mission_detail.validation.unavailable' => ['text' => 'La validation de cette mission n’est pas disponible dans ce contexte.', 'context' => 'Message shown when mission validation cannot be used.'],
        'lms.mission_detail.validation.start_quiz' => ['text' => 'Commencer le quiz', 'context' => 'Button label used to start the quiz.'],
        'lms.mission_detail.validation.mark_read' => ['text' => 'Marquer comme lu', 'context' => 'Button label used to complete a mission without quiz.'],
        'lms.mission_detail.validation.scroll_to_end' => ['text' => "Faites défiler le contenu jusqu’en bas pour marquer cette mission comme lue.", 'context' => 'Instruction shown while read confirmation is locked until the mission content has been scrolled to the end.'],
        'lms.mission_detail.validation.remaining_count' => ['one' => 'Terminez encore {total} devoir pour continuer.', 'other' => 'Terminez encore {total} devoirs pour continuer.', 'context' => 'Info message shown when some homeworks remain before validation. The count variable selects the plural form; total is the displayed count.'],
        'lms.mission_detail.validation.quiz_question_count' => ['one' => 'Cette mission sera validée par {total} question.', 'other' => 'Cette mission sera validée par {total} questions.', 'context' => 'Info message shown before starting the quiz. The count variable selects the plural form; total is the displayed count.'],
        'lms.mission_detail.quiz.counter' => ['text' => 'Question {current} sur {total}', 'context' => 'Counter shown above the current quiz question.'],
        'lms.mission_detail.quiz.multiple' => ['text' => 'Plusieurs réponses possibles', 'context' => 'Hint shown when a quiz question allows multiple answers.'],
        'lms.mission_detail.quiz.finish' => ['text' => 'Terminer', 'context' => 'Button label used on the last quiz question.'],
        'lms.mission_detail.quiz.submit' => ['text' => 'Valider la réponse', 'context' => 'Button label used to submit a quiz answer.'],
        'lms.mission_detail.quiz.select_answer' => ['text' => 'Veuillez sélectionner une réponse.', 'context' => 'Alert shown when no answer is selected.'],
        'lms.mission_detail.quiz.wrong_answer' => ['text' => 'Mauvaise réponse.', 'context' => 'Alert shown when the submitted answer is wrong.'],
        'lms.mission_detail.alert.load_quiz' => ['text' => 'Impossible de charger le quiz.', 'context' => 'Alert shown when the quiz cannot be loaded.'],
        'lms.mission_detail.alert.check_answer' => ['text' => 'Impossible de vérifier cette réponse. Réessayez.', 'context' => 'Notification shown when quiz answer verification fails.'],
        'lms.mission_detail.alert.save_homework' => ['text' => 'Impossible d’enregistrer ce devoir pour le moment.', 'context' => 'Alert shown when homework completion cannot be saved.'],
        'lms.mission_detail.alert.validate_mission' => ['text' => 'Impossible de valider cette mission pour le moment.', 'context' => 'Alert shown when the mission cannot be validated.'],
        'lms.mission_detail.login.invite' => ['text' => 'Connectez-vous pour valider cette mission et enregistrer votre progression.', 'context' => 'Invitation shown when mission progress requires login.'],
        'lms.mission_detail.login.button' => ['text' => 'Connexion', 'context' => 'Button opening the login form from a mission.'],
    ];
}

function commonLmsMissionT(string $key, array $variables = []): string
{
    static $bundles = [];
    $sourceLang = commonLmsMissionSourceLang();
    $locale = translationBundleResolveRequestLocale('lang');
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_lms_mission_detail', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}

function commonLmsMissionTexts(): array
{
    return [
        'loginInvite' => commonLmsMissionT('lms.mission_detail.login.invite'),
        'loginButton' => commonLmsMissionT('lms.mission_detail.login.button'),
        'homeworksTitle' => commonLmsMissionT('lms.mission_detail.homeworks.title'),
        'homeworkDone' => commonLmsMissionT('lms.mission_detail.homeworks.status_done'),
        'homeworkTodo' => commonLmsMissionT('lms.mission_detail.homeworks.status_todo'),
        'homeworkExpand' => commonLmsMissionT('lms.mission_detail.homeworks.expand'),
        'homeworkCollapse' => commonLmsMissionT('lms.mission_detail.homeworks.collapse'),
        'homeworkEmptyDetail' => commonLmsMissionT('lms.mission_detail.homeworks.empty_detail'),
        'homeworkHelp' => commonLmsMissionT('lms.mission_detail.homeworks.help'),
        'homeworkMarkDone' => commonLmsMissionT('lms.mission_detail.homeworks.mark_done'),
        'homeworkMarkUndone' => commonLmsMissionT('lms.mission_detail.homeworks.mark_undone'),
        'validationUnavailable' => commonLmsMissionT('lms.mission_detail.validation.unavailable'),
        'startQuiz' => commonLmsMissionT('lms.mission_detail.validation.start_quiz'),
        'markRead' => commonLmsMissionT('lms.mission_detail.validation.mark_read'),
        'scrollToEnd' => commonLmsMissionT('lms.mission_detail.validation.scroll_to_end'),
        'remainingOne' => commonLmsMissionT('lms.mission_detail.validation.remaining_count', ['count' => 1, 'total' => '{count}']),
        'remaining' => commonLmsMissionT('lms.mission_detail.validation.remaining_count', ['count' => 2, 'total' => '{count}']),
        'quizInfoOne' => commonLmsMissionT('lms.mission_detail.validation.quiz_question_count', ['count' => 1, 'total' => '{count}']),
        'quizInfo' => commonLmsMissionT('lms.mission_detail.validation.quiz_question_count', ['count' => 2, 'total' => '{count}']),
        'quizCounter' => commonLmsMissionT('lms.mission_detail.quiz.counter'),
        'quizMultiple' => commonLmsMissionT('lms.mission_detail.quiz.multiple'),
        'quizFinish' => commonLmsMissionT('lms.mission_detail.quiz.finish'),
        'quizSubmit' => commonLmsMissionT('lms.mission_detail.quiz.submit'),
        'quizSelectAnswer' => commonLmsMissionT('lms.mission_detail.quiz.select_answer'),
        'quizWrongAnswer' => commonLmsMissionT('lms.mission_detail.quiz.wrong_answer'),
        'alertLoadQuiz' => commonLmsMissionT('lms.mission_detail.alert.load_quiz'),
        'alertCheckAnswer' => commonLmsMissionT('lms.mission_detail.alert.check_answer'),
        'alertSaveHomework' => commonLmsMissionT('lms.mission_detail.alert.save_homework'),
        'alertValidateMission' => commonLmsMissionT('lms.mission_detail.alert.validate_mission'),
        'notFound' => commonLmsMissionT('lms.mission_detail.error.not_found'),
    ];
}
