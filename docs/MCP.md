# Serveur MCP OMO

Serveur pour le login OAuth, la consultation des informations accessibles,
la creation de documents et l envoi d e-mails aux audiences des objets OMO.
Il ne necessite aucun service Node en production ni aucune cle API OpenAI.

## Activation

1. Appliquer les migrations MCP `2026-10-02-04-mcp-structure-oauth.sql`
   et `2026-10-02-05-mcp-refresh-replay.sql`, puis `2026-10-03-01-mcp-document-creation.sql`
   et `2026-10-03-02-object-mail.sql`, puis `2026-10-03-03-object-mail-grant-reference.sql`
   avec le flux habituel :

   ```sh
   php scripts/run-migrations.php
   ```

   Installer aussi les dependances de `composer.lock` avec `composer install --no-dev` ;
   la conversion Markdown utilise `league/commonmark`.

2. Ajouter a `.env` l URL canonique du endpoint, avec le slash final sur Dev :

   ```dotenv
   MCP_PUBLIC_URL=https://test.exemple.ch/mcp/
   ```

   Le serveur reste indisponible (503 explicite) tant que cette variable est absente.
   Ne pas utiliser le sous-domaine de demonstration : il ne permet pas un login utilisateur.
   Apache doit appliquer le `.htaccess` racine et transmettre `Authorization`.
   Un proxy doit reproduire les routes `/mcp/` et `/.well-known/oauth-*`.

   La publication GitHub Actions de la branche Dev applique les migrations et
   configure cette valeur automatiquement pour `https://dev.opengov.tools/mcp/`.
   Le slash final evite la redirection 301 appliquee par l hebergement a `/mcp`,
   qui interrompt les POST de decouverte des outils apres le login. Le serveur
   conserve exactement l URL configuree, avec ou sans slash : la connexion MCP,
   les metadonnees OAuth et le parametre `resource` doivent utiliser la meme URL.

3. Pour Docker, placer cette variable dans `docker/app/.env`. Utiliser le meme domaine
   pour la connexion MCP et le login. `https://localtest.me/mcp/` convient a Inspector
   sur cette machine mais n est pas joignable depuis ChatGPT sur le web.
   Pour ChatGPT, publier sur le serveur de test ou utiliser un tunnel HTTPS dont
   l adresse est renseignee dans `MCP_PUBLIC_URL`. Un changement d URL demande
   une nouvelle connexion et une nouvelle autorisation.

4. Verification sans login : `GET /.well-known/oauth-protected-resource/mcp/`
   et `GET /.well-known/oauth-authorization-server` doivent renvoyer du JSON.
   `POST /mcp/` sans Bearer doit renvoyer directement 401 avec `WWW-Authenticate`,
   sans suivre de redirection. Un simple GET dans le navigateur ne constitue
   pas un test MCP.

## ChatGPT

Dans ChatGPT web, activer Developer mode dans Settings > Security and login,
ouvrir Plugins, choisir + et indiquer l URL `MCP_PUBLIC_URL`.
Sur Dev, saisir exactement `https://dev.opengov.tools/mcp/`. Si le connecteur a
ete cree avec `/mcp` sans slash, le recreer avec cette URL puis refaire le login
et le consentement ; les anciens jetons ciblent une autre URL de ressource.
Choisir OAuth et l enregistrement dynamique (DCR) si le formulaire propose
une methode d enregistrement. Le serveur ne fournit pas de client secret :
il utilise des clients publics, PKCE S256 et un `client_id` cree par DCR.
La disponibilite du mode depend du compte et de la politique de l espace.

Lors du login, utiliser le compte OMO habituel (code e-mail ou mot de passe
autorise, avec TOTP si configure), choisir une organisation et autoriser
la lecture. Installer le plugin personnel, puis ouvrir une nouvelle conversation
Work et selectionner le plugin avec @ ou le menu des outils.

Prompts de verification :

