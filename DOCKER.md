# Docker local

Cette configuration sert a lancer une version locale reproductible du projet avec :

- PHP 8.2 + Apache
- MariaDB 11.4
- Mailpit pour tester les emails en local
- phpMyAdmin pour inspecter la base locale
- prise en charge de `short_open_tag`
- acceptation de `localhost`, `demo.localhost`, `org1.localhost`, `org2.localhost`
- acceptation aussi d'un vrai domaine de dev partage comme `omo.test`, `org1.omo.test`, `org2.omo.test`

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

L'application sera disponible sur :

- `http://localhost:8080`
- `https://localhost:8443`
- `http://demo.localhost:8080`
- `https://demo.localhost:8443`
- `http://org1.localhost:8080`
- `https://org1.localhost:8443`
- `http://org2.localhost:8080`
- `https://org2.localhost:8443`
- `http://omo.test:8080`
- `https://omo.test:8443`
- `http://demo.omo.test:8080`
- `https://demo.omo.test:8443`
- `http://org1.omo.test:8080`
- `https://org1.omo.test:8443`
- `http://org2.omo.test:8080`
- `https://org2.omo.test:8443`
- Mailpit : `http://localhost:8025`
- phpMyAdmin : `http://localhost:8081`

Adresses de demonstration utiles :

- `admin@org1.opengov.tools`
- `member1@org1.opengov.tools`
- `admin@org2.opengov.tools`

Les codes de connexion sont envoyes dans Mailpit.

Le HTTPS local utilise un certificat autosigne genere dans l'image Docker, valable pour `localhost`, `*.localhost`, `omo.test` et `*.omo.test`.
Le navigateur affichera probablement un avertissement de securite la premiere fois : c'est normal en local.

## 4. Reinitialiser la base

Les scripts d'initialisation MariaDB ne tournent qu'au premier demarrage du volume.

Pour repartir de zero :

```bash
docker compose down -v
docker compose up --build
```

## 5. Domaine de dev recommande pour partager les cookies

Pour tester la connexion partagee entre sous-domaines, `localhost` n'est pas ideal.
Le plus simple est d'utiliser un vrai domaine local, par exemple `omo.test`.

Sous Windows, ajouter ces lignes dans `C:\Windows\System32\drivers\etc\hosts` :

```text
127.0.0.1 omo.test
127.0.0.1 demo.omo.test
127.0.0.1 org1.omo.test
127.0.0.1 org2.omo.test
```

Puis redemarrer Docker :

```bash
docker compose down
docker compose up --build
```

Ensuite, utiliser de preference :

- `http://omo.test:8080/omo/`
- `http://org1.omo.test:8080/omo/`
- `http://org2.omo.test:8080/omo/`
- `https://omo.test:8443/omo/`
- `https://org1.omo.test:8443/omo/`
- `https://org2.omo.test:8443/omo/`

En production, avec un domaine racine comme `opengov.tools`, le meme mecanisme donne :

- `https://org1.opengov.tools/omo/`
- `https://org2.opengov.tools/omo/`

Dans cette configuration, les cookies peuvent etre poses sur `.omo.test` et donc etre partages entre les sous-domaines, ce qui simule beaucoup mieux la production.

`omo.test` n'est pas "publie" par Docker sur Internet : c'est un domaine de dev local. Chaque personne qui veut l'utiliser doit ajouter les memes entrees dans son propre fichier `hosts`, ou utiliser un DNS local equivalent.

## 6. Si les sous-domaines `.localhost` ne resolvent pas chez toi

La plupart des environnements modernes gerent `*.localhost`.

Si ce n'est pas le cas, ajouter temporairement ces entrees dans le fichier hosts :

```text
127.0.0.1 demo.localhost
127.0.0.1 org1.localhost
127.0.0.1 org2.localhost
```
