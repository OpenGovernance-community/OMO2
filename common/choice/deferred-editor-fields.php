<?php
require_once __DIR__ . '/rule-scope-fields.php';
require_once __DIR__ . '/rule-date-fields.php';
require_once __DIR__ . '/rule-value-fields.php';
require_once __DIR__ . '/holon-removal-fields.php';
require_once dirname(__DIR__) . '/holon_scope_helper.php';

// Shared form fields for deferred proposals in meeting minutes and ballots.
function omoDeferredEditorT(string $key): string
{
    static $sourceLang = [
        'title' => ['text' => 'Titre', 'context' => 'Deferred proposal object title'],
        'intention' => ['text' => 'Intention', 'context' => 'Deferred rule intention'],
        'rule' => ['text' => 'Règle', 'context' => 'Deferred rule content'],
        'description' => ['text' => 'Description', 'context' => 'Deferred project description'],
        'proposed_by' => ['text' => 'Proposé par', 'context' => 'Member who requested the proposed project'],
        'responsible' => ['text' => 'Personne en charge', 'context' => 'Person assigned to the proposed project'],
        'members_role' => ['text' => 'Personnes en charge du rôle', 'context' => 'First group of project assignees'],
        'members_circle' => ['text' => 'Autres membres du cercle', 'context' => 'Second group of project assignees without duplicates'],
        'members_organization' => ['text' => 'Autres membres de l’organisation', 'context' => 'Last group of project assignees without duplicates'],
        'members_error' => ['text' => 'Impossible d’actualiser la liste des personnes.', 'context' => 'Project assignee list refresh error'],
        'person_fallback' => ['text' => 'Personne #', 'context' => 'Fallback prefix for a historical project assignee'],
        'review_date' => ['text' => 'Date de requestionnement', 'context' => 'Deferred rule review date'],
        'expiration_date' => ['text' => 'Date d’échéance', 'context' => 'Deferred rule expiration date'],
        'status' => ['text' => 'Statut', 'context' => 'Deferred project status'],
        'size' => ['text' => 'Taille', 'context' => 'Deferred project size'],
        'start' => ['text' => 'Début planifié', 'context' => 'Deferred project planned start'],
        'end' => ['text' => 'Fin planifiée', 'context' => 'Deferred project planned end'],
        'priority' => ['text' => 'Priorité', 'context' => 'Deferred project priority'],
        'importance' => ['text' => 'Importance stratégique', 'context' => 'Deferred project strategic importance'],
        'blocked_reason' => ['text' => 'Motif du blocage', 'context' => 'Deferred project blocking reason'],
        'blocked_until' => ['text' => 'Date de déblocage', 'context' => 'Deferred project follow-up date'],
        'save' => ['text' => 'Enregistrer la modification', 'context' => 'Deferred proposal save action'],
        'cancel' => ['text' => 'Annuler', 'context' => 'Deferred proposal cancel action'],
        'move_destination' => ['text' => 'Destination', 'context' => 'Deferred holon move destination'],
        'move_help' => ['text' => 'Le déplacement sera appliqué après validation de la proposition.', 'context' => 'Deferred holon move explanation'],
        'move_empty' => ['text' => 'Aucune destination compatible et autorisée.', 'context' => 'Deferred holon move without destination'],
        'creation_space' => ['text' => 'Espace de création', 'context' => 'Space receiving an object created by a PV proposal'],
        'creation_space_hint' => ['text' => 'Choisissez un espace autorisé par les droits collectifs du holon du PV.', 'context' => 'Collective permission boundary for a PV proposal creation destination'],
    ];
    static $bundle = null;
    $bundle ??= omoLoadTranslationBundle('omo_deferred_editor_fields', $sourceLang);
    return t($key, [], $bundle, $sourceLang);
}

function omoDeferredEditorRenderCreationHolonField(int $organizationId, int $holonId, array $catalog, string $operation = 'create'): void
{
    $config = [
        'organizationId' => $organizationId,
        'selectableHolonIds' => array_values(array_map('intval', array_keys(array_filter(
            $catalog,
            static fn (array $entry): bool => !empty($entry['permissions'][$operation])
        )))),
        'allowOrganization' => false,
        'labels' => commonHolonScopeTargetLabels(omoDeferredEditorT('creation_space_hint')),
    ];
    ?>
    <div class="generic-form-field">
        <label class="generic-form-label" for="omoDeferredCreationHolonLabel"><?= htmlspecialchars(omoDeferredEditorT('creation_space'), ENT_QUOTES, 'UTF-8') ?></label>
        <?php commonHolonScopeRenderTargetField('holon_id', $holonId, (string)($catalog[$holonId]['label'] ?? ''), $config, ['id' => 'omoDeferredCreationHolon']); ?>
    </div>
    <?php
}

