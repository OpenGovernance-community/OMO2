<?php

// Shared metadata for the additional server fields and their translation sources.
function omoServerEnvAdditionalSections(): array
{
    return [
        'hosting' => [
            'title' => 'Adresse et hébergement',
            'intro' => 'Identité publique du site et particularites du réseau. Les exceptions de proxy et d’images ne sont utiles que si votre hébergement ou vos documents les demandent.',
            'technical' => true,
            'fields' => [
                'AUTH_PUBLIC_URL' => ['label' => 'Adresse publique du site', 'type' => 'url', 'preserve_empty' => true, 'help' => 'Origine HTTPS de ce site, par exemple https://exemple.org, sans chemin ni paramètre. Nécessaire aux e-mails de connexion et de récupération. HTTP est accepté uniquement pour localhost et 127.0.0.1. Laissez vide pour conserver l’adresse actuelle.'],
                'AUTH_TRUSTED_PROXY_IPS' => ['label' => 'Proxies de confiance', 'help' => 'Adresses IP ou plages CIDR des reverse proxies autorisés, séparées par des virgules. Vide si le site est exposé directement.'],
                'GETIMG_ALLOWED_HOSTS' => ['label' => 'Domaines des images distantes', 'help' => 'Domaines supplémentaires autorisés pour les images distantes, séparés par des virgules.'],
            ],
        ],
        'database' => [
            'title' => 'Base de données',
            'intro' => 'Accès à la base qui contient toutes les données du site. Déjà configurés à l’installation ; à modifier seulement lors d’une intervention sur MySQL.',
            'technical' => true,
            'fields' => [
                'DB_HOST' => ['label' => 'Serveur MySQL', 'preserve_empty' => true, 'help' => 'Nom du serveur MySQL fourni par votre hébergeur. Ce n’est pas l’adresse publique du site. Ne le modifier que pour déplacer ou rétablir la base.'],
                'DB_NAME' => ['label' => 'Nom de la base', 'preserve_empty' => true, 'help' => 'Nom de la base contenant les données de cette installation. Choisir une autre base ne copie ni les données ni les comptes existants.'],
                'DB_USER' => ['label' => 'Utilisateur MySQL', 'preserve_empty' => true, 'help' => 'Compte MySQL autorise à lire et modifier cette base. Il est fourni par l’hébergeur et est indépendant du compte administrateur OMO.'],
                'DB_PASS' => ['label' => 'Mot de passe MySQL', 'secret' => true, 'help' => 'Mot de passe du compte MySQL, et non du compte OMO. Laissez vide pour conserver le mot de passe actuel.'],
            ],
        ],
        'access' => [
            'title' => 'Connexions et alertes',
            'intro' => 'Protection contre les tentatives de connexion répétées et e-mails d’alerte. Les valeurs proposées conviennent à une installation normale.',
            'technical' => true,
            'fields' => [
                'AUTH_RATE_LIMITS_ENABLED' => ['label' => 'Limiter les tentatives de connexion', 'type' => 'boolean', 'help' => 'Freine les tentatives répétées de connexion et de récupération de compte. Gardez cette protection active, sauf diagnostic temporaire.'],
                'AUTH_SECURITY_ALERT_ENABLED' => ['label' => 'Alertes de sécurité par e-mail', 'type' => 'boolean', 'help' => 'Autorise les e-mails signalant des incidents de sécurité. Nécessite un SMTP fonctionnel et un destinataire valide.'],
                'AUTH_SECURITY_ALERT_EMAIL' => ['label' => 'Destinataire des alertes', 'type' => 'email', 'help' => 'Adresse qui reçoit les alertes de sécurité. Vide : OMO utilise l’adresse du compte SMTP si elle est valide.'],
                'AUTH_SECURITY_ALERT_COOLDOWN_SECONDS' => ['label' => 'Intervalle entre alertes (secondes)', 'type' => 'number', 'min' => 300, 'max' => 86400, 'help' => 'Temps minimum entre deux alertes du même type : de 300 a 86400 secondes. La valeur habituelle est 3600 secondes, soit une heure.'],
            ],
        ],
        'keys' => [
            'title' => 'Clés de protection',
            'intro' => 'Secrets générés automatiquement à l’installation. Ils ne sont pas a compléter au quotidien. Conservez leur sauvegarde ; remplacer une clé de chiffrement rend les anciennes données illisibles.',
            'technical' => true,
            'fields' => [
                'AUTH_RATE_LIMIT_SECRET' => ['label' => 'Secret des limites de connexion', 'secret' => true, 'help' => 'Secret aléatoire propre au serveur, généré à l’installation. Laissez vide pour le conserver.'],
                'AUTH_TOTP_ENCRYPTION_KEY' => ['label' => 'Clé de chiffrement double authentification', 'secret' => true, 'help' => 'Générée à l’installation. Conservez une sauvegarde : la remplacer rend les secrets de double authentification existants illisibles. Laissez vide pour la conserver.'],
                'EXTERNAL_CALENDAR_ENCRYPTION_KEY' => ['label' => 'Clé de chiffrement des calendriers externes', 'secret' => true, 'help' => 'Générée à l’installation. La remplacer rend les identifiants de calendriers déjà enregistrés illisibles. Laissez vide pour la conserver.'],
            ],
        ],
        'maintenance' => [
            'title' => 'Mises à jour et tâches automatiques',
            'intro' => 'Exceptions utiles pour certains hébergements et pour les appels HTTP du cron. Laissez les chemins des exécutables vides si la détection automatique fonctionne.',
            'technical' => true,
            'fields' => [
                'SITE_UPDATE_COMPOSER_BINARY' => ['label' => 'Exécutable Composer des mises à jour', 'help' => 'Chemin de Composer 2 ou de composer.phar fourni par l’hébergeur, sans arguments. Vide : recherche automatique. A renseigner seulement si la mise à jour ne trouve pas Composer.'],
                'SITE_UPDATE_PHP_BINARY' => ['label' => 'Exécutable PHP des mises à jour', 'help' => 'PHP en ligne de commande de même version que le site, pour ses mises à jour. Vide : détection automatique. Ne pas utiliser php-fpm ni php-cgi. Ce chemin peut differer de celui des notifications.'],
                'DB_MIGRATION_DATABASES' => ['label' => 'Bases à mettre à jour', 'help' => 'Optionnel. Noms des bases séparés par des virgules pour les migrations. Vide : seule la base principale est mise à jour.'],
                'OMO_CRON_TOKEN' => ['label' => 'Secret des tâches planifiées', 'secret' => true, 'help' => 'Secret prive à transmettre aux appels HTTP du cron OMO. Laissez vide pour conserver la valeur actuelle.'],
            ],
        ],
        'logs' => [
            'title' => 'Journaux et diagnostic',
            'intro' => 'Le dossier commun suffit normalement. Les chemins propres à un journal sont des exceptions facultatives. Le diagnostic SQL sert surtout à examiner des lenteurs.',
            'technical' => true,
            'fields' => [
                'RUNTIME_LOG_DIR' => ['label' => 'Dossier des journaux', 'help' => 'Dossier commun des journaux et sauvegardes techniques, hors de la racine web. Vide : dossier log situé a cote du dossier du site. Les chemins particuliers ci-dessous remplacent ce choix uniquement pour leur journal.'],
                'AUTH_SECURITY_LOG_ENABLED' => ['label' => 'Journal de sécurité', 'type' => 'boolean', 'help' => 'Conserve les événements de connexion et de sécurité dans le dossier commun. Ce journal est distinct des e-mails d’alerte.'],
                'OMO_CRON_LOG_ENABLED' => ['label' => 'Journal des tâches planifiées', 'type' => 'boolean', 'help' => 'Conserve le résultat des tâches automatiques du cron. Gardez actif pour pouvoir diagnostiquer un traitement manquant.'],
                'DB_QUERY_LOG_ENABLED' => ['label' => 'Journal des requêtes SQL', 'type' => 'boolean', 'help' => 'Diagnostic des requêtes MySQL lentes. Desactive par défaut ; a activer ponctuellement pour chercher un problème de performance.'],
                'DB_QUERY_LOG_MIN_MS' => ['label' => 'Durée minimale des requêtes (ms)', 'type' => 'number', 'min' => 0, 'help' => 'Ne journalise que les requêtes depassant ce nombre de millisecondes, si le diagnostic SQL est actif. Valeur habituelle : 50 ms.'],
                'DB_QUERY_LOG_PATH' => ['label' => 'Fichier du journal SQL', 'help' => 'Chemin complet d’un fichier, pas d’un dossier. Vide ou dossier : fichiers quotidiens dans le sous-dossier sql-performance du dossier commun.'],
                'OMO_CRON_LOG_PATH' => ['label' => 'Fichier du journal des tâches planifiées', 'help' => 'Exception au dossier commun pour le cron. Vide : fichier omo-cron/omo-cron.jsonl dans le dossier commun.'],
                'NOTIFICATION_PUSH_WORKER_LOG' => ['label' => 'Journal des notifications push', 'help' => 'Exception au dossier commun pour les erreurs d’envoi push. Vide : fichier notification-push-worker.log dans le dossier commun.'],
            ],
        ],
        'ai' => [
            'title' => 'Texte et traduction',
            'intro' => 'Réponses, reformulation et résumés. La traduction peut utiliser un modèle plus simple du même fournisseur.',
            'fields' => [
                'AI_PROVIDER' => ['label' => 'Fournisseur de texte', 'type' => 'select', 'options' => ['openai' => 'OpenAI', 'anthropic' => 'Claude (Anthropic)', 'mistral' => 'Mistral', 'openai_compatible' => 'API compatible (OpenRouter, autre)', 'disabled' => 'Desactive'], 'help' => 'Service utilisé pour les fonctions texte et la traduction. Choisissez API compatible pour OpenRouter ou un autre service au format Chat Completions. La clé API et les deux modèles doivent appartenir au fournisseur choisi. Changer de fournisseur demande de saisir sa clé et de choisir ses modèles. Désactivé : coupe les réponses, reformulations, résumés et nouvelles traductions automatiques, tout en conservant les réglages. La transcription audio reste indépendante.'],
                'AI_BASE_URL' => ['label' => 'Adresse de base de l’API', 'type' => 'url', 'help' => 'Uniquement pour API compatible. OpenRouter : https://openrouter.ai/api/v1. Vous pouvez saisir une autre adresse HTTPS, par exemple https://api.mistral.ai/v1 ou https://api.groq.com/openai/v1. Ne pas ajouter /chat/completions : l’application le fait. Vide : OpenRouter. Utilisez la clé et les identifiants de modèles de ce service ; les fonctions qui demandent du JSON nécessitent un modèle compatible.'],
                'AI_API_KEY' => ['label' => 'Clé API du fournisseur', 'secret' => true, 'help' => 'Clé créée dans la console API du fournisseur choisi. Elle autorise les appels texte et traduction, facturés selon votre contrat API. Un abonnement à un assistant ne fournit pas cette clé. Laissez vide pour conserver la clé configurée.'],
                'AI_MODEL' => ['label' => 'Modèle principal', 'help' => 'Identifiant exact du modèle disponible dans la console du fournisseur. Utilise pour les réponses, résumés et reformulations. Avec Claude, Mistral ou API compatible, un identifiant est obligatoire. Pour Mistral, par exemple mistral-small-latest. Sur OpenRouter, recopiez l’identifiant complet du catalogue, avec son prefixe de fournisseur. Pour une installation historique, le modèle actuel est conservé.'],
                'AI_TRANSLATION_MODEL' => ['label' => 'Modèle de traduction', 'help' => 'Modèle du même fournisseur reserve aux traductions de l’interface. Vous pouvez choisir un modèle plus simple et moins couteux que le modèle principal. Vide : modèle principal, ou ancien modèle de traduction si la configuration historique est encore utilisée.'],
            ],
        ],
        'transcription' => [
            'title' => 'Transcription audio',
            'intro' => 'Conversion des dictées et mémos vocaux en texte. Ce service est indépendant du fournisseur de texte : OpenAI, Groq ou Mistral.',
            'fields' => [
                'TRANSCRIPTION_PROVIDER' => ['label' => 'Service de transcription', 'type' => 'select', 'options' => ['openai' => 'OpenAI', 'groq' => 'Groq', 'mistral' => 'Mistral', 'disabled' => 'Desactive'], 'help' => 'Active ou désactive la conversion du son en texte pour les documents et les mémos Telegram. Ce service peut rester actif lorsque le texte utilise un autre fournisseur ou est désactivé. Changer de service demande de saisir sa clé API.'],
                'TRANSCRIPTION_API_KEY' => ['label' => 'Clé API de transcription', 'secret' => true, 'help' => 'Clé créée dans la console du service audio choisi. Vide : conservation de la clé configurée ; a défaut, reutilisation de la clé texte uniquement si OpenAI ou Mistral est choisi pour les deux services. OpenAI peut aussi reprendre l’ancienne clé OpenAI. Groq demande sa propre clé. Une clé d’un autre fournisseur ne sera jamais reprise automatiquement.'],
                'TRANSCRIPTION_MODEL' => ['label' => 'Modèle de transcription', 'help' => 'Modèle de reconnaissance vocale du service choisi, indépendant des modèles de texte et de traduction. Vide : gpt-4o-mini-transcribe pour OpenAI, whisper-large-v3-turbo pour Groq, voxtral-mini-latest pour Mistral. Les anciens modèles OpenAI sont conservés uniquement pour OpenAI. Un modèle personnalise reste a adapter lors d’un changement de service.'],
            ],
        ],
        'push' => [
            'title' => 'Push',
            'intro' => 'Identité du serveur pour les notifications dans les navigateurs.',
            'fields' => [
                'WEB_PUSH_VAPID_PUBLIC_KEY' => ['label' => 'Clé publique VAPID', 'help' => 'Clé publique de la paire VAPID utilisée par ce serveur. Configurez les deux clés ensemble.'],
                'WEB_PUSH_VAPID_PRIVATE_KEY' => ['label' => 'Clé privée VAPID', 'secret' => true, 'help' => 'Clé privée de la même paire VAPID. La conserver tant que des navigateurs sont abonnes. Laissez vide pour la conserver.'],
                'WEB_PUSH_VAPID_SUBJECT' => ['label' => 'Contact VAPID', 'help' => 'Contact administrateur au format mailto:admin@exemple.org ou URL HTTPS.'],
            ],
        ],
        'mcp' => [
            'title' => 'MCP',
            'intro' => 'Connexion des assistants au serveur MCP de cette installation.',
            'fields' => [
                'MCP_PUBLIC_URL' => ['label' => 'Adresse publique MCP', 'type' => 'url', 'help' => 'Adresse HTTPS de ce site suivie de /mcp/, par exemple https://exemple.org/mcp/. Laisser vide desactive le serveur MCP et l’API REST associée.'],
            ],
        ],
    ];
}

function omoServerEnvAdditionalServiceFields(): array
{
    return [
        'mail' => [
            'MAIL_FROM' => ['label' => 'Adresse technique d’expédition', 'type' => 'email', 'help' => 'Adresse autorisée par le fournisseur SMTP, par exemple noreply@exemple.org. Vide : deduction depuis le compte SMTP ou le domaine du site. Les réponses sont envoyées au membre expediteur.'],
            'MAIL_TIMEOUT' => ['label' => 'Délai SMTP (secondes)', 'type' => 'number', 'min' => 3],
        ],
        'maps' => [
            'NOMINATIM_SEARCH_URL' => ['label' => 'Service de recherche d’adresses', 'type' => 'url', 'help' => 'URL du point d’entrée de recherche Nominatim. La valeur par défaut utilise le service public OpenStreetMap.'],
        ],
    ];
}
