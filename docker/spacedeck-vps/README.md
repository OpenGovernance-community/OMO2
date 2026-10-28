# SpaceDeck sur le VPS

Le projet Compose `spacedeck` est installe dans `/opt/spacedeck`. Son fichier
`.env` prive contient la configuration propre au serveur et ne doit pas etre
remplace par la configuration locale. Le proxy Caddy existant transmet
`board.opengov.tools` au service `spacedeck:9666`, sans port public supplementaire.

Ce modele utilise le Dockerfile du fork, place sous `app/`, sans le script de
creation du tableau de demonstration local. Il conserve les volumes nommes
`spacedeck_spacedeck_database` et `spacedeck_spacedeck_storage` du deploiement
precedent. Toujours conserver le nom de projet Compose `spacedeck`.

Avant une mise a jour, preparer une image separee, arreter les ecritures et
sauvegarder ensemble la base SQLite et les medias, ainsi que la configuration,
les sources et l identifiant de l ancienne image. Verifier une copie restauree
avec la nouvelle image avant de basculer. Conserver les sauvegardes hors du
repertoire publie, avec des permissions privees.

Le guide `../spacedeck/fork/docs/node24-upgrade.md` decrit les limites des
fonctionnalites optionnelles et les precautions du retour arriere. Ne jamais
demarrer l ancienne image sur une base deja migree sans restaurer la sauvegarde
correspondante et prendre en compte les modifications plus recentes.

## Publication du 23 octobre 2026

- Image active : `opengov-spacedeck:node24`, Node `24.22.1`.
- Image immuable : `sha256:1d515a29c2c0359e6b537b24318d54a3b49940d13497acaccfc9dfad1e02aec3`.
- Sources : `/opt/spacedeck/releases/node24-20261023-1055/app` ;
  `/opt/spacedeck/app` est un lien vers cette version.
- Sauvegarde privee : `/opt/spacedeck/backups/20261023-node24-1055`, acces `700`.
- Ancienne image : `opengov-spacedeck:before-node24-20261023` ; identifiant conserve
  dans `old-image.txt` au sein de la sauvegarde.

La sauvegarde contient la base, les medias, l ancien repertoire de sources,
Compose, le Dockerfile et le fichier `.env` prive. Les empreintes logiques de
toutes les tables avant et apres migration sont conservees, sans contenu des
documents. Le `.env` actif n a pas ete modifie ; le callback reste celui de
`https://beta.opengov.tools/omo/api/documents/spacedeck/access.php`.

Les 12 tests de regression ont ete executes sur le VPS, y compris les anciens
mots de passe et la compatibilite SQLite. Une copie de la vraie base a ensuite
ete restauree et validee avant bascule. Les controles publics ont verifie HTTPS,
les ressources servies, la creation/suppression d un tableau temporaire, le
refus des acces anonymes, le callback OMO et le transport WebSocket. Ils ne
remplacent pas une verification manuelle depuis une session OMO connectee.

Collabora, Caddy et la configuration DNS n ont pas ete modifies. La compilation
de futures mises a jour utilise `app/Dockerfile`, pas l ancien Dockerfile a la
racine du repertoire serveur.
