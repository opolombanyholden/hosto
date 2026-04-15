# ADR 0007 — Stratégie Git et workflow de contribution

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

HOSTO sera maintenu sur le long terme (5+ ans) par une équipe qui grossira progressivement (2 → 8 → 15 développeurs). Sans stratégie Git claire, les conflits, régressions et pertes de code deviennent inévitables.

## Décision

### Branches principales

| Branche | Rôle | Protection |
|---|---|---|
| `main` | Code en production. Toujours déployable. | ✅ Protected, no force-push, requires PR |
| `develop` | Intégration en cours. Cible des PR de fonctionnalités. | ✅ Protected, requires PR |

### Branches éphémères

| Préfixe | Usage | Exemple |
|---|---|---|
| `feature/` | Nouvelle fonctionnalité | `feature/p1-annuaire-recherche-geo` |
| `fix/` | Correction de bug | `fix/audit-signature-encoding` |
| `chore/` | Maintenance, déps, docs | `chore/upgrade-laravel-13.5` |
| `hotfix/` | Correctif urgent depuis `main` | `hotfix/sql-injection-search` |
| `refactor/` | Restructuration sans changement fonctionnel | `refactor/extract-audit-trait` |

Convention : `<type>/<phase>-<sujet-court-tirets>`. Le préfixe de phase (`p0`, `p1`, ...) facilite le tri visuel.

### Cycle de vie d'une fonctionnalité

```
main
 └── develop
      └── feature/p1-annuaire-recherche-geo
           ├── commit
           ├── commit
           └── PR vers develop (review + CI verte)
```

Quand `develop` est stable et prête pour une release :
```
develop → PR → main → tag v1.2.0
```

### Convention de commit

Format **Conventional Commits** :

```
<type>(<scope>): <sujet en français, impératif, sans point>

[corps optionnel : pourquoi, pas comment]

[footer optionnel : Refs #123, BREAKING CHANGE: ...]
```

Types autorisés : `feat`, `fix`, `chore`, `docs`, `refactor`, `test`, `perf`, `style`, `build`, `ci`.

Scopes typiques : `core`, `annuaire`, `usager`, `pro`, `audit`, `auth`, `db`, `infra`, `docs`.

Exemples :
```
feat(annuaire): ajouter recherche geo par rayon en kilometres
fix(audit): corriger encodage bytea sur PostgreSQL 17
docs(adr): documenter le choix de Sanctum (ADR 0005)
chore(deps): mettre a jour Laravel 13.4 vers 13.5
```

### Pull Requests

- Une PR = un sujet cohérent. Pas de PR fourre-tout.
- Description structurée : **Contexte**, **Changements**, **Comment tester**, **Risques**.
- **CI verte obligatoire** (lint + PHPStan + tests).
- **1 reviewer minimum** pour `develop`, **2 reviewers** pour `main`.
- Squash-merge par défaut (1 PR = 1 commit dans `develop`).

### Hooks pre-commit (recommandés)

À chaque commit local :
```
make check
```
Si quelqu'un veut bypasser, c'est explicite (`git commit --no-verify`) et visible en review.

### Tags et versions

Versionnement **SemVer** : `MAJOR.MINOR.PATCH`.
- `MAJOR` : changement incompatible d'API publique
- `MINOR` : ajout de fonctionnalité rétrocompatible
- `PATCH` : correctif rétrocompatible

Tags : `v1.0.0`, `v1.1.0`, `v1.1.1`...

Pré-Phase 1 : version `0.x` (instable, pas d'engagement de stabilité).

## Alternatives considérées

### Trunk-based development (1 seule branche `main`)
- Rejeté : nécessite une discipline et une CI ultra-rapide qu'on n'aura pas en début de projet
- Pourrait être adopté plus tard une fois les feature flags en place

### GitFlow (release branches, hotfix branches systématiques)
- Rejeté : surdimensionné pour un produit Web. Le combo `main`/`develop`/`feature` suffit.

## Conséquences

- Les développeurs doivent connaître `git rebase` (favorisé pour synchroniser `feature/*` avec `develop`)
- Les `hotfix/*` partent de `main`, sont mergés dans `main` ET dans `develop`
- La CI tournera sur chaque PR ; coût à anticiper côté GitHub Actions / GitLab CI
