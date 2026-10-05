# API REST OMO v1

L interface REST expose les 23 operations du MCP sous `/api/v1`.
Elle utilise le meme registre, la meme validation et les memes services dbObject.
Les droits, la pagination, les consentements OAuth et les protections contre les
doublons sont identiques. Les tables de suivi des ecritures sont partagees avec MCP ;
aucune cle API permanente n est ajoutee.

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
- Les GET prennent leurs filtres dans la query string ; les POST et PATCH prennent un objet
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
| `decisions:create` | Creation de scrutins et reservations provisoires des propositions datees |
| `projects:write` | Creation et modification de projets selon les droits OMO |
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
| GET | `/decision-spaces` | `omo_list_decision_spaces` |
| POST | `/decisions` | `omo_create_decision` |
| GET | `/project-spaces` | `omo_list_project_spaces` |
| GET | `/projects/{project_id}` | `omo_get_project` |
| POST | `/projects` | `omo_create_project` |
| PATCH | `/projects/{project_id}` | `omo_update_project` |
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
`mail_authorized` et `recipient_count`. L agent presente ensuite les destinataires,
leur nombre, le sujet et le message integral (liens inclus), adapte au public,
et attend la validation explicite de l utilisateur. Apres validation, POSTer vers
`/emails` le sujet et le texte approuves, `object_type`, `object_id`, le `audience_token` retourne et une
`request_key`. Si `user_ids` est utilise pour une audience d organisation,
garder la meme selection dans l apercu et l envoi. Les protections et limites
d envoi du MCP restent actives ; aucune liste libre d adresses n est acceptee.

Ne pas envoyer ni mettre en file un brouillon en attente de validation. Si le
contenu ou les destinataires changent, presenter la version revisee pour validation.
OAuth et `audience_token` ne valident pas le texte. Une validation deja obtenue
pour le contenu exact et les destinataires reste valable pour l envoi et ses
reessais identiques, avec la meme `request_key`.

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
et verifie les 19 routes, la parite MCP, la sauvegarde/relecture, les reessais
entre interfaces, les scopes, les permissions, les cookies et la revocation.
Il n envoie aucun e-mail.

## Organiser une reunion par scrutin

