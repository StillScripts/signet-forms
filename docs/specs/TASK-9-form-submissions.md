# TASK-9: Form Submissions with Public Renderer

## Problem Statement

Forms can be built and previewed in the admin panel, but there is no way for end users to actually fill out and submit a form. The system needs a public-facing form renderer that presents published forms on a responsive landing page, collects responses, and stores them as submissions. Form creators also need to view collected submissions in the admin panel — both globally across all forms and per-form.

Target users are:
- **Form respondents** — anyone with a link to a published form who fills it out on any device.
- **Form creators (Owners/Admins)** — team members who need to see collected responses for their forms.

## Goals

1. **Submission model** — Create a `submissions` table with a JSONB `data` column storing a key-value map (field keys → submitted values). One record per form response.
2. **Public form renderer** — A guest-accessible page that renders a published form using its schema. Responsive across mobile, tablet, and desktop. Multi-page forms render as a wizard/stepper.
3. **Submission storage** — Validate and store form responses. Map submitted values to field keys from the form schema.
4. **Admin submissions view** — Add a top-level "Submissions" sidebar item showing all submissions across all forms in the current team. Add per-form submission viewing via the form's view/edit pages.
5. **Thank-you page** — After successful submission, show a confirmation page.

## Non-Goals

- **Field-level encryption** — The data model supports it in the future (JSONB column), but no encryption is implemented in this task.
- **File upload handling** — FileUpload fields are defined in the schema but actual file storage/processing is out of scope. These fields will be skipped in the public renderer for now.
- **Spam prevention** — No CAPTCHA, rate limiting, or honeypot fields in this task.
- **Email notifications** — No email to form creator on new submission.
- **Submission editing** — Respondents cannot edit after submission. Admins can view but not edit.
- **Export** — No CSV/Excel export of submissions in this task.
- **Analytics** — No submission analytics, charts, or dashboards.
- **Draft/partial submissions** — No save-and-resume. Submission is all-or-nothing.

## User Stories

**As a form respondent:**

- I want to visit a public URL and see a form I can fill out, so I can provide my information.
- I want the form to look good on my phone, tablet, or desktop.
- I want multi-page forms to guide me through steps with clear navigation.
- I want to see which fields are required before I submit.
- I want to see a confirmation page after submitting so I know it worked.
- I want to see validation errors if I miss required fields or enter invalid data.

**As a form creator (Owner/Admin):**

- I want to see a list of all submissions across my team's forms in the sidebar.
- I want to see submissions for a specific form so I can review responses.
- I want to see when each submission was created.
- I want to see the submitted data in a readable format.
- I want only published forms to be accessible publicly.

**Edge cases:**

- Unpublished forms should return a 404 or "form not available" page.
- Submitting to a form that was unpublished after the page loaded should fail gracefully.
- A form with no fields should still allow submission (empty data).
- A form with multiple pages should validate per-page before advancing (nice-to-have).

## Requirements

### Must-Have (P0)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 1 | **Submission model** — Create `submissions` table with `id`, `form_id` (FK), `data` (JSON), `timestamps`. One record per form response. | Migration creates table. Model has `form` relationship. Form has `submissions` relationship. Factory exists. |
| 2 | **Submission data structure** — `data` column stores `{ "field_key": "value", ... }` where keys match field keys from the form schema. | Submitted data maps correctly to schema field keys. |
| 3 | **Public form route** — Guest-accessible route (e.g., `/forms/{team:slug}/{form:slug}`) that renders a published form. | Route exists. No authentication required. Returns form page for published forms. |
| 4 | **Public form renderer** — Blade/Livewire page that renders the form schema as interactive HTML form elements. Responsive layout using Tailwind. | All field types render correctly. Layout respects column_span. Labels, placeholders, required indicators shown. |
| 5 | **Multi-page wizard rendering** — Multi-page forms render as a step-by-step wizard with next/previous navigation. | Pages render in order. User can navigate forward/back. Last page has submit button. |
| 6 | **Form submission** — POST handler that validates required fields and stores the submission. | Required fields enforced. Data saved to submissions table. Success response shown. |
| 7 | **Thank-you page** — After successful submission, display a confirmation message. | User sees confirmation. Cannot accidentally re-submit by refreshing. |
| 8 | **Unpublished form guard** — Only published forms (`is_published = true`) are accessible via the public route. | Unpublished forms return 404 or "form not available" page. |
| 9 | **Submissions sidebar item** — Top-level "Submissions" item in the admin sidebar showing all submissions for the current team. | Sidebar item visible. Table shows submission data with form name, submitted date. |
| 10 | **Per-form submissions** — View submissions filtered to a specific form, accessible from the form's admin pages. | Can navigate from form to its submissions. Table shows submitted data and timestamp. |
| 11 | **Submission policy** — Team members can view submissions. Only Owners/Admins can delete submissions. | Policy enforced. Members can view. Members cannot delete. |
| 12 | **Tests** — Feature tests for model relationships, public form rendering, submission storage, validation, policy, and admin views. | All tests pass. |

