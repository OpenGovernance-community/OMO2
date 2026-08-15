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
            self::TYPE_HOLON_CREATE => ['target_type' => self::TARGET_HOLON, 'implemented' => false],
            self::TYPE_HOLON_UPDATE => ['target_type' => self::TARGET_HOLON, 'implemented' => false],
            self::TYPE_HOLON_DELETE => ['target_type' => self::TARGET_HOLON, 'implemented' => false],
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
