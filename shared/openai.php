<?php
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
			'temperature' => 0.7
		   );
		$payload = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($payload === false) {
			error_log('[openai-say] request_json_error='.json_last_error_msg());
			return "";
		}	

		// Configuration de la requête HTTP
		$options = array(
			'http' => array(
				'header'  => "Authorization: Bearer ".OpenAI."\r\nContent-Type: application/json\r\n",
				'method'  => 'POST',
				'content' => $payload,
				'ignore_errors' => true,
				'timeout' => 240
			)
		);

		// Créez le contexte HTTP
		$context  = stream_context_create($options);

		// Faites la requête HTTP à l'API
		$lastPhpError = null;
		$response = @file_get_contents($apiUrl, false, $context);
		if ($response === false) {
			$lastPhpError = error_get_last();
		}

		$httpStatus = '';
		$httpHeaders = array();
		if (function_exists('http_get_last_response_headers')) {
			$lastHeaders = http_get_last_response_headers();
			$httpHeaders = is_array($lastHeaders) ? $lastHeaders : array();
		} else {
			$legacyHeaders = ${'http_response_header'} ?? null;
			$httpHeaders = is_array($legacyHeaders) ? $legacyHeaders : array();
		}
		foreach ($httpHeaders as $header) {
				if (stripos((string)$header, 'HTTP/') === 0) {
					$httpStatus = (string)$header;
				}
		}

		// Si la requête a réussi, décodez la réponse JSON
		if ($response !== false) {
			$responseData = json_decode($response, true);
			$jsonError = json_last_error_msg();
			if (isset($responseData['choices'][0]['message'])) {
				error_log('[openai-say] status='.($httpStatus !== '' ? $httpStatus : 'unknown').', model='.MODEL.', request_bytes='.strlen($payload).', response_bytes='.strlen($response));
				$generatedText = $responseData['choices'][0]['message']['content'];
				return $generatedText;
			} else {
				$trace = array(
					'status' => $httpStatus !== '' ? $httpStatus : 'unknown',
					'model' => MODEL,
					'request_bytes' => strlen($payload),
					'response_bytes' => strlen($response),
					'json_error' => $jsonError,
					'response_preview' => substr($response, 0, 4000),
				);
				$traceJson = json_encode($trace, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				if ($traceJson === false) {
					$trace['response_preview'] = base64_encode(substr($response, 0, 4000));
					$traceJson = json_encode($trace, JSON_UNESCAPED_SLASHES);
				}
				error_log('[openai-say] response_without_message='.($traceJson !== false ? $traceJson : 'trace_encoding_failed'));
				// Rien à dire non plus
				return "";
			}
		} else {
			$trace = array(
				'status' => $httpStatus !== '' ? $httpStatus : 'unknown',
				'model' => MODEL,
				'request_bytes' => strlen($payload),
				'php_error' => is_array($lastPhpError) ? ($lastPhpError['message'] ?? 'unknown') : 'unknown',
			);
			$traceJson = json_encode($trace, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			error_log('[openai-say] request_failed='.($traceJson !== false ? $traceJson : 'trace_encoding_failed'));
			// Rien à faire, le texte n'était pas solicité
			return "";
		}	
	}
?>
