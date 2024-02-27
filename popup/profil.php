<?
	require_once("../config.php");
	require_once("../shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
	if (!$connected) Die("Login requis");
	
	
	// Charge le user
	$user=new \dbObject\User();
	$user->load($_SESSION["currentUser"]);
	if (!$user->get("id")>0) Die("Utilisateur inconnu");
?>

<style>
	.ui-accordion-header {background-color:#DDD; border:2px solid #AAA; padding:10px; margin-bottom:0px; margin-top:5px;}
	.ui-accordion-content {border: 1px solid #DDD; border-top:0px;padding:10px;position:relative;}
	#rememberbtn {text-decoration:underline;}
</style>
<div id="accordion">
  <h3><?=T_("Votre profil")?></h3>
  <div>
<?	
	// Affiche ses paramètres
	echo $user->get("email");
	echo $user->getParameter("basic");
	echo $user->getParameter("numeric");
?>
	</div>
	<h3><?=T_("Modifier votre profil")?></h3><div>
<?
	echo "<form name='formulaire' id='profil_formulaire' action='/ajax/saveaccount.php?origin=profil'>";
	// Affiche le formulaire pour modifier le profil
	$params=array(
		"buttons" => false,
		"form" => false,
	);	
	$user->display("adminEdit.php",$params);
	// Affiche le bouton
	echo "<button type='button' id='updateprofil'>Mettre à jour</button>";
	// Ferme le formaulaire
	echo "</form>";
?>
</div>
</div>
<script>


  $( function() {
	// Create account
	$("#updateprofil").click(function (e) {
		sendForm($("#profil_formulaire"),success);
	});
	  
    // Crée l'accordeon
    $( "#accordion" ).accordion({heightStyle: "fill"});
    // Le met à jour lorsque la fenêtre change
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
	
});
</script>

