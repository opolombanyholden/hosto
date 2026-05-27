# T9.A — Module RDV : Formulaire de prise de rendez-vous complet

**Date** : 2026-05-27
**Tâche** : T9.A (sous-projet du backlog T9 — Module RDV complet)
**Module principal** : `app/Modules/RendezVous` (+ extensions `app/Modules/Core` pour les grants médicaux)
**Statut** : conception validée, en attente de plan d'implémentation

---

## 1. Objectif et portée

Étendre le formulaire de prise de RDV existant pour couvrir tous les cas métier réels :

- **5 types de RDV** : ordinaire, urgence, grossesse, natalité, suivi chronique.
- **3 types de consultation** : à l'hôpital, à domicile, téléconsultation — avec géolocalisation pour le mode domicile.
- **RDV pour un tiers** avec vérification automatique d'un compte HOSTO existant (lookup téléphone normalisé E.164), avec invitation SMS si absent.
- **Upload de documents** (ordonnances, examens) attachés au RDV : PDF + images, 5 fichiers max, 10 MB max chacun, 30 MB total.
- **Partage du dossier médical** avec le pratitioner via un toggle au RDV + validation par PIN médical du patient ; portée **persistante jusqu'à révocation explicite** par le patient ; audit log à chaque accès.

T9.A se concentre sur la **demande de RDV** (form + persistance). Les sous-projets suivants couvrent :
- **T9.B** : workflow de négociation de date (patient propose, pro accepte/refuse/contre-propose, patient valide).
- **T9.C** : file d'attente live + numéro de passage + report + insertion urgence.
- **T9.D** : conversion RDV → consultation + paiement.

### Hors-scope explicite (v1)

- Workflow de négociation de date (T9.B).
- File d'attente live et numéro de passage (T9.C).
- Conversion RDV → consultation et paiement (T9.D).
- Notifications SMS / email réelles au tiers et au patient (juste log + stub en v1).
- Live tracking GPS du pratitioner pendant le trajet à domicile (T9.D).
- Scope granulaire des grants médicaux (`scope` jsonb prêt en base mais UI simple en v1).
- Antivirus sur les uploads (interface `FileScanner` injectable avec implémentation `NoopScanner` en v1).
- Re-PIN périodique pour révocation automatique : décision = grant reste actif jusqu'à révocation explicite.
- Promotion des documents dans un DPE structuré complet : le flag `keep_in_dpe` existe mais la vue DPE riche côté pro arrivera avec T14.
- Validation métier spécifique aux types de RDV (genre=femme pour grossesse, lien Mwana pour natalité, lien chronique du dossier) — capturé en base, exploité plus tard.

### Métriques de succès post-déploiement

- 100 % des routes nouvelles passent par middleware `auth + phone.verified` (pour les patients) ou `perm:*` (côté admin / pro).
- Couverture > 85 % sur les 5 services nouveaux (`AppointmentBookingService`, `ThirdPartyResolverService`, `DocumentUploadService`, `GeocodingService`, `MedicalRecordGrantService`).
- 0 régression sur les tests existants après migration et accessor de compat `is_teleconsultation`.
- Time-to-book < 60 s pour un RDV ordinaire avec 3-4 champs renseignés.
- Géocodage Nominatim < 3 s en moyenne, avec timeout 10 s + retry job.
- Upload de 5 PDF (2 MB chacun) traité en < 5 s côté serveur.

---

## 2. Décisions structurantes (résumé du brainstorming)

| Question | Décision |
|---|---|
| Les 5 types de RDV ? | **(C) Hybride pragmatique** : type stocké, `urgence` driver de priorité dans T9.C ; les autres types restent étiquettes pour v1. |
| Les 3 types de consultation ? | **(C) Enum `consultation_mode`** + accessor PHP `getIsTeleconsultationAttribute` pour compat lecture. Drop le booléen DB. |
| Vérification du tiers ? | **(C)** Normalisation E.164 (libphonenumber) + lookup `users.phone_normalized` + invitation SMS si absent. |
| Partage du dossier médical ? | **(D)** v1 = toggle simple + PIN au RDV + portée persistante jusqu'à révocation explicite ; v2 = scope granulaire. Audit log à chaque accès. |
| Upload de documents ? | PDF + JPG/PNG/HEIC, 10 MB/fichier, 5 fichiers/RDV, 30 MB total, stockage local via Laravel Storage abstraction (disk `private_appointments`), download gated, promotion DPE si `keep_in_dpe`. |
| Géolocalisation visite domicile ? | Lat/lng/accuracy capturés côté patient (API navigateur ou pin draggable Leaflet) ; fallback géocodage Nominatim asynchrone. Lat/lng accessibles uniquement au pratitioner du RDV et au patient. Purge après 30 j post-RDV. |