- "Verifie ma connexion OMO et donne le nom de l organisation autorisee."
- "Liste les premiers elements de sa structure avec leur type et leur lien."
- "Lis le detail du premier role de cette liste."
- "Recherche les documents et projets qui parlent de budget, puis lis les fiches trouvees."
- "Lis les proprietes du premier role avec omo_read_record, module structure."
- "Liste tous les membres, puis tous les roles occupes par Marie."
- "A quels holons Marie appartient-elle et quelles sont ses prochaines reunions ?"
- "Liste tous les projets et sous-projets assignes a Marie, avec leur statut."
- "Liste tous les documents dont Marie est proprietaire, en parcourant toutes les pages."
- "Quels evenements commencent ce mois-ci ? Quels indicateurs Marie suit-elle ?"
- "Montre-moi les espaces ou je peux enregistrer un document."
- "Enregistre ce fichier joint dans les documents du role Communication, avec une visibilite pour les membres de l organisation."
- "Cree un document prive dans ce dossier avec le texte que nous venons de rediger."

Documentation officielle verifiee pendant le developpement :
https://developers.openai.com/plugins/deploy/connect-chatgpt
https://developers.openai.com/plugins/build/auth

## Inspector et autres clients

```sh
npx @modelcontextprotocol/inspector@latest
```

Choisir Streamable HTTP, saisir l URL MCP, puis utiliser le parcours OAuth.
Pour le callback HTTP local d Inspector uniquement, ajouter :

```dotenv
MCP_ALLOW_LOCAL_HTTP=1
```

Cette option autorise seulement les adresses de retour HTTP sur localhost,
127.0.0.1 ou ::1 ; elle ne permet aucun callback HTTP distant.
Les callbacks HTTPS sont enregistres exactement et affiches sur le consentement.
Si Inspector appelle directement le serveur depuis le navigateur avec Origin,
ajouter son origine exacte (sans chemin), par exemple :

```dotenv
MCP_ALLOWED_ORIGINS=http://localhost:6274
```

Aucun joker CORS n est accepte. Les clients distants qui ne transmettent pas
Origin n ont pas besoin de cette variable. Claude et d autres clients MCP
peuvent utiliser le meme serveur s ils supportent HTTP, OAuth DCR et PKCE S256.

## Outils et limites

| Outil | Arguments | Resultat |
| --- | --- | --- |
| `omo_list_object_members` | `object_type` (holon, event, project, decision), `object_id` ; `offset`, `limit` (maximum 50) | Membres/invites, nom, e-mail et telephone de l organisation, relations/statut, pagination `next_offset`, `can_send`, `recipient_count`, `audience_token` |
| `omo_send_object_email` | `object_type`, `object_id`, `subject`, `message`, `audience_token`, `request_key` | Envoi direct jusqu a 5 destinataires, sinon traitement automatique en file ; `mail_id`, compteurs de livraison et `replayed` |
| `omo_object_email_status` | `mail_id` | Suivi des messages du compte connecte : queued, sending, sent, failed, skipped, unknown |
| `omo_connection_info` | aucun | Identite, organisation consentie, ID racine, couverture |
| `omo_list_document_spaces` | `kind` : `organization` (defaut), `holons`, `folders` ; `after_id` (0), `limit` (20, maximum 50) | Destinations ou les droits OMO autorisent la creation, visibilites compatibles, consentement et disponibilite du stockage de fichiers |
| `omo_create_document` | `title`, `request_key`, exactement un de `content`, `external_url` ou `file` ; `holon_id`, `parent_document_id`, `description`, `keywords`, `content_format`, `visibility_type` facultatifs | Nouveau Memo, lien externe ou fichier, ID, URL OMO, destination, visibilite effective et metadonnees du fichier ; `replayed` pour un reessai |
| `omo_catalog` | aucun | Modules accessibles, filtres, relations utilisateur, statuts et sens des dates |
| `omo_get_member` | `user_id` | Identite dans l organisation, premiere page des affectations avec type du holon, lien vers le profil et arguments pour explorer les objets lies |
| `omo_list_records` | `module`, filtres facultatifs : `user_id`, `user_relation`, `query`, `context_holon_id`, `parent_id`, `status`, `date_from`, `date_to` ; `after_id` (0), `limit` (20, maximum 50) | Liste complete paginee des fiches accessibles ; `next_after_id`, `complete` |
| `omo_list_assignments` | `user_id`, `holon_id` facultatifs ; `after_id` (0), `limit` (20, maximum 50) | Affectations directes actives : personne, role/cercle, focus et URL |
| `omo_list_structure` | `parent_id` facultatif, `after_id` (0), `limit` (20, maximum 50) | Noms, types, parents, URL ; `next_after_id` pour la suite |
| `omo_get_holon` | `holon_id` | Informations de base d un element |
| `omo_search` | `query`, `modules` facultatif, `context_holon_id` facultatif, `offset` (0), `limit` (20, maximum 50) | Selection de resultats accessibles avec module, ID, extrait et URL ; `next_offset` pour la suite |
| `omo_read_record` | `module`, `record_id`, `context_holon_id` facultatif, `mission_id` facultatif pour les tutoriels, `offset` (0), `limit` (12000 caracteres, maximum 20000) | Texte de la fiche et collections visibles ; `next_offset` pour la suite |

