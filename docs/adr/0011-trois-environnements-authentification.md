# ADR 0011 — Trois environnements d'authentification séparés

**Statut** : Accepté
**Date** : 2026-04-18

## Contexte

HOSTO sert trois types d'acteurs fondamentalement différents. Chacun a ses propres besoins d'interface, de sécurité et de workflow. Partager un seul point d'entrée créerait de la confusion et des failles de sécurité.

## Décision

### Trois environnements isolés

| Environnement | URL | Acteurs | Rôle |
|---|---|---|---|
| **Admin** | `/admin` | Super admin (Yubile), Modérateurs, Ministère de la Santé | Pilotage plateforme, validation structures, analytics |
| **Pro** | `/pro` | Médecins, Pharmaciens, Laborantins, Administratifs hospitaliers, Responsables de structures | Gestion activité médicale, patients, stocks |
| **Usager** | `/compte` | Patients, Citoyens | Dossier patient, RDV, ordonnances, suivi |

### URLs de connexion

```
/admin/connexion      → Login administrateur (protégé, non indexé)
/pro/connexion        → Login professionnel de santé
/compte/connexion     → Login usager / patient
/compte/inscription   → Inscription usager
/pro/inscription      → Inscription professionnel (avec workflow validation)
```

**Pas d'inscription admin** : les comptes admin sont créés manuellement par un super admin existant.

### Architecture technique

#### Table `users` unique

On ne crée **pas** trois tables séparées. Une seule table `users` avec un système de rôles :

```
users
  ├── uuid, email, phone, password, 2FA...
  └── (rôles via table pivot user_roles)

roles
  ├── slug: super_admin, moderator, ministry
  ├── slug: structure_owner, doctor, pharmacist, lab_tech, nurse, admin_staff
  └── slug: patient

user_roles
  ├── user_id → users
  └── role_id → roles
```

**Pourquoi une seule table** :
- Un médecin peut aussi être patient → il a deux rôles
- Sanctum ne supporte qu'un seul Authenticatable par défaut
- L'audit trail est unifié (un acteur = un UUID)
- Le consentement patient fonctionne sur un UUID unique

#### Middleware guards

```php
// Trois groupes de middleware
Route::prefix('admin')->middleware(['auth:sanctum', 'role:super_admin,moderator,ministry'])
Route::prefix('pro')->middleware(['auth:sanctum', 'role:structure_owner,doctor,...'])
Route::prefix('compte')->middleware(['auth:sanctum', 'role:patient'])
```

Chaque environnement vérifie que l'utilisateur a le bon rôle. Un utilisateur sans le rôle requis reçoit un 403.

#### Trois layouts Blade séparés

```
resources/views/
  layouts/
    app.blade.php       → Layout public (annuaire, homepage)
    admin.blade.php     → Layout admin (sidebar, logo Yubile)
    pro.blade.php       → Layout professionnel (sidebar structure)
    compte.blade.php    → Layout usager/patient
  admin/
    connexion.blade.php
    dashboard.blade.php
    ...
  pro/
    connexion.blade.php
    dashboard.blade.php
    ...
  compte/
    connexion.blade.php
    inscription.blade.php
    dashboard.blade.php
    ...
```

### Sécurité par environnement

| Mesure | Admin | Pro | Usager |
|---|---|---|---|
| 2FA obligatoire | ✅ | ✅ | Optionnel |
| Verrouillage IP | Optionnel (configurable) | Non | Non |
| Session timeout | 30 min | 2h | 24h |
| Complexité mot de passe | 16 chars min | 12 chars min | 12 chars min |
| Logging connexion | Complet (IP, UA, geo) | Complet | Standard |
| Page non indexée (robots) | ✅ `noindex` | ✅ `noindex` | Non |

### Séparation visuelle

Chaque environnement a sa propre identité :

| | Admin | Pro | Usager |
|---|---|---|---|
| Couleur primaire | **Rouge foncé** (#B71C1C) | **Bleu** (#1565C0) | **Vert** (#388E3C) |
| Logo | HOSTO Admin | HOSTO Pro | HOSTO |
| Sidebar | Oui (navigation admin) | Oui (navigation structure) | Non (top-nav simple) |
| Footer | Minimaliste | Minimaliste | Complet |

### Flux d'inscription

#### Usager (patient)
```
1. Formulaire : nom, email/téléphone, mot de passe
2. Vérification email/SMS (OTP)
3. Compte actif immédiatement
4. 2FA proposé (optionnel)
```

#### Professionnel
```
1. Formulaire : nom, email, téléphone, mot de passe
2. Vérification email/SMS (OTP)
3. Sélection du type de professionnel (médecin, pharmacien, etc.)
4. Upload justificatifs (diplôme, ordre professionnel, etc.)
5. Compte en statut "en_attente_validation"
6. → Validation par admin (cf ADR 0009)
7. Compte actif après validation
8. 2FA obligatoire activé
```

#### Admin
```
Pas d'inscription publique.
Créé par un super_admin via /admin/utilisateurs/creer
2FA obligatoire immédiatement.
```

### Impact sur les phases existantes

- **Phase 1** : aucun impact (annuaire public, pas d'auth)
- **Phase 3** : implémente les tables, guards, login, layouts
- **Phase 4+** : les modules (RDV, Pro, Pharma) utilisent les guards correspondants

## Alternatives considérées

### Trois applications Laravel séparées
- **Rejeté** : triplerait la maintenance, la base de code, les déploiements. Un seul utilisateur peut avoir plusieurs rôles (médecin + patient).

### Un seul login avec redirection par rôle
- **Rejeté** : confusion UX ("je suis médecin, pourquoi je vois le formulaire patient ?"), surface d'attaque plus large.

### Multi-tenancy (Tenancy for Laravel)
- **Rejeté** : surdimensionné. Les trois environnements partagent la même base de données. La séparation est au niveau routage + middleware, pas au niveau base de données.
