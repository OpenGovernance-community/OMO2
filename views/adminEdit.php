<!--
	USAGE EXAMPLE:
	
	$params=array(
		"fields" => array(array("id","IDadministrateur_charge"),array("date","[heurerendezvous] [rue] [npa] [localite]"),array("IDadresse","todo")),
		"page" => $_GET["page"],
	);


-->
<?php
//error_reporting(E_ALL | E_ALL);
?>
<style>
    .navTab a {
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

function displayField($object, $key, $default = null, $filter = null) {

    $type = $object->getFieldType($key);
    $class = adminEditMergeClass(($object->isRequired($key) ? "required" : ""), "admin-edit__control");
    switch ($type) {
        case "fk" :
            // Return this field's text value
            $txt = "<select class='" . $class . "' name='" . $key . "' id='" . $key . "' ><option value=''>Choisissez...</option>";

            // Load values and render them
            foreach ($object->getValues($key, $filter) as $value) {
                $txt .= "<option value='" . $value->getId() . "'" . ($default == $value->getId() || (is_null($default) && $value->getId() == $object->get($key)) ? " selected" : "") . ">" . $value->getLabel() . "</option>";
            }
            $txt .= "</select>";

            return $txt;
            break;
        case "date" :
            $datewg = new \widget\DateWidget($key, "", $object->get($key));
            if ($object->isProtected($key)) {
                $datewg->disable();
            }

            return $datewg->getString("defaultDate.php");
            break;
        case "time" :
            $datewg = new \widget\DateWidget($key, "", $object->get($key));
            if ($object->isProtected($key)) {
                $datewg->disable();
            }

            return $datewg->getString("defaultTime.php");
            break;
        case "datetime" :
            $datewg = new \widget\DateWidget($key, "", $object->get($key));
            if ($object->isProtected($key)) {
                $datewg->disable();
            }

            return $datewg->getString("defaultDateTime.php");
            break;
        case "daterange" :
            $datewg = new \widget\DateWidget($key, "", ($object->get($key) ? $object->get($key) : $default), ($object->get($key . "_fin") ? $object->get($key . "_fin") : $default));
            if ($object->isProtected($key)) {
                $datewg->disable();
            }

            return $datewg->getString("defaultDateRange.php");
            //return "<input class='".$class."' type='text' value='".date_format(date_create($object->get($key)), 'd.m.Y H:i:s')."'>";
            break;
        case "timezone" :
            $str = "<select name='" . $key . "' id='" . $key . "'>";

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
            $str = "<input class='" . $class . "' name='" . $key . "[]' id='" . $key . "_lat' type='text' value='" . $object->get($key)->lat . "'> <input class='" . $class . "' name='" . $key . "[]' id='" . $key . "_long' type='text' value='" . $object->get($key)->long . "'>";
            $str .= "<div id='map_" . $key . "' style='width:100%; height:200px; margin-top:5px; margin-bottom:5px; border-radius:10px; border:1px solid black'></div>";
            $str .= "<script>var currentMarker;\nvar map_" . $key . ";\nfunction initMap() {\nmap_" . $key . " = new google.maps.Map(document.getElementById('map_" . $key . "'), {\n";
            $str .= "    center: { lat: " . ($object->get($key)->lat ? $object->get($key)->lat : 0) . ", lng: " . ($object->get($key)->long ? $object->get($key)->long : 0) . " },\n";
            $str .= "    zoom: " . ($object->get($key)->lat ? 13 : 3) . "\n  });";
            $str .= "	currentMarker = new google.maps.Marker({position: { lat: " . ($object->get($key)->lat ? $object->get($key)->lat : 0) . ", lng: " . ($object->get($key)->long ? $object->get($key)->long : 0) . " },map: map_" . $key . " });\n";
            // Map click captures coordinates
            $str .= "map_" . $key . ".addListener('click', function(event) {\n";
            $str .= "  var latitude = event.latLng.lat();$('#" . $key . "_lat').val(latitude);\n";
            $str .= "  var longitude = event.latLng.lng();$('#" . $key . "_long').val(longitude);\n";
            $str .= "if (currentMarker) { currentMarker.setMap(null); }"; // Clear existing marker
            $str .= "	currentMarker = new google.maps.Marker({position: { lat: latitude, lng: longitude },map: map_" . $key . " });\n";
            $str .= "});\n";
            $str .= "}\n</script>";

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
            if (isset($object::attributePlaceholder()[$key])) {
                $str .= " placeholder='" . str_replace("'", "&apos;", $object::attributePlaceholder()[$key]) . "' ";
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
            if (isset($object::attributePlaceholder()[$key])) {
                $str .= " placeholder='" . str_replace("'", "&apos;", $object::attributePlaceholder()[$key]) . "' ";
            } else {
                $str .= " placeholder='#004663' ";
            }
            if (isset($object::attributeLength()[$key])) {
                $str .= "maxlength='" . $object::attributeLength()[$key] . "'  onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' >";
                $str .= "<div class='char_count'>max " . $object::attributeLength()[$key] . " caracteres</div>";
            } else {
                $str .= ">";
            }
            $str .= "</div>";

            return $str;
            break;
        case "color":
            $colorValue = ($object->get($key) != "" ? $object->get($key) : $default);
            $str = "<input  type='color' class='" . $class . "' name='" . $key . "' id='" . $key . "' style='width:50px' type='text' value='" . str_replace("'", "&apos;", (string)($colorValue ?? "")) . "'";
            if (isset($object::attributePlaceholder()[$key])) {
                $str .= " placeholder='" . str_replace("'", "&apos;", $object::attributePlaceholder()[$key]) . "' ";
            }
            if (isset($object::attributeLength()[$key])) {
                $str .= "maxlength='" . $object::attributeLength()[$key] . "'  onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' ><div class='char_count'> max " . $object::attributeLength()[$key] . " caractères</div>";
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
            if (isset($object::attributePlaceholder()[$key])) {
                $str .= " placeholder='" . str_replace("'", "&apos;", $object::attributePlaceholder()[$key]) . "' ";
            }
            if (isset($object::attributeLength()[$key])) {
                $str .= "maxlength='" . $object::attributeLength()[$key] . "'  onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' ><div class='char_count'> max " . $object::attributeLength()[$key] . " caractères</div>";
            } else {
                $str .= ">";
            }

            return $str;
            break;
        case "text" :
            $str = $object->get($key);
            $tmp = "<textarea class='" . $class . "' name='" . $key . "' id='" . $key . "' style='width:100%'";
            if (isset($object::attributeLength()[$key])) {
                $tmp .= "maxlength='" . $object::attributeLength()[$key] . "' onkeyup='countChar($(this), " . $object::attributeLength()[$key] . ")' onkeypress='countChar($(this), " . $object::attributeLength()[$key] . ")' >" . $str . "</textarea><div class='char_count'> max " . $object::attributeLength()[$key] . " caractères</div>";
            } else {
                $tmp .= ">" . $str . "</textarea>";
            }

            return $tmp;
        case "html" :
            $str = $object->get($key);

            return "<textarea  class='" . $class . "summernote' name='" . $key . "' id='" . $key . "' style='width:100%'>" . $str . "</textarea>";
            break;
        case "boolean" :
            return "<input type='hidden' id='" . $key . "' name='" . $key . "' value='0'>" .
                "<input type='checkbox' name='" . $key . "' id='" . $key . "'" . ($object->get($key) > 0 ? "checked" : "") . " value='1'>";
            break;
        case "image" :
            $output = "<input name='" . $key . "' id='" . $key . "' type='hidden' value='" . str_replace("'", "&apos;", (string)($object->get($key) ?? "")) . "'>";
            $output .= "<input class='" . $class . "' name='" . $key . "_file' id='" . $key . "_file' type='file' onchange='previewFile(\"" . $key . "\",$(\"#img_" . $key . "\"))'><br>";
            $output .= "<div id='img_" . $key . "' src='' style='width:" . (isset($object::attributeLength()[$key]) ? $object::attributeLength()[$key][0] : "200") . "px; height:" . (isset($object::attributeLength()[$key]) ? $object::attributeLength()[$key][1] : "200") . "px; border:1px solid black; background:url(" . $object->get($key) . "); background-size:cover; background-position:center center'>";
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
            $output .= "<input type='button' value='Choose image on disk...' onclick='$(\"#imageFileInput_" . $key . "\").click();' />";
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

                        }, 'image/jpeg', 0.9);
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
?>
<script>
    // generic validation helpers
    function countChar(objet, limit) {
        if (objet.val().length > limit) {
            objet.val(objet.val().substr(0, limit));
        }
        objet.nextAll(".char_count").html(objet.val().length + " sur " + limit + " caractères");
    }
</script>
<style>
    :root {
        --admin-edit-primary: var(--color-primary, #004663);
        --admin-edit-surface: var(--color-surface, #ffffff);
        --admin-edit-surface-alt: var(--color-surface-alt, #f0f2f5);
        --admin-edit-border: var(--color-border, #e5e7eb);
        --admin-edit-border-strong: var(--color-border-strong, #cbd5e1);
        --admin-edit-text: var(--color-text, #111827);
        --admin-edit-muted: var(--color-text-light, #6b7280);
        --admin-edit-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
    }

    .admin-edit {
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

    .admin-edit__actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .admin-edit__panel {
        padding: 24px;
        border: 1px solid var(--admin-edit-border);
        border-radius: 22px;
        background: var(--admin-edit-surface);
        box-shadow: var(--admin-edit-shadow);
    }

    table.dbobjecttable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 14px;
    }

    table.dbobjecttable > tbody > tr > th,
    table.dbobjecttable > tbody > tr > td,
    table.dbobjecttable > tr > th,
    table.dbobjecttable > tr > td {
        vertical-align: top;
    }

    table.dbobjecttable th {
        width: 240px;
        min-width: 220px;
        text-align: left;
        padding: 16px 18px 0 0;
        color: var(--admin-edit-text);
        font-weight: 700;
        line-height: 1.45;
    }

    table.dbobjecttable td {
        padding: 0;
    }

    table.dbobjecttable td > input:not([type='checkbox']):not([type='radio']):not([type='button']):not([type='submit']):not([type='hidden']),
    table.dbobjecttable td > select,
    table.dbobjecttable td > textarea,
    table.dbobjecttable td > table,
    table.dbobjecttable td > div,
    table.dbobjecttable td > p,
    table.dbobjecttable td > hr {
        margin-top: 0;
    }

    table.dbobjecttable td > input:not([type='checkbox']):not([type='radio']):not([type='button']):not([type='submit']):not([type='hidden']),
    table.dbobjecttable td > select,
    table.dbobjecttable td > textarea,
    table.dbobjecttable td > table,
    table.dbobjecttable td > div:not(.char_count):not(.field_help),
    table.dbobjecttable td > p,
    table.dbobjecttable td > hr,
    table.dbobjecttable td > h1,
    table.dbobjecttable td > h2 {
        display: block;
        width: 100%;
        box-sizing: border-box;
    }

    table.dbobjecttable td > table {
        border-collapse: separate;
        border-spacing: 12px 0;
    }

    table.dbobjecttable td > table td {
        padding: 0;
    }

    table.dbobjecttable td > table td:first-child {
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
        min-height: 46px;
        padding: 12px 14px;
        border: 1px solid var(--admin-edit-border);
        border-radius: 14px;
        background: var(--admin-edit-surface);
        color: var(--admin-edit-text);
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
        border: 1px dashed var(--admin-edit-border-strong);
        border-radius: 14px;
        background: color-mix(in srgb, var(--admin-edit-surface-alt) 68%, var(--admin-edit-surface));
        color: var(--admin-edit-muted);
        box-sizing: border-box;
    }

    #formulaire-edit input[type='file']::file-selector-button {
        margin-right: 12px;
        padding: 10px 14px;
        border: 0;
        border-radius: 10px;
        background: color-mix(in srgb, var(--admin-edit-primary) 12%, var(--admin-edit-surface));
        color: var(--admin-edit-primary);
        font: inherit;
        font-weight: 600;
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
        border-color: var(--admin-edit-primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--admin-edit-primary) 18%, transparent);
    }

    #formulaire-edit button,
    #formulaire-edit input[type='button'],
    #formulaire-edit input[type='submit'] {
        min-height: 44px;
        padding: 0 18px;
        border: 1px solid transparent;
        border-radius: 999px;
        background: var(--admin-edit-primary);
        color: #ffffff;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.18s ease, filter 0.18s ease, background-color 0.18s ease, border-color 0.18s ease;
    }

    #formulaire-edit button:hover,
    #formulaire-edit input[type='button']:hover,
    #formulaire-edit input[type='submit']:hover {
        filter: brightness(1.03);
        transform: translateY(-1px);
    }

    #formulaire-edit button:disabled,
    #formulaire-edit input[type='button']:disabled,
    #formulaire-edit input[type='submit']:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    #formulaire-edit .admin-edit__action--secondary,
    #formulaire-edit .admin-edit__draft-button {
        background: var(--admin-edit-surface);
        color: var(--admin-edit-text);
        border-color: var(--admin-edit-border);
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

    .char_count {
        margin-top: 8px;
        color: var(--admin-edit-muted);
        font-size: 0.85rem;
    }

    .field_help {
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

    .required-star {
        margin-left: 6px;
        color: #dc2626;
    }

    .star-rating {
        display: inline-flex;
        gap: 6px;
        padding: 10px 14px;
        border: 1px solid var(--admin-edit-border);
        border-radius: 14px;
        background: var(--admin-edit-surface);
    }

    .star-rating span {
        cursor: pointer;
        font-size: 1.5rem;
        color: #cbd5e1;
    }

    .star-rating span.active {
        color: #f59e0b;
    }

    .drag_img,
    [id^='imgContainer_'] {
        border-radius: 18px;
    }

    [id^='imgContainer_'] {
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
            padding: 18px;
            border-radius: 18px;
        }

        table.dbobjecttable,
        table.dbobjecttable tbody,
        table.dbobjecttable tr,
        table.dbobjecttable th,
        table.dbobjecttable td {
            display: block;
            width: 100%;
        }

        table.dbobjecttable {
            border-spacing: 0;
        }

        table.dbobjecttable tr {
            margin-bottom: 18px;
        }

        table.dbobjecttable th {
            min-width: 0;
            padding: 0 0 8px;
        }

        table.dbobjecttable td > table,
        table.dbobjecttable td > table tr,
        table.dbobjecttable td > table td {
            display: block;
            width: 100%;
        }

        table.dbobjecttable td > table td + td {
            margin-top: 12px;
        }
    }
</style>

<?php

// Header
$adminEditToolbarTitle = ($this->getId() != "" ? "Edition" : "Creation");
echo "<div class='admin-edit'>";
echo "<form id='formulaire-edit' method='POST' enctype='multipart/form-data'";
if (isset($params["action"]) && $params["action"]) {
    echo " action='" . $params["action"] . "'";
}
echo ">";
echo "<input type='hidden' name='MAX_FILE_SIZE' value='300000000' />";

// Navigation buttons
if ($params["buttons"]) {
    echo "<div class='admin-edit__toolbar'><div class='admin-edit__toolbar-inner'><div class='admin-edit__toolbar-copy'><h2 class='admin-edit__toolbar-title'>" . $adminEditToolbarTitle . "</h2><p class='admin-edit__toolbar-text'>Renseignez les champs puis enregistrez.</p></div><div class='admin-edit__actions'><input type='button' class='admin-edit__action--secondary' value='Annuler' onclick='history.go(-1)'> <input id='btn_submit' type='button' value='Sauver'>";

    if ($params["displayDraft"]) {
        echo "<input id='btn_save' class='admin-edit__draft-button' type='button' value='Enregistrer comme brouillon'>";
    }
    echo "</div></div></div>";
}
echo "<div class='admin-edit__panel'>";
echo "<table class='dbobjecttable'>";
$id = false;

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
            if (!$this->isProtected($colonne[0])) {
                if ($colonne[0] == "id") {
                    $id = true;
                }
                echo "<tr" . ($hidden ? " style='display:none'" : "") . " id='" . $colonne[0] . "'>";
                echo "<th style='white-space:nowrap'>" . $colonnes[$colonne[0]] . (isset($this->attributeDescriptions()[$colonne[0]]) ? "<sup class='field_help' title=\"" . $this->attributeDescriptions()[$colonne[0]] . "\">?</sup>" : "") . "</th>";
                echo "<td>";
                echo "<table><tr>";
                foreach ($colonne as $col) {
                    echo "<td>" . displayField($this, $col, $default, $params["filter"][$col] ?? null) . "</td>";
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
                            echo $colonnes[$colonne] . (isset($this->attributeDescriptions()[$colonne]) ? "<sup class='field_help' title=\"" . $this->attributeDescriptions()[$colonne] . "\">?</sup>" : "");
                        }
                    } else // Otherwise show default text
                    {
                        echo $colonnes[$colonne] . (isset($this->attributeDescriptions()[$colonne]) ? "<sup class='field_help' title=\"" . $this->attributeDescriptions()[$colonne] . "\">?</sup>" : "");
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
                } else if (!$this->isProtected($colonne)) {
                    if ($colonne == "id") {
                        $id = true;
                    }
                    echo "<tr" . ($hidden ? " style='display:none'" : "") . " id='row_" . $colonne . "'>";
                    echo "<th>" . $colonnes[$colonne] . (isset($this->attributeDescriptions()[$colonne]) ? "<sup class='field_help' title=\"" . $this->attributeDescriptions()[$colonne] . "\">?</sup>" : "") . "</th>";

                    echo "<td>" . displayField($this, $colonne, $default, $params["filter"][$colonne] ?? null) . "</td>";

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
            echo "<th>" . $colonne . (isset($this->attributeDescriptions()[$key]) ? "<sup class='field_help' title=\"" . $this->attributeDescriptions()[$key] . "\">?</sup>" : "") . "</th><td>";
            // Default vs specific elements?
            if (isset($params["widget"]) && isset($params["widget"][$key])) {
                echo $params["widget"][$key]($this, $key);
            } else {
                echo displayField($this, $key, null, $params["filter"][$key] ?? null);
            }
            echo "</td></tr>";
        }
    }
};
echo "</table>";
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
    $(function () {
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
            var formData = new FormData(form);

            console.log("=== SUBMIT START ===");

            // Cropped image blobs
            if (window.croppedImages) {
                for (let key in window.croppedImages) {
                    let blob = window.croppedImages[key];

                    if (blob) {
                        console.log("Ajout image :", key, blob);
                        formData.append(key, blob, key + '.jpg');
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
                        $("#btn_submit").prop("disabled", false);

                        <?php  } ?>

                    } else {
                        alert(response.message);
                        $("#btn_submit").prop("disabled", false);
                    }
                },

                error: function () {
                    alert("Une erreur s'est produite. Veuillez réessayer plus tard.");
                    $("#btn_submit").prop("disabled", false);
                }
            });

        });

    });
</script>
