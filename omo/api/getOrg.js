
function omoCloseHolonMenus() {
    $('#panel-left [data-holon-menu="1"]').each(function () {
        $(this).removeClass('is-open');
        $(this).find('[data-holon-menu-panel="1"]').prop('hidden', true);
        $(this).find('[data-holon-menu-toggle="1"]').attr('aria-expanded', 'false');
    });
}

function omoIsTouchMemberLayout() {
    return window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse)').matches;
}

function omoSetMemberStackExpanded(stack, expanded) {
    const $stack = $(stack);
    if (!expanded && $stack.attr('data-member-compact') !== '1') {
        expanded = true;
    }

    $stack.toggleClass('is-expanded', expanded);
    $stack.attr('aria-expanded', expanded ? 'true' : 'false');
}

function omoUpdateMemberStack(stack) {
    const $stack = $(stack);
    const $members = $stack.children('.circle-member--regular');
    const $addButton = $stack.children('[data-circle-member-action="add"]');
    const $moreButton = $stack.find('[data-circle-member-action="more"]').first();

    if (!$members.length) {
        return;
    }

    const memberSize = parseFloat(window.getComputedStyle($members[0]).width) || 34;
    const memberGap = parseFloat(window.getComputedStyle($stack[0]).columnGap) || 8;
    const columnCount = Math.max(1, Math.floor(($stack.innerWidth() + memberGap) / (memberSize + memberGap)));
    const oneRowCapacity = columnCount;
    const previewCapacity = columnCount * 3;
    const memberCount = $members.length;
    const hasAddButton = $addButton.length > 0;
    const requiresCompaction = memberCount + (hasAddButton ? 1 : 0) > oneRowCapacity;
    const memberCapacity = hasAddButton
        ? Math.max(0, previewCapacity - 1)
        : previewCapacity;
    const hasHiddenMembers = requiresCompaction && memberCount > memberCapacity;
    const visibleMemberCount = hasHiddenMembers
        ? Math.max(0, previewCapacity - 1)
        : memberCount;
    const compactItemCount = visibleMemberCount + (hasHiddenMembers || hasAddButton ? 1 : 0);
    const compactMaxSpread = 14;
    const compactSpread = compactItemCount > 1
        ? Math.min(
            compactMaxSpread,
            Math.max(0, $stack.innerWidth() - memberSize) / (compactItemCount - 1)
        )
        : 0;

    $stack.css('--circle-member-columns', columnCount);
    $stack.attr('data-member-capacity', previewCapacity);
    $stack.attr('data-member-compact', requiresCompaction ? '1' : '0');
    $members.each(function (index) {
        $(this)
            .css('--circle-member-index', index)
            .css('--circle-member-compact-left', (Math.max(0, Math.min(index, visibleMemberCount - 1)) * compactSpread) + 'px')
            .toggleClass('circle-member--out-of-preview', index >= visibleMemberCount);
    });

    $addButton.prop('hidden', !hasAddButton || hasHiddenMembers);
    $moreButton.prop('hidden', !hasHiddenMembers);
    $addButton.css('--circle-member-action-left', (visibleMemberCount * compactSpread) + 'px');
    $moreButton.css('--circle-member-action-left', (visibleMemberCount * compactSpread) + 'px');

    if (!requiresCompaction) {
        omoSetMemberStackExpanded(stack, true);
    } else if (!omoIsTouchMemberLayout()) {
        omoSetMemberStackExpanded(stack, false);
    }
}

function omoPrepareMemberStacks() {
    $('#panel-left .circle-members__list').each(function () {
        const $list = $(this);
        const $regularMembers = $list.children('.circle-member--regular');

        if (!$regularMembers.length || $list.children('[data-circle-member-stack="1"]').length) {
            return;
        }

        const stack = $('<div class="circle-member-stack" data-circle-member-stack="1" tabindex="0" role="group" aria-expanded="false"></div>')[0];
        $regularMembers.first()[0].parentNode.insertBefore(stack, $regularMembers.first()[0]);
        $regularMembers.each(function () {
            stack.appendChild(this);
        });

        $list.children('[data-circle-member-action]').each(function () {
            stack.appendChild(this);
        });
    });

    $('#panel-left [data-circle-member-stack="1"]').each(function () {
        const stack = this;
        omoUpdateMemberStack(stack);

        if (typeof ResizeObserver === 'function' && !stack._omoMemberResizeObserver) {
            stack._omoMemberResizeObserver = new ResizeObserver(function () {
                omoUpdateMemberStack(stack);
            });
            stack._omoMemberResizeObserver.observe(stack);
        }
    });
}

