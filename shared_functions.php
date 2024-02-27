<?
	require_once("config.php");
	session_start();

	require $_SERVER['DOCUMENT_ROOT'].'/../../vendor/autoload.php';
	// Pour la traduction automatique
	use Orhanerday\OpenAi\OpenAi;
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
	
	function writeHeadContent($title) {
		echo '<title>EasyPV - '.$title.'</title>';
		echo '<meta charset="utf-8">';
		echo '<meta name="viewport" content="width=device-width, initial-scale=1" /> ';
		
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
		echo '<script src="https://www.paypal.com/sdk/js?client-id=AYZnt2y7GXObIwaEE4lE00M5aqQbPnZo2ghT8323MbwnHI9dxGtLLVAQ4LLNVZnPbr9usFpnpra-lvSL&vault=true&intent=subscription" data-sdk-integration-source="button-factory" data-namespace="paypal_sdk"></script>';
		echo '<script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js" charset="UTF-8"></script>';
	}
	
	// Fonction de vérification de login, permettant d'une part d'initialiser 
	// le login à partir d'un cookie, et d'autre part de vérifier si nécessaire la bonne connexion
	function checkLogin() {
		if (isset($_SESSION["currentUser"])) return true;
		// Pas loggé, est-ce que les cookie permettent de retrouver l'utilisateur?
		if (isset($_COOKIE["currentUser"]) && isset($_COOKIE["currentCode"])) {
			// Charge l'utilisateur corrspondant
			$user=new \dbObject\User();
			$user->load([["id",$_COOKIE["currentUser"]],["password",$_COOKIE["currentCode"]]]);
			if ($user->get("id")>0) {
				// Redéfini les cookie pour 30 jours supplémentaires
				setcookie('currentUser', $user->get("id"), time()+60*60*24*30, '/', $_SERVER['HTTP_HOST'], false);
				setcookie('currentCode', $user->get("password"), time()+60*60*24*30, '/', $_SERVER['HTTP_HOST'], false);
				
				// Initialise la variable de session
				$_SESSION["currentUser"]=$user->get("id");
				
				// Confirme que l'utilisateur a bien été trouvé
				return true;
			} else {
				// Pas trouvé de correspondance
				return false;
			}
		}
	}
	
	
	// Fonction E-mail passant par un serveur, pour minimier les effets SPAM
	function myHTMLMail($from,$to,$subject,$body,$cc=null, $bcc=null) {


		$mail = new PHPMailer();

		// Configuration du serveur SMTP
		$mail->isSMTP();
		$mail->Host = $GLOBALS["mailHost"];
		$mail->Port = $GLOBALS["mailPort"];
		$mail->SMTPSecure = $GLOBALS["mailSecure"];
		$mail->SMTPAuth = $GLOBALS["mailAuth"];
		
		$mail->CharSet = $GLOBALS["mailCharset"];

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
		return $mail->send();
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
		// En attendant de stabiliser la fonction

		// Si aucune langue spécifiée, utilise celle de la license
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
		$translation = new \dbObject\translation();
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
			$open_ai = new OpenAi($GLOBALS["OpenAI"]);
			$result = $open_ai->chat([
				'model' => 'gpt-3.5-turbo',
				'messages' => $context,
				'temperature' => 0.2,
			   'max_tokens' => 2000,
			]);
			
			$ret = json_decode($result, true);
			if (isset($ret['error'])) {
				throw new \Exception($ret['error']['message']);
			}
			if (! isset($ret['choices'][0]['message']['content'])) {
				throw new \Exception("Unknown error: " . $result);
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
		}
		} else {
			return $text;
		}
	}
?>
