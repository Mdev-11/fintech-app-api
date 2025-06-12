# API Fintech

Une API d'application fintech basée sur Laravel offrant une authentification sécurisée avec reconnaissance faciale, gestion des utilisateurs et capacités de transactions financières.

## Fonctionnalités

- Authentification des utilisateurs avec reconnaissance faciale
- Authentification sécurisée par jetons (Laravel Sanctum)
- Inscription et connexion des utilisateurs
- Vérification des documents d'identité
- Gestion des transactions financières
- Système de notifications

## Prérequis

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js & NPM
- Service de reconnaissance faciale (AWS Rekognition ou similaire)
- Redis (optionnel, pour le cache)

## Installation

1. Cloner le dépôt :
```bash
git clone https://github.com/Mdev-11/fintech-app-api.git
cd fintech-app-api
```

2. Installer les dépendances PHP :
```bash
composer install
```

3. Copier le fichier d'environnement :
```bash
cp .env.example .env
```

4. Générer la clé d'application :
```bash
php artisan key:generate
```

5. Configurer la base de données dans `.env` :
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nom_de_votre_base
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

6. Configurer les identifiants AWS pour la reconnaissance faciale dans `.env` :
```
AWS_ACCESS_KEY_ID=votre_clé_aws
AWS_SECRET_ACCESS_KEY=votre_secret_aws
AWS_DEFAULT_REGION=votre_région_aws
AWS_SESSION_TOKEN=votre_session_token # si utilise des credenials temporaires
```

7. Exécuter les migrations de la base de données :
```bash
php artisan migrate
```

8. Créer le lien symbolique pour le stockage :
```bash
php artisan storage:link
```

## Configuration

### Service de Reconnaissance Faciale
L'application utilise la reconnaissance faciale pour l'authentification des utilisateurs. Configurez les identifiants de votre service de reconnaissance faciale dans le fichier `.env`.

### Stockage des Fichiers
Les images de visage et les documents d'identité des utilisateurs sont stockés de manière sécurisée. Configurez vos paramètres de stockage dans `config/filesystems.php`.

### Authentification
L'application utilise Laravel Sanctum pour l'authentification API. Configurez vos paramètres dans `config/sanctum.php`.

## Documentation de l'API

### Points d'Entrée d'Authentification

#### Inscription Utilisateur
```
POST /api/register
```
Champs requis :
- name (nom)
- phone_number (numéro de téléphone)
- password (mot de passe)
- face_image (fichier image du visage)
- id_document (fichier document d'identité)
- id_document_type (optionnel)

#### Connexion
```
POST /api/login
```
Champs requis :
- phone_number (numéro de téléphone)
- password (mot de passe)
- face_image (fichier image du visage)

#### Déconnexion
```
POST /api/logout
```
Nécessite un jeton d'authentification

## Considérations de Sécurité

- Toutes les données sensibles sont chiffrées
- La reconnaissance faciale est requise pour l'authentification
- Les documents d'identité sont stockés de manière sécurisée
- Les jetons API sont gérés de manière sécurisée via Laravel Sanctum
- CORS est configuré pour la sécurité de l'API

## Points d'Amélioration

1. **Authentification :**
   - Implémenter la 2FA comme couche de sécurité supplémentaire
   - Ajouter des options d'authentification biométrique
   - Implémenter la gestion des sessions

2. **Sécurité :**
   - Ajouter le rate limiting pour les points d'entrée API
   - Ajouter la journalisation et la surveillance des requêtes
   - Renforcer les politiques de mot de passe

3. **Performance :**
   - Implémenter la mise en cache des données fréquemment accédées
   - Optimiser les requêtes de base de données
   - Ajouter des workers de file d'attente pour les processus en arrière-plan

4. **Documentation :**
   - Ajouter la documentation API avec OpenAPI/Swagger
   - Inclure une collection Postman
   - Ajouter la documentation du code

5. **Tests :**
   - Ajouter des tests d'intégration
   - Implémenter un pipeline CI/CD

6. **Internationalisation :**
   - Implémenter la traduction complète des messages du serveur
   - Actuellement, la plupart des messages sont en anglais car le projet a été développé initialement dans cette langue
   - Utiliser le système d'internationalisation de Laravel pour supporter le français et d'autres langues
   - Créer les fichiers de traduction pour toutes les chaînes de caractères de l'application
   - Ajouter un middleware de détection automatique de la langue
   - Permettre aux utilisateurs de choisir leur langue préférée

Ce projet est sous licence MIT - voir le fichier LICENSE pour plus de détails.
