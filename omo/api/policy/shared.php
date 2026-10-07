<?php

use dbObject\ArrayAuthority;
use dbObject\Authority;
use dbObject\Holon;
use dbObject\Organization;
use dbObject\Rule;

if (!function_exists('omoPolicySourceLang')) {
    function omoPolicySourceLang()
    {
        return [
            'policy.title' => ['text' => 'Règlement', 'context' => 'Policy application title.'],
            'policy.description' => ['text' => 'Règles applicables au périmètre choisi.', 'context' => 'Policy application description.'],
            'policy.scope' => ['text' => 'Périmètre', 'context' => 'Label for the policy context scope selector.'],
            'policy.scope.local' => ['text' => 'Local', 'context' => 'Policy filter showing only rules defined in the current holon.'],
            'policy.scope.contextual' => ['text' => 'Contextuelles', 'context' => 'Policy filter showing all rules applicable to the selected holon.'],
            'policy.scope.global' => ['text' => 'Global', 'context' => 'Policy filter showing all rules in the organization, wherever they are defined.'],
            'policy.sort' => ['text' => 'Ordre', 'context' => 'Label for the policy rule ordering selector.'],
            'policy.sort.alpha' => ['text' => 'Alphabétique', 'context' => 'Policy rules ordered alphabetically.'],
            'policy.sort.created' => ['text' => 'Par date de création', 'context' => 'Policy rules ordered by creation date.'],
            'policy.sort.updated' => ['text' => 'Par date de modification', 'context' => 'Policy rules ordered by modification date.'],
            'policy.group' => ['text' => 'Regroupement', 'context' => 'Label for the policy rule grouping selector.'],
            'policy.group.holon' => ['text' => 'Par espace', 'context' => 'Policy rules grouped by space tree.'],
            'policy.group.authority' => ['text' => 'Par autorité', 'context' => 'Policy rules grouped by authority tree.'],
            'policy.group.none' => ['text' => 'Sans regroupement', 'context' => 'Policy rules displayed in one flat list without grouping.'],
            'policy.group.local_rules' => ['text' => 'Règles locales — {holon}', 'context' => 'Local rules when policy rules are grouped by authority.'],
            'policy.group.unnamed_authority' => ['text' => 'Autorité sans libellé', 'context' => 'Fallback title for an authority without a label.'],
            'policy.group.unknown' => ['text' => 'Rattachement inconnu', 'context' => 'Fallback title for a rule without a usable holon or authority.'],
            'policy.filters.aria' => ['text' => 'Filtres du règlement', 'context' => 'Accessible label for the compact policy scope filter.'],
            'policy.filters.apply' => ['text' => 'Appliquer', 'context' => 'Button applying the temporary policy scope selection.'],
            'policy.filters.save_view' => ['text' => 'Enregistrer la vue', 'context' => 'Button saving the policy scope selection for this holon.'],
            'policy.filters.more_actions' => ['text' => 'Autres options de vue', 'context' => 'Accessible label for additional policy view preference actions.'],
            'policy.filters.apply_everywhere' => ['text' => 'Appliquer partout', 'context' => 'Action setting the current policy view as the default and clearing specific views.'],
            'policy.filters.set_default' => ['text' => 'Définir comme vue par défaut', 'context' => 'Action saving the current policy view as the default view.'],
            'policy.filters.restore_default' => ['text' => 'Restaurer la vue par défaut', 'context' => 'Action removing the current holon specific policy view.'],
            'policy.search.aria' => ['text' => 'Filtrer les règles affichées', 'context' => 'Accessible label for the policy quick search input.'],
            'policy.search.placeholder' => ['text' => 'Filtrer les règles', 'context' => 'Placeholder for the policy quick search input.'],
            'policy.search.empty' => ['text' => 'Aucune règle ne correspond à cette recherche.', 'context' => 'Empty state when the policy quick search hides every rule.'],
            'policy.empty.local' => ['text' => 'Aucune règle définie dans cet espace.', 'context' => 'Empty policy list for locally defined rules.'],
            'policy.empty.contextual' => ['text' => 'Aucune règle ne s’applique à cet espace.', 'context' => 'Empty policy list for applicable rules.'],
            'policy.empty.global' => ['text' => 'Aucune règle dans cette organisation.', 'context' => 'Empty organization policy list.'],
            'policy.empty.title' => ['text' => 'Aucune règle pour le moment', 'context' => 'Title for the policy empty state.'],
            'policy.new' => ['text' => 'Nouvelle règle', 'context' => 'Create a new rule.'],
            'policy.edit' => ['text' => 'Modifier la règle', 'context' => 'Edit an existing rule.'],
            'policy.delete' => ['text' => 'Supprimer la règle', 'context' => 'Delete an existing rule.'],
            'policy.delete.confirm' => ['text' => "Supprimer cette règle ?\n\nCette action est définitive.", 'context' => 'Confirmation shown before deleting an existing rule.'],
            'policy.empty' => ['text' => 'Aucune règle dans ce contexte.', 'context' => 'Empty policy list.'],
            'policy.intention' => ['text' => 'Intention', 'context' => 'Rule intent section title.'],
            'policy.review' => ['text' => 'À reconsidérer le {date}', 'context' => 'Rule review date label.'],
            'policy.expiration' => ['text' => 'Échéance le {date}', 'context' => 'Rule expiration date label.'],
            'policy.status.review' => ['text' => 'À vérifier', 'context' => 'Status badge for a rule whose review date has been reached.'],
            'policy.status.expired' => ['text' => 'Obsolète', 'context' => 'Status badge for a rule past its expiration date.'],
            'policy.created' => ['text' => 'Créée le {date} par {user}', 'context' => 'Rule creation metadata.'],
            'policy.updated' => ['text' => 'Modifiée le {date} par {user}', 'context' => 'Rule modification metadata.'],
            'policy.holon' => ['text' => 'Espace : {holon}', 'context' => 'Rule space attachment metadata.'],
            'policy.authority' => ['text' => 'Autorité : {authority}', 'context' => 'Rule authority attachment metadata.'],
            'policy.documentation' => ['text' => 'Informations et traçabilité', 'context' => 'Collapsed legal and audit metadata for a rule.'],
            'policy.drawer.title' => ['text' => 'Nouvelle règle', 'context' => 'Rule creation drawer title.'],
            'policy.drawer.description' => ['text' => 'Cette règle peut être rattachée à l’espace courant ou à l’une de ses autorités.', 'context' => 'Rule creation drawer description.'],
            'policy.drawer.description_local' => ['text' => 'Cette règle est rattachée à l’espace courant.', 'context' => 'Rule creation drawer description when no authority exists.'],
            'policy.drawer.description_organization' => ['text' => 'Cette règle est rattachée directement à l’organisation.', 'context' => 'Rule creation drawer description without a structure.'],
            'policy.drawer.title_edit' => ['text' => 'Modifier la règle', 'context' => 'Rule edit drawer title.'],
            'policy.drawer.description_edit' => ['text' => 'Modifiez le contenu, le rattachement ou les dates de cette règle.', 'context' => 'Rule edit drawer description.'],
            'policy.field.title' => ['text' => 'Titre', 'context' => 'Rule title field.'],
            'policy.field.intention' => ['text' => 'Intention', 'context' => 'Rule intent field.'],
            'policy.field.description' => ['text' => 'Règle', 'context' => 'Rule HTML content field.'],
            'policy.field.authority' => ['text' => 'Autorité associée', 'context' => 'Optional direct authority used as the rule attachment.'],
            'policy.field.authority_local' => ['text' => 'Aucune (règle locale à l’espace)', 'context' => 'Authority selector option for a direct local rule.'],
            'policy.field.authority_organization' => ['text' => 'Aucune (règle de l’organisation)', 'context' => 'Authority selector option when editing an organization rule.'],
            'policy.field.review_date' => ['text' => 'Date de requestionnement', 'context' => 'Rule review date field.'],
            'policy.field.expiration_date' => ['text' => 'Date d’échéance', 'context' => 'Rule expiration date field.'],
            'policy.save' => ['text' => 'Enregistrer', 'context' => 'Save local rule action.'],
            'policy.close' => ['text' => 'Fermer', 'context' => 'Close drawer action.'],
            'policy.error.context' => ['text' => 'Contexte invalide ou inaccessible.', 'context' => 'Invalid policy context.'],
            'policy.error.authority' => ['text' => 'L’autorité choisie doit être rattachée directement à l’espace courant.', 'context' => 'An invalid authority was selected for a new rule.'],
            'policy.error.forbidden' => ['text' => 'Vous ne pouvez pas créer de règle dans ce contexte.', 'context' => 'Unauthorized rule creation.'],
            'policy.error.method' => ['text' => 'Cette action doit être envoyée en POST.', 'context' => 'Invalid HTTP method.'],
            'policy.error.load' => ['text' => 'Impossible de charger le formulaire.', 'context' => 'Local rule editor load error.'],
            'policy.error.save' => ['text' => 'Impossible d’enregistrer la règle.', 'context' => 'Rule save error.'],
            'policy.error.delete' => ['text' => 'Impossible de supprimer la règle.', 'context' => 'Rule delete error.'],
            'policy.success.save' => ['text' => 'Règle enregistrée.', 'context' => 'Rule creation confirmation.'],
            'policy.success.update' => ['text' => 'Règle modifiée.', 'context' => 'Rule update confirmation.'],
            'policy.success.delete' => ['text' => 'Règle supprimée.', 'context' => 'Rule deletion confirmation.'],
        ];
    }
}

