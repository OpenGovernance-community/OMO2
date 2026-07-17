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
    <style>
        :root {
            color-scheme: light;
            --bg: #f5f7fb;
            --panel: #ffffff;
            --border: #d8e0ec;
            --text: #1c2430;
            --muted: #5e6b7a;
            --accent: #0f6cbd;
            --soft: #eef5fc;
            --ok: #1f7a3d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: var(--bg);
            color: var(--text);
            font: 14px/1.5 Arial, sans-serif;
        }

        .debug-wrap {
            display: grid;
            gap: 16px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .debug-panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px 20px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        h1, h2, h3 {
            margin: 0 0 12px 0;
            line-height: 1.2;
        }

        h1 {
            font-size: 26px;
        }

        h2 {
            font-size: 18px;
        }

        h3 {
            font-size: 15px;
        }

        .debug-meta {
            display: grid;
            gap: 8px;
            color: var(--muted);
        }

        .debug-list,
        .debug-tree {
            display: grid;
            gap: 10px;
        }

        .debug-item {
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            background: #fff;
        }

        .debug-item strong {
            color: var(--text);
        }

        .debug-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .debug-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 10px;
            background: var(--soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
        }

        .debug-chip--ok {
            background: #edf9f0;
            color: var(--ok);
        }

        .debug-empty {
            color: var(--muted);
            font-style: italic;
        }

        pre {
            margin: 0;
            padding: 14px;
            border-radius: var(--radius-md);
            background: #0f172a;
            color: #dbe7ff;
            overflow: auto;
            font: 12px/1.45 Consolas, monospace;
        }

        .debug-columns {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
    </style>
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
