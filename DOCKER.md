# Docker local

Cette configuration sert a lancer une version locale reproductible du projet avec :

- PHP 8.2 + Apache
- MariaDB 11.4
- Mailpit pour tester les emails en local
- phpMyAdmin pour inspecter la base locale
- prise en charge de `short_open_tag`
- acceptation de `localhost`, `demo.localhost`, `instantz.localhost` et `trajets.localhost`

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

## 2. Dump SQL local

Le dump principal local est attendu dans :

`docker/db/init/00-base.local.sql`

Ce fichier est ignore par Git pour pouvoir utiliser un dump local sans le publier.
Si tu veux utiliser un autre dump plus tard, garde simplement le meme nom de fichier.

Au premier demarrage :

- MariaDB importe `00-base.local.sql`
- MariaDB utilise `utf8mb4` par defaut grace a `docker/db/conf.d/charset.cnf`
- le dump principal contient deja les organisations de prod et la structure de demo

## 3. Lancer les conteneurs

```bash
docker compose up --build
```

L'application sera disponible sur :

- `http://localhost:8080`
- `http://demo.localhost:8080`
- `http://instantz.localhost:8080`
- `http://trajets.localhost:8080`
- Mailpit : `http://localhost:8025`
- phpMyAdmin : `http://localhost:8081`

## 4. Reinitialiser la base

Les scripts d'initialisation MariaDB ne tournent qu'au premier demarrage du volume.

Pour repartir de zero :

```bash
docker compose down -v
docker compose up --build
```

## 5. Si les sous-domaines ne resolvent pas chez toi

La plupart des environnements modernes gerent `*.localhost`.

Si ce n'est pas le cas, ajouter temporairement ces entrees dans le fichier hosts :

```text
127.0.0.1 demo.localhost
127.0.0.1 instantz.localhost
127.0.0.1 trajets.localhost
```
