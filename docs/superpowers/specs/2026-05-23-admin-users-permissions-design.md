# Administration utilisateurs, rôles et permissions — Conception

**Date** : 2026-05-23
**Tâche** : T7 du backlog test HOSTO
**Module** : `app/Modules/Core` (extension), nouvelles vues sous `resources/views/admin/`
**Statut** : conception validée, en attente de plan d'implémentation

---

## 1. Objectif et portée

Bâtir un système RBAC complet pour HOSTO Admin :

- **Gestion complète des utilisateurs** : recherche/filtres, fiche détail, création, édition, suspension, reset de mot de passe, soft delete + restauration, validation pro, sessions/tokens, impersonation, export CSV, actions en lot.
- **Modèle rôles + permissions** standard (à la Spatie) avec scoping par structure.
- **Catégories de professionnels** : table dédiée avec hiérarchie, icône, couleur, etc.

L'existant minimaliste est conservé : `User::hasRole($slug)` continue de marcher (compatibilité). Nouvelle méthode `User::can($permission, $scope?)` pour la granularité.

### Hors-scope explicite

- Pas de notifications email/SMS sur actions admin. Le mot de passe temporaire est affiché en flash à l'admin pour transmission manuelle.
- Pas de 2FA forcé par rôle (le système 2FA existe déjà via `google2fa-laravel`, le forcing par rôle viendra plus tard).
- Pas d'audit "qui a vu ce dossier" — seules les actions write sont loguées.
- Pas de drag-and-drop pour l'arbre des catégories pro (juste un select `parent_category_id`).
- Pas de quotas / rate-limit sur les actions admin.
- Pas de promotion auto super_admin — toute escalade reste manuelle par un super_admin existant.

### Métriques de succès post-déploiement

- 100 % des routes admin sont gardées par une permission explicite (audit `grep "->middleware('perm:'" app/Modules/Core/Http/Controllers/Admin/`).
- Couverture > 90 % sur les services (`UserAdminService`, `RoleAssignmentService`, `PermissionResolver`, `ImpersonationService`).
- 0 régression sur les tests existants après ajout des permissions.
- `php artisan migrate:fresh --seed` passe sans erreur.
- Time-to-create d'un nouvel utilisateur complet (form ouvert → user créé + rôles assignés) < 30 s pour un admin formé.

---

## 2. Décisions structurantes (résumé du brainstorming)

| Question | Décision |
|---|---|
| Modèle de permissions ? | **(B)** Rôles + permissions standard (table `permissions` + pivot `role_permissions`). `hasRole()` reste compatible. |
| Périmètre actions admin ? | **Toutes** (a→m) : filtres, détail, create, edit, assign roles, suspend, reset_password, soft delete, sessions, impersonate, export CSV, validate_pro, bulk actions. |
| Liste des rôles ? | 11 rôles validés + 2 nouveaux slugs : `compta`, `stat`. Labels FR mis à jour : `structure_owner` → "Gestionnaire de compte" ; `ministry` → "Gouvernement / Ministère santé" ; `admin_staff` → "Administratif". **Les slugs techniques existants ne sont pas renommés** (compat code existant). |
| Matrice rôle ↔ permissions modifiable ? | **Oui dès la v1** depuis l'admin (`/admin/roles/{slug}/permissions`). Sauf `super_admin` (verrouillé). |
| Catégories pro ? | **(B)** Table dédiée `practitioner_categories` avec icône, couleur, parent_category_id, is_medical. 15 catégories seedées. |
| Portée des rôles ? | **(B)** Pivot `user_roles` étendu avec `scope_type` + `scope_id` (polymorphique). Rôles globaux = scope null, rôles structure = scope hosto. |

---

## 3. Modèle de données

### 3.1 Nouvelles tables

