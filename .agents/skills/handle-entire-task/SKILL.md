---
name: handle-entire-task
description: Autonomously handle an entire task end-to-end. Reads the task from TASKS.md, checks out a branch, investigates the codebase, implements the change, writes tests, lints, cleans up, commits, creates a PR, and iterates until CI passes. Use when the user wants a task completed from start to finish, says "handle this task", "do TASK-42", or wants autonomous end-to-end development.
---

# Handle Entire Task

Autonomously take a task from TASKS.md to a merge-ready PR. This skill orchestrates the full development workflow by calling other skills in sequence.

**Requires:** Either a task key (format: `TASK-<number>`, e.g., `TASK-42`), a task description, or a reference to an existing task in TASKS.md. If none is provided, ask the user.

## Arguments

The skill accepts one of:

- A task key in `TASK-<number>` format (e.g., `TASK-42`)
- A task description (when no task exists yet)

Task key format and allocation rules are defined in `/task-management` and must be treated as the source of truth.

## Process

### Phase 1: Understand the Task

**Option A — Existing task key provided:**

1. **Read the task** from `TASKS.md`. Look up the task by key and read its description, acceptance criteria, and any notes.

2. **Summarise the task** back to the user in 2-3 sentences. Include:
   - What needs to be done
   - Any constraints or scope limits
   - Your initial read on which files/areas of the codebase are involved

3. **Ask the user to confirm** before proceeding. If anything is unclear or ambiguous, ask clarifying questions now — not mid-implementation.

**Option B — No task key provided:**

1. **Clarify the task** with the user if the description is vague. Get enough detail to understand scope, expected behaviour, and which area of the codebase is involved.

2. **Create a task** in TASKS.md with a new key and description, following `/task-management` key allocation rules.

3. **Continue with the newly created task key** for the remaining phases.

### Phase 2: Set Up the Branch

Use the `/checkout` skill with the task key. This handles:

- Determining feature vs hotfix branch type
- Pulling latest from the correct base branch (`develop` for features, `main` for hotfixes)
- Creating the branch with proper naming (`feature/TASK-42-slug`)
- Running `composer install`, `npm install`, `php artisan migrate`

### Phase 3: Investigate the Codebase

Before writing any code, understand the relevant parts of the codebase. This step prevents wasted effort and ensures you follow existing patterns.

1. **Activate domain skills.** Based on what the task involves, read the relevant skill:
   - Working with Filament pages/resources → `/fluxui-development`
   - Authentication changes → `/fortify-development`
   - Livewire components → `/livewire-development`
   - Any PHP code → `/laravel-best-practices`
   - Styling → `/tailwindcss-development`

2. **Use Laravel Boost MCP** to research before coding:
   - `search-docs` — Look up relevant Laravel/Filament/Livewire documentation
   - `database-schema` — Inspect table structures before writing migrations or queries

3. **Search for related files** — Use Grep/Glob to find models, controllers, actions, Filament pages, and views related to the task.

4. **Read the key files** — Don't guess at patterns. Read the actual code to understand existing conventions, method signatures, and data flow. Check sibling files.

5. **Check for existing tests** — Find test files that cover the area you'll be changing. You'll need to update or add to these.

6. **Identify the scope** — List the files you plan to modify. If the scope is larger than expected, flag this to the user before proceeding.

### Phase 4: Implement

Write the code changes. Follow these principles:

- **Minimal changes** — Only modify what the task requires
- **Follow existing patterns** — Match the style and conventions of surrounding code. Check sibling files.
- **Don't over-engineer** — No speculative abstractions or unnecessary refactoring
- **Use `php artisan make:` commands** — For new models, controllers, migrations, tests, etc. Always pass `--no-interaction` and the correct options.
- **PHP 8.3+ features** — Constructor property promotion, attributes, enums, named arguments, explicit return types
- **Migration safety** — Always generate migration files with `php artisan make:migration` to get correct timestamps. Never create migration files manually.

### Phase 5: Test

1. **Write tests** using Pest following existing patterns. Activate the `/pest-testing` skill for guidance. Use `php artisan make:test --pest {name}` to create test files.

2. **Run existing tests** for the area you've changed:
   ```bash
   php artisan test --compact --filter=RelevantTest
   ```

