(function () {
  const D3_V3_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js';
  const STRUCTURE_DATA_PATH = 'api/getStructureData.php';
  const MOBILE_MEDIA_QUERY = '(max-width: 768px)';
  let d3Promise = null;

  function ensureD3() {
    if (window.d3 && window.d3.layout && typeof window.d3.layout.pack === 'function') {
      return Promise.resolve(window.d3);
    }

    if (d3Promise) {
      return d3Promise;
    }

    d3Promise = new Promise(function (resolve, reject) {
      const existingScript = document.querySelector('script[data-omo-d3-v3="1"]');

      if (existingScript) {
        existingScript.addEventListener('load', function () {
          resolve(window.d3);
        }, { once: true });
        existingScript.addEventListener('error', function () {
          reject(new Error('d3_load_failed'));
        }, { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = D3_V3_SRC;
      script.async = true;
      script.setAttribute('data-omo-d3-v3', '1');
      script.onload = function () {
        resolve(window.d3);
      };
      script.onerror = function () {
        reject(new Error('d3_load_failed'));
      };
      document.head.appendChild(script);
    });

    return d3Promise;
  }

  function resolveRoute() {
    if (typeof parseUrl === 'function') {
      return parseUrl();
    }

    return {
      oid: window.omoConfig && window.omoConfig.oid ? Number(window.omoConfig.oid) : null,
      cid: null,
      hash: window.location.hash ? window.location.hash.replace(/^#/, '') : null
    };
  }

  function buildStructureDataUrl(oid) {
    let url = STRUCTURE_DATA_PATH;

    if (Number.isInteger(Number(oid)) && Number(oid) > 0) {
      url += '?oid=' + encodeURIComponent(String(oid));
    }

    return typeof window.omoResolveAppUrl === 'function'
      ? window.omoResolveAppUrl(url)
      : url;
  }

  function clampNumber(value, min, max) {
    return Math.min(Math.max(value, min), max);
  }

  function addMediaQueryChangeListener(mediaQueryList, handler) {
    if (!mediaQueryList || typeof handler !== 'function') {
      return function () {};
    }

    if (typeof mediaQueryList.addEventListener === 'function') {
      mediaQueryList.addEventListener('change', handler);
      return function () {
        mediaQueryList.removeEventListener('change', handler);
      };
    }

    if (typeof mediaQueryList.addListener === 'function') {
      mediaQueryList.addListener(handler);
      return function () {
        mediaQueryList.removeListener(handler);
      };
    }

    return function () {};
  }

  function parseColorChannels(color) {
    const raw = String(color || '').trim();
    if (!raw) {
      return null;
    }

    const hexMatch = raw.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (hexMatch) {
      let hex = hexMatch[1];
      if (hex.length === 3) {
        hex = hex.split('').map(function (part) {
          return part + part;
        }).join('');
      }

      return {
        red: parseInt(hex.slice(0, 2), 16),
        green: parseInt(hex.slice(2, 4), 16),
        blue: parseInt(hex.slice(4, 6), 16),
        alpha: 1
      };
    }

    const rgbMatch = raw.match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+)\s*)?\)$/i);
    if (rgbMatch) {
      return {
        red: clampNumber(Number(rgbMatch[1]), 0, 255),
        green: clampNumber(Number(rgbMatch[2]), 0, 255),
        blue: clampNumber(Number(rgbMatch[3]), 0, 255),
        alpha: rgbMatch[4] !== undefined ? clampNumber(Number(rgbMatch[4]), 0, 1) : 1
      };
    }

    return null;
  }

  function colorToTransparentFill(color, alpha, fallback) {
    const channels = parseColorChannels(color);
    if (!channels) {
      return String(fallback || color || 'rgba(15, 23, 42, 0.18)');
    }

    const finalAlpha = clampNumber(Number(alpha), 0, 1) * clampNumber(Number(channels.alpha), 0, 1);
    return 'rgba(' + Math.round(channels.red) + ', ' + Math.round(channels.green) + ', ' + Math.round(channels.blue) + ', ' + finalAlpha + ')';
  }

  function colorToDesaturatedGray(color, fallback) {
    const channels = parseColorChannels(color || fallback);
    if (!channels) {
      return String(fallback || color || '#94a3b8');
    }

    const gray = Math.round(
      (channels.red * 0.299) +
      (channels.green * 0.587) +
      (channels.blue * 0.114)
    );

    return 'rgba(' + gray + ', ' + gray + ', ' + gray + ', ' + clampNumber(Number(channels.alpha), 0, 1) + ')';
  }

  function getCssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement)
      .getPropertyValue(name)
      .trim();
    return value || String(fallback || '');
  }

  function getChartColors() {
    return {
      background: getCssVar('--chart-bg', '#f0f2f5'),
      rootFill: getCssVar('--chart-root-fill', '#4f46e5'),
      groupFill: getCssVar('--chart-group-fill', 'rgba(79, 70, 229, 0.12)'),
      roleFill: getCssVar('--chart-role-fill', '#fbbf24'),
      roleFillAlt: getCssVar('--chart-role-fill-alt', '#fb923c'),
      labelDark: getCssVar('--chart-label-dark', '#1f2937'),
      labelLight: getCssVar('--chart-label-light', '#ffffff'),
      strokeStrong: getCssVar('--chart-stroke-strong', '#ffffff'),
      strokeSoft: getCssVar('--chart-stroke-soft', 'rgba(255,255,255,0.5)')
    };
  }

  function roleHasAttachedUsers(node) {
    if (String(node && node.type || '') !== '1') {
      return true;
    }

    return Array.isArray(node.userIds) && node.userIds.length > 0;
  }

  function getNodeDisplayColor(node, fallbackColor) {
    const baseColor = String(node && node.mycolor || fallbackColor || '').trim();
    if (!baseColor) {
      return String(fallbackColor || '#94a3b8');
    }

    if (!roleHasAttachedUsers(node)) {
      return colorToDesaturatedGray(baseColor, fallbackColor);
    }

    return baseColor;
  }

  function getNodePackSize(node, inheritedSize) {
    const baseSize = Math.max(2, Number(inheritedSize) || 2);

    if (!node || String(node.type) !== '1') {
      return baseSize;
    }

    const memberCount = Array.isArray(node.userIds) ? node.userIds.length : 0;
    const childCount = Array.isArray(node.children) ? node.children.length : 0;
    const weightedSize = 3 + (memberCount * 1.4) + (childCount * 0.35);

    return Math.max(baseSize, weightedSize);
  }

  function normalizeStructureNode(node, depth) {
    if (!node || typeof node !== 'object') {
      return null;
    }

    const normalizedNode = Object.assign({}, node);
    normalizedNode.depth = Number.isFinite(Number(depth)) ? Number(depth) : 0;
    normalizedNode.ID = String(node.ID || '');
    normalizedNode.name = String(node.name || '');
    normalizedNode.type = String(node.type || '');
    normalizedNode.mycolor = String(node.mycolor || '');
    normalizedNode.userIds = Array.isArray(node.userIds) ? node.userIds.slice() : [];
    normalizedNode.size = getNodePackSize(normalizedNode, node.size);

    if (Array.isArray(node.children) && node.children.length > 0) {
      normalizedNode.children = node.children.map(function (childNode) {
        return normalizeStructureNode(childNode, normalizedNode.depth + 1);
      }).filter(Boolean);
    } else {
      normalizedNode.children = [];
    }

    return normalizedNode;
  }

  function removeColorNodes(json, size) {
    if (Array.isArray(json)) {
      return json.map(function (item) {
        return removeColorNodes(item, size);
      });
    }

    if (!json || typeof json !== 'object') {
      return json;
    }

    Object.keys(json).forEach(function (key) {
      if (key === 'color') {
        delete json[key];
        return;
      }

      if (key === 'size') {
        json[key] = getNodePackSize(json, size);
        return;
      }

      if (key === 'children') {
        const nextSize = json.type === '2'
          ? (size > 2 ? size - 2 : 2)
          : size;
        json[key] = removeColorNodes(json[key], nextSize);
      }
    });

    return json;
  }

  function findNodeById(node, nodeId) {
    if (!node || nodeId === null || nodeId === undefined || nodeId === '') {
      return null;
    }

    if (String(node.ID) === String(nodeId)) {
      return node;
    }

    if (!Array.isArray(node.children)) {
      return null;
    }

    for (let index = 0; index < node.children.length; index += 1) {
      const foundNode = findNodeById(node.children[index], nodeId);
      if (foundNode) {
        return foundNode;
      }
    }

    return null;
  }

  function getNodeDepth(node) {
    const depth = Number(node && node.depth);
    return Number.isFinite(depth) && depth >= 0 ? depth : 0;
  }

  function getNodeDepthOpacity(node, currentNode, rootNode, minOpacity, maxOpacity) {
    const safeMinOpacity = clampNumber(Number(minOpacity), 0, 1);
    const safeMaxOpacity = clampNumber(Number(maxOpacity), safeMinOpacity, 1);
    const referenceNode = currentNode || rootNode || null;
    const referenceDepth = referenceNode ? getNodeDepth(referenceNode) : 0;
    const distanceFromCurrentLevel = Math.abs(getNodeDepth(node) - referenceDepth);
    const opacityStep = 0.18;
    const fadeDistance = Math.max(0, distanceFromCurrentLevel - 1);

    return clampNumber(safeMaxOpacity - (fadeDistance * opacityStep), safeMinOpacity, safeMaxOpacity);
  }

  function getNodeVisualOpacity(node, currentNode, rootNode) {
    return getNodeDepthOpacity(node, currentNode, rootNode, 0.24, 1);
  }

  function getNodeFill(node, nodeOpacity, chartColors) {
    const nodeType = String(node && node.type || '');
    const displayColor = getNodeDisplayColor(node, nodeType === '4' ? chartColors.rootFill : chartColors.roleFill);

    if (nodeType === '2' || nodeType === '3') {
      return colorToTransparentFill(node && node.mycolor, 0.06 + (0.16 * nodeOpacity), chartColors.groupFill);
    }

    if (nodeType === '4') {
      return colorToTransparentFill(displayColor, nodeOpacity, chartColors.rootFill);
    }

    return colorToTransparentFill(displayColor, nodeOpacity, chartColors.roleFill);
  }

  function getNodeStroke(node, nodeOpacity, chartColors) {
    const nodeType = String(node && node.type || '');

    if (nodeType === '3') {
      return colorToTransparentFill(node && node.mycolor, 0.2 + (0.45 * nodeOpacity), chartColors.strokeSoft);
    }

    if (nodeType === '4') {
      return colorToTransparentFill('#ffffff', 0.15 + (0.35 * nodeOpacity), 'rgba(255,255,255,0.5)');
    }

    return null;
  }

  function drawNodeLabel(ctx, node, textColor, strokeColor) {
    if (!node || !node.name || node.r < 18) {
      return;
    }

    const maxWidth = node.r * 1.4;
    let fontSize = Math.max(10, Math.min(15, node.r * 0.28));
    const words = String(node.name).split(/\s+/).filter(Boolean);
    const lines = [];
    let currentLine = '';

    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = String(textColor || 'rgba(15, 23, 42, 0.82)');
    ctx.font = '600 ' + fontSize + 'px system-ui, sans-serif';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = String(strokeColor || 'rgba(0, 0, 0, 0)');
    ctx.lineWidth = Math.max(2, Math.min(4, fontSize * 0.24));

    words.forEach(function (word) {
      const candidate = currentLine ? currentLine + ' ' + word : word;
      if (ctx.measureText(candidate).width <= maxWidth || !currentLine) {
        currentLine = candidate;
        return;
      }

      lines.push(currentLine);
      currentLine = word;
    });

    if (currentLine) {
      lines.push(currentLine);
    }

    const visibleLines = lines.slice(0, 2);
    const totalHeight = visibleLines.length * fontSize * 1.15;
    visibleLines.forEach(function (line, index) {
      let output = line;
      if (index === 1 && lines.length > 2) {
        output = line.replace(/\s+\S*$/, '') + '...';
      }

      const y = node.y + ((index + 0.5) * fontSize * 1.15) - (totalHeight / 2);
      if (strokeColor) {
        ctx.strokeText(output, node.x, y);
      }
      ctx.fillText(output, node.x, y);
    });
  }

  function isDirectChildNode(node, currentNode) {
    if (!node || !currentNode || !node.parent || !node.parent.ID) {
      return false;
    }

    return String(node.parent.ID) === String(currentNode.ID);
  }

  function isSameLevelSiblingNode(node, currentNode) {
    if (!node || !currentNode || !node.parent || !currentNode.parent || !node.parent.ID || !currentNode.parent.ID) {
      return false;
    }

    return String(node.parent.ID) === String(currentNode.parent.ID);
  }

  function shouldDrawInlineNodeLabel(node, currentNode, hoveredNode, screenR) {
    if (!node || !currentNode || screenR < 18) {
      return false;
    }

    const nodeId = String(node.ID || '');
    const currentNodeId = String(currentNode.ID || '');
    const currentNodeType = String(currentNode.type || '');

    if (!nodeId) {
      return false;
    }

    if (hoveredNode && nodeId === String(hoveredNode.ID || '')) {
      return true;
    }

    if (currentNodeType === '1') {
      if (nodeId === currentNodeId) {
        return screenR >= 18;
      }

      return isSameLevelSiblingNode(node, currentNode) && screenR >= 22;
    }

    if (nodeId === currentNodeId) {
      return false;
    }

    return isDirectChildNode(node, currentNode) && screenR >= 22;
  }

  function getNodeLabelStyle(node, chartColors) {
    if (String(node && node.type || '') === '1') {
      return {
        fill: chartColors.labelDark,
        stroke: null
      };
    }

    return {
      fill: chartColors.labelLight,
      stroke: chartColors.labelDark
    };
  }

  function getPackTypeOrder(node) {
    switch (String(node && node.type ? node.type : '')) {
      case '4':
        return 0;
      case '1':
        return 1;
      case '3':
        return 2;
      case '2':
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

    const nameDifference = String(a && a.name ? a.name : '').localeCompare(String(b && b.name ? b.name : ''));
    if (nameDifference !== 0) {
      return nameDifference;
    }

    return String(a && a.ID ? a.ID : '').localeCompare(String(b && b.ID ? b.ID : ''));
  }

  function getNodeRadiusFactor(node) {
    const nodeType = String(node && node.type ? node.type : '');

    if (nodeType === '1') {
      return 0.9;
    }

    if (nodeType === '4') {
      return 1.05;
    }

    return 1;
  }

  function shouldUseTightZoom(focusNode) {
    return !!focusNode && String(focusNode.type || '') === '1';
  }

  function arraysAreEqual(arr1, arr2) {
    if (!Array.isArray(arr1) || !Array.isArray(arr2) || arr1.length !== arr2.length) {
      return false;
    }

    for (let index = 0; index < arr1.length; index += 1) {
      if (arr1[index] !== arr2[index]) {
        return false;
      }
    }

    return true;
  }

  function easeInOutCubic(value) {
    if (value < 0.5) {
      return 4 * value * value * value;
    }

    return 1 - Math.pow((-2 * value) + 2, 3) / 2;
  }

  function createMiniStructureMap(host) {
    const shell = document.getElementById('omoLeftPanelShell');
    const structurePanel = document.getElementById('panel-left-structure');
    const state = {
      host: host,
      shell: shell,
      structurePanel: structurePanel,
      wrapper: null,
      canvas: null,
      tooltip: null,
      resizeObserver: null,
      currentOid: null,
      rootData: null,
      packedNodes: [],
      packedNodesById: Object.create(null),
      renderedNodes: [],
      currentNodeId: null,
      hoveredNodeId: null,
      requestId: 0,
      dpr: 1,
      width: 0,
      height: 0,
      centerX: 0,
      centerY: 0,
      diameter: 0,
      zoomInfo: null,
      vOld: null,
      layoutDirty: true,
      animationFrameId: null,
      animationInterpolator: null,
      animationDuration: 0,
      animationStartTime: 0,
      unregisterViewTarget: null,
      mobileMediaQuery: typeof window.matchMedia === 'function'
        ? window.matchMedia(MOBILE_MEDIA_QUERY)
        : null,
      unregisterMobileMediaQueryListener: null,
      structureAreaVisible: false
    };

    function cancelAnimation() {
      if (state.animationFrameId) {
        cancelAnimationFrame(state.animationFrameId);
        state.animationFrameId = null;
      }

      state.animationInterpolator = null;
      state.animationDuration = 0;
      state.animationStartTime = 0;
    }

    function hideStructureArea() {
      if (state.shell) {
        state.shell.classList.add('omo-left-panel-shell--structure-hidden');
      }
    }

    function showStructureArea() {
      if (state.shell) {
        state.shell.classList.remove('omo-left-panel-shell--structure-hidden');
      }
    }

    function isStructureFeatureEnabled() {
      if (window.omoConfig && window.omoConfig.shareAllowsStructure === false) {
        return false;
      }

      return !(window.omoConfig && window.omoConfig.structureEnabled === false);
    }

    function isMobileViewport() {
      return Boolean(state.mobileMediaQuery && state.mobileMediaQuery.matches);
    }

    function canDisplayStructureArea() {
      return isStructureFeatureEnabled() && !isMobileViewport();
    }

    function setStructureAreaVisibility(shouldShow) {
      if (shouldShow) {
        showStructureArea();
        state.structureAreaVisible = true;
        return true;
      }

      if (state.structureAreaVisible) {
        state.requestId += 1;
        cancelAnimation();
      }

      state.hoveredNodeId = null;
      if (state.canvas) {
        state.canvas.style.cursor = 'default';
      }

      hideStructureArea();
      state.structureAreaVisible = false;
      return false;
    }

    function renderStaticState(message, muted) {
      cancelAnimation();

      state.host.innerHTML = '<div class="omo-left-structure-map__state' + (muted ? ' is-muted' : '') + '">' + String(message || '') + '</div>';
      state.wrapper = null;
      state.canvas = null;
      state.tooltip = null;
      state.packedNodes = [];
      state.packedNodesById = Object.create(null);
      state.renderedNodes = [];
      state.zoomInfo = null;
      state.vOld = null;
      state.layoutDirty = true;
    }

    function ensureShell() {
      if (state.wrapper && state.canvas && state.tooltip) {
        return true;
      }

      state.host.innerHTML = [
        '<div class="omo-left-structure-map">',
        '  <canvas class="omo-left-structure-map__canvas"></canvas>',
        '  <div class="omo-left-structure-map__tooltip is-muted">Structure</div>',
        '</div>'
      ].join('');

      state.wrapper = state.host.querySelector('.omo-left-structure-map');
      state.canvas = state.host.querySelector('.omo-left-structure-map__canvas');
      state.tooltip = state.host.querySelector('.omo-left-structure-map__tooltip');

      if (!state.wrapper || !state.canvas || !state.tooltip) {
        return false;
      }

      state.canvas.addEventListener('mousemove', handlePointerMove);
      state.canvas.addEventListener('mouseleave', handlePointerLeave);
      state.canvas.addEventListener('click', handleCanvasClick);
      return true;
    }

    function updateTooltip() {
      if (!state.tooltip) {
        return;
      }

      const hoveredNode = state.hoveredNodeId ? state.packedNodesById[String(state.hoveredNodeId)] : null;
      const currentNode = state.currentNodeId ? state.packedNodesById[String(state.currentNodeId)] : null;

      if (hoveredNode && hoveredNode.name) {
        state.tooltip.textContent = hoveredNode.name;
        state.tooltip.classList.remove('is-muted');
        return;
      }

      if (currentNode && currentNode.name) {
        state.tooltip.textContent = currentNode.name;
        state.tooltip.classList.remove('is-muted');
        return;
      }

      state.tooltip.textContent = 'Structure';
      state.tooltip.classList.add('is-muted');
    }

    function getCurrentNodeIdFromRoute() {
      const route = resolveRoute();
      const routeCid = route && route.cid !== null && route.cid !== undefined && route.cid !== ''
        ? Number(route.cid)
        : 0;

      if (Number.isInteger(routeCid) && routeCid > 0) {
        return String(routeCid);
      }

      return state.rootData && state.rootData.ID ? String(state.rootData.ID) : null;
    }

    function getTargetNode(nodeId) {
      const normalizedNodeId = nodeId === null || nodeId === undefined || nodeId === ''
        ? null
        : String(nodeId);
      const resolvedId = normalizedNodeId || state.currentNodeId || getCurrentNodeIdFromRoute();

      if (resolvedId && state.packedNodesById[String(resolvedId)]) {
        return state.packedNodesById[String(resolvedId)];
      }

      return state.packedNodes.length ? state.packedNodes[0] : null;
    }

    function buildNodeView(node) {
      if (!node) {
        return null;
      }

      return shouldUseTightZoom(node)
        ? [node.x, node.y, node.r * 4.05]
        : [node.x, node.y, node.r * 2.05];
    }

    function applyView(view) {
      if (!Array.isArray(view) || view.length !== 3 || !state.diameter) {
        return;
      }

      state.zoomInfo = {
        centerX: view[0],
        centerY: view[1],
        scale: state.diameter / view[2]
      };
      state.vOld = view.slice();
    }

    function syncCanvasMetrics() {
      if (!state.host || !state.canvas) {
        return false;
      }

      const rect = state.host.getBoundingClientRect();
      const width = Math.max(1, Math.floor(rect.width));
      const height = Math.max(1, Math.floor(rect.height));

      if (width <= 1 || height <= 1) {
        return false;
      }

      if (state.width !== width || state.height !== height) {
        state.layoutDirty = true;
      }

      state.width = width;
      state.height = height;
      state.centerX = width / 2;
      state.centerY = height / 2;
      state.diameter = Math.min(width * 0.9, height * 0.9);
      state.dpr = window.devicePixelRatio || 1;
      state.canvas.width = Math.max(1, Math.floor(width * state.dpr));
      state.canvas.height = Math.max(1, Math.floor(height * state.dpr));
      state.canvas.style.width = width + 'px';
      state.canvas.style.height = height + 'px';

      return true;
    }

    function buildPackedNodes() {
      if (!state.rootData || !window.d3 || state.diameter <= 0) {
        state.packedNodes = [];
        state.packedNodesById = Object.create(null);
        state.renderedNodes = [];
        return;
      }

      const packedRoot = JSON.parse(JSON.stringify(state.rootData));
      removeColorNodes(packedRoot, 10);

      const pack = window.d3.layout.pack()
        .padding(1)
        .size([state.diameter, state.diameter])
        .value(function (node) {
          return node.size || 1;
        })
        .sort(comparePackNodes);

      const nodes = pack.nodes(packedRoot);
      const nodeMap = Object.create(null);

      nodes.forEach(function (node) {
        if (!node || !node.ID) {
          return;
        }

        nodeMap[String(node.ID)] = node;
      });

      state.packedNodes = nodes;
      state.packedNodesById = nodeMap;
      state.layoutDirty = false;
    }

    function ensureLayout() {
      if (!ensureShell() || !syncCanvasMetrics()) {
        return false;
      }

      if (state.layoutDirty || !state.packedNodes.length) {
        buildPackedNodes();
      }

      return state.packedNodes.length > 0;
    }

    function renderFrame() {
      if (!ensureLayout()) {
        return;
      }

      const ctx = state.canvas.getContext('2d');
      if (!ctx) {
        return;
      }

      const chartColors = getChartColors();
      const currentNode = getTargetNode();
      const hoveredNode = state.hoveredNodeId ? state.packedNodesById[String(state.hoveredNodeId)] : null;
      const rootNode = state.packedNodes.length ? state.packedNodes[0] : null;

      ctx.setTransform(state.dpr, 0, 0, state.dpr, 0, 0);
      ctx.clearRect(0, 0, state.width, state.height);
      ctx.fillStyle = chartColors.background;
      ctx.fillRect(0, 0, state.width, state.height);
      state.renderedNodes = [];
      const labelsToDraw = [];

      state.packedNodes
        .slice()
        .sort(function (left, right) {
          return (left.depth || 0) - (right.depth || 0);
        })
        .forEach(function (node) {
          if (!node || !node.ID || !state.zoomInfo) {
            return;
          }

          const nodeId = String(node.ID);
          const isActive = currentNode && nodeId === String(currentNode.ID);
          const isHovered = hoveredNode && nodeId === String(hoveredNode.ID);
          const nodeOpacity = getNodeVisualOpacity(node, currentNode, rootNode);
          const screenX = ((node.x - state.zoomInfo.centerX) * state.zoomInfo.scale) + state.centerX;
          const screenY = ((node.y - state.zoomInfo.centerY) * state.zoomInfo.scale) + state.centerY;
          const screenR = Math.max(1, node.r * state.zoomInfo.scale * getNodeRadiusFactor(node));
          const labelNode = {
            name: node.name,
            x: screenX,
            y: screenY,
            r: screenR
          };

          state.renderedNodes.push({
            ID: nodeId,
            depth: Number(node.depth || 0),
            x: screenX,
            y: screenY,
            r: screenR
          });

          ctx.beginPath();
          ctx.arc(screenX, screenY, screenR, 0, Math.PI * 2);
          ctx.fillStyle = getNodeFill(node, nodeOpacity, chartColors);
          ctx.setLineDash([]);

          if (String(node.type || '') === '3') {
            ctx.fillStyle = 'rgba(0,0,0,0)';
            ctx.lineWidth = 1;
            ctx.setLineDash([4, 4]);
            ctx.strokeStyle = getNodeStroke(node, nodeOpacity, chartColors) || chartColors.strokeSoft;
            ctx.stroke();
            ctx.fill();
          } else if (String(node.type || '') === '4') {
            ctx.lineWidth = 1;
            ctx.strokeStyle = getNodeStroke(node, nodeOpacity, chartColors) || chartColors.strokeSoft;
            ctx.stroke();
            ctx.fill();
          } else {
            ctx.fill();
          }

          if (isActive) {
            ctx.beginPath();
            ctx.arc(screenX, screenY, screenR, 0, Math.PI * 2);
            ctx.lineWidth = 2;
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.92)';
            ctx.stroke();
          } else if (isHovered) {
            ctx.beginPath();
            ctx.arc(screenX, screenY, screenR, 0, Math.PI * 2);
            ctx.lineWidth = Math.max(1.6, Math.min(3.2, screenR * 0.08));
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.72)';
            ctx.stroke();
          }

          if (shouldDrawInlineNodeLabel(node, currentNode, hoveredNode, screenR)) {
            const labelStyle = getNodeLabelStyle(node, chartColors);
            labelsToDraw.push({
              node: labelNode,
              color: labelStyle.fill,
              stroke: labelStyle.stroke,
              depth: Number(node.depth || 0),
              isRole: String(node.type || '') === '1',
              isHovered: isHovered
            });
          }
        });

      labelsToDraw.sort(function (left, right) {
        const leftPriority = left.isHovered ? 2 : (left.isRole ? 0 : 1);
        const rightPriority = right.isHovered ? 2 : (right.isRole ? 0 : 1);

        if (leftPriority !== rightPriority) {
          return leftPriority - rightPriority;
        }

        return left.depth - right.depth;
      });

      labelsToDraw.forEach(function (labelEntry) {
        drawNodeLabel(ctx, labelEntry.node, labelEntry.color, labelEntry.stroke);
      });

      updateTooltip();
    }

    function quickZoomToNode(node) {
      const view = buildNodeView(node);
      if (!view) {
        return Promise.resolve(null);
      }

      cancelAnimation();
      applyView(view);
      renderFrame();
      return Promise.resolve(node);
    }

    function animateFocusToNode(node) {
      const view = buildNodeView(node);
      if (!view) {
        return Promise.resolve(null);
      }

      if (!window.d3 || !state.vOld || arraysAreEqual(state.vOld, view)) {
        return quickZoomToNode(node);
      }

      cancelAnimation();
      state.animationInterpolator = window.d3.interpolateZoom(state.vOld, view);
      state.animationDuration = Math.max(500, state.animationInterpolator.duration || 500);
      state.animationStartTime = 0;

      return new Promise(function (resolve) {
        function step(timestamp) {
          if (!state.animationInterpolator) {
            resolve(node);
            return;
          }

          if (!state.animationStartTime) {
            state.animationStartTime = timestamp;
          }

          const elapsed = timestamp - state.animationStartTime;
          const progress = clampNumber(elapsed / state.animationDuration, 0, 1);
          const easedProgress = easeInOutCubic(progress);
          const nextView = state.animationInterpolator(easedProgress);

          state.zoomInfo = {
            centerX: nextView[0],
            centerY: nextView[1],
            scale: state.diameter / nextView[2]
          };

          renderFrame();

          if (progress >= 1) {
            state.vOld = view.slice();
            cancelAnimation();
            renderFrame();
            resolve(node);
            return;
          }

          state.animationFrameId = requestAnimationFrame(step);
        }

        state.animationFrameId = requestAnimationFrame(step);
      });
    }

    function focusCurrentNode(options) {
      const settings = Object.assign({
        animate: false,
        quickZoom: false
      }, options || {});

      if (!ensureLayout()) {
        return Promise.resolve(null);
      }

      const targetNode = getTargetNode(settings.nodeId);
      if (!targetNode) {
        return Promise.resolve(null);
      }

      if (!state.zoomInfo || !state.vOld || settings.quickZoom) {
        return quickZoomToNode(targetNode);
      }

      if (settings.animate) {
        return animateFocusToNode(targetNode);
      }

      renderFrame();
      return Promise.resolve(targetNode);
    }

    function updateCurrentNode(nodeId, options) {
      const normalizedNodeId = nodeId === null || nodeId === undefined || nodeId === ''
        ? null
        : String(nodeId);

      state.currentNodeId = normalizedNodeId || getCurrentNodeIdFromRoute();
      return focusCurrentNode(options);
    }

    function loadStructureData(oid, focusNodeId, options) {
      const settings = Object.assign({
        animate: false,
        quickZoom: true
      }, options || {});

      if (!Number.isInteger(Number(oid)) || Number(oid) <= 0) {
        renderStaticState('Organisation introuvable.', true);
        return Promise.resolve(null);
      }

      state.currentOid = Number(oid);
      state.requestId += 1;
      const requestId = state.requestId;

      renderStaticState('Chargement de la structure...', true);

      const url = buildStructureDataUrl(oid);
      const structureRequest = typeof window.omoFetchStructureData === 'function'
        ? window.omoFetchStructureData(url, {
            forceRefresh: Boolean(settings.forceRefresh)
          })
        : fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              Accept: 'application/json'
            }
          }).then(function (response) {
            if (!response.ok) {
              throw new Error('Structure indisponible.');
            }
            return response.json();
          });

      return Promise.all([ensureD3(), structureRequest])
        .then(function (results) {
          const response = results[1];
          if (requestId !== state.requestId) {
            return null;
          }

          if (!response || response.error) {
            throw new Error(response && response.message ? response.message : 'Structure indisponible.');
          }

          const normalizedRoot = normalizeStructureNode(response, 0);
          if (!normalizedRoot) {
            throw new Error('Structure indisponible.');
          }

          state.rootData = normalizedRoot;
          state.layoutDirty = true;
          if (!ensureShell()) {
            return null;
          }

          return updateCurrentNode(focusNodeId || getCurrentNodeIdFromRoute(), {
            animate: settings.animate,
            quickZoom: settings.quickZoom
          }).then(function () {
            return state.rootData;
          });
        })
        .catch(function () {
          if (requestId !== state.requestId) {
            return null;
          }

          state.rootData = null;
          renderStaticState('Structure indisponible.', true);
          return null;
        });
    }

    function draw(options) {
      const settings = Object.assign({
        rebuildLayout: false,
        quickZoom: false
      }, options || {});

      if (settings.rebuildLayout) {
        state.layoutDirty = true;
      }

      if (settings.quickZoom || !state.zoomInfo || !state.vOld) {
        focusCurrentNode({
          quickZoom: true
        });
        return;
      }

      renderFrame();
    }

    function hitTest(clientX, clientY) {
      if (!state.renderedNodes.length) {
        return null;
      }

      const rect = state.canvas.getBoundingClientRect();
      const x = clientX - rect.left;
      const y = clientY - rect.top;
      const sortedNodes = state.renderedNodes.slice().sort(function (left, right) {
        const depthDelta = (right.depth || 0) - (left.depth || 0);
        if (depthDelta !== 0) {
          return depthDelta;
        }

        return (left.r || 0) - (right.r || 0);
      });

      for (let index = 0; index < sortedNodes.length; index += 1) {
        const node = sortedNodes[index];
        const dx = x - node.x;
        const dy = y - node.y;
        if ((dx * dx) + (dy * dy) <= (node.r * node.r)) {
          return node;
        }
      }

      return null;
    }

    function handlePointerMove(event) {
      const node = hitTest(event.clientX, event.clientY);
      const nextHoverId = node && node.ID ? String(node.ID) : null;

      if (nextHoverId === state.hoveredNodeId) {
        return;
      }

      state.hoveredNodeId = nextHoverId;
      state.canvas.style.cursor = node ? 'pointer' : 'default';
      updateTooltip();
      draw();
    }

    function handlePointerLeave() {
      state.hoveredNodeId = null;
      if (state.canvas) {
        state.canvas.style.cursor = 'default';
      }
      updateTooltip();
      draw();
    }

    function handleCanvasClick(event) {
      const node = hitTest(event.clientX, event.clientY);
      if (!node || typeof navigate !== 'function') {
        return;
      }

      const route = resolveRoute();
      const targetCid = state.rootData && String(state.rootData.ID) === String(node.ID)
        ? null
        : Number(node.ID || 0);

      navigate(route.oid, Number.isInteger(targetCid) && targetCid > 0 ? targetCid : null, route.hash || null);
    }

    function syncRoute(detail) {
      if (!setStructureAreaVisibility(canDisplayStructureArea())) {
        return;
      }

      const nextOid = detail && detail.oid ? Number(detail.oid) : Number(resolveRoute().oid || 0);
      const nextCid = detail && Object.prototype.hasOwnProperty.call(detail, 'cid')
        ? detail.cid
        : resolveRoute().cid;

      if (!Number.isInteger(nextOid) || nextOid <= 0) {
        renderStaticState('Organisation introuvable.', true);
        return;
      }

      if (!state.rootData || state.currentOid !== nextOid || (detail && detail.organizationChanged)) {
        loadStructureData(nextOid, nextCid, {
          quickZoom: true
        });
        return;
      }

      updateCurrentNode(nextCid, {
        animate: Boolean(detail && detail.cidChanged),
        quickZoom: !(detail && detail.cidChanged)
      });
    }

    function handleStructureRefresh(event) {
      const detail = event && event.detail ? event.detail : {};
      const route = resolveRoute();
      const oid = route && route.oid ? Number(route.oid) : Number(state.currentOid || 0);
      const cid = Object.prototype.hasOwnProperty.call(detail, 'cid') ? detail.cid : route.cid;

      if (cid !== null && cid !== undefined && cid !== '') {
        state.currentNodeId = String(cid);
      }

      if (!setStructureAreaVisibility(canDisplayStructureArea())) {
        return;
      }

      if (!Number.isInteger(oid) || oid <= 0) {
        return;
      }

      loadStructureData(oid, cid, {
        quickZoom: Boolean(detail.quickZoom),
        forceRefresh: true
      });
    }

    function handleStructureFocus(event) {
      const cid = event && event.detail ? event.detail.cid : null;
      const quickZoom = Boolean(event && event.detail && event.detail.quickZoom);

      state.currentNodeId = cid === null || cid === undefined || cid === ''
        ? getCurrentNodeIdFromRoute()
        : String(cid);

      if (!setStructureAreaVisibility(canDisplayStructureArea())) {
        return;
      }

      updateCurrentNode(cid, {
        animate: !quickZoom,
        quickZoom: quickZoom
      });
    }

    function handleStructureAvailabilityChange(event) {
      const detail = event && event.detail ? event.detail : {};
      syncRoute(detail);
    }

    function init() {
      if (!state.host) {
        return;
      }

      if (typeof ResizeObserver === 'function' && state.structurePanel) {
        state.resizeObserver = new ResizeObserver(function () {
          if (!canDisplayStructureArea()) {
            return;
          }

          draw({
            rebuildLayout: true,
            quickZoom: true
          });
        });
        state.resizeObserver.observe(state.structurePanel);
      }

      window.addEventListener('omo-left-structure-resize', function () {
        if (!canDisplayStructureArea()) {
          return;
        }

        draw({
          rebuildLayout: true,
          quickZoom: true
        });
      });
      window.addEventListener('omo-structure-route-sync', function (event) {
        syncRoute(event && event.detail ? event.detail : {});
      });
      window.addEventListener('omo-structure-refresh', handleStructureRefresh);
      window.addEventListener('omo-structure-focus', handleStructureFocus);
      window.addEventListener('omo-structure-availability-change', handleStructureAvailabilityChange);

      state.unregisterMobileMediaQueryListener = addMediaQueryChangeListener(state.mobileMediaQuery, function () {
        syncRoute(resolveRoute());
      });

      if (typeof window.omoRegisterStructureViewTarget === 'function') {
        state.unregisterViewTarget = window.omoRegisterStructureViewTarget('left-structure-mini-map', {
          reloadAndFocus: function (nodeId) {
            const route = resolveRoute();
            const oid = route && route.oid ? Number(route.oid) : Number(state.currentOid || 0);
            state.currentNodeId = nodeId === null || nodeId === undefined || nodeId === ''
              ? getCurrentNodeIdFromRoute()
              : String(nodeId);

            if (!setStructureAreaVisibility(canDisplayStructureArea())) {
              return Promise.resolve(null);
            }

            if (!Number.isInteger(oid) || oid <= 0) {
              return Promise.resolve(null);
            }

            return loadStructureData(oid, nodeId, {
              quickZoom: true,
              forceRefresh: true
            });
          },
          getCurrentHolonId: function () {
            const currentNodeId = state.currentNodeId ? Number(state.currentNodeId) : 0;
            return Number.isInteger(currentNodeId) && currentNodeId > 0 ? currentNodeId : 0;
          }
        });
      }

      syncRoute(resolveRoute());
    }

    return {
      init: init
    };
  }

  function bootMiniStructureMap() {
    const host = document.getElementById('omo-left-structure-map');
    if (!host) {
      return;
    }

    const miniMap = createMiniStructureMap(host);
    miniMap.init();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootMiniStructureMap, { once: true });
  } else {
    bootMiniStructureMap();
  }
})();
