---
name: commit
description: Create commits following project conventions with task key references and proper formatting. Use whenever the user wants to commit their changes. Handles staging, message formatting, and Co-Authored-By attribution for AI-assisted work.
---

# Commit

Create a properly formatted commit following project conventions.

## Commit Message Format

Follow the canonical policy block in `AGENTS.md` if any local examples conflict.

```
[TASK-42] Add team role management

Implement role assignment and permission checks for team members.
Teams can now have Owner, Admin, and Member roles with hierarchical permissions.

Co-Authored-By: Claude <noreply@anthropic.com>
```

**Rules:**

- **Task key format:** `TASK-<number>` only (e.g., `TASK-42`), as defined by `/task-management`
- **Subject line:** `[TASK-<number>] Imperative mood description` (max ~72 chars)
- **Body:** Optional — explain _what_ and _why_, not _how_. Wrap at 72 chars.
- **Attribution:** Only include `Co-Authored-By: Claude <noreply@anthropic.com>` for AI-assisted changes if using Claude Code. If using another tool such as Cursor or Codex, attribute that tool.

If no task key is available, look up or create one via `TASKS.md` using `/task-management`. Only omit the prefix if the user explicitly requests a no-key commit.

## Process

1. **Check what's changed:**

    ```bash
    git status
    git diff --stat
    ```

2. **Stage the relevant files.** Prefer staging specific files over `git add .`:

    ```bash
    git add app/Models/Team.php app/Enums/TeamRole.php tests/Feature/Teams/TeamTest.php
    ```

3. **Lint before committing** — run Pint on any modified PHP files:

    ```bash
    vendor/bin/pint --dirty --format agent
    ```

    Re-stage any files that Pint modified.

4. **Write the commit message** following the format above. Derive the message from the actual diff, not assumptions.

5. **Create the commit:**

    ```bash
    git commit -m "[TASK-42] Add team role management

    Co-Authored-By: Claude <noreply@anthropic.com>"
    ```

## Migration Commits

If the change includes database migrations, commit them together with the code changes — Laravel projects using SQLite don't have the same schema drift issues as PostgreSQL/MySQL projects. However, review the migration diff to ensure it only contains intended changes.

## What Not to Commit

- `.env` or any environment files
- `vendor/` or `node_modules/`
- IDE configuration files (`.idea/`, `.vscode/`)
- `database/database.sqlite` (the dev database)
