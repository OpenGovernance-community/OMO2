<?php

require_once dirname(__DIR__, 4) . '/common/etherpad.php';

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
            'documents.params.field.folder_hint' => ['text' => 'Optionnel. Si vide, OMO utilisera directement un dossier omo-documents à la racine du compte.', 'context' => 'Hint shown below the remote Nextcloud folder field.'],
            'documents.params.field.default_visibility' => ['text' => 'Visibilite par defaut', 'context' => 'Label of the default document visibility selector in Documents settings.'],
            'documents.params.section.nextcloud' => ['text' => 'Stockage Nextcloud', 'context' => 'Heading of the Nextcloud settings section.'],
            'documents.params.section.etherpad' => ['text' => 'Documents Etherpad', 'context' => 'Heading of the Etherpad settings section.'],
            'documents.params.status.etherpad_ready' => ['text' => 'Connexion Etherpad configuree pour les documents collaboratifs.', 'context' => 'Status text shown when Etherpad is configured.'],
            'documents.params.status.etherpad_empty' => ['text' => 'Aucune connexion Etherpad configuree. Le type de document Etherpad ne sera pas propose.', 'context' => 'Status text shown when Etherpad is not configured.'],
            'documents.params.status.etherpad_cookie_missing' => ['text' => 'Etherpad est configure, mais le domaine de cookie ne permet pas encore de transmettre les sessions OMO.', 'context' => 'Status text shown when Etherpad API works but browser sessions cannot be shared.'],
            'documents.params.field.etherpad_base_url' => ['text' => 'URL du serveur Etherpad', 'context' => 'Label of the organization Etherpad URL field.'],
            'documents.params.field.etherpad_base_url_hint' => ['text' => 'Laissez vide pour utiliser le serveur Etherpad defini dans les secrets du serveur.', 'context' => 'Hint below the organization Etherpad URL field.'],
            'documents.params.field.etherpad_api_key' => ['text' => 'Cle API Etherpad', 'context' => 'Label of the organization Etherpad API key field.'],
            'documents.params.field.etherpad_api_key_hint' => ['text' => 'Laissez vide pour utiliser la cle API globale avec le serveur global. Un autre serveur exige sa propre cle.', 'context' => 'Hint below the organization Etherpad API key field.'],
            'documents.params.field.etherpad_api_key_placeholder_new' => ['text' => 'Cle API Etherpad de cette organisation', 'context' => 'Placeholder for a fresh Etherpad API key.'],
            'documents.params.field.etherpad_api_key_placeholder_keep' => ['text' => 'Laisser vide pour conserver la cle actuelle', 'context' => 'Placeholder when an Etherpad API key override exists.'],
            'documents.params.field.etherpad_clear' => ['text' => 'Supprimer la configuration Etherpad propre a cette organisation', 'context' => 'Checkbox label used to clear the Etherpad configuration.'],
            'documents.params.feedback.etherpad_cleared' => ['text' => 'Configuration Etherpad de l organisation supprimee; les secrets globaux s appliquent a nouveau.', 'context' => 'Success message returned after clearing Etherpad configuration.'],
            'documents.params.feedback.etherpad_invalid' => ['text' => 'L URL du serveur Etherpad doit etre une adresse http ou https valide.', 'context' => 'Error returned when the Etherpad URL is invalid.'],
            'documents.params.feedback.etherpad_incomplete' => ['text' => 'Une cle API propre est requise lorsque l organisation utilise un autre serveur Etherpad.', 'context' => 'Error returned when a custom Etherpad server has no organization API key.'],
            'documents.params.feedback.etherpad_server_in_use' => ['text' => 'Le serveur Etherpad ne peut pas etre change tant que cette organisation possede des documents Etherpad.', 'context' => 'Error returned when changing an Etherpad server that still owns organization pads.'],
            'documents.params.feedback.etherpad_config_in_use' => ['text' => 'Cette configuration Etherpad ne peut pas etre supprimee tant que l organisation possede des documents Etherpad.', 'context' => 'Error returned when clearing credentials still needed by organization pads.'],
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

if (!function_exists('omoDocumentsParamsNormalizeEtherpadConfig')) {
    function omoDocumentsParamsNormalizeEtherpadConfig(array $config): array
    {
        return array(
            'baseUrl' => omoEtherpadNormalizeBaseUrl((string)($config['baseUrl'] ?? '')),
            'apiKey' => trim((string)($config['apiKey'] ?? '')),
        );
    }
}

if (!function_exists('omoDocumentsParamsExtractEtherpadPayload')) {
    function omoDocumentsParamsExtractEtherpadPayload(array $parameters): array
    {
        return isset($parameters['etherpad']) && is_array($parameters['etherpad'])
            ? $parameters['etherpad']
            : array();
    }
}

