Bienvenue sur le repository de OMO2, la nouvelle version de OpenMyOrganization.

10 ans après la première version, nous lançons un chantier d'envergure: repenser le logiciel en intégrant nos dix années d'expériences, autant d'un point de vue des fonctionnalités que de l'ergonomie ou de la prise en main.

Accompagnez-nous dans cette grande aventure, en testant les nouvelles fonctionnalités, en soutenant financièrement son développement ou en amenant des propositions d'amélioration, entre autre sur cette plateforme.
Vous pouvez accéder à la version actuellement en ligne ici: https://systemdd.ch

Être proposé en OpenSource a toujours été l'objectif de OpenMyOrganization, mais une architecture de base un peu bacale et de grosses failles de sécurité ont empêché le projet de se concrétiser. Dans cette nouvelle version, nous espérons commencer sur de meilleures base et porter d'emblée une attention à ces aspects.

## Docker en local

Le projet peut maintenant etre lance en local avec Docker pour obtenir un environnement de test reproductible.

### Prerequis

- Docker Desktop en cours d'execution
- Le dump SQL principal place dans `docker/db/init/00-base.local.sql`

### Demarrage rapide

```bash
docker compose down -v
docker compose up --build
```

L'application devient alors accessible sur :

- `http://localhost:8080`
- `http://demo.localhost:8080`
- `http://instantz.localhost:8080`
- `http://trajets.localhost:8080`

Interface email locale :

- `http://localhost:8025`

Interface base de donnees :

- `http://localhost:8081`

### Ce que fait l'initialisation

- importe le dump principal `docker/db/init/00-base.local.sql`
- utilise MariaDB configuree en `utf8mb4` par defaut pour accepter les emoji
- charge le dump complet, qui contient deja les organisations de prod et la structure de demo

### Configuration Docker

Le conteneur web utilise `docker/app/.env`, ce qui evite d'employer par erreur un `.env` local de production.

Pour plus de details, voir [DOCKER.md](DOCKER.md).
