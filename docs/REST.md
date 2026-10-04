# API REST OMO v1

L interface REST expose les 17 operations du MCP sous `/api/v1`.
Elle utilise le meme registre, la meme validation et les memes services dbObject.
Les droits, la pagination, les consentements OAuth et les protections contre les
doublons sont identiques. Aucune nouvelle table ni cle API permanente n est ajoutee.

## Demarrage

- Decouverte publique : `GET /api/v1`
- Description OpenAPI 3.1, importable dans Postman : `GET /api/v1/openapi.json`
- Documentation publique dans le navigateur : `/developer/`, generee en PHP depuis
  la meme specification OpenAPI, avec index, filtres et liens directs par fonction.
- Chaque operation decrit son format de retour : champs requis/facultatifs,
  tableaux et objets imbriques, valeurs nullables et exemples JSON fictifs.
  Les schemas reutilisables se trouvent dans `components.schemas` d OpenAPI.
  La page presente les formats directement et distingue le conflit d evenement
  (`created: false`, `requires_confirmation: true`) ; les erreurs communes sont
  documentees une seule fois. Les codes HTTP restent complets dans OpenAPI.
- Toutes les operations metier demandent `Authorization: Bearer ACCESS_TOKEN`.
- Les GET prennent leurs filtres dans la query string ; les POST prennent un objet
  JSON avec `Content-Type: application/json`, sans parametres de query string.
- Les resultats sont directement des objets JSON, sans enveloppe JSON-RPC.

Apres publication sur Dev, la specification sera disponible a
`https://dev.opengov.tools/api/v1/openapi.json`.
Apache doit appliquer le `.htaccess` racine et transmettre `Authorization`.
Un autre proxy doit router `/api/v1` et ses sous-chemins vers `api/v1/index.php`,
en conservant l URI et le corps de requete, sans rediriger les POST.

## Connexion OAuth

REST et MCP sont deux interfaces de la **meme ressource OAuth**. Le parametre
`resource` reste exactement la valeur de `MCP_PUBLIC_URL`, slash final compris :
sur Dev, `https://dev.opengov.tools/mcp/`. Ne pas utiliser `/api/v1` comme resource.
Un jeton deja emis pour cette ressource fonctionne sur les deux interfaces.
L organisation est celle choisie pendant le consentement ; le client ne peut pas
la changer dans un appel REST. Pour une autre organisation, faire un autre consentement.

Le client REST utilise le parcours existant Authorization Code avec PKCE S256 :

1. Enregistrer son client par `POST /mcp/register.php` avec `client_name`,
   `redirect_uris` (les URL HTTPS exactes du client) et
   `token_endpoint_auth_method: "none"`. Conserver le `client_id` retourne.
2. Ouvrir `/mcp/authorize.php` dans le navigateur avec `response_type=code`,
   `client_id`, `redirect_uri`, `resource`, `scope`, un `state` aleatoire,
   `code_challenge_method=S256` et le `code_challenge` derive du `code_verifier`.
   La personne se connecte a OMO, choisit son organisation et accepte les droits.
3. Verifier `state` et `iss` au retour, puis echanger le `code` par un POST
   `application/x-www-form-urlencoded` vers `/mcp/token.php` avec
   `grant_type=authorization_code`, `client_id`, `redirect_uri`, `resource`,
   `code` et `code_verifier`. Aucun secret client n est requis.
4. Envoyer l `access_token` dans le header Bearer de chaque appel REST.

La configuration OAuth est aussi disponible dans
`/.well-known/oauth-authorization-server`. Dans Postman, configurer Authorization
Code avec PKCE, les URL ci-dessus, le client ID enregistre, les scopes choisis et
le parametre additionnel `resource` pour l autorisation et l echange de jeton.

Scopes :

| Scope | Acces |
| --- | --- |
| `organization:read` | Requis pour toutes les operations de lecture |
| `documents:create` | Creation de Memos, liens et fichiers |
| `events:create` | Creation d evenements |
| `mail:send` | Envoi aux destinataires references dans OMO |

