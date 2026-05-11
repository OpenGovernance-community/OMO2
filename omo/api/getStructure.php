<?php
require_once __DIR__ . '/bootstrap.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
if ($organizationId > 0) {
    $organization = new \dbObject\Organization();
    if ($organization->load($organizationId) && !$organization->canViewDetail()) {
        http_response_code(403);
        echo '<div class="error">Acces refuse a cette organisation.</div>';
        exit;
    }

    if ($organization->load($organizationId) && $organization->getStructuralRootHolon() === null) {
        require_once __DIR__ . '/organization_setup_panel.php';
        omoRenderOrganizationSetupPanel($organization);
        exit;
    }
}

$structureDataParams = array();
if (isset($_GET['oid']) && is_numeric($_GET['oid'])) {
    $structureDataParams['oid'] = (int)$_GET['oid'];
}
if (isset($_GET['cid']) && is_numeric($_GET['cid'])) {
    $structureDataParams['cid'] = (int)$_GET['cid'];
}
$initialCid = isset($_GET['cid']) && is_numeric($_GET['cid']) ? (int)$_GET['cid'] : 0;

$structureDataUrl = 'api/getStructureData.php';
if (count($structureDataParams) > 0) {
    $structureDataUrl .= '?' . http_build_query($structureDataParams);
}

$isShareMode = function_exists('commonGetCurrentShareToken') && commonGetCurrentShareToken() !== '';
$canCreateShareLink = !$isShareMode && (int)commonGetCurrentUserId() > 0 && commonCurrentUserHasOrganizationAccess($organizationId);
$canExportStructure = !$isShareMode && (int)commonGetCurrentUserId() > 0 && commonCurrentUserHasOrganizationAccess($organizationId);
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js"></script>

<style>
    #contentright {
      font-family: Arial, sans-serif;
      background: #f2f2f2;
    }

    #chart {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
    }

    canvas {
      display: block;
    }

#contentright {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

#chart,
#role_list {
  position: absolute;
  inset: 0;
}

#chart {
  z-index: 1;
}

#role_list {
  display: none;
  overflow: auto;
  background: var(--color-surface, #fff);
  color: var(--color-text, #1f2937);
  z-index: 2;
}

#contentright.list-mode #role_list {
  display: block;
  pointer-events: auto;
}

#contentright.list-mode #chart,
#contentright.list-mode #chart canvas {
  pointer-events: none;
}

.chart-toggle {
  position: absolute;
  right: 16px;
  bottom: 16px;
  z-index: 10;
}

.structure-actions {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
}

