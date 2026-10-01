# Deploiement sur un site ou un serveur

Ce guide sert a installer OMO2 sur un hebergement reel, sans Docker.

Les chemins et commandes de ce guide sont relatifs a la racine du depot, pas au dossier `docs/`.

Lors du deploiement du rangement des fichiers, publier ensemble les nouveaux fichiers et le `.htaccess` racine : celui-ci conserve les anciennes URL de l accueil OMO2, de la page de migration, des exports EasyPV et OMO1, des statistiques, de la confirmation de compte et des QR codes. Les reecritures sont internes pour conserver les donnees POST. Avec FTP, transferer les nouvelles destinations avant `.htaccess`, puis retirer les anciennes copies a la racine. Avec Git, publier l ensemble dans le meme commit. Un serveur sans prise en charge de `.htaccess` doit reproduire ces routes dans sa configuration.

## 1. Cloner le depot dans le dossier du site

Se placer dans le dossier racine du site web, puis cloner le depot :

```bash
git clone -b Dev <url-du-repo> .
```

Le point final `.` est important si vous voulez copier les fichiers directement dans le dossier courant.

## 2. Installer les dependances PHP

Installer les versions verrouillees par `composer.lock` :

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
```

## 3. Ouvrir le site dans le navigateur

Si aucun fichier `.env` n'est present, le site redirige automatiquement vers `install.php`.

L'assistant permet de :

- renseigner les acces MySQL
- verifier l'envoi d'e-mail avec un code recu par mail
- choisir le mode d'acces aux organisations
- creer automatiquement le fichier `.env`
- initialiser la base de donnees de depart

Le parcours le plus simple pour une premiere installation est donc :

1. cloner le depot
2. installer les dependances PHP
3. ouvrir l'URL du site
4. suivre l'assistant
5. se connecter avec le compte admin cree pendant l'installation

## 3. Choisir le mode d'URL des organisations

Le projet supporte deux modes.

### Mode recommande sans configuration wildcard

```env
ORGANIZATION_SUBDOMAIN_ROUTING=false
```

Dans ce mode, les organisations utilisent des URL de type :

- `https://domaine.com/omo/o/1`
- `https://domaine.com/omo/o/2`

Ce mode est le plus simple si l'hebergement n'est pas configure pour accepter `*.domaine.com`.

### Mode sous-domaines

```env
ORGANIZATION_SUBDOMAIN_ROUTING=true
```

Dans ce mode, les organisations utilisent des URL de type :

- `https://org1.domaine.com/omo/`
- `https://org2.domaine.com/omo/`

Ce mode demande une configuration speciale de l'hebergement :

- DNS wildcard ou sous-domaines explicites
- serveur web capable d'accepter les sous-domaines
- idealement cookies partages entre sous-domaines

### Portee des cookies

Le projet supporte aussi un reglage de portee pour eviter qu'un environnement `dev`, `beta` ou `deploy` ne reutilise les cookies d'un autre site :

```env
COOKIE_SCOPE_MODE=auto
```

Modes disponibles :

- `auto` : isole par defaut `dev`, `beta` et `deploy` en cookies host-only, tout en gardant le partage classique sur le domaine principal
- `host` : force des cookies limites au host courant
- `environment` : partage les cookies dans un environnement du type `*.dev.domaine.com`
- `parent` : partage les cookies dans tout `*.domaine.com`

Si une instance de dev doit partager la session entre `dev.domaine.com` et `*.dev.domaine.com` sans partager avec la prod, definir aussi :

```env
COOKIE_ROOT_HOST=dev.domaine.com
```

Cette valeur devient prioritaire pour la portee reelle des cookies et pour leurs noms scopes.

## 4. Appliquer les migrations SQL versionnees

Apres l'installation initiale, ou lors d'une mise a jour du code, appliquer les migrations SQL si necessaire :

```bash
php scripts/run-migrations.php
```

Le script :

- cree automatiquement la table `sql_migration`
- applique dans l'ordre les fichiers `*.sql` qui contiennent `-- @migration`
- n'execute chaque migration qu'une seule fois par base

Si plusieurs bases doivent etre migrees :

```bash
php scripts/run-migrations.php --databases=base1,base2
```

Ou via l'environnement :

```env
DB_MIGRATION_DATABASES=base1,base2
```

## 5. Mettre le site a jour plus tard

Si le serveur autorise PHP a piloter Git et la ligne de commande, le site peut proposer la mise a jour automatiquement a l'admin du site lors du chargement de `/omo/index.php`.

Dans ce cas, le site :

- verifie la branche Git suivie par le clone local
- detecte si un commit plus recent existe sur le depot distant
- propose l'installation de la nouvelle version
- bloque les mises a jour concurrentes
- execute aussi `php scripts/run-migrations.php` a la fin

Si le serveur ne permet pas cela, rien ne sera affiche et il faudra faire la mise a jour a la main.

Procedure manuelle typique :

```bash
cd /chemin/du/site
git fetch origin Dev
git reset --hard origin/Dev
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php scripts/run-migrations.php
```