Les scopes d ecriture s ajoutent a `organization:read`. Ils n accordent jamais
plus de droits que ceux du compte OMO.

Les jetons d acces durent une heure. Pour renouveler, appeler `/mcp/token.php`
avec `grant_type=refresh_token`, `client_id`, `refresh_token` et le meme `resource`.
Conserver le nouveau refresh token : il tourne a chaque renouvellement, et la
reutilisation d un ancien refresh token revoque la connexion. Le renouvellement
ne peut pas ajouter de scopes ; un consentement supplementaire est necessaire.
La revocation utilise `/mcp/revoke.php` ou la page OMO `/mcp/connections.php`.

Les cookies de session, les liens de partage et les jetons dans l URL ne sont
pas acceptes comme authentification. Les clients navigateur d une autre origine
doivent etre autorises par la configuration existante `MCP_ALLOWED_ORIGINS`.
Un client serveur ou CLI n a pas besoin de CORS.

## Routes

Les schemas complets et limites viennent du registre partage dans OpenAPI.

| Methode | Route | Operation MCP equivalente |
| --- | --- | --- |
| GET | `/connection` | `omo_connection_info` |
| GET | `/catalog` | `omo_catalog` |
| GET | `/structure` | `omo_list_structure` |
| GET | `/structure/{holon_id}` | `omo_get_holon` |
| GET | `/members/{user_id}` | `omo_get_member` |
| GET | `/records/{module}` | `omo_list_records` |
| GET | `/records/{module}/{record_id}` | `omo_read_record` |
| GET | `/assignments` | `omo_list_assignments` |
| GET | `/search` | `omo_search` |
| GET | `/objects/{object_type}/{object_id}/members` | `omo_list_object_members` |
| GET | `/document-spaces` | `omo_list_document_spaces` |
| POST | `/documents` | `omo_create_document` |
| GET | `/event-spaces` | `omo_list_event_spaces` |
| GET | `/availability` | `omo_get_availability` |
| POST | `/events` | `omo_create_event` |
| POST | `/emails` | `omo_send_object_email` |
| GET | `/emails/{mail_id}` | `omo_object_email_status` |

Toutes ces routes sont relatives a `/api/v1`. Les identifiants places dans le
chemin ne doivent pas etre repetes dans les parametres. Les tableaux GET sont
separes par des virgules (`user_ids=1,16`, `modules=documents,calendar`), sans
syntaxe `[]`. Les tableaux JSON POST restent des tableaux JSON ordinaires.
Les parametres inconnus, dupliques ou de type incorrect sont refuses.

Conserver les filtres entre les pages. Pour les listes, suivre `next_after_id`
jusqu a `null`, meme apres une page vide. Pour les audiences et le texte des
fiches, suivre `next_offset`. La recherche est bornee ; preferer `/records/{module}`
pour les listes exhaustives. Les collections d invites peuvent avoir leur propre
pagination `next_page`, identique au MCP.

## Exemples curl

Exemples pour un shell POSIX ; remplacer les identifiants par ceux retournes par
la decouverte. `OMO_TOKEN` contient un jeton OAuth valide, jamais un mot de passe.

```sh
curl --fail-with-body https://dev.opengov.tools/api/v1/connection \
  -H "Authorization: Bearer $OMO_TOKEN"

curl --fail-with-body 'https://dev.opengov.tools/api/v1/records/documents?limit=20' \
  -H "Authorization: Bearer $OMO_TOKEN"

curl --fail-with-body 'https://dev.opengov.tools/api/v1/document-spaces?kind=holons' \
  -H "Authorization: Bearer $OMO_TOKEN"
```

Pour un Memo, enregistrer ceci dans `memo.json` en adaptant `holon_id` :

```json
{
  "title": "Note via REST",
  "holon_id": 429,
  "content_format": "html",
  "content": "<h2>Objectif</h2><p>Contenu du Memo.</p>",
  "visibility_type": "self",
  "request_key": "memo-rest-premier-essai-001"
}
```