omoPrepareMemberStacks();
window.requestAnimationFrame(omoPrepareMemberStacks);

$(document)
  .off('click.omoOrgMemberStack', '#panel-left [data-circle-member-stack="1"]')
  .on('click.omoOrgMemberStack', '#panel-left [data-circle-member-stack="1"]', function (event) {
    if (!omoIsTouchMemberLayout() || $(this).attr('data-member-compact') !== '1') {
        return;
    }

    const $memberTarget = $(event.target).closest('.circle-member[data-circle-member-item="1"]');
    if ($memberTarget.length) {
        if (!$(this).hasClass('is-expanded')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            omoSetMemberStackExpanded(this, true);
        }
        return;
    }

    event.preventDefault();
    omoSetMemberStackExpanded(this, !$(this).hasClass('is-expanded'));
  });

$(document)
  .off('keydown.omoOrgMemberStack', '#panel-left [data-circle-member-stack="1"]')
  .on('keydown.omoOrgMemberStack', '#panel-left [data-circle-member-stack="1"]', function (event) {
    if ($(this).attr('data-member-compact') !== '1' || (event.key !== 'Enter' && event.key !== ' ')) {
        return;
    }

    event.preventDefault();
    omoSetMemberStackExpanded(this, !$(this).hasClass('is-expanded'));
  });

$(document)
  .off('click.omoOrgMemberStackOutside')
  .on('click.omoOrgMemberStackOutside', function (event) {
    if (!omoIsTouchMemberLayout() || $(event.target).closest('[data-circle-member-stack="1"]').length) {
        return;
    }

    $('#panel-left [data-circle-member-stack="1"].is-expanded').each(function () {
        omoSetMemberStackExpanded(this, false);
    });
  });

window.dispatchEvent(new CustomEvent('omo-structure-member-highlight', {
    detail: {
        userId: null
    }
}));

$(document)
  .off('click.omoOrgSection', '#panel-left .generic-accordion__header')
  .on('click.omoOrgSection', '#panel-left .generic-accordion__header', function () {
    const section = $(this).closest('.generic-accordion--collapsible');
    const key = String(section.data('section-key') || omoNormalizeSectionKey(section.find('.generic-accordion__title').first().text()));

    section.toggleClass('is-collapsed');
    localStorage.setItem('section_' + key, section.hasClass('is-collapsed'));
  });

$(document)
  .off('click.omoOrgCrumb', '#panel-left .crumb[data-cid]')
  .on('click.omoOrgCrumb', '#panel-left .crumb[data-cid]', function () {
    const cid = Number($(this).data('cid'));
    const isRoot = String($(this).data('is-root')) === '1';

    if (!cid || typeof navigate !== 'function' || typeof parseUrl !== 'function') {
        return;
    }

    const route = parseUrl();
    navigate(route.oid, isRoot ? null : cid, route.hash || null);
  });

$(document)
  .off('click.omoOrgChildNav', '#panel-left .child-nav-item[data-cid]')
  .on('click.omoOrgChildNav', '#panel-left .child-nav-item[data-cid]', function () {
    const cid = Number($(this).data('cid'));

    if (!cid || typeof navigate !== 'function' || typeof parseUrl !== 'function') {
        return;
    }

    const route = parseUrl();
    navigate(route.oid, cid, route.hash || null);
  });

$(document)
  .off('mouseenter.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]')
  .on('mouseenter.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]', function () {
    const userId = Number($(this).data('member-user-id'));

    window.dispatchEvent(new CustomEvent('omo-structure-member-highlight', {
        detail: {
            userId: userId > 0 ? userId : null
        }
    }));
  });

