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
- renseigner l origine HTTPS du site pour les liens de connexion (sans chemin, par exemple `https://exemple.org`)
- verifier l'envoi d'e-mail avec un code recu par mail
- creer automatiquement le fichier `.env`
- initialiser la base de donnees de depart

L assistant demande seulement les informations du site, les acces MySQL, le compte administrateur et le SMTP necessaire a la verification e-mail. Les cles Patreon, Telegram, IA et autres integrations ne sont pas demandees a cette etape. Le fichier cree reprend les variables de `.env.example` ; les secrets de limitation des connexions et de chiffrement sont generes automatiquement pour cette installation.

Apres connexion avec l administrateur, activer le mode administrateur du site puis ouvrir **Parametres > Administration du serveur**. Les reglages generaux restent visibles ; les onglets des services permettent de completer les integrations optionnelles. Sous les onglets, l accordeon **Reglages techniques**, ferme au depart, propose six rubriques repliees : adresse et hebergement, MySQL, connexions et alertes, cles de protection, maintenance, journaux et diagnostic. Les aides **?** expliquent leur role, les valeurs automatiques et les cas qui demandent une intervention. Son avertissement rappelle les risques de coupure du site ou de perte d acces aux donnees chiffrees ; les acces MySQL demandent en plus de cocher explicitement leur modification. **Push** et **MCP** ont leurs onglets ; le chemin PHP des notifications se trouve dans Push. Les variables inconnues ne sont pas ajoutees au formulaire, mais restent conservees dans le fichier. Les anciens parametres GitHub Projects inutilises sont retires au prochain enregistrement. Les valeurs deja configurees restent conservees lorsqu un secret ou un acces MySQL est laisse vide. Les cles de chiffrement doivent etre sauvegardees et conservees pour pouvoir relire les donnees existantes.

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

### Acces IA du createur Patreon

Le createur peut utiliser les fonctions IA sans contribuer a sa propre campagne.
Renseigner dans le `.env` de chaque serveur concerne, ou dans les parametres
serveur / Integrations, l identifiant numerique de son compte Patreon :

```env
PATREON_CREATOR_USER_ID=123456789
```

Remplacer cet exemple par l ID utilisateur Patreon reel, affiche en lecture seule
dans Mon profil > Patreon, sous ID utilisateur Patreon, apres connexion et
synchronisation du compte. Ce n est ni l ID de campagne, ni l ID utilisateur OMO,
ni le nom public Patreon. Garder la valeur vide pour desactiver cette exception.

Le compte OMO doit rester relie a ce compte Patreon. La comparaison porte sur
l identite obtenue par OAuth, pas sur une valeur fournie dans le profil utilisateur.
Les autorisations Patreon demandees restent limitees a `identity`. La configuration
OpenAI et la configuration Patreon habituelles restent necessaires. Les autres
comptes conservent la regle de contribution active strictement positive ; les
autres avantages Patreon ne sont pas modifies par cette exception IA.

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

