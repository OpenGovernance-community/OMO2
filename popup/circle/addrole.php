<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");


	// Initialise le login
	$connected=checklogin();

	// Affichage des champs de formulaires
	echo "<div id='form_new_node'></div>";
	echo "<button id='btn_create_role'>Ajouter</button>";


?>
<script>
   	// Récuipère l'ID du noeud permettant de générer la liste des propriétés
	// Si l'IDdb du noeud n'est pas spécifié, s'appuie sur son template. Et si pas de template, récupère l'ID de base, qui doit correspondre à une valeur dans la base de données
	function getRef(node) {
	    if (node.IDdb !== undefined && node.IDdb !== null && node.IDdb !== "" && !isNaN(Number(node.IDdb))) return node.IDdb;
		if (node.t !== undefined && node.t !== null && node.t !== "" && !isNaN(Number(node.t))) {
			if (node.ID !== undefined && node.ID !== null && node.ID !== "" && !isNaN(Number(node.ID))) return node.ID+","+node.t;
			else
				return node.t;
		} else {
			if (node.ID !== undefined && node.ID !== null && node.ID !== "" && !isNaN(Number(node.ID))) return node.ID;
		}
		return null; // Par défaut
	}
   
  // Récupère la chaîne des ID des parents, pour récupérer côté serveurs les templates associés
  function getParentsID(node,sep="") {
	  ref=getRef(node);
	  if (node.parent) {
		if (ref)
			return sep+ref+getParentsID(node.parent,",");
		else return getParentsID(node.parent,",");
	}
	else
		if (ref)
			return sep+ref;
  }	
	
  $( function() {
	 // Récupère les ID des parents (seuls les IDs sauvés peuvent avoir des templates associés)
	$parents=getParentsID(currentnode);
	if (!$parents) $parents=root.ID; // Si aucune référence à des noeuds trouvée, alors c'est une nouvelle structure et on utilise l'ID qui est initialisé avec l'ID du modèle dans la DB
	// Récupère l'interface tenant en compte le type de noeud et des templates disponibles, et l'affiche
	$("#form_new_node").load("/popup/circle/editinterface.php?p="+$parents);  
	  
	// Create account
	$("#btn_create_role").click(function (e) {
			// Ajoute un élément au noeud courant
			console.log(currentnode);
			if (currentnode) {
				if (!currentnode.children)
					currentnode.children=new Array();
				
				const newNode = {
				  ID: "TMP_"+Date.now(),
				  type: $("#type_role").val(), // Définition du type
				  size:10-currentnode.deph*2, // Taille directment adaptée
				  t: $("#selected_template").val(),
				  data : {},
				};	
				$("[id^='role_field_']").each(function() {
					let elementId = $(this).attr("id");
					let key = elementId.replace("role_field_", ""); // Supprime le préfixe

				if (key=="name") 
					newNode[key]=$(this).val();
				else
					newNode.data["d"+key]={"value" : $(this).val(),"ancestor" : $("#role_parent_"+key).html()}; // Initialise la valeur si la clé existe

				});


				console.log(newNode);
					
			

			currentnode.children.push(newNode);

			
			
			save();
			refreshCircle();
			// Ferme la fenêtre
			closePopup()
		}
		
	});
});
</script>
<?		

?>
