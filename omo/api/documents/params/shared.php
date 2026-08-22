<?php

require_once dirname(__DIR__, 4) . '/common/collabora.php';

if (!function_exists('omoDocumentsParamsSourceLang')) {
    function omoDocumentsParamsSourceLang()
    {
        static $sourceLang = null;

        if (is_array($sourceLang)) {
            return $sourceLang;
        }

        $sourceLang = [
            'documents.params.title' => ['text' => 'Paramètres Documents', 'context' => 'Title of the Documents application settings screen.'],
            'documents.params.description' => ['text' => 'Configurez ici le stockage distant utilisé par l’application Documents pour cette organisation.', 'context' => 'Intro text shown in the Documents application settings screen.'],
            'documents.params.field.storage_enabled' => ['text' => 'Activer le stockage de documents', 'context' => 'Checkbox enabling remote document storage for the organization.'],
            'documents.params.field.storage_enabled_hint' => ['text' => 'Les fichiers téléversés et les documents bureautiques collaboratifs seront conservés sur le serveur choisi ci-dessous.', 'context' => 'Explanation shown below the document storage checkbox.'],
            'documents.params.field.storage_type' => ['text' => 'Serveur de stockage', 'context' => 'Label for the remote document storage provider choice.'],
            'documents.params.field.storage_type_nextcloud' => ['text' => 'Nextcloud', 'context' => 'Radio label for Nextcloud document storage.'],
            'documents.params.field.storage_type_kdrive' => ['text' => 'kDrive (Infomaniak)', 'context' => 'Radio label for Infomaniak kDrive document storage.'],
            'documents.params.status.storage_ready' => ['text' => 'Stockage de documents configuré.', 'context' => 'Status shown when a document storage provider is configured.'],
            'documents.params.status.storage_empty' => ['text' => 'Aucun stockage de documents configuré. Les fichiers téléversés et les documents coopératifs ne seront pas proposés.', 'context' => 'Status shown when no document storage provider is configured.'],
            'documents.params.section.storage' => ['text' => 'Stockage des documents', 'context' => 'Heading of the remote document storage settings section.'],
            'documents.params.field.kdrive_id' => ['text' => 'Identifiant du kDrive', 'context' => 'Label of the Infomaniak kDrive identifier field.'],
            'documents.params.field.kdrive_id_hint' => ['text' => 'Vous le trouverez dans l’adresse du kDrive, après /drive/.', 'context' => 'Hint explaining where to find the Infomaniak kDrive identifier.'],
            'documents.params.field.kdrive_username' => ['text' => 'Adresse e-mail Infomaniak', 'context' => 'Label of the Infomaniak WebDAV username field.'],
            'documents.params.field.kdrive_password' => ['text' => 'Mot de passe d’application', 'context' => 'Label of the Infomaniak application password field.'],
            'documents.params.field.kdrive_password_hint' => ['text' => 'Utilisez un mot de passe d’application dédié à cette organisation.', 'context' => 'Hint for the Infomaniak application password field.'],
            'documents.params.field.kdrive_password_placeholder_new' => ['text' => 'Mot de passe d’application kDrive', 'context' => 'Placeholder shown for a fresh kDrive password field.'],
            'documents.params.field.kdrive_password_placeholder_keep' => ['text' => 'Laisser vide pour conserver le mot de passe actuel', 'context' => 'Placeholder shown when a kDrive password already exists.'],
            'documents.params.field.kdrive_folder_hint' => ['text' => 'Optionnel. OMO créera ensuite son dossier omo-documents dans ce dossier.', 'context' => 'Hint below the Infomaniak kDrive folder field.'],
            'documents.params.field.kdrive_subscription_hint' => ['text' => 'Attention : cette connexion utilise WebDAV et nécessite une offre kSuite Business ou supérieure. WebDAV n’est pas disponible avec kSuite Standard.', 'context' => 'Warning shown when configuring kDrive storage.'],
            'documents.params.action.test_storage' => ['text' => 'Tester la connexion', 'context' => 'Button used to test the selected document storage settings.'],
            'documents.params.action.testing_storage' => ['text' => 'Test en cours...', 'context' => 'Button label shown while testing document storage.'],
            'documents.params.feedback.storage_test_invalid' => ['text' => 'Impossible de tester la connexion au stockage.', 'context' => 'Fallback error shown when the document storage test fails unexpectedly.'],
            'documents.params.feedback.storage_invalid' => ['text' => 'Renseignez les informations complètes du serveur de stockage choisi.', 'context' => 'Error returned when document storage settings are incomplete.'],
            'documents.params.feedback.storage_change_warning' => ['text' => 'Ce changement fera perdre les fichiers associés aux documents existants. Confirmez pour supprimer ces fichiers de l’ancien stockage et enregistrer le nouveau serveur.', 'context' => 'Confirmation message shown before replacing a document storage server.'],
            'documents.params.feedback.storage_cleanup_failed' => ['text' => 'Le nouveau stockage n’a pas été enregistré : certains fichiers de l’ancien stockage n’ont pas pu être supprimés.', 'context' => 'Error shown when old remote files cannot be removed during a storage change.'],
            'documents.params.status.ready' => ['text' => 'Connexion Nextcloud configurée pour les documents téléversés.', 'context' => 'Status text shown when Documents Nextcloud storage is configured.'],
            'documents.params.status.empty' => ['text' => 'Aucune connexion Nextcloud configurée. Le type de document téléversé ne sera pas proposé.', 'context' => 'Status text shown when no Documents Nextcloud storage is configured.'],
            'documents.params.status.legacy' => ['text' => 'Une ancienne configuration a été détectée. Un enregistrement ici la migrera vers l’application Documents.', 'context' => 'Hint shown when a legacy organization-level Nextcloud configuration is still used.'],
            'documents.params.field.base_url' => ['text' => 'URL du serveur Nextcloud', 'context' => 'Label of the Nextcloud base URL field.'],
            'documents.params.field.base_url_hint' => ['text' => 'URL de base du serveur Nextcloud. Le WebDAV utilise ensuite automatiquement remote.php/dav/files/... .', 'context' => 'Hint shown below the Nextcloud base URL field.'],
            'documents.params.field.username' => ['text' => 'Utilisateur Nextcloud', 'context' => 'Label of the Nextcloud username field.'],
            'documents.params.field.password' => ['text' => 'Mot de passe applicatif', 'context' => 'Label of the Nextcloud app password field.'],
            'documents.params.field.password_hint' => ['text' => 'Utilisez de préférence un mot de passe applicatif Nextcloud plutôt que le mot de passe principal.', 'context' => 'Hint shown below the Nextcloud app password field.'],
            'documents.params.field.password_placeholder_new' => ['text' => 'Mot de passe applicatif Nextcloud', 'context' => 'Placeholder shown for a fresh Nextcloud password field.'],
            'documents.params.field.password_placeholder_keep' => ['text' => 'Laisser vide pour conserver le mot de passe actuel', 'context' => 'Placeholder shown when a Nextcloud password already exists.'],
            'documents.params.field.folder' => ['text' => 'Dossier distant', 'context' => 'Label of the remote Nextcloud folder field.'],
            'documents.params.action.test_nextcloud' => ['text' => 'Tester la connexion', 'context' => 'Button used to test the current Nextcloud settings without saving them.'],
            'documents.params.action.testing_nextcloud' => ['text' => 'Test en cours...', 'context' => 'Button label shown while testing the current Nextcloud settings.'],
            'documents.params.feedback.nextcloud_test_invalid' => ['text' => 'Impossible de tester la connexion Nextcloud.', 'context' => 'Fallback error shown when the Nextcloud test fails unexpectedly.'],
            'documents.params.field.folder_hint' => ['text' => 'Optionnel. Si vide, OMO utilisera directement un dossier omo-documents à la racine du compte.', 'context' => 'Hint shown below the remote Nextcloud folder field.'],
            'documents.params.field.default_visibility' => ['text' => 'Visibilite par defaut', 'context' => 'Label of the default document visibility selector in Documents settings.'],
            'documents.params.section.nextcloud' => ['text' => 'Stockage Nextcloud', 'context' => 'Heading of the Nextcloud settings section.'],
            'documents.params.section.collabora' => ['text' => 'Documents Collabora', 'context' => 'Heading of the Collabora settings section.'],
            'documents.params.field.collabora_enabled' => ['text' => 'Ajouter un serveur Collabora', 'context' => 'Checkbox enabling Collabora documents for the organization.'],
            'documents.params.field.collabora_enabled_hint' => ['text' => 'Permet de créer et modifier des documents bureautiques collaboratifs stockés sur le serveur de documents choisi.', 'context' => 'Explanation shown below the Collabora enable checkbox.'],
            'documents.params.field.collabora_base_url' => ['text' => 'URL publique du serveur Collabora', 'context' => 'Public Collabora URL field.'],
            'documents.params.field.collabora_base_url_hint' => ['text' => 'Adresse HTTPS ouverte dans le navigateur, avec le proxy WebSocket actif.', 'context' => 'Hint below the public Collabora URL field.'],
            'documents.params.field.collabora_internal_url' => ['text' => 'URL interne de Collabora', 'context' => 'Optional internal Collabora URL field.'],
            'documents.params.field.collabora_internal_url_hint' => ['text' => 'Optionnel. Utilisee par OMO pour charger la decouverte quand Collabora est sur le meme reseau prive.', 'context' => 'Hint below the internal Collabora URL field.'],
            'documents.params.field.collabora_appearance_hint' => ['text' => 'L editeur utilise les couleurs de cette organisation, le mode avec onglets et le theme clair ou sombre actif pour chaque utilisateur.', 'context' => 'Explanation of the Collabora appearance automatically applied by OMO.'],
            'documents.params.status.collabora_ready' => ['text' => 'Connexion Collabora configuree pour les documents bureautiques collaboratifs.', 'context' => 'Status shown when Collabora is configured.'],
            'documents.params.status.collabora_empty' => ['text' => 'Aucun serveur Collabora configure. Le document cooperatif ne sera pas propose.', 'context' => 'Status shown when Collabora is not configured.'],
            'documents.params.status.collabora_requires_nextcloud' => ['text' => 'Configurez d abord un stockage Nextcloud pour activer Collabora.', 'context' => 'Status shown when Collabora cannot be enabled without Nextcloud.'],
            'documents.params.field.collabora_clear' => ['text' => 'Supprimer cette configuration Collabora', 'context' => 'Checkbox used to clear Collabora configuration.'],
            'documents.params.feedback.collabora_invalid' => ['text' => 'L URL publique de Collabora doit etre une adresse http ou https valide.', 'context' => 'Error shown for invalid Collabora URL.'],
            'documents.params.feedback.collabora_incomplete' => ['text' => 'Une URL publique Collabora est necessaire pour activer cette integration.', 'context' => 'Error shown when Collabora configuration is incomplete.'],
            'documents.params.status.collabora_requires_storage' => ['text' => 'Configurez d’abord un stockage de documents pour activer Collabora.', 'context' => 'Status shown when Collabora cannot be enabled without document storage.'],
            'documents.params.feedback.collabora_cleared' => ['text' => 'Configuration Collabora supprimee.', 'context' => 'Success message returned after clearing Collabora configuration.'],
            'documents.params.feedback.collabora_server_in_use' => ['text' => 'Le serveur Collabora ne peut pas etre change tant que cette organisation possede des documents cooperatifs.', 'context' => 'Error shown when changing Collabora configuration with existing documents.'],
            'documents.params.feedback.collabora_config_in_use' => ['text' => 'Cette configuration Collabora ne peut pas etre supprimee tant que l organisation possede des documents cooperatifs.', 'context' => 'Error shown when clearing Collabora configuration with existing documents.'],
            'documents.params.field.default_edit_visibility' => ['text' => 'Edition par defaut', 'context' => 'Label of the default document edit visibility selector in Documents settings.'],
            'documents.params.field.default_visibility_hint' => ['text' => 'Valeur initiale proposee lors de la creation d un document.', 'context' => 'Hint shown below the default visibility selector in Documents settings.'],
            'documents.params.field.default_edit_visibility_hint' => ['text' => 'Definit qui peut modifier un nouveau document par defaut.', 'context' => 'Hint shown below the default edit visibility selector in Documents settings.'],
            'documents.params.field.clear' => ['text' => 'Supprimer cette configuration Nextcloud', 'context' => 'Checkbox label used to clear the Nextcloud configuration.'],
            'documents.params.action.save' => ['text' => 'Enregistrer', 'context' => 'Primary action used to save the Documents application settings screen.'],
            'documents.params.action.saving' => ['text' => 'Enregistrement...', 'context' => 'Primary action label shown while the Documents application settings are being saved.'],
            'documents.params.feedback.saved' => ['text' => 'Paramètres Documents enregistrés.', 'context' => 'Success message returned after saving the Documents application settings.'],
            'documents.params.feedback.cleared' => ['text' => 'Configuration Nextcloud supprimée.', 'context' => 'Success message returned after clearing the Documents Nextcloud configuration.'],
            'documents.params.feedback.invalid' => ['text' => 'Merci de renseigner au minimum l’URL, l’utilisateur et le mot de passe applicatif Nextcloud.', 'context' => 'Error returned when the Nextcloud configuration is incomplete.'],
            'documents.params.error.login' => ['text' => 'Connectez-vous pour accéder à ce panneau.', 'context' => 'Error shown when the Documents application settings screen is opened while logged out.'],
            'documents.params.error.organization' => ['text' => 'Aucune organisation active.', 'context' => 'Error shown when the Documents application settings screen cannot resolve an organization.'],
            'documents.params.error.forbidden' => ['text' => 'Activez le mode admin de cette organisation pour modifier les paramètres Documents.', 'context' => 'Error shown when the current user is not allowed to manage Documents application settings.'],
            'documents.params.error.unavailable' => ['text' => 'L’application Documents n’est pas disponible pour cette organisation.', 'context' => 'Error shown when the Documents application is not installed for this organization.'],
            'documents.params.error.save_failed' => ['text' => 'Impossible d’enregistrer les paramètres Documents pour le moment.', 'context' => 'Error returned when saving the Documents application settings failed.'],
            'documents.params.error.method' => ['text' => 'Méthode non autorisée.', 'context' => 'Error returned when the save endpoint receives an unsupported HTTP method.'],
        ];

        return $sourceLang;
    }
}

