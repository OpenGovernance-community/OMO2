# Accès Jitsi avec l’identité OMO2

La première étape fournit `/visio/` : connexion OMO2 habituelle, création d’une
salle personnelle et invitation `/visio/<code aléatoire>`. Le créateur est le
seul animateur. Les autres comptes OMO sont participants ; les invités indiquent
leur nom puis attendent une admission. Les liens partagés ne contiennent aucun
jeton. Les rôles sont imposés par Jitsi, pas uniquement par l’interface OMO.

Le nom de salle Jitsi est opaque. Le titre choisi apparaît dans OMO et devient
le sujet de la conférence lorsque l’animateur la rejoint. Les salles restent
réutilisables. Cette étape ne les associe pas encore à des événements du calendrier.

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

Documentation primaire : [authentification Docker Jitsi](https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-docker/),
[API iframe](https://jitsi.github.io/handbook/docs/dev-guide/dev-guide-iframe/),
[modules Prosody complémentaires](https://github.com/jitsi-contrib/prosody-plugins).
