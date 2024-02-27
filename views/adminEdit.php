<? 
	/* EXEMPLE D'APPEL:
	
	$params=array(
		"fields" => array(array("id","IDadministrateur_charge"),array("date","[heurerendezvous] [rue] [npa] [localite]"),array("IDadresse","todo")),
		"page" => $_GET["page"],
	);
	
	*/
?>



<style>
	.navTab a {border:1px solid black; display:inline-block; width:40px; height:40px; padding:5px; border-right:0px;}
</style>
<script>

				
		function previewFile(input, img) {
		  
		  var file    = document.querySelector('input[type=file]').files[0];
		  var reader  = new FileReader();
			
		  reader.addEventListener("load", function () {
			 img.css('background-image', 'url("' + reader.result + '")')
			 $("#"+input).val(file.name);
			//preview.src = reader.result;
		  }, false);

		  if (file) {
			reader.readAsDataURL(file);
		  }
		}
		
	</script>
<?
	// Défini les valeurs par défaut
	if (!isset($params["displayDraft"])) $params["displayDraft"]=false;		// Affichage du bouton de sauvegarde sans contrôle
	if (!isset($params["buttons"])) $params["buttons"]=true;				// Affichage des boutons de navigation


		
	function displayField ($object, $key, $default="") {

		$type=$object->getFieldType($key);
		$class=($object->isRequired($key)?"required ":"");
		switch ($type) {
			case "fk" :
				// Retourne la valeur texte de ce champ
				$txt="<select class='".$class."' name='".$key."' id='".$key."' ><option value=''>Choisissez...</option>";
				// Charge les valeurs et les affiche
				foreach($object->getValues($key) as $value) {
					$txt.="<option value='".$value->getId()."'".($default==$value->getId() || (is_null($default) && $value->getId()==$object->get($key))?" selected":"").">".$value->getLabel()."</option>";
				}
				$txt.="</select>";
				return $txt;
				break;
			case "date" :
				$datewg=new \widget\DateWidget($key, "", $object->get($key));
				if ($object->isProtected($key)) $datewg->disable();
				return $datewg->getString("defaultDate.php");
				break;
			case "time" :
				$datewg=new \widget\DateWidget($key, "", $object->get($key));
				if ($object->isProtected($key)) $datewg->disable();
				return $datewg->getString("defaultTime.php");
				break;
			case "datetime" :
				$datewg=new \widget\DateWidget($key, "", $object->get($key));
				if ($object->isProtected($key)) $datewg->disable();
				return $datewg->getString("defaultDateTime.php");
				break;
			case "daterange" :
				$datewg=new \widget\DateWidget($key, "", ($object->get($key)?$object->get($key):$default), ($object->get($key."_fin")?$object->get($key."_fin"):$default));
				if ($object->isProtected($key)) $datewg->disable();
				return $datewg->getString("defaultDateRange.php");
				//return "<input class='".$class."' type='text' value='".date_format(date_create($object->get($key)), 'd.m.Y H:i:s')."'>";
				break;
			case "timezone" :
				$str= "<select name='".$key."' id='".$key."'>";

				$timezones = timezone_identifiers_list();
				foreach ($timezones as $timezone) {
					$tz=(new DateTimeZone($timezone))->getOffset(new DateTime())/3600;
					$str.= '<option value="' . $timezone . '" '.($object->get($key)==$timezone?"selected":"").'>' . $timezone." (UTC".($tz>=0?"+":"").($tz).")" . '</option>';
				}

				$str.= "</select>";
				return $str;
				break;

			case "latlong" :
				$str= "<input class='".$class."' name='".$key."[]' id='".$key."_lat' type='text' value='".$object->get($key)->lat."'> <input class='".$class."' name='".$key."[]' id='".$key."_long' type='text' value='".$object->get($key)->long."'>";
				$str.="<div id='map_".$key."' style='width:100%; height:200px; margin-top:5px; margin-bottom:5px; border-radius:10px; border:1px solid black'></div>";
				$str.="<script>var currentMarker;\nvar map_".$key.";\nfunction initMap() {\nmap_".$key." = new google.maps.Map(document.getElementById('map_".$key."'), {\n";
				$str.="    center: { lat: ".($object->get($key)->lat?$object->get($key)->lat:0).", lng: ".($object->get($key)->long?$object->get($key)->long:0)." },\n";
				$str.="    zoom: ".($object->get($key)->lat?13:3)."\n  });";
				$str.="	currentMarker = new google.maps.Marker({position: { lat: ".($object->get($key)->lat?$object->get($key)->lat:0).", lng: ".($object->get($key)->long?$object->get($key)->long:0)." },map: map_".$key." });\n";
   // Ajoutez un gestionnaire d'événements pour capturer les coordonnées lors d'un clic sur la carte
  $str.="map_".$key.".addListener('click', function(event) {\n";
  $str.="  var latitude = event.latLng.lat();$('#".$key."_lat').val(latitude);\n";
  $str.="  var longitude = event.latLng.lng();$('#".$key."_long').val(longitude);\n";
  $str.="if (currentMarker) { currentMarker.setMap(null); }"; // Efface le marqueur existant
  $str.="	currentMarker = new google.maps.Marker({position: { lat: latitude, lng: longitude },map: map_".$key." });\n";
  $str.="});\n";
	$str.="}\n</script>";

				return $str;
			
			break;

			case "integer" :
			case "float" :
				// Si il y a des valeurs prédéfinies, affiche une liste à choix
				if (isset($object::attributeValues()[$key])) {
					$str="<select class='".$class."' name='".$key."' id='".$key."'>";
					foreach ($object::attributeValues()[$key] as $option) {
						$str.= "<option value='".$option[0]."' ".($option[0]==$object->get($key)?"selected":"").">".$option[1]."</option>";
					}					
					$str.='</select>';
					return $str;
				} 
					// Sinon, retourn un simple champ
					$str= "<input class='".$class."' name='".$key."' id='".$key."' type='text' value='".$object->get($key)."'";
					if (isset($object::attributePlaceholder()[$key])) {
						$str.= " placeholder='".str_replace("'","&apos;",$object::attributePlaceholder()[$key])."' ";
					}
					$str.= ">";
					return $str;
				break;
			case "mail" :
			case "string" :
				if (isset($object::attributeValues()[$key])) {
					$str="<select class='".$class."' name='".$key."' id='".$key."'>";
					foreach ($object::attributeValues()[$key] as $option) {
						$str.= "<option value='".$option[0]."' ".($option[0]==$object->get($key)?"selected":"").">".$option[1]."</option>";
					}					
					$str.='</select>';
					return $str;
				} 
				$tmpTxt=($object->get($key)!==null && $object->get($key)!=""?$object->get($key):$default);
				$str= "<input  class='".$class."' name='".$key."' id='".$key."' style='width:100%' type='text' value='".str_replace("'","&apos;",($tmpTxt===null?"":$tmpTxt))."'";
				if (isset($object::attributePlaceholder()[$key])) {
					$str.= " placeholder='".str_replace("'","&apos;",$object::attributePlaceholder()[$key])."' ";
				}
				if (isset($object::attributeLength()[$key])) {
					$str.= "maxlength='".$object::attributeLength()[$key]."'  onkeyup='countChar($(this), ".$object::attributeLength()[$key].")' onkeypress='countChar($(this), ".$object::attributeLength()[$key].")' ><div class='char_count'> max ".$object::attributeLength()[$key]." caractères</div>";
				} else {
					$str.= ">";
				}
				
				return $str;
				break;
			case "text" :
				$str=$object->get($key);
				$tmp= "<textarea class='".$class."' name='".$key."' id='".$key."' style='width:100%'";
				if (isset($object::attributeLength()[$key])) {
					$tmp.= "maxlength='".$object::attributeLength()[$key]."' onkeyup='countChar($(this), ".$object::attributeLength()[$key].")' onkeypress='countChar($(this), ".$object::attributeLength()[$key].")' >".$str."</textarea><div class='char_count'> max ".$object::attributeLength()[$key]." caractères</div>";
				} else {
					$tmp.=">".$str."</textarea>";
				}				
				
				return $tmp;
				
			case "parameters" :
				$str=$object->get($key); // $str: Contenu du champ  $key: Nom du champ   $class: Classe du champ

				// Valeur enregistrées
				$tmp= "<input type='hidden' name='".$key."' id='".$key."' style='width:100%' value='".str_replace("'","&apos;",$str)."'>";

				// Decode le JSon pour récupérer les données et les afficher dans les champs
				$json = json_decode($str, true);
				
				// cherche dans la liste des paramètres tous ceux associés à cette classe
				$parameters=new \dbobject\ArrayParameter();
				$params= array();	
				$params["filter"] = "typeobject='".str_replace("\\","\\\\",$object::class)."'";
				$parameters->load($params);
				
				// Les affiches tous
				$tmp.="<div class='form_parameter_".$key."' data-src='".$key."'>";
				foreach($parameters as $parameter) {
					
					switch ($parameter->get("type")) {
						case "string" :	
							$tmp.=$parameter->get("name").":\n";
							$tmp.="<input type='text' name='".$parameter->get("code")."' value='".(isset($json[$parameter->get("code")])?$json[$parameter->get("code")]:"")."'>";
							break;
						case "integer" :	
							$tmp.=$parameter->get("name").":\n";			
							$tmp.="<input type='number' name='".$parameter->get("code")."' value='".(isset($json[$parameter->get("code")])?$json[$parameter->get("code")]:"")."'>";
							break;
						case "checkbox" :				
							$tmp.="<div><input type='checkbox' name='".$parameter->get("code")."' ".(isset($json[$parameter->get("code")]) && $json[$parameter->get("code")]==true?" checked":"")."> ";
							$tmp.=$parameter->get("name")."</div>";
							break;
						case "select" :	
							$tmp.=$parameter->get("name").":\n";			
							$tmp.="<select name='".$parameter->get("code")."'>";
							
							$values=explode(";",$parameter->get("value"));
							foreach ($values as $value) {
								$tmp.="<option ".(isset($json[$parameter->get("code")]) && $json[$parameter->get("code")]==$value?" selected":"").">".$value."</option>";
							}
							
							$tmp.="</select>";
							break;
						}
				}
		ob_start();
?>
	<script>
		// Pour les paramètres
		$('.form_parameter_<?=$key?>').change(function () {
				tmparray={};
				// Input avec valeurs
				$(this).find("input").each(function(index, elem) {
					v1=$(elem).attr("name");
					if ($(this).attr('type') == 'checkbox')
						v2=$(elem).is(":checked");
					else
						v2=$(elem).val();
					eval("tmparray."+v1+"=v2");
				});
				// Select
				$(this).find("select").each(function(index, elem) {
					v1=$(elem).attr("name");
					v2=$(elem).val();
					eval("tmparray."+v1+"=v2");
				});

				//$("#parameters").val("Yopla");
				$("#"+$(this).attr("data-src")).val(JSON.stringify(tmparray));
				//console.log(tmparray);
				console.log(JSON.stringify(tmparray));
	
			
			});
	</script>
<?
	$tmp.= ob_get_clean();
				return $tmp;
				break;


			case "html" :
				$str=$object->get($key);
				return "<textarea  class='".$class." summernote' name='".$key."' id='".$key."' style='width:100%'>".$str."</textarea>";
				break;
			case "boolean" : 
				return "<input type='hidden' id='".$key."' name='".$key."' value='0'>".
				"<input type='checkbox' name='".$key."' id='".$key."'".($object->get($key)>0?"checked":"")." value='1'>";
				break;
			case "image" :
				$output="<input name='".$key."' id='".$key."' type='hidden' value='".str_replace("'","&apos;",$object->get($key))."'>";
				$output.="<input class='".$class."' name='".$key."_file' id='".$key."_file' type='file' onchange='previewFile(\"".$key."\",$(\"#img_".$key."\"))'><br>";
				$output.="<div id='img_".$key."' src='' style='width:".(isset($object::attributeLength()[$key])?$object::attributeLength()[$key][0]:"200")."px; height:".(isset($object::attributeLength()[$key])?$object::attributeLength()[$key][1]:"200")."px; border:1px solid black; background:url(".$object->get($key)."); background-size:cover; background-position:center center'>";
				$output.="<div id='drag_img_".$key."' class='drag_img' data='#img_".$key."' style='width:100%; height:100%;'>";
				$output.="</div>";
				$output.="</div>";

				return $output;
				break;
			// Image redimentionnable
			case "sizedimage" :
			
		$output="<input type='hidden' id='".$key."' name='".$key."' value='".$object->get($key)."'>";
		$output.="<div><input type='file' id='imageFileInput_".$key."' accept='image/*' style='display:none'>";
		$output.="<input type='button' value='Choose image on disk...' onclick='$(\"#imageFileInput_".$key."\").click();' />";
		$output.="</div><div id='imgContainer_".$key."' style='position: relative;	display: inline-block; border: 1px solid black;	cursor: move;overflow: hidden; width:".(isset($object::attributeLength()[$key])?$object::attributeLength()[$key][0]:"200")."px; height:".(isset($object::attributeLength()[$key])?$object::attributeLength()[$key][1]:"200")."px;'>";
		if ($object->get($key)!="")
			$output.="<img id='myImage_".$key."' style='display: block; position: absolute; top: 0px; left: 0px; object-fit: contain; width:".(isset($object::attributeLength()[$key])?$object::attributeLength()[$key][0]:"200")."px;' src='".$object->get($key)."'>";
		

		$output.="</div><div>";
		$output.="	<input type='range' id='zoomSlider_".$key."' min='0' max='100' step='1' value='0'>";
		$output.="</div>";
		$output.="<input type='hidden' id='imageDataInput_".$key."' name='imageDataInput_".$key."'>";			
		ob_start();
?>
	<script>

		$(function() {
			var imgContainer_<?=$key?> = $('#imgContainer_<?=$key?>');
			var img_<?=$key?> = $("#myImage_<?=$key?>");
			var img1 = document.getElementById("myImage_<?=$key?>");
			var imgWidth_<?=$key?> = (img1?img1.naturalWidth:0);
			var imgHeight_<?=$key?> = (img1?img1.naturalHeight:0);
			var imageDataInput_<?=$key?> = $('#imageDataInput_<?=$key?>');
			var zoomSlider_<?=$key?> = $('#zoomSlider_<?=$key?>');
			var zoomValue_<?=$key?> = 1;
			var oldZoomValue_<?=$key?> = 1;

			if (imgHeight_<?=$key?>==0) {
				imgContainer_<?=$key?>.css("display","none");
				zoomSlider_<?=$key?>.css("display","none");
			}

			var containerWidth_<?=$key?> = imgContainer_<?=$key?>.width();
			var containerHeight_<?=$key?> = imgContainer_<?=$key?>.height();
			var maxZoom_<?=$key?> = Math.min(imgWidth_<?=$key?> / containerWidth_<?=$key?>, imgHeight_<?=$key?> / containerHeight_<?=$key?>);
			
			function updateCoords_<?=$key?>() {
				console.log ("updateCoords_<?=$key?>");
				var imgPosX = parseInt(img_<?=$key?>.css('left'));
				var imgPosY = parseInt(img_<?=$key?>.css('top'));
				
				// Calcul les position dans l'image de base
				var x1= -imgPosX*(maxZoom_<?=$key?>*zoomValue_<?=$key?>/10);
				var y1= -imgPosY*(maxZoom_<?=$key?>*zoomValue_<?=$key?>/10);
				var width=imgContainer_<?=$key?>.width()*(maxZoom_<?=$key?>*zoomValue_<?=$key?>/10)
				var height=imgContainer_<?=$key?>.height()*(maxZoom_<?=$key?>*zoomValue_<?=$key?>/10)
				
				// Mise à jour de la valeur imageDataInput avec les données de l'image recadrée
				var canvas = $('<canvas>')[0];
				canvas.width = width;
				canvas.height = height;
				var ctx = canvas.getContext('2d');
				ctx.drawImage(img_<?=$key?>[0], x1, y1, width, height, 0, 0, width, height);
				var imageData = canvas.toDataURL();
				imageDataInput_<?=$key?>.val(imageData);
			}
			function updateImg_<?=$key?>() {
				console.log ("updateImg_<?=$key?>");
			//	if (img.position().left>0) img.css('left', 0 + 'px');
			//	if (img.position().top>0) img.css('top', 0 + 'px');
				
				var imgSize = imgWidth_<?=$key?> / (maxZoom_<?=$key?>*zoomValue_<?=$key?>/10);

				img_<?=$key?>.css('width', imgSize + 'px');
				
				// Limite la position de l'image

				var imgPosX = parseInt(img_<?=$key?>.css('left'));
				var imgPosY = parseInt(img_<?=$key?>.css('top'));
				if (imgPosX>0) imgPosX=0;
				if (imgPosY>0) imgPosY=0;
				//console.log ("3: "+imgPosX+" - "+imgContainer.width()+" - "+img.width());
				if (imgPosX<imgContainer_<?=$key?>.width()-img_<?=$key?>.width()) imgPosX=imgContainer_<?=$key?>.width()-img_<?=$key?>.width();
				if (imgPosY<imgContainer_<?=$key?>.height()-img_<?=$key?>.height()) imgPosY=imgContainer_<?=$key?>.height()-img_<?=$key?>.height();
				//console.log ("4: "+imgPosX+" - "+imgContainer.width()+" - "+img.width());
	
				img_<?=$key?>.css('left', imgPosX + 'px');
				img_<?=$key?>.css('top', imgPosY + 'px');
				updateCoords_<?=$key?>();
			}

		// Gestionnaire d'événements pour le bouton "Sélectionner sur le disque"
		$('#imageFileInput_<?=$key?>').on('change', function(event) {
			console.log ("#imageFileInput_<?=$key?>.change()");
			$("#<?=$key?>").val(event.target.files[0]);
			var file = event.target.files[0];
			var reader = new FileReader();
			reader.onload = function(event) {
				// Efface l'image existante 
				img_<?=$key?>.remove();
				img_<?=$key?> = $('<img id="myImage_<?=$key?>" style="display: block;position: absolute;top: 0;	left: 0; object-fit: contain;">').attr('src', event.target.result).appendTo(imgContainer_<?=$key?>);
				img_<?=$key?>.on('load', function() {
					var img1 = document.getElementById("myImage_<?=$key?>");
					imgWidth_<?=$key?> = img1.naturalWidth;
					imgHeight_<?=$key?> = img1.naturalHeight;
					if (imgHeight_<?=$key?>>0) {
						imgContainer_<?=$key?>.css("display","");
						zoomSlider_<?=$key?>.css("display","");
					}					
					var containerWidth = imgContainer_<?=$key?>.width();
					var containerHeight = imgContainer_<?=$key?>.height();
					maxZoom_<?=$key?> = Math.min(imgWidth_<?=$key?> / containerWidth, imgHeight_<?=$key?> / containerHeight);
					zoomSlider_<?=$key?>.val(0);
					var mini=10/maxZoom_<?=$key?>;
					oldZoomValue_<?=$key?> =zoomValue_<?=$key?> = Math.pow(10-mini, (100-zoomSlider_<?=$key?>.val()) / 50 - 1)+mini;

					// Centre l'image
					var cx=  (containerWidth-(imgWidth_<?=$key?>/ (maxZoom_<?=$key?>*zoomValue_<?=$key?>/10)))*0.5;  // -0.5*(container width-largeur img resizé)
					var cy=  (containerHeight-(imgHeight_<?=$key?>/ (maxZoom_<?=$key?>*zoomValue_<?=$key?>/10)))*0.5;
					img_<?=$key?>.css('left', cx + 'px');
					img_<?=$key?>.css('top', cy + 'px');

					updateImg_<?=$key?>();
				});
			};
			reader.readAsDataURL(file);
		});

		// Gestionnaire d'événements pour le curseur de zoom
		zoomSlider_<?=$key?>.on('input', function() {
			console.log ("zoomSlider_<?=$key?>.input");
			var mini=10/maxZoom_<?=$key?>;
			zoomValue_<?=$key?> = Math.pow(10-mini, (100-zoomSlider_<?=$key?>.val()) / 50 - 1)+mini;
			
			// Recalcule la position de l'image pour centrer le zoom
			var imgPosX = parseInt(img_<?=$key?>.css('left'));
			var imgPosY = parseInt(img_<?=$key?>.css('top'));
			var imgPosX2=-(imgPosX-containerWidth_<?=$key?>/2)*(maxZoom_<?=$key?>*oldZoomValue_<?=$key?>/10);
			var imgPosY2=-(imgPosY-containerHeight_<?=$key?>/2)*(maxZoom_<?=$key?>*oldZoomValue_<?=$key?>/10);
			img_<?=$key?>.css('left', (containerWidth_<?=$key?>/2-(imgPosX2/ (maxZoom_<?=$key?>*zoomValue_<?=$key?>/10))) + 'px');
			img_<?=$key?>.css('top', (containerHeight_<?=$key?>/2-(imgPosY2/ (maxZoom_<?=$key?>*zoomValue_<?=$key?>/10))) + 'px');

			// Enregistre la dernière version
			oldZoomValue_<?=$key?>=zoomValue_<?=$key?>;
			updateImg_<?=$key?>();
		});

		// Gestionnaire d'événements pour le mouvement de la souris sur l'image
		imgContainer_<?=$key?>.on('mousedown', function(event) {
			console.log ("imgContainer_<?=$key?>.mousedown");
			event.preventDefault();
			var startX = event.clientX;
			var startY = event.clientY;
			var imgPosX = parseInt(img_<?=$key?>.css('left'));
			var imgPosY = parseInt(img_<?=$key?>.css('top'));
			var moveHandler = function(event) {
				event.preventDefault();
				var deltaX = event.clientX - startX;
				var deltaY = event.clientY - startY;
				img_<?=$key?>.css('left', imgPosX + deltaX + 'px');
				img_<?=$key?>.css('top', imgPosY + deltaY + 'px');
				//updateCoords_<?=$key?>();
			};
			var upHandler = function(event) {
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
<?
	$output.= ob_get_clean();
    
		
		/*		$output="<input name='".$key."' id='".$key."' type='hidden' value='".str_replace("'","&apos;",$object->get($key))."'>";
				$output.="<input class='".$class."' name='".$key."_file' id='".$key."_file' type='file' onchange='previewFile(\"".$key."\",$(\"#img_".$key."\"))'><br>";
				$output.="<div id='img_".$key."' src='' style='width:400px; height:200px; border:1px solid black; background:url(".$object->get($key)."); background-size:cover; background-position:center center'>";
				$output.="<div id='drag_img_".$key."' class='drag_img' data='#img_".$key."' style='width:100%; height:100%;'>";
				$output.="</div>";
				$output.="</div>";*/

				return $output;
				break;
			case "password" :
				return "*****";
				break;
			case "undefined" :
				return "";
				break;
			default:
				return $object->get($key);
		}
			
	}
	
	// Charge les infos des objets
	$colonnes=$this->attributeLabels();
?>
	<script>
		//fonctions génériques de validation
		function countChar(objet, limit) {
			if (objet.val().length>limit) {
				objet.val(objet.val().substr(0,limit));
			}
			objet.nextAll(".char_count").html(objet.val().length+" sur "+limit+" caractères");
		} 
	</script>
	<style>
		table.dbobjecttable {width:100%}
		table.dbobjecttable table {width:100%}
		table.dbobjecttable td, table.dbobjecttable th  {vertical-align:top}
		table.dbobjecttable td table td  {padding-left:15px;}
		table.dbobjecttable td table td:first-of-type  {width:1%; padding-left:0px;}
		table.dbobjecttable+th {white-space:nowrap}
		table.dbobjecttable+td {width:100%}
	</style>

<?
	
	// Affiche l'entête
	if (!isset($params["form"]) || $params["form"]==true) {
		echo "<form id='formulaire-edit' method='POST' enctype='multipart/form-data'";
		if ($params["action"]) {echo " action='".$params["action"]."'";}
		echo ">";
	}
	echo "<input type='hidden' name='MAX_FILE_SIZE' value='300000000' />";

	// Affichage des boutons de navigation
	if ($params["buttons"]) {
		echo "<div style='position:fixed; z-index:10; background:#FFFFFF; height:50px; width:100%;left:0px; padding:5px;'><div class='container'><div class='row'><input type='button' value='Annuler' onclick='history.go(-1)'> <input id='btn_submit' type='button' value='Sauver'>";
	
		if ($params["displayDraft"]) echo "<input id='btn_save' type='button' value='Enregistrer comme brouillon'>";
		echo "</div></div></div><div style='height:60px;'></div>";

	}
	echo "<table class='dbobjecttable'>";
	$id=false;
	
	// Si le paramètre des champs à afficher à été passé
	if (isset($params["fields"]) ) {
		// Affiche uniquement les champs demandés
		foreach ($params["fields"] as $colonne) {
			$hidden=false;
			$default=NULL;
			if (is_array($colonne)) {
				// C'est un array... qu'en est-il du deuxième élément? Valeur par défaut ou champ?
				if ( !isset($colonne[1]) || is_numeric($colonne[1]) || !isset($colonnes[$colonne[1]])) {
					if (isset($colonne[2])) $hidden=$colonne[2];
					if (isset($colonne[1])) $default=$colonne[1];
					$colonne=$colonne[0];
				}
			}
			// Seulement si champ actif
			if (is_array($colonne)) {
				if (!$this->isProtected($colonne[0])) {
					if ($colonne[0]=="id") $id=true;
					echo "<tr".($hidden?" style='display:none'":"")." id='".$colonne[0]."'>";
					echo "<th style='white-space:nowrap'>".$colonnes[$colonne[0]].(isset($this->attributeDescriptions()[$colonne[0]])?"<sup class='field_help' title=\"".$this->attributeDescriptions()[$colonne[0]]."\">?</sup>":"")."</th>";
					echo "<td>";
					echo "<table><tr>";
					foreach ($colonne as $col) {
						echo "<td>".displayField($this,$col,$default)."</td>";
					}
					echo "</tr></table>";
					echo "</td>";
					echo "</tr>";
				}				
			} else 
			// Est-ce que c'est un séparateur, c'est à dire pas un champ
			if ($colonne[0]=="{") {
				echo "<tr><td colspan=2>";
				
				if (substr($colonne,1,3)=="hr}" || substr($colonne,1,3)=="hr:") echo "<hr>";
				if (substr($colonne,1,6)=="title:") echo "<h1>".substr($colonne,7,strlen($colonne)-8)."</h1>";
				if (substr($colonne,1,9)=="subtitle:") echo "<h1>".substr($colonne,10,strlen($colonne)-11)."</h1>";
				if (substr($colonne,1,5)=="text:") echo "<p>".substr($colonne,6,strlen($colonne)-7)."</p>";
				
			} else
			// Est-ce qu'une fonction existe avec ce nom, pour remplacer l'affichage
			if (function_exists('fct_'.$colonne)) {
				$display=call_user_func('fct_'.$colonne,$this,$colonne, $default);
				
				if ($colonne=="id") $id=true;
				echo "<tr".($hidden?" style='display:none'":"")." id='row_".$colonne."'>";
					echo "<th>";
						// S'il y a deux objets dans la chaîne de retour
						if (is_array($display)) {
							if (count($display)>1) {
								if (is_array($display[0]) && count($display[0])>1) {
									echo $display[0][0]."<sup class='field_help' title=\"".$display[0][1]."\">?</sup>";
								} else {
									echo $display[0];
								}
							} else {
								echo $colonnes[$colonne].(isset($this->attributeDescriptions()[$colonne])?"<sup class='field_help' title=\"".$this->attributeDescriptions()[$colonne]."\">?</sup>":"");
							}
							
						} else
						// Sinon, affiche le texte standard
							echo $colonnes[$colonne].(isset($this->attributeDescriptions()[$colonne])?"<sup class='field_help' title=\"".$this->attributeDescriptions()[$colonne]."\">?</sup>":"");
					echo "</th>";
					echo "<td>";
						if (is_array($display)) {
							if (count($display)>1) {
								echo $display[1];

							} else {
								echo $display[0];							
							}
						} else
						// Sinon, affiche le texte standard
						echo $display;
					echo "</td>";
				echo "</tr>";				
				
			} else			
			//if (!$this->isProtected($colonne))  // Si champ demandé explicitement, pas de raison de le cacher
			{
				if ($colonne=="id") $id=true;
				echo "<tr".($hidden?" style='display:none'":"")." id='row_".$colonne."'>";
				echo "<th>".(isset($colonnes[$colonne])?$colonnes[$colonne]:$colonne).(isset($this->attributeDescriptions()[$colonne])?"<sup class='field_help' title=\"".$this->attributeDescriptions()[$colonne]."\">?</sup>":"")."</th>";
				

					echo "<td>".displayField($this,$colonne,$default)."</td>";
				
				
				
				echo "</tr>";
			} 
		}		
	} else {
		// Sinon affiche tout
		foreach ($colonnes as $key => $colonne) {
			
			// Seulement si champ actif
			if (!$this->isProtected($key)) {
				if ($key=="id") $id=true;
				echo "<tr id='row_".$key."'>";
				echo "<th>".$colonne.(isset($this->attributeDescriptions()[$key])?"<sup class='field_help' title=\"".$this->attributeDescriptions()[$key]."\">?</sup>":"")."</th><td>";
				// Affichage par défaut ou elements spécifique?
				if (isset($params["widget"]) && isset($params["widget"][$key])) {
					echo $params["widget"][$key]($this, $key);
				} else
					echo displayField($this,$key);
				echo "</td></tr>";
			}
		}
	};
	echo "</table>";
	if (!$id && $this->getId()!="") echo "<input type='hidden' id='id' name='id' value='".$this->getId()."'>";
	if (!isset($params["form"]) || $params["form"]==true) {
		echo "</form>";
	}
?>

  <script>
  $( function() {
	 $( "<span style='position:absolute;font-size:150%; margin-left:2px; margin-top:-5px '>*</span>" ).insertAfter( ".required" );
	/* $("#btn_save").click(function() {
		 $("#formulaire-edit").submit();
	 });*/
	 $("#btn_submit").click(function() {
		 
		 let serform=$("#formulaire-edit").serialize()
		 if (serform.length>6000000) {
			 alert ("Image too big (max 6M)\nResize it or zoom it more");
			 return;
		 }
		 
		 // Grise le bouton
		 $(this).prop("disabled", true);
		// Contrôle la validitée des données en ajax
		
		$.post('/ajax/check.php?type=<?=$this->tableName()?>', serform, function(data) {
			if (data!="") {
				// Si pas ok, affiche le message d'erreur
				alert(data);
				$("#btn_submit").prop("disabled", false);
			} else {
				$("#formulaire-edit").submit();
			}
			
		})
		  .fail(function() {
			alert( "Sorry, we encounter an error while creating the license." );
			$("#btn_submit").prop("disabled", false);
		  });

	 });
	 
	 $("#formulaire-edit").submit(function (event) {
				
				event.preventDefault();

				var form = $(this);
				var url = form.attr('action');
				var formData = new FormData(this);

				$.ajax({
					type: 'POST',
					url: url,
					data: formData,
					cache: false,
					contentType: false,
					processData: false,
					success: function(data) {
						var response = JSON.parse(data);
						if (response.success) {
<?
		// Est-ce qu'une page de succès est définie? Si oui, redirige vers cette page en créan un formaulaire
		if (isset($params["success"]) && $params["success"]!="") {
			
?>

		// Est-ce que le paramètre de succès est une commande javascript? Si oui, l'appelle
		if ("<?=$params["success"]?>".indexOf("()")>0) {		
			eval("<?=$params["success"]?>");
		} else {

			// Création du formulaire
			var form_result = $('<form></form>');
			form_result.attr('method', 'post');
			form_result.attr('action', '<?=$params["success"]?>');
			// Ajout des champs de formulaire
			var id = $('<input type="text" name="id" value="'+response.id+'" />');
			form_result.append(id);
			$("body").append(form_result);

			// Envoi du formulaire
			form_result.submit();
		}
		
<? } else {
?>		
		
		
		// Sinon, affiche simplement un message de succès
							
							
							alert ("Données enregistrées");
							$("#btn_submit").prop("disabled", false);
							
<? } ?>
							
						} else {
							alert(response.message);
							$("#btn_submit").prop("disabled", false);
						}
					},
					error: function() {
						alert("Une erreur s'est produite. Veuillez réessayer plus tard.");
						$("#btn_submit").prop("disabled", false);
					}
				});
			});
	 
	 
 
    
  } );
  </script>