Le plus souvent, la branche a utiliser est celle que suit deja le clone du serveur, par exemple `Dev`.

Si vous avez fait des modifications locales non versionnees sur le serveur, evitez `reset --hard` et utilisez une procedure adaptee.

### Reprendre la synchronisation apres des envois FTP

Le bouton **Forcer la mise a jour** accepte aussi les fichiers non suivis qui entrent en conflit avec la version distante. Il compare le contenu local aux fichiers du commit distant avant la synchronisation :

- les fichiers identiques octet par octet ne sont pas sauvegardes ;
- les fichiers suivis modifies et les fichiers non suivis en conflit dont le contenu differe sont copies avant remplacement ;
- les fichiers non suivis sans conflit restent en place, y compris les fichiers ignores par Git qui ne bloquent pas la mise a jour ;
- un dossier qui serait remplace par un fichier est inventorie et son contenu est sauvegarde avant remplacement, sans suivre les liens symboliques ; un dossier vide ne demande aucune copie de fichier ;
- une sauvegarde impossible, un contenu de dossier qui change pendant la copie ou un depot Git imbrique bloque la synchronisation.

Les copies se trouvent par defaut dans `../log/site-update-backups/<date-identifiant>/files/`, avec les chemins relatifs d origine. Le fichier `manifest.json` indique le commit local, le commit distant, les fichiers absents, les liens symboliques, l inventaire des dossiers en conflit (y compris les dossiers vides) et les permissions. Ce sont des copies des fichiers sur disque, pas une sauvegarde de la base de donnees ou de l index Git. Le dossier est prive et doit rester hors de la racine web. Si `RUNTIME_LOG_DIR` est configure, il doit lui aussi pointer hors de la racine web. Le chemin de la sauvegarde est affiche a la fin et ecrit dans le journal PHP. La restauration est manuelle : recuperer les fichiers necessaires dans `files/`, puis comparer avant de les remettre en place. Ne pas poursuivre les envois FTP pendant une synchronisation.

Le chemin `docker/etherpad/APIKEY.txt` est un fichier monte dans Etherpad par la configuration Docker. Sur un hebergement sans Docker, il n est pas utilise par ce montage. S il a ete cree comme dossier sur le serveur, la synchronisation forcee peut le remplacer apres inventaire et sauvegarde de son contenu. Sur un serveur qui utilise Docker, verifier le montage et la configuration Etherpad avant de remplacer ce chemin.

Si l ancien synchroniseur bloque deja la production, publier d abord ce correctif dans la branche suivie par le serveur, puis transferer par FTP les fichiers de cette meme version :

1. `includes/site_update_admin.php`
2. `omo/assets/js/site-update.js`
3. `common/runtime_log.php` (helper utilise pour le dossier de sauvegarde, s il n est pas deja present)

Recharger ensuite `/omo/` et choisir **Forcer la mise a jour**. Si PHP utilise un OPcache sans verification des dates, reinitialiser le cache PHP depuis le panneau de l hebergeur. La synchronisation execute ensuite Composer et les migrations habituelles.

### Composer introuvable et finalisation interrompue

Le synchroniseur recherche Composer 2 dans le PATH, dans les emplacements usuels et dans `composer.phar` a la racine du projet. Il verifie Composer et PHP CLI avant de synchroniser le code. Un fichier PHP ou PHAR est lance avec le PHP CLI selectionne, de meme version majeure et mineure que le site.

