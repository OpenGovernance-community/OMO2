<?php
require_once __DIR__ . '/config.php';

$apiKey = $GLOBALS['openAiUploadApiKey'];
$fileId = 'file-57XAXCNTJ68Sg9A5Ag6Pky'; // file_id récupéré via le script précédent
$question = "Qu'est-ce que le lien pilotage ?";
$cookieName = 'openai_assistant_id';

// Fonction pour créer un assistant
function createAssistant($apiKey, $fileId) {
    $assistantData = [
        "name" => "Assistant utilisateur",
        "instructions" => "Tu es un assistant expert des documents uploadés. Réponds uniquement avec les infos des fichiers.",
        "model" => "gpt-4-1106-preview",
        "tools" => [
            ["type" => "file_search"]
        ],
        "file_ids" => [$fileId]
    ];

    $ch = curl_init("https://api.openai.com/v1/assistants");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($assistantData),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v2"
        ]
    ]);
    $response = curl_exec($ch);
    print_r ($response);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['id'] ?? null;
}

// Fonction pour vérifier qu'un assistant existe
function checkAssistantExists($apiKey, $assistantId) {
    $ch = curl_init("https://api.openai.com/v1/assistants/$assistantId");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "OpenAI-Beta: assistants=v2"
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    // S'il y a une erreur, l'assistant n'existe pas
    return !isset($data['error']);
}

// Récupérer assistant_id depuis cookie ou créer un nouveau
$assistantId = $_COOKIE[$cookieName] ?? null;

if ($assistantId === null || !checkAssistantExists($apiKey, $assistantId)) {
    $assistantId = createAssistant($apiKey, $fileId);
    if ($assistantId) {
        // Stocker dans un cookie valable 30 jours
        setcookie($cookieName, $assistantId, time() + 86400 * 30, "/");
        echo "Assistant créé et cookie mis à jour : $assistantId<br>";
    } else {
        die("Erreur lors de la création de l'assistant.");
    }
} else {
    echo "Assistant chargé depuis cookie : $assistantId<br>";
}




// 2. Créer un thread
$ch = curl_init('https://api.openai.com/v1/threads');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]
]);
$thread = json_decode(curl_exec($ch), true);
curl_close($ch);
$threadId = $thread['id'];

// 3. Ajouter un message au thread
$ch = curl_init("https://api.openai.com/v1/threads/$threadId/messages");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        "role" => "user",
        "content" => $question
    ])
]);
curl_exec($ch);
curl_close($ch);

// 4. Lancer le run
$ch = curl_init("https://api.openai.com/v1/threads/$threadId/runs");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        "assistant_id" => $assistantId
    ])
]);
$run = json_decode(curl_exec($ch), true);
curl_close($ch);
$runId = $run['id'];

// 5. Attente de la complétion du run (max 30s)
$maxWait = 10;
$elapsed = 0;

do {
    sleep(2);
    $elapsed += 2;

    $ch = curl_init("https://api.openai.com/v1/threads/$threadId/runs/$runId");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey"
        ]
    ]);
    $runStatusResponse = curl_exec($ch);
    curl_close($ch);

    $runStatus = json_decode($runStatusResponse, true);
    $status = $runStatus['status'] ?? 'unknown';

    echo "⏳ Statut du run : $status<br>";

    if (isset($runStatus['last_error'])) {
        echo "❌ Erreur : " . json_encode($runStatus['last_error']);
        exit;
    }

    if ($elapsed >= $maxWait) {
        echo "⏱️ Temps d'attente dépassé. Le run n'a pas terminé.";
        exit;
    }

} while ($status !== 'completed');


// 6. Récupérer la réponse
$ch = curl_init("https://api.openai.com/v1/threads/$threadId/messages");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey"
    ]
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "📣 Réponse :\n";
echo $response['data'][0]['content'][0]['text']['value'];
