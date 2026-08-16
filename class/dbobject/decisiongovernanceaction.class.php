<?php
namespace dbObject;

class DecisionGovernanceAction extends DbObject
{
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
            'action_type' => 'Type d action',
            'target_type' => 'Type de cible',
            'target_id' => 'Cible',
            'before_state' => 'Etat avant',
            'after_state' => 'Etat apres',
            'parameters' => 'Parametres',
            'position' => 'Ordre',
            'status' => 'Statut',
            'status_message' => 'Message de statut',
            'applied_at' => 'Application',
            'created_at' => 'Creation',
            'updated_at' => 'Mise a jour',
        ];
    }

    public static function attributeDescriptions()
    {
        return [
            'before_state' => 'Instantane utilise pour detecter un conflit avant application.',
            'after_state' => 'Etat a appliquer si la proposition est acceptee.',
            'parameters' => 'Metadonnees versionnees propres au type d action.',
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
            'description' => Rule::sanitizeContentHtml((string)($state['description'] ?? '')),
            'scope' => Rule::normalizeScope($state['scope'] ?? Rule::SCOPE_LOCAL),
            'review_date' => self::normalizeDateValue($state['review_date'] ?? ''),
            'expiration_date' => self::normalizeDateValue($state['expiration_date'] ?? ''),
        ];
        if ($normalized['IDauthority']) {
            $normalized['IDholon'] = null;
        } else {
            $normalized['scope'] = Rule::SCOPE_LOCAL;
        }
        return $normalized;
    }

    public static function validateRuleUpdate(Rule $rule, array $afterState, $expectedHolonId)
    {
        $ruleHolon = $rule->getHolon();
        if (!$ruleHolon instanceof Holon || (int)$ruleHolon->getId() !== (int)$expectedHolonId) {
            return ['status' => false, 'message' => 'La regle doit etre definie dans le contexte de la decision.'];
        }
        $state = self::normalizeRuleState($afterState, $rule);
        if ((int)$state['IDauthority'] > 0) {
            $authority = new Authority();
            if (!$authority->load((int)$state['IDauthority']) || (int)$authority->get('IDholon') !== (int)$expectedHolonId) {
                return ['status' => false, 'message' => 'Le domaine d autorite choisi n appartient pas au contexte de la decision.'];
            }
        } else {
            $state['IDholon'] = (int)$expectedHolonId;
            $state['scope'] = Rule::SCOPE_LOCAL;
        }
        if ($state['title'] === '' || $state['description'] === '' || $state['review_date'] === '' || $state['expiration_date'] === '') {
            return ['status' => false, 'message' => 'Le titre, la regle et les deux dates sont obligatoires.'];
        }
        if ($state['review_date'] > $state['expiration_date']) {
            return ['status' => false, 'message' => 'La date de requestionnement ne peut pas suivre la date d echeance.'];
        }
        return ['status' => true, 'state' => $state];
    }

    public static function validateRuleCreate(array $afterState, $expectedHolonId)
    {
        $state = self::normalizeRuleState($afterState);
        if ((int)$state['IDauthority'] > 0) {
            $authority = new Authority();
            if (!$authority->load((int)$state['IDauthority']) || (int)$authority->get('IDholon') !== (int)$expectedHolonId) {
                return ['status' => false, 'message' => 'Le domaine d autorite choisi n appartient pas au contexte de la decision.'];
            }
            $state['IDholon'] = null;
        } else {
            $state['IDauthority'] = null;
            $state['IDholon'] = (int)$expectedHolonId;
            $state['scope'] = Rule::SCOPE_LOCAL;
        }
        if ($state['title'] === '' || $state['description'] === '' || $state['review_date'] === '' || $state['expiration_date'] === '') {
            return ['status' => false, 'message' => 'Le titre, la regle et les deux dates sont obligatoires.'];
        }
        if ($state['review_date'] > $state['expiration_date']) {
            return ['status' => false, 'message' => 'La date de requestionnement ne peut pas suivre la date d echeance.'];
        }
        return ['status' => true, 'state' => $state];
    }

    public static function validateRuleDelete(Rule $rule, $expectedHolonId)
    {
        $ruleHolon = $rule->getHolon();
        if (!$ruleHolon instanceof Holon || (int)$ruleHolon->getId() !== (int)$expectedHolonId) {
            return ['status' => false, 'message' => 'La regle doit etre definie dans le contexte de la decision.'];
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

    public static function findRolesInGovernanceContext(Holon $contextHolon)
    {
        $roles = [];
        $visit = static function (Holon $parent) use (&$visit, &$roles) {
            foreach ($parent->getChildren() as $child) {
                if (!$child instanceof Holon) continue;
                $typeId = (int)$child->get('IDtypeholon');
                if ($typeId === 1) {
                    $roles[(int)$child->getId()] = $child;
                } elseif ($typeId === 3) {
                    $visit($child);
                }
            }
        };
        $visit($contextHolon);
        return array_values($roles);
    }

    public static function roleBelongsToGovernanceContext(Holon $role, Holon $contextHolon)
    {
        if ((int)$role->get('IDtypeholon') !== 1) return false;
        $parentId = (int)$role->get('IDholon_parent');
        $guard = 0;
        while ($parentId > 0 && $guard < 100) {
            if ($parentId === (int)$contextHolon->getId()) return true;
            $parent = new Holon();
            if (!$parent->load($parentId) || (int)$parent->get('IDtypeholon') !== 3) return false;
            $parentId = (int)$parent->get('IDholon_parent');
            $guard++;
        }
        return false;
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
            return ['status' => false, 'message' => 'Le nom du role est obligatoire.'];
        }
        if (mb_strlen($state['name'], 'UTF-8') > 255 || mb_strlen($state['full_name'], 'UTF-8') > 255) {
            return ['status' => false, 'message' => 'Le nom du role est trop long.'];
        }
        if ($role === null && $state['template_id'] <= 0) {
            return ['status' => false, 'message' => 'Le modele du role est obligatoire.'];
        }
        if ($state['template_id'] > 0) {
            $template = new Holon();
            if (!$template->load($state['template_id']) || (int)$template->get('IDtypeholon') !== 1) {
                return ['status' => false, 'message' => 'Le modele de role choisi est invalide.'];
            }
        }
        return ['status' => true, 'state' => $state];
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
            $items[] = '<li><strong>Proprietes</strong> : ' . count($state['editor_payload']['properties']) . ' propriete(s) configuree(s)</li>';
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
                $changes[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> : ' . htmlspecialchars($before, ENT_QUOTES, 'UTF-8') . ' &rarr; ' . htmlspecialchars($after, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }
        $beforeProperties = self::indexRoleProperties($beforeState['editor_payload']['properties'] ?? []);
        $afterProperties = self::indexRoleProperties($afterState['editor_payload']['properties'] ?? []);
        foreach ($afterProperties as $key => $property) {
            $beforeValue = self::formatRolePropertyValue(
                (string)($beforeProperties[$key]['listItemType'] ?? '') === 'authority'
                    ? ($beforeProperties[$key]['displayValue'] ?? $beforeProperties[$key]['value'] ?? '')
                    : ($beforeProperties[$key]['value'] ?? '')
            );
            $afterValue = self::formatRolePropertyValue(
                (string)($property['listItemType'] ?? '') === 'authority'
                    ? ($property['displayValue'] ?? $property['value'] ?? '')
                    : ($property['value'] ?? '')
            );
            if ($beforeValue === $afterValue) continue;
            $label = trim((string)($property['name'] ?? $property['shortname'] ?? 'Propriete')) ?: 'Propriete';
            $changes[] = '<li><strong>' . htmlspecialchars('Propriete - ' . $label, ENT_QUOTES, 'UTF-8') . '</strong> : '
                . htmlspecialchars($beforeValue, ENT_QUOTES, 'UTF-8') . ' &rarr; '
                . htmlspecialchars($afterValue, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        foreach ($beforeProperties as $key => $property) {
            if (isset($afterProperties[$key])) continue;
            $label = trim((string)($property['name'] ?? $property['shortname'] ?? 'Propriete')) ?: 'Propriete';
            $changes[] = '<li><strong>' . htmlspecialchars('Propriete - ' . $label, ENT_QUOTES, 'UTF-8') . '</strong> : supprimee</li>';
        }
        return count($changes) ? '<p>Modifications proposees :</p><ul>' . implode('', $changes) . '</ul>' : '<p>Aucune difference de contenu.</p>';
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

    protected static function formatRolePropertyValue($value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $value = trim(strip_tags((string)$value));
        return mb_strlen($value, 'UTF-8') > 240 ? mb_substr($value, 0, 237, 'UTF-8') . '...' : $value;
    }

    public static function buildRuleUpdateDescription(array $beforeState, array $afterState)
    {
        $labels = [
            'IDauthority' => 'Domaine d autorite',
            'title' => 'Titre',
            'intention' => 'Intention',
            'description' => 'Regle',
            'review_date' => 'Date de requestionnement',
            'expiration_date' => 'Date d echeance',
        ];
        $changes = [];
        foreach ($labels as $field => $label) {
            $before = trim(strip_tags((string)($beforeState[$field] ?? '')));
            $after = trim(strip_tags((string)($afterState[$field] ?? '')));
            if ($before === $after) {
                continue;
            }
            $changes[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> : '
                . htmlspecialchars($before, ENT_QUOTES, 'UTF-8') . ' &rarr; '
                . htmlspecialchars($after, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        return count($changes) > 0
            ? '<p>Modifications proposees :</p><ul>' . implode('', $changes) . '</ul>'
            : '<p>Aucune difference de contenu.</p>';
    }

    public static function buildRuleStateDescription(array $state)
    {
        $items = [];
        foreach ([
            'title' => 'Titre',
            'intention' => 'Intention',
            'description' => 'Regle',
            'review_date' => 'Date de requestionnement',
            'expiration_date' => 'Date d echeance',
        ] as $field => $label) {
            $value = trim(strip_tags((string)($state[$field] ?? '')));
            if ($value === '') {
                continue;
            }
            $items[] = '<li><strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> : '
                . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</li>';
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
                    self::setProposalActionStatus($proposal, self::STATUS_REJECTED, 'Proposition non acceptee.');
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
            return ['status' => false, 'failed' => true, 'message' => 'Connexion a la base impossible.'];
        }

        try {
            $pdo->beginTransaction();
            $actions = self::getPendingForProposalForUpdate((int)$proposal->getId());
            if (count($actions) === 0) {
                $pdo->commit();
                return ['status' => true, 'applied' => 0];
            }
            foreach ($actions as $action) {
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
                    throw new \RuntimeException('failure:Impossible d enregistrer le statut de l action.');
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

    protected function applyOne(DecisionProcess $decision)
    {
        $actionType = trim((string)$this->get('action_type'));
        if (in_array($actionType, [self::TYPE_HOLON_CREATE, self::TYPE_HOLON_UPDATE, self::TYPE_HOLON_DELETE], true)) {
            return $this->applyRoleAction($decision, $actionType);
        }
        if (!in_array($actionType, [self::TYPE_RULE_CREATE, self::TYPE_RULE_UPDATE, self::TYPE_RULE_DELETE], true)) {
            return ['status' => false, 'message' => 'Ce type d action n est pas encore executable.'];
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
                return ['status' => false, 'conflict' => true, 'message' => 'La creation de la regle est dans un etat incoherent.'];
            }
            $rule = new Rule();
            $saveResult = $rule->applyGovernanceState((array)$validation['state'], 0);
            if (!is_array($saveResult) || empty($saveResult['status']) || (int)$rule->getId() <= 0) {
                return [
                    'status' => false,
                    'message' => trim((string)($saveResult['text'] ?? 'La regle ne peut pas etre creee.')),
                ];
            }
            $this->set('target_id', (int)$rule->getId());
            return ['status' => true, 'created_id' => (int)$rule->getId()];
        }

        $rule = Rule::loadForGovernanceApplication((int)$this->get('target_id'));
        if (!$rule instanceof Rule) {
            return ['status' => false, 'conflict' => true, 'message' => 'La regle cible n existe plus.'];
        }
        $ruleHolon = $rule->getHolon();
        if (!$ruleHolon instanceof Holon || (int)$ruleHolon->getId() !== (int)$decision->get('IDholon')) {
            return ['status' => false, 'conflict' => true, 'message' => 'La regle cible a change de contexte.'];
        }

        $before = self::normalizeRuleState(self::normalizeState($this->get('before_state')));
        $current = self::captureRuleState($rule);
        if ($actionType === self::TYPE_RULE_DELETE) {
            if ($current !== $before) {
                return ['status' => false, 'conflict' => true, 'message' => 'La regle a ete modifiee depuis la proposition.'];
            }
            if (!$rule->delete()) {
                return ['status' => false, 'message' => 'La regle ne peut pas etre supprimee.'];
            }
            return ['status' => true, 'deleted_id' => (int)$rule->getId()];
        }

        $after = self::normalizeRuleState(self::normalizeState($this->get('after_state')));
        if ($current === $after) {
            return ['status' => true, 'already_applied' => true];
        }
        if ($current !== $before) {
            return ['status' => false, 'conflict' => true, 'message' => 'La regle a ete modifiee depuis la proposition.'];
        }

        $validation = self::validateRuleUpdate($rule, $after, (int)$decision->get('IDholon'));
        if (empty($validation['status'])) {
            return $validation;
        }
        return $rule->applyGovernanceState((array)$validation['state'], 0);
    }

    protected function applyRoleAction(DecisionProcess $decision, $actionType)
    {
        $context = new Holon();
        if (!$context->load((int)$decision->get('IDholon'))) {
            return ['status' => false, 'conflict' => true, 'message' => 'Le cercle de la decision n existe plus.'];
        }
        if ($actionType === self::TYPE_HOLON_CREATE) {
            $validation = self::validateRoleState(self::normalizeState($this->get('after_state')), $context);
            if (empty($validation['status'])) return $validation;
            $existingTargetId = (int)$this->get('target_id');
            if ($existingTargetId > 0) {
                $existingRole = new Holon();
                if ($existingRole->load($existingTargetId)
                    && (int)$existingRole->get('IDtypeholon') === 1
                    && (int)$existingRole->get('IDholon_parent') === (int)$context->getId()
                    && self::captureRoleState($existingRole) === array_diff_key((array)$validation['state'], ['editor_payload' => true])) {
                    return ['status' => true, 'already_applied' => true];
                }
                return ['status' => false, 'conflict' => true, 'message' => 'La creation du role est dans un etat incoherent.'];
            }
            $role = new Holon();
            $state = $validation['state'];
            if (is_array($state['editor_payload'] ?? null)) {
                $organization = new Organization();
                if (!$organization->load((int)$decision->get('IDorganization'))) {
                    return ['status' => false, 'message' => 'L organisation de la decision est introuvable.'];
                }
                $result = $organization->saveHolonEditorDefinition($state['editor_payload'], 0, (int)$context->getId(), 0, true);
                if (empty($result['status'])) return $result;
                $createdId = (int)($result['holon']['id'] ?? 0);
                if ($createdId <= 0) return ['status' => false, 'message' => 'Le role ne peut pas etre cree.'];
                $this->set('target_id', $createdId);
                return ['status' => true, 'created_id' => $createdId];
            }
            $role->set('name', $state['name']);
            $role->set('nomcomplet', $state['full_name'] !== '' ? $state['full_name'] : null);
            $role->set('color', $state['color'] !== '' ? $state['color'] : null);
            $role->set('IDtypeholon', 1);
            $role->set('IDholon_parent', (int)$context->getId());
            $role->set('IDholon_template', $state['template_id']);
            $role->set('IDholon_org', (int)$context->get('IDholon_org'));
            $role->set('IDuser', (int)$context->get('IDuser'));
            $role->set('active', true);
            $role->set('visible', true);
            $role->save();
            if ((int)$role->getId() <= 0) return ['status' => false, 'message' => 'Le role ne peut pas etre cree.'];
            $this->set('target_id', (int)$role->getId());
            return ['status' => true, 'created_id' => (int)$role->getId()];
        }
        $role = new Holon();
        if (!$role->load((int)$this->get('target_id')) || !self::roleBelongsToGovernanceContext($role, $context)) {
            return ['status' => false, 'conflict' => true, 'message' => 'Le role cible n appartient plus a ce cercle.'];
        }
        $before = self::normalizeRoleState(self::normalizeState($this->get('before_state')));
        $current = self::captureRoleState($role);
        $beforeComparable = array_diff_key($before, ['editor_payload' => true]);
        if ($actionType === self::TYPE_HOLON_DELETE) {
            if ($current !== $beforeComparable) return ['status' => false, 'conflict' => true, 'message' => 'Le role a ete modifie depuis la proposition.'];
            return $role->delete() ? ['status' => true, 'deleted_id' => (int)$role->getId()] : ['status' => false, 'message' => 'Le role ne peut pas etre supprime.'];
        }
        $after = self::normalizeRoleState(self::normalizeState($this->get('after_state')), $role);
        if ($current === $after) return ['status' => true, 'already_applied' => true];
        if ($current !== $beforeComparable) return ['status' => false, 'conflict' => true, 'message' => 'Le role a ete modifie depuis la proposition.'];
        $validation = self::validateRoleState($after, $context, $role);
        if (empty($validation['status'])) return $validation;
        if (is_array($validation['state']['editor_payload'] ?? null)) {
            $organization = new Organization();
            if (!$organization->load((int)$decision->get('IDorganization'))) {
                return ['status' => false, 'message' => 'L organisation de la decision est introuvable.'];
            }
            return $organization->saveHolonEditorDefinition($validation['state']['editor_payload'], 0, (int)$context->getId(), (int)$role->getId(), true);
        }
        $role->set('name', $validation['state']['name']);
        $role->set('nomcomplet', $validation['state']['full_name'] !== '' ? $validation['state']['full_name'] : null);
        $role->set('color', $validation['state']['color'] !== '' ? $validation['state']['color'] : null);
        return $role->save();
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