if (!function_exists('omoDocumentsParamsLang')) {
    function omoDocumentsParamsLang()
    {
        static $lang = null;

        if ($lang === null) {
            if (function_exists('omoLoadTranslationBundle')) {
                $lang = omoLoadTranslationBundle('omo_documents_params', omoDocumentsParamsSourceLang());
            } else {
                $lang = [];
            }
        }

        return $lang;
    }
}

if (!function_exists('omoDocumentsParamsT')) {
    function omoDocumentsParamsT($key, array $replace = [])
    {
        if (function_exists('t')) {
            return t($key, $replace, omoDocumentsParamsLang(), omoDocumentsParamsSourceLang());
        }

        $sourceLang = omoDocumentsParamsSourceLang();
        $text = (string)($sourceLang[$key]['text'] ?? $key);
        foreach ($replace as $replaceKey => $replaceValue) {
            $text = str_replace('{' . $replaceKey . '}', (string)$replaceValue, $text);
        }

        return $text;
    }
}

if (!function_exists('omoDocumentsParamsCanManage')) {
    function omoDocumentsParamsCanManage(int $organizationId, ?int $userId = null): bool
    {
        $organizationId = (int)$organizationId;
        $userId = $userId !== null
            ? (int)$userId
            : (function_exists('commonGetCurrentUserId')
                ? (int)commonGetCurrentUserId()
                : (int)($_SESSION['currentUser'] ?? 0));

        if ($organizationId <= 0 || $userId <= 0) {
            return false;
        }

        if (function_exists('commonUserHasOrganizationAccess') && !commonUserHasOrganizationAccess($userId, $organizationId)) {
            return false;
        }

        if (
            function_exists('commonUserHasSiteAdminOverride')
            && commonUserHasSiteAdminOverride($userId)
        ) {
            return true;
        }

        return $userId === (int)commonGetCurrentUserId()
            && function_exists('commonCurrentUserCanUseAdminMode')
            && commonCurrentUserCanUseAdminMode($organizationId);
    }
}

