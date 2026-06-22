<?php

use dbObject\DecisionProcess;
use dbObject\DecisionGroup;

$viewInput = isset($omoDecisionInput) && is_array($omoDecisionInput) ? $omoDecisionInput : $_GET;

$baseSourceLang = [
    'decisions.edit.create_title' => [
        'text' => 'Nouvelle prise de decision',
        'context' => 'Drawer title when creating a decision process.',
    ],
    'decisions.edit.edit_title' => [
        'text' => 'Modifier la prise de decision',
        'context' => 'Drawer title when editing a decision process.',
    ],
    'decisions.edit.view_title' => [
        'text' => 'Voir la prise de decision',
        'context' => 'Drawer title when opening a decision in read-only mode.',
    ],
    'decisions.edit.description' => [
        'text' => 'Choisissez une methode, puis configurez un premier scrutin dans une structure pensee pour accueillir d autres modules plus tard.',
        'context' => 'Short description for the decision editor screen.',
    ],
    'decisions.edit.choose_title' => [
        'text' => 'Choisir une methode',
        'context' => 'Title of the method selection step.',
    ],
    'decisions.edit.choose_text' => [
        'text' => 'Commencez par selectionner la maniere d evaluer cette prise de decision. Le flux de creation detaille depend ensuite du module choisi.',
        'context' => 'Description of the method selection step.',
    ],
    'decisions.edit.choose_later' => [
        'text' => 'Bientot disponible',
        'context' => 'Badge for methods not implemented yet.',
    ],
    'decisions.edit.method.simple_vote.label' => [
        'text' => 'Vote simple',
        'context' => 'Label of the simple vote module card.',
    ],
    'decisions.edit.method.simple_vote.description' => [
        'text' => 'Chaque participant choisit une proposition parmi plusieurs options.',
        'context' => 'Description of the simple vote module card.',
    ],
    'decisions.edit.method.majority_judgment.label' => [
        'text' => 'Jugement majoritaire',
        'context' => 'Label of the majority judgment module card.',
    ],
    'decisions.edit.method.majority_judgment.description' => [
        'text' => 'Chaque proposition recoit une mention sur une echelle commune.',
        'context' => 'Description of the majority judgment module card.',
    ],
    'decisions.edit.method.consent.label' => [
        'text' => 'Consentement',
        'context' => 'Label of the consent module card.',
    ],
    'decisions.edit.method.consent.description' => [
        'text' => 'Une proposition est retenue tant qu aucune objection bloquante n est posee.',
        'context' => 'Description of the consent module card.',
    ],
    'decisions.edit.method.open' => [
        'text' => 'Configurer cette methode',
        'context' => 'CTA on an available method card.',
    ],
    'decisions.edit.method.locked' => [
        'text' => 'Methode verrouillee',
        'context' => 'Hint when editing an existing decision method.',
    ],
    'decisions.edit.unsupported_title' => [
        'text' => 'Methode non encore disponible',
        'context' => 'Title shown when a method exists but its editor is not implemented.',
    ],
    'decisions.edit.unsupported_text' => [
        'text' => 'Ce mode est bien prevu dans l architecture, mais son ecran de creation detaille n est pas encore branche.',
        'context' => 'Body shown when a method exists but its editor is not implemented.',
    ],
    'decisions.edit.summary.organization' => [
        'text' => 'Organisation',
        'context' => 'Summary label for the organization context.',
    ],
    'decisions.edit.summary.context' => [
        'text' => 'Contexte',
        'context' => 'Summary label for the current holon or organization level context.',
    ],
    'decisions.edit.summary.mode' => [
        'text' => 'Mode',
        'context' => 'Summary label for create or edit mode.',
    ],
    'decisions.edit.summary.method' => [
        'text' => 'Methode',
        'context' => 'Summary label for the selected evaluation method.',
    ],
    'decisions.edit.summary.target' => [
        'text' => 'Cible',
        'context' => 'Summary label for the edited decision title.',
    ],
    'decisions.edit.summary.mode_create' => [
        'text' => 'Creation',
        'context' => 'Summary value when creating a decision.',
    ],
    'decisions.edit.summary.mode_edit' => [
        'text' => 'Edition',
        'context' => 'Summary value when editing a decision.',
    ],
    'decisions.edit.summary.no_holon' => [
        'text' => 'Sans holon',
        'context' => 'Summary fallback when the decision is attached only to the organization.',
    ],
    'decisions.edit.context.organization_invalid' => [
        'text' => 'Organisation invalide.',
        'context' => 'Error when the organization id is missing or invalid.',
    ],
    'decisions.edit.context.organization_not_found' => [
        'text' => 'Organisation introuvable.',
        'context' => 'Error when the organization cannot be loaded.',
    ],
    'decisions.edit.context.organization_denied' => [
        'text' => 'Acces refuse a cette organisation.',
        'context' => 'Error when the user cannot view the organization.',
    ],
    'decisions.edit.context.organization_manage_denied' => [
        'text' => 'Vous n avez pas les droits necessaires pour creer une prise de decision dans cette organisation.',
        'context' => 'Error when the user cannot create an organization-level decision.',
    ],
    'decisions.edit.context.holon_not_found' => [
        'text' => 'Holon introuvable pour cette organisation.',
        'context' => 'Error when the requested holon is invalid.',
    ],
    'decisions.edit.context.holon_denied' => [
        'text' => 'Acces refuse a ce holon.',
        'context' => 'Error when the user cannot view the requested holon.',
    ],
    'decisions.edit.context.holon_manage_denied' => [
        'text' => 'Vous n avez pas les droits necessaires pour creer une prise de decision dans ce holon.',
        'context' => 'Error when the user cannot create a holon-level decision.',
    ],
    'decisions.edit.context.decision_not_found' => [
        'text' => 'Prise de decision introuvable.',
        'context' => 'Error when the requested decision cannot be loaded.',
    ],
    'decisions.edit.context.decision_mismatch' => [
        'text' => 'Cette prise de decision n appartient pas a l organisation courante.',
        'context' => 'Error when the decision does not belong to the current organization.',
    ],
    'decisions.edit.context.decision_denied' => [
        'text' => 'Vous n avez pas les droits necessaires pour modifier cette prise de decision.',
        'context' => 'Error when the user cannot manage the requested decision.',
    ],
    'decisions.edit.groups.title' => [
        'text' => 'Groupes',
        'context' => 'Section title for decision groups navigation.',
    ],
    'decisions.edit.groups.text' => [
        'text' => 'Ajoutez plusieurs blocs de decision dans le meme processus, puis passez de l un a l autre.',
        'context' => 'Help text for decision groups navigation.',
    ],
    'decisions.edit.groups.add' => [
        'text' => 'Ajouter un groupe',
        'context' => 'Button label to create a new decision group.',
    ],
];

