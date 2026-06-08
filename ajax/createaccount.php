<?

	// Fonction spécifiue destinée à créer ou réinitialiser un compte utilisateur
	// Se doit d'être bien protégée, pour éviter le piratage et le spamming
	
	require_once("../config.php");
	require_once("../shared_functions.php");
	require_once("../common/auth.php");

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
	
	// Charge d'abord le compte cible
	$user=new \dbObject\user();
	$user->load($_POST["id"]); // Chargement sur la base de l'id

	// Contrôle que le mot de passe soit valide
	$passwordCheck = commonEvaluatePasswordComplexity(
		(string)($_POST["password"] ?? ""),
		(string)$user->get("email")
	);
	if (empty($passwordCheck["valid"])) {
		echo '{"status":false, "message":"Le mot de passe doit faire au moins 12 caracteres et contenir une minuscule, une majuscule, un chiffre et un caractere special.","script":"$(\'#password\').focus()"} ';
		exit;		
	}
	if ($_POST["password"]!=$_POST["password2"]) {
		echo '{"status":false, "message":"Les deux mots de passe ne correspondent pas.","script":"$(\'#password\').focus()"} ';
		exit;		
	}
	
	// Contrôle que le code envoyé correspond, sinon empêche l'exécution de la suite du script
	if ($user->get("code")!=$_POST["code"]) {
		echo '{"status":false, "message":"Accès interdit.","script":"$(\'#password\').focus()"} ';
		exit;		
	}
	
	// Met à jour les infos
	if (isset($_POST["username"])) $user->set("username",$_POST["username"]);
	if (isset($_POST["firstname"])) $user->set("firstname",$_POST["firstname"]);
	if (isset($_POST["lastname"])) $user->set("lastname",$_POST["lastname"]);
	$user->set("password",commonHashUserPassword($_POST["password"]));
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
