window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/omo/document_share.js"] = function (pageConfig, pageScript) {
    (function () {
        const token = pageConfig.token;
        const titleNode = document.getElementById('omoDocumentShareTitle');
        const descriptionNode = document.getElementById('omoDocumentShareDescription');
        const updatedAtNode = document.getElementById('omoDocumentShareUpdatedAt');
        const draftBadgeNode = document.getElementById('omoDocumentShareDraftBadge');
        const statusNode = document.getElementById('omoDocumentShareStatus');
        const contentNode = document.getElementById('omoDocumentShareContent');
        const pollDelayMs = 2000;
        let knownUpdatedAt = pageConfig.knownUpdatedAt || '';
        let knownContentHash = pageConfig.knownContentHash || '';
        let knownStateHash = pageConfig.knownStateHash || '';

        function updateSnapshot(payload) {
            if (!payload || payload.status !== true || !contentNode) {
                return;
            }

            if (payload.stateHash) {
                knownStateHash = String(payload.stateHash);
            }

            if (payload.contentHash) {
                knownContentHash = String(payload.contentHash);
            }

            if (payload.updatedAt) {
                knownUpdatedAt = String(payload.updatedAt);
            }

            if (payload.changed === false) {
                return;
            }

            const nextTitle = String(payload.title || '').trim();
            const nextDescription = String(payload.description || '').trim();

            if (nextTitle !== '' && titleNode) {
                titleNode.textContent = nextTitle;
            }

            if (descriptionNode) {
                descriptionNode.textContent = nextDescription;
                descriptionNode.hidden = nextDescription === '';
            }

            if (payload.contentChanged !== false && Object.prototype.hasOwnProperty.call(payload, 'content')) {
                contentNode.innerHTML = String(payload.content || '');
            }

            if (updatedAtNode && payload.updatedAt) {
                const date = new Date(payload.updatedAt);
                if (!Number.isNaN(date.getTime())) {
                    updatedAtNode.textContent = 'Mise a jour ' + date.toLocaleString('fr-CH');
                }
            }

            if (draftBadgeNode) {
                draftBadgeNode.hidden = !payload.isDraft;
            }

            if (statusNode) {
                if (payload.isDraft && payload.editingUserName) {
                    statusNode.textContent = 'Edition en cours par ' + payload.editingUserName + '.';
                } else if (payload.isDraft) {
                    statusNode.textContent = 'Edition en cours.';
                } else {
                    statusNode.textContent = 'Lecture partagee du document.';
                }
            }
        }

        function poll() {
            const params = new URLSearchParams();
            params.set('token', token);
            if (knownUpdatedAt) {
                params.set('known_updated_at', knownUpdatedAt);
            }
            if (knownContentHash) {
                params.set('known_content_hash', knownContentHash);
            }
            if (knownStateHash) {
                params.set('known_state_hash', knownStateHash);
            }

            fetch('/omo/api/documents/share_live.php?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('share_live_failed');
                    }
                    return response.json();
                })
                .then(updateSnapshot)
                .catch(function () {
                })
                .finally(function () {
                    window.setTimeout(poll, pollDelayMs);
                });
        }

        window.setTimeout(poll, pollDelayMs);
    })();
};
