<?php
	require_once("../config.php");
	require_once("../shared_functions.php");

// Fonctionne comme loadOrga, sauf que les ID ne sont pas initiés.

function createJSONstr($node) {
	$str='{"name": "'.$node->get("name").'", "ID" : "'.$node->get("id").'", "t" : "'.$node->get("IDholon_template").'", "type": "'.$node->get("IDtypeholon").'"';

	// Ajoute les datas 
	// Récupère la liste des propriétés souhaitées, incluant celles héritées des tempalates
	$properties=$node->getPropertiesValue();
	// Charge parallèlement dans un tableau l'ensemble des valeurs existantes, en vue d'un traitement ultérieur
	
	if (count($properties)>0) {
		$str.=', "data": {';
		foreach ($properties as $property) {
			// Récupère la veleur unique de la propriété, uniquement si elle est définie
			if ($property->get("value")!==null || $property->get("value_parents")!==null)
				$str.='"d'.$property->get("IDproperty").'" : {"value" : "'.($property->get("value")!==null?str_replace('"',"&quot;",$property->get("value")):"").'", "ancestor" : "'.($property->get("value_parents")!==null?str_replace('"',"&quot;",$property->get("value_parents")):"").'"}, ';
		}	
		$str=rtrim($str, ', '); // Supprime la dernère virgule, et ne fera rien s'il n'y avait pas d'enfants
		$str.='}';
	}
	
	// Ajoute les enfants
	if ($node->get("IDtypeholon")>1) {
		$str.=', "size": "10"'; // Pour si l'élément n'a pas d'enfants
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
		
		if (is_null($orga->get("IDuser")) || $orga->get("IDuser")==1) {
			$jsonStr=createJSONstr($orga);
			echo $jsonStr;

		} else {
			echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé')."(".$_GET['id'].(isset($_GET["accesskey"])?", ".$_GET["accesskey"]:"").")"));
		}
	} else echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé (aucun ID spécifié)')));

} else echo json_encode(array('error' => 'true', 'errorMsg' => T_('Accès refusé (aucun ID spécifié)')));
?>
