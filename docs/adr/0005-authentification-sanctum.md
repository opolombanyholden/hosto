# ADR 0005 — Authentification via Laravel Sanctum

**Statut** : Accepté
**Date** : 2026-04-15

## Contexte

HOSTO doit authentifier :
- Applications mobiles Flutter (iOS, Android) : tokens longue durée
- Application desktop Flutter : tokens longue durée
- Partenaires externes (CNAMGS, assurances) : tokens de service
- SPA éventuelles : cookies session ou tokens
- USSD/SMS : session éphémère liée à un numéro

## Décision

Utiliser **Laravel Sanctum** comme couche d'authentification.

### Pourquoi Sanctum plutôt qu'un JWT package

- Sanctum est **officiel Laravel**, maintenu, intégré à Eloquent
- Tokens stockés en base (révocation immédiate, essentielle en santé)
- Scopes/abilities natifs (RBAC fin)
- Pas de dépendance à une lib tierce qui peut être abandonnée (cf tymon/jwt-auth)

### Topologie des tokens

| Type | Durée | Usage | Rotation |
|---|---|---|---|
| `access_token` | 15 min | Appels API | À chaque refresh |
| `refresh_token` | 30 jours | Obtenir un nouvel access_token | À chaque utilisation (rolling) |
| `personal_access_token` | Configurable | Intégrations partenaires | Manuelle |
| `session_token` | 2h | USSD/SMS | Non renouvelable |

### 2FA obligatoire pour

- Tout compte professionnel (médecin, infirmier, pharmacien, laborantin, admin)
- Tout accès au dossier médical (même par le patient lui-même)
- Toute opération d'administration

### Mécanismes 2FA supportés (par ordre de préférence)

1. **TOTP** (Google Authenticator, Authy) — standard, offline
2. **SMS OTP** — fallback, surtout Afrique
3. **Email OTP** — fallback ultime

### Règles de mot de passe

- Minimum 12 caractères
- Pas de règle de complexité rigide (contre-productive) mais blacklist Pwned Passwords
- Pas de rotation forcée périodique (NIST SP 800-63B, contre-productif)
- Verrouillage progressif après échecs : 3 tentatives → délai 30s, 5 → 5 min, 10 → 1h

## Alternatives considérées

### JWT pur (firebase/php-jwt)
- Rejeté : révocation complexe (nécessite une blacklist = perd l'avantage stateless)

### Laravel Passport
- Rejeté : surdimensionné (OAuth2 complet) pour des besoins d'API propre

### Keycloak ou Auth0
- Rejeté en v1 : dépendance externe, souveraineté. À réévaluer en Phase 10 si SSO multi-structures devient critique.

## Conséquences

- Tables `personal_access_tokens`, `two_factor_secrets`, `two_factor_recovery_codes`
- Policies Laravel pour chaque ressource sensible
- Permission matrix stockée en base (rôles modifiables) + cache Redis
