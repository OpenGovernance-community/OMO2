(function (window) {
    'use strict';

    function normalizeId(value) {
        var id = Number(value || 0);
        return Number.isInteger(id) && id > 0 ? id : 0;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function ensureD3() {
        if (window.d3 && window.d3.layout && typeof window.d3.layout.pack === 'function') {
            return Promise.resolve(window.d3);
        }

        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[data-omo-d3-v3="1"]');
            if (existing) {
                existing.addEventListener('load', function () { resolve(window.d3); }, { once: true });
                existing.addEventListener('error', reject, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.6/d3.min.js';
            script.async = true;
            script.setAttribute('data-omo-d3-v3', '1');
            script.onload = function () { resolve(window.d3); };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function clampNumber(value, minimum, maximum) {
        return Math.min(Math.max(value, minimum), maximum);
    }

    function parseColorChannels(color) {
        var raw = String(color || '').trim();
        var hexMatch = raw.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (hexMatch) {
            var hex = hexMatch[1];
            if (hex.length === 3) {
                hex = hex.split('').map(function (part) { return part + part; }).join('');
            }
            return {
                red: parseInt(hex.slice(0, 2), 16),
                green: parseInt(hex.slice(2, 4), 16),
                blue: parseInt(hex.slice(4, 6), 16),
                alpha: 1
            };
        }

        var rgbMatch = raw.match(/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+)\s*)?\)$/i);
        if (!rgbMatch) {
            return null;
        }
        return {
            red: clampNumber(Number(rgbMatch[1]), 0, 255),
            green: clampNumber(Number(rgbMatch[2]), 0, 255),
            blue: clampNumber(Number(rgbMatch[3]), 0, 255),
            alpha: rgbMatch[4] === undefined ? 1 : clampNumber(Number(rgbMatch[4]), 0, 1)
        };
    }

    function colorToTransparentFill(color, alpha, fallback) {
        var channels = parseColorChannels(color);
        if (!channels) {
            return String(fallback || color || 'rgba(15, 23, 42, 0.18)');
        }
        var finalAlpha = clampNumber(Number(alpha), 0, 1) * clampNumber(Number(channels.alpha), 0, 1);
        return 'rgba(' + Math.round(channels.red) + ', ' + Math.round(channels.green) + ', ' + Math.round(channels.blue) + ', ' + finalAlpha + ')';
    }

    function colorToDesaturatedGray(color, fallback) {
        var channels = parseColorChannels(color || fallback);
        if (!channels) {
            return String(fallback || color || '#94a3b8');
        }
        var gray = Math.round((channels.red * 0.299) + (channels.green * 0.587) + (channels.blue * 0.114));
        return 'rgba(' + gray + ', ' + gray + ', ' + gray + ', ' + clampNumber(Number(channels.alpha), 0, 1) + ')';
    }

    function getCssVar(name, fallback) {
        var value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return value || String(fallback || '');
    }

    function getChartColors() {
        return {
            background: getCssVar('--chart-bg', '#f0f2f5'),
            rootFill: getCssVar('--chart-root-fill', '#4f46e5'),
            groupFill: getCssVar('--chart-group-fill', 'rgba(79, 70, 229, 0.12)'),
            roleFill: getCssVar('--chart-role-fill', '#fbbf24'),
            labelDark: getCssVar('--chart-label-dark', '#1f2937'),
            labelLight: getCssVar('--chart-label-light', '#ffffff'),
            strokeSoft: getCssVar('--chart-stroke-soft', 'rgba(255,255,255,0.5)')
        };
    }

    function roleHasAttachedUsers(node) {
        return String(node && node.type || '') !== '1'
            || (Array.isArray(node && node.userIds) && node.userIds.length > 0);
    }

    function getNodeDisplayColor(node, fallbackColor) {
        var color = String(node && node.mycolor || fallbackColor || '').trim();
        return node && node.ignoreAssignmentColor
            ? color
            : (roleHasAttachedUsers(node) ? color : colorToDesaturatedGray(color, fallbackColor));
    }

    function getNodeVisualOpacity(node, currentNode, rootNode) {
        var currentDepth = Number(currentNode && currentNode.depth || rootNode && rootNode.depth || 0);
        var distance = Math.max(0, Math.abs(Number(node && node.depth || 0) - currentDepth) - 1);
        return clampNumber(1 - (distance * 0.18), 0.24, 1);
    }

    function getNodeFill(node, opacity, chartColors) {
        var type = String(node && node.type || '');
        if (node && node.isSelectable === false) {
            return colorToTransparentFill('#94a3b8', type === '2' || type === '3' ? 0.08 + (0.12 * opacity) : 0.3 + (0.3 * opacity), '#94a3b8');
        }
        var color = getNodeDisplayColor(node, type === '4' ? chartColors.rootFill : chartColors.roleFill);
        if (type === '2' || type === '3') {
            return colorToTransparentFill(node && node.mycolor, 0.06 + (0.16 * opacity), chartColors.groupFill);
        }
        if (type === '4') {
            return colorToTransparentFill(color, opacity, chartColors.rootFill);
        }
        return colorToTransparentFill(color, opacity, chartColors.roleFill);
    }

    function getNodeStroke(node, opacity, chartColors) {
        var type = String(node && node.type || '');
        if (node && node.isSelectable === false) {
            return colorToTransparentFill('#94a3b8', 0.18 + (0.32 * opacity), chartColors.strokeSoft);
        }
        if (type === '3') {
            return colorToTransparentFill(node && node.mycolor, 0.2 + (0.45 * opacity), chartColors.strokeSoft);
        }
        if (type === '4') {
            return colorToTransparentFill('#ffffff', 0.15 + (0.35 * opacity), chartColors.strokeSoft);
        }
        return null;
    }

    function cloneStructureNode(node) {
        var copy = {
            ID: String(node && node.ID || ''),
            name: String(node && node.name || ''),
            type: String(node && node.type || ''),
            mycolor: String(node && node.mycolor || ''),
            userIds: Array.isArray(node && node.userIds) ? node.userIds.slice() : [],
            size: 1,
            children: []
        };
        copy.children = (Array.isArray(node && node.children) ? node.children : []).map(cloneStructureNode);
        copy.size = Math.max(1, copy.children.length || 1);
        return copy;
    }

    function getNodePackSize(node, inheritedSize) {
        var baseSize = Math.max(2, Number(inheritedSize) || 2);
        if (!node || String(node.type) !== '1') {
            return baseSize;
        }
        var memberCount = Array.isArray(node.userIds) ? node.userIds.length : 0;
        var childCount = Array.isArray(node.children) ? node.children.length : 0;
        return Math.max(baseSize, 3 + (memberCount * 1.4) + (childCount * 0.35));
    }

    function preparePackedNode(node, inheritedSize) {
        if (!node || typeof node !== 'object') {
            return node;
        }
        node.size = getNodePackSize(node, inheritedSize);
        var childSize = String(node.type) === '2'
            ? (Number(inheritedSize) > 2 ? Number(inheritedSize) - 2 : 2)
            : inheritedSize;
        node.children = (Array.isArray(node.children) ? node.children : []).map(function (child) {
            return preparePackedNode(child, childSize);
        });
        return node;
    }

    function getPackTypeOrder(node) {
        switch (String(node && node.type || '')) {
            case '4': return 0;
            case '1': return 1;
            case '3': return 2;
            case '2': return 3;
            default: return 99;
        }
    }

    function comparePackNodes(left, right) {
        var typeDifference = getPackTypeOrder(left) - getPackTypeOrder(right);
        if (typeDifference !== 0) {
            return typeDifference;
        }
        var nameDifference = String(left && left.name || '').localeCompare(String(right && right.name || ''));
        return nameDifference !== 0
            ? nameDifference
            : String(left && left.ID || '').localeCompare(String(right && right.ID || ''));
    }

    function getNodeRadiusFactor(node) {
        var type = String(node && node.type || '');
        return type === '1' ? 0.9 : (type === '4' ? 1.05 : 1);
    }

    function drawMapNodeLabel(context, node, textColor, strokeColor) {
        if (!node || !node.name || node.r < 22) {
            return;
        }
        var maximumWidth = node.r * 1.45;
        var fontSize = Math.max(9, Math.min(14, node.r * 0.28));
        var words = String(node.name).split(/\s+/).filter(Boolean);
        var lines = [];
        var line = '';

        context.font = '600 ' + fontSize + 'px system-ui, sans-serif';
        words.forEach(function (word) {
            var candidate = line ? line + ' ' + word : word;
            if (context.measureText(candidate).width <= maximumWidth || !line) {
                line = candidate;
                return;
            }
            lines.push(line);
            line = word;
        });
        if (line) {
            lines.push(line);
        }

        var visibleLines = lines.slice(0, 2);
        var lineHeight = fontSize * 1.12;
        context.fillStyle = String(textColor || 'rgba(15, 23, 42, 0.82)');
        context.strokeStyle = String(strokeColor || 'rgba(0, 0, 0, 0)');
        context.lineWidth = Math.max(2, fontSize * 0.22);
        context.textAlign = 'center';
        context.textBaseline = 'middle';
        visibleLines.forEach(function (text, index) {
            var output = index === 1 && lines.length > 2 ? text.replace(/\s+\S*$/, '') + '...' : text;
            var y = node.y + ((index - ((visibleLines.length - 1) / 2)) * lineHeight);
            context.strokeText(output, node.x, y);
            context.fillText(output, node.x, y);
        });
    }

    function getCircleAncestor(node) {
        var current = node;
        var guard = 0;
        while (current && guard < 100) {
            if (String(current.type || '') === '2') {
                return current;
            }
            current = current.parent || null;
            guard += 1;
        }
        return null;
    }

    function getParentCircle(node) {
        return getCircleAncestor(node && node.parent ? node.parent : null);
    }

    function getContentLabelThresholds() {
        var isNarrowScreen = typeof window.matchMedia === 'function'
            && window.matchMedia('(max-width: 768px)').matches;
        return isNarrowScreen
            ? { minimum: 9, nearby: 13 }
            : { minimum: 18, nearby: 22 };
    }

    function shouldDrawContentNodeLabel(node, currentNode, isHovered, screenRadius) {
        var thresholds = getContentLabelThresholds();
        if (!node || !currentNode || screenRadius < thresholds.minimum) {
            return false;
        }
        if (String(node.ID || '') === String(currentNode.ID || '')) {
            return String(currentNode.type || '') === '1';
        }
        if (isHovered) {
            return true;
        }
        if (String(currentNode.type || '') === '1') {
            return Boolean(node.parent && currentNode.parent && node.parent.ID && currentNode.parent.ID)
                && String(node.parent.ID) === String(currentNode.parent.ID)
                && screenRadius >= thresholds.nearby;
        }
        return Boolean(node.parent && node.parent.ID)
            && String(node.parent.ID) === String(currentNode.ID)
            && screenRadius >= thresholds.nearby;
    }

    function createStructureCanvas(map, data, getSelectedId, onSelect, isSelectable, ignoreAssignmentColor, labelMode) {
        map.innerHTML = '<canvas class="omo-holon-scope-picker__canvas"></canvas><div class="omo-holon-scope-picker__tooltip" hidden></div>';
        var canvas = map.querySelector('.omo-holon-scope-picker__canvas');
        var tooltip = map.querySelector('.omo-holon-scope-picker__tooltip');
        var drawnNodes = [];
        var hoveredNodeId = '';
        var renderedWidth = 0;
        var renderedHeight = 0;
        var renderedDpr = 0;

        function updateTooltip() {
            if (!tooltip) {
                return;
            }
            var hoveredNode = drawnNodes.find(function (node) { return String(node.ID) === hoveredNodeId; });
            var selectedNode = drawnNodes.find(function (node) { return String(node.ID) === String(getSelectedId() || ''); }) || drawnNodes[0] || null;
            var displayedNode = hoveredNode || selectedNode;
            tooltip.textContent = displayedNode ? String(displayedNode.name || '') : '';
            tooltip.hidden = !displayedNode || !displayedNode.name;
        }

        function getNodeAt(event) {
            if (!canvas) {
                return null;
            }
            var rect = canvas.getBoundingClientRect();
            var x = event.clientX - rect.left;
            var y = event.clientY - rect.top;
            return drawnNodes.slice().sort(function (left, right) {
                var depthDifference = Number(right.depth || 0) - Number(left.depth || 0);
                return depthDifference !== 0 ? depthDifference : Number(left.r || 0) - Number(right.r || 0);
            }).find(function (node) {
                var deltaX = x - node.x;
                var deltaY = y - node.y;
                return (deltaX * deltaX) + (deltaY * deltaY) <= node.r * node.r;
            }) || null;
        }

        function draw() {
            if (!canvas || !map.isConnected || !window.d3 || !window.d3.layout) {
                return;
            }
            var rect = map.getBoundingClientRect();
            var width = Math.max(1, Math.floor(rect.width));
            var height = Math.max(1, Math.floor(rect.height));
            var dpr = window.devicePixelRatio || 1;
            if (width !== renderedWidth || height !== renderedHeight || dpr !== renderedDpr) {
                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                renderedWidth = width;
                renderedHeight = height;
                renderedDpr = dpr;
            }

            var context = canvas.getContext('2d');
            if (!context) {
                return;
            }
            var diameter = Math.min(width * 0.9, height * 0.9);
            var packedRoot = preparePackedNode(cloneStructureNode(data), 10);
            var pack = window.d3.layout.pack()
                .padding(1)
                .size([diameter, diameter])
                .value(function (node) { return node.size || 1; })
                .sort(comparePackNodes);
            var packedNodes = pack.nodes(packedRoot);
            var selectedId = String(getSelectedId() || '');
            var focusedNode = packedNodes.find(function (node) { return String(node.ID) === selectedId; }) || packedNodes[0] || null;
            var focusDiameter = focusedNode
                ? focusedNode.r * (String(focusedNode.type) === '1' ? 4.05 : 2.05)
                : diameter;
            var zoomScale = diameter / Math.max(1, focusDiameter);
            drawnNodes = packedNodes.filter(function (node) { return node && node.ID; }).map(function (node) {
                var projectedNode = Object.assign({}, node);
                projectedNode.x = ((node.x - focusedNode.x) * zoomScale) + (width / 2);
                projectedNode.y = ((node.y - focusedNode.y) * zoomScale) + (height / 2);
                projectedNode.r = Math.max(1, node.r * zoomScale * getNodeRadiusFactor(node));
                projectedNode.isSelectable = typeof isSelectable === 'function' ? isSelectable(node.ID) : true;
                projectedNode.ignoreAssignmentColor = ignoreAssignmentColor === true;
                return projectedNode;
            });
            var currentNode = drawnNodes.find(function (node) { return String(node.ID) === selectedId; }) || drawnNodes[0] || null;
            var rootNode = drawnNodes[0] || null;
            var chartColors = getChartColors();
            var labels = [];

            function addLabel(node) {
                if (!node) {
                    return;
                }
                var existing = labels.find(function (entry) {
                    return String(entry.node && entry.node.ID || '') === String(node.ID || '');
                });
                if (existing) {
                    return;
                }
                labels.push({
                    node: node,
                    textColor: String(node.type) === '1' ? chartColors.labelDark : chartColors.labelLight,
                    strokeColor: String(node.type) === '1' ? null : chartColors.labelDark,
                    depth: Number(node.depth || 0),
                    isRole: String(node.type) === '1',
                    isHovered: String(node.ID || '') === hoveredNodeId
                });
            }

            context.setTransform(dpr, 0, 0, dpr, 0, 0);
            context.clearRect(0, 0, width, height);
            context.fillStyle = chartColors.background;
            context.fillRect(0, 0, width, height);

            drawnNodes.slice().sort(function (left, right) { return Number(left.depth || 0) - Number(right.depth || 0); }).forEach(function (node) {
                var nodeId = String(node.ID);
                var isActive = nodeId === selectedId;
                var isHovered = nodeId === hoveredNodeId;
                var opacity = getNodeVisualOpacity(node, currentNode, rootNode);
                context.beginPath();
                context.arc(node.x, node.y, Math.max(1, node.r), 0, Math.PI * 2);
                context.fillStyle = getNodeFill(node, opacity, chartColors);
                context.setLineDash([]);
                if (String(node.type) === '3') {
                    context.fillStyle = 'rgba(0,0,0,0)';
                    context.lineWidth = 1;
                    context.setLineDash([4, 4]);
                    context.strokeStyle = getNodeStroke(node, opacity, chartColors) || chartColors.strokeSoft;
                    context.stroke();
                    context.fill();
                } else if (String(node.type) === '4') {
                    context.lineWidth = 1;
                    context.strokeStyle = getNodeStroke(node, opacity, chartColors) || chartColors.strokeSoft;
                    context.stroke();
                    context.fill();
                } else {
                    context.fill();
                }
                if (isActive || isHovered) {
                    context.beginPath();
                    context.arc(node.x, node.y, Math.max(1, node.r), 0, Math.PI * 2);
                    context.setLineDash([]);
                    context.lineWidth = isActive ? Math.max(2.6, Math.min(6.5, node.r * 0.16)) : Math.max(1.6, Math.min(3.2, node.r * 0.08));
                    context.strokeStyle = isActive ? 'rgba(255, 255, 255, 0.92)' : 'rgba(255, 255, 255, 0.72)';
                    context.stroke();
                }
                var shouldLabel = false;
                if (labelMode === 'children') {
                    shouldLabel = shouldDrawContentNodeLabel(node, currentNode, isHovered, node.r);
                } else {
                    var selectedType = String(currentNode && currentNode.type || '');
                    var selectedCircle = selectedType === '2' ? currentNode : getCircleAncestor(currentNode);
                    var nodeCircle = getCircleAncestor(node);
                    var nodeParentCircle = getParentCircle(node);
                    var isRoleNeighbor = selectedType === '1'
                        && String(node.type || '') === '1'
                        && selectedCircle
                        && nodeCircle
                        && String(nodeCircle.ID) === String(selectedCircle.ID);
                    var isCircleContent = selectedType === '2'
                        && nodeId !== selectedId
                        && (String(node.type || '') === '1' || String(node.type || '') === '2')
                        && nodeParentCircle
                        && String(nodeParentCircle.ID) === String(currentNode.ID);
                    shouldLabel = node.r >= 22 && (isHovered || isRoleNeighbor || isCircleContent);
                }

                if (shouldLabel) {
                    addLabel(node);
                }
            });
            labels.sort(function (left, right) {
                var leftPriority = left.isHovered ? 2 : (left.isRole ? 0 : 1);
                var rightPriority = right.isHovered ? 2 : (right.isRole ? 0 : 1);
                if (leftPriority !== rightPriority) {
                    return leftPriority - rightPriority;
                }
                return left.depth - right.depth;
            });
            labels.forEach(function (entry) { drawMapNodeLabel(context, entry.node, entry.textColor, entry.strokeColor); });
            updateTooltip();
        }

        canvas.addEventListener('mousemove', function (event) {
            var node = getNodeAt(event);
            var nextHoveredNodeId = node ? String(node.ID) : '';
            if (nextHoveredNodeId !== hoveredNodeId) {
                hoveredNodeId = nextHoveredNodeId;
                draw();
            }
            canvas.style.cursor = node ? 'pointer' : 'default';
        });
        canvas.addEventListener('mouseleave', function () {
            canvas.style.cursor = 'default';
            if (hoveredNodeId !== '') {
                hoveredNodeId = '';
                draw();
            }
        });
        canvas.addEventListener('click', function (event) {
            var node = getNodeAt(event);
            if (node && typeof onSelect === 'function') {
                onSelect(node.ID);
            }
        });

        return draw;
    }

    function flatten(node, depth, nodes, descendants, directChildren) {
        if (!node || typeof node !== 'object') {
            return [];
        }
        var id = normalizeId(node.ID);
        var childIds = [];
        var directChildIds = [];
        var appendDirectChildren = function (candidate) {
            if (!candidate || typeof candidate !== 'object') {
                return;
            }
            if (String(candidate.type || '') === '3') {
                (Array.isArray(candidate.children) ? candidate.children : []).forEach(appendDirectChildren);
                return;
            }
            var candidateId = normalizeId(candidate.ID);
            if (candidateId > 0) {
                directChildIds.push(candidateId);
            }
        };
        (Array.isArray(node.children) ? node.children : []).forEach(function (child) {
            appendDirectChildren(child);
            childIds = childIds.concat(flatten(child, depth + 1, nodes, descendants, directChildren));
        });
        if (id > 0) {
            nodes.push({ id: id, label: String(node.name || ''), color: String(node.mycolor || ''), depth: depth });
            descendants[id] = [id].concat(childIds);
            directChildren[id] = directChildIds;
            return descendants[id];
        }
        return childIds;
    }

    window.omoMountHolonScopePicker = function (options) {
        var settings = options && typeof options === 'object' ? options : {};
        var host = settings.host instanceof Element ? settings.host : null;
        var organizationId = normalizeId(settings.organizationId);
        var selectedHolonId = normalizeId(settings.initialHolonId);
        var allowEmptySelection = settings.allowEmptySelection === true;
        var ignoreHolonAssignments = settings.ignoreHolonAssignments === true;
        var labelMode = settings.labelMode === 'context' ? 'context' : 'children';
        var selectableHolonIds = Array.isArray(settings.selectableHolonIds)
            ? settings.selectableHolonIds.map(normalizeId).filter(function (id) { return id > 0; })
            : null;
        var showModes = settings.showModes !== false;
        var scope = ['local', 'children', 'descendants'].indexOf(settings.initialScope) !== -1
            ? settings.initialScope
            : 'descendants';
        var scopeIndex = scope === 'local' ? 0 : (scope === 'children' ? 1 : 2);
        var suppressInitialChange = settings.suppressInitialChange === true;
        var nodes = [];
        var descendants = Object.create(null);
        var directChildren = Object.create(null);
        var labels = settings.labels || {};
        var redrawMap = function () {};

        function isSelectableHolon(holonId) {
            return selectableHolonIds === null || selectableHolonIds.indexOf(normalizeId(holonId)) !== -1;
        }

        if (!host || organizationId <= 0) {
            return { matches: function () { return true; } };
        }

        host.innerHTML = (showModes ? '<div class="omo-holon-scope-picker__modes omo-scope-toggle" role="tablist" data-omo-scope-switch="' + scope + '" style="--omo-scope-option-count: 3; --omo-scope-active-index: ' + String(scopeIndex) + ';">'
            + '<button type="button" class="omo-scope-toggle__button' + (scope === 'local' ? ' is-active' : '') + '" data-omo-holon-scope="local" data-omo-scope-option="contextual" data-omo-scope-index="0" aria-pressed="' + (scope === 'local' ? 'true' : 'false') + '"><span class="omo-scope-toggle__text">' + escapeHtml(labels.local) + '</span></button>'
            + '<button type="button" class="omo-scope-toggle__button' + (scope === 'children' ? ' is-active' : '') + '" data-omo-holon-scope="children" data-omo-scope-option="children" data-omo-scope-index="1" aria-pressed="' + (scope === 'children' ? 'true' : 'false') + '"><span class="omo-scope-toggle__text">' + escapeHtml(labels.children) + '</span></button>'
            + '<button type="button" class="omo-scope-toggle__button' + (scope === 'descendants' ? ' is-active' : '') + '" data-omo-holon-scope="descendants" data-omo-scope-option="descendants" data-omo-scope-index="2" aria-pressed="' + (scope === 'descendants' ? 'true' : 'false') + '"><span class="omo-scope-toggle__text">' + escapeHtml(labels.descendants) + '</span></button></div>' : '')
            + '<div class="omo-holon-scope-picker__map" data-omo-holon-scope-map></div>';
        var map = host.querySelector('[data-omo-holon-scope-map]');

        function matches(value) {
            var id = normalizeId(value);
            if (selectedHolonId <= 0) {
                return false;
            }
            if (scope === 'local') {
                return id === selectedHolonId;
            }
            return scope === 'children'
                ? id === selectedHolonId || (directChildren[selectedHolonId] || []).indexOf(id) !== -1
                : (descendants[selectedHolonId] || []).indexOf(id) !== -1;
        }

        function notify() {
            var scopeIndex = scope === 'local' ? 0 : (scope === 'children' ? 1 : 2);
            var scopeToggle = host.querySelector('[data-omo-scope-switch]');
            if (scopeToggle) {
                scopeToggle.setAttribute('data-omo-scope-switch', scope);
                scopeToggle.style.setProperty('--omo-scope-active-index', String(scopeIndex));
            }
            host.querySelectorAll('[data-omo-holon-scope]').forEach(function (button) {
                var isActive = button.getAttribute('data-omo-holon-scope') === scope;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
            if (typeof settings.onChange === 'function' && !suppressInitialChange) {
                settings.onChange(selectedHolonId);
            }
            suppressInitialChange = false;
            redrawMap();
        }

        host.addEventListener('click', function (event) {
            var scopeButton = event.target.closest('[data-omo-holon-scope]');
            if (scopeButton) {
                scope = scopeButton.getAttribute('data-omo-holon-scope') || 'descendants';
                notify();
                return;
            }
        });

        var structureDataUrl = '/omo/api/getStructureData.php?oid=' + encodeURIComponent(String(organizationId));
        var structureRequest = typeof window.omoFetchStructureData === 'function'
            ? window.omoFetchStructureData(structureDataUrl)
            : fetch(structureDataUrl, { credentials: 'same-origin' })
                .then(function (response) { return response.ok ? response.json() : null; });

        structureRequest
            .then(function (data) {
                if (selectedHolonId <= 0 && !allowEmptySelection) {
                    selectedHolonId = normalizeId(data && data.ID);
                }
                flatten(data, 0, nodes, descendants, directChildren);
                if (!map) { return; }
                return ensureD3().then(function () {
                    if (!map.isConnected) {
                        return;
                    }
                    redrawMap = createStructureCanvas(map, data, function () { return selectedHolonId; }, function (nodeId) {
                        selectedHolonId = normalizeId(nodeId);
                        notify();
                    }, isSelectableHolon, ignoreHolonAssignments, labelMode);
                    notify();
                    if (typeof settings.onReady === 'function') {
                        settings.onReady(selectedHolonId);
                    }
                });
            })
            .catch(function () {});

        return {
            matches: matches,
            getSelectedHolonId: function () { return selectedHolonId; },
            setSelectedHolonId: function (holonId) {
                selectedHolonId = normalizeId(holonId);
                notify();
            },
            getSelectedHolonLabel: function () {
                var selectedNode = nodes.find(function (node) { return Number(node.id) === selectedHolonId; });
                return selectedNode ? String(selectedNode.label || '') : '';
            }
        };
    };
})(window);
