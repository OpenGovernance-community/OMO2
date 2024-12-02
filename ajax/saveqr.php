<?
	
	// Fonction générique pour sauver un profil utilisateur

	require_once("../config.php");
	require_once("../shared_functions.php");

	$object=new \dbObject\qr();
	$data=$_POST;
	
	// S'il y a un ID de posté, charge l'élément
	if (isset($data["id"]) && $data["id"]>0) {
		$object->load($data["id"]);
		if (!$object->getId()>0) {
			echo '{"status":false, "message":"'.'Object not found'.'"} ';
			exit;
		}
		if (!$object->canEdit()) {
			echo "{'status':false, 'message':'"."Vous n\\'avez pas le droit d\\'éditer les informations de cet objet"."'} ";
			exit;
		}
		
	} else {
		// Crée un nouvel objet
		$object->set("IDuser",$_SESSION["currentUser"]);
	}

	// Met à jour les infos selon les données postées
	$object->loadFromArray($data);
	
	// Enregiste l'élément
	$object->save();
	$msg='Enregistrement réussi';
	$formCode="refresh('#popup_content','/popup/parameters.php')"; // Raffraichi la liste des QR
	echo '{"status":true, "message":"'.$msg.'","script": "'.$formCode.'"} ';
	


?>
