<?php

if (!function_exists('lmsRenderParcoursMissionManager')) {
    function lmsRenderParcoursMissionCreateForm($parcoursId)
    {
        $parcoursId = (int)$parcoursId;
        ob_start();
        ?>
        <div class="lms-parcours-mission-creator" data-lms-mission-creator-view="1" hidden>
            <div class="lms-parcours-mission-creator__header">
                <button type="button" class="lms-parcours-mission-creator__back" data-lms-back-to-mission-picker="1">Retour</button>
                <div>
                    <h4>Creer une nouvelle mission</h4>
                    <p>Renseignez les informations principales de la mission. Elle sera aussitot ajoutee a ce parcours.</p>
                </div>
            </div>

            <form
                method="post"
                action="<?php echo htmlspecialchars(omoLmsBuildPath('/parcours_mission_create.php')); ?>"
                data-lms-mission-create-form="1"
                data-parcours-id="<?php echo $parcoursId; ?>"
            >
                <div class="lms-parcours-mission-creator__grid">
                    <label class="lms-parcours-mission-creator__field">
                        <span>Titre</span>
                        <input type="text" name="title" maxlength="150" required>
                    </label>

                    <label class="lms-parcours-mission-creator__field lms-parcours-mission-creator__field--full">
                        <span>Resume</span>
                        <textarea name="resume" rows="3" required></textarea>
                    </label>

                    <label class="lms-parcours-mission-creator__field lms-parcours-mission-creator__field--full">
                        <span>Video</span>
                        <input type="text" name="video" maxlength="150" placeholder="https://...">
                    </label>

                    <label class="lms-parcours-mission-creator__field lms-parcours-mission-creator__field--full">
                        <span>Contenu HTML</span>
                        <textarea name="html" rows="8" placeholder="<p>Contenu de la mission</p>"></textarea>
                    </label>
                </div>

                <div class="lms-parcours-mission-creator__actions">
                    <button type="button" class="lms-parcours-mission-creator__cancel" data-lms-back-to-mission-picker="1">Annuler</button>
                    <button type="submit" class="lms-parcours-mission-creator__submit" data-lms-mission-create-submit="1">Creer et ajouter</button>
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
            class="lms-parcours-missions"
            data-lms-parcours-mission-manager="1"
            data-parcours-id="<?php echo (int)$parcoursId; ?>"
            data-organization-id="<?php echo (int)$organizationId; ?>"
        >
            <div class="lms-parcours-missions__header">
                <div>
                    <h3>Missions du parcours</h3>
                    <p>Glissez les missions pour changer leur ordre, puis ajoutez-en d autres depuis la bibliotheque existante.</p>
                </div>
                <button type="button" class="lms-parcours-missions__add-button" data-lms-open-mission-picker="1">Ajouter</button>
            </div>

            <?php if (count($missions) === 0): ?>
                <div class="lms-parcours-missions__empty">Aucune mission n est encore rattachee a ce parcours.</div>
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
                                    aria-label="Actions"
                                >...</button>
                                <div class="lms-parcours-mission-item__menu" id="lms-mission-item-menu-<?php echo (int)($mission['IDmission'] ?? 0); ?>">
                                    <button
                                        type="button"
                                        class="lms-parcours-mission-item__menu-item"
                                        data-lms-edit-mission="1"
                                        data-mission-id="<?php echo (int)($mission['IDmission'] ?? 0); ?>"
                                    >Editer</button>
                                </div>
                            </div>
                            <div class="lms-parcours-mission-item__handle" data-lms-mission-drag-handle="1" title="Deplacer">::</div>
                            <div class="lms-parcours-mission-item__body">
                                <strong><?php echo htmlspecialchars((string)($mission['title'] ?? '')); ?></strong>
                                <?php if (trim((string)($mission['resume'] ?? '')) !== ''): ?>
                                    <p><?php echo htmlspecialchars((string)$mission['resume']); ?></p>
                                <?php endif; ?>
                                <div class="lms-parcours-mission-item__meta">
                                    <span><?php echo (int)($mission['quiz_count'] ?? 0); ?> quiz</span>
                                    <span><?php echo (int)($mission['homework_count'] ?? 0); ?> devoirs</span>
                                    <?php if (trim((string)($mission['branch'] ?? '')) !== ''): ?>
                                        <span>Branche: <?php echo htmlspecialchars((string)$mission['branch']); ?></span>
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
                            <h4>Ajouter une mission existante</h4>
                            <p>Choisissez une mission deja creee pour la rattacher a ce parcours.</p>
                        </div>
                        <div class="lms-parcours-mission-picker__header-actions">
                            <button type="button" class="lms-parcours-mission-picker__new-button" data-lms-open-mission-creator="1">Nouvelle mission</button>
                            <button type="button" class="lms-parcours-mission-picker__close" data-lms-close-mission-picker="1">Fermer</button>
                        </div>
                    </div>

                    <div data-lms-mission-picker-library="1">
                        <label class="lms-parcours-mission-picker__search">
                            <span>Rechercher</span>
                            <input type="search" data-lms-mission-picker-search="1" placeholder="Titre ou resume">
                        </label>

                        <div class="lms-parcours-mission-picker__list" data-lms-mission-picker-list="1">
                            <?php if (count($availableMissions) === 0): ?>
                                <div class="lms-parcours-mission-picker__empty">Toutes les missions disponibles sont deja dans ce parcours.</div>
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
                                            <strong><?php echo htmlspecialchars((string)($mission['title'] ?? '')); ?></strong>
                                            <?php if (trim((string)($mission['resume'] ?? '')) !== ''): ?>
                                                <p><?php echo htmlspecialchars((string)$mission['resume']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" data-lms-add-mission-id="<?php echo (int)($mission['id'] ?? 0); ?>">Ajouter</button>
                                    </article>
                                <?php endforeach; ?>
                                <div class="lms-parcours-mission-picker__empty" data-lms-mission-picker-empty-search="1" hidden>Aucune mission ne correspond a cette recherche.</div>
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
}