.structure-actions__toggle {
  min-width: 44px;
  min-height: 44px;
  padding: 0 14px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 14px;
  background: color-mix(in srgb, var(--color-surface, #ffffff) 96%, transparent);
  color: var(--color-text, #1f2937);
  font-size: 24px;
  line-height: 1;
  cursor: pointer;
  box-shadow: var(--shadow-md, 0 12px 24px rgba(0,0,0,0.12));
}

.structure-actions__toggle:hover,
.structure-actions__toggle:focus-visible {
  background: var(--color-surface, #ffffff);
}

.structure-actions__panel {
  display: none;
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 180px;
  padding: 8px;
  border: 1px solid var(--color-border, #e5e7eb);
  border-radius: 16px;
  background: color-mix(in srgb, var(--color-surface, #ffffff) 98%, transparent);
  box-shadow: var(--shadow-md, 0 12px 24px rgba(0,0,0,0.12));
}

.structure-actions.is-open .structure-actions__panel {
  display: grid;
  gap: 4px;
}

.structure-actions__item {
  width: 100%;
  padding: 11px 12px;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: var(--color-text, #1f2937);
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.structure-actions__item:hover,
.structure-actions__item:focus-visible {
  background: var(--color-surface-alt, #f3f4f6);
}

.structure-browser-warning {
  position: absolute;
  right: 16px;
  bottom: 16px;
  z-index: 11;
  max-width: min(420px, calc(100% - 32px));
  padding: 12px 14px;
  border: 1px solid #f5c2c7;
  border-radius: 14px;
  background: #fff3cd;
  color: #7c2d12;
  box-shadow: var(--shadow-sm, 0 8px 18px rgba(0,0,0,0.08));
  font-size: 14px;
  line-height: 1.45;
}

.structure-browser-warning.is-collapsed {
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: transparent;
  box-shadow: none;
}

.structure-browser-warning[hidden] {
  display: none;
}

.structure-browser-warning:not([hidden]) ~ .chart-toggle {
  display: none;
}

.structure-browser-warning__content {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.structure-browser-warning.is-collapsed .structure-browser-warning__content {
  display: none;
}

.structure-browser-warning__message {
  flex: 1 1 auto;
}

.structure-browser-warning__dismiss,
.structure-browser-warning__restore {
  border: 1px solid rgba(124, 45, 18, 0.18);
  background: rgba(255, 255, 255, 0.72);
  color: inherit;
  font: inherit;
  cursor: pointer;
}

.structure-browser-warning__dismiss {
  flex: 0 0 auto;
  min-width: 32px;
  min-height: 32px;
  padding: 0 10px;
  border-radius: 999px;
}

.structure-browser-warning__restore {
  display: none;
  align-items: center;
  justify-content: center;
  min-height: 34px;
  padding: 6px 12px;
  border-radius: 999px;
  box-shadow: var(--shadow-sm, 0 8px 18px rgba(0,0,0,0.08));
}

.structure-browser-warning.is-collapsed .structure-browser-warning__restore {
  display: inline-flex;
}

.switch {
  display: inline-block;
  width: 52px;
  height: 32px;
}

.switch input {
  display: none;
}

.slider {
  position: absolute;
  inset: 0;
  cursor: pointer;
  border-radius: 999px;
  background: var(--color-surface-alt, #f0f2f5);
  border: 1px solid var(--color-border, #e5e7eb);
  box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
  font-size: 14px;
  line-height: 32px;
  padding: 0 8px;
  user-select: none;
  white-space: pre;
}

.slider::before {
  content: "";
  position: absolute;
  width: 24px;
  height: 24px;
  left: 3px;
  top: 3px;
  border-radius: 50%;
  background: var(--color-primary, #4f46e5);
  transition: transform 0.2s ease;
}

input:checked + .slider::before {
  transform: translateX(20px);
}

.role-list,
.role-list ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.role-list-panel {
  min-height: 100%;
}

.role-list-toolbar {
  position: sticky;
  top: 0;
  z-index: 3;
  padding: 16px 16px 12px;
  background: var(--color-surface, #fff);
  border-bottom: 1px solid var(--color-border, #e5e7eb);
}

.role-list-search {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--color-border, #d1d5db);
  border-radius: var(--radius-sm, 6px);
  background: var(--color-surface-alt, #f9fafb);
  color: inherit;
  font: inherit;
  box-sizing: border-box;
}

.role-list-results {
  padding: 16px;
}

.role-list ul {
  margin-left: 18px;
  padding-left: 14px;
  border-left: 1px solid var(--color-border, #e5e7eb);
}

.role-list li {
  margin: 8px 0;
}

.role-row {
  display: grid;
  gap: 8px;
}

.role-item-shell {
  display: grid;
  gap: 0;
  border-radius: 10px;
  background: var(--color-surface-alt, #f0f2f5);
  box-shadow: inset 0 0 0 1px transparent;
  overflow: hidden;
  opacity: var(--role-depth-opacity, 1);
}

.role-item-shell.match-direct {
  background: #fff7d6;
  box-shadow: inset 0 0 0 1px rgba(217, 119, 6, 0.22);
}

.role-item-shell.match-content {
  background: #eef6ff;
  box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.18);
}

.role-item-main {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  width: 100%;
}

.role-detail-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  align-self: stretch;
  width: 38px;
  min-width: 38px;
  padding: 8px 10px 8px 4px;
  border: 0;
  background: transparent;
  color: var(--color-text-muted, #6b7280);
  cursor: pointer;
  transition: color 0.16s ease;
}

.role-detail-toggle:hover,
.role-detail-toggle:focus-visible {
  color: var(--color-text, #1f2937);
}

.role-detail-toggle-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.72);
  transition: transform 0.16s ease, background 0.16s ease;
}

.role-detail-toggle:hover .role-detail-toggle-icon,
.role-detail-toggle:focus-visible .role-detail-toggle-icon {
  background: rgba(255, 255, 255, 0.96);
}

.role-detail-toggle.is-open .role-detail-toggle-icon {
  transform: rotate(90deg);
}

.role-detail-toggle--placeholder {
  width: 38px;
  min-width: 38px;
}

.role-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  width: 100%;
  padding: 8px 10px;
  background: transparent;
  cursor: pointer;
  border: 0;
  text-align: left;
  color: inherit;
}

.role-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex: 0 0 10px;
  margin-top: 5px;
}

.role-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1 1 auto;
}

.role-label {
  font-weight: 500;
}

.role-excerpt {
  font-size: 12px;
  line-height: 1.4;
  color: var(--color-text-muted, #6b7280);
}

.role-highlight {
  background: #fff3a3;
  color: inherit;
  padding: 0 1px;
  border-radius: 2px;
}

.role-list-empty {
  padding: 20px 4px;
  color: var(--color-text-muted, #6b7280);
  font-size: 14px;
}

.role-item-detail {
  display: none;
  padding: 0 14px 14px 14px;
  background: transparent;
  border-top: 1px solid rgba(148, 163, 184, 0.2);
}

.role-item-detail.is-open {
  display: block;
}

.role-properties {
  display: grid;
  gap: 12px;
}

.role-property {
  display: grid;
  gap: 6px;
}

.role-property + .role-property {
  padding-top: 12px;
  border-top: 1px solid var(--color-border, #eef2f7);
}

.role-property-label {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: var(--color-text-muted, #6b7280);
}

.role-property-value {
  color: var(--color-text, #1f2937);
  line-height: 1.5;
}

.role-property-text p,
.role-property-html p {
  margin: 0 0 8px;
}

.role-property-text p:last-child,
.role-property-html p:last-child {
  margin-bottom: 0;
}

.role-property-list {
  margin: 0;
  padding-left: 18px;
}

.role-property-list li + li {
  margin-top: 4px;
}

.role-property-detail-list {
  display: grid;
  gap: 10px;
}

.role-property-detail-card {
  padding: 10px 12px;
  border-radius: 8px;
  background: var(--color-surface-alt, #f8fafc);
  border: 1px solid var(--color-border, #e5e7eb);
}

.role-property-detail-card__title {
  font-weight: 600;
}

.role-property-detail-card__body {
  margin-top: 6px;
  color: var(--color-text-muted, #4b5563);
}
</style>
    <div id="contentright" class="contentright">
        <div id="chart"></div>
        <div id="role_list" class="filter_zone"></div>
        <div id="omoStructureCanvasWarning" class="structure-browser-warning" hidden>
            <div class="structure-browser-warning__content">
                <div id="omoStructureCanvasWarningMessage" class="structure-browser-warning__message"></div>
                <button type="button" id="omoStructureCanvasWarningDismiss" class="structure-browser-warning__dismiss" aria-label="Reduire ce message">x</button>
            </div>
            <button type="button" id="omoStructureCanvasWarningRestore" class="structure-browser-warning__restore">Info navigateur</button>
        </div>
        <div class="structure-actions" id="omoStructureActions">
            <button type="button" class="structure-actions__toggle" id="omoStructureActionsToggle" aria-label="Actions">...</button>
            <div class="structure-actions__panel" id="omoStructureActionsPanel">
                <?php if ($canExportStructure) { ?>
                    <button type="button" class="structure-actions__item" data-omo-structure-action="export">Export</button>
                <?php } ?>
                <?php if ($canCreateShareLink) { ?>
                    <button type="button" class="structure-actions__item" data-omo-structure-action="share">Partager</button>
                <?php } ?>
                <button type="button" class="structure-actions__item" data-omo-structure-action="print">Imprimer</button>
            </div>
        </div>

        <div class="switch chart-toggle">
            <input type="checkbox" id="toggleSwitch" />
            <label for="toggleSwitch" class="slider">O   L</label>
        </div>
    </div>

  <script>

function escapeHtml(text) {
  return String(text).replace(/[&<>"']/g, function (char) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;"
    }[char];
  });
}

function colorToTransparentFill(color, alpha, fallback) {
  const rawColor = String(color || "").trim();
  const fallbackColor = String(fallback || "rgba(79, 70, 229, 0.12)");
  const targetAlpha = Number(alpha);

  function convertColor(sourceColor) {
    const effectiveColor = String(sourceColor || "").trim();
    if (!effectiveColor) {
      return "";
    }

    const hexMatch = effectiveColor.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (hexMatch) {
      let hex = hexMatch[1];
      if (hex.length === 3) {
        hex = hex.split("").map(function (char) { return char + char; }).join("");
      }

      const red = parseInt(hex.slice(0, 2), 16);
      const green = parseInt(hex.slice(2, 4), 16);
      const blue = parseInt(hex.slice(4, 6), 16);
      return "rgba(" + red + ", " + green + ", " + blue + ", " + targetAlpha + ")";
    }

    const rgbMatch = effectiveColor.match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*[0-9.]+\s*)?\)$/i);
    if (rgbMatch) {
      return "rgba(" + rgbMatch[1] + ", " + rgbMatch[2] + ", " + rgbMatch[3] + ", " + targetAlpha + ")";
    }

    return "";
  }

  const convertedColor = convertColor(rawColor);
  if (convertedColor) {
    return convertedColor;
  }

  const convertedFallbackColor = convertColor(fallbackColor);
  if (convertedFallbackColor) {
    return convertedFallbackColor;
  }

  if (!rawColor) {
    return fallbackColor;
  }

  return fallbackColor;
}

function getListColor(node) {
  if (node.type == "4") return node.mycolor || chartColors.rootFill;
  if (node.type == "2" || node.type == "3") return colorToTransparentFill(node.mycolor, 0.12, chartColors.groupFill);
  return node.mycolor || chartColors.roleFill;
}

function getGroupStrokeColor(node) {
  return colorToTransparentFill(node && node.mycolor, 0.55, chartColors.strokeSoft);
}

function clampNumber(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function getNodeDepth(node) {
  const depth = Number(node && node.depth);
  return Number.isFinite(depth) && depth >= 0 ? depth : 0;
}

function getCurrentStructureDepth() {
  if (currentnode) {
    return getNodeDepth(currentnode);
  }

  if (root) {
    return getNodeDepth(root);
  }

  return 0;
}

function getNodeDepthOpacity(node, minOpacity, maxOpacity) {
  const safeMinOpacity = clampNumber(Number(minOpacity), 0, 1);
  const safeMaxOpacity = clampNumber(Number(maxOpacity), safeMinOpacity, 1);
  const distanceFromCurrentLevel = Math.abs(getNodeDepth(node) - getCurrentStructureDepth());
  const opacityStep = 0.18;
  const fadeDistance = Math.max(0, distanceFromCurrentLevel - 1);
  return clampNumber(safeMaxOpacity - (fadeDistance * opacityStep), safeMinOpacity, safeMaxOpacity);
}

function getNodeVisualOpacity(node) {
  return getNodeDepthOpacity(node, 0.24, 1);
}

function getNodeTextOpacity(node) {
  return clampNumber(getNodeDepthOpacity(node, 0.2, 1) * textAlpha, 0, 1);
}

function getNodePackSize(node, inheritedSize) {
  const baseSize = Math.max(2, Number(inheritedSize) || 2);

  if (!node || String(node.type) !== "1") {
    return baseSize;
  }

  const depthPenalty = Math.max(0, getNodeDepth(node) - 1) * 1.4;
  return Math.max(2, baseSize - depthPenalty);
}

function escapeRegExp(text) {
  return String(text).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function normalizeSearchText(text) {
  return String(text || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .trim();
}

function getHighlightRanges(text, query) {
  const source = String(text || "");
  const normalizedQuery = normalizeSearchText(query);

  if (!normalizedQuery) {
    return [];
  }

  let normalizedSource = "";
  const indexMap = [];

  for (let index = 0; index < source.length; index += 1) {
    const normalizedChar = source.charAt(index)
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();

    for (let charIndex = 0; charIndex < normalizedChar.length; charIndex += 1) {
      normalizedSource += normalizedChar.charAt(charIndex);
      indexMap.push(index);
    }
  }

  const ranges = [];
  let startAt = 0;

  while (startAt < normalizedSource.length) {
    const foundAt = normalizedSource.indexOf(normalizedQuery, startAt);

    if (foundAt === -1) {
      break;
    }

    const originalStart = indexMap[foundAt];
    const originalEnd = indexMap[foundAt + normalizedQuery.length - 1] + 1;
    const lastRange = ranges.length ? ranges[ranges.length - 1] : null;

    if (lastRange && originalStart <= lastRange.end) {
      lastRange.end = Math.max(lastRange.end, originalEnd);
    } else {
      ranges.push({ start: originalStart, end: originalEnd });
    }

    startAt = foundAt + normalizedQuery.length;
  }

  return ranges;
}

function highlightLabel(text, query) {
  const source = String(text || "");
  const ranges = getHighlightRanges(source, query);

  if (!ranges.length) {
    return escapeHtml(source);
  }

  let html = "";
  let lastIndex = 0;

  ranges.forEach(function (range) {
    if (range.start > lastIndex) {
      html += escapeHtml(source.slice(lastIndex, range.start));
    }

    html += `<mark class="role-highlight">${escapeHtml(source.slice(range.start, range.end))}</mark>`;
    lastIndex = range.end;
  });

  if (lastIndex < source.length) {
    html += escapeHtml(source.slice(lastIndex));
  }

  return html;
}

function getNodeSearchEntries(node) {
  const entries = [];
  const data = node && node.data && typeof node.data === "object" ? node.data : null;

  if (!data) {
    return entries;
  }

  Object.keys(data).forEach(function (key) {
    const item = data[key];

    if (item && typeof item === "object") {
      const effectiveValue = item.effectiveValue || item.value || item.ancestor || "";
      if (effectiveValue) {
        entries.push(String(effectiveValue));
      }
      return;
    }

    if (item) {
      entries.push(String(item));
    }
  });

  return entries;
}

function getNodeContentSearchText(node) {
  return getNodeSearchEntries(node).join(" ");
}

function trimExcerptBoundary(text, index, direction) {
  let currentIndex = index;

  while (currentIndex > 0 && currentIndex < text.length && /\S/.test(text.charAt(currentIndex))) {
    currentIndex += direction;
  }

  if (direction < 0) {
    return Math.max(0, currentIndex);
  }

  return Math.min(text.length, currentIndex);
}

function buildContentExcerptHtml(text, query) {
  const source = String(text || "").trim();
  const ranges = getHighlightRanges(source, query);

  if (!source || !ranges.length) {
    return "";
  }

  const excerptRadius = 80;
  const firstRange = ranges[0];
  let start = Math.max(0, firstRange.start - excerptRadius);
  let end = Math.min(source.length, firstRange.end + excerptRadius);

  if (start > 0) {
    start = trimExcerptBoundary(source, start, -1);
  }

  if (end < source.length) {
    end = trimExcerptBoundary(source, end, 1);
  }

  const prefix = start > 0 ? "..." : "";
  const suffix = end < source.length ? "..." : "";
  const excerpt = source.slice(start, end).trim();

  return prefix + highlightLabel(excerpt, query) + suffix;
}

function getNodeMatchExcerpt(node, query) {
  const entries = getNodeSearchEntries(node);

  for (let index = 0; index < entries.length; index += 1) {
    const entry = entries[index];

    if (getHighlightRanges(entry, query).length) {
      return buildContentExcerptHtml(entry, query);
    }
  }

  return "";
}

function nl2brHtml(text) {
  return escapeHtml(String(text || "")).replace(/\r\n|\r|\n/g, "<br>");
}

function splitListItems(text) {
  const source = String(text || "").trim();

  if (!source) {
    return [];
  }

  const parts = source.indexOf("|") !== -1
    ? source.split("|")
    : source.split(/\r\n|\r|\n/);

  return parts
    .map(function (item) {
      return String(item || "").trim();
    })
    .filter(function (item) {
      return item !== "";
    });
}

function parseListItems(rawValue) {
  const source = String(rawValue || "").trim();

  if (!source) {
    return [];
  }

  try {
    const decoded = JSON.parse(source);
    if (Array.isArray(decoded)) {
      return decoded;
    }
  } catch (error) {
  }

  return splitListItems(source);
}

function normalizeDetailedListItem(item) {
  if (item && typeof item === "object" && !Array.isArray(item)) {
    return {
      title: String(item.title || item.label || item.value || "").trim(),
      description: String(item.description || item.text || "").trim()
    };
  }

  return {
    title: String(item || "").trim(),
    description: ""
  };
}

function formatDateValue(value) {
  const source = String(value || "").trim();
  const dateMatch = source.match(/^(\d{4})-(\d{2})-(\d{2})/);

  if (dateMatch) {
    return dateMatch[3] + "." + dateMatch[2] + "." + dateMatch[1];
  }

  return source;
}

function formatListItemValue(item, entry) {
  if (String(entry && entry.listItemType || "") === "date") {
    return formatDateValue(item);
  }

  if (item && typeof item === "object" && !Array.isArray(item)) {
    return String(item.label || item.value || item.title || "").trim();
  }

  return String(item || "").trim();
}

function getPropertyDisplayLabel(entry) {
  return String(entry && (entry.name || entry.shortname || entry.key) || "").trim();
}

function getNodePropertyEntries(node) {
  const data = node && node.data && typeof node.data === "object" ? node.data : null;

  if (!data) {
    return [];
  }

  return Object.keys(data)
    .map(function (key) {
      const item = data[key];

      if (!item || typeof item !== "object") {
        return null;
      }

      return Object.assign(
        {
          key: key,
          position: Number.MAX_SAFE_INTEGER,
          name: "",
          shortname: "",
          effectiveValue: "",
          value: "",
          ancestor: "",
          formatId: 0,
          listItemType: ""
        },
        item
      );
    })
    .filter(function (item) {
      if (!item) {
        return false;
      }

      return String(item.effectiveValue || item.value || item.ancestor || "").trim() !== "";
    })
    .sort(function (left, right) {
      const leftPosition = Number(left.position || Number.MAX_SAFE_INTEGER);
      const rightPosition = Number(right.position || Number.MAX_SAFE_INTEGER);

      if (leftPosition !== rightPosition) {
        return leftPosition - rightPosition;
      }

      return getPropertyDisplayLabel(left).localeCompare(getPropertyDisplayLabel(right));
    });
}

function renderDetailListPropertyHtml(entry) {
  const items = parseListItems(entry.effectiveValue || entry.value || entry.ancestor || "");

  if (!items.length) {
    return "";
  }

  const cards = items
    .map(function (item) {
      const detailItem = normalizeDetailedListItem(item);

      if (!detailItem.title && !detailItem.description) {
        return "";
      }

      let html = `<div class="role-property-detail-card">`;

      if (detailItem.title) {
        html += `<div class="role-property-detail-card__title">${escapeHtml(detailItem.title)}</div>`;
      }

      if (detailItem.description) {
        html += `<div class="role-property-detail-card__body">${nl2brHtml(detailItem.description)}</div>`;
      }

      html += `</div>`;
      return html;
    })
    .filter(function (item) {
      return item !== "";
    });

  if (!cards.length) {
    return "";
  }

  return `<div class="role-property-detail-list">${cards.join("")}</div>`;
}

function renderStandardListPropertyHtml(entry) {
  const items = parseListItems(entry.effectiveValue || entry.value || entry.ancestor || "");

  if (!items.length) {
    return "";
  }

  const listItems = items
    .map(function (item) {
      return formatListItemValue(item, entry);
    })
    .filter(function (item) {
      return item !== "";
    })
    .map(function (item) {
      return `<li>${escapeHtml(item)}</li>`;
    });

  if (!listItems.length) {
    return "";
  }

  return `<ul class="role-property-list">${listItems.join("")}</ul>`;
}

function renderNodePropertyValueHtml(entry) {
  const effectiveValue = String(entry.effectiveValue || entry.value || entry.ancestor || "").trim();

  if (!effectiveValue) {
    return "";
  }

  if (Number(entry.formatId || 0) === 2) {
    if (String(entry.listItemType || "") === "detail") {
      return renderDetailListPropertyHtml(entry);
    }

    return renderStandardListPropertyHtml(entry);
  }

  if (Number(entry.formatId || 0) === 5) {
    return `<div class="role-property-html">${effectiveValue}</div>`;
  }

  if (Number(entry.formatId || 0) === 4) {
    return `<div class="role-property-text">${escapeHtml(formatDateValue(effectiveValue))}</div>`;
  }

  return `<div class="role-property-text">${nl2brHtml(effectiveValue)}</div>`;
}

function buildNodeDetailHtml(node) {
  const properties = getNodePropertyEntries(node);

  if (!properties.length) {
    return "";
  }

  const blocks = properties
    .map(function (entry) {
      const propertyHtml = renderNodePropertyValueHtml(entry);
      const propertyLabel = getPropertyDisplayLabel(entry);

      if (!propertyHtml || !propertyLabel) {
        return "";
      }

      return `
        <div class="role-property">
          <div class="role-property-label">${escapeHtml(propertyLabel)}</div>
          <div class="role-property-value">${propertyHtml}</div>
        </div>
      `;
    })
    .filter(function (block) {
      return block !== "";
    });

  if (!blocks.length) {
    return "";
  }

  return `<div class="role-properties">${blocks.join("")}</div>`;
}

function filterListNode(node, normalizedQuery) {
  const children = Array.isArray(node.children) ? node.children : [];
  const filteredChildren = children
    .map(function (child) {
      return filterListNode(child, normalizedQuery);
    })
    .filter(function (child) {
      return child !== null;
    });

  if (!normalizedQuery) {
    return {
      node: node,
      children: filteredChildren,
      matchesLabel: false,
      matchesContent: false,
      matchExcerpt: ""
    };
  }

  const matchesLabel = normalizeSearchText(node.name || "").includes(normalizedQuery);
  const matchesContent = normalizeSearchText(getNodeContentSearchText(node)).includes(normalizedQuery);

  if (!matchesLabel && !matchesContent && filteredChildren.length === 0) {
    return null;
  }

  return {
    node: node,
    children: filteredChildren,
    matchesLabel: matchesLabel,
    matchesContent: matchesContent,
    matchExcerpt: matchesContent ? getNodeMatchExcerpt(node, normalizedQuery) : ""
  };
}

function renderNodeList(entry, searchQuery) {
  const node = entry.node;
  const children = Array.isArray(entry.children) ? entry.children : [];
  const color = getListColor(node);
  const nodeId = String(node.ID || "");
  const escapedNodeId = escapeHtml(nodeId);
  const dotStyle = node.type == "3"
    ? "background:transparent;border:2px solid " + getGroupStrokeColor(node)
    : "background:" + color;
  const shellClasses = ["role-item-shell"];
  const detailHtml = buildNodeDetailHtml(node);
  const hasDetails = detailHtml !== "";
  const isDetailOpen = hasDetails && expandedRoleListNodeIds[nodeId] === true;
  const depthOpacity = getNodeDepthOpacity(node, 0.28, 1);

  if (entry.matchesLabel) {
    shellClasses.push("match-direct");
  } else if (entry.matchesContent) {
    shellClasses.push("match-content");
  }

  let detailToggleHtml = `<span class="role-detail-toggle role-detail-toggle--placeholder" aria-hidden="true"></span>`;
  if (hasDetails) {
    detailToggleHtml = `
      <button
        type="button"
        class="role-detail-toggle${isDetailOpen ? " is-open" : ""}"
        data-omo-role-detail-toggle="${escapedNodeId}"
        aria-expanded="${isDetailOpen ? "true" : "false"}"
        aria-controls="role_item_detail_${escapedNodeId}"
        aria-label="${isDetailOpen ? "Masquer les proprietes" : "Afficher les proprietes"}"
      ><span class="role-detail-toggle-icon">&#9654;</span></button>
    `;
  }

  let html = `
    <li class="node_${escapedNodeId}">
      <div class="role-row">
        <div class="${shellClasses.join(" ")}" style="--role-depth-opacity:${depthOpacity.toFixed(3)};">
          <div class="role-item-main">
            <button type="button" class="role-item" data-omo-cid="${escapedNodeId}" data-omo-root="${node.type == "4" ? "1" : "0"}">
              <span class="role-dot" style="${dotStyle}"></span>
              <span class="role-text">
                <span class="role-label">${highlightLabel(node.name || "", searchQuery)}</span>
                ${entry.matchExcerpt ? `<span class="role-excerpt">${entry.matchExcerpt}</span>` : ``}
              </span>
            </button>
            ${detailToggleHtml}
          </div>
          ${hasDetails ? `
      <div class="role-item-detail${isDetailOpen ? " is-open" : ""}" id="role_item_detail_${escapedNodeId}"${isDetailOpen ? "" : " hidden"}>
        ${detailHtml}
      </div>
          ` : ``}
        </div>
      </div>
  `;

  if (children.length) {
    html += `<ul>`;
    children.forEach(function (childEntry) {
      html += renderNodeList(childEntry, searchQuery);
    });
    html += `</ul>`;
  }

  html += `</li>`;
  return html;
}

const expandedRoleListNodeIds = Object.create(null);
let listFilterQuery = "";
let structurePrintPreviousListMode = null;

function ensureRoleListLayout() {
  const roleList = document.getElementById("role_list");

  if (!roleList) {
    return null;
  }

  if (!roleList.querySelector(".role-list-panel")) {
    roleList.innerHTML = `
      <div class="role-list-panel">
        <div class="role-list-toolbar">
          <input
            type="search"
            id="role_list_search"
            class="role-list-search"
            placeholder="Filtre rapide"
            autocomplete="off"
            spellcheck="false"
          >
        </div>
        <div class="role-list-results"></div>
      </div>
    `;
  }

  return roleList;
}

function updateRoleListResults() {
  const roleList = ensureRoleListLayout();

  if (!roleList || !root) {
    return;
  }

  const roleListInput = roleList.querySelector("#role_list_search");
  const roleListResults = roleList.querySelector(".role-list-results");
  const searchQuery = String(listFilterQuery || "");
  const normalizedQuery = normalizeSearchText(searchQuery);
  const filteredRoot = filterListNode(root, normalizedQuery);

  if (roleListInput && roleListInput.value !== searchQuery) {
    roleListInput.value = searchQuery;
  }

  if (!roleListResults) {
    return;
  }

  if (!filteredRoot) {
    roleListResults.innerHTML = `<div class="role-list-empty">Aucun noeud ne correspond a cette recherche.</div>`;
    return;
  }

  roleListResults.innerHTML = `<ul class="role-list">${renderNodeList(filteredRoot, searchQuery)}</ul>`;
}

function renderRoleList() {
  if (!root) return;
  ensureRoleListLayout();
  updateRoleListResults();
}

function refreshRoleListDepthOpacity() {
  if (!root) {
    return;
  }

  updateRoleListResults();
}

function prepareStructureForPrint() {
  renderRoleList();

  const contentRight = document.getElementById("contentright");
  if (contentRight) {
    structurePrintPreviousListMode = contentRight.classList.contains("list-mode");
    contentRight.classList.remove("list-mode");
  }

  const hiddenCanvasElement = document.getElementById("hiddenCanvas");
  if (hiddenCanvasElement) {
    hiddenCanvasElement.style.setProperty("display", "none", "important");
    hiddenCanvasElement.style.setProperty("visibility", "hidden", "important");
  }
}

function restoreStructureAfterPrint() {
  const contentRight = document.getElementById("contentright");
  if (!contentRight) {
    return;
  }

  if (structurePrintPreviousListMode === null) {
    const toggle = document.getElementById("toggleSwitch");
    contentRight.classList.toggle("list-mode", !!(toggle && toggle.checked));
    return;
  }

  contentRight.classList.toggle("list-mode", structurePrintPreviousListMode);
  structurePrintPreviousListMode = null;

  const hiddenCanvasElement = document.getElementById("hiddenCanvas");
  if (hiddenCanvasElement) {
    hiddenCanvasElement.style.setProperty("display", "none");
    hiddenCanvasElement.style.setProperty("visibility", "hidden");
    hiddenCanvasElement.style.setProperty("pointer-events", "none");
  }
}

$(document).on("change", "#toggleSwitch", function () {
  const isList = $(this).is(":checked");
  $("#contentright").toggleClass("list-mode", isList);

  if (!isList) {
    drawAll();
  }
});

$(document).on("input", "#role_list_search", function () {
  listFilterQuery = $(this).val() || "";
  updateRoleListResults();
});

$(document).on("click", "[data-omo-role-detail-toggle]", function (event) {
  event.preventDefault();
  event.stopPropagation();

  const nodeId = String($(this).data("omo-role-detail-toggle") || "").trim();

  if (!nodeId) {
    return;
  }

  if (expandedRoleListNodeIds[nodeId]) {
    delete expandedRoleListNodeIds[nodeId];
  } else {
    expandedRoleListNodeIds[nodeId] = true;
  }

  updateRoleListResults();
});

$(document).on("click", "#omoStructureActionsToggle", function (event) {
  event.preventDefault();
  event.stopPropagation();
  $("#omoStructureActions").toggleClass("is-open");
});

$(document).on("click", function (event) {
  if (!$(event.target).closest("#omoStructureActions").length) {
    omoCloseStructureActions();
  }
});

$(document).on("keydown", function (event) {
  if (event.key === "Escape") {
    omoCloseStructureActions();
  }
});

$(document).on("click", "#omoStructureCanvasWarningDismiss", function (event) {
  event.preventDefault();
  event.stopPropagation();
  collapseStructureCanvasWarning();
});

$(document).on("click", "#omoStructureCanvasWarningRestore", function (event) {
  event.preventDefault();
  event.stopPropagation();
  expandStructureCanvasWarning();
});

if (typeof window !== "undefined") {
  window.addEventListener("beforeprint", prepareStructureForPrint);
  window.addEventListener("afterprint", restoreStructureAfterPrint);
}

$(document).on("click", "[data-omo-structure-action]", function (event) {
  event.preventDefault();

  const action = String($(this).data("omo-structure-action") || "").trim();
  omoCloseStructureActions();

  if (action === "print") {
    prepareStructureForPrint();
    window.print();
    return;
  }

  if (action === "export") {
    if (!canExportStructure) {
      return;
    }

    const route = getCurrentRoute();
    const currentHolonId = omoGetCurrentStructureHolonId();
    let exportUrl = "api/exportStructure.php?oid=" + encodeURIComponent(route.oid || 0);

    if (currentHolonId > 0) {
      exportUrl += "&cid=" + encodeURIComponent(currentHolonId);
    }

    if (typeof window.omoResolveAppUrl === "function") {
      exportUrl = window.omoResolveAppUrl(exportUrl);
    }

    const link = document.createElement("a");
    link.href = exportUrl;
    link.download = "";
    link.rel = "noopener";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    return;
  }

  if (action !== "share" || !canCreateShareLink) {
    return;
  }

  if (typeof window.commonTopbarOpenModal !== "function") {
    return;
  }

  const route = getCurrentRoute();
  const currentHolonId = omoGetCurrentStructureHolonId();
  let popupUrl = "api/shares/popup.php?oid=" + encodeURIComponent(route.oid || 0);

  if (currentHolonId > 0) {
    popupUrl += "&cid=" + encodeURIComponent(currentHolonId);
  }

  if (typeof window.omoResolveAppUrl === "function") {
    popupUrl = window.omoResolveAppUrl(popupUrl);
  }

  window.commonTopbarOpenModal("Partager la structure", popupUrl, "fetch");
});

    const structureDataUrl = <?= json_encode($structureDataUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const initialCid = <?= (int)$initialCid ?>;
    const canCreateShareLink = <?= $canCreateShareLink ? 'true' : 'false' ?>;
    const canExportStructure = <?= $canExportStructure ? 'true' : 'false' ?>;
    let root = null;

    let canvas, hiddenCanvas, context, hiddenContext;
    let pack, nodes, nodeCount, focus, currentnode, hoverNode = null;
    let highlightedMemberUserId = null;
    let centerX, centerY, chartwidth, chartheight, diameter;
    let zoomInfo, colToCircle = {};
    let ease, interpolator = null, duration = 500, timeElapsed = 0, vOld;
    let showText = true, textAlpha = 1, fadeText = false, fadeTextDuration = 250;
    let stopTimer = false, nextCol = 1;
    let isDragging = false, wasDragging = false;
    let colorCircle;
    let structureReloadPromise = null;
    let structureCanvasPickingIssue = null;
    let structureCanvasWarningMessage = "";
    let structureCanvasWarningCollapsed = false;
    const structureBrowserInfo = {
      name: "ce navigateur",
      isBrave: false
    };

    detectStructureBrowser().then(function (browserInfo) {
      structureBrowserInfo.name = browserInfo && browserInfo.name ? browserInfo.name : structureBrowserInfo.name;
      structureBrowserInfo.isBrave = !!(browserInfo && browserInfo.isBrave);

      if (structureCanvasPickingIssue) {
        showStructureCanvasWarning(buildStructureCanvasWarningMessage(structureCanvasPickingIssue));
      }
    });

    function getCurrentRoute() {
      if (typeof parseUrl === "function") {
        return parseUrl();
      }

      return {
        oid: window.omoConfig && window.omoConfig.oid ? Number(window.omoConfig.oid) : null,
        cid: null,
        hash: window.location.hash ? window.location.hash.replace("#", "") : null
      };
    }

    function navigateToHolon(nodeOrId) {
      if (typeof navigate !== "function") {
        return;
      }

      const route = getCurrentRoute();
      const nodeId = nodeOrId && typeof nodeOrId === "object"
        ? String(nodeOrId.ID || "")
        : String(nodeOrId || "");
      const targetCid = root && String(root.ID) === nodeId ? null : (nodeId !== "" ? Number(nodeId) : null);

      navigate(route.oid, Number.isNaN(targetCid) ? null : targetCid, route.hash || null);
    }

    function omoCloseStructureActions() {
      const actions = document.getElementById("omoStructureActions");
      if (actions) {
        actions.classList.remove("is-open");
      }
    }

    function detectStructureBrowser() {
      if (typeof navigator === "undefined") {
        return Promise.resolve({
          name: "ce navigateur",
          isBrave: false
        });
      }

      const userAgent = String(navigator.userAgent || "");
      const browserInfo = {
        name: "ce navigateur",
        isBrave: false
      };

      if (/Firefox/i.test(userAgent)) {
        browserInfo.name = "Firefox";
      } else if (/Edg\//i.test(userAgent)) {
        browserInfo.name = "Microsoft Edge";
      } else if (/Chrome\//i.test(userAgent)) {
        browserInfo.name = "Chrome";
      } else if (/Safari\//i.test(userAgent) && !/Chrome\//i.test(userAgent)) {
        browserInfo.name = "Safari";
      }

      if (!navigator.brave || typeof navigator.brave.isBrave !== "function") {
        return Promise.resolve(browserInfo);
      }

      return navigator.brave.isBrave()
        .then(function (isBrave) {
          if (isBrave) {
            browserInfo.name = "Brave";
            browserInfo.isBrave = true;
          }

          return browserInfo;
        })
        .catch(function () {
          return browserInfo;
        });
    }

    function setStructureListMode(enabled, syncToggle) {
      const contentRight = document.getElementById("contentright");
      const toggle = document.getElementById("toggleSwitch");

      if (contentRight) {
        contentRight.classList.toggle("list-mode", !!enabled);
      }

      if (syncToggle && toggle) {
        toggle.checked = !!enabled;
      }
    }

    function renderStructureCanvasWarning() {
      const warningElement = document.getElementById("omoStructureCanvasWarning");
      const messageElement = document.getElementById("omoStructureCanvasWarningMessage");
      const dismissButton = document.getElementById("omoStructureCanvasWarningDismiss");
      const restoreButton = document.getElementById("omoStructureCanvasWarningRestore");

      if (!warningElement || !messageElement || !dismissButton || !restoreButton) {
        return;
      }

      const hasMessage = structureCanvasWarningMessage !== "";
      warningElement.hidden = !hasMessage;

      if (!hasMessage) {
        warningElement.classList.remove("is-collapsed");
        messageElement.textContent = "";
        dismissButton.hidden = true;
        restoreButton.hidden = true;
        return;
      }

      warningElement.classList.toggle("is-collapsed", structureCanvasWarningCollapsed);
      messageElement.textContent = structureCanvasWarningMessage;
      dismissButton.hidden = structureCanvasWarningCollapsed;
      restoreButton.hidden = !structureCanvasWarningCollapsed;
    }

    function showStructureCanvasWarning(message) {
      structureCanvasWarningMessage = String(message || "");
      renderStructureCanvasWarning();
    }

    function hideStructureCanvasWarning() {
      structureCanvasWarningMessage = "";
      structureCanvasWarningCollapsed = false;
      renderStructureCanvasWarning();
    }

    function collapseStructureCanvasWarning() {
      if (structureCanvasWarningMessage === "") {
        return;
      }

      structureCanvasWarningCollapsed = true;
      renderStructureCanvasWarning();
    }

    function expandStructureCanvasWarning() {
      if (structureCanvasWarningMessage === "") {
        return;
      }

      structureCanvasWarningCollapsed = false;
      renderStructureCanvasWarning();
    }

    function buildStructureCanvasWarningMessage(issue) {
      const browserName = structureBrowserInfo && structureBrowserInfo.name
        ? structureBrowserInfo.name
        : "ce navigateur";

      if (structureBrowserInfo && structureBrowserInfo.isBrave) {
        return "Brave semble bloquer la lecture du canvas utilisee pour la navigation graphique, probablement a cause du bouclier anti-empreinte numerique. La vue liste a ete activee pour continuer a naviguer. Vous pouvez aussi assouplir le bouclier pour ce site.";
      }

      if (issue && issue.reason === "pixel-mismatch") {
        return browserName + " bloque ou altere la lecture du canvas utilisee pour la navigation graphique. La vue liste a ete activee pour continuer a naviguer.";
      }

      return "La lecture du canvas utilisee pour la navigation graphique n'est pas disponible dans " + browserName + ". La vue liste a ete activee pour continuer a naviguer.";
    }

    function applyStructureCanvasPickingIssue(issue) {
      const chartCanvas = document.getElementById("canvas");

      if (!issue) {
        structureCanvasPickingIssue = null;
        hideStructureCanvasWarning();

        if (chartCanvas) {
          chartCanvas.style.pointerEvents = "auto";
        }

        return;
      }

      structureCanvasPickingIssue = issue;
      showStructureCanvasWarning(buildStructureCanvasWarningMessage(issue));

      if (chartCanvas) {
        chartCanvas.style.pointerEvents = "none";
      }

      setStructureListMode(true, true);
    }

    function probeStructureCanvasPicking() {
      if (!hiddenContext || typeof hiddenContext.getImageData !== "function") {
        return {
          ok: false,
          reason: "missing-context"
        };
      }

      try {
        hiddenContext.save();
        hiddenContext.clearRect(0, 0, 2, 2);
        hiddenContext.fillStyle = "rgba(17, 34, 51, 1)";
        hiddenContext.fillRect(0, 0, 1, 1);

        const pixel = hiddenContext.getImageData(0, 0, 1, 1).data;
        hiddenContext.clearRect(0, 0, 2, 2);
        hiddenContext.restore();

        if (!pixel || pixel.length < 4) {
          return {
            ok: false,
            reason: "empty-pixel"
          };
        }

        if (pixel[0] !== 17 || pixel[1] !== 34 || pixel[2] !== 51 || pixel[3] === 0) {
          return {
            ok: false,
            reason: "pixel-mismatch"
          };
        }

        return {
          ok: true
        };
      } catch (error) {
        try {
          hiddenContext.restore();
        } catch (restoreError) {
        }

        return {
          ok: false,
          reason: "exception",
          error: error
        };
      }
    }

    function omoGetCurrentStructureHolonId() {
      if (currentnode && currentnode.ID) {
        return Number(currentnode.ID);
      }

      if (initialCid > 0) {
        return Number(initialCid);
      }

      if (root && root.ID) {
        return Number(root.ID);
      }

      return 0;
    }

    function findPackedNodeById(nodeId) {
      if (!Array.isArray(nodes) || nodeId === null || nodeId === undefined || nodeId === "") {
        return null;
      }

      const normalizedId = String(nodeId);

      return nodes.find(function (item) {
        return String(item.ID) === normalizedId;
      }) || null;
    }

    // Recharge structure ciblée
    function reloadStructureAndFocus(nodeId, options) {
      if (structureReloadPromise) {
        return structureReloadPromise;
      }

      const settings = Object.assign({
        quickZoom: false
      }, options || {});

      structureReloadPromise = loadStructureData()
        .then(function() {
          if (nodeId) {
            currentnode = {
              ID: String(nodeId)
            };
          } else {
            currentnode = root;
          }

          drawAll();

          if (settings.quickZoom) {
            const quickTargetNode = nodeId ? findPackedNodeById(nodeId) : root;
            if (quickTargetNode) {
              quickZoomToCanvas(quickTargetNode);
            }
            return;
          }

          if (nodeId) {
            const reloadedNode = findPackedNodeById(nodeId);
            if (reloadedNode) {
              animateFocusToNode(reloadedNode);
              return;
            }
          }

          animateFocusToNode(root);
        })
        .catch(function(error) {
          console.error(error);
          renderStructureMessage(error && error.message ? error.message : "Impossible de charger la structure.");
        })
        .finally(function() {
          structureReloadPromise = null;
        });

      return structureReloadPromise;
    }

    window.omoReloadStructureAndFocus = function(nodeId, options) {
      return reloadStructureAndFocus(nodeId, options);
    };

    function focusStructureNode(nodeId, options) {
      if (!root || !Array.isArray(nodes)) {
        return;
      }

      const settings = Object.assign({
        allowReload: true,
        quickZoom: false
      }, options || {});
      const requestedNodeId = nodeId === null || nodeId === undefined || nodeId === ""
        ? null
        : nodeId;
      const targetNode = requestedNodeId ? findPackedNodeById(requestedNodeId) : root;

      if (!targetNode && requestedNodeId && settings.allowReload) {
        reloadStructureAndFocus(requestedNodeId, {
          quickZoom: settings.quickZoom
        });
        return;
      }

      if (settings.quickZoom) {
        quickZoomToCanvas(targetNode || root);
        return;
      }

      animateFocusToNode(targetNode || root);
    }

    function animateFocusToNode(targetNode, options) {
      if (!targetNode || !canvas) {
        return;
      }

      currentnode = targetNode;
      refreshRoleListDepthOpacity();
      canvas.style("pointer-events", "none");

      const v = shouldUseTightZoom(targetNode)
        ? [targetNode.x, targetNode.y, targetNode.r * 4.05]
        : [targetNode.x, targetNode.y, targetNode.r * 2.05];

      if (arraysAreEqual(vOld, v)) {
        drawCanvas(context, false);
        drawCanvas(hiddenContext, true);
        canvas.style("pointer-events", "auto");
        return;
      }

      focus = targetNode;
      interpolator = d3.interpolateZoom(vOld, v);
      duration = Math.max(500, interpolator.duration);
      timeElapsed = 0;
      showText = false;
      fadeText = false;
      textAlpha = 1;
      vOld = v;
      stopTimer = false;
      animate();
    }

    function normalizeStructureNode(node, depth) {
      if (!node || typeof node !== "object") {
        return null;
      }

      const normalizedNode = Object.assign({}, node);
      const children = Array.isArray(normalizedNode.children) ? normalizedNode.children : [];
      const normalizedDepth = Number.isFinite(Number(depth)) ? Number(depth) : 0;

      normalizedNode.ID = String(normalizedNode.ID || "");
      normalizedNode.type = String(normalizedNode.type || "");
      normalizedNode.size = Number(normalizedNode.size || (normalizedNode.type === "1" ? 10 : 20));
      normalizedNode.depth = Number.isFinite(Number(normalizedNode.depth)) ? Number(normalizedNode.depth) : normalizedDepth;
      normalizedNode.userIds = Array.isArray(normalizedNode.userIds)
        ? normalizedNode.userIds.map(function (userId) {
            return Number(userId);
          }).filter(function (userId) {
            return !Number.isNaN(userId) && userId > 0;
          })
        : [];
      normalizedNode.children = children
        .map(function (childNode) {
          return normalizeStructureNode(childNode, normalizedNode.depth + 1);
        })
        .filter(function (child) {
          return child !== null;
        });
      return normalizedNode;
    }

    function getPackTypeOrder(node) {
      switch (String(node && node.type ? node.type : "")) {
        case "4":
          return 0;
        case "1":
          return 1;
        case "3":
          return 2;
        case "2":
          return 3;
        default:
          return 99;
      }
    }

    function comparePackNodes(a, b) {
      const typeDifference = getPackTypeOrder(a) - getPackTypeOrder(b);
      if (typeDifference !== 0) {
        return typeDifference;
      }

      const nameDifference = String(a && a.name ? a.name : "").localeCompare(String(b && b.name ? b.name : ""));
      if (nameDifference !== 0) {
        return nameDifference;
      }

      return String(a && a.ID ? a.ID : "").localeCompare(String(b && b.ID ? b.ID : ""));
    }

    function renderStructureMessage(message) {
      const chart = document.getElementById("chart");
      const roleList = document.getElementById("role_list");

      hideStructureCanvasWarning();
      chart.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:center;height:100%;padding:24px;text-align:center;color:var(--color-text, #1f2937);">
          ${escapeHtml(message)}
        </div>
      `;
      roleList.innerHTML = "";
    }

    function getStructureErrorMessage(xhr) {
      if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
        return xhr.responseJSON.message;
      }

      if (xhr && xhr.responseText) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response && response.message) {
            return response.message;
          }
        } catch (error) {
        }
      }

      return "Impossible de charger la structure.";
    }

    function loadStructureData() {
      return new Promise(function (resolve, reject) {
        const resolvedStructureDataUrl = (typeof window.omoResolveAppUrl === "function")
          ? window.omoResolveAppUrl(structureDataUrl)
          : structureDataUrl;

        $.ajax({
          url: resolvedStructureDataUrl,
          method: "GET",
          cache: false,
          dataType: "json",
          success: function (response) {
            if (!response || response.error) {
              reject(new Error(response && response.message ? response.message : "Structure invalide."));
              return;
            }

            const normalizedRoot = normalizeStructureNode(response, 0);

            if (!normalizedRoot) {
              reject(new Error("Structure invalide."));
              return;
            }

            root = normalizedRoot;
            currentnode = null;
            resolve(root);
          },
          error: function (xhr) {
            reject(new Error(getStructureErrorMessage(xhr)));
          }
        });
      });
    }

    function removeColorNodes(json, size = 10) {
      if (Array.isArray(json)) {
        return json.map(item => removeColorNodes(item, size));
      }
      if (typeof json === "object" && json !== null) {
        for (const key in json) {
          if (key === "color") {
            delete json[key];
          } else if (key === "size") {
            json[key] = getNodePackSize(json, size);
          } else if (key === "children") {
            json[key] = removeColorNodes(json[key], json.type == 2 ? (size > 2 ? size - 2 : 2) : size);
          }
        }
      }
      return json;
    }

    function genColor() {
      const ret = [];
      if (nextCol < 16777215) {
        ret.push(nextCol & 0xff);
        ret.push((nextCol & 0xff00) >> 8);
        ret.push((nextCol & 0xff0000) >> 16);
        nextCol += 1;
      }
      return "rgb(" + ret.join(",") + ")";
    }

    function getLines(ctx, text, maxWidth, fontSize, fontFamily) {
      const words = text.split(" ");
      const lines = [];
      let currentLine = words[0] || "";
      let longest = "";

      for (let i = 1; i < words.length; i++) {
        const word = words[i];
        ctx.font = fontSize + "pt " + fontFamily;
        const width = ctx.measureText(currentLine + " " + word).width;
        if (width < maxWidth) {
          currentLine += " " + word;
        } else {
          lines.push(currentLine);
          if (currentLine.length > longest.length) longest = currentLine;
          currentLine = word;
        }
      }

      lines.push(currentLine);
      if (currentLine.length > longest.length) longest = currentLine;

      if (ctx.measureText(longest).width > maxWidth) {
        fontSize = fontSize / ctx.measureText(longest).width * maxWidth;
      }

      return { lines, fontSize };
    }

    function drawText(ctx, text, fontSize, centerX, centerY, radius, fillcolor = "#000", strokecolor = "#FFF", style = "", font = "Arial", opacity = 1) {
      if (fontSize < 6) return;
      if (fontSize < 12) fontSize = 12;

      ctx.textBaseline = "alphabetic";
      ctx.textAlign = "center";
      ctx.fillStyle = colorToTransparentFill(fillcolor, opacity, "rgba(0,0,0," + opacity + ")");
      ctx.strokeStyle = colorToTransparentFill(strokecolor, opacity, "rgba(255,255,255," + opacity + ")");
      ctx.lineWidth = 5;
      ctx.setLineDash([]);
      ctx.lineJoin = "round";
      ctx.font = style + " " + fontSize + "pt '" + font + "'";

      let titleText = getLines(ctx, text, radius * 2 * 0.7, fontSize, font);
      fontSize = titleText.fontSize;
      titleText = titleText.lines;

      if (fontSize < 6) return;
      if (fontSize < 12) fontSize = 12;

      ctx.font = style + " " + fontSize + "pt '" + font + "'";

      let cpt = 0;
      titleText.forEach(function(txt, iterator) {
        if (cpt < 4) {
          if (cpt === 3) txt = "...";
          const y = centerY + ((-Math.min(titleText.length, 4) / 2) + iterator + 0.5) * fontSize * 1.1;
          ctx.textBaseline = "middle";
          ctx.strokeText(txt, centerX, y);
          ctx.fillText(txt, centerX, y);
        }
        cpt += 1;
      });
    }

    function drawCircularText(ctx, text, fontSize, fontBold, titleFont, centerX, centerY, radius, startAngle, kerning, opacity) {
      ctx.textBaseline = "alphabetic";
      ctx.textAlign = "center";
      ctx.font = fontBold + " " + fontSize + "pt " + titleFont;
      ctx.fillStyle = colorToTransparentFill("#ffffff", opacity, "rgba(255,255,255," + opacity + ")");

      startAngle = startAngle * (Math.PI / 180);
      text = text.split("").reverse().join("");

      for (let j = 0; j < text.length; j++) {
        const charWid = ctx.measureText(text[j]).width;
        startAngle += ((charWid + (j === text.length - 1 ? 0 : kerning)) / radius) / 2;
      }

      ctx.save();
      ctx.translate(centerX, centerY);
      ctx.rotate(startAngle);

      for (let j = 0; j < text.length; j++) {
        const charWid = ctx.measureText(text[j]).width / 2;
        ctx.rotate(-charWid / radius);
        ctx.fillText(text[j], 0, -radius);
        ctx.rotate(-(charWid + kerning) / radius);
      }

      ctx.restore();
    }

    function drawPolygon(ctx, x, y, radius, sides) {
      if (sides < 3) return;
      ctx.beginPath();
      for (let i = 0; i <= sides; i++) {
        const angle = (2 * Math.PI / sides) * i;
        const px = x + radius * Math.cos(angle);
        const py = y + radius * Math.sin(angle);
        if (i === 0) ctx.moveTo(px, py);
        else ctx.lineTo(px, py);
      }
      ctx.closePath();
      ctx.stroke();
    }

    const hatchPatternCache = {};

    function getHatchPattern(ctx, nodeType) {
      const key = String(nodeType || "");
      if (hatchPatternCache[key]) {
        return hatchPatternCache[key];
      }

      const patternCanvas = document.createElement("canvas");
      patternCanvas.width = 24;
      patternCanvas.height = 24;

      const patternContext = patternCanvas.getContext("2d");
      patternContext.clearRect(0, 0, 24, 24);
      patternContext.strokeStyle = (nodeType == "3")
        ? "rgba(255,255,255,0.42)"
        : "rgba(255,255,255,0.34)";
      patternContext.lineWidth = 6;
      patternContext.beginPath();
      patternContext.moveTo(-6, 18);
      patternContext.lineTo(18, -6);
      patternContext.moveTo(6, 30);
      patternContext.lineTo(30, 6);
      patternContext.stroke();

      hatchPatternCache[key] = ctx.createPattern(patternCanvas, "repeat");
      return hatchPatternCache[key];
    }

    function shouldHatchNode(node) {
      return Boolean(node && node.isVisibleTemplateInstance) && (node.type == "1" || node.type == "2" || node.type == "3");
    }

    function drawNodeHatch(ctx, node, nodeX, nodeY, nodeR) {
      if (!shouldHatchNode(node)) {
        return;
      }

      const hatchPattern = getHatchPattern(ctx, node.type);
      if (!hatchPattern) {
        return;
      }

      ctx.save();
      ctx.beginPath();
      ctx.arc(nodeX, nodeY, nodeR, 0, 2 * Math.PI, true);
      ctx.globalAlpha = getNodeVisualOpacity(node);
      ctx.fillStyle = hatchPattern;
      ctx.fill();
      ctx.restore();
    }

    function nodeMatchesHighlightedMember(node) {
      if (!node || !highlightedMemberUserId) {
        return false;
      }

      const targetUserId = Number(highlightedMemberUserId);
      if (!targetUserId) {
        return false;
      }

      const userIds = Array.isArray(node.userIds) ? node.userIds : [];
      return userIds.some(function (userId) {
        return Number(userId) === targetUserId;
      });
    }

    function drawCanvas(chosenContext, hidden = false) {
      chosenContext.clearRect(0, 0, chartwidth, chartheight);
      chosenContext.fillStyle = chartColors.background;
      chosenContext.fillRect(0, 0, chartwidth, chartheight);

      for (let i = 0; i < nodeCount; i++) {
        const node = nodes[i];
        const nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX;
        const nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY;
        const nodeR = node.r * zoomInfo.scale * (node.type == "1" ? 0.9 : (node.type == "4" ? 1.05 : 1));
        const nodeOpacity = getNodeVisualOpacity(node);

        if (node.mod === "hierarchy") {
          drawPolygon(chosenContext, nodeX, nodeY, nodeR, 8);
        } else {
          chosenContext.beginPath();
          chosenContext.arc(nodeX, nodeY, nodeR, 0, 2 * Math.PI, true);
        }

        if (hidden) {
          if (!node.color) {
            node.color = genColor();
          }
          colToCircle[node.color] = node;
          chosenContext.fillStyle = node.color;
          chosenContext.fill();
        } else {
          chosenContext.fillStyle = (node.type == "3" || node.type == "2")
            ? colorToTransparentFill(node.mycolor, 0.06 + (0.16 * nodeOpacity), colorCircle(node.depth))
            : colorToTransparentFill(node.mycolor, nodeOpacity, node.type == "4" ? chartColors.rootFill : chartColors.roleFill);

          if (node.type == "3") {
            chosenContext.fillStyle = "rgba(0,0,0,0)";
            chosenContext.lineWidth = 2;
            chosenContext.setLineDash([10, 10]);
            chosenContext.strokeStyle = colorToTransparentFill(node.mycolor, 0.2 + (0.45 * nodeOpacity), chartColors.strokeSoft);
            chosenContext.stroke();
            chosenContext.fill();
          } else if (node.type == "4") {
            chosenContext.lineWidth = 1;
            chosenContext.setLineDash([]);
            chosenContext.strokeStyle = colorToTransparentFill("#ffffff", 0.15 + (0.35 * nodeOpacity), "rgba(255,255,255,0.5)");
            chosenContext.stroke();
            chosenContext.fillStyle = colorToTransparentFill(node.mycolor, nodeOpacity, chartColors.rootFill);
            chosenContext.fill();
          } else {
            chosenContext.setLineDash([]);
            chosenContext.fill();
          }

          drawNodeHatch(chosenContext, node, nodeX, nodeY, nodeR);

          if (currentnode && node.ID === currentnode.ID) {
            chosenContext.lineWidth = 6;
            chosenContext.strokeStyle = "rgba(255,255,255,1)";
            chosenContext.stroke();
          } else if (nodeMatchesHighlightedMember(node)) {
            chosenContext.lineWidth = 6;
            chosenContext.strokeStyle = "rgba(255,255,255,1)";
            chosenContext.stroke();
          } else if (node.ID === hoverNode) {
            chosenContext.lineWidth = 3;
            chosenContext.strokeStyle = "rgba(255,255,255,1)";
            chosenContext.stroke();
          }
        }
      }

      for (let i = nodeCount - 1; i >= 0; i--) {
        const node = nodes[i];
        const nodeX = ((node.x - zoomInfo.centerX) * zoomInfo.scale) + centerX;
        const nodeY = ((node.y - zoomInfo.centerY) * zoomInfo.scale) + centerY;
        const nodeR = node.r * zoomInfo.scale * (node.type == "1" ? 0.9 : (node.type == "4" ? 1.05 : 1));

        if (
          !hidden &&
          showText &&
          currentnode &&
          (
            node.ID === currentnode.ID ||
            node.parent === currentnode ||
            (node.parent && node.parent.parent === currentnode) ||
            (
              currentnode.parent &&
              (currentnode.type != "2" || currentnode.parent.children.length > 1) &&
              (node.ID === currentnode.parent.ID || (node.parent && node.parent.ID === currentnode.parent.ID))
            )
          )
        ) {
          const thename = node.name;
          const titleFont = "Arial";
          const nodeTextOpacity = getNodeTextOpacity(node);

          if ((node.type != "1" && node === currentnode) || currentnode.parent === node) {
            const fontSizeTitle = Math.round(nodeR / 6);
            if (fontSizeTitle > 4) {
              drawCircularText(chosenContext, thename.replace(/,? and /g, " & "), fontSizeTitle, "bold", titleFont, nodeX, nodeY, nodeR, 0, 0, nodeTextOpacity);
            }
          } else {
            let fontSizeTitle = Math.round(nodeR / 3);

            if (node.type == "1") {
              if (fontSizeTitle > 36) fontSizeTitle = 36;
              drawText(chosenContext, thename.replace(/,? and /g, " & "), fontSizeTitle, nodeX, nodeY, nodeR, chartColors.labelDark, chartColors.strokeStrong, "", "Arial", nodeTextOpacity);
            } else {
              drawText(chosenContext, thename.replace(/,? and /g, " & "), fontSizeTitle, nodeX, nodeY, nodeR, chartColors.labelLight, chartColors.labelDark, "bold", "Arial", nodeTextOpacity);
            }
          }
        }
      }
    }

    function shouldUseTightZoom(focusNode) {
      return !!focusNode && focusNode.type == "1";
    }

    function quickZoomToCanvas(focusNode) {
      currentnode = focusNode;
      refreshRoleListDepthOpacity();
      focus = focusNode;
      const v = shouldUseTightZoom(focusNode)
        ? [focus.x, focus.y, focus.r * 4.05]
        : [focus.x, focus.y, focus.r * 2.05];

      zoomInfo.centerX = v[0];
      zoomInfo.centerY = v[1];
      zoomInfo.scale = diameter / v[2];

      drawCanvas(context, false);
      drawCanvas(hiddenContext, true);
      vOld = v;
    }

    function arraysAreEqual(arr1, arr2) {
      if (!arr1 || !arr2 || arr1.length !== arr2.length) return false;
      for (let i = 0; i < arr1.length; i++) {
        if (arr1[i] !== arr2[i]) return false;
      }
      return true;
    }

    function zoomToCanvas(focusNode) {
      currentnode = focusNode;
      refreshRoleListDepthOpacity();
      canvas.style("pointer-events", "none");

      let v;
      if (focus === focusNode) {
        focus = root;
        v = [root.x, root.y, root.r * 2.05];
      } else {
        focus = focusNode;
        v = shouldUseTightZoom(focusNode)
          ? [focus.x, focus.y, focus.r * 4.05]
          : [focus.x, focus.y, focus.r * 2.05];
      }

      if (arraysAreEqual(vOld, v)) {
        drawCanvas(context, false);
        drawCanvas(hiddenContext, true);
        canvas.style("pointer-events", "auto");
        return;
      }

      interpolator = d3.interpolateZoom(vOld, v);
      duration = Math.max(500, interpolator.duration);
      timeElapsed = 0;
      showText = false;
      vOld = v;
      stopTimer = false;
      animate();
    }

    function interpolateZoom(dt) {
      if (!interpolator) return;

      timeElapsed += dt;
      const t = ease(timeElapsed / duration);

      zoomInfo.centerX = interpolator(t)[0];
      zoomInfo.centerY = interpolator(t)[1];
      zoomInfo.scale = diameter / interpolator(t)[2];

      if (timeElapsed >= duration) {
        interpolator = null;
        showText = true;
        fadeText = true;
        timeElapsed = 0;
        drawCanvas(hiddenContext, true);
      }
    }

    function interpolateFadeText(dt) {
      if (!fadeText) return;

      timeElapsed += dt;
      textAlpha = ease(timeElapsed / fadeTextDuration);

      if (timeElapsed >= fadeTextDuration) {
        canvas.style("pointer-events", "auto");
        fadeText = false;
        stopTimer = true;
      }
    }

    function animate() {
      let dt = 0;
      d3.timer(function(elapsed) {
        interpolateZoom(elapsed - dt);
        interpolateFadeText(elapsed - dt);
        dt = elapsed;
        drawCanvas(context, false);
        return stopTimer;
      });
    }

function getNodeFromEvent(event) {
  if (structureCanvasPickingIssue) {
    return null;
  }

  const rect = canvas.node().getBoundingClientRect();

  const scaleX = chartwidth / rect.width;
  const scaleY = chartheight / rect.height;

  let mouseX = Math.floor((event.clientX - rect.left) * scaleX);
  let mouseY = Math.floor((event.clientY - rect.top) * scaleY);

  mouseX = Math.max(0, Math.min(chartwidth - 1, mouseX));
  mouseY = Math.max(0, Math.min(chartheight - 1, mouseY));

  let col;

  try {
    col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
  } catch (error) {
    applyStructureCanvasPickingIssue({
      reason: "exception",
      error: error
    });
    return null;
  }

  const colString = "rgb(" + col[0] + "," + col[1] + "," + col[2] + ")";
  return colToCircle[colString] || null;
}


function buildCanvas() {
  d3.select("#chart").html("");

  const chartEl = document.getElementById("chart");
  const rect = chartEl.getBoundingClientRect();
  const dpr = window.devicePixelRatio || 1;

  chartwidth = Math.max(1, Math.floor(rect.width * dpr));
  chartheight = Math.max(1, Math.floor(rect.height * dpr));

  centerX = chartwidth / 2;
  centerY = chartheight / 2;
  diameter = Math.min(chartwidth * 0.9, chartheight * 0.9);

  canvas = d3.select("#chart").append("canvas")
    .attr("id", "canvas")
    .attr("width", chartwidth)
    .attr("height", chartheight)
    .style("width", rect.width + "px")
    .style("height", rect.height + "px")
    .style("position", "absolute")
    .style("inset", "0");

  context = canvas.node().getContext("2d", { willReadFrequently: true });

  hiddenCanvas = d3.select("#chart").append("canvas")
    .attr("id", "hiddenCanvas")
    .attr("width", chartwidth)
    .attr("height", chartheight)
    .style("width", rect.width + "px")
    .style("height", rect.height + "px")
    .style("position", "absolute")
    .style("inset", "0")
    .style("visibility", "hidden")
    .style("pointer-events", "none")
    .style("display", "none");

  hiddenContext = hiddenCanvas.node().getContext("2d", { willReadFrequently: true });
}

function showCanvasTooltip(node, event) {
  if (!node) return;

  tooltipNodeId = node.ID;

  tooltip
    .text(node.name)
    .css({
      top: event.clientY + 12 + "px",
      left: event.clientX + 12 + "px"
    })
    .addClass("visible");
}

function moveCanvasTooltip(event) {
  if (!tooltipNodeId) return;

  tooltip.css({
    top: event.clientY + 12 + "px",
    left: event.clientX + 12 + "px"
  });
}

function hideCanvasTooltip() {
  tooltip.removeClass("visible");
  tooltipNodeId = null;
}


   function bindEvents() {
  let lastX = 0;
  let lastY = 0;

  canvas.on("mousedown", function () {
    const event = d3.event;
    isDragging = true;
    wasDragging = false;
    lastX = event.pageX;
    lastY = event.pageY;
  });

 canvas.on("mousemove", function () {
  const event = d3.event;

  if (isDragging) {
    const deltaX = Math.abs(event.pageX - lastX);
    const deltaY = Math.abs(event.pageY - lastY);

    if (deltaX > 2 || deltaY > 2) {
      wasDragging = true;
      closeTooltip();
    }

    const dx = (-event.pageX + lastX) * 2 / (diameter / vOld[2]);
    const dy = (-event.pageY + lastY) * 2 / (diameter / vOld[2]);
    const v = [vOld[0] + dx, vOld[1] + dy, vOld[2]];

    lastX = event.pageX;
    lastY = event.pageY;

    zoomInfo.centerX = v[0];
    zoomInfo.centerY = v[1];
    zoomInfo.scale = diameter / v[2];
    vOld = v;

    drawCanvas(context, false);
    return;
  }

  const node = getNodeFromEvent(event);
  hoverNode = node ? node.ID : null;
  drawCanvas(context, false);

  if (!node) {
    closeTooltip();
    return;
  }

  if (tooltipTarget !== node.ID) {
    openTooltip(node.name, event, node.ID);
  } else {
    moveTooltip(event);
  }
});

canvas.on("mousedown", function () {
  closeTooltip();
  const event = d3.event;
  isDragging = true;
  wasDragging = false;
  lastX = event.pageX;
  lastY = event.pageY;
});

canvas.on("mouseout", function () {
  isDragging = false;
  hoverNode = null;
  closeTooltip();
  drawCanvas(context, false);
  drawCanvas(hiddenContext, true);
});

canvas.on("click", function () {
  closeTooltip();

  const node = getNodeFromEvent(d3.event);

  if (wasDragging) {
    wasDragging = false;
    return;
  }

  navigateToHolon(node || root);
});



  d3.select(window).on("mouseup.chart", function () {
    if (isDragging) {
      isDragging = false;
      drawCanvas(context, false);
      drawCanvas(hiddenContext, true);
    }
    wasDragging = false;
  });
}

function getCssVar(name, fallback = "") {
  const value = getComputedStyle(document.documentElement)
    .getPropertyValue(name)
    .trim();
  return value || fallback;
}

function getChartColors() {
  return {
    background: getCssVar("--chart-bg", "#f0f2f5"),
    rootFill: getCssVar("--chart-root-fill", "#4f46e5"),
    groupFill: getCssVar("--chart-group-fill", "rgba(79, 70, 229, 0.12)"),
    roleFill: getCssVar("--chart-role-fill", "#fbbf24"),
    roleFillAlt: getCssVar("--chart-role-fill-alt", "#fb923c"),
    labelDark: getCssVar("--chart-label-dark", "#1f2937"),
    labelLight: getCssVar("--chart-label-light", "#ffffff"),
    strokeStrong: getCssVar("--chart-stroke-strong", "#ffffff"),
    strokeSoft: getCssVar("--chart-stroke-soft", "rgba(255,255,255,0.5)")
  };
}


    let chartColors = getChartColors();

    function drawAll() {
      if (!root) {
        renderStructureMessage("Aucune structure disponible pour cette organisation.");
        return;
      }

      chartColors = getChartColors();
      removeColorNodes(root);

      buildCanvas();
      applyStructureCanvasPickingIssue(null);

      const canvasPickingProbe = probeStructureCanvasPicking();
      if (!canvasPickingProbe.ok) {
        applyStructureCanvasPickingIssue(canvasPickingProbe);
      }

 
        colorCircle = d3.scale.ordinal()
        .domain([0, 1, 2, 3, 4, 5, 6])
        .range([
            chartColors.rootFill,
            chartColors.groupFill,
            chartColors.groupFill,
            chartColors.groupFill,
            chartColors.groupFill,
            chartColors.groupFill
        ]);

      pack = d3.layout.pack()
        .padding(1)
        .size([diameter, diameter])
        .value(function(d) { return d.size; })
        .sort(comparePackNodes);

      nodes = pack.nodes(root);
      nodeCount = nodes.length;
      focus = root;
      colToCircle = {};
      nextCol = 1;

      if (currentnode && currentnode.ID) {
        currentnode = findPackedNodeById(currentnode.ID) || root;
      } else if (initialCid > 0) {
        currentnode = findPackedNodeById(initialCid) || root;
      } else {
        currentnode = root;
      }

      if (currentnode.x && currentnode.y && currentnode.r) {
        zoomInfo = {
          centerX: currentnode.x,
          centerY: currentnode.y,
          scale: diameter / currentnode.r * 2.05
        };
      } else {
        zoomInfo = {
          centerX: centerX,
          centerY: centerY,
          scale: 1
        };
      }

      ease = d3.ease("cubic-in-out");
      vOld = [focus.x, focus.y, focus.r * 2.05];

      renderRoleList();
      bindEvents();
      quickZoomToCanvas(currentnode);
    }


 let chartResizeObserver = null;
let resizeRaf = null;

function scheduleDrawAll() {
  if (resizeRaf) return;

  resizeRaf = requestAnimationFrame(function() {
    resizeRaf = null;
    drawAll();
  });
}

function startChart() {
  const chartEl = document.getElementById("chart");

  if (chartResizeObserver) {
    chartResizeObserver.disconnect();
  }

  chartResizeObserver = new ResizeObserver(function() {
    scheduleDrawAll();
  });

  chartResizeObserver.observe(chartEl);

  loadStructureData()
    .then(function() {
      drawAll();

      if (window.omoStructureFocusHandler) {
        window.removeEventListener("omo-structure-focus", window.omoStructureFocusHandler);
      }

      if (window.omoStructureRefreshHandler) {
        window.removeEventListener("omo-structure-refresh", window.omoStructureRefreshHandler);
      }

      window.omoStructureFocusHandler = function (event) {
        const cid = event && event.detail ? event.detail.cid : null;
        const quickZoom = Boolean(event && event.detail && event.detail.quickZoom);
        focusStructureNode(cid, {
          quickZoom: quickZoom
        });
      };

      window.omoStructureRefreshHandler = function (event) {
        const cid = event && event.detail ? event.detail.cid : null;
        reloadStructureAndFocus(cid, {
          quickZoom: true
        });
      };

      if (window.omoStructureMemberHighlightHandler) {
        window.removeEventListener("omo-structure-member-highlight", window.omoStructureMemberHighlightHandler);
      }

      window.omoStructureMemberHighlightHandler = function (event) {
        const userId = event && event.detail ? Number(event.detail.userId || 0) : 0;
        highlightedMemberUserId = userId > 0 ? userId : null;

        if (!root || !canvas || !context || !hiddenContext) {
          return;
        }

        drawCanvas(context, false);
        drawCanvas(hiddenContext, true);
      };

      window.addEventListener("omo-structure-focus", window.omoStructureFocusHandler);
      window.addEventListener("omo-structure-refresh", window.omoStructureRefreshHandler);
      window.addEventListener("omo-structure-member-highlight", window.omoStructureMemberHighlightHandler);
    })
    .catch(function(error) {
      root = null;
      currentnode = null;
      console.error(error);
      renderStructureMessage(error && error.message ? error.message : "Impossible de charger la structure.");
    });
}

window.addEventListener("omo-theme-change", function () {
  scheduleDrawAll();
});


  function waitForD3(callback, attempts = 100) {
    if (window.d3) {
      callback();
      return;
    }

    if (attempts <= 0) {
      console.error("D3 n'a pas pu etre charge.");
      return;
    }

    setTimeout(function () {
      waitForD3(callback, attempts - 1);
    }, 50);
  }

  waitForD3(startChart);


  </script>
