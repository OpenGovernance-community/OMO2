<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use dbObject\Organization;

$organizationId = (int)($_SESSION['currentOrganization'] ?? 0);
$contextHolonId = (int)($_GET['cid'] ?? 0);
$holonId = (int)($_GET['hid'] ?? 0);
$organization = new Organization();
$editorData = null;
$errorMessage = '';
$adminLabel = 'Admin';
$adminLabelLower = 'admin';

if ($organizationId <= 0) {
    $errorMessage = "Aucune organisation n'est actuellement sélectionnée.";
} elseif (!$organization->load($organizationId)) {
    $errorMessage = "L'organisation demandée est introuvable.";
} else {
	$organizationLexicon = $organization->getLexicon();
	$adminLabel = trim((string)($organizationLexicon['admin']['label'] ?? '')) ?: 'Admin';
	$adminLabelLower = function_exists('mb_strtolower')
		? mb_strtolower($adminLabel, 'UTF-8')
		: strtolower($adminLabel);
    $editorData = $organization->getHolonCreationEditorData($contextHolonId, $holonId);
    if ($holonId > 0 && (($editorData['mode'] ?? 'create') !== 'edit')) {
        $errorMessage = "Le holon demandé est introuvable.";
    } elseif (($editorData['mode'] ?? 'create') === 'edit' && !($editorData['canEdit'] ?? false)) {
        $errorMessage = "Ce holon ne peut pas être édité avec ce formulaire.";
    } elseif (($editorData['mode'] ?? 'create') !== 'edit' && !($editorData['canCreate'] ?? false)) {
        $errorMessage = "Ce holon n'autorise pas l'ajout d'enfant.";
    } elseif (count($editorData['templateCatalog'] ?? array()) === 0) {
        $errorMessage = ($editorData['mode'] ?? 'create') === 'edit'
            ? "Aucun modèle n'est disponible dans le contexte de ce holon."
            : "Aucun modèle n'est disponible dans ce contexte pour créer un nouveau holon.";
    }
}
?>
<div class="omo-holon-create omo-panel-view">
    <div class="omo-panel-view__header">
        <div class="omo-panel-view__header-copy">
            <h2 class="omo-panel-view__title"><?= omoApiEscape((($editorData['mode'] ?? 'create') === 'edit') ? 'Modifier le holon' : 'Nouveau holon') ?></h2>
            <p class="omo-panel-view__description">
                <?php if (($editorData['mode'] ?? 'create') === 'edit'): ?>
                    Modifiez ici ce holon à partir d'un modèle disponible dans
                    <?= omoApiEscape($editorData['contextHolonName'] ?? '') ?>.
                <?php else: ?>
                    Créez ici un nouveau cercle ou rôle à partir d'un modèle disponible dans
                    <?= omoApiEscape($editorData['contextHolonName'] ?? '') ?>.
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="omo-panel-view__body">
        <?php if ($errorMessage !== ''): ?>
            <div class="omo-holon-create__empty generic-section"><?= omoApiEscape($errorMessage) ?></div>
        <?php else: ?>
            <div class="omo-holon-create__layout" id="omo-holon-create-editor">
                <section class="omo-holon-create__panel generic-drawer-content">
                    <div class="omo-holon-create__status" id="omo-holon-create-status" hidden></div>

                    <form id="omo-holon-create-form" class="omo-holon-create__form generic-form-stack">
                        <div class="omo-panel-view__body_content">
                        <section class="omo-holon-create__section generic-section generic-section--stack generic-form-section">
                            <div class="omo-holon-create__section-title generic-title generic-title--medium"><?= omoApiEscape((($editorData['mode'] ?? 'create') === 'edit') ? 'Édition' : 'Création') ?></div>

                            <div class="omo-holon-create__grid generic-form-grid">
                                <label class="omo-holon-create__field generic-form-field">
                                    <span class="generic-form-label">Modèle</span>
                                    <select id="omo-holon-create-template" class="generic-form-control" required></select>
                                </label>

                                <label class="omo-holon-create__field omo-holon-create__field--full generic-form-field generic-form-field--full">
                                    <span class="generic-form-label">Nom</span>
                                    <input type="text" id="omo-holon-create-name" class="generic-form-control" maxlength="255" required>
                                    <small class="generic-help-text" id="omo-holon-create-name-help"></small>
                                </label>

                                <label class="omo-holon-create__field omo-holon-create__field--full generic-form-field generic-form-field--full">
                                    <span class="generic-form-label">Nom complet</span>
                                    <input type="text" id="omo-holon-create-full-name" class="generic-form-control" maxlength="255">
                                    <small class="generic-help-text">Optionnel. Utilise dans la vue liste et dans la fiche contexte.</small>
                                </label>

                            </div>

                            <div class="omo-holon-create__template-meta" id="omo-holon-create-template-meta"></div>
                        </section>

                        <section class="omo-holon-create__section generic-section generic-section--stack generic-form-section" id="omo-holon-create-admin-bounds-section">
                            <div class="omo-holon-create__section-head generic-form-section__heading">
                                <div class="generic-form-section__copy">
                                    <div class="omo-holon-create__section-title generic-title generic-title--medium"><?= omoApiEscape($adminLabel) ?></div>
                                    <p class="omo-holon-create__section-description generic-description">Ces limites viennent du modele. Elles peuvent etre redefinies uniquement si le modele ne les verrouille pas.</p>
                                </div>
                            </div>
                            <div class="omo-holon-create__admin-bounds generic-form-grid">
                                <label class="omo-holon-create__field generic-form-field">
                                    <span class="omo-holon-create__admin-bound-head">
                                        <span>Minimum de <?= omoApiEscape($adminLabelLower) ?></span>
                                        <span class="omo-holon-create__color-toggle">
                                            <input type="checkbox" id="omo-holon-create-admin-min-override">
                                            <span>Redefinir</span>
                                        </span>
                                    </span>
                                    <input type="number" id="omo-holon-create-admin-min" class="generic-form-control" min="0" step="1">
                                </label>
                                <label class="omo-holon-create__field generic-form-field">
                                    <span class="omo-holon-create__admin-bound-head">
                                        <span>Maximum de <?= omoApiEscape($adminLabelLower) ?></span>
                                        <span class="omo-holon-create__color-toggle">
                                            <input type="checkbox" id="omo-holon-create-admin-max-override">
                                            <span>Redefinir</span>
                                        </span>
                                    </span>
                                    <input type="number" id="omo-holon-create-admin-max" class="generic-form-control" min="0" step="1" placeholder="Sans limite">
                                </label>
                            </div>
                            <small class="generic-help-text" id="omo-holon-create-admin-bounds-help"></small>
                        </section>

                        <section class="omo-holon-create__section generic-section generic-section--stack generic-form-section">
                            <div class="omo-holon-create__section-head generic-form-section__heading">
                                <div class="generic-form-section__copy">
                                    <div class="omo-holon-create__section-title generic-title generic-title--medium">Propriétés</div>
                                    <p class="omo-holon-create__section-description generic-description">
                                        Les propriétés héritées du modèle sont affichées ci-dessous.
                                    </p>
                                </div>
                            </div>

                            <div class="omo-holon-create__properties" id="omo-holon-create-properties"></div>
                            <button type="button" class="generic-action-button generic-action-button--secondary" id="omo-holon-create-add-property">Ajouter une propriete</button>
                        </section>

                        <section class="omo-holon-create__section generic-section generic-section--stack generic-form-section">
                            <div class="omo-holon-create__section-head generic-form-section__heading">
                                <div class="generic-form-section__copy">
                                    <div class="omo-holon-create__section-title generic-title generic-title--medium">Droits</div>
                                    <p class="omo-holon-create__section-description generic-description">
                                        Ce holon peut aussi porter des droits directs pour ses membres.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="generic-action-button generic-action-button--secondary"
                                    id="omo-holon-create-permissions-toggle"
                                    aria-expanded="false"
                                    aria-controls="omo-holon-create-permissions-editor"
                                >Editer</button>
                            </div>

                            <div class="omo-holon-create__permission-summary generic-soft-panel" id="omo-holon-create-permissions-summary">
                                <div class="omo-holon-create__permission-summary-line">
                                    <div class="omo-holon-create__permission-summary-label">Droits herites</div>
                                    <div class="omo-holon-create__permission-summary-empty">aucun</div>
                                </div>
                                <div class="omo-holon-create__permission-summary-line">
                                    <div class="omo-holon-create__permission-summary-label">Droits associes au holon</div>
                                    <div class="omo-holon-create__permission-summary-empty">aucun</div>
                                </div>
                            </div>
                            <div class="omo-holon-create__permissions" id="omo-holon-create-permissions-editor" hidden></div>
                        </section>
                        </div>
                        <section class="omo-holon-create__section generic-section generic-section--stack generic-form-section">
                            <div class="omo-holon-create__section-head generic-form-section__heading">
                                <div class="generic-form-section__copy">
                                    <div class="omo-holon-create__section-title generic-title generic-title--medium">Apparence</div>
                                    <p class="omo-holon-create__section-description generic-description">
                                        Les choix visuels viennent ici, apres les proprietes plus importantes.
                                    </p>
                                </div>
                            </div>

                            <div class="omo-holon-create__grid generic-form-grid">
                                <label class="omo-holon-create__field generic-form-field" id="omo-holon-create-color-field">
                                    <div class="omo-holon-create__color-head">
                                        <span>Couleur</span>
                                        <span class="omo-holon-create__color-toggle">
                                            <input type="checkbox" id="omo-holon-create-color-enabled">
                                            <span>Redefinir</span>
                                        </span>
                                    </div>
                                    <div class="omo-holon-create__color-body" id="omo-holon-create-color-body">
                                        <input type="color" id="omo-holon-create-color" value="#f59e0b">
                                        <small class="generic-help-text">Sinon la couleur reste vide et l'heritage s'applique.</small>
                                    </div>
                                </label>

                                <div class="omo-holon-create__field omo-holon-create__field--full generic-form-field generic-form-field--full">
                                    <span class="generic-form-label">Illustrations</span>
                                    <div class="omo-holon-create__media-grid">
                                        <div class="omo-holon-create__media-card generic-soft-panel generic-soft-panel--stack">
                                            <div class="omo-holon-create__media-label">Icone</div>
                                            <div id="omo-holon-create-icon-field"></div>
                                        </div>
                                        <div class="omo-holon-create__media-card generic-soft-panel generic-soft-panel--stack">
                                            <div class="omo-holon-create__media-label">Banniere</div>
                                            <div id="omo-holon-create-banner-field"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="omo-holon-create__footer generic-section">
                            <div class="omo-holon-create__hint generic-help-text" id="omo-holon-create-hint"></div>
                            <div class="omo-holon-create__actions generic-form-actions generic-form-actions--stack-mobile">
                                <button type="button" class="generic-action-button generic-action-button--secondary" id="omo-holon-create-cancel">Fermer</button>
                                <button type="submit" class="generic-action-button generic-action-button--main"><?= omoApiEscape((($editorData['mode'] ?? 'create') === 'edit') ? 'Enregistrer' : 'Créer le holon') ?></button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($editorData !== null && $errorMessage === ''): ?>
