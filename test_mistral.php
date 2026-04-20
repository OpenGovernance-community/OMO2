

<?php

$apiKey = 'DS7aRCHZm5eejJHBQNCcPloDFNDLnqj4'; // Remplace avec ta clé
$url = 'https://api.mistral.ai/v1/chat/completions'; // URL de l'API Mistral

$data = [
    'model' => 'mistral-tiny', // ou mistral-small, mistral-medium, etc.
    'messages' => [
        ['role' => 'user', 'content' => 'Bonjour, que peux-tu faire ? Peux-tu me répondre dans un format json, avec les 3 rubriques suivantes: "resume" pour un rapide resumé, "detail" pour une description plus longue sous forme de liste à puces, et "motcle" pour 3 à 5 mots clés pertinents?']
    ],
    'temperature' => 0.7
];

$headers = [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Erreur Curl : ' . curl_error($ch);
} else {
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        echo "Réponse du modèle :\n";
        echo $result['choices'][0]['message']['content'];
    } else {
        echo "Erreur API (HTTP $httpCode) :\n$response";
    }
}

curl_close($ch);
?>
