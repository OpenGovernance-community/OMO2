<?php
require_once dirname(__DIR__) . '/shared_functions.php';
require_once __DIR__ . '/translations.php';

$sourceLang = [
    'offline.title' => ['text' => 'OMO hors ligne', 'context' => 'Offline browser page title.'],
    'offline.heading' => ['text' => 'OMO n’est pas disponible hors ligne', 'context' => 'Offline application heading.'],
    'offline.description' => ['text' => 'La connexion au serveur est nécessaire pour charger la structure et les outils de gouvernance.', 'context' => 'Offline application explanation.'],
    'offline.reconnect' => ['text' => 'Reconnectez-vous au réseau, puis revenez sur', 'context' => 'Offline reconnection instruction followed by the application link.'],
    'offline.link' => ['text' => 'l’application OMO', 'context' => 'Link back to the online application.'],
];
$bundle = omoLoadTranslationBundle('omo_offline', $sourceLang);
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(omoGetTranslationLocale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('offline.title', [], $bundle, $sourceLang), ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            color-scheme: light;
            --omo-offline-bg: #eef2ff;
            --omo-offline-surface: rgba(255, 255, 255, 0.92);
            --omo-offline-text: #111827;
            --omo-offline-muted: #4b5563;
            --omo-offline-accent: #4f46e5;
            --omo-offline-border: rgba(79, 70, 229, 0.18);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: system-ui, sans-serif;
            color: var(--omo-offline-text);
            background:
                radial-gradient(circle at top left, rgba(79, 70, 229, 0.16), transparent 34%),
                radial-gradient(circle at bottom right, rgba(15, 23, 42, 0.1), transparent 28%),
                var(--omo-offline-bg);
        }

        main {
            width: min(100%, 520px);
            padding: 32px;
            border-radius: 24px;
            background: var(--omo-offline-surface);
            border: 1px solid var(--omo-offline-border);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(28px, 5vw, 40px);
            line-height: 1.05;
        }

        p {
            margin: 0 0 14px;
            line-height: 1.6;
            color: var(--omo-offline-muted);
        }

        a {
            color: var(--omo-offline-accent);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars(t('offline.heading', [], $bundle, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars(t('offline.description', [], $bundle, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= htmlspecialchars(t('offline.reconnect', [], $bundle, $sourceLang), ENT_QUOTES, 'UTF-8') ?> <a href="/omo/"><?= htmlspecialchars(t('offline.link', [], $bundle, $sourceLang), ENT_QUOTES, 'UTF-8') ?></a>.</p>
    </main>
</body>
</html>