Une autorisation couvre une seule organisation. Le scope `organization:read`
donne acces aux informations consultables avec les droits OMO du compte :
structure et proprietes, membres et competences visibles, documents et PV,
calendrier, regles, decisions, projets, indicateurs et mesures, processus,
activites recurrentes, FAQ et tutoriels. `omo_connection_info` indique les
modules actives ; la disponibilite d un module ne garantit pas l acces a toutes
ses fiches. Une organisation sans module Structure peut aussi etre autorisee.
La liste de structure reste limitee aux elements actifs et visibles.

### Explorer sans plafond de recherche

Appeler `omo_catalog` pour connaitre les filtres propres aux modules actives.
Resoudre une personne avec `omo_list_records`, `module: "team"`, `query: "Marie"`.
Le `record_id` du membre est le `user_id` a utiliser dans les autres listes.
Un filtre utilisateur ne change jamais les droits de la personne connectee.
Si plusieurs membres correspondent, identifier la bonne personne avant de filtrer.

Appeler ensuite `omo_get_member` avec ce `user_id`. Sa section `assignments`
contient la premiere page des roles, cercles et groupes directement affectes ;
continuer avec `omo_list_assignments` et son `next_after_id`. `related_records`
fournit des appels aux listes des modules disponibles, et non des resultats ou
des compteurs : executer les listes pertinentes pour repondre a la demande.
Pour les appartenances effectives, utiliser `structure` avec
`user_relation: "effective_member"`. Pour les prochaines reunions, utiliser
`calendar`, `user_relation: "invited"` et `date_from` avec la date souhaitee.
Le filtre exclut les invitations refusees ou revoquees, les evenements annules
et les brouillons prives. Il tient compte des holons invites et des membres du
holon de reunion en l absence d invitations explicites. Les invitations par
e-mail ne sont rapprochees du membre que si les identites des invites sont
consultables par le compte connecte.

`omo_list_records` enumere les fiches par ID croissant, sans le plafond de
50 resultats de la recherche. Conserver exactement les memes filtres, puis
passer `next_after_id` comme `after_id` jusqu a recevoir `null` et `complete: true`.
Une page vide avec un curseur non nul demande de continuer : elle peut avoir
parcouru uniquement des fiches privees. Les listes sont vivantes, pas un instantane.
Les titres servent a choisir les fiches ; lire leur contenu avec `omo_read_record`
en reprenant leur `context_holon_id`. Les reponses de lecture incluent aussi un
objet `record` avec parent, holon, statut et dates selon le module.

| Module | Sens de `user_relation` |
| --- | --- |
| `structure` | `member` : affectation directe active ; `effective_member` : appartenance effective selon les regles natives du holon |
| `team` | `member` : personne elle-meme |
| `calendar` | `author` : createur ; `invited` : invite direct, via un holon ou par defaut, hors refus/revocation |
| `documents` | `owner` : proprietaire ; `author` : createur |
| `pv` | `owner`, `author`, `editor` : proprietaire, createur, redacteur courant ou officiel ; `invited` : invite de l evenement lie consultable, avec module Calendrier actif |
| `rules` | `author` : createur |
| `decision` | `owner` : proprietaire ; `participant` : participant non refuse/revoque, uniquement avec le droit de gestion des participants ; aucun vote expose |
| `projects` | `responsible` : responsable ; `assignee` : personne explicitement affectee |
| `stats` | `author` : createur ; `responsible` : responsable |
| `processus`, `activities` | `responsible` : responsable |
| `faq` | `requester` : demandeur initial |
| `tutorials` | Filtre utilisateur indisponible ; parcours accessibles a la personne connectee |

