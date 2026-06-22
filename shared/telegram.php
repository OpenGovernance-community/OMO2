<?php
	// Fonctions generiques pour Telegram

	function telegramNormalizeUtf8String($value) {
		$value = (string)$value;
		if ($value === '' || preg_match('//u', $value)) {
			return $value;
		}

		if (function_exists('mb_convert_encoding')) {
			$converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
			if (is_string($converted) && $converted !== '' && preg_match('//u', $converted)) {
				return $converted;
			}
		}

		$converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
		if (is_string($converted) && $converted !== '' && preg_match('//u', $converted)) {
			return $converted;
		}

		return $value;
	}

	function telegramNormalizePayload($value) {
		if (is_array($value)) {
			$normalized = array();
			foreach ($value as $key => $item) {
				$normalized[$key] = telegramNormalizePayload($item);
			}
			return $normalized;
		}

		if (is_string($value)) {
			return telegramNormalizeUtf8String($value);
		}

		return $value;
	}

	function telegramApiRequest($method, $params = array()) {
		$method = trim((string)$method);
		if ($method === '') {
			return null;
		}

		$params = array_filter($params, function ($value) {
			return !is_null($value);
		});
		$params = telegramNormalizePayload($params);

		$url = "https://api.telegram.org/bot".TOKEN."/".rawurlencode($method);
		$payload = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
		if (!is_string($payload)) {
			return null;
		}

		$result = false;

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=UTF-8',
				'Accept: application/json',
			));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			$result = curl_exec($ch);
			curl_close($ch);
		} else {
			$context = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => implode("\r\n", array(
						'Content-Type: application/json; charset=UTF-8',
						'Accept: application/json',
					)),
					'content' => $payload,
					'ignore_errors' => true,
				),
			));
			$result = @file_get_contents($url, false, $context);
		}

		if ($result === false) {
			return null;
		}

		$response = json_decode($result, true);
		return is_array($response) ? $response : null;
	}

	function telegramBuildInlineKeyboard($buttons) {
		if (is_null($buttons)) {
			return null;
		}

		return array(
			'inline_keyboard' => telegramNormalizePayload($buttons),
		);
	}

	function deleteMessage($chat_id, $message_id, $thread = null) {
		$response = telegramApiRequest('deleteMessage', array(
			'chat_id' => $chat_id,
			'message_id' => $message_id,
		));

		return is_array($response) ? $response : null;
	}

	function sendMessage($chat_id, $message, $buttons = null, $thread = null) {
		$response = telegramApiRequest('sendMessage', array(
			'chat_id' => $chat_id,
			'message_thread_id' => $thread,
			'text' => $message,
			'reply_markup' => telegramBuildInlineKeyboard($buttons),
		));

		if (is_array($response) && !empty($response['ok'])) {
			return isset($response['result']['message_id']) ? (int)$response['result']['message_id'] : null;
		}

		if (function_exists('saveLocalSession')) {
			$data = json_decode("{}");
			$data->erreur = $response;
			saveLocalSession($data, "error_log");
		}

		return null;
	}

	function editMessageText($chat_id, $message_id, $message, $buttons = null, $thread = null) {
		$response = telegramApiRequest('editMessageText', array(
			'chat_id' => $chat_id,
			'message_id' => $message_id,
			'text' => $message,
			'reply_markup' => telegramBuildInlineKeyboard($buttons),
		));

		return is_array($response) && !empty($response['ok']);
	}

	function answerCallbackQuery($callback_id, $text = null) {
		$response = telegramApiRequest('answerCallbackQuery', array(
			'callback_query_id' => $callback_id,
			'text' => $text,
		));

		return is_array($response) && !empty($response['ok']);
	}

	// Fonction pour recuperer les informations sur un fichier Telegram
	function getTelegramFile($file_id) {
		$response = telegramApiRequest('getFile', array(
			'file_id' => $file_id,
		));

		return is_array($response) ? $response : null;
	}
?>
