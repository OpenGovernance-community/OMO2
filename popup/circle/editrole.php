<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();

	// Si l'organisation n'est pas sauvegardée sur on compte et n'a pas de modèles de rôles spécifiques, applique les modèles de base
	// Affichage des champs de formulaires en fonction du type de rôle

	echo "<div id='form_edit_node'></div>";
	// Affiche le bouton pour sauver
	echo "<button id='btn_save_role_final'>Sauver</button>";
?>
<script>
	function getRef(node) {
	    if (node.IDdb !== undefined && node.IDdb !== null && node.IDdb !== "" && !isNaN(Number(node.IDdb))) return "n="+Number(node.IDdb);
		if (node.ID !== undefined && node.ID !== null && node.ID !== "" && !isNaN(Number(node.ID))) return "n="+Number(node.ID);
		if (node.t !== undefined && node.t !== null && node.t !== "" && !isNaN(Number(node.t))) return "t="+Number(node.t);
		return 2;
	}

	function parseStoredListValue(rawValue) {
		if (rawValue === undefined || rawValue === null || rawValue === "") {
			return [];
		}

		if (Array.isArray(rawValue)) {
			return rawValue;
		}

		try {
			const decoded = JSON.parse(rawValue);
			return Array.isArray(decoded) ? decoded : [];
		} catch (error) {
			return String(rawValue)
				.split(/\r\n|\r|\n|\|/)
				.map(function(item) { return item.trim(); })
				.filter(Boolean);
		}
	}

	function getListInputType(listItemType) {
		if (listItemType === "number") {
			return "number";
		}
		if (listItemType === "date") {
			return "date";
		}
		return "text";
	}

	function createListFieldRowHtml(listItemType, value) {
		const inputType = getListInputType(String(listItemType || "text"));
		const safeValue = value !== undefined && value !== null ? String(value) : "";
		const escapedValue = safeValue
			.replace(/&/g, "&amp;")
			.replace(/"/g, "&quot;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;");
		const step = inputType === "number" ? ' step="any"' : "";

		return ''
			+ '<div class="role-list-field__row">'
			+ '  <input type="' + inputType + '" class="role-list-field__value" value="' + escapedValue + '"' + step + '>'
			+ '  <button type="button" class="role-list-field__remove" data-list-remove="1" aria-label="Supprimer">&times;</button>'
			+ '</div>';
	}

	function ensureListFieldHasRow($field) {
		const $items = $field.find(".role-list-field__items");
		if ($items.children(".role-list-field__row").length > 0) {
			return;
		}

		$items.append(createListFieldRowHtml($field.data("list-item-type"), ""));
	}

	function writeRoleFieldValue($field, rawValue) {
		const mode = String($field.data("field-mode") || "scalar");

		if (mode === "json-holon-list") {
			const values = parseStoredListValue(rawValue).map(function(item) {
				return String(Array.isArray(item) ? "" : item);
			}).filter(Boolean);
			$field.val(values);
			return;
		}

		if (mode === "json-list") {
			const values = parseStoredListValue(rawValue).map(function(item) {
				return Array.isArray(item) ? "" : String(item).trim();
			}).filter(function(item) {
				return item !== "";
			});
			const $items = $field.find(".role-list-field__items");
			$items.empty();
			(values.length ? values : [""]).forEach(function(item) {
				$items.append(createListFieldRowHtml($field.data("list-item-type"), item));
			});
			return;
		}

		$field.val(rawValue !== undefined && rawValue !== null ? rawValue : "");
	}

	function readRoleFieldValue($field) {
		const mode = String($field.data("field-mode") || "scalar");

		if (mode === "json-holon-list") {
			const values = ($field.val() || []).map(function(item) {
				return Number(item || 0);
			}).filter(Boolean);
			return values.length ? JSON.stringify(values) : "";
		}

		if (mode === "json-list") {
			const values = $field.find(".role-list-field__value").map(function() {
				return String($(this).val() || "").trim();
			}).get().filter(Boolean);
			return values.length ? JSON.stringify(values) : "";
		}

		return $field.val();
	}

	$(function() {
		$("#form_edit_node").on("click", "[data-list-add]", function () {
			const $field = $(this).closest(".role-list-field");
			$field.find(".role-list-field__items").append(createListFieldRowHtml($field.data("list-item-type"), ""));
			$field.find(".role-list-field__value").last().trigger("focus");
		});

		$("#form_edit_node").on("click", "[data-list-remove]", function () {
			const $field = $(this).closest(".role-list-field");
			$(this).closest(".role-list-field__row").remove();
			ensureListFieldHasRow($field);
		});

		$("#form_edit_node").load("/popup/circle/editinterface.php?" + getRef(currentnode), function() {
			$("[id^='role_field_']").each(function() {
				const $field = $(this);
				const elementId = $field.attr("id");
				const key = elementId.replace("role_field_", "");

				if (key === "name") {
					$field.val(currentnode[key]);
					return;
				}

				if (currentnode.data && currentnode.data["d" + key] !== undefined) {
					writeRoleFieldValue($field, currentnode.data["d" + key].value);
					if (currentnode.data["d" + key].ancestor !== undefined) {
						$("#role_parent_" + key).text(currentnode.data["d" + key].ancestor || "");
					}
				}
			});
		});

		$("#btn_save_role_final").click(function () {
			if (!currentnode.data) {
				currentnode.data = {};
			}

			$("[id^='role_field_']").each(function() {
				const $field = $(this);
				const elementId = $field.attr("id");
				const key = elementId.replace("role_field_", "");

				if (key === "name") {
					currentnode[key] = $field.val();
				} else {
					currentnode.data["d" + key] = {
						"value": readRoleFieldValue($field),
						"ancestor": $("#role_parent_" + key).text()
					};
				}
			});

			localStorage.setItem('circlestructure', JSON.stringify(removeCircularReferences(root)));
			refreshCircle();
			closePopup();
		});
	});
</script>
<?
?>
