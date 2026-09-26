<?php
require_once __DIR__ . '/bootstrap.php';

use dbObject\Holon;
use dbObject\HolonPermission;
use dbObject\Organization;
use dbObject\Permission;
use dbObject\User;

$organizationId = (int)(commonGetRequestedOrganizationId() ?: ($_GET['oid'] ?? ($_SESSION['currentOrganization'] ?? 0)));
$currentUserId = (int)commonGetCurrentUserId();

if ($organizationId <= 0) {
    http_response_code(400);
    echo 'Organisation invalide.';
    exit;
}

$organization = new Organization();
if (!$organization->load($organizationId)) {
    http_response_code(404);
    echo 'Organisation introuvable.';
    exit;
}

if (!$organization->canViewDetail()) {
    http_response_code(403);
    echo 'Acces refuse.';
    exit;
}

$currentUser = new User();
$currentUserLoaded = $currentUserId > 0 && $currentUser->load($currentUserId);
$currentUserLabel = $currentUserLoaded
    ? trim((string)$currentUser->getScopedDisplayName($organizationId))
    : '';
if ($currentUserLabel === '' && $currentUserLoaded) {
    $currentUserLabel = trim((string)$currentUser->getScopedEmail($organizationId));
}
if ($currentUserLabel === '') {
    $currentUserLabel = 'Utilisateur inconnu';
}

$debug = HolonPermission::buildPermissionDebugForOrganization($currentUserId, $organizationId);

$holonLabelsById = [];
$holonTypeLabelsById = [];
$collectHolonIds = [];
$collectIds = static function ($value) use (&$collectHolonIds) {
    if (is_array($value)) {
        foreach ($value as $item) {
            $holonId = (int)$item;
            if ($holonId > 0) {
                $collectHolonIds[$holonId] = $holonId;
            }
        }
        return;
    }

    $holonId = (int)$value;
    if ($holonId > 0) {
        $collectHolonIds[$holonId] = $holonId;
    }
};

foreach ((array)($debug['organizationHolonIds'] ?? []) as $holonId) {
    $collectIds($holonId);
}
foreach ((array)($debug['rawUserHolonRows'] ?? []) as $row) {
    $collectIds($row['IDholon'] ?? 0);
}
foreach ((array)($debug['activeUserHolonRows'] ?? []) as $row) {
    $collectIds($row['IDholon'] ?? 0);
}
foreach ((array)($debug['permissionAssignments'] ?? []) as $row) {
    $collectIds($row['IDholon'] ?? 0);
}
foreach ((array)($debug['permissionSourceHolonIdsByAssignedHolonId'] ?? []) as $assignedHolonId => $sourceHolonIds) {
    $collectIds($assignedHolonId);
    $collectIds($sourceHolonIds);
}
foreach ((array)($debug['permissionSet']['permissions'] ?? []) as $scope) {
    $collectIds(array_keys((array)($scope['exact'] ?? [])));
    $collectIds(array_keys((array)($scope['subtree'] ?? [])));
}

foreach (array_values($collectHolonIds) as $holonId) {
    $holon = new Holon();
    if (!$holon->load($holonId)) {
        continue;
    }

    $name = trim((string)$holon->get('name'));
    $typeLabel = trim((string)$holon->getTemplateLabel(true));
    if ($typeLabel === '') {
        $typeLabel = 'Holon';
    }

    $holonLabelsById[$holonId] = $name !== '' ? $name : ('Holon #' . $holonId);
    $holonTypeLabelsById[$holonId] = $typeLabel;
}

$permissionCatalog = [];
foreach (Permission::getEditorCatalog() as $permissionEntry) {
    $permissionKey = trim((string)($permissionEntry['key'] ?? ''));
    if ($permissionKey === '') {
        continue;
    }

    $permissionCatalog[$permissionKey] = $permissionEntry;
}

$assignmentRowsByHolonId = [];
foreach ((array)($debug['permissionAssignments'] ?? []) as $assignmentRow) {
    $assignedHolonId = (int)($assignmentRow['IDholon'] ?? 0);
    $permissionKey = trim((string)($assignmentRow['permission_key'] ?? ''));
    if ($assignedHolonId <= 0 || $permissionKey === '') {
        continue;
    }

    if (!isset($assignmentRowsByHolonId[$assignedHolonId])) {
        $assignmentRowsByHolonId[$assignedHolonId] = [];
    }

    $assignmentRowsByHolonId[$assignedHolonId][] = [
        'permissionKey' => $permissionKey,
        'range' => (string)($assignmentRow['range'] ?? ''),
    ];
}

$formatHolonLabel = static function ($holonId) use ($holonLabelsById, $holonTypeLabelsById) {
    $holonId = (int)$holonId;
    $name = $holonLabelsById[$holonId] ?? ('Holon #' . $holonId);
    $typeLabel = $holonTypeLabelsById[$holonId] ?? 'Holon';
    return $name . ' [' . $typeLabel . '] #' . $holonId;
};

$formatPermissionLabel = static function ($permissionKey) use ($permissionCatalog) {
    $permissionKey = trim((string)$permissionKey);
    if ($permissionKey === '') {
        return '';
    }

    $title = trim((string)($permissionCatalog[$permissionKey]['title'] ?? ''));
    return $title !== '' ? ($title . ' (' . $permissionKey . ')') : $permissionKey;
};

