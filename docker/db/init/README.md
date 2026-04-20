# Database init

Ordre conseille pour l'initialisation locale :

1. placer un dump complet dans `docker/db/init/00-base.local.sql`
2. lancer `docker compose up --build`

Le dump courant est deja prevu pour :

- stocker les textes en `utf8mb4`
- conserver les organisations de prod `instantz` et `trajets`
- inclure la structure de demo utile a `demo.localhost`
