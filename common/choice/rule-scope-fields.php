<?php

function omoRuleScopeT(string $key): string
{
    static $sourceLang = [
        'scope' => ['text' => 'Portee', 'context' => 'Rule scope field'],
        'local' => ['text' => 'Locale', 'context' => 'Rule applies to its own context'],
        'circle' => ['text' => 'Cercle', 'context' => 'Rule applies to the circle and its direct children'],
        'descendants' => ['text' => 'Descendante', 'context' => 'Rule applies to its context and all descendants'],
        'global' => ['text' => 'Globale', 'context' => 'Rule applies to the entire organization'],
        'help.local' => ['text' => 'Locale : cet espace.', 'context' => 'Selected local rule scope explanation'],
        'help.circle' => ['text' => 'Cercle : le cercle et ses enfants directs (le cercle parent pour un role).', 'context' => 'Selected circle rule scope explanation'],
        'help.descendants' => ['text' => 'Descendante : cet espace et tous ses descendants.', 'context' => 'Selected descendant rule scope explanation'],
        'help.global' => ['text' => 'Globale : toute l organisation.', 'context' => 'Selected global rule scope explanation'],
        'authority' => ['text' => 'Domaine d autorite', 'context' => 'Rule authority attachment'],
        'none' => ['text' => 'Aucun domaine associe', 'context' => 'Empty authority option'],
        'required' => ['text' => 'Cette portee exige un domaine d autorite associe.', 'context' => 'Rule authority requirement'],
        'unavailable' => ['text' => 'Aucun domaine disponible dans cet espace. Ajoutez un domaine ou choisissez une portee autorisee sans domaine.', 'context' => 'Rule scope requires an unavailable authority'],
    ];
    static $bundle = null;
    $bundle ??= omoLoadTranslationBundle('omo_rule_scope', $sourceLang);
    return t($key, [], $bundle, $sourceLang);
}

function omoRuleScopeRenderFields(array $state, array $context = []): void
{
    $escape = static fn ($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $scope = \dbObject\Rule::normalizeScope($state['scope'] ?? 'local');
    ?>
    <div class="generic-form-stack" data-rule-scope-fields data-rule-scope-context="<?= $escape(json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
        <div class="generic-form-field">
            <span class="generic-form-label"><?= $escape(omoRuleScopeT('scope')) ?></span>
            <input type="hidden" name="scope" value="<?= $escape($scope) ?>">
            <div class="omo-segmented" role="group" aria-label="<?= $escape(omoRuleScopeT('scope')) ?>">
                <?php foreach (\dbObject\Rule::scopes() as $choice): ?>
                    <button type="button" class="omo-segmented__button<?= $scope === $choice ? ' is-active' : '' ?>" data-rule-scope-choice="<?= $escape($choice) ?>" aria-pressed="<?= $scope === $choice ? 'true' : 'false' ?>"><?= $escape(omoRuleScopeT($choice)) ?></button>
                <?php endforeach; ?>
            </div>
            <small class="generic-help-text" aria-live="polite">
                <?php foreach (\dbObject\Rule::scopes() as $choice): ?>
                    <span data-rule-scope-help="<?= $escape($choice) ?>"<?= $scope === $choice ? '' : ' hidden' ?>><?= $escape(omoRuleScopeT('help.' . $choice)) ?></span>
                <?php endforeach; ?>
            </small>
        </div>
        <label class="generic-form-field" data-rule-authority-field<?= empty($context['usesAuthorities']) ? ' hidden' : '' ?>>
            <span class="generic-form-label"><?= $escape(omoRuleScopeT('authority')) ?></span>
            <select class="generic-form-control" name="IDauthority">
                <option value=""><?= $escape(omoRuleScopeT('none')) ?></option>
                <?php foreach (($context['authorities'] ?? []) as $authority): ?>
                    <option value="<?= (int)$authority['id'] ?>"<?= (int)($state['IDauthority'] ?? 0) === (int)$authority['id'] ? ' selected' : '' ?>><?= $escape($authority['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <small class="generic-help-text" data-rule-authority-required hidden><?= $escape(omoRuleScopeT('required')) ?></small>
            <small class="generic-help-text" data-rule-authority-unavailable hidden><?= $escape(omoRuleScopeT('unavailable')) ?></small>
        </label>
    </div>
    <?php
}
