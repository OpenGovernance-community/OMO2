<?
	
	// Fonction générique pour sauver un profil utilisateur

	require_once("../config.php");
	require_once("../shared_functions.php");

	$object=new \dbObject\user();
	$data=$_POST;
	
	// S'il y a un ID de posté, charge l'élément
	if (isset($data["id"]) && $data["id"]>0) {
		$object->load($data["id"]);
		if (!$object->getId()>0) {
			echo '{"status":false, "message":"'.'Utilisateur inconnu'.'"} ';
			exit;
		}
		if (!$object->canEdit()) {
			echo "{'status':false, 'message':'"."Vous n\\'avez pas le droit d\\'éditer les informations de cet utilisateur"."'} ";
			exit;
		}
		
	} else {
		echo '{"status":false, "message":"'.'Erreur, impossible de créer un nouveau compte ici'.'"} ';
		exit;
	}

	// Met à jour les infos selon les données postées
	$object->loadFromArray($data);
	
	// Enregiste l'élément
	$object->save();
	$msg='Enregistrement réussi';
	$formCode="";
	if ($_GET["origin"]=="profil")
		$formCode="refresh('#popup_content','/popup/profil.php')"; // Raffraichi l'onglet login
	if ($_GET["origin"]=="params")
		$formCode="refresh('#popup_content','/popup/parameters.php')"; // Raffraichi l'onglet paramètres
	echo '{"status":true, "message":"'.$msg.'","script": "'.$formCode.'"} ';
	


?>
