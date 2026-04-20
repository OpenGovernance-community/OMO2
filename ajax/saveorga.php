<?php
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
	
	if ($connected) {
		
		// Crée un tableau de conversion des ID pour les duplications entre modèles et version finale sauvegardée		
		$GLOBALS["convertedID"]=array();
		
		function updateTemplate($idNode,$root) {
			// Est-ce que le template est déjà dans la table de conversion, auquel cas retourne la valeur dans la table de conversion
			if (in_array($idNode,$GLOBALS["convertedID"])) 
				return $GLOBALS["convertedID"][$idNode];
			
			// Charge le template
			$template=new \dbObject\Holon();
			$templateOrigine=new \dbObject\Holon(); // Lien vers l'original pour copie des propriétés
			$template->load($idNode);
			$templateOrigine->load($idNode);
			
			// Regarde s'il est déjà connecté à l'orga ?
			if ($template->get("IDholon_org")==$root) {
				// L'ajoute dans la table de conversion (n'y était pas, sinon on ne serait pas ici)
				$GLOBALS["convertedID"][$idNode]=$idNode;
				return $idNode;
			}
			// Sinon, le duplique et adapte l'orga
			$template->set("id",null);
			$template->set("IDholon_org",$root);
			// Redéfini le parent
			$template->set("IDholon_parent",$GLOBALS["convertedID"][$template->get("IDholon_parent")]);
			
			// Et regarde son template
			if (($template->get("IDholon_template")!==null) && is_numeric($template->get("IDholon_template")) && $template->get("IDholon_template")>0) {
				$template->set("IDholon_template",updateTemplate($template->get("IDholon_template")));
			}
			
			// Sauve le template
			$template->save();


		// Crée le tableau des propriétés définies (qui peuvent être nulles)
		$properties=$templateOrigine->getHolonProperties();
		
		// Pour chacune de ces propriété, duplique la propriété (si pas encore fait)
		foreach ($properties as $property) {
			echo "Duplicate propertyvalue ".$property->get("id");
			// Est-ce que la propriété de base appartient à l'orga courante ?
			echo "Check property ".$property->get("IDproperty");
			$realproperty=new \dbObject\Property();
			$realproperty->load($property->get("IDproperty"));
			print_r($realproperty);
			echo "Compare ".$realproperty->get("IDholon_organization")." and ".$root;
			if ($realproperty->get("IDholon_organization")!==$root) {
				// Est-ce déjà transformé ?
				echo "Find property ".$property->get("IDproperty");
				
				// Duplique la propriété, et l'ajoute au tableau de correspondance
				$oldID=$realproperty->get("id");

				if (isset($GLOBALS["convertedPropertyID"][$oldID])) {
					echo "property alerady converted (".$oldID." to ".$GLOBALS["convertedPropertyID"][$oldID].")";
				} else {

					$newProp=$realproperty;
					$newProp->set("id",null);
					$newProp->set("IDholon_organization",$root);
					$newProp->save();
					echo "property converted from ".$oldID." to ".$newProp->get("id");
					$GLOBALS["convertedPropertyID"][$oldID]=$newProp->get("id"); // Converti l'ID
				}
				// Duplique la valeur (HolonProperty)
				$property->set("id",null); // Pour en faire une nouvelle à la sauvegarde
				$property->set("IDholon",$template->get("id")); // Redéfini le holon au nouvellement créé
				$property->set("IDproperty",$GLOBALS["convertedPropertyID"][$oldID]); // Redéfini la propriété au nouveau
				// Supprime les éléments ajoutés par la fonction GetAllValues
				//$property->clear("liste_parent");
				//$property->clear("value_parents");
				
				
				print_r($property);
				$property->save();
				echo "New PropertyValue created (".$property->get("id").")";
				
			} else {
				// Ne fait rien ici je pense
				
			}
			
			
			
			//$template->setPropertyValue(substr($key,1),$data);

			// Récupère la veleur unique de la propriété, uniquement si elle est définie
			//if ($property->get("value")!==null || $property->get("value_parents")!==null)
				//$str.='"d'.$property->get("IDproperty").'" : {"value" : "'.($property->get("value")!==null?str_replace('"',"&quot;",$property->get("value")):"").'", "ancestor" : "'.($property->get("value_parents")!==null?str_replace('"',"&quot;",$property->get("value_parents")):"").'"}, ';
		}	



			
			// Duplique aussi ses propriétés, y compris celles qui ne sont pas renseignées
			foreach($node["data"] as $key=>$data) {
				$template->setPropertyValue(substr($key,1),$data);
			}
			
			
			// Met  à jour les infos pour l'arborescence
			$GLOBALS["convertedID"][$idNode]=$template->getId();
			return $template->getId();
			
		}

		function createNodes(&$array,$parent, $root) {
			
			foreach($array as &$node) {
			$holon=new \dbObject\Holon();
			// Est-ce que l'élément a déjà un ID
			if (isset($node['IDdb']) && is_numeric($node['IDdb'])) {
				// Si oui, il s'agit d'une mise à jour
				$holon->load($node['IDdb']);
				$holon->set("active",true); // Le réactive, puisqu'ils ont tous été désactivés au préalable
				// En principe, rien à faire du côté des tables de conversion: un noeud sauvé n'a pas pu changer de type ou de template
				// Faut-il mettre à jour ses infos en fonction d'éventuelles modification de ses templates entre temps ?
			} else {
				// Est-ce que le noeud a un ID numérique, auquel cas il faut dupliquer le noeud de base
				if (isset($node['ID']) && is_numeric($node['ID'])) {
					$holon->load($node['ID']);
					$holon->set("id",null);     // Remet l'ID à zéro pour une copie
					
					// Adapte le template
					// Nouveau noeud, donc forte chance de devoir l'adapter... cela ne se fait pas systématiquement
					// A voir dans un deuxième temps s'il est nécessaire de pouvoir "convertir" un noeud existant (dans le bloc en dessous)
					if (isset($node['t']) && is_numeric($node['t']) && $node['t']>0) {
						$node['t']=updateTemplate($node['t'],$root);
					}
				
				} else {
					// Sinon, il s'agit d'une nouvelle structure (ne devrait pas ou plus se produire)
					$holon->set("IDtypeholon",(int)$node["type"]);
					$holon->set("IDuser",$_SESSION["currentUser"]);
				}
			}
			$holon->set("IDholon_parent",$parent);
			$holon->set("IDholon_org",$root);
			$holon->set("name",$node["name"]);
			
			if (isset($node["t"])) {
				if (isset($GLOBALS["convertedID"][$node["t"]]))
					$holon->set("IDholon_template",$GLOBALS["convertedID"][$node["t"]]);
				else
					$holon->set("IDholon_template",$node["t"]);
			}
			// Sauve pour récupérer l'ID
			$holon->save();	
			// Met encore à jour les datas associées au noeud. Commence par désactiver tous les éléments, puis les réactive ou les crée
			$holon->disableAllProperty();
			
			// Parcours les datas et les enregistre
			foreach($node["data"] as $key=>$data) {
				if (isset($GLOBALS["convertedPropertyID"][substr($key,1)])) 
					$holon->setPropertyValue($GLOBALS["convertedPropertyID"][substr($key,1)],$data); // Si la propriété a été convertie
				else 
					$holon->setPropertyValue(substr($key,1),$data);
			}
			
			

			// Ajoute à la table de conversion, au cas où c'est un template (pourrait aussi être testé...)
			$GLOBALS["convertedID"][$node['ID']]=$holon->getId();
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
				// Est-ce que l'ID est numérique, auquel cas crée une copie de l'élément du template
				if (isset($data['ID']) && is_numeric($data['ID'])) {
					$holon->load($data['ID']);  // Récupère l'élément original
					$holon->set("id",null);     // Remet l'ID à zéro pour une copie
				} else {
					// Sinon, crée un élément totalement nouveau (ne devrait pas se produire)
					$holon->set("IDtypeholon",4);
				}
				$holon->set("IDuser",$_SESSION["currentUser"]);
			}
			// Crée le noeud, le met à jour
			$holon->set("name",$data['name']);
			
			$holon->save();
			// Crée la table de convesion et l'initialise
			$GLOBALS["convert"]=Array();
			if ($data['ID']!=$holon->getId()) 
				$GLOBALS["convertedID"][$data['ID']]=$holon->getId();
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
		// Sinon, retourne pour l'instant une erreur, pas possible de sauver des éléments autres
		{
			echo json_encode([
				'status' => 'error',
				'message' => 'Les données fournies sont invalides (pas une orga).'
			]);	
		}

	} else echo "Erreur, vous nêtes pas connecté";
?>
