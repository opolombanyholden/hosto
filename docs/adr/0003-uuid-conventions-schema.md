# ADR 0003 — UUID et conventions de schéma

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

HOSTO doit supporter :
- Synchronisation bidirectionnelle cloud ↔ local : les identifiants doivent être uniques **globalement**, sans coordination
- Export FHIR : les ressources FHIR utilisent des IDs textuels stables
- Non-devinabilité des URLs (`/api/v1/patients/12345` révèle le nombre de patients)

## Décision

Chaque entité persistée a :

| Colonne | Type | Rôle |
|---|---|---|
| `id` | `bigint` auto-increment | Clé primaire interne (performance jointures) |
| `uuid` | `uuid` UNIQUE NOT NULL | Identifiant métier exposé via API |
| `created_at` | `timestamptz` | Création |
| `updated_at` | `timestamptz` | Dernière modification |
| `deleted_at` | `timestamptz` NULL | Soft delete (sauf tables d'audit) |
| `created_by` | `uuid` NULL | UUID utilisateur créateur |
| `updated_by` | `uuid` NULL | UUID utilisateur dernière modif |

Pour les entités **synchronisables** (patient, acte médical, ordonnance, etc.) :

| Colonne supplémentaire | Type | Rôle |
|---|---|---|
| `origin` | `varchar(50)` | `cloud` ou identifiant de structure locale |
| `sync_version` | `bigint` | Horloge logique (Lamport) pour résolution de conflits |
| `sync_status` | `varchar(20)` | `synced`, `pending`, `conflict` |

## Règles

1. **L'API n'expose jamais `id` interne**. Uniquement `uuid`.
2. **Jointures internes** utilisent `id` (performance).
3. **FK inter-modules** utilisent `uuid` (découplage) ou une table de liaison si cardinalité élevée.
4. **Pas de `DELETE` dur** sauf décision RGPD explicite. Toujours `deleted_at`.
5. **`updated_at` est un trigger PostgreSQL** (pas un hook applicatif) : aucune fuite possible.

## Alternatives considérées

### UUID comme clé primaire
- Rejeté : fragmentation d'index, jointures 2-3x plus lentes. Hybride (bigint interne + uuid exposé) reconnu comme best practice.

### ULID à la place d'UUID
- Envisagé : triable par date. Rejeté : UUIDv7 (RFC 9562) résout le même besoin et est maintenant natif PostgreSQL 17.

## Conséquences

- Toutes les migrations **doivent** utiliser un helper `Schema::baseTable()` (à créer)
- Trigger PostgreSQL automatique pour `updated_at`
- Les seeders et factories génèrent des UUID v7