---

## 3. Modèle de données

### 3.1 Tables modifiées

```text
appointments (existante — extensions)
  ├ + appointment_type        varchar(20) NOT NULL DEFAULT 'ordinaire'
  │                           enum {ordinaire, urgence, grossesse, natalite, chronique}
  ├ + consultation_mode       varchar(20) NOT NULL DEFAULT 'in_hospital'
  │                           enum {in_hospital, home, telecon}
  ├ + share_medical_record    boolean DEFAULT false
  ├ + third_party_user_id     fk users NULL ON DELETE SET NULL
  ├ + visit_address           text NULL
  ├ + visit_lat               decimal(10, 7) NULL
  ├ + visit_lng               decimal(10, 7) NULL
  ├ + visit_geocoded_at       timestampTz NULL
  ├ + visit_location_accuracy_m  unsignedSmallInteger NULL
  ├ + requested_at            timestampTz NULL   -- date proposée par le patient (préparation T9.B)
  └ DROP is_teleconsultation
       Remplacé par accessor PHP : getIsTeleconsultationAttribute() =>
         $this->consultation_mode === 'telecon'

users (existante — extension)
  └ + phone_normalized        varchar(20) NULL UNIQUE
       Format E.164 (+24106...). Backfill via migration sur tous les phones existants.
       Index unique pour lookup rapide tiers.
```

### 3.2 Nouvelles tables

```text
appointment_documents
  ├ id, uuid
  ├ appointment_id    fk appointments CASCADE
  ├ uploaded_by_id    fk users (patient ou pro)
  ├ original_name     varchar(255)
  ├ stored_path       varchar(500)
  │     Format : 'appointments/{appointment_uuid}/{sha256_hash}.{ext}'
  ├ mime_type         varchar(100)
  ├ size_bytes        unsignedInteger
  ├ category          varchar(40) NULL  -- 'ordonnance' | 'examen' | 'autre'
  ├ keep_in_dpe       boolean DEFAULT false
  ├ created_at, updated_at, deleted_at (softDeletes)
  └ index (appointment_id), index (uploaded_by_id)

medical_record_grants
  ├ id, uuid
  ├ patient_id              fk users CASCADE
  ├ practitioner_id         fk practitioners CASCADE
  ├ source_appointment_id   fk appointments NULL ON DELETE SET NULL
  ├ scope                   jsonb NULL  -- v1 = null (tout) ; v2 = granulaire
  ├ granted_at              timestampTz NOT NULL
  ├ revoked_at              timestampTz NULL
  ├ access_count            unsignedInteger DEFAULT 0
  ├ last_accessed_at        timestampTz NULL
  ├ created_at, updated_at
  └ partial unique index (patient_id, practitioner_id) WHERE revoked_at IS NULL

medical_record_access_logs
  ├ id, uuid
  ├ grant_id                  fk medical_record_grants CASCADE
  ├ practitioner_user_id      fk users  -- snapshot user (pas practitioner_id, car le pro
  │                                       peut changer mais l'accès est fait par un user
  │                                       connecté à un instant t)
  ├ accessed_at               timestampTz
  ├ ip_address                varchar(45)
  ├ user_agent                text
  ├ sections_accessed         jsonb NULL  -- ex : ['allergies','medications']
  └ index (grant_id, accessed_at)

invitation_links
  ├ id, uuid
  ├ inviter_user_id    fk users
  ├ context            varchar(40)  -- 'appointment_third_party'
  ├ context_id         bigint        -- appointment_id
  ├ phone_normalized   varchar(20)
  ├ token              varchar(64) UNIQUE  -- URL signée
  ├ sent_via           varchar(20)  -- 'sms' | 'whatsapp' | 'email'
  ├ sent_at            timestampTz NULL
  ├ accepted_at        timestampTz NULL
  ├ accepted_user_id   fk users NULL
  ├ expires_at         timestampTz
  ├ created_at, updated_at
  └ index (phone_normalized), index (token)
```

