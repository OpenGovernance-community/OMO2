<?php

if (!function_exists('lmsRenderParcoursMissionManager')) {
    function lmsParcoursEditorSourceLang(): array
    {
        return [
            'lms.parcours_editor.error.not_found' => ['text' => 'Parcours introuvable.', 'context' => 'Error shown inside the parcours content manager when the parcours cannot be loaded.'],
            'lms.parcours_editor.common.actions' => ['text' => 'Actions', 'context' => 'Accessible label for item action menus in the parcours editor.'],
            'lms.parcours_editor.common.search' => ['text' => 'Rechercher', 'context' => 'Label shown above picker search fields in the parcours editor.'],
            'lms.parcours_editor.common.search_title_description' => ['text' => 'Titre ou description', 'context' => 'Search placeholder used in parcours pickers.'],
            'lms.parcours_editor.common.back' => ['text' => 'Retour', 'context' => 'Button used to go back to the previous step in the parcours editor.'],
            'lms.parcours_editor.common.cancel' => ['text' => 'Annuler', 'context' => 'Button used to cancel the current parcours editor subform.'],
            'lms.parcours_editor.common.close' => ['text' => 'Fermer', 'context' => 'Button used to close a picker modal in the parcours editor.'],
            'lms.parcours_editor.common.add' => ['text' => 'Ajouter', 'context' => 'Generic add action used in parcours editor pickers.'],
            'lms.parcours_editor.common.move' => ['text' => 'Deplacer', 'context' => 'Tooltip for drag handles in the parcours editor.'],
            'lms.parcours_editor.prerequisites.title' => ['text' => 'Prerequis', 'context' => 'Section title for parcours prerequisites.'],
            'lms.parcours_editor.prerequisites.intro' => ['text' => 'Ces parcours simples doivent etre termines a 100% avant de rendre celui-ci visible.', 'context' => 'Intro text for the parcours prerequisites section.'],
            'lms.parcours_editor.prerequisites.add' => ['text' => 'Ajouter un prerequis', 'context' => 'Button used to open the parcours prerequisite picker.'],
            'lms.parcours_editor.prerequisites.empty' => ['text' => 'Aucun prerequis n est encore defini pour ce parcours.', 'context' => 'Empty state shown when no parcours prerequisite exists yet.'],
            'lms.parcours_editor.prerequisites.edit' => ['text' => 'Editer le parcours', 'context' => 'Menu action used to open a prerequisite parcours in edit mode.'],
            'lms.parcours_editor.prerequisites.remove' => ['text' => 'Retirer le prerequis', 'context' => 'Menu action used to remove a prerequisite from the parcours.'],
            'lms.parcours_editor.prerequisites.required' => ['text' => '100% requis', 'context' => 'Badge shown on prerequisite parcours cards.'],
            'lms.parcours_editor.prerequisites.picker_title' => ['text' => 'Ajouter un prerequis', 'context' => 'Title of the parcours prerequisite picker.'],
            'lms.parcours_editor.prerequisites.picker_intro' => ['text' => 'Choisissez un autre parcours simple appartenant a votre organisation.', 'context' => 'Intro text of the parcours prerequisite picker.'],
            'lms.parcours_editor.prerequisites.empty_picker' => ['text' => 'Tous les parcours eligibles sont deja utilises comme prerequis.', 'context' => 'Empty state shown when no parcours can be added as prerequisite.'],
            'lms.parcours_editor.prerequisites.empty_search' => ['text' => 'Aucun parcours ne correspond a cette recherche.', 'context' => 'Empty state shown when no prerequisite search result matches.'],
            'lms.parcours_editor.mission_create.title' => ['text' => 'Creer une nouvelle mission', 'context' => 'Title of the inline mission creation form in the parcours editor.'],
            'lms.parcours_editor.mission_create.intro' => ['text' => 'Renseignez les informations principales de la mission. Elle sera aussitot ajoutee a ce parcours.', 'context' => 'Intro text of the inline mission creation form.'],
            'lms.parcours_editor.mission_create.field_title' => ['text' => 'Titre', 'context' => 'Label of the mission title field in the inline mission creation form.'],
            'lms.parcours_editor.mission_create.field_resume' => ['text' => 'Resume', 'context' => 'Label of the mission summary field in the inline mission creation form.'],
            'lms.parcours_editor.mission_create.field_video' => ['text' => 'Video', 'context' => 'Label of the mission video field in the inline mission creation form.'],
            'lms.parcours_editor.mission_create.field_html' => ['text' => 'Contenu HTML', 'context' => 'Label of the mission HTML content field in the inline mission creation form.'],
            'lms.parcours_editor.mission_create.placeholder_html' => ['text' => '<p>Contenu de la mission</p>', 'context' => 'Placeholder shown in the mission HTML content field.'],
            'lms.parcours_editor.mission_create.submit' => ['text' => 'Creer et ajouter', 'context' => 'Submit button of the inline mission creation form.'],
            'lms.parcours_editor.missions.title' => ['text' => 'Missions du parcours', 'context' => 'Section title for missions attached to the parcours.'],
            'lms.parcours_editor.missions.intro' => ['text' => 'Glissez les missions pour changer leur ordre, puis ajoutez-en d autres depuis la bibliotheque existante.', 'context' => 'Intro text for the parcours missions section.'],
            'lms.parcours_editor.missions.empty' => ['text' => 'Aucune mission n est encore rattachee a ce parcours.', 'context' => 'Empty state shown when the parcours has no mission yet.'],
            'lms.parcours_editor.missions.edit' => ['text' => 'Editer', 'context' => 'Menu action used to edit a mission from the parcours editor.'],
            'lms.parcours_editor.missions.remove' => ['text' => 'Retirer du parcours', 'context' => 'Menu action used to detach a mission from the parcours.'],
            'lms.parcours_editor.missions.branch' => ['text' => 'Branche : {branch}', 'context' => 'Metadata badge used to show the mission branch.'],
            'lms.parcours_editor.missions.existing_title' => ['text' => 'Ajouter une mission existante', 'context' => 'Title of the mission picker in the parcours editor.'],
            'lms.parcours_editor.missions.existing_intro' => ['text' => 'Choisissez une mission deja creee pour la rattacher a ce parcours.', 'context' => 'Intro text of the mission picker in the parcours editor.'],
            'lms.parcours_editor.missions.new_button' => ['text' => 'Nouvelle mission', 'context' => 'Button used to switch from the mission picker to the inline mission creation form.'],
            'lms.parcours_editor.missions.search_placeholder' => ['text' => 'Titre ou resume', 'context' => 'Search placeholder used in the mission picker.'],
            'lms.parcours_editor.missions.empty_picker' => ['text' => 'Toutes les missions disponibles sont deja dans ce parcours.', 'context' => 'Empty state shown when no existing mission can be added.'],
            'lms.parcours_editor.missions.empty_search' => ['text' => 'Aucune mission ne correspond a cette recherche.', 'context' => 'Empty state shown when no mission search result matches.'],
            'lms.parcours_editor.pack.title' => ['text' => 'Parcours du pack', 'context' => 'Section title for parcours items attached to a pack.'],
            'lms.parcours_editor.pack.intro' => ['text' => 'Glissez les parcours pour changer leur ordre, puis ajoutez des parcours simples dont votre organisation est proprietaire.', 'context' => 'Intro text for the pack children section.'],
            'lms.parcours_editor.pack.empty' => ['text' => 'Aucun parcours n est encore rattache a ce pack.', 'context' => 'Empty state shown when the pack has no child parcours.'],
            'lms.parcours_editor.pack.edit' => ['text' => 'Editer le parcours', 'context' => 'Menu action used to edit a child parcours of a pack.'],
            'lms.parcours_editor.pack.remove' => ['text' => 'Retirer du pack', 'context' => 'Menu action used to remove a child parcours from a pack.'],
            'lms.parcours_editor.pack.application_linked' => ['text' => 'Application liee', 'context' => 'Metadata badge shown when a pack child parcours is linked to an application.'],
            'lms.parcours_editor.pack.always_visible' => ['text' => 'Toujours visible', 'context' => 'Metadata badge shown when a pack child parcours remains visible regardless of apps.'],
            'lms.parcours_editor.pack.picker_title' => ['text' => 'Ajouter un parcours au pack', 'context' => 'Title of the pack child picker.'],
            'lms.parcours_editor.pack.picker_intro' => ['text' => 'Choisissez un parcours simple appartenant a votre organisation.', 'context' => 'Intro text of the pack child picker.'],
            'lms.parcours_editor.pack.empty_picker' => ['text' => 'Tous les parcours simples disponibles sont deja dans ce pack.', 'context' => 'Empty state shown when no parcours can be added to the pack.'],
            'lms.parcours_editor.pack.empty_search' => ['text' => 'Aucun parcours ne correspond a cette recherche.', 'context' => 'Empty state shown when no pack child search result matches.'],
        ];
    }

    function lmsParcoursEditorT(string $key, array $replace = []): string
    {
        static $lang = null;
        static $sourceLang = null;

        if ($sourceLang === null) {
            $sourceLang = lmsParcoursEditorSourceLang();
            $lang = omoLoadTranslationBundle('omo_lms_parcours_editor', $sourceLang);
        }

        return t($key, $replace, $lang, $sourceLang);
    }

    function lmsRenderParcoursContentManager($organizationId, $parcoursId)
    {
        $parcoursId = (int)$parcoursId;
        $parcours = new \dbObject\Parcours();
        if (!$parcours->load($parcoursId)) {
            return '<div class="lms-parcours-missions__empty generic-description">' . htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.error.not_found')) . '</div>';
        }

        $prerequisiteManager = lmsRenderParcoursPrerequisiteManager($organizationId, $parcoursId);
        $contentManager = '';
        if ($parcours->isPack()) {
            $contentManager = lmsRenderParcoursPackManager($organizationId, $parcoursId);
        } else {
            $contentManager = lmsRenderParcoursMissionManager($organizationId, $parcoursId);
        }

        return '<div data-lms-parcours-content-manager="1">' . $prerequisiteManager . $contentManager . '</div>';
    }

    function lmsRenderParcoursPrerequisiteManager($organizationId, $parcoursId)
    {
        $organizationId = (int)$organizationId;
        $parcoursId = (int)$parcoursId;
        $prerequisites = \dbObject\Parcours::fetchDetailedPrerequisitesForParcours($parcoursId);
        $availableParcours = \dbObject\Parcours::fetchAvailablePrerequisiteTargetsForOrganization($organizationId, $parcoursId);

        ob_start();
        ?>
        <section
            class="lms-parcours-missions generic-section generic-section--stack generic-form-section"
            data-lms-parcours-prerequisite-manager="1"
            data-parcours-id="<?php echo (int)$parcoursId; ?>"
            data-organization-id="<?php echo (int)$organizationId; ?>"
        >
            <div class="lms-parcours-missions__header generic-form-section__heading">
                <div class="generic-form-section__copy">
                    <h3 class="generic-title generic-title--medium"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.title')); ?></h3>
                    <p class="generic-description"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.intro')); ?></p>
                </div>
                <button type="button" class="lms-parcours-missions__add-button generic-action-button generic-action-button--secondary" data-lms-open-prerequisite-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.add')); ?></button>
            </div>

            <?php if (count($prerequisites) === 0): ?>
                <div class="lms-parcours-missions__empty generic-description"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.empty')); ?></div>
            <?php else: ?>
                <div class="lms-parcours-missions__list" data-lms-prerequisite-list="1">
                    <?php foreach ($prerequisites as $prerequisite): ?>
                        <article
                            class="lms-parcours-mission-item"
                            data-required-parcours-id="<?php echo (int)($prerequisite['IDparcours_required'] ?? 0); ?>"
                        >
                            <div class="lms-parcours-mission-item__menu-wrap">
                                <button
                                    type="button"
                                    class="lms-parcours-mission-item__menu-trigger"
                                    data-lms-toggle-prerequisite-menu="1"
                                    data-required-parcours-id="<?php echo (int)($prerequisite['IDparcours_required'] ?? 0); ?>"
                                    aria-label="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.actions')); ?>"
                                >...</button>
                                <div class="lms-parcours-mission-item__menu" id="lms-prerequisite-item-menu-<?php echo (int)($prerequisite['IDparcours_required'] ?? 0); ?>">
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-edit-prerequisite-parcours="1"
                                        data-required-parcours-id="<?php echo (int)($prerequisite['IDparcours_required'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.edit')); ?></button>
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-remove-prerequisite="1"
                                        data-required-parcours-id="<?php echo (int)($prerequisite['IDparcours_required'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.remove')); ?></button>
                                </div>
                            </div>
                            <div class="lms-parcours-mission-item__handle" aria-hidden="true">!</div>
                            <div class="lms-parcours-mission-item__body">
                                <strong><?php echo htmlspecialchars((string)($prerequisite['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($prerequisite['description'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$prerequisite['description']); ?></p>
                                <?php endif; ?>
                                <div class="lms-parcours-mission-item__meta">
                                    <span><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.required')); ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="lms-parcours-mission-picker" data-lms-prerequisite-picker="1" hidden>
                <div class="lms-parcours-mission-picker__backdrop" data-lms-close-prerequisite-picker="1"></div>
                <div class="lms-parcours-mission-picker__panel">
                    <div class="lms-parcours-mission-picker__header">
                        <div>
                            <h4><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.picker_title')); ?></h4>
                            <p><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.picker_intro')); ?></p>
                        </div>
                        <div class="lms-parcours-mission-picker__header-actions">
                            <button type="button" class="lms-parcours-mission-picker__close" data-lms-close-prerequisite-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.close')); ?></button>
                        </div>
                    </div>

                    <label class="lms-parcours-mission-picker__search">
                        <span><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.search')); ?></span>
                        <input type="search" data-lms-prerequisite-picker-search="1" placeholder="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.search_title_description')); ?>">
                    </label>

                    <div class="lms-parcours-mission-picker__list" data-lms-prerequisite-picker-list="1">
                        <?php if (count($availableParcours) === 0): ?>
                            <div class="lms-parcours-mission-picker__empty generic-description generic-description--compact"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.empty_picker')); ?></div>
                        <?php else: ?>
                            <?php foreach ($availableParcours as $availableParcoursItem): ?>
                                <?php
                                $searchText = function_exists('mb_strtolower')
                                    ? mb_strtolower(trim((string)($availableParcoursItem['title'] ?? '') . ' ' . (string)($availableParcoursItem['description'] ?? '')), 'UTF-8')
                                    : strtolower(trim((string)($availableParcoursItem['title'] ?? '') . ' ' . (string)($availableParcoursItem['description'] ?? '')));
                                ?>
                                <article
                                    class="lms-parcours-mission-picker__item"
                                    data-lms-prerequisite-picker-item="1"
                                    data-required-parcours-id="<?php echo (int)($availableParcoursItem['id'] ?? 0); ?>"
                                    data-search-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <div class="lms-parcours-mission-picker__copy">
                                        <strong class="generic-title generic-title--compact"><?php echo htmlspecialchars((string)($availableParcoursItem['title'] ?? '')); ?></strong>
                                        <?php if (trim((string)($availableParcoursItem['description'] ?? '')) !== ''): ?>
                                            <p class="generic-description generic-description--compact"><?php echo htmlspecialchars((string)$availableParcoursItem['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" data-lms-add-prerequisite-id="<?php echo (int)($availableParcoursItem['id'] ?? 0); ?>"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.add')); ?></button>
                                </article>
                            <?php endforeach; ?>
                            <div class="lms-parcours-mission-picker__empty generic-description generic-description--compact" data-lms-prerequisite-picker-empty-search="1" hidden><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.prerequisites.empty_search')); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    function lmsRenderParcoursMissionCreateForm($parcoursId)
    {
        $parcoursId = (int)$parcoursId;
        ob_start();
        ?>
        <div class="lms-parcours-mission-creator generic-form-stack" data-lms-mission-creator-view="1" hidden>
            <div class="lms-parcours-mission-creator__header generic-form-section__heading">
                <button type="button" class="lms-parcours-mission-creator__back generic-action-button generic-action-button--secondary" data-lms-back-to-mission-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.back')); ?></button>
                <div class="generic-form-section__copy">
                    <h4 class="generic-title generic-title--medium"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.title')); ?></h4>
                    <p class="generic-description"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.intro')); ?></p>
                </div>
            </div>

            <form
                method="post"
                action="<?php echo htmlspecialchars(omoLmsBuildPath('/parcours_mission_create.php')); ?>"
                class="generic-form-stack"
                data-lms-mission-create-form="1"
                data-parcours-id="<?php echo $parcoursId; ?>"
            >
                <div class="lms-parcours-mission-creator__grid generic-form-grid">
                    <label class="lms-parcours-mission-creator__field generic-form-field">
                        <span class="generic-form-label"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.field_title')); ?></span>
                        <input type="text" name="title" maxlength="150" class="generic-form-control" required>
                    </label>

                    <label class="lms-parcours-mission-creator__field lms-parcours-mission-creator__field--full generic-form-field generic-form-field--full">
                        <span class="generic-form-label"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.field_resume')); ?></span>
                        <textarea name="resume" rows="3" class="generic-form-control" required></textarea>
                    </label>

                    <label class="lms-parcours-mission-creator__field lms-parcours-mission-creator__field--full generic-form-field generic-form-field--full">
                        <span class="generic-form-label"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.field_video')); ?></span>
                        <input type="text" name="video" maxlength="1000" class="generic-form-control" placeholder="https://...">
                    </label>

                    <label class="lms-parcours-mission-creator__field lms-parcours-mission-creator__field--full generic-form-field generic-form-field--full">
                        <span class="generic-form-label"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.field_html')); ?></span>
                        <textarea name="html" rows="8" class="generic-form-control" placeholder="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.placeholder_html')); ?>"></textarea>
                    </label>
                </div>

                <div class="lms-parcours-mission-creator__actions generic-form-actions generic-form-actions--stack-mobile">
                    <button type="button" class="lms-parcours-mission-creator__cancel generic-action-button generic-action-button--secondary" data-lms-back-to-mission-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.cancel')); ?></button>
                    <button type="submit" class="lms-parcours-mission-creator__submit generic-action-button generic-action-button--main" data-lms-mission-create-submit="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.mission_create.submit')); ?></button>
                </div>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    function lmsRenderParcoursMissionManager($organizationId, $parcoursId)
    {
        $organizationId = (int)$organizationId;
        $parcoursId = (int)$parcoursId;
        $missions = \dbObject\ParcoursMission::fetchDetailedForParcours($parcoursId);
        $availableMissions = \dbObject\Mission::fetchAvailableForParcoursEditor($parcoursId);

        ob_start();
        ?>
        <section
            class="lms-parcours-missions generic-section generic-section--stack generic-form-section"
            data-lms-parcours-content-manager="1"
            data-lms-parcours-mission-manager="1"
            data-lms-manager-type="mission"
            data-parcours-id="<?php echo (int)$parcoursId; ?>"
            data-organization-id="<?php echo (int)$organizationId; ?>"
        >
            <div class="lms-parcours-missions__header generic-form-section__heading">
                <div class="generic-form-section__copy">
                    <h3 class="generic-title generic-title--medium"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.title')); ?></h3>
                    <p class="generic-description"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.intro')); ?></p>
                </div>
                <button type="button" class="lms-parcours-missions__add-button generic-action-button generic-action-button--secondary" data-lms-open-mission-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.add')); ?></button>
            </div>

            <?php if (count($missions) === 0): ?>
                <div class="lms-parcours-missions__empty generic-description"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.empty')); ?></div>
            <?php else: ?>
                <div class="lms-parcours-missions__list" data-lms-parcours-mission-list="1">
                    <?php foreach ($missions as $mission): ?>
                        <article
                            class="lms-parcours-mission-item"
                            draggable="true"
                            data-sortable-id="<?php echo (int)($mission['IDmission'] ?? 0); ?>"
                            data-mission-id="<?php echo (int)($mission['IDmission'] ?? 0); ?>"
                        >
                            <div class="lms-parcours-mission-item__menu-wrap">
                                <button
                                    type="button"
                                    class="lms-parcours-mission-item__menu-trigger"
                                    data-lms-toggle-mission-menu="1"
                                    data-mission-id="<?php echo (int)($mission['IDmission'] ?? 0); ?>"
                                    aria-label="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.actions')); ?>"
                                >...</button>
                                <div class="lms-parcours-mission-item__menu" id="lms-mission-item-menu-<?php echo (int)($mission['IDmission'] ?? 0); ?>">
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-edit-mission="1"
                                        data-mission-id="<?php echo (int)($mission['IDmission'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.edit')); ?></button>
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-remove-mission="1"
                                        data-mission-id="<?php echo (int)($mission['IDmission'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.remove')); ?></button>
                                </div>
                            </div>
                            <div class="lms-parcours-mission-item__handle generic-drag-handle generic-drag-handle--stretch" data-lms-mission-drag-handle="1" title="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.move')); ?>">::</div>
                            <div class="lms-parcours-mission-item__body">
                                <strong><?php echo htmlspecialchars((string)($mission['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($mission['resume'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$mission['resume']); ?></p>
                                <?php endif; ?>
                                <div class="lms-parcours-mission-item__meta">
                                    <span><?php echo (int)($mission['quiz_count'] ?? 0); ?> quiz</span>
                                    <span><?php echo (int)($mission['homework_count'] ?? 0); ?> devoirs</span>
                                    <?php if (trim((string)($mission['branch'] ?? '')) !== ''): ?>
                                        <span><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.branch', ['branch' => (string)$mission['branch']])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="lms-parcours-mission-picker" data-lms-mission-picker="1" hidden>
                <div class="lms-parcours-mission-picker__backdrop" data-lms-close-mission-picker="1"></div>
                <div class="lms-parcours-mission-picker__panel">
                    <div class="lms-parcours-mission-picker__header">
                        <div>
                            <h4><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.existing_title')); ?></h4>
                            <p><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.existing_intro')); ?></p>
                        </div>
                        <div class="lms-parcours-mission-picker__header-actions">
                            <button type="button" class="lms-parcours-mission-picker__new-button" data-lms-open-mission-creator="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.new_button')); ?></button>
                            <button type="button" class="lms-parcours-mission-picker__close" data-lms-close-mission-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.close')); ?></button>
                        </div>
                    </div>

                    <div data-lms-mission-picker-library="1">
                        <label class="lms-parcours-mission-picker__search">
                            <span><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.search')); ?></span>
                            <input type="search" data-lms-mission-picker-search="1" placeholder="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.search_placeholder')); ?>">
                        </label>

                        <div class="lms-parcours-mission-picker__list" data-lms-mission-picker-list="1">
                            <?php if (count($availableMissions) === 0): ?>
                                <div class="lms-parcours-mission-picker__empty generic-description generic-description--compact"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.empty_picker')); ?></div>
                            <?php else: ?>
                                <?php foreach ($availableMissions as $mission): ?>
                                    <?php
                                    $searchText = function_exists('mb_strtolower')
                                        ? mb_strtolower(trim((string)($mission['title'] ?? '') . ' ' . (string)($mission['resume'] ?? '')), 'UTF-8')
                                        : strtolower(trim((string)($mission['title'] ?? '') . ' ' . (string)($mission['resume'] ?? '')));
                                    ?>
                                    <article
                                        class="lms-parcours-mission-picker__item"
                                        data-lms-mission-picker-item="1"
                                        data-mission-id="<?php echo (int)($mission['id'] ?? 0); ?>"
                                        data-search-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <div class="lms-parcours-mission-picker__copy">
                                            <strong class="generic-title generic-title--compact"><?php echo htmlspecialchars((string)($mission['title'] ?? '')); ?></strong>
                                            <?php if (trim((string)($mission['resume'] ?? '')) !== ''): ?>
                                                <p class="generic-description generic-description--compact"><?php echo htmlspecialchars((string)$mission['resume']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" data-lms-add-mission-id="<?php echo (int)($mission['id'] ?? 0); ?>"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.add')); ?></button>
                                    </article>
                                <?php endforeach; ?>
                                <div class="lms-parcours-mission-picker__empty generic-description generic-description--compact" data-lms-mission-picker-empty-search="1" hidden><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.missions.empty_search')); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php echo lmsRenderParcoursMissionCreateForm($parcoursId); ?>
                </div>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }

    function lmsRenderParcoursPackManager($organizationId, $parcoursId)
    {
        $organizationId = (int)$organizationId;
        $parcoursId = (int)$parcoursId;
        $children = \dbObject\ParcoursParcours::fetchDetailedForParent($parcoursId);
        $availableParcours = \dbObject\Parcours::fetchAvailablePackTargetsForOrganization($organizationId, $parcoursId);

        ob_start();
        ?>
        <section
            class="lms-parcours-missions generic-section generic-section--stack generic-form-section"
            data-lms-parcours-content-manager="1"
            data-lms-parcours-pack-manager="1"
            data-lms-manager-type="pack"
            data-parcours-id="<?php echo (int)$parcoursId; ?>"
            data-organization-id="<?php echo (int)$organizationId; ?>"
        >
            <div class="lms-parcours-missions__header generic-form-section__heading">
                <div class="generic-form-section__copy">
                    <h3 class="generic-title generic-title--medium"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.title')); ?></h3>
                    <p class="generic-description"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.intro')); ?></p>
                </div>
                <button type="button" class="lms-parcours-missions__add-button generic-action-button generic-action-button--secondary" data-lms-open-pack-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.add')); ?></button>
            </div>

            <?php if (count($children) === 0): ?>
                <div class="lms-parcours-missions__empty generic-description"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.empty')); ?></div>
            <?php else: ?>
                <div class="lms-parcours-missions__list" data-lms-pack-parcours-list="1">
                    <?php foreach ($children as $child): ?>
                        <article
                            class="lms-parcours-mission-item"
                            draggable="true"
                            data-sortable-id="<?php echo (int)($child['IDparcours_child'] ?? 0); ?>"
                            data-child-parcours-id="<?php echo (int)($child['IDparcours_child'] ?? 0); ?>"
                        >
                            <div class="lms-parcours-mission-item__menu-wrap">
                                <button
                                    type="button"
                                    class="lms-parcours-mission-item__menu-trigger"
                                    data-lms-toggle-pack-item-menu="1"
                                    data-child-parcours-id="<?php echo (int)($child['IDparcours_child'] ?? 0); ?>"
                                    aria-label="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.actions')); ?>"
                                >...</button>
                                <div class="lms-parcours-mission-item__menu" id="lms-pack-item-menu-<?php echo (int)($child['IDparcours_child'] ?? 0); ?>">
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-edit-pack-child="1"
                                        data-child-parcours-id="<?php echo (int)($child['IDparcours_child'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.edit')); ?></button>
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-remove-pack-child="1"
                                        data-child-parcours-id="<?php echo (int)($child['IDparcours_child'] ?? 0); ?>"
                                    ><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.remove')); ?></button>
                                </div>
                            </div>
                            <div class="lms-parcours-mission-item__handle generic-drag-handle generic-drag-handle--stretch" data-lms-pack-drag-handle="1" title="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.move')); ?>">::</div>
                            <div class="lms-parcours-mission-item__body">
                                <strong><?php echo htmlspecialchars((string)($child['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($child['description'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$child['description']); ?></p>
                                <?php endif; ?>
                                <div class="lms-parcours-mission-item__meta">
                                    <?php if (!empty($child['IDapplication'])): ?>
                                        <span><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.application_linked')); ?></span>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.always_visible')); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="lms-parcours-mission-picker" data-lms-pack-picker="1" hidden>
                <div class="lms-parcours-mission-picker__backdrop" data-lms-close-pack-picker="1"></div>
                <div class="lms-parcours-mission-picker__panel">
                    <div class="lms-parcours-mission-picker__header">
                        <div>
                            <h4><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.picker_title')); ?></h4>
                            <p><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.picker_intro')); ?></p>
                        </div>
                        <div class="lms-parcours-mission-picker__header-actions">
                            <button type="button" class="lms-parcours-mission-picker__close" data-lms-close-pack-picker="1"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.close')); ?></button>
                        </div>
                    </div>

                    <label class="lms-parcours-mission-picker__search">
                        <span><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.search')); ?></span>
                        <input type="search" data-lms-pack-picker-search="1" placeholder="<?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.search_title_description')); ?>">
                    </label>

                    <div class="lms-parcours-mission-picker__list" data-lms-pack-picker-list="1">
                        <?php if (count($availableParcours) === 0): ?>
                            <div class="lms-parcours-mission-picker__empty generic-description generic-description--compact"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.empty_picker')); ?></div>
                        <?php else: ?>
                            <?php foreach ($availableParcours as $availableParcoursItem): ?>
                                <?php
                                $searchText = function_exists('mb_strtolower')
                                    ? mb_strtolower(trim((string)($availableParcoursItem['title'] ?? '') . ' ' . (string)($availableParcoursItem['description'] ?? '')), 'UTF-8')
                                    : strtolower(trim((string)($availableParcoursItem['title'] ?? '') . ' ' . (string)($availableParcoursItem['description'] ?? '')));
                                ?>
                                <article
                                    class="lms-parcours-mission-picker__item"
                                    data-lms-pack-picker-item="1"
                                    data-child-parcours-id="<?php echo (int)($availableParcoursItem['id'] ?? 0); ?>"
                                    data-search-text="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <div class="lms-parcours-mission-picker__copy">
                                        <strong class="generic-title generic-title--compact"><?php echo htmlspecialchars((string)($availableParcoursItem['title'] ?? '')); ?></strong>
                                        <?php if (trim((string)($availableParcoursItem['description'] ?? '')) !== ''): ?>
                                            <p class="generic-description generic-description--compact"><?php echo htmlspecialchars((string)$availableParcoursItem['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" data-lms-add-pack-child-id="<?php echo (int)($availableParcoursItem['id'] ?? 0); ?>"><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.common.add')); ?></button>
                                </article>
                            <?php endforeach; ?>
                            <div class="lms-parcours-mission-picker__empty generic-description generic-description--compact" data-lms-pack-picker-empty-search="1" hidden><?php echo htmlspecialchars(lmsParcoursEditorT('lms.parcours_editor.pack.empty_search')); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php

        return ob_get_clean();
    }
}
