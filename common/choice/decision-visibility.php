<?php

require_once dirname(__DIR__) . '/object_visibility_selector.php';

function commonDecisionRenderVisibilitySelectors(?\dbObject\DecisionProcess $decision, array $context, bool $editable, string $idPrefix): string
{
    static $lang = null;
    $sourceLang = [
        'visibility' => ['text' => 'Visibilité', 'context' => 'Decision read visibility selector.'],
        'edit_visibility' => ['text' => 'Édition', 'context' => 'Decision management visibility selector.'],
        'read_help' => ['text' => 'Les personnes invitées peuvent toujours voir le scrutin. Cette visibilité permet aux autres personnes de le consulter et de voir les résultats publiés, sans participer.', 'context' => 'Decision read visibility help.'],
        'edit_help' => ['text' => 'L’auteur peut toujours gérer le scrutin. Les personnes autorisées ici peuvent aussi le gérer, sans devenir participantes. Le contexte reste verrouillé dès le début du vote.', 'context' => 'Decision edit visibility help.'],
    ];
    $lang ??= omoLoadTranslationBundle('common_decision_visibility', $sourceLang);
    $config = \dbObject\DecisionProcess::buildVisibilityEditorConfig(
        (int)($context['organizationId'] ?? 0),
        (int)($decision ? $decision->get('IDholon') : ($context['targetHolonId'] ?? 0))
    );
    $disabled = $editable ? $config['disabledTypes'] : array_fill_keys(array_keys($config['visibilityOptions']), true);
    $html = '';
    foreach (['visibility_type' => 'visibility', 'edit_visibility_type' => 'edit_visibility'] as $inputName => $labelKey) {
        $selected = $inputName === 'visibility_type'
            ? ($decision ? $decision->get('visibility_type') : \dbObject\DecisionProcess::getDefaultVisibilityType())
            : ($decision ? $decision->getPrimaryEditVisibilityRuleRow()['visibility_type'] : \dbObject\DecisionProcess::getDefaultEditVisibilityType());
        $html .= '<div class="generic-form-field">' . commonRenderObjectVisibilitySelector([
            'inputName' => $inputName,
            'fieldLabel' => t($labelKey, [], $lang, $sourceLang),
            'selectedValue' => $selected,
            'optionLabels' => $config['visibilityOptions'],
            'disabledValues' => $disabled,
            'idPrefix' => $idPrefix . '-' . $inputName,
            'hint' => t($inputName === 'visibility_type' ? 'read_help' : 'edit_help', [], $lang, $sourceLang),
            'hintAsContextHelp' => true,
        ]) . '</div>';
    }
    return $html;
}
