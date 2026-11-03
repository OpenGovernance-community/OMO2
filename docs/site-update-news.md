# News de mise a jour

Le bouton `?` du bandeau administrateur affiche les modifications entre le commit
installe et le commit distant. Les descriptions des pull requests fusionnees sont
lues depuis GitHub, pour les merges classiques et les commits squash portant
`(#numero)`. Les autres commits et leurs messages complets restent presents.
Les descriptions sont mises en cache cinq minutes hors de la racine publique.
Les ajouts de `NOUVEAUTES.md` entre ces memes revisions sont presentes en premier,
pour disposer d explications utiles meme quand le merge ne comporte aucun descriptif.
Si GitHub est indisponible, limite les requetes ou ne fournit pas de description,
les messages Git servent de secours. Aucune synthese automatique n invente de contenu.

Pour un depot prive, renseigner `SITE_UPDATE_GITHUB_TOKEN` dans Admin du serveur
ou dans `.env`, avec un acces en lecture aux pull requests du depot suivi.
Le token des signalements GitHub est utilise en secours s il existe.
Le depot est determine depuis le remote Git, sans redirection des credentials.

Apres Composer et les migrations reussis, une news conserve les descriptions de
la version installee. Une notification interne personnelle est creee pour chaque
compte actif reel, y compris la personne qui installe la version. Les personnages
et comptes historiques sont exclus. Aucun e-mail, Telegram ou push n est envoye.
Les descriptions ainsi publiees sont visibles par tous les comptes connectes du site.

Le clic sur la notification ouvre la news dans la popup partagee. Le lien reste
utilisable apres reconnexion et depuis une autre organisation. Une contrainte
unique sur la revision et une transaction news/notifications evitent les doublons.
Une installation interrompue conserve la plage initiale et les descriptions pour
la finalisation, meme si une nouvelle version est deja disponible sur le remote.

Le script de migrations recharge le nouveau code et publie egalement la news
lors du premier deploiement de cette fonctionnalite, meme si la synchronisation
a ete lancee par l ancien outil. La nouvelle interface apparait au rechargement.

Validation : PHP 8.5, `tests/site_update_news_test.php` (tables temporaires, sans
notification de comptes reels), `tests/site_update_migration_news_test.php`,
`tests/site_update_upload_storage_test.php`, `node tests/site_update_notifications_test.js`
et `tests/site_update_banner_browser_test.js` (Playwright, ordinateur et mobile).
