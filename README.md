Texte de vérif de la mise à jour version 2

Bienvenue sur le repository de OMO2, la nouvelle version de OpenMyOrganization.

Le depot peut etre utilise de deux manieres distinctes :

## 1. Developpement local avec Docker

Utiliser ce parcours si vous voulez lancer une version locale reproductible du projet, avec base de demo, Mailpit, phpMyAdmin et tests de sous-domaines avec `localtest.me`.

Resume rapide :

```bash
git clone <url-du-repo>
cd OMO2
docker compose down -v
docker compose up --build
```

Guide complet : [DOCKER.md](docs/DOCKER.md)

## 2. Installation sur un site ou un serveur

Utiliser ce parcours si vous voulez deployer l'application sur un hebergement reel.

Resume rapide :

```bash
git clone -b Dev <url-du-repo> .
```

Puis ouvrir le site dans le navigateur. Si le fichier `.env` est absent, le site redirige automatiquement vers `install.php` et lance l'assistant d'installation.

Guide complet : [DEPLOY.md](docs/DEPLOY.md)

## Organisation des fichiers

- `docs/` : guides de deploiement et Docker ; `docs/performance/` : rapports de performance.
- `views/omo2-home.php` et `views/omo2-migration.php` : pages publiques OMO2, accessibles aux URL historiques `/index2.php` et `/migration-omo.php`.
- `pv/export.php` : exports Word, OpenDocument et PDF d EasyPV.
- `common/confirm.php`, `common/qr/` et `common/admin/stats.php` : confirmation de compte, QR codes et statistiques du site.
- `tools/migration/omo1-export.php` : outil ponctuel d export d une organisation OMO1 vers le format importe par OMO2. Il demande les acces a la base source et l identifiant de l organisation ; il s utilise depuis le navigateur.

Les anciennes URL des pages deplacees restent gerees par les reecritures internes de `.htaccess`, y compris les requetes POST. Les commandes des guides se lancent depuis la racine du depot.