$selectedGroup = (!empty($context['decisionGroup']) && $context['decisionGroup'] instanceof DecisionGroup)
    ? $context['decisionGroup']
    : null;
$groupAction = trim((string)($viewInput['group_action'] ?? ''));
$selectedMethod = '';
if (!($selectedGroup instanceof DecisionGroup) && !empty($context['decision']) && $context['decision'] instanceof DecisionProcess) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($context['decision']->get('evaluation_method'));
} elseif ($selectedGroup instanceof DecisionGroup && !($context['decision'] instanceof DecisionProcess && $groupAction === 'create' && !isset($viewInput['method']))) {
    $selectedMethod = DecisionProcess::normalizeEvaluationMethod($selectedGroup->get('evaluation_method'));
} elseif (isset($viewInput['method'])) {
    $requestedMethod = trim((string)$viewInput['method']);
    if ($requestedMethod !== '' && omoDecisionGetModuleDefinition($requestedMethod)) {
        $selectedMethod = $requestedMethod;
    }
}

$moduleDefinition = null;
if ($selectedMethod !== '') {
    $moduleDefinition = omoDecisionGetModuleDefinition($selectedMethod);
    if ($moduleDefinition && !empty($moduleDefinition['shared_file']) && is_file($moduleDefinition['shared_file'])) {
        require_once $moduleDefinition['shared_file'];
    }
    if ($moduleDefinition && !empty($moduleDefinition['editor_file']) && is_file($moduleDefinition['editor_file'])) {
        require_once $moduleDefinition['editor_file'];
        $moduleSourceFunction = (string)($moduleDefinition['source_lang_function'] ?? '');
        if ($moduleSourceFunction !== '' && function_exists($moduleSourceFunction)) {
            $baseSourceLang = array_merge($baseSourceLang, $moduleSourceFunction());
        }
    }
}

