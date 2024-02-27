<?
	function say($demand) {

		ini_set('default_socket_timeout', 240);
		
		$system_param=array();
		$data=array();

		$system_param[]=array('role' => 'system', 'content' => 'Tu es un assistant spécialisé dans les synthèses efficaces et pertinentes. Tu ne rajoute pas de titre, de fioritures ou de contexte aux résumés et listes produits.');

		// Endpoint de l'API de l'OpenAI
		$apiUrl = 'https://api.openai.com/v1/chat/completions';

		$data[]=array('role' => 'user', 'content' => $demand);

		// Créez le tableau des paramètres de la requête
		$params = array(
		  "model"=> MODEL,
			'messages' => array_merge($system_param,$data),
			'temperature' => 0.5
		   );

		// Configuration de la requête HTTP
		$options = array(
			'http' => array(
				'header'  => "Authorization: Bearer ".OpenAI."\r\nContent-Type: application/json\r\n",
				'method'  => 'POST',
				'content' => json_encode($params)
			)
		);

		// Créez le contexte HTTP
		$context  = stream_context_create($options);

		// Faites la requête HTTP à l'API
		$response = file_get_contents($apiUrl, true, $context);

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
