# ADR 0009 — Workflow d'enregistrement de structure et interactions sociales

**Statut** : Accepté (spécification, implémentation Phase 3+)
**Date** : 2026-04-18

## Contexte

L'annuaire HOSTO Phase 1 affiche des structures saisies par l'équipe Yubile. Pour passer à l'échelle, les propriétaires de structures doivent pouvoir s'auto-enregistrer. Par ailleurs, les usagers doivent pouvoir interagir avec les structures (like, partage, recommandation, évaluation).

## Décisions

### 1. Enregistrement d'une structure par son propriétaire

**Workflow en 5 étapes :**

```
1. INSCRIPTION    Le propriétaire crée un compte professionnel
                  (email/téléphone + 2FA obligatoire)

2. SAISIE         Il remplit le formulaire complet de la structure :
                  - Informations obligatoires (nom, type, adresse, ville,
                    téléphone, spécialités, services)
                  - Horaires d'ouverture
                  - Médias (logo, couverture, galerie)

3. JUSTIFICATIFS  Il upload les documents légaux :
                  - Registre de commerce ou autorisation d'exercer
                  - Pièce d'identité du représentant légal
                  - Tout document prouvant la propriété ou la
                    représentation légale de la structure

4. SOUMISSION     Le dossier passe en statut "en_attente_validation"
                  → notification aux administrateurs Yubile

5. VALIDATION     Un administrateur examine le dossier :
                  - Approuvé   → structure publiée dans l'annuaire
                  - Rejeté     → motif envoyé au propriétaire,
                                 possibilité de re-soumettre
                  - Suspendu   → en attente de documents complémentaires
```

**Table prévue : `structure_claims`**

| Colonne | Type | Rôle |
|---|---|---|
| uuid | uuid | Identifiant public |
| user_id | FK users | Le demandeur (compte pro) |
| hosto_id | FK hostos (nullable) | Structure existante revendiquée, ou NULL si nouvelle |
| status | enum | draft, submitted, under_review, approved, rejected, suspended |
| company_name | varchar | Raison sociale |
| registration_number | varchar | Numéro RCCM / autorisation |
| representative_name | varchar | Nom du représentant légal |
| representative_role | varchar | Fonction (Directeur, Gérant, etc.) |
| documents | jsonb | Liste des fichiers uploadés [{name, path, type, uploaded_at}] |
| rejection_reason | text | Motif du rejet (si applicable) |
| reviewed_by | FK users | Administrateur ayant validé/rejeté |
| reviewed_at | timestamptz | Date de la décision |
| submitted_at | timestamptz | Date de soumission |

**Règles métier :**
- Un compte pro ne peut avoir qu'un seul claim actif par structure
- Les documents uploadés sont stockés de manière sécurisée (non publics)
- Toute action de validation est auditée (AuditLogger)
- Le rejet n'est pas définitif : le demandeur peut corriger et re-soumettre

### 2. Interactions sociales sur les structures

**4 types d'interaction :**

| Interaction | Visibilité | Auth requise | Table |
|---|---|---|---|
| **Like** | Compteur public (nombre) | Oui (usager) | `hosto_likes` |
| **Partage** | Lien partageable public | Non | Pas de table (URL + meta OG) |
| **Recommandation** | Publique (texte + auteur) | Oui (usager) | `hosto_recommendations` |
| **Évaluation** | **PRIVÉE** — visible uniquement par le responsable de la structure et le Ministère de la Santé | Oui (usager) | `hosto_evaluations` |

**Table `hosto_likes` :**

| Colonne | Type |
|---|---|
| user_id | FK users |
| hosto_id | FK hostos |
| created_at | timestamptz |
| UNIQUE(user_id, hosto_id) | |

**Table `hosto_recommendations` :**

| Colonne | Type |
|---|---|
| uuid | uuid |
| user_id | FK users |
| hosto_id | FK hostos |
| content | text (max 500 chars) |
| is_approved | boolean (modération) |
| created_at | timestamptz |

**Table `hosto_evaluations` :**

| Colonne | Type | Rôle |
|---|---|---|
| uuid | uuid | |
| user_id | FK users | Évaluateur |
| hosto_id | FK hostos | Structure évaluée |
| score_accueil | smallint (1-5) | Qualité de l'accueil |
| score_proprete | smallint (1-5) | Propreté des locaux |
| score_competence | smallint (1-5) | Compétence du personnel |
| score_delai | smallint (1-5) | Respect des délais |
| score_global | smallint (1-5) | Note globale |
| comment | text | Commentaire libre |
| created_at | timestamptz | |

**Règle critique** : les évaluations ne sont **jamais affichées publiquement**. Seuls peuvent y accéder :
- Le responsable vérifié de la structure (via son compte pro lié)
- Les administrateurs Yubile
- Le Ministère de la Santé (via Hosto Analytic, données agrégées)

Cette décision protège les structures contre le dénigrement public tout en fournissant un retour constructif aux responsables et un outil de pilotage au ministère.

### 3. Partage (sans table)

Le partage est un simple lien URL avec des meta tags Open Graph sur la page detail :
```html
<meta property="og:title" content="{nom de la structure}">
<meta property="og:description" content="{types} - {ville}">
<meta property="og:image" content="{cover_image ou profile_image}">
<meta property="og:url" content="https://hosto.ga/annuaire/{slug}">
```

Un bouton "Partager" sur la fiche appelle `navigator.share()` (Web Share API) ou copie l'URL dans le presse-papiers en fallback.

## Planning d'implémentation

| Phase | Livrable |
|---|---|
| **Phase 2.5** | Likes + partage (meta OG + bouton share) + recommandations publiques |
| **Phase 3** | Comptes usagers + comptes professionnels + RBAC |
| **Phase 3.5** | Workflow enregistrement structure (claim + upload + validation admin) |
| **Phase 3.5** | Évaluations privées (saisie usager, lecture pro + admin + ministère) |

## Impact sur la Phase 1 actuelle

**Aucune modification requise.** Les tables actuelles (`hostos`, `hosto_media`, etc.) sont compatibles. Les colonnes `is_verified`, `verified_by`, `verified_at` déjà présentes sur `hostos` serviront directement au workflow de validation.

Le champ `created_by` (via TracksActor) identifiera le propriétaire une fois les comptes en place.
