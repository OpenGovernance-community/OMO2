<?php
	// Fonctions génériques pour Telegram

	function telegramApiRequest($method, $params = array()) {
		$method = trim((string)$method);
		if ($method === '') {
			return null;
		}

		$params = array_filter($params, function ($value) {
			return !is_null($value);
		});

		$query = http_build_query($params);
		$url = "https://api.telegram.org/bot".TOKEN."/".$method.($query !== '' ? "?".$query : "");
		$result = @file_get_contents($url);

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

		return json_encode(array(
			'inline_keyboard' => $buttons,
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

	// Fonction pour envoyer une requête à l'API de Telegram pour récupérer les informations sur le fichier
	function getTelegramFile($file_id) {
		$response = telegramApiRequest('getFile', array(
			'file_id' => $file_id,
		));

		return is_array($response) ? $response : null;
	}
?>