### Nice-to-Have (P1)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 13 | **Per-page validation** — Multi-page forms validate the current page's required fields before allowing navigation to the next page. | Validation errors shown on current page. Cannot advance with invalid data. |
| 14 | **Submission detail view** — Click a submission in the admin to see a formatted view of all submitted data with field labels. | Detail page shows field labels and values in a readable layout. |
| 15 | **Submission count on form list** — Show submission count on the forms table. | Count column visible. Updates when new submissions arrive. |

### Future Considerations (P2)

| # | Requirement |
|---|---|
| 16 | **Field-level encryption** — Encrypt sensitive field values at the application level before storage. |
| 17 | **File upload handling** — Store uploaded files and link them to submissions. |
| 18 | **Spam prevention** — CAPTCHA, rate limiting, honeypot fields. |
| 19 | **Email notifications** — Notify form creator on new submission. |
| 20 | **CSV/Excel export** — Export submissions for a form. |
| 21 | **Submission analytics** — Charts, response rates, field-level analytics. |

## Data Model

### Submissions table

```
submissions
├── id (bigint, PK)
├── form_id (bigint, FK → forms.id, cascade delete)
├── data (JSON) — { "field_key": "submitted_value", ... }
├── created_at (timestamp)
└── updated_at (timestamp)
```

### Data column structure

```json
{
  "full_name": "Jane Smith",
  "email": "jane@example.com",
  "feedback": "Great product!",
  "rating": "5",
  "newsletter": true
}
```

Keys are the field `key` values from the form schema. Values are the submitted data as strings/booleans/numbers depending on field type.

## Public Form URL Structure

```
/forms/{team:slug}/{form:slug}
```

This keeps forms namespaced to teams and uses slugs for readable URLs. The route resolves the team and form, checks `is_published`, and renders the form.

## Submission Flow

```
Respondent visits /forms/{team}/{form}
  → Controller resolves team + form, checks is_published
  → Livewire component renders form from schema
  → User fills in fields (wizard steps for multi-page)
  → User clicks Submit
  → Livewire validates required fields
  → Creates Submission { form_id, data: { key: value, ... } }
  → Redirects to thank-you state/page
```

## Public Form Rendering

Each field type maps to a standard HTML/Livewire form element:

| FormFieldType | Rendered as |
|---|---|
| TextInput | `<input type="text">` |
| Textarea | `<textarea>` |
| Number | `<input type="number">` |
| Select | `<select>` |
| Checkbox | `<input type="checkbox">` |
| RadioGroup | Radio button group |
| Toggle | Checkbox styled as toggle |
| DatePicker | `<input type="date">` |
| FileUpload | Skipped (P2) |
| RichEditor | `<textarea>` (plain text input) |

Layout uses CSS grid with the form's column count, respecting each field's `column_span`.

## Admin Panel Integration

### Sidebar

```
Dashboard
Projects
Forms
Submissions  ← NEW
```

### Submissions Resource

- Table columns: Form name, Submission date, Preview of data (truncated)
- Filterable by form
- Sortable by date
- View action to see full submission data

### Per-form access

From the Form view/edit page, a "Submissions" tab or action links to the submissions table filtered to that form.

## Success Metrics

- Public form renderer loads and is interactive on mobile, tablet, and desktop.
- All 10 field types render correctly (except FileUpload which is skipped).
- Multi-page forms render as wizard with working navigation.
- Submissions are stored with correct field key mapping.
- Admin can view submissions globally and per-form.
- All existing tests still pass. New tests cover the full feature.

## Decisions

| # | Decision |
|---|---|
| 1 | Public form uses **Filament/Livewire** for rendering — Filament's form components handle field rendering, validation, and wizard steps natively. |
| 2 | **No respondent metadata tracking** for now (IP, user agent). Could be added as a future enhancement. |
| 3 | **Customisable success page** — Form model gets `success_heading` and `success_message` columns. Defaults to "Thank you!" / "Your response has been recorded." when null. |
