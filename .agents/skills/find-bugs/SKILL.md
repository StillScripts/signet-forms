---
name: find-bugs
description: Find bugs, security vulnerabilities, and code quality issues in local branch changes. Use when asked to review changes, find bugs, security review, or audit code on the current branch. Covers Laravel, Filament, and Livewire-specific pitfalls alongside OWASP top 10.
---

# Find Bugs

Review changes on this branch for bugs, security vulnerabilities, and code quality issues.

## Phase 1: Complete Input Gathering

1. Determine the base branch: use `main` for hotfix branches, `develop` for everything else
2. Get the FULL diff: `git diff <base-branch>...HEAD`
2. If output is truncated, read each changed file individually until you have seen every changed line
3. List all files modified in this branch before proceeding

## Phase 2: Attack Surface Mapping

For each changed file, identify and list:

- All user inputs (request params, headers, body, URL components)
- All database queries (Eloquent, Query Builder, raw SQL)
- All authentication/authorization checks (Gates, policies, middleware)
- All session/state operations
- All external calls (HTTP, queues, notifications)
- All cryptographic operations
- All file uploads and storage operations

Then classify the file by what it touches:

- **Models** → mass assignment, relationships, scopes, casts
- **Controllers/Actions** → authorization, input validation, response data
- **Filament Pages/Resources** → tenant isolation, form validation, table actions, bulk actions
- **Migrations** → data integrity, rollback safety, index coverage
- **Routes** → middleware, authentication guards, rate limiting
- **Blade/Livewire** → XSS, unescaped output, public property exposure

## Phase 3: Security Checklist (check EVERY item for EVERY file)

### General Security (OWASP)

- [ ] **Injection**: SQL injection (especially `DB::raw()`, `whereRaw()` with user input), command injection, template injection, header injection
- [ ] **XSS**: All outputs in templates properly escaped? Unescaped `{!! !!}` justified?
- [ ] **Authentication**: Auth checks on all protected operations?
- [ ] **Authorization/IDOR**: Access control verified, not just auth? Can User A access User B's resources?
- [ ] **CSRF**: State-changing operations protected?
- [ ] **Race conditions**: TOCTOU in any read-then-write patterns?
- [ ] **Session**: Fixation, expiration, secure flags?
- [ ] **Cryptography**: Secure random, proper algorithms, no secrets in logs?
- [ ] **Information disclosure**: Error messages, logs, debug output, timing attacks?
- [ ] **DoS**: Unbounded operations, missing rate limits, resource exhaustion?
- [ ] **Business logic**: Edge cases, state machine violations, numeric overflow?

### Laravel-Specific

Before checking Laravel code, read the relevant rules from the `laravel-best-practices` skill at `.agents/skills/laravel-best-practices/rules/`. Key rule files to consult based on what changed:

| Changes touch | Read rule file |
|---------------|---------------|
| Models, relationships, scopes | `eloquent.md` |
| Database queries, joins, subqueries | `advanced-queries.md`, `db-performance.md` |
| Migrations | `migrations.md` |
| Controllers, routes | `routing.md`, `architecture.md` |
| Form validation | `validation.md` |
| Authorization, auth guards | `security.md` |
| Blade templates | `blade-views.md` |
| Caching | `caching.md` |
| Jobs, queues | `queue-jobs.md` |
| Events, notifications | `events-notifications.md` |
| Error handling | `error-handling.md` |
| HTTP client usage | `http-client.md` |
| Scheduled commands | `scheduling.md` |
| Config or environment | `config.md` |
| Collections | `collections.md` |
| Tests | `testing.md` |
| Code style | `style.md` |

Flag deviations from those rules. Additionally, always check for:

- [ ] **Mass assignment**: Are `$fillable` or `$guarded` correctly set? Any `Model::create($request->all())`?
- [ ] **N+1 queries**: New relationships or loops loading related models without eager loading?
- [ ] **Missing indexes**: Columns used in `where`/`orderBy` clauses without indexes?
- [ ] **Validation**: Inputs validated with Form Requests or inline rules before use?
- [ ] **File uploads**: MIME type validation (not just extension)? Storage path sanitized?

### Filament-Specific

- [ ] **Tenant isolation**: Are pages/resources properly scoped to the current tenant?
- [ ] **Policy checks**: Do destructive actions use `Gate::allows()` or `$this->authorize()`?
- [ ] **Cross-tenant access**: Can a user from Team A access Team B's resources through Filament actions?
- [ ] **Form schema validation**: Are Filament form inputs validated before the action runs?

### Livewire-Specific

- [ ] **Public property exposure**: Are public properties that shouldn't be user-modifiable protected with `#[Locked]`?
- [ ] **Action validation**: Do Livewire actions validate before persisting?

## Phase 4: Verification

For each potential issue:

- Check if it's already handled elsewhere in the changed code
- Check if existing middleware, policies, or base classes already handle the concern
- Search for existing tests covering the scenario
- Read surrounding context to verify the issue is real
- Use `search-docs` (Laravel Boost MCP) to verify security best practices against current Laravel/Filament docs before flagging

**Important**: This project uses Filament's built-in tenant scoping — verify whether the framework already handles a concern before reporting it. False positives waste time.

## Phase 5: Pre-Conclusion Audit

Before finalizing, you MUST:

1. List every file you reviewed and confirm you read it completely
2. List every checklist item and note whether you found issues or confirmed it's clean
3. List any areas you could NOT fully verify and why
4. Only then provide your final findings

## Output Format

**Prioritize**: security vulnerabilities > bugs > code quality

**Skip**: stylistic/formatting issues (Pint handles those)

For each issue:

- **File:Line** — Brief description
- **Severity**: Critical / High / Medium / Low
- **Problem**: What's wrong
- **Evidence**: Why this is real (not already fixed, no existing test, no framework protection, etc.)
- **Fix**: Concrete suggestion
- **References**: OWASP, Laravel docs, or other standards if applicable

If you find nothing significant, say so — don't invent issues.

**Do not make changes — just report findings.** The user will decide what to address.
