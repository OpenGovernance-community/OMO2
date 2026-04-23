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
	body {
		margin:0;
		padding:18px;
		background:#f8fafc;
		font-family:Arial, Helvetica, sans-serif;
		color:#0f172a;
	}
	.settings-accordion .ui-accordion-header {
		margin-top:10px;
		padding:14px 18px;
		border:1px solid #dbe4ee;
		border-radius:16px;
		background:linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
		color:#0f172a;
		font-weight:700;
	}
	.settings-accordion .ui-accordion-header.ui-state-active {
		border-bottom-left-radius:0;
		border-bottom-right-radius:0;
	}
	.settings-accordion .ui-accordion-content {
		padding:18px;
		border:1px solid #dbe4ee;
		border-top:0;
		border-radius:0 0 16px 16px;
		background:#ffffff;
		position:relative;
	}
	.settings-accordion button[type='button'] {
		min-height:44px;
		padding:10px 18px;
		border:0;
		border-radius:12px;
		background:#2563eb;
		color:#fff;
		font-weight:700;
		cursor:pointer;
		box-shadow:0 12px 24px rgba(37,99,235,.18);
	}
	.settings-summary {
		display:grid;
		gap:10px;
	}
	.settings-summary__item {
		padding:12px 14px;
		border:1px solid #dbe4ee;
		border-radius:12px;
		background:#f8fafc;
	}
	.settings-summary__item strong {
		display:block;
		margin-bottom:4px;
		font-size:12px;
		text-transform:uppercase;
		letter-spacing:.04em;
		color:#475569;
	}
</style>
<div id="accordion" class="settings-accordion">
  <h3><?=T_("Vos paramètres")?></h3>
  <div>
<?	
	echo "<div class='settings-summary'>";
	echo "<div class='settings-summary__item'><strong>Paramètre basic</strong>".$user->getParameter("basic")."</div>";
	echo "<div class='settings-summary__item'><strong>Paramètre numeric</strong>".$user->getParameter("numeric")."</div>";
	echo "<div class='settings-summary__item'><strong>Checkbox</strong>".($user->getParameter("check")?"true":"false")."</div>";
	echo "<div class='settings-summary__item'><strong>Select</strong>".$user->getParameter("select")."</div>";
	echo "</div>";
?>
	</div>
	<h3><?=T_("Modifier les paramètres")?></h3><div>
<?
	echo "<form name='formulaire' id='param_formulaire' action='/ajax/saveaccount.php?origin=params'>";
	$params=array(
		"fields" => array("parameters"),
		"buttons" => false,
		"form" => false,
	);	
	$user->display("adminEdit.php",$params);
	echo "<button type='button' id='updateparams'>Mettre à jour</button>";
	echo "</form>";
?>
</div>
</div>
<script>


  $( function() {
	$("#updateparams").click(function (e) {
		sendForm($("#param_formulaire"),success);
	});
	  
    $( "#accordion" ).accordion({heightStyle: "fill"});
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
	
});
</script>
