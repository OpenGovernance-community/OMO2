<?php

if (!function_exists('commonUserPermissionBuildCatalogMap')) {
    function commonUserPermissionBuildCatalogMap()
    {
        $catalog = [];
        foreach (\dbObject\Permission::getEditorCatalog() as $permissionEntry) {
            $permissionKey = trim((string)($permissionEntry['key'] ?? ''));
            if ($permissionKey === '') {
                continue;
            }

            $catalog[$permissionKey] = $permissionEntry;
        }

        return $catalog;
    }
}

if (!function_exists('commonUserPermissionBuildHolonMetaMap')) {
    function commonUserPermissionBuildHolonMetaMap(array $holonIds)
    {
        $labelsById = [];
        $typeLabelsById = [];

        foreach (array_values(array_unique(array_map('intval', $holonIds))) as $holonId) {
            if ($holonId <= 0) {
                continue;
            }

            $holon = new \dbObject\Holon();
            if (!$holon->load($holonId)) {
                continue;
            }

            $name = trim((string)$holon->get('name'));
            $typeLabel = trim((string)$holon->getTemplateLabel(true));
            if ($typeLabel === '') {
                $typeLabel = 'Holon';
            }

            $labelsById[$holonId] = $name !== '' ? $name : ('Holon #' . $holonId);
            $typeLabelsById[$holonId] = $typeLabel;
        }

        return [
            'labelsById' => $labelsById,
            'typeLabelsById' => $typeLabelsById,
        ];
    }
}

if (!function_exists('commonUserPermissionFormatHolonLabel')) {
    function commonUserPermissionFormatHolonLabel($holonId, array $labelsById, array $typeLabelsById)
    {
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return 'Holon inconnu';
        }

        $name = $labelsById[$holonId] ?? ('Holon #' . $holonId);
        $typeLabel = $typeLabelsById[$holonId] ?? 'Holon';
        return $name . ' [' . $typeLabel . '] #' . $holonId;
    }
}

if (!function_exists('commonUserPermissionDescribeScope')) {
    function commonUserPermissionDescribeScope($scopeType, $scopeHolonId, callable $formatHolonLabel)
    {
        $scopeType = trim((string)$scopeType);
        $scopeHolonId = (int)$scopeHolonId;

        if ($scopeType === 'organization') {
            return 'Toute l organisation';
        }

        if ($scopeType === 'subtree') {
            return $scopeHolonId > 0
                ? 'Sous-arbre de ' . $formatHolonLabel($scopeHolonId)
                : 'Sous-arbre';
        }

        if ($scopeType === 'exact') {
            return $scopeHolonId > 0
                ? 'Element exact: ' . $formatHolonLabel($scopeHolonId)
                : 'Element exact';
        }

        return 'Portee inconnue';
    }
}
