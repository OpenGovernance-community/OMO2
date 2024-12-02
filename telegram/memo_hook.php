<?php
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/openai.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/telegram.php");
	
	// Liste des variable de configuration 
	$minTimeMessage=10; // Durée minimum en seconde du message pour justifier une transformation
					
	// récupération des données envoyées par Telegram, transfo en objet
	$content = file_get_contents('php://input');
	$update = json_decode($content, true);
	
	//if (!isset($update['message']))
		//die ("Access denied");
	
	// Connecte l'utilisateur en récupérant dans la base le profil lié à cet ID
	$user=new \dbObject\User();
	$user->load(["telegramID",$update['message']['from']['id']]);
	

	// Fonction 
		// Traite les différents boutons
	function handleCallbackQuery($callbackQuery) {
		// Extraire les informations pertinentes de la callback query
		$callbackId = $callbackQuery['id'];
		$callbackData = $callbackQuery['data'];
		$chatId = $callbackQuery['message']['chat']['id'];
		
		
		if (substr($callbackData,0,9) == 'btn_share') {
			// Récupère le dernier document généré par cet utilisateur
			$data=loadLocalSession($callbackQuery['message']['chat']['id']);
			
			$document=new \dbObject\Document();
			$document->load($data->lastDoc);
			if ($document->getId()>0) {
				// Génère un code d'accès
				if ($document->get("codeview")==null) {
					$document->set("codeview",bin2hex(openssl_random_pseudo_bytes(10)));
					$document->save();
				}
			
				// Envoie un message avec le code
				$msg = sendMessage($callbackQuery['message']['chat']['id'],"https://systemdd.ch/memo/".$document->getId().($document->get("codeview")?"/".$document->get("codeview"):""),null,$callbackQuery['message']['message_thread_id']);		
			} else
				$msg = sendMessage($callbackQuery['message']['chat']['id'],"Le fichier n'a pas été trouvé.",null,$callbackQuery['message']['message_thread_id']);		

		} else
		if ($callbackData == 'btn_delete') {
				$buttons =  [[
					['text' => 'Le résumé', 'callback_data' => 'btn_del_resume'],
					['text' => 'Le fichier', 'callback_data' => 'btn_del_file'],
					['text' => 'Tout', 'callback_data' => 'btn_del_all']
				]];
				$msg = sendMessage($callbackQuery['message']['chat']['id'],"Que dois-je effacer ?",$buttons,$callbackQuery['message']['message_thread_id']);		
		} 
		
		// Répondre à la callback query pour indiquer qu'elle a été traitée
		file_get_contents("https://api.telegram.org/bot".TOKEN."/answerCallbackQuery?callback_query_id=".$callbackId);  // ."&text=SUCCESS".$callbackId


	}
	// **********************************************
	// Traite les Callback Query, c'est à dire les appels de boutons
	// **********************************************
	if (isset($update['callback_query'])) {
		// Gérer la callback query
		handleCallbackQuery($update['callback_query']);
		exit;
	}
	// **********************************************
	// Traite les images
	// **********************************************
	// Comportement de base: attache les images au dernier document généré
	if (isset($update['message']['photo'])) {
		// Quitte la traduction si inactif (à transformer en fonction du profil)
		$data=loadLocalSession($update['message']['from']['id']);

		// Récupérer le lien de l'image
		$photo=end($update['message']['photo']);	 
		$file_id = $photo['file_id'];

		// Attache le media son au document
		$media=new \dbObject\Media();
		$media->set("title",$update['message']['caption']);
		$media->set("filename","download.png");
		$media->set("contenttype","image/png");
		
		$media->set("IDdocument",$data->lastDoc);
		$media->set("IDtype",2); // Image
		$media->set("IDstorage",1); // Telegram
		$media->set("accesskey",$file_id); 
		$media->save();	
		
	}


	// **********************************************
	// Traite les messages vocaux
	// **********************************************
	if (isset($update['message']['voice'])) {
		
		// Quitte la traduction si inactif (à transformer en fonction du profil)
		$data=loadLocalSession($update['message']['from']['id']);
		// Vérifie la validité du compte (selon statut dans les données stockées)
		if (isset($data->active) && !$data->active)
			exit;
	
		// Récupérer les informations de l'enregistrement audio
		$voice = $update['message']['voice'];
		$file_id = $voice['file_id'];
		$duration = $voice['duration'];
	
		// Si durée suffisante pour valoir la peine d'être traduit
		if ($duration>=$minTimeMessage) {
			
			$waitmsg=sendMessage($update['message']['chat']['id'],"Un petit moment, je retranscrit tout ça...",null,(isset($update['message']['message_thread_id'])?$update['message']['message_thread_id']:null));	

			// Libère le script pour exécution
			set_time_limit(240); // Set the max execution time
			ignore_user_abort(true);
			header('Connection: close');
			flush();
			fastcgi_finish_request();

			// *****************************
			// Récupère le fichier
			// *****************************
			$file_info = getTelegramFile($file_id);
			// Récupérer le lien direct vers le fichier
			$file_url = $file_info['result']['file_path'];
			// Charger le contenu audio à partir de l'URL
			$audio_url = "https://api.telegram.org/file/bot".TOKEN."/$file_url";
			$audio_content = file_get_contents($audio_url);

			// Créer un fichier local temporaire pour stocker le contenu audio
			$temp_file_path = tempnam(sys_get_temp_dir(), 'audio');
			file_put_contents($temp_file_path, $audio_content);

			// ****************************
			// Envoie à Whisper
			// ****************************

		   // Prepare the headers
			$headers = [
				'Authorization: Bearer ' . OpenAI,
			];

			// Create a CURLFile object / preparing the sound file for upload
			$cfile = new CURLFile($temp_file_path);
			$cfile->setMimeType("audio/ogg");
			$cfile->setPostFilename("audio.ogg");

			// Initialize the cURL session
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			// Prepare the request body with the file and model
			$data2 = [
				'file' => $cfile,
				'model' => 'whisper-1',
			];

			// Set the request body
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data2);
			// Set option to return the result instead of outputting it
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Execute the cURL request and get the response
			$response = json_decode(curl_exec($ch));

			// Check if any error occurred during the request
			if(curl_errno($ch)){
				echo 'Request Error:' . curl_error($ch);
			}
	
			curl_close($ch); // Close the cURL session

			// Demande un résumé, des mots clés et une version retravaillée à ChatGPT
			$readable=say("Peux-tu générer un JSON pour le texte suivant, comprenant 4 entrée: une entrée 'titre' avec un titre pour ce document, une entrée 'resume' avec un résumé du texte en maximum 150 caractères, une entrée 'contenu' avec une mise en page lisible et formatée du texte (si nécessaire en HTML pour des titres ou des listes à puce) mais sans résumer le contenu et finalement une entrée 'hashtag' contenant un tableau avec 3 à 5 mots clés pertinents pour ce texte? Voici le texte : \n".$response->text);

			// Enregistre le retour pour debug
			$dataerr=json_decode("{}");
			$dataerr->GPTreturn=$readable;
			saveLocalSession ($dataerr,"error_log") ;				

			// Triate le JSON retourné avant de l'enregistrer
			$pattern = "/\{(.+?)\}/s";
			if (preg_match($pattern, $readable, $matches)) {
				// $matches[0] contient la correspondance complète, $matches[1] contient le JSON
				$readable = "{".$matches[1]."}";
				$dataerr->regexp=$readable;
				saveLocalSession ($dataerr,"error_log") ;				

			} else {
				sendMessage($update['message']['chat']['id'],"Désolé, problème de conversion du JSON...",null,(isset($update['message']['message_thread_id'])?$update['message']['message_thread_id']:null));		
				exit;
			}
			//$readable = preg_replace('/```json(.*)```/s', '$1', $readable);
			$readable=json_decode($readable);
			
			$dataerr->json=$readable;
			saveLocalSession ($dataerr,"error_log") ;				

			
			
			$title=$readable->titre;
			$resume=$readable->resume;
			$content=$readable->contenu;
			$hash = "#" . implode(" #", array_map(function($tag) {return str_replace(' ', '_', trim($tag)); }, $readable->hashtag));
			$dataerr->title=$title;
			$dataerr->resume=$resume;
			$dataerr->content=$content;
			$dataerr->hash=$hash;
			saveLocalSession ($dataerr,"error_log") ;				

			// *****************************************
			// Enregistre le memo dans la base de donnée
			// *****************************************
			// Rafraichi la connexion, car potentiellement perdu au regard du temps de traitement
			if ($user->getId()>0) {
				$user->refreshDbh();
				try {

				// Crée un document et le rattache à cet utilisateur
				$doc=new \dbObject\Document();
				
				$doc->set("title",$title);
				$doc->set("description",$resume);
				$doc->set("content",$content);
				$doc->set("keywords",$hash);
				$doc->set("IDuser",$user->getId());

				
				// Si généré sur un groupe, crée un code d'accès et affiche le lien avec le code
				if ($update['message']['chat']['id'] != $update['message']['from']['id']) {
					$doc->set("codeview",bin2hex(openssl_random_pseudo_bytes(10)));
				}
				
				$doc->save();


				$data->lastDoc=$doc->getId();
				saveLocalSession($data,$update['message']['from']['id']);
				
				// Attache le media son au document
				$media=new \dbObject\Media();
				$media->set("title",$title);
				$media->set("filename","download.oga");
				$media->set("contenttype","audio/ogg");
				$media->set("description",$resume);
				$media->set("IDdocument",$doc->getId());
				$media->set("IDtype",1); // Audio
				$media->set("IDstorage",1); // Telegram
				$media->set("accesskey",$file_id); 
				$media->save();
					
				} catch (Exception $e) {
					sendMessage($update['message']['chat']['id'],"Désolé, problème de génération du fichier...",null,(isset($update['message']['message_thread_id'])?$update['message']['message_thread_id']:null));		
				//	exit;
				}

			}
			// *****************************************
			// Envoie le résumé dans le groupe
			// *****************************************
			// Ajoute des boutons si dans une discussion simple
			if (isset($doc) && $doc->getId()>0 && $update['message']['chat']['id']==$update['message']['from']['id']) {
			$buttons =  [[
					['text' => 'Options', 'callback_data' => 'btn_options_'.$update['message']['id']],
					['text' => 'Delete', 'callback_data' => 'btn_delete'],
					['text' => 'Share', 'callback_data' => 'btn_share']
				]];
			} else  $buttons=null;

			deleteMessage ($update['message']['chat']['id'], $waitmsg);
			$msgID = sendMessage($update['message']['chat']['id'],"\xE2\xAC\x86 ".$resume."\n".$hash."\n".(isset($doc)?"https://systemdd.ch/memo/".$doc->getId().($doc->get("codeview")?"/".$doc->get("codeview"):""):""),$buttons,(isset($update['message']['message_thread_id'])?$update['message']['message_thread_id']:null));		
			
			// Stock le dernier message pour pouvoir l'effacer sur demande
			if ($msgID!=null) {
			// Sauve l'id du message
				$data->lastID=$msgID;
			}
			saveLocalSession($data,$update['message']['from']['id']);
		}
	}

	// **************************************************************
	// Traitement des commandes et des messages textes
	// **************************************************************
	if (($update['message']['text']) != "") {

		// COMMANDE : Connecter l'utilisateur à un compte SD2
		if(preg_match('/^\/connect/', $update['message']['text'])) {
			
			// Est-ce un canal direct avec le BOT, ou un groupe avec BOT partagé?
			if ($update['message']['chat']['id'] == $update['message']['from']['id']) {
				// Est-ce que l'utilisateur est déjà connecté?
				if (isset($user) && $user->getId()>0)
					sendMessage($update['message']['chat']['id'], "Vous êtes déjà connecté avec le compte de ".$user->get("username"));
				else
					sendMessage($update['message']['chat']['id'], "Pour connecter EasyMEMO à votre compte Telegram, éditez les paramètres de votre compte avec la valeur suivante pour le champ TelegramID: ".$update['message']['chat']['id']);

			} else {

				// Si non, envoie les infos et les instructuions
				sendMessage($update['message']['chat']['id'], "Pour connecter ce groupe à un projet, éditer les propriétés du projet avec les informations suivantes:\n\nChat ID: ".$update['message']['chat']['id'].", Group: ".$update['message']['message_thread_id'],null,$update['message']['message_thread_id']);
			}
		}
		else 
		
		// COMMANDE : Demander l'heure (fonction de test)   
		if (preg_match('/^\/time/', $update['message']['text'])) {

				$current=new DateTime();
				sendMessage($update['message']['chat']['id'], "It's ".$current->format("H:i"),null,$update['message']['message_thread_id']);

		}
		else 
		
		// COMMANDE : Supprime le dernier message résumé   
		if (preg_match('/^\/delete/', $update['message']['text'])) {

				$data=loadLocalSession($update['message']['from']['id']);

				if (isset($data->lastID)) {
					deleteMessage ($update['message']['chat']['id'], $data->lastID);	
					deleteMessage ($update['message']['chat']['id'], $update['message']['message_id']);	
				}		
				saveLocalSession($data, $update['message']['from']['id']);
		}
		else 
		// COMMANDE : Stop la transcription   
		if (preg_match('/^\/stop/', $update['message']['text'])) {

				$data=loadLocalSession($update['message']['from']['id']);
				sendMessage($update['message']['chat']['id'],"J'arrête les traductions pour ".$update['message']['from']['id'],null,$update['message']['message_thread_id']);
				$data->active=false;
				saveLocalSession($data, $update['message']['from']['id']);



		}
		else 
		// COMMANDE : Démarre une connexion avec le BOT, Bienvenue   
		if (preg_match('/^\/start/', $update['message']['text'])) {

			$data=loadLocalSession($update['message']['from']['id']);
			$data->active=true;
			saveLocalSession($data, $update['message']['from']['id']);
		}

		// l'utilisateur envoie autre chose qu'une commande reconnue
		else 
		// Est-ce une commande ou un simple commentaire?
		if(preg_match('/^\//', $update['message']['text'])) {
			// Si c'est une commande, elle est inconnue
			$id = "Commande inconnue.";
			sendMessage($update['message']['chat']['id'], $id);
		} else
		
		// Si c'est un commentaire, regarde si le BOT est référencé
		if(preg_match('/^@pottylicensebot/', $update['message']['text'])) {
			// Si oui, retourne un message avec la liste des commandes disponibles
			$id = "Je ne répond pas aux messages directs, utilisez les commandes.";
			sendMessage($update['message']['chat']['id'], $id);
		} else if ($update['message']['text']!="")
		// Si c'est un commentaire, regarde si le BOT est référencé
		 {
		 }

	} 	
	

			
			
	// Si nécessaire, sauve des infos en local pour faire office de session
	function saveLocalSession ($data,$name) {
				$file = fopen("data/".$name.".txt", 'w');
				echo fwrite($file,json_encode($data));
				fclose($file);
		}
		
	// Charge les infos sauvées localement
	function loadLocalSession($name) {
			if (file_exists("data/".$name.".txt")) {
				$handle = fopen("data/".$name.".txt", "r");
					$data = fread($handle, filesize("data/".$name.".txt"));
					fclose($handle);	
					$data=json_decode($data);
				
				} else $data=json_decode("{}");
				return $data;
	}


?>		