if (!function_exists('omoPolicyLoadTranslationBundle')) {
    function omoPolicyLoadTranslationBundle()
    {
        static $bundle = null;
        if ($bundle === null) {
            $bundle = omoLoadTranslationBundle('omo_policy', omoPolicySourceLang());
        }
        return $bundle;
    }
}

if (!function_exists('omoPolicyT')) {
    function omoPolicyT($key, array $replace = [])
    {
        return t($key, $replace, omoPolicyLoadTranslationBundle(), omoPolicySourceLang());
    }
}

if (!function_exists('omoPolicyResolveContext')) {
    function omoPolicyResolveContext($organizationId, $holonId = 0)
    {
        $organization = new Organization();
        if ((int)$organizationId <= 0 || !$organization->load((int)$organizationId) || !$organization->canViewDetail()) {
            return ['status' => false, 'message' => omoPolicyT('policy.error.context')];
        }

        $rootHolon = $organization->getEnabledStructuralRootHolon();
        $currentHolon = $rootHolon instanceof Holon ? $rootHolon : null;
        if ((int)$holonId > 0) {
            $candidate = new Holon();
            if (!($rootHolon instanceof Holon) || !$candidate->load((int)$holonId) || !$candidate->isDescendantOf((int)$rootHolon->getId(), true) || !$candidate->canViewDetail()) {
                return ['status' => false, 'message' => omoPolicyT('policy.error.context')];
            }
            $currentHolon = $candidate;
        }

        return [
            'status' => true,
            'message' => '',
            'organization' => $organization,
            'rootHolon' => $rootHolon,
            'currentHolon' => $currentHolon,
        ];
    }
}

