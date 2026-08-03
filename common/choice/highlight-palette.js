(function (window, document) {
    'use strict';

    if (window.omoHighlightPalette) {
        return;
    }

    var defaultColors = [
        {name: 'Jaune', value: '#f3e7ae'},
        {name: 'Vert', value: '#cfe8d2'},
        {name: 'Bleu', value: '#cbdcf0'},
        {name: 'Orange', value: '#f5d3ad'},
        {name: 'Rouge', value: '#f1c4c4'},
        {name: 'Violet', value: '#ddd0ee'}
    ];
    var activePalette = null;

    function ensureStyles() {
        if (document.getElementById('omo-highlight-palette-styles')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'omo-highlight-palette-styles';
        style.textContent = ''
            + '.omo-highlight-palette{position:fixed;z-index:100000;display:grid;grid-template-columns:repeat(3,auto);gap:6px;padding:8px;border:1px solid var(--color-border,#d1d5db);border-radius:var(--radius-md,8px);background:var(--color-surface,#fff);box-shadow:0 12px 28px rgba(15,23,42,.18);}'
            + '.omo-highlight-palette__color{display:grid;justify-items:center;gap:3px;min-width:48px;padding:4px;border:0;border-radius:6px;background:transparent;color:var(--color-text,#1f2937);font:inherit;font-size:10px;cursor:pointer;}'
            + '.omo-highlight-palette__color:hover,.omo-highlight-palette__color:focus-visible{background:var(--color-surface-alt,#f1f5f9);outline:none;}'
            + '.omo-highlight-palette__swatch{display:block;width:24px;height:20px;border:1px solid rgba(15,23,42,.18);border-radius:4px;box-shadow:inset 0 1px 1px rgba(255,255,255,.45);}'
            + '@media (max-width:420px){.omo-highlight-palette{grid-template-columns:repeat(3,1fr);}.omo-highlight-palette__color{min-width:42px;}}';
        document.head.appendChild(style);
    }

    function close() {
        if (!activePalette) {
            return;
        }

        document.removeEventListener('mousedown', activePalette.onDocumentMouseDown, true);
        document.removeEventListener('keydown', activePalette.onKeyDown, true);
        window.removeEventListener('resize', activePalette.reposition);
        window.removeEventListener('scroll', activePalette.reposition, true);
        if (activePalette.node && activePalette.node.parentNode) {
            activePalette.node.parentNode.removeChild(activePalette.node);
        }
        activePalette = null;
    }

    function open(options) {
        options = options || {};
        close();
        ensureStyles();

        var palette = document.createElement('div');
        palette.className = 'omo-highlight-palette';
        palette.setAttribute('role', 'menu');
        palette.setAttribute('aria-label', 'Couleur de surlignage');
        var colors = Array.isArray(options.colors) && options.colors.length ? options.colors : defaultColors;

        colors.forEach(function (color) {
            if (!color || !/^#[0-9a-f]{6}$/i.test(String(color.value || ''))) {
                return;
            }

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'omo-highlight-palette__color';
            button.setAttribute('role', 'menuitem');
            button.setAttribute('aria-label', 'Surligner en ' + String(color.name || 'couleur'));
            button.title = String(color.name || 'Couleur');

            var swatch = document.createElement('span');
            swatch.className = 'omo-highlight-palette__swatch';
            swatch.style.backgroundColor = String(color.value);
            swatch.setAttribute('aria-hidden', 'true');
            button.appendChild(swatch);

            var label = document.createElement('span');
            label.textContent = String(color.name || '');
            button.appendChild(label);

            button.addEventListener('click', function () {
                if (typeof options.onSelect === 'function') {
                    options.onSelect(String(color.value));
                }
                close();
            });
            palette.appendChild(button);
        });

        document.body.appendChild(palette);
        var anchor = options.anchor;
        if (anchor && anchor.jquery) {
            anchor = anchor[0];
        }

        var state = {
            node: palette,
            reposition: null,
            onDocumentMouseDown: null,
            onKeyDown: null
        };
        state.reposition = function () {
            var width = palette.offsetWidth || 190;
            var height = palette.offsetHeight || 90;
            var left = Math.max(8, (window.innerWidth - width) / 2);
            var top = Math.max(8, (window.innerHeight - height) / 2);
            if (anchor && typeof anchor.getBoundingClientRect === 'function') {
                var rectangle = anchor.getBoundingClientRect();
                left = Math.min(Math.max(8, rectangle.left), Math.max(8, window.innerWidth - width - 8));
                top = rectangle.bottom + 6;
                if (top + height > window.innerHeight - 8) {
                    top = rectangle.top - height - 6;
                }
                top = Math.max(8, top);
            }
            palette.style.left = Math.round(left) + 'px';
            palette.style.top = Math.round(top) + 'px';
        };
        state.onDocumentMouseDown = function (event) {
            if (!palette.contains(event.target) && (!anchor || !anchor.contains || !anchor.contains(event.target))) {
                close();
            }
        };
        state.onKeyDown = function (event) {
            if (event.key === 'Escape') {
                close();
            }
        };
        activePalette = state;
        state.reposition();
        document.addEventListener('mousedown', state.onDocumentMouseDown, true);
        document.addEventListener('keydown', state.onKeyDown, true);
        window.addEventListener('resize', state.reposition);
        window.addEventListener('scroll', state.reposition, true);
        var firstButton = palette.querySelector('button');
        if (firstButton) {
            firstButton.focus();
        }
    }

    window.omoHighlightPalette = {
        colors: defaultColors,
        open: open,
        close: close
    };
})(window, document);
