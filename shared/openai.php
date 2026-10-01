<?php
	function say($demand, $systemInstruction = null) {

		ini_set('default_socket_timeout', 240);
		
		$system_param=array();
		$data=array();

		$defaultSystemInstruction = 'Tu es un assistant spécialisé dans les synthèses efficaces et pertinentes. Tu ne rajoute pas de titre, de fioritures ou de contexte aux résumés et listes produits.';
		$systemInstruction = is_string($systemInstruction) && trim($systemInstruction) !== ''
			? $systemInstruction
			: $defaultSystemInstruction;
		$system_param[]=array('role' => 'system', 'content' => $systemInstruction);

		// Endpoint de l'API de l'OpenAI
		$apiUrl = 'https://api.openai.com/v1/chat/completions';

		$data[]=array('role' => 'user', 'content' => $demand);

		// Créez le tableau des paramètres de la requête
		$params = array(
		  "model"=> MODEL,
			'messages' => array_merge($system_param,$data),
			'temperature' => 0.7
		   );
		$payload = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($payload === false) {
			return "";
		}

		// Configuration de la requête HTTP
		// Créez le contexte HTTP
		// Faites la requête HTTP à l'API
		if (!function_exists('curl_init')) {
			return "";
		}

		$ch = curl_init($apiUrl);
		curl_setopt_array($ch, array(
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer '.OpenAI,
				'Content-Type: application/json',
			),
			CURLOPT_POSTFIELDS => $payload,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CONNECTTIMEOUT => 15,
			CURLOPT_TIMEOUT => 240,
			CURLOPT_FAILONERROR => false,
		));

		$response = curl_exec($ch);

		// Si la requête a réussi, décodez la réponse JSON
		if ($response !== false) {
			$responseData = json_decode($response, true);
			if (isset($responseData['choices'][0]['message'])) {
				$generatedText = $responseData['choices'][0]['message']['content'];
				return $generatedText;
			} else {
				// Rien à dire non plus
				return "";
			}
		} else {
			// Rien à faire, le texte n'était pas solicité
			return "";
		}	
	}
?>
