<laravel-boost-guidelines>
=== règles fondamentales ===

# Consignes Laravel Boost

## Contexte fondamental

Cette application Laravel fonctionne avec PHP 8.5. Utilisez toujours les API correspondant à la version majeure installée de chaque paquet ; ne présumez pas de la version.

Avant d'utiliser l'API d'un paquet, vérifiez la version installée :
- Paquets PHP : exécutez `composer show --direct` pour afficher les dépendances directes et leurs versions, ou `composer show <vendor/package>` pour un paquet précis.
- Paquets JavaScript : vérifiez les versions installées dans `package.json`.

## Conventions

- Respectez toutes les conventions de code déjà utilisées dans cette application. Avant de créer ou modifier un fichier, consultez les fichiers voisins pour en reprendre la structure, l'approche et le nommage appropriés.
- Choisissez des noms explicites pour les variables et les méthodes. Par exemple, `isRegisteredForDiscounts` plutôt que `discount()`.
- Avant d'écrire un nouveau composant, vérifiez s'il en existe déjà un qui puisse être réutilisé.

## Scripts de vérification

- Ne créez pas de scripts de vérification ni de commandes Tinker lorsque des tests couvrent déjà la fonctionnalité et démontrent son bon fonctionnement. Les tests unitaires et fonctionnels sont prioritaires.

## Structure et architecture de l'application

- Respectez la structure de dossiers existante ; ne créez pas de nouveaux dossiers racine sans autorisation.
- Ne modifiez pas les dépendances de l'application sans autorisation.

## Compilation des ressources de l'interface

- Si une modification de l'interface n'apparaît pas ou si l'erreur « Unable to locate file in Vite manifest » survient, exécutez `npm run build` ou demandez à l'utilisateur d'exécuter `npm run dev` ou `composer run dev`.

## Fichiers de documentation

- Ne créez des fichiers de documentation que si l'utilisateur le demande explicitement.

=== règles Boost ===

# Laravel Boost

## Règles du projet

- Si le dossier `.ai/rules` existe, ce projet contient des règles regroupées par domaine et suivies dans le dépôt, y compris des consignes de framework associées à des chemins sous `.ai/rules/boost`. Avant de passer en mode planification ou de créer/modifier un fichier, vous DEVEZ d'abord ouvrir `@.ai/rules/index.md` (qui associe les motifs de fichiers aux fichiers de règles), lire chaque fichier de règles dont les motifs couvrent les chemins concernés, puis exécuter `grep -rin 'keyword' .ai/rules` afin de repérer les règles qui ne seraient pas détectées par les seuls motifs de chemins. Ne rédigez pas de code avant d'avoir lu et appliqué toutes les règles correspondantes. Si `.ai/rules` n'existe pas, continuez sans ces règles.

## Artisan

- Exécutez les commandes Artisan directement dans le terminal (par exemple, `php artisan route:list`). Utilisez `php artisan list` pour découvrir les commandes disponibles et `php artisan [command] --help` pour vérifier leurs paramètres.
- Inspectez les routes avec `php artisan route:list`. Vous pouvez filtrer avec : `--method=GET`, `--name=users`, `--path=api`, `--except-vendor` et `--only-vendor`.
- Consultez les valeurs de configuration avec la notation par points, par exemple `php artisan config:show app.name` ou `php artisan config:show database.default`. Vous pouvez aussi lire directement les fichiers du dossier `config/`.

## Tinker

- Exécutez du PHP dans le contexte de l'application pour le débogage et les tests. Ne créez pas de modèles sans l'accord de l'utilisateur ; préférez plutôt les tests avec des fabriques. Privilégiez les commandes Artisan existantes plutôt que du code Tinker personnalisé.
- Utilisez toujours des apostrophes simples afin d'éviter l'expansion par le shell : `php artisan tinker --execute 'Your::code();'`
  - Utilisez des guillemets doubles pour les chaînes PHP à l'intérieur : `php artisan tinker --execute 'User::where("active", true)->count();'`

=== règles PHP ===

# PHP