if (function_exists('omoDecisionInvitationGetSourceLang')) {
    $baseSourceLang = array_merge($baseSourceLang, omoDecisionInvitationGetSourceLang());
}

$lang = omoLoadTranslationBundle('omo_decision_edit', $baseSourceLang);
$escape = 'omoApiEscape';

if (empty($context['status'])) {
    http_response_code((int)($context['code'] ?? 400));
    $errorKey = (string)($context['error_key'] ?? 'decisions.edit.context.organization_invalid');
    ?>
    <div class="omo-decision-edit omo-panel-view">
        <div class="omo-panel-view__body">
            <div class="omo-panel-view__body_content">
                <div class="omo-empty-state"><?= $escape(t($errorKey, [], $lang, $baseSourceLang)) ?></div>
            </div>
        </div>
    </div>
    <?php
    return;
}

$organization = $context['organization'];
$decision = $context['decision'];
$effectiveHolon = $context['effectiveHolon'];
$intent = (string)($context['intent'] ?? 'manage');
$isEditing = $decision instanceof DecisionProcess;
$decisionGroups = $isEditing ? $decision->getDecisionGroups(false) : [];
$modeLabel = $isEditing
    ? t('decisions.edit.summary.mode_edit', [], $lang, $baseSourceLang)
    : t('decisions.edit.summary.mode_create', [], $lang, $baseSourceLang);
$contextLabel = $effectiveHolon
    ? trim((string)$effectiveHolon->get('name'))
    : t('decisions.edit.summary.no_holon', [], $lang, $baseSourceLang);
$registry = omoDecisionGetModuleRegistry();
$selectedDefinition = $selectedMethod !== '' ? omoDecisionGetModuleDefinition($selectedMethod) : null;
$selectedLabel = $selectedDefinition
    ? t((string)$selectedDefinition['label_key'], [], $lang, $baseSourceLang)
    : '';
$showContextSummary = (($context['accessMode'] ?? '') !== 'public') && empty($context['previewLayout']);

if (!function_exists('omoDecisionRenderEditorGroupSwitch')) {
    function omoDecisionRenderEditorGroupSwitch(array $context, ?DecisionProcess $decision, ?DecisionGroup $selectedGroup, iterable $decisionGroups, array $lang, array $baseSourceLang, string $escape): void
    {
        if (!$decision instanceof DecisionProcess || (($context['intent'] ?? 'manage') !== 'manage')) {
            return;
        }
        ?>
        <section class="generic-soft-panel generic-soft-panel--stack omo-decision-edit__group-switch">
            <div class="omo-decision-edit__section-head">
                <div>
                    <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.edit.groups.title', [], $lang, $baseSourceLang)) ?></h3>
                    <p class="omo-decision-edit__lead"><?= $escape(t('decisions.edit.groups.text', [], $lang, $baseSourceLang)) ?></p>
                </div>
                <a
                    class="generic-action-button generic-action-button--secondary"
                    href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), '', 'manage', 0, 'create')) ?>"
                    data-omo-decision-editor-link
                    data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                >
                    <?= $escape(t('decisions.edit.groups.add', [], $lang, $baseSourceLang)) ?>
                </a>
            </div>
            <div class="omo-decision-edit__group-tabs">
                <?php foreach ($decisionGroups as $groupItem): ?>
                    <?php
                    $groupId = (int)$groupItem->getId();
                    $groupMethodDefinition = omoDecisionGetModuleDefinition((string)$groupItem->get('evaluation_method'));
                    $groupMethodLabel = $groupMethodDefinition
                        ? t((string)$groupMethodDefinition['label_key'], [], $lang, $baseSourceLang)
                        : trim((string)$groupItem->get('evaluation_method'));
                    $groupTitle = trim((string)$groupItem->get('title'));
                    if ($groupTitle === '') {
                        $groupTitle = 'Bloc ' . (string)$groupItem->get('position');
                    }
                    ?>
                    <a
                        class="generic-action-button <?= $selectedGroup && (int)$selectedGroup->getId() === $groupId ? 'generic-action-button--main' : 'generic-action-button--secondary' ?>"
                        href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), trim((string)$groupItem->get('evaluation_method')), 'manage', $groupId)) ?>"
                        data-omo-decision-editor-link
                        data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                    >
                        <?= $escape($groupTitle) ?> - <?= $escape($groupMethodLabel) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}
