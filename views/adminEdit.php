<!--
	USAGE EXAMPLE:
	
	$params=array(
		"fields" => array(array("id","IDadministrateur_charge"),array("date","[heurerendezvous] [rue] [npa] [localite]"),array("IDadresse","todo")),
		"page" => $_GET["page"],
	);


-->
<?php
//error_reporting(E_ALL | E_ALL);
require_once dirname(__DIR__) . '/common/leaflet_helper.php';
require_once dirname(__DIR__) . '/common/admin_edit_translation.php';
require_once dirname(__DIR__) . '/common/assets.php';
?>
<?php if (!isset($params['includeComponentAssets']) || $params['includeComponentAssets'] !== false) { ?>
<link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/components.css') ?>">
<?php } ?>
<script src="/common/choice/highlight-palette.js?v=20260904-highlight-clear"></script>
<link rel="stylesheet" href="<?= commonAssetUrl('/common/assets/admin-edit.css') ?>">
<script>

    function previewFile(input, source) {
        var file = source.files[0];
        if (!file || !source.form) return;
        var preview = source.form.querySelector('#img_' + CSS.escape(input));
        var valueField = source.form.elements.namedItem(input);
        var reader = new FileReader();

        reader.addEventListener("load", function () {
            if (preview) {
                preview.style.backgroundImage = 'url("' + reader.result + '")';
                preview.hidden = false;
            }
            if (valueField) valueField.value = file.name;
        }, false);

        reader.readAsDataURL(file);
    }

</script>
<?php
// Default parameter values
if (!isset($params["displayDraft"])) {
    $params["displayDraft"] = false;
}        // Show save button without validation
if (!isset($params["buttons"])) {
    $params["buttons"] = true;
}                // Navigation buttons

function adminEditMergeClass($baseClass, $extraClass = '') {
    $classes = trim((string)$baseClass);
    $extraClass = trim((string)$extraClass);
    if ($extraClass !== '') {
        $classes .= ($classes !== '' ? ' ' : '') . $extraClass;
    }

    return trim($classes);
}

function adminEditIsHexColor($value) {
    return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim((string)$value)) === 1;
}

function adminEditNormalizeColorValue($value, $fallback = '#004663') {
    $value = trim((string)$value);
    if (!adminEditIsHexColor($value)) {
        return strtolower((string)$fallback);
    }

    $value = strtolower($value);
    if (strlen($value) === 4) {
        return '#'
            . $value[1] . $value[1]
            . $value[2] . $value[2]
            . $value[3] . $value[3];
    }

    return $value;
}

function adminEditFormatTemporalValue($value, $format) {
    if ($value instanceof DateTimeInterface) {
        return $value->format($format);
    }

    if (is_string($value) && trim($value) !== '') {
        try {
            $date = new DateTime($value);
            return $date->format($format);
        } catch (Throwable $exception) {
            return '';
        }
    }

    if (is_numeric($value)) {
        try {
            $date = new DateTime('@' . (int)$value);
            return $date->format($format);
        } catch (Throwable $exception) {
            return '';
        }
    }

    return '';
}

function adminEditBuildTemporalInput($type, $name, $value, $class, $disabled = false, $attributes = []) {
    $html = "<input class='" . $class . "' name='" . $name . "' id='" . $name . "' type='" . $type . "'";

    if ($value !== '') {
        $html .= " value='" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "'";
    }

    foreach ($attributes as $attributeName => $attributeValue) {
        if ($attributeValue === null || $attributeValue === false || $attributeValue === '') {
            continue;
        }

        if ($attributeValue === true) {
            $html .= ' ' . $attributeName;
            continue;
        }

        $html .= ' ' . $attributeName . "='" . htmlspecialchars((string)$attributeValue, ENT_QUOTES, 'UTF-8') . "'";
    }

    if ($disabled) {
        $html .= ' disabled';
    }

    $html .= '>';

    return $html;
}

function adminEditResolveImageDisplaySize($object, $key) {
    $displayWidth = 200;
    $displayHeight = 200;
    $lengths = method_exists($object, 'attributeLength') ? $object::attributeLength() : array();
    $sizeConfig = $lengths[$key] ?? null;

    if (is_array($sizeConfig)) {
        if (isset($sizeConfig[0]) && is_array($sizeConfig[0])) {
            $displayWidth = isset($sizeConfig[1][0]) ? (int)$sizeConfig[1][0] : (isset($sizeConfig[0][0]) ? (int)$sizeConfig[0][0] : $displayWidth);
            $displayHeight = isset($sizeConfig[1][1]) ? (int)$sizeConfig[1][1] : (isset($sizeConfig[0][1]) ? (int)$sizeConfig[0][1] : $displayHeight);
        } else {
            $displayWidth = isset($sizeConfig[0]) ? (int)$sizeConfig[0] : $displayWidth;
            $displayHeight = isset($sizeConfig[1]) ? (int)$sizeConfig[1] : $displayHeight;
        }
    }

    if ($displayWidth <= 0) {
        $displayWidth = 200;
    }
    if ($displayHeight <= 0) {
        $displayHeight = 200;
    }

    return array($displayWidth, $displayHeight);
}