<script src="/omo/assets/js/sized-image-field.js"></script>
<script src="/omo/assets/js/simple-html-field.js?v=20260804-embed-remove-paragraph"></script>
<script src="/common/assets/multiline-list-paste.js"></script>
<script>
(() => {
const state = {
    data: <?= json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    statusTimer: null
};
const adminLexiconLabel = <?= json_encode($adminLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const root = document.getElementById('omo-holon-create-editor');
if (!root) {
    return;
}

const elements = {
    status: root.querySelector('#omo-holon-create-status'),
    form: root.querySelector('#omo-holon-create-form'),
    template: root.querySelector('#omo-holon-create-template'),
    name: root.querySelector('#omo-holon-create-name'),
    fullName: root.querySelector('#omo-holon-create-full-name'),
    colorEnabled: root.querySelector('#omo-holon-create-color-enabled'),
    colorBody: root.querySelector('#omo-holon-create-color-body'),
    color: root.querySelector('#omo-holon-create-color'),
	adminBoundsSection: root.querySelector('#omo-holon-create-admin-bounds-section'),
	adminMin: root.querySelector('#omo-holon-create-admin-min'),
	adminMax: root.querySelector('#omo-holon-create-admin-max'),
	adminMinOverride: root.querySelector('#omo-holon-create-admin-min-override'),
	adminMaxOverride: root.querySelector('#omo-holon-create-admin-max-override'),
	adminBoundsHelp: root.querySelector('#omo-holon-create-admin-bounds-help'),
    iconField: root.querySelector('#omo-holon-create-icon-field'),
    bannerField: root.querySelector('#omo-holon-create-banner-field'),
    meta: root.querySelector('#omo-holon-create-template-meta'),
    properties: root.querySelector('#omo-holon-create-properties'),
    addProperty: root.querySelector('#omo-holon-create-add-property'),
    permissions: root.querySelector('#omo-holon-create-permissions-editor'),
    permissionSummary: root.querySelector('#omo-holon-create-permissions-summary'),
    permissionToggle: root.querySelector('#omo-holon-create-permissions-toggle'),
    hint: root.querySelector('#omo-holon-create-hint'),
    nameHelp: root.querySelector('#omo-holon-create-name-help'),
    cancel: root.querySelector('#omo-holon-create-cancel')
};

const mediaFields = {
    icon: null,
    banner: null
};

function waitForGlobalLibrary(globalKey, timeoutMs) {
    const key = String(globalKey || '').trim();
    const maxWait = Number(timeoutMs || 4000);
    if (key !== '' && window[key]) {
        return Promise.resolve(true);
    }

    return new Promise(function (resolve) {
        const startedAt = Date.now();

        function checkAvailability() {
            if (key !== '' && window[key]) {
                resolve(true);
                return;
            }

            if (Date.now() - startedAt >= maxWait) {
                resolve(false);
                return;
            }

            window.setTimeout(checkAvailability, 30);
        }

        checkAvailability();
    });
}

// Échappe texte HTML
function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Parse valeurs liste
function parseStoredListValue(value) {
    const rawValue = value !== undefined && value !== null ? String(value) : '';
    if (!rawValue.trim()) {
        return [];
    }

    try {
        const decoded = JSON.parse(rawValue);
        return Array.isArray(decoded) ? decoded : [];
    } catch (error) {
        return rawValue.split(/\r\n|\r|\n|\|/).map(function (item) {
            return item.trim();
        }).filter(Boolean);
    }
}

function renderHtmlPreview(value, className) {
    if (window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.renderPreviewHtml === 'function') {
        return window.omoSimpleHtmlField.renderPreviewHtml(value, className);
    }

    return '<div class="' + escapeHtml(className || 'omo-holon-create__inherited-text generic-meta') + '">' + escapeHtml(value || '').replace(/\n/g, '<br>') + '</div>';
}

// Liste les modèles
function getPermissionCatalog() {
    return Array.isArray(state.data.permissionCatalog) ? state.data.permissionCatalog : [];
}

function getPermissionRangeOptions() {
    return Array.isArray(state.data.permissionRanges) ? state.data.permissionRanges : [];
}

function getInheritedPermissions() {
    const editingHolon = getEditingHolon();
    return editingHolon && editingHolon.inheritedPermissions && typeof editingHolon.inheritedPermissions === 'object'
        ? editingHolon.inheritedPermissions
        : {};
}

function normalizePermissionRanges(value) {
    const ranges = Array.isArray(value) ? value : (String(value || '').trim() !== '' ? [value] : []);
    const normalized = [];
    const seen = new Set();

    ranges.forEach(function (range) {
        const normalizedRange = String(range || '').trim();
        if (!normalizedRange || seen.has(normalizedRange)) {
            return;
        }

        seen.add(normalizedRange);
        normalized.push(normalizedRange);
    });

    return normalized;
}

function getPermissionRangeLabel(rangeKey, rangeOptions) {
    const range = (rangeOptions || []).find(function (item) {
        return String(item.key || '') === String(rangeKey || '');
    });

    return range ? String(range.label || range.key || '') : String(rangeKey || '');
}

function readPermissions() {
    if (!elements.permissions) {
        return {};
    }

    const assignments = { member: {}, admin: {} };
    Array.from(elements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
        const profileKey = String(row.getAttribute('data-permission-profile') || 'member');
        const permissionKey = String(row.getAttribute('data-permission-key') || '').trim();
        if (!permissionKey) {
            return;
        }

        const selectedRanges = Array.from(row.querySelectorAll('[data-permission-token]')).map(function (token) {
            return String(token.getAttribute('data-permission-token') || '').trim();
        }).filter(function (range) {
            return range !== '';
        });

        if (selectedRanges.length) {
            assignments[profileKey][permissionKey] = selectedRanges;
        }
    });

    return assignments;
}

function normalizePermissionProfiles(value) {
    const source = value && typeof value === 'object' ? value : {};
    const hasProfiles = Object.prototype.hasOwnProperty.call(source, 'member') || Object.prototype.hasOwnProperty.call(source, 'admin');
    return {
        member: hasProfiles && source.member && typeof source.member === 'object' ? source.member : (hasProfiles ? {} : source),
        admin: hasProfiles && source.admin && typeof source.admin === 'object' ? source.admin : {}
    };
}

function buildPermissionSummary(assignments) {
    const permissionCatalog = getPermissionCatalog();
    const titles = Object.keys(assignments || {}).map(function (permissionKey) {
        const permission = permissionCatalog.find(function (item) {
            return String(item && item.key ? item.key : '') === String(permissionKey || '');
        });

        return permission ? String(permission.title || permission.key || '').trim() : String(permissionKey || '').trim();
    }).filter(Boolean).sort(function (left, right) {
        return left.localeCompare(right, 'fr', { sensitivity: 'base' });
    });

    if (!titles.length) {
        return 'Droits associes au holon: aucun';
    }

    return 'Droits associes au holon: ' + titles.join(', ');
}

function getPermissionTitle(permissionKey) {
    const permissionCatalog = getPermissionCatalog();
    const permission = permissionCatalog.find(function (item) {
        return String(item && item.key ? item.key : '') === String(permissionKey || '');
    });

    return permission ? String(permission.title || permission.key || '').trim() : String(permissionKey || '').trim();
}

function renderPermissionSummaryCapsules(items, emptyText) {
    if (!Array.isArray(items) || !items.length) {
        return '<div class="omo-holon-create__permission-summary-empty">' + escapeHtml(emptyText || 'aucun') + '</div>';
    }

    return '<div class="omo-holon-create__permission-summary-capsules">'
        + items.map(function (item) {
            return ''
                + '<div class="omo-holon-create__permission-pill">'
                + '  <div class="omo-holon-create__permission-pill-title">' + escapeHtml(String(item.title || '')) + '</div>'
                + '  <div class="omo-holon-create__permission-pill-scope">' + escapeHtml(String(item.scope || '')) + '</div>'
                + '</div>';
        }).join('')
        + '</div>';
}

function buildLocalPermissionSummaryItems(assignments) {
    const defaultRangeOptions = getPermissionRangeOptions();

    return Object.keys(assignments || {}).map(function (permissionKey) {
        const ranges = normalizePermissionRanges(assignments[permissionKey]);
        if (!ranges.length) {
            return null;
        }

        const permissionCatalog = getPermissionCatalog();
        const permission = permissionCatalog.find(function (item) {
            return String(item && item.key ? item.key : '') === String(permissionKey || '');
        });
        const rangeOptions = permission && Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
            ? permission.rangeOptions
            : defaultRangeOptions;

        return {
            title: getPermissionTitle(permissionKey),
            scope: ranges.map(function (rangeKey) {
                return getPermissionRangeLabel(rangeKey, rangeOptions);
            }).filter(Boolean).join(' · ')
        };
    }).filter(Boolean).sort(function (left, right) {
        return String(left.title || '').localeCompare(String(right.title || ''), 'fr', { sensitivity: 'base' });
    });
}

function buildInheritedPermissionSummary(inheritedPermissions) {
    return Object.keys(inheritedPermissions || {}).map(function (permissionKey) {
        const permission = inheritedPermissions && inheritedPermissions[permissionKey] ? inheritedPermissions[permissionKey] : null;
        const visibleItems = permission && Array.isArray(permission.visibleItems) ? permission.visibleItems : [];

        return {
            title: permission ? String(permission.name || permission.shortname || permissionKey || '').trim() : String(permissionKey || '').trim(),
            scope: visibleItems.map(function (item) {
                return String(item && item.label ? item.label : '').trim();
            }).filter(Boolean).join(' · ')
        };
    }).filter(function (item) {
        return String(item.title || '').trim() !== '';
    }).sort(function (left, right) {
        return String(left.title || '').localeCompare(String(right.title || ''), 'fr', { sensitivity: 'base' });
    });
}

function buildLocalPermissionSummaryItems(assignments) {
    const defaultRangeOptions = getPermissionRangeOptions();
    const profiles = normalizePermissionProfiles(assignments);
    const profileLabels = { member: 'Membres', admin: adminLexiconLabel };
    const items = [];

    Object.keys(profiles).forEach(function (profileKey) {
        Object.keys(profiles[profileKey] || {}).forEach(function (permissionKey) {
            const ranges = normalizePermissionRanges(profiles[profileKey][permissionKey]);
            if (!ranges.length) {
                return;
            }

            const permission = getPermissionCatalog().find(function (item) {
                return String(item && item.key ? item.key : '') === String(permissionKey || '');
            });
            const rangeOptions = permission && Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
                ? permission.rangeOptions
                : defaultRangeOptions;

            items.push({
                title: getPermissionTitle(permissionKey) + ' (' + profileLabels[profileKey] + ')',
                scope: ranges.map(function (rangeKey) {
                    return getPermissionRangeLabel(rangeKey, rangeOptions);
                }).filter(Boolean).join(' / ')
            });
        });
    });

    return items.sort(function (left, right) {
        return String(left.title || '').localeCompare(String(right.title || ''), 'fr', { sensitivity: 'base' });
    });
}

function buildInheritedPermissionSummary(inheritedPermissions) {
    const profiles = normalizePermissionProfiles(inheritedPermissions);
    const profileLabels = { member: 'Membres', admin: adminLexiconLabel };
    const items = [];

    Object.keys(profiles).forEach(function (profileKey) {
        Object.keys(profiles[profileKey] || {}).forEach(function (permissionKey) {
            const permission = profiles[profileKey][permissionKey] || null;
            const visibleItems = permission && Array.isArray(permission.visibleItems) ? permission.visibleItems : [];
            const title = permission ? String(permission.name || permission.shortname || permissionKey || '').trim() : String(permissionKey || '').trim();
            if (!title) {
                return;
            }

            items.push({
                title: title + ' (' + profileLabels[profileKey] + ')',
                scope: visibleItems.map(function (item) {
                    return String(item && item.label ? item.label : '').trim();
                }).filter(Boolean).join(' / ')
            });
        });
    });

    return items.sort(function (left, right) {
        return String(left.title || '').localeCompare(String(right.title || ''), 'fr', { sensitivity: 'base' });
    });
}

function syncPermissionSummary() {
    if (!elements.permissionSummary) {
        return;
    }

    const permissionCatalog = getPermissionCatalog();
    if (!permissionCatalog.length) {
        elements.permissionSummary.innerHTML = ''
            + '<div class="omo-holon-create__permission-summary-line">'
            + '  <div class="omo-holon-create__permission-summary-label">Droits herites</div>'
            + '  <div class="omo-holon-create__permission-summary-empty">aucun droit disponible</div>'
            + '</div>'
            + '<div class="omo-holon-create__permission-summary-line">'
            + '  <div class="omo-holon-create__permission-summary-label">Droits associes au holon</div>'
            + '  <div class="omo-holon-create__permission-summary-empty">aucun droit disponible</div>'
            + '</div>';
        return;
    }

    const inheritedItems = buildInheritedPermissionSummary(getInheritedPermissions());
    const localItems = buildLocalPermissionSummaryItems(readPermissions());
    elements.permissionSummary.innerHTML = ''
        + '<div class="omo-holon-create__permission-summary-line">'
        + '  <div class="omo-holon-create__permission-summary-label">Droits herites</div>'
        +      renderPermissionSummaryCapsules(inheritedItems, 'aucun')
        + '</div>'
        + '<div class="omo-holon-create__permission-summary-line">'
        + '  <div class="omo-holon-create__permission-summary-label">Droits associes au holon</div>'
        +      renderPermissionSummaryCapsules(localItems, 'aucun')
        + '</div>';
}

function setPermissionEditorExpanded(isExpanded) {
    if (elements.permissions) {
        elements.permissions.hidden = !isExpanded;
    }

    if (elements.permissionToggle) {
        elements.permissionToggle.textContent = isExpanded ? 'Fermer' : 'Editer';
        elements.permissionToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    }
}

function setPermissionRowRanges(row, selectedRanges, rangeOptions) {
    const tokensContainer = row.querySelector('[data-permission-tokens]');
    const select = row.querySelector('[data-permission-select]');
    const normalizedRanges = normalizePermissionRanges(selectedRanges);

    if (!tokensContainer) {
        return;
    }

    if (!normalizedRanges.length) {
        tokensContainer.innerHTML = '<span class="omo-holon-create__permission-empty">Aucune portee selectionnee.</span>';
    } else {
        tokensContainer.innerHTML = normalizedRanges.map(function (rangeKey) {
            return ''
                + '<span class="omo-holon-create__permission-token" data-permission-token="' + escapeHtml(rangeKey) + '">'
                + '  <span>' + escapeHtml(getPermissionRangeLabel(rangeKey, rangeOptions)) + '</span>'
                + '  <button type="button" class="omo-holon-create__permission-token-remove" data-permission-remove="' + escapeHtml(rangeKey) + '" aria-label="Retirer cette portee">&times;</button>'
                + '</span>';
        }).join('');
    }

    if (select) {
        select.value = '';
    }

    syncPermissionSummary();
}

function bindPermissionRow(row, rangeOptions) {
    const select = row.querySelector('[data-permission-select]');
    if (!select || String(select.dataset.bound || '') === '1') {
        return;
    }

    select.dataset.bound = '1';
    select.addEventListener('change', function () {
        const nextRange = String(select.value || '').trim();
        if (!nextRange) {
            return;
        }

        const currentRanges = Array.from(row.querySelectorAll('[data-permission-token]')).map(function (token) {
            return String(token.getAttribute('data-permission-token') || '').trim();
        });
        currentRanges.push(nextRange);
        setPermissionRowRanges(row, currentRanges, rangeOptions);
    });

    row.addEventListener('click', function (event) {
        const removeButton = event.target instanceof Element
            ? event.target.closest('[data-permission-remove]')
            : null;
        if (!removeButton) {
            return;
        }

        const removedRange = String(removeButton.getAttribute('data-permission-remove') || '').trim();
        const remainingRanges = Array.from(row.querySelectorAll('[data-permission-token]')).map(function (token) {
            return String(token.getAttribute('data-permission-token') || '').trim();
        }).filter(function (range) {
            return range !== '' && range !== removedRange;
        });

        setPermissionRowRanges(row, remainingRanges, rangeOptions);
    });
}

function renderPermissions(permissionAssignments) {
    const permissionCatalog = getPermissionCatalog();
    const defaultRangeOptions = getPermissionRangeOptions();
    const assignments = normalizePermissionProfiles(permissionAssignments);
    const profiles = [
        { key: 'member', label: 'Membres' },
        { key: 'admin', label: adminLexiconLabel }
    ];
    const permissionGroups = groupPermissionCatalog(permissionCatalog);

    if (!elements.permissions) {
        return;
    }

    if (!permissionCatalog.length) {
        elements.permissions.innerHTML = '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Aucun droit n est disponible.</div>';
        syncPermissionSummary();
        return;
    }

    let html = '<div class="omo-holon-create__permission-tabs" role="tablist">';
    profiles.forEach(function (profile, index) {
        html += '<button type="button" class="omo-holon-create__permission-tab' + (index === 0 ? ' is-active' : '') + '" data-permission-profile-tab="' + profile.key + '" role="tab" aria-selected="' + (index === 0 ? 'true' : 'false') + '">' + escapeHtml(profile.label) + '</button>';
    });
    html += '</div>';

    profiles.forEach(function (profile, profileIndex) {
        html += '<div class="omo-holon-create__permission-panel" data-permission-profile-panel="' + profile.key + '" role="tabpanel"' + (profileIndex === 0 ? '' : ' hidden') + '>';
        permissionGroups.forEach(function (group) {
            html += '<section class="omo-holon-create__permission-group" data-permission-group="' + escapeHtml(group.key) + '">'
                + '<div class="omo-holon-create__permission-group-title">' + escapeHtml(group.title) + '</div>'
                + '<div class="omo-holon-create__permission-table">';
            group.permissions.forEach(function (permission) {
                const permissionRangeOptions = Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
                    ? permission.rangeOptions
                    : defaultRangeOptions;

                html += '<div class="omo-holon-create__permission-row" data-permission-profile="' + profile.key + '" data-permission-key="' + escapeHtml(permission.key) + '">'
                    + '<div class="omo-holon-create__permission-main">'
                    + '<div class="omo-holon-create__permission-title">' + escapeHtml(permission.title || permission.key) + '</div>'
                    + '<div class="omo-holon-create__permission-meta">' + escapeHtml(permission.key) + '</div>';

                if (String(permission.description || '').trim() !== '') {
                    html += '<div class="omo-holon-create__permission-description">' + escapeHtml(permission.description) + '</div>';
                }

                html += '</div><div class="omo-holon-create__permission-picker">'
                    + '<div class="omo-holon-create__permission-tokens" data-permission-tokens></div>'
                    + '<select class="omo-holon-create__permission-select generic-form-control" data-permission-select>'
                    + '<option value="">Ajouter une portee...</option>';

                permissionRangeOptions.forEach(function (range) {
                    html += '<option value="' + escapeHtml(range.key) + '">' + escapeHtml(range.label || range.key) + '</option>';
                });

                html += '</select></div></div>';
            });
            html += '</div></section>';
        });
        html += '</div>';
    });

    elements.permissions.innerHTML = html;
    Array.from(elements.permissions.querySelectorAll('[data-permission-profile-tab]')).forEach(function (tab) {
        tab.addEventListener('click', function () {
            const profileKey = String(tab.getAttribute('data-permission-profile-tab') || 'member');
            Array.from(elements.permissions.querySelectorAll('[data-permission-profile-tab]')).forEach(function (otherTab) {
                const isActive = otherTab === tab;
                otherTab.classList.toggle('is-active', isActive);
                otherTab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            Array.from(elements.permissions.querySelectorAll('[data-permission-profile-panel]')).forEach(function (panel) {
                panel.hidden = String(panel.getAttribute('data-permission-profile-panel') || '') !== profileKey;
            });
        });
    });

    Array.from(elements.permissions.querySelectorAll('[data-permission-key]')).forEach(function (row) {
        const permissionKey = String(row.getAttribute('data-permission-key') || '').trim();
        const profileKey = String(row.getAttribute('data-permission-profile') || 'member');
        const permission = permissionCatalog.find(function (item) {
            return String(item && item.key ? item.key : '') === permissionKey;
        }) || null;
        const permissionRangeOptions = permission && Array.isArray(permission.rangeOptions) && permission.rangeOptions.length
            ? permission.rangeOptions
            : defaultRangeOptions;

        bindPermissionRow(row, permissionRangeOptions);
        setPermissionRowRanges(row, assignments[profileKey][permissionKey], permissionRangeOptions);
    });

    syncPermissionSummary();
}

function groupPermissionCatalog(permissionCatalog) {
    const groupsByKey = {};
    const groups = [];

    permissionCatalog.forEach(function (permission) {
        const key = String(permission && permission.group ? permission.group : 'other');
        if (!groupsByKey[key]) {
            groupsByKey[key] = {
                key: key,
                title: String(permission && permission.groupTitle ? permission.groupTitle : 'Autres droits'),
                order: Number(permission && permission.groupOrder ? permission.groupOrder : 999),
                permissions: []
            };
            groups.push(groupsByKey[key]);
        }
        groupsByKey[key].permissions.push(permission);
    });

    return groups.sort(function (left, right) {
        return left.order - right.order || left.title.localeCompare(right.title, 'fr', { sensitivity: 'base' });
    });
}

function getTemplates() {
    return Array.isArray(state.data.templateCatalog) ? state.data.templateCatalog : [];
}

// Liste les holons
function getHolonCatalog() {
    return Array.isArray(state.data.holonCatalog) ? state.data.holonCatalog : [];
}

function getProjectCatalog() {
    return Array.isArray(state.data.projectCatalog) ? state.data.projectCatalog : [];
}

function getAuthorityCatalog() {
    return Array.isArray(state.data.authorityCatalog) ? state.data.authorityCatalog : [];
}

function getAuthorityParentCatalog() {
    return Array.isArray(state.data.authorityParentCatalog) ? state.data.authorityParentCatalog : [];
}

function canCreateRootAuthority() {
    return Boolean(state.data.authorityCanCreateRoot);
}

// Trouve un modèle
function findTemplate(templateId) {
    return getTemplates().find(function (template) {
        return Number(template.id || 0) === Number(templateId || 0);
    }) || null;
}

// Lit modèle courant
function getCurrentTemplate() {
    return findTemplate(elements.template.value || 0);
}

// Lit mode courant
function getMode() {
    return String(state.data.mode || 'create');
}

function isTemplateEditing() {
    return false;
}


// Lit holon édité
function getEditingHolon() {
    return state.data && state.data.holon && typeof state.data.holon === 'object'
        ? state.data.holon
        : null;
}

// Synchronise nom verrouille
function syncNameField(template) {
    const editingHolon = getEditingHolon();
    const isLocked = Boolean((editingHolon && editingHolon.nameLocked) || (template && template.lockedName));
    const isUnique = Boolean(template && template.unique);

    if (!elements.name) {
        return;
    }

    if (isLocked) {
        elements.name.dataset.unlockedValue = String(elements.name.value || '');
        elements.name.value = template ? String(template.name || '') : String((editingHolon && editingHolon.name) || '');
        elements.name.disabled = true;
        elements.name.required = false;
        if (elements.nameHelp) {
            elements.nameHelp.textContent = 'Le nom est verrouille par le modele.';
        }
        return;
    }

    if (elements.name.disabled) {
        elements.name.value = getMode() === 'edit' && editingHolon
            ? String(editingHolon.name || '')
            : String(elements.name.dataset.unlockedValue || '');
    }

    elements.name.disabled = false;
    elements.name.required = !isUnique;
    if (elements.nameHelp) {
        elements.nameHelp.textContent = isUnique
            ? 'Si le nom est vide, celui du modele sera utilise.'
            : '';
    }
}

// Synchronise champ couleur
function syncColorField() {
    const isEnabled = Boolean(elements.colorEnabled && elements.colorEnabled.checked);

    if (elements.colorBody) {
        elements.colorBody.hidden = !isEnabled;
    }

    if (elements.color) {
        elements.color.disabled = !isEnabled;
    }
}

function normalizeAdminBound(value, allowEmpty) {
    if (allowEmpty && (value === null || value === undefined || String(value).trim() === '')) {
        return null;
    }

    return Math.max(0, Number(value || 0) || 0);
}

function syncAdminBounds(template) {
    const editingHolon = getEditingHolon();
    const minLocked = Boolean(template && template.lockedAdminMin);
    const maxLocked = Boolean(template && template.lockedAdminMax);
    const minOverridden = !minLocked && Boolean(editingHolon && editingHolon.adminMinOverride);
    const maxOverridden = !maxLocked && Boolean(editingHolon && editingHolon.adminMaxOverride);
    const minValue = minOverridden && editingHolon
        ? normalizeAdminBound(editingHolon.adminMin, false)
        : normalizeAdminBound(template && template.adminMin, false);
    const maxValue = maxOverridden && editingHolon
        ? normalizeAdminBound(editingHolon.adminMax, true)
        : normalizeAdminBound(template && template.adminMax, true);

    if (elements.adminBoundsSection) {
        elements.adminBoundsSection.hidden = !template;
    }
    if (elements.adminMinOverride) {
        elements.adminMinOverride.checked = minOverridden;
        elements.adminMinOverride.disabled = minLocked;
    }
    if (elements.adminMaxOverride) {
        elements.adminMaxOverride.checked = maxOverridden;
        elements.adminMaxOverride.disabled = maxLocked;
    }
    if (elements.adminMin) {
        elements.adminMin.value = String(minValue);
        elements.adminMin.disabled = minLocked || !minOverridden;
    }
    if (elements.adminMax) {
        elements.adminMax.value = maxValue === null ? '' : String(maxValue);
        elements.adminMax.disabled = maxLocked || !maxOverridden;
    }
    if (elements.adminBoundsHelp) {
        const locked = [];
        if (minLocked) {
            locked.push('minimum verrouille');
        }
        if (maxLocked) {
            locked.push('maximum verrouille');
        }
        elements.adminBoundsHelp.textContent = locked.length
            ? 'Le modele impose le ' + locked.join(' et le ') + '.'
            : 'Cochez Redefinir pour appliquer une limite propre a ce holon.';
    }
}

function getMediaDisplayConfig(kind) {
    if (kind === 'banner') {
        return {
            displayWidth: 360,
            displayHeight: 202,
            targetWidth: 960,
            targetHeight: 540,
            emptyText: 'Aucune bannière définie pour ce holon.'
        };
    }

    return {
        displayWidth: 160,
        displayHeight: 160,
        targetWidth: 500,
        targetHeight: 500,
        emptyText: 'Aucune icône définie pour ce holon.'
    };
}

function resolveMediaState(kind, template) {
    const editingHolon = getEditingHolon();
    const suffix = kind === 'icon' ? 'Icon' : 'Banner';
    const locked = Boolean(template && template['effectiveLocked' + suffix]);
    const currentController = mediaFields[kind];
    const fallbackLocalValue = editingHolon && !locked
        ? String(editingHolon[kind] || '')
        : '';

    return {
        value: locked
            ? ''
            : (currentController ? currentController.getValue() : fallbackLocalValue),
        inheritedValue: template ? String(template['effective' + suffix] || '') : '',
        locked: locked
    };
}

function renderMediaFields(template) {
    if (!window.omoSizedImageField) {
        return;
    }

    [
        ['icon', elements.iconField, 'Icône'],
        ['banner', elements.bannerField, 'Bannière']
    ].forEach(function (entry) {
        const kind = entry[0];
        const target = entry[1];
        const label = entry[2];
        if (!target) {
            return;
        }

        const mediaState = resolveMediaState(kind, template);
        const config = getMediaDisplayConfig(kind);
        mediaFields[kind] = window.omoSizedImageField.mount(target, {
            inputName: 'holon_' + kind,
            uploadFieldName: kind,
            value: mediaState.value,
            inheritedValue: mediaState.inheritedValue,
            locked: mediaState.locked,
            displayWidth: config.displayWidth,
            displayHeight: config.displayHeight,
            targetWidth: config.targetWidth,
            targetHeight: config.targetHeight,
            emptyText: config.emptyText,
            labels: {
                choose: 'Choisir une ' + label.toLowerCase(),
                clear: 'Effacer',
                zoom: 'Zoom'
            }
        });
    });
}

// Déduit type liste
function getListInputType(listItemType) {
    if (String(listItemType || 'text') === 'number') {
        return 'number';
    }
    if (String(listItemType || 'text') === 'date') {
        return 'date';
    }
    return 'text';
}

function normalizeDetailedListItem(item) {
    if (item && typeof item === 'object' && !Array.isArray(item)) {
        return {
            title: String(item.title || item.label || item.value || '').trim(),
            description: String(item.description || item.text || '').trim()
        };
    }

    return {
        title: String(item || '').trim(),
        description: ''
    };
}

// Rend ligne liste
function renderSimpleListRow(listItemType, value) {
    if (String(listItemType || 'text') === 'detail') {
        const detailItem = normalizeDetailedListItem(value);
        return ''
            + '<div class="omo-holon-create__list-row omo-holon-create__list-row--detail">'
            + '  <div class="omo-holon-create__list-detail-fields">'
            + '      <input type="text" class="omo-holon-create__property-value-item omo-holon-create__property-value-item--detail-title generic-form-control" value="' + escapeHtml(detailItem.title) + '" placeholder="Titre">'
            + '      <textarea class="omo-holon-create__property-value-item omo-holon-create__property-value-item--detail-description generic-form-control" rows="3" placeholder="Description">' + escapeHtml(detailItem.description) + '</textarea>'
            + '  </div>'
            + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-move" data-list-move="-1" aria-label="Monter">&#8593;</button>'
            + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-move" data-list-move="1" aria-label="Descendre">&#8595;</button>'
            + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-remove" data-list-remove="1" aria-label="Retirer">&times;</button>'
            + '</div>';
    }

    const inputType = getListInputType(listItemType);
    const stepAttribute = inputType === 'number' ? ' step="any"' : '';
    return ''
        + '<div class="omo-holon-create__list-row">'
        + '  <input type="' + inputType + '" class="omo-holon-create__property-value-item generic-form-control" value="' + escapeHtml(value !== undefined && value !== null ? value : '') + '"' + stepAttribute + '>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-move" data-list-move="-1" aria-label="Monter">&#8593;</button>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-move" data-list-move="1" aria-label="Descendre">&#8595;</button>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost omo-holon-create__list-remove" data-list-remove="1" aria-label="Retirer">&times;</button>'
        + '</div>';
}

// Rend saisie liste
function renderSimpleListInput(listItemType, values) {
    const rows = Array.isArray(values) && values.length ? values : [''];
    return ''
        + '<div class="omo-holon-create__list" data-list-item-type="' + escapeHtml(listItemType) + '">'
        + '  <div class="omo-holon-create__list-items">'
        + rows.map(function (item) {
            return renderSimpleListRow(listItemType, item);
        }).join('')
        + '  </div>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--secondary omo-holon-create__list-add" data-list-add="1">Ajouter une valeur</button>'
        + '</div>';
}

function getAuthorityId(item) {
    return item && typeof item === 'object' && !Array.isArray(item)
        ? Number(item.id || 0)
        : Number(item || 0);
}

function getAuthorityDeletionImpact(authorityId, authorityDisposition, childrenDisposition) {
    const catalog = getAuthorityCatalog();
    const descendants = [];
    const knownIds = {};
    let pendingIds = [Number(authorityId || 0)];
    while (pendingIds.length) {
        const parentId = pendingIds.shift();
        catalog.forEach(function (entry) {
            const entryId = Number(entry.id || 0);
            if (Number(entry.parentId || 0) !== parentId || entryId <= 0 || knownIds[entryId]) {
                return;
            }
            knownIds[entryId] = true;
            descendants.push(entry);
            pendingIds.push(entryId);
        });
    }

    const affectedAuthorities = [];
    if (authorityDisposition === 'delete') {
        const authority = catalog.find(function (entry) {
            return Number(entry.id || 0) === Number(authorityId || 0);
        });
        if (authority) {
            affectedAuthorities.push(authority);
        }
    }
    if (childrenDisposition === 'delete') {
        descendants.forEach(function (entry) {
            affectedAuthorities.push(entry);
        });
    }

    return {
        descendants: descendants.length,
        rules: affectedAuthorities.reduce(function (total, entry) {
            return total + Number(entry.ruleCount || 0);
        }, 0)
    };
}

function formatAuthorityDeletionCount(count, singular, plural) {
    return ' (' + String(count) + ' ' + (count === 1 ? singular : plural) + ')';
}

function updateAuthorityDeletionCounts(authorityRow) {
    const authorityId = Number(authorityRow && authorityRow.getAttribute('data-authority-id') || 0);
    if (authorityId <= 0) {
        return;
    }
    const getChoice = function (name, fallback) {
        const checked = authorityRow.querySelector('[data-authority-deletion-choice="' + name + '"]:checked');
        return checked ? String(checked.value || fallback) : fallback;
    };
    const impact = getAuthorityDeletionImpact(
        authorityId,
        getChoice('authority', 'reassign'),
        getChoice('children', 'reassign')
    );
    const counts = {
        authority: formatAuthorityDeletionCount(1, 'autorite', 'autorites'),
        children: formatAuthorityDeletionCount(impact.descendants, 'sous-autorite', 'sous-autorites'),
        rules: formatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees')
    };
    Object.keys(counts).forEach(function (name) {
        authorityRow.querySelectorAll('[data-authority-deletion-count="' + name + '"]').forEach(function (element) {
            element.textContent = counts[name];
        });
    });
    authorityRow.querySelectorAll('[data-authority-deletion-group="rules"]').forEach(function (element) {
        element.hidden = impact.rules <= 0;
    });
}

function getAuthorityEntryPayload(authorityRow) {
    const authorityId = Number(authorityRow.getAttribute('data-authority-id') || 0);
    const authority = authorityId > 0 ? getAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === authorityId;
    }) : null;
    if (authorityId > 0 && authorityRow.getAttribute('data-authority-delete') === '1') {
        const getDeletionChoice = function (name, fallback) {
            const checked = authorityRow.querySelector('[data-authority-deletion-choice="' + name + '"]:checked');
            return checked ? String(checked.value || fallback) : fallback;
        };
        return {
            id: authorityId,
            delete: true,
            deletionPlan: {
                authority: getDeletionChoice('authority', 'reassign'),
                children: getDeletionChoice('children', 'reassign'),
                rules: getDeletionChoice('rules', 'reassign')
            }
        };
    }

    const labelField = authorityRow.querySelector('.omo-holon-create__authority-label');
    const parentField = authorityRow.querySelector('.omo-holon-create__authority-parent');
    const descriptionField = authorityRow.querySelector('.omo-holon-create__authority-description');
    const delegationField = authorityRow.querySelector('.omo-holon-create__authority-delegation');
    if (authorityId > 0 && !labelField && !parentField && !descriptionField) {
        return { id: authorityId };
    }

    const label = String(labelField && labelField.value ? labelField.value : '').trim();
    const parentId = Number(parentField && parentField.value ? parentField.value : 0);
    let description = String(descriptionField && descriptionField.value ? descriptionField.value : '').trim();
    if (authority && authority.needsParent && parentId <= 0) {
        description = '[OMO1_IMPORT_NEEDS_PARENT] ' + description;
    }
    const delegationMode = String(delegationField && delegationField.value ? delegationField.value : 'partial');
    if (authorityId > 0) {
        return { id: authorityId, label: label, parentId: parentId, description: description };
    }

    if (delegationMode === 'complete') {
        return parentId > 0 ? { parentId: parentId, delegationMode: 'complete' } : null;
    }
    return label !== '' || parentId > 0 || description !== '' ? { label: label, parentId: parentId, description: description, delegationMode: 'partial' } : null;
}

function renderAuthorityDeletionChoices(authorityId, draft) {
    const plan = draft && draft.deletionPlan && typeof draft.deletionPlan === 'object' ? draft.deletionPlan : {};
    const checked = function (name, value, fallback) {
        return String(plan[name] || fallback) === value ? ' checked' : '';
    };
    const prefix = 'authority-delete-' + String(authorityId);
    const impact = getAuthorityDeletionImpact(authorityId, String(plan.authority || 'reassign'), String(plan.children || 'reassign'));
    return ''
        + '<div class="omo-holon-create__authority-deletion" data-authority-deletion-options>'
        + '  <p>Choisissez ce qui doit etre conserve avant validation.</p>'
        + '  <fieldset><legend>Cette autorite</legend>'
        + '    <label><input type="radio" name="' + prefix + '-authority" value="delete" data-authority-deletion-choice="authority"' + checked('authority', 'delete', 'reassign') + '> Supprimer definitivement</label>'
        + '    <label><input type="radio" name="' + prefix + '-authority" value="reassign" data-authority-deletion-choice="authority"' + checked('authority', 'reassign', 'reassign') + '> Remonter au holon parent</label>'
        + '  </fieldset>'
        + (impact.descendants > 0 ? '  <fieldset data-authority-deletion-group="children"><legend>Sous-autorites</legend>'
        + '    <label><input type="radio" name="' + prefix + '-children" value="delete" data-authority-deletion-choice="children"' + checked('children', 'delete', 'reassign') + '> Supprimer les branches<span data-authority-deletion-count="children">' + formatAuthorityDeletionCount(impact.descendants, 'sous-autorite', 'sous-autorites') + '</span></label>'
        + '    <label><input type="radio" name="' + prefix + '-children" value="reassign" data-authority-deletion-choice="children"' + checked('children', 'reassign', 'reassign') + '> Remonter les branches au holon parent<span data-authority-deletion-count="children">' + formatAuthorityDeletionCount(impact.descendants, 'sous-autorite', 'sous-autorites') + '</span></label>'
        + '  </fieldset>' : '')
        + '  <fieldset data-authority-deletion-group="rules"' + (impact.rules <= 0 ? ' hidden' : '') + '><legend>Regles des autorites supprimees</legend>'
        + '    <label><input type="radio" name="' + prefix + '-rules" value="delete" data-authority-deletion-choice="rules"' + checked('rules', 'delete', 'reassign') + '> Supprimer les regles<span data-authority-deletion-count="rules">' + formatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees') + '</span></label>'
        + '    <label><input type="radio" name="' + prefix + '-rules" value="reassign" data-authority-deletion-choice="rules"' + checked('rules', 'reassign', 'reassign') + '> Remonter a l autorite la plus proche et demander une revue sous 2 mois<span data-authority-deletion-count="rules">' + formatAuthorityDeletionCount(impact.rules, 'regle concernee', 'regles concernees') + '</span></label>'
        + '  </fieldset>'
        + '</div>';
}

function renderAuthorityListRow(value) {
    const authorityId = getAuthorityId(value);
    const authority = authorityId > 0 ? getAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === authorityId;
    }) : null;
    const draft = value && typeof value === 'object' && !Array.isArray(value) ? value : {};
    if (authorityId > 0 && !draft.editing) {
        const label = authority ? String(authority.label || '') : 'Autorite #' + String(authorityId);
        const authorityPath = authority ? String(authority.pathLabel || authority.holonLabel || '') : '';
        const detailsPrefix = authority
            ? (authority.templateOriginLost
                ? 'Origine du modele supprimee'
                : (authority.isTemplateInstance || authority.isTemplateSource
                    ? 'Definie par le modele'
                    : (authority.needsParent ? 'A rattacher manuellement' : '')))
            : '';
        const details = detailsPrefix + (detailsPrefix && authorityPath ? ' - ' : '') + authorityPath;
        const isManagedTemplateAuthority = Boolean(authority && (authority.isTemplateInstance || authority.isTemplateSource));
        const labelMarkup = authority && (authority.isShell || isManagedTemplateAuthority)
            ? '<em>' + escapeHtml(label) + '</em>'
            : '<strong>' + escapeHtml(label) + '</strong>';
        return ''
            + '<div class="omo-holon-create__authority-row omo-holon-create__authority-row--existing' + (authority && authority.needsParent ? ' is-needs-parent' : '') + (isManagedTemplateAuthority ? ' is-template-instance' : '') + (authority && authority.templateOriginLost ? ' is-template-origin-lost' : '') + '" data-authority-entry data-authority-id="' + authorityId + '">'
            + (isManagedTemplateAuthority
                ? '  <div class="omo-holon-create__authority-edit" aria-disabled="true">' + labelMarkup + (details ? '<small>' + escapeHtml(details) + '</small>' : '') + '</div>'
                : '  <button type="button" class="omo-holon-create__authority-edit" data-authority-edit="1">' + labelMarkup + (details ? '<small>' + escapeHtml(details) + '</small>' : '') + '</button>')
            + (isManagedTemplateAuthority ? '' : '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost" data-authority-delete="1" aria-label="Supprimer l autorite">&times;</button>')
            + (isManagedTemplateAuthority ? '' : renderAuthorityDeletionChoices(authorityId, draft))
            + '</div>';
    }

    const parentId = Number(draft.parentId || (authority ? authority.parentId : 0) || 0);
    const label = String(draft.label || (authority ? authority.label : '') || '');
    const description = String(draft.description || (authority ? authority.description : '') || '');
    const requestedDelegationMode = String(draft.delegationMode || 'partial');
    const selectedParent = getAuthorityParentCatalog().find(function (entry) {
        return Number(entry.id || 0) === parentId;
    }) || null;
    const partialAllowed = !(selectedParent && selectedParent.isShell);
    const delegationMode = !partialAllowed && requestedDelegationMode !== 'complete' ? 'complete' : requestedDelegationMode;
    const parentOptions = getAuthorityParentCatalog().map(function (authority) {
        if (Number(authority.id || 0) === authorityId) {
            return '';
        }
        const selected = Number(authority.id || 0) === parentId ? ' selected' : '';
        const optionLabel = String(authority.label || '');
        return '<option value="' + Number(authority.id || 0) + '"' + selected + '>'
            + escapeHtml(optionLabel)
            + '</option>';
    }).join('');

    const rootAuthoritySelected = canCreateRootAuthority() && parentId <= 0;
    const delegationField = authorityId > 0 || rootAuthoritySelected ? '' : ''
        + '      <select class="omo-holon-create__authority-delegation generic-form-control"' + (parentId <= 0 ? ' disabled' : '') + '>'
        + '          <option value="partial"' + (delegationMode !== 'complete' ? ' selected' : '') + (partialAllowed ? '' : ' disabled') + '>Delegation partielle</option>'
        + '          <option value="complete"' + (delegationMode === 'complete' ? ' selected' : '') + '>Delegation complete</option>'
        + '      </select>';
    const authorityDetails = (authorityId > 0 || parentId > 0 || rootAuthoritySelected) && (authorityId > 0 || delegationMode !== 'complete')
        ? '      <input type="text" class="omo-holon-create__authority-label generic-form-control" value="' + escapeHtml(label) + '" placeholder="Nouvelle autorite">'
            + '      <textarea class="omo-holon-create__authority-description generic-form-control" rows="3" placeholder="Description">' + escapeHtml(description) + '</textarea>'
        : parentId > 0 && delegationMode === 'complete'
            ? '      <div class="omo-holon-create__authority-complete-note">L autorite parente sera deleguee completement. Une coquille sera conservee si le chemin doit rester marque.</div>'
            : '';
    const rootOption = canCreateRootAuthority()
        ? '<option value="0"' + (parentId <= 0 ? ' selected' : '') + '>Sans racine</option>'
        : '<option value="0">Autorite parente</option>';

    return ''
        + '<div class="omo-holon-create__authority-row" data-authority-entry' + (authorityId > 0 ? ' data-authority-id="' + authorityId + '"' : '') + '>'
        + '  <div class="omo-holon-create__authority-fields">'
        + '      <select class="omo-holon-create__authority-parent generic-form-control">'
        + '          ' + rootOption + parentOptions
        + '      </select>'
        + delegationField
        + authorityDetails
        + '  </div>'
        + '  <button type="button" class="omo-holon-create__button omo-holon-create__button--ghost" ' + (authorityId > 0 ? 'data-authority-delete="1" aria-label="Supprimer l autorite"' : 'data-authority-remove="1" aria-label="Retirer"') + '>&times;</button>'
        + (authorityId > 0 ? renderAuthorityDeletionChoices(authorityId, draft) : '')
        + '</div>';
}

