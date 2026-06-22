(function () {
    if (window.commonChoiceDecisionCards && typeof window.commonChoiceDecisionCards.renderCard === 'function') {
        return;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resolveDataAttribute(value, fallback) {
        var normalized = String(value || '').trim();
        if (normalized === '' || !/^data-[a-z0-9_-]+$/.test(normalized)) {
            return fallback;
        }

        return normalized;
    }

    function buildMetaItem(metaItem) {
        if (!metaItem || typeof metaItem !== 'object') {
            return '';
        }

        var label = String(metaItem.label || '').trim();
        var value = String(metaItem.value || '').trim();
        if (label === '' || value === '') {
            return '';
        }

        return '<span class="omo-decisions-card__meta-item"><strong>' + escapeHtml(label) + '</strong><span>' + escapeHtml(value) + '</span></span>';
    }

    function buildStat(stat) {
        if (!stat || typeof stat !== 'object') {
            return '';
        }

        var label = String(stat.label || '').trim();
        var value = String(stat.value || '').trim();
        if (label === '' || value === '') {
            return '';
        }

        return '<span class="omo-decisions-card__stat"><strong>' + escapeHtml(value) + '</strong><span>' + escapeHtml(label) + '</span></span>';
    }

    function buildDateItem(dateItem) {
        if (!dateItem || typeof dateItem !== 'object') {
            return '';
        }

        var label = String(dateItem.label || '').trim();
        var value = String(dateItem.value || '').trim();
        if (label === '' || value === '') {
            return '';
        }

        return '<span class="omo-decisions-card__date"><strong>' + escapeHtml(label) + '</strong><span>' + escapeHtml(value) + '</span></span>';
    }

    function renderCard(item, options) {
        var config = options && typeof options === 'object' ? options : {};
        var fallbackTitle = String(config.fallbackTitle || 'Decision').trim() || 'Decision';
        var openUrlAttribute = resolveDataAttribute(config.openUrlAttribute, 'data-open-url');
        var openTitleAttribute = resolveDataAttribute(config.openTitleAttribute, 'data-open-title');
        var article = document.createElement('article');
        var decision = item && typeof item === 'object' ? item : {};
        var title = String(decision.title || '').trim() || fallbackTitle;
        var description = String(decision.description || '').trim();
        var statusLabel = String(decision.statusLabel || '').trim();
        var owner = decision.owner && typeof decision.owner === 'object' ? decision.owner : {};
        var ownerName = String(owner.displayName || '').trim();
        var ownerInitials = String(owner.initials || 'P').trim() || 'P';
        var ownerPhotoUrl = String(owner.photoUrl || '').trim();
        var badges = Array.isArray(decision.badges) ? decision.badges : [];
        var actions = Array.isArray(decision.actions) ? decision.actions : [];
        var metaItems = Array.isArray(decision.metaItems) ? decision.metaItems : [];
        var stats = Array.isArray(decision.stats) ? decision.stats : [];
        var dateItems = Array.isArray(decision.dateItems) ? decision.dateItems : [];
        var badgesHtml = '';
        var actionsHtml = '<div class="omo-decisions-card__actions">';
        var metaHtml = '';
        var statsHtml = '';
        var dateHtml = '';
        var ownerAvatarHtml = '';

        article.className = 'omo-decisions-card generic-section generic-accordion generic-accordion--card generic-accordion--collapsible is-collapsed';
        article.setAttribute('data-generic-accordion', '1');

        if (badges.length > 0) {
            badgesHtml = '<span class="omo-decisions-card__badges">';
            badges.forEach(function (badge) {
                var normalizedBadge = String(badge || '').trim();
                if (normalizedBadge === '') {
                    return;
                }
                badgesHtml += '<span class="omo-decisions-card__badge">' + escapeHtml(normalizedBadge) + '</span>';
            });
            badgesHtml += '</span>';
        }

        if (ownerPhotoUrl !== '') {
            ownerAvatarHtml = '<img src="' + escapeHtml(ownerPhotoUrl) + '" alt="' + escapeHtml(ownerName !== '' ? ownerName : ownerInitials) + '" class="omo-decisions-card__owner-photo">';
        } else {
            ownerAvatarHtml = '<span class="omo-decisions-card__owner-placeholder">' + escapeHtml(ownerInitials) + '</span>';
        }

        actions.forEach(function (action) {
            if (!action || typeof action !== 'object') {
                return;
            }

            var label = String(action.label || '').trim();
            var url = String(action.url || '').trim();
            if (label === '' || url === '') {
                return;
            }

            var variant = String(action.variant || 'secondary').trim() || 'secondary';
            var buttonTitle = String(action.title || title).trim() || title;

            actionsHtml += '<button type="button" class="generic-action-button generic-action-button--' + escapeHtml(variant) + ' omo-decisions-card__action" '
                + openUrlAttribute + '="' + escapeHtml(url) + '" '
                + openTitleAttribute + '="' + escapeHtml(buttonTitle) + '">'
                + escapeHtml(label)
                + '</button>';
        });
        actionsHtml += '</div>';

        metaItems.forEach(function (metaItem) {
            metaHtml += buildMetaItem(metaItem);
        });
        stats.forEach(function (stat) {
            statsHtml += buildStat(stat);
        });
        dateItems.forEach(function (dateItem) {
            dateHtml += buildDateItem(dateItem);
        });

        article.innerHTML = '<div class="omo-decisions-card__header generic-accordion__header">'
            + '<button type="button" class="omo-decisions-card__summary" data-generic-accordion-toggle aria-expanded="false">'
                + '<span class="omo-decisions-card__owner-avatar">' + ownerAvatarHtml + '</span>'
                + '<span class="omo-decisions-card__summary-copy">'
                    + '<span class="omo-decisions-card__summary-top">'
                        + '<span class="omo-decisions-card__title">' + escapeHtml(title) + '</span>'
                        + (statusLabel !== '' ? '<span class="omo-decisions-card__status">' + escapeHtml(statusLabel) + '</span>' : '')
                    + '</span>'
                    + '<span class="omo-decisions-card__summary-bottom">'
                        + (ownerName !== '' ? '<span class="omo-decisions-card__owner-name">' + escapeHtml(ownerName) + '</span>' : '')
                        + badgesHtml
                    + '</span>'
                + '</span>'
                + '<span class="generic-accordion__toggle" aria-hidden="true">&#9662;</span>'
            + '</button>'
            + actionsHtml
            + '</div>'
            + '<div class="omo-decisions-card__content generic-accordion__content">'
                + (description !== '' ? '<p class="omo-decisions-card__description">' + escapeHtml(description) + '</p>' : '')
                + (metaHtml !== '' ? '<div class="omo-decisions-card__meta">' + metaHtml + '</div>' : '')
                + (statsHtml !== '' ? '<div class="omo-decisions-card__stats">' + statsHtml + '</div>' : '')
                + (dateHtml !== '' ? '<div class="omo-decisions-card__dates">' + dateHtml + '</div>' : '')
            + '</div>';

        return article;
    }

    window.commonChoiceDecisionCards = {
        escapeHtml: escapeHtml,
        renderCard: renderCard
    };
}());
