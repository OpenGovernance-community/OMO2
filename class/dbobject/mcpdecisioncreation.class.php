<?php
namespace dbObject;

/** One atomic native ballot creation shared by REST and MCP, with safe retry recovery. */
class McpDecisionCreation extends DbObject
{
    public static function tableName() { return 'mcp_decision_creation'; }
    public static function rules()
    {
        return [[['id', 'created_at'], 'integer'], [['completed'], 'boolean'],
            [['IDuser', 'IDorganization', 'IDdecision_process'], 'fk'], [['key_hash', 'payload_hash'], 'string'], [['id'], 'safe']];
    }
    public static function attributeLabels()
    {
        return ['id' => 'ID', 'created_at' => 'Creation', 'completed' => 'Termine', 'IDuser' => 'Personne',
            'IDorganization' => 'Organisation', 'IDdecision_process' => 'Scrutin', 'key_hash' => 'Demande', 'payload_hash' => 'Contenu'];
    }
    public static function attributeLength() { return ['key_hash' => 64, 'payload_hash' => 64]; }
    public function canView() { return false; }
    public function canViewDetail() { return false; }
    public function canEdit() { return false; }
    public function canDelete() { return false; }

    private static function organization(array $grant): Organization
    {
        $org = McpStructure::organization($grant);
        $user = new User();
        if (!$user->load((int)$grant['IDuser']) || !$user->get('active') || !$org->isApplicationEnabled('decision', (int)$grant['IDuser'])) {
            throw new \DomainException('Decision application unavailable.');
        }
        return $org;
    }

    private static function destination(Organization $org, array $grant, int $id): ?Holon
    {
        if (!$id) {
            $root = $org->getEnabledStructuralRootHolon();
            $allowed = $root ? $root->isAllowed('CAN_CREATE_DECISION', false, (int)$grant['IDuser'])
                : Permission::userCanInOrganization('CAN_CREATE_DECISION', (int)$org->getId(), (int)$grant['IDuser']);
            if (!$allowed) throw new \DomainException('Organization decision creation not permitted.');
            return null;
        }
        $holon = new Holon();
        if (!$holon->load($id) || !$holon->get('active') || !$holon->get('visible') || !$org->containsHolon($holon)
            || !$holon->canViewDetail() || !$holon->isAllowed('CAN_CREATE_DECISION', false, (int)$grant['IDuser'])) {
            throw new \DomainException('Decision destination unavailable or unauthorized.');
        }
        return $holon;
    }

    public static function spaces(array $grant, array $args): array
    {
        $org = self::organization($grant); $items = []; $cursor = $args['after_id'] ?? 0; $complete = false;
        if (($args['kind'] ?? 'organization') === 'organization') {
            try { self::destination($org, $grant, 0); $items[] = self::space($org, null); } catch (\DomainException $error) { }
            $complete = true;
        } else {
            $limit = $args['limit'] ?? 20; $scanned = 0;
            while (count($items) < $limit && $scanned < 1000) {
                $rows = self::fetchAll('SELECT h.id FROM holon h LEFT JOIN holon root ON root.id = h.IDholon_org
                    WHERE COALESCE(NULLIF(h.IDorganization, 0), root.IDorganization) = :oid
                    AND h.active = 1 AND h.visible = 1 AND h.id > :cursor ORDER BY h.id ASC LIMIT 100',
                    ['oid' => (int)$org->getId(), 'cursor' => $cursor]);
                if ($rows === false) throw new \RuntimeException('Decision spaces query failed.');
                if (!$rows) { $complete = true; break; }
                foreach ($rows as $row) {
                    $cursor = (int)$row['id']; $scanned++;
                    try { $holon = self::destination($org, $grant, $cursor); } catch (\DomainException $error) { continue; }
                    $items[] = self::space($org, $holon);
                    if (count($items) === $limit) break;
                }
                if (count($items) < $limit && count($rows) < 100) { $complete = true; break; }
            }
        }
        return ['organization_id' => (int)$org->getId(), 'items' => $items, 'next_after_id' => $complete ? null : $cursor,
            'complete' => $complete, 'write_authorized' => \omoMcpCanCreateDecisions($grant), 'required_scope' => \OMO_MCP_DECISION_SCOPE];
    }

    private static function space(Organization $org, ?Holon $holon): array
    {
        $probe = new DecisionProcess(); $probe->set('IDorganization', $org->getId()); $probe->set('IDholon', $holon?->getId());
        $visibility = [];
        foreach (array_keys(DecisionProcess::getVisibilityTypeOptions()) as $type) if (!empty($probe->resolveVisibilityRuleInput($type)['status'])) $visibility[] = $type;
        return ['holon_id' => $holon ? (int)$holon->getId() : 0,
            'name' => trim(strip_tags($holon ? $holon->getDisplayName() : $org->get('name'))), 'visibility_types' => $visibility];
    }

