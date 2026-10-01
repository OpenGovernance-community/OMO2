<?
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");

	// Initialise le login
	$connected=checklogin();

	// Affichage des champs de formulaires
	echo "<div id='form_new_node'></div>";
	echo "<button id='btn_create_role'>Ajouter</button>";
?>
<script src="<?= commonAssetUrl('/popup/circle/addrole.js') ?>"></script>
<?
?>
