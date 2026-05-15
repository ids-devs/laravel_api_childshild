# ChildShield Climate AI — API Reference

**Version:** v1  
**Base URL:** `https://<host>/api/v1`  
**Auth:** JWT Bearer Token (`Authorization: Bearer <token>`)  
**Content-Type:** `application/json` (excepto o endpoint USSD)

---

## Índice

1. [Visão Geral](#visão-geral)
2. [Autenticação](#autenticação)
3. [Convenções de Resposta](#convenções-de-resposta)
4. [Controlo de Acesso (RBAC)](#controlo-de-acesso-rbac)
5. [Endpoints Públicos](#endpoints-públicos)
6. [Autenticação](#auth-endpoints)
7. [Localizações](#localizações)
8. [Dados Climáticos](#dados-climáticos)
9. [Risk Scores](#risk-scores)
10. [Alertas](#alertas)
11. [Campanhas](#campanhas)
12. [Dashboard & Analytics](#dashboard--analytics)
13. [Relatórios de Sintomas](#relatórios-de-sintomas)
14. [Famílias](#famílias)
15. [Relatórios](#relatórios)
16. [Administração](#administração)
17. [USSD Gateway](#ussd-gateway)
18. [Modelos de Dados](#modelos-de-dados)
19. [Códigos de Erro](#códigos-de-erro)

---

## Visão Geral

A **ChildShield Climate AI API** é uma REST API que suporta:

- **Dashboard web** — clínicas, ONGs, governo, UNICEF, admins
- **Gateway USSD** — registo de famílias via `*123#` (Africa's Talking)
- **Canal WhatsApp/SMS** — despacho de alertas climático-sanitários

A API usa **JWT** (pacote `tymon/jwt-auth`) com expiração configurável (`jwt.ttl`). Toda a lógica de scoping de dados é orientada pela `organization_type` do utilizador autenticado — clínicas e ONGs vêem apenas a sua zona; governo, UNICEF e admin têm visibilidade global.

---

## Autenticação

Todos os endpoints protegidos exigem o header:

```
Authorization: Bearer <access_token>
```

O token é obtido via `POST /api/v1/auth/login`.

### Fluxo

```
POST /auth/login  →  recebe access_token
GET  /auth/me     →  verifica identidade
POST /auth/refresh → renova token antes de expirar
POST /auth/logout  → invalida token no servidor
```

---

## Convenções de Resposta

### Sucesso — objecto simples

```json
{
  "success": true,
  "message": "OK",
  "data": { ... }
}
```

### Sucesso — lista paginada

```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 98
  }
}
```

### Erro

```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": null
}
```

---

## Controlo de Acesso (RBAC)

O sistema usa `spatie/laravel-permission`. Os papéis existentes e os seus acessos:

| Papel (`role`)   | `organization_type` | Acesso geográfico | Permissões notáveis |
|------------------|---------------------|-------------------|----------------------|
| `super-admin`    | `admin`             | Global            | Tudo |
| `admin`          | `admin`             | Global            | Gerir utilizadores, roles, risk engine |
| `government`     | `government`        | Global (leitura)  | Ver todos os dados |
| `unicef`         | `unicef`            | Global (leitura)  | Ver todos os dados |
| `clinic`         | `clinic`            | Zona própria      | Criar alertas, campanhas |
| `ong`            | `ong`               | Zona própria      | Criar alertas, campanhas |

### Permissões granulares

| Permissão              | Quem tem por omissão         |
|------------------------|------------------------------|
| `manage-users`         | `super-admin`, `admin`       |
| `manage-locations`     | `super-admin`, `admin`       |
| `manage-risk-engine`   | `super-admin`, `admin`       |
| `manage-climate-data`  | `super-admin`, `admin`       |
| `create-alerts`        | `clinic`, `ong`, `admin`     |
| `create-campaigns`     | `clinic`, `ong`, `admin`     |
| `export-reports`       | `clinic`, `ong`, `admin`     |

---

## Endpoints Públicos

Estes endpoints **não requerem autenticação**.

---

### USSD Handler

```
POST /api/v1/ussd
```

Endpoint chamado pelo gateway Africa's Talking a cada interacção USSD.

**Rate limit:** 200 req/min

**Content-Type:** `application/x-www-form-urlencoded`

**Body:**

| Campo         | Tipo   | Obrigatório | Descrição |
|---------------|--------|-------------|-----------|
| `sessionId`   | string | Sim         | ID de sessão AT. Ex: `ATUid_xyz` |
| `serviceCode` | string | Sim         | Código USSD. Ex: `*123#` |
| `phoneNumber` | string | Sim         | Número do utilizador. Ex: `+258849123456` |
| `text`        | string | Sim         | Input acumulado do utilizador. Ex: `1*2` |

**Resposta:** `text/plain`

```
CON Welcome to ChildShield
1. Register family
2. Report symptoms
3. Get health tips
```

Prefixo `CON` = continuar sessão. Prefixo `END` = terminar sessão.

---

### SMS Delivery Callback

```
POST /api/v1/callbacks/sms-delivery
```

Webhook chamado pela Africa's Talking para reportar o estado de entrega de SMS.

**Rate limit:** 500 req/min

**Body:**

| Campo    | Tipo   | Descrição |
|----------|--------|-----------|
| `id`     | string | ID da mensagem AT |
| `status` | string | Estado: `Success`, `Failed`, `Buffered` |

**Resposta:**

```json
{ "status": "ok" }
```

---

### Submeter Relatório de Sintomas (USSD/WhatsApp)

```
POST /api/v1/symptoms
```

**Rate limit:** 30 req/min

**Body:**

| Campo       | Tipo     | Obrigatório | Validação |
|-------------|----------|-------------|-----------|
| `phone_hash`| string   | Sim         | Deve existir na tabela `users` |
| `symptoms`  | string[] | Sim         | `fever`, `diarrhea`, `cough`, `vomiting`, `rash`, `respiratory`, `malaria_symptoms`, `other` |
| `notes`     | string   | Não         | Max 500 chars |
| `channel`   | string   | Sim         | `whatsapp`, `ussd`, `dashboard` |

**Resposta 201:**

```json
{
  "success": true,
  "message": "Report registered",
  "data": {
    "id": 42,
    "user_id": 7,
    "location_id": 3,
    "symptoms": ["fever", "diarrhea"],
    "notes": "Started yesterday",
    "channel": "whatsapp",
    "created_at": "2025-11-01T08:00:00Z"
  }
}
```

---

### Províncias (público)

```
GET /api/v1/locations/provinces
```

Sem autenticação. Usado pelo USSD para selecção de localização.

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Maputo", "region": "Sul" },
    { "id": 2, "name": "Sofala", "region": "Centro" }
  ]
}
```

---

### Distritos por Província (público)

```
GET /api/v1/locations/districts/{provinceId}
```

**Path Params:**

| Param       | Tipo    | Descrição |
|-------------|---------|-----------|
| `provinceId`| integer | ID da província |

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    { "id": 10, "name": "Beira" },
    { "id": 11, "name": "Buzi" }
  ]
}
```

---

## Auth Endpoints

---

### Login

```
POST /api/v1/auth/login
```

**Body:**

| Campo      | Tipo   | Obrigatório |
|------------|--------|-------------|
| `email`    | string | Sim         |
| `password` | string | Sim         |

**Resposta 200:**

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "Admin ChildShield",
      "email": "admin@childshield.mz",
      "organization_type": "admin",
      "roles": ["super-admin"],
      "permissions": ["manage-users", "create-alerts", "..."]
    }
  }
}
```

**Resposta 401:**

```json
{ "success": false, "message": "Invalid credentials" }
```

> O token inclui claims customizados: `email`, `organization_type`, `roles`.

---

### Registar Utilizador Dashboard

```
POST /api/v1/auth/register
```

**Autenticação:** Requerida  
**Permissão:** `manage-users`

**Body:**

| Campo               | Tipo   | Obrigatório | Valores válidos |
|---------------------|--------|-------------|-----------------|
| `name`              | string | Sim         | — |
| `email`             | string | Sim         | Email único |
| `password`          | string | Sim         | Min 8 chars |
| `organization_name` | string | Sim         | — |
| `organization_type` | string | Sim         | `clinic`, `ong`, `government`, `unicef`, `admin` |
| `location_id`       | integer| Não         | FK para `locations` |

**Resposta 201:**

```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "user": {
      "id": 15,
      "name": "Clínica Beira",
      "email": "beira@childshield.mz",
      "organization_type": "clinic"
    }
  }
}
```

---

### Perfil do Utilizador Autenticado

```
GET /api/v1/auth/me
```

**Autenticação:** Requerida

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin",
    "email": "admin@childshield.mz",
    "organization_name": "ChildShield HQ",
    "organization_type": "admin",
    "location": {
      "id": 3,
      "province_id": 2,
      "province": "Sofala",
      "district_id": 10,
      "district": "Beira"
    },
    "roles": ["super-admin"],
    "permissions": ["manage-users", "create-alerts"]
  }
}
```

---

### Renovar Token

```
POST /api/v1/auth/refresh
```

**Autenticação:** Requerida

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAi...",
    "token_type": "bearer"
  }
}
```

---

### Logout

```
POST /api/v1/auth/logout
```

**Autenticação:** Requerida

Invalida o token no servidor (blacklist JWT).

**Resposta 200:**

```json
{ "success": true, "message": "Logged out successfully", "data": null }
```

---

## Localizações

Todos os endpoints abaixo requerem `auth:api` + middleware `check.active`.

---

### Listar Todas as Localizações

```
GET /api/v1/locations
```

**Query Params:**

| Param         | Tipo    | Descrição |
|---------------|---------|-----------|
| `province_id` | integer | Filtrar por província |
| `district_id` | integer | Filtrar por distrito |

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "province_id": 2,
      "district_id": 10,
      "locality": "Munhava",
      "latitude": -19.834,
      "longitude": 34.838,
      "province": { "id": 2, "name": "Sofala" },
      "district": { "id": 10, "name": "Beira" }
    }
  ]
}
```

---

### Detalhe de Localização

```
GET /api/v1/locations/{id}
```

Inclui os risk scores mais recentes para a localização.

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "id": 3,
    "province_id": 2,
    "district_id": 10,
    "locality": "Munhava",
    "latitude": -19.834,
    "longitude": 34.838,
    "malaria_risk_static": 72.5,
    "sanitation_score": 45.0,
    "flood_risk": 60.0,
    "is_coastal": true,
    "is_urban": true,
    "risk_scores": [
      {
        "risk_type": { "code": "malaria", "name": "Malária" },
        "score": 78.3,
        "risk_level": "high",
        "calculated_at": "2025-11-01T06:00:00Z"
      }
    ]
  }
}
```

---

### Instalações de Saúde Próximas

```
GET /api/v1/locations/{id}/facilities
```

**Query Params:**

| Param   | Tipo    | Default | Descrição |
|---------|---------|---------|-----------|
| `limit` | integer | 3       | Número máximo de instalações |

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "CS Munhava",
      "type": "Centro de Saúde",
      "latitude": -19.831,
      "longitude": 34.840,
      "distance_km": 0.4
    }
  ]
}
```

---

### Criar Localização

```
POST /api/v1/locations
```

**Permissão:** `manage-locations`

**Body:**

| Campo                    | Tipo    | Obrigatório | Validação |
|--------------------------|---------|-------------|-----------|
| `province_id`            | integer | Sim         | Existe em `provinces` |
| `district_id`            | integer | Sim         | Existe em `districts` |
| `locality`               | string  | Não         | — |
| `latitude`               | float   | Não         | Entre -90 e 90 |
| `longitude`              | float   | Não         | Entre -180 e 180 |
| `malaria_risk_static`    | float   | Não         | 0–100 |
| `sanitation_score`       | float   | Não         | 0–100 |
| `flood_risk`             | float   | Não         | 0–100 |
| `air_quality_baseline`   | float   | Não         | 0–100 |
| `health_coverage_score`  | float   | Não         | 0–100 |
| `is_coastal`             | boolean | Não         | — |
| `is_urban`               | boolean | Não         | — |

**Resposta 201:** Objecto `Location` criado.

---

## Dados Climáticos

Todos os endpoints requerem `auth:api` + `check.active`.

---

### Dados Climáticos Mais Recentes

```
GET /api/v1/climate/{locationId}/latest
```

Se não houver dados em cache, o sistema faz um fetch automático à fonte externa (OpenWeather / Tomorrow.io / INAM).

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "id": 201,
    "location_id": 3,
    "temperature": 32.5,
    "temperature_max": 35.0,
    "temperature_min": 28.0,
    "rainfall_24h": 12.3,
    "humidity": 85.0,
    "wind_speed": 15.2,
    "air_quality_index": 42.0,
    "source": "openweather",
    "is_forecast": false,
    "recorded_at": "2025-11-01T06:00:00Z"
  }
}
```

---

### Previsão a 5 Dias

```
GET /api/v1/climate/{locationId}/forecast
```

**Resposta 200:** Array de até 5 objectos `ClimateData` com `is_forecast: true`.

---

### Histórico Climático

```
GET /api/v1/climate/{locationId}/history
```

**Query Params:**

| Param  | Tipo    | Default | Máximo | Descrição |
|--------|---------|---------|--------|-----------|
| `days` | integer | 7       | 30     | Janela de histórico |

**Resposta 200:** Array ordenado por `recorded_at` ASC.

---

### Forçar Actualização de Dados Climáticos

```
POST /api/v1/climate/{locationId}/refresh
```

**Permissão:** `manage-climate-data`

**Resposta 200:**

```json
{ "success": true, "message": "Climate data refreshed", "data": { ... } }
```

---

## Risk Scores

Scores calculados pelo motor de risco (0–100). Mapeamento de níveis:

| Score    | Nível      |
|----------|------------|
| 0 – 29   | `low`      |
| 30 – 59  | `medium`   |
| 60 – 84  | `high`     |
| 85 – 100 | `critical` |

---

### Listar Risk Scores (mapa)

```
GET /api/v1/risk-scores
```

Retorna o último score por localização e tipo de risco. Escopo automático por zona do utilizador.

**Query Params:**

| Param       | Tipo   | Descrição |
|-------------|--------|-----------|
| `risk_type` | string | `heat`, `malaria`, `diarrhea`, `respiratory` |
| `min_level` | string | `low`, `medium`, `high`, `critical` |

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": 88,
      "location_id": 3,
      "risk_type": { "code": "malaria", "name": "Malária" },
      "score": 78.3,
      "risk_level": "high",
      "recommendation": "Distribua redes mosquiteiras e active vigilância.",
      "factors": {
        "rainfall_24h": 18.5,
        "temperature": 32.0,
        "malaria_risk_static": 72.5,
        "sanitation_score": 45.0
      },
      "calculated_at": "2025-11-01T06:00:00Z",
      "location": {
        "id": 3,
        "latitude": -19.834,
        "longitude": 34.838,
        "province": { "name": "Sofala" },
        "district": { "name": "Beira" }
      }
    }
  ]
}
```

---

### Risk Scores de uma Localização

```
GET /api/v1/risk-scores/location/{locationId}
```

Retorna todos os tipos de risco para a localização especificada.

> Clínicas/ONGs só podem aceder à sua própria `location_id`.

---

### Histórico de Risk Score

```
GET /api/v1/risk-scores/location/{locationId}/{riskType}/history
```

**Path Params:**

| Param        | Tipo    | Valores |
|--------------|---------|---------|
| `locationId` | integer | — |
| `riskType`   | string  | `heat`, `malaria`, `diarrhea`, `respiratory` |

**Query Params:**

| Param  | Default | Máximo |
|--------|---------|--------|
| `days` | 30      | 90     |

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    { "score": 55.2, "risk_level": "medium", "calculated_at": "2025-10-25T06:00:00Z" },
    { "score": 78.3, "risk_level": "high",   "calculated_at": "2025-11-01T06:00:00Z" }
  ]
}
```

---

### Recalcular Scores para uma Localização

```
POST /api/v1/risk-scores/location/{locationId}/recalculate
```

**Permissão:** `manage-risk-engine`

**Resposta 200:**

```json
{ "success": true, "message": "Risk scores recalculated", "data": [ ... ] }
```

---

## Alertas

---

### Listar Alertas

```
GET /api/v1/alerts
```

**Query Params:**

| Param         | Tipo   | Valores |
|---------------|--------|---------|
| `location_id` | integer | — |
| `status`      | string  | `pending`, `sent`, `failed`, `cancelled` |
| `risk_level`  | string  | `medium`, `high`, `critical` |

**Resposta 200:** Lista paginada (20/página) de alertas.

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "location": { "province": { "name": "Sofala" }, "district": { "name": "Beira" } },
      "risk_type": { "code": "malaria", "name": "Malária" },
      "risk_level": "high",
      "channel": "sms",
      "status": "sent",
      "recipients_total": 1240,
      "recipients_sent": 1198,
      "recipients_failed": 42,
      "sent_at": "2025-11-01T09:00:00Z",
      "created_by": { "name": "Clínica Beira" }
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 20, "total": 47 }
}
```

---

### Criar e Despachar Alerta

```
POST /api/v1/alerts
```

**Permissão:** `create-alerts`

**Body:**

| Campo          | Tipo    | Obrigatório | Descrição |
|----------------|---------|-------------|-----------|
| `location_id`  | integer | Sim         | Zona a alertar |
| `risk_type`    | string  | Sim         | `heat`, `malaria`, `diarrhea`, `respiratory` |
| `channel`      | string  | Sim         | `sms`, `whatsapp`, `both` |
| `scheduled_at` | string  | Não         | ISO 8601. Se omitido, despacha imediatamente |

> O sistema busca automaticamente o último `RiskScore` para a localização e tipo, gera as mensagens em todas as línguas (PT, Changane, Sena, Macua, Ndau) e faz o dispatch via Africa's Talking.

**Resposta 201:**

```json
{
  "success": true,
  "message": "Alert created",
  "data": {
    "id": 13,
    "location_id": 3,
    "risk_level": "high",
    "channel": "sms",
    "status": "pending",
    "scheduled_at": null,
    "created_at": "2025-11-01T08:00:00Z"
  }
}
```

---

### Detalhe de Alerta

```
GET /api/v1/alerts/{id}
```

Inclui até 100 registos de entrega (`deliveries`).

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "id": 12,
    "location": { ... },
    "message_pt": "ALERTA: Risco alto de malária em Beira. Durma sob rede mosquiteira.",
    "message_changane": "...",
    "recipients_total": 1240,
    "recipients_sent": 1198,
    "delivery_report": { "success_rate": 96.6 },
    "deliveries": [
      { "id": 1, "status": "delivered", "sent_at": "2025-11-01T09:01:00Z" }
    ]
  }
}
```

---

### Cancelar Alerta

```
PATCH /api/v1/alerts/{id}/cancel
```

**Permissão:** `create-alerts`

Só cancela alertas com `status = pending`.

**Resposta 200:**

```json
{ "success": true, "message": "Alert cancelled", "data": null }
```

---

## Campanhas

Campanhas são broadcasts personalizados (não automáticos), criados manualmente por operadores.

---

### Listar Campanhas

```
GET /api/v1/campaigns
```

**Query Params:**

| Param    | Tipo   | Valores |
|----------|--------|---------|
| `status` | string | `draft`, `scheduled`, `sent`, `cancelled` |

Clínicas/ONGs vêem apenas as suas próprias campanhas.

**Resposta 200:** Lista paginada (20/página).

---

### Criar Campanha

```
POST /api/v1/campaigns
```

**Permissão:** `create-campaigns`

**Body:**

| Campo               | Tipo     | Obrigatório | Descrição |
|---------------------|----------|-------------|-----------|
| `title`             | string   | Sim         | Título da campanha |
| `message`           | string   | Sim         | Corpo da mensagem |
| `channel`           | string   | Sim         | `sms`, `whatsapp`, `both` |
| `target_provinces`  | string[] | Não         | Filtrar por províncias. Ex: `["Sofala","Gaza"]` |
| `target_districts`  | string[] | Não         | Filtrar por distritos |
| `target_risk_level` | string   | Não         | `medium`, `high`, `critical` |
| `scheduled_at`      | string   | Não         | ISO 8601. Se omitido, agenda para +5 minutos |

**Resposta 201:**

```json
{
  "success": true,
  "message": "Campaign created",
  "data": {
    "id": 7,
    "title": "Prevenção Cólera — Novembro 2025",
    "status": "scheduled",
    "recipients_total": 3420,
    "scheduled_at": "2025-11-01T08:05:00Z"
  }
}
```

---

### Detalhe de Campanha

```
GET /api/v1/campaigns/{id}
```

**Resposta 200:** Objecto `Campaign` com `creator`.

---

### Cancelar Campanha

```
PATCH /api/v1/campaigns/{id}/cancel
```

**Permissão:** `create-campaigns`

Só cancela campanhas com `status` `draft` ou `scheduled`.

---

## Dashboard & Analytics

---

### Overview — KPIs Gerais

```
GET /api/v1/dashboard/overview
```

**Query Params (apenas admins/governo):**

| Param         | Tipo    | Descrição |
|---------------|---------|-----------|
| `location_id` | integer | Filtrar por localização |

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "total_families": 15420,
    "active_subscriptions": 12300,
    "alerts_sent_today": 3,
    "alerts_sent_month": 47,
    "high_risk_zones": 8,
    "symptom_reports_week": 234,
    "families_by_province": {
      "Sofala": 4200,
      "Maputo": 3100
    }
  }
}
```

---

### Dados do Mapa de Risco

```
GET /api/v1/dashboard/risk-map
```

Retorna coordenadas + nível de risco por localização. Consumido pelo mapa Mapbox GL do dashboard.

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    {
      "location_id": 3,
      "latitude": -19.834,
      "longitude": 34.838,
      "risk_level": "high",
      "score": 78.3,
      "risk_type": "malaria"
    }
  ]
}
```

---

### KPIs por Localização

```
GET /api/v1/dashboard/kpis/{locationId}
```

> Clínicas/ONGs só podem consultar a sua própria `location_id`.

**Resposta 200:** Métricas detalhadas para a localização especificada.

---

### Heatmap de Sintomas

```
GET /api/v1/dashboard/symptoms
```

Retorna relatórios de sintomas dos últimos 30 dias agregados por localização.

**Query Params:**

| Param         | Tipo    | Descrição |
|---------------|---------|-----------|
| `location_id` | integer | Filtrar por localização |

---

### Estatísticas de Famílias

```
GET /api/v1/dashboard/families
```

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "by_channel": { "sms": 8400, "whatsapp": 3900, "ussd": 3120 },
    "total": 15420,
    "by_province": { "Sofala": 4200, "Maputo": 3100 }
  }
}
```

---

## Relatórios de Sintomas

---

### Listar Relatórios de Sintomas

```
GET /api/v1/symptoms
```

**Query Params:**

| Param         | Tipo    | Default | Máximo |
|---------------|---------|---------|--------|
| `location_id` | integer | —       | —      |
| `days`        | integer | 30      | 90     |

**Resposta 200:** Lista paginada (50/página).

---

### Sintomas Agregados por Tipo

```
GET /api/v1/symptoms/aggregated
```

**Query Params:** `days` (default 30, máx 90)

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "period_days": 30,
    "total_reports": 234,
    "by_symptom": {
      "fever": 180,
      "diarrhea": 92,
      "cough": 67,
      "malaria_symptoms": 45
    }
  }
}
```

---

## Famílias

---

### Listar Famílias (Households)

```
GET /api/v1/families
```

**Query Params:**

| Param      | Tipo    | Descrição |
|------------|---------|-----------|
| `province` | string  | Nome da província |
| `district` | string  | Nome do distrito |
| `locality` | string  | Localidade |
| `status`   | string  | `active` ou `inactive` |
| `search`   | string  | Pesquisa por hash de telefone |
| `per_page` | integer | Default 10 |

**Resposta 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "phone_number": "+258849123456",
      "language": "pt",
      "channel": "whatsapp",
      "subscription_active": true,
      "location": {
        "id": 3,
        "province": "Sofala",
        "district": "Beira",
        "locality": "Munhava",
        "latitude": -19.834,
        "longitude": 34.838,
        "malaria_risk_static": 72.5,
        "sanitation_score": 45.0,
        "flood_risk": 60.0,
        "is_coastal": true,
        "is_urban": true
      },
      "number_of_children": 3,
      "children_age_groups": ["0-1", "1-5", "6-12"],
      "pregnant_woman": true,
      "weeks_pregnant": "13-28",
      "vulnerability_score": 85.0,
      "consent_status": "accepted",
      "created_at": "2025-09-15T10:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 12, "per_page": 10, "total": 120 }
}
```