    private static function response(Organization $org, array $grant, int $id, bool $replayed): array
    {
        $decision = new DecisionProcess();
        if (!$decision->load($id, true) || (int)$decision->get('IDorganization') !== (int)$org->getId()
            || (int)$decision->get('IDuser') !== (int)$grant['IDuser'] || !$decision->canViewDetail()) {
            throw new \DomainException('Previously created ballot unavailable. Do not retry with a new key.');
        }
        $group = $decision->getPrimaryGroup(false); $proposals = [];
        foreach ($group->getProposals(true) as $proposal) {
            $zone = new \DateTimeZone($proposal->get('timezone') ?: date_default_timezone_get());
            $event = Event::findByDecisionProposal((int)$proposal->getId());
            $proposals[] = ['proposal_id' => (int)$proposal->getId(), 'title' => (string)$proposal->get('title'),
                'description' => (string)$proposal->get('description'),
                'start_at' => $proposal->get('start_at') ? \DateTimeImmutable::createFromInterface($proposal->get('start_at'))->setTimezone($zone)->format(\DateTimeInterface::ATOM) : null,
                'end_at' => $proposal->get('end_at') ? \DateTimeImmutable::createFromInterface($proposal->get('end_at'))->setTimezone($zone)->format(\DateTimeInterface::ATOM) : null,
                'timezone' => $proposal->get('start_at') ? $zone->getName() : null,
                'event_id' => $event ? (int)$event->getId() : null, 'calendar_status' => $event ? (string)$event->get('status') : null];
        }
        $result = ['created' => true, 'replayed' => $replayed, 'decision_id' => $id, 'group_id' => (int)$group->getId(),
            'organization_id' => (int)$org->getId(), 'holon_id' => (int)$decision->get('IDholon'), 'title' => (string)$decision->get('title'),
            'question' => (string)$group->get('title'), 'method' => (string)$group->get('evaluation_method'), 'status' => (string)$decision->get('status'),
            'visibility_type' => (string)$decision->get('visibility_type'), 'participant_count' => count($decision->getParticipants(true)),
            'proposals' => $proposals, 'emails_sent' => false,
            'url' => McpContent::sourceUrl((int)$org->getId(), 'decision', $id, (int)$decision->get('IDholon')),
            'public_url' => $decision->getGenericPublicAccessUrl('participate')];
        foreach (['consultation_start_at', 'consultation_end_at', 'evaluation_start_at', 'evaluation_end_at'] as $field) {
            $value = $decision->get($field); $result[$field] = $value ? $value->format(\DateTimeInterface::ATOM) : null;
        }
        return $result;
    }

