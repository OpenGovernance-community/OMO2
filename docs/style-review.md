# Revue des styles — 24 septembre 2026

## Périmètre

Inventaire de `omo/`, `common/`, `popup/`, `views/` et `ajax/`, hors bibliothèques tierces. Les consommateurs des feuilles partagées dans les autres applications ont également été adaptés pour conserver leurs dépendances.

Références visuelles : édition des indicateurs et des tâches récurrentes. La page `common/styleguide.php#forms` reprend leurs sections compactes, séparateurs, champs, aides contextuelles et actions.

## Résultat mesuré

| CSS dans les sources inventoriées | Avant | Après |
| --- | ---: | ---: |
| Blocs intégrés aux fichiers PHP | 486 980 octets | 46 998 octets |
| Fichiers CSS séparés | 484 705 octets | 873 072 octets |
| Total | 971 685 octets | 920 070 octets |

- 52 fichiers PHP contiennent moins de CSS intégré : réduction cumulée de **439 982 octets, soit 90,3 %**.
- Le total diminue de **51 615 octets** grâce aux regroupements et à la suppression des indentations liées aux gabarits PHP.
- Ces chiffres décrivent les sources, avant compression HTTP. Ils ne représentent pas le poids d'une visite : les feuilles propres à un écran sont chargées lors de son ouverture.

## Changements appliqués

- Externalisation des styles statiques des FAQ, formulaires dbObject, profils, équipes, organisations, documents et éditeur de PV, décisions, calendrier, parcours et missions.
- `common/assets.php` fournit une URL stable avec empreinte du contenu. Les consommateurs partagent la même version du fichier. Les dépendances de `shared_css.css` et du CSS principal OMO sont déclarées explicitement, sans leurs anciens imports redondants.
- Apache conserve pendant un an les feuilles portant cette empreinte. Les URL non versionnées conservent la politique existante. Une modification du contenu produit immédiatement une nouvelle URL.
- Les chargeurs de panneaux OMO et de la barre supérieure attendent les feuilles externes avant l'initialisation. Les styles restent attachés à leur panneau ; ils ne s'accumulent pas dans l'en-tête du document.
- Le thème de la barre supérieure appartient désormais à `common/assets/topbar.css`. Ses copies dans le CSS OMO et l'ancien fichier `topbar-theme.css` ont été supprimées.
- Champs et libellés des tâches récurrentes et des indicateurs regroupés dans les primitives génériques. Les grilles des décisions réutilisent les mêmes espacements adaptatifs.
- Badges, textes réservés aux lecteurs d'écran, boutons d'arborescence et cartes de détails des organisations, holons et modèles regroupés dans `components.css`.
- Le calendrier utilise une seule feuille pour son sélecteur d'invitations. Les deux éditeurs LMS partagent 17 règles de cartes et de sélection de missions.
- La page de référence utilise les primitives partagées pour ses grilles, champs, actions et panneaux, avec une présentation plus sobre.

## Styles conservés volontairement

Les styles d'export PDF et d'impression, les styles d'e-mail, les couleurs produites par PHP et les dimensions calculées à partir des données ont des contraintes particulières. Ils restent à proximité de leur rendu. Les règles propres à un graphique, au glisser-déposer, à une carte ou à une variante mobile restent dans le module concerné.

Des ressemblances de déclarations subsistent : elles ne prouvent pas un doublon supprimable, notamment entre variantes mobiles ou états différents. L'inventaire signale ces candidats sans supprimer automatiquement les règles ni modifier leur priorité.

## Revue effectuée

- Syntaxe des 91 fichiers PHP modifiés ou ajoutés examinée avec PHP 8.5.10.
- Syntaxe des trois fichiers JavaScript partagés modifiés examinée avec Node.
- Observation dans le navigateur de l'éditeur FAQ, de la page de référence et de la liste des indicateurs. Aucune donnée de formulaire n'a été enregistrée.
- Réponses Apache `200`, type `text/css` et politique de cache examinés : cache long pour les URL avec empreinte, politique inchangée sans empreinte.
- Encodage UTF-8, absence de BOM et fins de ligne LF des fichiers modifiés examinés.

Les parcours fonctionnels complets, tous les thèmes et toutes les combinaisons de largeur n'ont pas fait l'objet d'une campagne de tests.

## Reprendre l'inventaire

```sh
node scripts/audit-styles.cjs
```

Le résultat JSON classe les blocs CSS intégrés, les feuilles séparées, les attributs `style` repérables dans les sources et les groupes de déclarations identiques. Il sert de guide de revue, sans écrire dans le projet.
