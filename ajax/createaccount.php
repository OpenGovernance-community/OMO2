<?

	// Fonction spécifiue destinée à créer ou réinitialiser un compte utilisateur
	// Se doit d'être bien protégée, pour éviter le piratage et le spamming
	
	require_once("../config.php");
	require_once("../shared_functions.php");

	// Vérifie les données reçues
	if (!$_POST["id"]>0) {
		echo '{"status":false, "message":"Erreur de sauvegarde. Veuillez recharger la page.","script":"$(\'#username\').focus()"} ';
		exit;		
	}
	if (isset($_POST["username"]) && $_POST["username"]=="") {
		echo '{"status":false, "message":"Veuillez choisir un nom d\'utilisateur.","script":"$(\'#username\').focus()"} ';
		exit;		
	}
	// Contrôle que le username est unique
	
	// Contrôle que le mot de passe soit valide
	if (strlen($_POST["password"])<8) {
		echo '{"status":false, "message":"Le mot de passe doit faire au moins 8 caractères","script":"$(\'#password\').focus()"} ';
		exit;		
	}
	if ($_POST["password"]!=$_POST["password2"]) {
		echo '{"status":false, "message":"Les deux mots de passe ne correspondent pas.","script":"$(\'#password\').focus()"} ';
		exit;		
	}
	
	// Si tout est bon, crée le compte
	$user=new \dbObject\user();
	$user->load($_POST["id"]); // Chargement sur la base de l'id
	
	// Contrôle que le code envoyé correspond, sinon empêche l'exécution de la suite du script
	if ($user->get("code")!=$_POST["code"]) {
		echo '{"status":false, "message":"Accès interdit.","script":"$(\'#password\').focus()"} ';
		exit;		
	}
	
	// Met à jour les infos
	if (isset($_POST["username"])) $user->set("username",$_POST["username"]);
	if (isset($_POST["firstname"])) $user->set("firstname",$_POST["firstname"]);
	if (isset($_POST["lastname"])) $user->set("lastname",$_POST["lastname"]);
	$user->set("password",md5($_POST["password"]));
	$user->set("code",null);
	$user->set("codeexpiration", null);
	$user->save();
	if (isset($_POST["username"]))
		$msg="Votre compte a bien été créé. Vous pouvez vous connecter au site.";
	else
		$msg="Votre email a été mis à jour. Vous pouvez vous connecter au site avec vos nouvelles informations.";
	$formCode="document.location='/';";
	
	echo '{"status":true, "message":"'.$msg.'","script": "'.$formCode.'"} ';
	


?>
