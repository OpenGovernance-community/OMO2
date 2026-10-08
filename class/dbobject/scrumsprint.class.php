<?php
namespace dbObject;

class ScrumSprint extends DbObject
{
    private static array $locks = [];
    public static function tableName() { return 'scrum_sprint'; }
    public static function rules() {
        return [
            [['title', 'start_date', 'end_date', 'IDorganization', 'IDholon'], 'required'],
            [['id', 'baseline'], 'integer'], [['IDorganization', 'IDholon'], 'fk'],
            [['title', 'state'], 'string'], [['objective'], 'text'],
            [['start_date', 'end_date'], 'date'], [['started_at', 'finished_at'], 'datetime'],
            [['archived'], 'boolean'], [['id'], 'safe'],
        ];
    }
    public static function attributeLabels() {
        return ['title' => 'Titre', 'objective' => 'Objectif / raison d etre', 'start_date' => 'Debut', 'end_date' => 'Fin',
            'IDorganization' => 'Organisation', 'IDholon' => 'Espace', 'state' => 'Etat', 'baseline' => 'Charge initiale',
            'started_at' => 'Demarrage', 'finished_at' => 'Cloture', 'archived' => 'Archive'];
    }
    public static function attributeLength() { return ['title' => 255, 'state' => 20]; }
    public static function available(): bool { return self::tableExists('scrum_sprint'); }
    public static function now(): \DateTimeImmutable { return new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zurich')); }
    public static function checkedSave(DbObject $object): void {
        $result = $object->save();
        if (!is_array($result) || empty($result['status'])) { throw new \RuntimeException($result['errorCode'] ?? 'save'); }
    }
    // Serialize imports, lifecycle transitions and project edits, including nested project saves.
    public static function locked(int $organizationId, callable $callback) {
        if (isset(self::$locks[$organizationId])) { return $callback(); }
        $name = 'omo_scrum_' . $organizationId;
        $row = self::fetchRow('SELECT GET_LOCK(:name, 10) AS acquired', ['name' => $name]);
        if ((int)($row['acquired'] ?? 0) !== 1) { throw new \RuntimeException('busy'); }
        self::$locks[$organizationId] = true;
        $pdo = self::getPdo();
        $ownTransaction = !$pdo->inTransaction();
        try {
            if ($ownTransaction) { $pdo->beginTransaction(); }
            $result = $callback();
            if ($ownTransaction) { $pdo->commit(); }
            return $result;
        } catch (\Throwable $error) {
            if ($ownTransaction && $pdo->inTransaction()) { $pdo->rollBack(); }
            throw $error;
        } finally {
            unset(self::$locks[$organizationId]);
            self::fetchRow('SELECT RELEASE_LOCK(:name) AS released', ['name' => $name]);
        }
    }
    public static function forContext(int $organizationId, int $holonId, bool $archived): array {
        $items = [];
        foreach (self::fetchAll('SELECT id FROM scrum_sprint WHERE IDorganization=:oid AND IDholon=:hid AND archived=:archived ORDER BY start_date DESC, id DESC',
            ['oid' => $organizationId, 'hid' => $holonId, 'archived' => (int)$archived]) ?: [] as $row) {
            $item = new self();
            if ($item->load((int)$row['id'], true)) { $items[] = $item; }
        }
        return $items;
    }
    public function editable(): bool { return in_array($this->get('state'), ['scheduled', 'stopped'], true); }
    public function endAt(): \DateTimeImmutable {
        return new \DateTimeImmutable($this->get('end_date')->format('Y-m-d') . ' 23:59:59', new \DateTimeZone('Europe/Zurich'));
    }
    public static function projectData(Project $project): array {
        return ['id' => (int)$project->getId(), 'parent' => (int)$project->get('IDproject_parent'),
            'title' => (string)$project->get('title'), 'status' => Project::normalizeStatus($project->get('status')),
            'size' => (string)$project->get('project_size'), 'user' => (int)$project->get('IDuser'),
            'holon' => (int)$project->get('IDholon')];
    }
    public static function points(string $size): int { return ['S' => 1, 'M' => 4, 'L' => 16, 'XL' => 64, 'XXL' => 256][$size] ?? 0; }
    // A parent's total is max(estimate, sum of recursive child totals). Only the residual belongs to it.
    public static function calculate(array $nodes): array {
        $children = [];
        foreach ($nodes as $id => $node) {
            if (isset($nodes[$node['parent'] ?? 0])) { $children[$node['parent']][] = $id; }
        }
        $result = []; $path = [];
        $visit = function ($id) use (&$visit, &$result, &$path, $nodes, $children): array {
            if (isset($result[$id])) { return $result[$id]; }
            if (isset($path[$id])) { throw new \RuntimeException('tree'); }
            $path[$id] = true; $childTotal = 0; $childRemaining = 0;
            foreach ($children[$id] ?? [] as $child) {
                $value = $visit($child); $childTotal += $value['total']; $childRemaining += $value['remaining'];
            }
            $points = (int)($nodes[$id]['points'] ?? self::points($nodes[$id]['size'] ?? ''));
            $own = max(0, $points - $childTotal);
            $remaining = in_array($nodes[$id]['status'] ?? '', [Project::STATUS_REVIEW, Project::STATUS_DONE], true) ? 0 : $own;
            unset($path[$id]);
            return $result[$id] = ['total' => max($points, $childTotal), 'own' => $own, 'remaining' => $remaining + $childRemaining, 'children' => $childTotal];
        };
        $total = 0; $remaining = 0;
        foreach ($nodes as $id => $node) { $visit($id); }
        foreach ($nodes as $id => $node) {
            if (!isset($nodes[$node['parent'] ?? 0])) { $total += $result[$id]['total']; $remaining += $result[$id]['remaining']; }
        }
        return ['total' => $total, 'remaining' => $remaining, 'nodes' => $result];
    }
    public static function tree(int $organizationId, array $rootIds): array {
        $all = new ArrayProject(); $all->loadForOrganization($organizationId, false, Project::KIND_STANDARD, true);
        $byId = []; $children = [];
        foreach ($all as $project) { $id = (int)$project->getId(); $byId[$id] = $project; $children[(int)$project->get('IDproject_parent')][] = $id; }
        $selected = []; $path = [];
        $visit = function (int $id) use (&$visit, &$selected, &$path, $children, $byId) {
            if (isset($path[$id])) { throw new \RuntimeException('tree'); }
            if (isset($selected[$id])) { return; }
            if (!isset($byId[$id])) { throw new \RuntimeException('missing'); }
            $path[$id] = true; $selected[$id] = $byId[$id];
            foreach ($children[$id] ?? [] as $child) { $visit($child); }
            unset($path[$id]);
        };
        foreach ($rootIds as $id) { $visit((int)$id); }
        return $selected;
    }
    public function nodes(bool $live = true): array {
        $nodes = [];
        foreach (ScrumProject::forSprint((int)$this->getId()) as $link) {
            $node = json_decode((string)$link->get('snapshot'), true);
            if ($live && $this->get('state') !== 'finished' && (int)$link->get('IDproject') > 0) {
                $project = new Project();
                if ($project->load((int)$link->get('IDproject'), true)) { $node = self::projectData($project); }
            }
            $node['parent'] = (int)$link->get('parent_key');
            $node['points'] = (int)$link->get('points');
            $node['own'] = (int)$link->get('own_points');
            $nodes[(int)$link->get('project_key')] = $node;
        }
        return $nodes;
    }
    public function import(array $rootIds, array $sizes, callable $canView, callable $canEdit): array {
        if (!$this->editable()) { throw new \RuntimeException('locked'); }
        $existing = $this->nodes();
        $projects = self::tree((int)$this->get('IDorganization'), array_merge(array_keys($existing), $rootIds));
        $nodes = []; $changes = [];
        foreach ($projects as $id => $project) {
            if (!$canView($project) || $project->isPrivateProposal()) { throw new \RuntimeException('access'); }
            $busy = self::fetchRow("SELECT s.id FROM scrum_project sp JOIN scrum_sprint s ON s.id=sp.IDsprint WHERE sp.IDproject=:pid AND s.id<>:sid AND s.state<>'finished' LIMIT 1", ['pid' => $id, 'sid' => (int)$this->getId()]);
            if ($busy) { throw new \RuntimeException('conflict'); }
            $size = (string)($sizes[$id] ?? $project->get('project_size'));
            if (self::points($size) === 0) { throw new \RuntimeException('estimate'); }
            if ($size !== (string)$project->get('project_size')) {
                if (!$canEdit($project)) { throw new \RuntimeException('access'); }
                $project->set('project_size', $size); $changes[$id] = $project;
            }
            $nodes[$id] = self::projectData($project);
            if ($project->get('status') === Project::STATUS_SOMEDAY) {
                if (!$canEdit($project)) { throw new \RuntimeException('access'); }
                $project->set('status', Project::STATUS_READY); $changes[$id] = $project;
                $nodes[$id] = self::projectData($project);
            }
        }
        foreach ($changes as $project) { self::checkedSave($project); }
        $calculation = self::calculate($nodes);
        ScrumProject::clearSprint((int)$this->getId());
        foreach ($nodes as $id => $node) {
            $link = new ScrumProject();
            $link->set('IDsprint', (int)$this->getId()); $link->set('IDproject', $id); $link->set('project_key', $id);
            $link->set('parent_key', $node['parent']); $link->set('points', self::points($node['size']));
            $link->set('own_points', $calculation['nodes'][$id]['own']); $link->set('snapshot', json_encode($node, JSON_UNESCAPED_UNICODE));
            self::checkedSave($link);
        }
        $this->set('baseline', $calculation['total']); self::checkedSave($this);
        return $calculation;
    }
    public function removeTree(int $projectId): void {
        if (!$this->editable()) { throw new \RuntimeException('locked'); }
        $nodes = $this->nodes();
        if (!isset($nodes[$projectId]) || isset($nodes[$nodes[$projectId]['parent']])) { throw new \RuntimeException('partial'); }
        $remove = [$projectId => true];
        do {
            $count = count($remove);
            foreach ($nodes as $id => $node) { if (isset($remove[$node['parent']])) { $remove[$id] = true; } }
        } while (count($remove) > $count);
        foreach (ScrumProject::forSprint((int)$this->getId()) as $link) {
            if (isset($remove[(int)$link->get('project_key')])) { if (!$link->delete()) { throw new \RuntimeException('save'); } }
        }
        $this->set('baseline', self::calculate($this->nodes())['total']); self::checkedSave($this);
    }
    public function sample(string $source, ?\DateTimeImmutable $at = null): void {
        if ($this->get('state') !== 'running') { return; }
        $at ??= self::now();
        if ($source === 'daily' && ScrumSample::hasDaily((int)$this->getId(), $at)) { return; }
        $remaining = 0;
        foreach (ScrumProject::forSprint((int)$this->getId()) as $link) {
            $node = json_decode((string)$link->get('snapshot'), true);
            $project = new Project();
            if ((int)$link->get('IDproject') > 0 && $project->load((int)$link->get('IDproject'), true)) { $node = self::projectData($project); }
            if (!in_array($node['status'], [Project::STATUS_REVIEW, Project::STATUS_DONE], true)) { $remaining += (int)$link->get('own_points'); }
            $link->set('snapshot', json_encode($node, JSON_UNESCAPED_UNICODE)); self::checkedSave($link);
        }
        $sample = new ScrumSample(); $sample->set('IDsprint', (int)$this->getId()); $sample->set('sampled_at', $at);
        $sample->set('remaining', $remaining); $sample->set('source', $source); self::checkedSave($sample);
    }
    public function start(?\DateTimeImmutable $at = null): void {
        $at ??= self::now();
        if ($this->get('state') === 'finished' || $at > $this->endAt()) { throw new \RuntimeException('dates'); }
        // Refresh the complete hierarchy and estimates before fixing the baseline.
        $this->import([], [], static fn () => true, static fn () => false);
        ScrumSample::clearSprint((int)$this->getId());
        $this->set('state', 'running'); $this->set('started_at', $at); self::checkedSave($this);
        $this->sample('start', $at);
    }
    public function sync(?\DateTimeImmutable $now = null): void {
        $now ??= self::now();
        if ($this->get('state') === 'finished') { return; }
        if ($now > $this->endAt()) {
            // Also retain the final state if a stopped sprint was never restarted.
            $this->set('state', 'running');
            $this->sample('finish', $this->endAt());
            $this->set('state', 'finished'); $this->set('finished_at', $this->endAt()); self::checkedSave($this); return;
        }
        if ($this->get('state') === 'scheduled' && $this->get('start_date')->format('Y-m-d') <= $now->format('Y-m-d')) {
            try { $this->start($now); }
            catch (\RuntimeException $error) {
                if (!in_array($error->getMessage(), ['estimate', 'conflict', 'missing', 'access'], true)) { throw $error; }
                $this->set('state', 'stopped'); self::checkedSave($this);
                error_log('Scrum start requires preparation: sprint ' . $this->getId() . ', ' . $error->getMessage());
            }
        }
    }
    public static function maintenance(?int $organizationId = null, string $source = 'daily'): int {
        if (!self::available()) { return 0; }
        $rows = self::fetchAll("SELECT id, IDorganization FROM scrum_sprint WHERE state<>'finished'" . ($organizationId ? ' AND IDorganization=:oid' : ''), $organizationId ? ['oid' => $organizationId] : []) ?: [];
        $count = 0;
        foreach ($rows as $row) {
            self::locked((int)$row['IDorganization'], function () use ($row, $source, &$count) {
                $sprint = new self();
                if ($sprint->load((int)$row['id'], true)) { $sprint->sync(); $sprint->sample($source); $count++; }
            });
        }
        return $count;
    }
    public static function validateProjectChange(Project $project): ?string {
        $id = (int)$project->getId();
        $old = $id ? self::fetchRow('SELECT status, IDproject_parent FROM project WHERE id=:id', ['id' => $id]) : null;
        if ($id && $project->get('status') === Project::STATUS_SOMEDAY) {
            $linked = self::fetchRow("SELECT sp.id FROM scrum_project sp JOIN scrum_sprint s ON s.id=sp.IDsprint WHERE sp.IDproject=:id AND s.state<>'finished' LIMIT 1", ['id' => $id]);
            if ($linked) { return 'sprint_someday'; }
        }
        if ($project->get('status') === Project::STATUS_DONE && (!$old || $old['status'] !== Project::STATUS_DONE)) {
            $child = self::fetchRow("SELECT id FROM project WHERE IDproject_parent=:id AND status<>'done' LIMIT 1", ['id' => $id]);
            if ($id && $child) { return 'children_not_done'; }
        }
        if (!$old || (int)$old['IDproject_parent'] !== (int)$project->get('IDproject_parent')) {
            foreach (array_unique([$id, (int)($old['IDproject_parent'] ?? 0), (int)$project->get('IDproject_parent')]) as $relatedId) {
                if ($relatedId <= 0) { continue; }
                $linked = self::fetchRow("SELECT sp.id FROM scrum_project sp JOIN scrum_sprint s ON s.id=sp.IDsprint WHERE sp.IDproject=:id AND s.state='running' LIMIT 1", ['id' => $relatedId]);
                if ($linked) { return 'sprint_tree_locked'; }
            }
        }
        return null;
    }

    public static function syncProject(int $projectId, bool $sample): void {
        if ($projectId <= 0) { return; }
        $rows = self::fetchAll("SELECT s.id FROM scrum_sprint s JOIN scrum_project sp ON sp.IDsprint=s.id WHERE sp.IDproject=:id AND s.state<>'finished'", ['id' => $projectId]) ?: [];
        foreach ($rows as $row) {
            $sprint = new self();
            if ($sprint->load((int)$row['id'], true)) { $sprint->sync(); if ($sample) { $sprint->sample('status'); } }
        }
    }

    public static function hasUnfinishedProject(int $projectId): bool {
        return (bool)self::fetchRow("SELECT sp.id FROM scrum_project sp JOIN scrum_sprint s ON s.id=sp.IDsprint WHERE sp.IDproject=:id AND s.state<>'finished' LIMIT 1", ['id' => $projectId]);
    }
}
