# ADR 0004 — Audit trail global et immutabilité

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

Le RGPD et la loi gabonaise exigent la **traçabilité** de tout accès à une donnée personnelle, en particulier médicale. Au-delà de l'exigence légale, l'audit est indispensable :
- Détection d'accès suspects (détournement par un professionnel)
- Défense juridique en cas de contentieux
- Répondre aux demandes des patients ("qui a consulté mon dossier ?")

## Décision

### Ce qui est audité

**Obligatoirement** :
- Toute **lecture** d'une ressource médicale (dossier patient, ordonnance, examen, etc.) par un professionnel
- Toute **écriture** (CREATE/UPDATE/DELETE) sur ressource médicale ou administrative
- Toute **authentification** (succès, échec, 2FA)
- Toute **modification de consentement**
- Toute **action d'administration** (création de compte pro, changement de rôle)

**Non audité** :
- Navigation publique (annuaire sans authentification)
- Lectures de référentiels génériques (liste de spécialités, etc.)

### Structure de la table `audit_logs`

```sql
CREATE TABLE audit_logs (
    id            bigserial PRIMARY KEY,
    uuid          uuid UNIQUE NOT NULL,
    occurred_at   timestamptz NOT NULL DEFAULT now(),

    -- Acteur
    actor_uuid    uuid NULL,            -- NULL = système
    actor_type    varchar(50),          -- patient, professionnel, admin, system
    actor_ip      inet,
    actor_ua      text,

    -- Contexte métier
    action        varchar(100) NOT NULL, -- read, create, update, delete, login, etc.
    resource_type varchar(100),         -- patient, ordonnance, consentement, ...
    resource_uuid uuid NULL,

    -- Détails
    payload       jsonb,                 -- avant/après pour update
    metadata      jsonb,                 -- contextuel (structure, session, etc.)

    -- Intégrité
    previous_hash bytea,                 -- hash du log précédent
    signature     bytea                  -- HMAC-SHA256(payload || previous_hash)
) PARTITION BY RANGE (occurred_at);
```

### Immutabilité

- **Aucune API** ne permet UPDATE ou DELETE sur `audit_logs`
- Permission PostgreSQL : utilisateur applicatif a INSERT + SELECT uniquement
- **Chaînage** : chaque ligne contient le hash de la précédente (détection de suppression)
- **Signature HMAC** avec clé rotative stockée hors de la base (HSM en prod, `.env` en dev)

### Partitionnement

Partition mensuelle : `audit_logs_2026_04`, `audit_logs_2026_05`...
- Performances : élagage automatique des partitions anciennes
- Archivage : partitions > 12 mois déplacées sur stockage froid
- Conformité : rétention légale 5 ans (données médicales) / 3 ans (accès)

## Implémentation technique

1. Trait `Auditable` sur les modèles concernés (hook `retrieved`, `created`, `updated`, `deleted`)
2. Middleware `AuditApiAccess` qui capture contexte HTTP
3. Service `AuditLogger` : point unique d'écriture, calcule hash et signature
4. Pas de queue : l'audit est **synchrone** pour éviter toute perte

## Alternatives considérées

### Audit asynchrone (queue Redis)
- Rejeté : risque de perte en cas de crash. Priorité à la complétude.

### Laravel Auditing (package)
- Évalué : bon point de départ mais ne gère pas le chaînage cryptographique. On code notre propre couche par-dessus les events Eloquent.

## Conséquences

- Volumétrie : prévoir ~10-20 lignes d'audit par consultation médicale → plusieurs millions de lignes/an à 10k patients actifs → partitionnement vital
- Performance : insert synchrone = ~0.5ms surcoût par écriture métier. Acceptable.
- Conformité : prêt pour audit RGPD
