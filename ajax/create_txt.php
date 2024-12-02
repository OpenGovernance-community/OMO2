<?php
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared/openai.php");


// Contrôle le format des données
if (isset($_POST['IDdocument']) && isset($_POST['IDaiprompt'])) {
    // Traitement de vos données ici...
    $doc= new \dbObject\Document();
    $doc->load($_POST['IDdocument']);
    $ai=new \dbObject\AIPrompt();
    $ai->load($_POST['IDaiprompt']);
    if ($doc->get("id")>0 && $ai->get("id")>0) {
		
		if ($doc->get("IDuser")==$_SESSION["currentUser"]) {
			// Génère le text via l'IA
			
			$readable=say("Peux-tu générer un JSON pour le texte suivant, comprenant une seule entrée: une entrée 'text' avec une retranscription correspondant à la description suivante: ".$ai->get("prompt")."\n\n Voici le texte : \n".$doc->get("content"));
			// Traite le JSON retourné avant de l'enregistrer
			$pattern = "/\{(.+?)\}/s";
			if (preg_match($pattern, $readable, $matches)) {
				// $matches[0] contient la correspondance complète, $matches[1] contient le JSON
				$readable = "{".$matches[1]."}";			

			} else {
			echo json_encode(array('error' => 'true', 'errorMsg' => T_('Désolé, problème de conversion du JSON...')));
				exit;
			}
			$readable=json_decode($readable);
			
			// Crée le nouveau texte
			$txt=new \dbObject\AltText();
			$txt->set("IDdocument",$_POST['IDdocument']);
			$txt->set("IDaiprompt",$_POST['IDaiprompt']);
			$txt->set("text",$readable->text);
			$txt->save();
		
			
			
			
			// Si le texte a bien été généré, envoie un message de confirmation et rafraichi la page			
			$msg="Le texte a bien été généré.";
			$formCode="document.location.reload();";
			echo '{"status":true, "message":"'.$msg.'","script": "'.$formCode.'"}';
		} else {
			echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé')));
		}
	} else echo json_encode(array('error' => 'true', 'errorMsg' => T_('Fichier non trouvé ou paramètres invalides')));

}  else echo json_encode(array('error' => 'true', 'errorMsg' => T_('Erreurs de paramètres')));
?>