function renderAuthorityListInput(values) {
    const authorities = getAuthorityParentCatalog();
    const rows = Array.isArray(values) && values.length ? values : [];
    const canCreateAuthority = authorities.length > 0 || canCreateRootAuthority();
    if (!canCreateAuthority && !rows.length) {
        return '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Une autorite parente existante est necessaire avant de pouvoir en creer une nouvelle.</div>';
    }

    return ''
        + '<div class="omo-holon-create__authority-list">'
        + '  <div class="omo-holon-create__authority-items">' + rows.map(renderAuthorityListRow).join('') + '</div>'
        + (canCreateAuthority
            ? '  <button type="button" class="omo-holon-create__button omo-holon-create__button--secondary" data-authority-add="1">Ajouter une autorite</button>'
            : '  <div class="omo-holon-create__empty-note generic-description generic-description--compact">Les autorites existantes restent disponibles, mais une autorite parente est necessaire pour en creer une nouvelle.</div>')
        + '</div>';
}

// Rend champ propriété
if (window.genericMultilineListPaste && typeof window.genericMultilineListPaste.attach === 'function') {
    window.genericMultilineListPaste.attach(root, {
        inputSelector: '.omo-holon-create__property-value-item',
        rowSelector: '.omo-holon-create__list-row',
        listSelector: '.omo-holon-create__list',
        itemsSelector: '.omo-holon-create__list-items',
        renderRow: renderSimpleListRow
    });
}

