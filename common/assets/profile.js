window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/profile.js"] = function (pageConfig, pageScript) {
(function () {
    var patreonConnectOrigin = pageConfig.patreonConnectOrigin;
    var root = document.getElementById("profilePanelRoot");
    var initialTabButton = root ? root.querySelector(".generic-tabs__tab.is-active[data-profile-fragment-panel]") : null;
    var activeProfileTabButton = initialTabButton;
    var invalidResponseMessage = pageConfig.invalidResponseMessage;
    var scopeLoadingMessage = pageConfig.scopeLoadingMessage;
    var scopeLoadErrorMessage = pageConfig.scopeLoadErrorMessage;
    var disconnectConfirmMessage = pageConfig.disconnectConfirmMessage;
    var unsavedChangesMessage = pageConfig.unsavedChangesMessage;
    var dirtyForms = Object.create(null);

    window.profileMarkDirty = function (key) {
        dirtyForms[String(key || 'profile')] = true;
    };

    window.profileMarkClean = function (key) {
        delete dirtyForms[String(key || 'profile')];
    };

    window.profileResetDirty = function () {
        dirtyForms = Object.create(null);
    };

    window.commonTopbarModalCanClose = function () {
        if (Object.keys(dirtyForms).length === 0) {
            return true;
        }

        return window.confirm(unsavedChangesMessage);
    };

    function parseJsonResponse(text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            return {
                status: false,
                message: invalidResponseMessage
            };
        }
    }

    function handleLegacyAjaxResponse(payload) {
        if (payload && payload.script) {
            try {
                eval(payload.script);
            } catch (error) {
                console.error(error);
            }
        }

        if (payload && payload.message) {
            alert(payload.message);
        }
    }

    function postUrl(url) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        }).then(function (response) {
            return response.text();
        }).then(parseJsonResponse);
    }

    function executeEmbeddedScripts(container) {
        return window.commonExecuteFragmentScripts(container);
    }

    function buildProfileModalUrl(tabName, scopeName) {
        var normalizedTab = tabName === "organization" || tabName === "general" || tabName === "competences" || tabName === "patreon" || tabName === "tools" ? tabName : "general";
        var normalizedScope = scopeName === "organization" ? "organization" : "general";
        return "/popup/profil.php?tab=" + encodeURIComponent(normalizedTab) + "&scope=" + encodeURIComponent(normalizedScope);
    }

    function getActiveProfileTabName() {
        var activeTab = root ? root.querySelector(".generic-tabs__tab.is-active") : null;
        if (!activeTab) {
            return "general";
        }

        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-organization") {
            return "organization";
        }
        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-general") {
            return "general";
        }
        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-competences") {
            return "competences";
        }
        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-patreon") {
            return "patreon";
        }
        if (activeTab.getAttribute("data-generic-tab-target") === "profile-panel-tab-tools") {
            return "tools";
        }

        return "general";
    }

    function loadFragmentHost(host, forceReload) {
        var fragmentUrl;

        if (!host) {
            return;
        }

        fragmentUrl = host.getAttribute("data-profile-fragment-url") || "";
        if (fragmentUrl === "") {
            return;
        }

        if (!forceReload && host.getAttribute("data-profile-fragment-loaded") === "1") {
            return;
        }

        if (host.getAttribute("data-profile-fragment-kind") === "profile" && root) {
            Array.prototype.forEach.call(root.querySelectorAll('[data-profile-fragment-host="1"][data-profile-fragment-kind="profile"]'), function (otherHost) {
                if (otherHost === host) {
                    return;
                }

                otherHost.removeAttribute("data-profile-fragment-loaded");
                otherHost.innerHTML = "";
            });
        }

        host.innerHTML = '<div class="profile-panel__feedback">' + scopeLoadingMessage + '</div>';

        fetch(fragmentUrl, {
            credentials: "same-origin",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Erreur de chargement");
                }

                return response.text();
            })
            .then(function (html) {
                host.innerHTML = html;
                host.setAttribute("data-profile-fragment-loaded", "1");
                return executeEmbeddedScripts(host);
            })
            .catch(function () {
                host.innerHTML = '<div class="profile-panel__feedback is-error">' + scopeLoadErrorMessage + '</div>';
            });
    }

    window.profileHandleGeneralSaved = function () {
        var targetUrl = buildProfileModalUrl("general", "general");
        window.profileResetDirty();
        if (window.commonTopbarRefreshUserProfile) {
            window.commonTopbarRefreshUserProfile("save");
        }
        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(targetUrl);
        }
        if (window.jQuery && document.getElementById("popup_content")) {
            window.jQuery("#popup_content").load(targetUrl);
        }
    };

    window.profileHandleOrganizationSaved = function () {
        var targetUrl = buildProfileModalUrl("organization", "organization");
        window.profileResetDirty();
        if (window.commonTopbarRefreshUserProfile) {
            window.commonTopbarRefreshUserProfile("save");
        }
        if (window.commonTopbarRefreshModalContent) {
            window.commonTopbarRefreshModalContent(targetUrl);
        }
        if (window.jQuery && document.getElementById("popup_content")) {
            window.jQuery("#popup_content").load(targetUrl);
        }
    };

    Array.prototype.forEach.call(document.querySelectorAll("[data-profile-fragment-panel]"), function (button) {
        button.addEventListener("click", function () {
            var panelId = button.getAttribute("data-profile-fragment-panel") || "";
            var panel = panelId !== "" ? document.getElementById(panelId) : null;
            var host = panel ? panel.querySelector('[data-profile-fragment-host="1"]') : null;
            var activeButton = activeProfileTabButton;

            if (activeButton !== button && !window.commonTopbarModalCanClose()) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }
            if (activeButton !== button) {
                window.profileResetDirty();
                if (root) {
                    Array.prototype.forEach.call(root.querySelectorAll('[data-profile-fragment-host="1"]'), function (otherHost) {
                        if (otherHost === host) {
                            return;
                        }

                        otherHost.removeAttribute("data-profile-fragment-loaded");
                        otherHost.innerHTML = "";
                    });
                }
                activeProfileTabButton = button;
            }

            loadFragmentHost(host, false);
        });
    });

    if (initialTabButton) {
        var initialPanelId = initialTabButton.getAttribute("data-profile-fragment-panel") || "";
        var initialPanel = initialPanelId !== "" ? document.getElementById(initialPanelId) : null;
        var initialHost = initialPanel ? initialPanel.querySelector('[data-profile-fragment-host="1"]') : null;
        loadFragmentHost(initialHost, false);
    }

    if (root) {
        root.addEventListener("click", function (event) {
            var connectButton = event.target.closest("#patreon_connect");
            var disconnectButton = event.target.closest("#patreon_disconnect");
            var syncButton = event.target.closest("#patreon_sync");
            var width;
            var height;
            var left;
            var top;

            if (connectButton) {
                width = 720;
                height = 860;
                left = Math.max(0, (window.screen.width - width) / 2);
                top = Math.max(0, (window.screen.height - height) / 2);
                window.open(
                    "/common/patreon_connect.php",
                    "patreon_connect",
                    "width=" + width + ",height=" + height + ",left=" + left + ",top=" + top + ",resizable=yes,scrollbars=yes"
                );
                return;
            }

            if (disconnectButton) {
                if (!confirm(disconnectConfirmMessage)) {
                    return;
                }

                disconnectButton.disabled = true;
                postUrl("/ajax/patreon_disconnect.php")
                    .then(handleLegacyAjaxResponse)
                    .finally(function () {
                        disconnectButton.disabled = false;
                    });
                return;
            }

            if (syncButton) {
                syncButton.disabled = true;
                postUrl("/ajax/patreon_sync.php")
                    .then(handleLegacyAjaxResponse)
                    .finally(function () {
                        syncButton.disabled = false;
                    });
            }
        });
    }

    window.addEventListener("message", function (event) {
        if (patreonConnectOrigin === "" || event.origin !== patreonConnectOrigin) {
            return;
        }

        if (event.data && event.data.type === "patreon-connected") {
            var activeTabName = getActiveProfileTabName();
            var activeScopeName = activeTabName === "organization" ? "organization" : "general";
            var targetUrl = buildProfileModalUrl(activeTabName, activeScopeName);
            if (window.commonTopbarRefreshModalContent) {
                window.commonTopbarRefreshModalContent(targetUrl);
            }
            if (window.jQuery && document.getElementById("popup_content")) {
                window.jQuery("#popup_content").load(targetUrl);
            }
        }
    });
})();
};
