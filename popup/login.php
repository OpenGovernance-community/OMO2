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
  <h3><?=T_("Connectez-vous")?></h3>
  <div>
	  <form  onsubmit="return false" name='loginform' id='loginform' class='ajax'  action='/ajax/login.php'>
	  <div class='label'>Email:</div>
	  <input type='text' name='email' id='email' >
	  <div class='label'>Mot de passe:</div>
	  <input type='password' name='password' id='password' >
	  <input type='checkbox' name='remember' id='remember' value='1'>&nbsp;Se souvenir de moi
 		<div><button type="button" name='loginbtn' id='loginbtn'>Connexion</button>&nbsp;
 		Mot de passe oublié? <span id='rememberbtn'>Cliquez ici</span>.</div>
		</form>
		
		<form  onsubmit="return false" name='reminderform' id='reminderform' class='ajax' action='/ajax/login.php' style='display:none'>
			<div>Renseignez votre e-mail. Si le compte existe, des instructions pour définir un nouveau mot de passe vous seront envoyées.</div>
	  Votre email:
	  <input type='text' name='email' id='email2' >
	  <button type="button" name='rememberaccount' id='rememberaccount'>Réinitialiser le mot de passe</button>
		</form>
		
		
  </div>
  <h3><?=T_("Pas encore de compte? Cliquez ici!")?></h3>
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
  <h3><?=T_("Pourquoi créer un compte ?")?></h3>
  <div>
    <p>
 <?=T_("En créant un compte, vous accédez à de nouvelles fonctionnalités:")?>
     </p>

 <?=T_("<ul><li>Vous disposez d'un lien permettant de partager votre PV avec d'autres personnes, en lecture seule.</li><li>Vous pouvez enregister votre PV sur un serveur, et le retrouver à tout moment.</li><li>Vous pouvez télécharger votre PV au format Word, vous permettant de l'éditer et le mettre en page à l'issue de la réunion.</li><li>Vous pouvez créer des sections dans votre ordre du jour, pour regrouper les points selon certaines thématiques. Par exemple pour différencier les points opérationnels des points de réorganisation.</li></ul>")?>

 </div>
  <h3><?=T_("Politique de confidentialité")?></h3>
  <div>
    <?=T_("<p><strong>Dernière mise à jour :</strong> 8 février 2024</p>")?>

    <p><?=T_("Nous attachons une grande importance à la protection de votre vie privée et à la sécurité de vos données personnelles. Cette politique de confidentialité explique comment nous collectons, utilisons et protégeons vos informations lorsque vous utilisez notre site web.")?></p>

    <h2><?=T_("1. Collecte de données")?></h2>

    <p><?=T_("Nous ne collectons aucune information personnelle vous concernant sans votre consentement explicite. Lorsque vous utilisez notre site web, aucune donnée personnelle n'est automatiquement enregistrée, et aucune technologie de suivi n'est utilisée.")?></p>

    <h2><?=T_("2. Utilisation des données")?></h2>

    <p><?=T_("Nous ne recueillons aucune donnée à des fins statistiques, publicitaires ou de toute autre exploitation. Les données que vous fournissez (le cas échéant) sont exclusivement utilisées pour répondre à vos demandes ou pour personnaliser votre expérience sur notre site, conformément à vos préférences.")?></p>

    <h2><?=T_("3. Partage d'informations")?></h2>

    <p><?=T_("Nous ne partageons, ne vendons ni ne louons aucune information personnelle à des tiers. Vos données sont conservées de manière sécurisée et ne sont accessibles qu'aux personnes autorisées, le cas échéant.")?></p>

    <h2><?=T_("4. Propriété des données")?></h2>

    <p><?=T_("Toutes les informations que vous fournissez sur notre site web sont et restent votre propriété exclusive. Nous ne revendiquons aucun droit de propriété sur vos données.")?></p>

    <h2><?=T_("5. Sécurité des données")?></h2>

    <p><?=T_("Nous mettons en œuvre des mesures de sécurité appropriées pour protéger vos données contre tout accès non autorisé, toute divulgation, toute altération ou toute destruction.")?></p>

    <?=T_("<h2>6. Cookies</h2><p>Nous n'utilisons pas de cookies ou d'autres technologies de suivi pour collecter des informations personnelles sur vous.</p>")?>

    <h2><?=T_("7. Modifications de la politique de confidentialité")?></h2>

    <p><?=T_("Nous nous réservons le droit de mettre à jour notre politique de confidentialité à tout moment. Les modifications seront publiées sur cette page avec la date de la dernière mise à jour.")?></p>

    <h2><?=T_("8. Contactez-nous")?></h2>

    <p><?=T_("Si vous avez des questions concernant notre politique de confidentialité, veuillez nous contacter à [adresse e-mail de contact].")?></p>
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
