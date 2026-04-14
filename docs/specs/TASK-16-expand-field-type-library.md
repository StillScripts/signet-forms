# TASK-16: Expand Field Type Library

## Problem Statement

The form builder ships with 10 data-collecting field types. That is enough for a prototype but falls far short of the Architecture Doc's section 6.3 target (email, phone, date range, time, rating, ranking, single/multi select, yes/no, signature, address) and far short of Filament v5's own form component catalogue. Form authors resort to generic `TextInput` for things that should have first-class UX — email validation, phone masks, address composites, signature capture — and the palette feels bare compared to competitors.

This task widens the palette to every practical Filament v5 form component plus composite types for the architecture list, grouped into Basic / Choice / Advanced / Layout categories.

## Goals

1. Add every practical Filament v5 form component as a first-class palette field.
2. Add the composite types called out in Architecture Doc section 6.3: date range, rating, ranking, yes/no, address, signature.
3. Group all data-collecting field types under **Basic**, **Choice**, or **Advanced** in the palette. Layout elements stay under **Layout** (unchanged from TASK-17).
4. Every new type has a property editor, a builder canvas preview, a public-form render path, and feature tests.
5. Every new type round-trips through save → form_versions → load without loss.

## Non-Goals

- **`Hidden` and `Builder` Filament components** — Hidden has nothing to render to an end respondent; Builder *is* the form builder itself. Both are explicitly skipped.
- **Conditional validation per type** (e.g., phone format regex per locale) — keep validation simple and per-type generic; TASK-18 (logic engine) can layer on conditional rules later.
- **Custom repeater sub-schemas** — the generic `Repeater` field exposes a single repeatable text input for now. A configurable sub-schema builder is a future enhancement.
- **Address autocomplete** — the `Address` field is a static fieldset of text inputs. A future enhancement could swap in Google Places autocomplete, but that is out of scope here.
- **Rating half-stars, custom signature palettes, code editor language auto-detect, slider pips customisation** — accept the Filament defaults.
- **Field-level encryption of signatures or sensitive payloads** — TASK-34 owns that.

## User Stories

- As a form author, I want an Email field so respondents get proper email validation and a `type="email"` input.
- As a form author, I want a Phone field with a tel-style input so mobile respondents get the right keyboard.
- As a form author, I want a Date Range field so respondents can answer "when are you available" with a start and end date in one question.
- As a form author, I want a Rating field so I can ask "How likely are you to recommend us?" with stars rather than a generic number input.
- As a form author, I want a Ranking field so respondents can drag options into their preferred order.
- As a form author, I want a Yes/No field so binary questions look binary, not like a generic toggle.
- As a form author, I want a Signature field so respondents can sign a waiver or consent form.
- As a form author, I want an Address field so the form collects street / suburb / state / postcode / country in a logical group.
- As a form author, I want access to every Filament field (Slider, ColorPicker, TagsInput, KeyValue, CodeEditor, MarkdownEditor, ToggleButtons, CheckboxList, DateTimePicker, TimePicker, Repeater, MultiSelect) so I do not feel constrained by the palette.
- As a respondent, I want rich, appropriate inputs for every question — not generic text boxes.

## Requirements

### Must Have

1. **New `FieldCategory` enum** (`app/Enums/FieldCategory.php`) with cases `Basic`, `Choice`, `Advanced`, `Layout`. Each case carries a `label()` used in the palette. Replace the current `string` return type of `FormFieldType::category()` with `FieldCategory`.
2. **Twenty new `FormFieldType` cases**, each with `label()`, `description()`, `icon()`, `category()`, `defaultData()`, and appropriate `hasPlaceholder()` / `hasOptions()` / `hasMinMax()` coverage:
    - **Basic**: `Email`, `Phone`, `MarkdownEditor`
    - **Choice**: `MultiSelect`, `CheckboxList`, `ToggleButtons`, `YesNo`, `Rating`, `Ranking`
    - **Advanced**: `Time`, `DateTimePicker`, `DateRange`, `Slider`, `ColorPicker`, `TagsInput`, `KeyValue`, `CodeEditor`, `Repeater`, `Signature`, `Address`