3. **Run your new tests:**
   ```bash
   php artisan test --compact tests/Feature/YourNewTest.php
   ```

4. If tests fail, diagnose and fix. Don't move to the next phase until tests are green.

5. Every change must be tested — this is a hard rule for this project. The only exception is changes to skill files, config, or documentation.

### Phase 6: Lint

Use the `/lint` skill:

- Run `vendor/bin/pint --dirty --format agent` on all modified PHP files
- If CSS/JS files were changed, run `npm run build` to verify the frontend compiles

Fix any issues before proceeding.

### Phase 7: Clean Up

Use the `/deslop` skill to review the changes for:

- Unnecessary comments that restate the code
- Over-defensive patterns (redundant null checks, empty try/catch)
- Style inconsistencies with surrounding code
- Over-engineering (unnecessary abstractions for single-use code)

Apply valid fixes. Run Pint again after cleanup.

### Phase 8: Find Bugs

Use the `/find-bugs` skill to review your changes before committing. This is the final quality gate — catch security vulnerabilities, bugs, and code quality issues before they reach the PR.

- Run the full checklist against your diff
- Fix any Critical or High severity findings before proceeding
- Medium/Low findings: use your judgment — fix if quick, otherwise note them in the PR description for the reviewer

### Phase 9: Commit

Use the `/commit` skill to create a properly formatted commit:

- `[TASK-<number>] Imperative description` subject line
- Optional body explaining what and why
- `Co-Authored-By: Claude <noreply@anthropic.com>`

### Phase 10: Create PR

Use the `/create-pr` skill to:

- Push the branch to origin
- Create a PR with comprehensive description
- Target the correct base branch (`develop` for features, `main` for hotfixes)
- Include the task key in the PR title and body

### Phase 11: Iterate Until CI Passes

Use the `/iterate-pr` skill to:

- Monitor CI checks (Pint lint + Pest tests across PHP 8.3/8.4/8.5)
- Fix any failures
- Push fixes and re-check until all checks pass

## When to Stop and Ask

**Do not proceed autonomously** in these situations:

- The task is ambiguous about the expected behaviour
- The scope is significantly larger than the task suggests
- You need to modify a file you haven't seen before in a non-obvious way
- Tests are failing for reasons unrelated to your changes
- A design decision has multiple valid approaches (e.g., where to put logic — model vs action vs service)
- The same CI check fails 3+ times after fixes
- The task requires changes to dependencies (`composer.json` or `package.json`)
- Merge conflicts require judgment calls

## When to Skip Phases

- **Phase 5 (Test):** Skip if the change is documentation-only, skill files only, or config-only
- **Phase 6 (Lint):** Skip if no PHP/CSS/JS files were changed
- **Phase 7 (Clean Up):** Skip if the diff is under ~10 lines or is config-only
- **Phase 8 (Find Bugs):** Skip if the change is documentation-only, skill files only, or config-only
- **Phase 11 (Iterate):** The user may prefer to handle CI iteration separately — ask if they want you to wait for CI

## Example Usage

```
User: /handle-entire-task TASK-42
```

Claude reads the task from TASKS.md, summarises it, gets confirmation, then autonomously:

1. Checks out `feature/TASK-42-add-team-role-management`
2. Activates `/laravel-best-practices` and searches docs for role/permission patterns
3. Finds Team model, Membership model, TeamRole enum, and TeamSettings page
4. Implements role assignment with permission checks
5. Writes Pest tests for role changes, permission enforcement, and edge cases
6. Lints — Pint reports 0 issues
7. Cleans up — removes 3 unnecessary comments, simplifies one null check
8. Runs find-bugs — no critical issues, one medium info note added to PR description
9. Commits with `[TASK-42] Add team role management with permission hierarchy`
10. Creates PR targeting develop
11. Iterates until all CI checks pass (PHP 8.3, 8.4, 8.5)

## Tips

- This skill is designed for tasks that are well-defined. Vague tasks need human clarification first.
- The skill calls other skills by name. If a sub-skill fails, surface the error rather than silently continuing.
- Always activate the relevant domain skills (laravel-best-practices, livewire-development, etc.) during Phase 3. They contain critical conventions that prevent rework.
- Use `search-docs` from Laravel Boost MCP liberally — don't guess at API surfaces.
- Always read before writing. Understanding the codebase prevents wasted effort.
