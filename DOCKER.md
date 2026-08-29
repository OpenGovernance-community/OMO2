# Docker local

Cette configuration sert a lancer une version locale reproductible du projet avec :

- PHP 8.5 + Apache
- MariaDB 11.4
- Mailpit pour tester les emails en local
- phpMyAdmin pour inspecter la base locale
- Etherpad local pour tester les documents collaboratifs
- EtherCalc local pour tester les tableurs collaboratifs
- Collabora CODE local pour preparer l edition de documents bureautiques
- SpaceDeck Open local pour tester les tableaux blancs collaboratifs
- prise en charge de `short_open_tag`
- acceptation de `localhost`, `demo.localhost`, `org1.localhost`, `org2.localhost`
- acceptation d'un domaine de dev partage recommande avec wildcard DNS : `localtest.me`, `demo.localtest.me`, `org1.localtest.me`
- compatibilite legacy conservee avec `omo.test` si des entrees `hosts` existent deja

## 1. Preparer le `.env`

Pour Docker, le conteneur web utilise automatiquement :

`docker/app/.env`

Cela evite d'utiliser par erreur un `.env` local de production dans le conteneur.

Ce fichier peut rester public et versionne pour toutes les valeurs non sensibles.

Pour les secrets locaux non publies, le conteneur charge aussi :

`docker/app/.env.private`

Ce second fichier est ignore par Git et injecte comme variables d'environnement du conteneur.

La separation recommandee est donc :

- `docker/app/.env` : valeurs Docker publiques et partageables comme `DB_HOST=db`, `MAIL_HOST=mailpit`, etc.
- `docker/app/.env` force aussi `ORGANIZATION_SUBDOMAIN_ROUTING=true` pour garder le routage par sous-domaines avec `localtest.me`
- `docker/app/.env.private` : secrets locaux comme `GITHUB_BUGREPORT_TOKEN`, `TELEGRAM_BOT_TOKEN`, `OPENAI_API_KEY`, mots de passe, etc.

Un exemple de depart est fourni dans :

`docker/app/.env.private.example`

Le fichier `.env.example` reste utile pour une configuration hors Docker.

Les valeurs Docker par defaut sont :

```env
DB_HOST=db
DB_NAME=omodev
DB_USER=omodev
DB_PASS=omodev
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_AUTH=false
COOKIE_SCOPE_MODE=auto
COOKIE_ROOT_HOST=
```

Avec `COOKIE_SCOPE_MODE=auto`, les hosts reserves comme `dev`, `beta` et `deploy` sont isoles automatiquement. Pour partager volontairement les cookies dans `*.dev.domaine.tld`, utiliser `COOKIE_SCOPE_MODE=environment`.
Pour forcer une racine precise comme `dev.opengov.tools`, utiliser `COOKIE_ROOT_HOST=dev.opengov.tools`.

## 2. Seed SQL publie

Le seed principal versionne est dans :

`docker/db/init/00-base.seed.sql`

Ce fichier est publie dans le repository pour que l'environnement Docker soit directement utilisable apres clonage.

Les evolutions versionnees ajoutees apres le snapshot de ce dump sont appliquees automatiquement juste apres par :

`docker/db/init/01-post-base-migrations.sql`

Ce second fichier rejoue les migrations SQL publiees manquantes pour aligner une base Docker neuve avec l'etat courant du schema et des donnees de reference, sans devoir lancer manuellement les migrations apres chaque recreation de volume.

Si tu veux ajouter des donnees locales non publiees, cree un script supplementaire ignore par Git, par exemple :

`docker/db/init/99-local.override.local.sql`

Au premier demarrage :

- MariaDB importe `00-base.seed.sql`
- MariaDB importe ensuite `01-post-base-migrations.sql` pour rejouer les migrations versionnees manquantes depuis le snapshot du dump
- MariaDB importe ensuite, s'ils existent, les scripts locaux additionnels comme `99-local.override.local.sql`
- MariaDB utilise `utf8mb4` par defaut grace a `docker/db/conf.d/charset.cnf`
- le dump principal contient deja les organisations de demo `Org1` et `Org2` ainsi que la structure de demo

