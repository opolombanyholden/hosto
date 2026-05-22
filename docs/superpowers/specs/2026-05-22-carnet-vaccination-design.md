# Carnet de vaccination numérique HOSTO — Conception

**Date** : 2026-05-22
**Tâche** : T10 du backlog test HOSTO
**Module** : `app/Modules/EVax`
**Statut** : conception validée, en attente de plan d'implémentation

---

## 1. Objectif et portée

Construire un carnet de vaccination numérique :

- **rempli** uniquement par des professionnels de santé vérifiés ;
- **consultable** par le patient (et le parent pour ses enfants dépendants) ;
- **imprimable** en PDF A4 ;
- **authentifiable** par un QR-code hybride : URL de vérification en ligne + JWS signé pour vérification hors-ligne ;
- conforme aux standards visant une adoption OMS future (clés EC P-256, JWKS public, structure de payload alignée sur SVC).

Le module existant `app/Modules/EVax` a déjà la table `vaccination_records` et un controller stub ; tout le reste est à construire.

### Hors scope explicite

- Pas de notifications "prochaine dose" par email / SMS (rappel visuel uniquement via le calendrier PEV).
- Pas de version multilingue du PDF (FR seul en v1 ; EN ajoutable plus tard sans rupture).
- Pas de scanner caméra natif intégré côté pro (on délègue à l'OS via `<input type="file" capture>` ou app externe ; intégration complète plus tard).
- Pas de comptes enfants autonomes — les mineurs sont des `dependents` rattachés à un compte parent. La migration vers un compte enfant viendra avec T13 (Hosto Mwana).
- Pas de carte plastifiée recto/verso. Le PDF A4 suffit pour v1.

### Métriques de succès post-déploiement

- ≥ 95 % des QR générés se vérifient sans erreur (sample sur 100 carnets test).
- 0 fuite de PII dans les logs serveur (audit du middleware).
- PDF généré en < 1.5 s pour un carnet de 20 vaccinations.
- Aucun écart entre carnet live online et payload JWS hors-ligne au moment de l'émission.

---

## 2. Décisions structurantes (résumé du brainstorming)

| Question | Décision |
|---|---|
| Qui peut ajouter une vaccination ? | Uniquement professionnels vérifiés (`users.pro_validated_at IS NOT NULL`). |
| Modèle d'authentification du QR ? | Hybride : URL de vérification en ligne + JWS signé pour vérif hors-ligne. |
| Catalogue des vaccins ? | Référentiel PEV Afrique pré-seedé + fallback texte libre marqué `is_standardized = false`. |
| Identification du patient par le pro ? | Trois portes : (A) recherche NIP/nom, (B) scan QR identité patient, (C) bouton depuis consultation en cours. |
| Carnet pour enfants ? | Table `dependents` rattachée au parent ; v1 ne crée pas de compte autonome pour l'enfant. |

---

## 3. Modèle de données

### 3.1 Nouvelles tables

```text
vaccines  (référentiel pré-seedé via VaccinePevSeeder)
  ├ id, uuid
  ├ code               varchar(30) UNIQUE  -- 'BCG', 'OPV0', 'PENTA1', ...
  ├ oms_code           varchar(30) NULL    -- 'XM68M0', code ICD-11 OMS
  ├ name_fr            varchar(255)
  ├ name_en            varchar(255) NULL
  ├ manufacturer       varchar(255) NULL
  ├ diseases           jsonb               -- ['tuberculose']
  ├ schedule_age_days  integer NULL        -- 0 = naissance, 60 = 2 mois, ...
  ├ doses_total        smallint            -- 1 pour BCG, 3 pour Pentavalent
  ├ is_standardized    boolean DEFAULT true
  ├ display_order      smallint DEFAULT 0
  ├ is_active          boolean DEFAULT true
  └ created_at / updated_at

dependents
  ├ id, uuid
  ├ user_id            fk users  CASCADE   -- le parent
  ├ first_name         varchar(255)
  ├ last_name          varchar(255)
  ├ date_of_birth      date
  ├ gender             varchar(10) NULL
  ├ nip                varchar(30) NULL
  ├ notes              text NULL
  ├ carnet_qr_secret   varchar(32) UNIQUE  -- symétrique à users.carnet_qr_secret ; généré à la création du dépendant
  ├ created_at / updated_at / deleted_at
  └ index (user_id)
```

### 3.2 Tables modifiées

```text
vaccination_records  (existante, ajouts)
  ├ + dependent_id           fk dependents nullable CASCADE
  ├ + vaccine_id             fk vaccines nullable      -- null si saisie texte libre
  ├ + is_standardized        boolean DEFAULT true      -- false si nom libre
  ├ + signed_at              timestamptz NULL          -- horodatage par le pro
  ├ + signed_by_signature    text NULL                 -- empreinte numérique du pro
  ├ + carnet_revision        integer DEFAULT 1         -- incrémenté à chaque ajout
  └ CHECK (patient_id IS NOT NULL OR dependent_id IS NOT NULL)
     AND NOT (patient_id IS NOT NULL AND dependent_id IS NOT NULL)

users  (existante, ajout)
  └ + carnet_qr_secret       varchar(32) UNIQUE NULL   -- secret unique du carnet ; généré à la création du compte ;
                                                         sert à la fois à l'URL d'identité (/carnet/identity/<secret>) et à
                                                         l'URL de vérification (/c/v/<secret>) — c'est la même valeur, le
                                                         routage distingue l'usage
```

### 3.3 Invariants

- Une `vaccination_record` est rattachée à **un seul** propriétaire (`patient_id` XOR `dependent_id`), validé par contrainte CHECK.
- `vaccine_id` peut être null **seulement si** `is_standardized = false` ; les services le valident.
- `carnet_revision` est porté par le carnet logique (couple patient/dependent), incrémenté à chaque ajout ou modification → permet d'invalider visuellement un QR imprimé périmé.
- `carnet_qr_secret` est généré côté backend lors de la création du compte ; jamais exposé en clair dans les réponses API publiques (sert d'identifiant secret pour l'URL `/carnet/identity/<secret>`).

---

## 4. Composants

```text
app/Modules/EVax/
├── Models/
│   ├── Vaccine.php
│   ├── Dependent.php
│   └── VaccinationRecord.php             (existant, étendu)
│
├── Services/
│   ├── CarnetSignerService.php
│   │     sign(array $payload): string                  -- retourne JWS compact
│   │     verify(string $jws): ?array                   -- retourne payload ou null
│   │     activeKid(): string
│   │     jwks(): array                                 -- pour /.well-known
│   │
│   ├── CarnetQrService.php
│   │     identityQr(User $user): string                -- SVG, URL /carnet/identity/<secret>
│   │     verificationQr(User|Dependent $carnet): string -- SVG, URL + #jws=<token>
│   │     buildSignedPayload(User|Dependent $carnet): array
│   │
│   ├── CarnetPdfService.php
│   │     render(User|Dependent $carnet): string        -- bytes PDF
│   │     stream(User|Dependent $carnet): Response      -- HTTP response inline / attachment
│   │
│   └── VaccinationCatalogService.php
│         all(): Collection<Vaccine>
│         findByCode(string $code): ?Vaccine
│         pevSchedule(\DateTimeInterface $dob): array   -- [{vaccine, due_at, status: done|due|overdue}]
│
├── Http/Controllers/
│   ├── EVaxProController.php             (middleware: auth + pro.verified)
│   │     searchPatient(Request)          -- GET /pro/evax/search?q=...
│   │     resolvePatientFromQr(Request)   -- POST /pro/evax/resolve-qr
│   │     showAddForm(string $target)     -- GET /pro/evax/vaccinations/new?target=user-uuid|dep-uuid
│   │     storeVaccination(Request)       -- POST /pro/evax/vaccinations
│   │
│   ├── EVaxPatientController.php         (middleware: auth)
│   │     myCarnet()                      -- GET /compte/carnet-vaccination
│   │     dependentCarnet(string $uuid)   -- GET /compte/carnet-vaccination/dependent/{uuid}
│   │     downloadPdf(string $target)     -- GET /compte/carnet-vaccination/{target}/pdf
│   │     dependentsIndex()               -- GET /compte/carnet-vaccination/dependents
│   │     storeDependent(Request)         -- POST /compte/carnet-vaccination/dependents
│   │     updateDependent(...)            -- PUT
│   │     destroyDependent(...)           -- DELETE
│   │
│   └── EVaxPublicController.php          (sans auth)
│         verify(string $secret)          -- GET /c/v/{secret}
│         verifyJws(Request)              -- POST /c/verify-jws  (debug, optionnel)
│         servePatientIdentity($secret)   -- GET /carnet/identity/{secret}
│         jwks()                          -- GET /.well-known/hosto/carnet-keys.json
│
├── Console/Commands/
│   └── GenerateCarnetKeypair.php         -- php artisan evax:generate-keypair
│
├── Database/
│   ├── Migrations/
│   │   ├── ...create_vaccines_table.php
│   │   ├── ...create_dependents_table.php
│   │   ├── ...add_evax_fields_to_vaccination_records.php
│   │   └── ...add_carnet_qr_secret_to_users.php
│   └── Seeders/
│       └── VaccinePevSeeder.php
│
├── Routes/
│   ├── api.php       (existant, étendu)
│   └── web.php       (nouveau, branché depuis routes/web.php)
│
└── Providers/
    └── EVaxServiceProvider.php           (existant, étendu pour migrations + routes web)

resources/views/evax/
├── pro/
│   ├── search.blade.php
│   └── vaccination-form.blade.php
├── patient/
│   ├── carnet.blade.php
│   ├── dependents.blade.php
│   └── dependent-carnet.blade.php
├── public/
│   └── verify.blade.php
└── pdf/
    └── carnet.blade.php                  (rendu par dompdf)
```

### Dépendances ajoutées (composer.json)

- `dompdf/dompdf` : génération PDF pur PHP, pas de binaire système.
- `firebase/php-jwt` : signature et vérification JWS ES256.

---

## 5. Workflows

### 5.1 Pro ajoute une vaccination

```text
Pro authentifié + vérifié
  │
  ├─ (A) GET /pro/evax/search?q=MBAYE
  │      → liste { user_uuid, full_name, nip, date_of_birth, dependents[] }
  │      → pro clique sur un patient ou un dépendant
  │
  ├─ (B) POST /pro/evax/resolve-qr  { qr_url_or_secret }
  │      → backend extrait le secret, lookup user, retourne la fiche + dépendants
  │
  └─ (C) Sur /pro/consultations/{uuid}, bouton "Ajouter vaccination"
         → ouvre /pro/evax/vaccinations/new?target=user-<uuid>&consultation=<uuid>

GET /pro/evax/vaccinations/new?target=user-<uuid>
  └─ formulaire :
       - autocomplete vaccin sur `vaccines` (référentiel PEV)
       - toggle "Vaccin hors référentiel" → champ texte libre + is_standardized=false
       - dose_number auto-suggéré (dernière dose +1 par vaccin)
       - administered_at par défaut today
       - batch_number, next_dose_date (auto-calc selon schedule_age_days), notes

POST /pro/evax/vaccinations
  ├─ validation : patient_id XOR dependent_id, vaccine_id XOR vaccine_name
  ├─ création VaccinationRecord avec :
  │     signed_at = now(), signed_by_signature = hash(pro_user_id + record),
  │     carnet_revision = (max revision sur ce carnet) + 1
  ├─ audit log (action_create, entity=vaccination_record)
  ├─ invalidation cache PDF (`Cache::forget("carnet-pdf:{$target}")`)
  └─ retour 201 + payload de la fiche
```

### 5.2 Patient consulte / imprime son carnet

```text
GET /compte/carnet-vaccination
  └─ vue :
       - en-tête : photo, nom, NIP, date naissance, QR vérification (SVG)
       - onglets : Mon carnet | Mes dépendants
       - tableau chronologique des vaccinations
       - calendrier PEV (uniquement si date_of_birth renseignée) :
           roue à 3 couleurs : vert=fait, orange=à venir, rouge=manqué
       - boutons :
           [Télécharger PDF]  → GET /compte/carnet-vaccination/me/pdf
           [Gérer mes dépendants] → /compte/carnet-vaccination/dependents

GET /compte/carnet-vaccination/{target}/pdf
  ├─ target = 'me' ou 'dep-<uuid>'
  ├─ vérification d'autorisation : user.id correspond
  ├─ Cache::remember("carnet-pdf:{$target}:rev{$rev}", 1 day, fn() => render)
  └─ Content-Type: application/pdf + Content-Disposition: attachment
```

### 5.3 Vérification publique du carnet

```text
[Tiers scanne le QR avec son téléphone]

Cas online (URL ouverte dans navigateur) :
  GET /c/v/{secret}
    ├─ lookup du carnet via users.carnet_qr_secret OU dependents.carnet_qr_secret
    ├─ si secret invalide → 404
    ├─ si secret désactivé → 410 Gone
    ├─ vue HTML responsive :
    │    - "Carnet de Marie N. — authentifié par HOSTO"
    │    - sceau "Vérifié le 2026-05-22 14h33" (server time)
    │    - liste vaccinations dans l'ordre chronologique
    │    - lien "Télécharger ce carnet (PDF)" — sans authentification
    └─ audit log de la vérification (IP, user-agent, timestamp)

Cas offline (app vérificateur tierce) :
  1. App scanne le QR
  2. Extrait le fragment #jws=...
  3. Récupère la clé publique HOSTO depuis le cache local (mis à jour
     périodiquement via /.well-known/hosto/carnet-keys.json)
  4. Vérifie la signature ES256
  5. Lit le payload : sub, nip, rev, vacc[], iat, exp
  6. Affiche au vérificateur

GET /.well-known/hosto/carnet-keys.json
  └─ JWKS public : [{ kid, kty=EC, crv=P-256, x, y, alg=ES256 }]
       toutes les clés actives ET retirées (rotation)
```

### 5.4 Patient gère ses dépendants

```text
GET /compte/carnet-vaccination/dependents
  └─ liste, formulaire d'ajout, boutons edit / archive

POST /compte/carnet-vaccination/dependents
  └─ validation : first_name, last_name, date_of_birth requis, gender optionnel
  └─ création + génération qr_secret unique
  └─ retour redirect avec flash

PUT /compte/carnet-vaccination/dependents/{uuid}
DELETE /compte/carnet-vaccination/dependents/{uuid}  (soft delete)
```

### 5.5 Cas d'erreur

- Pro non vérifié tente d'ajouter une vaccination → 403 avec message "Compte pro en attente de validation".
- Patient inexistant ou supprimé lors de l'ajout → 404 JSON ou redirect avec flash.
- QR scanné corrompu ou falsifié (online) → vue "Carnet invalide ou révoqué".
- QR offline avec signature invalide → l'app vérificateur affiche "Signature invalide".
- PDF sans vaccinations → généré quand même avec mention "Aucune vaccination enregistrée — carnet créé le {date}".
- Patient consulte le carnet d'un autre user (`dependent` qui ne lui appartient pas) → 403.

---

## 6. QR-code : structure et cryptographie

### 6.1 Format du QR

Une seule URL avec fragment JWS (le fragment n'est jamais envoyé au serveur) :

```text
https://hosto.ga/c/v/9f8a2c1e7d#jws=eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9...
```

- Avant `#` : URL de vérification online (route `/c/v/{secret}`).
- Après `#jws=` : JWS compact ES256 vérifiable hors-ligne.

### 6.2 Payload JWS

```json
{
  "iss": "hosto.ga",
  "sub": "9f8a2c1e",
  "kind": "user",
  "nip": "GA-2024-19850715",
  "name_hash": "a7b3...",
  "rev": 7,
  "vacc": [
    {"c": "BCG",   "d": "2024-01-15", "n": 1, "std": true},
    {"c": "OPV0",  "d": "2024-01-15", "n": 1, "std": true},
    {"c": "PENTA", "d": "2024-03-10", "n": 1, "std": true},
    {"c": "Vaccin tradi X", "d": "2023-09-01", "n": 1, "std": false}
  ],
  "vacc_hash": "d4e1...",
  "iat": 1716387600,
  "exp": 2031931200
}
```

Header JWS : `{"typ":"JWT","alg":"ES256","kid":"hosto-prod-2026"}`.

- `sub` = 8 premiers caractères du uuid (assez pour distinguer, opaque).
- `kind` = `user` ou `dependent`.
- `nip` absent si non renseigné.
- `name_hash` = SHA-256(`first_name|last_name|date_of_birth`) — permet à un vérificateur de matcher avec un document d'identité sans dévoiler le nom au backend tiers.
- `vacc` limité aux **15 dernières doses** si > 15 (garantit < 2 KB → compatible scanners standards).
- `vacc_hash` = SHA-256 du tableau complet (même si tronqué) — permet à un vérificateur online de détecter qu'il y a plus que ce qu'il voit.
- `exp` = `iat + 10 ans` (re-générable).

### 6.3 Gestion des clés

```php
// config/hosto.php
'carnet' => [
    'kid' => env('CARNET_KEY_ID', 'hosto-dev-2026'),
    'private_key_path' => env('CARNET_PRIVATE_KEY_PATH', storage_path('keys/carnet-private.pem')),
    'public_keys' => [
        'hosto-dev-2026'   => env('CARNET_PUBLIC_KEY_DEV'),
        'hosto-prod-2026'  => env('CARNET_PUBLIC_KEY_PROD'),
    ],
],
```

- Clé privée EC P-256, fichier PEM hors Git (`storage/keys/carnet-private.pem` ajouté à `.gitignore`).
- Clés publiques en env vars (peuvent aussi être en fichier).
- Endpoint JWKS public `/.well-known/hosto/carnet-keys.json` retourne **toutes** les clés publiques (active + retirées) pour permettre la vérification des QR anciens.
- Commande `php artisan evax:generate-keypair` : génère une nouvelle paire EC P-256, affiche les commandes pour mettre à jour `.env`.

### 6.4 Rotation et révocation

- Rotation : nouvelle clé tous les 12-24 mois, ancien `kid` reste dans le JWKS indéfiniment (on ne casse pas les QR anciens).
- Pas de révocation cryptographique. Mais :
  - L'endpoint `/c/v/{secret}` peut afficher "Ce carnet a été corrigé — révision courante : X. Vous lisez la révision Y."
  - En cas de fraude, le `carnet_qr_secret` peut être désactivé (URL retourne 410, via un flag `carnet_qr_disabled_at` ajouté plus tard si besoin). Le fragment JWS reste valide cryptographiquement mais isolé sans contexte de récupération.

---

## 7. Sécurité

- Le `carnet_qr_secret` est un identifiant secret URL-safe (32 caractères base62, ~190 bits d'entropie) — non devinable. Stocké en clair en base car son rôle est juste de servir d'identifiant opaque pour l'URL de vérification.
- Toutes les routes pro sont protégées par `auth` + middleware `pro.verified` (à créer si absent, ou utiliser un check `pro_validated_at IS NOT NULL`).
- Les routes patient sont protégées par `auth` + vérification au niveau controller que l'utilisateur courant possède bien le carnet (ou est le parent du dépendant).
- La route publique `/c/v/{secret}` ne dévoile aucune information sensible au-delà du strict nécessaire :
  - nom complet, date de naissance, NIP (si présent), liste vaccinations
  - PAS l'email, téléphone, adresse, ni autres champs du profil
- Tous les évènements (création vaccination, vérification publique, génération PDF) sont loggés via `AuditLogger`.
- La clé privée n'est jamais loggée ; toute exception sur la signature loggue uniquement le `kid`.

---

## 8. Tests

### 8.1 Tests unitaires `tests/Unit/EVax/`

- `CarnetSignerServiceTest`
  - `sign_then_verify_round_trip_succeeds`
  - `verify_returns_null_on_tampered_payload`
  - `verify_returns_null_on_wrong_signature`
  - `verify_accepts_old_kid_after_rotation`
  - `payload_stays_under_size_limit_for_50_vaccinations`
- `CarnetQrServiceTest`
  - `identity_qr_contains_short_url_with_secret`
  - `verification_qr_contains_url_and_jws_fragment`
  - `qr_payload_is_under_2kb`
- `VaccinationCatalogServiceTest`
  - `pev_schedule_returns_expected_milestones_for_newborn`
  - `pev_schedule_marks_overdue_doses`
  - `find_by_code_is_case_insensitive`

### 8.2 Tests de fonctionnalité `tests/Feature/EVax/`

- `EVaxProTest`
  - `unverified_pro_cannot_add_vaccination_returns_403`
  - `verified_pro_can_add_vaccination_to_patient_by_nip`
  - `verified_pro_can_add_vaccination_to_dependent`
  - `pro_can_resolve_patient_via_identity_qr`
  - `adding_vaccination_increments_carnet_revision`
  - `adding_vaccination_writes_audit_log`
- `EVaxPatientTest`
  - `patient_can_view_their_own_carnet`
  - `patient_can_view_their_dependents_carnet`
  - `patient_cannot_view_another_users_carnet_returns_403`
  - `patient_can_create_a_dependent`
  - `patient_can_download_pdf_of_carnet`
  - `pdf_contains_qr_with_correct_url_and_jws`
- `EVaxPublicVerificationTest`
  - `verify_endpoint_renders_carnet_for_valid_secret`
  - `verify_endpoint_returns_410_for_revoked_secret`
  - `verify_endpoint_returns_404_for_unknown_secret`
  - `verify_endpoint_logs_the_verification_audit`
  - `jwks_endpoint_serves_active_public_keys`
- `DependentsTest`
  - `patient_can_crud_dependents`
  - `dependent_uuid_is_unique_and_routable`
  - `deleting_dependent_soft_deletes_associated_vaccinations`

### 8.3 Validation manuelle (acceptance)

1. PDF visuel : générer un carnet avec 5 vaccinations BCG/OPV/Penta/Rougeole/FJ → ouvrir → vérifier mise en page A4, lisibilité, présence du QR.
2. QR scan smartphone : scanner avec l'appli appareil photo iOS/Android → vérifier que l'URL ouvre la page de vérification.
3. JWS offline : utiliser https://jwt.io avec la clé publique → coller le `#jws=` du QR → valider la signature, lire les claims.
4. Workflow pro complet : pro vérifié, ajouter vaccination via les 3 portes (recherche, scan, consultation), confirmer reflet dans le carnet patient.
5. Dépendant : patient crée "Junior, 6 mois", pro ajoute BCG+OPV0, parent télécharge le PDF de Junior.

---

## 9. Ordre d'implémentation suggéré

À détailler dans le plan d'implémentation (étape suivante via skill `writing-plans`), mais l'ordre logique est :

1. Migrations + Models + Seeder du référentiel PEV.
2. `CarnetSignerService` + tests unitaires (validation crypto en premier).
3. `CarnetQrService` + tests unitaires.
4. `VaccinationCatalogService` + tests.
5. Routes + controllers pro (workflow A en premier, puis B et C).
6. Routes + controllers patient (consultation, dépendants).
7. `CarnetPdfService` + template Blade PDF.
8. Routes + controller public de vérification.
9. Endpoint JWKS + commande `evax:generate-keypair`.
10. Tests de fonctionnalité de bout en bout.
11. Validation manuelle des 5 scenarios acceptance.
