# Controle de disponibilite de la visio

## Adresses OMO

- `/visio/status.php` : vue lisible, detail par composant, heure du controle et
  bouton **Verifier maintenant**. Accessible sans compte.
- `/visio/health.php` : endpoint JSON sans session ni redirection, pour les sondes.
  GET et HEAD executent les memes controles ; HEAD ne renvoie pas de corps.
  HTTP **200** signifie que tous les controles passent ; HTTP **503** indique
  un echec, une configuration absente ou un service desactive. Aucun cache.

Exemple de reponse disponible :

```json
{"status":"ok","checks":{"configuration":"ok","database":"ok","frontend":"ok","signaling":"ok","jicofo":"ok","videobridge":"ok"}}
```

Le controle utilise uniquement l origine `JITSI_URL` configuree par le serveur.
Il ne reprend aucun parametre, cookie ou jeton du visiteur. Il verifie :

1. L activation et la validite des parametres Jitsi OMO.
2. Une connexion en lecture seule a la base OMO, via `DbObject::checkDatabaseHealth()`.
3. La disponibilite de `external_api.js` et `config.js`, avec verification de contenu.
4. L ouverture puis la fermeture d une session BOSH anonyme aupres de Prosody.
5. Le test interne Jicofo `/about/health`.
6. Le test interne du Videobridge `/about/health`.

Les requetes ont des delais limites, ne suivent pas les redirections et
verifient les certificats TLS. Une reponse HTML de connexion ou de secours ne
valide pas le service. Le JSON public ne contient ni adresses internes, ni
identites, ni statistiques de salles, ni cles. Aucune salle n est creee.

## Activation dans Docker local

Depuis la racine :

```powershell
docker compose build jitsi-web
docker compose up -d --no-deps jitsi-web jitsi-jicofo jitsi-jvb
```

Si un fichier Compose additionnel est deja utilise (par exemple
`compose.monitoring.yaml` pour le trafic), conserver aussi ce fichier et vos
overrides locaux dans la commande de recreation des services concernes.

Le frontal ajoute deux routes exactes `/omo-health/jicofo` et
`/omo-health/videobridge`. Elles ne donnent acces qu aux tests de sante.
Les API REST completes et leurs ports restent dans le reseau prive Docker.
Jicofo active son API REST et ses controles internes ; le JVB expose deja
son test de sante dans le reseau prive. Les alias Docker `jicofo` et `jvb`
permettent d utiliser le meme frontal sur le VPS et en local.

Pour HTTPS local signe avec mkcert, installer le certificat **racine** mkcert
dans le magasin de confiance du conteneur app, puis lancer
`update-ca-certificates`. Conserver cette installation dans votre personnalisation
Docker locale si elle doit survivre a la recreation du conteneur. Ne pas
desactiver la verification TLS pour faire passer le controle. Un certificat
public reconnu ne demande aucune configuration supplementaire en production.

## Activation sur le VPS

Le deploiement des sondes VPS peut preceder la publication OMO. Publier aussi
les fichiers OMO pour rendre disponibles les deux pages de controle. Copier la version actuelle de
`docker/jitsi/web/` (y compris `health.conf`) dans
`/opt/jitsi/omo-integration/web/`, ainsi que `docker/jitsi-vps/compose.omo.yaml`
dans `/opt/jitsi/omo-integration/compose.omo.yaml`, selon le deploiement existant.

```sh
sudo /opt/jitsi/compose.sh build web
sudo /opt/jitsi/compose.sh up -d --no-deps web jicofo
```

Recreer Jicofo interrompt momentanement la gestion des conferences : choisir
une plage sans appel. Aucun changement du bloc Caddy existant n est requis :
il transmet deja ces chemins au frontal Jitsi. Ne pas publier les ports 8080
ou 8888 et ne pas ajouter un proxy public vers les prefixes REST complets.
Attendre la fin du demarrage et verifier `/visio/status.php`.

### Etat du VPS au 7 novembre 2026

Sondes deployees sur `meet.opengov.tools` : `/omo-health/jicofo` et
`/omo-health/videobridge`. GET et HEAD renvoient HTTP 200 ; le frontal utilise
GET vers les tests internes, y compris pour une sonde HEAD. Les chemins voisins
restent refuses et aucun port REST n est publie. Le frontal et Jicofo ont ete
recrees apres verification de l absence de conference sur le pont ; Prosody et
le Videobridge ont conserve leurs conteneurs.

Sauvegarde privee et script de retour arriere :
`/opt/jitsi/backups/20261107T103423Z-health/`. Le script `rollback.sh` restaure
la configuration et l image du frontal precedentes. Les verifications depuis
Internet couvrent les sondes GET/HEAD, les ressources web et une session BOSH
ouverte puis fermee sans creer de salle. La publication des pages OMO et la
creation du moniteur UptimeRobot restent des etapes distinctes.

## Configuration UptimeRobot

Dans le compte UptimeRobot :

1. Creer un moniteur **HTTP(s)**, par exemple `OMO - Visioconference`.
2. URL : `https://beta.opengov.tools/visio/health.php` pour l instance beta,
   ou la meme route sur le domaine OMO que vous voulez surveiller.
3. Methode GET (ou HEAD pour un controle du code HTTP uniquement).
4. Considerer 200 comme disponible et 503 comme indisponible ; ne pas classer
   503 parmi les codes acceptes. Choisir par exemple une frequence de 5 minutes.
5. Associer les contacts de notification souhaites au moniteur.
6. Facultatif : utiliser un moniteur **Keyword**, methode GET, qui recherche
   la presence exacte de `"status":"ok"`.

L URL doit etre publique, accessible sans connexion et exclue des caches
intermediaires. Les sous-sondes `/omo-health/*` ne suffisent pas seules :
utiliser l endpoint OMO qui agrege tous les controles.

References officielles : [types de moniteurs UptimeRobot](https://help.uptimerobot.com/en/articles/11358441-understanding-uptimerobot-monitor-types-a-guide-to-essential-services),
[controle interne Jicofo](https://github.com/jitsi/jicofo/blob/master/doc/health-checks.md).

## Portee et controle periodique des appels

Ces sondes detectent notamment une panne de base, de frontal, de Prosody,
de Jicofo ou de JVB. Elles ne prouvent pas qu un appel complet fonctionne :
elles ne testent ni la correspondance de la cle JWT OMO/Jitsi, ni les droits
Patreon, ni les migrations des salles, ni les WebSockets du navigateur, ni
les flux UDP depuis Internet. Un pare-feu qui bloque UDP 10000 peut laisser
toutes les sondes HTTP au vert.

Tester regulierement une vraie salle a **trois participants**, depuis des
reseaux distincts, pour forcer l utilisation du pont JVB plutot que du P2P.
Verifier son et image dans les deux sens, salle d attente, admission et partage
d ecran. Refaire ce controle apres une mise a jour, un changement de cle, de
DNS ou de pare-feu. Les scripts navigateur existants dans `docker/jitsi/` et
`docker/jitsi-vps/` permettent aussi un test avec medias simules ; ils doivent
rester hors de l endpoint public car ils ouvrent de vrais appels.
