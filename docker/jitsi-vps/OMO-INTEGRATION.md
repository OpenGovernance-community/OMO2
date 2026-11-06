# Accès Jitsi avec l’identité OMO2

## État du VPS au 6 novembre 2026

La passerelle est activée après publication sur bêta. Jitsi utilise les clés
préparées dans `omo-integration/jwt.env` et le profil `compose.omo.yaml`.
L’accueil de `meet.opengov.tools` et les invitations sans jeton reviennent vers
`https://beta.opengov.tools/visio/`. Le nom de domaine OMO ne détermine pas les
droits JWT : un autre domaine OMO correctement configuré peut utiliser le même
Jitsi. Pour promouvoir l’accueil vers la production, changer uniquement
l’origine de ces deux redirections, après publication et configuration OMO.

Gérer le service avec `sudo /opt/jitsi/compose.sh ps` ou `up -d`, pour toujours
charger le profil JWT. Les configurations antérieures sont sauvegardées sous
`/opt/jitsi/backups/` ; le chemin de la sauvegarde de bascule est enregistré dans
`omo-integration/last-backup`. Le compte organisateur provisoire n’est plus
utilisé par le profil actif.

Tests navigateur sur le VPS : premier participant sans promotion automatique,
créateur animateur, invité avant l’animateur retenu à l’extérieur, admission
depuis le lobby, refus d’un jeton d’une autre salle et décodage audio/vidéo à
trois participants simulés sur UDP 10000.

La première étape fournit `/visio/` : connexion OMO2 habituelle, création d’une
salle personnelle et invitation `/visio/<code aléatoire>`. Le créateur est le
seul animateur. Les autres comptes OMO sont participants ; les invités ouvrent
la réunion avec un bouton, puis indiquent leur nom directement dans Jitsi.
Les liens partagés ne contiennent aucun
jeton. Les rôles sont imposés par Jitsi, pas uniquement par l’interface OMO.

Le nom de salle Jitsi est opaque. Le titre choisi apparaît dans OMO et devient
le sujet de la conférence lorsque l’animateur la rejoint. Les salles restent
réutilisables. Cette étape ne les associe pas encore à des événements du calendrier.

## Réglages des salles

Le propriétaire dispose d’un lien **Modifier la salle**, dans la liste et dans
la salle ouverte. Le titre et l’organisation facultative sont modifiables sans
changer le lien d’invitation. Les champs sont rendus par `adminEdit` et enregistrés
avec la migration `2026-11-06-03-video-room-settings.sql`.

Deux groupes possèdent des réglages indépendants : membres identifiés et invités.
Avec une organisation associée, l’appartenance active à cette organisation est
requise pour le groupe membres ; un autre compte OMO reste identifié mais reçoit
les droits invités. Sans organisation, tout compte OMO actif est un membre.
Le propriétaire conserve son rôle d’animateur et n’a pas de réglages de groupe.

Chaque groupe peut commencer micro coupé et/ou caméra éteinte, avoir la caméra
bloquée, partager librement ou sur autorisation individuelle, et passer ou non
par le lobby. Les options de démarrage sont des valeurs initiales modifiables
par la personne ; le blocage caméra s’applique aussi à l’écran de préparation et
au lobby, via la politique de permissions du navigateur et un contrôle à l’entrée.
Il ne coupe pas le microphone et n’interdit pas le partage d’écran.

La politique complète et le groupe sont signés dans le JWT. Le module Prosody
utilise les listes d’autorisation natives de Jitsi pour la caméra et le partage
d’écran. Les personnes du groupe autorisé sont admises automatiquement dans ces
listes ; le modérateur peut approuver individuellement les demandes de partage.
Les versions des réglages sont monotones : un ancien jeton ne rétablit pas une
ancienne politique. Les options de démarrage et l’appartenance sont recalculées
à l’ouverture de la page ; recharger les pages déjà ouvertes après une modification.

Le propriétaire peut définir un **mot de passe d’accès direct** commun aux groupes
qui passent par le lobby. Sans mot de passe, ils peuvent toujours demander leur
admission ; le bon mot de passe permet d’entrer immédiatement, même avant
l’arrivée du propriétaire. Les groupes déjà autorisés à entrer directement
n’ont rien à saisir. Les droits caméra/écran et le rôle restent identiques.

Le mot de passe est saisi uniquement dans le champ natif du lobby Jitsi.
Le bouton de saisie est masqué si aucun mot de passe n’est configuré.
L’action de retour à la demande d’admission indique « Je n’ai pas de mot de
passe, je vais simplement attendre… ». La correction française est intégrée
dans le frontal construit depuis l’image officielle épinglée. Copier aussi
`docker/jitsi/web/Dockerfile` vers `omo-integration/web/Dockerfile` et construire
avec `sudo /opt/jitsi/compose.sh build web` avant de recréer le frontal.
OMO chiffre le mot de passe avec AES-256-GCM, une clé dérivée du secret JWT et
du nom de salle, puis transmet cette enveloppe dans la politique signée.
Prosody la déchiffre et initialise le vrai mot de passe de la salle Jitsi.
Le mot de passe en clair ne figure ni dans les jetons, ni dans les invitations,
ni dans le HTML. Aucun formulaire d’accès ou de nom supplémentaire côté OMO.

Un champ vide conserve le mot de passe ; une case permet de le supprimer.
Les modifications s’appliquent à la prochaine ouverture, sans déconnecter les
appels déjà établis. Les jetons délivrés gardent leur validité normale.
Appliquer les migrations `2026-11-06-04-video-room-lobby-password.sql` puis
`2026-11-06-05-video-room-native-password.sql` avec le code. Les mots de passe
hachés du premier prototype doivent être redéfinis une fois pour Jitsi.
Une rotation du secret JWT nécessite aussi de redéfinir les mots de passe
des salles, chiffrés avec l’ancien secret.

