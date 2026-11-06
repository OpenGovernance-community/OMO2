# Jitsi local

Jitsi est inclus dans le Compose principal. Il utilise les mêmes images
officielles épinglées et les mêmes droits JWT que le profil OMO2 du VPS.
Apache fournit HTTPS sur `https://meet.localtest.me` et transmet les WebSockets.
Le frontal est construit depuis l’image officielle épinglée. `web/labels.json`
clarifie le retour à la demande d’admission via les traductions natives Jitsi :
« Je n’ai pas de mot de passe, je vais simplement attendre… », traduit dans les
huit langues OMO et leurs variantes Jitsi. Reconstruire avec
`docker compose build jitsi-web` après modification.
Le frontal respecte aussi le paramètre de langue de l’iframe OMO, avant la
langue par défaut du serveur.
Les quatre services Jitsi restent séparés ; seul le frontal rejoint le réseau
de l’application. Aucun compte Jitsi à créer.

Depuis la racine du projet, dans PowerShell 7 :

```powershell
./docker/jitsi/setup-local.ps1
docker compose up -d app jitsi-web jitsi-jicofo jitsi-jvb
docker compose exec app apache2ctl -k graceful
```

Le script conserve les clés locales existantes lors de ses exécutions suivantes.
Il configure automatiquement la même clé dans `docker/jitsi/.env.private` et
`docker/app/.env.private`, tous deux exclus de Git. Le VPS et les paramètres de
production ne sont pas modifiés. Les autres valeurs de configuration locale
sont conservées. Le rechargement Apache prend en compte le nouvel hôte virtuel
si le conteneur app était déjà démarré.

La base doit inclure la migration `sql/2026-11-06-01-video-room.sql`, livrée
avec la passerelle `/visio/`. Elle passe par le flux habituel des migrations
OMO2 ; elle est déjà appliquée dans l’environnement local préparé ici.

Ouvrir **https://localtest.me/visio/**, ou `/visio/` sur le sous-domaine local
OMO habituel, puis se connecter et créer une réunion. Ouvrir son lien dans une
fenêtre privée pour essayer la salle d’attente. Un autre compte OMO actif
rejoint directement comme participant, sans devenir animateur.

Le lien **Modifier la salle** permet de changer son nom, son organisation
facultative et les options des membres et des invités. Une organisation associée
réserve le groupe membres à ses membres actifs ; les autres comptes sont invités.
Les micros/caméras au démarrage, la caméra bloquée, le partage sur autorisation et
le lobby sont indépendants pour chaque groupe. Le propriétaire n’est pas restreint.
Appliquer aussi la migration `2026-11-06-03-video-room-settings.sql` par le flux
normal de mise à jour. Après une mise à jour du module Prosody local, recharger
le module ou redémarrer `jitsi-prosody` pour prendre en compte ses nouveaux hooks.

Un mot de passe facultatif d’accès direct est configurable dans cette même page.
Il permet aux personnes soumises au lobby de le contourner, sans obtenir d’autres
droits. Sans mot de passe, elles peuvent toujours attendre l’admission. Le code
est saisi directement dans le lobby Jitsi et fonctionne sans animateur déjà
présent. Le bouton de saisie reste masqué sans mot de passe configuré.
Vide à l’enregistrement : conserver le code ; case cochée : le supprimer.
Appliquer les migrations `2026-11-06-04-video-room-lobby-password.sql` puis
`2026-11-06-05-video-room-native-password.sql` et recharger le module Prosody.
Le mot de passe est chiffré en base et dans la politique signée ; Prosody seul
le déchiffre pour initialiser le mot de passe natif de la salle. Une rotation
du secret JWT nécessite de redéfinir ces mots de passe, de même que les codes
hachés du premier prototype. Aucun nouveau paramètre de connexion à fournir.
Pour un invité, OMO affiche simplement un bouton d’ouverture : le nom est
demandé dans Jitsi. Les comptes connectés conservent leur identité OMO.

Les réglages sont modifiables dans **Paramètres → Configuration du serveur →
Visioconférence Jitsi** : activation, adresse publique, identifiant JWT, clé de
signature et domaine XMPP. Pour Docker local :

- Activation : Oui.
- Adresse : `https://meet.localtest.me`.
- Identifiant JWT : `omo2`.
- Clé : celle générée par le script, déjà renseignée et masquée dans le formulaire.
- Domaine XMPP : `meet.jitsi`.

Si la clé est changée dans OMO, reporter la même valeur dans `JWT_APP_SECRET`
du fichier privé Jitsi puis recréer les services Jitsi. L’enregistrement dans
OMO ne reconfigure pas automatiquement un serveur Jitsi externe.

Le certificat local existant couvre `*.localtest.me` ; il doit être accepté par
le navigateur avant l’utilisation de l’iframe, comme pour Collabora et Etherpad.
Autoriser ensuite caméra et microphone pour le site. Le port média UDP 10000
est publié sur le PC et son IPv4 est détectée par le script : WebRTC utilise
cette adresse plutôt que la boucle locale avec Docker Desktop. Après un
changement de réseau, relancer le script puis la commande de démarrage.
Les URL `localtest.me` restent destinées aux navigateurs de ce PC.
P2P est disponible à deux participants ; le
videobridge prend le relais pour les groupes.

Jitsi ne stocke ici aucun document ni compte : ses données de réunion sont
temporaires, les liens et propriétaires sont conservés dans la base OMO.
Redémarrer les services coupe les appels en cours ; les invitations restent
réutilisables. La mémoire des conteneurs est plafonnée.

Pour arrêter uniquement la vidéo :

```powershell
docker compose stop jitsi-web jitsi-prosody jitsi-jicofo jitsi-jvb
```

## Test navigateur optionnel

`test-local-browser.cjs` utilise `puppeteer-core` et un Chrome/Chromium isolé,
avec caméra et microphone simulés. Il crée une réunion depuis OMO, vérifie
les identités, les rôles, le lobby et la progression des compteurs audio/vidéo
à trois participants via UDP 10000. Aucun périphérique physique n’est utilisé.

Créer les fixtures avec `docker compose exec -T --user www-data app php
tests/visio_browser_fixture.php create`, puis copier le JSON privé
`/tmp/omo-visio-browser-fixture.json` du conteneur dans un répertoire temporaire
local. Fournir son chemin dans `VISIO_FIXTURE_FILE` et le chemin de Chrome
dans `PUPPETEER_EXECUTABLE_PATH`, puis exécuter le script avec Node.
Les cookies de test ne doivent pas être publiés ou ajoutés au dépôt.

Toujours terminer par `docker compose exec -T --user www-data app php
tests/visio_browser_fixture.php delete` et effacer la copie locale du JSON.
Cela supprime les comptes, leurs salles et les sessions des fixtures.

Référence : [déploiement Docker officiel Jitsi](https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-docker/).
