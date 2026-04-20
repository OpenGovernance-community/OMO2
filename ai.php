<?
	error_reporting(E_ERROR | E_PARSE);
	require_once($_SERVER['DOCUMENT_ROOT']."/config.php");
	require_once($_SERVER['DOCUMENT_ROOT']."/shared_functions.php");
	
	// Initialise le login
	$connected=checklogin();
?>
<html>
	<head>

		<?writeHeadContent("EasyQR - ".T_("Gardez les liens"));?>

<style>

.showsource {color:#00A; cursor:pointer;}
.showsource:hover {text-decoration:underline;} 
.source {display:none}
</style>
</head>
<body>
	
<?php
echo "<div style='position:fixed; top:0px; left:0px; right:0px; height:110px; padding:10px; background:#eee;'>";
echo "<form method='POST' style='text-align:center;'>";
echo "<input name='question' id='question' style='margin:auto;display:block; width:80%; padding:10px; border:1px solid #aaa; border-radius:10px;'>";
echo "<button style='padding:5px; margin-top:10px;'>Poser votre question</button>";
echo "</form>";
echo "</div>";
echo "<div style='padding:10px;padding-top:120px;'>";

if (isset($_POST["question"])) {
echo "<h3>Votre question:</h3>";
echo "<b>".$_POST["question"]."</b>";	
	
// URL de l'API
$url = 'https://api.afforai.com/api/api_completion';

// Données à envoyer
$data = array(
    'apiKey' => '2169c853-859c-4ae6-bd67-0d2fb7b9d36d',
    'sessionID' => '65bce2ddd802e36355b8eb36',
    'history' => array(
        array(
            'role' => 'user',
            'content' => $_POST["question"]
        )
    ),
    'powerful' => true,
    'google' => false
);
print_r ($data);
// Options pour la requête
$options = array(
    'http' => array(
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($data)
    )
);
print_r ($options);
// Création du contexte de la requête
$context = stream_context_create($options);

// Exécution de la requête et récupération de la réponse
$response = json_decode(file_get_contents($url, false, $context));
echo "<p>Reponse brut</p>";
print_r ($response);
echo "<h3>Ma réponse:</h3>";
// Affichage de la réponse
if ($response->success) {
	$result = $response->output->completion;
	// Remplacement des éléments en gras par les balises HTML correspondantes
	$result = preg_replace('/\*\*(.*?)\*\*/', '<b>$1</b>', $result);
	$result = preg_replace( '/【(\d+)†source】/', " <span class='showsource' data-src='$1'>(source<sup>$1</sup>)</span>", $result);

	$result = str_replace("\n","<br>",$result);
	echo $result;
	echo "<h4>Sources:</h4>";
	$old="";
	foreach ($response->output->file_citations as $citation) {
		if ($citation->name!=$old) {
			if ($old!="") echo ", ";
			echo $citation->name;
			$old=$citation->name;
		}
	}
	
	$i=1;
	foreach ($response->output->file_citations as $citation) {
		echo "<div id='source_".$i."' class='source'>";
		echo "<div style='font-size:100%; font-weight:bold; margin-top:10px;'>".$i.". ".$citation->name."</div>";
		echo "<div style='border:1px solid #aaa; padding:10px; font-size:80%'><b>Page ".$citation->page." : </b><br>".str_replace("\n","<br>",$citation->content)."</div>";
		$i++;
		echo "</div>";
	}
	foreach ($response->output->google_citations as $citation) {
		echo "<div style=''><a href='".$citation->url."'>".$citation->name."</a></div>";
		
	}
}
echo "<br><br>";
echo "<!--\n";
print_r ($response);
echo "-->";
}

?>
<script>
	$(function() {
		$(".showsource").click(function() {
			$("#source_"+$(this).attr("data-src")).show();
		});
	});
</script>
</div>
</body>
</html>
