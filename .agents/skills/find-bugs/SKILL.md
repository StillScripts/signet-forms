---
name: find-bugs
description: Review code changes for security vulnerabilities, bugs, and quality issues specific to Laravel, Filament, and Livewire. Use before creating a PR, during code review, or whenever the user wants a thorough check of their changes. Covers SQL injection, mass assignment, authorization gaps, and Laravel-specific pitfalls.
---

# Find Bugs

Perform a security and quality review of the current branch's changes, tailored to this Laravel + Filament + Livewire stack.

## Process

1. **Get the full diff:**
   ```bash
   git diff develop...HEAD
   ```
   (Use `main` for hotfix branches.)

2. **Map the attack surface.** For each changed file, note what it touches:
   - Models → mass assignment, relationships, scopes
   - Controllers/Actions → authorization, input validation
   - Filament Pages → tenant isolation, form validation, table actions
   - Migrations → data integrity, rollback safety
   - Routes → middleware, authentication guards
   - Blade/Livewire → XSS, unescaped output

3. **Run through the security checklist:**

   **Authorization & Tenancy:**
   - Are Filament pages properly scoped to the current tenant?
   - Do policy checks exist for all destructive actions?
   - Is `Gate::allows()` or `$this->authorize()` used before state changes?
   - Can a user from Team A access Team B's resources?

   **Mass Assignment:**
   - Are `$fillable` or `#[Fillable]` attributes correctly set?
   - Are there any `Model::create($request->all())` patterns? (These bypass fillable.)

   **SQL & Query Safety:**
   - Any raw SQL or `DB::raw()` with user input?
   - N+1 query risks in new relationships or loops?
   - Missing indexes on columns used in `where` clauses?

   **Input Validation:**
   - Are form inputs validated before use?
   - Are custom `Rule` classes used correctly?
   - Does file upload validation check MIME types, not just extensions?

   **Livewire-Specific:**
   - Are public properties that shouldn't be user-modifiable protected?
   - Do Livewire actions validate before persisting?

   **General:**
   - Secrets or credentials in committed code?
   - Debug/log statements left in production code?
   - Error messages that leak internal details?

4. **Verify each finding** against the actual codebase before reporting. Check if existing middleware, policies, or base classes already handle the concern. False positives waste the user's time.

5. **Report findings** grouped by severity:
   - **Critical** — Security vulnerabilities that could be exploited
   - **Warning** — Bugs or logic errors that will cause incorrect behaviour
   - **Info** — Code quality suggestions and minor improvements

## Notes

- Always use `search-docs` (Laravel Boost MCP) to verify security best practices against the current Laravel 13 docs before flagging something as an issue.
- This project uses Filament's built-in tenant scoping — check whether the framework already handles a concern before reporting it.
- Focus on the diff, not the entire codebase. The goal is to catch issues in _new_ code, not audit the whole project.
