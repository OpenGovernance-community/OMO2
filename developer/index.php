<?php
declare(strict_types=1);
// Public documentation never consumes legacy share or organization query context.
$_GET = $_POST = $_REQUEST = [];
require_once dirname(__DIR__) . '/common/api/bootstrap.php';
require_once dirname(__DIR__) . '/common/api/rest.php';
require_once dirname(__DIR__) . '/common/translation_bundles.php';
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    omoMcpJson(['error' => 'method_not_allowed'], 405);
}
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
header('Content-Type: text/html; charset=utf-8');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-ancestors 'none'; base-uri 'none'; form-action 'none'");
$sourceLang = [
    'title' => ['text' => 'API OMO — Documentation développeurs', 'context' => 'Public API reference browser title.'],
    'reference' => ['text' => 'Documentation API', 'context' => 'Developer documentation brand label.'],
    'skip' => ['text' => 'Aller au contenu', 'context' => 'Keyboard accessibility skip link.'],
    'index' => ['text' => 'Index des fonctions', 'context' => 'Documentation navigation heading.'],
    'filter' => ['text' => 'Filtrer les fonctions', 'context' => 'Navigation search field label.'],
    'empty' => ['text' => 'Aucune fonction correspondante.', 'context' => 'Empty navigation search results.'],
    'intro' => ['text' => 'Premiers pas', 'context' => 'Documentation introduction heading.'],
    'lead' => ['text' => 'Connectez vos applications aux organisations, aux membres et aux contenus OMO.', 'context' => 'Developer reference introduction.'],
    'shared' => ['text' => 'REST et MCP utilisent les mêmes opérations, permissions et consentements. Cette référence est générée depuis la spécification OpenAPI ; les descriptions techniques ci-dessous sont en anglais.', 'context' => 'Documentation source and language explanation.'],
    'spec' => ['text' => 'Voir la spécification OpenAPI (JSON)', 'context' => 'Link to the machine-readable API specification.'],
    'base' => ['text' => 'URL de base', 'context' => 'API server URL label.'],
    'auth' => ['text' => 'Connexion OAuth', 'context' => 'Authentication guide heading.'],
    'auth_intro' => ['text' => 'Utilisez Authorization Code avec PKCE S256. La personne choisit une organisation et autorise les scopes ; les permissions OMO continuent de limiter chaque opération. Demandez toujours organization:read, puis ajoutez les scopes des opérations d’écriture souhaitées.', 'context' => 'OAuth guide introduction.'],
    'register' => ['text' => 'Enregistrez votre client avec client_name, redirect_uris et token_endpoint_auth_method: none. Conservez le client_id retourné.', 'context' => 'OAuth dynamic registration instruction.'],
    'authorize' => ['text' => 'Ouvrez cette URL avec response_type=code, client_id, redirect_uri, resource, scope, state, code_challenge et code_challenge_method=S256.', 'context' => 'OAuth authorization instruction.'],
    'exchange' => ['text' => 'Vérifiez state et iss au retour. Échangez le code par un POST application/x-www-form-urlencoded : grant_type=authorization_code, client_id, redirect_uri, resource, code et code_verifier.', 'context' => 'OAuth token exchange instruction.'],
    'bearer' => ['text' => 'Envoyez le jeton dans chaque requête avec Authorization: Bearer ACCESS_TOKEN. Les cookies OMO ne remplacent pas le jeton.', 'context' => 'API bearer authentication instruction.'],
    'resource' => ['text' => 'La ressource OAuth reste celle du MCP, barre oblique finale comprise. Utilisez exactement cette valeur pour resource pendant l’autorisation et l’échange de jeton :', 'context' => 'Canonical OAuth resource requirement.'],
    'refresh' => ['text' => 'Les jetons d’accès expirent après une heure. Renouvelez-les sur la même URL de jeton avec grant_type=refresh_token, client_id, refresh_token et resource. Conservez le nouveau refresh_token à chaque renouvellement. Pour ajouter des scopes, demandez un nouveau consentement.', 'context' => 'OAuth refresh and additional consent instructions.'],
    'conventions' => ['text' => 'Appels et pagination', 'context' => 'Shared request conventions section.'],
    'queries' => ['text' => 'GET : filtres dans l’URL. Les tableaux sont séparés par des virgules (user_ids=1,16), sans syntaxe []. POST et PATCH : objet JSON uniquement, Content-Type: application/json, sans paramètres dans l’URL.', 'context' => 'REST query and body encoding conventions.'],
    'pages' => ['text' => 'Conservez les filtres et suivez next_after_id jusqu’à null, même après une page vide. Les audiences et le texte utilisent next_offset ; les invités peuvent utiliser next_page. La recherche est limitée : utilisez les listes pour une exploration exhaustive.', 'context' => 'Complete pagination guidance.'],
    'retry' => ['text' => 'Pour une création ou un envoi, réutilisez la même request_key et le même contenu après un échec réseau. Cette protection contre les doublons est commune à REST et MCP.', 'context' => 'Idempotent retry guidance.'],
    'cors' => ['text' => 'Pour un client navigateur sur une autre origine, faites autoriser son origine dans MCP_ALLOWED_ORIGINS. Les clients serveur et CLI ne demandent pas de configuration CORS.', 'context' => 'Browser integration origin configuration.'],
    'parameters' => ['text' => 'Paramètres', 'context' => 'Operation parameters heading.'],
    'none' => ['text' => 'Aucun paramètre.', 'context' => 'Parameterless operation label.'],
    'name' => ['text' => 'Nom / emplacement', 'context' => 'Parameter table name and placement column.'],
    'type' => ['text' => 'Type / contraintes', 'context' => 'Parameter table schema column.'],
    'details' => ['text' => 'Description', 'context' => 'Parameter and response table description column.'],
    'required' => ['text' => 'Requis', 'context' => 'Required parameter label.'],
    'optional' => ['text' => 'Facultatif', 'context' => 'Optional parameter label.'],
    'body' => ['text' => 'Corps JSON', 'context' => 'JSON body parameter location.'],
    'default' => ['text' => 'Valeur par défaut', 'context' => 'Schema default value label.'],
    'request' => ['text' => 'Exemple d’appel', 'context' => 'Curl request template heading.'],
    'example_hint' => ['text' => 'Remplacez ACCESS_TOKEN et les valeurs entre accolades, en encodant les paramètres d’URL. Ajoutez les filtres facultatifs selon vos besoins. Pour un POST ou PATCH, préparez request.json selon les paramètres ci-dessus. Aucun appel n’est exécuté par cette page.', 'context' => 'Non-interactive curl example instructions.'],
    'responses' => ['text' => 'Format du retour', 'context' => 'Operation response format heading.'],
    'schema' => ['text' => 'Schéma JSON de la requête', 'context' => 'Expandable raw input schema title.'],
    'scope' => ['text' => 'Scope OAuth', 'context' => 'Operation authorization scope label.'],
    'constraints' => ['text' => 'Contraintes', 'context' => 'Raw schema constraints label.'],
    'functions' => ['text' => 'fonctions', 'context' => 'Label following the total API operation count.'],
    'metadata' => ['text' => 'Métadonnées OAuth', 'context' => 'OAuth server metadata link label.'],
    'revocation' => ['text' => 'Révocation', 'context' => 'OAuth revocation endpoint label.'],
    'response_fields' => ['text' => 'Champs de la réponse', 'context' => 'Response field table title.'],
    'response_schema' => ['text' => 'Schéma JSON de la réponse', 'context' => 'Expandable raw response schema title.'],
    'response_example' => ['text' => 'Exemple de réponse JSON', 'context' => 'Synthetic response example title.'],
    'response_hint' => ['text' => 'Les exemples sont fictifs. Les champs facultatifs dépendent du module, des permissions ou du réessai. « Requis » s’applique à l’objet parent lorsqu’il est présent ; [] représente les éléments d’un tableau. null est une valeur possible, distincte d’un champ absent.', 'context' => 'Response examples, optional fields and nested requiredness explanation.'],
    'response_headers' => ['text' => 'En-têtes de réponse', 'context' => 'HTTP response header documentation title.'],
    'project_conflict' => ['text' => 'Projet modifie depuis sa lecture', 'context' => 'Concurrent project update response heading.'],
    'event_conflict' => ['text' => 'Conflit d’événement : aucun événement créé', 'context' => 'Business response variant requiring explicit confirmation.'],
    'error_format' => ['text' => 'Format des erreurs', 'context' => 'Shared error envelope documentation title.'],
    'error_hint' => ['text' => 'Une erreur renvoie error et, lorsqu’elle est disponible, error_description. required_scope indique un consentement OAuth manquant. Les conflits d’événement ont un retour spécifique décrit dans la fonction de création.', 'context' => 'Shared error fields and exceptional business response explanation.'],
];
$lang = loadTranslationBundle('developer_api', commonAuthGetTranslationLocale(), $sourceLang);
$tr = static fn (string $key): string => t($key, [], $lang, $sourceLang);
$escape = static fn (mixed $text): string => htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$json = static fn (mixed $value): string => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$spec = omoRestOpenApi();
$baseUrl = $spec['servers'][0]['url'];
$oauth = $spec['components']['securitySchemes']['oauth2'];
$flow = $oauth['flows']['authorizationCode'];
$operations = [];
foreach ($spec['paths'] as $path => $methods) {
    foreach ($methods as $method => $operation) $operations[] = ['path' => $path, 'method' => strtoupper($method)] + $operation;
}
/** Flatten nested response objects for reading; the OpenAPI contract remains the only schema source. */
function developerResponseFields(array $schema, array $schemas, string $prefix = ''): array
{
    if (isset($schema['$ref'])) $schema = array_replace($schemas[basename($schema['$ref'])], array_diff_key($schema, ['$ref' => true]));
    foreach (['anyOf', 'oneOf'] as $keyword) {
        if (isset($schema[$keyword])) return developerResponseFields($schema[$keyword][0], $schemas, $prefix);
    }
    if (($schema['type'] ?? null) === 'array' && isset($schema['items'])) return developerResponseFields($schema['items'], $schemas, $prefix . '[]');
    $rows = [];
    foreach ($schema['properties'] ?? [] as $name => $rule) {
        $resolved = isset($rule['$ref']) ? array_replace($schemas[basename($rule['$ref'])], array_diff_key($rule, ['$ref' => true])) : $rule;
        $types = (array)($resolved['type'] ?? []);
        foreach ($resolved['anyOf'] ?? $resolved['oneOf'] ?? [] as $variant) {
            if (isset($variant['$ref'])) $variant = $schemas[basename($variant['$ref'])];
            $types = [...$types, ...(array)($variant['type'] ?? 'object')];
        }
        if (($resolved['type'] ?? null) === 'array') {
            $item = $resolved['items'] ?? [];
            if (isset($item['$ref'])) $item = $schemas[basename($item['$ref'])];
            $types = ['array<' . implode(' | ', (array)($item['type'] ?? 'object')) . '>'];
        }
        $path = $prefix === '' ? $name : $prefix . '.' . $name;
        $rows[] = ['name' => $path, 'type' => implode(' | ', array_unique($types)),
            'required' => in_array($name, $schema['required'] ?? [], true), 'schema' => $resolved];
        $rows = [...$rows, ...developerResponseFields($resolved, $schemas, $path)];
    }
    return $rows;
}
?>
<!doctype html>
<html lang="<?= $escape(commonAuthGetTranslationLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($tr('title')) ?></title>
    <?= commonStylesheetTags('/common/assets/components.css') ?>
    <?= commonStylesheetTags('/developer/developer.css') ?>
    <script src="<?= $escape(commonAssetUrl('/developer/developer.js')) ?>" defer></script>
