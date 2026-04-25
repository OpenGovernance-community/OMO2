<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Initialise le login
	$connected=checklogin();

	// Affichage des champs de formulaires
	echo "<div id='form_new_node'></div>";
	echo "<button id='btn_create_role'>Ajouter</button>";
?>
<script>
	function getRef(node) {
	    if (node.IDdb !== undefined && node.IDdb !== null && node.IDdb !== "" && !isNaN(Number(node.IDdb))) return node.IDdb;
		if (node.t !== undefined && node.t !== null && node.t !== "" && !isNaN(Number(node.t))) {
			if (node.ID !== undefined && node.ID !== null && node.ID !== "" && !isNaN(Number(node.ID))) return node.ID+","+node.t;
			return node.t;
		}

		if (node.ID !== undefined && node.ID !== null && node.ID !== "" && !isNaN(Number(node.ID))) {
			return node.ID;
		}

		return null;
	}

	function getParentsID(node, sep="") {
		ref = getRef(node);
		if (node.parent) {
			if (ref) {
				return sep + ref + getParentsID(node.parent, ",");
			}
			return getParentsID(node.parent, ",");
		}

		if (ref) {
			return sep + ref;
		}

		return "";
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
		$("#form_new_node").on("click", "[data-list-add]", function () {
			const $field = $(this).closest(".role-list-field");
			$field.find(".role-list-field__items").append(createListFieldRowHtml($field.data("list-item-type"), ""));
			$field.find(".role-list-field__value").last().trigger("focus");
		});

		$("#form_new_node").on("click", "[data-list-remove]", function () {
			const $field = $(this).closest(".role-list-field");
			$(this).closest(".role-list-field__row").remove();
			ensureListFieldHasRow($field);
		});

		$parents = getParentsID(currentnode);
		if (!$parents) {
			$parents = root.ID;
		}

		$("#form_new_node").load("/popup/circle/editinterface.php?p="+$parents);

		$("#btn_create_role").click(function () {
			if (currentnode) {
				if (!currentnode.children) {
					currentnode.children = new Array();
				}

				const newNode = {
					ID: "TMP_" + Date.now(),
					type: $("#type_role").val(),
					size: 10 - currentnode.deph * 2,
					t: $("#selected_template").val(),
					data: {},
				};

				$("[id^='role_field_']").each(function() {
					const $field = $(this);
					const elementId = $field.attr("id");
					const key = elementId.replace("role_field_", "");

					if (key === "name") {
						newNode[key] = $field.val();
					} else {
						newNode.data["d" + key] = {
							"value": readRoleFieldValue($field),
							"ancestor": $("#role_parent_" + key).text()
						};
					}
				});

				currentnode.children.push(newNode);

				save();
				refreshCircle();
				closePopup();
			}
		});
	});
</script>
<?
?>