function adminEditLegacyEscape($value) {
    return str_replace("'", "&apos;", (string)$value);
}

function adminEditPlaceholderText($object, $key, ?array $translationBundle = null, ?array $translationSourceLang = null) {
    $placeholder = adminEditGetFieldPlaceholder($object, (string)$key, $translationBundle ?? adminEditLoadBundle($object), $translationSourceLang ?? adminEditBuildSourceLang($object));

    return $placeholder !== '' ? $placeholder : '';
}

function adminEditLengthText($object, $count, ?array $translationBundle = null, ?array $translationSourceLang = null) {
    return adminEditTranslate(
        'admin_edit.length.max',
        ['count' => (int)$count],
        $object,
        $translationBundle,
        $translationSourceLang
    );
}

function adminEditFieldHeading($object, $field, ?array $translationBundle = null, ?array $translationSourceLang = null) {
    $label = adminEditGetFieldLabel($object, (string)$field, $translationBundle ?? adminEditLoadBundle($object), $translationSourceLang ?? adminEditBuildSourceLang($object));
    $description = adminEditGetFieldDescription($object, (string)$field, $translationBundle ?? adminEditLoadBundle($object), $translationSourceLang ?? adminEditBuildSourceLang($object));
    $html = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    if ($description !== '') {
        $html .= "<sup class='field_help' title=\"" . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "\">?</sup>";
    }

    return $html;
}

function getFieldType($object, $key) {
    if (is_object($object)) {
        // Find rows linked to the type
        $params = array_filter($object->rules(), function ($ar) use ($key) {
            return (array_search($key, $ar[0]) !== false);
        });
        foreach ($params as $param) {
            switch ($param[1]) {
                case "date" :
                    return "date";
                case "daterange" :
                    return "daterange";
                case "password" :
                    return "password";
                case "image" :
                    return "image";
                case "sizedimage" :
                    return "sizedimage";
                case "integer" :
                    return "integer";
                case "cursor" :
                    return "cursor";
                case "eval" :
                    return "eval";
                case "latlong" :
                    return "latlong";
                case "float" :
                    return "float";
                case "color" :
                    return "colorpicker";
                case "fk" :
                    return "fk";
                case "text" :
                    return "text";
                case "boolean" :
                    return "boolean";
                case "html" :
                    return "html";
                case "timezone" :
                    return "timezone";
            }
        }

        return "string";
    } else {
        return "undefined";
    }
}