---

### Detalhe de Família

```
GET /api/v1/families/{id}
```

> Clínicas/ONGs só podem aceder a famílias da sua zona.

---

### Estatísticas de Famílias

```
GET /api/v1/families/stats
```

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "total": 120,
    "active": 98,
    "high_vulnerability": 34,
    "with_pregnant": 12
  }
}
```

---

## Relatórios

---

### Listar Relatórios Gerados

```
GET /api/v1/reports
```

**Query Params:**

| Param      | Tipo   | Descrição |
|------------|--------|-----------|
| `province` | string | Filtrar por província |
| `district` | string | Filtrar por distrito |
| `status`   | string | `processing`, `ready`, `failed` |
| `search`   | string | Pesquisa por nome |
| `per_page` | integer| Default 10 |

---

### Gerar Novo Relatório

```
POST /api/v1/reports
```

**Permissão:** `export-reports`

**Body:**

| Campo          | Tipo   | Obrigatório | Validação |
|----------------|--------|-------------|-----------|
| `province`     | string | Sim         | — |
| `district`     | string | Não         | — |
| `report_type`  | string | Sim         | `weekly`, `monthly`, `custom` |
| `format`       | string | Sim         | `pdf`, `excel` |
| `period_start` | date   | Sim         | ISO date |
| `period_end`   | date   | Sim         | >= `period_start` |
| `location_id`  | integer| Não         | Apenas para admins/governo |

**Resposta 201:**

```json
{
  "success": true,
  "message": "Report generated successfully",
  "data": {
    "id": 9,
    "name": "Relatorio Mensal — Sofala",
    "report_type": "monthly",
    "format": "pdf",
    "status": "ready",
    "period_start": "2025-10-01",
    "period_end": "2025-10-31",
    "alerts_count": 47,
    "families_covered": 15420,
    "download_url": null
  }
}
```

---

### Detalhe de Relatório

```
GET /api/v1/reports/{id}
```

---

## Administração

Todos os endpoints requerem `auth:api` + `check.active` + `role:super-admin|admin`.

---

### Listar Utilizadores Dashboard

```
GET /api/v1/admin/users
```

**Query Params:**

| Param               | Tipo   | Descrição |
|---------------------|--------|-----------|
| `organization_type` | string | Filtrar por tipo |
| `search`            | string | Pesquisa por nome/email |

**Resposta 200:** Lista paginada (20/página) com roles.

---

### Actualizar Role de Utilizador

```
PATCH /api/v1/admin/users/{id}/role
```

**Body:**

| Campo  | Tipo   | Obrigatório | Validação |
|--------|--------|-------------|-----------|
| `role` | string | Sim         | Deve existir em `roles` |

**Resposta 200:**

```json
{ "success": true, "message": "Role updated", "data": null }
```

---

### Activar / Desactivar Utilizador

```
PATCH /api/v1/admin/users/{id}/status
```

Toggle do campo `is_active`.

**Resposta 200:**

```json
{ "success": true, "message": "Status updated", "data": { "is_active": false } }
```

---

### Estatísticas do Sistema

```
GET /api/v1/admin/stats
```

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "total_families": 15420,
    "active_subscriptions": 12300,
    "total_dashboard_users": 42,
    "total_locations": 128,
    "roles": [
      {
        "id": 1,
        "name": "super-admin",
        "permissions": [ { "name": "manage-users" }, { "name": "create-alerts" } ]
      }
    ]
  }
}
```