### 3.3 Invariants

- `appointment_type = 'urgence'` est consommé par T9.C (file prioritaire) — aucun effet en T9.A à part stockage.
- `consultation_mode = 'home'` ⇒ `visit_address` OU (`visit_lat` ET `visit_lng`) requis.
- `consultation_mode = 'home'` ⇒ `practitioner.does_home_care = true` requis.
- `consultation_mode = 'telecon'` ⇒ `practitioner.does_teleconsultation = true` requis.
- `is_for_third_party = true` ⇒ au moins `third_party_name` + `third_party_phone` requis ; `third_party_user_id` rempli automatiquement si lookup réussit.
- `share_medical_record = true` ⇒ PIN médical du patient vérifié avant le commit ; création d'un `MedicalRecordGrant` (ou réactivation s'il était révoqué).
- Unicité `medical_record_grants(patient_id, practitioner_id) WHERE revoked_at IS NULL` empêche les doublons actifs.
- `appointment_documents` : 10 MB/fichier, 5 fichiers/appointment, 30 MB total — vérifié au store.
- Stockage local sous `storage/app/private/appointments/{uuid}/`, jamais dans `public/` ; téléchargement uniquement via contrôleur gated.
- `visit_lat/lng` purgés après 30 j post-RDV via job cron (rétention courte PII).

---

## 4. Composants

```text
app/Modules/RendezVous/
├── Models/
│   ├── Appointment.php                  (étendu : enums casts, accessors)
│   ├── TimeSlot.php                     (inchangé en T9.A)
│   └── AppointmentDocument.php          (NOUVEAU)
│
├── Services/
│   ├── AppointmentBookingService.php
│   │     book(BookingData $data): Appointment
│   │     cancel(Appointment, ?string $reason, User $by): void
│   │
│   ├── ThirdPartyResolverService.php
│   │     normalizePhone(string $raw, string $countryIso = 'GA'): ?string
│   │     findUserByPhone(string $normalized): ?User
│   │     sendInvitation(User $inviter, string $phone, Appointment $apt): InvitationLink
│   │
│   ├── DocumentUploadService.php
│   │     store(Appointment, UploadedFile, User $uploader, ?string $category): AppointmentDocument
│   │     download(AppointmentDocument, User $accessor): StreamedResponse
│   │     delete(AppointmentDocument, User $by): void
│   │     promoteToDpe(AppointmentDocument): void
│   │     canAccess(AppointmentDocument, User): bool
│   │
│   └── GeocodingService.php
│         geocode(string $address): ?array{lat,lng,accuracy_m}
│         reverseGeocode(float $lat, float $lng): ?string
│         dispatchGeocodeJob(Appointment $apt): void
│
├── Jobs/
│   ├── GeocodeAppointmentAddressJob.php
│   └── PurgeOldVisitLocationsJob.php      (cron 30 j)
│
├── Http/
│   ├── Controllers/
│   │   ├── AppointmentsController.php       (existant — étendu)
│   │   ├── BookingWebController.php         (existant — méthodes étendues)
│   │   ├── AppointmentDocumentsController.php  (NOUVEAU)
│   │   └── ThirdPartyLookupController.php   (NOUVEAU)
│   │
│   ├── Requests/
│   │   └── BookAppointmentRequest.php       (NOUVEAU — FormRequest avec validation complète)
│   │
│   └── Resources/
│       └── AppointmentResource.php          (étendu)
│
├── Database/
│   ├── Migrations/  (3 nouvelles)
│   │   ├── 2026_05_27_100000_extend_appointments_for_booking_v9a.php
│   │   ├── 2026_05_27_100100_create_appointment_documents_table.php
│   │   └── 2026_05_27_100200_add_phone_normalized_to_users.php
│
└── Routes/
    └── api.php                              (étendu)

app/Modules/Core/
├── Models/
│   ├── MedicalRecordGrant.php               (NOUVEAU)
│   ├── MedicalRecordAccessLog.php           (NOUVEAU)
│   └── InvitationLink.php                   (NOUVEAU)
│
├── Services/
│   └── MedicalRecordGrantService.php
│         grant(Patient, Practitioner, ?array $scope, ?Appointment $source): MedicalRecordGrant
│         revoke(MedicalRecordGrant, User $by): void
│         hasActiveGrant(Patient, Practitioner): bool
│         logAccess(MedicalRecordGrant, User $practitionerUser, array $sections, Request): MedicalRecordAccessLog
│
├── Http/Controllers/
│   └── MedicalRecordGrantsController.php
│         index() : liste des grants actifs du patient
│         show($uuid) : détails du grant + historique des accès
│         revoke($uuid) : POST de révocation
│
└── Database/Migrations/  (3 nouvelles)
    ├── 2026_05_27_110000_create_medical_record_grants_table.php
    ├── 2026_05_27_110100_create_medical_record_access_logs_table.php
    └── 2026_05_27_110200_create_invitation_links_table.php

config/filesystems.php  (étendu)
  ├ disk 'private_appointments' (driver local, racine storage/app/private/appointments)

resources/views/
├── compte/
│   ├── rendez-vous-form.blade.php           (NOUVEAU — formulaire complet)
│   ├── dossier/
│   │   ├── partages.blade.php               (NOUVEAU — gestion des grants)
│   │   └── partage-historique.blade.php     (NOUVEAU — historique d'accès d'un grant)
│   └── rendez-vous.blade.php                (existant — étendu pour nouveaux champs)
│
└── annuaire/
    └── book-rdv.blade.php                   (existant — refonte pour utiliser le nouveau form)

composer.json
  └ ajout : "giggsey/libphonenumber-for-php": "^9.0"
```

