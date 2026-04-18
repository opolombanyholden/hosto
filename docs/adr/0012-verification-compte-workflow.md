# ADR 0012 — Workflow de vérification des comptes usager et professionnel

**Statut** : Accepté
**Date** : 2026-04-18

## Contexte

L'inscription seule ne suffit pas pour accéder aux fonctionnalités transactionnelles de HOSTO. Il faut distinguer :
- Un compte **créé** (peut se connecter, accès limité)
- Un compte **vérifié** (email + téléphone confirmés, plein accès usager)
- Un compte **validé** (pour les pros uniquement : dossier examiné par un admin)

## Décision

### Parcours usager (patient)

```
1. INSCRIPTION         Nom, email, téléphone, mot de passe
                       → Compte créé. Accès au dashboard (lecture seule).

2. VERIFICATION EMAIL  Un code OTP est envoyé par email.
                       L'usager le saisit dans HOSTO.
                       → email_verified_at renseigné.

3. VERIFICATION TEL    Un code OTP est envoyé par SMS.
                       L'usager le saisit dans HOSTO.
                       → phone_verified_at renseigné.

4. COMPTE ACTIF        Email + téléphone vérifiés.
                       → Accès complet : RDV, ordonnances, dossier médical,
                         like, recommandation, etc.
```

### Parcours professionnel

```
1. INSCRIPTION         Nom, email, téléphone, profession, mot de passe
                       → Compte créé. Accès au dashboard (lecture seule).

2. VERIFICATION EMAIL  Même processus que l'usager.
   + VERIFICATION TEL  → email_verified_at + phone_verified_at renseignés.

3. SOUMISSION DOSSIER  Le professionnel upload ses documents légaux :
                       - Diplôme ou attestation de formation
                       - Inscription à l'Ordre professionnel (si applicable)
                       - Autorisation d'exercer
                       - Pièce d'identité
                       → Dossier en statut "submitted"

4. VALIDATION ADMIN    Un administrateur HOSTO examine le dossier :
                       - approved    → pro_validated_at renseigné
                       - rejected    → motif envoyé, possibilité de re-soumettre
                       - suspended   → demande de documents complémentaires

5. COMPTE PRO ACTIF    Email vérifié + téléphone vérifié + dossier validé.
                       → Accès complet : patients, consultations, ordonnances,
                         gestion structure, etc.
```

### Colonnes sur la table `users`

Les colonnes `email_verified_at` et `phone_verified_at` existent déjà. On ajoute :

| Colonne | Type | Rôle |
|---|---|---|
| `pro_validated_at` | timestamptz NULL | Date de validation du dossier pro |
| `pro_validated_by` | uuid NULL | Admin qui a validé |
| `pro_validation_status` | varchar(20) NULL | pending, submitted, approved, rejected, suspended |
| `pro_rejection_reason` | text NULL | Motif du rejet (si applicable) |

### Table `user_documents` (documents légaux des pros)

| Colonne | Type | Rôle |
|---|---|---|
| id | bigint | PK |
| uuid | uuid | Identifiant public |
| user_id | FK users | Le professionnel |
| type | varchar(50) | diploma, license, id_card, order_registration, other |
| name | varchar | Nom du fichier original |
| path | varchar | Chemin stockage sécurisé (non public) |
| mime_type | varchar(50) | |
| file_size | int | |
| status | varchar(20) | uploaded, verified, rejected |
| note | text NULL | Note de l'admin |
| uploaded_at | timestamptz | |
| reviewed_at | timestamptz NULL | |
| reviewed_by | uuid NULL | |

### Niveaux d'accès par état du compte

#### Usager

| État | Accès |
|---|---|
| Inscrit (non vérifié) | Dashboard, profil, voir annuaire |
| Email vérifié | + consulter fiches détaillées |
| Email + Téléphone vérifiés | **Accès complet** : RDV, téléconsultation, like, recommander, évaluer, dossier médical |

#### Professionnel

| État | Accès |
|---|---|
| Inscrit (non vérifié) | Dashboard, profil, voir annuaire |
| Email + Téléphone vérifiés | + consulter fiches, soumettre dossier pro |
| Dossier soumis (en attente) | Même accès, bandeau "En attente de validation" |
| Dossier validé | **Accès complet** : patients, consultations, ordonnances, gestion structure |
| Dossier rejeté | Accès limité + notification + possibilité de re-soumettre |

### Middleware `verified`

Un nouveau middleware `verified` vérifie l'état du compte :

```php
// Vérifie email + téléphone
Route::middleware(['auth', 'env:usager', 'verified'])

// Vérifie email + téléphone + validation pro
Route::middleware(['auth', 'env:pro', 'verified', 'pro.validated'])
```

Les routes non protégées par `verified` restent accessibles (dashboard, profil, page de vérification elle-même).

### OTP (One-Time Password)

Pour la vérification email et téléphone :
- Code à 6 chiffres, valide 10 minutes
- Stocké hashé dans une table `otp_codes` ou en session
- Maximum 5 tentatives avant blocage temporaire (30 minutes)
- Renvoi possible après 60 secondes

**Phase d'implémentation** :
- Email OTP : Phase 3.1 (immédiat, SMTP suffit)
- SMS OTP : Phase 3.2 (nécessite un fournisseur SMS : Twilio, Africa's Talking, ou gateway locale)

En attendant le SMS, la vérification téléphone peut être :
1. Manuelle (admin vérifie par appel)
2. Via WhatsApp Business API (plus courant au Gabon)
3. Différée (le compte fonctionne avec email vérifié seul, SMS ajouté plus tard)

## Impact sur le code existant

### Migration à ajouter

```sql
ALTER TABLE users ADD COLUMN pro_validated_at timestamptz NULL;
ALTER TABLE users ADD COLUMN pro_validated_by uuid NULL;
ALTER TABLE users ADD COLUMN pro_validation_status varchar(20) NULL;
ALTER TABLE users ADD COLUMN pro_rejection_reason text NULL;

CREATE TABLE user_documents (...);
```

### AuthController

Après inscription, rediriger vers une page de vérification email au lieu du dashboard direct.

### Bandeaux d'état

Chaque dashboard affiche un bandeau contextuel :

- **Email non vérifié** : bandeau jaune "Vérifiez votre adresse email pour activer votre compte"
- **Téléphone non vérifié** : bandeau jaune "Vérifiez votre numéro de téléphone"
- **Dossier pro en attente** : bandeau bleu "Votre dossier est en cours de validation"
- **Dossier pro rejeté** : bandeau rouge "Votre dossier a été rejeté : {motif}"

## Alternatives considérées

### Vérification obligatoire avant toute connexion
- **Rejeté** : frustrant pour l'utilisateur. Il doit pouvoir se connecter et voir son dashboard même sans avoir vérifié.

### Pas de vérification téléphone
- **Rejeté** : le téléphone est le canal principal en Afrique (SMS, WhatsApp). Le vérifier garantit la joignabilité pour les RDV et notifications.

### Validation pro automatique
- **Rejeté** : risque trop élevé. Un faux médecin pourrait accéder à des dossiers patients. La validation humaine est non négociable pour la v1.
