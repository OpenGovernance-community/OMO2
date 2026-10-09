<?php

// Shared metadata for the additional server fields and their translation sources.
function omoServerEnvAdditionalSections(): array
{
    return [
        'hosting' => [
            'title' => 'Adresse et hebergement',
            'intro' => 'Identite publique du site et particularites du reseau. Les exceptions de proxy et d images ne sont utiles que si votre hebergement ou vos documents les demandent.',
            'technical' => true,
            'fields' => [
                'AUTH_PUBLIC_URL' => ['label' => 'Adresse publique du site', 'type' => 'url', 'preserve_empty' => true, 'help' => 'Origine HTTPS de ce site, par exemple https://exemple.org, sans chemin ni parametre. Necessaire aux e-mails de connexion et de recuperation. HTTP est accepte uniquement pour localhost et 127.0.0.1. Laissez vide pour conserver l adresse actuelle.'],
                'AUTH_TRUSTED_PROXY_IPS' => ['label' => 'Proxies de confiance', 'help' => 'Adresses IP ou plages CIDR des reverse proxies autorises, separees par des virgules. Vide si le site est expose directement.'],
                'GETIMG_ALLOWED_HOSTS' => ['label' => 'Domaines des images distantes', 'help' => 'Domaines supplementaires autorises pour les images distantes, separes par des virgules.'],
            ],
        ],
        'database' => [
            'title' => 'Base de donnees',
            'intro' => 'Acces a la base qui contient toutes les donnees du site. Deja configures a l installation ; a modifier seulement lors d une intervention sur MySQL.',
            'technical' => true,
            'fields' => [
                'DB_HOST' => ['label' => 'Serveur MySQL', 'preserve_empty' => true, 'help' => 'Nom du serveur MySQL fourni par votre hebergeur. Ce n est pas l adresse publique du site. Ne le modifier que pour deplacer ou retablir la base.'],
                'DB_NAME' => ['label' => 'Nom de la base', 'preserve_empty' => true, 'help' => 'Nom de la base contenant les donnees de cette installation. Choisir une autre base ne copie ni les donnees ni les comptes existants.'],
                'DB_USER' => ['label' => 'Utilisateur MySQL', 'preserve_empty' => true, 'help' => 'Compte MySQL autorise a lire et modifier cette base. Il est fourni par l hebergeur et est independant du compte administrateur OMO.'],
                'DB_PASS' => ['label' => 'Mot de passe MySQL', 'secret' => true, 'help' => 'Mot de passe du compte MySQL, et non du compte OMO. Laissez vide pour conserver le mot de passe actuel.'],
            ],
        ],
        'access' => [
            'title' => 'Connexions et alertes',
            'intro' => 'Protection contre les tentatives de connexion repetees et e-mails d alerte. Les valeurs proposees conviennent a une installation normale.',
            'technical' => true,
            'fields' => [
                'AUTH_RATE_LIMITS_ENABLED' => ['label' => 'Limiter les tentatives de connexion', 'type' => 'boolean', 'help' => 'Freine les tentatives repetees de connexion et de recuperation de compte. Gardez cette protection active, sauf diagnostic temporaire.'],
                'AUTH_SECURITY_ALERT_ENABLED' => ['label' => 'Alertes de securite par e-mail', 'type' => 'boolean', 'help' => 'Autorise les e-mails signalant des incidents de securite. Necessite un SMTP fonctionnel et un destinataire valide.'],
                'AUTH_SECURITY_ALERT_EMAIL' => ['label' => 'Destinataire des alertes', 'type' => 'email', 'help' => 'Adresse qui recoit les alertes de securite. Vide : OMO utilise l adresse du compte SMTP si elle est valide.'],
                'AUTH_SECURITY_ALERT_COOLDOWN_SECONDS' => ['label' => 'Intervalle entre alertes (secondes)', 'type' => 'number', 'min' => 300, 'max' => 86400, 'help' => 'Temps minimum entre deux alertes du meme type : de 300 a 86400 secondes. La valeur habituelle est 3600 secondes, soit une heure.'],
            ],
        ],
        'keys' => [
            'title' => 'Cles de protection',
            'intro' => 'Secrets generes automatiquement a l installation. Ils ne sont pas a completer au quotidien. Conservez leur sauvegarde ; remplacer une cle de chiffrement rend les anciennes donnees illisibles.',
            'technical' => true,
            'fields' => [
                'AUTH_RATE_LIMIT_SECRET' => ['label' => 'Secret des limites de connexion', 'secret' => true, 'help' => 'Secret aleatoire propre au serveur, genere a l installation. Laissez vide pour le conserver.'],
                'AUTH_TOTP_ENCRYPTION_KEY' => ['label' => 'Cle de chiffrement double authentification', 'secret' => true, 'help' => 'Generee a l installation. Conservez une sauvegarde : la remplacer rend les secrets de double authentification existants illisibles. Laissez vide pour la conserver.'],
                'EXTERNAL_CALENDAR_ENCRYPTION_KEY' => ['label' => 'Cle de chiffrement des calendriers externes', 'secret' => true, 'help' => 'Generee a l installation. La remplacer rend les identifiants de calendriers deja enregistres illisibles. Laissez vide pour la conserver.'],
            ],
        ],
        'maintenance' => [
            'title' => 'Mises a jour et taches automatiques',
            'intro' => 'Exceptions utiles pour certains hebergements et pour les appels HTTP du cron. Laissez les chemins des executables vides si la detection automatique fonctionne.',
            'technical' => true,
            'fields' => [
                'SITE_UPDATE_COMPOSER_BINARY' => ['label' => 'Executable Composer des mises a jour', 'help' => 'Chemin de Composer 2 ou de composer.phar fourni par l hebergeur, sans arguments. Vide : recherche automatique. A renseigner seulement si la mise a jour ne trouve pas Composer.'],
                'SITE_UPDATE_PHP_BINARY' => ['label' => 'Executable PHP des mises a jour', 'help' => 'PHP en ligne de commande de meme version que le site, pour ses mises a jour. Vide : detection automatique. Ne pas utiliser php-fpm ni php-cgi. Ce chemin peut differer de celui des notifications.'],
                'DB_MIGRATION_DATABASES' => ['label' => 'Bases a mettre a jour', 'help' => 'Optionnel. Noms des bases separes par des virgules pour les migrations. Vide : seule la base principale est mise a jour.'],
                'OMO_CRON_TOKEN' => ['label' => 'Secret des taches planifiees', 'secret' => true, 'help' => 'Secret prive a transmettre aux appels HTTP du cron OMO. Laissez vide pour conserver la valeur actuelle.'],
            ],
        ],
        'logs' => [
            'title' => 'Journaux et diagnostic',
            'intro' => 'Le dossier commun suffit normalement. Les chemins propres a un journal sont des exceptions facultatives. Le diagnostic SQL sert surtout a examiner des lenteurs.',
            'technical' => true,
            'fields' => [
                'RUNTIME_LOG_DIR' => ['label' => 'Dossier des journaux', 'help' => 'Dossier commun des journaux et sauvegardes techniques, hors de la racine web. Vide : dossier log situe a cote du dossier du site. Les chemins particuliers ci-dessous remplacent ce choix uniquement pour leur journal.'],
                'AUTH_SECURITY_LOG_ENABLED' => ['label' => 'Journal de securite', 'type' => 'boolean', 'help' => 'Conserve les evenements de connexion et de securite dans le dossier commun. Ce journal est distinct des e-mails d alerte.'],
                'OMO_CRON_LOG_ENABLED' => ['label' => 'Journal des taches planifiees', 'type' => 'boolean', 'help' => 'Conserve le resultat des taches automatiques du cron. Gardez actif pour pouvoir diagnostiquer un traitement manquant.'],
                'DB_QUERY_LOG_ENABLED' => ['label' => 'Journal des requetes SQL', 'type' => 'boolean', 'help' => 'Diagnostic des requetes MySQL lentes. Desactive par defaut ; a activer ponctuellement pour chercher un probleme de performance.'],
                'DB_QUERY_LOG_MIN_MS' => ['label' => 'Duree minimale des requetes (ms)', 'type' => 'number', 'min' => 0, 'help' => 'Ne journalise que les requetes depassant ce nombre de millisecondes, si le diagnostic SQL est actif. Valeur habituelle : 50 ms.'],
                'DB_QUERY_LOG_PATH' => ['label' => 'Fichier du journal SQL', 'help' => 'Exception au dossier commun pour le diagnostic SQL. Vide : fichiers quotidiens dans le sous-dossier sql-performance du dossier commun.'],
                'OMO_CRON_LOG_PATH' => ['label' => 'Fichier du journal des taches planifiees', 'help' => 'Exception au dossier commun pour le cron. Vide : fichier omo-cron/omo-cron.jsonl dans le dossier commun.'],
                'NOTIFICATION_PUSH_WORKER_LOG' => ['label' => 'Journal des notifications push', 'help' => 'Exception au dossier commun pour les erreurs d envoi push. Vide : fichier notification-push-worker.log dans le dossier commun.'],
            ],
        ],
        'ai' => [
            'title' => 'Texte et traduction',
            'intro' => 'Reponses, reformulation et resumes. La traduction peut utiliser un modele plus simple du meme fournisseur.',
            'fields' => [
                'AI_PROVIDER' => ['label' => 'Fournisseur de texte', 'type' => 'select', 'options' => ['openai' => 'OpenAI', 'anthropic' => 'Claude (Anthropic)', 'mistral' => 'Mistral', 'openai_compatible' => 'API compatible (OpenRouter, autre)', 'disabled' => 'Desactive'], 'help' => 'Service utilise pour les fonctions texte et la traduction. Choisissez API compatible pour OpenRouter ou un autre service au format Chat Completions. La cle API et les deux modeles doivent appartenir au fournisseur choisi. Changer de fournisseur demande de saisir sa cle et de choisir ses modeles. Desactive : coupe les reponses, reformulations, resumes et nouvelles traductions automatiques, tout en conservant les reglages. La transcription audio reste independante.'],
                'AI_BASE_URL' => ['label' => 'Adresse de base de l API', 'type' => 'url', 'help' => 'Uniquement pour API compatible. OpenRouter : https://openrouter.ai/api/v1. Vous pouvez saisir une autre adresse HTTPS, par exemple https://api.mistral.ai/v1 ou https://api.groq.com/openai/v1. Ne pas ajouter /chat/completions : l application le fait. Vide : OpenRouter. Utilisez la cle et les identifiants de modeles de ce service ; les fonctions qui demandent du JSON necessitent un modele compatible.'],
                'AI_API_KEY' => ['label' => 'Cle API du fournisseur', 'secret' => true, 'help' => 'Cle creee dans la console API du fournisseur choisi. Elle autorise les appels texte et traduction, factures selon votre contrat API. Un abonnement a un assistant ne fournit pas cette cle. Laissez vide pour conserver la cle configuree.'],
                'AI_MODEL' => ['label' => 'Modele principal', 'help' => 'Identifiant exact du modele disponible dans la console du fournisseur. Utilise pour les reponses, resumes et reformulations. Avec Claude, Mistral ou API compatible, un identifiant est obligatoire. Pour Mistral, par exemple mistral-small-latest. Sur OpenRouter, recopiez l identifiant complet du catalogue, avec son prefixe de fournisseur. Pour une installation historique, le modele actuel est conserve.'],
                'AI_TRANSLATION_MODEL' => ['label' => 'Modele de traduction', 'help' => 'Modele du meme fournisseur reserve aux traductions de l interface. Vous pouvez choisir un modele plus simple et moins couteux que le modele principal. Vide : modele principal, ou ancien modele de traduction si la configuration historique est encore utilisee.'],
            ],
        ],
        'transcription' => [
            'title' => 'Transcription audio',
            'intro' => 'Conversion des dictees et memos vocaux en texte. Ce service est independant du fournisseur de texte : OpenAI, Groq ou Mistral.',
            'fields' => [
                'TRANSCRIPTION_PROVIDER' => ['label' => 'Service de transcription', 'type' => 'select', 'options' => ['openai' => 'OpenAI', 'groq' => 'Groq', 'mistral' => 'Mistral', 'disabled' => 'Desactive'], 'help' => 'Active ou desactive la conversion du son en texte pour les documents et les memos Telegram. Ce service peut rester actif lorsque le texte utilise un autre fournisseur ou est desactive. Changer de service demande de saisir sa cle API.'],
                'TRANSCRIPTION_API_KEY' => ['label' => 'Cle API de transcription', 'secret' => true, 'help' => 'Cle creee dans la console du service audio choisi. Vide : conservation de la cle configuree ; a defaut, reutilisation de la cle texte uniquement si OpenAI ou Mistral est choisi pour les deux services. OpenAI peut aussi reprendre l ancienne cle OpenAI. Groq demande sa propre cle. Une cle d un autre fournisseur ne sera jamais reprise automatiquement.'],
                'TRANSCRIPTION_MODEL' => ['label' => 'Modele de transcription', 'help' => 'Modele de reconnaissance vocale du service choisi, independant des modeles de texte et de traduction. Vide : gpt-4o-mini-transcribe pour OpenAI, whisper-large-v3-turbo pour Groq, voxtral-mini-latest pour Mistral. Les anciens modeles OpenAI sont conserves uniquement pour OpenAI. Un modele personnalise reste a adapter lors d un changement de service.'],
            ],
        ],
        'push' => [
            'title' => 'Push',
            'intro' => 'Identite du serveur pour les notifications dans les navigateurs.',
            'fields' => [
                'WEB_PUSH_VAPID_PUBLIC_KEY' => ['label' => 'Cle publique VAPID', 'help' => 'Cle publique de la paire VAPID utilisee par ce serveur. Configurez les deux cles ensemble.'],
                'WEB_PUSH_VAPID_PRIVATE_KEY' => ['label' => 'Cle privee VAPID', 'secret' => true, 'help' => 'Cle privee de la meme paire VAPID. La conserver tant que des navigateurs sont abonnes. Laissez vide pour la conserver.'],
                'WEB_PUSH_VAPID_SUBJECT' => ['label' => 'Contact VAPID', 'help' => 'Contact administrateur au format mailto:admin@exemple.org ou URL HTTPS.'],
            ],
        ],
        'mcp' => [
            'title' => 'MCP',
            'intro' => 'Connexion des assistants au serveur MCP de cette installation.',
            'fields' => [
                'MCP_PUBLIC_URL' => ['label' => 'Adresse publique MCP', 'type' => 'url', 'help' => 'Adresse HTTPS de ce site suivie de /mcp/, par exemple https://exemple.org/mcp/. Laisser vide desactive le serveur MCP et l API REST associee.'],
            ],
        ],
    ];
}

function omoServerEnvAdditionalServiceFields(): array
{
    return [
        'mail' => [
            'MAIL_FROM' => ['label' => 'Adresse technique d expedition', 'type' => 'email', 'help' => 'Adresse autorisee par le fournisseur SMTP, par exemple noreply@exemple.org. Vide : deduction depuis le compte SMTP ou le domaine du site. Les reponses sont envoyees au membre expediteur.'],
            'MAIL_TIMEOUT' => ['label' => 'Delai SMTP (secondes)', 'type' => 'number', 'min' => 3],
        ],
        'maps' => [
            'NOMINATIM_SEARCH_URL' => ['label' => 'Service de recherche d adresses', 'type' => 'url', 'help' => 'URL du point d entree de recherche Nominatim. La valeur par defaut utilise le service public OpenStreetMap.'],
        ],
    ];
}