3. **Re-grouping of existing cases** into the new categories:
    - **Basic**: `TextInput`, `Textarea`, `Number`, `RichEditor`
    - **Choice**: `Select` (the "single select / dropdown" per TASK-16), `RadioGroup`, `Checkbox`, `Toggle`
    - **Advanced**: `DatePicker`, `FileUpload`
    - **Layout**: `SectionHeader`, `Divider`, `InstructionalText`, `Image` (unchanged)
4. **`FieldComponentBuilder` match arms** for every new case. The service keeps its current `BuilderPreview` / `PublicForm` divergence model — only the `Image` element actually diverges today; the same hook is available for future per-type divergence.
5. **Composite-field rendering**:
    - `DateRange` → a `Grid(2)` containing two `DatePicker`s, stored as `{start, end}` under the field key.
    - `Address` → a `Fieldset` containing five `TextInput`s (`street`, `suburb`, `state`, `postcode`, `country`), stored as a nested object under the field key.
6. **Signature field** backed by `saade/filament-autograph` (Filament v5 compatible, maintained, wraps `szimek/signature_pad`). Stores as a PNG data URL string.
7. **Rating field** backed by `ToggleButtons` with `->icons()`, `->colors()`, `->hiddenButtonLabels()`, and `->grouped()`. Configurable per field:
    - `max` (default 5, range 3–10)
    - `icon` (star / heart / thumbs-up — maps to Heroicon)
    - Selected buttons colour warning; unselected stay gray via `colors()`.
8. **Ranking field** backed by `Repeater` with `->reorderableWithButtons()->addable(false)->deletable(false)`, seeded with the option list so respondents can only reorder, not add or remove.
9. **Builder property editor** extends `fieldSettingsSchema()` and the layout settings branch with per-type configuration:
    - `Slider`: min, max, step
    - `Rating`: max, icon (enum)
    - `Ranking`: options list (Repeater of `label`)
    - `DateRange`: no extra config beyond shared fields
    - `Address`: no extra config (all 5 sub-fields shown always)
    - `Signature`: no extra config
    - `CodeEditor`: language (Select of a handful of common langs: `php`, `javascript`, `html`, `css`, `json`)
    - `Repeater`: item label, min items, max items
    - `TagsInput`: suggestions list (optional)
    - `KeyValue`: key label, value label
    - `ColorPicker`: format (hex / rgb / hsl)
    - `MultiSelect`, `CheckboxList`, `ToggleButtons`: options list (like existing Select/Radio)
    - `YesNo`: no extra config (hardcoded Yes/No toggle buttons)
    - `MarkdownEditor`: no extra config
10. **`hasOptions()` / `hasMinMax()` / `hasPlaceholder()` coverage** for new types:
    - `hasOptions()` true for `MultiSelect`, `CheckboxList`, `ToggleButtons`, `Ranking`
    - `hasMinMax()` true for `Slider`, `Repeater`
    - `hasPlaceholder()` true for `Email`, `Phone`, `MarkdownEditor`, `TagsInput`, `CodeEditor`
11. **Default data per field type** covers every editor input so loading a newly-added field never shows empty placeholders in the builder.
12. **Submission data integrity**: every new type's value is stored under its key in `submissions.data` exactly as Filament produces it (primitive, array, or nested object). Composite types (`DateRange`, `Address`) land as nested objects, not flattened.
13. **Tests per field type** following the `LayoutElementsTest` pattern: add the field, configure it, save, render on public form, submit, assert correct shape in `submissions.data`.
14. **`filament:optimize`-safe**: new Filament components used must be registered via the normal schema pathways; no manual asset publishing is required for `saade/filament-autograph` beyond what the package does automatically.

### Should Have

- Palette category headers render in a stable order: Basic → Choice → Advanced → Layout.
- New fields pick appropriate Heroicons (envelope for Email, phone for Phone, map-pin for Address, etc.).
- Each new type carries a short `description()` so the palette tooltip is informative.

### Could Have (deferred)