---

### Roles e Permissões

```
GET /api/v1/admin/roles
```

**Resposta 200:**

```json
{
  "success": true,
  "data": {
    "roles": [
      { "id": 1, "name": "super-admin", "permissions": [{ "name": "manage-users" }] }
    ],
    "permissions": [
      { "id": 1, "name": "manage-users" },
      { "id": 2, "name": "create-alerts" }
    ]
  }
}
```

---

### Forçar Recálculo Global de Riscos

```
POST /api/v1/admin/trigger/risk
```

**Permissão:** `manage-risk-engine`

Despacha o job `CalculateRiskScoresJob` na fila assíncrona.

**Resposta 200:**

```json
{ "success": true, "message": "Risk recalculation queued", "data": null }
```

---

### Forçar Actualização Global de Dados Climáticos

```
POST /api/v1/admin/trigger/climate
```

**Permissão:** `manage-climate-data`

Despacha o job `FetchClimateDataJob`.

**Resposta 200:**

```json
{ "success": true, "message": "Climate data refresh queued", "data": null }
```

---

## USSD Gateway

Ver secção [Endpoints Públicos → USSD Handler](#ussd-handler).

O fluxo completo é gerido pelo `UssdService`. Principais menus:

| Input  | Acção |
|--------|-------|
| `1`    | Registar família |
| `2`    | Reportar sintomas |
| `3`    | Obter dicas de saúde |
| `0`    | Voltar |

---

## Modelos de Dados

### `ClinicUser` — Utilizador Dashboard

| Campo               | Tipo      | Descrição |
|---------------------|-----------|-----------|
| `id`                | integer   | PK |
| `name`              | string    | Nome completo |
| `email`             | string    | Único |
| `organization_name` | string    | Nome da organização |
| `organization_type` | string    | `clinic`, `ong`, `government`, `unicef`, `admin` |
| `location_id`       | integer   | FK → `locations` |
| `is_active`         | boolean   | Conta activa |
| `last_login_at`     | datetime  | — |
| `last_login_ip`     | string    | — |
| `roles`             | string[]  | Via Spatie (virtual) |

---

### `User` — Família Registada

| Campo                    | Tipo     | Descrição |
|--------------------------|----------|-----------|
| `id`                     | integer  | PK |
| `phone_number_encrypted` | string   | AES-256 via pgp_sym_encrypt |
| `phone_hash`             | string   | SHA-256 para lookup |
| `channel`                | string   | `sms`, `whatsapp`, `ussd` |
| `language`               | string   | `pt`, `changane`, `sena`, `macua`, `ndau` |
| `location_id`            | integer  | FK → `locations` |
| `consent_status`         | boolean  | Consentimento dado |
| `subscription_active`    | boolean  | Subscrito activo |
| `consent_given_at`       | datetime | — |
| `last_interaction_at`    | datetime | — |

> O campo `phone_number` é um accessor virtual que desencripta `phone_number_encrypted` em runtime.

---

### `Household` — Agregado Familiar

| Campo                | Tipo    | Descrição |
|----------------------|---------|-----------|
| `id`                 | integer | PK |
| `user_id`            | integer | FK → `users` |
| `number_of_children` | integer | — |
| `children_age_groups`| array   | `["0-1","1-5","6-12","13+"]` |
| `pregnant_woman`     | boolean | — |
| `weeks_pregnant`     | string  | `1-12`, `13-28`, `29+` |
| `vulnerability_score`| float   | 0–100 (calculado) |

**Cálculo do `vulnerability_score`:**

| Condição | Pontos |
|----------|--------|
| Criança 0–1 anos | +30 |
| Criança 1–5 anos | +20 |
| Criança 6–12 anos | +10 |
| Grávida | +25 |
| Grávida 1º trimestre | +5 extra |
| Por cada filho (max +20) | +5/filho |

---

### `Location` — Localização Geográfica

| Campo                   | Tipo    | Descrição |
|-------------------------|---------|-----------|
| `id`                    | integer | PK |
| `province_id`           | integer | FK → `provinces` |
| `district_id`           | integer | FK → `districts` |
| `locality`              | string  | Sub-localidade |
| `latitude` / `longitude`| float   | Coordenadas WGS84 |
| `malaria_risk_static`   | float   | Factor estático (0–100) |
| `sanitation_score`      | float   | Score saneamento (0–100) |
| `flood_risk`            | float   | Risco inundação (0–100) |
| `air_quality_baseline`  | float   | Baseline qualidade do ar |
| `health_coverage_score` | float   | Cobertura de saúde (0–100) |
| `is_coastal`            | boolean | — |
| `is_urban`              | boolean | — |

---

### `ClimateData` — Dado Climático

| Campo               | Tipo     | Descrição |
|---------------------|----------|-----------|
| `location_id`       | integer  | FK → `locations` |
| `temperature`       | float    | °C |
| `temperature_max`   | float    | °C |
| `temperature_min`   | float    | °C |
| `rainfall_24h`      | float    | mm em 24h |
| `humidity`          | float    | % |
| `wind_speed`        | float    | km/h |
| `air_quality_index` | float    | AQI |
| `source`            | string   | `openweather`, `tomorrow`, `inam` |
| `is_forecast`       | boolean  | `true` = previsão, `false` = observação |
| `recorded_at`       | datetime | — |

---

### `RiskScore` — Score de Risco

| Campo           | Tipo     | Descrição |
|-----------------|----------|-----------|
| `location_id`   | integer  | FK → `locations` |
| `risk_type_id`  | integer  | FK → `risk_types` |
| `score`         | float    | 0–100 |
| `risk_level`    | string   | `low`, `medium`, `high`, `critical` |
| `recommendation`| string   | Texto de recomendação gerado |
| `factors`       | object   | JSON com os factores usados no cálculo |
| `calculated_at` | datetime | — |

---

### `Alert` — Alerta Despachado

| Campo               | Tipo     | Descrição |
|---------------------|----------|-----------|
| `location_id`       | integer  | FK → `locations` |
| `risk_score_id`     | integer  | FK → `risk_scores` |
| `risk_type_id`      | integer  | FK → `risk_types` |
| `risk_level`        | string   | Nível no momento do envio |
| `message_pt`        | string   | Mensagem em português |
| `message_changane`  | string   | Mensagem em Changane |
| `message_sena`      | string   | Mensagem em Sena |
| `message_macua`     | string   | Mensagem em Macua |
| `message_ndau`      | string   | Mensagem em Ndau |
| `channel`           | string   | `sms`, `whatsapp`, `both` |
| `status`            | string   | `pending`, `sent`, `failed`, `cancelled` |
| `recipients_total`  | integer  | — |
| `recipients_sent`   | integer  | — |
| `recipients_failed` | integer  | — |
| `scheduled_at`      | datetime | — |
| `sent_at`           | datetime | — |
| `created_by`        | integer  | FK → `clinic_users` |

---

### `Campaign` — Campanha de Broadcast

| Campo               | Tipo     | Descrição |
|---------------------|----------|-----------|
| `title`             | string   | — |
| `message`           | string   | Corpo da mensagem |
| `channel`           | string   | `sms`, `whatsapp`, `both` |
| `target_provinces`  | array    | Filtro por províncias |
| `target_districts`  | array    | Filtro por distritos |
| `target_risk_level` | string   | Filtro por nível de risco |
| `status`            | string   | `draft`, `scheduled`, `sent`, `cancelled` |
| `recipients_total`  | integer  | — |
| `recipients_sent`   | integer  | — |
| `scheduled_at`      | datetime | — |
| `created_by`        | integer  | FK → `clinic_users` |

---

### `GeneratedReport` — Relatório Gerado

| Campo              | Tipo   | Descrição |
|--------------------|--------|-----------|
| `name`             | string | Nome gerado automaticamente |
| `period_start`     | date   | — |
| `period_end`       | date   | — |
| `province`         | string | — |
| `district`         | string | — |
| `report_type`      | string | `weekly`, `monthly`, `custom` |
| `format`           | string | `pdf`, `excel` |
| `status`           | string | `processing`, `ready`, `failed` |
| `download_url`     | string | URL de download (nullable) |
| `alerts_count`     | integer| — |
| `families_covered` | integer| — |
| `location_id`      | integer| FK → `locations` |
| `created_by`       | integer| FK → `clinic_users` |

---

## Códigos de Erro

| HTTP | Código de negócio | Situação |
|------|-------------------|----------|
| 200  | — | Sucesso |
| 201  | — | Recurso criado |
| 400  | — | Pedido inválido / erro de validação |
| 401  | — | Token ausente, expirado ou inválido |
| 403  | — | Sem permissão para o recurso / zona |
| 404  | — | Recurso não encontrado |
| 422  | — | Erro de validação com detalhe nos campos |
| 500  | — | Erro interno do servidor |

### Formato de erro de validação (422)

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "channel": ["The selected channel is invalid."]
  }
}
```

---

## Referência Rápida de Endpoints

| Método   | Endpoint                                            | Autenticação | Permissão |
|----------|-----------------------------------------------------|:------------:|-----------|
| `POST`   | `/ussd`                                             | ✗ | — |
| `POST`   | `/callbacks/sms-delivery`                           | ✗ | — |
| `POST`   | `/symptoms`                                         | ✗ | — |
| `GET`    | `/locations/provinces`                              | ✗ | — |
| `GET`    | `/locations/districts/{provinceId}`                 | ✗ | — |
| `POST`   | `/auth/login`                                       | ✗ | — |
| `POST`   | `/auth/register`                                    | ✓ | `manage-users` |
| `GET`    | `/auth/me`                                          | ✓ | — |
| `POST`   | `/auth/refresh`                                     | ✓ | — |
| `POST`   | `/auth/logout`                                      | ✓ | — |
| `GET`    | `/locations`                                        | ✓ | — |
| `GET`    | `/locations/{id}`                                   | ✓ | — |
| `GET`    | `/locations/{id}/facilities`                        | ✓ | — |
| `POST`   | `/locations`                                        | ✓ | `manage-locations` |
| `GET`    | `/climate/{locationId}/latest`                      | ✓ | — |
| `GET`    | `/climate/{locationId}/forecast`                    | ✓ | — |
| `GET`    | `/climate/{locationId}/history`                     | ✓ | — |
| `POST`   | `/climate/{locationId}/refresh`                     | ✓ | `manage-climate-data` |
| `GET`    | `/risk-scores`                                      | ✓ | — |
| `GET`    | `/risk-scores/location/{locationId}`                | ✓ | — |
| `GET`    | `/risk-scores/location/{locationId}/{riskType}/history` | ✓ | — |
| `POST`   | `/risk-scores/location/{locationId}/recalculate`    | ✓ | `manage-risk-engine` |
| `GET`    | `/alerts`                                           | ✓ | — |
| `POST`   | `/alerts`                                           | ✓ | `create-alerts` |
| `GET`    | `/alerts/{id}`                                      | ✓ | — |
| `PATCH`  | `/alerts/{id}/cancel`                               | ✓ | `create-alerts` |
| `GET`    | `/campaigns`                                        | ✓ | — |
| `POST`   | `/campaigns`                                        | ✓ | `create-campaigns` |
| `GET`    | `/campaigns/{id}`                                   | ✓ | — |
| `PATCH`  | `/campaigns/{id}/cancel`                            | ✓ | `create-campaigns` |
| `GET`    | `/dashboard/overview`                               | ✓ | — |
| `GET`    | `/dashboard/risk-map`                               | ✓ | — |
| `GET`    | `/dashboard/kpis/{locationId}`                      | ✓ | — |
| `GET`    | `/dashboard/symptoms`                               | ✓ | — |
| `GET`    | `/dashboard/families`                               | ✓ | — |
| `GET`    | `/symptoms`                                         | ✓ | — |
| `GET`    | `/symptoms/aggregated`                              | ✓ | — |
| `GET`    | `/families`                                         | ✓ | — |
| `GET`    | `/families/stats`                                   | ✓ | — |
| `GET`    | `/families/{id}`                                    | ✓ | — |
| `GET`    | `/reports`                                          | ✓ | — |
| `POST`   | `/reports`                                          | ✓ | `export-reports` |
| `GET`    | `/reports/{id}`                                     | ✓ | — |
| `GET`    | `/admin/users`                                      | ✓ | `super-admin\|admin` |
| `PATCH`  | `/admin/users/{id}/role`                            | ✓ | `super-admin\|admin` |
| `PATCH`  | `/admin/users/{id}/status`                          | ✓ | `super-admin\|admin` |
| `GET`    | `/admin/stats`                                      | ✓ | `super-admin\|admin` |
| `GET`    | `/admin/roles`                                      | ✓ | `super-admin\|admin` |
| `POST`   | `/admin/trigger/risk`                               | ✓ | `manage-risk-engine` |
| `POST`   | `/admin/trigger/climate`                            | ✓ | `manage-climate-data` |
