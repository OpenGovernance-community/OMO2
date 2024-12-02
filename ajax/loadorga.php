<?php
	require_once("../config.php");
	require_once("../shared_functions.php");


function createJSONstr($node) {
	$str='{"name": "'.$node->get("name").'", "ID" : "'.$node->get("id").'", "IDdb" : "'.$node->get("id").'", "type": "'.$node->get("IDtypeholon").'"';

	// Ajoute les datas (à implémenter)
	
	
	// Ajoute les enfants
	if ($node->get("IDtypeholon")>1) {
		$children=$node->getChildren();
		$str.=', "children": [';
		foreach($children as $child) {
			$str.=createJSONstr($child).", ";
		}
		$str=rtrim($str, ', '); // Supprime la dernère virgule, et ne fera rien s'il n'y avait pas d'enfants
		$str.=']';
	} else $str.=', "size": "10"';
	
	$str.='}';
	return $str;
}

// Contrôle le format des données
if (isset($_GET['id'])) {
    // Traitement de vos données ici...
    $orga= new \dbObject\Holon();
    if ($_GET['id']>0) {
		$orga->load($_GET['id']);
		
		if ((isset($_SESSION["currentUser"]) && $orga->get("IDuser")==$_SESSION["currentUser"]) || (isset($_GET["accesskey"]) && $_GET["accesskey"]!="" && $_GET["accesskey"]==$orga->get("accesskey"))) {
			$jsonStr=createJSONstr($orga);
			echo $jsonStr;
			  //echo '{"name": "Mon organisation 30 nov", "ID" : "36", "IDdb" : "36", "type": "4", "children": [{"name": "Ancrage", "ID" : "37", "IDdb" : "37", "type": "2", "children": [{"name": "Facilitation", "ID" : "38", "IDdb" : "38", "type": "1"}, {"name": "fdsfdf", "ID" : "43", "IDdb" : "43", "type": "1"}, {"name": "fsdfds", "ID" : "42", "IDdb" : "42", "type": "1"}, {"name": "Mémoire", "ID" : "40", "IDdb" : "40", "type": "1"}, {"name": "Pilotage", "ID" : "39", "IDdb" : "39", "type": "1"}, {"name": "Role opérationnel", "ID" : "41", "IDdb" : "41", "type": "1"}]}, {"name": "CA", "ID" : "44", "IDdb" : "44", "type": "2", "children": [{"name": "Président", "ID" : "46", "IDdb" : "46", "type": "1"}, {"name": "Trésorier", "ID" : "45", "IDdb" : "45", "type": "1"}]}]}';
			//echo '{"name": "Mon organisation chargée", "ID": "TMP_1", "type": "organization", "children": [{"name": "Ancrage", "ID": "TMP_2", "type": "circle", "children": [ {"name": "Facilitation", "mycolor":"#FF6600", "ID": "TMP_3","type":"role", "mod":"template",  "size":10}, {"name": "Pilotage",  "mycolor":"#FF2200", "type":"role", "mod":"template", "ID": "TMP_4", "size":10}, {"name": "Mémoire", "mycolor":"#FF9900", "type":"role", "mod":"template", "ID": "TMP_8", "size":10}, {"name": "Role opérationnel", "type":"role", "ID": "TMP_9", "size":10}]},{"name": "CA", "ID": "TMP_5", "type": "circle", "children": [ {"name": "Trésorier", "ID": "TMP_6","type":"role",  "size":10}, {"name": "Président", "type":"role", "ID": "TMP_7", "size":10}]}]}';

		} else {
			echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé')."(".$_GET['id'].(isset($_GET["accesskey"])?", ".$_GET["accesskey"]:"").")"));
		}
	} else echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé (aucun ID spécifié)')));

} else echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé (aucun ID spécifié)')));
?>
