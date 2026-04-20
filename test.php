<?
		$api_key = "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"; // Remplacez par votre clé API
		$site_url = "systemdd.ch"; // URL du site
		$model="openai/gpt-5-mini"; // Modèle d'IA
		
		$system_param=array();
		$query=array();
		$system_param2=array();
		
		
// Lire le corps brut de la requête POST
$rawData = file_get_contents("php://input");

// Le décoder comme JSON
$data = json_decode($rawData, true); // true = tableau associatif

if (!$data) {
  http_response_code(400);
  echo json_encode(["error" => "Données JSON invalides"]);
  exit;
}

// Exemple d'accès : $data['responses']
$responses = $data['responses'] ?? [];

$questionnaire="";
//1. J’ai besoin de contribuer à quelque chose de plus grand (Spontané, Sens) : 7\n2. Je peux facilement imaginer un futur enthousiasmant (Spontané, Rêve) : 7\n3. J’avance sans tout structurer dès le départ (Spontané, Plan) : 6\n4. J’aime me lancer avec enthousiasme dans ce qui m’inspire (Spontané, Action) : 5\n5. J’apprends vite en expérimentant, même sans tout comprendre (Spontané, Célébration) : 4\n6. J’aime faire avancer les choses dans une ambiance détendue (Spontané, Relation) : 3\n\n7. J’aime orienter un groupe vers un but clair (Directif, Sens) : 4\n8. Je sais transformer une idée en objectifs concrets (Directif, Rêve) : 5\n9. J’ai besoin que les choses soient structurées pour agir (Directif, Plan) : 3\n10. Je pousse les autres à agir quand c’est nécessaire (Directif, Action) : 6\n11. Je valorise la réussite, et pas trop les états d’âme (Directif, Célébration) : 5\n12. Je préfère dire ce qui doit être dit, même si c’est rude (Directif, Relation) : 2\n\n13. J’ai besoin de comprendre pourquoi on fait les choses (Méthodique, Sens) : 3\n14. Je préfère analyser avant de m’engager (Méthodique, Rêve) : 6\n15. Je structure les tâches et les outils pour ne rien oublier (Méthodique, Plan) : 4\n16. Je reste calme et précis quand il faut agir (Méthodique, Action) : 4\n17. Je prends du recul pour évaluer ce qu’on aurait pu mieux faire (Méthodique, Célébration) : 7\n18. Je note ce qui est dit pour garder des traces claires (Méthodique, Relation) : 5\n\n19. Je me sens utile quand je soutiens la mission du groupe (Stable, Sens) : 6\n20. Je fais confiance aux stratégies qui ont fait leurs preuves (Stable, Rêve) : 4\n21. J’ai besoin de savoir exactement ce que je dois faire (Stable, Plan) : 2\n22. Je préfère suivre un rythme régulier plutôt que foncer (Stable, Action) : 5\n23. J’essaie de faire plaisir et d’éviter les conflits (Stable, Célébration) : 5\n24. Je privilégie la qualité de la relation avant tout (Stable, Relation) : 4
foreach ($responses as $response) {
  $id = $response['id'] ?? '';
  $text = $response['question'] ?? 'indefini';
  $disc = $response['disc'] ?? 'indefini';
  $espace = $response['espace'] ?? 'indefini';
  $note = $response['response'] ?? 'indefini';

  // Nettoyage (au cas où)
  $text = trim($text);
  $disc = ucfirst(strtolower($disc));
  $espace = ucfirst(strtolower($espace));

  // Concaténation dans la forme demandée
  $questionnaire .= $id . ". $text ($disc, $espace) : $note\n";
}



