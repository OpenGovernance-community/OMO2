'use strict';

const fs = require('fs');
const path = require('path');

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

function read(relativePath) {
  return fs.readFileSync(path.join(__dirname, '..', relativePath), 'utf8');
}

const appSource = read('omo/assets/js/app.js');
assert(
  appSource.includes('function omoIsPvApplicationTabContext'),
  'The shared application runtime must detect PV application tab contexts.'
);
assert(
  appSource.includes('window.omoIsPvApplicationTabContext = omoIsPvApplicationTabContext;'),
  'PV application context detection must be available to embedded applications.'
);
assert(
  appSource.includes('function omoPreparePvApplicationSubdrawers')
    && appSource.includes("drawer.classList.add('omo-overlay-drawer--detail-panel');"),
  'Every internal drawer loaded by a PV application tab must receive the bounded detail-panel style.'
);
assert(
  appSource.includes('omoPreparePvApplicationSubdrawers($target.get(0));')
    && appSource.includes('omoPreparePvApplicationSubdrawers(nextRoot);'),
  'PV subdrawers must remain bounded after both initial loads and application view refreshes.'
);

const stylesSource = read('omo/assets/css/styles.css');
assert(
  stylesSource.includes('.omo-external-panel-drawer--headerless > .omo-overlay-drawer__panel > .omo-overlay-drawer__header'),
  'Headerless PV drawers must only hide their own direct header.'
);
assert(
  !stylesSource.includes('.omo-external-panel-drawer--headerless .omo-overlay-drawer__header {'),
  'Headerless PV drawers must not hide nested application drawer headers.'
);
assert(
  stylesSource.includes('.omo-external-panel-drawer > .omo-overlay-drawer__panel'),
  'The PV drawer width must only apply to its direct panel.'
);
assert(
  stylesSource.includes('.omo-overlay-drawer.is-open > .omo-overlay-drawer__panel'),
  'An open parent drawer must not force nested panels into their visible state.'
);
assert(
  stylesSource.includes('.omo-overlay-drawer--detail-panel > .omo-overlay-drawer__panel'),
  'Application detail drawers must use the shared bounded side-panel style.'
);
assert(
  stylesSource.includes('width: min(880px, 94vw, 100%);'),
  'Application detail drawers must never exceed their PV application column.'
);
assert(
  stylesSource.includes('transform: translateX(100%);'),
  'Application detail drawers must enter from the right.'
);

const subdrawerSource = read('common/drawer/subdrawer.js');
assert(
  subdrawerSource.includes('void drawer.offsetWidth;') && subdrawerSource.includes('open: open'),
  'The shared subdrawer controller must paint its closed state before starting the opening transition.'
);

[
  'omo/api/projects/projects.js',
  'omo/api/checklist/checklist.js',
  'omo/api/stats/index.php',
  'omo/api/calendar/index.php',
  'omo/api/decision/index.php'
].forEach(function (relativePath) {
  const source = read(relativePath);
  assert(
    source.includes('useLocalDrawerNavigation'),
    relativePath + ' must keep detail navigation local inside a PV application tab.'
  );
  assert(
    source.includes('!useLocalDrawerNavigation &&'),
    relativePath + ' must bypass global hash navigation in a PV application tab.'
  );
});

const documentsSource = read('omo/api/documents/index.php');
assert(
  documentsSource.includes('function useLocalDrawerNavigation(rootOverride)'),
  'Documents must detect local PV drawer navigation.'
);
assert(
  documentsSource.includes("detailDrawerController && typeof detailDrawerController.open === 'function'"),
  'Document details must use the shared animated subdrawer opener when available.'
);
assert(
  documentsSource.includes("!empty($applicationViewPreferences['isPvApplicationTab']) ? ' omo-overlay-drawer--detail-panel' : ''"),
  'The Documents application must render its bounded drawer class server-side in a PV tab.'
);
assert(
  documentsSource.includes('if (useLocalDrawerNavigation(documentsRoot))')
    && documentsSource.includes('window.omoOpenDocumentPvPreparationByPayload = function (documentItem, rootOverride)'),
  'The Documents meeting tab must never launch another PV preparation top sheet.'
);
assert(
  documentsSource.includes('!useLocalDrawerNavigation(root) && routeToken'),
  'Document details must bypass global hash navigation inside a PV tab.'
);
assert(
  documentsSource.includes("'canOpenInPvApplicationTab' => $canOpenInPvApplicationTab")
    && documentsSource.includes('function isPvDocumentBlockedInLocalNavigation(documentItem, rootOverride)')
    && documentsSource.includes('data-omo-document-can-open-in-pv-tab'),
  'The Documents meeting tab must expose and enforce whether a PV is validated before opening it.'
);
assert(
  documentsSource.includes('isPvDocumentBlockedInLocalNavigation(documentPayload, root)')
    && documentsSource.includes('documentItem.canOpenInPvApplicationTab !== true'),
  'Unvalidated PV documents must be blocked before detail, keyboard, or editor navigation can replace the meeting drawer.'
);
assert(
  documentsSource.includes('if (!useLocalDrawerNavigation(root) && !window.omoPreserveDocumentPvPreparationDrawer())'),
  'Opening a document locally must not collapse or close the persistent meeting drawer.'
);

const documentEditorSource = read('omo/api/documents/create.php');
assert(
  documentEditorSource.includes('window.omoIsPvApplicationTabContext(form)'),
  'Saving a document inside a PV tab must not update the global hash.'
);

const projectSource = read('omo/api/projects/projects.js');
assert(
  read('omo/api/projects/index.php').includes('omo-overlay-drawer--detail-panel omo-projects__drawer'),
  'Project details must use the bounded animated side panel.'
);
assert(
  read('omo/api/stats/index.php').includes('omo-overlay-drawer--detail-panel omo-stats__detail-drawer'),
  'Indicator details must use the bounded animated side panel.'
);
assert(
  projectSource.includes("typeof drawerController.open === 'function'")
    && read('omo/api/stats/index.php').includes("typeof drawerController.open === 'function'"),
  'Project and indicator drawers must use the shared animated opening controller.'
);
assert(
  projectSource.includes("openProjectDocumentDrawer('/omo/api/documents/detail.php"),
  'Documents opened from a project detail must stay in an internal PV subdrawer.'
);
assert(
  projectSource.includes("openProjectDocumentDrawer('/omo/api/calendar/detail.php"),
  'Events opened from a project detail must stay in an internal PV subdrawer.'
);

[
  ['omo/api/projects/index.php', 'data-omo-projects-drawer-close'],
  ['omo/api/checklist/index.php', 'data-checklist-drawer-close'],
  ['omo/api/stats/index.php', 'data-omo-stats-drawer-close'],
  ['omo/api/calendar/index.php', 'data-omo-calendar-editor-close'],
  ['omo/api/decision/index.php', 'data-omo-decision-editor-close'],
  ['omo/api/documents/index.php', 'data-omo-document-detail-close']
].forEach(function (entry) {
  const source = read(entry[0]);
  assert(source.includes('omo-overlay-drawer__backdrop'), entry[0] + ' must expose a clickable drawer backdrop.');
  assert(source.includes('generic-drawer-header'), entry[0] + ' must expose a drawer header.');
  assert(source.includes(entry[1]), entry[0] + ' must expose a close action.');
});

process.stdout.write('pv_application_subdrawers_test: OK\n');