?>
<div class="omo-decision-edit omo-panel-view">
    <div class="omo-panel-view__body">
        <div class="omo-panel-view__body_content omo-decision-edit__stack">
            

            <?php if (false && $isEditing && $intent === 'manage'): ?>
            <section class="generic-soft-panel generic-soft-panel--stack omo-decision-edit__group-switch">
                <div class="omo-decision-edit__section-head">
                    <div>
                        <h3 class="generic-card-title generic-card-title--section">Groupes</h3>
                        <p class="omo-decision-edit__lead">Ajoutez plusieurs blocs de decision dans le meme processus, puis passez de l un a l autre.</p>
                    </div>
                    <a
                        class="generic-action-button generic-action-button--secondary"
                        href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), '', 'manage', 0, 'create')) ?>"
                        data-omo-decision-editor-link
                        data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                    >
                        Ajouter un groupe
                    </a>
                </div>
                <div class="omo-decision-edit__group-tabs">
                    <?php foreach ($decisionGroups as $groupItem): ?>
                        <?php
                        $groupId = (int)$groupItem->getId();
                        $groupMethodDefinition = omoDecisionGetModuleDefinition((string)$groupItem->get('evaluation_method'));
                        $groupMethodLabel = $groupMethodDefinition
                            ? t((string)$groupMethodDefinition['label_key'], [], $lang, $baseSourceLang)
                            : trim((string)$groupItem->get('evaluation_method'));
                        $groupTitle = trim((string)$groupItem->get('title'));
                        if ($groupTitle === '') {
                            $groupTitle = 'Bloc ' . (string)$groupItem->get('position');
                        }
                        ?>
                        <a
                            class="generic-action-button <?= $selectedGroup && (int)$selectedGroup->getId() === $groupId ? 'generic-action-button--main' : 'generic-action-button--secondary' ?>"
                            href="<?= $escape(omoDecisionBuildEditorUrl((int)$context['organizationId'], (int)$context['targetHolonId'], (int)$decision->getId(), trim((string)$groupItem->get('evaluation_method')), 'manage', $groupId)) ?>"
                            data-omo-decision-editor-link
                            data-omo-decision-editor-title="<?= $escape(t('decisions.edit.edit_title', [], $lang, $baseSourceLang)) ?>"
                        >
                            <?= $escape($groupTitle) ?> · <?= $escape($groupMethodLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if ((!$isEditing && $selectedMethod === '') || ($isEditing && $groupAction === 'create' && $selectedMethod === '')): ?>
            <section class="generic-section generic-section--stack">
                <div class="omo-decision-edit__section-head">
                    <div>
                        <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.edit.choose_title', [], $lang, $baseSourceLang)) ?></h3>
                        <p class="omo-decision-edit__lead"><?= $escape($isEditing ? 'Choisissez la methode du nouveau groupe.' : t('decisions.edit.choose_text', [], $lang, $baseSourceLang)) ?></p>
                    </div>
                </div>

                <div class="omo-decision-edit__module-grid">
                    <?php foreach ($registry as $methodKey => $definition): ?>
                        <?php
                        $methodUrl = omoDecisionBuildEditorUrl(
                            (int)$context['organizationId'],
                            (int)$context['targetHolonId'],
                            $isEditing ? (int)$decision->getId() : 0,
                            (string)$methodKey,
                            'manage',
                            0,
                            $isEditing ? 'create' : ''
                        );
                        $isAvailable = !empty($definition['available']);
                        ?>
                        <article class="generic-soft-panel generic-soft-panel--stack omo-decision-edit__module-card<?= $isAvailable ? ' is-available' : '' ?>">
                            <div class="omo-decision-edit__module-copy">
                                <div class="omo-decision-edit__module-headline">
                                    <h4 class="generic-card-title"><?= $escape(t((string)$definition['label_key'], [], $lang, $baseSourceLang)) ?></h4>
                                    <?php if (!$isAvailable): ?>
                                    <span class="omo-decision-edit__module-badge"><?= $escape(t('decisions.edit.choose_later', [], $lang, $baseSourceLang)) ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="omo-decision-edit__text"><?= $escape(t((string)$definition['description_key'], [], $lang, $baseSourceLang)) ?></p>
                            </div>

                            <?php if ($isAvailable): ?>
                            <a
                                class="generic-action-button generic-action-button--main omo-decision-edit__module-action"
                                href="<?= $escape($methodUrl) ?>"
                                data-omo-decision-editor-link
                                data-omo-decision-editor-title="<?= $escape($isEditing ? t('decisions.edit.edit_title', [], $lang, $baseSourceLang) : t('decisions.edit.create_title', [], $lang, $baseSourceLang)) ?>"
                            >
                                <?= $escape(t('decisions.edit.method.open', [], $lang, $baseSourceLang)) ?>
                            </a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php elseif ($selectedDefinition && !empty($selectedDefinition['available']) && !empty($selectedDefinition['render_function']) && function_exists((string)$selectedDefinition['render_function'])): ?>
                <?php
                $renderFunction = (string)$selectedDefinition['render_function'];
                $renderFunction([
                    'context' => $context,
                    'decision' => $decision,
                    'organization' => $organization,
                    'effectiveHolon' => $effectiveHolon,
                    'lang' => $lang,
                    'sourceLang' => $baseSourceLang,
                    'escape' => $escape,
                    'selectedMethod' => $selectedMethod,
                ]);
                ?>
            <?php else: ?>
            <section class="generic-section generic-section--stack">
                <h3 class="generic-card-title generic-card-title--section"><?= $escape(t('decisions.edit.unsupported_title', [], $lang, $baseSourceLang)) ?></h3>
                <p class="omo-decision-edit__text"><?= $escape(t('decisions.edit.unsupported_text', [], $lang, $baseSourceLang)) ?></p>
                <?php if ($isEditing): ?>
                <p class="omo-decision-edit__text omo-decision-edit__muted"><?= $escape(t('decisions.edit.method.locked', [], $lang, $baseSourceLang)) ?></p>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    function openEditorUrl(url, title) {
        if (!url) {
            return;
        }

        if (typeof window.omoDecisionOpenNestedDrawer === 'function') {
            window.omoDecisionOpenNestedDrawer(title || 'Prises de decision', url, '');
            return;
        }

        if (typeof window.commonTopbarOpenDrawer === 'function') {
            window.commonTopbarOpenDrawer(title || 'Prises de decision', url, 'fetch');
            return;
        }

        window.location.href = url;
    }

    document.querySelectorAll('[data-omo-decision-editor-link]').forEach(function (link) {
        if (link.dataset.omoDecisionEditorReady === '1') {
            return;
        }

        link.dataset.omoDecisionEditorReady = '1';
        link.addEventListener('click', function (event) {
            event.preventDefault();
            openEditorUrl(
                link.getAttribute('href') || '',
                link.getAttribute('data-omo-decision-editor-title') || 'Prises de decision'
            );
        });
    });
})();
</script>

<style>
.omo-decision-edit__stack {
    display: grid;
    gap: 16px;
}

.omo-decision-edit__summary-grid,
.omo-decision-edit__module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.omo-decision-edit__section-head,
.omo-decision-edit__module-copy {
    display: grid;
    gap: 8px;
}

.omo-decision-edit__module-card {
    justify-content: space-between;
    min-height: 220px;
}

.omo-decision-edit__module-card.is-available {
    border: 1px solid color-mix(in srgb, var(--color-primary, #2563eb) 28%, white);
}

.omo-decision-edit__module-headline {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
}

.omo-decision-edit__module-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-text-light, #64748b) 12%, white);
    color: var(--color-text-light, #475569);
    font-size: 12px;
    white-space: nowrap;
}

.omo-decision-edit__module-action {
    align-self: start;
}

.omo-decision-edit__lead,
.omo-decision-edit__text {
    margin: 0;
    color: var(--color-text-light, #475569);
    line-height: 1.6;
}

.omo-decision-edit__muted {
    font-size: 14px;
}
</style>
