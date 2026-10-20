<?php
declare(strict_types=1);

// Exercise the generator without loading the event editor or touching a database.
class Document
{
    public const TYPE_PV = 'pv';
}

function omoCalendarCreateT(string $key, array $params = []): string
{
    $texts = [
        'calendar.create.document.summary.pv' => 'Compte rendu de « {eventTitle} » du {eventDate}.',
        'calendar.create.document.summary.document' => 'Document lié à « {eventTitle} » du {eventDate}.',
        'calendar.create.document.summary.date_time' => '{date} à {time}',
        'calendar.create.document.summary.untitled' => 'Événement',
        'calendar.create.document.keyword_pv' => 'PV',
    ];
    $text = $texts[$key] ?? throw new RuntimeException('Unexpected translation key: ' . $key);
    foreach ($params as $name => $value) {
        $text = str_replace('{' . $name . '}', (string)$value, $text);
    }
    return $text;
}

$source = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/create.php');
$start = strpos($source, 'function omoCalendarBuildDefaultLinkedDocumentValues(');
$end = strpos($source, "\n\$organizationId =", $start);
if ($start === false || $end === false) {
    throw new RuntimeException('Linked document generator not found.');
}
eval(substr($source, $start, $end - $start));

$date = new DateTimeImmutable('2026-10-20 09:00:00');
foreach (['test', 'Reunion "OP"', 'Chemin C:\\Documents', "L'equipe"] as $title) {
    $values = omoCalendarBuildDefaultLinkedDocumentValues($title, '', $date, 'html');
    $expected = 'Document lié à « ' . $title . ' » du 20.10.2026 à 09:00.';
    if ($values['description'] !== $expected) {
        throw new RuntimeException('The default summary must preserve the title without adding escapes.');
    }
}

$custom = 'Description avec "guillemets" et un chemin C:\\Documents.';
$values = omoCalendarBuildDefaultLinkedDocumentValues('test', $custom, $date, 'html');
if ($values['description'] !== $custom) {
    throw new RuntimeException('Explicit descriptions must remain unchanged.');
}
$values = omoCalendarBuildDefaultLinkedDocumentValues('', '', $date, 'html');
if ($values['description'] !== 'Document lié à « Événement » du 20.10.2026 à 09:00.') {
    throw new RuntimeException('Events without titles must keep the date fallback.');
}
$values = omoCalendarBuildDefaultLinkedDocumentValues('Reunion OP', '', $date, 'pv', 'PV Reunion OP');
if ($values['description'] !== 'Compte rendu de « Reunion OP » du 20.10.2026 à 09:00.') {
    throw new RuntimeException('Meeting report summaries must identify the report and its schedule.');
}
$values = omoCalendarBuildDefaultLinkedDocumentValues('Atelier', '', $date, 'html', '', true);
if ($values['description'] !== 'Document lié à « Atelier » du 20.10.2026.') {
    throw new RuntimeException('All-day summaries must omit the artificial starting time.');
}
echo "calendar_linked_document_summary_test: OK\n";