- Google Places autocomplete on `Address` (future enhancement — see Open Questions).
- Half-star ratings.
- Per-locale phone number validation and masking.
- Configurable Repeater sub-schemas (multiple fields per item).
- Signature stroke colour and background customisation exposed to form authors.

## Implementation Sketch

- **Enum**: extend `App\Enums\FormFieldType` with 20 new cases. Introduce `App\Enums\FieldCategory`. Move all `category()` return values from `string` to the enum. Update `grouped()` to use `FieldCategory::cases()` ordering.
- **Defaults**: each new case gets a `defaultData()` branch returning every editor-exposed property pre-populated so the settings panel renders immediately.
- **Render service**: extend `FieldComponentBuilder::buildFilamentComponent()` with the 20 new match arms. Composite fields (`DateRange`, `Address`) use `Grid`/`Fieldset` wrappers. Delegate `Signature` to `Saade\FilamentAutograph\Forms\Components\SignaturePad`.
- **Property editor**: extend `FormBuilderPage::fieldSettingsSchema()` with a new per-type config branch. Keep the common (label, key, helper text, column span, required) block unchanged; add a match on the field type below the common block.
- **Dependencies**: `composer require saade/filament-autograph`. The package auto-registers its assets via Filament's asset pipeline.
- **No migrations**: the schema JSONB structure on `forms.schema` is unchanged — only the set of legal `type` values grows.
- **Test helpers**: reuse `setUpLayoutBuilderTest()` and `makeLayoutFormSchema()` from `tests/TestHelpers.php` (promoted in TASK-46). Add a `makeDataField()` helper if the layout-focused one does not generalise cleanly.

## Success Metrics

- Form authors can add every new field type from the palette in one click.
- A form built from one of each new type saves, reloads, and renders on the public form without errors.
- A submission against such a form lands in `submissions.data` with every expected key and the correct value shape (primitive, array, or nested object).
- All existing tests pass; new per-type tests are green.
- The Filament palette in the builder lists categories in the order Basic → Choice → Advanced → Layout with every type under exactly one header.

## Open Questions

- **Address autocomplete?** Future enhancement — the current implementation is a static fieldset of five TextInputs. A later task can add Google Places or similar autocomplete behind a workspace-level config flag. Noted in the code with a `// future: Google Places autocomplete` comment at the `Address` render site.
- **Rating icon set?** Start with star / heart / thumbs-up. More can be added to the enum later without a migration.
- **Repeater genericity?** This task treats `Repeater` as "list of text inputs". A configurable sub-schema is a substantial separate task; defer.
- **Single vs multi select distinction in the palette?** The existing `Select` case represents "single select (dropdown)" directly — no rename needed. `MultiSelect` is a new distinct case that renders `Select::make($key)->multiple()` so the palette is clearer.

## Testing Strategy

Feature tests (Pest) cover:

1. **Enum coverage**: every new `FormFieldType` case has a `label`, `description`, `icon`, `category`, and `defaultData`. A dataset-driven test iterates `FormFieldType::cases()` and asserts no method throws.
2. **Palette grouping**: `FormFieldType::grouped()` returns cases under exactly the four expected `FieldCategory` keys with the right membership.
3. **Per-type builder add**: for each new type, add it to a form via the builder action, assert the field appears with the expected default data and correct category.
4. **Per-type public render**: the public form page renders the Filament component matching each type (e.g., `SignaturePad` for signature, `ToggleButtons` for rating, `Fieldset` for address).
5. **Per-type submission**: submitting a form with each type stores the correct value shape under the field key in `submissions.data`. Composite types store nested objects.
6. **Composite rendering**: `DateRange` produces a Grid of two DatePickers; `Address` produces a Fieldset with five TextInputs.
7. **Signature package integration**: a submission with a signature field stores a data-URL-shaped string under the field key.
8. **Options-based fields**: `MultiSelect`, `CheckboxList`, `ToggleButtons`, `Ranking` respect their configured options list.
9. **Validation integrity**: a required new-type field blocks submission when empty; an optional new-type field submits fine when empty (except where Filament's component forces a value, e.g., `Toggle` defaults to `false`).
