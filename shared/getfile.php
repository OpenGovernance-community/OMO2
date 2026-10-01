<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/telegram.php");

	// Initialise le login
	$connected=checklogin();

	// Charge le document
	$media=new \dbObject\Media();
	$media->load($_GET["id"]);
	// ID non trouvé
	if (!$media->getId()>0) die ('ID non trouvé');;
	
	if (!$media->canView()) die ('Access denied');;
	
	// Si c'est un document de type Telegram
	if ($media->get("IDstorage")==1) {
			$file_info = getTelegramFile($media->get("accesskey"));
			// Récupérer le lien direct vers le fichier
			$file_url = is_array($file_info) ? ($file_info['result']['file_path'] ?? null) : null;
			$download = $file_url ? telegramDownloadFile($file_url) : null;
			if (!is_array($download) || empty($download['ok'])) {
				http_response_code(502);
				die('Unable to download Telegram file');
			}

			header('Content-Type: '.$media->get("contenttype"));
			header('Content-Disposition: inline; filename="'.$media->get("filename").'"');
			echo $download['content'];
		
	}
	


?>
