# ADR 0014 — Synchronisation offline et instances locales

**Statut** : Accepté
**Date** : 2026-04-22

## Contexte

HOSTO doit fonctionner dans des structures hospitalières avec une connectivité intermittente. Les données médicales doivent être accessibles en mode offline et synchronisées avec le cloud central quand la connexion revient.

## Décision

### Architecture

```
Cloud central (Yubile / ANINF)
    ↕  sync bidirectionnelle
Instance locale (structure hospitalière)
```

Chaque instance locale est une installation HOSTO complète (même codebase, même schéma) fonctionnant sur un serveur local de la structure.

### Mécanisme de synchronisation

**Colonnes déjà présentes** sur toutes les entités syncables :
- `origin` : `cloud` ou UUID de la structure locale
- `sync_version` : compteur logique incrémenté à chaque modification
- `sync_status` : `synced`, `pending`, `conflict`

**Table `sync_queue`** : file d'attente des modifications locales à pousser vers le cloud.

**Table `sync_log`** : journal des synchronisations effectuées.

### Résolution de conflits

Stratégie **last-write-wins** basée sur `sync_version` :
- Les référentiels du cloud ont priorité (pays, spécialités, médicaments)
- Les actes médicaux récents locaux ont priorité
- En cas de conflit (même entité modifiée des deux côtés), la version avec le `sync_version` le plus élevé gagne
- Les conflits sont journalisés pour revue manuelle

### API de synchronisation

```
POST /api/v1/sync/push   → local pousse ses modifications vers le cloud
POST /api/v1/sync/pull   → local récupère les modifications du cloud
GET  /api/v1/sync/status → état de la dernière synchronisation
```

### Mode de déploiement

Configuré via `HOSTO_DEPLOYMENT` dans `.env` :
- `cloud` : instance centrale (défaut)
- `local` : instance locale dans une structure

En mode local :
- `HOSTO_STRUCTURE_UUID` identifie la structure
- Les données créées portent `origin = {structure_uuid}`
- La sync_queue accumule les modifications offline
- Un job planifié tente la synchronisation toutes les 5 minutes

## Conséquences

- Le schéma est déjà prêt (colonnes syncables sur toutes les tables médicales)
- L'implémentation complète nécessite un environnement multi-instances pour les tests
- Phase 10 pose les fondations (module, API, service) ; le déploiement réel est opérationnel
