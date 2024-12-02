<?
	// Charge les librairies
	require_once("../config.php");
	require_once("../shared_functions.php");
	
	// Contrôle le droit d'accès à ce formulaire
	$connected=checklogin();
	if (!$connected) Die("Login requis");
	
	// Crée un objet
	$qr=new \dbObject\qr();
	
	// Si ID spécifié, le charge
	if (isset($_GET["id"])) {
		$qr->load($_GET["id"]);
		
		// S'assure du droit d'étider ce QR
		if ($_SESSION["currentUser"]!=$qr->get("IDuser")) Die("Access denied");
		
		
	} else {
		// Sinon, l'initialise
		$qr->set("IDuser",$_SESSION["currentUser"]);
	}
	
	// Affiche le formulaire pour créer les éléments
	echo "<div style='text-align:center;'><div id='qr_place' style='background-size:cover;margin:auto; width:200px; height:200px; border:3px solid #aaa'></div>";
	echo "<form name='formulaire' id='qr_formulaire' action='/ajax/saveqr.php'>";
	$params=array(
		"buttons" => false,
		"form" => false,
	);	
	
	$qr->display("adminEdit.php",$params);
	
	// bouton pour valider la création/enregistrement
	echo "<button type='button' id='updateqr'>Enregistrer</button>";
	// Ferme le formaulaire
	echo "</form>";
?>
<script>
		let refresh_fct;
$( function() {
	// Create account
	$("#updateqr").click(function (e) {
		sendForm($("#qr_formulaire"),success);
	});
			function refreshQR() {
			$("#qr_place").css("background-image","url(/qr/image/?url="+encodeURIComponent($("#shortcut").val())+")");
		}
		$("#shortcut").on("keyup",function() {
			clearTimeout(refresh_fct);
			refresh_fct=setTimeout(refreshQR,1000);
			
		});
});
</script>