if (!function_exists('omoDocumentsParamsGetEtherpadConfig')) {
    function omoDocumentsParamsGetEtherpadConfig(\dbObject\Organization $organization, ?\dbObject\OrganizationApplication $organizationApplication = null): array
    {
        $appParameters = $organizationApplication
            ? $organizationApplication->getParametersArray()
            : $organization->getApplicationParametersByDirectory('documents');
        $storedConfig = omoDocumentsParamsNormalizeEtherpadConfig(omoDocumentsParamsExtractEtherpadPayload($appParameters));
        return omoEtherpadResolveConfig($storedConfig);
    }
}

if (!function_exists('omoDocumentsParamsHasEtherpadConfig')) {
    function omoDocumentsParamsHasEtherpadConfig(array $config): bool
    {
        $config = omoDocumentsParamsNormalizeEtherpadConfig($config);
        return $config['baseUrl'] !== '' && $config['apiKey'] !== '';
    }
}

if (!function_exists('omoDocumentsParamsStoreEtherpadConfig')) {
    function omoDocumentsParamsStoreEtherpadConfig(\dbObject\Organization $organization, array $values, bool $preserveExistingApiKey = true): array
    {
        $organizationId = (int)$organization->getId();
        $organizationApplication = $organizationId > 0
            ? omoDocumentsParamsGetApplicationLink($organizationId, true)
            : null;
        if (!$organizationApplication) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.unavailable'));
        }

        $parameters = $organizationApplication->getParametersArray();
        $storedConfig = omoDocumentsParamsNormalizeEtherpadConfig(omoDocumentsParamsExtractEtherpadPayload($parameters));
        if (!empty($values['etherpad_clear_config'])) {
            $currentConfig = omoEtherpadResolveConfig($storedConfig);
            $globalConfig = omoEtherpadResolveConfig();
            if (
                \dbObject\Document::organizationHasEtherpadDocuments($organizationId)
                && (
                    !hash_equals($currentConfig['baseUrl'], $globalConfig['baseUrl'])
                    || $globalConfig['apiKey'] === ''
                )
            ) {
                return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.etherpad_config_in_use'));
            }

            unset($parameters['etherpad']);
            $organizationApplication->setParametersArray($parameters);
            $saveResult = $organizationApplication->save();
            return is_array($saveResult) && !empty($saveResult['status'])
                ? array('status' => true, 'text' => omoDocumentsParamsT('documents.params.feedback.etherpad_cleared'))
                : array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.save_failed'));
        }

        $baseUrlInput = trim((string)($values['etherpad_base_url'] ?? ''));
        $baseUrl = omoEtherpadNormalizeBaseUrl($baseUrlInput);
        $apiKey = trim((string)($values['etherpad_api_key'] ?? ''));
        if ($baseUrlInput !== '' && $baseUrl === '') {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.etherpad_invalid'));
        }

        $globalConfig = omoEtherpadResolveConfig();
        $currentBaseUrl = $storedConfig['baseUrl'] !== '' ? $storedConfig['baseUrl'] : $globalConfig['baseUrl'];
        $nextBaseUrl = $baseUrl !== '' ? $baseUrl : $globalConfig['baseUrl'];
        if (
            !hash_equals($currentBaseUrl, $nextBaseUrl)
            && \dbObject\Document::organizationHasEtherpadDocuments($organizationId)
        ) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.etherpad_server_in_use'));
        }
        if (
            $preserveExistingApiKey
            && $apiKey === ''
            && $storedConfig['apiKey'] !== ''
            && $currentBaseUrl !== ''
            && $nextBaseUrl !== ''
            && hash_equals($currentBaseUrl, $nextBaseUrl)
        ) {
            $apiKey = $storedConfig['apiKey'];
        }

        $normalizedConfig = omoDocumentsParamsNormalizeEtherpadConfig(array(
            'baseUrl' => $baseUrl,
            'apiKey' => $apiKey,
        ));
        if ($normalizedConfig['baseUrl'] === '' && $normalizedConfig['apiKey'] === '') {
            unset($parameters['etherpad']);
        } else {
            $resolvedConfig = omoEtherpadResolveConfig($normalizedConfig);
            if ($resolvedConfig['baseUrl'] === '' || $resolvedConfig['apiKey'] === '') {
                return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.feedback.etherpad_incomplete'));
            }
            $parameters['etherpad'] = $normalizedConfig;
        }

        $organizationApplication->setParametersArray($parameters);
        $saveResult = $organizationApplication->save();
        if (!is_array($saveResult) || empty($saveResult['status'])) {
            return array('status' => false, 'text' => omoDocumentsParamsT('documents.params.error.save_failed'));
        }

        return array('status' => true, 'text' => omoDocumentsParamsT('documents.params.feedback.saved'), 'config' => $normalizedConfig);
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
