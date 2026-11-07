# ADR 0017 — Séquestre de la clé de chiffrement médicale

**Statut** : Accepté
**Date** : 2026-11-08

## Contexte

Depuis l'ADR 0016, les données médicales sont chiffrées avec `MEDICAL_ENCRYPTION_KEY`. Perdre cette clé rend **définitivement illisibles** toutes les données médicales de la plateforme. À l'inverse, une clé copiée dans trop d'endroits ou détenue par une seule personne devient un point de fuite.

Il faut donc pouvoir retrouver la clé dans tous les cas, sans qu'une personne seule puisse le faire.

## Décision

### 1. Séquestre par partage de secret (Shamir)

La clé est découpée en **N fragments remis à N dépositaires**. N'importe quels **K** fragments la reconstituent. Avec K−1 fragments, on n'apprend mathématiquement rien de la clé. Par défaut, **3 fragments sur 5** : on survit à la perte de deux fragments, et il faut trois personnes réunies.

- Algorithme : partage de Shamir sur GF(2^8) (polynôme AES), octet par octet, coefficients tirés par `random_bytes`. Implémentation `ShamirSecretSharing`, validée par le vecteur de référence FIPS-197 et par des tests sur toutes les combinaisons.
- Format d'un fragment : `HOSTO1-<empreinte>-<K>-<N>-<x>-<64 hex>-<somme de contrôle>`.
  - La **somme de contrôle** détecte un fragment mal recopié.
  - L'**empreinte** refuse le mélange avec les fragments d'une autre clé.
  - L'empreinte de la clé reconstituée prouve que le résultat est la bonne clé.
- Chaque fragment est écrit dans une **fiche texte imprimable** (droits 0600), avec les consignes pour le dépositaire. Ni la clé ni les fragments ne s'affichent dans le terminal.

### 2. Registre des clés

La table `medical_encryption_keys` conserve l'empreinte de chaque clé, son paramétrage de séquestre et la date du dernier exercice réussi. **Elle ne contient jamais la clé.**

### 3. Garde-fou contre la régénération

`hosto:medical-key` refuse de créer une clé dès que des données sont chiffrées. C'est le scénario de perte le plus probable : une personne qui « régénère » la clé sur un serveur où elle manque. La commande l'oriente vers la reconstitution. La rotation volontaire passe par `--rotate`.

### 4. Contrôle quotidien

`hosto:medical-key-check` tourne chaque jour à 4 h (`routes/console.php`). Il échoue et journalise un message **critique** si :

- la clé est absente ;
- des données sont chiffrées avec une clé indisponible ;
- la clé en service n'est pas sous séquestre.

## Procédures

### Mise en place (une fois par clé)

```bash
php artisan hosto:medical-key-escrow --shares=5 --threshold=3
```

1. Imprimer les fiches, une par dépositaire, sans les copier ailleurs.
2. Les remettre sous enveloppe scellée, contre signature.
3. Supprimer le dossier des fiches du serveur.
4. Faire immédiatement un exercice avec trois dépositaires.

### Choix des dépositaires

Des personnes et des lieux **indépendants**, qui ne peuvent pas disparaître ensemble (même bâtiment, même incident, même départ). Par exemple :

- le directeur général de YUbile Technologie ;
- le responsable technique ;
- un notaire ;
- un coffre bancaire ;
- un représentant de l'autorité partenaire (ministère ou ANINF) ou de l'actionnaire.

Ne jamais confier deux fragments à la même personne.

### Exercice (deux fois par an au minimum)

```bash
php artisan hosto:medical-key-recover --verify
```

Trois dépositaires saisissent chacun leur fragment. La commande confirme la correspondance avec la clé en service, déchiffre une donnée réelle et n'affiche jamais la clé. La date est enregistrée dans `last_verified_at`.

### Reconstitution après perte

```bash
php artisan hosto:medical-key-recover --write
```

En présence d'au moins trois dépositaires et du responsable technique, chacun saisit lui-même son fragment. La clé n'est écrite que si elle figure au registre et déchiffre une donnée réelle.

### Départ d'un dépositaire, perte ou vol d'un fragment

**Redécouper la même clé ne révoque pas les anciens fragments** : trois anciens fragments la reconstitueraient toujours. En cas de fragment perdu, volé ou détenu par une personne qui part dans de mauvaises conditions :

1. faire une rotation de clé (ADR 0016) ;
2. mettre la nouvelle clé sous séquestre ;
3. récupérer et détruire les anciennes fiches.

Un départ dans de bonnes conditions se règle par la remise de la fiche au successeur, devant témoin.

### Anciennes clés

Une clé retirée reste nécessaire tant que des sauvegardes chiffrées avec elle existent. Ses fiches doivent être conservées, et son entrée dans le registre ne doit pas être supprimée, pendant toute la durée de rétention des sauvegardes.

## Alternatives écartées

- **Copie dans un gestionnaire de mots de passe** : simple, mais une seule personne ou un seul compte détient la clé, et sa perte ou sa compromission suffit.
- **Service de gestion de clés d'un fournisseur cloud (KMS)** : dépendance à un fournisseur étranger, contraire à l'objectif d'hébergement au Gabon. Pourra compléter le séquestre si un fournisseur local est retenu.
- **Découpage simple en N morceaux (tous nécessaires)** : la perte d'un seul morceau fait tout perdre.
