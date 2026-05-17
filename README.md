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

Guide complet : [DOCKER.md](DOCKER.md)

## 2. Installation sur un site ou un serveur

Utiliser ce parcours si vous voulez deployer l'application sur un hebergement reel.

Resume rapide :

```bash
git clone -b Dev <url-du-repo> .
```

Puis ouvrir le site dans le navigateur. Si le fichier `.env` est absent, le site redirige automatiquement vers `install.php` et lance l'assistant d'installation.

Guide complet : [DEPLOY.md](DEPLOY.md)