Sans `user_relation`, le filtre `user_id` combine les relations disponibles avec
OU. Les filtres non pris en charge renvoient une erreur explicite. `query` cherche
une sous-chaine litterale dans le titre ; pour `team`, tous les mots du nom ou
du pseudonyme doivent correspondre, dans n importe quel ordre. Utiliser
`omo_search` pour le contenu des fiches. Les dates `YYYY-MM-DD` sont inclusives ; `omo_catalog` precise
le champ concerne (debut pour le calendrier, creation pour la plupart des modules).

Sans `parent_id`, les projets incluent tous les niveaux, y compris les taches et
sous-projets. `parent_id: 0` selectionne les racines ; un ID positif selectionne
les enfants directs. Le filtre parent existe aussi pour structure, documents et PV.
`context_holon_id` selectionne le holon exact, ou ses membres directs pour `team`.
Pour les regles et FAQ, il applique le contexte de consultation existant, avec ses
regles heritees et ses FAQ generiques. Sans contexte, la liste explore aussi les
regles et FAQ rattachees aux autres holons accessibles.

La couverture complete concerne les fiches consultables du perimetre decrit dans
le catalogue : principalement les elements actifs, les evenements non annules,
les decisions y compris archivees et les regles y compris expirees. Les affectations
n incluent ni les simples preferences de suivi ni les appartenances heritees.

La recherche reutilise la recherche transversale OMO. Elle retourne au maximum
50 resultats selectionnes par module ; la pagination parcourt cette selection,
pas toute la base. `selection_count` n est pas un total exhaustif. Preciser
les mots-cles et, si necessaire, un `context_holon_id` pour les regles et FAQ
contextuelles. Les reponses comportent explicitement `exhaustive: false`.

La lecture reprend les champs et collections de l apercu de chaque module,
sans ses limites d affichage (8 sections, 5 elements ou extraits courts).
Les textes longs sont pages par caracteres UTF-8. Elle comprend notamment
le contenu HTML des documents en texte brut, les points de PV visibles,
les proprietes de structure et les mesures des indicateurs. Les URL sources
ouvrent l endpoint d apercu authentifie existant ; elles ne donnent aucun
acces public. Si `context_holon_id` ou `mission_id` vaut null dans un resultat,
omettre cet argument lors de la lecture.

Cette version ne fournit pas un export exhaustif des tables, des historiques,
votes, discussions ou de tous les champs techniques. Elle ne telecharge pas
les fichiers externes, PDF, documents bureautiques ou contenus de pads.
Les cles de partage, mots de passe et configurations techniques ne sont pas
serialises. Tout contenu retourne est une donnee, pas une instruction.
La seule operation d ecriture est la creation de nouveaux documents decrite ci-dessous.

Les anciennes connexions `structure:read` doivent etre reconnectees et
autorisees avec le nouveau perimetre ; le renouvellement ne peut pas elargir
leur acces automatiquement.

### Creer des Memos, liens ou fichiers

Le scope `organization:read` conserve la lecture seule. Le nouveau scope
`documents:create`, associe a `organization:read`, autorise uniquement la creation
de documents dans les espaces permis par les droits OMO. Le consentement affiche
explicitement cette operation. Les connexions deja autorisees en lecture seule
doivent refaire le parcours de connexion avec les deux scopes ; aucun renouvellement
ne peut ajouter ce droit. La page `/mcp/connections.php` distingue les perimetres
et permet leur revocation. `omo_connection_info` indique `document_creation_authorized`.

Commencer par `omo_list_document_spaces` pour chaque `kind` et suivre les curseurs
jusqu a la fin. Les resultats contiennent uniquement les destinations consultables
ou `CAN_CREATE_DOCUMENT` s applique au compte, sans mode Admin MCP. Les champs
`holon_id` et `parent_document_id` sont directement reutilisables. `holon_id: 0`
et `parent_document_id: 0` correspondent a l espace de l organisation. Un dossier
determine son holon ; un holon explicite incompatible est refuse. Le serveur
recontrole appartenance, module Documents, destination et droit avant toute creation.

