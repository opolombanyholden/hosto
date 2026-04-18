# ADR — Architecture Decision Records

Ce dossier contient le journal des décisions architecturales de HOSTO.

## Pourquoi des ADR ?

Une décision d'architecture est rarement évidente. Sans trace, on oublie **pourquoi** un choix a été fait, ce qui conduit à :
- le remettre en question inutilement
- le défaire sans comprendre les contraintes qu'il résolvait
- reproduire les mêmes débats tous les 6 mois

Chaque ADR documente : le **contexte**, la **décision**, les **alternatives considérées** et les **conséquences**.

## Convention

- Un fichier par décision, numéroté séquentiellement : `0001-titre-court.md`
- Statut : `Proposé` | `Accepté` | `Déprécié` | `Remplacé par ADR-XXXX`
- Ne pas modifier un ADR accepté : créer un nouveau ADR qui remplace l'ancien

## Index

| # | Titre | Statut |
|---|---|---|
| 0001 | [Architecture monolithique modulaire](0001-architecture-monolithique-modulaire.md) | Accepté |
| 0002 | [PostgreSQL comme SGBDR principal](0002-postgresql-sgbdr-principal.md) | Accepté |
| 0003 | [UUID et conventions de schéma](0003-uuid-conventions-schema.md) | Accepté |
| 0004 | [Audit trail global et immutabilité](0004-audit-trail-global.md) | Accepté |
| 0005 | [Authentification via Laravel Sanctum](0005-authentification-sanctum.md) | Accepté |
| 0006 | [API versioning et contrat OpenAPI](0006-api-versioning-openapi.md) | Accepté |
| 0007 | [Stratégie Git et workflow de contribution](0007-strategie-git.md) | Accepté |
| 0008 | [Référentiels géographiques bilingues](0008-referentiels-geographiques.md) | Accepté |
| 0009 | [Workflow enregistrement structure + interactions sociales](0009-workflow-enregistrement-structure-interactions.md) | Accepté (spec) |
| 0010 | [Structures partenaires et layout page detail](0010-structures-partenaires-et-layout-detail.md) | Accepté (spec) |
| 0011 | [Trois environnements d'authentification séparés](0011-trois-environnements-authentification.md) | Accepté |
| 0010 | [Structures partenaires et layout page detail](0010-structures-partenaires-et-layout-detail.md) | Accepté (spec) |
