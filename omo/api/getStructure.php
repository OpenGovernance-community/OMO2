<?php
require_once __DIR__ . '/bootstrap.php';

$organizationId = (int)($_SESSION['currentOrganization'] ?? ($_GET['oid'] ?? 0));
if ($organizationId > 0) {
    $organization = new \dbObject\Organization();
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

.role-item {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  width: 100%;
  padding: 8px 10px;
  border-radius: var(--radius-sm, 6px);
  background: var(--color-surface-alt, #f0f2f5);
  cursor: pointer;
  border: 0;
  text-align: left;
  color: inherit;
}

.role-item.match-direct {
  background: #fff7d6;
  box-shadow: inset 0 0 0 1px rgba(217, 119, 6, 0.22);
}

.role-item.match-content {
  background: #eef6ff;
  box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.18);
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
</style>
    <div id="contentright" class="contentright">
        <div id="chart"></div>
        <div id="role_list" class="filter_zone"></div>

        <div class="switch chart-toggle">
            <input type="checkbox" id="toggleSwitch" />
            <label for="toggleSwitch" class="slider">◉   ☰</label>
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

function getListColor(node) {
  if (node.type == "4") return chartColors.rootFill;
  if (node.type == "2" || node.type == "3") return chartColors.groupFill1;
  return node.mycolor || chartColors.roleFill;
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
      const effectiveValue = item.value || item.ancestor || "";
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
  const itemClasses = ["role-item"];

  if (entry.matchesLabel) {
    itemClasses.push("match-direct");
  } else if (entry.matchesContent) {
    itemClasses.push("match-content");
  }

  let html = `
    <li class="node_${escapeHtml(node.ID)}">
      <button type="button" class="${itemClasses.join(" ")}" data-omo-cid="${escapeHtml(node.ID)}" data-omo-root="${node.type == "4" ? "1" : "0"}">
        <span class="role-dot" style="background:${color}"></span>
        <span class="role-text">
          <span class="role-label">${highlightLabel(node.name || "", searchQuery)}</span>
          ${entry.matchExcerpt ? `<span class="role-excerpt">${entry.matchExcerpt}</span>` : ``}
        </span>
      </button>
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

let listFilterQuery = "";

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

    const structureDataUrl = <?= json_encode($structureDataUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const initialCid = <?= (int)$initialCid ?>;
    let root = null;

    let canvas, hiddenCanvas, context, hiddenContext;
    let pack, nodes, nodeCount, focus, currentnode, hoverNode = null;
    let centerX, centerY, chartwidth, chartheight, diameter;
    let zoomInfo, colToCircle = {};
    let ease, interpolator = null, duration = 500, timeElapsed = 0, vOld;
    let showText = true, textAlpha = 1, fadeText = false, fadeTextDuration = 250;
    let stopTimer = false, nextCol = 1;
    let isDragging = false, wasDragging = false;
    let colorCircle;
    let structureReloadPromise = null;

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
      canvas.style("pointer-events", "none");

      const v = (targetNode.type == "1" || (targetNode.children && targetNode.children.length < 2))
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

    function normalizeStructureNode(node) {
      if (!node || typeof node !== "object") {
        return null;
      }

      const normalizedNode = Object.assign({}, node);
      const children = Array.isArray(normalizedNode.children) ? normalizedNode.children : [];

      normalizedNode.ID = String(normalizedNode.ID || "");
      normalizedNode.type = String(normalizedNode.type || "");
      normalizedNode.size = Number(normalizedNode.size || (normalizedNode.type === "1" ? 10 : 20));
      normalizedNode.children = children
        .map(normalizeStructureNode)
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
        $.ajax({
          url: structureDataUrl,
          method: "GET",
          cache: false,
          dataType: "json",
          success: function (response) {
            if (!response || response.error) {
              reject(new Error(response && response.message ? response.message : "Structure invalide."));
              return;
            }

            const normalizedRoot = normalizeStructureNode(response);

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
            json[key] = size;
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

    function drawText(ctx, text, fontSize, centerX, centerY, radius, fillcolor = "#000", strokecolor = "#FFF", style = "", font = "Arial") {
      if (fontSize < 6) return;
      if (fontSize < 12) fontSize = 12;

      ctx.textBaseline = "alphabetic";
      ctx.textAlign = "center";
      ctx.fillStyle = fillcolor;
      ctx.strokeStyle = strokecolor;
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

    function drawCircularText(ctx, text, fontSize, fontBold, titleFont, centerX, centerY, radius, startAngle, kerning) {
      ctx.textBaseline = "alphabetic";
      ctx.textAlign = "center";
      ctx.font = fontBold + " " + fontSize + "pt " + titleFont;
      ctx.fillStyle = "rgba(255,255,255," + textAlpha + ")";

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
      ctx.fillStyle = hatchPattern;
      ctx.fill();
      ctx.restore();
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
            ? colorCircle(node.depth)
            : (node.mycolor || "rgb(255, 204, 0)");

          if (node.type == "3") {
            chosenContext.fillStyle = "rgba(0,0,0,0)";
            chosenContext.lineWidth = 2;
            chosenContext.setLineDash([10, 10]);
            chosenContext.strokeStyle = "rgba(255,255,255,0.5)";
            chosenContext.stroke();
            chosenContext.fill();
          } else if (node.type == "4") {
            chosenContext.lineWidth = 1;
            chosenContext.setLineDash([]);
            chosenContext.strokeStyle = "rgba(255,255,255,0.5)";
            chosenContext.stroke();
            chosenContext.fillStyle = chartColors.rootFill;
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

          if ((node.type != "1" && node === currentnode) || currentnode.parent === node) {
            const fontSizeTitle = Math.round(nodeR / 6);
            if (fontSizeTitle > 4) {
              drawCircularText(chosenContext, thename.replace(/,? and /g, " & "), fontSizeTitle, "bold", titleFont, nodeX, nodeY, nodeR, 0, 0);
            }
          } else {
            let fontSizeTitle = Math.round(nodeR / 3);

            if (node.type == "1") {
              if (fontSizeTitle > 36) fontSizeTitle = 36;
              drawText(chosenContext, thename.replace(/,? and /g, " & "), fontSizeTitle, nodeX, nodeY, nodeR, chartColors.labelDark, chartColors.strokeStrong);
            } else {
              drawText(chosenContext, thename.replace(/,? and /g, " & "), fontSizeTitle, nodeX, nodeY, nodeR, chartColors.labelLight, chartColors.labelDark, "bold");
            }
          }
        }
      }
    }

    function quickZoomToCanvas(focusNode) {
      focus = focusNode;
      const v = (focusNode.type == "1" || (focusNode.children && focusNode.children.length < 2))
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
      canvas.style("pointer-events", "none");

      let v;
      if (focus === focusNode) {
        focus = root;
        v = [root.x, root.y, root.r * 2.05];
      } else {
        focus = focusNode;
        v = (focusNode.type == "1" || (focusNode.children && focusNode.children.length < 2))
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
  const rect = canvas.node().getBoundingClientRect();

  const scaleX = chartwidth / rect.width;
  const scaleY = chartheight / rect.height;

  let mouseX = Math.floor((event.clientX - rect.left) * scaleX);
  let mouseY = Math.floor((event.clientY - rect.top) * scaleY);

  mouseX = Math.max(0, Math.min(chartwidth - 1, mouseX));
  mouseY = Math.max(0, Math.min(chartheight - 1, mouseY));

  const col = hiddenContext.getImageData(mouseX, mouseY, 1, 1).data;
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

      renderRoleList();
      buildCanvas();

 
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

      window.addEventListener("omo-structure-focus", window.omoStructureFocusHandler);
      window.addEventListener("omo-structure-refresh", window.omoStructureRefreshHandler);
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