Pour un Memo, envoyer `content` (maximum 200000 caracteres). Le format `text`
est echappe pour ne pas interpreter les balises ; `content_format: "html"` accepte
du HTML, `content_format: "markdown"` (ou `"md"`) convertit le Markdown en HTML
avec `league/commonmark`. Les deux passent par `PropertyFormat::sanitizeHtml`,
le meme filtre serveur que l editeur Summernote : titres h1 a h3, listes, tableaux,
liens et surlignage autorises ; scripts, evenements, images et styles non autorises
retires. Le HTML present dans du Markdown passe egalement par ce filtre. Les
elements Markdown sans equivalent autorise perdent leur formatage.
Si le nettoyage retire tout le contenu, la creation est refusee sans laisser de
Memo vide ; le contenu peut etre corrige et renvoye avec la meme `request_key`.
Les Memos gardent le type technique `html`, compatible avec les documents existants.
`omo_list_document_spaces` annonce `content_formats` et `external_links_available`.
Un texte saisi ou dicte devient un Memo par defaut : l assistant transmet son
contenu directement, sans creer de fichier a telecharger. Il en va de meme pour
du HTML ou du Markdown fourni comme texte. `file` sert a conserver un original
lorsque l utilisateur le demande. Si le choix entre Memo editable et fichier
original est ambigu, l assistant doit poser la question avant de creer le document.
La visibilite de lecture par defaut est `self`
et l edition reste reservee au proprietaire. Choisir explicitement `organization`,
`circle` ou `role` si souhaite et compatible avec la destination. Le partage public
n est pas propose par MCP. Le compte authentifie devient proprietaire et createur.

Pour enregistrer une URL, envoyer `external_url` avec une adresse HTTP ou HTTPS,
sans identifiants de connexion. Le document cree est un lien externe OMO ; la
page cible n est pas telechargee. Un Memo ou un lien ne necessite pas de stockage
documentaire externe. `content_format` concerne uniquement `content` et ne peut
pas accompagner `external_url` ou `file`. Les trois entrees sont exclusives.

Exemples (remplacer `holon_id` par une destination retournee par la decouverte) :

```json
{"title":"Compte rendu","request_key":"memo-md-20261003-01","holon_id":123,"content_format":"markdown","content":"# Compte rendu\n\n**Decision** : adopter la proposition.\n\n- Premiere action\n- Seconde action"}
```

```json
{"title":"Site du projet","request_key":"lien-20261003-01","holon_id":123,"external_url":"https://example.org/projet#documentation"}
```