Sur Infomaniak, Composer est normalement disponible en SSH : voir la [documentation officielle](https://www.infomaniak.com/fr/support/faq/2118/utiliser-composer-2-en-ssh-sur-votre-hebergement). Le PATH et les alias de la session SSH peuvent differer de ceux du processus PHP du site. En SSH, utiliser `composer --version` et `type -a composer` pour identifier son emplacement. Si necessaire, renseigner dans le `.env` du serveur les chemins absolus reels, sans arguments ni alias :

```env
SITE_UPDATE_COMPOSER_BINARY=/chemin/absolu/composer.phar
SITE_UPDATE_PHP_BINARY=/chemin/absolu/php
```

Ne pas utiliser php-fpm ou php-cgi. Aucun telechargement de Composer n est lance automatiquement par le synchroniseur.

Si Git a deja ete synchronise mais que Composer ou les migrations ont echoue, le bandeau propose **Finaliser**. La reprise installe les dependances depuis le verrou Composer et execute les migrations du code present, sans reset Git. L etat de reprise est conserve dans le dossier temporaire du serveur ; la sauvegarde des fichiers reste a son emplacement habituel.

Pour reprendre un echec survenu avec l ancien synchroniseur, ou si le serveur a purge son dossier temporaire :

1. Transferer par FTP `includes/site_update_admin.php`, `omo/api/parameters/site_update_run.php`, `omo/assets/js/site-update.js` et `scripts/run-migrations.php` depuis cette version.
2. Configurer les chemins ci-dessus si la detection automatique ne suffit pas.
3. Ouvrir `/omo/?site-update-complete=1` avec le mode admin du site active, puis cliquer sur **Finaliser**. Le parametre ne lance aucune operation sans confirmation et est retire apres succes.

Une finalisation peut aussi etre effectuee en SSH depuis la racine du site, avec les vrais chemins des executables : `php /chemin/composer.phar install --no-dev --prefer-dist --no-interaction --optimize-autoloader`, puis, uniquement en cas de succes, `php scripts/run-migrations.php`. Utiliser le PHP CLI correspondant a la version du site.

## 6. Points utiles

- Si le SMTP est mal configure, l'assistant d'installation teste l'envoi avec un timeout court.
- Si la base cible est vide, l'installation importe le seed de depart.
- Si la base existe deja mais ne correspond pas au seed attendu, l'installation s'arrete pour eviter un ecrasement involontaire.
- Composer doit etre disponible sur le serveur pour installer les dependances verrouillees par `composer.lock`.
- Le processus PHP doit pouvoir creer et ecrire dans `../log/`. Il est preferable de creer ce repertoire avant la premiere requete afin que les erreurs de demarrage puissent aussi y etre journalisees.

## 7. Diagnostiquer les requetes SQL lentes

Le journal est desactive par defaut. Pour enregistrer les requetes dont la duree totale atteint 50 ms :

```env
DB_QUERY_LOG_ENABLED=true
DB_QUERY_LOG_MIN_MS=50
DB_QUERY_LOG_PATH=
RUNTIME_LOG_DIR=
```

Sans chemin explicite, les evenements JSONL sont ecrits dans `../log/sql-performance/sql-performance-AAAA-MM-JJ.jsonl`, hors de la racine publique. Un chemin relatif est resolu depuis la racine du projet. `RUNTIME_LOG_DIR` permet de changer le repertoire commun utilise par les journaux qui n ont pas de chemin specifique.

Chaque requete conserve sa duree, son type, son empreinte, son appelant et son nombre de lignes. Le journal ajoute aussi un resume par requete HTTP avec le temps SQL cumule. Les valeurs liees, les litteraux SQL, les messages d erreur et les parametres de l URL ne sont pas enregistres.

## 8. Mesurer les appels de maintenance OMO

La journalisation des appels de maintenance est activee par defaut. Elle peut etre configuree avec :

```env
OMO_CRON_LOG_ENABLED=true
OMO_CRON_LOG_PATH=
```

Sans chemin explicite, tous les evenements JSONL sont ajoutes dans le fichier unique `../log/omo-cron/omo-cron.jsonl`, hors de la racine publique. Chaque ligne indique l heure, la source de l appel, son statut et sa duree totale. Les traitements en echec ne sont precises que lorsqu il y en a. Les appels provenant de l endpoint partiel, du cron HTTP, du cron CLI et d un import sont distingues. Un appel evite par le verrou porte le statut `skipped` et une raison.

Les parametres de l URL et le jeton du cron ne sont jamais enregistres. Les tentatives refusees par le cron HTTP sont comptees avec le statut `rejected` sans conserver le jeton fourni.

### Planification et verrou de maintenance

La page `/omo/` ne lance plus la maintenance dans sa reponse PHP. Le navigateur conserve un secours asynchrone, deux secondes apres le chargement complet, puis lors du retour sur l application. Ce secours ne remplace pas une planification serveur quand aucun utilisateur ne visite le site.

Configurer de preference le planificateur de l hebergement pour appeler chaque minute le script existant avec un executable **PHP CLI 8.5** explicite (pas PHP-FPM ou CGI) :

```sh
/chemin/vers/php-cli /chemin/du/site/scripts/run-omo-maintenance.php
```

Le cron HTTP existant reste disponible avec son jeton habituel. Aucun planificateur n est installe automatiquement par ce changement.

CLI, cron HTTP, import et secours navigateur partagent un verrou non bloquant par serveur/base de donnees dans `RUNTIME_LOG_DIR/omo-cron/maintenance-<empreinte>.lock` (par defaut `../log/omo-cron/`). Le navigateur evite aussi une nouvelle execution pendant 60 secondes apres une execution terminee ; les crons forces et la maintenance suivant un import ignorent ce delai, jamais le verrou. Le fichier est conserve apres execution : ne pas le supprimer pendant un traitement.

Le compte CLI et le serveur web doivent pouvoir ouvrir les memes fichiers de verrou en lecture/ecriture. Le verrou est local au systeme de fichiers : pour plusieurs serveurs applicatifs, un repertoire partage supportant `flock` ou une coordination distribuee sera necessaire. Conserver ce repertoire hors de la racine publique, comme les journaux.

## 9. Reduire les anciennes images de profil

Les nouvelles images redimensionnables sont automatiquement enregistrees en WebP. Les photos de profil sont plafonnees a 320 x 320 pixels.

Pour examiner les gains possibles sur les anciennes photos sans modifier les fichiers :

```bash
php scripts/optimize-profile-images.php
```

Pour appliquer ensuite la reduction en conservant les noms de fichiers et les URL stockees en base :

```bash
php scripts/optimize-profile-images.php --apply
```

Une sauvegarde des fichiers concernes est recommandee avant l execution sur le serveur.
