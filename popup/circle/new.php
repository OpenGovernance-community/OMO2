<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();

		
		// Charge tous les PV de la personne
		$listeOrga=new \dbObject\ArrayHolon();
		// Limite aux holons de type organisations, définis comme templates.
			$params= array();	
			$params["filter"] = "IDtypeholon=4 and active=1 and IDuser=1 and templatename is not null";
		$listeOrga->load($params);
		echo '<div id="accordion">';
		echo '<h3>'.T_("Choisissez un modèle").'</h3>';
		echo '<div>';
		echo "<H1>".T_("Modèles de base")."</H1>";
		echo "<div id='orgaliste' class='loading_liste'>";
		foreach ($listeOrga as $orga) {
			echo "<div class='loading_element loadTemplate' data-src='".$orga->get("id")."'><b>".($orga->get("templatename"))."</b><br/><span style='font-size:80%'>".T_("Date de création").": ".$orga->get("datecreation")->format("d.m.Y H:i")."</span></div>";
		}
		echo "</div>";
		echo '</div>';
		echo '</div>';
		


?>
<script>
	// Charge le PV en Ajax

	$(function() {
		
    // Crée l'accordeon
    $( "#accordion" ).accordion({heightStyle: "fill"});
    // Le met à jour lorsque la fenêtre change
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
		
		
		$("#orgaliste").delegate(".loadTemplate","click",function(e) {
			fetch('/ajax/loadtemplate.php?id='+$(this).attr("data-src"))
				.then(response => {
					if (!response.ok) {
						throw new Error("<?=T_("Erreur réseau lors de la récupération des données.")?>");
					}
					return response.json();
				})
				.then(data => {
					// S'assure qu'il était authorisé de lire ce PV
					if (data.error) {
						alert (data.errorMsg);
					
					} else
					
					if (confirm("<?=T_("Êtes-vous sûr de vouloir écraser le contenu de l'éditeur avec la structure chargée ?")?>")) {
						root=data;
						localStorage.setItem('circlestructure', JSON.stringify(root));
						currentnode=focusNode=root;
						
						// Raffraichi l'affichage
						refreshCircle();
												
						$("#saved").val("");
					} 
					// Ferme la fenêtre
					closePopup()
				})
				.catch(error => {
					console.error('Erreur:', error);
				});	
		});
	});

</script>
<style>
	.loading_element {cursor:pointer;background:rgba(0,0,0,0.05); padding:5px; border-radius:3px; margin-bottom:5px;}
	.delete_option {display:none;}
	.loading_element:hover {background:rgba(0,0,0,0.1)}
	.loading_element:hover .delete_option {display:block; float:right; background:rgba(0,0,0,0.1); padding:3px; border-radius:3px;}
	.loading_element:hover .delete_option:hover {background:#FFFF00 }
</style>

