<?php

use dbObject\DecisionGovernanceAction;
use dbObject\DecisionProcess;
use dbObject\DecisionProposal;
use dbObject\Rule;

if (!function_exists('omoDecisionGovernanceGetSourceLang')) {
    function omoDecisionGovernanceGetSourceLang()
    {
        return [
            'governance.title.create' => ['text' => 'Nouvelle décision hors réorg', 'context' => 'Governance decision creation title.'],
            'governance.title.edit' => ['text' => 'Modifier la décision hors réorg', 'context' => 'Governance decision editor title.'],
            'governance.intro' => ['text' => 'Préparez une ou plusieurs propositions. Chaque proposition peut regrouper plusieurs modifications qui seront appliquées ensemble si elle ne reçoit aucune objection.', 'context' => 'Governance decision editor introduction.'],
            'governance.field.title' => ['text' => 'Titre', 'context' => 'Governance process title field.'],
            'governance.field.intention' => ['text' => 'Intention et contexte', 'context' => 'Governance process description field.'],
            'governance.field.consultation_end' => ['text' => 'Fin de la consultation', 'context' => 'Governance consultation end field.'],
            'governance.field.vote_end' => ['text' => 'Fin du vote', 'context' => 'Governance evaluation end field.'],
            'governance.question.label' => ['text' => 'Question soumise au consentement', 'context' => 'Governance consent question label.'],
            'governance.question.help' => ['text' => 'Cette question est definie pour cette prise de decision.', 'context' => 'Governance custom consent question help.'],
            'governance.question.vote_label' => ['text' => 'Question soumise au vote', 'context' => 'Governance simple vote question label.'],
            'governance.question.vote_help' => ['text' => 'Cette question est definie pour cette prise de decision.', 'context' => 'Governance custom simple vote question help.'],
            'governance.question.default' => ['text' => 'Voyez-vous une raison pour laquelle appliquer les modifications suivantes nous causerait du tort ou nous éloignerait de notre raison d’être ?', 'context' => 'Default governance consent question.'],
            'governance.proposals.title' => ['text' => 'Propositions', 'context' => 'Governance proposals section title.'],
            'governance.proposals.help' => ['text' => 'Les modifications d’une même proposition sont indissociables et seront appliquées dans une seule transaction.', 'context' => 'Governance proposal atomicity help.'],
            'governance.proposal.add' => ['text' => 'Ajouter une proposition', 'context' => 'Add governance proposal button.'],
            'governance.proposal.default' => ['text' => 'Proposition {index}', 'context' => 'Default governance proposal title.'],
            'governance.proposal.title' => ['text' => 'Titre de la proposition', 'context' => 'Governance proposal title field.'],
            'governance.proposal.description' => ['text' => 'Description de la proposition', 'context' => 'Governance proposal description field.'],
            'governance.proposal.remove' => ['text' => 'Retirer la proposition', 'context' => 'Remove governance proposal button.'],
            'governance.action.add' => ['text' => 'Ajouter une modification', 'context' => 'Add governance action button.'],
            'governance.action.edit' => ['text' => 'Modifier', 'context' => 'Edit governance action button.'],
            'governance.action.remove' => ['text' => 'Retirer', 'context' => 'Remove governance action button.'],
            'governance.action.rule_update' => ['text' => 'Modifier une règle', 'context' => 'Rule update governance action label.'],
            'governance.action.rule_create' => ['text' => 'Créer une règle', 'context' => 'Rule creation governance action label.'],
            'governance.action.rule_delete' => ['text' => 'Supprimer une règle', 'context' => 'Rule deletion governance action label.'],
            'governance.action.role_update' => ['text' => 'Modifier un role', 'context' => 'Role update governance action label.'],
            'governance.action.role_create' => ['text' => 'Creer un role', 'context' => 'Role creation governance action label.'],
            'governance.action.role_delete' => ['text' => 'Supprimer un role', 'context' => 'Role deletion governance action label.'],
            'governance.action.choose' => ['text' => 'Choisissez une modification', 'context' => 'Governance action chooser title.'],
            'governance.action.rule' => ['text' => 'Règle', 'context' => 'Rule selection field.'],
            'governance.action.authority' => ['text' => 'Domaine d’autorité', 'context' => 'Rule authority field.'],
            'governance.action.local_rule' => ['text' => 'Règle locale au holon', 'context' => 'Local holon rule option.'],
            'governance.action.intention' => ['text' => 'Intention', 'context' => 'Rule intention field.'],
            'governance.action.content' => ['text' => 'Règle', 'context' => 'Rule content field.'],
            'governance.action.review_date' => ['text' => 'Date de requestionnement', 'context' => 'Rule review date field.'],
            'governance.action.expiration_date' => ['text' => 'Date d’échéance', 'context' => 'Rule expiration date field.'],
            'governance.action.cancel' => ['text' => 'Annuler', 'context' => 'Governance action modal cancel button.'],
            'governance.action.apply' => ['text' => 'Ajouter à la proposition', 'context' => 'Governance action modal apply button.'],
            'governance.action.update' => ['text' => 'Mettre à jour la modification', 'context' => 'Governance action modal update button.'],
            'governance.action.delete_help' => ['text' => 'La règle complète sera supprimée uniquement si cette proposition est acceptée et si son contenu n’a pas changé entre-temps.', 'context' => 'Deferred rule deletion explanation.'],
            'governance.action.confirm_delete' => ['text' => 'Ajouter la suppression', 'context' => 'Add deferred rule deletion button.'],
            'governance.save' => ['text' => 'Créer la prise de décision', 'context' => 'Governance decision create button.'],
            'governance.update' => ['text' => 'Enregistrer les modifications', 'context' => 'Governance decision update button.'],
            'governance.saving' => ['text' => 'Enregistrement…', 'context' => 'Governance decision saving label.'],
            'governance.error.generic' => ['text' => 'Impossible d’enregistrer cette prise de décision.', 'context' => 'Governance editor generic error.'],
            'governance.error.holon' => ['text' => 'Une décision hors réorg doit être créée dans un cercle ou un rôle.', 'context' => 'Governance editor invalid holon error.'],
            'governance.error.disabled' => ['text' => 'Les decisions hors reorg ne sont pas activees dans les parametres de cette organisation.', 'context' => 'Governance workflow disabled error.'],
            'governance.error.owner' => ['text' => 'Seul le créateur peut modifier les propositions de cette prise de décision.', 'context' => 'Governance editor owner error.'],
            'governance.error.locked' => ['text' => 'Les propositions sont verrouillées depuis le début du vote.', 'context' => 'Governance editor locked error.'],
            'governance.empty.rules' => ['text' => 'Aucune règle n’est définie directement dans ce contexte.', 'context' => 'Governance editor no rules message.'],
            'governance.status.pending' => ['text' => 'En attente', 'context' => 'Governance action pending status.'],
            'governance.status.applied' => ['text' => 'Appliquée', 'context' => 'Governance action applied status.'],
            'governance.status.rejected' => ['text' => 'Non acceptée', 'context' => 'Governance action rejected status.'],
            'governance.status.conflict' => ['text' => 'Conflit', 'context' => 'Governance action conflict status.'],
            'governance.status.failed' => ['text' => 'Échec', 'context' => 'Governance action failed status.'],
        ];
    }
}

