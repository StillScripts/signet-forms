# Tasks

## Active

## Waiting On

## Someday

### Foundation Alignment


- [ ] **[TASK-12] Expand RBAC to five roles** - Add Editor, Reviewer, and Viewer roles to the TeamRole enum alongside existing Owner, Admin, Member (renamed from Member). Implement the full permission matrix from Architecture Doc Section 4.2 covering form management, submission access, team management, workspace settings, billing, audit trail, and integrations.

- [ ] **[TASK-14] Add submission metadata and status workflow** - Extend Submission model with: metadata JSONB (IP, user agent, device, time_to_complete, referrer), form_version integer, status enum (pending, in_review, approved, rejected, archived), assigned_reviewer_id, respondent_email, respondent_name. Add status transition UI in admin panel.
- [ ] **[TASK-15] Audit logging** - Create append-only `audit_logs` table (resource_type, resource_id, action, actor, IP, details JSONB). Build AuditService and queued WriteAuditLog job. Log: form CRUD, publish/unpublish, submission view/status change/export/delete, team member changes, settings changes, login events. Add read-only Filament page for browsing audit trail.

### Builder & Field Types

- [ ] **[TASK-16] Expand field type library** - Add field types from Architecture Doc Section 6.3: email, phone, date_range, time, rating, ranking, single_select (dropdown variant), multi_select, yes_no, signature, address. Implement builder property editors and public form renderers for each. Group as Basic, Choice, and Advanced in the field palette.
- [ ] **[TASK-17] Layout elements in builder** - Add non-data-collecting elements: section_header, divider, instructional_text, image. These render in both builder canvas and public form but are excluded from submission validation and data storage.
- [ ] **[TASK-18] Conditional logic engine** - Create `logic_rules` table and LogicEngine service. Support show/hide/require/skip-to-page actions with AND/OR condition chaining. Evaluate rules server-side on submission (discard hidden field values). Store rule references on fields via logicRuleIds.
- [ ] **[TASK-19] Logic editor UI** - Build Filament custom page for managing logic rules per form. Visual rule builder with condition rows, action selection, and test mode to preview logic without publishing.

### Submission Management

- [ ] **[TASK-20] Submission review workflow** - Build multi-stage approval engine: `workflow_stages` table (name, order, assigned_role/users, SLA days, escalation), `workflow_actions` table (append-only action log). Submission detail slide-over with Workflow tab showing timeline and action buttons (approve/reject/request changes). Scheduled CheckWorkflowSla job for escalation.
- [ ] **[TASK-21] Submission notes** - Add `submission_notes` table (author, body, timestamps, soft deletes). Internal-only comments visible in submission detail. Notes tab in the submission view with threaded display.
- [ ] **[TASK-22] CSV/Excel export of submissions** - Queued ProcessBulkExport job that streams results to storage. Email download link to requester. Filterable by date range, status, form. Role-based export restrictions with audit-logged reason.
- [ ] **[TASK-23] Submission PDF generation** - Queued job using Browsershot or Snappy. Per-submission PDF view matching the form layout with respondent data filled in. Optional auto-generation on submit (configurable in form settings). Store in S3.
- [ ] **[TASK-24] Email notifications** - Create `notification_rules` table (form_id, trigger, recipients JSONB, subject/body templates, attach_pdf, enabled). Queued SendNotificationEmail job. Triggers: new submission, status change, workflow action. Configurable per form in settings.

### Distribution & Respondent Experience

- [ ] **[TASK-25] Save and resume** - Add resume_token to submissions. Allow respondents to save partial progress and receive an email with a resume link. Resume URL: `/{form-slug}/resume/{token}`. Token-based access, no authentication required.
- [ ] **[TASK-26] QR code generation** - Generate QR codes for published form URLs. PNG/SVG download from the form view page. Display alongside the existing share URL.
- [ ] **[TASK-27] Embed distribution** - Generate iframe embed snippet with configurable dimensions. Copy-to-clipboard in form distribution settings. Embed-friendly layout variant for the public form renderer.
- [ ] **[TASK-28] Per-tenant respondent branding** - CSS custom properties set from workspace branding config. Configurable: logo, primary/secondary colours, background, font, button labels, progress bar style, form width. Applied to public form renderer without rebuilding CSS.