```sh
curl --fail-with-body -i https://dev.opengov.tools/api/v1/documents \
  -H "Authorization: Bearer $OMO_TOKEN" \
  -H 'Content-Type: application/json' --data-binary @memo.json
```

Une nouvelle creation retourne 201, `created: true`, `record.record_id` et un
header `Location` vers la fiche REST. Relire cette URL pour verifier le contenu.
La visibilite par defaut reste privee. Les formats texte, HTML, Markdown, liens
externes et fichiers distants suivent les memes regles que le [MCP](MCP.md).
Le champ `file` prend l objet `download_url`, `file_id` et les metadonnees
facultatives ; ce premier transport ne propose pas de formulaire multipart.

Pour les e-mails : lire `/objects/event/1162/members`, verifier `can_send`,
`mail_authorized` et `recipient_count`, puis POSTer vers `/emails` le sujet,
le message, `object_type`, `object_id`, le `audience_token` retourne et une
`request_key`. Si `user_ids` est utilise pour une audience d organisation,
garder la meme selection dans l apercu et l envoi. Les protections et limites
d envoi du MCP restent actives ; aucune liste libre d adresses n est acceptee.

## Resultats et erreurs

| HTTP | Signification |
| --- | --- |
| 200 | Lecture, reessai d une creation existante, ou envoi termine |
| 201 | Nouveau document ou evenement sauvegarde |
| 202 | E-mail accepte, livraisons encore en cours ; suivre `Location` |
| 400 | JSON, query string ou parametres invalides ; corps limite a 1 Mio |
| 401 | Bearer absent, invalide, expire, revoque ou appartenance retiree |
| 403 | Scope manquant (`WWW-Authenticate`) ou origine navigateur refusee |
| 404 | Route inconnue |
| 405 | Methode non prise en charge ; voir `Allow` |
| 409 | Conflits d evenement : `created: false`, `requires_confirmation: true` |
| 415 | Un POST doit utiliser `application/json` |
| 422 | Operation refusee par les droits ou les regles metier, objet indisponible ou cle reutilisee avec un autre contenu |
| 503 | Service ou configuration serveur indisponible |

Les erreurs renvoient `error` et `error_description`, sans details SQL ni secrets.
Pour les evenements, le 409 contient le resultat metier avec ses avertissements :
demander confirmation avant de renvoyer `allow_conflicts: true`.
Un envoi termine peut contenir des echecs ; toujours lire les compteurs de livraison.

Utiliser une `request_key` unique par creation/envoi voulu, puis la conserver avec
le meme contenu lors des reessais. Le meme registre de demandes sert a REST et
MCP : un reessai sur l autre interface retrouve le document, l evenement ou
l envoi existant. Un echec reseau n est pas une preuve d absence de sauvegarde.

## Maintenance et verification

- Registre, schemas, validation et execution : `common/api/operations.php`.
- Bootstrap et authentification Bearer communs : `common/api/bootstrap.php`.
- Correspondance des routes et generation OpenAPI : `common/api/rest.php`.
- Contrats de reponse partages avec la reference publique : `common/api/responses.php`.
- Adapteurs : `api/v1/index.php` pour REST, `common/mcp/server.php` pour MCP.
- Requetes SQL et regles metier : classes existantes dans `class/dbobject/`.

Les anciens noms PHP `omoMcpTools` et `omoMcpToolArguments` sont conserves pour
compatibilite ; leurs implementations sont communes. Aucun appel HTTP interne
de REST vers MCP n est necessaire.

```sh
docker compose exec -T app php tests/rest_http_test.php
docker compose exec -T app php tests/mcp_protocol_test.php
docker compose exec -T app php tests/mcp_http_test.php --mailpit
```

Le test REST utilise uniquement Docker, des fixtures supprimees en fin de test,
et verifie les 17 routes, la parite MCP, la sauvegarde/relecture, les reessais
entre interfaces, les scopes, les permissions, les cookies et la revocation.
Il n envoie aucun e-mail.