if (!function_exists('omoDocumentsParamsGetApplicationLink')) {
    function omoDocumentsParamsGetApplicationLink(int $organizationId, bool $createIfMissing = false): ?\dbObject\OrganizationApplication
    {
        if ($organizationId <= 0) {
            return null;
        }

        return $createIfMissing
            ? \dbObject\OrganizationApplication::ensureByOrganizationAndDirectory($organizationId, 'documents')
            : \dbObject\OrganizationApplication::loadByOrganizationAndDirectory($organizationId, 'documents', true);
    }
}

if (!function_exists('omoDocumentsParamsNormalizeNextcloudBaseUrl')) {
    function omoDocumentsParamsNormalizeNextcloudBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            return '';
        }

        $baseUrl = rtrim($baseUrl, '/');

        $parsedUrl = parse_url($baseUrl);
        if (!is_array($parsedUrl) || empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
            return $baseUrl;
        }

        $path = isset($parsedUrl['path']) ? (string)$parsedUrl['path'] : '';
        $path = preg_replace('#/(?:remote\.php(?:/(?:dav(?:/files(?:/[^/]+)?)?|webdav)?)?)$#i', '', rtrim($path, '/'));
        $path = $path === '/' ? '' : $path;

        $normalizedBaseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['port']) && (int)$parsedUrl['port'] > 0) {
            $normalizedBaseUrl .= ':' . (int)$parsedUrl['port'];
        }

        if ($path !== '') {
            $normalizedBaseUrl .= $path[0] === '/' ? $path : ('/' . $path);
        }

        return rtrim($normalizedBaseUrl, '/');
    }
}

