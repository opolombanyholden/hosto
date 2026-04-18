# ADR 0010 — Structures partenaires et layout de la page detail

**Statut** : Accepté (spécification, implémentation Phase 2+)
**Date** : 2026-04-18

## Contexte

HOSTO distingue deux catégories de structures dans l'annuaire. Cette distinction impacte les fonctionnalités disponibles et la présentation visuelle.

## Décisions

### 1. Structures partenaires vs non-partenaires

| | Partenaire HOSTO | Non-partenaire |
|---|---|---|
| **Icône** | Logo HOSTO **bleu** | Logo HOSTO **vert** |
| **Visibilité annuaire** | Oui | Oui |
| **Fiche détaillée** | Complète | Informations génériques uniquement |
| **Prise de rendez-vous** | ✅ | ❌ |
| **Téléconsultation** | ✅ | ❌ |
| **Paiement en ligne** | ✅ | ❌ |
| **Like** | ✅ | ❌ |
| **Partage** | ✅ | ❌ |
| **Recommandation** | ✅ | ❌ |
| **Évaluation** | ✅ | ❌ |

**Colonne sur la table `hostos`** : `is_partner` (boolean, default false)

Un partenaire est une structure qui a signé un accord avec Yubile Technologie. Le statut partenaire est distinct du statut vérifié (`is_verified`). Une structure peut être vérifiée (identité confirmée) sans être partenaire (pas d'accord commercial).

```
Non vérifiée        → pas affichée dans l'annuaire
Vérifiée            → affichée, icône verte, informations génériques
Vérifiée + Partenaire → affichée, icône bleue, toutes les fonctionnalités
```

### 2. Layout de la page détail (structure partenaire)

```
┌──────────────────────────────────────────────────────────┐
│                   IMAGE DE COUVERTURE                     │
│  [← Retour]                                         [x]  │
├──────────────────────────────────────────────────────────┤
│  [PROFIL]  Nom de la structure          [🔵 Partenaire]  │
│            Type(s) — Ville — Quartier                     │
│            📞 Téléphone   ✉ Email   🌐 Site web          │
│            🕐 Horaires : Lun-Ven 07:30-17:00              │
│            Statut: Ouvert | Service de garde | Public     │
│            [♡ Like] [↗ Partager] [⭐ Recommander]         │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─────────┬──────────┬────────┬───────┬──────────────┐  │
│  │Prestations│Spécialités│Examens│ Soins │Médicaments   │  │
│  └─────────┴──────────┴────────┴───────┴──────────────┘  │
│                                                          │
│  (Contenu de l'onglet actif)                             │
│  ┌──────────────────────────────────────────────────┐    │
│  │ Consultation générale      5 000 - 10 000 XAF   │    │
│  │ Consultation spécialisée  15 000 - 50 000 XAF   │    │
│  │ Hospitalisation           25 000 - 100 000 XAF  │    │
│  │ ...                                              │    │
│  └──────────────────────────────────────────────────┘    │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────────────────────────────────┐    │
│  │              CARTE OPENSTREETMAP                  │    │
│  │              (pleine largeur)                     │    │
│  │                     📍                            │    │
│  └──────────────────────────────────────────────────┘    │
│  [Itinéraire Google Maps →]                              │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Galerie                                                 │
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐                       │
│  │     │ │     │ │     │ │     │  ← scroll horizontal    │
│  │ img │ │ img │ │ img │ │ img │     clic = zoom/lightbox│
│  │     │ │     │ │     │ │     │                         │
│  └─────┘ └─────┘ └─────┘ └─────┘                       │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

### 3. Layout pour structure NON-partenaire

```
┌──────────────────────────────────────────────────────────┐
│                   IMAGE DE COUVERTURE                     │
│  [← Retour]                                              │
├──────────────────────────────────────────────────────────┤
│  [PROFIL]  Nom de la structure          [🟢 Vérifié]     │
│            Type(s) — Ville — Quartier                     │
│            📞 Téléphone   ✉ Email                        │
│            🕐 Horaires                                    │
│                                                          │
│  (Pas de tabs, pas de Like/Partage/Recommander)          │
│  (Pas de tarifs, pas de prise de RDV)                    │
│                                                          │
│  Spécialités : Cardiologie, Pédiatrie, ...               │
│                                                          │
│  ┌──────────────────────────────────────────────────┐    │
│  │              CARTE OPENSTREETMAP                  │    │
│  └──────────────────────────────────────────────────┘    │
│                                                          │
│  ┌─── Devenir partenaire HOSTO ──────────────────────┐   │
│  │  Accédez à plus de fonctionnalités :               │   │
│  │  rendez-vous, téléconsultation, paiement...        │   │
│  │  [En savoir plus]                                  │   │
│  └────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
```

### 4. Onglets du détail (structures partenaires)

| Onglet | Contenu | Source |
|---|---|---|
| **Prestations** | Services de catégorie `prestation` avec tarifs | `hosto_service` pivot (category=prestation) |
| **Spécialités** | Badges des spécialités proposées | `hosto_specialty` pivot |
| **Examens** | Services de catégorie `examen` avec tarifs | `hosto_service` pivot (category=examen) |
| **Soins** | Services de catégorie `soin` avec tarifs | `hosto_service` pivot (category=soin) |
| **Médicaments** | Produits disponibles (Phase 6 — Pharma) | `hosto_product` pivot (futur) |

L'onglet "Médicaments" est grisé / désactivé jusqu'à la Phase 6.

### 5. Galerie avec lightbox

Au clic sur une image de la galerie :
- Ouverture en plein écran (overlay sombre)
- Navigation gauche/droite (flèches ou swipe mobile)
- Zoom possible (pinch ou double-clic)
- Fermeture par clic extérieur ou bouton X

Librairie recommandée : **GLightbox** (open source, 12KB gzip, touch-friendly, pas de jQuery)
Alternative : implémentation vanilla JS si on veut 0 dépendance.

### 6. Position des éléments dans la page détail

L'ordre vertical est :

1. **Couverture** (bandeau image pleine largeur)
2. **Profil** (avatar chevauchant + infos génériques : nom, types, contact, horaires, statut)
3. **Actions sociales** (Like, Partager, Recommander — partenaires uniquement)
4. **Onglets** (Prestations, Spécialités, Examens, Soins, Médicaments — partenaires uniquement)
5. **Carte** (OpenStreetMap, pleine largeur horizontale)
6. **Galerie** (scroll horizontal avec effet lightbox au clic)

## Impact sur le schéma existant

**Migration à ajouter (Phase 2) :**
```sql
ALTER TABLE hostos ADD COLUMN is_partner boolean NOT NULL DEFAULT false;
CREATE INDEX hostos_is_partner_idx ON hostos (is_partner) WHERE is_partner = true;
```

**Impact Phase 1 :** aucun. La colonne sera ajoutée en Phase 2. En attendant, toutes les structures sont traitées comme "vérifiées non-partenaires" (comportement actuel).

## Alternatives considérées

### Évaluations publiques (comme Google Maps)
- **Rejeté** (cf ADR 0009) : les évaluations restent privées pour éviter le dénigrement public. Seules les recommandations (texte positif modéré) sont publiques.

### Un seul layout pour partenaire et non-partenaire
- **Rejeté** : la distinction visuelle claire (bleu vs vert, fonctionnalités disponibles) est un levier commercial pour inciter les structures à devenir partenaires.
