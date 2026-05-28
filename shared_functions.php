<?
	require_once __DIR__ . '/shared/date_groups.php';
	require_once __DIR__ . '/common/environment_subdomains.php';

	function appGetReservedEnvironmentSubdomains() {
		return commonGetConfiguredEnvironmentSubdomains();
	}

	function appNormalizeCookieHost($host = null) {
		$host = is_string($host) && $host !== '' ? strtolower($host) : strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
		$host = trim((string)$host);
		return preg_replace('/:\d+$/', '', $host);
	}

	function appGetCookieScopeMode($host = null) {
		$mode = strtolower(trim((string)commonReadRuntimeEnvValue('COOKIE_SCOPE_MODE', 'auto')));
		if (in_array($mode, ['host', 'environment', 'parent'], true)) {
			return $mode;
		}

		if (appGetEnvironmentSubdomain($host) !== '') {
			return 'host';
		}

		return 'parent';
	}

	function appGetParentCookieDomain($host = null) {
		$host = appNormalizeCookieHost($host);

		if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
			return '';
		}

		if ($host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
			return '';
		}

		$parts = array_values(array_filter(explode('.', $host)));
		if (count($parts) < 2) {
			return '';
		}

		return '.' . implode('.', array_slice($parts, -2));
	}

	function appGetEnvironmentCookieDomain($host = null) {
		$host = appNormalizeCookieHost($host);

		if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
			return '';
		}

		if ($host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
			return '';
		}

		$parts = array_values(array_filter(explode('.', $host)));
		if (count($parts) < 3) {
			return '';
		}

		$environmentCandidate = strtolower((string)($parts[count($parts) - 3] ?? ''));
		if (!in_array($environmentCandidate, appGetReservedEnvironmentSubdomains(), true)) {
			return '';
		}

		return '.' . implode('.', array_slice($parts, -3));
	}

	function appGetCookieDomain($host = null) {
		$host = appNormalizeCookieHost($host);

		if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
			return '';
		}

		if ($host === 'localhost' || preg_match('/(^|\.)localhost$/', $host)) {
			return '';
		}

		$scopeMode = appGetCookieScopeMode($host);
		if ($scopeMode === 'host') {
			return '';
		}

		if ($scopeMode === 'environment') {
			$environmentDomain = appGetEnvironmentCookieDomain($host);
			if ($environmentDomain !== '') {
				return $environmentDomain;
			}
		}

		return appGetParentCookieDomain($host);
	}

	function appShouldUseSecureCookies() {
		$https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
		if ($https !== '' && $https !== 'off') {
			return true;
		}

		if ((string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
			return true;
		}

		return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
	}

	function appGetCurrentSiteBaseUrl() {
		$host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
		if ($host === '') {
			return '';
		}

		$scheme = appShouldUseSecureCookies() ? 'https' : 'http';
		return $scheme . '://' . $host;
	}

	function appGetEnvironmentSubdomain($host = null) {
		$host = is_string($host) && $host !== '' ? strtolower($host) : strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
		$host = trim((string)$host);
		$host = preg_replace('/:\d+$/', '', $host);

		if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
			return '';
		}

		$parts = array_values(array_filter(explode('.', $host)));
		if (count($parts) < 3) {
			return '';
		}

		$environmentCandidate = strtolower((string)($parts[count($parts) - 3] ?? ''));
		if (in_array($environmentCandidate, appGetReservedEnvironmentSubdomains(), true)) {
			return $environmentCandidate;
		}

		return '';
	}

	function appShouldExposeDevDiagnostics() {
		if (function_exists('envBool') && envBool('APP_DEBUG', false)) {
			return true;
		}

		return appGetEnvironmentSubdomain() === 'dev';
	}

	function appSetLastMailError($message) {
		$GLOBALS['lastMailError'] = trim((string)$message);
	}

	function appGetLastMailError() {
		return trim((string)($GLOBALS['lastMailError'] ?? ''));
	}

	function appBuildAbsoluteUrl($path = '') {
		$path = (string)$path;
		$baseUrl = appGetCurrentSiteBaseUrl();
		if ($baseUrl === '') {
			return $path;
		}

		if ($path === '') {
			return $baseUrl;
		}

		if (preg_match('#^https?://#i', $path)) {
			return $path;
		}

		return $baseUrl . (substr($path, 0, 1) === '/' ? $path : '/' . $path);
	}

	function appBuildCookieOptions($expires = 0, $httpOnly = true, $host = null) {
		$options = [
			'expires' => (int)$expires,
			'path' => '/',
			'secure' => appShouldUseSecureCookies(),
			'httponly' => (bool)$httpOnly,
			'samesite' => 'Lax',
		];

		$domain = appGetCookieDomain($host);
		if ($domain !== '') {
			$options['domain'] = $domain;
		}

		return $options;
	}

	function appBuildCookieOptionsForDomain($expires = 0, $httpOnly = true, $domain = '') {
		$options = [
			'expires' => (int)$expires,
			'path' => '/',
			'secure' => appShouldUseSecureCookies(),
			'httponly' => (bool)$httpOnly,
			'samesite' => 'Lax',
		];

		$domain = trim((string)$domain);
		if ($domain !== '') {
			$options['domain'] = $domain;
		}

		return $options;
	}

	function appBuildSessionCookieOptions($host = null) {
		$options = appBuildCookieOptions(0, true, $host);
		unset($options['expires']);
		$options['lifetime'] = 0;
		return $options;
	}

	function appGetCookieDomainCandidates($host = null) {
		$host = appNormalizeCookieHost($host);
		$candidates = [''];

		foreach ([appGetCookieDomain($host), appGetEnvironmentCookieDomain($host), appGetParentCookieDomain($host)] as $domain) {
			$domain = trim((string)$domain);
			if ($domain !== '' && !in_array($domain, $candidates, true)) {
				$candidates[] = $domain;
			}
		}

		return $candidates;
	}

	function appShouldScopeSensitiveCookieNames($host = null) {
		return appGetEnvironmentSubdomain($host) !== '' || appGetCookieScopeMode($host) === 'host';
	}

	function appGetCookieScopeKey($host = null) {
		$domain = ltrim((string)appGetCookieDomain($host), '.');
		if ($domain !== '') {
			return $domain;
		}

		return appNormalizeCookieHost($host);
	}

	function appBuildScopedCookieName($baseName, $host = null) {
		$baseName = trim((string)$baseName);
		if ($baseName === '' || !appShouldScopeSensitiveCookieNames($host)) {
			return $baseName;
		}

		$suffix = preg_replace('/[^a-z0-9]+/i', '_', strtolower((string)appGetCookieScopeKey($host)));
		$suffix = trim((string)$suffix, '_');
		if ($suffix === '') {
			return $baseName;
		}

		return $baseName . '_' . $suffix;
	}

	function appGetCurrentUserCookieName($host = null) {
		return appBuildScopedCookieName('currentUser', $host);
	}

	function appGetCurrentCodeCookieName($host = null) {
		return appBuildScopedCookieName('currentCode', $host);
	}

	function appGetSessionCookieName($host = null) {
		return appBuildScopedCookieName('PHPSESSID', $host);
	}

	function appExpireCookieAcrossDomains($name, $httpOnly = true, $host = null) {
		$name = trim((string)$name);
		if ($name === '') {
			return false;
		}

		$expired = false;
		foreach (appGetCookieDomainCandidates($host) as $domain) {
			$expired = setcookie($name, '', appBuildCookieOptionsForDomain(time() - 3600, $httpOnly, $domain)) || $expired;
		}

		return $expired;
	}

	function appSetCookie($name, $value, $expires = 0, $httpOnly = true, $host = null) {
		return setcookie($name, $value, appBuildCookieOptions($expires, $httpOnly, $host));
	}

	function appExpireCookie($name, $httpOnly = true, $host = null) {
		return appSetCookie($name, '', time() - 3600, $httpOnly, $host);
	}

	require_once("config.php");
	if (session_status() === PHP_SESSION_NONE) {
		session_name(appGetSessionCookieName());
		session_set_cookie_params(appBuildSessionCookieOptions());
		session_start();
	}

	require __DIR__ . '/vendor/autoload.php';
	// Pour l'envoi de mails
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;
	
   // Chargement à la demande des classes non instanciées
	spl_autoload_register(function ($class) {
	    include dirname(__FILE__)."/".'class/' . str_replace("\\","/",strtolower($class)) . '.class.php';
	});
	
	// Initialise le login pour chaque page
	checkLogin();
	
	function writeHeadContent($title,$logiciel="EasyPV") {
		echo '<title>'.$logiciel.' - '.$title.'</title>';
		echo '<link rel="icon" type="image/png" href="/img/favicon-'.$logiciel.'.png" />';
		echo '<meta charset="utf-8">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" /> ';
		
		//<!-- JQuery et jquery UI -->
		echo '<script src="https://code.jquery.com/jquery-3.6.0.js"></script>';
		echo '<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>';
		echo '<script>';
			
		// Change JQueryUI plugin names to fix name collision with Bootstrap.
		echo '$.widget.bridge("uitooltip", $.ui.tooltip);';
		echo '$.widget.bridge("uibutton", $.ui.button);';
		echo '</script>';
		
		//<!-- Bootstrap (for html editor) Summernote-->
		echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">';
		echo '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>';

		//echo '<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">';
		//echo '<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.min.js"></script>';

		//<!-- include summernote css/js -->
		echo '<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">';
		echo '<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>';
	
		//<!-- Fonctions partagées entre plusieurs pages -->
		echo '<script src="/shared_functions.js"></script>';
		echo '<link href="/shared_css.css" rel="stylesheet">';
		
		//<!-- Script Paypal -->
		if (!empty($GLOBALS["paypalClientId"])) {
			echo '<script src="https://www.paypal.com/sdk/js?client-id='.rawurlencode($GLOBALS["paypalClientId"]).'&vault=true&intent=subscription" data-sdk-integration-source="button-factory" data-namespace="paypal_sdk"></script>';
			echo '<script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js" charset="UTF-8"></script>';
		}
	}
	
	// Fonction de vérification de login, permettant d'une part d'initialiser 
	// le login à partir d'un cookie, et d'autre part de vérifier si nécessaire la bonne connexion
	function checkLogin() {
		require_once __DIR__ . '/common/auth.php';
		commonRestoreRememberedUser();

		if (isset($_SESSION["currentUser"])) {
			$_SESSION["userRef"]=new \dbObject\User();
			$_SESSION["userRef"]->load($_SESSION["currentUser"]);
			return true;
		}
		// Pas loggé, est-ce que les cookie permettent de retrouver l'utilisateur?
		$currentUserCookieName = appGetCurrentUserCookieName();
		$currentCodeCookieName = appGetCurrentCodeCookieName();
		$currentUserCookieValue = $_COOKIE[$currentUserCookieName] ?? ($_COOKIE["currentUser"] ?? null);
		$currentCodeCookieValue = $_COOKIE[$currentCodeCookieName] ?? ($_COOKIE["currentCode"] ?? null);
		if ($currentUserCookieValue !== null && $currentCodeCookieValue !== null) {
			// Charge l'utilisateur corrspondant
			$user=new \dbObject\User();
			$user->load([["id",$currentUserCookieValue],["password",$currentCodeCookieValue]]);
			if ($user->get("id")>0) {
				// Redéfini les cookie pour 30 jours supplémentaires
				appSetCookie($currentUserCookieName, (string)$user->get("id"), time()+60*60*24*30, false);
				appSetCookie($currentCodeCookieName, (string)$user->get("password"), time()+60*60*24*30, false);
				appExpireCookieAcrossDomains('currentUser', false);
				appExpireCookieAcrossDomains('currentCode', false);
				
				// Initialise la variable de session
				$_SESSION["currentUser"]=$user->get("id");
				commonUpdateLastConnection((int)$user->get("id"));
				$_SESSION["userRef"]=$user;
				
				// Confirme que l'utilisateur a bien été trouvé
				return true;
			} else {
				// Pas trouvé de correspondance
				appExpireCookieAcrossDomains($currentUserCookieName, false);
				appExpireCookieAcrossDomains($currentCodeCookieName, false);
				appExpireCookieAcrossDomains('currentUser', false);
				appExpireCookieAcrossDomains('currentCode', false);
				return false;
			}
		}
	}
	
	
	// Fonction E-mail passant par un serveur, pour minimier les effets SPAM
	function myHTMLMail($from,$to,$subject,$body,$cc=null, $bcc=null) {


		appSetLastMailError('');
		$mail = new PHPMailer(true);
		$debugLines = [];

		// Configuration du serveur SMTP
		$mail->isSMTP();
		$mail->Host = $GLOBALS["mailHost"];
		$mail->Port = $GLOBALS["mailPort"];
		$mailSecure = strtolower(trim((string)($GLOBALS["mailSecure"] ?? '')));
		if ($mailSecure === 'tls') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		} elseif ($mailSecure === 'ssl') {
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		} else {
			$mail->SMTPSecure = $mailSecure;
		}
		$mail->SMTPAuth = $GLOBALS["mailAuth"];
		$mail->Timeout = max(3, (int)($GLOBALS["mailTimeout"] ?? 10));
		$mail->SMTPKeepAlive = false;
		
		$mail->CharSet = $GLOBALS["mailCharset"];
		if (appShouldExposeDevDiagnostics()) {
			$mail->SMTPDebug = SMTP::DEBUG_SERVER;
			$mail->Debugoutput = function ($message, $level) use (&$debugLines) {
				$debugLines[] = 'SMTP[' . (int)$level . '] ' . trim((string)$message);
			};
		}

		// Informations d'identification pour accéder au compte mail
		$mail->Username = $GLOBALS["mailUser"];
		$mail->Password = $GLOBALS["mailPassword"];

		// Configuration de l'expéditeur et du destinataire
		if (is_array($from))
			$mail->setFrom($from[0],$from[1]);
		else
			$mail->setFrom($from);
		if (is_array($to))
			foreach ($to as $dest) {
				$mail->addAddress($dest); // Destinataire
			}
		else
			$mail->addAddress($to); // Destinataire
		

		// Sujet et corps du message
		$mail->Subject = $subject;
		$mail->Body = $body;
		if (strip_tags($body)!=$body)
			$mail->IsHTML(true);  
		
		// Envoi de l'e-mail
		try {
			$result = $mail->send();
			if ($result) {
				return true;
			}
		} catch (\Throwable $exception) {
			$mailError = trim((string)$mail->ErrorInfo);
			$debugOutput = trim(implode("\n", $debugLines));
			$errorMessage = trim(($mailError !== '' ? $mailError : $exception->getMessage()) . ($debugOutput !== '' ? "\n" . $debugOutput : ''));
			appSetLastMailError($errorMessage);
			error_log('Mail send failed: ' . $errorMessage);
			return false;
		}

		$mailError = trim((string)$mail->ErrorInfo);
		$debugOutput = trim(implode("\n", $debugLines));
		$errorMessage = trim($mailError . ($debugOutput !== '' ? "\n" . $debugOutput : ''));
		appSetLastMailError($errorMessage);
		if ($errorMessage !== '') {
			error_log('Mail send failed: ' . $errorMessage);
		}
		return false;
	}
	
	// Fonction de traduction raccourcie pour texte courant dans les pages
	function T_ ($text, $isstring=false) {
		if ($isstring)
			return str_replace(array("'","\n"),array("&apos;","\\n"),translate($text));
		else
			return translate($text);
	}
	
	// Fonction de traduction complète, utilisant l'IA pour traduire les éléments qui n'ont pas été traduits manuellement
	function translate ($text, $language=null, $user=null) {
		return $text;
		// En attendant de stabiliser la fonction

		// Si aucune langue spécifiée, utilise celle du user
		if (is_null($language)) {
				if (isset($_COOKIE["lang"]))
					$language=$_COOKIE["lang"];
				else
					return $text; // Aucun language défini
		}
		
		// Si c'est du français, retourne directement le texte (ou pas... si ça corrige l'orthographe...)
		$language=strtoupper($language);
		if (preg_match('/^[A-Z]{2}$/', $language) === 1 && $language!="FR") {
		// Crée un ID unique pour le texte, 
		$id=md5($text);
		// Si déjà sauvé dans une variable de session, utilise cette valeur
		if (isset($_SESSION[$language."-".$id])) {
			return $_SESSION[$language."-".$id];
		}
		// Cherche dans la base de données si ce texte a déjà été traduit dans cette langue
		$translation = null;
		$translation->load(["uid",$language."-".$id]);
		if ($translation->get("id")>0) {
			// Trouvé, retourne la valeur
			// Défini la date de dernier accès, permettant de faire de l'ordre dans les éléments qui ne sont plus accédés depuis longtemps.
			$translation->set("date",new \DateTime());
			// Ajoute un compteur, pour rendre compte de ce qui est souvant utilisé, histoire d'en optimiser le chargement si nécessaire
			$translation->set("cpt",$translation->get("cpt")+1);
			$translation->save();
			$_SESSION[$language."-".$id]=$translation->get("value");
			return $translation->get("value");		
		} else {
			
			// Prépare le contexte de traduction
			$context=Array();
			$context[]=array('role' => 'system', 'content' => 'You are a professional translator, specialized in translating human-machine interface. You offer to translate software interface elements for shared governance, selecting the most appropriate terms based on the cultural context of a language, while remaining faithful to the original text and keeping the HTML formating. You NEVER add introductions like "Sure, here is the translation you ask" or something like that. Only the traduction, without anything else.');
			$context[]=	['role' => 'user', 'content' => "Can you translate my text from French to ".$language."?:\n".$text];
			$context[]=	['role' => 'user', 'content' => 'Of course, I would be happy to help you. Can you provide me with the text you would like me to translate from French to '.$language.'?'];
			$context[]=	['role' => 'user', 'content' => "Here it is. Thank you for not adding ANY embellishments. Limit to the translation, because it will be displayed on screen directly. Here is the text:\n".$text];
		
			
			// Demande à l'IA une traduction du texte
			$openAiApiKey = trim((string)($GLOBALS["OpenAI"] ?? ""));
			if ($openAiApiKey === "") {
				return $text;
			}
			$open_ai = null;
			$translationModel = (!empty($GLOBALS["openAiTranslationModel"]) ? $GLOBALS["openAiTranslationModel"] : MODEL);
			try {
			$result = $open_ai->chat([
				'model' => $translationModel,
				'messages' => $context,
				'temperature' => 0.2,
			   'max_tokens' => 2000,
			]);
			
			$ret = json_decode($result, true);
			if (isset($ret['error'])) {
				return $text;
			}
			if (! isset($ret['choices'][0]['message']['content'])) {
				return $text;
			}
			
			// Si la traduction a l'air correct (à peu près le même nombre de caractères)
			if (strlen($ret['choices'][0]['message']['content'])<strlen($text)*2 && strlen($ret['choices'][0]['message']['content'])>strlen($text)/2) {
				// Enregistre les infos dans l'objet
				$translation->set("value",$ret['choices'][0]['message']['content']);
				$translation->set("original",$text);
				$translation->set("uid",$language."-".$id);			
				$translation->save();
				$_SESSION[$language."-".$id]=$ret['choices'][0]['message']['content'];
			}
			return $ret['choices'][0]['message']['content'];
			} catch (\Throwable $exception) {
				return $text;
			}
		}
		} else {
			return $text;
		}
	}
?>
