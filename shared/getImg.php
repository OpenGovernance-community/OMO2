<?
	// Retourne une image avec une adaptation de taille
	// Paramètres:
	// - url : localisation de l'image
	// - x : taille X souhaitée
	// - y : Taille Y souhaitée
	// - resize : Mode de redimentionnelement (crop, ...). x et y doivent êtres spécifiés
	// - refresh : Force le chargement et l'enregistrement
	// - ext : Force le traitement de l'extention (utile si l'url ne spécifie pas le type de fichier).
	
	// Récupération de l'extension (type de l'image)
	if (isset($_GET['url'])) {
		$url = $_GET['url'];
		
		// Obtiens le chemin du fichier à partir de l'URL
		
		// Obtiens l'extension du fichier (en minuscules)
		if (isset($_GET["ext"])) {
			$file_extension=$_GET["ext"];
		} else {
			$path_parts = pathinfo(parse_url($url, PHP_URL_PATH));
			$file_extension = strtolower($path_parts['extension']);
		}
		
		// Vérifie si l'extension est parmi celles autorisées (png, jpeg, jpg, gif)
		if (in_array($file_extension, array('png', 'jpeg', 'jpg', 'gif'))) {

			$directory = $_SERVER["DOCUMENT_ROOT"]. '/img/small/';
			$uid=md5($_GET["url"]);

			// Si refresh, efface tous les fichiers 
			if (isset($_GET["refresh"]) && $_GET["refresh"]) {

				$pattern = $directory . $uid.'*.'.$file_extension;

				$files = glob($pattern);

				if ($files !== false) {
					foreach ($files as $file) {
						if (is_file($file)) {
							unlink($file); // Supprime le fichier
						}
					}
				} 
			}
			
			// Création de l'url de l'image
			$name=$uid.(isset($_GET["x"])?"_x".$_GET["x"]:"").(isset($_GET["y"])?"_y".$_GET["y"]:"").(isset($_GET["resize"])?"_".$_GET["resize"]:"").".".$file_extension;
			
			// Cherche si l'image existe et que le mode refresh n'est pas actif
			$file_path =  $directory . $name;

			if ((!isset($_GET["refresh"]) || !$_GET["refresh"]) && file_exists($file_path)) {
				// Si trouvé, retourne simplement l'image

				// Envoie l'en-tête Content-Type
				
				$taille_fichier = filesize($file_path);
				if ($taille_fichier>100) {
				// Lis le contenu du fichier et envoie-le en réponse
					$mime_type = mime_content_type($file_path);
					header("Content-Type: $mime_type");
					readfile($file_path);	
					exit;
				}			
			} 
			
				// Sinon, redimentionne l'image
				// ************************************************
				// Charger l'image depuis l'URL, en fonction du type
				$opts = array('http' => array('header'=> 'Cookie: ' . $_SERVER['HTTP_COOKIE']."\r\n"));
				$context = stream_context_create($opts);
				$image = imagecreatefromstring(file_get_contents((strpos($url,"://")>0?$url:$_SERVER['DOCUMENT_ROOT'].$url),false,$context));
				


				// Récupérer la largeur et la hauteur de l'image originale
				$width = imagesx($image);
				$height = imagesy($image);

				// Calculer la nouvelle hauteur en fonction de la nouvelle largeur maximale
				//$new_height = floor($height * ($max_width / $width));
				
				if (!isset($_GET["x"]) && !isset($_GET["y"])) {
					// Aucune information de taille, il s'agit juste d'enregistrer la version téléchargée ou générée
					$new_width=$width;
					$new_height=$height;
				} else
				if (isset($_GET["x"]) && isset($_GET["y"])) {
					// Les deux dimentions sont définies
					$new_width=$_GET["x"];
					$new_height=$_GET["y"];
				} else {
					// Une seule des deux, la deuxième est calculée
					if (isset($_GET["x"])) {
						$new_width=$_GET["x"];
						$new_height=$height/$width*$_GET["x"];
					} else {
						$new_height=$_GET["y"];
						$new_width=$width/$height*$_GET["y"];
					}
				}
				// Créer une nouvelle image vide avec la nouvelle taille
				$new_image = imagecreatetruecolor($new_width, $new_height);
				imagesavealpha($new_image, true);
				$transparency = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
				imagefill($new_image, 0, 0, $transparency);
				
				// Redimensionner l'image originale vers la nouvelle image (pour l'instant, uniquement en resize
				imagecopyresized($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

				// Afficher l'image redimensionnée dans le navigateur, avec le bon mime type
				switch($file_extension) {
					case "jpg":
					case "jpeg":
						
						header('Content-Type: image/jpeg');
						imagejpeg($new_image,$file_path,80);
						imagejpeg($new_image);
						break;
					case "png":
						header('Content-Type: image/png');
						imagepng($new_image,$file_path,7);
						imagepng($new_image);
						break;
					case "gif":
						header('Content-Type: image/gif');
						imagegif($new_image);
						break;
				}


				// Libérer la mémoire utilisée par les images
				imagedestroy($image);
				imagedestroy($new_image);			


						
			} else {
			echo "Extension de fichier non prise en charge."; exit;
		}
	} else {
		echo "Paramètre 'url' manquant."; exit;
	}

?>