$(document)
  .off('mouseleave.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]')
  .on('mouseleave.omoOrgMemberHighlight', '#panel-left .circle-member[data-member-user-id]', function () {
    window.dispatchEvent(new CustomEvent('omo-structure-member-highlight', {
        detail: {
            userId: null
        }
    }));
  });

$(document)
  .off('click.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]')
  .on('click.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]', function (event) {
    const $stack = $(this).closest('[data-circle-member-stack="1"]');

    if (omoIsTouchMemberLayout() && $stack.length && !$stack.hasClass('is-expanded')) {
        event.preventDefault();
        event.stopImmediatePropagation();
        omoSetMemberStackExpanded($stack[0], true);
        return;
    }

    const userId = Number($(this).data('member-user-id'));

    if (typeof window.omoOpenUserContextPopup !== 'function') {
        return;
    }

    window.omoOpenUserContextPopup(userId);
  });

$(document)
  .off('keydown.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]')
  .on('keydown.omoOrgMemberContext', '#panel-left .circle-member[data-open-user-context="1"]', function (event) {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    event.preventDefault();
    $(this).trigger('click');
  });

$(document)
  .off('click.omoOrgHolonMenu', '#panel-left [data-holon-menu-toggle="1"]')
  .on('click.omoOrgHolonMenu', '#panel-left [data-holon-menu-toggle="1"]', function (event) {
    event.stopPropagation();

    const menu = $(this).closest('[data-holon-menu="1"]');
    const willOpen = !menu.hasClass('is-open');
    omoCloseHolonMenus();

    if (!willOpen) {
        return;
    }

    menu.addClass('is-open');
    menu.find('[data-holon-menu-panel="1"]').prop('hidden', false);
    menu.find('[data-holon-menu-toggle="1"]').attr('aria-expanded', 'true');
  });

$(document)
  .off('click.omoOrgHolonMenuItem', '#panel-left [data-holon-menu-panel="1"] button')
  .on('click.omoOrgHolonMenuItem', '#panel-left [data-holon-menu-panel="1"] button', function () {
    omoCloseHolonMenus();
  });

$(document)
  .off('click.omoOrgHolonMenuOutside')
  .on('click.omoOrgHolonMenuOutside', function (event) {
    if ($(event.target).closest('#panel-left [data-holon-menu="1"]').length) {
        return;
    }

    omoCloseHolonMenus();
  });

$(document)
  .off('click.omoOrgCreateHolon', '#panel-left [data-open-create-holon="1"]')
  .on('click.omoOrgCreateHolon', '#panel-left [data-open-create-holon="1"]', function () {
    const cid = Number($(this).data('cid'));

    if (!cid) {
        return;
    }

    if (typeof window.omoOpenExternalRouteDrawer === 'function' && window.omoOpenExternalRouteDrawer('holon-create-' + cid, {
        title: 'Ajouter'
    })) {
        return;
    }

    if (typeof window.omoOpenDrawerHashState === 'function') {
        window.omoOpenDrawerHashState('holon-create-' + cid);
    }
  });

$(document)
  .off('click.omoOrgToggleOrganizationModel', '#panel-left [data-toggle-organization-model="1"]')
  .on('click.omoOrgToggleOrganizationModel', '#panel-left [data-toggle-organization-model="1"]', function () {
    const button = $(this);
    const organizationId = Number(button.data('oid'));
    if (!organizationId) {
        return;
    }
    button.prop('disabled', true);
    const data = new FormData();
    data.append('oid', String(organizationId));
    data.append('action', 'toggle-model');
    fetch('/omo/api/organizations/card_action.php', { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (!result || !result.status) {
            throw new Error(result && result.message ? result.message : 'Action impossible.');
        }
        if (typeof window.omoLoadLeft === 'function') {
            window.omoLoadLeft();
        } else {
            window.location.reload();
        }
      })
      .catch(function (error) {
        button.prop('disabled', false);
        window.alert(error && error.message ? error.message : 'Action impossible.');
      });
  });

