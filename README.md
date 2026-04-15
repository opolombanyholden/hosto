# HOSTO

> Plateforme panafricaine de e-santé. La santé au bout du click.

HOSTO est un système d'information hospitalier intégré, conçu pour les usagers, professionnels de santé, structures médicales, assureurs et autorités sanitaires en Afrique.

**Édité par** : Yubile Technologie (Gabon)

---

## Aperçu de l'architecture

HOSTO est un **monolithe modulaire** Laravel découpé en 14 modules métiers indépendants partageant un noyau commun (CORE).

```
app/Modules/
├── Core/         # Auth, audit, référentiels, sécurité (toujours actif)
├── Annuaire/     # Phase 1 — Annuaire géolocalisé public
├── Usager/       # Phase 3 — Comptes patients
├── RendezVous/   # Phase 4 — Prise de rendez-vous
├── Pro/          # Phase 5 — Activité médicale
├── Pharma/       # Phase 6 — Pharmacies commerciales
├── Lab/          # Phase 7 — Laboratoires
├── Assur/        # Phase 9 — Assurances
└── ...
```

Chaque module expose ses propres routes API, migrations et services. Communication inter-modules via contrats du noyau Core uniquement.

---

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel 13 / PHP 8.3 |
| Base de données | PostgreSQL 17 + PostGIS + uuid-ossp + pg_trgm |
| Cache & Files d'attente | Redis 8 |
| Authentification | Laravel Sanctum + 2FA |
| Frontend mobile/desktop | Flutter (à partir de la Phase 1) |
| Frontend web | Bootstrap 5 |
| Tests | PHPUnit (unitaires, fonctionnels, architecture) |
| Qualité de code | Pint, Larastan / PHPStan niveau 6 |

---

## Démarrage rapide

```bash
# 1. Installer les dépendances système
brew install postgresql@17 redis postgis
brew services start postgresql@17
brew services start redis

# 2. Cloner et installer le projet
git clone <repo> hosto
cd hosto
make setup

# 3. Lancer le serveur de développement
make serve
# → http://localhost:8010
```

Voir [docs/DEVELOPER.md](docs/DEVELOPER.md) pour le guide complet.

---

## Commandes principales

```bash
make help          # Liste toutes les commandes disponibles
make check         # Lint + analyse statique + tests (pre-commit)
make test          # Lance la suite de tests
make serve         # Démarre le serveur de dev (port 8010)
make db-shell      # Ouvre psql sur la base hosto
make routes        # Liste les routes API
```

---

## Documentation

- **Architecture & décisions** : [docs/adr/](docs/adr/) — lire en premier
- **Guide développeur** : [docs/DEVELOPER.md](docs/DEVELOPER.md)
- **Cahier des charges fonctionnel** : `conception/` (documents Yubile)

---

## Phases de développement

Le développement suit une approche incrémentale orientée **valeur usager** :

| Phase | Périmètre | Statut |
|---|---|---|
| 0 | Fondations techniques (noyau, audit, sécurité) | ✅ Terminée |
| 1 | Annuaire public géolocalisé (sans compte) | À démarrer |
| 2 | Annuaire des médecins et catalogues santé | À venir |
| 3 | Comptes usagers + consentements | À venir |
| 4 | Prise de rendez-vous | À venir |
| 5+ | Modules métiers (Pro, Pharma, Lab, ...) | À venir |

---

## Licence

Propriétaire — © Yubile Technologie. Tous droits réservés.