if (!function_exists('omoPolicyCanCreateLocalRule')) {
    function omoPolicyCanCreateLocalRule(array $context)
    {
        $holon = $context['currentHolon'] ?? null;
        if ($holon instanceof Holon) {
            return $holon->isAllowed('CAN_CREATE_RULE', false);
        }
        $organization = $context['organization'] ?? null;
        $currentUserId = function_exists('commonGetCurrentUserId') ? (int)commonGetCurrentUserId() : 0;
        return $organization instanceof Organization && \dbObject\Permission::userCanInOrganization('CAN_CREATE_RULE', (int)$organization->getId(), $currentUserId);
    }
}

if (!function_exists('omoPolicyNormalizeSort')) {
    function omoPolicyNormalizeSort($sort)
    {
        return in_array($sort, ['created', 'updated'], true) ? $sort : 'alpha';
    }
}

if (!function_exists('omoPolicyNormalizeGroup')) {
    function omoPolicyNormalizeGroup($group)
    {
        return $group === 'authority' ? 'authority' : ($group === 'none' ? 'none' : 'holon');
    }
}

if (!function_exists('omoPolicyGetDirectAuthorities')) {
    function omoPolicyGetDirectAuthorities(?Holon $holon, ?Organization $organization = null)
    {
        $authorities = new ArrayAuthority();
        if (!($holon instanceof Holon)) {
            return $authorities;
        }
        if ($organization instanceof Organization) {
            $organization->ensureTemplateAuthorityInstancesForHolon($holon);
        }

        $authorities->loadForHolon((int)$holon->getId());
        return $authorities;
    }
}

