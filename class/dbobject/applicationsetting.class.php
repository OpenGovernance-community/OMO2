<?php
namespace dbObject;

class ApplicationSetting extends DbObject
{
    public const DASHBOARD_SETTING_KEY = 'omo.dashboard';

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

    public static function getDashboardDefaultLayout(): ?array
    {
        $setting = self::loadDashboardSetting();
        if (!$setting) {
            return null;
        }

        $parameters = $setting->getParametersArray();
        if (!array_key_exists(UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER, $parameters)) {
            return null;
        }

        return UserHolon::normalizeDashboardLayout($parameters[UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER]);
    }

    public static function saveDashboardDefaultLayout(array $layout)
    {
        $setting = self::loadDashboardSetting();
        if (!$setting) {
            $setting = new self();
            $setting->set('setting_key', self::DASHBOARD_SETTING_KEY);
        }

        $parameters = $setting->getParametersArray();
        $parameters[UserHolon::DASHBOARD_DEFAULT_LAYOUT_PARAMETER] = UserHolon::normalizeDashboardLayout($layout);
        $setting->set('parameters', $parameters);
        return $setting->save();
    }

    public static function getDashboardTemplateDefaultLayout($templateKey): ?array
    {
        $templateKey = UserHolon::normalizeDashboardTemplateKey($templateKey);
        $setting = self::loadDashboardSetting();
        if ($templateKey === '' || !$setting) {
            return null;
        }

        $parameters = $setting->getParametersArray();
        $templateLayouts = UserHolon::normalizeDashboardTemplateLayouts(
            $parameters[UserHolon::DASHBOARD_TEMPLATE_LAYOUTS_PARAMETER] ?? array()
        );
        return array_key_exists($templateKey, $templateLayouts) ? $templateLayouts[$templateKey] : null;
    }

    public static function getDashboardTemplateDefaultLayoutForHolon(Holon $holon): ?array
    {
        foreach ($holon->getDashboardTemplateLayoutKeys() as $templateKey) {
            $layout = self::getDashboardTemplateDefaultLayout($templateKey);
            if ($layout !== null) {
                return $layout;
            }
        }

        return null;
    }

    public static function saveDashboardTemplateDefaultLayout($templateKey, array $layout)
    {
        $templateKey = UserHolon::normalizeDashboardTemplateKey($templateKey);
        if ($templateKey === '') {
            return array('status' => false);
        }

        $setting = self::loadDashboardSetting();
        if (!$setting) {
            $setting = new self();
            $setting->set('setting_key', self::DASHBOARD_SETTING_KEY);
        }

        $parameters = $setting->getParametersArray();
        $templateLayouts = UserHolon::normalizeDashboardTemplateLayouts(
            $parameters[UserHolon::DASHBOARD_TEMPLATE_LAYOUTS_PARAMETER] ?? array()
        );
        $templateLayouts[$templateKey] = UserHolon::normalizeDashboardLayout($layout);
        $parameters[UserHolon::DASHBOARD_TEMPLATE_LAYOUTS_PARAMETER] = $templateLayouts;
        $setting->set('parameters', $parameters);
        return $setting->save();
    }
}