```text
permissions
  ├ id, uuid
  ├ slug           varchar(80) UNIQUE     -- 'users.create', 'roles.assign', 'pro_categories.manage'
  ├ scope          varchar(40)            -- 'users', 'roles', 'structures', 'consultations', 'pro_categories',
  │                                          'payments', 'invoices', 'stats', 'exports', 'claims', 'permissions', 'self'
  ├ name_fr        varchar(255)
  ├ name_en        varchar(255) NULL
  ├ description_fr text NULL
  ├ is_active      boolean DEFAULT true
  ├ display_order  smallint DEFAULT 0
  ├ created_at / updated_at
  └ index (scope)

role_permissions
  ├ id
  ├ role_id        fk roles CASCADE
  ├ permission_id  fk permissions CASCADE
  ├ created_at
  └ unique (role_id, permission_id)

practitioner_categories
  ├ id, uuid
  ├ code                varchar(40) UNIQUE        -- 'doctor', 'specialist', 'dentist'...
  ├ name_fr             varchar(120)
  ├ name_en             varchar(120) NULL
  ├ description_fr      text NULL
  ├ icon_name           varchar(40) NULL          -- nom d'icône (ex 'stethoscope')
  ├ color_hex           varchar(7) NULL           -- '#388E3C'
  ├ parent_category_id  fk self NULL              -- hiérarchie
  ├ is_medical          boolean DEFAULT true
  ├ display_order       smallint DEFAULT 0
  ├ is_active           boolean DEFAULT true
  ├ created_at / updated_at / deleted_at
  └ index (is_active, display_order), index (parent_category_id)

impersonation_sessions
  ├ id, uuid
  ├ admin_user_id   fk users
  ├ target_user_id  fk users
  ├ reason          text NOT NULL                -- justification obligatoire
  ├ started_at      timestampTz
  ├ ended_at        timestampTz NULL
  ├ ip_address      varchar(45)
  ├ user_agent      text NULL
  └ index (admin_user_id, started_at), index (target_user_id, started_at)
```

### 3.2 Tables modifiées

```text
roles  (existante, ajouts)
  └ + is_system   boolean DEFAULT false             -- super_admin = true (non supprimable, non éditable)

user_roles  (existante, pivot étendu)
  ├ + scope_type   varchar(80) NULL                 -- 'App\Modules\Annuaire\Models\Hosto'
  ├ + scope_id     bigint NULL                      -- id de la structure scopée, null si rôle global
  ├ + assigned_by  bigint NULL fk users             -- qui a effectué l'assignation
  ├ + assigned_at  timestampTz DEFAULT now()
  ├ + expires_at   timestampTz NULL                 -- rôles temporaires (intérim, mission)
  └ unique (user_id, role_id, scope_type, scope_id)

users  (existante, ajout minimal)
  └ + must_change_password   boolean DEFAULT false  -- pour reset admin (forcer changement au prochain login)
       (locked_until existe déjà — on l'utilise pour suspend : '9999-12-31' = suspension permanente)

practitioners  (existante, ajout)
  └ + practitioner_category_id  fk practitioner_categories NULL
       -- on garde aussi practitioner_type (texte) pour compat,
       -- mais la validation pousse à choisir une catégorie de la table
```

### 3.3 Invariants

- `super_admin` est marqué `is_system = true`, a une permission virtuelle `*` (hardcodée dans `User::can()`). Il n'a aucune ligne dans `role_permissions` (court-circuit pur). Garantit l'accès même si la matrice est vidée par erreur.
- Un rôle avec `scope_type = null` est un rôle **global** (super_admin, compta, gouv, stat, moderator, patient).
- Un rôle avec `scope_type = 'App\Modules\Annuaire\Models\Hosto'` + `scope_id = 42` est un rôle **scopé à la structure 42** (structure_owner, doctor, admin_staff, nurse, pharmacist, lab_tech).
- Unicité `(user_id, role_id, scope_type, scope_id)` : un même rôle peut être assigné plusieurs fois si scopes différents (ex : Marie owner de la Clinique X **et** de la Clinique Y).
- `expires_at` non null = rôle temporaire (ignoré par `PermissionResolver` après expiration).
- L'impersonation est intégralement journalisée dans `impersonation_sessions` (start + end + raison + IP + UA).
- Toute action admin (create user, suspend, reset_pwd, assign role, modifier permissions...) loggue via `AuditLogger`.
- Refus de supprimer le **dernier super_admin** (cas verrouillé dans le service).
- Refus d'impersonifier un super_admin (anti-escalation), ni soi-même.

---

## 4. Composants

