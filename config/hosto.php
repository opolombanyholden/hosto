<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HOSTO Platform Configuration
|--------------------------------------------------------------------------
|
| Central configuration for the HOSTO modular monolith.
|
| @see docs/adr/0001-architecture-monolithique-modulaire.md
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Active modules
    |--------------------------------------------------------------------------
    |
    | Activate/deactivate each business module. "Core" is always loaded
    | and cannot be disabled.
    |
    | Per-country or per-tenant overrides can be layered on top via
    | environment variables or DB-driven config in later phases.
    |
    */
    'modules' => [
        // Phase 1.3+
        'Annuaire' => env('HOSTO_MODULE_ANNUAIRE', true),

        // Phase 1.1+
        'Referentiel' => env('HOSTO_MODULE_REFERENTIEL', true),

        // Phase 3
        'Usager' => env('HOSTO_MODULE_USAGER', false),

        // Phase 4
        'RendezVous' => env('HOSTO_MODULE_RDV', true),

        // Phase 5+
        'Pro' => env('HOSTO_MODULE_PRO', true),
        'Pharma' => env('HOSTO_MODULE_PHARMA', true),
        'Lab' => env('HOSTO_MODULE_LAB', true),
        'Telecon' => env('HOSTO_MODULE_TELECON', true),
        'Billing' => env('HOSTO_MODULE_BILLING', true),
        'Sync' => env('HOSTO_MODULE_SYNC', true),

        // Phase 11 — Modules specialises
        'Mwana' => env('HOSTO_MODULE_MWANA', true),
        'EVax' => env('HOSTO_MODULE_EVAX', true),
        'Lost' => env('HOSTO_MODULE_LOST', true),
        'Humanity' => env('HOSTO_MODULE_HUMANITY', true),
        'Connect' => env('HOSTO_MODULE_CONNECT', true),
        'HostoPlus' => env('HOSTO_MODULE_HOSTOPLUS', true),

        // Phase 12+
        'Analytic' => env('HOSTO_MODULE_ANALYTIC', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment mode
    |--------------------------------------------------------------------------
    |
    | cloud : central deployment (Yubile / ANINF)
    | local : on-premise deployment in a health structure
    |
    | Used to adjust sync behavior, enabled modules, data origin tags.
    |
    */
    'deployment' => env('HOSTO_DEPLOYMENT', 'cloud'),

    /*
    |--------------------------------------------------------------------------
    | Structure identifier (local deployments only)
    |--------------------------------------------------------------------------
    */
    'structure_uuid' => env('HOSTO_STRUCTURE_UUID'),

    /*
    |--------------------------------------------------------------------------
    | Country scope
    |--------------------------------------------------------------------------
    |
    | ISO 3166-1 alpha-2 code. Used as default for referentials and
    | geolocation queries.
    |
    */
    'country' => env('HOSTO_COUNTRY', 'GA'),

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    |
    | @see docs/adr/0004-audit-trail-global.md
    |
    */
    'audit' => [
        'enabled' => env('HOSTO_AUDIT_ENABLED', true),

        // HMAC key for signing audit log lines. In production this MUST
        // come from a secrets manager, never from .env.
        'signing_key' => env('HOSTO_AUDIT_SIGNING_KEY'),

        // Retention (days) for partitioned tables. Beyond this the
        // partitions are archived to cold storage.
        'retention_days' => env('HOSTO_AUDIT_RETENTION_DAYS', 1825), // 5 years
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | @see docs/adr/0006-api-versioning-openapi.md
    |
    */
    'api' => [
        'current_version' => 'v1',
        'supported_versions' => ['v1'],
    ],

];
