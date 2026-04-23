Bienvenue sur le repository de OMO2, la nouvelle version de OpenMyOrganization.

10 ans après la première version, nous lançons un chantier d'envergure: repenser le logiciel en intégrant nos dix années d'expériences, autant d'un point de vue des fonctionnalités que de l'ergonomie ou de la prise en main.

Accompagnez-nous dans cette grande aventure, en testant les nouvelles fonctionnalités, en soutenant financièrement son développement ou en amenant des propositions d'amélioration, entre autre sur cette plateforme.
Vous pouvez accéder à la version actuellement en ligne ici: https://systemdd.ch

Être proposé en OpenSource a toujours été l'objectif de OpenMyOrganization, mais une architecture de base un peu bacale et de grosses failles de sécurité ont empêché le projet de se concrétiser. Dans cette nouvelle version, nous espérons commencer sur de meilleures base et porter d'emblée une attention à ces aspects.

## Docker en local

Le projet peut maintenant etre lance en local avec Docker pour obtenir un environnement de test reproductible.

### Prerequis

- Docker Desktop en cours d'execution
- Le seed SQL publie dans `docker/db/init/00-base.seed.sql`

### Demarrage rapide

```bash
docker compose down -v
docker compose up --build
```

L'application devient alors accessible sur :

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
- `http://org1.omo.test:8080`
- `https://org1.omo.test:8443`
- `http://org2.omo.test:8080`
- `https://org2.omo.test:8443`

Interface email locale :

- `http://localhost:8025`

Interface base de donnees :

- `http://localhost:8081`

Comptes de demonstration conseilles :

- `admin@org1.opengov.tools`
- `member1@org1.opengov.tools`
- `admin@org2.opengov.tools`

Les codes de connexion arrivent dans Mailpit.

### Domaine local partage pour tester les sous-domaines

Pour tester correctement les cookies partages entre organisations, il est recommande d'utiliser un vrai domaine local comme `omo.test` plutot que `localhost`.

Sous Windows, ajouter ces lignes dans `C:\Windows\System32\drivers\etc\hosts` :

```text
127.0.0.1 omo.test
127.0.0.1 demo.omo.test
127.0.0.1 org1.omo.test
127.0.0.1 org2.omo.test
```

Puis relancer les conteneurs :

```bash
docker compose down
docker compose up --build
```

Ensuite, utiliser de preference :

- `https://omo.test:8443/omo/`
- `https://org1.omo.test:8443/omo/`
- `https://org2.omo.test:8443/omo/`

En production, avec un domaine racine comme `opengov.tools`, cela donne par exemple :

- `https://org1.opengov.tools/omo/`
- `https://org2.opengov.tools/omo/`

Le certificat HTTPS local est autosigne. Le navigateur affichera donc un avertissement de securite du type `ERR_CERT_AUTHORITY_INVALID` tant que ce certificat n'est pas ajoute comme certificat de confiance sur la machine. En local, il est possible de continuer manuellement via les options avancees du navigateur.

### Ce que fait l'initialisation

- importe le seed principal `docker/db/init/00-base.seed.sql`
- utilise MariaDB configuree en `utf8mb4` par defaut pour accepter les emoji
- charge le dump complet, qui contient deja les organisations de demo `Org1` et `Org2` ainsi que la structure de demo

### Variante locale privee

Si tu veux ajouter des donnees locales non publiees par-dessus le seed versionne, tu peux deposer un fichier SQL supplementaire dans `docker/db/init/`, par exemple :

- `docker/db/init/99-local.override.local.sql`

Ce type de fichier est ignore par Git, mais sera bien importe par MariaDB au premier demarrage du volume, apres le seed principal.

### Configuration Docker

Le conteneur web utilise `docker/app/.env`, ce qui evite d'employer par erreur un `.env` local de production.

Pour plus de details, voir [DOCKER.md](DOCKER.md).