if (!function_exists('omoDocumentsParamsExtractNextcloudPayload')) {
    function omoDocumentsParamsExtractNextcloudPayload(array $parameters): array
    {
        if (isset($parameters['nextcloud']) && is_array($parameters['nextcloud'])) {
            return $parameters['nextcloud'];
        }

        $flatConfigKeys = array('baseUrl', 'username', 'appPassword', 'folder');
        $flatConfig = array();
        $hasFlatConfig = false;
        foreach ($flatConfigKeys as $flatConfigKey) {
            if (array_key_exists($flatConfigKey, $parameters)) {
                $flatConfig[$flatConfigKey] = $parameters[$flatConfigKey];
                $hasFlatConfig = true;
            }
        }

        return $hasFlatConfig ? $flatConfig : array();
    }
}

if (!function_exists('omoDocumentsParamsNormalizeNextcloudConfig')) {
    function omoDocumentsParamsNormalizeNextcloudConfig(array $config): array
    {
        $baseUrl = omoDocumentsParamsNormalizeNextcloudBaseUrl((string)($config['baseUrl'] ?? ''));
        $username = trim((string)($config['username'] ?? ''));
        $appPassword = trim((string)($config['appPassword'] ?? ''));
        $folder = trim((string)($config['folder'] ?? ''));
        $folder = trim(str_replace('\\', '/', $folder), '/');

        return array(
            'baseUrl' => $baseUrl,
            'username' => $username,
            'appPassword' => $appPassword,
            'folder' => $folder,
        );
    }
}

if (!function_exists('omoDocumentsParamsHasNextcloudConfig')) {
    function omoDocumentsParamsHasNextcloudConfig(array $config): bool
    {
        $config = omoDocumentsParamsNormalizeNextcloudConfig($config);
        return $config['baseUrl'] !== ''
            && $config['username'] !== ''
            && $config['appPassword'] !== '';
    }
}

if (!function_exists('omoDocumentsParamsUsesLegacyNextcloudConfig')) {
    function omoDocumentsParamsUsesLegacyNextcloudConfig(\dbObject\Organization $organization, ?\dbObject\OrganizationApplication $organizationApplication = null): bool
    {
        $organizationParameters = $organization->getParametersArray();
        $legacyConfig = isset($organizationParameters['nextcloudDocuments']) && is_array($organizationParameters['nextcloudDocuments'])
            ? $organizationParameters['nextcloudDocuments']
            : array();
        if (!omoDocumentsParamsHasNextcloudConfig($legacyConfig)) {
            return false;
        }

        $appParameters = $organizationApplication
            ? $organizationApplication->getParametersArray()
            : $organization->getApplicationParametersByDirectory('documents');

        if (isset($appParameters['storage']) && is_array($appParameters['storage']) && omoDocumentsParamsNormalizeStorageType($appParameters['storage']['type'] ?? '') !== '') {
            return false;
        }

        $appConfig = omoDocumentsParamsExtractNextcloudPayload($appParameters);

        return !omoDocumentsParamsHasNextcloudConfig($appConfig);
    }
}

