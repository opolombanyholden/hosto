# ADR 0001 — Architecture monolithique modulaire

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

Le cahier des charges v1 évoquait une architecture microservices (msadmin, mspatient, mspro, etc.). La v2 a rectifié vers un monolithe modulaire. HOSTO doit fonctionner sur :
- Cloud central (Yubile/ANINF)
- Instances locales dans les structures hospitalières (y compris avec faibles ressources)
- Mode offline avec synchronisation

## Décision

Adopter une **architecture monolithique modulaire** au sein d'un seul codebase Laravel, avec isolation par namespaces.

Structure :
```
app/Modules/
  Core/          # Auth, audit, référentiels partagés
  Annuaire/      # Phase 1
  Usager/        # Phase 3
  Pro/           # Phase 5
  Pharma/        # Phase 6
  Lab/           # Phase 7
  Assur/         # Phase 9
  ...
```

Chaque module expose :
- `routes/api.php` monté sous `/api/v1/{module}`
- `database/migrations/` versionnées
- `Models/`, `Http/Controllers/`, `Services/`, `Policies/`
- `config/{module}.php` pour activation/désactivation
- Tests unitaires et fonctionnels dédiés

Communication inter-modules : **via services injectés uniquement**, jamais par accès direct aux tables d'un autre module.

## Alternatives considérées

### Microservices purs
- **Rejeté** : complexité opérationnelle (orchestration, découverte de services, observabilité distribuée) inadaptée à une équipe de 6-8 devs et à un déploiement hospitalier local où chaque structure doit pouvoir faire tourner la stack avec 8 Go RAM.

### Monolithe classique
- **Rejeté** : sans discipline modulaire, la dette s'accumule. L'isolation par module prévient le couplage.

### Packages Composer séparés (polylith)
- **Rejeté** pour maintenant : complexité CI/CD pour un bénéfice marginal à ce stade.

## Conséquences

**Positives**
- Un seul déploiement, un seul processus de build, un seul migrate
- Transactions DB atomiques entre modules (utile pour audit + métier)
- Onboarding développeur rapide
- Performance : pas de latence réseau inter-modules

**Négatives**
- Risque de couplage si la discipline n'est pas tenue : à mitiger par revues de code, tests d'architecture (Pest Arch ou Deptrac)
- Scalabilité verticale limitée par défaut : à gérer par cache Redis, read replicas, et découpage tardif si vraiment nécessaire (> 100k utilisateurs actifs)

## Garde-fous

- Règle de dépendance : un module ne peut dépendre que de `Core` et de lui-même
- Un module `A` ne peut pas importer un Model du module `B` — passer par un contrat/interface exposé par `B`
- Test d'architecture automatisé (Pest Arch) bloquant en CI
