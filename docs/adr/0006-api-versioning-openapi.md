# ADR 0006 — API versioning et contrat OpenAPI

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

HOSTO expose une API qui sera consommée par :
- Applications mobiles Flutter (iOS/Android) — déployées, mises à jour indépendamment
- Application desktop Flutter
- Partenaires externes (CNAMGS, assurances, pharmacies tierces)

Les clients mobiles ne peuvent pas être forcés à se mettre à jour : l'API doit rester rétrocompatible pendant des mois.

## Décision

### Versioning par préfixe d'URL

```
/api/v1/annuaire/hostos
/api/v2/annuaire/hostos
```

**Pas de versioning par header** (`Accept: application/vnd.hosto.v2+json`) : plus élégant mais moins lisible, moins cachable côté CDN.

### Règles de rétrocompatibilité

Dans une même version majeure (`v1`) :
- **Autorisé** : ajouter des endpoints, ajouter des champs optionnels aux réponses, ajouter des paramètres de requête optionnels
- **Interdit** : supprimer un champ, renommer, changer un type, rendre un champ obligatoire

Changement cassant → **nouvelle version majeure** avec période de transition minimum 6 mois.

### Contrat OpenAPI 3.1 obligatoire

- Toute route API **doit** être documentée dans OpenAPI
- Génération automatique via [Scribe](https://scribe.knuckles.wtf/laravel) (plutôt que l5-swagger)
- Le fichier `openapi.yaml` est versionné avec le code
- CI bloque tout PR qui modifie une route sans mettre à jour la doc

### Format de réponse standard

Succès :
```json
{
    "data": { ... } ou [ ... ],
    "meta": {
        "pagination": { ... },
        "generated_at": "2026-04-15T10:30:00Z"
    }
}
```

Erreur :
```json
{
    "error": {
        "code": "VALIDATION_FAILED",
        "message": "Les données fournies sont invalides",
        "details": [
            { "field": "email", "reason": "Format invalide" }
        ],
        "request_id": "req_abc123"
    }
}
```

Codes HTTP strictement respectés : 200, 201, 204, 400, 401, 403, 404, 409, 422, 429, 500.

### Pagination

Curseur (`cursor`) pour les flux, offset/limit pour les catalogues.
- Défaut : 25 items
- Maximum : 100 items

## Alternatives considérées

### Scribe vs l5-swagger
- Scribe retenu : génération à partir d'annotations + introspection, plus moderne, meilleure DX
- l5-swagger demande d'écrire le YAML à la main

### GraphQL
- Rejeté : surcoût d'apprentissage équipe, outillage mobile moins mature, cache HTTP perdu

## Conséquences

- Le script de build CI génère `public/api-docs/openapi.yaml` et le sert via `/api/docs`
- Contrat testé par comparaison (pact-like) à chaque release