## 3. Lancer les conteneurs

```bash
docker compose up --build
```

Apres le premier demarrage, installer les dependances PHP dans le volume du projet :

```bash
docker compose exec app composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

Les ports `80` et `443` doivent etre libres sur la machine hote.

L'application sera disponible sur :

- `http://localhost`
- `https://localhost`
- `https://localtest.me`
- `https://demo.localtest.me`
- `https://org1.localtest.me`
- `https://org2.localtest.me`
- `https://any-subdomain.localtest.me`
- Mailpit : `http://localhost:8025`
- phpMyAdmin : `http://localhost:8081`
- Etherpad : `https://doc.localtest.me`
- EtherCalc : `https://calc.localtest.me`
- Collabora CODE : `https://document.localtest.me`
- SpaceDeck Open : `https://whiteboard.localtest.me`

Adresses de demonstration utiles :

- `admin@org1.opengov.tools`
- `member1@org1.opengov.tools`
- `admin@org2.opengov.tools`

Les codes de connexion sont envoyes dans Mailpit.

Le HTTPS local utilise par defaut un certificat autosigne genere dans l'image Docker, valable pour `localhost`, `*.localhost`, `localtest.me`, `*.localtest.me`, ainsi que `omo.test` et `*.omo.test` pour compatibilite legacy.
Le navigateur affichera probablement un avertissement de securite la premiere fois : c'est normal en local, mais un certificat autosigne ne permet pas les service workers ni les notifications push.

Pour tester les notifications avec `localtest.me` ou ses sous-domaines, creer un certificat local de confiance avec `mkcert` :

```powershell
mkcert -install
New-Item -ItemType Directory -Force docker/apache/certs
mkcert -cert-file docker/apache/certs/dev-localtest.crt -key-file docker/apache/certs/dev-localtest.key localhost '*.localhost' localtest.me '*.localtest.me' omo.test '*.omo.test'
Copy-Item docker/compose.local-certificates.yaml.example compose.override.yaml
docker compose up -d --force-recreate app
```

Les certificats et le fichier `compose.override.yaml` sont ignores par Git. Le certificat signe par `mkcert` reste donc strictement local a la machine de developpement.

## 4. Reinitialiser la base

Les scripts d'initialisation MariaDB ne tournent qu'au premier demarrage du volume.

Pour repartir de zero :

```bash
docker compose down -v
docker compose up --build
```

## 4bis. Appliquer les migrations SQL versionnees

Apres le premier seed, les evolutions de schema versionnees dans `sql/` peuvent encore etre appliquees sans reinitialiser le volume :

```bash
docker compose exec app php scripts/run-migrations.php
```

Seuls les fichiers SQL contenant le marqueur `-- @migration` sont executes automatiquement.

Si tu veux appliquer les migrations sur plusieurs bases dans le conteneur, tu peux definir `DB_MIGRATION_DATABASES` dans `docker/app/.env` ou passer l'option `--databases`.

## 5. Domaine de dev recommande pour partager les cookies

Pour tester la connexion partagee entre sous-domaines, `localhost` n'est pas ideal.
Le plus simple est d'utiliser `localtest.me`.

`localtest.me` resolve automatiquement vers `127.0.0.1`, y compris pour les sous-domaines, donc aucun fichier `hosts` n'est necessaire.

Puis redemarrer Docker si tu viens d'une ancienne configuration :

```bash
docker compose down
docker compose up --build
```

Ensuite, utiliser de preference :

- `http://localtest.me/omo/`
- `http://org1.localtest.me/omo/`
- `http://org2.localtest.me/omo/`
- `https://localtest.me/omo/`
- `https://demo.localtest.me/omo/`
- `https://org1.localtest.me/omo/`
- `https://org2.localtest.me/omo/`