</head>
<body class="generic-public-page">
<a class="developer-skip" href="#content"><?= $escape($tr('skip')) ?></a>
<div class="generic-page-shell developer-layout">
    <aside class="developer-sidebar">
        <a class="generic-public-brand" href="/developer/">
            <img class="generic-public-brand__logo" src="<?= $escape(commonAssetUrl('/img/omo2/logo-omo-dark.png')) ?>" alt="OpenMyOrganization" width="1076" height="332">
            <span class="generic-public-brand__label"><?= $escape($tr('reference')) ?> · v<?= $escape($spec['info']['version']) ?></span>
        </a>
        <details class="generic-accordion developer-index" open>
            <summary><?= $escape($tr('index')) ?></summary>
            <nav aria-label="<?= $escape($tr('index')) ?>">
                <div class="developer-index-tools" hidden>
                    <label for="function-filter"><?= $escape($tr('filter')) ?></label>
                    <input class="generic-form-control" type="search" id="function-filter" autocomplete="off">
                </div>
                <a href="#overview" class="developer-nav-link"><?= $escape($tr('intro')) ?></a>
                <a href="#authentication" class="developer-nav-link"><?= $escape($tr('auth')) ?></a>
                <a href="#conventions" class="developer-nav-link"><?= $escape($tr('conventions')) ?></a>
                <div class="developer-function-links">
                <?php foreach ($operations as $operation): ?>
                    <a class="developer-nav-link" href="#<?= $escape($operation['operationId']) ?>" data-function="<?= $escape($operation['operationId'] . ' ' . $operation['path'] . ' ' . $operation['summary']) ?>">
                        <span class="developer-route"><span class="developer-method developer-method--<?= strtolower($operation['method']) ?>"><?= $escape($operation['method']) ?></span><span><?= $escape($operation['path']) ?></span></span>
                        <span class="developer-nav-title"><?= $escape($operation['summary']) ?></span>
                    </a>
                <?php endforeach; ?>
                </div>
                <p id="function-empty" role="status" hidden><?= $escape($tr('empty')) ?></p>
            </nav>
        </details>
    </aside>
    <main id="content" class="developer-content" tabindex="-1">
        <section id="overview" class="generic-hero-panel developer-section" tabindex="-1">
            <span class="generic-card-title generic-card-title--eyebrow">API REST · <?= count($operations) ?> <?= $escape($tr('functions')) ?></span>
            <h1 class="generic-card-title generic-card-title--display">API OpenMyOrganization</h1>
            <p><?= $escape($tr('lead')) ?></p>
            <p><?= $escape($tr('shared')) ?></p>
            <div class="developer-base"><span><?= $escape($tr('base')) ?></span><code><?= $escape($baseUrl) ?></code></div>
            <a class="generic-action-button generic-action-button--secondary" href="/api/v1/openapi.json"><?= $escape($tr('spec')) ?></a>
        </section>
        <section id="authentication" class="generic-section generic-section--stack developer-section" tabindex="-1">
            <h2 class="generic-card-title generic-card-title--section"><?= $escape($tr('auth')) ?></h2>
            <p><?= $escape($tr('auth_intro')) ?></p>
            <ol class="developer-steps">
                <li><code>POST <?= $escape($oauth['x-registration-url']) ?></code><p><?= $escape($tr('register')) ?></p></li>
                <li><code>GET <?= $escape($flow['authorizationUrl']) ?></code><p><?= $escape($tr('authorize')) ?></p></li>
                <li><code>POST <?= $escape($flow['tokenUrl']) ?></code><p><?= $escape($tr('exchange')) ?></p></li>
                <li><?= $escape($tr('bearer')) ?></li>
            </ol>
            <div class="generic-soft-panel generic-soft-panel--stack"><p><?= $escape($tr('resource')) ?></p><code><?= $escape($oauth['x-oauth-resource']) ?></code></div>
            <dl class="developer-scopes">
                <?php foreach ($flow['scopes'] as $scope => $description): ?><div><dt><code><?= $escape($scope) ?></code></dt><dd><?= $escape($description) ?></dd></div><?php endforeach; ?>
            </dl>
            <p><?= $escape($tr('refresh')) ?></p>
            <p><?= $escape($tr('metadata')) ?> : <a href="/.well-known/oauth-authorization-server"><code>/.well-known/oauth-authorization-server</code></a><br><?= $escape($tr('revocation')) ?> : <code>POST <?= $escape($oauth['x-revocation-url']) ?></code></p>
        </section>
        <section id="conventions" class="generic-section generic-section--stack developer-section" tabindex="-1">
            <h2 class="generic-card-title generic-card-title--section"><?= $escape($tr('conventions')) ?></h2>
            <?php foreach (['queries', 'pages', 'retry', 'cors'] as $key): ?><p><?= $escape($tr($key)) ?></p><?php endforeach; ?>
            <details class="generic-accordion generic-accordion--inset"><summary><?= $escape($tr('error_format')) ?></summary>
                <div class="generic-section generic-section--plain generic-section--stack generic-section--roomy">
                    <p><?= $escape($tr('error_hint')) ?></p>
                    <pre class="developer-code"><code><?= $escape($json((object)['error' => 'invalid_request', 'error_description' => 'Invalid query parameter.'])) ?></code></pre>
                    <details class="generic-accordion"><summary><?= $escape($tr('response_schema')) ?></summary><pre><?= $escape($json($spec['components']['schemas']['Error'])) ?></pre></details>
                </div>
            </details>
        </section>
        <?php foreach ($operations as $operation):
            $inputSchema = $operation['requestBody']['content']['application/json']['schema'] ?? null;
            $parameters = $operation['parameters'] ?? [];
            if ($inputSchema !== null) {
                foreach ((array)$inputSchema['properties'] as $name => $schema) $parameters[] = ['name' => $name, 'in' => 'body', 'required' => in_array($name, $inputSchema['required'] ?? [], true), 'schema' => $schema];
            }
            $query = [];
            foreach ($parameters as $parameter) {
                if ($parameter['in'] === 'query' && $parameter['required']) $query[] = $parameter['name'] . '={' . $parameter['name'] . '}';
            }
            $curl = 'curl --fail-with-body -i ' . "'" . $baseUrl . $operation['path']
                . ($query ? '?' . implode('&', $query) : '') . "'" . " \\\n  -H 'Authorization: Bearer ACCESS_TOKEN'";
            if ($operation['method'] === 'PATCH') $curl .= ' -X PATCH';
            if (in_array($operation['method'], ['POST', 'PATCH'], true)) $curl .= " \\\n  -H 'Content-Type: application/json' \\\n  --data-binary @request.json";
        ?>
        <section id="<?= $escape($operation['operationId']) ?>" class="generic-section generic-section--stack developer-section developer-operation" tabindex="-1" aria-labelledby="<?= $escape($operation['operationId']) ?>-title">
            <div class="developer-route developer-route--heading"><span class="developer-method developer-method--<?= strtolower($operation['method']) ?>"><?= $escape($operation['method']) ?></span><code>/api/v1<?= $escape($operation['path']) ?></code></div>
            <h2 id="<?= $escape($operation['operationId']) ?>-title" class="generic-card-title generic-card-title--section"><?= $escape($operation['summary']) ?></h2>
            <div class="developer-operation-meta"><code><?= $escape($operation['operationId']) ?></code><span><?= $escape($tr('scope')) ?> : <code><?= $escape(implode(', ', $operation['security'][0]['oauth2'])) ?></code></span></div>
            <p lang="en"><?= $escape($operation['description']) ?></p>
            <h3 class="generic-card-title generic-card-title--medium"><?= $escape($tr('parameters')) ?></h3>
            <?php if (!$parameters): ?><p><?= $escape($tr('none')) ?></p><?php else: ?>
            <div class="developer-table-wrap" tabindex="0" role="region" aria-label="<?= $escape($operation['summary'] . ': ' . $tr('parameters')) ?>">
                <table class="developer-table"><thead><tr><th scope="col"><?= $escape($tr('name')) ?></th><th scope="col"><?= $escape($tr('type')) ?></th><th scope="col"><?= $escape($tr('details')) ?></th></tr></thead><tbody>
                <?php foreach ($parameters as $parameter):
                    $schema = $parameter['schema'];
                    $constraints = array_diff_key($schema, array_flip(['type', 'description', 'default', 'properties', 'required', 'additionalProperties', 'items']));
                ?>
                    <tr><th scope="row"><code><?= $escape($parameter['name']) ?></code><small><?= $escape($parameter['in'] === 'body' ? $tr('body') : $parameter['in']) ?> · <?= $escape($tr($parameter['required'] ? 'required' : 'optional')) ?></small></th>
                        <td><code><?= $escape($schema['type'] . ($schema['type'] === 'array' ? '<' . ($schema['items']['type'] ?? 'object') . '>' : '')) ?></code>
                            <?php if ($constraints): ?><pre><?= $escape($json($constraints)) ?></pre><?php endif; ?>
                            <?php if (array_key_exists('default', $schema)): ?><small><?= $escape($tr('default')) ?>: <code><?= $escape($json($schema['default'])) ?></code></small><?php endif; ?>
                        </td>
                        <td lang="en"><?= $escape($schema['description'] ?? '') ?>
                            <?php if (isset($schema['properties']) || isset($schema['items'])): ?><details class="generic-accordion"><summary><?= $escape($tr('constraints')) ?></summary><pre><?= $escape($json($schema)) ?></pre></details><?php endif; ?>
                        </td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>
            <?php endif; ?>
            <?php if ($inputSchema !== null): ?><details class="generic-accordion generic-accordion--inset"><summary><?= $escape($tr('schema')) ?></summary><pre><?= $escape($json($inputSchema)) ?></pre></details><?php endif; ?>
            <h3 class="generic-card-title generic-card-title--medium"><?= $escape($tr('request')) ?></h3>
            <pre class="developer-code"><code><?= $escape($curl) ?></code></pre>
            <p class="developer-hint"><?= $escape($tr('example_hint')) ?></p>
            <h3 class="generic-card-title generic-card-title--medium"><?= $escape($tr('responses')) ?></h3>
            <p class="developer-hint"><?= $escape($tr('response_hint')) ?></p>
            <?php $primaryStatus = isset($operation['responses'][201]) ? 201 : 200;
            foreach (array_intersect_key($operation['responses'], array_flip([$primaryStatus, 409])) as $status => $response):
                $responseMedia = $response['content']['application/json'];
                $responseSchema = $spec['components']['schemas'][basename($responseMedia['schema']['$ref'])];
                $responseFields = developerResponseFields($responseSchema, $spec['components']['schemas']);
            ?>
            <?php if ($status === 409): ?><details class="generic-accordion generic-accordion--inset"><summary><?= $escape($tr($operation['operationId'] === 'omo_update_project' ? 'project_conflict' : 'event_conflict')) ?></summary><?php endif; ?>
                <div class="generic-section generic-section--plain generic-section--stack generic-section--roomy">
                    <?php if (!empty($response['headers'])): ?>
                        <h4 class="generic-card-title generic-card-title--small"><?= $escape($tr('response_headers')) ?></h4>
                        <dl class="developer-scopes"><?php foreach ($response['headers'] as $header => $definition): ?><div><dt><code><?= $escape($header) ?></code></dt><dd lang="en"><?= $escape($definition['description']) ?></dd></div><?php endforeach; ?></dl>
                    <?php endif; ?>
                    <h4 class="generic-card-title generic-card-title--small"><?= $escape($tr('response_fields')) ?></h4>
                    <div class="developer-table-wrap" tabindex="0" role="region" aria-label="<?= $escape($operation['summary'] . ' ' . $status . ' : ' . $tr('response_fields')) ?>">
                        <table class="developer-table"><thead><tr><th scope="col"><?= $escape($tr('name')) ?></th><th scope="col"><?= $escape($tr('type')) ?></th><th scope="col"><?= $escape($tr('details')) ?></th></tr></thead><tbody>
                        <?php foreach ($responseFields as $field): ?>
                            <tr><th scope="row"><code><?= $escape($field['name']) ?></code><small><?= $escape($tr($field['required'] ? 'required' : 'optional')) ?></small></th>
                                <td><code><?= $escape($field['type']) ?></code><?php if (isset($field['schema']['format'])): ?><small><?= $escape($field['schema']['format']) ?></small><?php endif; ?>
                                    <?php foreach (['const', 'enum', 'pattern'] as $constraint): ?><?php if (array_key_exists($constraint, $field['schema'])): ?><small><code><?= $escape($constraint . ': ' . $json($field['schema'][$constraint])) ?></code></small><?php endif; ?><?php endforeach; ?>
                                </td><td lang="en"><?= $escape($field['schema']['description'] ?? '') ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div>
                    <h4 class="generic-card-title generic-card-title--small"><?= $escape($tr('response_example')) ?></h4>
                    <pre class="developer-code"><code><?= $escape($json($responseMedia['example'])) ?></code></pre>
                    <details class="generic-accordion"><summary><?= $escape($tr('response_schema')) ?></summary><pre><?= $escape($json($responseSchema)) ?></pre></details>
                </div>
            <?php if ($status === 409): ?></details><?php endif; ?>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>
    </main>
</div>
</body>
</html>
