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
?>
<?php if (!isset($params['includeComponentAssets']) || $params['includeComponentAssets'] !== false) { ?>
<link rel="stylesheet" href="/common/assets/components.css">
<?php } ?>
<style>
    .admin-edit .navTab a {
        border: 1px solid var(--admin-edit-border-strong, #cbd5e1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        padding: 5px;
        border-right: 0px;
        background: var(--admin-edit-surface, #ffffff);
    }
    .admin-edit__date-range {
        display: grid;
        gap: 8px;
    }
    .admin-edit__latlong-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        align-items: end;
    }
    .admin-edit__latlong-map {
        width: 100%;
        height: 260px;
        margin-top: 8px;
        margin-bottom: 5px;
        border-radius: 10px;
        border: 1px solid var(--admin-edit-border-strong, #cbd5e1);
        overflow: hidden;
    }
    .admin-edit__latlong-help {
        margin-top: 6px;
        color: #64748b;
        font-size: 0.88rem;
        line-height: 1.4;
    }
    @media (max-width: 640px) {
        .admin-edit__latlong-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<script>

    function previewFile(input, img) {

        var file = document.querySelector('input[type=file]').files[0];
        var reader = new FileReader();

        reader.addEventListener("load", function () {
            img.css('background-image', 'url("' + reader.result + '")')
            $("#" + input).val(file.name);
            //preview.src = reader.result;
        }, false);

        if (file) {
            reader.readAsDataURL(file);
        }
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
            $str .= "<script>(function(){";
            $str .= "var runMapInit = function(){";
            $str .= "var mapElement = document.getElementById('map_" . $key . "');";
            $str .= "var latInput = document.getElementById('" . $key . "_lat');";
            $str .= "var longInput = document.getElementById('" . $key . "_long');";
            $str .= "if (!mapElement || !latInput || !longInput) { return; }";
            $str .= "if (mapElement.dataset.leafletReady === '1') { return; }";
            $str .= "mapElement.dataset.leafletReady = '1';";
            $str .= "var initialLat = " . json_encode($hasCoordinates ? $latitude : null) . ";";
            $str .= "var initialLng = " . json_encode($hasCoordinates ? $longitude : null) . ";";
            $str .= "var defaultCenter = [46.8182, 8.2275];";
            $str .= "var center = (typeof initialLat === 'number' && typeof initialLng === 'number') ? [initialLat, initialLng] : defaultCenter;";
            $str .= "var zoom = (typeof initialLat === 'number' && typeof initialLng === 'number') ? 13 : 7;";
            $str .= "var map = L.map(mapElement).setView(center, zoom);";
            $str .= "var tileState = { layer: null, theme: null };";
            $str .= "if (typeof window.commonBindLeafletTheme === 'function') { window.commonBindLeafletTheme(map, tileState); }";
            $str .= "var marker = null;";
            $str .= "function updateMarker(lat, lng, shouldCenter){";
            $str .= "  if (typeof lat !== 'number' || typeof lng !== 'number' || Number.isNaN(lat) || Number.isNaN(lng)) { return; }";
            $str .= "  if (marker) { marker.setLatLng([lat, lng]); } else { marker = L.circleMarker([lat, lng], {radius: 9, color: '#0f766e', weight: 2, fillColor: '#14b8a6', fillOpacity: 0.85}).addTo(map); }";
            $str .= "  if (shouldCenter) { map.setView([lat, lng], Math.max(map.getZoom(), 13)); }";
            $str .= "}";
            $str .= "function setCoordinates(lat, lng, shouldCenter){";
            $str .= "  latInput.value = Number(lat).toFixed(6);";
            $str .= "  longInput.value = Number(lng).toFixed(6);";
            $str .= "  updateMarker(Number(lat), Number(lng), shouldCenter);";
            $str .= "}";
            $str .= "map.on('click', function(event){ setCoordinates(event.latlng.lat, event.latlng.lng, true); });";
            $str .= "function syncInputs(){";
            $str .= "  var lat = parseFloat(latInput.value);";
            $str .= "  var lng = parseFloat(longInput.value);";
            $str .= "  if (Number.isNaN(lat) || Number.isNaN(lng)) { return; }";
            $str .= "  updateMarker(lat, lng, true);";
            $str .= "}";
            $str .= "latInput.addEventListener('change', syncInputs);";
            $str .= "longInput.addEventListener('change', syncInputs);";
            $str .= "if (typeof initialLat === 'number' && typeof initialLng === 'number') { updateMarker(initialLat, initialLng, false); }";
            $str .= "window.setTimeout(function(){ map.invalidateSize(); }, 0);";
            $str .= "window.setTimeout(function(){ map.invalidateSize(); }, 250);";
            $str .= "};";
            $str .= "if (typeof window.commonWhenLeafletReady === 'function') { window.commonWhenLeafletReady(runMapInit); } else { runMapInit(); }";
            $str .= "})();</script>";

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
            $output .= "<input class='" . $class . "' name='" . $key . "_file' id='" . $key . "_file' type='file' onchange='previewFile(\"" . $key . "\",$(\"#img_" . $key . "\"))'><br>";
            $output .= "<div id='img_" . $key . "' src='' style='width:" . $displayWidth . "px; height:" . $displayHeight . "px; border:1px solid black; background:url(" . $object->get($key) . "); background-size:cover; background-position:center center'>";
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
            <script>
                $(function () {
                    var imgContainer_<?=$key?> = $('#imgContainer_<?=$key?>');
                    var img_<?=$key?> = $("#myImage_<?=$key?>");
                    var img1 = document.getElementById("myImage_<?=$key?>");
                    var imgWidth_<?=$key?> = (img1 ? img1.naturalWidth : 0);
                    var imgHeight_<?=$key?> = (img1 ? img1.naturalHeight : 0);
                    var imageDataInput_<?=$key?> = $('#imageDataInput_<?=$key?>');
                    var zoomSlider_<?=$key?> = $('#zoomSlider_<?=$key?>');
                    var zoomValue_<?=$key?> = 1;
                    var oldZoomValue_<?=$key?> = 1;
                    var exportMime_<?=$key?> = 'image/jpeg';

                    function resolveExportFormat_<?=$key?>(source) {
                        var normalized = String(source || '').toLowerCase();

                        if (normalized.indexOf('image/png') !== -1 || normalized.match(/\.png(?:$|\?)/)) {
                            return {
                                mime: 'image/png',
                                extension: 'png'
                            };
                        }

                        return {
                            mime: 'image/jpeg',
                            extension: 'jpg'
                        };
                    }

                    function setExportFormat_<?=$key?>(source) {
                        var format = resolveExportFormat_<?=$key?>(source);
                        exportMime_<?=$key?> = format.mime;
                    }

                    setExportFormat_<?=$key?>($('#<?=$key?>').val() || (img1 ? img1.currentSrc || img1.src : ''));

                    if (imgHeight_<?=$key?> == 0) {
                        imgContainer_<?=$key?>.css("display", "none");
                        zoomSlider_<?=$key?>.css("display", "none");
                    }

                    var containerWidth_<?=$key?> = imgContainer_<?=$key?>.width();
                    var containerHeight_<?=$key?> = imgContainer_<?=$key?>.height();
                    var maxZoom_<?=$key?> = Math.min(imgWidth_<?=$key?> / containerWidth_<?=$key?>, imgHeight_<?=$key?> / containerHeight_<?=$key?>);

                    function updateCoords_<?=$key?>() {
                        console.log("updateCoords_<?=$key?>");

                        var imgPosX = parseInt(img_<?=$key?>.css('left'));
                        var imgPosY = parseInt(img_<?=$key?>.css('top'));

                        var scale = (maxZoom_<?=$key?> * zoomValue_<?=$key?> / 10);

                        var x1 = -imgPosX * scale;
                        var y1 = -imgPosY * scale;
                        var width = imgContainer_<?=$key?>.width() * scale;
                        var height = imgContainer_<?=$key?>.height() * scale;

                        var canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        var ctx = canvas.getContext('2d');
                        ctx.drawImage(img_<?=$key?>[0], x1, y1, width, height, 0, 0, width, height);

                        // 🔥 Convert to blob
                        canvas.toBlob(function (blob) {

                            window.croppedImages = window.croppedImages || {};
                            window.croppedImages["<?=$key?>"] = blob;

                            $("#<?=$key?>").val("newimage");

                            console.log("Blob prêt pour image :", blob);

                        }, exportMime_<?=$key?>, exportMime_<?=$key?> === 'image/png' ? undefined : 0.9);
                    }

                    function updateImg_<?=$key?>() {
                        console.log("updateImg_<?=$key?>");
                        //	if (img.position().left>0) img.css('left', 0 + 'px');
                        //	if (img.position().top>0) img.css('top', 0 + 'px');

                        var imgSize = imgWidth_<?=$key?> / (maxZoom_<?=$key?> * zoomValue_<?=$key?> / 10);

                        img_<?=$key?>.css('width', imgSize + 'px');

                        // Clamp image position

                        var imgPosX = parseInt(img_<?=$key?>.css('left'));
                        var imgPosY = parseInt(img_<?=$key?>.css('top'));
                        if (imgPosX > 0) {
                            imgPosX = 0;
                        }
                        if (imgPosY > 0) {
                            imgPosY = 0;
                        }
                        //console.log ("3: "+imgPosX+" - "+imgContainer.width()+" - "+img.width());
                        if (imgPosX < imgContainer_<?=$key?>.width() - img_<?=$key?>.width()) {
                            imgPosX = imgContainer_<?=$key?>.width() - img_<?=$key?>.width();
                        }
                        if (imgPosY < imgContainer_<?=$key?>.height() - img_<?=$key?>.height()) {
                            imgPosY = imgContainer_<?=$key?>.height() - img_<?=$key?>.height();
                        }
                        //console.log ("4: "+imgPosX+" - "+imgContainer.width()+" - "+img.width());

                        img_<?=$key?>.css('left', imgPosX + 'px');
                        img_<?=$key?>.css('top', imgPosY + 'px');
                        updateCoords_<?=$key?>();
                    }

                    // Event handler for the "Select from disk" button
                    $('#imageFileInput_<?=$key?>').on('change', function (event) {
                        console.log("#imageFileInput_<?=$key?>.change()");
                        var file = event.target.files[0];
                        setExportFormat_<?=$key?>(file ? file.type : '');
                        var reader = new FileReader();
                        reader.onload = function (event) {
                            // Remove existing image
                            img_<?=$key?>.remove();
                            img_<?=$key?> = $('<img id="myImage_<?=$key?>" style="display: block;position: absolute;top: 0;	left: 0; object-fit: contain;">').attr('src', event.target.result).appendTo(imgContainer_<?=$key?>);
                            img_<?=$key?>.on('load', function () {
                                var img1 = document.getElementById("myImage_<?=$key?>");
                                imgWidth_<?=$key?> = img1.naturalWidth;
                                imgHeight_<?=$key?> = img1.naturalHeight;
                                if (imgHeight_<?=$key?> > 0) {
                                    imgContainer_<?=$key?>.css("display", "");
                                    zoomSlider_<?=$key?>.css("display", "");
                                }
                                var containerWidth = imgContainer_<?=$key?>.width();
                                var containerHeight = imgContainer_<?=$key?>.height();
                                maxZoom_<?=$key?> = Math.min(imgWidth_<?=$key?> / containerWidth, imgHeight_<?=$key?> / containerHeight);
                                zoomSlider_<?=$key?>.val(0);
                                var mini = 10 / maxZoom_<?=$key?>;
                                oldZoomValue_<?=$key?> = zoomValue_<?=$key?> = Math.pow(10 - mini, (100 - zoomSlider_<?=$key?>.val()) / 50 - 1) + mini;

                                // Center image
                                var cx = (containerWidth - (imgWidth_<?=$key?> / (maxZoom_<?=$key?> * zoomValue_<?=$key?> / 10))) * 0.5;  // -0.5 * (container - scaled img width)
                                var cy = (containerHeight - (imgHeight_<?=$key?> / (maxZoom_<?=$key?> * zoomValue_<?=$key?> / 10))) * 0.5;
                                img_<?=$key?>.css('left', cx + 'px');
                                img_<?=$key?>.css('top', cy + 'px');

                                updateImg_<?=$key?>();
                            });
                        };
                        reader.readAsDataURL(file);
                    });

                    // Event handler for the zoom slider
                    zoomSlider_<?=$key?>.on('input', function () {
                        console.log("zoomSlider_<?=$key?>.input");
                        var mini = 10 / maxZoom_<?=$key?>;
                        zoomValue_<?=$key?> = Math.pow(10 - mini, (100 - zoomSlider_<?=$key?>.val()) / 50 - 1) + mini;

                        // Recenter image position for zoom
                        var imgPosX = parseInt(img_<?=$key?>.css('left'));
                        var imgPosY = parseInt(img_<?=$key?>.css('top'));
                        var imgPosX2 = -(imgPosX - containerWidth_<?=$key?> / 2) * (maxZoom_<?=$key?> * oldZoomValue_<?=$key?> / 10);
                        var imgPosY2 = -(imgPosY - containerHeight_<?=$key?> / 2) * (maxZoom_<?=$key?> * oldZoomValue_<?=$key?> / 10);
                        img_<?=$key?>.css('left', (containerWidth_<?=$key?> / 2 - (imgPosX2 / (maxZoom_<?=$key?> * zoomValue_<?=$key?> / 10))) + 'px');
                        img_<?=$key?>.css('top', (containerHeight_<?=$key?> / 2 - (imgPosY2 / (maxZoom_<?=$key?> * zoomValue_<?=$key?> / 10))) + 'px');

                        // Save the latest version
                        oldZoomValue_<?=$key?> = zoomValue_<?=$key?>;
                        updateImg_<?=$key?>();
                    });

                    // Event handler for mouse movement on the image
                    imgContainer_<?=$key?>.on('mousedown', function (event) {
                        console.log("imgContainer_<?=$key?>.mousedown");
                        event.preventDefault();
                        var startX = event.clientX;
                        var startY = event.clientY;
                        var imgPosX = parseInt(img_<?=$key?>.css('left'));
                        var imgPosY = parseInt(img_<?=$key?>.css('top'));
                        var moveHandler = function (event) {
                            event.preventDefault();
                            var deltaX = event.clientX - startX;
                            var deltaY = event.clientY - startY;
                            img_<?=$key?>.css('left', imgPosX + deltaX + 'px');
                            img_<?=$key?>.css('top', imgPosY + deltaY + 'px');
                            //updateCoords_<?=$key?>();
                        };
                        var upHandler = function (event) {
                            event.preventDefault();
                            updateCoords_<?=$key?>();
                            updateImg_<?=$key?>();
                            $(document).off('mousemove', moveHandler);
                            $(document).off('mouseup', upHandler);
                        };
                        $(document).on('mousemove', moveHandler);
                        $(document).on('mouseup', upHandler);
                    });
                });
            </script>
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
<style>
    .admin-edit {
        --admin-edit-primary: var(--color-primary, #004663);
        --admin-edit-surface: var(--color-surface, #ffffff);
        --admin-edit-surface-alt: var(--color-surface-alt, #f0f2f5);
        --admin-edit-border: var(--color-border, #e5e7eb);
        --admin-edit-border-strong: var(--color-border-strong, #cbd5e1);
        --admin-edit-text: var(--color-text, #111827);
        --admin-edit-muted: var(--color-text-light, #6b7280);
        --admin-edit-shadow: none;
        color: var(--admin-edit-text);
    }

    .admin-edit__toolbar {
        position: sticky;
        top: 0;
        z-index: 15;
        margin: -4px -4px 24px;
        padding: 16px 18px;
        background: color-mix(in srgb, var(--admin-edit-surface) 88%, transparent);
        backdrop-filter: blur(14px);
        border-bottom: 1px solid color-mix(in srgb, var(--admin-edit-border) 88%, transparent);
    }

    .admin-edit__toolbar-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .admin-edit__toolbar-copy {
        display: grid;
        gap: 4px;
    }

    .admin-edit__toolbar-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .admin-edit__toolbar-text {
        margin: 0;
        color: var(--admin-edit-muted);
        font-size: 0.95rem;
    }

    .admin-edit__panel {
        --generic-soft-panel-padding-block: var(--generic-container-padding-block, 16px);
        --generic-soft-panel-padding-inline: var(--generic-container-padding-inline, 16px);
        --generic-soft-panel-radius: var(--generic-container-radius, var(--radius-md, 6px));
        --generic-soft-panel-border: var(--admin-edit-border);
        --generic-soft-panel-background: var(--admin-edit-surface);
        --generic-soft-panel-gap: var(--generic-space-4, 16px);
        box-shadow: var(--admin-edit-shadow);
    }

    .admin-edit table.dbobjecttable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 14px;
    }

    .admin-edit table.dbobjecttable > tbody > tr > th,
    .admin-edit table.dbobjecttable > tbody > tr > td,
    .admin-edit table.dbobjecttable > tr > th,
    .admin-edit table.dbobjecttable > tr > td {
        vertical-align: top;
    }

    .admin-edit table.dbobjecttable th {
        width: 220px;
        min-width: 200px;
        text-align: left;
        padding: 13px var(--generic-space-4, 16px) 0 0;
        color: var(--admin-edit-text);
        font-weight: 700;
        font-size: var(--generic-type-size-sm, 0.84rem);
        line-height: 1.45;
    }

    .admin-edit table.dbobjecttable td {
        padding: 0;
    }

    .admin-edit table.dbobjecttable td > input:not([type='checkbox']):not([type='radio']):not([type='button']):not([type='submit']):not([type='hidden']),
    .admin-edit table.dbobjecttable td > select,
    .admin-edit table.dbobjecttable td > textarea,
    .admin-edit table.dbobjecttable td > table,
    .admin-edit table.dbobjecttable td > div,
    .admin-edit table.dbobjecttable td > p,
    .admin-edit table.dbobjecttable td > hr {
        margin-top: 0;
    }

    .admin-edit table.dbobjecttable td > input:not([type='checkbox']):not([type='radio']):not([type='button']):not([type='submit']):not([type='hidden']),
    .admin-edit table.dbobjecttable td > select,
    .admin-edit table.dbobjecttable td > textarea,
    .admin-edit table.dbobjecttable td > table,
    .admin-edit table.dbobjecttable td > div:not(.char_count):not(.field_help),
    .admin-edit table.dbobjecttable td > p,
    .admin-edit table.dbobjecttable td > hr,
    .admin-edit table.dbobjecttable td > h1,
    .admin-edit table.dbobjecttable td > h2 {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }

    .admin-edit table.dbobjecttable td > table {
        border-collapse: separate;
        border-spacing: 12px 0;
    }

    .admin-edit table.dbobjecttable td > table td {
        padding: 0;
    }

    .admin-edit table.dbobjecttable td > table td:first-child {
        padding-left: 0;
    }

    .admin-edit__control,
    #formulaire-edit input[type='text'],
    #formulaire-edit input[type='email'],
    #formulaire-edit input[type='password'],
    #formulaire-edit input[type='number'],
    #formulaire-edit input[type='date'],
    #formulaire-edit input[type='time'],
    #formulaire-edit input[type='datetime-local'],
    #formulaire-edit input[type='color'],
    #formulaire-edit select,
    #formulaire-edit textarea {
        width: 100%;
        min-height: var(--generic-form-control-min-height, 44px);
        padding: var(--generic-form-control-padding-block, 11px) var(--generic-form-control-padding-inline, 12px);
        border: 1px solid var(--generic-form-control-border, var(--color-border, #d1d5db));
        border-radius: var(--generic-form-control-radius, var(--radius-md, 6px));
        background: var(--generic-form-control-background, var(--color-bg, #f8fafc));
        color: var(--generic-form-control-color, var(--color-text, #1f2937));
        font: inherit;
        line-height: 1.45;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        box-sizing: border-box;
    }

    #formulaire-edit textarea {
        min-height: 120px;
        resize: vertical;
    }

    #formulaire-edit input[type='color'] {
        width: 72px;
        min-width: 72px;
        padding: 6px;
    }

    .admin-edit__color-field {
        display: grid;
        grid-template-columns: 72px minmax(0, 1fr);
        gap: 12px;
        align-items: start;
    }

    .admin-edit__color-picker {
        width: 72px !important;
        min-width: 72px;
        height: 46px;
        padding: 6px !important;
        border-radius: 14px;
        cursor: pointer;
    }

    .admin-edit__color-text {
        min-width: 0;
    }

    #formulaire-edit input[type='file'] {
        width: 100%;
        padding: 10px 12px;
        border: 1px dashed var(--color-border, #d1d5db);
        border-radius: 12px;
        background: color-mix(in srgb, var(--color-surface-alt, #f8fafc) 88%, white 12%);
        color: var(--color-text-light, #64748b);
        box-sizing: border-box;
    }

    #formulaire-edit input[type='file']::file-selector-button {
        margin-right: 12px;
        padding: 10px 14px;
        border: 1px solid var(--color-border, #d1d5db);
        border-radius: 10px;
        background: var(--color-surface, #ffffff);
        color: var(--color-text, #1f2937);
        font: inherit;
        font-weight: 700;
        cursor: pointer;
    }

    #formulaire-edit input[type='range'] {
        width: min(360px, 100%);
        accent-color: var(--admin-edit-primary);
    }

    #formulaire-edit input[type='checkbox'],
    #formulaire-edit input[type='radio'] {
        width: 18px;
        height: 18px;
        accent-color: var(--admin-edit-primary);
    }

    .admin-edit__control:focus,
    #formulaire-edit input[type='text']:focus,
    #formulaire-edit input[type='email']:focus,
    #formulaire-edit input[type='password']:focus,
    #formulaire-edit input[type='number']:focus,
    #formulaire-edit input[type='date']:focus,
    #formulaire-edit input[type='time']:focus,
    #formulaire-edit input[type='datetime-local']:focus,
    #formulaire-edit input[type='color']:focus,
    #formulaire-edit input[type='file']:focus,
    #formulaire-edit select:focus,
    #formulaire-edit textarea:focus {
        outline: none;
        border-color: color-mix(in srgb, var(--color-primary, #2563eb) 52%, var(--color-border, #d1d5db));
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary, #2563eb) 14%, transparent);
        background: var(--color-surface, #ffffff);
    }

    #formulaire-edit button:not(.note-btn),
    #formulaire-edit input[type='button'],
    #formulaire-edit input[type='submit'] {
        min-height: 42px;
        padding: 10px 16px;
        border: 1px solid transparent;
        border-radius: 12px;
        background: var(--color-primary, #2563eb);
        color: var(--color-text-inverse, #ffffff);
        font: inherit;
        font-weight: 700;
        line-height: 1.35;
        cursor: pointer;
        box-shadow: var(--shadow-md, 0 12px 24px rgba(0, 0, 0, 0.12));
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
    }

    #formulaire-edit button:not(.note-btn):hover,
    #formulaire-edit input[type='button']:hover,
    #formulaire-edit input[type='submit']:hover {
        transform: translateY(-1px);
    }

    #formulaire-edit button:not(.note-btn):disabled,
    #formulaire-edit input[type='button']:disabled,
    #formulaire-edit input[type='submit']:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    #formulaire-edit .admin-edit__action--secondary,
    #formulaire-edit .admin-edit__draft-button {
        background: var(--color-surface-alt, #f0f2f5);
        color: var(--color-text, #1f2937);
        border-color: var(--color-border, #d1d5db);
        box-shadow: none;
    }

    #formulaire-edit h1,
    #formulaire-edit h2 {
        margin: 0 0 10px;
        color: var(--admin-edit-text);
    }

    #formulaire-edit p {
        margin: 0;
        color: var(--admin-edit-muted);
        line-height: 1.6;
    }

    #formulaire-edit hr {
        border: 0;
        border-top: 1px solid var(--admin-edit-border);
        margin: 8px 0;
    }

    .admin-edit .char_count {
        margin-top: 8px;
        color: var(--color-text-light, #64748b);
        font-size: 0.85rem;
    }

    .admin-edit .field_help {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        margin-left: 6px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--admin-edit-primary) 12%, var(--admin-edit-surface));
        color: var(--admin-edit-primary);
        font-size: 0.75rem;
        font-weight: 700;
        cursor: help;
    }

    .admin-edit .required-star {
        margin-left: 6px;
        color: #dc2626;
    }

    .admin-edit .star-rating {
        display: inline-flex;
        gap: 6px;
        padding: 10px 14px;
        border: 1px solid var(--admin-edit-border);
        border-radius: 14px;
        background: var(--admin-edit-surface);
    }

    .admin-edit .star-rating span {
        cursor: pointer;
        font-size: 1.5rem;
        color: #cbd5e1;
    }

    .admin-edit .star-rating span.active {
        color: #f59e0b;
    }

    .admin-edit .drag_img,
    .admin-edit [id^='imgContainer_'] {
        border-radius: 18px;
    }

    .admin-edit [id^='imgContainer_'] {
        overflow: hidden;
        border: 1px solid var(--admin-edit-border-strong) !important;
        background: color-mix(in srgb, var(--admin-edit-surface-alt) 80%, var(--admin-edit-surface));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
    }

    @media only screen and (max-width: 860px) {
        .admin-edit__toolbar {
            margin-left: 0;
            margin-right: 0;
            padding-left: 0;
            padding-right: 0;
        }

        .admin-edit__panel {
            padding: var(--generic-container-padding-block, 16px) var(--generic-container-padding-inline, 16px);
            border-radius: var(--generic-container-radius, var(--radius-md, 6px));
        }

        .admin-edit table.dbobjecttable,
        .admin-edit table.dbobjecttable tbody,
        .admin-edit table.dbobjecttable tr,
        .admin-edit table.dbobjecttable th,
        .admin-edit table.dbobjecttable td {
            display: block;
            width: 100%;
        }

        .admin-edit table.dbobjecttable {
            border-spacing: 0;
        }

        .admin-edit table.dbobjecttable tr {
            margin-bottom: 18px;
        }

        .admin-edit table.dbobjecttable th {
            min-width: 0;
            padding: 0 0 8px;
        }

        .admin-edit table.dbobjecttable td > table,
        .admin-edit table.dbobjecttable td > table tr,
        .admin-edit table.dbobjecttable td > table td {
            display: block;
            width: 100%;
        }

        .admin-edit table.dbobjecttable td > table td + td {
            margin-top: 12px;
        }
    }
</style>

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
if (isset($params["afterTableHtml"]) && is_string($params["afterTableHtml"]) && trim($params["afterTableHtml"]) !== "") {
    echo $params["afterTableHtml"];
}
echo "</div>";
if (!$id && $this->getId() != "") {
    echo "<input type='hidden' id='id' name='id' value='" . $this->getId() . "'>";
}
echo "</form>";
echo "</div>";
?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script>
    window.adminEditSummernoteInitPromise = window.adminEditSummernoteInitPromise || null;

    function adminEditLoadStyleOnce(href, dataAttribute) {
        return new Promise(function (resolve, reject) {
            if (!href) {
                resolve();
                return;
            }

            var existingLink = document.querySelector('link[' + dataAttribute + '="' + href + '"]');
            if (existingLink) {
                resolve();
                return;
            }

            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.setAttribute(dataAttribute, href);
            link.onload = function () {
                resolve();
            };
            link.onerror = function () {
                reject(new Error('summernote_css_load_failed'));
            };
            document.head.appendChild(link);
        });
    }

    function adminEditLoadScriptOnce(src, dataAttribute) {
        return new Promise(function (resolve, reject) {
            if (!src) {
                resolve();
                return;
            }

            var existingScript = document.querySelector('script[' + dataAttribute + '="' + src + '"]');
            if (existingScript) {
                if (existingScript.getAttribute('data-admin-edit-loaded') === '1') {
                    resolve();
                    return;
                }

                existingScript.addEventListener('load', function () {
                    existingScript.setAttribute('data-admin-edit-loaded', '1');
                    resolve();
                }, { once: true });
                existingScript.addEventListener('error', function () {
                    reject(new Error('summernote_js_load_failed'));
                }, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = src;
            script.async = false;
            script.setAttribute(dataAttribute, src);
            script.onload = function () {
                script.setAttribute('data-admin-edit-loaded', '1');
                resolve();
            };
            script.onerror = function () {
                reject(new Error('summernote_js_load_failed'));
            };
            document.head.appendChild(script);
        });
    }

    function adminEditEnsureSummernoteAssets() {
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.summernote === 'function') {
            return Promise.resolve(window.jQuery);
        }

        if (window.adminEditSummernoteInitPromise) {
            return window.adminEditSummernoteInitPromise;
        }

        var summernoteVersion = '0.8.18';
        var summernoteCssUrl = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + summernoteVersion + '/summernote-lite.min.css';
        var summernoteJsUrl = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + summernoteVersion + '/summernote-lite.min.js';
        var summernoteLangUrl = 'https://cdnjs.cloudflare.com/ajax/libs/summernote/' + summernoteVersion + '/lang/summernote-fr-FR.min.js';

        window.adminEditSummernoteInitPromise = adminEditLoadStyleOnce(summernoteCssUrl, 'data-admin-edit-summernote-css')
            .then(function () {
                return adminEditLoadScriptOnce(summernoteJsUrl, 'data-admin-edit-summernote-js');
            })
            .then(function () {
                return adminEditLoadScriptOnce(summernoteLangUrl, 'data-admin-edit-summernote-lang');
            })
            .then(function () {
                if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.summernote !== 'function') {
                    throw new Error('summernote_not_available');
                }

                return window.jQuery;
            })
            .catch(function (error) {
                window.adminEditSummernoteInitPromise = null;
                throw error;
            });

        return window.adminEditSummernoteInitPromise;
    }

    function adminEditSyncHtmlFields(scope) {
        if (!window.jQuery || !window.jQuery.fn) {
            return;
        }

        var root = scope || document;
        window.jQuery(root).find('textarea.summernote').each(function () {
            var field = window.jQuery(this);
            if (field.data('adminEditSummernoteBound') === true && typeof field.summernote === 'function') {
                try {
                    field.val(field.summernote('code'));
                } catch (error) {
                }
            }
        });
    }

    function adminEditDestroyHtmlFields(scope) {
        if (!window.jQuery || !window.jQuery.fn) {
            return;
        }

        var root = scope || document;
        window.jQuery(root).find('textarea.summernote').each(function () {
            var field = window.jQuery(this);
            var isBound = field.data('adminEditSummernoteBound') === true
                || field.next('.note-editor').length > 0;

            if (isBound && typeof field.summernote === 'function') {
                try {
                    field.val(field.summernote('code'));
                    field.summernote('destroy');
                } catch (error) {
                }
            }

            field.removeData('adminEditSummernoteBound');
        });
    }

    function adminEditInitHtmlFields(scope) {
        var root = scope || document;
        var textareas = root.querySelectorAll ? root.querySelectorAll('textarea.summernote') : [];
        if (!textareas || textareas.length === 0) {
            return Promise.resolve();
        }

        function adminEditGetHtmlEditorOptions(field) {
            var profile = (field.data('editorProfile') || '').toString();
            var options = {
                lang: 'fr-FR',
                height: 240,
                disableResizeEditor: true,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table', 'hr']],
                    ['view', ['codeview']]
                ]
            };

            if (profile === 'simple') {
                options.toolbar = [
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']]
                ];
            }

            options.callbacks = {
                onChange: function (contents) {
                    field.val(contents);
                }
            };

            return options;
        }

        return adminEditEnsureSummernoteAssets()
            .then(function ($) {
                Array.prototype.forEach.call(textareas, function (textarea) {
                    if (!document.documentElement.contains(textarea)) {
                        return;
                    }

                    var field = $(textarea);
                    if (field.data('adminEditSummernoteBound') === true) {
                        return;
                    }

                    field.data('adminEditSummernoteBound', true);
                    field.summernote(adminEditGetHtmlEditorOptions(field));

                    field.val(field.summernote('code'));
                });
            })
            .catch(function () {
                console.warn('Impossible de charger l editeur HTML adminEdit.');
            });
    }

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

            $.post('/ajax/check.php?type=<?=$this->tableName()?>', serform, function (data) {
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

                        <?php  if (isset($params["success"]) && $params["success"] != "") { ?>

                        if ("<?=$params["success"]?>".indexOf("()") > 0) {
                            eval("<?=$params["success"]?>");
                        } else {

                            var form_result = $('<form></form>');
                            form_result.attr('method', 'post');
                            form_result.attr('action', '<?=$params["success"]?>');

                            var id = $('<input type="text" name="id" value="' + response.id + '" />');
                            form_result.append(id);

                            $("body").append(form_result);
                            form_result.submit();
                        }

                        <?php  } else { ?>

                        alert("Données enregistrées");
                        endAdminEditPending(form);

                        <?php  } ?>

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
</script>
