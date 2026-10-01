<?php
declare(strict_types=1);

use dbObject\DbObject;
use dbObject\PropertyFormat;

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/propertyformat.class.php';

final class HtmlSanitizationProbe extends DbObject
{
    public static function tableName()
    {
        return 'html_sanitization_probe';
    }

    public static function rules()
    {
        return [
            [['id'], 'integer'],
            [['content'], 'html'],
            [['id'], 'safe'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content' => 'Content',
        ];
    }
}

function assertHtmlSanitizationTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$unsafeHtml = '<h2 onclick="alert(1)">Titre <strong>important</strong></h2>'
    . '<p style="background-color: #aabbcc; position: fixed" onmouseover="alert(2)">Texte <em>formate</em>.</p>'
    . '<ul><li>Premier</li><li><u>Second</u></li></ul>'
    . '<table><tbody><tr><th colspan="2">Entete</th></tr><tr><td>Valeur</td><td>Autre</td></tr></tbody></table>'
    . '<a href="https://example.com/page" target="_blank" onclick="alert(3)">Lien sur</a>'
    . '<a href="page?heure=10:30">Lien relatif</a>'
    . '<a href="javascript:alert(4)">Lien dangereux</a>'
    . '<a href="java&#x0A;script:alert(5)">Lien obscurci</a>'
    . '<a href="data:text/html,&lt;script&gt;alert(6)&lt;/script&gt;">Lien data</a>'
    . '<img src="x" onerror="alert(6)">'
    . '<script>alert(7)</script>'
    . '<iframe srcdoc="<script>alert(8)</script>"></iframe>';

$probe = new HtmlSanitizationProbe();
$probe->set('content', $unsafeHtml);
$cleanHtml = (string)$probe->get('content');

assertHtmlSanitizationTest(str_contains($cleanHtml, '<h2>Titre <strong>important</strong></h2>'), 'Headings and bold text must be preserved.');
assertHtmlSanitizationTest(str_contains($cleanHtml, '<em>formate</em>'), 'Italic text must be preserved.');
assertHtmlSanitizationTest(str_contains($cleanHtml, '<ul><li>Premier</li><li><u>Second</u></li></ul>'), 'Lists and underline must be preserved.');
assertHtmlSanitizationTest(str_contains($cleanHtml, '<table>'), 'Tables must be preserved.');
assertHtmlSanitizationTest(str_contains($cleanHtml, 'background-color: #aabbcc'), 'Safe background colors must be preserved.');
assertHtmlSanitizationTest(str_contains($cleanHtml, 'href="https://example.com/page" target="_blank" rel="noopener noreferrer"'), 'Safe external links must be preserved and isolated.');
assertHtmlSanitizationTest(str_contains($cleanHtml, 'href="page?heure=10:30"'), 'Relative links containing a colon after the query delimiter must be preserved.');
assertHtmlSanitizationTest(str_contains($cleanHtml, 'Lien dangereux'), 'The visible text of rejected links must be preserved.');
assertHtmlSanitizationTest(!preg_match('/<\s*(?:script|iframe|img)\b/i', $cleanHtml), 'Active and unsupported elements must be removed.');
assertHtmlSanitizationTest(!preg_match('/\son[a-z]+\s*=/i', $cleanHtml), 'Event handler attributes must be removed.');
assertHtmlSanitizationTest(!str_contains(strtolower($cleanHtml), 'javascript:'), 'JavaScript links must be removed.');
assertHtmlSanitizationTest(!str_contains(strtolower($cleanHtml), 'data:text'), 'Data links must be removed.');
assertHtmlSanitizationTest(!str_contains(strtolower($cleanHtml), 'position:'), 'Unsafe style declarations must be removed.');
assertHtmlSanitizationTest(!str_contains($cleanHtml, 'alert(7)'), 'Script contents must not remain as visible content.');
assertHtmlSanitizationTest(PropertyFormat::sanitizeHtml($cleanHtml) === $cleanHtml, 'HTML sanitization must be idempotent.');

$loadedProbe = new HtmlSanitizationProbe();
assertHtmlSanitizationTest(
    $loadedProbe->hydrateFromDatabaseRow(['id' => 12, 'content' => '<p>Ancien <b>contenu</b></p><script>alert(9)</script>'], true),
    'A database row must be hydratable.'
);
$loadedHtml = (string)$loadedProbe->get('content');
assertHtmlSanitizationTest(str_contains($loadedHtml, '<p>Ancien <b>contenu</b></p>'), 'Existing basic formatting must survive database hydration.');
assertHtmlSanitizationTest(!str_contains($loadedHtml, 'alert(9)'), 'Existing malicious HTML must be removed during database hydration.');

echo "dbobject_html_sanitization_test: OK\n";