```text
app/Modules/Core/
├── Models/
│   ├── Role.php                       (existant — étendu : permissions() relation, is_system)
│   ├── Permission.php                 (NOUVEAU)
│   ├── UserRoleAssignment.php         (NOUVEAU — modèle pivot user_roles avec scope/assigned_by/expires_at)
│   └── ImpersonationSession.php       (NOUVEAU)
│
├── Services/
│   ├── PermissionResolver.php
│   │     userCan(User $u, string $permission, ?Model $scope = null): bool
│   │     userPermissions(User $u): Collection<Permission>      (cache per-request)
│   │     hasGlobalRole(User $u, string $slug): bool
│   │     hasScopedRole(User $u, string $slug, Model $scope): bool
│   │
│   ├── UserAdminService.php
│   │     createUser(array $data): User                         (auto-genère password si vide, set must_change_password)
│   │     updateUser(User, array): User                         (audit log diff)
│   │     suspend(User, ?string $reason): void                  (locked_until = +infinity + revoke tokens)
│   │     reactivate(User): void                                (locked_until = null)
│   │     resetPassword(User): string                           (retourne le mdp temporaire en clair pour transmission)
│   │     softDelete(User): void                                (refuse si dernier super_admin)
│   │     restore(User): void
│   │     validatePro(User, bool $approve, ?string $reason): void
│   │     bulkAction(Collection<User>, string $action, array $params): int     (refuse > 100 cibles)
│   │
│   ├── RoleAssignmentService.php
│   │     assign(User, Role, ?Model $scope, ?User $by, ?\DateTimeInterface $expiresAt): void
│   │     revoke(User, Role, ?Model $scope): void
│   │     syncRolesForScope(User, array<Role>, ?Model $scope): array          (pour UI checkbox)
│   │     usersHavingRole(Role, ?Model $scope): Builder
│   │
│   ├── ImpersonationService.php
│   │     start(User $admin, User $target, string $reason, Request): ImpersonationSession
│   │     stop(): void                                         (lit session, marque ended_at, restaure admin)
│   │     currentSession(): ?ImpersonationSession
│   │
│   └── UserExportService.php
│         exportCsv(Builder $query): StreamedResponse           (Symfony StreamedResponse pour ne pas charger en mémoire)
│
├── Http/
│   ├── Controllers/Admin/
│   │   ├── AdminUsersController.php           (13 méthodes — voir Workflows)
│   │   ├── AdminRolesController.php           (index, show, create, store, edit, update, destroy, assignPermissions, assignToUser)
│   │   ├── AdminPermissionsController.php     (index lecture seule, pas de CRUD)
│   │   └── ImpersonationController.php        (stop only — start est sur AdminUsersController)
│   │
│   └── Middleware/
│       ├── EnsurePermission.php
│       │     Usage : ->middleware('perm:users.delete')
│       │     ->middleware('perm:structures.edit,scope=hosto')  (pour scopage via route param)
│       └── EnsureNotSuspended.php
│             Si locked_until > now() → logout + flash + redirect /compte/connexion
│
├── Database/
│   ├── Migrations/  (5 nouvelles)
│   └── Seeders/
│       ├── PermissionsSeeder.php            (~41 permissions catalogue)
│       └── RolePermissionsSeeder.php        (matrice par défaut rôle → permissions)
│
└── Console/Commands/
    └── ImpersonationStopAll.php             (commande artisan d'urgence : termine toutes les sessions actives)

app/Modules/Annuaire/
├── Models/
│   └── PractitionerCategory.php       (NOUVEAU — placé dans Annuaire car lié à Practitioner)
├── Database/
│   ├── Migrations/
│   │   ├── create_practitioner_categories_table.php
│   │   └── add_practitioner_category_id_to_practitioners.php
│   └── Seeders/
│       └── PractitionerCategoriesSeeder.php   (15 catégories de base)
└── Http/Controllers/Admin/
    └── AdminPractitionerCategoriesController.php  (CRUD + reorder)

resources/views/admin/
├── users/
│   ├── index.blade.php       (liste + filtres + pagination + bulk select)
│   ├── show.blade.php        (détail : profil + rôles + dépendants + audit)
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── sessions.blade.php
├── roles/
│   ├── index.blade.php
│   ├── show.blade.php        (perm associées + users avec ce rôle)
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── permissions.blade.php (matrice scope × actions)
├── permissions/
│   └── index.blade.php       (catalogue read-only)
└── practitioner-categories/
    ├── index.blade.php
    └── form.blade.php        (create + edit partagé)

resources/views/layouts/partials/
└── impersonation-banner.blade.php  (top banner si session active)
```

