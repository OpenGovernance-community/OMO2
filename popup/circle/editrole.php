<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Il faut être connecté pour pouvoir partager.
	// Initialise le login
	$connected=checklogin();

	// Si l'organisation n'est pas sauvegardée sur on compte et n'a pas de modèles de rôles spécifiques, applique les modèles de base
	// Affichage des champs de formulaires en fonction du type de rôle

	echo "<div id='form_edit_node'></div>";
	// Affiche le bouton pour sauver
	echo "<button id='btn_save_role_final'>Sauver</button>";
?>
<script src="<?= commonAssetUrl('/popup/circle/editrole.js') ?>"></script>
<?
?>