// Utilisation des éléments pour création de la chaîne à analyser
		

		// Message tout au début, avec les instructions pour l'assistant
		$system_param[]=array('role' => 'system', 'content' => "Tu es un coach expert en typologies de profils, pédagogie de projet et intelligence collective.");
		$system_param[]=array('role' => 'system', 'content' => "Réponds uniquement avec un objet JSON valide.");
		$system_param[]=array('role' => 'system', 'content' => "Aucune ligne vide.");
		$system_param[]=array('role' => 'system', 'content' => "Aucun commentaire.");
		$system_param[]=array('role' => 'system', 'content' => "Toutes les clés et toutes les valeurs doivent être conformes au format JSON standard.");
		
		// Contenu des échanges
		$query[]=array("role" => "user", "content"=> "Voici les 24 affirmations d’un questionnaire visant à comprendre à la fois la posture naturelle d’un participant dans un projet (Sens, Rêve, Plan, Action, Célébration, Relation) et son style relationnel DISC (Spontané/Enthousiaste, Directif/Résultats, Méthodique/Analytique, Stable/Coopératif). Chaque affirmation correspond à une case croisant une dimension projet et un style. Le participant donne une note de 1 à 7 pour dire à quel point cette affirmation lui ressemble. Je te donne les affirmations, les styles et dimensions associées, ainsi que les réponses. Fais une analyse synthétique en JSON avec les sections : total_disc, total_projet, synthese, description, ce_qui_est_facile, ce_qui_est_difficile, style_relationnel, recommandations_collaboratives.En ayant une attention particulière à utiliser les guillemets pour délimiter les champs, dans un format JSON valide, sans ajouter de gillemets dans les chaînes de caractère.");

		$query[]=array("role" => "user", "content"=> "Format souhaité : 	{
	\"total_disc\": {
	 \"D\": \"score D\",
	 \"I\": \"score I\",
	 \"S\": \"score S\",
	 \"C\": \"score C\"
 },
	\"total_projet\": {
	 \"Sens\": \"score sens\",
	 \"Rêve\": \"score reve\",
	 \"Plan\": \"score plan\",
	 \"Action\": \"score action\",
	 \"Celebration\": \"score celebration\",
	 \"Relation\": \"score relation\"
 },
	
  \"synthese\": 'Description en utilisant en même temps les 6 espaces de la démarche Z et le profil DISC.\",
  \"description\": 'Description en utilisant en même temps les 6 espaces de la démarche Z et le profil DISC. Sous la forme d'un paragraphe de minimum 80 mots.\",
  \"ce_qui_est_facile\": [
    \"3 à 6 éléments décrivant ce qui est facile\",
    \"élément suivant\",
    \"etc...\"
  ],
  \"ce_qui_est_difficile\": [
   \"3 à 6 éléments décrivant ce qui est difficile\",
    \"élément suivant\",
    \"etc...\"
  ],
  \"style_relationnel\": {
    \"dominant\": ['Tableau avec un élément DISC dominant\"],
    \"teinte\": ['Tableau avec l'élément DISC secondaire\"],
    \"moins_present\": ['Tableau avec\", \"les autres éléments DISC moins presents\"]
  },
  \"recommandations_collaboratives\": [
    \"Tableau simple sans sous-sections avec 2 à 4 recommandations\",
    \"Autre recommandation\",
    \"Etc...\"
  ]
}");
		$query[]=array("role" => "user", "content"=> "Les questions utilisent une matrice à double entrée: l'une est le modèle DISC, l'autre les 6 espaces de la démarche Z: Sens/Visionnaires (la vision, la vission et les valeurs d'une organisation, sa raison d'être), le Rêve/Stratèges (Les priorités, la stratégie et les objectifs), le Plan/Planificateurs (La structuration, l'organisation, les règles, directives et processus), l'Action/Acteurs (la coordination, la synchronisation, le partage d'information et la coopération), la Célébration/Analystes (l'amélioration continue, l'agilité, tirer les enseignements des difficultés), et la Relation/Connecteurs (la dynamique et la qualité relationnelle, l'authenticité, l'ouverture, l'inclusion)");
		$query[]=array("role" => "user", "content"=> "Affirmations et réponses :\n\n".$questionnaire);
		$query[]=array("role" => "user", "content"=> "Voici un exemple de résultat attendu:	
	
	{
	\"total_disc\": {
	 \"D\": \"12\",
	 \"I\": \"18\",
	 \"S\": \"5\",
	 \"C\": \"9\"
 },
	\"total_projet\": {
	 \"Sens\": \"12\",
	 \"Rêve\": \"5\",
	 \"Plan\": \"14\",
	 \"Action\": \"16\",
	 \"Celebration\": \"20\",
	 \"Relation\": \"5\"
 },
  \"synthese\": \"Profil Rêveur-Célébrant à dominante Spontanée et Analytique. Il est motivé par le sens, la vision et l'amélioration continue.\",
  \"description\": \"Ce profil se distingue par une forte orientation Spontanée et un penchant Méthodique. Le participant est naturellement porté vers le Sens et le Rêve, avec une capacité à imaginer des futurs inspirants sans avoir besoin de tout structurer au départ. Sa posture révèle un style relationnel plutôt Enthousiaste (I) teinté d\"Analytique (C), ce qui en fait un générateur d\"idées motivantes, curieux d\"apprendre en expérimentant, tout en gardant une capacité de recul pour évaluer les actions menées. Il montre aussi une sensibilité au besoin de contribuer à quelque chose de plus grand que soi. Cependant, il semble moins à l\"aise dans la confrontation directe ou dans des environnements très cadrés où la structure prime sur l\"inspiration. Dans la démarche Z, son positionnement fort sur les espaces Sens et Rêve indique qu\"il contribue idéalement aux premières phases du projet : donner du sens collectif et proposer des orientations stratégiques mobilisatrices.\",
  \"ce_qui_est_facile\": [
    \"Imaginer une vision enthousiasmante\",
    \"Proposer des idées nouvelles ou créatives\",
    \"Passer à l’action quand le cap a du sens\",
    \"Intégrer les retours et apprendre rapidement\"
  ],
  \"ce_qui_est_difficile\": [
    \"Organiser les tâches dans le détail\",
    \"Recadrer ou confronter directement\",
    \"Maintenir une constance dans l’effort collectif\",
    \"Suivre des processus très rigides\"
  ],
  \"style_relationnel\": {
    \"dominant\": [\"I – Spontané / Enthousiaste\"],
    \"teinte\": [\"C – Méthodique / Analytique\"],
    \"moins_present\": [\"D – Directif\", \"S – Stable / Coopératif\"]
  },
  \"recommandations_collaboratives\": [
    \"S’entourer de profils structurants et cadrants\",
    \"Collaborer avec des personnes capables de trancher ou recadrer\",
    \"Valoriser ses idées sans négliger la mise en œuvre concrète\"
  ]
}");

		
		// Message tout à la fin, après l'historique des messages
		//$system_param2[]=array('role' => 'assistant', 'content' => '');

		$params  = array(
		
			"model" => $model,
			'response_format' => [ "type" => "json_object" ],

			'messages' => array_merge($system_param,$query,$system_param2),
			'temperature' => 0.7,
			'frequency_penalty' => 1,
			'presence_penalty' => 1,
			'repetition_penalty' =>1 // Repetition of the tocken...
		
		
		);



		$options = array(
			'http' => array(
				'header'  => "Authorization: Bearer $api_key\r\n" .
							 "HTTP-Referer: $site_url\r\n" .
							 "Content-Type: application/json\r\n",
				'method'  => 'POST',
				'content' => json_encode($params),
			),
		);

		// Créez le contexte HTTP
		$context  = stream_context_create($options);

		// Faites la requête HTTP à l'API
		$api_url = "https://openrouter.ai/api/v1/chat/completions";
		$response = file_get_contents($api_url, true, $context);
		$response = mb_convert_encoding($response, 'UTF-8', 'auto');

		// Si la requête a réussi, décodez la réponse JSON
		if ($response !== false) {
			$responseData = json_decode($response, true);
			//print_r($responseData);
			//print_r($responseData['choices'][0]['message']['content']);
			
			// extraction des données
			$content = $responseData['choices'][0]['message']['content'];

			// Étape 1 : extraire le bloc JSON entre les backticks ```json
			if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
				$jsonClean = $matches[1];
				
			} else {
				$jsonClean=$content;
				
			}

			//echo $jsonClean."\n\n";

			// Étape 2 : nettoyer les guillemets typographiques
			//$jsonClean = str_replace(['“','”','«','»','”','“'],'"',	$jsonClean);
			//$jsonClean = str_replace(['„','‘','’','’'],"'",	$jsonClean);
			//$jsonClean = preg_replace('/[[:cntrl:]]/', '', $jsonClean);

			// Corriger les doubles guillemets superflus autour des chaînes
			//$jsonClean = preg_replace('/\\\"/', '"', $jsonClean);
			//$jsonClean = preg_replace('/[^\x20-\x7E\xC0-\xFF]/u', ' ', $jsonClean);

			
			// Étape 3 : décoder le JSON en tableau associatif
			$data = json_decode($jsonClean, true);

			if (json_last_error() !== JSON_ERROR_NONE) {
				echo $jsonClean."<hr>";
				die("Erreur JSON : " . json_last_error_msg());
			}

			
			
		}
		print_r($jsonClean);
		exit;
		
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mon Profil Projet</title>
  <style>
    body { font-family: sans-serif; padding: 20px; max-width: 800px; margin: auto; }
    h2 { color: #336699; }
    ul { padding-left: 20px; }
    .section { margin-bottom: 30px; }
    .key { font-weight: bold; color: #333; }
  </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>

  <h1>🧭 Mon Profil Projet et Relationnel</h1>

  <div class="section">
    <h2>Synthèse</h2>
    <p><?= htmlspecialchars($data['synthese']) ?></p>
    <hr>
    <p><?= htmlspecialchars($data['description']) ?></p>
  </div>

  <div class="section">
    <h2>✅ Ce qui est facile pour moi</h2>
    <ul>
      <?php foreach ($data['ce_qui_est_facile'] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="section">
    <h2>⚠️ Ce qui est plus difficile</h2>
    <ul>
      <?php foreach ($data['ce_qui_est_difficile'] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="section">
    <h2>🔄 Style relationnel</h2>
  <canvas id="discRadarChart" width="400" height="400"></canvas>

  <script>
    const ctx = document.getElementById('discRadarChart').getContext('2d');

    // ✅ Valeurs d'exemple — à remplacer dynamiquement avec PHP/JS
    const discData = {
      labels: ['D – Directif', 'I – Spontané', 'S – Coopératif', 'C – Méthodique'],
      datasets: [{
        label: 'Votre profil DISC',
        data: [2, 5, 3, 9], // <-- à personnaliser selon ton analyse
        fill: true,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
      }]
    };

    new Chart(ctx, {
      type: 'radar',
      data: discData,
      options: {
        responsive: true,
        scales: {
          r: {
            suggestedMin: 0,
            suggestedMax: 5,
            ticks: {
              stepSize: 1,
              backdropColor: 'transparent'
            },
            pointLabels: {
              font: {
                size: 14
              }
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          title: {
            display: false
          }
        }
      }
    });
  </script>
    <ul>
      <li><span class="key">Dominant :</span> 
       <?= implode(', ', $data['style_relationnel']['dominant']) ?>
       </li>
      <li><span class="key">Teinte secondaire :</span> 
       <?= implode(', ', $data['style_relationnel']['teinte']) ?>
       </li>
      <li><span class="key">Moins présent :</span>
        <?= implode(', ', $data['style_relationnel']['moins_present']) ?>
      </li>
    </ul>
  </div>

  <div class="section">
    <h2>🎯 Recommandations collaboratives</h2>
    <ul>
      <?php foreach ($data['recommandations_collaboratives'] as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

</body>
</html>
