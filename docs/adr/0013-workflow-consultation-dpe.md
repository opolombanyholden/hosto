# ADR 0013 — Workflow complet de consultation et DPE

**Statut** : Accepté
**Date** : 2026-04-21

## Contexte

Le module Pro (Phase 5) doit modéliser le parcours médical complet d'une consultation, pas uniquement la saisie d'un diagnostic et d'une ordonnance. Chaque étape du workflow est optionnelle selon le cas clinique du patient.

## Décision

### Workflow de consultation complet

```
CONSULTATION (acte médical)
│
├── 1. MOTIF + ANAMNÈSE
│       Pourquoi le patient consulte-t-il ?
│       Historique médical pertinent
│
├── 2. EXAMEN CLINIQUE
│       Observation, palpation, auscultation
│       Signes vitaux (poids, taille, tension, T°, pouls, SpO2)
│
├── 3. DIAGNOSTIC
│       Conclusion médicale + code CIM-10
│       Peut être :
│         - Définitif → conduite à tenir directe
│         - Provisoire → nécessite des examens complémentaires
│
├── 4. EXAMENS (optionnel)
│       Prescrits SI le diagnostic nécessite confirmation
│       Types : bilan sanguin, échographie, radio, scanner, IRM, etc.
│       Le patient effectue les examens au labo/imagerie
│       Les résultats reviennent au médecin
│       → Le diagnostic peut être affiné après résultats
│
├── 5. SOINS (optionnel)
│       Prescrits SI le patient nécessite des actes de soins
│       Types : injection, perfusion, pansement, suture, kiné, etc.
│       Réalisés par le médecin, un infirmier ou un kiné
│       Peuvent être ponctuels ou sur plusieurs séances
│
├── 6. TRAITEMENTS (optionnel)
│       Plan thérapeutique à suivre par le patient
│       Peut inclure :
│         - Traitement médicamenteux (sans ordonnance formelle)
│         - Régime alimentaire
│         - Repos, arrêt de travail
│         - Rééducation
│         - Suivi périodique
│       Différent de l'ordonnance : le traitement est le "quoi faire",
│       l'ordonnance est le "quoi prendre" (médicaments)
│
└── 7. ORDONNANCE (optionnel, dernière étape)
        Prescription médicamenteuse formelle
        Référencée, signée, avec validité
        Envoyable à une pharmacie
        N'est créée que si des médicaments sont prescrits
```

### Cas d'usage typiques

| Cas | Étapes réalisées |
|---|---|
| Consultation simple (grippe) | 1 → 2 → 3 → 7 (ordonnance) |
| Bilan de santé | 1 → 2 → 4 (examens) → 3 (diagnostic après résultats) |
| Accident (suture) | 1 → 2 → 3 → 5 (soins) |
| Maladie chronique (diabète) | 1 → 2 → 3 → 4 → 6 (traitement long) → 7 |
| Suivi post-opératoire | 1 → 2 → 5 (soins) → 6 (traitement) |
| Patient sain (certificat médical) | 1 → 2 → 3 (RAS) |

### Règle fondamentale

> **Toutes les étapes après l'examen clinique (3-7) sont optionnelles.**
> Le médecin choisit ce qui est pertinent pour chaque patient.
> L'ordonnance n'est PAS automatique — elle n'intervient que si des
> médicaments sont nécessaires.

### Impact sur le schéma existant

La table `consultations` reste le conteneur principal. On ajoute une table `treatments` pour les traitements :

**Table `treatments` (nouvelle)**

| Colonne | Type | Rôle |
|---|---|---|
| uuid | uuid | Identifiant public |
| consultation_id | FK consultations | |
| practitioner_id | FK practitioners | |
| patient_id | FK users | |
| type | varchar(30) | medication, diet, rest, rehabilitation, follow_up, other |
| description | text | Description du traitement |
| instructions | text NULL | Instructions détaillées |
| frequency | varchar(100) NULL | ex: "3 fois par jour", "1 séance/semaine" |
| duration | varchar(100) NULL | ex: "7 jours", "3 mois", "à vie" |
| start_date | date NULL | |
| end_date | date NULL | |
| status | varchar(20) | prescribed, in_progress, completed, cancelled |

**Table `care_acts` (soins — nouvelle)**

| Colonne | Type | Rôle |
|---|---|---|
| uuid | uuid | |
| consultation_id | FK consultations | |
| practitioner_id | FK practitioners | Qui prescrit |
| performed_by_id | FK practitioners NULL | Qui réalise (infirmier, kiné...) |
| patient_id | FK users | |
| care_type | varchar(50) | injection, perfusion, pansement, suture, kine, etc. |
| description | text | Description de l'acte |
| instructions | text NULL | |
| scheduled_at | timestamptz NULL | Si planifié |
| performed_at | timestamptz NULL | Quand réalisé |
| status | varchar(20) | prescribed, scheduled, performed, cancelled |
| notes | text NULL | Notes post-acte |

### Workflow dans l'interface pro

```
/pro/consultations/{uuid}
┌─────────────────────────────────────────────┐
│  Patient: Jean Ndong     [Completed]        │
│  CHU de Libreville — 21/04/2026             │
│                                             │
│  [Consultation] [Examens] [Soins]           │
│  [Traitements] [Ordonnance]                 │
│                                             │
│  ═══ Onglet actif ═══                       │
│                                             │
│  Contenu de l'onglet selectionne            │
│  + formulaire d'ajout en bas               │
└─────────────────────────────────────────────┘
```

Chaque onglet :
- Affiche les éléments existants (si créés)
- Propose un bouton d'ajout (si pertinent)
- Est grisé/vide si aucun élément n'a été créé
- N'est jamais obligatoire

### Lien avec les tables existantes

| Table | Étape du workflow | Déjà créée ? |
|---|---|---|
| `consultations` | 1-3 (motif, examen, diagnostic) | ✅ Phase 5 actuelle |
| `exam_requests` | 4 (examens) | ✅ Phase 5 actuelle |
| `care_acts` | 5 (soins) | ❌ À ajouter |
| `treatments` | 6 (traitements) | ❌ À ajouter |
| `prescriptions` + `prescription_items` | 7 (ordonnance) | ✅ Phase 5 actuelle |

## Conséquences

- Ajouter 2 tables (`treatments`, `care_acts`) et leurs modèles
- Enrichir la fiche consultation avec des onglets
- Le formulaire de consultation reste simple (étapes 1-3)
- Les étapes 4-7 sont ajoutées depuis la fiche consultation (actions contextuelles)
- L'ordonnance est la dernière action possible, pas la première
