# Audit de sécurité du code — 18 novembre 2026

Révision examinée : `0742f081` ; arborescence de travail initialement propre.

Audit ciblé par lecture du code, recherches transversales et sondes locales. Quatre constats prioritaires : deux de gravité élevée et deux de gravité moyenne. Les niveaux ci-dessous évaluent le risque applicatif ; ils ne constituent pas des scores CVSS calculés.

L’audit initial a été réalisé sans modifier le code applicatif. Les corrections locales effectuées ensuite sont consignées ci-dessous. Aucun test offensif n’a été envoyé à un serveur de production. Ce rapport doit rester dans le dépôt privé ; le `.htaccess` actuel interdit les fichiers dont le nom commence par `SECURITY-`.

## Suivi des corrections locales — 18 novembre 2026

- **SEC-01 : corrigé dans les routes examinées.** Les téléchargements de documents, fichiers Nextcloud et anciens médias Telegram utilisent `/common/document/download.php`. Seule une liste explicite de formats PDF, images matricielles, audio et vidéo peut être servie inline. Les HTML, SVG et types inconnus sont servis en pièce jointe, avec `nosniff`. Les en-têtes MIME et les noms de téléchargement sont normalisés. Les contenus existants restent téléchargeables.
- **SEC-02 : versions corrigées installées et verrouillées.** PhpSpreadsheet 5.10.0, Symfony Config 7.4.23 et Symfony Filesystem 7.4.23. La contrainte minimale PhpSpreadsheet devient `^5.10`. L’audit Composer ne remonte plus aucun avis de sécurité ni paquet abandonné.
- **SEC-03 : corrigé.** La normalisation rejette les antislashs et caractères de contrôle et valide également le chemin de repli.
- **SEC-04 : corrigé au niveau du bootstrap partagé.** La vérification de l’origine exacte s’applique aussi aux requêtes anonymes. Une origine externe, y compris un autre sous-domaine, ne peut pas être remplacée par un jeton CSRF valide. Sans origine, un jeton lié à la session est requis et les métadonnées explicitement intersites sont refusées. Les formulaires de connexion et leurs requêtes JS transmettent le jeton. Les transports API, MCP, DAV et Telegram conservent leurs contrôles d’authentification indépendants.

Validation des corrections avec un PHP **8.5.12 portable**, téléchargé depuis windows.php.net et vérifié par SHA-256, sans modifier l’installation PHP existante :

- `tests/security_request_test.php` : OK ; normalisation et réponses HTTP réelles des protections communes et des en-têtes de fichiers, sans base de données.
- `tests/getimg_security_test.php`, `tests/dbobject_html_sanitization_test.php`, `tests/webdav_storage_test.php` : OK.
- Écriture puis lecture de fichiers XLSX, ODS et CSV, avec calcul de cellule : OK.
- Syntaxe de tous les PHP modifiés et du JS de connexion, validité Composer et audit des dépendances : OK.

Les constats et limites ci-dessous décrivent l’état initial. Les scénarios complets avec les comptes, permissions, lecteurs publics et stockages du déploiement restent à vérifier avec Docker ; aucun déploiement ni changement de base n’a été effectué.

## Périmètre et limites

Les recherches ont porté sur les points d’entrée PHP, l’authentification, le bootstrap partagé, les autorisations, le socle SQL des dbObject, les téléversements et téléchargements, les intégrations HTTP, les exports/imports, OAuth/MCP, les webhooks et les restrictions Apache. Les classes et endpoints sensibles ont ensuite fait l’objet de lectures ciblées. Les bibliothèques tierces ont été contrôlées avec `composer --no-plugins audit --locked --format=json`.

Il ne s’agit pas d’une lecture exhaustive de chaque ligne ni d’un pentest du déploiement réel. Docker est indisponible : la connexion au moteur Docker Desktop échoue. Le PHP local est 8.2.16, alors que la cible du projet est PHP 8.5. Les sondes locales et le test autonome mentionnés plus bas ne remplacent donc pas la validation d’intégration sous PHP 8.5. La configuration effective de l’hébergement, du stockage distant et de ses éventuels en-têtes supplémentaires reste à vérifier.

## Synthèse des constats