### Principes architecturaux

- **AppointmentBookingService est l'unique point d'entrée** pour créer un RDV (web UI ou API). Orchestrer les sous-services (tiers, géocodage, documents, grants).
- **ThirdPartyResolverService isolé** : la normalisation E.164 + invitation SMS sont des préoccupations distinctes, testables sans toucher au reste.
- **DocumentUploadService** : un service par responsabilité technique. Pas de logique métier RDV ici (qui peut télécharger quel doc reste une décision métier dans le service, mais via une méthode `canAccess` documentée).
- **MedicalRecordGrantService vit dans Core** (pas RendezVous) car les grants sont réutilisables par d'autres modules (Lab, Pharma, futurs).
- **GeocodingService abstrait Nominatim** ; on peut basculer Mapbox/Google plus tard sans toucher au reste.
- **Job queue** pour géocodage : ne pas bloquer la prise de RDV si Nominatim est lent.
- **Storage disk `private_appointments`** : jamais exposé en HTTP direct ; téléchargement via contrôleur uniquement.

---

## 5. Workflows

### 5.1 Patient prend un RDV pour lui-même

```text
Patient sur /annuaire/{slug}/rendez-vous  (gated par is_partner — T3)
  → vue rendez-vous-form.blade.php

Formulaire :
  1. Spécialité (select)
  2. Type de RDV (radio : ordinaire | urgence | grossesse | natalite | chronique)
  3. Type de consultation (radio dynamique : in_hospital | home | telecon)
     filtré selon practitioner.does_home_care / does_teleconsultation
  4. Si home : visit_address texte + bouton "Utiliser ma position" (geolocation API)
                + mini-carte Leaflet avec pin draggable
  5. Motif (texte facultatif)
  6. Pour qui : moi | tiers
  7. Documents (drag&drop, max 5 fichiers, 10 MB chacun)
  8. Checkbox "Partager mon dossier médical avec ce médecin"
     → si cochée, champ PIN apparaît (4-6 chiffres)
  9. Date proposée (calendrier)

POST /web/rdv/book
  ├ BookAppointmentRequest valide tout (XOR mode/practitioner, files, PIN, etc.)
  ├ AppointmentBookingService.book(BookingData)
  │   ├ validateConsultationMode() — refuse si mode incompatible practitioner
  │   ├ if home et pas de lat/lng → dispatch GeocodeAppointmentAddressJob async
  │   ├ if is_for_third_party → ThirdPartyResolverService.findUserByPhone(normalized)
  │   │     match → third_party_user_id = match.id
  │   │     no match → invitation_links créé + ThirdPartyResolverService.sendInvitation()
  │   ├ create Appointment (status='pending', appointment_type, consultation_mode, etc.)
  │   ├ if files → DocumentUploadService.store() pour chacun
  │   ├ if share_medical_record et PIN OK :
  │   │     MedicalRecordGrantService.grant(patient, practitioner, scope=null, $apt)
  │   │     PIN vérifié via Hash::check sur User.medical_pin
  │   │     refus 422 si PIN faux ou non défini
  │   ├ AuditLogger.record(ACTION_CREATE, 'appointment', $apt->uuid, [...])
  │   └ retour Appointment
  └ flash "RDV demandé. Le médecin va vous proposer un horaire."
    (en attendant T9.B : pas encore de négociation, juste demande envoyée)

NB : la date proposée est stockée dans appointments.requested_at (champ
ajouté en T9.A pour préparer T9.B). Le statut reste 'pending'.
```