Pour partager une page personnelle de prise de rendez-vous plutot qu organiser un
scrutin, voir la section [Lien personnel de rendez-vous](#lien-personnel-de-rendez-vous).

Parcours commun REST/MCP :

1. Resoudre les membres avec `omo_list_records` (module `team`), ou lister les
   membres du groupe avec `omo_list_object_members` (type `holon`).
2. Appeler `omo_get_availability` avec ces IDs (maximum 20 membres, 31 jours).
   Choisir trois intervalles communs, decoupes a la duree de la reunion.
   `incomplete=true` signifie que certaines disponibilites ne sont pas verifiees.
3. Decouvrir une destination avec `omo_list_decision_spaces`. Le contexte
   organisation utilise `holon_id=0`, les autres espaces utilisent leur ID.
4. Creer le scrutin et ses propositions avec `omo_create_decision`. Les trois
   propositions datees deviennent des evenements `option` (ICS TENTATIVE).
   Ne pas creer trois autres evenements avec `omo_create_event`.
5. Previsualiser les destinataires avec `omo_list_object_members`, type
   `decision`, ID du scrutin. Faire valider le sujet et le texte integral,
   puis envoyer le message approuve avec
   `omo_send_object_email`, le jeton `audience_token` et le lien du scrutin.
   `mail:send` reste une autorisation distincte. Creation et invitations seules
   n envoient aucun e-mail.

Exemple REST : `POST /api/v1/decisions` (meme JSON en arguments MCP).
Les dates sont des exemples a adapter au rendez-vous reel.

```json
{
  "holon_id": 429,
  "title": "Planifier la prochaine reunion",
  "question": "Quel creneau vous convient ?",
  "method": "majority_judgment",
  "consultation_start_at": "2026-11-02T09:00:00+01:00",
  "consultation_end_at": "2026-11-03T09:00:00+01:00",
  "evaluation_start_at": "2026-11-03T09:00:00+01:00",
  "evaluation_end_at": "2026-11-06T18:00:00+01:00",
  "timezone": "Europe/Zurich",
  "proposals": [
    {"start_at": "2026-11-09T10:00:00+01:00", "end_at": "2026-11-09T11:00:00+01:00"},
    {"start_at": "2026-11-10T14:00:00+01:00", "end_at": "2026-11-10T15:00:00+01:00"},
    {"start_at": "2026-11-12T09:00:00+01:00", "end_at": "2026-11-12T10:00:00+01:00"}
  ],
  "request_key": "planning-novembre-2026-01"
}
```

Le resultat contient `created`, `replayed`, `decision_id`, `group_id`, les dates
reellement sauvees, la question, `participant_count`, les propositions et leurs
`event_id`/`calendar_status`, `url` et `public_url`.
La page `/developer/` et OpenAPI decrivent tous les champs et valeurs nulles.

- Methodes : `simple_vote` (choix unique natif), `majority_judgment`, `consent`.
- Chaque proposition peut contenir `title`, `description` (texte brut), des
  dates ou un melange. Deux a vingt propositions, chacune avec du contenu.
- Les periodes d elaboration (`consultation_*`) et d evaluation (`evaluation_*`)
  sont facultatives, mais une periode fournie exige debut et fin. L evaluation
  suit l elaboration ; les creneaux proposes commencent apres la fin du vote.
  Sans periode, le scrutin reste brouillon. Les dates programmees utilisent
  le cycle de vie natif et sa maintenance habituelle.
- Sans tableaux d invitations : membres natifs du holon ou de l organisation,
  plus le proprietaire. `invitation_user_ids` et `invitation_holon_ids` remplacent
  cette selection ; le proprietaire participe toujours. IDs de l organisation
  autorisee uniquement, aucune adresse libre.
- `visibility_type` vaut `organization` par defaut. `everyone` publie explicitement
  le contenu. `public_url` est une entree de participation publique generique :
  l identification et les controles d invitation natifs restent applicables.
  Aucun jeton personnel ni droit de vote anonyme n est expose.
- Au resultat, les regles natives confirment le gagnant et annulent les autres
  options. Une egalite exige une resolution par un gestionnaire. Un consentement
  peut egalement exiger un arbitrage si plusieurs propositions sont acceptees.
- L API cree un nouveau scrutin complet ; elle ne modifie pas les scrutins existants.
- Garder `request_key` et le contenu identiques apres une erreur ou un delai depasse.
  Les reessais REST et MCP partagent la meme protection contre les doublons.

Publication : appliquer `sql/2026-10-05-01-api-decision-creation.sql` via le flux
habituel de migration. Reconnecter les clients pour consentir a `decisions:create` ;
un renouvellement de jeton ne peut pas ajouter cette autorisation.

Verification locale sans envoi d e-mails :

```sh
docker compose exec -T app php tests/api_decision_test.php
docker compose exec -T app php tests/rest_http_test.php
docker compose exec -T app php tests/decision_calendar_test.php
```

## Lien personnel de rendez-vous

Le lien `/meeting/nom-unique` deja configure dans OMO est disponible en lecture,
avec le consentement `organization:read`, sans nouvelle operation ni migration :

| REST | MCP | Champ |
| --- | --- | --- |
| `GET /api/v1/connection` | `omo_connection_info` | `user.meeting_booking_url` pour l utilisateur connecte |
| `GET /api/v1/members/{user_id}` | `omo_get_member` | `member.meeting_booking_url` |
| `GET /api/v1/records/team` | `omo_list_records`, module `team` | `items[].meeting_booking_url` |
| `GET /api/v1/records/team/{record_id}` | `omo_read_record`, module `team` | `record.meeting_booking_url` |

```json
{"meeting_booking_url": "https://example.org/meeting/alice"}
```

Ce champ vaut `null` si le profil est absent ou desactive, sans nom public ou
sans calendrier de destination valide (appartenant a la personne, actif et
distinct d un calendrier de plages d ouverture). Les droits habituels de lecture
du membre et l appartenance a l organisation sont verifies avant sa divulgation.
Seul le lien public est fourni : aucun calendrier prive, identifiant, mot de
passe, moyen de rencontre ou configuration horaire n est expose par ce champ.

L agent doit reprendre l URL exacte, sans inventer un nom lorsque le champ est
`null`. Le destinataire choisira son creneau sur cette page. Partager le lien ne
reserve aucun rendez-vous et ne garantit aucune disponibilite.

Pour envoyer **ton propre lien** aux membres choisis de l organisation :

1. Lire `user.meeting_booking_url` avec `omo_connection_info`.
2. Previsualiser `omo_list_object_members` avec `object_type=organization`,
   `object_id=organization.id` et les `user_ids` demandes. Omettre `user_ids`
   uniquement si l utilisateur demande explicitement tous les membres.
3. Faire valider le sujet, le texte integral (lien inclus) et les destinataires.
   Si `can_send=true`, appeler ensuite `omo_send_object_email` avec la meme selection,
   l `audience_token`, le sujet, le message contenant l URL et une `request_key`.
   L envoi exige toujours `mail:send` et une demande explicite d envoi.

La documentation `/developer/` presente ce champ et sa signification depuis le
meme schema OpenAPI que le reste de l API.

## Creer et modifier un projet

Autoriser `organization:read projects:write` en reconnectant le client. Un refresh
ne peut pas ajouter ce consentement. Les outils de decouverte et de lecture
restent accessibles avec la seule lecture.

1. Choisir le contexte avec `GET /project-spaces?kind=holons` (ou
   `omo_list_project_spaces`). Suivre `next_after_id` jusqu a null. `kind=organization`
   retourne le holon racine structurel lorsqu il existe, sinon le contexte 0.
   Les statuts renvoyes comprennent leurs libelles personnalises et l indication
   `displayed` pour les vues natives ; les six statuts natifs restent utilisables.
2. Demander les informations manquantes : parent ou aucun, responsable ou aucun,
   statut initial, importance strategique, priorite, debut et delai ou absence
   explicite de dates. Chercher un parent avec `/records/projects` et une personne
   avec `/records/team` ; ne pas deviner les identifiants ni le role de destination.
3. Creer par `POST /projects` ou `omo_create_project`, avec une `request_key` unique.
   Une valeur null signifie un choix explicite de laisser le champ non defini.
   `parent_id=0` signifie aucun parent. Exemple de corps JSON :

```json
{
  "holon_id": 429,
  "title": "Preparer la formation",
  "description": "Preparer le programme et les supports.",
  "parent_id": 0,
  "responsible_user_id": null,
  "status": "ready",
  "priority": 2,
  "importance": 4,
  "planned_start_date": null,
  "planned_end_date": "2030-01-31",
  "request_key": "formation-project-001"
}
```

Le resultat contient `created`, `replayed` et `project`, avec tous les champs
sauves, le lien OMO, `can_edit` et `version`. Le `Location` pointe vers
`GET /projects/{project_id}`. Une nouvelle creation retourne 201, un reessai 200.
La description d entree est du texte simple et le retour contient le HTML natif
assaini. Les niveaux priorite/importance vont de 1 a 5 ou valent null ; le score
`calculated_importance` est calcule par OMO et ne peut pas etre fourni.

Pour modifier, lire `GET /projects/{project_id}` ou `omo_get_project`, puis envoyer
uniquement les champs demandes dans `PATCH /projects/{project_id}`. Pour MCP,
appeler `omo_update_project` avec aussi `project_id`. Copier `version` dans
`expected_version` et fournir une nouvelle `request_key` pour chaque intention
de modification. Le corps REST ne doit pas repeter `project_id` du chemin.

```json
{
  "expected_version": "COPIER_LA_VERSION_LUE",
  "status": "blocked",
  "blocked_reason": "Attente de la validation du budget",
  "blocked_until": "2030-01-15",
  "request_key": "formation-project-block-001"
}
```

Le passage a `blocked` exige explicitement raison et date de reexamen ; l agent
les demande si elles manquent. La date de reexamen n est pas le delai du projet.
`blocked_auto_reactivate` reste false sauf demande explicite ; si active, le
mecanisme natif de relance utilise `blocked_reactivate_status` (`ready` ou
`in_progress`). Quitter le statut bloque efface les details du blocage. Les autres
champs omis d un PATCH sont conserves ; null efface les valeurs nullables.

Les six statuts sont `someday`, `ready`, `in_progress`, `blocked`, `review`, `done`.
Les regles natives restent actives : `someday` efface la planification,
`in_progress` remplit un debut absent avec aujourd hui, `done` remplit une fin
absente avec aujourd hui, un parent date peut imposer sa date de fin. Lire le
resultat pour connaitre les dates effectivement enregistrees. Cycles, parents
inaccessibles et dates incompatibles sont refuses sans sauvegarde partielle.

Une version obsolete retourne 409 avec `error=project_changed`, une explication
et `current_version`. Relire les champs et clarifier la modification avant une
nouvelle tentative ; ne pas simplement reutiliser la nouvelle version pour
forcer un ecrasement. Un timeout se traite en reutilisant exactement la meme
cle et le meme contenu. Le reessai retourne **l etat actuel** du projet et
`replayed=true`, sans recreer le projet ni reappliquer un ancien changement.

Les droits natifs de creation/gestion sont reverifies, y compris le droit du
responsable de modifier son projet. Un reessai exige encore les droits courants.
Historique, calcul d importance et notifications natives de changement de statut
sont conserves. Les nouveaux projets sont standards et actifs ; suppression,
archivage, modeles de processus, validation de propositions et changement de
holon ne sont pas exposes par ces outils.

La migration `sql/2026-10-05-02-api-project-writes.sql` cree uniquement le suivi
commun de reessais REST/MCP. Les donnees du projet restent dans `project`.
