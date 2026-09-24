(function () {
	if (typeof window.__omoPopupCleanup === 'function') {
		window.__omoPopupCleanup();
	} else if (typeof window.__omoFaqPopupCleanup === 'function') {
		window.__omoFaqPopupCleanup();
	}

	const root = document.getElementById('faqPopupRoot');
	if (!root) {
		return;
	}

	const detailView = root.querySelector('[data-faq-detail-view]');
	const editorView = root.querySelector('[data-faq-editor-view]');
	const requestView = root.querySelector('[data-faq-request-view]');
	const askShell = root.querySelector('[data-faq-ask-shell]');
	const searchInput = root.querySelector('[data-faq-search-input]');
	const helper = root.querySelector('[data-faq-helper]');
	const noResult = root.querySelector('[data-faq-no-result]');
	const list = root.querySelector('[data-faq-list]');
	const loadMoreShell = root.querySelector('[data-faq-load-more-shell]');
	const loadMoreButton = root.querySelector('[data-faq-load-more]');
	const modalBody = document.getElementById('commonTopbarModalBody');
	const modalTitle = modalBody && modalBody.contains(root) ? document.getElementById('commonTopbarModalTitle') : null;
	const listTitle = modalTitle ? modalTitle.textContent : '';
	let appliedTitle = null;
	const parsedDefaultVisibleLimit = Number(root.getAttribute('data-faq-default-visible') || 5);
	const defaultVisibleLimit = parsedDefaultVisibleLimit > 0 ? parsedDefaultVisibleLimit : Number.MAX_SAFE_INTEGER;
	const currentOid = Number(root.getAttribute('data-faq-oid') || 0);
	const currentCid = Number(root.getAttribute('data-faq-cid') || 0);
	const currentScope = normalizeFaqScope(root.getAttribute('data-faq-scope') || 'contextual');
	const reloadUrl = root.getAttribute('data-faq-reload-url') || '/popup/faq.php';
	let currentViewToken = null;
	let refreshRequestId = 0;
	let detailRequestId = 0;
	let currentVisibleLimit = defaultVisibleLimit;

	if (modalBody) {
		modalBody.setAttribute('data-omo-faq-modal', '1');
		modalBody.setAttribute('data-omo-popup-key', 'faq');
		modalBody.setAttribute('data-omo-popup-url', reloadUrl);
		modalBody.setAttribute('data-omo-popup-live-sync', '1');
	}

	function normalize(value) {
		return String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '');
	}

	function escapeRegExp(value) {
		return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function normalizeFaqScope(value) {
		const normalizedScope = String(value || '').trim().toLowerCase();
		if (normalizedScope === 'global') {
			return 'descendants';
		}
		if (normalizedScope === 'children' || normalizedScope === 'descendants') {
			return normalizedScope;
		}

		return 'contextual';
	}

	function buildAccentInsensitivePattern(word) {
		const accentMap = {
			a: '[a\\u00E0\\u00E1\\u00E2\\u00E3\\u00E4\\u00E5]',
			c: '[c\\u00E7]',
			e: '[e\\u00E8\\u00E9\\u00EA\\u00EB]',
			i: '[i\\u00EC\\u00ED\\u00EE\\u00EF]',
			n: '[n\\u00F1]',
			o: '[o\\u00F2\\u00F3\\u00F4\\u00F5\\u00F6\\u00F8]',
			u: '[u\\u00F9\\u00FA\\u00FB\\u00FC]',
			y: '[y\\u00FF\\u00FD]'
		};

		return word
			.split('')
			.map(function (char) {
				return accentMap[char] || escapeRegExp(char);
			})
			.join('');
	}

	function buildFaqQuery(id, extraParams) {
		const query = ['id=' + encodeURIComponent(id)];
		if (currentOid > 0) {
			query.push('oid=' + encodeURIComponent(currentOid));
		}
		if (currentCid > 0) {
			query.push('cid=' + encodeURIComponent(currentCid));
		}
		if (currentScope !== 'contextual') {
			query.push('faq_scope=' + encodeURIComponent(currentScope));
		}
		if (extraParams && typeof extraParams === 'object') {
			Object.keys(extraParams).forEach(function (key) {
				const value = extraParams[key];
				if (value === null || value === undefined || value === '') {
					return;
				}
				query.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
			});
		}
		return query.join('&');
	}

	function buildPopupUrlForScope(scope) {
		const resolvedScope = normalizeFaqScope(scope);
		const query = [];

		if (currentOid > 0) {
			query.push('oid=' + encodeURIComponent(currentOid));
		}
		if (currentCid > 0) {
			query.push('cid=' + encodeURIComponent(currentCid));
		}
		if (resolvedScope !== 'contextual') {
			query.push('faq_scope=' + encodeURIComponent(resolvedScope));
		}

		return '/popup/faq.php' + (query.length > 0 ? '?' + query.join('&') : '');
	}


	function initFaqRichTextFields(container) {
		if (!container || typeof window.adminEditInitHtmlFields !== 'function') {
			return Promise.resolve();
		}

		try {
			return Promise.resolve(window.adminEditInitHtmlFields(container));
		} catch (error) {
			return Promise.resolve();
		}
	}

	function destroyFaqRichTextFields(container) {
		if (!container) {
			return;
		}

		if (typeof window.adminEditDestroyHtmlFields === 'function') {
			try {
				window.adminEditDestroyHtmlFields(container);
				return;
			} catch (error) {
			}
		}

		if (!window.jQuery || !window.jQuery.fn) {
			return;
		}

		window.jQuery(container).find('textarea.summernote').each(function () {
			const field = window.jQuery(this);
			if (typeof field.summernote === 'function' && (field.data('adminEditSummernoteBound') === true || field.next('.note-editor').length > 0)) {
				try {
					field.val(field.summernote('code'));
					field.summernote('destroy');
				} catch (error) {
				}
			}
			field.removeData('adminEditSummernoteBound');
		});
	}

	function clearFaqView(container) {
		if (!container) {
			return;
		}

		destroyFaqRichTextFields(container);
		container.innerHTML = '';
	}

	function setLoadingState(isLoading, nextScope) {
		const activeRoot = document.getElementById('faqPopupRoot');
		if (!activeRoot) {
			return;
		}

		activeRoot.classList.toggle('is-loading', !!isLoading);
		activeRoot.setAttribute('aria-busy', isLoading ? 'true' : 'false');

		const scopeSwitch = activeRoot.querySelector('[data-faq-scope-switch]');
		if (scopeSwitch && nextScope) {
			const normalizedScope = normalizeFaqScope(nextScope);
			scopeSwitch.setAttribute(
				'data-faq-scope-switch',
				normalizedScope
			);
		}

		activeRoot.querySelectorAll('[data-faq-scope-toggle]').forEach(function (button) {
			button.disabled = !!isLoading;
		});
	}

	function refreshPopupContent(url, options) {
		const config = options || {};
		const targetScope = normalizeFaqScope(config.scope || '');
		const requestId = ++refreshRequestId;

		setLoadingState(true, config.scope ? targetScope : null);

		return fetch(url, {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('faq_popup_reload_failed');
				}
				return response.text();
			})
			.then(function (html) {
				if (requestId !== refreshRequestId) {
					return;
				}

				const temp = document.createElement('div');
				temp.innerHTML = html;
				const nextRoot = temp.querySelector('#faqPopupRoot');
				const activeRoot = document.getElementById('faqPopupRoot');

				if (!nextRoot || !activeRoot || !activeRoot.parentNode) {
					throw new Error('faq_popup_reload_invalid');
				}

				const fetchedScripts = Array.from(temp.querySelectorAll('script'));

				if (typeof window.__omoPopupCleanup === 'function') {
					window.__omoPopupCleanup();
				}

				Array.from(temp.querySelectorAll('link[rel~="stylesheet"]'))
					.filter(function (link) { return !nextRoot.contains(link); })
					.reverse()
					.forEach(function (link) { nextRoot.insertBefore(link, nextRoot.firstChild); });
				activeRoot.parentNode.replaceChild(nextRoot, activeRoot);
				return window.commonExecuteFragmentScripts(nextRoot, {
                    scripts: fetchedScripts, target: nextRoot,
                    isCurrent: function () { return nextRoot.isConnected && requestId === refreshRequestId; }
                });
			})
			.catch(function () {
				setLoadingState(false);
				window.alert('Impossible de recharger la FAQ pour le moment.');
			});
	}

	function resetHighlights(container) {
		container.querySelectorAll('[data-faq-answer-text], .faq-popup__question').forEach(function (node) {
			const original = node.getAttribute('data-original-text');
			if (original !== null) {
				node.innerHTML = original;
			}
		});
	}

	function ensureOriginalText(node) {
		if (!node.hasAttribute('data-original-text')) {
			node.setAttribute('data-original-text', node.innerHTML);
		}
	}

	function highlight(node, words) {
		ensureOriginalText(node);
		const html = node.getAttribute('data-original-text') || '';
		const filteredWords = words.filter(function (word) {
			return word.length >= 2;
		});

		if (filteredWords.length === 0) {
			node.innerHTML = html;
			return;
		}

		const pattern = filteredWords
			.map(buildAccentInsensitivePattern)
			.sort(function (a, b) {
				return b.length - a.length;
			})
			.join('|');
		const regex = new RegExp('(' + pattern + ')', 'gi');

		node.innerHTML = html.replace(regex, '<span class="faq-popup__highlight">$1</span>');
	}

	function getPopupHashState() {
		if (typeof window.omoParsePopupHashState === 'function') {
			const popupState = window.omoParsePopupHashState();

			return {
				popupToken: popupState.popupKey === 'faq' ? popupState.popupToken : null,
				popupId: popupState.popupKey === 'faq' ? popupState.popupId : null
			};
		}

		const normalizedHash = (window.location.hash || '').replace(/^#/, '').trim();
		const hashParts = normalizedHash === '' ? [] : normalizedHash.split('|');
		const popupToken = String(hashParts.length > 1 ? hashParts[1] : '').trim();
		const popupMatch = popupToken.match(/^faq(?:-(\d+))?$/i);

		return {
			popupToken: popupMatch ? (popupMatch[1] ? 'faq-' + Number(popupMatch[1]) : 'faq') : null,
			popupId: popupMatch && popupMatch[1] ? Number(popupMatch[1]) : null
		};
	}

	function showList(options) {
		syncFaqTitle(null);
		const config = options || {};
		detailRequestId++;
		currentViewToken = 'faq';
		root.classList.remove('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			clearFaqView(detailView);
		}
		if (editorView) {
			editorView.hidden = true;
		}
		if (requestView) {
			requestView.hidden = true;
		}

		if (config.updateHash !== false && typeof window.omoOpenPopupHashState === 'function') {
			window.omoOpenPopupHashState('faq', null);
		}
	}

	function showEditor() {
		if (!editorView) {
			return;
		}

		detailRequestId++;
		currentViewToken = 'faq-create';
		root.classList.add('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			clearFaqView(detailView);
		}
		if (requestView) {
			requestView.hidden = true;
		}
		editorView.hidden = false;
		syncFaqTitle(editorView);
		syncScopeSelectors(editorView);
		initFaqRichTextFields(editorView);
	}

	function showDetail(id, options) {
		const config = options || {};
		if (!detailView) {
			return;
		}

		const requestId = ++detailRequestId;
		currentViewToken = 'faq-' + Number(id);
		root.classList.add('faq-popup--detail-open');
		if (editorView) {
			editorView.hidden = true;
		}
		if (requestView) {
			requestView.hidden = true;
		}
		clearFaqView(detailView);
		detailView.hidden = false;
		syncFaqTitle(null);
		detailView.innerHTML = '<div class="faq-popup__helper">Chargement...</div>';

		if (config.updateHash !== false && typeof window.omoOpenPopupHashState === 'function') {
			window.omoOpenPopupHashState('faq', id);
		}

		const extraParams = {};
		if (config.edit === true || config.edit === 'auto') {
			extraParams.edit = config.edit === 'auto' ? 'auto' : '1';
		}

		fetch('/ajax/faq_detail.php?' + buildFaqQuery(id, extraParams), {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('faq_detail_load_failed');
				}
				return response.text();
			})
			.then(function (html) {
				if (requestId !== detailRequestId || !document.documentElement.contains(root)) {
					return;
				}

				clearFaqView(detailView);
				detailView.innerHTML = html;
				syncFaqTitle(detailView);
				syncScopeSelectors(detailView);
				initFaqRichTextFields(detailView);
			})
			.catch(function () {
				if (requestId !== detailRequestId || !document.documentElement.contains(root)) {
					return;
				}
				detailView.innerHTML = '<div class="faq-popup__no-result">Impossible de charger cette FAQ pour le moment.</div>';
			});
	}

	function showRequestForm() {
		if (!requestView) return;
		detailRequestId++;
		currentViewToken = 'faq-request';
		root.classList.add('faq-popup--detail-open');
		if (detailView) {
			detailView.hidden = true;
			clearFaqView(detailView);
		}
		if (editorView) editorView.hidden = true;
		requestView.hidden = false;
		syncFaqTitle(requestView);
		const form = requestView.querySelector('[data-faq-request-form]');
		if (form && form.dataset.submitting !== '1') {
			form.reset();
			form.querySelector('[data-faq-ai-notice]').hidden = true;
			form.querySelector('[data-faq-ai-draft]').hidden = true;
			form.querySelector('[data-faq-ai-draft-text]').replaceChildren();
			form.querySelector('[data-faq-request-message]').textContent = '';
		}
	}

	function syncFaqTitle(view) {
		if (!modalTitle) return;
		const heading = view ? view.querySelector('[data-faq-view-title]') : null;
		appliedTitle = heading ? heading.textContent.trim() : listTitle;
		modalTitle.textContent = appliedTitle;
		if (heading) heading.hidden = true;
	}

	function submitQuestionRequest(form) {
		if (form.dataset.submitting === '1') return;
		form.dataset.submitting = '1';
		const message = form.querySelector('[data-faq-request-message]');
		const draftNotice = form.querySelector('[data-faq-ai-notice]');
		const draftPanel = form.querySelector('[data-faq-ai-draft]');
		const draftText = form.querySelector('[data-faq-ai-draft-text]');
		if (draftNotice) draftNotice.hidden = true;
		if (draftPanel) draftPanel.hidden = true;
		if (draftText) draftText.replaceChildren();
		const submitButton = form.querySelector('[type="submit"]');
		if (submitButton) submitButton.disabled = true;
		if (message) message.textContent = 'Envoi en cours...';
		const requestParams = new URLSearchParams();
		if (currentOid > 0) requestParams.set('oid', String(currentOid));
		if (currentCid > 0) requestParams.set('cid', String(currentCid));
		fetch('/ajax/faq_question_request.php' + (requestParams.toString() ? '?' + requestParams.toString() : ''), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			body: new FormData(form)
		}).then(function (response) {
			return response.json().then(function (payload) {
				if (!response.ok || !payload.success) throw new Error(payload.message || 'Impossible d envoyer votre question.');
				return payload;
			});
		}).then(function (payload) {
			if (message) message.textContent = payload.message || 'Votre question a bien été envoyée.';
			if (draftPanel && draftText && typeof payload.ai_draft === 'string' && payload.ai_draft.trim()) {
				payload.ai_draft.trim().split(/\r?\n\s*\r?\n/).forEach(function (block) {
					const paragraph = document.createElement('p');
					block.split(/\r?\n/).forEach(function (line, index) {
						if (index > 0) paragraph.appendChild(document.createElement('br'));
						paragraph.appendChild(document.createTextNode(line));
					});
					draftText.appendChild(paragraph);
				});
				draftNotice.hidden = false;
				draftPanel.hidden = false;
			}
			form.reset();
		}).catch(function (error) {
			if (message) message.textContent = error.message || 'Impossible d envoyer votre question.';
		}).finally(function () {
			delete form.dataset.submitting;
			if (submitButton) submitButton.disabled = false;
		});
	}

	function syncFromHash() {
		const popupState = getPopupHashState();
		const targetToken = popupState.popupToken;

		if (!targetToken || targetToken === currentViewToken) {
			return;
		}

		if (popupState.popupId) {
			showDetail(popupState.popupId, { updateHash: false, edit: 'auto' });
			return;
		}

		showList({ updateHash: false });
	}

	function sortItemsByDefaultOrder(items) {
		return items.sort(function (a, b) {
			const orderA = Number(a.getAttribute('data-faq-default-order') || 0);
			const orderB = Number(b.getAttribute('data-faq-default-order') || 0);

			return orderA - orderB;
		});
	}

	function updateLoadMoreControls(totalCount, filteredCount, queryActive) {
		if (!loadMoreShell || !loadMoreButton) {
			return;
		}

		const totalVisibleCandidates = queryActive ? filteredCount : totalCount;
		const remainingCount = Math.max(0, totalVisibleCandidates - currentVisibleLimit);
		const showControls = !queryActive && remainingCount > 0;

		loadMoreShell.hidden = !showControls;
		loadMoreButton.hidden = !showControls;
		if (!showControls) {
			loadMoreButton.textContent = '';
			return;
		}

		const increment = Math.min(defaultVisibleLimit, remainingCount);
		loadMoreButton.textContent = 'Voir ' + increment + ' de plus';
	}

	function filterList() {
		if (!list || !searchInput) {
			return;
		}

		const query = searchInput.value.trim();
		if (askShell) {
			askShell.hidden = query.length < 3;
		}
		const words = normalize(query).split(/\s+/).filter(Boolean);
		const items = Array.from(list.querySelectorAll('[data-faq-item]'));
		let visibleCount = 0;
		const rankedItems = [];

		resetHighlights(root);

		if (words.length === 0) {
			sortItemsByDefaultOrder(items).forEach(function (item, index) {
				item.hidden = index >= currentVisibleLimit;
				item.classList.remove('is-open');

				if (!item.hidden) {
					visibleCount++;
				}

				list.appendChild(item);
			});

			if (helper) {
				helper.hidden = false;
			}
			if (noResult) {
				noResult.hidden = true;
			}
			updateLoadMoreControls(items.length, visibleCount, false);

			return;
		}

		items.forEach(function (item) {
			const question = item.querySelector('.faq-popup__question');
			const answer = item.querySelector('[data-faq-answer]');
			const answerText = item.querySelector('[data-faq-answer-text]');
			const meta = item.querySelector('.faq-popup__meta');
			const haystack = normalize(
				(question ? question.textContent : '')
				+ ' '
				+ (answer ? answer.textContent : '')
				+ ' '
				+ (meta ? meta.textContent : '')
			);

			let score = 0;
			words.forEach(function (word) {
				if (word.length > 0 && haystack.indexOf(word) !== -1) {
					score++;
				}
			});

			const visible = score >= Math.ceil(words.length / 2);
			item.hidden = !visible;

			if (visible) {
				visibleCount++;
				item.classList.add('is-open');
				rankedItems.push({
					item: item,
					score: score
				});
				if (question) {
					highlight(question, words);
				}
				if (answerText) {
					highlight(answerText, words);
				}
			} else {
				item.classList.remove('is-open');
			}
		});

		if (helper) {
			helper.hidden = words.length > 0;
		}
		if (noResult) {
			noResult.hidden = visibleCount > 0 || words.length === 0;
		}
		updateLoadMoreControls(items.length, visibleCount, true);

		if (words.length > 0 && rankedItems.length > 1) {
			rankedItems
				.sort(function (a, b) {
					return b.score - a.score;
				})
				.forEach(function (entry) {
					list.appendChild(entry.item);
				});
		}
	}

	function syncScopeSelectors(container) {
		if (!container) {
			return;
		}

		const form = container.querySelector('#formulaire-edit');
		const scopeFields = container.querySelector('[data-faq-scope-fields]');
		if (form && scopeFields && !form.contains(scopeFields)) {
			const scopeSection = scopeFields.closest('[data-faq-scope-section]') || scopeFields;
			form.insertBefore(scopeSection, form.firstChild || null);
		}

		const typeSelect = container.querySelector('[data-faq-scope-kind]');
		const organizationSelect = container.querySelector('[data-faq-scope-organization]');
		const holonSelect = container.querySelector('[data-faq-scope-holon]');
		const parcoursSelect = container.querySelector('[data-faq-scope-parcours]');
		const holonShell = container.querySelector('[data-faq-scope-holon-shell]');
		const parcoursShell = container.querySelector('[data-faq-scope-parcours-shell]');
		const organizationShell = container.querySelector('[data-faq-scope-organization-shell]');
		const organizationInput = container.querySelector('input[name="IDorganization"]');
		const holonInput = container.querySelector('input[name="IDholon"]');
		const parcoursInput = container.querySelector('input[name="IDparcours"]');
		let scopeKindInput = container.querySelector('input[name="faq_scope_kind"]');

		if (!scopeKindInput && form) {
			scopeKindInput = document.createElement('input');
			scopeKindInput.type = 'hidden';
			scopeKindInput.name = 'faq_scope_kind';
			form.appendChild(scopeKindInput);
		}

		if (!holonSelect && !parcoursSelect && !typeSelect) {
			return;
		}

		const scopeKind = typeSelect ? String(typeSelect.value || 'organization') : 'organization';
		const selectedOrganizationId = organizationSelect
			? Number(organizationSelect.value || 0)
			: 0;
		const organizationId = selectedOrganizationId > 0
			? selectedOrganizationId
			: (currentOid > 0 ? currentOid : Number((organizationInput && organizationInput.value) || 0));

		if (scopeKind !== 'generic' && organizationSelect && selectedOrganizationId <= 0 && organizationId > 0) {
			organizationSelect.value = String(organizationId);
		}

		if (scopeKindInput) {
			scopeKindInput.value = scopeKind;
		}

		if (organizationShell) {
			organizationShell.hidden = scopeKind === 'generic';
		}
		if (holonShell) {
			holonShell.hidden = scopeKind !== 'organization';
		}
		if (parcoursShell) {
			parcoursShell.hidden = scopeKind !== 'parcours';
		}

		if (organizationInput) {
			organizationInput.value = scopeKind === 'generic' ? '' : String(organizationId > 0 ? organizationId : '');
		}

		if (holonSelect) {
			let hasVisibleHolonSelection = false;
			Array.from(holonSelect.options || []).forEach(function (option, index) {
				if (index === 0) {
					option.hidden = false;
					return;
				}

				const optionOrganizationId = Number(option.getAttribute('data-organization-id') || 0);
				const shouldShow = organizationId > 0 && optionOrganizationId === organizationId;
				option.hidden = !shouldShow;
				if (!shouldShow && option.selected) {
					holonSelect.selectedIndex = 0;
				}
				if (shouldShow && option.selected) {
					hasVisibleHolonSelection = true;
				}
			});

			if (!hasVisibleHolonSelection && holonSelect.selectedIndex > 0) {
				holonSelect.selectedIndex = 0;
			}

			holonSelect.disabled = scopeKind !== 'organization';
		}
		if (parcoursSelect) {
			let hasVisibleParcoursSelection = false;
			Array.from(parcoursSelect.options || []).forEach(function (option, index) {
				if (index === 0) {
					option.hidden = false;
					return;
				}

				const optionOrganizationId = Number(option.getAttribute('data-organization-id') || 0);
				const shouldShow = organizationId > 0 && optionOrganizationId === organizationId;
				option.hidden = !shouldShow;
				if (!shouldShow && option.selected) {
					parcoursSelect.selectedIndex = 0;
				}
				if (shouldShow && option.selected) {
					hasVisibleParcoursSelection = true;
				}
			});

			if (!hasVisibleParcoursSelection && parcoursSelect.selectedIndex > 0) {
				parcoursSelect.selectedIndex = 0;
			}

			parcoursSelect.disabled = scopeKind !== 'parcours';
		}

		if (holonInput) {
			holonInput.value = scopeKind === 'organization' ? String(holonSelect && holonSelect.value ? holonSelect.value : '') : '';
		}
		if (parcoursInput) {
			parcoursInput.value = scopeKind === 'parcours' ? String(parcoursSelect && parcoursSelect.value ? parcoursSelect.value : '') : '';
		}
	}

	function handleSaveResponse(data) {
		let payload = data;

		if (typeof payload === 'string') {
			try {
				payload = JSON.parse(payload);
			} catch (error) {
				payload = null;
			}
		}

		if (!payload || payload.status === false) {
			window.alert(payload && payload.message ? payload.message : "Impossible d'enregistrer cette FAQ.");
			return;
		}

		if (typeof window.omoOpenPopupHashState === 'function') {
			if (payload.focusId) {
				window.omoOpenPopupHashState('faq', Number(payload.focusId));
			} else {
				window.omoOpenPopupHashState('faq', null);
			}
		}

		if (payload.reloadUrl) {
			refreshPopupContent(payload.reloadUrl);
		} else if (payload.script) {
			eval(payload.script);
		}

		if (payload.message) {
			window.alert(payload.message);
		}
	}

	function syncFaqRichTextFields(form) {
		if (!form || typeof window.jQuery !== 'function') {
			return;
		}

		window.jQuery(form).find('textarea.summernote').each(function () {
			const field = window.jQuery(this);
			if (typeof field.summernote === 'function') {
				try {
					field.val(field.summernote('code'));
				} catch (error) {
				}
			}
		});
	}

	function submitFaqForm(form, requestResolution) {
		if (!form) {
			return;
		}

		syncScopeSelectors(form.closest('[data-faq-form-shell]') || form);
		syncFaqRichTextFields(form);
		let resolutionInput = form.querySelector('input[name="faq_request_resolution"]');
		if (requestResolution) {
			if (!resolutionInput) {
				resolutionInput = document.createElement('input');
				resolutionInput.type = 'hidden';
				resolutionInput.name = 'faq_request_resolution';
				form.appendChild(resolutionInput);
			}
			resolutionInput.value = requestResolution;
		} else if (resolutionInput) {
			resolutionInput.remove();
		}
		form.classList.add('disabled');

		fetch(form.getAttribute('action'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: new FormData(form)
		})
			.then(function (response) {
				return response.text();
			})
			.then(function (data) {
				handleSaveResponse(data);
			})
			.catch(function () {
				window.alert("Impossible d'enregistrer cette FAQ.");
			})
			.finally(function () {
				form.classList.remove('disabled');
			});
	}

	function relayFaqRequest(relayButton) {
		const faqId = Number(relayButton && relayButton.getAttribute('data-faq-id') || 0);
		if (!faqId) {
			return;
		}

		relayButton.disabled = true;
		fetch('/ajax/faq_request_relay.php?' + buildFaqQuery(faqId), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (response) {
				return response.json().then(function (payload) {
					if (!response.ok || !payload.success) {
						throw new Error(payload.message || 'Impossible de relayer cette question.');
					}
					return payload;
				});
			})
			.then(function (payload) {
				window.alert(payload.message || 'Question relayée aux administrateurs de l’organisation.');
				showDetail(faqId, { updateHash: false });
			})
			.catch(function (error) {
				window.alert(error.message || 'Impossible de relayer cette question.');
			})
			.finally(function () {
				relayButton.disabled = false;
			});
	}

	function deleteFaq(deleteButton) {
		const faqId = Number(deleteButton && deleteButton.getAttribute('data-faq-id') || 0);
		if (!faqId) {
			return;
		}

		const confirmationMessage = deleteButton.getAttribute('data-faq-delete-confirm') || 'Supprimer cette FAQ ?';
		if (!window.confirm(confirmationMessage)) {
			return;
		}

		deleteButton.disabled = true;
		fetch('/ajax/faq_delete.php?' + buildFaqQuery(faqId), {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				return response.text();
			})
			.then(function (data) {
				let payload = null;
				try {
					payload = JSON.parse(data);
				} catch (error) {
				}

				if (!payload || payload.status === false) {
					deleteButton.disabled = false;
					window.alert(payload && payload.message ? payload.message : 'Impossible de supprimer cette FAQ.');
					return;
				}

				handleSaveResponse(payload);
			})
			.catch(function () {
				deleteButton.disabled = false;
				window.alert('Impossible de supprimer cette FAQ.');
			});
	}

	function formatVoteScore(value) {
		const numericValue = Number(value || 0);
		if (!Number.isFinite(numericValue)) {
			return '0';
		}

		if (Math.abs(numericValue - Math.round(numericValue)) < 0.00001) {
			return String(Math.round(numericValue));
		}

		return numericValue.toFixed(2).replace(/\.?0+$/, '');
	}

	function estimateVoteSplit(positiveScore, negativeScore, totalVotes) {
		const positive = Math.max(0, Number(positiveScore || 0));
		const negative = Math.abs(Number(negativeScore || 0));
		const total = Math.max(0, Number(totalVotes || 0));
		const activeSignal = positive + negative;

		if (!activeSignal || !total) {
			return {
				positive: 0,
				negative: 0,
				total: total
			};
		}

		const estimatedPositive = Math.max(0, Math.min(total, Math.round(total * (positive / activeSignal))));
		return {
			positive: estimatedPositive,
			negative: Math.max(0, total - estimatedPositive),
			total: total
		};
	}

	function renderStars(starCount) {
		const normalizedCount = Math.max(0, Math.min(5, Number(starCount || 0)));
		return '★'.repeat(normalizedCount) + '☆'.repeat(5 - normalizedCount);
	}

	function refreshCompactVoteStars() {
		const compactShells = Array.from(root.querySelectorAll('[data-faq-vote-shell][data-faq-vote-mode="compact"]'));
		if (compactShells.length === 0) {
			return;
		}

		const reliabilities = compactShells
			.map(function (shell) {
				return Math.max(0, Number(shell.getAttribute('data-faq-reliability') || 0));
			})
			.filter(function (value) {
				return Number.isFinite(value);
			});

		const minReliability = reliabilities.length > 0 ? Math.min.apply(null, reliabilities) : 0;
		const maxReliability = reliabilities.length > 0 ? Math.max.apply(null, reliabilities) : 0;

		compactShells.forEach(function (shell) {
			const reliability = Math.max(0, Number(shell.getAttribute('data-faq-reliability') || 0));
			let starCount = 0;

			if (maxReliability <= minReliability) {
				starCount = reliability > 0 ? 5 : 0;
			} else {
				starCount = Math.max(0, Math.min(5, Math.round(((reliability - minReliability) / (maxReliability - minReliability)) * 5)));
			}

			const starsNode = shell.querySelector('[data-faq-stars-text]');
			if (starsNode) {
				starsNode.textContent = renderStars(starCount);
			}
		});
	}

	function setVoteButtonsDisabled(faqId, disabled) {
		root.querySelectorAll('[data-faq-vote-shell][data-faq-id="' + faqId + '"] [data-faq-vote]').forEach(function (button) {
			button.disabled = !!disabled;
		});
	}

	function applyVoteState(payload) {
		const faqId = Number(payload && payload.faqId ? payload.faqId : 0);
		if (!faqId) {
			return;
		}

		root.querySelectorAll('[data-faq-vote-shell][data-faq-id="' + faqId + '"]').forEach(function (shell) {
			const voteMode = shell.getAttribute('data-faq-vote-mode') || 'detail';
			const positiveNode = shell.querySelector('[data-faq-score="positive_estimated"]');
			const negativeNode = shell.querySelector('[data-faq-score="negative_estimated"]');
			const totalNode = shell.querySelector('[data-faq-score="total"]');
			const messageNode = shell.querySelector('[data-faq-vote-message]');
			const voteSplit = estimateVoteSplit(payload.positiveScore, payload.negativeScore, payload.totalVotes);

			if (payload.reliability !== undefined) {
				shell.setAttribute('data-faq-reliability', String(Number(payload.reliability || 0)));
			}
			if (voteMode !== 'compact' && positiveNode) {
				positiveNode.textContent = String(voteSplit.positive);
			}
			if (voteMode !== 'compact' && negativeNode) {
				negativeNode.textContent = String(voteSplit.negative);
			}
			if (totalNode && payload.totalVotes !== undefined) {
				totalNode.textContent = String(Number(payload.totalVotes || 0));
			}

			if (messageNode) {
				const message = payload.message ? String(payload.message) : '';
				messageNode.textContent = message;
				messageNode.classList.toggle('is-visible', message !== '');
			}
		});

		refreshCompactVoteStars();

		if (payload.status === true || payload.alreadyVoted) {
			setVoteButtonsDisabled(faqId, true);
		}
	}

	function submitVote(faqId, vote) {
		const normalizedFaqId = Number(faqId || 0);
		const normalizedVote = String(vote || '').trim().toLowerCase();
		if (!normalizedFaqId || (normalizedVote !== 'up' && normalizedVote !== 'down')) {
			return;
		}

		setVoteButtonsDisabled(normalizedFaqId, true);

		const requestBody = new URLSearchParams();
		requestBody.set('faq_id', String(normalizedFaqId));
		requestBody.set('vote', normalizedVote);
		requestBody.set('oid', String(currentOid));
		requestBody.set('cid', String(currentCid));
		requestBody.set('faq_scope', currentScope);

		fetch('/ajax/faq_vote.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: requestBody.toString()
		})
			.then(function (response) {
				return response.text().then(function (text) {
					let payload = null;

					try {
						payload = JSON.parse(text);
					} catch (error) {
						payload = null;
					}

					return {
						ok: response.ok,
						payload: payload
					};
				});
			})
			.then(function (result) {
				if (result.payload) {
					applyVoteState(result.payload);
				}

				if (result.payload && (result.payload.status === true || result.payload.alreadyVoted)) {
					return;
				}

				setVoteButtonsDisabled(normalizedFaqId, false);
				window.alert(result.payload && result.payload.message ? result.payload.message : 'Impossible d enregistrer ce vote.');
			})
			.catch(function () {
				setVoteButtonsDisabled(normalizedFaqId, false);
				window.alert('Impossible d enregistrer ce vote pour le moment.');
			});
	}

	root.addEventListener('change', function (event) {
		if (event.target.matches('[data-faq-scope-kind], [data-faq-scope-organization], [data-faq-scope-holon], [data-faq-scope-parcours]')) {
			syncScopeSelectors(event.target.closest('[data-faq-form-shell]') || event.target.closest('.faq-popup__detail') || root);
		}
	});

	root.addEventListener('click', function (event) {
		const toggle = event.target.closest('[data-faq-toggle]');
		if (toggle) {
			const item = toggle.closest('[data-faq-item]');
			if (!item) {
				return;
			}

			root.querySelectorAll('[data-faq-item]').forEach(function (other) {
				if (other !== item) {
					other.classList.remove('is-open');
				}
			});

			item.classList.toggle('is-open');
			return;
		}

		const detailButton = event.target.closest('[data-faq-detail]');
		if (detailButton) {
			showDetail(detailButton.getAttribute('data-faq-id'));
			return;
		}

		const deleteButton = event.target.closest('[data-faq-delete]');
		if (deleteButton) {
			deleteFaq(deleteButton);
			return;
		}

		const editButton = event.target.closest('[data-faq-edit]');
		if (editButton) {
			showDetail(editButton.getAttribute('data-faq-id'), { edit: true });
			return;
		}

		const cancelEditButton = event.target.closest('[data-faq-cancel-edit]');
		if (cancelEditButton) {
			showDetail(cancelEditButton.getAttribute('data-faq-id'));
			return;
		}

		const scopeToggleButton = event.target.closest('[data-faq-scope-toggle]');
		if (scopeToggleButton) {
			const targetScope = scopeToggleButton.getAttribute('data-faq-scope-toggle') || 'contextual';
			refreshPopupContent(buildPopupUrlForScope(targetScope), { scope: targetScope });
			return;
		}

		if (event.target.closest('[data-faq-load-more]')) {
			currentVisibleLimit += defaultVisibleLimit;
			filterList();
			return;
		}

		if (event.target.closest('[data-faq-add]')) {
			showEditor();
			return;
		}

		if (event.target.closest('[data-faq-ask]')) {
			showRequestForm();
			return;
		}

		const saveButton = event.target.closest('[data-faq-save]');
		if (saveButton) {
			const scope = saveButton.closest('[data-faq-form-shell]') || editorView || detailView;
			const form = scope ? scope.querySelector('#formulaire-edit') : null;
			if (form) {
				submitFaqForm(form, saveButton.getAttribute('data-faq-request-resolution') || '');
			}
			return;
		}

		const relayButton = event.target.closest('[data-faq-relay]');
		if (relayButton) {
			relayFaqRequest(relayButton);
			return;
		}

		const voteButton = event.target.closest('[data-faq-vote]');
		if (voteButton) {
			submitVote(voteButton.getAttribute('data-faq-id'), voteButton.getAttribute('data-faq-vote'));
			return;
		}

		if (event.target.closest('[data-faq-back]')) {
			showList();
		}
	});

	root.addEventListener('submit', function (event) {
		const form = event.target.closest('[data-faq-request-form]');
		if (!form) return;
		event.preventDefault();
		submitQuestionRequest(form);
	});

	if (searchInput) {
		searchInput.addEventListener('input', filterList);
	}

	window.addEventListener('hashchange', syncFromHash);
	window.addEventListener('omo-popup-route-update', syncFromHash);

	window.__omoPopupCleanup = function () {
		detailRequestId++;
		if (modalTitle && modalTitle.textContent === appliedTitle) modalTitle.textContent = listTitle;
		window.removeEventListener('hashchange', syncFromHash);
		window.removeEventListener('omo-popup-route-update', syncFromHash);
		destroyFaqRichTextFields(root);
		if (modalBody) {
			modalBody.removeAttribute('data-omo-faq-modal');
			modalBody.removeAttribute('data-omo-popup-key');
			modalBody.removeAttribute('data-omo-popup-url');
			modalBody.removeAttribute('data-omo-popup-live-sync');
		}
	};

	syncFromHash();
	if (!currentViewToken) {
		showList({ updateHash: false });
	}
	syncScopeSelectors(editorView);
	refreshCompactVoteStars();
	filterList();
})();
