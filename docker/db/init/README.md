# Database init

Ordre conseille pour l'initialisation locale :

1. utiliser le seed versionne `docker/db/init/00-base.seed.sql`
2. ajouter si besoin un override local du type `docker/db/init/99-local.override.local.sql`
3. lancer `docker compose up --build`

Le dump courant est deja prevu pour :

- stocker les textes en `utf8mb4`
- fournir deux organisations de demo generiques `org1` et `org2`
- inclure la structure de demo utile a `demo.localhost`
