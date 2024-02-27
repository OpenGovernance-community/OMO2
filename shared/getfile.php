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
			$file_url = $file_info['result']['file_path'];
			$tmp_url = "https://api.telegram.org/file/bot".TOKEN."/$file_url";
			$file_content = file_get_contents($tmp_url);

			$headers = get_headers($tmp_url, 1);
			if (isset($headers['Content-Type'])) {
				$fileType = $headers['Content-Type'];
				header('Content-Type: '.$media->get("contenttype"));
				header('Content-Disposition: inline; filename="'.$media->get("filename").'"');
				//header('Content-Disposition: attachment; filename="downloaded.pdf"');
			} 

			echo $file_content;			
		
	}
	


?>
