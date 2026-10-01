# Revue des scripts JavaScript — 24 septembre 2026

## Résultat

Les gros scripts de 79 pages et composants PHP sont externalisés dans 88 fichiers JavaScript. Les principaux écrans concernés sont les documents et PV, décisions, modèles de structure, indicateurs, équipe, FAQ, profil, formations, paramètres, Mémo, EasyCIRCLE, l’éditeur de PV autonome et les questionnaires publics.

Dans le périmètre initial `omo`, `common`, `popup`, `views`, `ajax`, les blocs JavaScript intégrés passent d’environ 2 422 509 à 58 431 octets, soit une réduction de 97,6 %. Aucun bloc exécutable restant dans ce périmètre ne dépasse 3 Ko.

Ces chiffres décrivent les sources avant compression. Les configurations PHP et données propres à chaque requête restent transmises avec la page. Ils ne mesurent donc pas directement le trafic réseau économisé. Le gain sur les ouvertures suivantes vient de la réutilisation des fichiers JavaScript depuis le cache.

## Éléments partagés

- `common/assets.php` fournit les URLs portant une empreinte du contenu et le rendu des scripts avec leur configuration JSON.
- `common/assets/components.js` exécute les scripts des fragments dans leur ordre d’origine, attend les dépendances externes et les feuilles de style, conserve leur position dans le DOM et ignore les blocs JSON.
- La barre supérieure, les panneaux OMO, FAQ, profil, décisions, paramètres, documents, Mémo, projets, indicateurs et formations utilisent ce chargement commun.
- `common/assets/admin-edit.js` porte les fonctions communes de l’éditeur HTML. Les initialisations de formulaire, de carte et de recadrage sont dans des fichiers dédiés partagés par les objets dbObject.
- Les écrans de décisions propres à OMO gardent leurs scripts et styles dans `omo/api/decision/` ; les composants utilisables par plusieurs applications restent dans `common/choice/`.
- `common/lms/video.js` partage le lecteur vidéo entre les formations publiques et leur intégration dans OMO.
- La liste des décisions transmet son jeu de données une seule fois dans le bloc JSON déjà présent dans le panneau.

## Ajouter un script de page

Pour un script statique autonome :

```php
<script src="<?= commonAssetUrl('/common/example/page.js') ?>"></script>
```

Pour un script qui reçoit des traductions, URLs ou paramètres PHP :

```php
<?= commonPageScriptTags('/common/example/page.js', [
    'saveUrl' => $saveUrl,
    'labels' => $translatedLabels,
]) ?>
```

Le fichier associé expose son initialisation :

```js
window.commonPageScripts = window.commonPageScripts || {};
window.commonPageScripts['/common/example/page.js'] = function (pageConfig, pageScript) {
    const root = pageScript.closest('[data-example]');
    if (!root) return;
    // Initialiser les interactions de ce fragment avec pageConfig.
};
```

`pageScript` désigne le script externe à son emplacement d’origine. Il permet de retrouver le formulaire ou le fragment auquel les paramètres appartiennent. La configuration est encodée en JSON avec protection des caractères HTML ; aucun code PHP n’est placé dans le fichier `.js`.

Le troisième argument optionnel de `commonPageScriptTags` fournit un nom de configuration globale pour les anciennes pages dont les fonctions sont appelées par des attributs HTML ou d’autres scripts. Les nouvelles pages devraient utiliser l’initialisation isolée ci-dessus.

Pour un fragment chargé avec `fetch`, affecter son HTML avec les APIs DOM, puis attendre `commonExecuteFragmentScripts(container)`. Lorsque le chargement peut être remplacé par une autre navigation, fournir `isCurrent` dans les options. Les scripts sont réinitialisés pour chaque nouveau fragment ; leur téléchargement bénéficie du cache HTTP.

## Cache et publication

`commonAssetUrl` ajoute une empreinte SHA-256 abrégée à chaque URL. Apache attribue un cache d’un an avec `immutable` aux fichiers CSS et JS portant cette empreinte. Une modification du contenu produit une nouvelle URL. Publier les fichiers JavaScript avec les pages PHP qui les référencent.

## Emplacement des fichiers

- Un écran ou module d’une seule application garde ses fichiers CSS et JS près de sa page PHP, par exemple `omo/api/documents/` ou `omo/api/decision/modules/vote/`.
- Les ressources utilisées par plusieurs écrans OMO résident dans `omo/assets/css/` et `omo/assets/js/`, notamment la FAQ, la création d’organisation et les préférences de vue.
- Les ressources utilisées dans plusieurs applications résident à la racine du dépôt, principalement sous `common/assets/` et `common/<module>/`. Les composants génériques, l’éditeur dbObject, la barre supérieure, le profil et le lecteur vidéo LMS suivent cette règle.
- Les applications autonomes conservent leurs ressources sous leur répertoire (`lms/`, `memo/`, `circle/`, `pv/`, `survey/`).

## Inventaire et limites

```sh
node scripts/audit-scripts.cjs
node scripts/audit-scripts.cjs --all
```

Le second mode inclut les applications autonomes. L’inventaire distingue fichiers externes, blocs intégrés et appels d’initialisation configurés. Les petits amorçages, préférences de thème et données dynamiques restent proches des pages. L’ancien `task.php`, qui dépend d’un bootstrap situé hors du dépôt, conserve son bloc conditionnel de gestes tactiles d’environ 3 Ko. Les prototypes sous `test/` sont exclus.

Contrôles effectués : syntaxe PHP 8.5.10 et JavaScript, existence des assets référencés, UTF-8 sans BOM et fins de ligne LF, en-tête HTTP de cache. Consultation dans le navigateur de l’édition FAQ, des documents et de leur détail, des indicateurs et du profil. Ces consultations couvrent le chargement et l’initialisation ; les opérations d’enregistrement et les parcours métier complets restent à valider.