$(document)
  .off('click.omoOrgEditHolon', '#panel-left [data-open-edit-holon="1"]')
  .on('click.omoOrgEditHolon', '#panel-left [data-open-edit-holon="1"]', function () {
    const button = $(this);
    const hid = Number(button.data('hid'));
    const isTemplateEdit = String(button.data('template-edit')) === '1';
    const isDefinitionEdit = String(button.data('definition-edit')) === '1';
    const templateContextId = Number(button.data('template-context-id') || 0);

    if (!hid) {
        return;
    }

    let routeToken = 'holon-edit-' + hid;
    if ((isTemplateEdit || isDefinitionEdit) && templateContextId > 0) {
        routeToken = 'holon-template-edit-' + templateContextId + '-' + hid;
    }

    if (typeof window.omoOpenExternalRouteDrawer === 'function' && window.omoOpenExternalRouteDrawer(routeToken, {
        title: 'Modifier'
    })) {
        return;
    }

    if (typeof window.omoOpenDrawerHashState === 'function') {
        window.omoOpenDrawerHashState(routeToken);
    }
  });

$(document)
  .off('click.omoOrgMoveHolon', '#panel-left [data-open-move-holon="1"]')
  .on('click.omoOrgMoveHolon', '#panel-left [data-open-move-holon="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.omoOpenPopupHashState !== 'function') {
        return;
    }

    window.omoOpenPopupHashState('holon-move', hid);
  });

$(document)
  .off('click.omoOrgOpenTeamDrawer', '#panel-left [data-open-team-drawer="1"]')
  .on('click.omoOrgOpenTeamDrawer', '#panel-left [data-open-team-drawer="1"]', function () {
    if (typeof openDrawer !== 'function') {
        return;
    }

    const route = typeof parseUrl === 'function' ? parseUrl() : { oid: null, cid: null };
    const targetCid = Number($(this).data('cid') || route.cid || 0);
    let drawerUrl = '/omo/api/team/index.php';

    if (route && route.oid) {
        drawerUrl += '?oid=' + encodeURIComponent(route.oid);
        if (targetCid > 0) {
            drawerUrl += '&cid=' + encodeURIComponent(targetCid);
        }
    } else if (targetCid > 0) {
        drawerUrl += '?cid=' + encodeURIComponent(targetCid);
    }

    openDrawer('drawer_team', drawerUrl);
  });

$(document)
  .off('click.omoOrgOpenMemberPopup', '#panel-left [data-open-member-popup="1"]')
  .on('click.omoOrgOpenMemberPopup', '#panel-left [data-open-member-popup="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    window.commonTopbarOpenModal(
        window.omoOrganizationPageConfig.leftbarMembersAdd,
        'api/holons/member_popup.php?hid=' + hid,
        'fetch'
    );
  });

$(document)
  .off('click.omoOrgOpenHolonHistory', '#panel-left [data-open-holon-history="1"]')
  .on('click.omoOrgOpenHolonHistory', '#panel-left [data-open-holon-history="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.commonTopbarOpenModal !== 'function') {
        return;
    }

    window.commonTopbarOpenModal(
        window.omoOrganizationPageConfig.leftbarActionsHistory,
        'api/holons/history_popup.php?hid=' + hid,
        'fetch'
    );
  });

$(document)
  .off('click.omoOrgDeleteHolon', '#panel-left [data-delete-holon="1"]')
  .on('click.omoOrgDeleteHolon', '#panel-left [data-delete-holon="1"]', function () {
    const hid = Number($(this).data('hid'));

    if (!hid || typeof window.omoOpenPopupHashState !== 'function') {
        return;
    }

    window.omoOpenPopupHashState('holon-delete', hid);
  });

function omoNormalizeSectionKey(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, '_');
}

function omoBuildDirectHolonUrl(cid) {
    const rootId = window.omoOrganizationPageConfig.rootId;
    const route = typeof parseUrl === 'function'
        ? parseUrl()
        : { oid: window.omoOrganizationPageConfig.oid };
    const targetCid = Number(cid) === Number(rootId) ? null : cid;

    if (typeof buildOmoUrl === 'function') {
        return buildOmoUrl(route.oid, targetCid, null, { absolute: true });
    }

    if (targetCid) {
        return `${window.location.origin}/omo/c/${targetCid}`;
    }

    return `${window.location.origin}/omo/`;
}