Pour conserver le fichier original, utiliser `file` avec `download_url` et `file_id`,
ainsi que `file_name` et `mime_type` facultatifs. Le descripteur declare les quatre
proprietes et `_meta["openai/fileParams"]: ["file"]`, conformement a la
[documentation OpenAI](https://developers.openai.com/plugins/reference#file-apis),
pour transmettre une piece jointe de ChatGPT. Les autres clients peuvent fournir
la meme structure avec une URL temporaire HTTPS et un identifiant stable de fichier.
Ne pas fabriquer ces valeurs ni passer une reference locale `sandbox:` ou `file:`.
Si le client ne transmet pas de fichier, utiliser le contenu extrait seulement
lorsque l utilisateur souhaite enregistrer ce texte plutot que l original.

Le stockage documentaire de l organisation (Nextcloud ou kDrive) doit deja etre
configure. La limite d import est de 20 Mio. Le serveur telecharge le fichier vers
un fichier temporaire, verifie les adresses publiques IPv4 et TLS, controle chaque
redirection, limite le volume et le temps de telechargement, puis utilise le flux
d enregistrement OMO existant. Aucun cookie ou jeton OMO n est transmis a l URL
source. Le type MIME vient des octets ; les fichiers temporaires sont supprimes.
Le fichier original reste dans le stockage de l organisation, pas dans la table SQL.
Les dossiers distants Nextcloud, les associations de projets, les PV, l edition
et la suppression de documents existants ne font pas partie de ce premier essai.

Chaque creation demande un `request_key` unique (8 a 100 caracteres alphanumeriques,
tiret ou underscore). Reutiliser cette cle et les memes valeurs lors d un reessai.
La table `mcp_document_creation` associe une empreinte de demande au compte, a
l organisation et au document ; un verrou transactionnel empeche les creations
concurrentes de dupliquer le document. Un contenu ou une destination differents
avec la meme cle sont refuses. Les URL temporaires peuvent etre renouvelees sans
changer de cle ; elles ne sont pas stockees. Un document supprime apres creation
n est pas recree par un reessai. Un echec annule les changements en base et nettoie
le fichier importe si une etape ulterieure echoue.

Les outils recontrolent l appartenance active a l organisation. Ils utilisent
l identite du Bearer, jamais une identite ou une organisation envoyee par l agent,
ni les cookies du navigateur. Aucun mode Admin n est active pour MCP.

Les codes durent 5 minutes et sont a usage unique. Les jetons d acces durent
1 heure. Les jetons de renouvellement sont remplaces a chaque utilisation,
avec une duree maximale de 30 jours depuis le consentement. Le rejeu d un
ancien code ou jeton de renouvellement revoque toute la connexion concernee.
Seules les empreintes des codes et jetons sont conservees en base.
Les appels journalisent le nom de l outil et les IDs, sans jetons ni arguments.

Ouvrir `/mcp/connections.php` pour revoquer une connexion personnelle. La
revocation invalide aussi son renouvellement. Le endpoint OAuth `/mcp/revoke.php`
est disponible aux clients. Aucun token ne doit etre colle dans une conversation.

Le transport est stateless : reponses JSON aux POST, notifications acceptees
en 202, pas de session MCP ni de flux SSE GET. Versions negociees :
2025-11-25, 2025-06-18 et 2025-03-26.

## Membres, invitations et e-mails

`omo_list_object_members` fournit une liste complete paginee, independante de la
recherche. Suivre `next_offset` jusqu a `null` ; les listes refletent les droits
du compte connecte et les changements faits dans OMO.

- Holon : membres actifs effectifs, appartenances calculees et roles descendants
  pour les cercles ; un holon de type organisation represente ses membres actifs.
- Reunion : utiliser `object_type: "event"` et l identifiant de l evenement.
  Pour un PV, `omo_list_records` renvoie `metadata.IDevent`. Les invitations
  explicites (personnes et holons invites) remplacent les membres du holon par
  defaut. Les inscriptions publiques s ajoutent aux invitations. Les adresses
  des invites externes sont accessibles aux gestionnaires de l evenement.
- Projet : responsable et affectations individuelles actives, sans inclure
  automatiquement les membres du holon ni les simples abonnements.
- Decision : participants deja references. Leur consultation et l envoi exigent
  `CAN_EDIT_DECISION` ; aucun vote, resultat individuel ou jeton personnel n est divulgue.

Avant l envoi, presenter a l utilisateur le nom de l objet et le nombre de
destinataires, puis copier `audience_token` de la liste. `subject` et `message`
sont du texte brut. Une audience modifiee demande une nouvelle lecture.
Aucune adresse libre, piece jointe, adresse d expediteur, cc/bcc ou contenu HTML
ne peut etre fourni a l outil. Les refus, revocations et comptes inactifs sont
exclus. Les adresses identiques sont dedupliquees ; chacun recoit un e-mail
individuel, sans partager les coordonnees des autres destinataires.

L assistant doit obtenir un nouveau consentement `organization:read mail:send`
(avec `documents:create` egalement si souhaite). Le renouvellement d un ancien
jeton ne peut pas ajouter ce droit. L utilisateur doit participer a l objet ou
disposer de son droit de gestion ; les decisions exigent toujours la gestion.
Dans OMO, le bouton **Envoyer un e-mail** ouvre la popup partagee des membres,
evenements, projets et decisions, avec protection CSRF.

Jusqu a **5 destinataires**, la requete effectue directement les livraisons SMTP.
Au-dela, le worker `scripts/process-object-mail.php --mail=<id>` est lance
automatiquement en arriere-plan. Il utilise le resolveur PHP CLI du site,
verifiant la version et rejetant PHP-FPM/CGI. En cas de lancement indisponible,
la maintenance OMO reprend la file via `php scripts/run-omo-maintenance.php`
ou le cron OMO existant. Les diagnostics du worker sont dans le journal runtime
`object-mail/worker.log`. Sur un hebergement avec un CLI particulier, configurer
`SITE_UPDATE_PHP_BINARY` avec son chemin absolu.

Le transport SMTP deja configure dans OMO est utilise. Les destinataires et
l adresse Reply-To viennent des profils/invitations de l objet. L adresse
d expediteur est `MAIL_FROM` si elle est valide, sinon l adresse du compte SMTP
OMO si son identifiant est une adresse, sinon celle du profil connecte.
`MAIL_USER` reste un identifiant SMTP et peut etre vide sur un relais sans
authentification ; aucune adresse technique supplementaire n est obligatoire.

Le resultat distingue les envois acceptes par SMTP (`sent`), en attente
(`queued`/`sending`), refuses/echoues (`failed`), annules apres reverification
des droits (`skipped`) et incertains (`unknown`). `sent` ne garantit pas la
reception finale. Ne pas annoncer un succes complet tant que tous les
destinataires ne sont pas `sent`. Un worker interrompu apres la prise en charge
d un destinataire produit `unknown`, sans relance automatique.

Conserver la meme `request_key` (8 a 100 caracteres alphanumeriques, `_`, `-`)
et exactement les memes arguments en cas de reessai : un message deja cree
n est jamais renvoye automatiquement, meme en cas d echec SMTP. Limites :
500 destinataires par message, 20 messages par heure et 1000 destinataires
par jour par compte, 5000 par jour par organisation, sur des fenetres glissantes.
Les tables `object_mail` et `object_mail_recipient` conservent les demandes
et leurs livraisons. Contenus et adresses sont purges apres 30 jours par la
maintenance ; les compteurs et empreintes restent pour l audit et la deduplication.

## Tests

Executer sur PHP 8.5, apres migration :

```sh
docker compose exec -T app php tests/mcp_protocol_test.php
docker compose exec -T app php tests/mcp_oauth_structure_test.php
docker compose exec -T app php tests/mcp_http_test.php
docker compose exec -T app php tests/mcp_content_test.php
docker compose exec -T app php tests/mcp_modules_test.php
docker compose exec -T app php tests/mcp_browse_test.php
docker compose exec -T app php tests/mcp_member_test.php
docker compose exec -T app php tests/mcp_document_creation_test.php
docker compose exec -T app php tests/mcp_file_import_test.php
docker compose exec -T app php tests/object_mail_test.php
docker compose exec -T app php tests/mcp_deployed_discovery_test.php
```

Test optionnel d envoi HTTP reel, uniquement vers le Mailpit local configure
sur `mailpit:1025` sans authentification :

```sh
docker compose exec -T app php tests/mcp_http_test.php --mailpit
```

Il verifie l envoi MCP direct, le formulaire OMO avec CSRF et le lancement
automatique du worker pour six destinataires synthetiques. Les tests de file
ciblent exclusivement les identifiants de leurs propres messages ; aucun
envoi utilisateur en attente n est consomme par le simulateur SMTP.

Les tests de donnees creent leurs propres fixtures et les suppriment ensuite.
Le test d import utilise un petit fichier de ce depot via HTTPS public et un
stockage WebDAV simule sur un port loopback aleatoire ; il ne touche aucun stockage
utilisateur. Il necessite l acces a `raw.githubusercontent.com` et PHP CLI Linux
(Docker), et verifie les octets, les metadonnees, les reessais et le retour arriere.
Le deploiement Dev execute aussi `mcp_deployed_discovery_test.php` sur le serveur :
echange OAuth et appels HTTPS authentifies avec des fixtures temporaires, sans
donnees utilisateur ni jetons dans les logs. Ce test accepte uniquement les
hosts `localtest.me` et `dev.opengov.tools` ; TLS est verifie sur Dev.
Le test navigateur du consentement utilise Docker sur `https://localtest.me`,
`MCP_ALLOW_LOCAL_HTTP=1`, Chrome et une installation de Playwright de test :

```sh
node tests/mcp_consent_browser_test.cjs /chemin/vers/node_modules/playwright
```

Il verifie le retour OAuth apres acceptation et refus, et le blocage CSP d une
destination non autorisee. Le formulaire de consentement autorise uniquement
son origine et celle du callback valide de la demande ; les autres pages MCP
gardent `form-action 'self'`. Les tests HTTP seuls ne detectent pas les blocages
CSP appliques par le navigateur aux redirections apres soumission.

Verifier aussi manuellement une connexion neuve dans ChatGPT : decouverte,
login, consentement lecture et creation, quatorze outils, import d une piece jointe,
refus puis revocation. Les tests locaux ne
peuvent pas prouver l accessibilite du domaine depuis les serveurs du fournisseur.
