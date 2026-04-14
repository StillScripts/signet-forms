# TASK-26: QR Code Generation

## Problem Statement

Published forms have a public URL that can be shared, but there is no quick way to generate a scannable QR code for physical distribution (printed flyers, posters, event signage). Users need to generate and download QR codes directly from the form view page.

## Goals

1. Generate QR codes encoding the published form's public URL
2. Display the QR code on the form view page alongside the existing share URL
3. Provide PNG and SVG download options
4. Only show QR code for published forms

## Non-Goals

- **QR code customisation** (colours, logos, sizes) — keep it simple with standard black-on-white
- **Tracking/analytics via QR codes** — TASK-32 will handle analytics
- **Bulk QR code generation** — single form at a time

## Implementation

### Package

Use `chillerlan/php-qrcode` — a well-maintained, zero-dependency PHP QR code library that supports both PNG and SVG output natively.

### Service

Create `App\Services\QrCodeService` with methods:
- `generateSvg(string $url, int $size = 300): string` — returns SVG markup
- `generatePng(string $url, int $size = 300): string` — returns PNG binary data

### Filament Integration

Add a QR code section to the `FormInfolist` (displayed on `ViewForm` page):
- Visible only when the form is published
- Display the QR code as an SVG inline
- Add download actions for PNG and SVG formats

### Download Route

Add a controller route for downloading QR codes:
- `GET /forms/{form}/qr-code/{format}` where format is `png` or `svg`
- Authenticated, with policy check (user must be able to view the form)
- Returns file download response with appropriate content type

## Acceptance Criteria

- [ ] QR code displays on form view page when form is published
- [ ] QR code encodes the correct public form URL
- [ ] PNG download works and produces a valid image
- [ ] SVG download works and produces valid SVG
- [ ] QR code section is hidden for unpublished forms
- [ ] Scanning the QR code navigates to the correct form URL

## Success Metrics

- QR code renders correctly on the view page
- Both download formats produce scannable codes
- All existing tests continue to pass
