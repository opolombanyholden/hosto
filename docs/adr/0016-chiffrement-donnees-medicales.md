# ADR 0016 — Chiffrement des données médicales au repos

**Statut** : Accepté
**Date** : 2026-11-07

## Contexte

Les données cliniques (motifs, diagnostics, ordonnances, résultats d'analyse, antécédents) étaient stockées en clair dans PostgreSQL, et les documents joints aux rendez-vous en clair sur le disque. Une copie de la base, une sauvegarde égarée ou un accès en lecture au serveur exposait donc directement le dossier des patients.

Le chiffrement du disque (LUKS, volume chiffré du fournisseur) reste souhaitable mais ne protège pas contre ces cas : une sauvegarde ou un dump est lisible dès qu'il quitte le disque.

## Décision

Chiffrement applicatif, champ par champ, en **AES-256-GCM** (chiffrement authentifié : une valeur modifiée est rejetée, pas mal lue).

- **Clé dédiée** `MEDICAL_ENCRYPTION_KEY`, distincte d'`APP_KEY`. Une rotation d'`APP_KEY` (sessions, cookies) ne rend pas les données médicales illisibles, et une fuite d'`APP_KEY` ne les expose pas.
- **Format stocké** : `hme1:<id de clé>:<charge utile>`. Le préfixe distingue les valeurs chiffrées des lignes pas encore migrées. L'identifiant de clé (8 caractères dérivés de la clé) permet la rotation.
- **Échec franc** : sans clé valide, toute écriture d'un champ médical lève `MedicalEncryptionKeyMissing`. Aucune donnée n'est jamais écrite en clair par repli.
- **Implémentation** : cast Eloquent `EncryptedMedical` (et `EncryptedMedical::class.':json'`), service `MedicalCipher` enregistré en singleton dans `CoreServiceProvider`.
- **Documents** : le contenu des fichiers joints aux rendez-vous est chiffré avec la même clé avant écriture sur le disque, et déchiffré au téléchargement.

### Champs chiffrés

La liste de référence est `App\Modules\Core\Support\MedicalFields::COLUMNS`. Elle doit rester alignée sur les casts des modèles.

| Table | Colonnes |
|---|---|
| consultations | motif, anamnesis, examen_clinique, diagnostic, diagnostic_code, conduite_a_tenir, notes_internes, vitals |
| prescriptions, prescription_items | notes ; medication_name, dosage, posology, duration, instructions |
| exam_requests | exam_type, clinical_info, results |
| treatments, care_acts | description, instructions, frequency, duration ; description, instructions, notes |
| lab_results, lab_result_items | conclusion, notes ; test_name, value, reference_range, comment |
| dispensations, dispensation_items | notes ; medication_name, dosage, substitution_reason |
| appointments | reason, notes, third_party_notes, cancellation_reason |
| users | allergies, chronic_conditions, current_medications, surgical_history, family_history, disabilities |

La migration `2026_11_07_100000_widen_medical_columns_for_encryption` passe en `text` les colonnes de longueur limitée ou `jsonb` concernées. Elle n'est pas réversible : une valeur chiffrée ne tient plus dans l'ancien type.

## Conséquences

- **Plus de recherche ni de tri SQL** sur ces colonnes. La recherche du patient dans son propre dossier (`/compte/api/rendez-vous`, `/compte/api/consultations`) filtre désormais en PHP, ce qui reste raisonnable au volume d'un patient. Toute future recherche transverse (statistiques par diagnostic, par exemple) devra déchiffrer en traitement de fond, ou s'appuyer sur des agrégats anonymisés.
- **Perte de clé = perte de données.** La clé doit être sauvegardée hors du serveur et hors des sauvegardes de la base (coffre de secrets, ou deux personnes de confiance).
- **Instances locales** (ADR 0014) : chaque instance doit recevoir la même clé que le cloud, transmise hors du canal de synchronisation.
- La migration des données existantes réécrit les lignes : le déclencheur `updated_at` met à jour leur date de modification.

## Mise en œuvre

```bash
php artisan hosto:medical-key --write      # une seule fois par environnement, puis sauvegarder la clé
php artisan migrate --force
php artisan hosto:medical-encrypt --dry-run
php artisan hosto:medical-encrypt          # idempotent
```

### Rotation de clé

1. Placer l'ancienne clé dans `MEDICAL_ENCRYPTION_PREVIOUS_KEYS` (séparées par des virgules) et la nouvelle dans `MEDICAL_ENCRYPTION_KEY`.
2. Lancer `php artisan hosto:medical-encrypt` : les valeurs sont rechiffrées avec la nouvelle clé.
3. Retirer l'ancienne clé une fois la commande terminée sans erreur, en gardant une sauvegarde le temps de vérifier les sauvegardes de base.

## Hors périmètre, à traiter ensuite

- Carnet vaccinal (`vaccination_records`), personnes à charge, et instantanés d'identité des rendez-vous (`patient_*_snapshot`) : données personnelles plutôt que cliniques, et certaines sont lues par la vérification publique du carnet.
- `user_documents`, modules en préparation (`pregnancies`, `prenatal_visits`, `vital_declarations`, `chatbot_messages`, `ocr_results`) : à chiffrer quand ils seront livrés.
- Journaux applicatifs et journal d'audit : ils ne doivent pas contenir de contenu clinique. À vérifier lors de l'audit de sécurité.

## Alternatives écartées

- **`encrypted` natif de Laravel** : utilise `APP_KEY`, donc couple la sécurité des données médicales à celle des sessions et rend leur rotation risquée.
- **Chiffrement PostgreSQL (`pgcrypto`)** : la clé transiterait dans les requêtes SQL et dans les journaux de requêtes lentes.
- **Chiffrement disque seul** : ne protège ni les dumps ni les sauvegardes.