(function restoreSections() {
    $('#panel-left .generic-accordion--collapsible').each(function () {
        const key = String($(this).data('section-key') || omoNormalizeSectionKey($(this).find('.generic-accordion__title').first().text()));
        const saved = localStorage.getItem('section_' + key);

        if (saved === 'true') {
            $(this).addClass('is-collapsed');
            return;
        }

        if (saved === null && key === 'dependencies') {
            $(this).addClass('is-collapsed');
        }
    });
})();

$(document)
  .off('click.omoOrgCopyDirectLink', '#panel-left [data-copy-direct-link="1"]')
  .on('click.omoOrgCopyDirectLink', '#panel-left [data-copy-direct-link="1"]', async function () {
    const button = this;
    const cid = Number($(button).data('cid'));
    const url = omoBuildDirectHolonUrl(cid);

    if (!url) {
        return;
    }

    try {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            await navigator.clipboard.writeText(url);
        } else {
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
        }

        const originalText = button.textContent;
        $(button).addClass('copied');
        button.textContent = window.omoOrganizationPageConfig.leftbarCopyLinkSuccess;

        window.setTimeout(function () {
            button.textContent = originalText;
            $(button).removeClass('copied');
        }, 1200);
    } catch (error) {
        console.error(window.omoOrganizationPageConfig.leftbarCopyLinkError, error);
    }
  });

function omoToggleProjectReferenceChildren(card, toggle) {
    const projectId = Number($(card).data('project-id'));
    const host = $(card).children('[data-omo-project-reference-children]').first();
    if (!projectId || !host.length) {
        return;
    }

    if (host.data('loaded') === 1) {
        const nextHidden = !host.prop('hidden');
        host.prop('hidden', nextHidden);
        if (toggle) {
            toggle.setAttribute('aria-expanded', nextHidden ? 'false' : 'true');
        }
        return;
    }

    host.prop('hidden', false).html(("<div class=\"section-project-reference__children-loading\">" + window.omoOrganizationPageConfig.leftbarProjectChildrenLoading + "</div>"));
    if (toggle) {
        toggle.disabled = true;
    }
    $.ajax({
        url: '/omo/api/projects/children.php?id=' + encodeURIComponent(projectId),
        method: 'GET',
        cache: false,
        dataType: 'html'
    }).done(function (html) {
        host.data('loaded', 1).html(html || ("<div class=\"section-project-reference__children-empty\">" + window.omoOrganizationPageConfig.leftbarProjectChildrenEmpty + "</div>"));
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    }).fail(function () {
        host.html(("<div class=\"section-project-reference__children-error\">" + window.omoOrganizationPageConfig.leftbarProjectChildrenError + "</div>"));
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }).always(function () {
        if (toggle) {
            toggle.disabled = false;
        }
    });
}

$(document).off('click.omoOrgProjectReference', '#panel-left [data-omo-project-reference]');

$(document)
  .off('click.omoOrgProjectReferenceTitle', '#panel-left [data-omo-project-reference-title]')
  .on('click.omoOrgProjectReferenceTitle', '#panel-left [data-omo-project-reference-title]', function (event) {
    const href = String($(this).attr('href') || '');
    const routeMatch = href.match(/^#(projects-d\d+)$/i);

    if (routeMatch && typeof window.omoOpenDrawerHashState === 'function') {
        event.preventDefault();
        event.stopPropagation();
        window.omoOpenDrawerHashState(routeMatch[1]);
        return;
    }

    event.stopPropagation();
  });

window.omoToggleProjectReferenceChildren = omoToggleProjectReferenceChildren;
if (!window.omoOrgProjectReferenceCaptureBound) {
    window.omoOrgProjectReferenceCaptureBound = true;
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('#panel-left [data-omo-project-reference-toggle]');
        if (!toggle) {
            return;
        }
        const card = toggle.closest('[data-omo-project-reference]');
        if (!card) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        if (typeof window.omoToggleProjectReferenceChildren === 'function') {
            window.omoToggleProjectReferenceChildren(card, toggle);
        }
    }, true);
}


