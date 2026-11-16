<?php

function commonSharePopupT(string $key, array $variables = []): string
{
    static $sourceLang = [
        'password' => ['text' => 'Mot de passe', 'context' => 'Share link protected by a password.'],
        'no_password' => ['text' => 'Sans mot de passe', 'context' => 'Share link accessible without a password.'],
        'expires' => ['text' => 'Expire le {date}', 'context' => 'Share link expiration indicator.'],
        'expired' => ['text' => 'Expiré le {date}', 'context' => 'Expired share link indicator.'],
        'no_expiration' => ['text' => 'Sans expiration', 'context' => 'Share link without an expiration date.'],
        'editing' => ['text' => 'Édition autorisée', 'context' => 'Share link allows collaborative content editing.'],
        'readonly' => ['text' => 'Lecture seule', 'context' => 'Share link allows reading only.'],
    ];
    static $bundles = [];
    $locale = omoGetTranslationLocale();
    $bundle = $bundles[$locale] ??= loadTranslationBundle('common_share_popup', $locale, $sourceLang);
    return t($key, $variables, $bundle, $sourceLang);
}

function commonSharePopupIcon(string $name): string
{
    $paths = [
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'unlock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'edit' => '<path d="m16 3 5 5-12 12-6 1 1-6ZM14 5l5 5"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'delete' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
    ];
    return '<svg class="omo-share-popup__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? '') . '</svg>';
}

function commonRenderShareLinkIndicators(\dbObject\ShareLink $link, bool $canEdit = false, array $permissions = []): void
{
    $expiration = $link->get('dateexpiration');
    $expired = $link->isExpired();
    $expirationText = $expiration instanceof DateTimeInterface
        ? commonSharePopupT($expired ? 'expired' : 'expires', ['date' => $expiration->format('d.m.Y H:i')])
        : commonSharePopupT('no_expiration');
    $indicators = [
        [$link->requiresPassword() ? 'lock' : 'unlock', commonSharePopupT($link->requiresPassword() ? 'password' : 'no_password'), $link->requiresPassword() ? 'success' : 'muted'],
        ['calendar', $expirationText, $expired ? 'warning' : 'muted'],
        [$canEdit ? 'edit' : 'eye', commonSharePopupT($canEdit ? 'editing' : 'readonly'), $canEdit ? 'success' : 'muted'],
    ];
    foreach ($indicators as [$icon, $label, $tone]) {
        echo '<span class="generic-badge generic-badge--compact generic-badge--' . $tone . '">'
            . commonSharePopupIcon($icon) . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    foreach ($permissions as $label) {
        echo '<span class="generic-badge generic-badge--compact">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