- Utilisez toujours des accolades pour les structures de contrôle, même lorsque leur corps tient sur une seule ligne.
- Utilisez la promotion des propriétés du constructeur de PHP 8 : `public function __construct(public GitHub $github) { }`. Ne laissez pas de méthode `__construct()` vide et sans paramètre, sauf si le constructeur est privé.
- Déclarez explicitement le type de retour et le type de chaque paramètre de méthode : `function isAccessible(User $user, ?string $path = null): bool`
- Utilisez le format `TitleCase` pour les clés d'énumération : `FavoritePerson`, `BestLake`, `Monthly`.
- Préférez les blocs PHPDoc aux commentaires en ligne. N'ajoutez des commentaires en ligne que pour expliquer une logique exceptionnellement complexe.
- Décrivez la forme des tableaux dans les blocs PHPDoc.

=== règles de déploiement ===

# Déploiement

- Laravel peut être déployé avec [Laravel Cloud](https://cloud.laravel.com/), qui permet de déployer et de faire évoluer rapidement des applications Laravel en production.

=== règles fondamentales Laravel ===

# Respecter les pratiques Laravel

- Utilisez les commandes `php artisan make:` pour créer des fichiers (migrations, contrôleurs, modèles, etc.). Vous pouvez afficher les commandes Artisan disponibles avec `php artisan list` et vérifier leurs paramètres avec `php artisan [command] --help`.
- Pour créer une classe PHP générique, utilisez `php artisan make:class`.
- Ajoutez `--no-interaction` à toutes les commandes Artisan pour qu'elles ne sollicitent pas l'utilisateur. Ajoutez également les options appropriées pour obtenir le comportement attendu.

### Création de modèles

- Lors de la création d'un modèle, créez également des fabriques et des seeders utiles. Demandez à l'utilisateur s'il a d'autres besoins et vérifiez les options disponibles avec `php artisan make:model --help`.

## API et ressources Eloquent

- Pour les API, privilégiez les ressources API Eloquent et le versionnement des API. Si les routes API existantes ne suivent pas cette approche, respectez la convention déjà utilisée par l'application.

## Génération d'URL

- Pour créer des liens vers d'autres pages, privilégiez les routes nommées et la fonction `route()`.

## Tests

- Pour créer des modèles dans les tests, utilisez leurs fabriques. Vérifiez si une fabrique propose des états personnalisés avant de configurer le modèle manuellement.
- Faker : utilisez des méthodes telles que `$this->faker->word()` ou `fake()->randomDigit()`. Respectez la convention existante, qu'elle utilise `$this->faker` ou `fake()`.
- Pour créer un test, utilisez `php artisan make:test [options] {name}` pour un test fonctionnel, et ajoutez `--unit` pour un test unitaire. La plupart des tests devraient être fonctionnels.

=== règles fondamentales Livewire ===

# Livewire

- Livewire permet de créer des interfaces dynamiques et réactives en PHP, sans écrire de JavaScript.
- Vous pouvez utiliser Alpine.js pour les interactions côté client plutôt qu'un framework JavaScript.
- Conservez l'état côté serveur afin que l'interface le reflète. Validez et autorisez les actions comme vous le feriez dans des requêtes HTTP.

=== règles fondamentales Pint ===

# Formateur de code Laravel Pint

- Si vous avez modifié des fichiers PHP, vous devez exécuter `vendor/bin/pint --dirty --format agent` avant de finaliser les changements, afin de vérifier que le code respecte le style attendu par le projet.
- N'exécutez pas `vendor/bin/pint --test --format agent` ; exécutez plutôt `vendor/bin/pint --format agent` pour corriger les problèmes de formatage.

=== règles fondamentales PHPUnit ===

# PHPUnit

- Ce projet utilise PHPUnit. Créez les tests avec `php artisan make:test --phpunit {name}`.
- N'incluez pas le dossier de la suite de tests dans `{name}`. Utilisez `SomeFeatureTest`, et non `Feature/SomeFeatureTest`.
- Consultez la compétence `testing-best-practices` pour les recommandations sur la couverture, le nommage, la structure, l'isolation des dépendances et la revue.

## Exécution des tests

- Exécutez le sous-ensemble de tests le plus restreint qui couvre le changement. Passez un chemin de fichier ou `--filter=testName` à `php artisan test --compact`.
- Relancez un test après chaque modification de celui-ci.
- Exécutez `vendor/bin/phpunit` pour lancer directement le moteur de test. Il accepte les mêmes arguments de chemin de fichier et `--filter=testName`.

</laravel-boost-guidelines>