function renderPropertyInput(property) {
    const formatId = Number(property.formatId || 0);
    const localValue = property.value !== undefined && property.value !== null
        ? String(property.value)
        : '';

    if (!property.canEditValue) {
        return renderReadonlyPropertyValue(property, localValue)
            + '<div class="omo-holon-create__permission-note generic-description generic-description--compact">Vous n\'avez pas les droits de modification.</div>';
    }

    if (formatId === 2) {
        if (String(property.listItemType || 'text') === 'authority') {
            return renderAuthorityListInput(parseStoredListValue(localValue));
        }

        if (String(property.listItemType || 'text') === 'holon') {
            const allowedTypeIds = Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds.map(Number) : [];
            const holonOptions = getHolonCatalog().filter(function (holon) {
                return allowedTypeIds.length === 0 || allowedTypeIds.indexOf(Number(holon.typeId || 0)) >= 0;
            });
            const selectedIds = parseStoredListValue(localValue).map(Number);

            if (!holonOptions.length) {
                return '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Aucun holon disponible pour les types autorisés.</div>';
            }

            return '<div class="omo-holon-create__check-grid">'
                + holonOptions.map(function (holon) {
                    const checked = selectedIds.indexOf(Number(holon.id)) >= 0 ? ' checked' : '';
                    return ''
                        + '<label class="omo-holon-create__check-option">'
                        + '  <input type="checkbox" class="omo-holon-create__property-value omo-holon-create__property-value--holon" value="' + Number(holon.id) + '"' + checked + '>'
                        + '  <span>' + escapeHtml(holon.name) + '<small>' + escapeHtml(holon.pathLabel || holon.typeLabel || '') + '</small></span>'
                        + '</label>';
                }).join('')
                + '</div>';
        }

        if (String(property.listItemType || 'text') === 'project') {
            const projectOptions = getProjectCatalog();
            const selectedIds = parseStoredListValue(localValue).map(Number);

            if (!projectOptions.length) {
                return '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Aucun projet disponible.</div>';
            }

            return '<div class="omo-holon-create__check-grid">'
                + projectOptions.map(function (project) {
                    const checked = selectedIds.indexOf(Number(project.id)) >= 0 ? ' checked' : '';
                    return ''
                        + '<label class="omo-holon-create__check-option">'
                        + '  <input type="checkbox" class="omo-holon-create__property-value omo-holon-create__property-value--project" value="' + Number(project.id) + '"' + checked + '>'
                        + '  <span>' + escapeHtml(project.title) + (project.holonLabel ? '<small>' + escapeHtml(project.holonLabel) + '</small>' : '') + '</span>'
                        + '</label>';
                }).join('')
                + '</div>';
        }

        return renderSimpleListInput(property.listItemType || 'text', parseStoredListValue(localValue));
    }

    if (formatId === 6) {
        let parts = {};
        try { parts = JSON.parse(localValue) || {}; } catch (error) {}
        return '<input type="text" class="omo-holon-create__property-value-text-html-title generic-form-control" value="' + escapeHtml(parts.text || '') + '" placeholder="Texte affiche">'
            + '<textarea class="omo-holon-create__property-value-text-html-detail generic-form-control" rows="5" placeholder="Detail HTML">' + escapeHtml(parts.detail || '') + '</textarea>';
    }

    if (formatId === 7) {
        let parts = {};
        try { parts = JSON.parse(localValue) || {}; } catch (error) {}
        const listControl = renderPropertyInput(Object.assign({}, property, { formatId: 2, value: JSON.stringify(Array.isArray(parts.items) ? parts.items : []) }));
        return '<div class="omo-holon-create__composite-html" data-omo-composite-html="before" data-value="' + escapeHtml(parts.before || '') + '"></div>'
            + '<div class="omo-holon-create__composite-list">' + listControl + '</div>'
            + '<div class="omo-holon-create__composite-html" data-omo-composite-html="after" data-value="' + escapeHtml(parts.after || '') + '"></div>';
    }

    if (formatId === 3) {
        return '<input type="number" step="any" class="omo-holon-create__property-value generic-form-control" value="' + escapeHtml(localValue) + '" placeholder="Ex.: 42">';
    }

    if (formatId === 4) {
        return '<input type="date" class="omo-holon-create__property-value generic-form-control" value="' + escapeHtml(localValue) + '">';
    }

    if (formatId === 5) {
        return '<div class="omo-holon-create__html-editor"></div>';
    }

    return '<textarea class="omo-holon-create__property-value" rows="4" placeholder="Renseignez une valeur locale si nécessaire.">' + escapeHtml(localValue) + '</textarea>';
}

// Formate holon hérité
function formatInheritedHolonItem(item) {
    const holonId = Number(item || 0);
    const holon = getHolonCatalog().find(function (entry) {
        return Number(entry.id || 0) === holonId;
    });

    return holon ? holon.pathLabel : String(item || '');
}

function formatInheritedProjectItem(item) {
    const projectId = Number(item || 0);
    const project = getProjectCatalog().find(function (entry) {
        return Number(entry.id || 0) === projectId;
    });

    return project ? project.title : String(item || '');
}

function formatInheritedAuthorityItem(item) {
    const authorityId = getAuthorityId(item);
    const authority = getAuthorityCatalog().find(function (entry) {
        return Number(entry.id || 0) === authorityId;
    });

    return authority ? String(authority.pathLabel || authority.label || '') : String(item || '');
}

function getPropertyPreviewItems(property, rawValue) {
    return parseStoredListValue(rawValue).map(function (item) {
        if (String(property.listItemType || 'text') === 'detail') {
            return normalizeDetailedListItem(item);
        }
        if (String(property.listItemType || 'text') === 'holon') {
            return formatInheritedHolonItem(item);
        }
        if (String(property.listItemType || 'text') === 'project') {
            return formatInheritedProjectItem(item);
        }
        if (String(property.listItemType || 'text') === 'authority') {
            return formatInheritedAuthorityItem(item);
        }
        return String(item || '');
    }).filter(Boolean);
}

