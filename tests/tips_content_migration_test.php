<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/db/migrations.php';

use dbObject\{DbObject, FAQ, ArrayFAQ, Tip, ArrayTip, ArrayApplication};

function tipContentCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function tipContentSnapshot(string $collectionClass, array $fields): array
{
    $collection = new $collectionClass();
    $collection->load(['hydrate' => true]);
    $rows = [];
    foreach ($collection as $object) {
        $row = [];
        foreach ($fields as $field) {
            $value = $object->get($field);
            $row[$field] = $value instanceof DateTimeInterface ? $value->format('c') : $value;
        }
        $rows[(int)$object->getId()] = $row;
    }
    ksort($rows);
    return $rows;
}

$sql = loadSqlMigrationContent(dirname(__DIR__) . '/sql/2026-10-12-04-tips-user-help-content.sql');
$pattern = <<<'REGEX'
~INSERT INTO tip_help_seed \(seed_key, question, answer, detail, tip_text, displayorder\) VALUES \(\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*'((?:''|[^'])*)',\s*(\d+)\s*\);~s
REGEX;
preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER);
$seeds = [];
foreach ($matches as $match) {
    $values = array_map(static fn(string $value): string => str_replace("''", "'", $value), array_slice($match, 1));
    [$key, $question, $answer, $detail, $tip] = $values;
    tipContentCheck(!isset($seeds[$key]), 'Seed keys must be unique.');
    tipContentCheck(mb_strlen($question) <= 255 && mb_strlen($tip) <= 150, 'Tips are short teasers; detailed instructions belong in the FAQ.');
    tipContentCheck(!str_contains($tip, '<') && $tip !== $question, 'A tip must be plain text, distinct from the FAQ title.');
    tipContentCheck($answer !== '' && $detail !== '', 'Each FAQ has a summary and a detailed answer.');
    $seeds[$key] = compact('question', 'answer', 'detail', 'tip');
}
tipContentCheck(count($seeds) === 143, 'The complete user catalogue must be present.');

// Make the collision fixtures independent of whether the catalogue is already installed.
$nonce = bin2hex(random_bytes(4));
foreach (['calendar-shortcut', 'indicator-range', 'project-gantt', 'calendar-recurrence'] as $key) {
    $originalQuestion = $seeds[$key]['question'];
    $seeds[$key]['question'] = 'Test ' . $nonce . ' - ' . $originalQuestion;
    $sql = str_replace("'" . str_replace("'", "''", $originalQuestion) . "'",
        "'" . str_replace("'", "''", $seeds[$key]['question']) . "'", $sql);
}

