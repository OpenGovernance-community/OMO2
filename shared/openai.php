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
			if (function_exists('telegramTraceLog')) {
				telegramTraceLog('openai_say.request_json_error', array('json_error' => json_last_error_msg()));
			}
			return "";
		}
		if (function_exists('telegramTraceLog')) {
			telegramTraceLog('openai_say.started', array(
				'model' => MODEL,
				'request_bytes' => strlen($payload),
			));
		}

		// Configuration de la requête HTTP
		// Créez le contexte HTTP
		// Faites la requête HTTP à l'API
		if (!function_exists('curl_init')) {
			error_log('[openai-say] cURL extension is not available');
			if (function_exists('telegramTraceLog')) {
				telegramTraceLog('openai_say.request_failed', array(
					'status' => 'unknown',
					'curl_error' => 'cURL extension is not available',
				));
			}
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

		$requestCallback = function () use ($ch) {
			return curl_exec($ch);
		};
		$response = function_exists('telegramTraceRun')
			? telegramTraceRun('openai_say_request', $requestCallback)
			: $requestCallback();
		$curlErrorNumber = curl_errno($ch);
		$curlError = $curlErrorNumber ? curl_error($ch) : null;
		$curlInfo = curl_getinfo($ch);
		$httpStatus = isset($curlInfo['http_code']) ? (int)$curlInfo['http_code'] : 0;

		// Si la requête a réussi, décodez la réponse JSON
		if ($response !== false) {
			$responseData = json_decode($response, true);
			$jsonError = json_last_error_msg();
			if (isset($responseData['choices'][0]['message'])) {
				error_log('[openai-say] status='.($httpStatus > 0 ? $httpStatus : 'unknown').', model='.MODEL.', request_bytes='.strlen($payload).', response_bytes='.strlen($response));
				if (function_exists('telegramTraceLog')) {
					telegramTraceLog('openai_say.success', array(
						'status' => $httpStatus > 0 ? $httpStatus : 'unknown',
						'response_bytes' => strlen($response),
						'generated_bytes' => isset($responseData['choices'][0]['message']['content']) ? strlen((string)$responseData['choices'][0]['message']['content']) : 0,
					));
				}
				$generatedText = $responseData['choices'][0]['message']['content'];
				return $generatedText;
			} else {
				$trace = array(
						'status' => $httpStatus > 0 ? $httpStatus : 'unknown',
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
				if (function_exists('telegramTraceLog')) {
					telegramTraceLog('openai_say.response_without_message', array(
					'status' => $httpStatus > 0 ? $httpStatus : 'unknown',
						'json_error' => $jsonError,
						'response_preview' => function_exists('telegramTracePreview') ? telegramTracePreview($response) : substr($response, 0, 4000),
					));
				}
				// Rien à dire non plus
				return "";
			}
		} else {
			$trace = array(
				'status' => $httpStatus > 0 ? $httpStatus : 'unknown',
				'model' => MODEL,
				'request_bytes' => strlen($payload),
				'curl_errno' => $curlErrorNumber,
				'curl_error' => $curlError ?: 'unknown',
			);
			$traceJson = json_encode($trace, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			error_log('[openai-say] request_failed='.($traceJson !== false ? $traceJson : 'trace_encoding_failed'));
			if (function_exists('telegramTraceLog')) {
				telegramTraceLog('openai_say.request_failed', array(
					'status' => $httpStatus > 0 ? $httpStatus : 'unknown',
					'curl_errno' => $curlErrorNumber,
					'curl_error' => $curlError ?: 'unknown',
				));
			}
			// Rien à faire, le texte n'était pas solicité
			return "";
		}	
	}
?>