if (!function_exists('omoDecisionGovernanceT')) {
    function omoDecisionGovernanceT($key, array $variables = [])
    {
        static $sourceLang = null;
        static $bundle = null;
        if ($sourceLang === null) {
            $sourceLang = omoDecisionGovernanceGetSourceLang();
            $bundle = omoLoadTranslationBundle('omo_decision_governance', $sourceLang);
        }
        return t((string)$key, $variables, $bundle, $sourceLang);
    }
}

if (!function_exists('omoDecisionGovernanceEncodeJson')) {
    function omoDecisionGovernanceEncodeJson($value, $fallback = '[]')
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return is_string($encoded) ? $encoded : $fallback;
    }
}

if (!function_exists('omoDecisionGovernanceBuildRuleClientData')) {
    function omoDecisionGovernanceBuildRuleClientData(Rule $rule)
    {
        $state = DecisionGovernanceAction::captureRuleState($rule);
        return [
            'id' => (int)$rule->getId(),
            'label' => trim((string)$rule->get('title')),
            'state' => $state,
        ];
    }
}

if (!function_exists('omoDecisionGovernanceDecorateRoleProperties')) {
    function omoDecisionGovernanceDecorateRoleProperties(array $properties)
    {
        foreach ($properties as &$property) {
            if (!is_array($property) || (string)($property['listItemType'] ?? '') !== 'authority') continue;
            $decodedValue = json_decode((string)($property['value'] ?? ''), true);
            $items = is_array($decodedValue['items'] ?? null)
                ? array_values($decodedValue['items'])
                : (is_array($decodedValue) ? array_values($decodedValue) : []);
            $authorityIds = [];
            foreach ($items as $item) {
                if (is_array($item) && !empty($item['delete'])) continue;
                $authorityId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
                if ($authorityId > 0) $authorityIds[] = $authorityId;
            }
            $labelsById = \dbObject\Authority::getLabelsByIds($authorityIds);
            $displayItems = [];
            foreach ($items as $item) {
                if (is_array($item) && !empty($item['delete'])) continue;
                $authorityId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
                $label = is_array($item) ? trim((string)($item['label'] ?? '')) : '';
                if ($label === '' && $authorityId > 0) $label = trim((string)($labelsById[$authorityId] ?? ''));
                if ($label !== '') $displayItems[] = $label;
            }
            $property['displayValue'] = implode('; ', $displayItems);
        }
        unset($property);
        return array_values($properties);
    }
}