function renderReadonlyListContent(property, rawValue) {
    const items = getPropertyPreviewItems(property, rawValue);
    if (!items.length) {
        return '';
    }

    if (String(property.listItemType || 'text') === 'detail') {
        return '<div class="omo-holon-create__inherited-detail-list">'
            + items.map(function (item) {
                return '<details class="omo-holon-create__detail-card">'
                    + '<summary>' + escapeHtml(item.title || 'Element') + '</summary>'
                    + (item.description !== ''
                        ? '<div class="omo-holon-create__detail-body">' + escapeHtml(item.description).replace(/\n/g, '<br>') + '</div>'
                        : '')
                    + '</details>';
            }).join('')
            + '</div>';
    }

    return '<ul class="omo-holon-create__inherited-list">'
        + items.map(function (item) {
            return '<li>' + escapeHtml(item) + '</li>';
        }).join('')
        + '</ul>';
}

function renderReadonlyPropertyValue(property, rawValue) {
    const value = rawValue !== undefined && rawValue !== null ? String(rawValue) : '';
    if (!value.trim()) {
        return '';
    }

    const formatId = Number(property.formatId || 0);
    let content = '';

    if (formatId === 2) {
        content = renderReadonlyListContent(property, value);
    } else if (formatId === 5) {
        content = renderHtmlPreview(value, 'omo-holon-create__readonly-text generic-meta');
    } else if (formatId === 6) {
        let parts = {};
        try { parts = JSON.parse(value) || {}; } catch (error) {}
        content = (String(parts.text || '').trim() !== ''
            ? '<div class="omo-holon-create__readonly-text generic-meta">' + escapeHtml(parts.text) + '</div>'
            : '')
            + (String(parts.detail || '').trim() !== ''
                ? renderHtmlPreview(parts.detail, 'omo-holon-create__readonly-text generic-meta')
                : '');
    } else if (formatId === 7) {
        let parts = {};
        try { parts = JSON.parse(value) || {}; } catch (error) {}
        content = (String(parts.before || '').trim() !== ''
            ? renderHtmlPreview(parts.before, 'omo-holon-create__readonly-text generic-meta')
            : '')
            + renderReadonlyListContent(property, JSON.stringify(Array.isArray(parts.items) ? parts.items : []))
            + (String(parts.after || '').trim() !== ''
                ? renderHtmlPreview(parts.after, 'omo-holon-create__readonly-text generic-meta')
                : '');
    } else {
        content = '<div class="omo-holon-create__readonly-text generic-meta">' + escapeHtml(value).replace(/\n/g, '<br>') + '</div>';
    }

    return content
        ? '<div class="omo-holon-create__readonly-value generic-soft-panel generic-soft-panel--stack">' + content + '</div>'
        : '';
}

// Rend valeur héritée
function renderInheritedValue(property) {
    const inheritedValue = property.inheritedValue !== undefined && property.inheritedValue !== null
        ? String(property.inheritedValue)
        : '';

    if (!inheritedValue.trim()) {
        return '';
    }

    if (Number(property.formatId || 0) === 2) {
        const items = getPropertyPreviewItems(property, inheritedValue);

        if (!items.length) {
            return '';
        }

        if (String(property.listItemType || 'text') === 'detail') {
            return ''
                + '<div class="omo-holon-create__inherited">'
                + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur heritee</div>'
                + '  <div class="omo-holon-create__inherited-detail-list">'
                + items.map(function (item) {
                    return ''
                        + '<details class="omo-holon-create__detail-card">'
                        + '  <summary>' + escapeHtml(item.title || 'Element') + '</summary>'
                        + (item.description !== ''
                            ? '  <div class="omo-holon-create__detail-body">' + escapeHtml(item.description).replace(/\n/g, '<br>') + '</div>'
                            : '')
                        + '</details>';
                }).join('')
                + '  </div>'
                + '</div>';
        }

        return ''
            + '<div class="omo-holon-create__inherited">'
            + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur héritée</div>'
            + '  <ul class="omo-holon-create__inherited-list">'
            + items.map(function (item) {
                return '<li>' + escapeHtml(item) + '</li>';
            }).join('')
            + '  </ul>'
            + '</div>';
    }

    if (Number(property.formatId || 0) === 5) {
        return ''
            + '<div class="omo-holon-create__inherited">'
            + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur heritee</div>'
            +       renderHtmlPreview(inheritedValue, 'omo-holon-create__inherited-text generic-meta')
            + '</div>';
    }

    return ''
        + '<div class="omo-holon-create__inherited">'
        + '  <div class="omo-holon-create__inherited-label generic-card-title generic-card-title--eyebrow">Valeur héritée</div>'
        + '  <div class="omo-holon-create__inherited-text generic-meta">' + escapeHtml(inheritedValue).replace(/\n/g, '<br>') + '</div>'
        + '</div>';
}

// Crée ligne propriété
function isDirectHolonProperty(property) {
    return !isTemplateEditing() && Boolean(property && property.isDirectProperty);
}

function getPropertyFormatOptions(formatId) {
    return (state.data.formats || []).map(function (format) {
        const selected = Number(format.id || 0) === Number(formatId || 0) ? ' selected' : '';
        return '<option value="' + Number(format.id || 0) + '"' + selected + '>' + escapeHtml(format.name || '') + '</option>';
    }).join('');
}

function renderDirectPropertyListConfig(property, disabled) {
    const formatId = Number(property.formatId || 0);
    if ([2, 7].indexOf(formatId) < 0) {
        return '';
    }

    const disabledAttribute = disabled ? ' disabled' : '';
    const itemType = String(property.listItemType || 'text');
    const itemTypeOptions = (state.data.listItemTypes || []).map(function (itemTypeOption) {
        const selected = String(itemTypeOption.id || '') === itemType ? ' selected' : '';
        return '<option value="' + escapeHtml(itemTypeOption.id || '') + '"' + selected + '>' + escapeHtml(itemTypeOption.name || '') + '</option>';
    }).join('');
    const selectedTypeIds = Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds.map(Number) : [];
    const holonTypeOptions = itemType === 'holon'
        ? '<div class="omo-holon-create__check-grid">' + (state.data.types || []).map(function (type) {
            const checked = selectedTypeIds.indexOf(Number(type.id || 0)) >= 0 ? ' checked' : '';
            return '<label class="omo-holon-create__check-option"><input type="checkbox" class="omo-holon-create__direct-property-holon-type" value="' + Number(type.id || 0) + '"' + checked + disabledAttribute + '><span>' + escapeHtml(type.name || '') + '</span></label>';
        }).join('') + '</div>'
        : '';

    return ''
        + '<label class="omo-holon-create__field">'
        + '  <span>Type des elements</span>'
        + '  <select class="omo-holon-create__direct-property-list-type generic-form-control"' + disabledAttribute + '>' + itemTypeOptions + '</select>'
        + '</label>'
        + holonTypeOptions;
}

function renderDirectPropertyDefinition(property) {
    if (!isDirectHolonProperty(property)) {
        return '';
    }

    const disabled = !property.canEditDefinition;
    const disabledAttribute = disabled ? ' disabled' : '';
    const deleteDisabled = property.canDelete ? '' : ' disabled';
    return ''
        + '<div class="omo-holon-create__direct-property-definition generic-soft-panel">'
        + '  <div class="omo-holon-create__grid">'
        + '    <label class="omo-holon-create__field"><span>Nom de la propriete</span><input type="text" class="omo-holon-create__direct-property-name generic-form-control" maxlength="255" value="' + escapeHtml(property.name || '') + '"' + disabledAttribute + '></label>'
        + '    <label class="omo-holon-create__field"><span>Format</span><select class="omo-holon-create__direct-property-format generic-form-control"' + disabledAttribute + '>' + getPropertyFormatOptions(property.formatId) + '</select></label>'
        +      renderDirectPropertyListConfig(property, disabled)
        + '  </div>'
        + '  <button type="button" class="generic-action-button generic-action-button--secondary" data-direct-property-remove="1"' + deleteDisabled + '>Retirer cette propriete</button>'
        + '</div>';
}

function createPropertyRow(property, index) {
    const row = document.createElement('div');
    row.className = 'omo-holon-create__property generic-section';
    row.dataset.propertyId = Number(property.id || 0);
    row.dataset.holonPropertyId = Number(property.holonPropertyId || 0);
    row.dataset.formatId = Number(property.formatId || 0);
    row.dataset.listItemType = String(property.listItemType || 'text');
    row.dataset.propertyName = String(property.name || '');
    row.dataset.shortname = String(property.shortname || '');
    row.dataset.value = property.value !== undefined && property.value !== null ? String(property.value) : '';
    row.dataset.listHolonTypeIds = JSON.stringify(Array.isArray(property.listHolonTypeIds) ? property.listHolonTypeIds : []);
    row.dataset.mandatory = property.mandatory ? '1' : '0';
    row.dataset.locked = property.locked ? '1' : '0';
    row.dataset.localMandatory = property.mandatory ? '1' : '0';
    row.dataset.localLocked = property.locked ? '1' : '0';
    row.dataset.inheritedMandatory = property.inheritedMandatory ? '1' : '0';
    row.dataset.inheritedLocked = property.inheritedLocked ? '1' : '0';
    row.dataset.isInherited = property.isInherited ? '1' : '0';
    row.dataset.isLocal = property.isLocal ? '1' : '0';
    row.dataset.isDirectProperty = property.isDirectProperty ? '1' : '0';
    row.dataset.isTemplateProperty = property.isTemplateProperty ? '1' : '0';
    row.dataset.canEditDefinition = property.canEditDefinition ? '1' : '0';
    row.dataset.canDelete = property.canDelete ? '1' : '0';
    row.dataset.canEditValue = property.canEditValue ? '1' : '0';

    const chips = [];
    if (property.formatName) {
        chips.push('<span class="omo-holon-create__chip omo-holon-create__chip--accent">' + escapeHtml(property.formatName) + '</span>');
    }
    if (property.effectiveMandatory) {
        chips.push('<span class="omo-holon-create__chip">Obligatoire</span>');
    }
    if (property.effectiveLocked) {
        chips.push('<span class="omo-holon-create__chip">Verrouillée</span>');
    }

    row.innerHTML = ''
        + '<div class="omo-holon-create__property-index">P' + String(index + 1) + '</div>'
        + '<div class="omo-holon-create__property-body">'
        + '  <div class="omo-holon-create__property-head">'
        + '      <div>'
        + '          <div class="omo-holon-create__property-name">' + escapeHtml(property.name || ('Propriété ' + Number(property.id || 0))) + '</div>'
        + '          <div class="omo-holon-create__property-meta">' + chips.join('') + '</div>'
        + '      </div>'
        + '  </div>'
        + renderDirectPropertyDefinition(property)
        + renderInheritedValue(property)
        + '  <' + ([5, 7].indexOf(Number(property.formatId || 0)) >= 0 ? 'div' : 'label') + ' class="omo-holon-create__field">'
        + '      <span>Valeur locale</span>'
        + '      <div class="omo-holon-create__property-input">' + renderPropertyInput(property) + '</div>'
        + '  </' + ([5, 7].indexOf(Number(property.formatId || 0)) >= 0 ? 'div' : 'label') + '>'
        + '</div>';

    const propertyTitle = row.querySelector('.omo-holon-create__property-name');
    if (propertyTitle && (!property.name || Number(property.id || 0) <= 0)) {
        propertyTitle.textContent = String(property.name || '').trim() || 'Nouvelle propriete';
    }

    if ([5, 7].indexOf(Number(property.formatId || 0)) >= 0) {
        row.querySelectorAll('.omo-holon-create__html-editor, [data-omo-composite-html]').forEach(function (htmlEditorHost) {
        if (htmlEditorHost && window.omoSimpleHtmlField && typeof window.omoSimpleHtmlField.mount === 'function') {
            window.omoSimpleHtmlField.mount(htmlEditorHost, {
                value: htmlEditorHost.hasAttribute('data-omo-composite-html') ? String(htmlEditorHost.getAttribute('data-value') || '') : (property.value !== undefined && property.value !== null ? String(property.value) : ''),
                placeholder: 'Renseignez une valeur locale si necessaire.'
            });
        }
        });
    }

    return row;
}

// Rend bloc propriétés
function renderProperties(properties) {
    elements.properties.innerHTML = '';

    if (!Array.isArray(properties) || !properties.length) {
        elements.properties.innerHTML = '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Ce modèle ne définit aucune propriété.</div>';
        return;
    }

    properties.forEach(function (property, index) {
        elements.properties.appendChild(createPropertyRow(property, index));
    });
}

function getDirectPropertyDraft(row) {
    const nameField = row.querySelector('.omo-holon-create__direct-property-name');
    const formatField = row.querySelector('.omo-holon-create__direct-property-format');
    const listTypeField = row.querySelector('.omo-holon-create__direct-property-list-type');
    const formatId = Number(formatField && formatField.value ? formatField.value : row.dataset.formatId || 1);
    const format = (state.data.formats || []).find(function (item) {
        return Number(item.id || 0) === formatId;
    });
    return {
        id: Number(row.dataset.propertyId || 0),
        holonPropertyId: Number(row.dataset.holonPropertyId || 0),
        name: String(nameField && nameField.value ? nameField.value : row.dataset.propertyName || ''),
        shortname: String(row.dataset.shortname || ''),
        formatId: formatId,
        formatName: format ? String(format.name || '') : '',
        listItemType: String(listTypeField && listTypeField.value ? listTypeField.value : row.dataset.listItemType || 'text'),
        listHolonTypeIds: Array.from(row.querySelectorAll('.omo-holon-create__direct-property-holon-type:checked')).map(function (input) {
            return Number(input.value || 0);
        }).filter(Boolean),
        value: serializePropertyValue(row),
        isDirectProperty: true,
        isTemplateProperty: false,
        canEditDefinition: String(row.dataset.canEditDefinition || '0') === '1',
        canDelete: String(row.dataset.canDelete || '0') === '1',
        canEditValue: String(row.dataset.canEditValue || '0') === '1'
    };
}

// Prépare propriétés modèle
function buildPropertiesForTemplate(template, sourceProperties) {
    const sourceMap = new Map();
    const directProperties = [];
    (sourceProperties || []).forEach(function (property) {
        sourceMap.set(Number(property.id || 0), property);
        if (property && property.isDirectProperty) {
            directProperties.push(property);
        }
    });

    if (!template) {
        return [];
    }

    const templateProperties = (template && Array.isArray(template.properties) ? template.properties : []).map(function (property) {
        const source = sourceMap.get(Number(property.id || 0));
        const sourceInheritedValue = source && source.inheritedValue !== undefined && source.inheritedValue !== null
            ? String(source.inheritedValue)
            : '';
        const templateInheritedValue = property.inheritedValue !== undefined && property.inheritedValue !== null
            ? String(property.inheritedValue)
            : '';
        const inheritedValue = sourceInheritedValue.trim() !== '' ? sourceInheritedValue : templateInheritedValue;
        return Object.assign({}, property, {
            value: source && source.value !== undefined && source.value !== null ? String(source.value) : '',
            inheritedValue: inheritedValue,
            inheritedMandatory: source && source.inheritedMandatory !== undefined ? Boolean(source.inheritedMandatory) : property.inheritedMandatory,
            inheritedLocked: Boolean((source && source.inheritedLocked) || property.inheritedLocked),
            effectiveMandatory: Boolean((source && source.effectiveMandatory) || property.effectiveMandatory),
            effectiveLocked: Boolean((source && source.effectiveLocked) || property.effectiveLocked),
            canEditValue: source && source.canEditValue !== undefined ? Boolean(source.canEditValue) : property.canEditValue,
            isTemplateProperty: true,
            isDirectProperty: false,
            canEditDefinition: false,
            canDelete: false
        });
    });

    return templateProperties.concat(directProperties);
}