En production, avec un domaine racine comme `opengov.tools`, le meme mecanisme donne :

- `https://org1.opengov.tools/omo/`
- `https://org2.opengov.tools/omo/`

Dans cette configuration, les cookies peuvent etre poses sur `.localtest.me` et donc etre partages entre les sous-domaines, ce qui simule beaucoup mieux la production.

Le service Etherpad local est preconfigure pour OMO et passe par le certificat HTTPS local partage avec Apache. Copier les valeurs Etherpad de `docker/app/.env.private.example` dans `docker/app/.env.private` et celles de `docker/etherpad/.env.private.example` dans `docker/etherpad/.env.private`, puis utiliser OMO et Etherpad en HTTPS. Si un ancien fichier prive contient encore des variables Etherpad vides, les supprimer ou les remplacer par les valeurs de l exemple, car ce fichier prive est prioritaire. Etherpad utilise une base MariaDB separee sur le meme serveur que OMO. Son image inclut aussi le module qui synchronise le theme defini dans OMO avec l iframe Etherpad.

EtherCalc est egalement disponible localement via `https://calc.localtest.me`. Copier les variables EtherCalc de `docker/app/.env.private.example` dans `docker/app/.env.private`, puis copier `docker/ethercalc/.env.private.example` vers `docker/ethercalc/.env.private`. OMO cree et supprime les feuilles depuis le reseau Docker interne; l URL publique ne permet que l affichage et les editions autorisees par les jetons signes par OMO.

Collabora CODE est disponible localement via `https://document.localtest.me`. Copier `docker/collabora/.env.private.example` vers `docker/collabora/.env.private` avant de lancer Docker. Le conteneur est accessible uniquement depuis le reseau Docker et Apache fournit le proxy HTTPS, y compris les connexions WebSocket. L image locale charge un script de marque OMO versionne qui reapplique les variables de palette apres l initialisation de CODE; apres une modification de `docker/collabora/branding.js`, changer aussi le suffixe de version dans `docker/collabora/Dockerfile`, puis reconstruire avec `docker compose up -d --build collabora`.

SpaceDeck Open est disponible localement via `https://whiteboard.localtest.me`. Copier `docker/spacedeck/.env.private.example` vers `docker/spacedeck/.env.private` avant de lancer Docker. Le service est construit depuis le fork local `docker/spacedeck/fork`, qui implemente un contrat generique de controle d acces externe et une API interne de creation/suppression de tableaux. Il conserve ses medias et sa base SQLite dans deux volumes Docker, et Apache transmet son WebSocket `/socket`. L image locale installe GraphicsMagick pour les images; FFmpeg, Ghostscript et Chromium pourront etre ajoutes lorsque les conversions multimedia et les exports seront integres.

Une page locale de test est disponible sur `https://org1.localtest.me/test/spacedeck.php`. Elle ouvre dans une iframe le tableau blanc de demonstration cree automatiquement au demarrage du conteneur. Elle utilise l utilisateur OMO connecte, son nom affiche et un jeton signe de courte duree. Les liens `mode=edit`, `mode=read` et `mode=deny` permettent de verifier les trois niveaux de droit.

`localtest.me` n'est pas "publie" par Docker sur Internet du projet : c'est simplement un domaine public qui renvoie automatiquement vers `127.0.0.1`, ce qui evite toute configuration DNS locale supplementaire.

Si besoin, l'ancien schema `omo.test` reste compatible a condition d'ajouter manuellement les entrees `hosts`.

## 6. Si les sous-domaines `.localhost` ne resolvent pas chez toi

La plupart des environnements modernes gerent `*.localhost`.

Si ce n'est pas le cas, ajouter temporairement ces entrees dans le fichier hosts :

```text
127.0.0.1 demo.localhost
127.0.0.1 org1.localhost
127.0.0.1 org2.localhost
```