### 5.2 Patient prend un RDV pour un tiers

```text
Patient coche "Pour un tiers" → sous-form :
  Nom complet*  | Téléphone*  | Âge*  | Sexe  | Lien | Ville/adresse | Notes

JS au blur du téléphone :
  GET /api/v1/rdv/third-party/lookup?phone={raw}
    ├ ThirdPartyResolverService.normalizePhone({raw}, 'GA')
    ├ findUserByPhone({normalized})
    └ retourne { matched: true, full_name: 'M. Diop' } | { matched: false }

UI affiche selon réponse :
  ✓ "M. Diop a un compte HOSTO. Le RDV sera lié à son dossier."
  ✗ "Aucun compte trouvé. Un SMS d'invitation lui sera envoyé."

POST /web/rdv/book (mêmes champs + tiers)
  → flux identique au 5.1 mais avec is_for_third_party=true
  → si pas matché : invitation_links créé + SMS envoyé (stub log en v1)
```

### 5.3 Patient gère ses partages de dossier médical

```text
GET /compte/dossier/partages
  ├ middleware : auth + phone.verified
  ├ liste MedicalRecordGrant WHERE patient_id=user.id ORDER BY granted_at DESC
  └ vue partages.blade.php

Affichage par grant :
  ☑ Dr Marie NDONG (CHU Libreville) — partagé depuis 12/03/2026
    Dernier accès : 15/05/2026 à 10:23
    Total : 12 accès
    [ Voir l'historique ]  [ Révoquer ]

GET /compte/dossier/partages/{uuid}/historique
  → liste MedicalRecordAccessLog WHERE grant_id=$uuid ORDER BY accessed_at DESC
  → vue partage-historique.blade.php (table : date, IP, sections accédées)

POST /compte/dossier/partages/{uuid}/revoke
  ├ MedicalRecordGrantService.revoke($grant, $user)
  │   ├ grant.revoked_at = now()
  │   └ AuditLogger.record(ACTION_REVOKE, 'medical_record_grant', $uuid, [...])
  └ redirect with flash success
```

### 5.4 Upload + download documents

```text
POST /web/rdv/{appointment_uuid}/documents
  ├ auth + owner check (patient ou pratitioner du RDV)
  ├ FormRequest : files[] (mime jpg/png/heic/pdf, max 10 MB)
  ├ DocumentUploadService.store(apt, file, $user, $category)
  │   ├ validate count + total size + per-file size + mime (signature binaire)
  │   ├ hash filename : sha256(uuid + original + microtime)
  │   ├ Storage::disk('private_appointments')->putFileAs("{uuid}", $file, $hashedName)
  │   └ create AppointmentDocument
  └ flash succès

GET /web/rdv/documents/{document_uuid}/download
  ├ auth + canAccess check
  │   patient propriétaire OU pratitioner du RDV OU admin avec users.view
  ├ DocumentUploadService.download($doc, $user)
  └ StreamedResponse avec Content-Disposition: attachment; filename="{original_name}"

DELETE /web/rdv/documents/{document_uuid}
  ├ owner-only (uploader)
  └ soft delete (le fichier physique est purgé par job cron 30 j plus tard)
```

### 5.5 Pro accède au dossier médical d'un patient (préparation — implémentation côté Pro module hors T9.A)

```text
GET /pro/patients/{user_uuid}/dossier
  ├ permission : consultations.manage (ou self.medical_record pour le patient lui-même)
  ├ check MedicalRecordGrantService.hasActiveGrant($patient, $pro.practitioner)
  │   false → vue "Demander le partage" (notification au patient)
  │   true → continue
  ├ MedicalRecordGrantService.logAccess($grant, $proUser, $sections, $request)
  └ vue dossier avec les sections autorisées

Le LOG d'accès est crucial : le patient peut voir qui a accédé, quand, depuis où.
```