| Référence | Gravité | Constat | Niveau de vérification |
| --- | --- | --- | --- |
| SEC-01 | Élevée | Fichiers actifs servis sur l’origine applicative | Chaîne de code identifiée ; scénario navigateur complet à confirmer localement |
| SEC-02 | Élevée | Lecture de tableurs avec une dépendance vulnérable | Versions et avis confirmés ; chemin de lecture présent ; charge malveillante non exécutée |
| SEC-03 | Moyenne | Redirection externe via une barre oblique inverse dans `return_to` | Fonction réelle reproduite localement et destination vérifiée par le parseur URL de Node |
| SEC-04 | Moyenne | Connexion anonyme sans contrôle CSRF | Absence de contrôle identifiée dans le chemin de code ; scénario HTTP complet à confirmer |

## SEC-01 — Contenu HTML/SVG exécutable sur le domaine de l’application

**Emplacements :** `omo/api/documents/upload/download.php:129`, `omo/api/documents/nextcloud/file.php:39`, `class/dbobject/organization.class.php:1635`, `class/dbobject/organization.class.php:2158`, `class/dbobject/document.class.php:3828`, `.htaccess:15`.

La route de téléchargement des documents accepte `inline=1` pour tous les types de fichiers dans son chemin authentifié normal. La route Nextcloud fait également dépendre `Content-Disposition: inline` du seul paramètre `inline`. Le type MIME distant est retransmis au navigateur. À l’envoi, les deux méthodes de stockage privilégient le type MIME fourni dans le multipart, et ne font une détection locale que si ce type est vide.

La branche de lecture par code permet aussi l’affichage inline des fichiers classés comme images. Cette classification inclut `image/svg+xml` et l’extension `svg`. Un SVG non nettoyé devient un document actif lorsqu’il est ouvert directement dans un onglet, même si son affichage dans une balise `img` est inerte.

**Scénario :** un utilisateur autorisé à créer un document téléverse un HTML ou SVG contenant un script, puis fait ouvrir à un autre utilisateur autorisé l’URL de téléchargement avec `inline=1`. Pour un HTML, le chemin authentifié suffit. Pour un SVG, la branche de lecture par code est également concernée. Un fichier actif déjà présent dans un dossier Nextcloud accessible présente le même risque.

**Impact :** exécution de JavaScript avec l’origine de l’application, lecture des réponses accessibles au compte de la victime et actions avec sa session. `HttpOnly` limite la lecture directe des cookies, mais n’empêche pas ces requêtes. `nosniff` ne neutralise pas un contenu explicitement servi comme HTML ou SVG. La CSP du dépôt ne définit pas de restriction sur les scripts ni de `sandbox`.

**Préconditions :** stockage distant configuré, possibilité de déposer le fichier, victime ayant accès à celui-ci, et ouverture du lien. L’exploitabilité exacte dépend aussi du type MIME renvoyé par le stockage et des en-têtes ajoutés par l’hébergeur.

**Correction proposée :** appliquer une liste explicite de types inertes autorisés en inline sur toutes les routes de fichiers. Forcer le téléchargement des HTML, SVG et autres formats actifs, ou les servir sur une origine séparée sans cookies applicatifs. Détecter le MIME côté serveur ; cela complète l’isolation sans la remplacer. Pour les prévisualisations actives indispensables, définir une politique `sandbox` adaptée.

**Validation attendue :** téléverser un HTML et un SVG de démonstration inoffensifs, puis ouvrir leurs URL directement et depuis les lecteurs avec une seconde session. Vérifier qu’aucun script ne s’exécute sur l’origine de l’application. Couvrir les accès authentifiés, par code lecteur et via Nextcloud.

## SEC-02 — Dépendances vulnérables utilisées pour lire les tableurs

**Emplacements :** `composer.lock:1635`, `composer.lock:2396`, `composer.lock:2646`, `common/stats_spreadsheet_sync.php:132`, `common/spreadsheet.php:5`.

Le verrouillage contient PhpSpreadsheet **5.9.0**, Symfony Config **7.4.16** et Symfony Filesystem **7.4.15**. Composer remonte six avis de sécurité pour ces trois paquets. Ce nombre représente des avis sur les dépendances présentes, et non six exploits démontrés dans l’application.