if (!function_exists('omoPolicyBuildRuleEntries')) {
    function omoPolicyBuildRuleEntries(iterable $organizationRules, iterable $visibleRules): array
    {
        $visibleIds = [];
        foreach ($visibleRules as $rule) {
            $visibleIds[(int)$rule->getId()] = true;
        }
        $entries = [];
        foreach ($organizationRules as $rule) {
            if ($rule instanceof Rule) {
                $entries[] = [
                    'rule' => $rule,
                    'visible' => isset($visibleIds[(int)$rule->getId()]),
                    'holon' => $rule->getHolon(),
                    'authority' => $rule->getAuthority(),
                ];
            }
        }
        return $entries;
    }
}

if (!function_exists('omoPolicyBuildRuleGroups')) {
    /** Holon groups define canonical references before filtering or display sorting. */
    function omoPolicyBuildRuleGroups(array $policyRuleEntries, string $policyGroup, Organization $organization): array
    {
        $policyGroupNodes = [];
        $policyRegisterNode = static function ($key, $label, $parentKey = null) use (&$policyGroupNodes) {
            if (!isset($policyGroupNodes[$key])) {
                $policyGroupNodes[$key] = [
                    'key' => $key,
                    'label' => $label,
                    'parent' => $parentKey,
                    'rules' => [],
                    'allRules' => [],
                    'children' => [],
                ];
            } elseif ($policyGroupNodes[$key]['parent'] === null && $parentKey !== null) {
                $policyGroupNodes[$key]['parent'] = $parentKey;
            }

            return $key;
        };
        $policyRegisterHolon = null;
        $policyRegisterHolon = static function ($holon, array $seen = []) use (&$policyRegisterHolon, $policyRegisterNode) {
            if (!($holon instanceof Holon)) {
                return null;
            }

            $holonId = (int)$holon->getId();
            if ($holonId <= 0 || isset($seen[$holonId])) {
                return null;
            }
            $seen[$holonId] = true;
            $parent = $holon->getParentHolon();
            $parentKey = $parent instanceof Holon ? $policyRegisterHolon($parent, $seen) : null;
            return $policyRegisterNode('holon:' . $holonId, $holon->getFullDisplayName(), $parentKey);
        };
        $policyRegisterAuthority = null;
        $policyRegisterAuthority = static function ($authority, array $seen = []) use (&$policyRegisterAuthority, $policyRegisterNode) {
            if (!($authority instanceof Authority)) {
                return null;
            }

            $authorityId = (int)$authority->getId();
            if ($authorityId <= 0 || isset($seen[$authorityId])) {
                return null;
            }
            $seen[$authorityId] = true;
            $parent = $authority->getParent();
            $parentKey = $parent instanceof Authority ? $policyRegisterAuthority($parent, $seen) : null;
            if ((int)$authority->get('is_shell') === 1) {
                return $parentKey;
            }
            $label = trim((string)$authority->get('label'));
            return $policyRegisterNode('authority:' . $authorityId, $label !== '' ? $label : omoPolicyT('policy.group.unnamed_authority'), $parentKey);
        };
        if ($policyGroup === 'none') {
            $policyGroupNodes['flat'] = [
                'key' => 'flat',
                'label' => '',
                'parent' => null,
                'rules' => array_values(array_filter($policyRuleEntries, static fn (array $entry) => $entry['visible'])),
                'allRules' => $policyRuleEntries,
                'children' => [],
            ];
        } else {
            foreach ($policyRuleEntries as $entry) {
                $ruleHolon = $entry['holon'];
                $ruleAuthority = $entry['authority'];
                if ($policyGroup === 'authority' && $ruleAuthority instanceof Authority) {
                    $nodeKey = $policyRegisterAuthority($ruleAuthority);
                } elseif ($policyGroup === 'authority') {
                    $holonLabel = $ruleHolon instanceof Holon ? $ruleHolon->getFullDisplayName() : (string)$organization->get('name');
                    $nodeKey = $policyRegisterNode('local:' . ($ruleHolon instanceof Holon ? (int)$ruleHolon->getId() : 'organization'), omoPolicyT('policy.group.local_rules', ['holon' => $holonLabel]));
                } else {
                    $nodeKey = $ruleHolon instanceof Holon
                        ? $policyRegisterHolon($ruleHolon)
                        : $policyRegisterNode('organization', (string)$organization->get('name'));
                }

                if ($nodeKey === null) {
                    $nodeKey = $policyRegisterNode('unknown', omoPolicyT('policy.group.unknown'));
                }
                $policyGroupNodes[$nodeKey]['allRules'][] = $entry;
                if ($entry['visible']) {
                    $policyGroupNodes[$nodeKey]['rules'][] = $entry;
                }
            }
        }
        foreach ($policyGroupNodes as $nodeKey => $node) {
            $parentKey = $node['parent'];
            if ($parentKey !== null && isset($policyGroupNodes[$parentKey])) {
                $policyGroupNodes[$parentKey]['children'][] = $nodeKey;
            }
        }
        $policyRootGroupKeys = [];
        foreach ($policyGroupNodes as $nodeKey => $node) {
            if ($node['parent'] === null || !isset($policyGroupNodes[$node['parent']])) {
                $policyRootGroupKeys[] = $nodeKey;
            }
        }
        $policySortGroupKeys = static function (array $keys) use (&$policyGroupNodes) {
            usort($keys, static function ($left, $right) use (&$policyGroupNodes) {
                return strnatcasecmp($policyGroupNodes[$left]['label'], $policyGroupNodes[$right]['label'])
                    ?: strnatcmp($left, $right);
            });
            return $keys;
        };
        // Rules precede child holons, sharing one sequence to avoid duplicate references.
        $policyRuleNumbers = [];
        $numberGroups = null;
        $numberGroups = static function (array $keys, string $prefix = '', bool $showRootTitles = true, int $offset = 0) use (&$numberGroups, &$policyGroupNodes, &$policyRuleNumbers, $policySortGroupKeys, $policyGroup): array {
            $visibleKeys = [];
            foreach ($policySortGroupKeys($keys) as $index => $nodeKey) {
                $showTitle = $showRootTitles || $prefix !== '';
                $number = $prefix === '' ? (string)($offset + $index + 1) : $prefix . '.' . ($offset + $index + 1);
                $policyGroupNodes[$nodeKey]['number'] = $number;
                $policyGroupNodes[$nodeKey]['showTitle'] = $showTitle;
                $nextPrefix = $showTitle ? $number : '';
                $childOffset = 0;
                if ($policyGroup === 'holon') {
                    $allRules = $policyGroupNodes[$nodeKey]['allRules'];
                    usort($allRules, static function (array $left, array $right): int {
                        return strnatcasecmp((string)$left['rule']->get('title'), (string)$right['rule']->get('title'))
                            ?: ((int)$left['rule']->getId() <=> (int)$right['rule']->getId());
                    });
                    foreach ($allRules as $ruleIndex => $entry) {
                        $policyRuleNumbers[(int)$entry['rule']->getId()] = $nextPrefix === ''
                            ? (string)($ruleIndex + 1) : $nextPrefix . '.' . ($ruleIndex + 1);
                    }
                    $childOffset = count($allRules);
                }
                $policyGroupNodes[$nodeKey]['children'] = $numberGroups(
                    $policyGroupNodes[$nodeKey]['children'], $nextPrefix, true, $childOffset
                );
                if ($policyGroupNodes[$nodeKey]['rules'] !== [] || $policyGroupNodes[$nodeKey]['children'] !== []) {
                    $visibleKeys[] = $nodeKey;
                }
            }
            return $visibleKeys;
        };
        $policyRootGroupKeys = $numberGroups($policyRootGroupKeys, '', count($policyRootGroupKeys) > 1);
        if ($policyGroup === 'holon') {
            foreach ($policyGroupNodes as &$node) {
                foreach ($node['rules'] as &$entry) {
                    $entry['number'] = $policyRuleNumbers[(int)$entry['rule']->getId()];
                }
                unset($entry);
            }
            unset($node);
        }
        return ['nodes' => $policyGroupNodes, 'roots' => $policyRootGroupKeys, 'ruleNumbers' => $policyRuleNumbers];
    }
}
