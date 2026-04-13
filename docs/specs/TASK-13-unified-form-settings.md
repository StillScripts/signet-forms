# TASK-13: Unified Form Settings JSONB Column

## Problem Statement

Form configuration is currently limited to two flat columns (`success_heading`, `success_message`) on the `forms` table. As the product grows, forms need many more configurable behaviours — submission limits, close dates, branding, compliance options, and richer confirmation settings. Adding each as a separate column would bloat the schema and require a migration for every new setting. A structured JSONB `settings` column provides a single, extensible store for all form-level configuration while preserving type safety through a PHP value object.

Target users are:
- **Form creators (Owners/Admins)** — team members who configure form behaviour, confirmation messages, and branding.
- **Form respondents** — indirectly affected by settings like submission limits, close dates, and post-submit messages.

## Goals

1. **Unified settings column** — Replace `success_heading` and `success_message` with a single `settings` JSONB column on the `forms` table containing structured sections.
2. **Data migration** — Migrate existing `success_heading` and `success_message` values into the new `settings` column under the confirmation section, then drop the old columns.
3. **PHP value object** — Provide a `FormSettings` cast/DTO so application code accesses settings with typed properties and sensible defaults, not raw array access.
4. **Settings UI** — Replace the current "Success Page" section in the edit form with a richer "Settings" interface covering all four sections (behaviour, confirmation, compliance, branding), scoped to what is implementable now.
5. **Backward compatibility** — The public form renderer and all existing tests continue to work with the new structure. Default values match current behaviour.

## Non-Goals

- **Implementing all setting behaviours** — This task adds the data structure and UI for all four sections, but only confirmation settings have functional backend behaviour today. Behaviour settings (submission limits, close dates, CAPTCHA) are stored but not enforced — enforcement is a separate task.
- **Save and resume** — Listed in the behaviour section schema but not implemented. Requires TASK-25.
- **CAPTCHA integration** — Setting is stored but no CAPTCHA provider is wired up.
- **File uploads for branding** — Logo upload requires file storage infrastructure. The field is present in the schema but disabled in the UI until file uploads are supported.
- **Custom CSS injection** — Stored in settings but not applied to the renderer. Security review needed before enabling.
- **Form versioning of settings** — The `form_versions` table currently only snapshots `schema`. Settings versioning is a future consideration.

## User Stories

**As a form creator:**

- I want to configure what respondents see after submitting (heading, message, or redirect URL) so I can control the post-submission experience.
- I want to set a close date for my form so it stops accepting submissions after a deadline.
- I want to limit the number of submissions my form accepts so I can cap registrations.
- I want to see all form settings organised in clear sections so I can find what I need quickly.
- I want settings to have sensible defaults so I only configure what I need to change.

**As a form respondent:**

- I want to see a personalised confirmation message after submitting so I know my response was received.

**Edge cases:**

- A form created before this migration should display the same success messages as before.
- A form with no settings configured should behave identically to current defaults ("Thank you!" / "Your response has been recorded.").
- Null/empty settings column should be treated as all-defaults, not an error.

## Requirements

### Must-Have (P0)

| # | Requirement | Acceptance Criteria |
|---|-------------|---------------------|
| 1 | Add `settings` JSONB column to `forms` table | Migration adds nullable `json` column. Existing forms get their `success_heading`/`success_message` values migrated into `settings.confirmation`. Old columns are dropped. |
| 2 | `FormSettings` value object with typed access | Cast on the Form model. Sections: `behaviour`, `confirmation`, `compliance`, `branding`. Each section is a typed object with defaults. `$form->settings->confirmation->heading` returns a string. |
| 3 | Confirmation section works end-to-end | Public renderer reads heading/message from `$form->settings->confirmation->heading` and `->message`. Defaults match current values ("Thank you!" / "Your response has been recorded."). |
| 4 | Settings UI in Filament edit form | Replace "Success Page" section with a tabbed or sectioned "Settings" area. Confirmation tab has heading, message, and redirect URL fields. Other sections present with fields but clearly labelled as "coming soon" or disabled where backend enforcement doesn't exist yet. |
| 5 | Factory and seeder updated | FormFactory produces valid `settings` structure. DatabaseSeeder migrates to use new settings format for seed data. |
| 6 | All existing tests pass | No regressions. Tests updated to use new settings path. |