function displayField($object, $key, $default = null, $filter = null, ?array $translationBundle = null, ?array $translationSourceLang = null) {

    $type = $object->getFieldType($key);
    $class = adminEditMergeClass(($object->isRequired($key) ? "required" : ""), "admin-edit__control generic-form-control");
    switch ($type) {
        case "fk" :
            // Return this field's text value
            $txt = "<select class='" . $class . "' name='" . $key . "' id='" . $key . "' ><option value=''>"
                . htmlspecialchars(adminEditTranslate('admin_edit.choice.select', [], $object, $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8')
                . "</option>";

            // Load values and render them
            foreach ($object->getValues($key, $filter) as $value) {
                $txt .= "<option value='" . $value->getId() . "'" . ($default == $value->getId() || (is_null($default) && $value->getId() == $object->get($key)) ? " selected" : "") . ">" . $value->getLabel() . "</option>";
            }
            $txt .= "</select>";

            return $txt;
            break;
        case "date" :
            return adminEditBuildTemporalInput(
                'date',
                $key,
                adminEditFormatTemporalValue($object->get($key), 'Y-m-d'),
                $class,
                $object->isProtected($key)
            );
            break;
        case "time" :
            return adminEditBuildTemporalInput(
                'time',
                $key,
                adminEditFormatTemporalValue($object->get($key), 'H:i'),
                $class,
                $object->isProtected($key)
            );
            break;
        case "datetime" :
            return adminEditBuildTemporalInput(
                'datetime-local',
                $key,
                adminEditFormatTemporalValue($object->get($key), 'Y-m-d\TH:i'),
                $class,
                $object->isProtected($key)
            );
            break;
        case "daterange" :
            $rangeStartValue = $object->get($key) ? $object->get($key) : $default;
            $rangeEndValue = $object->get($key . "_fin") ? $object->get($key . "_fin") : $default;

            return "<div class='admin-edit__date-range'>"
                . adminEditBuildTemporalInput(
                    'datetime-local',
                    $key,
                    adminEditFormatTemporalValue($rangeStartValue, 'Y-m-d\TH:i'),
                    $class,
                    $object->isProtected($key)
                )
                . adminEditBuildTemporalInput(
                    'datetime-local',
                    $key . "_fin",
                    adminEditFormatTemporalValue($rangeEndValue, 'Y-m-d\TH:i'),
                    $class,
                    $object->isProtected($key)
                )
                . "</div>";
            break;
        case "timezone" :
            $str = "<select class='" . $class . "' name='" . $key . "' id='" . $key . "'>";

            $timezones = timezone_identifiers_list();
            foreach ($timezones as $timezone) {
                $tz = (new DateTimeZone($timezone))->getOffset(new DateTime()) / 3600;
                $str .= '<option value="' . $timezone . '" ' . ($object->get($key) == $timezone ? "selected" : "") . '>' . $timezone . " (UTC" . ($tz >= 0 ? "+" : "") . ($tz) . ")" . '</option>';
            }

            $str .= "</select>";

            return $str;
            break;
        case "cursor" :
            if (isset($object::attributeLength()[$key])) {
                if (is_array($object::attributeLength()[$key])) {
                    $min = $object::attributeLength()[$key][0];
                    $max = $object::attributeLength()[$key][1];
                } else {
                    $min = 0;
                    $max = $object::attributeLength()[$key];
                }
            } else {
                $min = 0;
                $max = 100; // fallback
            }

            $value = $object->get($key);

            $str .= "<input type='range' 
								name='" . $key . "' 
								min='" . $min . "' 
								max='" . $max . "' 
								value='" . $value . "' 
								>";

            // field to show the live value
            return $str;
            break;
        case "latlong" :
            $leafletMapsEnabled = function_exists('commonLeafletMapsEnabled') && commonLeafletMapsEnabled();
            $leafletAssets = '';
            if ($leafletMapsEnabled && function_exists('commonRenderLeafletAssets')) {
                ob_start();
                commonRenderLeafletAssets();
                $leafletAssets = ob_get_clean();
            }

            $latitude = 0.0;
            $longitude = 0.0;
            $hasCoordinates = false;
            $latlongValue = $object->get($key);
            if (is_object($latlongValue)) {
                $rawLatitude = $latlongValue->lat ?? null;
                $rawLongitude = $latlongValue->long ?? null;
                $hasCoordinates = is_numeric($rawLatitude) && is_numeric($rawLongitude);
                if ($hasCoordinates) {
                    $latitude = (float)$rawLatitude;
                    $longitude = (float)$rawLongitude;
                }
            }

            $str = $leafletAssets;
            $str .= "<div class='admin-edit__latlong-grid'>";
            $str .= "<input class='" . $class . "' name='" . $key . "[]' id='" . $key . "_lat' type='text' value='" . htmlspecialchars((string)($hasCoordinates ? $latitude : ''), ENT_QUOTES, 'UTF-8') . "' placeholder='" . htmlspecialchars(adminEditTranslate('admin_edit.latlong.placeholder.latitude', [], $object, $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "'>";
            $str .= "<input class='" . $class . "' name='" . $key . "[]' id='" . $key . "_long' type='text' value='" . htmlspecialchars((string)($hasCoordinates ? $longitude : ''), ENT_QUOTES, 'UTF-8') . "' placeholder='" . htmlspecialchars(adminEditTranslate('admin_edit.latlong.placeholder.longitude', [], $object, $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "'>";
            $str .= "</div>";
            if (!$leafletMapsEnabled) {
                $str .= "<div class='admin-edit__latlong-help'>" . htmlspecialchars(adminEditTranslate('admin_edit.latlong.help.manual', [], $object, $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "</div>";
                return $str;
            }
            $str .= "<div id='map_" . $key . "' class='admin-edit__latlong-map'></div>";
            $str .= "<div class='admin-edit__latlong-help'>" . htmlspecialchars(adminEditTranslate('admin_edit.latlong.help.map', [], $object, $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "</div>";
            $str .= commonPageScriptTags('/common/assets/admin-edit-map.js', [
                'key' => $key,
                'initialLat' => $hasCoordinates ? $latitude : null,
                'initialLng' => $hasCoordinates ? $longitude : null,
            ]);

            return $str;

            break;
        case "eval" :
            $str = "<input type='hidden' name='" . $key . "' id='" . $key . "' value='" . $object->get($key) . "'";
            $str .= ">";
            $str .= '<div class="star-rating" data-input="' . $key . '">';
            for ($i = 1; $i <= 5; $i++) {
                $str .= '<span data-value="' . $i . '"' . ($i <= $object->get($key) ? " class='active'" : "") . '>★</span>';
            }
            $str .= '</div>';

            return $str;
        case "integer" :
        case "float" :
            // Predefined values: use select
            if (isset($object::attributeValues()[$key])) {
                $str = "<select class='" . $class . "' name='" . $key . "' id='" . $key . "'>";
                foreach ($object::attributeValues()[$key] as $option) {
                    $str .= "<option value='" . $option[0] . "' " . ($option[0] == $object->get($key) ? "selected" : "") . ">" . $option[1] . "</option>";
                }
                $str .= '</select>';

                return $str;
            }
            // Otherwise return a plain field
            $str = "<input class='" . $class . "' name='" . $key . "' id='" . $key . "' type='text' value='" . $object->get($key) . "'";
            $translatedPlaceholder = adminEditPlaceholderText($object, $key, $translationBundle, $translationSourceLang);
            if ($translatedPlaceholder !== '') {
                $str .= " placeholder='" . adminEditLegacyEscape($translatedPlaceholder) . "' ";
            }
            $str .= ">";

            return $str;
            break;
        case "colorpicker":
            $colorValue = ($object->get($key) != "" ? $object->get($key) : $default);
            $colorValue = trim((string)($colorValue ?? ""));
            $colorPickerValue = adminEditNormalizeColorValue($colorValue);
            $colorTextClass = adminEditMergeClass($class, "admin-edit__color-text");
            $str = "<div class='admin-edit__color-field'>";
            $str .= "<input type='hidden' name='" . $key . "' id='" . $key . "' value='" . str_replace("'", "&apos;", $colorValue) . "'>";
            $str .= "<input type='color' class='admin-edit__color-picker' id='" . $key . "_picker' value='" . $colorPickerValue . "' data-target='" . $key . "' data-text-target='" . $key . "_text'>";
            $str .= "<input class='" . $colorTextClass . "' name='" . $key . "_text' id='" . $key . "_text' type='text' value='" . str_replace("'", "&apos;", $colorValue) . "' data-target='" . $key . "' data-picker-target='" . $key . "_picker'";
            $translatedPlaceholder = adminEditPlaceholderText($object, $key, $translationBundle, $translationSourceLang);
            if ($translatedPlaceholder !== '') {
                $str .= " placeholder='" . adminEditLegacyEscape($translatedPlaceholder) . "' ";
            } else {
                $str .= " placeholder='#004663' ";
            }
            if (isset($object::attributeLength()[$key])) {
                $str .= "maxlength='" . $object::attributeLength()[$key] . "'  onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' >";
                $str .= "<div class='char_count'>" . htmlspecialchars(adminEditLengthText($object, $object::attributeLength()[$key], $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "</div>";
            } else {
                $str .= ">";
            }
            $str .= "</div>";

            return $str;
            break;
        case "color":
            $colorValue = ($object->get($key) != "" ? $object->get($key) : $default);
            $str = "<input  type='color' class='" . $class . "' name='" . $key . "' id='" . $key . "' style='width:50px' type='text' value='" . str_replace("'", "&apos;", (string)($colorValue ?? "")) . "'";
            $translatedPlaceholder = adminEditPlaceholderText($object, $key, $translationBundle, $translationSourceLang);
            if ($translatedPlaceholder !== '') {
                $str .= " placeholder='" . adminEditLegacyEscape($translatedPlaceholder) . "' ";
            }
            if (isset($object::attributeLength()[$key])) {
                $str .= "maxlength='" . $object::attributeLength()[$key] . "'  onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' ><div class='char_count'>" . htmlspecialchars(adminEditLengthText($object, $object::attributeLength()[$key], $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "</div>";
            } else {
                $str .= ">";
            }

            return $str;
            break;
        case "mail" :
        case "string" :
            if (isset($object::attributeValues()[$key])) {
                $str = "<select class='" . $class . "' name='" . $key . "' id='" . $key . "'>";
                foreach ($object::attributeValues()[$key] as $option) {
                    $str .= "<option value='" . $option[0] . "' " . ($option[0] == $object->get($key) ? "selected" : "") . ">" . $option[1] . "</option>";
                }
                $str .= '</select>';

                return $str;
            }
            $fieldValue = ($object->get($key) != "" ? $object->get($key) : $default);
            $str = "<input  class='" . $class . "' name='" . $key . "' id='" . $key . "' style='width:100%' type='text' value='" . str_replace("'", "&apos;", (string)($fieldValue ?? "")) . "'";
            $translatedPlaceholder = adminEditPlaceholderText($object, $key, $translationBundle, $translationSourceLang);
            if ($translatedPlaceholder !== '') {
                $str .= " placeholder='" . adminEditLegacyEscape($translatedPlaceholder) . "' ";
            }
            if (isset($object::attributeLength()[$key])) {
                $str .= "maxlength='" . $object::attributeLength()[$key] . "'  onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' ><div class='char_count'>" . htmlspecialchars(adminEditLengthText($object, $object::attributeLength()[$key], $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "</div>";
            } else {
                $str .= ">";
            }

            return $str;
            break;
        case "text" :
            $str = $object->get($key);
            $tmp = "<textarea class='" . $class . "' name='" . $key . "' id='" . $key . "' style='width:100%'";
            if (isset($object::attributeLength()[$key])) {
                $tmp .= "maxlength='" . $object::attributeLength()[$key] . "' onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' >" . $str . "</textarea><div class='char_count'>" . htmlspecialchars(adminEditLengthText($object, $object::attributeLength()[$key], $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "</div>";
            } else {
                $tmp .= ">" . $str . "</textarea>";
            }

            return $tmp;
        case "html" :
            $str = $object->get($key);
            $editorProfiles = method_exists($object, 'attributeHtmlEditorProfiles') ? $object::attributeHtmlEditorProfiles() : array();
            $editorProfile = isset($editorProfiles[$key]) ? trim((string)$editorProfiles[$key]) : '';
            $profileAttribute = $editorProfile !== ''
                ? " data-editor-profile='" . htmlspecialchars($editorProfile, ENT_QUOTES, 'UTF-8') . "'"
                : '';

            return "<textarea  class='" . adminEditMergeClass($class, "summernote") . "' name='" . $key . "' id='" . $key . "' style='width:100%'" . $profileAttribute . ">" . $str . "</textarea>";
            break;
        case "boolean" :
            return "<input type='hidden' id='" . $key . "' name='" . $key . "' value='0'>" .
                "<input type='checkbox' name='" . $key . "' id='" . $key . "'" . ($object->get($key) > 0 ? "checked" : "") . " value='1'>";
            break;
        case "image" :
            list($displayWidth, $displayHeight) = adminEditResolveImageDisplaySize($object, $key);
            $output = "<input name='" . $key . "' id='" . $key . "' type='hidden' value='" . str_replace("'", "&apos;", (string)($object->get($key) ?? "")) . "'>";
            $output .= "<input class='" . $class . "' name='" . $key . "_file' id='" . $key . "_file' type='file' onchange='previewFile(\"" . $key . "\",this)'><br>";
            $output .= "<div id='img_" . $key . "'" . (trim((string)$object->get($key)) === '' ? " hidden" : "") . " style='width:" . $displayWidth . "px; height:" . $displayHeight . "px; border:1px solid var(--color-border, #d1d5db); background:url(" . $object->get($key) . "); background-size:cover; background-position:center center'>";
            $output .= "<div id='drag_img_" . $key . "' class='drag_img' data='#img_" . $key . "' style='width:100%; height:100%;'>";
            $output .= "</div>";
            $output .= "</div>";

            return $output;
        // Resizable image
        case "sizedimage" :

            $sizes = $object::attributeLength();
            $sizeConfig = $sizes[$key] ?? null;

            $displayWidth = 200;
            $displayHeight = 200;

            // 🔥 Format handling
            if (is_array($sizeConfig)) {

                // New format: [[400,400],[200,200]]
                if (isset($sizeConfig[0]) && is_array($sizeConfig[0])) {

                    if (isset($sizeConfig[1])) {
                        $displayWidth = $sizeConfig[1][0] ?? 200;
                        $displayHeight = $sizeConfig[1][1] ?? 200;
                    } else {
                        // fallback to saved size
                        $displayWidth = $sizeConfig[0][0] ?? 200;
                        $displayHeight = $sizeConfig[0][1] ?? 200;
                    }
                    // Legacy format: [400,400]
                } else {
                    $displayWidth = $sizeConfig[0] ?? 200;
                    $displayHeight = $sizeConfig[1] ?? 200;
                }
            }

            $output = "<input type='hidden' id='" . $key . "' name='" . $key . "' value='" . $object->get($key) . "'>";

            $output .= "<div><input type='file' id='imageFileInput_" . $key . "' accept='image/*' style='display:none'>";
            $output .= "<input type='button' value='" . htmlspecialchars(adminEditTranslate('admin_edit.image.choose_disk', [], $object, $translationBundle, $translationSourceLang), ENT_QUOTES, 'UTF-8') . "' onclick='$(\"#imageFileInput_" . $key . "\").click();' />";
            $output .= "</div>";

            $output .= "<div id='imgContainer_" . $key . "' style='position: relative; display: inline-block; border: 1px solid black; cursor: move; overflow: hidden; width:" . $displayWidth . "px; height:" . $displayHeight . "px;'>";

            if ($object->get($key) != "") {
                $output .= "<img id='myImage_" . $key . "' style='display: block; position: absolute; top: 0px; left: 0px; object-fit: contain; width:" . $displayWidth . "px;' src='" . $object->get($key) . "'>";
            }

            $output .= "</div>";

            $output .= "<div>";
            $output .= "    <input type='range' id='zoomSlider_" . $key . "' min='0' max='100' step='1' value='0'>";
            $output .= "</div>";

            //$output.="<input type='hidden' id='imageDataInput_".$key."' name='imageDataInput_".$key."'>";
            ob_start();
            ?>
            <?= commonPageScriptTags('/common/assets/admin-edit-crop.js', [
    'key' => $key,
]) ?>
            <?php
            $output .= ob_get_clean();

            return $output;
        case "password" :
            return "*****";
        case "undefined" :
            return "";
        default:
            return $object->get($key);
    }
}

// Load object metadata
$colonnes = $this->attributeLabels();
$adminEditTranslationSourceLang = adminEditBuildSourceLang($this);
$adminEditTranslationBundle = adminEditLoadBundle($this);
$adminEditCharCountTemplate = adminEditTranslate('admin_edit.length.progress', ['current' => '__CURRENT__', 'limit' => '__LIMIT__'], $this, $adminEditTranslationBundle, $adminEditTranslationSourceLang);
?>
<script>
    var adminEditCharCountTemplate = <?= json_encode($adminEditCharCountTemplate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    // generic validation helpers
    function countChar(objet, limit) {
        if (objet.val().length > limit) {
            objet.val(objet.val().substr(0, limit));
        }
        objet.nextAll(".char_count").html(objet.val().length + " sur " + limit + " caractères");
    }
</script>


<?php

// Header
$adminEditToolbarTitle = $this->getId() != ""
    ? adminEditTranslate('admin_edit.toolbar.edit', [], $this, $adminEditTranslationBundle, $adminEditTranslationSourceLang)
    : adminEditTranslate('admin_edit.toolbar.create', [], $this, $adminEditTranslationBundle, $adminEditTranslationSourceLang);
echo "<div class='admin-edit'>";
echo "<form id='formulaire-edit' class='generic-form-stack' method='POST' enctype='multipart/form-data'";
if (isset($params["action"]) && $params["action"]) {
    echo " action='" . $params["action"] . "'";
}
echo ">";
echo "<input type='hidden' name='MAX_FILE_SIZE' value='300000000' />";

// Navigation buttons
if ($params["buttons"]) {
    echo "<div class='admin-edit__toolbar'><div class='admin-edit__toolbar-inner'><div class='admin-edit__toolbar-copy'><h2 class='admin-edit__toolbar-title'>" . htmlspecialchars($adminEditToolbarTitle, ENT_QUOTES, 'UTF-8') . "</h2><p class='admin-edit__toolbar-text'>" . htmlspecialchars(adminEditTranslate('admin_edit.toolbar.text', [], $this, $adminEditTranslationBundle, $adminEditTranslationSourceLang), ENT_QUOTES, 'UTF-8') . "</p></div><div class='admin-edit__actions generic-form-actions generic-form-actions--stack-mobile'><input type='button' class='generic-action-button generic-action-button--secondary admin-edit__action--secondary' value='" . htmlspecialchars(adminEditTranslate('admin_edit.action.cancel', [], $this, $adminEditTranslationBundle, $adminEditTranslationSourceLang), ENT_QUOTES, 'UTF-8') . "' onclick='history.go(-1)'> <input id='btn_submit' class='generic-action-button generic-action-button--main' type='button' value='" . htmlspecialchars(adminEditTranslate('admin_edit.action.save', [], $this, $adminEditTranslationBundle, $adminEditTranslationSourceLang), ENT_QUOTES, 'UTF-8') . "'>";

    if ($params["displayDraft"]) {
        echo "<input id='btn_save' class='generic-action-button generic-action-button--secondary admin-edit__draft-button' type='button' value='" . htmlspecialchars(adminEditTranslate('admin_edit.action.save_draft', [], $this, $adminEditTranslationBundle, $adminEditTranslationSourceLang), ENT_QUOTES, 'UTF-8') . "'>";
    }
    echo "</div></div></div>";
}
$id = false;
$allowProtectedFields = !empty($params["allowProtectedFields"]);
// Optional sections keep the dbObject field widgets while using a drawer layout.
if (!empty($params['sections']) && is_array($params['sections'])) {
    foreach ($params['sections'] as $section) {
        $sectionFields = array_values(array_filter($section['fields'] ?? [], function ($field) use ($params, $colonnes, $allowProtectedFields) {
            return is_string($field) && isset($colonnes[$field])
                && (!isset($params['fields']) || in_array($field, $params['fields'], true))
                && ($allowProtectedFields || !$this->isProtected($field));
        }));
        if (!$sectionFields) continue;
        $collapsible = !empty($section['collapsible']);
        $title = htmlspecialchars((string)($section['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        echo $collapsible
            ? "<details class='generic-accordion'><summary>" . $title . "</summary><div class='generic-accordion__content generic-form-stack'>"
            : "<fieldset class='generic-fieldset'><legend class='generic-card-title generic-card-title--small'>" . $title . "</legend><div class='generic-fieldset__body'>";
        if (!empty($section['description'])) {
            echo "<p class='generic-help-text'>" . htmlspecialchars((string)$section['description'], ENT_QUOTES, 'UTF-8') . "</p>";
        }
        foreach ($sectionFields as $field) {
            if ($field === 'id') $id = true;
            $fieldId = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');
            $heading = adminEditFieldHeading($this, $field, $adminEditTranslationBundle, $adminEditTranslationSourceLang);
            $widget = displayField($this, $field, null, $params['filter'][$field] ?? null, $adminEditTranslationBundle, $adminEditTranslationSourceLang);
            echo "<div class='generic-form-field' id='row_" . $fieldId . "'>";
            if ($this->getFieldType($field) === 'boolean') {
                echo "<label class='generic-checkbox'>" . $widget . "<span class='generic-form-label'>" . $heading . "</span></label>";
            } else {
                $fieldType = $this->getFieldType($field);
                $labelFor = $fieldType === 'sizedimage' ? 'imageFileInput_' . $fieldId : ($fieldType === 'image' ? $fieldId . '_file' : $fieldId);
                echo "<label class='generic-form-label' for='" . $labelFor . "'>" . $heading . "</label><div class='admin-edit__field-content'>" . $widget . "</div>";
            }
            echo "</div>";
        }
        echo $collapsible ? "</div></details>" : "</div></fieldset>";
    }
} else {
echo "<div class='admin-edit__panel generic-soft-panel generic-soft-panel--stack'>";
echo "<table class='dbobjecttable'>";
$id = false;
$allowProtectedFields = !empty($params["allowProtectedFields"]);

// Visible-fields param passed
if (isset($params["fields"])) {
    // Requested fields only
    foreach ($params["fields"] as $colonne) {
        $hidden = false;
        $default = NULL;
        if (is_array($colonne)) {
            // Array: second element default or field?
            if (!isset($colonne[1]) || is_numeric($colonne[1]) || !isset($colonnes[$colonne[1]])) {
                if (isset($colonne[2])) {
                    $hidden = $colonne[2];
                }
                if (isset($colonne[1])) {
                    $default = $colonne[1];
                }
                $colonne = $colonne[0];
            }
        }
        // Only if field is active
        if (is_array($colonne)) {
            if ($allowProtectedFields || !$this->isProtected($colonne[0])) {
                if ($colonne[0] == "id") {
                    $id = true;
                }
                echo "<tr" . ($hidden ? " style='display:none'" : "") . " id='" . $colonne[0] . "'>";
                echo "<th style='white-space:nowrap'>" . adminEditFieldHeading($this, $colonne[0], $adminEditTranslationBundle, $adminEditTranslationSourceLang) . "</th>";
                echo "<td>";
                echo "<table><tr>";
                foreach ($colonne as $col) {
                    echo "<td>" . displayField($this, $col, $default, $params["filter"][$col] ?? null, $adminEditTranslationBundle, $adminEditTranslationSourceLang) . "</td>";
                }
                echo "</tr></table>";
                echo "</td>";
                echo "</tr>";
            }
        } else // Is this a separator (not a field)?
            if ($colonne[0] == "{") {
                echo "<tr><td colspan=2>";

                if (substr($colonne, 1, 3) == "hr}" || substr($colonne, 1, 3) == "hr:") {
                    echo "<hr>";
                }
                if (substr($colonne, 1, 6) == "title:") {
                    echo "<h1>" . substr($colonne, 7, strlen($colonne) - 8) . "</h1>";
                }
                if (substr($colonne, 1, 9) == "subtitle:") {
                    echo "<h1>" . substr($colonne, 10, strlen($colonne) - 11) . "</h1>";
                }
                if (substr($colonne, 1, 5) == "text:") {
                    echo "<p>" . substr($colonne, 6, strlen($colonne) - 7) . "</p>";
                }
            } else // Is there a function with this name to override rendering?
                if (function_exists('fct_' . $colonne)) {
                    $display = call_user_func('fct_' . $colonne, $this, $colonne, $default);

                    if ($colonne == "id") {
                        $id = true;
                    }
                    echo "<tr" . ($hidden ? " style='display:none'" : "") . " id='row_" . $colonne . "'>";
                    echo "<th>";
                    // Two objects in return chain
                    if (is_array($display)) {
                        if (count($display) > 1) {
                            if (is_array($display[0]) && count($display[0]) > 1) {
                                echo $display[0][0] . "<sup class='field_help' title=\"" . $display[0][1] . "\">?</sup>";
                            } else {
                                echo $display[0];
                            }
                        } else {
                            echo adminEditFieldHeading($this, $colonne, $adminEditTranslationBundle, $adminEditTranslationSourceLang);
                        }
                    } else // Otherwise show default text
                    {
                        echo adminEditFieldHeading($this, $colonne, $adminEditTranslationBundle, $adminEditTranslationSourceLang);
                    }
                    echo "</th>";
                    echo "<td>";
                    if (is_array($display)) {
                        if (count($display) > 1) {
                            echo $display[1];
                        } else {
                            echo $display[0];
                        }
                    } else // Otherwise show default text
                    {
                        echo $display;
                    }
                    echo "</td>";
                    echo "</tr>";
                } else if ($allowProtectedFields || !$this->isProtected($colonne)) {
                    if ($colonne == "id") {
                        $id = true;
                    }
                    echo "<tr" . ($hidden ? " style='display:none'" : "") . " id='row_" . $colonne . "'>";
                    echo "<th>" . adminEditFieldHeading($this, $colonne, $adminEditTranslationBundle, $adminEditTranslationSourceLang) . "</th>";

                    echo "<td>" . displayField($this, $colonne, $default, $params["filter"][$colonne] ?? null, $adminEditTranslationBundle, $adminEditTranslationSourceLang) . "</td>";

                    echo "</tr>";
                }
    }
} else {
    // Otherwise show all
    foreach ($colonnes as $key => $colonne) {

        // Only if field is active
        if (!$this->isProtected($key)) {
            if ($key == "id") {
                $id = true;
            }
            echo "<tr id='row_" . $key . "'>";
            echo "<th>" . adminEditFieldHeading($this, $key, $adminEditTranslationBundle, $adminEditTranslationSourceLang) . "</th><td>";
            // Default vs specific elements?
            if (isset($params["widget"]) && isset($params["widget"][$key])) {
                echo $params["widget"][$key]($this, $key);
            } else {
                echo displayField($this, $key, null, $params["filter"][$key] ?? null, $adminEditTranslationBundle, $adminEditTranslationSourceLang);
            }
            echo "</td></tr>";
        }
    }
};
echo "</table>";
}
if (isset($params["afterTableHtml"]) && is_string($params["afterTableHtml"]) && trim($params["afterTableHtml"]) !== "") {
    echo $params["afterTableHtml"];
}
if (empty($params['sections']) || !is_array($params['sections'])) {
    echo "</div>";
}
if (!$id && $this->getId() != "") {
    echo "<input type='hidden' id='id' name='id' value='" . $this->getId() . "'>";
}
echo "</form>";
echo "</div>";
?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script src="<?= commonAssetUrl('/common/assets/admin-edit.js') ?>"></script>
<?= commonPageScriptTags('/common/assets/admin-edit-form.js', [
    'this' => $this->tableName(),
    'success' => (string)($params["success"] ?? ""),
]) ?>
