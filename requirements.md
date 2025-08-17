# 📞 Call-Bot SaaS — Full Product Prompt (Validated & Completed)

We’re building a **multi-tenant call-bot platform** that lets companies create outbound **voice campaigns** powered by **ElevenLabs TTS** and a pluggable **Call Gateway** (Twilio/Maqsam/SIP). Users register, pick a company type, create campaigns with schedules, import data (orders, leads, contacts), assign agents, and analyze results in a premium **shadcn-vue** UI.

---

## 🔑 Principles
- **Reusable**: Controller → Service → Repository. Services are business-logic first; repositories do DB only.
- **Scalable**: multi-tenant, modular, background jobs, provider adapters.
- **Simple where possible**: don’t over-engineer; prioritize reuse in services.
- **Premium UX**: clean, modern, fast. All tables use server-side pagination + filters via AJAX.
- **Event-driven**: everything emits auditable events.

---

## 📦 Modules

### 1. Authentication & RBAC
- Signup → select Company Type → redirect to dashboard.
- Roles:
  - `PARENT_SUPER_ADMIN`: full access to all companies & data.
  - `COMPANY_ADMIN`: full access within company.
  - `AGENT`: handle assigned campaigns/calls only.
  - `VIEWER`: read-only dashboards/reports.
- Team invites (email link), 2FA optional.
- Tenant scoping on every request (`company_id`).

### 2. Company & Company Type
- Super Admin: CRUD companies, assign type (E-commerce, Healthcare, Real Estate, Generic).
- Company Settings:
  - default timezone & business hours
  - call rules (max concurrency, retries, recording)
  - provider keys (ElevenLabs API key, Call Gateway credentials)
  - compliance flags (GDPR/HIPAA mode), data retention policy
  - default language/locale, currency for cost reporting

### 3. Call Gateway (Abstracted)
- **GatewayAdapter** interface with implementations:
  - TwilioAdapter, MaqsamAdapter, SIP/WebRTCAdapter
- Responsibilities:
  - originate call(to, from, campaignId, metadata)
  - fetch call status & cost
  - handle webhooks: ringing/answered/busy/failed/voicemail/DTMF
  - recording URL handling
- Configurable per company or campaign.

### 4. Voice & Conversation (ElevenLabs)
- ElevenLabs TTS voice profile per **Agent Template** or per campaign.
- Script templates (greetings, retry, voicemail, fallback).
- Multi-language support.
- Optional STT/IVR (future).

### 5. Campaigns
- Create/Edit in **Dialog**:
  - name, description, schedule, retries, concurrency, call order, record_calls
  - assigned agent
  - data source: Contacts/Leads or E-commerce Orders
- Status lifecycle: `draft → active → completed → archived`.
- Import Data action (reusable **Import Dialog Component**).

### 6. Agents
- CRUD agents per company; assign to campaigns.
- **Predefined Agent Templates**:
  - role, tone, persona, ElevenLabs voice_id, language, scripts
- Agent performance metrics: calls made, connect rate, conversions.

### 7. Contacts / Leads / Orders
- Contacts module:
  - fields: name, phone, tags, segment, locale, status
  - **Do-Not-Call (DNC)** list at company level.
  - Opt-out management (keyword/webhook/manual).
- E-commerce:
  - Import Orders + OrderItems (CSV/XLSX).
  - Auto-populate Product/Category/Brand if new.
  - CRUD orders + items inline.
- Generic Leads for non-ecommerce.

### 8. Scheduling & Execution Engine
- Background jobs (Redis workers).
- **CampaignScheduler**: enqueue eligible contacts/orders by schedule.
- **CallJob**: originate call, handle webhooks, retries.
- Timezone-aware execution.

### 9. Reports & Analytics
- Campaign Dashboard:
  - attempts, connected, voicemail, busy, failed, retries, completion %
  - funnel: attempted → connected → successful
  - answered heatmap
- Agent Performance
- Contact Outcomes
- **Cost Tracking**:
  - call cost, TTS usage, per campaign/agent/segment
- Export: CSV/PDF
- Server-side filters + pagination.

### 10. Settings & Integrations
- ElevenLabs test & key validation.
- Call gateway test call.
- Webhooks:
  - call.status.updated, contact.opted_out, campaign.completed, import.completed
- Public REST API:
  - push contacts/leads/orders, start/stop campaign, fetch reports.
- Optional CRM connectors (Shopify, HubSpot, Zoho).

### 11. Audit Trail / Events
- Actions emit events:
  - campaign.created, import.completed, call.answered, dnc.added, etc.
- Event viewer with filters + export.

### 12. Compliance & Security
- GDPR/HIPAA modes:
  - data retention policies
  - PII masking for non-privileged roles
  - mandatory opt-out enforcement
  - immutable audit logs
- Rate limiting, IP allowlist
- Encryption at rest for sensitive fields

---

## 🎨 UI/UX (shadcn-vue)
- Layout: sidebar + topbar.
- Reusable dialogs:
  - Campaign Create/Edit
  - Import Data
  - Filter Dialog
  - Assign Agent
  - Voice Preview/Test Call
- Tables:
  - server-side pagination/sort/filter
  - row actions
- Dashboards:
  - campaign KPIs, agent KPIs, costs, trend charts
- Design: clean, premium, modern.

use only shadcn-vue components install if not found
---

## ⚙️ Developer Guidelines

### Architecture
- **Controllers**: request/response, validation, tenancy.
- **Services**: business logic (scheduling, retries, DNC, costs).
- **Repositories**: raw DB queries.
- **Adapters**: GatewayAdapter, StorageAdapter, ImportParser.

### Example Service Contracts
- `CampaignService`: create, update, activate, archive, enqueue batch, compute stats
- `CallingService`: originate call, handle webhook, schedule retry
- `ImportService`: detect file type, dryRun, import
- `DncService`: add/remove/check
- `CostService`: record(callId, gatewayCost, ttsUnits)

### Status Enums
- **Contact**: `new | queued | calling | called | success | failed | opted_out`
- **Call**: `queued | ringing | answered | voicemail | busy | failed | no_answer`
- **Import**: `pending | validating | running | completed | failed`

### Imports
- CSV/XLSX with column mapping UI.
- Auto-populate entities during import.
- Chunked processing for large files.



## 📊 Non-Functional Requirements
- Observability: structured logs, metrics, tracing.
- DB migrations & seeders (roles, agent templates, demo data).
- Feature flags for gradual rollouts.
- Horizontal scalability of workers.
- Per-tenant concurrency enforcement.

---

## ✅ MVP Definition of Done
- End-to-end campaign lifecycle: create → import → schedule → calls placed → statuses tracked → dashboards updated.
- DNC/opt-out enforced.
- Server-side filters/pagination in all tables.
- Events logged & visible.
- Reusable dialogs built with shadcn-vue.
- Pluggable Call Gateway + ElevenLabs voice preview.
 