### Nice-to-Have (P1)

| # | Requirement | Acceptance Criteria |
|---|-------------|---------------------|
| 7 | Behaviour section fields in UI | Close date (datetime picker), submission limit (integer), allow multiple submissions (toggle). Stored in settings but not enforced. |
| 8 | Compliance section fields in UI | Retention period, data residency, consent text fields. Stored but not enforced. |
| 9 | Branding section fields in UI | Primary colour, font family, custom CSS textarea. Stored but not applied to renderer. |
| 10 | Redirect URL on confirmation | If `settings.confirmation.redirect_url` is set, redirect respondent to that URL instead of showing the thank-you page. |

### Future Considerations (P2)

| # | Requirement |
|---|-------------|
| 11 | Enforce submission limits and close dates (requires reading behaviour settings on submit) |
| 12 | Apply branding settings to public renderer (CSS custom properties from settings) |
| 13 | Version settings alongside schema in `form_versions` |
| 14 | Logo upload for branding section (requires file storage) |
| 15 | CAPTCHA provider integration (reCAPTCHA, hCaptcha, Turnstile) |

## Technical Design

### Settings Structure

```json
{
  "behaviour": {
    "submission_limit": null,
    "close_date": null,
    "allow_multiple_submissions": false,
    "save_and_resume": false,
    "captcha_enabled": false
  },
  "confirmation": {
    "heading": "Thank you!",
    "message": "Your response has been recorded.",
    "redirect_url": null
  },
  "compliance": {
    "standards": [],
    "retention_days": null,
    "data_residency": null,
    "consent_text": null,
    "encrypt_submissions": false
  },
  "branding": {
    "logo_url": null,
    "primary_colour": null,
    "font_family": null,
    "custom_css": null
  }
}
```

### PHP Implementation

- `App\ValueObjects\FormSettings` — top-level DTO implementing `Castable`.
- Nested DTOs: `BehaviourSettings`, `ConfirmationSettings`, `ComplianceSettings`, `BrandingSettings`.
- Each DTO has a static `defaults()` method and `toArray()` for serialisation.
- The Form model casts `settings` to `FormSettings`. Null column returns `FormSettings::defaults()`.

### Migration Strategy

1. Add `settings` JSONB column (nullable).
2. Migrate existing `success_heading`/`success_message` into `settings.confirmation`.
3. Drop `success_heading` and `success_message` columns.
4. All in a single migration file.

### Files to Modify

| File | Change |
|------|--------|
| `database/migrations/` | New migration: add settings, migrate data, drop old columns |
| `app/Models/Form.php` | Replace `success_heading`/`success_message` in fillable with `settings`. Add cast. |
| `app/ValueObjects/FormSettings.php` | New — top-level settings DTO |
| `app/ValueObjects/Settings/` | New — nested section DTOs |
| `app/Filament/.../Schemas/FormForm.php` | Replace "Success Page" section with settings sections |
| `resources/views/livewire/public-form-page.blade.php` | Read from `$formRecord->settings->confirmation->heading` |
| `app/Livewire/PublicFormPage.php` | No change expected (passes `$formRecord` to view) |
| `database/factories/FormFactory.php` | Generate valid `settings` structure |
| `database/seeders/DatabaseSeeder.php` | Use new settings format |
| `tests/Feature/Submissions/SubmissionTest.php` | Update success field tests |
| `tests/Feature/Submissions/PublicFormTest.php` | Update success page tests |

## Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Zero regressions | All existing tests pass after migration | `php artisan test` |
| Data integrity | 100% of existing success_heading/message values preserved | Migration test |
| Settings extensibility | New setting can be added by modifying only the DTO and UI — no migration needed | Code review |

## Open Questions

| Question | Owner | Blocking? |
|----------|-------|-----------|
| Should settings be versioned alongside schema in `form_versions`? | Engineering | No — can be added later |
| Should the branding section be gated behind a plan tier? | Product | No — store the data now, enforce later |
| Should redirect URL support template variables (e.g., `{submission_id}`)? | Product | No — plain URL for v1 |
