# ADR 0002 — PostgreSQL comme SGBDR principal

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

Le cahier des charges initial proposait MySQL/MariaDB. Une réévaluation a identifié plusieurs besoins métier mal adressés par MySQL :
- Géolocalisation avancée (structures, pharmacies de garde, cartographie épidémiologique)
- Données semi-structurées (ressources FHIR pour interopérabilité HL7)
- Analytique lourde (Hosto Analytic)
- Synchronisation bidirectionnelle cloud/local

## Décision

Adopter **PostgreSQL 17+** comme SGBDR principal, avec les extensions suivantes :
- **PostGIS** : géolocalisation
- **uuid-ossp** : génération UUID native
- **pg_trgm** : recherche floue (noms de structures, médicaments)

Pour les instances locales à très faibles ressources (postes de santé ruraux) : **SQLite** en fallback avec schema compatible.

Pour l'analytique (Phase 12) : **TimescaleDB** (extension PostgreSQL) ou **ClickHouse** selon les volumes constatés.

## Alternatives considérées

### MySQL/MariaDB
- Rejeté : JSON non indexable correctement, pas de PostGIS équivalent, sharding artisanal
- Laravel Eloquent supporte les deux, la migration est faisable mais coûteuse après N tables

### MongoDB
- Rejeté : données médicales intrinsèquement relationnelles (Patient → Encounter → Observation), ACID multi-documents récent et limité

## Conséquences

**Positives**
- FHIR JSON stocké en JSONB indexable
- Recherche géospatiale performante (index GiST)
- Réplication logique native pour la synchro cloud/local
- Compatibilité Laravel Eloquent totale

**Négatives**
- Moins de DBA MySQL que PostgreSQL au Gabon → formation à prévoir
- PostGIS installé sous macOS Homebrew dépend de `libpq` et non de `postgresql@17` directement → documenter l'installation

## Migration

N/A : projet neuf.