### 5.6 Cas d'erreur

- `consultation_mode='telecon'` mais `practitioner.does_teleconsultation=false` → 422.
- `consultation_mode='home'` mais `practitioner.does_home_care=false` → 422.
- `consultation_mode='home'` sans adresse ni lat/lng → 422.
- Upload > 10 MB / fichier → 422.
- Plus de 5 fichiers déjà attachés → 422.
- Total > 30 MB → 422.
- Mime non autorisé → 422.
- `share_medical_record=true` mais PIN faux ou non défini → 422.
- Tiers téléphone invalide → 422.
- Géocodage Nominatim échoue → RDV persisté quand même, job se retentera ; pro voit "adresse géocodée en cours…".
- Tentative d'accès à un document par un non-autorisé → 403.

---

## 6. Sécurité et vie privée

### 6.1 Upload de documents

- Mime vérifié en double : `mimes:` (Laravel) + `mimetypes:` (lit signature binaire).
- Filename hashé à l'écriture (`sha256(uuid + original + microtime)`) → anti-énumération.
- Stockage local privé, jamais exposé en HTTP direct. Téléchargement via contrôleur gated uniquement.
- Antivirus stub `NoopScanner` en v1, interface `FileScanner` injectable pour brancher ClamAV plus tard.
- `.htaccess` ou config Nginx `deny from all` sur le dossier privé.

### 6.2 Téléchargement de documents

- Gated par `DocumentUploadService.canAccess($doc, $user)` :
  - Patient propriétaire du RDV (`patient_id` ou `third_party_user_id`).
  - Pratitioner du RDV (`appointment.practitioner.user_id === $user->id`).
  - Admin avec permission `appointments.manage` ou `users.view`.
- URL non devinable : utilise `uuid` du document.
- Log d'accès dans `audit_logs`.
- `Content-Disposition: attachment` — force le téléchargement, jamais le rendu inline.

### 6.3 Partage du dossier médical

- Création du grant requiert le PIN médical du patient (`Hash::check`).
- 5 essais PIN faux sur 15 min → blocage 1 h (à brancher sur le mécanisme `failed_login_attempts` de Core, extension PIN si pas déjà en place).
- Unicité du grant `(patient_id, practitioner_id) WHERE revoked_at IS NULL` empêche les doublons.
- Révocation immédiate (pas de grace period).
- Log d'accès systématique : à chaque consultation du DPE par le pro, `MedicalRecordAccessLog` créé avec IP, UA, sections.

### 6.4 Lookup tiers

- Retourne uniquement `{ matched: bool, full_name?: string }`. Jamais email ou autres données (anti-énumération).
- Rate-limit : 30 requêtes/heure/user.
- Invitation SMS : token signé limité dans le temps (24 h).

### 6.5 Géolocalisation

- `visit_lat/lng` accessible **uniquement** au pratitioner du RDV et au patient.
- Purgé après 30 j post-RDV via `PurgeOldVisitLocationsJob` (cron).
- Géolocalisation client-side : autorisation explicite via API navigateur, refusable sans casser la prise de RDV (fallback adresse texte).
- Nominatim (reverse-geocoding) : on n'envoie jamais lat/lng + nom patient ensemble.

### 6.6 CSRF + headers

