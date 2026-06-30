<?php

if (!function_exists('lmsRenderMissionHomeworkManager')) {
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
                    <h3>Prerequis de mission</h3>
                    <p>Selectionnez ici les missions qui doivent etre terminees avant de rendre celle-ci disponible.</p>
                </div>
                <button type="button" data-lms-open-mission-dependency-picker="1">Ajouter un prerequis</button>
            </div>

            <?php if (count($dependencies) === 0): ?>
                <div class="lms-mission-related__empty">Aucun prerequis n est encore defini pour cette mission.</div>
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
                                    aria-label="Actions"
                                >...</button>
                                <div class="lms-parcours-mission-item__menu" id="lms-mission-dependency-item-menu-<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>">
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-edit-required-mission="1"
                                        data-required-mission-id="<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>"
                                    >Editer la mission</button>
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-remove-mission-dependency="1"
                                        data-required-mission-id="<?php echo (int)($dependency['IDmission_parent'] ?? 0); ?>"
                                    >Retirer le prerequis</button>
                                </div>
                            </div>
                            <div class="lms-parcours-mission-item__handle" aria-hidden="true">!</div>
                            <div class="lms-parcours-mission-item__body">
                                <strong><?php echo htmlspecialchars((string)($dependency['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($dependency['resume'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$dependency['resume']); ?></p>
                                <?php endif; ?>
                                <div class="lms-parcours-mission-item__meta">
                                    <span>Mission requise</span>
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
                            <h4>Ajouter un prerequis de mission</h4>
                            <p>Choisissez une autre mission de ce parcours.</p>
                        </div>
                        <div class="lms-parcours-mission-picker__header-actions">
                            <button type="button" class="lms-parcours-mission-picker__close" data-lms-close-mission-dependency-picker="1">Fermer</button>
                        </div>
                    </div>

                    <label class="lms-parcours-mission-picker__search">
                        <span>Rechercher</span>
                        <input type="search" data-lms-mission-dependency-picker-search="1" placeholder="Titre ou resume">
                    </label>

                    <div class="lms-parcours-mission-picker__list" data-lms-mission-dependency-picker-list="1">
                        <?php if (count($availableMissions) === 0): ?>
                            <div class="lms-parcours-mission-picker__empty">Toutes les missions eligibles sont deja utilisees comme prerequis.</div>
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
                                    <button type="button" data-lms-add-mission-dependency-id="<?php echo (int)($availableMission['id'] ?? 0); ?>">Ajouter</button>
                                </article>
                            <?php endforeach; ?>
                            <div class="lms-parcours-mission-picker__empty" data-lms-mission-dependency-picker-empty-search="1" hidden>Aucune mission ne correspond a cette recherche.</div>
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
                    <h3>Devoirs</h3>
                    <p>Ajoutez ici les taches a effectuer avant de pouvoir valider la mission. Glissez-deposez les cartes pour definir leur ordre.</p>
                </div>
                <button type="button" data-lms-open-homework-creator="1">Ajouter un devoir</button>
            </div>

            <?php if (count($homeworks) === 0): ?>
                <div class="lms-mission-related__empty">Aucun devoir n est encore rattache a cette mission.</div>
            <?php else: ?>
                <div class="lms-mission-related__list" data-lms-homework-list="1">
                    <?php foreach ($homeworks as $homework): ?>
                        <article
                            class="lms-mission-related__item"
                            data-lms-homework-item="1"
                            data-homework-id="<?php echo (int)($homework['IDhomework'] ?? 0); ?>"
                            data-homework-title="<?php echo htmlspecialchars((string)($homework['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-homework-detail="<?php echo htmlspecialchars((string)($homework['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <div class="lms-mission-related__item-head">
                                <button type="button" class="lms-mission-related__drag-handle" data-lms-homework-drag-handle="1" aria-label="Deplacer le devoir" title="Deplacer le devoir">::</button>
                                <div class="lms-mission-related__item-actions">
                                    <button type="button" data-lms-edit-homework="1">Editer</button>
                                </div>
                            </div>
                            <strong><?php echo htmlspecialchars((string)($homework['title'] ?? '')); ?></strong>
                            <?php if (trim((string)($homework['detail'] ?? '')) !== ''): ?>
                                <p><?php echo nl2br(htmlspecialchars((string)$homework['detail'])); ?></p>
                            <?php endif; ?>
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
                        <span>Titre</span>
                        <input type="text" name="title" maxlength="150" required>
                    </label>

                    <label class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span>Detail</span>
                        <textarea name="detail" rows="4"></textarea>
                    </label>
                </div>

                <div class="lms-mission-creator-form__actions">
                    <button type="button" data-lms-close-homework-creator="1">Annuler</button>
                    <button type="submit" data-lms-homework-create-submit="1">Creer le devoir</button>
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
                    <span>Choix</span>
                    <input type="text" name="choices[<?php echo $index; ?>][label]" required>
                </label>
                <label class="lms-question-choice-row__correct">
                    <input type="checkbox" name="choices[<?php echo $index; ?>][is_correct]" value="1">
                    <span>Bonne reponse</span>
                </label>
                <button type="button" class="lms-question-choice-row__remove" data-lms-remove-question-choice="1">Supprimer</button>
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
                    <h3>Questions</h3>
                    <p>Ajoutez ici les questions de validation avec leurs choix de reponse. Glissez-deposez les cartes pour definir leur ordre.</p>
                </div>
                <button type="button" data-lms-open-question-creator="1">Ajouter une question</button>
            </div>

            <?php if (count($questions) === 0): ?>
                <div class="lms-mission-related__empty">Aucune question n est encore rattachee a cette mission.</div>
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
                            class="lms-mission-related__item"
                            data-lms-question-item="1"
                            data-question-id="<?php echo (int)($question['IDquestion'] ?? 0); ?>"
                            data-question-text="<?php echo htmlspecialchars((string)($question['question'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-question-answer="<?php echo htmlspecialchars((string)($question['answer'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-question-detail="<?php echo htmlspecialchars((string)($question['detail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-question-choices="<?php echo htmlspecialchars($choicesJson, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <div class="lms-mission-related__item-head">
                                <button type="button" class="lms-mission-related__drag-handle" data-lms-question-drag-handle="1" aria-label="Deplacer la question" title="Deplacer la question">::</button>
                                <div class="lms-mission-related__item-actions">
                                    <button type="button" data-lms-edit-question="1">Editer</button>
                                </div>
                            </div>
                            <strong><?php echo htmlspecialchars((string)($question['question'] ?? '')); ?></strong>
                            <?php if (trim((string)($question['answer'] ?? '')) !== ''): ?>
                                <p><?php echo nl2br(htmlspecialchars((string)$question['answer'])); ?></p>
                            <?php endif; ?>
                            <div class="lms-mission-related__meta">
                                <span><?php echo (int)($question['choice_count'] ?? 0); ?> choix</span>
                                <span><?php echo (int)($question['correct_choice_count'] ?? 0); ?> correct</span>
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
                        <span>Question</span>
                        <textarea name="question" rows="3" required></textarea>
                    </label>

                    <label class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span>Reponse / explication</span>
                        <textarea name="answer" rows="4" required></textarea>
                    </label>

                    <label class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span>Detail</span>
                        <textarea name="detail" rows="3"></textarea>
                    </label>

                    <div class="lms-mission-creator-form__field lms-mission-creator-form__field--full">
                        <span>Choix de reponse</span>
                        <div class="lms-question-choice-list" data-lms-question-choice-list="1">
                            <?php echo lmsRenderMissionQuestionChoiceRows(2); ?>
                        </div>
                        <button type="button" class="lms-question-choice-list__add" data-lms-add-question-choice="1">Ajouter un choix</button>
                    </div>
                </div>

                <div class="lms-mission-creator-form__actions">
                    <button type="button" data-lms-close-question-creator="1">Annuler</button>
                    <button type="submit" data-lms-question-create-submit="1">Creer la question</button>
                </div>
            </form>
        </section>
        <?php

        return ob_get_clean();
    }
}
