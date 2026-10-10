# Database init

Ordre conseille pour l'initialisation locale :

1. utiliser le seed versionne complet `docker/db/init/00-base.seed.sql`
2. ajouter si besoin un override local du type `docker/db/init/99-local.override.local.sql`
3. lancer `docker compose up --build`

Le script `01-etherpad.sh` cree aussi la base et l'utilisateur Etherpad lors de la
premiere initialisation. Il lit les identifiants prives de
`docker/etherpad/.env.private`, injectes dans le service `db` par Compose.

Le dump courant contient le schema complet et un jeu de donnees minimal. Il est deja prevu pour :

- stocker les textes en `utf8mb4`
- fournir deux organisations de demo generiques `org1` et `org2`
- inclure trois comptes de demo, deux racines d'organisation et les catalogues systeme

Les projets, documents, activites et autres donnees de travail de l'ancien dump
ne sont pas reinjectes lors d'un reset.

Le seed a ete reimporte et controle apres les migrations jusqu'au
`2026-09-28-05-property-type-activation.sql`. Il inclut aussi les liens des
packs de parcours (`parcours.ispack` et `parcours_parcours`).
Le schema de `meeting_profile` inclut aussi `max_duration_minutes` (migration
`2026-09-30-01-meeting-max-duration.sql`, valeur initiale 60 minutes).
Les moyens de rencontre et leur copie dans les reservations sont inclus
(`2026-09-30-02-meeting-methods.sql`).
Les calendriers externes peuvent definir des plages de disponibilite
(`external_calendar.availability_only`, migration `2026-10-02-01-external-calendar-availability.sql`).

Un redemarrage avec le volume `db_data` existant conserve sa base : les fichiers
de ce repertoire ne sont lus que lors de la premiere initialisation du volume.
Le schema inclut aussi la configuration et le suivi des sauvegardes automatiques
(`organization_backup`, migrations `2026-10-02-02-organization-backup.sql` et `2026-10-02-03-organization-backup-optional-email.sql` ; adresse e-mail complémentaire facultative).
Les sauvegardes restent desactivees sur une nouvelle installation.

Les propositions de decision acceptent des plages horaires et chaque evenement
issu d une proposition conserve un lien unique vers celle-ci
(`2026-10-03-05-decision-proposal-dates.sql`).

Les dates, les reservations, leurs agendas synchronises et les profils de prise
de rendez-vous incluent les temps de preparation et de cloture en minutes,
initialement a zero (`2026-10-05-01-calendar-time-buffers.sql`). Le seed a ete
regenere depuis une base temporaire vide, importee puis migree.
Les annotations locales sur les evenements importes sont conservees grace a
`external_calendar_event.time_buffers_local`
(`2026-10-05-02-external-event-local-time-buffers.sql`).

Les evenements OMO utilisent des durees individuelles dans `event_time_buffer`
(`2026-10-09-02-event-personal-time-buffers.sql`). Les anciennes colonnes de
`event` sont retirees. Le schema et l historique correspondants du seed ont
ete regeneres depuis une base temporaire vide, importee puis migree.

Pour une base existante, verifier l'historique `sql_migration` et appliquer les
migrations manquantes avec `php scripts/run-migrations.php`. Un dump peut contenir
des changements deja integres sans entree correspondante dans cet historique ;
il faut reconciler ces entrees avant de relancer les migrations concernees.

Le seed inclut les series de reunions (`event_recurrence`) et les liens des
occurrences, y compris la protection contre les doublons
(`2026-10-09-03-event-recurrence.sql`). Il a ete regenere depuis une base
temporaire vide, importee depuis le seed versionne puis migree ; aucune donnee
de travail locale n a ete exportee.

Lorsqu une migration est ajoutee a `sql/`, le seed doit etre regenere depuis
une base locale vide sur laquelle cette migration a ete appliquee. Ainsi, une
nouvelle instance Docker ne depend d aucun rejeu de migrations.

Les liens vers les documents reutilises entre occurrences sont inclus dans
`event_shared_document` (`2026-10-10-01-event-shared-document.sql`). La table
vide et l historique de migration ont ete regeneres depuis une copie vide
importee du seed, sans exporter les donnees locales de travail.

Le seed inclut les tables vides `mcp_oauth_client` et `mcp_oauth_grant`
des migrations `2026-10-02-04-mcp-structure-oauth.sql` et
`2026-10-02-05-mcp-refresh-replay.sql`. Aucun jeton ni acces
personnel n est fourni dans la demo. Activation et tests : `docs/MCP.md`.

Le seed inclut aussi la table vide `mcp_event_creation` et l historique de la
migration `2026-10-03-04-mcp-event-creation.sql`, pour les reessais sans doublons
des creations d evenements MCP. Aucune demande personnelle n est fournie.