if (!function_exists('omoDocumentsParamsGetNextcloudConfig')) {
    function omoDocumentsParamsGetNextcloudConfig(\dbObject\Organization $organization, ?\dbObject\OrganizationApplication $organizationApplication = null, bool $fallbackToLegacy = true): array
    {
        $appParameters = $organizationApplication
            ? $organizationApplication->getParametersArray()
            : $organization->getApplicationParametersByDirectory('documents');

        if (isset($appParameters['storage']) && is_array($appParameters['storage'])) {
            $storageType = omoDocumentsParamsNormalizeStorageType($appParameters['storage']['type'] ?? '');
            if ($storageType === 'nextcloud') {
                return omoDocumentsParamsNormalizeNextcloudConfig($appParameters['storage']);
            }
            if ($storageType === 'kdrive') {
                return omoDocumentsParamsNormalizeNextcloudConfig(array());
            }
        }

        $appConfig = omoDocumentsParamsExtractNextcloudPayload($appParameters);
        $normalizedAppConfig = omoDocumentsParamsNormalizeNextcloudConfig($appConfig);

        if (omoDocumentsParamsHasNextcloudConfig($normalizedAppConfig) || !$fallbackToLegacy) {
            return $normalizedAppConfig;
        }

        $organizationParameters = $organization->getParametersArray();
        $legacyConfig = isset($organizationParameters['nextcloudDocuments']) && is_array($organizationParameters['nextcloudDocuments'])
            ? $organizationParameters['nextcloudDocuments']
            : array();

        return omoDocumentsParamsNormalizeNextcloudConfig($legacyConfig);
    }
}

if (!function_exists('omoDocumentsParamsClearLegacyNextcloudConfig')) {
    function omoDocumentsParamsClearLegacyNextcloudConfig(\dbObject\Organization $organization): void
    {
        $parameters = $organization->getParametersArray();
        if (!array_key_exists('nextcloudDocuments', $parameters)) {
            return;
        }

        unset($parameters['nextcloudDocuments']);
        $organization->setParametersArray($parameters);
        $organization->save();
    }
}

if (!function_exists('omoDocumentsParamsNormalizeStorageType')) {
    function omoDocumentsParamsNormalizeStorageType($value): string
    {
        $value = strtolower(trim((string)$value));
        return in_array($value, array('nextcloud', 'kdrive'), true) ? $value : '';
    }
}

if (!function_exists('omoDocumentsParamsNormalizeKdriveConfig')) {
    function omoDocumentsParamsNormalizeKdriveConfig(array $config): array
    {
        $driveId = trim((string)($config['driveId'] ?? $config['id'] ?? ''));
        $driveId = preg_replace('/[^0-9]/', '', $driveId);
        $username = trim((string)($config['username'] ?? ''));
        $appPassword = trim((string)($config['appPassword'] ?? ''));
        $folder = trim(str_replace('\\', '/', (string)($config['folder'] ?? '')), '/');

        return array(
            'type' => 'kdrive',
            'driveId' => $driveId,
            'username' => $username,
            'appPassword' => $appPassword,
            'folder' => $folder,
        );
    }
}

if (!function_exists('omoDocumentsParamsHasKdriveConfig')) {
    function omoDocumentsParamsHasKdriveConfig(array $config): bool
    {
        $config = omoDocumentsParamsNormalizeKdriveConfig($config);
        return $config['driveId'] !== ''
            && $config['username'] !== ''
            && $config['appPassword'] !== '';
    }
}

if (!function_exists('omoDocumentsParamsNormalizeDocumentStorageConfig')) {
    function omoDocumentsParamsNormalizeDocumentStorageConfig(array $config, string $type = ''): array
    {
        $type = omoDocumentsParamsNormalizeStorageType($type !== '' ? $type : ($config['type'] ?? ''));
        if ($type === 'kdrive') {
            return omoDocumentsParamsNormalizeKdriveConfig($config);
        }

        if ($type === 'nextcloud') {
            return array_merge(
                array('type' => 'nextcloud'),
                omoDocumentsParamsNormalizeNextcloudConfig($config)
            );
        }

        return array(
            'type' => '',
            'baseUrl' => '',
            'driveId' => '',
            'username' => '',
            'appPassword' => '',
            'folder' => '',
        );
    }
}

if (!function_exists('omoDocumentsParamsHasDocumentStorageConfig')) {
    function omoDocumentsParamsHasDocumentStorageConfig(array $config): bool
    {
        $config = omoDocumentsParamsNormalizeDocumentStorageConfig($config);
        if ($config['type'] === 'kdrive') {
            return omoDocumentsParamsHasKdriveConfig($config);
        }

        return $config['type'] === 'nextcloud' && omoDocumentsParamsHasNextcloudConfig($config);
    }
}

