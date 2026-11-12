<?php
declare(strict_types=1);

// Isolated model test: no application bootstrap or database connection.
namespace dbObject {
    class DbObject
    {
        public array $fields = [];
        public function get($key) { return $this->fields[$key] ?? null; }
        public function set($key, $value) { $this->fields[$key] = $value; }
        protected static function uiMessage($key) { return static::uiMessageSourceLang()[$key]['text']; }
    }
    class Holon extends DbObject
    {
        public static array $fixtures = [];
        public function load($id) { $this->fields = self::$fixtures[$id] ?? []; return (bool)$this->fields; }
        public function getContainingCircle($includeSelf = false) {
            $circle = new self();
            return $circle->load($this->get('circle')) ? $circle : null;
        }
        public function getAssociatedMemberUserIds(array $options = []) {
            return $this->get('members');
        }
    }
    class ArrayUserOrganization extends \ArrayObject
    {
        public function loadActiveForOrganization($id) {
            foreach (UserOrganization::$members[$id] ?? [] as $userId) {
                $member = new DbObject();
                $member->set('IDuser', $userId);
                $this[] = $member;
            }
        }
    }
    class UserOrganization
    {
        public static array $members = [7 => [11, 12, 13, 14], 8 => [15]];
        public static function hasActiveMembership($userId, $organizationId) {
            return in_array($userId, self::$members[$organizationId] ?? [], true);
        }
    }
    class DocumentPvPoint
    {
        public static function getUserDisplayNameForOrganization($id, $organizationId) {
            return [11 => 'Zoe', 12 => 'Alice', 13 => 'Bob', 14 => 'Claire', 15 => 'Foreign'][$id] ?? 'Former member';
        }
    }
}

namespace {
    use dbObject\{DeferredProposal, Holon, Project};
    require_once dirname(__DIR__) . '/class/dbobject/project.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/deferredproposal.class.php';

    function checkResponsible(bool $condition, string $message): void {
        if (!$condition) throw new RuntimeException($message);
    }
    function omoLoadTranslationBundle($domain, $source) { return $source; }
    function t($key, $parameters, $bundle, $source) { return $source[$key]['text']; }
    require_once dirname(__DIR__) . '/common/choice/deferred-editor-fields.php';

    Holon::$fixtures = [
        100 => ['IDorganization' => 7, 'IDtypeholon' => 1, 'circle' => 200, 'members' => [11, 12, 15]],
        200 => ['IDorganization' => 7, 'IDtypeholon' => 2, 'circle' => 200, 'members' => [11, 12, 13]],
        300 => ['IDorganization' => 8, 'IDtypeholon' => 1, 'circle' => 0, 'members' => [15]],
    ];
    $groups = Project::getResponsibleOptionGroups(7, 100);
    checkResponsible(array_keys($groups['role']) === [12, 11], 'Role holders must come first, sorted by name.');
    checkResponsible(array_keys($groups['circle']) === [13], 'Circle members must exclude role holders.');
    checkResponsible(array_keys($groups['organization']) === [14], 'Organization members must exclude earlier groups and foreigners.');
    $circleGroups = Project::getResponsibleOptionGroups(7, 200);
    checkResponsible($circleGroups['role'] === [] && count($circleGroups['circle']) === 3, 'A circle must not have a role group.');
    checkResponsible(count(Project::getResponsibleOptionGroups(7, 300)['organization']) === 4, 'Foreign contexts must not expose foreign members.');
    checkResponsible(Project::isAllowedResponsible(7, 14) && Project::isAllowedResponsible(7, 0), 'Organization members and empty assignments are allowed.');
    checkResponsible(!Project::isAllowedResponsible(7, 15) && !Project::isAllowedResponsible(7, -1), 'Foreign and negative assignments must be rejected.');

    $project = new Project();
    $project->set('IDorganization', 7);
    $project->set('IDuser', 99);
    checkResponsible(Project::isAllowedResponsible(7, 99, $project), 'An unchanged historical assignment must be preserved.');
    checkResponsible(!Project::isAllowedResponsible(8, 99, $project), 'The historical exception must stay in the same organization.');
    $state = DeferredProposal::normalizeProjectState(['IDholon' => 100, 'IDuser' => 12, 'IDuser_proposed' => 14, 'title' => 'Project']);
    $apply = new ReflectionMethod(DeferredProposal::class, 'applyProjectState');
    $apply->invoke(null, $project, $state, 7);
    checkResponsible($project->get('IDuser') === 12 && $project->get('IDuser_proposed') === 14, 'Applying the state must keep assignee and requester separate.');
    checkResponsible(DeferredProposal::captureProjectState($project)['IDuser'] === 12, 'Snapshots must preserve the assignee.');
    checkResponsible(DeferredProposal::normalizeProjectState(['title' => 'Legacy'], $project)['IDuser'] === 12, 'Legacy updates must retain the assignee.');
    checkResponsible(DeferredProposal::normalizeProjectState(['IDuser' => ''], $project)['IDuser'] === null, 'Assignments must be clearable.');

    ob_start();
    omoDeferredEditorRenderFields('project', $state, Project::getProposerOptions(7), null, $groups);
    $html = (string)ob_get_clean();
    checkResponsible(str_contains($html, 'name="IDuser"') && str_contains($html, 'value="12" selected'), 'The shared form must restore the assignee.');
    if (in_array('--fixture', $argv, true)) file_put_contents(dirname(__DIR__) . '/tmp/project-responsible-fields.html', $html);
    if (in_array('--html', $argv, true)) echo $html;
    else echo "project_responsible_test: OK\n";
}