Déployer aussi la version correspondante de
`prosody-plugins/mod_omo_lobby_autostart.lua` sur Jitsi, puis recharger ce module.
Les anciens jetons sans réglages conservent le comportement initial pendant la
publication de l’application.

## Publication en deux temps

Ne pas activer le profil JWT avant la publication du code OMO2. La configuration
actuelle à compte organisateur continue à fonctionner pendant cette préparation.

1. Publier OMO2 normalement, avec la migration
   `sql/2026-11-06-01-video-room.sql`. Laisser la visioconférence désactivée.
2. Dans l’administration **serveur**, rubrique **Visioconférence Jitsi**, renseigner
   l’origine publique, l’identifiant d’application, le domaine XMPP interne et
   la clé privée. Pour cette installation : `https://meet.opengov.tools`, `omo2`,
   `meet.jitsi`. La clé doit correspondre exactement au secret Jitsi ; elle est
   conservée hors Git et ne doit jamais être transmise dans une URL.
3. Sur le VPS, sauvegarder `/opt/jitsi/.env` et `/opt/collabora/Caddyfile`.
   Ajouter `compose.omo.yaml` à la commande Compose existante, après
   `compose.vps.yaml`, et charger le fichier privé des deux variables
   `JWT_APP_ID` et `JWT_APP_SECRET` après le `.env` existant. Ce fichier définit
   aussi `OMO_JITSI_PLUGIN_DIR=/opt/jitsi/omo-integration/prosody-plugins` :

   ```sh
   cd /opt/jitsi
   sudo docker compose -p jitsi --env-file .env --env-file omo-integration/jwt.env \
     -f upstream/docker-compose.yml -f compose.vps.yaml \
     -f omo-integration/compose.omo.yaml up -d
   ```

4. Activer la visioconférence dans les paramètres serveur OMO2, puis vérifier
   `/visio/` avec deux comptes distincts et une fenêtre privée.
5. Pour l’accès direct depuis `meet.opengov.tools`, remplacer uniquement son bloc
   dans Caddy par `Caddyfile.omo.snippet`, adapter l’origine OMO si nécessaire,
   valider le fichier puis recharger Caddy. Les invitations Jitsi sans jeton
   reviennent ainsi vers OMO ; les appels de l’iframe avec jeton restent sur Jitsi.

`omo-integration/omo.env` sur le VPS et la copie privée locale sous
`~/.ssh/services/` contiennent les paramètres OMO prêts à reporter. Ne pas les
publier. Une même configuration OMO peut servir plusieurs domaines : les liens
d’invitation gardent le domaine courant et Jitsi utilise une clé commune.

## Sécurité et limites

- JWT HS256 de deux heures, lié à une seule salle et signé côté serveur ; nom
  et rôle déduits du compte et du propriétaire enregistré. Recharger la page
  réémet une autorisation avec la session courante. Une conférence déjà établie
  n’est pas interrompue par OMO ; une reconnexion après expiration nécessite un
  rechargement.
- Les invités ont aussi un jeton signé, avec passage obligatoire par le lobby.
  Les connexions Jitsi sans jeton sont refusées. Aucune promotion automatique du
  premier participant par Jicofo ; `token_affiliation` applique les rôles.
  Le petit module `mod_omo_lobby_autostart.lua` utilise l’événement lobby de la
  version épinglée ; le module autostart communautaire livré avec cette version
  émet encore un ancien événement sans effet. Son montage est nécessaire.
- Un compte OMO actif qui possède le lien peut rejoindre comme participant sans
  attendre. Ce n’est pas encore une liste nominative d’invités par événement.
- Pas de suppression de salle, de programmation, d’enregistrement ni de TURN
  dans cette première étape. Le nom d’un invité est déclaratif.
- Conserver les sauvegardes et la clé. Pour revenir à l’accès provisoire, relancer
  la commande Compose initiale sans `compose.omo.yaml` ni `jwt.env` et restaurer
  le bloc Caddy initial ; désactiver la passerelle OMO.

## Vérification

`php tests/visio_test.php` vérifie la création et les invitations dans une
transaction annulée, les identités et les signatures/portées/rôles des jetons.
`php tests/visio_http_test.php` vérifie les formulaires, les sessions, le refus
CSRF et l’absence de promotion via les paramètres envoyés par le navigateur.

`test-omo-browser.cjs` nécessite Chromium, `puppeteer-core` et un secret privé
fourni via `JITSI_TEST_SECRET_FILE`. Il utilise une salle aléatoire et des médias
simulés pour vérifier le participant arrivé avant l’animateur, l’animateur,
l’admission d’un invité et les refus sans jeton ou pour une autre salle.
`JITSI_TEST_URL` cible uniquement une installation utilisant déjà ce profil JWT ;
`JITSI_TEST_INSECURE=1` est réservé au certificat local de test.
`PUPPETEER_EXECUTABLE_PATH` choisit Chrome/Chromium ; `JITSI_TEST_OMO_ORIGIN`
permet de vérifier aussi la redirection des invitations sans jeton vers OMO.

Documentation primaire : [authentification Docker Jitsi](https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-docker/),
[API iframe](https://jitsi.github.io/handbook/docs/dev-guide/dev-guide-iframe/),
[modules Prosody complémentaires](https://github.com/jitsi-contrib/prosody-plugins).
