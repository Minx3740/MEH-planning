# MEH

MEH est une application web de gestion et de consultation des plannings et activités du ministère. La partie serveur repose sur Laravel/PHP, avec MySQL ou MariaDB. L'interface doit s'adapter aux ordinateurs, tablettes et téléphones.

## Référentiel du projet

Le document de référence fonctionnel et technique est [directive.md](directive.md). Il distingue les décisions validées des sujets encore à définir : respecter les décisions validées et ne pas inventer de règle pour les points ouverts.

Principes essentiels :

- Les rôles V1 sont Administrateur, Secrétaire général (SG) et Directeur. Le rôle Chef de service est exclu de la V1.
- Un directeur accède uniquement aux données de sa direction ; le SG peut consulter les plannings de toutes les directions.
- Une activité est créée en brouillon et ne devient visible aux autres qu'après publication.
- Les chevauchements sont autorisés et signalés sans bloquer automatiquement l'activité.
- Le partage est sélectif, en lecture seule, révocable et limité aux activités publiées.
- L'audit est essentiel et ses traces doivent rester immuables.

## Environnement actuel

Le projet utilise Laravel 13, PHP 8.3 ou ultérieur selon `composer.json`, Livewire, Vite et Tailwind CSS. La version effective des dépendances est définie par les manifestes et fichiers de verrouillage.

La conception complète de la base de données et plusieurs règles fonctionnelles restent à valider. Avant d'ajouter des migrations définitives ou d'implémenter un point non tranché, consulter `directive.md`.

## Installation locale

Prérequis : PHP compatible avec `composer.json`, Composer, Node.js et npm.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
```

Configurer ensuite la connexion MySQL/MariaDB dans `.env`. Ne lancer les migrations qu'après vérification de leur compatibilité avec le modèle de données validé.

Pour démarrer le serveur de développement de l'interface et compiler les ressources :

```powershell
npm run dev
```

Dans un autre terminal, démarrer Laravel :

```powershell
php artisan serve
```

## Tests

```powershell
php artisan test
```
