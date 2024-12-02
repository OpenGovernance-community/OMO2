<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
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
  <h3><?=T_("Vos paramètres")?></h3>
  <div>
<?	
	// Affiche ses paramètres
	echo "<div>Paramètre basic: ".$user->getParameter("basic")."</div>";
	echo "<div>Paramètre numeric: ".$user->getParameter("numeric")."</div>";
	echo "<div>Checkbox: ".($user->getParameter("check")?"true":"false")."</div>";
	echo "<div>Select: ".$user->getParameter("select")."</div>";
?>
	</div>
	<h3><?=T_("Modifier les paramètres")?></h3><div>
<?
	echo "<form name='formulaire' id='param_formulaire' action='/ajax/saveaccount.php?origin=params'>";
	// Affiche le formulaire pour modifier le profil
	$params=array(
		"fields" => array("parameters"),
		"buttons" => false,
		"form" => false,
	);	
	$user->display("adminEdit.php",$params);
	// Affiche le bouton
	echo "<button type='button' id='updateparams'>Mettre à jour</button>";
	// Ferme le formaulaire
	echo "</form>";
?>
</div>
</div>
<script>


  $( function() {
	// Create account
	$("#updateparams").click(function (e) {
		sendForm($("#param_formulaire"),success);
	});
	  
    // Crée l'accordeon
    $( "#accordion" ).accordion({heightStyle: "fill"});
    // Le met à jour lorsque la fenêtre change
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
	
});
</script>
