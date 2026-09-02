<?php
namespace dbObject;

class ApplicationSetting extends DbObject
{
    public const DASHBOARD_SETTING_KEY = 'omo.dashboard';
    public const APPLICATION_VIEW_SETTING_KEY = 'omo.applicationViews';

    protected static ?self $applicationViewSettingCache = null;
    protected static bool $applicationViewSettingLoaded = false;

    public static function tableName()
    {
        return 'application_setting';
    }

    public static function rules()
    {
        return array(
            array(array('setting_key'), 'required'),
            array(array('id'), 'integer'),
            array(array('setting_key'), 'string'),
            array(array('parameters'), 'parameters'),
            array(array('setting_key'), 'unique'),
            array(array('datecreation', 'datemodification'), 'datetime'),
            array(array('id'), 'safe'),
        );
    }

    public static function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'setting_key' => 'Cle',
            'parameters' => 'Parametres',
            'datecreation' => 'Creation',
            'datemodification' => 'Modification',
        );
    }

    public static function attributeLength()
    {
        return array('setting_key' => 120);
    }

    protected function getParametersArray(): array
    {
        $parameters = json_decode((string)$this->get('parameters'), true);
        return is_array($parameters) ? $parameters : array();
    }

    protected static function loadDashboardSetting(): ?self
    {
        $setting = new self();
        return $setting->load(array(array('setting_key', self::DASHBOARD_SETTING_KEY))) ? $setting : null;
    }

    protected static function loadApplicationViewSetting(): ?self
    {
        if (self::$applicationViewSettingLoaded) {
            return self::$applicationViewSettingCache;
        }

        self::$applicationViewSettingLoaded = true;
        $setting = new self();
        self::$applicationViewSettingCache = $setting->load(array(array('setting_key', self::APPLICATION_VIEW_SETTING_KEY)))
            ? $setting
            : null;
        return self::$applicationViewSettingCache;
    }

    public static function getApplicationViewDefaultsForType($applicationKey, $typeId): array
    {
        $applicationKey = UserHolon::normalizeApplicationViewKey($applicationKey);
        $typeKey = UserHolon::makeDashboardBaseTypeKey($typeId);
        $setting = self::loadApplicationViewSetting();
        if ($applicationKey === '' || !$setting) {
            return array('baseType' => null, 'global' => null);
        }

        $parameters = $setting->getParametersArray();
        $globalViews = UserHolon::normalizeApplicationViewDefaults(
            $parameters[UserHolon::APPLICATION_VIEW_DEFAULTS_PARAMETER] ?? array()
        );
        $baseTypeViews = UserHolon::normalizeApplicationViewBaseTypeDefaults(
            $parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER] ?? array()
        );

        return array(
            'baseType' => $typeKey !== '' ? ($baseTypeViews[$typeKey][$applicationKey] ?? null) : null,
            'global' => $globalViews[$applicationKey] ?? null,
        );
    }

    public static function saveApplicationViewBaseTypeDefault($applicationKey, $typeId, array $view)
    {
        $applicationKey = UserHolon::normalizeApplicationViewKey($applicationKey);
        $typeKey = UserHolon::makeDashboardBaseTypeKey($typeId);
        if ($applicationKey === '' || $typeKey === '') {
            return array('status' => false);
        }
        $setting = self::loadApplicationViewSetting();
        if (!$setting) {
            $setting = new self();
            $setting->set('setting_key', self::APPLICATION_VIEW_SETTING_KEY);
            self::$applicationViewSettingCache = $setting;
            self::$applicationViewSettingLoaded = true;
        }
        $parameters = $setting->getParametersArray();
        $views = UserHolon::normalizeApplicationViewBaseTypeDefaults(
            $parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER] ?? array()
        );
        if (!isset($views[$typeKey]) || !is_array($views[$typeKey])) {
            $views[$typeKey] = array();
        }
        $views[$typeKey][$applicationKey] = UserHolon::normalizeApplicationView($view);
        $parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER] = $views;
        $setting->set('parameters', $parameters);
        return $setting->save();
    }

    public static function clearApplicationViewBaseTypeDefault($applicationKey, $typeId)
    {
        $applicationKey = UserHolon::normalizeApplicationViewKey($applicationKey);
        $typeKey = UserHolon::makeDashboardBaseTypeKey($typeId);
        $setting = self::loadApplicationViewSetting();
        if ($applicationKey === '' || $typeKey === '' || !$setting) {
            return array('status' => true);
        }
        $parameters = $setting->getParametersArray();
        $views = UserHolon::normalizeApplicationViewBaseTypeDefaults(
            $parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER] ?? array()
        );
        if (isset($views[$typeKey])) {
            unset($views[$typeKey][$applicationKey]);
            if ($views[$typeKey] === array()) {
                unset($views[$typeKey]);
            }
        }
        if ($views === array()) {
            unset($parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER]);
        } else {
            $parameters[UserHolon::APPLICATION_VIEW_BASE_TYPE_DEFAULTS_PARAMETER] = $views;
        }
        $setting->set('parameters', $parameters);
        return $setting->save();
    }

    public static function getDashboardBaseTypeDefaultLayout($typeId): ?array
    {
        $typeKey = UserHolon::makeDashboardBaseTypeKey($typeId);
        $setting = self::loadDashboardSetting();
        if ($typeKey === '' || !$setting) {
            return null;
        }

        $parameters = $setting->getParametersArray();
        $layouts = UserHolon::normalizeDashboardTemplateLayouts(
            $parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER] ?? array()
        );
        return array_key_exists($typeKey, $layouts) ? $layouts[$typeKey] : null;
    }

    public static function getDashboardBaseTypeDefaultLayoutForHolon(Holon $holon): ?array
    {
        return self::getDashboardBaseTypeDefaultLayout((int)$holon->get('IDtypeholon'));
    }

    public static function saveDashboardBaseTypeDefaultLayout($typeId, array $layout)
    {
        $typeKey = UserHolon::makeDashboardBaseTypeKey($typeId);
        if ($typeKey === '') {
            return array('status' => false);
        }

        $setting = self::loadDashboardSetting();
        if (!$setting) {
            $setting = new self();
            $setting->set('setting_key', self::DASHBOARD_SETTING_KEY);
        }

        $parameters = $setting->getParametersArray();
        $layouts = UserHolon::normalizeDashboardTemplateLayouts(
            $parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER] ?? array()
        );
        $layouts[$typeKey] = UserHolon::normalizeDashboardLayout($layout);
        $parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER] = $layouts;
        $setting->set('parameters', $parameters);
        return $setting->save();
    }

    public static function clearDashboardBaseTypeDefaultLayout($typeId)
    {
        $typeKey = UserHolon::makeDashboardBaseTypeKey($typeId);
        $setting = self::loadDashboardSetting();
        if ($typeKey === '' || !$setting) {
            return array('status' => false);
        }

        $parameters = $setting->getParametersArray();
        $layouts = UserHolon::normalizeDashboardTemplateLayouts(
            $parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER] ?? array()
        );
        unset($layouts[$typeKey]);
        if ($layouts === array()) {
            unset($parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER]);
        } else {
            $parameters[UserHolon::DASHBOARD_BASE_TYPE_LAYOUTS_PARAMETER] = $layouts;
        }
        $setting->set('parameters', $parameters);
        return $setting->save();
    }
}
