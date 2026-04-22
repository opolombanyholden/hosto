# ADR 0015 — Workflow de prise de rendez-vous par l'usager

**Statut** : Accepté
**Date** : 2026-04-22

## Contexte

Le patient ne connait pas nécessairement le médecin qui va le consulter. Il choisit une structure et une spécialité, pas un praticien spécifique. Le système attribue un médecin disponible.

## Décision

### Workflow par défaut (depuis la fiche structure)

```
1. STRUCTURE       L'usager est sur la fiche d'une structure de santé
                   → Clique sur le bouton "Prendre rendez-vous"

2. SPÉCIALITÉ      Choisit la spécialité souhaitée parmi celles
                   proposées par la structure
                   (liste dynamique selon hosto_specialty)

3. DATE + HEURE    Choisit le jour dans un calendrier
                   → Voit les créneaux disponibles pour cette
                   spécialité dans cette structure (tous médecins confondus)
                   → Sélectionne un créneau

4. MOTIF           Ajoute un commentaire sur le motif de consultation
                   (texte libre, 500 chars max)

5. BÉNÉFICIAIRE    Indique si le rendez-vous est :
                   - Pour lui-même → utilise ses infos de profil
                   - Pour un tiers → remplit un formulaire :
                     • Nom complet *
                     • Âge *
                     • Sexe *
                     • Lien avec le demandeur (enfant, parent, conjoint, autre)
                     • Adresse
                     • Ville
                     • Téléphone
                     • Notes médicales éventuelles

6. CONFIRMATION    Résumé : structure, spécialité, date, heure, médecin attribué
                   → Valide le rendez-vous
```

### Attribution du médecin

Le système attribue automatiquement un médecin selon :
1. Disponibilité du créneau sélectionné
2. Spécialité correspondante
3. Charge de travail (répartition équitable)

Le patient voit le nom du médecin attribué **après** la confirmation,
pas pendant la sélection du créneau.

### Workflow alternatif (depuis la fiche médecin)

Le flux existant (choisir un médecin → voir ses créneaux → réserver)
reste disponible comme parcours alternatif pour les patients qui
connaissent déjà leur médecin.

### Impact sur le schéma

**Table `appointments`** : ajouter les colonnes pour le tiers :

| Colonne | Type | Rôle |
|---|---|---|
| `is_for_third_party` | boolean | RDV pour un tiers ? |
| `third_party_name` | varchar NULL | Nom du tiers |
| `third_party_age` | smallint NULL | Âge |
| `third_party_gender` | varchar(10) NULL | Sexe |
| `third_party_relation` | varchar(30) NULL | Lien (enfant, parent, conjoint, autre) |
| `third_party_address` | varchar NULL | Adresse |
| `third_party_city` | varchar NULL | Ville |
| `third_party_phone` | varchar(30) NULL | Téléphone |
| `third_party_notes` | text NULL | Notes medicales |

### Endpoint modifié

```
POST /web/rdv/book
{
    "hosto_uuid": "...",           // structure (obligatoire)
    "specialty_code": "CARD",      // spécialité (obligatoire)
    "time_slot_uuid": "...",       // créneau (obligatoire)
    "reason": "Douleur poitrine",  // motif
    "is_for_third_party": false,   // pour soi ou un tiers
    "third_party": {               // si tiers
        "name": "...",
        "age": 5,
        "gender": "male",
        "relation": "enfant",
        "address": "...",
        "city": "...",
        "phone": "...",
        "notes": "..."
    }
}
```
