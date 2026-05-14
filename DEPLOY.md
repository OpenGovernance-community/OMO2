# Deploiement sur un site ou un serveur

Ce guide sert a installer OMO2 sur un hebergement reel, sans Docker.

## 1. Cloner le depot dans le dossier du site

Se placer dans le dossier racine du site web, puis cloner le depot :

```bash
git clone -b Dev <url-du-repo> .
```

Le point final `.` est important si vous voulez copier les fichiers directement dans le dossier courant.

## 2. Ouvrir le site dans le navigateur

Si aucun fichier `.env` n'est present, le site redirige automatiquement vers `install.php`.

L'assistant permet de :

- renseigner les acces MySQL
- verifier l'envoi d'e-mail avec un code recu par mail
- choisir le mode d'acces aux organisations
- creer automatiquement le fichier `.env`
- initialiser la base de donnees de depart

Le parcours le plus simple pour une premiere installation est donc :

1. cloner le depot
2. ouvrir l'URL du site
3. suivre l'assistant
4. se connecter avec le compte admin cree pendant l'installation

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

Si le site est deja clone sur le serveur, une mise a jour typique ressemble a ceci :

```bash
cd /chemin/du/site
git fetch origin Dev
git reset --hard origin/Dev
php scripts/run-migrations.php
```

Si vous avez fait des modifications locales non versionnees sur le serveur, evitez `reset --hard` et utilisez une procedure adaptee.

## 6. Points utiles

- Si le SMTP est mal configure, l'assistant d'installation teste l'envoi avec un timeout court.
- Si la base cible est vide, l'installation importe le seed de depart.
- Si la base existe deja mais ne correspond pas au seed attendu, l'installation s'arrete pour eviter un ecrasement involontaire.