$faqFields = ['question', 'answer', 'detail', 'isactive', 'IDorganization', 'IDholon', 'IDparcours', 'IDapplication', 'displayorder', 'created', 'updated'];
$tipFields = ['text', 'link', 'image', 'active'];
$pdo = DbObject::getPdo();
tipContentCheck($pdo instanceof PDO, 'Database connection is required.');
$pdo->beginTransaction();
try {
    // Existing titles can have arbitrary IDs, capitalisation and edited content.
    $existing = new FAQ();
    $existing->loadFromArray(['question' => mb_strtoupper($seeds['calendar-shortcut']['question']),
        'answer' => 'Production answer kept as written', 'detail' => '<p>Manually completed instructions.</p>', 'isactive' => 1]);
    tipContentCheck(!empty($existing->save()['status']), 'Cannot create pre-existing FAQ.');

    $hidden = new FAQ();
    $hidden->loadFromArray(['question' => $seeds['indicator-range']['question'],
        'answer' => 'Hidden production answer', 'detail' => '<p>Do not publish automatically.</p>', 'isactive' => 0]);
    tipContentCheck(!empty($hidden->save()['status']), 'Cannot create unpublished FAQ.');

    $applications = new ArrayApplication();
    $applications->load(['limit' => 1]);
    tipContentCheck(count($applications) === 1, 'An application is required for the scope fixture.');
    $scoped = new FAQ();
    $scoped->loadFromArray(['question' => $seeds['project-gantt']['question'], 'IDapplication' => $applications[0]->getId(),
        'answer' => 'Application-specific answer', 'detail' => '<p>Not a generic FAQ.</p>', 'isactive' => 1]);
    tipContentCheck(!empty($scoped->save()['status']), 'Cannot create scoped FAQ.');

    $duplicateHidden = new FAQ();
    $duplicateHidden->loadFromArray(['question' => $seeds['calendar-recurrence']['question'],
        'answer' => 'Older unpublished answer', 'detail' => '<p>Keep hidden.</p>', 'isactive' => 0]);
    tipContentCheck(!empty($duplicateHidden->save()['status']), 'Cannot create duplicate hidden FAQ.');
    $duplicateActive = new FAQ();
    $duplicateActive->loadFromArray(['question' => $seeds['calendar-recurrence']['question'],
        'answer' => 'Published answer', 'detail' => '<p>Prefer this answer.</p>', 'isactive' => 1]);
    tipContentCheck(!empty($duplicateActive->save()['status']), 'Cannot create duplicate published FAQ.');

    $editedTip = new Tip();
    $editedTip->loadFromArray(['text' => 'A manually edited and unpublished tip', 'link' => '#|faq-' . $existing->getId(), 'active' => 0]);
    tipContentCheck(!empty($editedTip->save()['status']), 'Cannot create pre-existing tip.');
    $beforeFaq = tipContentSnapshot(ArrayFAQ::class, $faqFields);
    $beforeTip = tipContentSnapshot(ArrayTip::class, $tipFields);

    executeSqlMigrationStatements($pdo, $sql);
    tipContentCheck($pdo->inTransaction(), 'Temporary table statements must preserve the test transaction.');
    $afterFaq = tipContentSnapshot(ArrayFAQ::class, $faqFields);
    $afterTip = tipContentSnapshot(ArrayTip::class, $tipFields);
    foreach ($beforeFaq as $id => $row) tipContentCheck($afterFaq[$id] === $row, 'An existing FAQ was modified: ' . $id);
    foreach ($beforeTip as $id => $row) tipContentCheck($afterTip[$id] === $row, 'An existing tip was modified: ' . $id);

    foreach ($seeds as $seed) {
        $candidates = [];
        foreach ($afterFaq as $id => $faq) {
            if (mb_strtolower($faq['question']) === mb_strtolower($seed['question'])
                && !$faq['IDorganization'] && !$faq['IDholon'] && !$faq['IDparcours'] && !$faq['IDapplication']) {
                $candidates[$id] = $faq;
            }
        }
        tipContentCheck($candidates !== [], 'A generic FAQ is missing: ' . $seed['question']);
        uasort($candidates, static fn(array $a, array $b): int => (int)$b['isactive'] <=> (int)$a['isactive']);
        $faqId = (int)array_key_first($candidates);
        $links = array_filter($afterTip, static fn(array $tip): bool => $tip['link'] === '#|faq-' . $faqId);
        tipContentCheck($links !== [], 'A tip is not linked to its resolved FAQ: ' . $seed['question']);
        foreach ($links as $tip) {
            if (!reset($candidates)['isactive']) tipContentCheck(!$tip['active'], 'An unpublished FAQ must not get a published tip.');
            tipContentCheck(Tip::isSafeLink($tip['link']), 'Generated FAQ links must be safe.');
        }
    }
    $shortcutFaqs = array_filter($afterFaq, static fn(array $faq): bool => mb_strtolower($faq['question']) === mb_strtolower($seeds['calendar-shortcut']['question']));
    $oldShortcutFaqs = array_filter($beforeFaq, static fn(array $faq): bool => mb_strtolower($faq['question']) === mb_strtolower($seeds['calendar-shortcut']['question']));
    tipContentCheck(count($shortcutFaqs) === count($oldShortcutFaqs), 'Title matching must reuse existing IDs and ignore letter case.');
    tipContentCheck(count(array_filter($afterTip, static fn(array $tip): bool => $tip['link'] === '#|faq-' . $duplicateActive->getId())) === 1,
        'A published FAQ must be preferred among duplicate titles.');
    tipContentCheck(count(array_filter($afterTip, static fn(array $tip): bool => $tip['link'] === '#|faq-' . $duplicateHidden->getId())) === 0,
        'The older unpublished duplicate must not be selected.');

    executeSqlMigrationStatements($pdo, $sql);
    tipContentCheck(tipContentSnapshot(ArrayFAQ::class, $faqFields) === $afterFaq, 'Rerunning the migration must not create or change FAQs.');
    tipContentCheck(tipContentSnapshot(ArrayTip::class, $tipFields) === $afterTip, 'Rerunning the migration must not create or change tips.');
    echo 'tips_content_migration_test: OK (' . count($seeds) . " linked seeds, preserved content and publication, idempotent)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