| Paquet | Avis | Risque publié | Version corrigée sur la branche utilisée |
| --- | --- | --- | --- |
| PhpSpreadsheet | [GHSA-j2w7-387v-m2rq](https://github.com/PHPOffice/PhpSpreadsheet/security/advisories/GHSA-j2w7-387v-m2rq) | Épuisement CPU/mémoire à la lecture ODS | 5.10.0 |
| PhpSpreadsheet | [GHSA-3x94-m842-937c](https://github.com/PHPOffice/PhpSpreadsheet/security/advisories/GHSA-3x94-m842-937c) | Contournement du contrôle XML ; déni de service, XXE selon configuration | 5.10.0 |
| PhpSpreadsheet | [GHSA-rxj4-h86g-25gc](https://github.com/PHPOffice/PhpSpreadsheet/security/advisories/GHSA-rxj4-h86g-25gc) | Contournement du contrôle XML ; déni de service, XXE selon configuration | 5.10.0 |
| PhpSpreadsheet | [GHSA-2phh-27fw-q4gc](https://github.com/PHPOffice/PhpSpreadsheet/security/advisories/GHSA-2phh-27fw-q4gc) | Contournement de restrictions SSRF lors du chargement d’images | 5.10.0 |
| Symfony Config | [GHSA-5jff-8h8q-r5qx](https://github.com/symfony/symfony/security/advisories/GHSA-5jff-8h8q-r5qx) | Lecture locale via le parseur XML | 7.4.23 |
| Symfony Filesystem | [GHSA-6prc-h956-hxjq](https://github.com/symfony/symfony/security/advisories/GHSA-6prc-h956-hxjq) | Mauvaise interprétation de racines de chemins | 7.4.23 |

Les versions corrigées ci-dessus correspondent à ces avis ; elles ne dispensent pas d’un nouvel audit lors de la mise à jour.

**Chemin applicatif établi :** les statistiques téléchargent un document tableur depuis le stockage de l’organisation, créent le lecteur avec `IOFactory::createReaderForFile()` puis exécutent `$reader->load()`. Les formats acceptés incluent ODS et XLSX. `setReadDataOnly(true)` ne limite pas le nombre de cellules matérialisées. Un tableur ODS piégé peut donc atteindre le lecteur vulnérable lorsqu’il est choisi comme source d’un indicateur. L’avis ODS décrit une expansion de lignes sans borne permettant un déni de service avec un petit fichier. [Avis de l’éditeur](https://github.com/PHPOffice/PhpSpreadsheet/security/advisories/GHSA-j2w7-387v-m2rq).

**Limite :** aucune lecture arbitraire de fichiers ni SSRF n’a été démontrée ici. Les deux avis XML précisent des conditions supplémentaires pour XXE, notamment PHP antérieur à 8 ou l’activation de `LIBXML_NOENT`. Aucun appel applicatif direct aux primitives Symfony concernées n’a été identifié dans les chemins examinés. L’avis sur les images n’est pas une preuve que le mode de lecture actuel charge ces images.

**Correction proposée :** mettre à jour les trois paquets et leurs dépendances nécessaires vers des versions corrigées compatibles avec PHP 8.5, régénérer le verrouillage puis relancer l’audit. Vérifier la synchronisation des indicateurs ODS/XLSX/CSV. Traiter les tableurs non fiables avec des limites de ressources et, si possible, dans un processus isolé.

## SEC-03 — Redirection externe malgré la normalisation des chemins locaux

**Emplacements :** `common/auth.php:184`, `common/auth.php:2664`, `common/auth.php:3176`, `common/auth.php:3504`.

`commonNormalizeLocalPath()` rejette les schémas absolus et les chaînes commençant par `//`, mais accepte `/\audit-redirect.invalid/path`. Les URL de retour sont ensuite utilisées dans des en-têtes `Location` et dans les redirections de l’interface de connexion.

**Reproduction locale :** appel de la fonction réelle du projet et de `commonBuildLoginFeedbackUrl()` sous PHP 8.2.16 :

```text
Entrée : /\audit-redirect.invalid/path
Sortie : /\audit-redirect.invalid/path?login_error=invalid
```

Le parseur URL WHATWG de Node 24 résout cette sortie, avec une base `https://app.example`, vers :

```text
https://audit-redirect.invalid/path?login_error=invalid
```

**Scénario :** fournir `return_to=/%5Caudit-redirect.invalid/path` à un flux de connexion utilisant cette normalisation. La page GET de vérification conserve ce retour et soumet automatiquement le formulaire ; les réponses d’erreur POST construisent une redirection à partir de celui-ci. Le chemin complet avec serveur et base doit encore être rejoué localement.

**Impact :** redirection vers un site de phishing depuis un parcours de connexion légitime. Cette observation seule ne démontre pas une prise de contrôle de compte ni un contournement des adresses OAuth autorisées.

**Correction proposée :** rejeter les antislashs et caractères de contrôle avant d’accepter un chemin local, conserver un seul slash initial, et appliquer la même règle aux valeurs de repli. Ajouter des cas de régression pour `/\hôte`, `//hôte`, les schémas explicites et les chemins locaux valides. Vérifier aussi la destination finale dans un navigateur.

## SEC-04 — Connexion forcée au compte d’un attaquant par CSRF

**Emplacements :** `common/request_security.php:59`, `common/login_password.php:6`, `common/auth.php:3504`, `common/auth.php:3418`.

Le garde partagé quitte immédiatement si aucun utilisateur n’est connecté. Le gestionnaire de connexion par mot de passe accepte ensuite les champs POST sans contrôle d’origine ni jeton de préconnexion. Une session authentifiée existante bénéficie du garde ; le problème concerne le navigateur anonyme.

**Scénario :** un attaquant possède un compte valide avec connexion par mot de passe autorisée, sans TOTP. Une page externe soumet un formulaire de navigation vers `/common/login_password.php` avec les identifiants de ce compte. Le navigateur anonyme de la victime peut recevoir une session pour le compte de l’attaquant. La victime peut ensuite y enregistrer des documents ou des informations en pensant utiliser son propre espace.

**Impact :** confusion d’identité et divulgation des informations que la victime enregistre ensuite dans le compte contrôlé par l’attaquant. Cela ne connecte pas l’attaquant au compte personnel de la victime. Une navigation POST peut créer une nouvelle session ; l’absence d’envoi d’un ancien cookie `SameSite=Lax` ne suffit donc pas à protéger ce cas. OWASP décrit ce risque et recommande une protection sur le formulaire de connexion. [Recommandations OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#login-csrf).

**Correction proposée :** contrôler les origines des connexions avant authentification et exiger un jeton lié à la session de préconnexion lorsque l’origine est absente, en réutilisant les primitives partagées. Préserver la régénération de session après authentification. Vérifier séparément le fonctionnement des liens de connexion reçus par e-mail.

**Validation attendue :** soumettre depuis une autre origine un formulaire de navigation vers la connexion, avec un navigateur anonyme, et vérifier son rejet sans création de session authentifiée. Vérifier ensuite la connexion normale, TOTP et les liens e-mail.

## Protections relevées et points à approfondir

Le socle `DbObject::load/save/delete` examiné utilise des paramètres SQL et contrôle les identifiants de tables/champs. Les mots de passe passent par `password_verify`. Les jetons de connexion persistante sont hachés ; la restauration des sessions vérifie l’activité du compte et sa version de sécurité. Le garde CSRF vérifie l’origine exacte pour les utilisateurs connectés. Les chemins examinés de récupération de compte, du webhook Telegram, des téléchargements MCP et des calendriers externes contiennent également des protections explicites.

Ces observations ne certifient pas l’absence de SQLi, de contournement d’autorisation ou de SSRF ailleurs. Il reste notamment à exécuter une matrice HTTP multi-utilisateurs et multi-organisations sur les partages, permissions de champs, lecteurs publics et anciennes routes.

La CSP globale ne définit pas `frame-ancestors` et seules certaines pages ajoutent une protection contre l’intégration en iframe. Prévoir une vérification de clickjacking et une politique partagée compatible avec les intégrations autorisées. Ce point est un durcissement à approfondir, pas un cinquième exploit démontré.

## Contrôles exécutés

- Audit Composer des versions verrouillées : six avis sur trois paquets, aucun paquet abandonné signalé.
- `php tests/getimg_security_test.php` : **OK**, sous PHP 8.2.16 seulement.
- Sonde sur les fonctions réelles de normalisation et de construction du retour de connexion : contournement par antislash reproduit.
- Résolution de ce retour par le parseur URL de Node 24 : destination externe confirmée.
- Recherche ciblée de signatures de secrets dans les sources applicatives, sans afficher de valeurs : aucun résultat dans le périmètre recherché. L’historique Git, les fichiers privés et les services externes ne sont pas couverts par ce contrôle.
- Tests d’intégration HTTP/base et validation sous PHP 8.5 : **non exécutés**, environnement Docker indisponible.

Ordre proposé : isoler les fichiers actifs et mettre à jour les dépendances, puis fermer la redirection externe et protéger la préconnexion contre le CSRF. Après correction, exécuter les validations indiquées sous PHP 8.5, puis la matrice de permissions.
