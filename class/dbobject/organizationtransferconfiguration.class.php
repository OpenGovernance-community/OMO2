<?php
namespace dbObject;

/** Shared configuration for archive imports and organization model mapping. */
class OrganizationTransferConfiguration
{
    public const ORGANIZATION_KEYS = [
        'lexicon', Organization::STRUCTURE_DISPLAY_SETTINGS_PARAMETER,
        UserHolon::APPLICATION_VIEW_TEMPLATE_DEFAULTS_PARAMETER,
        UserHolon::APPLICATION_VIEW_ORGANIZATION_DEFAULTS_PARAMETER,
        UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER,
        UserHolon::DASHBOARD_TEMPLATE_LAYOUTS_PARAMETER,
        UserHolon::DASHBOARD_ORGANIZATION_DEFAULT_LAYOUT_PARAMETER,
        UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER,
    ];
    public const HOLON_KEYS = [
        UserHolon::APPLICATION_VIEW_HOLON_DEFAULTS_PARAMETER,
        UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER,
    ];

    public static function holonDefaults(Holon $holon): array
    {
        return array_intersect_key($holon->getParametersArray(), array_flip(self::HOLON_KEYS));
    }

    public static function setHolonDefaults(Holon $holon, array $parameters): void
    {
        $keys = array_flip(self::HOLON_KEYS);
        $holon->setParametersArray(array_replace(
            array_diff_key($holon->getParametersArray(), $keys),
            array_intersect_key($parameters, $keys)
        ));
    }

    public static function capture(Organization $organization): array
    {
        $parameters = $organization->getTransferConfigurationParameters();
        // Type defaults are global on the source server. Keep a local snapshot
        // so imports on another server never change defaults for other orgs.
        foreach ([1, 2, 3, 4] as $typeId) {
            $key = UserHolon::makeDashboardBaseTypeKey($typeId);
            foreach (UserHolon::getApplicationViewKeys() as $app) {
                $view = ApplicationSetting::getApplicationViewDefaultsForType($app, $typeId)['baseType'];
                if ($view !== null && !isset($parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER][$key][$app])) {
                    $parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER][$key][$app] = $view;
                }
            }
            $layout = ApplicationSetting::getDashboardBaseTypeDefaultLayout($typeId);
            if ($layout !== null && !isset($parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER][$key])) {
                $parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER][$key] = $layout;
            }
        }
        $links = new ArrayOrganizationApplication();
        $links->load(['where' => [['field' => 'IDorganization', 'value' => (int)$organization->getId()]]]);
        $byId = [];
        foreach ($links as $link) { $byId[(int)$link->get('IDapplication')] = $link; }
        $applications = new ArrayApplication();
        $applications->load();
        $records = [];
        foreach ($applications as $application) {
            $link = $byId[(int)$application->getId()] ?? null;
            $records[] = [
                'directory' => (string)$application->get('directory'),
                'hash' => (string)$application->get('hash'),
                'position' => $link ? $link->get('position') : $application->get('position'),
                'active' => $link ? (bool)$link->get('active') : true,
                'parameters' => $link ? $link->getParametersArray() : [],
            ];
        }
        return ['parameters' => $parameters, 'applications' => $records];
    }

    public static function apply(Organization $organization, array $configuration): void
    {
        if (is_array($configuration['parameters'] ?? null)) {
            $keys = array_flip(self::ORGANIZATION_KEYS);
            $parameters = array_replace(
                array_diff_key($organization->getParametersArray(), $keys),
                array_intersect_key($configuration['parameters'], $keys)
            );
            $organization->setParametersArray($parameters);
            $organization->setLexicon($organization->getLexicon());
            self::save($organization);
        }
        if (!is_array($configuration['applications'] ?? null)) { return; }
        $byDirectory = $byHash = [];
        foreach ($configuration['applications'] as $record) {
            if (!is_array($record)) { continue; }
            $directory = strtolower(trim((string)($record['directory'] ?? '')));
            $hash = strtolower(trim((string)($record['hash'] ?? '')));
            if ($directory !== '') { $byDirectory[$directory] = $record; }
            if ($hash !== '') { $byHash[$hash] = $record; }
        }
        $applications = new ArrayApplication();
        $applications->load();
        foreach ($applications as $application) {
            $record = $byDirectory[strtolower(trim((string)$application->get('directory')))]
                ?? $byHash[strtolower(trim((string)$application->get('hash')))] ?? null;
            $link = new OrganizationApplication();
            $link->load([['IDorganization', (int)$organization->getId()], ['IDapplication', (int)$application->getId()]]);
            $link->set('IDorganization', (int)$organization->getId());
            $link->set('IDapplication', (int)$application->getId());
            $link->set('position', $record['position'] ?? $application->get('position'));
            $link->set('active', !empty($record['active']));
            $link->setParametersArray(is_array($record['parameters'] ?? null) ? $record['parameters'] : []);
            self::save($link);
        }
    }

    private static function save(DbObject $object): void
    {
        if (empty($object->save()['status'])) {
            throw new \RuntimeException('Impossible de copier la configuration de l organisation.');
        }
    }
}
