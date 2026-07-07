<?php

if (!function_exists('lmsRenderMissionHomeworkManager')) {
    function lmsMissionEditorSourceLang(): array
    {
        return [
            'lms.mission_editor.common.actions' => ['text' => 'Actions', 'context' => 'Accessible label for action menus in the mission editor.'],
            'lms.mission_editor.common.search' => ['text' => 'Rechercher', 'context' => 'Label shown above picker search fields in the mission editor.'],
            'lms.mission_editor.common.search_mission' => ['text' => 'Titre ou resume', 'context' => 'Search placeholder used in mission pickers.'],
            'lms.mission_editor.common.close' => ['text' => 'Fermer', 'context' => 'Button used to close a picker modal in the mission editor.'],
            'lms.mission_editor.common.add' => ['text' => 'Ajouter', 'context' => 'Generic add action used in mission editor panels.'],
            'lms.mission_editor.common.cancel' => ['text' => 'Annuler', 'context' => 'Button used to cancel mission editor subforms.'],
            'lms.mission_editor.common.edit' => ['text' => 'Editer', 'context' => 'Generic edit action used in mission editor cards.'],
            'lms.mission_editor.common.choice' => ['text' => 'Choix', 'context' => 'Label used for question choice fields.'],
            'lms.mission_editor.common.correct_answer' => ['text' => 'Bonne reponse', 'context' => 'Label used for the correct answer checkbox.'],
            'lms.mission_editor.common.delete' => ['text' => 'Supprimer', 'context' => 'Button used to remove an item.'],
            'lms.mission_editor.dependencies.title' => ['text' => 'Prerequis de mission', 'context' => 'Section title for mission prerequisites.'],
            'lms.mission_editor.dependencies.intro' => ['text' => 'Selectionnez ici les missions qui doivent etre terminees avant de rendre celle-ci disponible.', 'context' => 'Intro text for mission prerequisites.'],
            'lms.mission_editor.dependencies.add' => ['text' => 'Ajouter un prerequis', 'context' => 'Button used to open the mission dependency picker.'],
            'lms.mission_editor.dependencies.empty' => ['text' => 'Aucun prerequis n est encore defini pour cette mission.', 'context' => 'Empty state shown when a mission has no prerequisite.'],
            'lms.mission_editor.dependencies.edit' => ['text' => 'Editer la mission', 'context' => 'Menu action used to edit a prerequisite mission.'],
            'lms.mission_editor.dependencies.remove' => ['text' => 'Retirer le prerequis', 'context' => 'Menu action used to remove a prerequisite mission.'],
            'lms.mission_editor.dependencies.required' => ['text' => 'Mission requise', 'context' => 'Badge shown on mission prerequisite cards.'],
            'lms.mission_editor.dependencies.picker_title' => ['text' => 'Ajouter un prerequis de mission', 'context' => 'Title of the mission dependency picker.'],
            'lms.mission_editor.dependencies.picker_intro' => ['text' => 'Choisissez une autre mission de ce parcours.', 'context' => 'Intro text of the mission dependency picker.'],
            'lms.mission_editor.dependencies.empty_picker' => ['text' => 'Toutes les missions eligibles sont deja utilisees comme prerequis.', 'context' => 'Empty state shown when no mission can be added as prerequisite.'],
            'lms.mission_editor.dependencies.empty_search' => ['text' => 'Aucune mission ne correspond a cette recherche.', 'context' => 'Empty state shown when no dependency search result matches.'],
            'lms.mission_editor.homeworks.title' => ['text' => 'Devoirs', 'context' => 'Section title for mission homeworks.'],
            'lms.mission_editor.homeworks.intro' => ['text' => 'Ajoutez ici les taches a effectuer avant de pouvoir valider la mission. Glissez-deposez les cartes pour definir leur ordre.', 'context' => 'Intro text for the mission homeworks section.'],
            'lms.mission_editor.homeworks.add' => ['text' => 'Ajouter un devoir', 'context' => 'Button used to open the homework creator.'],
            'lms.mission_editor.homeworks.empty' => ['text' => 'Aucun devoir n est encore rattache a cette mission.', 'context' => 'Empty state shown when no homework is attached to the mission.'],
            'lms.mission_editor.homeworks.move_aria' => ['text' => 'Deplacer le devoir', 'context' => 'Accessible label used on homework drag handles.'],
            'lms.mission_editor.homeworks.field_title' => ['text' => 'Titre', 'context' => 'Label of the homework title field.'],
            'lms.mission_editor.homeworks.field_detail' => ['text' => 'Detail', 'context' => 'Label of the homework detail field.'],
            'lms.mission_editor.homeworks.field_only_admin' => ['text' => 'Demander ce devoir uniquement aux admins de l organisation', 'context' => 'Label of the admin-only homework checkbox.'],
            'lms.mission_editor.homeworks.badge_only_admin' => ['text' => 'Admins uniquement', 'context' => 'Badge shown on homework cards restricted to organization admins.'],
            'lms.mission_editor.homeworks.submit' => ['text' => 'Creer le devoir', 'context' => 'Submit button used to create or save a homework.'],
            'lms.mission_editor.homeworks.update' => ['text' => 'Mettre a jour le devoir', 'context' => 'Submit button used when editing a homework.'],
            'lms.mission_editor.questions.title' => ['text' => 'Questions', 'context' => 'Section title for mission validation questions.'],
            'lms.mission_editor.questions.intro' => ['text' => 'Ajoutez ici les questions de validation avec leurs choix de reponse. Glissez-deposez les cartes pour definir leur ordre.', 'context' => 'Intro text for the mission questions section.'],
            'lms.mission_editor.questions.add' => ['text' => 'Ajouter une question', 'context' => 'Button used to open the question creator.'],
            'lms.mission_editor.questions.empty' => ['text' => 'Aucune question n est encore rattachee a cette mission.', 'context' => 'Empty state shown when no question is attached to the mission.'],
            'lms.mission_editor.questions.move_aria' => ['text' => 'Deplacer la question', 'context' => 'Accessible label used on question drag handles.'],
            'lms.mission_editor.questions.field_question' => ['text' => 'Question', 'context' => 'Label of the question text field.'],
            'lms.mission_editor.questions.field_answer' => ['text' => 'Reponse / explication', 'context' => 'Label of the answer explanation field.'],
            'lms.mission_editor.questions.field_detail' => ['text' => 'Detail', 'context' => 'Label of the question detail field.'],
            'lms.mission_editor.questions.field_choices' => ['text' => 'Choix de reponse', 'context' => 'Label of the question choices group.'],
            'lms.mission_editor.questions.add_choice' => ['text' => 'Ajouter un choix', 'context' => 'Button used to append a new answer choice row.'],
            'lms.mission_editor.questions.submit' => ['text' => 'Creer la question', 'context' => 'Submit button used to create or save a question.'],
            'lms.mission_editor.questions.choice_count' => ['text' => '{count} choix', 'context' => 'Metadata badge used to show how many choices a question has.'],
            'lms.mission_editor.questions.correct_count' => ['text' => '{count} correct', 'context' => 'Metadata badge used to show how many correct choices a question has.'],
        ];
    }

    function lmsMissionEditorT(string $key, array $replace = []): string
    {
        static $lang = null;
        static $sourceLang = null;

        if ($sourceLang === null) {
            $sourceLang = lmsMissionEditorSourceLang();
            $lang = omoLoadTranslationBundle('omo_lms_mission_editor', $sourceLang);
        }

        return t($key, $replace, $lang, $sourceLang);
    }

    function lmsRenderMissionDependencyManager($parcoursId, $missionId)
    {
        $parcoursId = (int)$parcoursId;
        $missionId = (int)$missionId;
        $dependencies = \dbObject\MissionDependencies::fetchDetailedForMission($parcoursId, $missionId);
        $availableMissions = \dbObject\Mission::fetchAvailableDependencyTargetsForMission($parcoursId, $missionId);

        ob_start();
        ?>
        <section class="lms-mission-related" data-lms-mission-dependency-manager="1" data-mission-id="<?php echo $missionId; ?>" data-parcours-id="<?php echo $parcoursId; ?>">
            <div class="lms-mission-related__header">
                <div>
                    <h3><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.title')); ?></h3>
                    <p><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.intro')); ?></p>
                </div>
                <button type="button" data-lms-open-mission-dependency-picker="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.add')); ?></button>
            </div>

            <?php if (count($dependencies) === 0): ?>
                <div class="lms-mission-related__empty"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.empty')); ?></div>
            <?php else: ?>
                <div class="lms-mission-related__list" data-lms-mission-dependency-list="1">
                    <?php foreach ($dependencies as $dependency): ?>
                        <article class="lms-parcours-mission-item" data-required-mission-id="<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>">
                            <div class="lms-parcours-mission-item__menu-wrap">
                                <button
                                    type="button"
                                    class="lms-parcours-mission-item__menu-trigger"
                                    data-lms-toggle-mission-dependency-menu="1"
                                    data-required-mission-id="<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>"
                                    aria-label="<?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.actions')); ?>"
                                >...</button>
                                <div class="lms-parcours-mission-item__menu" id="lms-mission-dependency-item-menu-<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>">
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-edit-required-mission="1"
                                        data-required-mission-id="<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.edit')); ?></button>
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-remove-mission-dependency="1"
                                        data-required-mission-id="<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.remove')); ?></button>
                                </div>
                            </div>
                            <div class="lms-parcours-mission-item__handle" aria-hidden="true">!</div>
                            <div class="lms-parcours-mission-item__body">
                                <strong><?php echo htmlspecialchars((string)($dependency['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($dependency['resume'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$dependency['resume']); ?></p>
                                <?php endif; ?>
                                <div class="lms-parcours-mission-item__meta">
                                    <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.required')); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="lms-parcours-mission-picker" data-lms-mission-dependency-picker="1" hidden>
                <div class="lms-parcours-mission-picker__backdrop" data-lms-close-mission-dependency-picker="1"></div>
                <div class="lms-parcours-mission-picker__panel">
                    <div class="lms-parcours-mission-picker__header">
                        <div>
                            <h4><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.picker_title')); ?></h4>
                            <p><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.picker_intro')); ?></p>
                        </div>
                        <div class="lms-parcours-mission-picker__header-actions">
                            <button type="button" class="lms-parcours-mission-picker__close" data-lms-close-mission-dependency-picker="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.close')); ?></button>
                        </div>
                    </div>

                    <label class="lms-parcours-mission-picker__search">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.search')); ?></span>
                        <input type="search" data-lms-mission-dependency-picker-search="1" placeholder="<?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.search_mission')); ?>">
                    </label>

                    <div class="lms-parcours-mission-picker__list" data-lms-mission-dependency-picker-list="1">
                        <?php if (count($availableMissions) === 0): ?>
                            <div class="lms-parcours-mission-picker__empty"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.empty_picker')); ?></div>
                        <?php else: ?>
                            <?php foreach ($availableMissions as $availableMission): ?>
                                <?php
                                $searchText = function_exists('mb_strtolower')
                                    ? mb_strtolower(trim((string)($availableMission['title'] ?? '') . ' ' . (string)($availableMission['resume'] ?? '')), 'UTF-8')
                                    : strtolower(trim((string)($availableMission['title'] ?? '') . ' ' . (string)($availableMission['resume'] ?? '')));
                                ?>
                                <article
                                    class="lms-parcours-mission-picker__item"
                                    data-lms-mission-dependency-picker-item="1"
                                    data-required-mission-id="<?php echo (int)($availableMission['id'] ?? 0); ?>"
                                    data-search-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <div class="lms-parcours-mission-picker__copy">
                                        <strong><?php echo htmlspecialchars((string)($availableMission['title'] ?? '')); ?></strong>
                                        <?php if (trim((string)($availableMission['resume'] ?? '')) !== ''): ?>
                                            <p><?php echo htmlspecialchars((string)$availableMission['resume']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" data-lms-add-mission-dependency-id="<?php echo (int)($availableMission['id'] ?? 0); ?>"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.add')); ?></button>
                                </article>
                            <?php endforeach; ?>
                            <div class="lms-parcours-mission-picker__empty" data-lms-mission-dependency-picker-empty-search="1" hidden><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.dependencies.empty_search')); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    function lmsRenderMissionHomeworkManager($parcoursId, $missionId)
    {
        $parcoursId = (int)$parcoursId;
        $missionId = (int)$missionId;
        $homeworks = \dbObject\MissionHomework::fetchDetailedForMission($missionId);

        ob_start();
        ?>
        <section class="lms-mission-related" data-lms-homework-manager="1" data-mission-id="<?php echo $missionId; ?>" data-parcours-id="<?php echo $parcoursId; ?>">
            <div class="lms-mission-related__header">
                <div>
                    <h3><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.title')); ?></h3>
                    <p><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.intro')); ?></p>
                </div>
                <button type="button" data-lms-open-homework-creator="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.add')); ?></button>
            </div>

            <?php if (count($homeworks) === 0): ?>
                <div class="lms-mission-related__empty"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.empty')); ?></div>
            <?php else: ?>
                <div class="lms-mission-related__list" data-lms-homework-list="1">
                    <?php foreach ($homeworks as $homework): ?>
                        <article
                            class="lms-mission-related__item lms-mission-related__item--structured lms-mission-related__item--homework"
                            data-lms-homework-item="1"
                            data-homework-id="<?php echo (int)($homework['IDhomework'] ?? 0); ?>"
                            data-homework-title="<?php echo htmlspecialchars((string)($homework['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-homework-detail="<?php echo htmlspecialchars((string)($homework['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-homework-only-admin="<?php echo !empty($homework['onlyAdmin']) ? '1' : '0'; ?>"
                        >
                            <div class="lms-mission-related__item-head lms-mission-related__item-head--structured">
                                <button type="button" class="lms-mission-related__drag-handle generic-drag-handle generic-drag-handle--stretch" data-lms-homework-drag-handle="1" aria-label="<?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.move_aria')); ?>" title="<?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.move_aria')); ?>">::</button>
                                <div class="lms-mission-related__item-main">
                                    <div class="lms-mission-related__item-topbar">
                                        <strong><?php echo htmlspecialchars((string)($homework['title'] ?? '')); ?></strong>
                                        <div class="lms-mission-related__item-actions">
                                            <button type="button" data-lms-edit-homework="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.edit')); ?></button>
                                        </div>
                                    </div>
                                    <?php if (trim((string)($homework['detail'] ?? '')) !== ''): ?>
                                        <div class="lms-mission-related__item-html"><?php echo (string)$homework['detail']; ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($homework['onlyAdmin'])): ?>
                                        <div class="lms-mission-related__meta">
                                            <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.badge_only_admin')); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form
                method="post"
                action="<?php echo htmlspecialchars(omoLmsBuildPath('/mission_homework_create.php')); ?>"
                class="lms-mission-creator-form"
                data-lms-homework-create-form="1"
                hidden
            >
                <input type="hidden" name="id" value="">
                <div class="lms-mission-creator-form__grid">
                    <label class="lms-mission-creator-form__field">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.field_title')); ?></span>
                        <input type="text" name="title" maxlength="150" class="generic-form-control" required>
                    </label>

                    <label class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.field_detail')); ?></span>
                        <textarea name="detail" rows="4" class="generic-form-control summernote" data-editor-profile="simple"></textarea>
                    </label>

                    <label class="lms-mission-creator-form__check lms-mission-creator-form__field--full">
                        <input type="hidden" name="onlyAdmin" value="0">
                        <input type="checkbox" name="onlyAdmin" value="1">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.field_only_admin')); ?></span>
                    </label>
                </div>

                <div class="lms-mission-creator-form__actions">
                    <button type="button" class="generic-action-button generic-action-button--secondary" data-lms-close-homework-creator="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.cancel')); ?></button>
                    <button type="submit" class="generic-action-button generic-action-button--main" data-lms-homework-create-submit="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.homeworks.submit')); ?></button>
                </div>
            </form>
        </section>
        <?php

        return ob_get_clean();
    }

    function lmsRenderMissionQuestionChoiceRows($choiceCount = 2)
    {
        $choiceCount = max(2, (int)$choiceCount);
        ob_start();
        for ($index = 0; $index < $choiceCount; $index++) {
            ?>
            <div class="lms-question-choice-row" data-lms-question-choice-row="1">
                <label class="lms-question-choice-row__label">
                    <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.choice')); ?></span>
                    <input type="text" name="choices[<?php echo $index; ?>][label]" required>
                </label>
                <label class="lms-question-choice-row__correct">
                    <input type="checkbox" name="choices[<?php echo $index; ?>][is_correct]" value="1">
                    <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.correct_answer')); ?></span>
                </label>
                <button type="button" class="lms-question-choice-row__remove" data-lms-remove-question-choice="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.delete')); ?></button>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    function lmsRenderMissionQuestionManager($parcoursId, $missionId)
    {
        $parcoursId = (int)$parcoursId;
        $missionId = (int)$missionId;
        $questions = \dbObject\MissionQuestion::fetchDetailedForMission($missionId);

        ob_start();
        ?>
        <section class="lms-mission-related" data-lms-question-manager="1" data-mission-id="<?php echo $missionId; ?>" data-parcours-id="<?php echo $parcoursId; ?>">
            <div class="lms-mission-related__header">
                <div>
                    <h3><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.title')); ?></h3>
                    <p><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.intro')); ?></p>
                </div>
                <button type="button" data-lms-open-question-creator="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.add')); ?></button>
            </div>

            <?php if (count($questions) === 0): ?>
                <div class="lms-mission-related__empty"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.empty')); ?></div>
            <?php else: ?>
                <div class="lms-mission-related__list" data-lms-question-list="1">
                    <?php foreach ($questions as $question): ?>
                        <?php
                        $choices = \dbObject\QuestionChoice::fetchForQuestion((int)($question['IDquestion'] ?? 0));
                        $choicesJson = json_encode($choices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        if (!is_string($choicesJson)) {
                            $choicesJson = '[]';
                        }
                        ?>
                        <article
                            class="lms-mission-related__item lms-mission-related__item--structured lms-mission-related__item--question"
                            data-lms-question-item="1"
                            data-question-id="<?php echo (int)($question['IDquestion'] ?? 0); ?>"
                            data-question-text="<?php echo htmlspecialchars((string)($question['question'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-question-answer="<?php echo htmlspecialchars((string)($question['answer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-question-detail="<?php echo htmlspecialchars((string)($question['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-question-choices="<?php echo htmlspecialchars($choicesJson, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <div class="lms-mission-related__item-head lms-mission-related__item-head--structured">
                                <button type="button" class="lms-mission-related__drag-handle generic-drag-handle generic-drag-handle--stretch" data-lms-question-drag-handle="1" aria-label="<?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.move_aria')); ?>" title="<?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.move_aria')); ?>">::</button>
                                <div class="lms-mission-related__item-main">
                                    <div class="lms-mission-related__item-topbar">
                                        <strong><?php echo htmlspecialchars((string)($question['question'] ?? '')); ?></strong>
                                        <div class="lms-mission-related__item-actions">
                                            <button type="button" data-lms-edit-question="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.edit')); ?></button>
                                        </div>
                                    </div>
                                    <?php if (trim((string)($question['answer'] ?? '')) !== ''): ?>
                                        <p><?php echo nl2br(htmlspecialchars((string)$question['answer'])); ?></p>
                                    <?php endif; ?>
                                    <div class="lms-mission-related__meta">
                                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.choice_count', ['count' => (string)((int)($question['choice_count'] ?? 0))])); ?></span>
                                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.correct_count', ['count' => (string)((int)($question['correct_choice_count'] ?? 0))])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form
                method="post"
                action="<?php echo htmlspecialchars(omoLmsBuildPath('/mission_question_create.php')); ?>"
                class="lms-mission-creator-form"
                data-lms-question-create-form="1"
                hidden
            >
                <input type="hidden" name="id" value="">
                <div class="lms-mission-creator-form__grid">
                    <label class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.field_question')); ?></span>
                        <textarea name="question" rows="3" required></textarea>
                    </label>

                    <label class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.field_answer')); ?></span>
                        <textarea name="answer" rows="4" required></textarea>
                    </label>

                    <label class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.field_detail')); ?></span>
                        <textarea name="detail" rows="3"></textarea>
                    </label>

                    <div class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.field_choices')); ?></span>
                        <div class="lms-question-choice-list" data-lms-question-choice-list="1">
                            <?php echo lmsRenderMissionQuestionChoiceRows(2); ?>
                        </div>
                        <button type="button" class="lms-question-choice-list__add" data-lms-add-question-choice="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.add_choice')); ?></button>
                    </div>
                </div>

                <div class="lms-mission-creator-form__actions">
                    <button type="button" data-lms-close-question-creator="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.common.cancel')); ?></button>
                    <button type="submit" data-lms-question-create-submit="1"><?php echo htmlspecialchars(lmsMissionEditorT('lms.mission_editor.questions.submit')); ?></button>
                </div>
            </form>
        </section>
        <?php

        return ob_get_clean();
    }
}