if (!function_exists('omoDocumentsParamsGetDocumentStorageConfig')) {
    function omoDocumentsParamsGetDocumentStorageConfig(\dbObject\Organization $organization, ?\dbObject\OrganizationApplication $organizationApplication = null): array
    {
        $appParameters = $organizationApplication
            ? $organizationApplication->getParametersArray()
            : $organization->getApplicationParametersByDirectory('documents');

        if (isset($appParameters['storage']) && is_array($appParameters['storage'])) {
            $storedType = omoDocumentsParamsNormalizeStorageType($appParameters['storage']['type'] ?? '');
            if ($storedType !== '') {
                return omoDocumentsParamsNormalizeDocumentStorageConfig($appParameters['storage'], $storedType);
            }
        }

        $legacyNextcloud = omoDocumentsParamsGetNextcloudConfig($organization, $organizationApplication, true);
        if (omoDocumentsParamsHasNextcloudConfig($legacyNextcloud)) {
            return omoDocumentsParamsNormalizeDocumentStorageConfig($legacyNextcloud, 'nextcloud');
        }

        return omoDocumentsParamsNormalizeDocumentStorageConfig(array());
    }
}

if (!function_exists('omoDocumentsParamsStorageIdentity')) {
    function omoDocumentsParamsStorageIdentity(array $config): string
    {
        $config = omoDocumentsParamsNormalizeDocumentStorageConfig($config);
        return implode('|', array(
            $config['type'],
            $config['baseUrl'] ?? '',
            $config['driveId'] ?? '',
            $config['username'] ?? '',
            $config['folder'] ?? '',
        ));
    }
}

if (!function_exists('omoDocumentsParamsBuildDocumentStorageConfigFromValues')) {
    function omoDocumentsParamsBuildDocumentStorageConfigFromValues(array $values, array $currentConfig = array(), bool $preserveExistingPassword = true): array
    {
        if (empty($values['document_storage_enabled'])) {
            return omoDocumentsParamsNormalizeDocumentStorageConfig(array());
        }

        $storageType = omoDocumentsParamsNormalizeStorageType($values['storage_type'] ?? '');
        if ($storageType === 'nextcloud') {
            $appPassword = trim((string)($values['nextcloud_app_password'] ?? ''));
            if ($preserveExistingPassword && $appPassword === '' && ($currentConfig['type'] ?? '') === 'nextcloud') {
                $appPassword = (string)($currentConfig['appPassword'] ?? '');
            }
            return omoDocumentsParamsNormalizeDocumentStorageConfig(array(
                'baseUrl' => trim((string)($values['nextcloud_base_url'] ?? '')),
                'username' => trim((string)($values['nextcloud_username'] ?? '')),
                'appPassword' => $appPassword,
                'folder' => trim((string)($values['nextcloud_folder'] ?? '')),
            ), 'nextcloud');
        }

        if ($storageType === 'kdrive') {
            $appPassword = trim((string)($values['kdrive_app_password'] ?? ''));
            if ($preserveExistingPassword && $appPassword === '' && ($currentConfig['type'] ?? '') === 'kdrive') {
                $appPassword = (string)($currentConfig['appPassword'] ?? '');
            }
            return omoDocumentsParamsNormalizeDocumentStorageConfig(array(
                'driveId' => trim((string)($values['kdrive_id'] ?? '')),
                'username' => trim((string)($values['kdrive_username'] ?? '')),
                'appPassword' => $appPassword,
                'folder' => trim((string)($values['kdrive_folder'] ?? '')),
            ), 'kdrive');
        }

        return omoDocumentsParamsNormalizeDocumentStorageConfig(array());
    }
}

if (!function_exists('omoDocumentsParamsStoreDocumentStorageConfig')) {
    function omoDocumentsParamsStoreDocumentStorageConfig(\dbObject\Organization $organization, array $values, bool $preserveExistingPassword = true): array
    {
        $organizationId = (int)$organization->getId();
        $organizationApplication = $organizationId > 0
            ? omoDocumentsParamsGetApplicationLink($organizationId, true)
            : null;
        if (!$organizationApplication) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.unavailable'));
        }

        $currentConfig = omoDocumentsParamsGetDocumentStorageConfig($organization, $organizationApplication);
        $targetConfig = omoDocumentsParamsBuildDocumentStorageConfigFromValues($values, $currentConfig, $preserveExistingPassword);
        $enabled = !empty($values['document_storage_enabled']);

        if ($enabled && !omoDocumentsParamsHasDocumentStorageConfig($targetConfig)) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.storage_invalid'));
        }

        $storageChanged = omoDocumentsParamsHasDocumentStorageConfig($currentConfig)
            && omoDocumentsParamsStorageIdentity($currentConfig) !== omoDocumentsParamsStorageIdentity($targetConfig);
        if ($storageChanged && \dbObject\Document::organizationHasStoredDocumentFiles($organizationId)) {
            if (empty($values['storage_change_confirmed'])) {
                return array(
                    'status' => false,
                    'confirmationRequired' => true,
                    'text' => omoDocumentsParamsT('documents.params.feedback.storage_change_warning'),
                );
            }

            $cleanupResult = \dbObject\Document::deleteOrganizationStoredFiles($organizationId, $organization, $currentConfig);
            if (!is_array($cleanupResult) || empty($cleanupResult['status'])) {
                return array(
                    'status' => false,
                    'text' => omoDocumentsParamsT('documents.params.feedback.storage_cleanup_failed'),
                );
            }
        }

        $parameters = $organizationApplication->getParametersArray();
        if (omoDocumentsParamsHasDocumentStorageConfig($targetConfig)) {
            $parameters['storage'] = $targetConfig;
        } else {
            unset($parameters['storage']);
            unset($parameters['collabora']);
        }
        unset($parameters['nextcloud']);
        $organizationApplication->setParametersArray($parameters);
        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.save_failed'));
        }

        omoDocumentsParamsClearLegacyNextcloudConfig($organization);
        return array(
            'status' => true,
            'text' => omoDocumentsParamsT('documents.params.feedback.saved'),
            'config' => $targetConfig,
        );
    }
}

