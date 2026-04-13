# TASK-5: Form Templates

## Problem Statement

Every new form starts blank. Users with common needs (contact form, feedback survey, event registration) must manually recreate fields they've built before or seen elsewhere. This slows onboarding and creates friction for users who know *what* they want but not how to build it from scratch.

## Goals

1. Provide a library of 10+ ready-made form templates across industries
2. Let users create a form from a template in two clicks (pick template, confirm project)
3. Templates are shared globally — not scoped to any tenant
4. Template library is browsable and filterable by category in the admin panel

## Non-Goals

- **User-created templates** — Users cannot save their own forms as templates in this version. That's a future enhancement.
- **Template editing/management UI** — Templates are seeded; there is no admin CRUD for managing the template library itself.
- **Template versioning** — Templates are static seed data. No version history needed.
- **Template preview rendering** — No live form preview from the template library. Users see the field list and description, then create the form and preview it in the builder.

## Schema Changes

New `form_templates` table:

| Column | Type | Default | Notes |
|---|---|---|---|
| `id` | uuid | — | Primary key |
| `name` | string(255) | — | Template display name |
| `slug` | string(255) | — | URL-friendly unique identifier |
| `description` | text | null | What the template is for |
| `category` | string(100) | — | Industry/use-case category |
| `icon` | string(100) | null | Heroicon name for display |
| `schema` | json | — | Same structure as Form.schema (pages with fields) |
| `created_at` | timestamp | — | — |
| `updated_at` | timestamp | — | — |

No foreign keys — templates are global, not tenant-scoped.

## FormTemplateCategory Enum

Categories for grouping templates:

- `general` — General Purpose
- `business` — Business & Corporate
- `education` — Education
- `events` — Events & Registration
- `government` — Government & Public Sector
- `healthcare` — Healthcare & Medical
- `nonprofit` — Non-Profit & Community
- `feedback` — Feedback & Surveys

## Template Library

A new Filament page accessible from the sidebar (or from the "Create Form" flow). Displays templates as a card grid with:

- Template name and description
- Category badge
- Icon
- "Use Template" button

Filtering by category. Search by name/description.

### "Use Template" Flow

1. User clicks "Use Template" on a template card
2. Modal opens asking for: form name (pre-filled from template name) and project selection
3. On confirm: a new Form is created in the selected project with the template's schema copied in
4. User is redirected to the form builder page for the new form

### Integration with Create Form

The existing "Create Form" action should offer a link/button to "Browse Templates" as an alternative to starting blank.

## Templates to Seed (10+)

| # | Name | Category | Fields |
|---|---|---|---|
| 1 | Contact Us | general | name, email, phone, subject, message |
| 2 | Feedback Survey | feedback | name, email, rating (select), what_went_well, improvements, recommend (radio) |
| 3 | Event Registration | events | first_name, last_name, email, phone, ticket_type (select), dietary_requirements, terms (checkbox) |
| 4 | Job Application | business | full_name, email, phone, position (select), cover_letter (textarea), resume (file) |
| 5 | Patient Intake | healthcare | first_name, last_name, date_of_birth (date), email, phone, medical_conditions (textarea), medications (textarea), emergency_contact, consent (checkbox) |
| 6 | Volunteer Sign-Up | nonprofit | name, email, phone, availability (select), skills (textarea), experience (textarea), background_check (checkbox) |
| 7 | Course Evaluation | education | course_name, instructor, rating (select), most_valuable, least_valuable, suggestions, recommend (radio) |
| 8 | Public Comment | government | name, email, organisation, topic (select), comment (textarea), supporting_docs (file) |
| 9 | Bug Report | business | reporter_name, email, severity (select), steps_to_reproduce (textarea), expected_behaviour (textarea), actual_behaviour (textarea) |
| 10 | Workshop Registration | events | first_name, last_name, email, organisation, role, workshop_choice (select), special_requirements (textarea) |
| 11 | Donation Pledge | nonprofit | donor_name, email, phone, amount (number), frequency (radio: one-time/monthly/yearly), dedication (textarea) |
| 12 | Permit Application | government | applicant_name, email, phone, address (textarea), permit_type (select), project_description (textarea), start_date (date), supporting_docs (file) |

## Requirements

### Must-Have (P0)

- [ ] `form_templates` migration with all columns
- [ ] `FormTemplate` model with slug generation, schema cast
- [ ] `FormTemplateCategory` enum with labels and icons
- [ ] `FormTemplateSeeder` seeding 12 templates
- [ ] Template library Filament page with card grid layout
- [ ] Category filtering on the template library
- [ ] "Use Template" action that creates a form from the template schema
- [ ] Project selection in the "use template" modal
- [ ] Redirect to form builder after creation
- [ ] Permission gate — user must have `form:create` permission
- [ ] Tests for template model, seeder, and "use template" flow

### Nice-to-Have (P1)

- [ ] Search/filter by template name
- [ ] "Browse Templates" link from the Create Form flow
- [ ] Template field count badge on cards

## Files to Create/Modify

- New: `app/Models/FormTemplate.php`
- New: `app/Enums/FormTemplateCategory.php`
- New: migration for `form_templates` table
- New: `database/seeders/FormTemplateSeeder.php`
- New: `app/Filament/Resources/FormTemplates/Pages/TemplateLibrary.php`
- Modify: `database/seeders/DatabaseSeeder.php` — call FormTemplateSeeder
- Modify: Filament panel provider — register template library page
- New: `tests/Feature/FormTemplates/FormTemplateTest.php`