    public static function create(array $grant, array $args): array
    {
        $args = \omoApiDecisionValidate('omo_create_decision', $args);
        if (!\omoMcpCanCreateDecisions($grant) || !McpOauthGrant::hasActiveScopeAuthorization($grant, \OMO_MCP_DECISION_SCOPE)) {
            throw new \DomainException('Reconnect and authorize decisions:create before creating ballots.');
        }
        $org = self::organization($grant); $oid = (int)$org->getId(); $uid = (int)$grant['IDuser'];
        self::destination($org, $grant, $args['holon_id']);
        require_once dirname(__DIR__, 2) . '/omo/api/decision/modules/vote/shared.php';
        require_once dirname(__DIR__, 2) . '/omo/api/decision/modules/majority_judgment/shared.php';
        require_once dirname(__DIR__, 2) . '/omo/api/decision/modules/consent/shared.php';
        // Canonical key order allows equivalent JSON objects in either transport.
        $canonical = static function (array $value) use (&$canonical): array {
            if (!array_is_list($value)) ksort($value);
            foreach ($value as &$item) if (is_array($item)) $item = $canonical($item);
            return $value;
        };
        $payload = $args; unset($payload['request_key']);
        $hash = hash('sha256', json_encode($canonical($payload), JSON_THROW_ON_ERROR));
        $bindings = ['uid' => $uid, 'oid' => $oid, 'key_hash' => hash('sha256', $args['request_key'])];
        $lookup = 'SELECT * FROM mcp_decision_creation WHERE IDuser = :uid AND IDorganization = :oid AND key_hash = :key_hash';
        $pdo = self::getPdo();
        try {
            $pdo->beginTransaction();
            if (!self::execute('INSERT INTO mcp_decision_creation (IDuser, IDorganization, key_hash, payload_hash, created_at)
                VALUES (:uid, :oid, :key_hash, :payload_hash, :now) ON DUPLICATE KEY UPDATE id = id', $bindings + ['payload_hash' => $hash, 'now' => time()])) {
                throw new \RuntimeException('Decision request storage failed.');
            }
            $row = self::fetchRow($lookup . ' FOR UPDATE', $bindings);
            if (!$row || !hash_equals($row['payload_hash'], $hash)) throw new \DomainException('request_key already used for a different ballot.');
            if ($row['completed']) { $result = self::response($org, $grant, (int)$row['IDdecision_process'], true); $pdo->commit(); return $result; }
            if (isset($args['evaluation_end_at']) && \omoMcpCalendarDate($args['evaluation_end_at'], true) <= new \DateTimeImmutable()) {
                throw new \DomainException('Evaluation end must be in the future.');
            }
            $decision = new DecisionProcess();
            foreach (['IDorganization' => $oid, 'IDuser' => $uid, 'IDholon' => $args['holon_id'] ?: null,
                'title' => $args['title'], 'description' => $args['description'] ?? null, 'decision_type' => DecisionProcess::TYPE_DECISION,
                'evaluation_method' => $args['method'], 'visibility_type' => $args['visibility_type'], 'status' => DecisionProcess::STATUS_DRAFT] as $key => $value) $decision->set($key, $value);
            if (empty($decision->resolveVisibilityRuleInput($args['visibility_type'])['status'])) throw new \DomainException('Visibility not supported in this destination.');
            $storageZone = new \DateTimeZone(date_default_timezone_get());
            foreach (['consultation_start_at', 'consultation_end_at', 'evaluation_start_at', 'evaluation_end_at'] as $key) {
                if (isset($args[$key])) $decision->set($key, \omoMcpCalendarDate($args[$key], true)->setTimezone($storageZone));
            }
            $content = ['title' => false, 'description' => false, 'url' => false, 'date' => false];
            foreach ($args['proposals'] as $proposal) foreach (['title' => 'title', 'description' => 'description', 'start_at' => 'date'] as $field => $flag) if (isset($proposal[$field])) $content[$flag] = true;
            $config = ['proposal_content' => $content, 'allow_consultation_proposals' => isset($args['consultation_start_at'])];
            $parameters = match ($args['method']) {
                DecisionProcess::METHOD_SIMPLE_VOTE => \omoDecisionVoteMergeConfigIntoParameters([], \omoDecisionVoteBuildConfig($config)),
                DecisionProcess::METHOD_MAJORITY_JUDGMENT => \omoDecisionMajorityJudgmentMergeConfigIntoParameters([], \omoDecisionMajorityJudgmentBuildConfig($config)),
                DecisionProcess::METHOD_CONSENT => \omoDecisionConsentMergeConfigIntoParameters([], \omoDecisionConsentBuildConfig($config)),
            };
            $decision->set('parameters', $parameters);
            self::saveOrFail($decision);
            $group = $decision->ensurePrimaryGroup();
            if (!$group) throw new \RuntimeException('Decision question creation failed.');
            $group->set('title', $args['question']); $group->set('description', $args['question_description'] ?? null);
            self::saveOrFail($group);
            $invited = \omoDecisionApplyInvitationSelections($decision, $org, $oid, $args['invitation_holon_ids'] ?? [], $args['invitation_user_ids'] ?? [], [], false);
            if (empty($invited['status'])) throw new \DomainException($invited['message'] ?? 'Invalid decision invitations.');
            foreach ($args['invitation_holon_ids'] ?? [] as $id) {
                $holon = new Holon();
                if (!$holon->load($id) || !$holon->get('active') || !$holon->get('visible')) throw new \DomainException('Invitation holon unavailable.');
            }
            foreach ($args['invitation_user_ids'] ?? [] as $id) {
                $user = new User();
                if (!$user->load($id) || !$user->get('active') || !$user->canViewDetail()) throw new \DomainException('Invitation member unavailable.');
            }
            if (empty($decision->syncParticipantsFromInvitations()['status'])) throw new \RuntimeException('Decision participant synchronization failed.');
            foreach ($args['proposals'] as $position => $values) {
                $proposal = new DecisionProposal();
                foreach (['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(), 'IDuser_author' => $uid,
                    'title' => $values['title'] ?? '', 'description' => PropertyFormat::formattedTextToHtml($values['description'] ?? '', 'text'),
                    'position' => $position + 1, 'active' => 1] as $key => $value) $proposal->set($key, $value);
                if (isset($values['start_at'])) {
                    $range = DecisionProposal::normalizeCalendarRange(\omoMcpCalendarDate($values['start_at'], true), \omoMcpCalendarDate($values['end_at'], true), $args['timezone']);
                    if (empty($range['status'])) throw new \DomainException('Invalid proposal dates.');
                    foreach ($range['values'] as $key => $value) $proposal->set($key, $value);
                }
                self::saveOrFail($proposal);
            }
            // Reload avoids mirroring the process title over the newly written question.
            $decision->load($decision->getId(), true);
            $nextStatus = $decision->resolveAutomaticStatus();
            if ($nextStatus !== $decision->get('status')) { $decision->set('status', $nextStatus); self::saveOrFail($decision); }
            if (empty($decision->syncProposalCalendarEvents()['status'])) throw new \RuntimeException('Decision calendar synchronization failed.');
            if (!self::execute('UPDATE mcp_decision_creation SET IDdecision_process = :decision, completed = 1 WHERE id = :id',
                ['decision' => (int)$decision->getId(), 'id' => (int)$row['id']])) throw new \RuntimeException('Decision request completion failed.');
            $result = self::response($org, $grant, (int)$decision->getId(), false); $pdo->commit(); return $result;
        } catch (\Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
    }

    private static function saveOrFail(DbObject $object): void
    {
        $saved = $object->save();
        if (empty($saved['status'])) throw new \RuntimeException('Native decision save failed: ' . ($saved['text'] ?? get_class($object)));
    }
}
