# News de mise a jour

Le bouton `?` du bandeau administrateur affiche les modifications entre le commit
installe et le commit distant. Les descriptions des pull requests fusionnees sont
lues depuis GitHub, pour les merges classiques et les commits squash portant
`(#numero)`, sur la ligne principale des livraisons (first parent), sans reprendre
les PR imbriquees dans un merge. Les descriptions sont mises en cache cinq minutes
hors de la racine publique.

Le texte Markdown prepare dans la description GitHub est la presentation principale.
Sa mise en page et ses repetitions intentionnelles sont conservees, avec le nettoyage
HTML commun pour la securite. Les titres et messages techniques des commits ne sont
jamais ajoutes. Plusieurs presentations de livraisons sont separees par un trait.
Les ajouts de `NOUVEAUTES.md` de la meme plage sont proposes en dessous, en petit,
dans un accordeon ferme par defaut. Si aucune presentation n est preparee ou que
GitHub est inaccessible, le journal est affiche directement. Sans aucune de ces
sources, un message indique qu aucune description n est disponible.

La presentation et le journal optionnel sont stockes separement ; la migration
`2026-11-03-02-site-update-news-journal.sql` ajoute ce champ. Les anciennes news
contenant les sections concatenees connues sont relues dans ce nouvel ordre sans
modifier leur stockage ni recreer de notifications. Le titre de la news figure
uniquement dans l entete de la popup.

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
