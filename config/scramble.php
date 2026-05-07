<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
    |--------------------------------------------------------------------------
    | ChildShield Climate AI — Scramble API Documentation (OpenAPI 3.1)
    |--------------------------------------------------------------------------
    */

    'api_path' => 'api',
    'api_domain' => env('SCRAMBLE_API_DOMAIN', null),

    'info' => [
        'version' => '1.0.0',
        'description' => <<<'DESC'
# ChildShield Climate AI — REST API

**Climate health alerts for children in Mozambique** via SMS · USSD · WhatsApp · Dashboard.

## Authentication
All dashboard endpoints require a **Bearer JWT token** obtained from `POST /api/v1/auth/login`.

## Versioning
All endpoints are prefixed with `/api/v1/`.

## Roles
| Role | Access |
|------|--------|
| super-admin | Full system access |
| admin | All data + user management |
| government | National read-only + export |
| unicef | National read + create alerts/campaigns |
| ong | Zone read + create alerts/campaigns |
| clinic | Zone read + create alerts |

## Rate Limits
- Admin/Government/UNICEF: 1000 req/min
- ONG: 300 req/min
- Clinic: 100 req/min
DESC
    ],

    'servers' => null,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    'x-tagGroups' => [
        ['name' => 'Authentication', 'tags' => ['Authentication']],
        ['name' => 'Core Data',      'tags' => ['Locations', 'Climate Data', 'Risk Scores']],
        ['name' => 'Alerts & Comms', 'tags' => ['Alerts', 'Campaigns', 'USSD']],
        ['name' => 'Surveillance',   'tags' => ['Symptom Reports', 'Dashboard']],
        ['name' => 'Administration', 'tags' => ['Admin']],
    ],
];
