# ADR 0008 — Référentiels géographiques bilingues

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

HOSTO doit fonctionner au Gabon puis s'étendre à la CEMAC et à l'Afrique francophone. Les référentiels géographiques (pays, régions, villes) doivent :
- supporter plusieurs pays avec des découpages administratifs hétérogènes (provinces, régions, départements)
- être **bilingues dès le jour 1** (français + anglais), avec possibilité d'ajouter le portugais et les langues locales ultérieurement
- être interrogeables par géolocalisation (structures à proximité)
- rester stables dans le temps (un `uuid` exposé à l'API ne bouge jamais)

## Décision

### Hiérarchie simplifiée

```
Country (pays)
  └── Region (province / région / département selon pays)
       └── City (ville / commune)
```

On ne modélise **pas** le niveau inférieur (quartiers, arrondissements) en Phase 1 — trop de diversité entre pays et valeur marginale pour l'annuaire. Ajouté si besoin réel émerge.

### Schéma bilingue

Chaque entité porte trois colonnes de nom :

| Colonne | Rôle |
|---|---|
| `name_fr` | Nom en français (langue par défaut) |
| `name_en` | Nom en anglais |
| `name_local` | Nom dans la langue locale (ex : fang pour le Woleu-Ntem), NULL si identique au français |

Accessor Eloquent `$model->name` : retourne la meilleure traduction selon `app()->getLocale()`, avec cascade `locale → name_local → name_fr`.

### Identifiants

| Entité | Identifiant métier externe | Identifiant recherche |
|---|---|---|
| Country | `iso2` (2 lettres ISO 3166-1) — unique | `iso2` |
| Region | `uuid` | `code` (ex: `G1` pour Estuaire) — unique par pays |
| City | `uuid` | pas de code stable |

Pourquoi `iso2` pour les pays plutôt que `uuid` : c'est un standard universel, stable, court et lisible dans les URLs (`/countries/GA`).

### Géolocalisation

La table `cities` a une colonne `location` de type PostgreSQL `geography(Point, 4326)` (latitude/longitude WGS84).

En Phase 1.1 : coordonnées du chef-lieu uniquement. En Phase 1.5 : indexation GiST pour recherche par rayon.

### Population comme méta-donnée

Colonne `population` (nullable, bigint) sur `cities`. Pas critique mais utile pour ordonner les résultats de recherche (les grandes villes en premier par défaut).

## Alternatives considérées

### Table de traductions séparée (`city_translations`)
- Rejeté : lourdeur de jointure pour une feature (les noms) qu'on affiche partout. Trois colonnes `name_*` restent lisibles et performantes pour 3 langues.
- Si on devait dépasser 5 langues, on basculerait sur cette approche.

### Données externes via API (GeoNames, Wikidata)
- Rejeté : dépendance réseau, latence, et nos données doivent fonctionner **offline** (mode local structure hospitalière).
- On peut s'en servir comme **source** lors de l'import initial, mais les données sont stockées chez nous.

### UUID comme identifiant de pays
- Rejeté : l'ISO 3166-1 `iso2` est bien plus pratique. L'UUID reste stocké (colonne `uuid`) pour cohérence avec les autres entités.

## Conséquences

- Tables `countries`, `regions`, `cities` avec conventions HOSTO (UUID, timestamps tz, soft delete)
- Seed initial : 1 pays (Gabon), 9 provinces, ~40 villes principales
- Accessor bilingue avec fallback
- Exposition API publique (aucune auth requise) : `/api/v1/referentiel/countries`, `/regions`, `/cities`
