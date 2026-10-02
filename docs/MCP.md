# Premier serveur MCP OMO

Serveur en lecture seule pour tester le login OAuth, la structure et la consultation
des informations accessibles a la personne connectee dans les modules OMO.
Il ne necessite aucun service Node en production ni aucune cle API OpenAI.

## Activation

1. Appliquer les migrations MCP `2026-10-02-04-mcp-structure-oauth.sql`
   et `2026-10-02-05-mcp-refresh-replay.sql` avec le flux habituel :

   ```sh
   php scripts/run-migrations.php
   ```

2. Ajouter a `.env` l URL canonique du endpoint, sans slash final :

   ```dotenv
   MCP_PUBLIC_URL=https://test.exemple.ch/mcp
   ```

   Le serveur reste indisponible (503 explicite) tant que cette variable est absente.
   Ne pas utiliser le sous-domaine de demonstration : il ne permet pas un login utilisateur.
   Apache doit appliquer le `.htaccess` racine et transmettre `Authorization`.
   Un proxy doit reproduire les routes `/mcp` et `/.well-known/oauth-*`.

   La publication GitHub Actions de la branche Dev applique les migrations et
   configure cette valeur automatiquement pour `https://dev.opengov.tools/mcp`.

3. Pour Docker, placer cette variable dans `docker/app/.env`. Utiliser le meme domaine
   pour la connexion MCP et le login. `https://localtest.me/mcp` convient a Inspector
   sur cette machine mais n est pas joignable depuis ChatGPT sur le web.
   Pour ChatGPT, publier sur le serveur de test ou utiliser un tunnel HTTPS dont
   l adresse est renseignee dans `MCP_PUBLIC_URL`. Un changement d URL demande
   une nouvelle connexion et une nouvelle autorisation.

4. Verification sans login : `GET /.well-known/oauth-protected-resource/mcp`
   et `GET /.well-known/oauth-authorization-server` doivent renvoyer du JSON.
   `POST /mcp` sans Bearer doit renvoyer 401 avec `WWW-Authenticate`.
   Un simple GET /mcp dans le navigateur ne constitue pas un test MCP.

## ChatGPT

Dans ChatGPT web, activer Developer mode dans Settings > Security and login,
ouvrir Plugins, choisir + et indiquer l URL `MCP_PUBLIC_URL`.
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
| `omo_connection_info` | aucun | Identite, organisation consentie, ID racine, couverture |
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
Il n y a aucune operation d ecriture.

Les anciennes connexions `structure:read` doivent etre reconnectees et
autorisees avec le nouveau perimetre ; le renouvellement ne peut pas elargir
leur acces automatiquement. Aucune migration supplementaire n est requise.

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

## Tests

Executer sur PHP 8.5, apres migration :

```sh
docker compose exec -T app php tests/mcp_protocol_test.php
docker compose exec -T app php tests/mcp_oauth_structure_test.php
docker compose exec -T app php tests/mcp_http_test.php
docker compose exec -T app php tests/mcp_content_test.php
docker compose exec -T app php tests/mcp_modules_test.php
```

Les tests de donnees creent leurs propres fixtures et les suppriment ensuite.
Verifier aussi manuellement une connexion neuve dans ChatGPT : decouverte,
login, consentement, cinq outils, refus puis revocation. Les tests locaux ne
peuvent pas prouver l accessibilite du domaine depuis les serveurs du fournisseur.
