<?php
require_once __DIR__ . '/rule-scope-fields.php';

// Shared form fields for deferred proposals in meeting minutes and ballots.
function omoDeferredEditorT(string $key): string
{
    static $sourceLang = [
        'title' => ['text' => 'Titre', 'context' => 'Deferred proposal object title'],
        'intention' => ['text' => 'Intention', 'context' => 'Deferred rule intention'],
        'rule' => ['text' => 'Règle', 'context' => 'Deferred rule content'],
        'description' => ['text' => 'Description', 'context' => 'Deferred project description'],
        'review_date' => ['text' => 'Date de requestionnement', 'context' => 'Deferred rule review date'],
        'expiration_date' => ['text' => 'Date d’échéance', 'context' => 'Deferred rule expiration date'],
        'status' => ['text' => 'Statut', 'context' => 'Deferred project status'],
        'size' => ['text' => 'Taille', 'context' => 'Deferred project size'],
        'start' => ['text' => 'Début planifié', 'context' => 'Deferred project planned start'],
        'end' => ['text' => 'Fin planifiée', 'context' => 'Deferred project planned end'],
        'priority' => ['text' => 'Priorité', 'context' => 'Deferred project priority'],
        'importance' => ['text' => 'Importance stratégique', 'context' => 'Deferred project strategic importance'],
        'blocked_reason' => ['text' => 'Motif du blocage', 'context' => 'Deferred project blocking reason'],
        'blocked_until' => ['text' => 'Relance du projet bloqué', 'context' => 'Deferred project follow-up date'],
        'save' => ['text' => 'Enregistrer la modification', 'context' => 'Deferred proposal save action'],
        'cancel' => ['text' => 'Annuler', 'context' => 'Deferred proposal cancel action'],
        'move_destination' => ['text' => 'Destination', 'context' => 'Deferred holon move destination'],
        'move_help' => ['text' => 'Le deplacement sera applique apres validation de la proposition.', 'context' => 'Deferred holon move explanation'],
        'move_empty' => ['text' => 'Aucune destination compatible et autorisee.', 'context' => 'Deferred holon move without destination'],
    ];
    static $bundle = null;
    $bundle ??= omoLoadTranslationBundle('omo_deferred_editor_fields', $sourceLang);
    return t($key, [], $bundle, $sourceLang);
}

function omoDeferredEditorRenderFields(string $targetType, array $state): void
{
    $escape = static fn ($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $value = static fn (string $name): string => $escape(($state[$name] ?? '') instanceof DateTimeInterface ? $state[$name]->format('Y-m-d') : ($state[$name] ?? ''));
    $label = static fn (string $key): string => $escape(omoDeferredEditorT($key));
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
        <label class="generic-form-field"><span class="generic-form-label"><?= $label('title') ?></span><input class="generic-form-control" name="title" required maxlength="255" value="<?= $value('title') ?>"></label>
        <?php if ($targetType === 'rule'): ?>
            <?php omoRuleScopeRenderFields($state, $state['scopeContext'] ?? []); ?>
            <label class="generic-form-field"><span class="generic-form-label"><?= $label('intention') ?></span><textarea class="generic-form-control" name="intention" rows="3"><?= $value('intention') ?></textarea></label>
            <label class="generic-form-field"><span class="generic-form-label"><?= $label('rule') ?></span><textarea class="generic-form-control" name="description" rows="5" required><?= $value('description') ?></textarea></label>
            <div class="generic-form-grid">
                <?php foreach (['review_date', 'expiration_date'] as $field): ?>
                    <label class="generic-form-field"><span class="generic-form-label"><?= $label($field) ?></span><input class="generic-form-control" type="date" name="<?= $field ?>" value="<?= $value($field) ?>" required></label>
                <?php endforeach; ?>
            </div>
        <?php elseif ($targetType === 'project'): ?>
            <label class="generic-form-field"><span class="generic-form-label"><?= $label('description') ?></span><textarea class="generic-form-control" name="description" rows="4"><?= $value('description') ?></textarea></label>
            <div class="generic-form-grid">
                <label class="generic-form-field"><span class="generic-form-label"><?= $label('status') ?></span><select class="generic-form-control" name="status"><?php foreach (\dbObject\Project::getStatusCatalog() as $key => $entry): ?><option value="<?= $escape($key) ?>"<?= ($state['status'] ?? \dbObject\Project::STATUS_IN_PROGRESS) === $key ? ' selected' : '' ?>><?= $escape($entry['label']) ?></option><?php endforeach; ?></select></label>
                <label class="generic-form-field"><span class="generic-form-label"><?= $label('size') ?></span><select class="generic-form-control" name="project_size"><?php foreach (\dbObject\Project::sizes() as $size): ?><option value="<?= $size ?>"<?= ($state['project_size'] ?? \dbObject\Project::SIZE_M) === $size ? ' selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select></label>
            </div>
            <div class="generic-form-grid">
                <?php foreach (['planned_start_date' => 'start', 'planned_end_date' => 'end'] as $field => $key): ?>
                    <label class="generic-form-field"><span class="generic-form-label"><?= $label($key) ?></span><input class="generic-form-control" type="date" name="<?= $field ?>" value="<?= $value($field) ?>"></label>
                <?php endforeach; ?>
            </div>
            <div class="generic-form-grid">
                <?php foreach (['priority', 'importance'] as $field): ?>
                    <label class="generic-form-field"><span class="generic-form-label"><?= $label($field) ?></span><select class="generic-form-control" name="<?= $field ?>"><option value=""></option><?php for ($level = 1; $level <= 5; $level++): ?><option value="<?= $level ?>"<?= (int)($state[$field] ?? 0) === $level ? ' selected' : '' ?>><?= $field === 'priority' ? 'P' . $level : $level . '/5' ?></option><?php endfor; ?></select></label>
                <?php endforeach; ?>
            </div>
            <div class="generic-form-grid">
                <label class="generic-form-field"><span class="generic-form-label"><?= $label('blocked_reason') ?></span><input class="generic-form-control" name="blocked_reason" value="<?= $value('blocked_reason') ?>"></label>
                <label class="generic-form-field"><span class="generic-form-label"><?= $label('blocked_until') ?></span><input class="generic-form-control" type="date" name="blocked_until" value="<?= $value('blocked_until') ?>"></label>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