Sans chemin explicite, les evenements JSONL sont ecrits dans `../log/sql-performance/sql-performance-AAAA-MM-JJ.jsonl`, hors de la racine publique. `DB_QUERY_LOG_PATH` attend un fichier : un dossier existant ou un chemin termine par `/` ou `\` fait reprendre ce chemin quotidien par defaut. Un chemin relatif est resolu depuis la racine du projet. `RUNTIME_LOG_DIR` permet de changer le repertoire commun utilise par les journaux qui n ont pas de chemin specifique.

Chaque requete conserve sa duree, son type, son empreinte, son appelant et son nombre de lignes. Le journal ajoute aussi un resume par requete HTTP avec le temps SQL cumule et le statut HTTP (`status_code`), en distinguant les erreurs HTTP des erreurs SQL (`error_count`). Les valeurs liees, les litteraux SQL, les messages d erreur et les parametres de l URL ne sont pas enregistres.

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

## Configuration IA : texte, traduction et transcription

Infomaniak est propose comme fournisseur explicite (`AI_PROVIDER=infomaniak`). Dans l onglet IA, renseigner le champ editable "Product ID Infomaniak" avec l identifiant numerique du produit AI Services dans le Manager Infomaniak, puis saisir le token et cliquer sur "Actualiser les modeles", directement sous le token, avant d enregistrer. Le Product ID est commun aux modeles de cette connexion et conserve dans `AI_INFOMANIAK_PRODUCT_ID`. Quand il est renseigne, OMO appelle directement `GET /2/ai/{product_id}/openai/v1/models`, sans imposer l acces a `GET /1/ai`. Champ vide : tentative de detection via `GET /1/ai` ; un produit unique est complete automatiquement, plusieurs produits deviennent des suggestions pour le champ editable. La liste des modeles alimente les selects principal et de traduction ; le catalogue facultatif `GET /1/ai/models` permet d ecarter les types non textuels identifies. La traduction peut reprendre le modele principal.

Enregistrer conserve `AI_API_KEY`, `AI_INFOMANIAK_PRODUCT_ID`, `AI_MODEL` et `AI_TRANSLATION_MODEL`. L URL Infomaniak est construite automatiquement, sans utiliser `AI_BASE_URL`. Le bouton affiche le chargement en cours, avec un delai maximum ; les erreurs de token, droits, limitation API, reponse invalide ou connexion sont affichees dans les notifications communes. Un deverrouillage expire est signale sans recharger le formulaire ni effacer le token saisi. Les appels de decouverte sont reserves a l administration deverrouillee et ne generent aucun texte ; la cle enregistree n est jamais renvoyee au navigateur. Un changement de token, de produit ou de modele exige une selection issue de la decouverte avec ce token. Les reglages inchanges peuvent etre conserves pendant une panne de l API. La decouverte n est pas executee a chaque question ; le bouton permet d actualiser les listes avec le token conserve si Infomaniak est deja configure. La transcription garde son fournisseur independant. Les appels texte et les outils du chat utilisent Chat Completions v2, avec `stream=false` et `max_completion_tokens`.

References Infomaniak : [produits](https://developer.infomaniak.com/docs/api/get/1/ai), [modeles du produit](https://developer.infomaniak.com/docs/api/get/2/ai/%7Bproduct_id%7D/openai/v1/models) et [conversations](https://developer.infomaniak.com/docs/api/post/2/ai/%7Bproduct_id%7D/openai/v1/chat/completions). Le support des outils depend du modele ; la presence dans la liste ne garantit pas ses capacites.

Transcription Infomaniak : choisir `TRANSCRIPTION_PROVIDER=infomaniak`. Le select propose **Whisper V3**, identifiant `whisper`. Les champs `TRANSCRIPTION_API_KEY`, `TRANSCRIPTION_INFOMANIAK_PRODUCT_ID` et `TRANSCRIPTION_MODEL` sont propres au service audio. Si la cle et le Product ID audio sont vides, leur repli sur `AI_API_KEY` et `AI_INFOMANIAK_PRODUCT_ID` n est autorise que lorsque le fournisseur de texte est lui aussi Infomaniak. Avec un autre fournisseur de texte, renseigner les acces audio separement. Lors d un changement de fournisseur texte, les acces audio effectifs deja enregistres sont conserves independamment ; une ancienne cle audio OpenAI, Groq ou Mistral n est jamais envoyee automatiquement a Infomaniak.

L audio est envoye en multipart sur `POST /1/ai/{product_id}/openai/audio/transcriptions`. Cette API renvoie une tache asynchrone : OMO suit `GET /1/ai/{product_id}/results/{batch_id}`, puis recupere le texte ou utilise le chemin de telechargement fixe du meme produit. Les URL arbitraires des reponses ne sont jamais suivies. Le traitement est borne a 110 secondes pour rester adapte aux dictees et memos courts ; un depassement ou un echec est signale a l utilisateur. La limite d envoi OMO reste de 25 Mo. [Documentation Infomaniak audio](https://developer.infomaniak.com/docs/api/post/1/ai/%7Bproduct_id%7D/openai/audio/transcriptions).

Dans l administration du serveur, l onglet IA contient deux rubriques :

- Texte et traduction : `AI_PROVIDER` (`openai`, `anthropic`, `mistral`, `infomaniak`, `openai_compatible` ou `disabled`), `AI_API_KEY`, `AI_MODEL` et `AI_TRANSLATION_MODEL`. Les deux modeles appartiennent au meme fournisseur ; un modele plus simple peut servir uniquement aux traductions. Avec Claude, Mistral ou une API compatible, renseigner explicitement le modele principal (par exemple `mistral-small-latest`). Mistral utilise directement `https://api.mistral.ai/v1/chat/completions`. Un changement de fournisseur ou de destination API exige de saisir explicitement la cle du service choisi.
- Avec `openai_compatible`, `AI_BASE_URL` designe une adresse de base HTTPS sans `/chat/completions`, sans identifiants ni parametres. OpenRouter est preconfigure : `https://openrouter.ai/api/v1`. Une autre API au meme format peut etre saisie, par exemple Mistral (`https://api.mistral.ai/v1`) ou Groq (`https://api.groq.com/openai/v1`). Utiliser la cle et les identifiants complets de modeles de ce service ; choisir des modeles qui prennent en charge les reponses JSON pour les outils qui les demandent. Le client ajoute `/chat/completions` et ne suit pas les redirections HTTP. Cette adresse est ignoree pour les choix OpenAI, Claude et Mistral, et ne modifie pas le service audio.
- Transcription audio : `TRANSCRIPTION_PROVIDER` (`openai`, `groq`, `mistral`, `infomaniak` ou `disabled`), `TRANSCRIPTION_API_KEY` et `TRANSCRIPTION_MODEL`. Cette configuration s applique aux dictees dans les documents et aux memos vocaux Telegram. Elle reste independante lorsque le texte utilise un autre fournisseur ou est desactive. Vide, le modele utilise la configuration historique OpenAI ou `gpt-4o-mini-transcribe` pour OpenAI, `whisper-large-v3-turbo` pour Groq, `voxtral-mini-latest` pour Mistral. Le formulaire adapte les modeles par defaut lors du changement de service, en conservant les modeles personnalises. Mistral recoit le fichier en multipart sans les champs OpenAI `prompt` et `response_format`. Une cle texte ne sert de repli audio que si les deux fournisseurs sont OpenAI ou Mistral ; Groq demande une cle dediee. Changer de service exige de saisir sa cle pour eviter de transmettre la precedente a un autre fournisseur.

