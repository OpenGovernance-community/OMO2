<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
?>
<style>
	.ui-accordion-header {background-color:#DDD; border:2px solid #AAA; padding:10px; margin-bottom:0px; margin-top:5px;}
	.ui-accordion-content {border: 1px solid #DDD; border-top:0px;padding:10px;position:relative;}
	#rememberbtn {text-decoration:underline;}
</style>
<div style='position:relative; height:100%'>
<div id="accordion">
  <h3><?=T_("Charger une organisation existante")?></h3>
  <div>
<?

	

?>


		
		
  </div>
  <h3><?=T_("Créez une nouvelle organisation")?></h3>
  <div>
	  <p>Pour créer un compte, entrez simplement votre e-mail. Vous recevrez par email les instructions pour finaliser la création de celui-ci, en complétant votre profil et en créant un mot de passe.</p>
 	  <form  onsubmit="return false" name='createform' id='createform' class='ajax' action='/ajax/login.php'>
	  Votre email:
	  <input type='text' name='email' id='email3' >
	  <div class='label'>Sécurité:</div>
<?
	$nb1=rand(1,12);
	$nb2=rand(1,12);
	echo "Combien font ".$nb1."x".$nb2." ? <input type='text' name='result' id='result' style='width:40px'><input type='hidden' name='check' value='".md5($nb1*$nb2)."'>";
?>	  
		<button type="button" name='createaccount' id='createaccount'>Créer un compte</button>
		</form>
 
  </div>
  <h3><?=T_("Aide sur la création")?></h3>
  <div>
    <p>
 <?=T_("En créant un compte, vous accédez à de nouvelles fonctionnalités:")?>
     </p>

 <?=T_("<ul><li>Vous disposez d'un lien permettant de partager votre PV avec d'autres personnes, en lecture seule.</li><li>Vous pouvez enregister votre PV sur un serveur, et le retrouver à tout moment.</li><li>Vous pouvez télécharger votre PV au format Word, vous permettant de l'éditer et le mettre en page à l'issue de la réunion.</li><li>Vous pouvez créer des sections dans votre ordre du jour, pour regrouper les points selon certaines thématiques. Par exemple pour différencier les points opérationnels des points de réorganisation.</li></ul>")?>

 </div>
</div>
</div>
<script>

  $( function() {
    // Crée l'accordeon
    $( "#accordion" ).accordion({heightStyle: "fill"});
    // Le met à jour lorsque la fenêtre change
    window.onresize = function() {
		$( "#accordion" ).accordion( "refresh" );
	};
	

	$("#rememberbtn").click(function(e) {
		// Affiche le formulaire de rappel de connexion
		$("#loginform").hide();
		$("#reminderform").show();
	});	

	// Create account
	$("#createaccount").click(function (e) {
		sendForm($("#createform"),success);
	});

	// Remember account
	$("#rememberaccount").click(function (e) {
		sendForm($("#reminderform"),success);
	});

    
  } );
  


</script>
