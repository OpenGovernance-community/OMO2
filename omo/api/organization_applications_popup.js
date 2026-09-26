window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/api/organization_applications_popup.js"] = function (pageConfig, pageScript) {
        (function () {
            var appPickerText = pageConfig.appPickerText;
            var form = document.getElementById('omoApplicationPickerForm');
            var list = document.getElementById('omoApplicationPickerList');
            var feedback = document.getElementById('omoApplicationPickerFeedback');
            var submitButton = document.getElementById('omoApplicationPickerSubmit');

            if (!form || !list || !feedback || !submitButton) {
                return;
            }

            var clearFeedback = function () {
                feedback.textContent = '';
                feedback.classList.remove('is-success');
            };
            var showFeedback = function (message, isError) {
                if (typeof window.commonNotify === 'function') {
                    window.commonNotify(String(message || ''), isError ? 'error' : 'success');
                    clearFeedback();
                    return;
                }

                feedback.textContent = String(message || '');
                feedback.classList.toggle('is-success', !isError);
            };

            var updateCardState = function (checkbox) {
                var card = checkbox.closest('[data-omo-app-picker-card]');
                var state = card ? card.querySelector('[data-omo-app-picker-state]') : null;

                if (card) {
                    card.classList.toggle('is-active', checkbox.checked);
                }

                if (state) {
                    state.textContent = checkbox.checked ? appPickerText.visible : appPickerText.hidden;
                    state.classList.toggle('omo-app-picker__state--active', checkbox.checked);
                }
            };

            Array.prototype.forEach.call(form.querySelectorAll('.omo-app-picker__checkbox'), function (checkbox) {
                updateCardState(checkbox);
                checkbox.addEventListener('change', function () {
                    updateCardState(checkbox);
                    clearFeedback();
                });
            });

            if (typeof window.commonCreateVerticalSortableList === 'function') {
                window.commonCreateVerticalSortableList({
                    list: list,
                    itemSelector: '[data-omo-app-picker-card]',
                    handleSelector: '[data-omo-app-picker-drag]',
                    draggingClass: 'is-dragging',
                    dropTargetClass: 'is-drop-target',
                    placeholderClass: 'omo-app-picker__placeholder',
                    createPlaceholder: function (card) {
                        var placeholderCard = document.createElement('div');
                        placeholderCard.style.height = card.getBoundingClientRect().height + 'px';
                        return placeholderCard;
                    },
                    onDragStart: function () {
                        clearFeedback();
                    },
                    onDrop: function () {
                        clearFeedback();
                    }
                });
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                clearFeedback();
                submitButton.disabled = true;

                var formData = new FormData(form);
                Array.prototype.forEach.call(list.querySelectorAll('[data-omo-app-picker-card]'), function (card) {
                    var applicationId = card.getAttribute('data-omo-app-id');
                    if (!applicationId) {
                        return;
                    }

                    formData.append('order[]', applicationId);
                });

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data || !data.status) {
                            showFeedback(data && data.message ? data.message : appPickerText.genericError, true);
                            submitButton.disabled = false;
                            return;
                        }

                        showFeedback(data.message || appPickerText.savedSimple, false);

                        if (typeof window.omoRefreshSidebar === 'function') {
                            window.omoRefreshSidebar(function () {
                                if (typeof window.omoRefreshMainRightPanel === 'function') {
                                    window.omoRefreshMainRightPanel();
                                }
                                if (typeof window.commonTopbarCloseModal === 'function') {
                                    window.commonTopbarCloseModal();
                                }
                            });
                            return;
                        }

                        if (typeof window.commonTopbarCloseModal === 'function') {
                            window.commonTopbarCloseModal();
                        }
                    })
                    .catch(function () {
                        showFeedback(appPickerText.saveLater, true);
                        submitButton.disabled = false;
                    });
            });
        })();
};
