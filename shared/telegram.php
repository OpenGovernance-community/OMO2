<?
	// Fonctions génériques pour Telegram

	function deleteMessage ($chat_id, $message_id,$thread=null) {


		// URL de l'API Telegram pour supprimer un message
		if (!$thread==null)
			$api_url = "https://api.telegram.org/bot".TOKEN."/deleteMessage?message_thread_id={$thread}&chat_id={$chat_id}&message_id={$message_id}";
		else
			$api_url = "https://api.telegram.org/bot".TOKEN."/deleteMessage?&chat_id={$chat_id}&message_id={$message_id}";
		// Effectuer la demande HTTP avec cURL
		$ch = curl_init($api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	// fonction qui envoie un message à l'utilisateur
	function sendMessage($chat_id, $message,$buttons=null, $thread=null) {
		if (!is_null($buttons)) {

			$keyboard = [
				'inline_keyboard' => $buttons
			];

			// Convert the keyboard array to JSON
			$keyboard_json = json_encode($keyboard);

			// Create the API call URL
			$q = http_build_query([
				'chat_id' => $chat_id,
				'message_thread_id' => $thread,
				'text' => $message,
				'reply_markup' => $keyboard_json
				]);
			$api_url = "https://api.telegram.org/bot".TOKEN."/sendMessage?".$q;

			// Send the request
			$response = file_get_contents($api_url);
			if ($response['ok'] === true) {
				$messageId = $response['result']['message_id'];
				return $messageId;
			} else {
				// Enregistre dans le répertoire DATA l'erreur retournée
				$data=json_decode("{}");
				$data->erreur=$result;
				saveLocalSession ($data,"error_log") ;				
			}			
		} else {	
			$q = http_build_query([
				'chat_id' => $chat_id,
				'message_thread_id' => $thread,
				'text' => $message
				]);
			$result = file_get_contents('https://api.telegram.org/bot'.TOKEN.'/sendMessage?'.$q);
			$response = json_decode($result, true);
			if ($response['ok'] === true) {
				$messageId = $response['result']['message_id'];
				return $messageId;
			} else {
				// Enregistre dans le répertoire DATA l'erreur retournée
				$data=json_decode("{}");
				$data->erreur=$result;
				saveLocalSession ($data,"error_log") ;				
			}

		}
	}
	
	// Fonction pour envoyer une requête à l'API de Telegram pour récupérer les informations sur le fichier
	function getTelegramFile($file_id) {
		$url = "https://api.telegram.org/bot".TOKEN."/getFile";
		$data = array(
			'file_id' => $file_id
		);

		// Envoyer la requête HTTP POST
		$options = array(
			'http' => array(
				'method' => 'POST',
				'header' => "Content-Type:application/x-www-form-urlencoded\r\n",
				'content' => http_build_query($data),
			),
		);
		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);

		// Renvoyer les données de réponse JSON décodées
		return json_decode($result, true);
	}
?>