if (!function_exists('omoDocumentsParamsStoreNextcloudConfig')) {
    function omoDocumentsParamsStoreNextcloudConfig(\dbObject\Organization $organization, array $values, bool $preserveExistingPassword = true): array
    {
        $organizationId = (int)$organization->getId();
        if ($organizationId <= 0) {
            return array(
                'status' => false,
                'text' => omoDocumentsParamsT('documents.params.error.organization'),
            );
        }

        $organizationApplication = omoDocumentsParamsGetApplicationLink($organizationId, true);
        if (!$organizationApplication) {
            return array(
                'status' => false,
                'text' => omoDocumentsParamsT('documents.params.error.unavailable'),
            );
        }

        $parameters = $organizationApplication->getParametersArray();
        $currentConfig = omoDocumentsParamsGetNextcloudConfig($organization, $organizationApplication, true);
        $clearConfig = !empty($values['nextcloud_clear_config']);

        if ($clearConfig) {
            unset($parameters['nextcloud']);
            $organizationApplication->setParametersArray($parameters);
            $saveResult = $organizationApplication->save();
            if (!is_array($saveResult) || empty($saveResult['status'])) {
                return array(
                    'status' => false,
                    'text' => omoDocumentsParamsT('documents.params.error.save_failed'),
                );
            }

            omoDocumentsParamsClearLegacyNextcloudConfig($organization);

            return array(
                'status' => true,
                'text' => omoDocumentsParamsT('documents.params.feedback.cleared'),
                'config' => omoDocumentsParamsNormalizeNextcloudConfig(array()),
            );
        }

        $baseUrl = trim((string)($values['nextcloud_base_url'] ?? ''));
        $username = trim((string)($values['nextcloud_username'] ?? ''));
        $appPassword = trim((string)($values['nextcloud_app_password'] ?? ''));
        $folder = trim((string)($values['nextcloud_folder'] ?? ''));

        if ($preserveExistingPassword && $appPassword === '' && $currentConfig['appPassword'] !== '') {
            $appPassword = $currentConfig['appPassword'];
        }

        $normalizedConfig = omoDocumentsParamsNormalizeNextcloudConfig(array(
            'baseUrl' => $baseUrl,
            'username' => $username,
            'appPassword' => $appPassword,
            'folder' => $folder,
        ));

        $allFieldsEmpty = $normalizedConfig['baseUrl'] === ''
            && $normalizedConfig['username'] === ''
            && $normalizedConfig['appPassword'] === ''
            && $normalizedConfig['folder'] === '';

        if ($allFieldsEmpty) {
            unset($parameters['nextcloud']);
        } else {
            if (
                $normalizedConfig['baseUrl'] === ''
                || $normalizedConfig['username'] === ''
                || $normalizedConfig['appPassword'] === ''
            ) {
                return array(
                    'status' => false,
                    'text' => omoDocumentsParamsT('documents.params.feedback.invalid'),
                );
            }

            $parameters['nextcloud'] = $normalizedConfig;
        }

        $organizationApplication->setParametersArray($parameters);
        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return array(
                'status' => false,
                'text' => omoDocumentsParamsT('documents.params.error.save_failed'),
            );
        }

        omoDocumentsParamsClearLegacyNextcloudConfig($organization);

        return array(
            'status' => true,
            'text' => $allFieldsEmpty
                ? omoDocumentsParamsT('documents.params.feedback.cleared')
                : omoDocumentsParamsT('documents.params.feedback.saved'),
            'config' => $allFieldsEmpty ? omoDocumentsParamsNormalizeNextcloudConfig(array()) : $normalizedConfig,
        );
    }
}

if (!function_exists('omoDocumentsParamsExtractCollaboraPayload')) {
    function omoDocumentsParamsExtractCollaboraPayload(array $parameters): array
    {
        return isset($parameters['collabora']) && is_array($parameters['collabora'])
            ? $parameters['collabora']
            : array();
    }
}

if (!function_exists('omoDocumentsParamsGetCollaboraConfig')) {
    function omoDocumentsParamsGetCollaboraConfig(\dbObject\Organization $organization, ?\dbObject\OrganizationApplication $organizationApplication = null): array
    {
        $appParameters = $organizationApplication
            ? $organizationApplication->getParametersArray()
            : $organization->getApplicationParametersByDirectory('documents');
        return omoCollaboraNormalizeConfig(omoDocumentsParamsExtractCollaboraPayload($appParameters));
    }
}

if (!function_exists('omoDocumentsParamsHasCollaboraConfig')) {
    function omoDocumentsParamsHasCollaboraConfig(array $config): bool
    {
        return omoCollaboraNormalizeConfig($config)['baseUrl'] !== '';
    }
}