### Principes

- **PermissionResolver est le seul endroit où on vérifie les permissions** — partout ailleurs on appelle `$user->can('users.delete')` (Gate Laravel) qui délègue.
- **`$user->can('*')` retourne true pour super_admin** (court-circuit dans le resolver).
- **Le middleware `perm:`** est utilisable au niveau route (`->middleware('perm:users.create')`) — alternative au Gate dans le contrôleur.
- **Tous les services Admin sont indépendants et testables isolément** (pas de dépendances croisées entre `UserAdminService` et `RoleAssignmentService`).
- **`UserAdminService` ne supprime jamais en hard** (soft delete uniquement).
- **`ImpersonationService` empêche d'impersonifier un autre super_admin** (sécurité : limite l'escalade).
- **Le banner d'impersonation** est injecté dans le layout dashboard via `@include` quand la session est active — impossible d'oublier qu'on est en mode "vu comme".
- **Les permissions sont définies en code** (seeder) — pas de CRUD UI dessus (sinon n'importe quel admin pourrait inventer une permission inutile). La matrice rôle↔permissions, elle, est éditable.

---

## 5. Workflows

### 5.1 Admin gère un utilisateur (CRUD + actions)

```text
GET /admin/utilisateurs?q=ndong&role=doctor&env=pro&status=active&page=1
  ├ permission : users.view
  ├ filtres : q (name/email/nip/phone ILIKE), role (slug), env (admin|pro|usager),
  │          status (active|locked|deleted), date_from, date_to
  ├ tri par défaut : created_at DESC
  └ vue : liste paginée (30/page) + checkbox bulk + bouton "+ Nouveau"

GET /admin/utilisateurs/{uuid}
  ├ permission : users.view
  └ vue : 4 cards
      1. Profil (nom, email, NIP, phone, photo, dates, statut)
      2. Rôles attachés (global + par scope structure) avec bouton retirer
      3. Dépendants (lecture seule)
      4. Audit récent (10 derniers events de audit_logs concernant cet user)

POST /admin/utilisateurs  (création)
  ├ permission : users.create
  ├ body : name, email, phone, password (auto-générée si vide), roles[]
  ├ Si password auto-généré : must_change_password = true
  ├ Audit log
  └ flash : "Compte créé. Mot de passe temporaire : XXXXXX (à transmettre)"

PUT /admin/utilisateurs/{uuid}
  ├ permission : users.edit
  ├ body : name, email, phone, nip
  ├ Audit log avec diff des champs modifiés
  └ flash

POST /admin/utilisateurs/{uuid}/suspend
  ├ permission : users.suspend
  ├ body : reason (texte obligatoire)
  ├ User.locked_until = '9999-12-31' + Audit log
  ├ Révocation des tokens Sanctum actifs (logout forcé)
  └ flash

POST /admin/utilisateurs/{uuid}/reactivate
  ├ permission : users.suspend
  └ User.locked_until = null

POST /admin/utilisateurs/{uuid}/reset-password
  ├ permission : users.reset_password
  ├ Génère un mdp aléatoire (16 chars alphanum + symboles)
  ├ User.password = hash(new), must_change_password = true
  ├ Révocation tokens actifs
  ├ Audit log (sans le mdp en clair)
  └ flash : "Nouveau mot de passe : XXXXXX (à transmettre par canal sécurisé)"

POST /admin/utilisateurs/{uuid}/validate-pro
  ├ permission : users.validate_pro
  ├ body : action (approve|reject), rejection_reason (si reject)
  └ Met à jour pro_validated_at, pro_validation_status, pro_rejection_reason

DELETE /admin/utilisateurs/{uuid}
  ├ permission : users.delete
  ├ refus 422 si user = dernier super_admin
  ├ refus 422 si user = self
  └ Soft delete + révocation tokens + Audit

POST /admin/utilisateurs/{uuid}/restore
  ├ permission : users.delete
  └ User->restore()

GET /admin/utilisateurs/{uuid}/sessions
  ├ permission : users.sessions
  └ vue : tokens Sanctum actifs (name, created_at, last_used_at) + bouton révoquer

DELETE /admin/utilisateurs/{uuid}/sessions/{tokenId}
  ├ permission : users.sessions
  └ Révoque le token

GET /admin/utilisateurs/export.csv?...filtres
  ├ permission : exports.users
  └ StreamedResponse CSV (Symfony) avec colonnes :
    uuid, name, email, phone, nip, env, roles, created_at, last_login_at, status

POST /admin/utilisateurs/bulk
  ├ permission : users.bulk
  ├ body : user_uuids[], action (suspend|delete|export), params{}
  ├ refus 410 si > 100 cibles (redirige vers export CSV)
  └ Traite chaque cible via UserAdminService, retourne count succès
```

### 5.2 Admin assigne des rôles (avec scope)

UI sur la fiche utilisateur, card "Rôles" :

```text
┌──────────────────────────────────────────────────┐
│  Rôles globaux                                   │
│  ☐ super_admin   ☐ moderator   ☐ compta          │
│  ☐ gouv          ☐ stat        ☑ patient         │
├──────────────────────────────────────────────────┤
│  Rôles par structure         [+ Ajouter structure]│
│  ─ Clinique Saint-Joseph (Libreville)            │
│    ☑ structure_owner   ☐ doctor   ☐ admin_staff  │
│    [×] Retirer cette structure                   │
│  ─ CHU Libreville                                │
│    ☐ structure_owner   ☑ doctor   ☐ admin_staff  │
│    Expire le : [____] (optionnel)                │
└──────────────────────────────────────────────────┘

PUT /admin/utilisateurs/{uuid}/roles
  ├ permission : roles.assign
  ├ body : { global_role_ids: [...], scoped: [{ structure_uuid, role_ids, expires_at? }] }
  ├ RoleAssignmentService.syncRolesForScope() pour chaque scope (global + chaque structure)
  ├ Audit log diff
  └ flash
```

### 5.3 Admin gère les rôles et leur matrice de permissions

```text
GET /admin/roles
  ├ permission : roles.view
  └ table : slug, name_fr, env, nb permissions, nb users, is_system, actions

POST /admin/roles
  ├ permission : roles.create
  └ body : slug, name_fr, name_en, environment, description_fr

PUT /admin/roles/{slug}/permissions
  ├ permission : permissions.assign
  ├ vue : matrice scope × actions (cases à cocher groupées par scope)
  ├ body : permission_ids[]
  ├ super_admin verrouillé (UI désactive + serveur refuse 422)
  └ Audit log diff

DELETE /admin/roles/{slug}
  ├ permission : roles.delete
  ├ refus 422 si is_system = true
  ├ refus 422 si users attachés > 0 (faut détacher d'abord)
  └ soft delete
```

### 5.4 Impersonation (debug support)

```text
Sur fiche utilisateur, bouton "Se connecter comme cet utilisateur" :

POST /admin/utilisateurs/{uuid}/impersonate
  ├ permission : users.impersonate
  ├ refus 403 si target = super_admin (anti-escalation)
  ├ refus 422 si target = self
  ├ body : reason (texte obligatoire)
  ├ Crée ImpersonationSession (admin_id, target_id, raison, IP, UA, started_at)
  ├ session(['impersonator_id' => admin->id, 'impersonation_session_id' => session->uuid])
  ├ Auth::login($target)
  └ redirect vers la racine de l'environnement du target (/compte ou /pro)

Toute requête de la session impersonifiée affiche un banner top :
  ┌──────────────────────────────────────────────────────────────────┐
  │ ⚠ Vous êtes connecté en tant que Marie NDONG (patient)           │
  │   [ Arrêter l'impersonation ]                                    │
  └──────────────────────────────────────────────────────────────────┘
  (injecté via @include('layouts.partials.impersonation-banner'))

GET /stop-impersonate
  ├ ImpersonationService->stop() : marque ended_at, Auth::logout(target), Auth::loginUsingId(admin_id)
  ├ session forget impersonator
  └ redirect /admin
```

### 5.5 Admin gère les catégories pro

```text
GET /admin/practitioner-categories
  ├ permission : pro_categories.view
  └ table : code, name_fr, parent, icône mini-SVG, couleur pastille, nb practitioners, actions

POST /admin/practitioner-categories
  ├ permission : pro_categories.manage
  ├ body : code, name_fr, name_en, description_fr, parent_category_id, icon_name, color_hex, is_medical
  └ create

PUT /admin/practitioner-categories/{uuid}
  └ update

DELETE /admin/practitioner-categories/{uuid}
  ├ refus 422 si practitioners liés > 0
  └ soft delete

POST /admin/practitioner-categories/reorder
  ├ body : [{ uuid, display_order }, ...]
  └ batch update
```

### 5.6 Cas d'erreur globaux

- User sans permission → 403 avec message explicite.
- Bulk action sur > 100 items → refus 410, redirige vers export CSV.
- Création user avec email déjà pris → 422 validation.
- Suspendre soi-même → refus 422.
- Supprimer le dernier super_admin → refus 422.
- Impersonifier un super_admin → refus 403.
- Reset password en mode impersonation → refus 403.
- Édition matrice de super_admin → refus 422 ("ce rôle a toutes les permissions et ne peut pas être modifié").

---

## 6. Catalogue des permissions et matrice par défaut

### 6.1 Catalogue (~41 permissions, seedées via `PermissionsSeeder`)

| Scope | Slug | Libellé FR |
|---|---|---|
| **users** | `users.view` | Voir la liste / fiche des utilisateurs |
|  | `users.create` | Créer un utilisateur |
|  | `users.edit` | Modifier les infos d'un utilisateur |
|  | `users.delete` | Supprimer (soft) / restaurer un utilisateur |
|  | `users.suspend` | Suspendre / réactiver |
|  | `users.reset_password` | Réinitialiser le mot de passe |
|  | `users.validate_pro` | Valider / rejeter un compte pro |
|  | `users.impersonate` | Se connecter "comme" un utilisateur (debug support) |
|  | `users.sessions` | Voir et révoquer les sessions/tokens actifs |
|  | `users.bulk` | Actions en lot sur plusieurs utilisateurs |
| **roles** | `roles.view` | Voir les rôles |
|  | `roles.create` | Créer un rôle |
|  | `roles.edit` | Modifier nom/description d'un rôle |
|  | `roles.delete` | Supprimer un rôle |
|  | `roles.assign` | Assigner / retirer des rôles à un user |
| **permissions** | `permissions.view` | Voir le catalogue des permissions |
|  | `permissions.assign` | Modifier la matrice rôle ↔ permissions |
| **structures** | `structures.view` | Voir les structures de l'annuaire |
|  | `structures.edit` | Éditer une structure |
|  | `structures.validate` | Valider une revendication (claim) de structure |
|  | `structures.delete` | Supprimer une structure |
| **pro_categories** | `pro_categories.view` | Voir les catégories de professionnels |
|  | `pro_categories.manage` | CRUD complet sur les catégories pro |
| **claims** | `claims.review` | Examiner et trancher les claims de structure |
| **consultations** | `consultations.view` | Voir les consultations (lecture globale) |
|  | `consultations.manage` | Gérer ses propres consultations (médecin) |
| **prescriptions** | `prescriptions.create` | Créer une ordonnance |
|  | `prescriptions.view` | Voir les ordonnances |
| **appointments** | `appointments.manage` | Gérer les rendez-vous (secrétariat) |
|  | `appointments.book.self` | Prendre rendez-vous pour soi (patient) |
| **payments** | `payments.view` | Voir les paiements |
|  | `payments.refund` | Émettre un remboursement |
| **invoices** | `invoices.manage` | Créer / valider des factures (compta) |
| **stats** | `stats.view` | Accéder aux tableaux de bord statistiques |
|  | `stats.view.financial` | Accéder aux KPI financiers (compta only) |
| **exports** | `exports.users` | Exporter la liste users en CSV |
|  | `exports.structures` | Exporter les structures |
|  | `exports.financial` | Exporter les données financières |
|  | `exports.stats` | Exporter les rapports stats |
| **self** | `self.profile` | Gérer son propre profil (tous comptes) |
|  | `self.medical_record` | Accéder à son dossier médical (patient) |
|  | `self.carnet_vaccination` | Voir/imprimer son carnet de vaccination |

Total : **41 permissions**.

### 6.2 Matrice par défaut (seedée via `RolePermissionsSeeder`)

| Rôle | Permissions accordées |
|---|---|
| `super_admin` | Toutes (via court-circuit, pas stocké en base) |
| `moderator` | `users.view`, `users.suspend`, `users.validate_pro`, `structures.view`, `structures.validate`, `claims.review`, `stats.view` |
| `ministry` (label FR mis à jour vers "Gouvernement / Ministère santé") | `users.view`, `structures.view`, `stats.view`, `stats.view.financial`, `exports.users`, `exports.structures`, `exports.stats` |
| `compta` (nouveau) | `users.view`, `payments.view`, `payments.refund`, `invoices.manage`, `stats.view.financial`, `exports.financial` |
| `stat` (nouveau) | `users.view`, `structures.view`, `stats.view`, `stats.view.financial`, `exports.users`, `exports.structures`, `exports.stats` |
| `structure_owner` (label "Gestionnaire de compte") | `structures.edit` (scopée), `appointments.manage` (scopée), `users.view` (scopée à sa structure), `pro_categories.view`, `stats.view` (scopée), `self.profile` |
| `admin_staff` (label "Administratif") | `appointments.manage` (scopée), `users.view` (scopée), `consultations.view` (scopée), `self.profile` |
| `doctor` | `consultations.manage`, `prescriptions.create`, `prescriptions.view`, `appointments.manage` (scopée), `self.profile` |
| `nurse` | `consultations.view`, `prescriptions.view`, `appointments.manage` (scopée), `self.profile` |
| `pharmacist` | `prescriptions.view`, `payments.view` (scopée), `self.profile` |
| `lab_tech` | `consultations.view` (scopée à ses examens), `self.profile` |
| `patient` | `self.profile`, `self.medical_record`, `self.carnet_vaccination`, `appointments.book.self` |

### 6.3 Scoping

- Permissions accordées via un rôle **global** (super_admin, compta, gouv, stat, moderator, patient) → s'appliquent partout sans scope.
- Mêmes slugs accordés via un rôle **scopé à une structure** (structure_owner, admin_staff, doctor, nurse, pharmacist, lab_tech) → `PermissionResolver` n'autorise que les actions dont l'objet est rattaché à cette structure.
- Exemple : Dr Ndong (`doctor` scopé au CHU Libreville) peut `consultations.manage` **seulement** sur les consultations dont `hosto_id = CHU_Libreville_id`. Le contrôleur appelle `$user->can('consultations.manage', $consultation->hosto)`.

### 6.4 Édition de la matrice (UI admin)

L'écran `/admin/roles/{slug}/permissions` propose une matrice **scope × actions** (cases à cocher groupées par scope). L'admin coche/décoche, sauvegarde — `RolePermissionsSeeder` sert juste de base initiale, l'admin peut tout modifier après.

Exception : `super_admin` est verrouillé en lecture seule avec message "Ce rôle a toutes les permissions et ne peut pas être modifié" — sécurité contre un moderator qui voudrait verrouiller le super_admin hors du système.

---

## 7. Sécurité

- Toutes les routes admin sont protégées par middleware `auth` + `env:admin` + `perm:<slug>`.
- Le banner d'impersonation est toujours visible quand la session est active (rendu dans le layout dashboard, impossible à dissimuler côté serveur).
- L'impersonation refuse les cibles `super_admin` (anti-escalation) et l'auto-impersonation.
- Reset password en mode impersonation est refusé (sinon l'admin pourrait changer le mdp de la cible, puis détourner le compte).
- Les mots de passe temporaires ne sont **jamais loggués** (audit log enregistre l'action sans le mdp).
- La clé privée RBAC (matrice) est versionnée via le seeder ; toute modification UI passe par `AuditLogger` pour traçabilité.
- Le hard delete est interdit côté service (`UserAdminService::softDelete` uniquement).
- Refus de supprimer le **dernier super_admin** (vérif `Role::where('slug','super_admin')->users()->count() > 1`).

---

## 8. Tests

### 8.1 Unit (`tests/Unit/Core/`)

- `PermissionResolverTest` (9 tests : court-circuit super_admin, héritage, multi-rôles, scoping, expiration, cache, slug inconnu).
- `RoleAssignmentServiceTest` (7 tests : assign idempotent, multi-scopes, revoke ciblé, sync, assigned_by, expiration).
- `UserAdminServiceTest` (12 tests : createUser, suspend revoke tokens, reactivate, resetPassword retourne plain text, soft/restore, validatePro, bulkAction).
- `ImpersonationServiceTest` (5 tests : start persists, refuse super_admin, refuse self, stop restaure admin, currentSession).
- `UserExportServiceTest` (3 tests : colonnes CSV, filtres respectés, streaming non in-memory).

### 8.2 Feature (`tests/Feature/Admin/`)

- `AdminUsersIndexTest` (8 tests : auth, RBAC, filtres q/role/env/status, pagination).
- `AdminUsersCrudTest` (13 tests : create, update diff, delete revoke tokens, restore, suspend, reactivate, reset_password, validatePro approve/reject, cannot suspend self, cannot delete last super_admin).
- `AdminRoleAssignmentTest` (5 tests : global role grants global, scoped role grants only scope, expired ignored, sync replaces).
- `AdminRolesCrudTest` (7 tests : list shows counts, create, edit, delete refuse system, delete refuse users attached, assign permissions matrix, super_admin refuse modification).
- `AdminPermissionsCatalogTest` (2 tests : lists seeded, groups by scope).
- `AdminImpersonationTest` (6 tests : impersonate patient, session persisted, banner visible, stop restore, refuse super_admin, refuse self).
- `AdminPractitionerCategoriesTest` (6 tests : list 15, create, update, delete refuse if practitioners attached, reorder, parent_category).
- `AdminUsersBulkTest` (3 tests : bulk_suspend, bulk_export streams CSV, bulk > 100 refuse 410).
- `AdminUsersExportTest` (2 tests : headers correct, count matches).
- `AdminUserSessionsTest` (2 tests : list active sanctum tokens, revoke deletes token).

### 8.3 Validation manuelle (acceptance) — 8 scénarios

1. **Login en super_admin** → toutes les sections admin accessibles, `*` implicite.
2. **Créer un compte `compta`** : créer un nouveau user, lui assigner le rôle `compta`. Login avec ce compte → seules les sections `payments`, `invoices`, `stats.financial` apparaissent dans le menu.
3. **Suspension** : suspendre un patient → le patient ne peut plus se connecter (message "Compte suspendu, contactez l'admin").
4. **Reset password** : reset pour un patient → admin reçoit le mdp temporaire, transmet au user → user se connecte, est forcé de changer son mdp avant tout le reste.
5. **Validation pro** : un médecin s'inscrit (`pro_validated_at = null`) → admin valide → médecin peut accéder à `/pro/*`.
6. **Rôle scopé** : assigner `doctor` à Dr Ndong sur le CHU Libreville → Dr Ndong voit/édite UNIQUEMENT les consultations du CHU, pas celles de la Clinique Saint-Joseph.
7. **Impersonation** : super_admin impersonifie Marie (patiente) → voit son dossier comme elle → banner top visible en permanence → clique "Arrêter" → revient en super_admin.
8. **Catégorie pro** : créer la catégorie "Cardiologue" avec parent "specialist", couleur rouge, icône `heart` → apparaît dans la liste de création d'un nouveau practitioner.

---

## 9. Ordre d'implémentation suggéré

À détailler dans le plan d'implémentation (skill `writing-plans`), mais l'ordre logique est :

1. **Migrations** : permissions, role_permissions, user_roles étendu, users.must_change_password, roles.is_system, impersonation_sessions, practitioner_categories, practitioners.practitioner_category_id (8 migrations).
2. **Models** : Permission, UserRoleAssignment (pivot), ImpersonationSession, PractitionerCategory ; étendre Role.
3. **Seeders** : PermissionsSeeder (41 permissions) + RolePermissionsSeeder (matrice par défaut) + nouveaux rôles compta/stat + PractitionerCategoriesSeeder (15 catégories).
4. **PermissionResolver + Gate registration** + tests unitaires.
5. **RoleAssignmentService** + tests unitaires.
6. **Middleware `perm:`** + tests.
7. **UserAdminService** + tests unitaires.
8. **AdminUsersController + vues** (index, show, CRUD, suspend, reset_password, validate_pro, sessions, bulk, export).
9. **AdminRolesController + vues** (CRUD + matrice permissions).
10. **AdminPermissionsController** (read-only catalogue).
11. **ImpersonationService + Controller + middleware + banner** + tests.
12. **AdminPractitionerCategoriesController + vues**.
13. **Validation manuelle** des 8 scénarios.
14. **Sidebar admin** : ajouter les nouvelles entrées de menu.
