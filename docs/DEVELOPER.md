# Guide développeur HOSTO

Ce guide vise à amener un nouveau développeur d'un poste vide à un environnement HOSTO fonctionnel en moins de 30 minutes.

---

## 1. Prérequis système

### macOS

```bash
# Homebrew (si pas déjà installé)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Outils
brew install php@8.3 composer node@22 postgresql@17 redis postgis git make

# Démarrer les services
brew services start postgresql@17
brew services start redis
```

### Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php8.3-cli php8.3-pgsql php8.3-redis php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl \
    composer nodejs npm postgresql-17 postgresql-17-postgis redis-server git make

sudo systemctl enable --now postgresql redis-server
```

### Windows

Utiliser **WSL2** avec Ubuntu, puis suivre les instructions Linux.

---

## 2. Création de la base de données

```bash
psql postgres -c "CREATE USER hosto WITH PASSWORD 'hosto_secret_2025' SUPERUSER;"
psql postgres -c "CREATE DATABASE hosto OWNER hosto;"
psql postgres -c "CREATE DATABASE hosto_testing OWNER hosto;"
```

> Le `SUPERUSER` est requis en local pour installer les extensions PostGIS, uuid-ossp et pg_trgm. En production, l'utilisateur applicatif a des droits restreints (audit immutable, etc.).

---

## 3. Installation du projet

```bash
git clone <repo-url> hosto
cd hosto

# .env avec les bonnes valeurs (voir .env.example)
cp .env.example .env

# Tout-en-un : composer install + npm install + key:generate + migrate
make setup
```

Vérification :

```bash
make serve
# Dans un autre terminal :
curl http://localhost:8010/api/v1/core/health/ready | jq
```

---

## 4. Configuration `.env` minimale

```env
APP_NAME=HOSTO
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8010

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hosto
DB_USERNAME=hosto
DB_PASSWORD=hosto_secret_2025

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# HOSTO-spécifique
HOSTO_DEPLOYMENT=cloud
HOSTO_COUNTRY=GA
HOSTO_AUDIT_ENABLED=true
```

---

## 5. Workflow quotidien

### Pendant le développement

```bash
make serve            # Démarre le serveur
make routes           # Liste les routes API
make db-shell         # Accède à PostgreSQL
make tinker           # REPL Laravel
make clean            # Vide les caches Laravel
```

### Avant chaque commit

```bash
make check            # Lint + PHPStan + tests
```

Si l'un des trois échoue, **on ne commit pas**. Tout est censé passer en local avant CI.

---

## 6. Conventions de code

### Style (Pint)

- PSR-12 + extensions Laravel
- `declare(strict_types=1);` obligatoire dans tous les fichiers PHP
- Imports ordonnés alphabétiquement (classes, fonctions, constantes)
- Trailing commas sur multi-lignes
- Final classes par défaut (sauf modèles Eloquent et migrations)

### Typage (PHPStan niveau 6)

- Tous les paramètres et retours typés
- Génériques Eloquent : `Builder<MyModel>` plutôt que `Builder`
- PhpDoc `@property` sur les modèles pour les colonnes

### Architecture

- Pas d'import direct entre modules (ex : `use App\Modules\Pro\...` depuis `App\Modules\Pharma\...` interdit)
- Tout module a son `{Module}ServiceProvider`
- Tout modèle utilise `HasUuid`
- Toute écriture sensible appelle `AuditLogger::record(...)`

Ces règles sont **vérifiées automatiquement** par `tests/Architecture/`.

---

## 7. Création d'un nouveau module

```bash
MODULE=Annuaire
mkdir -p app/Modules/$MODULE/{Http/Controllers,Models,Services,Providers,Routes,Database/Migrations}
```

Créer le ServiceProvider (`app/Modules/$MODULE/Providers/${MODULE}ServiceProvider.php`) :

```php
<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class AnnuaireServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Route::middleware('api')
            ->prefix('api/'.config('hosto.api.current_version').'/annuaire')
            ->name('annuaire.api.')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
```

Activer le module dans `config/hosto.php` ou via `.env` :

```env
HOSTO_MODULE_ANNUAIRE=true
```

---

## 8. Création d'une migration

```bash
php artisan make:migration create_hostos_table
# La migration est créée dans database/migrations/
```

Pour qu'elle suive les conventions HOSTO :

```php
use App\Modules\Core\Support\SchemaBuilder;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostos', function (Blueprint $table): void {
            SchemaBuilder::base($table);          // id, uuid, timestamps, soft delete, created_by/updated_by
            SchemaBuilder::syncable($table);      // origin, sync_version, sync_status (entités synchronisables)

            $table->string('name');
            $table->string('city');
            // ...
        });

        SchemaBuilder::installUpdatedAtTrigger('hostos');
    }

    public function down(): void
    {
        SchemaBuilder::dropUpdatedAtTrigger('hostos');
        Schema::dropIfExists('hostos');
    }
};
```

---

## 9. Audit d'une opération sensible

```php
use App\Modules\Core\Services\AuditLogger;

public function show(string $uuid, AuditLogger $audit): JsonResponse
{
    $patient = Patient::whereUuid($uuid)->firstOrFail();

    $audit->record(
        action: AuditLogger::ACTION_READ,
        resourceType: 'patient',
        resourceUuid: $patient->uuid,
        metadata: ['reason' => 'consultation'],
    );

    return response()->json(['data' => new PatientResource($patient)]);
}
```

L'audit est **synchrone** et **chaîné cryptographiquement** (HMAC-SHA256 + previous_hash). Toute suppression d'une ligne d'audit est détectable.

---

## 10. Outils utiles

| Outil | Commande | Note |
|---|---|---|
| **Tinker** (REPL) | `make tinker` | Tester du code sans HTTP |
| **psql** | `make db-shell` | Accès direct PostgreSQL |
| **redis-cli** | `make redis-shell` | Inspection cache/queues |
| **Pail** (logs live) | `php artisan pail` | Suit les logs en temps réel |
| **Telescope** | (Phase 1+) | Debug HTTP/DB/queue/cache |

---

## 11. Dépannage courant

### `Class "Redis" not found`

PHP n'a pas l'extension `phpredis`. Soit l'installer (`pecl install redis`), soit utiliser `predis` (déjà installé).

Vérifier dans `.env` : `REDIS_CLIENT=predis`

### `extension "postgis" is not available`

```bash
brew reinstall postgis
brew services restart postgresql@17
psql hosto -c 'CREATE EXTENSION postgis;'
```

### `permission denied to create extension`

Le user `hosto` n'est pas SUPERUSER. En local :

```bash
psql postgres -c "ALTER USER hosto WITH SUPERUSER;"
```

### Tests qui échouent uniquement sur certains environnements

Vérifier que `.env.testing` et `phpunit.xml` pointent bien sur PostgreSQL (pas SQLite). HOSTO utilise des fonctionnalités (PostGIS, partitionnement, JSONB indexé) que SQLite ne sait pas reproduire.

---

## 12. Pour aller plus loin

- **Décisions d'architecture** : `docs/adr/` — toutes les décisions structurantes y sont documentées
- **Cahier des charges** : `conception/` — documents fonctionnels Yubile
- **Plan de phases** : voir le ticket projet ou le compte-rendu architectural