`AI_PROVIDER=disabled` masque les outils texte, bloque les appels directs et suspend la creation et le traitement des traductions automatiques. Les traductions deja enregistrees restent disponibles ; les travaux en attente sont conserves. Les cles et modeles sont conserves lors de la desactivation, et la transcription conserve son propre interrupteur. Pour tout desactiver, choisir `disabled` pour les deux services.

Les anciennes variables `OPENAI_API_KEY`, `OPENAI_MODEL`, `OPENAI_TRANSLATION_MODEL` et les variables de modele audio restent lues si leur equivalent generique n est pas renseigne. L interface affiche les valeurs effectives et les enregistre ensuite sous les nouveaux noms. Une cle texte Claude n est jamais utilisee comme cle de transcription. Les anciennes variables propres a l hebergement restent conservees dans le fichier.

Les fonctions texte, les traductions de l interface et les anciennes traductions a la demande partagent le meme client HTTP. Les outils utilisateur conservent leurs controles Patreon ; la generation des traductions de l interface reste un traitement serveur. Les modeles selectionnes sont utilises tels quels, sans repli automatique vers un autre modele.

References des API : [Mistral texte](https://docs.mistral.ai/resources/migration-guides), [Mistral audio](https://docs.mistral.ai/api/endpoint/audio/transcriptions), [Groq audio](https://console.groq.com/docs/speech-to-text), [Messages Anthropic](https://platform.claude.com/docs/en/api/messages/create), [Chat Completions](https://developers.openai.com/api/reference/resources/chat/subresources/completions/methods/create) et [transcription audio](https://developers.openai.com/api/docs/guides/speech-to-text).

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

## 10. Surveillance externe de la base de donnees

Configurer un controle HTTPS GET sur `https://votre-domaine/omo/health.php`, par exemple dans UptimeRobot. Aucun compte utilisateur ou jeton n est necessaire. Le controle utilise les memes variables `DB_HOST`, `DB_NAME`, `DB_USER` et `DB_PASS` que le site, y compris les surcharges locales habituelles.

- Etat attendu : HTTP **200**, avec `{"status":"ok","database":"ok"}`.
- Echec de configuration, de connexion ou de requete : HTTP **503**, avec `{"status":"error","database":"unavailable"}`.
- HEAD effectue le meme controle, mais sans corps de reponse.

Chaque appel ouvre une connexion MySQL neuve (delai de connexion limite a trois secondes) et execute uniquement `SELECT 1`, via DbObject. Il ne cree pas de session, ne lance ni maintenance ni installation, et n expose aucun identifiant, contenu de table ou detail d erreur. Les reponses ne sont pas mises en cache.

Ce controle confirme la disponibilite de la connexion et du moteur SQL ; il ne verifie pas les tables applicatives, les droits d ecriture, l integrite des donnees ou les sauvegardes. Une frequence de cinq minutes est suffisante pour une surveillance de base. Publier `omo/health.php` et la classe DbObject mise a jour ensemble avant d activer le moniteur.

### Patreon des organisations et suivi IA

- Appliquer les migrations `sql/2026-10-15-01-organization-patreon.sql` et `sql/2026-10-15-02-ai-usage.sql`, y compris sur le serveur central de connexion Patreon. Deployer aussi le nouveau callback central avant d activer les connexions d organisation sur les sites clients : un ancien callback ne connait que les comptes individuels.
- `PATREON_ORGANIZATION_MINIMUM_AMOUNT=40` definit le minimum inclusif en unites du montant nominal renvoye par Patreon, sans conversion de devise. Une valeur invalide desactive le droit d organisation.
- La configuration de l organisation permet a ses administrateurs de connecter, synchroniser ou deconnecter Patreon. Le cron `php scripts/patreon-sync.php` synchronise les personnes et les organisations ; garder son execution quotidienne.
- Le compte verifie indique par `PATREON_CREATOR_USER_ID` dispense aussi son organisation du minimum de contribution et du statut de patron actif. Il doit rester connecte via OAuth ; les limites de rattachement restent identiques.
- Le meme compte Patreon peut etre relie a une personne et une organisation. Les liens individuels deja connectes en double restent acceptes ; les nouvelles associations supplementaires sont refusees. Les rotations OAuth sont partagees entre les liens autorises de la meme identite.
- `/common/admin/stats.php` affiche les mesures par personne, organisation, origine et fonction. Les filtres de periode, organisation et origine s appliquent aux compteurs ; les filtres de performance et CalDAV ne les filtrent pas.
- `ai_usage` conserve des metadonnees et compteurs, jamais les prompts ou reponses : jetons fournis pour les modeles, caracteres JSON et octets audio, requetes API/MCP separees des jetons. Les valeurs de jetons absentes restent NULL ; aucune estimation de facturation ni quota n est applique. Les dates des mesures sont en UTC.

### Fonctions IA et autorisations API/MCP par organisation

Pour les memos Telegram, `/connect` ne demande pas d abonnement Patreon ; le compte doit avoir les droits OMO de configuration de la destination. La transcription utilise soit l abonnement personnel de l expediteur, soit l abonnement actif de l organisation du role ou projet relie au groupe/sujet Telegram. Dans ce second cas, les autres participants peuvent envoyer un vocal sans lien Patreon personnel ni compte OMO relie a Telegram. Le compte ayant configure la destination doit toujours etre actif et autorise a y creer des documents. Son abonnement personnel ne finance pas les autres participants. L abonnement d organisation ne finance ni les messages prives ni les groupes/sujets non relies. Le blocage IA de l organisation reste prioritaire sur les deux financements.

Dans **Parametres > Securite**, en mode administrateur de l organisation, deux choix independants autorisent l API REST et le serveur MCP. Aucun lien Patreon de l organisation ni abonnement de son administrateur n est necessaire pour definir ces regles. Le blocage s impose aussi aux membres ayant un abonnement individuel. Les deux acces sont bloques par defaut, y compris pour les organisations existantes. Les autorisations individuelles OAuth et les droits OMO restent requis apres activation.

Le troisieme reglage **Bloquer les fonctions IA** est decoche par defaut : l IA interne reste autorisee selon les droits habituels. Le cocher coupe le chat integre, les resumes, la reformulation, la transcription et les memos Telegram destines a cette organisation, meme pour un abonne individuel ou un administrateur du site. Le controle est relu avant les appels texte et audio ; les ecrans deja ouverts ne contournent pas le blocage. Les nouvelles traductions IA ne sont plus declenchees depuis ce contexte ; les traductions deja disponibles restent affichees. Ce blocage interne est independant des autorisations API et MCP : pour interdire aussi l acces aux donnees par des assistants externes, conserver ces deux acces bloques.

Le bouton global **Enregistrer** valide et conserve ensemble les choix IA, API, MCP et les parametres de sauvegarde, en une transaction. Aucun bouton supplementaire n est necessaire pour les acces externes. **Sauvegarder immediatement** enregistre aussi tous ces choix avant l envoi du fichier.

Les choix `ai_blocked`, `api_enabled` et `mcp_enabled` sont conserves dans le JSON `organization.parameters` existant : aucune migration SQL n est necessaire. Chaque transport externe verifie son autorisation sur les requetes authentifiees et les autorisations d ecriture, y compris pour les jetons deja emis. Si les deux sont bloques, les nouvelles connexions OAuth et les renouvellements sont refuses. Le reglage `ai_blocked` reste independant de ces deux choix.

Ces reglages sont propres a l organisation et stockes en base, contrairement aux fournisseurs IA du `.env`. Deux instances partageant la base lisent donc les memes choix des qu elles executent cette version du code ; une ancienne version ne les applique pas.
