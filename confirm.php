<?
	require_once("config.php");
	require_once("shared_functions.php");
?>
<html>
	<head>
		<title>EasyPV - <?=T_("Facilitez-vous la prise de notes !");?>?></title>
		<meta charset="utf-8">
		
		<script src="https://code.jquery.com/jquery-3.6.0.js"></script>
		<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
		<script>
		// Change JQueryUI plugin names to fix name collision with Bootstrap.
		$.widget.bridge('uitooltip', $.ui.tooltip);
		$.widget.bridge('uibutton', $.ui.button);
		</script>
		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.bundle.min.js"></script>


		<!-- include summernote css/js -->
		<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
		<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

		<script src="shared_functions.js"></script>
		<script src="/common/assets/password_policy.js"></script>
		<script>
			$(function() {		
				$("#btn_finalize").click(function (e) {
					sendForm($("#formulaire-edit"),success);
				});
			});
		</script>
		<link href="shared_css.css" rel="stylesheet">
		<style>
			@media screen {

				.top {background:var(--midlow-bg-color)}
				.left { background:var(--light-bg-color)}
				.right {  background:var(--light-bg-color)}
				.bottom { background:var(--midlow-bg-color)}
				
			}
		</style>
	</head>
	<body>
	<div style='margin:10px auto; max-width:800px; width:100%; border:1px solid black; border-radius:10px; overflow:hidden; box-shadow: 5px 5px 10px rgba(0,0,0,0.5); '>
<?

		function fct_motdepasse($object, $colonne, $default) {
			$content="<input type='password' autocomplete='off' id='password' name='password' placeholder='Choisissez un mot de passe' minlength='12'><br><input type='password' autocomplete='off' id='password2' name='password2' placeholder='Confirmez votre mot de passe' minlength='12'>";
			$content.="<div class='common-password-policy' data-password-policy='1' data-password-policy-password-selector='#password' data-password-policy-confirm-selector='#password2' data-password-policy-email-selector='#email' data-password-policy-min-length='12' data-password-policy-required-keys='length,lower,upper,digit,special' data-password-policy-status-empty='Le mot de passe doit respecter les criteres ci-dessous.' data-password-policy-status-valid='Mot de passe OK.' data-password-policy-status-invalid='Mot de passe encore incomplet.' data-password-policy-match-empty='Retapez le meme mot de passe pour confirmation.' data-password-policy-match-valid='Confirmation OK.' data-password-policy-match-invalid='La confirmation ne correspond pas encore.'>";
			$content.="<span class='common-password-policy__status' data-password-status aria-live='polite'>Le mot de passe doit respecter les criteres ci-dessous.</span>";
			$content.="<ul class='common-password-policy__rules'>";
			$content.="<li class='common-password-policy__rule' data-password-rule='length'>Au moins 12 caracteres</li>";
			$content.="<li class='common-password-policy__rule' data-password-rule='lower'>Au moins une minuscule</li>";
			$content.="<li class='common-password-policy__rule' data-password-rule='upper'>Au moins une majuscule</li>";
			$content.="<li class='common-password-policy__rule' data-password-rule='digit'>Au moins un chiffre</li>";
			$content.="<li class='common-password-policy__rule' data-password-rule='special'>Au moins un caractere special ou un espace</li>";
		$content.="<li class='common-password-policy__rule' data-password-rule='email'>Evitez de reprendre votre e-mail ou votre nom d'utilisateur</li>";
			$content.="</ul>";
			$content.="<span class='common-password-policy__match' data-password-match aria-live='polite'>Retapez le meme mot de passe pour confirmation.</span>";
			$content.="</div>";
			return array("Mot de passe",$content);
		}
		
	// Charge le compte avec cet ID
	$user=new \dbObject\user();
	$user->load(["code",$_GET["code"]]); // Chargement sur la base de l'email
	
	if ($user->get("id")>0 && $user->get("codeexpiration")!=null && $user->get("codeexpiration")>new \DateTime()) {
		
		// Affiche un texte d'intro selon la situation
		if ($user->get("password")!=null) {
			// Récupération de mot de passe
				$title="Récupération du mot de passe";
				$intro="<p>Veuillez définir un nouveau mot de passe, qui remplaçera le précédent. Si vous n'êtes pas l'auteur de cette demande de réinitialisation, ignorez simplement cette demande et connectez-vous comme normalement.</p>";
				$btntxt="Valider le nouveau mot de passe";
				$params=array(
					"fields" => array(array("code",null,true),array("email",null,true),"motdepasse"),
					"buttons" => false,
					"action" => "/ajax/createaccount.php",
					"success" => "index.php"
				);
			} else {
				$title="Finalisation de la création du compte";
				$intro="<p>Veuillez compléter les informations suivantes pour finaliser la création de votre compte.</p>";
				$btntxt="Créer le compte";
				$params=array(
					"fields" => array(array("code",null,true),"username","firstname","lastname","email","motdepasse"),
					"buttons" => false,
					"action" => "/ajax/createaccount.php",
					"success" => "license.php"
				);			
		}
		
		// Affiche le formulaire de saisie de cet utilisateur
				echo "<div class='bottom' style='padding:10px' >".$title."</div>";
				
				
				echo "<div style='padding:20px;  width:100%; '>";

				echo $intro;
	
				$user->display("adminEdit.php",$params);	
				
				echo "<input type='button' id='btn_finalize' value='".str_replace("'","&apos;",$btntxt)."'>";	
		
	} else {
		sleep(3);
		echo "Invalid access code";
	} 
?>
</div>
<div class='bottom' style='padding:10px' >&nbsp; <img src='img/systemeD.png' style='height:30px;'></div><div>
	</body>
</html>