function omoDeferredEditorRenderFields(string $targetType, array $state, array $memberLabels = [], ?callable $creationSpaceRenderer = null, array $responsibleGroups = []): void
{
    if ($targetType === 'holon_delete') {
        echo '<p>' . htmlspecialchars(omoHolonRemovalT('deferred'), ENT_QUOTES, 'UTF-8') . '</p>';
        omoRenderHolonRemovalFields((string)($state['authority_choice'] ?? ''));
        return;
    }
    $escape = static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $value = static fn (string $name): string => $escape(($state[$name] ?? '') instanceof DateTimeInterface ? $state[$name]->format('Y-m-d') : ($state[$name] ?? ''));
    $label = static fn (string $key): string => $escape(omoDeferredEditorT($key));
    $htmlField = static function (string $name, string $labelKey) use ($label, $value): void {
        ?>
        <div class="generic-form-field" data-omo-proposal-html-field>
            <span class="generic-form-label"><?= $label($labelKey) ?></span>
            <div class="omo-proposal-html-editor" data-omo-proposal-html-editor data-omo-deferred-html="<?= $name ?>"></div>
            <textarea name="<?= $name ?>" data-omo-proposal-html-value hidden aria-hidden="true"><?= $value($name) ?></textarea>
        </div>
        <?php
    };
    if ($targetType === 'holon_move') {
        ?>
        <p><?= $label('move_help') ?></p>
        <label class="generic-form-field"><span class="generic-form-label"><?= $label('move_destination') ?></span><select class="generic-form-control" name="parent_id" required>
            <option value=""></option>
            <?php foreach (($state['destinations'] ?? []) as $destination): ?>
                <?php if (!empty($destination['isCurrentParent'])) continue; ?>
                <option value="<?= (int)$destination['id'] ?>"<?= (int)($state['parent_id'] ?? 0) === (int)$destination['id'] ? ' selected' : '' ?>><?= $escape($destination['pathLabel']) ?></option>
            <?php endforeach; ?>
        </select></label>
        <?php
        return;
    }
    ?>
    <div class="generic-form-stack">
        <?php if ($targetType === 'project'): ?>
        <div class="generic-form-grid generic-form-grid--main-aside" style="--param-form-grid-columns: minmax(0, 3fr) minmax(0, 1fr);">
        <?php endif; ?>
        <label class="generic-form-field"><span class="generic-form-label"><?= $label('title') ?></span><input class="generic-form-control" name="title" required maxlength="255" value="<?= $value('title') ?>"></label>
        <?php if ($targetType === 'project'): ?>
            <label class="generic-form-field">
                <span class="generic-form-label"><?= $label('proposed_by') ?></span>
                <select class="generic-form-control" name="IDuser_proposed">
                    <option value=""></option>
                    <?php foreach ($memberLabels as $memberId => $memberLabel): ?>
                        <option value="<?= (int)$memberId ?>"<?= (int)($state['IDuser_proposed'] ?? 0) === (int)$memberId ? ' selected' : '' ?>><?= $escape($memberLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <?php endif; ?>
        <?php if ($targetType === 'rule'): ?>
            <?php omoRuleScopeRenderFields($state, $state['scopeContext'] ?? []); ?>
            <?php $htmlField('description', 'rule'); ?>
            <?php $htmlField('intention', 'intention'); ?>
            <?php omoRuleValueRenderField((int)($_SESSION['currentOrganization'] ?? 0), (int)($state['IDvaleur'] ?? 0)); ?>
            <div class="generic-form-grid">
                <?php foreach (['review_date', 'expiration_date'] as $field): ?>
                    <label class="generic-form-field"><span class="generic-form-label"><?= $label($field) ?></span><?php omoRuleDateRenderInput($field, $state[$field] ?? ''); ?></label>
                <?php endforeach; ?>
            </div>
        <?php elseif ($targetType === 'project'): ?>
            <?php $htmlField('description', 'description'); ?>
            <?php $isBlocked = ($state['status'] ?? \dbObject\Project::STATUS_IN_PROGRESS) === \dbObject\Project::STATUS_BLOCKED; ?>
            <div class="generic-form-grid" data-deferred-project-status-row>
                <label class="generic-form-field"><span class="generic-form-label"><?= $label('responsible') ?></span>
                    <select class="generic-form-control" name="IDuser" data-deferred-project-responsible
                        data-group-role="<?= $label('members_role') ?>" data-group-circle="<?= $label('members_circle') ?>" data-group-organization="<?= $label('members_organization') ?>" data-members-error="<?= $label('members_error') ?>" data-person-fallback="<?= $label('person_fallback') ?>">
                        <option value=""></option>
                        <?php
                        $responsibleGroups = $responsibleGroups ?: ['organization' => $memberLabels];
                        $responsibleId = (int)($state['IDuser'] ?? 0);
                        if ($responsibleId > 0 && !array_any($responsibleGroups, static fn (array $members): bool => isset($members[$responsibleId]))) {
                            $responsibleGroups['organization'][$responsibleId] = \dbObject\DocumentPvPoint::getUserDisplayNameForOrganization($responsibleId, (int)($_SESSION['currentOrganization'] ?? 0));
                        }
                        ?>
                        <?php foreach ($responsibleGroups as $group => $members): ?>
                            <?php if (!$members) continue; ?>
                            <optgroup label="<?= $label('members_' . $group) ?>">
                                <?php foreach ($members as $memberId => $memberLabel): ?>
                                    <option value="<?= (int)$memberId ?>"<?= $responsibleId === (int)$memberId ? ' selected' : '' ?>><?= $escape($memberLabel) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="generic-form-field"><span class="generic-form-label"><?= $label('status') ?></span><select class="generic-form-control" name="status"><?php foreach (\dbObject\Project::getStatusCatalog() as $key => $entry): ?><option value="<?= $escape($key) ?>"<?= ($state['status'] ?? \dbObject\Project::STATUS_IN_PROGRESS) === $key ? ' selected' : '' ?>><?= $escape($entry['label']) ?></option><?php endforeach; ?></select></label>
            </div>
            <div class="generic-form-grid" data-deferred-project-blocked-row<?= $isBlocked ? '' : ' hidden' ?>>
                <label class="generic-form-field" data-deferred-project-blocked-field<?= $isBlocked ? '' : ' hidden' ?>><span class="generic-form-label"><?= $label('blocked_until') ?></span><input class="generic-form-control" type="date" name="blocked_until" value="<?= $value('blocked_until') ?>"></label>
                <label class="generic-form-field" data-deferred-project-blocked-field<?= $isBlocked ? '' : ' hidden' ?>><span class="generic-form-label"><?= $label('blocked_reason') ?></span><input class="generic-form-control" name="blocked_reason" value="<?= $value('blocked_reason') ?>"></label>
            </div>
            <div class="generic-form-grid<?= $creationSpaceRenderer ? ' generic-form-grid--trio' : '' ?>">
                <?php if ($creationSpaceRenderer) $creationSpaceRenderer(); ?>
                <?php foreach (['planned_start_date' => 'start', 'planned_end_date' => 'end'] as $field => $key): ?>
                    <label class="generic-form-field"><span class="generic-form-label"><?= $label($key) ?></span><input class="generic-form-control" type="date" name="<?= $field ?>" value="<?= $value($field) ?>"></label>
                <?php endforeach; ?>
            </div>
            <div class="generic-form-grid generic-form-grid--trio">
                <label class="generic-form-field"><span class="generic-form-label"><?= $label('size') ?></span><select class="generic-form-control" name="project_size"><?php foreach (\dbObject\Project::sizes() as $size): ?><option value="<?= $size ?>"<?= ($state['project_size'] ?? \dbObject\Project::SIZE_M) === $size ? ' selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select></label>
                <?php foreach (['priority', 'importance'] as $field): ?>
                    <label class="generic-form-field"><span class="generic-form-label"><?= $label($field) ?></span><select class="generic-form-control" name="<?= $field ?>"><option value=""></option><?php for ($level = 1; $level <= 5; $level++): ?><option value="<?= $level ?>"<?= (int)($state[$field] ?? 0) === $level ? ' selected' : '' ?>><?= $field === 'priority' ? 'P' . $level : $level . '/5' ?></option><?php endfor; ?></select></label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
