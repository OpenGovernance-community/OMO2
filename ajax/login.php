<?
	/* Gestion des comptes
	 * Ce fichier prends les différentes requêtes liées à la gestion du login en permettant:
	 * - De créer des comptes, à partir d'un simple e-mail
	 * - De se connecter, à partir d'une combinaison e-mail/password
	 */ 

	require_once("../config.php");
	require_once("../shared_functions.php");

	// Si c'est une déconnexion
	if (!isset($_POST["user"]) && !isset($_POST["email"])) {
		unset($_SESSION["currentUser"]);
		unset($_COOKIE['currentUser']); 
		unset($_COOKIE['currentCode']); 
		setcookie('currentUser', '', time()-1, '/', $_SERVER['HTTP_HOST'], false);
		setcookie('currentCode', '', time()-1, '/', $_SERVER['HTTP_HOST'], false);
		echo '{"status":true, "script":"location.reload()"} ';
		exit;
	} else
	// Si c'est un login classique
	if (isset($_POST["email"]) && isset($_POST["password"])) {
		// Contrôle la validité du mail
		if ($_POST["email"]=="" || !filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
			echo '{"status":false, "message":"Veuillez utiliser votre email pour la connexion.","script":"$(\'#user\').focus()"} ';
			exit;
		}
		// Contrôle la validité du mot de passe
		if ($_POST["password"]=="") {
			echo '{"status":false, "message":"Veuillez renseigner votre mot de passe.","script":"$(\'#password\').focus()"} ';
			exit;
		}

		// S'assure que l'adresse e-mail n'existe pas déjà dans la table, ou que les données n'ont pas été complétées
		$user=new \dbObject\user();
		$user->load([["email",$_POST["email"]],["password",md5($_POST["password"])]]); // Chargement sur la base de l'email
		
		// Pas trouvé
		if (!$user->get("id")>0) {
			echo '{"status":false, "message":"Utilisateur inconnu","script":"$(\'#password\').focus()"} ';
			exit;
		}
		
		// Stock l'info dans la session
		$_SESSION["currentUser"]=$user->get("id");
		
		// Si demande de se souvenir, stock l'info dans un cookie pendant 30 jours
		if (isset($_POST["remember"]) && $_POST["remember"]=="1") {
			setcookie('currentUser', $user->get("id"), time()+60*60*24*30, '/', $_SERVER['HTTP_HOST'], false);
			setcookie('currentCode', $user->get("password"), time()+60*60*24*30, '/', $_SERVER['HTTP_HOST'], false);
		} else {
			// Sinon, enregistre malgré tout des cookies, mais sur la durée d'ouverture du navigateur afin d'éviter
			// la déconnexion pour fin de session.
			setcookie('currentUser', $user->get("id"));
			setcookie('currentCode', $user->get("password"));
		}
		echo '{"status":true, "script":"location.reload()"} ';
		
		exit;
	} else
	// Si c'est la création d'un compte, envoie le message avec les instructions
	if (isset($_POST["email"]) && !isset($_POST["password"])) {

		// Contrôle la validité du mail
		if ($_POST["email"]=="") {
			echo '{"status":false, "message":"Veuillez compléter l\'adresse e-mail.","script":"$(\'#email\').focus()"} ';
			exit;
		}
		if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
			echo '{"status":false, "message":"Format d\'email invalide.","script":"$(\'#email\').focus()"} ';
			exit;

		}
		
		// S'assure que l'adresse e-mail n'existe pas déjà dans la table, ou que les données n'ont pas été complétées
		$user=new \dbObject\user();
		$user->load(["email",$_POST["email"]]); // Chargement sur la base de l'email

		if ($user->get("password")!=null && $user->get("password")!="") {
			
			// Est-ce posté depuis le formaulaire de création de compte? Si oui, signale une erreur
			if (isset($_POST["check"])) {
				echo '{"status":false, "message":"Ce compte existe déjà. Veuillez plutôt utiliser le formulaire de réinitialisation du mot de passe.","script":"$(\'#email\').focus()"} ';
				exit;
				
			} 
			
			// Le mot de passe existe déjà, compte existant dont il faut rappeler le mot de passe
			$bytes = random_bytes(10);
			$user->set("code",bin2hex($bytes));
			// Défini la durée de validité de ce code
			$user->set("codeexpiration",(new \DateTime())->add(new \DateInterval("PT1H")));
			
			$msg="Si ce compte existe, la procédure de réinitialisation vous a été envoyée.\\n\\nVeuillez vérifier votre boîte e-mail et suivre les instructions envoyés pour modifier votre mot de passe.";
			$title="Réinitialisation du mot de passe ".$GLOBALS["siteTitle"];
			$txt="Bienvenue!";
			$txt.="\n"."Cliquez ici pour redéfinir votre mot de passe : <a href='https://pv.systemdd.ch/confirm.php?code=".$user->get("code")."'>Accéder à mon compte</a>.";
			$formCode="$(this).html('<b>Veuillez vérifier votre boîte e-mail et suivre les instructions du mail envoyé pour modifier votre mot de passe.</b>');";

			
		} else {
		// Création uniquement avec la patern anti-spam
		if ($user->get("id")<=0 &&!isset($_POST["result"])) {
			echo '{"status":false, "message":"Ce compte n\'existe pas."} ';
			exit;
			
		}
		// Contrôle la patern anti-spam
		if (md5($_POST["result"])!=$_POST["check"]) {
			echo '{"status":false, "message":"Veuillez résoudre le calcul de façon correcte.","script":"$(\'#result\').focus()"} ';
			exit;
			
		}
		
		// Crée le compte
		$user->set("email",$_POST["email"]);
		// Défini le code d'accès unique
		$bytes = random_bytes(10);
		$user->set("code",bin2hex($bytes));
		// Défini la durée de validité de ce code
		$user->set("codeexpiration",(new \DateTime())->add(new \DateInterval("PT1H")));
		
		// Si l'adresse existe, envoie un message pour redéfinir le mot de passe
		if ($user->get("id")>0) {
			// Message d'alert affiché
			$msg="Un compte avec cette adresse e-mail existe déjà.\\n\\nVeuillez vérifier votre boîte e-mail et suivre les instructions envoyés pour le réactiver ou modifier le mot de passe.";
			// Texte de remplacement du formaulaire
			$formCode="$(this).html('<b>Veuillez vérifier votre boîte e-mail et suivre les instructions du mail envoyé pour réactiver votre compte ou modifier votre mot de passe.</b>');";
			// Création du message
			$title="Création d'un compte sur ".$GLOBALS["siteTitle"];
			$txt="Bienvenue!";
			$txt.="\n"."Cliquez ici pour réactiver votre compte ou redéfinir votre mot de passe : <a href='https://pv.systemdd.ch/confirm.php?code=".$user->get("code")."'>Accéder à mon compte</a>.";
			
		} else {
		// Si elle n'existe pas
			
			// Message d'alert affiché
			$msg="Votre compte a bien été créé.\\n\\nVeuillez vérifier votre boîte e-mail et suivre les instructions du mail envoyé pour finaliser votre inscription et compléter votre profil.";
			// Texte de remplacement du formaulaire
			$formCode="$(this).html('<b>Votre compte a bien été créé.<br><br>Veuillez vérifier votre boîte e-mail et suivre les instructions du mail envoyé pour finaliser votre inscription et compléter votre profil.</b>');";
			// Création du message
			$title="Création d'un compte sur ".$GLOBALS["siteTitle"];
			$txt="Bienvenue!";
			$txt.="\n"."Cliquez ici pour finaliser la configuration de votre compte : <a href='https://pv.systemdd.ch/confirm.php?code=".$user->get("code")."'>Finaliser mon inscription</a>.";
		}	
		}
			// Envoie le message
			if (myHTMLMail("info@systemdd.ch",$_POST["email"],$title,$txt)) {
				// Confirme que tout s'est bien passé
				$user->save();
				echo '{"status":true, "message":"'.$msg.'","script": "'.$formCode.'"} ';
			} else {	
				// Problème d'envoi de mail
				echo '{"status":false, "message":"Problème d\'envoi de mail."} ';
			}
			// Sauve les infos
			
		exit;
		
	}
	echo '{"status":false, "message":"Commande inconnue"} ';
?>