$renderJson = static function ($value) {
    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug permissions</title>
    <link rel="stylesheet" href="<?= commonAssetUrl('/omo/api/debug_permissions.css') ?>">
</head>
<body>
    <div class="debug-wrap">
        <section class="debug-panel">
            <h1>Debug permissions</h1>
            <div class="debug-meta">
                <div><strong>Organisation:</strong> <?= omoApiEscape(trim((string)$organization->get('name'))) ?> (#<?= (int)$organizationId ?>)</div>
                <div><strong>Utilisateur:</strong> <?= omoApiEscape($currentUserLabel) ?> (#<?= (int)$currentUserId ?>)</div>
                <div><strong>Holon racine:</strong> <?= !empty($debug['organizationRootHolonId']) ? omoApiEscape($formatHolonLabel((int)$debug['organizationRootHolonId'])) : 'aucun' ?></div>
            </div>
        </section>

        <section class="debug-panel">
            <h2>Holons de l utilisateur connecte</h2>
            <?php if (count((array)($debug['activeUserHolonRows'] ?? [])) === 0): ?>
                <div class="debug-empty">Aucun holon effectif trouve pour cet utilisateur dans cette organisation.</div>
            <?php else: ?>
                <div class="debug-list">
                    <?php foreach ((array)$debug['activeUserHolonRows'] as $membershipRow): ?>
                        <?php $assignedHolonId = (int)($membershipRow['IDholon'] ?? 0); ?>
                        <div class="debug-item">
                            <strong><?= omoApiEscape($formatHolonLabel($assignedHolonId)) ?></strong>
                            <div class="debug-chip-row">
                                <span class="debug-chip debug-chip--ok">actif</span>
                            </div>
                            <?php $sourceHolonIds = (array)($debug['permissionSourceHolonIdsByAssignedHolonId'][$assignedHolonId] ?? []); ?>
                            <div style="margin-top:10px;">
                                <strong>Holons sources pris en compte pour ses droits:</strong>
                                <?php if (count($sourceHolonIds) === 0): ?>
                                    <div class="debug-empty">aucun</div>
                                <?php else: ?>
                                    <div class="debug-chip-row">
                                        <?php foreach ($sourceHolonIds as $sourceHolonId): ?>
                                            <span class="debug-chip"><?= omoApiEscape($formatHolonLabel((int)$sourceHolonId)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="debug-panel">
            <h2>Arbre des droits</h2>
            <?php if (count((array)($debug['activeUserHolonRows'] ?? [])) === 0): ?>
                <div class="debug-empty">Impossible de construire l arbre sans holon utilisateur effectif.</div>
            <?php else: ?>
                <div class="debug-tree">
                    <?php foreach ((array)$debug['activeUserHolonRows'] as $membershipRow): ?>
                        <?php $assignedHolonId = (int)($membershipRow['IDholon'] ?? 0); ?>
                        <?php $sourceHolonIds = (array)($debug['permissionSourceHolonIdsByAssignedHolonId'][$assignedHolonId] ?? []); ?>
                        <div class="debug-item">
                            <h3><?= omoApiEscape($formatHolonLabel($assignedHolonId)) ?></h3>
                            <?php if (count($sourceHolonIds) === 0): ?>
                                <div class="debug-empty">Aucune source de droits.</div>
                            <?php else: ?>
                                <div class="debug-list">
                                    <?php foreach ($sourceHolonIds as $sourceHolonId): ?>
                                        <?php $sourceHolonId = (int)$sourceHolonId; ?>
                                        <?php $assignmentRows = (array)($assignmentRowsByHolonId[$sourceHolonId] ?? []); ?>
                                        <div class="debug-item">
                                            <strong>Source:</strong> <?= omoApiEscape($formatHolonLabel($sourceHolonId)) ?>
                                            <?php if (count($assignmentRows) === 0): ?>
                                                <div class="debug-empty">Aucun droit defini sur ce holon source.</div>
                                            <?php else: ?>
                                                <div class="debug-chip-row">
                                                    <?php foreach ($assignmentRows as $assignmentRow): ?>
                                                        <span class="debug-chip">
                                                            <?= omoApiEscape($formatPermissionLabel($assignmentRow['permissionKey'] ?? '')) ?>
                                                            <span>-> <?= omoApiEscape((string)($assignmentRow['range'] ?? '')) ?></span>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="debug-panel">
            <h2>Droits calcules</h2>
            <?php $permissionScopes = (array)($debug['permissionSet']['permissions'] ?? []); ?>
            <?php if (count($permissionScopes) === 0): ?>
                <div class="debug-empty">Aucun droit calcule pour cet utilisateur dans cette organisation.</div>
            <?php else: ?>
                <div class="debug-list">
                    <?php foreach ($permissionScopes as $permissionKey => $scope): ?>
                        <?php $scope = is_array($scope) ? $scope : []; ?>
                        <div class="debug-item">
                            <strong><?= omoApiEscape($formatPermissionLabel((string)$permissionKey)) ?></strong>
                            <div class="debug-chip-row">
                                <?php if (!empty($scope['organization'])): ?>
                                    <span class="debug-chip debug-chip--ok">portee: toute l organisation</span>
                                <?php endif; ?>
                                <?php foreach (array_keys((array)($scope['exact'] ?? [])) as $holonId): ?>
                                    <span class="debug-chip">exact: <?= omoApiEscape($formatHolonLabel((int)$holonId)) ?></span>
                                <?php endforeach; ?>
                                <?php foreach (array_keys((array)($scope['subtree'] ?? [])) as $holonId): ?>
                                    <span class="debug-chip">subtree: <?= omoApiEscape($formatHolonLabel((int)$holonId)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="debug-panel">
            <h2>Dump technique</h2>
            <div class="debug-columns">
                <div>
                    <h3>Rebuild</h3>
                    <pre><?= omoApiEscape($renderJson($debug)) ?></pre>
                </div>
                <div>
                    <h3>Cache session</h3>
                    <pre><?= omoApiEscape($renderJson($_SESSION['permissionCacheByOrganization'][$organizationId] ?? null)) ?></pre>
                </div>
            </div>
        </section>
    </div>
</body>
</html>
