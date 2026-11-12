<?php
require_once __DIR__ . '/../../translations.php';

function omoDocumentHistoryT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'title' => ['text' => 'Anciennes versions', 'context' => 'Document version history section.'],
        'archive' => ['text' => 'Archiver maintenant', 'context' => 'Create an independent snapshot of the current file.'],
        'hint' => ['text' => 'Les archives sont des copies indépendantes, disponibles au téléchargement. Pour Collabora, archivez avant de commencer l’édition de la nouvelle version.', 'context' => 'Help for document history.'],
        'empty' => ['text' => 'Aucune version archivée pour le moment.', 'context' => 'Empty document history.'],
        'success' => ['text' => 'La version actuelle a été archivée.', 'context' => 'Successful document snapshot notification.'],
        'error' => ['text' => 'Impossible d’archiver cette version.', 'context' => 'Failed document snapshot notification.'],
        'manual' => ['text' => 'Archivage manuel', 'context' => 'Reason for a document version snapshot.'],
        'replacement' => ['text' => 'Avant remplacement', 'context' => 'Reason for a document version snapshot.'],
        'removal' => ['text' => 'Avant retrait du fichier', 'context' => 'Reason for a document version snapshot.'],
        'by' => ['text' => 'Par {name}', 'context' => 'User who archived a document version.'],
    ];
    static $bundles = [];
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('omo_document_history', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}

function omoDocumentHistoryVersionHtml(\dbObject\DocumentVersion $version, int $organizationId): string
{
    $escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $created = $version->get('datecreation');
    $date = $created instanceof DateTimeInterface ? $created->format('d.m.Y H:i:s') : '';
    $user = $version->get('user');
    $name = $user instanceof \dbObject\User ? $user->getScopedDisplayName($organizationId) : '';
    $reason = (string)$version->get('reason');
    $metadata = [$date];
    if (in_array($reason, ['manual', 'replacement', 'removal'], true)) {
        $metadata[] = omoDocumentHistoryT($reason);
    }
    if ($name !== '') $metadata[] = omoDocumentHistoryT('by', ['name' => $name]);
    return '<li class="generic-soft-panel generic-soft-panel--stack">'
        . '<a href="' . $escape($version->buildDownloadUrl()) . '" download>' . $escape($version->get('storedfilename')) . '</a>'
        . '<span class="generic-help-text">' . $escape(implode(' · ', $metadata)) . '</span></li>';
}
