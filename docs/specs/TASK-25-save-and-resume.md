# TASK-25: Save and Resume

## Problem Statement

Long public forms (patient intake, permit applications, multi-page job applications, etc.) ask respondents to provide a lot of information in a single sitting. If the browser closes, the network drops, or the respondent simply needs time to gather paperwork, all progress is lost. This causes high abandonment on multi-page forms and forces respondents to restart from scratch.

Respondents must be able to pause and resume a submission without creating an account.

## Goals

1. Let a respondent save their current progress on a public form at any time from a "Save and finish later" button.
2. Capture the respondent's email address and send them a resume link that works without authentication.
3. Persist partial progress as a draft `Submission` with a secure, unguessable `resume_token`.
4. Support resuming into the correct page on multi-page (wizard) forms.
5. On final submit, promote the draft to a completed submission and invalidate the token.

## Non-Goals

- **Multiple concurrent drafts per respondent per form** — one active draft per token, period. If a respondent wants another, they restart.
- **Account creation** — explicitly out of scope per task description. Anonymous token-based flow only.
- **Expiring drafts via scheduled cleanup** — we expire via a `resume_token_expires_at` timestamp that prevents resume, but purge logic can ship with a later retention task (TASK-33).
- **Draft list UI for admins** — admins can see a draft in the submissions table only if/when we decide to expose draft status. Not in this task.
- **Live autosave on every keystroke** — explicit save action only. Avoids race conditions, surprise behaviour and failed writes.
- **SMS/Push resume links** — email only for now.

## User Stories

- As a patient filling in a long intake form, I can click "Save and finish later", enter my email, and receive a link that takes me back to exactly where I left off on any device.
- As a job applicant halfway through a multi-page application, I can resume on the same page I saved on, with all my previously entered values already filled in.
- As a respondent who has finished, submitting clears the draft so my resume link no longer works.

## Implementation

### Database

Add columns to the `submissions` table via a new migration:

- `resume_token` — `string(64)`, nullable, unique index. Random 40-character token.
- `resume_token_expires_at` — `timestamp`, nullable. Defaults to `now() + 30 days` at save time.
- `resume_page_index` — `unsignedInteger`, nullable. Which page the respondent was on when they saved (multi-page wizard).
- `is_draft` — `boolean`, default `false`. Distinguishes draft rows from completed submissions.

Add a partial/conditional index is not strictly required — the unique index on `resume_token` with nullability is sufficient (nullable columns ignore null duplicates in Postgres/MySQL).

### Model

Update `App\Models\Submission`:

- Add the new columns to the `Fillable` attribute list.
- Cast `resume_token_expires_at` to `datetime` and `is_draft` to `boolean`.
- Add a helper `isResumable(): bool` that returns true when `is_draft && resume_token && resume_token_expires_at?->isFuture()`.
- Add a `generateResumeToken(): string` static helper using `Str::random(40)`.

Filter draft submissions out of analytics and submission list views (Filament resource, submissions table) by scoping to `is_draft = false` where those views already exist. Audit the submissions table and SubmissionsTable filters.

### Public form flow

Update `App\Livewire\PublicFormPage`:

1. **Save action (`saveProgress`)**:
    - Take an `email` and optional `currentPageIndex` argument.
    - Create or update a draft `Submission`:
        - If `$this->submissionId` is already set (from an earlier save or a resume), update it in place.
        - Otherwise create a new row with `is_draft = true`, `resume_token = Submission::generateResumeToken()`, `resume_token_expires_at = now()->addDays(30)`.
    - Store current form state in `data`. We intentionally bypass required-field validation on save (use `$this->form->getRawState()` instead of `getState()`).
    - Dispatch a queued `SendResumeLink` mailable to the provided email.
    - Show a success confirmation view telling the respondent to check their email.

2. **Resume mount**: when mounted from the resume route, load the draft submission by token, hydrate `$this->data` from `submission->data`, and set `$this->submissionId` and `$this->resumedPageIndex`. For multi-page forms, start the wizard at the saved page.

3. **Submit**: when `submit()` runs and `$this->submissionId` is set, update that row instead of creating a new one. Set `is_draft = false`, null the resume fields so the token is permanently invalidated.

### Route & controller

Add a route in `routes/web.php`:

```php
Route::get('forms/{team:slug}/{formSlug}/resume/{token}', PublicFormPage::class)
    ->name('forms.resume');
```

The existing `PublicFormPage` Livewire component's `mount()` method accepts the optional `$token` parameter and loads the draft. Using the same component avoids duplication.

The URL pattern differs from the task wording (`/{form-slug}/resume/{token}`) because forms are already namespaced under `/forms/{team}/{slug}` in `web.php`. Keeping the team prefix is necessary for tenant isolation.

### Email

Add `App\Mail\SubmissionResumeLink` mailable modelled on `SubmissionExportReady`:

- Queued (implements `ShouldQueue` via `Queueable` trait).
- Constructor takes the `Submission`.
- Builds `route('forms.resume', [...])` with the token.
- Uses `mail::message` markdown layout via `resources/views/emails/submission-resume-link.blade.php`.

### UI

Add a "Save and finish later" button next to the Submit button on the public form page. Clicking it toggles a small inline form that collects an email address and calls `saveProgress($email)`. After success, the confirmation view replaces the save form.

On multi-page forms, the save button lives inside the wizard footer on every step. Pass the current step index via a small Livewire state property.

For the resumed form view, show a dismissable banner: "Welcome back. Your previous progress has been loaded." This banner is rendered from the Blade view when `$resumedPageIndex !== null`.

### Template seeding (extra requirement)

Extend `database/seeders/FormTemplateSeeder.php` so at least one template demonstrates where save-and-resume is valuable: a multi-page Patient Intake template with clear page boundaries (Personal Info, Medical History, Insurance, Consent). Introduce a `multiPageSchema()` helper alongside the existing `schema()` helper — both produce `{ pages: [...] }` but `multiPageSchema()` accepts an array of page definitions, each with `title`, `heading`, `subheading`, `submit_button_text`, and `fields`.

Leave the existing single-page templates unchanged to keep the PR focused; only Patient Intake becomes multi-page in this task.

## Acceptance Criteria

- [ ] `submissions` table has `resume_token`, `resume_token_expires_at`, `resume_page_index`, `is_draft` columns with correct types/indexes.
- [ ] Respondent can click "Save and finish later" on a public form, enter an email, and receive a resume email.
- [ ] The resume email contains a working URL that loads the form with prior values filled in.
- [ ] On multi-page forms, the wizard opens at the saved page.
- [ ] Submitting the form after resume promotes the draft to a completed submission and invalidates the token.
- [ ] Expired or unknown tokens return 404.
- [ ] Draft submissions are excluded from the admin submissions list and CSV export.
- [ ] `FormTemplateSeeder` ships at least one multi-page template (Patient Intake) with clearly separated pages.
- [ ] All new code has Pest tests; existing tests continue to pass.

## Success Metrics

- Respondents on long forms have an escape hatch that survives browser close.
- Draft submissions are counted separately and never leak into completed submission dashboards.
- No authentication is required to resume.

## Open Questions

- Should we rate-limit how often a single email can request resume links? (Not in this task — can be handled at the mailer level later.)
- Should the resume link auto-extend the expiry on use? (Left out for simplicity — 30 days is the window.)
