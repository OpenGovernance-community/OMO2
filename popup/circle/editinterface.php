<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();

	function circleEscape($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	function circleParseListValue($rawValue) {
		$rawValue = trim((string)$rawValue);
		if ($rawValue === '') {
			return array();
		}

		$decoded = json_decode($rawValue, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
			return array_values($decoded);
		}

		$items = preg_split('/\r\n|\r|\n|\|/', $rawValue);
		return array_values(array_filter(array_map('trim', $items), function($item) {
			return $item !== '';
		}));
	}

	function circleMergeListValues($ancestorValue, $currentValue) {
		$merged = array();
		$seen = array();

		foreach (array_merge(circleParseListValue($ancestorValue), circleParseListValue($currentValue)) as $item) {
			$key = is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : trim((string)$item);
			if ($key === '' || isset($seen[$key])) {
				continue;
			}

			$seen[$key] = true;
			$merged[] = $item;
		}

		return $merged;
	}

	function circleFormatScalarValue($property, $rawValue) {
		$rawValue = trim((string)$rawValue);
		if ($rawValue === '') {
			return '';
		}

		if ((int)$property->get('IDpropertyformat') === \dbObject\PropertyFormat::FORMAT_DATE) {
			try {
				return (new DateTime($rawValue))->format('d.m.Y');
			} catch (Exception $exception) {
				return $rawValue;
			}
		}

		return $rawValue;
	}

	function circleFormatListPreviewValue($property, $items) {
		$listItemType = trim((string)$property->get('listitemtype'));
		$items = is_array($items) ? $items : circleParseListValue($items);
		if (count($items) === 0) {
			return '';
		}

		$formattedItems = array();
		foreach ($items as $item) {
			if ($listItemType === \dbObject\Property::LIST_ITEM_HOLON) {
				$holon = new \dbObject\Holon();
				$holonId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
				if ($holonId > 0 && $holon->load($holonId)) {
					$formattedItems[] = $holon->getDisplayName();
				}
			} elseif ($listItemType === \dbObject\Property::LIST_ITEM_DATE) {
				$formattedItems[] = circleFormatScalarValue($property, $item);
			} else {
				$formattedItems[] = is_array($item) ? '' : trim((string)$item);
			}
		}

		return implode("\n", array_values(array_filter($formattedItems)));
	}

	function circleRenderPreviewValue($property, $rawValue) {
		$formatId = (int)$property->get('IDpropertyformat');
		if ($formatId === \dbObject\PropertyFormat::FORMAT_LIST) {
			return circleFormatListPreviewValue($property, $rawValue);
		}
		return circleFormatScalarValue($property, $rawValue);
	}

	function circleBuildEffectivePreviewValue($property, $ancestorValue, $currentValue) {
		$hasInheritedDefinition = trim((string)$property->get("list_parent")) !== '' || trim((string)$property->get("value_parents")) !== '';
		$isLockedByInheritance = (bool)$property->get("locked") && $hasInheritedDefinition;

		$formatId = (int)$property->get('IDpropertyformat');
		if ($formatId === \dbObject\PropertyFormat::FORMAT_LIST) {
			if ($isLockedByInheritance) {
				return circleFormatListPreviewValue($property, $ancestorValue);
			}
			return circleFormatListPreviewValue($property, circleMergeListValues($ancestorValue, $currentValue));
		}

		if ($isLockedByInheritance) {
			return circleRenderPreviewValue($property, $ancestorValue);
		}

		$currentValue = trim((string)$currentValue);
		if ($currentValue !== '') {
			return circleRenderPreviewValue($property, $currentValue);
		}

		return circleRenderPreviewValue($property, $ancestorValue);
	}

	function circleGetHolonReferenceOptions($property, $referenceHolon) {
		$rootHolonId = (int)$referenceHolon->get('IDholon_org');
		if ($rootHolonId <= 0) {
			$rootHolonId = (int)$referenceHolon->getId();
		}

		if ($rootHolonId <= 0) {
			return array();
		}

		$allowedTypeIds = \dbObject\Property::parseHolonTypeIds($property->get('listholontypeids'));
		$filter = 'active = 1 and IDholon_org = ' . $rootHolonId;
		if (method_exists($referenceHolon, 'isTemplateNode') && $referenceHolon->isTemplateNode($rootHolonId)) {
			$filter .= ' and (visible = 0 or (templatename is not null and templatename != ""))';
		} else {
			$filter .= ' and visible = 1';
		}

		if (count($allowedTypeIds) > 0) {
			$filter .= ' and IDtypeholon in (' . implode(',', $allowedTypeIds) . ')';
		}

		$holons = new \dbObject\ArrayHolon();
		$holons->load(array(
			'filter' => $filter,
			'orderBy' => array(
				array('field' => 'IDtypeholon', 'dir' => 'ASC'),
				array('field' => 'name', 'dir' => 'ASC'),
				array('field' => 'id', 'dir' => 'ASC'),
			),
		));

		$options = array();
		foreach ($holons as $holon) {
			$options[] = array(
				'id' => (int)$holon->getId(),
				'label' => $holon->getDisplayName(),
				'type' => $holon->getTypeLabel(),
			);
		}

		return $options;
	}

	function circleGetListInputType($listItemType) {
		switch ($listItemType) {
			case \dbObject\Property::LIST_ITEM_NUMBER:
				return 'number';
			case \dbObject\Property::LIST_ITEM_DATE:
				return 'date';
			default:
				return 'text';
		}
	}

	function circleRenderListField($property) {
		$propertyId = (int)$property->get("IDproperty");
		$listItemType = \dbObject\Property::normalizeListItemType($property->get('listitemtype'));
		$inputType = circleGetListInputType($listItemType);
		$placeholder = circleEscape($property->get("name"));
		$step = $inputType === 'number' ? " step='any'" : '';

		return ""
			. "<div class='role-list-field' id='role_field_".$propertyId."' data-field-mode='json-list' data-list-item-type='".circleEscape($listItemType)."'>"
			. "  <div class='role-list-field__items'>"
			. "      <div class='role-list-field__row'>"
			. "          <input type='".$inputType."' class='role-list-field__value' placeholder='".$placeholder."'".$step.">"
			. "          <button type='button' class='role-list-field__remove' data-list-remove='1' aria-label='Supprimer'>&times;</button>"
			. "      </div>"
			. "  </div>"
			. "  <button type='button' class='role-list-field__add' data-list-add='1'>+</button>"
			. "</div>";
	}

	function circleIsPropertyLocked($property) {
		$hasInheritedDefinition = trim((string)$property->get("list_parent")) !== '' || trim((string)$property->get("value_parents")) !== '';
		if (!(bool)$property->get("locked")) {
			return false;
		}

		return $hasInheritedDefinition;
	}

	function circleRenderReadonlyField() {
		return "<div class='role-field-readonly'>Valeur héritée du modèle</div>";
	}

	function circleRenderPropertyInput($property, $referenceHolon) {
		$propertyId = (int)$property->get("IDproperty");
		$placeholder = circleEscape($property->get("name"));
		$formatId = (int)$property->get("IDpropertyformat");
		$style = "style='border-top:0px; border-radius:0px 0px 3px 3px'";

		if (circleIsPropertyLocked($property)) {
			return circleRenderReadonlyField();
		}

		switch ($formatId) {
			case \dbObject\PropertyFormat::FORMAT_NUMBER:
				return "<input type='number' step='any' data-field-mode='scalar' ".$style." id='role_field_".$propertyId."' placeholder='".$placeholder."'>";
			case \dbObject\PropertyFormat::FORMAT_DATE:
				return "<input type='date' data-field-mode='scalar' ".$style." id='role_field_".$propertyId."' placeholder='".$placeholder."'>";
			case \dbObject\PropertyFormat::FORMAT_LIST:
				$listItemType = \dbObject\Property::normalizeListItemType($property->get('listitemtype'));
				if ($listItemType === \dbObject\Property::LIST_ITEM_HOLON) {
					$options = circleGetHolonReferenceOptions($property, $referenceHolon);
					$html = "<select multiple data-field-mode='json-holon-list' ".$style." id='role_field_".$propertyId."'>";
					foreach ($options as $option) {
						$html .= "<option value='".$option['id']."'>".circleEscape($option['label']." (".$option['type'].")")."</option>";
					}
					$html .= "</select>";
					return $html;
				}
				return circleRenderListField($property);
			case \dbObject\PropertyFormat::FORMAT_TEXT:
			default:
				return "<textarea data-field-mode='scalar' ".$style." id='role_field_".$propertyId."' placeholder='".$placeholder."'></textarea>";
		}
	}

?>
<style>
	.role-list-field {
		display: flex;
		flex-direction: column;
		gap: 8px;
		padding: 8px 0 0;
	}

	.role-list-field__items {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}

	.role-list-field__row {
		display: grid;
		grid-template-columns: minmax(0, 1fr) 34px;
		gap: 8px;
		align-items: center;
	}

	.role-list-field__row input {
		width: 100%;
	}

	.role-list-field__add,
	.role-list-field__remove {
		border: 1px solid #d0d7de;
		background: #fff;
		border-radius: 8px;
		cursor: pointer;
		font: inherit;
		height: 34px;
	}

	.role-list-field__add {
		width: 34px;
		align-self: flex-start;
		font-size: 1.1rem;
		line-height: 1;
	}

	.role-field-readonly {
		padding: 9px 10px;
		border: 1px dashed #d0d7de;
		border-top: 0;
		border-radius: 0 0 8px 8px;
		background: #f8fafc;
		color: #64748b;
		font-style: italic;
	}
</style>
<?
	if (isset($_GET["p"]) && $_GET["p"]!="") {
		$templates=new \dbObject\ArrayHolon();
		$params= array();
		$params["filter"] = "templatename is not null and IDholon_parent in (".$_GET["p"].")";
		$templates->load($params);
		echo "<select id='selected_template'>";
		echo "<option>Choisissez...</option>";
		foreach ($templates as $template) {
			echo "<option value='".$template->get("id")."'";
			if (isset($_GET["t"]) && $_GET["t"]==$template->get("id")) { echo " selected"; $selectedTemplate=$template;}
			echo ">".circleEscape($template->get("templatename"))."</option>";
		}
		echo "</select>";
?>
		<script>
			$("#selected_template").change(function() {
				$("#form_new_node").load("/popup/circle/editinterface.php?p="+$parents+"&t="+$(this).val());
			});
		</script>
<?
	}

	if (isset($_GET["t"]) && $_GET["t"]>0) {
		if (!isset($selectedTemplate)) {
			$selectedTemplate=new \dbObject\Holon();
			$selectedTemplate->load($_GET["t"]);
		}

		echo "<input type='hidden' id='type_role' value='".$selectedTemplate->get("IDtypeholon")."'>";
		echo $selectedTemplate->getString("IDtypeholon");

		echo "<div>Nom:</div>";
		echo "<input id='role_field_name' placeholder='Nom'>";

		$properties=$selectedTemplate->getPropertiesValue();
		foreach ($properties as $property) {
			$effectivePreview = circleBuildEffectivePreviewValue($property, $property->get("value_parents"), $property->get("value"));
			echo "<div>".circleEscape($property->get("name")).":</div>";
			echo "<div id='role_parent_".$property->get("IDproperty")."' style='white-space: pre-line;border:1px solid lightgrey; border-bottom:0px; background:#f9f9f9; border-radius:3px 3px 0px 0px; padding:5px;'>".str_replace("'","&apos;",circleEscape($effectivePreview))."</div>";
			echo circleRenderPropertyInput($property, $selectedTemplate);
		}
	}

	if (isset($_GET["n"]) && $_GET["n"]>0) {
		if (!isset($selectedTemplate)) {
			$selectedTemplate=new \dbObject\Holon();
			$selectedTemplate->load($_GET["n"]);
		}

		echo "<input type='hidden' id='type_role' value='".$selectedTemplate->get("IDtypeholon")."'>";

		echo "<div>Nom:</div>";
		echo "<input id='role_field_name' placeholder='Nom'>";

		$properties=$selectedTemplate->getPropertiesValue();
		foreach ($properties as $property) {
			$ancestorPreview = circleRenderPreviewValue($property, $property->get("value_parents"));
			echo "<div>".circleEscape($property->get("name")).":</div>";
			echo "<div id='role_parent_".$property->get("IDproperty")."' style='white-space: pre-line;border:1px solid lightgrey; border-bottom:0px; background:#f9f9f9; border-radius:3px 3px 0px 0px; padding:5px;'>".str_replace("'","&apos;",circleEscape($ancestorPreview))."</div>";
			echo circleRenderPropertyInput($property, $selectedTemplate);
		}
	}
?>
