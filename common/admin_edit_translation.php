<?php

require_once dirname(__DIR__) . '/omo/translations.php';

function adminEditBuildBundleKey($object): string
{
    $objectKey = '';

    if (is_object($object) && method_exists($object, 'tableName')) {
        $objectKey = (string)$object::tableName();
    }

    if ($objectKey === '' && is_object($object)) {
        $objectKey = strtolower((new ReflectionClass($object))->getShortName());
    }

    $objectKey = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($objectKey));
    $objectKey = trim((string)$objectKey, '_');

    if ($objectKey === '') {
        $objectKey = 'object';
    }

    return 'admin_edit_' . $objectKey;
}

function adminEditBuildSourceLang($object): array
{
    $sourceLang = [
        'admin_edit.toolbar.edit' => [
            'text' => 'Édition',
            'context' => 'Toolbar title shown when editing an existing dbObject record.',
        ],
        'admin_edit.toolbar.create' => [
            'text' => 'Création',
            'context' => 'Toolbar title shown when creating a new dbObject record.',
        ],
        'admin_edit.toolbar.text' => [
            'text' => 'Renseignez les champs puis enregistrez.',
            'context' => 'Toolbar helper text shown above the adminEdit form.',
        ],
        'admin_edit.action.cancel' => [
            'text' => 'Annuler',
            'context' => 'Secondary button label used to cancel an adminEdit form.',
        ],
        'admin_edit.action.save' => [
            'text' => 'Sauver',
            'context' => 'Primary button label used to submit an adminEdit form.',
        ],
        'admin_edit.action.save_draft' => [
            'text' => 'Enregistrer comme brouillon',
            'context' => 'Secondary button label used to save a draft from adminEdit.',
        ],
        'admin_edit.choice.select' => [
            'text' => 'Choisissez...',
            'context' => 'Default empty option label shown in foreign key selects.',
        ],
        'admin_edit.latlong.placeholder.latitude' => [
            'text' => 'Latitude',
            'context' => 'Latitude placeholder shown in adminEdit latlong fields.',
        ],
        'admin_edit.latlong.placeholder.longitude' => [
            'text' => 'Longitude',
            'context' => 'Longitude placeholder shown in adminEdit latlong fields.',
        ],
        'admin_edit.latlong.help.manual' => [
            'text' => 'Renseignez la latitude et la longitude manuellement si la carte n’est pas disponible.',
            'context' => 'Help text shown when leaflet is unavailable in adminEdit latlong fields.',
        ],
        'admin_edit.latlong.help.map' => [
            'text' => 'Cliquez sur la carte pour choisir l’emplacement.',
            'context' => 'Help text shown below the leaflet map in adminEdit latlong fields.',
        ],
        'admin_edit.length.max' => [
            'text' => '{count} caractères maximum',
            'context' => 'Character limit helper shown below adminEdit text fields.',
        ],
        'admin_edit.length.progress' => [
            'text' => '{current} sur {limit} caractères',
            'context' => 'Live character count helper shown below adminEdit text fields.',
        ],
        'admin_edit.image.choose_disk' => [
            'text' => 'Choisir une image sur le disque',
            'context' => 'Button label shown in the sized image adminEdit field.',
        ],
    ];

    $labels = is_object($object) && method_exists($object, 'attributeLabels')
        ? (array)$object->attributeLabels()
        : [];
    $descriptions = is_object($object) && method_exists($object, 'attributeDescriptions')
        ? (array)$object->attributeDescriptions()
        : [];
    $placeholders = is_object($object) && method_exists($object, 'attributePlaceholder')
        ? (array)$object::attributePlaceholder()
        : [];
    $values = is_object($object) && method_exists($object, 'attributeValues')
        ? (array)$object::attributeValues()
        : [];
    $objectKey = preg_replace('/[^a-z0-9_]+/i', '_', strtolower(is_object($object) && method_exists($object, 'tableName') ? (string)$object::tableName() : 'object'));
    $objectKey = trim((string)$objectKey, '_');
    if ($objectKey === '') {
        $objectKey = 'object';
    }

    foreach ($labels as $field => $label) {
        $normalizedField = preg_replace('/[^a-z0-9_]+/i', '_', strtolower((string)$field));
        $normalizedField = trim((string)$normalizedField, '_');
        if ($normalizedField === '') {
            continue;
        }

        $sourceLang['dbobject.' . $objectKey . '.' . $normalizedField . '.label'] = [
            'text' => (string)$label,
            'context' => 'Field label for the `' . $field . '` attribute in the `' . $objectKey . '` dbObject adminEdit form.',
        ];

        if (array_key_exists($field, $descriptions) && trim((string)$descriptions[$field]) !== '') {
            $sourceLang['dbobject.' . $objectKey . '.' . $normalizedField . '.description'] = [
                'text' => (string)$descriptions[$field],
                'context' => 'Field help text for the `' . $field . '` attribute in the `' . $objectKey . '` dbObject adminEdit form.',
            ];
        }

        if (array_key_exists($field, $placeholders) && trim((string)$placeholders[$field]) !== '') {
            $sourceLang['dbobject.' . $objectKey . '.' . $normalizedField . '.placeholder'] = [
                'text' => (string)$placeholders[$field],
                'context' => 'Input placeholder for the `' . $field . '` attribute in the `' . $objectKey . '` dbObject adminEdit form.',
            ];
        }
        foreach ($values[$field] ?? [] as $option) {
            if (!is_array($option) || !array_key_exists(0, $option) || !array_key_exists(1, $option)) continue;
            $sourceLang[adminEditFieldOptionTranslationKey($object, (string)$field, $option[0])] = [
                'text' => (string)$option[1],
                'context' => 'Choice label for the `' . $field . '` field in the `' . $objectKey . '` adminEdit form. The stored choice value must stay unchanged.',
            ];
        }
    }

    return $sourceLang;
}

