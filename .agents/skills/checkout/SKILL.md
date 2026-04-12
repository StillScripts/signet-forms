---
name: checkout
description: Create a new git branch with proper naming conventions. Use whenever starting work on a new task, feature, or bugfix. Handles pulling latest from the correct base branch, creating the branch with the right prefix, and running setup commands so the environment is ready to go.
---

# Checkout

Create a new branch following project conventions and prepare the local environment for development.

**Requires:** A task key in `TASK-<number>` format (e.g., `TASK-42`) or a description of the work. If neither is provided, ask the user.

## Branch Naming

Branches follow the pattern: `{type}/{task-key}-{slug}`

- **Features:** `feature/TASK-42-add-team-roles` — branch from `develop`
- **Hotfixes:** `hotfix/TASK-15-fix-login-redirect` — branch from `main`

The slug should be a short, lowercase, hyphenated description derived from the task.

Task keys must follow `/task-management` as the source of truth.

## Process

1. **Determine branch type.** Ask the user if it's not clear from context:
    - Feature or enhancement → `feature/` from `develop`
    - Urgent production fix → `hotfix/` from `main`

2. **Pull latest from the base branch:**

    ```bash
    git checkout develop && git pull origin develop
    ```

    (or `main` for hotfixes)

3. **Create the branch:**

    ```bash
    git checkout -b feature/TASK-42-add-team-roles
    ```

4. **Confirm** the branch is ready: show the user the branch name and which base it was created from.

## Notes

- If the user provides a task key, use it to look up the task description from TASKS.md to generate the slug automatically.
- If there are uncommitted changes on the current branch, warn the user before switching.
- Always pull the latest base branch to minimise merge conflicts later.
