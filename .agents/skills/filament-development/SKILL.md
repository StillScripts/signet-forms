---
name: filament-development
description: "ACTIVATE when working with Filament v5 in this Laravel application. This includes panel configuration, resources (CRUD), custom pages, relation managers, tables, forms/schemas, infolists, actions, notifications, widgets, navigation, tenancy, authentication (login/register/password reset/email verification/MFA), theming, and testing Filament components. Trigger when the user mentions Filament, panel, resource, or references app/Filament/, app/Providers/Filament/, AdminPanelProvider, or any Filament class. Also activate for multi-tenancy configuration, tenant registration/profile pages, or Filament's built-in auth features. Do NOT activate for Flux UI components, standalone Livewire components outside Filament, or Laravel Fortify auth."
---

# Filament v5 Development

Filament is the admin panel and application framework for this project. It provides resources (CRUD), custom pages, forms, tables, actions, notifications, widgets, tenancy, and authentication — all built on Livewire.

## Documentation

Always use `search-docs` with `packages: ["filament/filament"]` before writing Filament code. Filament v5 has significant API changes from v4 — do not guess at method names or component APIs.

## Panel Configuration

The panel is configured in `app/Providers/Filament/AdminPanelProvider.php`. Key configuration methods:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('')                           // URL prefix (empty = root)
        ->tenant(Team::class, slugAttribute: 'slug')  // Multi-tenancy
        ->login()                            // Enable login page
        ->registration()                     // Enable registration page
        ->passwordReset()                    // Enable password reset
        ->emailVerification()                // Enable email verification
        ->emailChangeVerification()          // Verify email changes
        ->profile()                          // Enable profile page
        ->multiFactorAuthentication([...])   // MFA via TOTP or email
        ->viteTheme('resources/css/filament/admin/theme.css')
        ->breadcrumbs(false)
        ->tenantMiddleware([...], isPersistent: true)
        ->tenantRegistration(RegisterTeam::class)
        ->tenantProfile(EditTeamProfile::class);
}
```

## Resources (CRUD)

Resources are the core of Filament — they generate List, Create, and Edit pages for Eloquent models.

```bash
php artisan make:filament-resource Customer --view --no-interaction
```

### Key Concepts

- **Form schema**: Define form fields in `form(Form $form)` using `->schema([...])`
- **Table columns**: Define table columns in `table(Table $table)` using `->columns([...])`
- **Pages**: Each resource has List, Create, Edit (and optionally View) pages
- **Relation managers**: Interactive tables for related records on Edit/View pages
- **Singular resources**: For single-record pages (settings, profile) — no list table

### Tenant Scoping

Resources are automatically scoped to the current tenant when tenancy is configured. To disable for a specific resource:

```php
protected static bool $isScopedToTenant = false;
```

Use `scopedUnique()` and `scopedExists()` instead of Laravel's `unique` and `exists` validation rules in tenant-aware resources.

## Custom Pages

Custom pages are full Livewire components registered in the panel. Use for settings, dashboards, or anything not covered by resources.

```bash
php artisan make:filament-page TeamSettings --no-interaction
```

Custom pages can have their own schemas, actions, and tables using `InteractsWithTable`.

## Forms & Schemas

Filament v5 uses a unified "schema" system for forms, infolists, and page content.

### Available Form Fields

TextInput, Select, Checkbox, Toggle, CheckboxList, Radio, DateTimePicker, FileUpload, RichEditor, MarkdownEditor, Repeater, Builder, TagsInput, Textarea, KeyValue, ColorPicker, ToggleButtons, Slider, CodeEditor, Hidden

### Layout Components

Grid, Flex, Fieldset, Section, Tabs, Wizard, Callout, EmptyState

### Key Patterns

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

// Form with validation
TextInput::make('name')
    ->required()
    ->maxLength(255)

// Select with relationship
Select::make('team_id')
    ->relationship('team', 'name')
    ->required()

// Conditional visibility
TextInput::make('reason')
    ->visible(fn (string $operation) => $operation === 'edit')
```

### Saving to Relationships

Layout components can save fields to related models:

```php
Fieldset::make('Metadata')
    ->relationship('metadata')
    ->schema([
        TextInput::make('title'),
        Textarea::make('description'),
    ])
```

## Tables

Tables display paginated, searchable, sortable lists of records.

### Available Column Types

TextColumn, IconColumn, ImageColumn, ColorColumn, ToggleColumn, SelectColumn, TextInputColumn, CheckboxColumn

### Actions

```php
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;

$table
    ->columns([...])
    ->recordActions([
        Action::make('edit'),
        DeleteAction::make(),
    ])
    ->headerActions([
        Action::make('create'),
    ])
    ->bulkActions([
        BulkAction::make('delete'),
    ]);
```

## Actions & Modals

Actions are buttons that run PHP functions and can open modals with forms.

```php
use Filament\Actions\Action;

Action::make('invite')
    ->label('Invite member')
    ->icon('heroicon-o-plus')
    ->schema([
        TextInput::make('email')->email()->required(),
        Select::make('role')->options([...])->required(),
    ])
    ->action(function (array $data): void {
        // Handle the form submission
    })
    ->requiresConfirmation()  // Show confirmation dialog
    ->visible(fn () => Gate::allows('invite', $team))
```

## Notifications

