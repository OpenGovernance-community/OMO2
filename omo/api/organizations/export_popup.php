<?php
require_once dirname(__DIR__) . '/bootstrap.php';

$sourceLang = [
    'organization_export.title' => ['text' => 'Exporter l organisation', 'context' => 'Title of the organization export popup.'],
    'organization_export.error.auth' => ['text' => 'Connexion requise.', 'context' => 'Authentication message in the organization export popup.'],
    'organization_export.help' => ['text' => 'Choisissez les elements a inclure. La structure est toujours exportee et le fichier JSON reste compatible avec l import OMO 2.', 'context' => 'Introductory text of the organization export popup.'],
    'organization_export.module.members' => ['text' => 'Membres et roles', 'context' => 'Members module label in the organization export popup.'],
    'organization_export.module.documents' => ['text' => 'Documents', 'context' => 'Documents module label in the organization export popup.'],
    'organization_export.module.projects' => ['text' => 'Projets', 'context' => 'Projects module label in the organization export popup.'],
    'organization_export.module.tasks' => ['text' => 'Taches et sous-projets', 'context' => 'Tasks module label in the organization export popup.'],
    'organization_export.module.checklists' => ['text' => 'Checklists', 'context' => 'Checklists module label in the organization export popup.'],
    'organization_export.module.indicators' => ['text' => 'Indicateurs et mesures', 'context' => 'Indicators module label in the organization export popup.'],
    'organization_export.module.calendar' => ['text' => 'Calendrier', 'context' => 'Calendar module label in the organization export popup.'],
    'organization_export.module.pv' => ['text' => 'Proces-verbaux', 'context' => 'Meeting minutes module label in the organization export popup.'],
    'organization_export.action.cancel' => ['text' => 'Annuler', 'context' => 'Cancel button in the organization export popup.'],
    'organization_export.action.download' => ['text' => 'Telecharger le JSON', 'context' => 'Download button in the organization export popup.'],
    'organization_export.structure' => ['text' => 'Structure', 'context' => 'Always selected structure module label in the organization export popup.'],
];
$lang = translationBundleInit('omo_organization_export_popup', omoGetTranslationLocale(), $sourceLang);
$currentUserId = (int)commonGetCurrentUserId();
if ($currentUserId <= 0) {
    http_response_code(403);
    echo '<div class="generic-soft-panel">' . htmlspecialchars(t('organization_export.error.auth', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') . '</div>';
    exit;
}
$modules = [
    'members' => t('organization_export.module.members', [], $lang, $sourceLang),
    'documents' => t('organization_export.module.documents', [], $lang, $sourceLang),
    'projects' => t('organization_export.module.projects', [], $lang, $sourceLang),
    'tasks' => t('organization_export.module.tasks', [], $lang, $sourceLang),
    'checklists' => t('organization_export.module.checklists', [], $lang, $sourceLang),
    'indicators' => t('organization_export.module.indicators', [], $lang, $sourceLang),
    'calendar' => t('organization_export.module.calendar', [], $lang, $sourceLang),
    'pv' => t('organization_export.module.pv', [], $lang, $sourceLang),
];
?>
<div class="omo-organization-export" data-omo-organization-export="1">
    <header class="generic-drawer-header generic-drawer-header--sticky">
        <div class="generic-drawer-header__copy">
            <div class="generic-card-title generic-card-title--eyebrow">JSON OMO 2</div>
            <h2 class="generic-card-title generic-card-title--large"><?= htmlspecialchars(t('organization_export.title', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars(t('organization_export.help', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>
    <form class="omo-organization-export__form generic-section generic-section--stack" data-omo-organization-export-form="1" method="get" action="/omo/api/organizations/export.php" target="_blank">
        <fieldset class="omo-organization-export__modules">
            <legend><?= htmlspecialchars(t('organization_export.structure', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></legend>
            <label class="omo-organization-export__module is-required">
                <input type="checkbox" checked disabled>
                <span><?= htmlspecialchars(t('organization_export.structure', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></span>
                <small>Obligatoire</small>
            </label>
            <?php foreach ($modules as $module => $label): ?>
            <label class="omo-organization-export__module">
                <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>" checked data-omo-export-module="<?= htmlspecialchars($module, ENT_QUOTES, 'UTF-8') ?>">
                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            </label>
            <?php endforeach; ?>
        </fieldset>
        <div class="omo-organization-export__actions">
            <button type="button" class="generic-action-button generic-action-button--secondary" data-omo-organization-export-cancel="1"><?= htmlspecialchars(t('organization_export.action.cancel', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="submit" class="generic-action-button generic-action-button--main"><?= htmlspecialchars(t('organization_export.action.download', [], $lang, $sourceLang), ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </form>
</div>
<style>
.omo-organization-export { display: flex; flex-direction: column; gap: 16px; color: var(--color-text, #1f2937); }
.omo-organization-export .generic-drawer-header p { margin: 8px 0 0; color: var(--color-text-light, #64748b); line-height: 1.45; }
.omo-organization-export__form { --generic-section-padding-block: 18px; --generic-section-padding-inline: 18px; }
.omo-organization-export__modules { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 9px; margin: 0; padding: 14px; border: 1px solid var(--color-border, #d1d5db); border-radius: var(--radius-md); }
.omo-organization-export__modules legend { padding: 0 6px; font-weight: 700; }
.omo-organization-export__module { display: grid; grid-template-columns: auto 1fr; column-gap: 8px; align-items: center; padding: 7px; border-radius: 8px; }
.omo-organization-export__module input { grid-row: 1 / span 2; }
.omo-organization-export__module small { color: var(--color-text-light, #64748b); font-size: 12px; }
.omo-organization-export__actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; }
</style>
<script>
(function () {
    var root = document.querySelector('[data-omo-organization-export="1"]');
    if (!root) { return; }
    var cancel = root.querySelector('[data-omo-organization-export-cancel="1"]');
    if (cancel) {
        cancel.addEventListener('click', function () {
            if (typeof window.commonTopbarCloseModal === 'function') { window.commonTopbarCloseModal(); }
        });
    }
    var projects = root.querySelector('[data-omo-export-module="projects"]');
    var tasks = root.querySelector('[data-omo-export-module="tasks"]');
    var calendar = root.querySelector('[data-omo-export-module="calendar"]');
    var pv = root.querySelector('[data-omo-export-module="pv"]');
    if (tasks && projects) {
        tasks.addEventListener('change', function () {
            if (tasks.checked) { projects.checked = true; }
        });
        projects.addEventListener('change', function () {
            if (!projects.checked) { tasks.checked = false; }
        });
    }
    if (pv && calendar) {
        pv.addEventListener('change', function () {
            if (pv.checked) { calendar.checked = true; }
        });
        calendar.addEventListener('change', function () {
            if (!calendar.checked) { pv.checked = false; }
        });
    }
})();
</script>