function adminEditLoadBundle($object): array
{
    static $cache = [];

    $bundleKey = adminEditBuildBundleKey($object);
    if (isset($cache[$bundleKey])) {
        return $cache[$bundleKey];
    }

    $cache[$bundleKey] = omoLoadTranslationBundle($bundleKey, adminEditBuildSourceLang($object));
    return $cache[$bundleKey];
}

function adminEditTranslate(string $key, array $variables = [], $object = null, ?array $bundle = null, ?array $sourceLang = null): string
{
    $sourceLang = is_array($sourceLang) ? $sourceLang : adminEditBuildSourceLang($object);
    $bundle = is_array($bundle) ? $bundle : adminEditLoadBundle($object);

    return t($key, $variables, $bundle, $sourceLang);
}

function adminEditFieldTranslationKey($object, string $field, string $suffix): string
{
    $objectKey = is_object($object) && method_exists($object, 'tableName') ? (string)$object::tableName() : 'object';
    $objectKey = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($objectKey));
    $fieldKey = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($field));

    return 'dbobject.' . trim((string)$objectKey, '_') . '.' . trim((string)$fieldKey, '_') . '.' . $suffix;
}

function adminEditFieldOptionTranslationKey($object, string $field, $value): string
{
    return adminEditFieldTranslationKey($object, $field, 'option.' . substr(hash('sha256', (string)$value), 0, 16));
}

function adminEditGetFieldOptionLabel($object, string $field, array $option, array $bundle, array $sourceLang): string
{
    return adminEditTranslate(adminEditFieldOptionTranslationKey($object, $field, $option[0]), [], $object, $bundle, $sourceLang);
}

function adminEditGetFieldLabel($object, string $field, array $bundle, array $sourceLang): string
{
    $labels = is_object($object) && method_exists($object, 'attributeLabels')
        ? (array)$object->attributeLabels()
        : [];
    $fallback = isset($labels[$field]) ? (string)$labels[$field] : $field;

    return adminEditTranslate(adminEditFieldTranslationKey($object, $field, 'label'), [], $object, $bundle, $sourceLang) ?: $fallback;
}

function adminEditGetFieldDescription($object, string $field, array $bundle, array $sourceLang): string
{
    $descriptions = is_object($object) && method_exists($object, 'attributeDescriptions')
        ? (array)$object->attributeDescriptions()
        : [];
    if (!isset($descriptions[$field]) || trim((string)$descriptions[$field]) === '') {
        return '';
    }

    return adminEditTranslate(adminEditFieldTranslationKey($object, $field, 'description'), [], $object, $bundle, $sourceLang);
}

function adminEditGetFieldPlaceholder($object, string $field, array $bundle, array $sourceLang): string
{
    $placeholders = is_object($object) && method_exists($object, 'attributePlaceholder')
        ? (array)$object::attributePlaceholder()
        : [];
    if (!isset($placeholders[$field]) || trim((string)$placeholders[$field]) === '') {
        return '';
    }

    return adminEditTranslate(adminEditFieldTranslationKey($object, $field, 'placeholder'), [], $object, $bundle, $sourceLang);
}

?>
