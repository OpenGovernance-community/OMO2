<?php

/** Active documents must be downloaded instead of executing on the application origin. */
function commonDocumentFileAllowsInline(string $contentType): bool
{
    $mime = strtolower(trim(explode(';', $contentType, 2)[0]));
    return in_array($mime, [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp',
        'image/x-icon', 'image/vnd.microsoft.icon', 'image/heic', 'image/heif',
        'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/webm',
        'audio/aac', 'audio/flac', 'audio/x-flac', 'audio/aiff', 'audio/x-aiff', 'audio/x-m4a',
        'video/mp4', 'video/webm', 'video/ogg', 'video/mpeg', 'video/quicktime',
        'video/x-msvideo', 'video/x-matroska',
    ], true);
}

function commonDocumentFileSendHeaders(string $filename, string $contentType, bool $inline = false): void
{
    $mime = strtolower(trim(explode(';', $contentType, 2)[0]));
    if (!preg_match('~^[a-z0-9][a-z0-9.+_-]*/[a-z0-9][a-z0-9.+_-]*$~D', $mime)) {
        $mime = 'application/octet-stream';
    }
    $filename = str_replace(["\r", "\n", "\0"], '', $filename);
    $asciiName = trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename), '-.');
    $asciiName = $asciiName !== '' ? $asciiName : 'document';
    $disposition = $inline && commonDocumentFileAllowsInline($mime) ? 'inline' : 'attachment';
    header('X-Content-Type-Options: nosniff');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . $disposition . '; filename="' . $asciiName
        . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
}