### Templates & API

- [ ] **[TASK-5] Form templates** - Allow users to create a form from a preset template (e.g., Contact Us, Feedback Survey, Registration) instead of starting blank. Seed 10+ templates across industries (government, education, non-profit, events, medical). Shared `templates` table (not tenant-scoped). Template library page in admin panel.
- [ ] **[TASK-29] REST API v1** - Sanctum token authentication for external consumers. API key management (scopes, expiry, revocation) in workspace settings. Versioned endpoints at `/api/v1` for forms and submissions. Eloquent API Resources. Rate limiting per Architecture Doc Section 9.1.
- [ ] **[TASK-30] Webhook integration** - Configurable webhook endpoints per form in `integrations` table. HMAC-SHA256 signed payloads. Queued DeliverWebhook job with 5 retries and exponential backoff up to 24h. Delivery logs with status tracking.

### Dashboard & Analytics

- [ ] **[TASK-31] Dashboard widgets** - Custom Filament dashboard replacing the default. Stats overview (total forms, total submissions, active forms, pending reviews). Recent forms list, recent submissions list. Getting-started checklist for first-time users (dismissible). Quick actions row.
- [ ] **[TASK-32] Submission analytics** - Workspace-level analytics page with submission trends over time (Chart.js via Filament widgets), completion rates, drop-off analysis by field. Form-level stats on the form view page.

### Compliance & Enterprise

- [ ] **[TASK-33] Compliance modes** - HIPAA/GDPR toggles on workspace. Data retention enforcement via scheduled EnforceDataRetention job (soft-deletes expired submissions, writes audit entries). Consent field auto-insertion. Privacy impact assessment fields.
- [ ] **[TASK-34] Field-level encryption** - EncryptionService using AES-256-GCM. Workspace-specific encryption keys. Encrypt designated sensitive field values before writing to submissions.data. Decrypt on read for authorised users. Sensitive flag on field schema objects.
- [ ] **[TASK-35] Plan enforcement** - Plan tiers (free, starter, professional, enterprise) with plan_limits JSONB on workspace. PlanEnforcement middleware checking limits for form creation, submission acceptance, member invitations, file uploads. 402 response with upgrade prompt when limits exceeded.
- [ ] **[TASK-36] SSO integration** - SAML 2.0 (via laravel-saml2) and OAuth 2.0 (via Socialite) for Google Workspace and Microsoft Entra ID. Per-workspace SSO configuration. Optional 2FA enforcement at workspace level.
- [ ] **[TASK-37] Custom domain support** - Workspace custom_domain column. DNS verification workflow. Middleware resolving workspace from Host header. SSL via AWS Certificate Manager or Let's Encrypt. Available on Professional and Enterprise plans.
- [ ] **[TASK-38] White-label branding** - Complete FormForge branding removal for Enterprise workspaces. Custom login page, email templates, and respondent surface with no FormForge identity.

### Integrations (Phase 3+)

- [ ] **[TASK-39] TrustLoop consent integration** - Connect FormForge consent fields to TrustLoop's consent API. Phase 1: standalone toggle. Phase 3: centralised consent record management, preference centres, withdrawal workflows.
- [ ] **[TASK-40] Microsoft 365 connector** - SharePoint document upload for file submissions, Teams notification on new submissions. Configurable per form via integrations settings.
- [ ] **[TASK-41] Google Workspace connector** - Google Sheets append (submission data rows), Google Drive upload (file submissions), Gmail notification. Configurable per form.
- [ ] **[TASK-42] Payment field (Stripe)** - Stripe payment field type for paid form submissions. Configurable amount or calculated from form fields. Payment confirmation before submission. Webhook for payment status updates.

