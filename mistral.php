<?php
require_once __DIR__ . '/config.php';

$apiKey = $GLOBALS['openAiUploadApiKey'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $filepath = $_FILES['file']['tmp_name'];
        $filename = $_FILES['file']['name'];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.openai.com/v1/files",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $apiKey"
            ],
            CURLOPT_POSTFIELDS => [
                "purpose" => "assistants",
                "file" => new CURLFile($filepath, mime_content_type($filepath), $filename)
            ]
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode !== 200) {
            $result = "❌ Erreur lors de l'upload : $response";
        } else {
            $data = json_decode($response, true);
            $result = "✅ Fichier envoyé avec succès !<br><strong>file_id :</strong> " . $data['id'];
        }
    } else {
        $result = "❌ Aucun fichier sélectionné.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Uploader un document vers OpenAI</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; }
        form { padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        input[type=file] { margin-bottom: 10px; }
        .result { margin-top: 20px; padding: 10px; background: #f9f9f9; border-left: 4px solid #007bff; }
    </style>
</head>
<body>

<h2>📤 Uploader un document vers OpenAI</h2>

<form method="POST" enctype="multipart/form-data">
    <label for="file">Choisis un document (PDF, TXT...)</label><br>
    <input type="file" name="file" required><br><br>
    <button type="submit">Envoyer à OpenAI</button>
</form>

<?php if (isset($result)): ?>
    <div class="result"><?= $result ?></div>
<?php endif; ?>

</body>
</html>