- Toutes les routes POST/PUT/DELETE protégées par middleware `csrf` Laravel.
- `Content-Security-Policy` strict (déjà en place sur d'autres modules).
- `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`.

### 6.7 Logs sensibles

- Aucun PIN médical loggé en clair.
- Le téléphone normalisé peut être loggé pour debug, mais pas l'adresse complète.
- Les `MedicalRecordAccessLog` conservés indéfiniment (audit santé).

### 6.8 Rate-limiting

- POST `/web/rdv/book` : 10 RDV/heure/user.
- POST `/web/rdv/{uuid}/documents` : 20 uploads/heure/user.
- GET `/web/rdv/documents/{uuid}/download` : 100 téléchargements/heure/user.
- GET `/api/v1/rdv/third-party/lookup` : 30 requêtes/heure/user.

---

## 7. Tests

### 7.1 Unitaires (`tests/Unit/RendezVous/` et `tests/Unit/Core/`)

- `AppointmentBookingServiceTest` (10 tests)
- `ThirdPartyResolverServiceTest` (6 tests)
- `DocumentUploadServiceTest` (11 tests)
- `GeocodingServiceTest` (4 tests, mock Nominatim HTTP)
- `MedicalRecordGrantServiceTest` (6 tests)
- `AppointmentExtendedModelTest` (4 tests)

### 7.2 Fonctionnels (`tests/Feature/RendezVous/` et `tests/Feature/Compte/`)

- `BookingFlowTest` (12 tests : RDV ordinaire, telecon, home, third-party matched/unmatched, documents, partage avec PIN)
- `ThirdPartyLookupApiTest` (4 tests)
- `AppointmentDocumentsControllerTest` (6 tests)
- `MedicalRecordGrantsControllerTest` (4 tests)
- `PhoneNormalizationBackfillTest` (2 tests)
- `GeocodingJobTest` (2 tests)

### 7.3 Validation manuelle (acceptance) — 7 scénarios

1. RDV ordinaire à l'hôpital : patient prend un RDV simple → apparaît dans `/compte/rendez-vous` avec statut "En attente du médecin".
2. RDV téléconsultation : sur la fiche d'un médecin avec `does_teleconsultation=true`, option apparaît. Sans ce flag, option désactivée avec tooltip.
3. RDV à domicile avec géolocation : mode "domicile" → mini-carte Leaflet → clic "Utiliser ma position" → permission navigateur → pin se positionne → adresse auto-remplie via reverse-geocoding.
4. RDV à domicile avec adresse manuelle : saisit "BP 1234, Quartier Glass, Libreville" → géocodage async → 5 s plus tard, lat/lng populés.
5. RDV pour tiers matché : saisit téléphone du père patient HOSTO → message "Ahmed BA a un compte HOSTO" → soumet → notification dans son espace.
6. RDV pour tiers non matché : saisit téléphone cousin sans compte → message "SMS d'invitation envoyé" → soumet → `invitation_links` créé + SMS stub loggé.
7. Partage dossier médical : coche toggle → champ PIN apparaît → saisit PIN → soumet → grant créé. Va dans `/compte/dossier/partages` → voit grant. Clique "Révoquer" → marqué revoked_at. Tente d'accéder en pro → "Accès révoqué".

---

## 8. Ordre d'implémentation suggéré

À détailler dans le plan d'implémentation (skill `writing-plans`), l'ordre logique :

1. **Migrations** : extend `appointments`, create `appointment_documents`, add `phone_normalized` à `users`, create `medical_record_grants`, create `medical_record_access_logs`, create `invitation_links` (6 migrations).
2. **Models** : `AppointmentDocument`, `MedicalRecordGrant`, `MedicalRecordAccessLog`, `InvitationLink` ; étendre `Appointment` (enums + accessors).
3. **Config** : ajouter disk `private_appointments` dans `config/filesystems.php`.
4. **Dépendances composer** : `giggsey/libphonenumber-for-php`.
5. **`ThirdPartyResolverService`** + tests unitaires (normalisation phone, lookup, invitation).
6. **`GeocodingService`** + `GeocodeAppointmentAddressJob` + tests (mock Nominatim).
7. **`DocumentUploadService`** + tests unitaires (store, download, canAccess, promote).
8. **`MedicalRecordGrantService`** + tests unitaires (grant, revoke, hasActiveGrant, logAccess).
9. **`AppointmentBookingService`** + tests unitaires (orchestration, validation modes, PIN, third-party, documents, grants).
10. **`BookAppointmentRequest`** (FormRequest).
11. **`AppointmentDocumentsController`** + routes + tests fonctionnels.
12. **`ThirdPartyLookupController`** + route + tests fonctionnels.
13. **`MedicalRecordGrantsController`** + routes + vues `partages.blade.php` + `partage-historique.blade.php` + tests fonctionnels.
14. **Refonte de `BookingWebController`** ou extension pour utiliser `AppointmentBookingService` + nouvelle vue `rendez-vous-form.blade.php`.
15. **Cron `PurgeOldVisitLocationsJob`** (30 j post-RDV).
16. **Validation manuelle** des 7 scénarios acceptance.
