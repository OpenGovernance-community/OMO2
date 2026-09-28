# Database init

Ordre conseille pour l'initialisation locale :

1. utiliser le seed versionne complet `docker/db/init/00-base.seed.sql`
2. ajouter si besoin un override local du type `docker/db/init/99-local.override.local.sql`
3. lancer `docker compose up --build`

Le dump courant contient le schema complet et les donnees de demonstration. Il est deja prevu pour :

- stocker les textes en `utf8mb4`
- fournir deux organisations de demo generiques `org1` et `org2`
- inclure la structure de demo utile a `demo.localhost`

Le seed a ete reimporte et controle apres les migrations jusqu'au
`2026-09-28-05-property-type-activation.sql`. Il inclut aussi les liens des
packs de parcours (`parcours.ispack` et `parcours_parcours`).

Un redemarrage avec le volume `db_data` existant conserve sa base : les fichiers
de ce repertoire ne sont lus que lors de la premiere initialisation du volume.
Pour une base existante, verifier l'historique `sql_migration` et appliquer les
migrations manquantes avec `php scripts/run-migrations.php`. Un dump peut contenir
des changements deja integres sans entree correspondante dans cet historique ;
il faut reconciler ces entrees avant de relancer les migrations concernees.

Lorsqu une migration est ajoutee a `sql/`, le seed doit etre regenere depuis
une base locale vide sur laquelle cette migration a ete appliquee. Ainsi, une
nouvelle instance Docker ne depend d aucun rejeu de migrations.