### AI & Advanced (Phase 4)

- [ ] **[TASK-43] AI-assisted form generation** - Describe a form in natural language, receive a draft schema. Uses Claude API to generate form fields, validation rules, and page structure from a text description.
- [ ] **[TASK-44] AI-powered logic suggestions** - Suggest conditional logic rules based on field types and common patterns. Contextual suggestions in the logic editor.
- [ ] **[TASK-45] Advanced analytics** - Drop-off analysis by field, A/B testing for form variants, conversion funnel visualisation.

## Done

- [x] **[TASK-13] Add unified form settings JSONB column** - Replace flat form columns (success_heading, success_message) with a structured `settings` JSONB column with four sections (behaviour, confirmation, compliance, branding). FormSettings value object with Castable/Wireable interfaces. Tabbed settings UI in Filament. 5 unit tests, all 230 tests pass.
- [x] **[TASK-11] Migrate to UUID primary keys** - Convert all tables to UUIDv7 via HasUuids trait. Consolidate incremental migrations into create-table migrations. Add DatabaseSeeder with test user, two teams, projects, forms, and submissions. All 192 tests pass.
- [x] **[TASK-10] Review and comply with technical specifications** - Create a comprehensive task list in TASKS.md based on the FormForge Technical Architecture v1.0 document. Map existing work to the architecture, identify gaps, and plan future tasks with proper sequencing. Added 35 new tasks across 8 categories. Added Workspaces rename note to Team model.
- [x] **[TASK-9] Form submissions with public renderer** - Add a Submission model (JSONB data column, one record per form response). Build a public-facing form renderer page at `/forms/{team}/{form}` using Filament components. Add submissions sidebar navigation, per-form submission viewing, and customisable success page. 30 tests.
- [x] **[TASK-8] Migrate to page-based schema structure** - Rename `fields` → `schema` on forms and form_versions tables. Restructure data from flat field arrays to `{ "pages": [{ "id", "title", "heading", "subheading", "submit_button_text", "fields" }] }` format supporting single-page and multi-page (wizard) forms. Add multi-page builder UI with page tabs, add/remove/reorder pages, page settings panel. Migrate existing data. Update all tests.
- [x] **[TASK-7] Add form versioning model** - Introduce a `form_versions` table as an append-only snapshot store. Each save creates a new version record. Replaces in-memory undo/redo with DB-backed version navigation. Builder header shows version label.
- [x] **[TASK-6] Add top-level Forms sidebar navigation** - Add a top-level "Forms" item in the sidebar (below Projects) showing all forms across all projects in the current team. Table includes a Project column. Clicking a form navigates to the existing nested view. Create action requires project selection.
- [x] **[TASK-4] Add visual form builder page** - Build a custom Livewire page for the form builder with three-panel interface: component palette (left), canvas with drag-to-reorder (center), field settings (right). Includes Builder/Preview tabs, multi-column layout support, undo/redo, and save functionality. Fields stored as JSON in the Form model's `fields` column.
- [x] **[TASK-3] Add Form model and Filament resource under Projects** - Introduce the Form model belonging to Project with name, slug, description, fields (JSON), is_published. Includes migration, factory, policy, TeamPermission entries, Filament CRUD resource scoped under projects, and tests.
- [x] **[TASK-2] Add Projects to the application** - Introduce the Project model as an organizational layer between Teams and Forms. Teams own Projects, Projects will eventually own Forms. Includes model, migration, factory, Filament resource, policy, and tests.
- [x] **[TASK-1] Set up feature documentation workflow** - Add docs/ directory structure, integrate doc-coauthoring, product-brainstorming, and write-spec skills into the development workflow so every feature gets a spec with clear reasoning. Eventually these docs will power a doc site.