// Rend options modèles
function renderTemplateOptions(preferredTemplateId) {
    const templates = getTemplates();

    elements.template.innerHTML = '';
    let currentGroupId = null;
    let currentGroup = null;
    templates.forEach(function (template, index) {
        const definedInId = Number(template.definedInId || 0);
        const contextName = String(template.definedInName || template.definedInLabel || '').trim();
        const groupId = definedInId > 0 ? String(definedInId) : 'unassigned';
        if (groupId !== currentGroupId) {
            currentGroupId = groupId;
            currentGroup = document.createElement('optgroup');
            currentGroup.label = contextName || 'Espace';
            elements.template.appendChild(currentGroup);
        }

        const option = document.createElement('option');
        const name = String(template.name || '').trim();
        option.value = Number(template.id);
        option.textContent = name;
        option.selected = Number(preferredTemplateId || 0) === Number(template.id) || (!preferredTemplateId && index === 0);
        currentGroup.appendChild(option);
    });

    if (!elements.template.value && templates.length) {
        elements.template.value = String(Number(templates[0].id));
    }

    elements.template.required = true;
}

// Rend méta modèle
function renderTemplateMeta(template, sourceProperties) {
    if (!template) {
        elements.meta.innerHTML = '';
        elements.hint.textContent = '';
        renderProperties([]);
        return;
    }

    const meta = [];
    meta.push('<span class="omo-holon-create__chip omo-holon-create__chip--accent">' + escapeHtml(template.typeLabel || '') + '</span>');
    meta.push('<span class="omo-holon-create__chip">' + (Array.isArray(template.properties) ? template.properties.length : 0) + ' propriété' + ((template.properties || []).length > 1 ? 's' : '') + '</span>');
    elements.meta.innerHTML = meta.join('');
    elements.hint.textContent = getMode() === 'edit'
        ? 'Le type et les propriétés suivent le modèle sélectionné.'
        : 'Le type et les propriétés sont hérités du modèle sélectionné.';
    renderProperties(buildPropertiesForTemplate(template, sourceProperties));

    if (elements.color) {
        const editingHolon = getEditingHolon();
        const resolvedColor = getMode() === 'edit' && editingHolon
            ? String(editingHolon.color || template.color || '')
            : String(template.color || '');
        elements.color.value = resolvedColor.trim() !== '' ? resolvedColor : '#f59e0b';
    }
    if (elements.colorEnabled) {
        const editingHolon = getEditingHolon();
        elements.colorEnabled.checked = getMode() === 'edit'
            ? String((editingHolon && editingHolon.color) || '').trim() !== ''
            : false;
    }
    syncColorField();
}

// Synchronise modèle courant
function renderEditorMeta(template, sourceProperties) {
    const editingHolon = getEditingHolon();
    const properties = buildPropertiesForTemplate(template, sourceProperties);
    const propertyCount = Array.isArray(properties) ? properties.length : 0;
    const meta = [];
    const typeLabel = template
        ? String(template.typeLabel || '')
        : String((editingHolon && editingHolon.typeLabel) || '');

    if (elements.templateLabel) {
        elements.templateLabel.textContent = isTemplateEditing() ? 'Modèle parent' : 'Modèle';
    }

    if (typeLabel) {
        meta.push('<span class="omo-holon-create__chip omo-holon-create__chip--accent">' + escapeHtml(typeLabel) + '</span>');
    }
    meta.push('<span class="omo-holon-create__chip">' + propertyCount + ' propriété' + (propertyCount > 1 ? 's' : '') + '</span>');

    if (isTemplateEditing() && !template) {
        meta.push('<span class="omo-holon-create__chip">Sans modèle parent</span>');
    }

    elements.meta.innerHTML = meta.join('');
    elements.hint.textContent = isTemplateEditing()
        ? 'Les options et propriétés de ce template peuvent être redéfinies ici.'
        : getMode() === 'edit'
        ? 'Le type et les propriétés suivent le modèle sélectionné.'
        : 'Le type et les propriétés sont hérités du modèle sélectionné.';
    renderProperties(properties);
    if (elements.addProperty) {
        elements.addProperty.disabled = isTemplateEditing() || !Boolean(state.data.canAddHolonProperties);
    }

    if (elements.color) {
        const resolvedColor = getMode() === 'edit' && editingHolon
            ? String(editingHolon.color || (template ? template.color : '') || '')
            : String((template ? template.color : '') || '');
        elements.color.value = resolvedColor.trim() !== '' ? resolvedColor : '#f59e0b';
    }

    if (elements.colorEnabled) {
        elements.colorEnabled.checked = getMode() === 'edit'
            ? String((editingHolon && editingHolon.color) || '').trim() !== ''
            : false;
    }

    syncNameField(template);
    syncColorField();
	 syncAdminBounds(template);
    renderMediaFields(template);
}

function syncTemplateSelection(preferredTemplateId, sourceProperties) {
    renderTemplateOptions(preferredTemplateId);
    renderEditorMeta(getCurrentTemplate(), sourceProperties);
}

