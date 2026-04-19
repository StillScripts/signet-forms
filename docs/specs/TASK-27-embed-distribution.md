# TASK-27: Embed Distribution

## Problem Statement

Published forms have a public URL (TASK-13) and a downloadable QR code (TASK-26) but cannot be embedded into third-party websites. Customers often want to embed a form inline on their own marketing site, intranet, or landing page without sending respondents away to a standalone URL. We need a first-class iframe embed snippet that form authors can copy-and-paste, plus a layout variant of the public renderer that looks right when rendered inside an iframe (no duplicated page chrome, no excessive padding, transparent background so the host page's styling shows through).

## Goals

1. Generate an `<iframe>` embed snippet for the published form with configurable width and height dimensions.
2. Provide a copy-to-clipboard UI on the Filament form view page alongside the existing public URL and QR code from TASK-26.
3. Render a stripped-down "embed" variant of the public form page when requested via `?embed=1`, removing the outer heading, padding, and page background.
4. Allow form authors to disable embedding entirely (`X-Frame-Options: DENY`) as a safety guardrail.
5. Hide the embed section and block the `?embed=1` renderer for unpublished forms.

## Non-Goals

- **Responsive auto-sizing / postMessage height syncing** — host sites will specify a fixed height; dynamic resize is out of scope.
- **Script-based (non-iframe) embeds** — iframe only for TASK-27.
- **Per-domain embed allowlists / CSP frame-ancestors configuration** — global allow/deny only.
- **White-label embed chrome** — TASK-28 handles respondent branding.

## Implementation

### EmbedSettings value object

Add `App\ValueObjects\Settings\EmbedSettings` with fields:

- `allowEmbedding: bool` (default `true`)
- `width: string` (default `'100%'`)
- `height: int` (default `600`)

Wire it onto `FormSettings` as a new `embed` property, with `fromArray` / `toArray` support mirroring the other `*Settings` objects.

### FormForm tab

Add an **Embed** tab in the Settings `Tabs` list:

- `Toggle::make('settings.embed.allow_embedding')` — "Allow embedding"
- `TextInput::make('settings.embed.width')` — "Width" (e.g. `100%`, `600px`)
- `TextInput::make('settings.embed.height')` — "Height" (numeric, pixels)

### FormInfolist section

Add an **Embed** section on the form view page:

- Visible only when the form is published AND `allow_embedding` is true.
- Render the iframe snippet inside a `<pre>` with Filament copy-to-clipboard functionality.
- Snippet template:
  ```html
  <iframe src="{public_form_url}?embed=1" width="{width}" height="{height}" frameborder="0" style="border:0;" loading="lazy"></iframe>
  ```

### Public renderer embed variant

- `PublicFormPage::mount` reads `request()->query('embed')` and stores a `bool $embedded` property.
- The Livewire view uses a compact layout when embedded: no outer max-width padding, no header card styling, transparent background.
- The controller sends `X-Frame-Options: DENY` when `allow_embedding` is `false` so the iframe will be blocked by the browser.

## Acceptance Criteria

- [ ] `FormSettings::fromArray` / `toArray` round-trip includes an `embed` section.
- [ ] Defaults: `allow_embedding = true`, `width = '100%'`, `height = 600`.
- [ ] Form view page shows Embed section with a copyable iframe snippet when the form is published.
- [ ] Embed section is hidden when the form is unpublished or `allow_embedding` is disabled.
- [ ] The snippet width/height reflects the settings.
- [ ] `GET /forms/{team}/{slug}?embed=1` renders the form with no outer page chrome.
- [ ] `GET /forms/{team}/{slug}?embed=1` returns `X-Frame-Options: DENY` when embedding is disabled.
- [ ] Unpublished forms still return 404 regardless of the `embed` query param.

## Success Metrics

- Authors can copy a working `<iframe>` snippet and paste it into any host page.
- Embedded form renders cleanly (no duplicated chrome) and submits successfully.
- Embedding respects the author's allow/deny toggle.
