<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");


	// Initialise le login
	$connected=checklogin();

	// Affichage des champs de formulaires
	echo "<select id='type_role'><option value='1'>Rôle</option><option value='2'>Cercle</option><option value='3'>Groupe</option></select>";
	echo "<input id='role_field_name' placeholder='Nom'>";
	echo "<textarea id='role_field_rde' placeholder='Raison d&apos;être'></textarea>";
	echo "<textarea id='role_field_domain' placeholder='Domaines d&apos;autorité'></textarea>";
	echo "<textarea id='role_field_redevability' placeholder='Attendus'></textarea>";
	echo "<button id='btn_create_role'>Ajouter</button>";


?>
<script>
  $( function() {
	// Create account
	$("#btn_create_role").click(function (e) {
			// Ajoute un élément au noeud courant
			console.log(currentnode);
			if (currentnode) {
				if (!currentnode.children)
					currentnode.children=new Array();
				
				const newNode = {
				  ID: "TMP_"+Date.now(),
				  type: $("#type_role").val(),
				  size:10-currentnode.deph*2,
				  data : {},
				};	
				$("[id^='role_field_']").each(function() {
					let elementId = $(this).attr("id");
					let key = elementId.replace("role_field_", ""); // Supprime le préfixe

				if (key=="name") 
					newNode[key]=$(this).val();
				else
					newNode.data[key]=$(this).val(); // Initialise la valeur si la clé existe

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