// Sérialise valeur propriété
function serializePropertyValue(row) {
    const formatId = Number(row.dataset.formatId || 0);
    const listItemType = String(row.dataset.listItemType || 'text');
    const canEditValue = String(row.dataset.canEditValue || '0') === '1';
    const htmlFieldHost = row.querySelector('[data-omo-html-field="1"]');

    if (!canEditValue) {
        return String(row.dataset.value || '');
    }

    if (formatId === 5 && htmlFieldHost && htmlFieldHost.__omoSimpleHtmlField && typeof htmlFieldHost.__omoSimpleHtmlField.getValue === 'function') {
        return String(htmlFieldHost.__omoSimpleHtmlField.getValue() || '');
    }

    if (formatId === 2) {
        if (listItemType === 'authority') {
            const items = Array.from(row.querySelectorAll('[data-authority-entry]')).map(getAuthorityEntryPayload).filter(Boolean);
            return items.length ? JSON.stringify(items) : '';
        }

        if (listItemType === 'holon') {
            const selectedIds = Array.from(row.querySelectorAll('.omo-holon-create__property-value--holon:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        if (listItemType === 'project') {
            const selectedIds = Array.from(row.querySelectorAll('.omo-holon-create__property-value--project:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            return selectedIds.length ? JSON.stringify(selectedIds) : '';
        }

        if (listItemType === 'detail') {
            const items = Array.from(row.querySelectorAll('.omo-holon-create__list-row--detail')).map(function (detailRow) {
                const titleField = detailRow.querySelector('.omo-holon-create__property-value-item--detail-title');
                const descriptionField = detailRow.querySelector('.omo-holon-create__property-value-item--detail-description');
                const item = {
                    title: String(titleField && titleField.value ? titleField.value : '').trim(),
                    description: String(descriptionField && descriptionField.value ? descriptionField.value : '').trim()
                };

                return item.title !== '' || item.description !== '' ? item : null;
            }).filter(Boolean);

            return items.length ? JSON.stringify(items) : '';
        }

        const items = Array.from(row.querySelectorAll('.omo-holon-create__property-value-item')).map(function (input) {
            return String(input.value || '').trim();
        }).filter(Boolean);

        return items.length ? JSON.stringify(items) : '';
    }

    if (formatId === 6) {
        return JSON.stringify({
            text: String((row.querySelector('.omo-holon-create__property-value-text-html-title') || {}).value || '').trim(),
            detail: String((row.querySelector('.omo-holon-create__property-value-text-html-detail') || {}).value || '')
        });
    }

    if (formatId === 7) {
        const items = listItemType === 'authority'
            ? Array.from(row.querySelectorAll('[data-authority-entry]')).map(getAuthorityEntryPayload).filter(Boolean)
            : listItemType === 'project' ? Array.from(row.querySelectorAll('.omo-holon-create__property-value--project:checked')).map(function (input) { return Number(input.value || 0); }).filter(Boolean) : listItemType === 'holon' ? Array.from(row.querySelectorAll('.omo-holon-create__property-value--holon:checked')).map(function (input) { return Number(input.value || 0); }).filter(Boolean) : Array.from(row.querySelectorAll('.omo-holon-create__property-value-item')).map(function (input) { return String(input.value || '').trim(); }).filter(Boolean);
        const beforeHost = row.querySelector('[data-omo-composite-html="before"]');
        const afterHost = row.querySelector('[data-omo-composite-html="after"]');
        return JSON.stringify({
            before: beforeHost && beforeHost.__omoSimpleHtmlField ? String(beforeHost.__omoSimpleHtmlField.getValue() || '') : '',
            items: items,
            after: afterHost && afterHost.__omoSimpleHtmlField ? String(afterHost.__omoSimpleHtmlField.getValue() || '') : ''
        });
    }

    const valueField = row.querySelector('.omo-holon-create__property-value');
    return valueField ? String(valueField.value || '') : '';
}

// Lit valeurs propriétés
function readProperties() {
    return Array.from(elements.properties.querySelectorAll('.omo-holon-create__property')).map(function (row) {
        const property = {
            id: Number(row.dataset.propertyId || 0),
            value: serializePropertyValue(row)
        };

        const isDirectProperty = String(row.dataset.isDirectProperty || '0') === '1';
        if (isDirectProperty) {
            const nameField = row.querySelector('.omo-holon-create__direct-property-name');
            const formatField = row.querySelector('.omo-holon-create__direct-property-format');
            const listTypeField = row.querySelector('.omo-holon-create__direct-property-list-type');
            property.name = String(nameField && nameField.value ? nameField.value : row.dataset.propertyName || '').trim();
            property.shortname = String(row.dataset.shortname || '');
            property.formatId = Number(formatField && formatField.value ? formatField.value : row.dataset.formatId || 0);
            property.listItemType = String(listTypeField && listTypeField.value ? listTypeField.value : row.dataset.listItemType || 'text');
            property.listHolonTypeIds = Array.from(row.querySelectorAll('.omo-holon-create__direct-property-holon-type:checked')).map(function (input) {
                return Number(input.value || 0);
            }).filter(Boolean);
            property.position = Array.from(elements.properties.querySelectorAll('.omo-holon-create__property')).indexOf(row) + 1;
            property.isDirectProperty = true;
            property.isTemplateProperty = false;
            property.canEditDefinition = String(row.dataset.canEditDefinition || '0') === '1';
            property.canDelete = String(row.dataset.canDelete || '0') === '1';
        }

        if (isTemplateEditing()) {
            let listHolonTypeIds = [];
            try {
                listHolonTypeIds = JSON.parse(String(row.dataset.listHolonTypeIds || '[]'));
            } catch (error) {
                listHolonTypeIds = [];
            }

            const mandatoryField = row.querySelector('.omo-holon-create__property-mandatory');
            const lockedField = row.querySelector('.omo-holon-create__property-locked');
            const inheritedMandatory = String(row.dataset.inheritedMandatory || '0') === '1';
            const inheritedLocked = String(row.dataset.inheritedLocked || '0') === '1';
            const localMandatory = mandatoryField
                ? (mandatoryField.disabled && inheritedMandatory
                    ? String(row.dataset.localMandatory || '0') === '1'
                    : Boolean(mandatoryField.checked))
                : false;
            const localLocked = lockedField
                ? (lockedField.disabled && inheritedLocked
                    ? String(row.dataset.localLocked || '0') === '1'
                    : Boolean(lockedField.checked))
                : false;

            property.holonPropertyId = Number(row.dataset.holonPropertyId || 0);
            property.name = String(row.dataset.propertyName || '');
            property.shortname = String(row.dataset.shortname || '');
            property.formatId = Number(row.dataset.formatId || 0);
            property.listItemType = String(row.dataset.listItemType || 'text');
            property.listHolonTypeIds = Array.isArray(listHolonTypeIds) ? listHolonTypeIds.map(Number).filter(Boolean) : [];
            property.mandatory = localMandatory;
            property.locked = localLocked;
            property.inheritedMandatory = inheritedMandatory;
            property.inheritedLocked = inheritedLocked;
            property.effectiveMandatory = inheritedMandatory || localMandatory;
            property.effectiveLocked = inheritedLocked || localLocked;
            property.isInherited = String(row.dataset.isInherited || '0') === '1';
            property.isLocal = String(row.dataset.isLocal || '0') === '1';
        }

        return property;
    }).filter(function (property) {
        return Number(property.id || 0) > 0 || (property.isDirectProperty && String(property.name || '').trim() !== '');
    });
}

// Remplit formulaire courant
function fillFormFromState() {
    const editingHolon = getEditingHolon();

    if (editingHolon) {
        elements.name.value = String(editingHolon.name || '');
        if (elements.fullName) {
            elements.fullName.value = String(editingHolon.fullName || '');
        }
        syncTemplateSelection(Number(editingHolon.templateId || 0), editingHolon.properties || []);
        renderPermissions(editingHolon.permissionAssignments || {});
        if (isTemplateEditing()) {
            if (elements.visible) {
                elements.visible.checked = Boolean(editingHolon.visible);
            }
            if (elements.mandatory) {
                elements.mandatory.checked = Boolean(editingHolon.mandatory);
            }
            if (elements.link) {
                elements.link.checked = Boolean(editingHolon.link);
            }
        }
        return;
    }

    elements.name.value = '';
    if (elements.fullName) {
        elements.fullName.value = '';
    }
    elements.name.disabled = false;
    renderPermissions({});
    if (elements.visible) {
        elements.visible.checked = false;
    }
    if (elements.mandatory) {
        elements.mandatory.checked = false;
    }
    if (elements.link) {
        elements.link.checked = false;
    }
    syncTemplateSelection();
}

// Efface message statut
function clearStatus() {
    if (state.statusTimer) {
        window.clearTimeout(state.statusTimer);
        state.statusTimer = null;
    }

    elements.status.hidden = true;
    elements.status.className = 'omo-holon-create__status';
    elements.status.innerHTML = '';
}

// Affiche message statut
function showStatus(message, tone) {
    clearStatus();

    if (typeof window.commonNotify === 'function') {
        window.commonNotify(String(message || ''), tone === 'success' ? 'success' : 'error');
        return;
    }

    elements.status.hidden = false;
    elements.status.className = 'omo-holon-create__status is-' + tone;
    elements.status.innerHTML = '<div class="omo-holon-create__status-copy">' + escapeHtml(message) + '</div>';
    state.statusTimer = window.setTimeout(clearStatus, 12000);
}

// Ferme drawer création
function getCurrentDrawerRouteToken() {
    if (typeof parseUrl !== 'function') {
        return '';
    }

    const route = parseUrl();
    const rawHash = String(route && route.hash ? route.hash : '').trim();
    if (!rawHash) {
        return '';
    }

    return rawHash.split('|')[0] || '';
}

function isHashManagedHolonEditorDrawer() {
    return /^(holon-create-\d+|holon-edit-\d+)$/i.test(getCurrentDrawerRouteToken());
}

function isHashManagedCreateDrawer() {
    return /^holon-create-\d+$/i.test(getCurrentDrawerRouteToken());
}

function getExternalDrawerContext() {
    if (typeof window.omoGetExternalPanelDrawerContext !== 'function') {
        return null;
    }

    return window.omoGetExternalPanelDrawerContext(root);
}

function closeCreateDrawer() {
    const externalDrawerContext = getExternalDrawerContext();
    if (externalDrawerContext && typeof window.omoCloseExternalPanelDrawer === 'function') {
        window.omoCloseExternalPanelDrawer();
        return;
    }

    if (isHashManagedHolonEditorDrawer() && typeof window.omoSetDrawerHashState === 'function') {
        window.omoSetDrawerHashState({
            open: false
        });
        return;
    }

    if (typeof closeDrawer === 'function') {
        closeDrawer('drawer_holon_create');
    }
}

function refreshStructureViews(targetHolonId, options) {
    const cid = targetHolonId === null || targetHolonId === undefined || targetHolonId === ''
        ? null
        : Number(targetHolonId);
    const refreshOptions = options && typeof options === 'object'
        ? options
        : {};
    const detail = {
        cid: Number.isNaN(cid) ? null : cid,
        quickZoom: Boolean(refreshOptions.quickZoom)
    };

    if (typeof window.omoReloadStructureAndFocus === 'function') {
        return window.omoReloadStructureAndFocus(detail.cid, refreshOptions)
            .catch(function () {
                return null;
            });
    }

    window.dispatchEvent(new CustomEvent('omo-structure-refresh', {
        detail: detail
    }));

    return Promise.resolve(null);
}

// Enregistre holon courant
function saveHolon(event) {
    event.preventDefault();

    if (elements.form && typeof window.omoBeginPendingAction === 'function' && !window.omoBeginPendingAction(elements.form)) {
        return;
    }

    clearStatus();

    const pendingMediaFlushes = [];
    if (mediaFields.icon && typeof mediaFields.icon.flushPending === 'function') {
        pendingMediaFlushes.push(mediaFields.icon.flushPending());
    }
    if (mediaFields.banner && typeof mediaFields.banner.flushPending === 'function') {
        pendingMediaFlushes.push(mediaFields.banner.flushPending());
    }

    Promise.all(pendingMediaFlushes)
        .then(function () {
            const payload = {
                templateId: Number(elements.template.value || 0),
                name: String(elements.name.value || '').trim(),
                fullName: String(elements.fullName && elements.fullName.value ? elements.fullName.value : '').trim(),
                color: Boolean(elements.colorEnabled && elements.colorEnabled.checked)
                    ? String(elements.color && elements.color.value ? elements.color.value : '')
                    : '',
                icon: mediaFields.icon ? mediaFields.icon.getValue() : '',
                banner: mediaFields.banner ? mediaFields.banner.getValue() : '',
				adminMin: normalizeAdminBound(elements.adminMin && elements.adminMin.value, false),
				adminMax: normalizeAdminBound(elements.adminMax && elements.adminMax.value, true),
				adminMinOverride: Boolean(elements.adminMinOverride && elements.adminMinOverride.checked),
				adminMaxOverride: Boolean(elements.adminMaxOverride && elements.adminMaxOverride.checked),
                permissions: readPermissions(),
                properties: readProperties()
            };

            if (isTemplateEditing()) {
                payload.visible = Boolean(elements.visible && elements.visible.checked);
                payload.mandatory = Boolean(elements.mandatory && elements.mandatory.checked);
                payload.link = Boolean(elements.link && elements.link.checked);
            }

            let saveUrl = '/omo/api/holons/save.php?cid=' + Number(state.data.contextHolonId || 0);
            if (getMode() === 'edit' && Number(state.data.holonId || 0) > 0) {
                saveUrl += '&hid=' + Number(state.data.holonId || 0);
            }

            const formData = new FormData();
            formData.append('payload', JSON.stringify(payload));
            if (mediaFields.icon) {
                mediaFields.icon.appendToFormData(formData);
            }
            if (mediaFields.banner) {
                mediaFields.banner.appendToFormData(formData);
            }

            return fetch(saveUrl, {
                method: 'POST',
                body: formData
            });
        })
        .then(function (response) {
            return response.json().then(function (data) {
                return {
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(function (result) {
            if (!result.ok || !result.data || result.data.status !== 'ok') {
                throw new Error(result.data && result.data.message ? result.data.message : (getMode() === 'edit' ? "Impossible d'enregistrer le holon." : "Impossible de créer le holon."));
            }

            const hashManagedEditorDrawer = isHashManagedHolonEditorDrawer();
            const hashManagedCreateDrawer = getMode() !== 'edit' && isHashManagedCreateDrawer();
            const route = typeof parseUrl === 'function'
                ? parseUrl()
                : {
                    oid: Number(state.data.organizationId || 0),
                    cid: null,
                    hash: null
                };
            const targetHolonId = Number(result.data.holon.id || 0);
            const externalDrawerContext = getExternalDrawerContext();
            const externalStructureHost = externalDrawerContext
                && String(externalDrawerContext.hostRouteToken || '').trim().toLowerCase() === 'structure';
            const currentRouteCid = Number(route && route.cid ? route.cid : 0);
            const shouldNavigate = targetHolonId > 0
                && typeof navigate === 'function'
                && Number(route && route.oid ? route.oid : 0) > 0
                && currentRouteCid !== targetHolonId;

            if (getMode() === 'edit' && typeof loadContent === 'function' && !shouldNavigate) {
                let leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || state.data.organizationId || 0);

                if (targetHolonId > 0) {
                    leftUrl += '&cid=' + targetHolonId;
                }

                loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);

                refreshStructureViews(targetHolonId > 0 ? targetHolonId : null, {
                    quickZoom: externalStructureHost ? false : true
                });

                if (externalDrawerContext) {
                    closeCreateDrawer();
                    if (
                        typeof window.omoRefreshExternalPanelDrawerHost === 'function'
                        && !externalStructureHost
                        && !shouldNavigate
                    ) {
                        window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                    }
                } else if (hashManagedEditorDrawer && typeof window.omoSetDrawerHashState === 'function') {
                    window.omoSetDrawerHashState({
                        open: false,
                        replace: true
                    });
                } else {
                    closeCreateDrawer();
                }
            } else if (typeof navigate === 'function' && shouldNavigate) {
                if (externalStructureHost) {
                    closeCreateDrawer();
                    navigate(route.oid, targetHolonId, hashManagedCreateDrawer ? null : (route.hash || null));
                    return;
                }

                const parentHolonId = Number((result.data.holon && result.data.holon.parentId) || state.data.contextHolonId || 0);
                const refreshPromise = refreshStructureViews(parentHolonId > 0 ? parentHolonId : null, {
                    quickZoom: true
                });

                refreshPromise
                    .then(function () {
                        if (externalDrawerContext) {
                            closeCreateDrawer();
                        }

                        navigate(route.oid, targetHolonId, hashManagedCreateDrawer ? null : (route.hash || null));

                        if (
                            externalDrawerContext
                            && !externalStructureHost
                            && typeof window.omoRefreshExternalPanelDrawerHost === 'function'
                        ) {
                            window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                        }
                    });
            } else if (typeof loadContent === 'function') {
                let leftUrl = 'api/getOrg.php?oid=' + Number(route.oid || state.data.organizationId || 0);

                if (targetHolonId > 0) {
                    leftUrl += '&cid=' + targetHolonId;
                }

                loadContent(typeof omoGetLeftPanelContentSelector === 'function' ? omoGetLeftPanelContentSelector() : '#panel-left', leftUrl);

                refreshStructureViews(targetHolonId > 0 ? targetHolonId : null, {
                    quickZoom: externalStructureHost ? false : true
                });

                if (externalDrawerContext) {
                    closeCreateDrawer();
                    if (!externalStructureHost && typeof window.omoRefreshExternalPanelDrawerHost === 'function') {
                        window.omoRefreshExternalPanelDrawerHost(externalDrawerContext.drawer);
                    }
                }
            }

            if (getMode() === 'edit') {
                return;
            }

            if (externalDrawerContext) {
                return;
            }

            if (!hashManagedCreateDrawer) {
                closeCreateDrawer();
            } else if (typeof navigate !== 'function' && typeof window.omoSetDrawerHashState === 'function') {
                window.omoSetDrawerHashState({
                    open: false,
                    replace: true
                });
            }
        })
        .catch(function (error) {
            showStatus(error && error.message ? error.message : (getMode() === 'edit' ? "Impossible d'enregistrer le holon." : "Impossible de créer le holon."), 'error');
        })
        .finally(function () {
            if (elements.form && typeof window.omoEndPendingAction === 'function') {
                window.omoEndPendingAction(elements.form);
            }
        });
}

Promise.all([
    waitForGlobalLibrary('omoSizedImageField', 5000),
    waitForGlobalLibrary('omoSimpleHtmlField', 5000)
]).finally(function () {
    fillFormFromState();
});

elements.template.addEventListener('change', function () {
    renderEditorMeta(getCurrentTemplate(), readProperties());
});

if (elements.addProperty) {
    elements.addProperty.addEventListener('click', function () {
        if (elements.addProperty.disabled || isTemplateEditing()) {
            return;
        }
        const defaultFormat = (state.data.formats || []).length
            ? state.data.formats[0]
            : { id: 1, name: 'Texte libre' };
        const emptyNote = elements.properties.querySelector('.omo-holon-create__empty-note');
        if (emptyNote) {
            emptyNote.remove();
        }
        const property = {
            id: 0,
            name: '',
            shortname: '',
            formatId: Number(defaultFormat.id || 1),
            formatName: String(defaultFormat.name || ''),
            listItemType: 'text',
            listHolonTypeIds: [],
            value: '',
            isDirectProperty: true,
            isTemplateProperty: false,
            canEditDefinition: true,
            canDelete: true,
            canEditValue: true
        };
        elements.properties.appendChild(createPropertyRow(property, elements.properties.querySelectorAll('.omo-holon-create__property').length));
        const nameField = elements.properties.lastElementChild ? elements.properties.lastElementChild.querySelector('.omo-holon-create__direct-property-name') : null;
        if (nameField) {
            nameField.focus();
        }
    });
}

if (elements.colorEnabled) {
    elements.colorEnabled.addEventListener('change', function () {
        syncColorField();
    });
}

if (elements.adminMinOverride) {
	elements.adminMinOverride.addEventListener('change', function () {
		if (elements.adminMin) {
			elements.adminMin.disabled = !elements.adminMinOverride.checked;
		}
	});
}

if (elements.adminMaxOverride) {
	elements.adminMaxOverride.addEventListener('change', function () {
		if (elements.adminMax) {
			elements.adminMax.disabled = !elements.adminMaxOverride.checked;
		}
	});
}

if (elements.permissionToggle) {
    elements.permissionToggle.addEventListener('click', function () {
        const isExpanded = !(elements.permissions && elements.permissions.hidden === false);
        setPermissionEditorExpanded(isExpanded);
    });
}

elements.form.addEventListener('submit', saveHolon);

elements.cancel.addEventListener('click', function () {
    closeCreateDrawer();
});

root.addEventListener('change', function (event) {
    if (event.target.matches('.omo-holon-create__direct-property-format, .omo-holon-create__direct-property-list-type')) {
        const propertyRow = event.target.closest('.omo-holon-create__property');
        if (!propertyRow) {
            return;
        }
        const propertyDraft = getDirectPropertyDraft(propertyRow);
        if (event.target.matches('.omo-holon-create__direct-property-format')) {
            propertyDraft.value = '';
        }
        const propertyRows = Array.from(elements.properties.querySelectorAll('.omo-holon-create__property'));
        const index = propertyRows.indexOf(propertyRow);
        propertyRow.replaceWith(createPropertyRow(propertyDraft, index >= 0 ? index : 0));
        return;
    }
    if (!event.target.matches('[data-authority-deletion-choice]')) {
        const authorityField = event.target.closest('.omo-holon-create__authority-parent, .omo-holon-create__authority-delegation');
        const authorityRow = authorityField && authorityField.closest('[data-authority-entry]');
        if (!authorityRow || Number(authorityRow.getAttribute('data-authority-id') || 0) > 0) {
            return;
        }
        authorityRow.outerHTML = renderAuthorityListRow({
            parentId: Number((authorityRow.querySelector('.omo-holon-create__authority-parent') || {}).value || 0),
            delegationMode: String((authorityRow.querySelector('.omo-holon-create__authority-delegation') || {}).value || 'partial'),
            label: String((authorityRow.querySelector('.omo-holon-create__authority-label') || {}).value || ''),
            description: String((authorityRow.querySelector('.omo-holon-create__authority-description') || {}).value || '')
        });
        return;
    }
    const authorityRow = event.target.closest('[data-authority-entry]');
    if (authorityRow) {
        updateAuthorityDeletionCounts(authorityRow);
    }
});

root.addEventListener('input', function (event) {
    if (!event.target.matches('.omo-holon-create__direct-property-name')) {
        return;
    }
    const propertyRow = event.target.closest('.omo-holon-create__property');
    const propertyTitle = propertyRow ? propertyRow.querySelector('.omo-holon-create__property-name') : null;
    if (propertyTitle) {
        propertyTitle.textContent = String(event.target.value || '').trim() || 'Nouvelle propriete';
    }
});

root.addEventListener('click', function (event) {
    const directPropertyRemoveButton = event.target.closest('[data-direct-property-remove]');
    if (directPropertyRemoveButton) {
        if (directPropertyRemoveButton.disabled) {
            return;
        }
        const propertyRow = directPropertyRemoveButton.closest('.omo-holon-create__property');
        if (propertyRow) {
            propertyRow.remove();
        }
        if (!elements.properties.querySelector('.omo-holon-create__property')) {
            elements.properties.innerHTML = '<div class="omo-holon-create__empty-note generic-description generic-description--compact">Ce modele ne definit aucune propriete.</div>';
        }
        return;
    }

    const authorityAddButton = event.target.closest('[data-authority-add]');
    if (authorityAddButton) {
        const list = authorityAddButton.closest('.omo-holon-create__authority-list');
        const items = list ? list.querySelector('.omo-holon-create__authority-items') : null;
        if (items) {
            items.insertAdjacentHTML('beforeend', renderAuthorityListRow({}));
            const labelField = items.lastElementChild ? items.lastElementChild.querySelector('.omo-holon-create__authority-label') : null;
            if (labelField) {
                labelField.focus();
            }
        }
        return;
    }

    const authorityRemoveButton = event.target.closest('[data-authority-remove]');
    if (authorityRemoveButton) {
        const authorityRow = authorityRemoveButton.closest('[data-authority-entry]');
        if (authorityRow) {
            authorityRow.remove();
        }
        return;
    }

    const authorityEditButton = event.target.closest('[data-authority-edit]');
    if (authorityEditButton) {
        const authorityRow = authorityEditButton.closest('[data-authority-entry]');
        const authorityId = Number(authorityRow && authorityRow.getAttribute('data-authority-id') || 0);
        if (authorityRow && authorityId > 0) {
            authorityRow.outerHTML = renderAuthorityListRow({ id: authorityId, editing: true });
            const labelField = root.querySelector('[data-authority-entry][data-authority-id="' + authorityId + '"] .omo-holon-create__authority-label');
            if (labelField) {
                labelField.focus();
            }
        }
        return;
    }

    const authorityDeleteButton = event.target.closest('button[data-authority-delete]');
    if (authorityDeleteButton) {
        const authorityRow = authorityDeleteButton.closest('[data-authority-entry]');
        if (authorityRow) {
            const isPendingDeletion = authorityRow.getAttribute('data-authority-delete') === '1';
            if (isPendingDeletion) {
                authorityRow.removeAttribute('data-authority-delete');
                authorityRow.classList.remove('is-pending-delete');
                authorityDeleteButton.setAttribute('aria-label', 'Supprimer l autorite');
                authorityDeleteButton.title = 'Supprimer l autorite';
            } else {
                authorityRow.setAttribute('data-authority-delete', '1');
                authorityRow.classList.add('is-pending-delete');
                authorityDeleteButton.setAttribute('aria-label', 'Annuler la suppression');
                authorityDeleteButton.title = 'Annuler la suppression';
                updateAuthorityDeletionCounts(authorityRow);
            }
        }
        return;
    }

    const addButton = event.target.closest('[data-list-add]');
    if (addButton) {
        const list = addButton.closest('.omo-holon-create__list');
        const items = list ? list.querySelector('.omo-holon-create__list-items') : null;
        if (!list || !items) {
            return;
        }

        items.insertAdjacentHTML('beforeend', renderSimpleListRow(list.getAttribute('data-list-item-type') || 'text', ''));
        return;
    }

    const moveButton = event.target.closest('[data-list-move]');
    if (moveButton) {
        const direction = Number(moveButton.getAttribute('data-list-move') || 0);
        const row = moveButton.closest('.omo-holon-create__list-row');
        const items = row && row.parentNode ? row.parentNode : null;
        if (!row || !items || !direction) {
            return;
        }

        if (direction < 0) {
            const previousRow = row.previousElementSibling;
            if (previousRow) {
                items.insertBefore(row, previousRow);
            }
        } else {
            const nextRow = row.nextElementSibling;
            if (nextRow) {
                items.insertBefore(nextRow, row);
            }
        }

        const input = row.querySelector('.omo-holon-create__property-value-item');
        if (input) {
            input.focus();
        }
        return;
    }

    const removeButton = event.target.closest('[data-list-remove]');
    if (removeButton) {
        const row = removeButton.closest('.omo-holon-create__list-row');
        const list = removeButton.closest('.omo-holon-create__list');
        const items = list ? list.querySelector('.omo-holon-create__list-items') : null;
        if (!row || !list || !items) {
            return;
        }

        row.remove();
        if (!items.querySelector('.omo-holon-create__list-row')) {
            items.insertAdjacentHTML('beforeend', renderSimpleListRow(list.getAttribute('data-list-item-type') || 'text', ''));
        }
    }
});
})();
</script>
<?php endif; ?>

<style>
.omo-holon-create__layout {
    display: block;
}

.omo-holon-create__panel {
    --generic-drawer-content-gap: var(--generic-space-4, 16px);
}

.omo-holon-create__section {
    --generic-section-gap: var(--generic-space-4, 16px);
}

.omo-holon-create__property {
    --generic-section-padding-block: 12px;
    --generic-section-padding-inline: 12px;
}

.omo-holon-create__status {
    padding: 12px 14px;
    border-radius: var(--radius-md);
    border: 1px solid transparent;
    box-shadow: var(--shadow-sm);
}

.omo-holon-create__status[hidden] {
    display: none !important;
}

.omo-holon-create__status.is-error {
    color: #991b1b;
    background: color-mix(in srgb, #dc2626 10%, white);
    border-color: color-mix(in srgb, #dc2626 22%, transparent);
}

.omo-holon-create__properties {
    display: grid;
    gap: var(--generic-space-4, 16px);
}

.omo-holon-create__grid {
    --generic-form-grid-min: 260px;
}

.omo-holon-create__grid > [hidden] {
    display: none !important;
}

.omo-holon-create__field {
    display: grid;
    align-content: start;
    gap: var(--generic-form-field-gap, var(--generic-space-2, 8px));
}

.omo-holon-create__field--full {
    grid-column: 1 / -1;
}

.omo-holon-create__color-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.omo-holon-create__color-body[hidden] {
    display: none !important;
}

.omo-holon-create__color-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--color-text-light);
}

.omo-holon-create__color-toggle input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-holon-create__toggles {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.omo-holon-create__toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    background: var(--color-surface-alt);
    color: var(--color-text);
    font-size: 0.9rem;
}

.omo-holon-create__toggle input {
    width: 16px;
    height: 16px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-holon-create__field > span {
    display: block;
    color: var(--color-text, #1f2937);
    font-size: var(--generic-type-size-sm, 0.875rem);
    font-weight: 700;
    line-height: var(--generic-type-line-title, 1.3);
}

.omo-holon-create__media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 14px;
    margin-top: 8px;
}

.omo-holon-create__permissions[hidden] {
    display: none !important;
}

.omo-holon-create__permission-summary {
    --generic-soft-panel-padding-block: 12px;
    --generic-soft-panel-padding-inline: 14px;
    --generic-soft-panel-radius: var(--radius-md);
    color: var(--color-text);
}

.omo-holon-create__permission-summary-line + .omo-holon-create__permission-summary-line {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid color-mix(in srgb, var(--color-border) 78%, transparent);
}

.omo-holon-create__permission-summary-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 8px;
}

.omo-holon-create__permission-summary-capsules {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-holon-create__permission-summary-empty {
    color: var(--color-text-light);
    line-height: 1.45;
}

.omo-holon-create__permission-pill {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 3px;
    padding: 8px 10px;
    border-radius: var(--radius-md);
    border: 1px solid color-mix(in srgb, var(--color-primary) 18%, var(--color-border));
    background: color-mix(in srgb, var(--color-primary) 8%, var(--color-surface));
    min-width: 120px;
    max-width: 100%;
}

.omo-holon-create__permission-pill-title {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1.25;
}

.omo-holon-create__permission-pill-scope {
    font-size: 0.68rem;
    color: var(--color-text-light);
    line-height: 1.2;
}

.omo-holon-create__permission-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--color-border);
}

.omo-holon-create__permission-tab {
    border: 0;
    border-bottom: 2px solid transparent;
    padding: 9px 12px;
    background: transparent;
    color: var(--color-text-light);
    cursor: pointer;
    font: inherit;
    font-weight: 700;
}

.omo-holon-create__permission-tab.is-active {
    border-bottom-color: var(--color-primary);
    color: var(--color-primary);
}

.omo-holon-create__permission-panel[hidden] {
    display: none !important;
}

.omo-holon-create__permission-table {
    display: grid;
    gap: 12px;
}

.omo-holon-create__permission-group {
    display: grid;
    gap: 10px;
}

.omo-holon-create__permission-group + .omo-holon-create__permission-group {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
}

.omo-holon-create__permission-group-title {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 7px 10px;
    border: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
    border-radius: var(--radius-sm);
    background: color-mix(in srgb, var(--color-surface) 96%, var(--color-surface-alt));
    color: var(--color-text-light);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.omo-holon-create__permission-row {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(240px, 0.9fr);
    gap: 12px;
    align-items: start;
    padding: 14px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
}

.omo-holon-create__permission-main {
    display: grid;
    gap: 6px;
}

.omo-holon-create__permission-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--color-text);
}

.omo-holon-create__permission-meta,
.omo-holon-create__permission-description,
.omo-holon-create__permission-empty {
    color: var(--color-text-light);
    line-height: 1.45;
}

.omo-holon-create__permission-picker,
.omo-holon-create__permission-tokens {
    display: grid;
    gap: 8px;
}

.omo-holon-create__permission-token {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    max-width: 100%;
    padding: 7px 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
    border: 1px solid color-mix(in srgb, var(--color-primary) 22%, transparent);
    color: var(--color-text);
}

.omo-holon-create__permission-token-remove {
    border: 0;
    background: transparent;
    color: var(--color-text-light);
    cursor: pointer;
    font: inherit;
    line-height: 1;
    padding: 0;
}

.omo-holon-create__media-card {
    --generic-soft-panel-padding-block: 14px;
    --generic-soft-panel-padding-inline: 14px;
    --generic-soft-panel-radius: var(--radius-md);
    --generic-soft-panel-background: var(--color-surface);
}

.omo-holon-create__media-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--color-text);
}

textarea.omo-holon-create__property-value {
    display: block;
    width: 100%;
    min-height: 110px;
    padding: 11px 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
    color: var(--color-text);
    font: inherit;
    box-sizing: border-box;
    resize: vertical;
}

textarea.omo-holon-create__property-value:focus {
    outline: none;
    border-color: color-mix(in srgb, var(--color-primary) 52%, var(--color-border));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 14%, transparent);
    background: var(--color-surface);
}

