<?php
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
	
	if ($connected) {

		function createNodes(&$array,$parent, $root) {
			foreach($array as &$node) {
			$holon=new \dbObject\Holon();
			// Est-ce que l'élément a déjà un ID
			if (isset($node['IDdb']) && is_numeric($node['IDdb'])) {
				// Si oui, il s'agit d'une mise à jour
				$holon->load($node['IDdb']);
				$holon->set("active",true);
			} else {
				// Sinon, il s'agit d'une nouvelle structure
				$holon->set("IDtypeholon",(int)$node["type"]);
				$holon->set("IDuser",$_SESSION["currentUser"]);
			}
			$holon->set("IDholon_parent",$parent);
			$holon->set("IDholon_org",$root);
			$holon->set("name",$node["name"]);
			
			// Met encore à jour les datas associées au noeud
			
			// Sauve pour récupérer l'ID
			$holon->save();	
			$node['IDdb']=$holon->getId();

			if (isset($node["children"]))
				createNodes($node["children"], $node['IDdb'], $root);
			}
		}

		// Récupérer les données JSON envoyées par jQuery
		$tmp=file_get_contents('php://input');
		$data = json_decode($tmp, true);

		// Est-ce que le noeud de base est une orga ?
		if (isset($data['type']) && $data['type']=="4") {
			

			$holon=new \dbObject\Holon();
			// Est-ce que l'élément a déjà un ID
			if (isset($data['IDdb']) && is_numeric($data['IDdb'])) {
				// Si oui, il s'agit d'une mise à jour
				$holon->load($data['IDdb']);
				// Désactive de façon récursice tous les noeuds associés à la structure
				$holon->disableAllChildren();
			} else {
				// Sinon, il s'agit d'une nouvelle structure
				$holon->set("IDtypeholon",4);
				$holon->set("IDuser",$_SESSION["currentUser"]);
			}
			// Crée le noeud, le met à jour
			$holon->set("name",$data['name']);
			
			$holon->save();
			// Met à jour le JSON (qui sera retourné avec les nouvelles infos ensuite)
			$data['IDdb']=$holon->getId();
			
			// Parcours le JSON
			createNodes($data["children"],$data['IDdb'],$data['IDdb']);

		    echo json_encode([
				'status' => 'ok',
				'id' => $holon->getID(),
				'json' => $data,
			]);	
			
		} else
		// Sinon, retourne pour l'instant une erreur
		{
			echo json_encode([
				'status' => 'error',
				'message' => 'Les données fournies sont invalides (pas une orga).'
			]);	
		}

	} else echo "Erreur, vous nêtes pas connecté";
?>