if (!function_exists('omoDecisionGovernanceBuildRoleClientData')) {
    function omoDecisionGovernanceBuildRoleClientData(\dbObject\Holon $role, ?\dbObject\Organization $organization = null, $contextHolonId = 0)
    {
        $state = DecisionGovernanceAction::captureRoleState($role);
        $labelParts = [trim((string)$role->getDisplayName())];
        $parent = $role->getParentHolon();
        $guard = 0;
        while ($parent instanceof \dbObject\Holon && (int)$parent->getId() !== (int)$contextHolonId && $guard < 100) {
            if ((int)$parent->get('IDtypeholon') !== 3) break;
            array_unshift($labelParts, trim((string)$parent->getDisplayName()));
            $parent = $parent->getParentHolon();
            $guard++;
        }
        if ($organization instanceof \dbObject\Organization) {
            $editorData = $organization->getHolonCreationEditorData((int)$contextHolonId, (int)$role->getId(), true);
            $holon = is_array($editorData['holon'] ?? null) ? $editorData['holon'] : [];
            if (count($holon) > 0) {
                $properties = omoDecisionGovernanceDecorateRoleProperties(
                    is_array($holon['properties'] ?? null) ? array_values($holon['properties']) : []
                );
                $state['editor_payload'] = [
                    'templateId' => (int)($holon['templateId'] ?? 0),
                    'name' => (string)($holon['name'] ?? ''),
                    'fullName' => (string)($holon['fullName'] ?? ''),
                    'color' => (string)($holon['color'] ?? ''),
                    'icon' => (string)($holon['icon'] ?? ''),
                    'banner' => (string)($holon['banner'] ?? ''),
                    'adminMin' => $holon['adminMin'] ?? 0,
                    'adminMax' => $holon['adminMax'] ?? null,
                    'adminMinOverride' => !empty($holon['adminMinOverride']),
                    'adminMaxOverride' => !empty($holon['adminMaxOverride']),
                    'permissions' => is_array($holon['permissionAssignments'] ?? null) ? $holon['permissionAssignments'] : [],
                    'properties' => $properties,
                ];
            }
        }
        return [
            'id' => (int)$role->getId(),
            'label' => implode(' > ', array_filter($labelParts, static function ($label) { return $label !== ''; })),
            'state' => $state,
        ];
    }
}

if (!function_exists('omoDecisionGovernanceBuildBlueprint')) {
    function omoDecisionGovernanceBuildBlueprint(?DecisionProcess $decision)
    {
        if (!$decision instanceof DecisionProcess) {
            return [];
        }
        $blueprint = [];
        foreach ($decision->getProposals(true) as $proposal) {
            if (!$proposal instanceof DecisionProposal || !$proposal->hasGovernanceActions()) {
                continue;
            }
            $actions = [];
            foreach ($proposal->getGovernanceActions() as $action) {
                if (!$action instanceof DecisionGovernanceAction || (string)$action->get('status') === DecisionGovernanceAction::STATUS_REMOVED) {
                    continue;
                }
                $beforeState = DecisionGovernanceAction::normalizeState($action->get('before_state'));
                $afterState = DecisionGovernanceAction::normalizeState($action->get('after_state'));
                if ((string)$action->get('target_type') === DecisionGovernanceAction::TARGET_HOLON) {
                    if (is_array($beforeState['editor_payload']['properties'] ?? null)) {
                        $beforeState['editor_payload']['properties'] = omoDecisionGovernanceDecorateRoleProperties($beforeState['editor_payload']['properties']);
                    }
                    if (is_array($afterState['editor_payload']['properties'] ?? null)) {
                        $afterState['editor_payload']['properties'] = omoDecisionGovernanceDecorateRoleProperties($afterState['editor_payload']['properties']);
                    }
                }
                $actions[] = [
                    'id' => (int)$action->getId(),
                    'type' => (string)$action->get('action_type'),
                    'targetId' => (int)$action->get('target_id'),
                    'before' => $beforeState,
                    'after' => $afterState,
                    'status' => (string)$action->get('status'),
                    'statusMessage' => trim((string)$action->get('status_message')),
                ];
            }
            $blueprint[] = [
                'id' => (int)$proposal->getId(),
                'title' => trim((string)$proposal->get('title')),
                'description' => trim((string)$proposal->get('description')),
                'actions' => $actions,
            ];
        }
        return $blueprint;
    }
}

?>
