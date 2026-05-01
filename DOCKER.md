# Docker local

Cette configuration sert a lancer une version locale reproductible du projet avec :

- PHP 8.2 + Apache
- MariaDB 11.4
- Mailpit pour tester les emails en local
- phpMyAdmin pour inspecter la base locale
- prise en charge de `short_open_tag`
- acceptation de `localhost`, `demo.localhost`, `org1.localhost`, `org2.localhost`
- acceptation d'un domaine de dev partage recommande avec wildcard DNS : `localtest.me`, `demo.localtest.me`, `org1.localtest.me`
- compatibilite legacy conservee avec `omo.test` si des entrees `hosts` existent deja

## 1. Preparer le `.env`

Pour Docker, le conteneur web utilise automatiquement :

`docker/app/.env`

Cela evite d'utiliser par erreur un `.env` local de production dans le conteneur.

Si tu veux personnaliser l'environnement Docker, modifie ce fichier.

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
```

## 2. Seed SQL publie

Le seed principal versionne est dans :

`docker/db/init/00-base.seed.sql`

Ce fichier est publie dans le repository pour que l'environnement Docker soit directement utilisable apres clonage.

Si tu veux ajouter des donnees locales non publiees, cree un script supplementaire ignore par Git, par exemple :

`docker/db/init/99-local.override.local.sql`

Au premier demarrage :

- MariaDB importe `00-base.seed.sql`
- MariaDB importe ensuite, s'ils existent, les scripts locaux additionnels comme `99-local.override.local.sql`
- MariaDB utilise `utf8mb4` par defaut grace a `docker/db/conf.d/charset.cnf`
- le dump principal contient deja les organisations de demo `Org1` et `Org2` ainsi que la structure de demo

## 3. Lancer les conteneurs

```bash
docker compose up --build
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

Adresses de demonstration utiles :

- `admin@org1.opengov.tools`
- `member1@org1.opengov.tools`
- `admin@org2.opengov.tools`

Les codes de connexion sont envoyes dans Mailpit.

Le HTTPS local utilise un certificat autosigne genere dans l'image Docker, valable pour `localhost`, `*.localhost`, `localtest.me`, `*.localtest.me`, ainsi que `omo.test` et `*.omo.test` pour compatibilite legacy.
Le navigateur affichera probablement un avertissement de securite la premiere fois : c'est normal en local.

## 4. Reinitialiser la base

Les scripts d'initialisation MariaDB ne tournent qu'au premier demarrage du volume.

Pour repartir de zero :

```bash
docker compose down -v
docker compose up --build
```

## 4bis. Appliquer les migrations SQL versionnees

Apres le premier seed, les evolutions de schema versionnees dans `sql/` peuvent etre appliquees sans reinitialiser le volume :

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