.omo-holon-create__template-meta,
.omo-holon-create__property-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.omo-holon-create__admin-bounds {
    --generic-form-grid-min: 260px;
}

.omo-holon-create__admin-bound-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

#omo-holon-create-admin-bounds-help {
    color: var(--color-text-light);
    line-height: 1.4;
}

.omo-holon-create__chip {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 8px;
    border-radius: 999px;
    font-size: 0.78rem;
    border: 1px solid color-mix(in srgb, var(--color-border) 84%, transparent);
    background: var(--color-surface-alt);
    color: var(--color-text-light);
}

.omo-holon-create__chip--accent {
    color: var(--color-primary);
    border-color: color-mix(in srgb, var(--color-primary) 28%, transparent);
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
}

.omo-holon-create__property {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 12px;
}

.omo-holon-create__property-index {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    min-height: 40px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary) 12%, var(--color-surface));
    color: var(--color-primary);
    font-size: 0.8rem;
    font-weight: 700;
}

.omo-holon-create__property-body {
    display: grid;
    gap: 12px;
    min-width: 0;
}

.omo-holon-create__property-input {
    display: grid;
    gap: 8px;
    min-width: 0;
}

.omo-holon-create__readonly-value .omo-holon-create__inherited-list {
    margin-top: 0;
}

.omo-holon-create__property-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.omo-holon-create__property-name {
    font-weight: 700;
    line-height: 1.35;
}

.omo-holon-create__property-meta--toggles {
    margin-top: -4px;
}

.omo-holon-create__property-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 6px 10px;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    background: var(--color-surface-alt);
    color: var(--color-text);
    font-size: 0.82rem;
}

.omo-holon-create__property-toggle input {
    width: 15px;
    height: 15px;
    margin: 0;
    accent-color: var(--color-primary);
}

.omo-holon-create__property-toggle input:disabled + span {
    opacity: 0.65;
}

.omo-holon-create__inherited {
    padding: 14px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface-alt) 80%, var(--color-surface));
}

.omo-holon-create__inherited-list {
    margin: 8px 0 0;
    padding-left: 20px;
    display: grid;
    gap: 6px;
    color: var(--color-text-light);
}

.omo-holon-create__locked-note,
.omo-holon-create__permission-note,
.omo-holon-create__empty-note {
    padding: 12px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
}

.omo-holon-create__check-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 8px;
}

.omo-holon-create__check-option {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
}

.omo-holon-create__check-option small {
    display: block;
    color: var(--color-text-light);
    line-height: 1.35;
}

.omo-holon-create__list,
.omo-holon-create__list-items {
    display: grid;
    gap: 8px;
}

.omo-holon-create__authority-list,
.omo-holon-create__authority-items {
    display: grid;
    gap: 8px;
}

.omo-holon-create__authority-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
    align-items: start;
    padding: 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface-alt);
}

.omo-holon-create__authority-row--existing {
    align-items: center;
}

.omo-holon-create__authority-row.is-needs-parent {
    border-color: color-mix(in srgb, var(--color-danger, #dc2626) 55%, var(--color-border));
    background: color-mix(in srgb, var(--color-danger, #dc2626) 10%, var(--color-surface-alt));
    color: var(--color-danger, #dc2626);
}

.omo-holon-create__authority-row.is-template-instance {
    border-style: dashed;
    background: color-mix(in srgb, var(--color-accent-soft, #e9f4f5) 68%, var(--color-surface-alt));
}

.omo-holon-create__authority-row.is-template-instance .omo-holon-create__authority-edit {
    font-style: italic;
    cursor: default;
}

.omo-holon-create__authority-row.is-template-origin-lost {
    border-color: color-mix(in srgb, var(--color-warning, #d97706) 55%, var(--color-border));
    background: color-mix(in srgb, var(--color-warning, #d97706) 10%, var(--color-surface-alt));
}

.omo-holon-create__authority-edit {
    border: 0;
    padding: 0;
    background: transparent;
    color: inherit;
    cursor: pointer;
    text-align: left;
}

.omo-holon-create__authority-row.is-pending-delete {
    color: var(--color-text-light);
    background: color-mix(in srgb, var(--color-danger, #dc2626) 6%, var(--color-surface-alt));
    border-style: dashed;
}

.omo-holon-create__authority-row.is-pending-delete .omo-holon-create__authority-edit,
.omo-holon-create__authority-row.is-pending-delete .omo-holon-create__authority-fields {
    pointer-events: none;
    text-decoration: line-through;
    opacity: 0.62;
}

.omo-holon-create__authority-deletion {
    display: none;
    grid-column: 1 / -1;
    gap: 10px;
    padding: 12px;
    border: 1px solid color-mix(in srgb, var(--color-danger, #dc2626) 35%, var(--color-border));
    border-radius: var(--radius-sm);
    background: var(--color-surface);
    color: var(--color-text);
}

.omo-holon-create__authority-row.is-pending-delete .omo-holon-create__authority-deletion {
    display: grid;
}

.omo-holon-create__authority-deletion p,
.omo-holon-create__authority-deletion fieldset {
    margin: 0;
}

.omo-holon-create__authority-deletion fieldset {
    display: grid;
    gap: 6px;
    min-width: 0;
    padding: 8px 10px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
}

.omo-holon-create__authority-deletion fieldset[hidden] {
    display: none;
}

.omo-holon-create__authority-deletion legend {
    padding: 0 4px;
    font-weight: 700;
}

.omo-holon-create__authority-deletion label {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    font-size: 0.9rem;
    line-height: 1.35;
}

.omo-holon-create__authority-row strong,
.omo-holon-create__authority-row small {
    display: block;
}

.omo-holon-create__authority-row small,
.omo-holon-create__authority-state {
    color: var(--color-text-light);
    font-size: 0.82rem;
    line-height: 1.35;
}

.omo-holon-create__authority-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.omo-holon-create__authority-description {
    grid-column: 1 / -1;
    min-height: 76px;
    resize: vertical;
}

.omo-holon-create__authority-complete-note {
    grid-column: 1 / -1;
    padding: 10px;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-sm);
    color: var(--color-text-light);
}

.omo-holon-create__list-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px 42px 42px;
    gap: 8px;
    align-items: center;
}

.omo-holon-create__list-row--detail {
    align-items: start;
}

.omo-holon-create__list-detail-fields {
    display: grid;
    gap: 8px;
}

.omo-holon-create__property-value-item--detail-description {
    min-height: 88px;
    resize: vertical;
}

.omo-holon-create__inherited-detail-list {
    display: grid;
    gap: 8px;
}

.omo-holon-create__detail-card {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-surface-alt) 65%, var(--color-surface));
    overflow: hidden;
}

.omo-holon-create__detail-card summary {
    cursor: pointer;
    padding: 10px 12px;
    font-weight: 600;
}

.omo-holon-create__detail-body {
    padding: 0 12px 12px;
    color: var(--color-text-light);
    line-height: 1.5;
    white-space: pre-line;
}

.omo-holon-create__footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    position: sticky;
    bottom: 0;
    z-index: 10;
    padding: 16px;
    margin-top: 8px;
    border-top: 1px solid color-mix(in srgb, var(--color-border) 86%, transparent);
    background: color-mix(in srgb, var(--color-surface) 92%, var(--color-surface-alt));
    box-shadow: 0 -8px 24px color-mix(in srgb, var(--color-shadow) 8%, transparent);
    backdrop-filter: blur(6px);
}

.omo-holon-create__actions {
    --generic-action-row-gap: var(--generic-space-2, 8px);
}

.omo-holon-create__button {
    min-height: 40px;
    padding: 8px 14px;
    border-radius: 999px;
    border: 1px solid var(--color-border);
    background: var(--color-surface-alt);
    color: var(--color-text);
    cursor: pointer;
    font: inherit;
}

.omo-holon-create__button--primary {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: var(--color-text-inverse);
}

.omo-holon-create__button--secondary {
    color: var(--color-primary);
    border-color: color-mix(in srgb, var(--color-primary) 24%, var(--color-border));
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface));
}

.omo-holon-create__button--ghost {
    background: var(--color-surface);
}

@media (max-width: 1024px) {
    .omo-holon-create__layout,
    .omo-holon-create__grid {
        grid-template-columns: 1fr;
    }

    .omo-holon-create__permission-row {
        grid-template-columns: 1fr;
    }

    .omo-holon-create__authority-fields {
        grid-template-columns: 1fr;
    }

    .omo-holon-create__footer,
    .omo-holon-create__section-head {
        flex-direction: column;
        align-items: stretch;
    }

    .omo-holon-create__property {
        grid-template-columns: 1fr;
    }
}
</style>
