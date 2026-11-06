# Jitsi Meet sur le VPS

Installation independante de OMO2, dans `/opt/jitsi`, avec le projet Compose
`jitsi`. La version officielle est epinglee a `stable-11248-3`. Aucune modification
des images Collabora ou SpaceDeck n est necessaire.

## Reseau

- DNS A `meet.opengov.tools` : `179.237.104.43`. Ne pas conserver une ancienne
  entree AAAA pointant ailleurs ; IPv4 suffit pour cette premiere installation.
- Pare-feu Infomaniak : autoriser UDP 10000 depuis Internet, en plus des ports
  TCP 22, 80 et 443 existants.
- Caddy fournit HTTPS et relaie aussi les WebSockets via `jitsi-web:8000`.
- Seul le frontal rejoint le reseau partage `collabora_collabora`. Prosody,
  Jicofo et les interfaces de gestion restent sur le reseau prive Jitsi.
- Aucun nouveau port HTTP/HTTPS ni interface d administration n est expose.

## Installation

Copier ces fichiers dans `/opt/jitsi`, appartenant a l utilisateur VPS uid 1000,
puis executer `bash /opt/jitsi/install.sh`. Le script verifie l empreinte de
l archive officielle, genere des secrets distincts et prepare les dossiers
persistants avec des permissions privees. Il refuse d ecraser une installation.
Ne pas afficher `docker compose config` sans `--quiet` : il contient les secrets.

Ajouter le contenu de `Caddyfile.snippet` au Caddyfile existant apres sauvegarde,
valider sa syntaxe et effectuer un rechargement gracieux, sans recreer Caddy.

Commande de gestion :

```sh
docker compose -p jitsi --env-file /opt/jitsi/.env \
  -f /opt/jitsi/upstream/docker-compose.yml \
  -f /opt/jitsi/compose.vps.yaml ps
```

## Integration OMO2 preparee

Voir [OMO-INTEGRATION.md](OMO-INTEGRATION.md) pour la page `/visio/`, les roles
JWT et la salle d attente. Ce profil est separe et reste inactif jusqu a la
publication d OMO2. Ne pas ajouter son overlay a la commande ci-dessous avant
cette etape.

## Acces provisoire

L authentification interne protege l ouverture des reunions. Un compte
organisateur doit etre cree dans Prosody ; les invites peuvent rejoindre
une reunion ouverte. La fonction salle d attente est disponible, mais doit
etre activee pour chaque salle par son moderateur. `ENABLE_LOBBY=1` ne signifie
pas que toute nouvelle salle commence automatiquement avec cette restriction.
Un invite ne doit pas pouvoir ouvrir seul une reunion.

L integration des identites OMO2, des droits du meeting, des jetons par salle,
des roles et de l admission automatique est une etape ulterieure distincte.

## Limites et controles

Les plafonds memoire et les heaps Java sont ajustes pour un premier essai sur
le VPS 4 Go partage. Ce ne sont pas des garanties de capacite ; surveiller la
memoire, les redemarrages et la qualite lors de reunions a plusieurs. Aucun
enregistrement serveur, SIP ou transcription n est installe.

Verifier une vraie reunion a au moins trois participants, ou desactiver le P2P
pendant le test : un appel direct a deux ne valide pas le trajet UDP vers le
videobridge. Une page HTTPS qui s affiche ne suffit pas non plus.

Aucun relais TURN n est installe a cette etape. Certains reseaux d entreprise
ou mobiles bloquant UDP peuvent donc necessiter un relais supplementaire.

Avant une mise a jour, sauvegarder `.env`, les fichiers Compose et tout le
repertoire `config`, y compris les comptes Prosody. Ne pas reutiliser les
secrets d un autre service ni publier les fichiers d identifiants.

## Etat du deploiement du 5 novembre 2026

Les quatre services sont installes, le compte `organisateur` existe et le
frontal HTTPS utilise un certificat public valide sur `meet.opengov.tools`.
Le Caddyfile precedent est sauvegarde dans
`/opt/jitsi/backups/20261105-caddy/Caddyfile` ; le proxy a ete recharge sans
recreation de ses conteneurs. Les identifiants sont conserves dans
`/opt/jitsi/organisateur.credentials`, prive et hors du depot.

L ouverture UDP 10000 cote Infomaniak est verifiee. Un test Chromium depuis une
autre machine a valide une reunion avec trois participants simules : chaque
client emet des medias et decode effectivement de l audio et de la video via
le videobridge public, sur UDP 10000, avec progression des compteurs RTP. Les
identites navigateur etaient isolees ; seules des cameras et des entrees audio
fictives ont ete utilisees. Aucun micro ou camera physique n a ete sollicite.

Le meme test controle qu un invite seul ne peut pas ouvrir une reunion, que
le compte organisateur devient moderateur, que les invites attendent leur
admission et qu ils restent participants apres acceptation. Les salles de test
sont ephemeres et fermees a la fin de la verification.

## Test navigateur optionnel

`test-browser.cjs` utilise `puppeteer-core` et Chromium, uniquement sur la machine
de test, pas sur le VPS. Il suit la page de preconnexion normale pour initialiser
les medias, puis ouvre une salle aleatoire et trois contextes navigateur isoles.
Le test des dialogues correspond a la langue francaise de cette installation.

Fournir `JITSI_USERNAME` et `JITSI_PASSWORD` dans l environnement prive, sans
les inscrire dans le script, le depot ou les journaux. `JITSI_TEST_URL` vaut
`https://meet.opengov.tools` par defaut ; `JITSI_TEST_IP` permet de contourner
un ancien cache DNS pendant la verification. `PUPPETEER_EXECUTABLE_PATH`
permet d indiquer le binaire Chromium. Executer :

```sh
node test-browser.cjs
```

Il ne s agit pas d un test de charge et le succes ne garantit pas le passage
depuis tous les reseaux restrictifs sans relais TURN.
