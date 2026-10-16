(function () {
    'use strict';
    if (window.commonChatReactions) return;
    var icons = {up: '/common/chat/assets/pouce-en-lair.png', down: '/common/chat/assets/pouce-en-lair.png',
        clap: '/common/chat/assets/applaudissements.png'};
    var fallback = {up: 'Pouce leve', down: 'Pouce baisse', clap: 'Applaudissements',
        choose: 'Reagir au message', remove: 'Retirer ma reaction', ownMessage: 'Vous ne pouvez pas reagir a votre propre message.',
        error: 'La reaction ne peut pas etre enregistree.'};
    var active = null;
    var translations;
    var nextPickerId = 0;

    function createIcon(kind, inPicker) {
        var icon = document.createElement('img');
        icon.src = icons[kind];
        icon.alt = '';
        icon.width = inPicker ? 20 : 16;
        icon.height = inPicker ? 20 : 16;
        icon.draggable = false;
        icon.className = 'chat-reactions__icon' + (inPicker ? ' black-icon' : '')
            + ' chat-reactions__icon--' + kind;
        return icon;
    }

    function closePicker(restoreFocus) {
        if (!active) return;
        var widget = active;
        active = null;
        widget.picker.hidden = true;
        widget.picker.remove();
        widget.trigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus) widget.trigger.focus();
    }
    document.addEventListener('click', function (event) {
        if (active && !active.bubble.contains(event.target) && !active.picker.contains(event.target)) closePicker(false);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && active) { event.preventDefault(); closePicker(true); }
    });
    window.addEventListener('common-topbar-modal-close', function () { closePicker(false); });
    window.addEventListener('resize', function () { closePicker(false); });
    document.addEventListener('scroll', function (event) {
        if (active && !active.picker.contains(event.target)) closePicker(false);
    }, true);
    document.addEventListener('focusin', function (event) {
        if (active && !active.bubble.contains(event.target) && !active.picker.contains(event.target)) closePicker(false);
    });

    function positionPicker(widget) {
        if (!widget.bubble.isConnected) return closePicker(false);
        var anchor = widget.trigger.getBoundingClientRect();
        var panel = widget.picker.getBoundingClientRect();
        var left = anchor.left + (anchor.width - panel.width) / 2;
        var top = anchor.top - panel.height - 6;
        if (top < 12) top = anchor.bottom + 6;
        widget.picker.style.left = Math.max(12, Math.min(left, window.innerWidth - panel.width - 12)) + 'px';
        widget.picker.style.top = Math.max(12, Math.min(top, window.innerHeight - panel.height - 12)) + 'px';
    }

    function bind(root, request, body) {
        var controller = {widgets: new Map(), labels: fallback, canReact: false, csrf: '', request: request, body: body};
        root.__chatReactions = controller;
        if (!translations) {
            translations = window.fetch('/common/jstranslation/chat_reactions.php').then(function (response) {
                return response.ok ? response.json() : {};
            }).catch(function () { return {}; });
        }
        translations.then(function (labels) {
            if (!controller.hasPayloadLabels) {
                controller.labels = Object.assign({}, fallback, labels);
                controller.widgets.forEach(render);
            }
        });
    }

    function canReact(widget) {
        return widget.controller.canReact && widget.state.canReact === true;
    }

    function render(widget) {
        var controller = widget.controller;
        var labels = controller.labels;
        widget.trigger.replaceChildren();
        Object.keys(icons).forEach(function (kind) {
            var count = Number(widget.state.counts[kind]) || 0;
            var item = document.createElement(widget.state.own === kind ? 'strong' : 'span');
            item.className = 'chat-reactions__item' + (count > 0 ? '' : ' chat-reactions__item--empty');
            item.dataset.reactionKind = kind;
            item.appendChild(createIcon(kind, false));
            if (count > 0) item.appendChild(document.createTextNode(String(count)));
            item.title = labels[kind];
            widget.trigger.appendChild(item);
        });
        widget.trigger.disabled = !canReact(widget) || widget.pending;
        widget.trigger.title = widget.state.canReact ? labels.choose : labels.ownMessage;
        widget.trigger.setAttribute('aria-label', widget.trigger.title + ': ' + Object.keys(icons).map(function (kind) {
            return labels[kind] + ' ' + (Number(widget.state.counts[kind]) || 0);
        }).join(', '));
        widget.picker.setAttribute('aria-label', labels.choose);
        widget.buttons.forEach(function (button) {
            var kind = button.dataset.reaction;
            var selected = widget.state.own === kind;
            button.title = labels[kind] + (selected ? ' - ' + labels.remove : '');
            button.setAttribute('aria-label', button.title);
            button.setAttribute('aria-pressed', String(selected));
            button.className = 'generic-action-button generic-action-button--compact generic-action-button--' + (selected ? 'main' : 'secondary');
            button.disabled = !canReact(widget) || widget.pending;
        });
        if (!canReact(widget) && active === widget) closePicker(false);
        if (active === widget) positionPicker(widget);
    }

    function update(root, payload) {
        var controller = root.__chatReactions;
        if (!controller) return;
        if (typeof payload.canReact === 'boolean') controller.canReact = payload.canReact;
        if (payload.reactionCsrf) controller.csrf = payload.reactionCsrf;
        if (payload.reactionLabels) {
            controller.labels = Object.assign({}, fallback, payload.reactionLabels);
            controller.hasPayloadLabels = true;
        }
        (payload.reactions || []).forEach(function (state) {
            var widget = controller.widgets.get(Number(state.id));
            if (widget && Number(state.version) >= Number(widget.state.version)) widget.state = state;
        });
        controller.widgets.forEach(render);
    }

    function attach(root, bubble, time, message) {
        var controller = root.__chatReactions;
        if (!controller) return;
        var footer = document.createElement('div');
        footer.className = 'chat-reactions__footer';
        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'generic-action-button generic-action-button--inline generic-action-button--no-lift chat-reactions__summary';
        trigger.setAttribute('aria-expanded', 'false');
        var picker = document.createElement('div');
        picker.className = 'generic-menu-panel generic-menu-panel--floating chat-reactions__picker';
        picker.id = 'chat-reactions-picker-' + (++nextPickerId);
        trigger.setAttribute('aria-controls', picker.id);
        picker.setAttribute('role', 'group');
        picker.hidden = true;
        var widget = {controller: controller, bubble: bubble, trigger: trigger, picker: picker, buttons: [], pending: false,
            state: {id: Number(message.id), counts: {}, own: '', version: -1, canReact: message.isOwn !== true}};
        Object.keys(icons).forEach(function (kind) {
            var button = document.createElement('button');
            button.type = 'button';
            button.dataset.reaction = kind;
            button.appendChild(createIcon(kind, true));
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                if (widget.pending || !canReact(widget)) return;
                var body = controller.body();
                body.append('action', 'reaction');
                body.append('message_id', String(message.id));
                body.append('reaction', widget.state.own === kind ? '' : kind);
                body.append('_csrf', controller.csrf);
                widget.pending = true;
                render(widget);
                controller.request(body).then(function (payload) {
                    update(root, payload);
                    if (active === widget) closePicker(true);
                }).catch(function (error) {
                    if (typeof window.commonNotify === 'function') window.commonNotify(error.message || controller.labels.error, 'error');
                }).finally(function () { widget.pending = false; render(widget); });
            });
            picker.appendChild(button);
            widget.buttons.push(button);
        });
        function toggle(fromKeyboard) {
            if (!canReact(widget) || widget.pending) return;
            var wasOpen = active === widget;
            closePicker(false);
            if (!wasOpen) {
                active = widget;
                document.body.appendChild(picker);
                picker.hidden = false;
                positionPicker(widget);
                trigger.setAttribute('aria-expanded', 'true');
                if (fromKeyboard) widget.buttons[0].focus({preventScroll: true});
            }
        }
        trigger.addEventListener('click', function (event) { event.stopPropagation(); toggle(event.detail === 0); });
        bubble.addEventListener('click', function (event) {
            if (event.target.closest('button, a, input, textarea, select, [contenteditable]')) return;
            if (window.getSelection && String(window.getSelection()) !== '') return;
            toggle(false);
        });
        footer.appendChild(trigger);
        footer.appendChild(time);
        bubble.appendChild(footer);
        controller.widgets.set(Number(message.id), widget);
        render(widget);
    }

    function appendQuery(root, query) {
        if (!root.__chatReactions) return;
        var widgets = root.__chatReactions.widgets;
        // Remove detached nodes after a full message refresh.
        widgets.forEach(function (widget, id) { if (!root.contains(widget.bubble)) widgets.delete(id); });
        var ids = Array.from(widgets.keys()).slice(-500);
        if (ids.length) query.append('reaction_ids', ids.join(','));
    }
    window.commonChatReactions = {bind: bind, attach: attach, update: update, appendQuery: appendQuery};
}());
