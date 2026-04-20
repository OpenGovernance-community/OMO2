<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();


		if (isset($_GET["p"]) && $_GET["p"]!="") {
			// Sélectionne tous les templates attachés à des parents du noeud
			$templates=new \dbObject\ArrayHolon();
				$params= array();	
				$params["filter"] = "templatename is not null and IDholon_parent in (".$_GET["p"].")";
			$templates->load($params);
			echo "<select id='selected_template'>";
			echo "<option>Choisissez...</option>";
			foreach ($templates as $template) {
				echo "<option value='".$template->get("id")."'";
				if (isset($_GET["t"]) && $_GET["t"]==$template->get("id")) { echo " selected"; $selectedTemplate=$template;}
				echo ">".$template->get("templatename")."</option>";
			}
			echo "</select>";
			// Ajoute encore le code spécifique pour gérer le choix dans la select box
?>
			<script>
				$("#selected_template").change(function() {
					$("#form_new_node").load("/popup/circle/editinterface.php?p="+$parents+"&t="+$(this).val());  

				});
			</script>
<?

		}
		// Appel sur un template (nouveau ou pas sauvé)
		if (isset($_GET["t"]) && $_GET["t"]>0) {
			if (!isset($selectedTemplate)) {
				$selectedTemplate=new \dbObject\Holon();
				$selectedTemplate->load($_GET["t"]);
			}
			// Crée le champ avec le type de noeud
			echo "<input type='hidden' id='type_role' value='".$selectedTemplate->get("IDtypeholon")."'>";
			echo $selectedTemplate->getString("IDtypeholon");
			// Récupère les propriétés liées à ce template

			echo "<div>Nom:</div>";
			echo "<input id='role_field_name' placeholder='Nom'>";

			$properties=$selectedTemplate->getPropertiesValue();
			foreach ($properties as $property) {
				// Affiche le formulaire correspondant
				echo "<div>".$property->get("name").":</div>";
				// Affiche la partie héritée
				echo "<div id='role_parent_".$property->get("IDproperty")."' style='white-space: pre-line;border:1px solid lightgrey; border-bottom:0px; background:#f9f9f9; border-radius:3px 3px 0px 0px; padding:5px;'>".str_replace("'","&apos;",($property->get("value_parents")!==null?$property->get("value_parents")."\n":"").($property->get("value")!==null?str_replace("|","\n",$property->get("value")):""))."</div>";
				echo "<textarea style='border-top:0px; border-radius:0px 0px 3px 3px' id='role_field_".$property->get("IDproperty")."' placeholder='".str_replace("'","&apos;",$property->get("name"))."'></textarea>";
			}
			
		} 
		// Appel sur un noeud (sauvé, donc)
		if (isset($_GET["n"]) && $_GET["n"]>0) {
			if (!isset($selectedTemplate)) {
				$selectedTemplate=new \dbObject\Holon();
				$selectedTemplate->load($_GET["n"]);
			}

			
			// Crée le champ avec le type de noeud
			echo "<input type='hidden' id='type_role' value='".$selectedTemplate->get("IDtypeholon")."'>";
			// Récupère les propriétés liées à ce template
			
			echo "<div>Nom:</div>";
			echo "<input id='role_field_name' placeholder='Nom'>";

			$properties=$selectedTemplate->getPropertiesValue();
			foreach ($properties as $property) {
				// Affiche le formulaire correspondant
				echo "<div>".$property->get("name").":</div>";
				// Affiche la partie héritée
				echo "<div id='role_parent_".$property->get("IDproperty")."' style='white-space: pre-line;border:1px solid lightgrey; border-bottom:0px; background:#f9f9f9; border-radius:3px 3px 0px 0px; padding:5px;'>".str_replace("'","&apos;",($property->get("value_parents")!==null?$property->get("value_parents")."\n":""))."</div>";
				echo "<textarea style='border-top:0px; border-radius:0px 0px 3px 3px' id='role_field_".$property->get("IDproperty")."' placeholder='".str_replace("'","&apos;",$property->get("name"))."'></textarea>";
			}
			
		} 
		
	
?>
