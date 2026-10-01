window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts["/common/assets/admin-edit-form.js"] = function (pageConfig, pageScript) {
(function(){
    $(function () {
        function beginAdminEditPending(form) {
            if (typeof window.omoBeginPendingAction === "function") {
                return window.omoBeginPendingAction(form);
            }

            if ($(form).data("admin-edit-pending") === true) {
                return false;
            }

            $(form).data("admin-edit-pending", true);
            $(form).find("button, input[type='submit'], input[type='button']").prop("disabled", true);
            $("[form='" + form.id + "']").prop("disabled", true);
            return true;
        }

        function endAdminEditPending(form) {
            if (typeof window.omoEndPendingAction === "function") {
                window.omoEndPendingAction(form);
                return;
            }

            $(form).removeData("admin-edit-pending");
            $(form).find("button, input[type='submit'], input[type='button']").prop("disabled", false);
            $("[form='" + form.id + "']").prop("disabled", false);
        }

        adminEditInitHtmlFields(document);

        $(".required").each(function () {
            $(this).closest("tr").find("th").append("<span class='required-star'>*</span>");
        });

        $(".admin-edit__color-picker").on("input change", function () {
            var hiddenField = $("#" + $(this).data("target"));
            var textField = $("#" + $(this).data("text-target"));
            hiddenField.val($(this).val());
            textField.val($(this).val());
            textField.trigger("keyup");
        });

        $(".admin-edit__color-text").on("input change", function () {
            var rawValue = $.trim($(this).val());
            var hiddenField = $("#" + $(this).data("target"));
            var pickerField = $("#" + $(this).data("picker-target"));
            hiddenField.val(rawValue);

            if (/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(rawValue)) {
                var normalizedValue = rawValue.toLowerCase();
                if (normalizedValue.length === 4) {
                    normalizedValue = "#" + normalizedValue[1] + normalizedValue[1] + normalizedValue[2] + normalizedValue[2] + normalizedValue[3] + normalizedValue[3];
                }
                pickerField.val(normalizedValue);
            }
        });

        $("#btn_submit").click(function () {
            adminEditSyncHtmlFields(document);

            let serform = $("#formulaire-edit").serialize()
            if (serform.length > 20000000) {
                alert("Image too big (max 20M)\nResize it or zoom it more");
                return;
            }

            // Disable the button
            $(this).prop("disabled", true);
            // Validate data via ajax

            $.post(("/ajax/check.php?type=" + pageConfig.this + ""), serform, function (data) {
                if (data != "") {
                    // If not ok, show the error message
                    alert(data);
                    $("#btn_submit").prop("disabled", false);
                } else {
                    // Transfer the validation lock to the form-wide save lock.
                    $("#btn_submit").prop("disabled", false);
                    $("#formulaire-edit").submit();
                }

            })
                .fail(function () {
                    alert("Sorry, we encounter an error while creating the object.");
                    $("#btn_submit").prop("disabled", false);
                });

        });

        $("#formulaire-edit").on('submit', function (event) {

            event.preventDefault();

            var form = this;
            var url = $(form).attr('action');
            adminEditSyncHtmlFields(form);
            var formData = new FormData(form);
            if (!beginAdminEditPending(form)) {
                return;
            }

            console.log("=== SUBMIT START ===");

            // Cropped image blobs
            if (window.croppedImages) {
                for (let key in window.croppedImages) {
                    let blob = window.croppedImages[key];

                    if (blob) {
                        console.log("Ajout image :", key, blob);
                        let extension = 'jpg';

                        if (blob.type === 'image/png') {
                            extension = 'png';
                        } else if (blob.type === 'image/webp') {
                            extension = 'webp';
                        }

                        formData.append(key, blob, key + '.' + extension);
                    }
                }
            } else {
                console.log("Aucune image cropée trouvée");
            }

            // 🔍 DEBUG
            for (let pair of formData.entries()) {
                console.log(pair[0], pair[1]);
            }

            // AJAX (existing handler)
            $.ajax({
                type: 'POST',
                url: url,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,

                success: function (data) {

                    console.log("Réponse serveur :", data);

                    var response;
                    try {
                        response = JSON.parse(data);
                    } catch (e) {
                        console.error("Réponse non JSON :", data);
                        alert("Erreur serveur");
                        endAdminEditPending(form);
                        return;
                    }

                    if (response.success) {

                        if (pageConfig.success !== "") {

                        if (("" + pageConfig.success + "").indexOf("()") > 0) {
                            eval(("" + pageConfig.success + ""));
                        } else {

                            var form_result = $('<form></form>');
                            form_result.attr('method', 'post');
                            form_result.attr('action', ("" + pageConfig.success + ""));

                            var id = $('<input type="text" name="id" value="' + response.id + '" />');
                            form_result.append(id);

                            $("body").append(form_result);
                            form_result.submit();
                        }

                        } else {

                        alert("Données enregistrées");
                        endAdminEditPending(form);

                        }

                    } else {
                        alert(response.message);
                        endAdminEditPending(form);
                    }
                },

                error: function () {
                    alert("Une erreur s'est produite. Veuillez réessayer plus tard.");
                    endAdminEditPending(form);
                }
            });

        });

    });

})();
};