```php
use Filament\Notifications\Notification;

Notification::make()
    ->title('Saved successfully')
    ->success()
    ->send();
```

## Widgets

Widgets display data on dashboards. Types: stats overview, charts, table widgets.

```bash
php artisan make:filament-widget StatsOverview --stats-overview --no-interaction
```

## Relation Managers

Interactive tables for managing related records (HasMany, BelongsToMany, MorphMany, etc.):

```bash
php artisan make:filament-relation-manager PostResource comments content --no-interaction
```

Test relation managers by passing `ownerRecord` and `pageClass`:

```php
livewire(CommentsRelationManager::class, [
    'ownerRecord' => $post,
    'pageClass' => EditPost::class,
])
    ->assertOk()
    ->assertCanSeeTableRecords($post->comments);
```

## Multi-Tenancy

This project uses Filament's built-in tenancy with the Team model.

### Key Points

- Resources are automatically scoped to the current tenant
- New records are automatically associated with the current tenant
- Queries outside the panel (CLI, queues, API routes) are NOT scoped — you must handle this manually
- Use tenant middleware for additional global scopes on non-resource models
- `Filament::getTenant()` returns the current tenant
- Listen to `Filament\Events\TenantSet` to sync state when tenants switch

### Tenant Registration & Profile

```php
// In panel provider
->tenantRegistration(RegisterTeam::class)
->tenantProfile(EditTeamProfile::class)
```

### Security Considerations

- Only models with resources in the tenancy-enabled panel are automatically scoped
- `withoutGlobalScopes()` disables the tenancy scope — be very careful
- Queries made before tenant identification (early middleware, service providers) are NOT scoped
- Always verify tenant scoping before deploying

## Authentication

Filament v5 has built-in authentication features — no need for Fortify or custom auth controllers.

### Features

- `->login()` — Login page
- `->registration()` — Registration page
- `->passwordReset()` — Password reset via email
- `->emailVerification()` — Email verification
- `->emailChangeVerification()` — Verify email changes
- `->profile()` — User profile page
- `->multiFactorAuthentication([...])` — MFA (TOTP app or email codes)

### User Model Contract

```php
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Or conditional logic per panel
    }
}
```

### Multi-Tenancy Contracts

```php
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasDefaultTenant;

class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant
{
    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->belongsToTeam($tenant);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->currentTeam;
    }
}
```

### MFA Setup

```php
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;

->multiFactorAuthentication([
    AppAuthentication::make(),    // TOTP app (Google Authenticator, etc.)
    EmailAuthentication::make(),  // Email one-time codes
], isRequired: true)  // Force MFA setup on next login
```

MFA is enforced only within the Filament panel auth flow — API routes and non-Filament login pages need separate MFA implementation.

## Theming

```bash
php artisan make:filament-theme
```

This creates `resources/css/filament/{panel}/theme.css`. Register it:

```php
->viteTheme('resources/css/filament/admin/theme.css')
```

**Important**: Filament's default stylesheet only includes its own UI classes. To use custom Tailwind classes in your Blade views, you must create a custom theme. Add `@source` directives to include your app's views:

```css
@import "tailwindcss";

@source '../../../../app/Filament/**/*';
@source '../../../../resources/views/filament/**/*';
```

## Testing Filament Components

### Authentication in Tests

```php
use App\Models\User;

beforeEach(function () {
    actingAs(User::factory()->create());
});
```

### Testing Resource Pages

```php
use App\Filament\Resources\Users\Pages\EditUser;

it('can load the edit page', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => $user->name,
            'email' => $user->email,
        ]);
});

it('can update a user', function () {
    $user = User::factory()->create();
    $newData = User::factory()->make();

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['name' => $newData->name, 'email' => $newData->email])
        ->call('save')
        ->assertNotified();

    assertDatabaseHas(User::class, [
        'id' => $user->id,
        'name' => $newData->name,
    ]);
});
```

### Testing Validation with Datasets

```php
it('validates the form data', function (array $data, array $errors) {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm([...$data])
        ->call('save')
        ->assertHasFormErrors($errors)
        ->assertNotNotified();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`email` is invalid' => [['email' => 'not-an-email'], ['email' => 'email']],
]);
```

### Testing Actions

```php
use Filament\Actions\DeleteAction;

it('can delete a user', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseMissing($user);
});
```

## Artisan Commands

| Command | Purpose |
|---------|---------|
| `make:filament-resource` | Create a new resource (CRUD) |
| `make:filament-page` | Create a custom page |
| `make:filament-relation-manager` | Create a relation manager |
| `make:filament-widget` | Create a widget |
| `make:filament-theme` | Create a custom theme |

Always pass `--no-interaction` and check `--help` for available options.

## Common Pitfalls

- **Guessing API methods**: Filament v5 changed many APIs from v4. Always use `search-docs` first.
- **Missing tenant scoping**: Queries outside the panel (queues, CLI, API) are NOT automatically scoped.
- **Using `unique`/`exists` validation**: Use `scopedUnique()`/`scopedExists()` in tenant-aware resources.
- **Custom Tailwind classes not working**: You must create a custom theme for your own Tailwind classes to be compiled.
- **MFA only covers panel auth**: API routes and other auth paths need separate MFA enforcement.
- **`withoutGlobalScopes()` removes tenancy**: This can leak data across tenants. Use `withoutGlobalScope()` with specific scope names instead.