if (!function_exists('omoDocumentsParamsStoreCollaboraConfig')) {
    function omoDocumentsParamsStoreCollaboraConfig(\dbObject\Organization $organization, array $values): array
    {
        $organizationId = (int)$organization->getId();
        $organizationApplication = $organizationId > 0
            ? omoDocumentsParamsGetApplicationLink($organizationId, true)
            : null;
        if (!$organizationApplication) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.unavailable'));
        }

        $parameters = $organizationApplication->getParametersArray();
        $currentConfig = omoDocumentsParamsGetCollaboraConfig($organization, $organizationApplication);
        $documentStorageConfig = omoDocumentsParamsGetDocumentStorageConfig($organization, $organizationApplication);
        $enabled = !empty($values['collabora_enabled']);
        $storageEnabled = !empty($values['document_storage_enabled']);
        $clearConfig = !empty($values['collabora_clear_config']) || !$enabled || !$storageEnabled;

        if (!$enabled && !$clearConfig) {
            return array('status' => true, 'text' => omoDocumentsParamsT('documents.params.feedback.saved'));
        }

        if ($clearConfig) {
            if ($storageEnabled && \dbObject\Document::organizationHasCollaboraDocuments($organizationId) && $currentConfig['baseUrl'] !== '') {
                return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.collabora_config_in_use'));
            }

            unset($parameters['collabora']);
            $organizationApplication->setParametersArray($parameters);
            $saveResult = $organizationApplication->save();
            return is_array($saveResult) && !empty($saveResult['status'])
                ? array('status' => true, 'text' => omoDocumentsParamsT('documents.params.feedback.collabora_cleared'))
                : array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.save_failed'));
        }

        if (!omoDocumentsParamsHasDocumentStorageConfig($documentStorageConfig) && !empty($values['document_storage_enabled'])) {
            $documentStorageConfig = omoDocumentsParamsBuildDocumentStorageConfigFromValues($values, $documentStorageConfig, true);
        }

        if (!omoDocumentsParamsHasDocumentStorageConfig($documentStorageConfig)) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.status.collabora_requires_storage'));
        }

        $baseUrlInput = trim((string)($values['collabora_base_url'] ?? ''));
        $internalUrlInput = trim((string)($values['collabora_internal_url'] ?? ''));
        $config = omoCollaboraNormalizeConfig(array(
            'baseUrl' => $baseUrlInput,
            'internalUrl' => $internalUrlInput,
        ));
        if ($config['baseUrl'] === '') {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.collabora_invalid'));
        }

        if ($internalUrlInput !== '' && $config['internalUrl'] === '') {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.collabora_invalid'));
        }

        if (
            $currentConfig['baseUrl'] !== ''
            && !hash_equals($currentConfig['baseUrl'], $config['baseUrl'])
            && \dbObject\Document::organizationHasCollaboraDocuments($organizationId)
        ) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.collabora_server_in_use'));
        }

        $parameters['collabora'] = $config;
        $organizationApplication->setParametersArray($parameters);
        $saveResult = $organizationApplication->save();
        return is_array($saveResult) && !empty($saveResult['status'])
            ? array('status' => true, 'text' => omoDocumentsParamsT('documents.params.feedback.saved'), 'config' => $config)
            : array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.save_failed'));
    }
}

if (!function_exists('omoDocumentsParamsGetVisibilityDefaults')) {
    function omoDocumentsParamsGetVisibilityDefaults(\dbObject\Organization $organization, ?\dbObject\OrganizationApplication $organizationApplication = null): array
    {
        $organizationId = (int)$organization->getId();
        return \dbObject\Document::getApplicationDefaultScopeTypes($organizationId);
    }
}

if (!function_exists('omoDocumentsParamsStoreVisibilityDefaults')) {
    function omoDocumentsParamsStoreVisibilityDefaults(\dbObject\Organization $organization, array $values): array
    {
        $organizationId = (int)$organization->getId();
        if ($organizationId <= 0) {
            return array(
                'status' => false,
                'text' => omoDocumentsParamsT('documents.params.error.organization'),
            );
        }

        $organizationApplication = omoDocumentsParamsGetApplicationLink($organizationId, true);
        if (!$organizationApplication) {
            return array(
                'status' => false,
                'text' => omoDocumentsParamsT('documents.params.error.unavailable'),
            );
        }

        $parameters = $organizationApplication->getParametersArray();
        $parameters['documentDefaults'] = array(
            'visibilityType' => \dbObject\ObjectVisibility::normalizeVisibilityType(
                (string)($values['default_visibility_type'] ?? \dbObject\Document::getDefaultVisibilityTypeForOrganization($organizationId))
            ),
            'editVisibilityType' => \dbObject\ObjectVisibility::normalizeVisibilityType(
                (string)($values['default_edit_visibility_type'] ?? \dbObject\Document::getDefaultEditVisibilityTypeForOrganization($organizationId))
            ),
        );

        $organizationApplication->setParametersArray($parameters);
        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return array(
                'status' => false,
                'text' => omoDocumentsParamsT('documents.params.error.save_failed'),
            );
        }

        return array(
            'status' => true,
            'text' => omoDocumentsParamsT('documents.params.feedback.saved'),
            'defaults' => $parameters['documentDefaults'],
        );
    }
}
