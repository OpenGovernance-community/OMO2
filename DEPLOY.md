# Deploiement sur un site ou un serveur

Ce guide sert a installer OMO2 sur un hebergement reel, sans Docker.

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

Sans chemin explicite, tous les evenements JSONL sont ajoutes dans le fichier unique `../log/omo-cron/omo-cron.jsonl`, hors de la racine publique. Chaque ligne indique uniquement l heure, la source de l appel, son statut et sa duree totale. Les traitements en echec ne sont precises que lorsqu il y en a. Les appels provenant de la visite de `/omo/`, de l endpoint partiel, du cron HTTP, du cron CLI et d un import sont distingues.

Les parametres de l URL et le jeton du cron ne sont jamais enregistres. Les tentatives refusees par le cron HTTP sont comptees avec le statut `rejected` sans conserver le jeton fourni.

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
