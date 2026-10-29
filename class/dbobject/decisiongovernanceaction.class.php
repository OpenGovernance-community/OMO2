<?php
namespace dbObject;

class DecisionGovernanceAction extends DbObject
{
        protected static function uiMessageSourceLang(): array
        {
            return [
                'la_regle_doit_etre_definie_dans_le_contexte_de_la_decision' => ['text' => 'La règle doit être définie dans le contexte de la décision.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'valeur_introuvable_dans_cette_organisation' => ['text' => 'Valeur introuvable dans cette organisation.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_domaine_d_autorite_choisi_n_appartient_pas_au_contexte_de_la_decision' => ['text' => 'Le domaine d’autorité choisi n’appartient pas au contexte de la décision.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_titre_la_regle_et_les_deux_dates_sont_obligatoires' => ['text' => 'Le titre, la règle et les deux dates sont obligatoires.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'la_date_de_requestionnement_ne_peut_pas_suivre_la_date_d_echeance' => ['text' => 'La date de requestionnement ne peut pas suivre la date d’échéance.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'espace_introuvable' => ['text' => 'Espace introuvable.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_nom_du_role_est_obligatoire' => ['text' => 'Le nom du rôle est obligatoire.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_nom_du_role_est_trop_long' => ['text' => 'Le nom du rôle est trop long.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_modele_de_l_espace_est_obligatoire' => ['text' => 'Le modèle de l’espace est obligatoire.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_modele_d_espace_choisi_est_invalide' => ['text' => 'Le modèle d’espace choisi est invalide.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'consultez_le_detail_ci_dessous_pour_lire_le_texte_complet_de_la_regle' => ['text' => 'Consultez le détail ci-dessous pour lire le texte complet de la règle.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'connexion_a_la_base_impossible' => ['text' => 'Connexion à la base impossible.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'type_de_modification_invalide' => ['text' => 'Type de modification invalide.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'ce_type_d_action_n_est_pas_encore_executable' => ['text' => 'Ce type d’action n’est pas encore exécutable.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'la_creation_de_la_regle_est_dans_un_etat_incoherent' => ['text' => 'La création de la règle est dans un état incohérent.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'la_regle_cible_n_existe_plus' => ['text' => 'La règle cible n’existe plus.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'la_regle_cible_a_change_de_contexte' => ['text' => 'La règle cible a changé de contexte.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'la_regle_a_ete_modifiee_depuis_la_preparation_de_cette_modification' => ['text' => 'La règle a été modifiée depuis la préparation de cette modification.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'la_regle_ne_peut_pas_etre_supprimee' => ['text' => 'La règle ne peut pas être supprimée.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_contexte_de_la_modification_n_existe_plus' => ['text' => 'Le contexte de la modification n’existe plus.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'l_organisation_de_la_modification_est_introuvable' => ['text' => 'L’organisation de la modification est introuvable.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'la_creation_de_l_espace_est_dans_un_etat_incoherent' => ['text' => 'La création de l’espace est dans un état incohérent.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'l_espace_ne_peut_pas_etre_cree' => ['text' => 'L’espace ne peut pas être créé.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_role_ne_peut_pas_etre_cree' => ['text' => 'Le rôle ne peut pas être créé.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'l_espace_cible_n_appartient_plus_a_cette_organisation' => ['text' => 'L’espace cible n’appartient plus à cette organisation.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_role_cible_n_appartient_plus_a_ce_cercle' => ['text' => 'Le rôle cible n’appartient plus à ce cercle.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'l_espace_a_ete_modifie_depuis_la_preparation_de_cette_modification' => ['text' => 'L’espace a été modifié depuis la préparation de cette modification.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
                'le_parent_de_l_espace_cible_est_introuvable' => ['text' => 'Le parent de l’espace cible est introuvable.', 'context' => 'Validation or action feedback for decisiongovernanceaction.'],
            ];
        }

    protected $ruleAuditUserId = 0;
    protected $ruleCreationContext = [];
    public const TYPE_RULE_CREATE = 'rule.create';
    public const TYPE_RULE_UPDATE = 'rule.update';
    public const TYPE_RULE_DELETE = 'rule.delete';
    public const TYPE_HOLON_CREATE = 'holon.create';
    public const TYPE_HOLON_UPDATE = 'holon.update';
    public const TYPE_HOLON_DELETE = 'holon.delete';

    public const TARGET_RULE = 'rule';
    public const TARGET_HOLON = 'holon';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REMOVED = 'removed';
    public const STATUS_CONFLICT = 'conflict';
    public const STATUS_FAILED = 'failed';

    // Limit duplicated rule text in generated summaries; action snapshots stay complete.
    private const RULE_SUMMARY_CONTENT_MAX_LENGTH = 600;

    public static function tableName()
    {
        return 'decision_governance_action';
    }

    public static function rules()
    {
        return [
            [['IDdecision_proposal', 'action_type', 'target_type', 'status'], 'required'],
            [['id', 'target_id', 'position'], 'integer'],
            [['IDdecision_proposal'], 'fk'],
            [['action_type', 'target_type', 'status'], 'string'],
            [['before_state', 'after_state', 'parameters'], 'parameters'],
            [['status_message'], 'text'],
            [['applied_at', 'created_at', 'updated_at'], 'datetime'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'IDdecision_proposal' => 'Proposition',
            'action_type' => 'Type d’action',
            'target_type' => 'Type de cible',
            'target_id' => 'Cible',
            'before_state' => 'État avant',
            'after_state' => 'État après',
            'parameters' => 'Paramètres',
            'position' => 'Ordre',
            'status' => 'Statut',
            'status_message' => 'Message de statut',
            'applied_at' => 'Application',
            'created_at' => 'Création',
            'updated_at' => 'Mise à jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'before_state' => 'Instantané utilisé pour détecter un conflit avant application.',
            'after_state' => 'État à appliquer si la proposition est acceptée.',
            'parameters' => 'Métadonnées versionnées propres au type d’action.',
        ];
    }

    public static function attributeLength()
    {
        return [
            'action_type' => 60,
            'target_type' => 40,
            'status' => 30,
        ];
    }

    public static function getOrder()
    {
        return 'position ASC, id ASC';
    }

    public static function getTypeRegistry()
    {
        return [
            self::TYPE_RULE_CREATE => ['target_type' => self::TARGET_RULE, 'implemented' => true],
            self::TYPE_RULE_UPDATE => ['target_type' => self::TARGET_RULE, 'implemented' => true],
            self::TYPE_RULE_DELETE => ['target_type' => self::TARGET_RULE, 'implemented' => true],
            self::TYPE_HOLON_CREATE => ['target_type' => self::TARGET_HOLON, 'implemented' => true],
            self::TYPE_HOLON_UPDATE => ['target_type' => self::TARGET_HOLON, 'implemented' => true],
            self::TYPE_HOLON_DELETE => ['target_type' => self::TARGET_HOLON, 'implemented' => true],
        ];
    }

    public static function isImplementedType($actionType)
    {
        $definition = self::getTypeRegistry()[trim((string)$actionType)] ?? null;
        return is_array($definition) && !empty($definition['implemented']);
    }

    public static function normalizeState($value)
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode(trim((string)$value), true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function getForProposal($proposalId, $activeOnly = false)
    {
        $items = new ArrayDecisionGovernanceAction();
        $where = [
            ['field' => 'IDdecision_proposal', 'value' => (int)$proposalId],
        ];
        if ($activeOnly) {
            $where[] = ['field' => 'status', 'value' => self::STATUS_PENDING];
        }
        $items->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'position', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
        return $items;
    }

    protected static function getPendingForProposalForUpdate($proposalId)
    {
        $rows = self::fetchAll(
            'SELECT * FROM `decision_governance_action`
             WHERE `IDdecision_proposal` = :proposal_id
               AND `status` = :pending_status
             ORDER BY `position` ASC, `id` ASC
             FOR UPDATE',
            [
                'proposal_id' => (int)$proposalId,
                'pending_status' => self::STATUS_PENDING,
            ]
        );
        $actions = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || !isset($row['id'])) {
                continue;
            }
            $action = new self();
            $action->loadFromArray($row);
            $action->setId((int)$row['id']);
            $actions[] = $action;
        }
        return $actions;
    }

    public static function captureRuleState(Rule $rule)
    {
        return [
            'IDauthority' => (int)$rule->get('IDauthority') > 0 ? (int)$rule->get('IDauthority') : null,
            'IDholon' => (int)$rule->get('IDholon') > 0 ? (int)$rule->get('IDholon') : null,
            'title' => trim((string)$rule->get('title')),
            'intention' => Rule::sanitizeContentHtml((string)$rule->get('intention')),
            'IDvaleur' => (int)$rule->get('IDvaleur') ?: null,
            'description' => Rule::sanitizeContentHtml((string)$rule->get('description')),
            'scope' => Rule::normalizeScope($rule->get('scope')),
            'review_date' => self::normalizeDateValue($rule->get('review_date')),
            'expiration_date' => self::normalizeDateValue($rule->get('expiration_date')),
        ];
    }

    public static function normalizeRuleState(array $state, ?Rule $baseRule = null)
    {
        $base = $baseRule instanceof Rule ? self::captureRuleState($baseRule) : [];
        $state = array_merge($base, $state);
        $normalized = [
            'IDauthority' => (int)($state['IDauthority'] ?? 0) > 0 ? (int)$state['IDauthority'] : null,
            'IDholon' => (int)($state['IDholon'] ?? 0) > 0 ? (int)$state['IDholon'] : null,
            'title' => trim((string)($state['title'] ?? '')),
            'intention' => Rule::sanitizeContentHtml((string)($state['intention'] ?? '')),
            'IDvaleur' => (int)($state['IDvaleur'] ?? 0) ?: null,
            'description' => Rule::sanitizeContentHtml((string)($state['description'] ?? '')),
            'scope' => Rule::normalizeScope($state['scope'] ?? Rule::SCOPE_LOCAL),
            'review_date' => self::normalizeDateValue($state['review_date'] ?? ''),
            'expiration_date' => self::normalizeDateValue($state['expiration_date'] ?? ''),
        ];
        if ($normalized['IDauthority']) {
            $normalized['IDholon'] = null;
        }
        return $normalized;
    }

    public static function validateRuleUpdate(Rule $rule, array $afterState, $expectedHolonId)
    {
        $ruleHolon = $rule->getHolon();
        if (!$ruleHolon instanceof Holon || (int)$ruleHolon->getId() !== (int)$expectedHolonId) {
            return ['status' => false, 'message' => self::uiMessage('la_regle_doit_etre_definie_dans_le_contexte_de_la_decision')];
        }
        $state = self::normalizeRuleState($afterState, $rule);
        if ($state['IDvaleur'] && !Valeur::loadInOrganization($state['IDvaleur'], $rule->getOrganizationId())) return ['status' => false, 'message' => self::uiMessage('valeur_introuvable_dans_cette_organisation')];
        $scopeValidation = Rule::validateScopeAttachment($afterState['scope'] ?? $state['scope'], $ruleHolon, $state['IDauthority']);
        if (empty($scopeValidation['status'])) return ['status' => false, 'message' => $scopeValidation['text']];
        if ((int)$state['IDauthority'] > 0) {
            $authority = new Authority();
            if (!$authority->load((int)$state['IDauthority']) || (int)$authority->get('IDholon') !== (int)$expectedHolonId) {
                return ['status' => false, 'message' => self::uiMessage('le_domaine_d_autorite_choisi_n_appartient_pas_au_contexte_de_la_decision')];
            }
        } else {
            $state['IDholon'] = (int)$expectedHolonId;
        }
        if ($state['title'] === '' || !Rule::hasContentText($state['description']) || $state['review_date'] === '' || $state['expiration_date'] === '') {
            return ['status' => false, 'message' => self::uiMessage('le_titre_la_regle_et_les_deux_dates_sont_obligatoires')];
        }
        if ($state['review_date'] > $state['expiration_date']) {
            return ['status' => false, 'message' => self::uiMessage('la_date_de_requestionnement_ne_peut_pas_suivre_la_date_d_echeance')];
        }
        return ['status' => true, 'state' => $state];
    }

    public static function validateRuleCreate(array $afterState, $expectedHolonId)
    {
        $state = self::normalizeRuleState($afterState);
        $holon = new Holon();
        if (!$holon->load((int)$expectedHolonId)) return ['status' => false, 'message' => self::uiMessage('espace_introuvable')];
        $root = new Holon();
        $valueOrganizationId = (int)$holon->get('IDorganization');
        if (!$valueOrganizationId && $root->load((int)$holon->get('IDholon_org'))) $valueOrganizationId = (int)$root->get('IDorganization');
        if ($state['IDvaleur'] && !Valeur::loadInOrganization($state['IDvaleur'], $valueOrganizationId)) return ['status' => false, 'message' => self::uiMessage('valeur_introuvable_dans_cette_organisation')];
        $scopeValidation = Rule::validateScopeAttachment($afterState['scope'] ?? $state['scope'], $holon, $state['IDauthority']);
        if (empty($scopeValidation['status'])) return ['status' => false, 'message' => $scopeValidation['text']];
        if ((int)$state['IDauthority'] > 0) {
            $authority = new Authority();
            if (!$authority->load((int)$state['IDauthority']) || (int)$authority->get('IDholon') !== (int)$expectedHolonId) {
                return ['status' => false, 'message' => self::uiMessage('le_domaine_d_autorite_choisi_n_appartient_pas_au_contexte_de_la_decision')];
            }
            $state['IDholon'] = null;
        } else {
            $state['IDauthority'] = null;
            $state['IDholon'] = (int)$expectedHolonId;
        }
        if ($state['title'] === '' || !Rule::hasContentText($state['description']) || $state['review_date'] === '' || $state['expiration_date'] === '') {
            return ['status' => false, 'message' => self::uiMessage('le_titre_la_regle_et_les_deux_dates_sont_obligatoires')];
        }
        if ($state['review_date'] > $state['expiration_date']) {
            return ['status' => false, 'message' => self::uiMessage('la_date_de_requestionnement_ne_peut_pas_suivre_la_date_d_echeance')];
        }
        return ['status' => true, 'state' => $state];
    }

    public static function validateRuleDelete(Rule $rule, $expectedHolonId)
    {
        $ruleHolon = $rule->getHolon();
        if (!$ruleHolon instanceof Holon || (int)$ruleHolon->getId() !== (int)$expectedHolonId) {
            return ['status' => false, 'message' => self::uiMessage('la_regle_doit_etre_definie_dans_le_contexte_de_la_decision')];
        }
        return ['status' => true, 'state' => self::captureRuleState($rule)];
    }

    public static function captureRoleState(Holon $role)
    {
        return [
            'name' => trim((string)$role->get('name')),
            'full_name' => trim((string)$role->get('nomcomplet')),
            'color' => trim((string)$role->get('color')),
            'template_id' => (int)$role->get('IDholon_template'),
        ];
    }

    /**
     * Deferred proposals use the same structural state for roles and
     * circles. Keep the historical role method for decision compatibility.
     */
    public static function captureHolonState(Holon $holon)
    {
        return self::captureRoleState($holon);
    }

    /**
     * Snapshot the standard structural editor so deferred proposals can show
     * property-level differences in the shared change-detail renderer.
     */
    public static function captureHolonEditorState(Holon $holon, Organization $organization): array
    {
        $state = self::captureHolonState($holon);
        $parentHolon = $holon->getParentHolon();
        if (!$parentHolon instanceof Holon) {
            return $state;
        }
        $editorHolon = $organization->getHolonEditorState(
            (int)$parentHolon->getId(),
            (int)$holon->getId(),
            true
        );
        if (count($editorHolon) === 0) {
            return $state;
        }
        $state['editor_payload'] = [
            'templateId' => (int)($editorHolon['templateId'] ?? 0),
            'name' => (string)($editorHolon['name'] ?? ''),
            'fullName' => (string)($editorHolon['fullName'] ?? ''),
            'color' => (string)($editorHolon['color'] ?? ''),
            'icon' => (string)($editorHolon['icon'] ?? ''),
            'adminMin' => $editorHolon['adminMin'] ?? 0,
            'adminMax' => $editorHolon['adminMax'] ?? null,
            'adminMinOverride' => !empty($editorHolon['adminMinOverride']),
            'adminMaxOverride' => !empty($editorHolon['adminMaxOverride']),
            'permissions' => is_array($editorHolon['permissionAssignments'] ?? null) ? $editorHolon['permissionAssignments'] : [],
            'properties' => is_array($editorHolon['properties'] ?? null) ? array_values($editorHolon['properties']) : [],
        ];
        return DeferredProposal::decorateHolonListDisplayState($state, $organization);
    }

    public static function findRolesInGovernanceContext(Holon $contextHolon)
    {
        $roles = [];
        foreach ($contextHolon->getChildrenThroughGroups() as $child) {
            if ((int)$child->get('IDtypeholon') === 1) {
                $roles[(int)$child->getId()] = $child;
            }
        }
        return array_values($roles);
    }

    public static function roleBelongsToGovernanceContext(Holon $role, Holon $contextHolon)
    {
        return (int)$role->get('IDtypeholon') === 1 && $role->isChildOfThroughGroups($contextHolon);
    }

    public static function normalizeRoleState(array $state, ?Holon $baseRole = null)
    {
        $editorPayload = is_array($state['editor_payload'] ?? null) ? $state['editor_payload'] : null;
        if ($editorPayload !== null) {
            $state = array_merge($state, [
                'name' => $editorPayload['name'] ?? '',
                'full_name' => $editorPayload['fullName'] ?? '',
                'color' => $editorPayload['color'] ?? '',
                'template_id' => $editorPayload['templateId'] ?? 0,
            ]);
        }
        $base = $baseRole instanceof Holon ? self::captureRoleState($baseRole) : [];
        $state = array_merge($base, $state);
        $normalized = [
            'name' => trim((string)($state['name'] ?? '')),
            'full_name' => trim((string)($state['full_name'] ?? '')),
            'color' => trim((string)($state['color'] ?? '')),
            'template_id' => max(0, (int)($state['template_id'] ?? 0)),
        ];
        if ($editorPayload !== null) {
            $normalized['editor_payload'] = $editorPayload;
        }
        return $normalized;
    }

    public static function validateRoleState(array $state, Holon $contextHolon, ?Holon $role = null)
    {
        $state = self::normalizeRoleState($state, $role);
        if ($state['name'] === '') {
            return ['status' => false, 'message' => self::uiMessage('le_nom_du_role_est_obligatoire')];
        }
        if (mb_strlen($state['name'], 'UTF-8') > 255 || mb_strlen($state['full_name'], 'UTF-8') > 255) {
            return ['status' => false, 'message' => self::uiMessage('le_nom_du_role_est_trop_long')];
        }
        if ($role === null && $state['template_id'] <= 0) {
            return ['status' => false, 'message' => self::uiMessage('le_modele_de_l_espace_est_obligatoire')];
        }
        if ($state['template_id'] > 0) {
            $template = new Holon();
            if (!$template->load($state['template_id']) || !in_array((int)$template->get('IDtypeholon'), [1, 2, 3], true)) {
                return ['status' => false, 'message' => self::uiMessage('le_modele_d_espace_choisi_est_invalide')];
            }
        }
        return ['status' => true, 'state' => $state];
    }

    public static function normalizeHolonState(array $state, ?Holon $holon = null)
    {
        return self::normalizeRoleState($state, $holon);
    }

    public static function validateHolonState(array $state, Holon $contextHolon, ?Holon $holon = null)
    {
        return self::validateRoleState($state, $contextHolon, $holon);
    }

    public static function buildRoleStateDescription(array $state)
    {
        $items = [];
        foreach (['name' => 'Nom', 'full_name' => 'Nom complet', 'color' => 'Couleur'] as $field => $label) {
            $value = trim((string)($state[$field] ?? ''));
            if ($value !== '') {
                $items[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> : ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }
        if (is_array($state['editor_payload']['properties'] ?? null)) {
            $items[] = '<li><strong>Propriétés</strong> : ' . count($state['editor_payload']['properties']) . ' propriété(s) configurée(s)</li>';
        }
        return count($items) ? '<ul>' . implode('', $items) . '</ul>' : '';
    }

    public static function buildRoleUpdateDescription(array $beforeState, array $afterState)
    {
        $changes = [];
        foreach (['name' => 'Nom', 'full_name' => 'Nom complet', 'color' => 'Couleur'] as $field => $label) {
            $before = trim((string)($beforeState[$field] ?? ''));
            $after = trim((string)($afterState[$field] ?? ''));
            if ($before !== $after) {
                $changes[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> : ' . self::formatDescriptionValue($before) . ' &rarr; ' . self::formatDescriptionValue($after) . '</li>';
            }
        }
        $beforeProperties = self::indexRoleProperties(self::decorateRoleProperties($beforeState['editor_payload']['properties'] ?? []));
        $afterProperties = self::indexRoleProperties(self::decorateRoleProperties($afterState['editor_payload']['properties'] ?? []));
        foreach ($afterProperties + $beforeProperties as $key => $property) {
            $beforeValue = self::formatRolePropertyValue($beforeProperties[$key] ?? []);
            $afterValue = self::formatRolePropertyValue($afterProperties[$key] ?? []);
            if ($beforeValue === $afterValue) continue;
            $label = trim((string)($property['name'] ?? $property['shortname'] ?? 'Propriété')) ?: 'Propriété';
            $changes[] = '<li><strong>' . htmlspecialchars('Propriété - ' . $label, ENT_QUOTES, 'UTF-8') . '</strong> : '
                . self::formatDescriptionValue($beforeValue) . ' &rarr; '
                . self::formatDescriptionValue($afterValue) . '</li>';
        }
        return count($changes) ? '<p>Modifications proposées :</p><ul>' . implode('', $changes) . '</ul>' : '<p>Aucune différence de contenu.</p>';
    }

    protected static function indexRoleProperties($properties)
    {
        $indexed = [];
        foreach (is_array($properties) ? array_values($properties) : [] as $index => $property) {
            if (!is_array($property)) continue;
            $id = (int)($property['id'] ?? 0);
            $key = $id > 0 ? 'id:' . $id : 'name:' . mb_strtolower(trim((string)($property['name'] ?? $property['shortname'] ?? $index)), 'UTF-8');
            $indexed[$key] = $property;
        }
        return $indexed;
    }

    protected static function formatDescriptionValue(string $value): string
    {
        return trim($value) === '' ? '<em>vide</em>' : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    protected static function formatRolePropertyValue(array $property)
    {
        $value = $property['value'] ?? '';
        if ((string)($property['listItemType'] ?? '') === 'authority') {
            $value = $property['displayValue'] ?? '';
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $value = trim(str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        return mb_strlen($value, 'UTF-8') > 240 ? mb_substr($value, 0, 237, 'UTF-8') . '...' : $value;
    }

    public static function decorateRoleProperties(array $properties): array
    {
        foreach ($properties as &$property) {
            if (!is_array($property) || (string)($property['listItemType'] ?? '') !== 'authority') continue;
            $value = $property['value'] ?? '';
            $decoded = is_array($value) ? $value : json_decode((string)$value, true);
            $items = is_array($decoded['items'] ?? null) ? $decoded['items'] : (is_array($decoded) ? $decoded : []);
            $ids = [];
            foreach ($items as $item) {
                if (is_array($item) && !empty($item['delete'])) continue;
                $ids[] = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
                if (is_array($item) && ($item['delegationMode'] ?? '') === 'complete') $ids[] = (int)($item['parentId'] ?? 0);
            }
            $labels = Authority::getLabelsByIds($ids);
            foreach ($property['displayItems'] ?? [] as $snapshot) {
                if (is_array($snapshot) && (int)($snapshot['id'] ?? 0) > 0) {
                    $labels[(int)$snapshot['id']] ??= (string)($snapshot['label'] ?? '');
                }
            }
            $display = [];
            foreach ($items as $item) {
                if (is_array($item) && !empty($item['delete'])) continue;
                $id = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
                $label = is_array($item) ? trim((string)($item['label'] ?? '')) : '';
                if ($id <= 0 && is_array($item) && ($item['delegationMode'] ?? '') === 'complete') $id = (int)($item['parentId'] ?? 0);
                if ($label === '') $label = trim((string)($labels[$id] ?? ''));
                if ($label !== '') $display[] = $label;
            }
            $property['displayValue'] = implode('; ', $display);
        }
        unset($property);
        return array_values($properties);
    }

    protected static function buildRuleSummaryContentNotice(): string
    {
        static $sourceLang = [
            'rule_content_details' => [
                'text' => self::uiMessage('consultez_le_detail_ci_dessous_pour_lire_le_texte_complet_de_la_regle'),
                'context' => 'Generated proposal summary replacing a long rule body; the complete content and changes are available in the modification detail below.',
            ],
        ];
        static $bundle = null;
        $bundle ??= function_exists('omoLoadTranslationBundle')
            ? \omoLoadTranslationBundle('omo_decision_rule_summary', $sourceLang)
            : [];
        $text = function_exists('t')
            ? \t('rule_content_details', [], $bundle, $sourceLang)
            : $sourceLang['rule_content_details']['text'];
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function buildRuleUpdateDescription(array $beforeState, array $afterState)
    {
        $labels = [
            'IDauthority' => 'Domaine d’autorité',
            'IDvaleur' => 'Valeur',
            'scope' => 'Portée',
            'title' => 'Titre',
            'intention' => 'Intention',
            'description' => 'Règle',
            'review_date' => 'Date de requestionnement',
            'expiration_date' => 'Date d’échéance',
        ];
        $changes = [];
        foreach ($labels as $field => $label) {
            $before = in_array($field, ['description', 'intention'], true)
                ? DecisionProposal::descriptionToPlainText((string)($beforeState[$field] ?? ''))
                : trim(strip_tags((string)($beforeState[$field] ?? '')));
            $after = in_array($field, ['description', 'intention'], true)
                ? DecisionProposal::descriptionToPlainText((string)($afterState[$field] ?? ''))
                : trim(strip_tags((string)($afterState[$field] ?? '')));
            if ($field === 'scope') {
                $scopeLabels = array_column(Rule::attributeValues()['scope'], 1, 0);
                $before = $scopeLabels[Rule::normalizeScope($before)] ?? $before;
                $after = $scopeLabels[Rule::normalizeScope($after)] ?? $after;
            }
            if ($field === 'IDvaleur' && $before !== $after) {
                $value = new Valeur();
                if ((int)$before > 0 && $value->load((int)$before)) $before = (string)$value->get('title');
                $value = new Valeur();
                if ((int)$after > 0 && $value->load((int)$after)) $after = (string)$value->get('title');
            }
            if ($field === 'IDauthority' && $before !== $after) {
                $labelsById = Authority::getLabelsByIds(array_filter([(int)$before, (int)$after]));
                $before = (int)$before > 0 ? (string)($labelsById[(int)$before] ?? $before) : '';
                $after = (int)$after > 0 ? (string)($labelsById[(int)$after] ?? $after) : '';
            }
            if ($before === $after) {
                continue;
            }
            $valueHtml = $field === 'description'
                && mb_strlen($before, 'UTF-8') + mb_strlen($after, 'UTF-8') > self::RULE_SUMMARY_CONTENT_MAX_LENGTH
                ? self::buildRuleSummaryContentNotice()
                : nl2br(self::formatDescriptionValue($before)) . ' &rarr; ' . nl2br(self::formatDescriptionValue($after));
            $changes[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> : '
                . $valueHtml . '</li>';
        }
        return count($changes) > 0
            ? '<p>Modifications proposées :</p><ul>' . implode('', $changes) . '</ul>'
            : '<p>Aucune différence de contenu.</p>';
    }

    public static function buildRuleStateDescription(array $state)
    {
        $items = [];
        foreach ([
            'IDvaleur' => 'Valeur',
            'scope' => 'Portée',
            'title' => 'Titre',
            'intention' => 'Intention',
            'description' => 'Règle',
            'review_date' => 'Date de requestionnement',
            'expiration_date' => 'Date d’échéance',
        ] as $field => $label) {
            $value = in_array($field, ['description', 'intention'], true)
                ? DecisionProposal::descriptionToPlainText((string)($state[$field] ?? ''))
                : trim(strip_tags((string)($state[$field] ?? '')));
            if ($field === 'IDvaleur') {
                $linkedValue = new Valeur();
                $value = (int)$value > 0 && $linkedValue->load((int)$value) ? (string)$linkedValue->get('title') : '';
            }
            if ($field === 'scope') $value = array_column(Rule::attributeValues()['scope'], 1, 0)[Rule::normalizeScope($value)];
            if ($value === '') {
                continue;
            }
            $valueHtml = $field === 'description' && mb_strlen($value, 'UTF-8') > self::RULE_SUMMARY_CONTENT_MAX_LENGTH
                ? self::buildRuleSummaryContentNotice()
                : nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            $items[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> : '
                . $valueHtml . '</li>';
        }
        return count($items) > 0 ? '<ul>' . implode('', $items) . '</ul>' : '';
    }

    public static function proposalHasObjection(DecisionProposal $proposal, array $responses)
    {
        $proposalId = (int)$proposal->getId();
        foreach ($responses as $response) {
            if (!$response instanceof DecisionResponse) {
                continue;
            }
            $parameters = self::normalizeState($response->get('parameters'));
            $methodParameters = is_array($parameters[DecisionProcess::METHOD_CONSENT] ?? null)
                ? $parameters[DecisionProcess::METHOD_CONSENT]
                : $parameters;
            $choices = is_array($methodParameters['choices'] ?? null) ? $methodParameters['choices'] : [];
            if (trim((string)($choices[$proposalId] ?? '')) === 'objection') {
                return true;
            }
        }
        return false;
    }

    public static function getSimpleVoteWinningProposalIds(array $responses): array
    {
        $counts = [];
        foreach ($responses as $response) {
            if (!$response instanceof DecisionResponse) {
                continue;
            }
            $parameters = self::normalizeState($response->get('parameters'));
            $methodParameters = is_array($parameters[DecisionProcess::METHOD_SIMPLE_VOTE] ?? null)
                ? $parameters[DecisionProcess::METHOD_SIMPLE_VOTE]
                : $parameters;
            $proposalIds = is_array($methodParameters['selected_proposal_ids'] ?? null)
                ? $methodParameters['selected_proposal_ids']
                : [$methodParameters['selected_proposal_id'] ?? 0];
            $proposalId = (int)reset($proposalIds);
            if ($proposalId > 0) {
                $counts[$proposalId] = (int)($counts[$proposalId] ?? 0) + 1;
            }
        }
        if (count($counts) === 0) {
            return [];
        }
        $highestCount = max($counts);
        $winners = array_keys(array_filter($counts, static function ($count) use ($highestCount) {
            return (int)$count === (int)$highestCount;
        }));
        return count($winners) === 1 ? [(int)$winners[0]] : [];
    }

    public static function applyAcceptedForDecision(DecisionProcess $decision)
    {
        if (!$decision->isGovernanceWorkflow()
            || DecisionProcess::getStatusRank($decision->get('status')) < DecisionProcess::getStatusRank(DecisionProcess::STATUS_RESULTS)) {
            return ['status' => true, 'applied' => 0, 'conflicts' => 0, 'failed' => 0];
        }

        $summary = ['status' => true, 'applied' => 0, 'conflicts' => 0, 'failed' => 0];
        foreach ($decision->getDecisionGroups(true) as $group) {
            if (!$group instanceof DecisionGroup) {
                continue;
            }
            $method = DecisionProcess::normalizeEvaluationMethod($group->get('evaluation_method'));
            if (!in_array($method, [DecisionProcess::METHOD_CONSENT, DecisionProcess::METHOD_SIMPLE_VOTE], true)) {
                continue;
            }
            $responses = [];
            foreach ($group->getResponses(DecisionResponse::STATUS_SUBMITTED) as $response) {
                $responses[] = $response;
            }
            $winningProposalIds = $method === DecisionProcess::METHOD_SIMPLE_VOTE
                ? array_fill_keys(self::getSimpleVoteWinningProposalIds($responses), true)
                : [];
            foreach ($group->getProposals(true) as $proposal) {
                if (!$proposal instanceof DecisionProposal || !$proposal->hasGovernanceActions()) {
                    continue;
                }
                if ($method === DecisionProcess::METHOD_CONSENT && self::proposalHasObjection($proposal, $responses)) {
                    self::setProposalActionStatus($proposal, self::STATUS_REJECTED, 'Proposition non acceptée.');
                    continue;
                }
                if ($method === DecisionProcess::METHOD_SIMPLE_VOTE && !isset($winningProposalIds[(int)$proposal->getId()])) {
                    self::setProposalActionStatus($proposal, self::STATUS_REJECTED, 'Proposition non retenue par le vote.');
                    continue;
                }
                $result = self::applyProposal($proposal, $decision);
                $summary['applied'] += (int)($result['applied'] ?? 0);
                $summary['conflicts'] += !empty($result['conflict']) ? 1 : 0;
                $summary['failed'] += !empty($result['failed']) ? 1 : 0;
            }
        }
        return $summary;
    }

    public static function applyProposal(DecisionProposal $proposal, DecisionProcess $decision)
    {
        $pdo = self::getPdo();
        if (!$pdo) {
            return ['status' => false, 'failed' => true, 'message' => self::uiMessage('connexion_a_la_base_impossible')];
        }

        try {
            $pdo->beginTransaction();
            $actions = self::getPendingForProposalForUpdate((int)$proposal->getId());
            if (count($actions) === 0) {
                $pdo->commit();
                return ['status' => true, 'applied' => 0];
            }
            foreach ($actions as $action) {
                $action->ruleAuditUserId = (int)$proposal->get('IDuser_author');
                $action->ruleCreationContext = Rule::buildDecisionCreationContext($decision);
                $result = $action->applyOne($decision);
                if (empty($result['status'])) {
                    throw new \RuntimeException((!empty($result['conflict']) ? 'conflict:' : 'failure:') . (string)($result['message'] ?? 'Application impossible.'));
                }
                $action->set('status', self::STATUS_APPLIED);
                $action->set('status_message', null);
                $action->set('applied_at', new \DateTimeImmutable('now'));
                $action->set('updated_at', new \DateTimeImmutable('now'));
                $saved = $action->save();
                if (!is_array($saved) || empty($saved['status'])) {
                    throw new \RuntimeException('failure:Impossible d’enregistrer le statut de l’action.');
                }
            }
            $pdo->commit();
            return ['status' => true, 'applied' => count($actions)];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = trim((string)$exception->getMessage());
            $isConflict = str_starts_with($message, 'conflict:');
            $message = preg_replace('/^(conflict|failure):/', '', $message) ?: 'Application impossible.';
            self::setProposalActionStatus(
                $proposal,
                $isConflict ? self::STATUS_CONFLICT : self::STATUS_FAILED,
                $message,
                self::STATUS_PENDING
            );
            return [
                'status' => false,
                'conflict' => $isConflict,
                'failed' => !$isConflict,
                'message' => $message,
            ];
        }
    }

    public static function setProposalActionStatus(DecisionProposal $proposal, $status, $message, $onlyCurrentStatus = '')
    {
        foreach (self::getForProposal((int)$proposal->getId()) as $action) {
            if (!$action instanceof self) {
                continue;
            }
            if ($onlyCurrentStatus !== '' && (string)$action->get('status') !== (string)$onlyCurrentStatus) {
                continue;
            }
            if (in_array((string)$action->get('status'), [self::STATUS_APPLIED, self::STATUS_REJECTED], true)) {
                continue;
            }
            $action->set('status', (string)$status);
            $action->set('status_message', trim((string)$message) !== '' ? trim((string)$message) : null);
            $action->set('updated_at', new \DateTimeImmutable('now'));
            $action->save();
        }
    }

    /**
     * Compatibility executor used by the generic DeferredProposal store.
     * Keeping the rule and role application code here avoids two divergent
     * implementations during the migration away from decision-only actions.
     */
    public static function applyDeferredProposal(DeferredProposal $proposal, int $contextHolonId, int $collectiveHolonId = 0): array
    {
        $targetType = trim((string)$proposal->get('target_type'));
        $operation = trim((string)$proposal->get('operation'));
        $actionType = $targetType . '.' . $operation;
        if (!self::isImplementedType($actionType)) {
            return ['status' => false, 'message' => self::uiMessage('type_de_modification_invalide')];
        }
        $action = new self();
        $action->ruleAuditUserId = (int)$proposal->get('IDuser_author');
        $action->ruleCreationContext = Rule::buildProposalCreationContext($proposal);
        $action->set('action_type', $actionType);
        $action->set('target_type', $targetType);
        $action->set('target_id', (int)$proposal->get('target_id'));
        $action->set('before_state', DeferredProposal::normalizeState($proposal->get('before_state')));
        $action->set('after_state', DeferredProposal::normalizeState($proposal->get('after_state')));
        $decision = new DecisionProcess();
        $decision->set('IDorganization', (int)$proposal->get('IDorganization'));
        $decision->set('IDholon', $contextHolonId > 0 ? $contextHolonId : (int)$proposal->get('IDholon'));
        $result = $action->applyOne($decision, $collectiveHolonId > 0 ? $collectiveHolonId : -1);
        if (!empty($result['created_id'])) {
            $result['target_id'] = (int)$result['created_id'];
        }
        return $result;
    }

    protected function applyOne(DecisionProcess $decision, ?int $collectiveHolonId = null)
    {
        $actionType = trim((string)$this->get('action_type'));
        if (in_array($actionType, [self::TYPE_HOLON_CREATE, self::TYPE_HOLON_UPDATE, self::TYPE_HOLON_DELETE], true)) {
            return $this->applyHolonAction($decision, $actionType, $collectiveHolonId ?? (int)$decision->get('IDholon'));
        }
        if (!in_array($actionType, [self::TYPE_RULE_CREATE, self::TYPE_RULE_UPDATE, self::TYPE_RULE_DELETE], true)) {
            return ['status' => false, 'message' => self::uiMessage('ce_type_d_action_n_est_pas_encore_executable')];
        }

        if ($actionType === self::TYPE_RULE_CREATE) {
            $after = self::normalizeRuleState(self::normalizeState($this->get('after_state')));
            $validation = self::validateRuleCreate($after, (int)$decision->get('IDholon'));
            if (empty($validation['status'])) {
                return $validation;
            }
            $existingTargetId = (int)$this->get('target_id');
            if ($existingTargetId > 0) {
                $existingRule = Rule::loadForGovernanceApplication($existingTargetId);
                if ($existingRule instanceof Rule && self::captureRuleState($existingRule) === (array)$validation['state']) {
                    return ['status' => true, 'already_applied' => true];
                }
                return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('la_creation_de_la_regle_est_dans_un_etat_incoherent')];
            }
            $rule = new Rule();
            $rule->set('creation_context', $this->ruleCreationContext ?: null);
            $saveResult = $rule->applyGovernanceState((array)$validation['state'], $this->ruleAuditUserId);
            if (!is_array($saveResult) || empty($saveResult['status']) || (int)$rule->getId() <= 0) {
                return [
                    'status' => false,
                    'message' => trim((string)($saveResult['text'] ?? 'La règle ne peut pas être créée.')),
                ];
            }
            $this->set('target_id', (int)$rule->getId());
            return ['status' => true, 'created_id' => (int)$rule->getId()];
        }

        $rule = Rule::loadForGovernanceApplication((int)$this->get('target_id'));
        if (!$rule instanceof Rule) {
            return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('la_regle_cible_n_existe_plus')];
        }
        $ruleHolon = $rule->getHolon();
        if (!$ruleHolon instanceof Holon || (int)$ruleHolon->getId() !== (int)$decision->get('IDholon')) {
            return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('la_regle_cible_a_change_de_contexte')];
        }

        $before = self::normalizeRuleState(self::normalizeState($this->get('before_state')));
        $current = self::captureRuleState($rule);
        if ($actionType === self::TYPE_RULE_DELETE) {
            if ($current !== $before) {
                return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('la_regle_a_ete_modifiee_depuis_la_preparation_de_cette_modification')];
            }
            if (!$rule->delete()) {
                return ['status' => false, 'message' => self::uiMessage('la_regle_ne_peut_pas_etre_supprimee')];
            }
            return ['status' => true, 'deleted_id' => (int)$rule->getId()];
        }

        $after = self::normalizeRuleState(self::normalizeState($this->get('after_state')));
        if ($current === $after) {
            return ['status' => true, 'already_applied' => true];
        }
        if ($current !== $before) {
            return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('la_regle_a_ete_modifiee_depuis_la_preparation_de_cette_modification')];
        }

        $validation = self::validateRuleUpdate($rule, $after, (int)$decision->get('IDholon'));
        if (empty($validation['status'])) {
            return $validation;
        }
        return $rule->applyGovernanceState((array)$validation['state'], $this->ruleAuditUserId);
    }

    protected function applyHolonAction(DecisionProcess $decision, $actionType, int $collectiveHolonId)
    {
        $context = new Holon();
        if (!$context->load((int)$decision->get('IDholon'))) {
            return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('le_contexte_de_la_modification_n_existe_plus')];
        }
        $organization = new Organization();
        if (!$organization->load((int)$decision->get('IDorganization'))) {
            return ['status' => false, 'message' => self::uiMessage('l_organisation_de_la_modification_est_introuvable')];
        }
        if ($actionType === self::TYPE_HOLON_CREATE) {
            $validation = self::validateHolonState(self::normalizeState($this->get('after_state')), $context);
            if (empty($validation['status'])) return $validation;
            $existingTargetId = (int)$this->get('target_id');
            if ($existingTargetId > 0) {
                $existingHolon = new Holon();
                if ($existingHolon->load($existingTargetId)
                    && (int)$existingHolon->get('IDholon_parent') === (int)$context->getId()
                    && self::captureHolonState($existingHolon) === array_diff_key((array)$validation['state'], ['editor_payload' => true])) {
                    return ['status' => true, 'already_applied' => true];
                }
                return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('la_creation_de_l_espace_est_dans_un_etat_incoherent')];
            }
            $holon = new Holon();
            $state = $validation['state'];
            if (is_array($state['editor_payload'] ?? null)) {
                $result = $organization->saveHolonEditorDefinition($state['editor_payload'], 0, (int)$context->getId(), 0, true, $collectiveHolonId);
                if (empty($result['status'])) return $result;
                $createdId = (int)($result['holon']['id'] ?? 0);
                if ($createdId <= 0) return ['status' => false, 'message' => self::uiMessage('l_espace_ne_peut_pas_etre_cree')];
                $this->set('target_id', $createdId);
                return ['status' => true, 'created_id' => $createdId];
            }
            // Older hors-reorg decisions only store the compact role state.
            // Keep that format executable while new deferred proposals always
            // carry the full editor payload.
            $holon->set('name', $state['name']);
            $holon->set('nomcomplet', $state['full_name'] !== '' ? $state['full_name'] : null);
            $holon->set('color', $state['color'] !== '' ? $state['color'] : null);
            $holon->set('IDtypeholon', 1);
            $holon->set('IDholon_parent', (int)$context->getId());
            $holon->set('IDholon_template', $state['template_id']);
            $holon->set('IDholon_org', (int)$context->get('IDholon_org'));
            $holon->set('IDuser', (int)$context->get('IDuser'));
            $holon->set('active', true);
            $holon->set('visible', true);
            $holon->save();
            if ((int)$holon->getId() <= 0) return ['status' => false, 'message' => self::uiMessage('le_role_ne_peut_pas_etre_cree')];
            $this->set('target_id', (int)$holon->getId());
            return ['status' => true, 'created_id' => (int)$holon->getId()];
        }
        $holon = new Holon();
        $rootHolon = $organization->getStructuralRootHolon();
        if (!$rootHolon instanceof Holon
            || !$holon->load((int)$this->get('target_id'))
            || !$holon->isDescendantOf($rootHolon, true)
            || $holon->isTemplateNode((int)$rootHolon->getId())
            || !in_array((int)$holon->get('IDtypeholon'), [1, 2, 3], true)) {
            return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('l_espace_cible_n_appartient_plus_a_cette_organisation')];
        }
        // A persisted hors-reorg decision retains its historic role-only
        // boundary. DeferredProposal builds an unsaved decision adapter and
        // deliberately permits any structural holon selected by the PV.
        if ((int)$decision->getId() > 0 && !self::roleBelongsToGovernanceContext($holon, $context)) {
            return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('le_role_cible_n_appartient_plus_a_ce_cercle')];
        }
        $before = self::normalizeRoleState(self::normalizeState($this->get('before_state')));
        $current = self::captureHolonState($holon);
        $beforeComparable = array_diff_key($before, ['editor_payload' => true]);
        if ($actionType === self::TYPE_HOLON_DELETE) {
            if ($current !== $beforeComparable) return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('l_espace_a_ete_modifie_depuis_la_preparation_de_cette_modification')];
            $choice = self::normalizeState($this->get('after_state'))['authority_choice'] ?? '';
            $result = $organization->deleteHolonDefinition((int)$holon->getId(), 0, true, is_string($choice) ? $choice : '');
            return !empty($result['status'])
                ? ['status' => true, 'deleted_id' => (int)$holon->getId()]
                : ['status' => false, 'message' => (string)($result['message'] ?? 'L’espace ne peut pas être supprimé.')];
        }
        $after = self::normalizeHolonState(self::normalizeState($this->get('after_state')), $holon);
        if ($current === $after) return ['status' => true, 'already_applied' => true];
        if ($current !== $beforeComparable) return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('l_espace_a_ete_modifie_depuis_la_preparation_de_cette_modification')];
        $parentHolon = $holon->getParentHolon();
        if (!$parentHolon instanceof Holon) return ['status' => false, 'conflict' => true, 'message' => self::uiMessage('le_parent_de_l_espace_cible_est_introuvable')];
        $validation = self::validateHolonState($after, $parentHolon, $holon);
        if (empty($validation['status'])) return $validation;
        if (is_array($validation['state']['editor_payload'] ?? null)) {
            return $organization->saveHolonEditorDefinition($validation['state']['editor_payload'], 0, (int)$parentHolon->getId(), (int)$holon->getId(), true, $collectiveHolonId);
        }
        $holon->set('name', $validation['state']['name']);
        $holon->set('nomcomplet', $validation['state']['full_name'] !== '' ? $validation['state']['full_name'] : null);
        $holon->set('color', $validation['state']['color'] !== '' ? $validation['state']['color'] : null);
        return $holon->save();
    }

    protected static function normalizeDateValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable $exception) {
            return '';
        }
    }
}

?>
